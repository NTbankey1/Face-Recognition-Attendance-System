<?php
declare(strict_types=1);

require_once __DIR__ . '/../../database/database_connection.php';
require_once __DIR__ . '/../lib/php_functions.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

const FACE_LOGIN_DEFAULT_MIN_SCORE = 0.55;
const FACE_LOGIN_TIMEOUT_SECONDS = 6;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'code' => 'method_not_allowed',
        'message' => 'Chỉ hỗ trợ phương thức POST cho endpoint này.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput ?? '', true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'code' => 'invalid_json',
        'message' => 'Payload gửi lên không hợp lệ, vui lòng kiểm tra định dạng JSON.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$imageData = $data['image'] ?? null;
$userTypeHint = $data['userType'] ?? null;
$width = isset($data['width']) ? (int) $data['width'] : null;
$height = isset($data['height']) ? (int) $data['height'] : null;
$minScoreOverride = isset($data['minScore']) ? (float) $data['minScore'] : null;

if (!is_string($imageData) || trim($imageData) === '') {
    http_response_code(422);
    echo json_encode([
        'status' => 'error',
        'code' => 'missing_image',
        'message' => 'Thiếu dữ liệu hình ảnh base64 để nhận diện khuôn mặt.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$allowedRoles = ['administrator', 'lecture'];
if ($userTypeHint !== null && !in_array($userTypeHint, $allowedRoles, true)) {
    $userTypeHint = null; // Bỏ qua nếu không hợp lệ
}

$minScore = FACE_LOGIN_DEFAULT_MIN_SCORE;
$envMinScore = getenv('FACE_LOGIN_MIN_SCORE');
if ($envMinScore !== false && is_numeric($envMinScore)) {
    $minScore = (float) $envMinScore;
}
if ($minScoreOverride !== null) {
    $minScore = $minScoreOverride;
}
$minScore = max(0.2, min($minScore, 0.95));

try {
    $matchResponse = callFaceService($imageData, $width, $height);
} catch (RuntimeException $exception) {
    http_response_code(503);
    echo json_encode([
        'status' => 'error',
        'code' => 'face_service_unavailable',
        'message' => 'Không thể kết nối tới dịch vụ nhận diện khuôn mặt. Vui lòng kiểm tra backend.',
        'details' => $exception->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$matches = $matchResponse['matches'] ?? [];
if (!is_array($matches) || empty($matches)) {
    http_response_code(404);
    echo json_encode([
        'status' => 'error',
        'code' => 'no_face_detected',
        'message' => 'Không tìm thấy khuôn mặt phù hợp trong khung hình. Hãy thử lại với ánh sáng và khoảng cách tốt hơn.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

usort($matches, static function ($a, $b) {
    return ($b['score'] ?? 0) <=> ($a['score'] ?? 0);
});

$bestMatch = $matches[0];
$bestLabel = $bestMatch['label'] ?? '';
$bestScore = isset($bestMatch['score']) ? (float) $bestMatch['score'] : 0.0;

if ($bestLabel === '' || $bestScore < $minScore) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'code' => 'low_confidence',
        'message' => 'Độ tin cậy nhận diện chưa đủ cao để đăng nhập tự động.',
        'score' => $bestScore,
        'requiredScore' => $minScore,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $identity = resolveFaceIdentity($pdo, $bestLabel, $userTypeHint, $bestScore);
} catch (RuntimeException $exception) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'code' => 'identity_resolution_failed',
        'message' => 'Không thể ánh xạ khuôn mặt sang tài khoản hệ thống.',
        'details' => $exception->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($identity === null) {
    http_response_code(403);
    echo json_encode([
        'status' => 'error',
        'code' => 'user_not_mapped',
        'message' => 'Nhãn khuôn mặt chưa được cấu hình với tài khoản nào. Vui lòng liên hệ quản trị viên.',
        'label' => $bestLabel,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$user = $identity['user'];
$role = $identity['role'];

$_SESSION['user'] = [
    'id' => $user['Id'],
    'email' => $user['emailAddress'] ?? $user['email'] ?? '',
    'name' => trim(($user['firstName'] ?? '') . ' ' . ($user['lastName'] ?? '')) ?: ($user['firstName'] ?? $bestLabel),
    'role' => $role,
];

try {
    log_event($role, 'face_login', [
        'label' => $bestLabel,
        'score' => $bestScore,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        'userAgent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
    ], 'face-login');
} catch (Throwable $ignored) {
    // Logging không ảnh hưởng dòng chính
}

http_response_code(200);
echo json_encode([
    'status' => 'success',
    'message' => 'Đăng nhập bằng khuôn mặt thành công.',
    'redirect' => 'home',
    'match' => [
        'label' => $bestLabel,
        'score' => $bestScore,
    ],
], JSON_UNESCAPED_UNICODE);
exit;

/**
 * @throws RuntimeException
 */
function callFaceService(string $image, ?int $width, ?int $height): array
{
    $serviceUrl = resolveFaceServiceUrl();
    $endpoint = rtrim($serviceUrl, '/') . '/match';

    $payload = [
        'image' => $image,
    ];
    if ($width !== null && $width > 0) {
        $payload['width'] = $width;
    }
    if ($height !== null && $height > 0) {
        $payload['height'] = $height;
    }

    $ch = curl_init($endpoint);
    if ($ch === false) {
        throw new RuntimeException('Không khởi tạo được kết nối cURL.');
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => FACE_LOGIN_TIMEOUT_SECONDS,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload),
    ]);

    $responseBody = curl_exec($ch);
    if ($responseBody === false) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new RuntimeException('Lỗi khi gọi dịch vụ nhận diện: ' . $error);
    }

    $statusCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE) ?: curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($statusCode !== 200) {
        throw new RuntimeException('Dịch vụ nhận diện phản hồi lỗi HTTP ' . $statusCode);
    }

    $decoded = json_decode($responseBody, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('Không thể phân tích phản hồi từ dịch vụ nhận diện.');
    }

    return $decoded;
}

function resolveFaceServiceUrl(): string
{
    if (defined('FACE_SERVICE_URL')) {
        $constVal = FACE_SERVICE_URL;
        if (is_string($constVal) && $constVal !== '') {
            return $constVal;
        }
    }

    $env = getenv('FACE_SERVICE_URL');
    if (is_string($env) && $env !== '') {
        return $env;
    }

    return 'http://localhost:8001';
}

/**
 * @return array{role:string, user:array}|null
 */
function resolveFaceIdentity(PDO $pdo, string $label, ?string $roleHint, float $score)
{
    $label = trim($label);
    if ($label === '') {
        return null;
    }

    ensureFaceLoginMapTable($pdo);

    $mapping = fetchFaceMapping($pdo, $label);
    if ($mapping !== null) {
        if (!(int) $mapping['active']) {
            return null; // mapping bị vô hiệu hóa
        }
        $identity = fetchUserByMapping($pdo, $mapping['user_type'], (int) $mapping['user_id']);
        if ($identity === null) {
            return null;
        }
        touchFaceMapping($pdo, $label, $score);
        return $identity;
    }

    // Tự động suy luận theo gợi ý role hoặc định dạng label
    $candidates = inferIdentityByLabel($pdo, $label, $roleHint);
    if ($candidates === null) {
        return null;
    }

    storeFaceMapping($pdo, $label, $candidates['role'], (int) $candidates['user']['Id'], $score);
    return $candidates;
}

function ensureFaceLoginMapTable(PDO $pdo): void
{
    static $created = false;
    if ($created) {
        return;
    }

    $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `face_login_map` (
    `label` VARCHAR(191) NOT NULL,
    `user_type` ENUM('administrator','lecture') NOT NULL,
    `user_id` INT NOT NULL,
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `last_score` FLOAT DEFAULT NULL,
    `last_seen_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`label`),
    KEY `idx_face_user` (`user_type`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

    $pdo->exec($sql);
    $created = true;
}

function fetchFaceMapping(PDO $pdo, string $label): ?array
{
    $stmt = $pdo->prepare('SELECT label, user_type, user_id, active FROM face_login_map WHERE label = :label LIMIT 1');
    $stmt->execute([':label' => $label]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function touchFaceMapping(PDO $pdo, string $label, float $score): void
{
    $stmt = $pdo->prepare('UPDATE face_login_map SET last_score = :score, last_seen_at = NOW() WHERE label = :label');
    $stmt->execute([
        ':score' => $score,
        ':label' => $label,
    ]);
}

function storeFaceMapping(PDO $pdo, string $label, string $role, int $userId, float $score): void
{
    $stmt = $pdo->prepare('REPLACE INTO face_login_map (label, user_type, user_id, active, last_score, last_seen_at) VALUES (:label, :role, :user_id, 1, :score, NOW())');
    $stmt->execute([
        ':label' => $label,
        ':role' => $role,
        ':user_id' => $userId,
        ':score' => $score,
    ]);
}

/**
 * @return array{role:string, user:array}|null
 */
function inferIdentityByLabel(PDO $pdo, string $label, ?string $roleHint)
{
    $labelLower = mb_strtolower($label, 'UTF-8');

    if ($roleHint === null || $roleHint === 'administrator') {
        // Ưu tiên khớp email quản trị viên
        $admin = findAdminByLabel($pdo, $labelLower);
        if ($admin !== null) {
            return ['role' => 'administrator', 'user' => $admin];
        }
    }

    if ($roleHint === null || $roleHint === 'lecture') {
        $lecture = findLectureByLabel($pdo, $labelLower);
        if ($lecture !== null) {
            return ['role' => 'lecture', 'user' => $lecture];
        }
    }

    return null;
}

function findAdminByLabel(PDO $pdo, string $labelLower): ?array
{
    // Khớp email hoặc ID
    $stmt = $pdo->prepare('SELECT * FROM tbladmin WHERE LOWER(emailAddress) = :email LIMIT 1');
    $stmt->execute([':email' => $labelLower]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($admin) {
        return $admin;
    }

    if (ctype_digit($labelLower)) {
        $stmt = $pdo->prepare('SELECT * FROM tbladmin WHERE Id = :id LIMIT 1');
        $stmt->execute([':id' => (int) $labelLower]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($admin) {
            return $admin;
        }
    }

    return null;
}

function findLectureByLabel(PDO $pdo, string $labelLower): ?array
{
    // Khớp email, mã giảng viên hoặc ID
    $stmt = $pdo->prepare('SELECT * FROM tbllecture WHERE LOWER(emailAddress) = :email LIMIT 1');
    $stmt->execute([':email' => $labelLower]);
    $lecture = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($lecture) {
        return $lecture;
    }

    if (ctype_digit($labelLower)) {
        $stmt = $pdo->prepare('SELECT * FROM tbllecture WHERE Id = :id LIMIT 1');
        $stmt->execute([':id' => (int) $labelLower]);
        $lecture = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($lecture) {
            return $lecture;
        }
    }

    // Khớp registrationNumber nếu trùng với nhãn (một số tổ chức dùng vậy)
    $stmt = $pdo->prepare('SELECT l.* FROM tbllecture l JOIN tblstudents s ON LOWER(s.registrationNumber) = :reg AND s.email = l.emailAddress LIMIT 1');
    $stmt->execute([':reg' => $labelLower]);
    $lecture = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($lecture) {
        return $lecture;
    }

    return null;
}

/**
 * @return array{role:string, user:array}|null
 */
function fetchUserByMapping(PDO $pdo, string $role, int $userId)
{
    if ($role === 'administrator') {
        $stmt = $pdo->prepare('SELECT * FROM tbladmin WHERE Id = :id LIMIT 1');
    } elseif ($role === 'lecture') {
        $stmt = $pdo->prepare('SELECT * FROM tbllecture WHERE Id = :id LIMIT 1');
    } else {
        return null;
    }
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        return null;
    }
    return ['role' => $role, 'user' => $user];
}

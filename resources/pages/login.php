<?php

if (!function_exists('verify_password_compat')) {
    /**
     * Cho phép đăng nhập với cả mật khẩu đã hash (bcrypt) và dữ liệu cũ lưu plain text hoặc md5/sha1.
     * Ưu tiên dùng password_hash/password_verify; các nhánh còn lại chỉ nhằm tương thích dữ liệu đã có.
     */
    function verify_password_compat(string $inputPassword, string $storedPassword): bool
    {
        if ($storedPassword === '') {
            return false;
        }

        $info = password_get_info($storedPassword);
        if (($info['algo'] ?? 0) !== 0 && ($info['algoName'] ?? 'unknown') !== 'unknown') {
            return password_verify($inputPassword, $storedPassword);
        }

        if (preg_match('/^\$2[aby]\$/', $storedPassword)) {
            return password_verify($inputPassword, $storedPassword);
        }

        if (preg_match('/^[a-f0-9]{32}$/i', $storedPassword)) {
            return hash_equals($storedPassword, md5($inputPassword));
        }

        if (preg_match('/^[a-f0-9]{40}$/i', $storedPassword)) {
            return hash_equals($storedPassword, sha1($inputPassword));
        }

        return hash_equals($storedPassword, $inputPassword);
    }
}

//handle user login logics 



$errors = [];


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = isset($_POST['email']) ? trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL)) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $userType = isset($_POST['user_type']) ? $_POST['user_type'] : '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Email không hợp lệ';
    }

    if (empty($password)) {
        $errors['password'] = 'Mật khẩu không được để trống';
    }

    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
    } else {
        // Thứ tự ưu tiên: nếu người dùng chọn vai trò, thử vai trò đó trước, sau đó thử vai trò còn lại
        $roles = in_array($userType, ['administrator', 'lecture'], true)
            ? [$userType, $userType === 'administrator' ? 'lecture' : 'administrator']
            : ['administrator', 'lecture'];

        $roleMatched = null;
        $user = null;

        foreach ($roles as $role) {
            if ($role === 'administrator') {
                $stmt = $pdo->prepare("SELECT * FROM tbladmin WHERE emailAddress = :email");
            } else {
                $stmt = $pdo->prepare("SELECT * FROM tbllecture WHERE emailAddress = :email");
            }
            $stmt->execute(['email' => $email]);
            $row = $stmt->fetch();
            if ($row && verify_password_compat($password, $row['password'])) {
                $roleMatched = $role;
                $user = $row;
                break;
            }
        }

        if ($roleMatched && $user) {
            $_SESSION['user'] = [
                'id' => $user['Id'],
                'email' => $user['emailAddress'],
                'name' => $user['firstName'],
                'role' => $roleMatched,
            ];
            header('Location: home');
            exit();
        } else {
            $errors['login'] = 'Email hoặc mật khẩu không đúng';
            $_SESSION['errors'] = $errors;
        }
    }
}
if (isset($_SESSION['errors'])) {
    $errors = $_SESSION['errors'];
}


function display_error($error, $is_main = false)
{
    global $errors;
    if (isset($errors[$error])) {
        $class = $is_main ? 'error-main' : 'error';
        $message = htmlspecialchars($errors[$error], ENT_QUOTES, 'UTF-8');
        printf("<div class='%s'><p>%s</p></div>", $class, $message);
    }
}
?>


<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> Đăng nhập hệ thống </title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="resources/assets/css/login_styles.css">
</head>

<body>

    <div class="container" id="signIn">
        <h1 class="form-title">Đăng nhập</h1>
        <?php
        display_error('login', true);
        ?>
        <form method="POST" action="">
            <div class="input-group">
                <i class="fas fa-times"></i>

                <select name="user_type" id="userTypeSelect">
                    <option value="">Chọn loại người dùng (tự động nhận dạng nếu bỏ trống)</option>
                    <option value="lecture">Người dùng</option>
                    <option value="administrator">Quản trị viên</option>
                </select>
            </div>
            <div class="input-group">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" id="email" placeholder="Email" required>
                <?php
                display_error('email');
                ?>
            </div>
            <div class="input-group password">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" id="password" placeholder="Mật khẩu" required>
                <i id="eye" class="fa fa-eye"></i>
                <?php
                display_error('password')
                ?>
            </div>
            <p class="recover">
                <a href="#">Quên mật khẩu?</a>
            </p>
            <input type="submit" class="btn" value="Đăng nhập" name="login">
        </form>
        <div class="face-login-section">
            <button type="button" class="btn face-login-btn" id="faceLoginButton">
                <i class="fas fa-camera"></i>
                Đăng nhập bằng khuôn mặt
            </button>
            <p class="face-login-note">Hãy chắc chắn đã chọn đúng loại người dùng ở trên trước khi bắt đầu.</p>
            <div id="faceLoginInlineMessage" class="face-login-inline-message" aria-live="polite"></div>
        </div>
        <p class="or">
            ----------hoặc--------
        </p>
        <div class="icons">
            <i class="fab fa-google" title="Đăng nhập Google (sắp ra mắt)"></i>
            <i class="fab fa-facebook" title="Đăng nhập Facebook (sắp ra mắt)"></i>
        </div>

    </div>

    <div id="faceLoginModal" class="face-login-modal" aria-hidden="true">
        <div class="face-login-backdrop"></div>
        <div class="face-login-dialog" role="dialog" aria-modal="true" aria-labelledby="faceLoginTitle">
            <button type="button" class="face-login-close" id="faceLoginClose" aria-label="Đóng">
                <i class="fas fa-times"></i>
            </button>
            <h2 id="faceLoginTitle">Đăng nhập bằng khuôn mặt</h2>
            <p class="face-login-hint">Giữ khuôn mặt bạn trong khung hình, nhìn thẳng và đảm bảo ánh sáng tốt.</p>
            <div class="face-login-video">
                <video id="faceLoginVideo" autoplay playsinline muted></video>
            </div>
            <div class="face-login-status">
                <span id="faceLoginStatusText">Đang khởi tạo camera...</span>
                <span id="faceLoginLoader" class="face-login-loader" hidden></span>
            </div>
            <div class="face-login-actions">
                <button type="button" id="faceLoginCancel" class="face-login-action">Hủy</button>
                <button type="button" id="faceLoginRetry" class="face-login-action" hidden>Thử lại</button>
            </div>
        </div>
    </div>

    <div id="faceLoginToast" class="face-login-toast" role="status" aria-live="polite"></div>

    <script src="resources/assets/javascript/script.js"></script>
    <script src="resources/assets/javascript/face_logics/login.js"></script>
</body>

</html>

<?php
function user()
{
    if (isset($_SESSION['user'])) {
        return (object) $_SESSION['user'];
    }
    return null;
}

/**
 * Resolve the base directory for face-label images, allowing override through FACE_LABELS_DIR.
 *
 * @return string Absolute path to the labels directory.
 */
function labels_base_path(): string
{
    static $cached = null;
    if ($cached === null) {
        $override = getenv('FACE_LABELS_DIR');
        if (is_string($override) && $override !== '') {
            // Treat relative overrides as relative to the resources directory
            if ($override[0] !== '/' && !preg_match('/^[A-Za-z]:[\\\\\\/]/', $override)) {
                $base = dirname(__DIR__) . '/' . trim($override, '/');
            } else {
                $base = rtrim($override, '/');
            }
        } else {
            $base = dirname(__DIR__) . '/labels';
        }
        $cached = rtrim($base, '/');
    }
    if (!is_dir($cached)) {
        @mkdir($cached, 0775, true);
    }
    return $cached;
}

/**
 * Ensure the label folder for a registration number exists and return its path.
 *
 * @param string $registrationNumber
 * @return string Absolute path to the student's label folder.
 */
function labels_folder_for(string $registrationNumber): string
{
    $safe = preg_replace('/[^A-Za-z0-9@._-]/', '_', $registrationNumber);
    $folder = labels_base_path() . '/' . $safe;
    if (!is_dir($folder)) {
        @mkdir($folder, 0775, true);
    }
    return $folder;
}

function getFacultyNames()
{
    global $pdo;
    $sql = "SELECT * FROM tblfaculty";
    $stmt = $pdo->query($sql);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $facultyNames = array();
    if ($result) {
        foreach ($result as $row) {
            $facultyNames[] = $row;
        }
    }

    return $facultyNames;
}
function getLectureNames()
{
    global $pdo;
    $sql = "SELECT Id, firstName, lastName FROM tbllecture";
    $stmt = $pdo->query($sql);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $lectureNames = array();
    if ($result) {
        foreach ($result as $row) {
            $lectureNames[] = $row;
        }
    }

    return $lectureNames;
}
function getCourseNames()
{
    global $pdo;
    $sql = "SELECT * FROM tblcourse";
    $stmt = $pdo->query($sql);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $courseNames = array();
    if ($result) {
        foreach ($result as $row) {
            $courseNames[] = $row;
        }
    }

    return $courseNames;
}
function getVenueNames()
{
    $sql = "SELECT className FROM tblvenue";
    $result =  fetch($sql);

    $venueNames = array();
    if ($result) {
        foreach ($result as $row) {
            $venueNames[] = $row;
        }
    }

    return $venueNames;
}
function getUnitNames()
{
    $sql = "SELECT unitCode,name FROM tblunit";
    $result = fetch($sql);

    $unitNames = array();
    if ($result) {
        foreach ($result as $row) {
            $unitNames[] = $row;
        }
    }

    return $unitNames;
}

function showMessage(): void
{
    if (isset($_SESSION['message'])) {
        $message = htmlspecialchars($_SESSION['message'], ENT_QUOTES, 'UTF-8');
        echo " <div id='messageDiv' class='messageDiv'>{$message}</div>";
        echo "<script>
(function(){
  var messageDiv = document.getElementById('messageDiv');
  if (messageDiv) {
    messageDiv.style.opacity = 1;
    setTimeout(function () {
      messageDiv.style.opacity = 0;
    }, 5000);
  }
})();
</script>";
        unset($_SESSION['message']);
    }
}


function total_rows($tablename)
{
    global $pdo;
    if (!preg_match('/^[A-Za-z0-9_]+$/', $tablename)) {
        error_log('Invalid table name requested in total_rows: ' . $tablename);
        echo 0;
        return;
    }
    $stmt = $pdo->query("SELECT * FROM {$tablename}");
    $total_rows = $stmt->rowCount();
    echo $total_rows;
}

function fetch($sql, $params = [])
{
    global $pdo;
    try {
        if (!empty($params)) {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
        } else {
            $stmt = $pdo->query($sql);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $exception) {
        error_log('Database fetch error: ' . $exception->getMessage());
        return [];
    }
}


function fetchStudentRecordsFromDatabase($courseCode, $unitCode)
{
    global $pdo;

    $stmt = $pdo->prepare(
        'SELECT * FROM tblattendance WHERE course = :course AND unit = :unit'
    );
    $stmt->execute([
        ':course' => $courseCode,
        ':unit' => $unitCode,
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function js_asset($links = [])
{
    if ($links) {
        foreach ($links as $link) {
            echo "<script src='resources/assets/javascript/{$link}.js'>
        </script>";
        }
    }
}

// Append-only JSONL logger for audit/export per role
function log_event(string $role, string $category, array $data, string $fileKey = ''): void
{
    try {
        $baseDir = dirname(__DIR__) . '/logs'; // resources/logs
        if (!is_dir($baseDir)) {
            @mkdir($baseDir, 0777, true);
        }
        $roleDir = $baseDir . '/' . $role;
        if (!is_dir($roleDir)) {
            @mkdir($roleDir, 0777, true);
        }
        $datePart = date('Y-m-d');
        $suffix = $fileKey !== '' ? ('_' . preg_replace('/[^A-Za-z0-9_-]/', '-', $fileKey)) : '';
        $file = sprintf('%s/%s%s.jsonl', $roleDir, $datePart, $suffix);
        $payload = [
            'ts' => date('c'),
            'category' => $category,
            'data' => $data,
        ];
        @file_put_contents($file, json_encode($payload, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND | LOCK_EX);
    } catch (Throwable $e) {
        // swallow logging errors
    }
}

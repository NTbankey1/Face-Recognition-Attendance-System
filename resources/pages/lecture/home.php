<?php


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $attendanceData = json_decode(file_get_contents("php://input"), true);
    if ($attendanceData) {
        try {
            $sql = "INSERT INTO tblattendance (studentRegistrationNumber, course, unit, attendanceStatus, dateMarked)  
                VALUES (:studentID, :course, :unit, :attendanceStatus, :date)";

            $stmt = $pdo->prepare($sql);

            foreach ($attendanceData as $data) {
                $studentID = $data['studentID'];
                $attendanceStatus = $data['attendanceStatus'];
                $course = $data['course'];
                $unit = $data['unit'];
                $date = date("Y-m-d");

                // Bind parameters and execute for each attendance record
                $stmt->execute([
                    ':studentID' => $studentID,
                    ':course' => $course,
                    ':unit' => $unit,
                    ':attendanceStatus' => $attendanceStatus,
                    ':date' => $date
                ]);
            }

            $_SESSION['message'] = "Attendance recorded successfully for all entries.";
        } catch (PDOException $e) {
            $_SESSION['message'] = "Error inserting attendance data: " . $e->getMessage();
        }
    } else {
        $_SESSION['message'] = "No attendance data received.";
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="resources/images/logo/attnlg.png" rel="icon">
    <title>lecture Dashboard</title>
    <link rel="stylesheet" href="resources/assets/css/styles.css">
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.2.0/remixicon.css" rel="stylesheet">
</head>


<body>

    <?php include 'includes/topbar.php'; ?>
    <section class="main">
        <?php include 'includes/sidebar.php'; ?>
        <div class="main--content">
            <div id="messageDiv" class="messageDiv" style="display:none;"></div>

            <div class="recognition-layout">
                <div class="recognition-left">
                    <div class="status-header">
                        <div class="service-status" id="serviceStatus">
                            <span class="status-dot offline" id="serviceStatusDot"></span>
                            <span id="serviceStatusText">Chưa kết nối dịch vụ nhận diện</span>
                        </div>
                        <span id="modelsLoader" style="display:none;">Đang tải mô hình...</span>
                    </div>

                    <p class="helper-text">Chọn khóa học, môn học và phòng học trước khi khởi động nhận diện khuôn mặt. Đảm bảo sinh viên ngồi trong khung hình với ánh sáng tốt.</p>

                    <form class="lecture-options" id="selectForm">
                        <select required name="course" id="courseSelect" onChange="updateTable()">
                            <option value="" selected>Chọn khóa học</option>
                            <?php
                            $courseNames = getCourseNames();
                            foreach ($courseNames as $course) {
                                echo '<option value="' . $course["courseCode"] . '">' . $course["name"] . '</option>';
                            }
                            ?>
                        </select>

                        <select required name="unit" id="unitSelect" onChange="updateTable()">
                            <option value="" selected>Chọn môn học</option>
                            <?php
                            $unitNames = getUnitNames();
                            foreach ($unitNames as $unit) {
                                echo '<option value="' . $unit["unitCode"] . '">' . $unit["name"] . '</option>';
                            }
                            ?>
                        </select>

                        <select required name="venue" id="venueSelect" onChange="updateTable()">
                            <option value="" selected>Chọn phòng học</option>
                            <?php
                            $venueNames = getVenueNames();
                            foreach ($venueNames as $venue) {
                                echo '<option value="' . $venue["className"] . '">' . $venue["className"] . '</option>';
                            }
                            ?>
                        </select>
                    </form>

                    <div class="attendance-button">
                        <button id="startButton" class="add">Khởi động nhận diện khuôn mặt</button>
                        <button id="endButton" class="add" style="display:none">Kết thúc phiên</button>
                        <button id="endAttendance" class="add">LƯU điểm danh</button>
                    </div>

                    <div class="stats-bar" id="statusBar">
                        <span>Có mặt: <strong id="presentCount">0</strong></span>
                        <span>Tổng số: <strong id="totalCount">0</strong></span>
                    </div>

                    <div class="video-wrapper video-container" style="display:none;">
                        <video id="video" width="640" height="480" autoplay></video>
                        <canvas id="overlay"></canvas>
                    </div>

                    <div class="table-container">
                        <div id="studentTableContainer"></div>
                    </div>
                </div>

                <div class="recognition-right">
                    <div class="recognition-card">
                        <h3><i class="ri-lightbulb-flash-line"></i>Hướng dẫn nhanh</h3>
                        <ul class="instruction-list">
                            <li>Đảm bảo webcam hoạt động và ánh sáng đều.</li>
                            <li>Sinh viên nhìn thẳng, khoảng cách 50–100cm.</li>
                            <li>Điểm danh từng nhóm nhỏ để tránh trùng lặp.</li>
                            <li>Nhấn “LƯU điểm danh” sau khi kết thúc buổi học.</li>
                        </ul>
                    </div>

                    <div class="recognition-card">
                        <h3><i class="ri-user-smile-line"></i>Nhận diện gần nhất</h3>
                        <div class="recognized-list" id="recognizedList">
                            <div class="empty-state" id="recognizedEmpty">Chưa có dữ liệu nhận diện.</div>
                        </div>
                    </div>
                </div>
            </div>
 
        </div>
    </section>
    <?php
    $faceServiceUrl = null;
    $publicEnv = getenv('FACE_SERVICE_PUBLIC_URL');
    if ($publicEnv && is_string($publicEnv) && $publicEnv !== '') {
        $faceServiceUrl = $publicEnv;
    }
    if (!$faceServiceUrl && defined('FACE_SERVICE_URL')) {
        $faceServiceUrl = FACE_SERVICE_URL;
    }
    if (!$faceServiceUrl) {
        $envUrl = getenv('FACE_SERVICE_URL');
        if ($envUrl && is_string($envUrl) && $envUrl !== '') {
            $faceServiceUrl = $envUrl;
        }
    }
    $faceServiceUrl = is_string($faceServiceUrl) ? trim($faceServiceUrl) : '';
    if ($faceServiceUrl === '' || strpos($faceServiceUrl, 'face_backend') !== false) {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $hostHeader = $_SERVER['HTTP_HOST'] ?? 'localhost:8080';
        $hostParts = explode(':', $hostHeader);
        $hostOnly = $hostParts[0] ?: 'localhost';
        $faceServiceUrl = sprintf('%s://%s:%d', $scheme, $hostOnly, 8001);
    }
    ?>
    <script>
        window.FACE_SERVICE_URL = <?php echo json_encode($faceServiceUrl, JSON_UNESCAPED_SLASHES); ?>;
    </script>
    <?php js_asset(["active_link", 'face_logics/script']) ?>




</body>

</html>

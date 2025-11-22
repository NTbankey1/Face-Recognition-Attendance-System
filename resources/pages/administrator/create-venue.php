<?php


if (isset($_POST["addVenue"])) {
    // Sanitize and validate inputs
    $className = htmlspecialchars(trim($_POST['className']));
    $facultyCode = htmlspecialchars(trim($_POST['faculty']));
    $currentStatus = htmlspecialchars(trim($_POST['currentStatus']));
    $capacity = filter_var($_POST['capacity'], FILTER_VALIDATE_INT);
    $classification = htmlspecialchars(trim($_POST['classification']));

    // Check for required fields
    if (!$className || !$facultyCode || !$currentStatus || !$capacity || !$classification) {
        $_SESSION['message'] = "Vui lòng nhập đầy đủ và hợp lệ.";
    } else {
        $dateRegistered = date("Y-m-d");

        // Prepare database operations using PDO
        try {
            // Check if venue already exists
            $stmt = $pdo->prepare("SELECT * FROM tblvenue WHERE className = :className");
            $stmt->bindParam(':className', $className);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $_SESSION['message'] = "Phòng học đã tồn tại";
            } else {
                // Insert the new venue
                $stmt = $pdo->prepare(
                    "INSERT INTO tblvenue (className, facultyCode, currentStatus, capacity, classification, dateCreated)
                    VALUES (:className, :facultyCode, :currentStatus, :capacity, :classification, :dateCreated)"
                );
                $stmt->bindParam(':className', $className);
                $stmt->bindParam(':facultyCode', $facultyCode);
                $stmt->bindParam(':currentStatus', $currentStatus);
                $stmt->bindParam(':capacity', $capacity, PDO::PARAM_INT);
                $stmt->bindParam(':classification', $classification);
                $stmt->bindParam(':dateCreated', $dateRegistered);

                if ($stmt->execute()) {
                    // log event
                    log_event('admin', 'add_venue', [
                        'className' => $className,
                        'facultyCode' => $facultyCode,
                        'currentStatus' => $currentStatus,
                        'capacity' => $capacity,
                        'classification' => $classification,
                    ], 'venues');

                    $_SESSION['message'] = "Thêm phòng học thành công";
                } else {
                    $_SESSION['message'] = "Thêm phòng học thất bại.";
                }
            }
        } catch (PDOException $e) {
            $_SESSION['message'] = "Lỗi CSDL: " . $e->getMessage();
        }
    }
}


?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="resources/images/logo/attnlg.png" rel="icon">
    <title>Bảng điều khiển</title>
    <link rel="stylesheet" href="resources/assets/css/admin_styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.2.0/remixicon.css" rel="stylesheet">
</head>

<body>
    <?php include 'includes/topbar.php' ?>
    <section class="main">
        <?php include 'includes/sidebar.php'; ?>
        <div class="main--content">

            <div id="overlay"></div>

            <div class="rooms">
                <div class="title">
                    <h2 class="section--title">Khu vực</h2>
                    <div class="rooms--right--btns">
                        <select name="date" id="date" class="dropdown room--filter">
                            <option>Bộ lọc</option>
                            <option value="free">Sẵn sàng</option>
                            <option value="scheduled">Đã lên lịch</option>
                        </select>
                        <button id="addClass1" class="add show-form"><i class="ri-add-line"></i>Thêm phòng học</button>
                    </div>
                </div>
                <div class="rooms--cards">
                    <a href="#" class="room--card">
                        <div class="img--box--cover">
                            <div class="img--box">
                                <img src="resources/images/office image.jpeg" alt="">
                            </div>
                        </div>
                        <p class="free">Văn phòng</p>
                    </a>
                    <a href="#" class="room--card">
                        <div class="img--box--cover">
                            <div class="img--box">
                                <img src="resources/images/class.jpeg" alt="">
                            </div>
                        </div>
                        <p class="free">Lớp</p>
                    </a>

                    <a href="#" class="room--card">
                        <div class="img--box--cover">
                            <div class="img--box">
                                <img src="resources/images/lecture hall.jpeg" alt="">
                            </div>
                        </div>
                        <p class="free">Hội trường</p>
                    </a>

                    <a href="#" class="room--card">
                        <div class="img--box--cover">
                            <div class="img--box">
                                <img src="resources/images/computer lab.jpeg" alt="">
                            </div>
                        </div>
                        <p class="free">Phòng máy</p>
                    </a>
                    <a href="#" class="room--card">
                        <div class="img--box--cover">
                            <div class="img--box">
                                <img src="resources/images/laboratory.jpeg" alt="">
                            </div>
                        </div>
                        <p class="free">Phòng thí nghiệm</p>
                    </a>
                </div>
            </div>
            <?php showMessage() ?>
            <div class="table-container">
                <div class="title" id="addClass2">
                    <h2 class="section--title">Phòng học</h2>
                    <button class="add show-form"><i class="ri-add-line"></i>Thêm phòng</button>
                </div>

                <div class="table">
                    <table>
                        <thead>
                            <tr>
                                <th>Tên phòng</th>
                                <th>Khoa</th>
                                <th>Trạng thái</th>
                                <th>Sức chứa</th>
                                <th>Phân loại</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT * FROM tblvenue";
                            $stmt = $pdo->query($sql);
                            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            if ($result) {
                                foreach ($result as $row)
                                    echo "<tr id='rowvenue" . (int) $row['Id'] . "'>";
                                echo "<td>" . htmlspecialchars($row['className'], ENT_QUOTES, 'UTF-8') . "</td>";
                                echo "<td>" . htmlspecialchars($row['facultyCode'], ENT_QUOTES, 'UTF-8') . "</td>";
                                echo "<td>" . htmlspecialchars($row['currentStatus'], ENT_QUOTES, 'UTF-8') . "</td>";
                                echo "<td>" . htmlspecialchars($row['capacity'], ENT_QUOTES, 'UTF-8') . "</td>";
                                echo "<td>" . htmlspecialchars($row['classification'], ENT_QUOTES, 'UTF-8') . "</td>";
                                echo "<td><span><i class='ri-delete-bin-line delete' data-id='" . (int) $row['Id'] . "' data-name='venue'></i></span></td>";
                                echo "</tr>";
                            } else {
                                echo "<tr><td colspan='6'>Không có dữ liệu</td></tr>";
                            }

                            ?>
                        </tbody>
                    </table>
                </div>

            </div>

            <div class="formDiv-venue" id="addClassForm" style="display:none ">
                <form method="POST" action="" name="addVenue" enctype="multipart/form-data">
                    <div style="display:flex; justify-content:space-around;">
                        <div class="form-title">
                            <p>Thêm phòng</p>
                        </div>
                        <div>
                            <span class="close">&times;</span>
                        </div>
                    </div>
                    <input type="text" name="className" placeholder="Tên phòng" required>
                    <select name="currentStatus" id="">
                        <option value="">--Trạng thái--</option>
                        <option value="availlable">Sẵn sàng</option>
                        <option value="scheduled">Đã lên lịch</option>
                    </select>
                    <input type="text" name="capacity" placeholder="Sức chứa" required>
                    <select required name="classification">
                        <option value="" selected> --Chọn loại phòng--</option>
                        <option value="laboratory">Phòng thí nghiệm</option>
                        <option value="computerLab">Phòng máy</option>
                        <option value="lectureHall">Hội trường</option>
                        <option value="class">Lớp</option>
                        <option value="office">Văn phòng</option>
                    </select>
                    <select required name="faculty">
                        <option value="" selected>Chọn khoa</option>
                        <?php
                        $facultyNames = getFacultyNames();
                        foreach ($facultyNames as $faculty) {
                            echo '<option value="' . $faculty["facultyCode"] . '">' . $faculty["facultyName"] . '</option>';
                        }
                        ?>
                    </select>
                    <input type="submit" class="submit" value="Lưu phòng" name="addVenue">
                </form>
            </div>
        </div>
    </section>
    <?php js_asset(["active_link", "delete_request"]) ?>


    <script>
        const show_form = document.querySelectorAll(".show-form")
        const addClassForm = document.getElementById('addClassForm');
        const overlay = document.getElementById('overlay');
        const closeButtons = document.querySelectorAll('#addClassForm .close');
        show_form.forEach((showForm) => {
            showForm.addEventListener('click', function() {
                addClassForm.style.display = 'block';
                overlay.style.display = 'block';
                document.body.style.overflow = 'hidden';

            });
        })

        closeButtons.forEach(function(closeButton) {
            closeButton.addEventListener('click', function() {
                addClassForm.style.display = 'none';
                overlay.style.display = 'none';
                document.body.style.overflow = 'auto';

            });
        });
    </script>
</body>

</html>

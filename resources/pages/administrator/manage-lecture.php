<?php

if (isset($_POST["addLecture"])) {
    // Securely handle input
    $firstName = htmlspecialchars(trim($_POST["firstName"]));
    $lastName = htmlspecialchars(trim($_POST["lastName"]));
    $email = filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL);
    $phoneNumber = htmlspecialchars(trim($_POST["phoneNumber"]));
    $faculty = htmlspecialchars(trim($_POST["faculty"]));
    $dateRegistered = date("Y-m-d");
    $password = $_POST['password'];

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT); // Secure password hashing

    if ($email && $firstName && $lastName && $phoneNumber && $faculty) {
        try {
            // Check if lecture already exists
            $query = $pdo->prepare("SELECT * FROM tbllecture WHERE emailAddress = :email");
            $query->bindParam(':email', $email);
            $query->execute();

            if ($query->rowCount() > 0) {
                $_SESSION['message'] = "Người dùng đã tồn tại";
            } else {
                // Insert new lecture
                $query = $pdo->prepare("INSERT INTO tbllecture 
                    (firstName, lastName, emailAddress, password, phoneNo, facultyCode, dateCreated) 
                    VALUES (:firstName, :lastName, :email, :password, :phoneNumber, :faculty, :dateCreated)");
                $query->bindParam(':firstName', $firstName);
                $query->bindParam(':lastName', $lastName);
                $query->bindParam(':email', $email);
                $query->bindParam(':password', $hashedPassword);
                $query->bindParam(':phoneNumber', $phoneNumber);
                $query->bindParam(':faculty', $faculty);
                $query->bindParam(':dateCreated', $dateRegistered);

                $query->execute();

                // log event for audit/export
                log_event('admin', 'add_lecture', [
                    'firstName' => $firstName,
                    'lastName' => $lastName,
                    'email' => $email,
                    'phone' => $phoneNumber,
                    'facultyCode' => $faculty,
                ], 'lectures');

                $_SESSION['message'] = "Thêm người dùng thành công";
            }
        } catch (PDOException $e) {
            $_SESSION['message'] = "Lỗi: " . $e->getMessage();
        }
    } else {
        $_SESSION['message'] = "Dữ liệu không hợp lệ. Vui lòng kiểm tra lại.";
    }
}

?>



<!DOCTYPE html>
<html lang="vi">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="resources/images/logo/attnlg.png" rel="icon">

    <title>Bảng điều khiển</title>
    <link rel="stylesheet" href="resources/assets/css/admin_styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.2.0/remixicon.css" rel="stylesheet">

</head>

<body>
    <?php include "includes/topbar.php"; ?>

    <section class=main>

        <?php include "includes/sidebar.php"; ?>

        <div class="main--content">
            <div id="overlay"></div>
            <?php showMessage() ?>
            <div class="table-container">
                <div class="title" id="showButton">
                    <h2 class="section--title">Người dùng</h2>
                    <button class="add"><i class="ri-add-line"></i>Thêm người dùng</button>
                </div>
                <div class="table">
                    <table>
                        <thead>
                            <tr>
                                <th>Họ tên</th>
                                <th>Email</th>
                                <th>Số điện thoại</th>
                                <th>Khoa</th>
                                <th>Ngày tạo</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <?php
                                $sql = "SELECT * FROM tbllecture";
                                $result = fetch($sql);
                                if ($result) {
                                    foreach ($result as $row) {
                                        echo "<tr id='rowlecture{$row["Id"]}'>";
                                        echo "<td>" . $row["firstName"] . "</td>";
                                        echo "<td>" . $row["emailAddress"] . "</td>";
                                        echo "<td>" . $row["phoneNo"] . "</td>";
                                        echo "<td>" . $row["facultyCode"] . "</td>";
                                        echo "<td>" . $row["dateCreated"] . "</td>";
                                        echo "<td><span><i class='ri-delete-bin-line delete' data-id='{$row["Id"]}' data-name='lecture'></i></span></td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='6'>Không có dữ liệu</td></tr>";
                                }

                                ?>

                        </tbody>
                    </table>
                </div>
            </div>
            <div class="formDiv--" id="form" style="display:none; ">
                <form method="POST" action="" name="addLecture" enctype="multipart/form-data">
                    <div style="display:flex; justify-content:space-around;">
                        <div class="form-title">
                            <p>Thêm người dùng</p>
                        </div>
                        <div>
                            <span class="close">&times;</span>
                        </div>
                    </div>
                    <input type="text" name="firstName" placeholder="Họ tên" required>
                    <input type="text" name="lastName" placeholder="Tên" required>
                    <input type="email" name="email" placeholder="Email" required>
                    <input type="text" name="phoneNumber" placeholder="Số điện thoại" required>
                    <input type="password" name="password" placeholder="Mật khẩu" required>

                    <select required name="faculty">
                        <option value="" selected>Chọn khoa</option>
                        <?php
                        $facultyNames = getFacultyNames();
                        foreach ($facultyNames as $faculty) {
                            echo '<option value="' . $faculty["facultyCode"] . '">' . $faculty["facultyName"] . '</option>';
                        }
                        ?>
                    </select>
                    <input type="submit" class="submit" value="Lưu người dùng" name="addLecture">
                </form>
            </div>



    </section>

    <?php js_asset(["admin_functions", "active_link", "delete_request", "script"]) ?>


</body>

</html>

<?php

$courseCode = isset($_GET['course']) ? trim($_GET['course']) : '';
$unitCode = isset($_GET['unit']) ? trim($_GET['unit']) : '';

$codePattern = '/^[A-Za-z0-9_-]+$/';
if ($courseCode !== '' && !preg_match($codePattern, $courseCode)) {
    $courseCode = '';
}
if ($unitCode !== '' && !preg_match($codePattern, $unitCode)) {
    $unitCode = '';
}

$studentRows = fetchStudentRecordsFromDatabase($courseCode, $unitCode);

$coursename = "";
if (!empty($courseCode)) {
    $result = fetch("SELECT name FROM tblcourse WHERE courseCode = :courseCode", [':courseCode' => $courseCode]);
    foreach ($result as $row) {

        $coursename = $row['name'];
    }
}
$unitname = "";
if (!empty($unitCode)) {
    $result = fetch("SELECT name FROM tblunit WHERE unitCode = :unitCode", [':unitCode' => $unitCode]);
    foreach ($result as $row) {

        $unitname = $row['name'];
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
            <form class="lecture-options" id="selectForm">
                <select required name="course" id="courseSelect" onChange="updateTable()">
                    <option value="" selected>Select Course</option>
                    <?php
                    $courseNames = getCourseNames();
                    foreach ($courseNames as $course) {
                        echo '<option value="' . $course["courseCode"] . '">' . $course["name"] . '</option>';
                    }
                    ?>
                </select>

                <select required name="unit" id="unitSelect" onChange="updateTable()">
                    <option value="" selected>Select Unit</option>
                    <?php
                    $unitNames = getUnitNames();
                    foreach ($unitNames as $unit) {
                        echo '<option value="' . $unit["unitCode"] . '">' . $unit["name"] . '</option>';
                    }
                    ?>
                </select>
            </form>


            <div class="table-container">
                <div class="title">
                    <h2 class="section--title">Students List</h2>
                </div>
                <div class="table attendance-table" id="attendaceTable">
                    <table>
                        <thead>
                            <tr>
                                <th>Registration No</th>
                                <th>First Name</th>
                                <th>Last Name</th>
                                <th>Email</th>

                            </tr>
                        </thead>
                        <tbody>
                            <?php $query = "SELECT * FROM tblstudents WHERE courseCode = :courseCode";

                            $result = fetch($query, [':courseCode' => $courseCode]);
                            if ($result) {
                                foreach ($result as $row) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row['registrationNumber'], ENT_QUOTES, 'UTF-8') . "</td>";
                                    echo "<td>" . htmlspecialchars($row['firstName'], ENT_QUOTES, 'UTF-8') . "</td>";
                                    echo "<td>" . htmlspecialchars($row['lastName'], ENT_QUOTES, 'UTF-8') . "</td>";
                                    echo "<td>" . htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8') . "</td>";

                                    echo "</tr>";
                                }

                                echo "</table>";
                            } else {
                            }
                            ?>

                        </tbody>
                    </table>

                </div>
            </div>

        </div>
        </div>
    </section>
    <div>
        <?php js_asset(["active_link", "min/js/filesaver", "min/js/xlsx"]) ?>
</body>


<script>
    function updateTable() {
        console.log("update noted");
        var courseSelect = document.getElementById("courseSelect");
        var unitSelect = document.getElementById("unitSelect");

        var selectedCourse = courseSelect.value;
        var selectedUnit = unitSelect.value;

        var url = "view-students";
        if (selectedCourse && selectedUnit) {
            url += "?course=" + encodeURIComponent(selectedCourse) + "&unit=" + encodeURIComponent(selectedUnit);
            window.location.href = url;
            console.log(url)
        }
    }
</script>

</html>

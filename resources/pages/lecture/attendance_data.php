<?php
require_once "../../lib/php_functions.php";
require_once "../../../database/database_connection.php";

header('Content-Type: application/json');

$courseCode = isset($_GET['course']) ? trim($_GET['course']) : '';
$unitCode = isset($_GET['unit']) ? trim($_GET['unit']) : '';

$codePattern = '/^[A-Za-z0-9_-]+$/';
if ($courseCode !== '' && !preg_match($codePattern, $courseCode)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid course code']);
    exit;
}
if ($unitCode !== '' && !preg_match($codePattern, $unitCode)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid unit code']);
    exit;
}

ob_start();
?>
<tbody>
<?php
// Fetch distinct dates for the selected course and unit
$distinctDatesQuery = "SELECT DISTINCT dateMarked FROM tblattendance WHERE course = :courseCode AND unit = :unitCode ORDER BY dateMarked";
$stmtDates = $pdo->prepare($distinctDatesQuery);
$stmtDates->execute([
    ':courseCode' => $courseCode,
    ':unitCode' => $unitCode,
]);
$distinctDatesResult = $stmtDates->fetchAll(PDO::FETCH_ASSOC);

// Table header row will be on client; we only output tbody rows
$studentsQuery = "SELECT DISTINCT studentRegistrationNumber FROM tblattendance WHERE course = :courseCode AND unit = :unitCode ORDER BY studentRegistrationNumber";
$stmtStudents = $pdo->prepare($studentsQuery);
$stmtStudents->execute([
    ':courseCode' => $courseCode,
    ':unitCode' => $unitCode,
]);
$studentRows = $stmtStudents->fetchAll(PDO::FETCH_ASSOC);

if ($studentRows) {
    foreach ($studentRows as $row) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['studentRegistrationNumber'], ENT_QUOTES, 'UTF-8') . "</td>";
        foreach ($distinctDatesResult as $dateRow) {
            $date = $dateRow['dateMarked'];
            $attendanceQuery = "SELECT attendanceStatus FROM tblattendance 
                                WHERE studentRegistrationNumber = :studentRegistrationNumber 
                                AND dateMarked = :date 
                                AND course = :courseCode 
                                AND unit = :unitCode";
            $stmtAttendance = $pdo->prepare($attendanceQuery);
            $stmtAttendance->execute([
                ':studentRegistrationNumber' => $row['studentRegistrationNumber'],
                ':date' => $date,
                ':courseCode' => $courseCode,
                ':unitCode' => $unitCode,
            ]);
            $attendanceResult = $stmtAttendance->fetch(PDO::FETCH_ASSOC);
            if ($attendanceResult) {
                echo "<td>" . htmlspecialchars($attendanceResult['attendanceStatus'], ENT_QUOTES, 'UTF-8') . "</td>";
            } else {
                echo "<td>Absent</td>";
            }
        }
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='7'>No records found</td></tr>";
}
?>
</tbody>
<?php
$html = ob_get_clean();
echo json_encode(['status' => 'success', 'html' => $html]);
?>

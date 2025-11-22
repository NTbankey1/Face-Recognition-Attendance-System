<?php
require_once "../../lib/php_functions.php";
require_once "../../../database/database_connection.php";

$response = [
    'status' => 'error',
    'message' => 'Invalid or missing parameters',
    'data' => [],
    'html' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $courseID = isset($_POST['courseID']) ? trim($_POST['courseID']) : '';
    $unitID = isset($_POST['unitID']) ? trim($_POST['unitID']) : '';
    $venueID = isset($_POST['venueID']) ? trim($_POST['venueID']) : '';

    if ($courseID !== '' && $unitID !== '' && $venueID !== '') {
        try {
            $stmt = $pdo->prepare(
                "SELECT registrationNumber, firstName, lastName FROM tblstudents WHERE courseCode = :courseCode"
            );
            $stmt->execute([':courseCode' => $courseID]);
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $selectedStudents = $students;
            $selectedCourseID = $courseID;
            $selectedUnitID = $unitID;
            $selectedVenue = $venueID;

            ob_start();
            include './studentTable.php';
            $response['html'] = ob_get_clean();

            $response['status'] = 'success';
            $response['message'] = $students ? null : 'No records found';
            $response['data'] = $students ? array_column($students, 'registrationNumber') : [];
        } catch (PDOException $exception) {
            error_log('manageFolder.php: ' . $exception->getMessage());
            $response['message'] = 'An error occurred while fetching student data.';
        }
    }
}

header('Content-Type: application/json');
echo json_encode($response);

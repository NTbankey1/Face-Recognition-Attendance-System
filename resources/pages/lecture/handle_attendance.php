<?php

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$attendanceData = json_decode(file_get_contents('php://input'), true);

if (!is_array($attendanceData)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid attendance payload.']);
    exit;
}

if (!$attendanceData) {
    echo json_encode(['status' => 'error', 'message' => 'No attendance data received.']);
    exit;
}

try {
    $sql = "INSERT INTO tblattendance (studentRegistrationNumber, course, unit, attendanceStatus, dateMarked)  
                VALUES (:studentID, :course, :unit, :attendanceStatus, :date)";

    $stmt = $pdo->prepare($sql);
    $validStatuses = ['present', 'absent'];

    $logged = [];

    foreach ($attendanceData as $data) {
        $studentID = isset($data['studentID']) ? trim($data['studentID']) : null;
        $attendanceStatus = isset($data['attendanceStatus']) ? strtolower(trim($data['attendanceStatus'])) : null;
        $course = isset($data['course']) ? trim($data['course']) : null;
        $unit = isset($data['unit']) ? trim($data['unit']) : null;

        if (!$studentID || !$course || !$unit) {
            continue;
        }

        if (!in_array($attendanceStatus, $validStatuses, true)) {
            $attendanceStatus = 'absent';
        }

        $stmt->execute([
            ':studentID' => $studentID,
            ':course' => $course,
            ':unit' => $unit,
            ':attendanceStatus' => $attendanceStatus,
            ':date' => date('Y-m-d')
        ]);

        $logged[] = [
            'studentID' => $studentID,
            'course' => $course,
            'unit' => $unit,
            'attendanceStatus' => $attendanceStatus,
            'date' => date('Y-m-d')
        ];
    }

    // log event per submission batch
    if (!empty($logged)) {
        $key = '';
        $first = $logged[0];
        if (isset($first['course'], $first['unit'])) {
            $key = $first['course'] . '_' . $first['unit'];
        }
        log_event('lecturer', 'submit_attendance', $logged, $key);
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Attendance recorded successfully for all entries.'
    ]);
} catch (PDOException $e) {
    error_log('Attendance insert error: ' . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Error inserting attendance data.'
    ]);
}

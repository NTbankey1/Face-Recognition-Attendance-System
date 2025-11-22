<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        echo json_encode(['success' => false, 'error' => 'Invalid JSON payload.']);
        exit;
    }

    $allowedTables = [
        'students' => 'tblstudents',
        'lecture' => 'tbllecture',
        'course' => 'tblcourse',
        'unit' => 'tblunit',
        'faculty' => 'tblfaculty',
        'venue' => 'tblvenue'
    ];

    $rawId = $input['id'] ?? null;
    $nameKey = $input['name'] ?? null;

    $id = filter_var($rawId, FILTER_VALIDATE_INT);

    if ($id === false || !isset($allowedTables[$nameKey])) {
        echo json_encode(['success' => false, 'error' => 'Invalid deletion request.']);
        exit;
    }

    $tableName = $allowedTables[$nameKey];

    try {
        $stmt = $pdo->prepare("DELETE FROM {$tableName} WHERE Id = :id");
        $stmt->execute([':id' => $id]);

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        error_log('Deletion error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Failed to delete record.']);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
}

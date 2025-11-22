<?php

$host = getenv('DB_HOST');
if ($host === false || $host === '') {
$host = "localhost";
}

// your database name
$database = getenv('DB_NAME');
if ($database === false || $database === '') {
$database = "attendance_db"; // default recommended name
}

// database user
$user = getenv('DB_USER');
if ($user === false || $user === '') {
$user = "root";
}

// database password
$password = getenv('DB_PASS');
if ($password === false) {
$password = ""; // default empty for local root
}
try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $user, $password);
    // Set PDO error mode to exception for better error handling
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

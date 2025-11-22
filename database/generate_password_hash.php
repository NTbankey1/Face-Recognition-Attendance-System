#!/usr/bin/env php
<?php
/**
 * Script tạo password hash cho Admin và Lecture
 * Sử dụng: php database/generate_password_hash.php
 * Hoặc: php database/generate_password_hash.php "your_password"
 */

if (isset($argv[1])) {
    $password = $argv[1];
} else {
    echo "Nhập password cần hash: ";
    $password = trim(fgets(STDIN));
}

if (empty($password)) {
    echo "❌ Password không được để trống!\n";
    exit(1);
}

$hash = password_hash($password, PASSWORD_BCRYPT);

echo "\n";
echo "========================================\n";
echo "PASSWORD HASH GENERATOR\n";
echo "========================================\n";
echo "Password gốc: {$password}\n";
echo "Hash (BCRYPT): {$hash}\n";
echo "\n";
echo "SQL INSERT mẫu:\n";
echo "----------------------------------------\n";
echo "-- Cho Admin:\n";
echo "INSERT INTO tbladmin (firstName, lastName, emailAddress, password) \n";
echo "VALUES ('Admin', 'User', 'admin@example.com', '{$hash}');\n";
echo "\n";
echo "-- Cho Lecture:\n";
echo "INSERT INTO tbllecture (firstName, lastName, emailAddress, password, phoneNo, facultyCode, dateCreated) \n";
echo "VALUES ('John', 'Doe', 'lecture@example.com', '{$hash}', '0123456789', 'CIT', CURDATE());\n";
echo "========================================\n";



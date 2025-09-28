<?php
// src/db.php
// DB 連線 - 使用 PDO


$DB_HOST = '127.0.0.1';
$DB_NAME = 'auth_system';
$DB_USER = 'root';
$DB_PASS = 'gut2011cct';
$DSN = "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4";


try {
$pdo = new PDO("mysql:host=localhost;dbname=auth_project", "auth_user", "yourpassword", [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

} catch (PDOException $e) {
die('Database connection failed: ' . $e->getMessage());
}

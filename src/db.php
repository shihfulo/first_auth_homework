<?php
// src/db.php
// 資料庫連線設定與初始化

$DB_HOST = '127.0.0.1';
$DB_NAME = 'auth_project';
$DB_USER = 'auth_user';
$DB_PASS = 'yourpassword';
$DSN = "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4";

try {
    // 建立連線
    $pdo = new PDO($DSN, $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // 確認 users 資料表存在，若不存在則建立
    $createTableSQL = "
    CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        is_admin TINYINT(1) NOT NULL DEFAULT 0,
        is_approved TINYINT(1) NOT NULL DEFAULT 0,
        failed_attempts INT NOT NULL DEFAULT 0,
        lock_until DATETIME NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $pdo->exec($createTableSQL);

    // ======== 預設系統管理員帳號 ========
    $defaultUsername = 'admin';
    $defaultPassword = 'admin123';
    $hashedPassword = password_hash($defaultPassword, PASSWORD_DEFAULT);

    // 檢查是否已有管理員帳號
    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $checkStmt->execute([$defaultUsername]);
    $count = $checkStmt->fetchColumn();

    // 若不存在則建立
    if ($count == 0) {
        $insertStmt = $pdo->prepare("
            INSERT INTO users (username, password_hash, is_admin, is_approved)
            VALUES (?, ?, 1, 1)
        ");
        $insertStmt->execute([$defaultUsername, $hashedPassword]);
        error_log("✅ Default admin created: {$defaultUsername} / {$defaultPassword}");
    }

} catch (PDOException $e) {
    die('❌ Database connection failed: ' . $e->getMessage());
}
?>

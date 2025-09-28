<?php
// src/auth.php
require_once __DIR__ . '/db.php';
session_start();


function user_count() {
global $pdo;
$stmt = $pdo->query('SELECT COUNT(*) FROM users');
return (int)$stmt->fetchColumn();
}

function require_login() {
    if (empty($_SESSION['user_id'])) {
        header('Location: index.php');
        exit;
    }
}

function require_admin() {
    if (empty($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
        header('HTTP/1.1 403 Forbidden');
        echo "你沒有管理員權限";
        exit;
    }
}


function find_user_by_username($username) {
global $pdo;
$stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
$stmt->execute([$username]);
return $stmt->fetch();
}


function create_user($username, $password, $make_admin = false) {
global $pdo;
$hash = password_hash($password, PASSWORD_DEFAULT);
$is_admin = $make_admin ? 1 : 0;
// 新註冊預設 is_approved = 1 如果是第一個帳號 (由呼叫端判斷) 否則 0
$is_approved = $make_admin ? 1 : 0;


$stmt = $pdo->prepare('INSERT INTO users (username, password_hash, is_admin, is_approved) VALUES (?, ?, ?, ?)');
$stmt->execute([$username, $hash, $is_admin, $is_approved]);
return $pdo->lastInsertId();
}


function verify_login($username, $password) {
global $pdo;
$user = find_user_by_username($username);
if (!$user) return ['ok'=>false, 'reason'=>'no_user'];


// 鎖定檢查
if (!empty($user['lock_until'])) {
$now = new DateTime();
$lock_until = new DateTime($user['lock_until']);
if ($now < $lock_until) {
$remaining = $lock_until->getTimestamp() - $now->getTimestamp();
return ['ok'=>false, 'reason'=>'locked', 'remaining'=>$remaining];
}
}


if (!password_verify($password, $user['password_hash'])) {
// 增加 failed_attempts
$failed = $user['failed_attempts'] + 1;
$lock_until = null;
if ($failed >= 3) {
// 鎖定 30 秒
$dt = new DateTime();
$dt->modify('+30 seconds');
$lock_until = $dt->format('Y-m-d H:i:s');
$failed = 0; // 重設失敗次數（也可以選擇保留，這裡重設）
}
$stmt = $pdo->prepare('UPDATE users SET failed_attempts = ?, lock_until = ? WHERE id = ?');
$stmt->execute([$failed, $lock_until, $user['id']]);
return ['ok'=>false, 'reason'=>'wrong_pass', 'remaining_attempts' => 3 - ($failed)];
}


// 密碼正確但尚未批准
if (!$user['is_approved']) return ['ok'=>false, 'reason'=>'not_approved'];


// 登入成功：重設 failed_attempts, lock_until
$stmt = $pdo->prepare('UPDATE users SET failed_attempts = 0, lock_until = NULL WHERE id = ?');
$stmt->execute([$user['id']]);


// 建立 session
$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['is_admin'] = (bool)$user['is_admin'];


return ['ok'=>true, 'user'=>$user];
}


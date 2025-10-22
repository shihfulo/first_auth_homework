<?php
// src/auth.php
require_once __DIR__ . '/db.php';
session_start();

/* ---------- 基本查詢 ---------- */
function user_count() {
    global $pdo;
    $stmt = $pdo->query('SELECT COUNT(*) FROM users');
    return (int)$stmt->fetchColumn();
}

function find_user_by_username($username) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function get_user_by_id($id) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/* ---------- 建立新使用者 ---------- */
// $role 預設 'member'，$status 預設 'pending'（第一個帳號會由呼叫端指定 system_admin 並直接 approved）
function create_user($username, $password, $role = 'member', $group_id = null, $status = 'pending') {
    global $pdo;
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO users (username, password_hash, group_id, role, status) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$username, $hash, $group_id, $role, $status]);
    return $pdo->lastInsertId();
}

/* ---------- 登入驗證（含鎖定、審核檢查） ---------- */
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

    // 密碼錯誤處理
    if (!password_verify($password, $user['password_hash'])) {
        $failed = (int)$user['failed_attempts'] + 1;
        $lock_until = null;
        if ($failed >= 3) {
            $dt = new DateTime();
            $dt->modify('+30 seconds');
            $lock_until = $dt->format('Y-m-d H:i:s');
            $failed = 0; // 重新計算或歸零
        }
        $stmt = $pdo->prepare('UPDATE users SET failed_attempts = ?, lock_until = ? WHERE id = ?');
        $stmt->execute([$failed, $lock_until, $user['id']]);
        return ['ok'=>false, 'reason'=>'wrong_pass', 'remaining_attempts' => 3 - $failed];
    }

    // 密碼正確後檢查審核狀態
    if ($user['status'] !== 'approved') {
        return ['ok'=>false, 'reason'=>'not_approved', 'status'=>$user['status']];
    }

    // 登入成功：重設 failed_attempts 與 lock_until，並寫入 session
    $stmt = $pdo->prepare('UPDATE users SET failed_attempts = 0, lock_until = NULL WHERE id = ?');
    $stmt->execute([$user['id']]);

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['group_id'] = $user['group_id'];

    return ['ok'=>true, 'user'=>$user];
}

/* ---------- 權限相關輔助 ---------- */
function require_login() {
    if (empty($_SESSION['user_id'])) {
        header('Location: index.php');
        exit;
    }
}

function require_system_admin() {
    if (empty($_SESSION['role']) || $_SESSION['role'] !== 'system_admin') {
        header('HTTP/1.1 403 Forbidden');
        echo "403 Forbidden - 需要系統管理員權限";
        exit;
    }
}

function require_group_admin_or_system() {
    if (empty($_SESSION['role'])) {
        header('HTTP/1.1 403 Forbidden'); exit;
    }
    if ($_SESSION['role'] === 'system_admin') return;
    if ($_SESSION['role'] === 'group_admin') return;
    header('HTTP/1.1 403 Forbidden');
    echo "403 Forbidden - 需要群組管理員或系統管理員權限";
    exit;
}

function is_system_admin() {
    return (!empty($_SESSION['role']) && $_SESSION['role'] === 'system_admin');
}

function is_group_admin() {
    return (!empty($_SESSION['role']) && $_SESSION['role'] === 'group_admin');
}

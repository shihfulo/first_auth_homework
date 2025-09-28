<?php
require_once __DIR__ . '/../src/auth.php';

// 若資料庫中尚無任何帳號，導向 register.php 創建第一個管理員
if (user_count() == 0) {
    header('Location: register.php?first=1');
    exit;
}

$err = null;
$msg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $res = verify_login($username, $password);
    if ($res['ok']) {
        $msg = "登入成功！歡迎，" . htmlspecialchars($res['user']['username']);
    } else {
        $err = $res;
    }
}
?>

<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>登入</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="card">
<h1>登入</h1>

<?php if ($msg): ?>
    <div class="success"><?php echo $msg; ?></div>
<?php else: ?>
    <?php if ($err): ?>
        <div class="error">
        <?php
        if ($err['reason'] === 'no_user') echo '使用者不存在';
        elseif ($err['reason'] === 'locked') echo '帳號被鎖定，請等待 ' . ($err['remaining']) . ' 秒';
        elseif ($err['reason'] === 'wrong_pass') echo '密碼錯誤；剩餘嘗試次數: ' . max(0, $err['remaining_attempts']);
        elseif ($err['reason'] === 'not_approved') echo '帳號尚未通過管理員審核，無法登入';
        else echo '登入失敗';
        ?>
        </div>
    <?php endif; ?>

    <form method="post">
        <label>帳號: <input type="text" name="username" required></label><br><br>
        <label>密碼: <input type="password" name="password" required></label><br><br>
        <button type="submit">登入</button>
    </form>
<?php endif; ?>

</div>
</body>
</html>

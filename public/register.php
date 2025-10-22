<?php
require_once __DIR__ . '/../src/auth.php';


$first = (user_count() == 0) || isset($_GET['first']);
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';
    if ($password !== $password2) {
        $message = '兩次密碼不一致';
    } elseif (find_user_by_username($username)) {
        $message = '使用者名稱已存在';
    } else {
        if ($first) {
            // 第一個帳號：系統管理員，直接 approved
            create_user($username, $password, 'system_admin', null, 'approved');
            $message = '已建立系統管理員，請用此帳號登入';
            header('Location: index.php');
            exit;
        } else {
            // 預設為 member，狀態 pending（需 admin 批准）
            create_user($username, $password, 'member', null, 'pending');
            $message = '註冊成功！請等待系統管理員審核後才能登入';
        }
    }
}
?>


<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>註冊</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="card">
<h1>註冊</h1>
<?php if ($message): ?><div class="message"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
<form method="post">
<label>使用者名稱<br><input name="username" required></label><br>
<label>密碼<br><input name="password" type="password" required></label><br>
<label>再次輸入密碼<br><input name="password2" type="password" required></label><br>
<button type="submit">註冊</button>
</form>
<p>已經有帳號？<a href="index.php">登入</a></p>
</div>
</body>
</html>

<?php
require_once __DIR__ . '/../src/auth.php';


$first = isset($_GET['first']) && $_GET['first'] == '1';
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$password2 = $_POST['password2'] ?? '';
if ($password !== $password2) {
$message = '兩次密碼不一致';
} else if (find_user_by_username($username)) {
$message = '使用者名稱已存在';
} else {
// 若是第一個帳號 or first=1 -> 直接成為 admin 並自動批准
$make_admin = false;
if (user_count() == 0 || $first) {
$make_admin = true;
}
create_user($username, $password, $make_admin);
if ($make_admin) {
$message = '已建立管理員帳號，請用此帳號登入';
} else {
$message = '註冊成功！請等待管理員審核後才能登入';
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

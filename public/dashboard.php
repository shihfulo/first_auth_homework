<?php
require_once __DIR__ . '/../src/auth.php';
require_login();
?>
<?php if (is_system_admin()): ?>
  <p><a href="admin/users.php">後台：使用者管理（系統管理）</a></p>
<?php endif; ?>

<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>會員首頁</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="card">
<h1>歡迎，<?php echo htmlspecialchars($_SESSION['username']); ?></h1>
<?php if ($_SESSION['is_admin']): ?>
<p><a href="admin_review.php">前往管理員審核頁面</a></p>
<?php endif; ?>
<p><a href="logout.php">登出</a></p>
</div>
</body>
</html>

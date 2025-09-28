<?php
require_once __DIR__ . '/../src/auth.php';
require_login();
require_admin();

// 處理批准/拒絕
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $user_id = intval($_POST['user_id'] ?? 0);

    if ($action === 'approve') {
        $stmt = $pdo->prepare('UPDATE users SET is_approved = 1 WHERE id = ?');
        $stmt->execute([$user_id]);
    } elseif ($action === 'reject') {
        // 若拒絕，直接刪除
        $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$user_id]);
    }
}

// 取得待審核清單
$stmt = $pdo->query('SELECT id, username, created_at FROM users WHERE is_approved = 0');
$pending = $stmt->fetchAll();
?>

<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>管理員 - 帳號審核</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="card">
<h1>帳號審核</h1>

<?php if (empty($pending)): ?>
    <p>目前沒有待審核的新帳號。</p>
<?php else: ?>
    <table>
        <thead>
            <tr><th>帳號</th><th>註冊時間</th><th>操作</th></tr>
        </thead>
        <tbody>
        <?php foreach ($pending as $u): ?>
            <tr>
                <td><?php echo htmlspecialchars($u['username']); ?></td>
                <td><?php echo htmlspecialchars($u['created_at']); ?></td>
                <td>
                    <form method="post" style="display:inline">
                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                        <button name="action" value="approve">批准</button>
                    </form>
                    <form method="post" style="display:inline">
                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                        <button name="action" value="reject" onclick="return confirm('確定拒絕並刪除此帳號？')">拒絕</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

</div>
</body>
</html>

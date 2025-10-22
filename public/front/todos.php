<?php
require_once __DIR__ . '/../../src/auth.php';
require_login();

$me = get_user_by_id($_SESSION['user_id']);
$role = $_SESSION['role'];
$group_id = $_SESSION['group_id'];
$is_admin = $me['is_admin'] ?? 0;  // 從資料庫取得 is_admin 欄位

$msg = '';

global $pdo;

// === 處理新增待辦事項 ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content'])) {
    $content = trim($_POST['content']);
    if ($content !== '') {
        $stmt = $pdo->prepare('INSERT INTO todos (user_id, group_id, content) VALUES (?, ?, ?)');
        $stmt->execute([$me['id'], $group_id, $content]);
        $msg = '✅ 新增完成！';
    } else {
        $msg = '❌ 內容不可為空。';
    }
}

// === 讀取待辦事項 ===
if ($role === 'admin') {
    // 管理員可看全部
    $stmt = $pdo->query('SELECT t.*, u.username, g.name AS group_name 
                         FROM todos t 
                         JOIN users u ON t.user_id = u.id 
                         LEFT JOIN groups g ON t.group_id = g.id 
                         ORDER BY t.created_at DESC');
} else {
    // 一般組員只能看自己組別
    $stmt = $pdo->prepare('SELECT t.*, u.username, g.name AS group_name 
                           FROM todos t 
                           JOIN users u ON t.user_id = u.id 
                           LEFT JOIN groups g ON t.group_id = g.id 
                           WHERE t.group_id = ? 
                           ORDER BY t.created_at DESC');
    $stmt->execute([$group_id]);
}
$todos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// === 讀取同組成員 ===
$stmt = $pdo->prepare('SELECT username, role FROM users WHERE group_id = ? AND status = "active" ORDER BY username ASC');
$stmt->execute([$group_id]);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="zh-Hant">
<head>
<meta charset="utf-8">
<title>代辦事項</title>
<style>
    body { font-family: "Microsoft JhengHei", Arial, sans-serif; background: #f8f9fa; margin: 40px; }
    h1 { color: #333; }
    .header-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .admin-btn { 
        background: #FF9800; 
        color: white; 
        padding: 10px 16px; 
        border-radius: 6px; 
        text-decoration: none; 
        transition: background 0.3s;
        display: inline-block;
    }
    .admin-btn:hover { background: #E68900; }
    .admin-btn:disabled,
    .admin-btn.disabled { 
        background: #CCCCCC; 
        cursor: not-allowed; 
        pointer-events: none;
    }
    form textarea { width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc; resize: none; }
    form button { margin-top: 10px; background: #4CAF50; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; }
    form button:hover { background: #45a049; }
    table { border-collapse: collapse; width: 100%; background: #fff; box-shadow: 0 2px 6px rgba(0,0,0,0.1); margin-top: 20px; }
    th, td { padding: 10px; border: 1px solid #ddd; text-align: center; }
    th { background: #4CAF50; color: white; }
    tr:nth-child(even) { background: #f2f2f2; }
    .msg { margin: 15px 0; padding: 10px; border-radius: 6px; background: #e8f5e9; color: #2e7d32; }
    .members { margin-top: 30px; }
    .back { display: inline-block; margin-top: 20px; text-decoration: none; background: #2196F3; color: white; padding: 8px 14px; border-radius: 6px; }
    .back:hover { background: #1976D2; }
</style>
</head>
<body>
    <!-- 頁首：標題 + 後台管理按鈕 -->
    <div class="header-top">
        <h1>📋 代辦事項系統</h1>
        <?php if ($is_admin == 1): ?>
            <a href="/auth_project/public/admin_review.php" class="admin-btn">⚙️ 後台管理</a>
        <?php else: ?>
            <button class="admin-btn disabled" disabled title="僅管理員可訪問">⚙️ 後台管理</button>
        <?php endif; ?>
    </div>

    <p>👤 使用者：<?php echo htmlspecialchars($me['username']); ?>（<?php echo htmlspecialchars($role); ?>）</p>

    <?php if ($msg): ?>
        <div class="msg"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <!-- 新增待辦事項 -->
    <form method="post">
        <label>輸入代辦事項：</label><br>
        <textarea name="content" rows="3" required></textarea><br>
        <button type="submit">新增</button>
    </form>

    <!-- 代辦列表 -->
    <h2>📄 組別代辦列表</h2>
    <table>
        <thead>
            <tr>
                <th>內容</th>
                <th>建立者</th>
                <th>組別</th>
                <th>時間</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($todos) > 0): ?>
                <?php foreach ($todos as $t): ?>
                    <tr>
                        <td><?= htmlspecialchars($t['content']) ?></td>
                        <td><?= htmlspecialchars($t['username']) ?></td>
                        <td><?= htmlspecialchars($t['group_name'] ?? '未分組') ?></td>
                        <td><?= htmlspecialchars($t['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="4">目前沒有代辦事項。</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- 組員名單 -->
    <div class="members">
        <h2>👥 組員名單（<?php echo htmlspecialchars($me['group_id'] ?: '未分組'); ?>）</h2>
        <?php if (count($members) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>名稱</th>
                        <th>角色</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($members as $m): ?>
                        <tr>
                            <td><?= htmlspecialchars($m['username']) ?></td>
                            <td><?= htmlspecialchars($m['role']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>尚無同組成員。</p>
        <?php endif; ?>
    </div>

    <a class="back" href="/auth_project/public/dashboard.php">⬅ 回首頁</a>
</body>
</html>
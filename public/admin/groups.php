<?php
require_once __DIR__ . '/../../src/auth.php';
require_login();
require_system_admin();

global $pdo;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create'])) {
        $name = trim($_POST['name']);
        if ($name !== '') {
            $stmt = $pdo->prepare('INSERT INTO groups (name) VALUES (?)');
            $stmt->execute([$name]);
        }
    } elseif (isset($_POST['delete'])) {
        $id = intval($_POST['group_id']);
        $stmt = $pdo->prepare('DELETE FROM groups WHERE id = ?');
        $stmt->execute([$id]);
    }
}

$groups = $pdo->query('SELECT * FROM groups ORDER BY id')->fetchAll();
?>
<!doctype html><html><head><meta charset="utf-8"><title>群組管理</title></head><body>
<h1>群組管理</h1>
<form method="post">
    <input type="text" name="name" required placeholder="組別名稱">
    <button name="create" type="submit">新增</button>
</form>

<h2>現有群組</h2>
<ul>
<?php foreach ($groups as $g): ?>
  <li><?php echo htmlspecialchars($g['name']); ?>
    <form method="post" style="display:inline">
      <input type="hidden" name="group_id" value="<?php echo $g['id']; ?>">
      <button name="delete" type="submit" onclick="return confirm('刪除?')">刪除</button>
    </form>
  </li>
<?php endforeach; ?>
</ul>
<p><a href="/auth_project/public/dashboard.php">回首頁</a></p>
</body></html>

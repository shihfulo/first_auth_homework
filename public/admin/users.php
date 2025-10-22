<?php
require_once __DIR__ . '/../../src/auth.php';
require_login();
require_system_admin(); // 只有系統管理員能管理使用者與群組

// 處理修改
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    global $pdo;
    if (isset($_POST['action']) && $_POST['action'] === 'update') {
        $id = intval($_POST['user_id']);
        $role = $_POST['role'];
        $group_id = ($_POST['group_id'] === '') ? null : intval($_POST['group_id']);
        $status = $_POST['status'];
        $stmt = $pdo->prepare('UPDATE users SET role = ?, group_id = ?, status = ? WHERE id = ?');
        $stmt->execute([$role, $group_id, $status, $id]);
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = intval($_POST['user_id']);
        $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$id]);
    }
}

// 讀取 users / groups
global $pdo;
$users = $pdo->query('SELECT u.*, g.name AS group_name FROM users u LEFT JOIN groups g ON u.group_id = g.id ORDER BY u.id')->fetchAll();
$groups = $pdo->query('SELECT * FROM groups')->fetchAll();
?>
<!doctype html><html><head><meta charset="utf-8"><title>使用者管理</title></head><body>
<h1>使用者管理</h1>
<table border="1">
<tr><th>ID</th><th>帳號</th><th>群組</th><th>角色</th><th>狀態</th><th>操作</th></tr>
<?php foreach ($users as $u): ?>
<tr>
<td><?php echo $u['id']; ?></td>
<td><?php echo htmlspecialchars($u['username']); ?></td>
<td><?php echo htmlspecialchars($u['group_name']); ?></td>
<td><?php echo htmlspecialchars($u['role']); ?></td>
<td><?php echo htmlspecialchars($u['status']); ?></td>
<td>
  <form method="post" style="display:inline">
    <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
    <select name="role">
      <option value="member" <?php if ($u['role']=='member') echo 'selected'; ?>>member</option>
      <option value="group_admin" <?php if ($u['role']=='group_admin') echo 'selected'; ?>>group_admin</option>
      <option value="system_admin" <?php if ($u['role']=='system_admin') echo 'selected'; ?>>system_admin</option>
    </select>
    <select name="group_id">
      <option value="">--無群組--</option>
      <?php foreach ($groups as $g): ?>
        <option value="<?php echo $g['id'];?>" <?php if ($u['group_id']==$g['id']) echo 'selected';?>><?php echo htmlspecialchars($g['name']);?></option>
      <?php endforeach; ?>
    </select>
    <select name="status">
      <option value="pending" <?php if($u['status']=='pending') echo 'selected';?>>pending</option>
      <option value="approved" <?php if($u['status']=='approved') echo 'selected';?>>approved</option>
      <option value="rejected" <?php if($u['status']=='rejected') echo 'selected';?>>rejected</option>
    </select>
    <button name="action" value="update">更新</button>
  </form>
  <form method="post" style="display:inline" onsubmit="return confirm('確定刪除?')">
    <input type="hidden" name="user_id" value="<?php echo $u['id'];?>">
    <button name="action" value="delete">刪除</button>
  </form>
</td>
</tr>
<?php endforeach; ?>
</table>
<p><a href="/auth_project/public/dashboard.php">回首頁</a></p>
</body></html>

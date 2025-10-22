<?php
session_start();
require_once __DIR__ . '/../src/db.php';

// 驗證是否登入
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// 取得目前登入使用者
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$current_user = $stmt->fetch(PDO::FETCH_ASSOC);

// 檢查是否為管理員
if (!$current_user || !$current_user['is_admin']) {
    echo "🚫 您沒有權限進入此頁面。";
    exit();
}

// 取得現有組別
$stmt = $pdo->query("SELECT id, name FROM groups ORDER BY name ASC");
$groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 處理新增組別
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_group'])) {
    $group_name = trim($_POST['new_group_name']);
    if ($group_name !== '') {
        $stmt = $pdo->prepare("INSERT INTO groups (name) VALUES (?)");
        $stmt->execute([$group_name]);
        $message = "✅ 組別 {$group_name} 已新增。";
        $stmt = $pdo->query("SELECT id, name FROM groups ORDER BY name ASC");
        $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// 處理待審核帳號操作
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['action'])) {
    $target_user_id = intval($_POST['user_id']);
    $action = $_POST['action'];

    if ($action === 'approve') {
        // 統一使用 'approved' 作為已批准的狀態
        $update = $pdo->prepare("UPDATE users SET status='approved', is_approved=1 WHERE id=?");
        $update->execute([$target_user_id]);
        $message = "✅ 使用者 ID {$target_user_id} 已通過審核。";
    } elseif ($action === 'reject') {
        $update = $pdo->prepare("UPDATE users SET status='rejected', is_approved=0 WHERE id=?");
        $update->execute([$target_user_id]);
        $message = "❌ 使用者 ID {$target_user_id} 已被拒絕。";
    }
}

// 處理已批准使用者組別與角色更新
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['uid'], $_POST['update_group_role'])) {
    $uid = intval($_POST['uid']);
    $group_id = !empty($_POST['group_id']) ? intval($_POST['group_id']) : null;
    $role = $_POST['role'];

    $update = $pdo->prepare("UPDATE users SET group_id=?, role=? WHERE id=?");
    $update->execute([$group_id, $role, $uid]);
    $message = "✅ 使用者 ID {$uid} 的組別和角色已更新。";
}

// 取得待審核名單
$stmt = $pdo->query("SELECT id, username, created_at FROM users WHERE status='pending'");
$pending_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 取得已批准名單 - 修正此處
$stmt = $pdo->query("SELECT id, username, group_id, role, status FROM users WHERE status='approved'");
$approved_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title>管理員後台</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f9f9f9; }
        h1, h2 { color: #333; }
        table { border-collapse: collapse; width: 100%; background: #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 30px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: center; }
        th { background: #4CAF50; color: white; }
        form { display: inline; }
        button { padding: 6px 10px; border: none; cursor: pointer; border-radius: 4px; }
        .approve { background: #4CAF50; color: white; }
        .reject { background: #e74c3c; color: white; }
        .message { margin: 20px 0; padding: 10px; background: #f0f8ff; border: 1px solid #ccc; }
        .logout { float: right; margin-top: -50px; }
        select, input[type=text] { padding: 4px; }
    </style>
</head>
<body>
    <h1>👑 管理員後台</h1>
    <a href="logout.php" class="logout">登出</a>

    <?php if (!empty($message)): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <!-- 待審核帳號 -->
    <h2>待審核使用者</h2>
    <?php if (count($pending_users) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>帳號</th>
                    <th>註冊時間</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pending_users as $user): ?>
                    <tr>
                        <td><?= htmlspecialchars($user['id']) ?></td>
                        <td><?= htmlspecialchars($user['username']) ?></td>
                        <td><?= htmlspecialchars($user['created_at']) ?></td>
                        <td>
                            <form method="POST">
                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                <button type="submit" name="action" value="approve" class="approve">批准 ✅</button>
                                <button type="submit" name="action" value="reject" class="reject">拒絕 ❌</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>目前沒有待審核的使用者。</p>
    <?php endif; ?>

    <!-- 新增組別 -->
    <h2>新增組別</h2>
    <form method="POST">
        <input type="text" name="new_group_name" placeholder="輸入組別名稱" required>
        <button type="submit" name="add_group">新增組別</button>
    </form>

    <!-- 已批准使用者管理 -->
    <h2>已批准使用者</h2>
    <?php if (count($approved_users) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>帳號</th>
                    <th>組別</th>
                    <th>角色</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($approved_users as $user): ?>
                    <tr>
                        <td><?= htmlspecialchars($user['id']) ?></td>
                        <td><?= htmlspecialchars($user['username']) ?></td>
                        <td>
                            <form method="POST">
                                <input type="hidden" name="uid" value="<?= $user['id'] ?>">
                                <select name="group_id">
                                    <option value="">未分組</option>
                                    <?php foreach ($groups as $g): ?>
                                        <option value="<?= $g['id'] ?>" <?= $user['group_id']==$g['id']?'selected':'' ?>>
                                            <?= htmlspecialchars($g['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                        </td>
                        <td>
                                <select name="role">
                                    <option value="member" <?= $user['role']=='member'?'selected':'' ?>>一般組員</option>
                                    <option value="manager" <?= $user['role']=='manager'?'selected':'' ?>>組管理員</option>
                                    <option value="admin" <?= $user['role']=='admin'?'selected':'' ?>>系統管理員</option>
                                </select>
                        </td>
                        <td>
                                <button type="submit" name="update_group_role">更新 ✅</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>目前沒有已批准的使用者。</p>
    <?php endif; ?>
</body>
</html>
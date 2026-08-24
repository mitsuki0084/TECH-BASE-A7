<?php
/**
 * 担当: 開発メインB
 * 画面名: ユーザー管理（管理者用）
 *
 * 【⑦対応・新規作成】
 * 要件定義書 初期資料で挙げられていた「管理者機能：ユーザー管理」に対応する画面。
 *
 * 【実装仕様】
 * 1. 管理者権限確認（(int)$_SESSION['role'] === 1）。
 * 2. 全ユーザー一覧（ID・ユーザー名・メール・権限・登録日）を表示。
 * 3. 管理者⇔一般ユーザーの権限切り替え。
 * 4. 不適切なユーザーアカウントの削除（自分自身は削除不可）。
 *    削除すると ON DELETE CASCADE により当該ユーザーの投稿プランも連動削除される。
 */

require_once 'config/db.php';
require_once 'includes/header.php';

// 権限判定（③対応：(int) キャストしてから厳密比較する）
if (!isset($_SESSION['role']) || (int)$_SESSION['role'] !== 1) {
    echo '<p class="error">アクセス権限がありません。</p>';
    require_once 'includes/footer.php';
    exit;
}

$currentUserId = (int)$_SESSION['user_id'];
$infoMessage  = '';
$errorMessage = '';

// 権限切り替え処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_role') {
    $targetUserId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    $newRole      = filter_input(INPUT_POST, 'new_role', FILTER_VALIDATE_INT);

    if ($targetUserId === $currentUserId) {
        $errorMessage = '自分自身の権限は変更できません。';
    } elseif ($targetUserId && in_array($newRole, [0, 1], true)) {
        $updateStmt = $pdo->prepare('UPDATE users SET role = ? WHERE id = ?');
        $updateStmt->execute([$newRole, $targetUserId]);
        $infoMessage = '権限を更新しました。';
    }
}

// ユーザー削除処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_user') {
    $targetUserId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

    if ($targetUserId === $currentUserId) {
        // 自分自身は削除不可
        $errorMessage = '自分自身のアカウントは削除できません。';
    } elseif ($targetUserId) {
        // ON DELETE CASCADE により、当該ユーザーの plans / schedules も連動削除される
        $deleteStmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
        $deleteStmt->execute([$targetUserId]);
        $infoMessage = 'ユーザーを削除しました（関連する投稿プランも削除されました）。';
    }
}

$users = $pdo->query(
    'SELECT id, username, email, role, created_at
     FROM users
     ORDER BY id ASC'
)->fetchAll();
?>

<h2>【管理者】ユーザー管理</h2>

<?php if ($infoMessage !== ''): ?>
    <p class="info"><?= htmlspecialchars($infoMessage, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>
<?php if ($errorMessage !== ''): ?>
    <p class="error"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>

<table class="plan-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>ユーザー名</th>
            <th>メールアドレス</th>
            <th>権限</th>
            <th>登録日</th>
            <th>操作</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($users as $user): ?>
            <?php $isSelf = (int)$user['id'] === $currentUserId; ?>
            <tr>
                <td><?= (int)$user['id'] ?></td>
                <td><?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?><?= $isSelf ? '（自分）' : '' ?></td>
                <td><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= (int)$user['role'] === 1 ? '管理者' : '一般ユーザー' ?></td>
                <td><?= htmlspecialchars($user['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                <td>
                    <?php if (!$isSelf): ?>
                        <form method="post" action="admin_users.php" style="display:inline">
                            <input type="hidden" name="action" value="toggle_role">
                            <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">
                            <?php if ((int)$user['role'] === 1): ?>
                                <input type="hidden" name="new_role" value="0">
                                <button type="submit">一般ユーザーにする</button>
                            <?php else: ?>
                                <input type="hidden" name="new_role" value="1">
                                <button type="submit">管理者にする</button>
                            <?php endif; ?>
                        </form>

                        <form method="post" action="admin_users.php" style="display:inline"
                              onsubmit="return confirm('このユーザーを削除します。投稿された全プランも削除されます。よろしいですか？');">
                            <input type="hidden" name="action" value="delete_user">
                            <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">
                            <button type="submit">削除</button>
                        </form>
                    <?php else: ?>
                        <span>操作不可（自分自身）</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require_once 'includes/footer.php'; ?>

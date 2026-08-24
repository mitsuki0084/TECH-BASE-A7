<?php
/**
 * 担当: 開発メインB（バックエンドロジック）、負担軽めD（UI/フォーム）
 * 画面名: 観光エリアタグ管理画面
 *
 * 【③対応】role の比較は必ず (int) キャストしてから行う。
 */

require_once 'config/db.php';
require_once 'includes/header.php';

// 権限判定（③対応）
if (!isset($_SESSION['role']) || (int)$_SESSION['role'] !== 1) {
    echo '<p class="error">アクセス権限がありません。</p>';
    require_once 'includes/footer.php';
    exit;
}

$errorMessage = '';
$infoMessage  = '';

// 新規タグ追加
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_tag') {
    $tagName = trim($_POST['tag_name'] ?? '');

    if ($tagName === '') {
        $errorMessage = 'タグ名を入力してください。';
    } elseif (mb_strlen($tagName) > 50) {
        $errorMessage = 'タグ名は50文字以内で入力してください。';
    } else {
        $checkStmt = $pdo->prepare('SELECT id FROM tags WHERE name = ?');
        $checkStmt->execute([$tagName]);

        if ($checkStmt->fetch()) {
            $errorMessage = 'このタグ名は既に登録されています。';
        } else {
            $insertStmt = $pdo->prepare('INSERT INTO tags (name) VALUES (?)');
            $insertStmt->execute([$tagName]);
            $infoMessage = 'タグを追加しました。';
        }
    }
}

// タグ削除（関連プランのtag_idはON DELETE SET NULLによりNULL化される）
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_tag') {
    $tagId = filter_input(INPUT_POST, 'tag_id', FILTER_VALIDATE_INT);

    if ($tagId) {
        $deleteStmt = $pdo->prepare('DELETE FROM tags WHERE id = ?');
        $deleteStmt->execute([$tagId]);
        $infoMessage = 'タグを削除しました。関連プランのタグ設定は解除されました。';
    }
}

$tags = $pdo->query(
    "SELECT t.id, t.name, COUNT(p.id) AS plan_count
     FROM tags t
     LEFT JOIN plans p ON p.tag_id = t.id
     GROUP BY t.id, t.name
     ORDER BY t.id ASC"
)->fetchAll();
?>

<h2>【管理者】エリアタグ管理</h2>

<?php if ($infoMessage !== ''): ?>
    <p class="info"><?= htmlspecialchars($infoMessage, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>
<?php if ($errorMessage !== ''): ?>
    <p class="error"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>

<div class="card">
    <h3>新規タグ追加</h3>
    <form method="post" action="admin_tags.php" class="form-inline">
        <input type="hidden" name="action" value="add_tag">
        <label for="tag_name">タグ名</label>
        <input type="text" id="tag_name" name="tag_name" maxlength="50" required>
        <button type="submit">追加</button>
    </form>
</div>

<div class="card">
    <h3>登録済みタグ一覧</h3>
    <table class="plan-table">
        <thead>
            <tr>
                <th>タグ名</th>
                <th>利用中プラン数</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tags as $tag): ?>
                <tr>
                    <td><?= htmlspecialchars($tag['name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= (int)$tag['plan_count'] ?></td>
                    <td>
                        <form method="post" action="admin_tags.php" style="display:inline"
                              onsubmit="return confirm('このタグを削除します。関連プランのタグ設定は解除されます。よろしいですか？');">
                            <input type="hidden" name="action" value="delete_tag">
                            <input type="hidden" name="tag_id" value="<?= (int)$tag['id'] ?>">
                            <button type="submit">削除</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once 'includes/footer.php'; ?>

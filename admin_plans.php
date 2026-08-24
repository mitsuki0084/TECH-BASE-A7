<?php
/**
 * 担当: 開発メインB
 * 画面名: 投稿プラン管理（管理者用）
 *
 * 【③対応】role の比較は必ず (int) キャストしてから行う。
 *          login.php 側でキャスト済みだが、呼び出し側でも保険としてキャストする。
 * 【⑧対応】「強制削除」ではなく status=2（強制非公開）を正式仕様とする。
 */

require_once 'config/db.php';
require_once 'includes/header.php';

// 権限判定（③対応：(int) キャストしてから厳密比較する）
if (!isset($_SESSION['role']) || (int)$_SESSION['role'] !== 1) {
    echo '<p class="error">アクセス権限がありません。</p>';
    require_once 'includes/footer.php';
    exit;
}

$infoMessage = '';

// 公開／強制非公開の切り替え処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_status') {
    $planId    = filter_input(INPUT_POST, 'plan_id', FILTER_VALIDATE_INT);
    $newStatus = filter_input(INPUT_POST, 'new_status', FILTER_VALIDATE_INT);

    // 管理者が設定できるのは 1(公開) か 2(強制非公開) のみ
    if ($planId && in_array($newStatus, [1, 2], true)) {
        $updateStmt = $pdo->prepare('UPDATE plans SET status = ? WHERE id = ?');
        $updateStmt->execute([$newStatus, $planId]);
        $infoMessage = 'ステータスを更新しました。';
    }
}

// 全ユーザーの投稿プラン（ステータス問わず）を一覧表示
$plans = $pdo->query(
    "SELECT p.id, p.title, p.status, p.start_date, p.end_date,
            u.username, t.name AS tag_name
     FROM plans p
     INNER JOIN users u ON u.id = p.user_id
     LEFT JOIN tags t ON t.id = p.tag_id
     ORDER BY p.created_at DESC"
)->fetchAll();

$statusLabels = [
    0 => '非公開（ユーザー設定）',
    1 => '公開',
    2 => '強制非公開（管理者操作）',
];
?>

<h2>【管理者】全投稿プラン管理</h2>

<?php if ($infoMessage !== ''): ?>
    <p class="info"><?= htmlspecialchars($infoMessage, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>

<table class="plan-table">
    <thead>
        <tr>
            <th>タイトル</th>
            <th>作成者</th>
            <th>エリア</th>
            <th>日程</th>
            <th>ステータス</th>
            <th>操作</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($plans as $plan): ?>
            <tr>
                <td>
                    <a href="plan_detail.php?id=<?= (int)$plan['id'] ?>">
                        <?= htmlspecialchars($plan['title'], ENT_QUOTES, 'UTF-8') ?>
                    </a>
                </td>
                <td><?= htmlspecialchars($plan['username'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($plan['tag_name'] ?? '未設定', ENT_QUOTES, 'UTF-8') ?></td>
                <td>
                    <?= htmlspecialchars($plan['start_date'], ENT_QUOTES, 'UTF-8') ?>
                    〜
                    <?= htmlspecialchars($plan['end_date'], ENT_QUOTES, 'UTF-8') ?>
                </td>
                <td><?= htmlspecialchars($statusLabels[(int)$plan['status']] ?? '不明', ENT_QUOTES, 'UTF-8') ?></td>
                <td>
                    <?php if ((int)$plan['status'] !== 2): ?>
                        <form method="post" action="admin_plans.php" style="display:inline"
                              onsubmit="return confirm('このプランを強制非公開にします。よろしいですか？');">
                            <input type="hidden" name="action" value="toggle_status">
                            <input type="hidden" name="plan_id" value="<?= (int)$plan['id'] ?>">
                            <input type="hidden" name="new_status" value="2">
                            <button type="submit">強制非公開にする</button>
                        </form>
                    <?php else: ?>
                        <form method="post" action="admin_plans.php" style="display:inline">
                            <input type="hidden" name="action" value="toggle_status">
                            <input type="hidden" name="plan_id" value="<?= (int)$plan['id'] ?>">
                            <input type="hidden" name="new_status" value="1">
                            <button type="submit">公開に戻す</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require_once 'includes/footer.php'; ?>

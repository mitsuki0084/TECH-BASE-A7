<?php
/**
 * plan_list.php
 * 概要: plansテーブルとusersテーブルを結合して、旅行プランの一覧を表示する。
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/db.php';

// plansとusersを結合して、投稿者名も一緒に取得する
$sql = "SELECT plans.*, users.username
        FROM plans
        JOIN users ON plans.user_id = users.id
        ORDER BY plans.created_at DESC";

$stmt = $pdo->query($sql);
$plans = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<h1>旅行プラン一覧</h1>

<!-- ログイン中のユーザーだけに「新規作成」ボタンを表示 -->
<?php if (!empty($_SESSION['user_id'])): ?>
    <p><a href="plan_create.php">＋ 新しいプランを作成する</a></p>
<?php endif; ?>

<?php if (empty($plans)): ?>
    <p>まだ投稿されたプランがありません。</p>
<?php else: ?>
    <ul>
        <?php foreach ($plans as $plan): ?>
            <li>
                <a href="plan_detail.php?id=<?= (int)$plan['id'] ?>">
                    <?= htmlspecialchars($plan['title'], ENT_QUOTES, 'UTF-8') ?>
                </a>
                (行き先: <?= htmlspecialchars($plan['destination'], ENT_QUOTES, 'UTF-8') ?>)
                <br>
                投稿者: <?= htmlspecialchars($plan['username'], ENT_QUOTES, 'UTF-8') ?>
                ／期間: <?= htmlspecialchars($plan['start_date'], ENT_QUOTES, 'UTF-8') ?>
                〜 <?= htmlspecialchars($plan['end_date'], ENT_QUOTES, 'UTF-8') ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
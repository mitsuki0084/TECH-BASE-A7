<?php
/**
 * 担当: 開発メインA
 * 画面名: マイページ（作成プラン一覧・操作）
 * 
 * 【実装仕様】
 * 1. ログインチェック（非ログイン時は login.php へリダイレクト）。
 * 2. ログインユーザーが作成した全プランを `plans` テーブルから取得。
 * 3. 各プランの「タイトル」「日程」「公開ステータス」を一覧表示。
 * 4. 各プランに対する「編集 (plan_edit.php)」「削除 (削除POST処理)」ボタンを配置。
 */
session_start();
require_once 'config/db.php';
// 1. ログイン確認
if (!isset($_SESSION['user_id'])) {
$_SESSION['user_id'] = (int)$user['id'];
$_SESSION['role'] = (int)$user['role'];

header('Location: mypage.php');
exit;
}
require_once 'includes/header.php';



// 2. データベースから自分のプランを取得する処理
$user_id = (int)$_SESSION['user_id'];
// ※config/db.phpで $pdo が定義されている前提のコードです
$stmt = $pdo->prepare(
    'SELECT *
     FROM plans
     WHERE user_id = :user_id
     ORDER BY created_at DESC'
);
$stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>マイページ</h2>

<!-- 3 & 4. 取得したプランを一覧表示する処理 -->
<?php if (empty($plans)): ?>
    <p>作成したプランはまだありません。</p>
<?php else: ?>
    <table border="1">
        <tr>
            <th>タイトル</th>
            <th>日程</th>
            <th>操作</th>
        </tr>
        <?php foreach ($plans as $plan): ?>
        <tr>
            <!-- タイトル -->
            <td><?= htmlspecialchars($plan['title'], ENT_QUOTES, 'UTF-8') ?></td>
            
            <!-- 日程（開始日 〜 終了日） -->
            <td>
                <?= htmlspecialchars($plan['start_date'], ENT_QUOTES, 'UTF-8') ?> 〜 
                <?= htmlspecialchars($plan['end_date'], ENT_QUOTES, 'UTF-8') ?>
            </td>
            
            <!-- 編集・削除ボタン（URLパラメーターでIDを渡す） -->
            <td>
                    <a href="plan_detail.php?id=<?= (int)$plan['id'] ?>">編集</a> |
                
                <!-- 削除は確認メッセージを出すと安全です -->
                <a href="plan_delete.php?id=<?= $plan['id'] ?>" onclick="return confirm('本当に削除してよろしいですか？');">削除</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
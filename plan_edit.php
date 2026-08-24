<?php
/**

 * 担当: 開発メインA
 * 画面名: プラン基本情報編集
 * 
 * 【実装仕様】
 * 1. GETパラメータ `id` から編集対象プランを取得。
 * 2. 権限チェック: 自分の作成したプラン、または管理者でない場合は拒否。
 * 3. フォームに現在の設定値を初期表示。
 * 4. POST送信時: `plans` テーブルの対象レコードを UPDATE。

 */

require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId  = (int)$_SESSION['user_id'];
$isAdmin = (int)($_SESSION['role'] ?? 0) === 1;

$planId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$planId) {
    exit('プランIDが指定されていません。');
}

// 編集対象プランを取得
$planStmt = $pdo->prepare('SELECT * FROM plans WHERE id = ?');
$planStmt->execute([$planId]);
$plan = $planStmt->fetch();

if (!$plan) {
    exit('指定されたプランが見つかりません。');
}

// 権限チェック：自分の作成したプラン、または管理者でない場合は拒否
if ((int)$plan['user_id'] !== $userId && !$isAdmin) {
    exit('このプランを編集する権限がありません。');
}

$errorMessage = '';
$tags = $pdo->query('SELECT id, name FROM tags ORDER BY id ASC')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $startDate   = trim($_POST['start_date'] ?? '');
    $endDate     = trim($_POST['end_date'] ?? '');
    $destination = trim($_POST['destination'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $tagId       = filter_input(INPUT_POST, 'tag_id', FILTER_VALIDATE_INT);
    $status      = filter_input(INPUT_POST, 'status', FILTER_VALIDATE_INT);

    if ($title === '' || $startDate === '' || $endDate === '') {
        $errorMessage = 'タイトル・開始日・終了日は必須です。';
    } elseif (strtotime($startDate) === false || strtotime($endDate) === false) {
        $errorMessage = '日付の形式が正しくありません。';
    } elseif (strtotime($endDate) < strtotime($startDate)) {
        $errorMessage = '終了日は開始日以降の日付を指定してください。';
    } elseif (!in_array($status, [0, 1], true)) {
        // 一般ユーザーは 0(非公開)/1(公開) のみ選択可。
        // 2(強制非公開)は管理者操作(admin_plans.php)専用のため、ここでは選ばせない。
        $errorMessage = '公開設定の値が不正です。';
    } else {
        $updateStmt = $pdo->prepare(
            'UPDATE plans
             SET tag_id = ?, title = ?, start_date = ?, end_date = ?,
                 description = ?, destination = ?, status = ?
             WHERE id = ? AND (user_id = ? OR ? = 1)'
        );
        $updateStmt->execute([
            $tagId ?: null,
            $title,
            $startDate,
            $endDate,
            $description !== '' ? $description : null,
            $destination !== '' ? $destination : null,
            $status,
            $planId,
            $userId,
            $isAdmin ? 1 : 0,
        ]);

        header('Location: plan_detail.php?id=' . $planId);
        exit;
    }
}

require_once 'includes/header.php';
?>

<h2>プラン編集</h2>

<?php if ($errorMessage !== ''): ?>
    <p class="error"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>

<form method="post" action="plan_edit.php?id=<?= $planId ?>" class="form">
    <div>
        <label for="title">タイトル</label>
        <input type="text" id="title" name="title" maxlength="100" required
               value="<?= htmlspecialchars($_POST['title'] ?? $plan['title'], ENT_QUOTES, 'UTF-8') ?>">
    </div>

    <div>
        <label for="start_date">開始日</label>
        <input type="date" id="start_date" name="start_date" required
               value="<?= htmlspecialchars($_POST['start_date'] ?? $plan['start_date'], ENT_QUOTES, 'UTF-8') ?>">
    </div>

    <div>
        <label for="end_date">終了日</label>
        <input type="date" id="end_date" name="end_date" required
               value="<?= htmlspecialchars($_POST['end_date'] ?? $plan['end_date'], ENT_QUOTES, 'UTF-8') ?>">
    </div>

    <div>
        <label for="destination">目的地（天気表示に使用されます）</label>
        <input type="text" id="destination" name="destination" maxlength="100"
               value="<?= htmlspecialchars($_POST['destination'] ?? ($plan['destination'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </div>

    <div>
        <label for="tag_id">エリアタグ</label>
        <select id="tag_id" name="tag_id">
            <option value="">選択してください</option>
            <?php
            $currentTagId = $_POST['tag_id'] ?? $plan['tag_id'];
            foreach ($tags as $tag):
            ?>
                <option value="<?= (int)$tag['id'] ?>"
                    <?= ((int)$currentTagId === (int)$tag['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($tag['name'], ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div>
        <label for="description">説明</label>
        <textarea id="description" name="description" rows="4"><?= htmlspecialchars($_POST['description'] ?? ($plan['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
    </div>

    <div>
        <label>公開設定</label>
        <?php $currentStatus = (int)($_POST['status'] ?? $plan['status']); ?>
        <label><input type="radio" name="status" value="1" <?= $currentStatus === 1 ? 'checked' : '' ?>> 公開</label>
        <label><input type="radio" name="status" value="0" <?= $currentStatus === 0 ? 'checked' : '' ?>> 非公開</label>
        <?php if ((int)$plan['status'] === 2): ?>
            <p class="notice">
                現在このプランは管理者により強制非公開に設定されています。
                公開に切り替えても管理者が再度強制非公開にする場合があります。
            </p>
        <?php endif; ?>
    </div>

    <button type="submit">更新する</button>
</form>

<?php require_once 'includes/footer.php'; ?>

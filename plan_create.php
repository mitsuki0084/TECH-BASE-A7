<?php
/**
 * plan_create.php
 * 概要: ログイン中のユーザーが新しい旅行プランを投稿するページ。
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/db.php';

// 未ログインならログインページへ移動させる
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$errors = [];

// フォームが送信されたとき（POSTのとき）だけ登録処理を行う
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // フォームから受け取った値を取り出す（無ければ空文字にしておく）
    $title       = trim($_POST['title'] ?? '');
    $destination = trim($_POST['destination'] ?? '');
    $start_date  = $_POST['start_date'] ?? '';
    $end_date    = $_POST['end_date'] ?? '';
    $tag_id      = $_POST['tag_id'] ?? '';
    $description = trim($_POST['description'] ?? '');

    // 簡単な入力チェック
    if ($title === '') {
        $errors[] = 'タイトルを入力してください。';
    }
    if ($destination === '') {
        $errors[] = '目的地を入力してください。';
    }
    if ($start_date === '' || $end_date === '') {
        $errors[] = '日程を入力してください。';
    }

    // エラーが無ければDBに保存する
    if (empty($errors)) {
        $sql = "INSERT INTO plans (user_id, title, destination, start_date, end_date, tag_id, description, created_at)
                VALUES (:user_id, :title, :destination, :start_date, :end_date, :tag_id, :description, NOW())";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':user_id'     => $_SESSION['user_id'],
            ':title'       => $title,
            ':destination' => $destination,
            ':start_date'  => $start_date,
            ':end_date'    => $end_date,
            ':tag_id'      => $tag_id !== '' ? $tag_id : null,
            ':description' => $description,
        ]);

        $newId = $pdo->lastInsertId();

        // 登録できたら詳細ページへ移動する
        header('Location: plan_detail.php?id=' . (int)$newId);
        exit;
    }
}

// プルダウン用にタグ一覧を取得しておく
$tagStmt = $pdo->query("SELECT id, name FROM tags ORDER BY name");
$tags = $tagStmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<h1>新しいプランを作成する</h1>

<?php if (!empty($errors)): ?>
    <ul style="color:red;">
        <?php foreach ($errors as $error): ?>
            <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<form action="plan_create.php" method="post">
    <p>
        <label>タイトル<br>
            <input type="text" name="title" value="<?= htmlspecialchars($_POST['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </label>
    </p>
    <p>
        <label>目的地<br>
            <input type="text" name="destination" value="<?= htmlspecialchars($_POST['destination'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </label>
    </p>
    <p>
        <label>開始日<br>
            <input type="date" name="start_date" value="<?= htmlspecialchars($_POST['start_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </label>
    </p>
    <p>
        <label>終了日<br>
            <input type="date" name="end_date" value="<?= htmlspecialchars($_POST['end_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </label>
    </p>
    <p>
        <label>タグ<br>
            <select name="tag_id">
                <option value="">選択しない</option>
                <?php foreach ($tags as $tag): ?>
                    <option value="<?= (int)$tag['id'] ?>">
                        <?= htmlspecialchars($tag['name'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
    </p>
    <p>
        <label>説明<br>
            <textarea name="description" rows="5" cols="40"><?= htmlspecialchars($_POST['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
        </label>
    </p>
    <p>
        <button type="submit">登録する</button>
    </p>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

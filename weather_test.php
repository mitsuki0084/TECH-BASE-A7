<?php

require_once 'config/db.php';

$planId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$planId) {
    exit('テスト対象のプランIDを指定してください。例：weather_test.php?id=1');
}

$stmt = $pdo->prepare(
    'SELECT title, destination, description
     FROM plans
     WHERE id = ?'
);
$stmt->execute([$planId]);
$plan = $stmt->fetch();

if (!$plan) {
    exit('指定されたプランが見つかりません。');
}

$destination = trim((string)($plan['destination'] ?? ''));

if ($destination === '') {
    exit('このプランに目的地が登録されていません。');
}

require_once 'includes/header.php';
?>

<h2>天気表示テスト</h2>

<div
    id="weather-info"
    class="weather-test-card card"
    data-area="<?= htmlspecialchars($destination, ENT_QUOTES, 'UTF-8') ?>"
>
    <h3>
        <?= htmlspecialchars($plan['title'], ENT_QUOTES, 'UTF-8') ?>
    </h3>
    <p>
        目的地：
        <?= htmlspecialchars($destination, ENT_QUOTES, 'UTF-8') ?>
    </p>
    <p>天気情報を読み込み中...</p>
</div>

<?php require_once 'includes/footer.php'; ?>
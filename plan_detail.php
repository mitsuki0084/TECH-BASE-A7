
<?php
// filepath: c:\xampp\htdocs\TECH-BASE-A7\plan_detail.php
/**
 * 担当: みつき（API連携・バックエンド logic）、負担軽めC（UI・天気カード表示）
 * 画面名: プラン詳細・スケジュール調整画面
 * 
 * 【実装仕様】
 * 1. GETパラメータ `id` から該当プラン情報と関連する `schedules` レコードを取得（sort_order昇順）。
 * 2. 観光地の新規追加フォーム（日程番号、スポット名、時間帯、メモ）。
 * 3. 観光地リスト表示:
 *    - HTML構造に `<ul id="sortable-schedule">` と `<li class="sortable-item" data-id="スケジュールID">` を使用。
 *    - JS (main.js) がドラッグ＆ドロップを有効化し、update_order.php へ非同期送信。
 * 4. 天気情報表示用領域:
 *    - `<div id="weather-info" data-area="目的地名"></div>` を配置。
 *    - バックエンド側で天気API連携モジュールをコール、またはJSで描画。
 */
require_once 'config/db.php';

$planId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$planId) {
    exit('プランIDが指定されていません。');
}

/**
 * スケジュール追加処理
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['action'] ?? '') === 'add_schedule'
) {
    $dayNumber = filter_input(INPUT_POST, 'day_number', FILTER_VALIDATE_INT);
    $spotName = trim($_POST['spot_name'] ?? '');
    $timeSlot = trim($_POST['time_slot'] ?? '');
    $memo = trim($_POST['memo'] ?? '');

    if ($dayNumber && $spotName !== '') {
        $sortStmt = $pdo->prepare(
            'SELECT COALESCE(MAX(sort_order), 0) + 1
             FROM schedules
             WHERE plan_id = ?'
        );
        $sortStmt->execute([$planId]);
        $sortOrder = (int)$sortStmt->fetchColumn();

        $insertStmt = $pdo->prepare(
            'INSERT INTO schedules
                (plan_id, day_number, spot_name, time_slot, memo, sort_order)
             VALUES (?, ?, ?, ?, ?, ?)'
        );

        $insertStmt->execute([
            $planId,
            $dayNumber,
            $spotName,
            $timeSlot,
            $memo,
            $sortOrder
        ]);

        header('Location: plan_detail.php?id=' . $planId);
        exit;
    }

    $errorMessage = '日程番号とスポット名を入力してください。';
}

/**
 * プラン情報取得
 */
$planStmt = $pdo->prepare(
    'SELECT *
     FROM plans
     WHERE id = ?'
);
$planStmt->execute([$planId]);
$plan = $planStmt->fetch(PDO::FETCH_ASSOC);

if (!$plan) {
    exit('指定されたプランが見つかりません。');
}

/**
 * スケジュール取得
 */
$scheduleStmt = $pdo->prepare(
    'SELECT *
     FROM schedules
     WHERE plan_id = ?
     ORDER BY day_number ASC, sort_order ASC, id ASC'
);
$scheduleStmt->execute([$planId]);
$schedules = $scheduleStmt->fetchAll(PDO::FETCH_ASSOC);

$destination = $plan['destination'] ?? $plan['area'] ?? 'Tokyo';

require_once 'includes/header.php';
?>

<h2><?= htmlspecialchars($plan['title'] ?? 'プラン詳細', ENT_QUOTES, 'UTF-8') ?></h2>

<?php if (!empty($errorMessage)): ?>
    <p class="error">
        <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
    </p>
<?php endif; ?>

<div class="card">
    <p>
        目的地：
        <?= htmlspecialchars($destination, ENT_QUOTES, 'UTF-8') ?>
    </p>

    <?php if (!empty($plan['description'])): ?>
        <p><?= nl2br(htmlspecialchars($plan['description'], ENT_QUOTES, 'UTF-8')) ?></p>
    <?php endif; ?>
</div>

<div id="weather-info"
     class="card"
     data-area="<?= htmlspecialchars($destination, ENT_QUOTES, 'UTF-8') ?>">
    <h3>目的地の天気予報</h3>
    <p>天気情報を読み込み中...</p>
</div>

<div class="card">
    <h3>観光地を追加</h3>

    <form method="post" action="plan_detail.php?id=<?= $planId ?>">
        <input type="hidden" name="action" value="add_schedule">

        <div>
            <label for="day_number">日程番号</label>
            <input
                type="number"
                id="day_number"
                name="day_number"
                min="1"
                required
            >
        </div>

        <div>
            <label for="spot_name">スポット名</label>
            <input
                type="text"
                id="spot_name"
                name="spot_name"
                maxlength="255"
                required
            >
        </div>

        <div>
            <label for="time_slot">時間帯</label>
            <input
                type="text"
                id="time_slot"
                name="time_slot"
                placeholder="例：10:00〜12:00"
            >
        </div>

        <div>
            <label for="memo">メモ</label>
            <textarea
                id="memo"
                name="memo"
                rows="4"
            ></textarea>
        </div>

        <button type="submit">スケジュールを追加</button>
    </form>
</div>

<div class="card">
    <h3>スケジュール一覧</h3>

    <ul id="sortable-schedule" class="sortable-list">
        <?php if (empty($schedules)): ?>
            <li>スケジュールはまだ登録されていません。</li>
        <?php else: ?>
            <?php foreach ($schedules as $schedule): ?>
                <li
                    class="sortable-item"
                    data-id="<?= (int)$schedule['id'] ?>"
                >
                    <strong>
                        Day <?= (int)$schedule['day_number'] ?>
                    </strong>

                    <span>
                        <?= htmlspecialchars(
                            $schedule['spot_name'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </span>

                    <?php if (!empty($schedule['time_slot'])): ?>
                        <span>
                            （<?= htmlspecialchars(
                                $schedule['time_slot'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>）
                        </span>
                    <?php endif; ?>

                    <?php if (!empty($schedule['memo'])): ?>
                        <p>
                            <?= nl2br(htmlspecialchars(
                                $schedule['memo'],
                                ENT_QUOTES,
                                'UTF-8'
                            )) ?>
                        </p>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        <?php endif; ?>
    </ul>
</div>

<?php require_once 'includes/footer.php'; ?>
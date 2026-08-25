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
 *    - HTML構造に `<ul id="sortable-schedule">` と `<li class="schedule-item" data-id="スケジュールID">` を使用。
 *    - 【今回対応】スケジュール項目をクリックすると誰でもその場で編集できるようにした。
 *      保存時は main.js から update_schedule.php（JSON API・認証チェックなし）を呼び出す。
 * 4. 天気情報表示用領域:
 *    - 【今回対応】単一の目的地天気表示から「旅行日程（start_date〜end_date）の日別天気カード」表示に変更。
 *      カードは左＝旅行初日、右＝旅行最終日の順に並べ、main.js が Open-Meteo API から取得して描画する。
 *      天気情報取得日時はカード群の右上に小さく表示する。
 * 5. 【今回対応】天気情報をもとに Gemini API で「おすすめの服装イラスト」を生成し表示する領域を追加。
 *    API呼び出し処理はすべて main.js に集約する。
 *
 * 【④対応・認可チェック（維持）】
 * ・観光地追加処理(add_schedule)は「ログイン済み」かつ「プラン所有者本人（または管理者）」
 *   のみ実行できる。
 * ・非公開(status=0)・強制非公開(status=2)のプランは、所有者本人または管理者のみ閲覧可能。
 * 【今回対応・スケジュール編集の仕様】
 * ・既存スケジュールの「クリック編集」については、指示に基づき所有者チェックを行わず
 *   誰でも編集できる仕様としている（update_schedule.php側も認証なし）。
 */

session_start();
require_once 'config/db.php';

$planId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$planId) {
    exit('プランIDが指定されていません。');
}

$currentUserId = $_SESSION['user_id'] ?? null;
$isAdmin = (int)($_SESSION['role'] ?? 0) === 1;

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

$isOwner = $currentUserId !== null
    && (int)$plan['user_id'] === (int)$currentUserId;

// 非公開・強制非公開プランは所有者本人または管理者のみ閲覧可能
if ((int)$plan['status'] !== 1 && !$isOwner && !$isAdmin) {
    exit('このプランは非公開のため閲覧できません。');
}

/**
 * スケジュール追加処理（新規追加のみ・所有者/管理者限定）
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['action'] ?? '') === 'add_schedule'
) {
    if ($currentUserId === null) {
        $errorMessage = 'スケジュールを追加するにはログインが必要です。';
    } else {
        $dayNumber = filter_input(
            INPUT_POST,
            'day_number',
            FILTER_VALIDATE_INT
        );
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
                $timeSlot !== '' ? $timeSlot : null,
                $memo !== '' ? $memo : null,
                $sortOrder
            ]);

            header('Location: plan_detail.php?id=' . $planId);
            exit;
        }

        $errorMessage = '日程番号とスポット名を入力してください。';
    }
}

/**
 * スケジュール取得
 */
$scheduleStmt = $pdo->prepare(
    'SELECT *
     FROM schedules
     WHERE plan_id = ?
     ORDER BY
         day_number ASC,
         CASE
             WHEN time_slot REGEXP "^[0-9]{1,2}:[0-9]{2}"
             THEN STR_TO_DATE(
                 LEFT(TRIM(time_slot), 5),
                 "%H:%i"
             )
             ELSE NULL
         END IS NULL ASC,
         CASE
             WHEN time_slot REGEXP "^[0-9]{1,2}:[0-9]{2}"
             THEN STR_TO_DATE(
                 LEFT(TRIM(time_slot), 5),
                 "%H:%i"
             )
             ELSE NULL
         END ASC,
         sort_order ASC,
         id ASC'
);
$scheduleStmt->execute([$planId]);
$schedules = $scheduleStmt->fetchAll(PDO::FETCH_ASSOC);

// 目的地（天気検索に使用）
$destination = trim((string)($plan['destination'] ?? ''));

if ($destination === '') {
    $errorMessage = ($errorMessage ?? '') . ' このプランに目的地が登録されていません。天気情報は表示できません。';
    $destination = '東京';
}

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

    <p>
        旅行日程：
        <?= htmlspecialchars($plan['start_date'], ENT_QUOTES, 'UTF-8') ?>
        〜
        <?= htmlspecialchars($plan['end_date'], ENT_QUOTES, 'UTF-8') ?>
    </p>

    <?php if (!empty($plan['description'])): ?>
        <p><?= nl2br(htmlspecialchars($plan['description'], ENT_QUOTES, 'UTF-8')) ?></p>
    <?php endif; ?>

    <?php if ((int)$plan['status'] !== 1): ?>
        <p class="notice">
            <?= (int)$plan['status'] === 2 ? '管理者により強制非公開に設定されています。' : '非公開に設定されています。' ?>
            （あなたと管理者のみ閲覧できます）
        </p>
    <?php endif; ?>
</div>

<!--
    旅行日程の天気予報カード表示エリア
    data-destination / data-start-date / data-end-date を main.js が読み取り、
    Open-Meteo APIから日程分の天気予報を取得してカードを描画する。
    旅行日程がAPIの予報取得可能範囲（本日から16日先まで）を超える場合は、
    main.js側の判定により直近1週間の予報にフォールバックする。
-->
<div id="weather-forecast"
    class="card"
    data-destination="<?= htmlspecialchars($destination, ENT_QUOTES, 'UTF-8') ?>"
    data-start-date="<?= htmlspecialchars($plan['start_date'], ENT_QUOTES, 'UTF-8') ?>"
    data-end-date="<?= htmlspecialchars($plan['end_date'], ENT_QUOTES, 'UTF-8') ?>">
    <div class="weather-forecast-header">
        <h3>旅行期間の天気予報</h3>
        <span id="weather-fetched-at" class="weather-fetched-at"></span>
    </div>
    <div id="weather-cards" class="weather-cards">
        <p class="weather-loading">天気情報を読み込み中...</p>
    </div>
</div>



<?php if ($currentUserId !== null): ?>
<div class="card">
    <h3>観光地を追加</h3>

        <form method="post" action="plan_detail.php?id=<?= (int)$planId ?>">
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
<?php else: ?>
<div class="card">
        <p>観光地を追加するにはログインしてください。</p>
</div>
<?php endif; ?>

<div class="card">
    <h3>スケジュール一覧</h3>
    <p class="schedule-list-note">スケジュールをクリックすると、誰でもその場で内容を編集できます。</p>

    <ul
        id="sortable-schedule"
        class="schedule-list"
        data-plan-id="<?= (int)$planId ?>"
    >
        <?php if (empty($schedules)): ?>
            <li>スケジュールはまだ登録されていません。</li>
        <?php else: ?>
            <?php foreach ($schedules as $schedule): ?>
                <li
                    class="schedule-item"
                    data-id="<?= (int)$schedule['id'] ?>"
                    data-day-number="<?= (int)$schedule['day_number'] ?>"
                    data-spot-name="<?= htmlspecialchars($schedule['spot_name'], ENT_QUOTES, 'UTF-8') ?>"
                    data-time-slot="<?= htmlspecialchars($schedule['time_slot'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    data-memo="<?= htmlspecialchars($schedule['memo'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    tabindex="0"
                >
                    <div class="schedule-item-view">
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

                        <span class="schedule-edit-hint">クリックして編集</span>
                    </div>
                </li>
            <?php endforeach; ?>
        <?php endif; ?>
    </ul>
</div>

<?php require_once 'includes/footer.php'; ?>

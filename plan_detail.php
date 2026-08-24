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
 *
 * 【④対応・認可チェック追加】
 * ・観光地追加処理(add_schedule)は「ログイン済み」かつ「プラン所有者本人（または管理者）」
 *   のみ実行できるようにチェックを追加した（従来は誰でも実行可能だった）。
 * ・非公開(status=0)・強制非公開(status=2)のプランは、所有者本人または管理者のみ閲覧可能とした。
 */
require_once 'config/db.php';

$planId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT); // プランIDを取得し、整数としてバリデーション

if (!$planId) {
    exit('プランIDが指定されていません。');
}

$currentUserId = $_SESSION['user_id'] ?? null;
$isAdmin       = (int)($_SESSION['role'] ?? 0) === 1;

/**
 * プラン情報取得
 * 【④対応】add_schedule 処理より先に取得し、所有者チェックに使用する。
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

$isOwner = $currentUserId !== null && (int)$plan['user_id'] === (int)$currentUserId;

// 【④対応】非公開・強制非公開プランは所有者本人または管理者のみ閲覧可能
if ((int)$plan['status'] !== 1 && !$isOwner && !$isAdmin) {
    exit('このプランは非公開のため閲覧できません。');
}

/**
 * スケジュール追加処理
 * 【④対応】未ログイン・非所有者からの追加をすべて拒否する。
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' // POSTリクエストかつ、アクションがスケジュール追加の場合
    && ($_POST['action'] ?? '') === 'add_schedule'
) {
    if ($currentUserId === null) {
        // 未ログインは拒否
        $errorMessage = 'スケジュールを追加するにはログインが必要です。';
    } elseif (!$isOwner && !$isAdmin) {
        // 所有者・管理者以外は拒否
        $errorMessage = 'このプランにスケジュールを追加する権限がありません。';
    } else {
        // 入力値の取得とバリデーション
        $dayNumber = filter_input(INPUT_POST, 'day_number', FILTER_VALIDATE_INT);
        $spotName = trim($_POST['spot_name'] ?? '');
        $timeSlot = trim($_POST['time_slot'] ?? '');
        $memo = trim($_POST['memo'] ?? '');

        // 日程番号とスポット名が有効な場合にスケジュールを追加
        if ($dayNumber && $spotName !== '') {
            $sortStmt = $pdo->prepare( // 新しいスケジュールの sort_order を決定するために、既存の最大 sort_order を取得
                'SELECT COALESCE(MAX(sort_order), 0) + 1
                FROM schedules
                WHERE plan_id = ?'
            );
            $sortStmt->execute([$planId]);
            $sortOrder = (int)$sortStmt->fetchColumn();

            // 新しいスケジュールを `schedules` テーブルに挿入
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

            // リダイレクトして、フォームの再送信を防ぐ
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
    ORDER BY day_number ASC, sort_order ASC, id ASC'
); // プランIDに基づいてスケジュールを取得し、日程番号、並び順、IDの昇順でソート
$scheduleStmt->execute([$planId]);
$schedules = $scheduleStmt->fetchAll(PDO::FETCH_ASSOC);

// 目的地の天気情報を表示するために、プランの目的地またはエリアを取得
$destination = trim((string)($plan['destination'] ?? ''));

if ($destination === '') { // 目的地が未設定の場合はデフォルト値を設定
    $errorMessage = ($errorMessage ?? '') . ' このプランに目的地が登録されていません。天気情報は表示できません。';
    $destination = '東京';
}

// HTMLヘッダーを読み込み
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

    <?php if ((int)$plan['status'] !== 1): ?>
        <p class="notice">
            <?= (int)$plan['status'] === 2 ? '管理者により強制非公開に設定されています。' : '非公開に設定されています。' ?>
            （あなたと管理者のみ閲覧できます）
        </p>
    <?php endif; ?>
</div>

<div id="weather-info"
    class="card"
    data-area="<?= htmlspecialchars($destination, ENT_QUOTES, 'UTF-8') ?>">
    <h3>目的地の天気予報</h3>
    <p>天気情報を読み込み中...</p>
</div>

<?php if ($isOwner || $isAdmin): ?>
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
<?php else: ?>
<div class="card">
    <p>観光地の追加はプラン作成者本人のみ行えます。</p>
</div>
<?php endif; ?>

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

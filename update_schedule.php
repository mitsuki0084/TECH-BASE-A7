<?php
/**
 * update_schedule.php
 * 概要: plan_detail.php のスケジュール一覧で「クリックして編集」した内容を保存する非同期API（JSON）。
 *
 * 【重要・仕様上の注意】
 * ご指示（「スケジュールをクリックしたら誰でも変更を加えられるようにしたい」）に基づき、
 * このエンドポイントは意図的にログインチェック・所有者チェックを行っていない。
 * そのため、このURLを直接叩けば誰でも任意のスケジュールを書き換えられる状態になる。
 * 本番公開する場合は、荒らし対策として最低限のレート制限や、
 * 編集ログ（誰が・いつ変更したか）の記録などを追加することを推奨する。
 *
 * 【リクエスト形式】
 * POST（JSON） { "id": 1, "day_number": 2, "spot_name": "浅草寺", "time_slot": "10:00〜12:00", "memo": "..." }
 *
 * 【レスポンス形式】
 * 成功: { "success": true }
 * 失敗: { "success": false, "message": "..." }
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once 'config/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
function respond(array $data, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond([
        'success' => false,
        'message' => 'POSTリクエストのみ利用できます。'
    ], 405);
}

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

if (!is_array($input)) {
    respond([
        'success' => false,
        'message' => '不正なデータです。'
    ], 400);
}

$id = filter_var(
    $input['id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

$dayNumber = filter_var(
    $input['day_number'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

$spotName = trim((string)($input['spot_name'] ?? ''));
$timeSlot = trim((string)($input['time_slot'] ?? ''));
$memo     = trim((string)($input['memo'] ?? ''));

if ($id === false) {
    respond([
        'success' => false,
        'message' => 'IDが不正です。'
    ], 400);
}

if ($dayNumber === false) {
    respond([
        'success' => false,
        'message' => '日程番号が不正です。'
    ], 400);
}

if ($spotName === '') {
    respond([
        'success' => false,
        'message' => 'スポット名を入力してください。'
    ], 400);
}

if (mb_strlen($spotName) > 255) {
    respond([
        'success' => false,
        'message' => 'スポット名は255文字以内で入力してください。'
    ], 400);
}

// 対象スケジュールの存在確認
$checkStmt = $pdo->prepare(
    'SELECT id, day_number, spot_name, time_slot, memo
     FROM schedules
     WHERE id = ?'
);
$checkStmt->execute([$id]);
$oldSchedule = $checkStmt->fetch(PDO::FETCH_ASSOC);

if (!$oldSchedule) {
    respond([
        'success' => false,
        'message' => '対象のスケジュールが見つかりません。'
    ], 404);
}

try {
    $pdo->beginTransaction();

    $updateStmt = $pdo->prepare(
        'UPDATE schedules
         SET day_number = ?, spot_name = ?, time_slot = ?, memo = ?
         WHERE id = ?'
    );

    $newTimeSlot = $timeSlot !== '' ? $timeSlot : null;
    $newMemo = $memo !== '' ? $memo : null;

    $updateStmt->execute([
        $dayNumber,
        $spotName,
        $newTimeSlot,
        $newMemo,
        $id,
    ]);

    $logStmt = $pdo->prepare(
        'INSERT INTO edit_logs (
            schedule_id,
            user_id,
            old_day_number,
            new_day_number,
            old_spot_name,
            new_spot_name,
            old_time_slot,
            new_time_slot,
            old_memo,
            new_memo,
            ip_address
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );

    $logStmt->execute([
        $id,
        $_SESSION['user_id'] ?? null,
        $oldSchedule['day_number'],
        $dayNumber,
        $oldSchedule['spot_name'],
        $spotName,
        $oldSchedule['time_slot'],
        $newTimeSlot,
        $oldSchedule['memo'],
        $newMemo,
        $_SERVER['REMOTE_ADDR'] ?? null,
    ]);

    $pdo->commit();

    respond(['success' => true]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());

    respond([
        'success' => false,
        'message' => '編集内容の保存に失敗しました。'
    ], 500);
}

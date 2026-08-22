<?php
// filepath: c:\xampp\htdocs\TECH-BASE-A7\update_order.php
/**
 * 担当: みつき（バックエンド & API担当）
 * 処理内容: ドラッグ＆ドロップによる並び替え順序の非同期保存処理（JSON API）
 * 
 * 【実装仕様】
 * 1. リクエストメソッドチェック (POST) および JSON入力のデコード。
 * 2. 受け取るデータフォーマット例:
 *    { "schedules": [ { "id": 1, "sort_order": 1 }, { "id": 5, "sort_order": 2 } ] }
 * 3. 認証確認（ログインユーザー以外の操作拒否）。
 * 4. トランザクションを開始し、`schedules` テーブルの `sort_order` を一括 UPDATE。
 * 5. レスポンスとして `{"success": true}` またはエラーメッセージを JSON 形式で返却。
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

$userId = filter_var(
    $_SESSION['user_id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if ($userId === false) {
    respond([
        'success' => false,
        'message' => 'ログインが必要です。'
    ], 401);
}

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

if (!is_array($input) || !isset($input['schedules'])) {
    respond([
        'success' => false,
        'message' => '不正なデータです。'
    ], 400);
}

$schedules = $input['schedules'];

if (!is_array($schedules) || count($schedules) === 0) {
    respond([
        'success' => false,
        'message' => 'スケジュールデータがありません。'
    ], 400);
}

$items = [];
$ids = [];
$orders = [];

foreach ($schedules as $item) {
    if (!is_array($item)) {
        respond([
            'success' => false,
            'message' => 'スケジュール形式が不正です。'
        ], 400);
    }

    $id = filter_var(
        $item['id'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    $sortOrder = filter_var(
        $item['sort_order'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if ($id === false || $sortOrder === false) {
        respond([
            'success' => false,
            'message' => 'IDまたは並び順が不正です。'
        ], 400);
    }

    if (isset($ids[$id]) || isset($orders[$sortOrder])) {
        respond([
            'success' => false,
            'message' => 'IDまたは並び順が重複しています。'
        ], 400);
    }

    $ids[$id] = true;
    $orders[$sortOrder] = true;

    $items[] = [
        'id' => $id,
        'sort_order' => $sortOrder
    ];
}

try {
    $placeholders = implode(',', array_fill(0, count($items), '?'));
    $idValues = array_column($items, 'id');

    $checkSql = "
        SELECT s.id, s.plan_id
        FROM schedules s
        INNER JOIN plans p ON p.id = s.plan_id
        WHERE p.user_id = ?
          AND s.id IN ($placeholders)
    ";

    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute(array_merge([$userId], $idValues));
    $ownedSchedules = $checkStmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($ownedSchedules) !== count($items)) {
        respond([
            'success' => false,
            'message' => '操作権限がありません。'
        ], 403);
    }

    $planIds = array_unique(array_column($ownedSchedules, 'plan_id'));

    if (count($planIds) !== 1) {
        respond([
            'success' => false,
            'message' => '同じプラン内のスケジュールのみ並び替えできます。'
        ], 400);
    }

    $pdo->beginTransaction();

    $updateStmt = $pdo->prepare(
        'UPDATE schedules
         SET sort_order = :sort_order
         WHERE id = :id
           AND plan_id = :plan_id'
    );

    $planId = (int)reset($planIds);

    foreach ($items as $item) {
        $updateStmt->execute([
            ':sort_order' => $item['sort_order'],
            ':id' => $item['id'],
            ':plan_id' => $planId
        ]);
    }

    $pdo->commit();

    respond(['success' => true]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());

    respond([
        'success' => false,
        'message' => '並び順の保存に失敗しました。'
    ], 500);
}
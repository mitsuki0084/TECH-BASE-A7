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
declare(strict_types=1);// 厳格な型チェックを有効化

header('Content-Type: application/json; charset=utf-8');// JSONレスポンスのヘッダーを設定

require_once 'config/db.php';// データベース接続設定の読み込み

if (session_status() === PHP_SESSION_NONE) {// セッションが開始されていない場合は開始
    session_start();
}

function respond(array $data, int $status = 200): never// レスポンスを返してスクリプトを終了する関数
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {// POSTリクエスト以外は405 Method Not Allowedを返す
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

if ($userId === false) {// ログインしていない場合は401 Unauthorizedを返す
    respond([
        'success' => false,
        'message' => 'ログインが必要です。'
    ], 401);
}

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

if (!is_array($input) || !isset($input['schedules'])) {// JSONデコードに失敗した場合や、期待するキーがない場合は400 Bad Requestを返す
    respond([
        'success' => false,
        'message' => '不正なデータです。'
    ], 400);
}

$schedules = $input['schedules'];

if (!is_array($schedules) || count($schedules) === 0) {// スケジュールデータが空の場合は400 Bad Requestを返す
    respond([
        'success' => false,
        'message' => 'スケジュールデータがありません。'
    ], 400);
}

$items = [];
$ids = [];
$orders = [];

foreach ($schedules as $item) {// 各スケジュールアイテムの検証
    if (!is_array($item)) {// アイテムが配列でない場合は400 Bad Requestを返す
        respond([
            'success' => false,
            'message' => 'スケジュール形式が不正です。'
        ], 400);
    }

    $id = filter_var(// IDのバリデーション
        $item['id'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]// IDは1以上の整数である必要がある
    );

    $sortOrder = filter_var(// 並び順のバリデーション
        $item['sort_order'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]// 並び順は1以上の整数である必要がある
    );

    if ($id === false || $sortOrder === false) {// IDまたは並び順が不正な場合は400 Bad Requestを返す
        respond([
            'success' => false,
            'message' => 'IDまたは並び順が不正です。'
        ], 400);
    }

    if (isset($ids[$id]) || isset($orders[$sortOrder])) {// IDまたは並び順が重複している場合は400 Bad Requestを返す
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

try {// データベース操作をトランザクションで実行
    $placeholders = implode(',', array_fill(0, count($items), '?'));// プレースホルダーを作成
    $idValues = array_column($items, 'id');// IDの値を抽出
// SQLクエリを作成して、ユーザーが所有するスケジュールかどうかを確認
    $checkSql = "
        SELECT s.id, s.plan_id
        FROM schedules s
        INNER JOIN plans p ON p.id = s.plan_id
        WHERE p.user_id = ?
        AND s.id IN ($placeholders)
    ";
// SQLクエリを実行して、ユーザーが所有するスケジュールを取得
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute(array_merge([$userId], $idValues));
    $ownedSchedules = $checkStmt->fetchAll(PDO::FETCH_ASSOC);
// ユーザーが所有するスケジュールの数と、リクエストで送信されたスケジュールの数を比較して、操作権限があるかどうかを確認
    if (count($ownedSchedules) !== count($items)) {
        respond([
            'success' => false,
            'message' => '操作権限がありません。'
        ], 403);
    }

    $planIds = array_unique(array_column($ownedSchedules, 'plan_id'));// 同じプラン内のスケジュールのみ並び替え可能かどうかを確認

    if (count($planIds) !== 1) {// 複数のプランにまたがるスケジュールが含まれている場合は400 Bad Requestを返す
        respond([
            'success' => false,
            'message' => '同じプラン内のスケジュールのみ並び替えできます。'
        ], 400);
    }
// トランザクションを開始して、スケジュールの並び順を更新
    $pdo->beginTransaction();
// UPDATE文を準備して、スケジュールの並び順を更新
    $updateStmt = $pdo->prepare(
        'UPDATE schedules
        SET sort_order = :sort_order
        WHERE id = :id
        AND plan_id = :plan_id'
    );

    $planId = (int)reset($planIds);

    foreach ($items as $item) {// 各スケジュールアイテムの並び順を更新
        $updateStmt->execute([
            ':sort_order' => $item['sort_order'],
            ':id' => $item['id'],
            ':plan_id' => $planId
        ]);
    }
// トランザクションをコミットして、変更を確定
    $pdo->commit();
// 成功レスポンスを返す
    respond(['success' => true]);
} catch (Throwable $e) {// エラーが発生した場合はトランザクションをロールバックして、エラーレスポンスを返す
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
// エラーログにエラーメッセージを出力
    error_log($e->getMessage());

    respond([
        'success' => false,
        'message' => '並び順の保存に失敗しました。'
    ], 500);
}
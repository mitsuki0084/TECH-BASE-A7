<?php
/**
 * 担当: あなた（バックエンド & API担当）
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

header('Content-Type: application/json; charset=utf-8');
require_once 'config/db.php';

// ログイン確認
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// JSON データの取得
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['schedules'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid Data']);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("UPDATE schedules SET sort_order = :sort_order WHERE id = :id");

    foreach ($input['schedules'] as $item) {
        $stmt->bindValue(':sort_order', (int)$item['sort_order'], PDO::PARAM_INT);
        $stmt->bindValue(':id', (int)$item['id'], PDO::PARAM_INT);
        $stmt->execute();
    }

    $pdo->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
<?php
/**
 * 担当: 開発メインA
 * 画面名: プラン新規作成
 * 
 * 【実装仕様】
 * 1. ログインチェック（非ログイン時は login.php へリダイレクト）。
 * 2. 入力項目: タイトル、開始日、終了日、エリアタグ (tagsテーブルから一覧取得)、公開/非公開。
 * 3. POST送信時: `plans` テーブルへ INSERT 実行。
 * 4. 作成完了後、詳細・スケジュール追加画面 (plan_detail.php?id=作成したID) へリダイレクト。
 */

require_once 'config/db.php';
require_once 'includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>

<h2>新規旅行プラン作成</h2>
<!-- TODO: 開発メインA プラン作成フォームの実装を行うこと -->

<?php require_once 'includes/footer.php'; ?>
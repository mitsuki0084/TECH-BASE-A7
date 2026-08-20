<?php
/**
 * 担当: 開発メインA
 * 画面名: マイページ（作成プラン一覧・操作）
 * 
 * 【実装仕様】
 * 1. ログインチェック（非ログイン時は login.php へリダイレクト）。
 * 2. ログインユーザーが作成した全プランを `plans` テーブルから取得。
 * 3. 各プランの「タイトル」「日程」「公開ステータス」を一覧表示。
 * 4. 各プランに対する「編集 (plan_edit.php)」「削除 (削除POST処理)」ボタンを配置。
 */

require_once 'config/db.php';
require_once 'includes/header.php';

// ログイン確認
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>

<h2>マイページ</h2>
<!-- TODO: 開発メインA プラン一覧表示および削除処理の実装を行うこと -->

<?php require_once 'includes/footer.php'; ?>
<?php
/**
 * 担当: 開発メインA
 * 画面名: プラン基本情報編集
 * 
 * 【実装仕様】
 * 1. GETパラメータ `id` から編集対象プランを取得。
 * 2. 権限チェック: 自分の作成したプラン、または管理者でない場合は拒否。
 * 3. フォームに現在の設定値を初期表示。
 * 4. POST送信時: `plans` テーブルの対象レコードを UPDATE。
 */

require_once 'config/db.php';
require_once 'includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>

<h2>プラン編集</h2>
<!-- TODO: 開発メインA プラン基本情報の更新処理を行うこと -->

<?php require_once 'includes/footer.php'; ?>
<?php
/**
 * 担当: 開発メインB
 * 画面名: 管理者ログイン画面
 * 
 * 【実装仕様】
 * 1. ログインフォーム作成。
 * 2. 認証処理時、`users` テーブルから `role = 1`（管理者）であるか判定。
 * 3. 条件を満たさない場合は「管理者権限がありません」としてアクセスを拒否。
 * 4. 認証成功時、`admin_tags.php` または `admin_plans.php` へリダイレクト。
 */

require_once 'config/db.php';
require_once 'includes/header.php';
?>

<h2>管理者ログイン</h2>
<!-- TODO: 開発メインB 管理者認証処理の実装を行うこと -->

<?php require_once 'includes/footer.php'; ?>
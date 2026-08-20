<?php
/**
 * 担当: 開発メインA
 * 画面名: ユーザー新規登録
 * 
 * 【実装仕様】
 * 1. GETリクエスト時: 登録フォーム（ユーザー名、メールアドレス、パスワード）を表示。
 * 2. POSTリクエスト時:
 *    - 入力値バリデーション（空チェック、メール形式チェック等）。
 *    - メールアドレスの重複チェック（`users`テーブル）。
 *    - パスワードを `password_hash()` で暗号化。
 *    - `users` テーブルへ INSERT（role はデフォルト 0:一般ユーザー）。
 *    - 登録成功後、login.php へリダイレクト。
 */

require_once 'config/db.php';
require_once 'includes/header.php';
?>

<h2>ユーザー新規登録</h2>
<!-- TODO: 開発メインA 処理・フォームの実装を行うこと -->

<?php require_once 'includes/footer.php'; ?>
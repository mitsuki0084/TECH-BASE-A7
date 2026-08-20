<?php
/**
 * 担当: 開発メインA
 * 画面名: ログアウト処理
 * 
 * 【実装仕様】
 * 1. $_SESSION 変数を空にする。
 * 2. セッションクッキーが存在する場合は削除する。
 * 3. `session_destroy()` を呼び出してセッションを完全破棄。
 * 4. login.php へリダイレクト。
 */

session_start();
// TODO: 開発メインA ログアウト処理の実装を行うこと

header('Location: login.php');
exit;
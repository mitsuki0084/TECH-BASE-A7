<?php
/**
 * 担当: 開発メインA
 * 画面名: ログアウト処理
 */

require_once 'config/db.php';

// $_SESSION を空にする
$_SESSION = [];

// セッションクッキーが存在する場合は削除する
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// セッションを完全破棄
session_destroy();

header('Location: login.php');
exit;

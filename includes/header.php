<?php

/**
 * 共通ヘッダーパーツ
 * 概要: セッションの開始、共通HTML頭部、ナビゲーションバーの表示を行う。
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>旅行プラン作成・共有ツール</title>
<link rel="stylesheet" href="/TECH-BASE-A7/css/style.css">
</head>
<body>
    <header class="site-header">
        <div class="header-container">
<h1 class="logo">
    <a href="index.php">🌈 TRIPPY ～旅行でHAPPYに～ ✈️</a>
</h1>

            <nav class="main-nav">
                <ul>
                    <li><a href="index.php">プランを探す</a></li>

                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li><a href="plan_create.php">新規プラン作成</a></li>
                        <li><a href="mypage.php">マイページ</a></li>

                        <?php if (
                            isset($_SESSION['role']) &&
                            $_SESSION['role'] === 1
                        ): ?>
                            <li>
                                <a href="admin_tags.php" class="admin-link">
                                    管理者画面
                                </a>
                            </li>
                        <?php endif; ?>

                        <li><a href="logout.php">ログアウト</a></li>
                    <?php else: ?>
                        <li><a href="login.php">ログイン</a></li>
                        <li><a href="register.php">会員登録</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <main class="main-content">
<?php
/**
 * 担当: 開発メインB
 * 画面名: 投稿プラン管理（管理者用）
 * 
 * 【実装仕様】
 * 1. 管理者権限確認 (`$_SESSION['role'] === 1`)。
 * 2. 全ユーザーの投稿プラン（ステータス問わず）を一覧表示。
 * 3. 「公開 / 強制非公開」の切り替えスイッチまたはボタンを配置。
 * 4. ボタン操作により `plans` テーブルの `status` を更新（2: 強制非公開 等）。
 */

require_once 'config/db.php';
require_once 'includes/header.php';

// 権限判定
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 1) {
    echo "<p>アクセス権限がありません。</p>";
    require_once 'includes/footer.php';
    exit;
}
?>

<h2>【管理者】全投稿プラン管理</h2>
<!-- TODO: 開発メインB プラン一覧・公開ステータス強制変更処理の実装を行うこと -->

<?php require_once 'includes/footer.php'; ?>
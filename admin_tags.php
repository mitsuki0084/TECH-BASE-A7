<?php
/**
 * 担当: 開発メインB（バックエンド logic）、負担軽めD（UI/フォーム作成）
 * 画面名: 観光エリアタグ管理画面
 * 
 * 【実装仕様】
 * 1. 管理者権限確認 (`$_SESSION['role'] === 1`)。未権限者はアクセス拒否。
 * 2. 現在登録されている `tags` 一覧を表示。
 * 3. 新規タグ追加用の Simple な入力フォーム (負担軽めD 担当)。
 * 4. タグの削除機能 (関連するプランの `tag_id` は SET NULL となるよう処理)。
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

<h2>【管理者】エリアタグ管理</h2>
<!-- TODO: 開発メインB・負担軽めD タグ一覧・追加フォーム・削除処理の実装を行うこと -->

<?php require_once 'includes/footer.php'; ?>
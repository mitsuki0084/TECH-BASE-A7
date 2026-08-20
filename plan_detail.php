<?php
/**
 * 担当: みつき（API連携・バックエンド logic）、負担軽めC（UI・天気カード表示）
 * 画面名: プラン詳細・スケジュール調整画面
 * 
 * 【実装仕様】
 * 1. GETパラメータ `id` から該当プラン情報と関連する `schedules` レコードを取得（sort_order昇順）。
 * 2. 観光地の新規追加フォーム（日程番号、スポット名、時間帯、メモ）。
 * 3. 観光地リスト表示:
 *    - HTML構造に `<ul id="sortable-schedule">` と `<li class="sortable-item" data-id="スケジュールID">` を使用。
 *    - JS (main.js) がドラッグ＆ドロップを有効化し、update_order.php へ非同期送信。
 * 4. 天気情報表示用領域:
 *    - `<div id="weather-info" data-area="目的地名"></div>` を配置。
 *    - バックエンド側で天気API連携モジュールをコール、またはJSで描画。
 */

require_once 'config/db.php';
require_once 'includes/header.php';

// スケジュール追加POST処理（例）
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_schedule') {
    // スケジュール追加処理の実装箇所
}
?>

<h2>プラン詳細・スケジュール調整</h2>

<!-- 天気情報表示エリア（負担軽めC / あなた 担当） -->
<div id="weather-info" class="card" data-area="Tokyo">
    <h3>目的地の天気予報</h3>
    <p>天気情報を読み込み中...</p>
</div>

<!-- スケジュールリスト（D&D並び替え対象） -->
<div class="card">
    <h3>スケジュール一覧（ドラッグ＆ドロップで並び替え可能）</h3>
    <ul id="sortable-schedule" class="sortable-list">
        <!-- スケジュールデータの繰り返し表示箇所 -->
    </ul>
</div>

<?php require_once 'includes/footer.php'; ?>
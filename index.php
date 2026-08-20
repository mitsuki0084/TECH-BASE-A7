<?php
/**
 * 担当: 開発メインB
 * 画面名: 公開プラン一覧・検索画面（トップページ）
 * 
 * 【実装仕様】
 * 1. 検索フォーム（エリアタグ選択、キーワード入力）。
 * 2. `plans` テーブルから `status = 1`（公開状態）のデータのみ取得してカード表示。
 * 3. 各プランの「タイトル」「作成者名」「エリアタグ」「日程」を表示。
 * 4. クリック時にプラン詳細画面 (plan_detail.php?id=X) へ遷移。
 */

require_once 'config/db.php';
require_once 'includes/header.php';
?>

<h2>みんなの旅行プラン</h2>
<!-- TODO: 開発メインB 検索フォームおよび公開プラン一覧表示処理の実装を行うこと -->

<?php require_once 'includes/footer.php'; ?>
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

session_start();
require_once 'config/db.php';
require_once 'includes/header.php';

$keyword=$_GET['keyword'] ?? '';
$tag_id=$_GET['tag_id'] ?? '';

$tagStmt = $pdo->query("SELECT id, name FROM tags");
$tags=$tagStmt->fetchAll();


?>

<h2>みんなの旅行プラン</h2>
<!-- TODO: 開発メインB 検索フォームおよび公開プラン一覧表示処理の実装を行うこと -->

<?php require_once 'includes/footer.php'; ?>

<!--検索フォーム-->
<form method="GET" action="">
    <input type="text" name="keyword" placeholder="キーワードを入力" value="<?= htmlspecialchars($keyword) ?>">
    
    <select name="tag_id">
        <option value="">エリアを選択</option>
        <?php foreach ($tags as $tag): ?>
            <option value="<?= $tag['id'] ?>" <?= ($tag_id == $tag['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($tag['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <button type="submit">検索</button>
</form>

<!--カード表示-->
<strong>検索結果</strong>

<div class="card_list">
    <?php foreach ($plans as $plan): ?>
       <a class="card" href="plan_detail.php?id=<?= $plan['id'] ?>"> 
        
            <h3><?= htmlspecialchars($plan['title']) ?></h3>
            <p>作成者: <?= htmlspecialchars($plan['author_name']) ?></p>
            <p>場所: <?= htmlspecialchars($plan['tag_name']) ?></p>
            <p>日程: <?= htmlspecialchars($plan['start_date']) ?> ～ <?= htmlspecialchars($plan['end_date']) ?></p>
        </a>
    <?php endforeach; ?>
</div>


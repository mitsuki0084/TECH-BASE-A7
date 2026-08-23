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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST["name"];
    $email = $_POST["email"];
    $password = $_POST["password"];

    //パスワードをハッシュ化
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    //usersテーブルへ登録
    $sql = "INSERT INTO users (username, email, password, role)
            VALUES (:username, :email, :password, 0)";
    
    $stmt = $pdo->prepare($sql);

    $stmt->bindParam(':username', $name, PDO::PARAM_STR);
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->bindParam(':password',$hashed_password, PDO::PARAM_STR);

    $stmt->execute();

    header('Location: login.php');
    exit;

}
require_once 'includes/header.php';
?>

<h2>ユーザー新規登録</h2>
<!-- TODO: 開発メインA 処理・フォームの実装を行うこと -->
<form action="" method="post">
    <input type="text" name="name" placeholder="名前">
    <input type="email" name="email" placeholder="メールアドレス">
    <input type="password" name="password" placeholder="パスワード">
    <input type="submit" value="登録">
</form>


<?php require_once 'includes/footer.php'; ?>
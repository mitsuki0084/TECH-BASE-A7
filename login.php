<?php
/**
 * 担当: 開発メインA
 * 画面名: ログイン処理
 * 
 * 【実装仕様】
 * 1. GETリクエスト時: ログインフォームを表示。
 * 2. POSTリクエスト時:
 *    - メールアドレスをもとに `users` テーブルからユーザーを取得。
 *    - `password_verify()` を使用してパスワードを判定。
 *    - 認証成功時:
 *      - `session_regenerate_id(true)` でセッションIDを再発行。
 *      - $_SESSION['user_id']、$_SESSION['username']、$_SESSION['role'] を保持。
 *      - index.php または mypage.php へリダイレクト。
 *    - 認証失敗時: エラーメッセージを表示。
 */

session_start();

require_once 'config/db.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = $_POST["email"];
    $password = $_POST["password"];

    // メールアドレスからユーザーを検索
    $sql = "SELECT * FROM users WHERE email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->execute();

    $user = $stmt->fetch();

    if ($user && password_verify($password, $user["password"])) {
        session_regenerate_id(true);

        $_SESSION["user_id"] = $user["id"];
        $_SESSION["username"] = $user["username"];
        $_SESSION["role"] = $user["role"];

        header("Location: index.php");
        exit;

    } else {
        $error ="  メールアドレスまたはパスワードが違います。";

    }

}


require_once 'includes/header.php';
?>

<h2>ログイン</h2>
<!-- TODO: 開発メインA 処理・フォームの実装を行うこと -->
<?php
if (!empty($error)) {
    echo $error . "<br />";
}
?>

<form action="" method="post">
    <input type="email" name="email" placeholder="メールアドレス">
    <input type="password" name="password" placeholder="パスワード">
    <input type="submit" value="ログイン">
</form>

<?php require_once 'includes/footer.php'; ?>
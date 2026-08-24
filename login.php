<?php
/**
 * 担当: 開発メインA
 * 画面名: ログイン処理
 *
 * 【②対応】管理者専用ログイン画面(admin_login.php)は廃止し、本画面に統合した。
 *          認証成功後、role に応じてリダイレクト先を自動で振り分ける。
 * 【③対応】$_SESSION['role'] は必ず (int) キャストして格納する。
 *          管理画面側の厳密比較(!==)で誤って弾かれることを防ぐための必須ルール。
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

        $_SESSION["user_id"] = (int)$user["id"];
        $_SESSION["username"] = $user["username"];
        $_SESSION["role"] = (int)$user["role"];

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
<?php if ($error !== ''): ?>
    <p class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>

<form method="post" action="login.php" class="form">
    <input type="email" name="email" placeholder="メールアドレス">
    <input type="password" name="password" placeholder="パスワード">
    <input type="submit" value="ログイン">
</form>


<p><a href="register.php">アカウントをお持ちでない方はこちら</a></p>

<?php require_once 'includes/footer.php'; ?>

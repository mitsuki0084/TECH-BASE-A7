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

require_once 'config/db.php';

$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $errorMessage = 'メールアドレスとパスワードを入力してください。';
    } else {
        $stmt = $pdo->prepare(
            'SELECT id, username, email, password, role
             FROM users
             WHERE email = ?'
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // アカウントの有無を推測させないよう、常に同じ文言でエラーを返す
        if (!$user || !password_verify($password, $user['password'])) {
            $errorMessage = 'メールアドレスまたはパスワードが正しくありません。';
        } else {
            // セッション固定化攻撃対策
            session_regenerate_id(true);

            $_SESSION['user_id']  = (int)$user['id'];
            $_SESSION['username'] = $user['username'];
            // 【③対応】必ず (int) キャストする。DBの取得値が文字列でも安全に比較できるようにする。
            $_SESSION['role']     = (int)$user['role'];

            // 【②対応】role に応じて自動振り分け。管理者専用の別ログイン画面は不要。
            if ($_SESSION['role'] === 1) {
                header('Location: admin_plans.php');
            } else {
                header('Location: index.php');
            }
            exit;
        }
    }
}

require_once 'includes/header.php';
?>

<h2>ログイン</h2>

<?php if ($errorMessage !== ''): ?>
    <p class="error"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>

<form method="post" action="login.php" class="form">
    <div>
        <label for="email">メールアドレス</label>
        <input type="email" id="email" name="email" required
               value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
    </div>

    <div>
        <label for="password">パスワード</label>
        <input type="password" id="password" name="password" required>
    </div>

    <button type="submit">ログイン</button>
</form>

<p><a href="register.php">アカウントをお持ちでない方はこちら</a></p>

<?php require_once 'includes/footer.php'; ?>

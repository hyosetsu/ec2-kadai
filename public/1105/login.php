<?php
// login.php - password_verify() を使用し、PHP標準セッション + Redis保存対応

// DB接続
$dbh = new PDO('mysql:host=mysql;dbname=example_db', 'root', '');

if (!empty($_POST['email']) && !empty($_POST['password'])) {
    // email から会員情報を取得
    $select_sth = $dbh->prepare("SELECT * FROM users WHERE email = :email ORDER BY id DESC LIMIT 1");
    $select_sth->execute([':email' => $_POST['email']]);
    $user = $select_sth->fetch();

    if (empty($user)) {
        header("HTTP/1.1 303 See Other");
        header("Location: ./login.php?error=1");
        exit;
    }

    // ✅ PHP標準の password_verify で照合
    if (!password_verify($_POST['password'], $user['password'])) {
        header("HTTP/1.1 303 See Other");
        header("Location: ./login.php?error=1");
        exit;
    }

    // ✅ PHP標準セッション開始（Redis保存）
    session_start();
    $_SESSION['login_user_id'] = $user['id'];

    header("HTTP/1.1 303 See Other");
    header("Location: ./login_finish.php");
    exit;
}
?>

<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<title>ログイン</title>
</head>
<body>
<h1>ログイン</h1>
<form method="POST">
  <label>メールアドレス: <input type="email" name="email" required></label><br>
  <label>パスワード: <input type="password" name="password" minlength="6" required></label><br>
  <button type="submit">決定</button>
</form>

<?php if(!empty($_GET['error'])): ?>
<div style="color: red;">メールアドレスかパスワードが間違っています。</div>
<?php endif; ?>
</body>
</html>


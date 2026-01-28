<?php
require_once __DIR__ . '/init_session.php'; // ← ここを一番上に追加
// login.php - password_verify() を使用し、PHP標準セッション + Redis保存対応

// DB接続
$dbh = new PDO('mysql:host=mysql;dbname=kadai_db', 'root', '');

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
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<link rel="stylesheet" href="/style.css">
<title>ログイン</title>
</head>
<body>
<?php $active = 'timeline'; include __DIR__ . '/_nav.php'; ?>
<div class="container">
<hr>
初めての人は<a href="/signup.php">会員登録</a>しましょう。
<hr>
<h1>ログイン</h1>
<form method="POST">
  <label>メールアドレス: <input type="email" name="email" required></label><br>
  <label>パスワード: <input type="password" name="password" minlength="6" required></label><br>
  <button type="submit">決定</button>
</form>

<?php if(!empty($_GET['logged_out'])): ?>
  <div style="color: green;">ログアウトしました。</div>
<?php endif; ?>

<?php if(!empty($_GET['error'])): ?>
<div style="color: red;">メールアドレスかパスワードが間違っています。</div>
<?php endif; ?>
</body>
</div>
</html>


<?php
require_once __DIR__ . '/init_session.php';
// signup.php - password_hash() を使用

// DB接続
$dbh = new PDO('mysql:host=mysql;dbname=kadai_db', 'root', '');

if (!empty($_POST['name']) && !empty($_POST['email']) && !empty($_POST['password'])) {
  // 同じメールアドレスの存在確認
  $select_sth = $dbh->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
  $select_sth->execute([':email' => $_POST['email']]);
  if ($select_sth->fetchColumn() > 0) {
    header("HTTP/1.1 303 See Other");
    header("Location: ./signup.php?duplicate_email=1");
    exit;
  }

  // ✅ PHP標準の安全なハッシュ化
  $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);

  // DB登録
  $insert_sth = $dbh->prepare("INSERT INTO users (name, email, password) VALUES (:name, :email, :password)");
  $insert_sth->execute([
    ':name' => $_POST['name'],
    ':email' => $_POST['email'],
    ':password' => $hashed_password,
  ]);

  header("HTTP/1.1 303 See Other");
  header("Location: ./signup_finish.php");
  exit;
}
?>

<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<link rel="stylesheet" href="/style.css">
<title>会員登録</title>
</head>
<body>
<?php $active = 'timeline'; include __DIR__ . '/_nav.php'; ?>
<div class="container">
<h1>会員登録</h1>
<hr>
会員登録済の人は<a href="/login.php">ログイン</a>しましょう。
<hr>
<form method="POST">
  <label>名前: <input type="text" name="name" required></label><br>
  <label>メールアドレス: <input type="email" name="email" required></label><br>
  <label>パスワード: <input type="password" name="password" minlength="6" required autocomplete="new-password"></label><br>
  <button type="submit">決定</button>
</form>

<?php if(!empty($_GET['duplicate_email'])): ?>
<div style="color: red;">入力されたメールアドレスは既に使われています。</div>
<?php endif; ?>
</body>
</div>
</html>


<?php
// signup.php - ソルト付きストレッチング(100000回)でパスワードを保存

// DBに接続
$dbh = new PDO('mysql:host=mysql;dbname=example_db', 'root', '');

// ストレッチング関数
function stretch_hash(string $password, string $salt, int $iterations = 100000): string {
    // 初回ハッシュ
    $h = hash('sha256', $password . $salt);
    // 残りを繰り返す（合計 iterations 回）
    for ($i = 1; $i < $iterations; $i++) {
        $h = hash('sha256', $h . $salt);
    }
    return $h; // hex文字列(64)
}

if (!empty($_POST['name']) && !empty($_POST['email']) && !empty($_POST['password'])) {
  // 既に同じメールアドレスで登録された会員が存在しないか確認
  $select_sth = $dbh->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
  $select_sth->execute([':email' => $_POST['email']]);
  if ($select_sth->fetchColumn() > 0) {
    header("HTTP/1.1 303 See Other");
    header("Location: ./signup.php?duplicate_email=1");
    return;
  }

  // ソルト生成（16バイト -> hex 32文字）
  $salt = bin2hex(random_bytes(16));

  // ストレッチング（100,000回）
  $hashed = stretch_hash($_POST['password'], $salt, 100000);

  // DBに保存： hashed(64) + salt(32)
  $store_value = $hashed . $salt;

  $insert_sth = $dbh->prepare("INSERT INTO users (name, email, password) VALUES (:name, :email, :password)");
  $insert_sth->execute([
    ':name' => $_POST['name'],
    ':email' => $_POST['email'],
    ':password' => $store_value,
  ]);

  header("HTTP/1.1 303 See Other");
  header("Location: ./signup_finish.php");
  return;
}
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<title>会員登録</title>
</head>
<body>
<h1>会員登録</h1>
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
</html>


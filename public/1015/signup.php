<?php
// DBに接続
$dbh = new PDO('mysql:host=mysql;dbname=example_db', 'root', '');

// すでにPOSTでデータが送られている場合のみ処理
if (!empty($_POST['name']) && !empty($_POST['email']) && !empty($_POST['password'])) {

  // まず同じメールアドレスがすでに登録されていないか確認
  $check_sth = $dbh->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
  $check_sth->execute([':email' => $_POST['email']]);
  $email_exists = $check_sth->fetchColumn();

  if ($email_exists > 0) {
    // 既に登録済みならエラーメッセージ付きでリロード
    header("HTTP/1.1 303 See Other");
    header("Location: ./signup.php?error=1");
    return;
  }

  // まだ登録されていなければinsert実行
  $insert_sth = $dbh->prepare("INSERT INTO users (name, email, password) VALUES (:name, :email, :password)");
  $insert_sth->execute([
    ':name' => $_POST['name'],
    ':email' => $_POST['email'],
    ':password' => hash('sha256', $_POST['password']),
  ]);

  // 処理が終わったら完了画面にリダイレクト
  header("HTTP/1.1 303 See Other");
  header("Location: ./signup_finish.php");
  return;
}
?>

<h1>会員登録</h1>

<!-- 登録フォーム -->
<form method="POST">
  <label>
    名前:
    <input type="text" name="name" required>
  </label>
  <br>
  <label>
    メールアドレス:
    <input type="email" name="email" required>
  </label>
  <br>
  <label>
    パスワード:
    <input type="password" name="password" minlength="6" required autocomplete="new-password">
  </label>
  <br>
  <button type="submit">決定</button>
</form>

<?php if (!empty($_GET['error'])): ?>
<div style="color: red; margin-top: 10px;">
  既にこのメールアドレスは登録されています。
</div>
<?php endif; ?>


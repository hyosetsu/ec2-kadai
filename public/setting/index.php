<?php
// public/setting/index.php
session_start();
if (empty($_SESSION['login_user_id'])) {
  header("HTTP/1.1 302 Found");
  header("Location: /login.php");
  exit;
}

// DBに接続
$dbh = new PDO('mysql:host=mysql;dbname=example_db', 'root', '');
// ログイン中ユーザー情報取得
$select_sth = $dbh->prepare("SELECT * FROM users WHERE id = :id");
$select_sth->execute([':id' => $_SESSION['login_user_id']]);
$user = $select_sth->fetch();
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>設定一覧</title>
<link rel="stylesheet" href="/style.css">
</head>
<body>
  <a href="/timeline.php">タイムラインに戻る</a>
  <h1>設定画面</h1>

  <p>現在の設定</p>
  <dl>
    <dt>ID</dt>
    <dd><?= htmlspecialchars($user['id'], ENT_QUOTES, 'UTF-8') ?></dd>
    <dt>メールアドレス</dt>
    <dd><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></dd>
    <dt>名前</dt>
    <dd><?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?></dd>
  </dl>

  <ul>
    <li><a href="./icon.php">アイコン設定</a></li>
    <li><a href="./intro.php">自己紹介文設定</a></li>
    <li><a href="./cover.php">カバー画像設定</a></li>
     <li><a href="./birthdate.php">生年月日設定</a></li>
    <!-- 必要に応じて他の設定へのリンクを追加 -->
  </ul>
</body>
</html>


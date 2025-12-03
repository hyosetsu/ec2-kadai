<?php
require_once __DIR__ . '/init_session.php';

$user_id = null;
if (!empty($_GET['user_id'])) {
  $user_id = $_GET['user_id']; // 指定されたユーザー
} elseif (!empty($_SESSION['login_user_id'])) {
  $user_id = $_SESSION['login_user_id']; // ログイン中のユーザー
}

if (empty($user_id)) {
  header("HTTP/1.1 302 Found");
  header("Location: /login.php");
  exit;
}

// DB接続
$dbh = new PDO('mysql:host=mysql;dbname=example_db', 'root', '');

// 対象のユーザー取得
$select_sth = $dbh->prepare("SELECT * FROM users WHERE id = :id");
$select_sth->execute([':id' => $user_id]);
$user = $select_sth->fetch();

if (empty($user)) {
  header("HTTP/1.1 404 Not Found");
  print("そのようなユーザーIDの会員情報は存在しません");
  exit;
}
?>

<h1><?= htmlspecialchars($user['name']) ?> さん のプロフィール</h1>
<div>
  <?php if(empty($user['icon_filename'])): ?>
    現在アイコン未設定
  <?php else: ?>
    <img src="/image/<?= htmlspecialchars($user['icon_filename']) ?>"
      style="height: 5em; width: 5em; border-radius: 50%; object-fit: cover;">
  <?php endif; ?>
</div>

<h3>自己紹介</h3>
<div style="white-space: pre-wrap; border: 1px solid #ccc; padding: 1em; width: 400px;">
  <?php if(empty($user['intro'])): ?>
    <span style="color: gray;">自己紹介文がまだ設定されていません。</span>
  <?php else: ?>
    <?= nl2br(htmlspecialchars($user['intro'], ENT_QUOTES, 'UTF-8')) ?>
  <?php endif; ?>
</div>


<?php
require_once __DIR__ . '/init_session.php';
$dbh = new PDO('mysql:host=mysql;dbname=example_db', 'root', '');

if (empty($_SESSION['login_user_id'])) {
    header("HTTP/1.1 303 See Other");
    header("Location: ./login.php");
    exit;
}

$select_sth = $dbh->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
$select_sth->execute([':id' => $_SESSION['login_user_id']]);
$user = $select_sth->fetch();

if (!$user) {
    echo "ユーザー情報が見つかりません。";
    exit;
}
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>ログイン完了</title>
</head>
<body>
<h1>ログイン完了</h1>

<p><?php echo htmlspecialchars($user['name']); ?> さん、ログインしました。</p>

<hr>
<a href="/timeline.php">タイムラインはこちら</a>
<a href="/profile.php">プロフィールはこちら</a>
<a href="/setting/index.php">設定画面はこちら</a>

<hr>

<h2>ユーザー情報</h2>
<ul>
  <li><strong>ユーザーID：</strong> <?php echo htmlspecialchars($user['id']); ?></li>
  <li><strong>名前：</strong> <?php echo htmlspecialchars($user['name']); ?></li>
  <li><strong>メールアドレス：</strong> <?php echo htmlspecialchars($user['email']); ?></li>
  <li><strong>登録日時：</strong> <?php echo htmlspecialchars($user['created_at']); ?></li>
  <?php if (!empty($user['icon_filename'])): ?>
    <li>
      <strong>アイコン：</strong><br>
      <img src="/image/<?php echo htmlspecialchars($user['icon_filename']); ?>" alt="アイコン画像" width="100">
    </li>
  <?php else: ?>
    <li><strong>アイコン：</strong> 未設定</li>
  <?php endif; ?>
</ul>

<hr>

<p><a href="profile_edit.php">プロフィールを編集する</a></p>
<p><a href="logout.php">ログアウト</a></p>

</body>
</html>


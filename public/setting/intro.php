<?php
session_start();
if (empty($_SESSION['login_user_id'])) {
  header("HTTP/1.1 302 Found");
  header("Location: /login.php");
  exit;
}

// DB接続
$dbh = new PDO('mysql:host=mysql;dbname=kadai_db', 'root', '');

// 自分の情報取得
$select_sth = $dbh->prepare("SELECT * FROM users WHERE id = :id");
$select_sth->execute([':id' => $_SESSION['login_user_id']]);
$user = $select_sth->fetch();

if (!$user) {
  session_destroy();
  header("HTTP/1.1 302 Found");
  header("Location: /login.php");
  exit;
}

// POST時の処理
if (isset($_POST['intro'])) {
  $intro = trim($_POST['intro']);
  if (mb_strlen($intro) > 1000) {
    $error = "自己紹介文は1000文字以内で入力してください。";
  } else {
    $update_sth = $dbh->prepare("UPDATE users SET intro = :intro WHERE id = :id");
    $update_sth->execute([
      ':intro' => $intro,
      ':id' => $_SESSION['login_user_id']
    ]);
    $success = "自己紹介文を更新しました。";

    // 最新データ再取得
    $select_sth->execute([':id' => $_SESSION['login_user_id']]);
    $user = $select_sth->fetch();
  }
}
?>

<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <link rel="stylesheet" href="/../style.css">
  <title>自己紹介文設定</title>
</head>
<body>
<?php $active = 'timeline'; include __DIR__ . '/../_nav.php'; ?>
<div class="container">
<h1>自己紹介文設定</h1>

<a href="./index.php">設定一覧に戻る</a>
<p><a href="/login_finish.php">← 戻る</a></p>

<form method="POST">
  <label>自己紹介 (1000文字以内):</label><br>
  <textarea name="intro" rows="10" cols="60" maxlength="1000" style="white-space: pre-wrap;"><?=
    htmlspecialchars($user['intro'] ?? '', ENT_QUOTES, 'UTF-8');
  ?></textarea><br><br>
  <button type="submit">更新する</button>
</form>

<?php if (!empty($error)): ?>
  <p style="color:red;"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<?php if (!empty($success)): ?>
  <p style="color:green;"><?= htmlspecialchars($success) ?></p>
<?php endif; ?>
</body>
</div>
</html>


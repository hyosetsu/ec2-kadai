<?php
session_start();
$dbh = new PDO('mysql:host=mysql;dbname=example_db', 'root', '');

if (empty($_SESSION['login_user_id'])) {
    header("HTTP/1.1 303 See Other");
    header("Location: ./login.php");
    exit;
}

$select_sth = $dbh->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
$select_sth->execute([':id' => $_SESSION['login_user_id']]);
$user = $select_sth->fetch();
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<title>ログイン完了</title>
</head>
<body>
<h1>ログイン完了</h1>
<p><?php echo htmlspecialchars($user['name']); ?> さん、ログインしました。</p>

<p><a href="profile_edit.php">プロフィールを編集する</a></p>
<p><a href="logout.php">ログアウト</a></p>
</body>
</html>


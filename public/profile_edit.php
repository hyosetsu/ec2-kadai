<?php
require_once __DIR__ . '/init_session.php';
// profile_edit.php

// ログインチェック
if (empty($_SESSION['login_user_id'])) {
    header("HTTP/1.1 303 See Other");
    header("Location: ./login.php");
    exit;
}

// DB接続
$dbh = new PDO('mysql:host=mysql;dbname=example_db', 'root', '');

// 現在のユーザー情報を取得
$select_sth = $dbh->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
$select_sth->execute([':id' => $_SESSION['login_user_id']]);
$user = $select_sth->fetch();

if (!$user) {
    // 万が一データが消えていた場合
    session_destroy();
    header("HTTP/1.1 303 See Other");
    header("Location: ./login.php");
    exit;
}

// POSTされた場合（名前更新処理）
if (!empty($_POST['name'])) {
    $new_name = trim($_POST['name']);

    if ($new_name === '') {
        $error = "名前を入力してください。";
    } else {
        $update_sth = $dbh->prepare("UPDATE users SET name = :name WHERE id = :id");
        $update_sth->execute([
            ':name' => $new_name,
            ':id' => $_SESSION['login_user_id'],
        ]);

        $success = "プロフィールを更新しました。";
        // 再取得して最新表示
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
<title>プロフィール編集</title>
</head>
<body>
<h1>プロフィール編集</h1>

<p><a href="login_finish.php">← 戻る</a></p>

<form method="POST">
  <label>名前: 
    <input type="text" name="name" value="<?php echo htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'); ?>" required>
  </label><br><br>
  <button type="submit">更新する</button>
</form>

<?php if (!empty($error)): ?>
  <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
<?php endif; ?>

<?php if (!empty($success)): ?>
  <p style="color:green;"><?php echo htmlspecialchars($success); ?></p>
<?php endif; ?>

</body>
</html>


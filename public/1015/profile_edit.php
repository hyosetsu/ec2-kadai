<?php
// セッションIDの取得（なければ新規作成＆設定）
$session_cookie_name = 'session_id';
$session_id = $_COOKIE[$session_cookie_name] ?? base64_encode(random_bytes(64));
if (!isset($_COOKIE[$session_cookie_name])) {
    setcookie($session_cookie_name, $session_id);
}

// Redisに接続
$redis = new Redis();
$redis->connect('redis', 6379);

// Redisキー
$redis_session_key = "session-" . $session_id;

// セッションデータを取得
$session_values = $redis->exists($redis_session_key)
    ? json_decode($redis->get($redis_session_key), true)
    : [];

// 未ログインならログインページへリダイレクト
if (empty($session_values['login_user_id'])) {
    header("HTTP/1.1 302 Found");
    header("Location: ./login.php");
    return;
}

// DB接続
$dbh = new PDO('mysql:host=mysql;dbname=example_db', 'root', '');

// ログイン中ユーザーの情報を取得
$select_sth = $dbh->prepare("SELECT * FROM users WHERE id = :id");
$select_sth->execute([':id' => $session_values['login_user_id']]);
$user = $select_sth->fetch();

// POST送信があれば、名前を更新
if (!empty($_POST['name'])) {
    $update_sth = $dbh->prepare("UPDATE users SET name = :name WHERE id = :id");
    $update_sth->execute([
        ':name' => $_POST['name'],
        ':id' => $user['id'],
    ]);

    // 更新後、完了ページにリダイレクト
    header("HTTP/1.1 303 See Other");
    header("Location: ./profile_edit_finish.php");
    return;
}
?>

<h1>プロフィール編集</h1>

<form method="POST">
  <label>
    名前:
    <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
  </label>
  <br>
  <button type="submit">変更を保存</button>
</form>

<hr>
<a href="./login_finish.php">ログイン完了画面へ戻る</a>


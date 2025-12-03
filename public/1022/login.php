<?php
// login.php - 保存された salt を取り出して同じストレッチング回数でハッシュし照合

// DBに接続
$dbh = new PDO('mysql:host=mysql;dbname=example_db', 'root', '');

// ストレッチング関数（signup と同じ）
function stretch_hash(string $password, string $salt, int $iterations = 100000): string {
    $h = hash('sha256', $password . $salt);
    for ($i = 1; $i < $iterations; $i++) {
        $h = hash('sha256', $h . $salt);
    }
    return $h;
}

if (!empty($_POST['email']) && !empty($_POST['password'])) {
  // email から会員情報を取得
  $select_sth = $dbh->prepare("SELECT * FROM users WHERE email = :email ORDER BY id DESC LIMIT 1");
  $select_sth->execute([':email' => $_POST['email']]);
  $user = $select_sth->fetch();

  if (empty($user)) {
    header("HTTP/1.1 303 See Other");
    header("Location: ./login.php?error=1");
    return;
  }

  // 保存値をハッシュ部(前64)とソルト部(後32)に分解
  $stored_value = $user['password'] ?? '';
  if (strlen($stored_value) < 96) { // 64 + 32 = 96
      // 保存フォーマットが期待と違う（エラー扱い）
      header("HTTP/1.1 303 See Other");
      header("Location: ./login.php?error=1");
      return;
  }
  $stored_hash = substr($stored_value, 0, 64);   // sha256 hex = 64
  $salt = substr($stored_value, -32);            // salt hex = 32

  // 入力パスワードを同じストレッチングでハッシュ化
  $input_hash = stretch_hash($_POST['password'], $salt, 100000);

  // 定数時間比較で照合
  if (!hash_equals($stored_hash, $input_hash)) {
    header("HTTP/1.1 303 See Other");
    header("Location: ./login.php?error=1");
    return;
  }

  // 以下は既存の Redis セッション処理（省略せずそのまま使う）
  $session_cookie_name = 'session_id';
  $session_id = $_COOKIE[$session_cookie_name] ?? base64_encode(random_bytes(64));
  if (!isset($_COOKIE[$session_cookie_name])) {
    setcookie($session_cookie_name, $session_id);
  }
  $redis = new Redis();
  $redis->connect('redis', 6379);
  $redis_session_key = "session-" . $session_id;
  $session_values = $redis->exists($redis_session_key)
    ? json_decode($redis->get($redis_session_key), true)
    : [];
  $session_values["login_user_id"] = $user['id'];
  $redis->set($redis_session_key, json_encode($session_values));

  header("HTTP/1.1 303 See Other");
  header("Location: ./login_finish.php");
  return;
}
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<title>ログイン</title>
</head>
<body>
<h1>ログイン</h1>
<form method="POST">
  <label>メールアドレス: <input type="email" name="email" required></label><br>
  <label>パスワード: <input type="password" name="password" minlength="6" required></label><br>
  <button type="submit">決定</button>
</form>

<?php if(!empty($_GET['error'])): ?>
<div style="color: red;">メールアドレスかパスワードが間違っています。</div>
<?php endif; ?>
</body>
</html>


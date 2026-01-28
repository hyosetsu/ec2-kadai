<?php
require_once __DIR__ . '/init_session.php';

// ログインしてなければログインへ
if (empty($_SESSION['login_user_id'])) {
  header("HTTP/1.1 303 See Other");
  header("Location: /login.php");
  exit;
}

$active = ''; // どのタブでもない
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title>ログアウト</title>
  <link rel="stylesheet" href="/style.css">
</head>
<body>
<?php include __DIR__ . '/_nav.php'; ?>

<main class="container">
  <h1>ログアウト</h1>
  <p>ログアウトしますか？</p>

  <div style="display:flex; gap:0.6em; flex-wrap:wrap;">
    <form method="POST" action="/logout_do.php">
      <button type="submit" class="btn danger">ログアウトする</button>
    </form>

    <a class="btn" href="/timeline.php">キャンセル</a>
  </div>
</main>

</body>
</html>

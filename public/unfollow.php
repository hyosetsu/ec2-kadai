<?php
// public/unfollow.php
session_start();

// ログイン確認
if (empty($_SESSION['login_user_id'])) {
  header("HTTP/1.1 302 Found");
  header("Location: /login.php");
  exit;
}

$dbh = new PDO('mysql:host=mysql;dbname=kadai_db', 'root', '');
$me = (int)$_SESSION['login_user_id'];

// POSTで削除実行
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['followee_user_id'])) {
  $followee_id = (int)$_POST['followee_user_id'];

  // 存在確認（防御）
  $select = $dbh->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
  $select->execute([':id' => $followee_id]);
  $target = $select->fetch();
  if (empty($target)) {
    header("HTTP/1.1 404 Not Found");
    echo "そのようなユーザーは存在しません";
    exit;
  }

  // フォロー関係があるか確認
  $check = $dbh->prepare(
    "SELECT * FROM user_relationships
     WHERE follower_user_id = :follower AND followee_user_id = :followee LIMIT 1"
  );
  $check->execute([
    ':follower' => $me,
    ':followee' => $followee_id,
  ]);
  $rel = $check->fetch();

  if (empty($rel)) {
    // 既に存在しないならリダイレクト（冪等）
    header("HTTP/1.1 303 See Other");
    header("Location: /follow_list.php");
    exit;
  }

  // 削除
  $del = $dbh->prepare(
    "DELETE FROM user_relationships
     WHERE follower_user_id = :follower AND followee_user_id = :followee"
  );
  $del->execute([
    ':follower' => $me,
    ':followee' => $followee_id,
  ]);

  // 削除後はプロフィールに戻す（元ページへ戻したい場合は referer を使っても良い）
  header("HTTP/1.1 303 See Other");
  header("Location: /profile.php?user_id=" . $followee_id . "&unfollowed=1");
  exit;
}

// GET は確認画面を表示
$followee_id = isset($_GET['followee_user_id']) ? (int)$_GET['followee_user_id'] : 0;
if ($followee_id <= 0) {
  header("HTTP/1.1 400 Bad Request");
  echo "無効なリクエストです";
  exit;
}

// フォロー対象取得
$select = $dbh->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
$select->execute([':id' => $followee_id]);
$followee = $select->fetch();
if (empty($followee)) {
  header("HTTP/1.1 404 Not Found");
  echo "そのようなユーザーは存在しません";
  exit;
}

// フォロー関係があるかチェック（無ければ案内）
$check = $dbh->prepare(
  "SELECT * FROM user_relationships WHERE follower_user_id = :follower AND followee_user_id = :followee LIMIT 1"
);
$check->execute([':follower' => $me, ':followee' => $followee_id]);
$relationship = $check->fetch();
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<link rel="stylesheet" href="/style.css">
<title>フォロー解除の確認</title>
</head>
<body>
<?php $active = 'timeline'; include __DIR__ . '/_nav.php'; ?>
<div class="container">
  <h1>フォロー解除の確認</h1>

  <p>
    <?php echo htmlspecialchars($followee['name'], ENT_QUOTES, 'UTF-8'); ?> さんのフォローを解除しますか？
  </p>

  <?php if (empty($relationship)): ?>
    <p>現在このユーザーはフォローされていません。</p>
    <p><a href="/follow_list.php">フォロー一覧へ戻る</a> / <a href="/profile.php?user_id=<?php echo $followee_id; ?>">プロフィールへ戻る</a></p>
  <?php else: ?>
    <form method="POST" action="/unfollow.php">
      <input type="hidden" name="followee_user_id" value="<?php echo $followee_id; ?>">
      <button type="submit" style="background:#ddd;padding:0.5em 1em;border-radius:4px;border:1px solid #bbb;">フォロー解除する</button>
      <a href="/profile.php?user_id=<?php echo $followee_id; ?>" style="margin-left:1em;">キャンセル</a>
    </form>
  <?php endif; ?>
</body>
</div>
</html>


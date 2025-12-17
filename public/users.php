<?php
session_start();
$dbh = new PDO('mysql:host=mysql;dbname=example_db', 'root', '');

// ログインしてなければログイン画面へ
if (empty($_SESSION['login_user_id'])) {
  header("HTTP/1.1 302 Found");
  header("Location: /login.php");
  exit;
}

// 自分がフォローしているユーザーID一覧を取得
$followed_sth = $dbh->prepare(
  "SELECT followee_user_id FROM user_relationships WHERE follower_user_id = :me"
);
$followed_sth->execute([':me' => $_SESSION['login_user_id']]);
$followed_ids = $followed_sth->fetchAll(PDO::FETCH_COLUMN); // [1, 2, 3...] みたいな配列

// 検索キーワード（部分一致）
$q = $_GET['q'] ?? '';
$q = trim($q);

// 会員データを取得
if ($q !== '') {
  $select_sth = $dbh->prepare('SELECT * FROM users WHERE name LIKE :q ORDER BY id DESC');
  $select_sth->execute([':q' => '%' . $q . '%']);
} else {
  $select_sth = $dbh->prepare('SELECT * FROM users ORDER BY id DESC');
  $select_sth->execute();
}
?>

<body>
  <h1>会員一覧</h1>

  <div style="margin-bottom: 1em;">
    <a href="/setting/index.php">設定画面</a>
    /
    <a href="/timeline.php">タイムライン</a>
  </div>

  <form method="GET" style="margin: 1em 0;">
    <input type="text" name="q" placeholder="名前で検索（部分一致）"
      value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>">
    <button type="submit">検索</button>

    <?php if ($q !== ''): ?>
      <a href="/users.php" style="margin-left: 0.5em;">クリア</a>
    <?php endif; ?>
  </form>

  <?php foreach($select_sth as $user): ?>
    <div style="display: flex; justify-content: start; align-items: center; padding: 1em 2em;">
      <div style="display:flex; align-items:center;">
        <?php if(empty($user['icon_filename'])): ?>
          <div style="height:2em; width:2em;"></div>
        <?php else: ?>
          <img src="/image/<?= htmlspecialchars($user['icon_filename']) ?>"
            style="height:2em; width:2em; border-radius:50%; object-fit:cover;">
        <?php endif; ?>

        <a href="/profile.php?user_id=<?= htmlspecialchars($user['id']) ?>" style="margin-left:1em;">
          <?= htmlspecialchars($user['name']) ?>
        </a>
      </div>

      <div>
        <?php if ((int)$user['id'] === (int)$_SESSION['login_user_id']): ?>
          <span style="color:gray;">あなた</span>
        <?php elseif (in_array((string)$user['id'], array_map('strval', $followed_ids), true)): ?>
          <span style="color:gray;">フォロー中</span>
          <!-- 解除導線も出したいならここに unfollow へのリンクを置ける -->
        <?php else: ?>
          <a href="/follow.php?followee_user_id=<?= htmlspecialchars($user['id']) ?>"
             style="padding:0.4em 0.8em; background:#1DA1F2; color:#fff; border-radius:6px; text-decoration:none;">
            フォローする
          </a>
        <?php endif; ?>
      </div>
    </div>
    <hr style="border: none; border-bottom: 1px solid gray;">
  <?php endforeach; ?>
</body>

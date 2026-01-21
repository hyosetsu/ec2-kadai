<?php
// public/followers.php
session_start();

// ログインしていなければログイン画面に飛ばす
if (empty($_SESSION['login_user_id'])) {
  header("HTTP/1.1 302 Found");
  header("Location: /login.php");
  exit;
}

// DB接続
$dbh = new PDO('mysql:host=mysql;dbname=example_db', 'root', '');

// 自分（ログインユーザー）がフォローされている（=自分がフォロイー）一覧を取得
$select_sth = $dbh->prepare(
  'SELECT ur.*, u.id AS follower_user_id, u.name AS follower_user_name, u.icon_filename AS follower_user_icon_filename'
  . ' FROM user_relationships ur'
  . ' INNER JOIN users u ON ur.follower_user_id = u.id'
  . ' WHERE ur.followee_user_id = :my_id'
  . ' ORDER BY ur.created_at DESC'
);
$select_sth->execute([
  ':my_id' => $_SESSION['login_user_id'],
]);

?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<link rel="stylesheet" href="/style.css">
<title>フォロワー一覧</title>
<style>
  body { font-family: sans-serif; padding: 1em; }
  ul { padding-left: 0; list-style: none; }
  li { margin-bottom: 0.8em; display:flex; align-items:center; gap:0.6em; border-bottom:1px solid #eee; padding-bottom:0.6em; }
  .icon { height: 3em; width: 3em; border-radius: 50%; object-fit: cover; }
  .name { font-weight: 600; }
  .meta { color: #666; font-size: 0.9em; }
  .actions { margin-left: auto; }
  a.button { display:inline-block; padding:0.35em 0.6em; border-radius:4px; border:1px solid #ccc; text-decoration:none; color:#333; background:#fff; }
</style>
</head>
<body>
<?php $active = 'timeline'; include __DIR__ . '/_nav.php'; ?>
<div class="container">
<h1>あなたのフォロワー</h1>

<?php if ($select_sth->rowCount() === 0): ?>
  <p style="color: #666;">まだフォロワーがいません。</p>
<?php else: ?>
  <ul>
    <?php foreach ($select_sth as $row): ?>
      <?php
        $fid = (int)$row['follower_user_id'];
        $fname = htmlspecialchars($row['follower_user_name'], ENT_QUOTES, 'UTF-8');
        $ficon = htmlspecialchars($row['follower_user_icon_filename'] ?? '', ENT_QUOTES, 'UTF-8');
        $fcreated = htmlspecialchars($row['created_at'] ?? '', ENT_QUOTES, 'UTF-8');
      ?>
      <li>
        <a href="/profile.php?user_id=<?= $fid ?>" style="display:flex; align-items:center; gap:0.6em; text-decoration:none; color:inherit;">
          <?php if (!empty($ficon)): ?>
            <img class="icon" src="/image/<?= $ficon ?>" alt="icon of <?= $fname ?>">
          <?php else: ?>
            <div class="icon" style="background:#ccc; display:flex; align-items:center; justify-content:center; color:#fff;">No</div>
          <?php endif; ?>

          <div>
            <div class="name"><?= $fname ?> <span style="font-weight:normal;color:#666;">(ID: <?= $fid ?>)</span></div>
            <div class="meta">フォローした日時: <?= $fcreated ?></div>
          </div>
        </a>

        <div class="actions">
          <!-- オプション：フォロー解除やメッセージ等のボタンを追加できる -->
          <!-- 例：自分がフォローしている相手なら「フォロー解除」ボタンを表示する等 -->
          <a class="button" href="/profile.php?user_id=<?= $fid ?>">プロフィールへ</a>
        </div>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>

<p><a href="/bbs.php">掲示板に戻る</a>　|　<a href="/setting/index.php">設定</a></p>
<p><a href="/profile.php">プロフィール画面に戻る</a></p>

</body>
</div>
</html>


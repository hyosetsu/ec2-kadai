<?php
session_start();

// ログインしてなければログイン画面に飛ばす
if (empty($_SESSION['login_user_id'])) {
  header("HTTP/1.1 302 Found");
  header("Location: /login.php");
  return;
}

// DBに接続
$dbh = new PDO('mysql:host=mysql;dbname=example_db', 'root', '');

// 自分がフォローしている一覧をDBから引く。
// テーブル結合を使って、フォローしている対象の会員情報も一緒に取得。
$select_sth = $dbh->prepare(
  'SELECT user_relationships.*, users.name AS followee_user_name, users.icon_filename AS followee_user_icon_filename'
  . ' FROM user_relationships INNER JOIN users ON user_relationships.followee_user_id = users.id'
  . ' WHERE user_relationships.follower_user_id = :follower_user_id'
  . ' ORDER BY user_relationships.id DESC'
);
$select_sth->execute([
  ':follower_user_id' => $_SESSION['login_user_id'],
]);
?>

<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>フォロー済のユーザー一覧</title>
<style>
  ul { padding-left: 0; list-style: none; }
  li { margin-bottom: 0.8em; }
  .follow-item { display:flex; align-items:center; gap:0.6em; }
  .follow-info { flex: 1; }
  .icon { height: 2em; width: 2em; border-radius: 50%; object-fit: cover; vertical-align: middle; }
  .unfollow-btn {
    background: #fff;
    border: 1px solid #ccc;
    padding: 0.25em 0.6em;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    color: #333;
    font-size: 0.9em;
  }
</style>
</head>
<body>

<h1>フォロー済のユーザー一覧</h1>

<ul>
  <?php foreach($select_sth as $relationship): ?>
  <?php
    // 安全のためキャスト
    $followee_id = (int)$relationship['followee_user_id'];
    $followee_name = htmlspecialchars($relationship['followee_user_name'], ENT_QUOTES, 'UTF-8');
    $followee_icon = htmlspecialchars($relationship['followee_user_icon_filename'] ?? '', ENT_QUOTES, 'UTF-8');
    $created_at = htmlspecialchars($relationship['created_at'] ?? '', ENT_QUOTES, 'UTF-8');
  ?>
  <li>
    <div class="follow-item">
      <div class="follow-info">
        <a href="/profile.php?user_id=<?= $followee_id ?>">
          <?php if(!empty($followee_icon)): // アイコン画像がある場合は表示 ?>
            <img class="icon" src="/image/<?= $followee_icon ?>" alt="icon">
          <?php endif; ?>
          <?= $followee_name ?> (ID: <?= $followee_id ?>)
        </a>
        <div style="font-size:0.9em; color:#666; margin-top:0.25em;">
          <?= $created_at ?> にフォロー
        </div>
      </div>

      <!-- フォロー解除への導線（確認画面へGETで飛ばす） -->
      <div>
        <a class="unfollow-btn" href="/unfollow.php?followee_user_id=<?= $followee_id ?>">
          フォロー解除
        </a>
      </div>
    </div>
  </li>
  <?php endforeach; ?>
</ul>

<p><a href="/bbs.php">掲示板に戻る</a> | <a href="/profile.php">プロフィール画面に戻る</a></p>

</body>
</html>


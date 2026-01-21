<?php
require_once __DIR__ . '/init_session.php';
$dbh = new PDO('mysql:host=mysql;dbname=example_db', 'root', '');

// 表示対象の会員ID(フォローしている会員)のリストを取得
$target_user_ids_select_sth = $dbh->prepare(
  'SELECT * FROM user_relationships WHERE follower_user_id = :follower_user_id'
);
$target_user_ids_select_sth->execute([
  ':follower_user_id' => $_SESSION['login_user_id'],
]);
$target_user_ids = array_map(
  function ($relationship) {
      return $relationship['followee_user_id'];
  },
  $target_user_ids_select_sth->fetchAll()
); // array_map で followee_user_id カラムだけ抜き出す
$target_user_ids[] = $_SESSION['login_user_id']; // 自分自身の投稿も表示対象とする

// 投稿データを取得。IN句の中身もプレースホルダを使うために、$target_user_ids の要素数だけ「?」を付けている。
$sql = 'SELECT bbs_entries.*, users.name AS user_name, users.icon_filename AS user_icon_filename'
  . ' FROM bbs_entries INNER JOIN users ON bbs_entries.user_id = users.id'
  . ' WHERE bbs_entries.user_id IN (' . substr(str_repeat(',?', count($target_user_ids)), 1) . ')'
  . ' ORDER BY bbs_entries.created_at DESC';
$select_sth = $dbh->prepare($sql);
$select_sth->execute($target_user_ids);

// body表示用関数
function bodyFilter(string $body): string
{
  $body = htmlspecialchars($body, ENT_QUOTES, 'UTF-8');
  $body = nl2br($body);
  $body = preg_replace('/&gt;&gt;(\d+)/', '<a href="#entry$1">&gt;&gt;$1</a>', $body);
  return $body;
}
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>掲示板</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<?php $active = 'timeline'; include __DIR__ . '../_nav.php'; ?>
<div class="container">
  <h1>掲示板</h1>

<?php if (empty($_SESSION['login_user_id'])): ?>
  <p><a href="/login.php">ログイン</a>して自分のタイムラインを閲覧しましょう！</p>
<?php else: ?>
  <p><a href="/timeline.php">タイムラインはこちら</a></p>
<?php endif; ?>

<hr>

<?php foreach ($select_sth as $entry): ?>
  <article class="bbs-entry" id="entry<?= htmlspecialchars($entry['id'], ENT_QUOTES, 'UTF-8') ?>">
    <dl style="margin-bottom:1em; padding-bottom:1em; border-bottom:1px solid #ccc;">
      <dt>No.</dt>
      <dd><?= htmlspecialchars($entry['id'], ENT_QUOTES, 'UTF-8') ?></dd>

      <dt>投稿者</dt>
      <dd>
        <?php
          $uid = (int)$entry['user_id'];
          $user_name = $entry['user_name'] !== null ? $entry['user_name'] : '（退会済み）';
          $user_icon = $entry['user_icon_filename'] ?? '';
          $profile_href = '/profile.php?user_id=' . rawurlencode((string)$uid);
        ?>
        <a href="<?= htmlspecialchars($profile_href, ENT_QUOTES, 'UTF-8') ?>" style="text-decoration:none; color:inherit; display:inline-flex; align-items:center;">
          <?php if (!empty($user_icon)): ?>
            <img src="/image/<?= htmlspecialchars($user_icon, ENT_QUOTES, 'UTF-8') ?>"
                 alt="icon" style="height:2em; width:2em; border-radius:50%; object-fit:cover; margin-right:0.5em;">
          <?php else: ?>
            <span style="display:inline-block; height:2em; width:2em; border-radius:50%; background:#ddd; margin-right:0.5em;"></span>
          <?php endif; ?>
          <span><?= htmlspecialchars($user_name, ENT_QUOTES, 'UTF-8') ?></span>
          <small style="margin-left:0.5em; color:#666;">(ID: <?= htmlspecialchars($entry['user_id'], ENT_QUOTES, 'UTF-8') ?>)</small>
        </a>
      </dd>

      <dt>日時</dt>
      <dd><?= htmlspecialchars($entry['created_at'], ENT_QUOTES, 'UTF-8') ?></dd>

      <dt>内容</dt>
      <dd>
        <?= bodyFilter($entry['body']) ?>
        <?php if (!empty($entry['image_filename'])): ?>
          <div style="margin-top:0.5em;">
            <img src="/image/<?= htmlspecialchars($entry['image_filename'], ENT_QUOTES, 'UTF-8') ?>"
                 alt="投稿画像" style="max-height:10em; max-width:100%; border:1px solid #ccc;">
          </div>
        <?php endif; ?>
      </dd>
    </dl>
  </article>
<?php endforeach; ?>
</body>
</div>
</html>

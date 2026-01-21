<?php
session_start();

$user_id = null;
if (!empty($_GET['user_id'])) {
  $user_id = $_GET['user_id']; // 指定されたユーザー
} elseif (!empty($_SESSION['login_user_id'])) {
  $user_id = $_SESSION['login_user_id']; // ログイン中のユーザー
}

if (empty($user_id)) {
  header("HTTP/1.1 302 Found");
  header("Location: /login.php");
  exit;
}

// DB接続
$dbh = new PDO('mysql:host=mysql;dbname=kadai_db', 'root', '');

// 対象のユーザー取得
$select_sth = $dbh->prepare("SELECT * FROM users WHERE id = :id");
$select_sth->execute([':id' => $user_id]);
$user = $select_sth->fetch();

if (empty($user)) {
  header("HTTP/1.1 404 Not Found");
  print("そのようなユーザーIDの会員情報は存在しません");
  exit;
}

// ---------------------------
// ★ フォロー状態を取得
// ---------------------------
$relationship = null;

if (!empty($_SESSION['login_user_id'])) { // ログイン中の場合のみチェック
  $select_sth = $dbh->prepare(
    "SELECT * FROM user_relationships
     WHERE follower_user_id = :follower_user_id
     AND followee_user_id = :followee_user_id"
  );
  $select_sth->execute([
    ':followee_user_id' => $user['id'], // プロフィールの主
    ':follower_user_id' => $_SESSION['login_user_id'], // 自分
  ]);
  $relationship = $select_sth->fetch();
}

// ---------------------------
// ★ あなたをフォローしているか（相手 → 自分）
// ---------------------------
$is_follower = false;

if (!empty($_SESSION['login_user_id']) && $_SESSION['login_user_id'] != $user['id']) {

    $sth = $dbh->prepare(
      "SELECT * FROM user_relationships
       WHERE follower_user_id = :other_id
       AND followee_user_id = :me_id"
    );
    $sth->execute([
      ':other_id' => $user['id'],              // プロフィールの主
      ':me_id'    => $_SESSION['login_user_id'] // 自分
    ]);

    if ($sth->fetch()) {
        $is_follower = true;
    }
}

// 生年月日から年齢（満年齢）を計算する関数
function calc_age_from_birthdate(?string $birthdate): ?int {
  if (empty($birthdate)) return null;
  try {
    $dob = new DateTime($birthdate);
    $today = new DateTime('now');
    $diff = $today->diff($dob);
    return (int)$diff->y;
  } catch (Exception $e) {
    return null;
  }
}

// ---------------------------
// ★ 追加：ユーザーの投稿一覧取得
// ---------------------------
$post_sth = $dbh->prepare(
  "SELECT * FROM bbs_entries
   WHERE user_id = :uid
   ORDER BY created_at DESC"
);
$post_sth->execute([':uid' => $user_id]);
$posts = $post_sth->fetchAll();

// 本文フィルタ（bbs.php と同じ書式）
function bodyFilter(string $body): string {
  $body = htmlspecialchars($body, ENT_QUOTES, 'UTF-8');
  $body = nl2br($body);
  $body = preg_replace('/&gt;&gt;(\d+)/', '<a href="/bbs.php#entry$1">&gt;&gt;$1</a>', $body);
  return $body;
}
?>

<!doctype html>
<html lang="ja">
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<link rel="stylesheet" href="/style.css">
<style>
/* ===== カバー画像とアイコンのレイアウト ===== */
.profile-cover-wrapper {
  width: 100%;
  max-width: 900px;
  margin: 0 auto;
  position: relative;
}

.profile-cover-img {
  width: 100%;
  height: 220px;
  object-fit: cover;
  border-radius: 10px;
}

.profile-icon-wrapper {
  position: absolute;
  bottom: -40px; /* 半分かぶせる */
  left: 20px;
}

.profile-icon {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  object-fit: cover;
  border: 4px solid white; /* 見た目が美しくなる枠線 */
}
</style>
</head>
<body>
<?php $active = 'timeline'; include __DIR__ . '/_nav.php'; ?>
<div class="container">

<!-- =========================== -->
<!-- ★ カバー画像 + アイコン重ねる部分 -->
<!-- =========================== -->
<div class="profile-cover-wrapper">

  <?php if(!empty($user['cover_filename'])): ?>
    <img class="profile-cover-img"
      src="/image/<?= htmlspecialchars($user['cover_filename']) ?>">
  <?php else: ?>
    <div class="profile-cover-img"
      style="background:#e0e0e0; display:flex; align-items:center; justify-content:center; color:#777;">
      カバー画像 未設定
    </div>
  <?php endif; ?>

  <div class="profile-icon-wrapper">
    <?php if(!empty($user['icon_filename'])): ?>
      <img class="profile-icon"
        src="/image/<?= htmlspecialchars($user['icon_filename']) ?>">
    <?php else: ?>
      <div class="profile-icon"
        style="background:#ccc; display:flex; align-items:center; justify-content:center; color:#fff;">
        No Icon
      </div>
    <?php endif; ?>
  </div>

</div>

<!-- アイコンがかぶさっている分の余白 -->
<div style="height: 50px;"></div>

<!-- ★ フォローボタン表示部分 -->
<?php if (!empty($_SESSION['login_user_id']) && $_SESSION['login_user_id'] != $user['id']): ?>
    <?php if($user['id'] === $_SESSION['login_user_id']): // 自分自身の場合 ?>
        <div style="margin: 1em 0;">
            これはあなたです！<br>
            <a href="/setting/index.php">設定画面はこちら</a>
        </div>
    <?php else: // 他人の場合 ?>
        <div style="margin: 1em 0;">
            <?php if(empty($relationship)): // フォローしていない場合 ?>
            <div>
                <a href="./follow.php?followee_user_id=<?= $user['id'] ?>">フォローする</a>
            </div>
            <?php else: // フォローしている場合 ?>
            <div>
               <?= $relationship['created_at'] ?> にフォローしました。
            </div>
            <?php endif; ?>
            <?php if(!empty($follower_relationship)): // フォローされている場合 ?>
            <div>
               フォローされています。
            </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php if ($is_follower): ?>
    <div style="color: green; margin: 0.5em 0;">
        ✔ このユーザーはあなたをフォローしています
    </div>
<?php endif; ?>

<h1><?= htmlspecialchars($user['name']) ?> さん のプロフィール</h1>
<?php if (!empty($user['birthdate'])): ?>
  <p>生年月日: <?= htmlspecialchars($user['birthdate']) ?>　/　年齢: <?= calc_age_from_birthdate($user['birthdate']) ?>歳</p>
<?php endif; ?>

<a href="/timeline.php">タイムラインに戻る</a>
<a href="/setting/index.php">設定に行く</a>
<a href="/followers.php">このユーザーのフォロワーを見る</a>
<a href="/follow_list.php">このユーザーのフォロー一覧を見る</a>

<hr>

<div class="card">
  <h2>自己紹介</h2>
  <div style="white-space: pre-wrap;">
    <?php if(empty($user['intro'])): ?>
      <span style="color: gray;">自己紹介文がまだ設定されていません。</span>
    <?php else: ?>
      <?= nl2br(htmlspecialchars($user['intro'], ENT_QUOTES, 'UTF-8')) ?>
    <?php endif; ?>
  </div>
</div>

<hr>

<!-- ---------------------------- -->
<!-- ★ ここから投稿一覧の表示部分 -->
<!-- ---------------------------- -->
<h2><?= htmlspecialchars($user['name']) ?> さんの投稿一覧</h2>

<?php if (empty($posts)): ?>
  <p style="color: gray;">まだ投稿がありません。</p>
<?php else: ?>
  <?php foreach ($posts as $entry): ?>
    <article style="margin-bottom: 1.5em; border-bottom: 1px solid #ccc; padding-bottom: 1em;">
      <p>
        <strong>No.<?= htmlspecialchars($entry['id']) ?></strong>
        （<a href="/bbs.php#entry<?= htmlspecialchars($entry['id']) ?>">掲示板で見る</a>）
      </p>

      <p style="color:#666; font-size: 0.9em;">
        投稿日時：<?= htmlspecialchars($entry['created_at']) ?>
      </p>

      <p>
        <?= bodyFilter($entry['body']) ?>
      </p>

      <?php if (!empty($entry['image_filename'])): ?>
        <div style="margin-top:0.5em;">
          <img src="/image/<?= htmlspecialchars($entry['image_filename']) ?>"
            style="max-width: 300px; max-height: 300px; border:1px solid #ccc;">
        </div>
      <?php endif; ?>
    </article>
  <?php endforeach; ?>
<?php endif; ?>
</div>
</body>
</html>

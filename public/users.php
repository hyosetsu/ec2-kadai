<?php
session_start();
$dbh = new PDO('mysql:host=mysql;dbname=kadai_db', 'root', '');

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

// 生まれ年の範囲（空OK）
$year_from_raw = $_GET['year_from'] ?? '';
$year_to_raw   = $_GET['year_to'] ?? '';
$year_from_raw = trim($year_from_raw);
$year_to_raw   = trim($year_to_raw);

// 数字だけにしたい（空は許可）
$year_from = ($year_from_raw !== '' && ctype_digit($year_from_raw)) ? (int)$year_from_raw : null;
$year_to   = ($year_to_raw !== '' && ctype_digit($year_to_raw)) ? (int)$year_to_raw : null;

// 片方だけ入ってたら同じ値にして「その年だけ」にする
if ($year_from !== null && $year_to === null) $year_to = $year_from;
if ($year_from === null && $year_to !== null) $year_from = $year_to;

// from > to なら入れ替え
if ($year_from !== null && $year_to !== null && $year_from > $year_to) {
  [$year_from, $year_to] = [$year_to, $year_from];
}

// 条件を組み立て
$where = [];
$params = [];

// 名前（部分一致）
if ($q !== '') {
  $where[] = 'name LIKE :q';
  $params[':q'] = '%' . $q . '%';
}

// 生まれ年（birthdate の日付範囲に変換）
if ($year_from !== null && $year_to !== null) {
  $where[] = 'birthdate BETWEEN :birth_from AND :birth_to';
  $params[':birth_from'] = sprintf('%04d-01-01', $year_from);
  $params[':birth_to']   = sprintf('%04d-12-31', $year_to);
}

// SQL確定
$sql = 'SELECT * FROM users';
if (!empty($where)) {
  $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY id DESC';

$select_sth = $dbh->prepare($sql);
$select_sth->execute($params);

// クリア表示判定
$has_filter = ($q !== '') || ($year_from_raw !== '') || ($year_to_raw !== '');
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<link rel="stylesheet" href="/style.css">
  <title>会員一覧</title>
</head>
<body>
<?php $active = 'timeline'; include __DIR__ . '/_nav.php'; ?>
<div class="container">

  <div style="margin-bottom: 1em;">
    <a href="/setting/index.php">設定画面</a>
    /
    <a href="/timeline.php">タイムライン</a>
  </div>

  <div class="card">
    <h1 style="margin:0;">会員一覧</h1>
    <div class="muted">名前（部分一致） / 生まれ年（範囲）で検索できます</div>
  </div>

  <!-- 検索フォーム（名前：部分一致 / 生まれ年：範囲検索） -->
  <div class="card">
    <form method="GET" class="form-row">
      <input type="text" name="q" placeholder="名前で検索（部分一致）" value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>">

      <div class="form-row cols-2">
        <input type="number" name="year_from" placeholder="XXXX" value="<?= htmlspecialchars($year_from_raw, ENT_QUOTES, 'UTF-8') ?>" min="100" max="2100">
        <input type="number" name="year_to" placeholder="YYYY" value="<?= htmlspecialchars($year_to_raw, ENT_QUOTES, 'UTF-8') ?>" min="100" max="2100">
      </div>

      <div class="row">
        <button class="btn primary" type="submit">検索</button>
        <?php if ($has_filter): ?>
          <a class="btn ghost" href="/users.php">クリア</a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <?php foreach($select_sth as $user): ?>
    <div style="display: flex; justify-content: space-between; align-items: center; padding: 1em 2em;">
      <div style="display:flex; align-items:center;">
        <?php if(empty($user['icon_filename'])): ?>
          <div style="height:2em; width:2em;"></div>
        <?php else: ?>
          <img src="/image/<?= htmlspecialchars($user['icon_filename'], ENT_QUOTES, 'UTF-8') ?>"
            style="height:2em; width:2em; border-radius:50%; object-fit:cover;">
        <?php endif; ?>

        <a href="/profile.php?user_id=<?= htmlspecialchars($user['id'], ENT_QUOTES, 'UTF-8') ?>" style="margin-left:1em;">
          <?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?>
        </a>
      </div>

      <div>
        <?php if ((int)$user['id'] === (int)$_SESSION['login_user_id']): ?>
          <span style="color:gray;">あなた</span>
        <?php elseif (in_array((string)$user['id'], array_map('strval', $followed_ids), true)): ?>
          <span style="color:gray;">フォロー中</span>
          <!-- 解除導線も出したいならここに unfollow へのリンクを置ける -->
        <?php else: ?>
          <a href="/follow.php?followee_user_id=<?= htmlspecialchars($user['id'], ENT_QUOTES, 'UTF-8') ?>"
             style="padding:0.4em 0.8em; background:#1DA1F2; color:#fff; border-radius:6px; text-decoration:none;">
            フォローする
          </a>
        <?php endif; ?>
      </div>
    </div>
    <hr style="border: none; border-bottom: 1px solid gray;">
  <?php endforeach; ?>
</body>
</div>
</html>


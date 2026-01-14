<?php
// timeline_join.php
session_start();

// ログインチェック
if (empty($_SESSION['login_user_id'])) {
  header("HTTP/1.1 302 Found");
  header("Location: /login.php");
  exit;
}

$dbh = new PDO('mysql:host=mysql;dbname=example_db', 'root', '');
$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 投稿処理（フォーム送信）
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['body'])) {
  $image_filename = null;
  if (!empty($_POST['image_base64'])) {
    $base64 = preg_replace('/^data:.+base64,/', '', $_POST['image_base64']);
    $image_binary = base64_decode($base64);
    $image_filename = strval(time()) . '_' . bin2hex(random_bytes(12)) . '.png';
    $filepath = '/var/www/upload/image/' . $image_filename;
    file_put_contents($filepath, $image_binary);
  }

  $insert_sth = $dbh->prepare("INSERT INTO bbs_entries (user_id, body, image_filename) VALUES (:user_id, :body, :image_filename)");
  $insert_sth->execute([
    ':user_id' => $_SESSION['login_user_id'],
    ':body' => $_POST['body'],
    ':image_filename' => $image_filename,
  ]);

  header("HTTP/1.1 303 See Other");
  header("Location: ./timeline_join.php");
  exit;
}

// ----------------------
// 投稿取得（JOIN方式）
// ----------------------
// 説明：
// b = bbs_entries
// u = users (投稿者の情報)
// ur = user_relationships（自分がフォローしている相手）
// LEFT JOIN にしているのは、
//   フォロー関係が無い場合でも「自分自身の投稿」は残すため。
// WHEREの条件で (ur.follower_user_id IS NOT NULL OR b.user_id = :me)
// を満たす投稿のみ取得する（＝フォローしている人の投稿 + 自分自身）。
$sql = '
  SELECT b.*, u.name AS user_name, u.icon_filename AS user_icon_filename
  FROM bbs_entries b
  INNER JOIN users u ON b.user_id = u.id
  LEFT JOIN user_relationships ur
    ON ur.followee_user_id = b.user_id
    AND ur.follower_user_id = :me
  WHERE (ur.follower_user_id IS NOT NULL OR b.user_id = :me)
  ORDER BY b.created_at DESC
';
$select_sth = $dbh->prepare($sql);
$select_sth->execute([':me' => $_SESSION['login_user_id']]);

// 本文フィルタ（XSS対策・レスアンカーを有効化）
function bodyFilter(string $body): string {
  $body = htmlspecialchars($body, ENT_QUOTES, 'UTF-8');
  $body = nl2br($body);
  $body = preg_replace('/&gt;&gt;(\d+)/', '<a href="/bbs.php#entry$1">&gt;&gt;$1</a>', $body);
  return $body;
}
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>タイムライン（JOIN版）</title>
<style>
  body { font-family: sans-serif; max-width: 900px; margin: 1em auto; padding: 0 1em; }
  header { display:flex; justify-content:space-between; align-items:center; }
  textarea { width:100%; box-sizing:border-box; }
  .post { border-bottom:1px solid #ddd; padding:1em 0; display:flex; gap:1em; }
  .avatar { width:3.2em; height:3.2em; border-radius:50%; object-fit:cover; }
  .meta { color:#666; font-size:0.9em; }
  .post-body img { max-width:320px; max-height:320px; border:1px solid #ccc; display:block; margin-top:0.5em; }
</style>
</head>
<body>
<header>
  <h1>タイムライン（フォロー + 自分）</h1>
  <nav>
    <a href="/setting/index.php">設定</a> |
    <a href="/bbs.php">掲示板</a> |
    <a href="/follow_list.php">フォロー一覧</a>
  </nav>
</header>

<section>
  <h2>投稿する</h2>
  <form method="POST" id="postForm">
    <textarea name="body" rows="4" required placeholder="いまどうしてる？"></textarea>
    <div style="margin:0.5em 0;">
      <input type="file" accept="image/*" id="imageInput">
      <input type="hidden" id="imageBase64Input" name="image_base64">
      <canvas id="imageCanvas" style="display:none;"></canvas>
    </div>
    <button type="submit">投稿</button>
  </form>
</section>

<hr>

<section>
  <h2>タイムライン</h2>
  <?php foreach($select_sth as $entry): ?>
    <article class="post" id="entry<?= htmlspecialchars($entry['id']) ?>">
      <div>
        <?php if(!empty($entry['user_icon_filename'])): ?>
          <a href="/profile.php?user_id=<?= htmlspecialchars($entry['user_id']) ?>">
            <img src="/image/<?= htmlspecialchars($entry['user_icon_filename']) ?>" alt="icon" class="avatar">
          </a>
        <?php else: ?>
          <div style="width:3.2em; height:3.2em; border-radius:50%; background:#ccc;"></div>
        <?php endif; ?>
      </div>
      <div style="flex:1;">
        <div style="display:flex; justify-content:space-between; align-items:center;">
          <div>
            <a href="/profile.php?user_id=<?= htmlspecialchars($entry['user_id']) ?>">
              <strong><?= htmlspecialchars($entry['user_name']) ?></strong>
            </a>
            <div class="meta">ID: <?= htmlspecialchars($entry['user_id']) ?> — <?= htmlspecialchars($entry['created_at']) ?></div>
          </div>
        </div>

        <div class="post-body">
          <?= bodyFilter($entry['body']) ?>
          <?php if (!empty($entry['image_filename'])): ?>
            <img src="/image/<?= htmlspecialchars($entry['image_filename']) ?>" alt="attached image">
          <?php endif; ?>
        </div>
      </div>
    </article>
  <?php endforeach; ?>
</section>

<script>
// 画像を縮小して base64 にして hidden に入れる（投稿用）
document.getElementById('postForm').addEventListener('submit', async function(e) {
  const fileInput = document.getElementById('imageInput');
  if (fileInput.files.length === 0) return; // 画像なしはそのまま送る
  const file = fileInput.files[0];
  if (!file.type.startsWith('image/')) return;

  e.preventDefault();

  const img = await createImageBitmap(file);
  const maxSize = 1200;
  let { width, height } = img;
  if (width > maxSize || height > maxSize) {
    if (width > height) {
      height = Math.round(height * maxSize / width); width = maxSize;
    } else {
      width = Math.round(width * maxSize / height); height = maxSize;
    }
  }
  const canvas = document.getElementById('imageCanvas');
  canvas.width = width; canvas.height = height;
  const ctx = canvas.getContext('2d');
  ctx.drawImage(img, 0, 0, width, height);

  const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/png', 0.9));
  if (blob.size > 5 * 1024 * 1024) {
    alert('画像を5MB以下にしてください');
    return;
  }
  const reader = new FileReader();
  reader.onload = () => {
    document.getElementById('imageBase64Input').value = reader.result;
    this.submit();
  };
  reader.readAsDataURL(blob);
});
</script>
</body>
</html>


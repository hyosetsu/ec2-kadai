<?php
require_once __DIR__ . '/init_session.php';
$dbh = new PDO('mysql:host=mysql;dbname=example_db', 'root', '');

// 投稿処理
if (isset($_POST['body']) && !empty($_SESSION['login_user_id'])) {
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
  header("Location: ./bbs.php");
  exit;
}

// 投稿取得（サブクエリで投稿者の名前とアイコンを取得）
$select_sth = $dbh->prepare(
  'SELECT bbs_entries.*,
     (SELECT name FROM users WHERE id = bbs_entries.user_id) AS user_name,
     (SELECT icon_filename FROM users WHERE id = bbs_entries.user_id) AS user_icon_filename
   FROM bbs_entries
   ORDER BY created_at DESC'
);
$select_sth->execute();

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
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>掲示板</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
  <h1>掲示板</h1>

<?php if (empty($_SESSION['login_user_id'])): ?>
  <p>投稿するには <a href="/login.php">ログイン</a> が必要です。</p>
<?php else: ?>
  <p>現在ログイン中 — <a href="/setting/index.php">設定画面はこちら</a></p>
  <p><a href="/profile.php">プロフィール画面はこちら</a></p>
  <form method="POST" action="./bbs.php" enctype="multipart/form-data" id="postForm">
    <textarea name="body" required placeholder="本文を入力してください" rows="4" cols="40"></textarea>
    <div style="margin:1em 0;">
      <input type="file" accept="image/*" name="image" id="imageInput">
    </div>
    <input id="imageBase64Input" type="hidden" name="image_base64">
    <canvas id="imageCanvas" style="display:none;"></canvas>
    <button type="submit">送信</button>
  </form>
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

<script>
// 既存の画像縮小処理（あなたの既存コードをそのまま使用）
document.addEventListener("DOMContentLoaded", () => {
  const imageInput = document.getElementById("imageInput");
  if (!imageInput) return;
  const imageBase64Input = document.getElementById("imageBase64Input");
  const canvas = document.getElementById("imageCanvas");

  imageInput.addEventListener("change", () => {
    if (imageInput.files.length < 1) return;
    const file = imageInput.files[0];
    if (!file.type.startsWith('image/')) return;

    const reader = new FileReader();
    const image = new Image();
    reader.onload = () => {
      image.onload = () => {
        const maxLength = 1000;
        let ow = image.naturalWidth;
        let oh = image.naturalHeight;
        let w = ow;
        let h = oh;
        if (ow > oh) {
          if (ow > maxLength) { h = Math.round(h * maxLength / ow); w = maxLength; }
        } else {
          if (oh > maxLength) { w = Math.round(w * maxLength / oh); h = maxLength; }
        }
        canvas.width = w;
        canvas.height = h;
        const ctx = canvas.getContext("2d");
        ctx.drawImage(image, 0, 0, w, h);
        imageBase64Input.value = canvas.toDataURL('image/png', 0.9);
      };
      image.src = reader.result;
    };
    reader.readAsDataURL(file);
  });
});
</script>
</body>
</html>

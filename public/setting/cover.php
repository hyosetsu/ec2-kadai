<?php
session_start();
if (empty($_SESSION['login_user_id'])) {
  header("HTTP/1.1 302 Found");
  header("Location: /login.php");
  exit;
}

// DBに接続
$dbh = new PDO('mysql:host=mysql;dbname=example_db', 'root', '');
$select_sth = $dbh->prepare("SELECT * FROM users WHERE id = :id");
$select_sth->execute([ ':id' => $_SESSION['login_user_id'] ]);
$user = $select_sth->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $cover_filename = null;
  if (!empty($_POST['image_base64'])) {
    $base64 = preg_replace('/^data:.+base64,/', '', $_POST['image_base64']);
    $image_binary = base64_decode($base64);
    if ($image_binary !== false) {
      $cover_filename = strval(time()) . '_' . bin2hex(random_bytes(12)) . '.jpg';
      $filepath = '/var/www/upload/image/' . $cover_filename;
      file_put_contents($filepath, $image_binary);
    }
  }

  // DB更新（NULL許容）
  $update_sth = $dbh->prepare("UPDATE users SET cover_filename = :cover_filename WHERE id = :id");
  $update_sth->execute([
    ':cover_filename' => $cover_filename,
    ':id' => $user['id'],
  ]);

  header("HTTP/1.1 302 Found");
  header("Location: ./cover.php");
  exit;
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <link rel="stylesheet" href="/../style.css">
<title>カバー画像設定</title>
<style>
  .cover-preview { width: 100%; max-width: 900px; height: 220px; background:#eee; border:1px solid #ccc; object-fit: cover; display:block; margin: 0 auto 1em; }
  form { max-width:900px; margin:0 auto; }
</style>
</head>
<body>
<?php $active = 'timeline'; include __DIR__ . '/../_nav.php'; ?>
<div class="container">
<a href="./index.php">設定一覧に戻る</a>
<h1>カバー画像の設定/変更</h1>

<?php if (!empty($user['cover_filename'])): ?>
  <img class="cover-preview" src="/image/<?= htmlspecialchars($user['cover_filename']) ?>" alt="現在のカバー画像">
<?php else: ?>
  <div class="cover-preview" style="display:flex;align-items:center;justify-content:center;color:#777;">
    カバー画像 未設定
  </div>
<?php endif; ?>

<form method="POST" id="coverForm">
  <div style="margin:1em 0;">
    <input type="file" accept="image/*" id="imageInput">
  </div>

  <input id="imageBase64Input" type="hidden" name="image_base64">
  <canvas id="imageCanvas" style="display:none;"></canvas>

  <button type="submit">アップロード（保存）</button>
</form>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const input = document.getElementById('imageInput');
  const canvas = document.getElementById('imageCanvas');
  const imageBase64Input = document.getElementById('imageBase64Input');
  const form = document.getElementById('coverForm');

  // プレビュー表示（オプション）：簡易に既存のプレビュー要素を置き換える
  function setPreviewDataURL(dataURL) {
    let prev = document.querySelector('.cover-preview');
    if (!prev) {
      prev = document.createElement('img');
      prev.className = 'cover-preview';
      form.parentNode.insertBefore(prev, form);
    }
    prev.src = dataURL;
  }

  input.addEventListener('change', () => {
    if (!input.files || input.files.length === 0) return;
    const file = input.files[0];
    if (!file.type.startsWith('image/')) return;

    const reader = new FileReader();
    reader.onload = () => {
      const img = new Image();
      img.onload = () => {
        // カバーは横長向けに最大幅900、高さ220を基準にリサイズ（縦横比維持）
        const targetW = 1600; // 最終的に高品質で保存したければ大きめに
        const targetH = 400;
        let width = img.naturalWidth;
        let height = img.naturalHeight;

        // 横長優先で調整（どちらか一方が超えたら縮小）
        const ratio = Math.max(width / targetW, height / targetH, 1);
        width = Math.round(width / ratio);
        height = Math.round(height / ratio);

        canvas.width = width;
        canvas.height = height;
        const ctx = canvas.getContext('2d');
        ctx.clearRect(0,0,width,height);
        // 背景を白に（透明PNG対策）
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0,0,width,height);
        ctx.drawImage(img, 0, 0, width, height);
        // JPEGで品質0.9にしてbase64化
        canvas.toBlob(blob => {
          const reader2 = new FileReader();
          reader2.onload = () => {
            const dataURL = reader2.result;
            imageBase64Input.value = dataURL;
            setPreviewDataURL(dataURL);
          };
          reader2.readAsDataURL(blob);
        }, 'image/jpeg', 0.9);
      };
      img.src = reader.result;
    };
    reader.readAsDataURL(file);
  });

  // フォーム送信時、image_base64があれば通常POST（inputが空だとサーバでNULL扱い）
  form.addEventListener('submit', (e) => {
    // そのまま送信（hidden inputに入っていればサーバで保存）
    // もし大きすぎてhiddenが空の場合はキャンセルして警告
    if (document.getElementById('imageBase64Input').value === '' && input.files.length > 0) {
      e.preventDefault();
      alert('画像処理に失敗しました。別の画像をお試しください。');
    }
  });
});
</script>
</body>
</div>
</html>


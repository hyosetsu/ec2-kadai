<?php
$dbh = new PDO('mysql:host=mysql;dbname=kadai_db', 'root', '');

session_start();
if (empty($_SESSION['login_user_id'])) { // 非ログインの場合利用不可
  header("HTTP/1.1 302 Found");
  header("Location: /login.php");
  return;
}

// 現在のログイン情報を取得する
$user_select_sth = $dbh->prepare("SELECT * from users WHERE id = :id");
$user_select_sth->execute([':id' => $_SESSION['login_user_id']]);
$user = $user_select_sth->fetch();

// 投稿処理
if (isset($_POST['body']) && !empty($_SESSION['login_user_id'])) {

  $image_filename = null;
  if (!empty($_POST['image_base64'])) {
    // 先頭の data:~base64, のところは削る
    $base64 = preg_replace('/^data:.+base64,/', '', $_POST['image_base64']);

    // base64からバイナリにデコードする
    $image_binary = base64_decode($base64);

    // 新しいファイル名を決めてバイナリを出力する
    $image_filename = strval(time()) . bin2hex(random_bytes(25)) . '.png';
    $filepath =  '/var/www/upload/image/' . $image_filename;
    file_put_contents($filepath, $image_binary);
  }

  // insertする
  $insert_sth = $dbh->prepare("INSERT INTO bbs_entries (user_id, body, image_filename) VALUES (:user_id, :body, :image_filename)");
  $insert_sth->execute([
    ':user_id' => $_SESSION['login_user_id'], // ログインしている会員情報の主キー
    ':body' => $_POST['body'], // フォームから送られてきた投稿本文
    ':image_filename' => $image_filename, // 保存した画像の名前 (nullの場合もある)
  ]);

  // 処理が終わったらリダイレクトする
  // リダイレクトしないと，リロード時にまた同じ内容でPOSTすることになる
  header("HTTP/1.1 303 See Other");
  header("Location: ./timeline.php");
  return;
}

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
?>

<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <link rel="stylesheet" href="/style.css">
  <title>タイムライン</title>
</head>
<body>
<?php $active = 'timeline'; include __DIR__ . '/_nav.php'; ?>
<div class="container">

  <div class="card">
    <div class="row">
      <div>
        <div style="font-weight:800;">タイムライン</div>
        <div class="muted">現在 <?= htmlspecialchars($user['name']) ?> (ID: <?= htmlspecialchars($user['id']) ?>) でログイン中</div>
      </div>
      <a class="btn ghost" href="/users.php">会員一覧</a>
    </div>
  </div>

  <!-- フォームのPOST先はこのファイル自身にする -->
  <div class="card">
    <form method="POST" action="./timeline.php">
      <div class="form-row">
        <textarea name="body" required placeholder="いまどうしてる？"></textarea>

        <div class="form-row cols-2">
          <input type="file" accept="image/*" name="image" id="imageInput">
          <button class="btn primary" type="submit">送信</button>
        </div>

        <input id="imageBase64Input" type="hidden" name="image_base64">
        <canvas id="imageCanvas" style="display:none;"></canvas>
      </div>
    </form>
  </div>

  <dl id="entryTemplate" class="card post" style="display:none;">
    <dt>番号</dt>
    <dd data-role="entryIdArea"></dd>
    <dt>投稿者</dt>
    <dd style="display: flex; align-items: center; gap: 0.5em;">
     	<!-- ★投稿者アイコン -->
    	<img data-role="entryUserIcon"
      	   src=""
        	 style="height: 2em; width: 2em; border-radius: 50%; object-fit: cover; display: none;">

  	  <!-- 投稿者名 -->
      <a href="" data-role="entryUserAnchor"></a>
    </dd>
    <dt>日時</dt>
    <dd data-role="entryCreatedAtArea"></dd>
    <dt>内容</dt>
    <dd>
      <div data-role="entryBodyArea"></div>
      <!-- ★画像表示枠を追加 -->
      <div data-role="entryImageWrap" style="margin-top: 0.5em; display: none;">
        <img data-role="entryImage"
           src=""
           style="max-height: 10em;">
      </div>
    </dd>
  </dl>
  <div id="entriesRenderArea"></div>

<script>
document.addEventListener("DOMContentLoaded", () => {
  const entryTemplate = document.getElementById('entryTemplate');
  const entriesRenderArea = document.getElementById('entriesRenderArea');

  const request = new XMLHttpRequest();
  request.onload = (event) => {
    const response = event.target.response;
    response.entries.forEach((entry) => {
      // テンプレートとするものから要素をコピー
      const entryCopied = entryTemplate.cloneNode(true);

      // display: none を display: block に書き換える
      entryCopied.style.display = 'block';

      // 番号(ID)を表示
      entryCopied.querySelector('[data-role="entryIdArea"]').innerText = entry.id.toString();

      // 名前を表示
      entryCopied.querySelector('[data-role="entryUserAnchor"]').innerText = entry.user_name;

			entryCopied.querySelector('[data-role="entryUserAnchor"]').href = entry.user_profile_url;

			// ★投稿者アイコン
			const userIconImg = entryCopied.querySelector('[data-role="entryUserIcon"]');

			if (entry.user_icon_url) {
			  userIconImg.src = entry.user_icon_url;
			  userIconImg.style.display = 'block';
			} else {
			  userIconImg.style.display = 'none';
			}

      // 名前のところのリンク先(プロフィール)のURLを設定
      entryCopied.querySelector('[data-role="entryUserAnchor"]').href = entry.user_profile_url;

      // 投稿日時を表示
      entryCopied.querySelector('[data-role="entryCreatedAtArea"]').innerText = entry.created_at;

      // 本文を表示 (ここはHTMLなのでinnerHTMLで)
      entryCopied.querySelector('[data-role="entryBodyArea"]').innerHTML = entry.body;
      const imageWrap = entryCopied.querySelector('[data-role="entryImageWrap"]');
			const imageEl = entryCopied.querySelector('[data-role="entryImage"]');

			if (entry.post_image_url) {
			  imageEl.src = entry.post_image_url;
  			imageWrap.style.display = 'block';
			} else {
  			imageWrap.style.display = 'none';
			}

      // 最後に実際の描画を行う
      entriesRenderArea.appendChild(entryCopied);
    });
  }
  request.open('GET', '/timeline_json.php', true); // timeline_json.php を叩く
  request.responseType = 'json';
  request.send();


  // 以下画像縮小用
  const imageInput = document.getElementById("imageInput");
  imageInput.addEventListener("change", () => {
    if (imageInput.files.length < 1) {
      // 未選択の場合
      return;
    }

    const file = imageInput.files[0];
    if (!file.type.startsWith('image/')){ // 画像でなければスキップ
      return;
    }

    // 画像縮小処理
    const imageBase64Input = document.getElementById("imageBase64Input"); // base64を送るようのinput
    const canvas = document.getElementById("imageCanvas"); // 描画するcanvas
    const reader = new FileReader();
    const image = new Image();
    reader.onload = () => { // ファイルの読み込み完了したら動く処理を指定
      image.onload = () => { // 画像として読み込み完了したら動く処理を指定

        // 元の縦横比を保ったまま縮小するサイズを決めてcanvasの縦横に指定する
        const originalWidth = image.naturalWidth; // 元画像の横幅
        const originalHeight = image.naturalHeight; // 元画像の高さ
        const maxLength = 1000; // 横幅も高さも1000以下に縮小するものとする
        if (originalWidth <= maxLength && originalHeight <= maxLength) { // どちらもmaxLength以下の場合そのまま
            canvas.width = originalWidth;
            canvas.height = originalHeight;
        } else if (originalWidth > originalHeight) { // 横長画像の場合
            canvas.width = maxLength;
            canvas.height = maxLength * originalHeight / originalWidth;
        } else { // 縦長画像の場合
            canvas.width = maxLength * originalWidth / originalHeight;
            canvas.height = maxLength;
        }

        // canvasに実際に画像を描画 (canvasはdisplay:noneで隠れているためわかりにくいが...)
        const context = canvas.getContext("2d");
        context.drawImage(image, 0, 0, canvas.width, canvas.height);

        // canvasの内容をbase64に変換しinputのvalueに設定
        imageBase64Input.value = canvas.toDataURL();
      };
      image.src = reader.result;
    };
    reader.readAsDataURL(file);
  });
});
</script>
</div>
</body>
</html>

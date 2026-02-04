<?php
$dbh = new PDO('mysql:host=mysql;dbname=kadai_db', 'root', '');

session_start();
if (empty($_SESSION['login_user_id'])) {
  header("HTTP/1.1 302 Found");
  header("Location: /login.php");
  exit;
}

// ログインユーザー取得
$sth = $dbh->prepare("SELECT * FROM users WHERE id=:id");
$sth->execute([':id'=>$_SESSION['login_user_id']]);
$user = $sth->fetch();

// 投稿処理
if(!empty($_POST['body'])){

  $image_filename=null;

  if(!empty($_POST['image_base64'])){
    $base64=preg_replace('/^data:.+base64,/', '', $_POST['image_base64']);
    $binary=base64_decode($base64);

    $image_filename=time().bin2hex(random_bytes(8)).'.png';
    file_put_contents('/var/www/upload/image/'.$image_filename,$binary);
  }

  $ins=$dbh->prepare(
    "INSERT INTO bbs_entries(user_id,body,image_filename)
     VALUES(:uid,:body,:img)"
  );
  $ins->execute([
    ':uid'=>$_SESSION['login_user_id'],
    ':body'=>$_POST['body'],
    ':img'=>$image_filename
  ]);

  header("Location: ./timeline.php");
  exit;
}
?>

<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="/style.css">
<title>タイムライン</title>
</head>

<body>

<?php $active='timeline'; include __DIR__.'/_nav.php'; ?>

<div class="container">

<div class="card">
<b>タイムライン</b><br>
<span class="muted"><?=htmlspecialchars($user['name'])?> さんでログイン中</span>
</div>

<div class="card">
<form method="POST">
<textarea name="body" required placeholder="いまどうしてる？"></textarea>

<div style="margin-top:.5em">
<input type="file" id="imageInput" accept="image/*">
<button class="btn primary">送信</button>
</div>

<input type="hidden" name="image_base64" id="imageBase64Input">
<canvas id="imageCanvas" style="display:none"></canvas>
</form>
</div>

<!-- テンプレ -->
<div id="entryTemplate" class="card post" style="display:none">
<img class="avatar" data-role="icon">

<div class="post-main">
<div class="post-head">
<div class="post-user">
<a data-role="name"></a>
</div>
<div class="muted" data-role="time"></div>
</div>

<div class="post-body" data-role="body"></div>

<div class="post-image" data-role="imgWrap" style="display:none">
<img data-role="img">
</div>
</div>
</div>

<div id="entries"></div>

</div>

<script>
document.addEventListener('DOMContentLoaded',()=>{

let lastId=null;
let loading=false;

const template=document.getElementById('entryTemplate');
const list=document.getElementById('entries');

function render(){

if(loading) return;
loading=true;

const url='/timeline_json.php'+(lastId?('?last_id='+lastId):'');

fetch(url)
.then(r=>r.json())
.then(data=>{

data.entries.forEach(e=>{

const node=template.cloneNode(true);
node.style.display='flex';

node.querySelector('[data-role=name]').textContent=e.user_name;
node.querySelector('[data-role=name]').href=e.user_profile_url;
node.querySelector('[data-role=time]').textContent=e.created_at;
node.querySelector('[data-role=body]').innerHTML=e.body;

const icon=node.querySelector('[data-role=icon]');
if(e.user_icon_url){
 icon.src=e.user_icon_url;
}else{
 icon.style.display='none';
}

const imgWrap=node.querySelector('[data-role=imgWrap]');
if(e.post_image_url){
 imgWrap.style.display='block';
 imgWrap.querySelector('img').src=e.post_image_url;
}

list.appendChild(node);
lastId=e.id;

});

loading=false;

if(lastId>data.last_entries_id){
observeLast();
}

});
}

function observeLast(){
const last=list.lastElementChild;
if(!last) return;

const io=new IntersectionObserver(es=>{
es.forEach(en=>{
if(en.isIntersecting){
io.disconnect();
render();
}
});
});
io.observe(last);
}

render();

/* 画像縮小 */
const input=document.getElementById('imageInput');
input.addEventListener('change',()=>{

if(!input.files.length) return;

const file=input.files[0];
if(!file.type.startsWith('image/')) return;

const reader=new FileReader();
const img=new Image();
const canvas=document.getElementById('imageCanvas');
const hidden=document.getElementById('imageBase64Input');

reader.onload=()=>{
img.onload=()=>{

let w=img.width,h=img.height;
const max=1000;

if(w>h && w>max){ h=h*max/w; w=max;}
else if(h>max){ w=w*max/h; h=max;}

canvas.width=w; canvas.height=h;
canvas.getContext('2d').drawImage(img,0,0,w,h);

hidden.value=canvas.toDataURL();
};
img.src=reader.result;
};
reader.readAsDataURL(file);
});

});
</script>

</body>
</html>


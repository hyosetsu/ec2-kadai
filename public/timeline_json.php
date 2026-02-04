<?php
$dbh = new PDO('mysql:host=mysql;dbname=kadai_db', 'root', '');

session_start();
if (empty($_SESSION['login_user_id'])) {
  header("HTTP/1.1 401 Unauthorized");
  header("Content-Type: application/json");
  echo json_encode(['entries'=>[]]);
  exit;
}

$limit = 10;

$sql =
'SELECT bbs_entries.*, users.name AS user_name, users.icon_filename AS user_icon_filename
 FROM bbs_entries
 INNER JOIN users ON bbs_entries.user_id = users.id
 WHERE '
 . (isset($_GET['last_id']) ? ' bbs_entries.id < :last_id AND ' : '')
 . '( bbs_entries.user_id IN
      (SELECT followee_user_id FROM user_relationships WHERE follower_user_id = :uid)
    OR bbs_entries.user_id = :uid )
 ORDER BY bbs_entries.id DESC
 LIMIT '.$limit;

$params = [':uid'=>$_SESSION['login_user_id']];
if(isset($_GET['last_id'])){
  $params[':last_id'] = intval($_GET['last_id']);
}

$sth = $dbh->prepare($sql);
$sth->execute($params);

// 最古ID取得（終了判定用）
$lastIdSt = $dbh->prepare(
'SELECT id FROM bbs_entries
 WHERE user_id IN
 (SELECT followee_user_id FROM user_relationships WHERE follower_user_id=:uid)
 OR user_id=:uid
 ORDER BY id ASC LIMIT 1'
);
$lastIdSt->execute([':uid'=>$_SESSION['login_user_id']]);
$lastRow = $lastIdSt->fetch();
$last_entries_id = $lastRow ? intval($lastRow['id']) : 0;

function bodyFilter($b){
  return nl2br(htmlspecialchars($b));
}

$entries=[];
$last_rendered_entry_id=null;

foreach($sth as $e){
  $last_rendered_entry_id=$e['id'];

  $entries[]=[
    'id'=>$e['id'],
    'user_name'=>$e['user_name'],
    'user_profile_url'=>'/profile.php?user_id='.$e['user_id'],
    'created_at'=>$e['created_at'],
    'body'=>bodyFilter($e['body']),

    'user_icon_url'=> empty($e['user_icon_filename'])?null:'/image/'.$e['user_icon_filename'],
    'post_image_url'=> empty($e['image_filename'])?null:'/image/'.$e['image_filename'],
  ];
}

header("Content-Type: application/json");
echo json_encode([
  'entries'=>$entries,
  'last_rendered_entry_id'=>$last_rendered_entry_id,
  'last_entries_id'=>$last_entries_id,
]);


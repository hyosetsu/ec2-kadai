<?php
// --- Redis接続 ---
$redis = new Redis();
$redis->connect('redis', 6379); // docker-compose の service 名で接続
// $redis->auth('password'); // パスワードを設定している場合は認証する

// --- カウンタ処理 ---
$key = "page_access_count";
$count = $redis->exists($key) ? intval($redis->get($key)) : 0;

// カウントをインクリメント
$count++;

// カウントをRedisに文字列として保存
$redis->set($key, strval($count));

?>
現在のカウントは <?= $count ?> です。

<?php
// セッションIDの取得(なければ新規で作成&設定)
$session_cookie_name = 'session_id';
$session_id = $_COOKIE[$session_cookie_name] ?? base64_encode(random_bytes(64));
if (!isset($_COOKIE[$session_cookie_name])) {
    setcookie($session_cookie_name, $session_id, time() + 3600, "/"); // 有効期限1時間
}

// Redisへ接続（redisコンテナ）
$redis = new Redis();
$redis->connect('redis', 6379);

// Redis上のセッションキー
$redis_session_key = "session-" . $session_id;

// 既にセッション変数があれば取得、なければ空配列
$session_values = $redis->exists($redis_session_key)
    ? json_decode($redis->get($redis_session_key), true)
    : [];

// アクセスカウンタを更新
if (!isset($session_values['access_count'])) {
    $session_values['access_count'] = 1;
} else {
    $session_values['access_count']++;
}

// Redisにセッション情報を保存
$redis->set($redis_session_key, json_encode($session_values));

// 表示
$count = $session_values['access_count'];
echo "このセッションでの {$count} 回目 のアクセスです！";


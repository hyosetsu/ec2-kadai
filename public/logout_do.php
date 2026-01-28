<?php
require_once __DIR__ . '/init_session.php';

// POST以外は拒否（安全）
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("HTTP/1.1 405 Method Not Allowed");
  echo "Method Not Allowed";
  exit;
}

// セッション破棄
$_SESSION = [];

if (ini_get("session.use_cookies")) {
  $params = session_get_cookie_params();
  setcookie(session_name(), '', time() - 42000,
    $params["path"], $params["domain"],
    $params["secure"], $params["httponly"]
  );
}

session_destroy();

// 完了画面へ
header("HTTP/1.1 303 See Other");
header("Location: /logout_finish.php");
exit;

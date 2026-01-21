<?php
session_start();
if (empty($_SESSION['login_user_id'])) {
  header("HTTP/1.1 302 Found");
  header("Location: /login.php");
  exit;
}

$dbh = new PDO('mysql:host=mysql;dbname=example_db', 'root', '');
$select = $dbh->prepare("SELECT birthdate FROM users WHERE id = :id LIMIT 1");
$select->execute([':id' => $_SESSION['login_user_id']]);
$user = $select->fetch();

$current_birth = $user['birthdate'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // input validation: expect YYYY-MM-DD or empty
  $birth_input = $_POST['birthdate'] ?? '';
  if ($birth_input === '') {
    // unset birthdate
    $update = $dbh->prepare("UPDATE users SET birthdate = NULL WHERE id = :id");
    $update->execute([':id' => $_SESSION['login_user_id']]);
    header("Location: ./birthdate.php?success=1");
    exit;
  }
  // basic format check
  $d = DateTime::createFromFormat('Y-m-d', $birth_input);
  $valid = $d && $d->format('Y-m-d') === $birth_input;
  if (!$valid) {
    $error = "正しい日付（YYYY-MM-DD）を入力してください。";
  } else {
    $update = $dbh->prepare("UPDATE users SET birthdate = :birthdate WHERE id = :id");
    $update->execute([':birthdate' => $birth_input, ':id' => $_SESSION['login_user_id']]);
    header("Location: ./birthdate.php?success=1");
    exit;
  }
}
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>生年月日設定</title>
</head>
<body>
<a href="./index.php">設定一覧に戻る</a>
<h1>生年月日設定</h1>

<?php if (!empty($error)): ?>
  <div style="color: red;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if (!empty($_GET['success'])): ?>
  <div style="color: green;">保存しました。</div>
<?php endif; ?>

<form method="POST">
  <label>
    生年月日:
    <input type="date" name="birthdate" value="<?= htmlspecialchars($current_birth) ?>">
    <small>未設定にするには空にして送信</small>
  </label>
  <br><br>
  <button type="submit">保存</button>
</form>
</body>
</html>


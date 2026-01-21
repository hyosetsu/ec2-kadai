<?php
// _nav.php
// どのページでも使えるようにする（login_user_id が無いページでもOK）
$me_id = $_SESSION['login_user_id'] ?? null;
?>
<div class="topbar">
  <div class="topbar-inner">
    <div class="brand">MySNS</div>
    <div class="space"></div>
    <?php if($me_id): ?>
      <a class="btn" href="/profile.php">自分のプロフィール</a>
    <?php else: ?>
      <a class="btn" href="/login.php">ログイン</a>
    <?php endif; ?>
  </div>
</div>

<div class="bottombar">
  <div class="bottombar-inner">
    <a href="/timeline.php" class="primary">タイムライン</a>
    <a href="/users.php">会員一覧</a>
    <a href="/setting/index.php">設定</a>
  </div>
</div>


<?php
$active = $active ?? '';

function tabClass(string $key, string $active): string {
  return $key === $active ? 'tab is-active' : 'tab';
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <link rel="stylesheet" href="/style.css">
</head>
<body>
<div class="header">
  <div class="header-inner">
    <a class="brand" href="/timeline.php">MiniSNS</a>

    <div class="top-links">
      <a href="/timeline.php">タイムライン</a>
      <a href="/users.php">会員一覧</a>
      <a href="/profile.php">プロフィール</a>
      <a href="/setting/index.php">設定</a>
      <a href="/logout.php">ログアウト</a>
    </div>

    <button class="menu-btn" type="button" aria-label="メニュー" aria-controls="drawer" aria-expanded="false">
      <span class="menu-icon" aria-hidden="true"></span>
    </button>
  </div>
</div>

<div id="drawerBackdrop" class="drawer-backdrop" hidden></div>

<nav id="drawer" class="drawer" aria-hidden="true">
  <div class="drawer-head">
    <div class="drawer-title">メニュー</div>
    <button class="drawer-close" type="button" aria-label="閉じる">×</button>
  </div>

  <div class="drawer-links">
    <a href="/timeline.php">タイムライン</a>
    <a href="/users.php">会員一覧</a>
    <a href="/profile.php">プロフィール</a>
    <a href="/setting/index.php">設定</a>
    <a class="danger" href="/logout.php">ログアウト</a>
  </div>
</nav>

<div class="bottom-nav">
  <div class="bottom-nav-inner">
    <a class="<?= tabClass('timeline', $active) ?>" href="/timeline.php">TL</a>
    <a class="<?= tabClass('users', $active) ?>" href="/users.php">会員</a>
    <a class="<?= tabClass('profile', $active) ?>" href="/profile.php">自分</a>
    <a class="<?= tabClass('setting', $active) ?>" href="/setting/index.php">設定</a>
  </div>
</div>

<script>
(() => {
  const btn = document.querySelector('.menu-btn');
  const drawer = document.getElementById('drawer');
  const backdrop = document.getElementById('drawerBackdrop');
  const closeBtn = document.querySelector('.drawer-close');

  if (!btn || !drawer || !backdrop || !closeBtn) return;

  function openDrawer() {
    drawer.classList.add('is-open');
    backdrop.hidden = false;
    btn.setAttribute('aria-expanded', 'true');
    drawer.setAttribute('aria-hidden', 'false');
    document.body.classList.add('no-scroll');
  }

  function closeDrawer() {
    drawer.classList.remove('is-open');
    backdrop.hidden = true;
    btn.setAttribute('aria-expanded', 'false');
    drawer.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('no-scroll');
  }

  btn.addEventListener('click', () => {
    if (drawer.classList.contains('is-open')) closeDrawer();
    else openDrawer();
  });

  closeBtn.addEventListener('click', closeDrawer);
  backdrop.addEventListener('click', closeDrawer);

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeDrawer();
  });
})();
</script>
</body>
</html>

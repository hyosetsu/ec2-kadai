<?php
// public/_nav.php
// 使い方：各ページで $active = 'timeline'; などをセットしてから includeする

$active = $active ?? '';

function tabClass(string $key, string $active): string {
  return $key === $active ? 'tab is-active' : 'tab';
}
?>
<div class="header">
  <div class="header-inner">
    <a class="brand" href="/timeline.php">MiniSNS</a>
    <div class="top-links">
      <a href="/timeline.php">タイムライン</a>
      <a href="/users.php">会員一覧</a>
      <a href="/profile.php">プロフィール</a>
      <a href="/setting/index.php">設定</a>
    </div>
  </div>
</div>

<div class="bottom-nav">
  <div class="bottom-nav-inner">
    <a class="<?= tabClass('timeline', $active) ?>" href="/timeline.php">TL</a>
    <a class="<?= tabClass('users', $active) ?>" href="/users.php">会員</a>
    <a class="<?= tabClass('profile', $active) ?>" href="/profile.php">自分</a>
    <a class="<?= tabClass('setting', $active) ?>" href="/setting/index.php">設定</a>
  </div>
</div>


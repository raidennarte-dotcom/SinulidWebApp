<?php
$isAuth  = ($layout ?? 'app') === 'auth';
$cssFile = $isAuth ? 'css/style.css' : 'css/MainStyle.css';
$ver = function (string $file): int {
    $path = BASE_PATH . '/public/assets/' . $file;
    return is_file($path) ? (int) filemtime($path) : 0;
};
?>
<!doctype html>
<html lang="en" data-theme="light">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>" />
    <title><?= e($pageTitle ?? 'Sinulid') ?> | Sinulid</title>
    <link rel="icon" type="image/png" href="<?= e(asset('images/logo2.png')) ?>" />
    <link rel="stylesheet" href="<?= e(asset($cssFile)) ?>?v=<?= $ver($cssFile) ?>" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
<?php if (!$isAuth): ?>
    <script>
      (function () {
        try {
          var t = localStorage.getItem("platform-theme");
          if (t !== "dark" && t !== "light") {
            t = window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
          }
          document.documentElement.setAttribute("data-theme", t);
        } catch (e) {}
      })();
    </script>
<?php endif; ?>
    <script src="<?= e(asset('js/script.js')) ?>?v=<?= $ver('js/script.js') ?>" defer></script>
  </head>
<?php if ($isAuth): ?>
  <body>
<?php else: ?>
  <body class="app" data-logged-in="<?= $currentUser ? 'true' : 'false' ?>">

    <header class="topbar">
      <a href="<?= e(url('feed')) ?>" class="topbar__brand">Sinulid</a>

      <form class="search" role="search" action="<?= e(base_url()) ?>/index.php" method="get">
        <input type="hidden" name="r" value="search" />
        <label class="visually-hidden" for="search-input">Search people and posts</label>
        <input
          class="search__input"
          type="search"
          id="search-input"
          name="q"
          maxlength="100"
          placeholder="Search people and posts"
          value="<?= e($q ?? '') ?>"
        />
      </form>

      <?php if ($currentUser): ?>
      <?php require BASE_PATH . '/app/views/partials/notifications.php'; ?>
      <button
        type="button"
        class="profile-toggle"
        id="profile-toggle"
        aria-expanded="false"
        aria-controls="profile-panel"
        aria-label="Open profile"
      >
        <span class="profile-toggle__avatar" id="toggle-avatar"><?= avatar_html($currentUser) ?></span>
      </button>
      <?php endif; ?>
    </header>

    <div class="flash-wrap">
      <?php require BASE_PATH . '/app/views/partials/flash.php'; ?>
    </div>

    <div class="dashboard">
<?php endif; ?>
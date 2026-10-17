<?php
$isAuth = ($layout ?? 'app') === 'auth';
?>
<?php if (!$isAuth): ?>
<?php if ($currentUser): ?>
<?php
  $anonName  = trim((string) ($currentUser['anon_name'] ?? ''));
  $anonOn    = (int) ($currentUser['anon_mode'] ?? 0) === 1;
  $anonNext  = '?' . http_build_query($_GET);
?>
      <aside class="profile-panel" id="profile-panel" aria-labelledby="profile-title" hidden>

        <h2 class="profile-panel__title" id="profile-title">Profile</h2>
        <div class="profile-panel__picture" id="user-avatar"><?= avatar_html($currentUser) ?></div>

        <dl class="profile-panel__details">
          <div class="profile-panel__row">
            <dt>Full Name</dt>
            <dd id="user-full-name"><?= e(display_name($currentUser)) ?></dd>
          </div>
          <div class="profile-panel__row">
            <dt>Username</dt>
            <dd id="user-username">@<?= e($currentUser['username']) ?></dd>
          </div>
          <div class="profile-panel__row">
            <dt>Email Address</dt>
            <dd id="user-email"><?= e($currentUser['email']) ?></dd>
          </div>
          <div class="profile-panel__row">
            <dt>Member Since</dt>
            <dd id="user-since"><?= e(date('F j, Y', strtotime($currentUser['created_at']))) ?></dd>
          </div>
        </dl>

        <a class="profile-panel__link" href="<?= e(url('profile')) ?>">
          <i class="bi bi-person-circle" aria-hidden="true"></i> View my profile
        </a>

        <div class="settings-group">
          <button type="button" class="settings-group__toggle" id="settings-toggle"
                  aria-expanded="false" aria-controls="settings-menu">
            <span>Settings</span>
            <span class="settings-group__chevron" aria-hidden="true"></span>
          </button>
          <ul class="settings-menu" id="settings-menu" hidden>
            <li>
              <a class="settings-menu__item" id="edit-profile-button" href="<?= e(url('profile/edit')) ?>">
                <i class="bi bi-pencil-square" aria-hidden="true"></i> Edit Profile
              </a>
            </li>
            <li>
              <form action="<?= e(url('auth/logout')) ?>" method="post">
                <?= csrf_field() ?>
                <button type="submit" class="settings-menu__item">
                  <i class="bi bi-box-arrow-right" aria-hidden="true"></i> Log out
                </button>
              </form>
            </li>
          </ul>
        </div>

        <div class="anon-setting" id="anon-setting">
          <div class="anon-setting__row">
            <div>
              <h3 class="profile-panel__settings-title">Anonymous mode</h3>
              <p class="field-hint">
                <?php if ($anonOn): ?>
                  On. Posting as <?= e($anonName) ?>
                <?php elseif ($anonName !== ''): ?>
                  Off. Your real name is shown.
                <?php else: ?>
                  Create an anonymous name in Edit Profile first.
                <?php endif; ?>
              </p>
            </div>
            <button type="button" class="theme-switch" id="anon-toggle"
                    aria-pressed="<?= $anonOn ? 'true' : 'false' ?>" aria-label="Anonymous mode">
              <span class="theme-switch__knob" aria-hidden="true"></span>
            </button>
          </div>

          <div class="anon-confirm" id="anon-confirm" hidden>
            <?php if ($anonName === ''): ?>
              <p class="anon-confirm__text">Create your anonymous name in Edit Profile before turning this on.</p>
              <div class="anon-confirm__actions">
                <button type="button" class="btn btn--ghost" data-anon-cancel>Cancel</button>
                <a class="btn" href="<?= e(url('profile/edit')) ?>">Edit Profile</a>
              </div>
            <?php else: ?>
              <p class="anon-confirm__text">
                <?= $anonOn
                  ? 'Are you sure you want to turn off anonymous mode? Your new posts will show your real name.'
                  : 'Are you sure you want to make this account anonymous?' ?>
              </p>
              <form class="anon-confirm__actions" action="<?= e(url('anon/toggle')) ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="mode" value="<?= $anonOn ? 'off' : 'on' ?>">
                <input type="hidden" name="next" value="<?= e($anonNext) ?>">
                <button type="button" class="btn btn--ghost" data-anon-cancel>Cancel</button>
                <button type="submit" class="btn">Yes, continue</button>
              </form>
            <?php endif; ?>
          </div>
        </div>

        <div class="profile-panel__settings">
          <h3 class="profile-panel__settings-title">Light mode</h3>
          <button type="button" class="theme-switch" id="theme-toggle"
                  aria-pressed="false" aria-label="Light mode">
            <span class="theme-switch__knob" aria-hidden="true"></span>
          </button>
        </div>
      </aside>
<?php endif; ?>
    </div>
<?php endif; ?>
  </body>

  <script>
  (function () {
    var toggle = document.getElementById('anon-toggle');
    var box = document.getElementById('anon-confirm');
    if (!toggle || !box) return;

    var original = toggle.getAttribute('aria-pressed') === 'true';

    function setOpen(open) {
      box.hidden = !open;
      if (!open) {
        toggle.setAttribute('aria-pressed', String(original));
      } else if (box.querySelector('form')) {
        toggle.setAttribute('aria-pressed', String(!original));
      }
    }

    toggle.addEventListener('click', function () {
      setOpen(box.hidden);
    });

    document.querySelectorAll('[data-anon-cancel]').forEach(function (button) {
      button.addEventListener('click', function () {
        setOpen(false);
      });
    });
  })();
</script>
</html>
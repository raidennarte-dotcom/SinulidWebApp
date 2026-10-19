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
    var LIMITS = { post: 4 * 1024 * 1024, avatar: 2 * 1024 * 1024 };
    var TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    function check(file, limit) {
      if (file.type && TYPES.indexOf(file.type) === -1) {
        return 'Only JPG, PNG, GIF or WEBP images are allowed.';
      }
      if (file.size > limit) {
        return 'Image must be ' + Math.round(limit / 1048576) + ' MB or smaller.';
      }
      return '';
    }

    /* ---------- Post box on the feed ---------- */
    var postInput = document.getElementById('composer-files');
    if (postInput) {
      var box = document.createElement('div');
      box.className = 'photo-review';
      box.hidden = true;
      box.innerHTML =
        '<img class="photo-review__img" alt="Photo preview" hidden>' +
        '<button type="button" class="btn btn--ghost photo-review__remove" hidden>Remove photo</button>' +
        '<p class="photo-review__note is-error" role="alert" hidden></p>';

      var anchor = postInput.closest('.composer__upload') || postInput.parentElement;
      anchor.insertAdjacentElement('afterend', box);

      var img = box.querySelector('img');
      var remove = box.querySelector('button');
      var note = box.querySelector('p');
      var postUrl = null;

      function clearPost() {
        if (postUrl) { URL.revokeObjectURL(postUrl); postUrl = null; }
        img.removeAttribute('src');
        img.hidden = true;
        remove.hidden = true;
        note.hidden = true;
        box.hidden = true;
      }

      postInput.addEventListener('change', function () {
        clearPost();
        var file = postInput.files[0];
        if (!file) return;

        var problem = check(file, LIMITS.post);
        if (problem) {
          postInput.value = '';
          note.textContent = problem;
          note.hidden = false;
          box.hidden = false;
          return;
        }
        postUrl = URL.createObjectURL(file);
        img.src = postUrl;
        img.hidden = false;
        remove.hidden = false;
        box.hidden = false;
      });

      remove.addEventListener('click', function () {
        postInput.value = '';
        clearPost();
        postInput.dispatchEvent(new Event('change'));
      });
    }

    /* ---------- Profile picture on Edit profile ---------- */
    var avatarInput = document.querySelector('input[type="file"][name="profile_image"]');
    if (avatarInput) {
      var wrap = avatarInput.closest('.profile-edit-avatar');
      var circle = null;

      if (wrap) {
        for (var i = 0; i < wrap.children.length; i++) {
          if (!wrap.children[i].contains(avatarInput)) { circle = wrap.children[i]; break; }
        }
      }
      var madeCircle = false;
      if (!circle) {
        circle = document.createElement('div');
        circle.className = 'photo-review__avatar-box';
        circle.hidden = true;
        avatarInput.insertAdjacentElement('afterend', circle);
        madeCircle = true;
      }

      var original = circle.innerHTML;
      var avatarNote = document.createElement('p');
      avatarNote.className = 'photo-review__note';
      avatarNote.setAttribute('role', 'status');
      avatarNote.hidden = true;
      avatarInput.insertAdjacentElement('afterend', avatarNote);

      var avatarUrl = null;

      function restoreAvatar() {
        if (avatarUrl) { URL.revokeObjectURL(avatarUrl); avatarUrl = null; }
        circle.innerHTML = original;
        if (madeCircle) circle.hidden = true;
      }

      avatarInput.addEventListener('change', function () {
        avatarNote.hidden = true;
        avatarNote.classList.remove('is-error');
        restoreAvatar();

        var file = avatarInput.files[0];
        if (!file) return;

        var problem = check(file, LIMITS.avatar);
        if (problem) {
          avatarInput.value = '';
          avatarNote.textContent = problem;
          avatarNote.classList.add('is-error');
          avatarNote.hidden = false;
          return;
        }

        avatarUrl = URL.createObjectURL(file);
        circle.innerHTML = '<img class="photo-review__avatar" alt="New profile picture preview">';
        circle.firstChild.src = avatarUrl;
        if (madeCircle) circle.hidden = false;

        avatarNote.textContent = 'Preview only. Click Save profile to keep this picture.';
        avatarNote.hidden = false;
      });
    }
  })();

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
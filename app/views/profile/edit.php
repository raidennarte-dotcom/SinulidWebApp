<main class="feed-column">
  <div class="feed__list">
    <section class="form-card" aria-labelledby="edit-profile-title">
      <h1 class="form-card__title" id="edit-profile-title">Edit profile</h1>
      <?php require BASE_PATH . '/app/views/partials/errors.php'; ?>

      <form class="composer__form" action="<?= e(url('profile/update')) ?>" method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <div class="profile-edit-avatar">
          <div class="profile-card__avatar"><?= avatar_html($currentUser) ?></div>
          <div class="form-field">
            <label for="profile_image">Profile picture</label>
            <input type="file" id="profile_image" name="profile_image" accept="image/jpeg,image/png,image/gif,image/webp">
            <?php if (!empty($currentUser['profile_image'])): ?>
              <label class="check-row"><input type="checkbox" name="remove_image" value="1"> Remove current picture</label>
            <?php endif; ?>
          </div>
        </div>

        <div class="form-field">
          <label for="full_name">Full name</label>
            <input class="text-input" type="text" id="anon-name" name="anon_name"
             minlength="3" maxlength="30" pattern="[A-Za-z0-9_]{3,30}"
             title="3 to 30 characters: letters, numbers and underscores. Use _ instead of spaces."
             placeholder="e.g. Midnight_Owl"
             value="<?= e($currentUser['anon_name'] ?? '') ?>" required>
        </div>

        <div class="form-field">
          <label for="username_display">Username</label>
          <input class="text-input" type="text" id="username_display" value="@<?= e($currentUser['username']) ?>" disabled>
          <small class="field-hint">Usernames cannot be changed.</small>
        </div>

        <div class="form-field">
          <label for="bio">Bio</label>
          <textarea class="composer__textarea" id="bio" name="bio" rows="4" maxlength="300"
                    placeholder="Tell people a little about yourself"><?= e($form['bio']) ?></textarea>
        </div>

        <div class="form-actions">
          <a class="btn btn--ghost" href="<?= e(url('profile')) ?>">Cancel</a>
          <button type="submit" class="composer__submit">Save profile</button>
        </div>
      </form>
      <section class="anon-card" aria-labelledby="anon-card-title">
  <h2 class="form-card__title" id="anon-card-title">Anonymous account</h2>
  <p class="field-hint">
    Pick the name people see when anonymous mode is on. They never see your real name, username or picture.
  </p>
  <form class="anon-card__form" action="<?= e(url('anon/name')) ?>" method="post">
    <?= csrf_field() ?>
    <div class="form-field">
      <label for="anon-name">Anonymous name</label>
      <input class="text-input" type="text" id="anon-name" name="anon_name"
             minlength="3" maxlength="30" placeholder="E.g pitsenyur deha mega"
             value="<?= e($currentUser['anon_name'] ?? '') ?>" required>
    </div>
    <div class="form-actions">
      <button type="submit" class="btn">Save anonymous name</button>
    </div>
  </form>
</section>
    </section>
  </div>
</main>

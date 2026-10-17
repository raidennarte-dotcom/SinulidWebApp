<main class="feed-column">
  <section class="feed" aria-label="Profile">
    <div class="feed__list">
      <section class="profile-card">
        <div class="profile-card__avatar"><?= avatar_html($profileUser) ?></div>
        <div class="profile-card__info">
          <h1 class="profile-card__name"><?= e($profileUser['full_name']) ?></h1>
          <?php require BASE_PATH . '/app/views/partials/follow-box.php'; ?>
          <p class="profile-card__username">@<?= e($profileUser['username']) ?></p>
          <?php if (!empty($profileUser['bio'])): ?>
            <p class="profile-card__bio"><?= nl2br(e($profileUser['bio'])) ?></p>
          <?php else: ?>s
            <p class="profile-card__bio profile-card__bio--empty">No bio yet.</p>
          <?php endif; ?>
          <p class="profile-card__stats">
            <?= count($posts) ?> <?= count($posts) === 1 ? 'post' : 'posts' ?>
            &middot; Joined <?= e(date('F Y', strtotime($profileUser['created_at']))) ?>
          </p>
          <?php if ($isOwner): ?>
            <a class="btn" href="<?= e(url('profile/edit')) ?>"><i class="bi bi-pencil-square" aria-hidden="true"></i> Edit profile</a>
          <?php endif; ?>
        </div>
      </section>

      <h2 class="section-title">Posts</h2>

      <?php if ($posts === []): ?>
        <div class="empty-state">
          <i class="bi bi-chat-square-text" aria-hidden="true"></i>
          <h2>No posts yet</h2>
          <p><?= $isOwner ? 'Share your first post from the home page.' : e($profileUser['full_name']) . ' has not posted anything yet.' ?></p>
        </div>
      <?php else: ?>
        <div id="feed-list" class="profile-posts">
          <?php foreach ($posts as $post): ?>
            <?php require BASE_PATH . '/app/views/partials/post.php'; ?>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>
</main>

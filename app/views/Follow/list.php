<?php
$name        = display_name($profileUser);
$isFollowers = $type === 'followers';
$uParam      = ['u' => $profileUser['username']];
?>
<main class="feed-column">
  <section class="feed" aria-label="<?= $isFollowers ? 'Followers' : 'Following' ?>">
    <div class="feed__list">
      <p class="back-link">
        <a href="<?= e(url('profile', $uParam)) ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back to <?= e($name) ?></a>
      </p>

      <h1 class="section-title"><?= e($name) ?></h1>

      <nav class="follow-tabs" aria-label="Followers and following">
        <a class="follow-tabs__item<?= $isFollowers ? ' is-active' : '' ?>"
           href="<?= e(url('follow/followers', $uParam)) ?>">
          <strong><?= (int) $followerCount ?></strong> <?= (int) $followerCount === 1 ? 'Follower' : 'Followers' ?>
        </a>
        <a class="follow-tabs__item<?= $isFollowers ? '' : ' is-active' ?>"
           href="<?= e(url('follow/following', $uParam)) ?>">
          <strong><?= $followingCount === null ? '&mdash;' : (int) $followingCount ?></strong> Following
        </a>
      </nav>

      <?php if (!empty($listHidden)): ?>
        <div class="empty-state">
          <i class="bi bi-lock" aria-hidden="true"></i>
          <h2>This list is private</h2>
        </div>
      <?php elseif ($list === []): ?>
        <div class="empty-state">
          <i class="bi bi-people" aria-hidden="true"></i>
          <h2><?= $isFollowers ? 'No followers yet' : 'Not following anyone yet' ?></h2>
          <p>
            <?php if ($isOwner): ?>
              <?= $isFollowers ? 'When people follow you, they will show up here.' : 'Find people with the search bar and follow them.' ?>
            <?php else: ?>
              <?= e($name) ?> <?= $isFollowers ? 'has no followers yet.' : 'is not following anyone yet.' ?>
            <?php endif; ?>
          </p>
        </div>
      <?php else: ?>
        <ul class="user-list">
          <?php foreach ($list as $row): ?>
            <li>
              <a class="user-card" href="<?= e(url('profile', ['u' => $row['username']])) ?>">
                <span class="user-card__avatar"><?= avatar_html($row) ?></span>
                <span class="user-card__text">
                  <strong><?= e(display_name($row)) ?></strong>
                  <span class="post__username">@<?= e($row['username']) ?></span>
                  <?php if (!empty($row['bio'])): ?>
                    <span class="user-card__bio"><?= e($row['bio']) ?></span>
                  <?php endif; ?>
                </span>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </section>
</main>
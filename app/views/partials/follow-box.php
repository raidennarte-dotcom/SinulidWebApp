<?php
$fCount = (int) ($followerCount ?? 0);
$gCount = $followingCount ?? null;
$uParam = ['u' => $profileUser['username']];
?>
<p class="follow-stats">
  <a href="<?= e(url('follow/followers', $uParam)) ?>">
    <strong><?= $fCount ?></strong> <?= $fCount === 1 ? 'Follower' : 'Followers' ?>
  </a>
  <?php if ($gCount === null): ?>
    <span><strong>&mdash;</strong> Following</span>
  <?php else: ?>
    <a href="<?= e(url('follow/following', $uParam)) ?>">
      <strong><?= (int) $gCount ?></strong> Following
    </a>
  <?php endif; ?>
</p>

<?php if (empty($isOwner)): ?>
  <?php if (!empty($viewerAnonymous)): ?>
    <p class="field-hint">Turn off anonymous mode to follow people.</p>
  <?php else: ?>
    <form class="follow-form" action="<?= e(url('follow/toggle')) ?>" method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="user_id" value="<?= (int) $profileUser['id'] ?>">
      <input type="hidden" name="next" value="<?= e('?' . http_build_query(['r' => 'profile', 'u' => $profileUser['username']])) ?>">
      <?php if (!empty($isFollowing)): ?>
        <button type="submit" class="btn btn--ghost">Following</button>
      <?php else: ?>
        <button type="submit" class="btn">Follow</button>
      <?php endif; ?>
    </form>
  <?php endif; ?>
<?php endif; ?>
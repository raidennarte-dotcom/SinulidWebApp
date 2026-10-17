<?php
$pid        = (int) $post['id'];
$isAnon     = !empty($post['is_anonymous']);
$isMine     = !empty($post['is_mine']);
$comments   = $post['comments'] ?? [];
$liked      = (int) $post['liked_by_me'] === 1;
$open       = open_post_id() === $pid;
$commentNext = current_next($pid);
$backQuery  = '?' . http_build_query($_GET);
$edited     = (strtotime($post['updated_at']) - strtotime($post['created_at'])) > 5;
$profileUrl = $isAnon ? '' : url('profile', ['u' => $post['username']]);

$isRepost  = !empty($post['repost_of']);
$origAnon  = !empty($post['orig_is_anonymous']);
$orig      = [
    'username'      => $post['orig_username'] ?? '',
    'full_name'     => $post['orig_full_name'] ?? '',
    'profile_image' => $post['orig_profile_image'] ?? null,
    'is_anonymous'  => $origAnon ? 1 : 0,
];
?>


<article class="post" id="post-<?= $pid ?>">
  <header class="post__header">
    <?php if ($isAnon): ?>
      <span class="post__avatar" aria-hidden="true"><?= avatar_html($post) ?></span>
    <?php else: ?>
      <a class="post__avatar" href="<?= e($profileUrl) ?>" aria-label="View <?= e(display_name($post)) ?>'s profile">
        <?= avatar_html($post) ?>
      </a>
    <?php endif; ?>

    <div class="post__meta">
      <p class="post__author">
        <?php if ($isAnon): ?>
          <span class="post__author-link"><?= e(display_name($post)) ?></span>
          <span class="post__anon-badge"><i class="bi bi-incognito" aria-hidden="true"></i> <?= $isMine ? 'You (anonymous)' : 'Anonymous' ?></span>
        <?php else: ?>
          <a class="post__author-link" href="<?= e($profileUrl) ?>"><?= e(display_name($post)) ?></a>
          <span class="post__username">@<?= e($post['username']) ?></span>
        <?php endif; ?>
      </p>
      <time class="post__time" datetime="<?= e($post['created_at']) ?>" title="<?= e($post['created_at']) ?>"><?= e(time_ago($post['created_at'])) ?></time>
      <?php if ($edited): ?><span class="post__edited">&middot; edited</span><?php endif; ?>
    </div>

    <?php if ($isMine): ?>
      <details class="post__more">
        <summary class="post__more-btn" aria-label="More options">
          <i class="bi bi-three-dots" aria-hidden="true"></i>
        </summary>

        <div class="post__more-menu" role="menu">
          <?php if (!$isRepost): ?>
            <a class="post__more-item" role="menuitem" href="<?= e(url('post/edit', ['id' => $pid])) ?>">
              <i class="bi bi-pencil" aria-hidden="true"></i> Edit
            </a>
          <?php endif; ?>
          <form action="<?= e(url('post/delete')) ?>" method="post" data-confirm="Delete this post and all of its comments?">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $pid ?>">
            <input type="hidden" name="next" value="<?= e($backQuery) ?>">
            <button type="submit" class="post__more-item post__more-item--danger" role="menuitem">
              <i class="bi bi-trash" aria-hidden="true"></i> Delete
            </button>
          </form>
        </div>
      </details>
    <?php endif; ?>
  </header>

  <?php if ($isRepost): ?>
    <p class="post__repost-label"><i class="bi bi-arrow-repeat" aria-hidden="true"></i> Shared a post</p>
    <div class="repost-card">
      <?php if ($origAnon): ?>
        <span class="repost-card__author">
          <span class="repost-card__avatar"><?= avatar_html($orig) ?></span>
          <strong><?= e(display_name($orig)) ?></strong>
          <span class="post__anon-badge"><i class="bi bi-incognito" aria-hidden="true"></i> Anonymous</span>
        </span>
      <?php else: ?>
        <a class="repost-card__author" href="<?= e(url('profile', ['u' => $orig['username']])) ?>">
          <span class="repost-card__avatar"><?= avatar_html($orig) ?></span>
          <strong><?= e(display_name($orig)) ?></strong>
          <span class="post__username">@<?= e($orig['username']) ?></span>
        </a>
      <?php endif; ?>
      <?php if (($post['orig_content'] ?? '') !== ''): ?>
        <div class="post__body"><?= nl2br(e($post['orig_content'])) ?></div>
      <?php endif; ?>
      <?php if (!empty($post['orig_image'])): ?>
        <img class="post__image" src="<?= e(upload_url($post['orig_image'])) ?>" alt="Shared photo" loading="lazy">
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="post__body"><?= nl2br(e($post['content'])) ?></div>

    <?php if (!empty($post['image'])): ?>
      <a href="<?= e(upload_url($post['image'])) ?>" target="_blank" rel="noopener">
        <img class="post__image" src="<?= e(upload_url($post['image'])) ?>"
             alt="Photo attached to <?= e(display_name($post)) ?>'s post" loading="lazy">
      </a>
    <?php endif; ?>
  <?php endif; ?>

  <footer class="post__actions">
    <button type="button" class="post__action" data-action="like" data-post-id="<?= $pid ?>"
            data-url="<?= e(url('like/toggle')) ?>"
            aria-pressed="<?= $liked ? 'true' : 'false' ?>">
      <i class="bi <?= $liked ? 'bi-heart-fill' : 'bi-heart' ?>" aria-hidden="true"></i>
      <span class="post__action-label">Like</span>
      <span class="post__count"><?= (int) $post['like_count'] ?></span>
    </button>

    <button type="button" class="post__action" data-action="comment"
            aria-expanded="<?= $open ? 'true' : 'false' ?>" aria-controls="post-<?= $pid ?>-comments">
      <i class="bi bi-chat" aria-hidden="true"></i>
      <span class="post__action-label">Comment</span>
      <span class="post__count"><?= count($comments) ?></span>
    </button>

    <div class="post__share">
      <button type="button" class="post__action" data-action="share" aria-haspopup="menu" aria-expanded="false">
        <i class="bi bi-send" aria-hidden="true"></i>
        <span class="post__action-label">Share</span>
      </button>

      <div class="share-menu" role="menu" hidden>
        <form action="<?= e(url('post/repost')) ?>" method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= $pid ?>">
          <input type="hidden" name="next" value="<?= e($backQuery) ?>">
          <button type="submit" class="share-menu__item" role="menuitem">Share to my feed</button>
        </form>
        <button type="button" class="share-menu__item" role="menuitem" data-share="copy">Copy link</button>
      </div>
    </div>
  </footer>

  <section class="post__comments" id="post-<?= $pid ?>-comments" aria-label="Comments"<?= $open ? '' : ' hidden' ?>>
    <?php if ($comments === []): ?>
      <p class="post__no-comments">No comments yet. Be the first to reply.</p>
    <?php else: ?>
      <ul class="post__comment-list">
        <?php foreach ($comments as $c): ?>
          <?php
            $cMine   = (int) $c['user_id'] === (int) $currentUser['id'];
            $cEdited = (strtotime($c['updated_at']) - strtotime($c['created_at'])) > 5;
          ?>
          <li class="post__comment" id="comment-<?= (int) $c['id'] ?>">
            <a class="post__comment-avatar" href="<?= e(url('profile', ['u' => $c['username']])) ?>"
               aria-label="View <?= e(display_name($c)) ?>'s profile"><?= avatar_html($c) ?></a>
            <div class="post__comment-main">
              <div class="post__comment-bubble">
                <p class="post__comment-name">
                  <?= e(display_name($c)) ?> <span class="post__username">@<?= e($c['username']) ?></span>
                </p>
                <p class="post__comment-text"><?= nl2br(e($c['content'])) ?></p>
              </div>
              <div class="post__comment-meta">
                <time class="post__comment-time" datetime="<?= e($c['created_at']) ?>" title="<?= e($c['created_at']) ?>"><?= e(time_ago($c['created_at'])) ?></time>
                <?php if ($cEdited): ?><span class="post__edited">&middot; edited</span><?php endif; ?>
                <?php if ($cMine): ?>
                  <a class="post__tool post__tool--small"
                     href="<?= e(url('comment/edit', ['id' => (int) $c['id'], 'next' => $commentNext])) ?>">Edit</a>
                  <form action="<?= e(url('comment/delete')) ?>" method="post" data-confirm="Delete this comment?">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                    <input type="hidden" name="next" value="<?= e($commentNext) ?>">
                    <button type="submit" class="post__tool post__tool--small post__tool--danger">Delete</button>
                  </form>
                <?php endif; ?>
              </div>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <form class="post__comment-form" action="<?= e(url('comment/store')) ?>" method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="post_id" value="<?= $pid ?>">
      <input type="hidden" name="next" value="<?= e($commentNext) ?>">
      <label class="visually-hidden" for="comment-input-<?= $pid ?>">Write a comment</label>
      <input class="post__comment-input" type="text" id="comment-input-<?= $pid ?>" name="content"
             maxlength="500" required placeholder="Write a comment..." autocomplete="off">
      <button type="submit" class="post__comment-submit">Send</button>
    </form>
  </section>
</article>
<?php
$notifModel  = new NotificationModel(db());
$notifUnread = $notifModel->unreadCount((int) $currentUser['id']);
$notifItems  = $notifModel->latest((int) $currentUser['id']);
$notifText   = [
    'follow'  => 'started following you',
    'like'    => 'liked your post',
    'comment' => 'commented on your post',
    'share'   => 'shared your post',
];
?>
<div class="notif" id="notif">
  <button type="button" class="notif__btn" id="notif-toggle" aria-haspopup="true"
          aria-expanded="false" aria-controls="notif-panel" aria-label="Notifications"
          data-url="<?= e(url('notifications/read')) ?>">
    <i class="bi bi-bell" aria-hidden="true"></i>
    <span class="notif__badge" id="notif-badge"<?= $notifUnread > 0 ? '' : ' hidden' ?>><?= $notifUnread > 99 ? '99+' : $notifUnread ?></span>
  </button>

  <div class="notif__panel" id="notif-panel" hidden>
    <h2 class="notif__title">Notifications</h2>
    <?php if ($notifItems === []): ?>
      <p class="notif__empty">Nothing yet.</p>
    <?php else: ?>
      <ul class="notif__list">
        <?php foreach ($notifItems as $n): ?>
          <?php
            $anonActor = (int) $n['actor_anonymous'] === 1;
            if ($n['type'] === 'follow' && !$anonActor && $n['actor_username'] !== null) {
                $href = url('profile', ['u' => $n['actor_username']]);
            } elseif (!empty($n['post_id'])) {
                $href = url('feed', ['open' => (int) $n['post_id']]) . '#post-' . (int) $n['post_id'];
            } else {
                $href = url('feed');
            }
            $actor = [
                'is_anonymous'  => $anonActor ? 1 : 0,
                'full_name'     => $n['actor_name'],
                'profile_image' => $n['actor_image'],
            ];
          ?>
          <li>
            <a class="notif__item<?= (int) $n['is_read'] === 0 ? ' is-unread' : '' ?>" href="<?= e($href) ?>">
              <span class="notif__avatar"><?= avatar_html($actor) ?></span>
              <span class="notif__text">
                <strong><?= e($n['actor_name']) ?></strong>
                <?= e($notifText[$n['type']] ?? 'sent you a notification') ?>
                <time class="notif__time"><?= e(time_ago($n['created_at'])) ?></time>
              </span>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</div>

<script>
  (function () {
    var btn = document.getElementById('notif-toggle');
    var panel = document.getElementById('notif-panel');
    var badge = document.getElementById('notif-badge');
    if (!btn || !panel) return;

    function markRead() {
      var meta = document.querySelector('meta[name="csrf-token"]');
      var token = meta ? meta.getAttribute('content') : '';
      var body = new URLSearchParams();
      body.set('csrf_token', token);
      fetch(btn.dataset.url, {
        method: 'POST',
        headers: { 'X-CSRF-Token': token, Accept: 'application/json' },
        body: body,
        credentials: 'same-origin'
      }).then(function (r) { return r.json(); })
        .then(function (d) { if (d && d.ok && badge) badge.hidden = true; })
        .catch(function () {});
    }

    function setOpen(open) {
      panel.hidden = !open;
      btn.setAttribute('aria-expanded', String(open));
      if (open && badge && !badge.hidden) markRead();
    }

    btn.addEventListener('click', function () { setOpen(panel.hidden); });
    document.addEventListener('click', function (e) {
      if (!panel.hidden && !e.target.closest('#notif')) setOpen(false);
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') setOpen(false);
    });
  })();
</script>
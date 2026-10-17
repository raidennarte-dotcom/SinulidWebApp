<?php
declare(strict_types=1);

class NotificationModel
{
    public function __construct(private PDO $pdo) {}

    public function create(int $userId, int $actorId, bool $anon, string $type, ?int $postId = null): void
    {
        if ($userId === $actorId) {
            return;
        }

        // Skip if the same unread notification already exists (stops like spam).
        $sql    = 'SELECT 1 FROM notifications
                    WHERE user_id = :u AND actor_id = :a AND type = :t AND is_read = 0';
        $params = [':u' => $userId, ':a' => $actorId, ':t' => $type];
        if ($postId === null) {
            $sql .= ' AND post_id IS NULL';
        } else {
            $sql .= ' AND post_id = :p';
            $params[':p'] = $postId;
        }
        $st = $this->pdo->prepare($sql . ' LIMIT 1');
        $st->execute($params);
        if ($st->fetchColumn()) {
            return;
        }

        $st = $this->pdo->prepare(
            'INSERT INTO notifications (user_id, actor_id, actor_anonymous, type, post_id)
             VALUES (:u, :a, :anon, :t, :p)'
        );
        $st->bindValue(':u', $userId, PDO::PARAM_INT);
        $st->bindValue(':a', $actorId, PDO::PARAM_INT);
        $st->bindValue(':anon', $anon ? 1 : 0, PDO::PARAM_INT);
        $st->bindValue(':t', $type);
        $st->bindValue(':p', $postId, $postId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $st->execute();
    }

    public function notifyPostOwner(int $postId, int $actorId, bool $anon, string $type): void
    {
        $st = $this->pdo->prepare('SELECT user_id FROM posts WHERE id = :id');
        $st->execute([':id' => $postId]);
        $owner = $st->fetchColumn();
        if ($owner !== false) {
            $this->create((int) $owner, $actorId, $anon, $type, $postId);
        }
    }

    /** $newPostId is the repost; the person to notify is the owner of the original. */
    public function notifyShare(int $newPostId, int $actorId, bool $anon): void
    {
        $st = $this->pdo->prepare('SELECT repost_of FROM posts WHERE id = :id');
        $st->execute([':id' => $newPostId]);
        $originalId = (int) $st->fetchColumn();
        if ($originalId > 0) {
            $this->notifyPostOwner($originalId, $actorId, $anon, 'share');
        }
    }

    public function unreadCount(int $userId): int
    {
        $st = $this->pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = :u AND is_read = 0');
        $st->execute([':u' => $userId]);
        return (int) $st->fetchColumn();
    }

    public function latest(int $userId, int $limit = 20): array
    {
        $st = $this->pdo->prepare(
            "SELECT n.id, n.type, n.post_id, n.is_read, n.actor_anonymous, n.created_at,
                    CASE WHEN n.actor_anonymous = 1 THEN COALESCE(a.anon_name, 'Anonymous')
                         ELSE COALESCE(NULLIF(a.full_name, ''), a.username) END AS actor_name,
                    CASE WHEN n.actor_anonymous = 1 THEN NULL ELSE a.username END AS actor_username,
                    CASE WHEN n.actor_anonymous = 1 THEN NULL ELSE a.profile_image END AS actor_image
               FROM notifications n
               JOIN users a ON a.id = n.actor_id
              WHERE n.user_id = :u
              ORDER BY n.id DESC
              LIMIT :lim"
        );
        $st->bindValue(':u', $userId, PDO::PARAM_INT);
        $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

    public function markAllRead(int $userId): void
    {
        $st = $this->pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = :u AND is_read = 0');
        $st->execute([':u' => $userId]);
    }
}
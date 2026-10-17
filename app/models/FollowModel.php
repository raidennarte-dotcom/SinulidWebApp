<?php
declare(strict_types=1);

class FollowModel
{
    public function __construct(private PDO $pdo) {}

    public function follow(int $followerId, int $followeeId): void
    {
        $st = $this->pdo->prepare(
            'INSERT IGNORE INTO follows (follower_id, followee_id) VALUES (:a, :b)'
        );
        $st->execute([':a' => $followerId, ':b' => $followeeId]);
    }

    public function unfollow(int $followerId, int $followeeId): void
    {
        $st = $this->pdo->prepare('DELETE FROM follows WHERE follower_id = :a AND followee_id = :b');
        $st->execute([':a' => $followerId, ':b' => $followeeId]);
    }

    public function isFollowing(int $followerId, int $followeeId): bool
    {
        $st = $this->pdo->prepare(
            'SELECT 1 FROM follows WHERE follower_id = :a AND followee_id = :b LIMIT 1'
        );
        $st->execute([':a' => $followerId, ':b' => $followeeId]);
        return (bool) $st->fetchColumn();
    }

    /** Followers the viewer is allowed to see (anonymous ones are hidden). */
    public function followerCount(int $userId, int $viewerId): int
    {
        $st = $this->pdo->prepare(
            'SELECT COUNT(*)
               FROM follows f
               JOIN users u ON u.id = f.follower_id
              WHERE f.followee_id = :id AND (u.anon_mode = 0 OR f.follower_id = :viewer)'
        );
        $st->execute([':id' => $userId, ':viewer' => $viewerId]);
        return (int) $st->fetchColumn();
    }

    /** True when $userId is in anonymous mode and someone else is looking. */
    public function followingHidden(int $userId, int $viewerId): bool
    {
        if ($userId === $viewerId) {
            return false;
        }
        $st = $this->pdo->prepare('SELECT anon_mode FROM users WHERE id = :id');
        $st->execute([':id' => $userId]);
        return (int) $st->fetchColumn() === 1;
    }

    /** Returns null when the list is private to the viewer. */
    public function followingCount(int $userId, int $viewerId): ?int
    {
        if ($this->followingHidden($userId, $viewerId)) {
            return null;
        }
        $st = $this->pdo->prepare('SELECT COUNT(*) FROM follows WHERE follower_id = :id');
        $st->execute([':id' => $userId]);
        return (int) $st->fetchColumn();
    }

    public function followers(int $userId, int $viewerId, int $limit = 200): array
    {
        $st = $this->pdo->prepare(
            'SELECT u.id, u.username, u.full_name, u.bio, u.profile_image
               FROM follows f
               JOIN users u ON u.id = f.follower_id
              WHERE f.followee_id = :id AND (u.anon_mode = 0 OR f.follower_id = :viewer)
              ORDER BY f.created_at DESC
              LIMIT :lim'
        );
        $st->bindValue(':id', $userId, PDO::PARAM_INT);
        $st->bindValue(':viewer', $viewerId, PDO::PARAM_INT);
        $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

    public function following(int $userId, int $viewerId, int $limit = 200): array
    {
        if ($this->followingHidden($userId, $viewerId)) {
            return [];
        }
        $st = $this->pdo->prepare(
            'SELECT u.id, u.username, u.full_name, u.bio, u.profile_image
               FROM follows f
               JOIN users u ON u.id = f.followee_id
              WHERE f.follower_id = :id
              ORDER BY f.created_at DESC
              LIMIT :lim'
        );
        $st->bindValue(':id', $userId, PDO::PARAM_INT);
        $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }
}
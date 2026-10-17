<?php
declare(strict_types=1);

class PostModel
{
    private const SELECT = "
        SELECT p.id,
               CASE WHEN p.is_anonymous = 1 THEN NULL ELSE p.user_id END AS user_id,
               (p.user_id = :me) AS is_mine,
               p.is_anonymous, p.content, p.image, p.repost_of, p.created_at, p.updated_at,
               CASE WHEN p.is_anonymous = 1 THEN NULL ELSE u.username END AS username,
               CASE WHEN p.is_anonymous = 1 THEN u.anon_name ELSE u.full_name END AS full_name,
               CASE WHEN p.is_anonymous = 1 THEN NULL ELSE u.profile_image END AS profile_image,
               op.content AS orig_content, op.image AS orig_image, op.created_at AS orig_created_at,
               op.is_anonymous AS orig_is_anonymous,
               CASE WHEN op.is_anonymous = 1 THEN NULL ELSE ou.username END AS orig_username,
               CASE WHEN op.is_anonymous = 1 THEN ou.anon_name ELSE ou.full_name END AS orig_full_name,
               CASE WHEN op.is_anonymous = 1 THEN NULL ELSE ou.profile_image END AS orig_profile_image,
               (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id)    AS like_count,
               (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id) AS comment_count,
               EXISTS(SELECT 1 FROM likes l2
                       WHERE l2.post_id = p.id AND l2.user_id = :viewer) AS liked_by_me
          FROM posts p
          JOIN users u ON u.id = p.user_id
          LEFT JOIN posts op ON op.id = p.repost_of
          LEFT JOIN users ou ON ou.id = op.user_id";

    private const ORDER = ' ORDER BY p.created_at DESC, p.id DESC LIMIT :lim';

    public function __construct(private PDO $pdo) {}

    private function bindViewer(PDOStatement $st, int $viewerId): void
    {
        $st->bindValue(':viewer', $viewerId, PDO::PARAM_INT);
        $st->bindValue(':me', $viewerId, PDO::PARAM_INT);
    }

    public function create(int $userId, string $content, ?string $image, bool $anonymous = false): int
    {
        $st = $this->pdo->prepare(
            'INSERT INTO posts (user_id, content, image, is_anonymous) VALUES (:u, :c, :i, :a)'
        );
        $st->execute([':u' => $userId, ':c' => $content, ':i' => $image, ':a' => $anonymous ? 1 : 0]);
        return (int) $this->pdo->lastInsertId();
    }

    public function createRepost(int $userId, int $postId, bool $anonymous = false): ?int
    {
        // If the post is itself a repost, share the original instead.
        $st = $this->pdo->prepare('SELECT COALESCE(repost_of, id) FROM posts WHERE id = :id');
        $st->execute([':id' => $postId]);
        $rootId = $st->fetchColumn();
        if ($rootId === false) {
            return null;
        }

        $st = $this->pdo->prepare(
            "INSERT INTO posts (user_id, content, image, repost_of, is_anonymous)
             VALUES (:u, '', NULL, :o, :a)"
        );
        $st->execute([':u' => $userId, ':o' => (int) $rootId, ':a' => $anonymous ? 1 : 0]);
        return (int) $this->pdo->lastInsertId();
    }

    public function find(int $id): ?array
    {
        $st = $this->pdo->prepare('SELECT id, user_id, content, image, created_at, updated_at FROM posts WHERE id = :id');
        $st->execute([':id' => $id]);
        return $st->fetch() ?: null;
    }

    public function exists(int $id): bool
    {
        $st = $this->pdo->prepare('SELECT 1 FROM posts WHERE id = :id');
        $st->execute([':id' => $id]);
        return (bool) $st->fetchColumn();
    }

    public function feed(int $viewerId, int $limit = 100): array
    {
        $st = $this->pdo->prepare(self::SELECT . self::ORDER);
        $this->bindViewer($st, $viewerId);
        $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

    /** Anonymous posts only appear on the author's own profile. */
    public function byUser(int $authorId, int $viewerId, int $limit = 100): array
    {
        $st = $this->pdo->prepare(
            self::SELECT . ' WHERE p.user_id = :author AND (p.is_anonymous = 0 OR p.user_id = :viewer2)' . self::ORDER
        );
        $this->bindViewer($st, $viewerId);
        $st->bindValue(':author', $authorId, PDO::PARAM_INT);
        $st->bindValue(':viewer2', $viewerId, PDO::PARAM_INT);
        $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

    public function search(string $like, int $viewerId, int $limit = 50): array
    {
        $st = $this->pdo->prepare(self::SELECT . " WHERE p.content LIKE :kw ESCAPE '!'" . self::ORDER);
        $this->bindViewer($st, $viewerId);
        $st->bindValue(':kw', $like);
        $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

    public function update(int $id, int $userId, string $content, bool $setImage, ?string $image): bool
    {
        if ($setImage) {
            $st = $this->pdo->prepare(
                'UPDATE posts SET content = :c, image = :i WHERE id = :id AND user_id = :u'
            );
            $st->execute([':c' => $content, ':i' => $image, ':id' => $id, ':u' => $userId]);
        } else {
            $st = $this->pdo->prepare('UPDATE posts SET content = :c WHERE id = :id AND user_id = :u');
            $st->execute([':c' => $content, ':id' => $id, ':u' => $userId]);
        }
        return true;
    }

    public function delete(int $id, int $userId): bool
    {
        $st = $this->pdo->prepare('DELETE FROM posts WHERE id = :id AND user_id = :u');
        $st->execute([':id' => $id, ':u' => $userId]);
        return $st->rowCount() > 0;
    }
}
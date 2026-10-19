<?php
declare(strict_types=1);

class LikeModel
{
    public function __construct(private PDO $pdo) {}

    public function count(int $postId): int
    {
        $st = $this->pdo->prepare('SELECT COUNT(*) FROM likes WHERE post_id = :p');
        $st->execute([':p' => $postId]);
        return (int) $st->fetchColumn();
    }

    public function toggle(int $postId, int $userId): array
    {
        $this->pdo->beginTransaction();
        try {
            $del = $this->pdo->prepare('DELETE FROM likes WHERE post_id = :p AND user_id = :u');
            $del->execute([':p' => $postId, ':u' => $userId]);

            if ($del->rowCount() > 0) {
                $liked = false;
            } else {
                $ins = $this->pdo->prepare(
                    'INSERT INTO likes (post_id, user_id) VALUES (:p, :u)
                     ON DUPLICATE KEY UPDATE post_id = post_id'
                );
                $ins->execute([':p' => $postId, ':u' => $userId]);
                $liked = true;
            }
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
        return ['liked' => $liked, 'count' => $this->count($postId)];
    }
}

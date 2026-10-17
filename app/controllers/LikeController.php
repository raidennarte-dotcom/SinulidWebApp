<?php
declare(strict_types=1);

class LikeController extends Controller
{
    public function toggle(): void
    {
        $me = $this->user();
        if ($me === null) {
            $this->json(['ok' => false, 'message' => 'Please log in to like posts.'], 401);
        }
        $this->requireCsrf(true);

        $postId = $this->postInt('post_id');
        if ($postId <= 0 || !(new PostModel($this->pdo))->exists($postId)) {
            $this->json(['ok' => false, 'message' => 'That post no longer exists.'], 404);
        }

        $result = (new LikeModel($this->pdo))->toggle((int) $me['id'], $postId);
        if ($result['liked']) {
            try {
                $anon = (new UserModel($this->pdo))->isAnonymous((int) $me['id']);
                (new NotificationModel($this->pdo))->notifyPostOwner($postId, (int) $me['id'], $anon, 'like');
            } catch (Throwable $e) {
                error_log('Notification failed: ' . $e->getMessage());
            }
        }
        $this->json(['ok' => true, 'liked' => $result['liked'], 'count' => $result['count']]);
    }
}

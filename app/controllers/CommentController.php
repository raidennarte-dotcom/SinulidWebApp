<?php
declare(strict_types=1);

class CommentController extends Controller
{
    private const MAX_LENGTH = 500;

    public function store(): void
    {
        $me = $this->requireLogin();
        $this->requireCsrf();

        $postId  = $this->postInt('post_id');
        $content = trim((string) ($_POST['content'] ?? ''));
        $back    = safe_next($_POST['next'] ?? null, url('feed', ['open' => $postId]) . '#post-' . $postId);

        if (!(new PostModel($this->pdo))->exists($postId)) {
            $this->showError(404, 'That post does not exist anymore.');
        }

        $error = $this->validate($content);
        if ($error !== null) {
            flash('error', $error);
            redirect($back);
        }

        (new CommentModel($this->pdo))->create($postId, (int) $me['id'], $content);
                try {
            $anon = (new UserModel($this->pdo))->isAnonymous((int) $me['id']);
            (new NotificationModel($this->pdo))->notifyPostOwner($postId, (int) $me['id'], $anon, 'comment');
        } catch (Throwable $e) {
            error_log('Notification failed: ' . $e->getMessage());
        }
        flash('success', 'Comment added.');
        redirect($back);
    }

    public function edit(): void
    {
        $me      = $this->requireLogin();
        $comment = $this->ownedCommentOrFail($this->getInt('id'), (int) $me['id']);
        $this->render('comments/edit', [
            'pageTitle' => 'Edit comment',
            'comment'   => $comment,
            'next'      => (string) ($_GET['next'] ?? ''),
            'errors'    => [],
        ]);
    }

    public function update(): void
    {
        $me      = $this->requireLogin();
        $this->requireCsrf();
        $comment = $this->ownedCommentOrFail($this->postInt('id'), (int) $me['id']);
        $next    = (string) ($_POST['next'] ?? '');
        $back    = safe_next($next, url('feed', ['open' => $comment['post_id']]) . '#post-' . (int) $comment['post_id']);

        $content = trim((string) ($_POST['content'] ?? ''));
        $error   = $this->validate($content);
        if ($error !== null) {
            $comment['content'] = $content;
            $this->render('comments/edit', [
                'pageTitle' => 'Edit comment',
                'comment'   => $comment,
                'next'      => $next,
                'errors'    => [$error],
            ]);
            return;
        }

        (new CommentModel($this->pdo))->update((int) $comment['id'], (int) $me['id'], $content);
        flash('success', 'Comment updated.');
        redirect($back);
    }

    public function delete(): void
    {
        $me      = $this->requireLogin();
        $this->requireCsrf();
        $comment = $this->ownedCommentOrFail($this->postInt('id'), (int) $me['id']);
        $back    = safe_next($_POST['next'] ?? null, url('feed', ['open' => $comment['post_id']]) . '#post-' . (int) $comment['post_id']);

        if ((new CommentModel($this->pdo))->delete((int) $comment['id'], (int) $me['id'])) {
            flash('success', 'Comment deleted.');
        }
        redirect($back);
    }

    private function validate(string $content): ?string
    {
        $len = mb_strlen($content);
        if ($len === 0) {
            return 'A comment cannot be empty.';
        }
        if ($len > self::MAX_LENGTH) {
            return 'Comments can be at most ' . self::MAX_LENGTH . ' characters.';
        }
        return null;
    }

    private function ownedCommentOrFail(int $id, int $myId): array
    {
        $comment = (new CommentModel($this->pdo))->find($id);
        if ($comment === null) {
            $this->showError(404, 'That comment does not exist.');
        }
        if ((int) $comment['user_id'] !== $myId) {
            $this->showError(403, 'You can only change your own comments.');
        }
        return $comment;
    }
}

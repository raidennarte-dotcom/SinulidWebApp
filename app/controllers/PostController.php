<?php
declare(strict_types=1);

class PostController extends Controller
{
    private const MAX_LENGTH = 2000;

    public function index(): void
    {
        $me    = $this->requireLogin();
        $posts = (new PostModel($this->pdo))->feed((int) $me['id']);
        $this->render('feed/index', [
            'pageTitle' => 'Home',
            'posts'     => $this->attachComments($posts),
        ]);
    }

    public function create(): void
    {
        $this->requireLogin();
        $this->render('posts/create', ['pageTitle' => 'New post', 'errors' => [], 'content' => '']);
    }

    public function store(): void
    {
        $me = $this->requireLogin();
        $this->requireCsrf();

        $content = trim((string) ($_POST['content'] ?? ''));
        $errors  = $this->validateContent($content);

        $image = null;
        if ($errors === []) {
            [$image, $uploadError] = handle_image_upload($_FILES['image'] ?? null, 'posts', MAX_POST_IMAGE_BYTES);
            if ($uploadError !== null) {
                $errors[] = $uploadError;
            }
        }

        if ($errors !== []) {
            $this->render('posts/create', ['pageTitle' => 'New post', 'errors' => $errors, 'content' => $content]);
            return;
        }

        $id = (new PostModel($this->pdo))->create(
            (int) $me['id'], $content, $image, $this->isAnonymousMode((int) $me['id'])
        );
        flash('success', 'Your post was published.');
        redirect(url('feed') . '#post-' . $id);
    }

    public function edit(): void
    {
        $me   = $this->requireLogin();
        $post = $this->ownedPostOrFail($this->getInt('id'), (int) $me['id']);
        $this->render('posts/edit', ['pageTitle' => 'Edit post', 'post' => $post, 'errors' => []]);
    }

    public function update(): void
    {
        $me = $this->requireLogin();
        $this->requireCsrf();
        $post = $this->ownedPostOrFail($this->postInt('id'), (int) $me['id']);

        $content = trim((string) ($_POST['content'] ?? ''));
        $errors  = $this->validateContent($content);

        $setImage = false;
        $newImage = $post['image'];
        $oldImage = $post['image'];

        if ($errors === []) {
            [$uploaded, $uploadError] = handle_image_upload($_FILES['image'] ?? null, 'posts', MAX_POST_IMAGE_BYTES);
            if ($uploadError !== null) {
                $errors[] = $uploadError;
            } elseif ($uploaded !== null) {
                $setImage = true;
                $newImage = $uploaded;
            } elseif (!empty($_POST['remove_image'])) {
                $setImage = true;
                $newImage = null;
            }
        }

        if ($errors !== []) {
            $post['content'] = $content;
            $this->render('posts/edit', ['pageTitle' => 'Edit post', 'post' => $post, 'errors' => $errors]);
            return;
        }

        (new PostModel($this->pdo))->update((int) $post['id'], (int) $me['id'], $content, $setImage, $newImage);
        if ($setImage) {
            delete_upload($oldImage);
        }
        flash('success', 'Post updated.');
        redirect(url('feed') . '#post-' . (int) $post['id']);
    }

    public function delete(): void
    {
        $me = $this->requireLogin();
        $this->requireCsrf();
        $post = $this->ownedPostOrFail($this->postInt('id'), (int) $me['id']);

        if ((new PostModel($this->pdo))->delete((int) $post['id'], (int) $me['id'])) {
            delete_upload($post['image']);
            flash('success', 'Post deleted.');
        }
        redirect(safe_next($_POST['next'] ?? null, url('feed')));
    }

    private function validateContent(string $content): array
    {
        $len = mb_strlen($content);
        if ($len === 0) {
            return ['Please write something before posting.'];
        }
        if ($len > self::MAX_LENGTH) {
            return ['Posts can be at most ' . self::MAX_LENGTH . ' characters.'];
        }
        return [];
    }

    private function ownedPostOrFail(int $id, int $myId): array
    {
        $post = (new PostModel($this->pdo))->find($id);
        if ($post === null) {
            $this->showError(404, 'That post does not exist.');
        }
        if ((int) $post['user_id'] !== $myId) {
            $this->showError(403, 'You can only change your own posts.');
        }
        return $post;
    }

               public function repost(): void
    {
        $me = $this->requireLogin();
        $this->requireCsrf();

        $postId = (int) ($_POST['id'] ?? 0);
        $anon   = (new UserModel($this->pdo))->isAnonymous((int) $me['id']);
        $newId  = (new PostModel($this->pdo))->createRepost((int) $me['id'], $postId, $anon);

        if ($newId === null) {
            flash('error', 'That post no longer exists.');
        } else {
            try {
                (new NotificationModel($this->pdo))->notifyShare($newId, (int) $me['id'], $anon);
            } catch (Throwable $e) {
                error_log('Notification failed: ' . $e->getMessage());
            }
            flash('success', 'Shared to your feed.');
        }
        redirect(safe_next($_POST['next'] ?? null, url('feed')));
    }

    /** Read fresh from the database so a stale session can never leak a real name. */
    private function isAnonymousMode(int $userId): bool
    {
        $u = (new UserModel($this->pdo))->findById($userId);
        return $u !== null
            && (int) ($u['anon_mode'] ?? 0) === 1
            && trim((string) ($u['anon_name'] ?? '')) !== '';
    }
}

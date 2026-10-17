<?php
declare(strict_types=1);

class FollowController extends Controller
{
    public function toggle(): void
    {
        $me = $this->requireLogin();
        $this->requireCsrf();

        $users = new UserModel($this->pdo);

        if ($users->isAnonymous((int) $me['id'])) {
            flash('error', 'Turn off anonymous mode to follow or unfollow people.');
            redirect(safe_next($_POST['next'] ?? null, url('feed')));
        }

        $targetId = (int) ($_POST['user_id'] ?? 0);
        $target   = $users->findById($targetId);

        if ($target === null) {
            flash('error', 'That user does not exist.');
            redirect(url('feed'));
        }
        if ($targetId === (int) $me['id']) {
            flash('error', 'You cannot follow yourself.');
            redirect(url('profile'));
        }

        $follows = new FollowModel($this->pdo);
        if ($follows->isFollowing((int) $me['id'], $targetId)) {
            $follows->unfollow((int) $me['id'], $targetId);
            flash('success', 'You unfollowed @' . $target['username'] . '.');
        } else {
            $follows->follow((int) $me['id'], $targetId);
            try {
                (new NotificationModel($this->pdo))->create($targetId, (int) $me['id'], false, 'follow', null);
            } catch (Throwable $e) {
                error_log('Notification failed: ' . $e->getMessage());
            }
            flash('success', 'You are now following @' . $target['username'] . '.');
        }

        redirect(safe_next($_POST['next'] ?? null, url('profile', ['u' => $target['username']])));
    }

    public function followers(): void
    {
        $this->showList('followers');
    }

    public function following(): void
    {
        $this->showList('following');
    }

    private function showList(string $type): void
    {
        $me    = $this->requireLogin();
        $users = new UserModel($this->pdo);

        $username = trim((string) ($_GET['u'] ?? ''));
        $profile  = $username === '' ? $me : $users->findByUsername($username);
        if ($profile === null) {
            $this->showError(404, 'That user does not exist.');
        }

        $myId      = (int) $me['id'];
        $profileId = (int) $profile['id'];
        $follows   = new FollowModel($this->pdo);

        $this->render('follow/list', [
            'pageTitle'      => ($type === 'followers' ? 'Followers' : 'Following') . ' · ' . display_name($profile),
            'profileUser'    => $profile,
            'type'           => $type,
            'isOwner'        => $profileId === $myId,
            'followerCount'  => $follows->followerCount($profileId, $myId),
            'followingCount' => $follows->followingCount($profileId, $myId),
            'listHidden'     => $type === 'following' && $follows->followingHidden($profileId, $myId),
            'list'           => $type === 'followers'
                ? $follows->followers($profileId, $myId)
                : $follows->following($profileId, $myId),
        ]);
    }
}
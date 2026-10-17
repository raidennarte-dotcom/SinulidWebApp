<?php
declare(strict_types=1);

class AnonController extends Controller
{
    public function toggle(): void
    {
        $me = $this->requireLogin();
        $this->requireCsrf();

        $users  = new UserModel($this->pdo);
        $user   = $users->findById((int) $me['id']);
        $turnOn = (string) ($_POST['mode'] ?? '') === 'on';

        if ($turnOn && trim((string) ($user['anon_name'] ?? '')) === '') {
            flash('error', 'Create your anonymous name in Edit Profile first.');
            redirect(url('profile/edit'));
        }

        $users->setAnonMode((int) $me['id'], $turnOn);
        flash('success', $turnOn ? 'Anonymous mode is on.' : 'Anonymous mode is off.');
        redirect(safe_next($_POST['next'] ?? null, url('feed')));
    }

    public function saveName(): void
    {
        $me = $this->requireLogin();
        $this->requireCsrf();

        $users = new UserModel($this->pdo);
        $name  = trim((string) ($_POST['anon_name'] ?? ''));

        if (preg_match('/^[\p{L}\p{N}_ ]{3,30}$/u', $name) !== 1) {
            flash('error', 'Anonymous name must be 3 to 30 characters: letters, numbers, spaces and underscores.');
            redirect(url('profile/edit'));
        }
        if ($users->anonNameTaken($name, (int) $me['id']) || $users->usernameExists($name)) {
            flash('error', 'That anonymous name is already taken.');
            redirect(url('profile/edit'));
        }

        try {
            $users->setAnonName((int) $me['id'], $name);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                flash('error', 'That anonymous name is already taken.');
                redirect(url('profile/edit'));
            }
            throw $e;
        }

        flash('success', 'Anonymous name saved.');
        redirect(url('profile/edit'));
    }
}
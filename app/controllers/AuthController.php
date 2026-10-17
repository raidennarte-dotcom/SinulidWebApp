<?php
declare(strict_types=1);

class AuthController extends Controller
{
    public function login(): void
    {
        if ($this->user() !== null) {
            redirect(url('feed'));
        }

        $errors     = [];
        $identifier = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requireCsrf();
            $identifier = trim((string) ($_POST['identifier'] ?? ''));
            $password   = (string) ($_POST['password'] ?? '');

            if ($identifier === '' || $password === '') {
                $errors[] = 'Please enter your email or username and your password.';
            } else {
                $user = (new UserModel($this->pdo))->findByIdentifier($identifier);
                // Always run password_verify so "no such user" takes as long as "wrong password".
                $hash = $user['password'] ?? password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
                $ok   = password_verify($password, $hash);

                if ($user !== null && $ok) {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = (int) $user['id'];
                    flash('success', 'Welcome back, ' . $user['username'] . '!');
                    redirect(url('feed'));
                }
                $errors[] = 'Invalid email/username or password.';
            }
        }

        $this->render('auth/login', [
            'pageTitle'  => 'Log In',
            'errors'     => $errors,
            'identifier' => $identifier,
        ], 'auth');
    }

    public function signup(): void
    {
        if ($this->user() !== null) {
            redirect(url('feed'));
        }

        $errors = [];
        $old    = ['username' => '', 'email' => ''];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requireCsrf();
            $users = new UserModel($this->pdo);

            $old = [
                'username' => trim((string) ($_POST['username'] ?? '')),
                'email'    => trim((string) ($_POST['email'] ?? '')),
            ];
            $password = (string) ($_POST['password'] ?? '');
            $confirm  = (string) ($_POST['confirm_password'] ?? '');

            if (preg_match('/^[A-Za-z0-9_]{3,30}$/', $old['username']) !== 1) {
                $errors[] = 'Username must be 3 to 30 characters: letters, numbers and underscores only.';
            }
            if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL) || strlen($old['email']) > 190) {
                $errors[] = 'Please enter a valid email address.';
            }
            if (strlen($password) < 8 || strlen($password) > 72
                || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
                $errors[] = 'Password must be 8 to 72 characters and include at least one letter and one number.';
            }
            if ($password !== $confirm) {
                $errors[] = 'Passwords do not match.';
            }

            if ($errors === []) {
                if ($users->usernameExists($old['username'])) {
                    $errors[] = 'That username is already taken.';
                }
                if ($users->emailExists($old['email'])) {
                    $errors[] = 'That email address is already registered.';
                }
            }

            if ($errors === []) {
                try {
                    $users->create(
                        $old['username'],
                        $old['email'],
                        password_hash($password, PASSWORD_DEFAULT)
                    );
                    flash('success', 'Account created! You can log in now.');
                    redirect(url('auth/login'));
                } catch (PDOException $e) {
                    if ($e->getCode() === '23000') {
                        $errors[] = 'That username or email address is already registered.';
                    } else {
                        throw $e;
                    }
                }
            }
        }

        $this->render('auth/signup', [
            'pageTitle' => 'Sign Up',
            'errors'    => $errors,
            'old'       => $old,
        ], 'auth');
    }

    public function logout(): void
    {
        $this->requireCsrf();
        destroy_session();
        start_secure_session();
        flash('success', 'You have been logged out.');
        redirect(url('auth/login'));
    }
}
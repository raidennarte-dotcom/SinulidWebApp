<?php
declare(strict_types=1);

const MAX_AVATAR_BYTES     = 2 * 1024 * 1024; // 2 MB
const MAX_POST_IMAGE_BYTES = 4 * 1024 * 1024; // 4 MB

/* ---------- Sessions ---------- */
function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('SINULIDSESSID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function auth_id(): ?int
{
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

function destroy_session(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 42000,
            'path'     => $p['path'],
            'domain'   => $p['domain'],
            'secure'   => $p['secure'],
            'httponly' => $p['httponly'],
            'samesite' => $p['samesite'] ?? 'Lax',
        ]);
    }
    session_destroy();
}

/* ---------- Output escaping ---------- */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/* ---------- URLs ---------- */
function base_url(): string
{
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    return rtrim($dir, '/');
}

function url(string $route = 'feed', array $params = []): string
{
    $query = ['r' => $route] + $params;
    return base_url() . '/index.php?' . http_build_query($query);
}

function asset(string $path): string
{
    return base_url() . '/assets/' . ltrim($path, '/');
}

function upload_url(?string $relative): ?string
{
    if ($relative === null || $relative === '') {
        return null;
    }
    return base_url() . '/uploads/' . ltrim($relative, '/');
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function safe_next(?string $next, string $fallback): string
{
    if ($next !== null && preg_match('/^\?[A-Za-z0-9_=&%.\-+\/]*(#[A-Za-z0-9_\-]*)?$/', $next) === 1) {
        return base_url() . '/index.php' . $next;
    }
    return $fallback;
}

function current_next(int $postId): string
{
    $params = $_GET;
    unset($params['open']);
    $params['open'] = $postId;
    return '?' . http_build_query($params) . '#post-' . $postId;
}

function open_post_id(): int
{
    return isset($_GET['open']) ? (int) $_GET['open'] : 0;
}

/* ---------- Flash messages ---------- */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function pull_flashes(): array
{
    $items = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $items;
}

/* ---------- CSRF ---------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_valid($token): bool
{
    return is_string($token) && $token !== '' && hash_equals(csrf_token(), $token);
}

/* ---------- Text helpers ---------- */
function initial_of(?string $name): string
{
    $name = trim((string) $name);
    return $name === '' ? '?' : mb_strtoupper(mb_substr($name, 0, 1));
}

function display_name(array $user): string
{
    $name = trim((string) ($user['full_name'] ?? ''));
    if (!empty($user['is_anonymous'])) {
        return $name !== '' ? $name : 'Anonymous';
    }
    return $name !== '' ? $name : (string) ($user['username'] ?? '');
}

function avatar_html(array $user): string
{
    if (!empty($user['is_anonymous'])) {
        return '<span class="avatar-initial avatar-anon" aria-hidden="true"><i class="bi bi-incognito"></i></span>';
    }
    $src = upload_url($user['profile_image'] ?? null);
    if ($src !== null) {
        return '<img src="' . e($src) . '" alt="">';
    }
    return '<span class="avatar-initial" aria-hidden="true">' . e(initial_of(display_name($user))) . '</span>';
}

function time_ago(string $datetime): string
{
    $ts = strtotime($datetime);
    if ($ts === false) {
        return $datetime;
    }
    $diff = time() - $ts;
    if ($diff < 60)     return 'Just now';
    if ($diff < 3600)   return (int) ($diff / 60) . 'm ago';
    if ($diff < 86400)  return (int) ($diff / 3600) . 'h ago';
    if ($diff < 604800) return (int) ($diff / 86400) . 'd ago';
    return date('M j, Y', $ts);
}

/* ---------- Image uploads ---------- */
function handle_image_upload(?array $file, string $subdir, int $maxBytes): array
{
    if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return [null, null];
    }
    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return [null, 'That image is too large.'];
        default:
            return [null, 'The image could not be uploaded. Please try again.'];
    }
    if (!is_uploaded_file($file['tmp_name'])) {
        return [null, 'Invalid upload.'];
    }
    if ($file['size'] > $maxBytes) {
        return [null, 'Image must be ' . (int) ($maxBytes / 1048576) . ' MB or smaller.'];
    }

    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];

    $originalExt = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
    if (!in_array($originalExt, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
        return [null, 'Only JPG, PNG, GIF or WEBP images are allowed.'];
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!is_string($mime) || !isset($allowed[$mime])) {
        return [null, 'Only JPG, PNG, GIF or WEBP images are allowed.'];
    }
    if (@getimagesize($file['tmp_name']) === false) {
        return [null, 'That file is not a valid image.'];
    }

    $dir = UPLOAD_PATH . '/' . $subdir;
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        return [null, 'The server could not store the image.'];
    }
    $name = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
        return [null, 'The server could not store the image.'];
    }
    return [$subdir . '/' . $name, null];
}

function delete_upload(?string $relative): void
{
    if ($relative !== null && preg_match('#^(avatars|posts)/[a-f0-9]{32}\.(jpg|png|gif|webp)$#', $relative) === 1) {
        $path = UPLOAD_PATH . '/' . $relative;
        if (is_file($path)) {
            @unlink($path);
        }
    }
}

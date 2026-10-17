<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
define('UPLOAD_PATH', __DIR__ . '/uploads');

require BASE_PATH . '/config/database.php';
require BASE_PATH . '/app/helpers.php';

foreach (['UserModel', 'PostModel', 'CommentModel', 'LikeModel', 'FollowModel', 'NotificationModel'] as $file) {
    require BASE_PATH . '/app/models/' . $file . '.php';
}
require BASE_PATH . '/app/controllers/Controller.php';
foreach (['Auth', 'Profile', 'Post', 'Comment', 'Like', 'Search', 'Anon', 'Follow', 'Notification'] as $name) {
    require BASE_PATH . '/app/controllers/' . $name . 'Controller.php';
}

date_default_timezone_set('Asia/Manila');
ini_set('display_errors', APP_DEBUG ? '1' : '0');
error_reporting(E_ALL);
start_secure_session();

$routes = [
    'feed'            => ['PostController',    'index',   ['GET']],
    'post/create'     => ['PostController',    'create',  ['GET']],
    'post/store'      => ['PostController',    'store',   ['POST']],
    'post/edit'       => ['PostController',    'edit',    ['GET']],
    'post/update'     => ['PostController',    'update',  ['POST']],
    'post/delete'     => ['PostController',    'delete',  ['POST']],
    'post/repost'     => ['PostController',    'repost',  ['POST']],
    'anon/toggle'     => ['AnonController',    'toggle',   ['POST']],
    'anon/name'       => ['AnonController',    'saveName', ['POST']],
    'follow/toggle'   => ['FollowController',  'toggle',   ['POST']],
    'follow/followers' => ['FollowController', 'followers', ['GET']],
    'follow/following' => ['FollowController', 'following', ['GET']],
    'notifications/read' => ['NotificationController', 'read', ['POST']],
    

    'comment/store'   => ['CommentController', 'store',   ['POST']],
    'comment/edit'    => ['CommentController', 'edit',    ['GET']],
    'comment/update'  => ['CommentController', 'update',  ['POST']],
    'comment/delete'  => ['CommentController', 'delete',  ['POST']],

    'like/toggle'     => ['LikeController',    'toggle',  ['POST']],

    'profile'         => ['ProfileController', 'show',    ['GET']],
    'profile/edit'    => ['ProfileController', 'edit',    ['GET']],
    'profile/update'  => ['ProfileController', 'update',  ['POST']],

    'search'          => ['SearchController',  'index',   ['GET']],

    'auth/login'      => ['AuthController',    'login',   ['GET', 'POST']],
    'auth/signup'     => ['AuthController',    'signup',  ['GET', 'POST']],
    'auth/logout'     => ['AuthController',    'logout',  ['POST']],
];

$route = $_GET['r'] ?? 'feed';

try {
    $controller = null;
    if (!is_string($route) || !isset($routes[$route])) {
        $controller = new Controller(db());
        $controller->showError(404, 'Page not found.');
    }
    [$class, $method, $verbs] = $routes[$route];
    $controller = new $class(db());
    if (!in_array($_SERVER['REQUEST_METHOD'], $verbs, true)) {
        header('Allow: ' . implode(', ', $verbs));
        $controller->showError(405, 'That action is not allowed this way.');
    }
    $controller->$method();
} catch (Throwable $e) {
    error_log('Sinulid error: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    echo APP_DEBUG
        ? '<pre>' . e($e) . '</pre>'
        : 'Something went wrong on our side. Please try again.';
}

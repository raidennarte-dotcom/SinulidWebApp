<?php
declare(strict_types=1);

class NotificationController extends Controller
{
    public function read(): void
    {
        $me = $this->user();
        if ($me === null) {
            $this->json(['ok' => false, 'message' => 'Please log in.'], 401);
        }
        $this->requireCsrf(true);

        (new NotificationModel($this->pdo))->markAllRead((int) $me['id']);
        $this->json(['ok' => true]);
    }
}
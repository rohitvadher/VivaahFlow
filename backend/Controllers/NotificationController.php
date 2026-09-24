<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Services\NotificationService;

class NotificationController extends BaseController
{
    public function __construct(private NotificationService $notificationService)
    {
    }

    public function index(Request $request): void
    {
        $this->requirePermission('notifications.read');
        $this->success([
            'items' => $this->notificationService->list($this->userId()),
            'unread' => $this->notificationService->unreadCount($this->userId()),
        ]);
    }

    public function read(Request $request): void
    {
        $this->requirePermission('notifications.update');
        $this->notificationService->markRead($this->routeId($request), $this->userId());
        $this->success(['unread' => $this->notificationService->unreadCount($this->userId())], 'Notification marked as read.');
    }

    public function readAll(Request $request): void
    {
        $this->requirePermission('notifications.update');
        $this->notificationService->markAllRead($this->userId());
        $this->success(['unread' => 0], 'All notifications marked as read.');
    }
}
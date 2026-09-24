<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Services\NotificationService;
use App\Services\ReportService;

class DashboardController extends BaseController
{
    public function __construct(
        private ReportService $reportService,
        private NotificationService $notificationService
    ) {
    }

    public function index(Request $request): void
    {
        $this->requirePermission('dashboard.view');
        $data = $this->reportService->dashboard();
        $data['unread_notifications'] = $this->notificationService->unreadCount($this->userId());
        $this->success($data);
    }
}
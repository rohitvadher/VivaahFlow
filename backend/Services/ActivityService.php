<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ActivityLogRepository;

class ActivityService
{
    public function __construct(private ActivityLogRepository $activityLogRepository)
    {
    }

    public function log(?int $userId, string $action, ?string $entityType = null, ?int $entityId = null, ?string $details = null): void
    {
        $this->activityLogRepository->log($userId, $action, $entityType, $entityId, $details);
    }
}
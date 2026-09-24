<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\LeadRepository;
use App\Repositories\NotificationRepository;

class NotificationService
{
    public function __construct(
        private NotificationRepository $notificationRepository,
        private LeadRepository $leadRepository
    ) {
    }

    public function create(?int $userId, string $title, ?string $message, string $type = 'info', ?string $link = null): void
    {
        $this->notificationRepository->bulkCreate([
            [
                'user_id' => $userId,
                'title' => $title,
                'message' => $message,
                'notification_type' => $type,
                'link' => $link,
            ],
        ]);
    }

    public function notifyAdmins(string $title, ?string $message, string $type = 'info', ?string $link = null): void
    {
        $this->notificationRepository->bulkCreate([
            [
                'user_id' => null,
                'title' => $title,
                'message' => $message,
                'notification_type' => $type,
                'link' => $link,
            ],
        ]);
    }

    public function list(?int $userId): array
    {
        $stored = $this->notificationRepository->recent($userId, 50);
        $computed = $this->computedSystemAlerts();
        $items = array_merge($stored, $computed);
        usort($items, fn(array $a, array $b): int => strcmp($b['sort_key'] ?? $b['created_at'] ?? '', $a['sort_key'] ?? $a['created_at'] ?? ''));
        return $items;
    }

    public function unreadCount(?int $userId): int
    {
        return $this->notificationRepository->unreadCount($userId) + count($this->computedSystemAlerts());
    }

    private function computedSystemAlerts(): array
    {
        $items = [];
        $today = date('Y-m-d');
        $dueFollowups = $this->leadRepository->upcomingFollowups(6);
        foreach ($dueFollowups as $lead) {
            $items[] = [
                'id' => null,
                'computed' => true,
                'title' => 'Follow-up due: ' . $lead['name'],
                'message' => 'A follow-up for ' . $lead['name'] . ' is due on ' . $lead['next_followup_at'] . '.',
                'notification_type' => 'warning',
                'link' => \App\Helpers\FrontendRoutes::path('manage.leads'),
                'is_read' => 0,
                'created_at' => $lead['next_followup_at'] . ' 08:00:00',
                'sort_key' => $lead['next_followup_at'] . ' 08:00:00',
            ];
        }
        return $items;
    }

    public function markRead(int $id, ?int $userId): void
    {
        $this->notificationRepository->markRead($id, $userId);
    }

    public function markAllRead(?int $userId): void
    {
        $this->notificationRepository->markAllRead($userId);
    }
}
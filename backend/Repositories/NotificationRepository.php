<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Connection;

class NotificationRepository extends BaseRepository
{
    protected string $table = 'notifications';

    public function unreadCount(?int $userId): int
    {
        $params = [];
        $userClause = 'user_id IS NULL';
        if ($userId !== null) {
            $userClause = '(user_id = ? OR user_id IS NULL)';
            $params = [$userId];
        }
        return (int)Connection::fetchColumn(
            "SELECT COUNT(*) FROM notifications WHERE is_read = 0 AND {$userClause}",
            $params
        );
    }

    public function recent(?int $userId, int $limit = 8): array
    {
        $params = [];
        $userClause = 'user_id IS NULL';
        if ($userId !== null) {
            $userClause = '(user_id = ? OR user_id IS NULL)';
            $params[] = $userId;
        }
        $params[] = $limit;
        return Connection::fetchAll(
            "SELECT * FROM notifications WHERE {$userClause}
             ORDER BY created_at DESC, id DESC LIMIT ?",
            $params
        );
    }

    public function markRead(int $id, ?int $userId): void
    {
        Connection::execute(
            'UPDATE notifications SET is_read = 1 WHERE id = ? AND (user_id IS NULL OR user_id = ?)',
            [$id, $userId]
        );
    }

    public function markAllRead(?int $userId): void
    {
        if ($userId !== null) {
            Connection::execute('UPDATE notifications SET is_read = 1 WHERE (user_id = ? OR user_id IS NULL)', [$userId]);
        } else {
            Connection::execute('UPDATE notifications SET is_read = 1');
        }
    }

    public function bulkCreate(array $rows): void
    {
        foreach ($rows as $row) {
            Connection::execute(
                'INSERT INTO notifications (user_id, title, message, notification_type, link)
                 VALUES (?, ?, ?, ?, ?)',
                [
                    $row['user_id'] ?? null,
                    $row['title'] ?? '',
                    $row['message'] ?? null,
                    $row['notification_type'] ?? 'info',
                    $row['link'] ?? null,
                ]
            );
        }
    }
}
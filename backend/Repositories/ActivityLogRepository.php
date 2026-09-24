<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Connection;

class ActivityLogRepository extends BaseRepository
{
    protected string $table = 'activity_logs';

    public function log(?int $userId, string $action, ?string $entityType, ?int $entityId, ?string $details): void
    {
        Connection::execute(
            'INSERT INTO activity_logs (user_id, action, entity_type, entity_id, details, ip_address)
             VALUES (?, ?, ?, ?, ?, ?)',
            [
                $userId,
                $action,
                $entityType,
                $entityId,
                $details,
                $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            ]
        );
    }

    public function recent(int $limit = 12): array
    {
        return Connection::fetchAll(
            'SELECT a.*, u.name AS user_name
             FROM activity_logs a
             LEFT JOIN users u ON u.id = a.user_id
             ORDER BY a.created_at DESC, a.id DESC
             LIMIT ?',
            [$limit]
        );
    }
}
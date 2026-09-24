<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Connection;

class ServiceImageRepository extends BaseRepository
{
    protected string $table = 'service_images';

    public function forService(int $serviceId): array
    {
        return Connection::fetchAll(
            'SELECT * FROM service_images WHERE service_id = ? ORDER BY is_primary DESC, display_order ASC, id ASC',
            [$serviceId]
        );
    }

    public function findForService(int $serviceId, int $imageId): ?array
    {
        return $this->findWhere(['id' => $imageId, 'service_id' => $serviceId]);
    }

    public function primaryForService(int $serviceId): ?array
    {
        return $this->findWhere(['service_id' => $serviceId, 'is_primary' => 1]);
    }

    public function clearPrimary(int $serviceId): void
    {
        Connection::execute('UPDATE service_images SET is_primary = 0 WHERE service_id = ?', [$serviceId]);
    }

    public function activeGallery(int $limit = 60): array
    {
        return Connection::fetchAll(
            'SELECT si.id, si.image_path, si.caption, si.service_id, s.name AS service_name, s.slug AS service_slug
             FROM service_images si
             INNER JOIN services s ON s.id = si.service_id
             WHERE si.status = "active" AND s.status = "active"
             ORDER BY si.is_primary DESC, si.display_order ASC, si.id DESC
             LIMIT ?',
            [$limit]
        );
    }
}
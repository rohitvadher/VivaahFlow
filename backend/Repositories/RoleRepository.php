<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Connection;

class RoleRepository extends BaseRepository
{
    protected string $table = 'roles';

    public function findBySlug(string $slug): ?array
    {
        return $this->findWhere(['slug' => $slug]);
    }

    public function allForSelect(): array
    {
        return Connection::fetchAll('SELECT id, name, slug FROM roles ORDER BY id');
    }
}
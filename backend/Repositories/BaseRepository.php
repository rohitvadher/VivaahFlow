<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\BusinessException;
use App\Database\Connection;

abstract class BaseRepository
{
    protected string $table = '';
    protected string $primaryKey = 'id';

    public function find(int $id): ?array
    {
        return Connection::fetchOne("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?", [$id]);
    }

    public function findOrFail(int $id): array
    {
        $row = $this->find($id);
        if ($row === null) {
            throw new BusinessException('Record not found.', 404);
        }
        return $row;
    }

    public function findWhere(array $conditions): ?array
    {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];
        $clauses = [];
        foreach ($conditions as $column => $value) {
            $clauses[] = "{$column} = ?";
            $params[] = $value;
        }
        if ($clauses !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $clauses);
        }
        return Connection::fetchOne($sql . ' LIMIT 1', $params);
    }

    public function all(string $orderBy = 'id DESC'): array
    {
        return Connection::fetchAll("SELECT * FROM {$this->table} ORDER BY {$orderBy}");
    }

    public function count(string $where = '1 = 1', array $params = []): int
    {
        return (int)Connection::fetchColumn("SELECT COUNT(*) FROM {$this->table} WHERE {$where}", $params);
    }

    public function exists(int $id): bool
    {
        $count = (int)Connection::fetchColumn(
            "SELECT COUNT(*) FROM {$this->table} WHERE {$this->primaryKey} = ?",
            [$id]
        );
        return $count > 0;
    }

    public function insert(array $data): int
    {
        $columns = array_keys($data);
        $placeholders = implode(', ', array_map(fn(string $column): string => ':' . $column, $columns));
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->table,
            $this->quoteColumns($columns),
            $placeholders
        );
        $prepared = [];
        foreach ($data as $column => $value) {
            $prepared[':' . $column] = $value;
        }
        Connection::run($sql, $prepared);
        return Connection::lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $sets = [];
        $params = [];
        foreach ($data as $column => $value) {
            $sets[] = "{$column} = ?";
            $params[] = $value;
        }
        if ($sets === []) {
            return false;
        }
        $params[] = $id;
        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s = ?',
            $this->table,
            implode(', ', $sets),
            $this->primaryKey
        );
        return Connection::execute($sql, $params) >= 0;
    }

    public function delete(int $id): bool
    {
        return Connection::execute("DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?", [$id]) > 0;
    }

    private function quoteColumns(array $columns): string
    {
        return implode(', ', array_map(fn(string $column): string => '`' . $column . '`', $columns));
    }
}
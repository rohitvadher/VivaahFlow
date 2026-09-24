<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Connection;

class StaffRepository extends BaseRepository
{
    protected string $table = 'staff';

    public function findByUserId(int $userId): ?array
    {
        return $this->findWhere(['user_id' => $userId]);
    }

    public function listPaginated(int $page, int $perPage, string $search, string $status): array
    {
        [$params, $where] = $this->buildListWhere($search, $status);
        $total = (int)Connection::fetchColumn('SELECT COUNT(*) FROM staff' . $where, $params);
        $rows = Connection::fetchAll(
            'SELECT * FROM staff' . $where . ' ORDER BY created_at DESC LIMIT ? OFFSET ?',
            array_merge($params, [$perPage, ($page - 1) * $perPage])
        );
        return [$rows, $total];
    }

    public function activeStaff(): array
    {
        return Connection::fetchAll('SELECT * FROM staff WHERE status = "active" ORDER BY name ASC');
    }

    public function assignmentsForStaff(int $staffId): array
    {
        return Connection::fetchAll(
            'SELECT sa.*, e.title AS event_title, e.event_date, b.reference_no AS booking_no
             FROM staff_assignments sa
             INNER JOIN events e ON e.id = sa.event_id
             INNER JOIN bookings b ON b.id = e.booking_id
             WHERE sa.staff_id = ? AND sa.status <> "cancelled"
             ORDER BY e.event_date ASC',
            [$staffId]
        );
    }

    public function assign(int $eventId, int $staffId, ?string $roleNote): int
    {
        $existing = Connection::fetchColumn(
            'SELECT id FROM staff_assignments WHERE event_id = ? AND staff_id = ?',
            [$eventId, $staffId]
        );
        if ($existing !== false && $existing !== null) {
            Connection::execute(
                'UPDATE staff_assignments SET status = "assigned", role_note = ? WHERE id = ?',
                [$roleNote, (int)$existing]
            );
            return (int)$existing;
        }
        Connection::execute(
            'INSERT INTO staff_assignments (event_id, staff_id, role_note, status) VALUES (?, ?, ?, "assigned")',
            [$eventId, $staffId, $roleNote]
        );
        return Connection::lastInsertId();
    }

    public function assignment(int $assignmentId): ?array
    {
        return Connection::fetchOne(
            'SELECT sa.*, e.event_date, e.title AS event_title, st.name AS staff_name
             FROM staff_assignments sa
             INNER JOIN events e ON e.id = sa.event_id
             INNER JOIN staff st ON st.id = sa.staff_id
             WHERE sa.id = ?',
            [$assignmentId]
        );
    }

    private function buildListWhere(string $search, string $status): array
    {
        $clauses = [];
        $params = [];
        if ($status !== '' && $status !== 'all') {
            $clauses[] = 'status = ?';
            $params[] = $status;
        }
        if ($search !== '') {
            $clauses[] = '(name LIKE ? OR email LIKE ? OR phone LIKE ? OR designation LIKE ?)';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }
        $where = $clauses === [] ? '' : ' WHERE ' . implode(' AND ', $clauses);
        return [$params, $where];
    }
}
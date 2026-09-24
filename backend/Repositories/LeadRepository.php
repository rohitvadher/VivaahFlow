<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Connection;

class LeadRepository extends BaseRepository
{
    protected string $table = 'leads';

    public function listPaginated(
        int $page,
        int $perPage,
        string $search,
        string $status,
        ?int $assignedTo
    ): array {
        [$params, $where] = $this->buildListWhere($search, $status, $assignedTo);
        $total = (int)Connection::fetchColumn(
            'SELECT COUNT(*) FROM leads l
             INNER JOIN staff st ON st.id = l.assigned_to' . $where,
            $params
        );
        $rows = Connection::fetchAll(
            'SELECT l.*, st.name AS staff_name, en.reference_no AS enquiry_no
             FROM leads l
             LEFT JOIN staff st ON st.id = l.assigned_to
             LEFT JOIN enquiries en ON en.id = l.enquiry_id' . $where . '
             ORDER BY l.created_at DESC LIMIT ? OFFSET ?',
            array_merge($params, [$perPage, ($page - 1) * $perPage])
        );
        return [$rows, $total];
    }

    public function findDetailed(int $id): ?array
    {
        return Connection::fetchOne(
            'SELECT l.*, st.name AS staff_name, en.reference_no AS enquiry_no
             FROM leads l
             LEFT JOIN staff st ON st.id = l.assigned_to
             LEFT JOIN enquiries en ON en.id = l.enquiry_id
             WHERE l.id = ?',
            [$id]
        );
    }

    public function followups(int $leadId): array
    {
        return Connection::fetchAll(
            'SELECT f.*, u.name AS created_by_name
             FROM lead_followups f
             LEFT JOIN users u ON u.id = f.created_by
             WHERE f.lead_id = ?
             ORDER BY f.followup_date DESC, f.id DESC',
            [$leadId]
        );
    }

    public function addFollowup(
        int $leadId,
        string $date,
        ?string $note,
        string $status,
        ?int $userId
    ): int {
        Connection::execute(
            'INSERT INTO lead_followups (lead_id, followup_date, note, status, created_by)
             VALUES (?, ?, ?, ?, ?)',
            [
                $leadId,
                $date,
                $note,
                in_array($status, ['done', 'missed', 'pending'], true) ? $status : 'done',
                $userId,
            ]
        );
        return Connection::lastInsertId();
    }

    public function upcomingFollowups(int $limit = 10): array
    {
        $today = date('Y-m-d');
        return Connection::fetchAll(
            'SELECT l.*, st.name AS staff_name
             FROM leads l
             LEFT JOIN staff st ON st.id = l.assigned_to
             WHERE l.status IN ("new", "contacted", "follow_up")
               AND l.next_followup_at IS NOT NULL
               AND l.next_followup_at <= ?
             ORDER BY l.next_followup_at ASC
             LIMIT ?',
            [$today, $limit]
        );
    }

    public function countDue(int $days = 0): int
    {
        $date = date('Y-m-d', strtotime("+{$days} days"));
        return (int)Connection::fetchColumn(
            'SELECT COUNT(*) FROM leads
             WHERE status IN ("new", "contacted", "follow_up")
               AND next_followup_at IS NOT NULL
               AND next_followup_at <= ?',
            [$date]
        );
    }

    public function pendingFollowupsCount(): int
    {
        return $this->countDue(0);
    }

    private function buildListWhere(string $search, string $status, ?int $assignedTo): array
    {
        $clauses = [];
        $params = [];
        if ($status !== '' && $status !== 'all') {
            $clauses[] = 'l.status = ?';
            $params[] = $status;
        }
        if ($assignedTo !== null && $assignedTo > 0) {
            $clauses[] = 'l.assigned_to = ?';
            $params[] = $assignedTo;
        }
        if ($search !== '') {
            $clauses[] = '(l.name LIKE ? OR l.email LIKE ? OR l.phone LIKE ?)';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }
        $where = $clauses === [] ? '' : ' WHERE ' . implode(' AND ', $clauses);
        return [$params, $where];
    }
}
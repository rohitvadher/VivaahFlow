<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Connection;

class EventRepository extends BaseRepository
{
    protected string $table = 'events';

    public function forBooking(int $bookingId): array
    {
        return Connection::fetchAll(
            'SELECT * FROM events WHERE booking_id = ? ORDER BY event_date ASC, start_time ASC',
            [$bookingId]
        );
    }

    public function listPaginated(int $page, int $perPage, ?string $date, string $status): array
    {
        [$params, $where] = $this->buildListWhere($date, $status);
        $total = (int)Connection::fetchColumn(
            'SELECT COUNT(*) FROM events e
             INNER JOIN bookings b ON b.id = e.booking_id
             INNER JOIN customers c ON c.id = b.customer_id' . $where,
            $params
        );
        $rows = Connection::fetchAll(
            'SELECT e.*, b.reference_no AS booking_no, c.name AS customer_name, c.phone AS customer_phone
             FROM events e
             INNER JOIN bookings b ON b.id = e.booking_id
             INNER JOIN customers c ON c.id = b.customer_id' . $where . '
             ORDER BY e.event_date ASC, e.start_time ASC LIMIT ? OFFSET ?',
            array_merge($params, [$perPage, ($page - 1) * $perPage])
        );
        return [$rows, $total];
    }

    public function upcomingByDate(string $date, string $status = ''): array
    {
        $params = [$date];
        $statusClause = '';
        if ($status !== '' && $status !== 'all') {
            $statusClause = ' AND e.status = ?';
            $params[] = $status;
        }
        return Connection::fetchAll(
            'SELECT e.*, b.reference_no AS booking_no, c.name AS customer_name, c.phone AS customer_phone
             FROM events e
             INNER JOIN bookings b ON b.id = e.booking_id
             INNER JOIN customers c ON c.id = b.customer_id
             WHERE e.event_date = ?' . $statusClause . '
             ORDER BY e.start_time ASC, e.id ASC',
            $params
        );
    }

    public function findDetailed(int $id): ?array
    {
        return Connection::fetchOne(
            'SELECT e.*, b.reference_no AS booking_no, b.event_date AS booking_event_date,
                    c.name AS customer_name, c.phone AS customer_phone
             FROM events e
             INNER JOIN bookings b ON b.id = e.booking_id
             INNER JOIN customers c ON c.id = b.customer_id
             WHERE e.id = ?',
            [$id]
        );
    }

    public function assignments(int $eventId): array
    {
        return Connection::fetchAll(
            'SELECT sa.*, st.name AS staff_name, st.designation
             FROM staff_assignments sa
             INNER JOIN staff st ON st.id = sa.staff_id
             WHERE sa.event_id = ? AND sa.status <> "cancelled"
             ORDER BY sa.id ASC',
            [$eventId]
        );
    }

    public function eventsBetween(string $from, string $to): array
    {
        return Connection::fetchAll(
            'SELECT * FROM events WHERE event_date BETWEEN ? AND ? ORDER BY event_date ASC, start_time ASC',
            [$from, $to]
        );
    }

    public function conflictsForStaff(int $staffId, string $date, string $startTime, string $endTime, ?int $excludeEventId): array
    {
        $params = [$staffId, $date];
        $excludeClause = '';
        if ($excludeEventId !== null) {
            $excludeClause = ' AND e.id <> ?';
            $params[] = $excludeEventId;
        }
        if ($startTime !== '' && $endTime !== '') {
            $params[] = $startTime;
            $params[] = $endTime;
            return Connection::fetchAll(
                'SELECT e.*, st.name AS staff_name
                 FROM staff_assignments sa
                 INNER JOIN events e ON e.id = sa.event_id
                 INNER JOIN staff st ON st.id = sa.staff_id
                 WHERE sa.staff_id = ?
                   AND e.event_date = ?
                   AND e.status <> "cancelled"
                   AND sa.status <> "cancelled"
                   AND e.start_time IS NOT NULL AND e.end_time IS NOT NULL
                   AND e.start_time < ? AND e.end_time > ?' . $excludeClause,
                $params
            );
        }
        return Connection::fetchAll(
            'SELECT e.*, st.name AS staff_name
             FROM staff_assignments sa
             INNER JOIN events e ON e.id = sa.event_id
             INNER JOIN staff st ON st.id = sa.staff_id
             WHERE sa.staff_id = ?
               AND e.event_date = ?
               AND e.status <> "cancelled"
               AND sa.status <> "cancelled"' . $excludeClause,
            $params
        );
    }

    private function buildListWhere(?string $date, string $status): array
    {
        $clauses = [];
        $params = [];
        if ($date !== null && $date !== '') {
            $clauses[] = 'e.event_date = ?';
            $params[] = $date;
        }
        if ($status !== '' && $status !== 'all') {
            $clauses[] = 'e.status = ?';
            $params[] = $status;
        }
        $where = $clauses === [] ? '' : ' WHERE ' . implode(' AND ', $clauses);
        return [$params, $where];
    }
}
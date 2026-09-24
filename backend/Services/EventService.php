<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\BusinessException;
use App\Helpers\Pagination;
use App\Repositories\BookingRepository;
use App\Repositories\EventRepository;
use App\Repositories\StaffRepository;

class EventService
{
    private const TRANSITIONS = [
        'scheduled' => ['in_progress', 'completed', 'cancelled'],
        'in_progress' => ['completed', 'cancelled'],
        'completed' => [],
        'cancelled' => [],
    ];

    public function __construct(
        private EventRepository $eventRepository,
        private BookingRepository $bookingRepository,
        private StaffRepository $staffRepository,
        private ActivityService $activityService
    ) {
    }

    public function list(int $page, int $perPage, ?string $date, string $status): array
    {
        [$rows, $total] = $this->eventRepository->listPaginated($page, $perPage, $date, $status);
        foreach ($rows as &$row) {
            $row['assignments'] = $this->eventRepository->assignments((int)$row['id']);
        }
        unset($row);
        return Pagination::build($rows, $total, $page, $perPage);
    }

    public function schedule(string $date, string $status = 'all'): array
    {
        return $this->eventRepository->upcomingByDate($date, $status);
    }

    public function find(int $id): array
    {
        $event = $this->eventRepository->findDetailed($id);
        if ($event === null) {
            throw new BusinessException('Event not found.', 404);
        }
        $event['assignments'] = $this->eventRepository->assignments($id);
        return $event;
    }

    public function create(array $data, ?int $userId): array
    {
        $booking = $this->bookingRepository->find((int)$data['booking_id']);
        if ($booking === null) {
            throw new BusinessException('Booking not found.', 404);
        }
        $eventId = $this->eventRepository->insert([
            'booking_id' => (int)$booking['id'],
            'booking_service_id' => null,
            'title' => trim((string)($data['title'] ?? 'Service Event')),
            'event_date' => $data['event_date'] ?? $booking['event_date'],
            'start_time' => $this->normalizeTime($data['start_time'] ?? null),
            'end_time' => $this->normalizeTime($data['end_time'] ?? null),
            'venue_address' => $data['venue_address'] ?? $booking['venue_address'],
            'status' => 'scheduled',
            'notes' => $data['notes'] ?? null,
        ]);
        $this->activityService->log($userId, 'event.created', 'event', $eventId, 'Created event for booking ' . $booking['reference_no']);
        return $this->find($eventId);
    }

    public function update(int $id, array $data, ?int $userId): array
    {
        $event = $this->eventRepository->findOrFail($id);
        $startTime = $this->normalizeTime($data['start_time'] ?? $event['start_time']);
        $endTime = $this->normalizeTime($data['end_time'] ?? $event['end_time']);
        if ($startTime !== null && $endTime !== null && $endTime <= $startTime) {
            throw new BusinessException('The end time must be after the start time.');
        }
        $this->eventRepository->update($id, [
            'title' => $data['title'] ?? $event['title'],
            'event_date' => $data['event_date'] ?? $event['event_date'],
            'start_time' => $startTime,
            'end_time' => $endTime,
            'venue_address' => array_key_exists('venue_address', $data) ? $data['venue_address'] : $event['venue_address'],
            'notes' => array_key_exists('notes', $data) ? $data['notes'] : $event['notes'],
        ]);
        $this->activityService->log($userId, 'event.updated', 'event', $id, 'Updated event ' . $event['title']);
        return $this->find($id);
    }

    public function changeStatus(int $id, string $status, ?int $userId): array
    {
        $event = $this->eventRepository->findOrFail($id);
        $current = $event['status'];
        if (!isset(self::TRANSITIONS[$current]) || !in_array($status, self::TRANSITIONS[$current], true)) {
            throw new BusinessException("An event cannot move from {$current} to {$status}.");
        }
        $this->eventRepository->update($id, ['status' => $status]);
        $this->activityService->log($userId, 'event.status', 'event', $id, 'Set event status to ' . $status);
        return $this->find($id);
    }

    public function delete(int $id, ?int $userId): void
    {
        $event = $this->eventRepository->findOrFail($id);
        $this->eventRepository->delete($id);
        $this->activityService->log($userId, 'event.deleted', 'event', $id, 'Deleted event ' . $event['title']);
    }

    public function assignStaff(int $eventId, int $staffId, ?string $roleNote, ?int $userId): array
    {
        $event = $this->eventRepository->findOrFail($eventId);
        $staff = $this->staffRepository->find($staffId);
        if ($staff === null) {
            throw new BusinessException('Staff member not found.', 404);
        }
        if ($staff['status'] !== 'active') {
            throw new BusinessException('This staff member is inactive.');
        }
        $conflicts = $this->detectConflicts(
            $eventId,
            $staffId,
            (string)$event['event_date'],
            (string)($event['start_time'] ?? ''),
            (string)($event['end_time'] ?? '')
        );
        if ($conflicts !== []) {
            $titles = implode(', ', array_map(fn(array $row): string => $row['title'] . ' on ' . $row['event_date'], $conflicts));
            throw new BusinessException('Scheduling conflict: ' . trim((string)$staff['name']) . ' is already assigned to ' . $titles . '.', 409);
        }
        $this->staffRepository->assign($eventId, $staffId, $roleNote);
        $this->activityService->log($userId, 'assignment.created', 'event', $eventId, 'Assigned ' . $staff['name'] . ' to ' . $event['title']);
        return $this->find($eventId);
    }

    public function detectConflicts(int $eventId, int $staffId, string $date, string $startTime, string $endTime): array
    {
        return $this->eventRepository->conflictsForStaff($staffId, $date, $startTime, $endTime, $eventId);
    }

    public function removeAssignment(int $assignmentId, ?int $userId): void
    {
        $assignment = $this->staffRepository->assignment($assignmentId);
        if ($assignment === null) {
            throw new BusinessException('Assignment not found.', 404);
        }
        \App\Database\Connection::execute('DELETE FROM staff_assignments WHERE id = ?', [$assignmentId]);
        $this->activityService->log($userId, 'assignment.removed', 'event', (int)$assignment['event_id'], 'Removed assignment for ' . $assignment['staff_name']);
    }

    public function completeAssignment(int $assignmentId, ?int $userId): void
    {
        $assignment = $this->staffRepository->assignment($assignmentId);
        if ($assignment === null) {
            throw new BusinessException('Assignment not found.', 404);
        }
        \App\Database\Connection::execute('UPDATE staff_assignments SET status = "completed" WHERE id = ?', [$assignmentId]);
        $this->activityService->log($userId, 'assignment.completed', 'event', (int)$assignment['event_id'], 'Completed assignment for ' . $assignment['staff_name']);
    }

    private function normalizeTime($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $value = trim((string)$value);
        if (preg_match('/^\d{2}:\d{2}$/', $value) === 1) {
            return $value . ':00';
        }
        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $value) === 1) {
            return $value;
        }
        $timestamp = strtotime($value);
        return $timestamp === false ? null : date('H:i:s', $timestamp);
    }
}
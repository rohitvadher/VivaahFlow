<?php

declare(strict_types=1);

namespace App\Services;

use App\Calculations\CalculationEngine;
use App\Core\BusinessException;
use App\Database\Connection;
use App\Helpers\Money;
use App\Helpers\Pagination;
use App\Helpers\Reference;
use App\Repositories\BookingRepository;
use App\Repositories\EventRepository;

class BookingService
{
    private const TRANSITIONS = [
        'pending' => ['confirmed', 'cancelled'],
        'confirmed' => ['scheduled', 'in_progress', 'cancelled'],
        'scheduled' => ['in_progress', 'completed', 'cancelled'],
        'in_progress' => ['completed', 'cancelled'],
        'completed' => [],
        'cancelled' => [],
    ];

    public function __construct(
        private BookingRepository $bookingRepository,
        private EventRepository $eventRepository,
        private QuotationService $quotationService,
        private NotificationService $notificationService,
        private ActivityService $activityService
    ) {
    }

    public function list(
        int $page,
        int $perPage,
        string $search,
        string $status,
        ?string $dateFrom,
        ?string $dateTo
    ): array {
        [$rows, $total] = $this->bookingRepository->listPaginated($page, $perPage, $search, $status, $dateFrom, $dateTo);
        foreach ($rows as &$row) {
            $row = $this->decorate($row);
        }
        unset($row);
        return Pagination::build($rows, $total, $page, $perPage);
    }

    public function find(int $id): array
    {
        $booking = $this->bookingRepository->findDetailed($id);
        if ($booking === null) {
            throw new BusinessException('Booking not found.', 404);
        }
        $booking = $this->decorate($booking);
        $booking['services'] = $this->bookingRepository->services($id);
        $booking['events'] = $this->eventRepository->forBooking($id);
        foreach ($booking['events'] as &$event) {
            $event['assignments'] = $this->eventRepository->assignments((int)$event['id']);
        }
        unset($event);
        return $booking;
    }

    private function decorate(array $booking): array
    {
        $summary = CalculationEngine::bookingPaymentSummary((int)$booking['id'], $booking);
        $booking['subtotal_formatted'] = Money::format(Money::toCents($booking['subtotal']));
        $booking['discount_formatted'] = Money::format(Money::toCents($booking['discount_amount']));
        $booking['total_formatted'] = $summary['total'];
        $booking['paid_amount'] = $summary['paid'];
        $booking['paid_formatted'] = $summary['paid'];
        $booking['remaining_amount'] = $summary['remaining'];
        $booking['remaining_formatted'] = $summary['remaining'];
        $booking['payment_status'] = $summary['payment_status'];
        return $booking;
    }

    public function createFromQuotation(int $quotationId, array $data, ?int $userId): array
    {
        $quotation = $this->quotationService->toBookingSource($quotationId);
        $existing = $this->bookingRepository->findWhere(['quotation_id' => $quotationId]);
        if ($existing !== null) {
            throw new BusinessException('This quotation has already been converted to a booking.', 409);
        }
        $eventDate = trim((string)($data['event_date'] ?? ''));
        $parsed = \DateTime::createFromFormat('Y-m-d', $eventDate);
        if ($parsed === false || $parsed->format('Y-m-d') !== $eventDate) {
            throw new BusinessException('A valid event date is required to create a booking.');
        }
        $bookingId = Connection::transaction(function () use ($quotation, $data, $eventDate, $userId): int {
            $bookingId = $this->bookingRepository->insert([
                'reference_no' => Reference::next('booking'),
                'quotation_id' => (int)$quotation['id'],
                'customer_id' => (int)$quotation['customer_id'],
                'booking_date' => date('Y-m-d'),
                'event_date' => $eventDate,
                'event_type' => $data['event_type'] ?? null,
                'venue_address' => $data['venue_address'] ?? null,
                'subtotal' => $quotation['subtotal'],
                'discount_type' => $quotation['discount_type'],
                'discount_value' => $quotation['discount_value'],
                'discount_amount' => $quotation['discount_amount'],
                'total_amount' => $quotation['total_amount'],
                'status' => 'pending',
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);
            $items = [];
            foreach ($quotation['items'] as $item) {
                $items[] = [
                    'source_type' => $item['source_type'],
                    'source_id' => $item['source_id'],
                    'item_name' => $item['item_name'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'amount' => $item['amount'],
                    'notes' => $item['notes'],
                ];
            }
            $this->bookingRepository->replaceServices($bookingId, $items);
            foreach ($items as $item) {
                $this->eventRepository->insert([
                    'booking_id' => $bookingId,
                    'booking_service_id' => null,
                    'title' => $item['item_name'],
                    'event_date' => $eventDate,
                    'start_time' => null,
                    'end_time' => null,
                    'venue_address' => $data['venue_address'] ?? null,
                    'status' => 'scheduled',
                    'notes' => null,
                ]);
            }
            return $bookingId;
        });
        $booking = $this->find($bookingId);
        $this->notificationService->notifyAdmins(
            'Booking created',
            $booking['reference_no'] . ' created for ' . $booking['customer_name'],
            'success',
            \App\Helpers\FrontendRoutes::path('manage.booking', ['id' => $bookingId])
        );
        $this->activityService->log($userId, 'booking.created', 'booking', $bookingId, 'Created booking from quotation ' . $quotation['reference_no']);
        return $booking;
    }

    public function update(int $id, array $data, ?int $userId): array
    {
        $booking = $this->bookingRepository->findOrFail($id);
        if ($booking['status'] === 'cancelled') {
            throw new BusinessException('Cancelled bookings cannot be edited.');
        }
        $this->bookingRepository->update($id, [
            'booking_date' => $data['booking_date'] ?? $booking['booking_date'],
            'event_date' => $data['event_date'] ?? $booking['event_date'],
            'event_type' => array_key_exists('event_type', $data) ? $data['event_type'] : $booking['event_type'],
            'venue_address' => array_key_exists('venue_address', $data) ? $data['venue_address'] : $booking['venue_address'],
            'notes' => array_key_exists('notes', $data) ? $data['notes'] : $booking['notes'],
        ]);
        $this->activityService->log($userId, 'booking.updated', 'booking', $id, 'Updated booking ' . $booking['reference_no']);
        return $this->find($id);
    }

    public function changeStatus(int $id, string $status, ?int $userId): array
    {
        $booking = $this->bookingRepository->findOrFail($id);
        $current = $booking['status'];
        if (!isset(self::TRANSITIONS[$current]) || !in_array($status, self::TRANSITIONS[$current], true)) {
            throw new BusinessException("A booking cannot move from {$current} to {$status}.");
        }
        Connection::transaction(function () use ($id, $booking, $status): void {
            $this->bookingRepository->update($id, ['status' => $status]);
            if ($status === 'cancelled') {
                Connection::execute('UPDATE events SET status = "cancelled" WHERE booking_id = ?', [$id]);
            }
            if ($status === 'completed') {
                Connection::execute('UPDATE events SET status = "completed" WHERE booking_id = ? AND status <> "cancelled"', [$id]);
            }
        });
        $this->notificationService->notifyAdmins(
            'Booking status updated',
            $booking['reference_no'] . ' is now ' . str_replace('_', ' ', $status) . '.',
            'info',
            \App\Helpers\FrontendRoutes::path('manage.booking', ['id' => $id])
        );
        $this->activityService->log($userId, 'booking.status', 'booking', $id, 'Set booking status to ' . $status);
        return $this->find($id);
    }

    public function schedule(int $id, array $data, ?int $userId): array
    {
        $booking = $this->bookingRepository->findOrFail($id);
        if (in_array($booking['status'], ['cancelled', 'completed'], true)) {
            throw new BusinessException('This booking can no longer be scheduled.');
        }
        $eventDate = $data['event_date'] ?? $booking['event_date'];
        $venue = array_key_exists('venue_address', $data) ? $data['venue_address'] : $booking['venue_address'];
        Connection::transaction(function () use ($id, $booking, $eventDate, $venue): void {
            $this->bookingRepository->update($id, [
                'event_date' => $eventDate,
                'venue_address' => $venue,
                'status' => $booking['status'] === 'pending' ? 'confirmed' : 'scheduled',
            ]);
            Connection::execute(
                'UPDATE events SET event_date = ?, venue_address = ? WHERE booking_id = ? AND status <> "cancelled"',
                [$eventDate, $venue, $id]
            );
        });
        $this->activityService->log($userId, 'booking.scheduled', 'booking', $id, 'Scheduled booking ' . $booking['reference_no'] . ' for ' . $eventDate);
        return $this->find($id);
    }

    public function delete(int $id, ?int $userId): void
    {
        $booking = $this->bookingRepository->findOrFail($id);
        $payments = (int)Connection::fetchColumn('SELECT COUNT(*) FROM payments WHERE booking_id = ?', [$id]);
        if ($payments > 0) {
            throw new BusinessException('This booking has recorded payments and cannot be deleted. Cancel it instead.', 409);
        }
        $this->bookingRepository->delete($id);
        $this->activityService->log($userId, 'booking.deleted', 'booking', $id, 'Deleted booking ' . $booking['reference_no']);
    }

    public function forCustomer(int $customerId): array
    {
        $rows = $this->bookingRepository->forCustomer($customerId);
        foreach ($rows as &$row) {
            $row = $this->decorate($row);
        }
        unset($row);
        return $rows;
    }

    public function findForCustomer(int $id, int $customerId): array
    {
        $booking = $this->find($id);
        if ((int)$booking['customer_id'] !== $customerId) {
            throw new BusinessException('Booking not found.', 404);
        }
        return $booking;
    }
}
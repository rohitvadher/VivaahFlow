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
use App\Repositories\PaymentRepository;

class PaymentService
{
    public function __construct(
        private PaymentRepository $paymentRepository,
        private BookingRepository $bookingRepository,
        private InvoiceService $invoiceService,
        private NotificationService $notificationService,
        private ActivityService $activityService
    ) {
    }

    public function list(int $page, int $perPage, string $search, string $status): array
    {
        [$rows, $total] = $this->paymentRepository->listPaginated($page, $perPage, $search, $status);
        foreach ($rows as &$row) {
            $row['amount_formatted'] = Money::format(Money::toCents($row['amount']));
        }
        unset($row);
        return Pagination::build($rows, $total, $page, $perPage);
    }

    public function find(int $id): array
    {
        $payment = $this->paymentRepository->findDetailed($id);
        if ($payment === null) {
            throw new BusinessException('Payment not found.', 404);
        }
        $payment['amount_formatted'] = Money::format(Money::toCents($payment['amount']));
        return $payment;
    }

    public function create(int $bookingId, array $data, ?int $userId): array
    {
        $booking = $this->bookingRepository->find($bookingId);
        if ($booking === null) {
            throw new BusinessException('Booking not found.', 404);
        }
        if ($booking['status'] === 'cancelled') {
            throw new BusinessException('Payments cannot be recorded against a cancelled booking.');
        }
        $amountCents = Money::toCents($data['amount'] ?? 0);
        if ($amountCents <= 0) {
            throw new BusinessException('The payment amount must be greater than zero.');
        }
        $summary = CalculationEngine::bookingPaymentSummary($bookingId, $booking);
        if ($amountCents > $summary['remaining_cents']) {
            throw new BusinessException(
                'The payment exceeds the remaining balance of ' . $summary['remaining'] . '.'
            );
        }
        $method = trim((string)($data['method'] ?? ''));
        if ($method === '') {
            throw new BusinessException('A payment method is required.');
        }

        $paymentId = Connection::transaction(function () use ($booking, $bookingId, $amountCents, $data, $method, $userId): int {
            $paymentId = $this->paymentRepository->insert([
                'booking_id' => $bookingId,
                'customer_id' => (int)$booking['customer_id'],
                'amount' => Money::fromCents($amountCents),
                'payment_date' => $data['payment_date'] ?? date('Y-m-d'),
                'method' => $method,
                'reference_no' => $data['reference_no'] ?? Reference::next('payment'),
                'notes' => $data['notes'] ?? null,
                'status' => 'recorded',
                'created_by' => $userId,
            ]);
            $this->invoiceService->refreshStatus($bookingId);
            return $paymentId;
        });

        $payment = $this->find($paymentId);
        $this->notificationService->notifyAdmins(
            'Payment recorded',
            $payment['amount_formatted'] . ' received for booking ' . $booking['reference_no'],
            'success',
            \App\Helpers\FrontendRoutes::path('manage.payments')
        );
        $this->activityService->log($userId, 'payment.created', 'payment', $paymentId, 'Recorded payment for ' . $booking['reference_no']);
        return $payment;
    }

    public function reverse(int $id, ?int $userId): array
    {
        $payment = $this->paymentRepository->findOrFail($id);
        if ($payment['status'] !== 'recorded') {
            throw new BusinessException('Only recorded payments can be reversed.');
        }
        $this->paymentRepository->update($id, ['status' => 'reversed']);
        $this->invoiceService->refreshStatus((int)$payment['booking_id']);
        $this->activityService->log($userId, 'payment.reversed', 'payment', $id, 'Reversed payment ' . $payment['reference_no']);
        return $this->find($id);
    }

    public function forCustomer(int $customerId): array
    {
        $rows = Connection::fetchAll(
            'SELECT p.*, b.reference_no AS booking_no FROM payments p
             INNER JOIN bookings b ON b.id = p.booking_id
             WHERE p.customer_id = ?
             ORDER BY p.payment_date DESC, p.id DESC',
            [$customerId]
        );
        foreach ($rows as &$row) {
            $row['amount_formatted'] = Money::format(Money::toCents($row['amount']));
        }
        unset($row);
        return $rows;
    }
}
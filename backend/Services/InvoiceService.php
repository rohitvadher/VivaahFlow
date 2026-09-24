<?php

declare(strict_types=1);

namespace App\Services;

use App\Calculations\CalculationEngine;
use App\Core\BusinessException;
use App\Helpers\Money;
use App\Helpers\Pagination;
use App\Helpers\Reference;
use App\Repositories\BookingRepository;
use App\Repositories\InvoiceRepository;

class InvoiceService
{
    public function __construct(
        private InvoiceRepository $invoiceRepository,
        private BookingRepository $bookingRepository,
        private ActivityService $activityService
    ) {
    }

    public function list(int $page, int $perPage, string $search, string $status): array
    {
        [$rows, $total] = $this->invoiceRepository->listPaginated($page, $perPage, $search, $status);
        foreach ($rows as &$row) {
            $row = $this->decorate($row);
        }
        unset($row);
        return Pagination::build($rows, $total, $page, $perPage);
    }

    public function find(int $id): array
    {
        $invoice = $this->invoiceRepository->findDetailed($id);
        if ($invoice === null) {
            throw new BusinessException('Invoice not found.', 404);
        }
        $invoice = $this->decorate($invoice);
        $invoice['booking'] = $this->bookingRepository->findDetailed((int)$invoice['booking_id']);
        $invoice['services'] = $this->bookingRepository->services((int)$invoice['booking_id']);
        return $invoice;
    }

    public function forCustomer(int $customerId): array
    {
        $rows = $this->invoiceRepository->forCustomer($customerId);
        foreach ($rows as &$row) {
            $row = $this->decorate($row);
        }
        unset($row);
        return $rows;
    }

    public function findForCustomer(int $id, int $customerId): array
    {
        $invoice = $this->invoiceRepository->findForCustomer($id, $customerId);
        if ($invoice === null) {
            throw new BusinessException('Invoice not found.', 404);
        }
        $invoice = $this->decorate($invoice);
        $invoice['services'] = $this->bookingRepository->services((int)$invoice['booking_id']);
        return $invoice;
    }

    private function decorate(array $invoice): array
    {
        $paidCents = Money::toCents($invoice['paid'] ?? 0);
        $invoice['paid_formatted'] = Money::format($paidCents);
        $invoice['total_formatted'] = Money::format(Money::toCents($invoice['total_amount']));
        $invoice['remaining_cents'] = max(0, Money::toCents($invoice['total_amount']) - $paidCents);
        $invoice['remaining_formatted'] = Money::format($invoice['remaining_cents']);
        $invoice['computed_status'] = CalculationEngine::invoiceStatus($invoice, $paidCents);
        return $invoice;
    }

    public function createFromBooking(int $bookingId, ?int $userId, array $data = []): array
    {
        $booking = $this->bookingRepository->findDetailed($bookingId);
        if ($booking === null) {
            throw new BusinessException('Booking not found.', 404);
        }
        $existing = $this->invoiceRepository->forBooking($bookingId);
        if ($existing !== null) {
            return $this->find((int)$existing['id']);
        }
        $issueDate = $data['issue_date'] ?? date('Y-m-d');
        $dueDate = $data['due_date'] ?? date('Y-m-d', strtotime('+14 days'));
        $invoiceId = $this->invoiceRepository->insert([
            'booking_id' => $bookingId,
            'invoice_number' => Reference::next('invoice'),
            'issue_date' => $issueDate,
            'due_date' => $dueDate,
            'subtotal' => $booking['subtotal'],
            'discount_amount' => $booking['discount_amount'],
            'total_amount' => $booking['total_amount'],
            'status' => 'issued',
            'notes' => $data['notes'] ?? null,
            'created_by' => $userId,
        ]);
        $this->activityService->log($userId, 'invoice.created', 'invoice', $invoiceId, 'Generated invoice for booking ' . $booking['reference_no']);
        return $this->find($invoiceId);
    }

    public function changeStatus(int $id, string $status, ?int $userId): array
    {
        if (!in_array($status, ['draft', 'issued', 'cancelled'], true)) {
            throw new BusinessException('Invoices can only be set to draft, issued or cancelled.');
        }
        $invoice = $this->invoiceRepository->findOrFail($id);
        $this->invoiceRepository->update($id, ['status' => $status]);
        $this->activityService->log($userId, 'invoice.status', 'invoice', $id, 'Set invoice status to ' . $status);
        return $this->find($id);
    }

    public function refreshStatus(int $bookingId): void
    {
        $invoice = $this->invoiceRepository->forBooking($bookingId);
        if ($invoice === null || in_array($invoice['status'], ['draft', 'cancelled'], true)) {
            return;
        }
        $paidCents = CalculationEngine::paidAmount($bookingId);
        $status = CalculationEngine::invoiceStatus($invoice, $paidCents);
        if ($status !== $invoice['status']) {
            $this->invoiceRepository->update((int)$invoice['id'], ['status' => $status]);
        }
    }

    public function delete(int $id, ?int $userId): void
    {
        $invoice = $this->invoiceRepository->findOrFail($id);
        $this->invoiceRepository->delete($id);
        $this->activityService->log($userId, 'invoice.deleted', 'invoice', $id, 'Deleted invoice ' . $invoice['invoice_number']);
    }
}
<?php

declare(strict_types=1);

namespace App\Services;

use App\Calculations\CalculationEngine;
use App\Database\Connection;
use App\Helpers\Money;
use App\Repositories\ActivityLogRepository;
use App\Repositories\BookingRepository;
use App\Repositories\LeadRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\QuotationRepository;

class ReportService
{
    public function __construct(
        private BookingRepository $bookingRepository,
        private PaymentRepository $paymentRepository,
        private QuotationRepository $quotationRepository,
        private LeadRepository $leadRepository,
        private ActivityLogRepository $activityLogRepository
    ) {
    }

    public function dashboard(): array
    {
        return [
            'kpis' => CalculationEngine::dashboardKpis(),
            'revenue' => CalculationEngine::revenueByMonth(6),
            'bookings_by_month' => CalculationEngine::bookingsByMonth(6),
            'booking_status' => CalculationEngine::bookingStatusDistribution(),
            'quotation_conversion' => CalculationEngine::quotationConversion(),
            'lead_conversion' => CalculationEngine::leadConversion(),
            'service_performance' => CalculationEngine::servicePerformance(6),
            'top_customers' => CalculationEngine::topCustomers(5),
            'upcoming_bookings' => $this->bookingRepository->upcoming(6),
            'recent_bookings' => $this->bookingRepository->recent(6),
            'pending_payments' => $this->paymentRepository->pendingPaymentBookings(6),
            'pending_quotations' => $this->quotationRepository->pendingQuotations(6),
            'upcoming_followups' => $this->leadRepository->upcomingFollowups(6),
            'recent_activities' => $this->activityLogRepository->recent(8),
        ];
    }

    public function summary(string $range): array
    {
        return CalculationEngine::reportSummary($range);
    }

    public function revenueReport(string $from, string $to): array
    {
        $rows = Connection::fetchAll(
            'SELECT p.payment_date, p.reference_no, p.method, p.amount, p.status,
                    b.reference_no AS booking_no, c.name AS customer_name
             FROM payments p
             INNER JOIN bookings b ON b.id = p.booking_id
             INNER JOIN customers c ON c.id = b.customer_id
             WHERE p.payment_date BETWEEN ? AND ?
             ORDER BY p.payment_date ASC, p.id ASC',
            [$from, $to]
        );
        $recordedCents = 0;
        $reversedCents = 0;
        foreach ($rows as &$row) {
            $row['amount_formatted'] = Money::format(Money::toCents($row['amount']));
            if ($row['status'] === 'recorded') {
                $recordedCents += Money::toCents($row['amount']);
            } else {
                $reversedCents += Money::toCents($row['amount']);
            }
        }
        unset($row);
        $byMethod = Connection::fetchAll(
            'SELECT method, COUNT(*) AS total_count, COALESCE(SUM(amount), 0) AS total_amount
             FROM payments
             WHERE status = "recorded" AND payment_date BETWEEN ? AND ?
             GROUP BY method
             ORDER BY total_amount DESC',
            [$from, $to]
        );
        foreach ($byMethod as &$method) {
            $method['total_formatted'] = Money::format(Money::toCents($method['total_amount']));
        }
        unset($method);
        return [
            'range' => ['from' => $from, 'to' => $to],
            'totals' => [
                'recorded_cents' => $recordedCents,
                'recorded' => Money::format($recordedCents),
                'reversed_cents' => $reversedCents,
                'reversed' => Money::format($reversedCents),
                'net_cents' => $recordedCents - $reversedCents,
                'net' => Money::format($recordedCents - $reversedCents),
            ],
            'rows' => $rows,
            'by_method' => $byMethod,
        ];
    }

    public function bookingsReport(string $from, string $to): array
    {
        $rows = Connection::fetchAll(
            'SELECT b.reference_no, b.event_date, b.event_type, b.total_amount, b.status,
                    c.name AS customer_name,
                    (SELECT COALESCE(SUM(p.amount), 0) FROM payments p WHERE p.booking_id = b.id AND p.status = "recorded") AS paid
             FROM bookings b
             INNER JOIN customers c ON c.id = b.customer_id
             WHERE b.event_date BETWEEN ? AND ?
             ORDER BY b.event_date ASC',
            [$from, $to]
        );
        $totalCents = 0;
        $paidCents = 0;
        foreach ($rows as &$row) {
            $row['total_formatted'] = Money::format(Money::toCents($row['total_amount']));
            $row['paid_formatted'] = Money::format(Money::toCents($row['paid']));
            $row['remaining_formatted'] = Money::format(max(0, Money::toCents($row['total_amount']) - Money::toCents($row['paid'])));
            if ($row['status'] !== 'cancelled') {
                $totalCents += Money::toCents($row['total_amount']);
                $paidCents += Money::toCents($row['paid']);
            }
        }
        unset($row);
        return [
            'range' => ['from' => $from, 'to' => $to],
            'totals' => [
                'bookings' => count($rows),
                'value_cents' => $totalCents,
                'value' => Money::format($totalCents),
                'collected_cents' => $paidCents,
                'collected' => Money::format($paidCents),
                'outstanding_cents' => max(0, $totalCents - $paidCents),
                'outstanding' => Money::format(max(0, $totalCents - $paidCents)),
            ],
            'rows' => $rows,
        ];
    }
}
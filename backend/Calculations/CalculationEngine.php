<?php

declare(strict_types=1);

namespace App\Calculations;

use App\Database\Connection;
use App\Helpers\Money;
use App\Repositories\BookingRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\EnquiryRepository;
use App\Repositories\LeadRepository;
use App\Repositories\OfferRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\QuotationRepository;
use App\Repositories\ReviewRepository;
use App\Repositories\StaffRepository;

class CalculationEngine
{
    public static function serviceAmount(array $service, int $quantity): int
    {
        return Money::toCents($service['starting_price'] ?? 0) * max(1, $quantity);
    }

    public static function discountAmount(string $discountType, $discountValue, int $baseCents): int
    {
        if ($baseCents <= 0 || (float)$discountValue <= 0) {
            return 0;
        }
        if ($discountType === 'percent') {
            return min($baseCents, (int)round($baseCents * (float)$discountValue / 100));
        }
        return min($baseCents, Money::toCents($discountValue));
    }

    public static function packageAmount(array $package, array $services): array
    {
        $baseCents = 0;
        foreach ($services as $service) {
            $baseCents += Money::toCents($service['starting_price'] ?? 0) * max(1, (int)($service['quantity'] ?? 1));
        }
        $discountCents = self::discountAmount(
            $package['discount_type'] ?? 'fixed',
            $package['discount_value'] ?? 0,
            $baseCents
        );
        return [
            'base_amount' => Money::fromCents($baseCents),
            'base_cents' => $baseCents,
            'discount_amount' => Money::fromCents($discountCents),
            'discount_cents' => $discountCents,
            'total_amount' => Money::fromCents(max(0, $baseCents - $discountCents)),
            'total_cents' => max(0, $baseCents - $discountCents),
        ];
    }

    public static function quotationTotals(int $subtotalCents, ?string $discountType, $discountValue): array
    {
        $discountCents = $discountType !== null
            ? self::discountAmount($discountType, $discountValue ?? 0, $subtotalCents)
            : 0;
        $totalCents = max(0, $subtotalCents - $discountCents);
        return [
            'subtotal' => Money::fromCents($subtotalCents),
            'subtotal_cents' => $subtotalCents,
            'discount_amount' => Money::fromCents($discountCents),
            'discount_cents' => $discountCents,
            'total_amount' => Money::fromCents($totalCents),
            'total_cents' => $totalCents,
        ];
    }

    public static function itemsSubtotal(array $items): int
    {
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += Money::toCents($item['unit_price'] ?? 0) * max(1, (int)($item['quantity'] ?? 1));
        }
        return $subtotal;
    }

    public static function paidAmount(int $bookingId): int
    {
        return Money::toCents(Connection::fetchColumn(
            'SELECT COALESCE(SUM(amount), 0) FROM payments WHERE booking_id = ? AND status = "recorded"',
            [$bookingId]
        ));
    }

    public static function bookingPaymentSummary(int $bookingId, ?array $booking = null): array
    {
        $booking = $booking ?? (new BookingRepository())->find($bookingId);
        $totalCents = Money::toCents($booking['total_amount'] ?? 0);
        $paidCents = self::paidAmount($bookingId);
        return [
            'total' => Money::format($totalCents),
            'total_cents' => $totalCents,
            'paid' => Money::format($paidCents),
            'paid_cents' => $paidCents,
            'remaining' => Money::format(max(0, $totalCents - $paidCents)),
            'remaining_cents' => max(0, $totalCents - $paidCents),
            'payment_status' => self::paymentStatus($totalCents, $paidCents),
        ];
    }

    public static function paymentStatus(int $totalCents, int $paidCents): string
    {
        if ($totalCents <= 0) {
            return 'paid';
        }
        if ($paidCents <= 0) {
            return 'unpaid';
        }
        if ($paidCents >= $totalCents) {
            return 'paid';
        }
        return 'partial';
    }

    public static function invoiceStatus(array $invoice, int $paidCents): string
    {
        if (($invoice['status'] ?? 'draft') === 'cancelled') {
            return 'cancelled';
        }
        if (($invoice['status'] ?? 'draft') === 'draft') {
            return 'draft';
        }
        $totalCents = Money::toCents($invoice['total_amount'] ?? 0);
        if ($totalCents > 0 && $paidCents >= $totalCents) {
            return 'paid';
        }
        $dueDate = $invoice['due_date'] ?? null;
        if ($dueDate !== null && $dueDate < date('Y-m-d') && $paidCents > 0) {
            return 'overdue';
        }
        if (($invoice['status'] ?? '') === 'partial' || ($invoice['status'] ?? '') === 'issued' && $paidCents > 0) {
            return 'partial';
        }
        if ($paidCents > 0) {
            return 'partial';
        }
        return 'issued';
    }

    public static function leadStatus(bool $hasEnquiry, bool $convertedToBooking, bool $lost): string
    {
        if ($lost) {
            return 'lost';
        }
        if ($convertedToBooking) {
            return 'converted';
        }
        return $hasEnquiry ? 'follow_up' : 'contacted';
    }

    public static function dashboardKpis(): array
    {
        $totalCents = Money::toCents((new PaymentRepository())->totalRecorded());
        $activeBookings = (int)Connection::fetchColumn(
            'SELECT COUNT(*) FROM bookings WHERE status IN ("confirmed", "scheduled", "in_progress")'
        );
        $upcomingBookings = (int)Connection::fetchColumn(
            'SELECT COUNT(*) FROM bookings
             WHERE status IN ("confirmed", "scheduled", "in_progress") AND event_date >= ?',
            [date('Y-m-d')]
        );
        $pendingCents = Money::toCents((int)Connection::fetchColumn(
            'SELECT COALESCE(SUM(CASE WHEN total_amount > COALESCE(paid.amount, 0) THEN total_amount - COALESCE(paid.amount, 0) ELSE 0 END), 0)
             FROM bookings b
             LEFT JOIN (
                SELECT booking_id, SUM(amount) AS amount FROM payments WHERE status = "recorded" GROUP BY booking_id
             ) paid ON paid.booking_id = b.id
             WHERE b.status <> "cancelled"'
        ));
        $completedServices = (int)Connection::fetchColumn(
            'SELECT COUNT(*) FROM events WHERE status = "completed"'
        );
        $pendingFollowups = (new LeadRepository())->pendingFollowupsCount();

        return [
            'customers_total' => (new CustomerRepository())->count(),
            'enquiries_total' => (new EnquiryRepository())->count(),
            'active_bookings' => $activeBookings,
            'upcoming_bookings' => $upcomingBookings,
            'total_revenue_cents' => $totalCents,
            'total_revenue' => Money::format($totalCents),
            'pending_payments_cents' => $pendingCents,
            'pending_payments' => Money::format($pendingCents),
            'completed_services' => $completedServices,
            'pending_followups' => $pendingFollowups,
            'pending_quotations' => (new QuotationRepository())->count("status IN ('draft', 'sent')"),
            'active_offers' => count((new OfferRepository())->activeOffers()),
            'staff_total' => (new StaffRepository())->count("status = 'active'"),
        ];
    }

    public static function revenueByMonth(int $months): array
    {
        $rows = (new PaymentRepository())->monthlyRevenue($months);
        $labels = [];
        $values = [];
        $start = new \DateTimeImmutable(date('Y-m-01'));
        for ($index = $months - 1; $index >= 0; $index--) {
            $date = $start->modify("-{$index} months");
            $key = $date->format('Y-m');
            $labels[] = $date->format('M Y');
            $values[] = 0;
            foreach ($rows as $row) {
                if ($row['period'] === $key) {
                    $values[count($values) - 1] = Money::toCents($row['revenue']);
                }
            }
        }
        return ['labels' => $labels, 'values' => $values];
    }

    public static function bookingsByMonth(int $months): array
    {
        $start = new \DateTimeImmutable(date('Y-m-01'));
        $from = $start->modify('-' . ($months - 1) . ' months')->format('Y-m-d');
        $rows = Connection::fetchAll(
            'SELECT DATE_FORMAT(created_at, "%Y-%m") AS period, COUNT(*) AS total
             FROM bookings WHERE created_at >= ?
             GROUP BY period',
            [$from]
        );
        $labels = [];
        $values = [];
        for ($index = $months - 1; $index >= 0; $index--) {
            $date = $start->modify("-{$index} months");
            $labels[] = $date->format('M Y');
            $values[] = 0;
            foreach ($rows as $row) {
                if ($row['period'] === $date->format('Y-m')) {
                    $values[count($values) - 1] = (int)$row['total'];
                }
            }
        }
        return ['labels' => $labels, 'values' => $values];
    }

    public static function bookingStatusDistribution(): array
    {
        $rows = Connection::fetchAll('SELECT status, COUNT(*) AS total FROM bookings GROUP BY status');
        $statuses = ['pending', 'confirmed', 'scheduled', 'in_progress', 'completed', 'cancelled'];
        $labels = [];
        $values = [];
        foreach ($statuses as $status) {
            $labels[] = ucfirst($status);
            $values[] = 0;
            foreach ($rows as $row) {
                if ($row['status'] === $status) {
                    $values[count($values) - 1] = (int)$row['total'];
                }
            }
        }
        return ['labels' => $labels, 'values' => $values];
    }

    public static function servicePerformance(int $limit = 8): array
    {
        $rows = Connection::fetchAll(
            'SELECT s.name AS service_name, COUNT(bs.id) AS usage_count, SUM(bs.amount) AS revenue_cents
             FROM booking_services bs
             INNER JOIN services s ON s.id = bs.source_id AND bs.source_type = "service"
             GROUP BY s.id, s.name
             ORDER BY usage_count DESC
             LIMIT ?',
            [$limit]
        );
        $labels = [];
        $counts = [];
        $revenue = [];
        foreach ($rows as $row) {
            $labels[] = $row['service_name'];
            $counts[] = (int)$row['usage_count'];
            $revenue[] = Money::toCents($row['revenue_cents']);
        }
        return [
            'labels' => $labels,
            'counts' => $counts,
            'revenue' => $revenue,
        ];
    }

    public static function quotationConversion(): array
    {
        $rows = Connection::fetchAll('SELECT status, COUNT(*) AS total FROM quotations GROUP BY status');
        $statuses = ['draft', 'sent', 'accepted', 'rejected', 'expired'];
        $labels = [];
        $values = [];
        foreach ($statuses as $status) {
            $labels[] = ucfirst($status);
            $values[] = 0;
            foreach ($rows as $row) {
                if ($row['status'] === $status) {
                    $values[count($values) - 1] = (int)$row['total'];
                }
            }
        }
        $total = array_sum($values);
        $accepted = $values[2] ?? 0;
        return [
            'labels' => $labels,
            'values' => $values,
            'total' => $total,
            'accepted' => $accepted,
            'acceptance_rate' => $total > 0 ? round($accepted / $total * 100, 1) : 0,
        ];
    }

    public static function leadConversion(): array
    {
        $rows = Connection::fetchAll('SELECT status, COUNT(*) AS total FROM leads GROUP BY status');
        $statuses = ['new', 'contacted', 'follow_up', 'converted', 'lost'];
        $labels = [];
        $values = [];
        foreach ($statuses as $status) {
            $labels[] = ucfirst(str_replace('_', ' ', $status));
            $values[] = 0;
            foreach ($rows as $row) {
                if ($row['status'] === $status) {
                    $values[count($values) - 1] = (int)$row['total'];
                }
            }
        }
        $total = array_sum($values);
        $converted = $values[3] ?? 0;
        return [
            'labels' => $labels,
            'values' => $values,
            'total' => $total,
            'converted' => $converted,
            'conversion_rate' => $total > 0 ? round($converted / $total * 100, 1) : 0,
        ];
    }

    public static function topCustomers(int $limit = 6): array
    {
        $rows = Connection::fetchAll(
            'SELECT c.name AS customer_name, c.email, COUNT(DISTINCT b.id) AS booking_count,
                    COALESCE(SUM(p.amount), 0) AS paid_cents
             FROM customers c
             LEFT JOIN bookings b ON b.customer_id = c.id AND b.status <> "cancelled"
             LEFT JOIN payments p ON p.booking_id = b.id AND p.status = "recorded"
             GROUP BY c.id, c.name, c.email
             ORDER BY paid_cents DESC
             LIMIT ?',
            [$limit]
        );
        foreach ($rows as &$row) {
            $row['paid_cents'] = Money::toCents($row['paid_cents']);
            $row['paid_formatted'] = Money::format($row['paid_cents']);
        }
        unset($row);
        return $rows;
    }

    public static function recentActivities(int $limit = 10): array
    {
        return Connection::fetchAll(
            'SELECT a.*, u.name AS user_name
             FROM activity_logs a
             LEFT JOIN users u ON u.id = a.user_id
             ORDER BY a.created_at DESC, a.id DESC
             LIMIT ?',
            [$limit]
        );
    }

    public static function reportSummary(string $range): array
    {
        $months = match ($range) {
            'month' => 1,
            'year' => 12,
            default => 6,
        };
        return [
            'kpis' => self::dashboardKpis(),
            'revenue' => self::revenueByMonth($months),
            'bookings' => self::bookingsByMonth($months),
            'booking_status' => self::bookingStatusDistribution(),
            'services' => self::servicePerformance(),
            'quotations' => self::quotationConversion(),
            'leads' => self::leadConversion(),
            'top_customers' => self::topCustomers(),
            'reviews' => [
                'average' => (new ReviewRepository())->averageGlobal(),
                'distribution' => (new ReviewRepository())->ratingSummary(),
            ],
        ];
    }
}
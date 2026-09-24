<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Database\Connection;

class Reference
{
    private const DEFINITIONS = [
        'customer' => ['table' => 'customers', 'column' => 'id', 'prefix' => 'CUS'],
        'category' => ['table' => 'service_categories', 'column' => 'id', 'prefix' => 'CAT'],
        'service' => ['table' => 'services', 'column' => 'id', 'prefix' => 'SVC'],
        'package' => ['table' => 'packages', 'column' => 'id', 'prefix' => 'PKG'],
        'enquiry' => ['table' => 'enquiries', 'column' => 'reference_no', 'prefix' => 'ENQ'],
        'quotation' => ['table' => 'quotations', 'column' => 'reference_no', 'prefix' => 'QTN'],
        'booking' => ['table' => 'bookings', 'column' => 'reference_no', 'prefix' => 'BKG'],
        'payment' => ['table' => 'payments', 'column' => 'reference_no', 'prefix' => 'PAY'],
        'invoice' => ['table' => 'invoices', 'column' => 'invoice_number', 'prefix' => 'INV'],
        'lead' => ['table' => 'leads', 'column' => 'id', 'prefix' => 'LEA'],
        'event' => ['table' => 'events', 'column' => 'id', 'prefix' => 'EVT'],
        'staff' => ['table' => 'staff', 'column' => 'id', 'prefix' => 'STF'],
        'review' => ['table' => 'reviews', 'column' => 'id', 'prefix' => 'RVW'],
        'offer' => ['table' => 'offers', 'column' => 'id', 'prefix' => 'OFR'],
        'user' => ['table' => 'users', 'column' => 'id', 'prefix' => 'USR'],
    ];

    public static function next(string $kind): string
    {
        $definition = self::DEFINITIONS[$kind] ?? null;
        if ($definition === null) {
            throw new \InvalidArgumentException("Unknown reference kind: {$kind}");
        }
        $year = date('Y');
        $prefix = $definition['prefix'] . '-' . $year . '-';
        if ($definition['column'] === 'id') {
            $count = (int)Connection::fetchColumn("SELECT COUNT(*) FROM {$definition['table']}", []);
        } else {
            $count = (int)Connection::fetchColumn(
                "SELECT COUNT(*) FROM {$definition['table']} WHERE {$definition['column']} LIKE ?",
                [$prefix . '%']
            );
        }
        return sprintf('%s%03d', $prefix, $count + 1);
    }
}
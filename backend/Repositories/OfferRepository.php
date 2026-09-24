<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Connection;

class OfferRepository extends BaseRepository
{
    protected string $table = 'offers';

    public function listPaginated(int $page, int $perPage, string $search, string $status): array
    {
        [$params, $where] = $this->buildListWhere($search, $status);
        $total = (int)Connection::fetchColumn('SELECT COUNT(*) FROM offers' . $where, $params);
        $rows = Connection::fetchAll(
            'SELECT * FROM offers' . $where . ' ORDER BY start_date DESC, id DESC LIMIT ? OFFSET ?',
            array_merge($params, [$perPage, ($page - 1) * $perPage])
        );
        return [$rows, $total];
    }

    public function activeOffers(): array
    {
        $today = date('Y-m-d');
        return Connection::fetchAll(
            'SELECT * FROM offers
             WHERE status = "active" AND start_date <= ? AND end_date >= ?
             ORDER BY start_date DESC, id DESC LIMIT 100',
            [$today, $today]
        );
    }

    public function isApplicable(int $offerId, string $entityType, int $entityId): bool
    {
        $offer = $this->find($offerId);
        if ($offer === null) {
            return false;
        }
        if ($offer['applicable_to'] === 'all') {
            return true;
        }
        if ($offer['applicable_to'] === 'services' && $entityType === 'service') {
            $value = Connection::fetchColumn(
                'SELECT COUNT(*) FROM offer_services WHERE offer_id = ? AND service_id = ?',
                [$offerId, $entityId]
            );
            return (int)$value > 0;
        }
        if ($offer['applicable_to'] === 'packages' && $entityType === 'package') {
            $value = Connection::fetchColumn(
                'SELECT COUNT(*) FROM offer_packages WHERE offer_id = ? AND package_id = ?',
                [$offerId, $entityId]
            );
            return (int)$value > 0;
        }
        return false;
    }

    public function syncLinks(int $offerId, string $applicableTo, array $serviceIds, array $packageIds): void
    {
        Connection::execute('DELETE FROM offer_services WHERE offer_id = ?', [$offerId]);
        Connection::execute('DELETE FROM offer_packages WHERE offer_id = ?', [$offerId]);
        if ($applicableTo === 'services') {
            foreach ($serviceIds as $serviceId) {
                if (is_numeric($serviceId)) {
                    Connection::execute(
                        'INSERT INTO offer_services (offer_id, service_id) VALUES (?, ?)',
                        [$offerId, (int)$serviceId]
                    );
                }
            }
        }
        if ($applicableTo === 'packages') {
            foreach ($packageIds as $packageId) {
                if (is_numeric($packageId)) {
                    Connection::execute(
                        'INSERT INTO offer_packages (offer_id, package_id) VALUES (?, ?)',
                        [$offerId, (int)$packageId]
                    );
                }
            }
        }
    }

    public function serviceIds(int $offerId): array
    {
        return array_map(
            fn(array $row): int => (int)$row['service_id'],
            Connection::fetchAll('SELECT service_id FROM offer_services WHERE offer_id = ?', [$offerId])
        );
    }

    public function packageIds(int $offerId): array
    {
        return array_map(
            fn(array $row): int => (int)$row['package_id'],
            Connection::fetchAll('SELECT package_id FROM offer_packages WHERE offer_id = ?', [$offerId])
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
            $clauses[] = '(name LIKE ?)';
            $params[] = '%' . $search . '%';
        }
        $where = $clauses === [] ? '' : ' WHERE ' . implode(' AND ', $clauses);
        return [$params, $where];
    }
}
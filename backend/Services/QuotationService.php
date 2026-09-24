<?php

declare(strict_types=1);

namespace App\Services;

use App\Calculations\CalculationEngine;
use App\Core\BusinessException;
use App\Database\Connection;
use App\Helpers\Money;
use App\Helpers\Pagination;
use App\Helpers\Reference;
use App\Repositories\EnquiryRepository;
use App\Repositories\PackageRepository;
use App\Repositories\QuotationRepository;
use App\Repositories\ServiceRepository;

class QuotationService
{
    public const VALID_STATUSES = ['draft', 'sent', 'accepted', 'rejected', 'expired'];
    private const TRANSITIONS = [
        'draft' => ['sent'],
        'sent' => ['accepted', 'rejected', 'expired'],
        'accepted' => [],
        'rejected' => [],
        'expired' => [],
    ];

    public function __construct(
        private QuotationRepository $quotationRepository,
        private EnquiryRepository $enquiryRepository,
        private ServiceRepository $serviceRepository,
        private PackageRepository $packageRepository,
        private NotificationService $notificationService,
        private ActivityService $activityService
    ) {
    }

    public function list(int $page, int $perPage, string $search, string $status): array
    {
        [$rows, $total] = $this->quotationRepository->listPaginated($page, $perPage, $search, $status);
        foreach ($rows as &$row) {
            $row = $this->decorate($row);
        }
        unset($row);
        return Pagination::build($rows, $total, $page, $perPage);
    }

    public function find(int $id): array
    {
        $quotation = $this->quotationRepository->findDetailed($id);
        if ($quotation === null) {
            throw new BusinessException('Quotation not found.', 404);
        }
        $quotation = $this->decorate($quotation);
        $quotation['items'] = $this->quotationRepository->items($id);
        return $quotation;
    }

    private function decorate(array $quotation): array
    {
        $quotation = $this->applyExpiry($quotation);
        $quotation['subtotal_formatted'] = Money::format(Money::toCents($quotation['subtotal']));
        $quotation['discount_formatted'] = Money::format(Money::toCents($quotation['discount_amount']));
        $quotation['total_formatted'] = Money::format(Money::toCents($quotation['total_amount']));
        $quotation['is_expired'] = $quotation['status'] === 'expired';
        return $quotation;
    }

    private function applyExpiry(array $quotation): array
    {
        if (!in_array($quotation['status'], ['draft', 'sent'], true)) {
            return $quotation;
        }
        if ($quotation['valid_until'] === null || $quotation['valid_until'] === '') {
            return $quotation;
        }
        if ($quotation['valid_until'] < date('Y-m-d')) {
            $this->quotationRepository->update((int)$quotation['id'], ['status' => 'expired']);
            $quotation['status'] = 'expired';
        }
        return $quotation;
    }

    public function createFromEnquiry(int $enquiryId, ?int $userId): array
    {
        $enquiry = $this->enquiryRepository->findDetailed($enquiryId);
        if ($enquiry === null) {
            throw new BusinessException('Enquiry not found.', 404);
        }
        $existing = $this->quotationRepository->findWhere(['enquiry_id' => $enquiryId]);
        if ($existing !== null) {
            return $this->find((int)$existing['id']);
        }
        $enquiryItems = $this->enquiryRepository->items($enquiryId);
        if ($enquiryItems === []) {
            throw new BusinessException('This enquiry has no selected services.');
        }
        $items = [];
        foreach ($enquiryItems as $enquiryItem) {
            $quantity = max(1, (int)$enquiryItem['quantity']);
            if ($enquiryItem['source_type'] === 'package') {
                $package = $this->packageRepository->find((int)$enquiryItem['source_id']);
                if ($package === null) {
                    continue;
                }
                $services = $this->packageRepository->services((int)$package['id']);
                $totals = CalculationEngine::packageAmount($package, $services);
                $items[] = [
                    'source_type' => 'package',
                    'source_id' => (int)$package['id'],
                    'item_name' => $package['name'] . ' (Package)',
                    'quantity' => $quantity,
                    'unit_price' => $totals['total_amount'],
                    'amount' => Money::fromCents($totals['total_cents'] * $quantity),
                    'notes' => null,
                ];
            } else {
                $service = $this->serviceRepository->find((int)$enquiryItem['source_id']);
                if ($service === null) {
                    continue;
                }
                $items[] = [
                    'source_type' => 'service',
                    'source_id' => (int)$service['id'],
                    'item_name' => $service['name'],
                    'quantity' => $quantity,
                    'unit_price' => $service['starting_price'],
                    'amount' => Money::fromCents(CalculationEngine::serviceAmount($service, $quantity)),
                    'notes' => $enquiryItem['notes'] ?? null,
                ];
            }
        }
        $subtotalCents = CalculationEngine::itemsSubtotal($items);
        $totals = CalculationEngine::quotationTotals($subtotalCents, null, 0);
        $quotationId = Connection::transaction(function () use ($enquiry, $items, $totals, $userId): int {
            $quotationId = $this->quotationRepository->insert([
                'reference_no' => Reference::next('quotation'),
                'enquiry_id' => (int)$enquiry['id'],
                'customer_id' => (int)$enquiry['customer_id'],
                'subtotal' => $totals['subtotal'],
                'discount_type' => null,
                'discount_value' => 0,
                'discount_amount' => $totals['discount_amount'],
                'total_amount' => $totals['total_amount'],
                'valid_until' => date('Y-m-d', strtotime('+14 days')),
                'notes' => 'Prepared from enquiry ' . $enquiry['reference_no'],
                'status' => 'draft',
                'created_by' => $userId,
            ]);
            $this->quotationRepository->replaceItems($quotationId, $items);
            return $quotationId;
        });
        $this->activityService->log($userId, 'quotation.created', 'quotation', $quotationId, 'Created quotation from enquiry ' . $enquiry['reference_no']);
        return $this->find($quotationId);
    }

    public function update(int $id, array $data, array $items, ?int $userId): array
    {
        $quotation = $this->quotationRepository->findOrFail($id);
        if (in_array($quotation['status'], ['accepted', 'rejected', 'expired'], true)) {
            throw new BusinessException('Accepted, rejected or expired quotations cannot be edited.');
        }
        $normalized = [];
        foreach ($items as $item) {
            $quantity = max(1, (int)($item['quantity'] ?? 1));
            $unitPrice = Money::toCents($item['unit_price'] ?? 0);
            if ($unitPrice < 0) {
                throw new BusinessException('Unit price cannot be negative.');
            }
            $normalized[] = [
                'source_type' => ($item['source_type'] ?? 'service') === 'package' ? 'package' : 'service',
                'source_id' => isset($item['source_id']) && is_numeric($item['source_id']) ? (int)$item['source_id'] : null,
                'item_name' => trim((string)($item['item_name'] ?? 'Service')),
                'quantity' => $quantity,
                'unit_price' => Money::fromCents($unitPrice),
                'amount' => Money::fromCents($unitPrice * $quantity),
                'notes' => $item['notes'] ?? null,
            ];
        }
        if ($normalized === []) {
            throw new BusinessException('A quotation must contain at least one item.');
        }
        foreach ($normalized as $item) {
            if ($item['item_name'] === '') {
                throw new BusinessException('Each quotation item must have a name.');
            }
        }
        $subtotalCents = CalculationEngine::itemsSubtotal($normalized);
        $discountType = in_array($data['discount_type'] ?? null, ['percent', 'fixed'], true) ? $data['discount_type'] : null;
        $discountValue = $discountType !== null ? ($data['discount_value'] ?? 0) : 0;
        if ($discountType === 'percent' && (float)$discountValue > 100) {
            throw new BusinessException('Percentage discount cannot exceed 100%.');
        }
        $totals = CalculationEngine::quotationTotals($subtotalCents, $discountType, $discountValue);
        Connection::transaction(function () use ($id, $quotation, $data, $normalized, $totals, $discountType, $discountValue): void {
            $this->quotationRepository->update($id, [
                'subtotal' => $totals['subtotal'],
                'discount_type' => $discountType,
                'discount_value' => Money::fromCents(Money::toCents($discountValue)),
                'discount_amount' => $totals['discount_amount'],
                'total_amount' => $totals['total_amount'],
                'valid_until' => $data['valid_until'] ?? $quotation['valid_until'],
                'notes' => array_key_exists('notes', $data) ? $data['notes'] : $quotation['notes'],
            ]);
            $this->quotationRepository->replaceItems($id, $normalized);
        });
        $this->activityService->log($userId, 'quotation.updated', 'quotation', $id, 'Updated quotation ' . $quotation['reference_no']);
        return $this->find($id);
    }

    public function changeStatus(int $id, string $status, ?int $userId): array
    {
        if (!in_array($status, self::VALID_STATUSES, true)) {
            throw new BusinessException('Invalid quotation status.');
        }
        $quotation = $this->applyExpiry($this->quotationRepository->findOrFail($id));
        $current = $quotation['status'];
        if ($status !== $current && !in_array($status, self::TRANSITIONS[$current] ?? [], true)) {
            throw new BusinessException("A quotation cannot move from {$current} to {$status}.", 409);
        }
        $this->quotationRepository->update($id, ['status' => $status]);
        $this->syncEnquiryStatus($quotation, $status);
        $this->activityService->log($userId, 'quotation.status', 'quotation', $id, 'Set quotation status to ' . $status);
        return $this->find($id);
    }

    public function accept(int $id, ?int $userId, string $performedBy = 'admin'): array
    {
        $quotation = $this->quotationRepository->findDetailed($id);
        if ($quotation === null) {
            throw new BusinessException('Quotation not found.', 404);
        }
        $quotation = $this->applyExpiry($quotation);
        if ($quotation['status'] !== 'sent') {
            throw new BusinessException('Only sent, unexpired quotations can be accepted.', 409);
        }
        $this->quotationRepository->update($id, ['status' => 'accepted', 'accepted_at' => date('Y-m-d H:i:s')]);
        $this->syncEnquiryStatus($quotation, 'accepted');
        $this->notificationService->notifyAdmins(
            'Quotation accepted',
            $quotation['reference_no'] . ' has been accepted. You can create a booking now.',
            'success',
            \App\Helpers\FrontendRoutes::path('manage.quotation', ['id' => $id])
        );
        $this->activityService->log($userId, 'quotation.accepted', 'quotation', $id, ucfirst($performedBy) . ' accepted quotation ' . $quotation['reference_no']);
        return $this->find($id);
    }

    public function reject(int $id, ?int $userId, ?string $reason = null): array
    {
        $quotation = $this->quotationRepository->findDetailed($id);
        if ($quotation === null) {
            throw new BusinessException('Quotation not found.', 404);
        }
        $quotation = $this->applyExpiry($quotation);
        if ($quotation['status'] !== 'sent') {
            throw new BusinessException('Only sent, unexpired quotations can be rejected.', 409);
        }
        $this->quotationRepository->update($id, ['status' => 'rejected', 'rejected_at' => date('Y-m-d H:i:s')]);
        $this->syncEnquiryStatus($quotation, 'rejected');
        $this->notificationService->notifyAdmins(
            'Quotation rejected',
            $quotation['reference_no'] . ' was rejected.' . ($reason ? ' Reason: ' . $reason : ''),
            'warning',
            \App\Helpers\FrontendRoutes::path('manage.quotation', ['id' => $id])
        );
        $this->activityService->log($userId, 'quotation.rejected', 'quotation', $id, 'Rejected quotation ' . $quotation['reference_no']);
        return $this->find($id);
    }

    private function syncEnquiryStatus(array $quotation, string $quotationStatus): void
    {
        $enquiryId = $quotation['enquiry_id'] ?? null;
        if ($enquiryId === null) {
            return;
        }
        $map = [
            'sent' => 'quotation_sent',
            'accepted' => 'converted',
            'rejected' => 'closed',
            'expired' => 'closed',
        ];
        if (isset($map[$quotationStatus])) {
            $this->enquiryRepository->update((int)$enquiryId, ['status' => $map[$quotationStatus]]);
        }
    }

    public function forCustomer(int $customerId): array
    {
        $rows = $this->quotationRepository->forCustomer($customerId);
        foreach ($rows as &$row) {
            $row = $this->decorate($row);
        }
        unset($row);
        return $rows;
    }

    public function findForCustomer(int $id, int $customerId): array
    {
        $quotation = $this->find($id);
        if ((int)$quotation['customer_id'] !== $customerId) {
            throw new BusinessException('Quotation not found.', 404);
        }
        return $quotation;
    }

    public function toBookingSource(int $quotationId): array
    {
        $quotation = $this->find($quotationId);
        if ($quotation['status'] !== 'accepted') {
            throw new BusinessException('Only accepted quotations can be converted to a booking.');
        }
        return $quotation;
    }
}
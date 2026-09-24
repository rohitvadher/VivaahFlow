<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\BusinessException;
use App\Helpers\Pagination;
use App\Helpers\Str;
use App\Repositories\OfferRepository;

class OfferService
{
    public function __construct(
        private OfferRepository $offerRepository,
        private ActivityService $activityService
    ) {
    }

    public function list(int $page, int $perPage, string $search, string $status): array
    {
        [$rows, $total] = $this->offerRepository->listPaginated($page, $perPage, $search, $status);
        foreach ($rows as &$row) {
            $row = $this->decorate($row);
        }
        unset($row);
        return Pagination::build($rows, $total, $page, $perPage);
    }

    public function active(): array
    {
        $rows = $this->offerRepository->activeOffers();
        foreach ($rows as &$row) {
            $row = $this->decorate($row);
        }
        unset($row);
        return $rows;
    }

    public function find(int $id): array
    {
        $offer = $this->offerRepository->find($id);
        if ($offer === null) {
            throw new BusinessException('Offer not found.', 404);
        }
        $offer = $this->decorate($offer);
        $offer['service_ids'] = $this->offerRepository->serviceIds($id);
        $offer['package_ids'] = $this->offerRepository->packageIds($id);
        return $offer;
    }

    private function decorate(array $offer): array
    {
        $today = date('Y-m-d');
        $offer['is_live'] = $offer['status'] === 'active'
            && $offer['start_date'] <= $today
            && $offer['end_date'] >= $today;
        $offer['discount_label'] = (float)$offer['discount_value'] > 0
            ? ($offer['discount_type'] === 'percent'
                ? rtrim(rtrim((string)$offer['discount_value'], '0'), '.') . '% off'
                : \App\Helpers\Money::format(\App\Helpers\Money::toCents($offer['discount_value'])) . ' off')
            : null;
        return $offer;
    }

    public function create(array $data, ?int $userId): array
    {
        $this->assertDates($data);
        $slug = $this->uniqueSlug($data['name'], null);
        $offerId = $this->offerRepository->insert([
            'name' => trim((string)$data['name']),
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'discount_type' => $data['discount_type'] ?? 'percent',
            'discount_value' => $data['discount_value'],
            'applicable_to' => $data['applicable_to'] ?? 'all',
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'status' => $data['status'] ?? 'active',
        ]);
        $this->syncLinks($offerId, $data);
        $this->activityService->log($userId, 'offer.created', 'offer', $offerId, 'Created offer ' . $data['name']);
        return $this->find($offerId);
    }

    public function update(int $id, array $data, ?int $userId): array
    {
        $offer = $this->offerRepository->findOrFail($id);
        $merged = array_merge($offer, $data);
        $this->assertDates($merged);
        $name = $data['name'] ?? $offer['name'];
        $slug = $name !== $offer['name'] ? $this->uniqueSlug($name, $id) : $offer['slug'];
        $this->offerRepository->update($id, [
            'name' => $name,
            'slug' => $slug,
            'description' => array_key_exists('description', $data) ? $data['description'] : $offer['description'],
            'discount_type' => $data['discount_type'] ?? $offer['discount_type'],
            'discount_value' => $data['discount_value'] ?? $offer['discount_value'],
            'applicable_to' => $data['applicable_to'] ?? $offer['applicable_to'],
            'start_date' => $merged['start_date'],
            'end_date' => $merged['end_date'],
            'status' => $data['status'] ?? $offer['status'],
        ]);
        if (array_key_exists('applicable_to', $data) || array_key_exists('service_ids', $data) || array_key_exists('package_ids', $data)) {
            $this->syncLinks($id, array_merge($offer, $data));
        }
        $this->activityService->log($userId, 'offer.updated', 'offer', $id, 'Updated offer ' . $name);
        return $this->find($id);
    }

    public function changeStatus(int $id, string $status, ?int $userId): array
    {
        if (!in_array($status, ['active', 'inactive'], true)) {
            throw new BusinessException('Invalid offer status.');
        }
        $offer = $this->offerRepository->findOrFail($id);
        $this->offerRepository->update($id, ['status' => $status]);
        $this->activityService->log($userId, 'offer.status', 'offer', $id, 'Set offer status to ' . $status);
        return $this->find($id);
    }

    public function delete(int $id, ?int $userId): void
    {
        $offer = $this->offerRepository->findOrFail($id);
        $this->offerRepository->delete($id);
        $this->activityService->log($userId, 'offer.deleted', 'offer', $id, 'Deleted offer ' . $offer['name']);
    }

    private function syncLinks(int $offerId, array $data): void
    {
        $applicable = $data['applicable_to'] ?? 'all';
        $serviceIds = $applicable === 'services' ? array_map('intval', (array)($data['service_ids'] ?? [])) : [];
        $packageIds = $applicable === 'packages' ? array_map('intval', (array)($data['package_ids'] ?? [])) : [];
        if ($applicable === 'services' && $serviceIds === []) {
            throw new BusinessException('Select at least one service for this offer.');
        }
        if ($applicable === 'packages' && $packageIds === []) {
            throw new BusinessException('Select at least one package for this offer.');
        }
        $this->offerRepository->syncLinks($offerId, $applicable, $serviceIds, $packageIds);
    }

    private function assertDates(array $data): void
    {
        if (empty($data['start_date']) || empty($data['end_date'])) {
            throw new BusinessException('Offer start and end dates are required.');
        }
        if ($data['end_date'] < $data['start_date']) {
            throw new BusinessException('The offer end date must be on or after the start date.');
        }
        $type = $data['discount_type'] ?? 'percent';
        $value = (float)($data['discount_value'] ?? 0);
        if ($value <= 0) {
            throw new BusinessException('The discount value must be greater than zero.');
        }
        if ($type === 'percent' && $value > 100) {
            throw new BusinessException('A percentage discount cannot exceed 100.');
        }
    }

    private function uniqueSlug(string $name, ?int $ignoreId): string
    {
        $base = Str::slugify($name);
        $slug = $base;
        $suffix = 1;
        while (true) {
            $existing = $this->offerRepository->findWhere(['slug' => $slug]);
            if ($existing === null || ($ignoreId !== null && (int)$existing['id'] === $ignoreId)) {
                return $slug;
            }
            $suffix++;
            $slug = $base . '-' . $suffix;
        }
    }
}
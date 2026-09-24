<?php

declare(strict_types=1);

namespace App\Services;

use App\Calculations\CalculationEngine;
use App\Core\BusinessException;
use App\Database\Connection;
use App\Helpers\ImageEngine;
use App\Helpers\Money;
use App\Helpers\Pagination;
use App\Helpers\Str;
use App\Repositories\PackageRepository;

class PackageService
{
    public function __construct(
        private PackageRepository $packageRepository,
        private ActivityService $activityService
    ) {
    }

    public function list(int $page, int $perPage, string $search, string $status): array
    {
        [$rows, $total] = $this->packageRepository->listPaginated($page, $perPage, $search, $status);
        foreach ($rows as &$row) {
            $row = $this->hydrate($row, false);
        }
        unset($row);
        return Pagination::build($rows, $total, $page, $perPage);
    }

    public function find(int $id): array
    {
        $package = $this->packageRepository->find($id);
        if ($package === null) {
            throw new BusinessException('Package not found.', 404);
        }
        return $this->hydrate($package, true);
    }

    public function hydrate(array $package, bool $withServices): array
    {
        $services = $this->packageRepository->services((int)$package['id']);
        $totals = CalculationEngine::packageAmount($package, $services);
        $package['services'] = $withServices ? $services : null;
        $package['cover_image_url'] = $package['cover_image'] ? upload_url($package['cover_image']) : '';
        $package['service_names'] = array_map(fn(array $item): string => $item['name'], $services);
        $package['base_amount'] = $totals['base_amount'];
        $package['discount_amount'] = $totals['discount_amount'];
        if ($totals['discount_cents'] <= 0) {
            $package['discount_type'] = null;
            $package['discount_value'] = '0.00';
            $package['discount_label'] = null;
        } else {
            $package['discount_label'] = $package['discount_type'] === 'percent'
                ? rtrim(rtrim((string)$package['discount_value'], '0'), '.') . '% off'
                : Money::format($totals['discount_cents']) . ' off';
        }
        $package['total_amount'] = $totals['total_amount'];
        $package['total_cents'] = $totals['total_cents'];
        $package['service_count'] = count($services);
        return $package;
    }

    public function create(array $data, array $serviceIds, ?array $file, ?int $userId): array
    {
        $slug = Str::slugify($data['slug'] ?? $data['name']);
        if ($this->packageRepository->findWhere(['slug' => $slug]) !== null) {
            throw new BusinessException('A package with this name already exists.', 409);
        }
        $image = null;
        if ($file !== null) {
            $stored = ImageEngine::store($file, 'packages', $data['name']);
            if ($stored['error'] !== null) {
                throw new BusinessException($stored['error']);
            }
            $image = $stored['path'];
        }
        $id = $this->packageRepository->insert([
            'name' => trim($data['name']),
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'cover_image' => $image,
            'discount_type' => $data['discount_type'] ?? null,
            'discount_value' => $data['discount_value'] ?? 0,
            'is_featured' => !empty($data['is_featured']) ? 1 : 0,
            'status' => $data['status'] ?? 'active',
            'display_order' => (int)($data['display_order'] ?? 0),
        ]);
        $this->packageRepository->syncServices($id, $serviceIds);
        $this->activityService->log($userId, 'package.created', 'package', $id, 'Created package ' . $data['name']);
        return $this->find($id);
    }

    public function update(int $id, array $data, array $serviceIds, ?array $file, ?int $userId): array
    {
        $package = $this->packageRepository->findOrFail($id);
        $slug = Str::slugify($data['slug'] ?? $data['name'] ?? $package['slug']);
        $conflict = $this->packageRepository->findWhere(['slug' => $slug]);
        if ($conflict !== null && (int)$conflict['id'] !== $id) {
            throw new BusinessException('A package with this name already exists.', 409);
        }
        $image = $package['cover_image'];
        if ($file !== null) {
            $stored = ImageEngine::replace($file, 'packages', $data['name'] ?? $package['name'], $image);
            if ($stored['error'] !== null) {
                throw new BusinessException($stored['error']);
            }
            $image = $stored['path'];
        }
        $this->packageRepository->update($id, [
            'name' => $data['name'] ?? $package['name'],
            'slug' => $slug,
            'description' => array_key_exists('description', $data) ? $data['description'] : $package['description'],
            'cover_image' => $image,
            'discount_type' => $data['discount_type'] ?? $package['discount_type'],
            'discount_value' => $data['discount_value'] ?? $package['discount_value'],
            'is_featured' => array_key_exists('is_featured', $data) ? (!empty($data['is_featured']) ? 1 : 0) : $package['is_featured'],
            'status' => $data['status'] ?? $package['status'],
            'display_order' => (int)($data['display_order'] ?? $package['display_order']),
        ]);
        $this->packageRepository->syncServices($id, $serviceIds);
        $this->activityService->log($userId, 'package.updated', 'package', $id, 'Updated package ' . $package['name']);
        return $this->find($id);
    }

    public function changeStatus(int $id, string $status, ?int $userId): array
    {
        if (!in_array($status, ['active', 'inactive'], true)) {
            throw new BusinessException('Invalid package status.');
        }
        $this->packageRepository->findOrFail($id);
        $this->packageRepository->update($id, ['status' => $status]);
        $this->activityService->log($userId, 'package.status', 'package', $id, 'Set package status to ' . $status);
        return $this->find($id);
    }

    public function delete(int $id, ?int $userId): void
    {
        $package = $this->packageRepository->findOrFail($id);
        $usage = (int)Connection::fetchColumn(
            'SELECT
                (SELECT COUNT(*) FROM enquiry_services WHERE source_type = "package" AND source_id = ?) +
                (SELECT COUNT(*) FROM quotation_items WHERE source_type = "package" AND source_id = ?) +
                (SELECT COUNT(*) FROM booking_services WHERE source_type = "package" AND source_id = ?)',
            [$id, $id, $id]
        );
        if ($usage > 0) {
            throw new BusinessException('This package is referenced by enquiries, quotations or bookings and cannot be deleted. Deactivate it instead.', 409);
        }
        $image = $package['cover_image'];
        $this->packageRepository->delete($id);
        if ($image !== null) {
            ImageEngine::deleteIfUnused($image);
        }
        $this->activityService->log($userId, 'package.deleted', 'package', $id, 'Deleted package ' . $package['name']);
    }

    public function allActive(): array
    {
        $packages = $this->packageRepository->activePackages();
        foreach ($packages as &$package) {
            $package = $this->hydrate($package, false);
        }
        unset($package);
        return $packages;
    }
}
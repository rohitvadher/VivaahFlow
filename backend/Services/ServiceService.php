<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\BusinessException;
use App\Database\Connection;
use App\Helpers\ImageEngine;
use App\Helpers\Pagination;
use App\Helpers\Str;
use App\Repositories\CategoryRepository;
use App\Repositories\ServiceImageRepository;
use App\Repositories\ServiceRepository;

class ServiceService
{
    public function __construct(
        private ServiceRepository $serviceRepository,
        private CategoryRepository $categoryRepository,
        private ServiceImageRepository $imageRepository,
        private ActivityService $activityService
    ) {
    }

    public function categories(bool $withCounts = false): array
    {
        if ($withCounts) {
            return $this->categoryRepository->serviceCountByCategory();
        }
        return $this->categoryRepository->activeCategories();
    }

    public function list(int $page, int $perPage, string $search, ?int $categoryId, string $status): array
    {
        [$rows, $total] = $this->serviceRepository->listPaginated($page, $perPage, $search, $categoryId, $status);
        return Pagination::build($rows, $total, $page, $perPage);
    }

    public function find(int $id): array
    {
        $service = $this->serviceRepository->findWithCategory($id);
        if ($service === null) {
            throw new BusinessException('Service not found.', 404);
        }
        $service['gallery'] = $this->imageRepository->forService($id);
        return $service;
    }

    public function create(array $data, ?array $file, ?int $userId): array
    {
        $slug = Str::slugify($data['slug'] ?? $data['name']);
        if ($this->serviceRepository->findWhere(['slug' => $slug]) !== null) {
            throw new BusinessException('A service with this name already exists.', 409);
        }
        $image = null;
        if ($file !== null) {
            $stored = ImageEngine::store($file, 'services', $data['name']);
            if ($stored['error'] !== null) {
                throw new BusinessException($stored['error']);
            }
            $image = $stored['path'];
        }
        $id = $this->serviceRepository->insert([
            'category_id' => (int)$data['category_id'],
            'name' => trim($data['name']),
            'slug' => $slug,
            'short_description' => $data['short_description'] ?? null,
            'description' => $data['description'] ?? null,
            'duration_minutes' => $data['duration_minutes'] !== '' ? (int)$data['duration_minutes'] : null,
            'starting_price' => $data['starting_price'],
            'image' => $image,
            'is_featured' => !empty($data['is_featured']) ? 1 : 0,
            'status' => $data['status'] ?? 'active',
            'display_order' => (int)($data['display_order'] ?? 0),
        ]);
        $this->activityService->log($userId, 'service.created', 'service', $id, 'Created service ' . $data['name']);
        return $this->serviceRepository->findWithCategory($id);
    }

    public function update(int $id, array $data, ?array $file, ?int $userId): array
    {
        $service = $this->serviceRepository->findOrFail($id);
        $slug = Str::slugify($data['slug'] ?? $data['name'] ?? $service['slug']);
        $conflict = $this->serviceRepository->findWhere(['slug' => $slug]);
        if ($conflict !== null && (int)$conflict['id'] !== $id) {
            throw new BusinessException('A service with this name already exists.', 409);
        }
        $image = $service['image'];
        if ($file !== null) {
            $stored = ImageEngine::replace($file, 'services', $data['name'] ?? $service['name'], $image);
            if ($stored['error'] !== null) {
                throw new BusinessException($stored['error']);
            }
            $image = $stored['path'];
        }
        $this->serviceRepository->update($id, [
            'category_id' => (int)($data['category_id'] ?? $service['category_id']),
            'name' => $data['name'] ?? $service['name'],
            'slug' => $slug,
            'short_description' => array_key_exists('short_description', $data) ? $data['short_description'] : $service['short_description'],
            'description' => array_key_exists('description', $data) ? $data['description'] : $service['description'],
            'duration_minutes' => $data['duration_minutes'] !== '' ? (int)$data['duration_minutes'] : null,
            'starting_price' => $data['starting_price'] ?? $service['starting_price'],
            'image' => $image,
            'is_featured' => array_key_exists('is_featured', $data) ? (!empty($data['is_featured']) ? 1 : 0) : $service['is_featured'],
            'status' => $data['status'] ?? $service['status'],
            'display_order' => (int)($data['display_order'] ?? $service['display_order']),
        ]);
        $this->activityService->log($userId, 'service.updated', 'service', $id, 'Updated service ' . $service['name']);
        return $this->serviceRepository->findWithCategory($id);
    }

    public function changeStatus(int $id, string $status, ?int $userId): array
    {
        if (!in_array($status, ['active', 'inactive'], true)) {
            throw new BusinessException('Invalid service status.');
        }
        $service = $this->serviceRepository->findOrFail($id);
        $this->serviceRepository->update($id, ['status' => $status]);
        $this->activityService->log($userId, 'service.status', 'service', $id, 'Set service status to ' . $status);
        return $this->serviceRepository->findWithCategory($id);
    }

    public function delete(int $id, ?int $userId): void
    {
        $service = $this->serviceRepository->findOrFail($id);
        $usage = (int)Connection::fetchColumn(
            'SELECT
                (SELECT COUNT(*) FROM package_services WHERE service_id = ?) +
                (SELECT COUNT(*) FROM enquiry_services WHERE source_type = "service" AND source_id = ?) +
                (SELECT COUNT(*) FROM booking_services WHERE source_type = "service" AND source_id = ?) +
                (SELECT COUNT(*) FROM quotation_items WHERE source_type = "service" AND source_id = ?)',
            [$id, $id, $id, $id]
        );
        if ($usage > 0) {
            throw new BusinessException('This service is referenced by packages, enquiries or bookings and cannot be deleted. Deactivate it instead.', 409);
        }
        $image = $service['image'];
        $deleted = $this->serviceRepository->delete($id);
        if ($deleted && $image !== null) {
            ImageEngine::deleteIfUnused($image);
        }
        $this->activityService->log($userId, 'service.deleted', 'service', $id, 'Deleted service ' . $service['name']);
    }

    public function createCategory(array $data, ?int $userId): array
    {
        $slug = Str::slugify($data['slug'] ?? $data['name']);
        if ($this->categoryRepository->findWhere(['slug' => $slug]) !== null) {
            throw new BusinessException('A category with this name already exists.', 409);
        }
        $id = $this->categoryRepository->insert([
            'name' => trim($data['name']),
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'display_order' => (int)($data['display_order'] ?? 0),
            'status' => $data['status'] ?? 'active',
        ]);
        $this->activityService->log($userId, 'category.created', 'category', $id, 'Created category ' . $data['name']);
        return $this->categoryRepository->find($id);
    }

    public function updateCategory(int $id, array $data, ?int $userId): array
    {
        $category = $this->categoryRepository->findOrFail($id);
        $slug = Str::slugify($data['slug'] ?? $data['name'] ?? $category['slug']);
        $conflict = $this->categoryRepository->findWhere(['slug' => $slug]);
        if ($conflict !== null && (int)$conflict['id'] !== $id) {
            throw new BusinessException('A category with this name already exists.', 409);
        }
        $this->categoryRepository->update($id, [
            'name' => $data['name'] ?? $category['name'],
            'slug' => $slug,
            'description' => array_key_exists('description', $data) ? $data['description'] : $category['description'],
            'display_order' => (int)($data['display_order'] ?? $category['display_order']),
            'status' => $data['status'] ?? $category['status'],
        ]);
        $this->activityService->log($userId, 'category.updated', 'category', $id, 'Updated category ' . $category['name']);
        return $this->categoryRepository->find($id);
    }

    public function deleteCategory(int $id, ?int $userId): void
    {
        $category = $this->categoryRepository->findOrFail($id);
        $services = (int)Connection::fetchColumn(
            'SELECT COUNT(*) FROM services WHERE category_id = ?',
            [$id]
        );
        if ($services > 0) {
            throw new BusinessException('This category has services assigned and cannot be deleted.', 409);
        }
        $this->categoryRepository->delete($id);
        $this->activityService->log($userId, 'category.deleted', 'category', $id, 'Deleted category ' . $category['name']);
    }

    public function galleryFor(int $serviceId): array
    {
        $this->serviceRepository->findOrFail($serviceId);
        return $this->imageRepository->forService($serviceId);
    }

    public function addImage(int $serviceId, ?array $file, ?string $caption, ?int $userId): array
    {
        $this->serviceRepository->findOrFail($serviceId);
        if ($file === null) {
            throw new BusinessException('An image file is required.');
        }
        $stored = ImageEngine::store($file, 'services', 'gallery');
        if ($stored['error'] !== null) {
            throw new BusinessException($stored['error']);
        }
        $existing = $this->imageRepository->forService($serviceId);
        $isPrimary = $existing === [] ? 1 : 0;
        $id = $this->imageRepository->insert([
            'service_id' => $serviceId,
            'image_path' => $stored['path'],
            'caption' => $caption,
            'is_primary' => $isPrimary,
            'display_order' => count($existing),
            'status' => 'active',
        ]);
        if ($isPrimary) {
            $this->serviceRepository->update($serviceId, ['image' => $stored['path']]);
        }
        $this->activityService->log($userId, 'gallery.created', 'service', $serviceId, 'Added gallery image to service');
        return $this->imageRepository->find($id);
    }

    public function updateImage(int $serviceId, int $imageId, ?array $file, array $data, ?int $userId): array
    {
        $image = $this->imageRepository->findForService($serviceId, $imageId);
        if ($image === null) {
            throw new BusinessException('Gallery image not found.', 404);
        }
        $path = $image['image_path'];
        if ($file !== null) {
            $stored = ImageEngine::replace($file, 'services', 'gallery', $path);
            if ($stored['error'] !== null) {
                throw new BusinessException($stored['error']);
            }
            $path = $stored['path'];
        }
        $wantsPrimary = array_key_exists('is_primary', $data) ? !empty($data['is_primary']) : (bool)$image['is_primary'];
        $this->imageRepository->update($imageId, [
            'image_path' => $path,
            'caption' => $data['caption'] ?? $image['caption'],
            'display_order' => (int)($data['display_order'] ?? $image['display_order']),
            'status' => $data['status'] ?? $image['status'],
        ]);
        if ($wantsPrimary) {
            $this->setPrimary($serviceId, $imageId, $userId);
        }
        $this->activityService->log($userId, 'gallery.updated', 'service', $serviceId, 'Updated gallery image');
        return $this->imageRepository->find($imageId);
    }

    public function setPrimary(int $serviceId, int $imageId, ?int $userId): array
    {
        $image = $this->imageRepository->findForService($serviceId, $imageId);
        if ($image === null) {
            throw new BusinessException('Gallery image not found.', 404);
        }
        $this->imageRepository->clearPrimary($serviceId);
        $this->imageRepository->update($imageId, ['is_primary' => 1]);
        $this->serviceRepository->update($serviceId, ['image' => $image['image_path']]);
        $this->activityService->log($userId, 'gallery.primary', 'service', $serviceId, 'Set gallery primary image');
        return $this->imageRepository->forService($serviceId);
    }

    public function deleteImage(int $serviceId, int $imageId, ?int $userId): void
    {
        $image = $this->imageRepository->findForService($serviceId, $imageId);
        if ($image === null) {
            throw new BusinessException('Gallery image not found.', 404);
        }
        $wasPrimary = (int)$image['is_primary'] === 1;
        if ($wasPrimary) {
            $remaining = array_values(array_filter(
                $this->imageRepository->forService($serviceId),
                fn(array $item): bool => (int)$item['id'] !== $imageId
            ));
            if ($remaining !== []) {
                $this->imageRepository->clearPrimary($serviceId);
                $this->imageRepository->update((int)$remaining[0]['id'], ['is_primary' => 1]);
                $this->serviceRepository->update($serviceId, ['image' => $remaining[0]['image_path']]);
            } else {
                $this->serviceRepository->update($serviceId, ['image' => null]);
            }
        }
        $this->imageRepository->delete($imageId);
        ImageEngine::deleteIfUnused($image['image_path']);
        $this->activityService->log($userId, 'gallery.deleted', 'service', $serviceId, 'Deleted gallery image');
    }
}
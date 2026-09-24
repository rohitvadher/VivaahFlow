<?php

declare(strict_types=1);

namespace App\Services;

use App\Calculations\CalculationEngine;
use App\Core\BusinessException;
use App\Database\Connection;
use App\Repositories\OfferRepository;
use App\Repositories\PackageRepository;
use App\Repositories\ReviewRepository;
use App\Repositories\ServiceImageRepository;
use App\Repositories\ServiceRepository;

class PublicCatalogService
{
    public function __construct(
        private ServiceRepository $serviceRepository,
        private PackageRepository $packageRepository,
        private ServiceImageRepository $imageRepository,
        private ReviewRepository $reviewRepository,
        private OfferRepository $offerRepository,
        private SettingsService $settingsService,
        private PackageService $packageService
    ) {
    }

    public function home(): array
    {
        $categories = Connection::fetchAll(
            'SELECT id, name, slug, description FROM service_categories
             WHERE status = "active" ORDER BY display_order ASC, name ASC'
        );
        $services = array_map([$this, 'decorateServiceUrls'], $this->serviceRepository->featuredActive(6));
        $packages = $this->packageRepository->activePackages();
        $packageList = [];
        foreach ($packages as $package) {
            $packageList[] = $this->packageService->hydrate($package, false);
        }
        $stats = [
            'services' => (int)Connection::fetchColumn('SELECT COUNT(*) FROM services WHERE status = "active"'),
            'packages' => (int)Connection::fetchColumn('SELECT COUNT(*) FROM packages WHERE status = "active"'),
            'happy_customers' => (int)Connection::fetchColumn(
                'SELECT COUNT(*) FROM bookings WHERE status IN ("completed", "in_progress")'
            ),
            'avg_rating' => $this->reviewRepository->averageGlobal(),
        ];
        return [
            'settings' => $this->settingsService->publicBranding(),
            'categories' => $categories,
            'services' => $services,
            'packages' => $packageList,
            'reviews' => $this->reviewRepository->approvedVisible(6),
            'offers' => $this->offerRepository->activeOffers(),
            'gallery' => array_map([$this, 'decorateGalleryUrls'], $this->imageRepository->activeGallery(12)),
            'stats' => $stats,
        ];
    }

    private function decorateServiceUrls(array $service): array
    {
        $service['image_url'] = $service['image'] ? upload_url($service['image']) : '';
        return $service;
    }

    private function decorateGalleryUrls(array $item): array
    {
        $item['image'] = isset($item['image_path']) && $item['image_path'] !== '' ? upload_url($item['image_path']) : '';
        return $item;
    }

    public function services(string $search, ?int $categoryId): array
    {
        $rows = array_map([$this, 'decorateServiceUrls'], $this->serviceRepository->byCategoryForPublic());
        $matched = [];
        foreach ($rows as $row) {
            if ($categoryId !== null && $categoryId > 0 && (int)$row['category_id'] !== $categoryId) {
                continue;
            }
            if ($search !== '' && stripos($row['name'], $search) === false && stripos($row['category_name'], $search) === false) {
                continue;
            }
            $matched[] = $row;
        }
        return $matched;
    }

    public function serviceDetail(string $slug): array
    {
        $service = $this->serviceRepository->findBySlug($slug);
        if ($service === null || $service['status'] !== 'active') {
            throw new BusinessException('Service not found.', 404);
        }
        $service = $this->decorateServiceUrls($service);
        $service['gallery'] = array_map([$this, 'decorateGalleryUrls'], $this->imageRepository->forService((int)$service['id']));
        $service['published_reviews'] = $this->reviewRepository->forService((int)$service['id'], 10);
        $service['similar'] = array_values(array_filter(
            $this->serviceRepository->listPaginated(1, 4, '', (int)$service['category_id'], 'all', true)[0],
            fn(array $item): bool => (int)$item['id'] !== (int)$service['id']
        ));
        return $service;
    }

    public function packages(): array
    {
        $packages = $this->packageRepository->activePackages();
        $result = [];
        foreach ($packages as $package) {
            $result[] = $this->packageService->hydrate($package, false);
        }
        return $result;
    }

    public function packageDetail(string $slug): array
    {
        $package = $this->packageRepository->findBySlug($slug);
        if ($package === null || $package['status'] !== 'active') {
            throw new BusinessException('Package not found.', 404);
        }
        return $this->packageService->hydrate($package, true);
    }

    public function gallery(): array
    {
        return array_map([$this, 'decorateGalleryUrls'], $this->imageRepository->activeGallery(80));
    }

    public function reviews(): array
    {
        $reviews = $this->reviewRepository->approvedVisible(30);
        $summary = $this->reviewRepository->ratingSummary();
        return [
            'reviews' => $reviews,
            'average' => $this->reviewRepository->averageGlobal(),
            'distribution' => $summary,
            'total' => count($reviews),
        ];
    }

    public function offers(): array
    {
        $offers = $this->offerRepository->activeOffers();
        foreach ($offers as &$offer) {
            $offer['discount_label'] = $offer['discount_type'] === 'percent'
                ? $offer['discount_value'] . '% off'
                : \App\Helpers\Money::format(\App\Helpers\Money::toCents($offer['discount_value']));
        }
        unset($offer);
        return $offers;
    }
}
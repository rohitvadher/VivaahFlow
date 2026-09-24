<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Database\Connection;
use App\Services\AuthService;
use App\Services\EnquiryService;
use App\Services\PublicCatalogService;
use App\Services\ServiceService;
use App\Services\SettingsService;
use App\Validators\Validator;

class PublicController extends BaseController
{
    public function __construct(
        private PublicCatalogService $catalogService,
        private ServiceService $serviceService,
        private SettingsService $settingsService,
        private EnquiryService $enquiryService,
        private AuthService $authService
    ) {
    }

    private function setupRequired(): bool
    {
        try {
            return Connection::getStatus() !== 'installed';
        } catch (\Throwable $e) {
            return true;
        }
    }

    private function brandingFallback(): array
    {
        try {
            return $this->settingsService->publicBranding();
        } catch (\Throwable $e) {
            return [
                'company_name' => (string)app_config('app_name', 'VivaahFlow'),
                'tagline' => 'Wedding Services Management System',
                'company_email' => '',
                'company_phone' => '',
                'company_address' => '',
                'company_city' => '',
                'currency' => 'INR',
                'logo_path' => '',
                'footer_text' => 'Crafting beautiful wedding experiences.',
                'currency_symbol' => '₹',
                'registration_open' => true,
            ];
        }
    }

    public function bootstrap(Request $request): void
    {
        if ($this->setupRequired()) {
            $this->success([
                'settings' => $this->brandingFallback(),
                'categories' => [],
                'setup_required' => true,
            ]);
        }
        try {
            $this->success([
                'settings' => $this->settingsService->publicBranding(),
                'categories' => $this->serviceService->categories(),
                'setup_required' => false,
            ]);
        } catch (\Throwable $e) {
            error_log('[PUBLIC] bootstrap failed: ' . $e->getMessage());
            $this->success([
                'settings' => $this->brandingFallback(),
                'categories' => [],
                'setup_required' => true,
            ]);
        }
    }

    public function home(Request $request): void
    {
        if ($this->setupRequired()) {
            $this->success([
                'settings' => $this->brandingFallback(),
                'categories' => [],
                'services' => [],
                'packages' => [],
                'reviews' => [],
                'offers' => [],
                'gallery' => [],
                'stats' => ['services' => 0, 'packages' => 0, 'happy_customers' => 0, 'avg_rating' => 0],
                'setup_required' => true,
            ]);
        }
        try {
            $data = $this->catalogService->home();
            $data['setup_required'] = false;
            $this->success($data);
        } catch (\Throwable $e) {
            error_log('[PUBLIC] home failed: ' . $e->getMessage());
            Response::error('The website content is temporarily unavailable.', 503);
        }
    }

    public function services(Request $request): void
    {
        $search = trim((string)$request->input('search', ''));
        $categoryId = $request->input('category_id');
        if ($this->setupRequired()) {
            $this->success(['services' => [], 'categories' => [], 'setup_required' => true]);
        }
        try {
            $this->success([
                'services' => $this->catalogService->services($search, $categoryId !== null ? (int)$categoryId : null),
                'categories' => $this->serviceService->categories(),
                'setup_required' => false,
            ]);
        } catch (\Throwable $e) {
            error_log('[PUBLIC] services failed: ' . $e->getMessage());
            Response::error('The service catalogue is temporarily unavailable.', 503);
        }
    }

    public function service(Request $request): void
    {
        try {
            $this->success($this->catalogService->serviceDetail((string)$request->attribute('slug')));
        } catch (\App\Core\BusinessException $e) {
            throw $e;
        } catch (\Throwable $e) {
            error_log('[PUBLIC] service failed: ' . $e->getMessage());
            Response::error('The service is temporarily unavailable.', 503);
        }
    }

    public function packages(Request $request): void
    {
        if ($this->setupRequired()) {
            $this->success([]);
        }
        try {
            $this->success($this->catalogService->packages());
        } catch (\Throwable $e) {
            error_log('[PUBLIC] packages failed: ' . $e->getMessage());
            Response::error('Packages are temporarily unavailable.', 503);
        }
    }

    public function package(Request $request): void
    {
        try {
            $this->success($this->catalogService->packageDetail((string)$request->attribute('slug')));
        } catch (\App\Core\BusinessException $e) {
            throw $e;
        } catch (\Throwable $e) {
            error_log('[PUBLIC] package failed: ' . $e->getMessage());
            Response::error('The package is temporarily unavailable.', 503);
        }
    }

    public function gallery(Request $request): void
    {
        try {
            $this->success($this->catalogService->gallery());
        } catch (\Throwable $e) {
            $this->success([]);
        }
    }

    public function reviews(Request $request): void
    {
        try {
            $this->success($this->catalogService->reviews());
        } catch (\Throwable $e) {
            $this->success(['reviews' => [], 'average' => 0, 'distribution' => [], 'total' => 0]);
        }
    }

    public function offers(Request $request): void
    {
        try {
            $this->success($this->catalogService->offers());
        } catch (\Throwable $e) {
            $this->success([]);
        }
    }

    public function submitEnquiry(Request $request): void
    {
        if ($this->setupRequired()) {
            Response::error('Setup is not complete yet. Please complete the installation first.', 503);
        }
        $result = Validator::validate($request->all(), [
            'name' => 'required|string|min:3|max:120',
            'email' => 'required|email',
            'phone' => 'required|phone',
            'event_date' => 'nullable|date',
            'event_type' => 'nullable|string|max:80',
            'venue_address' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
        ]);
        if (!$result['valid']) {
            Response::validation($result['errors']);
        }
        $user = null;
        try {
            $user = $this->authService->user();
        } catch (\Throwable $e) {
            $user = null;
        }
        $context = ['customer_id' => $user['customer']['id'] ?? null, 'user_id' => $user['id'] ?? null];
        $enquiry = $this->enquiryService->submitWebsite($result['data'], $context);
        Response::created([
            'reference_no' => $enquiry['reference_no'],
        ], 'Thank you. Your enquiry has been received.');
    }
}

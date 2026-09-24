<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\ImageEngine;
use App\Repositories\SettingsRepository;

class SettingsService
{
    public function __construct(private SettingsRepository $settingsRepository)
    {
    }

    public function all(): array
    {
        $settings = $this->settingsRepository->allGrouped();
        // Reuse the already-fetched row instead of a second query.
        $settings['registration_open'] = $this->settingsRepository->isRegistrationOpen($settings['registration_open'] ?? '1') ? '1' : '0';
        return $settings;
    }

    public function publicBranding(): array
    {
        try {
            $branding = $this->settingsRepository->publicBranding();
        } catch (\Throwable $e) {
            $branding = [];
        }
        // registration_open is already part of the branding SELECT above.
        $registrationOpen = $this->settingsRepository->isRegistrationOpen($branding['registration_open'] ?? '1');
        return [
            'company_name' => $branding['company_name'] ?? app_config('app_name', 'VivaahFlow'),
            'tagline' => $branding['tagline'] ?? 'Wedding Services Management System',
            'company_email' => $branding['company_email'] ?? '',
            'company_phone' => $branding['company_phone'] ?? '',
            'company_address' => $branding['company_address'] ?? '',
            'company_city' => $branding['company_city'] ?? '',
            'currency' => $branding['currency'] ?? 'INR',
            'logo_path' => upload_url($branding['logo_path'] ?? null),
            'footer_text' => $branding['footer_text'] ?? 'Crafting beautiful wedding experiences.',
            'currency_symbol' => $branding['currency_symbol'] ?? '₹',
            'registration_open' => $registrationOpen,
        ];
    }

    public function update(array $data): array
    {
        $fields = [
            'company_name', 'tagline', 'company_email', 'company_phone',
            'company_address', 'company_city', 'footer_text', 'currency',
            'currency_symbol', 'timezone',
        ];
        $values = [];
        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $values[$field] = (string)$data[$field];
            }
        }
        if (array_key_exists('registration_open', $data)) {
            $values['registration_open'] = in_array($data['registration_open'], [1, '1', true, 'true'], true) ? '1' : '0';
        }
        // Single upsert per key (no separate existence SELECT).
        if ($values !== []) {
            $this->settingsRepository->setMany($values);
        }
        return $this->all();
    }

    public function updateLogo(?array $file): array
    {
        if ($file === null) {
            return ['path' => null, 'error' => null];
        }
        $oldPath = $this->settingsRepository->get('logo_path');
        $result = ImageEngine::replace($file, 'settings', 'company-logo', $oldPath);
        if ($result['error'] === null && $result['path'] !== null) {
            $this->settingsRepository->set('logo_path', $result['path']);
        }
        return $result;
    }
}
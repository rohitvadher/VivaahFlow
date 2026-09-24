<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\SettingsService;
use App\Validators\Validator;

class SettingsController extends BaseController
{
    public function __construct(private SettingsService $settingsService)
    {
    }

    public function index(Request $request): void
    {
        $this->requirePermission('settings.read');
        $this->success($this->settingsService->all());
    }

    public function update(Request $request): void
    {
        $this->requirePermission('settings.update');
        $result = Validator::validate($request->all(), [
            'company_name' => 'required|string|max:160',
            'tagline' => 'nullable|string|max:200',
            'company_email' => 'nullable|email',
            'company_phone' => 'nullable|phone',
            'company_address' => 'nullable|string|max:255',
            'company_city' => 'nullable|string|max:80',
            'currency' => 'nullable|string|max:8',
            'timezone' => 'nullable|string|max:64',
            'footer_text' => 'nullable|string|max:255',
            'currency_symbol' => 'nullable|string|max:8',
            'registration_open' => 'required|boolean',
        ]);
        if (!$result['valid']) {
            Response::validation($result['errors']);
        }
        $this->success($this->settingsService->update($result['data']), 'Settings saved.');
    }

    public function logo(Request $request): void
    {
        $this->requirePermission('settings.update');
        $result = $this->settingsService->updateLogo($request->file('logo'));
        if ($result['error'] !== null) {
            Response::error($result['error'], 422);
        }
        $this->success(['logo_path' => upload_url($result['path'])], 'Logo updated.');
    }
}
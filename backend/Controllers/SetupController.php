<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\InstallationService;
use App\Validators\Validator;

class SetupController extends BaseController
{
    public function __construct(private InstallationService $installer)
    {
    }

    public function status(Request $request): void
    {
        $state = $this->installer->status();
        $this->success($state);
    }

    public function install(Request $request): void
    {
        if ($this->installer->isInstalled()) {
            Response::error('VivaahFlow is already installed. Re-installation is disabled.', 409);
        }
        $result = Validator::validate($request->all(), [
            'company_name' => 'required|string|min:2|max:160',
            'tagline' => 'nullable|string|max:200',
            'company_email' => 'nullable|email',
            'company_phone' => 'nullable|string|max:40',
            'company_address' => 'nullable|string|max:255',
            'company_city' => 'nullable|string|max:80',
            'currency' => 'nullable|string|max:8',
            'currency_symbol' => 'nullable|string|max:8',
            'timezone' => 'nullable|string|max:64',
            'footer_text' => 'nullable|string|max:255',
            'registration_open' => 'nullable',
            'admin_name' => 'required|string|min:3|max:120',
            'admin_email' => 'required|email',
            'admin_password' => 'required|string|min:6|max:120',
            'admin_password_confirm' => 'required|string|min:6|max:120',
            'with_demo' => 'nullable',
        ]);
        if (!$result['valid']) {
            Response::validation($result['errors']);
        }
        $data = $result['data'];
        if ((string)($data['admin_password'] ?? '') !== (string)($data['admin_password_confirm'] ?? '')) {
            Response::validation(['admin_password_confirm' => 'Password confirmation does not match.']);
        }
        $business = [
            'company_name' => $data['company_name'] ?? 'VivaahFlow',
            'tagline' => $data['tagline'] ?? null,
            'company_email' => $data['company_email'] ?? null,
            'company_phone' => $data['company_phone'] ?? null,
            'company_address' => $data['company_address'] ?? null,
            'company_city' => $data['company_city'] ?? null,
            'currency' => $data['currency'] ?? 'INR',
            'currency_symbol' => $data['currency_symbol'] ?? '₹',
            'timezone' => $data['timezone'] ?? 'Asia/Kolkata',
            'footer_text' => $data['footer_text'] ?? null,
            'registration_open' => isset($data['registration_open'])
                ? (in_array($data['registration_open'], [1, '1', true, 'true', 'yes', 'on'], true) ? '1' : '0')
                : '1',
        ];
        $business = array_filter($business, fn($v): bool => $v !== null);
        $withDemo = in_array($data['with_demo'] ?? false, [1, '1', true, 'true', 'yes', 'on'], true);
        try {
            $summary = $this->installer->install(
                $business,
                [
                    'name' => (string)$data['admin_name'],
                    'email' => (string)$data['admin_email'],
                    'password' => (string)$data['admin_password'],
                ],
                $withDemo
            );
        } catch (\InvalidArgumentException $e) {
            Response::error($e->getMessage(), 422);
        } catch (\Throwable $e) {
            error_log('[SETUP] install failed: ' . $e->getMessage());
            $message = $this->friendlySetupError($e);
            Response::error($message, 500);
        }
        // Rotate CSRF token post-install so the login form gets a fresh token.
        Session::set('_csrf', bin2hex(random_bytes(16)));
        Response::created([
            'summary' => $summary,
            'login_url' => url_for('manage.login'),
        ], 'VivaahFlow has been installed successfully.');
    }

    private function friendlySetupError(\Throwable $e): string
    {
        $message = strtolower($e->getMessage());
        if (str_contains($message, 'connection refused') || str_contains($message, 'no connection') || str_contains($message, 'could not find driver')) {
            return 'Could not reach the database server. Start MySQL/MariaDB and verify host, port and credentials.';
        }
        if (str_contains($message, 'access denied')) {
            return 'Database credentials were rejected. Check the DB username and password in config/config.php.';
        }
        if (str_contains($message, 'unknown database')) {
            return 'The application database could not be created. Check that the DB user has CREATE privileges.';
        }
        if (app_config('debug', false)) {
            return 'Installation failed: ' . $e->getMessage();
        }
        return 'Installation failed. Check storage/logs/php-error.log for details and try again.';
    }
}

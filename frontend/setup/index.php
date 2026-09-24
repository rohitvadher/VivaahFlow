<?php

declare(strict_types=1);

require_once __DIR__ . '/../../backend/bootstrap.php';

use App\Helpers\AssetManager;
use App\Services\InstallationService;

$brandName = (string)app_config('app_name', 'VivaahFlow');
$installer = new InstallationService();
$state = $installer->status();
$alreadyInstalled = (bool)($state['installed'] ?? false);
$initialState = json_encode($state, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup &middot; <?= e($brandName) ?></title>
    <?= AssetManager::head(['css/site.css']) ?>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#fdf2fb', 100: '#fbe5f7', 200: '#f5c2eb', 300: '#ec92da',
                            400: '#d95ebf', 500: '#b72c9b', 600: '#8b0a72', 700: '#72095e',
                            800: '#5c0848', 900: '#4a073b'
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                        display: ['"Playfair Display"', 'Georgia', 'serif']
                    }
                }
            }
        };
    </script>
    <?= AssetManager::favicon() ?>
    <style>
        .setup-steps { display: flex; gap: 8px; margin: 18px 0 22px; }
        .setup-step { flex: 1; text-align: center; font-size: 12px; font-weight: 600; color: var(--ink-400); }
        .setup-step .dot { display: flex; align-items: center; justify-content: center; width: 30px; height: 30px; border-radius: 50%; background: var(--ink-100); color: var(--ink-500); margin: 0 auto 6px; font-weight: 700; }
        .setup-step.is-active { color: var(--brand-700); }
        .setup-step.is-active .dot { background: var(--brand-600); color: #fff; }
        .setup-step.is-done .dot { background: var(--success); color: #fff; }
        .setup-pane[hidden] { display: none; }
    </style>
</head>
<body class="site">
<div class="site-section">
    <div class="site-container" style="max-width:760px">
        <div class="text-center mb-6">
            <img src="<?= asset_url('images/brand/logo.png') ?>" alt="<?= e($brandName) ?> logo" width="48" height="48" style="width:48px;height:48px;border-radius:14px;margin:0 auto 12px">
            <h1 class="site-section-title">Welcome to <?= e($brandName) ?></h1>
            <p class="site-section-sub">First-run setup — database, business profile and administrator account.</p>
        </div>

        <div class="card card-pad">
            <div data-setup-status class="alert alert-info" role="status">
                <i data-lucide="info" aria-hidden="true"></i>
                <div data-setup-status-text>Checking installation status…</div>
            </div>

            <div class="setup-steps" data-setup-steps hidden>
                <div class="setup-step" data-step="1"><span class="dot">1</span>Business</div>
                <div class="setup-step" data-step="2"><span class="dot">2</span>Admin</div>
                <div class="setup-step" data-step="3"><span class="dot">3</span>Finish</div>
            </div>

            <div data-setup-installed <?= $alreadyInstalled ? '' : 'hidden' ?>>
                <div class="alert alert-success">
                    <i data-lucide="circle-check-big" aria-hidden="true"></i>
                    <div>
                        <div class="font-semibold">VivaahFlow is already installed.</div>
                        <div class="text-sm mt-1">Re-installation is disabled to protect your data. Use the links below to continue.</div>
                    </div>
                </div>
                <div class="flex gap-2 mt-4" style="display:flex;gap:10px;flex-wrap:wrap">
                    <a class="btn btn-primary" href="<?= url_for('manage.login') ?>">Admin login</a>
                    <a class="btn btn-outline" href="<?= url_for('home') ?>">View website</a>
                </div>
            </div>

            <form data-setup-form novalidate <?= $alreadyInstalled ? 'hidden' : '' ?>>
                <div class="setup-pane" data-pane="1">
                    <h2 class="card-title">Business information</h2>
                    <p class="text-sm text-muted mt-1 mb-4">Shown across the website, invoices and notifications.</p>
                    <div class="form-grid">
                        <div class="full" style="grid-column:1/-1">
                            <label class="label" for="setup-company">Business / company name *</label>
                            <input class="form-control" id="setup-company" name="company_name" required value="VivaahFlow" autocomplete="organization">
                            <div class="form-error" data-error-for="company_name" role="alert"></div>
                        </div>
                        <div class="full" style="grid-column:1/-1">
                            <label class="label" for="setup-tagline">Tagline</label>
                            <input class="form-control" id="setup-tagline" name="tagline" value="Weddings planned with heart, executed with precision.">
                            <div class="form-error" data-error-for="tagline" role="alert"></div>
                        </div>
                        <div>
                            <label class="label" for="setup-email">Business email</label>
                            <input class="form-control" id="setup-email" name="company_email" type="email" autocomplete="email" placeholder="hello@vivaahflow.in">
                            <div class="form-error" data-error-for="company_email" role="alert"></div>
                        </div>
                        <div>
                            <label class="label" for="setup-phone">Phone</label>
                            <input class="form-control" id="setup-phone" name="company_phone" autocomplete="tel" placeholder="+91 98765 43210">
                            <div class="form-error" data-error-for="company_phone" role="alert"></div>
                        </div>
                        <div class="full" style="grid-column:1/-1">
                            <label class="label" for="setup-address">Address</label>
                            <input class="form-control" id="setup-address" name="company_address" autocomplete="street-address" placeholder="Street, area">
                            <div class="form-error" data-error-for="company_address" role="alert"></div>
                        </div>
                        <div>
                            <label class="label" for="setup-city">City</label>
                            <input class="form-control" id="setup-city" name="company_city" autocomplete="address-level2" value="Mumbai">
                            <div class="form-error" data-error-for="company_city" role="alert"></div>
                        </div>
                        <div>
                            <label class="label" for="setup-currency-symbol">Currency symbol</label>
                            <input class="form-control" id="setup-currency-symbol" name="currency_symbol" value="₹" maxlength="8">
                            <div class="form-error" data-error-for="currency_symbol" role="alert"></div>
                        </div>
                        <div>
                            <label class="label" for="setup-currency">Currency code</label>
                            <input class="form-control" id="setup-currency" name="currency" value="INR" maxlength="8">
                        </div>
                        <div>
                            <label class="label" for="setup-timezone">Timezone</label>
                            <input class="form-control" id="setup-timezone" name="timezone" value="Asia/Kolkata">
                        </div>
                        <div class="full" style="grid-column:1/-1">
                            <label class="label" for="setup-footer">Footer text</label>
                            <input class="form-control" id="setup-footer" name="footer_text" value="Crafting beautiful wedding experiences across India.">
                        </div>
                        <div class="full" style="grid-column:1/-1">
                            <label class="checkbox"><input type="checkbox" name="registration_open" value="1" checked> Allow new customers to register</label>
                        </div>
                    </div>
                    <div class="flex justify-end mt-6" style="display:flex;justify-content:flex-end">
                        <button type="button" class="btn btn-primary" data-next="2">Continue <i data-lucide="arrow-right" aria-hidden="true"></i></button>
                    </div>
                </div>

                <div class="setup-pane" data-pane="2" hidden>
                    <h2 class="card-title">Administrator account</h2>
                    <p class="text-sm text-muted mt-1 mb-4">You will use this to sign in at <code>/manage/login</code>.</p>
                    <div class="form-grid">
                        <div class="full" style="grid-column:1/-1">
                            <label class="label" for="setup-admin-name">Admin name *</label>
                            <div class="input-group">
                                <span class="input-icon" aria-hidden="true"><i data-lucide="user-round"></i></span>
                                <input class="form-control" id="setup-admin-name" name="admin_name" required autocomplete="name" placeholder="Your name">
                            </div>
                            <div class="form-error" data-error-for="admin_name" role="alert"></div>
                        </div>
                        <div class="full" style="grid-column:1/-1">
                            <label class="label" for="setup-admin-email">Admin email *</label>
                            <div class="input-group">
                                <span class="input-icon" aria-hidden="true"><i data-lucide="mail"></i></span>
                                <input class="form-control" id="setup-admin-email" name="admin_email" type="email" required autocomplete="email" placeholder="you@example.com">
                            </div>
                            <div class="form-error" data-error-for="admin_email" role="alert"></div>
                        </div>
                        <div>
                            <label class="label" for="setup-admin-password">Password *</label>
                            <div class="input-group has-right-action">
                                <span class="input-icon" aria-hidden="true"><i data-lucide="lock"></i></span>
                                <input class="form-control" id="setup-admin-password" name="admin_password" type="password" required autocomplete="new-password" placeholder="Minimum 6 characters">
                                <button type="button" class="input-action" data-password-toggle="#setup-admin-password" aria-label="Show password" title="Show password" aria-pressed="false"><i data-lucide="eye" aria-hidden="true"></i></button>
                            </div>
                            <div class="form-error" data-error-for="admin_password" role="alert"></div>
                        </div>
                        <div>
                            <label class="label" for="setup-admin-confirm">Confirm password *</label>
                            <div class="input-group has-right-action">
                                <span class="input-icon" aria-hidden="true"><i data-lucide="lock"></i></span>
                                <input class="form-control" id="setup-admin-confirm" name="admin_password_confirm" type="password" required autocomplete="new-password" placeholder="Repeat password">
                                <button type="button" class="input-action" data-password-toggle="#setup-admin-confirm" aria-label="Show password" title="Show password" aria-pressed="false"><i data-lucide="eye" aria-hidden="true"></i></button>
                            </div>
                            <div class="form-error" data-error-for="admin_password_confirm" role="alert"></div>
                        </div>
                        <div class="full" style="grid-column:1/-1">
                            <label class="checkbox"><input type="checkbox" name="with_demo" value="1" checked> Install sample / demo data (recommended for evaluation)</label>
                            <p class="form-hint">Demo data includes catalogue, bookings, payments and working demo accounts. Uncheck for a clean production start.</p>
                        </div>
                    </div>
                    <div class="mt-6" style="display:flex;justify-content:space-between;gap:10px">
                        <button type="button" class="btn btn-ghost" data-back="1"><i data-lucide="arrow-left" aria-hidden="true"></i> Back</button>
                        <button type="button" class="btn btn-primary" data-next="3">Review &amp; install</button>
                    </div>
                </div>

                <div class="setup-pane" data-pane="3" hidden>
                    <h2 class="card-title">Confirm installation</h2>
                    <p class="text-sm text-muted mt-1 mb-4">Review the summary below, then install. This creates the <code>vivaahflow</code> database, schema and your admin account.</p>
                    <div class="card" style="background:var(--ink-50)"><div class="card-pad" data-setup-summary></div></div>
                    <div class="mt-6" style="display:flex;justify-content:space-between;gap:10px">
                        <button type="button" class="btn btn-ghost" data-back="2"><i data-lucide="arrow-left" aria-hidden="true"></i> Back</button>
                        <button type="submit" class="btn btn-primary btn-lg" data-submit><i data-lucide="rocket" aria-hidden="true"></i> Install VivaahFlow</button>
                    </div>
                </div>
            </form>

            <div data-setup-done hidden>
                <div class="alert alert-success">
                    <i data-lucide="circle-check-big" aria-hidden="true"></i>
                    <div>
                        <div class="font-semibold">Installation complete.</div>
                        <div class="text-sm mt-1" data-setup-done-text></div>
                    </div>
                </div>
                <div class="mt-4" style="display:flex;gap:10px;flex-wrap:wrap">
                    <a class="btn btn-primary" href="<?= url_for('manage.login') ?>">Go to admin login</a>
                    <a class="btn btn-outline" href="<?= url_for('home') ?>">View website</a>
                </div>
                <div class="text-sm text-muted mt-4">Next steps: sign in, review Settings, change demo passwords before real use.</div>
            </div>
        </div>
        <p class="text-center text-sm text-muted mt-4">Database: <code><?= e(\App\Database\Connection::databaseName()) ?></code> &middot; Setup is idempotent and safe to re-open.</p>
    </div>
</div>
<script>window.VIVAAH_SETUP_INITIAL = <?= $initialState ?>;</script>
<?= AssetManager::scripts('js/setup/wizard.js') ?>
</body>
</html>

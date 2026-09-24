<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/backend/bootstrap.php';

use App\Core\Session;
use App\Helpers\AssetManager;

if (Session::get('user_id')) {
    if ((string)Session::get('user_role') === 'customer') {
        redirect(route_path('account.dashboard'));
    }
    redirect(route_path('manage.dashboard'));
}

$brandName = (string)app_config('app_name', 'VivaahFlow');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In &middot; <?= e($brandName) ?></title>
    <?= AssetManager::head() ?>
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
</head>
<body>
<div class="auth-wrap">
    <aside class="auth-aside bg-brand-gradient">
        <div class="auth-logo" style="position:relative;z-index:1">
            <img src="<?= asset_url('images/brand/logo.png') ?>" alt="<?= e($brandName) ?> logo" width="40" height="40" style="width:40px;height:40px;border-radius:12px">
            <span style="font-family:var(--font-display);font-weight:700;font-size:18px"><?= e($brandName) ?></span>
        </div>
        <div class="auth-quote">
            <h2>Plan every celebration with confidence.</h2>
            <div class="auth-points">
                <div class="auth-point"><i data-lucide="circle-check-big"></i> Manage enquiries, quotations and bookings</div>
                <div class="auth-point"><i data-lucide="circle-check-big"></i> Track payments, invoices and revenue</div>
                <div class="auth-point"><i data-lucide="circle-check-big"></i> Coordinate events and staff in one place</div>
                <div class="auth-point"><i data-lucide="circle-check-big"></i> Delight customers with a self-service portal</div>
            </div>
        </div>
        <div style="position:relative;z-index:1;font-size:13px;color:rgba(255,255,255,0.75)">
            &copy; <?= date('Y') ?> <?= e($brandName) ?>. All rights reserved.
        </div>
    </aside>
    <div class="auth-form-wrap">
        <div class="auth-card rise-in">
            <div class="mb-6">
                <h1 class="text-2xl">Welcome back</h1>
                <p class="text-muted mt-1">Sign in to your management dashboard.</p>
            </div>
            <div class="card card-pad">
                <form id="login-form" novalidate>
                    <div class="mb-4">
                        <label class="label" for="email">Email address</label>
                        <div class="input-group">
                            <span class="input-icon" aria-hidden="true"><i data-lucide="mail"></i></span>
                            <input class="form-control" type="email" id="email" name="email" placeholder="you@example.com" autocomplete="email" autofocus>
                        </div>
                        <div class="form-error" data-error-for="email" role="alert"></div>
                    </div>
                    <div class="mb-4">
                        <label class="label" for="password">Password</label>
                        <div class="input-group has-right-action">
                            <span class="input-icon" aria-hidden="true"><i data-lucide="lock"></i></span>
                            <input class="form-control" type="password" id="password" name="password" placeholder="Your password" autocomplete="current-password">
                            <button type="button" class="input-action" data-password-toggle="#password" aria-label="Show password" title="Show password" aria-pressed="false">
                                <i data-lucide="eye" aria-hidden="true"></i>
                            </button>
                        </div>
                        <div class="form-error" data-error-for="password" role="alert"></div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block btn-lg" data-submit>Sign In</button>
                </form>
            </div>
            <div class="alert alert-info mt-4">
                <i data-lucide="info" style="width:18px;height:18px;flex:none"></i>
                <div>
                    <div class="font-semibold">Demo accounts</div>
                    <div class="text-sm mt-1">
                        Admin: admin@example.com / Admin@123<br>
                        Manager: manager@example.com / Manager@123<br>
                        Staff: staff@example.com / Staff@123
                    </div>
                </div>
            </div>
            <p class="text-center text-muted text-sm mt-4">
                <a class="link-brand" href="<?= url_for('home') ?>">Back to website</a>
            </p>
        </div>
    </div>
</div>
<?= AssetManager::scripts('js/pages/login.js') ?>
</body>
</html>


<?php

declare(strict_types=1);

require __DIR__ . '/partials/bootstrap.php';

if (site_is_customer()) {
    redirect(route_path('account.dashboard'));
}

$pageTitle = 'Sign In';
$pageDesc = 'Sign in to your customer portal.';
$activeNav = '';
$pageScript = 'js/pages/public/login.js';

require __DIR__ . '/partials/header.php';
?>
<section class="site-section">
    <div class="site-container">
        <div class="auth-card card card-pad" style="max-width:440px;margin:0 auto">
            <div class="text-center mb-6">
                <span class="stat-icon mx-auto mb-3"><i data-lucide="user-round" aria-hidden="true"></i></span>
                <h1 class="site-section-title" style="font-size:26px">Welcome back</h1>
                <p class="site-section-sub">Sign in to manage your enquiries, quotations and bookings.</p>
            </div>
            <form data-login-form novalidate>
                <div class="form-control" style="padding:0;border:none;background:none">
                    <label class="label" for="login-email">Email address</label>
                    <div class="input-group">
                        <span class="input-icon" aria-hidden="true"><i data-lucide="mail"></i></span>
                        <input class="form-control" id="login-email" name="email" type="email" required autocomplete="email" placeholder="you@example.com">
                    </div>
                    <div class="form-error" data-error-for="email" role="alert"></div>
                </div>
                <div class="form-control mt-4" style="padding:0;border:none;background:none">
                    <label class="label" for="login-password">Password</label>
                    <div class="input-group has-right-action">
                        <span class="input-icon" aria-hidden="true"><i data-lucide="lock"></i></span>
                        <input class="form-control" id="login-password" name="password" type="password" required autocomplete="current-password" placeholder="Your password">
                        <button type="button" class="input-action" data-password-toggle="#login-password" aria-label="Show password" title="Show password" aria-pressed="false">
                            <i data-lucide="eye" aria-hidden="true"></i>
                        </button>
                    </div>
                    <div class="form-error" data-error-for="password" role="alert"></div>
                </div>
                <button class="btn btn-primary btn-block mt-6" type="submit" data-submit>
                    <i data-lucide="log-in" aria-hidden="true"></i> Sign In
                </button>
            </form>
            <p class="text-sm text-muted text-center mt-5">
                New here? <a href="<?= url_for('register') ?>" style="color:var(--brand-600)">Create an account</a>
            </p>
        </div>
    </div>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>

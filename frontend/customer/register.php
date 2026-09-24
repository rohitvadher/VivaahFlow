<?php

declare(strict_types=1);

require __DIR__ . '/partials/bootstrap.php';

if (site_is_customer()) {
    redirect(route_path('account.dashboard'));
}

$pageTitle = 'Create Account';
$pageDesc = 'Create your account to plan and track your celebration.';
$activeNav = '';
$pageScript = 'js/pages/public/register.js';

require __DIR__ . '/partials/header.php';
?>
<section class="site-section">
    <div class="site-container">
        <div class="card card-pad" style="max-width:560px;margin:0 auto">
            <div class="text-center mb-6">
                <span class="stat-icon mx-auto mb-3"><i data-lucide="sparkles" aria-hidden="true"></i></span>
                <h1 class="site-section-title" style="font-size:26px">Create your account</h1>
                <p class="site-section-sub">Track enquiries, accept quotations and follow every detail in one place.</p>
            </div>
            <div data-registration-closed hidden>
                <div class="table-empty">
                    <div class="empty-title">Registration is currently closed</div>
                    <p class="text-sm text-muted mt-1">Please <a href="<?= url_for('contact') ?>" style="color:var(--brand-600)">contact our team</a> to get started.</p>
                </div>
            </div>
            <form data-register-form novalidate>
                <div class="form-grid">
                    <div class="full" style="grid-column:1/-1">
                        <label class="label" for="reg-name">Full name *</label>
                        <div class="input-group">
                            <span class="input-icon" aria-hidden="true"><i data-lucide="user-round"></i></span>
                            <input class="form-control" id="reg-name" name="name" required autocomplete="name" placeholder="Your full name">
                        </div>
                        <div class="form-error" data-error-for="name" role="alert"></div>
                    </div>
                    <div>
                        <label class="label" for="reg-email">Email *</label>
                        <div class="input-group">
                            <span class="input-icon" aria-hidden="true"><i data-lucide="mail"></i></span>
                            <input class="form-control" id="reg-email" name="email" type="email" required autocomplete="email" placeholder="you@example.com">
                        </div>
                        <div class="form-error" data-error-for="email" role="alert"></div>
                    </div>
                    <div>
                        <label class="label" for="reg-phone">Phone</label>
                        <div class="input-group">
                            <span class="input-icon" aria-hidden="true"><i data-lucide="phone"></i></span>
                            <input class="form-control" id="reg-phone" name="phone" type="tel" autocomplete="tel" placeholder="+91 98765 43210">
                        </div>
                        <div class="form-error" data-error-for="phone" role="alert"></div>
                    </div>
                    <div>
                        <label class="label" for="reg-password">Password *</label>
                        <div class="input-group has-right-action">
                            <span class="input-icon" aria-hidden="true"><i data-lucide="lock"></i></span>
                            <input class="form-control" id="reg-password" name="password" type="password" required autocomplete="new-password" placeholder="Minimum 6 characters">
                            <button type="button" class="input-action" data-password-toggle="#reg-password" aria-label="Show password" title="Show password" aria-pressed="false">
                                <i data-lucide="eye" aria-hidden="true"></i>
                            </button>
                        </div>
                        <div class="form-error" data-error-for="password" role="alert"></div>
                    </div>
                    <div>
                        <label class="label" for="reg-date">Wedding / event date</label>
                        <div class="input-group">
                            <span class="input-icon" aria-hidden="true"><i data-lucide="calendar-days"></i></span>
                            <input class="form-control" id="reg-date" name="wedding_date" type="date" autocomplete="off">
                        </div>
                        <div class="form-error" data-error-for="wedding_date" role="alert"></div>
                    </div>
                    <div class="full" style="grid-column:1/-1">
                        <label class="label" for="reg-type">Event type</label>
                        <div class="input-group">
                            <span class="input-icon" aria-hidden="true"><i data-lucide="sparkles"></i></span>
                            <input class="form-control" id="reg-type" name="event_type" placeholder="Wedding, Reception, Engagement" autocomplete="off">
                        </div>
                        <div class="form-error" data-error-for="event_type" role="alert"></div>
                    </div>
                </div>
                <button class="btn btn-primary btn-block mt-6" type="submit" data-submit>
                    <i data-lucide="user-plus" aria-hidden="true"></i> Create Account
                </button>
                <p class="text-xs text-muted text-center mt-4">By creating an account you agree to be contacted about your enquiry.</p>
            </form>
            <p class="text-sm text-muted text-center mt-5">
                Already have an account? <a href="<?= url_for('login') ?>" style="color:var(--brand-600)">Sign in</a>
            </p>
        </div>
    </div>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>

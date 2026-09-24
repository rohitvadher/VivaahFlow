<?php

declare(strict_types=1);

$pageTitle = 'Settings';
$pageDesc = 'Business profile and branding';
$activeNav = 'settings';
$pageScript = 'js/pages/settings.js';

require __DIR__ . '/partials/header.php';
?>
<div class="page-head">
    <div>
        <h1 class="page-title">Settings</h1>
        <p class="page-desc">These details appear across the admin panel and public website.</p>
    </div>
</div>

<div class="grid-3">
    <div class="grid-span-2 card">
        <div class="card-head"><h2 class="card-title">Business Profile</h2></div>
        <form id="settings-form">
            <div class="card-pad">
                <div class="form-grid">
                    <div class="full">
                        <label class="label">Company name</label>
                        <input class="form-control" type="text" name="company_name" required>
                        <div class="form-error" data-error-for="company_name"></div>
                    </div>
                    <div class="full">
                        <label class="label">Tagline</label>
                        <input class="form-control" type="text" name="tagline">
                    </div>
                    <div>
                        <label class="label">Email</label>
                        <input class="form-control" type="email" name="company_email">
                        <div class="form-error" data-error-for="company_email"></div>
                    </div>
                    <div>
                        <label class="label">Phone</label>
                        <input class="form-control" type="text" name="company_phone">
                    </div>
                    <div class="full">
                        <label class="label">Address</label>
                        <input class="form-control" type="text" name="company_address">
                    </div>
                    <div>
                        <label class="label">Currency symbol</label>
                        <input class="form-control" type="text" name="currency_symbol" maxlength="8">
                    </div>
                    <div class="full">
                        <label class="label">Footer text</label>
                        <input class="form-control" type="text" name="footer_text">
                    </div>
                    <div class="full">
                        <label class="checkbox"><input type="checkbox" name="registration_open" value="1" checked> Allow new customers to register</label>
                    </div>
                </div>
            </div>
            <div class="card-pad flex justify-end">
                <button type="submit" class="btn btn-primary" data-submit data-permission="settings.update"><i data-lucide="save"></i> Save Settings</button>
            </div>
        </form>
    </div>
    <div class="card">
        <div class="card-head"><h2 class="card-title">Logo</h2></div>
        <div class="card-pad">
            <div class="upload-drop" data-logo-dropzone>
                <div data-logo-preview class="hidden"><img alt="Logo preview"></div>
                <div class="text-muted text-sm mt-2" data-logo-info></div>
                <input type="file" class="hidden" accept="image/png,image/jpeg,image/webp" data-logo-input>
            </div>
            <button type="button" class="btn btn-outline btn-block mt-4" data-logo-upload data-permission="settings.update">Upload Logo</button>
        </div>
    </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>


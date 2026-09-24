<?php

declare(strict_types=1);

$pageTitle = 'My Profile';
$pageDesc = 'Keep your contact details up to date so we can reach you.';
$activeNav = 'profile';
$pageScript = 'js/pages/portal/profile.js';

require __DIR__ . '/partials/header.php';
?>
<div class="grid gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2 grid gap-6">
        <div class="card">
            <div class="card-head">
                <div>
                    <h2 class="card-title">Profile details</h2>
                    <p class="card-subtitle">Your name, contact and celebration details</p>
                </div>
            </div>
            <form class="card-pad" data-profile-form novalidate>
                <div class="form-grid">
                    <div class="form-control">
                        <label class="label" for="pf-name">Full name *</label>
                        <input class="form-control" id="pf-name" name="name" required>
                        <div class="form-error" data-error-for="name"></div>
                    </div>
                    <div class="form-control">
                        <label class="label" for="pf-phone">Phone</label>
                        <input class="form-control" id="pf-phone" name="phone">
                        <div class="form-error" data-error-for="phone"></div>
                    </div>
                    <div class="form-control">
                        <label class="label" for="pf-date">Wedding / event date</label>
                        <input class="form-control" id="pf-date" name="wedding_date" type="date">
                        <div class="form-error" data-error-for="wedding_date"></div>
                    </div>
                    <div class="form-control">
                        <label class="label" for="pf-type">Event type</label>
                        <input class="form-control" id="pf-type" name="event_type">
                        <div class="form-error" data-error-for="event_type"></div>
                    </div>
                    <div class="form-control grid-span-2">
                        <label class="label" for="pf-address">Address</label>
                        <input class="form-control" id="pf-address" name="address">
                        <div class="form-error" data-error-for="address"></div>
                    </div>
                    <div class="form-control">
                        <label class="label" for="pf-city">City</label>
                        <input class="form-control" id="pf-city" name="city">
                        <div class="form-error" data-error-for="city"></div>
                    </div>
                </div>
                <div class="flex justify-end mt-6">
                    <button type="submit" class="btn btn-primary" data-submit>
                        <i data-lucide="save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>

        <div class="card">
            <div class="card-head">
                <div>
                    <h2 class="card-title">Change password</h2>
                    <p class="card-subtitle">Use at least 6 characters</p>
                </div>
            </div>
            <form class="card-pad" data-password-form novalidate>
                <div class="form-grid">
                    <div class="form-control">
                        <label class="label" for="pw-current">Current password *</label>
                        <input class="form-control" id="pw-current" name="current_password" type="password" required>
                        <div class="form-error" data-error-for="current_password"></div>
                    </div>
                    <div class="form-control">
                        <label class="label" for="pw-new">New password *</label>
                        <input class="form-control" id="pw-new" name="new_password" type="password" required>
                        <div class="form-error" data-error-for="new_password"></div>
                    </div>
                </div>
                <div class="flex justify-end mt-6">
                    <button type="submit" class="btn btn-outline" data-submit>
                        <i data-lucide="key-round"></i> Update Password
                    </button>
                </div>
            </form>
        </div>
    </div>

    <aside>
        <div class="card card-pad">
            <div class="text-center">
                <span class="avatar" style="width:64px;height:64px;font-size:22px;margin:0 auto" data-avatar>&mdash;</span>
                <h3 class="font-semibold mt-3" data-profile-name>&mdash;</h3>
                <p class="text-sm text-muted" data-profile-email></p>
            </div>
            <div class="detail-list mt-5">
                <div class="detail-row"><span>Customer since</span><strong data-profile-since>&mdash;</strong></div>
                <div class="detail-row"><span>Event date</span><strong data-profile-date>&mdash;</strong></div>
            </div>
        </div>
    </aside>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>


<?php

declare(strict_types=1);

$pageTitle = 'My Profile';
$pageDesc = 'Manage your account details and password';
$activeNav = 'profile';
$pageScript = 'js/pages/profile.js';

require __DIR__ . '/partials/header.php';
?>
<div class="page-head">
    <div>
        <h1 class="page-title">My Profile</h1>
        <p class="page-desc">Update your personal details and password.</p>
    </div>
</div>

<div class="grid-3">
    <div class="card">
        <div class="card-head"><h2 class="card-title">Account</h2></div>
        <div class="card-pad" id="profile-summary">
            <?= loader_skeleton(1) ?>
            <?= loader_skeleton(1) ?>
        </div>
    </div>
    <div class="card">
        <div class="card-head"><h2 class="card-title">Personal Details</h2></div>
        <form id="profile-form">
            <div class="card-pad">
                <div class="form-grid">
                    <div class="full">
                        <label class="label">Name</label>
                        <input class="form-control" type="text" name="name" required>
                        <div class="form-error" data-error-for="name"></div>
                    </div>
                    <div class="full">
                        <label class="label">Email</label>
                        <input class="form-control" type="email" name="email" disabled>
                        <div class="text-muted text-xs mt-1">Email is managed by an administrator.</div>
                    </div>
                    <div class="full">
                        <label class="label">Phone</label>
                        <input class="form-control" type="text" name="phone">
                        <div class="form-error" data-error-for="phone"></div>
                    </div>
                </div>
            </div>
            <div class="card-pad flex justify-end">
                <button type="submit" class="btn btn-primary" data-submit><i data-lucide="save"></i> Save Details</button>
            </div>
        </form>
    </div>
    <div class="card">
        <div class="card-head"><h2 class="card-title">Change Password</h2></div>
        <form id="password-form">
            <div class="card-pad">
                <div class="form-grid">
                    <div class="full">
                        <label class="label">Current password</label>
                        <input class="form-control" type="password" name="current_password" required>
                        <div class="form-error" data-error-for="current_password"></div>
                    </div>
                    <div class="full">
                        <label class="label">New password</label>
                        <input class="form-control" type="password" name="new_password" minlength="6" required>
                        <div class="form-error" data-error-for="new_password"></div>
                    </div>
                </div>
            </div>
            <div class="card-pad flex justify-end">
                <button type="submit" class="btn btn-outline" data-submit><i data-lucide="lock"></i> Update Password</button>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>


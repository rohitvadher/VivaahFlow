<?php

declare(strict_types=1);

$pageTitle = 'Staff';
$pageDesc = 'Team members available for event assignments';
$activeNav = 'staff';
$pageScript = 'js/pages/staff.js';

require __DIR__ . '/partials/header.php';
?>
<div class="page-head">
    <div>
        <h1 class="page-title">Staff</h1>
        <p class="page-desc">Assign active staff to events from each booking or the schedule.</p>
    </div>
    <div class="page-actions">
        <button type="button" class="btn btn-primary" data-open-staff data-permission="staff.create"><i data-lucide="plus"></i> Add Staff</button>
    </div>
</div>

<div class="card">
    <div class="card-head">
        <div class="toolbar w-full">
            <div class="input-group search-box">
                <span class="input-icon"><i data-lucide="search"></i></span>
                <input type="search" class="form-control" placeholder="Search staff by name or role" data-table-search>
            </div>
            <select class="form-control" style="width:auto" data-table-status>
                <option value="">All statuses</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
            <span class="text-muted text-sm ml-auto" data-count-label></span>
        </div>
    </div>
    <div id="staff-table">
        <div class="card-pad"><?= loader_skeleton(3) ?></div>
    </div>
    <div class="pagination" id="staff-pagination"></div>
</div>

<div class="modal" id="staff-modal" hidden>
    <div class="modal-dialog">
        <form id="staff-form">
            <div class="modal-head">
                <h3 class="card-title" data-staff-title>Add Staff</h3>
                <button type="button" class="btn-icon" data-modal-close aria-label="Close"><i data-lucide="x"></i></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id">
                <div class="form-grid">
                    <div>
                        <label class="label">Name</label>
                        <input class="form-control" type="text" name="name" required>
                        <div class="form-error" data-error-for="name"></div>
                    </div>
                    <div>
                        <label class="label">Designation</label>
                        <input class="form-control" type="text" name="designation">
                    </div>
                    <div>
                        <label class="label">Email</label>
                        <input class="form-control" type="email" name="email">
                        <div class="form-error" data-error-for="email"></div>
                    </div>
                    <div>
                        <label class="label">Phone</label>
                        <input class="form-control" type="text" name="phone">
                    </div>
                    <div class="full">
                        <label class="label">Specialty</label>
                        <input class="form-control" type="text" name="specialty">
                    </div>
                    <div class="full" data-login-fields>
                        <div class="flex items-center gap-2">
                            <input type="checkbox" name="create_login" value="1" data-login-toggle>
                            <label class="label mb-0">Create a login for this staff member</label>
                        </div>
                    </div>
                    <div class="full" data-password-field>
                        <label class="label">Password</label>
                        <input class="form-control" type="password" name="password" minlength="6">
                        <div class="form-error" data-error-for="password"></div>
                    </div>
                    <div>
                        <label class="label">Status</label>
                        <select class="form-control" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="full">
                        <label class="label">Notes</label>
                        <textarea class="form-control" name="notes"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-ghost" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary" data-submit>Save Staff</button>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>


<?php

declare(strict_types=1);

$pageTitle = 'Users';
$pageDesc = 'Accounts and access roles';
$activeNav = 'users';
$pageScript = 'js/pages/users.js';

require __DIR__ . '/partials/header.php';
?>
<div class="page-head">
    <div>
        <h1 class="page-title">Users</h1>
        <p class="page-desc">Control who can access the admin panel and what they can do.</p>
    </div>
    <div class="page-actions">
        <button type="button" class="btn btn-primary" data-open-user data-permission="users.create"><i data-lucide="plus"></i> Add User</button>
    </div>
</div>

<div class="card">
    <div class="card-head">
        <div class="toolbar w-full">
            <div class="input-group search-box">
                <span class="input-icon"><i data-lucide="search"></i></span>
                <input type="search" class="form-control" placeholder="Search users by name or email" data-table-search>
            </div>
            <select class="form-control" style="width:auto" data-role-filter></select>
            <span class="text-muted text-sm ml-auto" data-count-label></span>
        </div>
    </div>
    <div id="users-table">
        <div class="card-pad"><?= loader_skeleton(3) ?></div>
    </div>
    <div class="pagination" id="users-pagination"></div>
</div>

<div class="modal" id="user-modal" hidden>
    <div class="modal-dialog">
        <form id="user-form">
            <div class="modal-head">
                <h3 class="card-title" data-user-title>Add User</h3>
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
                        <label class="label">Role</label>
                        <select class="form-control" name="role_id" data-role-select required></select>
                        <div class="form-error" data-error-for="role_id"></div>
                    </div>
                    <div>
                        <label class="label">Email</label>
                        <input class="form-control" type="email" name="email" required>
                        <div class="form-error" data-error-for="email"></div>
                    </div>
                    <div>
                        <label class="label">Phone</label>
                        <input class="form-control" type="text" name="phone">
                    </div>
                    <div>
                        <label class="label" data-password-label>Password</label>
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
                </div>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-ghost" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary" data-submit>Save User</button>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>


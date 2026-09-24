<?php

declare(strict_types=1);

$pageTitle = 'Leads';
$pageDesc = 'Track and convert prospective customers';
$activeNav = 'leads';
$pageScript = 'js/pages/leads.js';

require __DIR__ . '/partials/header.php';
?>
<div class="page-head">
    <div>
        <h1 class="page-title">Leads</h1>
        <p class="page-desc">Follow up on prospects and turn them into bookings.</p>
    </div>
    <div class="page-actions">
        <button type="button" class="btn btn-primary" data-open-lead data-permission="leads.create">
            <i data-lucide="plus"></i> Add Lead
        </button>
    </div>
</div>

<div class="card">
    <div class="card-head">
        <div class="toolbar w-full">
            <div class="input-group search-box">
                <span class="input-icon"><i data-lucide="search"></i></span>
                <input type="search" class="form-control" placeholder="Search name, email or phone" data-table-search>
            </div>
            <select class="form-control" style="width:auto" data-table-status>
                <option value="">All statuses</option>
                <option value="new">New</option>
                <option value="contacted">Contacted</option>
                <option value="follow_up">Follow Up</option>
                <option value="converted">Converted</option>
                <option value="lost">Lost</option>
            </select>
            <span class="text-muted text-sm ml-auto" data-count-label></span>
        </div>
    </div>
    <div id="leads-table">
        <div class="card-pad"><?= loader_skeleton(4) ?></div>
    </div>
    <div class="pagination" id="leads-pagination"></div>
</div>

<div class="modal" id="lead-form-modal" hidden>
    <div class="modal-dialog">
        <form id="lead-form">
            <div class="modal-head">
                <h3 class="card-title" data-modal-title>Add Lead</h3>
                <button type="button" class="btn-icon" data-modal-close aria-label="Close"><i data-lucide="x"></i></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id">
                <div class="form-grid">
                    <div class="full">
                        <label class="label">Full name</label>
                        <input class="form-control" type="text" name="name">
                        <div class="form-error" data-error-for="name"></div>
                    </div>
                    <div>
                        <label class="label">Email</label>
                        <input class="form-control" type="email" name="email">
                        <div class="form-error" data-error-for="email"></div>
                    </div>
                    <div>
                        <label class="label">Phone</label>
                        <input class="form-control" type="tel" name="phone">
                        <div class="form-error" data-error-for="phone"></div>
                    </div>
                    <div>
                        <label class="label">Source</label>
                        <input class="form-control" type="text" name="source" placeholder="e.g. referral, website">
                    </div>
                    <div>
                        <label class="label">Status</label>
                        <select class="form-control" name="status">
                            <option value="new">New</option>
                            <option value="contacted">Contacted</option>
                            <option value="follow_up">Follow Up</option>
                            <option value="converted">Converted</option>
                            <option value="lost">Lost</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">Assign to</label>
                        <select class="form-control" name="assigned_to" data-staff-select>
                            <option value="">Unassigned</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">Next follow-up</label>
                        <input class="form-control" type="date" name="next_followup_at">
                    </div>
                    <div class="full">
                        <label class="label">Notes</label>
                        <textarea class="form-control" name="notes"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-ghost" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary" data-submit>Save Lead</button>
            </div>
        </form>
    </div>
</div>

<div class="modal" id="lead-detail-modal" hidden>
    <div class="modal-dialog modal-lg">
        <div class="modal-head">
            <div>
                <h3 class="card-title" data-lead-name>Lead</h3>
                <div class="text-muted text-sm" data-lead-meta></div>
            </div>
            <button type="button" class="btn-icon" data-modal-close aria-label="Close"><i data-lucide="x"></i></button>
        </div>
        <div class="modal-body" data-lead-body></div>
    </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>


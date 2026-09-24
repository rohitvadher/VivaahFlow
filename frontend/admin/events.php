<?php

declare(strict_types=1);

$pageTitle = 'Events';
$pageDesc = 'Track every scheduled event and its team';
$activeNav = 'events';
$pageScript = 'js/pages/events.js';

require __DIR__ . '/partials/header.php';
?>
<div class="page-head">
    <div>
        <h1 class="page-title">Events</h1>
        <p class="page-desc">Every booking can run across multiple dated events.</p>
    </div>
</div>

<div class="grid-3">
    <div class="grid-span-2">
        <div class="card">
            <div class="card-head">
                <div class="toolbar w-full">
                    <div class="input-group search-box">
                        <span class="input-icon"><i data-lucide="search"></i></span>
                        <input type="search" class="form-control" placeholder="Search events or customers" data-table-search>
                    </div>
                    <select class="form-control" style="width:auto" data-table-status>
                        <option value="">All statuses</option>
                        <option value="scheduled">Scheduled</option>
                        <option value="in_progress">In progress</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                    <input class="form-control" type="date" style="width:auto" data-filter-date aria-label="Event date">
                    <span class="text-muted text-sm ml-auto" data-count-label></span>
                </div>
            </div>
            <div id="events-table">
                <div class="card-pad"><?= loader_skeleton(3) ?></div>
            </div>
            <div class="pagination" id="events-pagination"></div>
        </div>
    </div>
    <div>
        <div class="card">
            <div class="card-head">
                <h2 class="card-title">Day Schedule</h2>
                <input class="form-control" type="date" style="width:auto" data-schedule-date aria-label="Schedule date">
            </div>
            <div class="card-pad" id="schedule-panel">
                <?= loader_skeleton(1) ?>
                <?= loader_skeleton(1) ?>
            </div>
        </div>
    </div>
</div>

<div class="modal" id="event-modal" hidden>
    <div class="modal-dialog">
        <form id="event-form">
            <div class="modal-head">
                <h3 class="card-title">Edit Event</h3>
                <button type="button" class="btn-icon" data-modal-close aria-label="Close"><i data-lucide="x"></i></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id">
                <div class="form-grid">
                    <div class="full">
                        <label class="label">Title</label>
                        <input class="form-control" type="text" name="title" required>
                        <div class="form-error" data-error-for="title"></div>
                    </div>
                    <div>
                        <label class="label">Date</label>
                        <input class="form-control" type="date" name="event_date" required>
                    </div>
                    <div>
                        <label class="label">Venue</label>
                        <input class="form-control" type="text" name="venue_address">
                    </div>
                    <div>
                        <label class="label">Start time</label>
                        <input class="form-control" type="time" name="start_time">
                    </div>
                    <div>
                        <label class="label">End time</label>
                        <input class="form-control" type="time" name="end_time">
                    </div>
                    <div class="full">
                        <label class="label">Notes</label>
                        <textarea class="form-control" name="notes"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-ghost" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary" data-submit>Save Event</button>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>


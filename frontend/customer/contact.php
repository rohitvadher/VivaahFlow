<?php

declare(strict_types=1);

$pageTitle = 'Plan Your Event';
$pageDesc = 'Share your celebration details and receive a personalised proposal.';
$activeNav = 'contact';
$pageScript = 'js/pages/public/contact.js';

require __DIR__ . '/partials/header.php';
?>
<section class="site-section is-tight" style="padding-bottom:0">
    <div class="site-container">
        <h1 class="site-section-title">Plan your event</h1>
        <p class="site-section-sub">Tell us what you are celebrating and we will get back within 24 hours.</p>
    </div>
</section>

<section class="site-section">
    <div class="site-container">
        <div class="grid gap-8 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <div class="card card-pad">
                    <form data-enquiry-form novalidate>
                        <div class="form-grid">
                            <div class="form-control">
                                <label class="label" for="enq-name">Full name *</label>
                                <input class="form-control" id="enq-name" name="name" required>
                                <div class="form-error" data-error-for="name"></div>
                            </div>
                            <div class="form-control">
                                <label class="label" for="enq-email">Email *</label>
                                <input class="form-control" id="enq-email" name="email" type="email" required>
                                <div class="form-error" data-error-for="email"></div>
                            </div>
                            <div class="form-control">
                                <label class="label" for="enq-phone">Phone *</label>
                                <input class="form-control" id="enq-phone" name="phone" required>
                                <div class="form-error" data-error-for="phone"></div>
                            </div>
                            <div class="form-control">
                                <label class="label" for="enq-date">Event date</label>
                                <input class="form-control" id="enq-date" name="event_date" type="date">
                                <div class="form-error" data-error-for="event_date"></div>
                            </div>
                            <div class="form-control">
                                <label class="label" for="enq-type">Event type</label>
                                <input class="form-control" id="enq-type" name="event_type" placeholder="Wedding, Reception, Engagement">
                                <div class="form-error" data-error-for="event_type"></div>
                            </div>
                            <div class="form-control">
                                <label class="label" for="enq-venue">Venue / location</label>
                                <input class="form-control" id="enq-venue" name="venue_address" placeholder="City or venue name">
                                <div class="form-error" data-error-for="venue_address"></div>
                            </div>
                        </div>
                        <div class="form-control mt-4">
                            <label class="label" for="enq-notes">Tell us more</label>
                            <textarea class="form-control" id="enq-notes" name="notes" rows="4" placeholder="Guest count, must-haves, budget range..."></textarea>
                            <div class="form-error" data-error-for="notes"></div>
                        </div>

                        <div class="mt-6">
                            <label class="label">Select services or packages *</label>
                            <div class="form-error mb-2" data-error-for="items"></div>
                            <input type="hidden" name="items" value="">
                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <div class="text-sm font-semibold mb-2">Services</div>
                                    <div class="card" style="max-height:260px;overflow:auto" data-service-options>
                                        <?= loader_skeleton(1) ?>
                                    </div>
                                </div>
                                <div>
                                    <div class="text-sm font-semibold mb-2">Packages</div>
                                    <div class="card" style="max-height:260px;overflow:auto" data-package-options>
                                        <?= loader_skeleton(1) ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 mt-6">
                            <button type="reset" class="btn btn-ghost">Clear</button>
                            <button type="submit" class="btn btn-primary" data-submit>
                                <i data-lucide="send"></i> Submit Enquiry
                            </button>
                        </div>
                    </form>
                    <div class="mt-6" data-success hidden></div>
                </div>
            </div>
            <aside>
                <div class="card card-pad">
                    <h3 class="font-semibold mb-3">Talk to our team</h3>
                    <div class="detail-list">
                        <div class="detail-row"><span>Phone</span><strong data-contact-phone>&mdash;</strong></div>
                        <div class="detail-row"><span>Email</span><strong data-contact-email>&mdash;</strong></div>
                        <div class="detail-row"><span>Office</span><strong data-contact-address>&mdash;</strong></div>
                    </div>
                    <p class="text-xs text-muted mt-4">Submitting an enquiry is free and does not commit you to a booking.</p>
                </div>
            </aside>
        </div>
    </div>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>


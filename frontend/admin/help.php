<?php

declare(strict_types=1);

$pageTitle = 'Help Center';
$pageDesc = 'A plain-language guide to running your business with VivaahFlow';
$activeNav = 'help';
$pageScript = 'js/pages/help.js';
$extraCss = ['css/help.css'];

$modules = [
    'getting-started' => [
        'title' => 'Getting Started',
        'icon' => 'rocket',
        'purpose' => 'VivaahFlow is the business system that runs your wedding planning company. It stores your customers, services, quotes, bookings, payments and reviews in one place so nothing gets missed.',
        'when' => 'Use this section the first time you open the admin panel and whenever you want a quick reminder of the basics.',
        'steps' => [
            'Open the login link your administrator gave you (or go to the Admin Panel link on your website).',
            'Type your email address and password.',
            'You will land on the Dashboard, your daily summary screen.',
            'Use the menu on the left to move between customers, enquiries, services, bookings, payments and more.',
        ],
        'next' => 'Take a fresh customer enquiry from start to finish once, and the whole system will make sense.',
    ],
    'dashboard' => [
        'title' => 'Dashboard',
        'icon' => 'layout-dashboard',
        'purpose' => 'The Dashboard is the home screen. It shows today\u2019s numbers at a glance: how many enquiries came in, active bookings, money collected, and upcoming work.',
        'when' => 'Check it every morning to see what needs your attention today.',
        'steps' => [
            'Look at the summary cards for enquiries, quotations, bookings and revenue.',
            'Review the "needs attention" lists, such as fresh enquiries waiting for a follow-up.',
            'Click any card or list item to jump straight to that record.',
        ],
        'next' => 'Start your day with the enquiries that still need a reply before anything else.',
    ],
    'customers' => [
        'title' => 'Customers',
        'icon' => 'users',
        'purpose' => 'Customers are the people who ask you about weddings. Every enquiry and booking is linked to a customer record that stores their contact details, wedding date and preference.',
        'when' => 'Use it to find a customer, update their details, or see everything they have done with you.',
        'steps' => [
            'Search by name, email or phone number.',
            'Open a customer to see their full profile.',
            'Edit their phone, email or wedding date if they tell you it changed.',
            'Check the customer\u2019s enquiry, quotation and booking history from their profile.',
        ],
        'next' => 'If a customer has no details, ask for them at the first call and update the record.',
    ],
    'enquiries' => [
        'title' => 'Enquiries',
        'icon' => 'messages-square',
        'purpose' => 'An enquiry is a request from a customer. It arrives from the Contact form on your website, from the customer portal, or when you add one yourself.',
        'when' => 'Every new enquiry is your first chance to win the booking. Work through them the same day they arrive.',
        'steps' => [
            'Open the Enquiries list and pick the newest items first (marked "New").',
            'Open an enquiry to read what the customer asked for.',
            'Contact the customer, then update the status so everyone knows where things stand.',
            'When the customer is serious, convert the enquiry into a quotation.',
        ],
        'statuses' => [
            'New' => 'Just arrived, nobody has contacted them yet.',
            'Contacted' => 'Someone has reached out to the customer.',
            'Quotation required' => 'You are preparing a price for them.',
            'Quotation sent' => 'A quote has been shared with the customer.',
            'Converted' => 'The customer accepted and it became a booking.',
            'Closed' => 'No longer active, for any reason.',
        ],
        'next' => 'Reply quickly, keep the status accurate, and convert accepted customers into bookings.',
    ],
    'leads' => [
        'title' => 'Leads and Follow-ups',
        'icon' => 'target',
        'purpose' => 'Leads are potential customers who are not quite ready to book. Follow-ups remind you when to call back, so promising customers do not go cold.',
        'when' => 'Use it when an enquiry is still "in the pipeline" and the customer asked you to call them later.',
        'steps' => [
            'Open the Leads list to see everyone being followed up.',
            'Read the notes to remember where the conversation stands.',
            'When you have contacted the customer again, log the follow-up and set the next date.',
            'Mark a follow-up Done when the customer is ready, or Missed if you could not reach them.',
        ],
        'statuses' => [
            'New' => 'Just added to the follow-up list.',
            'Contacted' => 'You have reached them once.',
            'Active follow-up' => 'You are chasing them on a schedule.',
            'Converted' => 'They became a booking.',
            'Lost' => 'They chose another supplier.',
        ],
        'next' => 'Never let a follow-up date pass without a call. The list is your promise tracker.',
    ],
    'categories' => [
        'title' => 'Categories',
        'icon' => 'tags',
        'purpose' => 'Categories group your services on the website, for example Photography, Catering or Venue. They help customers find what they want.',
        'when' => 'Use it when you add a new type of service, or want to rename/reorder how services are grouped.',
        'steps' => [
            'Go to Categories.',
            'Add a category with a clear name (e.g. "Florists").',
            'Optionally write a short description customers will see.',
            'Assign your services to the correct category afterwards.',
        ],
        'statuses' => [
            'Active' => 'Shown on the website.',
            'Inactive' => 'Hidden from the website.',
        ],
        'next' => 'Keep categories few and clear. Too many small categories confuse customers.',
    ],
    'services' => [
        'title' => 'Services',
        'icon' => 'sparkles',
        'purpose' => 'A service is something you sell, such as Bridal Makeup, a DJ, or Venue Decoration. Each service has a price, duration, description and photos.',
        'when' => 'Use it whenever you add something new to sell, or update pricing/details of an existing service.',
        'steps' => [
            'Open Services and press Add Service.',
            'Fill in the name, category, description and price.',
            'Set the duration (how many hours) if it is time-based.',
            'Add a cover photo and detailed photos in the gallery.',
            'Save, then check how it looks on the website.',
        ],
        'statuses' => [
            'Active' => 'Visible and bookable on the website.',
            'Inactive' => 'Hidden, but nothing is deleted.',
        ],
        'next' => 'After creating a service, add its gallery photos and make sure it sits in the right category.',
    ],
    'service-gallery' => [
        'title' => 'Service Gallery',
        'icon' => 'images',
        'purpose' => 'Each service has its own photo gallery. These are the real pictures customers see on the service page.',
        'when' => 'Use it right after creating a service, and whenever you have better photos from a real wedding.',
        'steps' => [
            'Open the service and press the gallery button.',
            'Upload photos of the service at real weddings, if possible.',
            'Remove poor-quality or duplicate photos.',
        ],
        'next' => 'Real photos sell weddings better than stock images. Keep galleries fresh.',
    ],
    'packages' => [
        'title' => 'Packages',
        'icon' => 'package',
        'purpose' => 'A package combines several services into one bundle with a single price, and often a discount. Example: a "Grand Wedding Package" with decor, photography and catering.',
        'when' => 'Use it when you want to bundle services and give customers a simple, appealing price.',
        'steps' => [
            'Open Packages and press Add Package.',
            'Give it a name and a description.',
            'Choose the services included and how many of each.',
            'Set a discount (percentage or fixed amount) to reward bundling.',
            'The system calculates the final package price automatically.',
        ],
        'next' => 'Feature your most popular packages on the website\u2019s Packages page.',
    ],
    'offers' => [
        'title' => 'Offers',
        'icon' => 'badge-percent',
        'purpose' => 'Offers are time-limited promotions, like "10% off catering this month." They appear on the website to encourage bookings.',
        'when' => 'Use it for seasonal discounts, festival offers, or to fill gaps in the calendar.',
        'steps' => [
            'Open Offers and press Add Offer.',
            'Name the offer and decide the discount (percentage or fixed).',
            'Choose which services or packages it applies to.',
            'Set an end date so it stops automatically.',
        ],
        'statuses' => [
            'Active' => 'Shown to customers.',
            'Inactive' => 'Hidden.',
            'Expired' => 'Past its end date and hidden automatically.',
        ],
        'next' => 'Remind customers of active offers in your calls and messages.',
    ],
    'quotations' => [
        'title' => 'Quotations',
        'icon' => 'file-text',
        'purpose' => 'A quotation is the formal price list you send a customer. It lists each service, the quantity, the price, discounts and the final total.',
        'when' => 'Create one when a customer asks "how much would this all cost?"',
        'steps' => [
            'Open the enquiry and press Create Quotation.',
            'Add the services or packages the customer wants.',
            'Adjust quantities and any discounts.',
            'Send it to the customer. They can accept or decline it from their portal.',
            'Watch the status to know who has accepted.',
        ],
        'statuses' => [
            'Draft' => 'Being prepared, not yet sent.',
            'Sent' => 'Shared with the customer, waiting for their decision.',
            'Accepted' => 'The customer said yes. Turn it into a booking.',
            'Rejected' => 'The customer said no. Ask why and keep the relationship warm.',
            'Expired' => 'Past its validity date, so no longer usable.',
        ],
        'next' => 'When a quotation is accepted, immediately convert it into a booking.',
    ],
    'bookings' => [
        'title' => 'Bookings',
        'icon' => 'calendar-check',
        'purpose' => 'A booking is a confirmed contract. It records the customer, services, date, venue, price and payment plan.',
        'when' => 'Create it once the customer accepts a quotation, or as soon as you take a confirmed order.',
        'steps' => [
            'From an accepted quotation, press Confirm Booking.',
            'Enter the event date, venue and any notes.',
            'Confirm the booking so it is locked in.',
            'Schedule events and assign staff (see Events).',
            'Record advances and balance payments against it.',
        ],
        'statuses' => [
            'Pending' => 'Waiting to be confirmed.',
            'Confirmed' => 'Locked in and on the calendar.',
            'Scheduled' => 'Events and staff have been planned.',
            'In progress' => 'The wedding is happening now.',
            'Completed' => 'The wedding is over.',
            'Cancelled' => 'The booking will not happen.',
        ],
        'next' => 'For every confirmed booking, schedule its events and assign staff as early as possible.',
    ],
    'events' => [
        'title' => 'Events and Scheduling',
        'icon' => 'calendar-days',
        'purpose' => 'An event is a part of the wedding day, such as the Ceremony, Reception or Mehndi. Each booking can have several events with dates and times.',
        'when' => 'Use it to plan the day\u2019s timeline so every part has a time and the right people assigned.',
        'steps' => [
            'Open the booking and add an event (type, date, time, venue).',
            'Set the status as the day approaches.',
            'Open the Events schedule to see everything happening on a given date.',
        ],
        'statuses' => [
            'Scheduled' => 'Planned on the calendar.',
            'In progress' => 'Happening now.',
            'Completed' => 'Finished.',
            'Cancelled' => 'Removed from the plan.',
        ],
        'next' => 'Review the weekly event schedule ahead of each weekend and fill any gaps.',
    ],
    'staff' => [
        'title' => 'Staff',
        'icon' => 'user-cog',
        'purpose' => 'Staff are the people who do the work: photographers, makeup artists, coordinators, and helpers. Each has a name and a role.',
        'when' => 'Add staff members once, then you can assign them to events again and again.',
        'steps' => [
            'Open Staff and press Add Staff.',
            'Enter their name, role (e.g. Photographer) and contact details.',
            'Keep their information current so you can always reach them.',
        ],
        'statuses' => [
            'Active' => 'Available to assign.',
            'Inactive' => 'No longer working with you, hidden from assignments.',
        ],
        'next' => 'After adding staff, assign them to the right events so everyone knows who is doing what.',
    ],
    'staff-assignment' => [
        'title' => 'Staff Assignment',
        'icon' => 'user-check',
        'purpose' => 'Assigning staff means attaching a staff member to a specific event, for example "Photographer on the Reception event."',
        'when' => 'Do it as soon as a booking is confirmed, so no one is double-booked.',
        'steps' => [
            'Open the booking\u2019s event.',
            'Press Add Staff and pick the person from the list.',
            'Remove or replace an assignment if plans change.',
        ],
        'next' => 'Check the event schedule for any staff member appearing twice at the same time.',
    ],
    'payments' => [
        'title' => 'Payments',
        'icon' => 'wallet',
        'purpose' => 'Payments records every rupee the customer pays: advances, instalments and final settlement.',
        'when' => 'Record a payment the moment you receive it, so balances are always correct.',
        'steps' => [
            'Open the booking and press Record Payment.',
            'Enter the amount, date and how it was paid (bank transfer, cash, card).',
            'The system updates the remaining balance automatically.',
            'If a payment was recorded by mistake, reverse it.',
        ],
        'next' => 'Match what you have recorded in the system against your bank statement at least weekly.',
    ],
    'invoices' => [
        'title' => 'Invoices',
        'icon' => 'receipt',
        'purpose' => 'An invoice is the official bill you give the customer. It shows what was booked, the total, what has been paid and what is still due.',
        'when' => 'Generate one at milestones: an advance invoice, and a final invoice after the wedding.',
        'steps' => [
            'Open Invoices and create one for the booking.',
            'Issue it to the customer so they can view it online.',
            'As payments arrive, the invoice updates automatically.',
            'When fully paid, the invoice shows as Paid.',
        ],
        'statuses' => [
            'Draft' => 'Being prepared.',
            'Issued' => 'Sent to the customer.',
            'Paid' => 'Everything has been settled.',
            'Overdue' => 'Payment was expected and has not arrived.',
            'Cancelled' => 'No longer valid.',
        ],
        'next' => 'A few days before the wedding, make sure every booking has an invoice and a clear balance.',
    ],
    'reviews' => [
        'title' => 'Reviews',
        'icon' => 'star',
        'purpose' => 'Reviews are what customers write about your work after the wedding. Only approved reviews appear on your website.',
        'when' => 'Check the list regularly and approve genuine reviews quickly so new customers see them.',
        'steps' => [
            'Open the Reviews list and read new reviews.',
            'Approve the ones that are genuine and appropriate.',
            'Reply where a thank-you is appropriate.',
            'Reject reviews that are rude, fake or contain personal details.',
        ],
        'statuses' => [
            'Pending' => 'Waiting for your approval.',
            'Approved' => 'Visible on the website.',
            'Rejected' => 'Hidden from the website.',
        ],
        'next' => 'If a customer says they cannot see their review, first check whether it is approved and visible.',
    ],
    'reports' => [
        'title' => 'Reports',
        'icon' => 'chart-column',
        'purpose' => 'Reports turn your records into charts and numbers: revenue over time, how many bookings by status, which services sell best, and where your leads come from.',
        'when' => 'Use them for monthly business reviews, or to decide what to promote next.',
        'steps' => [
            'Open Reports.',
            'Pick a date range (for example, this quarter).',
            'Read the summary cards and charts.',
            'Use the "Top services" list to see what customers buy most.',
        ],
        'next' => 'Promote your best-selling services and investigate months where enquiries dropped.',
    ],
    'notifications' => [
        'title' => 'Notifications',
        'icon' => 'bell',
        'purpose' => 'Notifications are the system\u2019s reminders to you: new enquiries, accepted quotations, new reviews and similar events. The bell icon shows how many are unread.',
        'when' => 'Check the bell at the top of every page and clear important items.',
        'steps' => [
            'Click the bell to open your notifications.',
            'Read each one, then mark it read.',
            'Use "mark all as read" when you have reviewed the list.',
        ],
        'next' => 'Treat notifications as your to-do list and act on the important ones first.',
    ],
    'settings' => [
        'title' => 'Settings',
        'icon' => 'settings',
        'purpose' => 'Settings control how your business looks across the website: company name, contact details, currency symbol, logo, and whether new customers may register.',
        'when' => 'Use it when your business details change, branding is updated, or registration needs to be opened or closed.',
        'steps' => [
            'Open Settings.',
            'Update the company name, tagline and contact details.',
            'Change the currency symbol if needed.',
            'Turn customer registration on or off.',
            'Upload your logo and refresh the website.',
        ],
        'next' => 'After changing company details, open the website to confirm everything updated.',
    ],
    'users' => [
        'title' => 'Users and Roles',
        'icon' => 'shield',
        'purpose' => 'Users are the people who can log in to the admin panel. Each user has a role: Administrator (full control), Manager (nearly everything), or Staff (limited access).',
        'when' => 'Add users when someone joins the business, and remove or deactivate them when they leave.',
        'steps' => [
            'Open Users and press Add User.',
            'Enter name, email and password.',
            'Choose the role that matches their job.',
        ],
        'next' => 'Give people the smallest role they need. Most daily work only needs Staff or Manager access.',
    ],
    'activity' => [
        'title' => 'Activity Log',
        'icon' => 'history',
        'purpose' => 'The activity log records every important action in the system: who changed what and when. It is the audit trail of your business.',
        'when' => 'Use it when you need to find out what happened, or who made a particular change.',
        'steps' => [
            'Open Activity Log.',
            'Search by person, action or details.',
            'Look up the date and time of a change when something is unclear.',
        ],
        'next' => 'If a customer dispute arises about what was agreed, the activity log shows exactly what happened and when.',
    ],
    'profile' => [
        'title' => 'Profile',
        'icon' => 'user-round',
        'purpose' => 'Your profile holds your own name, email and password.',
        'when' => 'Use it to update your own information or change your password.',
        'steps' => [
            'Open Profile from your name in the top-right corner.',
            'Update your name or email.',
            'Change your password if you think someone may know it.',
        ],
        'next' => 'Change your password if it was ever shared, and keep it private.',
    ],
];

$workflow = [
    'step 1' => 'Customer enquiry — a new enquiry arrives from the website or portal.',
    'step 2' => 'Follow-up — you contact the customer and discuss their needs.',
    'step 3' => 'Quotation — you prepare and send a quotation. The customer can accept or decline it online.',
    'step 4' => 'Customer accepts — the quotation status becomes Accepted.',
    'step 5' => 'Booking — you turn the accepted quotation into a confirmed booking.',
    'step 6' => 'Staff assignment — you assign staff to the events that need people.',
    'step 7' => 'Event scheduling — you set dates and times for each part of the wedding.',
    'step 8' => 'Payment — you record the advance, instalments and final payment.',
    'step 9' => 'Completion — the wedding takes place and the booking is completed.',
    'step 10' => 'Review — you invite the customer (or they write) a review, and you approve it online.',
];

$commonTasks = [
    'add-service' => ['title' => 'How to add a service', 'steps' => ['Go to Services → Add Service.', 'Fill in the name, category, and description.', 'Enter the price and duration.', 'Save, then add gallery photos.']],
    'change-service-image' => ['title' => 'How to change a service image', 'steps' => ['Open the service.', 'Press the gallery/upload button.', 'Upload the new photo and remove the old one.']],
    'create-package' => ['title' => 'How to create a package', 'steps' => ['Go to Packages → Add Package.', 'Name it and write a description.', 'Add the services it includes and quantities.', 'Set the discount and save.']],
    'create-offer' => ['title' => 'How to create an offer', 'steps' => ['Go to Offers → Add Offer.', 'Name the offer and set the discount.', 'Choose what it applies to.', 'Set an end date and save.']],
    'create-quotation' => ['title' => 'How to create a quotation', 'steps' => ['Open the enquiry.', 'Press Create Quotation.', 'Add services or packages, quantities and discounts.', 'Send it to the customer.']],
    'confirm-booking' => ['title' => 'How to confirm a booking', 'steps' => ['Open the accepted quotation.', 'Press Confirm Booking.', 'Enter the event date and venue.', 'Confirm the booking.']],
    'record-payment' => ['title' => 'How to record a payment', 'steps' => ['Open the booking.', 'Press Record Payment.', 'Enter the amount, date and method.', 'Save. The balance updates automatically.']],
    'assign-staff' => ['title' => 'How to assign staff', 'steps' => ['Open the booking\u2019s event.', 'Press Add Staff.', 'Choose the staff member and save.']],
    'create-event' => ['title' => 'How to create an event', 'steps' => ['Open the booking.', 'Press Add Event.', 'Choose the type, date, time and venue.', 'Save.']],
    'approve-review' => ['title' => 'How to approve a review', 'steps' => ['Open the Reviews list.', 'Read the pending review.', 'Press Approve to publish it, or Reject to hide it.']],
    'view-reports' => ['title' => 'How to view reports', 'steps' => ['Open Reports.', 'Choose a date range.', 'Read the charts and summary cards.']],
    'company-info' => ['title' => 'How to change company information', 'steps' => ['Go to Settings.', 'Edit the company name, tagline and contact details.', 'Save and check the website.']],
    'website-logo' => ['title' => 'How to change the website logo', 'steps' => ['Go to Settings.', 'Upload the new logo in the branding section.', 'Save and refresh the website.']],
];

$statusReference = [
    'New' => 'A record that has just arrived and has not been worked on yet.',
    'Contacted' => 'Someone from your team has reached the customer.',
    'Quotation required / pending' => 'A quote is being prepared.',
    'Quotation sent' => 'A quote has been shared with the customer.',
    'Converted' => 'The customer said yes and it moved to a booking.',
    'Closed' => 'Finished or no longer active.',
    'Active / Inactive' => 'Visible and working, or hidden for now (nothing is deleted).',
    'Qualified / Unqualified' => 'A lead that has promise, or one that is not right for you.',
    'Draft' => 'Still being prepared, not official yet.',
    'Accepted / Rejected' => 'The customer said yes, or no.',
    'Expired' => 'Past its valid date and no longer usable.',
    'Pending / Confirmed' => 'Waiting to be locked in, or locked in.',
    'Scheduled' => 'Planned with dates and times.',
    'In progress' => 'Happening right now.',
    'Completed' => 'Finished successfully.',
    'Cancelled' => 'Will not happen.',
    'Paid / Partial / Unpaid / Overdue' => 'Settled, partly settled, nothing paid yet, or expected payment has not arrived.',
    'Recorded / Reversed' => 'A payment logged, or one that was undone.',
    'Issued' => 'A document that has been sent to the customer.',
    'Approved / Pending / Rejected (reviews)' => 'Published, waiting for you, or hidden.',
];

$commonProblems = [
    'customer-cannot-see-review' => ['q' => 'A customer says they cannot see their review on the website.', 'a' => 'First check whether the review is approved and visible. Only approved reviews are published. If it is still "Pending," approve it.'],
    'website-not-showing-new-service' => ['q' => 'A new service or package is not showing on the website.', 'a' => 'Check that the item is marked "Active" and not "Inactive." Inactive items are hidden from customers. Also set a category for services so they appear in the right place.'],
    'balance-looking-wrong' => ['q' => 'A customer\u2019s remaining balance looks wrong.', 'a' => 'Open the booking and check its Payments list. Confirm every payment was recorded and that none were reversed by mistake.'],
    'quotation-still-sent' => ['q' => 'A customer says they accepted a quotation but it still shows "Sent."', 'a' => 'Ask them to reopen the quotation and confirm the accept button. If the customer does not use the portal, you can update the status yourself from the quotation record.'],
    'quotation-cannot-be-accepted' => ['q' => 'A quotation cannot be accepted.', 'a' => 'Only a sent quotation before its validity date can be accepted. Move a draft to sent first; an expired, rejected, or already accepted quotation cannot be accepted.'],
    'cant-login' => ['q' => 'An employee cannot log in.', 'a' => 'Check the Users list that their account is active and that they were given a role. Reset the password if they have forgotten it. Locked accounts wait 15 minutes before the next attempt is allowed.'],
    'offer-expired' => ['q' => 'An offer disappeared from the website.', 'a' => 'It has probably passed its end date. Open the offer to see its validity and either extend the date or leave it hidden.'],
    'double-booked-staff' => ['q' => 'The same staff member is assigned to two events at the same time.', 'a' => 'Open both events on the schedule and reassign one of them to a different person, or arrange replacement staff.'],
];

require __DIR__ . '/partials/header.php';
?>
<div class="page-head">
    <div>
        <h1 class="page-title">Help Center</h1>
        <p class="page-desc">Everything you need to run your business with VivaahFlow, in plain language.</p>
    </div>
</div>

<div class="help-search">
    <div class="input-group search-box" style="max-width:520px">
        <span class="input-icon"><i data-lucide="search"></i></span>
        <input type="search" class="form-control" placeholder="Search help, e.g. record a payment, approve review..." data-help-search>
    </div>
    <span class="text-muted text-sm" data-help-results></span>
</div>

<div class="help-index">
    <span class="help-index-label">Jump to:</span>
    <?php foreach ($modules as $id => $module): ?>
        <a class="btn btn-ghost btn-sm" href="#help-<?= e($id) ?>"><i data-lucide="<?= e($module['icon']) ?>"></i><?= e($module['title']) ?></a>
    <?php endforeach; ?>
    <a class="btn btn-ghost btn-sm" href="#help-workflow"><i data-lucide="workflow"></i>Workflow</a>
    <a class="btn btn-ghost btn-sm" href="#help-tasks"><i data-lucide="list-checks"></i>Common Tasks</a>
    <a class="btn btn-ghost btn-sm" href="#help-statuses"><i data-lucide="book-open"></i>Status Reference</a>
    <a class="btn btn-ghost btn-sm" href="#help-problems"><i data-lucide="circle-help"></i>Common Problems</a>
</div>

<?php foreach ($modules as $id => $module): ?>
    <section class="card help-card" id="help-<?= e($id) ?>">
        <div class="card-head">
            <div class="help-card-title">
                <span class="help-icon"><i data-lucide="<?= e($module['icon']) ?>"></i></span>
                <div>
                    <h2 class="card-title"><?= e($module['title']) ?></h2>
                    <div class="card-subtitle"><?= e($module['purpose']) ?></div>
                </div>
            </div>
        </div>
        <div class="card-pad">
            <dl class="help-list">
                <div class="help-item" data-help-item>
                    <dt class="help-q" data-help-q><span class="help-q-icon"><i data-lucide="circle-help"></i></span>What is this for?</dt>
                    <dd class="help-a"><?= e($module['purpose']) ?></dd>
                </div>
                <div class="help-item" data-help-item>
                    <dt class="help-q" data-help-q><span class="help-q-icon"><i data-lucide="clock"></i></span>When should I use it?</dt>
                    <dd class="help-a"><?= e($module['when']) ?></dd>
                </div>
                <div class="help-item" data-help-item>
                    <dt class="help-q" data-help-q><span class="help-q-icon"><i data-lucide="list-ordered"></i></span>How do I use it?</dt>
                    <dd class="help-a">
                        <ol class="help-steps">
                            <?php foreach ($module['steps'] as $step): ?>
                                <li><?= e($step) ?></li>
                            <?php endforeach; ?>
                        </ol>
                    </dd>
                </div>
                <?php if (!empty($module['statuses'])): ?>
                    <div class="help-item" data-help-item>
                        <dt class="help-q" data-help-q><span class="help-q-icon"><i data-lucide="badge-info"></i></span>What do its statuses mean?</dt>
                        <dd class="help-a">
                            <table class="table help-table">
                                <tbody>
                                    <?php foreach ($module['statuses'] as $label => $meaning): ?>
                                        <tr><th class="help-table-label"><?= e($label) ?></th><td><?= e($meaning) ?></td></tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </dd>
                    </div>
                <?php endif; ?>
                <div class="help-item" data-help-item>
                    <dt class="help-q" data-help-q><span class="help-q-icon"><i data-lucide="arrow-right"></i></span>What should I do next?</dt>
                    <dd class="help-a"><?= e($module['next']) ?></dd>
                </div>
            </dl>
        </div>
    </section>
<?php endforeach; ?>

<section class="card help-card" id="help-workflow">
    <div class="card-head">
        <div class="help-card-title">
            <span class="help-icon"><i data-lucide="workflow"></i></span>
            <div>
                <h2 class="card-title">The full journey: from enquiry to review</h2>
                <div class="card-subtitle">How a customer moves through your business in ten steps.</div>
            </div>
        </div>
    </div>
    <div class="card-pad">
        <ol class="help-steps help-flow">
            <?php foreach ($workflow as $step): ?>
                <li><?= e($step) ?></li>
            <?php endforeach; ?>
        </ol>
    </div>
</section>

<section class="card help-card" id="help-tasks">
    <div class="card-head">
        <div class="help-card-title">
            <span class="help-icon"><i data-lucide="list-checks"></i></span>
            <div>
                <h2 class="card-title">Common Tasks</h2>
                <div class="card-subtitle">Quick step-by-step instructions for everyday work.</div>
            </div>
        </div>
    </div>
    <div class="card-pad">
        <div class="grid help-tasks">
            <?php foreach ($commonTasks as $task): ?>
                <div class="help-task" data-help-item>
                    <div class="help-task-head" data-help-q>
                        <span class="help-q-icon"><i data-lucide="circle-check"></i></span>
                        <span class="help-task-title"><?= e($task['title']) ?></span>
                    </div>
                    <div class="help-a">
                        <ol class="help-steps">
                            <?php foreach ($task['steps'] as $step): ?>
                                <li><?= e($step) ?></li>
                            <?php endforeach; ?>
                        </ol>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="card help-card" id="help-statuses">
    <div class="card-head">
        <div class="help-card-title">
            <span class="help-icon"><i data-lucide="book-open"></i></span>
            <div>
                <h2 class="card-title">What does this status mean?</h2>
                <div class="card-subtitle">A plain-language reference for the statuses you will see across the system.</div>
            </div>
        </div>
    </div>
    <div class="card-pad">
        <table class="table help-table">
            <tbody>
                <?php foreach ($statusReference as $label => $meaning): ?>
                    <tr><th class="help-table-label"><?= e($label) ?></th><td><?= e($meaning) ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="card help-card" id="help-problems">
    <div class="card-head">
        <div class="help-card-title">
            <span class="help-icon"><i data-lucide="circle-help"></i></span>
            <div>
                <h2 class="card-title">Common Problems</h2>
                <div class="card-subtitle">Typical situations and what to check first.</div>
            </div>
        </div>
    </div>
    <div class="card-pad">
        <dl class="help-list">
            <?php foreach ($commonProblems as $problem): ?>
                <div class="help-item" data-help-item>
                    <dt class="help-q" data-help-q>
                        <span class="help-q-icon"><i data-lucide="circle-alert"></i></span>
                        <?= e($problem['q']) ?>
                    </dt>
                    <dd class="help-a"><?= e($problem['a']) ?></dd>
                </div>
            <?php endforeach; ?>
        </dl>
    </div>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>

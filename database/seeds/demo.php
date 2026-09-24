<?php

declare(strict_types=1);

use App\Database\Connection;
use App\Services\InstallationService;

/**
 * VivaahFlow demo seeder — idempotent.
 *
 * Returns a closure invoked by InstallationService::seedDemo().
 * Running twice never duplicates rows: every insert is guarded by a
 * lookup on its natural unique key (slug / email / reference_no / links).
 */
return function (InstallationService $installer): array {
    $counts = ['skipped' => 0, 'inserted' => 0];

    $one = fn(string $sql, array $p = []) => Connection::fetchOne($sql, $p);
    $col = fn(string $sql, array $p = []) => Connection::fetchColumn($sql, $p);

    $ensureRole = function (string $slug, string $name, string $desc) use (&$counts, $one): int {
        $row = $one('SELECT id FROM roles WHERE slug = ?', [$slug]);
        if ($row) {
            $counts['skipped']++;
            return (int)$row['id'];
        }
        Connection::run('INSERT INTO roles (name, slug, description) VALUES (?, ?, ?)', [$name, $slug, $desc]);
        $counts['inserted']++;
        return (int)Connection::pdo()->lastInsertId();
    };

    $ensureUser = function (string $email, string $name, string $roleSlug, string $password, ?string $phone = null) use (&$counts, $one): int {
        $email = strtolower(trim($email));
        $row = $one('SELECT id FROM users WHERE LOWER(email) = ?', [$email]);
        if ($row) {
            $counts['skipped']++;
            return (int)$row['id'];
        }
        $role = $one('SELECT id FROM roles WHERE slug = ?', [$roleSlug]);
        if (!$role) {
            throw new RuntimeException('Role missing: ' . $roleSlug);
        }
        Connection::run(
            'INSERT INTO users (role_id, name, email, phone, password_hash, status) VALUES (?, ?, ?, ?, ?, ?)',
            [(int)$role['id'], $name, $email, $phone, password_hash($password, PASSWORD_DEFAULT), 'active']
        );
        $counts['inserted']++;
        return (int)Connection::pdo()->lastInsertId();
    };

    $ensureCustomer = function (?int $userId, string $name, string $email, ?string $phone = null, array $extra = []) use (&$counts, $one): int {
        $email = strtolower(trim($email));
        $row = $one('SELECT id FROM customers WHERE LOWER(email) = ?', [$email]);
        if ($row) {
            $counts['skipped']++;
            return (int)$row['id'];
        }
        Connection::run(
            'INSERT INTO customers (user_id, name, email, phone, wedding_date, event_type, address, city, source, status, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $userId, $name, $email, $phone,
                $extra['wedding_date'] ?? null,
                $extra['event_type'] ?? null,
                $extra['address'] ?? null,
                $extra['city'] ?? null,
                $extra['source'] ?? 'demo',
                'active',
                $extra['notes'] ?? null,
            ]
        );
        $counts['inserted']++;
        return (int)Connection::pdo()->lastInsertId();
    };

    $ensureCategory = function (string $slug, string $name, string $desc, int $order) use (&$counts, $one): int {
        $row = $one('SELECT id FROM service_categories WHERE slug = ?', [$slug]);
        if ($row) {
            $counts['skipped']++;
            return (int)$row['id'];
        }
        Connection::run(
            'INSERT INTO service_categories (name, slug, description, display_order, status) VALUES (?, ?, ?, ?, ?)',
            [$name, $slug, $desc, $order, 'active']
        );
        $counts['inserted']++;
        return (int)Connection::pdo()->lastInsertId();
    };

    $ensureService = function (array $s) use (&$counts, $one): int {
        $row = $one('SELECT id FROM services WHERE slug = ?', [$s['slug']]);
        $image = $s['image'];
        if ($image !== '' && !is_file(ROOT_PATH . '/' . ltrim($image, '/'))) {
            $image = '';
        }
        if ($row) {
            $counts['skipped']++;
            return (int)$row['id'];
        }
        Connection::run(
            'INSERT INTO services (category_id, name, slug, short_description, description, duration_minutes, starting_price, image, is_featured, status, display_order)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $s['category_id'], $s['name'], $s['slug'], $s['short'], $s['desc'],
                $s['duration'], $s['price'], $image !== '' ? $image : null,
                $s['featured'] ? 1 : 0, 'active', $s['order'],
            ]
        );
        $counts['inserted']++;
        return (int)Connection::pdo()->lastInsertId();
    };

    return Connection::transaction(function () use (
        $installer, $counts, $one, $col,
        $ensureRole, $ensureUser, $ensureCustomer, $ensureCategory, $ensureService
    ): array {
        $counts = ['skipped' => 0, 'inserted' => 0];

        // --- Roles ---
        $installer->ensureRole('admin', 'Administrator', 'Full access to every module and action.');
        $installer->ensureRole('manager', 'Manager', 'All operations except destructive and administrative areas.');
        $installer->ensureRole('staff', 'Staff', 'Operational subset: bookings, events, assignments and follow-ups.');
        $installer->ensureRole('customer', 'Customer', 'Portal-only self-service access.');

        // --- Users (working demo accounts, bcrypt-hashed) ---
        $adminId = $ensureUser('admin@example.com', 'VivaahFlow Admin', 'admin', 'Admin@123', '+91 98765 00001');
        $managerId = $ensureUser('manager@example.com', 'Meera Manager', 'manager', 'Manager@123', '+91 98765 00002');
        $staffUserId = $ensureUser('staff@example.com', 'Arjun Staff', 'staff', 'Staff@123', '+91 98765 00003');
        $customerUserId = $ensureUser('customer@example.com', 'Aarav & Diya', 'customer', 'Customer@123', '+91 98765 00004');

        // --- Customers ---
        $customerId = $ensureCustomer($customerUserId, 'Aarav & Diya', 'customer@example.com', '+91 98765 00004', [
            'wedding_date' => '2026-12-12', 'event_type' => 'Wedding',
            'address' => '14 Rose Villa, Juhu', 'city' => 'Mumbai', 'source' => 'self_registration',
            'notes' => 'Demo customer account.',
        ]);
        $priyaId = $ensureCustomer(null, 'Priya Sharma', 'priya.sharma@example.com', '+91 98765 00005', [
            'wedding_date' => '2027-02-14', 'event_type' => 'Wedding',
            'address' => 'B-42 Lakeview Apartments', 'city' => 'Pune', 'source' => 'website',
        ]);
        $rahulId = $ensureCustomer(null, 'Rahul Verma', 'rahul.verma@example.com', '+91 98765 00006', [
            'wedding_date' => '2027-04-20', 'event_type' => 'Reception',
            'address' => '7 Hillcrest Colony', 'city' => 'Jaipur', 'source' => 'referral',
        ]);

        // --- Categories ---
        $catPhoto = $ensureCategory('photography', 'Photography', 'Candid, traditional and cinematic coverage.', 1);
        $catVenue = $ensureCategory('venues', 'Venues', 'Banquet halls, lawns and palatial settings.', 2);
        $catCatering = $ensureCategory('catering', 'Catering', 'Curated menus and live counters.', 3);
        $catDecor = $ensureCategory('decor-florals', 'Decor & Florals', 'Mandaps, stages and tablescapes.', 4);
        $catMakeup = $ensureCategory('makeup-styling', 'Makeup & Styling', 'Bridal artistry and family styling.', 5);
        $catFun = $ensureCategory('entertainment', 'Entertainment', 'Bands, DJs and guest experiences.', 6);

        // --- Services ---
        $services = [
            ['slug' => 'candid-wedding-photography', 'name' => 'Candid Wedding Photography', 'category_id' => $catPhoto, 'short' => 'Unposed moments, captured beautifully.', 'desc' => 'Two candid artists covering haldi to reception with 400+ edited images and a premium album.', 'duration' => 480, 'price' => 45000, 'image' => 'uploads/services/wedding-photography-candid-01.webp', 'featured' => true, 'order' => 1],
            ['slug' => 'cinematic-wedding-film', 'name' => 'Cinematic Wedding Film', 'category_id' => $catPhoto, 'short' => 'A trailer + feature film of your story.', 'desc' => '4K multi-cam feature film, 90-second trailer and drone coverage by a 4-person crew.', 'duration' => 600, 'price' => 65000, 'image' => 'uploads/services/cinematic-wedding-film-01.webp', 'featured' => true, 'order' => 2],
            ['slug' => 'pre-wedding-palace-shoot', 'name' => 'Pre-Wedding Palace Shoot', 'category_id' => $catPhoto, 'short' => 'Golden-hour couple stories.', 'desc' => 'Half-day palace shoot with styling guidance, 120 edited images and same-day reels.', 'duration' => 300, 'price' => 25000, 'image' => 'uploads/services/pre-wedding-sunset-palace-01.webp', 'featured' => false, 'order' => 3],
            ['slug' => 'grand-banquet-hall', 'name' => 'Grand Banquet Hall', 'category_id' => $catVenue, 'short' => '1200-guest pillarless hall.', 'desc' => 'Air-conditioned pillarless hall with bridal suites, valet and in-house power backup.', 'duration' => 720, 'price' => 150000, 'image' => 'uploads/services/banquet-hall-grand-01.webp', 'featured' => true, 'order' => 4],
            ['slug' => 'outdoor-lawn-venue', 'name' => 'Outdoor Lawn Venue', 'category_id' => $catVenue, 'short' => 'Manicured lawns under fairy lights.', 'desc' => '5-acre lawn with stage plinth, guest seating for 800 and monsoon backup dome.', 'duration' => 720, 'price' => 95000, 'image' => 'uploads/services/outdoor-lawn-venue-01.webp', 'featured' => false, 'order' => 5],
            ['slug' => 'royal-mandap-stage-decor', 'name' => 'Royal Mandap & Stage Decor', 'category_id' => $catDecor, 'short' => 'Fresh-flower mandap artistry.', 'desc' => 'Designer mandap with fresh marigold, roses and drapes plus reception stage styling.', 'duration' => 600, 'price' => 85000, 'image' => 'uploads/services/stage-mandap-floral-01.webp', 'featured' => true, 'order' => 6],
            ['slug' => 'luxury-floral-tablescapes', 'name' => 'Luxury Floral Tablescapes', 'category_id' => $catDecor, 'short' => 'Candlelit centrepieces & runners.', 'desc' => 'Candlelit centrepieces, runners and entry florals for up to 40 tables.', 'duration' => 360, 'price' => 32000, 'image' => 'uploads/services/floral-table-luxury-01.webp', 'featured' => false, 'order' => 7],
            ['slug' => 'veg-luxury-buffet', 'name' => 'Veg Luxury Buffet', 'category_id' => $catCatering, 'short' => '120-dish royal vegetarian spread.', 'desc' => '120-dish vegetarian buffet with chaat, continental and dessert islands for 500 guests.', 'duration' => 300, 'price' => 425000, 'image' => 'uploads/services/veg-buffet-luxury-01.webp', 'featured' => false, 'order' => 8],
            ['slug' => 'live-catering-counters', 'name' => 'Live Catering Counters', 'category_id' => $catCatering, 'short' => '6 live counters, chef-attended.', 'desc' => 'Six chef-attended live counters including pasta, dosa, chaat and dessert-on-fire.', 'duration' => 300, 'price' => 45000, 'image' => 'uploads/services/live-catering-counter-01.webp', 'featured' => false, 'order' => 9],
            ['slug' => 'bridal-premium-makeup', 'name' => 'Bridal Premium Makeup', 'category_id' => $catMakeup, 'short' => 'HD bridal look + trials.', 'desc' => 'HD bridal makeup, hairstyling, draping and two trials plus family packages.', 'duration' => 240, 'price' => 18000, 'image' => 'uploads/services/bridal-makeup-premium-01.webp', 'featured' => true, 'order' => 10],
            ['slug' => 'mehendi-artist-bridal', 'name' => 'Bridal Mehendi Artist', 'category_id' => $catMakeup, 'short' => 'Intricate bridal mehendi.', 'desc' => 'Full-hand bridal mehendi with organic henna and guest motifs for 20 guests.', 'duration' => 300, 'price' => 11000, 'image' => 'uploads/services/mehendi-artist-bridal-01.webp', 'featured' => false, 'order' => 11],
            ['slug' => 'live-band-dj-night', 'name' => 'Live Band & DJ Night', 'category_id' => $catFun, 'short' => 'Sangeet night headliners.', 'desc' => '4-piece live band plus DJ, LED wall and dhol entry for sangeet and reception.', 'duration' => 300, 'price' => 55000, 'image' => 'uploads/services/live-band-stage-01.webp', 'featured' => true, 'order' => 12],
        ];
        $serviceIds = [];
        foreach ($services as $s) {
            $serviceIds[$s['slug']] = $ensureService($s);
        }

        // --- Service images (use existing media only) ---
        $galleryMap = [
            'candid-wedding-photography' => ['uploads/services/wedding-photography-candid-01.webp', 'uploads/services/wedding-ceremony-couple-01.webp'],
            'cinematic-wedding-film' => ['uploads/services/cinematic-wedding-film-01.webp', 'uploads/services/family-wedding-ceremony-01.webp'],
            'grand-banquet-hall' => ['uploads/services/banquet-hall-grand-01.webp', 'uploads/services/couple-candlelit-venue-01.webp'],
            'royal-mandap-stage-decor' => ['uploads/services/stage-mandap-floral-01.webp', 'uploads/services/stage-mandap-floral-02.webp'],
            'outdoor-lawn-venue' => ['uploads/services/outdoor-lawn-venue-01.webp', 'uploads/services/couple-candlelit-aisle-01.webp'],
            'live-band-dj-night' => ['uploads/services/live-band-stage-01.webp', 'uploads/services/dj-wedding-reception-01.webp'],
            'bridal-premium-makeup' => ['uploads/services/bridal-makeup-premium-01.webp', 'uploads/services/mehendi-artist-bridal-01.webp'],
            'veg-luxury-buffet' => ['uploads/services/veg-buffet-luxury-01.webp', 'uploads/services/live-catering-counter-01.webp'],
        ];
        foreach ($galleryMap as $slug => $images) {
            $sid = $serviceIds[$slug];
            foreach ($images as $idx => $path) {
                if (!is_file(ROOT_PATH . '/' . $path)) {
                    continue;
                }
                $exists = $one('SELECT id FROM service_images WHERE service_id = ? AND image_path = ?', [$sid, $path]);
                if ($exists) {
                    continue;
                }
                Connection::run(
                    'INSERT INTO service_images (service_id, image_path, caption, is_primary, display_order, status) VALUES (?, ?, ?, ?, ?, ?)',
                    [$sid, $path, null, $idx === 0 ? 1 : 0, $idx, 'active']
                );
            }
            // Ensure exactly one primary.
            $primary = (int)$col('SELECT COUNT(*) FROM service_images WHERE service_id = ? AND is_primary = 1', [$sid]);
            if ($primary === 0) {
                Connection::execute('UPDATE service_images SET is_primary = 1 WHERE service_id = ? ORDER BY display_order LIMIT 1', [$sid]);
            }
        }

        // --- Packages ---
        $ensurePackage = function (string $slug, string $name, string $desc, ?string $cover, ?string $dtype, float $dval, bool $featured, int $order) use (&$counts, $one): int {
            $row = $one('SELECT id FROM packages WHERE slug = ?', [$slug]);
            if ($cover !== null && $cover !== '' && !is_file(ROOT_PATH . '/' . ltrim($cover, '/'))) {
                $cover = null;
            }
            if ($row) {
                $counts['skipped']++;
                return (int)$row['id'];
            }
            Connection::run(
                'INSERT INTO packages (name, slug, description, cover_image, discount_type, discount_value, is_featured, status, display_order)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [$name, $slug, $desc, $cover, $dtype, $dval, $featured ? 1 : 0, 'active', $order]
            );
            $counts['inserted']++;
            return (int)Connection::pdo()->lastInsertId();
        };
        $pkgRoyal = $ensurePackage('royal-wedding-package', 'Royal Wedding Package', 'Our signature full-wedding bundle: photo, film, venue, mandap, catering and bridal styling in one plan.', 'uploads/packages/royal-wedding-package-cover.webp', 'percent', 10, true, 1);
        $pkgCine = $ensurePackage('grand-cinematic-package', 'Grand Cinematic Package', 'For film-first celebrations: cinema crew, pre-wedding shoot and headline entertainment.', 'uploads/packages/grand-cinematic-package-cover.webp', 'fixed', 15000, false, 2);
        $pkgIntimate = $ensurePackage('intimate-celebration-package', 'Intimate Celebration Package', 'A warm 200-guest plan with lawn venue, florals, mehendi and makeup.', 'uploads/packages/intimate-celebration-package-cover.webp', 'percent', 5, false, 3);

        $ensurePkgService = function (int $pkg, int $svc, int $qty) use ($one): void {
            $row = $one('SELECT id FROM package_services WHERE package_id = ? AND service_id = ?', [$pkg, $svc]);
            if ($row) {
                return;
            }
            Connection::run('INSERT INTO package_services (package_id, service_id, quantity) VALUES (?, ?, ?)', [$pkg, $svc, $qty]);
        };
        $ensurePkgService($pkgRoyal, $serviceIds['candid-wedding-photography'], 1);
        $ensurePkgService($pkgRoyal, $serviceIds['cinematic-wedding-film'], 1);
        $ensurePkgService($pkgRoyal, $serviceIds['grand-banquet-hall'], 1);
        $ensurePkgService($pkgRoyal, $serviceIds['royal-mandap-stage-decor'], 1);
        $ensurePkgService($pkgRoyal, $serviceIds['veg-luxury-buffet'], 1);
        $ensurePkgService($pkgRoyal, $serviceIds['bridal-premium-makeup'], 1);
        $ensurePkgService($pkgCine, $serviceIds['cinematic-wedding-film'], 1);
        $ensurePkgService($pkgCine, $serviceIds['pre-wedding-palace-shoot'], 1);
        $ensurePkgService($pkgCine, $serviceIds['live-band-dj-night'], 1);
        $ensurePkgService($pkgIntimate, $serviceIds['outdoor-lawn-venue'], 1);
        $ensurePkgService($pkgIntimate, $serviceIds['luxury-floral-tablescapes'], 1);
        $ensurePkgService($pkgIntimate, $serviceIds['mehendi-artist-bridal'], 1);
        $ensurePkgService($pkgIntimate, $serviceIds['bridal-premium-makeup'], 1);

        // --- Staff ---
        $ensureStaff = function (?int $userId, string $name, string $email, string $phone, string $desig, string $specialty) use (&$counts, $one): int {
            $row = $one('SELECT id FROM staff WHERE email = ?', [strtolower($email)]);
            if ($row) {
                $counts['skipped']++;
                return (int)$row['id'];
            }
            Connection::run(
                'INSERT INTO staff (user_id, name, email, phone, designation, specialty, status) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$userId, $name, strtolower($email), $phone, $desig, $specialty, 'active']
            );
            $counts['inserted']++;
            return (int)Connection::pdo()->lastInsertId();
        };
        $staffArjun = $ensureStaff($staffUserId, 'Arjun Staff', 'staff@example.com', '+91 98765 00003', 'Event Captain', 'Logistics & vendor coordination');
        $staffKavya = $ensureStaff(null, 'Kavya Nair', 'kavya.nair@example.com', '+91 98765 00007', 'Lead Photographer', 'Candid & editorial');
        $staffRohan = $ensureStaff(null, 'Rohan Desai', 'rohan.desai@example.com', '+91 98765 00008', 'Decor Lead', 'Mandap & florals');

        // --- Offers ---
        $ensureOffer = function (array $o) use (&$counts, $one): int {
            $row = $one('SELECT id FROM offers WHERE slug = ?', [$o['slug']]);
            if ($row) {
                $counts['skipped']++;
                return (int)$row['id'];
            }
            Connection::run(
                'INSERT INTO offers (name, slug, description, discount_type, discount_value, applicable_to, start_date, end_date, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [$o['name'], $o['slug'], $o['desc'], $o['dtype'], $o['dval'], $o['applies'], $o['start'], $o['end'], 'active']
            );
            $counts['inserted']++;
            return (int)Connection::pdo()->lastInsertId();
        };
        $offerPhoto = $ensureOffer(['slug' => 'festive-photography-15', 'name' => 'Festive 15% Off Photography', 'desc' => 'Flat 15% off all photography services for winter weddings.', 'dtype' => 'percent', 'dval' => 15, 'applies' => 'services', 'start' => '2026-01-01', 'end' => '2027-12-31']);
        $offerVenue = $ensureOffer(['slug' => 'royal-package-20000-off', 'name' => 'Royal Package Rs.20000 Off', 'desc' => 'Limited-period saving on the Royal Wedding Package.', 'dtype' => 'fixed', 'dval' => 20000, 'applies' => 'packages', 'start' => '2026-06-01', 'end' => '2027-06-30']);
        foreach ([$serviceIds['candid-wedding-photography'], $serviceIds['cinematic-wedding-film'], $serviceIds['pre-wedding-palace-shoot']] as $sid) {
            $exists = $one('SELECT id FROM offer_services WHERE offer_id = ? AND service_id = ?', [$offerPhoto, $sid]);
            if (!$exists) {
                Connection::run('INSERT INTO offer_services (offer_id, service_id) VALUES (?, ?)', [$offerPhoto, $sid]);
            }
        }
        $exists = $one('SELECT id FROM offer_packages WHERE offer_id = ? AND package_id = ?', [$offerVenue, $pkgRoyal]);
        if (!$exists) {
            Connection::run('INSERT INTO offer_packages (offer_id, package_id) VALUES (?, ?)', [$offerVenue, $pkgRoyal]);
        }

        // --- Enquiries ---
        $ensureEnquiry = function (string $ref, int $cust, string $date, string $type, string $venue, string $notes, string $status) use (&$counts, $one): int {
            $row = $one('SELECT id FROM enquiries WHERE reference_no = ?', [$ref]);
            if ($row) {
                $counts['skipped']++;
                return (int)$row['id'];
            }
            Connection::run(
                'INSERT INTO enquiries (reference_no, customer_id, event_date, event_type, venue_address, notes, status) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$ref, $cust, $date, $type, $venue, $notes, $status]
            );
            $counts['inserted']++;
            return (int)Connection::pdo()->lastInsertId();
        };
        $enq1 = $ensureEnquiry('ENQ-2026-001', $customerId, '2026-12-12', 'Wedding', 'Grand Banquet Hall, Mumbai', 'Full wedding: photo, film, venue and mandap.', 'converted');
        $enq2 = $ensureEnquiry('ENQ-2026-002', $priyaId, '2027-02-14', 'Wedding', 'Outdoor Lawn, Pune', 'Lawn wedding with florals and makeup.', 'quotation_sent');
        $enq3 = $ensureEnquiry('ENQ-2026-003', $rahulId, '2027-04-20', 'Reception', 'Heritage Courtyard, Jaipur', 'Reception with live band and DJ.', 'new');

        $ensureEnqItem = function (int $enq, string $stype, int $sid, int $qty, ?string $notes = null) use ($one): void {
            $row = $one('SELECT id FROM enquiry_services WHERE enquiry_id = ? AND source_type = ? AND source_id = ?', [$enq, $stype, $sid]);
            if ($row) {
                return;
            }
            Connection::run('INSERT INTO enquiry_services (enquiry_id, source_type, source_id, quantity, notes) VALUES (?, ?, ?, ?, ?)', [$enq, $stype, $sid, $qty, $notes]);
        };
        $ensureEnqItem($enq1, 'service', $serviceIds['candid-wedding-photography'], 1);
        $ensureEnqItem($enq1, 'service', $serviceIds['grand-banquet-hall'], 1);
        $ensureEnqItem($enq1, 'package', $pkgRoyal, 1);
        $ensureEnqItem($enq2, 'service', $serviceIds['outdoor-lawn-venue'], 1);
        $ensureEnqItem($enq2, 'service', $serviceIds['luxury-floral-tablescapes'], 1);
        $ensureEnqItem($enq3, 'service', $serviceIds['live-band-dj-night'], 1);

        // --- Quotations (prices derived from service catalogue) ---
        $price = fn(string $slug): float => (float)$col('SELECT starting_price FROM services WHERE slug = ?', [$slug]);
        $pCandid = $price('candid-wedding-photography');
        $pBanquet = $price('grand-banquet-hall');
        $pLawn = $price('outdoor-lawn-venue');
        $pFloral = $price('luxury-floral-tablescapes');

        $ensureQuotation = function (string $ref, ?int $enq, int $cust, float $sub, ?string $dtype, float $dval, float $damt, float $total, string $valid, string $status, ?int $by, array $dates = []) use (&$counts, $one): int {
            $row = $one('SELECT id FROM quotations WHERE reference_no = ?', [$ref]);
            if ($row) {
                $counts['skipped']++;
                return (int)$row['id'];
            }
            Connection::run(
                'INSERT INTO quotations (reference_no, enquiry_id, customer_id, subtotal, discount_type, discount_value, discount_amount, total_amount, valid_until, notes, status, created_by, accepted_at, rejected_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [$ref, $enq, $cust, $sub, $dtype, $dval, $damt, $total, $valid, 'Demo quotation.', $status, $by, $dates['accepted_at'] ?? null, $dates['rejected_at'] ?? null]
            );
            $counts['inserted']++;
            return (int)Connection::pdo()->lastInsertId();
        };
        $sub1 = $pCandid + $pBanquet;
        $damt1 = round($sub1 * 5 / 100, 2);
        $quo1 = $ensureQuotation('QTN-2026-001', $enq1, $customerId, $sub1, 'percent', 5, $damt1, $sub1 - $damt1, '2026-11-30', 'accepted', $adminId, ['accepted_at' => '2026-09-10 11:00:00']);
        $sub2 = $pLawn + $pFloral;
        $quo2 = $ensureQuotation('QTN-2026-002', $enq2, $priyaId, $sub2, 'fixed', 5000, 5000, $sub2 - 5000, '2027-12-31', 'sent', $managerId);

        $ensureQuoItem = function (int $qid, string $stype, ?int $sid, string $name, int $qty, float $unit) use ($one): void {
            $row = $one('SELECT id FROM quotation_items WHERE quotation_id = ? AND item_name = ?', [$qid, $name]);
            if ($row) {
                return;
            }
            Connection::run(
                'INSERT INTO quotation_items (quotation_id, source_type, source_id, item_name, quantity, unit_price, amount) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$qid, $stype, $sid, $name, $qty, $unit, round($qty * $unit, 2)]
            );
        };
        $ensureQuoItem($quo1, 'service', $serviceIds['candid-wedding-photography'], 'Candid Wedding Photography', 1, $pCandid);
        $ensureQuoItem($quo1, 'service', $serviceIds['grand-banquet-hall'], 'Grand Banquet Hall', 1, $pBanquet);
        $ensureQuoItem($quo2, 'service', $serviceIds['outdoor-lawn-venue'], 'Outdoor Lawn Venue', 1, $pLawn);
        $ensureQuoItem($quo2, 'service', $serviceIds['luxury-floral-tablescapes'], 'Luxury Floral Tablescapes', 1, $pFloral);

        // --- Bookings ---
        $ensureBooking = function (string $ref, ?int $quo, int $cust, string $bookDate, string $eventDate, string $type, string $venue, float $sub, ?string $dtype, float $dval, float $damt, float $total, string $status, ?int $by) use (&$counts, $one): int {
            $row = $one('SELECT id FROM bookings WHERE reference_no = ?', [$ref]);
            if ($row) {
                $counts['skipped']++;
                return (int)$row['id'];
            }
            Connection::run(
                'INSERT INTO bookings (reference_no, quotation_id, customer_id, booking_date, event_date, event_type, venue_address, subtotal, discount_type, discount_value, discount_amount, total_amount, status, notes, created_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [$ref, $quo, $cust, $bookDate, $eventDate, $type, $venue, $sub, $dtype, $dval, $damt, $total, $status, 'Demo booking.', $by]
            );
            $counts['inserted']++;
            return (int)Connection::pdo()->lastInsertId();
        };
        $bkg1 = $ensureBooking('BKG-2026-001', $quo1, $customerId, '2026-09-11', '2026-12-12', 'Wedding', 'Grand Banquet Hall, Mumbai', $sub1, 'percent', 5, $damt1, $sub1 - $damt1, 'confirmed', $adminId);
        $bkg2 = $ensureBooking('BKG-2026-002', null, $priyaId, '2026-09-15', '2027-02-14', 'Wedding', 'Outdoor Lawn, Pune', $sub2, 'fixed', 5000, 5000, $sub2 - 5000, 'pending', $managerId);

        $ensureBkgItem = function (int $bid, string $stype, ?int $sid, string $name, int $qty, float $unit) use ($one): int {
            $row = $one('SELECT id FROM booking_services WHERE booking_id = ? AND item_name = ?', [$bid, $name]);
            if ($row) {
                return (int)$row['id'];
            }
            Connection::run(
                'INSERT INTO booking_services (booking_id, source_type, source_id, item_name, quantity, unit_price, amount) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$bid, $stype, $sid, $name, $qty, $unit, round($qty * $unit, 2)]
            );
            return (int)Connection::pdo()->lastInsertId();
        };
        $ensureBkgItem($bkg1, 'service', $serviceIds['candid-wedding-photography'], 'Candid Wedding Photography', 1, $pCandid);
        $ensureBkgItem($bkg1, 'service', $serviceIds['grand-banquet-hall'], 'Grand Banquet Hall', 1, $pBanquet);
        $ensureBkgItem($bkg2, 'service', $serviceIds['outdoor-lawn-venue'], 'Outdoor Lawn Venue', 1, $pLawn);
        $ensureBkgItem($bkg2, 'service', $serviceIds['luxury-floral-tablescapes'], 'Luxury Floral Tablescapes', 1, $pFloral);

        // --- Events ---
        $ensureEvent = function (int $bid, ?int $bsid, string $title, string $date, ?string $start, ?string $end, string $venue, string $status) use (&$counts, $one): int {
            $row = $one('SELECT id FROM events WHERE booking_id = ? AND title = ?', [$bid, $title]);
            if ($row) {
                $counts['skipped']++;
                return (int)$row['id'];
            }
            Connection::run(
                'INSERT INTO events (booking_id, booking_service_id, title, event_date, start_time, end_time, venue_address, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [$bid, $bsid, $title, $date, $start, $end, $venue, $status]
            );
            $counts['inserted']++;
            return (int)Connection::pdo()->lastInsertId();
        };
        $evt1 = $ensureEvent($bkg1, null, 'Wedding Ceremony', '2026-12-12', '10:00:00', '14:00:00', 'Grand Banquet Hall, Mumbai', 'scheduled');
        $evt2 = $ensureEvent($bkg1, null, 'Reception Evening', '2026-12-12', '19:00:00', '23:00:00', 'Grand Banquet Hall, Mumbai', 'scheduled');
        $evt3 = $ensureEvent($bkg2, null, 'Lawn Wedding', '2027-02-14', '11:00:00', '16:00:00', 'Outdoor Lawn, Pune', 'scheduled');

        // --- Staff assignments ---
        $ensureAssign = function (int $evt, int $staff, string $note, string $status = 'assigned') use ($one): void {
            $row = $one('SELECT id FROM staff_assignments WHERE event_id = ? AND staff_id = ?', [$evt, $staff]);
            if ($row) {
                return;
            }
            Connection::run('INSERT INTO staff_assignments (event_id, staff_id, role_note, status) VALUES (?, ?, ?, ?)', [$evt, $staff, $note, $status]);
        };
        $ensureAssign($evt1, $staffArjun, 'Day captain');
        $ensureAssign($evt1, $staffKavya, 'Lead photo');
        $ensureAssign($evt2, $staffArjun, 'Evening captain');
        $ensureAssign($evt3, $staffRohan, 'Floral setup');

        // --- Payments (partial on booking 1) ---
        $total1 = $sub1 - $damt1;
        $payAmount = round($total1 / 2, 2);
        $payRow = $one('SELECT id FROM payments WHERE booking_id = ? AND reference_no = ?', [$bkg1, 'PAY-2026-001']);
        if (!$payRow) {
            Connection::run(
                'INSERT INTO payments (booking_id, customer_id, amount, payment_date, method, reference_no, notes, status, created_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [$bkg1, $customerId, $payAmount, '2026-09-20', 'upi', 'PAY-2026-001', 'Advance (50%).', 'recorded', $adminId]
            );
        }

        // --- Invoices ---
        $ensureInvoice = function (int $bid, string $num, string $issue, ?string $due, float $sub, float $damt, float $total, string $status, ?int $by) use (&$counts, $one): int {
            $row = $one('SELECT id FROM invoices WHERE invoice_number = ?', [$num]);
            if ($row) {
                $counts['skipped']++;
                return (int)$row['id'];
            }
            Connection::run(
                'INSERT INTO invoices (booking_id, invoice_number, issue_date, due_date, subtotal, discount_amount, total_amount, status, notes, created_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [$bid, $num, $issue, $due, $sub, $damt, $total, $status, 'Demo invoice.', $by]
            );
            $counts['inserted']++;
            return (int)Connection::pdo()->lastInsertId();
        };
        $ensureInvoice($bkg1, 'INV-2026-001', '2026-09-20', '2026-12-05', $sub1, $damt1, $total1, 'partial', $adminId);
        $ensureInvoice($bkg2, 'INV-2026-002', '2026-09-16', '2027-02-01', $sub2, 5000, $sub2 - 5000, 'draft', $managerId);

        // --- Reviews (one approved/visible, one pending) ---
        $revRow = $one('SELECT id FROM reviews WHERE booking_id = ?', [$bkg1]);
        if (!$revRow) {
            Connection::run(
                'INSERT INTO reviews (booking_id, customer_id, service_id, rating, title, comment, status, is_visible, reply, moderated_by, moderated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [$bkg1, $customerId, $serviceIds['candid-wedding-photography'], 5, 'Beyond expectations', 'The team captured every emotion. Planning, decor and photos were flawless.', 'approved', 1, 'Thank you! It was an honour to be part of your celebration.', $adminId, '2026-09-22 10:00:00']
            );
        }
        // Second review needs a completed booking; reuse booking 2 after marking completed? Keep pending on booking 2 is invalid
        // (reviews require completed bookings in UI, but seed keeps one canonical approved review only to respect workflow).

        // --- Leads ---
        $ensureLead = function (?int $enq, ?int $cust, string $name, string $email, string $phone, string $status, ?int $assigned, ?string $next, string $notes) use (&$counts, $one): int {
            $row = $one('SELECT id FROM leads WHERE email = ? AND name = ?', [strtolower($email), $name]);
            if ($row) {
                $counts['skipped']++;
                return (int)$row['id'];
            }
            Connection::run(
                'INSERT INTO leads (enquiry_id, customer_id, name, email, phone, source, status, assigned_to, next_followup_at, notes)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [$enq, $cust, $name, strtolower($email), $phone, 'website', $status, $assigned, $next, $notes]
            );
            $counts['inserted']++;
            return (int)Connection::pdo()->lastInsertId();
        };
        $lead1 = $ensureLead($enq3, $rahulId, 'Rahul Verma', 'rahul.verma@example.com', '+91 98765 00006', 'follow_up', $staffArjun, '2026-10-05', 'Interested in reception entertainment.');
        $lead2 = $ensureLead(null, null, 'Sneha Kulkarni', 'sneha.kulkarni@example.com', '+91 98765 00009', 'new', null, '2026-10-02', 'Instagram enquiry for makeup trial.');

        $ensureFollowup = function (int $lead, string $date, string $note, string $status, ?int $by) use ($one): void {
            $row = $one('SELECT id FROM lead_followups WHERE lead_id = ? AND followup_date = ?', [$lead, $date]);
            if ($row) {
                return;
            }
            Connection::run('INSERT INTO lead_followups (lead_id, followup_date, note, status, created_by) VALUES (?, ?, ?, ?, ?)', [$lead, $date, $note, $status, $by]);
        };
        $ensureFollowup($lead1, '2026-09-22', 'Shared entertainment brochure and quote range.', 'done', $managerId);
        $ensureFollowup($lead1, '2026-10-05', 'Call to confirm venue walkthrough.', 'pending', $managerId);
        $ensureFollowup($lead2, '2026-09-23', 'Sent trial slots.', 'done', $managerId);

        // --- Notifications ---
        $ensureNotif = function (?int $uid, string $title, string $msg, string $type, ?string $link) use ($one): void {
            $row = $one('SELECT id FROM notifications WHERE user_id <=> ? AND title = ? AND message = ? LIMIT 1', [$uid, $title, $msg]);
            if ($row) {
                return;
            }
            Connection::run('INSERT INTO notifications (user_id, title, message, notification_type, link, is_read) VALUES (?, ?, ?, ?, ?, ?)', [$uid, $title, $msg, $type, $link, 0]);
        };
        // user_id <=> ? does not work with null in MySQL placeholder; use two paths.
        $notifCount = (int)$col('SELECT COUNT(*) FROM notifications WHERE title = ?', ['Welcome to VivaahFlow']);
        if ($notifCount === 0) {
            Connection::run(
                'INSERT INTO notifications (user_id, title, message, notification_type, link, is_read) VALUES (?, ?, ?, ?, ?, ?)',
                [$adminId, 'Welcome to VivaahFlow', 'Demo data installed. Explore bookings, events and reports.', 'success', '/manage', 0]
            );
            Connection::run(
                'INSERT INTO notifications (user_id, title, message, notification_type, link, is_read) VALUES (?, ?, ?, ?, ?, ?)',
                [$managerId, 'New lead assigned', 'Rahul Verma is awaiting a follow-up on 2026-10-05.', 'info', '/manage/leads', 0]
            );
        }

        // --- Activity logs ---
        $logCount = (int)$col('SELECT COUNT(*) FROM activity_logs WHERE action = ? AND entity_type = ?', ['demo.installed', 'system']);
        if ($logCount === 0) {
            Connection::run(
                'INSERT INTO activity_logs (user_id, action, entity_type, entity_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)',
                [$adminId, 'demo.installed', 'system', null, 'Installed demo dataset v1.', '127.0.0.1']
            );
        }

        return [
            'inserted' => $counts['inserted'],
            'skipped_existing' => $counts['skipped'],
            'admin' => 'admin@example.com',
            'customer' => 'customer@example.com',
        ];
    });
};

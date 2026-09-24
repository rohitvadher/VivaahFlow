-- VivaahFlow demo seed (separate from structural schema).
-- Database: vivaahflow
-- Usage (manual):
--   mysql -u root vivaahflow < database/demo_seed.sql
-- Preferred (idempotent, recommended):
--   php database/seed.php --with-demo
-- This file is safe to import multiple times (INSERT IGNORE on unique keys).
-- Passwords are bcrypt hashes for: Admin@123 / Manager@123 / Staff@123 / Customer@123

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Roles
INSERT IGNORE INTO roles (id, name, slug, description) VALUES
  (1, 'Administrator', 'admin', 'Full access to every module and action.'),
  (2, 'Manager', 'manager', 'All operations except destructive and administrative areas.'),
  (3, 'Staff', 'staff', 'Operational subset: bookings, events, assignments and follow-ups.'),
  (4, 'Customer', 'customer', 'Portal-only self-service access.');

-- Users (bcrypt hashes generated with password_hash / PASSWORD_DEFAULT)
INSERT IGNORE INTO users (id, role_id, name, email, phone, password_hash, status) VALUES
  (1, 1, 'VivaahFlow Admin', 'admin@example.com', '+91 98765 00001', '$2y$10$021d.owje4JX/hotAfLGvuiQD8tyWssIouoVkeD9VTNI6JUuyg6U6', 'active'),
  (2, 2, 'Meera Manager', 'manager@example.com', '+91 98765 00002', '$2y$10$O.eA98IWqXiIinKUIgvcQu7uAlTVnPP1A7ndIZ2KpYe36f3fsNS.G', 'active'),
  (3, 3, 'Arjun Staff', 'staff@example.com', '+91 98765 00003', '$2y$10$WNGikzIlrEUF42vAqQkQdOGNdlb13kkOs4QHjMe.Gt90gZ7CtEO1y', 'active'),
  (4, 4, 'Aarav & Diya', 'customer@example.com', '+91 98765 00004', '$2y$10$Dr2BGx7JCuGWWjhwjUB9gemsjnqnNikRw4HDyPVLsB5HXMCxdmuIi', 'active');

-- Settings (defaults; installer never overwrites existing keys on re-run)
INSERT IGNORE INTO settings (setting_key, setting_value, category) VALUES
  ('company_name', 'VivaahFlow', 'business'),
  ('tagline', 'Weddings planned with heart, executed with precision.', 'business'),
  ('company_email', 'hello@vivaahflow.in', 'business'),
  ('company_phone', '+91 98765 43210', 'business'),
  ('company_address', '2nd Floor, Celebration House, Linking Road, Bandra West', 'business'),
  ('company_city', 'Mumbai', 'business'),
  ('currency', 'INR', 'general'),
  ('currency_symbol', '₹', 'general'),
  ('timezone', 'Asia/Kolkata', 'general'),
  ('footer_text', 'Crafting beautiful wedding experiences across India.', 'business'),
  ('registration_open', '1', 'general'),
  ('logo_path', 'uploads/settings/company-logo-01.png', 'business');

-- Categories
INSERT IGNORE INTO service_categories (id, name, slug, description, display_order, status) VALUES
  (1, 'Photography', 'photography', 'Candid, traditional and cinematic coverage.', 1, 'active'),
  (2, 'Venues', 'venues', 'Banquet halls, lawns and palatial settings.', 2, 'active'),
  (3, 'Catering', 'catering', 'Curated menus and live counters.', 3, 'active'),
  (4, 'Decor & Florals', 'decor-florals', 'Mandaps, stages and tablescapes.', 4, 'active'),
  (5, 'Makeup & Styling', 'makeup-styling', 'Bridal artistry and family styling.', 5, 'active'),
  (6, 'Entertainment', 'entertainment', 'Bands, DJs and guest experiences.', 6, 'active');

-- Services
INSERT IGNORE INTO services (id, category_id, name, slug, short_description, description, duration_minutes, starting_price, image, is_featured, status, display_order) VALUES
  (1, 1, 'Candid Wedding Photography', 'candid-wedding-photography', 'Unposed moments, captured beautifully.', 'Two candid artists covering haldi to reception with 400+ edited images and a premium album.', 480, 45000.00, 'uploads/services/wedding-photography-candid-01.webp', 1, 'active', 1),
  (2, 1, 'Cinematic Wedding Film', 'cinematic-wedding-film', 'A trailer + feature film of your story.', '4K multi-cam feature film, 90-second trailer and drone coverage by a 4-person crew.', 600, 65000.00, 'uploads/services/cinematic-wedding-film-01.webp', 1, 'active', 2),
  (3, 1, 'Pre-Wedding Palace Shoot', 'pre-wedding-palace-shoot', 'Golden-hour couple stories.', 'Half-day palace shoot with styling guidance, 120 edited images and same-day reels.', 300, 25000.00, 'uploads/services/pre-wedding-sunset-palace-01.webp', 0, 'active', 3),
  (4, 2, 'Grand Banquet Hall', 'grand-banquet-hall', '1200-guest pillarless hall.', 'Air-conditioned pillarless hall with bridal suites, valet and in-house power backup.', 720, 150000.00, 'uploads/services/banquet-hall-grand-01.webp', 1, 'active', 4),
  (5, 2, 'Outdoor Lawn Venue', 'outdoor-lawn-venue', 'Manicured lawns under fairy lights.', '5-acre lawn with stage plinth, guest seating for 800 and monsoon backup dome.', 720, 95000.00, 'uploads/services/outdoor-lawn-venue-01.webp', 0, 'active', 5),
  (6, 4, 'Royal Mandap & Stage Decor', 'royal-mandap-stage-decor', 'Fresh-flower mandap artistry.', 'Designer mandap with fresh marigold, roses and drapes plus reception stage styling.', 600, 85000.00, 'uploads/services/stage-mandap-floral-01.webp', 1, 'active', 6),
  (7, 4, 'Luxury Floral Tablescapes', 'luxury-floral-tablescapes', 'Candlelit centrepieces & runners.', 'Candlelit centrepieces, runners and entry florals for up to 40 tables.', 360, 32000.00, 'uploads/services/floral-table-luxury-01.webp', 0, 'active', 7),
  (8, 3, 'Veg Luxury Buffet', 'veg-luxury-buffet', '120-dish royal vegetarian spread.', '120-dish vegetarian buffet with chaat, continental and dessert islands for 500 guests.', 300, 425000.00, 'uploads/services/veg-buffet-luxury-01.webp', 0, 'active', 8),
  (9, 3, 'Live Catering Counters', 'live-catering-counters', '6 live counters, chef-attended.', 'Six chef-attended live counters including pasta, dosa, chaat and dessert-on-fire.', 300, 45000.00, 'uploads/services/live-catering-counter-01.webp', 0, 'active', 9),
  (10, 5, 'Bridal Premium Makeup', 'bridal-premium-makeup', 'HD bridal look + trials.', 'HD bridal makeup, hairstyling, draping and two trials plus family packages.', 240, 18000.00, 'uploads/services/bridal-makeup-premium-01.webp', 1, 'active', 10),
  (11, 5, 'Bridal Mehendi Artist', 'mehendi-artist-bridal', 'Intricate bridal mehendi.', 'Full-hand bridal mehendi with organic henna and guest motifs for 20 guests.', 300, 11000.00, 'uploads/services/mehendi-artist-bridal-01.webp', 0, 'active', 11),
  (12, 6, 'Live Band & DJ Night', 'live-band-dj-night', 'Sangeet night headliners.', '4-piece live band plus DJ, LED wall and dhol entry for sangeet and reception.', 300, 55000.00, 'uploads/services/live-band-stage-01.webp', 1, 'active', 12);

-- Service images (existing media only)
INSERT IGNORE INTO service_images (id, service_id, image_path, caption, is_primary, display_order, status) VALUES
  (1, 1, 'uploads/services/wedding-photography-candid-01.webp', NULL, 1, 0, 'active'),
  (2, 1, 'uploads/services/wedding-ceremony-couple-01.webp', NULL, 0, 1, 'active'),
  (3, 2, 'uploads/services/cinematic-wedding-film-01.webp', NULL, 1, 0, 'active'),
  (4, 2, 'uploads/services/family-wedding-ceremony-01.webp', NULL, 0, 1, 'active'),
  (5, 4, 'uploads/services/banquet-hall-grand-01.webp', NULL, 1, 0, 'active'),
  (6, 4, 'uploads/services/couple-candlelit-venue-01.webp', NULL, 0, 1, 'active'),
  (7, 6, 'uploads/services/stage-mandap-floral-01.webp', NULL, 1, 0, 'active'),
  (8, 6, 'uploads/services/stage-mandap-floral-02.webp', NULL, 0, 1, 'active'),
  (9, 5, 'uploads/services/outdoor-lawn-venue-01.webp', NULL, 1, 0, 'active'),
  (10, 5, 'uploads/services/couple-candlelit-aisle-01.webp', NULL, 0, 1, 'active'),
  (11, 12, 'uploads/services/live-band-stage-01.webp', NULL, 1, 0, 'active'),
  (12, 12, 'uploads/services/dj-wedding-reception-01.webp', NULL, 0, 1, 'active'),
  (13, 10, 'uploads/services/bridal-makeup-premium-01.webp', NULL, 1, 0, 'active'),
  (14, 10, 'uploads/services/mehendi-artist-bridal-01.webp', NULL, 0, 1, 'active'),
  (15, 8, 'uploads/services/veg-buffet-luxury-01.webp', NULL, 1, 0, 'active'),
  (16, 8, 'uploads/services/live-catering-counter-01.webp', NULL, 0, 1, 'active');

-- Packages
INSERT IGNORE INTO packages (id, name, slug, description, cover_image, discount_type, discount_value, is_featured, status, display_order) VALUES
  (1, 'Royal Wedding Package', 'royal-wedding-package', 'Our signature full-wedding bundle: photo, film, venue, mandap, catering and bridal styling in one plan.', 'uploads/packages/royal-wedding-package-cover.webp', 'percent', 10.00, 1, 'active', 1),
  (2, 'Grand Cinematic Package', 'grand-cinematic-package', 'For film-first celebrations: cinema crew, pre-wedding shoot and headline entertainment.', 'uploads/packages/grand-cinematic-package-cover.webp', 'fixed', 15000.00, 0, 'active', 2),
  (3, 'Intimate Celebration Package', 'intimate-celebration-package', 'A warm 200-guest plan with lawn venue, florals, mehendi and makeup.', 'uploads/packages/intimate-celebration-package-cover.webp', 'percent', 5.00, 0, 'active', 3);

INSERT IGNORE INTO package_services (package_id, service_id, quantity) VALUES
  (1, 1, 1), (1, 2, 1), (1, 4, 1), (1, 6, 1), (1, 8, 1), (1, 10, 1),
  (2, 2, 1), (2, 3, 1), (2, 12, 1),
  (3, 5, 1), (3, 7, 1), (3, 11, 1), (3, 10, 1);

-- Customers
INSERT IGNORE INTO customers (id, user_id, name, email, phone, wedding_date, event_type, address, city, source, status, notes) VALUES
  (1, 4, 'Aarav & Diya', 'customer@example.com', '+91 98765 00004', '2026-12-12', 'Wedding', '14 Rose Villa, Juhu', 'Mumbai', 'self_registration', 'active', 'Demo customer account.'),
  (2, NULL, 'Priya Sharma', 'priya.sharma@example.com', '+91 98765 00005', '2027-02-14', 'Wedding', 'B-42 Lakeview Apartments', 'Pune', 'website', 'active', NULL),
  (3, NULL, 'Rahul Verma', 'rahul.verma@example.com', '+91 98765 00006', '2027-04-20', 'Reception', '7 Hillcrest Colony', 'Jaipur', 'referral', 'active', NULL);

-- Staff
INSERT IGNORE INTO staff (id, user_id, name, email, phone, designation, specialty, status) VALUES
  (1, 3, 'Arjun Staff', 'staff@example.com', '+91 98765 00003', 'Event Captain', 'Logistics & vendor coordination', 'active'),
  (2, NULL, 'Kavya Nair', 'kavya.nair@example.com', '+91 98765 00007', 'Lead Photographer', 'Candid & editorial', 'active'),
  (3, NULL, 'Rohan Desai', 'rohan.desai@example.com', '+91 98765 00008', 'Decor Lead', 'Mandap & florals', 'active');

-- Offers
INSERT IGNORE INTO offers (id, name, slug, description, discount_type, discount_value, applicable_to, start_date, end_date, status) VALUES
  (1, 'Festive 15% Off Photography', 'festive-photography-15', 'Flat 15% off all photography services for winter weddings.', 'percent', 15.00, 'services', '2026-01-01', '2027-12-31', 'active'),
  (2, 'Royal Package Rs.20000 Off', 'royal-package-20000-off', 'Limited-period saving on the Royal Wedding Package.', 'fixed', 20000.00, 'packages', '2026-06-01', '2027-06-30', 'active');

INSERT IGNORE INTO offer_services (offer_id, service_id) VALUES (1, 1), (1, 2), (1, 3);
INSERT IGNORE INTO offer_packages (offer_id, package_id) VALUES (2, 1);

-- Enquiries
INSERT IGNORE INTO enquiries (id, reference_no, customer_id, event_date, event_type, venue_address, notes, status) VALUES
  (1, 'ENQ-2026-001', 1, '2026-12-12', 'Wedding', 'Grand Banquet Hall, Mumbai', 'Full wedding: photo, film, venue and mandap.', 'converted'),
  (2, 'ENQ-2026-002', 2, '2027-02-14', 'Wedding', 'Outdoor Lawn, Pune', 'Lawn wedding with florals and makeup.', 'quotation_sent'),
  (3, 'ENQ-2026-003', 3, '2027-04-20', 'Reception', 'Heritage Courtyard, Jaipur', 'Reception with live band and DJ.', 'new');

INSERT IGNORE INTO enquiry_services (enquiry_id, source_type, source_id, quantity, notes) VALUES
  (1, 'service', 1, 1, NULL), (1, 'service', 4, 1, NULL), (1, 'package', 1, 1, NULL),
  (2, 'service', 5, 1, NULL), (2, 'service', 7, 1, NULL),
  (3, 'service', 12, 1, NULL);

-- Quotations (subtotal 195000 / discount 9750 / total 185250; subtotal 127000 / discount 5000 / total 122000)
INSERT IGNORE INTO quotations (id, reference_no, enquiry_id, customer_id, subtotal, discount_type, discount_value, discount_amount, total_amount, valid_until, notes, status, created_by, accepted_at, rejected_at) VALUES
  (1, 'QTN-2026-001', 1, 1, 195000.00, 'percent', 5.00, 9750.00, 185250.00, '2026-11-30', 'Demo quotation.', 'accepted', 1, '2026-09-10 11:00:00', NULL),
  (2, 'QTN-2026-002', 2, 2, 127000.00, 'fixed', 5000.00, 5000.00, 122000.00, '2027-12-31', 'Demo quotation.', 'sent', 2, NULL, NULL);

INSERT IGNORE INTO quotation_items (quotation_id, source_type, source_id, item_name, quantity, unit_price, amount) VALUES
  (1, 'service', 1, 'Candid Wedding Photography', 1, 45000.00, 45000.00),
  (1, 'service', 4, 'Grand Banquet Hall', 1, 150000.00, 150000.00),
  (2, 'service', 5, 'Outdoor Lawn Venue', 1, 95000.00, 95000.00),
  (2, 'service', 7, 'Luxury Floral Tablescapes', 1, 32000.00, 32000.00);

-- Bookings
INSERT IGNORE INTO bookings (id, reference_no, quotation_id, customer_id, booking_date, event_date, event_type, venue_address, subtotal, discount_type, discount_value, discount_amount, total_amount, status, notes, created_by) VALUES
  (1, 'BKG-2026-001', 1, 1, '2026-09-11', '2026-12-12', 'Wedding', 'Grand Banquet Hall, Mumbai', 195000.00, 'percent', 5.00, 9750.00, 185250.00, 'confirmed', 'Demo booking.', 1),
  (2, 'BKG-2026-002', NULL, 2, '2026-09-15', '2027-02-14', 'Wedding', 'Outdoor Lawn, Pune', 127000.00, 'fixed', 5000.00, 5000.00, 122000.00, 'pending', 'Demo booking.', 2);

INSERT IGNORE INTO booking_services (booking_id, source_type, source_id, item_name, quantity, unit_price, amount) VALUES
  (1, 'service', 1, 'Candid Wedding Photography', 1, 45000.00, 45000.00),
  (1, 'service', 4, 'Grand Banquet Hall', 1, 150000.00, 150000.00),
  (2, 'service', 5, 'Outdoor Lawn Venue', 1, 95000.00, 95000.00),
  (2, 'service', 7, 'Luxury Floral Tablescapes', 1, 32000.00, 32000.00);

-- Events
INSERT IGNORE INTO events (id, booking_id, booking_service_id, title, event_date, start_time, end_time, venue_address, status) VALUES
  (1, 1, NULL, 'Wedding Ceremony', '2026-12-12', '10:00:00', '14:00:00', 'Grand Banquet Hall, Mumbai', 'scheduled'),
  (2, 1, NULL, 'Reception Evening', '2026-12-12', '19:00:00', '23:00:00', 'Grand Banquet Hall, Mumbai', 'scheduled'),
  (3, 2, NULL, 'Lawn Wedding', '2027-02-14', '11:00:00', '16:00:00', 'Outdoor Lawn, Pune', 'scheduled');

INSERT IGNORE INTO staff_assignments (event_id, staff_id, role_note, status) VALUES
  (1, 1, 'Day captain', 'assigned'), (1, 2, 'Lead photo', 'assigned'),
  (2, 1, 'Evening captain', 'assigned'), (3, 3, 'Floral setup', 'assigned');

-- Payments (50% advance on booking 1: 92625.00)
INSERT IGNORE INTO payments (booking_id, customer_id, amount, payment_date, method, reference_no, notes, status, created_by) VALUES
  (1, 1, 92625.00, '2026-09-20', 'upi', 'PAY-2026-001', 'Advance (50%).', 'recorded', 1);

-- Invoices
INSERT IGNORE INTO invoices (booking_id, invoice_number, issue_date, due_date, subtotal, discount_amount, total_amount, status, notes, created_by) VALUES
  (1, 'INV-2026-001', '2026-09-20', '2026-12-05', 195000.00, 9750.00, 185250.00, 'partial', 'Demo invoice.', 1),
  (2, 'INV-2026-002', '2026-09-16', '2027-02-01', 127000.00, 5000.00, 122000.00, 'draft', 'Demo invoice.', 2);

-- Reviews
INSERT IGNORE INTO reviews (booking_id, customer_id, service_id, rating, title, comment, status, is_visible, reply, moderated_by, moderated_at) VALUES
  (1, 1, 1, 5, 'Beyond expectations', 'The team captured every emotion. Planning, decor and photos were flawless.', 'approved', 1, 'Thank you! It was an honour to be part of your celebration.', 1, '2026-09-22 10:00:00');

-- Leads
INSERT IGNORE INTO leads (id, enquiry_id, customer_id, name, email, phone, source, status, assigned_to, next_followup_at, notes) VALUES
  (1, 3, 3, 'Rahul Verma', 'rahul.verma@example.com', '+91 98765 00006', 'website', 'follow_up', 1, '2026-10-05', 'Interested in reception entertainment.'),
  (2, NULL, NULL, 'Sneha Kulkarni', 'sneha.kulkarni@example.com', '+91 98765 00009', 'website', 'new', NULL, '2026-10-02', 'Instagram enquiry for makeup trial.');

INSERT IGNORE INTO lead_followups (lead_id, followup_date, note, status, created_by) VALUES
  (1, '2026-09-22', 'Shared entertainment brochure and quote range.', 'done', 2),
  (1, '2026-10-05', 'Call to confirm venue walkthrough.', 'pending', 2),
  (2, '2026-09-23', 'Sent trial slots.', 'done', 2);

-- Notifications
INSERT IGNORE INTO notifications (user_id, title, message, notification_type, link, is_read) VALUES
  (1, 'Welcome to VivaahFlow', 'Demo data installed. Explore bookings, events and reports.', 'success', '/manage', 0),
  (2, 'New lead assigned', 'Rahul Verma is awaiting a follow-up on 2026-10-05.', 'info', '/manage/leads', 0);

-- Activity logs
INSERT IGNORE INTO activity_logs (user_id, action, entity_type, entity_id, details, ip_address) VALUES
  (1, 'demo.installed', 'system', NULL, 'Installed demo dataset v1.', '127.0.0.1');

-- Schema version marker
INSERT IGNORE INTO schema_migrations (version, app_version) VALUES (1, '1.0.0');

SET FOREIGN_KEY_CHECKS = 1;

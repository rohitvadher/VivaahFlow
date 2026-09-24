-- VivaahFlow — Wedding Services Management System
-- Database: vivaahflow
-- MySQL / MariaDB schema (structural only — no demo data)
-- Charset: utf8mb4, Engine: InnoDB

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS roles (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(60) NOT NULL,
  slug VARCHAR(60) NOT NULL,
  description VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_roles_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  role_id INT UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(150) NOT NULL,
  phone VARCHAR(30) DEFAULT NULL,
  password_hash VARCHAR(255) NOT NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  last_login_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email),
  KEY idx_users_role (role_id),
  KEY idx_users_status (status),
  CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS customers (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED DEFAULT NULL,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(150) DEFAULT NULL,
  phone VARCHAR(30) DEFAULT NULL,
  wedding_date DATE DEFAULT NULL,
  event_type VARCHAR(60) DEFAULT NULL,
  address VARCHAR(255) DEFAULT NULL,
  city VARCHAR(80) DEFAULT NULL,
  source VARCHAR(60) DEFAULT NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  notes TEXT DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_customers_email (email),
  KEY idx_customers_user (user_id),
  KEY idx_customers_status (status),
  KEY idx_customers_name (name),
  CONSTRAINT fk_customers_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS service_categories (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  slug VARCHAR(140) NOT NULL,
  description VARCHAR(255) DEFAULT NULL,
  display_order INT NOT NULL DEFAULT 0,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_service_categories_slug (slug),
  KEY idx_service_categories_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS services (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  category_id INT UNSIGNED NOT NULL,
  name VARCHAR(140) NOT NULL,
  slug VARCHAR(160) NOT NULL,
  short_description VARCHAR(255) DEFAULT NULL,
  description TEXT DEFAULT NULL,
  duration_minutes INT DEFAULT NULL,
  starting_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  image VARCHAR(255) DEFAULT NULL,
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  display_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_services_slug (slug),
  KEY idx_services_category (category_id),
  KEY idx_services_status (status),
  KEY idx_services_featured (is_featured),
  KEY idx_services_public (status, is_featured, display_order),
  CONSTRAINT fk_services_category FOREIGN KEY (category_id) REFERENCES service_categories (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS service_images (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  service_id INT UNSIGNED NOT NULL,
  image_path VARCHAR(255) NOT NULL,
  caption VARCHAR(255) DEFAULT NULL,
  is_primary TINYINT(1) NOT NULL DEFAULT 0,
  display_order INT NOT NULL DEFAULT 0,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_service_images_service (service_id),
  KEY idx_service_images_status (status),
  KEY idx_service_images_service_status (service_id, status),
  CONSTRAINT fk_service_images_service FOREIGN KEY (service_id) REFERENCES services (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS packages (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(140) NOT NULL,
  slug VARCHAR(160) NOT NULL,
  description TEXT DEFAULT NULL,
  cover_image VARCHAR(255) DEFAULT NULL,
  discount_type ENUM('percent','fixed') DEFAULT NULL,
  discount_value DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  display_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_packages_slug (slug),
  KEY idx_packages_status (status),
  KEY idx_packages_public (status, is_featured, display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS package_services (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  package_id INT UNSIGNED NOT NULL,
  service_id INT UNSIGNED NOT NULL,
  quantity INT NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE KEY uq_package_services (package_id, service_id),
  KEY idx_package_services_service (service_id),
  CONSTRAINT fk_package_services_package FOREIGN KEY (package_id) REFERENCES packages (id) ON DELETE CASCADE,
  CONSTRAINT fk_package_services_service FOREIGN KEY (service_id) REFERENCES services (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enquiries (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  reference_no VARCHAR(30) NOT NULL,
  customer_id INT UNSIGNED NOT NULL,
  event_date DATE DEFAULT NULL,
  event_type VARCHAR(60) DEFAULT NULL,
  venue_address VARCHAR(255) DEFAULT NULL,
  notes TEXT DEFAULT NULL,
  status ENUM('new','contacted','quotation_pending','quotation_sent','converted','closed') NOT NULL DEFAULT 'new',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_enquiries_reference (reference_no),
  KEY idx_enquiries_customer (customer_id),
  KEY idx_enquiries_status (status),
  KEY idx_enquiries_event_date (event_date),
  CONSTRAINT fk_enquiries_customer FOREIGN KEY (customer_id) REFERENCES customers (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enquiry_services (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  enquiry_id INT UNSIGNED NOT NULL,
  source_type ENUM('service','package') NOT NULL DEFAULT 'service',
  source_id INT UNSIGNED NOT NULL,
  quantity INT NOT NULL DEFAULT 1,
  notes VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_enquiry_services_enquiry (enquiry_id),
  CONSTRAINT fk_enquiry_services_enquiry FOREIGN KEY (enquiry_id) REFERENCES enquiries (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS quotations (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  reference_no VARCHAR(30) NOT NULL,
  enquiry_id INT UNSIGNED DEFAULT NULL,
  customer_id INT UNSIGNED NOT NULL,
  subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  discount_type ENUM('percent','fixed') DEFAULT NULL,
  discount_value DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  total_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  valid_until DATE DEFAULT NULL,
  notes TEXT DEFAULT NULL,
  status ENUM('draft','sent','accepted','rejected','expired') NOT NULL DEFAULT 'draft',
  created_by INT UNSIGNED DEFAULT NULL,
  accepted_at DATETIME DEFAULT NULL,
  rejected_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_quotations_reference (reference_no),
  KEY idx_quotations_customer (customer_id),
  KEY idx_quotations_customer_created (customer_id, created_at),
  KEY idx_quotations_enquiry (enquiry_id),
  KEY idx_quotations_status (status),
  CONSTRAINT fk_quotations_customer FOREIGN KEY (customer_id) REFERENCES customers (id),
  CONSTRAINT fk_quotations_enquiry FOREIGN KEY (enquiry_id) REFERENCES enquiries (id) ON DELETE SET NULL,
  CONSTRAINT fk_quotations_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS quotation_items (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  quotation_id INT UNSIGNED NOT NULL,
  source_type ENUM('service','package') NOT NULL DEFAULT 'service',
  source_id INT UNSIGNED DEFAULT NULL,
  item_name VARCHAR(160) NOT NULL,
  quantity INT NOT NULL DEFAULT 1,
  unit_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  notes VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_quotation_items_quotation (quotation_id),
  CONSTRAINT fk_quotation_items_quotation FOREIGN KEY (quotation_id) REFERENCES quotations (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bookings (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  reference_no VARCHAR(30) NOT NULL,
  quotation_id INT UNSIGNED DEFAULT NULL,
  customer_id INT UNSIGNED NOT NULL,
  booking_date DATE DEFAULT NULL,
  event_date DATE NOT NULL,
  event_type VARCHAR(60) DEFAULT NULL,
  venue_address VARCHAR(255) DEFAULT NULL,
  subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  discount_type ENUM('percent','fixed') DEFAULT NULL,
  discount_value DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  total_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  status ENUM('pending','confirmed','scheduled','in_progress','completed','cancelled') NOT NULL DEFAULT 'pending',
  notes TEXT DEFAULT NULL,
  created_by INT UNSIGNED DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_bookings_reference (reference_no),
  KEY idx_bookings_customer (customer_id),
  KEY idx_bookings_customer_created (customer_id, created_at),
  KEY idx_bookings_quotation (quotation_id),
  KEY idx_bookings_status (status),
  KEY idx_bookings_event_date (event_date),
  CONSTRAINT fk_bookings_customer FOREIGN KEY (customer_id) REFERENCES customers (id),
  CONSTRAINT fk_bookings_quotation FOREIGN KEY (quotation_id) REFERENCES quotations (id) ON DELETE SET NULL,
  CONSTRAINT fk_bookings_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS booking_services (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  booking_id INT UNSIGNED NOT NULL,
  source_type ENUM('service','package') DEFAULT 'service',
  source_id INT UNSIGNED DEFAULT NULL,
  item_name VARCHAR(160) NOT NULL,
  quantity INT NOT NULL DEFAULT 1,
  unit_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  notes VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_booking_services_booking (booking_id),
  CONSTRAINT fk_booking_services_booking FOREIGN KEY (booking_id) REFERENCES bookings (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS events (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  booking_id INT UNSIGNED NOT NULL,
  booking_service_id INT UNSIGNED DEFAULT NULL,
  title VARCHAR(180) NOT NULL,
  event_date DATE NOT NULL,
  start_time TIME DEFAULT NULL,
  end_time TIME DEFAULT NULL,
  venue_address VARCHAR(255) DEFAULT NULL,
  status ENUM('scheduled','in_progress','completed','cancelled') NOT NULL DEFAULT 'scheduled',
  notes TEXT DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_events_booking (booking_id),
  KEY idx_events_booking_date (booking_id, event_date),
  KEY idx_events_date (event_date),
  KEY idx_events_status (status),
  KEY idx_events_booking_service (booking_service_id),
  CONSTRAINT fk_events_booking FOREIGN KEY (booking_id) REFERENCES bookings (id) ON DELETE CASCADE,
  CONSTRAINT fk_events_booking_service FOREIGN KEY (booking_service_id) REFERENCES booking_services (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS staff (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED DEFAULT NULL,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(150) DEFAULT NULL,
  phone VARCHAR(30) DEFAULT NULL,
  designation VARCHAR(80) DEFAULT NULL,
  specialty VARCHAR(120) DEFAULT NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  notes TEXT DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_staff_user (user_id),
  KEY idx_staff_status (status),
  CONSTRAINT fk_staff_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS staff_assignments (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  event_id INT UNSIGNED NOT NULL,
  staff_id INT UNSIGNED NOT NULL,
  role_note VARCHAR(255) DEFAULT NULL,
  status ENUM('assigned','completed','cancelled') NOT NULL DEFAULT 'assigned',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_staff_assignments_event_staff (event_id, staff_id),
  KEY idx_staff_assignments_staff (staff_id),
  KEY idx_staff_assignments_status (status),
  CONSTRAINT fk_staff_assignments_event FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE CASCADE,
  CONSTRAINT fk_staff_assignments_staff FOREIGN KEY (staff_id) REFERENCES staff (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payments (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  booking_id INT UNSIGNED NOT NULL,
  customer_id INT UNSIGNED DEFAULT NULL,
  amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  payment_date DATE NOT NULL,
  method VARCHAR(40) NOT NULL,
  reference_no VARCHAR(80) DEFAULT NULL,
  notes TEXT DEFAULT NULL,
  status ENUM('recorded','reversed') NOT NULL DEFAULT 'recorded',
  created_by INT UNSIGNED DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_payments_booking (booking_id),
  KEY idx_payments_customer (customer_id),
  KEY idx_payments_date (payment_date),
  KEY idx_payments_status (status),
  CONSTRAINT fk_payments_booking FOREIGN KEY (booking_id) REFERENCES bookings (id) ON DELETE CASCADE,
  CONSTRAINT fk_payments_customer FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE SET NULL,
  CONSTRAINT fk_payments_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS invoices (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  booking_id INT UNSIGNED NOT NULL,
  invoice_number VARCHAR(30) NOT NULL,
  issue_date DATE NOT NULL,
  due_date DATE DEFAULT NULL,
  subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  total_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  status ENUM('draft','issued','partial','paid','overdue','cancelled') NOT NULL DEFAULT 'draft',
  notes TEXT DEFAULT NULL,
  created_by INT UNSIGNED DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_invoices_number (invoice_number),
  KEY idx_invoices_booking (booking_id),
  KEY idx_invoices_status (status),
  CONSTRAINT fk_invoices_booking FOREIGN KEY (booking_id) REFERENCES bookings (id) ON DELETE CASCADE,
  CONSTRAINT fk_invoices_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reviews (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  booking_id INT UNSIGNED NOT NULL,
  customer_id INT UNSIGNED NOT NULL,
  service_id INT UNSIGNED DEFAULT NULL,
  rating TINYINT UNSIGNED NOT NULL,
  title VARCHAR(160) DEFAULT NULL,
  comment TEXT NOT NULL,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  is_visible TINYINT(1) NOT NULL DEFAULT 0,
  reply TEXT DEFAULT NULL,
  moderated_by INT UNSIGNED DEFAULT NULL,
  moderated_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_reviews_booking (booking_id),
  KEY idx_reviews_customer (customer_id),
  KEY idx_reviews_status (status),
  KEY idx_reviews_visible (is_visible),
  KEY idx_reviews_public (status, is_visible, created_at),
  KEY idx_reviews_service_public (service_id, status, is_visible),
  CONSTRAINT fk_reviews_booking FOREIGN KEY (booking_id) REFERENCES bookings (id) ON DELETE CASCADE,
  CONSTRAINT fk_reviews_customer FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE CASCADE,
  CONSTRAINT fk_reviews_service FOREIGN KEY (service_id) REFERENCES services (id) ON DELETE SET NULL,
  CONSTRAINT fk_reviews_moderated_by FOREIGN KEY (moderated_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS offers (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(140) NOT NULL,
  slug VARCHAR(160) NOT NULL,
  description TEXT DEFAULT NULL,
  discount_type ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
  discount_value DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  applicable_to ENUM('all','services','packages') NOT NULL DEFAULT 'all',
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_offers_slug (slug),
  KEY idx_offers_status (status),
  KEY idx_offers_dates (start_date, end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS offer_services (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  offer_id INT UNSIGNED NOT NULL,
  service_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_offer_services (offer_id, service_id),
  KEY idx_offer_services_service (service_id),
  CONSTRAINT fk_offer_services_offer FOREIGN KEY (offer_id) REFERENCES offers (id) ON DELETE CASCADE,
  CONSTRAINT fk_offer_services_service FOREIGN KEY (service_id) REFERENCES services (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS offer_packages (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  offer_id INT UNSIGNED NOT NULL,
  package_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_offer_packages (offer_id, package_id),
  KEY idx_offer_packages_package (package_id),
  CONSTRAINT fk_offer_packages_offer FOREIGN KEY (offer_id) REFERENCES offers (id) ON DELETE CASCADE,
  CONSTRAINT fk_offer_packages_package FOREIGN KEY (package_id) REFERENCES packages (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS leads (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  enquiry_id INT UNSIGNED DEFAULT NULL,
  customer_id INT UNSIGNED DEFAULT NULL,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(150) DEFAULT NULL,
  phone VARCHAR(30) DEFAULT NULL,
  source VARCHAR(60) DEFAULT 'website',
  status ENUM('new','contacted','follow_up','converted','lost') NOT NULL DEFAULT 'new',
  assigned_to INT UNSIGNED DEFAULT NULL,
  next_followup_at DATE DEFAULT NULL,
  notes TEXT DEFAULT NULL,
  converted_at DATETIME DEFAULT NULL,
  lost_at DATETIME DEFAULT NULL,
  lost_reason VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_leads_enquiry (enquiry_id),
  KEY idx_leads_customer (customer_id),
  KEY idx_leads_status (status),
  KEY idx_leads_next_followup (next_followup_at),
  KEY idx_leads_assigned (assigned_to),
  CONSTRAINT fk_leads_enquiry FOREIGN KEY (enquiry_id) REFERENCES enquiries (id) ON DELETE SET NULL,
  CONSTRAINT fk_leads_customer FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE SET NULL,
  CONSTRAINT fk_leads_assigned FOREIGN KEY (assigned_to) REFERENCES staff (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lead_followups (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  lead_id INT UNSIGNED NOT NULL,
  followup_date DATE NOT NULL,
  note TEXT DEFAULT NULL,
  status ENUM('done','missed','pending') NOT NULL DEFAULT 'done',
  created_by INT UNSIGNED DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_lead_followups_lead (lead_id),
  KEY idx_lead_followups_date (followup_date),
  CONSTRAINT fk_lead_followups_lead FOREIGN KEY (lead_id) REFERENCES leads (id) ON DELETE CASCADE,
  CONSTRAINT fk_lead_followups_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notifications (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED DEFAULT NULL,
  title VARCHAR(180) NOT NULL,
  message TEXT DEFAULT NULL,
  notification_type VARCHAR(40) DEFAULT 'info',
  link VARCHAR(255) DEFAULT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_notifications_user (user_id),
  KEY idx_notifications_read (is_read),
  KEY idx_notifications_user_read (user_id, is_read, created_at),
  CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  setting_key VARCHAR(80) NOT NULL,
  setting_value TEXT DEFAULT NULL,
  category VARCHAR(60) NOT NULL DEFAULT 'general',
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_settings_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS activity_logs (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED DEFAULT NULL,
  action VARCHAR(80) NOT NULL,
  entity_type VARCHAR(60) DEFAULT NULL,
  entity_id INT UNSIGNED DEFAULT NULL,
  details TEXT DEFAULT NULL,
  ip_address VARCHAR(45) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_activity_logs_user (user_id),
  KEY idx_activity_logs_entity (entity_type, entity_id),
  KEY idx_activity_logs_created (created_at),
  CONSTRAINT fk_activity_logs_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  email VARCHAR(150) NOT NULL,
  ip_address VARCHAR(45) NOT NULL,
  attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  success TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY idx_login_attempts_lookup (email, ip_address, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS schema_migrations (
  version INT UNSIGNED NOT NULL PRIMARY KEY,
  app_version VARCHAR(20) NOT NULL DEFAULT '1.0.0',
  applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
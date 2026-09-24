<?php

declare(strict_types=1);

require_once __DIR__ . '/../../backend/bootstrap.php';

redirect_to_setup_unless(false);

$route = $_GET['route'] ?? '';
$route = ltrim($route, '/');

if ($route === '' || $route === 'index.php') {
    $route = 'dashboard';
}

$routes = [
    'dashboard' => ['file' => 'dashboard.php', 'title' => 'Dashboard'],
    'login' => ['file' => 'login.php', 'title' => 'Login', 'no_auth' => true],
    'customers' => ['file' => 'customers.php', 'title' => 'Customers'],
    'customers/{id}' => ['file' => 'customer.php', 'title' => 'Customer Detail'],
    'enquiries' => ['file' => 'enquiries.php', 'title' => 'Enquiries'],
    'leads' => ['file' => 'leads.php', 'title' => 'Leads'],
    'categories' => ['file' => 'categories.php', 'title' => 'Categories'],
    'services' => ['file' => 'services.php', 'title' => 'Services'],
    'packages' => ['file' => 'packages.php', 'title' => 'Packages'],
    'offers' => ['file' => 'offers.php', 'title' => 'Offers'],
    'quotations' => ['file' => 'quotations.php', 'title' => 'Quotations'],
    'quotations/{id}' => ['file' => 'quotation.php', 'title' => 'Quotation Detail'],
    'bookings' => ['file' => 'bookings.php', 'title' => 'Bookings'],
    'bookings/{id}' => ['file' => 'booking.php', 'title' => 'Booking Detail'],
    'events' => ['file' => 'events.php', 'title' => 'Events'],
    'staff' => ['file' => 'staff.php', 'title' => 'Staff'],
    'payments' => ['file' => 'payments.php', 'title' => 'Payments'],
    'invoices' => ['file' => 'invoices.php', 'title' => 'Invoices'],
    'invoices/{id}' => ['file' => 'invoice.php', 'title' => 'Invoice Detail'],
    'reviews' => ['file' => 'reviews.php', 'title' => 'Reviews'],
    'reports' => ['file' => 'reports.php', 'title' => 'Reports'],
    'notifications' => ['file' => 'notifications.php', 'title' => 'Notifications'],
    'settings' => ['file' => 'settings.php', 'title' => 'Settings'],
    'users' => ['file' => 'users.php', 'title' => 'Users'],
    'activity' => ['file' => 'activity.php', 'title' => 'Activity'],
    'help' => ['file' => 'help.php', 'title' => 'Help'],
    'profile' => ['file' => 'profile.php', 'title' => 'Profile'],
];

$matchedRoute = null;
$routeParams = [];

foreach ($routes as $pattern => $info) {
    $regex = preg_replace('/\{([^}]+)\}/', '(?P<$1>[^/]+)', $pattern);
    if (preg_match('#^' . $regex . '$#', $route, $matches)) {
        $matchedRoute = $info;
        $routeParams = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
        break;
    }
}

$routeInfo = $matchedRoute ?? ['file' => 'dashboard.php', 'title' => 'Dashboard'];
$GLOBALS['routeParams'] = $routeParams;
$pageFile = __DIR__ . '/' . $routeInfo['file'];

if (!is_file($pageFile)) {
    http_response_code(404);
    $pageFile = __DIR__ . '/dashboard.php';
}

$pageTitle = $routeInfo['title'];
$pageDesc = '';
$activeNav = $route;
$pageScript = '';
$extraCss = [];
$extraLibraries = [];
$routeParams = $routeParams;
$noAuth = $routeInfo['no_auth'] ?? false;

require $pageFile;
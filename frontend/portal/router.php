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
    'enquiries' => ['file' => 'enquiries.php', 'title' => 'Enquiries'],
    'quotations' => ['file' => 'quotations.php', 'title' => 'Quotations'],
    'quotations/{id}' => ['file' => 'quotation.php', 'title' => 'Quotation Detail'],
    'bookings' => ['file' => 'bookings.php', 'title' => 'Bookings'],
    'bookings/{id}' => ['file' => 'booking.php', 'title' => 'Booking Detail'],
    'payments' => ['file' => 'payments.php', 'title' => 'Payments'],
    'invoices' => ['file' => 'invoices.php', 'title' => 'Invoices'],
    'invoices/{id}' => ['file' => 'invoice.php', 'title' => 'Invoice Detail'],
    'reviews' => ['file' => 'reviews.php', 'title' => 'Reviews'],
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

require $pageFile;
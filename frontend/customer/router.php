<?php

declare(strict_types=1);

require_once __DIR__ . '/../../backend/bootstrap.php';

redirect_to_setup_unless(false);

$route = $_GET['route'] ?? '';
$route = ltrim($route, '/');

if ($route === '' || $route === 'index.php') {
    $route = 'home';
}

$routes = [
    'home' => ['file' => 'home.php', 'title' => 'Home', 'nav' => 'home'],
    'services' => ['file' => 'services.php', 'title' => 'Services', 'nav' => 'services'],
    'services/{slug}' => ['file' => 'service.php', 'title' => 'Service Detail', 'nav' => 'services'],
    'packages' => ['file' => 'packages.php', 'title' => 'Packages', 'nav' => 'packages'],
    'packages/{slug}' => ['file' => 'package.php', 'title' => 'Package Detail', 'nav' => 'packages'],
    'gallery' => ['file' => 'gallery.php', 'title' => 'Gallery', 'nav' => 'gallery'],
    'offers' => ['file' => 'offers.php', 'title' => 'Offers', 'nav' => 'offers'],
    'reviews' => ['file' => 'reviews.php', 'title' => 'Reviews', 'nav' => 'reviews'],
    'contact' => ['file' => 'contact.php', 'title' => 'Contact', 'nav' => 'contact'],
    'login' => ['file' => 'login.php', 'title' => 'Login', 'nav' => ''],
    'register' => ['file' => 'register.php', 'title' => 'Register', 'nav' => ''],
];

$routeInfo = ['file' => 'home.php', 'title' => 'Home', 'nav' => 'home'];
$routeParams = [];
foreach ($routes as $pattern => $candidate) {
    $regex = preg_replace('/\{([^}]+)\}/', '(?P<$1>[^/]+)', $pattern);
    if (preg_match('#^' . $regex . '$#', $route, $matches)) {
        $routeInfo = $candidate;
        $routeParams = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
        break;
    }
}
$GLOBALS['routeParams'] = $routeParams;
$pageFile = __DIR__ . '/' . $routeInfo['file'];

if (!is_file($pageFile)) {
    http_response_code(404);
    $pageFile = __DIR__ . '/home.php';
}

$pageTitle = $routeInfo['title'];
$pageDesc = '';
$activeNav = $routeInfo['nav'];
$pageScript = '';
$extraCss = [];
$extraLibraries = [];

require $pageFile;
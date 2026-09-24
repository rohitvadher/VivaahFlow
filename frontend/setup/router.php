<?php

declare(strict_types=1);

$route = $_GET['route'] ?? '';
$route = ltrim((string)$route, '/');

if ($route === '' || $route === 'index.php') {
    $route = 'index';
}

$GLOBALS['routeParams'] = [];
$pageTitle = 'Setup';
$pageDesc = 'First-run VivaahFlow installation.';
$activeNav = '';
$pageScript = 'js/setup/wizard.js';
$extraCss = ['css/site.css'];
$extraLibraries = [];

$pageFile = __DIR__ . '/index.php';
require $pageFile;

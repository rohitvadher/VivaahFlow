<?php

declare(strict_types=1);

// Root entry point. Under Apache (.htaccess) this serves `/` only;
// clean routes are rewritten to the matching frontend router.
// Under the PHP built-in server (no .htaccess) this also dispatches
// `/setup`, `/account…` and `/manage…` so the installer works there too.
$uri = parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
$uri = '/' . trim($uri, '/');

if ($uri === '/setup' || str_starts_with($uri, '/setup/')) {
    $_GET['route'] = trim(substr($uri, strlen('/setup')), '/');
    require __DIR__ . '/frontend/setup/router.php';
    return;
}
if ($uri === '/account' || str_starts_with($uri, '/account/')) {
    $_GET['route'] = trim(substr($uri, strlen('/account')), '/');
    require __DIR__ . '/frontend/portal/router.php';
    return;
}
if ($uri === '/manage' || str_starts_with($uri, '/manage/')) {
    $_GET['route'] = trim(substr($uri, strlen('/manage')), '/');
    require __DIR__ . '/frontend/admin/router.php';
    return;
}
if (preg_match('#^/(services|packages|gallery|offers|reviews|contact|login|register)(/.*)?$#', $uri)) {
    $_GET['route'] = ltrim($uri, '/');
    require __DIR__ . '/frontend/customer/router.php';
    return;
}

require __DIR__ . '/frontend/customer/router.php';

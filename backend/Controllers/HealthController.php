<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Database\Connection;

class HealthController extends BaseController
{
    public function index(Request $request): void
    {
        $database = 'ok';
        try {
            Connection::fetchColumn('SELECT 1');
        } catch (\Throwable $e) {
            $database = 'unavailable';
        }
        $this->success([
            'status' => 'ok',
            'database' => $database,
            'time' => date('c'),
        ], 'API is healthy.');
    }
}
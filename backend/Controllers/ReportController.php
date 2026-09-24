<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Services\ReportService;

class ReportController extends BaseController
{
    public function __construct(private ReportService $reportService)
    {
    }

    public function dashboard(Request $request): void
    {
        $this->requirePermission('dashboard.view');
        $this->success($this->reportService->dashboard());
    }

    public function summary(Request $request): void
    {
        $this->requirePermission('reports.read');
        $range = (string)$request->input('range', '6m');
        $this->success($this->reportService->summary($range));
    }

    public function revenue(Request $request): void
    {
        $this->requirePermission('reports.read');
        $from = (string)$request->input('from', date('Y-m-01', strtotime('-5 months')));
        $to = (string)$request->input('to', date('Y-m-d'));
        $this->success($this->reportService->revenueReport($from, $to));
    }

    public function bookings(Request $request): void
    {
        $this->requirePermission('reports.read');
        $from = (string)$request->input('from', date('Y-01-01'));
        $to = (string)$request->input('to', date('Y-12-31'));
        $this->success($this->reportService->bookingsReport($from, $to));
    }
}
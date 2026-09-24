<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\InvoiceService;
use App\Validators\Validator;

class InvoiceController extends BaseController
{
    public function __construct(private InvoiceService $invoiceService)
    {
    }

    public function index(Request $request): void
    {
        $this->requirePermission('invoices.read');
        $this->success($this->invoiceService->list($this->page(), $this->perPage(), $this->search(), $this->statusFilter()));
    }

    public function show(Request $request): void
    {
        $this->requirePermission('invoices.read');
        $this->success($this->invoiceService->find($this->routeId($request)));
    }

    public function store(Request $request): void
    {
        $this->requirePermission('invoices.create');
        $result = Validator::validate($request->all(), [
            'booking_id' => 'required|integer|exists:bookings,id',
            'issue_date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
        ]);
        if (!$result['valid']) {
            Response::validation($result['errors']);
        }
        $bookingId = (int)$result['data']['booking_id'];
        unset($result['data']['booking_id']);
        $this->created($this->invoiceService->createFromBooking($bookingId, $this->userId(), $result['data']), 'Invoice generated.');
    }

    public function status(Request $request): void
    {
        $this->requirePermission('invoices.update');
        $this->success(
            $this->invoiceService->changeStatus($this->routeId($request), (string)$request->input('status', ''), $this->userId()),
            'Invoice status updated.'
        );
    }

    public function destroy(Request $request): void
    {
        $this->requirePermission('invoices.delete');
        $this->invoiceService->delete($this->routeId($request), $this->userId());
        $this->success(null, 'Invoice deleted.');
    }
}
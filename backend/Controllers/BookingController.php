<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\BookingService;
use App\Validators\Validator;

class BookingController extends BaseController
{
    public function __construct(private BookingService $bookingService)
    {
    }

    public function index(Request $request): void
    {
        $this->requirePermission('bookings.read');
        $this->success($this->bookingService->list(
            $this->page(),
            $this->perPage(),
            $this->search(),
            $this->statusFilter(),
            $request->input('date_from'),
            $request->input('date_to')
        ));
    }

    public function show(Request $request): void
    {
        $this->requirePermission('bookings.read');
        $this->success($this->bookingService->find($this->routeId($request)));
    }

    public function store(Request $request): void
    {
        $this->requirePermission('bookings.create');
        $result = Validator::validate($request->all(), [
            'quotation_id' => 'required|integer|exists:quotations,id',
            'event_date' => 'required|date',
            'event_type' => 'nullable|string|max:80',
            'venue_address' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
        ]);
        if (!$result['valid']) {
            Response::validation($result['errors']);
        }
        $quotationId = (int)$result['data']['quotation_id'];
        unset($result['data']['quotation_id']);
        $this->created($this->bookingService->createFromQuotation($quotationId, $result['data'], $this->userId()), 'Booking created.');
    }

    public function update(Request $request): void
    {
        $this->requirePermission('bookings.update');
        $result = Validator::validate($request->all(), [
            'booking_date' => 'nullable|date',
            'event_date' => 'nullable|date',
            'event_type' => 'nullable|string|max:80',
            'venue_address' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
        ]);
        if (!$result['valid']) {
            Response::validation($result['errors']);
        }
        $this->success($this->bookingService->update($this->routeId($request), $result['data'], $this->userId()), 'Booking updated.');
    }

    public function status(Request $request): void
    {
        $this->requirePermission('bookings.status');
        $this->success(
            $this->bookingService->changeStatus($this->routeId($request), (string)$request->input('status', ''), $this->userId()),
            'Booking status updated.'
        );
    }

    public function schedule(Request $request): void
    {
        $this->requirePermission('bookings.update');
        $result = Validator::validate($request->all(), [
            'event_date' => 'required|date',
            'venue_address' => 'nullable|string|max:255',
        ]);
        if (!$result['valid']) {
            Response::validation($result['errors']);
        }
        $this->success($this->bookingService->schedule($this->routeId($request), $result['data'], $this->userId()), 'Booking scheduled.');
    }

    public function destroy(Request $request): void
    {
        $this->requirePermission('bookings.update');
        $this->bookingService->delete($this->routeId($request), $this->userId());
        $this->success(null, 'Booking deleted.');
    }
}
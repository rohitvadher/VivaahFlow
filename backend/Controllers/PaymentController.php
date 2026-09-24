<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\PaymentService;
use App\Validators\Validator;

class PaymentController extends BaseController
{
    public function __construct(private PaymentService $paymentService)
    {
    }

    public function index(Request $request): void
    {
        $this->requirePermission('payments.read');
        $this->success($this->paymentService->list($this->page(), $this->perPage(), $this->search(), $this->statusFilter()));
    }

    public function show(Request $request): void
    {
        $this->requirePermission('payments.read');
        $this->success($this->paymentService->find($this->routeId($request)));
    }

    public function store(Request $request): void
    {
        $this->requirePermission('payments.create');
        $result = Validator::validate($request->all(), [
            'booking_id' => 'required|integer|exists:bookings,id',
            'amount' => 'required|numeric',
            'payment_date' => 'nullable|date',
            'method' => 'required|string|max:60',
            'reference_no' => 'nullable|string|max:60',
            'notes' => 'nullable|string|max:2000',
        ]);
        if (!$result['valid']) {
            Response::validation($result['errors']);
        }
        $bookingId = (int)$result['data']['booking_id'];
        unset($result['data']['booking_id']);
        $this->created($this->paymentService->create($bookingId, $result['data'], $this->userId()), 'Payment recorded.');
    }

    public function reverse(Request $request): void
    {
        $this->requirePermission('payments.reverse');
        $this->success($this->paymentService->reverse($this->routeId($request), $this->userId()), 'Payment reversed.');
    }
}
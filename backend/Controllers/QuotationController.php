<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\QuotationService;
use App\Validators\Validator;

class QuotationController extends BaseController
{
    public function __construct(private QuotationService $quotationService)
    {
    }

    public function index(Request $request): void
    {
        $this->requirePermission('quotations.read');
        $this->success($this->quotationService->list($this->page(), $this->perPage(), $this->search(), $this->statusFilter()));
    }

    public function show(Request $request): void
    {
        $this->requirePermission('quotations.read');
        $this->success($this->quotationService->find($this->routeId($request)));
    }

    public function update(Request $request): void
    {
        $this->requirePermission('quotations.update');
        $result = Validator::validate($request->all(), [
            'discount_type' => 'nullable|in:percent,fixed',
            'discount_value' => 'nullable|numeric',
            'valid_until' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
        ]);
        if (!$result['valid']) {
            Response::validation($result['errors']);
        }
        $this->success(
            $this->quotationService->update($this->routeId($request), $result['data'], (array)$request->input('items', []), $this->userId()),
            'Quotation updated.'
        );
    }

    public function status(Request $request): void
    {
        $this->requirePermission('quotations.status');
        $this->success(
            $this->quotationService->changeStatus($this->routeId($request), (string)$request->input('status', ''), $this->userId()),
            'Quotation status updated.'
        );
    }

    public function accept(Request $request): void
    {
        $this->requirePermission('quotations.status');
        $this->success($this->quotationService->accept($this->routeId($request), $this->userId()), 'Quotation accepted.');
    }

    public function reject(Request $request): void
    {
        $this->requirePermission('quotations.status');
        $this->success(
            $this->quotationService->reject($this->routeId($request), $this->userId(), $request->input('reason')),
            'Quotation rejected.'
        );
    }
}
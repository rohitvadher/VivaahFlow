<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\OfferService;
use App\Validators\Validator;

class OfferController extends BaseController
{
    public function __construct(private OfferService $offerService)
    {
    }

    public function index(Request $request): void
    {
        $this->requirePermission('offers.read');
        $this->success($this->offerService->list($this->page(), $this->perPage(), $this->search(), $this->statusFilter()));
    }

    public function active(Request $request): void
    {
        $this->requirePermission('offers.read');
        $this->success($this->offerService->active());
    }

    public function show(Request $request): void
    {
        $this->requirePermission('offers.read');
        $this->success($this->offerService->find($this->routeId($request)));
    }

    public function store(Request $request): void
    {
        $this->requirePermission('offers.create');
        $result = Validator::validate($request->all(), $this->rules());
        if (!$result['valid']) {
            Response::validation($result['errors']);
        }
        $this->created($this->offerService->create($result['data'], $this->userId()), 'Offer created.');
    }

    public function update(Request $request): void
    {
        $this->requirePermission('offers.update');
        $result = Validator::validate($request->all(), $this->rules());
        if (!$result['valid']) {
            Response::validation($result['errors']);
        }
        $this->success($this->offerService->update($this->routeId($request), $result['data'], $this->userId()), 'Offer updated.');
    }

    public function status(Request $request): void
    {
        $this->requirePermission('offers.update');
        $this->success(
            $this->offerService->changeStatus($this->routeId($request), (string)$request->input('status', ''), $this->userId()),
            'Offer status updated.'
        );
    }

    public function destroy(Request $request): void
    {
        $this->requirePermission('offers.delete');
        $this->offerService->delete($this->routeId($request), $this->userId());
        $this->success(null, 'Offer deleted.');
    }

    private function rules(): array
    {
        return [
            'name' => 'required|string|min:2|max:140',
            'description' => 'nullable|string|max:2000',
            'discount_type' => 'required|in:percent,fixed',
            'discount_value' => 'required|numeric',
            'applicable_to' => 'required|in:all,services,packages',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'service_ids' => 'nullable|array',
            'package_ids' => 'nullable|array',
            'status' => 'nullable|in:active,inactive',
        ];
    }
}
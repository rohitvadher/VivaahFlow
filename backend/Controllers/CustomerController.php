<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\CustomerService;
use App\Validators\Validator;

class CustomerController extends BaseController
{
    public function __construct(private CustomerService $customerService)
    {
    }

    public function index(Request $request): void
    {
        $this->requirePermission('customers.read');
        $this->success($this->customerService->list($this->page(), $this->perPage(), $this->search(), $this->statusFilter()));
    }

    public function show(Request $request): void
    {
        $this->requirePermission('customers.read');
        $this->success($this->customerService->find($this->routeId($request)));
    }

    public function store(Request $request): void
    {
        $this->requirePermission('customers.create');
        $result = Validator::validate($request->all(), [
            'name' => 'required|string|min:3|max:120',
            'email' => 'required|email|unique:customers,email',
            'phone' => 'nullable|phone',
            'wedding_date' => 'nullable|date',
            'event_type' => 'nullable|string|max:80',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'status' => 'nullable|in:active,inactive',
        ]);
        if (!$result['valid']) {
            Response::validation($result['errors']);
        }
        $this->created($this->customerService->create($result['data'], $this->userId()), 'Customer created.');
    }

    public function update(Request $request): void
    {
        $this->requirePermission('customers.update');
        $result = Validator::validate($request->all(), [
            'name' => 'required|string|min:3|max:120',
            'email' => 'nullable|email',
            'phone' => 'nullable|phone',
            'wedding_date' => 'nullable|date',
            'event_type' => 'nullable|string|max:80',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'status' => 'nullable|in:active,inactive',
        ]);
        if (!$result['valid']) {
            Response::validation($result['errors']);
        }
        $this->success($this->customerService->update($this->routeId($request), $result['data'], $this->userId()), 'Customer updated.');
    }

    public function status(Request $request): void
    {
        $this->requirePermission('customers.update');
        $status = (string)$request->input('status', '');
        $this->success($this->customerService->changeStatus($this->routeId($request), $status, $this->userId()), 'Customer status updated.');
    }

    public function destroy(Request $request): void
    {
        $this->requirePermission('customers.delete');
        $this->customerService->delete($this->routeId($request), $this->userId());
        $this->success(null, 'Customer deleted.');
    }
}
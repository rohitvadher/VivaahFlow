<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\StaffService;
use App\Validators\Validator;

class StaffController extends BaseController
{
    public function __construct(private StaffService $staffService)
    {
    }

    public function index(Request $request): void
    {
        $this->requirePermission('staff.read');
        $this->success($this->staffService->list($this->page(), $this->perPage(), $this->search(), $this->statusFilter()));
    }

    public function active(Request $request): void
    {
        $this->requirePermission('staff.read');
        $this->success($this->staffService->active());
    }

    public function show(Request $request): void
    {
        $this->requirePermission('staff.read');
        $this->success($this->staffService->find($this->routeId($request)));
    }

    public function store(Request $request): void
    {
        $this->requirePermission('staff.create');
        $result = Validator::validate($request->all(), $this->rules());
        if (!$result['valid']) {
            Response::validation($result['errors']);
        }
        $this->created($this->staffService->create($result['data'], $this->userId()), 'Staff member created.');
    }

    public function update(Request $request): void
    {
        $this->requirePermission('staff.update');
        $result = Validator::validate($request->all(), $this->rules(false));
        if (!$result['valid']) {
            Response::validation($result['errors']);
        }
        $this->success($this->staffService->update($this->routeId($request), $result['data'], $this->userId()), 'Staff member updated.');
    }

    public function status(Request $request): void
    {
        $this->requirePermission('staff.update');
        $this->success(
            $this->staffService->changeStatus($this->routeId($request), (string)$request->input('status', ''), $this->userId()),
            'Staff status updated.'
        );
    }

    public function destroy(Request $request): void
    {
        $this->requirePermission('staff.delete');
        $this->staffService->delete($this->routeId($request), $this->userId());
        $this->success(null, 'Staff member deleted.');
    }

    private function rules(bool $creating = true): array
    {
        $rules = [
            'name' => 'required|string|min:2|max:120',
            'email' => 'nullable|email',
            'phone' => 'nullable|phone',
            'designation' => 'nullable|string|max:120',
            'specialty' => 'nullable|string|max:120',
            'status' => 'nullable|in:active,inactive',
            'notes' => 'nullable|string|max:2000',
        ];
        if ($creating) {
            $rules['create_login'] = 'nullable|boolean';
            $rules['password'] = 'nullable|string|min:6';
        }
        return $rules;
    }
}
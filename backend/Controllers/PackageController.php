<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\PackageService;
use App\Validators\Validator;

class PackageController extends BaseController
{
    public function __construct(private PackageService $packageService)
    {
    }

    public function index(Request $request): void
    {
        $this->requirePermission('packages.read');
        $this->success($this->packageService->list($this->page(), $this->perPage(), $this->search(), $this->statusFilter()));
    }

    public function show(Request $request): void
    {
        $this->requirePermission('packages.read');
        $this->success($this->packageService->find($this->routeId($request)));
    }

    public function store(Request $request): void
    {
        $this->requirePermission('packages.create');
        $result = Validator::validate($this->input($request), $this->rules());
        if (!$result['valid']) {
            Response::validation($result['errors']);
        }
        $this->created(
            $this->packageService->create($result['data'], $this->serviceIds($request), $request->file('cover_image'), $this->userId()),
            'Package created.'
        );
    }

    public function update(Request $request): void
    {
        $this->requirePermission('packages.update');
        $result = Validator::validate($this->input($request), $this->rules());
        if (!$result['valid']) {
            Response::validation($result['errors']);
        }
        $this->success(
            $this->packageService->update($this->routeId($request), $result['data'], $this->serviceIds($request), $request->file('cover_image'), $this->userId()),
            'Package updated.'
        );
    }

    public function status(Request $request): void
    {
        $this->requirePermission('packages.update');
        $this->success($this->packageService->changeStatus($this->routeId($request), (string)$request->input('status', ''), $this->userId()), 'Package status updated.');
    }

    public function destroy(Request $request): void
    {
        $this->requirePermission('packages.delete');
        $this->packageService->delete($this->routeId($request), $this->userId());
        $this->success(null, 'Package deleted.');
    }

    private function input(Request $request): array
    {
        $input = $request->all();
        if (isset($input['service_ids'])) {
            $input['service_ids'] = $this->serviceIds($request);
        }
        return $input;
    }

    private function serviceIds(Request $request): array
    {
        $ids = $request->input('service_ids', []);
        if (is_string($ids)) {
            $decoded = json_decode($ids, true);
            $ids = is_array($decoded) ? $decoded : explode(',', $ids);
        }
        return array_values(array_filter(array_map('intval', (array)$ids), fn(int $id): bool => $id > 0));
    }

    private function rules(): array
    {
        return [
            'name' => 'required|string|min:2|max:160',
            'description' => 'nullable|string',
            'discount_type' => 'nullable|in:percent,fixed',
            'discount_value' => 'nullable|numeric',
            'service_ids' => 'nullable|array',
            'is_featured' => 'nullable|boolean',
            'status' => 'nullable|in:active,inactive',
            'display_order' => 'nullable|integer',
        ];
    }
}
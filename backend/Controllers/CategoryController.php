<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\ServiceService;
use App\Validators\Validator;

class CategoryController extends BaseController
{
    public function __construct(private ServiceService $serviceService)
    {
    }

    public function index(Request $request): void
    {
        $this->requirePermission('categories.read');
        $this->success($this->serviceService->categories(true));
    }

    public function store(Request $request): void
    {
        $this->requirePermission('categories.create');
        $result = Validator::validate($request->all(), [
            'name' => 'required|string|min:2|max:120',
            'description' => 'nullable|string|max:1000',
            'display_order' => 'nullable|integer',
            'status' => 'nullable|in:active,inactive',
        ]);
        if (!$result['valid']) {
            Response::validation($result['errors']);
        }
        $this->created($this->serviceService->createCategory($result['data'], $this->userId()), 'Category created.');
    }

    public function update(Request $request): void
    {
        $this->requirePermission('categories.update');
        $result = Validator::validate($request->all(), [
            'name' => 'required|string|min:2|max:120',
            'description' => 'nullable|string|max:1000',
            'display_order' => 'nullable|integer',
            'status' => 'nullable|in:active,inactive',
        ]);
        if (!$result['valid']) {
            Response::validation($result['errors']);
        }
        $this->success($this->serviceService->updateCategory($this->routeId($request), $result['data'], $this->userId()), 'Category updated.');
    }

    public function destroy(Request $request): void
    {
        $this->requirePermission('categories.delete');
        $this->serviceService->deleteCategory($this->routeId($request), $this->userId());
        $this->success(null, 'Category deleted.');
    }
}
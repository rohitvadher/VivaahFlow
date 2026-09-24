<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\ServiceService;
use App\Validators\Validator;

class ServiceController extends BaseController
{
    public function __construct(private ServiceService $serviceService)
    {
    }

    public function index(Request $request): void
    {
        $this->requirePermission('services.read');
        $categoryId = $request->input('category_id');
        $this->success($this->serviceService->list(
            $this->page(),
            $this->perPage(),
            $this->search(),
            $categoryId !== null && $categoryId !== '' ? (int)$categoryId : null,
            $this->statusFilter()
        ));
    }

    public function show(Request $request): void
    {
        $this->requirePermission('services.read');
        $this->success($this->serviceService->find($this->routeId($request)));
    }

    public function store(Request $request): void
    {
        $this->requirePermission('services.create');
        $result = Validator::validate($request->all(), $this->rules());
        if (!$result['valid']) {
            Response::validation($result['errors']);
        }
        $this->created($this->serviceService->create($result['data'], $request->file('image'), $this->userId()), 'Service created.');
    }

    public function update(Request $request): void
    {
        $this->requirePermission('services.update');
        $result = Validator::validate($request->all(), $this->rules());
        if (!$result['valid']) {
            Response::validation($result['errors']);
        }
        $this->success($this->serviceService->update($this->routeId($request), $result['data'], $request->file('image'), $this->userId()), 'Service updated.');
    }

    public function status(Request $request): void
    {
        $this->requirePermission('services.update');
        $this->success($this->serviceService->changeStatus($this->routeId($request), (string)$request->input('status', ''), $this->userId()), 'Service status updated.');
    }

    public function destroy(Request $request): void
    {
        $this->requirePermission('services.delete');
        $this->serviceService->delete($this->routeId($request), $this->userId());
        $this->success(null, 'Service deleted.');
    }

    public function gallery(Request $request): void
    {
        $this->requirePermission('services.read');
        $this->success($this->serviceService->galleryFor($this->routeId($request)));
    }

    public function storeGallery(Request $request): void
    {
        $this->requirePermission('services.update');
        $this->created(
            $this->serviceService->addImage(
                $this->routeId($request),
                $request->file('image'),
                $request->input('caption'),
                $this->userId()
            ),
            'Gallery image uploaded.'
        );
    }

    public function updateGallery(Request $request): void
    {
        $this->requirePermission('services.update');
        $this->success(
            $this->serviceService->updateImage(
                $this->routeId($request),
                (int)$request->attribute('imageId', 0),
                $request->file('image'),
                $request->all(),
                $this->userId()
            ),
            'Gallery image updated.'
        );
    }

    public function primaryGallery(Request $request): void
    {
        $this->requirePermission('services.update');
        $this->success(
            $this->serviceService->setPrimary($this->routeId($request), (int)$request->attribute('imageId', 0), $this->userId()),
            'Primary image updated.'
        );
    }

    public function destroyGallery(Request $request): void
    {
        $this->requirePermission('services.update');
        $this->serviceService->deleteImage($this->routeId($request), (int)$request->attribute('imageId', 0), $this->userId());
        $this->success(null, 'Gallery image deleted.');
    }

    private function rules(): array
    {
        return [
            'category_id' => 'required|integer|exists:service_categories,id',
            'name' => 'required|string|min:2|max:160',
            'short_description' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'duration_minutes' => 'nullable|integer',
            'starting_price' => 'required|numeric',
            'is_featured' => 'nullable|boolean',
            'status' => 'nullable|in:active,inactive',
            'display_order' => 'nullable|integer',
        ];
    }
}
<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\ReviewService;
use App\Validators\Validator;

class ReviewController extends BaseController
{
    public function __construct(private ReviewService $reviewService)
    {
    }

    public function index(Request $request): void
    {
        $this->requirePermission('reviews.read');
        $this->success($this->reviewService->list($this->page(), $this->perPage(), $this->search(), $this->statusFilter()));
    }

    public function show(Request $request): void
    {
        $this->requirePermission('reviews.read');
        $this->success($this->reviewService->find($this->routeId($request)));
    }

    public function update(Request $request): void
    {
        $this->requirePermission('reviews.moderate');
        $result = Validator::validate($request->all(), [
            'status' => 'required|in:pending,approved,rejected',
            'is_visible' => 'nullable|boolean',
            'reply' => 'nullable|string|max:2000',
        ]);
        if (!$result['valid']) {
            Response::validation($result['errors']);
        }
        $this->success($this->reviewService->moderate($this->routeId($request), $result['data'], $this->userId()), 'Review updated.');
    }

    public function destroy(Request $request): void
    {
        $this->requirePermission('reviews.delete');
        $this->reviewService->delete($this->routeId($request), $this->userId());
        $this->success(null, 'Review deleted.');
    }
}
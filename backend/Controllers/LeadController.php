<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\LeadService;
use App\Validators\Validator;

class LeadController extends BaseController
{
    public function __construct(private LeadService $leadService)
    {
    }

    public function index(Request $request): void
    {
        $this->requirePermission('leads.read');
        $assignedTo = $request->input('assigned_to');
        $this->success($this->leadService->list(
            $this->page(),
            $this->perPage(),
            $this->search(),
            $this->statusFilter(),
            $assignedTo !== null && $assignedTo !== '' ? (int)$assignedTo : null
        ));
    }

    public function upcoming(Request $request): void
    {
        $this->requirePermission('leads.read');
        $this->success($this->leadService->upcoming());
    }

    public function show(Request $request): void
    {
        $this->requirePermission('leads.read');
        $this->success($this->leadService->find($this->routeId($request)));
    }

    public function store(Request $request): void
    {
        $this->requirePermission('leads.create');
        $result = Validator::validate($request->all(), $this->rules());
        if (!$result['valid']) {
            Response::validation($result['errors']);
        }
        $this->created($this->leadService->create($result['data'], $this->userId()), 'Lead created.');
    }

    public function update(Request $request): void
    {
        $this->requirePermission('leads.update');
        $result = Validator::validate($request->all(), $this->rules());
        if (!$result['valid']) {
            Response::validation($result['errors']);
        }
        $this->success($this->leadService->update($this->routeId($request), $result['data'], $this->userId()), 'Lead updated.');
    }

    public function status(Request $request): void
    {
        $this->requirePermission('leads.update');
        $this->success(
            $this->leadService->changeStatus(
                $this->routeId($request),
                (string)$request->input('status', ''),
                $this->userId(),
                ['lost_reason' => $request->input('lost_reason')]
            ),
            'Lead status updated.'
        );
    }

    public function assign(Request $request): void
    {
        $this->requirePermission('leads.update');
        $staffId = $request->input('assigned_to');
        $this->success(
            $this->leadService->assign($this->routeId($request), $staffId !== null && $staffId !== '' ? (int)$staffId : null, $this->userId()),
            'Lead assigned.'
        );
    }

    public function followup(Request $request): void
    {
        $this->requirePermission('followups.manage');
        $result = Validator::validate($request->all(), [
            'followup_date' => 'required|date',
            'note' => 'nullable|string|max:2000',
            'status' => 'nullable|in:done,missed,pending',
            'next_followup_at' => 'nullable|date',
        ]);
        if (!$result['valid']) {
            Response::validation($result['errors']);
        }
        $this->created($this->leadService->addFollowup($this->routeId($request), $result['data'], $this->userId()), 'Follow-up logged.');
    }

    public function destroy(Request $request): void
    {
        $this->requirePermission('leads.delete');
        $this->leadService->delete($this->routeId($request), $this->userId());
        $this->success(null, 'Lead deleted.');
    }

    private function rules(): array
    {
        return [
            'name' => 'required|string|min:2|max:120',
            'email' => 'nullable|email',
            'phone' => 'nullable|phone',
            'source' => 'nullable|string|max:60',
            'status' => 'nullable|in:new,contacted,follow_up,converted,lost',
            'assigned_to' => 'nullable|integer|exists:staff,id',
            'next_followup_at' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
        ];
    }
}
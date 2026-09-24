<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\EventService;
use App\Validators\Validator;

class EventController extends BaseController
{
    public function __construct(private EventService $eventService)
    {
    }

    public function index(Request $request): void
    {
        $this->requirePermission('events.read');
        $date = $request->input('date');
        $this->success($this->eventService->list(
            $this->page(),
            $this->perPage(),
            $date !== null && $date !== '' ? (string)$date : null,
            $this->statusFilter()
        ));
    }

    public function schedule(Request $request): void
    {
        $this->requirePermission('events.read');
        $date = (string)$request->input('date', date('Y-m-d'));
        $this->success([
            'date' => $date,
            'events' => $this->eventService->schedule($date, $this->statusFilter()),
        ]);
    }

    public function show(Request $request): void
    {
        $this->requirePermission('events.read');
        $this->success($this->eventService->find($this->routeId($request)));
    }

    public function store(Request $request): void
    {
        $this->requirePermission('events.create');
        $result = Validator::validate($request->all(), [
            'booking_id' => 'required|integer|exists:bookings,id',
            'title' => 'required|string|min:2|max:160',
            'event_date' => 'required|date',
            'start_time' => 'nullable|string|max:8',
            'end_time' => 'nullable|string|max:8',
            'venue_address' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
        ]);
        if (!$result['valid']) {
            Response::validation($result['errors']);
        }
        $this->created($this->eventService->create($result['data'], $this->userId()), 'Event created.');
    }

    public function update(Request $request): void
    {
        $this->requirePermission('events.update');
        $result = Validator::validate($request->all(), [
            'title' => 'required|string|min:2|max:160',
            'event_date' => 'required|date',
            'start_time' => 'nullable|string|max:8',
            'end_time' => 'nullable|string|max:8',
            'venue_address' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
        ]);
        if (!$result['valid']) {
            Response::validation($result['errors']);
        }
        $this->success($this->eventService->update($this->routeId($request), $result['data'], $this->userId()), 'Event updated.');
    }

    public function status(Request $request): void
    {
        $this->requirePermission('events.status');
        $this->success(
            $this->eventService->changeStatus($this->routeId($request), (string)$request->input('status', ''), $this->userId()),
            'Event status updated.'
        );
    }

    public function destroy(Request $request): void
    {
        $this->requirePermission('events.delete');
        $this->eventService->delete($this->routeId($request), $this->userId());
        $this->success(null, 'Event deleted.');
    }

    public function assign(Request $request): void
    {
        $this->requirePermission('assignments.manage');
        $result = Validator::validate($request->all(), [
            'staff_id' => 'required|integer|exists:staff,id',
            'role_note' => 'nullable|string|max:160',
        ]);
        if (!$result['valid']) {
            Response::validation($result['errors']);
        }
        $this->success(
            $this->eventService->assignStaff(
                $this->routeId($request),
                (int)$result['data']['staff_id'],
                $result['data']['role_note'],
                $this->userId()
            ),
            'Staff assigned.'
        );
    }

    public function unassign(Request $request): void
    {
        $this->requirePermission('assignments.manage');
        $this->eventService->removeAssignment((int)$request->attribute('assignmentId', 0), $this->userId());
        $this->success(null, 'Assignment removed.');
    }

    public function completeAssignment(Request $request): void
    {
        $this->requirePermission('assignments.manage');
        $this->eventService->completeAssignment((int)$request->attribute('assignmentId', 0), $this->userId());
        $this->success(null, 'Assignment completed.');
    }
}
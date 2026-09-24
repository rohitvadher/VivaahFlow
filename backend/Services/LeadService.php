<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\BusinessException;
use App\Helpers\Pagination;
use App\Repositories\LeadRepository;
use App\Repositories\StaffRepository;

class LeadService
{
    private const STATUSES = ['new', 'contacted', 'follow_up', 'converted', 'lost'];

    public function __construct(
        private LeadRepository $leadRepository,
        private StaffRepository $staffRepository,
        private NotificationService $notificationService,
        private ActivityService $activityService
    ) {
    }

    public function list(int $page, int $perPage, string $search, string $status, ?int $assignedTo): array
    {
        [$rows, $total] = $this->leadRepository->listPaginated($page, $perPage, $search, $status, $assignedTo);
        foreach ($rows as &$row) {
            $row['is_overdue'] = $row['next_followup_at'] !== null
                && $row['next_followup_at'] <= date('Y-m-d')
                && in_array($row['status'], ['new', 'contacted', 'follow_up'], true);
        }
        unset($row);
        return Pagination::build($rows, $total, $page, $perPage);
    }

    public function find(int $id): array
    {
        $lead = $this->leadRepository->findDetailed($id);
        if ($lead === null) {
            throw new BusinessException('Lead not found.', 404);
        }
        $lead['followups'] = $this->leadRepository->followups($id);
        return $lead;
    }

    public function create(array $data, ?int $userId): array
    {
        $this->assertStaff($data['assigned_to'] ?? null);
        $leadId = $this->leadRepository->insert([
            'enquiry_id' => !empty($data['enquiry_id']) ? (int)$data['enquiry_id'] : null,
            'customer_id' => !empty($data['customer_id']) ? (int)$data['customer_id'] : null,
            'name' => trim((string)$data['name']),
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'source' => $data['source'] ?? 'website',
            'status' => in_array($data['status'] ?? 'new', self::STATUSES, true) ? $data['status'] : 'new',
            'assigned_to' => !empty($data['assigned_to']) ? (int)$data['assigned_to'] : null,
            'next_followup_at' => $data['next_followup_at'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
        $this->activityService->log($userId, 'lead.created', 'lead', $leadId, 'Created lead ' . $data['name']);
        return $this->find($leadId);
    }

    public function createFromEnquiry(array $enquiry, ?int $userId): array
    {
        $existing = $this->leadRepository->findWhere(['enquiry_id' => (int)$enquiry['id']]);
        if ($existing !== null) {
            return $this->find((int)$existing['id']);
        }
        $leadId = $this->leadRepository->insert([
            'enquiry_id' => (int)$enquiry['id'],
            'customer_id' => !empty($enquiry['customer_id']) ? (int)$enquiry['customer_id'] : null,
            'name' => trim((string)($enquiry['customer_name'] ?? $enquiry['name'] ?? '')),
            'email' => $enquiry['customer_email'] ?? $enquiry['email'] ?? null,
            'phone' => $enquiry['customer_phone'] ?? $enquiry['phone'] ?? null,
            'source' => 'enquiry',
            'status' => 'new',
            'assigned_to' => null,
            'next_followup_at' => date('Y-m-d', strtotime('+1 day')),
            'notes' => 'Converted from enquiry ' . ($enquiry['reference_no'] ?? ''),
        ]);
        $this->notificationService->notifyAdmins(
            'New lead created',
            'Enquiry ' . ($enquiry['reference_no'] ?? '') . ' was converted into a lead.',
            'info',
            \App\Helpers\FrontendRoutes::path('manage.leads')
        );
        return $this->find($leadId);
    }

    public function update(int $id, array $data, ?int $userId): array
    {
        $lead = $this->leadRepository->findOrFail($id);
        $this->assertStaff($data['assigned_to'] ?? null);
        $status = $data['status'] ?? $lead['status'];
        if (!in_array($status, self::STATUSES, true)) {
            throw new BusinessException('Invalid lead status.');
        }
        $update = [
            'name' => $data['name'] ?? $lead['name'],
            'email' => array_key_exists('email', $data) ? $data['email'] : $lead['email'],
            'phone' => array_key_exists('phone', $data) ? $data['phone'] : $lead['phone'],
            'source' => $data['source'] ?? $lead['source'],
            'status' => $status,
            'assigned_to' => array_key_exists('assigned_to', $data) ? (!empty($data['assigned_to']) ? (int)$data['assigned_to'] : null) : $lead['assigned_to'],
            'next_followup_at' => array_key_exists('next_followup_at', $data) ? $data['next_followup_at'] : $lead['next_followup_at'],
            'notes' => array_key_exists('notes', $data) ? $data['notes'] : $lead['notes'],
        ];
        if ($status === 'converted' && $lead['converted_at'] === null) {
            $update['converted_at'] = date('Y-m-d H:i:s');
        }
        if ($status === 'lost' && $lead['lost_at'] === null) {
            $update['lost_at'] = date('Y-m-d H:i:s');
        }
        $this->leadRepository->update($id, $update);
        $this->activityService->log($userId, 'lead.updated', 'lead', $id, 'Updated lead ' . $update['name']);
        return $this->find($id);
    }

    public function changeStatus(int $id, string $status, ?int $userId, array $extra = []): array
    {
        $lead = $this->leadRepository->findOrFail($id);
        if (!in_array($status, self::STATUSES, true)) {
            throw new BusinessException('Invalid lead status.');
        }
        $update = ['status' => $status];
        if ($status === 'converted') {
            $update['converted_at'] = $lead['converted_at'] ?? date('Y-m-d H:i:s');
        }
        if ($status === 'lost') {
            $update['lost_at'] = $lead['lost_at'] ?? date('Y-m-d H:i:s');
            $update['lost_reason'] = $extra['lost_reason'] ?? $lead['lost_reason'];
        }
        $this->leadRepository->update($id, $update);
        $this->activityService->log($userId, 'lead.status', 'lead', $id, 'Set lead status to ' . $status);
        return $this->find($id);
    }

    public function assign(int $id, ?int $staffId, ?int $userId): array
    {
        $this->leadRepository->findOrFail($id);
        $this->assertStaff($staffId);
        $this->leadRepository->update($id, ['assigned_to' => $staffId ?: null]);
        $this->activityService->log($userId, 'lead.assigned', 'lead', $id, 'Assigned lead to staff #' . (string)$staffId);
        return $this->find($id);
    }

    public function addFollowup(int $id, array $data, ?int $userId): array
    {
        $lead = $this->leadRepository->findOrFail($id);
        $date = $data['followup_date'] ?? date('Y-m-d');
        $note = isset($data['note']) ? trim((string)$data['note']) : null;
        $this->leadRepository->addFollowup($id, $date, $note, $data['status'] ?? 'done', $userId);
        $next = $data['next_followup_at'] ?? null;
        if ($next !== null) {
            $this->leadRepository->update($id, ['next_followup_at' => $next]);
        }
        $this->activityService->log($userId, 'lead.followup', 'lead', $id, 'Logged a follow-up for lead ' . $lead['name']);
        return $this->find($id);
    }

    public function delete(int $id, ?int $userId): void
    {
        $lead = $this->leadRepository->findOrFail($id);
        $this->leadRepository->delete($id);
        $this->activityService->log($userId, 'lead.deleted', 'lead', $id, 'Deleted lead ' . $lead['name']);
    }

    public function upcoming(): array
    {
        return $this->leadRepository->upcomingFollowups(10);
    }

    private function assertStaff(?int $staffId): void
    {
        if ($staffId === null || $staffId <= 0) {
            return;
        }
        $staff = $this->staffRepository->find($staffId);
        if ($staff === null) {
            throw new BusinessException('Assigned staff member not found.', 404);
        }
    }
}
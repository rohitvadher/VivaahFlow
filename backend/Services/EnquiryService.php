<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\BusinessException;
use App\Database\Connection;
use App\Helpers\Pagination;
use App\Helpers\Reference;
use App\Repositories\CustomerRepository;
use App\Repositories\EnquiryRepository;

class EnquiryService
{
    public const VALID_STATUSES = [
        'new', 'contacted', 'quotation_pending', 'quotation_sent', 'converted', 'closed',
    ];

    public function __construct(
        private EnquiryRepository $enquiryRepository,
        private CustomerRepository $customerRepository,
        private QuotationService $quotationService,
        private LeadService $leadService,
        private NotificationService $notificationService,
        private ActivityService $activityService
    ) {
    }

    public function list(int $page, int $perPage, string $search, string $status): array
    {
        [$rows, $total] = $this->enquiryRepository->listPaginated($page, $perPage, $search, $status);
        return Pagination::build($rows, $total, $page, $perPage);
    }

    public function find(int $id): array
    {
        $enquiry = $this->enquiryRepository->findDetailed($id);
        if ($enquiry === null) {
            throw new BusinessException('Enquiry not found.', 404);
        }
        $enquiry['items'] = $this->enquiryRepository->items($id);
        return $enquiry;
    }

    public function submitWebsite(array $data, ?array $customerContext): array
    {
        $name = trim((string)($data['name'] ?? ''));
        $email = trim((string)($data['email'] ?? ''));
        $phone = trim((string)($data['phone'] ?? ''));
        $items = $data['items'] ?? null;

        if (!is_array($items) || count($items) === 0) {
            throw new BusinessException('Please select at least one service or package.');
        }

        $customer = null;
        if (isset($customerContext['customer_id']) && $customerContext['customer_id'] !== null) {
            $customer = $this->customerRepository->find((int)$customerContext['customer_id']);
        }
        $customer = $customer ?? ($email !== '' ? $this->customerRepository->findByEmail($email) : null);

        $enquiryId = Connection::transaction(function () use ($data, $name, $email, $phone, $items, $customer): int {
            if ($customer === null) {
                $customerId = $this->customerRepository->insert([
                    'user_id' => null,
                    'name' => $name,
                    'email' => $email !== '' ? $email : null,
                    'phone' => $phone !== '' ? $phone : null,
                    'source' => 'website',
                    'status' => 'active',
                ]);
            } else {
                $customerId = (int)$customer['id'];
                $this->customerRepository->update($customerId, [
                    'name' => $name !== '' ? $name : $customer['name'],
                    'phone' => $phone !== '' ? $phone : $customer['phone'],
                    'event_type' => $data['event_type'] ?? $customer['event_type'],
                ]);
            }

            $enquiryId = $this->enquiryRepository->insert([
                'reference_no' => Reference::next('enquiry'),
                'customer_id' => $customerId,
                'event_date' => $data['event_date'] ?? null,
                'event_type' => $data['event_type'] ?? null,
                'venue_address' => $data['venue_address'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => 'new',
            ]);

            $normalized = [];
            foreach ($items as $item) {
                $sourceType = ($item['source_type'] ?? 'service') === 'package' ? 'package' : 'service';
                $normalized[] = [
                    'source_type' => $sourceType,
                    'source_id' => (int)($item['source_id'] ?? 0),
                    'quantity' => max(1, (int)($item['quantity'] ?? 1)),
                    'notes' => $item['notes'] ?? null,
                ];
            }
            $this->enquiryRepository->replaceItems($enquiryId, $normalized);
            return $enquiryId;
        });

        $enquiry = $this->enquiryRepository->findDetailed($enquiryId);
        $this->notificationService->notifyAdmins(
            'New enquiry received',
            'Enquiry ' . $enquiry['reference_no'] . ' from ' . $enquiry['customer_name'],
            'info',
            \App\Helpers\FrontendRoutes::path('manage.enquiries')
        );
        $this->activityService->log(null, 'enquiry.created', 'enquiry', $enquiryId, 'Website enquiry from ' . $enquiry['customer_name']);
        return $this->find($enquiryId);
    }

    public function changeStatus(int $id, string $status, ?int $userId): array
    {
        if (!in_array($status, self::VALID_STATUSES, true)) {
            throw new BusinessException('Invalid enquiry status.');
        }
        $enquiry = $this->enquiryRepository->findOrFail($id);
        $this->enquiryRepository->update($id, ['status' => $status]);
        $this->activityService->log($userId, 'enquiry.status', 'enquiry', $id, 'Set enquiry status to ' . $status);
        return $this->find($id);
    }

    public function convertToQuotation(int $id, ?int $userId): array
    {
        $enquiry = $this->enquiryRepository->findOrFail($id);
        $quotation = $this->quotationService->createFromEnquiry($id, $userId);
        $this->enquiryRepository->update($id, ['status' => 'quotation_pending']);
        $this->notificationService->notifyAdmins(
            'Quotation created from enquiry',
            'Quotation ' . $quotation['reference_no'] . ' prepared for ' . $enquiry['reference_no'],
            'success',
            \App\Helpers\FrontendRoutes::path('manage.quotation', ['id' => $quotation['id']])
        );
        return $quotation;
    }

    public function convertToLead(int $id, ?int $userId): array
    {
        $enquiry = $this->enquiryRepository->findDetailed($id);
        if ($enquiry === null) {
            throw new BusinessException('Enquiry not found.', 404);
        }
        $lead = $this->leadService->createFromEnquiry($enquiry, $userId);
        $this->activityService->log($userId, 'lead.created', 'lead', (int)$lead['id'], 'Converted enquiry ' . $enquiry['reference_no'] . ' to lead');
        return $lead;
    }
}
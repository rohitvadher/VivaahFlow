<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Database\Connection;
use App\Helpers\Money;
use App\Repositories\CustomerRepository;
use App\Services\BookingService;
use App\Services\EnquiryService;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use App\Services\QuotationService;
use App\Services\ReviewService;
use App\Validators\Validator;

class PortalController extends BaseController
{
    public function __construct(
        private CustomerRepository $customerRepository,
        private EnquiryService $enquiryService,
        private QuotationService $quotationService,
        private BookingService $bookingService,
        private PaymentService $paymentService,
        private InvoiceService $invoiceService,
        private ReviewService $reviewService
    ) {
    }

    private function customerId(): int
    {
        $user = $this->user();
        if ($user === null || empty($user['customer']['id'])) {
            throw new \App\Core\BusinessException('Customer profile not found.', 404);
        }
        return (int)$user['customer']['id'];
    }

    public function profile(Request $request): void
    {
        $customer = $this->customerRepository->find($this->customerId());
        $this->success($customer);
    }

    public function updateProfile(Request $request): void
    {
        $result = Validator::validate($request->all(), [
            'name' => 'required|string|min:3|max:120',
            'phone' => 'nullable|phone',
            'wedding_date' => 'nullable|date',
            'event_type' => 'nullable|string|max:80',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
        ]);
        if (!$result['valid']) {
            Response::validation($result['errors']);
        }
        $data = $result['data'];
        $this->customerRepository->update($this->customerId(), [
            'name' => $data['name'],
            'phone' => $data['phone'],
            'wedding_date' => $data['wedding_date'] ?: null,
            'event_type' => $data['event_type'],
            'address' => $data['address'],
            'city' => $data['city'],
        ]);
        $user = $this->user();
        if ($user !== null && isset($user['id'])) {
            Connection::execute('UPDATE users SET name = ?, updated_at = NOW() WHERE id = ?', [(string)$data['name'], (int)$user['id']]);
            Session::set('user_name', (string)$data['name']);
        }
        $this->success($this->customerRepository->find($this->customerId()), 'Profile updated.');
    }

    public function enquiries(Request $request): void
    {
        $rows = Connection::fetchAll(
            'SELECT e.id, e.reference_no, e.event_date, e.event_type, e.venue_address, e.notes, e.status, e.created_at
             FROM enquiries e WHERE e.customer_id = ? ORDER BY e.created_at DESC',
            [$this->customerId()]
        );
        $this->success($rows);
    }

    public function storeEnquiry(Request $request): void
    {
        $result = Validator::validate($request->all(), [
            'event_date' => 'nullable|date',
            'event_type' => 'nullable|string|max:80',
            'venue_address' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
        ]);
        if (!$result['valid']) {
            Response::validation($result['errors']);
        }
        $user = $this->user();
        $enquiry = $this->enquiryService->submitWebsite($result['data'], [
            'customer_id' => $this->customerId(),
            'name' => $user['name'],
            'email' => $user['email'],
            'phone' => $user['phone'],
        ]);
        $this->created(['reference_no' => $enquiry['reference_no']], 'Enquiry submitted.');
    }

    public function quotations(Request $request): void
    {
        $this->success($this->quotationService->forCustomer($this->customerId()));
    }

    public function quotation(Request $request): void
    {
        $this->success($this->quotationService->findForCustomer($this->routeId($request), $this->customerId()));
    }

    public function acceptQuotation(Request $request): void
    {
        $quotation = $this->quotationService->findForCustomer($this->routeId($request), $this->customerId());
        $this->success($this->quotationService->accept((int)$quotation['id'], $this->userId(), 'customer'), 'Quotation accepted.');
    }

    public function rejectQuotation(Request $request): void
    {
        $quotation = $this->quotationService->findForCustomer($this->routeId($request), $this->customerId());
        $this->success(
            $this->quotationService->reject((int)$quotation['id'], $this->userId(), $request->input('reason')),
            'Quotation rejected.'
        );
    }

    public function bookings(Request $request): void
    {
        $this->success($this->bookingService->forCustomer($this->customerId()));
    }

    public function booking(Request $request): void
    {
        $this->success($this->bookingService->findForCustomer($this->routeId($request), $this->customerId()));
    }

    public function payments(Request $request): void
    {
        $rows = $this->paymentService->forCustomer($this->customerId());
        $paidCents = 0;
        foreach ($rows as $row) {
            if ($row['status'] === 'recorded') {
                $paidCents += Money::toCents($row['amount']);
            }
        }
        $this->success([
            'items' => $rows,
            'total_paid' => Money::format($paidCents),
        ]);
    }

    public function invoices(Request $request): void
    {
        $this->success($this->invoiceService->forCustomer($this->customerId()));
    }

    public function invoice(Request $request): void
    {
        $this->success($this->invoiceService->findForCustomer($this->routeId($request), $this->customerId()));
    }

    public function eligibleReviews(Request $request): void
    {
        $this->success($this->reviewService->eligibleForCustomer($this->customerId()));
    }

    public function reviews(Request $request): void
    {
        $this->success($this->reviewService->forCustomer($this->customerId()));
    }

    public function submitReview(Request $request): void
    {
        $result = Validator::validate($request->all(), [
            'booking_id' => 'required|integer|exists:bookings,id',
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:160',
            'comment' => 'required|string|min:5|max:2000',
        ]);
        if (!$result['valid']) {
            Response::validation($result['errors']);
        }
        $this->created($this->reviewService->submit($this->customerId(), $result['data'], $this->userId()), 'Thank you for your review.');
    }
}
<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Services\EnquiryService;

class EnquiryController extends BaseController
{
    public function __construct(private EnquiryService $enquiryService)
    {
    }

    public function index(Request $request): void
    {
        $this->requirePermission('enquiries.read');
        $this->success($this->enquiryService->list($this->page(), $this->perPage(), $this->search(), $this->statusFilter()));
    }

    public function show(Request $request): void
    {
        $this->requirePermission('enquiries.read');
        $this->success($this->enquiryService->find($this->routeId($request)));
    }

    public function status(Request $request): void
    {
        $this->requirePermission('enquiries.update');
        $this->success(
            $this->enquiryService->changeStatus($this->routeId($request), (string)$request->input('status', ''), $this->userId()),
            'Enquiry status updated.'
        );
    }

    public function toQuotation(Request $request): void
    {
        $this->requirePermission('enquiries.convert');
        $this->created($this->enquiryService->convertToQuotation($this->routeId($request), $this->userId()), 'Quotation created from enquiry.');
    }

    public function toLead(Request $request): void
    {
        $this->requirePermission('enquiries.convert');
        $this->created($this->enquiryService->convertToLead($this->routeId($request), $this->userId()), 'Lead created from enquiry.');
    }
}
<?php

declare(strict_types=1);

namespace App\Core;

use App\Middleware\EnsureCustomer;
use App\Middleware\EnsurePanelUser;

class Routes
{
    public static function register(Router $router): void
    {
        $panel = [EnsurePanelUser::class];
        $customer = [EnsureCustomer::class];

        $router->get('/health', 'HealthController@index');

        $router->get('/setup/status', 'SetupController@status');
        $router->post('/setup/install', 'SetupController@install');

        $router->post('/auth/login', 'AuthController@login');
        $router->post('/auth/register', 'AuthController@register');
        $router->post('/auth/logout', 'AuthController@logout');
        $router->get('/auth/csrf', 'AuthController@csrf');
        $router->get('/auth/me', 'AuthController@me');
        $router->post('/auth/change-password', 'AuthController@changePassword');
        $router->put('/auth/profile', 'AuthController@updateProfile');

        $router->get('/public/bootstrap', 'PublicController@bootstrap');
        $router->get('/public/home', 'PublicController@home');
        $router->get('/public/services', 'PublicController@services');
        $router->get('/public/services/{slug}', 'PublicController@service');
        $router->get('/public/packages', 'PublicController@packages');
        $router->get('/public/packages/{slug}', 'PublicController@package');
        $router->get('/public/gallery', 'PublicController@gallery');
        $router->get('/public/reviews', 'PublicController@reviews');
        $router->get('/public/offers', 'PublicController@offers');
        $router->post('/public/enquiries', 'PublicController@submitEnquiry');

        $router->get('/dashboard', 'DashboardController@index', $panel);

        $router->get('/customers', 'CustomerController@index', $panel);
        $router->post('/customers', 'CustomerController@store', $panel);
        $router->get('/customers/{id}', 'CustomerController@show', $panel);
        $router->put('/customers/{id}', 'CustomerController@update', $panel);
        $router->patch('/customers/{id}/status', 'CustomerController@status', $panel);
        $router->delete('/customers/{id}', 'CustomerController@destroy', $panel);

        $router->get('/categories', 'CategoryController@index', $panel);
        $router->post('/categories', 'CategoryController@store', $panel);
        $router->put('/categories/{id}', 'CategoryController@update', $panel);
        $router->delete('/categories/{id}', 'CategoryController@destroy', $panel);

        $router->get('/services', 'ServiceController@index', $panel);
        $router->post('/services', 'ServiceController@store', $panel);
        $router->get('/services/{id}', 'ServiceController@show', $panel);
        $router->post('/services/{id}', 'ServiceController@update', $panel);
        $router->patch('/services/{id}/status', 'ServiceController@status', $panel);
        $router->delete('/services/{id}', 'ServiceController@destroy', $panel);
        $router->get('/services/{id}/gallery', 'ServiceController@gallery', $panel);
        $router->post('/services/{id}/gallery', 'ServiceController@storeGallery', $panel);
        $router->post('/services/{id}/gallery/{imageId}', 'ServiceController@updateGallery', $panel);
        $router->post('/services/{id}/gallery/{imageId}/primary', 'ServiceController@primaryGallery', $panel);
        $router->delete('/services/{id}/gallery/{imageId}', 'ServiceController@destroyGallery', $panel);

        $router->get('/packages', 'PackageController@index', $panel);
        $router->post('/packages', 'PackageController@store', $panel);
        $router->get('/packages/{id}', 'PackageController@show', $panel);
        $router->post('/packages/{id}', 'PackageController@update', $panel);
        $router->patch('/packages/{id}/status', 'PackageController@status', $panel);
        $router->delete('/packages/{id}', 'PackageController@destroy', $panel);

        $router->get('/enquiries', 'EnquiryController@index', $panel);
        $router->get('/enquiries/{id}', 'EnquiryController@show', $panel);
        $router->patch('/enquiries/{id}/status', 'EnquiryController@status', $panel);
        $router->post('/enquiries/{id}/quotation', 'EnquiryController@toQuotation', $panel);
        $router->post('/enquiries/{id}/lead', 'EnquiryController@toLead', $panel);

        $router->get('/quotations', 'QuotationController@index', $panel);
        $router->get('/quotations/{id}', 'QuotationController@show', $panel);
        $router->post('/quotations/{id}', 'QuotationController@update', $panel);
        $router->patch('/quotations/{id}/status', 'QuotationController@status', $panel);
        $router->post('/quotations/{id}/accept', 'QuotationController@accept', $panel);
        $router->post('/quotations/{id}/reject', 'QuotationController@reject', $panel);

        $router->get('/bookings', 'BookingController@index', $panel);
        $router->post('/bookings', 'BookingController@store', $panel);
        $router->get('/bookings/{id}', 'BookingController@show', $panel);
        $router->post('/bookings/{id}', 'BookingController@update', $panel);
        $router->patch('/bookings/{id}/status', 'BookingController@status', $panel);
        $router->post('/bookings/{id}/schedule', 'BookingController@schedule', $panel);
        $router->delete('/bookings/{id}', 'BookingController@destroy', $panel);

        $router->get('/events', 'EventController@index', $panel);
        $router->get('/events/schedule', 'EventController@schedule', $panel);
        $router->post('/events', 'EventController@store', $panel);
        $router->get('/events/{id}', 'EventController@show', $panel);
        $router->post('/events/{id}', 'EventController@update', $panel);
        $router->patch('/events/{id}/status', 'EventController@status', $panel);
        $router->delete('/events/{id}', 'EventController@destroy', $panel);
        $router->post('/events/{id}/assignments', 'EventController@assign', $panel);
        $router->delete('/events/assignments/{assignmentId}', 'EventController@unassign', $panel);
        $router->post('/events/assignments/{assignmentId}/complete', 'EventController@completeAssignment', $panel);

        $router->get('/staff', 'StaffController@index', $panel);
        $router->get('/staff/active', 'StaffController@active', $panel);
        $router->post('/staff', 'StaffController@store', $panel);
        $router->get('/staff/{id}', 'StaffController@show', $panel);
        $router->put('/staff/{id}', 'StaffController@update', $panel);
        $router->patch('/staff/{id}/status', 'StaffController@status', $panel);
        $router->delete('/staff/{id}', 'StaffController@destroy', $panel);

        $router->get('/payments', 'PaymentController@index', $panel);
        $router->post('/payments', 'PaymentController@store', $panel);
        $router->get('/payments/{id}', 'PaymentController@show', $panel);
        $router->post('/payments/{id}/reverse', 'PaymentController@reverse', $panel);

        $router->get('/invoices', 'InvoiceController@index', $panel);
        $router->post('/invoices', 'InvoiceController@store', $panel);
        $router->get('/invoices/{id}', 'InvoiceController@show', $panel);
        $router->patch('/invoices/{id}/status', 'InvoiceController@status', $panel);
        $router->delete('/invoices/{id}', 'InvoiceController@destroy', $panel);

        $router->get('/reviews', 'ReviewController@index', $panel);
        $router->get('/reviews/{id}', 'ReviewController@show', $panel);
        $router->patch('/reviews/{id}', 'ReviewController@update', $panel);
        $router->delete('/reviews/{id}', 'ReviewController@destroy', $panel);

        $router->get('/offers', 'OfferController@index', $panel);
        $router->get('/offers/active', 'OfferController@active', $panel);
        $router->post('/offers', 'OfferController@store', $panel);
        $router->get('/offers/{id}', 'OfferController@show', $panel);
        $router->put('/offers/{id}', 'OfferController@update', $panel);
        $router->patch('/offers/{id}/status', 'OfferController@status', $panel);
        $router->delete('/offers/{id}', 'OfferController@destroy', $panel);

        $router->get('/leads', 'LeadController@index', $panel);
        $router->get('/leads/upcoming', 'LeadController@upcoming', $panel);
        $router->post('/leads', 'LeadController@store', $panel);
        $router->get('/leads/{id}', 'LeadController@show', $panel);
        $router->put('/leads/{id}', 'LeadController@update', $panel);
        $router->patch('/leads/{id}/status', 'LeadController@status', $panel);
        $router->post('/leads/{id}/assign', 'LeadController@assign', $panel);
        $router->post('/leads/{id}/followups', 'LeadController@followup', $panel);
        $router->delete('/leads/{id}', 'LeadController@destroy', $panel);

        $router->get('/reports/dashboard', 'ReportController@dashboard', $panel);
        $router->get('/reports/summary', 'ReportController@summary', $panel);
        $router->get('/reports/revenue', 'ReportController@revenue', $panel);
        $router->get('/reports/bookings', 'ReportController@bookings', $panel);

        $router->get('/notifications', 'NotificationController@index', $panel);
        $router->post('/notifications/read-all', 'NotificationController@readAll', $panel);
        $router->post('/notifications/{id}/read', 'NotificationController@read', $panel);

        $router->get('/settings', 'SettingsController@index', $panel);
        $router->put('/settings', 'SettingsController@update', $panel);
        $router->post('/settings/logo', 'SettingsController@logo', $panel);

        $router->get('/users', 'UserController@index', $panel);
        $router->get('/roles', 'UserController@roles', $panel);
        $router->post('/users', 'UserController@store', $panel);
        $router->get('/users/{id}', 'UserController@show', $panel);
        $router->put('/users/{id}', 'UserController@update', $panel);
        $router->patch('/users/{id}/status', 'UserController@status', $panel);
        $router->delete('/users/{id}', 'UserController@destroy', $panel);

        $router->get('/activity', 'ActivityController@index', $panel);

        $router->get('/portal/profile', 'PortalController@profile', $customer);
        $router->put('/portal/profile', 'PortalController@updateProfile', $customer);
        $router->get('/portal/enquiries', 'PortalController@enquiries', $customer);
        $router->post('/portal/enquiries', 'PortalController@storeEnquiry', $customer);
        $router->get('/portal/quotations', 'PortalController@quotations', $customer);
        $router->get('/portal/quotations/{id}', 'PortalController@quotation', $customer);
        $router->post('/portal/quotations/{id}/accept', 'PortalController@acceptQuotation', $customer);
        $router->post('/portal/quotations/{id}/reject', 'PortalController@rejectQuotation', $customer);
        $router->get('/portal/bookings', 'PortalController@bookings', $customer);
        $router->get('/portal/bookings/{id}', 'PortalController@booking', $customer);
        $router->get('/portal/payments', 'PortalController@payments', $customer);
        $router->get('/portal/invoices', 'PortalController@invoices', $customer);
        $router->get('/portal/invoices/{id}', 'PortalController@invoice', $customer);
        $router->get('/portal/reviews/eligible', 'PortalController@eligibleReviews', $customer);
        $router->get('/portal/reviews', 'PortalController@reviews', $customer);
        $router->post('/portal/reviews', 'PortalController@submitReview', $customer);
    }
}
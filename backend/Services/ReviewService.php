<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\BusinessException;
use App\Database\Connection;
use App\Helpers\Pagination;
use App\Repositories\CustomerRepository;
use App\Repositories\ReviewRepository;

class ReviewService
{
    public function __construct(
        private ReviewRepository $reviewRepository,
        private CustomerRepository $customerRepository,
        private NotificationService $notificationService,
        private ActivityService $activityService
    ) {
    }

    public function list(int $page, int $perPage, string $search, string $status): array
    {
        [$rows, $total] = $this->reviewRepository->listPaginated($page, $perPage, $search, $status);
        return Pagination::build($rows, $total, $page, $perPage);
    }

    public function find(int $id): array
    {
        $review = $this->reviewRepository->findDetailed($id);
        if ($review === null) {
            throw new BusinessException('Review not found.', 404);
        }
        return $review;
    }

    public function eligibleForCustomer(int $customerId): array
    {
        return Connection::fetchAll(
            'SELECT b.id, b.reference_no, b.event_date, b.event_type
             FROM bookings b
             LEFT JOIN reviews r ON r.booking_id = b.id
             WHERE b.customer_id = ?
               AND b.status = "completed"
               AND r.id IS NULL
             ORDER BY b.event_date DESC',
            [$customerId]
        );
    }

    public function submit(int $customerId, array $data, ?int $userId = null): array
    {
        $bookingId = (int)($data['booking_id'] ?? 0);
        $booking = Connection::fetchOne(
            'SELECT * FROM bookings WHERE id = ? AND customer_id = ?',
            [$bookingId, $customerId]
        );
        if ($booking === null) {
            throw new BusinessException('Booking not found.', 404);
        }
        if ($booking['status'] !== 'completed') {
            throw new BusinessException('You can only review a completed booking.');
        }
        $existing = $this->reviewRepository->findWhere(['booking_id' => $bookingId]);
        if ($existing !== null) {
            throw new BusinessException('You have already reviewed this booking.', 409);
        }
        $rating = (int)($data['rating'] ?? 0);
        if ($rating < 1 || $rating > 5) {
            throw new BusinessException('Please provide a rating between 1 and 5.');
        }
        $comment = trim((string)($data['comment'] ?? ''));
        if (mb_strlen($comment) < 5) {
            throw new BusinessException('Please write a short review.');
        }
        $reviewId = $this->reviewRepository->insert([
            'booking_id' => $bookingId,
            'customer_id' => $customerId,
            'service_id' => !empty($data['service_id']) ? (int)$data['service_id'] : null,
            'rating' => $rating,
            'title' => $data['title'] ?? null,
            'comment' => $comment,
            'status' => 'pending',
            'is_visible' => 0,
        ]);
        $this->notificationService->notifyAdmins(
            'New review submitted',
            'A new review is awaiting moderation.',
            'info',
            \App\Helpers\FrontendRoutes::path('manage.reviews')
        );
        $this->activityService->log($userId, 'review.created', 'review', $reviewId, 'Customer submitted a review');
        return $this->reviewRepository->findDetailed($reviewId);
    }

    public function moderate(int $id, array $data, ?int $userId): array
    {
        $review = $this->reviewRepository->findOrFail($id);
        $status = $data['status'] ?? $review['status'];
        if (!in_array($status, ['pending', 'approved', 'rejected'], true)) {
            throw new BusinessException('Invalid review status.');
        }
        $isVisible = array_key_exists('is_visible', $data) ? (!empty($data['is_visible']) ? 1 : 0) : (int)$review['is_visible'];
        if ($status !== 'approved') {
            $isVisible = 0;
        }
        $this->reviewRepository->update($id, [
            'status' => $status,
            'is_visible' => $isVisible,
            'reply' => array_key_exists('reply', $data) ? $data['reply'] : $review['reply'],
            'moderated_by' => $userId,
            'moderated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->activityService->log($userId, 'review.moderated', 'review', $id, 'Set review status to ' . $status);
        return $this->reviewRepository->findDetailed($id);
    }

    public function delete(int $id, ?int $userId): void
    {
        $this->reviewRepository->findOrFail($id);
        $this->reviewRepository->delete($id);
        $this->activityService->log($userId, 'review.deleted', 'review', $id, 'Deleted review');
    }

    public function forCustomer(int $customerId): array
    {
        return $this->customerRepository->reviewHistory($customerId);
    }
}
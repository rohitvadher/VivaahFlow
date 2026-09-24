<?php

declare(strict_types=1);

namespace App\Core;

class Response
{
    public static function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function success($data = null, string $message = '', int $status = 200): void
    {
        static::json(['success' => true, 'message' => $message, 'data' => $data], $status);
    }

    public static function created($data = null, string $message = 'Created successfully.'): void
    {
        static::success($data, $message, 201);
    }

    public static function error(string $message, int $status = 400, $errors = null): void
    {
        $body = ['success' => false, 'message' => $message];
        if ($errors !== null) {
            $body['errors'] = $errors;
        }
        static::json($body, $status);
    }

    public static function validation($errors, string $message = 'Validation failed.'): void
    {
        static::error($message, 422, $errors);
    }

    public static function notFound(string $message = 'Resource not found.'): void
    {
        static::error($message, 404);
    }

    public static function unauthorized(string $message = 'Please sign in to continue.'): void
    {
        static::error($message, 401);
    }

    public static function forbidden(string $message = 'You do not have permission to perform this action.'): void
    {
        static::error($message, 403);
    }

    public static function conflict(string $message): void
    {
        static::error($message, 409);
    }

    public static function tooMany(string $message = 'Too many failed attempts. Please try again later.'): void
    {
        static::error($message, 429);
    }
}
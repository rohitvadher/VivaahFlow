<?php

declare(strict_types=1);

namespace App\Core;

class BusinessException extends \RuntimeException
{
    private array $errors;

    public function __construct(string $message, int $status = 422, array $errors = [])
    {
        parent::__construct($message, $status);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
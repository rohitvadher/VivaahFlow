<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Connection;

class LoginAttemptRepository extends BaseRepository
{
    protected string $table = 'login_attempts';

    public function record(string $email, string $ip, bool $success): void
    {
        Connection::execute(
            'INSERT INTO login_attempts (email, ip_address, success) VALUES (?, ?, ?)',
            [$email, $ip, $success ? 1 : 0]
        );
    }

    public function recentFailures(string $email, string $ip, int $minutes): int
    {
        $since = date('Y-m-d H:i:s', time() - ($minutes * 60));
        return (int)Connection::fetchColumn(
            'SELECT COUNT(*) FROM login_attempts
             WHERE success = 0 AND attempted_at >= ? AND (email = ? OR ip_address = ?)',
            [$since, $email, $ip]
        );
    }
}
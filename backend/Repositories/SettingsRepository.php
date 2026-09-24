<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Connection;

class SettingsRepository extends BaseRepository
{
    protected string $table = 'settings';

    public function allGrouped(): array
    {
        $rows = Connection::fetchAll('SELECT * FROM settings ORDER BY category ASC, id ASC');
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['setting_key']] = $row['setting_value'];
        }
        return $grouped;
    }

    public function get(string $key, $default = null)
    {
        $value = Connection::fetchColumn('SELECT setting_value FROM settings WHERE setting_key = ?', [$key]);
        return ($value === false || $value === null) ? $default : $value;
    }

    public function set(string $key, $value): void
    {
        $exists = (int)Connection::fetchColumn(
            'SELECT COUNT(*) FROM settings WHERE setting_key = ?',
            [$key]
        );
        if ($exists > 0) {
            Connection::execute('UPDATE settings SET setting_value = ? WHERE setting_key = ?', [$value, $key]);
        } else {
            Connection::execute(
                'INSERT INTO settings (setting_key, setting_value, category) VALUES (?, ?, "general")',
                [$key, $value]
            );
        }
    }

    /**
     * @param string|null $prefetched Pass an already-loaded raw value to avoid
     * a second query (used by SettingsService which selects this key anyway).
     */
    public function isRegistrationOpen(?string $prefetched = null): bool
    {
        try {
            $raw = $prefetched ?? (string)$this->get('registration_open', '1');
            $value = strtolower(trim($raw));
        } catch (\Throwable $e) {
            return true;
        }
        return !in_array($value, ['', '0', 'false', 'no', 'off', 'closed'], true);
    }

    /**
     * Single-query upsert used by bulk settings saves.
     */
    public function setMany(array $values, string $category = 'general'): void
    {
        foreach ($values as $key => $value) {
            Connection::execute(
                'INSERT INTO settings (setting_key, setting_value, category) VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)',
                [$key, (string)$value, $category]
            );
        }
    }

    public function publicBranding(): array
    {
        $rows = Connection::fetchAll(
            'SELECT setting_key, setting_value FROM settings
             WHERE setting_key IN ("company_name", "tagline", "company_email", "company_phone",
                                   "company_address", "company_city", "currency", "logo_path",
                                   "footer_text", "currency_symbol", "timezone",
                                   "registration_open")'
        );
        $result = [];
        foreach ($rows as $row) {
            $result[$row['setting_key']] = $row['setting_value'];
        }
        return $result;
    }
}
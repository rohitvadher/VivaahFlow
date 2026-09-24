<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Database\Connection;
use App\Helpers\Pagination;

class ActivityController extends BaseController
{
    public function index(Request $request): void
    {
        $this->requirePermission('activity.read');
        $page = $this->page();
        $perPage = $this->perPage();
        $search = $this->search();
        $clauses = [];
        $params = [];
        if ($search !== '') {
            $clauses[] = '(a.action LIKE ? OR a.details LIKE ? OR u.name LIKE ?)';
            $term = '%' . $search . '%';
            array_push($params, $term, $term, $term);
        }
        $where = $clauses === [] ? '' : ' WHERE ' . implode(' AND ', $clauses);
        $total = (int)Connection::fetchColumn(
            'SELECT COUNT(*) FROM activity_logs a LEFT JOIN users u ON u.id = a.user_id' . $where,
            $params
        );
        $rows = Connection::fetchAll(
            'SELECT a.*, u.name AS user_name, r.name AS role_name
             FROM activity_logs a
             LEFT JOIN users u ON u.id = a.user_id
             LEFT JOIN roles r ON r.id = u.role_id' . $where . '
             ORDER BY a.created_at DESC, a.id DESC LIMIT ? OFFSET ?',
            array_merge($params, [$perPage, ($page - 1) * $perPage])
        );
        $this->success(Pagination::build($rows, $total, $page, $perPage));
    }
}
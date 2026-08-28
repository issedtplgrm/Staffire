<?php

class LeaveRequest
{
    private mysqli $connection;
    private ?int $id = null;
    private ?int $userId = null;
    private string $type = '';
    private string $startDate = '';
    private string $endDate = '';
    private string $reason = '';
    private string $status = 'pending';
    private ?int $managerId = null;
    private string $submittedByRole = 'employee';

    public function __construct(mysqli $connection)
    {
        $this->connection = $connection;
    }

    public function setId(int $id): self { $this->id = $id; return $this; }
    public function setUserId(int $userId): self { $this->userId = $userId; return $this; }
    public function setType(string $type): self { $this->type = $type; return $this; }
    public function setStartDate(string $date): self { $this->startDate = $date; return $this; }
    public function setEndDate(string $date): self { $this->endDate = $date; return $this; }
    public function setReason(string $reason): self { $this->reason = $reason; return $this; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }
    public function setManagerId(?int $managerId): self { $this->managerId = $managerId; return $this; }
    public function setSubmittedByRole(string $role): self { $this->submittedByRole = $role; return $this; }

    public function create(): bool
    {
        if ($this->userId === null) return false;
        $stmt = $this->connection->prepare("INSERT INTO leave_requests (user_id, type, start_date, end_date, reason, status, submitted_by_role) VALUES (?, ?, ?, ?, ?, 'pending', ?)");
        $stmt->bind_param('isssss', $this->userId, $this->type, $this->startDate, $this->endDate, $this->reason, $this->submittedByRole);
        return $stmt->execute();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->connection->prepare('SELECT lr.id, u.id AS user_id, u.role FROM leave_requests lr INNER JOIN users u ON lr.user_id = u.id WHERE lr.id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function update(): bool
    {
        if ($this->id === null) return false;
        $stmt = $this->connection->prepare('UPDATE leave_requests SET status = ?, manager_id = ? WHERE id = ?');
        $stmt->bind_param('sii', $this->status, $this->managerId, $this->id);
        return $stmt->execute();
    }

    public function delete(): bool
    {
        if ($this->id === null) return false;
        $stmt = $this->connection->prepare('DELETE FROM leave_requests WHERE id = ?');
        $stmt->bind_param('i', $this->id);
        return $stmt->execute();
    }

    public function updateStatus(int $id, string $status, int $managerId): bool
    {
        return $this->setId($id)->setStatus($status)->setManagerId($managerId)->update();
    }

    public function pending(array $conditions = [], array $params = [], string $types = ''): array
    {
        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        $sql = "SELECT lr.id AS leave_request_id, lr.user_id, lr.start_date, lr.end_date, DATEDIFF(lr.end_date, lr.start_date) + 1 AS duration, lr.type AS leave_type, lr.reason, lr.status, lr.created_at, u.full_name, u.email, d.name AS department_name FROM leave_requests lr JOIN users u ON lr.user_id = u.id LEFT JOIN departments d ON u.department_id = d.id $where ORDER BY lr.created_at DESC";
        $stmt = $this->connection->prepare($sql);
        if ($params) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

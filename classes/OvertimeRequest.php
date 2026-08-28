<?php

class OvertimeRequest
{
    private mysqli $connection;
    private ?int $id = null;
    private ?int $userId = null;
    private string $date = '';
    private string $start = '';
    private string $end = '';
    private float $hours = 0;
    private string $type = '';
    private string $reason = '';
    private ?string $work = null;
    private string $status = 'pending';
    private ?int $managerId = null;
    private string $submittedByRole = 'employee';

    public function __construct(mysqli $connection)
    {
        $this->connection = $connection;
    }

    public function setId(int $id): self { $this->id = $id; return $this; }
    public function setUserId(int $userId): self { $this->userId = $userId; return $this; }
    public function setDate(string $date): self { $this->date = $date; return $this; }
    public function setStart(string $start): self { $this->start = $start; return $this; }
    public function setEnd(string $end): self { $this->end = $end; return $this; }
    public function setHours(float $hours): self { $this->hours = $hours; return $this; }
    public function setType(string $type): self { $this->type = $type; return $this; }
    public function setReason(string $reason): self { $this->reason = $reason; return $this; }
    public function setWork(?string $work): self { $this->work = $work; return $this; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }
    public function setManagerId(?int $managerId): self { $this->managerId = $managerId; return $this; }
    public function setSubmittedByRole(string $role): self { $this->submittedByRole = $role; return $this; }

    public function create(): bool
    {
        if ($this->userId === null) return false;
        $stmt = $this->connection->prepare("INSERT INTO overtime_requests (user_id, overtime_date, start_time, end_time, total_hours, overtime_type, reason, work, status, manager_id, submitted_by_role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NULL, ?)");
        $stmt->bind_param('isssdssss', $this->userId, $this->date, $this->start, $this->end, $this->hours, $this->type, $this->reason, $this->work, $this->submittedByRole);
        return $stmt->execute();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->connection->prepare('SELECT submitted_by_role, user_id FROM overtime_requests WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function update(): bool
    {
        if ($this->id === null) return false;
        if ($this->managerId === null) {
            $stmt = $this->connection->prepare('UPDATE overtime_requests SET status = ? WHERE id = ?');
            $stmt->bind_param('si', $this->status, $this->id);
            return $stmt->execute();
        }
        $stmt = $this->connection->prepare('UPDATE overtime_requests SET status = ?, manager_id = ? WHERE id = ?');
        $stmt->bind_param('sii', $this->status, $this->managerId, $this->id);
        return $stmt->execute();
    }

    public function delete(): bool
    {
        if ($this->id === null) return false;
        $stmt = $this->connection->prepare('DELETE FROM overtime_requests WHERE id = ?');
        $stmt->bind_param('i', $this->id);
        return $stmt->execute();
    }

    public function updateStatus(int $id, string $status, ?int $managerId = null): bool
    {
        return $this->setId($id)->setStatus($status)->setManagerId($managerId)->update();
    }

    public function pending(int $limit = 5): array
    {
        $limit = max(1, (int) $limit);
        $stmt = $this->connection->prepare("SELECT ot.id AS overtime_request_id, ot.user_id, ot.overtime_date, ot.start_time, ot.end_time, ot.total_hours, ot.overtime_type, ot.status, ot.created_at, u.full_name, d.name AS department_name FROM overtime_requests ot JOIN users u ON ot.user_id = u.id LEFT JOIN departments d ON u.department_id = d.id WHERE ot.status = 'pending' ORDER BY ot.created_at DESC LIMIT ?");
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function all(array $conditions = [], array $params = [], string $types = ''): array
    {
        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        $sql = "SELECT ot.id AS overtime_request_id, ot.user_id, ot.overtime_date, ot.start_time, ot.end_time, ot.total_hours, ot.overtime_type, ot.reason, ot.work, ot.status, ot.created_at, ot.submitted_by_role, u.full_name, u.email, d.name AS department_name FROM overtime_requests ot JOIN users u ON ot.user_id = u.id LEFT JOIN departments d ON u.department_id = d.id $where ORDER BY ot.created_at DESC";
        $stmt = $this->connection->prepare($sql);
        if ($params) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

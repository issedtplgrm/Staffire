<?php

class Attendance
{
    private mysqli $connection;
    private ?int $id = null;
    private ?int $userId = null;
    private string $loginTime = '';
    private ?string $logoutTime = null;
    private string $status = 'present';

    public function __construct(mysqli $connection)
    {
        $this->connection = $connection;
    }

    public function setId(int $id): self { $this->id = $id; return $this; }
    public function setUserId(int $userId): self { $this->userId = $userId; return $this; }
    public function setLoginTime(string $loginTime): self { $this->loginTime = $loginTime; return $this; }
    public function setLogoutTime(?string $logoutTime): self { $this->logoutTime = $logoutTime; return $this; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }
    public function getId(): ?int { return $this->id; }

    public function activeToday(int $userId): ?array
    {
        $stmt = $this->connection->prepare('SELECT id, login_time, status FROM attendance WHERE user_id = ? AND DATE(login_time) = CURDATE() AND logout_time IS NULL LIMIT 1');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function timeoutOthers(int $userId): bool
    {
        $stmt = $this->connection->prepare('UPDATE attendance SET logout_time = NOW() WHERE DATE(login_time) = CURDATE() AND logout_time IS NULL AND user_id != ?');
        $stmt->bind_param('i', $userId);
        return $stmt->execute();
    }

    public function deleteToday(int $userId): bool
    {
        $stmt = $this->connection->prepare('DELETE FROM attendance WHERE user_id = ? AND DATE(login_time) = CURDATE()');
        $stmt->bind_param('i', $userId);
        return $stmt->execute();
    }

    public function create(): bool
    {
        if ($this->userId === null || $this->loginTime === '') return false;
        $stmt = $this->connection->prepare('INSERT INTO attendance (user_id, login_time, logout_time, status) VALUES (?, ?, NULL, ?)');
        $stmt->bind_param('iss', $this->userId, $this->loginTime, $this->status);
        return $stmt->execute();
    }

    public function update(): bool
    {
        if ($this->id === null) return false;
        $stmt = $this->connection->prepare('UPDATE attendance SET logout_time = ?, status = ? WHERE id = ?');
        $stmt->bind_param('ssi', $this->logoutTime, $this->status, $this->id);
        return $stmt->execute();
    }

    public function delete(): bool
    {
        if ($this->id === null) return false;
        $stmt = $this->connection->prepare('DELETE FROM attendance WHERE id = ?');
        $stmt->bind_param('i', $this->id);
        return $stmt->execute();
    }

    public function logout(): bool
    {
        if ($this->id === null) return false;
        $this->logoutTime = date('Y-m-d H:i:s');
        return $this->update();
    }

    public function getLogoutTime(int $attendanceId): ?string
    {
        $stmt = $this->connection->prepare('SELECT logout_time FROM attendance WHERE id = ?');
        $stmt->bind_param('i', $attendanceId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row['logout_time'] ?? null;
    }

    public function todayForUser(int $userId): ?array
    {
        $stmt = $this->connection->prepare('SELECT login_time, logout_time, status FROM attendance WHERE user_id = ? AND DATE(login_time) = CURDATE() LIMIT 1');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function all(array $conditions = [], array $params = [], string $types = ''): array
    {
        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        $sql = "SELECT a.id AS attendance_id, a.login_time, a.logout_time, a.status, u.full_name, d.name AS department_name FROM attendance a JOIN users u ON a.user_id = u.id LEFT JOIN departments d ON u.department_id = d.id $where ORDER BY a.login_time DESC";
        $stmt = $this->connection->prepare($sql);
        if ($params) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

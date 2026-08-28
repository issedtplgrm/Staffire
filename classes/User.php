<?php

class User
{
    private mysqli $connection;
    private ?int $id = null;
    private string $fullName = '';
    private string $username = '';
    private string $email = '';
    private string $password = '';
    private string $role = 'employee';
    private ?int $departmentId = null;

    public function __construct(mysqli $connection)
    {
        $this->connection = $connection;
    }

    public function setId(int $id): self { $this->id = $id; return $this; }
    public function setFullName(string $fullName): self { $this->fullName = $fullName; return $this; }
    public function setUsername(string $username): self { $this->username = $username; return $this; }
    public function setEmail(string $email): self { $this->email = $email; return $this; }
    public function setPassword(string $password): self { $this->password = $password; return $this; }
    public function setRole(string $role): self { $this->role = $role; return $this; }
    public function setDepartmentId(?int $departmentId): self { $this->departmentId = $departmentId; return $this; }

    public function getId(): ?int { return $this->id; }
    public function getPassword(): string { return $this->password; }
    public function getEmail(): string { return $this->email; }
    public function getUsername(): string { return $this->username; }
    public function getRole(): string { return $this->role; }

    public function findByLogin(string $login): ?array
    {
        $stmt = $this->connection->prepare('SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1');
        $stmt->bind_param('ss', $login, $login);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function find(int $id): ?array
    {
        $stmt = $this->connection->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function create(): bool
    {
        $this->password = password_hash($this->password, PASSWORD_DEFAULT);
        $stmt = $this->connection->prepare('INSERT INTO users (full_name, username, email, password, role, department_id) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('sssssi', $this->fullName, $this->username, $this->email, $this->password, $this->role, $this->departmentId);
        return $stmt->execute();
    }

    public function update(): bool
    {
        if ($this->id === null) return false;
        $stmt = $this->connection->prepare('UPDATE users SET full_name = ?, username = ?, email = ?, role = ?, department_id = ? WHERE id = ?');
        $stmt->bind_param('ssssii', $this->fullName, $this->username, $this->email, $this->role, $this->departmentId, $this->id);
        return $stmt->execute();
    }

    public function delete(): bool
    {
        if ($this->id === null) return false;
        $stmt = $this->connection->prepare('DELETE FROM users WHERE id = ?');
        $stmt->bind_param('i', $this->id);
        return $stmt->execute();
    }

    public function allWithDepartment(): mysqli_result
    {
        return $this->connection->query("SELECT u.id, u.full_name, u.username, u.email, u.role, u.department_id, d.name AS department FROM users u LEFT JOIN departments d ON u.department_id = d.id ORDER BY u.full_name ASC");
    }
}

<?php

class Department
{
    private mysqli $connection;
    private ?int $id = null;
    private string $name = '';

    public function __construct(mysqli $connection)
    {
        $this->connection = $connection;
    }

    public function setId(int $id): self { $this->id = $id; return $this; }
    public function setName(string $name): self { $this->name = $name; return $this; }

    public function create(): bool
    {
        $stmt = $this->connection->prepare('INSERT INTO departments (name) VALUES (?)');
        $stmt->bind_param('s', $this->name);
        return $stmt->execute();
    }

    public function update(): bool
    {
        if ($this->id === null) return false;
        $stmt = $this->connection->prepare('UPDATE departments SET name = ? WHERE id = ?');
        $stmt->bind_param('si', $this->name, $this->id);
        return $stmt->execute();
    }

    public function delete(): bool
    {
        if ($this->id === null) return false;
        $stmt = $this->connection->prepare('DELETE FROM departments WHERE id = ?');
        $stmt->bind_param('i', $this->id);
        return $stmt->execute();
    }

    public function all(): mysqli_result
    {
        return $this->connection->query('SELECT id, name FROM departments ORDER BY name');
    }
}

<?php

class Department
{
    private $connection;
    private $id = null;
    private $name = '';

    public function __construct($connection)
    {
        $this->connection = $connection;
    }

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function create()
    {
        $query = "INSERT INTO departments (name) VALUES (?)";
        $stmt = $this->connection->prepare($query);

        if (!$stmt) {
            die("SQL Error in create(): " . $this->connection->error);
        }

        $stmt->bind_param("s", $this->name);
        return $stmt->execute();
    }

    public function update()
    {
        if ($this->id == null) {
            return false;
        }

        $query = "UPDATE departments SET name = ? WHERE id = ?";
        $stmt = $this->connection->prepare($query);

        if (!$stmt) {
            die("SQL Error in update(): " . $this->connection->error);
        }

        $stmt->bind_param("si", $this->name, $this->id);
        return $stmt->execute();
    }

    public function delete()
    {
        if ($this->id == null) {
            return false;
        }

        $query = "DELETE FROM departments WHERE id = ?";
        $stmt = $this->connection->prepare($query);

        if (!$stmt) {
            die("SQL Error in delete(): " . $this->connection->error);
        }

        $stmt->bind_param("i", $this->id);
        return $stmt->execute();
    }

    public function all()
    {
        $query = "SELECT id, name FROM departments ORDER BY name";
        return $this->connection->query($query);
    }
}

<?php

class User
{
    private $connection;
    private $id = null;
    private $fullName = '';
    private $username = '';
    private $email = '';
    private $password = '';
    private $role = 'employee';
    private $departmentId = null;

    public function __construct($connection)
    {
        $this->connection = $connection;
    }

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }
    public function setFullName($fullName)
    {
        $this->fullName = $fullName;
        return $this;
    }
    public function setUsername($username)
    {
        $this->username = $username;
        return $this;
    }
    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }
    public function setPassword($password)
    {
        $this->password = $password;
        return $this;
    }
    public function setRole($role)
    {
        $this->role = $role;
        return $this;
    }
    public function setDepartmentId($departmentId)
    {
        $this->departmentId = $departmentId;
        return $this;
    }

    public function getId()
    {
        return $this->id;
    }
    public function getPassword()
    {
        return $this->password;
    }
    public function getEmail()
    {
        return $this->email;
    }
    public function getUsername()
    {
        return $this->username;
    }
    public function getRole()
    {
        return $this->role;
    }

    public function findByLogin($login)
    {
        $query = "SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1";
        $stmt = $this->connection->prepare($query);

        if (!$stmt) {
            die("SQL Error in findByLogin(): " . $this->connection->error);
        }

        $stmt->bind_param("ss", $login, $login);
        $stmt->execute();

        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function find($id)
    {
        $query = "SELECT * FROM users WHERE id = ?";
        $stmt = $this->connection->prepare($query);

        if (!$stmt) {
            die("SQL Error in find(): " . $this->connection->error);
        }

        $stmt->bind_param("i", $id);
        $stmt->execute();

        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function create()
    {
        $password = password_hash($this->password, PASSWORD_DEFAULT);

        $query = "INSERT INTO users
                  (full_name, username, email, password, role, department_id)
                  VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $this->connection->prepare($query);

        if (!$stmt) {
            die("SQL Error in create(): " . $this->connection->error);
        }

        $stmt->bind_param(
            "sssssi",
            $this->fullName,
            $this->username,
            $this->email,
            $password,
            $this->role,
            $this->departmentId
        );

        return $stmt->execute();
    }

    public function update()
    {
        if ($this->id == null) {
            return false;
        }

        $query = "UPDATE users
                  SET full_name = ?, username = ?, email = ?, role = ?, department_id = ?
                  WHERE id = ?";

        $stmt = $this->connection->prepare($query);

        if (!$stmt) {
            die("SQL Error in update(): " . $this->connection->error);
        }

        $stmt->bind_param(
            "ssssii",
            $this->fullName,
            $this->username,
            $this->email,
            $this->role,
            $this->departmentId,
            $this->id
        );

        return $stmt->execute();
    }

    public function delete()
    {
        if ($this->id == null) {
            return false;
        }

        $query = "DELETE FROM users WHERE id = ?";
        $stmt = $this->connection->prepare($query);

        if (!$stmt) {
            die("SQL Error in delete(): " . $this->connection->error);
        }

        $stmt->bind_param("i", $this->id);
        return $stmt->execute();
    }

    public function allWithDepartment()
    {
        $query = "SELECT u.id, u.full_name, u.username, u.email, u.role,
                         u.department_id, d.name AS department
                  FROM users u
                  LEFT JOIN departments d ON u.department_id = d.id
                  ORDER BY u.full_name ASC";

        return $this->connection->query($query);
    }
}

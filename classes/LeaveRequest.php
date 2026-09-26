<?php

class LeaveRequest
{
    private $connection;
    private $id = null;
    private $userId = null;
    private $type = '';
    private $startDate = '';
    private $endDate = '';
    private $reason = '';
    private $status = 'pending';
    private $managerId = null;
    private $submittedByRole = 'employee';

    public function __construct($connection)
    {
        $this->connection = $connection;
    }

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }
    public function setUserId($userId)
    {
        $this->userId = $userId;
        return $this;
    }
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }
    public function setStartDate($date)
    {
        $this->startDate = $date;
        return $this;
    }
    public function setEndDate($date)
    {
        $this->endDate = $date;
        return $this;
    }
    public function setReason($reason)
    {
        $this->reason = $reason;
        return $this;
    }
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }
    public function setManagerId($managerId)
    {
        $this->managerId = $managerId;
        return $this;
    }
    public function setSubmittedByRole($role)
    {
        $this->submittedByRole = $role;
        return $this;
    }

    public function create()
    {
        if ($this->userId == null) {
            return false;
        }

        $query = "INSERT INTO leave_requests
                  (user_id, type, start_date, end_date, reason, status, submitted_by_role)
                  VALUES (?, ?, ?, ?, ?, 'pending', ?)";

        $stmt = $this->connection->prepare($query);

        if (!$stmt) {
            die("SQL Error in create(): " . $this->connection->error);
        }

        $stmt->bind_param(
            "isssss",
            $this->userId,
            $this->type,
            $this->startDate,
            $this->endDate,
            $this->reason,
            $this->submittedByRole
        );

        return $stmt->execute();
    }

    public function find($id)
    {
        $query = "SELECT lr.id, u.id AS user_id, u.role
                  FROM leave_requests lr
                  INNER JOIN users u ON lr.user_id = u.id
                  WHERE lr.id = ?";

        $stmt = $this->connection->prepare($query);

        if (!$stmt) {
            die("SQL Error in find(): " . $this->connection->error);
        }

        $stmt->bind_param("i", $id);
        $stmt->execute();

        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function update()
    {
        if ($this->id == null) {
            return false;
        }

        $query = "UPDATE leave_requests
                  SET status = ?, manager_id = ?
                  WHERE id = ?";

        $stmt = $this->connection->prepare($query);

        if (!$stmt) {
            die("SQL Error in update(): " . $this->connection->error);
        }

        $stmt->bind_param("sii", $this->status, $this->managerId, $this->id);
        return $stmt->execute();
    }

    public function delete()
    {
        if ($this->id == null) {
            return false;
        }

        $query = "DELETE FROM leave_requests WHERE id = ?";
        $stmt = $this->connection->prepare($query);

        if (!$stmt) {
            die("SQL Error in delete(): " . $this->connection->error);
        }

        $stmt->bind_param("i", $this->id);
        return $stmt->execute();
    }

    public function updateStatus($id, $status, $managerId)
    {
        $this->setId($id);
        $this->setStatus($status);
        $this->setManagerId($managerId);

        return $this->update();
    }

    public function pending($conditions = [], $params = [], $types = '')
    {
        $where = '';

        if (!empty($conditions)) {
            $where = 'WHERE ' . implode(' AND ', $conditions);
        }

        $query = "SELECT
                    lr.id AS leave_request_id,
                    lr.user_id,
                    lr.start_date,
                    lr.end_date,
                    DATEDIFF(lr.end_date, lr.start_date) + 1 AS duration,
                    lr.type AS leave_type,
                    lr.reason,
                    lr.status,
                    lr.created_at,
                    u.full_name,
                    u.email,
                    d.name AS department_name
                  FROM leave_requests lr
                  INNER JOIN users u ON lr.user_id = u.id
                  LEFT JOIN departments d ON u.department_id = d.id
                  $where
                  ORDER BY lr.created_at DESC";

        $stmt = $this->connection->prepare($query);

        if (!$stmt) {
            die("SQL Error in pending(): " . $this->connection->error);
        }

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();

        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}

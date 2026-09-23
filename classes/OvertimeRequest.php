<?php

class OvertimeRequest
{
    private $connection;
    private $id = null;
    private $userId = null;
    private $date = '';
    private $start = '';
    private $end = '';
    private $hours = 0;
    private $type = '';
    private $reason = '';
    private $work = null;
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
    public function setDate($date)
    {
        $this->date = $date;
        return $this;
    }
    public function setStart($start)
    {
        $this->start = $start;
        return $this;
    }
    public function setEnd($end)
    {
        $this->end = $end;
        return $this;
    }
    public function setHours($hours)
    {
        $this->hours = $hours;
        return $this;
    }
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }
    public function setReason($reason)
    {
        $this->reason = $reason;
        return $this;
    }
    public function setWork($work)
    {
        $this->work = $work;
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

        $query = "INSERT INTO overtime_requests
                  (user_id, overtime_date, start_time, end_time, total_hours,
                   overtime_type, reason, work, status, manager_id, submitted_by_role)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NULL, ?)";

        $stmt = $this->connection->prepare($query);

        if (!$stmt) {
            die("SQL Error in create(): " . $this->connection->error);
        }

        $stmt->bind_param(
            "isssdssss",
            $this->userId,
            $this->date,
            $this->start,
            $this->end,
            $this->hours,
            $this->type,
            $this->reason,
            $this->work,
            $this->submittedByRole
        );

        return $stmt->execute();
    }

    public function find($id)
    {
        $query = "SELECT submitted_by_role, user_id
                  FROM overtime_requests
                  WHERE id = ?";

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

        if ($this->managerId == null) {
            $query = "UPDATE overtime_requests
                      SET status = ?
                      WHERE id = ?";

            $stmt = $this->connection->prepare($query);

            if (!$stmt) {
                die("SQL Error in update(): " . $this->connection->error);
            }

            $stmt->bind_param("si", $this->status, $this->id);
            return $stmt->execute();
        }

        $query = "UPDATE overtime_requests
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

        $query = "DELETE FROM overtime_requests WHERE id = ?";
        $stmt = $this->connection->prepare($query);

        if (!$stmt) {
            die("SQL Error in delete(): " . $this->connection->error);
        }

        $stmt->bind_param("i", $this->id);
        return $stmt->execute();
    }

    public function updateStatus($id, $status, $managerId = null)
    {
        $this->setId($id);
        $this->setStatus($status);
        $this->setManagerId($managerId);

        return $this->update();
    }

    public function pending($limit = 5)
    {
        $limit = (int) $limit;

        if ($limit < 1) {
            $limit = 1;
        }

        $query = "SELECT
                    ot.id AS overtime_request_id,
                    ot.user_id,
                    ot.overtime_date,
                    ot.start_time,
                    ot.end_time,
                    ot.total_hours,
                    ot.overtime_type,
                    ot.status,
                    ot.created_at,
                    u.full_name,
                    d.name AS department_name
                  FROM overtime_requests ot
                  INNER JOIN users u ON ot.user_id = u.id
                  LEFT JOIN departments d ON u.department_id = d.id
                  WHERE ot.status = 'pending'
                  ORDER BY ot.created_at DESC
                  LIMIT ?";

        $stmt = $this->connection->prepare($query);

        if (!$stmt) {
            die("SQL Error in pending(): " . $this->connection->error);
        }

        $stmt->bind_param("i", $limit);
        $stmt->execute();

        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function all($conditions = [], $params = [], $types = '')
    {
        $where = '';

        if (!empty($conditions)) {
            $where = 'WHERE ' . implode(' AND ', $conditions);
        }

        $query = "SELECT
                    ot.id AS overtime_request_id,
                    ot.user_id,
                    ot.overtime_date,
                    ot.start_time,
                    ot.end_time,
                    ot.total_hours,
                    ot.overtime_type,
                    ot.reason,
                    ot.work,
                    ot.status,
                    ot.created_at,
                    ot.submitted_by_role,
                    u.full_name,
                    u.email,
                    d.name AS department_name
                  FROM overtime_requests ot
                  INNER JOIN users u ON ot.user_id = u.id
                  LEFT JOIN departments d ON u.department_id = d.id
                  $where
                  ORDER BY ot.created_at DESC";

        $stmt = $this->connection->prepare($query);

        if (!$stmt) {
            die("SQL Error in all(): " . $this->connection->error);
        }

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();

        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}

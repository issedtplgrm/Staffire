<?php

class Attendance
{
    private $connection;
    private $id = null;
    private $userId = null;
    private $loginTime = '';
    private $logoutTime = null;
    private $status = 'present';

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
    public function setLoginTime($loginTime)
    {
        $this->loginTime = $loginTime;
        return $this;
    }
    public function setLogoutTime($logoutTime)
    {
        $this->logoutTime = $logoutTime;
        return $this;
    }
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    public function activeToday($userId)
    {
        $query = "SELECT id, login_time, status
                  FROM attendance
                  WHERE user_id = ?
                  AND DATE(login_time) = CURDATE()
                  AND logout_time IS NULL
                  LIMIT 1";

        $stmt = $this->connection->prepare($query);

        if (!$stmt) {
            die("SQL Error in activeToday(): " . $this->connection->error);
        }

        $stmt->bind_param("i", $userId);
        $stmt->execute();

        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function timeoutOthers($userId)
    {
        $query = "UPDATE attendance
                  SET logout_time = NOW()
                  WHERE DATE(login_time) = CURDATE()
                  AND logout_time IS NULL
                  AND user_id != ?";

        $stmt = $this->connection->prepare($query);

        if (!$stmt) {
            die("SQL Error in timeoutOthers(): " . $this->connection->error);
        }

        $stmt->bind_param("i", $userId);
        return $stmt->execute();
    }

    public function deleteToday($userId)
    {
        $query = "DELETE FROM attendance
                  WHERE user_id = ?
                  AND DATE(login_time) = CURDATE()";

        $stmt = $this->connection->prepare($query);

        if (!$stmt) {
            die("SQL Error in deleteToday(): " . $this->connection->error);
        }

        $stmt->bind_param("i", $userId);
        return $stmt->execute();
    }

    public function create()
    {
        if ($this->userId == null || $this->loginTime == '') {
            return false;
        }

        $query = "INSERT INTO attendance
                  (user_id, login_time, logout_time, status)
                  VALUES (?, ?, NULL, ?)";

        $stmt = $this->connection->prepare($query);

        if (!$stmt) {
            die("SQL Error in create(): " . $this->connection->error);
        }

        $stmt->bind_param("iss", $this->userId, $this->loginTime, $this->status);
        return $stmt->execute();
    }

    public function update()
    {
        if ($this->id == null) {
            return false;
        }

        $query = "UPDATE attendance
                  SET logout_time = ?, status = ?
                  WHERE id = ?";

        $stmt = $this->connection->prepare($query);

        if (!$stmt) {
            die("SQL Error in update(): " . $this->connection->error);
        }

        $stmt->bind_param("ssi", $this->logoutTime, $this->status, $this->id);
        return $stmt->execute();
    }

    public function delete()
    {
        if ($this->id == null) {
            return false;
        }

        $query = "DELETE FROM attendance WHERE id = ?";
        $stmt = $this->connection->prepare($query);

        if (!$stmt) {
            die("SQL Error in delete(): " . $this->connection->error);
        }

        $stmt->bind_param("i", $this->id);
        return $stmt->execute();
    }

    public function logout()
    {
        if ($this->id == null) {
            return false;
        }

        $this->logoutTime = date('Y-m-d H:i:s');
        return $this->update();
    }

    public function getLogoutTime($attendanceId)
    {
        $query = "SELECT logout_time FROM attendance WHERE id = ?";
        $stmt = $this->connection->prepare($query);

        if (!$stmt) {
            die("SQL Error in getLogoutTime(): " . $this->connection->error);
        }

        $stmt->bind_param("i", $attendanceId);
        $stmt->execute();

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        return $row['logout_time'] ?? null;
    }

    public function todayForUser($userId)
    {
        $query = "SELECT login_time, logout_time, status
                  FROM attendance
                  WHERE user_id = ?
                  AND DATE(login_time) = CURDATE()
                  LIMIT 1";

        $stmt = $this->connection->prepare($query);

        if (!$stmt) {
            die("SQL Error in todayForUser(): " . $this->connection->error);
        }

        $stmt->bind_param("i", $userId);
        $stmt->execute();

        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function all($conditions = [], $params = [], $types = '')
    {
        $where = '';

        if (!empty($conditions)) {
            $where = 'WHERE ' . implode(' AND ', $conditions);
        }

        $query = "SELECT
                    a.id AS attendance_id,
                    a.login_time,
                    a.logout_time,
                    a.status,
                    u.full_name,
                    d.name AS department_name
                  FROM attendance a
                  INNER JOIN users u ON a.user_id = u.id
                  LEFT JOIN departments d ON u.department_id = d.id
                  $where
                  ORDER BY a.login_time DESC";

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

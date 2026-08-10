<?php
    require_once __DIR__ . '/../config/db.php';
    require_once __DIR__ . '/../process/access_control.php';

    session_start();
    //check if a user is logged in
    if (!isset($_SESSION['id'])) {
    header("Location: ../auth/login.php");
    exit();
}

//added status of leave
$attendance_sql = "
    SELECT
        users.full_name,
        departments.name AS department,
        attendance.login_time,
        attendance.logout_time,
        leave_requests.status AS leave_status,
        CASE
            WHEN leave_requests.user_id IS NOT NULL THEN 'on leave'
            WHEN attendance.id IS NULL THEN 'absent'
            ELSE attendance.status
        END AS status
    FROM users
    LEFT JOIN attendance
        ON attendance.user_id = users.id
        AND DATE(attendance.login_time) = CURDATE()
    LEFT JOIN departments
        ON users.department_id = departments.id
     LEFT JOIN leave_requests
        ON leave_requests.user_id = users.id
        AND leave_requests.status = 'approved'
        AND CURDATE() BETWEEN leave_requests.start_date AND leave_requests.end_date
    ORDER BY attendance.login_time DESC, users.full_name ASC
";

$attendance_result = $connection->query($attendance_sql);

// Counters
$attendance_rows = [];
$present_count = 0;
$absent_count = 0;
$leave_req = 0;
$on_leave = 0;

// Save attendance rows and count statuses
while ($row = $attendance_result->fetch_assoc()) {
    $attendance_rows[] = $row;

    if($row["leave_status"] === 'approved'){
        $on_leave++;
    } else if ($row["status"] === "absent") {
        $absent_count++;
    } else {
        $present_count++;
    } 
}

$all_count = count($attendance_rows);

$department_sql = "SELECT * FROM departments";
$departments = $connection->query($department_sql);

//get the current page that is opened
$current_page = basename($_SERVER['PHP_SELF']);
 ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Requests</title>
 
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alata&family=Geist+Pixel&family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/adminDashboard.css">
    <link rel="stylesheet" href="../assets/css/leaveRequests.css">
</head>
<body>
   <header class="header">
        <nav>
            <!-- All -->
            <a href="dashboard.php" class="<?= $current_page === 'dashboard.php' ? 'active' : '' ?>">Home</a>
            <!-- Admin and Manager -->
            <?php if (isRole("admin") || isRole("manager")): ?>
                <a href="leaveRequests.php" class="<?= $current_page === 'leaveRequests.php' ? 'active' : '' ?>">Leave Requests</a>
            <?php endif; ?>

            <!-- Employee and Manager(?) -->
            <?php if (isRole("manager") || isRole("employee")): ?>
                <a href="requestLeave.php" class="<?= $current_page === 'requestLeave.php' ? 'active' : '' ?>">Request Leave</a>
            <?php endif; ?>

            <!-- Admin and Manager -->
            <?php if (isRole("admin") || isRole("manager")): ?>
                <a href="attendanceRecords.php" class="<?= $current_page === 'attendanceRecords.php' ? 'active' : '' ?>"> Attendance Records</a>
            <?php endif; ?>

            <!-- Admin -->
            <?php if (isRole("admin")): ?>
                <a href="manageEmployees.php" class="<?= $current_page === 'manageEmployees.php' ? 'active' : '' ?>">Manage Employees</a>
            <?php endif; ?>
        </nav>

        <div class="header-items">
            <a href=""><img src="../assets/img/notifbell-icon.png" class="notifbell-icon" alt=""></a>
            <div class="pfp"></div>
        </div>
    </header>

    <main class="main-cont">
        <section class="stats">

            <!-- Statistics cards -->
            <div class="stat-card">
                <div class="icon-cont">
                    <div class="emp-icon-cont">
                        <img
                            src="../assets/img/emps-icon.png"
                            class="emp-icon"
                            alt="">
                    </div>
                </div>

                <div class="stats-info">
                    <p class="stat-title">Total Employee</p>
                    <p class="stat-value"><?= $all_count ?></p>
                </div>
            </div>

            <div class="stat-card">
                <div class="icon-cont">
                    <div class="present-icon-cont">
                        <img
                            src="../assets/img/present-icon.png"
                            class="present-icon"
                            alt="">
                    </div>
                </div>

                <div class="stats-info">
                    <p class="stat-title">Present Today</p>
                    <p class="stat-value"><?= $present_count ?></p>
                </div>
            </div>

            <div class="stat-card">
                <div class="icon-cont">
                    <div class="onleave-icon-cont">
                        <img
                            src="../assets/img/onleave-icon.png"
                            class="onleave-icon"
                            alt="">
                    </div>
                </div>

                <div class="stats-info">
                    <p class="stat-title">On Leave</p>
                    <p class="stat-value"><?= $on_leave ?></p>
                </div>
            </div>
        </section>

        <div class="lr-table-wrap">
            <div class="lr-toolbar">
                <h3>Leave Requests</h3>
 
                <input type="text" id="lr-search" class="lr-search" placeholder="Search Employee Name...">
 
                <select id="lr-department" class="lr-select">
                    <option value="all">All Departments</option>
                    <?php while ($d = $departments->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($d['name']) ?>"><?= htmlspecialchars($d['name']) ?></option>
                    <?php endwhile; ?>
                </select>
 
                <select id="lr-status" class="lr-select">
                    <option value="all">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
 
                <input type="date" id="lr-date-from" class="lr-date">
                <input type="date" id="lr-date-to" class="lr-date">
            </div>
 
            <table>
                <thead>
                    <tr>
                        <th>Employee Name</th>
                        <th>Department</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Duration</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Requested On</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="lr-table-body"></tbody>
            </table>
        </div>
    </main>
    <script src="../assets/js/leaveRequests.js"></script>
</body>
</html>
<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../process/access_control.php';

session_start();

// Check if a user is logged in
if (!isset($_SESSION['id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$username = $_SESSION["username"];

// Latest leave requests
$leave_sql = "
    SELECT lr.start_date, lr.status
    FROM leave_requests lr
    JOIN users u ON lr.user_id = u.id
    WHERE u.username = ?
    ORDER BY lr.created_at DESC
    LIMIT 5
";

$leaveStmt = $connection->prepare($leave_sql);
$leaveStmt->bind_param("s", $username);
$leaveStmt->execute();
$leaveResult = $leaveStmt->get_result();

// Latest overtime requests
$ot_sql = "
    SELECT orq.overtime_date, orq.status
    FROM overtime_requests orq
    JOIN users u ON orq.user_id = u.id
    WHERE u.username = ?
    ORDER BY orq.created_at DESC
    LIMIT 5
";

$otStmt = $connection->prepare($ot_sql);
$otStmt->bind_param("s", $username);
$otStmt->execute();
$otResult = $otStmt->get_result();

$where = "WHERE users.role != 'admin'";

// Attendance query
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
            ELSE 'present'
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
        $where
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

    if ($row["leave_status"] === 'approved') {
        $on_leave++;
    } else if ($row["status"] === "absent") {
        $absent_count++;
    } else if ($row["status"] === "present") {
        $present_count++;
    }
}

$all_count = count($attendance_rows);

$department_sql = "SELECT * FROM departments";
$departments = $connection->query($department_sql);

// Get the current page that is opened
$current_page = basename($_SERVER['PHP_SELF']);

// Get name
$username = getUsername();

// Get current user's role
$user_role = $_SESSION['role'];
$user_id = $_SESSION['id'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Overtime Requests</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Alata&family=Geist+Pixel&family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/adminDashboard.css">
    <!-- <link rel="stylesheet" href="../assets/css/empDashboard.css"> -->
    <link rel="stylesheet" href="../assets/css/overtimeRequest.css">
</head>

<body>

    <sidebar class="sidebar">
        <div class="sidebar-brand">
            <div>
                <img src="../assets/img/staffire-logo.png" class="staffire-icon" alt="staffire-icon">
            </div>
            <div>
                <a href="dashboard.php"><span>STAFF</span>IRE</a>  
            </div>
        </div>
        <nav>
            <!-- All -->
                <a href="dashboard.php" class="<?= $current_page === 'dashboard.php' ? 'active' : '' ?>"><svg xmlns="http://www.w3.org/2000/svg"
                    fill="#e3dada" viewBox="0 0 24 24">
                    <path d="M3 13h1v7c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2v-7h1c.4 0 .77-.24.92-.62.15-.37.07-.8-.22-1.09l-8.99-9a.996.996 0 0 0-1.41 0l-9.01 9c-.29.29-.37.72-.22 1.09s.52.62.92.62Zm9-8.59 6 6V20H6v-9.59z"></path>
                </svg>Dashboard</a>

                <!-- Admin and Manager -->
                <?php if (isRole("admin") || isRole("manager")): ?>
                    <a href="../pages/leaveRequests.php" class="<?= $current_page === 'leaveRequests.php' ? 'active' : '' ?>"><svg xmlns="http://www.w3.org/2000/svg" width="256" height="256"
                            fill="#e3dada" viewBox="0 0 24 24">
                            <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2M5 19V5h14v14z"></path>
                            <path d="M8.5 10.5a1.5 1.5 0 1 0 0 3 1.5 1.5 0 1 0 0-3m2.5.5h6v2h-6zM7 7h10v2H7zm0 8h10v2H7z"></path>
                        </svg>Leave Requests</a>
                <?php endif; ?>

                <!-- Admin and Manager -->
                <?php if (isRole("admin") || isRole("manager")): ?>
                    <a href="overtimeRequests.php" class="<?= $current_page === 'overtimeRequests.php' ? 'active' : '' ?>"><svg xmlns="http://www.w3.org/2000/svg" width="256" height="256"
                            fill="#e3dada" viewBox="0 0 24 24">
                            <path d="M12 2C6.58 2 2 6.58 2 12s4.58 10 10 10 10-4.58 10-10S17.42 2 12 2m0 18c-4.34 0-8-3.66-8-8s3.66-8 8-8 8 3.66 8 8-3.66 8-8 8"></path>
                            <path d="M13 7h-2v6h6v-2h-4z"></path>
                        </svg>Overtime Requests</a>
                <?php endif; ?>

                <!-- Admin and Manager -->
                <?php if (isRole("admin") || isRole("manager")): ?>
                    <a href="attendanceRecords.php" class="<?= $current_page === 'attendanceRecords.php' ? 'active' : '' ?>"><svg xmlns="http://www.w3.org/2000/svg" width="256" height="256"
                            fill="#e3dada" viewBox="0 0 24 24">
                            <path d="M19 3h-2c0-.55-.45-1-1-1H8c-.55 0-1 .45-1 1H5c-1.1 0-2 .9-2 2v15c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2m0 17H5V5h2v2h10V5h2z"></path>
                            <path d="M11 14.09 8.71 11.8 7.3 13.21l3 3c.2.2.45.29.71.29s.51-.1.71-.29l5-5-1.41-1.41-4.29 4.29Z"></path>
                        </svg> Attendance Records</a>
                <?php endif; ?>

                <!-- Admin -->
                <?php if (isRole("admin")): ?>
                    <a href="manageEmployees.php" class="<?= $current_page === 'manageEmployees.php' ? 'active' : '' ?>"><svg xmlns="http://www.w3.org/2000/svg" width="256" height="256"
                            fill="#e3dada" viewBox="0 0 24 24">
                            <path d="M12 11c1.71 0 3-1.29 3-3s-1.29-3-3-3-3 1.29-3 3 1.29 3 3 3m0-4c.6 0 1 .4 1 1s-.4 1-1 1-1-.4-1-1 .4-1 1-1m1 5h-2c-2.76 0-5 2.24-5 5v.5c0 .83.67 1.5 1.5 1.5h9c.83 0 1.5-.67 1.5-1.5V17c0-2.76-2.24-5-5-5m-5 5c0-1.65 1.35-3 3-3h2c1.65 0 3 1.35 3 3zm-1.5-6c.47 0 .9-.12 1.27-.33a5.03 5.03 0 0 1-.42-4.52C7.09 6.06 6.8 6 6.5 6 5.06 6 4 7.06 4 8.5S5.06 11 6.5 11m-.39 1H5.5C3.57 12 2 13.57 2 15.5v1c0 .28.22.5.5.5H4c0-1.96.81-3.73 2.11-5m11.39-1c1.44 0 2.5-1.06 2.5-2.5S18.94 6 17.5 6c-.31 0-.59.06-.85.15a5.03 5.03 0 0 1-.42 4.52c.37.21.79.33 1.27.33m1 1h-.61A6.97 6.97 0 0 1 20 17h1.5c.28 0 .5-.22.5-.5v-1c0-1.93-1.57-3.5-3.5-3.5"></path>
                        </svg>Manage Employees</a>
                <?php endif; ?>

            <hr>
            <?php if (isRole("manager")): ?>

                <!-- Request Status -->
                <div class="sidebar-section">

                    <h4>My Leave Requests</h4>


                    <ul class="req-list">
                        <?php while ($row = $leaveResult->fetch_assoc()): ?>

                            <li class="req-item">

                                <span class="req-date">
                                    <?= htmlspecialchars($row['start_date']) ?>
                                </span>

                                <span class="status-badge status-<?= strtolower($row['status']) ?>">
                                    <?= htmlspecialchars(ucfirst($row['status'])) ?>
                                </span>

                            </li>

                        <?php endwhile; ?>
                    </ul>

                    <h4>My Overtime Requests</h4>

                    <ul class="req-list">
                        <?php while ($row = $otResult->fetch_assoc()): ?>

                            <li class="req-item">

                                <span class="req-date">
                                    <?= htmlspecialchars($row['overtime_date']) ?>
                                </span>

                                <span class="status-badge status-<?= strtolower($row['status']) ?>">
                                    <?= htmlspecialchars(ucfirst($row['status'])) ?>
                                </span>

                            </li>

                        <?php endwhile; ?>
                    </ul>

                </div>

            <?php endif; ?>

        </nav>

    </sidebar>

    <main class="main-cont">

        <header class="top-header">

            <div class="header-items">

                <!-- Left part -->
                <div>

                    <h1>
                        Welcome,
                        <span>
                            <?php echo htmlspecialchars(ucfirst($username)) ?>!
                        </span>
                    </h1>

                </div>

                <!-- Right part -->
                <div class="header-items-right">

                    <div class="notifbell-wrap">

                        <img
                            src="../assets/img/notifbell-icon.png"
                            class="notifbell-icon"
                            alt=""
                            onclick="showNotifs">

                        <span
                            class="notif-badge hidden"
                            id="notif-badge">
                        </span>

                    </div>

                    <div class="user">

                        <div
                            class="pfp"
                            onclick="showMenu()">
                        </div>

                        <p>
                            <?php echo htmlspecialchars(ucfirst($username)) ?>
                        </p>

                    </div>

                </div>

            </div>

        </header>

        <!-- Notification -->
        <div class="notif-wrap" id="notifs">

            <div class="notifs">

                <div class="notif-panel-header">

                    <h4>
                        Notifications
                    </h4>

                </div>

                <hr>

                <div
                    class="notif-list"
                    id="notif-list">
                </div>

            </div>

        </div>

        <!-- Profile Menu -->
        <div class="pfp-menu-wrap" id="pfp-menu">

            <div class="pfp-menu">

                <div class="user-info">

                    <h2>
                        <?php echo htmlspecialchars($username) ?>
                    </h2>

                </div>

                <hr>

                <!-- <a href="#">IN CASE OF ADDING A NEW PAGE</a> -->

                <form action="../process/logout.php">

                    <button
                        type="submit"
                        class="logout-btn"
                        href="../process/logout.php">

                        <img
                            src="../assets/img/logout-icon.png"
                            class="logout-icon"
                            alt="">

                        <span>
                            Logout
                        </span>

                    </button>

                </form>

            </div>

        </div>

        <!-- Statistics -->
        <section class="stats">

            <!-- Total Employee -->
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

                    <p class="stat-title">
                        Total Employee
                    </p>

                    <p class="stat-value">
                        <?= $all_count ?>
                    </p>

                </div>

            </div>

            <!-- Present Today -->
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

                    <p class="stat-title">
                        Present Today
                    </p>

                    <p class="stat-value">
                        <?= $present_count ?>
                    </p>

                </div>

            </div>

            <!-- On Leave -->
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

                    <p class="stat-title">
                        On Leave
                    </p>

                    <p class="stat-value">
                        <?= $on_leave ?>
                    </p>

                </div>

            </div>

        </section>

        <div class="panel">

            <div class="ot-table-wrap">

                <div class="ot-toolbar">

                    <h3>
                        Overtime Requests
                    </h3>

                    <input
                        type="text"
                        id="ot-search"
                        class="ot-search"
                        placeholder="Search Employee Name...">

                    <select
                        id="ot-department"
                        class="ot-select">

                        <option value="all">
                            All Departments
                        </option>

                        <?php while ($d = $departments->fetch_assoc()): ?>

                            <option value="<?= htmlspecialchars($d['name']) ?>">
                                <?= htmlspecialchars($d['name']) ?>
                            </option>

                        <?php endwhile; ?>

                    </select>

                    <select
                        id="ot-status"
                        class="ot-select">

                        <option value="all">
                            All Statuses
                        </option>

                        <option value="pending">
                            Pending
                        </option>

                        <option value="approved">
                            Approved
                        </option>

                        <option value="rejected">
                            Rejected
                        </option>

                    </select>

                    <input
                        type="date"
                        id="ot-date-from"
                        class="ot-date">

                    <input
                        type="date"
                        id="ot-date-to"
                        class="ot-date">

                </div>

                <table>

                    <thead>

                        <tr>
                            <th>Employee Name</th>
                            <th>Department</th>
                            <th>Overtime Date</th>
                            <th>Start Time</th>
                            <th>End Time</th>
                            <th>Total Hours</th>
                            <th>Type</th>
                            <th>Reason</th>
                            <th>Work</th>
                            <th>Status</th>
                            <th>Submitted On</th>
                            <th>Action</th>
                        </tr>

                    </thead>

                    <tbody
                        id="ot-table-body"
                        data-user-role="<?= htmlspecialchars($user_role) ?>"
                        data-user-id="<?= htmlspecialchars($user_id) ?>">
                    </tbody>

                </table>

            </div>

        </div>

    </main>

    <script src="../assets/js/overtimeRequest.js"></script>

</body>

</html>
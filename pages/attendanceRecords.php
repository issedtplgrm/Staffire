<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../process/access_control.php';

session_start();

if (!isset($_SESSION['id'])) {
    header("Location: ../auth/login.php");
    exit();
}

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
$leaveStmt->bind_param("s", $_SESSION["username"]);
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
$otStmt->bind_param("s", $_SESSION["username"]);
$otStmt->execute();
$otResult = $otStmt->get_result();

//added query for on leave and status of leave
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
    WHERE users.role != 'admin'
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
    } else {
        $present_count++;
    }
}

$all_count = count($attendance_rows);
$departments = $connection->query("SELECT id, name FROM departments ORDER BY name");

//get the current page that is opened
$current_page = basename($_SERVER['PHP_SELF']);

//getnmae
$username = getUsername();

//get current user's role
$user_role = $_SESSION['role'];
$user_id = $_SESSION['id'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Records</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Alata&family=Geist+Pixel&family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/adminDashboard.css">
    <link rel="stylesheet" href="../assets/css/empDashboard.css">
    <link rel="stylesheet" href="../assets/css/attendanceRecords.css">
</head>

<body>

    <sidebar class="sidebar">

        <div class="sidebar-brand">
            <span>STAFF</span>IRE
        </div>

        <nav>

            <!-- All -->
            <a href="dashboard.php" class="<?= $current_page === 'dashboard.php' ? 'active' : '' ?>">
                Home
            </a>

            <!-- Admin and Manager -->
            <?php if (isRole("admin") || isRole("manager")): ?>
                <a href="leaveRequests.php" class="<?= $current_page === 'leaveRequests.php' ? 'active' : '' ?>">
                    Leave Requests
                </a>
            <?php endif; ?>

            <!-- Admin and Manager -->
            <?php if (isRole("admin") || isRole("manager")): ?>
                <a href="overtimeRequests.php" class="<?= $current_page === 'overtimeRequests.php' ? 'active' : '' ?>">
                    Overtime Requests
                </a>
            <?php endif; ?>

            <!-- Admin and Manager -->
            <?php if (isRole("admin") || isRole("manager")): ?>
                <a href="attendanceRecords.php" class="<?= $current_page === 'attendanceRecords.php' ? 'active' : '' ?>">
                    Attendance Records
                </a>
            <?php endif; ?>

            <!-- Admin -->
            <?php if (isRole("admin")): ?>
                <a href="manageEmployees.php" class="<?= $current_page === 'manageEmployees.php' ? 'active' : '' ?>">
                    Manage Employees
                </a>
            <?php endif; ?>

            <br>

            <?php if (isRole("manager")): ?>

                <!-- Request Status -->
                <div class="sidebar-section">

                    <h4>My Leave Requests</h4>

                    <br>

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

                    <br>

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

    <!-- replace old header with sidebar, add new header in main cont--top header--put header items -->
    <main class="main-cont">

        <header class="top-header">

            <div class="header-items">

                <!-- left part -->
                <div>

                    <h1>
                        Welcome,
                        <span>
                            <?php echo htmlspecialchars(ucfirst($username)) ?>!
                        </span>
                    </h1>

                </div>

                <!-- right part -->
                <div class="header-items-right">

                    <div class="notifbell-wrap">

                        <img
                            src="../assets/img/notifbell-icon.png"
                            class="notifbell-icon"
                            alt=""
                            onclick="showNotifs()">

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

        <!-- notif-->
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

        <!-- pfp -->
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

        <section class="stats">

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
                        Total Employees
                    </p>

                    <p class="stat-value">
                        <?= $all_count ?>
                    </p>

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

                    <p class="stat-title">
                        Present Today
                    </p>

                    <p class="stat-value">
                        <?= $present_count ?>
                    </p>

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

                    <p class="stat-title">
                        On Leave Today
                    </p>

                    <p class="stat-value">
                        <?= $on_leave ?>
                    </p>

                </div>

            </div>

        </section>

        <div class="panel">

            <div class="ar-toolbar">

                <h3>
                    Attendance Records
                </h3>

                <input
                    type="text"
                    id="ar-search"
                    class="ar-search"
                    placeholder="Search Employee Name...">

                <select
                    id="ar-department"
                    class="ar-select">

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
                    id="ar-status"
                    class="ar-select">

                    <option value="all">
                        All Statuses
                    </option>

                    <option value="present">
                        Present
                    </option>

                    <option value="late">
                        Late
                    </option>

                    <option value="absent">
                        Absent
                    </option>

                </select>

                <input
                    type="date"
                    id="ar-date-from"
                    class="ar-date">

                <input
                    type="date"
                    id="ar-date-to"
                    class="ar-date">

            </div>

            <table>

                <thead>

                    <tr>
                        <th>Employee Name</th>
                        <th>Department</th>
                        <th>Date</th>
                        <th>Time In</th>
                        <th>Time Out</th>
                        <th>Status</th>
                    </tr>

                </thead>

                <tbody id="ar-table-body"></tbody>

            </table>

        </div>

    </main>

    <script src="../assets/js/attendanceRecords.js"></script>

</body>

</html>
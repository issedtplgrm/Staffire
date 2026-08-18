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
            WHEN TIME(attendance.login_time) > '09:15:00' THEN 'late'
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
$late_count = 0;
$leave_req = 0;
$on_leave = 0;

// Save attendance rows and count statuses
while ($row = $attendance_result->fetch_assoc()) {
    $attendance_rows[] = $row;

    switch ($row["status"]) {
        case "on leave":
            $on_leave++;
            break;

        case "absent":
            $absent_count++;
            break;

        case "late":
            $late_count++;
            $present_count++;
            break;

        case "present":
            $present_count++;
            break;
    }
}

$all_count = count($attendance_rows);

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
    <title>Staffire Dashboard</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alata&family=Geist+Pixel&family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/adminDashboard.css">
    <link rel="stylesheet" href="../assets/css/empDashboard.css">
</head>

<body>

    <sidebar class="sidebar">

        <div class="sidebar-brand">
            <span>STAFF</span>IRE
        </div>

        <nav>

            <!-- All -->
            <a href="dashboard.php" class="<?= $current_page === 'dashboard.php' ? 'active' : '' ?>">Home</a>

            <!-- Admin and Manager -->
            <?php if (isRole("admin") || isRole("manager")): ?>
                <a href="leaveRequests.php" class="<?= $current_page === 'leaveRequests.php' ? 'active' : '' ?>">Leave Requests</a>
            <?php endif; ?>

            <!-- Admin and Manager -->
            <?php if (isRole("admin") || isRole("manager")): ?>
                <a href="overtimeRequests.php" class="<?= $current_page === 'overtimeRequests.php' ? 'active' : '' ?>">Overtime Requests</a>
            <?php endif; ?>

            <!-- Admin and Manager -->
            <?php if (isRole("admin") || isRole("manager")): ?>
                <a href="attendanceRecords.php" class="<?= $current_page === 'attendanceRecords.php' ? 'active' : '' ?>"> Attendance Records</a>
            <?php endif; ?>

            <!-- Admin -->
            <?php if (isRole("admin")): ?>
                <a href="manageEmployees.php" class="<?= $current_page === 'manageEmployees.php' ? 'active' : '' ?>">Manage Employees</a>
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

                    <?php if (isRole("manager")): ?>
                        <button type="button" class="new-request-btn" onclick="showRequest()">
                            Request
                        </button>
                    <?php endif; ?>

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

        <!-- Notification -->
        <div class="notif-wrap" id="notifs">

            <div class="notifs">

                <div class="notif-panel-header">
                    <h4>Notifications</h4>
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

                        <span>Logout</span>

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

        <div class="main-grid">

            <!-- Pending Requests -->
            <div class="panel">

                <div class="panel-header">

                    <div class="panel-tabs">

                        <button
                            type="button"
                            class="panel-tab active"
                            id="tab-leave-panel"
                            data-tab="leave">

                            Leave Requests
                            (<span id="leave-count"><?= $leave_req ?></span>)

                        </button>

                        <button
                            type="button"
                            class="panel-tab"
                            id="tab-overtime-panel"
                            data-tab="overtime">

                            Overtime Requests
                            (<span id="ot-count">0</span>)

                        </button>

                    </div>

                    <a
                        href="leaveRequests.php"
                        class="see-more"
                        id="panel-see-more">
                        See More
                    </a>

                </div>

                <div
                    id="leave-list"
                    class="panel-tab-content active">
                </div>

                <div
                    id="ot-list"
                    class="panel-tab-content">
                </div>

            </div>

            <!-- Attendance Table -->
            <div class="panel">

                <div class="attendance-header">

                    <h3>
                        Attendance for
                        <span id="clock"></span>
                    </h3>

                    <h3>
                        Shift 9:00 AM - 5:00 PM

                        <a
                            class="see-more"
                            href="../pages/attendanceRecords.php">
                            See More
                        </a>

                    </h3>

                </div>

                <div class="filters">

                    <button
                        class="filter-btn active"
                        data-filter="all">

                        All (<?= $all_count ?>)

                    </button>

                    <button
                        class="filter-btn"
                        data-filter="present">

                        Present (<?= $present_count ?>)

                    </button>

                    <button
                        class="filter-btn"
                        data-filter="absent">

                        Absent (<?= $absent_count ?>)

                    </button>

                    <button
                        class="filter-btn"
                        data-filter="late">

                        Late (<?= $late_count ?>)

                    </button>

                </div>

                <div class="table-scroll">

                    <table>

                        <thead>

                            <tr>
                                <th>Employee Name</th>
                                <th>Department</th>
                                <th>Time In</th>
                                <th>Time Out</th>
                            </tr>

                        </thead>

                        <tbody id="attendance-body"></tbody>

                    </table>

                </div>

            </div>

        </div>

        <!-- Leave / Overtime Request Modal -->
        <div
            class="request-modal-wrap"
            id="request">

            <div class="request-modal">

                <div class="request-modal-header">

                    <h3>File a Request</h3>

                    <button
                        type="button"
                        class="modal-close-btn"
                        onclick="hideRequest()">

                        &times;

                    </button>

                </div>

                <div
                    class="form-tabs"
                    id="form-tabs">

                    <button
                        type="button"
                        id="tab-leave"
                        class="form-tab-btn active">

                        Leave Request

                    </button>

                    <button
                        type="button"
                        id="tab-overtime"
                        class="form-tab-btn">

                        Overtime Request

                    </button>

                </div>

                <!-- Leave Request Form -->
                <form
                    id="leave-form-section"
                    class="request-form"
                    action="../process/submit-request.php"
                    method="POST">

                    <input
                        type="hidden"
                        name="request_type"
                        value="leave">

                    <div class="date-field">

                        <label for="leave_type">
                            Leave Type
                        </label>

                        <select
                            name="leave_type"
                            id="leave_type"
                            required>

                            <option
                                value=""
                                disabled
                                selected>
                                Select leave type
                            </option>

                            <option value="vacation">
                                Vacation Leave
                            </option>

                            <option value="sick">
                                Sick Leave
                            </option>

                            <option value="emergency">
                                Emergency Leave
                            </option>

                            <option value="others">
                                Others
                            </option>

                        </select>

                    </div>

                    <div class="date-row">

                        <div class="date-field">

                            <label for="start_date">
                                Start Date
                            </label>

                            <input
                                type="date"
                                name="start_date"
                                id="start_date"
                                required>

                        </div>

                        <div class="date-field">

                            <label for="end_date">
                                End Date
                            </label>

                            <input
                                type="date"
                                name="end_date"
                                id="end_date"
                                required>

                        </div>

                    </div>

                    <div class="date-field">

                        <label for="reason">
                            Reason
                        </label>

                        <textarea
                            name="reason"
                            id="reason"
                            maxlength="300"
                            placeholder="Enter Reason for Leave"
                            required></textarea>

                        <span class="character-limit">
                            Maximum 300 characters
                        </span>

                    </div>

                    <button
                        type="submit"
                        class="submit-btn">

                        Submit Leave Request

                    </button>

                </form>

                <!-- Overtime Request Form -->
                <form
                    id="overtime-form-section"
                    class="request-form hidden"
                    action="../process/submit-request.php"
                    method="POST">

                    <input
                        type="hidden"
                        name="request_type"
                        value="overtime">

                    <label for="overtime_date">
                        Date of Overtime
                    </label>

                    <input
                        type="date"
                        name="overtime_date"
                        id="overtime_date"
                        required>

                    <div class="date-row">

                        <div class="date-field">

                            <label for="overtime_start">
                                Start Time
                            </label>

                            <input
                                type="time"
                                name="overtime_start"
                                id="overtime_start"
                                required>

                        </div>

                        <div class="date-field">

                            <label for="overtime_end">
                                End Time
                            </label>

                            <input
                                type="time"
                                name="overtime_end"
                                id="overtime_end"
                                required>

                        </div>

                    </div>

                    <label for="total_hours">
                        Total Hours
                    </label>

                    <input
                        type="text"
                        name="total_hours"
                        id="total_hours"
                        placeholder="4h 00m"
                        readonly>

                    <label>
                        Type of Overtime
                    </label>

                    <div class="overtime-type-row">

                        <label class="overtime-type-option">

                            <input
                                type="radio"
                                name="overtime_type"
                                value="regular"
                                checked>

                            Regular Overtime

                        </label>

                        <label class="overtime-type-option">

                            <input
                                type="radio"
                                name="overtime_type"
                                value="emergency">

                            Emergency Overtime

                        </label>

                    </div>

                    <label for="overtime_reason">
                        Reason for Overtime
                    </label>

                    <textarea
                        name="overtime_reason"
                        id="overtime_reason"
                        maxlength="300"
                        placeholder="Provide a detailed reason for your overtime request."
                        required></textarea>

                    <label for="overtime_work">
                        Work to be Accomplished (Optional)
                    </label>

                    <textarea
                        name="overtime_work"
                        id="overtime_work"
                        maxlength="300"
                        placeholder="Describe tasks or work you will be handling during this overtime."></textarea>

                    <button
                        type="submit"
                        class="submit-btn">

                        Submit Overtime Request

                    </button>

                </form>

            </div>

        </div>

    </main>

    <script src="../assets/js/admindashboard.js"></script>
    <script src="../assets/js/requestForms.js"></script>
    <script src="../assets/js/clock.js"></script>
    <script src="../assets/js/overTime.js"></script>

    <!-- If admin exclude in OT -->
    <script>
        const userRole = "<?php echo $_SESSION['role']; ?>";
    </script>

</body>

</html>


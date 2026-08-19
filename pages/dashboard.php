<?php
session_start();

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../process/access_control.php';

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

// Added query for on leave and status of leave
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
        ON attendance.id = (
            SELECT MIN(a.id)
            FROM attendance a
            WHERE a.user_id = users.id
            AND DATE(a.login_time) = CURDATE())
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
$late_count = 0; // late
$leave_req = 0;
$on_leave = 0;

// Save attendance rows and count statuses
while ($row = $attendance_result->fetch_assoc()) {
    $attendance_rows[] = $row;

    if ($row["leave_status"] === 'approved') {
        $on_leave++;
    } else if ($row["status"] === "absent") {
        $absent_count++;
    } else if ($row["status"] === "late") { // late condition
        $late_count++;
        $present_count++;
    } else {
        $present_count++;
    }
}


$all_count = count($attendance_rows);

// Get the current page that is opened
$current_page = basename($_SERVER['PHP_SELF']);

// Get name
$username = getUsername();
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
    <!-- To get the styles for the forms -->
    <link rel="stylesheet" href="../assets/css/forms.css">
</head>

<body>
    <sidebar class="sidebar">
         <div class="sidebar-brand">
            <div>
                <img src="../assets/img/staffire-logo.png" class="staffire-icon" alt="staffire-icon">
            </div>
            <div>
                <span>STAFF</span>IRE  
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

                <br>

                <?php if (isRole("manager")): ?>
                    <!-- Requst Status -->
                    <div class="sidebar-section">
                        <h4>My Leave Requests</h4>
                        <br>
                        <ul class="req-list">
                            <?php while ($row = $leaveResult->fetch_assoc()): ?>
                                <li class="req-item">
                                    <span class="req-date"><?= htmlspecialchars($row['start_date']) ?></span>
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
                                    <span class="req-date"><?= htmlspecialchars($row['overtime_date']) ?></span>
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

    <!-- Replace old header with sidebar, add new header in main cont-top header-put header items -->
    <main class="main-cont">
        <header class="top-header">
            <div class="header-items">
                <!-- Left part -->
                <div>
                    <h1>Welcome, <span><?php echo htmlspecialchars(ucfirst($username)) ?>!</span></h1>
                </div>

                <!-- Right part -->
                <div class="header-items-right">
                    <?php if (isRole("manager")): ?>
                        <button type="button" class="new-request-btn" onclick="showRequest()">Request</button>
                    <?php endif; ?>

                    <div class="notifbell-wrap">
                        <img src="../assets/img/notifbell-icon.png" class="notifbell-icon" alt="" onclick="showNotifs()">
                        <span class="notif-badge hidden" id="notif-badge"></span>
                    </div>

                    <div class="user">
                        <div class="pfp" onclick="showMenu()"></div>
                        <p><?php echo htmlspecialchars(ucfirst($username)) ?></p>
                    </div>
                </div>
            </div>
        </header>

        <!-- Notif -->
        <div class="notif-wrap" id="notifs">
            <div class="notifs">
                <div class="notif-panel-header">
                    <h4>Notifications</h4>
                </div>
                <hr>
                <div class="notif-list" id="notif-list"></div>
            </div>
        </div>

        <!-- PFP -->
        <div class="pfp-menu-wrap" id="pfp-menu">
            <div class="pfp-menu">
                <div class="user-info">
                    <h2><?php echo htmlspecialchars(ucfirst($username)) ?></h2>
                </div>
                <hr>
                <!-- <a href="#">IN CASE OF ADDING A NEW PAGE</a> -->
                <form action="../process/logout.php">
                    <button type="submit" class="logout-btn" href="../process/logout.php">
                        <img src="../assets/img/logout-icon.png" class="logout-icon" alt="">
                        <span>Logout</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Hanggag dito copy -->
        <section class="stats">
            <!-- Statistics cards -->
            <div class="stat-card">
                <div class="icon-cont">
                    <div class="emp-icon-cont">
                        <img src="../assets/img/emps-icon.png" class="emp-icon" alt="">
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
                        <img src="../assets/img/present-icon.png" class="present-icon" alt="">
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
                        <img src="../assets/img/onleave-icon.png" class="onleave-icon" alt="">
                    </div>
                </div>
                <div class="stats-info">
                    <p class="stat-title">On Leave</p>
                    <p class="stat-value"><?= $on_leave ?></p>
                </div>
            </div>
        </section>

        <div class="main-grid">
            <!-- Pending requests: leave / overtime tabs -->
            <div class="panel">
                <div class="panel-header">
                    <div class="panel-tabs">
                        <button type="button" class="panel-tab active" id="tab-leave-panel" data-tab="leave">
                            Leave Requests (<span id="leave-count"><?= $leave_req ?></span>)
                        </button>
                        <button type="button" class="panel-tab" id="tab-overtime-panel" data-tab="overtime">
                            Overtime Requests (<span id="ot-count">0</span>)
                        </button>
                    </div>
                    <a href="leaveRequests.php" class="see-more" id="panel-see-more">See More</a>
                </div>

                <div id="leave-list" class="panel-tab-content active"></div>
                <div id="ot-list" class="panel-tab-content"></div>
            </div>

            <!-- Attendance table -->
            <div class="panel">
                <div class="attendance-header">
                    <!-- Replaced with real-tim clock.js, replaced "button tag of "SEE MORE" to 'a' tag -->
                    <h3>Attendance for <span id="clock"></span></h3>
                    <h3>Shift 9:00 AM - 5:00 PM <a class="see-more" href="../pages/attendanceRecords.php">See More</a></h3>
                </div>

                <div class="filters">
                    <button class="filter-btn active" data-filter="all">
                        All (<?= $all_count ?>)
                    </button>
                    <button class="filter-btn" data-filter="present">
                        Present (<?= $present_count ?>)
                    </button>
                    <button class="filter-btn" data-filter="absent">
                        Absent (<?= $absent_count ?>)
                    </button>
                    <!-- Late button -->
                    <button class="filter-btn" data-filter="late">
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

        <!-- Leave / Overtime request modal (manager only) -->
        <div class="request-modal-wrap" id="request">
            <div class="request-modal">
                <div class="request-modal-header">
                    <h3>File a Request</h3>
                    <button type="button" class="modal-close-btn" onclick="hideRequest()">&times;</button>
                </div>

                <div class="form-tabs" id="form-tabs">
                    <button type="button" id="tab-leave" class="form-tab-btn active">Leave Request</button>
                    <button type="button" id="tab-overtime" class="form-tab-btn">Overtime Request</button>
                </div>

                <form id="leave-form-section" class="request-form" action="../process/submit-request.php" method="POST">
                    <input type="hidden" name="request_type" value="leave">

                    <div class="date-field">
                        <label for="leave_type">Leave Type</label>
                        <select name="leave_type" id="leave_type" required>
                            <option value="" disabled selected>Select leave type</option>
                            <option value="vacation">Vacation Leave</option>
                            <option value="sick">Sick Leave</option>
                            <option value="emergency">Emergency Leave</option>
                            <option value="others">Others</option>
                        </select>
                    </div>

                    <div class="date-row">
                        <div class="date-field">
                            <label for="start_date">Start Date</label>
                            <input type="date" name="start_date" id="start_date" required>
                        </div>
                        <div class="date-field">
                            <label for="end_date">End Date</label>
                            <input type="date" name="end_date" id="end_date" required>
                        </div>
                    </div>

                    <div class="date-field">
                        <label for="reason">Reason</label>
                        <textarea name="reason" id="reason" maxlength="300" placeholder="Enter Reason for Leave" required></textarea>
                        <span class="character-limit">Maximum 300 characters</span>
                    </div>
                    <button type="submit" class="submit-btn">Submit Leave Request</button>
                </form>

                <!-- Overtime request form -->
                <form id="overtime-form-section" class="request-form hidden" action="../process/submit-request.php" method="POST">
                    <input type="hidden" name="request_type" value="overtime">

                    <label for="overtime_date">Date of Overtime</label>
                    <input type="date" name="overtime_date" id="overtime_date" required>

                    <div class="date-row">
                        <div class="date-field">
                            <label for="overtime_start">Start Time</label>
                            <input type="time" name="overtime_start" id="overtime_start" required>
                        </div>
                        <div class="date-field">
                            <label for="overtime_end">End Time</label>
                            <input type="time" name="overtime_end" id="overtime_end" required>
                        </div>
                    </div>

                    <label for="total_hours">Total Hours</label>
                    <input type="text" name="total_hours" id="total_hours" placeholder="4h 00m" readonly>

                    <label>Type of Overtime</label>
                    <div class="overtime-type-row">
                        <label class="overtime-type-option">
                            <input type="radio" name="overtime_type" value="regular" checked> Regular Overtime
                        </label>
                        <label class="overtime-type-option">
                            <input type="radio" name="overtime_type" value="emergency"> Emergency Overtime
                        </label>
                    </div>

                    <label for="overtime_reason">Reason for Overtime</label>
                    <textarea name="overtime_reason" id="overtime_reason" maxlength="300" placeholder="Provide a detailed reason for your overtime request." required></textarea>

                    <label for="overtime_work">Work to be Accomplished (Optional)</label>
                    <textarea name="overtime_work" id="overtime_work" maxlength="300" placeholder="Describe tasks or work you will be handling during this overtime."></textarea>

                    <button type="submit" class="submit-btn">Submit Overtime Request</button>
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
<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../process/access_control.php';
date_default_timezone_set('Asia/Manila');

if (!isset($_SESSION['id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$username = $_SESSION["username"];

// Latest leave requests
$leaveSql = "
    SELECT lr.start_date, lr.status
    FROM leave_requests lr
    JOIN users u ON lr.user_id = u.id
    WHERE u.username = ?
    ORDER BY lr.created_at DESC
    LIMIT 5
";

$leaveStmt = $connection->prepare($leaveSql);
$leaveStmt->bind_param("s", $username);
$leaveStmt->execute();
$leaveResult = $leaveStmt->get_result();

// Latest overtime requests
$otSql = "
    SELECT orq.overtime_date, orq.status
    FROM overtime_requests orq
    JOIN users u ON orq.user_id = u.id
    WHERE u.username = ?
    ORDER BY orq.created_at DESC
    LIMIT 5
";

$otStmt = $connection->prepare($otSql);
$otStmt->bind_param("s", $username);
$otStmt->execute();
$otResult = $otStmt->get_result();

$user_id = $_SESSION['id'];
$current_page = basename($_SERVER['PHP_SELF']);
$username = $_SESSION['username'] ?? 'User';

// Today's attendance
$stmt = $connection->prepare("
    SELECT login_time, logout_time, status FROM attendance
    WHERE user_id = ? AND DATE(login_time) = CURDATE()
    ORDER BY login_time DESC LIMIT 1
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$attendance = $result->fetch_assoc() ?: [];
$stmt->close();

$time_in = $attendance['login_time'] ?? null;
$time_out = $attendance['logout_time'] ?? null;
$attendance_status = $attendance['status'] ?? 'Not Logged In';

$display_time_in = $time_in ? date('h:i A', strtotime($time_in)) : '--:--';
$display_time_out = $time_out ? date('h:i A', strtotime($time_out)) : '--:--';

if ($attendance_status === 'late') {
    $display_status = 'Late';
} elseif ($time_in) {
    $display_status = 'Present';
} else {
    $display_status = 'Absent';
}

// Monthly attendance
$current_month = date('Y-m-01');
$current_date = date('Y-m-d');

$stmt = $connection->prepare("
    SELECT id, present_count, absent_count, leave_count, last_counted_date
    FROM monthlyAttendance
    WHERE user_id = ? AND attendance_month = ?
    LIMIT 1
");
$stmt->bind_param("is", $user_id, $current_month);
$stmt->execute();
$monthly = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$monthly) {
    $stmt = $connection->prepare("
        INSERT INTO monthlyAttendance
        (user_id, attendance_month, present_count, absent_count, leave_count, last_counted_date)
        VALUES (?, ?, 0, 0, 0, NULL)
    ");
    $stmt->bind_param("is", $user_id, $current_month);
    $stmt->execute();
    $stmt->close();

    $monthly = [
        'present_count' => 0,
        'absent_count' => 0,
        'leave_count' => 0,
        'last_counted_date' => null
    ];
}

// Count today's status once
if ($monthly['last_counted_date'] !== $current_date) {
    $stmt = $connection->prepare("
        SELECT id FROM leave_requests
        WHERE user_id = ? AND status = 'approved'
        AND CURDATE() BETWEEN start_date AND end_date
        LIMIT 1
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $on_leave = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    $column = $on_leave ? 'leave_count' : ($time_in ? 'present_count' : 'absent_count');

    $stmt = $connection->prepare("
        UPDATE monthlyAttendance
        SET $column = $column + 1, last_counted_date = ?
        WHERE user_id = ? AND attendance_month = ?
    ");
    $stmt->bind_param("sis", $current_date, $user_id, $current_month);
    $stmt->execute();
    $stmt->close();

    $monthly[$column]++;
    $monthly['last_counted_date'] = $current_date;
}

$monthly_present = (int)$monthly['present_count'];
$monthly_absent = (int)$monthly['absent_count'];
$monthly_leave = (int)$monthly['leave_count'];
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
    <link rel="stylesheet" href="../assets/css/empDashboard.css">
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-brand">
            <span>STAFF</span>IRE
        </div>
        <nav>
             <a href="dashboard.php" class="<?= $current_page === 'dashboard.php' ? 'active' : '' ?>"><svg xmlns="http://www.w3.org/2000/svg"
                    fill="#e3dada" viewBox="0 0 24 24">
                    <path d="M3 13h1v7c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2v-7h1c.4 0 .77-.24.92-.62.15-.37.07-.8-.22-1.09l-8.99-9a.996.996 0 0 0-1.41 0l-9.01 9c-.29.29-.37.72-.22 1.09s.52.62.92.62Zm9-8.59 6 6V20H6v-9.59z"></path>
                </svg>Dashboard</a>
        </nav>
        <br>
        <br>

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
    </aside>

    <main class="main-cont">
        <header class="top-header">
            <div class="header-items">
                <!-- left part -->
                <div>
                    <h1>Welcome, <span><?php echo htmlspecialchars(ucfirst($username)) ?>!</span></h1>
                </div>

                <!-- righth part -->
                <div class="header-items-right">
                    <div class="user">
                        <div class="pfp" onclick="showMenu()"></div>
                        <p><?php echo htmlspecialchars(ucfirst($username)) ?></p>
                    </div>
                </div>
            </div>
        </header>

        <!-- pfp -->
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

        <!-- Monthly attendance -->
        <section class="stats">
            <div class="stat-card">
                <div class="icon-cont">
                    <div class="present-stat-icon-cont">
                        <img src="../assets/img/emps-icon.png" class="present-stat-icon" alt="Present">
                    </div>
                </div>
                <div class="stats-info">
                    <p class="stat-title">Total Days Present this Month</p>
                    <p class="stat-value"><?= $monthly_present ?></p>
                </div>
            </div>

            <div class="stat-card">
                <div class="icon-cont">
                    <div class="absent-stat-icon-cont">
                        <img src="../assets/img/absent-icon.png" class="absent-stat-icon" alt="Absent">
                    </div>
                </div>
                <div class="stats-info">
                    <p class="stat-title">Days Absent this Month</p>
                    <p class="stat-value"><?= $monthly_absent ?></p>
                </div>
            </div>

            <div class="stat-card">
                <div class="icon-cont">
                    <div class="onleave-stat-icon-cont">
                        <img src="../assets/img/onleave-icon.png" class="onleave-stat-icon" alt="On Leave">
                    </div>
                </div>
                <div class="stats-info">
                    <p class="stat-title">Days on Leave this Month</p>
                    <p class="stat-value"><?= $monthly_leave ?></p>
                </div>
            </div>
        </section>

        <div class="main-grid">
            <!-- Request forms -->
            <div class="panel">
                <div class="panel-header">
                    <h3>File Request Forms</h3>
                </div>

                <div class="form-tabs" id="form-tabs">
                    <button type="button" id="tab-leave" class="form-tab-btn active">Leave Request</button>
                    <button type="button" id="tab-overtime" class="form-tab-btn">Overtime Request</button>
                </div>

                <form id="leave-form-section" class="request-form" action="../process/submit-request.php" method="POST" enctype="multipart/form-data">
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

                <!-- overtime request form -->
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

            <!-- RIGHT PANEL: Attendance Today -->
             <div class="panel">

            <div class="attendance-header">
                <h3>Attendance Today</h3>
                <span class="status-pill" id="clock"></span>
                <span class="status-pill"><?= htmlspecialchars($display_status) ?></span>
            </div>

            <div class="today-timeline">

                <div class="time-point">
                    <div class="time-in-icon-cont">
                        <img src="../assets/img/timeIn-icon.png" class="time-in-icon" alt="Time In">
                    </div>

                    <div>
                        <p class="time-label">Time In</p>
                        <p class="time-value"><?= htmlspecialchars($display_time_in) ?></p>
                    </div>
                </div>

                <div class="time-line"></div>

                <div class="time-point">
                    <div class="time-out-icon-cont">
                        <img src="../assets/img/timeOut-icon.png" class="time-out-icon" alt="Time Out">
                    </div>

                    <div>
                        <p class="time-label">Time Out</p>
                        <p class="time-value"><?= htmlspecialchars($display_time_out) ?></p>
                    </div>
                </div>

            </div>

            <div class="hours-row">

                <div>
                    <p class="hours-label">Working Hours</p>
                    <p>8 hours per day</p>
                </div>

                <div>
                    <p class="hours-label">Break Time</p>
                    <p>12 NN - 1 PM</p>
                </div>

                <div>
                    <p class="hours-label">Overtime</p>
                    <p class="hours-value" id="overtime">--</p>
                </div>

            </div>

            <div class="guidelines-box" id="guidelines-leave">
                <p class="guidelines-title">Leave Request Guidelines</p>
                <ul>
                    <li>File your leave request in advance before your intended leave date.</li>
                    <li>Provide accurate details and enter the correct information.</li>
                    <li>Your request must be reviewed and approved by the manager.</li>
                </ul>
            </div>

            <div class="guidelines-box hidden" id="guidelines-overtime">
                <p class="guidelines-title">Overtime Request Guidelines</p>
                <ul>
                    <li>Overtime must be approved by the administrator.</li>
                    <li>Submit your request at least a day in advance.</li>
                    <li>Overtime will be compensated based on company policy.</li>
                    <li>Ensure your overtime work is necessary and justified.</li>
                </ul>
            </div>

            <div class="note-box" id="note-leave">
                <p class="note-title">Note</p>
                <p>Your leave request will be reviewed by the administrator. Please wait for the approval before considering your leave officially approved.</p>
            </div>

            <div class="note-box hidden" id="note-overtime">
                <p class="note-title">Note</p>
                <p>Overtime will be compensated based on company policy and subject to approval.</p>
            </div>

        </div>
        </div>
    </main>
    <script src="../assets/js/empdashboard.js"></script>
    <script src="../assets/js/requestForms.js"></script>
    <script src="../assets/js/clock.js"></script>
</body>

</html>
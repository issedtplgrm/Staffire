<?php
session_start();

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../process/access_control.php';

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
            WHEN TIME(NOW()) > '09:15:00' THEN 'late'
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
    WHERE users.role != 'admin'
    ORDER BY attendance.login_time DESC, users.full_name ASC
";

$attendance_result = $connection->query($attendance_sql);

// Counters
$attendance_rows = [];
$present_count = 0;
$absent_count = 0;
$late_count = 0; //late
$leave_req = 0;
$on_leave = 0;

// Save attendance rows and count statuses
while ($row = $attendance_result->fetch_assoc()) {
    $attendance_rows[] = $row;

    if($row["leave_status"] === 'approved'){
        $on_leave++
        ;
    } else if ($row["status"] === "absent") {
        $absent_count++; }
    else if ($row["status"] === "late") {   // late condition
        $late_count++;
        $present_count++;
    } else {
        $present_count++;
    } 
}

$all_count = count($attendance_rows);

//get the current page that is opened
$current_page = basename($_SERVER['PHP_SELF']);

//get name
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
    
    <link
        href="https://fonts.googleapis.com/css2?family=Alata&family=Geist+Pixel&family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/adminDashboard.css">
    <!-- to get the styles for the forms -->
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
        </nav>

    </sidebar>

      <!-- replace old header with sidebar, add new header in main cont--top header--put header items  -->
    <main class="main-cont">
      <header class="top-header">
        <div class="header-items">
            <!-- left part -->
            <div> 
                <h1>Welcome, <span><?php echo htmlspecialchars($username) ?>!</span></h1>
            </div>
            <!-- righth part -->
            <div class="header-items-right">
                <?php if (isRole("manager")): ?>
                    <button type="button" class="new-request-btn" onclick="showRequest()">Request</button>
                <?php endif; ?>
                <div>
                    <img src="../assets/img/notifbell-icon.png" class="notifbell-icon" alt="" onclick="showNotifs()">
                </div>
                <div class="user">
                    <div class="pfp" onclick="showMenu()"></div>
                    <p><?php echo htmlspecialchars($username) ?></p>
                </div>
            </div>    
        </div>
    </header>
         <!-- notif-->
    
        <div class="notif-wrap" id="notifs">
            <div class="notifs">
                 <hr>
                <div class="notif-card">  
                <p>notifs</p>
                </div>
            </div>
        </div>        
        <!-- pfp -->

        <div class="pfp-menu-wrap" id="pfp-menu">
            <div class="pfp-menu">
                <div class="user-info">
                    <h2><?php echo htmlspecialchars($username) ?></h2>
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
        <!-- hanngang dito copy -->
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
                    <!-- replaced with real-tim clock.js, replaced "button tag of "SEE MORE" to 'a' tag-->
                    <h3>Attendance for <span id="clock"></span> </h3>
                    <h3>Shift 9:00 AM - 5:00 PM    <a class="see-more" href="../pages/attendanceRecords.php">See More</a> </h3>

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
                    <!-- late button -->
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
                            <input type="radio" name="overtime_type" value="regular" checked> Regular Overtime </label>
                        <label class="overtime-type-option">
                            <input type="radio" name="overtime_type" value="emergency"> Emergency Overtime </label>
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
    <!-- if admin exclude in OT -->
    <script>
    const userRole = "<?php echo $_SESSION['role']; ?>";
</script>



</body>

</html>
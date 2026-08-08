<?php
session_start();

require_once __DIR__ . '/../config/db.php';

$attendance_sql = "
    SELECT
        users.full_name,
        departments.name AS department,
        attendance.login_time,
        attendance.logout_time,
        CASE
            WHEN attendance.id IS NULL THEN 'absent'
            ELSE attendance.status
        END AS status
    FROM users
    LEFT JOIN attendance
        ON attendance.user_id = users.id
        AND DATE(attendance.login_time) = CURDATE()
    LEFT JOIN departments
        ON users.department_id = departments.id
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

    if ($row["status"] === "absent") {
        $absent_count++;
    } else {
        $present_count++;
    }
}

$all_count = count($attendance_rows);
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
        rel="stylesheet"
    >

    <link rel="stylesheet" href="../assets/css/adminDashboard.css">
</head>

<body>
    <header class="header">
        <nav>
            <a href="dashboard.php">Home</a>
            <a href="#">Department</a>
            <a href="leaveRequests.php">Leave Requests</a>
            <a href="#"> Attendance Records</a>
            <a href="../pages/manageEmployees.php">Manage Employees</a>
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
                            alt=""
                        >
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
                            alt=""
                        >
                    </div>
                </div>

                <div class="stats-info">
                    <p class="stat-title">Present Today</p>
                    <p class="stat-value"><?=  $present_count ?></p>
                </div>
            </div>

            <div class="stat-card">
                <div class="icon-cont">
                    <div class="onleave-icon-cont">
                        <img
                            src="../assets/img/onleave-icon.png"
                            class="onleave-icon"
                            alt=""
                        >
                    </div>
                </div>

                <div class="stats-info">
                    <p class="stat-title">On Leave</p>
                    <p class="stat-value"><?= $on_leave ?></p>
                </div>
            </div>

        </section> 
            <div class="main-grid"> 

                <!-- Pending leave requests -->
                <div class="panel">
                    <div class="panel-header">
                        <h3>Pending Leave Requests (<span id="leave-count"><?= $leave_req ?></span>)</h3>
                        <a href="leaveRequests.php" class="see-more">See More</a>
                    </div>

                    <div id="leave-list"></div>
                        <form action="../process/logout.php">
                            <button type="submit" class="logout-btn" href="../process/logout.php">Logout</button>
                            
                        </form>
                </div>

                <!-- Attendance table -->
                <div class="panel">
                    <div class="attendance-header">
                        <h3>Attendance for <?= date("d/m/Y") ?></h3>
                        <span class="see-more">See More</span>
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
    </main>

    <script src="../assets/js/admindashboard.js"></script>
</body>

</html>
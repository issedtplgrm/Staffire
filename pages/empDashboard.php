<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../process/access_control.php';

//get the current page that is opened
$current_page = basename($_SERVER['PHP_SELF']);
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

    <link rel="stylesheet" href="../assets/css/empDashboard.css">
</head>

<body>
    <header class="header">
        <nav>
            <!-- dashboard for emploeyee -->
            <a href="empDashboard.php" class="<?= $current_page === 'empDashboard.php' ? 'active' : '' ?>">Home</a>
            <!-- Admin and Manager -->
            <?php if (isRole("admin") || isRole("manager")): ?>
                <a href="leaveRequests.php" class="<?= $current_page === 'leaveRequests.php' ? 'active' : '' ?>">Leave Requests</a>
            <?php endif; ?>
            <!-- Employee and Manager(?) -->
            <?php if (isRole("manager") || isRole("employee")): ?>
                <a href="leaveRequests.php" class="<?= $current_page === 'requestLeave.php' ? 'active' : '' ?>">Request Leave</a>
            <?php endif; ?>
            <!-- Admin and Manager -->
            <?php if (isRole("admin") || isRole("manager")): ?>
                <a href="attendanceRecords.php" class="<?= $current_page === 'attendanceRecords.php' ? 'active' : '' ?>"> Attendance Records</a>
            <?php endif; ?>
        </nav>

        <div class="header-items">
            <div><img src="../assets/img/notifbell-icon.png" class="notifbell-icon" alt="" onclick="showNotifs()"></div>

            <div class="pfp" onclick="showMenu()"></div>
        </div>
        <div class="notif-wrap" id="notifs">
            <div class="notifs">
                <hr>
                <div class="notif-card">
                    <p>notifs</p>
                </div>
            </div>
        </div>
        <div class="pfp-menu-wrap" id="pfp-menu">
            <div class="pfp-menu">
                <div class="user-info">
                    <h2>user name</h2>
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
    </header>

    <main class="main-cont">
        <h2>Good Day! Welcome to Staffire, </h2>
        <!-- stat cards -->
        <section class="stats">
            <div class="stat-card">
                <div class="icon-cont">
                    <div class="present-stat-icon-cont">
                         <img src="../assets/img/emps-icon.png" class="present-stat-icon" alt="">
                    </div>
                </div>

                <div class="stats-info">
                    <p class="stat-title">Total Days Present this Month</p>
                    <p class="stat-value"></p>
                </div>
            </div>

            <div class="stat-card">
                <div class="icon-cont">
                    <div class="absent-stat-icon-cont">
                        <img src="../assets/img/absent-icon.png" class="absent-stat-icon" alt="">    
                    </div>
                </div>
                <div class="stats-info">
                    <p class="stat-title">Days Absent this Month</p>
                    <p class="stat-value"></p>
                </div>
            </div>

            <div class="stat-card">
                <div class="icon-cont">
                    <div class="onleave-stat-icon-cont">  
                         <img src="../assets/img/onleave-icon.png" class="onleave-stat-icon" alt="">
                    </div>
                </div>
                <div class="stats-info">
                    <p class="stat-title">Days on Leave this Month</p>
                    <p class="stat-value"></p>
                </div>
            </div>

        </section>
        <!-- request forms -->
        <div class="main-grid"> 
            <div class="panel">
                <div class="panel-header">
                    <h3>File Request Forms</h3>
                </div>
                <div class="form-tabs">
                    <button type="button" id="tab-leave" class="form-tab-btn active">Leave Request</button>
                    <button type="button" id="tab-overtime" class="form-tab-btn">Overtime Request</button>
                </div>

                <form id="leave-form-section" class="request-form" action="../process/submitLeaveRequest.php" method="POST" enctype="multipart/form-data">
                    <label for="leave_type">Leave Type</label>
                    <select name="leave_type" id="leave_type" required>
                        <option value="" disabled selected>Select leave type</option>
                        <option value="vacation">Vacation Leave</option>
                        <option value="sick">Sick Leave</option>
                        <option value="emergency">Emergency Leave</option>
                        <option value="others">Others</option>
                    </select>
                    <div class="date-row">
                        <div class="date-field">
                             <label for="start_date">Start Date</label>
                            <input type="date" name="start_date" id="start_date" required>
                        </div>
                            <div class="date-field"> <label for="end_date">End Date</label>
                            <input type="date" name="end_date" id="end_date" required>
                        </div>
                    </div> 

                    <label for="reason">Reason</label> 
                        <textarea name="reason" id="reason" maxlength="300" placeholder="Enter Reason for Leave" required></textarea>
                    <label for="attachment-input">Attachment (Optional)</label>
                    <div class="file-input-row"> 
                        <label class="file-choose-btn" for="attachment-input">Choose File</label> 
                        <span id="attachment-filename">No file chosen</span>
                        <input type="file" name="attachment" id="attachment-input" hidden>
                    </div> 
                     <button type="submit" class="submit-btn">Submit Leave Request</button>
                </form>
                <!-- overtime request form -->
                <!-- <form id="overtime-form-section" class="request-form hidden" onsubmit="return false;">
                    <label for="overtime_date">Date of Overtime</label>
                        <input type="date" name="overtime_date" id="overtime_date">
                    <div class="date-row">
                        <div class="date-field">
                            <label for="overtime_start">Start Time</label>
                                <input type="time" name="overtime_start" id="overtime_start">
                        </div>
                        <div class="date-field">
                            <label for="overtime_end">End Time</label>
                                <input type="time" name="overtime_end" id="overtime_end">
                        </div>
                    </div>
                     <label for="total_hours">Total Hours</label>
                        <input type="text" name="total_hours" id="total_hours" placeholder="4h 00m" readonly>
                            <label>Type of Overtime</label>
                    <div class="overtime-type-row">
                            <label class="overtime-type-option">
                                <input type="radio" name="overtime_type" value="regular" checked> Regular Overtime </label>
                            <label class="overtime-type-option"></label> 
                            <input type="radio" name="overtime_type" value="emergency"> Emergency Overtime </label>
                    </div> 
                            <label for="overtime_reason">Reason for Overtime</label>
                                <textarea name="overtime_reason" id="overtime_reason" maxlength="300" placeholder="Provide a detailed reason for your overtime request."></textarea>
                            <label for="overtime_work">Work to be Accomplished (Optional)</label>
                               <textarea name="overtime_work" id="overtime_work" maxlength="300" placeholder="Describe tasks or work you will be handling during this overtime."></textarea>
                            
                            <button type="submit" class="submit-btn" disabled title="Overtime requests aren't wired up yet">Submit Overtime Request</button>
                </form> -->
            </div>
             <!-- RIGHT PANEL: Attendance Today -->
            <div class="panel">
                <div class="attendance-header">
                    <h3>Attendance Today</h3>
                    <span class="status-pill"></span>
                </div>
                <div class="today-timeline">
                    <div class="time-point">
                      <div class="time-in-icon-cont">
                        <img src="../assets/img/timeIn-icon.png" class="time-in-icon" alt="">
                      </div>
                        <div>
                            <p class="time-label">Time In</p>
                            <p class="time-value" id="time-out">--:--</p>
                        </div>
                    </div>
                    <div class="time-line"></div>
                    <div class="time-point">
                        <div class="time-out-icon-cont">
                            <img src="../assets/img/timeOut-icon.png" class="time-out-icon" alt="">
                        </div>
                        <div>
                            <p class="time-label">Time Out</p>
                            <p class="time-value" id= "time-out">--:--</p>
                        </div>
                    </div>
                </div>
                <div class="hours-row">
                    <div>
                        <p class="hours-label">Working Hours</p>
                        <p class="hours-value" id="workingHours">--</p>
                    </div>
                    <div>
                        <p class="hours-label">Break Time</p>
                        <p class="hours-value" id="breakTime">--</p>
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
                        <li>Submit your request at least hours or a day in advance.</li>
                        <li>Overtime will be compensated based on company policy.</li>
                        <li>Ensure your overtime work is necessary and justified.</li>
                    </ul>
                </div>
                <div class="note-box" id="note-leave">
                    <p class="note-title">Note</p>
                    <p>Your leave request will be reviewed by the administrator.
                        Please wait for the approval before considering your leave officially approved.</p>
                </div>
                <div class="note-box hidden" id="note-overtime">
                    <p class="note-title">Note</p>
                    <p>Overtime will be compensated based on the company policy and subject to approval.</p>
                </div>
            </div>
        </div>
    </main>

    <script src="../assets/js/admindashboard.js"></script>
</body>

</html>
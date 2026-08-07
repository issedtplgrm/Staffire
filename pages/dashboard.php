<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alata&family=Geist+Pixel&family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/adminDashboard.css">
</head>

<body>
    <header class="header">
        <nav>
            <a href="dashboard.php">Home</a>
            <a href="#">Department</a>
            <a href="#">Leave Requests</a>
            <a href="#"> Attendance Records</a>
            <a href="manageEmployees.php">Manage Employees</a>
        </nav>
        <div class="header-items">
            <a href=""><img src="../assets/img/notifbell-icon.png" class="notifbell-icon" alt=""></a>
            <div class="pfp"></div>
        </div>
    </header>
    <main class="main-cont">
        <!-- stats card -->
        <section class="stats">

            <div class="stat-card">
                <div class="icon-cont">
                    <div class="emp-icon-cont">
                        <img src="../assets/img/emps-icon.png" class="emp-icon" alt="">
                    </div>
                </div>
                <div class="stats-info">
                    <p class="stat-title">Total Employee</p>
                    <p class="stat-value">88</p>
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
                    <p class="stat-value">22</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="icon-cont">
                    <div class="onleave-icon-cont">
                        <img src="../assets/img/onleave-icon.png" class="onleave-icon" alt="">
                    </div>
                </div>
                <div class="stats-info">
                    <p class="stat-title">On leave</p>
                    <p class="stat-value">08</p>
                </div>
            </div>

            <div class="main-grid">

                <!-- left side leave requests -->
                <div class="panel">
                    <div class="panel-header">
                        <h3>Pending Leave Requests (5)</h3>
                        <span class="see-more">See More</span>
                    </div>

                    <div id="leave-list"></div>

                    <button class="logout-btn">Logout</button>
                </div>

                <!-- right attendance -->
                <div class="panel">
                    <div class="attendance-header">
                        <h3>Attendance for DD/MM/YYYY</h3>
                        <span class="see-more">See More</span>
                    </div>
                    <div class="filters">
                        <button class="filter-btn active">All (88)</button>
                        <button class="filter-btn">Present (37)</button>
                        <button class="filter-btn">Absent (37)</button>
                    </div>
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


        </section>
    </main>
</body>

</html>
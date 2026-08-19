<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../process/access_control.php';

//check if a user is logged in
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

//clears any past messages :,(
$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? "";
unset($_SESSION['error'], $_SESSION['success']);

//if admin is editing, automatically fill the input with that employees details
$editing = null;
if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];
    $stmt = $connection->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $editing = $stmt->get_result()->fetch_assoc();
}

// departments for the dropdown
$departments = $connection->query("SELECT id, name FROM departments ORDER BY name");

// All employees, with department name
$employees = $connection->query(
    "SELECT u.id, u.full_name, u.username, u.email, u.role, d.name AS department
     FROM users u
     LEFT JOIN departments d ON d.id = u.department_id
     ORDER BY u.full_name"
);

$current_page = basename($_SERVER['PHP_SELF']);

//getname
$username = getUsername();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Employees</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Alata&family=Geist+Pixel&family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/adminDashboard.css">
    <link rel="stylesheet" href="../assets/css/manageEmp.css">
</head>

<body>
    <sidebar class="sidebar">
        <div class="sidebar-brand">
            <div>
                <img src="../assets/img/staffire-icon.png" class="staffire-icon" alt="staffire-icon">
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

        <!-- notif -->
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

        <!-- alerts -->
        <?php if ($error): ?>

            <div class="alert alert-error">
                <?= htmlspecialchars($error) ?>
            </div>

        <?php endif; ?>

        <?php if ($success): ?>

            <div class="alert alert-success">
                <?= htmlspecialchars($success) ?>
            </div>

        <?php endif; ?>

        <!-- add / edit form -->
        <div class="form-card">

            <h3 style="margin-bottom:16px;">
                <?= $editing ? 'Edit Employee' : 'Add New Employee' ?>
            </h3>

            <form
                method="POST"
                action="<?= $editing ? '../process/editEmp-process.php' : '../process/addEmp-process.php' ?>">

                <?php if ($editing): ?>

                    <input
                        type="hidden"
                        name="id"
                        value="<?= (int) $editing['id'] ?>">

                <?php endif; ?>

                <div class="form-grid">

                    <div>

                        <label>
                            Full Name
                        </label>

                        <input
                            type="text"
                            name="full_name"
                            required
                            value="<?= htmlspecialchars($editing['full_name'] ?? '') ?>">

                    </div>

                    <div>

                        <label>
                            Username
                        </label>

                        <input
                            type="text"
                            name="username"
                            required
                            value="<?= htmlspecialchars($editing['username'] ?? '') ?>">

                    </div>

                    <div>

                        <label>
                            Email
                        </label>

                        <input
                            type="email"
                            name="email"
                            required
                            value="<?= htmlspecialchars($editing['email'] ?? '') ?>">

                    </div>

                    <!-- if admin is not editing, show the password input field -->
                    <?php if (!$editing): ?>

                        <div>

                            <label>
                                Password
                            </label>

                            <input
                                type="password"
                                name="password"
                                required>

                        </div>

                    <?php endif; ?>

                    <div>

                        <label>
                            Role
                        </label>

                        <select name="role">

                            <?php foreach (['employee', 'manager', 'admin'] as $r): ?>

                                <option
                                    value="<?= $r ?>"
                                    <?= (($editing['role'] ?? 'employee') === $r) ? 'selected' : '' ?>>

                                    <?= ucfirst($r) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <div>

                        <label>
                            Department
                        </label>

                        <select name="department_id">

                            <option value="">
                                -- None --
                            </option>

                            <?php

                            $departments->data_seek(0);

                            while ($d = $departments->fetch_assoc()):

                            ?>

                                <option
                                    value="<?= $d['id'] ?>"
                                    <?= (($editing['department_id'] ?? null) == $d['id']) ? 'selected' : '' ?>>

                                    <?= htmlspecialchars($d['name']) ?>

                                </option>

                            <?php endwhile; ?>

                        </select>

                    </div>

                </div>

                <button
                    type="submit"
                    class="submit-btn">

                    <?= $editing ? 'Save Changes' : 'Add Employee' ?>

                </button>

                <!-- if admin is editing, show a cancel button -->
                <?php if ($editing): ?>

                    <a
                        href="../pages/manageEmployees.php"
                        class="cancel-link">

                        Cancel

                    </a>

                <?php endif; ?>

            </form>

        </div>

        <!-- EMPLOYEE LIST -->
        <div class="emp-table-wrap">

            <h3>
                All Employees
            </h3>

            <table>

                <thead>

                    <tr>

                        <th>
                            Full Name
                        </th>

                        <th>
                            Username
                        </th>

                        <th>
                            Email
                        </th>

                        <th>
                            Role
                        </th>

                        <th>
                            Department
                        </th>

                        <th>
                            Actions
                        </th>

                    </tr>

                </thead>

                <tbody>

                    <?php while ($emp = $employees->fetch_assoc()): ?>

                        <tr>

                            <td>
                                <?= htmlspecialchars($emp['full_name']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($emp['username']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($emp['email']) ?>
                            </td>

                            <td>

                                <span class="role-badge">
                                    <?= htmlspecialchars(ucfirst($emp['role'])) ?>
                                </span>

                            </td>

                            <td>
                                <?= htmlspecialchars($emp['department'] ?? 'Unassigned') ?>
                            </td>

                            <td>

                                <a
                                    href="../pages/manageEmployees.php?edit=<?= $emp['id'] ?>"
                                    class="action-link">

                                    Edit

                                </a>

                                <a
                                    href="../process/deleteEmp-process.php?id=<?= $emp['id'] ?>"
                                    class="action-link delete"
                                    onclick="return confirm('Delete this user? This cannot be undone.');">

                                    Delete

                                </a>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                </tbody>

            </table>

        </div>

    </main>

    <script src="../assets/js/manageEmployee.js"></script>

</body>

</html>
```

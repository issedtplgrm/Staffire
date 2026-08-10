<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../process/access_control.php';

//check if a user is logged in
if (!isset($_SESSION['id'])) {
    header("Location: ../auth/login.php");
    exit();
}

//clears any past messages :,(
$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success']??"";
unset($_SESSION['error'], $_SESSION['success']);

//if admin is editing, automatically fill the input with that employees details
$editing = null;
    if (isset($_GET['edit'])){
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
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Employees</title>
    <link rel="stylesheet" href="../assets/css/adminDashboard.css">
    <link rel="stylesheet" href="../assets/css/manageEmp.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alata&family=Geist+Pixel&family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

</head>

<body>
    <header class="header">
        <nav>
            <!-- All -->
            <a href="dashboard.php" class="<?= $current_page === 'dashboard.php' ? 'active' : '' ?>">Home</a>
            <!-- Admin and Manager -->
            <?php if (isRole("admin") || isRole("manager")): ?>
                <a href="leaveRequests.php" class="<?= $current_page === 'leaveRequests.php' ? 'active' : '' ?>">Leave Requests</a>
            <?php endif; ?>

            <!-- Employee and Manager(?) -->
            <?php if (isRole("manager") || isRole("employee")): ?>
                <a href="requestLeave.php" class="<?= $current_page === 'requestLeave.php' ? 'active' : '' ?>">Request Leave</a>
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
        <div class="header-items">
            <a href=""><img src="../assets/img/notifbell-icon.png" class="notifbell-icon" alt=""></a>
            <div class="pfp"></div>
        </div>
    </header>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <!-- add / edit form -->
    <div class="form-card">
        <h3 style="margin-bottom:16px;"><?= $editing ? 'Edit Employee' : 'Add New Employee' ?></h3>
        <form method="POST" action="<?= $editing ? '../process/editEmp-process.php' : '../process/addEmp-process.php' ?>">
            <?php if ($editing): ?>
                <input type="hidden" name="id" value="<?= (int) $editing['id'] ?>">
            <?php endif; ?>
 
            <div class="form-grid">
                <div>
                    <label>Full Name</label>
                    <input type="text" name="full_name" required value="<?= htmlspecialchars($editing['full_name'] ?? '') ?>">
                </div>
                <div>
                    <label>Username</label>
                    <input type="text" name="username" required value="<?= htmlspecialchars($editing['username'] ?? '') ?>">
                </div>
                <div>
                    <label>Email</label>
                    <input type="email" name="email" required value="<?= htmlspecialchars($editing['email'] ?? '') ?>">
                </div>
                
                <!-- if admin is not editing, show the password input filed -->
                <?php if (!$editing): ?>
                    <div>
                        <label>Password</label>
                        <input type="password" name="password" required>
                    </div>
                <?php endif; ?>
 
                <div>
                    <label>Role</label>
                    <select name="role">
                        <?php foreach (['employee', 'manager', 'admin'] as $r): ?>
                            <option value="<?= $r ?>" <?= (($editing['role'] ?? 'employee') === $r) ? 'selected' : '' ?>>
                                <?= ucfirst($r) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
 
                <div>
                    <label>Department</label>
                    <select name="department_id">
                        <option value="">-- None --</option>
                        <?php
                        $departments->data_seek(0);
                        while ($d = $departments->fetch_assoc()):
                        ?>
                            <option value="<?= $d['id'] ?>" <?= (($editing['department_id'] ?? null) == $d['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($d['name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
 
            <button type="submit" class="submit-btn"><?= $editing ? 'Save Changes' : 'Add Employee' ?></button>
            <!-- if admin is editing, show a cancel button -->
            <?php if ($editing): ?>
                <a href="../pages/manageEmployees.php" class="cancel-link">Cancel</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- EMPLOYEE LIST -->
    <div class="emp-table-wrap">
        <h3>All Employees</h3>
        <table>
            <thead>
                <tr>
                    <th>Full Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Department</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($emp = $employees->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($emp['full_name']) ?></td>
                        <td><?= htmlspecialchars($emp['username']) ?></td>
                        <td><?= htmlspecialchars($emp['email']) ?></td>
                        <td><span class="role-badge"><?= htmlspecialchars(ucfirst($emp['role'])) ?></span></td>
                        <td><?= htmlspecialchars($emp['department'] ?? 'Unassigned') ?></td>
                        <td>
                            <a href="../pages/manageEmployees.php?edit=<?= $emp['id'] ?>" class="action-link">Edit</a>
                            <a href="../process/deleteEmp-process.php?id=<?= $emp['id'] ?>" class="action-link delete"
                               onclick="return confirm('Delete this user? This cannot be undone.');">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

</body>
</html>
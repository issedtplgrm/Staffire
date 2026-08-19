CREATE DATABASE IF NOT EXISTS staffire;
 Use Staffire;
-- chavez bakla
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL, 
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    role ENUM('admin','manager','employee') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS attendance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    login_time DATETIME NOT NULL,
    logout_time DATETIME,
    status ENUM('present','absent') DEFAULT 'present',
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS leave_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    type ENUM('sick','vacation','emergency','other') NOT NULL,
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    manager_id INT,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (manager_id) REFERENCES users(id)
);

-- added departments table and foreign keys
    CREATE TABLE IF NOT EXISTS departments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL
);
 
ALTER TABLE users
    ADD COLUMN department_id INT NULL AFTER role,
    ADD FOREIGN KEY (department_id) REFERENCES departments(id);
 
ALTER TABLE leave_requests
    ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER manager_id;

insert into users (username, password, full_name, email, role) values("admin", "123admin", "admin_name", "admin@clsu.edu.ph", "admin");

insert into departments (name) values("IT Department"), ("HR Department"), ("Finance Department"), ("Marketing Department"), ("Sales Department");

-- attendance: if the employee is deleted, delete their attendance rows too
ALTER TABLE attendance DROP FOREIGN KEY attendance_ibfk_1;
ALTER TABLE attendance ADD CONSTRAINT attendance_ibfk_1
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE;

-- leave_requests.user_id: if the employee is deleted, delete their leave requests too
ALTER TABLE leave_requests DROP FOREIGN KEY leave_requests_ibfk_1;
ALTER TABLE leave_requests ADD CONSTRAINT leave_requests_ibfk_1
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE;
-- added reason column
ALTER TABLE leave_requests ADD COLUMN reason VARCHAR(255) NULL AFTER type;

-- leave_requests.manager_id: if the MANAGER is deleted, just blank out
-- who approved it  don't delete the employee's leave request
ALTER TABLE leave_requests DROP FOREIGN KEY leave_requests_ibfk_2;
ALTER TABLE leave_requests ADD CONSTRAINT leave_requests_ibfk_2
    FOREIGN KEY (manager_id) REFERENCES users(id)
    ON DELETE SET NULL;

-- test the leave request
insert into leave_requests (user_id, start_date, end_date, type, status, manager_id, reason) values
(5, '2026-08-09', '2026-08-21', 'vacation', 'approved', 1, 'Relapse :,( '),
(5, '2026-08-09', '2026-08-23', 'sick', 'approved', 1, 'May nalaman');

select * from leave_requests;

-- late
ALTER TABLE attendance 
MODIFY status ENUM('present','absent','late') DEFAULT 'present';

-- month present absent
CREATE TABLE IF NOT EXISTS monthlyAttendance(
    id INT PRIMARY KEY AUTO_INCREMENT,
    present_count INT,
    absent_count INT,
    leave_count INT,
    user_id INT NOT NULL,
    attendance_month DATE NOT NULL, 
    last_counted_date DATE NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);


CREATE TABLE IF NOT EXISTS overtime_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    overtime_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    total_hours DECIMAL(5,2) NOT NULL, -- store hours as numeric (e.g., 3.50)
    overtime_type ENUM('regular','emergency') DEFAULT 'regular',
    reason VARCHAR(300) NOT NULL,
    work VARCHAR(300) DEFAULT NULL,
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    manager_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (manager_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

ALTER TABLE leave_requests 
    ADD COLUMN submitted_by_role ENUM('employee','manager','admin') NOT NULL DEFAULT 'employee';

ALTER TABLE overtime_requests 
    ADD COLUMN submitted_by_role ENUM('employee','manager','admin') NOT NULL DEFAULT 'employee';

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

-- mock data 
INSERT INTO users
(username, password, full_name, email, role, department_id)
VALUES

('phoebe_bridgers', 'manager123', 'Phoebe Bridgers', 'phoebe.bridgers@staffire.test', 'manager', 1),
('sabrina_carpenter', 'manager123', 'Sabrina Carpenter', 'sabrina.carpenter@staffire.test', 'manager', 2),
('olivia_rodrigo', 'manager123', 'Olivia Rodrigo', 'olivia.rodrigo@staffire.test', 'manager', 3),
('taylor_swift', 'manager123', 'Taylor Swift', 'taylor.swift@staffire.test', 'manager', 4),
('timothee_chalamet', 'manager123', 'Timothee Chalamet', 'timothee.chalamet@staffire.test', 'manager', 5),


('paul_atreides', 'employee123', 'Paul Atreides', 'paul.atreides@staffire.test', 'employee', 1),
('peter_parker', 'employee123', 'Peter Parker', 'peter.parker@staffire.test', 'employee', 1),
('wednesday_addams', 'employee123', 'Wednesday Addams', 'wednesday.addams@staffire.test', 'employee', 1),


('chappell_roan', 'employee123', 'Chappell Roan', 'chappell.roan@staffire.test', 'employee', 2),
('jenna_ortega', 'employee123', 'Jenna Ortega', 'jenna.ortega@staffire.test', 'employee', 2),
('elle_woods', 'employee123', 'Elle Woods', 'elle.woods@staffire.test', 'employee', 2),


('billie_eilish', 'employee123', 'Billie Eilish', 'billie.eilish@staffire.test', 'employee', 3),
('zendaya', 'employee123', 'Zendaya', 'zendaya@staffire.test', 'employee', 3),
('bruce_wayne', 'employee123', 'Bruce Wayne', 'bruce.wayne@staffire.test', 'employee', 3),


('lana_del_rey', 'employee123', 'Lana Del Rey', 'lana.delrey@staffire.test', 'employee', 4),
('ariana_grande', 'employee123', 'Ariana Grande', 'ariana.grande@staffire.test', 'employee', 4),
('regina_george', 'employee123', 'Regina George', 'regina.george@staffire.test', 'employee', 4),


('harry_styles', 'employee123', 'Harry Styles', 'harry.styles@staffire.test', 'employee', 5),
('dua_lipa', 'employee123', 'Dua Lipa', 'dua.lipa@staffire.test', 'employee', 5),
('barbie', 'employee123', 'Barbie Roberts', 'barbie.roberts@staffire.test', 'employee', 5);

--leave requests
INSERT INTO leave_requests
(user_id, start_date, end_date, type, reason, status, manager_id, submitted_by_role)
VALUES
(7, '2026-09-28', '2026-09-30', 'vacation',
 'Traveling to Arrakis.',
 'approved', 2, 'employee'),

(8, '2026-10-03', '2026-10-03', 'emergency',
 'Personal emergency.',
 'pending', NULL, 'employee'),

(9, '2026-10-07', '2026-10-08', 'other',
 'Attending a family event.',
 'approved', 2, 'employee'),

(10, '2026-10-12', '2026-10-14', 'vacation',
 'Going out of town.',
 'approved', 3, 'employee'),

(11, '2026-10-15', '2026-10-16', 'sick',
 'Not feeling well.',
 'pending', NULL, 'employee'),

(12, '2026-10-19', '2026-10-19', 'other',
 'Personal appointment.',
 'rejected', 3, 'employee'),

(13, '2026-10-21', '2026-10-22', 'sick',
 'Doctor advised rest.',
 'approved', 4, 'employee'),

(14, '2026-10-26', '2026-10-28', 'vacation',
 'Personal vacation.',
 'pending', NULL, 'employee'),

(15, '2026-11-02', '2026-11-04', 'other',
 'Business matters in Gotham.',
 'approved', 4, 'employee'),

(16, '2026-11-09', '2026-11-11', 'vacation',
 'Short trip.',
 'approved', 5, 'employee'),

(17, '2026-11-13', '2026-11-13', 'sick',
 'Medical appointment.',
 'pending', NULL, 'employee'),

(18, '2026-11-16', '2026-11-17', 'other',
 'Personal commitment.',
 'rejected', 5, 'employee'),

(19, '2026-11-23', '2026-11-25', 'vacation',
 'Family vacation.',
 'approved', 6, 'employee'),

(20, '2026-11-27', '2026-11-27', 'emergency',
 'Urgent family concern.',
 'pending', NULL, 'employee'),

(21, '2026-12-01', '2026-12-04', 'vacation',
 'Visiting Barbie Land.',
 'approved', 6, 'employee');

--  leave requests
INSERT INTO leave_requests
(user_id, start_date, end_date, type, reason, status, manager_id, submitted_by_role)
VALUES


(9, '2026-09-28', '2026-09-30', 'vacation',
'Traveling to Arrakis.',
'approved', 4, 'employee'),

(10, '2026-10-05', '2026-10-05', 'emergency',
'Unexpected personal emergency.',
'pending', NULL, 'employee'),

(11, '2026-10-08', '2026-10-09', 'other',
'Family commitment.',
'rejected', 4, 'employee'),

(12, '2026-10-12', '2026-10-14', 'vacation',
'Personal vacation.',
'approved', 5, 'employee'),

(13, '2026-10-15', '2026-10-16', 'sick',
'Not feeling well and needs rest.',
'pending', NULL, 'employee'),

(14, '2026-10-19', '2026-10-19', 'other',
'Personal appointment.',
'approved', 5, 'employee'),

(15, '2026-10-21', '2026-10-22', 'sick',
'Medical consultation and rest.',
'approved', 6, 'employee'),

(16, '2026-10-26', '2026-10-28', 'vacation',
'Going on a short vacation.',
'pending', NULL, 'employee'),

(17, '2026-11-02', '2026-11-04', 'other',
'Important business in Gotham.',
'approved', 6, 'employee'),

(18, '2026-11-09', '2026-11-11', 'vacation',
'Going on a family trip.',
'approved', 7, 'employee'),

(19, '2026-11-13', '2026-11-13', 'sick',
'Medical appointment.',
'pending', NULL, 'employee'),

(20, '2026-11-16', '2026-11-17', 'other',
'Personal commitment.',
'rejected', 7, 'employee'),


(21, '2026-11-23', '2026-11-25', 'vacation',
'Family vacation.',
'approved', 8, 'employee'),

(22, '2026-11-27', '2026-11-27', 'emergency',
'Urgent family concern.',
'pending', NULL, 'employee'),

(23, '2026-12-01', '2026-12-04', 'vacation',
'Visiting Barbie Land.',
'approved', 8, 'employee');

--overtime requests
INSERT INTO overtime_requests
(user_id, overtime_date, start_time, end_time, total_hours,
 overtime_type, reason, work, status, manager_id, submitted_by_role)
VALUES

(9, '2026-09-22', '17:00:00', '20:00:00', 3.00,
'regular',
'Needed additional time to finish system tasks.',
'Completed system maintenance.',
'approved', 4, 'employee'),

(10, '2026-09-23', '17:00:00', '19:30:00', 2.50,
'emergency',
'Urgent system issue.',
'Fixed critical application bugs.',
'approved', 4, 'employee'),

(11, '2026-09-24', '17:00:00', '19:00:00', 2.00,
'regular',
'Pending documentation needed completion.',
'Completed system documentation.',
'pending', NULL, 'employee'),


(12, '2026-09-25', '17:00:00', '20:00:00', 3.00,
'regular',
'Employee files needed updating.',
'Updated employee records.',
'approved', 5, 'employee'),

(13, '2026-09-26', '17:00:00', '18:30:00', 1.50,
'regular',
'Recruitment applications required review.',
'Reviewed applicant records.',
'pending', NULL, 'employee'),

(14, '2026-09-27', '17:00:00', '19:00:00', 2.00,
'regular',
'Required completion of monthly HR reports.',
'Prepared monthly HR reports.',
'rejected', 5, 'employee'),


(15, '2026-09-28', '17:00:00', '21:00:00', 4.00,
'regular',
'Monthly financial report deadline.',
'Prepared financial reports.',
'approved', 6, 'employee'),

(16, '2026-09-29', '17:00:00', '20:00:00', 3.00,
'regular',
'Pending transaction reconciliation.',
'Reconciled financial transactions.',
'pending', NULL, 'employee'),

(17, '2026-09-30', '17:00:00', '22:00:00', 5.00,
'emergency',
'Urgent financial review.',
'Reviewed company financial accounts.',
'approved', 6, 'employee'),


(18, '2026-10-01', '17:00:00', '19:30:00', 2.50,
'regular',
'Campaign deadline approaching.',
'Prepared campaign materials.',
'approved', 7, 'employee'),

(19, '2026-10-02', '17:00:00', '20:00:00', 3.00,
'regular',
'Additional promotional materials required.',
'Prepared promotional content.',
'pending', NULL, 'employee'),

(20, '2026-10-03', '17:00:00', '19:00:00', 2.00,
'regular',
'Needed additional time for campaign planning.',
'Completed marketing schedule.',
'rejected', 7, 'employee'),

(21, '2026-10-04', '17:00:00', '20:00:00', 3.00,
'regular',
'Client proposals required completion.',
'Prepared client proposals.',
'approved', 8, 'employee'),

(22, '2026-10-05', '17:00:00', '19:30:00', 2.50,
'regular',
'Additional sales reports were required.',
'Prepared weekly sales reports.',
'pending', NULL, 'employee'),

(23, '2026-10-06', '17:00:00', '21:00:00', 4.00,
'emergency',
'Urgent client presentation.',
'Prepared client presentation.',
'approved', 8, 'employee');
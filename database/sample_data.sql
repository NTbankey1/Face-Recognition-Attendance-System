-- ============================================
-- FILE: sample_data.sql
-- MÔ TẢ: File SQL mẫu để thêm dữ liệu vào database
-- SỬ DỤNG: mysql -u root attendance-db < database/sample_data.sql
-- ============================================

USE `attendance-db`;

-- ============================================
-- 1. THÊM ADMIN MỚI
-- ============================================
-- Lưu ý: Password phải được hash bằng password_hash() trong PHP
-- Để tạo hash: php -r "echo password_hash('your_password', PASSWORD_BCRYPT);"
-- 
-- Ví dụ với password '@admin_':
-- INSERT INTO tbladmin (firstName, lastName, emailAddress, password) 
-- VALUES ('Admin2', 'User', 'admin2@gmail.com', '$2y$12$eL2jcpxx9lH4TYvRIjXnsudbG7OSChqgAb7EOCANJdiSuEuSgJEg6');

-- ============================================
-- 2. THÊM FACULTY (Khoa) MỚI
-- ============================================
INSERT INTO tblfaculty (facultyName, facultyCode, dateRegistered) 
VALUES 
('Engineering', 'ENG', CURDATE()),
('Business Administration', 'BA', CURDATE()),
('Science', 'SCI', CURDATE());

-- ============================================
-- 3. THÊM COURSE (Khóa học) MỚI
-- ============================================
-- Lưu ý: facultyID phải tồn tại trong tblfaculty
-- Kiểm tra: SELECT Id FROM tblfaculty WHERE facultyCode = 'CIT';
INSERT INTO tblcourse (name, facultyID, dateCreated, courseCode) 
VALUES 
('Software Engineering', 8, CURDATE(), 'SE'),
('Data Science', 8, CURDATE(), 'DS'),
('Network Engineering', 8, CURDATE(), 'NE');

-- ============================================
-- 4. THÊM LECTURE (Giảng viên) MỚI
-- ============================================
-- Lưu ý: Password phải được hash bằng password_hash() trong PHP
-- Để tạo hash: php -r "echo password_hash('your_password', PASSWORD_BCRYPT);"
-- 
-- Ví dụ với password '@mark_':
INSERT INTO tbllecture (firstName, lastName, emailAddress, password, phoneNo, facultyCode, dateCreated) 
VALUES 
('John', 'Smith', 'john.smith@gmail.com', '$2y$12$ThYLvMNlZ2WI1Y3gapedDu.5itqmvhWvy0hAUr0EkhVgzEiibWCJG', '0123456789', 'CIT', CURDATE()),
('Sarah', 'Johnson', 'sarah.j@gmail.com', '$2y$12$U75wDuUCTUecWRI6qXOBFOOdPR796d7vEwxi1SfmmWBsTC5jsQMv.', '0987654321', 'CIT', CURDATE());

-- ============================================
-- 5. THÊM STUDENT (Sinh viên) MỚI
-- ============================================
-- Lưu ý: 
-- - faculty phải là facultyCode (ví dụ: 'CIT')
-- - courseCode phải tồn tại trong tblcourse
-- - studentImage sẽ được tạo tự động khi upload ảnh qua web
INSERT INTO tblstudents (firstName, lastName, registrationNumber, email, faculty, courseCode, studentImage, dateRegistered) 
VALUES 
('Nguyen', 'Van A', 'SV001', 'sv001@example.com', 'CIT', 'BCT', '', CURDATE()),
('Tran', 'Thi B', 'SV002', 'sv002@example.com', 'CIT', 'BCT', '', CURDATE()),
('Le', 'Van C', 'SV003', 'sv003@example.com', 'CIT', 'SE', '', CURDATE()),
('Pham', 'Thi D', 'SV004', 'sv004@example.com', 'CIT', 'DS', '', CURDATE());

-- ============================================
-- 6. THÊM UNIT (Môn học) MỚI
-- ============================================
-- Lưu ý: courseID phải là Id của course trong tblcourse
-- Kiểm tra: SELECT Id FROM tblcourse WHERE courseCode = 'BCT';
INSERT INTO tblunit (name, unitCode, courseID, dateCreated) 
VALUES 
('Web Development', 'BCT 2412', '10', CURDATE()),
('Database Systems', 'BCT 2413', '10', CURDATE()),
('Mobile App Development', 'SE 3001', (SELECT Id FROM tblcourse WHERE courseCode = 'SE' LIMIT 1), CURDATE());

-- ============================================
-- 7. THÊM VENUE (Địa điểm) MỚI
-- ============================================
INSERT INTO tblvenue (className, facultyCode, currentStatus, capacity, classification, dateCreated) 
VALUES 
('A101', 'CIT', 'available', 50, 'lecture hall', CURDATE()),
('B201', 'CIT', 'available', 30, 'laboratory', CURDATE()),
('C301', 'CIT', 'available', 40, 'computer lab', CURDATE()),
('D401', 'CIT', 'available', 25, 'office', CURDATE());

-- ============================================
-- 8. THÊM ATTENDANCE (Điểm danh) MẪU
-- ============================================
-- Lưu ý: 
-- - studentRegistrationNumber phải tồn tại trong tblstudents
-- - course và unit phải tồn tại
INSERT INTO tblattendance (studentRegistrationNumber, course, attendanceStatus, dateMarked, unit) 
VALUES 
('SV001', 'BCT', 'Present', CURDATE(), 'BCT 2411'),
('SV002', 'BCT', 'Present', CURDATE(), 'BCT 2411'),
('SV001', 'BCT', 'Absent', DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'BCT 2411'),
('SV003', 'SE', 'Present', CURDATE(), 'SE 3001');

-- ============================================
-- XEM DỮ LIỆU SAU KHI THÊM
-- ============================================
-- Chạy các lệnh sau để kiểm tra:
-- SELECT COUNT(*) as total_students FROM tblstudents;
-- SELECT COUNT(*) as total_lectures FROM tbllecture;
-- SELECT COUNT(*) as total_courses FROM tblcourse;
-- SELECT COUNT(*) as total_venues FROM tblvenue;
-- SELECT COUNT(*) as total_attendance FROM tblattendance;








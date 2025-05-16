-- QR Attendance System Database Schema

-- Create database
CREATE DATABASE IF NOT EXISTS qr_attendance;
USE qr_attendance;

-- Users table (for admins and teachers)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'teacher') NOT NULL DEFAULT 'teacher',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Students table
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Classes table
CREATE TABLE classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    teacher_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Class-Student relationship table
CREATE TABLE class_students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    student_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    UNIQUE KEY (class_id, student_id)
);

-- Attendance sessions table
CREATE TABLE attendance_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    session_date DATE NOT NULL,
    session_time TIME NOT NULL,
    qr_code_data VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
);

-- Attendance records table
CREATE TABLE attendance_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    student_id INT NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES attendance_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    UNIQUE KEY (session_id, student_id)
);

-- Add indexes for performance
CREATE INDEX idx_attendance_records_session_id ON attendance_records(session_id);
CREATE INDEX idx_attendance_records_student_id ON attendance_records(student_id);
CREATE INDEX idx_class_students_class_id ON class_students(class_id);
CREATE INDEX idx_class_students_student_id ON class_students(student_id);

-- Insert default admin account
INSERT INTO users (username, password, role) VALUES 
('admin', '$2y$10$8WxmVVhKZvSFfT1vQc.Xb.w2M9/L3aIe5JeVKJHjvs3Hdz5GP4Iva', 'admin');
-- Default password: admin123

-- Sample data for demonstration
-- Insert sample teachers
INSERT INTO users (username, password, role) VALUES 
('teacher1', '$2y$10$8WxmVVhKZvSFfT1vQc.Xb.w2M9/L3aIe5JeVKJHjvs3Hdz5GP4Iva', 'teacher'),
('teacher2', '$2y$10$8WxmVVhKZvSFfT1vQc.Xb.w2M9/L3aIe5JeVKJHjvs3Hdz5GP4Iva', 'teacher');
-- Default password: admin123

-- Insert sample classes
INSERT INTO classes (name, teacher_id) VALUES 
('BSIT-1A', 2),
('BSIT-1B', 2),
('BSIT-2A', 3),
('BSIT-2B', 3);

-- Insert sample students
INSERT INTO students (student_id, password) VALUES 
('20-1001', '$2y$10$8WxmVVhKZvSFfT1vQc.Xb.w2M9/L3aIe5JeVKJHjvs3Hdz5GP4Iva'),
('20-1002', '$2y$10$8WxmVVhKZvSFfT1vQc.Xb.w2M9/L3aIe5JeVKJHjvs3Hdz5GP4Iva'),
('20-1003', '$2y$10$8WxmVVhKZvSFfT1vQc.Xb.w2M9/L3aIe5JeVKJHjvs3Hdz5GP4Iva'),
('20-1004', '$2y$10$8WxmVVhKZvSFfT1vQc.Xb.w2M9/L3aIe5JeVKJHjvs3Hdz5GP4Iva'),
('20-1005', '$2y$10$8WxmVVhKZvSFfT1vQc.Xb.w2M9/L3aIe5JeVKJHjvs3Hdz5GP4Iva'),
('19-2001', '$2y$10$8WxmVVhKZvSFfT1vQc.Xb.w2M9/L3aIe5JeVKJHjvs3Hdz5GP4Iva'),
('19-2002', '$2y$10$8WxmVVhKZvSFfT1vQc.Xb.w2M9/L3aIe5JeVKJHjvs3Hdz5GP4Iva'),
('19-2003', '$2y$10$8WxmVVhKZvSFfT1vQc.Xb.w2M9/L3aIe5JeVKJHjvs3Hdz5GP4Iva'),
('19-2004', '$2y$10$8WxmVVhKZvSFfT1vQc.Xb.w2M9/L3aIe5JeVKJHjvs3Hdz5GP4Iva'),
('19-2005', '$2y$10$8WxmVVhKZvSFfT1vQc.Xb.w2M9/L3aIe5JeVKJHjvs3Hdz5GP4Iva');
-- Default password: admin123

-- Assign students to classes
INSERT INTO class_students (class_id, student_id) VALUES 
(1, 1), (1, 2), (1, 3), (1, 4), (1, 5),
(2, 1), (2, 2), (2, 3),
(3, 6), (3, 7), (3, 8), (3, 9), (3, 10),
(4, 8), (4, 9), (4, 10);

-- Sample attendance sessions
INSERT INTO attendance_sessions (class_id, session_date, session_time, qr_code_data) VALUES 
(1, CURDATE(), '08:00:00', 'QR_ATTENDANCE_1234567890_1_1234'),
(2, CURDATE(), '10:00:00', 'QR_ATTENDANCE_1234567891_2_5678'),
(3, CURDATE(), '13:00:00', 'QR_ATTENDANCE_1234567892_3_9012'),
(4, CURDATE(), '15:00:00', 'QR_ATTENDANCE_1234567893_4_3456');

-- Sample attendance records
INSERT INTO attendance_records (session_id, student_id) VALUES 
(1, 1), (1, 2), (1, 3),
(2, 1), (2, 2),
(3, 6), (3, 7), (3, 8),
(4, 8), (4, 9);

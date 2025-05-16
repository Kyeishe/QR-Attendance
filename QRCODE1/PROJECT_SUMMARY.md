# QR Attendance System - Project Summary

## Project Overview
The QR Attendance System is a web-based application that allows teachers and administrators to track student attendance using QR codes. Teachers can generate QR codes for their classes, and students can scan these codes to mark their attendance.

## Key Features
1. **User Authentication**
   - Admin, teacher, and student login
   - Student registration

2. **Class Management**
   - Create, edit, and delete classes
   - Assign students to classes

3. **Attendance Tracking**
   - Generate QR codes for attendance sessions
   - Students scan QR codes to mark attendance
   - Manual attendance marking option
   - View and export attendance reports

4. **QR Code Management**
   - Store QR codes until they expire
   - Delete expired QR codes
   - View all active QR codes

## Database Structure

### Tables
1. **users**
   - id (PK)
   - username
   - password
   - role (admin/teacher)

2. **students**
   - id (PK)
   - student_id (unique identifier)
   - password

3. **classes**
   - id (PK)
   - name
   - teacher_id (FK to users)

4. **class_students**
   - id (PK)
   - class_id (FK to classes)
   - student_id (FK to students)

5. **attendance_sessions**
   - id (PK)
   - class_id (FK to classes)
   - session_date
   - session_time
   - qr_code_data
   - created_at

6. **attendance_records**
   - id (PK)
   - session_id (FK to attendance_sessions)
   - student_id (FK to students)
   - timestamp

## File Structure

### Core Files
- **index.php** - Landing page with links to login
- **config/database.php** - Database connection configuration

### Authentication
- **pages/login.php** - Combined login for admin, teachers, and students
- **pages/register_student.php** - Student registration
- **pages/logout.php** - Logout functionality

### Admin/Teacher Pages
- **pages/dashboard.php** - Admin/teacher dashboard
- **pages/manage_users.php** - Manage admin and teacher accounts
- **pages/manage_students.php** - Manage student accounts
- **pages/manage_classes.php** - Manage classes
- **pages/attendance.php** - Generate QR codes for attendance
- **pages/qr_code_list.php** - View and manage QR codes
- **pages/reports.php** - View attendance reports
- **pages/view_attendance.php** - View attendance for a specific session

### Student Pages
- **pages/student_dashboard.php** - Student dashboard
- **pages/student_mark_attendance.php** - Mark attendance using QR code
- **pages/student_qr_codes.php** - View QR codes for enrolled classes

### Includes
- **includes/header.php** - Common header with navigation
- **includes/footer.php** - Common footer with scripts

### Assets
- **assets/css/style.css** - Custom CSS styles
- **assets/js/scripts.js** - Custom JavaScript functions

## User Workflows

### Admin Workflow
1. Login as admin
2. Manage users (add/edit/delete teachers)
3. Manage students (add/edit/delete students)
4. Manage classes (create/edit/delete classes)
5. View attendance reports

### Teacher Workflow
1. Login as teacher
2. Manage assigned classes
3. Generate QR codes for attendance
4. View and export attendance reports

### Student Workflow
1. Login as student (or register if new)
2. View enrolled classes
3. Scan QR code or enter session code to mark attendance
4. View attendance history

## Technical Implementation

### Authentication System
- Password hashing using PHP's password_hash() function
- Session-based authentication
- Role-based access control

### QR Code Generation
- Uses JavaScript library (qrcodejs) for client-side QR code generation
- QR codes contain unique session identifiers

### Responsive Design
- Bootstrap 5 framework for responsive layout
- Mobile-friendly interface for easy QR code scanning

## Security Features
- Password hashing
- Input validation and sanitization
- Prepared SQL statements to prevent SQL injection
- Session management
- Role-based access control

## Future Enhancements
1. Email notifications for absent students
2. Integration with school management systems
3. Mobile app for easier QR code scanning
4. Advanced analytics and reporting
5. Automatic attendance reminders

## Demonstration Instructions
1. Login as admin (username: admin, password: admin123)
2. Create a new class
3. Add students to the class
4. Generate a QR code for attendance
5. Login as a student and mark attendance
6. View attendance reports

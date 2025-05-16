# QR Attendance System

A web-based application for tracking student attendance using QR codes.

## Overview

The QR Attendance System simplifies the process of tracking student attendance by using QR codes. Teachers can generate QR codes for their classes, and students can scan these codes to mark their attendance automatically. The system provides real-time attendance data and eliminates the need for paper-based records.

## Features

### Admin/Teacher Features
- **User Authentication**: Secure login for admins, teachers, and students
- **Class Management**: Create, edit, and delete classes
- **Student Management**: Add, edit, and delete students
- **QR Code Generation**: Generate unique QR codes for attendance sessions
- **QR Code Management**: Store QR codes until they expire, with option to delete expired codes
- **Attendance Tracking**: View which students have marked attendance
- **Reporting**: View and export attendance reports with filtering options

### Student Features
- **Class Enrollment**: Browse and enroll in available classes with search functionality
- **QR Code Viewing**: See QR codes for enrolled classes
- **Attendance Marking**: Scan or enter QR codes to mark attendance
- **Status Indicators**: Clear indicators for expired sessions

## Technology Stack

- **Frontend**: HTML, CSS, JavaScript, Bootstrap 5
- **Backend**: PHP
- **Database**: MySQL
- **QR Code Generation**: qrcodejs library
- **Time Zone**: Philippines (UTC+8)

## Installation

1. **Clone the repository**
   ```
   git clone https://github.com/yourusername/qr-attendance-system.git
   ```

2. **Set up the database**
   - Create a MySQL database named `qr_attendance`
   - Import the `DATABASE_SCHEMA.sql` file to create the tables and sample data

3. **Configure the database connection**
   - Edit the `config/database.php` file with your database credentials

4. **Deploy to a PHP server**
   - Copy the files to your web server directory (e.g., htdocs for XAMPP)
   - Ensure the server has PHP 7.0+ installed

## Usage

### Admin/Teacher

1. **Login**
   - Use the admin account (username: admin, password: admin123) or a teacher account

2. **Manage Classes**
   - Create new classes
   - Edit existing classes
   - Delete classes

3. **Manage Students**
   - Add new students
   - Edit student information
   - Delete students

4. **Generate QR Codes**
   - Select a class
   - Set date and time
   - Generate a QR code for attendance

5. **View Reports**
   - Filter by class and date range
   - View attendance statistics
   - Export reports to CSV

### Student

1. **Login/Register**
   - Login with student ID and password
   - Register if new to the system

2. **Enroll in Classes**
   - Browse available classes
   - Search for specific classes
   - Enroll in classes of interest

3. **Mark Attendance**
   - Scan QR code with device camera
   - Or enter the session code manually
   - See clear indicators for expired sessions

4. **View Attendance History**
   - See past attendance records
   - View enrolled classes

## Project Structure

```
QRCODE/
├── config/
│   ├── database.php     # Database connection configuration
│   └── timezone.php     # Time zone configuration
├── includes/
│   ├── header.php       # Common header
│   └── footer.php       # Common footer
├── pages/
│   ├── attendance.php           # QR code generation
│   ├── dashboard.php            # Admin/teacher dashboard
│   ├── edit_class.php           # Edit class information
│   ├── edit_student.php         # Edit student information
│   ├── edit_user.php            # Edit user information
│   ├── export_attendance.php    # Export attendance data
│   ├── export_reports.php       # Export reports
│   ├── login.php                # Login page
│   ├── logout.php               # Logout functionality
│   ├── manage_classes.php       # Class management
│   ├── manage_class_students.php # Manage students in classes
│   ├── manage_students.php      # Student management
│   ├── manage_users.php         # User management
│   ├── print_attendance.php     # Print attendance reports
│   ├── qr_code_list.php         # QR code management
│   ├── register.php             # User registration
│   ├── register_student.php     # Student registration
│   ├── reports.php              # View reports
│   ├── scan_attendance.php      # Scan QR codes
│   ├── student_attendance.php   # Student attendance view
│   ├── student_dashboard.php    # Student dashboard
│   ├── student_enroll.php       # Class enrollment for students
│   ├── student_mark_attendance.php # Mark attendance
│   ├── student_qr_codes.php     # Student QR code view
│   └── view_attendance.php      # View attendance records
└── index.php                    # Main entry point
```

## Default Accounts

- **Admin**: username: admin, password: admin123
- **Teacher**: username: teacher1, password: admin123
- **Student**: student_id: 20-1001, password: admin123

## QR Code Expiration

QR codes expire based on the session date and time:
- Expired sessions show "Expired Session" with "Attendance cannot be marked"
- Students cannot mark attendance for expired sessions
- All time calculations use the Philippines time zone (UTC+8)
- Admin can delete expired QR codes individually or all at once

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Acknowledgements

- Bootstrap for the responsive UI components
- qrcodejs for QR code generation
- Font Awesome for icons

# QR Attendance System - Key Features Visual Guide

## 1. User Authentication

### Admin/Teacher Login
```
+----------------------------------+
|           Login Form             |
+----------------------------------+
| Username: [admin          ]      |
| Password: [********      ]      |
|                                  |
|         [Login Button]           |
+----------------------------------+
```

### Student Login/Registration
```
+----------------------------------+
|           Login Form             |
+----------------------------------+
| Student ID: [20-1001     ]      |
| Password:   [********    ]      |
|                                  |
|         [Login Button]           |
|                                  |
| Don't have an account?           |
| [Register here]                  |
+----------------------------------+
```

## 2. Admin/Teacher Dashboard

```
+----------------------------------+
|         Admin Dashboard          |
+----------------------------------+
| Welcome, admin!                  |
|                                  |
| +--------+ +--------+ +--------+ |
| | Classes| |Students| |Sessions| |
| |   7    | |   35   | |   12   | |
| +--------+ +--------+ +--------+ |
|                                  |
| Recent Attendance Sessions:      |
| +------------------------------+ |
| | Date  | Class | Attendance  | |
| | 05/15 | BSIT1A| 25/30 (83%) | |
| | 05/15 | BSIT2B| 18/25 (72%) | |
| | 05/14 | BSIT1A| 28/30 (93%) | |
| +------------------------------+ |
|                                  |
| [Take Attendance] [View Reports] |
+----------------------------------+
```

## 3. Class Management

```
+----------------------------------+
|        Manage Classes            |
+----------------------------------+
| Add New Class:                   |
| +------------------------------+ |
| | Class Name: [BSIT-1C      ] | |
| |                              | |
| |       [Add Class Button]     | |
| +------------------------------+ |
|                                  |
| Class List:                      |
| +------------------------------+ |
| | Class Name | Teacher | Actions| |
| | BSIT-1A    | Teacher1| [···] | |
| | BSIT-1B    | Teacher1| [···] | |
| | BSIT-2A    | Teacher2| [···] | |
| | BSIT-2B    | Teacher2| [···] | |
| +------------------------------+ |
+----------------------------------+
```

## 4. Student Management

```
+----------------------------------+
|        Manage Students           |
+----------------------------------+
| Add New Student:                 |
| +------------------------------+ |
| | Student ID: [20-1006      ] | |
| | Password:   [********     ] | |
| |                              | |
| |     [Add Student Button]     | |
| +------------------------------+ |
|                                  |
| Student List:                    |
| +------------------------------+ |
| | Student ID | Actions         | |
| | 20-1001    | [Edit] [Delete] | |
| | 20-1002    | [Edit] [Delete] | |
| | 20-1003    | [Edit] [Delete] | |
| | 19-2001    | [Edit] [Delete] | |
| +------------------------------+ |
+----------------------------------+
```

## 5. QR Code Generation

```
+----------------------------------+
|       Generate QR Code           |
+----------------------------------+
| Create Attendance Session:       |
| +------------------------------+ |
| | Class:    [BSIT-1A        ▼] | |
| | Date:     [2023-05-16      ] | |
| | Time:     [08:00           ] | |
| |                              | |
| |   [Generate QR Code Button]  | |
| +------------------------------+ |
|                                  |
| Generated QR Code:               |
| +------------------------------+ |
| |                              | |
| |          [QR CODE]           | |
| |                              | |
| | Class: BSIT-1A               | |
| | Date: 2023-05-16             | |
| | Time: 08:00                  | |
| |                              | |
| | [Scan] [View Attendance]     | |
| +------------------------------+ |
+----------------------------------+
```

## 6. QR Code List Management

```
+----------------------------------+
|         QR Code List             |
+----------------------------------+
| Filter QR Codes:                 |
| +------------------------------+ |
| | Class: [All Classes       ▼] | |
| | Date:  [2023-05-16        ] | |
| | [✓] Show Expired QR Codes    | |
| |                              | |
| |      [Apply Filters]         | |
| +------------------------------+ |
|                                  |
| [Delete All Expired QR Codes]    |
| [Delete Selected QR Codes]       |
|                                  |
| QR Codes:                        |
| +------------------------------+ |
| | [✓] BSIT-1A | 2023-05-15    | |
| |     [QR CODE]                | |
| |     Expires today            | |
| |     [Scan] [View] [Delete]   | |
| +------------------------------+ |
| | [ ] BSIT-2B | 2023-05-16    | |
| |     [QR CODE]                | |
| |     Expires in 1 day         | |
| |     [Scan] [View] [Delete]   | |
| +------------------------------+ |
+----------------------------------+
```

## 7. Student Marking Attendance

```
+----------------------------------+
|        Mark Attendance           |
+----------------------------------+
| Enter Session Code:              |
| +------------------------------+ |
| | Session Code:                | |
| | [QR_ATTENDANCE_1234567890_1] | |
| |                              | |
| |    [Mark Attendance Button]  | |
| +------------------------------+ |
|                                  |
| Or scan QR code with your device |
|                                  |
| [Back to Dashboard]              |
+----------------------------------+
```

## 8. Attendance Reports

```
+----------------------------------+
|       Attendance Reports         |
+----------------------------------+
| Filter Reports:                  |
| +------------------------------+ |
| | Class: [BSIT-1A           ▼] | |
| | Start: [2023-05-01        ] | |
| | End:   [2023-05-16        ] | |
| |                              | |
| |      [Apply Filters]         | |
| +------------------------------+ |
|                                  |
| Attendance Sessions:             |
| +------------------------------+ |
| | Date  | Class | Attendance  | |
| | 05/15 | BSIT1A| 25/30 (83%) | |
| |       |       | [███████░░░] | |
| | 05/14 | BSIT1A| 28/30 (93%) | |
| |       |       | [█████████░] | |
| | 05/13 | BSIT1A| 22/30 (73%) | |
| |       |       | [███████░░░] | |
| +------------------------------+ |
|                                  |
| [Export to CSV]                  |
+----------------------------------+
```

## 9. View Attendance Details

```
+----------------------------------+
|      Attendance Details          |
+----------------------------------+
| Class: BSIT-1A                   |
| Date: May 15, 2023               |
| Time: 08:00 AM                   |
|                                  |
| Attendance: 25/30 (83%)          |
|                                  |
| Student List:                    |
| +------------------------------+ |
| | Student ID | Status  | Time  | |
| | 20-1001    | Present | 08:02 | |
| | 20-1002    | Present | 08:03 | |
| | 20-1003    | Present | 08:01 | |
| | 20-1004    | Absent  |  -    | |
| | 20-1005    | Present | 08:05 | |
| +------------------------------+ |
|                                  |
| [Print] [Export] [Back]          |
+----------------------------------+
```

## 10. Student Dashboard

```
+----------------------------------+
|       Student Dashboard          |
+----------------------------------+
| Welcome, 20-1001!                |
|                                  |
| Your Classes:                    |
| +------------------------------+ |
| | BSIT-1A | Teacher: Teacher1  | |
| | BSIT-1B | Teacher: Teacher1  | |
| +------------------------------+ |
|                                  |
| Your Attendance:                 |
| +------------------------------+ |
| | Date  | Class | Time  | Status| |
| | 05/15 | BSIT1A| 08:02 |Present| |
| | 05/15 | BSIT1B| 10:05 |Present| |
| | 05/14 | BSIT1A| 08:01 |Present| |
| | 05/13 | BSIT1A|   -   | Absent| |
| +------------------------------+ |
|                                  |
| [Mark Attendance] [View QR Codes]|
+----------------------------------+
```

## 11. Student QR Codes View

```
+----------------------------------+
|     QR Codes for Your Classes    |
+----------------------------------+
| Your Classes:                    |
| +------------------------------+ |
| | BSIT-1A | Teacher: Teacher1  | |
| | BSIT-1B | Teacher: Teacher1  | |
| +------------------------------+ |
|                                  |
| Upcoming Attendance Sessions:    |
| +------------------------------+ |
| | BSIT-1A | 2023-05-16 | 08:00 | |
| |     [QR CODE]                | |
| |                              | |
| |    [Mark Attendance]         | |
| +------------------------------+ |
| | BSIT-1B | 2023-05-16 | 10:00 | |
| |     [QR CODE]                | |
| |                              | |
| |    [Mark Attendance]         | |
| +------------------------------+ |
+----------------------------------+
```

## 12. System Flow Diagram

```
+-------------+     +--------------+     +---------------+
| Admin Login |---->| Create Class |---->| Add Students  |
+-------------+     +--------------+     +---------------+
                           |                     |
                           v                     v
                    +--------------+     +---------------+
                    | Generate QR  |<----| Assign to     |
                    | Code         |     | Class         |
                    +--------------+     +---------------+
                           |
                           v
+-------------+     +--------------+     +---------------+
| Student     |---->| Scan QR Code |---->| Attendance    |
| Login       |     | or Enter Code|     | Recorded      |
+-------------+     +--------------+     +---------------+
                                                |
                                                v
                    +--------------+     +---------------+
                    | View         |<----| Generate      |
                    | Reports      |     | Reports       |
                    +--------------+     +---------------+
```

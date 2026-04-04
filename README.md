# SchoolMS — School Management System
### Built with: Procedural PHP + MySQLi + MySQL + HTML/CSS/JS

---

## 📁 FOLDER STRUCTURE

```
school-system/
│
├── config/
│   └── db.php               ← Database connection + helper functions
│
├── admin/
│   ├── dashboard.php        ← Admin home with stats
│   ├── students.php         ← Add/Edit/Delete students
│   ├── teachers.php         ← Add/Edit/Delete teachers
│   ├── parents.php          ← Manage parents/guardians
│   ├── classes.php          ← Manage classes + assign subjects
│   ├── subjects.php         ← Manage subjects
│   ├── attendance.php       ← Mark + view attendance
│   ├── results.php          ← Enter student scores
│   ├── payments.php         ← Record + view payments
│   ├── timetable.php        ← Create class timetables
│   ├── notifications.php    ← Publish announcements
│   └── reports.php          ← Printable result sheets
│
├── teacher/
│   ├── dashboard.php        ← Teacher home
│   ├── attendance.php       ← Mark attendance for assigned class
│   ├── results.php          ← Enter scores for assigned subject
│   └── timetable.php        ← View own timetable
│
├── student/
│   ├── dashboard.php        ← Student home + profile
│   ├── results.php          ← View results + class rank
│   ├── attendance.php       ← View own attendance + rate
│   ├── payments.php         ← View payments + print receipt
│   └── timetable.php        ← View class timetable
│
├── includes/
│   ├── header.php           ← HTML head + sidebar + topbar
│   ├── footer.php           ← Closing HTML tags
│   └── auth.php             ← Session + role guard functions
│
├── assets/
│   ├── css/style.css        ← All styles (Navy + Amber theme)
│   └── js/main.js           ← UI helpers (modal, clock, alerts)
│
├── uploads/
│   └── students/            ← Student photo uploads
│
├── login.php                ← Login page (all roles)
├── logout.php               ← Destroys session
├── index.php                ← Redirects to correct dashboard
└── database.sql             ← Full SQL schema + seed data
```

---

## ⚙️ INSTALLATION STEPS

### 1. Requirements
- PHP 7.4+ (with MySQLi extension)
- MySQL 5.7+ or MariaDB 10+
- Apache/Nginx (XAMPP, WAMP, LAMP all work)

### 2. Setup
```bash
# 1. Copy the school-system/ folder into your web root:
#    e.g. /var/www/html/school-system/
#    or   C:/xampp/htdocs/school-system/

# 2. Import the database
#    Open phpMyAdmin → create database "school_db" → Import database.sql

# 3. Edit database credentials in config/db.php:
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'school_db');

# 4. Make sure the uploads folder is writable:
chmod -R 775 uploads/
```

### 3. Open in browser
```
http://localhost/school-system/
```

---

## 🔑 DEFAULT LOGIN

| Role    | Username | Password |
|---------|----------|----------|
| Admin   | admin    | admin123 |

> After logging in as admin, create teacher and student accounts from the management pages.

---

## 🧩 FEATURES

| Feature               | Admin | Teacher | Student |
|-----------------------|-------|---------|---------|
| Dashboard             | ✅    | ✅      | ✅      |
| Student Management    | ✅    | —       | —       |
| Teacher Management    | ✅    | —       | —       |
| Parent Management     | ✅    | —       | —       |
| Class & Subject Mgmt  | ✅    | —       | —       |
| Mark Attendance       | ✅    | ✅      | View    |
| Enter Results         | ✅    | ✅      | View    |
| Timetable             | ✅    | View    | View    |
| Payments              | ✅    | —       | View    |
| Notifications         | ✅    | View    | View    |
| Printable Reports     | ✅    | —       | Print   |

---

## 🛠 GRADE SCALE

| Grade | Range  | Remark    |
|-------|--------|-----------|
| A     | 70–100 | Excellent |
| B     | 60–69  | Very Good |
| C     | 50–59  | Good      |
| D     | 40–49  | Pass      |
| F     | 0–39   | Fail      |

CA = max 40 marks | Exam = max 60 marks | Total = 100

---

## 🔒 SECURITY NOTES

- All passwords are hashed with `password_hash()` using `PASSWORD_DEFAULT`
- All user inputs are sanitized with `mysqli_real_escape_string()`
- Sessions are used for authentication
- Role-based access control prevents unauthorized access
- For production: enable HTTPS, use prepared statements for extra safety

---

## 📝 TECH STACK

- **Backend**: Procedural PHP (no OOP, no PDO, no frameworks)
- **Database**: MySQL via MySQLi procedural functions only
- **Frontend**: Plain HTML5 + CSS3 + Vanilla JavaScript
- **Fonts**: Plus Jakarta Sans (Google Fonts)
- **Icons**: Font Awesome 6
- **No npm, no composer, no build tools needed**

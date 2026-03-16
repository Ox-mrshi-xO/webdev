# DSRRM Clinic Management System
**Bestlink College of the Philippines**

---

## 📁 File Structure
```
clinic_system/              ← Main system folder (place in your web root)
├── sql/
│   └── clinic_db.sql       ← Import this first!
├── config/
│   ├── database.php        ← DB credentials here
│   └── init.php
├── classes/
│   ├── Database.php
│   ├── Auth.php
│   ├── Patient.php
│   ├── Medicine.php
│   └── Models.php          ← Appointment, MedicalRecord, UserManager, Notification, etc.
├── css/
│   ├── frontpage.css       ← For public pages
│   └── dashboard.css       ← For admin/user dashboards
├── js/
│   └── dashboard.js
├── admin/
│   ├── index.php           ← Admin Dashboard
│   ├── patients.php        ← Patient Records (CRUD)
│   ├── patient_history.php ← Medical History (CRUD)
│   ├── medicines.php       ← Medicine Stock (CRUD + Low Stock Alerts)
│   ├── appointments.php    ← Appointments (CRUD)
│   ├── consultations.php   ← Consultation Requests
│   ├── medicine_requests.php
│   ├── reports.php         ← Clinic Reports with Charts
│   ├── users.php           ← User Management (CRUD)
│   └── partials/
│       ├── sidebar.php
│       └── topbar.php
├── user/
│   ├── index.php           ← Student Dashboard
│   ├── consultation.php    ← Online Consultation Request
│   ├── medical_record.php  ← View My Medical Records
│   ├── medicine_request.php
│   ├── appointments.php
│   ├── notifications.php
│   └── partials/
│       ├── sidebar.php
│       └── topbar.php
├── login.php               ← Login Page
├── logout.php
└── img/                    ← Place Logo.jpg and bestlink.jpg here

Your existing front pages (index.php, services.php, etc.)
should be in the same root folder and link to clinic_system/login.php
```

---

## ⚙️ Setup Instructions

### 1. Database
```sql
-- Open phpMyAdmin or MySQL CLI and run:
SOURCE /path/to/clinic_db.sql
```

### 2. Configure DB Connection
Edit `clinic_system/config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // your MySQL username
define('DB_PASS', '');           // your MySQL password
define('DB_NAME', 'dsrrm_clinic');
```

### 3. Images
Place your images in `clinic_system/img/`:
- `Logo.jpg` — Clinic logo
- `bestlink.jpg` — Hero background image

### 4. Update Front Pages
In your existing `index.php`, `frontpage.php`, etc., update the login button:
```html
<a href="clinic_system/login.php" class="login-btn">Log In</a>
```
The `Get Started` button:
```html
<a href="clinic_system/login.php" class="btn-get-started">Get Started</a>
```

---

## 🔐 Default Login
| Email | Password | Role |
|-------|----------|------|
| admin@dsrrm.edu.ph | password | Admin |

> ⚠️ **Change the default password immediately after first login!**
> Go to Admin → User Management → Edit Admin → Update Password

---

## ✨ Features

### Admin Dashboard
- 📊 Overview with stats, low stock alerts, upcoming appointments
- 👤 **Patient Records** — Add, edit, view, delete patients
- 📋 **Medical History** — Record clinic visits with vitals & prescriptions
- 💊 **Medicine Stock** — Full CRUD, low stock & expiry alerts
- 📅 **Appointments** — Schedule & manage appointments
- 💬 **Consultation Requests** — Approve/reject → auto-creates appointment
- 🤲 **Medicine Requests** — Approve & auto-dispense medicine
- 📈 **Reports** — Charts (visits, appointments, diagnoses, medicines)
- 👥 **User Management** — Create students & admin accounts

### Student Portal
- 🏠 **Dashboard** — Personal overview with quick actions
- 💬 **Online Consultation** — Request clinic visit online
- 📋 **Medical Record** — View all past visits & prescriptions
- 💊 **Medicine Request** — Request available medicines
- 📅 **Appointments** — View upcoming & past appointments
- 🔔 **Notifications** — Real-time clinic notifications

---

## 🛡️ Security Features
- Password hashing (bcrypt)
- Session-based authentication
- Role-based access control (admin/user)
- SQL injection prevention (prepared queries / escaping)
- HTTPOnly session cookies
- Session regeneration on login

---

## 📦 Requirements
- PHP 8.0+
- MySQL 5.7+ / MariaDB 10.3+
- Apache/Nginx (XAMPP, WAMP, Laragon work fine)
- Web browser (Chrome, Firefox, Edge)

## Task 3 — User Authentication & Management System

🔗 **Live Demo:** [jagadeesh-task3.infinityfree.io](https://jagadeesh-task3.infinityfree.io/)

A complete authentication and user management system built with PHP, MySQLi, and Vanilla JS.

### Features
- Register / Login / Logout with PHP sessions
- Password hashing with bcrypt
- Role-based access control (Admin / User)
- Admin can edit and delete users
- Self-delete prevention
- Profile page with live avatar upload
- 3-step password reset (email → verify → new password)
- Real-time email availability check on register
- Password strength meter with live rule validation
- Particle canvas background with amber dark theme

### Stack
- **Frontend:** HTML, CSS, Vanilla JS
- **Backend:** PHP (no framework)
- **Database:** MySQL via MySQLi
- **Server:** XAMPP (Apache + MySQL)

### Setup
1. Clone the repo into `C:\xampp\htdocs\task3\`
2. Import the DB — create `task3_db` in phpMyAdmin and run the SQL in `schema.sql`
3. Start Apache & MySQL in XAMPP
4. Open `http://localhost/task3/`
5. Register → set your `role_id` to `2` in phpMyAdmin to become admin

<p align="center">
  <img src="public_html/css/logo_blue.png" alt="UniHive Logo" width="120"/>
</p>

<h1 align="center">UniHive — University Learning Management System</h1>

<p align="center">
  A full-featured university platform for managing courses, meetings, quizzes, and the student library.
  <br/>
  Built with <strong>PHP</strong> · <strong>MySQL</strong> · <strong>Vanilla JS</strong>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.x-777BB4?style=for-the-badge&logo=php&logoColor=white"/>
  <img src="https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white"/>
  <img src="https://img.shields.io/badge/XAMPP-FB7A24?style=for-the-badge&logo=xampp&logoColor=white"/>
  <img src="https://img.shields.io/badge/Bootstrap-5-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white"/>
</p>

---

## ✨ Features

- 🔐 Role-based authentication (Admin · Professor · Student)
- 📚 Course management & enrollment
- 📹 Live & scheduled meetings (BigBlueButton integration)
- 📝 Online quiz system with auto-grading
- 📖 Library — upload & access books and past exams
- 🔔 Real-time notification system
- 👤 User profiles with image upload
- 🌐 Arabic / English bilingual UI

---

## 🚀 Getting Started

### Requirements

- [XAMPP](https://www.apachefriends.org/) (PHP 8.x + MySQL)
- Git

### Installation

```bash
# 1. Clone the repository
git clone https://github.com/YOUR_USERNAME/unihive.systems.git

# 2. Move to XAMPP's htdocs folder
# Place the project inside: C:/xampp/htdocs/

# 3. Import the database
#    Open phpMyAdmin → Create a new database named: project
#    Import the SQL file provided separately (project.sql)

# 4. Configure the database connection
#    Edit: public_html/config.php
#    Set your DB host, username, and password

# 5. Start Apache + MySQL from XAMPP Control Panel
#    Then visit: http://localhost/unihive.systems/public_html/
```

---

## 🔑 Demo Login Credentials

> Use these accounts to explore all features of the platform.

### 🛡️ Admin Account
| Field    | Value                        |
|----------|------------------------------|
| Email    | `admin@university.com`       |
| Password | `54321`                      |
| Access   | Full dashboard control, manage users, send notifications |

---

### 👨‍🏫 Professor Account
| Field    | Value                            |
|----------|----------------------------------|
| Email    | `john.smith@university.com`      |
| Password | `12345`                          |
| Name     | John Smith                       |
| Department | Technology & Information Systems |
| Access   | Create meetings, upload to library, manage quizzes, view course students |

---

### 🎓 Student Account
| Field      | Value                        |
|------------|------------------------------|
| Email      | `ahmed@unihive.systems`      |
| Password   | `12345`                      |
| Name       | Ahmed Mohsen                 |
| Level      | Year 4                       |
| Department | Technology & Information Systems |
| Access     | Join meetings, take quizzes, access library, view notifications |

---

## 🗂️ Project Structure

```
unihive.systems/
├── public_html/
│   ├── index.php           # Login page
│   ├── config.php          # DB configuration
│   ├── auth.php            # Authentication logic
│   ├── admin/              # Admin dashboard & controls
│   ├── home/               # Student/Professor home
│   ├── meetings/           # Meeting management (BBB)
│   ├── quiz-system/        # Quiz engine
│   ├── library/            # Books & exams library
│   ├── notifications/      # Notification system
│   ├── student/            # Student-specific pages
│   ├── professor/          # Professor-specific pages
│   └── uploads/            # User uploaded files
└── mail.unihive.systems/   # Mail configuration
```

---

## 🛠️ Tech Stack

| Layer      | Technology                     |
|------------|-------------------------------|
| Backend    | PHP 8.x (procedural + MVC mix) |
| Database   | MySQL via MySQLi               |
| Frontend   | HTML5, CSS3, Bootstrap 5, Vanilla JS |
| Meetings   | BigBlueButton API              |
| Server     | Apache (XAMPP)                 |

---

## 📌 Notes

- The database SQL file is **not included** in this repository for security reasons.
- Contact the repository owner to get the `project.sql` seed file for local setup.
- Upload directories (`/uploads`) are excluded from the repository.

---

## 📄 License

This project is for educational purposes. Feel free to use and modify it.

---

<p align="center">Made with ❤️ — UniHive Team</p>

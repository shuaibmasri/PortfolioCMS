# PortfolioCMS

> A modern, secure, and responsive Portfolio Content Management System (CMS) built with PHP and MySQL, designed to help professionals showcase their skills, experience, projects, education, and achievements through an elegant personal portfolio website with an integrated administration panel.

![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?style=for-the-badge&logo=php)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.x-7952B3?style=for-the-badge&logo=bootstrap)
![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)

---

## 🌐 Live Demo

**Website:** https://portfoliocms.shmasri.org

---

# Overview

PortfolioCMS is a lightweight and customizable Content Management System developed for personal portfolio websites.

It provides a complete administration dashboard that allows users to manage portfolio content without editing source code.

The project focuses on:

- Clean architecture
- Security best practices
- Responsive UI
- Easy deployment
- Maintainable codebase

---

# Features

## Public Website

- Professional Home Page
- Hero Section
- About Me
- Skills
- Experience Timeline
- Education
- Certifications
- Projects Portfolio
- Contact Information
- Download CV
- Responsive Design
- SEO Friendly

---

## Admin Dashboard

- Secure Authentication
- Dashboard Overview
- Profile Management
- Skills Management
- Experience Management
- Education Management
- Certifications Management
- Projects Management
- File Upload Management
- Website Settings
- Media Management

---

# Security Features

- CSRF Protection
- Password Hashing
- Session Authentication
- Input Validation
- XSS Protection
- SQL Injection Prevention using PDO Prepared Statements
- Secure File Upload Validation

---

# Technology Stack

| Technology | Description |
|------------|-------------|
| PHP | Backend Development |
| MySQL | Database |
| Bootstrap 5 | Responsive UI |
| JavaScript | Client-side functionality |
| HTML5 | Markup |
| CSS3 | Styling |
| Font Awesome | Icons |

---

# Project Structure

```text
PortfolioCMS/
│
├── admin/
├── assets/
│   ├── css/
│   ├── js/
│   ├── images/
│   └── uploads/
│
├── config/
├── database/
├── includes/
├── templates/
├── vendor/
├── index.php
└── README.md
```

---

# Installation

## 1. Clone Repository

```bash
git clone https://github.com/shuaibmasri/PortfolioCMS.git
```

---

## 2. Navigate to Project

```bash
cd PortfolioCMS
```

---

## 3. Create Database

Create a new MySQL database.

Example:

```
PortfolioCMS
```

---

## 4. Import Database

Import

```
database/PortfolioCMS.sql
```

using phpMyAdmin or MySQL CLI.

---

## 5. Configure Database

Update

```
config/database.php
```

with your database credentials.

Example:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'PortfolioCMS');
define('DB_USER', 'root');
define('DB_PASS', 'password');
```

---

## 6. Configure Application

Update

```
config/constants.php
```

```php
define('APP_URL', 'https://your-domain.com');
```

---

## 7. Start Server

Apache + PHP

or

```bash
php -S localhost:8000
```

---

# Screenshots

> Replace these placeholders with screenshots.

### Home Page

![Home](docs/screenshots/home.png)

---

### Admin Dashboard

![Dashboard](docs/screenshots/dashboard.png)

---

### Projects

![Projects](docs/screenshots/projects.png)

---

### Skills

![Skills](docs/screenshots/skills.png)

---

# Future Improvements

- Multi-language support
- Dark Mode
- Blog Module
- Visitor Analytics
- Contact Form Email Notifications
- REST API
- Docker Support
- GitHub Actions CI/CD
- Unit Testing

---

# License

This project is licensed under the MIT License.

See the `LICENSE` file for details.

---

# Author

**Shuaib Al-Masri**

ERP Systems Developer | Development Officer

- 🌐 Website: https://portfoliocms.shmasri.org
- 💼 LinkedIn: *(www.linkedin.com/in/shuaib-al-masri-228386172)*
- 📧 Email: *(sh.almassri2013@gmail.com)*
- 🐙 GitHub: https://github.com/shuaibmasri

---

# Acknowledgements

This project was developed to demonstrate practical software engineering skills in PHP application development, secure authentication, responsive web design, and content management systems.

It serves as a professional portfolio solution and a showcase of clean architecture, maintainable code, and modern development practices.
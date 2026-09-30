# InternConnect

### Web-Based Internship Management Platform

InternConnect is a PHP/MySQL web application designed to connect students with internship opportunities while giving companies a structured way to publish internships and manage applications.

> **Portfolio project by May Thet Khine**  
> Higher Diploma in Infocomm Technology — British United College  
> COS 209 Final Project

![InternConnect](images/hero.png)

## Overview

Internship information can be scattered across different websites and social platforms. InternConnect provides one platform where students can discover and apply for internships, companies can manage internship opportunities and applicants, and administrators can monitor the platform.

## Key Features

### Student
- Register and log in
- Manage student profile
- Browse and search internships
- View internship and company details
- Apply for internships
- Upload a resume
- Submit a cover letter
- Track application status
- Save internships

### Company
- Register and log in
- Manage company profile
- Create internship opportunities
- Edit internship posts
- View student applications
- Review applicant information and resumes
- Accept or reject applicants

### Administrator
- Secure administrator login
- Dashboard and system monitoring
- Review internship posts
- Approve internships
- Change internship status
- View registered companies and students
- Review application information and statistics

## Technology Stack

| Area | Technology |
|---|---|
| Backend | PHP |
| Database | MySQL |
| Frontend | HTML, CSS, JavaScript |
| Local Server | XAMPP |
| Database Tool | MySQL Workbench |
| Editor | Visual Studio Code |

## Technical Highlights

- Role-based access control for Student, Company, and Admin
- Session-based authentication and authorization
- Password hashing with PHP `password_hash()` / `password_verify()`
- Prepared SQL statements for database operations
- Relational database design with foreign keys
- Internship approval workflow: pending → active → closed
- Application workflow: pending → accepted / rejected
- Resume upload validation for file type and size
- Separate dashboards and workflows for different user roles
- Responsive glassmorphism-style interface

## Database Structure

The main database entities are:

- `users`
- `students_profile`
- `companies`
- `internships`
- `applications`

The supplied project documentation describes the system scope, user roles, technology choices, database design, testing, and implementation. fileciteturn0file0L75-L94

## Local Installation

### 1. Requirements

Install:
- XAMPP
- PHP 8+
- MySQL / MariaDB
- A modern web browser

### 2. Clone or download the repository

Place the project inside your XAMPP `htdocs` directory:

```text
C:\xampp\htdocs\InternConnect
```

### 3. Create the database

Open phpMyAdmin or MySQL Workbench and import:

```text
database/schema.sql
```

The repository contains a **schema-only** database file. Real/test users, password hashes, applications, and uploaded resumes are intentionally not included.

### 4. Configure the database connection

For the easiest XAMPP setup, copy:

```text
config.example.php
```

to:

```text
config.php
```

Then update the values in `config.php` for your local MySQL installation.

`config.php` is ignored by Git, so your local database password will not be uploaded.

Alternatively, the application can read `DB_HOST`, `DB_USER`, `DB_PASS`, and `DB_NAME` from environment variables.

### 5. Start XAMPP

Start:
- Apache
- MySQL

Then open:

```text
http://localhost/InternConnect/
```

### 6. Create a local account

Use the registration page to create a Student or Company account.

Administrator accounts are intentionally **not publicly registered**. Create/manage an Admin account only in the local development database.

## Project Structure

```text
InternConnect/
├── admin-*.php / admin-*.css
├── company-*.php / company-*.css
├── student-*.php / student-*.css
├── index.php
├── internships.php
├── internship-details.php
├── companies.php
├── about.php
├── contact.php
├── login.php
├── register.php
├── db.php
├── style.css
├── images/
├── uploads/
│   ├── company_logos/
│   └── resumes/
└── database/
    └── schema.sql
```

## Security & Privacy

This public repository intentionally excludes:
- Database passwords
- `.env` files
- Real/test account records
- Password hashes from the development database
- Student resumes
- Private uploaded documents

Uploaded resumes are runtime data and should never be committed to a public portfolio repository.

## Portfolio Notes

This project demonstrates practical experience in:
- PHP web application development
- MySQL database management
- Authentication and authorization
- CRUD operations
- Role-based system design
- File upload handling
- Database relationships and foreign keys
- UI development with HTML/CSS/JavaScript
- Requirements analysis, system design, testing, and documentation

## Author

**May Thet Khine**

Higher Diploma in Infocomm Technology  
British United College

For recruitment or portfolio review, please refer to the repository source code and project documentation.

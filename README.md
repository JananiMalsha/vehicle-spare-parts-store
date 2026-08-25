# ⚙️ GearParts - Online Vehicle Spare Parts Store

A full-stack, secure e-commerce web application for automotive vehicle spare parts built completely from scratch with **HTML5, CSS3, Vanilla JavaScript, PHP 8.x, and MySQL (PDO)** without any third-party frameworks.

---

## 🚀 Key Features

### 🛒 Customer Features
- **Vehicle Parts Catalog**: Filter spare parts by Vehicle System (Brakes, Engine, Suspension, Electrical, Fluids), Vehicle Make (Toyota, Honda, Ford, Nissan, Universal), and Sort by price or newest.
- **Dynamic Search**: Live search by part name, SKU, or vehicle make/model.
- **Vehicle Compatibility Engine**: Clear fitment tags and compatibility callouts (Make, Model, Year Range) on product pages.
- **Session-based Shopping Cart**: Add parts, adjust quantities with real-time inventory limit validation, and dynamic shipping calculations.
- **Transactional Checkout**: Place orders with delivery details and payment methods (Cash on Delivery, Mock Card, Wire Transfer).
- **Customer Order Tracking**: View past orders and track shipment statuses.
- **Authentication**: Customer registration and login with secure **BCrypt password hashing**.

### 🛠️ Administrator Panel
- **Dashboard Metrics**: Live statistics for total products, categories, orders, and total sales revenue.
- **Inventory Stock Alert**: Automatic warnings for low-stock parts ($\le 10$ units remaining).
- **Category Management**: Full CRUD for vehicle systems with real-time part counts.
- **Spare Parts Management**: Add, edit, and delete parts with **native PHP photo upload handling**.
- **Order Management & Workflow**: Update customer order fulfillment statuses (*Pending* ➔ *Processing* ➔ *Shipped* ➔ *Delivered* ➔ *Cancelled*).

---

## 🔒 Security Highlights

- **PDO Prepared Statements**: 100% immune to SQL Injection attacks through parameter binding.
- **Password Security**: Passwords hashed with `password_hash($pass, PASSWORD_BCRYPT)` and verified with `password_verify()`.
- **Session Fixation Defense**: Session regeneration (`session_regenerate_id(true)`) upon authentication.
- **XSS Prevention**: Clean HTML entity escaping on all user outputs.
- **ACID Database Transactions**: Order creation and inventory deduction are atomic (`beginTransaction` / `commit` / `rollBack`).

---

## 💻 Tech Stack

- **Frontend**: HTML5, Responsive CSS3 (Flexbox & CSS Grid), Vanilla JavaScript (ES6)
- **Backend**: Pure PHP 8.x
- **Database**: MySQL / MariaDB (via PDO)
- **Architecture**: Modular Template Architecture (`includes/`, `admin/includes/`)

---

## ⚙️ Installation & Setup Guide

### 1. Prerequisites
- [XAMPP](https://www.apachefriends.org/), WampServer, or Laragon with **PHP 8.x** and **MySQL** installed.

### 2. Setup the Project
1. Clone this repository or copy it into your web server root:
   ```bash
   git clone https://github.com/YOUR_USERNAME/vehicle-spare-parts-store.git
   ```
2. Place the folder into `C:/xampp/htdocs/vehical-spare-parts`.

### 3. Database Initialization (2 Easy Ways)

#### Option A (1-Click Automated Setup - Recommended):
1. Start **Apache** and **MySQL** in your XAMPP Control Panel.
2. Open your browser and navigate to:
   ```text
   http://localhost/vehical-spare-parts/setup.php
   ```
3. The script will automatically create the database, tables, seed categories, spare parts, and create admin/customer accounts.

#### Option B (Manual Import via phpMyAdmin):
1. Open `http://localhost/phpmyadmin`.
2. Import the file located at: `database/schema.sql`.

---

## 🔑 Demo Test Accounts

| Role | Email | Password |
|---|---|---|
| **Administrator** | `admin@gearparts.com` | `password123` |
| **Customer** | `john@example.com` | `password123` |

---

## 📜 License
This project is open-source and available under the [MIT License](LICENSE).

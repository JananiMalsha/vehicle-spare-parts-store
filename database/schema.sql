-- ==========================================================
-- Online Vehicle Spare Parts Store - Database Schema
-- Compatible with MySQL 5.7+ / MySQL 8.x / MariaDB
-- ==========================================================

-- Create database if it does not exist
CREATE DATABASE IF NOT EXISTS `vehicle_parts_db` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `vehicle_parts_db`;

-- ----------------------------------------------------------
-- 1. Table: users
-- Stores both Customers and Administrators
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `address` TEXT DEFAULT NULL,
    `role` ENUM('customer', 'admin') DEFAULT 'customer',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------
-- 2. Table: categories
-- Product categories (e.g., Brake System, Engine Parts, Electrical)
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `image` VARCHAR(255) DEFAULT 'default_category.jpg',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------
-- 3. Table: products
-- Spare parts catalog with vehicle compatibility details
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `products` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT NOT NULL,
    `name` VARCHAR(200) NOT NULL,
    `part_number` VARCHAR(100) NOT NULL UNIQUE,
    `vehicle_make` VARCHAR(100) NOT NULL,    -- e.g. Toyota, Honda, Ford, Nissan, Universal
    `vehicle_model` VARCHAR(100) NOT NULL,   -- e.g. Corolla, Civic, F-150, All Models
    `year_compatibility` VARCHAR(50) NOT NULL, -- e.g. 2015-2022, All Years
    `price` DECIMAL(10, 2) NOT NULL,
    `stock_quantity` INT NOT NULL DEFAULT 0,
    `description` TEXT DEFAULT NULL,
    `image` VARCHAR(255) DEFAULT 'default_part.jpg',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------
-- 4. Table: orders
-- Customer order headers with shipping details and status
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `total_amount` DECIMAL(10, 2) NOT NULL,
    `shipping_name` VARCHAR(100) NOT NULL,
    `shipping_phone` VARCHAR(20) NOT NULL,
    `shipping_address` TEXT NOT NULL,
    `payment_method` VARCHAR(50) DEFAULT 'Cash on Delivery',
    `status` ENUM('Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled') DEFAULT 'Pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------
-- 5. Table: order_items
-- Individual spare parts ordered within each order
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `order_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `product_name` VARCHAR(200) NOT NULL,
    `price` DECIMAL(10, 2) NOT NULL,
    `quantity` INT NOT NULL,
    `subtotal` DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================================
-- Sample Data Insertion
-- ==========================================================

-- Default Users (Passwords are hashed for 'password123')
-- Hash created with password_hash('password123', PASSWORD_BCRYPT)
INSERT INTO `users` (`name`, `email`, `password`, `phone`, `address`, `role`) VALUES
('Store Admin', 'admin@gearparts.com', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', '1234567890', '123 Auto Care Blvd, Tech City', 'admin'),
('John Doe', 'john@example.com', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', '9876543210', '45 Green Park Road, Springfield', 'customer');

-- Categories
INSERT INTO `categories` (`id`, `name`, `description`, `image`) VALUES
(1, 'Braking System', 'High performance brake pads, rotors, calipers, and fluid.', 'cat_brakes.jpg'),
(2, 'Engine & Drivetrain', 'Spark plugs, timing belts, filters, pistons, and gaskets.', 'cat_engine.jpg'),
(3, 'Suspension & Steering', 'Shock absorbers, struts, control arms, and tie rods.', 'cat_suspension.jpg'),
(4, 'Electrical & Lighting', 'Alternators, batteries, starter motors, and LED headlamps.', 'cat_electrical.jpg'),
(5, 'Filters & Fluids', 'Engine oil, air filters, oil filters, and coolant.', 'cat_fluids.jpg');

-- Products / Spare Parts
INSERT INTO `products` (`category_id`, `name`, `part_number`, `vehicle_make`, `vehicle_model`, `year_compatibility`, `price`, `stock_quantity`, `description`, `image`) VALUES
(1, 'Brembo Ceramic Front Brake Pads', 'BRK-BRM-001', 'Toyota', 'Corolla', '2014-2022', 45.99, 25, 'Premium ceramic brake pads offering quiet braking and reduced dust for daily driving.', 'part_brake_pads.jpg'),
(1, 'Bosch QuietCast Premium Rear Brake Rotor', 'BRK-BOS-002', 'Honda', 'Civic', '2016-2023', 68.50, 18, 'Precision balanced rotor preventing pedal vibration and ensuring smooth stopping power.', 'part_brake_rotor.jpg'),
(2, 'NGK Iridium IX Spark Plug (Set of 4)', 'ENG-NGK-003', 'Toyota', 'Camry', '2012-2021', 34.00, 40, 'High-grade iridium spark plugs for optimal fuel efficiency and strong ignition.', 'part_spark_plug.jpg'),
(2, 'Gates Timing Belt Kit with Water Pump', 'ENG-GAT-004', 'Honda', 'Accord', '2008-2017', 129.99, 10, 'Complete replacement kit for timing belt and water pump assembly.', 'part_timing_belt.jpg'),
(3, 'Monroe Quick-Strut Complete Assembly', 'SUS-MON-005', 'Ford', 'F-150', '2015-2020', 115.00, 12, 'Pre-assembled strut unit for quick installation and restored ride height.', 'part_strut.jpg'),
(3, 'Moog Front Lower Control Arm', 'SUS-MOO-006', 'Nissan', 'Altima', '2013-2018', 74.50, 15, 'Engineered to withstand road punishment and deliver precise steering response.', 'part_control_arm.jpg'),
(4, 'Denso 100A High Output Alternator', 'ELE-DEN-007', 'Toyota', 'RAV4', '2013-2019', 185.00, 8, 'Original equipment quality alternator providing steady charging voltage.', 'part_alternator.jpg'),
(4, 'Philips Ultinon Pro H4 LED Headlight Bulbs (Pair)', 'ELE-PHI-008', 'Universal', 'All Models', 'All Years', 59.99, 30, 'Bright white 6000K LED headlight conversion bulbs with superior beam pattern.', 'part_headlight.jpg'),
(5, 'Mobil 1 Advanced Full Synthetic 5W-30 (5 Qts)', 'FLU-MOB-009', 'Universal', 'All Models', 'All Years', 29.99, 50, 'Keeps engines running like new and protects for up to 10,000 miles between oil changes.', 'part_engine_oil.jpg'),
(5, 'K&N High-Flow Washable Air Filter', 'FLU-KNN-010', 'Toyota', 'Corolla', '2014-2022', 49.99, 22, 'Reusable cotton air filter designed to increase horsepower and acceleration.', 'part_air_filter.jpg');

<?php
/**
 * Quick Database Initializer & Admin Account Setup
 * 
 * Run this file in your browser once (e.g. http://localhost/vehical-spare-parts/setup.php)
 * to automatically create the database, tables, and fresh accounts.
 */

// Database configuration
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'vehicle_parts_db';

try {
    // 1. Connect to MySQL server without database first
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // 2. Create Database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbname`");

    // 3. Create Tables
    $pdo->exec("
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
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `categories` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL,
            `description` TEXT DEFAULT NULL,
            `image` VARCHAR(255) DEFAULT 'default_category.jpg',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `products` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `category_id` INT NOT NULL,
            `name` VARCHAR(200) NOT NULL,
            `part_number` VARCHAR(100) NOT NULL UNIQUE,
            `vehicle_make` VARCHAR(100) NOT NULL,
            `vehicle_model` VARCHAR(100) NOT NULL,
            `year_compatibility` VARCHAR(50) NOT NULL,
            `price` DECIMAL(10, 2) NOT NULL,
            `stock_quantity` INT NOT NULL DEFAULT 0,
            `description` TEXT DEFAULT NULL,
            `image` VARCHAR(255) DEFAULT 'default_part.jpg',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $pdo->exec("
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
    ");

    $pdo->exec("
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
    ");

    // 4. Seed Fresh Admin and Customer accounts with live password_hash
    $password = 'password123';
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    $userStmt = $pdo->prepare("
        INSERT INTO `users` (`name`, `email`, `password`, `phone`, `address`, `role`) 
        VALUES (:name, :email, :password, :phone, :address, :role)
        ON DUPLICATE KEY UPDATE `password` = :up_password, `role` = :up_role
    ");

    // Admin user
    $userStmt->execute([
        'name'        => 'Store Admin',
        'email'       => 'admin@gearparts.com',
        'password'    => $hashedPassword,
        'phone'       => '1234567890',
        'address'     => '123 Auto Care Blvd, Tech City',
        'role'        => 'admin',
        'up_password' => $hashedPassword,
        'up_role'     => 'admin'
    ]);

    // Customer user
    $userStmt->execute([
        'name'        => 'John Doe',
        'email'       => 'john@example.com',
        'password'    => $hashedPassword,
        'phone'       => '9876543210',
        'address'     => '45 Green Park Road, Springfield',
        'role'        => 'customer',
        'up_password' => $hashedPassword,
        'up_role'     => 'customer'
    ]);

    // 5. Seed categories if table is empty
    $catCount = $pdo->query("SELECT COUNT(*) FROM `categories`")->fetchColumn();
    if ($catCount == 0) {
        $pdo->exec("
            INSERT INTO `categories` (`id`, `name`, `description`, `image`) VALUES
            (1, 'Braking System', 'High performance brake pads, rotors, calipers, and fluid.', 'cat_brakes.jpg'),
            (2, 'Engine & Drivetrain', 'Spark plugs, timing belts, filters, pistons, and gaskets.', 'cat_engine.jpg'),
            (3, 'Suspension & Steering', 'Shock absorbers, struts, control arms, and tie rods.', 'cat_suspension.jpg'),
            (4, 'Electrical & Lighting', 'Alternators, batteries, starter motors, and LED headlamps.', 'cat_electrical.jpg'),
            (5, 'Filters & Fluids', 'Engine oil, air filters, oil filters, and coolant.', 'cat_fluids.jpg');
        ");
    }

    // 6. Seed products if table is empty
    $prodCount = $pdo->query("SELECT COUNT(*) FROM `products`")->fetchColumn();
    if ($prodCount == 0) {
        $pdo->exec("
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
        ");
    }

    $setupSuccess = true;

} catch (PDOException $e) {
    $setupSuccess = false;
    $errorMessage = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - GearParts</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8fafc; color: #1e293b; padding: 40px 20px; }
        .card { max-width: 550px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); border: 1px solid #e2e8f0; text-align: center; }
        .btn { display: inline-block; background: #0284c7; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; margin-top: 20px; }
        .btn:hover { background: #0369a1; }
        .success { color: #059669; }
        .error { color: #dc2626; }
        .code-box { background: #f1f5f9; padding: 12px; border-radius: 6px; text-align: left; margin: 15px 0; font-family: monospace; font-size: 0.9rem; }
    </style>
</head>
<body>
<div class="card">
    <?php if ($setupSuccess): ?>
        <h2 class="success">🎉 Database Setup Completed!</h2>
        <p>All database tables, categories, spare parts, and test accounts have been configured and verified.</p>
        
        <div class="code-box">
            <strong>✅ Admin Account:</strong><br>
            • Email: <code>admin@gearparts.com</code><br>
            • Password: <code>password123</code><br><br>
            <strong>✅ Customer Account:</strong><br>
            • Email: <code>john@example.com</code><br>
            • Password: <code>password123</code>
        </div>

        <a href="login.php" class="btn">🚀 Proceed to Login Page</a>
    <?php else: ?>
        <h2 class="error">❌ Setup Encountered an Error</h2>
        <p><?= htmlspecialchars($errorMessage); ?></p>
        <p style="font-size: 0.9rem; color: #64748b;">Please make sure MySQL is started in your XAMPP Control Panel.</p>
    <?php endif; ?>
</div>
</body>
</html>

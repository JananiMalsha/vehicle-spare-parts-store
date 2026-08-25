<?php
/**
 * Database Connection & Global Configuration
 * 
 * Uses PHP Data Objects (PDO) for secure, prepared SQL interactions.
 */

// Start session if not already active across all pages
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'vehicle_parts_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Site Base URL helper (adjust if installed in a different folder)
define('SITE_URL', 'http://localhost/vehical-spare-parts');

// Establish PDO Connection
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    
    $options = [
        // Throw PDOExceptions on errors instead of silent warnings
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        // Fetch rows as associative arrays by default (e.g. $row['name'])
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // Disable emulated prepares to ensure real database-level prepared statements
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

} catch (PDOException $e) {
    // If connection fails, display a user-friendly error with setup hints
    die("
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 40px auto; padding: 20px; border: 1px solid #ff4d4f; background: #fff1f0; border-radius: 8px;'>
            <h3 style='color: #cf1322; margin-top: 0;'>⚠️ Database Connection Failed</h3>
            <p><strong>Error Details:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
            <hr style='border: none; border-top: 1px solid #ffa39e;' />
            <h4>Troubleshooting Checklist:</h4>
            <ol style='line-height: 1.6;'>
                <li>Make sure your MySQL server is running (in XAMPP, WampServer, or Laragon).</li>
                <li>Ensure the database <code>vehicle_parts_db</code> exists. Import <code>database/schema.sql</code> via phpMyAdmin.</li>
                <li>Check your database username and password in <code>config/db.php</code> (Default in XAMPP is user: <code>root</code>, password: empty).</li>
            </ol>
        </div>
    ");
}

/**
 * Global Helper Functions
 */

// Escape HTML output to prevent Cross-Site Scripting (XSS)
function sanitize($data) {
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

// Format numbers into currency
function formatPrice($amount) {
    return '$' . number_format((float)$amount, 2);
}
?>

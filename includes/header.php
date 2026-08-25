<?php
/**
 * Header Template
 * 
 * Included at the top of every customer-facing page.
 */

// Include database connection if not already loaded
require_once __DIR__ . '/../config/db.php';

// Calculate total cart items count from session
$cart_total_items = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_total_items += $item['quantity'];
    }
}

$site_title = isset($page_title) ? $page_title . " - GearParts Auto Store" : "GearParts - Quality Online Vehicle Spare Parts";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($site_title); ?></title>
    <!-- Main Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php
/**
 * Admin Panel Header Template
 * 
 * Enforces admin authorization and renders sidebar layout.
 */

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';

// Protect all admin pages - only users with role === 'admin' can proceed
requireAdmin();

$current_page = basename($_SERVER['PHP_SELF']);
$admin_title = isset($page_title) ? $page_title . " - GearParts Admin" : "Admin Panel - GearParts";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($admin_title); ?></title>
    <!-- Admin CSS -->
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<div class="admin-layout">
    <!-- Left Navigation Sidebar -->
    <aside class="admin-sidebar">
        <div class="admin-brand">
            ⚙️ <span>Gear</span>Parts <span style="font-size: 0.75rem; background: #0284c7; color: white; padding: 2px 6px; border-radius: 4px;">ADMIN</span>
        </div>

        <ul class="admin-nav">
            <li>
                <a href="index.php" class="<?= $current_page === 'index.php' ? 'active' : ''; ?>">
                    📊 Dashboard
                </a>
            </li>
            <li>
                <a href="categories.php" class="<?= $current_page === 'categories.php' ? 'active' : ''; ?>">
                    📁 Categories
                </a>
            </li>
            <li>
                <a href="products.php" class="<?= in_array($current_page, ['products.php', 'product-add.php', 'product-edit.php']) ? 'active' : ''; ?>">
                    🔧 Spare Parts
                </a>
            </li>
            <li>
                <a href="orders.php" class="<?= $current_page === 'orders.php' ? 'active' : ''; ?>">
                    📦 Orders
                </a>
            </li>
        </ul>

        <div class="admin-sidebar-footer">
            <a href="../index.php" target="_blank" class="btn btn-outline btn-sm" style="color: #cbd5e1; width: 100%; margin-bottom: 8px;">
                🌐 View Main Store
            </a>
            <a href="../logout.php" class="btn btn-danger btn-sm" style="width: 100%;">
                🚪 Sign Out
            </a>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="admin-main">
        <!-- Topbar -->
        <header class="admin-topbar">
            <div>
                <strong>⚙️ Auto Spare Parts Management System</strong>
            </div>
            <div class="admin-user-info">
                <span>Logged in as: <strong><?= sanitize($_SESSION['user_name']); ?></strong></span>
                <span class="badge badge-processing">Admin</span>
            </div>
        </header>

        <!-- Admin Content Container -->
        <main class="admin-content">

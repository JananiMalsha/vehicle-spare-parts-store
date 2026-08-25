<?php
/**
 * User & Administrator Login Page
 */

$page_title = "Sign In";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
require_once __DIR__ . '/includes/auth.php';

// If already logged in, redirect to appropriate destination
if (isLoggedIn()) {
    if (isAdmin()) {
        header("Location: admin/index.php");
    } else {
        header("Location: index.php");
    }
    exit;
}

$error = '';
$email = '';

// Check for flash messages
$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError   = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        try {
            // Find user by email using PDO Prepared Statement
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            // Verify password using native password_verify
            if ($user && password_verify($password, $user['password'])) {
                // Prevent Session Fixation attacks
                session_regenerate_id(true);

                // Set session data
                $_SESSION['user_id']    = $user['id'];
                $_SESSION['user_name']  = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role']  = $user['role']; // 'customer' or 'admin'

                // Redirect based on role
                if ($user['role'] === 'admin') {
                    header("Location: admin/index.php");
                } else {
                    header("Location: index.php");
                }
                exit;
            } else {
                $error = "Invalid email address or password.";
            }

        } catch (PDOException $e) {
            $error = "An error occurred. Please try again: " . $e->getMessage();
        }
    }
}
?>

<div class="container section" style="max-width: 500px;">
    <div style="background: white; padding: 35px 30px; border-radius: var(--radius); border: 1px solid var(--border-color); box-shadow: var(--shadow);">
        
        <div style="text-align: center; margin-bottom: 25px;">
            <h2 style="font-size: 1.8rem; margin-bottom: 8px;">Welcome Back</h2>
            <p style="color: var(--text-muted); font-size: 0.95rem;">Sign in to your GearParts customer or admin account</p>
        </div>

        <?php if ($flashSuccess): ?>
            <div class="alert alert-success alert-auto-dismiss">
                <?= sanitize($flashSuccess); ?>
            </div>
        <?php endif; ?>

        <?php if ($flashError): ?>
            <div class="alert alert-warning">
                <?= sanitize($flashError); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <?= sanitize($error); ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="e.g. john@example.com" value="<?= sanitize($email); ?>" required autofocus>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block" style="padding: 12px; margin-top: 10px;">
                Sign In
            </button>
        </form>

        <!-- Demo credentials box for easy testing -->
        <div style="margin-top: 25px; padding: 15px; background-color: #f8fafc; border-radius: 6px; font-size: 0.85rem; border: 1px dashed var(--border-color);">
            <strong>🔑 Demo Test Credentials:</strong><br>
            • <strong>Admin:</strong> <code>admin@gearparts.com</code> / <code>password123</code><br>
            • <strong>Customer:</strong> <code>john@example.com</code> / <code>password123</code>
        </div>

        <div style="text-align: center; margin-top: 20px; font-size: 0.9rem; color: var(--text-muted);">
            Don't have an account yet? <a href="register.php" style="font-weight: 600;">Register here</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

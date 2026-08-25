<?php
/**
 * Customer Registration Page
 */

$page_title = "Create an Account";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
require_once __DIR__ . '/includes/auth.php';

// If already logged in, redirect to home
if (isLoggedIn()) {
    header("Location: index.php");
    exit;
}

$errors = [];
$name = '';
$email = '';
$phone = '';
$address = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and trim form data
    $name             = trim($_POST['name'] ?? '');
    $email            = trim($_POST['email'] ?? '');
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $phone            = trim($_POST['phone'] ?? '');
    $address          = trim($_POST['address'] ?? '');

    // Validation checks
    if (empty($name)) {
        $errors[] = "Please enter your full name.";
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please provide a valid email address.";
    }

    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long.";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    // Check if email is already registered
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
            $stmt->execute(['email' => $email]);
            if ($stmt->fetch()) {
                $errors[] = "An account with this email address already exists. Please log in.";
            }
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }

    // Insert user if validation passes
    if (empty($errors)) {
        try {
            // Hash password using BCRYPT
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            $insertStmt = $pdo->prepare("
                INSERT INTO users (name, email, password, phone, address, role) 
                VALUES (:name, :email, :password, :phone, :address, 'customer')
            ");

            $insertStmt->execute([
                'name'     => $name,
                'email'    => $email,
                'password' => $hashedPassword,
                'phone'    => $phone,
                'address'  => $address
            ]);

            $_SESSION['flash_success'] = "Account created successfully! You can now log in.";
            header("Location: login.php");
            exit;

        } catch (PDOException $e) {
            $errors[] = "Registration failed: " . $e->getMessage();
        }
    }
}
?>

<div class="container section" style="max-width: 550px;">
    <div style="background: white; padding: 35px 30px; border-radius: var(--radius); border: 1px solid var(--border-color); box-shadow: var(--shadow);">
        
        <div style="text-align: center; margin-bottom: 25px;">
            <h2 style="font-size: 1.8rem; margin-bottom: 8px;">Create Customer Account</h2>
            <p style="color: var(--text-muted); font-size: 0.95rem;">Join GearParts to order parts, save delivery details, and track orders</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul style="margin-left: 20px; line-height: 1.5;">
                    <?php foreach ($errors as $error): ?>
                        <li><?= sanitize($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST">
            <div class="form-group">
                <label class="form-label" for="name">Full Name *</label>
                <input type="text" id="name" name="name" class="form-control" placeholder="e.g. John Doe" value="<?= sanitize($name); ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="email">Email Address *</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="e.g. john@example.com" value="<?= sanitize($email); ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" class="form-control" placeholder="e.g. +1 555-0199" value="<?= sanitize($phone); ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="address">Delivery Address</label>
                <textarea id="address" name="address" class="form-control" rows="2" placeholder="Street address, City, Postal Code"><?= sanitize($address); ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password (Minimum 6 characters) *</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="confirm_password">Confirm Password *</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block" style="padding: 12px; margin-top: 10px;">
                Create Account
            </button>
        </form>

        <div style="text-align: center; margin-top: 20px; font-size: 0.9rem; color: var(--text-muted);">
            Already have an account? <a href="login.php" style="font-weight: 600;">Sign in here</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

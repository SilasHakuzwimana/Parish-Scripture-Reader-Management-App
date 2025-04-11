<?php

date_default_timezone_set('Africa/Kigali');


require_once  '../vendor/autoload.php';
require_once  '../backend/db.php';
require_once  '../includes/functions.php';

// Initialize variables
$token = $_GET['token'] ?? '';
$error = '';
$success = '';
$show_form = true;

// Validate token and process reset
try {
    if (empty($token)) {
        throw new Exception("Invalid password reset link");
    }

    // Check token validity
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE reset_token = ? AND reset_expires > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        throw new Exception("Invalid or expired password reset link. Please request a new one.");
    }

    // Process form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validate password
        if (empty($password) || strlen($password) < 8) {
            throw new Exception("Password must be at least 8 characters long");
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            throw new Exception("Password must contain at least one uppercase letter");
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            throw new Exception("Password must contain at least one number");
        }
        
        if ($password !== $confirm_password) {
            throw new Exception("Passwords do not match");
        }
        
        // Update password
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, reset_token = NULL, reset_expires = NULL WHERE user_id = ?");
        $stmt->execute([$passwordHash, $user['user_id']]);
        
        // Log the password change
        log_action($user['user_id'], 'password_reset', 'Password reset via email link');
        
        $success = "Your password has been updated successfully!";
        $show_form = false;
    }

} catch (Exception $e) {
    $error = $e->getMessage();
    
    // If token is invalid, don't show the form
    if (strpos($error, 'Invalid or expired') !== false) {
        $show_form = false;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | Parish Scripture Reader</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --parish-primary: #2c3e50;
            --parish-secondary: #3498db;
        }
        body { 
            background-color: #f8f9fa; 
            height: 100vh;
            display: flex;
            align-items: center;
            background-image: url('assets/images/parish-bg.jpg');
            background-size: cover;
            background-position: center;
        }
        .password-reset-card {
            max-width: 500px;
            margin: 0 auto;
            padding: 2rem;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        .parish-logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        .parish-logo img {
            height: 80px;
            margin-bottom: 1rem;
        }
        .password-strength {
            height: 5px;
            margin-top: 5px;
            background: #eee;
            border-radius: 3px;
            overflow: hidden;
        }
        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: width 0.3s, background-color 0.3s;
        }
        .btn-parish {
            background-color: var(--parish-primary);
            border-color: var(--parish-primary);
        }
        .btn-parish:hover {
            background-color: var(--parish-secondary);
            border-color: var(--parish-secondary);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="password-reset-card">
            <div class="parish-logo">
                <img src="../assets/images/parish-logo.png" alt="Parish Logo">
                <h2>Reset Your Password</h2>
                <p class="text-muted">Create a new secure password</p>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?= htmlspecialchars($success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <div class="d-grid gap-2 mt-4">
                    <a href="login.php" class="btn btn-parish">Return to Login</a>
                </div>
            <?php elseif ($show_form): ?>
                <form method="POST" id="resetForm">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="password" name="password" required 
                               minlength="8" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}">
                        <div class="password-strength">
                            <div class="password-strength-bar" id="password-strength-bar"></div>
                        </div>
                        <div class="form-text">
                            Must contain at least 8 characters, one uppercase letter, and one number
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-parish btn-lg">Update Password</button>
                    </div>
                </form>
            <?php else: ?>
                <div class="d-grid gap-2">
                    <a href="forgot-password.php" class="btn btn-parish">Request New Reset Link</a>
                    <a href="login.php" class="btn btn-outline-secondary">Back to Login</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password strength meter
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('password-strength-bar');
            let strength = 0;
            
            // Length check
            if (password.length >= 8) strength += 30;
            if (password.length >= 12) strength += 10;
            
            // Complexity checks
            if (password.match(/[A-Z]/)) strength += 20;
            if (password.match(/[0-9]/)) strength += 20;
            if (password.match(/[^A-Za-z0-9]/)) strength += 20;
            
            strength = Math.min(strength, 100);
            strengthBar.style.width = strength + '%';
            
            // Color coding
            if (strength < 40) {
                strengthBar.style.backgroundColor = '#dc3545'; // Red
            } else if (strength < 70) {
                strengthBar.style.backgroundColor = '#ffc107'; // Yellow
            } else {
                strengthBar.style.backgroundColor = '#28a745'; // Green
            }
        });

        // Form validation
        document.getElementById('resetForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirm = document.getElementById('confirm_password').value;
            
            if (password !== confirm) {
                e.preventDefault();
                alert('Passwords do not match!');
                document.getElementById('confirm_password').focus();
            }
        });
    </script>
</body>
</html>
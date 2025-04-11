<?php
date_default_timezone_set("Africa/Kigali");

require_once  '../vendor/autoload.php';
require_once '../backend/db.php';
//require_once __DIR__ . '/includes/functions.php';

// Initialize PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    
    try {
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Please enter a valid email address");
        }

        // Check if user exists
        $stmt = $pdo->prepare("SELECT user_id, name FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            throw new Exception("No account found with that email address");
        }

        // Generate reset token (valid for 1 hour)
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Store token in database
        $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE user_id = ?");
        $stmt->execute([$token, $expires, $user['user_id']]);

        // Send reset email
        $resetLink = "https://" . $_SERVER['HTTP_HOST'] . "/reset-password.php?token=$token";
        
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.yourparish.org';
        $mail->SMTPAuth = true;
        $mail->Username = 'noreply@yourparish.org';
        $mail->Password = 'yourSMTPpassword';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        $mail->setFrom('info.@yourparish.org', 'Parish Scripture Reader System');
        $mail->addAddress($email, $user['name']);
        
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Request';
        $mail->Body = "
            <h2>Password Reset</h2>
            <p>Hello {$user['name']},</p>
            <p>We received a request to reset your password. Click the link below to proceed:</p>
            <p><a href='$resetLink'>Reset Password</a></p>
            <p><small>This link will expire in 1 hour. If you didn't request this, please ignore this email.</small></p>
            <hr>
            <p>Blessings,<br>Your Parish Team</p>
        ";
        
        $mail->send();
        $success = "Password reset link has been sent to your email";
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Parish Scripture Reader</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; height: 100vh; display: flex; align-items: center; }
        .password-container { max-width: 450px; margin: 0 auto; padding: 30px; background: white; border-radius: 8px; box-shadow: 0 0 15px rgba(0,0,0,0.1); }
        .parish-logo { text-align: center; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="password-container">
            <div class="parish-logo">
                <h3>Parish Scripture Reader System</h3>
                <p class="text-muted">Password Recovery</p>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <div class="text-center mt-3">
                    <a href="login.php" class="btn btn-primary">Return to Login</a>
                </div>
            <?php else: ?>
                <form method="POST">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                        <div class="form-text">Enter the email associated with your account</div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Reset Password</button>
                </form>
                <div class="mt-3 text-center">
                    <a href="login.php">Remember your password? Login</a>
                </div>
            <?php endif; ?>
        </div>
        <footer class="footer text-center">
            <p>&copy; 2025 - <?php echo date('Y') ?> S<sup>t</sup>Basil. All right resevered!</p>
        </footer>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
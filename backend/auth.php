<?php
session_start();

// Database connection
require_once 'db.php';

class Auth {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function login($email, $password) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['last_activity'] = time();
            
            // Log login
            $this->logAction($user['user_id'], 'Login', 'User logged in');
            
            return true;
        }
        return false;
    }
    
    public function logout() {
        // Log logout
        if (isset($_SESSION['user_id'])) {
            $this->logAction($_SESSION['user_id'], 'Logout', 'User logged out');
        }
        
        session_unset();
        session_destroy();
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function requireAuth() {
        if (!$this->isLoggedIn()) {
            header("Location: login.php");
            exit();
        }
        
        // Check session timeout (30 minutes)
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
            $this->logout();
            header("Location: login.php?timeout=1");
            exit();
        }
        $_SESSION['last_activity'] = time();
    }
    
    public function requireRole($role) {
        $this->requireAuth();
        
        if ($_SESSION['role'] !== $role) {
            header("HTTP/1.1 403 Forbidden");
            exit("Access denied");
        }
    }
    
    private function logAction($userId, $action, $details) {
        $ip = $_SERVER['REMOTE_ADDR'];
        $stmt = $this->db->prepare("INSERT INTO system_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $action, $details, $ip]);
    }
}

// Initialize auth
$auth = new Auth($db);
?>
<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'parish_reader_system');
define('DB_USER', 'root');
define('DB_PASS', '');

class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $this->connection = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_PERSISTENT => true
                ]
            );
            
            // Set timezone for database connection
            $this->connection->exec("SET time_zone = '+00:00'");
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection error. Please try again later.");
        }
    }
    
    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new Database();
        }
        return self::$instance->connection;
    }
    
    public static function testConnection() {
        try {
            $conn = self::getInstance();
            $conn->query("SELECT 1");
            return true;
        } catch (PDOException $e) {
            error_log("Database test failed: " . $e->getMessage());
            return false;
        }
    }
}

// Initialize database connection
try {
    $db = Database::getInstance();
    
    // Test connection on direct access (for debugging)
    if (isset($_GET['testdb'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => Database::testConnection() ? 'success' : 'error',
            'message' => Database::testConnection() ? 
                'Database connection successful' : 
                'Database connection failed',
            'database' => DB_NAME,
            'host' => DB_HOST
        ]);
        exit;
    };
}catch (Exception $e) {
    // Handle database connection errors gracefully
    if (php_sapi_name() === 'cli') {
        die("Database error: " . $e->getMessage() . "\n");
    } else {
        header('HTTP/1.1 503 Service Unavailable');
        die("We're experiencing technical difficulties. Please try again later.");
    }
}

// Create database tables if they don't exist (first-time setup)
function initializeDatabase($db) {
    $tables = [
        "CREATE TABLE IF NOT EXISTS users (
            user_id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            role ENUM('admin', 'coordinator', 'reader') NOT NULL,
            phone VARCHAR(20),
            photo_path VARCHAR(255),
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB",
        
        "CREATE TABLE IF NOT EXISTS mass_schedules (
            mass_id INT AUTO_INCREMENT PRIMARY KEY,
            mass_date DATE NOT NULL,
            mass_time TIME NOT NULL,
            mass_type ENUM('Sunday', 'Weekday', 'Special') NOT NULL,
            location VARCHAR(100) NOT NULL,
            notes TEXT,
            created_by INT,
            FOREIGN KEY (created_by) REFERENCES users(user_id)
        ) ENGINE=InnoDB",
        
        "CREATE TABLE IF NOT EXISTS reader_availability (
            availability_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            day_of_week ENUM('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday') NOT NULL,
            is_available BOOLEAN DEFAULT TRUE,
            FOREIGN KEY (user_id) REFERENCES users(user_id),
            UNIQUE KEY (user_id, day_of_week)
        ) ENGINE=InnoDB",
        
        "CREATE TABLE IF NOT EXISTS assignments (
            assignment_id INT AUTO_INCREMENT PRIMARY KEY,
            mass_id INT NOT NULL,
            user_id INT NOT NULL,
            role ENUM('First Reading', 'Second Reading', 'Preaching', 'Psalm', 'Other') NOT NULL,
            status ENUM('Assigned', 'Confirmed', 'Cancelled') DEFAULT 'Assigned',
            scripture_reference VARCHAR(100),
            FOREIGN KEY (mass_id) REFERENCES mass_schedules(mass_id),
            FOREIGN KEY (user_id) REFERENCES users(user_id)
        ) ENGINE=InnoDB",
        
        "CREATE TABLE IF NOT EXISTS reminders (
            reminder_id INT AUTO_INCREMENT PRIMARY KEY,
            assignment_id INT NOT NULL,
            reminder_type ENUM('Initial', '2-Day', '1-Day') NOT NULL,
            sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status ENUM('Pending', 'Sent', 'Failed') DEFAULT 'Pending',
            FOREIGN KEY (assignment_id) REFERENCES assignments(assignment_id)
        ) ENGINE=InnoDB",
        
        "CREATE TABLE IF NOT EXISTS system_logs (
            log_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            action VARCHAR(255) NOT NULL,
            details TEXT,
            ip_address VARCHAR(45),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id)
        ) ENGINE=InnoDB"
    ];
    
    try {
        // Enable foreign key constraints
        $db->exec("SET FOREIGN_KEY_CHECKS=1");
        
        // Create tables
        foreach ($tables as $table) {
            $db->exec($table);
        }
        
        // Create initial admin user if none exists
        $stmt = $db->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
        if ($stmt->fetchColumn() == 0) {
            $defaultAdmin = [
                'name' => 'System Administrator',
                'email' => 'admin@parish.example',
                'password' => 'ChangeThisPassword123!',
                'role' => 'admin'
            ];
            
            $passwordHash = password_hash($defaultAdmin['password'], PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $defaultAdmin['name'],
                $defaultAdmin['email'],
                $passwordHash,
                $defaultAdmin['role']
            ]);
            
            error_log("Initial admin user created. Email: " . $defaultAdmin['email'] . " Password: " . $defaultAdmin['password']);
        }
    } catch (PDOException $e) {
        error_log("Database initialization failed: " . $e->getMessage());
    }
}

// Run initialization on first setup (comment out after first run)
// initializeDatabase($db);
?>
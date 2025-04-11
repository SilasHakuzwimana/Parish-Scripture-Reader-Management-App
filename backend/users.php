<?php
require_once 'auth.php';
require_once 'db.php';

class UserManager {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function createUser($name, $email, $password, $role, $phone = null) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            $stmt = $this->db->prepare("INSERT INTO users (name, email, password_hash, role, phone) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $passwordHash, $role, $phone]);
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            // Handle duplicate email
            if ($e->errorInfo[1] == 1062) {
                throw new Exception("Email already exists");
            }
            throw $e;
        }
    }
    
    public function updateUser($userId, $name, $email, $phone = null, $isActive = true) {
        $stmt = $this->db->prepare("UPDATE users SET name = ?, email = ?, phone = ?, is_active = ? WHERE user_id = ?");
        $stmt->execute([$name, $email, $phone, $isActive, $userId]);
        return $stmt->rowCount();
    }
    
    public function deleteUser($userId) {
        // Soft delete (mark as inactive)
        $stmt = $this->db->prepare("UPDATE users SET is_active = FALSE WHERE user_id = ?");
        return $stmt->execute([$userId]);
    }
    
    public function getAllReaders() {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE role = 'reader' AND is_active = TRUE ORDER BY name");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getUserById($userId) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function setAvailability($userId, $day, $isAvailable) {
        // Check if record exists
        $stmt = $this->db->prepare("SELECT availability_id FROM reader_availability WHERE user_id = ? AND day_of_week = ?");
        $stmt->execute([$userId, $day]);
        
        if ($stmt->fetch()) {
            // Update existing
            $stmt = $this->db->prepare("UPDATE reader_availability SET is_available = ? WHERE user_id = ? AND day_of_week = ?");
            return $stmt->execute([$isAvailable, $userId, $day]);
        } else {
            // Insert new
            $stmt = $this->db->prepare("INSERT INTO reader_availability (user_id, day_of_week, is_available) VALUES (?, ?, ?)");
            return $stmt->execute([$userId, $day, $isAvailable]);
        }
    }
    
    public function getUserAvailability($userId) {
        $stmt = $this->db->prepare("SELECT day_of_week, is_available FROM reader_availability WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        $availability = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $availability[$row['day_of_week']] = (bool)$row['is_available'];
        }
        
        // Fill in missing days as available by default
        $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        foreach ($days as $day) {
            if (!isset($availability[$day])) {
                $availability[$day] = true;
            }
        }
        
        return $availability;
    }
}
?>
<?php
require_once 'auth.php';
require_once 'db.php';

class ScheduleManager {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function createMass($date, $time, $type, $location, $createdBy, $notes = null) {
        $stmt = $this->db->prepare("INSERT INTO mass_schedules (mass_date, mass_time, mass_type, location, notes, created_by) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$date, $time, $type, $location, $notes, $createdBy]);
        return $this->db->lastInsertId();
    }
    
    public function updateMass($massId, $date, $time, $type, $location, $notes = null) {
        $stmt = $this->db->prepare("UPDATE mass_schedules SET mass_date = ?, mass_time = ?, mass_type = ?, location = ?, notes = ? WHERE mass_id = ?");
        return $stmt->execute([$date, $time, $type, $location, $notes, $massId]);
    }
    
    public function deleteMass($massId) {
        // First delete assignments
        $this->db->prepare("DELETE FROM assignments WHERE mass_id = ?")->execute([$massId]);
        
        // Then delete the mass
        $stmt = $this->db->prepare("DELETE FROM mass_schedules WHERE mass_id = ?");
        return $stmt->execute([$massId]);
    }
    
    public function getMassById($massId) {
        $stmt = $this->db->prepare("SELECT * FROM mass_schedules WHERE mass_id = ?");
        $stmt->execute([$massId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getMassesByDateRange($startDate, $endDate) {
        $stmt = $this->db->prepare("SELECT * FROM mass_schedules WHERE mass_date BETWEEN ? AND ? ORDER BY mass_date, mass_time");
        $stmt->execute([$startDate, $endDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function assignReader($massId, $userId, $role, $scripture = null) {
        // Check if assignment already exists
        $stmt = $this->db->prepare("SELECT assignment_id FROM assignments WHERE mass_id = ? AND role = ?");
        $stmt->execute([$massId, $role]);
        
        if ($existing = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Update existing assignment
            $stmt = $this->db->prepare("UPDATE assignments SET user_id = ?, scripture_reference = ?, status = 'Assigned' WHERE assignment_id = ?");
            return $stmt->execute([$userId, $scripture, $existing['assignment_id']]);
        } else {
            // Create new assignment
            $stmt = $this->db->prepare("INSERT INTO assignments (mass_id, user_id, role, scripture_reference, status) VALUES (?, ?, ?, ?, 'Assigned')");
            return $stmt->execute([$massId, $userId, $role, $scripture]);
        }
    }
    
    public function removeAssignment($assignmentId) {
        $stmt = $this->db->prepare("DELETE FROM assignments WHERE assignment_id = ?");
        return $stmt->execute([$assignmentId]);
    }
    
    public function getAssignmentsForMass($massId) {
        $stmt = $this->db->prepare("
            SELECT a.*, u.name, u.email, u.phone 
            FROM assignments a
            JOIN users u ON a.user_id = u.user_id
            WHERE a.mass_id = ?
        ");
        $stmt->execute([$massId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getReaderAssignments($userId, $startDate = null, $endDate = null) {
        $sql = "
            SELECT a.*, m.mass_date, m.mass_time, m.mass_type, m.location 
            FROM assignments a
            JOIN mass_schedules m ON a.mass_id = m.mass_id
            WHERE a.user_id = ?
        ";
        
        $params = [$userId];
        
        if ($startDate && $endDate) {
            $sql .= " AND m.mass_date BETWEEN ? AND ?";
            array_push($params, $startDate, $endDate);
        }
        
        $sql .= " ORDER BY m.mass_date, m.mass_time";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function autoAssignReadersForWeek($weekStartDate) {
        $weekEndDate = date('Y-m-d', strtotime($weekStartDate . ' +6 days'));
        
        // Get all masses for the week
        $masses = $this->getMassesByDateRange($weekStartDate, $weekEndDate);
        
        // Get all active readers
        $userManager = new UserManager($this->db);
        $readers = $userManager->getAllReaders();
        
        foreach ($masses as $mass) {
            $dayOfWeek = date('l', strtotime($mass['mass_date']));
            
            // Get available readers for this day
            $availableReaders = [];
            foreach ($readers as $reader) {
                $availability = $userManager->getUserAvailability($reader['user_id']);
                if ($availability[$dayOfWeek]) {
                    $availableReaders[] = $reader['user_id'];
                }
            }
            
            if (empty($availableReaders)) {
                continue;
            }
            
            // For Sunday masses, assign multiple roles
            if ($mass['mass_type'] === 'Sunday') {
                $roles = ['First Reading', 'Second Reading', 'Preaching'];
                shuffle($availableReaders); // Randomize to distribute assignments
                
                foreach ($roles as $role) {
                    if (!empty($availableReaders)) {
                        $readerId = array_shift($availableReaders);
                        $this->assignReader($mass['mass_id'], $readerId, $role);
                    }
                }
            } else {
                // For weekday masses, assign one reader
                $readerId = $availableReaders[array_rand($availableReaders)];
                $this->assignReader($mass['mass_id'], $readerId, 'Reading');
            }
        }
        
        return true;
    }
}
?>
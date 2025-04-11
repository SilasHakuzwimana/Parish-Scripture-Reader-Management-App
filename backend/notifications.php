<?php
require_once 'auth.php';
require_once 'db.php';
require 'vendor/autoload.php'; 


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class NotificationManager {
    private $db;
    private $mailer;
    
    public function __construct($db) {
        $this->db = $db;
        
        // Configure PHPMailer
        $this->mailer = new PHPMailer(true);
        $this->mailer->isSMTP();
        $this->mailer->Host = 'smtp.gmail.com';
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = 'info.stbasile@gmail.com';
        $this->mailer->Password = '';#App password here
        $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mailer->Port = 587;
        $this->mailer->setFrom('info.stbasile@gmail.com', 'Parish Reader System');
    }
    
    public function sendInitialAssignmentNotification($assignmentId) {
        $assignment = $this->getAssignmentDetails($assignmentId);
        
        if (!$assignment) {
            return false;
        }
        
        $subject = "New Mass Reading Assignment - " . $assignment['mass_date'];
        
        $body = "
            <h2>Dear {$assignment['name']},</h2>
            <p>You have been assigned to read during the mass on <strong>{$assignment['mass_date']} at {$assignment['mass_time']}</strong>.</p>
            <p><strong>Your Role:</strong> {$assignment['role']}</p>
            <p><strong>Location:</strong> {$assignment['location']}</p>
        ";
        
        if (!empty($assignment['scripture_reference'])) {
            $body .= "<p><strong>Scripture:</strong> {$assignment['scripture_reference']}</p>";
        }
        
        $body .= "
            <p>Please arrive at least 15 minutes before the mass starts.</p>
            <p>If you are unable to fulfill this assignment, please contact the coordinator as soon as possible.</p>
            <p>Thank you for your service!</p>
        ";
        
        return $this->sendEmail($assignment['email'], $subject, $body, 'initial', $assignmentId);
    }
    
    public function sendReminder($assignmentId, $type) {
        $assignment = $this->getAssignmentDetails($assignmentId);
        
        if (!$assignment) {
            return false;
        }
        
        $daysWord = ($type === '2-Day') ? 'two days' : 'tomorrow';
        $subject = "Reminder: Your Mass Reading Assignment $daysWord - " . $assignment['mass_date'];
        
        $body = "
            <h2>Dear {$assignment['name']},</h2>
            <p>This is a reminder that you are scheduled to read during the mass $daysWord, <strong>{$assignment['mass_date']} at {$assignment['mass_time']}</strong>.</p>
            <p><strong>Your Role:</strong> {$assignment['role']}</p>
            <p><strong>Location:</strong> {$assignment['location']}</p>
        ";
        
        if (!empty($assignment['scripture_reference'])) {
            $body .= "<p><strong>Scripture:</strong> {$assignment['scripture_reference']}</p>";
        }
        
        $body .= "
            <p>Please arrive at least 15 minutes before the mass starts.</p>
            <p>If you have any issues, please contact the coordinator immediately.</p>
            <p>Thank you for your service!</p>
        ";
        
        return $this->sendEmail($assignment['email'], $subject, $body, $type, $assignmentId);
    }
    
    private function getAssignmentDetails($assignmentId) {
        $stmt = $this->db->prepare("
            SELECT a.*, u.name, u.email, m.mass_date, m.mass_time, m.location 
            FROM assignments a
            JOIN users u ON a.user_id = u.user_id
            JOIN mass_schedules m ON a.mass_id = m.mass_id
            WHERE a.assignment_id = ?
        ");
        $stmt->execute([$assignmentId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function sendEmail($to, $subject, $body, $type, $assignmentId) {
        try {
            $this->mailer->addAddress($to);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;
            
            $this->mailer->send();
            
            // Log the notification
            $this->logNotification($assignmentId, $type);
            
            return true;
        } catch (Exception $e) {
            error_log("Mailer Error: " . $this->mailer->ErrorInfo);
            return false;
        }
    }
    
    private function logNotification($assignmentId, $type) {
        $stmt = $this->db->prepare("INSERT INTO reminders (assignment_id, reminder_type, status) VALUES (?, ?, 'Sent')");
        $stmt->execute([$assignmentId, $type]);
    }
    
    public function processScheduledReminders() {
        // Process 2-day reminders
        $twoDaysLater = date('Y-m-d', strtotime('+2 days'));
        $this->sendRemindersForDate($twoDaysLater, '2-Day');
        
        // Process 1-day reminders
        $oneDayLater = date('Y-m-d', strtotime('+1 day'));
        $this->sendRemindersForDate($oneDayLater, '1-Day');
    }
    
    private function sendRemindersForDate($massDate, $reminderType) {
        // Get all assignments for the specified date that haven't had this reminder sent yet
        $stmt = $this->db->prepare("
            SELECT a.assignment_id 
            FROM assignments a
            JOIN mass_schedules m ON a.mass_id = m.mass_id
            LEFT JOIN reminders r ON a.assignment_id = r.assignment_id AND r.reminder_type = ?
            WHERE m.mass_date = ? 
            AND r.reminder_id IS NULL
            AND a.status = 'Assigned'
        ");
        $stmt->execute([$reminderType, $massDate]);
        
        $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($assignments as $assignment) {
            $this->sendReminder($assignment['assignment_id'], $reminderType);
        }
    }
}
?>
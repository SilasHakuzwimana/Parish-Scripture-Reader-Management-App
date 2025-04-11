<?php
require_once 'db.php';
require_once 'notifications.php';

// Initialize classes
$notificationManager = new NotificationManager($db);

// Log start of reminder process
error_log("Starting reminder processing at " . date('Y-m-d H:i:s'));

try {
    // Process scheduled reminders (2-day and 1-day)
    $notificationManager->processScheduledReminders();
    
    // Log successful completion
    error_log("Reminder processing completed successfully at " . date('Y-m-d H:i:s'));
    
    // Output for cron job logging
    echo "Reminders processed successfully at " . date('Y-m-d H:i:s') . "\n";
} catch (Exception $e) {
    // Log any errors
    error_log("Error processing reminders: " . $e->getMessage());
    
    // Output for cron job logging
    echo "Error processing reminders: " . $e->getMessage() . "\n";
    exit(1); // Exit with error code
}

// Additional functions for manual reminder triggering (optional)
if (php_sapi_name() === 'cli') {
    // Running from command line (cron)
    exit(0);
} else {
    // Running via web request (for testing)
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'message' => 'Reminders processed successfully',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
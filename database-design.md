-- Users table
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'coordinator', 'reader') NOT NULL,
    phone VARCHAR(20),
    photo_path VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,

    reset_token VARCHAR(64) NULL,

    reset_expires DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

```
reset_expires DATETIME NULL;
```


```
reset_expires DATETIME NULL;
```


```
reset_expires DATETIME NULL;
```


-- Mass schedule table
CREATE TABLE mass_schedules (
    mass_id INT AUTO_INCREMENT PRIMARY KEY,
    mass_date DATE NOT NULL,
    mass_time TIME NOT NULL,
    mass_type ENUM('Sunday', 'Weekday', 'Special') NOT NULL,
    location VARCHAR(100) NOT NULL,
    notes TEXT,
    created_by INT,
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);

-- Reader availability
CREATE TABLE reader_availability (
    availability_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    day_of_week ENUM('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday') NOT NULL,
    is_available BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    UNIQUE KEY (user_id, day_of_week)
);

-- Assignments
CREATE TABLE assignments (
    assignment_id INT AUTO_INCREMENT PRIMARY KEY,
    mass_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('First Reading', 'Second Reading', 'Preaching', 'Psalm', 'Other') NOT NULL,
    status ENUM('Assigned', 'Confirmed', 'Cancelled') DEFAULT 'Assigned',
    scripture_reference VARCHAR(100),
    FOREIGN KEY (mass_id) REFERENCES mass_schedules(mass_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Reminders
CREATE TABLE reminders (
    reminder_id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT NOT NULL,
    reminder_type ENUM('Initial', '2-Day', '1-Day') NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Pending', 'Sent', 'Failed') DEFAULT 'Pending',
    FOREIGN KEY (assignment_id) REFERENCES assignments(assignment_id)
);

-- System logs
CREATE TABLE system_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(255) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

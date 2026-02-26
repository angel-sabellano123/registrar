<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'rts_db');

function getDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}

function initDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
    if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

    $conn->query("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
    $conn->select_db(DB_NAME);

    $conn->query("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id VARCHAR(20) UNIQUE NOT NULL,
        first_name VARCHAR(100) NOT NULL,
        last_name VARCHAR(100) NOT NULL,
        email VARCHAR(150) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        course VARCHAR(100),
        year_level VARCHAR(20),
        role ENUM('student','admin') DEFAULT 'student',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $conn->query("CREATE TABLE IF NOT EXISTS tickets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ticket_number VARCHAR(20) UNIQUE NOT NULL,
        user_id INT NOT NULL,
        request_type VARCHAR(100) NOT NULL,
        purpose TEXT,
        semester VARCHAR(50),
        school_year VARCHAR(20),
        copies INT DEFAULT 1,
        notes TEXT,
        status ENUM('pending','in_progress','ready','completed','rejected') DEFAULT 'pending',
        admin_remarks TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    $conn->query("CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        ticket_id INT,
        message TEXT NOT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    // Create default admin
    $adminPass = password_hash('admin123', PASSWORD_DEFAULT);
    $conn->query("INSERT IGNORE INTO users (student_id, first_name, last_name, email, password, role)
        VALUES ('ADMIN001', 'System', 'Administrator', 'admin@rts.edu', '$adminPass', 'admin')");

    $conn->close();
}

initDB();
?>
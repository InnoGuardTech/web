<?php
$host = 'localhost';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create DB
    $pdo->exec("CREATE DATABASE IF NOT EXISTS haraj_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE haraj_db");
    
    // Drop old tables if they are broken/mismatched to avoid conflicts
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $pdo->exec("DROP TABLE IF EXISTS comments;");
    $pdo->exec("DROP TABLE IF EXISTS favorites;");
    $pdo->exec("DROP TABLE IF EXISTS messages;");
    $pdo->exec("DROP TABLE IF EXISTS chat_threads;");
    $pdo->exec("DROP TABLE IF EXISTS reviews;");
    $pdo->exec("DROP TABLE IF EXISTS notifications;");
    $pdo->exec("DROP TABLE IF EXISTS reports;");
    $pdo->exec("DROP TABLE IF EXISTS commission_transfers;");
    $pdo->exec("DROP TABLE IF EXISTS ads;");
    $pdo->exec("DROP TABLE IF EXISTS users;");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    
    // Users Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        phone VARCHAR(20) UNIQUE NOT NULL,
        email VARCHAR(100) DEFAULT NULL,
        password VARCHAR(255) NOT NULL,
        avatar VARCHAR(255) DEFAULT NULL,
        role VARCHAR(20) DEFAULT 'user',
        rating FLOAT DEFAULT 5.0,
        isBanned TINYINT(1) DEFAULT 0,
        joinedDate VARCHAR(20) NOT NULL,
        createdAt DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");
    
    // Ads Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS ads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        userId INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        category VARCHAR(50) NOT NULL,
        city VARCHAR(50) NOT NULL,
        price DECIMAL(12,2) DEFAULT NULL,
        images JSON,
        specifications JSON,
        carBrand VARCHAR(50),
        carYear VARCHAR(4),
        carTransmission VARCHAR(20),
        carMileage INT,
        propertyType VARCHAR(50),
        propertyRooms VARCHAR(20),
        propertyContract VARCHAR(50),
        views INT DEFAULT 0,
        isPinned TINYINT(1) DEFAULT 0,
        status ENUM('active', 'sold', 'deleted') DEFAULT 'active',
        createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");
    
    // Comments Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS comments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        adId INT NOT NULL,
        username VARCHAR(100) NOT NULL,
        content TEXT NOT NULL,
        createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (adId) REFERENCES ads(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");
    
    // Favorites Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS favorites (
        id INT AUTO_INCREMENT PRIMARY KEY,
        userId INT NOT NULL,
        adId INT NOT NULL,
        createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (adId) REFERENCES ads(id) ON DELETE CASCADE,
        UNIQUE KEY unique_fav (userId, adId)
    ) ENGINE=InnoDB");
    
    // Notifications Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        userId INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        content TEXT NOT NULL,
        isRead TINYINT(1) DEFAULT 0,
        createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");
    
    // Chat Threads Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS chat_threads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        adId INT NOT NULL,
        buyerId INT NOT NULL,
        unreadFor JSON,
        createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (adId) REFERENCES ads(id) ON DELETE CASCADE,
        FOREIGN KEY (buyerId) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");
    
    // Messages Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        threadId INT NOT NULL,
        senderId INT NOT NULL,
        text TEXT NOT NULL,
        createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (threadId) REFERENCES chat_threads(id) ON DELETE CASCADE,
        FOREIGN KEY (senderId) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");
    
    // Reviews Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        targetUserId INT NOT NULL,
        authorName VARCHAR(100) NOT NULL,
        rating INT DEFAULT 5,
        content TEXT NOT NULL,
        createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (targetUserId) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");
    
    // Reports Table (NEW - was missing)
    $pdo->exec("CREATE TABLE IF NOT EXISTS reports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        adId INT NOT NULL,
        reporterId INT NOT NULL,
        reason TEXT NOT NULL,
        status ENUM('pending', 'resolved', 'dismissed') DEFAULT 'pending',
        createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (adId) REFERENCES ads(id) ON DELETE CASCADE,
        FOREIGN KEY (reporterId) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");
    
    // Commission Transfers Table (NEW - was missing)
    $pdo->exec("CREATE TABLE IF NOT EXISTS commission_transfers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        userId INT NOT NULL,
        adId INT NOT NULL,
        amount DECIMAL(12,2) NOT NULL,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        proofImage VARCHAR(500) DEFAULT NULL,
        createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (adId) REFERENCES ads(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");
    
    // Seed Demo Users
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $hashed = password_hash('123456', PASSWORD_DEFAULT);
        $joined = date('Y-m');
        $pdo->exec("INSERT INTO users (name, phone, password, role, rating, joinedDate) VALUES 
            ('أبو راكان للسيارات', '0555555555', '$hashed', 'admin', 5.0, '$joined'),
            ('أحمد محمد', '0566666666', '$hashed', 'user', 4.8, '$joined'),
            ('خالد العتيبي', '0577777777', '$hashed', 'user', 5.0, '$joined'),
            ('محمد العمري', '0588888888', '$hashed', 'user', 4.5, '$joined'),
            ('عبدالله الحميدي', '0599999999', '$hashed', 'user', 4.9, '$joined')");
        echo "Demo users seeded.\n";
    }
    
    echo "Database, tables, and constraints setup/updated successfully.\n";
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>

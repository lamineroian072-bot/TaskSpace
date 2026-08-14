-- database/schema.sql
-- Online Boarding House Booking System Database Schema

CREATE DATABASE IF NOT EXISTS `boardinghouse_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `boardinghouse_db`;

-- 1. Users Table
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) UNIQUE NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'tenant') NOT NULL DEFAULT 'tenant',
    `phone` VARCHAR(30) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Rooms Table
CREATE TABLE IF NOT EXISTS `rooms` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `type` VARCHAR(50) NOT NULL,
    `price` DECIMAL(10,2) NOT NULL,
    `capacity` INT NOT NULL DEFAULT 1,
    `floor` VARCHAR(20) DEFAULT '1st Floor',
    `status` ENUM('Available', 'Occupied', 'Maintenance') DEFAULT 'Available',
    `image` VARCHAR(255) NULL,
    `description` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Bookings Table
CREATE TABLE IF NOT EXISTS `bookings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `room_id` INT NOT NULL,
    `user_id` INT NULL,
    `tenant_name` VARCHAR(100) NOT NULL,
    `tenant_phone` VARCHAR(30) NOT NULL,
    `tenant_email` VARCHAR(100) NOT NULL,
    `check_in_date` DATE NOT NULL,
    `move_in_date` DATE NULL,
    `notes` TEXT,
    `status` ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_bookings_room` (`room_id`),
    INDEX `idx_bookings_user` (`user_id`),
    INDEX `idx_bookings_status` (`status`),
    CONSTRAINT `fk_bookings_room` FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_bookings_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Payments Table
CREATE TABLE IF NOT EXISTS `payments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `booking_id` INT NOT NULL,
    `payment_method` VARCHAR(50) NOT NULL,
    `reference_number` VARCHAR(100) NULL,
    `proof_image` VARCHAR(255) NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `status` ENUM('Pending Verification', 'Verified', 'Rejected') DEFAULT 'Pending Verification',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_payments_booking` (`booking_id`),
    INDEX `idx_payments_status` (`status`),
    CONSTRAINT `fk_payments_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default Demo Data Insert (Password for both accounts: admin123 / tenant123)
INSERT INTO `users` (`name`, `email`, `password`, `role`, `phone`) VALUES
('Admin User', 'admin@boardinghouse.com', '$2y$10$wO8oK74sFzO.6qSgA6Lgq.kFk1CqYvV3yT5E3/m8/9YxYqXJgYJ8K', 'admin', '09100000001'),
('Tenant User', 'tenant@boardinghouse.com', '$2y$10$wO8oK74sFzO.6qSgA6Lgq.kFk1CqYvV3yT5E3/m8/9YxYqXJgYJ8K', 'tenant', '09100000002')
ON DUPLICATE KEY UPDATE `id`=`id`;

INSERT INTO `rooms` (`name`, `type`, `price`, `capacity`, `floor`, `status`, `image`, `description`) VALUES
('Room 101 - Cozy Single', 'Single', 3500.00, 1, '1st Floor', 'Available', 'images/single.svg', 'Cozy single room with study desk, private fan, and high-speed Wi-Fi.'),
('Room 102 - Deluxe Double', 'Double', 5500.00, 2, '1st Floor', 'Available', 'images/double.svg', 'Spacious room suitable for 2 tenants with twin beds and built-in closets.'),
('Room 201 - Executive Studio', 'Studio', 8000.00, 2, '2nd Floor', 'Available', 'images/studio.svg', 'Modern studio room with private bathroom, kitchenette, and air conditioner.'),
('Room 202 - Shared Dormitory', 'Dormitory', 2500.00, 4, '2nd Floor', 'Available', 'images/dormitory.svg', 'Affordable bedspace in a shared 4-person aircon room with individual lockers.')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Roles table
CREATE TABLE IF NOT EXISTS `roles` (
  `role_id` INT NOT NULL AUTO_INCREMENT,
  `role_name` VARCHAR(50) NOT NULL UNIQUE,
  PRIMARY KEY (`role_id`)
);

-- Users table
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `fullname` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role_id` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`role_id`) REFERENCES `roles`(`role_id`) ON DELETE CASCADE
);

-- Farms table
CREATE TABLE IF NOT EXISTS `farms` (
  `farm_id` INT NOT NULL AUTO_INCREMENT,
  `farm_name` VARCHAR(255) NOT NULL,
  `owner_id` INT NOT NULL,
  `location` VARCHAR(255),
  `barangay` VARCHAR(100),
  `city` VARCHAR(100),
  `province` VARCHAR(100),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`farm_id`),
  FOREIGN KEY (`owner_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- Devices table
CREATE TABLE IF NOT EXISTS `devices` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `device_type` VARCHAR(255) NOT NULL,
  `device_mac` VARCHAR(255) NOT NULL UNIQUE,
  `device_wifi` VARCHAR(255),
  `device_wifi_pass` VARCHAR(255),
  `device_wifi_ip` VARCHAR(255),
  `registration_date` DATE,
  `registration_time` TIME,
  `device_serial_no` VARCHAR(255) UNIQUE,
  `device_owner_id` INT,
  `is_registered` TINYINT DEFAULT 0,
  `iv` VARCHAR(255),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`device_owner_id`) REFERENCES `farms`(`farm_id`) ON DELETE SET NULL
);

-- Egg data table
CREATE TABLE IF NOT EXISTS `egg_data` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `mac` VARCHAR(17),
  `size` VARCHAR(50),
  `egg_weight` DECIMAL(5,2) NOT NULL,
  `validation_status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
  PRIMARY KEY (`id`)
);

-- Trays table
CREATE TABLE IF NOT EXISTS `trays` (
  `tray_id` INT NOT NULL AUTO_INCREMENT,
  `farm_id` INT NOT NULL,
  `device_mac` VARCHAR(17) NOT NULL,
  `size` VARCHAR(50) NOT NULL,
  `egg_count` INT NOT NULL DEFAULT 0,
  `status` ENUM('pending', 'published', 'sold', 'expired') DEFAULT 'pending',
  `price` DECIMAL(10,2) DEFAULT NULL,
  `image_url` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `published_at` TIMESTAMP NULL,
  `sold_at` TIMESTAMP NULL,
  `expired_at` TIMESTAMP NULL,
  PRIMARY KEY (`tray_id`),
  FOREIGN KEY (`farm_id`) REFERENCES `farms`(`farm_id`) ON DELETE CASCADE,
  FOREIGN KEY (`device_mac`) REFERENCES `devices`(`device_mac`) ON DELETE CASCADE
);

-- Tray Settings
CREATE TABLE IF NOT EXISTS `tray_settings` (
  `setting_id` INT NOT NULL AUTO_INCREMENT,
  `farm_id` INT NOT NULL,
  `size` VARCHAR(50) NOT NULL,
  `default_price` DECIMAL(10,2) NOT NULL,
  `auto_publish` BOOLEAN DEFAULT 0,
  PRIMARY KEY (`setting_id`),
  FOREIGN KEY (`farm_id`) REFERENCES `farms`(`farm_id`) ON DELETE CASCADE
);

-- Tray Eggs (Many-to-many)
CREATE TABLE IF NOT EXISTS `tray_eggs` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `tray_id` INT NOT NULL,
  `egg_id` INT NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`tray_id`) REFERENCES `trays`(`tray_id`) ON DELETE CASCADE,
  FOREIGN KEY (`egg_id`) REFERENCES `egg_data`(`id`) ON DELETE CASCADE
);

-- Notifications
CREATE TABLE IF NOT EXISTS `notifications` (
  `notification_id` INT NOT NULL AUTO_INCREMENT,
  `farm_id` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `type` ENUM('success', 'error', 'warning', 'info') NOT NULL,
  `is_read` BOOLEAN DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`notification_id`),
  FOREIGN KEY (`farm_id`) REFERENCES `farms`(`farm_id`) ON DELETE CASCADE
);

-- Transactions
CREATE TABLE IF NOT EXISTS `transactions` (
  `transaction_id` INT NOT NULL AUTO_INCREMENT,
  `farm_id` INT NOT NULL,
  `subtotal` DECIMAL(10,2) NOT NULL,
  `tax` DECIMAL(10,2) NOT NULL,
  `total` DECIMAL(10,2) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`transaction_id`),
  FOREIGN KEY (`farm_id`) REFERENCES `farms`(`farm_id`) ON DELETE CASCADE
);

-- Transaction Items
CREATE TABLE IF NOT EXISTS `transaction_items` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `transaction_id` INT NOT NULL,
  `size` VARCHAR(50) NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `quantity` INT NOT NULL,
  `total` DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`transaction_id`) REFERENCES `transactions`(`transaction_id`) ON DELETE CASCADE
);

-- Employee Types
CREATE TABLE IF NOT EXISTS `employee_types` (
  `type_id` INT NOT NULL AUTO_INCREMENT,
  `type_name` VARCHAR(50) NOT NULL UNIQUE,
  PRIMARY KEY (`type_id`)
);

INSERT INTO `employee_types` (`type_name`) VALUES
('Farmer'),
('Veterinarian'),
('Feeding Specialist'),
('Egg Collector'),
('Cleaner'),
('Manager'),
('Maintenance Technician');

-- Employees
CREATE TABLE IF NOT EXISTS `employees` (
  `employee_id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `farm_id` INT NOT NULL,
  `type_id` INT NOT NULL,
  `hire_date` DATE NOT NULL,
  `salary` DECIMAL(10,2) DEFAULT NULL,
  `status` ENUM('active', 'on_leave', 'terminated') DEFAULT 'active',
  PRIMARY KEY (`employee_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`farm_id`) REFERENCES `farms`(`farm_id`) ON DELETE CASCADE,
  FOREIGN KEY (`type_id`) REFERENCES `employee_types`(`type_id`) ON DELETE CASCADE
);

-- Tasks
CREATE TABLE IF NOT EXISTS `tasks` (
  `task_id` INT NOT NULL AUTO_INCREMENT,
  `farm_id` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `assigned_to` INT,
  `schedule_id` INT DEFAULT NULL,
  `priority` ENUM('low', 'medium', 'high') DEFAULT 'medium',
  `due_date` DATE,
  `status` ENUM('pending', 'in_progress', 'completed', 'overdue') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `completed_at` TIMESTAMP NULL,
  PRIMARY KEY (`task_id`),
  FOREIGN KEY (`farm_id`) REFERENCES `farms`(`farm_id`) ON DELETE CASCADE,
  FOREIGN KEY (`assigned_to`) REFERENCES `employees`(`employee_id`) ON DELETE SET NULL
);

-- Checklist items
CREATE TABLE IF NOT EXISTS `checklist_items` (
  `item_id` INT NOT NULL AUTO_INCREMENT,
  `task_id` INT NOT NULL,
  `description` VARCHAR(255) NOT NULL,
  `is_completed` BOOLEAN DEFAULT 0,
  `completed_at` TIMESTAMP NULL,
  PRIMARY KEY (`item_id`),
  FOREIGN KEY (`task_id`) REFERENCES `tasks`(`task_id`) ON DELETE CASCADE
);

-- Schedules
CREATE TABLE IF NOT EXISTS `schedules` (
  `schedule_id` INT NOT NULL AUTO_INCREMENT,
  `farm_id` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `schedule_type` ENUM('daily', 'weekly', 'custom') NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `days_of_week` VARCHAR(20),
  `custom_days` VARCHAR(255),
  `assigned_to` INT,
  `is_recurring` BOOLEAN DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`schedule_id`),
  FOREIGN KEY (`farm_id`) REFERENCES `farms`(`farm_id`) ON DELETE CASCADE,
  FOREIGN KEY (`assigned_to`) REFERENCES `employees`(`employee_id`) ON DELETE SET NULL
);

-- Add schedule_id foreign key to tasks (after creating schedules)
ALTER TABLE `tasks`
  ADD FOREIGN KEY (`schedule_id`) REFERENCES `schedules`(`schedule_id`) ON DELETE SET NULL;

-- Consumer Addresses (moved above for FK dependency)
CREATE TABLE IF NOT EXISTS `consumer_addresses` (
  `address_id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `label` VARCHAR(100) NOT NULL,
  `recipient_name` VARCHAR(255) NOT NULL,
  `street_address` TEXT NOT NULL,
  `barangay` VARCHAR(100) NOT NULL,
  `city` VARCHAR(100) NOT NULL,
  `province` VARCHAR(100) NOT NULL,
  `zip_code` VARCHAR(10) NOT NULL,
  `contact_number` VARCHAR(20) NOT NULL,
  `is_default` BOOLEAN DEFAULT 0,
  PRIMARY KEY (`address_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- Consumer Orders
CREATE TABLE IF NOT EXISTS `consumer_orders` (
  `order_id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `address_id` INT NOT NULL,
  `order_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `total_amount` DECIMAL(10,2) NOT NULL,
  `status` ENUM('pending', 'confirmed', 'packing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
  `delivery_method` ENUM('pickup', 'delivery') NOT NULL DEFAULT 'delivery',
  `delivery_date` DATE,
  `delivery_time` TIME,
  `payment_method` VARCHAR(50) DEFAULT 'COD',
  `payment_status` VARCHAR(50) DEFAULT 'pending',
  `order_notes` TEXT,
  PRIMARY KEY (`order_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`address_id`) REFERENCES `consumer_addresses`(`address_id`) ON DELETE RESTRICT
);

-- Consumer Order Items
CREATE TABLE IF NOT EXISTS `consumer_order_items` (
  `item_id` INT NOT NULL AUTO_INCREMENT,
  `order_id` INT NOT NULL,
  `tray_id` INT NOT NULL,
  `quantity` INT NOT NULL,
  `unit_price` DECIMAL(10,2) NOT NULL,
  `total_price` DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (`item_id`),
  FOREIGN KEY (`order_id`) REFERENCES `consumer_orders`(`order_id`) ON DELETE CASCADE,
  FOREIGN KEY (`tray_id`) REFERENCES `trays`(`tray_id`) ON DELETE RESTRICT
);

-- Consumer Loyalty
CREATE TABLE IF NOT EXISTS `consumer_loyalty` (
  `user_id` INT NOT NULL,
  `points` INT NOT NULL DEFAULT 0,
  `tier` ENUM('bronze', 'silver', 'gold', 'platinum') DEFAULT 'bronze',
  `last_activity` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- Consumer Reviews
CREATE TABLE IF NOT EXISTS `consumer_reviews` (
  `review_id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `order_id` INT NOT NULL,
  `rating` TINYINT(1) NOT NULL CHECK (rating BETWEEN 1 AND 5),
  `comment` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `approved` BOOLEAN DEFAULT 0,
  PRIMARY KEY (`review_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`order_id`) REFERENCES `consumer_orders`(`order_id`) ON DELETE CASCADE
);

-- Consumer Carts
CREATE TABLE IF NOT EXISTS `consumer_carts` (
  `cart_id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`cart_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- Consumer Cart Items
CREATE TABLE IF NOT EXISTS `consumer_cart_items` (
  `item_id` INT NOT NULL AUTO_INCREMENT,
  `cart_id` INT NOT NULL,
  `tray_id` INT NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  PRIMARY KEY (`item_id`),
  FOREIGN KEY (`cart_id`) REFERENCES `consumer_carts`(`cart_id`) ON DELETE CASCADE,
  FOREIGN KEY (`tray_id`) REFERENCES `trays`(`tray_id`) ON DELETE CASCADE
);

-- Create tax_settings table
CREATE TABLE IF NOT EXISTS `tax_settings` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `farm_id` INT NOT NULL,
  `tax_name` VARCHAR(100) NOT NULL,
  `tax_rate` DECIMAL(5,2) NOT NULL,
  `is_default` BOOLEAN DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`farm_id`) REFERENCES `farms`(`farm_id`) ON DELETE CASCADE
);

-- Add tax_id to transactions table
ALTER TABLE `transactions`
ADD `tax_id` INT DEFAULT NULL AFTER `farm_id`,
ADD FOREIGN KEY (`tax_id`) REFERENCES `tax_settings`(`id`) ON DELETE SET NULL;

ALTER TABLE `tax_settings` 
ADD COLUMN `is_active` BOOLEAN NOT NULL DEFAULT 1 AFTER `is_default`;

ALTER TABLE `transactions`
ADD CONSTRAINT `fk_transactions_tax`
FOREIGN KEY (`tax_id`) REFERENCES `tax_settings`(`id`)
ON DELETE SET NULL;

ALTER TABLE consumer_cart_items
ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Trigger: Add loyalty points after order
DELIMITER $$
CREATE TRIGGER after_consumer_order
AFTER INSERT ON consumer_order_items
FOR EACH ROW
BEGIN
    DECLARE points_earned INT;
    SET points_earned = FLOOR(NEW.total_price / 10);

    INSERT INTO consumer_loyalty (user_id, points)
    SELECT o.user_id, points_earned
    FROM consumer_orders o
    WHERE o.order_id = NEW.order_id
    ON DUPLICATE KEY UPDATE
        points = points + points_earned,
        last_activity = CURRENT_TIMESTAMP;
END$$
DELIMITER ;
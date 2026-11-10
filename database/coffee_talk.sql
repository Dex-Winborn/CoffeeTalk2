-- Coffee Talk Café System database backup (safe to import repeatedly).
-- Import via phpMyAdmin. Demo login: demo / Demo@1234
CREATE DATABASE IF NOT EXISTS `coffee_talk3` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `coffee_talk3`;

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `full_name` VARCHAR(100) NOT NULL,
  `username` VARCHAR(30) NOT NULL,
  `email` VARCHAR(120) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('staff','admin') NOT NULL DEFAULT 'staff',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`), UNIQUE KEY `uq_users_username` (`username`), UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `token_hash` CHAR(64) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_password_reset_token_hash` (`token_hash`),
  KEY `idx_password_reset_user` (`user_id`),
  CONSTRAINT `fk_password_reset_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `menu_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(80) NOT NULL,
  `category` VARCHAR(40) NOT NULL,
  `description` VARCHAR(255) NOT NULL DEFAULT '',
  `price` DECIMAL(8,2) NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`), UNIQUE KEY `uq_menu_category_name` (`category`,`name`), KEY `idx_menu_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_ref` VARCHAR(30) NOT NULL,
  `user_id` INT UNSIGNED NULL DEFAULT NULL,
  `item_id` INT UNSIGNED NOT NULL,
  `customer_name` VARCHAR(100) NOT NULL,
  `quantity` SMALLINT UNSIGNED NOT NULL,
  `unit_price` DECIMAL(8,2) NOT NULL,
  `total_amount` DECIMAL(10,2) NOT NULL,
  `status` ENUM('Pending','Preparing','Served','Cancelled') NOT NULL DEFAULT 'Pending',
  `notes` VARCHAR(255) NOT NULL DEFAULT '',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`), UNIQUE KEY `uq_orders_ref` (`order_ref`), KEY `idx_orders_status` (`status`), KEY `idx_orders_user` (`user_id`), KEY `idx_orders_item` (`item_id`),
  CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_orders_item` FOREIGN KEY (`item_id`) REFERENCES `menu_items` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `payments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_ref` VARCHAR(30) NOT NULL,
  `method` ENUM('Cash','QR','Card') NOT NULL,
  `amount_due` DECIMAL(10,2) NOT NULL,
  `amount_received` DECIMAL(10,2) NOT NULL,
  `change_given` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `reference_no` VARCHAR(40) NOT NULL DEFAULT '',
  `received_by` INT UNSIGNED NULL DEFAULT NULL,
  `paid_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`), UNIQUE KEY `uq_payments_order` (`order_ref`), KEY `idx_payments_method` (`method`),
  CONSTRAINT `fk_payments_order` FOREIGN KEY (`order_ref`) REFERENCES `orders` (`order_ref`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_payments_user` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `users` (`id`,`full_name`,`username`,`email`,`password_hash`,`role`) VALUES
(1,'Demo Barista','demo','demo@example.com','$2y$10$z00YcNxn0njpLu9eGQNkFeJeTJOpAFlJiZNhMZJTKHgJoPpAAnLqO','staff');
INSERT INTO `users` (`full_name`,`username`,`email`,`password_hash`,`role`)
VALUES ('Coffee Talk Administrator','admin','admin@example.com','$2y$12$IEaKkCcImEIpVkZk7gYE.e/3y8vmeRE1LBcG/jdKOn5qMyu0GyNhy','admin')
ON DUPLICATE KEY UPDATE `role`='admin';

INSERT IGNORE INTO `menu_items` (`id`,`name`,`category`,`description`,`price`,`is_active`) VALUES
(1,'Americano','Coffee (Iced / Hot)','',100,1),(2,'Caffe Latte','Coffee (Iced / Hot)','',120,1),(3,'Spanish Latte','Coffee (Iced / Hot)','',150,1),(4,'Mocha','Coffee (Iced / Hot)','',150,1),(5,'White Mocha','Coffee (Iced / Hot)','',160,1),(6,'Caramel Latte','Coffee (Iced / Hot)','',150,1),(7,'Salted Caramel Latte','Coffee (Iced / Hot)','',150,1),(8,'Caramel Macchiato','Coffee (Iced / Hot)','',150,1),(9,'Hazelnut Latte','Coffee (Iced / Hot)','',150,1),(10,'Vanilla Latte','Coffee (Iced / Hot)','',150,1),
(11,'CT Signature Blend','Premium Coffee','',160,1),(12,'Sea Salt Crème Latte','Premium Coffee','',160,1),(13,'Tres Leches','Premium Coffee','',160,1),(14,'Popcorn Oat Latte','Premium Coffee','',180,1),(15,'Biscoff Cloud Latte','Premium Coffee','',180,1),
(16,'Sauce / Syrup','Add-ons','',30,1),(17,'Espresso Shot','Add-ons','',30,1),(18,'Sub Oatmilk','Add-ons','',40,1),
(19,'Matcha Latte','Non-Coffee','',140,1),(20,'Sea Salt Matcha','Non-Coffee','',160,1),(21,'Chocolate Latte','Non-Coffee','',130,1),(22,'Strawberry Latte','Non-Coffee','',130,1),(23,'Strawberry Matcha','Non-Coffee','',150,1),
(24,'Apple Chamomile','Wellness Blend (Iced)','',130,1),(25,'Passion Fruit Burst','Wellness Blend (Iced)','',130,1),(26,'Green Apple Twist','Wellness Blend (Iced)','',130,1),(27,'Lychee Fizz','Wellness Blend (Iced)','',130,1),(28,'Classic Wellness Tea','Wellness Blend (Hot)','',100,1),(29,'Apple Chamomile','Wellness Blend (Hot)','',100,1),
(30,'Double Choco Chip','Chill Blend (Frappe)','',160,1),(31,'Oreo CB','Chill Blend (Frappe)','',165,1),(32,'Matcha CB','Chill Blend (Frappe)','',165,1),(33,'Strawberry CB','Chill Blend (Frappe)','',165,1),(34,'Biscoff CB','Chill Blend (Frappe)','',170,1),(35,'Java Chip CB','Chill Blend (Frappe)','',170,1),
(36,'Flavored Fries','Snack','Cheese/Sour cream/Bbq',100,1),(37,'Cheezy Nachos','Snack','',130,1),(38,'Twister Fries','Snack','',150,1),(39,'Beef Lasagna','Pasta','',160,1),(40,'Creamy Carbonara','Pasta','',180,1),
(41,'Classic Croffle','Classic Croffle','',90,1),(42,'Classic Cinnamon','Classic Croffle','',100,1),(43,'Classic Choco','Classic Croffle','',110,1),(44,'Berry Delight','Premium Croffle','',130,1),(45,'Nutella Alcapone','Premium Croffle','',145,1),(46,'Oreo Craze','Premium Croffle','',150,1),(47,'Biscoff Crunch','Premium Croffle','',160,1),(48,'Banana Pudding','Premium Croffle','',180,1);

INSERT IGNORE INTO `orders` (`id`,`order_ref`,`user_id`,`item_id`,`customer_name`,`quantity`,`unit_price`,`total_amount`,`status`,`notes`,`created_at`) VALUES
(1,'CT-20261102-0001',1,3,'Maria Santos',2,150,300,'Served','Less sweet','2026-11-02 09:20:00'),
(2,'CT-20261102-0002',1,19,'Joshua Reyes',1,140,140,'Served','','2026-11-02 10:05:00'),
(3,'CT-20261103-0003',1,40,'Andrea Cruz',1,180,180,'Served','','2026-11-03 12:40:00'),
(4,'CT-20261104-0004',1,48,'Paolo Villanueva',2,180,360,'Cancelled','Customer left','2026-11-04 15:10:00'),
(5,'CT-20261105-0005',1,31,'Liza Mendoza',1,165,165,'Preparing','','2026-11-05 14:25:00'),
(6,'CT-20261105-0006',1,15,'Carlo Dizon',1,180,180,'Pending','Oat milk','2026-11-05 16:02:00');
INSERT IGNORE INTO `payments` (`id`,`order_ref`,`method`,`amount_due`,`amount_received`,`change_given`,`reference_no`,`received_by`,`paid_at`) VALUES
(1,'CT-20261102-0001','Cash',300,500,200,'',1,'2026-11-02 09:25:00'),
(2,'CT-20261102-0002','QR',140,140,0,'QR-88231',1,'2026-11-02 10:08:00'),
(3,'CT-20261103-0003','Card',180,180,0,'AUTH-4410',1,'2026-11-03 12:45:00');

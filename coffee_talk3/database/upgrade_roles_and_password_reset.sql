-- Apply once to an existing coffee_talk3 database. This keeps all existing data.
USE `coffee_talk3`;

ALTER TABLE `users`
  ADD COLUMN `role` ENUM('staff','admin') NOT NULL DEFAULT 'staff' AFTER `password_hash`;

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

INSERT INTO `users` (`full_name`,`username`,`email`,`password_hash`,`role`)
VALUES ('Coffee Talk Administrator','admin','admin@example.com','$2y$12$IEaKkCcImEIpVkZk7gYE.e/3y8vmeRE1LBcG/jdKOn5qMyu0GyNhy','admin')
ON DUPLICATE KEY UPDATE `role`='admin';
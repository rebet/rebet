use rebet;

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `user_id` INTEGER PRIMARY KEY,
    `name` TEXT NOT NULL,
    `gender` INTEGER NOT NULL,
    `birthday` DATE NOT NULL,
    `email` TEXT NOT NULL,
    `role` VARCHAR(6) NOT NULL DEFAULT 'user',
    `password` VARCHAR(255) NOT NULL,
    `api_token` VARCHAR(127),
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

DROP TABLE IF EXISTS `remember_tokens`;
CREATE TABLE `remember_tokens` (
    `provider` VARCHAR(127) NOT NULL,
    `remember_token` VARCHAR(127) NOT NULL,
    `remember_id` VARCHAR(127) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME,
    PRIMARY KEY(`provider`, `remember_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

DROP TABLE IF EXISTS `banks`;
CREATE TABLE `banks` (
    `user_id` INTEGER PRIMARY KEY,
    `name` VARCHAR(127) NOT NULL,
    `branch` VARCHAR(127) NOT NULL,
    `number` VARCHAR(7) NOT NULL,
    `holder` VARCHAR(127) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

DROP TABLE IF EXISTS `articles`;
CREATE TABLE `articles` (
    `article_id` INTEGER PRIMARY KEY AUTO_INCREMENT,
    `user_id` INTEGER NOT NULL,
    `subject` VARCHAR(30) NOT NULL,
    `body` TEXT NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

DROP TABLE IF EXISTS `groups`;
CREATE TABLE `groups` (
    `groups_id` INTEGER PRIMARY KEY AUTO_INCREMENT,
    `name` TEXT NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

DROP TABLE IF EXISTS `group_user`;
CREATE TABLE `group_user` (
    `group_id` INTEGER,
    `user_id` INTEGER,
    `position` INTEGER NOT NULL DEFAULT 3,
    `join_on` DATE NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME,
    PRIMARY KEY(`group_id`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

DROP TABLE IF EXISTS `fortunes`;
CREATE TABLE `fortunes` (
    `gender` INTEGER NOT NULL,
    `birthday` DATE NOT NULL,
    `result` TEXT NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME,
    PRIMARY KEY(`gender`, `birthday`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

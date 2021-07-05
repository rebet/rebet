use `{! $db_name !}`;

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

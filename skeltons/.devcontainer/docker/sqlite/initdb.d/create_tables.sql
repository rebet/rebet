DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `user_id` INTEGER PRIMARY KEY,
    `name` TEXT NOT NULL,
    `gender` INTEGER NOT NULL,
    `birthday` TEXT NOT NULL,
    `email` TEXT NOT NULL,
    `role` TEXT NOT NULL DEFAULT 'user',
    `password` TEXT NOT NULL,
    `api_token` TEXT,
    `created_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TEXT
);

DROP TABLE IF EXISTS `remember_tokens`;
CREATE TABLE `remember_tokens` (
    `provider` TEXT NOT NULL,
    `remember_token` TEXT NOT NULL,
    `remember_id` TEXT NOT NULL,
    `expires_at` TEXT NOT NULL,
    `created_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TEXT,
    PRIMARY KEY(`provider`, `remember_token`)
);

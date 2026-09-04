CREATE DATABASE `{! $db_name !}` DEFAULT CHARACTER SET utf8mb4 DEFAULT COLLATE utf8mb4_bin;

CREATE USER `{! $db_user !}` IDENTIFIED BY '{! $db_pass !}';
CREATE USER `{! $db_user !}`@localhost IDENTIFIED BY '{! $db_pass !}';
GRANT ALL PRIVILEGES ON `{! $db_name !}`.* TO `{! $db_user !}` WITH GRANT OPTION;
GRANT ALL PRIVILEGES ON `{! $db_name !}`.* TO `{! $db_user !}`@localhost WITH GRANT OPTION;

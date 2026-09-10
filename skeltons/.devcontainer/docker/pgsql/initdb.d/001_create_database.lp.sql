CREATE USER {! $db_user !} WITH PASSWORD '{! $db_pass !}';
ALTER ROLE {! $db_user !} WITH SUPERUSER;
CREATE DATABASE {! $db_name !} WITH OWNER = {! $db_user !} TEMPLATE = template0 ENCODING = 'UTF8' LC_COLLATE = 'C' LC_CTYPE = 'C';

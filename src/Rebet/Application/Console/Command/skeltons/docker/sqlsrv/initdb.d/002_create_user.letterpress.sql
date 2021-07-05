CREATE LOGIN [{! $db_user !}]
WITH
  PASSWORD = '{! $db_pass !}',
  DEFAULT_DATABASE = [{! $db_name !}],
  CHECK_EXPIRATION = OFF,
  CHECK_POLICY = OFF
;

USE [{! $db_name !}];

CREATE USER [{! $db_user !}] FOR LOGIN [{! $db_name !}];

EXEC sp_addrolemember 'db_owner', '{! $db_user !}';

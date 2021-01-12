CREATE LOGIN [rebet]
WITH
  PASSWORD = 'rebet',
  DEFAULT_DATABASE = [rebet],
  CHECK_EXPIRATION = OFF,
  CHECK_POLICY = OFF
;

USE [rebet];

CREATE USER [rebet] FOR LOGIN [rebet];

EXEC sp_addrolemember 'db_owner', 'rebet';

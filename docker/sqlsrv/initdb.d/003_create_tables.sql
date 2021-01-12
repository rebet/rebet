USE [rebet];

DROP TABLE IF EXISTS [users];
CREATE TABLE [users] (
    [user_id] INTEGER PRIMARY KEY,
    [name] NVARCHAR(MAX) NOT NULL,
    [gender] INTEGER NOT NULL,
    [birthday] DATE NOT NULL,
    [email] NVARCHAR(MAX) NOT NULL,
    [role] NVARCHAR(6) NOT NULL DEFAULT 'user',
    [password] NVARCHAR(255) NOT NULL,
    [api_token] NVARCHAR(127),
    [created_at] DATETIME2(6) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    [updated_at] DATETIME2(6)
);

DROP TABLE IF EXISTS [remember_tokens];
CREATE TABLE [remember_tokens] (
    [provider] NVARCHAR(127) NOT NULL,
    [remember_token] NVARCHAR(127) NOT NULL,
    [remember_id] NVARCHAR(127) NOT NULL,
    [expires_at] DATETIME2(6) NOT NULL,
    [created_at] DATETIME2(6) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    [updated_at] DATETIME2(6),
    PRIMARY KEY([provider], [remember_token])
);

DROP TABLE IF EXISTS [banks];
CREATE TABLE [banks] (
    [user_id] INTEGER PRIMARY KEY,
    [name] NVARCHAR(127) NOT NULL,
    [branch] NVARCHAR(127) NOT NULL,
    [number] NVARCHAR(7) NOT NULL,
    [holder] NVARCHAR(127) NOT NULL,
    [created_at] DATETIME2(6) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    [updated_at] DATETIME2(6)
);

DROP TABLE IF EXISTS [articles];
CREATE TABLE [articles] (
    [article_id] INTEGER PRIMARY KEY IDENTITY(1,1),
    [user_id] INTEGER NOT NULL,
    [subject] NVARCHAR(30) NOT NULL,
    [body] NVARCHAR(MAX) NOT NULL,
    [created_at] DATETIME2(6) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    [updated_at] DATETIME2(6)
);

DROP TABLE IF EXISTS [groups];
CREATE TABLE [groups] (
    [groups_id] INTEGER PRIMARY KEY IDENTITY(1,1),
    [name] NVARCHAR(MAX) NOT NULL,
    [created_at] DATETIME2(6) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    [updated_at] DATETIME2(6)
);

DROP TABLE IF EXISTS [group_user];
CREATE TABLE [group_user] (
    [group_id] INTEGER,
    [user_id] INTEGER,
    [position] INTEGER NOT NULL DEFAULT 3,
    [join_on] DATE NOT NULL,
    [created_at] DATETIME2(6) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    [updated_at] DATETIME2(6),
    PRIMARY KEY([group_id], [user_id])
);

DROP TABLE IF EXISTS [fortunes];
CREATE TABLE [fortunes] (
    [gender] INTEGER NOT NULL,
    [birthday] DATE NOT NULL,
    [result] NVARCHAR(MAX) NOT NULL,
    [created_at] DATETIME2(6) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    [updated_at] DATETIME2(6),
    PRIMARY KEY([gender], [birthday])
);

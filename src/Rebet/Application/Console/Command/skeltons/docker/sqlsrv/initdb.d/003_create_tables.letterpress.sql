USE [{! $db_name !}];

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

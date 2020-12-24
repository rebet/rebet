\c rebet;

DROP TABLE IF EXISTS "users";
CREATE TABLE "users" (
    "user_id" INTEGER PRIMARY KEY,
    "name" TEXT NOT NULL,
    "gender" INTEGER NOT NULL,
    "birthday" DATE NOT NULL,
    "email" TEXT NOT NULL,
    "role" TEXT NOT NULL DEFAULT 'user',
    "password" TEXT NOT NULL,
    "api_token" TEXT,
    "created_at" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP
);

DROP TABLE IF EXISTS "remember_tokens";
CREATE TABLE "remember_tokens" (
    "provider" TEXT NOT NULL,
    "remember_token" TEXT NOT NULL,
    "remember_id" TEXT NOT NULL,
    "expires_at" TIMESTAMP NOT NULL,
    "created_at" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP,
    PRIMARY KEY("provider", "remember_token")
);

DROP TABLE IF EXISTS "banks";
CREATE TABLE "banks" (
    "user_id" INTEGER PRIMARY KEY,
    "name" TEXT NOT NULL,
    "branch" TEXT NOT NULL,
    "number" TEXT NOT NULL,
    "holder" TEXT NOT NULL,
    "created_at" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP
);

DROP TABLE IF EXISTS "articles";
CREATE TABLE "articles" (
    "article_id" SERIAL,
    "user_id" INTEGER NOT NULL,
    "subject" VARCHAR(30) NOT NULL,
    "body" TEXT NOT NULL,
    "created_at" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP
);

DROP TABLE IF EXISTS "groups";
CREATE TABLE "groups" (
    "group_id" SERIAL,
    "name" TEXT NOT NULL,
    "created_at" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP
);

DROP TABLE IF EXISTS "group_user";
CREATE TABLE "group_user" (
    "group_id" INTEGER,
    "user_id" INTEGER,
    "position" INTEGER NOT NULL DEFAULT 3,
    "join_on" Date NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "created_at" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP,
    PRIMARY KEY("group_id", "user_id")
);

DROP TABLE IF EXISTS "fortunes";
CREATE TABLE "fortunes" (
    "gender" INTEGER NOT NULL,
    "birthday" DATE NOT NULL,
    "result" TEXT NOT NULL,
    "created_at" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP,
    PRIMARY KEY("gender", "birthday")
);

CREATE TABLE "projects" (
	"id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL ,
	"name" VARCHAR NOT NULL ,
	"branch" VARCHAR NOT NULL  DEFAULT 'master',
	"last_deployed" INTEGER,
	"destination" VARCHAR NOT NULL ,
	"repository_id" INTEGER NOT NULL
);

CREATE TABLE "repositories" (
	"id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL ,
	"name" VARCHAR NOT NULL ,
	"hash" VARCHAR NOT NULL,
	"remote" VARCHAR NOT NULL,
	"location" VARCHAR NOT NULL
);

CREATE TABLE "config" (
	"id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL ,
	"key" VARCHAR NOT NULL ,
	"value" VARCHAR NOT NULL
);

INSERT INTO config ("key", "value") VALUES ("hipchat_enabled", "no");
INSERT INTO config ("key", "value") VALUES ("hipchat_room_id", "1");
INSERT INTO config ("key", "value") VALUES ("hipchat_color", "purple");
INSERT INTO config ("key", "value") VALUES ("hipchat_from", "Git Deployer");
INSERT INTO config ("key", "value") VALUES ("hipchat_notify", "1");
INSERT INTO config ("key", "value") VALUES ("hipchat_auth_token", "change_me");
INSERT INTO config ("key", "value") VALUES ("curl_proxy", "");

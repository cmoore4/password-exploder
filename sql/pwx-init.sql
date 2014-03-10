CREATE DATABASE "pwx";

CREATE EXTENSION "uuid-ossp";
CREATE SEQUENCE "pwx_global_seq";

CREATE TABLE passwords (
  id VARCHAR (256) PRIMARY KEY,
  created TIMESTAMP DEFAULT CURRENT_DATE,
  count INT8 NOT NULL DEFAULT NEXTVAL('pwx_global_seq'),
  password VARCHAR (256) NOT NULL,
  username VARCHAR (256) DEFAULT NULL,
  note TEXT DEFAULT NULL,
  expiration TIMESTAMP NOT NULL,
  maxviews INT DEFAULT NULL,
  ip_restrictions VARCHAR(256) DEFAULT NULL,
  account_id INT8 DEFAULT NULL,
  lock_to_account BOOLEAN DEFAULT FALSE,
  emails TEXT DEFAULT NULL,
  viewcount INT DEFAULT 0
);

CREATE TABLE accounts (
  id INT8 NOT NULL DEFAULT NEXTVAL('pwx_global_seq'),
  email VARCHAR(256) NOT NULL UNIQUE,
  password VARCHAR(256) NOT NULL,
  name VARCHAR(256) DEFAULT NULL,
  account_password VARCHAR(256) NOT NULL
);

CREATE TABLE logs (
  id INT8 PRIMARY KEY,
  created TIMESTAMP DEFAULT CURRENT_DATE,
  has_username BOOLEAN DEFAULT FALSE,
  has_note BOOLEAN DEFAULT FALSE,
  has_maxviews BOOLEAN DEFAULT FALSE,
  has_ip_restrictions BOOLEAN DEFAULT FALSE,
  has_account BOOLEAN DEFAULT FALSE,
  notification_count INT DEFAULT 0,
  total_viewcount INT DEFAULT 0
);

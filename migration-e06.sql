-- Menambah kolom password_hash untuk autentikasi JWT.
USE todo_app;

ALTER TABLE users
  ADD COLUMN password_hash VARCHAR(255) NOT NULL DEFAULT '' AFTER email;

-- Set password demo "password123" untuk user yang sudah ada
UPDATE users SET password_hash = '$2y$10$9G5VWyfnAFtCm.sVDqJAYuTHpGaaC/JFfcc87cvuIGu34Rd57ipoi' WHERE password_hash = '';

ALTER TABLE users ALTER COLUMN password_hash DROP DEFAULT;

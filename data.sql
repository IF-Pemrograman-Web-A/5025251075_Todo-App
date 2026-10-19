CREATE DATABASE if not exists todo_app
character set utf8mb4
collate utf8mb4_general_ci;

USE todo_app;

CREATE TABLE if not exists users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(255) NOT NULL UNIQUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE todos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    owner_id INT UNSIGNED NOT NULL,

    title VARCHAR(255) NOT NULL,
    description TEXT NULL,

    due_date DATE NOT NULL,
    notification_time DATETIME NULL,

    image_path VARCHAR(255) NULL,

    is_completed BOOLEAN NOT NULL DEFAULT FALSE,

    visibility ENUM('personal', 'shared')
        NOT NULL DEFAULT 'personal',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_todos_owner
        FOREIGN KEY (owner_id)
        REFERENCES users(id)
        ON DELETE CASCADE
);

INSERT INTO users (name, email) VALUES
('Naufal', 'naufal@example.com'),
('Demo User', 'demo@example.com');

INSERT INTO todos (
    owner_id,
    title,
    description,
    due_date,
    notification_time,
    is_completed,
    visibility
) VALUES
(
    1,
    'Ambil baju di laundry',
    'Ambil sebelum sore',
    '2026-10-15',
    NULL,
    FALSE,
    'personal'
),
(
    1,
    'Selesaikan tugas E04',
    'Implementasi PHP',
    '2026-10-16',
    NULL,
    FALSE,
    'shared'
),
(
    2,
    'Belajar PHP',
    'Mempelajari PDO',
    '2026-10-17',
    NULL,
    FALSE,
    'personal'
);
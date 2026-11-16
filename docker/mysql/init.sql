CREATE DATABASE IF NOT EXISTS reelhouse_chat CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON reelhouse_chat.* TO 'reelhouse'@'%';
FLUSH PRIVILEGES;

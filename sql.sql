-- 1. Таблица пользователей
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) UNIQUE NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('listener', 'author', 'admin') DEFAULT 'listener',
    `avatar` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Таблица альбомов (создаем раньше треков, так как треки ссылаются на них)
CREATE TABLE `albums` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `author_id` INT NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `cover_path` VARCHAR(255) DEFAULT NULL,
    `released_at` DATE,
    FOREIGN KEY (`author_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Таблица треков
CREATE TABLE `tracks` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `author_id` INT NOT NULL,
    `album_id` INT DEFAULT NULL,
    `title` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `cover_path` VARCHAR(255) DEFAULT NULL,
    `duration` INT COMMENT 'Длительность в секундах',
    `plays_count` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`author_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`album_id`) REFERENCES `albums`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Таблица плейлистов
CREATE TABLE `playlists` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `is_public` TINYINT(1) DEFAULT 1,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Связующая таблица для плейлистов и треков (Many-to-Many)
CREATE TABLE `playlist_tracks` (
    `playlist_id` INT NOT NULL,
    `track_id` INT NOT NULL,
    `sort_order` INT DEFAULT 0,
    PRIMARY KEY (`playlist_id`, `track_id`),
    FOREIGN KEY (`playlist_id`) REFERENCES `playlists`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`track_id`) REFERENCES `tracks`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Лайки (они же избранное)
CREATE TABLE `likes` (
    `user_id` INT NOT NULL,
    `track_id` INT NOT NULL,
    PRIMARY KEY (`user_id`, `track_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`track_id`) REFERENCES `tracks`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Подписки на авторов
CREATE TABLE `subscriptions` (
    `subscriber_id` INT NOT NULL,
    `author_id` INT NOT NULL,
    PRIMARY KEY (`subscriber_id`, `author_id`),
    FOREIGN KEY (`subscriber_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`author_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. История прослушиваний (статистика)
CREATE TABLE `listens` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT DEFAULT NULL,
    `track_id` INT NOT NULL,
    `listened_seconds` INT DEFAULT NULL COMMENT 'Сколько секунд трек реально слушали',
    `is_completed` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1, если трек дослушали до конца',
    `listened_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_listens_track_id` (`track_id`),
    INDEX `idx_listens_user_id` (`user_id`),
    INDEX `idx_listens_listened_at` (`listened_at`),
    INDEX `idx_listens_track_listened_at` (`track_id`, `listened_at`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`track_id`) REFERENCES `tracks`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. Автоматическое обновление общего счётчика прослушиваний
DROP TRIGGER IF EXISTS `trg_listens_after_insert_increment_track_plays`;

CREATE TRIGGER `trg_listens_after_insert_increment_track_plays`
AFTER INSERT ON `listens`
FOR EACH ROW
UPDATE `tracks`
SET `plays_count` = `plays_count` + 1
WHERE `id` = NEW.`track_id`;

-- Для существующей базы данных:
ALTER TABLE `users`
MODIFY `role` ENUM('listener', 'author', 'admin') DEFAULT 'listener';

ALTER TABLE `listens`
    ADD COLUMN `listened_seconds` INT DEFAULT NULL COMMENT 'Сколько секунд трек реально слушали' AFTER `track_id`,
    ADD COLUMN `is_completed` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1, если трек дослушали до конца' AFTER `listened_seconds`,
    ADD INDEX `idx_listens_track_id` (`track_id`),
    ADD INDEX `idx_listens_user_id` (`user_id`),
    ADD INDEX `idx_listens_listened_at` (`listened_at`),
    ADD INDEX `idx_listens_track_listened_at` (`track_id`, `listened_at`);

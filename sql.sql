-- =============================================================
-- Richsound — полная схема БД
-- Объединяет schema.sql + все миграции (001–003)
-- Запуск: mysql -u <user> -p <database> < sql.sql
-- =============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -------------------------------------------------------------
-- Удаление таблиц (в порядке, обратном FK-зависимостям)
-- -------------------------------------------------------------
DROP TABLE IF EXISTS `listens`;
DROP TABLE IF EXISTS `likes`;
DROP TABLE IF EXISTS `playlist_tracks`;
DROP TABLE IF EXISTS `playlists`;
DROP TABLE IF EXISTS `subscriptions`;
DROP TABLE IF EXISTS `tracks`;
DROP TABLE IF EXISTS `albums`;
DROP TABLE IF EXISTS `users`;

-- -------------------------------------------------------------
-- 1. users
-- -------------------------------------------------------------
CREATE TABLE `users` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `name`       VARCHAR(255) NOT NULL,
    `email`      VARCHAR(255) NOT NULL UNIQUE,
    `password`   VARCHAR(255) NOT NULL,
    `role`       ENUM('listener', 'author', 'admin') NOT NULL DEFAULT 'listener',
    `avatar`     VARCHAR(255)  DEFAULT NULL,
    `bio`        TEXT          DEFAULT NULL,           -- migration 003
    `created_at` TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    FULLTEXT INDEX `ft_users_name` (`name`)            -- migration 001
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 2. albums
-- -------------------------------------------------------------
CREATE TABLE `albums` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `author_id`   INT          NOT NULL,
    `title`       VARCHAR(255) NOT NULL,
    `cover_path`  VARCHAR(255) DEFAULT NULL,
    `released_at` DATE         DEFAULT NULL,
    `created_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`author_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 3. tracks
-- -------------------------------------------------------------
CREATE TABLE `tracks` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `author_id`   INT          NOT NULL,
    `album_id`    INT          DEFAULT NULL,
    `title`       VARCHAR(255) NOT NULL,
    `file_path`   VARCHAR(255) NOT NULL,
    `cover_path`  VARCHAR(255) DEFAULT NULL,
    `duration`    INT          DEFAULT NULL COMMENT 'Длительность в секундах',
    `plays_count` INT          NOT NULL DEFAULT 0,
    `created_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`author_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`album_id`)  REFERENCES `albums`(`id`) ON DELETE SET NULL,
    FULLTEXT INDEX `ft_tracks_title` (`title`)         -- migration 001
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 4. playlists
-- -------------------------------------------------------------
CREATE TABLE `playlists` (
    `id`        INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`   INT          NOT NULL,
    `title`     VARCHAR(255) NOT NULL,
    `is_public` TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 5. playlist_tracks  (M:M треки ↔ плейлисты)
-- -------------------------------------------------------------
CREATE TABLE `playlist_tracks` (
    `playlist_id` INT NOT NULL,
    `track_id`    INT NOT NULL,
    `sort_order`  INT NOT NULL DEFAULT 0,
    PRIMARY KEY (`playlist_id`, `track_id`),
    FOREIGN KEY (`playlist_id`) REFERENCES `playlists`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`track_id`)    REFERENCES `tracks`(`id`)    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 6. likes
-- -------------------------------------------------------------
CREATE TABLE `likes` (
    `user_id`  INT NOT NULL,
    `track_id` INT NOT NULL,
    PRIMARY KEY (`user_id`, `track_id`),
    FOREIGN KEY (`user_id`)  REFERENCES `users`(`id`)  ON DELETE CASCADE,
    FOREIGN KEY (`track_id`) REFERENCES `tracks`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 7. subscriptions
-- -------------------------------------------------------------
CREATE TABLE `subscriptions` (
    `subscriber_id` INT       NOT NULL,
    `author_id`     INT       NOT NULL,
    `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,  -- migration 002
    PRIMARY KEY (`subscriber_id`, `author_id`),
    FOREIGN KEY (`subscriber_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`author_id`)     REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 8. listens
-- -------------------------------------------------------------
CREATE TABLE `listens` (
    `id`               INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`          INT       DEFAULT NULL,
    `track_id`         INT       NOT NULL,
    `listened_seconds` INT       DEFAULT NULL COMMENT 'Сколько секунд реально слушали',
    `is_completed`     TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 — трек дослушан до конца',
    `listened_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_listens_track_id`          (`track_id`),
    INDEX `idx_listens_user_id`           (`user_id`),
    INDEX `idx_listens_listened_at`       (`listened_at`),
    INDEX `idx_listens_track_listened_at` (`track_id`, `listened_at`),
    FOREIGN KEY (`user_id`)  REFERENCES `users`(`id`)  ON DELETE SET NULL,
    FOREIGN KEY (`track_id`) REFERENCES `tracks`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- -------------------------------------------------------------
-- 9. Триггер: автоинкремент plays_count при записи прослушивания
-- -------------------------------------------------------------
DROP TRIGGER IF EXISTS `trg_listens_after_insert_increment_track_plays`;

DELIMITER $$
CREATE TRIGGER `trg_listens_after_insert_increment_track_plays`
AFTER INSERT ON `listens`
FOR EACH ROW
BEGIN
    UPDATE `tracks`
    SET `plays_count` = `plays_count` + 1
    WHERE `id` = NEW.`track_id`;
END$$
DELIMITER ;

-- =====================================================
-- Sawalef - Block & Report Feature
-- SQL Migration Script
-- Date: 2026-03-07
-- =====================================================

-- -----------------------------------------------------
-- Table: user_blocks (جدول الحظر)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_blocks` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `blocker_id` BIGINT UNSIGNED NOT NULL COMMENT 'المستخدم الذي عمل الحظر',
    `blocked_id` BIGINT UNSIGNED NOT NULL COMMENT 'المستخدم المحظور',
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `user_blocks_blocker_blocked_unique` (`blocker_id`, `blocked_id`),
    KEY `user_blocks_blocked_id_foreign` (`blocked_id`),
    CONSTRAINT `user_blocks_blocker_id_foreign`
        FOREIGN KEY (`blocker_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `user_blocks_blocked_id_foreign`
        FOREIGN KEY (`blocked_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table: post_reports (جدول البلاغات)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `post_reports` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reporter_id` BIGINT UNSIGNED NOT NULL COMMENT 'المستخدم الذي أبلغ',
    `post_id`     BIGINT UNSIGNED NOT NULL COMMENT 'البوست المُبلَّغ عنه',
    `reason`      VARCHAR(255)    NULL     COMMENT 'سبب البلاغ (اختياري)',
    `created_at`  TIMESTAMP NULL DEFAULT NULL,
    `updated_at`  TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `post_reports_reporter_post_unique` (`reporter_id`, `post_id`),
    KEY `post_reports_post_id_foreign` (`post_id`),
    CONSTRAINT `post_reports_reporter_id_foreign`
        FOREIGN KEY (`reporter_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `post_reports_post_id_foreign`
        FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Laravel migrations record (لتسجيل الـ migration في Laravel)
-- -----------------------------------------------------
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_03_07_000001_create_user_blocks_table', IFNULL(MAX(`batch`), 0) + 1
FROM `migrations`
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations` WHERE `migration` = '2026_03_07_000001_create_user_blocks_table'
);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_03_07_000002_create_post_reports_table', (SELECT MAX(`batch`) FROM `migrations`)
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations` WHERE `migration` = '2026_03_07_000002_create_post_reports_table'
);

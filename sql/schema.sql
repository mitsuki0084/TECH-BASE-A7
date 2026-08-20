-- データベース作成と設定
CREATE DATABASE IF NOT EXISTS `travel_plan_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `travel_plan_db`;

-- テーブル削除（再作成時のため）
DROP TABLE IF EXISTS `schedules`;
DROP TABLE IF EXISTS `plans`;
DROP TABLE IF EXISTS `tags`;
DROP TABLE IF EXISTS `users`;

-- 1. ユーザーテーブル
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL COMMENT 'ユーザー表示名',
    `email` VARCHAR(100) NOT NULL UNIQUE COMMENT 'ログイン用メールアドレス',
    `password` VARCHAR(255) NOT NULL COMMENT 'ハッシュ化済パスワード',
    `role` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0: 一般ユーザー, 1: 管理者',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. 観光エリアタグテーブル
CREATE TABLE `tags` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL UNIQUE COMMENT 'タグ名（例: 関東、北海道）',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 3. 旅行プランテーブル
CREATE TABLE `plans` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL COMMENT '作成ユーザーID',
    `tag_id` INT DEFAULT NULL COMMENT '関連エリアタグID',
    `title` VARCHAR(100) NOT NULL COMMENT 'プランタイトル',
    `start_date` DATE NOT NULL COMMENT '旅行開始日',
    `end_date` DATE NOT NULL COMMENT '旅行終了日',
    `status` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '0: 非公開, 1: 公開, 2: 強制非公開(管理者操作)',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`tag_id`) REFERENCES `tags`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 4. スケジュール詳細テーブル
CREATE TABLE `schedules` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `plan_id` INT NOT NULL COMMENT '所属プランID',
    `day_number` INT NOT NULL DEFAULT 1 COMMENT '日程（1日目、2日目等）',
    `spot_name` VARCHAR(100) NOT NULL COMMENT '観光地名',
    `time_slot` VARCHAR(50) DEFAULT NULL COMMENT '時間帯メモ（例: 10:00 - 12:00）',
    `memo` TEXT DEFAULT NULL COMMENT '詳細メモ',
    `sort_order` INT NOT NULL DEFAULT 0 COMMENT '並び替え順序用インデックス',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`plan_id`) REFERENCES `plans`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- テストデータの初期投入
INSERT INTO `users` (`username`, `email`, `password`, `role`) VALUES
('管理者ユーザー', 'admin@example.com', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1a9I.6e5YQpWJg1.Z81m5/sYpBGe1sS', 1), -- pass: password
('一般ユーザー1', 'user1@example.com', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1a9I.6e5YQpWJg1.Z81m5/sYpBGe1sS', 0);

INSERT INTO `tags` (`name`) VALUES 
('北海道・東北'), ('関東'), ('中部・北陸'), ('関西'), ('中国・四国'), ('九州・沖縄');
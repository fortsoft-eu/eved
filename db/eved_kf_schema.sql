CREATE TABLE IF NOT EXISTS `kf_menu` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `target` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_kf_menu_path` (`path`),
  KEY `ix_kf_menu_order` (`order`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

CREATE TABLE IF NOT EXISTS `kf_fin_types` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `legacy_id` smallint(5) unsigned DEFAULT NULL,
  `type_kind` enum('expense','income','group') CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `name` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `order` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `updated_at` datetime(6) NOT NULL DEFAULT current_timestamp(6) ON UPDATE current_timestamp(6),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_kf_fin_types_legacy_id` (`legacy_id`),
  UNIQUE KEY `ux_kf_fin_types_name` (`name`),
  KEY `ix_kf_fin_types_kind_order` (`type_kind`,`order`,`name`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

CREATE TABLE IF NOT EXISTS `kf_fin_groups` (
  `group_type_id` smallint(5) unsigned NOT NULL,
  `member_type_id` smallint(5) unsigned NOT NULL,
  PRIMARY KEY (`group_type_id`,`member_type_id`),
  KEY `ix_kf_fin_groups_member` (`member_type_id`),
  CONSTRAINT `fk_kf_fin_groups_group` FOREIGN KEY (`group_type_id`) REFERENCES `kf_fin_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_kf_fin_groups_member` FOREIGN KEY (`member_type_id`) REFERENCES `kf_fin_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

CREATE TABLE IF NOT EXISTS `kf_fin_trans` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `legacy_id` int(10) unsigned DEFAULT NULL,
  `transaction_date` date NOT NULL,
  `finance_type_id` smallint(5) unsigned NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `counterparty` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `updated_at` datetime(6) NOT NULL DEFAULT current_timestamp(6) ON UPDATE current_timestamp(6),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_kf_fin_trans_legacy_id` (`legacy_id`),
  KEY `ix_kf_fin_trans_date` (`transaction_date`,`id`),
  KEY `ix_kf_fin_trans_type` (`finance_type_id`),
  CONSTRAINT `fk_kf_fin_trans_type` FOREIGN KEY (`finance_type_id`) REFERENCES `kf_fin_types` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

CREATE TABLE IF NOT EXISTS `kf_debts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `legacy_id` int(10) unsigned DEFAULT NULL,
  `first_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `account_number` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `updated_at` datetime(6) NOT NULL DEFAULT current_timestamp(6) ON UPDATE current_timestamp(6),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_kf_debts_legacy_id` (`legacy_id`),
  KEY `ix_kf_debts_name` (`last_name`,`first_name`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

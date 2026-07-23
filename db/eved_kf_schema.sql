CREATE TABLE `kf_debts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ex_subjects_id` int(10) unsigned DEFAULT NULL,
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `updated_at` datetime(6) NOT NULL DEFAULT current_timestamp(6) ON UPDATE current_timestamp(6),
  PRIMARY KEY (`id`),
  KEY `ix_kf_debts_ex_subjects_id` (`ex_subjects_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `kf_debt_movements` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `debt_id` int(10) unsigned NOT NULL,
  `movement_date` date NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` char(3) NOT NULL DEFAULT 'USD',
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `updated_at` datetime(6) NOT NULL DEFAULT current_timestamp(6) ON UPDATE current_timestamp(6),
  PRIMARY KEY (`id`),
  KEY `ix_kf_debt_movements_debt_date` (`debt_id`,`movement_date`,`id`),
  KEY `ix_kf_debt_movements_date` (`movement_date`,`id`),
  KEY `ix_kf_debt_movements_currency` (`currency`,`movement_date`,`id`),
  CONSTRAINT `fk_kf_debt_movements_debt` FOREIGN KEY (`debt_id`) REFERENCES `kf_debts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `kf_exchange_rates` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `valid_for` date NOT NULL,
  `order` int(10) unsigned NOT NULL,
  `country` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci NOT NULL,
  `currency` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci NOT NULL,
  `currency_code` char(3) NOT NULL,
  `amount` bigint(20) unsigned NOT NULL,
  `rate` decimal(18,6) NOT NULL,
  `fetched_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `updated_at` datetime(6) NOT NULL DEFAULT current_timestamp(6) ON UPDATE current_timestamp(6),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_kf_exrates_date_currency` (`valid_for`,`currency_code`),
  KEY `ix_kf_exrates_currency_date` (`currency_code`,`valid_for`),
  KEY `ix_kf_exrates_valid_for` (`valid_for`,`id`),
  KEY `ix_kf_exrates_date_order` (`valid_for`,`order`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `kf_exrates_fetches` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `requested_for` date NOT NULL,
  `status` enum('pending','running','success','error') NOT NULL DEFAULT 'pending',
  `last_attempt_at` datetime(6) DEFAULT NULL,
  `succeeded_at` datetime(6) DEFAULT NULL,
  `response_valid_for` date DEFAULT NULL,
  `http_status_code` smallint(5) unsigned DEFAULT NULL,
  `rates_count` int(10) unsigned NOT NULL DEFAULT 0,
  `attempt_count` int(10) unsigned NOT NULL DEFAULT 0,
  `error_message` varchar(1024) DEFAULT NULL,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `updated_at` datetime(6) NOT NULL DEFAULT current_timestamp(6) ON UPDATE current_timestamp(6),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_kf_exrates_fetches_date` (`requested_for`),
  KEY `ix_kf_exrates_fetches_status` (`status`,`last_attempt_at`),
  KEY `ix_kf_exrates_fetches_response` (`response_valid_for`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `kf_fin_types` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `legacy_id` int(10) unsigned DEFAULT NULL,
  `type_kind` enum('expense','income','group') NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `order` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `updated_at` datetime(6) NOT NULL DEFAULT current_timestamp(6) ON UPDATE current_timestamp(6),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_kf_fin_types_name` (`name`),
  UNIQUE KEY `ux_kf_fin_types_legacy_id` (`legacy_id`),
  KEY `ix_kf_fin_types_kind_order` (`type_kind`,`order`,`name`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `kf_fin_groups` (
  `group_type_id` int(10) unsigned NOT NULL,
  `member_type_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`group_type_id`,`member_type_id`),
  KEY `ix_kf_fin_groups_member` (`member_type_id`),
  CONSTRAINT `fk_kf_fin_groups_group` FOREIGN KEY (`group_type_id`) REFERENCES `kf_fin_types` (`id`),
  CONSTRAINT `fk_kf_fin_groups_member` FOREIGN KEY (`member_type_id`) REFERENCES `kf_fin_types` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `kf_fin_transactions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `legacy_id` int(10) unsigned DEFAULT NULL,
  `transaction_date` date NOT NULL,
  `finance_type_id` int(10) unsigned NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` char(3) NOT NULL DEFAULT 'USD',
  `counterparty` varchar(255) DEFAULT NULL,
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `updated_at` datetime(6) NOT NULL DEFAULT current_timestamp(6) ON UPDATE current_timestamp(6),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_kf_fin_transactions_legacy_id` (`legacy_id`),
  KEY `ix_kf_fin_transactions_date` (`transaction_date`,`id`),
  KEY `ix_kf_fin_transactions_type` (`finance_type_id`),
  KEY `ix_kf_fin_transactions_currency` (`currency`,`transaction_date`,`id`),
  CONSTRAINT `fk_kf_fin_transactions_type` FOREIGN KEY (`finance_type_id`) REFERENCES `kf_fin_types` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `kf_subscriptions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci NOT NULL,
  `finance_type_id` int(10) unsigned NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` char(3) NOT NULL DEFAULT 'USD',
  `billing_period` enum('weekly','monthly','quarterly','yearly','other') NOT NULL DEFAULT 'monthly',
  `billing_day` tinyint(2) unsigned DEFAULT NULL,
  `next_due_at` datetime(6) DEFAULT NULL,
  `counterparty` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `updated_at` datetime(6) NOT NULL DEFAULT current_timestamp(6) ON UPDATE current_timestamp(6),
  PRIMARY KEY (`id`),
  KEY `ix_kf_subscriptions_name` (`name`,`id`),
  KEY `ix_kf_subscriptions_type` (`finance_type_id`),
  KEY `ix_kf_subscriptions_active_due` (`is_active`,`next_due_at`,`id`),
  KEY `ix_kf_subscriptions_currency` (`currency`,`next_due_at`,`id`),
  CONSTRAINT `fk_kf_subscriptions_type` FOREIGN KEY (`finance_type_id`) REFERENCES `kf_fin_types` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

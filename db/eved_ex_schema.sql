CREATE TABLE `ex_calendar_events` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `calendar_url_hash` char(64) NOT NULL,
  `source_event_key` char(64) NOT NULL,
  `first_fetch_id` int(10) unsigned NOT NULL,
  `last_fetch_id` int(10) unsigned NOT NULL,
  `event_order` int(10) unsigned NOT NULL DEFAULT 0,
  `uid` varchar(255) DEFAULT NULL,
  `recurrence_id_raw` varchar(64) DEFAULT NULL,
  `status` varchar(32) DEFAULT NULL,
  `summary` varchar(512) DEFAULT NULL,
  `description` mediumtext DEFAULT NULL,
  `location` varchar(512) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `start_raw` varchar(64) DEFAULT NULL,
  `start_timezone` varchar(64) DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `end_raw` varchar(64) DEFAULT NULL,
  `end_timezone` varchar(64) DEFAULT NULL,
  `dtstamp_raw` varchar(64) DEFAULT NULL,
  `created_raw` varchar(64) DEFAULT NULL,
  `last_modified_raw` varchar(64) DEFAULT NULL,
  `sequence_no` int(11) DEFAULT NULL,
  `transp` varchar(32) DEFAULT NULL,
  `raw_event` mediumtext NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `seen_count` int(10) unsigned NOT NULL DEFAULT 1,
  `first_seen_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `last_seen_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `ux_ex_calendar_events_source` (`calendar_url_hash`,`source_event_key`) USING BTREE,
  KEY `ix_ex_calendar_events_calendar_date` (`calendar_url_hash`,`is_active`,`start_date`,`start_time`,`event_order`,`id`) USING BTREE,
  KEY `ix_ex_calendar_events_first_fetch` (`first_fetch_id`) USING BTREE,
  KEY `ix_ex_calendar_events_last_fetch` (`last_fetch_id`) USING BTREE,
  KEY `ix_ex_calendar_events_uid` (`uid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `ex_calendar_fetches` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `calendar_url_hash` char(64) NOT NULL,
  `calendar_url` text NOT NULL,
  `status` varchar(16) NOT NULL DEFAULT 'pending',
  `last_attempt_at` datetime(6) DEFAULT NULL,
  `succeeded_at` datetime(6) DEFAULT NULL,
  `attempt_count` int(10) unsigned NOT NULL DEFAULT 0,
  `http_status_code` smallint(5) unsigned DEFAULT NULL,
  `events_count` int(10) unsigned DEFAULT NULL,
  `raw_content` longtext DEFAULT NULL,
  `error_message` varchar(1000) DEFAULT NULL,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `updated_at` datetime(6) NOT NULL DEFAULT current_timestamp(6) ON UPDATE current_timestamp(6),
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `ux_ex_calendar_fetches_hash` (`calendar_url_hash`) USING BTREE,
  KEY `ix_ex_calendar_fetches_status_attempt` (`status`,`last_attempt_at`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `ex_contact_types` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `contact_type` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `order` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `ux_ex_contact_types_type` (`contact_type`) USING BTREE,
  UNIQUE KEY `ux_ex_contact_types_name` (`name`) USING BTREE,
  KEY `ix_ex_contact_types_active_order` (`is_active`,`order`,`id`) USING BTREE,
  KEY `ix_ex_contact_types_order` (`order`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `ex_contacts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `contact_type_id` int(10) unsigned NOT NULL,
  `contact_value` varchar(255) NOT NULL,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `updated_at` datetime(6) NOT NULL DEFAULT current_timestamp(6) ON UPDATE current_timestamp(6),
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `ux_ex_contacts_type_value` (`contact_type_id`,`contact_value`) USING BTREE,
  KEY `ix_ex_contacts_value` (`contact_value`) USING BTREE,
  CONSTRAINT `fk_ex_contacts_contact_type` FOREIGN KEY (`contact_type_id`) REFERENCES `ex_contact_types` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `ex_groups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `legacy_id` int(10) unsigned DEFAULT NULL,
  `order` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `updated_at` datetime(6) NOT NULL DEFAULT current_timestamp(6) ON UPDATE current_timestamp(6),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_ex_groups_name` (`name`),
  KEY `ix_ex_groups_legacy_id` (`legacy_id`),
  KEY `ix_ex_groups_order` (`order`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `ex_permissions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `permission_key` varchar(100) NOT NULL,
  `name` varchar(255) NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ex_permissions_key` (`permission_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `ex_group_permissions` (
  `group_id` int(10) unsigned NOT NULL,
  `permission_id` int(10) unsigned NOT NULL,
  `is_allowed` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`group_id`,`permission_id`),
  KEY `fk_ex_group_permissions_permission` (`permission_id`),
  CONSTRAINT `fk_ex_group_permissions_group` FOREIGN KEY (`group_id`) REFERENCES `ex_groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ex_group_permissions_permission` FOREIGN KEY (`permission_id`) REFERENCES `ex_permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `ex_subjects` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `subject_type` enum('person','organization','service','other') NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `legacy_id` int(10) unsigned DEFAULT NULL,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `updated_at` datetime(6) NOT NULL DEFAULT current_timestamp(6) ON UPDATE current_timestamp(6),
  PRIMARY KEY (`id`),
  KEY `ix_ex_subjects_type` (`subject_type`),
  KEY `ix_ex_subjects_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `ex_group_subject` (
  `group_id` int(10) unsigned NOT NULL,
  `subject_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`group_id`,`subject_id`) USING BTREE,
  KEY `ix_ex_group_subject_subject_id` (`subject_id`) USING BTREE,
  CONSTRAINT `fk_ex_group_subject_group` FOREIGN KEY (`group_id`) REFERENCES `ex_groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ex_group_subject_subject` FOREIGN KEY (`subject_id`) REFERENCES `ex_subjects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `ex_name_day_groups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_ex_name_day_groups_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `ex_name_days` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` int(10) unsigned NOT NULL,
  `date` char(5) NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_ex_name_days_group_date_name` (`group_id`,`date`,`name`),
  KEY `ix_ex_name_days_group_date_id` (`group_id`,`date`,`id`),
  KEY `ix_ex_name_days_name` (`name`),
  CONSTRAINT `fk_ex_name_days_group_id` FOREIGN KEY (`group_id`) REFERENCES `ex_name_day_groups` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `ex_persons` (
  `subject_id` int(10) unsigned NOT NULL,
  `title_before` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `first_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `middle_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `last_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `title_after` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `birth_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `birth_number` varchar(255) DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `death_date` date DEFAULT NULL,
  `birthday_served_at` datetime(6) DEFAULT NULL,
  `inter_served_at` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`subject_id`),
  KEY `ix_ex_persons_last_name` (`last_name`),
  KEY `ix_ex_persons_first_name` (`first_name`),
  CONSTRAINT `fk_ex_persons_subject` FOREIGN KEY (`subject_id`) REFERENCES `ex_subjects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `ex_subject_contacts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `subject_id` int(10) unsigned NOT NULL,
  `contact_id` int(10) unsigned NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `note` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `ux_ex_subject_contacts_subject_contact` (`subject_id`,`contact_id`) USING BTREE,
  KEY `ix_ex_subject_contacts_contact_id` (`contact_id`) USING BTREE,
  KEY `ix_ex_subject_contacts_subject_sort` (`subject_id`,`is_active`,`is_primary`,`id`),
  CONSTRAINT `fk_ex_subject_contacts_contact` FOREIGN KEY (`contact_id`) REFERENCES `ex_contacts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ex_subject_contacts_subject` FOREIGN KEY (`subject_id`) REFERENCES `ex_subjects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `ex_phone_book` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `subject_contact_id` int(10) unsigned NOT NULL,
  `phone_book` int(10) unsigned NOT NULL,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_ex_phone_book_book_sc` (`phone_book`,`subject_contact_id`),
  KEY `ix_ex_phone_book_sc` (`subject_contact_id`),
  CONSTRAINT `fk_ex_phone_book_sc` FOREIGN KEY (`subject_contact_id`) REFERENCES `ex_subject_contacts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `ex_subject_addresses` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `subject_id` int(10) unsigned NOT NULL,
  `address_type` enum('main','home','cottage','work','office','registered','delivery','billing','foreign','temporary','old','other') NOT NULL DEFAULT 'main',
  `organization_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `department_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `care_of` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `street_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `house_number` varchar(50) DEFAULT NULL,
  `evidence_number` varchar(50) DEFAULT NULL,
  `orientation_number` varchar(50) DEFAULT NULL,
  `orientation_suffix` varchar(50) DEFAULT NULL,
  `address_line2` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `city` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `city_part` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `postal_code` varchar(50) DEFAULT NULL,
  `region` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `country` enum('AD','AE','AF','AG','AI','AL','AM','AO','AQ','AR','AS','AT','AU','AW','AX','AZ','BA','BB','BD','BE','BF','BG','BH','BI','BJ','BL','BM','BN','BO','BQ','BR','BS','BT','BV','BW','BY','BZ','CA','CC','CD','CF','CG','CH','CI','CK','CL','CM','CN','CO','CR','CS','CU','CV','CW','CX','CY','CZ','DE','DJ','DK','DM','DO','DZ','EC','EE','EG','ER','ES','ET','FI','FJ','FK','FM','FO','FR','GA','GB','GD','GE','GF','GG','GH','GI','GL','GM','GN','GP','GQ','GR','GS','GT','GU','GW','GY','HK','HM','HN','HR','HT','HU','ID','IE','IL','IM','IN','IO','IQ','IR','IS','IT','JE','JM','JO','JP','KE','KG','KH','KI','KM','KN','KP','KR','KW','KY','KZ','LA','LB','LC','LI','LK','LR','LS','LT','LU','LV','LY','MA','MC','MD','ME','MF','MG','MH','MK','ML','MM','MN','MO','MP','MQ','MR','MS','MT','MU','MV','MW','MX','MY','MZ','NA','NC','NE','NF','NG','NI','NL','NO','NP','NR','NU','NZ','OM','PA','PE','PF','PG','PH','PK','PL','PM','PN','PR','PS','PT','PW','PY','QA','RE','RO','RS','RU','RW','SA','SB','SC','SD','SE','SG','SH','SI','SJ','SK','SL','SM','SN','SO','SR','SS','ST','SV','SX','SY','SZ','TC','TD','TF','TG','TH','TJ','TK','TL','TM','TN','TO','TR','TT','TV','TW','TZ','UA','UG','UM','US','UY','UZ','VA','VC','VE','VG','VI','VN','VU','WF','WS','YE','YT','ZA','ZM','ZW') DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `note` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `updated_at` datetime(6) NOT NULL DEFAULT current_timestamp(6) ON UPDATE current_timestamp(6),
  PRIMARY KEY (`id`),
  KEY `ix_ex_subject_addresses_city` (`city`),
  KEY `ix_ex_subject_addresses_subject_sort` (`subject_id`,`is_active`,`is_primary`,`id`),
  CONSTRAINT `fk_ex_subject_addresses_subject` FOREIGN KEY (`subject_id`) REFERENCES `ex_subjects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `ex_subject_names` (
  `subject_id` int(10) unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci NOT NULL,
  PRIMARY KEY (`subject_id`),
  KEY `ix_ex_subject_names_name` (`name`),
  CONSTRAINT `fk_ex_subject_names_subject` FOREIGN KEY (`subject_id`) REFERENCES `ex_subjects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `ex_subject_nicknames` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `subject_id` int(10) unsigned NOT NULL,
  `nickname` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci NOT NULL,
  `context` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `note` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `updated_at` datetime(6) NOT NULL DEFAULT current_timestamp(6) ON UPDATE current_timestamp(6),
  PRIMARY KEY (`id`),
  KEY `ix_ex_subject_nicknames_nickname` (`nickname`),
  KEY `ix_ex_subject_nicknames_subject_sort` (`subject_id`,`is_active`,`is_primary`,`id`),
  CONSTRAINT `fk_ex_subject_nicknames_subject` FOREIGN KEY (`subject_id`) REFERENCES `ex_subjects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `ex_subject_notes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `subject_id` int(10) unsigned NOT NULL,
  `note_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `updated_at` datetime(6) NOT NULL DEFAULT current_timestamp(6) ON UPDATE current_timestamp(6),
  PRIMARY KEY (`id`),
  KEY `ix_ex_subject_notes_subject_sort` (`subject_id`,`is_active`,`is_primary`,`id`),
  CONSTRAINT `fk_ex_subject_notes_subject` FOREIGN KEY (`subject_id`) REFERENCES `ex_subjects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `ex_users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `subject_id` int(10) unsigned NOT NULL,
  `user_name` varchar(191) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `session_timeout` int(10) unsigned NOT NULL DEFAULT 1200,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `updated_at` datetime(6) NOT NULL DEFAULT current_timestamp(6) ON UPDATE current_timestamp(6),
  `last_login_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ex_users_subject` (`subject_id`),
  UNIQUE KEY `uq_ex_users_user_name` (`user_name`),
  CONSTRAINT `fk_ex_users_subject` FOREIGN KEY (`subject_id`) REFERENCES `ex_subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `ex_user_permission` (
  `user_id` int(10) unsigned NOT NULL,
  `permission_id` int(10) unsigned NOT NULL,
  `is_allowed` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`user_id`,`permission_id`),
  KEY `fk_user_permission` (`permission_id`),
  CONSTRAINT `fk_permission_user` FOREIGN KEY (`user_id`) REFERENCES `ex_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_permission` FOREIGN KEY (`permission_id`) REFERENCES `ex_permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

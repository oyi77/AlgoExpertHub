/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.6.23-MariaDB, for Linux (x86_64)
--
-- Host: 127.0.0.1    Database: algotrad_signals
-- ------------------------------------------------------
-- Server version	10.6.23-MariaDB-cll-lve

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `crypto_payments`
--

DROP TABLE IF EXISTS `crypto_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `crypto_payments` (
  `paymentID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `boxID` bigint(20) unsigned NOT NULL DEFAULT 0,
  `boxType` enum('paymentbox','captchabox') NOT NULL,
  `orderID` varchar(50) NOT NULL DEFAULT '',
  `userID` varchar(50) NOT NULL DEFAULT '',
  `countryID` varchar(3) NOT NULL DEFAULT '',
  `coinLabel` varchar(6) NOT NULL DEFAULT '',
  `amount` double(20,8) NOT NULL DEFAULT 0.00000000,
  `amountUSD` double(20,8) NOT NULL DEFAULT 0.00000000,
  `unrecognised` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `addr` varchar(34) NOT NULL DEFAULT '',
  `txID` char(64) NOT NULL DEFAULT '',
  `txDate` datetime DEFAULT NULL,
  `txConfirmed` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `txCheckDate` datetime DEFAULT NULL,
  `processed` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `processedDate` datetime DEFAULT NULL,
  `recordCreated` datetime DEFAULT NULL,
  PRIMARY KEY (`paymentID`),
  UNIQUE KEY `key3` (`boxID`,`orderID`,`userID`,`txID`,`amount`,`addr`),
  KEY `boxID` (`boxID`),
  KEY `boxType` (`boxType`),
  KEY `userID` (`userID`),
  KEY `countryID` (`countryID`),
  KEY `orderID` (`orderID`),
  KEY `amount` (`amount`),
  KEY `amountUSD` (`amountUSD`),
  KEY `coinLabel` (`coinLabel`),
  KEY `unrecognised` (`unrecognised`),
  KEY `addr` (`addr`),
  KEY `txID` (`txID`),
  KEY `txDate` (`txDate`),
  KEY `txConfirmed` (`txConfirmed`),
  KEY `txCheckDate` (`txCheckDate`),
  KEY `processed` (`processed`),
  KEY `processedDate` (`processedDate`),
  KEY `recordCreated` (`recordCreated`),
  KEY `key1` (`boxID`,`orderID`),
  KEY `key2` (`boxID`,`orderID`,`userID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crypto_payments`
--

LOCK TABLES `crypto_payments` WRITE;
/*!40000 ALTER TABLE `crypto_payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `crypto_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_admin_password_resets`
--

DROP TABLE IF EXISTS `sp_admin_password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_admin_password_resets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `status` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_admin_password_resets`
--

LOCK TABLES `sp_admin_password_resets` WRITE;
/*!40000 ALTER TABLE `sp_admin_password_resets` DISABLE KEYS */;
/*!40000 ALTER TABLE `sp_admin_password_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_admins`
--

DROP TABLE IF EXISTS `sp_admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_admins` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sp_admins_username_unique` (`username`),
  UNIQUE KEY `sp_admins_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_admins`
--

LOCK TABLES `sp_admins` WRITE;
/*!40000 ALTER TABLE `sp_admins` DISABLE KEYS */;
INSERT INTO `sp_admins` VALUES (1,'admin','admin@demo.com',NULL,'super','$2y$10$xnhUf3FZKlHuoLXzArg5k.5tRGGFKmnByguDyMMXiZbhDH3SfSJNG',1,'LNMv6gEZySjLPuMUJ20DrtgItQgYfTQ0gjQI1WakdpGIujZdIBzp9Ev6KKXB','2025-12-04 20:57:56','2025-12-04 20:57:56');
/*!40000 ALTER TABLE `sp_admins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_ai_configurations`
--

DROP TABLE IF EXISTS `sp_ai_configurations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_ai_configurations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `provider` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `api_key` text DEFAULT NULL,
  `api_url` varchar(255) DEFAULT NULL,
  `model` varchar(255) DEFAULT NULL,
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`settings`)),
  `enabled` tinyint(1) NOT NULL DEFAULT 0,
  `migrated` tinyint(1) NOT NULL DEFAULT 0,
  `priority` int(11) NOT NULL DEFAULT 50,
  `timeout` int(11) NOT NULL DEFAULT 30,
  `temperature` double(3,2) NOT NULL DEFAULT 0.30,
  `max_tokens` int(11) NOT NULL DEFAULT 500,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sp_ai_configurations_provider_unique` (`provider`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_ai_configurations`
--

LOCK TABLES `sp_ai_configurations` WRITE;
/*!40000 ALTER TABLE `sp_ai_configurations` DISABLE KEYS */;
/*!40000 ALTER TABLE `sp_ai_configurations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_ai_connection_usage`
--

DROP TABLE IF EXISTS `sp_ai_connection_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_ai_connection_usage` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `connection_id` bigint(20) unsigned NOT NULL,
  `feature` varchar(255) NOT NULL,
  `tokens_used` int(11) NOT NULL DEFAULT 0,
  `cost` decimal(10,6) NOT NULL DEFAULT 0.000000,
  `success` tinyint(1) NOT NULL DEFAULT 1,
  `response_time_ms` int(11) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `sp_ai_connection_usage_connection_id_created_at_index` (`connection_id`,`created_at`),
  KEY `sp_ai_connection_usage_feature_created_at_index` (`feature`,`created_at`),
  KEY `sp_ai_connection_usage_success_created_at_index` (`success`,`created_at`),
  KEY `sp_ai_connection_usage_created_at_index` (`created_at`),
  CONSTRAINT `sp_ai_connection_usage_connection_id_foreign` FOREIGN KEY (`connection_id`) REFERENCES `sp_ai_connections` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_ai_connection_usage`
--

LOCK TABLES `sp_ai_connection_usage` WRITE;
/*!40000 ALTER TABLE `sp_ai_connection_usage` DISABLE KEYS */;
/*!40000 ALTER TABLE `sp_ai_connection_usage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_ai_connections`
--

DROP TABLE IF EXISTS `sp_ai_connections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_ai_connections` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `provider_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `credentials` text NOT NULL,
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`settings`)),
  `status` enum('active','inactive','error') NOT NULL DEFAULT 'active',
  `priority` int(11) NOT NULL DEFAULT 50,
  `rate_limit_per_minute` int(11) DEFAULT NULL,
  `rate_limit_per_day` int(11) DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `last_error_at` timestamp NULL DEFAULT NULL,
  `error_count` int(11) NOT NULL DEFAULT 0,
  `success_count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sp_ai_connections_provider_id_status_priority_index` (`provider_id`,`status`,`priority`),
  KEY `sp_ai_connections_status_index` (`status`),
  KEY `sp_ai_connections_priority_index` (`priority`),
  KEY `sp_ai_connections_last_used_at_index` (`last_used_at`),
  CONSTRAINT `sp_ai_connections_provider_id_foreign` FOREIGN KEY (`provider_id`) REFERENCES `sp_ai_providers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_ai_connections`
--

LOCK TABLES `sp_ai_connections` WRITE;
/*!40000 ALTER TABLE `sp_ai_connections` DISABLE KEYS */;
/*!40000 ALTER TABLE `sp_ai_connections` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_ai_model_profiles`
--

DROP TABLE IF EXISTS `sp_ai_model_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_ai_model_profiles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `visibility` enum('PRIVATE','PUBLIC_MARKETPLACE') NOT NULL DEFAULT 'PRIVATE',
  `clonable` tinyint(1) NOT NULL DEFAULT 1,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `ai_connection_id` bigint(20) unsigned DEFAULT NULL,
  `provider` varchar(255) NOT NULL,
  `model_name` varchar(255) NOT NULL,
  `api_key_ref` varchar(255) DEFAULT NULL,
  `mode` enum('CONFIRM','SCAN','POSITION_MGMT') NOT NULL DEFAULT 'CONFIRM',
  `prompt_template` text DEFAULT NULL,
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`settings`)),
  `max_calls_per_minute` int(11) DEFAULT NULL,
  `max_calls_per_day` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sp_ai_model_profiles_created_by_user_id_index` (`created_by_user_id`),
  KEY `sp_ai_model_profiles_visibility_index` (`visibility`),
  KEY `sp_ai_model_profiles_enabled_index` (`enabled`),
  KEY `sp_ai_model_profiles_provider_index` (`provider`),
  KEY `sp_ai_model_profiles_mode_index` (`mode`),
  KEY `sp_ai_model_profiles_ai_connection_id_index` (`ai_connection_id`),
  CONSTRAINT `sp_ai_model_profiles_ai_connection_id_foreign` FOREIGN KEY (`ai_connection_id`) REFERENCES `sp_ai_connections` (`id`),
  CONSTRAINT `sp_ai_model_profiles_created_by_user_id_foreign` FOREIGN KEY (`created_by_user_id`) REFERENCES `sp_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_ai_model_profiles`
--

LOCK TABLES `sp_ai_model_profiles` WRITE;
/*!40000 ALTER TABLE `sp_ai_model_profiles` DISABLE KEYS */;
/*!40000 ALTER TABLE `sp_ai_model_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_ai_parsing_profiles`
--

DROP TABLE IF EXISTS `sp_ai_parsing_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_ai_parsing_profiles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `channel_source_id` bigint(20) unsigned DEFAULT NULL,
  `ai_connection_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `parsing_prompt` text DEFAULT NULL,
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`settings`)),
  `priority` int(11) NOT NULL DEFAULT 50,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sp_ai_parsing_profiles_channel_source_id_index` (`channel_source_id`),
  KEY `sp_ai_parsing_profiles_ai_connection_id_index` (`ai_connection_id`),
  KEY `sp_ai_parsing_profiles_enabled_priority_index` (`enabled`,`priority`),
  CONSTRAINT `sp_ai_parsing_profiles_ai_connection_id_foreign` FOREIGN KEY (`ai_connection_id`) REFERENCES `sp_ai_connections` (`id`),
  CONSTRAINT `sp_ai_parsing_profiles_channel_source_id_foreign` FOREIGN KEY (`channel_source_id`) REFERENCES `sp_channel_sources` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_ai_parsing_profiles`
--

LOCK TABLES `sp_ai_parsing_profiles` WRITE;
/*!40000 ALTER TABLE `sp_ai_parsing_profiles` DISABLE KEYS */;
/*!40000 ALTER TABLE `sp_ai_parsing_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_ai_providers`
--

DROP TABLE IF EXISTS `sp_ai_providers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_ai_providers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `default_connection_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sp_ai_providers_slug_unique` (`slug`),
  KEY `sp_ai_providers_status_index` (`status`),
  KEY `sp_ai_providers_slug_index` (`slug`),
  KEY `sp_ai_providers_default_connection_id_foreign` (`default_connection_id`),
  CONSTRAINT `sp_ai_providers_default_connection_id_foreign` FOREIGN KEY (`default_connection_id`) REFERENCES `sp_ai_connections` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_ai_providers`
--

LOCK TABLES `sp_ai_providers` WRITE;
/*!40000 ALTER TABLE `sp_ai_providers` DISABLE KEYS */;
INSERT INTO `sp_ai_providers` VALUES (1,'OpenAI','openai','active',NULL,'2025-12-04 20:58:25','2025-12-04 20:58:25'),(2,'Google Gemini','gemini','active',NULL,'2025-12-04 20:58:25','2025-12-04 20:58:25'),(3,'OpenRouter','openrouter','active',NULL,'2025-12-04 20:58:25','2025-12-04 20:58:25');
/*!40000 ALTER TABLE `sp_ai_providers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_bot_templates`
--

DROP TABLE IF EXISTS `sp_bot_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_bot_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL COMMENT 'Creator (null if admin-owned)',
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` enum('grid','dca','martingale','scalping','trend_following','breakout','mean_reversion') NOT NULL DEFAULT 'scalping',
  `config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Grid levels, DCA steps, risk settings' CHECK (json_valid(`config`)),
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '0=free',
  `downloads_count` int(11) NOT NULL DEFAULT 0,
  `avg_rating` decimal(3,2) NOT NULL DEFAULT 0.00,
  `total_ratings` int(11) NOT NULL DEFAULT 0,
  `backtest_id` bigint(20) unsigned DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sp_bot_templates_user_id_foreign` (`user_id`),
  KEY `bot_tmpl_public_feat_rating_idx` (`is_public`,`is_featured`,`avg_rating`),
  KEY `bot_tmpl_category_idx` (`category`),
  KEY `bot_tmpl_downloads_idx` (`downloads_count`),
  CONSTRAINT `sp_bot_templates_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `sp_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_bot_templates`
--

LOCK TABLES `sp_bot_templates` WRITE;
/*!40000 ALTER TABLE `sp_bot_templates` DISABLE KEYS */;
/*!40000 ALTER TABLE `sp_bot_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_channel_messages`
--

DROP TABLE IF EXISTS `sp_channel_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_channel_messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `channel_source_id` bigint(20) unsigned NOT NULL,
  `raw_message` text NOT NULL,
  `message_hash` varchar(64) NOT NULL COMMENT 'SHA256 hash for duplicate detection',
  `parsed_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Parsed signal data' CHECK (json_valid(`parsed_data`)),
  `signal_id` bigint(20) unsigned DEFAULT NULL,
  `status` enum('pending','processed','failed','duplicate','manual_review') NOT NULL DEFAULT 'pending',
  `confidence_score` int(10) unsigned DEFAULT NULL COMMENT '0-100 parsing confidence',
  `error_message` text DEFAULT NULL,
  `processing_attempts` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sp_channel_messages_channel_source_id_index` (`channel_source_id`),
  KEY `sp_channel_messages_message_hash_index` (`message_hash`),
  KEY `sp_channel_messages_status_index` (`status`),
  KEY `sp_channel_messages_signal_id_index` (`signal_id`),
  KEY `sp_channel_messages_created_at_index` (`created_at`),
  CONSTRAINT `sp_channel_messages_channel_source_id_foreign` FOREIGN KEY (`channel_source_id`) REFERENCES `sp_channel_sources` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sp_channel_messages_signal_id_foreign` FOREIGN KEY (`signal_id`) REFERENCES `sp_signals` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_channel_messages`
--

LOCK TABLES `sp_channel_messages` WRITE;
/*!40000 ALTER TABLE `sp_channel_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `sp_channel_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_channel_source_plans`
--

DROP TABLE IF EXISTS `sp_channel_source_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_channel_source_plans` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `channel_source_id` bigint(20) unsigned NOT NULL,
  `plan_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sp_channel_source_plans_channel_source_id_plan_id_unique` (`channel_source_id`,`plan_id`),
  KEY `sp_channel_source_plans_channel_source_id_index` (`channel_source_id`),
  KEY `sp_channel_source_plans_plan_id_index` (`plan_id`),
  CONSTRAINT `sp_channel_source_plans_channel_source_id_foreign` FOREIGN KEY (`channel_source_id`) REFERENCES `sp_channel_sources` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sp_channel_source_plans_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `sp_plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_channel_source_plans`
--

LOCK TABLES `sp_channel_source_plans` WRITE;
/*!40000 ALTER TABLE `sp_channel_source_plans` DISABLE KEYS */;
/*!40000 ALTER TABLE `sp_channel_source_plans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_channel_source_users`
--

DROP TABLE IF EXISTS `sp_channel_source_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_channel_source_users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `channel_source_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sp_channel_source_users_channel_source_id_user_id_unique` (`channel_source_id`,`user_id`),
  KEY `sp_channel_source_users_channel_source_id_index` (`channel_source_id`),
  KEY `sp_channel_source_users_user_id_index` (`user_id`),
  CONSTRAINT `sp_channel_source_users_channel_source_id_foreign` FOREIGN KEY (`channel_source_id`) REFERENCES `sp_channel_sources` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sp_channel_source_users_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `sp_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_channel_source_users`
--

LOCK TABLES `sp_channel_source_users` WRITE;
/*!40000 ALTER TABLE `sp_channel_source_users` DISABLE KEYS */;
/*!40000 ALTER TABLE `sp_channel_source_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_channel_sources`
--

DROP TABLE IF EXISTS `sp_channel_sources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_channel_sources` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `is_admin_owned` tinyint(1) NOT NULL DEFAULT 0,
  `scope` enum('user','plan','global') DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `type` enum('telegram','telegram_mtproto','api','web_scrape','rss') NOT NULL DEFAULT 'telegram',
  `config` text DEFAULT NULL COMMENT 'Encrypted credentials, URLs, selectors, etc.',
  `status` enum('active','paused','error') NOT NULL DEFAULT 'active',
  `last_processed_at` timestamp NULL DEFAULT NULL,
  `error_count` int(10) unsigned NOT NULL DEFAULT 0,
  `last_error` text DEFAULT NULL,
  `auto_publish_confidence_threshold` int(10) unsigned NOT NULL DEFAULT 90 COMMENT '0-100, signals with confidence >= this are auto-published',
  `default_plan_id` bigint(20) unsigned DEFAULT NULL,
  `default_market_id` bigint(20) unsigned DEFAULT NULL,
  `default_timeframe_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sp_channel_sources_default_plan_id_foreign` (`default_plan_id`),
  KEY `sp_channel_sources_default_market_id_foreign` (`default_market_id`),
  KEY `sp_channel_sources_default_timeframe_id_foreign` (`default_timeframe_id`),
  KEY `sp_channel_sources_user_id_index` (`user_id`),
  KEY `sp_channel_sources_status_index` (`status`),
  KEY `sp_channel_sources_type_index` (`type`),
  CONSTRAINT `sp_channel_sources_default_market_id_foreign` FOREIGN KEY (`default_market_id`) REFERENCES `sp_markets` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sp_channel_sources_default_plan_id_foreign` FOREIGN KEY (`default_plan_id`) REFERENCES `sp_plans` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sp_channel_sources_default_timeframe_id_foreign` FOREIGN KEY (`default_timeframe_id`) REFERENCES `sp_time_frames` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sp_channel_sources_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `sp_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_channel_sources`
--

LOCK TABLES `sp_channel_sources` WRITE;
/*!40000 ALTER TABLE `sp_channel_sources` DISABLE KEYS */;
/*!40000 ALTER TABLE `sp_channel_sources` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_complete_bots`
--

DROP TABLE IF EXISTS `sp_complete_bots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_complete_bots` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `indicators_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'EMA, RSI, PSAR settings' CHECK (json_valid(`indicators_config`)),
  `entry_rules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Entry conditions' CHECK (json_valid(`entry_rules`)),
  `exit_rules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'SL/TP rules' CHECK (json_valid(`exit_rules`)),
  `risk_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Position sizing, risk %' CHECK (json_valid(`risk_config`)),
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `downloads_count` int(11) NOT NULL DEFAULT 0,
  `avg_rating` decimal(3,2) NOT NULL DEFAULT 0.00,
  `total_ratings` int(11) NOT NULL DEFAULT 0,
  `backtest_id` bigint(20) unsigned DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sp_complete_bots_user_id_foreign` (`user_id`),
  KEY `comp_bot_public_feat_rating_idx` (`is_public`,`is_featured`,`avg_rating`),
  KEY `comp_bot_downloads_idx` (`downloads_count`),
  CONSTRAINT `sp_complete_bots_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `sp_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_complete_bots`
--

LOCK TABLES `sp_complete_bots` WRITE;
/*!40000 ALTER TABLE `sp_complete_bots` DISABLE KEYS */;
/*!40000 ALTER TABLE `sp_complete_bots` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_configurations`
--

DROP TABLE IF EXISTS `sp_configurations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_configurations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `appname` varchar(255) DEFAULT NULL,
  `theme` varchar(255) DEFAULT NULL,
  `backend_theme` varchar(255) NOT NULL DEFAULT 'default',
  `currency` varchar(255) DEFAULT NULL,
  `pagination` int(11) NOT NULL DEFAULT 10,
  `number_format` int(11) NOT NULL DEFAULT 2,
  `alert` varchar(255) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `favicon` varchar(255) DEFAULT NULL,
  `reg_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `fonts` text DEFAULT NULL,
  `is_email_verification_on` tinyint(1) NOT NULL DEFAULT 0,
  `is_sms_verification_on` tinyint(1) NOT NULL DEFAULT 0,
  `preloader_image` varchar(255) DEFAULT NULL,
  `preloader_status` tinyint(1) NOT NULL DEFAULT 1,
  `analytics_status` tinyint(1) NOT NULL DEFAULT 0,
  `analytics_key` varchar(255) DEFAULT NULL,
  `allow_modal` tinyint(1) NOT NULL DEFAULT 1,
  `button_text` varchar(255) DEFAULT NULL,
  `cookie_text` varchar(255) DEFAULT NULL,
  `allow_recaptcha` tinyint(1) NOT NULL DEFAULT 0,
  `recaptcha_key` varchar(255) DEFAULT NULL,
  `recaptcha_secret` varchar(255) DEFAULT NULL,
  `tdio_allow` tinyint(1) NOT NULL DEFAULT 1,
  `tdio_url` varchar(255) DEFAULT NULL,
  `seo_tags` varchar(255) DEFAULT NULL,
  `seo_description` text DEFAULT NULL,
  `color` varchar(255) DEFAULT NULL,
  `signup_bonus` decimal(28,8) NOT NULL DEFAULT 0.00000000,
  `withdraw_limit` int(11) DEFAULT NULL,
  `kyc` text DEFAULT NULL,
  `is_allow_kyc` tinyint(1) DEFAULT NULL,
  `transfer_limit` int(11) DEFAULT NULL,
  `transfer_charge` decimal(28,8) DEFAULT 0.00000000,
  `transfer_type` varchar(255) DEFAULT NULL,
  `transfer_min_amount` decimal(28,8) DEFAULT 0.00000000,
  `transfer_max_amount` decimal(28,8) DEFAULT 0.00000000,
  `allow_facebook` tinyint(1) NOT NULL DEFAULT 0,
  `allow_google` tinyint(1) NOT NULL DEFAULT 0,
  `cron` datetime DEFAULT NULL,
  `copyright` varchar(255) DEFAULT NULL,
  `email_method` text DEFAULT NULL,
  `email_sent_from` text DEFAULT NULL,
  `email_config` text DEFAULT NULL,
  `decimal_precision` int(11) NOT NULL DEFAULT 2,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_configurations`
--

LOCK TABLES `sp_configurations` WRITE;
/*!40000 ALTER TABLE `sp_configurations` DISABLE KEYS */;
INSERT INTO `sp_configurations` VALUES (1,'AlgoExpertHub','default','default','usd',10,2,'izi','logo.png','favicon.png',1,'{\"heading_font_url\":\"https:\\/\\/fonts.googleapis.com\\/css2?family=Roboto&display=swap\",\"heading_font_family\":\"\'Roboto\',\'sans-serif\'\",\"paragraph_font_url\":\"https:\\/\\/fonts.googleapis.com\\/css2?family=Roboto&display=swap\",\"paragraph_font_family\":\"\'Roboto\',\'sans-serif\'\"}',0,0,NULL,0,0,NULL,1,'Lorem, ipsum.','Lorem ipsum dolor sit, amet consectetur adipisicing elit. Delectus, autem.',0,NULL,NULL,0,NULL,'[\"trading\",\"signals\",\"forex\",\"crypto\",\"stocks\"]','Lorem ipsum dolor sit amet consectetur adipisicing elit. Adipisci delectus deleniti temporibus quas veritatis eaque quae iste excepturi natus unde magnam nostrum, officiis tenetur ipsam ratione accusamus nulla esse ab, cumque maxime fugiat modi. Unde dolore nisi nostrum, accusamus eum perferendis distinctio molestiae quam possimus cupiditate, velit ut consequatur eius?',NULL,0.00000000,NULL,NULL,NULL,NULL,0.00000000,NULL,0.00000000,0.00000000,0,0,NULL,NULL,NULL,NULL,NULL,2,'2025-12-04 20:57:56','2025-12-04 20:57:56'),(2,'AlgoExpertHub','default','default','usd',10,2,'izi','logo.png','favicon.png',1,'{\"heading_font_url\":\"https:\\/\\/fonts.googleapis.com\\/css2?family=Roboto&display=swap\",\"heading_font_family\":\"\'Roboto\',\'sans-serif\'\",\"paragraph_font_url\":\"https:\\/\\/fonts.googleapis.com\\/css2?family=Roboto&display=swap\",\"paragraph_font_family\":\"\'Roboto\',\'sans-serif\'\"}',0,0,NULL,0,0,NULL,1,'Lorem, ipsum.','Lorem ipsum dolor sit, amet consectetur adipisicing elit. Delectus, autem.',0,NULL,NULL,0,NULL,'[\"trading\",\"signals\",\"forex\",\"crypto\",\"stocks\"]','Lorem ipsum dolor sit amet consectetur adipisicing elit. Adipisci delectus deleniti temporibus quas veritatis eaque quae iste excepturi natus unde magnam nostrum, officiis tenetur ipsam ratione accusamus nulla esse ab, cumque maxime fugiat modi. Unde dolore nisi nostrum, accusamus eum perferendis distinctio molestiae quam possimus cupiditate, velit ut consequatur eius?',NULL,0.00000000,NULL,NULL,NULL,NULL,0.00000000,NULL,0.00000000,0.00000000,0,0,NULL,NULL,NULL,NULL,NULL,2,'2025-12-04 20:58:07','2025-12-04 20:58:07'),(3,'AlgoExpertHub','default','default','usd',10,2,'izi','logo.png','favicon.png',1,'{\"heading_font_url\":\"https:\\/\\/fonts.googleapis.com\\/css2?family=Roboto&display=swap\",\"heading_font_family\":\"\'Roboto\',\'sans-serif\'\",\"paragraph_font_url\":\"https:\\/\\/fonts.googleapis.com\\/css2?family=Roboto&display=swap\",\"paragraph_font_family\":\"\'Roboto\',\'sans-serif\'\"}',0,0,NULL,0,0,NULL,1,'Lorem, ipsum.','Lorem ipsum dolor sit, amet consectetur adipisicing elit. Delectus, autem.',0,NULL,NULL,0,NULL,'[\"trading\",\"signals\",\"forex\",\"crypto\",\"stocks\"]','Lorem ipsum dolor sit amet consectetur adipisicing elit. Adipisci delectus deleniti temporibus quas veritatis eaque quae iste excepturi natus unde magnam nostrum, officiis tenetur ipsam ratione accusamus nulla esse ab, cumque maxime fugiat modi. Unde dolore nisi nostrum, accusamus eum perferendis distinctio molestiae quam possimus cupiditate, velit ut consequatur eius?',NULL,0.00000000,NULL,NULL,NULL,NULL,0.00000000,NULL,0.00000000,0.00000000,0,0,NULL,NULL,NULL,NULL,NULL,2,'2025-12-04 20:58:24','2025-12-04 20:58:24');
/*!40000 ALTER TABLE `sp_configurations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_contents`
--

DROP TABLE IF EXISTS `sp_contents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_contents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `theme` varchar(255) NOT NULL DEFAULT 'default',
  `language_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_contents`
--

LOCK TABLES `sp_contents` WRITE;
/*!40000 ALTER TABLE `sp_contents` DISABLE KEYS */;
INSERT INTO `sp_contents` VALUES (1,'non_iteratable','banner','{\"title\":\"Automate, Copy, or Lead \\u2013 Your Trading\",\"color_text_for_title\":\"Ecosystem Awaits\",\"button_text\":\"Get Started\",\"button_text_link\":\"register\",\"repeater\":[{\"repeater\":\"Let our AI engine forecast and execute across all markets\"},{\"repeater\":\"Make smarter decisions and build a profile others pay to copy\"},{\"repeater\":\"Discover seamless autotrading tailored to your risk, on any market\"}],\"image_one\":\"693264e8a02e41764910312.png\",\"image_two\":\"693264e8ac81e1764910312.png\",\"image_three\":null}','default',0,'2025-12-04 21:09:39','2025-12-04 21:51:52'),(2,'non_iteratable','about','{\"title\":\"Unlock Your Edge in Algorithmic Trading\",\"color_text_for_title\":\"AlgoExperthub\",\"button_text\":\"Launch Your Edge\",\"button_link\":\"\\/register\",\"repeater\":[{\"repeater\":\"Your Trading Signals Are Already Obsolete. Evolve Your Edge.\"},{\"repeater\":\"From Manual Trading to Automated Strategy Architect.\"},{\"repeater\":\"Trade with Conviction, Backed by AI & Institutional Tools.\"}],\"description\":\"AlgoExperthub is your all-in-one platform to automate, analyze, and amplify your trading. Leverage AI, institutional strategies, and a mastermind community.\",\"image_one\":\"6932650a780531764910346.png\",\"image_two\":\"\"}','default',0,'2025-12-04 21:09:39','2025-12-04 21:52:26'),(3,'non_iteratable','benefits','{\"section_header\":\"Summary of Benefits\",\"title\":\"Everything You Need to Fast Track Your Trading\",\"color_text_for_title\":\"Track Your Trading\",\"image_one\":\"\"}','default',0,'2025-12-04 21:09:39','2025-12-04 21:09:39'),(4,'non_iteratable','how_works','{\"section_header\":\"How it Works\",\"title\":\"Started Trading With Algoexperthub\",\"color_text_for_title\":\"With Algoexperthub\",\"image_one\":\"\"}','default',0,'2025-12-04 21:09:39','2025-12-04 21:15:48'),(5,'non_iteratable','plans','{\"section_header\":\"Packages\",\"title\":\"Our Best Packages\",\"color_text_for_title\":\"Packages\",\"image_one\":\"\"}','default',0,'2025-12-04 21:09:39','2025-12-04 21:15:48'),(6,'non_iteratable','contact','{\"section_header\":\"Contact\",\"title\":\"We\'d Love to Hear From You\",\"color_text_for_title\":\"Hear From You\",\"email\":\"support@algoexperthub.com\",\"phone\":\"+1 (800) 123-4567\",\"address\":\"Visit our office HQ, Trading Center, New York\",\"form_header\":\"Love to hear from you, Get in touch\",\"color_text_for_form_header\":\"Get in touch\",\"image_one\":\"\"}','default',0,'2025-12-04 21:09:39','2025-12-04 21:15:48'),(7,'non_iteratable','footer','{\"footer_short_details\":\"AlgoExpertHub - Advanced trading signals platform powered by AI and institutional-grade strategies.\",\"image_one\":\"\"}','default',0,'2025-12-04 21:09:39','2025-12-04 21:15:48'),(8,'non_iteratable','trade','{\"section_header\":\"Live Trading\",\"title\":\"Join the Algoexperthub community\",\"color_text_for_title\":\"Algoexperthub community\",\"button_text\":\"Start Trading\",\"button_link\":\"register\",\"image_one\":\"\"}','default',0,'2025-12-04 21:09:39','2025-12-04 21:15:48'),(9,'non_iteratable','why_choose_us','{\"section_header\":\"Choose Us\",\"title\":\"Why Choose AlgoExperthub\",\"color_text_for_title\":\"AlgoExperthub\",\"image_one\":\"\"}','default',0,'2025-12-04 21:09:39','2025-12-04 21:15:48'),(10,'non_iteratable','referral','{\"section_header\":\"Referral\",\"title\":\"Our Forex Trading Referral\",\"color_text_for_title\":\"Trading Referral\",\"image_one\":\"\"}','default',0,'2025-12-04 21:09:39','2025-12-04 21:15:48'),(11,'non_iteratable','team','{\"section_header\":\"Our Team\",\"title\":\"Our Forex Trading Specialist\",\"color_text_for_title\":\"Forex Trading\",\"image_one\":\"\"}','default',0,'2025-12-04 21:09:39','2025-12-04 21:15:48'),(12,'non_iteratable','testimonial','{\"section_header\":\"Testimonials\",\"title\":\"What Our Customer Says\",\"color_text_for_title\":\"Our Customer\",\"image_one\":\"\"}','default',0,'2025-12-04 21:09:39','2025-12-04 21:15:48'),(13,'non_iteratable','blog','{\"section_header\":\"Blog Post\",\"title\":\"Our Latest News\",\"color_text_for_title\":\"News\",\"image_one\":\"\"}','default',0,'2025-12-04 21:09:39','2025-12-04 21:15:48'),(14,'non_iteratable','auth','{\"title\":\"Welcome to AlgoExpertHub\",\"image_one\":\"\"}','default',0,'2025-12-04 21:09:39','2025-12-04 21:15:48'),(15,'iteratable','benefits','{\"title\":\"20+ Proven Trading Strategies\",\"icon\":\"fab fa-searchengin\",\"description\":\"Access a curated library of institutional-grade strategies ready to deploy or customize.\",\"image_one\":\"\"}','default',0,'2025-12-04 21:09:39','2025-12-04 21:09:39'),(16,'iteratable','benefits','{\"title\":\"VIP Insights & Direct Support\",\"icon\":\"far fa-user\",\"description\":\"Get exclusive market analysis from our pros and real-time signals via VIP Telegram groups.\",\"image_one\":\"\"}','default',0,'2025-12-04 21:09:39','2025-12-04 21:09:39'),(17,'iteratable','benefits','{\"title\":\"AI-Powered Market Forecasting\",\"icon\":\"far fa-thumbs-up\",\"description\":\"Let our advanced AI analyze sentiment, patterns, and correlations across markets.\",\"image_one\":\"\"}','default',0,'2025-12-04 21:09:39','2025-12-04 21:09:39'),(18,'iteratable','benefits','{\"title\":\"Seamless Autotrading Execution\",\"icon\":\"far fa-chart-bar\",\"description\":\"Set your rules once. Our system executes trades 24\\/7 with precision speed.\",\"image_one\":\"\"}','default',0,'2025-12-04 21:09:39','2025-12-04 21:09:39'),(19,'iteratable','benefits','{\"title\":\"Multi-Channel Alert System\",\"icon\":\"far fa-envelope\",\"description\":\"Receive critical trade signals via Telegram. Never miss a key market movement.\",\"image_one\":\"\"}','default',0,'2025-12-04 21:09:39','2025-12-04 21:09:39'),(20,'iteratable','benefits','{\"title\":\"Join a Growing Community\",\"icon\":\"fas fa-users\",\"description\":\"Connect with traders worldwide and share strategies.\",\"image_one\":\"\"}','default',0,'2025-12-04 21:09:39','2025-12-04 21:09:39'),(21,'iteratable','how_works','{\"title\":\"Create Account\",\"description\":\"Simple registration process. No credit card required for trial.\",\"image_one\":\"\"}','default',0,'2025-12-04 21:09:39','2025-12-04 21:15:48'),(22,'iteratable','how_works','{\"title\":\"Select Package\",\"description\":\"Choose your subscription plan. Flexible monthly or yearly options.\",\"image_one\":\"\"}','default',0,'2025-12-04 21:09:39','2025-12-04 21:15:48'),(23,'iteratable','how_works','{\"title\":\"Start Trading\",\"description\":\"Activate autotrading and let the algo work for you.\",\"image_one\":\"\"}','default',0,'2025-12-04 21:09:39','2025-12-04 21:15:48'),(24,'iteratable','socials','{\"icon\":\"fab fa-facebook-f\",\"link\":\"https:\\/\\/facebook.com\",\"image_one\":\"\"}','default',0,'2025-12-04 21:09:39','2025-12-04 21:15:48'),(25,'iteratable','socials','{\"icon\":\"fab fa-twitter\",\"link\":\"https:\\/\\/twitter.com\",\"image_one\":\"\"}','default',0,'2025-12-04 21:09:39','2025-12-04 21:15:48'),(26,'iteratable','socials','{\"icon\":\"fab fa-telegram-plane\",\"link\":\"https:\\/\\/t.me\\/algoexperthub\",\"image_one\":\"\"}','default',0,'2025-12-04 21:09:39','2025-12-04 21:15:48');
/*!40000 ALTER TABLE `sp_contents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_copy_trading_executions`
--

DROP TABLE IF EXISTS `sp_copy_trading_executions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_copy_trading_executions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `trader_position_id` bigint(20) unsigned NOT NULL COMMENT 'Original ExecutionPosition from trader',
  `trader_id` bigint(20) unsigned NOT NULL,
  `follower_id` bigint(20) unsigned NOT NULL,
  `subscription_id` bigint(20) unsigned NOT NULL,
  `follower_position_id` bigint(20) unsigned DEFAULT NULL COMMENT 'Created ExecutionPosition for follower',
  `follower_connection_id` bigint(20) unsigned NOT NULL,
  `copied_at` timestamp NULL DEFAULT NULL,
  `status` enum('pending','executed','failed','closed') NOT NULL DEFAULT 'pending',
  `error_message` text DEFAULT NULL,
  `risk_multiplier_used` decimal(10,4) DEFAULT NULL,
  `original_quantity` decimal(20,8) NOT NULL,
  `copied_quantity` decimal(20,8) NOT NULL,
  `calculation_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Details about how quantity was calculated' CHECK (json_valid(`calculation_details`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sp_copy_trading_executions_follower_position_id_foreign` (`follower_position_id`),
  KEY `sp_copy_trading_executions_follower_connection_id_foreign` (`follower_connection_id`),
  KEY `sp_copy_trading_executions_trader_position_id_index` (`trader_position_id`),
  KEY `sp_copy_trading_executions_trader_id_index` (`trader_id`),
  KEY `sp_copy_trading_executions_follower_id_index` (`follower_id`),
  KEY `sp_copy_trading_executions_subscription_id_index` (`subscription_id`),
  KEY `sp_copy_trading_executions_status_index` (`status`),
  CONSTRAINT `sp_copy_trading_executions_follower_connection_id_foreign` FOREIGN KEY (`follower_connection_id`) REFERENCES `sp_execution_connections` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sp_copy_trading_executions_follower_id_foreign` FOREIGN KEY (`follower_id`) REFERENCES `sp_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sp_copy_trading_executions_follower_position_id_foreign` FOREIGN KEY (`follower_position_id`) REFERENCES `sp_execution_positions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sp_copy_trading_executions_subscription_id_foreign` FOREIGN KEY (`subscription_id`) REFERENCES `sp_copy_trading_subscriptions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sp_copy_trading_executions_trader_id_foreign` FOREIGN KEY (`trader_id`) REFERENCES `sp_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sp_copy_trading_executions_trader_position_id_foreign` FOREIGN KEY (`trader_position_id`) REFERENCES `sp_execution_positions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_copy_trading_executions`
--

LOCK TABLES `sp_copy_trading_executions` WRITE;
/*!40000 ALTER TABLE `sp_copy_trading_executions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sp_copy_trading_executions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_copy_trading_settings`
--

DROP TABLE IF EXISTS `sp_copy_trading_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_copy_trading_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `admin_id` bigint(20) unsigned DEFAULT NULL,
  `is_admin_owned` tinyint(1) NOT NULL DEFAULT 0,
  `is_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `min_followers_balance` decimal(20,8) DEFAULT NULL COMMENT 'Minimum balance required to follow',
  `max_copiers` int(11) DEFAULT NULL COMMENT 'Max number of followers allowed',
  `risk_multiplier_default` decimal(10,4) NOT NULL DEFAULT 1.0000 COMMENT 'Default risk multiplier for followers',
  `allow_manual_trades` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Whether to copy manual trades',
  `allow_auto_trades` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Whether to copy signal-based trades',
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Additional settings' CHECK (json_valid(`settings`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `copy_trading_settings_user_id_unique` (`user_id`),
  UNIQUE KEY `copy_trading_settings_admin_id_unique` (`admin_id`),
  KEY `sp_copy_trading_settings_is_enabled_index` (`is_enabled`),
  KEY `sp_copy_trading_settings_admin_id_index` (`admin_id`),
  KEY `sp_copy_trading_settings_is_admin_owned_index` (`is_admin_owned`),
  CONSTRAINT `sp_copy_trading_settings_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `sp_admins` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sp_copy_trading_settings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `sp_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_copy_trading_settings`
--

LOCK TABLES `sp_copy_trading_settings` WRITE;
/*!40000 ALTER TABLE `sp_copy_trading_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `sp_copy_trading_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_copy_trading_subscriptions`
--

DROP TABLE IF EXISTS `sp_copy_trading_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_copy_trading_subscriptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `trader_id` bigint(20) unsigned NOT NULL COMMENT 'User who is being copied',
  `follower_id` bigint(20) unsigned NOT NULL COMMENT 'User who is copying',
  `copy_mode` enum('easy','advanced') NOT NULL DEFAULT 'easy' COMMENT 'Copy trading mode',
  `risk_multiplier` decimal(10,4) NOT NULL DEFAULT 1.0000 COMMENT 'Position size multiplier for easy mode (0.1 to 10.0)',
  `max_position_size` decimal(20,8) DEFAULT NULL COMMENT 'Max USD per copied trade',
  `connection_id` bigint(20) unsigned NOT NULL COMMENT 'Which ExecutionConnection to use for copying',
  `copy_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Advanced mode settings: method, percentage, fixed_quantity, min_quantity, max_quantity' CHECK (json_valid(`copy_settings`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `subscribed_at` timestamp NULL DEFAULT NULL,
  `unsubscribed_at` timestamp NULL DEFAULT NULL,
  `stats` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Copied trades count, total PnL, etc.' CHECK (json_valid(`stats`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sp_copy_trading_subscriptions_trader_id_follower_id_unique` (`trader_id`,`follower_id`),
  KEY `sp_copy_trading_subscriptions_connection_id_foreign` (`connection_id`),
  KEY `sp_copy_trading_subscriptions_trader_id_index` (`trader_id`),
  KEY `sp_copy_trading_subscriptions_follower_id_index` (`follower_id`),
  KEY `sp_copy_trading_subscriptions_is_active_index` (`is_active`),
  KEY `sp_copy_trading_subscriptions_copy_mode_index` (`copy_mode`),
  CONSTRAINT `sp_copy_trading_subscriptions_connection_id_foreign` FOREIGN KEY (`connection_id`) REFERENCES `sp_execution_connections` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sp_copy_trading_subscriptions_follower_id_foreign` FOREIGN KEY (`follower_id`) REFERENCES `sp_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sp_copy_trading_subscriptions_trader_id_foreign` FOREIGN KEY (`trader_id`) REFERENCES `sp_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_copy_trading_subscriptions`
--

LOCK TABLES `sp_copy_trading_subscriptions` WRITE;
/*!40000 ALTER TABLE `sp_copy_trading_subscriptions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sp_copy_trading_subscriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_currency_pairs`
--

DROP TABLE IF EXISTS `sp_currency_pairs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_currency_pairs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `status` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sp_currency_pairs_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_currency_pairs`
--

LOCK TABLES `sp_currency_pairs` WRITE;
/*!40000 ALTER TABLE `sp_currency_pairs` DISABLE KEYS */;
INSERT INTO `sp_currency_pairs` VALUES (1,'EUR/USD',1,'2025-12-04 20:57:57','2025-12-04 20:57:57'),(2,'GBP/USD',1,'2025-12-04 20:57:57','2025-12-04 20:57:57'),(3,'USD/JPY',1,'2025-12-04 20:57:57','2025-12-04 20:57:57'),(4,'AUD/USD',1,'2025-12-04 20:57:57','2025-12-04 20:57:57'),(5,'USD/CHF',1,'2025-12-04 20:57:57','2025-12-04 20:57:57'),(6,'USD/CAD',1,'2025-12-04 20:57:57','2025-12-04 20:57:57'),(7,'NZD/USD',1,'2025-12-04 20:57:57','2025-12-04 20:57:57'),(8,'EUR/GBP',1,'2025-12-04 20:57:57','2025-12-04 20:57:57'),(9,'EUR/JPY',1,'2025-12-04 20:57:57','2025-12-04 20:57:57'),(10,'GBP/JPY',1,'2025-12-04 20:57:57','2025-12-04 20:57:57'),(11,'AUD/JPY',1,'2025-12-04 20:57:57','2025-12-04 20:57:57'),(12,'EUR/AUD',1,'2025-12-04 20:57:57','2025-12-04 20:57:57'),(13,'BTC/USDT',1,'2025-12-04 20:57:57','2025-12-04 20:57:57'),(14,'ETH/USDT',1,'2025-12-04 20:57:57','2025-12-04 20:57:57'),(15,'BNB/USDT',1,'2025-12-04 20:57:57','2025-12-04 20:57:57'),(16,'XRP/USDT',1,'2025-12-04 20:57:57','2025-12-04 20:57:57'),(17,'ADA/USDT',1,'2025-12-04 20:57:57','2025-12-04 20:57:57'),(18,'SOL/USDT',1,'2025-12-04 20:57:57','2025-12-04 20:57:57'),(19,'DOT/USDT',1,'2025-12-04 20:57:57','2025-12-04 20:57:57'),(20,'XAU/USD',1,'2025-12-04 20:57:57','2025-12-04 20:57:57'),(21,'XAG/USD',1,'2025-12-04 20:57:57','2025-12-04 20:57:57'),(22,'WTI/USD',1,'2025-12-04 20:57:57','2025-12-04 20:57:57'),(23,'BRENT/USD',1,'2025-12-04 20:57:57','2025-12-04 20:57:57'),(24,'US30',1,'2025-12-04 20:57:57','2025-12-04 20:57:57'),(25,'US100',1,'2025-12-04 20:57:57','2025-12-04 20:57:57'),(26,'US500',1,'2025-12-04 20:57:57','2025-12-04 20:57:57'),(27,'UK100',1,'2025-12-04 20:57:57','2025-12-04 20:57:57'),(28,'GER40',1,'2025-12-04 20:57:57','2025-12-04 20:57:57'),(29,'JPN225',1,'2025-12-04 20:57:57','2025-12-04 20:57:57');
/*!40000 ALTER TABLE `sp_currency_pairs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_dashboard_signals`
--

DROP TABLE IF EXISTS `sp_dashboard_signals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_dashboard_signals` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `plan_id` bigint(20) unsigned NOT NULL,
  `signal_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sp_dashboard_signals_user_id_signal_id_unique` (`user_id`,`signal_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_dashboard_signals`
--

LOCK TABLES `sp_dashboard_signals` WRITE;
/*!40000 ALTER TABLE `sp_dashboard_signals` DISABLE KEYS */;
/*!40000 ALTER TABLE `sp_dashboard_signals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_deposits`
--

DROP TABLE IF EXISTS `sp_deposits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_deposits` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `gateway_id` bigint(20) unsigned NOT NULL,
  `trx` varchar(255) NOT NULL,
  `amount` decimal(28,8) NOT NULL DEFAULT 0.00000000,
  `rate` decimal(28,8) NOT NULL DEFAULT 0.00000000,
  `charge` decimal(28,8) NOT NULL DEFAULT 0.00000000,
  `total` decimal(28,8) NOT NULL DEFAULT 0.00000000,
  `status` int(11) NOT NULL COMMENT '1 => approved,2 => pending,3 => rejected',
  `type` int(11) NOT NULL DEFAULT 1 COMMENT '0=>manual , 1 => autometic',
  `payment_proof` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sp_deposits_trx_unique` (`trx`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_deposits`
--

LOCK TABLES `sp_deposits` WRITE;
/*!40000 ALTER TABLE `sp_deposits` DISABLE KEYS */;
/*!40000 ALTER TABLE `sp_deposits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_execution_analytics`
--

DROP TABLE IF EXISTS `sp_execution_analytics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_execution_analytics` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `connection_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL COMMENT 'For user analytics',
  `admin_id` bigint(20) unsigned DEFAULT NULL COMMENT 'For admin analytics',
  `date` date NOT NULL,
  `total_trades` int(10) unsigned NOT NULL DEFAULT 0,
  `winning_trades` int(10) unsigned NOT NULL DEFAULT 0,
  `losing_trades` int(10) unsigned NOT NULL DEFAULT 0,
  `total_pnl` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `win_rate` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Percentage',
  `profit_factor` decimal(10,4) NOT NULL DEFAULT 0.0000,
  `max_drawdown` decimal(10,4) NOT NULL DEFAULT 0.0000 COMMENT 'Percentage',
  `balance` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `equity` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `additional_metrics` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Sharpe ratio, expectancy, etc.' CHECK (json_valid(`additional_metrics`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sp_execution_analytics_connection_id_date_unique` (`connection_id`,`date`),
  KEY `sp_execution_analytics_user_id_index` (`user_id`),
  KEY `sp_execution_analytics_admin_id_index` (`admin_id`),
  KEY `sp_execution_analytics_date_index` (`date`),
  CONSTRAINT `sp_execution_analytics_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `sp_admins` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sp_execution_analytics_connection_id_foreign` FOREIGN KEY (`connection_id`) REFERENCES `sp_execution_connections` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sp_execution_analytics_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `sp_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_execution_analytics`
--

LOCK TABLES `sp_execution_analytics` WRITE;
/*!40000 ALTER TABLE `sp_execution_analytics` DISABLE KEYS */;
/*!40000 ALTER TABLE `sp_execution_analytics` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_execution_connections`
--

DROP TABLE IF EXISTS `sp_execution_connections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_execution_connections` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL COMMENT 'For user-owned connections',
  `admin_id` bigint(20) unsigned DEFAULT NULL COMMENT 'For admin-owned connections',
  `name` varchar(255) NOT NULL,
  `type` enum('crypto','fx') NOT NULL DEFAULT 'crypto',
  `exchange_name` varchar(255) NOT NULL COMMENT 'Exchange/broker identifier (e.g., binance, mt4_account_123)',
  `credentials` text NOT NULL COMMENT 'Encrypted API keys, tokens, etc.',
  `status` enum('active','inactive','error','testing') NOT NULL DEFAULT 'inactive',
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `is_admin_owned` tinyint(1) NOT NULL DEFAULT 0,
  `last_error` text DEFAULT NULL,
  `last_tested_at` timestamp NULL DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Position sizing, risk limits, etc.' CHECK (json_valid(`settings`)),
  `preset_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sp_execution_connections_user_id_index` (`user_id`),
  KEY `sp_execution_connections_admin_id_index` (`admin_id`),
  KEY `sp_execution_connections_type_index` (`type`),
  KEY `sp_execution_connections_status_index` (`status`),
  KEY `sp_execution_connections_is_active_index` (`is_active`),
  KEY `sp_execution_connections_preset_id_index` (`preset_id`),
  CONSTRAINT `sp_execution_connections_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `sp_admins` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sp_execution_connections_preset_id_foreign` FOREIGN KEY (`preset_id`) REFERENCES `sp_trading_presets` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sp_execution_connections_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `sp_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_execution_connections`
--

LOCK TABLES `sp_execution_connections` WRITE;
/*!40000 ALTER TABLE `sp_execution_connections` DISABLE KEYS */;
/*!40000 ALTER TABLE `sp_execution_connections` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_execution_logs`
--

DROP TABLE IF EXISTS `sp_execution_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_execution_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `signal_id` bigint(20) unsigned NOT NULL,
  `connection_id` bigint(20) unsigned NOT NULL,
  `execution_type` enum('market','limit') NOT NULL DEFAULT 'market',
  `order_id` varchar(255) DEFAULT NULL COMMENT 'Exchange/broker order ID',
  `symbol` varchar(255) NOT NULL,
  `direction` enum('buy','sell') NOT NULL,
  `quantity` decimal(20,8) NOT NULL,
  `entry_price` decimal(20,8) DEFAULT NULL,
  `slippage` decimal(8,4) DEFAULT NULL COMMENT 'Actual slippage in pips',
  `latency_ms` int(10) unsigned DEFAULT NULL COMMENT 'Time from signal received to execution',
  `market_atr` decimal(10,4) DEFAULT NULL COMMENT 'ATR value at execution time',
  `trading_session` enum('TOKYO','LONDON','NEW_YORK','ASIAN','OVERLAP') DEFAULT NULL COMMENT 'Trading session',
  `day_of_week` tinyint(4) DEFAULT NULL COMMENT 'Day of week 1-7',
  `volatility_index` decimal(8,4) DEFAULT NULL COMMENT 'Calculated volatility metric',
  `signal_provider_id` varchar(255) DEFAULT NULL COMMENT 'channel_source_id or user_id',
  `signal_provider_type` enum('channel_source','user') DEFAULT NULL COMMENT 'Signal provider type',
  `sl_price` decimal(20,8) DEFAULT NULL,
  `tp_price` decimal(20,8) DEFAULT NULL,
  `status` enum('pending','executed','failed','cancelled','partial') NOT NULL DEFAULT 'pending',
  `executed_at` timestamp NULL DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `response_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Raw response from exchange/broker' CHECK (json_valid(`response_data`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sp_execution_logs_signal_id_index` (`signal_id`),
  KEY `sp_execution_logs_connection_id_index` (`connection_id`),
  KEY `sp_execution_logs_status_index` (`status`),
  KEY `sp_execution_logs_order_id_index` (`order_id`),
  KEY `sp_execution_logs_executed_at_index` (`executed_at`),
  CONSTRAINT `sp_execution_logs_connection_id_foreign` FOREIGN KEY (`connection_id`) REFERENCES `sp_execution_connections` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sp_execution_logs_signal_id_foreign` FOREIGN KEY (`signal_id`) REFERENCES `sp_signals` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_execution_logs`
--

LOCK TABLES `sp_execution_logs` WRITE;
/*!40000 ALTER TABLE `sp_execution_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `sp_execution_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_execution_notifications`
--

DROP TABLE IF EXISTS `sp_execution_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_execution_notifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL COMMENT 'For user notifications',
  `admin_id` bigint(20) unsigned DEFAULT NULL COMMENT 'For admin notifications',
  `connection_id` bigint(20) unsigned NOT NULL,
  `signal_id` bigint(20) unsigned DEFAULT NULL,
  `position_id` bigint(20) unsigned DEFAULT NULL,
  `type` enum('execution','open','close','error','sl_hit','tp_hit','liquidation') NOT NULL DEFAULT 'execution',
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Additional data' CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sp_execution_notifications_signal_id_foreign` (`signal_id`),
  KEY `sp_execution_notifications_position_id_foreign` (`position_id`),
  KEY `sp_execution_notifications_user_id_index` (`user_id`),
  KEY `sp_execution_notifications_admin_id_index` (`admin_id`),
  KEY `sp_execution_notifications_connection_id_index` (`connection_id`),
  KEY `sp_execution_notifications_is_read_index` (`is_read`),
  KEY `sp_execution_notifications_type_index` (`type`),
  KEY `sp_execution_notifications_created_at_index` (`created_at`),
  CONSTRAINT `sp_execution_notifications_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `sp_admins` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sp_execution_notifications_connection_id_foreign` FOREIGN KEY (`connection_id`) REFERENCES `sp_execution_connections` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sp_execution_notifications_position_id_foreign` FOREIGN KEY (`position_id`) REFERENCES `sp_execution_positions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sp_execution_notifications_signal_id_foreign` FOREIGN KEY (`signal_id`) REFERENCES `sp_signals` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sp_execution_notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `sp_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_execution_notifications`
--

LOCK TABLES `sp_execution_notifications` WRITE;
/*!40000 ALTER TABLE `sp_execution_notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `sp_execution_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_execution_positions`
--

DROP TABLE IF EXISTS `sp_execution_positions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_execution_positions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `signal_id` bigint(20) unsigned NOT NULL,
  `connection_id` bigint(20) unsigned NOT NULL,
  `execution_log_id` bigint(20) unsigned NOT NULL,
  `order_id` varchar(255) DEFAULT NULL COMMENT 'Exchange/broker order ID',
  `symbol` varchar(255) NOT NULL,
  `direction` enum('buy','sell') NOT NULL,
  `quantity` decimal(20,8) NOT NULL,
  `srm_adjusted_lot` decimal(10,4) DEFAULT NULL COMMENT 'SRM-adjusted lot size',
  `entry_price` decimal(20,8) NOT NULL,
  `predicted_slippage` decimal(8,4) DEFAULT NULL COMMENT 'Predicted slippage at entry',
  `performance_score_at_entry` decimal(5,2) DEFAULT NULL COMMENT 'SP performance score at entry',
  `current_price` decimal(20,8) DEFAULT NULL,
  `sl_price` decimal(20,8) DEFAULT NULL,
  `srm_sl_buffer` decimal(8,4) DEFAULT NULL COMMENT 'SRM-added SL buffer in pips',
  `srm_adjustment_reason` text DEFAULT NULL COMMENT 'Reason for SRM adjustment (JSON)',
  `tp_price` decimal(20,8) DEFAULT NULL,
  `tp1_price` decimal(20,8) DEFAULT NULL,
  `status` enum('open','closed','liquidated') NOT NULL DEFAULT 'open',
  `pnl` decimal(20,8) NOT NULL DEFAULT 0.00000000 COMMENT 'Profit/Loss',
  `pnl_percentage` decimal(10,4) NOT NULL DEFAULT 0.0000 COMMENT 'PnL as percentage',
  `closed_at` timestamp NULL DEFAULT NULL,
  `closed_reason` enum('tp','sl','manual','liquidation') DEFAULT NULL,
  `last_price_update_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `tp2_price` decimal(20,8) DEFAULT NULL,
  `tp3_price` decimal(20,8) DEFAULT NULL,
  `tp1_close_pct` decimal(5,2) DEFAULT NULL COMMENT 'Percentage to close at TP1 (0-100)',
  `tp2_close_pct` decimal(5,2) DEFAULT NULL,
  `tp3_close_pct` decimal(5,2) DEFAULT NULL,
  `tp1_closed_at` timestamp NULL DEFAULT NULL,
  `tp2_closed_at` timestamp NULL DEFAULT NULL,
  `tp3_closed_at` timestamp NULL DEFAULT NULL,
  `tp1_closed_qty` decimal(20,8) DEFAULT NULL COMMENT 'Quantity closed at TP1',
  `tp2_closed_qty` decimal(20,8) DEFAULT NULL,
  `tp3_closed_qty` decimal(20,8) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sp_execution_positions_signal_id_index` (`signal_id`),
  KEY `sp_execution_positions_connection_id_index` (`connection_id`),
  KEY `sp_execution_positions_execution_log_id_index` (`execution_log_id`),
  KEY `sp_execution_positions_status_index` (`status`),
  KEY `sp_execution_positions_order_id_index` (`order_id`),
  KEY `sp_execution_positions_closed_at_index` (`closed_at`),
  CONSTRAINT `sp_execution_positions_connection_id_foreign` FOREIGN KEY (`connection_id`) REFERENCES `sp_execution_connections` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sp_execution_positions_execution_log_id_foreign` FOREIGN KEY (`execution_log_id`) REFERENCES `sp_execution_logs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sp_execution_positions_signal_id_foreign` FOREIGN KEY (`signal_id`) REFERENCES `sp_signals` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_execution_positions`
--

LOCK TABLES `sp_execution_positions` WRITE;
/*!40000 ALTER TABLE `sp_execution_positions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sp_execution_positions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_filter_strategies`
--

DROP TABLE IF EXISTS `sp_filter_strategies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_filter_strategies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `visibility` enum('PRIVATE','PUBLIC_MARKETPLACE') NOT NULL DEFAULT 'PRIVATE',
  `clonable` tinyint(1) NOT NULL DEFAULT 1,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`config`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sp_filter_strategies_created_by_user_id_index` (`created_by_user_id`),
  KEY `sp_filter_strategies_visibility_index` (`visibility`),
  KEY `sp_filter_strategies_enabled_index` (`enabled`),
  KEY `sp_filter_strategies_clonable_index` (`clonable`),
  CONSTRAINT `sp_filter_strategies_created_by_user_id_foreign` FOREIGN KEY (`created_by_user_id`) REFERENCES `sp_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_filter_strategies`
--

LOCK TABLES `sp_filter_strategies` WRITE;
/*!40000 ALTER TABLE `sp_filter_strategies` DISABLE KEYS */;
/*!40000 ALTER TABLE `sp_filter_strategies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_frontend_components`
--

DROP TABLE IF EXISTS `sp_frontend_components`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_frontend_components` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `builder` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_frontend_components`
--

LOCK TABLES `sp_frontend_components` WRITE;
/*!40000 ALTER TABLE `sp_frontend_components` DISABLE KEYS */;
/*!40000 ALTER TABLE `sp_frontend_components` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_frontend_media`
--

DROP TABLE IF EXISTS `sp_frontend_media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_frontend_media` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `content_id` bigint(20) unsigned NOT NULL,
  `media` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_frontend_media`
--

LOCK TABLES `sp_frontend_media` WRITE;
/*!40000 ALTER TABLE `sp_frontend_media` DISABLE KEYS */;
/*!40000 ALTER TABLE `sp_frontend_media` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_gateways`
--

DROP TABLE IF EXISTS `sp_gateways`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_gateways` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `parameter` text DEFAULT NULL,
  `type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '0=manual, 1=autometic',
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `rate` decimal(28,8) NOT NULL DEFAULT 1.00000000,
  `charge` decimal(28,8) NOT NULL DEFAULT 0.00000000,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sp_gateways_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_gateways`
--

LOCK TABLES `sp_gateways` WRITE;
/*!40000 ALTER TABLE `sp_gateways` DISABLE KEYS */;
INSERT INTO `sp_gateways` VALUES (1,'stripe',NULL,NULL,1,1,1.00000000,0.00000000,'2025-12-04 20:58:24','2025-12-04 20:58:24'),(2,'paypal',NULL,NULL,1,1,1.00000000,0.00000000,'2025-12-04 20:58:24','2025-12-04 20:58:24'),(3,'vougepay',NULL,NULL,1,1,1.00000000,0.00000000,'2025-12-04 20:58:24','2025-12-04 20:58:24'),(4,'razorpay',NULL,NULL,1,1,1.00000000,0.00000000,'2025-12-04 20:58:24','2025-12-04 20:58:24'),(5,'coinpayments',NULL,NULL,1,1,1.00000000,0.00000000,'2025-12-04 20:58:24','2025-12-04 20:58:24'),(6,'mollie',NULL,NULL,1,1,1.00000000,0.00000000,'2025-12-04 20:58:24','2025-12-04 20:58:24'),(7,'nowpayments',NULL,NULL,1,1,1.00000000,0.00000000,'2025-12-04 20:58:24','2025-12-04 20:58:24'),(8,'flutterwave',NULL,NULL,1,1,1.00000000,0.00000000,'2025-12-04 20:58:24','2025-12-04 20:58:24'),(9,'paystack',NULL,NULL,1,1,1.00000000,0.00000000,'2025-12-04 20:58:24','2025-12-04 20:58:24'),(10,'paghiper',NULL,NULL,1,1,1.00000000,0.00000000,'2025-12-04 20:58:24','2025-12-04 20:58:24'),(11,'gourl_BTC',NULL,NULL,1,1,1.00000000,0.00000000,'2025-12-04 20:58:24','2025-12-04 20:58:24'),(12,'perfectmoney',NULL,NULL,1,1,1.00000000,0.00000000,'2025-12-04 20:58:24','2025-12-04 20:58:24'),(13,'mercadopago',NULL,NULL,1,1,1.00000000,0.00000000,'2025-12-04 20:58:24','2025-12-04 20:58:24'),(14,'paytm',NULL,NULL,1,1,1.00000000,0.00000000,'2025-12-04 20:58:24','2025-12-04 20:58:24');
/*!40000 ALTER TABLE `sp_gateways` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_jobs`
--

DROP TABLE IF EXISTS `sp_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sp_jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_jobs`
--

LOCK TABLES `sp_jobs` WRITE;
/*!40000 ALTER TABLE `sp_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `sp_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_languages`
--

DROP TABLE IF EXISTS `sp_languages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_languages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `status` int(11) NOT NULL COMMENT '0=>default,1=>changeable',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sp_languages_name_unique` (`name`),
  UNIQUE KEY `sp_languages_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_languages`
--

LOCK TABLES `sp_languages` WRITE;
/*!40000 ALTER TABLE `sp_languages` DISABLE KEYS */;
INSERT INTO `sp_languages` VALUES (1,'English','en',0,'2025-12-04 20:57:57','2025-12-04 20:57:57'),(2,'Spanish','es',1,'2025-12-04 20:57:57','2025-12-04 20:57:57'),(3,'Indonesia','id',1,'2025-12-04 20:57:57','2025-12-04 20:57:57'),(4,'French','fr',1,'2025-12-04 20:57:57','2025-12-04 20:57:57'),(5,'German','de',1,'2025-12-04 20:57:57','2025-12-04 20:57:57'),(6,'Chinese','zh',1,'2025-12-04 20:57:57','2025-12-04 20:57:57'),(7,'Japanese','ja',1,'2025-12-04 20:57:57','2025-12-04 20:57:57'),(8,'Arabic','ar',1,'2025-12-04 20:57:57','2025-12-04 20:57:57');
/*!40000 ALTER TABLE `sp_languages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_login_securities`
--

DROP TABLE IF EXISTS `sp_login_securities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_login_securities` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `google2fa_enable` tinyint(1) NOT NULL,
  `google2fa_secret` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_login_securities`
--

LOCK TABLES `sp_login_securities` WRITE;
/*!40000 ALTER TABLE `sp_login_securities` DISABLE KEYS */;
/*!40000 ALTER TABLE `sp_login_securities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_market_data_subscriptions`
--

DROP TABLE IF EXISTS `sp_market_data_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_market_data_subscriptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `symbol` varchar(50) NOT NULL,
  `timeframe` enum('M1','M5','M15','M30','H1','H4','D1','W1','MN') NOT NULL,
  `subscriber_type` enum('bot','user','backtest','system') NOT NULL DEFAULT 'user',
  `subscriber_id` bigint(20) unsigned NOT NULL,
  `last_access` timestamp NULL DEFAULT NULL,
  `access_count` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `mkt_data_sub_sym_tf_active_idx` (`symbol`,`timeframe`,`is_active`),
  KEY `mkt_data_sub_type_id_idx` (`subscriber_type`,`subscriber_id`),
  KEY `mkt_data_sub_access_idx` (`last_access`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_market_data_subscriptions`
--

LOCK TABLES `sp_market_data_subscriptions` WRITE;
/*!40000 ALTER TABLE `sp_market_data_subscriptions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sp_market_data_subscriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_markets`
--

DROP TABLE IF EXISTS `sp_markets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_markets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `status` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sp_markets_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_markets`
--

LOCK TABLES `sp_markets` WRITE;
/*!40000 ALTER TABLE `sp_markets` DISABLE KEYS */;
INSERT INTO `sp_markets` VALUES (1,'Forex',1,'2025-12-04 20:57:58','2025-12-04 20:57:58'),(2,'Crypto',1,'2025-12-04 20:57:58','2025-12-04 20:57:58'),(3,'Stocks',1,'2025-12-04 20:57:58','2025-12-04 20:57:58'),(4,'Commodities',1,'2025-12-04 20:57:58','2025-12-04 20:57:58'),(5,'Indices',1,'2025-12-04 20:57:58','2025-12-04 20:57:58'),(6,'Futures',1,'2025-12-04 20:57:58','2025-12-04 20:57:58'),(7,'Options',1,'2025-12-04 20:57:58','2025-12-04 20:57:58');
/*!40000 ALTER TABLE `sp_markets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_message_parsing_patterns`
--

DROP TABLE IF EXISTS `sp_message_parsing_patterns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_message_parsing_patterns` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `channel_source_id` bigint(20) unsigned DEFAULT NULL COMMENT 'NULL for global patterns',
  `user_id` bigint(20) unsigned DEFAULT NULL COMMENT 'NULL for admin-created global patterns',
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `pattern_type` enum('regex','template','ai_fallback') NOT NULL DEFAULT 'regex',
  `pattern_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Pattern definitions, field mappings, regex rules' CHECK (json_valid(`pattern_config`)),
  `priority` int(11) NOT NULL DEFAULT 0 COMMENT 'Higher priority tried first',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `success_count` int(11) NOT NULL DEFAULT 0 COMMENT 'Number of successful parses',
  `failure_count` int(11) NOT NULL DEFAULT 0 COMMENT 'Number of failed parses',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sp_message_parsing_patterns_channel_source_id_index` (`channel_source_id`),
  KEY `sp_message_parsing_patterns_user_id_index` (`user_id`),
  KEY `sp_message_parsing_patterns_priority_index` (`priority`),
  KEY `sp_message_parsing_patterns_is_active_index` (`is_active`),
  CONSTRAINT `sp_message_parsing_patterns_channel_source_id_foreign` FOREIGN KEY (`channel_source_id`) REFERENCES `sp_channel_sources` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sp_message_parsing_patterns_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `sp_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_message_parsing_patterns`
--

LOCK TABLES `sp_message_parsing_patterns` WRITE;
/*!40000 ALTER TABLE `sp_message_parsing_patterns` DISABLE KEYS */;
INSERT INTO `sp_message_parsing_patterns` VALUES (1,NULL,NULL,'Forex Auto Format','Format: buy USA100 q=0.01 tt=0.46% td=0.46%','regex','{\"required_fields\":[\"currency_pair\",\"direction\"],\"patterns\":{\"direction\":[\"\\/(?:^|\\\\s)(buy|sell)(?:\\\\s|$)\\/i\"],\"symbol\":[\"\\/(?:^|\\\\s)([A-Z0-9]{2,10})(?:\\\\s|q=)\\/i\",\"\\/(?:buy|sell)\\\\s+([A-Z0-9]{2,10})(?:\\\\s|q=)\\/i\"],\"currency_pair\":[\"\\/(?:buy|sell)\\\\s+([A-Z0-9]{2,10})(?:\\\\s|q=)\\/i\"],\"tp\":[\"\\/tt\\\\s*=\\\\s*([\\\\d.]+)\\\\s*%\\/i\",\"\\/tp\\\\s*=\\\\s*([\\\\d.]+)\\\\s*%\\/i\"],\"sl\":[\"\\/td\\\\s*=\\\\s*([\\\\d.]+)\\\\s*%\\/i\",\"\\/sl\\\\s*=\\\\s*([\\\\d.]+)\\\\s*%\\/i\"]},\"confidence_weights\":{\"currency_pair\":20,\"symbol\":20,\"direction\":20,\"tp\":20,\"sl\":20}}',80,1,0,0,'2025-12-04 20:58:25','2025-12-04 20:58:25'),(2,NULL,NULL,'Gold Multi-TP Format','Format: Gold SELL Limit, TP1/TP2/TP3/TP MAX, entry range','regex','{\"required_fields\":[\"currency_pair\",\"direction\"],\"patterns\":{\"direction\":[\"\\/(?:^|\\\\s)(Gold|XAU|GOLD)\\\\s+(BUY|SELL|LONG|SHORT)\\/i\",\"\\/(BUY|SELL|LONG|SHORT)\\\\s+(?:Limit|Market)\\/i\"],\"symbol\":[\"\\/(Gold|XAU|GOLD)\\\\s+(?:BUY|SELL|LONG|SHORT)\\/i\",\"\\/(?:^|\\\\s)(Gold|XAU|GOLD)(?:\\\\s|$)\\/i\"],\"currency_pair\":[\"\\/(Gold|XAU|GOLD)\\\\s+(?:BUY|SELL|LONG|SHORT)\\/i\"],\"open_price\":[\"\\/(?:^|\\\\n)\\\\s*(\\\\d{3,5}\\\\.?\\\\d*)\\\\s*-\\\\s*(\\\\d{3,5}\\\\.?\\\\d*)\\\\s*(?:\\\\n|$)\\/\",\"\\/(?:^|\\\\n)\\\\s*(\\\\d{3,5}\\\\.?\\\\d*)\\\\s*(?:\\\\n|$)\\/\"],\"tp\":[\"\\/TP\\\\s*MAX\\\\s*[:\\\\s]*([\\\\d.]+)\\/i\",\"\\/TP\\\\s*(\\\\d+)\\\\s*[:\\\\s]*([\\\\d.]+)\\/i\",\"\\/TP\\\\s*[:\\\\s]*([\\\\d.]+)\\/i\"],\"sl\":[\"\\/STOP\\\\s*LOSS\\\\s*[:\\\\s]*([\\\\d.]+)\\/i\",\"\\/SL\\\\s*[:\\\\s]*([\\\\d.]+)\\/i\"]},\"confidence_weights\":{\"currency_pair\":20,\"symbol\":20,\"direction\":20,\"open_price\":20,\"tp\":15,\"sl\":15}}',85,1,0,0,'2025-12-04 20:58:25','2025-12-04 20:58:25'),(3,NULL,NULL,'Standard Signal Format','Common format: PAIR DIRECTION ENTRY SL TP','regex','{\"required_fields\":[\"currency_pair\",\"direction\",\"open_price\"],\"patterns\":{\"currency_pair\":[\"\\/([A-Z]{2,10}\\\\\\/[A-Z]{2,10})\\/\",\"\\/([A-Z]{2,10}-[A-Z]{2,10})\\/\"],\"direction\":[\"\\/(BUY|SELL)\\/i\",\"\\/(LONG|SHORT)\\/i\"],\"open_price\":[\"\\/ENTRY[:\\\\s]*([\\\\d,]+\\\\.?\\\\d*)\\/i\",\"\\/PRICE[:\\\\s]*([\\\\d,]+\\\\.?\\\\d*)\\/i\"],\"sl\":[\"\\/SL[:\\\\s]*([\\\\d,]+\\\\.?\\\\d*)\\/i\",\"\\/STOP[\\\\s]*LOSS[:\\\\s]*([\\\\d,]+\\\\.?\\\\d*)\\/i\"],\"tp\":[\"\\/TP[:\\\\s]*([\\\\d,]+\\\\.?\\\\d*)\\/i\",\"\\/TAKE[\\\\s]*PROFIT[:\\\\s]*([\\\\d,]+\\\\.?\\\\d*)\\/i\"]},\"confidence_weights\":{\"currency_pair\":15,\"direction\":15,\"open_price\":20,\"sl\":15,\"tp\":15}}',50,1,0,0,'2025-12-04 20:58:25','2025-12-04 20:58:25'),(4,NULL,NULL,'Line-Based Template','Each field on separate line','template','{\"required_fields\":[\"currency_pair\",\"direction\"],\"line_mappings\":[{\"field\":\"currency_pair\",\"pattern\":\"\\/([A-Z]{2,10}\\\\\\/[A-Z]{2,10})\\/\",\"match_index\":1},{\"field\":\"direction\",\"pattern\":\"\\/(BUY|SELL)\\/i\",\"match_index\":1},{\"field\":\"open_price\",\"pattern\":\"\\/([\\\\d,]+\\\\.?\\\\d*)\\/\",\"match_index\":1},{\"field\":\"sl\",\"pattern\":\"\\/([\\\\d,]+\\\\.?\\\\d*)\\/\",\"match_index\":1},{\"field\":\"tp\",\"pattern\":\"\\/([\\\\d,]+\\\\.?\\\\d*)\\/\",\"match_index\":1}]}',40,1,0,0,'2025-12-04 20:58:25','2025-12-04 20:58:25');
/*!40000 ALTER TABLE `sp_message_parsing_patterns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_migrations`
--

DROP TABLE IF EXISTS `sp_migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=101 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_migrations`
--

LOCK TABLES `sp_migrations` WRITE;
/*!40000 ALTER TABLE `sp_migrations` DISABLE KEYS */;
INSERT INTO `sp_migrations` VALUES (1,'2019_12_14_000001_create_personal_access_tokens_table',1),(2,'2021_08_15_113006_create_crypto_payments_table',1),(3,'2023_02_22_104311_create_admins_table',1),(4,'2023_02_22_111101_create_configurations_table',1),(5,'2023_02_22_121218_create_gateways_table',1),(6,'2023_02_25_120246_create_users_table',1),(7,'2023_02_26_063704_create_admin_password_resets_table',1),(8,'2023_02_26_081605_create_deposits_table',1),(9,'2023_02_26_082931_create_withdraw_gateways_table',1),(10,'2023_02_26_084519_create_withdraws_table',1),(11,'2023_02_26_085002_create_tickets_table',1),(12,'2023_02_26_085317_create_ticket_replies_table',1),(13,'2023_02_26_085758_create_payments_table',1),(14,'2023_02_26_090322_create_user_logs_table',1),(15,'2023_02_26_091028_create_languages_table',1),(16,'2023_02_26_092247_create_notifications_table',1),(17,'2023_02_26_094347_create_permission_tables',1),(18,'2023_02_26_105957_create_pages_table',1),(19,'2023_02_26_110308_create_page_sections_table',1),(20,'2023_02_28_064341_create_contents_table',1),(21,'2023_02_28_104449_create_frontend_components_table',1),(22,'2023_03_07_113921_create_referrals_table',1),(23,'2023_03_11_064120_create_subscribers_table',1),(24,'2023_03_11_101143_create_templates_table',1),(25,'2023_03_16_054806_create_plan_subscriptions_table',1),(26,'2023_03_16_055015_create_login_securities_table',1),(27,'2023_03_16_055208_create_transactions_table',1),(28,'2023_03_16_055624_create_plans_table',1),(29,'2023_03_16_072610_create_markets_table',1),(30,'2023_03_16_080329_create_currency_pairs_table',1),(31,'2023_03_16_080524_create_time_frames_table',1),(32,'2023_03_16_080747_create_signals_table',1),(33,'2023_03_16_081326_create_plan_signals_table',1),(34,'2023_03_18_052943_create_dashboard_signals_table',1),(35,'2023_03_18_053717_create_user_signals_table',1),(36,'2023_03_20_091115_create_money_transfers_table',1),(37,'2023_03_20_095030_create_referral_commissions_table',1),(38,'2023_03_22_060754_create_jobs_table',1),(39,'2023_04_02_045912_create_frontend_media_table',1),(40,'2025_01_19_100000_add_parser_preference_to_channel_sources',1),(41,'2025_01_27_100000_create_channel_sources_table',1),(42,'2025_01_27_100001_create_channel_messages_table',1),(43,'2025_01_27_100002_add_channel_source_fields_to_signals_table',1),(44,'2025_01_28_100000_create_ai_configurations_table',1),(45,'2025_01_29_100000_create_execution_connections_table',1),(46,'2025_01_29_100000_create_trading_presets_table',1),(47,'2025_01_29_100001_add_preset_id_to_execution_connections',1),(48,'2025_01_29_100001_create_execution_logs_table',1),(49,'2025_01_29_100002_add_preset_id_to_copy_trading_subscriptions',1),(50,'2025_01_29_100002_create_execution_positions_table',1),(51,'2025_01_29_100003_add_default_preset_id_to_users',1),(52,'2025_01_29_100003_create_execution_analytics_table',1),(53,'2025_01_29_100004_add_multi_tp_to_execution_positions',1),(54,'2025_01_29_100004_create_execution_notifications_table',1),(55,'2025_01_29_100005_add_structure_sl_to_signals',1),(56,'2025_01_29_100006_add_preset_id_to_trading_bots',1),(57,'2025_01_30_100000_create_copy_trading_settings_table',1),(58,'2025_01_30_100001_create_copy_trading_subscriptions_table',1),(59,'2025_01_30_100002_create_copy_trading_executions_table',1),(60,'2025_01_30_100003_add_admin_support_to_copy_trading_settings',1),(61,'2025_11_07_000000_add_manage_addon_permission',1),(62,'2025_11_11_100000_extend_channel_sources_for_admin_ownership',1),(63,'2025_11_11_100001_create_channel_source_users_table',1),(64,'2025_11_11_100002_create_channel_source_plans_table',1),(65,'2025_11_11_100003_create_message_parsing_patterns_table',1),(66,'2025_11_11_100004_create_signal_analytics_table',1),(67,'2025_11_13_160910_make_user_id_nullable_in_channel_sources_table',1),(68,'2025_11_13_161451_change_config_column_to_text_in_channel_sources_table',1),(69,'2025_12_02_105002_create_filter_strategies_table',1),(70,'2025_12_02_105100_add_filter_strategy_to_trading_presets',1),(71,'2025_12_02_111940_create_ai_model_profiles_table',1),(72,'2025_12_02_111949_add_ai_fields_to_trading_presets',1),(73,'2025_12_02_120000_create_srm_signal_provider_metrics_table',1),(74,'2025_12_02_120001_create_srm_predictions_table',1),(75,'2025_12_02_120002_create_srm_model_versions_table',1),(76,'2025_12_02_120003_create_srm_ab_tests_table',1),(77,'2025_12_02_120004_create_srm_ab_test_assignments_table',1),(78,'2025_12_02_120005_add_srm_fields_to_execution_logs_table',1),(79,'2025_12_02_120006_add_srm_fields_to_execution_positions_table',1),(80,'2025_12_03_020013_add_backend_theme_to_configurations_table',1),(81,'2025_12_03_100000_create_ai_providers_table',1),(82,'2025_12_03_100001_create_ai_connections_table',1),(83,'2025_12_03_100002_create_ai_connection_usage_table',1),(84,'2025_12_03_100003_add_default_connection_foreign_key',1),(85,'2025_12_03_120000_create_ai_parsing_profiles_table',1),(86,'2025_12_03_120001_migrate_ai_configurations_to_connections',1),(87,'2025_12_03_130000_create_translation_settings_table',1),(88,'2025_12_03_140000_refactor_ai_model_profiles_to_use_connections',1),(89,'2025_12_04_000001_add_telegram_chat_id_to_users_and_indexes',1),(90,'2025_12_05_015138_create_bot_templates_table',1),(91,'2025_12_05_015159_create_signal_source_templates_table',1),(92,'2025_12_05_015204_create_complete_bots_table',1),(93,'2025_12_05_015209_create_template_backtests_table',1),(94,'2025_12_05_015213_create_template_ratings_table',1),(95,'2025_12_05_015218_create_template_clones_table',1),(96,'2025_12_05_015223_create_trader_profiles_table',1),(97,'2025_12_05_015228_create_trader_leaderboard_table',1),(98,'2025_12_05_015233_create_trader_ratings_table',1),(99,'2025_12_05_015237_create_market_data_subscriptions_table',1),(100,'2025_12_05_015242_add_cache_metadata_to_market_data_table',1);
/*!40000 ALTER TABLE `sp_migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_model_has_permissions`
--

DROP TABLE IF EXISTS `sp_model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_model_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `sp_model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `sp_permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_model_has_permissions`
--

LOCK TABLES `sp_model_has_permissions` WRITE;
/*!40000 ALTER TABLE `sp_model_has_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sp_model_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_model_has_roles`
--

DROP TABLE IF EXISTS `sp_model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_model_has_roles` (
  `role_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `sp_model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `sp_roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_model_has_roles`
--

LOCK TABLES `sp_model_has_roles` WRITE;
/*!40000 ALTER TABLE `sp_model_has_roles` DISABLE KEYS */;
INSERT INTO `sp_model_has_roles` VALUES (1,'App\\Models\\Admin',1);
/*!40000 ALTER TABLE `sp_model_has_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_money_transfers`
--

DROP TABLE IF EXISTS `sp_money_transfers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_money_transfers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sender_id` bigint(20) unsigned NOT NULL,
  `receiver_id` bigint(20) unsigned NOT NULL,
  `trx` varchar(255) NOT NULL,
  `details` varchar(255) NOT NULL,
  `amount` decimal(28,8) NOT NULL,
  `charge` decimal(28,8) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_money_transfers`
--

LOCK TABLES `sp_money_transfers` WRITE;
/*!40000 ALTER TABLE `sp_money_transfers` DISABLE KEYS */;
/*!40000 ALTER TABLE `sp_money_transfers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_notifications`
--

DROP TABLE IF EXISTS `sp_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_notifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(255) NOT NULL,
  `notifiable_type` varchar(255) NOT NULL,
  `notifiable_id` varchar(255) NOT NULL,
  `data` text NOT NULL,
  `read_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sp_notifications_notifiable_type_unique` (`notifiable_type`),
  UNIQUE KEY `sp_notifications_notifiable_id_unique` (`notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_notifications`
--

LOCK TABLES `sp_notifications` WRITE;
/*!40000 ALTER TABLE `sp_notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `sp_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_page_sections`
--

DROP TABLE IF EXISTS `sp_page_sections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_page_sections` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `page_id` bigint(20) unsigned NOT NULL,
  `sections` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_page_sections`
--

LOCK TABLES `sp_page_sections` WRITE;
/*!40000 ALTER TABLE `sp_page_sections` DISABLE KEYS */;
INSERT INTO `sp_page_sections` VALUES (1,1,'\"banner\"','2025-12-04 20:57:58','2025-12-04 20:57:58'),(2,1,'\"about\"','2025-12-04 20:57:58','2025-12-04 20:57:58'),(3,1,'\"benefits\"','2025-12-04 20:57:58','2025-12-04 20:57:58'),(4,1,'\"how_works\"','2025-12-04 20:57:58','2025-12-04 20:57:58'),(5,1,'\"plans\"','2025-12-04 20:57:58','2025-12-04 20:57:58'),(6,1,'\"trade\"','2025-12-04 20:57:58','2025-12-04 20:57:58'),(7,1,'\"referral\"','2025-12-04 20:57:58','2025-12-04 20:57:58'),(8,1,'\"team\"','2025-12-04 20:57:58','2025-12-04 20:57:58'),(9,1,'\"testimonial\"','2025-12-04 20:57:58','2025-12-04 20:57:58'),(10,1,'\"blog\"','2025-12-04 20:57:58','2025-12-04 20:57:58'),(11,2,'\"about\"','2025-12-04 20:57:58','2025-12-04 20:57:58'),(12,2,'\"overview\"','2025-12-04 20:57:58','2025-12-04 20:57:58'),(13,2,'\"how_works\"','2025-12-04 20:57:58','2025-12-04 20:57:58'),(14,2,'\"team\"','2025-12-04 20:57:58','2025-12-04 20:57:58'),(15,3,'\"plans\"','2025-12-04 20:57:58','2025-12-04 20:57:58'),(16,4,'\"contact\"','2025-12-04 20:57:58','2025-12-04 20:57:58'),(17,5,'\"blog\"','2025-12-04 20:57:58','2025-12-04 20:57:58'),(18,1,'\"banner\"','2025-12-04 20:58:07','2025-12-04 20:58:07'),(19,1,'\"about\"','2025-12-04 20:58:07','2025-12-04 20:58:07'),(20,1,'\"benefits\"','2025-12-04 20:58:07','2025-12-04 20:58:07'),(21,1,'\"how_works\"','2025-12-04 20:58:07','2025-12-04 20:58:07'),(22,1,'\"plans\"','2025-12-04 20:58:07','2025-12-04 20:58:07'),(23,1,'\"trade\"','2025-12-04 20:58:07','2025-12-04 20:58:07'),(24,1,'\"referral\"','2025-12-04 20:58:07','2025-12-04 20:58:07'),(25,1,'\"team\"','2025-12-04 20:58:07','2025-12-04 20:58:07'),(26,1,'\"testimonial\"','2025-12-04 20:58:07','2025-12-04 20:58:07'),(27,1,'\"blog\"','2025-12-04 20:58:07','2025-12-04 20:58:07'),(28,2,'\"about\"','2025-12-04 20:58:07','2025-12-04 20:58:07'),(29,2,'\"overview\"','2025-12-04 20:58:07','2025-12-04 20:58:07'),(30,2,'\"how_works\"','2025-12-04 20:58:07','2025-12-04 20:58:07'),(31,2,'\"team\"','2025-12-04 20:58:07','2025-12-04 20:58:07'),(32,3,'\"plans\"','2025-12-04 20:58:07','2025-12-04 20:58:07'),(33,4,'\"contact\"','2025-12-04 20:58:07','2025-12-04 20:58:07'),(34,5,'\"blog\"','2025-12-04 20:58:07','2025-12-04 20:58:07'),(35,1,'\"banner\"','2025-12-04 20:58:25','2025-12-04 20:58:25'),(36,1,'\"about\"','2025-12-04 20:58:25','2025-12-04 20:58:25'),(37,1,'\"benefits\"','2025-12-04 20:58:25','2025-12-04 20:58:25'),(38,1,'\"how_works\"','2025-12-04 20:58:25','2025-12-04 20:58:25'),(39,1,'\"plans\"','2025-12-04 20:58:25','2025-12-04 20:58:25'),(40,1,'\"trade\"','2025-12-04 20:58:25','2025-12-04 20:58:25'),(41,1,'\"referral\"','2025-12-04 20:58:25','2025-12-04 20:58:25'),(42,1,'\"team\"','2025-12-04 20:58:25','2025-12-04 20:58:25'),(43,1,'\"testimonial\"','2025-12-04 20:58:25','2025-12-04 20:58:25'),(44,1,'\"blog\"','2025-12-04 20:58:25','2025-12-04 20:58:25'),(45,2,'\"about\"','2025-12-04 20:58:25','2025-12-04 20:58:25'),(46,2,'\"overview\"','2025-12-04 20:58:25','2025-12-04 20:58:25'),(47,2,'\"how_works\"','2025-12-04 20:58:25','2025-12-04 20:58:25'),(48,2,'\"team\"','2025-12-04 20:58:25','2025-12-04 20:58:25'),(49,3,'\"plans\"','2025-12-04 20:58:25','2025-12-04 20:58:25'),(50,4,'\"contact\"','2025-12-04 20:58:25','2025-12-04 20:58:25'),(51,5,'\"blog\"','2025-12-04 20:58:25','2025-12-04 20:58:25');
/*!40000 ALTER TABLE `sp_page_sections` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_pages`
--

DROP TABLE IF EXISTS `sp_pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_pages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `order` int(11) NOT NULL,
  `is_dropdown` tinyint(1) NOT NULL,
  `seo_keywords` text DEFAULT NULL,
  `seo_description` text DEFAULT NULL,
  `status` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sp_pages_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_pages`
--

LOCK TABLES `sp_pages` WRITE;
/*!40000 ALTER TABLE `sp_pages` DISABLE KEYS */;
INSERT INTO `sp_pages` VALUES (1,'Home','home',1,0,'[\"trading\",\"signals\",\"forex\",\"crypto\",\"algo trading\"]','AlgoExpertHub - Your premier destination for algorithmic trading signals across Forex, Crypto, and Stock markets.',1,'2025-12-04 20:57:58','2025-12-04 20:57:58'),(2,'About','about',2,0,'[\"about us\",\"company\",\"trading platform\"]','Learn about AlgoExpertHub and our mission to democratize algorithmic trading.',1,'2025-12-04 20:57:58','2025-12-04 20:57:58'),(3,'Packages','packages',3,0,'[\"plans\",\"pricing\",\"subscription\"]','Choose the perfect plan for your trading journey. Flexible monthly and yearly subscriptions.',1,'2025-12-04 20:57:58','2025-12-04 20:57:58'),(4,'Contact','contact',4,0,'[\"contact\",\"support\",\"help\"]','Get in touch with our support team. We\'re here to help you succeed.',1,'2025-12-04 20:57:58','2025-12-04 20:57:58'),(5,'Blog','blog',5,1,'[\"blog\",\"news\",\"trading tips\"]','Latest trading news, tips, and market analysis from our experts.',1,'2025-12-04 20:57:58','2025-12-04 20:57:58');
/*!40000 ALTER TABLE `sp_pages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_payments`
--

DROP TABLE IF EXISTS `sp_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `plan_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `gateway_id` bigint(20) unsigned NOT NULL,
  `trx` varchar(255) NOT NULL,
  `amount` decimal(28,8) NOT NULL,
  `rate` decimal(28,8) NOT NULL,
  `charge` decimal(28,8) NOT NULL,
  `total` decimal(28,8) NOT NULL,
  `status` int(11) NOT NULL COMMENT '1=>approved, 2=>pending, 3=>rejected',
  `type` int(11) NOT NULL,
  `proof` text DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `plan_expired_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sp_payments_trx_unique` (`trx`),
  KEY `sp_payments_user_id_status_index` (`user_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_payments`
--

LOCK TABLES `sp_payments` WRITE;
/*!40000 ALTER TABLE `sp_payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `sp_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_permissions`
--

DROP TABLE IF EXISTS `sp_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sp_permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_permissions`
--

LOCK TABLES `sp_permissions` WRITE;
/*!40000 ALTER TABLE `sp_permissions` DISABLE KEYS */;
INSERT INTO `sp_permissions` VALUES (1,'manage-addon','admin','2025-12-04 20:56:42','2025-12-04 20:56:42'),(2,'manage-admin','admin','2025-12-04 20:57:56','2025-12-04 20:57:56'),(3,'manage-role','admin','2025-12-04 20:57:56','2025-12-04 20:57:56'),(4,'manage-referral','admin','2025-12-04 20:57:56','2025-12-04 20:57:56'),(5,'signal','admin','2025-12-04 20:57:56','2025-12-04 20:57:56'),(6,'manage-plan','admin','2025-12-04 20:57:56','2025-12-04 20:57:56'),(7,'manage-user','admin','2025-12-04 20:57:56','2025-12-04 20:57:56'),(8,'manage-ticket','admin','2025-12-04 20:57:56','2025-12-04 20:57:56'),(9,'manage-gateway','admin','2025-12-04 20:57:56','2025-12-04 20:57:56'),(10,'payments','admin','2025-12-04 20:57:56','2025-12-04 20:57:56'),(11,'manage-withdraw','admin','2025-12-04 20:57:56','2025-12-04 20:57:56'),(12,'manage-deposit','admin','2025-12-04 20:57:56','2025-12-04 20:57:56'),(13,'manage-theme','admin','2025-12-04 20:57:56','2025-12-04 20:57:56'),(14,'manage-email','admin','2025-12-04 20:57:56','2025-12-04 20:57:56'),(15,'manage-setting','admin','2025-12-04 20:57:56','2025-12-04 20:57:56'),(16,'manage-language','admin','2025-12-04 20:57:56','2025-12-04 20:57:56'),(17,'manage-logs','admin','2025-12-04 20:57:56','2025-12-04 20:57:56'),(18,'manage-frontend','admin','2025-12-04 20:57:56','2025-12-04 20:57:56'),(19,'manage-subscriber','admin','2025-12-04 20:57:56','2025-12-04 20:57:56'),(20,'manage-report','admin','2025-12-04 20:57:56','2025-12-04 20:57:56');
/*!40000 ALTER TABLE `sp_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_personal_access_tokens`
--

DROP TABLE IF EXISTS `sp_personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sp_personal_access_tokens_token_unique` (`token`),
  KEY `sp_personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_personal_access_tokens`
--

LOCK TABLES `sp_personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `sp_personal_access_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `sp_personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_plan_signals`
--

DROP TABLE IF EXISTS `sp_plan_signals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_plan_signals` (
  `plan_id` bigint(20) unsigned NOT NULL,
  `signal_id` bigint(20) unsigned NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_plan_signals`
--

LOCK TABLES `sp_plan_signals` WRITE;
/*!40000 ALTER TABLE `sp_plan_signals` DISABLE KEYS */;
/*!40000 ALTER TABLE `sp_plan_signals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_plan_subscriptions`
--

DROP TABLE IF EXISTS `sp_plan_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_plan_subscriptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `plan_id` bigint(20) unsigned NOT NULL,
  `is_current` tinyint(1) NOT NULL,
  `plan_expired_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sp_plan_subscriptions_user_id_is_current_plan_expired_at_index` (`user_id`,`is_current`,`plan_expired_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_plan_subscriptions`
--

LOCK TABLES `sp_plan_subscriptions` WRITE;
/*!40000 ALTER TABLE `sp_plan_subscriptions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sp_plan_subscriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_plans`
--

DROP TABLE IF EXISTS `sp_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_plans` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `price` decimal(28,8) NOT NULL,
  `duration` int(11) NOT NULL,
  `plan_type` varchar(255) NOT NULL,
  `price_type` varchar(255) NOT NULL,
  `feature` text DEFAULT NULL,
  `whatsapp` tinyint(1) NOT NULL,
  `telegram` tinyint(1) NOT NULL,
  `email` tinyint(1) NOT NULL,
  `sms` tinyint(1) NOT NULL,
  `dashboard` tinyint(1) NOT NULL,
  `status` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sp_plans_name_unique` (`name`),
  UNIQUE KEY `sp_plans_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_plans`
--

LOCK TABLES `sp_plans` WRITE;
/*!40000 ALTER TABLE `sp_plans` DISABLE KEYS */;
INSERT INTO `sp_plans` VALUES (6,'Free Trial','free-trial',0.00000000,7,'limited','free','[\"Basic signals\",\"7 days access\",\"Email support\"]',0,1,1,0,1,1,'2025-12-04 21:18:42','2025-12-04 21:18:42'),(7,'Basic Monthly','basic-monthly',29.99000000,30,'limited','paid','[\"All signals\",\"Telegram notifications\",\"Email alerts\",\"Dashboard access\"]',0,1,1,0,1,1,'2025-12-04 21:18:42','2025-12-04 21:18:42'),(8,'Pro Monthly','pro-monthly',49.99000000,30,'limited','paid','[\"All signals\",\"Priority support\",\"Telegram + WhatsApp\",\"Auto-trading integration\",\"Advanced analytics\"]',1,1,1,0,1,1,'2025-12-04 21:18:42','2025-12-04 21:18:42'),(9,'Premium Yearly','premium-yearly',499.99000000,365,'limited','paid','[\"All signals\",\"VIP support\",\"All channels (Telegram, WhatsApp, Email, SMS)\",\"Auto-trading\",\"Copy trading\",\"Custom presets\",\"2 months free\"]',1,1,1,1,1,1,'2025-12-04 21:18:42','2025-12-04 21:18:42'),(10,'Lifetime','lifetime',999.99000000,0,'unlimited','paid','[\"Lifetime access\",\"All features\",\"Priority VIP support\",\"All notification channels\",\"Auto-trading unlimited\",\"Copy trading\",\"Early access to new features\"]',1,1,1,1,1,1,'2025-12-04 21:18:42','2025-12-04 21:18:42');
/*!40000 ALTER TABLE `sp_plans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_referral_commissions`
--

DROP TABLE IF EXISTS `sp_referral_commissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_referral_commissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `commission_from` bigint(20) unsigned NOT NULL,
  `commission_to` bigint(20) unsigned NOT NULL,
  `amount` decimal(28,8) NOT NULL,
  `purpouse` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_referral_commissions`
--

LOCK TABLES `sp_referral_commissions` WRITE;
/*!40000 ALTER TABLE `sp_referral_commissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sp_referral_commissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_referrals`
--

DROP TABLE IF EXISTS `sp_referrals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_referrals` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(255) NOT NULL,
  `level` text NOT NULL,
  `commission` text NOT NULL,
  `status` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_referrals`
--

LOCK TABLES `sp_referrals` WRITE;
/*!40000 ALTER TABLE `sp_referrals` DISABLE KEYS */;
INSERT INTO `sp_referrals` VALUES (3,'invest','[\"Level 1\",\"Level 2\",\"Level 3\"]','[\"10\",\"5\",\"3\"]',1,'2025-12-04 21:21:24','2025-12-04 21:21:24'),(4,'subscription','[\"Level 1\",\"Level 2\"]','[\"15\",\"10\"]',1,'2025-12-04 21:21:24','2025-12-04 21:21:24');
/*!40000 ALTER TABLE `sp_referrals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_role_has_permissions`
--

DROP TABLE IF EXISTS `sp_role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_role_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `sp_role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `sp_role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `sp_permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sp_role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `sp_roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_role_has_permissions`
--

LOCK TABLES `sp_role_has_permissions` WRITE;
/*!40000 ALTER TABLE `sp_role_has_permissions` DISABLE KEYS */;
INSERT INTO `sp_role_has_permissions` VALUES (1,1),(2,1),(3,1),(4,1),(5,1),(6,1),(7,1),(8,1),(9,1),(10,1),(11,1),(12,1),(13,1),(14,1),(15,1),(16,1),(17,1),(18,1),(19,1),(20,1);
/*!40000 ALTER TABLE `sp_role_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_roles`
--

DROP TABLE IF EXISTS `sp_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sp_roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_roles`
--

LOCK TABLES `sp_roles` WRITE;
/*!40000 ALTER TABLE `sp_roles` DISABLE KEYS */;
INSERT INTO `sp_roles` VALUES (1,'Admin','admin','2025-12-04 20:57:56','2025-12-04 20:57:56');
/*!40000 ALTER TABLE `sp_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_signal_analytics`
--

DROP TABLE IF EXISTS `sp_signal_analytics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_signal_analytics` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `signal_id` bigint(20) unsigned NOT NULL,
  `channel_source_id` bigint(20) unsigned DEFAULT NULL,
  `plan_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL COMMENT 'User yang menerima signal',
  `currency_pair` varchar(255) DEFAULT NULL,
  `direction` varchar(255) DEFAULT NULL,
  `open_price` decimal(28,8) DEFAULT NULL,
  `sl` decimal(28,8) DEFAULT NULL,
  `tp` decimal(28,8) DEFAULT NULL,
  `actual_open_price` decimal(28,8) DEFAULT NULL,
  `actual_close_price` decimal(28,8) DEFAULT NULL,
  `profit_loss` decimal(28,8) DEFAULT 0.00000000,
  `pips` decimal(18,2) DEFAULT 0.00,
  `trade_status` enum('pending','open','closed','cancelled') NOT NULL DEFAULT 'pending',
  `signal_received_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `signal_published_at` timestamp NULL DEFAULT NULL,
  `trade_opened_at` timestamp NULL DEFAULT NULL,
  `trade_closed_at` timestamp NULL DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Additional data: parsing confidence, pattern used, etc.' CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sp_signal_analytics_signal_id_index` (`signal_id`),
  KEY `sp_signal_analytics_channel_source_id_index` (`channel_source_id`),
  KEY `sp_signal_analytics_plan_id_index` (`plan_id`),
  KEY `sp_signal_analytics_user_id_index` (`user_id`),
  KEY `sp_signal_analytics_trade_status_index` (`trade_status`),
  KEY `sp_signal_analytics_signal_received_at_index` (`signal_received_at`),
  KEY `sp_signal_analytics_currency_pair_index` (`currency_pair`),
  CONSTRAINT `sp_signal_analytics_channel_source_id_foreign` FOREIGN KEY (`channel_source_id`) REFERENCES `sp_channel_sources` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sp_signal_analytics_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `sp_plans` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sp_signal_analytics_signal_id_foreign` FOREIGN KEY (`signal_id`) REFERENCES `sp_signals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sp_signal_analytics_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `sp_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_signal_analytics`
--

LOCK TABLES `sp_signal_analytics` WRITE;
/*!40000 ALTER TABLE `sp_signal_analytics` DISABLE KEYS */;
/*!40000 ALTER TABLE `sp_signal_analytics` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_signal_source_templates`
--

DROP TABLE IF EXISTS `sp_signal_source_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_signal_source_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `source_type` enum('telegram','telegram_mtproto','api','firebase','rss','web_scrape') NOT NULL DEFAULT 'telegram',
  `config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Connection params, parsing rules' CHECK (json_valid(`config`)),
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `downloads_count` int(11) NOT NULL DEFAULT 0,
  `avg_rating` decimal(3,2) NOT NULL DEFAULT 0.00,
  `total_ratings` int(11) NOT NULL DEFAULT 0,
  `image_url` varchar(255) DEFAULT NULL,
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sp_signal_source_templates_user_id_foreign` (`user_id`),
  KEY `sig_src_public_feat_rating_idx` (`is_public`,`is_featured`,`avg_rating`),
  KEY `sig_src_type_idx` (`source_type`),
  KEY `sig_src_downloads_idx` (`downloads_count`),
  CONSTRAINT `sp_signal_source_templates_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `sp_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_signal_source_templates`
--

LOCK TABLES `sp_signal_source_templates` WRITE;
/*!40000 ALTER TABLE `sp_signal_source_templates` DISABLE KEYS */;
/*!40000 ALTER TABLE `sp_signal_source_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_signals`
--

DROP TABLE IF EXISTS `sp_signals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_signals` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `channel_source_id` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `time_frame_id` bigint(20) unsigned NOT NULL,
  `currency_pair_id` bigint(20) unsigned NOT NULL,
  `market_id` bigint(20) unsigned NOT NULL,
  `open_price` decimal(28,8) NOT NULL,
  `sl` decimal(28,8) NOT NULL,
  `tp` decimal(28,8) NOT NULL,
  `structure_sl_price` decimal(28,8) DEFAULT NULL COMMENT 'Structure-based SL price (for sl_mode=STRUCTURE)',
  `image` varchar(255) DEFAULT NULL,
  `description` longtext DEFAULT NULL,
  `direction` varchar(255) NOT NULL,
  `is_published` tinyint(1) NOT NULL,
  `auto_created` tinyint(1) NOT NULL DEFAULT 0,
  `message_hash` varchar(64) DEFAULT NULL,
  `published_date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sp_signals_channel_source_id_index` (`channel_source_id`),
  KEY `sp_signals_auto_created_index` (`auto_created`),
  KEY `sp_signals_message_hash_index` (`message_hash`),
  KEY `sp_signals_structure_sl_price_index` (`structure_sl_price`),
  KEY `sp_signals_is_published_published_date_index` (`is_published`,`published_date`),
  CONSTRAINT `sp_signals_channel_source_id_foreign` FOREIGN KEY (`channel_source_id`) REFERENCES `sp_channel_sources` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_signals`
--

LOCK TABLES `sp_signals` WRITE;
/*!40000 ALTER TABLE `sp_signals` DISABLE KEYS */;
/*!40000 ALTER TABLE `sp_signals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_srm_ab_test_assignments`
--

DROP TABLE IF EXISTS `sp_srm_ab_test_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_srm_ab_test_assignments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ab_test_id` bigint(20) unsigned NOT NULL COMMENT 'FK to srm_ab_tests',
  `user_id` bigint(20) unsigned DEFAULT NULL COMMENT 'FK to users',
  `connection_id` bigint(20) unsigned DEFAULT NULL COMMENT 'FK to execution_connections',
  `group_type` enum('pilot','control') NOT NULL COMMENT 'Group assignment',
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'When user was assigned',
  PRIMARY KEY (`id`),
  KEY `idx_ab_test` (`ab_test_id`,`group_type`),
  KEY `idx_user` (`user_id`),
  KEY `idx_connection` (`connection_id`),
  CONSTRAINT `sp_srm_ab_test_assignments_ab_test_id_foreign` FOREIGN KEY (`ab_test_id`) REFERENCES `sp_srm_ab_tests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sp_srm_ab_test_assignments_connection_id_foreign` FOREIGN KEY (`connection_id`) REFERENCES `sp_execution_connections` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sp_srm_ab_test_assignments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `sp_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_srm_ab_test_assignments`
--

LOCK TABLES `sp_srm_ab_test_assignments` WRITE;
/*!40000 ALTER TABLE `sp_srm_ab_test_assignments` DISABLE KEYS */;
/*!40000 ALTER TABLE `sp_srm_ab_test_assignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_srm_ab_tests`
--

DROP TABLE IF EXISTS `sp_srm_ab_tests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_srm_ab_tests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT 'Test name',
  `description` text DEFAULT NULL COMMENT 'Test description',
  `status` enum('draft','running','paused','completed','cancelled') NOT NULL DEFAULT 'draft' COMMENT 'Test status',
  `pilot_group_percentage` decimal(5,2) NOT NULL DEFAULT 10.00 COMMENT 'Percentage of users in pilot group',
  `test_duration_days` int(10) unsigned NOT NULL DEFAULT 14 COMMENT 'Test duration in days',
  `control_logic` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Control group SRM logic (current production)' CHECK (json_valid(`control_logic`)),
  `pilot_logic` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Pilot group SRM logic (new logic to test)' CHECK (json_valid(`pilot_logic`)),
  `start_date` date DEFAULT NULL COMMENT 'Test start date',
  `end_date` date DEFAULT NULL COMMENT 'Test end date',
  `pilot_group_size` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Pilot group size',
  `control_group_size` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Control group size',
  `pilot_avg_pnl` decimal(10,2) DEFAULT NULL COMMENT 'Pilot group average P/L',
  `control_avg_pnl` decimal(10,2) DEFAULT NULL COMMENT 'Control group average P/L',
  `pilot_avg_drawdown` decimal(5,2) DEFAULT NULL COMMENT 'Pilot group average drawdown',
  `control_avg_drawdown` decimal(5,2) DEFAULT NULL COMMENT 'Control group average drawdown',
  `pilot_win_rate` decimal(5,2) DEFAULT NULL COMMENT 'Pilot group win rate',
  `control_win_rate` decimal(5,2) DEFAULT NULL COMMENT 'Control group win rate',
  `p_value` decimal(8,6) DEFAULT NULL COMMENT 'Statistical significance test result',
  `is_significant` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Is result statistically significant',
  `decision` enum('deploy','reject','extend') DEFAULT NULL COMMENT 'Test decision',
  `decision_notes` text DEFAULT NULL COMMENT 'Decision notes',
  `created_by_admin_id` bigint(20) unsigned DEFAULT NULL COMMENT 'Admin who created the test',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_dates` (`start_date`,`end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_srm_ab_tests`
--

LOCK TABLES `sp_srm_ab_tests` WRITE;
/*!40000 ALTER TABLE `sp_srm_ab_tests` DISABLE KEYS */;
/*!40000 ALTER TABLE `sp_srm_ab_tests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_srm_model_versions`
--

DROP TABLE IF EXISTS `sp_srm_model_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_srm_model_versions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `model_type` enum('slippage_prediction','performance_score','risk_optimization') NOT NULL COMMENT 'Type of ML model',
  `version` varchar(50) NOT NULL COMMENT 'Model version identifier',
  `status` enum('training','active','deprecated','testing') NOT NULL DEFAULT 'training' COMMENT 'Model status',
  `parameters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Model hyperparameters, weights, etc.' CHECK (json_valid(`parameters`)),
  `training_data_count` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Number of training samples',
  `training_date_start` timestamp NULL DEFAULT NULL COMMENT 'Training start date',
  `training_date_end` timestamp NULL DEFAULT NULL COMMENT 'Training end date',
  `accuracy` decimal(5,2) DEFAULT NULL COMMENT 'Overall accuracy percentage',
  `mse` decimal(10,6) DEFAULT NULL COMMENT 'Mean Squared Error (for regression)',
  `r2_score` decimal(5,4) DEFAULT NULL COMMENT 'R² score (for regression)',
  `validation_accuracy` decimal(5,2) DEFAULT NULL COMMENT 'Validation accuracy',
  `validation_mse` decimal(10,6) DEFAULT NULL COMMENT 'Validation MSE',
  `deployed_at` timestamp NULL DEFAULT NULL COMMENT 'When model was deployed',
  `deprecated_at` timestamp NULL DEFAULT NULL COMMENT 'When model was deprecated',
  `notes` text DEFAULT NULL COMMENT 'Additional notes',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_model_version` (`model_type`,`version`),
  KEY `idx_model_type` (`model_type`,`status`),
  KEY `idx_version` (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_srm_model_versions`
--

LOCK TABLES `sp_srm_model_versions` WRITE;
/*!40000 ALTER TABLE `sp_srm_model_versions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sp_srm_model_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_srm_predictions`
--

DROP TABLE IF EXISTS `sp_srm_predictions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_srm_predictions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `execution_log_id` bigint(20) unsigned DEFAULT NULL COMMENT 'FK to execution_logs',
  `signal_id` bigint(20) unsigned DEFAULT NULL COMMENT 'FK to signals',
  `connection_id` bigint(20) unsigned DEFAULT NULL COMMENT 'FK to execution_connections',
  `prediction_type` enum('slippage','performance_score','lot_optimization') NOT NULL COMMENT 'Type of prediction',
  `symbol` varchar(50) DEFAULT NULL COMMENT 'Trading symbol',
  `trading_session` enum('TOKYO','LONDON','NEW_YORK','ASIAN','OVERLAP') DEFAULT NULL COMMENT 'Trading session',
  `day_of_week` tinyint(4) DEFAULT NULL COMMENT 'Day of week 1-7',
  `market_atr` decimal(10,4) DEFAULT NULL COMMENT 'Market ATR value',
  `volatility_index` decimal(8,4) DEFAULT NULL COMMENT 'Volatility index',
  `signal_provider_id` varchar(255) DEFAULT NULL COMMENT 'Signal provider identifier',
  `predicted_value` decimal(10,4) NOT NULL COMMENT 'Predicted value (slippage, score, or lot)',
  `confidence_score` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Confidence score 0-100',
  `actual_value` decimal(10,4) DEFAULT NULL COMMENT 'Actual value after execution',
  `accuracy` decimal(5,2) DEFAULT NULL COMMENT 'Prediction accuracy percentage',
  `model_version` varchar(50) DEFAULT NULL COMMENT 'ML model version used',
  `model_type` varchar(50) DEFAULT NULL COMMENT 'Model type (regression, weighted_scoring, etc.)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_execution_log` (`execution_log_id`),
  KEY `idx_signal` (`signal_id`),
  KEY `idx_connection` (`connection_id`),
  KEY `idx_prediction_type` (`prediction_type`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `sp_srm_predictions_execution_log_id_foreign` FOREIGN KEY (`execution_log_id`) REFERENCES `sp_execution_logs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sp_srm_predictions_signal_id_foreign` FOREIGN KEY (`signal_id`) REFERENCES `sp_signals` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_srm_predictions`
--

LOCK TABLES `sp_srm_predictions` WRITE;
/*!40000 ALTER TABLE `sp_srm_predictions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sp_srm_predictions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_srm_signal_provider_metrics`
--

DROP TABLE IF EXISTS `sp_srm_signal_provider_metrics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_srm_signal_provider_metrics` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `signal_provider_id` varchar(255) NOT NULL COMMENT 'channel_source_id or user_id',
  `signal_provider_type` enum('channel_source','user') NOT NULL COMMENT 'Type of signal provider',
  `period_start` date NOT NULL COMMENT 'Period start date',
  `period_end` date NOT NULL COMMENT 'Period end date',
  `period_type` enum('daily','weekly','monthly') NOT NULL DEFAULT 'daily' COMMENT 'Period type',
  `total_signals` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Total signals in period',
  `winning_signals` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Winning signals count',
  `losing_signals` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Losing signals count',
  `win_rate` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Win rate percentage',
  `avg_slippage` decimal(8,4) NOT NULL DEFAULT 0.0000 COMMENT 'Average slippage in pips',
  `max_slippage` decimal(8,4) NOT NULL DEFAULT 0.0000 COMMENT 'Maximum slippage in pips',
  `avg_latency_ms` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Average latency in milliseconds',
  `max_drawdown` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Maximum drawdown percentage',
  `reward_to_risk_ratio` decimal(8,4) NOT NULL DEFAULT 0.0000 COMMENT 'Reward to risk ratio',
  `sl_compliance_rate` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT 'SL compliance rate percentage',
  `performance_score` decimal(5,2) NOT NULL DEFAULT 50.00 COMMENT 'Performance score 0-100',
  `performance_score_previous` decimal(5,2) NOT NULL DEFAULT 50.00 COMMENT 'Previous performance score',
  `score_trend` enum('up','down','stable') NOT NULL DEFAULT 'stable' COMMENT 'Score trend',
  `calculated_at` timestamp NULL DEFAULT NULL COMMENT 'When metrics were calculated',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_provider_period` (`signal_provider_id`,`signal_provider_type`,`period_start`,`period_end`,`period_type`),
  KEY `idx_signal_provider` (`signal_provider_id`,`signal_provider_type`),
  KEY `idx_period` (`period_start`,`period_end`,`period_type`),
  KEY `idx_performance_score` (`performance_score`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_srm_signal_provider_metrics`
--

LOCK TABLES `sp_srm_signal_provider_metrics` WRITE;
/*!40000 ALTER TABLE `sp_srm_signal_provider_metrics` DISABLE KEYS */;
/*!40000 ALTER TABLE `sp_srm_signal_provider_metrics` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_subscribers`
--

DROP TABLE IF EXISTS `sp_subscribers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_subscribers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_subscribers`
--

LOCK TABLES `sp_subscribers` WRITE;
/*!40000 ALTER TABLE `sp_subscribers` DISABLE KEYS */;
/*!40000 ALTER TABLE `sp_subscribers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_template_backtests`
--

DROP TABLE IF EXISTS `sp_template_backtests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_template_backtests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `template_type` enum('bot','signal','complete') NOT NULL,
  `template_id` bigint(20) unsigned NOT NULL,
  `capital_initial` decimal(15,2) NOT NULL,
  `capital_final` decimal(15,2) NOT NULL,
  `net_profit_percent` decimal(10,2) NOT NULL,
  `win_rate` decimal(5,2) NOT NULL,
  `profit_factor` decimal(10,4) DEFAULT NULL,
  `max_drawdown` decimal(10,2) DEFAULT NULL,
  `total_trades` int(11) NOT NULL DEFAULT 0,
  `winning_trades` int(11) NOT NULL DEFAULT 0,
  `losing_trades` int(11) NOT NULL DEFAULT 0,
  `avg_win_percent` decimal(10,2) DEFAULT NULL,
  `avg_loss_percent` decimal(10,2) DEFAULT NULL,
  `backtest_period_start` timestamp NULL DEFAULT NULL,
  `backtest_period_end` timestamp NULL DEFAULT NULL,
  `symbols_tested` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`symbols_tested`)),
  `timeframes_tested` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`timeframes_tested`)),
  `detailed_results` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Trade-by-trade data' CHECK (json_valid(`detailed_results`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tmpl_backtest_type_id_idx` (`template_type`,`template_id`),
  KEY `tmpl_backtest_winrate_idx` (`win_rate`),
  KEY `tmpl_backtest_profit_idx` (`net_profit_percent`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_template_backtests`
--

LOCK TABLES `sp_template_backtests` WRITE;
/*!40000 ALTER TABLE `sp_template_backtests` DISABLE KEYS */;
/*!40000 ALTER TABLE `sp_template_backtests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_template_clones`
--

DROP TABLE IF EXISTS `sp_template_clones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_template_clones` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `template_type` enum('bot','signal','complete') NOT NULL,
  `template_id` bigint(20) unsigned NOT NULL,
  `original_id` bigint(20) unsigned NOT NULL COMMENT 'Points to original template',
  `cloned_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'User customizations' CHECK (json_valid(`cloned_config`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `custom_name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tmpl_clone_user_active_idx` (`user_id`,`is_active`),
  KEY `tmpl_clone_type_id_idx` (`template_type`,`template_id`),
  CONSTRAINT `sp_template_clones_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `sp_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_template_clones`
--

LOCK TABLES `sp_template_clones` WRITE;
/*!40000 ALTER TABLE `sp_template_clones` DISABLE KEYS */;
/*!40000 ALTER TABLE `sp_template_clones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_template_ratings`
--

DROP TABLE IF EXISTS `sp_template_ratings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_template_ratings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `template_type` enum('bot','signal','complete') NOT NULL,
  `template_id` bigint(20) unsigned NOT NULL,
  `rating` tinyint(4) NOT NULL COMMENT '1-5 stars',
  `review` text DEFAULT NULL,
  `verified_purchase` tinyint(1) NOT NULL DEFAULT 0,
  `helpful_votes` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_template_rating` (`user_id`,`template_type`,`template_id`),
  KEY `sp_template_ratings_template_type_template_id_rating_index` (`template_type`,`template_id`,`rating`),
  CONSTRAINT `sp_template_ratings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `sp_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_template_ratings`
--

LOCK TABLES `sp_template_ratings` WRITE;
/*!40000 ALTER TABLE `sp_template_ratings` DISABLE KEYS */;
/*!40000 ALTER TABLE `sp_template_ratings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_templates`
--

DROP TABLE IF EXISTS `sp_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sp_templates_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_templates`
--

LOCK TABLES `sp_templates` WRITE;
/*!40000 ALTER TABLE `sp_templates` DISABLE KEYS */;
INSERT INTO `sp_templates` VALUES (1,'password_reset','Password Reset','2025-12-04 20:58:24','2025-12-04 20:58:24'),(2,'payment_successfull','Payment Successful','2025-12-04 20:58:24','2025-12-04 20:58:24'),(3,'payment_received','Payment Received','2025-12-04 20:58:24','2025-12-04 20:58:24'),(4,'verify_email','Verify Email','2025-12-04 20:58:24','2025-12-04 20:58:24'),(5,'payment_confirmed','Payment Confirmed','2025-12-04 20:58:24','2025-12-04 20:58:24'),(6,'payment_rejected','Payment Rejected','2025-12-04 20:58:24','2025-12-04 20:58:24'),(7,'withdraw_accepted','Withdrawal Accepted','2025-12-04 20:58:24','2025-12-04 20:58:24'),(8,'withdraw_rejected','Withdrawal Rejected','2025-12-04 20:58:24','2025-12-04 20:58:24'),(9,'refer_commission','Referral Commission','2025-12-04 20:58:24','2025-12-04 20:58:24'),(10,'send_money','Money Sent','2025-12-04 20:58:24','2025-12-04 20:58:24'),(11,'receive_money','Money Received','2025-12-04 20:58:24','2025-12-04 20:58:24'),(12,'plan_subscription','Plan Subscription','2025-12-04 20:58:24','2025-12-04 20:58:24'),(13,'Signal','New Signal Arrived','2025-12-04 20:58:24','2025-12-04 20:58:24');
/*!40000 ALTER TABLE `sp_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_ticket_replies`
--

DROP TABLE IF EXISTS `sp_ticket_replies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_ticket_replies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ticket_id` bigint(20) unsigned NOT NULL,
  `admin_id` bigint(20) unsigned NOT NULL,
  `message` text DEFAULT NULL,
  `file` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_ticket_replies`
--

LOCK TABLES `sp_ticket_replies` WRITE;
/*!40000 ALTER TABLE `sp_ticket_replies` DISABLE KEYS */;
/*!40000 ALTER TABLE `sp_ticket_replies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_tickets`
--

DROP TABLE IF EXISTS `sp_tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_tickets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `reply_id` bigint(20) unsigned NOT NULL,
  `support_id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `status` int(11) NOT NULL COMMENT '1=closed, 2=> pending, 3=> answered',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_tickets`
--

LOCK TABLES `sp_tickets` WRITE;
/*!40000 ALTER TABLE `sp_tickets` DISABLE KEYS */;
/*!40000 ALTER TABLE `sp_tickets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_time_frames`
--

DROP TABLE IF EXISTS `sp_time_frames`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_time_frames` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `status` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sp_time_frames_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_time_frames`
--

LOCK TABLES `sp_time_frames` WRITE;
/*!40000 ALTER TABLE `sp_time_frames` DISABLE KEYS */;
INSERT INTO `sp_time_frames` VALUES (1,'1M',1,'2025-12-04 20:57:57','2025-12-04 20:57:57'),(2,'5M',1,'2025-12-04 20:57:57','2025-12-04 20:57:57'),(3,'15M',1,'2025-12-04 20:57:57','2025-12-04 20:57:57'),(4,'30M',1,'2025-12-04 20:57:57','2025-12-04 20:57:57'),(5,'1H',1,'2025-12-04 20:57:57','2025-12-04 20:57:57'),(6,'2H',1,'2025-12-04 20:57:57','2025-12-04 20:57:57'),(7,'4H',1,'2025-12-04 20:57:57','2025-12-04 20:57:57'),(8,'6H',1,'2025-12-04 20:57:58','2025-12-04 20:57:58'),(9,'8H',1,'2025-12-04 20:57:58','2025-12-04 20:57:58'),(10,'12H',1,'2025-12-04 20:57:58','2025-12-04 20:57:58'),(11,'1D',1,'2025-12-04 20:57:58','2025-12-04 20:57:58'),(12,'1W',1,'2025-12-04 20:57:58','2025-12-04 20:57:58'),(13,'1MO',1,'2025-12-04 20:57:58','2025-12-04 20:57:58');
/*!40000 ALTER TABLE `sp_time_frames` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_trader_leaderboard`
--

DROP TABLE IF EXISTS `sp_trader_leaderboard`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_trader_leaderboard` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `trader_id` bigint(20) unsigned NOT NULL,
  `rank` int(11) NOT NULL DEFAULT 0,
  `timeframe` enum('daily','weekly','monthly','all_time') NOT NULL DEFAULT 'all_time',
  `profit_percent` decimal(10,2) NOT NULL DEFAULT 0.00,
  `win_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `sharpe_ratio` decimal(10,4) DEFAULT NULL,
  `roi` decimal(10,2) DEFAULT NULL,
  `total_trades` int(11) NOT NULL DEFAULT 0,
  `followers_gained` int(11) NOT NULL DEFAULT 0,
  `avg_rating` decimal(3,2) NOT NULL DEFAULT 0.00,
  `calculated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `trader_lead_trader_tf_uniq` (`trader_id`,`timeframe`),
  KEY `trader_lead_tf_rank_idx` (`timeframe`,`rank`),
  KEY `trader_lead_tf_profit_idx` (`timeframe`,`profit_percent`),
  CONSTRAINT `sp_trader_leaderboard_trader_id_foreign` FOREIGN KEY (`trader_id`) REFERENCES `sp_trader_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_trader_leaderboard`
--

LOCK TABLES `sp_trader_leaderboard` WRITE;
/*!40000 ALTER TABLE `sp_trader_leaderboard` DISABLE KEYS */;
/*!40000 ALTER TABLE `sp_trader_leaderboard` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_trader_profiles`
--

DROP TABLE IF EXISTS `sp_trader_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_trader_profiles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `display_name` varchar(255) NOT NULL,
  `bio` text DEFAULT NULL,
  `avatar_url` varchar(255) DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `accepts_followers` tinyint(1) NOT NULL DEFAULT 1,
  `max_followers` int(11) DEFAULT NULL,
  `subscription_price` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '0=free',
  `currency` varchar(3) NOT NULL DEFAULT 'USD',
  `total_followers` int(11) NOT NULL DEFAULT 0,
  `total_profit_percent` decimal(10,2) NOT NULL DEFAULT 0.00,
  `win_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `avg_monthly_return` decimal(10,2) NOT NULL DEFAULT 0.00,
  `max_drawdown` decimal(10,2) NOT NULL DEFAULT 0.00,
  `trades_count` int(11) NOT NULL DEFAULT 0,
  `verified` tinyint(1) NOT NULL DEFAULT 0,
  `trading_style` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Markets, strategies, timeframes' CHECK (json_valid(`trading_style`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sp_trader_profiles_user_id_unique` (`user_id`),
  KEY `trader_public_ver_profit_idx` (`is_public`,`verified`,`total_profit_percent`),
  KEY `trader_winrate_idx` (`win_rate`),
  KEY `trader_followers_idx` (`total_followers`),
  CONSTRAINT `sp_trader_profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `sp_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_trader_profiles`
--

LOCK TABLES `sp_trader_profiles` WRITE;
/*!40000 ALTER TABLE `sp_trader_profiles` DISABLE KEYS */;
/*!40000 ALTER TABLE `sp_trader_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_trader_ratings`
--

DROP TABLE IF EXISTS `sp_trader_ratings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_trader_ratings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `trader_id` bigint(20) unsigned NOT NULL,
  `follower_id` bigint(20) unsigned NOT NULL,
  `rating` tinyint(4) NOT NULL COMMENT '1-5 stars',
  `review` text DEFAULT NULL,
  `verified_follower` tinyint(1) NOT NULL DEFAULT 0,
  `helpful_votes` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `trader_rating_uniq` (`trader_id`,`follower_id`),
  KEY `sp_trader_ratings_follower_id_foreign` (`follower_id`),
  KEY `trader_rating_trader_idx` (`trader_id`,`rating`),
  CONSTRAINT `sp_trader_ratings_follower_id_foreign` FOREIGN KEY (`follower_id`) REFERENCES `sp_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sp_trader_ratings_trader_id_foreign` FOREIGN KEY (`trader_id`) REFERENCES `sp_trader_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_trader_ratings`
--

LOCK TABLES `sp_trader_ratings` WRITE;
/*!40000 ALTER TABLE `sp_trader_ratings` DISABLE KEYS */;
/*!40000 ALTER TABLE `sp_trader_ratings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_trading_presets`
--

DROP TABLE IF EXISTS `sp_trading_presets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_trading_presets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `symbol` varchar(50) DEFAULT NULL COMMENT 'Logical symbol (e.g., XAUUSD)',
  `timeframe` varchar(10) DEFAULT NULL COMMENT 'M1, M5, M15, H1, etc.',
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Array of tags: ["scalping", "xau", "layering"]' CHECK (json_valid(`tags`)),
  `position_size_mode` enum('FIXED','RISK_PERCENT') NOT NULL DEFAULT 'RISK_PERCENT',
  `fixed_lot` decimal(10,2) DEFAULT NULL,
  `risk_per_trade_pct` decimal(5,2) DEFAULT NULL COMMENT 'Percentage of equity',
  `max_positions` int(10) unsigned NOT NULL DEFAULT 1,
  `max_positions_per_symbol` int(10) unsigned NOT NULL DEFAULT 1,
  `equity_dynamic_mode` enum('NONE','LINEAR','STEP') NOT NULL DEFAULT 'NONE',
  `equity_base` decimal(15,2) DEFAULT NULL COMMENT 'Base equity amount',
  `equity_step_factor` decimal(5,2) DEFAULT NULL COMMENT 'Multiplier for step mode',
  `risk_min_pct` decimal(5,2) DEFAULT NULL,
  `risk_max_pct` decimal(5,2) DEFAULT NULL,
  `sl_mode` enum('PIPS','R_MULTIPLE','STRUCTURE') NOT NULL DEFAULT 'PIPS',
  `sl_pips` int(11) DEFAULT NULL,
  `sl_r_multiple` decimal(5,2) DEFAULT NULL COMMENT 'R multiple (e.g., 1.5R)',
  `tp_mode` enum('DISABLED','SINGLE','MULTI') NOT NULL DEFAULT 'SINGLE',
  `tp1_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `tp1_rr` decimal(5,2) DEFAULT NULL COMMENT 'Risk:Reward ratio',
  `tp1_close_pct` decimal(5,2) DEFAULT NULL COMMENT 'Percentage to close at TP1',
  `tp2_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `tp2_rr` decimal(5,2) DEFAULT NULL,
  `tp2_close_pct` decimal(5,2) DEFAULT NULL,
  `tp3_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `tp3_rr` decimal(5,2) DEFAULT NULL,
  `tp3_close_pct` decimal(5,2) DEFAULT NULL,
  `close_remaining_at_tp3` tinyint(1) NOT NULL DEFAULT 0,
  `be_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `be_trigger_rr` decimal(5,2) DEFAULT NULL COMMENT 'Trigger BE when this RR is reached',
  `be_offset_pips` int(11) DEFAULT NULL COMMENT 'Offset from entry (can be negative)',
  `ts_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `ts_mode` enum('STEP_PIPS','STEP_ATR','CHANDELIER') NOT NULL DEFAULT 'STEP_PIPS',
  `ts_trigger_rr` decimal(5,2) DEFAULT NULL COMMENT 'Start trailing after this RR',
  `ts_step_pips` int(11) DEFAULT NULL,
  `ts_atr_period` int(11) DEFAULT NULL COMMENT 'For ATR mode',
  `ts_atr_multiplier` decimal(5,2) DEFAULT NULL,
  `ts_update_interval_sec` int(11) DEFAULT NULL COMMENT 'Update frequency',
  `layering_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `max_layers_per_symbol` int(10) unsigned NOT NULL DEFAULT 3,
  `layer_distance_pips` int(11) DEFAULT NULL,
  `layer_martingale_mode` enum('NONE','MULTIPLY','ADD') NOT NULL DEFAULT 'NONE',
  `layer_martingale_factor` decimal(5,2) DEFAULT NULL,
  `layer_max_total_risk_pct` decimal(5,2) DEFAULT NULL,
  `hedging_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `hedge_trigger_drawdown_pct` decimal(5,2) DEFAULT NULL,
  `hedge_distance_pips` int(11) DEFAULT NULL,
  `hedge_lot_factor` decimal(5,2) DEFAULT NULL COMMENT 'Multiplier for hedge lot size',
  `auto_close_on_candle_close` tinyint(1) NOT NULL DEFAULT 0,
  `auto_close_timeframe` varchar(10) DEFAULT NULL COMMENT 'M5, M15, etc.',
  `hold_max_candles` int(11) DEFAULT NULL,
  `trading_hours_start` time DEFAULT NULL COMMENT 'HH:MM format',
  `trading_hours_end` time DEFAULT NULL,
  `trading_timezone` varchar(50) NOT NULL DEFAULT 'SERVER',
  `trading_days_mask` int(10) unsigned NOT NULL DEFAULT 127 COMMENT 'Bitmask: 1=Mon, 2=Tue, 4=Wed, 8=Thu, 16=Fri, 32=Sat, 64=Sun',
  `session_profile` enum('ASIA','LONDON','NY','CUSTOM') NOT NULL DEFAULT 'CUSTOM',
  `only_trade_in_session` tinyint(1) NOT NULL DEFAULT 0,
  `weekly_target_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `weekly_target_profit_pct` decimal(5,2) DEFAULT NULL,
  `weekly_reset_day` tinyint(3) unsigned DEFAULT NULL COMMENT '1=Monday, 7=Sunday',
  `auto_stop_on_weekly_target` tinyint(1) NOT NULL DEFAULT 0,
  `created_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `filter_strategy_id` bigint(20) unsigned DEFAULT NULL,
  `ai_model_profile_id` bigint(20) unsigned DEFAULT NULL,
  `ai_confirmation_mode` enum('NONE','REQUIRED','ADVISORY') NOT NULL DEFAULT 'NONE',
  `ai_min_safety_score` decimal(5,2) DEFAULT NULL,
  `ai_position_mgmt_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `is_default_template` tinyint(1) NOT NULL DEFAULT 0,
  `clonable` tinyint(1) NOT NULL DEFAULT 1,
  `visibility` enum('PRIVATE','PUBLIC_MARKETPLACE') NOT NULL DEFAULT 'PRIVATE',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sp_trading_presets_created_by_user_id_index` (`created_by_user_id`),
  KEY `sp_trading_presets_visibility_index` (`visibility`),
  KEY `sp_trading_presets_enabled_index` (`enabled`),
  KEY `sp_trading_presets_is_default_template_index` (`is_default_template`),
  KEY `sp_trading_presets_symbol_index` (`symbol`),
  KEY `sp_trading_presets_timeframe_index` (`timeframe`),
  KEY `sp_trading_presets_filter_strategy_id_index` (`filter_strategy_id`),
  KEY `sp_trading_presets_ai_model_profile_id_index` (`ai_model_profile_id`),
  KEY `sp_trading_presets_ai_confirmation_mode_index` (`ai_confirmation_mode`),
  CONSTRAINT `sp_trading_presets_ai_model_profile_id_foreign` FOREIGN KEY (`ai_model_profile_id`) REFERENCES `sp_ai_model_profiles` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sp_trading_presets_created_by_user_id_foreign` FOREIGN KEY (`created_by_user_id`) REFERENCES `sp_users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sp_trading_presets_filter_strategy_id_foreign` FOREIGN KEY (`filter_strategy_id`) REFERENCES `sp_filter_strategies` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_trading_presets`
--

LOCK TABLES `sp_trading_presets` WRITE;
/*!40000 ALTER TABLE `sp_trading_presets` DISABLE KEYS */;
INSERT INTO `sp_trading_presets` VALUES (1,'Conservative Scalper','Low risk, quick profits. Perfect for beginners who want to start trading safely with minimal risk per trade.',NULL,NULL,1,'[\"scalping\",\"conservative\",\"beginner\",\"low-risk\"]','RISK_PERCENT',NULL,0.50,1,1,'NONE',NULL,NULL,NULL,NULL,'PIPS',20,NULL,'SINGLE',1,1.50,100.00,0,NULL,NULL,0,NULL,NULL,0,0,NULL,NULL,0,'STEP_PIPS',NULL,NULL,NULL,NULL,NULL,0,3,NULL,'NONE',NULL,NULL,0,NULL,NULL,NULL,0,NULL,NULL,'08:00:00','18:00:00','SERVER',31,'CUSTOM',0,1,2.00,1,1,NULL,NULL,NULL,'NONE',NULL,0,1,1,'PUBLIC_MARKETPLACE','2025-12-04 20:58:25','2025-12-04 20:58:25',NULL),(2,'Swing Trader','Medium-term trading strategy with multiple take profit levels. Uses break-even and trailing stop for better risk management.',NULL,NULL,1,'[\"swing\",\"medium-term\",\"multi-tp\",\"break-even\"]','RISK_PERCENT',NULL,1.00,3,1,'NONE',NULL,NULL,NULL,NULL,'PIPS',50,NULL,'MULTI',1,2.00,30.00,1,3.00,40.00,1,5.00,30.00,0,1,1.50,0,1,'STEP_PIPS',1.50,20,NULL,NULL,60,0,3,NULL,'NONE',NULL,NULL,0,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,'SERVER',31,'CUSTOM',0,0,NULL,NULL,0,NULL,NULL,NULL,'NONE',NULL,0,1,1,'PUBLIC_MARKETPLACE','2025-12-04 20:58:25','2025-12-04 20:58:25',NULL),(3,'Aggressive Day Trader','High risk, high reward strategy for experienced traders. Features layering and advanced trailing stop.',NULL,NULL,1,'[\"aggressive\",\"day-trading\",\"layering\",\"high-risk\"]','RISK_PERCENT',NULL,2.00,5,2,'NONE',NULL,NULL,NULL,NULL,'PIPS',30,NULL,'MULTI',1,1.50,50.00,1,2.50,30.00,1,4.00,20.00,0,0,NULL,NULL,1,'STEP_ATR',1.00,NULL,14,1.50,30,1,3,20,'MULTIPLY',1.50,10.00,0,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,'SERVER',31,'CUSTOM',0,1,5.00,1,0,NULL,NULL,NULL,'NONE',NULL,0,1,1,'PUBLIC_MARKETPLACE','2025-12-04 20:58:25','2025-12-04 20:58:25',NULL),(4,'Safe Long-Term','Very conservative strategy for long-term traders. Minimal risk with high reward potential.',NULL,NULL,1,'[\"conservative\",\"long-term\",\"safe\",\"low-risk\"]','RISK_PERCENT',NULL,0.25,1,1,'NONE',NULL,NULL,NULL,NULL,'PIPS',100,NULL,'SINGLE',1,3.00,100.00,0,NULL,NULL,0,NULL,NULL,0,1,2.00,0,0,'STEP_PIPS',NULL,NULL,NULL,NULL,NULL,0,3,NULL,'NONE',NULL,NULL,0,NULL,NULL,NULL,0,NULL,NULL,'08:00:00','22:00:00','SERVER',31,'CUSTOM',0,1,1.00,1,1,NULL,NULL,NULL,'NONE',NULL,0,1,1,'PUBLIC_MARKETPLACE','2025-12-04 20:58:25','2025-12-04 20:58:25',NULL),(5,'Grid Trading','Grid/martingale strategy with layering and hedging. Suitable for range-bound markets.',NULL,NULL,1,'[\"grid\",\"martingale\",\"layering\",\"hedging\"]','FIXED',0.01,NULL,5,5,'NONE',NULL,NULL,NULL,NULL,'PIPS',50,NULL,'SINGLE',1,1.00,100.00,0,NULL,NULL,0,NULL,NULL,0,0,NULL,NULL,0,'STEP_PIPS',NULL,NULL,NULL,NULL,NULL,1,5,15,'MULTIPLY',2.00,5.00,1,2.00,20,1.00,0,NULL,NULL,NULL,NULL,'SERVER',31,'CUSTOM',0,1,3.00,1,0,NULL,NULL,NULL,'NONE',NULL,0,1,1,'PUBLIC_MARKETPLACE','2025-12-04 20:58:25','2025-12-04 20:58:25',NULL),(6,'Breakout Trader','Breakout/volatility strategy with structure-based SL and Chandelier trailing stop.',NULL,NULL,1,'[\"breakout\",\"volatility\",\"structure\",\"chandelier\"]','RISK_PERCENT',NULL,1.50,2,1,'NONE',NULL,NULL,NULL,NULL,'STRUCTURE',NULL,NULL,'MULTI',1,2.00,40.00,1,4.00,40.00,1,6.00,20.00,0,1,1.00,0,1,'CHANDELIER',1.00,NULL,22,3.00,60,0,3,NULL,'NONE',NULL,NULL,0,NULL,NULL,NULL,0,NULL,NULL,'08:00:00','17:00:00','SERVER',31,'CUSTOM',1,0,NULL,NULL,0,NULL,NULL,NULL,'NONE',NULL,0,1,1,'PUBLIC_MARKETPLACE','2025-12-04 20:58:25','2025-12-04 20:58:25',NULL);
/*!40000 ALTER TABLE `sp_trading_presets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_transactions`
--

DROP TABLE IF EXISTS `sp_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `trx` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(28,8) NOT NULL,
  `charge` decimal(28,8) NOT NULL,
  `details` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_transactions`
--

LOCK TABLES `sp_transactions` WRITE;
/*!40000 ALTER TABLE `sp_transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sp_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_translation_settings`
--

DROP TABLE IF EXISTS `sp_translation_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_translation_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ai_connection_id` bigint(20) unsigned NOT NULL,
  `fallback_connection_id` bigint(20) unsigned DEFAULT NULL,
  `batch_size` int(11) NOT NULL DEFAULT 10,
  `delay_between_requests_ms` int(11) NOT NULL DEFAULT 100,
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`settings`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sp_translation_settings_fallback_connection_id_foreign` (`fallback_connection_id`),
  KEY `sp_translation_settings_ai_connection_id_index` (`ai_connection_id`),
  CONSTRAINT `sp_translation_settings_ai_connection_id_foreign` FOREIGN KEY (`ai_connection_id`) REFERENCES `sp_ai_connections` (`id`),
  CONSTRAINT `sp_translation_settings_fallback_connection_id_foreign` FOREIGN KEY (`fallback_connection_id`) REFERENCES `sp_ai_connections` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_translation_settings`
--

LOCK TABLES `sp_translation_settings` WRITE;
/*!40000 ALTER TABLE `sp_translation_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `sp_translation_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_user_logs`
--

DROP TABLE IF EXISTS `sp_user_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_user_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `browser` varchar(255) NOT NULL,
  `system` varchar(255) NOT NULL,
  `country` varchar(255) NOT NULL,
  `ip` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_user_logs`
--

LOCK TABLES `sp_user_logs` WRITE;
/*!40000 ALTER TABLE `sp_user_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `sp_user_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_user_signals`
--

DROP TABLE IF EXISTS `sp_user_signals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_user_signals` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `signal_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sp_user_signals_user_id_signal_id_unique` (`user_id`,`signal_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_user_signals`
--

LOCK TABLES `sp_user_signals` WRITE;
/*!40000 ALTER TABLE `sp_user_signals` DISABLE KEYS */;
/*!40000 ALTER TABLE `sp_user_signals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_users`
--

DROP TABLE IF EXISTS `sp_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `default_preset_id` bigint(20) unsigned DEFAULT NULL,
  `ref_id` bigint(20) unsigned NOT NULL,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `address` text DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `balance` decimal(28,8) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `is_email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `is_sms_verified` tinyint(1) NOT NULL DEFAULT 0,
  `is_kyc_verified` tinyint(1) NOT NULL DEFAULT 0,
  `email_verification_code` varchar(255) DEFAULT NULL,
  `sms_verification_code` varchar(255) DEFAULT NULL,
  `login_at` datetime NOT NULL,
  `kyc_information` text DEFAULT NULL,
  `facebook_id` varchar(255) DEFAULT NULL,
  `google_id` varchar(255) DEFAULT NULL,
  `status` tinyint(1) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `telegram_chat_id` varchar(255) DEFAULT NULL,
  `phone_country_code` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sp_users_username_unique` (`username`),
  UNIQUE KEY `sp_users_email_unique` (`email`),
  UNIQUE KEY `sp_users_phone_unique` (`phone`),
  KEY `sp_users_default_preset_id_index` (`default_preset_id`),
  KEY `sp_users_telegram_chat_id_index` (`telegram_chat_id`),
  CONSTRAINT `sp_users_default_preset_id_foreign` FOREIGN KEY (`default_preset_id`) REFERENCES `sp_trading_presets` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_users`
--

LOCK TABLES `sp_users` WRITE;
/*!40000 ALTER TABLE `sp_users` DISABLE KEYS */;
/*!40000 ALTER TABLE `sp_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_withdraw_gateways`
--

DROP TABLE IF EXISTS `sp_withdraw_gateways`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_withdraw_gateways` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `min_withdraw_amount` decimal(28,8) NOT NULL,
  `max_withdraw_amount` decimal(28,8) NOT NULL,
  `charge` decimal(28,8) NOT NULL,
  `type` varchar(255) NOT NULL,
  `instruction` text DEFAULT NULL,
  `status` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sp_withdraw_gateways_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_withdraw_gateways`
--

LOCK TABLES `sp_withdraw_gateways` WRITE;
/*!40000 ALTER TABLE `sp_withdraw_gateways` DISABLE KEYS */;
INSERT INTO `sp_withdraw_gateways` VALUES (1,'Bank',5.00000000,500.00000000,1.00000000,'fixed',NULL,1,'2025-12-04 20:57:57','2025-12-04 20:57:57');
/*!40000 ALTER TABLE `sp_withdraw_gateways` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_withdraws`
--

DROP TABLE IF EXISTS `sp_withdraws`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_withdraws` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `withdraw_method_id` bigint(20) unsigned NOT NULL,
  `trx` varchar(255) NOT NULL,
  `withdraw_amount` decimal(28,8) NOT NULL,
  `withdraw_charge` decimal(28,8) NOT NULL,
  `total` decimal(28,8) NOT NULL,
  `proof` text DEFAULT NULL,
  `reject_reason` text DEFAULT NULL,
  `status` tinyint(1) NOT NULL COMMENT '0=>pending, 1=>approved, 2 => reject',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sp_withdraws_trx_unique` (`trx`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_withdraws`
--

LOCK TABLES `sp_withdraws` WRITE;
/*!40000 ALTER TABLE `sp_withdraws` DISABLE KEYS */;
/*!40000 ALTER TABLE `sp_withdraws` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-12-05 11:58:50

mysqldump: [Warning] Using a password on the command line interface can be insecure.
-- Warning: column statistics not supported by the server.
-- MySQL dump 10.13  Distrib 8.0.44, for Linux (x86_64)
--
-- Host: aviano-db.id.rapidplex.com    Database: algotrad_signals
-- ------------------------------------------------------
-- Server version	5.5.5-10.11.15-MariaDB-ubu2404

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
INSERT INTO `sp_ai_providers` VALUES (1,'OpenAI','openai','active',NULL,'2025-12-05 08:58:13','2025-12-05 08:58:13'),(2,'Google Gemini','gemini','active',NULL,'2025-12-05 08:58:13','2025-12-05 08:58:13'),(3,'OpenRouter','openrouter','active',NULL,'2025-12-05 08:58:13','2025-12-05 08:58:13');
/*!40000 ALTER TABLE `sp_ai_providers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_backtest_results`
--

DROP TABLE IF EXISTS `sp_backtest_results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_backtest_results` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `backtest_id` bigint(20) unsigned NOT NULL,
  `total_trades` int(11) NOT NULL DEFAULT 0,
  `winning_trades` int(11) NOT NULL DEFAULT 0,
  `losing_trades` int(11) NOT NULL DEFAULT 0,
  `win_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `total_profit` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `total_loss` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `net_profit` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `final_balance` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `return_percent` decimal(10,4) NOT NULL DEFAULT 0.0000,
  `profit_factor` decimal(10,4) NOT NULL DEFAULT 0.0000,
  `sharpe_ratio` decimal(10,4) DEFAULT NULL,
  `max_drawdown` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `max_drawdown_percent` decimal(10,4) NOT NULL DEFAULT 0.0000,
  `avg_win` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `avg_loss` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `largest_win` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `largest_loss` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `consecutive_wins` int(11) NOT NULL DEFAULT 0,
  `consecutive_losses` int(11) NOT NULL DEFAULT 0,
  `equity_curve` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Array of balance over time' CHECK (json_valid(`equity_curve`)),
  `trade_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Individual trade results' CHECK (json_valid(`trade_details`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sp_backtest_results_backtest_id_unique` (`backtest_id`),
  KEY `sp_backtest_results_backtest_id_index` (`backtest_id`),
  CONSTRAINT `sp_backtest_results_backtest_id_foreign` FOREIGN KEY (`backtest_id`) REFERENCES `sp_backtests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_backtest_results`
--

LOCK TABLES `sp_backtest_results` WRITE;
/*!40000 ALTER TABLE `sp_backtest_results` DISABLE KEYS */;
/*!40000 ALTER TABLE `sp_backtest_results` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_backtests`
--

DROP TABLE IF EXISTS `sp_backtests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_backtests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `admin_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `filter_strategy_id` bigint(20) unsigned DEFAULT NULL,
  `ai_model_profile_id` bigint(20) unsigned DEFAULT NULL,
  `preset_id` bigint(20) unsigned NOT NULL COMMENT 'Trading preset for position sizing',
  `symbol` varchar(255) NOT NULL,
  `timeframe` varchar(255) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `initial_balance` decimal(20,8) NOT NULL DEFAULT 10000.00000000,
  `status` enum('pending','running','completed','failed') NOT NULL DEFAULT 'pending',
  `progress_percent` int(11) NOT NULL DEFAULT 0,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sp_backtests_filter_strategy_id_foreign` (`filter_strategy_id`),
  KEY `sp_backtests_ai_model_profile_id_foreign` (`ai_model_profile_id`),
  KEY `sp_backtests_preset_id_foreign` (`preset_id`),
  KEY `sp_backtests_user_id_index` (`user_id`),
  KEY `sp_backtests_admin_id_index` (`admin_id`),
  KEY `sp_backtests_status_index` (`status`),
  KEY `sp_backtests_symbol_index` (`symbol`),
  CONSTRAINT `sp_backtests_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `sp_admins` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sp_backtests_ai_model_profile_id_foreign` FOREIGN KEY (`ai_model_profile_id`) REFERENCES `sp_ai_model_profiles` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sp_backtests_filter_strategy_id_foreign` FOREIGN KEY (`filter_strategy_id`) REFERENCES `sp_filter_strategies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sp_backtests_preset_id_foreign` FOREIGN KEY (`preset_id`) REFERENCES `sp_trading_presets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sp_backtests_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `sp_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_backtests`
--

LOCK TABLES `sp_backtests` WRITE;
/*!40000 ALTER TABLE `sp_backtests` DISABLE KEYS */;
/*!40000 ALTER TABLE `sp_backtests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_bot_templates`
--

DROP TABLE IF EXISTS `sp_bot_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
  `bot_url` varchar(255) DEFAULT NULL,
  `telegram_token` varchar(255) DEFAULT NULL,
  `allow_telegram` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_configurations`
--

LOCK TABLES `sp_configurations` WRITE;
/*!40000 ALTER TABLE `sp_configurations` DISABLE KEYS */;
INSERT INTO `sp_configurations` VALUES (1,'AlgoExpertHub','default','default','usd',10,2,'izi','logo.png','favicon.png',1,'{\"heading_font_url\":\"https:\\/\\/fonts.googleapis.com\\/css2?family=Roboto&display=swap\",\"heading_font_family\":\"\'Roboto\',\'sans-serif\'\",\"paragraph_font_url\":\"https:\\/\\/fonts.googleapis.com\\/css2?family=Roboto&display=swap\",\"paragraph_font_family\":\"\'Roboto\',\'sans-serif\'\"}',0,0,NULL,0,0,NULL,1,'Lorem, ipsum.','Lorem ipsum dolor sit, amet consectetur adipisicing elit. Delectus, autem.',0,NULL,NULL,0,NULL,'[\"trading\",\"signals\",\"forex\",\"crypto\",\"stocks\"]','Lorem ipsum dolor sit amet consectetur adipisicing elit. Adipisci delectus deleniti temporibus quas veritatis eaque quae iste excepturi natus unde magnam nostrum, officiis tenetur ipsam ratione accusamus nulla esse ab, cumque maxime fugiat modi. Unde dolore nisi nostrum, accusamus eum perferendis distinctio molestiae quam possimus cupiditate, velit ut consequatur eius?',NULL,0.00000000,NULL,NULL,NULL,NULL,0.00000000,NULL,0.00000000,0.00000000,0,0,NULL,NULL,NULL,NULL,NULL,2,NULL,NULL,0,'2025-12-04 20:57:56','2025-12-04 20:57:56'),(2,'AlgoExpertHub','default','default','usd',10,2,'izi','logo.png','favicon.png',1,'{\"heading_font_url\":\"https:\\/\\/fonts.googleapis.com\\/css2?family=Roboto&display=swap\",\"heading_font_family\":\"\'Roboto\',\'sans-serif\'\",\"paragraph_font_url\":\"https:\\/\\/fonts.googleapis.com\\/css2?family=Roboto&display=swap\",\"paragraph_font_family\":\"\'Roboto\',\'sans-serif\'\"}',0,0,NULL,0,0,NULL,1,'Lorem, ipsum.','Lorem ipsum dolor sit, amet consectetur adipisicing elit. Delectus, autem.',0,NULL,NULL,0,NULL,'[\"trading\",\"signals\",\"forex\",\"crypto\",\"stocks\"]','Lorem ipsum dolor sit amet consectetur adipisicing elit. Adipisci delectus deleniti temporibus quas veritatis eaque quae iste excepturi natus unde magnam nostrum, officiis tenetur ipsam ratione accusamus nulla esse ab, cumque maxime fugiat modi. Unde dolore nisi nostrum, accusamus eum perferendis distinctio molestiae quam possimus cupiditate, velit ut consequatur eius?',NULL,0.00000000,NULL,NULL,NULL,NULL,0.00000000,NULL,0.00000000,0.00000000,0,0,NULL,NULL,NULL,NULL,NULL,2,NULL,NULL,0,'2025-12-04 20:58:07','2025-12-04 20:58:07'),(3,'AlgoExpertHub','default','default','usd',10,2,'izi','logo.png','favicon.png',1,'{\"heading_font_url\":\"https:\\/\\/fonts.googleapis.com\\/css2?family=Roboto&display=swap\",\"heading_font_family\":\"\'Roboto\',\'sans-serif\'\",\"paragraph_font_url\":\"https:\\/\\/fonts.googleapis.com\\/css2?family=Roboto&display=swap\",\"paragraph_font_family\":\"\'Roboto\',\'sans-serif\'\"}',0,0,NULL,0,0,NULL,1,'Lorem, ipsum.','Lorem ipsum dolor sit, amet consectetur adipisicing elit. Delectus, autem.',0,NULL,NULL,0,NULL,'[\"trading\",\"signals\",\"forex\",\"crypto\",\"stocks\"]','Lorem ipsum dolor sit amet consectetur adipisicing elit. Adipisci delectus deleniti temporibus quas veritatis eaque quae iste excepturi natus unde magnam nostrum, officiis tenetur ipsam ratione accusamus nulla esse ab, cumque maxime fugiat modi. Unde dolore nisi nostrum, accusamus eum perferendis distinctio molestiae quam possimus cupiditate, velit ut consequatur eius?',NULL,0.00000000,NULL,NULL,NULL,NULL,0.00000000,NULL,0.00000000,0.00000000,0,0,NULL,NULL,NULL,NULL,NULL,2,NULL,NULL,0,'2025-12-04 20:58:24','2025-12-04 20:58:24'),(4,'AlgoExpertHub','default','default','usd',10,2,'izi','logo.png','favicon.png',1,'{\"heading_font_url\":\"https:\\/\\/fonts.googleapis.com\\/css2?family=Roboto&display=swap\",\"heading_font_family\":\"\'Roboto\',\'sans-serif\'\",\"paragraph_font_url\":\"https:\\/\\/fonts.googleapis.com\\/css2?family=Roboto&display=swap\",\"paragraph_font_family\":\"\'Roboto\',\'sans-serif\'\"}',0,0,NULL,0,0,NULL,1,'Lorem, ipsum.','Lorem ipsum dolor sit, amet consectetur adipisicing elit. Delectus, autem.',0,NULL,NULL,0,NULL,'[\"trading\",\"signals\",\"forex\",\"crypto\",\"stocks\"]','Lorem ipsum dolor sit amet consectetur adipisicing elit. Adipisci delectus deleniti temporibus quas veritatis eaque quae iste excepturi natus unde magnam nostrum, officiis tenetur ipsam ratione accusamus nulla esse ab, cumque maxime fugiat modi. Unde dolore nisi nostrum, accusamus eum perferendis distinctio molestiae quam possimus cupiditate, velit ut consequatur eius?',NULL,0.00000000,NULL,NULL,NULL,NULL,0.00000000,NULL,0.00000000,0.00000000,0,0,NULL,NULL,NULL,NULL,NULL,2,NULL,NULL,0,'2025-12-05 08:41:33','2025-12-05 08:41:33'),(5,'AlgoExpertHub','default','default','usd',10,2,'izi','logo.png','favicon.png',1,'{\"heading_font_url\":\"https:\\/\\/fonts.googleapis.com\\/css2?family=Roboto&display=swap\",\"heading_font_family\":\"\'Roboto\',\'sans-serif\'\",\"paragraph_font_url\":\"https:\\/\\/fonts.googleapis.com\\/css2?family=Roboto&display=swap\",\"paragraph_font_family\":\"\'Roboto\',\'sans-serif\'\"}',0,0,NULL,0,0,NULL,1,'Lorem, ipsum.','Lorem ipsum dolor sit, amet consectetur adipisicing elit. Delectus, autem.',0,NULL,NULL,0,NULL,'[\"trading\",\"signals\",\"forex\",\"crypto\",\"stocks\"]','Lorem ipsum dolor sit amet consectetur adipisicing elit. Adipisci delectus deleniti temporibus quas veritatis eaque quae iste excepturi natus unde magnam nostrum, officiis tenetur ipsam ratione accusamus nulla esse ab, cumque maxime fugiat modi. Unde dolore nisi nostrum, accusamus eum perferendis distinctio molestiae quam possimus cupiditate, velit ut consequatur eius?',NULL,0.00000000,NULL,NULL,NULL,NULL,0.00000000,NULL,0.00000000,0.00000000,0,0,NULL,NULL,NULL,NULL,NULL,2,NULL,NULL,0,'2025-12-05 08:41:37','2025-12-05 08:41:37'),(6,'AlgoExpertHub','default','default','usd',10,2,'izi','logo.png','favicon.png',1,'{\"heading_font_url\":\"https:\\/\\/fonts.googleapis.com\\/css2?family=Roboto&display=swap\",\"heading_font_family\":\"\'Roboto\',\'sans-serif\'\",\"paragraph_font_url\":\"https:\\/\\/fonts.googleapis.com\\/css2?family=Roboto&display=swap\",\"paragraph_font_family\":\"\'Roboto\',\'sans-serif\'\"}',0,0,NULL,0,0,NULL,1,'Lorem, ipsum.','Lorem ipsum dolor sit, amet consectetur adipisicing elit. Delectus, autem.',0,NULL,NULL,0,NULL,'[\"trading\",\"signals\",\"forex\",\"crypto\",\"stocks\"]','Lorem ipsum dolor sit amet consectetur adipisicing elit. Adipisci delectus deleniti temporibus quas veritatis eaque quae iste excepturi natus unde magnam nostrum, officiis tenetur ipsam ratione accusamus nulla esse ab, cumque maxime fugiat modi. Unde dolore nisi nostrum, accusamus eum perferendis distinctio molestiae quam possimus cupiditate, velit ut consequatur eius?',NULL,0.00000000,NULL,NULL,NULL,NULL,0.00000000,NULL,0.00000000,0.00000000,0,0,NULL,NULL,NULL,NULL,NULL,2,NULL,NULL,0,'2025-12-05 08:45:24','2025-12-05 08:45:24'),(7,'AlgoExpertHub','default','default','usd',10,2,'izi','logo.png','favicon.png',1,'{\"heading_font_url\":\"https:\\/\\/fonts.googleapis.com\\/css2?family=Roboto&display=swap\",\"heading_font_family\":\"\'Roboto\',\'sans-serif\'\",\"paragraph_font_url\":\"https:\\/\\/fonts.googleapis.com\\/css2?family=Roboto&display=swap\",\"paragraph_font_family\":\"\'Roboto\',\'sans-serif\'\"}',0,0,NULL,0,0,NULL,1,'Lorem, ipsum.','Lorem ipsum dolor sit, amet consectetur adipisicing elit. Delectus, autem.',0,NULL,NULL,0,NULL,'[\"trading\",\"signals\",\"forex\",\"crypto\",\"stocks\"]','Lorem ipsum dolor sit amet consectetur adipisicing elit. Adipisci delectus deleniti temporibus quas veritatis eaque quae iste excepturi natus unde magnam nostrum, officiis tenetur ipsam ratione accusamus nulla esse ab, cumque maxime fugiat modi. Unde dolore nisi nostrum, accusamus eum perferendis distinctio molestiae quam possimus cupiditate, velit ut consequatur eius?',NULL,0.00000000,NULL,NULL,NULL,NULL,0.00000000,NULL,0.00000000,0.00000000,0,0,NULL,NULL,NULL,NULL,NULL,2,NULL,NULL,0,'2025-12-05 08:47:13','2025-12-05 08:47:13'),(8,'AlgoExpertHub','default','default','usd',10,2,'izi','logo.png','favicon.png',1,'{\"heading_font_url\":\"https:\\/\\/fonts.googleapis.com\\/css2?family=Roboto&display=swap\",\"heading_font_family\":\"\'Roboto\',\'sans-serif\'\",\"paragraph_font_url\":\"https:\\/\\/fonts.googleapis.com\\/css2?family=Roboto&display=swap\",\"paragraph_font_family\":\"\'Roboto\',\'sans-serif\'\"}',0,0,NULL,0,0,NULL,1,'Lorem, ipsum.','Lorem ipsum dolor sit, amet consectetur adipisicing elit. Delectus, autem.',0,NULL,NULL,0,NULL,'[\"trading\",\"signals\",\"forex\",\"crypto\",\"stocks\"]','Lorem ipsum dolor sit amet consectetur adipisicing elit. Adipisci delectus deleniti temporibus quas veritatis eaque quae iste excepturi natus unde magnam nostrum, officiis tenetur ipsam ratione accusamus nulla esse ab, cumque maxime fugiat modi. Unde dolore nisi nostrum, accusamus eum perferendis distinctio molestiae quam possimus cupiditate, velit ut consequatur eius?',NULL,0.00000000,NULL,NULL,NULL,NULL,0.00000000,NULL,0.00000000,0.00000000,0,0,NULL,NULL,NULL,NULL,NULL,2,NULL,NULL,0,'2025-12-05 08:48:37','2025-12-05 08:48:37'),(9,'AlgoExpertHub','default','default','usd',10,2,'izi','logo.png','favicon.png',1,'{\"heading_font_url\":\"https:\\/\\/fonts.googleapis.com\\/css2?family=Roboto&display=swap\",\"heading_font_family\":\"\'Roboto\',\'sans-serif\'\",\"paragraph_font_url\":\"https:\\/\\/fonts.googleapis.com\\/css2?family=Roboto&display=swap\",\"paragraph_font_family\":\"\'Roboto\',\'sans-serif\'\"}',0,0,NULL,0,0,NULL,1,'Lorem, ipsum.','Lorem ipsum dolor sit, amet consectetur adipisicing elit. Delectus, autem.',0,NULL,NULL,0,NULL,'[\"trading\",\"signals\",\"forex\",\"crypto\",\"stocks\"]','Lorem ipsum dolor sit amet consectetur adipisicing elit. Adipisci delectus deleniti temporibus quas veritatis eaque quae iste excepturi natus unde magnam nostrum, officiis tenetur ipsam ratione accusamus nulla esse ab, cumque maxime fugiat modi. Unde dolore nisi nostrum, accusamus eum perferendis distinctio molestiae quam possimus cupiditate, velit ut consequatur eius?',NULL,0.00000000,NULL,NULL,NULL,NULL,0.00000000,NULL,0.00000000,0.00000000,0,0,NULL,NULL,NULL,NULL,NULL,2,NULL,NULL,0,'2025-12-05 08:55:57','2025-12-05 08:55:57'),(10,'AlgoExpertHub','default','default','usd',10,2,'izi','logo.png','favicon.png',1,'{\"heading_font_url\":\"https:\\/\\/fonts.googleapis.com\\/css2?family=Roboto&display=swap\",\"heading_font_family\":\"\'Roboto\',\'sans-serif\'\",\"paragraph_font_url\":\"https:\\/\\/fonts.googleapis.com\\/css2?family=Roboto&display=swap\",\"paragraph_font_family\":\"\'Roboto\',\'sans-serif\'\"}',0,0,NULL,0,0,NULL,1,'Lorem, ipsum.','Lorem ipsum dolor sit, amet consectetur adipisicing elit. Delectus, autem.',0,NULL,NULL,0,NULL,'[\"trading\",\"signals\",\"forex\",\"crypto\",\"stocks\"]','Lorem ipsum dolor sit amet consectetur adipisicing elit. Adipisci delectus deleniti temporibus quas veritatis eaque quae iste excepturi natus unde magnam nostrum, officiis tenetur ipsam ratione accusamus nulla esse ab, cumque maxime fugiat modi. Unde dolore nisi nostrum, accusamus eum perferendis distinctio molestiae quam possimus cupiditate, velit ut consequatur eius?',NULL,0.00000000,NULL,NULL,NULL,NULL,0.00000000,NULL,0.00000000,0.00000000,0,0,NULL,NULL,NULL,NULL,NULL,2,NULL,NULL,0,'2025-12-05 08:57:57','2025-12-05 08:57:57'),(11,'AlgoExpertHub','default','default','usd',10,2,'izi','logo.png','favicon.png',1,'{\"heading_font_url\":\"https:\\/\\/fonts.googleapis.com\\/css2?family=Roboto&display=swap\",\"heading_font_family\":\"\'Roboto\',\'sans-serif\'\",\"paragraph_font_url\":\"https:\\/\\/fonts.googleapis.com\\/css2?family=Roboto&display=swap\",\"paragraph_font_family\":\"\'Roboto\',\'sans-serif\'\"}',0,0,NULL,0,0,NULL,1,'Lorem, ipsum.','Lorem ipsum dolor sit, amet consectetur adipisicing elit. Delectus, autem.',0,NULL,NULL,0,NULL,'[\"trading\",\"signals\",\"forex\",\"crypto\",\"stocks\"]','Lorem ipsum dolor sit amet consectetur adipisicing elit. Adipisci delectus deleniti temporibus quas veritatis eaque quae iste excepturi natus unde magnam nostrum, officiis tenetur ipsam ratione accusamus nulla esse ab, cumque maxime fugiat modi. Unde dolore nisi nostrum, accusamus eum perferendis distinctio molestiae quam possimus cupiditate, velit ut consequatur eius?',NULL,0.00000000,NULL,NULL,NULL,NULL,0.00000000,NULL,0.00000000,0.00000000,0,0,NULL,NULL,NULL,NULL,NULL,2,NULL,NULL,0,'2025-12-05 08:58:01','2025-12-05 08:58:01');
/*!40000 ALTER TABLE `sp_configurations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_contents`
--

DROP TABLE IF EXISTS `sp_contents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=236 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_contents`
--

LOCK TABLES `sp_contents` WRITE;
/*!40000 ALTER TABLE `sp_contents` DISABLE KEYS */;
INSERT INTO `sp_contents` VALUES (1,'non_iteratable','banner','{\"title\":\"Automate, Copy, or Lead \\u2013 Your Trading\",\"color_text_for_title\":\"Ecosystem Awaits\",\"button_text\":\"Get Started\",\"button_text_link\":\"register\",\"repeater\":[{\"repeater\":\"Let our AI engine forecast and execute across all markets\"},{\"repeater\":\"Make smarter decisions and build a profile others pay to copy\"},{\"repeater\":\"Discover seamless autotrading tailored to your risk, on any market\"}],\"image_two\":\"69326cfda1eb91764912381.png\",\"image_one\":\"69326cdc0239e1764912348.png\",\"image_three\":null}','default',0,'2025-12-04 21:09:39','2025-12-04 22:26:21'),(2,'non_iteratable','about','{\"title\":\"Unlock Your Edge in Algorithmic Trading\",\"color_text_for_title\":\"AlgoExperthub\",\"button_text\":\"Launch Your Edge\",\"button_link\":\"\\/register\",\"repeater\":[{\"repeater\":\"Your Trading Signals Are Already Obsolete. Evolve Your Edge.\"},{\"repeater\":\"From Manual Trading to Automated Strategy Architect.\"},{\"repeater\":\"Trade with Conviction, Backed by AI & Institutional Tools.\"}],\"description\":\"AlgoExperthub is your all-in-one platform to automate, analyze, and amplify your trading. Leverage AI, institutional strategies, and a mastermind community.\",\"image_one\":\"69326dd0aa18a1764912592.png\",\"image_two\":\"69326dd0c39591764912592.png\"}','default',0,'2025-12-04 21:09:39','2025-12-04 22:29:52'),(3,'non_iteratable','benefits','{\"section_header\":\"Summary of Benefits\",\"title\":\"Everything You Need to Fast Track Your Trading\",\"color_text_for_title\":\"Track Your Trading\",\"image_one\":\"693272dde9bf61764913885.png\"}','default',0,'2025-12-04 21:09:39','2025-12-04 22:51:26'),(4,'non_iteratable','how_works','{\"section_header\":\"How it Works\",\"title\":\"Started Trading With Algoexperthub\",\"color_text_for_title\":\"With Algoexperthub\",\"image_one\":\"\"}','default',0,'2025-12-04 21:09:39','2025-12-04 21:15:48'),(5,'non_iteratable','plans','{\"section_header\":\"Packages\",\"title\":\"Our Best Packages\",\"color_text_for_title\":\"Packages\",\"image_one\":\"69326e5ddd38b1764912733.png\"}','default',0,'2025-12-04 21:09:39','2025-12-04 22:32:14'),(6,'non_iteratable','contact','{\"section_header\":\"Contact\",\"title\":\"We\",\"color_text_for_title\":\"Hear From You\",\"email\":\"support@algoexperthub.com\",\"phone\":\"+6282247006969\",\"address\":\"Visit our office HQ, Trading Center, New York\",\"form_header\":\"Love to hear from you, Get in touch\",\"color_text_for_form_header\":\"Get in touch\"}','default',0,'2025-12-04 21:09:39','2025-12-04 22:59:13'),(7,'non_iteratable','footer','{\"footer_short_details\":\"AlgoExpertHub - Advanced trading signals platform powered by AI and institutional-grade strategies.\",\"image_one\":\"69327360696421764914016.png\"}','default',0,'2025-12-04 21:09:39','2025-12-04 22:53:36'),(8,'non_iteratable','trade','{\"section_header\":\"Live Trading\",\"title\":\"Join the Algoexperthub community\",\"color_text_for_title\":\"Algoexperthub community\",\"button_text\":\"Start Trading\",\"button_link\":\"register\",\"image_one\":\"\"}','default',0,'2025-12-04 21:09:39','2025-12-04 21:15:48'),(9,'non_iteratable','why_choose_us','{\"section_header\":\"Choose Us\",\"title\":\"Why Choose AlgoExperthub\",\"color_text_for_title\":\"AlgoExperthub\",\"image_one\":\"\"}','default',0,'2025-12-04 21:09:39','2025-12-04 21:15:48'),(10,'non_iteratable','referral','{\"section_header\":\"Referral\",\"title\":\"Our Forex Trading Referral\",\"color_text_for_title\":\"Trading Referral\",\"image_one\":\"\"}','default',0,'2025-12-04 21:09:39','2025-12-04 21:15:48'),(11,'non_iteratable','team','{\"section_header\":\"Our Team\",\"title\":\"Our Forex Trading Specialist\",\"color_text_for_title\":\"Forex Trading\",\"image_one\":\"\"}','default',0,'2025-12-04 21:09:39','2025-12-04 21:15:48'),(12,'non_iteratable','testimonial','{\"section_header\":\"Testimonials\",\"title\":\"What Our Customer Says\",\"color_text_for_title\":\"Our Customer\",\"image_one\":\"\"}','default',0,'2025-12-04 21:09:39','2025-12-04 21:15:48'),(13,'non_iteratable','blog','{\"section_header\":\"Blog Post\",\"title\":\"Our Latest News\",\"color_text_for_title\":\"News\",\"image_one\":\"\"}','default',0,'2025-12-04 21:09:39','2025-12-04 21:15:48'),(14,'non_iteratable','auth','{\"title\":\"Welcome to AlgoExpertHub\",\"image_one\":\"6932749236e581764914322.jpg\"}','default',0,'2025-12-04 21:09:39','2025-12-04 22:58:42'),(15,'iteratable','benefits','{\"title\":\"20+ Proven Trading Strategies\",\"icon\":\"fab fa-searchengin\",\"description\":\"Access a curated library of institutional-grade strategies ready to deploy or customize.\",\"image_one\":\"69326ea889b5f1764912808.png\"}','default',0,'2025-12-04 21:09:39','2025-12-04 22:33:28'),(16,'iteratable','benefits','{\"title\":\"VIP Insights & Direct Support\",\"icon\":\"far fa-user\",\"description\":\"Get exclusive market analysis from our pros and real-time signals via VIP Telegram groups.\",\"image_one\":\"69326eb7e649f1764912823.png\"}','default',0,'2025-12-04 21:09:39','2025-12-04 22:33:43'),(17,'iteratable','benefits','{\"title\":\"AI-Powered Market Forecasting\",\"icon\":\"far fa-thumbs-up\",\"description\":\"Let our advanced AI analyze sentiment, patterns, and correlations across markets.\",\"image_one\":\"69326ec22023e1764912834.png\"}','default',0,'2025-12-04 21:09:39','2025-12-04 22:33:54'),(18,'iteratable','benefits','{\"title\":\"Seamless Autotrading Execution\",\"icon\":\"far fa-chart-bar\",\"description\":\"Set your rules once. Our system executes trades 24\\/7 with precision speed.\",\"image_one\":\"69326ed233d361764912850.png\"}','default',0,'2025-12-04 21:09:39','2025-12-04 22:34:10'),(19,'iteratable','benefits','{\"title\":\"Multi-Channel Alert System\",\"icon\":\"far fa-envelope\",\"description\":\"Receive critical trade signals via Telegram. Never miss a key market movement.\",\"image_one\":\"69326effddd451764912895.png\"}','default',0,'2025-12-04 21:09:39','2025-12-04 22:34:55'),(20,'iteratable','benefits','{\"title\":\"Join a Growing Community\",\"icon\":\"fas fa-users\",\"description\":\"Connect with traders worldwide and share strategies.\",\"image_one\":\"69326f0be023e1764912907.png\"}','default',0,'2025-12-04 21:09:39','2025-12-04 22:35:07'),(21,'iteratable','how_works','{\"title\":\"Create Account\",\"description\":\"Simple registration process. No credit card required for trial.\",\"image_one\":\"\"}','default',0,'2025-12-04 21:09:39','2025-12-04 21:15:48'),(22,'iteratable','how_works','{\"title\":\"Select Package\",\"description\":\"Choose your subscription plan. Flexible monthly or yearly options.\",\"image_one\":\"\"}','default',0,'2025-12-04 21:09:39','2025-12-04 21:15:48'),(23,'iteratable','how_works','{\"title\":\"Start Trading\",\"description\":\"Activate autotrading and let the algo work for you.\",\"image_one\":\"\"}','default',0,'2025-12-04 21:09:39','2025-12-04 21:15:48'),(26,'iteratable','socials','{\"icon\":\"fab fa-telegram-plane\",\"link\":\"https:\\/\\/t.me\\/algoexperthub\",\"image_one\":\"\"}','default',0,'2025-12-04 21:09:39','2025-12-04 21:15:48'),(27,'iteratable','brand','{\"image_one\":\"6932700a91e771764913162.jpg\"}','default',0,'2025-12-04 22:39:22','2025-12-04 22:39:22'),(28,'non_iteratable','banner','{\"title\":\"Automate, Copy, or Lead \\u2013 Your Trading\",\"color_text_for_title\":\"Ecosystem Awaits\",\"button_text\":\"Get Started\",\"button_text_link\":\"register\",\"image_one\":\"\",\"image_two\":\"\",\"repeater\":[{\"repeater\":\"Let our AI engine forecast and execute across all markets\"},{\"repeater\":\"Make smarter decisions and build a profile others pay to copy\"},{\"repeater\":\"Discover seamless autotrading tailored to your risk, on any market\"}]}','default',0,'2025-12-05 08:41:37','2025-12-05 08:41:37'),(29,'non_iteratable','about','{\"title\":\"Unlock Your Edge in Algorithmic Trading\",\"color_text_for_title\":\"AlgoExperthub\",\"button_text\":\"Launch Your Edge\",\"button_link\":\"\\/register\",\"description\":\"AlgoExperthub is your all-in-one platform to automate, analyze, and amplify your trading. Leverage AI, institutional strategies, and a mastermind community.\",\"image_one\":\"\",\"image_two\":\"\",\"repeater\":[{\"repeater\":\"Your Trading Signals Are Already Obsolete. Evolve Your Edge.\"},{\"repeater\":\"From Manual Trading to Automated Strategy Architect.\"},{\"repeater\":\"Trade with Conviction, Backed by AI & Institutional Tools.\"}]}','default',0,'2025-12-05 08:41:37','2025-12-05 08:41:37'),(30,'non_iteratable','benefits','{\"section_header\":\"Summary of Benefits\",\"title\":\"Everything You Need to Fast Track Your Trading\",\"color_text_for_title\":\"Track Your Trading\",\"image_one\":\"\"}','default',0,'2025-12-05 08:41:37','2025-12-05 08:41:37'),(31,'non_iteratable','how_works','{\"section_header\":\"How it Works\",\"title\":\"Started Trading With Algoexperthub\",\"color_text_for_title\":\"With Algoexperthub\",\"image_one\":\"\"}','default',0,'2025-12-05 08:41:37','2025-12-05 08:41:37'),(32,'non_iteratable','plans','{\"section_header\":\"Packages\",\"title\":\"Our Best Packages\",\"color_text_for_title\":\"Packages\",\"image_one\":\"\"}','default',0,'2025-12-05 08:41:37','2025-12-05 08:41:37'),(33,'non_iteratable','contact','{\"section_header\":\"Contact\",\"title\":\"We\'d Love to Hear From You\",\"color_text_for_title\":\"Hear From You\",\"email\":\"support@algoexperthub.com\",\"phone\":\"+1 (800) 123-4567\",\"address\":\"Visit our office HQ, Trading Center, New York\",\"form_header\":\"Love to hear from you, Get in touch\",\"color_text_for_form_header\":\"Get in touch\",\"image_one\":\"\"}','default',0,'2025-12-05 08:41:37','2025-12-05 08:41:37'),(34,'non_iteratable','footer','{\"footer_short_details\":\"AlgoExpertHub - Advanced trading signals platform powered by AI and institutional-grade strategies.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:41:37','2025-12-05 08:41:37'),(35,'non_iteratable','trade','{\"section_header\":\"Live Trading\",\"title\":\"Join the Algoexperthub community\",\"color_text_for_title\":\"Algoexperthub community\",\"button_text\":\"Start Trading\",\"button_link\":\"register\",\"image_one\":\"\"}','default',0,'2025-12-05 08:41:37','2025-12-05 08:41:37'),(36,'non_iteratable','why_choose_us','{\"section_header\":\"Choose Us\",\"title\":\"Why Choose AlgoExperthub\",\"color_text_for_title\":\"AlgoExperthub\",\"image_one\":\"\"}','default',0,'2025-12-05 08:41:37','2025-12-05 08:41:37'),(37,'non_iteratable','referral','{\"section_header\":\"Referral\",\"title\":\"Our Forex Trading Referral\",\"color_text_for_title\":\"Trading Referral\",\"image_one\":\"\"}','default',0,'2025-12-05 08:41:37','2025-12-05 08:41:37'),(38,'non_iteratable','team','{\"section_header\":\"Our Team\",\"title\":\"Our Forex Trading Specialist\",\"color_text_for_title\":\"Forex Trading\",\"image_one\":\"\"}','default',0,'2025-12-05 08:41:37','2025-12-05 08:41:37'),(39,'non_iteratable','testimonial','{\"section_header\":\"Testimonials\",\"title\":\"What Our Customer Says\",\"color_text_for_title\":\"Our Customer\",\"image_one\":\"\"}','default',0,'2025-12-05 08:41:37','2025-12-05 08:41:37'),(40,'non_iteratable','blog','{\"section_header\":\"Blog Post\",\"title\":\"Our Latest News\",\"color_text_for_title\":\"News\",\"image_one\":\"\"}','default',0,'2025-12-05 08:41:37','2025-12-05 08:41:37'),(41,'non_iteratable','auth','{\"title\":\"Welcome to AlgoExpertHub\",\"image_one\":\"\"}','default',0,'2025-12-05 08:41:37','2025-12-05 08:41:37'),(42,'iteratable','benefits','{\"title\":\"20+ Proven Trading Strategies\",\"icon\":\"fab fa-searchengin\",\"description\":\"Access a curated library of institutional-grade strategies ready to deploy or customize.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:41:38','2025-12-05 08:41:38'),(43,'iteratable','benefits','{\"title\":\"VIP Insights & Direct Support\",\"icon\":\"far fa-user\",\"description\":\"Get exclusive market analysis from our pros and real-time signals via VIP Telegram groups.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:41:38','2025-12-05 08:41:38'),(44,'iteratable','benefits','{\"title\":\"AI-Powered Market Forecasting\",\"icon\":\"far fa-thumbs-up\",\"description\":\"Let our advanced AI analyze sentiment, patterns, and correlations across markets.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:41:38','2025-12-05 08:41:38'),(45,'iteratable','benefits','{\"title\":\"Seamless Autotrading Execution\",\"icon\":\"far fa-chart-bar\",\"description\":\"Set your rules once. Our system executes trades 24\\/7 with precision speed.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:41:38','2025-12-05 08:41:38'),(46,'iteratable','benefits','{\"title\":\"Multi-Channel Alert System\",\"icon\":\"far fa-envelope\",\"description\":\"Receive critical trade signals via Telegram. Never miss a key market movement.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:41:38','2025-12-05 08:41:38'),(47,'iteratable','benefits','{\"title\":\"Join a Growing Community\",\"icon\":\"fas fa-users\",\"description\":\"Connect with traders worldwide and share strategies.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:41:38','2025-12-05 08:41:38'),(48,'iteratable','how_works','{\"title\":\"Create Account\",\"description\":\"Simple registration process. No credit card required for trial.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:41:38','2025-12-05 08:41:38'),(49,'iteratable','how_works','{\"title\":\"Select Package\",\"description\":\"Choose your subscription plan. Flexible monthly or yearly options.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:41:38','2025-12-05 08:41:38'),(50,'iteratable','how_works','{\"title\":\"Start Trading\",\"description\":\"Activate autotrading and let the algo work for you.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:41:38','2025-12-05 08:41:38'),(51,'iteratable','socials','{\"icon\":\"fab fa-facebook-f\",\"link\":\"https:\\/\\/facebook.com\",\"image_one\":\"\"}','default',0,'2025-12-05 08:41:38','2025-12-05 08:41:38'),(52,'iteratable','socials','{\"icon\":\"fab fa-twitter\",\"link\":\"https:\\/\\/twitter.com\",\"image_one\":\"\"}','default',0,'2025-12-05 08:41:39','2025-12-05 08:41:39'),(53,'iteratable','socials','{\"icon\":\"fab fa-telegram-plane\",\"link\":\"https:\\/\\/t.me\\/algoexperthub\",\"image_one\":\"\"}','default',0,'2025-12-05 08:41:39','2025-12-05 08:41:39'),(54,'non_iteratable','banner','{\"title\":\"Automate, Copy, or Lead \\u2013 Your Trading\",\"color_text_for_title\":\"Ecosystem Awaits\",\"button_text\":\"Get Started\",\"button_text_link\":\"register\",\"image_one\":\"\",\"image_two\":\"\",\"repeater\":[{\"repeater\":\"Let our AI engine forecast and execute across all markets\"},{\"repeater\":\"Make smarter decisions and build a profile others pay to copy\"},{\"repeater\":\"Discover seamless autotrading tailored to your risk, on any market\"}]}','default',0,'2025-12-05 08:41:42','2025-12-05 08:41:42'),(55,'non_iteratable','about','{\"title\":\"Unlock Your Edge in Algorithmic Trading\",\"color_text_for_title\":\"AlgoExperthub\",\"button_text\":\"Launch Your Edge\",\"button_link\":\"\\/register\",\"description\":\"AlgoExperthub is your all-in-one platform to automate, analyze, and amplify your trading. Leverage AI, institutional strategies, and a mastermind community.\",\"image_one\":\"\",\"image_two\":\"\",\"repeater\":[{\"repeater\":\"Your Trading Signals Are Already Obsolete. Evolve Your Edge.\"},{\"repeater\":\"From Manual Trading to Automated Strategy Architect.\"},{\"repeater\":\"Trade with Conviction, Backed by AI & Institutional Tools.\"}]}','default',0,'2025-12-05 08:41:43','2025-12-05 08:41:43'),(56,'non_iteratable','benefits','{\"section_header\":\"Summary of Benefits\",\"title\":\"Everything You Need to Fast Track Your Trading\",\"color_text_for_title\":\"Track Your Trading\",\"image_one\":\"\"}','default',0,'2025-12-05 08:41:43','2025-12-05 08:41:43'),(57,'non_iteratable','how_works','{\"section_header\":\"How it Works\",\"title\":\"Started Trading With Algoexperthub\",\"color_text_for_title\":\"With Algoexperthub\",\"image_one\":\"\"}','default',0,'2025-12-05 08:41:43','2025-12-05 08:41:43'),(58,'non_iteratable','plans','{\"section_header\":\"Packages\",\"title\":\"Our Best Packages\",\"color_text_for_title\":\"Packages\",\"image_one\":\"\"}','default',0,'2025-12-05 08:41:43','2025-12-05 08:41:43'),(59,'non_iteratable','contact','{\"section_header\":\"Contact\",\"title\":\"We\'d Love to Hear From You\",\"color_text_for_title\":\"Hear From You\",\"email\":\"support@algoexperthub.com\",\"phone\":\"+1 (800) 123-4567\",\"address\":\"Visit our office HQ, Trading Center, New York\",\"form_header\":\"Love to hear from you, Get in touch\",\"color_text_for_form_header\":\"Get in touch\",\"image_one\":\"\"}','default',0,'2025-12-05 08:41:43','2025-12-05 08:41:43'),(60,'non_iteratable','footer','{\"footer_short_details\":\"AlgoExpertHub - Advanced trading signals platform powered by AI and institutional-grade strategies.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:41:43','2025-12-05 08:41:43'),(61,'non_iteratable','trade','{\"section_header\":\"Live Trading\",\"title\":\"Join the Algoexperthub community\",\"color_text_for_title\":\"Algoexperthub community\",\"button_text\":\"Start Trading\",\"button_link\":\"register\",\"image_one\":\"\"}','default',0,'2025-12-05 08:41:43','2025-12-05 08:41:43'),(62,'non_iteratable','why_choose_us','{\"section_header\":\"Choose Us\",\"title\":\"Why Choose AlgoExperthub\",\"color_text_for_title\":\"AlgoExperthub\",\"image_one\":\"\"}','default',0,'2025-12-05 08:41:44','2025-12-05 08:41:44'),(63,'non_iteratable','referral','{\"section_header\":\"Referral\",\"title\":\"Our Forex Trading Referral\",\"color_text_for_title\":\"Trading Referral\",\"image_one\":\"\"}','default',0,'2025-12-05 08:41:44','2025-12-05 08:41:44'),(64,'non_iteratable','team','{\"section_header\":\"Our Team\",\"title\":\"Our Forex Trading Specialist\",\"color_text_for_title\":\"Forex Trading\",\"image_one\":\"\"}','default',0,'2025-12-05 08:41:44','2025-12-05 08:41:44'),(65,'non_iteratable','testimonial','{\"section_header\":\"Testimonials\",\"title\":\"What Our Customer Says\",\"color_text_for_title\":\"Our Customer\",\"image_one\":\"\"}','default',0,'2025-12-05 08:41:44','2025-12-05 08:41:44'),(66,'non_iteratable','blog','{\"section_header\":\"Blog Post\",\"title\":\"Our Latest News\",\"color_text_for_title\":\"News\",\"image_one\":\"\"}','default',0,'2025-12-05 08:41:44','2025-12-05 08:41:44'),(67,'non_iteratable','auth','{\"title\":\"Welcome to AlgoExpertHub\",\"image_one\":\"\"}','default',0,'2025-12-05 08:41:44','2025-12-05 08:41:44'),(68,'iteratable','benefits','{\"title\":\"20+ Proven Trading Strategies\",\"icon\":\"fab fa-searchengin\",\"description\":\"Access a curated library of institutional-grade strategies ready to deploy or customize.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:41:44','2025-12-05 08:41:44'),(69,'iteratable','benefits','{\"title\":\"VIP Insights & Direct Support\",\"icon\":\"far fa-user\",\"description\":\"Get exclusive market analysis from our pros and real-time signals via VIP Telegram groups.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:41:44','2025-12-05 08:41:44'),(70,'iteratable','benefits','{\"title\":\"AI-Powered Market Forecasting\",\"icon\":\"far fa-thumbs-up\",\"description\":\"Let our advanced AI analyze sentiment, patterns, and correlations across markets.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:41:45','2025-12-05 08:41:45'),(71,'iteratable','benefits','{\"title\":\"Seamless Autotrading Execution\",\"icon\":\"far fa-chart-bar\",\"description\":\"Set your rules once. Our system executes trades 24\\/7 with precision speed.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:41:45','2025-12-05 08:41:45'),(72,'iteratable','benefits','{\"title\":\"Multi-Channel Alert System\",\"icon\":\"far fa-envelope\",\"description\":\"Receive critical trade signals via Telegram. Never miss a key market movement.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:41:45','2025-12-05 08:41:45'),(73,'iteratable','benefits','{\"title\":\"Join a Growing Community\",\"icon\":\"fas fa-users\",\"description\":\"Connect with traders worldwide and share strategies.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:41:45','2025-12-05 08:41:45'),(74,'iteratable','how_works','{\"title\":\"Create Account\",\"description\":\"Simple registration process. No credit card required for trial.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:41:45','2025-12-05 08:41:45'),(75,'iteratable','how_works','{\"title\":\"Select Package\",\"description\":\"Choose your subscription plan. Flexible monthly or yearly options.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:41:45','2025-12-05 08:41:45'),(76,'iteratable','how_works','{\"title\":\"Start Trading\",\"description\":\"Activate autotrading and let the algo work for you.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:41:45','2025-12-05 08:41:45'),(77,'iteratable','socials','{\"icon\":\"fab fa-facebook-f\",\"link\":\"https:\\/\\/facebook.com\",\"image_one\":\"\"}','default',0,'2025-12-05 08:41:45','2025-12-05 08:41:45'),(78,'iteratable','socials','{\"icon\":\"fab fa-twitter\",\"link\":\"https:\\/\\/twitter.com\",\"image_one\":\"\"}','default',0,'2025-12-05 08:41:45','2025-12-05 08:41:45'),(79,'iteratable','socials','{\"icon\":\"fab fa-telegram-plane\",\"link\":\"https:\\/\\/t.me\\/algoexperthub\",\"image_one\":\"\"}','default',0,'2025-12-05 08:41:46','2025-12-05 08:41:46'),(80,'non_iteratable','banner','{\"title\":\"Automate, Copy, or Lead \\u2013 Your Trading\",\"color_text_for_title\":\"Ecosystem Awaits\",\"button_text\":\"Get Started\",\"button_text_link\":\"register\",\"image_one\":\"\",\"image_two\":\"\",\"repeater\":[{\"repeater\":\"Let our AI engine forecast and execute across all markets\"},{\"repeater\":\"Make smarter decisions and build a profile others pay to copy\"},{\"repeater\":\"Discover seamless autotrading tailored to your risk, on any market\"}]}','default',0,'2025-12-05 08:45:30','2025-12-05 08:45:30'),(81,'non_iteratable','about','{\"title\":\"Unlock Your Edge in Algorithmic Trading\",\"color_text_for_title\":\"AlgoExperthub\",\"button_text\":\"Launch Your Edge\",\"button_link\":\"\\/register\",\"description\":\"AlgoExperthub is your all-in-one platform to automate, analyze, and amplify your trading. Leverage AI, institutional strategies, and a mastermind community.\",\"image_one\":\"\",\"image_two\":\"\",\"repeater\":[{\"repeater\":\"Your Trading Signals Are Already Obsolete. Evolve Your Edge.\"},{\"repeater\":\"From Manual Trading to Automated Strategy Architect.\"},{\"repeater\":\"Trade with Conviction, Backed by AI & Institutional Tools.\"}]}','default',0,'2025-12-05 08:45:30','2025-12-05 08:45:30'),(82,'non_iteratable','benefits','{\"section_header\":\"Summary of Benefits\",\"title\":\"Everything You Need to Fast Track Your Trading\",\"color_text_for_title\":\"Track Your Trading\",\"image_one\":\"\"}','default',0,'2025-12-05 08:45:30','2025-12-05 08:45:30'),(83,'non_iteratable','how_works','{\"section_header\":\"How it Works\",\"title\":\"Started Trading With Algoexperthub\",\"color_text_for_title\":\"With Algoexperthub\",\"image_one\":\"\"}','default',0,'2025-12-05 08:45:30','2025-12-05 08:45:30'),(84,'non_iteratable','plans','{\"section_header\":\"Packages\",\"title\":\"Our Best Packages\",\"color_text_for_title\":\"Packages\",\"image_one\":\"\"}','default',0,'2025-12-05 08:45:30','2025-12-05 08:45:30'),(85,'non_iteratable','contact','{\"section_header\":\"Contact\",\"title\":\"We\'d Love to Hear From You\",\"color_text_for_title\":\"Hear From You\",\"email\":\"support@algoexperthub.com\",\"phone\":\"+1 (800) 123-4567\",\"address\":\"Visit our office HQ, Trading Center, New York\",\"form_header\":\"Love to hear from you, Get in touch\",\"color_text_for_form_header\":\"Get in touch\",\"image_one\":\"\"}','default',0,'2025-12-05 08:45:30','2025-12-05 08:45:30'),(86,'non_iteratable','footer','{\"footer_short_details\":\"AlgoExpertHub - Advanced trading signals platform powered by AI and institutional-grade strategies.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:45:30','2025-12-05 08:45:30'),(87,'non_iteratable','trade','{\"section_header\":\"Live Trading\",\"title\":\"Join the Algoexperthub community\",\"color_text_for_title\":\"Algoexperthub community\",\"button_text\":\"Start Trading\",\"button_link\":\"register\",\"image_one\":\"\"}','default',0,'2025-12-05 08:45:31','2025-12-05 08:45:31'),(88,'non_iteratable','why_choose_us','{\"section_header\":\"Choose Us\",\"title\":\"Why Choose AlgoExperthub\",\"color_text_for_title\":\"AlgoExperthub\",\"image_one\":\"\"}','default',0,'2025-12-05 08:45:31','2025-12-05 08:45:31'),(89,'non_iteratable','referral','{\"section_header\":\"Referral\",\"title\":\"Our Forex Trading Referral\",\"color_text_for_title\":\"Trading Referral\",\"image_one\":\"\"}','default',0,'2025-12-05 08:45:31','2025-12-05 08:45:31'),(90,'non_iteratable','team','{\"section_header\":\"Our Team\",\"title\":\"Our Forex Trading Specialist\",\"color_text_for_title\":\"Forex Trading\",\"image_one\":\"\"}','default',0,'2025-12-05 08:45:31','2025-12-05 08:45:31'),(91,'non_iteratable','testimonial','{\"section_header\":\"Testimonials\",\"title\":\"What Our Customer Says\",\"color_text_for_title\":\"Our Customer\",\"image_one\":\"\"}','default',0,'2025-12-05 08:45:31','2025-12-05 08:45:31'),(92,'non_iteratable','blog','{\"section_header\":\"Blog Post\",\"title\":\"Our Latest News\",\"color_text_for_title\":\"News\",\"image_one\":\"\"}','default',0,'2025-12-05 08:45:32','2025-12-05 08:45:32'),(93,'non_iteratable','auth','{\"title\":\"Welcome to AlgoExpertHub\",\"image_one\":\"\"}','default',0,'2025-12-05 08:45:32','2025-12-05 08:45:32'),(94,'iteratable','benefits','{\"title\":\"20+ Proven Trading Strategies\",\"icon\":\"fab fa-searchengin\",\"description\":\"Access a curated library of institutional-grade strategies ready to deploy or customize.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:45:32','2025-12-05 08:45:32'),(95,'iteratable','benefits','{\"title\":\"VIP Insights & Direct Support\",\"icon\":\"far fa-user\",\"description\":\"Get exclusive market analysis from our pros and real-time signals via VIP Telegram groups.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:45:32','2025-12-05 08:45:32'),(96,'iteratable','benefits','{\"title\":\"AI-Powered Market Forecasting\",\"icon\":\"far fa-thumbs-up\",\"description\":\"Let our advanced AI analyze sentiment, patterns, and correlations across markets.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:45:32','2025-12-05 08:45:32'),(97,'iteratable','benefits','{\"title\":\"Seamless Autotrading Execution\",\"icon\":\"far fa-chart-bar\",\"description\":\"Set your rules once. Our system executes trades 24\\/7 with precision speed.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:45:33','2025-12-05 08:45:33'),(98,'iteratable','benefits','{\"title\":\"Multi-Channel Alert System\",\"icon\":\"far fa-envelope\",\"description\":\"Receive critical trade signals via Telegram. Never miss a key market movement.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:45:33','2025-12-05 08:45:33'),(99,'iteratable','benefits','{\"title\":\"Join a Growing Community\",\"icon\":\"fas fa-users\",\"description\":\"Connect with traders worldwide and share strategies.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:45:33','2025-12-05 08:45:33'),(100,'iteratable','how_works','{\"title\":\"Create Account\",\"description\":\"Simple registration process. No credit card required for trial.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:45:33','2025-12-05 08:45:33'),(101,'iteratable','how_works','{\"title\":\"Select Package\",\"description\":\"Choose your subscription plan. Flexible monthly or yearly options.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:45:33','2025-12-05 08:45:33'),(102,'iteratable','how_works','{\"title\":\"Start Trading\",\"description\":\"Activate autotrading and let the algo work for you.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:45:34','2025-12-05 08:45:34'),(103,'iteratable','socials','{\"icon\":\"fab fa-facebook-f\",\"link\":\"https:\\/\\/facebook.com\",\"image_one\":\"\"}','default',0,'2025-12-05 08:45:34','2025-12-05 08:45:34'),(104,'iteratable','socials','{\"icon\":\"fab fa-twitter\",\"link\":\"https:\\/\\/twitter.com\",\"image_one\":\"\"}','default',0,'2025-12-05 08:45:34','2025-12-05 08:45:34'),(105,'iteratable','socials','{\"icon\":\"fab fa-telegram-plane\",\"link\":\"https:\\/\\/t.me\\/algoexperthub\",\"image_one\":\"\"}','default',0,'2025-12-05 08:45:34','2025-12-05 08:45:34'),(106,'non_iteratable','banner','{\"title\":\"Automate, Copy, or Lead \\u2013 Your Trading\",\"color_text_for_title\":\"Ecosystem Awaits\",\"button_text\":\"Get Started\",\"button_text_link\":\"register\",\"image_one\":\"\",\"image_two\":\"\",\"repeater\":[{\"repeater\":\"Let our AI engine forecast and execute across all markets\"},{\"repeater\":\"Make smarter decisions and build a profile others pay to copy\"},{\"repeater\":\"Discover seamless autotrading tailored to your risk, on any market\"}]}','default',0,'2025-12-05 08:47:13','2025-12-05 08:47:13'),(107,'non_iteratable','about','{\"title\":\"Unlock Your Edge in Algorithmic Trading\",\"color_text_for_title\":\"AlgoExperthub\",\"button_text\":\"Launch Your Edge\",\"button_link\":\"\\/register\",\"description\":\"AlgoExperthub is your all-in-one platform to automate, analyze, and amplify your trading. Leverage AI, institutional strategies, and a mastermind community.\",\"image_one\":\"\",\"image_two\":\"\",\"repeater\":[{\"repeater\":\"Your Trading Signals Are Already Obsolete. Evolve Your Edge.\"},{\"repeater\":\"From Manual Trading to Automated Strategy Architect.\"},{\"repeater\":\"Trade with Conviction, Backed by AI & Institutional Tools.\"}]}','default',0,'2025-12-05 08:47:13','2025-12-05 08:47:13'),(108,'non_iteratable','benefits','{\"section_header\":\"Summary of Benefits\",\"title\":\"Everything You Need to Fast Track Your Trading\",\"color_text_for_title\":\"Track Your Trading\",\"image_one\":\"\"}','default',0,'2025-12-05 08:47:13','2025-12-05 08:47:13'),(109,'non_iteratable','how_works','{\"section_header\":\"How it Works\",\"title\":\"Started Trading With Algoexperthub\",\"color_text_for_title\":\"With Algoexperthub\",\"image_one\":\"\"}','default',0,'2025-12-05 08:47:13','2025-12-05 08:47:13'),(110,'non_iteratable','plans','{\"section_header\":\"Packages\",\"title\":\"Our Best Packages\",\"color_text_for_title\":\"Packages\",\"image_one\":\"\"}','default',0,'2025-12-05 08:47:13','2025-12-05 08:47:13'),(111,'non_iteratable','contact','{\"section_header\":\"Contact\",\"title\":\"We\'d Love to Hear From You\",\"color_text_for_title\":\"Hear From You\",\"email\":\"support@algoexperthub.com\",\"phone\":\"+1 (800) 123-4567\",\"address\":\"Visit our office HQ, Trading Center, New York\",\"form_header\":\"Love to hear from you, Get in touch\",\"color_text_for_form_header\":\"Get in touch\",\"image_one\":\"\"}','default',0,'2025-12-05 08:47:13','2025-12-05 08:47:13'),(112,'non_iteratable','footer','{\"footer_short_details\":\"AlgoExpertHub - Advanced trading signals platform powered by AI and institutional-grade strategies.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:47:13','2025-12-05 08:47:13'),(113,'non_iteratable','trade','{\"section_header\":\"Live Trading\",\"title\":\"Join the Algoexperthub community\",\"color_text_for_title\":\"Algoexperthub community\",\"button_text\":\"Start Trading\",\"button_link\":\"register\",\"image_one\":\"\"}','default',0,'2025-12-05 08:47:13','2025-12-05 08:47:13'),(114,'non_iteratable','why_choose_us','{\"section_header\":\"Choose Us\",\"title\":\"Why Choose AlgoExperthub\",\"color_text_for_title\":\"AlgoExperthub\",\"image_one\":\"\"}','default',0,'2025-12-05 08:47:13','2025-12-05 08:47:13'),(115,'non_iteratable','referral','{\"section_header\":\"Referral\",\"title\":\"Our Forex Trading Referral\",\"color_text_for_title\":\"Trading Referral\",\"image_one\":\"\"}','default',0,'2025-12-05 08:47:13','2025-12-05 08:47:13'),(116,'non_iteratable','team','{\"section_header\":\"Our Team\",\"title\":\"Our Forex Trading Specialist\",\"color_text_for_title\":\"Forex Trading\",\"image_one\":\"\"}','default',0,'2025-12-05 08:47:13','2025-12-05 08:47:13'),(117,'non_iteratable','testimonial','{\"section_header\":\"Testimonials\",\"title\":\"What Our Customer Says\",\"color_text_for_title\":\"Our Customer\",\"image_one\":\"\"}','default',0,'2025-12-05 08:47:13','2025-12-05 08:47:13'),(118,'non_iteratable','blog','{\"section_header\":\"Blog Post\",\"title\":\"Our Latest News\",\"color_text_for_title\":\"News\",\"image_one\":\"\"}','default',0,'2025-12-05 08:47:13','2025-12-05 08:47:13'),(119,'non_iteratable','auth','{\"title\":\"Welcome to AlgoExpertHub\",\"image_one\":\"\"}','default',0,'2025-12-05 08:47:13','2025-12-05 08:47:13'),(120,'iteratable','benefits','{\"title\":\"20+ Proven Trading Strategies\",\"icon\":\"fab fa-searchengin\",\"description\":\"Access a curated library of institutional-grade strategies ready to deploy or customize.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:47:13','2025-12-05 08:47:13'),(121,'iteratable','benefits','{\"title\":\"VIP Insights & Direct Support\",\"icon\":\"far fa-user\",\"description\":\"Get exclusive market analysis from our pros and real-time signals via VIP Telegram groups.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:47:13','2025-12-05 08:47:13'),(122,'iteratable','benefits','{\"title\":\"AI-Powered Market Forecasting\",\"icon\":\"far fa-thumbs-up\",\"description\":\"Let our advanced AI analyze sentiment, patterns, and correlations across markets.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:47:13','2025-12-05 08:47:13'),(123,'iteratable','benefits','{\"title\":\"Seamless Autotrading Execution\",\"icon\":\"far fa-chart-bar\",\"description\":\"Set your rules once. Our system executes trades 24\\/7 with precision speed.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:47:13','2025-12-05 08:47:13'),(124,'iteratable','benefits','{\"title\":\"Multi-Channel Alert System\",\"icon\":\"far fa-envelope\",\"description\":\"Receive critical trade signals via Telegram. Never miss a key market movement.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:47:13','2025-12-05 08:47:13'),(125,'iteratable','benefits','{\"title\":\"Join a Growing Community\",\"icon\":\"fas fa-users\",\"description\":\"Connect with traders worldwide and share strategies.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:47:13','2025-12-05 08:47:13'),(126,'iteratable','how_works','{\"title\":\"Create Account\",\"description\":\"Simple registration process. No credit card required for trial.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:47:13','2025-12-05 08:47:13'),(127,'iteratable','how_works','{\"title\":\"Select Package\",\"description\":\"Choose your subscription plan. Flexible monthly or yearly options.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:47:13','2025-12-05 08:47:13'),(128,'iteratable','how_works','{\"title\":\"Start Trading\",\"description\":\"Activate autotrading and let the algo work for you.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:47:13','2025-12-05 08:47:13'),(129,'iteratable','socials','{\"icon\":\"fab fa-facebook-f\",\"link\":\"https:\\/\\/facebook.com\",\"image_one\":\"\"}','default',0,'2025-12-05 08:47:13','2025-12-05 08:47:13'),(130,'iteratable','socials','{\"icon\":\"fab fa-twitter\",\"link\":\"https:\\/\\/twitter.com\",\"image_one\":\"\"}','default',0,'2025-12-05 08:47:13','2025-12-05 08:47:13'),(131,'iteratable','socials','{\"icon\":\"fab fa-telegram-plane\",\"link\":\"https:\\/\\/t.me\\/algoexperthub\",\"image_one\":\"\"}','default',0,'2025-12-05 08:47:13','2025-12-05 08:47:13'),(132,'non_iteratable','banner','{\"title\":\"Automate, Copy, or Lead \\u2013 Your Trading\",\"color_text_for_title\":\"Ecosystem Awaits\",\"button_text\":\"Get Started\",\"button_text_link\":\"register\",\"image_one\":\"\",\"image_two\":\"\",\"repeater\":[{\"repeater\":\"Let our AI engine forecast and execute across all markets\"},{\"repeater\":\"Make smarter decisions and build a profile others pay to copy\"},{\"repeater\":\"Discover seamless autotrading tailored to your risk, on any market\"}]}','default',0,'2025-12-05 08:48:38','2025-12-05 08:48:38'),(133,'non_iteratable','about','{\"title\":\"Unlock Your Edge in Algorithmic Trading\",\"color_text_for_title\":\"AlgoExperthub\",\"button_text\":\"Launch Your Edge\",\"button_link\":\"\\/register\",\"description\":\"AlgoExperthub is your all-in-one platform to automate, analyze, and amplify your trading. Leverage AI, institutional strategies, and a mastermind community.\",\"image_one\":\"\",\"image_two\":\"\",\"repeater\":[{\"repeater\":\"Your Trading Signals Are Already Obsolete. Evolve Your Edge.\"},{\"repeater\":\"From Manual Trading to Automated Strategy Architect.\"},{\"repeater\":\"Trade with Conviction, Backed by AI & Institutional Tools.\"}]}','default',0,'2025-12-05 08:48:38','2025-12-05 08:48:38'),(134,'non_iteratable','benefits','{\"section_header\":\"Summary of Benefits\",\"title\":\"Everything You Need to Fast Track Your Trading\",\"color_text_for_title\":\"Track Your Trading\",\"image_one\":\"\"}','default',0,'2025-12-05 08:48:38','2025-12-05 08:48:38'),(135,'non_iteratable','how_works','{\"section_header\":\"How it Works\",\"title\":\"Started Trading With Algoexperthub\",\"color_text_for_title\":\"With Algoexperthub\",\"image_one\":\"\"}','default',0,'2025-12-05 08:48:38','2025-12-05 08:48:38'),(136,'non_iteratable','plans','{\"section_header\":\"Packages\",\"title\":\"Our Best Packages\",\"color_text_for_title\":\"Packages\",\"image_one\":\"\"}','default',0,'2025-12-05 08:48:38','2025-12-05 08:48:38'),(137,'non_iteratable','contact','{\"section_header\":\"Contact\",\"title\":\"We\'d Love to Hear From You\",\"color_text_for_title\":\"Hear From You\",\"email\":\"support@algoexperthub.com\",\"phone\":\"+1 (800) 123-4567\",\"address\":\"Visit our office HQ, Trading Center, New York\",\"form_header\":\"Love to hear from you, Get in touch\",\"color_text_for_form_header\":\"Get in touch\",\"image_one\":\"\"}','default',0,'2025-12-05 08:48:38','2025-12-05 08:48:38'),(138,'non_iteratable','footer','{\"footer_short_details\":\"AlgoExpertHub - Advanced trading signals platform powered by AI and institutional-grade strategies.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:48:38','2025-12-05 08:48:38'),(139,'non_iteratable','trade','{\"section_header\":\"Live Trading\",\"title\":\"Join the Algoexperthub community\",\"color_text_for_title\":\"Algoexperthub community\",\"button_text\":\"Start Trading\",\"button_link\":\"register\",\"image_one\":\"\"}','default',0,'2025-12-05 08:48:38','2025-12-05 08:48:38'),(140,'non_iteratable','why_choose_us','{\"section_header\":\"Choose Us\",\"title\":\"Why Choose AlgoExperthub\",\"color_text_for_title\":\"AlgoExperthub\",\"image_one\":\"\"}','default',0,'2025-12-05 08:48:38','2025-12-05 08:48:38'),(141,'non_iteratable','referral','{\"section_header\":\"Referral\",\"title\":\"Our Forex Trading Referral\",\"color_text_for_title\":\"Trading Referral\",\"image_one\":\"\"}','default',0,'2025-12-05 08:48:38','2025-12-05 08:48:38'),(142,'non_iteratable','team','{\"section_header\":\"Our Team\",\"title\":\"Our Forex Trading Specialist\",\"color_text_for_title\":\"Forex Trading\",\"image_one\":\"\"}','default',0,'2025-12-05 08:48:38','2025-12-05 08:48:38'),(143,'non_iteratable','testimonial','{\"section_header\":\"Testimonials\",\"title\":\"What Our Customer Says\",\"color_text_for_title\":\"Our Customer\",\"image_one\":\"\"}','default',0,'2025-12-05 08:48:38','2025-12-05 08:48:38'),(144,'non_iteratable','blog','{\"section_header\":\"Blog Post\",\"title\":\"Our Latest News\",\"color_text_for_title\":\"News\",\"image_one\":\"\"}','default',0,'2025-12-05 08:48:38','2025-12-05 08:48:38'),(145,'non_iteratable','auth','{\"title\":\"Welcome to AlgoExpertHub\",\"image_one\":\"\"}','default',0,'2025-12-05 08:48:38','2025-12-05 08:48:38'),(146,'iteratable','benefits','{\"title\":\"20+ Proven Trading Strategies\",\"icon\":\"fab fa-searchengin\",\"description\":\"Access a curated library of institutional-grade strategies ready to deploy or customize.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:48:38','2025-12-05 08:48:38'),(147,'iteratable','benefits','{\"title\":\"VIP Insights & Direct Support\",\"icon\":\"far fa-user\",\"description\":\"Get exclusive market analysis from our pros and real-time signals via VIP Telegram groups.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:48:38','2025-12-05 08:48:38'),(148,'iteratable','benefits','{\"title\":\"AI-Powered Market Forecasting\",\"icon\":\"far fa-thumbs-up\",\"description\":\"Let our advanced AI analyze sentiment, patterns, and correlations across markets.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:48:38','2025-12-05 08:48:38'),(149,'iteratable','benefits','{\"title\":\"Seamless Autotrading Execution\",\"icon\":\"far fa-chart-bar\",\"description\":\"Set your rules once. Our system executes trades 24\\/7 with precision speed.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:48:38','2025-12-05 08:48:38'),(150,'iteratable','benefits','{\"title\":\"Multi-Channel Alert System\",\"icon\":\"far fa-envelope\",\"description\":\"Receive critical trade signals via Telegram. Never miss a key market movement.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:48:38','2025-12-05 08:48:38'),(151,'iteratable','benefits','{\"title\":\"Join a Growing Community\",\"icon\":\"fas fa-users\",\"description\":\"Connect with traders worldwide and share strategies.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:48:38','2025-12-05 08:48:38'),(152,'iteratable','how_works','{\"title\":\"Create Account\",\"description\":\"Simple registration process. No credit card required for trial.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:48:38','2025-12-05 08:48:38'),(153,'iteratable','how_works','{\"title\":\"Select Package\",\"description\":\"Choose your subscription plan. Flexible monthly or yearly options.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:48:38','2025-12-05 08:48:38'),(154,'iteratable','how_works','{\"title\":\"Start Trading\",\"description\":\"Activate autotrading and let the algo work for you.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:48:38','2025-12-05 08:48:38'),(155,'iteratable','socials','{\"icon\":\"fab fa-facebook-f\",\"link\":\"https:\\/\\/facebook.com\",\"image_one\":\"\"}','default',0,'2025-12-05 08:48:38','2025-12-05 08:48:38'),(156,'iteratable','socials','{\"icon\":\"fab fa-twitter\",\"link\":\"https:\\/\\/twitter.com\",\"image_one\":\"\"}','default',0,'2025-12-05 08:48:38','2025-12-05 08:48:38'),(157,'iteratable','socials','{\"icon\":\"fab fa-telegram-plane\",\"link\":\"https:\\/\\/t.me\\/algoexperthub\",\"image_one\":\"\"}','default',0,'2025-12-05 08:48:38','2025-12-05 08:48:38'),(158,'non_iteratable','banner','{\"title\":\"Automate, Copy, or Lead \\u2013 Your Trading\",\"color_text_for_title\":\"Ecosystem Awaits\",\"button_text\":\"Get Started\",\"button_text_link\":\"register\",\"image_one\":\"\",\"image_two\":\"\",\"repeater\":[{\"repeater\":\"Let our AI engine forecast and execute across all markets\"},{\"repeater\":\"Make smarter decisions and build a profile others pay to copy\"},{\"repeater\":\"Discover seamless autotrading tailored to your risk, on any market\"}]}','default',0,'2025-12-05 08:55:58','2025-12-05 08:55:58'),(159,'non_iteratable','about','{\"title\":\"Unlock Your Edge in Algorithmic Trading\",\"color_text_for_title\":\"AlgoExperthub\",\"button_text\":\"Launch Your Edge\",\"button_link\":\"\\/register\",\"description\":\"AlgoExperthub is your all-in-one platform to automate, analyze, and amplify your trading. Leverage AI, institutional strategies, and a mastermind community.\",\"image_one\":\"\",\"image_two\":\"\",\"repeater\":[{\"repeater\":\"Your Trading Signals Are Already Obsolete. Evolve Your Edge.\"},{\"repeater\":\"From Manual Trading to Automated Strategy Architect.\"},{\"repeater\":\"Trade with Conviction, Backed by AI & Institutional Tools.\"}]}','default',0,'2025-12-05 08:55:58','2025-12-05 08:55:58'),(160,'non_iteratable','benefits','{\"section_header\":\"Summary of Benefits\",\"title\":\"Everything You Need to Fast Track Your Trading\",\"color_text_for_title\":\"Track Your Trading\",\"image_one\":\"\"}','default',0,'2025-12-05 08:55:58','2025-12-05 08:55:58'),(161,'non_iteratable','how_works','{\"section_header\":\"How it Works\",\"title\":\"Started Trading With Algoexperthub\",\"color_text_for_title\":\"With Algoexperthub\",\"image_one\":\"\"}','default',0,'2025-12-05 08:55:58','2025-12-05 08:55:58'),(162,'non_iteratable','plans','{\"section_header\":\"Packages\",\"title\":\"Our Best Packages\",\"color_text_for_title\":\"Packages\",\"image_one\":\"\"}','default',0,'2025-12-05 08:55:58','2025-12-05 08:55:58'),(163,'non_iteratable','contact','{\"section_header\":\"Contact\",\"title\":\"We\'d Love to Hear From You\",\"color_text_for_title\":\"Hear From You\",\"email\":\"support@algoexperthub.com\",\"phone\":\"+1 (800) 123-4567\",\"address\":\"Visit our office HQ, Trading Center, New York\",\"form_header\":\"Love to hear from you, Get in touch\",\"color_text_for_form_header\":\"Get in touch\",\"image_one\":\"\"}','default',0,'2025-12-05 08:55:58','2025-12-05 08:55:58'),(164,'non_iteratable','footer','{\"footer_short_details\":\"AlgoExpertHub - Advanced trading signals platform powered by AI and institutional-grade strategies.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:55:58','2025-12-05 08:55:58'),(165,'non_iteratable','trade','{\"section_header\":\"Live Trading\",\"title\":\"Join the Algoexperthub community\",\"color_text_for_title\":\"Algoexperthub community\",\"button_text\":\"Start Trading\",\"button_link\":\"register\",\"image_one\":\"\"}','default',0,'2025-12-05 08:55:58','2025-12-05 08:55:58'),(166,'non_iteratable','why_choose_us','{\"section_header\":\"Choose Us\",\"title\":\"Why Choose AlgoExperthub\",\"color_text_for_title\":\"AlgoExperthub\",\"image_one\":\"\"}','default',0,'2025-12-05 08:55:58','2025-12-05 08:55:58'),(167,'non_iteratable','referral','{\"section_header\":\"Referral\",\"title\":\"Our Forex Trading Referral\",\"color_text_for_title\":\"Trading Referral\",\"image_one\":\"\"}','default',0,'2025-12-05 08:55:58','2025-12-05 08:55:58'),(168,'non_iteratable','team','{\"section_header\":\"Our Team\",\"title\":\"Our Forex Trading Specialist\",\"color_text_for_title\":\"Forex Trading\",\"image_one\":\"\"}','default',0,'2025-12-05 08:55:58','2025-12-05 08:55:58'),(169,'non_iteratable','testimonial','{\"section_header\":\"Testimonials\",\"title\":\"What Our Customer Says\",\"color_text_for_title\":\"Our Customer\",\"image_one\":\"\"}','default',0,'2025-12-05 08:55:58','2025-12-05 08:55:58'),(170,'non_iteratable','blog','{\"section_header\":\"Blog Post\",\"title\":\"Our Latest News\",\"color_text_for_title\":\"News\",\"image_one\":\"\"}','default',0,'2025-12-05 08:55:58','2025-12-05 08:55:58'),(171,'non_iteratable','auth','{\"title\":\"Welcome to AlgoExpertHub\",\"image_one\":\"\"}','default',0,'2025-12-05 08:55:58','2025-12-05 08:55:58'),(172,'iteratable','benefits','{\"title\":\"20+ Proven Trading Strategies\",\"icon\":\"fab fa-searchengin\",\"description\":\"Access a curated library of institutional-grade strategies ready to deploy or customize.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:55:58','2025-12-05 08:55:58'),(173,'iteratable','benefits','{\"title\":\"VIP Insights & Direct Support\",\"icon\":\"far fa-user\",\"description\":\"Get exclusive market analysis from our pros and real-time signals via VIP Telegram groups.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:55:58','2025-12-05 08:55:58'),(174,'iteratable','benefits','{\"title\":\"AI-Powered Market Forecasting\",\"icon\":\"far fa-thumbs-up\",\"description\":\"Let our advanced AI analyze sentiment, patterns, and correlations across markets.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:55:58','2025-12-05 08:55:58'),(175,'iteratable','benefits','{\"title\":\"Seamless Autotrading Execution\",\"icon\":\"far fa-chart-bar\",\"description\":\"Set your rules once. Our system executes trades 24\\/7 with precision speed.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:55:58','2025-12-05 08:55:58'),(176,'iteratable','benefits','{\"title\":\"Multi-Channel Alert System\",\"icon\":\"far fa-envelope\",\"description\":\"Receive critical trade signals via Telegram. Never miss a key market movement.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:55:58','2025-12-05 08:55:58'),(177,'iteratable','benefits','{\"title\":\"Join a Growing Community\",\"icon\":\"fas fa-users\",\"description\":\"Connect with traders worldwide and share strategies.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:55:58','2025-12-05 08:55:58'),(178,'iteratable','how_works','{\"title\":\"Create Account\",\"description\":\"Simple registration process. No credit card required for trial.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:55:58','2025-12-05 08:55:58'),(179,'iteratable','how_works','{\"title\":\"Select Package\",\"description\":\"Choose your subscription plan. Flexible monthly or yearly options.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:55:58','2025-12-05 08:55:58'),(180,'iteratable','how_works','{\"title\":\"Start Trading\",\"description\":\"Activate autotrading and let the algo work for you.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:55:58','2025-12-05 08:55:58'),(181,'iteratable','socials','{\"icon\":\"fab fa-facebook-f\",\"link\":\"https:\\/\\/facebook.com\",\"image_one\":\"\"}','default',0,'2025-12-05 08:55:58','2025-12-05 08:55:58'),(182,'iteratable','socials','{\"icon\":\"fab fa-twitter\",\"link\":\"https:\\/\\/twitter.com\",\"image_one\":\"\"}','default',0,'2025-12-05 08:55:58','2025-12-05 08:55:58'),(183,'iteratable','socials','{\"icon\":\"fab fa-telegram-plane\",\"link\":\"https:\\/\\/t.me\\/algoexperthub\",\"image_one\":\"\"}','default',0,'2025-12-05 08:55:58','2025-12-05 08:55:58'),(184,'non_iteratable','banner','{\"title\":\"Automate, Copy, or Lead \\u2013 Your Trading\",\"color_text_for_title\":\"Ecosystem Awaits\",\"button_text\":\"Get Started\",\"button_text_link\":\"register\",\"image_one\":\"\",\"image_two\":\"\",\"repeater\":[{\"repeater\":\"Let our AI engine forecast and execute across all markets\"},{\"repeater\":\"Make smarter decisions and build a profile others pay to copy\"},{\"repeater\":\"Discover seamless autotrading tailored to your risk, on any market\"}]}','default',0,'2025-12-05 08:57:59','2025-12-05 08:57:59'),(185,'non_iteratable','about','{\"title\":\"Unlock Your Edge in Algorithmic Trading\",\"color_text_for_title\":\"AlgoExperthub\",\"button_text\":\"Launch Your Edge\",\"button_link\":\"\\/register\",\"description\":\"AlgoExperthub is your all-in-one platform to automate, analyze, and amplify your trading. Leverage AI, institutional strategies, and a mastermind community.\",\"image_one\":\"\",\"image_two\":\"\",\"repeater\":[{\"repeater\":\"Your Trading Signals Are Already Obsolete. Evolve Your Edge.\"},{\"repeater\":\"From Manual Trading to Automated Strategy Architect.\"},{\"repeater\":\"Trade with Conviction, Backed by AI & Institutional Tools.\"}]}','default',0,'2025-12-05 08:57:59','2025-12-05 08:57:59'),(186,'non_iteratable','benefits','{\"section_header\":\"Summary of Benefits\",\"title\":\"Everything You Need to Fast Track Your Trading\",\"color_text_for_title\":\"Track Your Trading\",\"image_one\":\"\"}','default',0,'2025-12-05 08:57:59','2025-12-05 08:57:59'),(187,'non_iteratable','how_works','{\"section_header\":\"How it Works\",\"title\":\"Started Trading With Algoexperthub\",\"color_text_for_title\":\"With Algoexperthub\",\"image_one\":\"\"}','default',0,'2025-12-05 08:57:59','2025-12-05 08:57:59'),(188,'non_iteratable','plans','{\"section_header\":\"Packages\",\"title\":\"Our Best Packages\",\"color_text_for_title\":\"Packages\",\"image_one\":\"\"}','default',0,'2025-12-05 08:57:59','2025-12-05 08:57:59'),(189,'non_iteratable','contact','{\"section_header\":\"Contact\",\"title\":\"We\'d Love to Hear From You\",\"color_text_for_title\":\"Hear From You\",\"email\":\"support@algoexperthub.com\",\"phone\":\"+1 (800) 123-4567\",\"address\":\"Visit our office HQ, Trading Center, New York\",\"form_header\":\"Love to hear from you, Get in touch\",\"color_text_for_form_header\":\"Get in touch\",\"image_one\":\"\"}','default',0,'2025-12-05 08:57:59','2025-12-05 08:57:59'),(190,'non_iteratable','footer','{\"footer_short_details\":\"AlgoExpertHub - Advanced trading signals platform powered by AI and institutional-grade strategies.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:57:59','2025-12-05 08:57:59'),(191,'non_iteratable','trade','{\"section_header\":\"Live Trading\",\"title\":\"Join the Algoexperthub community\",\"color_text_for_title\":\"Algoexperthub community\",\"button_text\":\"Start Trading\",\"button_link\":\"register\",\"image_one\":\"\"}','default',0,'2025-12-05 08:57:59','2025-12-05 08:57:59'),(192,'non_iteratable','why_choose_us','{\"section_header\":\"Choose Us\",\"title\":\"Why Choose AlgoExperthub\",\"color_text_for_title\":\"AlgoExperthub\",\"image_one\":\"\"}','default',0,'2025-12-05 08:57:59','2025-12-05 08:57:59'),(193,'non_iteratable','referral','{\"section_header\":\"Referral\",\"title\":\"Our Forex Trading Referral\",\"color_text_for_title\":\"Trading Referral\",\"image_one\":\"\"}','default',0,'2025-12-05 08:57:59','2025-12-05 08:57:59'),(194,'non_iteratable','team','{\"section_header\":\"Our Team\",\"title\":\"Our Forex Trading Specialist\",\"color_text_for_title\":\"Forex Trading\",\"image_one\":\"\"}','default',0,'2025-12-05 08:57:59','2025-12-05 08:57:59'),(195,'non_iteratable','testimonial','{\"section_header\":\"Testimonials\",\"title\":\"What Our Customer Says\",\"color_text_for_title\":\"Our Customer\",\"image_one\":\"\"}','default',0,'2025-12-05 08:57:59','2025-12-05 08:57:59'),(196,'non_iteratable','blog','{\"section_header\":\"Blog Post\",\"title\":\"Our Latest News\",\"color_text_for_title\":\"News\",\"image_one\":\"\"}','default',0,'2025-12-05 08:57:59','2025-12-05 08:57:59'),(197,'non_iteratable','auth','{\"title\":\"Welcome to AlgoExpertHub\",\"image_one\":\"\"}','default',0,'2025-12-05 08:57:59','2025-12-05 08:57:59'),(198,'iteratable','benefits','{\"title\":\"20+ Proven Trading Strategies\",\"icon\":\"fab fa-searchengin\",\"description\":\"Access a curated library of institutional-grade strategies ready to deploy or customize.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:57:59','2025-12-05 08:57:59'),(199,'iteratable','benefits','{\"title\":\"VIP Insights & Direct Support\",\"icon\":\"far fa-user\",\"description\":\"Get exclusive market analysis from our pros and real-time signals via VIP Telegram groups.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:57:59','2025-12-05 08:57:59'),(200,'iteratable','benefits','{\"title\":\"AI-Powered Market Forecasting\",\"icon\":\"far fa-thumbs-up\",\"description\":\"Let our advanced AI analyze sentiment, patterns, and correlations across markets.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:57:59','2025-12-05 08:57:59'),(201,'iteratable','benefits','{\"title\":\"Seamless Autotrading Execution\",\"icon\":\"far fa-chart-bar\",\"description\":\"Set your rules once. Our system executes trades 24\\/7 with precision speed.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:57:59','2025-12-05 08:57:59'),(202,'iteratable','benefits','{\"title\":\"Multi-Channel Alert System\",\"icon\":\"far fa-envelope\",\"description\":\"Receive critical trade signals via Telegram. Never miss a key market movement.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:57:59','2025-12-05 08:57:59'),(203,'iteratable','benefits','{\"title\":\"Join a Growing Community\",\"icon\":\"fas fa-users\",\"description\":\"Connect with traders worldwide and share strategies.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:57:59','2025-12-05 08:57:59'),(204,'iteratable','how_works','{\"title\":\"Create Account\",\"description\":\"Simple registration process. No credit card required for trial.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:57:59','2025-12-05 08:57:59'),(205,'iteratable','how_works','{\"title\":\"Select Package\",\"description\":\"Choose your subscription plan. Flexible monthly or yearly options.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:58:00','2025-12-05 08:58:00'),(206,'iteratable','how_works','{\"title\":\"Start Trading\",\"description\":\"Activate autotrading and let the algo work for you.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:58:00','2025-12-05 08:58:00'),(207,'iteratable','socials','{\"icon\":\"fab fa-facebook-f\",\"link\":\"https:\\/\\/facebook.com\",\"image_one\":\"\"}','default',0,'2025-12-05 08:58:00','2025-12-05 08:58:00'),(208,'iteratable','socials','{\"icon\":\"fab fa-twitter\",\"link\":\"https:\\/\\/twitter.com\",\"image_one\":\"\"}','default',0,'2025-12-05 08:58:00','2025-12-05 08:58:00'),(209,'iteratable','socials','{\"icon\":\"fab fa-telegram-plane\",\"link\":\"https:\\/\\/t.me\\/algoexperthub\",\"image_one\":\"\"}','default',0,'2025-12-05 08:58:00','2025-12-05 08:58:00'),(210,'non_iteratable','banner','{\"title\":\"Automate, Copy, or Lead \\u2013 Your Trading\",\"color_text_for_title\":\"Ecosystem Awaits\",\"button_text\":\"Get Started\",\"button_text_link\":\"register\",\"image_one\":\"\",\"image_two\":\"\",\"repeater\":[{\"repeater\":\"Let our AI engine forecast and execute across all markets\"},{\"repeater\":\"Make smarter decisions and build a profile others pay to copy\"},{\"repeater\":\"Discover seamless autotrading tailored to your risk, on any market\"}]}','default',0,'2025-12-05 08:58:04','2025-12-05 08:58:04'),(211,'non_iteratable','about','{\"title\":\"Unlock Your Edge in Algorithmic Trading\",\"color_text_for_title\":\"AlgoExperthub\",\"button_text\":\"Launch Your Edge\",\"button_link\":\"\\/register\",\"description\":\"AlgoExperthub is your all-in-one platform to automate, analyze, and amplify your trading. Leverage AI, institutional strategies, and a mastermind community.\",\"image_one\":\"\",\"image_two\":\"\",\"repeater\":[{\"repeater\":\"Your Trading Signals Are Already Obsolete. Evolve Your Edge.\"},{\"repeater\":\"From Manual Trading to Automated Strategy Architect.\"},{\"repeater\":\"Trade with Conviction, Backed by AI & Institutional Tools.\"}]}','default',0,'2025-12-05 08:58:04','2025-12-05 08:58:04'),(212,'non_iteratable','benefits','{\"section_header\":\"Summary of Benefits\",\"title\":\"Everything You Need to Fast Track Your Trading\",\"color_text_for_title\":\"Track Your Trading\",\"image_one\":\"\"}','default',0,'2025-12-05 08:58:04','2025-12-05 08:58:04'),(213,'non_iteratable','how_works','{\"section_header\":\"How it Works\",\"title\":\"Started Trading With Algoexperthub\",\"color_text_for_title\":\"With Algoexperthub\",\"image_one\":\"\"}','default',0,'2025-12-05 08:58:04','2025-12-05 08:58:04'),(214,'non_iteratable','plans','{\"section_header\":\"Packages\",\"title\":\"Our Best Packages\",\"color_text_for_title\":\"Packages\",\"image_one\":\"\"}','default',0,'2025-12-05 08:58:04','2025-12-05 08:58:04'),(215,'non_iteratable','contact','{\"section_header\":\"Contact\",\"title\":\"We\'d Love to Hear From You\",\"color_text_for_title\":\"Hear From You\",\"email\":\"support@algoexperthub.com\",\"phone\":\"+1 (800) 123-4567\",\"address\":\"Visit our office HQ, Trading Center, New York\",\"form_header\":\"Love to hear from you, Get in touch\",\"color_text_for_form_header\":\"Get in touch\",\"image_one\":\"\"}','default',0,'2025-12-05 08:58:04','2025-12-05 08:58:04'),(216,'non_iteratable','footer','{\"footer_short_details\":\"AlgoExpertHub - Advanced trading signals platform powered by AI and institutional-grade strategies.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:58:04','2025-12-05 08:58:04'),(217,'non_iteratable','trade','{\"section_header\":\"Live Trading\",\"title\":\"Join the Algoexperthub community\",\"color_text_for_title\":\"Algoexperthub community\",\"button_text\":\"Start Trading\",\"button_link\":\"register\",\"image_one\":\"\"}','default',0,'2025-12-05 08:58:04','2025-12-05 08:58:04'),(218,'non_iteratable','why_choose_us','{\"section_header\":\"Choose Us\",\"title\":\"Why Choose AlgoExperthub\",\"color_text_for_title\":\"AlgoExperthub\",\"image_one\":\"\"}','default',0,'2025-12-05 08:58:04','2025-12-05 08:58:04'),(219,'non_iteratable','referral','{\"section_header\":\"Referral\",\"title\":\"Our Forex Trading Referral\",\"color_text_for_title\":\"Trading Referral\",\"image_one\":\"\"}','default',0,'2025-12-05 08:58:04','2025-12-05 08:58:04'),(220,'non_iteratable','team','{\"section_header\":\"Our Team\",\"title\":\"Our Forex Trading Specialist\",\"color_text_for_title\":\"Forex Trading\",\"image_one\":\"\"}','default',0,'2025-12-05 08:58:04','2025-12-05 08:58:04'),(221,'non_iteratable','testimonial','{\"section_header\":\"Testimonials\",\"title\":\"What Our Customer Says\",\"color_text_for_title\":\"Our Customer\",\"image_one\":\"\"}','default',0,'2025-12-05 08:58:04','2025-12-05 08:58:04'),(222,'non_iteratable','blog','{\"section_header\":\"Blog Post\",\"title\":\"Our Latest News\",\"color_text_for_title\":\"News\",\"image_one\":\"\"}','default',0,'2025-12-05 08:58:04','2025-12-05 08:58:04'),(223,'non_iteratable','auth','{\"title\":\"Welcome to AlgoExpertHub\",\"image_one\":\"\"}','default',0,'2025-12-05 08:58:05','2025-12-05 08:58:05'),(224,'iteratable','benefits','{\"title\":\"20+ Proven Trading Strategies\",\"icon\":\"fab fa-searchengin\",\"description\":\"Access a curated library of institutional-grade strategies ready to deploy or customize.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:58:05','2025-12-05 08:58:05'),(225,'iteratable','benefits','{\"title\":\"VIP Insights & Direct Support\",\"icon\":\"far fa-user\",\"description\":\"Get exclusive market analysis from our pros and real-time signals via VIP Telegram groups.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:58:05','2025-12-05 08:58:05'),(226,'iteratable','benefits','{\"title\":\"AI-Powered Market Forecasting\",\"icon\":\"far fa-thumbs-up\",\"description\":\"Let our advanced AI analyze sentiment, patterns, and correlations across markets.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:58:05','2025-12-05 08:58:05'),(227,'iteratable','benefits','{\"title\":\"Seamless Autotrading Execution\",\"icon\":\"far fa-chart-bar\",\"description\":\"Set your rules once. Our system executes trades 24\\/7 with precision speed.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:58:05','2025-12-05 08:58:05'),(228,'iteratable','benefits','{\"title\":\"Multi-Channel Alert System\",\"icon\":\"far fa-envelope\",\"description\":\"Receive critical trade signals via Telegram. Never miss a key market movement.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:58:05','2025-12-05 08:58:05'),(229,'iteratable','benefits','{\"title\":\"Join a Growing Community\",\"icon\":\"fas fa-users\",\"description\":\"Connect with traders worldwide and share strategies.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:58:05','2025-12-05 08:58:05'),(230,'iteratable','how_works','{\"title\":\"Create Account\",\"description\":\"Simple registration process. No credit card required for trial.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:58:05','2025-12-05 08:58:05'),(231,'iteratable','how_works','{\"title\":\"Select Package\",\"description\":\"Choose your subscription plan. Flexible monthly or yearly options.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:58:05','2025-12-05 08:58:05'),(232,'iteratable','how_works','{\"title\":\"Start Trading\",\"description\":\"Activate autotrading and let the algo work for you.\",\"image_one\":\"\"}','default',0,'2025-12-05 08:58:05','2025-12-05 08:58:05'),(233,'iteratable','socials','{\"icon\":\"fab fa-facebook-f\",\"link\":\"https:\\/\\/facebook.com\",\"image_one\":\"\"}','default',0,'2025-12-05 08:58:05','2025-12-05 08:58:05'),(234,'iteratable','socials','{\"icon\":\"fab fa-twitter\",\"link\":\"https:\\/\\/twitter.com\",\"image_one\":\"\"}','default',0,'2025-12-05 08:58:05','2025-12-05 08:58:05'),(235,'iteratable','socials','{\"icon\":\"fab fa-telegram-plane\",\"link\":\"https:\\/\\/t.me\\/algoexperthub\",\"image_one\":\"\"}','default',0,'2025-12-05 08:58:05','2025-12-05 08:58:05');
/*!40000 ALTER TABLE `sp_contents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_copy_trading_executions`
--

DROP TABLE IF EXISTS `sp_copy_trading_executions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_dashboard_signals` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `plan_id` bigint(20) unsigned NOT NULL,
  `signal_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sp_dashboard_signals_user_id_signal_id_unique` (`user_id`,`signal_id`),
  UNIQUE KEY `dashboard_signals_user_id_signal_id_unique` (`user_id`,`signal_id`)
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
/*!50503 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=201 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_deposits`
--

LOCK TABLES `sp_deposits` WRITE;
/*!40000 ALTER TABLE `sp_deposits` DISABLE KEYS */;
INSERT INTO `sp_deposits` VALUES (1,4,4,'HLQMC5PBVW9OZ9CG',3700.00000000,1.00000000,0.00000000,3700.00000000,1,0,NULL,'2025-12-04 19:41:58','2025-12-04 19:41:58'),(2,5,13,'SH6PRQZVV51NEYS0',4233.00000000,1.00000000,0.00000000,4233.00000000,1,1,NULL,'2025-10-29 12:41:58','2025-10-29 12:41:58'),(3,3,3,'KCKJHE4WSHOAKWWT',4005.00000000,1.00000000,0.00000000,4005.00000000,3,0,NULL,'2025-10-12 21:41:58','2025-10-12 21:41:58'),(4,3,2,'2BYIKKSHHZOWF5XC',682.00000000,1.00000000,0.00000000,682.00000000,1,1,NULL,'2025-10-26 21:41:58','2025-10-26 21:41:58'),(5,2,12,'GHCKEBISCKQ2L0T5',3151.00000000,1.00000000,0.00000000,3151.00000000,3,0,NULL,'2025-11-13 12:41:58','2025-11-13 12:41:58'),(6,5,10,'ZQ5RKWVLHKAREFNK',2440.00000000,1.00000000,0.00000000,2440.00000000,3,1,NULL,'2025-10-27 04:41:58','2025-10-27 04:41:58'),(7,1,2,'UJR7GVIFEUKUEKNR',2248.00000000,1.00000000,0.00000000,2248.00000000,1,1,NULL,'2025-11-21 17:41:58','2025-11-21 17:41:58'),(8,1,9,'2TMKTGA97YDOBCS4',4088.00000000,1.00000000,0.00000000,4088.00000000,3,0,NULL,'2025-12-03 20:41:58','2025-12-03 20:41:58'),(9,2,10,'IHM7JUY0EOINTTAB',2572.00000000,1.00000000,0.00000000,2572.00000000,3,1,NULL,'2025-11-22 08:41:58','2025-11-22 08:41:58'),(10,2,3,'XDBAUB1BFKR3PXIR',2142.00000000,1.00000000,0.00000000,2142.00000000,1,0,NULL,'2025-11-08 13:41:59','2025-11-08 13:41:59'),(11,5,1,'OUJLA0JSYKC1SRB4',4920.00000000,1.00000000,0.00000000,4920.00000000,1,1,NULL,'2025-11-18 21:41:59','2025-11-18 21:41:59'),(12,5,3,'K5QM4FF0DANJMSEX',3952.00000000,1.00000000,0.00000000,3952.00000000,1,1,NULL,'2025-11-03 03:41:59','2025-11-03 03:41:59'),(13,5,3,'0E6813PJOCPMGGDK',4753.00000000,1.00000000,0.00000000,4753.00000000,2,1,NULL,'2025-10-09 12:41:59','2025-10-09 12:41:59'),(14,1,2,'RXLMTK30AJ3MJKTY',3955.00000000,1.00000000,0.00000000,3955.00000000,3,0,NULL,'2025-10-31 09:41:59','2025-10-31 09:41:59'),(15,3,6,'NTWJVTDMSGYBHIZB',4132.00000000,1.00000000,0.00000000,4132.00000000,1,0,NULL,'2025-10-15 22:41:59','2025-10-15 22:41:59'),(16,2,12,'X3YX0HLWRIFJAOQK',3965.00000000,1.00000000,0.00000000,3965.00000000,1,1,NULL,'2025-10-17 06:41:59','2025-10-17 06:41:59'),(17,3,5,'BXWJNZZNTY8PJKYP',2497.00000000,1.00000000,0.00000000,2497.00000000,1,0,NULL,'2025-10-26 23:41:59','2025-10-26 23:41:59'),(18,1,2,'PRQDVCWIRGBREMGS',1546.00000000,1.00000000,0.00000000,1546.00000000,2,1,NULL,'2025-10-31 02:41:59','2025-10-31 02:41:59'),(19,5,1,'XQS4XNQVF8ZDFSKM',1671.00000000,1.00000000,0.00000000,1671.00000000,1,0,NULL,'2025-11-13 07:41:59','2025-11-13 07:41:59'),(20,2,10,'UWH53GVO6N51J8VV',2479.00000000,1.00000000,0.00000000,2479.00000000,2,1,NULL,'2025-11-29 22:41:59','2025-11-29 22:41:59'),(21,4,8,'4REBZHAMVAGHF6N7',3853.00000000,1.00000000,0.00000000,3853.00000000,3,1,NULL,'2025-10-25 08:41:59','2025-10-25 08:41:59'),(22,1,1,'YASFBE2LCPTUMGC7',4913.00000000,1.00000000,0.00000000,4913.00000000,3,1,NULL,'2025-11-05 01:41:59','2025-11-05 01:41:59'),(23,2,14,'PJJEJARUSRFXPLO3',1566.00000000,1.00000000,0.00000000,1566.00000000,2,0,NULL,'2025-10-28 23:42:00','2025-10-28 23:42:00'),(24,4,2,'OLDCRAFSVUIABIBY',3765.00000000,1.00000000,0.00000000,3765.00000000,2,1,NULL,'2025-11-10 23:42:00','2025-11-10 23:42:00'),(25,4,7,'QMYBG0B9RSWDEIQG',3702.00000000,1.00000000,0.00000000,3702.00000000,3,1,NULL,'2025-10-14 19:42:00','2025-10-14 19:42:00'),(26,4,4,'UNORQ8IJV4YFHS16',4741.00000000,1.00000000,0.00000000,4741.00000000,1,0,NULL,'2025-10-07 12:42:00','2025-10-07 12:42:00'),(27,1,13,'XFG6W9TWH7OR5OF5',4666.00000000,1.00000000,0.00000000,4666.00000000,1,0,NULL,'2025-11-14 03:42:00','2025-11-14 03:42:00'),(28,1,8,'12FK6MJDS9H4CR5B',1783.00000000,1.00000000,0.00000000,1783.00000000,3,0,NULL,'2025-10-31 06:42:00','2025-10-31 06:42:00'),(29,2,13,'PNMPISJJERNPDLFU',3793.00000000,1.00000000,0.00000000,3793.00000000,2,1,NULL,'2025-11-21 14:42:00','2025-11-21 14:42:00'),(30,3,11,'R2KRGH3UXONKVMTY',1962.00000000,1.00000000,0.00000000,1962.00000000,3,0,NULL,'2025-11-22 03:42:00','2025-11-22 03:42:00'),(31,1,2,'JRC5SWN8QS24SBIU',3809.00000000,1.00000000,0.00000000,3809.00000000,2,1,NULL,'2025-11-07 16:42:00','2025-11-07 16:42:00'),(32,3,12,'M4HWMP3FUNCTLQU3',4532.00000000,1.00000000,0.00000000,4532.00000000,3,0,NULL,'2025-11-22 19:42:00','2025-11-22 19:42:00'),(33,1,13,'DKOYH69BQKI4X8M7',4889.00000000,1.00000000,0.00000000,4889.00000000,2,1,NULL,'2025-10-31 03:42:00','2025-10-31 03:42:00'),(34,2,12,'REYIJJ50SRC8NWM5',4563.00000000,1.00000000,0.00000000,4563.00000000,1,0,NULL,'2025-10-30 09:42:00','2025-10-30 09:42:00'),(35,4,4,'M7QB5ZO6MNAA3MJ1',2140.00000000,1.00000000,0.00000000,2140.00000000,2,1,NULL,'2025-11-27 04:42:00','2025-11-27 04:42:00'),(36,5,2,'XETHUHRSKCVNFZKB',526.00000000,1.00000000,0.00000000,526.00000000,1,0,NULL,'2025-10-22 22:42:00','2025-10-22 22:42:00'),(37,1,13,'P6P2MQ7UM527X3OL',1104.00000000,1.00000000,0.00000000,1104.00000000,1,0,NULL,'2025-10-08 10:42:00','2025-10-08 10:42:00'),(38,3,11,'E9MSFXJ3LTWZQCMP',815.00000000,1.00000000,0.00000000,815.00000000,2,1,NULL,'2025-11-27 19:42:00','2025-11-27 19:42:00'),(39,4,10,'YOUEYEUIZOCRHMLI',1742.00000000,1.00000000,0.00000000,1742.00000000,2,0,NULL,'2025-11-24 10:42:00','2025-11-24 10:42:00'),(40,4,8,'XKXAWQVJXBSZKUJV',4011.00000000,1.00000000,0.00000000,4011.00000000,3,1,NULL,'2025-11-02 22:42:00','2025-11-02 22:42:00'),(41,5,11,'JUYSHERQJUTOZOSY',3168.00000000,1.00000000,0.00000000,3168.00000000,2,1,NULL,'2025-11-14 01:42:00','2025-11-14 01:42:00'),(42,5,4,'IHNXMWWWVQKG2WAZ',4479.00000000,1.00000000,0.00000000,4479.00000000,1,0,NULL,'2025-10-06 17:42:00','2025-10-06 17:42:00'),(43,3,5,'YN5VDQMDIK2DKZT2',3300.00000000,1.00000000,0.00000000,3300.00000000,1,1,NULL,'2025-11-12 03:42:00','2025-11-12 03:42:00'),(44,3,13,'RLWFTFO74KCB3CW5',637.00000000,1.00000000,0.00000000,637.00000000,1,0,NULL,'2025-10-14 07:42:01','2025-10-14 07:42:01'),(45,4,6,'YRBZ9NFEXT7QOEDG',1093.00000000,1.00000000,0.00000000,1093.00000000,2,0,NULL,'2025-11-07 13:42:01','2025-11-07 13:42:01'),(46,3,4,'8MNS9O6FIQUDZUY5',1828.00000000,1.00000000,0.00000000,1828.00000000,1,1,NULL,'2025-10-26 02:42:01','2025-10-26 02:42:01'),(47,1,8,'QAAF35MO6QYJRSOU',2669.00000000,1.00000000,0.00000000,2669.00000000,1,0,NULL,'2025-11-09 18:42:01','2025-11-09 18:42:01'),(48,4,9,'YL1SQVH3C1AVYG0C',3967.00000000,1.00000000,0.00000000,3967.00000000,1,0,NULL,'2025-12-03 01:42:01','2025-12-03 01:42:01'),(49,2,11,'9TWKQUVGZPRWL1C7',3175.00000000,1.00000000,0.00000000,3175.00000000,1,1,NULL,'2025-11-29 22:42:01','2025-11-29 22:42:01'),(50,1,9,'GPYWCS55Z4MTWMT3',818.00000000,1.00000000,0.00000000,818.00000000,2,1,NULL,'2025-10-19 02:42:01','2025-10-19 02:42:01'),(51,4,13,'ATCLV5B6NGLRFQWA',2353.00000000,1.00000000,0.00000000,2353.00000000,2,0,NULL,'2025-11-08 14:45:41','2025-11-08 14:45:41'),(52,2,5,'TTKOT8SNJ6YPGM0T',4947.00000000,1.00000000,0.00000000,4947.00000000,1,1,NULL,'2025-10-07 18:45:42','2025-10-07 18:45:42'),(53,3,7,'6HIDH7ZKPXPQKBUY',996.00000000,1.00000000,0.00000000,996.00000000,2,1,NULL,'2025-11-25 07:45:42','2025-11-25 07:45:42'),(54,4,4,'BB68SDEXBLWASFGZ',1842.00000000,1.00000000,0.00000000,1842.00000000,2,0,NULL,'2025-10-16 06:45:42','2025-10-16 06:45:42'),(55,4,9,'V3L0YQJTMURKV1FB',3181.00000000,1.00000000,0.00000000,3181.00000000,2,1,NULL,'2025-10-22 00:45:42','2025-10-22 00:45:42'),(56,3,5,'Y9HHRXYYQ020HQ6O',364.00000000,1.00000000,0.00000000,364.00000000,1,1,NULL,'2025-10-12 03:45:42','2025-10-12 03:45:42'),(57,5,8,'THN4TGBAOO2PIWHG',2704.00000000,1.00000000,0.00000000,2704.00000000,3,1,NULL,'2025-12-02 10:45:42','2025-12-02 10:45:42'),(58,1,6,'MSQNFML7DIQRDWQX',2987.00000000,1.00000000,0.00000000,2987.00000000,2,0,NULL,'2025-11-01 19:45:42','2025-11-01 19:45:42'),(59,2,6,'A5KKLLY4BUTACKX0',1126.00000000,1.00000000,0.00000000,1126.00000000,3,1,NULL,'2025-11-16 19:45:43','2025-11-16 19:45:43'),(60,1,6,'1UWSX3KOQKKLINPE',3267.00000000,1.00000000,0.00000000,3267.00000000,1,1,NULL,'2025-11-26 16:45:43','2025-11-26 16:45:43'),(61,2,1,'4U4PI2EJGGGWRRGO',957.00000000,1.00000000,0.00000000,957.00000000,1,0,NULL,'2025-12-01 05:45:43','2025-12-01 05:45:43'),(62,3,3,'MWLA0EEP14W0T2NF',3597.00000000,1.00000000,0.00000000,3597.00000000,2,1,NULL,'2025-10-05 17:45:43','2025-10-05 17:45:43'),(63,1,8,'21PLNGGPC7QAYP9F',280.00000000,1.00000000,0.00000000,280.00000000,1,1,NULL,'2025-11-08 21:45:43','2025-11-08 21:45:43'),(64,1,8,'UIERWIBLOVOT89RL',2436.00000000,1.00000000,0.00000000,2436.00000000,3,1,NULL,'2025-10-15 02:45:43','2025-10-15 02:45:43'),(65,2,5,'P9OTT4SWH6KLCTJ3',1643.00000000,1.00000000,0.00000000,1643.00000000,3,0,NULL,'2025-10-28 13:45:43','2025-10-28 13:45:43'),(66,2,11,'GMEZCGXFHJKKDRK3',1946.00000000,1.00000000,0.00000000,1946.00000000,1,0,NULL,'2025-10-17 14:45:44','2025-10-17 14:45:44'),(67,3,1,'0NIAXNJYUCCLC7HA',3959.00000000,1.00000000,0.00000000,3959.00000000,3,0,NULL,'2025-12-03 14:45:44','2025-12-03 14:45:44'),(68,5,8,'424IKDYXIAL5F00D',3828.00000000,1.00000000,0.00000000,3828.00000000,2,1,NULL,'2025-12-05 07:45:44','2025-12-05 07:45:44'),(69,5,13,'KJFJUDZZOPVS4JVM',4642.00000000,1.00000000,0.00000000,4642.00000000,2,0,NULL,'2025-11-25 23:45:44','2025-11-25 23:45:44'),(70,3,2,'1T00IUOSN19CWPMG',1527.00000000,1.00000000,0.00000000,1527.00000000,2,1,NULL,'2025-10-22 22:45:44','2025-10-22 22:45:44'),(71,4,7,'J85XGKSYGT5ROAMN',826.00000000,1.00000000,0.00000000,826.00000000,2,0,NULL,'2025-10-30 18:45:44','2025-10-30 18:45:44'),(72,2,5,'SECIJTFT5LM7MQVB',2390.00000000,1.00000000,0.00000000,2390.00000000,3,1,NULL,'2025-12-04 15:45:44','2025-12-04 15:45:44'),(73,4,3,'ERORHWISU4XG8Z8J',616.00000000,1.00000000,0.00000000,616.00000000,2,1,NULL,'2025-12-03 17:45:44','2025-12-03 17:45:44'),(74,1,4,'I3VZSWGYLJ4INFAU',4218.00000000,1.00000000,0.00000000,4218.00000000,2,1,NULL,'2025-10-29 09:45:45','2025-10-29 09:45:45'),(75,3,8,'ZHWHIR3XAEFEPJLV',1739.00000000,1.00000000,0.00000000,1739.00000000,1,0,NULL,'2025-10-29 17:45:45','2025-10-29 17:45:45'),(76,3,7,'I9U4NENL6S8RRJPE',2805.00000000,1.00000000,0.00000000,2805.00000000,2,0,NULL,'2025-10-23 00:47:14','2025-10-23 00:47:14'),(77,1,2,'F0HNDZ0KFJLEMLIY',2615.00000000,1.00000000,0.00000000,2615.00000000,2,0,NULL,'2025-11-08 08:47:14','2025-11-08 08:47:14'),(78,2,4,'1XDJAELAUGJYDTWI',1988.00000000,1.00000000,0.00000000,1988.00000000,2,0,NULL,'2025-10-16 18:47:14','2025-10-16 18:47:14'),(79,5,8,'R0YYSARRQBEQFK1T',1726.00000000,1.00000000,0.00000000,1726.00000000,2,1,NULL,'2025-12-04 11:47:14','2025-12-04 11:47:14'),(80,3,11,'9AMIYKN0YFTNLJIQ',3640.00000000,1.00000000,0.00000000,3640.00000000,2,1,NULL,'2025-11-13 22:47:14','2025-11-13 22:47:14'),(81,3,6,'ZJWGSZSGBERPI3BX',4759.00000000,1.00000000,0.00000000,4759.00000000,2,0,NULL,'2025-10-15 12:47:14','2025-10-15 12:47:14'),(82,3,4,'0JYNHSYNTTWFK7KJ',1021.00000000,1.00000000,0.00000000,1021.00000000,2,1,NULL,'2025-11-29 14:47:14','2025-11-29 14:47:14'),(83,4,5,'LIHPPKMJ9PZP46EC',1483.00000000,1.00000000,0.00000000,1483.00000000,3,0,NULL,'2025-10-31 02:47:14','2025-10-31 02:47:14'),(84,5,6,'VK6MMQ1ZDBQRRYJX',925.00000000,1.00000000,0.00000000,925.00000000,3,1,NULL,'2025-11-20 23:47:14','2025-11-20 23:47:14'),(85,2,14,'W1QA5RHWGSTQC2WC',2762.00000000,1.00000000,0.00000000,2762.00000000,1,1,NULL,'2025-11-14 19:47:14','2025-11-14 19:47:14'),(86,4,4,'XSMHHW6LY8HZSW7D',1477.00000000,1.00000000,0.00000000,1477.00000000,2,1,NULL,'2025-11-21 00:47:14','2025-11-21 00:47:14'),(87,3,5,'15ODGFHVC6KOLLBS',4605.00000000,1.00000000,0.00000000,4605.00000000,2,0,NULL,'2025-10-28 12:47:14','2025-10-28 12:47:14'),(88,2,8,'UCMY1RSNT0MKGN43',934.00000000,1.00000000,0.00000000,934.00000000,1,1,NULL,'2025-11-16 21:47:14','2025-11-16 21:47:14'),(89,1,2,'LWSYUWPYMNCEAJI8',1136.00000000,1.00000000,0.00000000,1136.00000000,3,0,NULL,'2025-11-26 15:47:14','2025-11-26 15:47:14'),(90,5,5,'CTBNHNZPEQZRQIVY',1577.00000000,1.00000000,0.00000000,1577.00000000,1,0,NULL,'2025-12-03 20:47:14','2025-12-03 20:47:14'),(91,4,11,'FPSZEOFTXP7IKUXD',1268.00000000,1.00000000,0.00000000,1268.00000000,2,1,NULL,'2025-10-12 15:47:14','2025-10-12 15:47:14'),(92,4,3,'TAK4WAJDFYWCITLC',4100.00000000,1.00000000,0.00000000,4100.00000000,3,0,NULL,'2025-11-03 15:47:14','2025-11-03 15:47:14'),(93,5,9,'7MOLORYOK9JBYF6G',1503.00000000,1.00000000,0.00000000,1503.00000000,3,1,NULL,'2025-12-02 15:47:14','2025-12-02 15:47:14'),(94,4,9,'EEXNNNFKOV0PWPMH',562.00000000,1.00000000,0.00000000,562.00000000,3,1,NULL,'2025-10-18 04:47:14','2025-10-18 04:47:14'),(95,3,10,'FODPKGWKWWJU775V',3304.00000000,1.00000000,0.00000000,3304.00000000,3,1,NULL,'2025-11-02 13:47:14','2025-11-02 13:47:14'),(96,5,6,'EQVHQ6NUM486MTUI',1049.00000000,1.00000000,0.00000000,1049.00000000,1,1,NULL,'2025-11-19 09:47:14','2025-11-19 09:47:14'),(97,2,5,'K93SEK7PSLESB9Y6',2828.00000000,1.00000000,0.00000000,2828.00000000,3,0,NULL,'2025-12-03 07:47:14','2025-12-03 07:47:14'),(98,3,12,'63TPVYEOXRDRZHWG',4507.00000000,1.00000000,0.00000000,4507.00000000,3,1,NULL,'2025-11-08 20:47:14','2025-11-08 20:47:14'),(99,5,12,'RI1YJXDFIMCXAABF',2706.00000000,1.00000000,0.00000000,2706.00000000,3,0,NULL,'2025-11-07 11:47:14','2025-11-07 11:47:14'),(100,3,5,'KIFJXCN9G1VFRWAZ',2087.00000000,1.00000000,0.00000000,2087.00000000,2,1,NULL,'2025-10-27 17:47:14','2025-10-27 17:47:14'),(101,5,1,'AWHVZ2D7BEMFX5NY',4818.00000000,1.00000000,0.00000000,4818.00000000,1,0,NULL,'2025-11-30 10:48:38','2025-11-30 10:48:38'),(102,5,3,'RXNE9C9QIWUEHEWQ',4210.00000000,1.00000000,0.00000000,4210.00000000,2,1,NULL,'2025-10-17 20:48:38','2025-10-17 20:48:38'),(103,5,6,'CYXK0WG2XJ0F8J3W',3092.00000000,1.00000000,0.00000000,3092.00000000,3,1,NULL,'2025-10-12 12:48:38','2025-10-12 12:48:38'),(104,4,14,'4MVPIR0TSNUNC1TZ',1347.00000000,1.00000000,0.00000000,1347.00000000,2,0,NULL,'2025-12-02 10:48:38','2025-12-02 10:48:38'),(105,4,5,'EU9FIJHMXDRVVMSI',3372.00000000,1.00000000,0.00000000,3372.00000000,2,1,NULL,'2025-10-30 14:48:38','2025-10-30 14:48:38'),(106,3,9,'OTSOAADEHT8JIV5P',2798.00000000,1.00000000,0.00000000,2798.00000000,3,0,NULL,'2025-10-13 11:48:38','2025-10-13 11:48:38'),(107,1,3,'VY2THXIXK3JTQHJA',2069.00000000,1.00000000,0.00000000,2069.00000000,2,1,NULL,'2025-11-16 18:48:38','2025-11-16 18:48:38'),(108,1,13,'QGTITYTYRUYOUDYQ',4542.00000000,1.00000000,0.00000000,4542.00000000,2,1,NULL,'2025-10-17 00:48:38','2025-10-17 00:48:38'),(109,2,11,'MLHZZASUXRZNOKTA',1150.00000000,1.00000000,0.00000000,1150.00000000,1,1,NULL,'2025-10-10 23:48:39','2025-10-10 23:48:39'),(110,1,5,'C4HBKZROCXWVAJON',3848.00000000,1.00000000,0.00000000,3848.00000000,2,1,NULL,'2025-10-28 21:48:39','2025-10-28 21:48:39'),(111,5,8,'X8L3CQ7JKC8N8YAD',4372.00000000,1.00000000,0.00000000,4372.00000000,2,1,NULL,'2025-11-19 12:48:39','2025-11-19 12:48:39'),(112,2,6,'BKWELLB7TQIODE68',971.00000000,1.00000000,0.00000000,971.00000000,1,0,NULL,'2025-10-14 00:48:39','2025-10-14 00:48:39'),(113,4,3,'EWZ6ADEPSKZDPRHG',1531.00000000,1.00000000,0.00000000,1531.00000000,1,0,NULL,'2025-11-02 00:48:39','2025-11-02 00:48:39'),(114,4,2,'KMNTFAFVSPS8O7IR',4152.00000000,1.00000000,0.00000000,4152.00000000,2,0,NULL,'2025-11-05 00:48:39','2025-11-05 00:48:39'),(115,2,2,'MJOFDG6VZ68TX1FJ',849.00000000,1.00000000,0.00000000,849.00000000,3,0,NULL,'2025-11-08 14:48:39','2025-11-08 14:48:39'),(116,3,12,'MYOIJCPPXWDWUKBA',870.00000000,1.00000000,0.00000000,870.00000000,3,1,NULL,'2025-10-18 22:48:39','2025-10-18 22:48:39'),(117,3,6,'DU4LLNZGT1TBXWZT',4672.00000000,1.00000000,0.00000000,4672.00000000,1,1,NULL,'2025-10-13 16:48:39','2025-10-13 16:48:39'),(118,5,6,'Z2NBJ0X5CWEMZDCD',4469.00000000,1.00000000,0.00000000,4469.00000000,2,1,NULL,'2025-11-02 02:48:39','2025-11-02 02:48:39'),(119,1,12,'BRUWSI6UTAHXYRMK',2486.00000000,1.00000000,0.00000000,2486.00000000,1,1,NULL,'2025-10-15 14:48:39','2025-10-15 14:48:39'),(120,4,9,'IAYNPHOARUVKXCG1',1764.00000000,1.00000000,0.00000000,1764.00000000,2,0,NULL,'2025-11-04 01:48:39','2025-11-04 01:48:39'),(121,3,3,'ZO9BILAWZDJDSQYO',732.00000000,1.00000000,0.00000000,732.00000000,2,1,NULL,'2025-10-29 04:48:39','2025-10-29 04:48:39'),(122,1,9,'LHCXRMLIR4AKO0QA',4648.00000000,1.00000000,0.00000000,4648.00000000,3,1,NULL,'2025-12-04 11:48:39','2025-12-04 11:48:39'),(123,2,10,'SXFLC3T8XBYZAVWN',3648.00000000,1.00000000,0.00000000,3648.00000000,1,0,NULL,'2025-10-15 00:48:39','2025-10-15 00:48:39'),(124,4,4,'JRQDDA7LMODASIZW',1892.00000000,1.00000000,0.00000000,1892.00000000,3,1,NULL,'2025-12-04 22:48:39','2025-12-04 22:48:39'),(125,2,5,'PKZ78FDVQZERTVT2',3641.00000000,1.00000000,0.00000000,3641.00000000,2,0,NULL,'2025-10-25 15:48:39','2025-10-25 15:48:39'),(126,5,6,'T9SMPUATNGDWGWAF',4410.00000000,1.00000000,0.00000000,4410.00000000,1,1,NULL,'2025-11-20 16:55:58','2025-11-20 16:55:58'),(127,2,10,'FYNBSERMIUCD2A6B',210.00000000,1.00000000,0.00000000,210.00000000,1,0,NULL,'2025-10-17 02:55:58','2025-10-17 02:55:58'),(128,4,5,'21VDEI6LBEPQ34FX',2030.00000000,1.00000000,0.00000000,2030.00000000,1,0,NULL,'2025-10-21 16:55:58','2025-10-21 16:55:58'),(129,2,6,'YXNSPIWVXZ6HUOLK',576.00000000,1.00000000,0.00000000,576.00000000,2,1,NULL,'2025-10-08 06:55:58','2025-10-08 06:55:58'),(130,3,13,'IRRUTXWLJQLR5UPH',1209.00000000,1.00000000,0.00000000,1209.00000000,1,1,NULL,'2025-11-02 04:55:58','2025-11-02 04:55:58'),(131,5,9,'IDG3HODQIBTDVPB5',1431.00000000,1.00000000,0.00000000,1431.00000000,1,1,NULL,'2025-10-20 14:55:58','2025-10-20 14:55:58'),(132,3,11,'VOIQT71S8RVJP6EX',4845.00000000,1.00000000,0.00000000,4845.00000000,1,1,NULL,'2025-10-18 13:55:58','2025-10-18 13:55:58'),(133,4,11,'LW5L9HKILSQNAJNG',710.00000000,1.00000000,0.00000000,710.00000000,2,1,NULL,'2025-11-12 15:55:58','2025-11-12 15:55:58'),(134,1,6,'RHAEQFYISZUWY2AG',1731.00000000,1.00000000,0.00000000,1731.00000000,3,1,NULL,'2025-10-08 07:55:59','2025-10-08 07:55:59'),(135,5,2,'ZU9A6VCHNN2ZVZGA',4281.00000000,1.00000000,0.00000000,4281.00000000,2,0,NULL,'2025-10-16 08:55:59','2025-10-16 08:55:59'),(136,5,1,'C7AMRCLFOJZZVRFU',924.00000000,1.00000000,0.00000000,924.00000000,2,0,NULL,'2025-11-04 00:55:59','2025-11-04 00:55:59'),(137,5,1,'RGU1MERJVOV0LYUJ',3290.00000000,1.00000000,0.00000000,3290.00000000,2,0,NULL,'2025-10-07 16:55:59','2025-10-07 16:55:59'),(138,2,11,'G8PBBZF4K0GFLJWH',675.00000000,1.00000000,0.00000000,675.00000000,3,0,NULL,'2025-11-30 19:55:59','2025-11-30 19:55:59'),(139,3,1,'CQCYHRHOMKWCGTYY',4265.00000000,1.00000000,0.00000000,4265.00000000,3,0,NULL,'2025-12-02 19:55:59','2025-12-02 19:55:59'),(140,3,13,'KA53U2IOZXVBT4LD',3844.00000000,1.00000000,0.00000000,3844.00000000,3,1,NULL,'2025-10-14 19:55:59','2025-10-14 19:55:59'),(141,3,13,'UV9CIHV601W1S0XQ',4627.00000000,1.00000000,0.00000000,4627.00000000,2,0,NULL,'2025-10-06 11:55:59','2025-10-06 11:55:59'),(142,1,11,'RPAC8YAWLQ88V3HN',239.00000000,1.00000000,0.00000000,239.00000000,2,0,NULL,'2025-11-24 20:55:59','2025-11-24 20:55:59'),(143,5,9,'14ANAZLJAR2XAZVB',4148.00000000,1.00000000,0.00000000,4148.00000000,3,0,NULL,'2025-11-19 02:55:59','2025-11-19 02:55:59'),(144,4,13,'OUB2BNMJEE8SMJPW',3090.00000000,1.00000000,0.00000000,3090.00000000,2,1,NULL,'2025-10-27 20:55:59','2025-10-27 20:55:59'),(145,3,11,'RJSLOYDQXNAYLF6R',1943.00000000,1.00000000,0.00000000,1943.00000000,1,0,NULL,'2025-11-24 20:55:59','2025-11-24 20:55:59'),(146,1,3,'KLUG7J2T2H2JUOXG',2469.00000000,1.00000000,0.00000000,2469.00000000,2,1,NULL,'2025-10-10 16:55:59','2025-10-10 16:55:59'),(147,4,4,'MRH0CFHZ8CJR4BSY',94.00000000,1.00000000,0.00000000,94.00000000,2,1,NULL,'2025-11-29 09:55:59','2025-11-29 09:55:59'),(148,5,8,'PH1YIZBVYJGIAWT4',1073.00000000,1.00000000,0.00000000,1073.00000000,3,0,NULL,'2025-10-07 21:55:59','2025-10-07 21:55:59'),(149,4,7,'BWRV2O8IYMLICHUZ',4876.00000000,1.00000000,0.00000000,4876.00000000,3,1,NULL,'2025-11-17 01:55:59','2025-11-17 01:55:59'),(150,3,14,'QQLVS2KFFB2DKMDN',429.00000000,1.00000000,0.00000000,429.00000000,1,0,NULL,'2025-10-15 16:55:59','2025-10-15 16:55:59'),(151,5,2,'DGGYXPENXQQXMAKP',4245.00000000,1.00000000,0.00000000,4245.00000000,1,0,NULL,'2025-11-02 04:58:02','2025-11-02 04:58:02'),(152,3,3,'BJNA9QRR7X6DEUDQ',1572.00000000,1.00000000,0.00000000,1572.00000000,1,0,NULL,'2025-12-05 08:58:02','2025-12-05 08:58:02'),(153,4,2,'HD7QNGZRWWXBO8AQ',893.00000000,1.00000000,0.00000000,893.00000000,2,0,NULL,'2025-11-12 17:58:02','2025-11-12 17:58:02'),(154,4,5,'HE1VXWBYZYQTQTMH',3140.00000000,1.00000000,0.00000000,3140.00000000,2,0,NULL,'2025-11-26 17:58:02','2025-11-26 17:58:02'),(155,2,10,'HI2O6GJJEUD98BTF',3973.00000000,1.00000000,0.00000000,3973.00000000,1,0,NULL,'2025-11-20 01:58:02','2025-11-20 01:58:02'),(156,5,13,'C2L9WXO9FKHBUWVG',4567.00000000,1.00000000,0.00000000,4567.00000000,2,1,NULL,'2025-11-17 17:58:03','2025-11-17 17:58:03'),(157,5,5,'PHBDC5ZGYHLSW8MG',1287.00000000,1.00000000,0.00000000,1287.00000000,2,0,NULL,'2025-11-23 05:58:03','2025-11-23 05:58:03'),(158,4,10,'BB5N7ACDV9AQNWMN',748.00000000,1.00000000,0.00000000,748.00000000,1,1,NULL,'2025-11-01 20:58:03','2025-11-01 20:58:03'),(159,3,3,'19KXAC27B9IBTSUA',2205.00000000,1.00000000,0.00000000,2205.00000000,3,1,NULL,'2025-10-10 01:58:03','2025-10-10 01:58:03'),(160,5,12,'JRS4AIYOAQRGG0C1',3682.00000000,1.00000000,0.00000000,3682.00000000,1,1,NULL,'2025-10-30 02:58:03','2025-10-30 02:58:03'),(161,3,6,'UWVGV05NSXVRBXIX',184.00000000,1.00000000,0.00000000,184.00000000,1,1,NULL,'2025-10-13 23:58:03','2025-10-13 23:58:03'),(162,3,9,'JEMQKLLBUOJGXHWU',4093.00000000,1.00000000,0.00000000,4093.00000000,1,1,NULL,'2025-11-08 06:58:03','2025-11-08 06:58:03'),(163,1,7,'QESDUXXQV5ZOAJMZ',1932.00000000,1.00000000,0.00000000,1932.00000000,3,1,NULL,'2025-10-14 07:58:03','2025-10-14 07:58:03'),(164,1,2,'WDPNEW5B1BXCLBME',1046.00000000,1.00000000,0.00000000,1046.00000000,2,1,NULL,'2025-10-07 05:58:03','2025-10-07 05:58:03'),(165,2,12,'WRVHFNCKIWKIUQGL',2620.00000000,1.00000000,0.00000000,2620.00000000,1,1,NULL,'2025-11-26 03:58:03','2025-11-26 03:58:03'),(166,3,5,'UITYNUPCYHSHND8H',3735.00000000,1.00000000,0.00000000,3735.00000000,3,0,NULL,'2025-10-06 02:58:03','2025-10-06 02:58:03'),(167,2,8,'LJ9YBKRTX7HHM0ZQ',1773.00000000,1.00000000,0.00000000,1773.00000000,3,0,NULL,'2025-11-04 04:58:03','2025-11-04 04:58:03'),(168,3,1,'RCDLATAW3VLIG1PW',3758.00000000,1.00000000,0.00000000,3758.00000000,2,0,NULL,'2025-10-06 17:58:03','2025-10-06 17:58:03'),(169,3,1,'BSD6Q9VPTRO3XEMJ',3832.00000000,1.00000000,0.00000000,3832.00000000,2,0,NULL,'2025-11-12 05:58:03','2025-11-12 05:58:03'),(170,1,9,'X691OTPMVHWMUVYT',2099.00000000,1.00000000,0.00000000,2099.00000000,1,0,NULL,'2025-10-25 08:58:03','2025-10-25 08:58:03'),(171,1,1,'NJUT0JDPQTBFCHXT',878.00000000,1.00000000,0.00000000,878.00000000,1,1,NULL,'2025-10-15 02:58:03','2025-10-15 02:58:03'),(172,5,7,'UKVKGJUSYB2V2ZO4',3441.00000000,1.00000000,0.00000000,3441.00000000,1,0,NULL,'2025-10-12 16:58:03','2025-10-12 16:58:03'),(173,3,9,'QBUUEQD8JEWRY4NN',1626.00000000,1.00000000,0.00000000,1626.00000000,2,1,NULL,'2025-10-05 09:58:03','2025-10-05 09:58:03'),(174,3,13,'UVF7OHYRLNIUNUHI',2515.00000000,1.00000000,0.00000000,2515.00000000,1,0,NULL,'2025-10-18 23:58:04','2025-10-18 23:58:04'),(175,2,2,'L9XL1KVXHPPZ9YQI',1705.00000000,1.00000000,0.00000000,1705.00000000,2,1,NULL,'2025-10-08 17:58:04','2025-10-08 17:58:04'),(176,4,10,'HLRE9MR4JI7LV2NY',3840.00000000,1.00000000,0.00000000,3840.00000000,2,0,NULL,'2025-10-13 17:58:08','2025-10-13 17:58:08'),(177,3,3,'RJHNMOJKFPOCLT3A',4616.00000000,1.00000000,0.00000000,4616.00000000,2,0,NULL,'2025-11-26 12:58:08','2025-11-26 12:58:08'),(178,3,6,'JUCCWQUYB7HV957P',650.00000000,1.00000000,0.00000000,650.00000000,3,1,NULL,'2025-11-15 22:58:09','2025-11-15 22:58:09'),(179,4,11,'HKECKRYPSDP8F3AE',2125.00000000,1.00000000,0.00000000,2125.00000000,1,0,NULL,'2025-10-24 20:58:09','2025-10-24 20:58:09'),(180,1,12,'M1ZKROHNOZVEEM83',1912.00000000,1.00000000,0.00000000,1912.00000000,2,0,NULL,'2025-11-12 00:58:09','2025-11-12 00:58:09'),(181,3,10,'LGHQU1KWQECMHVZ8',4871.00000000,1.00000000,0.00000000,4871.00000000,3,0,NULL,'2025-11-15 12:58:09','2025-11-15 12:58:09'),(182,2,9,'SROKZJ5EVFZIQTXQ',2744.00000000,1.00000000,0.00000000,2744.00000000,1,0,NULL,'2025-10-06 00:58:09','2025-10-06 00:58:09'),(183,4,6,'56N2M2G5LSAOGJCY',4423.00000000,1.00000000,0.00000000,4423.00000000,2,0,NULL,'2025-11-27 16:58:09','2025-11-27 16:58:09'),(184,1,12,'BAQCRIKYC32PPIW8',829.00000000,1.00000000,0.00000000,829.00000000,1,0,NULL,'2025-10-22 02:58:09','2025-10-22 02:58:09'),(185,1,11,'O9DFY5QIC0QKVGIE',3254.00000000,1.00000000,0.00000000,3254.00000000,2,0,NULL,'2025-10-29 23:58:09','2025-10-29 23:58:09'),(186,5,13,'X29NVPCPKEZGCAJU',1592.00000000,1.00000000,0.00000000,1592.00000000,3,1,NULL,'2025-12-03 06:58:09','2025-12-03 06:58:09'),(187,1,10,'Q5UJJ55HMGHHRITG',3720.00000000,1.00000000,0.00000000,3720.00000000,3,1,NULL,'2025-11-21 00:58:09','2025-11-21 00:58:09'),(188,1,14,'M6MTWH32IKUFJCUU',719.00000000,1.00000000,0.00000000,719.00000000,3,0,NULL,'2025-11-21 17:58:09','2025-11-21 17:58:09'),(189,1,5,'KIKXSOAEXREFIGQS',655.00000000,1.00000000,0.00000000,655.00000000,1,1,NULL,'2025-11-24 08:58:09','2025-11-24 08:58:09'),(190,5,9,'MIBV1LK079AMAWMC',3909.00000000,1.00000000,0.00000000,3909.00000000,1,0,NULL,'2025-10-29 00:58:09','2025-10-29 00:58:09'),(191,3,14,'UVENNIR5LLHAGO3E',3817.00000000,1.00000000,0.00000000,3817.00000000,2,1,NULL,'2025-11-18 08:58:09','2025-11-18 08:58:09'),(192,2,13,'WXTGDIKJWP6N119E',1216.00000000,1.00000000,0.00000000,1216.00000000,1,0,NULL,'2025-11-11 19:58:09','2025-11-11 19:58:09'),(193,5,13,'JRJHXIXZ78IF0Y55',1795.00000000,1.00000000,0.00000000,1795.00000000,3,1,NULL,'2025-12-03 03:58:09','2025-12-03 03:58:09'),(194,3,6,'MKPQICPKZQT6DGHG',3359.00000000,1.00000000,0.00000000,3359.00000000,3,0,NULL,'2025-11-29 22:58:09','2025-11-29 22:58:09'),(195,1,1,'JIHQEANGAOWB6YNJ',4224.00000000,1.00000000,0.00000000,4224.00000000,3,1,NULL,'2025-11-24 04:58:09','2025-11-24 04:58:09'),(196,2,14,'4ARLT8KHSZTBOSPW',1297.00000000,1.00000000,0.00000000,1297.00000000,2,0,NULL,'2025-10-24 17:58:09','2025-10-24 17:58:09'),(197,4,4,'HKH4MY3B5VBL2GYB',213.00000000,1.00000000,0.00000000,213.00000000,1,0,NULL,'2025-10-06 00:58:09','2025-10-06 00:58:09'),(198,1,6,'SQHHPYBZ6QEJNNML',74.00000000,1.00000000,0.00000000,74.00000000,3,1,NULL,'2025-10-26 15:58:09','2025-10-26 15:58:09'),(199,4,12,'IW5LBRRQEFGS2NQC',2404.00000000,1.00000000,0.00000000,2404.00000000,2,1,NULL,'2025-11-10 14:58:09','2025-11-10 14:58:09'),(200,2,4,'IQ9LVFJWR5KUSPO1',792.00000000,1.00000000,0.00000000,792.00000000,1,0,NULL,'2025-11-29 18:58:09','2025-11-29 18:58:09');
/*!40000 ALTER TABLE `sp_deposits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_execution_analytics`
--

DROP TABLE IF EXISTS `sp_execution_analytics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
  `trailing_stop_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `trailing_stop_distance` decimal(10,4) DEFAULT NULL COMMENT 'Distance in price units or pips',
  `trailing_stop_percentage` decimal(5,2) DEFAULT NULL COMMENT 'Distance as percentage',
  `highest_price` decimal(20,8) DEFAULT NULL COMMENT 'Highest price reached (for buy)',
  `lowest_price` decimal(20,8) DEFAULT NULL COMMENT 'Lowest price reached (for sell)',
  `breakeven_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `breakeven_trigger_price` decimal(20,8) DEFAULT NULL COMMENT 'Price at which to move SL to breakeven',
  `sl_moved_to_breakeven` tinyint(1) NOT NULL DEFAULT 0,
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
-- Table structure for table `sp_failed_jobs`
--

DROP TABLE IF EXISTS `sp_failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `sp_failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_failed_jobs`
--

LOCK TABLES `sp_failed_jobs` WRITE;
/*!40000 ALTER TABLE `sp_failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `sp_failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_filter_strategies`
--

DROP TABLE IF EXISTS `sp_filter_strategies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
INSERT INTO `sp_gateways` VALUES (1,'stripe',NULL,NULL,1,1,1.00000000,0.00000000,'2025-12-05 08:58:01','2025-12-05 08:58:01'),(2,'paypal',NULL,NULL,1,1,1.00000000,0.00000000,'2025-12-05 08:58:01','2025-12-05 08:58:01'),(3,'vougepay',NULL,NULL,1,1,1.00000000,0.00000000,'2025-12-05 08:58:02','2025-12-05 08:58:02'),(4,'razorpay',NULL,NULL,1,1,1.00000000,0.00000000,'2025-12-05 08:58:02','2025-12-05 08:58:02'),(5,'coinpayments',NULL,NULL,1,1,1.00000000,0.00000000,'2025-12-05 08:58:02','2025-12-05 08:58:02'),(6,'mollie',NULL,NULL,1,1,1.00000000,0.00000000,'2025-12-05 08:58:02','2025-12-05 08:58:02'),(7,'nowpayments',NULL,NULL,1,1,1.00000000,0.00000000,'2025-12-05 08:58:02','2025-12-05 08:58:02'),(8,'flutterwave',NULL,NULL,1,1,1.00000000,0.00000000,'2025-12-05 08:58:02','2025-12-05 08:58:02'),(9,'paystack',NULL,NULL,1,1,1.00000000,0.00000000,'2025-12-05 08:58:02','2025-12-05 08:58:02'),(10,'paghiper',NULL,NULL,1,1,1.00000000,0.00000000,'2025-12-05 08:58:02','2025-12-05 08:58:02'),(11,'gourl_BTC',NULL,NULL,1,1,1.00000000,0.00000000,'2025-12-05 08:58:02','2025-12-05 08:58:02'),(12,'perfectmoney',NULL,NULL,1,1,1.00000000,0.00000000,'2025-12-05 08:58:02','2025-12-05 08:58:02'),(13,'mercadopago',NULL,NULL,1,1,1.00000000,0.00000000,'2025-12-05 08:58:02','2025-12-05 08:58:02'),(14,'paytm',NULL,NULL,1,1,1.00000000,0.00000000,'2025-12-05 08:58:02','2025-12-05 08:58:02');
/*!40000 ALTER TABLE `sp_gateways` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_global_configurations`
--

DROP TABLE IF EXISTS `sp_global_configurations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_global_configurations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `config_key` varchar(255) NOT NULL,
  `config_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`config_value`)),
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sp_global_configurations_config_key_unique` (`config_key`),
  KEY `sp_global_configurations_config_key_index` (`config_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_global_configurations`
--

LOCK TABLES `sp_global_configurations` WRITE;
/*!40000 ALTER TABLE `sp_global_configurations` DISABLE KEYS */;
/*!40000 ALTER TABLE `sp_global_configurations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_jobs`
--

DROP TABLE IF EXISTS `sp_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
INSERT INTO `sp_message_parsing_patterns` VALUES (1,NULL,NULL,'Forex Auto Format','Format: buy USA100 q=0.01 tt=0.46% td=0.46%','regex','{\"required_fields\":[\"currency_pair\",\"direction\"],\"patterns\":{\"direction\":[\"\\/(?:^|\\\\s)(buy|sell)(?:\\\\s|$)\\/i\"],\"symbol\":[\"\\/(?:^|\\\\s)([A-Z0-9]{2,10})(?:\\\\s|q=)\\/i\",\"\\/(?:buy|sell)\\\\s+([A-Z0-9]{2,10})(?:\\\\s|q=)\\/i\"],\"currency_pair\":[\"\\/(?:buy|sell)\\\\s+([A-Z0-9]{2,10})(?:\\\\s|q=)\\/i\"],\"tp\":[\"\\/tt\\\\s*=\\\\s*([\\\\d.]+)\\\\s*%\\/i\",\"\\/tp\\\\s*=\\\\s*([\\\\d.]+)\\\\s*%\\/i\"],\"sl\":[\"\\/td\\\\s*=\\\\s*([\\\\d.]+)\\\\s*%\\/i\",\"\\/sl\\\\s*=\\\\s*([\\\\d.]+)\\\\s*%\\/i\"]},\"confidence_weights\":{\"currency_pair\":20,\"symbol\":20,\"direction\":20,\"tp\":20,\"sl\":20}}',80,1,0,0,'2025-12-05 08:58:13','2025-12-05 08:58:13'),(2,NULL,NULL,'Gold Multi-TP Format','Format: Gold SELL Limit, TP1/TP2/TP3/TP MAX, entry range','regex','{\"required_fields\":[\"currency_pair\",\"direction\"],\"patterns\":{\"direction\":[\"\\/(?:^|\\\\s)(Gold|XAU|GOLD)\\\\s+(BUY|SELL|LONG|SHORT)\\/i\",\"\\/(BUY|SELL|LONG|SHORT)\\\\s+(?:Limit|Market)\\/i\"],\"symbol\":[\"\\/(Gold|XAU|GOLD)\\\\s+(?:BUY|SELL|LONG|SHORT)\\/i\",\"\\/(?:^|\\\\s)(Gold|XAU|GOLD)(?:\\\\s|$)\\/i\"],\"currency_pair\":[\"\\/(Gold|XAU|GOLD)\\\\s+(?:BUY|SELL|LONG|SHORT)\\/i\"],\"open_price\":[\"\\/(?:^|\\\\n)\\\\s*(\\\\d{3,5}\\\\.?\\\\d*)\\\\s*-\\\\s*(\\\\d{3,5}\\\\.?\\\\d*)\\\\s*(?:\\\\n|$)\\/\",\"\\/(?:^|\\\\n)\\\\s*(\\\\d{3,5}\\\\.?\\\\d*)\\\\s*(?:\\\\n|$)\\/\"],\"tp\":[\"\\/TP\\\\s*MAX\\\\s*[:\\\\s]*([\\\\d.]+)\\/i\",\"\\/TP\\\\s*(\\\\d+)\\\\s*[:\\\\s]*([\\\\d.]+)\\/i\",\"\\/TP\\\\s*[:\\\\s]*([\\\\d.]+)\\/i\"],\"sl\":[\"\\/STOP\\\\s*LOSS\\\\s*[:\\\\s]*([\\\\d.]+)\\/i\",\"\\/SL\\\\s*[:\\\\s]*([\\\\d.]+)\\/i\"]},\"confidence_weights\":{\"currency_pair\":20,\"symbol\":20,\"direction\":20,\"open_price\":20,\"tp\":15,\"sl\":15}}',85,1,0,0,'2025-12-05 08:58:13','2025-12-05 08:58:13'),(3,NULL,NULL,'Standard Signal Format','Common format: PAIR DIRECTION ENTRY SL TP','regex','{\"required_fields\":[\"currency_pair\",\"direction\",\"open_price\"],\"patterns\":{\"currency_pair\":[\"\\/([A-Z]{2,10}\\\\\\/[A-Z]{2,10})\\/\",\"\\/([A-Z]{2,10}-[A-Z]{2,10})\\/\"],\"direction\":[\"\\/(BUY|SELL)\\/i\",\"\\/(LONG|SHORT)\\/i\"],\"open_price\":[\"\\/ENTRY[:\\\\s]*([\\\\d,]+\\\\.?\\\\d*)\\/i\",\"\\/PRICE[:\\\\s]*([\\\\d,]+\\\\.?\\\\d*)\\/i\"],\"sl\":[\"\\/SL[:\\\\s]*([\\\\d,]+\\\\.?\\\\d*)\\/i\",\"\\/STOP[\\\\s]*LOSS[:\\\\s]*([\\\\d,]+\\\\.?\\\\d*)\\/i\"],\"tp\":[\"\\/TP[:\\\\s]*([\\\\d,]+\\\\.?\\\\d*)\\/i\",\"\\/TAKE[\\\\s]*PROFIT[:\\\\s]*([\\\\d,]+\\\\.?\\\\d*)\\/i\"]},\"confidence_weights\":{\"currency_pair\":15,\"direction\":15,\"open_price\":20,\"sl\":15,\"tp\":15}}',50,1,0,0,'2025-12-05 08:58:13','2025-12-05 08:58:13'),(4,NULL,NULL,'Line-Based Template','Each field on separate line','template','{\"required_fields\":[\"currency_pair\",\"direction\"],\"line_mappings\":[{\"field\":\"currency_pair\",\"pattern\":\"\\/([A-Z]{2,10}\\\\\\/[A-Z]{2,10})\\/\",\"match_index\":1},{\"field\":\"direction\",\"pattern\":\"\\/(BUY|SELL)\\/i\",\"match_index\":1},{\"field\":\"open_price\",\"pattern\":\"\\/([\\\\d,]+\\\\.?\\\\d*)\\/\",\"match_index\":1},{\"field\":\"sl\",\"pattern\":\"\\/([\\\\d,]+\\\\.?\\\\d*)\\/\",\"match_index\":1},{\"field\":\"tp\",\"pattern\":\"\\/([\\\\d,]+\\\\.?\\\\d*)\\/\",\"match_index\":1}]}',40,1,0,0,'2025-12-05 08:58:13','2025-12-05 08:58:13');
/*!40000 ALTER TABLE `sp_message_parsing_patterns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_migrations`
--

DROP TABLE IF EXISTS `sp_migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=116 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_migrations`
--

LOCK TABLES `sp_migrations` WRITE;
/*!40000 ALTER TABLE `sp_migrations` DISABLE KEYS */;
INSERT INTO `sp_migrations` VALUES (1,'2019_12_14_000001_create_personal_access_tokens_table',1),(2,'2021_08_15_113006_create_crypto_payments_table',1),(3,'2023_02_22_104311_create_admins_table',1),(4,'2023_02_22_111101_create_configurations_table',1),(5,'2023_02_22_121218_create_gateways_table',1),(6,'2023_02_25_120246_create_users_table',1),(7,'2023_02_26_063704_create_admin_password_resets_table',1),(8,'2023_02_26_081605_create_deposits_table',1),(9,'2023_02_26_082931_create_withdraw_gateways_table',1),(10,'2023_02_26_084519_create_withdraws_table',1),(11,'2023_02_26_085002_create_tickets_table',1),(12,'2023_02_26_085317_create_ticket_replies_table',1),(13,'2023_02_26_085758_create_payments_table',1),(14,'2023_02_26_090322_create_user_logs_table',1),(15,'2023_02_26_091028_create_languages_table',1),(16,'2023_02_26_092247_create_notifications_table',1),(17,'2023_02_26_094347_create_permission_tables',1),(18,'2023_02_26_105957_create_pages_table',1),(19,'2023_02_26_110308_create_page_sections_table',1),(20,'2023_02_28_064341_create_contents_table',1),(21,'2023_02_28_104449_create_frontend_components_table',1),(22,'2023_03_07_113921_create_referrals_table',1),(23,'2023_03_11_064120_create_subscribers_table',1),(24,'2023_03_11_101143_create_templates_table',1),(25,'2023_03_16_054806_create_plan_subscriptions_table',1),(26,'2023_03_16_055015_create_login_securities_table',1),(27,'2023_03_16_055208_create_transactions_table',1),(28,'2023_03_16_055624_create_plans_table',1),(29,'2023_03_16_072610_create_markets_table',1),(30,'2023_03_16_080329_create_currency_pairs_table',1),(31,'2023_03_16_080524_create_time_frames_table',1),(32,'2023_03_16_080747_create_signals_table',1),(33,'2023_03_16_081326_create_plan_signals_table',1),(34,'2023_03_18_052943_create_dashboard_signals_table',1),(35,'2023_03_18_053717_create_user_signals_table',1),(36,'2023_03_20_091115_create_money_transfers_table',1),(37,'2023_03_20_095030_create_referral_commissions_table',1),(38,'2023_03_22_060754_create_jobs_table',1),(39,'2023_04_02_045912_create_frontend_media_table',1),(40,'2025_01_19_100000_add_parser_preference_to_channel_sources',1),(41,'2025_01_27_100000_create_channel_sources_table',1),(42,'2025_01_27_100001_create_channel_messages_table',1),(43,'2025_01_27_100002_add_channel_source_fields_to_signals_table',1),(44,'2025_01_28_100000_create_ai_configurations_table',1),(45,'2025_01_29_100000_create_execution_connections_table',1),(46,'2025_01_29_100000_create_trading_presets_table',1),(47,'2025_01_29_100001_add_preset_id_to_execution_connections',1),(48,'2025_01_29_100001_create_execution_logs_table',1),(49,'2025_01_29_100002_add_preset_id_to_copy_trading_subscriptions',1),(50,'2025_01_29_100002_create_execution_positions_table',1),(51,'2025_01_29_100003_add_default_preset_id_to_users',1),(52,'2025_01_29_100003_create_execution_analytics_table',1),(53,'2025_01_29_100004_add_multi_tp_to_execution_positions',1),(54,'2025_01_29_100004_create_execution_notifications_table',1),(55,'2025_01_29_100005_add_structure_sl_to_signals',1),(56,'2025_01_29_100006_add_preset_id_to_trading_bots',1),(57,'2025_01_30_100000_create_copy_trading_settings_table',1),(58,'2025_01_30_100001_create_copy_trading_subscriptions_table',1),(59,'2025_01_30_100002_create_copy_trading_executions_table',1),(60,'2025_01_30_100003_add_admin_support_to_copy_trading_settings',1),(61,'2025_11_07_000000_add_manage_addon_permission',1),(62,'2025_11_11_100000_extend_channel_sources_for_admin_ownership',1),(63,'2025_11_11_100001_create_channel_source_users_table',1),(64,'2025_11_11_100002_create_channel_source_plans_table',1),(65,'2025_11_11_100003_create_message_parsing_patterns_table',1),(66,'2025_11_11_100004_create_signal_analytics_table',1),(67,'2025_11_13_160910_make_user_id_nullable_in_channel_sources_table',1),(68,'2025_11_13_161451_change_config_column_to_text_in_channel_sources_table',1),(69,'2025_12_02_105002_create_filter_strategies_table',1),(70,'2025_12_02_105100_add_filter_strategy_to_trading_presets',1),(71,'2025_12_02_111940_create_ai_model_profiles_table',1),(72,'2025_12_02_111949_add_ai_fields_to_trading_presets',1),(73,'2025_12_02_120000_create_srm_signal_provider_metrics_table',1),(74,'2025_12_02_120001_create_srm_predictions_table',1),(75,'2025_12_02_120002_create_srm_model_versions_table',1),(76,'2025_12_02_120003_create_srm_ab_tests_table',1),(77,'2025_12_02_120004_create_srm_ab_test_assignments_table',1),(78,'2025_12_02_120005_add_srm_fields_to_execution_logs_table',1),(79,'2025_12_02_120006_add_srm_fields_to_execution_positions_table',1),(80,'2025_12_03_020013_add_backend_theme_to_configurations_table',1),(81,'2025_12_03_100000_create_ai_providers_table',1),(82,'2025_12_03_100001_create_ai_connections_table',1),(83,'2025_12_03_100002_create_ai_connection_usage_table',1),(84,'2025_12_03_100003_add_default_connection_foreign_key',1),(85,'2025_12_03_120000_create_ai_parsing_profiles_table',1),(86,'2025_12_03_120001_migrate_ai_configurations_to_connections',1),(87,'2025_12_03_130000_create_translation_settings_table',1),(88,'2025_12_03_140000_refactor_ai_model_profiles_to_use_connections',1),(89,'2025_12_04_000001_add_telegram_chat_id_to_users_and_indexes',1),(90,'2025_12_05_015138_create_bot_templates_table',1),(91,'2025_12_05_015159_create_signal_source_templates_table',1),(92,'2025_12_05_015204_create_complete_bots_table',1),(93,'2025_12_05_015209_create_template_backtests_table',1),(94,'2025_12_05_015213_create_template_ratings_table',1),(95,'2025_12_05_015218_create_template_clones_table',1),(96,'2025_12_05_015223_create_trader_profiles_table',1),(97,'2025_12_05_015228_create_trader_leaderboard_table',1),(98,'2025_12_05_015233_create_trader_ratings_table',1),(99,'2025_12_05_015237_create_market_data_subscriptions_table',1),(100,'2025_12_05_015242_add_cache_metadata_to_market_data_table',1),(101,'2025_01_30_100000_add_template_fields_to_trading_bots_table',2),(102,'2025_12_04_100015_create_trading_bots_table',2),(103,'2025_12_05_100000_add_trading_mode_to_trading_bots_table',2),(104,'2025_12_05_100001_add_bot_lifecycle_fields_to_trading_bots_table',2),(105,'2025_12_04_100013_create_backtests_table',3),(106,'2025_12_04_100014_create_backtest_results_table',3),(107,'2025_12_05_093225_add_template_fields_to_trading_bots_table',4),(108,'2025_12_05_100000_create_signal_take_profits_table',5),(109,'2025_12_05_121113_create_mt_accounts_table',5),(110,'2025_12_05_121638_add_trailing_stop_fields_to_execution_positions_table',5),(111,'2025_12_05_124411_create_failed_jobs_table',5),(112,'2025_12_05_130000_add_signal_modification_tracking',5),(113,'2025_12_05_125048_add_performance_indexes_to_tables',6),(114,'2025_12_05_153354_add_telegram_fields_to_configurations_table',7),(115,'2025_12_06_000810_create_global_configurations_table',8);
/*!40000 ALTER TABLE `sp_migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_model_has_permissions`
--

DROP TABLE IF EXISTS `sp_model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `sp_mt_accounts`
--

DROP TABLE IF EXISTS `sp_mt_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_mt_accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `admin_id` bigint(20) unsigned DEFAULT NULL,
  `execution_connection_id` bigint(20) unsigned DEFAULT NULL,
  `platform` enum('MT4','MT5') NOT NULL DEFAULT 'MT4',
  `account_number` varchar(255) NOT NULL COMMENT 'MT4/MT5 account number',
  `server` varchar(255) NOT NULL COMMENT 'MT4/MT5 server name',
  `broker_name` varchar(255) DEFAULT NULL,
  `api_key` varchar(255) NOT NULL COMMENT 'mtapi.io API key',
  `account_id` varchar(255) NOT NULL COMMENT 'mtapi.io account ID',
  `credentials` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Encrypted credentials' CHECK (json_valid(`credentials`)),
  `balance` decimal(20,2) NOT NULL DEFAULT 0.00,
  `equity` decimal(20,2) NOT NULL DEFAULT 0.00,
  `margin` decimal(20,2) NOT NULL DEFAULT 0.00,
  `free_margin` decimal(20,2) NOT NULL DEFAULT 0.00,
  `currency` varchar(10) NOT NULL DEFAULT 'USD',
  `leverage` int(11) NOT NULL DEFAULT 100,
  `status` enum('active','inactive','error') NOT NULL DEFAULT 'inactive',
  `last_synced_at` timestamp NULL DEFAULT NULL,
  `last_error` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sp_mt_accounts_account_number_server_platform_unique` (`account_number`,`server`,`platform`),
  KEY `sp_mt_accounts_user_id_platform_status_index` (`user_id`,`platform`,`status`),
  KEY `sp_mt_accounts_admin_id_platform_status_index` (`admin_id`,`platform`,`status`),
  KEY `sp_mt_accounts_execution_connection_id_index` (`execution_connection_id`),
  CONSTRAINT `sp_mt_accounts_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `sp_admins` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sp_mt_accounts_execution_connection_id_foreign` FOREIGN KEY (`execution_connection_id`) REFERENCES `sp_execution_connections` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sp_mt_accounts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `sp_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_mt_accounts`
--

LOCK TABLES `sp_mt_accounts` WRITE;
/*!40000 ALTER TABLE `sp_mt_accounts` DISABLE KEYS */;
/*!40000 ALTER TABLE `sp_mt_accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_notifications`
--

DROP TABLE IF EXISTS `sp_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=92 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_notifications`
--

LOCK TABLES `sp_notifications` WRITE;
/*!40000 ALTER TABLE `sp_notifications` DISABLE KEYS */;
INSERT INTO `sp_notifications` VALUES (1,'App\\Notifications\\PaymentApprovedNotification','App\\Models\\User','2','{\"message\":\"Your payment has been approved!\",\"type\":\"payment_approved\",\"action_url\":\"\\/dashboard\"}','2025-12-05 15:48:39','2025-11-25 20:48:39','2025-11-25 20:48:39');
/*!40000 ALTER TABLE `sp_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_page_sections`
--

DROP TABLE IF EXISTS `sp_page_sections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_page_sections` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `page_id` bigint(20) unsigned NOT NULL,
  `sections` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=154 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_page_sections`
--

LOCK TABLES `sp_page_sections` WRITE;
/*!40000 ALTER TABLE `sp_page_sections` DISABLE KEYS */;
INSERT INTO `sp_page_sections` VALUES (1,1,'\"banner\"','2025-12-04 23:35:56','2025-12-04 23:35:56'),(2,1,'\"about\"','2025-12-04 23:35:56','2025-12-04 23:35:56'),(3,1,'\"benefits\"','2025-12-04 23:35:56','2025-12-04 23:35:56'),(4,1,'\"how_works\"','2025-12-04 23:35:56','2025-12-04 23:35:56'),(5,1,'\"plans\"','2025-12-04 23:35:56','2025-12-04 23:35:56'),(6,1,'\"trade\"','2025-12-04 23:35:56','2025-12-04 23:35:56'),(7,1,'\"referral\"','2025-12-04 23:35:56','2025-12-04 23:35:56'),(8,1,'\"team\"','2025-12-04 23:35:56','2025-12-04 23:35:56'),(9,1,'\"testimonial\"','2025-12-04 23:35:56','2025-12-04 23:35:56'),(10,1,'\"blog\"','2025-12-04 23:35:56','2025-12-04 23:35:56'),(11,2,'\"about\"','2025-12-04 23:35:56','2025-12-04 23:35:56'),(12,2,'\"overview\"','2025-12-04 23:35:56','2025-12-04 23:35:56'),(13,2,'\"how_works\"','2025-12-04 23:35:56','2025-12-04 23:35:56'),(14,2,'\"team\"','2025-12-04 23:35:56','2025-12-04 23:35:56'),(15,3,'\"plans\"','2025-12-04 23:35:56','2025-12-04 23:35:56'),(16,4,'\"contact\"','2025-12-04 23:35:56','2025-12-04 23:35:56'),(17,5,'\"blog\"','2025-12-04 23:35:56','2025-12-04 23:35:56'),(18,1,'\"banner\"','2025-12-05 08:41:35','2025-12-05 08:41:35'),(19,1,'\"about\"','2025-12-05 08:41:35','2025-12-05 08:41:35'),(20,1,'\"benefits\"','2025-12-05 08:41:35','2025-12-05 08:41:35'),(21,1,'\"how_works\"','2025-12-05 08:41:35','2025-12-05 08:41:35'),(22,1,'\"plans\"','2025-12-05 08:41:35','2025-12-05 08:41:35'),(23,1,'\"trade\"','2025-12-05 08:41:35','2025-12-05 08:41:35'),(24,1,'\"referral\"','2025-12-05 08:41:35','2025-12-05 08:41:35'),(25,1,'\"team\"','2025-12-05 08:41:35','2025-12-05 08:41:35'),(26,1,'\"testimonial\"','2025-12-05 08:41:36','2025-12-05 08:41:36'),(27,1,'\"blog\"','2025-12-05 08:41:36','2025-12-05 08:41:36'),(28,2,'\"about\"','2025-12-05 08:41:36','2025-12-05 08:41:36'),(29,2,'\"overview\"','2025-12-05 08:41:36','2025-12-05 08:41:36'),(30,2,'\"how_works\"','2025-12-05 08:41:36','2025-12-05 08:41:36'),(31,2,'\"team\"','2025-12-05 08:41:36','2025-12-05 08:41:36'),(32,3,'\"plans\"','2025-12-05 08:41:36','2025-12-05 08:41:36'),(33,4,'\"contact\"','2025-12-05 08:41:36','2025-12-05 08:41:36'),(34,5,'\"blog\"','2025-12-05 08:41:36','2025-12-05 08:41:36'),(35,1,'\"banner\"','2025-12-05 08:41:40','2025-12-05 08:41:40'),(36,1,'\"about\"','2025-12-05 08:41:40','2025-12-05 08:41:40'),(37,1,'\"benefits\"','2025-12-05 08:41:40','2025-12-05 08:41:40'),(38,1,'\"how_works\"','2025-12-05 08:41:41','2025-12-05 08:41:41'),(39,1,'\"plans\"','2025-12-05 08:41:41','2025-12-05 08:41:41'),(40,1,'\"trade\"','2025-12-05 08:41:41','2025-12-05 08:41:41'),(41,1,'\"referral\"','2025-12-05 08:41:41','2025-12-05 08:41:41'),(42,1,'\"team\"','2025-12-05 08:41:41','2025-12-05 08:41:41'),(43,1,'\"testimonial\"','2025-12-05 08:41:41','2025-12-05 08:41:41'),(44,1,'\"blog\"','2025-12-05 08:41:41','2025-12-05 08:41:41'),(45,2,'\"about\"','2025-12-05 08:41:41','2025-12-05 08:41:41'),(46,2,'\"overview\"','2025-12-05 08:41:42','2025-12-05 08:41:42'),(47,2,'\"how_works\"','2025-12-05 08:41:42','2025-12-05 08:41:42'),(48,2,'\"team\"','2025-12-05 08:41:42','2025-12-05 08:41:42'),(49,3,'\"plans\"','2025-12-05 08:41:42','2025-12-05 08:41:42'),(50,4,'\"contact\"','2025-12-05 08:41:42','2025-12-05 08:41:42'),(51,5,'\"blog\"','2025-12-05 08:41:42','2025-12-05 08:41:42'),(52,1,'\"banner\"','2025-12-05 08:45:27','2025-12-05 08:45:27'),(53,1,'\"about\"','2025-12-05 08:45:27','2025-12-05 08:45:27'),(54,1,'\"benefits\"','2025-12-05 08:45:27','2025-12-05 08:45:27'),(55,1,'\"how_works\"','2025-12-05 08:45:28','2025-12-05 08:45:28'),(56,1,'\"plans\"','2025-12-05 08:45:28','2025-12-05 08:45:28'),(57,1,'\"trade\"','2025-12-05 08:45:28','2025-12-05 08:45:28'),(58,1,'\"referral\"','2025-12-05 08:45:28','2025-12-05 08:45:28'),(59,1,'\"team\"','2025-12-05 08:45:28','2025-12-05 08:45:28'),(60,1,'\"testimonial\"','2025-12-05 08:45:28','2025-12-05 08:45:28'),(61,1,'\"blog\"','2025-12-05 08:45:28','2025-12-05 08:45:28'),(62,2,'\"about\"','2025-12-05 08:45:28','2025-12-05 08:45:28'),(63,2,'\"overview\"','2025-12-05 08:45:29','2025-12-05 08:45:29'),(64,2,'\"how_works\"','2025-12-05 08:45:29','2025-12-05 08:45:29'),(65,2,'\"team\"','2025-12-05 08:45:29','2025-12-05 08:45:29'),(66,3,'\"plans\"','2025-12-05 08:45:29','2025-12-05 08:45:29'),(67,4,'\"contact\"','2025-12-05 08:45:29','2025-12-05 08:45:29'),(68,5,'\"blog\"','2025-12-05 08:45:29','2025-12-05 08:45:29'),(69,1,'\"banner\"','2025-12-05 08:47:13','2025-12-05 08:47:13'),(70,1,'\"about\"','2025-12-05 08:47:13','2025-12-05 08:47:13'),(71,1,'\"benefits\"','2025-12-05 08:47:13','2025-12-05 08:47:13'),(72,1,'\"how_works\"','2025-12-05 08:47:13','2025-12-05 08:47:13'),(73,1,'\"plans\"','2025-12-05 08:47:13','2025-12-05 08:47:13'),(74,1,'\"trade\"','2025-12-05 08:47:13','2025-12-05 08:47:13'),(75,1,'\"referral\"','2025-12-05 08:47:13','2025-12-05 08:47:13'),(76,1,'\"team\"','2025-12-05 08:47:13','2025-12-05 08:47:13'),(77,1,'\"testimonial\"','2025-12-05 08:47:13','2025-12-05 08:47:13'),(78,1,'\"blog\"','2025-12-05 08:47:13','2025-12-05 08:47:13'),(79,2,'\"about\"','2025-12-05 08:47:13','2025-12-05 08:47:13'),(80,2,'\"overview\"','2025-12-05 08:47:13','2025-12-05 08:47:13'),(81,2,'\"how_works\"','2025-12-05 08:47:13','2025-12-05 08:47:13'),(82,2,'\"team\"','2025-12-05 08:47:13','2025-12-05 08:47:13'),(83,3,'\"plans\"','2025-12-05 08:47:13','2025-12-05 08:47:13'),(84,4,'\"contact\"','2025-12-05 08:47:13','2025-12-05 08:47:13'),(85,5,'\"blog\"','2025-12-05 08:47:13','2025-12-05 08:47:13'),(86,1,'\"banner\"','2025-12-05 08:48:37','2025-12-05 08:48:37'),(87,1,'\"about\"','2025-12-05 08:48:37','2025-12-05 08:48:37'),(88,1,'\"benefits\"','2025-12-05 08:48:37','2025-12-05 08:48:37'),(89,1,'\"how_works\"','2025-12-05 08:48:37','2025-12-05 08:48:37'),(90,1,'\"plans\"','2025-12-05 08:48:38','2025-12-05 08:48:38'),(91,1,'\"trade\"','2025-12-05 08:48:38','2025-12-05 08:48:38'),(92,1,'\"referral\"','2025-12-05 08:48:38','2025-12-05 08:48:38'),(93,1,'\"team\"','2025-12-05 08:48:38','2025-12-05 08:48:38'),(94,1,'\"testimonial\"','2025-12-05 08:48:38','2025-12-05 08:48:38'),(95,1,'\"blog\"','2025-12-05 08:48:38','2025-12-05 08:48:38'),(96,2,'\"about\"','2025-12-05 08:48:38','2025-12-05 08:48:38'),(97,2,'\"overview\"','2025-12-05 08:48:38','2025-12-05 08:48:38'),(98,2,'\"how_works\"','2025-12-05 08:48:38','2025-12-05 08:48:38'),(99,2,'\"team\"','2025-12-05 08:48:38','2025-12-05 08:48:38'),(100,3,'\"plans\"','2025-12-05 08:48:38','2025-12-05 08:48:38'),(101,4,'\"contact\"','2025-12-05 08:48:38','2025-12-05 08:48:38'),(102,5,'\"blog\"','2025-12-05 08:48:38','2025-12-05 08:48:38'),(103,1,'\"banner\"','2025-12-05 08:55:58','2025-12-05 08:55:58'),(104,1,'\"about\"','2025-12-05 08:55:58','2025-12-05 08:55:58'),(105,1,'\"benefits\"','2025-12-05 08:55:58','2025-12-05 08:55:58'),(106,1,'\"how_works\"','2025-12-05 08:55:58','2025-12-05 08:55:58'),(107,1,'\"plans\"','2025-12-05 08:55:58','2025-12-05 08:55:58'),(108,1,'\"trade\"','2025-12-05 08:55:58','2025-12-05 08:55:58'),(109,1,'\"referral\"','2025-12-05 08:55:58','2025-12-05 08:55:58'),(110,1,'\"team\"','2025-12-05 08:55:58','2025-12-05 08:55:58'),(111,1,'\"testimonial\"','2025-12-05 08:55:58','2025-12-05 08:55:58'),(112,1,'\"blog\"','2025-12-05 08:55:58','2025-12-05 08:55:58'),(113,2,'\"about\"','2025-12-05 08:55:58','2025-12-05 08:55:58'),(114,2,'\"overview\"','2025-12-05 08:55:58','2025-12-05 08:55:58'),(115,2,'\"how_works\"','2025-12-05 08:55:58','2025-12-05 08:55:58'),(116,2,'\"team\"','2025-12-05 08:55:58','2025-12-05 08:55:58'),(117,3,'\"plans\"','2025-12-05 08:55:58','2025-12-05 08:55:58'),(118,4,'\"contact\"','2025-12-05 08:55:58','2025-12-05 08:55:58'),(119,5,'\"blog\"','2025-12-05 08:55:58','2025-12-05 08:55:58'),(120,1,'\"banner\"','2025-12-05 08:57:58','2025-12-05 08:57:58'),(121,1,'\"about\"','2025-12-05 08:57:58','2025-12-05 08:57:58'),(122,1,'\"benefits\"','2025-12-05 08:57:58','2025-12-05 08:57:58'),(123,1,'\"how_works\"','2025-12-05 08:57:58','2025-12-05 08:57:58'),(124,1,'\"plans\"','2025-12-05 08:57:58','2025-12-05 08:57:58'),(125,1,'\"trade\"','2025-12-05 08:57:58','2025-12-05 08:57:58'),(126,1,'\"referral\"','2025-12-05 08:57:58','2025-12-05 08:57:58'),(127,1,'\"team\"','2025-12-05 08:57:58','2025-12-05 08:57:58'),(128,1,'\"testimonial\"','2025-12-05 08:57:58','2025-12-05 08:57:58'),(129,1,'\"blog\"','2025-12-05 08:57:58','2025-12-05 08:57:58'),(130,2,'\"about\"','2025-12-05 08:57:59','2025-12-05 08:57:59'),(131,2,'\"overview\"','2025-12-05 08:57:59','2025-12-05 08:57:59'),(132,2,'\"how_works\"','2025-12-05 08:57:59','2025-12-05 08:57:59'),(133,2,'\"team\"','2025-12-05 08:57:59','2025-12-05 08:57:59'),(134,3,'\"plans\"','2025-12-05 08:57:59','2025-12-05 08:57:59'),(135,4,'\"contact\"','2025-12-05 08:57:59','2025-12-05 08:57:59'),(136,5,'\"blog\"','2025-12-05 08:57:59','2025-12-05 08:57:59'),(137,1,'\"banner\"','2025-12-05 08:58:03','2025-12-05 08:58:03'),(138,1,'\"about\"','2025-12-05 08:58:03','2025-12-05 08:58:03'),(139,1,'\"benefits\"','2025-12-05 08:58:03','2025-12-05 08:58:03'),(140,1,'\"how_works\"','2025-12-05 08:58:03','2025-12-05 08:58:03'),(141,1,'\"plans\"','2025-12-05 08:58:03','2025-12-05 08:58:03'),(142,1,'\"trade\"','2025-12-05 08:58:03','2025-12-05 08:58:03'),(143,1,'\"referral\"','2025-12-05 08:58:03','2025-12-05 08:58:03'),(144,1,'\"team\"','2025-12-05 08:58:03','2025-12-05 08:58:03'),(145,1,'\"testimonial\"','2025-12-05 08:58:03','2025-12-05 08:58:03'),(146,1,'\"blog\"','2025-12-05 08:58:03','2025-12-05 08:58:03'),(147,2,'\"about\"','2025-12-05 08:58:03','2025-12-05 08:58:03'),(148,2,'\"overview\"','2025-12-05 08:58:04','2025-12-05 08:58:04'),(149,2,'\"how_works\"','2025-12-05 08:58:04','2025-12-05 08:58:04'),(150,2,'\"team\"','2025-12-05 08:58:04','2025-12-05 08:58:04'),(151,3,'\"plans\"','2025-12-05 08:58:04','2025-12-05 08:58:04'),(152,4,'\"contact\"','2025-12-05 08:58:04','2025-12-05 08:58:04'),(153,5,'\"blog\"','2025-12-05 08:58:04','2025-12-05 08:58:04');
/*!40000 ALTER TABLE `sp_page_sections` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_pages`
--

DROP TABLE IF EXISTS `sp_pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
  KEY `sp_payments_user_id_status_index` (`user_id`,`status`),
  KEY `payments_user_id_status_index` (`user_id`,`status`)
) ENGINE=InnoDB AUTO_INCREMENT=241 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_payments`
--

LOCK TABLES `sp_payments` WRITE;
/*!40000 ALTER TABLE `sp_payments` DISABLE KEYS */;
INSERT INTO `sp_payments` VALUES (1,9,4,2,'Y0IREYWCEWH7LWIP',499.99000000,1.00000000,0.00000000,499.99000000,0,0,NULL,NULL,NULL,'2025-10-17 10:41:51','2025-10-17 10:41:51'),(2,9,1,8,'CLJ2YAZP6VKGGAU8',499.99000000,1.00000000,0.00000000,499.99000000,1,1,NULL,NULL,'2026-11-28 18:41:51','2025-11-28 18:41:51','2025-11-28 18:41:51'),(3,10,1,3,'060HOC3VPLR5CSH7',999.99000000,1.00000000,0.00000000,999.99000000,2,0,NULL,NULL,NULL,'2025-10-24 20:41:51','2025-10-24 20:41:51'),(4,8,3,1,'LCEXV8Y2G5FZI9N9',49.99000000,1.00000000,0.00000000,49.99000000,2,1,NULL,NULL,NULL,'2025-10-31 21:41:51','2025-10-31 21:41:51'),(5,6,2,13,'L7M8EAXFASKF2HTJ',0.00000000,1.00000000,0.00000000,0.00000000,1,1,NULL,NULL,'2025-11-30 16:41:52','2025-11-23 16:41:52','2025-11-23 16:41:52'),(6,8,2,7,'CEOOP4ZWMZWOUAK6',49.99000000,1.00000000,0.00000000,49.99000000,2,0,NULL,NULL,NULL,'2025-09-06 01:41:52','2025-09-06 01:41:52'),(7,10,3,10,'R2NLYPMIIBCUQTYZ',999.99000000,1.00000000,0.00000000,999.99000000,1,0,NULL,NULL,'2025-09-29 01:41:52','2025-09-29 01:41:52','2025-09-29 01:41:52'),(8,10,4,14,'WGJT9GTLUVT4T8DX',999.99000000,1.00000000,0.00000000,999.99000000,0,1,NULL,NULL,NULL,'2025-11-16 16:41:52','2025-11-16 16:41:52'),(9,8,2,13,'AWIXIUXHFFLRVKSW',49.99000000,1.00000000,0.00000000,49.99000000,0,1,NULL,NULL,NULL,'2025-10-06 04:41:53','2025-10-06 04:41:53'),(10,6,3,12,'13FNYI4N9NIWU4SA',0.00000000,1.00000000,0.00000000,0.00000000,2,1,NULL,NULL,NULL,'2025-10-23 10:41:53','2025-10-23 10:41:53'),(11,9,5,3,'HIZXZPGOP9IGMUBX',499.99000000,1.00000000,0.00000000,499.99000000,1,1,NULL,NULL,'2026-11-19 14:41:53','2025-11-19 14:41:53','2025-11-19 14:41:53'),(12,10,1,1,'YNPW5ZHIWAS7WVWH',999.99000000,1.00000000,0.00000000,999.99000000,1,0,NULL,NULL,'2025-11-30 12:41:53','2025-11-30 12:41:53','2025-11-30 12:41:53'),(13,6,2,14,'GQKOZFC6QVRFWAJ7',0.00000000,1.00000000,0.00000000,0.00000000,1,0,NULL,NULL,'2025-11-06 03:41:53','2025-10-30 03:41:53','2025-10-30 03:41:53'),(14,10,4,1,'9B8FIZIOBHOKFWRU',999.99000000,1.00000000,0.00000000,999.99000000,1,0,NULL,NULL,'2025-10-21 15:41:53','2025-10-21 15:41:53','2025-10-21 15:41:53'),(15,8,3,8,'KL7FJAN5A5UFIATX',49.99000000,1.00000000,0.00000000,49.99000000,1,0,NULL,NULL,'2025-10-15 09:41:53','2025-09-15 09:41:53','2025-09-15 09:41:53'),(16,9,2,1,'MBQQWMGBUVLT9YWG',499.99000000,1.00000000,0.00000000,499.99000000,0,1,NULL,NULL,NULL,'2025-12-02 23:41:53','2025-12-02 23:41:53'),(17,9,4,6,'GIP5WMAI1JHKUUQ4',499.99000000,1.00000000,0.00000000,499.99000000,0,1,NULL,NULL,NULL,'2025-10-29 14:41:54','2025-10-29 14:41:54'),(18,7,4,10,'DOACP0VCYMNKLCF0',29.99000000,1.00000000,0.00000000,29.99000000,1,0,NULL,NULL,'2025-12-26 16:41:54','2025-11-26 16:41:54','2025-11-26 16:41:54'),(19,6,4,9,'VBGC7CSVQGPKEXXG',0.00000000,1.00000000,0.00000000,0.00000000,2,0,NULL,NULL,NULL,'2025-11-13 22:41:54','2025-11-13 22:41:54'),(20,8,3,5,'6ZSZ6TD5DXRQLFXR',49.99000000,1.00000000,0.00000000,49.99000000,1,0,NULL,NULL,'2025-12-31 07:41:54','2025-12-01 07:41:54','2025-12-01 07:41:54'),(21,10,2,11,'L8ZSPBW4T1QUEM0I',999.99000000,1.00000000,0.00000000,999.99000000,0,1,NULL,NULL,NULL,'2025-09-15 02:41:54','2025-09-15 02:41:54'),(22,6,5,3,'YCKLNTYIE37SZFWY',0.00000000,1.00000000,0.00000000,0.00000000,2,0,NULL,NULL,NULL,'2025-10-04 03:41:54','2025-10-04 03:41:54'),(23,6,2,12,'DFF2S4RKP0Y9LDHM',0.00000000,1.00000000,0.00000000,0.00000000,1,0,NULL,NULL,'2025-12-11 00:41:54','2025-12-04 00:41:54','2025-12-04 00:41:54'),(24,9,5,7,'DUGQMEZNQTW04IYS',499.99000000,1.00000000,0.00000000,499.99000000,1,0,NULL,NULL,'2026-09-10 01:41:54','2025-09-10 01:41:54','2025-09-10 01:41:54'),(25,6,5,1,'HSQSZKHM1TLB8WVY',0.00000000,1.00000000,0.00000000,0.00000000,2,1,NULL,NULL,NULL,'2025-11-03 07:41:54','2025-11-03 07:41:54'),(26,9,2,6,'PEQLEJHZ94FZ2H5V',499.99000000,1.00000000,0.00000000,499.99000000,2,0,NULL,NULL,NULL,'2025-10-16 20:41:54','2025-10-16 20:41:54'),(27,7,4,9,'VCPA9EUTSJ8HJLB2',29.99000000,1.00000000,0.00000000,29.99000000,0,0,NULL,NULL,NULL,'2025-09-16 15:41:54','2025-09-16 15:41:54'),(28,9,3,10,'KRSFAEWNZS6QCQCD',499.99000000,1.00000000,0.00000000,499.99000000,1,0,NULL,NULL,'2026-11-18 16:41:55','2025-11-18 16:41:55','2025-11-18 16:41:55'),(29,7,2,7,'S7DZV45YN36AMJJR',29.99000000,1.00000000,0.00000000,29.99000000,0,1,NULL,NULL,NULL,'2025-09-24 00:41:55','2025-09-24 00:41:55'),(30,10,2,1,'NV3NOCLHS7ARPSHP',999.99000000,1.00000000,0.00000000,999.99000000,0,0,NULL,NULL,NULL,'2025-11-24 05:41:55','2025-11-24 05:41:55'),(31,9,2,11,'GFWXEBYQO3XZONEU',499.99000000,1.00000000,0.00000000,499.99000000,0,1,NULL,NULL,NULL,'2025-10-16 06:41:55','2025-10-16 06:41:55'),(32,9,1,6,'YVDJYNAYZWPJTB96',499.99000000,1.00000000,0.00000000,499.99000000,1,1,NULL,NULL,'2026-12-04 21:41:55','2025-12-04 21:41:55','2025-12-04 21:41:55'),(33,6,2,7,'UXWQXS5FIYQDXHFQ',0.00000000,1.00000000,0.00000000,0.00000000,1,0,NULL,NULL,'2025-10-13 05:41:55','2025-10-06 05:41:55','2025-10-06 05:41:55'),(34,8,3,4,'HQACHCCRLJNVFFKT',49.99000000,1.00000000,0.00000000,49.99000000,2,1,NULL,NULL,NULL,'2025-11-16 23:41:55','2025-11-16 23:41:55'),(35,10,2,5,'EZSGWZRDF0MCHPI7',999.99000000,1.00000000,0.00000000,999.99000000,1,0,NULL,NULL,'2025-10-20 13:41:55','2025-10-20 13:41:55','2025-10-20 13:41:55'),(36,7,2,1,'TQZSY5ID2LAF3RK8',29.99000000,1.00000000,0.00000000,29.99000000,1,1,NULL,NULL,'2025-12-08 14:41:55','2025-11-08 14:41:55','2025-11-08 14:41:55'),(37,10,4,11,'RH3DICZ1MRHL98QP',999.99000000,1.00000000,0.00000000,999.99000000,1,0,NULL,NULL,'2025-10-03 04:41:55','2025-10-03 04:41:55','2025-10-03 04:41:55'),(38,7,5,3,'FBPH4OQ1CX9FEGFO',29.99000000,1.00000000,0.00000000,29.99000000,0,0,NULL,NULL,NULL,'2025-10-17 19:41:56','2025-10-17 19:41:56'),(39,6,1,9,'U4SFY7XPKKHLQ9CI',0.00000000,1.00000000,0.00000000,0.00000000,0,1,NULL,NULL,NULL,'2025-11-19 04:41:56','2025-11-19 04:41:56'),(40,10,3,14,'K0KNBYDALHD61H4J',999.99000000,1.00000000,0.00000000,999.99000000,1,0,NULL,NULL,'2025-10-18 11:41:56','2025-10-18 11:41:56','2025-10-18 11:41:56'),(41,9,4,14,'5WXHGUL3NQ9V0NJX',499.99000000,1.00000000,0.00000000,499.99000000,1,1,NULL,NULL,'2026-11-26 14:41:56','2025-11-26 14:41:56','2025-11-26 14:41:56'),(42,9,4,5,'FVOGXROTNODR0WYW',499.99000000,1.00000000,0.00000000,499.99000000,1,1,NULL,NULL,'2026-11-28 23:41:56','2025-11-28 23:41:56','2025-11-28 23:41:56'),(43,8,1,10,'I0SWSHU5XTAJTFW3',49.99000000,1.00000000,0.00000000,49.99000000,1,0,NULL,NULL,'2025-10-07 18:41:56','2025-09-07 18:41:56','2025-09-07 18:41:56'),(44,9,1,9,'WEF0EWQDO7W326QJ',499.99000000,1.00000000,0.00000000,499.99000000,0,1,NULL,NULL,NULL,'2025-11-27 10:41:56','2025-11-27 10:41:56'),(45,6,4,12,'7R0U3ANQABBPPFF4',0.00000000,1.00000000,0.00000000,0.00000000,0,0,NULL,NULL,NULL,'2025-11-19 13:41:56','2025-11-19 13:41:56'),(46,8,4,14,'SCUWFWOBHOWP9U6J',49.99000000,1.00000000,0.00000000,49.99000000,1,1,NULL,NULL,'2025-11-13 11:41:56','2025-10-14 11:41:56','2025-10-14 11:41:56'),(47,7,1,6,'QRVZGEMS9M7PG53D',29.99000000,1.00000000,0.00000000,29.99000000,0,0,NULL,NULL,NULL,'2025-11-25 18:41:56','2025-11-25 18:41:56'),(48,10,2,4,'MYQ4QFXTSBG1VURN',999.99000000,1.00000000,0.00000000,999.99000000,0,1,NULL,NULL,NULL,'2025-09-11 21:41:56','2025-09-11 21:41:56'),(49,7,5,8,'1Y3NZLL8EUP13I9T',29.99000000,1.00000000,0.00000000,29.99000000,2,0,NULL,NULL,NULL,'2025-11-28 03:41:57','2025-11-28 03:41:57'),(50,6,2,4,'F4ACQFBYMUQOP7RI',0.00000000,1.00000000,0.00000000,0.00000000,0,1,NULL,NULL,NULL,'2025-09-09 11:41:57','2025-09-09 11:41:57'),(51,8,2,10,'ECWYAHNPCVOEGT4W',49.99000000,1.00000000,0.00000000,49.99000000,2,1,NULL,NULL,NULL,'2025-10-20 14:41:57','2025-10-20 14:41:57'),(52,8,3,1,'ZYUJFMRKBE4RK2MH',49.99000000,1.00000000,0.00000000,49.99000000,0,0,NULL,NULL,NULL,'2025-10-17 12:41:57','2025-10-17 12:41:57'),(53,10,2,3,'SRINXGBB0RQE4LW5',999.99000000,1.00000000,0.00000000,999.99000000,0,1,NULL,NULL,NULL,'2025-11-17 06:41:57','2025-11-17 06:41:57'),(54,10,5,11,'6M8CJNL1NZBHPT0M',999.99000000,1.00000000,0.00000000,999.99000000,1,1,NULL,NULL,'2025-10-27 20:41:57','2025-10-27 20:41:57','2025-10-27 20:41:57'),(55,7,1,7,'UFJ0ZSZVJZRXALL5',29.99000000,1.00000000,0.00000000,29.99000000,0,1,NULL,NULL,NULL,'2025-11-06 10:41:57','2025-11-06 10:41:57'),(56,8,3,9,'K9AC9QLDAQYFAJMY',49.99000000,1.00000000,0.00000000,49.99000000,0,0,NULL,NULL,NULL,'2025-09-24 02:41:57','2025-09-24 02:41:57'),(57,10,5,13,'EXHYXFLKFILWM7NW',999.99000000,1.00000000,0.00000000,999.99000000,0,1,NULL,NULL,NULL,'2025-10-15 04:41:57','2025-10-15 04:41:57'),(58,7,1,9,'KWA66FDPVGU2XRYM',29.99000000,1.00000000,0.00000000,29.99000000,0,0,NULL,NULL,NULL,'2025-10-30 08:41:58','2025-10-30 08:41:58'),(59,7,5,13,'Y2ZNVQRZ92SLAGBK',29.99000000,1.00000000,0.00000000,29.99000000,2,0,NULL,NULL,NULL,'2025-09-16 18:41:58','2025-09-16 18:41:58'),(60,7,5,12,'JDUAKSXNJHUTRWYX',29.99000000,1.00000000,0.00000000,29.99000000,1,0,NULL,NULL,'2025-11-01 09:41:58','2025-10-02 09:41:58','2025-10-02 09:41:58'),(61,9,2,11,'ZPQUDC3YONBJMJVN',499.99000000,1.00000000,0.00000000,499.99000000,1,0,NULL,NULL,'2026-11-09 12:45:36','2025-11-09 12:45:36','2025-11-09 12:45:36'),(62,8,3,11,'DVSBSJCX3UNYMVG6',49.99000000,1.00000000,0.00000000,49.99000000,0,1,NULL,NULL,NULL,'2025-09-05 16:45:37','2025-09-05 16:45:37'),(63,9,5,1,'NTYBAJV5QSCHZZ2Z',499.99000000,1.00000000,0.00000000,499.99000000,1,1,NULL,NULL,'2026-12-05 06:45:37','2025-12-05 06:45:37','2025-12-05 06:45:37'),(64,8,2,10,'BC3UOQWJIO8XXUGE',49.99000000,1.00000000,0.00000000,49.99000000,1,0,NULL,NULL,'2025-10-23 11:45:37','2025-09-23 11:45:37','2025-09-23 11:45:37'),(65,8,2,3,'KIWGYJBRREVG3J4G',49.99000000,1.00000000,0.00000000,49.99000000,0,0,NULL,NULL,NULL,'2025-09-20 06:45:37','2025-09-20 06:45:37'),(66,6,4,2,'KP6E4ZBPYY2MQNPW',0.00000000,1.00000000,0.00000000,0.00000000,0,0,NULL,NULL,NULL,'2025-09-11 06:45:37','2025-09-11 06:45:37'),(67,10,4,6,'5RJRFINHFPUAPYVS',999.99000000,1.00000000,0.00000000,999.99000000,2,0,NULL,NULL,NULL,'2025-10-27 14:45:37','2025-10-27 14:45:37'),(68,7,5,13,'VOUXTI7A0I6DXIEP',29.99000000,1.00000000,0.00000000,29.99000000,2,1,NULL,NULL,NULL,'2025-10-18 17:45:38','2025-10-18 17:45:38'),(69,8,5,2,'MAOLKFAUWXPUITNM',49.99000000,1.00000000,0.00000000,49.99000000,1,1,NULL,NULL,'2025-12-30 14:45:38','2025-11-30 14:45:38','2025-11-30 14:45:38'),(70,10,2,7,'O59A2K6ZKI7SNNIX',999.99000000,1.00000000,0.00000000,999.99000000,0,1,NULL,NULL,NULL,'2025-09-17 12:45:38','2025-09-17 12:45:38'),(71,7,5,8,'RUNRNSAJGMUBWVO7',29.99000000,1.00000000,0.00000000,29.99000000,2,1,NULL,NULL,NULL,'2025-09-23 04:45:38','2025-09-23 04:45:38'),(72,9,5,6,'LTXBH9JAHBIJOJHU',499.99000000,1.00000000,0.00000000,499.99000000,2,0,NULL,NULL,NULL,'2025-10-28 07:45:38','2025-10-28 07:45:38'),(73,9,3,4,'X4ANYSZIKHXSPXKU',499.99000000,1.00000000,0.00000000,499.99000000,2,0,NULL,NULL,NULL,'2025-10-29 21:45:38','2025-10-29 21:45:38'),(74,10,2,2,'0ZFXRSIHVYBWVG9P',999.99000000,1.00000000,0.00000000,999.99000000,1,0,NULL,NULL,'2025-11-28 11:45:39','2025-11-28 11:45:39','2025-11-28 11:45:39'),(75,8,4,2,'XXY9QBD8VNZWKH28',49.99000000,1.00000000,0.00000000,49.99000000,0,1,NULL,NULL,NULL,'2025-10-24 09:45:39','2025-10-24 09:45:39'),(76,8,4,6,'TYL3LZQ0CNWGEG64',49.99000000,1.00000000,0.00000000,49.99000000,1,1,NULL,NULL,'2025-10-08 02:45:39','2025-09-08 02:45:39','2025-09-08 02:45:39'),(77,10,3,11,'IL1MBYAJL8Y3MRXG',999.99000000,1.00000000,0.00000000,999.99000000,0,0,NULL,NULL,NULL,'2025-11-21 10:45:39','2025-11-21 10:45:39'),(78,6,3,14,'FJPMHJ55XT0XDXRP',0.00000000,1.00000000,0.00000000,0.00000000,0,1,NULL,NULL,NULL,'2025-11-21 02:45:39','2025-11-21 02:45:39'),(79,6,2,10,'4CUH2CJUQWI00MA9',0.00000000,1.00000000,0.00000000,0.00000000,1,1,NULL,NULL,'2025-11-03 01:45:39','2025-10-27 01:45:39','2025-10-27 01:45:39'),(80,10,3,4,'DULSV7OA6ZTTWJGN',999.99000000,1.00000000,0.00000000,999.99000000,0,0,NULL,NULL,NULL,'2025-09-18 09:45:39','2025-09-18 09:45:39'),(81,8,3,4,'IJUT8FRWCJCPQDYM',49.99000000,1.00000000,0.00000000,49.99000000,1,0,NULL,NULL,'2025-10-25 14:45:39','2025-09-25 14:45:39','2025-09-25 14:45:39'),(82,6,4,3,'MNTQMVIYFPQVXX0U',0.00000000,1.00000000,0.00000000,0.00000000,2,0,NULL,NULL,NULL,'2025-11-19 17:45:39','2025-11-19 17:45:39'),(83,10,3,1,'F8WPEAMUJADFUYWD',999.99000000,1.00000000,0.00000000,999.99000000,0,0,NULL,NULL,NULL,'2025-11-15 16:45:40','2025-11-15 16:45:40'),(84,7,5,1,'QGMI9HQDE6D81XMQ',29.99000000,1.00000000,0.00000000,29.99000000,1,1,NULL,NULL,'2025-12-09 02:45:40','2025-11-09 02:45:40','2025-11-09 02:45:40'),(85,6,5,1,'GULKBJNXVCDPD6OU',0.00000000,1.00000000,0.00000000,0.00000000,0,0,NULL,NULL,NULL,'2025-09-22 23:45:40','2025-09-22 23:45:40'),(86,6,4,9,'TV6BRGNCDHMYTMVY',0.00000000,1.00000000,0.00000000,0.00000000,0,1,NULL,NULL,NULL,'2025-10-01 21:45:40','2025-10-01 21:45:40'),(87,6,2,12,'9IOTFX8QRGDZZLD5',0.00000000,1.00000000,0.00000000,0.00000000,2,0,NULL,NULL,NULL,'2025-11-17 20:45:40','2025-11-17 20:45:40'),(88,8,4,11,'IWDECUVNXQP03V5N',49.99000000,1.00000000,0.00000000,49.99000000,2,1,NULL,NULL,NULL,'2025-10-04 19:45:40','2025-10-04 19:45:40'),(89,7,2,13,'BHKNBFH9PY1OHZVW',29.99000000,1.00000000,0.00000000,29.99000000,1,0,NULL,NULL,'2026-01-03 13:45:41','2025-12-04 13:45:41','2025-12-04 13:45:41'),(90,10,4,4,'HRFBCDAFBRDZT606',999.99000000,1.00000000,0.00000000,999.99000000,2,0,NULL,NULL,NULL,'2025-09-24 20:45:41','2025-09-24 20:45:41'),(91,9,5,8,'BOZJ9SY9V7GALTUX',499.99000000,1.00000000,0.00000000,499.99000000,0,1,NULL,NULL,NULL,'2025-10-31 16:47:14','2025-10-31 16:47:14'),(92,10,5,6,'FK3GWU4BJMOBTM6R',999.99000000,1.00000000,0.00000000,999.99000000,2,1,NULL,NULL,NULL,'2025-11-15 05:47:14','2025-11-15 05:47:14'),(93,6,5,14,'TJYVUK5X09OUTOBI',0.00000000,1.00000000,0.00000000,0.00000000,2,0,NULL,NULL,NULL,'2025-11-21 20:47:14','2025-11-21 20:47:14'),(94,9,4,7,'BPVHOGFQVB4VIQZV',499.99000000,1.00000000,0.00000000,499.99000000,1,0,NULL,NULL,'2026-10-17 17:47:14','2025-10-17 17:47:14','2025-10-17 17:47:14'),(95,10,1,10,'Q0KSJ85IDDMRTEN6',999.99000000,1.00000000,0.00000000,999.99000000,1,0,NULL,NULL,'2025-11-20 15:47:14','2025-11-20 15:47:14','2025-11-20 15:47:14'),(96,10,2,4,'PCSCG8SZEJO9IZEL',999.99000000,1.00000000,0.00000000,999.99000000,1,0,NULL,NULL,'2025-09-24 08:47:14','2025-09-24 08:47:14','2025-09-24 08:47:14'),(97,8,2,7,'TKMTQBEGTMXLG6LO',49.99000000,1.00000000,0.00000000,49.99000000,1,0,NULL,NULL,'2025-12-12 09:47:14','2025-11-12 09:47:14','2025-11-12 09:47:14'),(98,7,2,6,'DHI40AHHXIJSHODP',29.99000000,1.00000000,0.00000000,29.99000000,1,0,NULL,NULL,'2025-10-22 02:47:14','2025-09-22 02:47:14','2025-09-22 02:47:14'),(99,9,1,3,'JEWFNKRR8JCVJIF9',499.99000000,1.00000000,0.00000000,499.99000000,2,1,NULL,NULL,NULL,'2025-09-08 01:47:14','2025-09-08 01:47:14'),(100,6,5,14,'RZBPBLYJTHAPBXQK',0.00000000,1.00000000,0.00000000,0.00000000,2,0,NULL,NULL,NULL,'2025-10-29 04:47:14','2025-10-29 04:47:14'),(101,8,2,10,'OQMZKEIAD8VMQU1V',49.99000000,1.00000000,0.00000000,49.99000000,1,0,NULL,NULL,'2025-10-21 08:47:14','2025-09-21 08:47:14','2025-09-21 08:47:14'),(102,8,2,8,'VT9JB0AFTFIAJ5AM',49.99000000,1.00000000,0.00000000,49.99000000,1,0,NULL,NULL,'2025-12-04 09:47:14','2025-11-04 09:47:14','2025-11-04 09:47:14'),(103,9,4,13,'8CESLQZTUIPGSSPJ',499.99000000,1.00000000,0.00000000,499.99000000,0,0,NULL,NULL,NULL,'2025-11-02 13:47:14','2025-11-02 13:47:14'),(104,10,1,8,'RVYOMJET6IWYPHBJ',999.99000000,1.00000000,0.00000000,999.99000000,2,1,NULL,NULL,NULL,'2025-11-27 11:47:14','2025-11-27 11:47:14'),(105,7,4,2,'CA546HG5T9VA7LI1',29.99000000,1.00000000,0.00000000,29.99000000,1,1,NULL,NULL,'2025-12-20 16:47:14','2025-11-20 16:47:14','2025-11-20 16:47:14'),(106,9,1,12,'X7WEUEU4UMRITE9G',499.99000000,1.00000000,0.00000000,499.99000000,1,0,NULL,NULL,'2026-11-22 12:47:14','2025-11-22 12:47:14','2025-11-22 12:47:14'),(107,6,5,7,'XBHSIVLM5X3WXMSL',0.00000000,1.00000000,0.00000000,0.00000000,0,0,NULL,NULL,NULL,'2025-09-23 17:47:14','2025-09-23 17:47:14'),(108,9,4,2,'PKSYMVMAQ90TJY9V',499.99000000,1.00000000,0.00000000,499.99000000,0,0,NULL,NULL,NULL,'2025-11-20 23:47:14','2025-11-20 23:47:14'),(109,6,4,5,'2L7ZTMOF2C63V8EY',0.00000000,1.00000000,0.00000000,0.00000000,2,1,NULL,NULL,NULL,'2025-10-12 22:47:14','2025-10-12 22:47:14'),(110,9,1,5,'EUVXIIW1MZJXKUZV',499.99000000,1.00000000,0.00000000,499.99000000,0,1,NULL,NULL,NULL,'2025-09-18 03:47:14','2025-09-18 03:47:14'),(111,10,3,1,'65ESDCNDQAJQ4DFP',999.99000000,1.00000000,0.00000000,999.99000000,1,1,NULL,NULL,'2025-12-03 07:47:14','2025-12-03 07:47:14','2025-12-03 07:47:14'),(112,9,3,14,'J577BS1HUZVGZJKX',499.99000000,1.00000000,0.00000000,499.99000000,2,0,NULL,NULL,NULL,'2025-10-03 03:47:14','2025-10-03 03:47:14'),(113,10,5,3,'4NC31ROAB3SHWVJP',999.99000000,1.00000000,0.00000000,999.99000000,0,0,NULL,NULL,NULL,'2025-10-15 06:47:14','2025-10-15 06:47:14'),(114,8,4,8,'NDONT2LREJ9SUCLN',49.99000000,1.00000000,0.00000000,49.99000000,1,1,NULL,NULL,'2025-10-12 15:47:14','2025-09-12 15:47:14','2025-09-12 15:47:14'),(115,7,3,4,'3FR1WJVDGXFT0PGM',29.99000000,1.00000000,0.00000000,29.99000000,0,0,NULL,NULL,NULL,'2025-09-09 00:47:14','2025-09-09 00:47:14'),(116,6,2,7,'3OL07PVU1F9GV9HR',0.00000000,1.00000000,0.00000000,0.00000000,2,0,NULL,NULL,NULL,'2025-10-10 16:47:14','2025-10-10 16:47:14'),(117,6,5,8,'58BQZIQL9QMZW6B2',0.00000000,1.00000000,0.00000000,0.00000000,1,1,NULL,NULL,'2025-12-09 18:47:14','2025-12-02 18:47:14','2025-12-02 18:47:14'),(118,10,3,2,'OBKHLDJ4QAPH0ZR6',999.99000000,1.00000000,0.00000000,999.99000000,0,1,NULL,NULL,NULL,'2025-11-06 13:47:14','2025-11-06 13:47:14'),(119,7,1,4,'IDMQ8T42BOIJIMCT',29.99000000,1.00000000,0.00000000,29.99000000,2,1,NULL,NULL,NULL,'2025-10-13 11:47:14','2025-10-13 11:47:14'),(120,10,2,9,'JUXJQZPW5KFVCER8',999.99000000,1.00000000,0.00000000,999.99000000,2,1,NULL,NULL,NULL,'2025-09-27 22:47:14','2025-09-27 22:47:14'),(121,10,2,7,'2VJ6JZRNFIUEUNPR',999.99000000,1.00000000,0.00000000,999.99000000,2,1,NULL,NULL,NULL,'2025-11-06 05:48:38','2025-11-06 05:48:38'),(122,9,1,2,'RX3PR4O0JSQM8VPE',499.99000000,1.00000000,0.00000000,499.99000000,2,1,NULL,NULL,NULL,'2025-11-10 04:48:38','2025-11-10 04:48:38'),(123,10,2,5,'AGF4ZHHZC79R1FIK',999.99000000,1.00000000,0.00000000,999.99000000,1,0,NULL,NULL,'2025-10-24 17:48:38','2025-10-24 17:48:38','2025-10-24 17:48:38'),(124,9,5,14,'WKUBPHH01VT20KSO',499.99000000,1.00000000,0.00000000,499.99000000,0,0,NULL,NULL,NULL,'2025-10-21 18:48:38','2025-10-21 18:48:38'),(125,8,5,4,'FEYSCVIESPXFXG0N',49.99000000,1.00000000,0.00000000,49.99000000,2,1,NULL,NULL,NULL,'2025-11-01 14:48:38','2025-11-01 14:48:38'),(126,7,1,4,'6X5EM3VIEMFE3RGR',29.99000000,1.00000000,0.00000000,29.99000000,0,1,NULL,NULL,NULL,'2025-09-15 10:48:38','2025-09-15 10:48:38'),(127,8,1,13,'RX56FHWGAMWZY3PU',49.99000000,1.00000000,0.00000000,49.99000000,2,0,NULL,NULL,NULL,'2025-10-11 01:48:38','2025-10-11 01:48:38'),(128,8,5,5,'XKDYJ25XBIXWHUPB',49.99000000,1.00000000,0.00000000,49.99000000,1,0,NULL,NULL,'2025-10-07 05:48:38','2025-09-07 05:48:38','2025-09-07 05:48:38'),(129,10,2,7,'YTW9Z29BIYQUQ14M',999.99000000,1.00000000,0.00000000,999.99000000,2,0,NULL,NULL,NULL,'2025-12-03 04:48:38','2025-12-03 04:48:38'),(130,6,3,11,'EFUSMOGGP1FVKHAT',0.00000000,1.00000000,0.00000000,0.00000000,0,1,NULL,NULL,NULL,'2025-10-14 14:48:38','2025-10-14 14:48:38'),(131,10,1,13,'TP4GC2HTRZGCQJBG',999.99000000,1.00000000,0.00000000,999.99000000,0,1,NULL,NULL,NULL,'2025-10-10 02:48:38','2025-10-10 02:48:38'),(132,7,4,1,'K0397PXOCEEL7EVY',29.99000000,1.00000000,0.00000000,29.99000000,2,1,NULL,NULL,NULL,'2025-11-13 02:48:38','2025-11-13 02:48:38'),(133,6,2,1,'9MUDL8YAXUQYVIDG',0.00000000,1.00000000,0.00000000,0.00000000,2,1,NULL,NULL,NULL,'2025-11-15 15:48:38','2025-11-15 15:48:38'),(134,9,4,11,'AUO1RKG6HN1TPQJ4',499.99000000,1.00000000,0.00000000,499.99000000,1,1,NULL,NULL,'2026-09-14 21:48:38','2025-09-14 21:48:38','2025-09-14 21:48:38'),(135,6,2,3,'E1KRGVLOY8AW4VKA',0.00000000,1.00000000,0.00000000,0.00000000,2,1,NULL,NULL,NULL,'2025-10-06 13:48:38','2025-10-06 13:48:38'),(136,6,4,4,'M8ASUB9DWW2JBCE2',0.00000000,1.00000000,0.00000000,0.00000000,1,1,NULL,NULL,'2025-09-28 08:48:38','2025-09-21 08:48:38','2025-09-21 08:48:38'),(137,6,5,3,'ZPYXFF5KK11GCQMI',0.00000000,1.00000000,0.00000000,0.00000000,2,1,NULL,NULL,NULL,'2025-10-13 14:48:38','2025-10-13 14:48:38'),(138,9,4,12,'KWIFQZ99UOAONEEE',499.99000000,1.00000000,0.00000000,499.99000000,2,1,NULL,NULL,NULL,'2025-11-06 20:48:38','2025-11-06 20:48:38'),(139,6,2,4,'7GTCAQNRXPQGKKOF',0.00000000,1.00000000,0.00000000,0.00000000,2,0,NULL,NULL,NULL,'2025-11-23 22:48:38','2025-11-23 22:48:38'),(140,8,3,6,'TBJV18ISQRVEDF1N',49.99000000,1.00000000,0.00000000,49.99000000,1,1,NULL,NULL,'2025-10-30 22:48:38','2025-09-30 22:48:38','2025-09-30 22:48:38'),(141,10,5,2,'MDAOF1XPXI7CKSSI',999.99000000,1.00000000,0.00000000,999.99000000,1,1,NULL,NULL,'2025-09-22 20:48:38','2025-09-22 20:48:38','2025-09-22 20:48:38'),(142,6,4,9,'KMGBZWBMFDRZKTS0',0.00000000,1.00000000,0.00000000,0.00000000,0,1,NULL,NULL,NULL,'2025-11-03 08:48:38','2025-11-03 08:48:38'),(143,10,3,7,'7LY4WPW0Q2YAHSHA',999.99000000,1.00000000,0.00000000,999.99000000,1,0,NULL,NULL,'2025-11-26 14:48:38','2025-11-26 14:48:38','2025-11-26 14:48:38'),(144,8,5,13,'EVQMJ5CPKEUWADLJ',49.99000000,1.00000000,0.00000000,49.99000000,0,1,NULL,NULL,NULL,'2025-09-28 09:48:38','2025-09-28 09:48:38'),(145,6,2,5,'UTOTGG22VKCS4MWV',0.00000000,1.00000000,0.00000000,0.00000000,1,0,NULL,NULL,'2025-11-02 13:48:38','2025-10-26 13:48:38','2025-10-26 13:48:38'),(146,9,2,1,'DPNNEMJKVW5UXP4T',499.99000000,1.00000000,0.00000000,499.99000000,1,1,NULL,NULL,'2026-12-02 17:48:38','2025-12-02 17:48:38','2025-12-02 17:48:38'),(147,8,3,9,'BGZZW4IULCBWAATS',49.99000000,1.00000000,0.00000000,49.99000000,1,0,NULL,NULL,'2025-11-20 16:48:38','2025-10-21 16:48:38','2025-10-21 16:48:38'),(148,7,2,3,'FZKG2IPIGNC3Z0BZ',29.99000000,1.00000000,0.00000000,29.99000000,0,0,NULL,NULL,NULL,'2025-11-23 01:48:38','2025-11-23 01:48:38'),(149,10,2,14,'EMCL28YDPGTGQVU5',999.99000000,1.00000000,0.00000000,999.99000000,2,0,NULL,NULL,NULL,'2025-09-22 18:48:38','2025-09-22 18:48:38'),(150,8,5,1,'58OMQUGBJ4JBFSYA',49.99000000,1.00000000,0.00000000,49.99000000,0,0,NULL,NULL,NULL,'2025-10-07 16:48:38','2025-10-07 16:48:38'),(151,10,2,3,'WN2EGDO5ZGRGKJW6',999.99000000,1.00000000,0.00000000,999.99000000,0,1,NULL,NULL,NULL,'2025-11-30 16:55:58','2025-11-30 16:55:58'),(152,9,2,14,'KK6TTWOUX38TEQAQ',499.99000000,1.00000000,0.00000000,499.99000000,0,0,NULL,NULL,NULL,'2025-09-25 09:55:58','2025-09-25 09:55:58'),(153,6,1,7,'Y3R7VSLG3CPANDOO',0.00000000,1.00000000,0.00000000,0.00000000,2,0,NULL,NULL,NULL,'2025-09-06 19:55:58','2025-09-06 19:55:58'),(154,9,1,9,'PAKREZAPWHBKPUQ7',499.99000000,1.00000000,0.00000000,499.99000000,2,1,NULL,NULL,NULL,'2025-11-30 03:55:58','2025-11-30 03:55:58'),(155,8,1,4,'ZQS78JHWPO92NFSC',49.99000000,1.00000000,0.00000000,49.99000000,2,1,NULL,NULL,NULL,'2025-11-30 15:55:58','2025-11-30 15:55:58'),(156,6,2,2,'9LWTWTKJIM97AZKZ',0.00000000,1.00000000,0.00000000,0.00000000,0,0,NULL,NULL,NULL,'2025-11-18 02:55:58','2025-11-18 02:55:58'),(157,7,1,5,'OCAJKRGUI7PWALRP',29.99000000,1.00000000,0.00000000,29.99000000,1,0,NULL,NULL,'2025-12-03 02:55:58','2025-11-03 02:55:58','2025-11-03 02:55:58'),(158,7,3,3,'KXWTWC8W234P6XO6',29.99000000,1.00000000,0.00000000,29.99000000,1,0,NULL,NULL,'2025-11-15 02:55:58','2025-10-16 02:55:58','2025-10-16 02:55:58'),(159,8,4,5,'JJ47BVDB8ZK0JCPL',49.99000000,1.00000000,0.00000000,49.99000000,2,1,NULL,NULL,NULL,'2025-10-09 08:55:58','2025-10-09 08:55:58'),(160,7,5,3,'ROHDAPK5VRF5KWJT',29.99000000,1.00000000,0.00000000,29.99000000,2,1,NULL,NULL,NULL,'2025-10-10 14:55:58','2025-10-10 14:55:58'),(161,7,1,3,'ESFNIUECA36MDJ9K',29.99000000,1.00000000,0.00000000,29.99000000,0,0,NULL,NULL,NULL,'2025-09-20 02:55:58','2025-09-20 02:55:58'),(162,6,4,6,'HQD73LJSPRXLJ08F',0.00000000,1.00000000,0.00000000,0.00000000,2,0,NULL,NULL,NULL,'2025-09-15 11:55:58','2025-09-15 11:55:58'),(163,9,3,1,'REJXBVZ1HVHGAQIK',499.99000000,1.00000000,0.00000000,499.99000000,1,0,NULL,NULL,'2026-10-09 09:55:58','2025-10-09 09:55:58','2025-10-09 09:55:58'),(164,7,3,5,'LJ5YKLG5KEAGPO5U',29.99000000,1.00000000,0.00000000,29.99000000,0,1,NULL,NULL,NULL,'2025-10-03 20:55:58','2025-10-03 20:55:58'),(165,6,3,11,'PBYIQTR4TCX1XUAI',0.00000000,1.00000000,0.00000000,0.00000000,1,0,NULL,NULL,'2025-11-26 13:55:58','2025-11-19 13:55:58','2025-11-19 13:55:58'),(166,9,2,3,'JB4LDMYYXEUVKD0T',499.99000000,1.00000000,0.00000000,499.99000000,1,0,NULL,NULL,'2026-12-02 17:55:58','2025-12-02 17:55:58','2025-12-02 17:55:58'),(167,10,3,6,'8ANSISBDPK6AXAMX',999.99000000,1.00000000,0.00000000,999.99000000,0,1,NULL,NULL,NULL,'2025-12-01 02:55:58','2025-12-01 02:55:58'),(168,8,4,12,'83BDNKPNR9JIJVYF',49.99000000,1.00000000,0.00000000,49.99000000,1,0,NULL,NULL,'2025-11-16 05:55:58','2025-10-17 05:55:58','2025-10-17 05:55:58'),(169,7,3,14,'EZDZ5VMWP6BZTKOW',29.99000000,1.00000000,0.00000000,29.99000000,2,0,NULL,NULL,NULL,'2025-12-02 05:55:58','2025-12-02 05:55:58'),(170,9,3,4,'UZKKGHXUHYAVDTVF',499.99000000,1.00000000,0.00000000,499.99000000,0,1,NULL,NULL,NULL,'2025-11-07 02:55:58','2025-11-07 02:55:58'),(171,8,1,5,'SB9YPEDFXJYZLLEU',49.99000000,1.00000000,0.00000000,49.99000000,0,0,NULL,NULL,NULL,'2025-11-15 14:55:58','2025-11-15 14:55:58'),(172,7,1,9,'SIAS4FW9KYOOPTSO',29.99000000,1.00000000,0.00000000,29.99000000,0,1,NULL,NULL,NULL,'2025-10-27 11:55:58','2025-10-27 11:55:58'),(173,8,5,6,'RSWCPLMU2EKW9WET',49.99000000,1.00000000,0.00000000,49.99000000,0,1,NULL,NULL,NULL,'2025-11-22 14:55:58','2025-11-22 14:55:58'),(174,9,5,1,'QAAOM1RETHQ66DJD',499.99000000,1.00000000,0.00000000,499.99000000,2,0,NULL,NULL,NULL,'2025-10-28 21:55:58','2025-10-28 21:55:58'),(175,10,2,6,'ZA46OACSDHYWFIAC',999.99000000,1.00000000,0.00000000,999.99000000,1,1,NULL,NULL,'2025-09-29 03:55:58','2025-09-29 03:55:58','2025-09-29 03:55:58'),(176,7,5,1,'J3TZDORLLA1UYNNP',29.99000000,1.00000000,0.00000000,29.99000000,2,0,NULL,NULL,NULL,'2025-09-27 15:55:58','2025-09-27 15:55:58'),(177,7,2,8,'M2EMMO0TXMC23CIC',29.99000000,1.00000000,0.00000000,29.99000000,1,0,NULL,NULL,'2025-11-23 11:55:58','2025-10-24 11:55:58','2025-10-24 11:55:58'),(178,10,3,1,'KS4Z2GJWWYUKZZAP',999.99000000,1.00000000,0.00000000,999.99000000,0,0,NULL,NULL,NULL,'2025-11-28 12:55:58','2025-11-28 12:55:58'),(179,6,3,8,'THKNSTLKWRNYMWFI',0.00000000,1.00000000,0.00000000,0.00000000,2,0,NULL,NULL,NULL,'2025-11-17 11:55:58','2025-11-17 11:55:58'),(180,6,4,2,'EXOOFQSNQSZVV5IX',0.00000000,1.00000000,0.00000000,0.00000000,0,0,NULL,NULL,NULL,'2025-11-02 05:55:58','2025-11-02 05:55:58'),(181,9,4,4,'RHA67JLVFN6BYYIP',499.99000000,1.00000000,0.00000000,499.99000000,0,1,NULL,NULL,NULL,'2025-09-15 00:58:01','2025-09-15 00:58:01'),(182,6,5,4,'AD3BRYKL71DMYXSM',0.00000000,1.00000000,0.00000000,0.00000000,1,1,NULL,NULL,'2025-12-12 04:58:01','2025-12-05 04:58:01','2025-12-05 04:58:01'),(183,8,5,6,'61PJHGXTCVANYZSK',49.99000000,1.00000000,0.00000000,49.99000000,0,1,NULL,NULL,NULL,'2025-12-02 04:58:01','2025-12-02 04:58:01'),(184,10,5,13,'UVEJMJOS3BIBKIQF',999.99000000,1.00000000,0.00000000,999.99000000,1,1,NULL,NULL,'2025-11-15 02:58:01','2025-11-15 02:58:01','2025-11-15 02:58:01'),(185,6,1,4,'SZWFHRJXN8ONSCTT',0.00000000,1.00000000,0.00000000,0.00000000,0,1,NULL,NULL,NULL,'2025-10-21 01:58:01','2025-10-21 01:58:01'),(186,9,4,4,'C2TXFL9JSXRHNZHQ',499.99000000,1.00000000,0.00000000,499.99000000,0,0,NULL,NULL,NULL,'2025-11-28 00:58:01','2025-11-28 00:58:01'),(187,7,5,3,'NNFHLYJHCTJB6YRQ',29.99000000,1.00000000,0.00000000,29.99000000,0,0,NULL,NULL,NULL,'2025-10-13 04:58:01','2025-10-13 04:58:01'),(188,7,2,1,'1IMTMKMQBOJW0QUB',29.99000000,1.00000000,0.00000000,29.99000000,0,0,NULL,NULL,NULL,'2025-12-03 11:58:01','2025-12-03 11:58:01'),(189,9,5,14,'9CHGVJ6SUOLYF17K',499.99000000,1.00000000,0.00000000,499.99000000,0,1,NULL,NULL,NULL,'2025-09-07 15:58:01','2025-09-07 15:58:01'),(190,6,2,1,'JWTKVDHLBCDEPXTC',0.00000000,1.00000000,0.00000000,0.00000000,1,0,NULL,NULL,'2025-10-02 13:58:01','2025-09-25 13:58:01','2025-09-25 13:58:01'),(191,8,2,1,'N43HIIVHKDAOH3RD',49.99000000,1.00000000,0.00000000,49.99000000,0,1,NULL,NULL,NULL,'2025-10-17 01:58:01','2025-10-17 01:58:01'),(192,9,5,10,'TGWE6WR6YCJYQKA6',499.99000000,1.00000000,0.00000000,499.99000000,2,1,NULL,NULL,NULL,'2025-12-03 17:58:01','2025-12-03 17:58:01'),(193,6,3,7,'OOPFE7WAXKPAPULB',0.00000000,1.00000000,0.00000000,0.00000000,0,1,NULL,NULL,NULL,'2025-09-23 00:58:01','2025-09-23 00:58:01'),(194,9,4,12,'XKNJUUZPF038DHEF',499.99000000,1.00000000,0.00000000,499.99000000,2,1,NULL,NULL,NULL,'2025-10-08 08:58:01','2025-10-08 08:58:01'),(195,10,1,14,'ONLKMKVPJJX9I8IY',999.99000000,1.00000000,0.00000000,999.99000000,0,1,NULL,NULL,NULL,'2025-10-25 08:58:01','2025-10-25 08:58:01'),(196,6,4,2,'XWMC24TSS0TBQSXN',0.00000000,1.00000000,0.00000000,0.00000000,2,0,NULL,NULL,NULL,'2025-11-12 04:58:01','2025-11-12 04:58:01'),(197,9,2,1,'3NJGLBSZRS0DTLTG',499.99000000,1.00000000,0.00000000,499.99000000,1,1,NULL,NULL,'2026-10-14 02:58:01','2025-10-14 02:58:01','2025-10-14 02:58:01'),(198,7,5,12,'LCZQYPRBOD6XFHDA',29.99000000,1.00000000,0.00000000,29.99000000,2,1,NULL,NULL,NULL,'2025-11-12 18:58:01','2025-11-12 18:58:01'),(199,9,3,12,'JJD2DSFMEHNWVEUH',499.99000000,1.00000000,0.00000000,499.99000000,2,1,NULL,NULL,NULL,'2025-10-14 04:58:02','2025-10-14 04:58:02'),(200,6,2,10,'B8EAU33PYO63RBC1',0.00000000,1.00000000,0.00000000,0.00000000,1,1,NULL,NULL,'2025-10-26 09:58:02','2025-10-19 09:58:02','2025-10-19 09:58:02'),(201,6,1,9,'PHVHK2B6PTGWOC4T',0.00000000,1.00000000,0.00000000,0.00000000,1,0,NULL,NULL,'2025-11-15 22:58:02','2025-11-08 22:58:02','2025-11-08 22:58:02'),(202,8,4,1,'7OR5SDUJO0RV3LJL',49.99000000,1.00000000,0.00000000,49.99000000,0,1,NULL,NULL,NULL,'2025-10-20 17:58:02','2025-10-20 17:58:02'),(203,10,4,10,'DBHAMPXBQHVGNT8G',999.99000000,1.00000000,0.00000000,999.99000000,0,0,NULL,NULL,NULL,'2025-11-14 19:58:02','2025-11-14 19:58:02'),(204,8,1,2,'DZQZ8BOWTNKBDVIL',49.99000000,1.00000000,0.00000000,49.99000000,0,0,NULL,NULL,NULL,'2025-09-19 02:58:02','2025-09-19 02:58:02'),(205,10,3,7,'LAFEPBNKDR5JTQFR',999.99000000,1.00000000,0.00000000,999.99000000,0,1,NULL,NULL,NULL,'2025-11-26 11:58:02','2025-11-26 11:58:02'),(206,7,3,11,'NJSQHOPE5POWZAPF',29.99000000,1.00000000,0.00000000,29.99000000,2,1,NULL,NULL,NULL,'2025-11-27 06:58:02','2025-11-27 06:58:02'),(207,7,4,13,'FSP40ENYMFWR3OTM',29.99000000,1.00000000,0.00000000,29.99000000,0,1,NULL,NULL,NULL,'2025-11-07 22:58:02','2025-11-07 22:58:02'),(208,9,5,4,'USG6WF5HCFWMJCQS',499.99000000,1.00000000,0.00000000,499.99000000,0,1,NULL,NULL,NULL,'2025-10-15 00:58:02','2025-10-15 00:58:02'),(209,6,5,6,'ZQYJVCNQS9LWQZRI',0.00000000,1.00000000,0.00000000,0.00000000,1,1,NULL,NULL,'2025-10-07 23:58:02','2025-09-30 23:58:02','2025-09-30 23:58:02'),(210,9,4,6,'KCYIEALP9QNFTBEQ',499.99000000,1.00000000,0.00000000,499.99000000,0,1,NULL,NULL,NULL,'2025-11-05 15:58:02','2025-11-05 15:58:02'),(211,6,1,13,'MLTI5PGNMILCU8YZ',0.00000000,1.00000000,0.00000000,0.00000000,0,1,NULL,NULL,NULL,'2025-11-30 15:58:06','2025-11-30 15:58:06'),(212,10,1,5,'JLTV58IVG6DTVKYP',999.99000000,1.00000000,0.00000000,999.99000000,0,1,NULL,NULL,NULL,'2025-12-04 11:58:07','2025-12-04 11:58:07'),(213,8,3,13,'HGCTY1NG81WYMQYT',49.99000000,1.00000000,0.00000000,49.99000000,2,0,NULL,NULL,NULL,'2025-09-10 14:58:07','2025-09-10 14:58:07'),(214,8,1,5,'YQIKOVTVMGLT05E4',49.99000000,1.00000000,0.00000000,49.99000000,1,0,NULL,NULL,'2025-10-19 10:58:07','2025-09-19 10:58:07','2025-09-19 10:58:07'),(215,6,2,10,'CE9VJIYEPZZPFBN4',0.00000000,1.00000000,0.00000000,0.00000000,0,1,NULL,NULL,NULL,'2025-11-11 15:58:07','2025-11-11 15:58:07'),(216,7,5,1,'U76H0N0DB14FV4OA',29.99000000,1.00000000,0.00000000,29.99000000,2,0,NULL,NULL,NULL,'2025-10-13 16:58:07','2025-10-13 16:58:07'),(217,10,3,10,'EBUSXQ4V0PE50FSK',999.99000000,1.00000000,0.00000000,999.99000000,0,0,NULL,NULL,NULL,'2025-09-08 04:58:07','2025-09-08 04:58:07'),(218,7,4,1,'4L0Z33IXAJHI6R1H',29.99000000,1.00000000,0.00000000,29.99000000,2,1,NULL,NULL,NULL,'2025-10-19 20:58:07','2025-10-19 20:58:07'),(219,10,1,9,'MEVW4IIYRUZBBX3S',999.99000000,1.00000000,0.00000000,999.99000000,1,0,NULL,NULL,'2025-10-11 14:58:07','2025-10-11 14:58:07','2025-10-11 14:58:07'),(220,8,5,6,'ESXHKRKFZ3GCXBE1',49.99000000,1.00000000,0.00000000,49.99000000,2,1,NULL,NULL,NULL,'2025-10-01 11:58:07','2025-10-01 11:58:07'),(221,8,1,2,'HCBVDCXMG6UM0NIE',49.99000000,1.00000000,0.00000000,49.99000000,1,0,NULL,NULL,'2025-10-26 20:58:07','2025-09-26 20:58:07','2025-09-26 20:58:07'),(222,6,3,14,'XPW0I3NWWJP4KVZP',0.00000000,1.00000000,0.00000000,0.00000000,1,0,NULL,NULL,'2025-10-05 01:58:07','2025-09-28 01:58:07','2025-09-28 01:58:07'),(223,6,1,10,'JVJLT8QUIBITVZV3',0.00000000,1.00000000,0.00000000,0.00000000,1,1,NULL,NULL,'2025-09-25 18:58:07','2025-09-18 18:58:07','2025-09-18 18:58:07'),(224,10,4,2,'LL9YGR6GOEUOV8D7',999.99000000,1.00000000,0.00000000,999.99000000,2,0,NULL,NULL,NULL,'2025-12-01 13:58:07','2025-12-01 13:58:07'),(225,9,1,11,'GB4WDL66RJ1TYDC4',499.99000000,1.00000000,0.00000000,499.99000000,1,1,NULL,NULL,'2026-10-05 01:58:07','2025-10-05 01:58:07','2025-10-05 01:58:07'),(226,6,1,12,'ADHPH69QAGZXM3BM',0.00000000,1.00000000,0.00000000,0.00000000,1,0,NULL,NULL,'2025-11-01 18:58:07','2025-10-25 18:58:07','2025-10-25 18:58:07'),(227,10,4,12,'FTHN54QBDZDMHW1A',999.99000000,1.00000000,0.00000000,999.99000000,0,0,NULL,NULL,NULL,'2025-12-02 18:58:07','2025-12-02 18:58:07'),(228,8,2,3,'WUTI2A6NVTOFJUMK',49.99000000,1.00000000,0.00000000,49.99000000,0,1,NULL,NULL,NULL,'2025-10-07 03:58:08','2025-10-07 03:58:08'),(229,10,2,11,'5L8ZBHUIPB6QRP57',999.99000000,1.00000000,0.00000000,999.99000000,2,1,NULL,NULL,NULL,'2025-10-25 15:58:08','2025-10-25 15:58:08'),(230,9,2,9,'QLEUYEL0RWNN3S7J',499.99000000,1.00000000,0.00000000,499.99000000,1,0,NULL,NULL,'2026-09-18 19:58:08','2025-09-18 19:58:08','2025-09-18 19:58:08'),(231,7,1,4,'CTJKQZSBG5THYSSO',29.99000000,1.00000000,0.00000000,29.99000000,2,0,NULL,NULL,NULL,'2025-10-01 08:58:08','2025-10-01 08:58:08'),(232,8,1,10,'UA6D88QSIW4UW1TL',49.99000000,1.00000000,0.00000000,49.99000000,1,0,NULL,NULL,'2025-12-27 16:58:08','2025-11-27 16:58:08','2025-11-27 16:58:08'),(233,10,4,9,'QRJCNLYOWXDPTST0',999.99000000,1.00000000,0.00000000,999.99000000,0,1,NULL,NULL,NULL,'2025-09-23 08:58:08','2025-09-23 08:58:08'),(234,8,4,14,'YTMJ37MLTSRQQ6EO',49.99000000,1.00000000,0.00000000,49.99000000,1,1,NULL,NULL,'2025-11-03 16:58:08','2025-10-04 16:58:08','2025-10-04 16:58:08'),(235,9,1,9,'G8C6C4SOMLMJNV9G',499.99000000,1.00000000,0.00000000,499.99000000,2,1,NULL,NULL,NULL,'2025-10-26 05:58:08','2025-10-26 05:58:08'),(236,9,4,10,'HJMTUHV96HN6HQIQ',499.99000000,1.00000000,0.00000000,499.99000000,0,0,NULL,NULL,NULL,'2025-09-27 04:58:08','2025-09-27 04:58:08'),(237,6,1,1,'XK03BUYSFWUBF7CA',0.00000000,1.00000000,0.00000000,0.00000000,1,0,NULL,NULL,'2025-10-01 06:58:08','2025-09-24 06:58:08','2025-09-24 06:58:08'),(238,6,5,8,'G9KR7JDYVGSCSXRO',0.00000000,1.00000000,0.00000000,0.00000000,2,1,NULL,NULL,NULL,'2025-10-21 17:58:08','2025-10-21 17:58:08'),(239,8,5,10,'UOCJPIORN9M3KE9E',49.99000000,1.00000000,0.00000000,49.99000000,2,1,NULL,NULL,NULL,'2025-09-09 01:58:08','2025-09-09 01:58:08'),(240,6,2,5,'9EKIIGIGPLNA80JR',0.00000000,1.00000000,0.00000000,0.00000000,0,1,NULL,NULL,NULL,'2025-10-03 19:58:08','2025-10-03 19:58:08');
/*!40000 ALTER TABLE `sp_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_permissions`
--

DROP TABLE IF EXISTS `sp_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
INSERT INTO `sp_plan_signals` VALUES (8,8615691),(10,8615691),(10,6439884),(8,70992515),(6,58455202),(8,58455202),(10,58455202),(9,28228894),(6,98170302),(9,98170302),(7,93271535),(8,93271535),(10,93271535),(8,37479548),(9,37479548),(10,37479548),(7,26453875),(8,2082774),(9,2082774),(10,2082774),(6,90081086),(10,90081086),(6,64366681),(9,64366681),(10,64366681),(6,99207005),(8,99207005),(9,99207005),(7,39928468),(9,39928468),(7,53650350),(8,53650350),(9,53650350),(9,6766881),(10,83809273),(9,8770522),(6,73781678),(8,73781678),(7,34393087),(8,34393087),(6,40549812),(8,40549812),(6,92440206),(8,92440206),(9,92440206),(6,27873679),(9,27873679),(6,32851846),(9,32851846),(8,74071800),(10,74071800),(7,67631132),(8,97763066),(9,97763066),(6,65123182),(9,65123182),(6,90875894),(7,90875894),(8,90875894),(6,83107355),(6,36943596),(10,36943596),(9,96722720),(8,60415315),(6,57448405),(7,57448405),(10,57448405),(7,13296942),(10,13296942),(7,71105228),(8,71105228),(10,71105228),(7,27029770),(10,27029770),(6,83595915),(8,83595915),(9,83595915),(7,40835261),(9,40835261),(10,40835261),(8,19112185),(10,19112185),(6,19821883),(7,19821883),(8,19821883),(9,8051606),(10,8051606),(7,64926928),(6,29692448),(10,29692448),(6,69947681),(8,69947681),(9,69947681),(6,57409494),(10,57409494),(7,51844902);
/*!40000 ALTER TABLE `sp_plan_signals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_plan_subscriptions`
--

DROP TABLE IF EXISTS `sp_plan_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_plan_subscriptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `plan_id` bigint(20) unsigned NOT NULL,
  `is_current` tinyint(1) NOT NULL,
  `plan_expired_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sp_plan_subscriptions_user_id_is_current_plan_expired_at_index` (`user_id`,`is_current`,`plan_expired_at`),
  KEY `plan_subscriptions_user_id_is_current_plan_expired_at_index` (`user_id`,`is_current`,`plan_expired_at`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_plan_subscriptions`
--

LOCK TABLES `sp_plan_subscriptions` WRITE;
/*!40000 ALTER TABLE `sp_plan_subscriptions` DISABLE KEYS */;
INSERT INTO `sp_plan_subscriptions` VALUES (1,1,10,1,'2025-11-19 15:48:39','2025-11-19 08:48:39','2025-12-05 08:48:39'),(2,2,8,1,'2025-12-11 15:58:07','2025-11-11 08:58:07','2025-12-05 08:58:07'),(3,3,6,1,'2025-12-06 15:58:12','2025-11-29 08:58:12','2025-12-05 08:58:12'),(4,5,9,0,'2026-08-19 15:48:39','2025-08-19 08:48:39','2026-08-19 08:48:39'),(5,5,6,0,'2025-10-04 15:48:39','2025-09-27 08:48:39','2025-10-04 08:48:39'),(6,1,9,0,'2026-09-07 15:48:39','2025-09-07 08:48:39','2026-09-07 08:48:39'),(7,3,9,0,'2026-09-01 15:48:39','2025-09-01 08:48:39','2026-09-01 08:48:39'),(8,4,6,0,'2025-09-02 15:48:39','2025-08-26 08:48:39','2025-09-02 08:48:39'),(9,1,8,1,'2025-12-05 15:58:07','2025-11-05 08:58:07','2025-12-05 08:58:07'),(10,2,10,1,'2025-11-27 15:55:59','2025-11-27 08:55:59','2025-12-05 08:55:59'),(11,3,7,1,'2025-12-06 15:55:59','2025-11-06 08:55:59','2025-12-05 08:55:59'),(12,3,7,0,'2025-09-15 15:55:59','2025-08-16 08:55:59','2025-09-15 08:55:59'),(13,2,8,0,'2025-09-16 15:55:59','2025-08-17 08:55:59','2025-09-16 08:55:59'),(14,2,7,0,'2025-10-31 15:55:59','2025-10-01 08:55:59','2025-10-31 08:55:59'),(15,4,7,0,'2025-09-28 15:55:59','2025-08-29 08:55:59','2025-09-28 08:55:59'),(16,2,9,0,'2026-10-01 15:55:59','2025-10-01 08:55:59','2026-10-01 08:55:59'),(17,3,9,1,'2026-11-27 15:58:07','2025-11-27 08:58:07','2025-12-05 08:58:07'),(18,4,8,0,'2025-09-29 15:58:07','2025-08-30 08:58:07','2025-09-29 08:58:07'),(19,2,9,0,'2026-08-28 15:58:07','2025-08-28 08:58:07','2026-08-28 08:58:07'),(20,2,10,0,'2025-09-10 15:58:07','2025-09-10 08:58:07','2025-09-10 08:58:07'),(21,1,7,0,'2025-10-22 15:58:07','2025-09-22 08:58:07','2025-10-22 08:58:07'),(22,1,8,0,'2025-10-16 15:58:07','2025-09-16 08:58:07','2025-10-16 08:58:07'),(23,1,6,1,'2025-11-23 15:58:12','2025-11-16 08:58:12','2025-12-05 08:58:12'),(24,2,6,1,'2025-11-13 15:58:12','2025-11-06 08:58:12','2025-12-05 08:58:12'),(25,1,8,0,'2025-09-30 15:58:12','2025-08-31 08:58:12','2025-09-30 08:58:12'),(26,1,6,0,'2025-10-06 15:58:12','2025-09-29 08:58:12','2025-10-06 08:58:12'),(27,5,9,0,'2026-09-04 15:58:12','2025-09-04 08:58:12','2026-09-04 08:58:12'),(28,2,6,0,'2025-09-26 15:58:13','2025-09-19 08:58:13','2025-09-26 08:58:13'),(29,3,6,0,'2025-09-14 15:58:13','2025-09-07 08:58:13','2025-09-14 08:58:13');
/*!40000 ALTER TABLE `sp_plan_subscriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_plans`
--

DROP TABLE IF EXISTS `sp_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `sp_signal_take_profits`
--

DROP TABLE IF EXISTS `sp_signal_take_profits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_signal_take_profits` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `signal_id` bigint(20) unsigned NOT NULL,
  `tp_level` tinyint(4) NOT NULL COMMENT 'TP level number (1, 2, 3, etc.)',
  `tp_price` decimal(28,8) NOT NULL COMMENT 'Take profit price for this level',
  `tp_percentage` decimal(5,2) DEFAULT NULL COMMENT 'Percentage of total position to close at this TP',
  `lot_percentage` decimal(5,2) DEFAULT NULL COMMENT 'Percentage of lot size for this TP (alternative to tp_percentage)',
  `is_closed` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Whether this TP level has been hit',
  `closed_at` timestamp NULL DEFAULT NULL COMMENT 'When this TP was hit',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sp_signal_take_profits_signal_id_tp_level_unique` (`signal_id`,`tp_level`),
  KEY `sp_signal_take_profits_signal_id_tp_level_index` (`signal_id`,`tp_level`),
  KEY `sp_signal_take_profits_signal_id_is_closed_index` (`signal_id`,`is_closed`),
  CONSTRAINT `sp_signal_take_profits_signal_id_foreign` FOREIGN KEY (`signal_id`) REFERENCES `sp_signals` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_signal_take_profits`
--

LOCK TABLES `sp_signal_take_profits` WRITE;
/*!40000 ALTER TABLE `sp_signal_take_profits` DISABLE KEYS */;
/*!40000 ALTER TABLE `sp_signal_take_profits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_signals`
--

DROP TABLE IF EXISTS `sp_signals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
  `last_modified_at` timestamp NULL DEFAULT NULL,
  `modification_count` int(11) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sp_signals_channel_source_id_index` (`channel_source_id`),
  KEY `sp_signals_auto_created_index` (`auto_created`),
  KEY `sp_signals_message_hash_index` (`message_hash`),
  KEY `sp_signals_structure_sl_price_index` (`structure_sl_price`),
  KEY `sp_signals_is_published_published_date_index` (`is_published`,`published_date`),
  KEY `signals_is_published_published_date_index` (`is_published`,`published_date`),
  CONSTRAINT `sp_signals_channel_source_id_foreign` FOREIGN KEY (`channel_source_id`) REFERENCES `sp_channel_sources` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=99207006 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_signals`
--

LOCK TABLES `sp_signals` WRITE;
/*!40000 ALTER TABLE `sp_signals` DISABLE KEYS */;
INSERT INTO `sp_signals` VALUES (1673671,NULL,'BNB/USDT sell signal - 8H',9,15,6,476.18000000,487.13214000,434.27616000,NULL,NULL,'Demo trading signal for BNB/USDT on 8H timeframe. This is a sell signal with entry at 476.18, stop loss at 487.13214, and take profit at 434.27616.','sell',0,0,NULL,'2025-12-05 15:41:45',NULL,0,1,'2025-12-05 08:41:45','2025-12-05 08:41:45'),(2082774,NULL,'BTC/USDT buy signal - 8H',9,13,5,425.74000000,417.65094000,450.00718000,NULL,NULL,'Demo trading signal for BTC/USDT on 8H timeframe. This is a buy signal with entry at 425.74, stop loss at 417.65094, and take profit at 450.00718.','buy',1,1,NULL,'2025-11-10 11:41:44',NULL,0,1,'2025-12-05 08:41:44','2025-12-05 08:41:44'),(6439884,NULL,'UK100 buy signal - 1D',11,27,6,139.35000000,135.86625000,151.19475000,NULL,NULL,'Demo trading signal for UK100 on 1D timeframe. This is a buy signal with entry at 139.35, stop loss at 135.86625, and take profit at 151.19475.','buy',1,0,NULL,'2025-11-18 23:41:41',NULL,0,1,'2025-12-05 08:41:41','2025-12-05 08:41:41'),(6766881,NULL,'US30 buy signal - 1W',12,24,4,417.99000000,405.86829000,438.88950000,NULL,NULL,'Demo trading signal for US30 on 1W timeframe. This is a buy signal with entry at 417.99, stop loss at 405.86829, and take profit at 438.8895.','buy',1,1,NULL,'2025-11-18 16:41:45',NULL,0,1,'2025-12-05 08:41:45','2025-12-05 08:41:45'),(7358420,NULL,'US30 sell signal - 6H',8,24,6,480.25000000,504.26250000,444.71150000,NULL,NULL,'Demo trading signal for US30 on 6H timeframe. This is a sell signal with entry at 480.25, stop loss at 504.2625, and take profit at 444.7115.','sell',0,0,NULL,'2025-12-05 15:41:48',NULL,0,1,'2025-12-05 08:41:48','2025-12-05 08:41:48'),(7523648,NULL,'USD/CHF buy signal - 12H',10,5,5,442.08000000,419.97600000,483.19344000,NULL,NULL,'Demo trading signal for USD/CHF on 12H timeframe. This is a buy signal with entry at 442.08, stop loss at 419.976, and take profit at 483.19344.','buy',0,1,NULL,'2025-12-05 15:41:49',NULL,0,1,'2025-12-05 08:41:49','2025-12-05 08:41:49'),(8051606,NULL,'EUR/AUD sell signal - 1MO',13,12,7,403.31000000,407.74641000,373.86837000,NULL,NULL,'Demo trading signal for EUR/AUD on 1MO timeframe. This is a sell signal with entry at 403.31, stop loss at 407.74641, and take profit at 373.86837.','sell',1,1,NULL,'2025-11-06 01:41:50',NULL,0,1,'2025-12-05 08:41:50','2025-12-05 08:41:50'),(8615691,NULL,'ADA/USDT buy signal - 1W',12,17,2,148.99000000,142.43444000,159.27031000,NULL,NULL,'Demo trading signal for ADA/USDT on 1W timeframe. This is a buy signal with entry at 148.99, stop loss at 142.43444, and take profit at 159.27031.','buy',1,1,NULL,'2025-11-29 10:41:41',NULL,0,1,'2025-12-05 08:41:41','2025-12-05 08:41:41'),(8770522,NULL,'EUR/JPY sell signal - 1M',1,9,4,123.13000000,125.10008000,116.85037000,NULL,NULL,'Demo trading signal for EUR/JPY on 1M timeframe. This is a sell signal with entry at 123.13, stop loss at 125.10008, and take profit at 116.85037.','sell',1,0,NULL,'2025-11-27 15:41:46',NULL,0,1,'2025-12-05 08:41:46','2025-12-05 08:41:46'),(13296942,NULL,'SOL/USDT buy signal - 4H',7,18,7,319.89000000,305.17506000,337.48395000,NULL,NULL,'Demo trading signal for SOL/USDT on 4H timeframe. This is a buy signal with entry at 319.89, stop loss at 305.17506, and take profit at 337.48395.','buy',1,1,NULL,'2025-11-23 01:41:49',NULL,0,1,'2025-12-05 08:41:49','2025-12-05 08:41:49'),(18818071,NULL,'US500 sell signal - 15M',3,26,1,391.44000000,404.74896000,365.99640000,NULL,NULL,'Demo trading signal for US500 on 15M timeframe. This is a sell signal with entry at 391.44, stop loss at 404.74896, and take profit at 365.9964.','sell',0,1,NULL,'2025-12-05 15:41:50',NULL,0,1,'2025-12-05 08:41:50','2025-12-05 08:41:50'),(19112185,NULL,'DOT/USDT sell signal - 8H',9,19,4,315.48000000,329.36112000,287.71776000,NULL,NULL,'Demo trading signal for DOT/USDT on 8H timeframe. This is a sell signal with entry at 315.48, stop loss at 329.36112, and take profit at 287.71776.','sell',1,0,NULL,'2025-11-24 18:41:50',NULL,0,1,'2025-12-05 08:41:50','2025-12-05 08:41:50'),(19821883,NULL,'WTI/USD buy signal - 1MO',13,22,6,216.48000000,212.79984000,223.62384000,NULL,NULL,'Demo trading signal for WTI/USD on 1MO timeframe. This is a buy signal with entry at 216.48, stop loss at 212.79984, and take profit at 223.62384.','buy',1,1,NULL,'2025-11-18 03:41:50',NULL,0,1,'2025-12-05 08:41:50','2025-12-05 08:41:50'),(20850519,NULL,'GBP/USD buy signal - 2H',6,2,3,39.26000000,38.59258000,41.30152000,NULL,NULL,'Demo trading signal for GBP/USD on 2H timeframe. This is a buy signal with entry at 39.26, stop loss at 38.59258, and take profit at 41.30152.','buy',0,0,NULL,'2025-12-05 15:41:51',NULL,0,1,'2025-12-05 08:41:51','2025-12-05 08:41:51'),(21205743,NULL,'ADA/USDT sell signal - 2H',6,17,6,186.70000000,190.80740000,179.97880000,NULL,NULL,'Demo trading signal for ADA/USDT on 2H timeframe. This is a sell signal with entry at 186.7, stop loss at 190.8074, and take profit at 179.9788.','sell',0,1,NULL,'2025-12-05 15:41:46',NULL,0,1,'2025-12-05 08:41:46','2025-12-05 08:41:46'),(21543303,NULL,'USD/JPY buy signal - 30M',4,3,4,331.79000000,323.49525000,355.34709000,NULL,NULL,'Demo trading signal for USD/JPY on 30M timeframe. This is a buy signal with entry at 331.79, stop loss at 323.49525, and take profit at 355.34709.','buy',0,0,NULL,'2025-12-05 15:41:51',NULL,0,1,'2025-12-05 08:41:51','2025-12-05 08:41:51'),(26453875,NULL,'JPN225 buy signal - 5M',2,29,6,459.07000000,439.32999000,499.46816000,NULL,NULL,'Demo trading signal for JPN225 on 5M timeframe. This is a buy signal with entry at 459.07, stop loss at 439.32999, and take profit at 499.46816.','buy',1,0,NULL,'2025-11-09 02:41:44',NULL,0,1,'2025-12-05 08:41:44','2025-12-05 08:41:44'),(27029770,NULL,'USD/CAD buy signal - 8H',9,6,6,499.64000000,485.15044000,544.60760000,NULL,NULL,'Demo trading signal for USD/CAD on 8H timeframe. This is a buy signal with entry at 499.64, stop loss at 485.15044, and take profit at 544.6076.','buy',1,1,NULL,'2025-12-03 12:41:49',NULL,0,1,'2025-12-05 08:41:49','2025-12-05 08:41:49'),(27873679,NULL,'BTC/USDT sell signal - 2H',6,13,6,498.67000000,516.12345000,452.29369000,NULL,NULL,'Demo trading signal for BTC/USDT on 2H timeframe. This is a sell signal with entry at 498.67, stop loss at 516.12345, and take profit at 452.29369.','sell',1,1,NULL,'2025-11-28 16:41:48',NULL,0,1,'2025-12-05 08:41:48','2025-12-05 08:41:48'),(28228894,NULL,'US100 sell signal - 6H',8,25,7,87.79000000,91.03823000,84.62956000,NULL,NULL,'Demo trading signal for US100 on 6H timeframe. This is a sell signal with entry at 87.79, stop loss at 91.03823, and take profit at 84.62956.','sell',1,1,NULL,'2025-12-02 18:41:42',NULL,0,1,'2025-12-05 08:41:42','2025-12-05 08:41:42'),(29614616,NULL,'USD/CAD sell signal - 15M',3,6,5,103.99000000,108.98152000,101.91020000,NULL,NULL,'Demo trading signal for USD/CAD on 15M timeframe. This is a sell signal with entry at 103.99, stop loss at 108.98152, and take profit at 101.9102.','sell',0,1,NULL,'2025-12-05 15:41:47',NULL,0,1,'2025-12-05 08:41:47','2025-12-05 08:41:47'),(29692448,NULL,'EUR/GBP buy signal - 8H',9,8,2,268.59000000,263.21820000,288.73425000,NULL,NULL,'Demo trading signal for EUR/GBP on 8H timeframe. This is a buy signal with entry at 268.59, stop loss at 263.2182, and take profit at 288.73425.','buy',1,1,NULL,'2025-11-09 02:41:51',NULL,0,1,'2025-12-05 08:41:51','2025-12-05 08:41:51'),(32851846,NULL,'XAU/USD sell signal - 30M',4,20,5,154.93000000,157.09902000,142.07081000,NULL,NULL,'Demo trading signal for XAU/USD on 30M timeframe. This is a sell signal with entry at 154.93, stop loss at 157.09902, and take profit at 142.07081.','sell',1,1,NULL,'2025-11-06 01:41:48',NULL,0,1,'2025-12-05 08:41:48','2025-12-05 08:41:48'),(33064970,NULL,'WTI/USD sell signal - 1M',1,22,4,33.75000000,34.52625000,30.67875000,NULL,NULL,'Demo trading signal for WTI/USD on 1M timeframe. This is a sell signal with entry at 33.75, stop loss at 34.52625, and take profit at 30.67875.','sell',0,0,NULL,'2025-12-05 15:41:49',NULL,0,1,'2025-12-05 08:41:49','2025-12-05 08:41:49'),(34393087,NULL,'AUD/JPY buy signal - 15M',3,11,4,309.85000000,297.45600000,334.32815000,NULL,NULL,'Demo trading signal for AUD/JPY on 15M timeframe. This is a buy signal with entry at 309.85, stop loss at 297.456, and take profit at 334.32815.','buy',1,1,NULL,'2025-11-24 05:41:47',NULL,0,1,'2025-12-05 08:41:47','2025-12-05 08:41:47'),(36943596,NULL,'USD/CAD sell signal - 6H',8,6,7,451.37000000,468.07069000,439.63438000,NULL,NULL,'Demo trading signal for USD/CAD on 6H timeframe. This is a sell signal with entry at 451.37, stop loss at 468.07069, and take profit at 439.63438.','sell',1,1,NULL,'2025-12-01 22:41:49',NULL,0,1,'2025-12-05 08:41:49','2025-12-05 08:41:49'),(37479548,NULL,'GBP/USD buy signal - 12H',10,2,6,58.16000000,57.11312000,63.33624000,NULL,NULL,'Demo trading signal for GBP/USD on 12H timeframe. This is a buy signal with entry at 58.16, stop loss at 57.11312, and take profit at 63.33624.','buy',1,0,NULL,'2025-11-23 20:41:43',NULL,0,1,'2025-12-05 08:41:43','2025-12-05 08:41:43'),(39928468,NULL,'US30 buy signal - 1MO',13,24,2,358.66000000,354.71474000,384.84218000,NULL,NULL,'Demo trading signal for US30 on 1MO timeframe. This is a buy signal with entry at 358.66, stop loss at 354.71474, and take profit at 384.84218.','buy',1,0,NULL,'2025-11-13 09:41:45',NULL,0,1,'2025-12-05 08:41:45','2025-12-05 08:41:45'),(40549812,NULL,'BNB/USDT sell signal - 5M',2,15,1,298.69000000,309.44284000,283.45681000,NULL,NULL,'Demo trading signal for BNB/USDT on 5M timeframe. This is a sell signal with entry at 298.69, stop loss at 309.44284, and take profit at 283.45681.','sell',1,0,NULL,'2025-11-12 10:41:47',NULL,0,1,'2025-12-05 08:41:47','2025-12-05 08:41:47'),(40835261,NULL,'BRENT/USD buy signal - 15M',3,23,7,63.68000000,62.85216000,68.71072000,NULL,NULL,'Demo trading signal for BRENT/USD on 15M timeframe. This is a buy signal with entry at 63.68, stop loss at 62.85216, and take profit at 68.71072.','buy',1,1,NULL,'2025-11-20 01:41:50',NULL,0,1,'2025-12-05 08:41:50','2025-12-05 08:41:50'),(45253448,NULL,'US30 buy signal - 1D',11,24,5,206.89000000,198.40751000,212.88981000,NULL,NULL,'Demo trading signal for US30 on 1D timeframe. This is a buy signal with entry at 206.89, stop loss at 198.40751, and take profit at 212.88981.','buy',0,1,NULL,'2025-12-05 15:41:50',NULL,0,1,'2025-12-05 08:41:50','2025-12-05 08:41:50'),(51844902,NULL,'EUR/JPY buy signal - 4H',7,9,6,283.11000000,278.86335000,305.75880000,NULL,NULL,'Demo trading signal for EUR/JPY on 4H timeframe. This is a buy signal with entry at 283.11, stop loss at 278.86335, and take profit at 305.7588.','buy',1,1,NULL,'2025-11-19 08:41:51',NULL,0,1,'2025-12-05 08:41:51','2025-12-05 08:41:51'),(53650350,NULL,'XAG/USD buy signal - 15M',3,21,2,248.72000000,246.23280000,259.91240000,NULL,NULL,'Demo trading signal for XAG/USD on 15M timeframe. This is a buy signal with entry at 248.72, stop loss at 246.2328, and take profit at 259.9124.','buy',1,0,NULL,'2025-11-23 03:41:45',NULL,0,1,'2025-12-05 08:41:45','2025-12-05 08:41:45'),(54847152,NULL,'XAU/USD sell signal - 1D',11,20,4,443.38000000,462.44534000,402.58904000,NULL,NULL,'Demo trading signal for XAU/USD on 1D timeframe. This is a sell signal with entry at 443.38, stop loss at 462.44534, and take profit at 402.58904.','sell',0,0,NULL,'2025-12-05 15:41:45',NULL,0,1,'2025-12-05 08:41:45','2025-12-05 08:41:45'),(57409494,NULL,'EUR/AUD buy signal - 1H',5,12,7,42.34000000,40.56172000,44.49934000,NULL,NULL,'Demo trading signal for EUR/AUD on 1H timeframe. This is a buy signal with entry at 42.34, stop loss at 40.56172, and take profit at 44.49934.','buy',1,0,NULL,'2025-11-10 03:41:51',NULL,0,1,'2025-12-05 08:41:51','2025-12-05 08:41:51'),(57448405,NULL,'NZD/USD sell signal - 1H',5,7,7,51.15000000,52.78680000,49.05285000,NULL,NULL,'Demo trading signal for NZD/USD on 1H timeframe. This is a sell signal with entry at 51.15, stop loss at 52.7868, and take profit at 49.05285.','sell',1,0,NULL,'2025-11-24 09:41:49',NULL,0,1,'2025-12-05 08:41:49','2025-12-05 08:41:49'),(58455202,NULL,'USD/JPY sell signal - 5M',2,3,6,346.89000000,352.44024000,323.30148000,NULL,NULL,'Demo trading signal for USD/JPY on 5M timeframe. This is a sell signal with entry at 346.89, stop loss at 352.44024, and take profit at 323.30148.','sell',1,1,NULL,'2025-11-21 23:41:42',NULL,0,1,'2025-12-05 08:41:42','2025-12-05 08:41:42'),(60415315,NULL,'XAU/USD sell signal - 1M',1,20,3,261.08000000,265.25728000,252.72544000,NULL,NULL,'Demo trading signal for XAU/USD on 1M timeframe. This is a sell signal with entry at 261.08, stop loss at 265.25728, and take profit at 252.72544.','sell',1,1,NULL,'2025-11-17 03:41:49',NULL,0,1,'2025-12-05 08:41:49','2025-12-05 08:41:49'),(60516071,NULL,'ETH/USDT sell signal - 1D',11,14,1,472.09000000,477.75508000,454.15058000,NULL,NULL,'Demo trading signal for ETH/USDT on 1D timeframe. This is a sell signal with entry at 472.09, stop loss at 477.75508, and take profit at 454.15058.','sell',0,1,NULL,'2025-12-05 15:41:42',NULL,0,1,'2025-12-05 08:41:42','2025-12-05 08:41:42'),(61344947,NULL,'EUR/JPY sell signal - 1W',12,9,3,111.20000000,112.31200000,106.19600000,NULL,NULL,'Demo trading signal for EUR/JPY on 1W timeframe. This is a sell signal with entry at 111.2, stop loss at 112.312, and take profit at 106.196.','sell',0,1,NULL,'2025-12-05 15:41:49',NULL,0,1,'2025-12-05 08:41:49','2025-12-05 08:41:49'),(63497230,NULL,'EUR/GBP buy signal - 1MO',13,8,7,153.30000000,147.32130000,158.51220000,NULL,NULL,'Demo trading signal for EUR/GBP on 1MO timeframe. This is a buy signal with entry at 153.3, stop loss at 147.3213, and take profit at 158.5122.','buy',0,0,NULL,'2025-12-05 15:41:48',NULL,0,1,'2025-12-05 08:41:48','2025-12-05 08:41:48'),(64366681,NULL,'SOL/USDT sell signal - 30M',4,18,2,485.43000000,508.24521000,445.62474000,NULL,NULL,'Demo trading signal for SOL/USDT on 30M timeframe. This is a sell signal with entry at 485.43, stop loss at 508.24521, and take profit at 445.62474.','sell',1,0,NULL,'2025-11-10 06:41:44',NULL,0,1,'2025-12-05 08:41:44','2025-12-05 08:41:44'),(64926928,NULL,'XAG/USD sell signal - 8H',9,21,4,413.49000000,425.48121000,381.65127000,NULL,NULL,'Demo trading signal for XAG/USD on 8H timeframe. This is a sell signal with entry at 413.49, stop loss at 425.48121, and take profit at 381.65127.','sell',1,0,NULL,'2025-11-22 15:41:50',NULL,0,1,'2025-12-05 08:41:50','2025-12-05 08:41:50'),(65123182,NULL,'XAU/USD buy signal - 15M',3,20,7,407.15000000,386.79250000,445.82925000,NULL,NULL,'Demo trading signal for XAU/USD on 15M timeframe. This is a buy signal with entry at 407.15, stop loss at 386.7925, and take profit at 445.82925.','buy',1,1,NULL,'2025-11-22 15:41:48',NULL,0,1,'2025-12-05 08:41:48','2025-12-05 08:41:48'),(66480269,NULL,'WTI/USD buy signal - 1MO',13,22,4,479.23000000,461.01926000,498.87843000,NULL,NULL,'Demo trading signal for WTI/USD on 1MO timeframe. This is a buy signal with entry at 479.23, stop loss at 461.01926, and take profit at 498.87843.','buy',0,0,NULL,'2025-12-05 15:41:47',NULL,0,1,'2025-12-05 08:41:47','2025-12-05 08:41:47'),(67631132,NULL,'WTI/USD sell signal - 1W',12,22,2,86.29000000,87.84322000,81.11260000,NULL,NULL,'Demo trading signal for WTI/USD on 1W timeframe. This is a sell signal with entry at 86.29, stop loss at 87.84322, and take profit at 81.1126.','sell',1,0,NULL,'2025-11-27 00:41:48',NULL,0,1,'2025-12-05 08:41:48','2025-12-05 08:41:48'),(69947681,NULL,'XAG/USD sell signal - 1H',5,21,1,20.87000000,21.78828000,20.32738000,NULL,NULL,'Demo trading signal for XAG/USD on 1H timeframe. This is a sell signal with entry at 20.87, stop loss at 21.78828, and take profit at 20.32738.','sell',1,1,NULL,'2025-11-30 20:41:51',NULL,0,1,'2025-12-05 08:41:51','2025-12-05 08:41:51'),(70992515,NULL,'US30 sell signal - 4H',7,24,2,29.27000000,30.35299000,26.43081000,NULL,NULL,'Demo trading signal for US30 on 4H timeframe. This is a sell signal with entry at 29.27, stop loss at 30.35299, and take profit at 26.43081.','sell',1,1,NULL,'2025-11-05 08:41:42',NULL,0,1,'2025-12-05 08:41:42','2025-12-05 08:41:42'),(71105228,NULL,'ADA/USDT buy signal - 30M',4,17,5,59.64000000,57.61224000,60.95208000,NULL,NULL,'Demo trading signal for ADA/USDT on 30M timeframe. This is a buy signal with entry at 59.64, stop loss at 57.61224, and take profit at 60.95208.','buy',1,0,NULL,'2025-11-23 20:41:49',NULL,0,1,'2025-12-05 08:41:49','2025-12-05 08:41:49'),(71728155,NULL,'USD/CAD buy signal - 2H',6,6,5,69.06000000,66.91914000,73.68702000,NULL,NULL,'Demo trading signal for USD/CAD on 2H timeframe. This is a buy signal with entry at 69.06, stop loss at 66.91914, and take profit at 73.68702.','buy',0,0,NULL,'2025-12-05 15:41:50',NULL,0,1,'2025-12-05 08:41:50','2025-12-05 08:41:50'),(73781678,NULL,'XAG/USD buy signal - 1MO',13,21,3,39.14000000,37.26128000,41.95808000,NULL,NULL,'Demo trading signal for XAG/USD on 1MO timeframe. This is a buy signal with entry at 39.14, stop loss at 37.26128, and take profit at 41.95808.','buy',1,1,NULL,'2025-11-21 01:41:47',NULL,0,1,'2025-12-05 08:41:47','2025-12-05 08:41:47'),(74071800,NULL,'GER40 buy signal - 6H',8,28,4,140.54000000,137.44812000,149.67510000,NULL,NULL,'Demo trading signal for GER40 on 6H timeframe. This is a buy signal with entry at 140.54, stop loss at 137.44812, and take profit at 149.6751.','buy',1,0,NULL,'2025-11-16 06:41:48',NULL,0,1,'2025-12-05 08:41:48','2025-12-05 08:41:48'),(75506397,NULL,'BNB/USDT buy signal - 15M',3,15,2,327.99000000,313.88643000,355.86915000,NULL,NULL,'Demo trading signal for BNB/USDT on 15M timeframe. This is a buy signal with entry at 327.99, stop loss at 313.88643, and take profit at 355.86915.','buy',0,0,NULL,'2025-12-05 15:41:43',NULL,0,1,'2025-12-05 08:41:43','2025-12-05 08:41:43'),(81443483,NULL,'US100 buy signal - 1MO',13,25,7,474.73000000,457.63972000,504.16326000,NULL,NULL,'Demo trading signal for US100 on 1MO timeframe. This is a buy signal with entry at 474.73, stop loss at 457.63972, and take profit at 504.16326.','buy',0,0,NULL,'2025-12-05 15:41:44',NULL,0,1,'2025-12-05 08:41:44','2025-12-05 08:41:44'),(83107355,NULL,'EUR/USD buy signal - 12H',10,1,7,170.75000000,168.53025000,176.21400000,NULL,NULL,'Demo trading signal for EUR/USD on 12H timeframe. This is a buy signal with entry at 170.75, stop loss at 168.53025, and take profit at 176.214.','buy',1,1,NULL,'2025-11-25 18:41:48',NULL,0,1,'2025-12-05 08:41:48','2025-12-05 08:41:48'),(83107524,NULL,'XRP/USDT buy signal - 6H',8,16,2,365.36000000,358.78352000,388.74304000,NULL,NULL,'Demo trading signal for XRP/USDT on 6H timeframe. This is a buy signal with entry at 365.36, stop loss at 358.78352, and take profit at 388.74304.','buy',0,0,NULL,'2025-12-05 15:41:46',NULL,0,1,'2025-12-05 08:41:46','2025-12-05 08:41:46'),(83595915,NULL,'EUR/USD buy signal - 12H',10,1,3,474.41000000,456.85683000,516.15808000,NULL,NULL,'Demo trading signal for EUR/USD on 12H timeframe. This is a buy signal with entry at 474.41, stop loss at 456.85683, and take profit at 516.15808.','buy',1,0,NULL,'2025-11-07 03:41:50',NULL,0,1,'2025-12-05 08:41:50','2025-12-05 08:41:50'),(83809273,NULL,'GBP/USD buy signal - 4H',7,2,4,155.86000000,152.58694000,168.48466000,NULL,NULL,'Demo trading signal for GBP/USD on 4H timeframe. This is a buy signal with entry at 155.86, stop loss at 152.58694, and take profit at 168.48466.','buy',1,1,NULL,'2025-11-04 17:41:46',NULL,0,1,'2025-12-05 08:41:46','2025-12-05 08:41:46'),(89608448,NULL,'XAG/USD sell signal - 8H',9,21,4,496.60000000,505.04220000,465.81080000,NULL,NULL,'Demo trading signal for XAG/USD on 8H timeframe. This is a sell signal with entry at 496.6, stop loss at 505.0422, and take profit at 465.8108.','sell',0,0,NULL,'2025-12-05 15:41:46',NULL,0,1,'2025-12-05 08:41:46','2025-12-05 08:41:46'),(90081086,NULL,'EUR/GBP sell signal - 15M',3,8,6,429.63000000,448.96335000,417.17073000,NULL,NULL,'Demo trading signal for EUR/GBP on 15M timeframe. This is a sell signal with entry at 429.63, stop loss at 448.96335, and take profit at 417.17073.','sell',1,1,NULL,'2025-12-03 08:41:44',NULL,0,1,'2025-12-05 08:41:44','2025-12-05 08:41:44'),(90875894,NULL,'XAU/USD buy signal - 15M',3,20,6,396.49000000,392.52510000,412.74609000,NULL,NULL,'Demo trading signal for XAU/USD on 15M timeframe. This is a buy signal with entry at 396.49, stop loss at 392.5251, and take profit at 412.74609.','buy',1,0,NULL,'2025-11-27 18:41:48',NULL,0,1,'2025-12-05 08:41:48','2025-12-05 08:41:48'),(92440206,NULL,'AUD/JPY buy signal - 6H',8,11,3,155.97000000,148.95135000,164.23641000,NULL,NULL,'Demo trading signal for AUD/JPY on 6H timeframe. This is a buy signal with entry at 155.97, stop loss at 148.95135, and take profit at 164.23641.','buy',1,1,NULL,'2025-11-18 17:41:47',NULL,0,1,'2025-12-05 08:41:47','2025-12-05 08:41:47'),(93271535,NULL,'US100 sell signal - 1M',1,25,2,207.18000000,211.32360000,188.32662000,NULL,NULL,'Demo trading signal for US100 on 1M timeframe. This is a sell signal with entry at 207.18, stop loss at 211.3236, and take profit at 188.32662.','sell',1,1,NULL,'2025-11-28 03:41:43',NULL,0,1,'2025-12-05 08:41:43','2025-12-05 08:41:43'),(96722720,NULL,'XRP/USDT buy signal - 2H',6,16,2,278.75000000,268.43625000,289.90000000,NULL,NULL,'Demo trading signal for XRP/USDT on 2H timeframe. This is a buy signal with entry at 278.75, stop loss at 268.43625, and take profit at 289.9.','buy',1,1,NULL,'2025-11-25 15:41:49',NULL,0,1,'2025-12-05 08:41:49','2025-12-05 08:41:49'),(97218925,NULL,'GBP/JPY sell signal - 30M',4,10,4,348.66000000,355.28454000,322.85916000,NULL,NULL,'Demo trading signal for GBP/JPY on 30M timeframe. This is a sell signal with entry at 348.66, stop loss at 355.28454, and take profit at 322.85916.','sell',0,1,NULL,'2025-12-05 15:41:49',NULL,0,1,'2025-12-05 08:41:49','2025-12-05 08:41:49'),(97763066,NULL,'JPN225 buy signal - 5M',2,29,6,46.24000000,44.99152000,47.62720000,NULL,NULL,'Demo trading signal for JPN225 on 5M timeframe. This is a buy signal with entry at 46.24, stop loss at 44.99152, and take profit at 47.6272.','buy',1,1,NULL,'2025-11-05 14:41:48',NULL,0,1,'2025-12-05 08:41:48','2025-12-05 08:41:48'),(98170302,NULL,'BNB/USDT sell signal - 4H',7,15,1,466.07000000,480.98424000,420.39514000,NULL,NULL,'Demo trading signal for BNB/USDT on 4H timeframe. This is a sell signal with entry at 466.07, stop loss at 480.98424, and take profit at 420.39514.','sell',1,0,NULL,'2025-11-27 00:41:43',NULL,0,1,'2025-12-05 08:41:43','2025-12-05 08:41:43'),(99207005,NULL,'NZD/USD sell signal - 2H',6,7,2,126.71000000,129.87775000,116.19307000,NULL,NULL,'Demo trading signal for NZD/USD on 2H timeframe. This is a sell signal with entry at 126.71, stop loss at 129.87775, and take profit at 116.19307.','sell',1,1,NULL,'2025-11-28 11:41:45',NULL,0,1,'2025-12-05 08:41:45','2025-12-05 08:41:45');
/*!40000 ALTER TABLE `sp_signals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_srm_ab_test_assignments`
--

DROP TABLE IF EXISTS `sp_srm_ab_test_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
INSERT INTO `sp_templates` VALUES (1,'password_reset','Password Reset','2025-12-05 08:58:02','2025-12-05 08:58:02'),(2,'payment_successfull','Payment Successful','2025-12-05 08:58:02','2025-12-05 08:58:02'),(3,'payment_received','Payment Received','2025-12-05 08:58:02','2025-12-05 08:58:02'),(4,'verify_email','Verify Email','2025-12-05 08:58:02','2025-12-05 08:58:02'),(5,'payment_confirmed','Payment Confirmed','2025-12-05 08:58:02','2025-12-05 08:58:02'),(6,'payment_rejected','Payment Rejected','2025-12-05 08:58:02','2025-12-05 08:58:02'),(7,'withdraw_accepted','Withdrawal Accepted','2025-12-05 08:58:03','2025-12-05 08:58:03'),(8,'withdraw_rejected','Withdrawal Rejected','2025-12-05 08:58:03','2025-12-05 08:58:03'),(9,'refer_commission','Referral Commission','2025-12-05 08:58:03','2025-12-05 08:58:03'),(10,'send_money','Money Sent','2025-12-05 08:58:03','2025-12-05 08:58:03'),(11,'receive_money','Money Received','2025-12-05 08:58:03','2025-12-05 08:58:03'),(12,'plan_subscription','Plan Subscription','2025-12-05 08:58:03','2025-12-05 08:58:03'),(13,'Signal','New Signal Arrived','2025-12-05 08:58:03','2025-12-05 08:58:03');
/*!40000 ALTER TABLE `sp_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_ticket_replies`
--

DROP TABLE IF EXISTS `sp_ticket_replies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `sp_trading_bots`
--

DROP TABLE IF EXISTS `sp_trading_bots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_trading_bots` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `admin_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `exchange_connection_id` bigint(20) unsigned DEFAULT NULL COMMENT 'FK to exchange_connections (nullable for templates)',
  `data_connection_id` bigint(20) unsigned DEFAULT NULL COMMENT 'FK to exchange_connections for OHLCV streaming (MARKET_STREAM_BASED only)',
  `trading_preset_id` bigint(20) unsigned NOT NULL COMMENT 'FK to trading_presets',
  `filter_strategy_id` bigint(20) unsigned DEFAULT NULL COMMENT 'FK to filter_strategies (optional)',
  `ai_model_profile_id` bigint(20) unsigned DEFAULT NULL COMMENT 'FK to ai_model_profiles (optional)',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_paper_trading` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Paper trading mode (demo)',
  `trading_mode` enum('SIGNAL_BASED','MARKET_STREAM_BASED') NOT NULL DEFAULT 'SIGNAL_BASED' COMMENT 'SIGNAL_BASED: Execute only on signals | MARKET_STREAM_BASED: Stream OHLCV data and apply technical indicators',
  `status` enum('stopped','running','paused') NOT NULL DEFAULT 'stopped' COMMENT 'Bot lifecycle status',
  `is_template` tinyint(1) NOT NULL DEFAULT 0,
  `visibility` enum('private','public','admin_only') NOT NULL DEFAULT 'private',
  `parent_bot_id` bigint(20) unsigned DEFAULT NULL,
  `is_admin_owned` tinyint(1) NOT NULL DEFAULT 0,
  `worker_pid` int(10) unsigned DEFAULT NULL COMMENT 'Process ID of background worker',
  `last_started_at` timestamp NULL DEFAULT NULL,
  `last_stopped_at` timestamp NULL DEFAULT NULL,
  `last_paused_at` timestamp NULL DEFAULT NULL,
  `worker_started_at` timestamp NULL DEFAULT NULL,
  `last_market_analysis_at` timestamp NULL DEFAULT NULL,
  `last_position_check_at` timestamp NULL DEFAULT NULL,
  `streaming_symbols` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Symbols to stream (e.g., ["BTC/USDT", "ETH/USDT"])' CHECK (json_valid(`streaming_symbols`)),
  `streaming_timeframes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Timeframes to stream (e.g., ["1h", "4h", "1d"])' CHECK (json_valid(`streaming_timeframes`)),
  `position_monitoring_interval` int(10) unsigned NOT NULL DEFAULT 5 COMMENT 'How often to check SL/TP (seconds)',
  `market_analysis_interval` int(10) unsigned NOT NULL DEFAULT 60 COMMENT 'How often to analyze market for MARKET_STREAM_BASED (seconds)',
  `total_executions` int(10) unsigned NOT NULL DEFAULT 0,
  `successful_executions` int(10) unsigned NOT NULL DEFAULT 0,
  `failed_executions` int(10) unsigned NOT NULL DEFAULT 0,
  `total_profit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `win_rate` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Percentage',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sp_trading_bots_filter_strategy_id_foreign` (`filter_strategy_id`),
  KEY `sp_trading_bots_ai_model_profile_id_foreign` (`ai_model_profile_id`),
  KEY `sp_trading_bots_user_id_index` (`user_id`),
  KEY `sp_trading_bots_admin_id_index` (`admin_id`),
  KEY `sp_trading_bots_is_active_index` (`is_active`),
  KEY `sp_trading_bots_exchange_connection_id_index` (`exchange_connection_id`),
  KEY `sp_trading_bots_trading_preset_id_index` (`trading_preset_id`),
  KEY `sp_trading_bots_status_index` (`status`),
  KEY `sp_trading_bots_data_connection_id_index` (`data_connection_id`),
  KEY `sp_trading_bots_worker_pid_index` (`worker_pid`),
  KEY `sp_trading_bots_parent_bot_id_foreign` (`parent_bot_id`),
  KEY `sp_trading_bots_is_template_visibility_index` (`is_template`,`visibility`),
  CONSTRAINT `sp_trading_bots_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `sp_admins` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sp_trading_bots_ai_model_profile_id_foreign` FOREIGN KEY (`ai_model_profile_id`) REFERENCES `sp_ai_model_profiles` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sp_trading_bots_exchange_connection_id_foreign` FOREIGN KEY (`exchange_connection_id`) REFERENCES `sp_execution_connections` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sp_trading_bots_filter_strategy_id_foreign` FOREIGN KEY (`filter_strategy_id`) REFERENCES `sp_filter_strategies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sp_trading_bots_parent_bot_id_foreign` FOREIGN KEY (`parent_bot_id`) REFERENCES `sp_trading_bots` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sp_trading_bots_trading_preset_id_foreign` FOREIGN KEY (`trading_preset_id`) REFERENCES `sp_trading_presets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sp_trading_bots_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `sp_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_trading_bots`
--

LOCK TABLES `sp_trading_bots` WRITE;
/*!40000 ALTER TABLE `sp_trading_bots` DISABLE KEYS */;
/*!40000 ALTER TABLE `sp_trading_bots` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_trading_presets`
--

DROP TABLE IF EXISTS `sp_trading_presets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
INSERT INTO `sp_trading_presets` VALUES (1,'Conservative Scalper','Low risk, quick profits. Perfect for beginners who want to start trading safely with minimal risk per trade.',NULL,NULL,1,'[\"scalping\",\"conservative\",\"beginner\",\"low-risk\"]','RISK_PERCENT',NULL,0.50,1,1,'NONE',NULL,NULL,NULL,NULL,'PIPS',20,NULL,'SINGLE',1,1.50,100.00,0,NULL,NULL,0,NULL,NULL,0,0,NULL,NULL,0,'STEP_PIPS',NULL,NULL,NULL,NULL,NULL,0,3,NULL,'NONE',NULL,NULL,0,NULL,NULL,NULL,0,NULL,NULL,'08:00:00','18:00:00','SERVER',31,'CUSTOM',0,1,2.00,1,1,NULL,NULL,NULL,'NONE',NULL,0,1,1,'PUBLIC_MARKETPLACE','2025-12-05 08:58:05','2025-12-05 08:58:05',NULL),(2,'Swing Trader','Medium-term trading strategy with multiple take profit levels. Uses break-even and trailing stop for better risk management.',NULL,NULL,1,'[\"swing\",\"medium-term\",\"multi-tp\",\"break-even\"]','RISK_PERCENT',NULL,1.00,3,1,'NONE',NULL,NULL,NULL,NULL,'PIPS',50,NULL,'MULTI',1,2.00,30.00,1,3.00,40.00,1,5.00,30.00,0,1,1.50,0,1,'STEP_PIPS',1.50,20,NULL,NULL,60,0,3,NULL,'NONE',NULL,NULL,0,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,'SERVER',31,'CUSTOM',0,0,NULL,NULL,0,NULL,NULL,NULL,'NONE',NULL,0,1,1,'PUBLIC_MARKETPLACE','2025-12-05 08:58:05','2025-12-05 08:58:05',NULL),(3,'Aggressive Day Trader','High risk, high reward strategy for experienced traders. Features layering and advanced trailing stop.',NULL,NULL,1,'[\"aggressive\",\"day-trading\",\"layering\",\"high-risk\"]','RISK_PERCENT',NULL,2.00,5,2,'NONE',NULL,NULL,NULL,NULL,'PIPS',30,NULL,'MULTI',1,1.50,50.00,1,2.50,30.00,1,4.00,20.00,0,0,NULL,NULL,1,'STEP_ATR',1.00,NULL,14,1.50,30,1,3,20,'MULTIPLY',1.50,10.00,0,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,'SERVER',31,'CUSTOM',0,1,5.00,1,0,NULL,NULL,NULL,'NONE',NULL,0,1,1,'PUBLIC_MARKETPLACE','2025-12-05 08:58:06','2025-12-05 08:58:06',NULL),(4,'Safe Long-Term','Very conservative strategy for long-term traders. Minimal risk with high reward potential.',NULL,NULL,1,'[\"conservative\",\"long-term\",\"safe\",\"low-risk\"]','RISK_PERCENT',NULL,0.25,1,1,'NONE',NULL,NULL,NULL,NULL,'PIPS',100,NULL,'SINGLE',1,3.00,100.00,0,NULL,NULL,0,NULL,NULL,0,1,2.00,0,0,'STEP_PIPS',NULL,NULL,NULL,NULL,NULL,0,3,NULL,'NONE',NULL,NULL,0,NULL,NULL,NULL,0,NULL,NULL,'08:00:00','22:00:00','SERVER',31,'CUSTOM',0,1,1.00,1,1,NULL,NULL,NULL,'NONE',NULL,0,1,1,'PUBLIC_MARKETPLACE','2025-12-05 08:58:06','2025-12-05 08:58:06',NULL),(5,'Grid Trading','Grid/martingale strategy with layering and hedging. Suitable for range-bound markets.',NULL,NULL,1,'[\"grid\",\"martingale\",\"layering\",\"hedging\"]','FIXED',0.01,NULL,5,5,'NONE',NULL,NULL,NULL,NULL,'PIPS',50,NULL,'SINGLE',1,1.00,100.00,0,NULL,NULL,0,NULL,NULL,0,0,NULL,NULL,0,'STEP_PIPS',NULL,NULL,NULL,NULL,NULL,1,5,15,'MULTIPLY',2.00,5.00,1,2.00,20,1.00,0,NULL,NULL,NULL,NULL,'SERVER',31,'CUSTOM',0,1,3.00,1,0,NULL,NULL,NULL,'NONE',NULL,0,1,1,'PUBLIC_MARKETPLACE','2025-12-05 08:58:06','2025-12-05 08:58:06',NULL),(6,'Breakout Trader','Breakout/volatility strategy with structure-based SL and Chandelier trailing stop.',NULL,NULL,1,'[\"breakout\",\"volatility\",\"structure\",\"chandelier\"]','RISK_PERCENT',NULL,1.50,2,1,'NONE',NULL,NULL,NULL,NULL,'STRUCTURE',NULL,NULL,'MULTI',1,2.00,40.00,1,4.00,40.00,1,6.00,20.00,0,1,1.00,0,1,'CHANDELIER',1.00,NULL,22,3.00,60,0,3,NULL,'NONE',NULL,NULL,0,NULL,NULL,NULL,0,NULL,NULL,'08:00:00','17:00:00','SERVER',31,'CUSTOM',1,0,NULL,NULL,0,NULL,NULL,NULL,'NONE',NULL,0,1,1,'PUBLIC_MARKETPLACE','2025-12-05 08:58:06','2025-12-05 08:58:06',NULL);
/*!40000 ALTER TABLE `sp_trading_presets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_transactions`
--

DROP TABLE IF EXISTS `sp_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=201 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_transactions`
--

LOCK TABLES `sp_transactions` WRITE;
/*!40000 ALTER TABLE `sp_transactions` DISABLE KEYS */;
INSERT INTO `sp_transactions` VALUES (1,'TRX6932FE8293CA2',1,1551.00000000,4.00000000,'Payment refund','refund','2025-11-18 00:47:14','2025-11-18 00:47:14'),(2,'TRX6932FE8294F2D',4,955.00000000,9.00000000,'Payment refund','refund','2025-09-28 01:47:14','2025-09-28 01:47:14'),(3,'TRX6932FE82966FF',4,655.00000000,25.00000000,'Withdrawal request','withdraw','2025-11-06 06:47:14','2025-11-06 06:47:14'),(4,'TRX6932FE8297B2E',2,1036.00000000,42.00000000,'Wallet deposit transaction','deposit','2025-10-24 05:47:14','2025-10-24 05:47:14'),(5,'TRX6932FE829947B',2,1161.00000000,4.00000000,'Payment refund','refund','2025-09-09 23:47:14','2025-09-09 23:47:14'),(6,'TRX6932FE829A839',2,567.00000000,11.00000000,'Welcome bonus credited','bonus','2025-09-06 01:47:14','2025-09-06 01:47:14'),(7,'TRX6932FE829B954',5,558.00000000,13.00000000,'Welcome bonus credited','bonus','2025-10-05 10:47:14','2025-10-05 10:47:14'),(8,'TRX6932FE829CAA5',2,1979.00000000,26.00000000,'Withdrawal request','withdraw','2025-10-27 20:47:14','2025-10-27 20:47:14'),(9,'TRX6932FE829DA33',4,1248.00000000,8.00000000,'Welcome bonus credited','bonus','2025-10-02 08:47:14','2025-10-02 08:47:14'),(10,'TRX6932FE829E6FA',2,1387.00000000,1.00000000,'Plan subscription payment','subscription','2025-09-27 15:47:14','2025-09-27 15:47:14'),(11,'TRX6932FE829FA62',1,814.00000000,21.00000000,'Plan subscription payment','subscription','2025-11-03 02:47:14','2025-11-03 02:47:14'),(12,'TRX6932FE82A0992',4,781.00000000,28.00000000,'Withdrawal request','withdraw','2025-10-23 16:47:14','2025-10-23 16:47:14'),(13,'TRX6932FE82A1BB6',1,649.00000000,22.00000000,'Welcome bonus credited','bonus','2025-10-28 20:47:14','2025-10-28 20:47:14'),(14,'TRX6932FE82A2C0E',4,847.00000000,13.00000000,'Referral commission earned','referral_commission','2025-10-26 02:47:14','2025-10-26 02:47:14'),(15,'TRX6932FE82A3C2B',1,204.00000000,41.00000000,'Withdrawal request','withdraw','2025-11-17 14:47:14','2025-11-17 14:47:14'),(16,'TRX6932FE82A4CBA',2,644.00000000,7.00000000,'Referral commission earned','referral_commission','2025-10-17 17:47:14','2025-10-17 17:47:14'),(17,'TRX6932FE82A5EAA',5,1020.00000000,1.00000000,'Payment refund','refund','2025-11-22 17:47:14','2025-11-22 17:47:14'),(18,'TRX6932FE82A6F8E',3,1574.00000000,4.00000000,'Welcome bonus credited','bonus','2025-11-22 23:47:14','2025-11-22 23:47:14'),(19,'TRX6932FE82A8339',1,602.00000000,1.00000000,'Wallet deposit transaction','deposit','2025-10-19 06:47:14','2025-10-19 06:47:14'),(20,'TRX6932FE82A9501',1,1858.00000000,26.00000000,'Welcome bonus credited','bonus','2025-10-01 03:47:14','2025-10-01 03:47:14'),(21,'TRX6932FE82AA86B',4,55.00000000,6.00000000,'Plan subscription payment','subscription','2025-10-10 20:47:14','2025-10-10 20:47:14'),(22,'TRX6932FE82ABE44',3,1793.00000000,45.00000000,'Withdrawal request','withdraw','2025-11-01 19:47:14','2025-11-01 19:47:14'),(23,'TRX6932FE82AD2B3',3,1414.00000000,17.00000000,'Plan subscription payment','subscription','2025-09-25 06:47:14','2025-09-25 06:47:14'),(24,'TRX6932FE82AE6CF',2,1936.00000000,48.00000000,'Wallet deposit transaction','deposit','2025-09-22 12:47:14','2025-09-22 12:47:14'),(25,'TRX6932FE82AF73B',4,1463.00000000,7.00000000,'Payment refund','refund','2025-09-15 11:47:14','2025-09-15 11:47:14'),(26,'TRX6932FE82B0DDE',4,1604.00000000,14.00000000,'Payment refund','refund','2025-11-04 16:47:14','2025-11-04 16:47:14'),(27,'TRX6932FE82B1F0A',3,97.00000000,24.00000000,'Wallet deposit transaction','deposit','2025-11-01 23:47:14','2025-11-01 23:47:14'),(28,'TRX6932FE82B2FE7',3,709.00000000,19.00000000,'Welcome bonus credited','bonus','2025-11-27 14:47:14','2025-11-27 14:47:14'),(29,'TRX6932FE82B4979',3,1285.00000000,24.00000000,'Plan subscription payment','subscription','2025-10-20 02:47:14','2025-10-20 02:47:14'),(30,'TRX6932FE82B614A',4,316.00000000,19.00000000,'Welcome bonus credited','bonus','2025-11-22 00:47:14','2025-11-22 00:47:14'),(31,'TRX6932FE82B8A9F',2,1135.00000000,39.00000000,'Withdrawal request','withdraw','2025-12-01 10:47:14','2025-12-01 10:47:14'),(32,'TRX6932FE82B9CC9',2,754.00000000,15.00000000,'Referral commission earned','referral_commission','2025-11-19 01:47:14','2025-11-19 01:47:14'),(33,'TRX6932FE82BAC4C',2,809.00000000,35.00000000,'Payment refund','refund','2025-10-27 20:47:14','2025-10-27 20:47:14'),(34,'TRX6932FE82BBDB7',1,1696.00000000,47.00000000,'Payment refund','refund','2025-10-08 02:47:14','2025-10-08 02:47:14'),(35,'TRX6932FE82BC7EA',5,1353.00000000,24.00000000,'Withdrawal request','withdraw','2025-11-23 10:47:14','2025-11-23 10:47:14'),(36,'TRX6932FE82BDB40',3,1641.00000000,36.00000000,'Withdrawal request','withdraw','2025-10-20 08:47:14','2025-10-20 08:47:14'),(37,'TRX6932FE82BEDB5',3,28.00000000,45.00000000,'Welcome bonus credited','bonus','2025-11-20 07:47:14','2025-11-20 07:47:14'),(38,'TRX6932FE82BFFAA',4,744.00000000,50.00000000,'Plan subscription payment','subscription','2025-10-19 06:47:14','2025-10-19 06:47:14'),(39,'TRX6932FE82C0E44',2,1897.00000000,7.00000000,'Plan subscription payment','subscription','2025-10-30 16:47:14','2025-10-30 16:47:14'),(40,'TRX6932FE82C1E73',5,344.00000000,36.00000000,'Wallet deposit transaction','deposit','2025-10-22 09:47:14','2025-10-22 09:47:14'),(41,'TRX6932FED74569F',3,307.00000000,43.00000000,'Wallet deposit transaction','deposit','2025-10-15 13:48:39','2025-10-15 13:48:39'),(42,'TRX6932FED747414',1,357.00000000,35.00000000,'Welcome bonus credited','bonus','2025-11-14 02:48:39','2025-11-14 02:48:39'),(43,'TRX6932FED748AE5',1,400.00000000,11.00000000,'Welcome bonus credited','bonus','2025-09-24 14:48:39','2025-09-24 14:48:39'),(44,'TRX6932FED74A015',4,970.00000000,16.00000000,'Plan subscription payment','subscription','2025-10-31 07:48:39','2025-10-31 07:48:39'),(45,'TRX6932FED74B660',4,1405.00000000,11.00000000,'Referral commission earned','referral_commission','2025-09-27 18:48:39','2025-09-27 18:48:39'),(46,'TRX6932FED74CDFD',2,1471.00000000,36.00000000,'Payment refund','refund','2025-09-11 11:48:39','2025-09-11 11:48:39'),(47,'TRX6932FED74E7EE',2,192.00000000,3.00000000,'Withdrawal request','withdraw','2025-10-10 10:48:39','2025-10-10 10:48:39'),(48,'TRX6932FED74FC4D',3,576.00000000,32.00000000,'Welcome bonus credited','bonus','2025-11-10 11:48:39','2025-11-10 11:48:39'),(49,'TRX6932FED751094',4,1669.00000000,8.00000000,'Payment refund','refund','2025-10-02 05:48:39','2025-10-02 05:48:39'),(50,'TRX6932FED752368',3,896.00000000,21.00000000,'Payment refund','refund','2025-10-09 01:48:39','2025-10-09 01:48:39'),(51,'TRX6932FED753942',1,1193.00000000,2.00000000,'Withdrawal request','withdraw','2025-10-09 05:48:39','2025-10-09 05:48:39'),(52,'TRX6932FED754D47',2,1369.00000000,0.00000000,'Payment refund','refund','2025-11-28 09:48:39','2025-11-28 09:48:39'),(53,'TRX6932FED756513',1,318.00000000,35.00000000,'Welcome bonus credited','bonus','2025-12-04 06:48:39','2025-12-04 06:48:39'),(54,'TRX6932FED757FD6',4,302.00000000,24.00000000,'Payment refund','refund','2025-11-07 23:48:39','2025-11-07 23:48:39'),(55,'TRX6932FED75B585',4,63.00000000,45.00000000,'Referral commission earned','referral_commission','2025-10-18 04:48:39','2025-10-18 04:48:39'),(56,'TRX6932FED75D052',4,748.00000000,33.00000000,'Payment refund','refund','2025-09-17 12:48:39','2025-09-17 12:48:39'),(57,'TRX6932FED75EF6B',3,1237.00000000,8.00000000,'Welcome bonus credited','bonus','2025-10-21 20:48:39','2025-10-21 20:48:39'),(58,'TRX6932FED760BAD',4,1550.00000000,31.00000000,'Welcome bonus credited','bonus','2025-10-19 06:48:39','2025-10-19 06:48:39'),(59,'TRX6932FED761E2F',5,598.00000000,18.00000000,'Payment refund','refund','2025-10-07 14:48:39','2025-10-07 14:48:39'),(60,'TRX6932FED7633F0',2,791.00000000,21.00000000,'Wallet deposit transaction','deposit','2025-10-23 21:48:39','2025-10-23 21:48:39'),(61,'TRX6932FED764469',2,978.00000000,28.00000000,'Payment refund','refund','2025-10-02 18:48:39','2025-10-02 18:48:39'),(62,'TRX6932FED7657E0',2,1118.00000000,27.00000000,'Payment refund','refund','2025-10-20 16:48:39','2025-10-20 16:48:39'),(63,'TRX6932FED766A28',5,1677.00000000,45.00000000,'Withdrawal request','withdraw','2025-10-25 12:48:39','2025-10-25 12:48:39'),(64,'TRX6932FED768219',2,68.00000000,48.00000000,'Wallet deposit transaction','deposit','2025-11-05 03:48:39','2025-11-05 03:48:39'),(65,'TRX6932FED769524',4,930.00000000,50.00000000,'Welcome bonus credited','bonus','2025-11-05 11:48:39','2025-11-05 11:48:39'),(66,'TRX6932FED76AD6A',2,572.00000000,25.00000000,'Wallet deposit transaction','deposit','2025-11-12 12:48:39','2025-11-12 12:48:39'),(67,'TRX6932FED76BEA8',2,256.00000000,22.00000000,'Payment refund','refund','2025-09-10 21:48:39','2025-09-10 21:48:39'),(68,'TRX6932FED76D4B1',2,1052.00000000,48.00000000,'Withdrawal request','withdraw','2025-11-04 00:48:39','2025-11-04 00:48:39'),(69,'TRX6932FED76E85E',5,1530.00000000,1.00000000,'Welcome bonus credited','bonus','2025-10-10 23:48:39','2025-10-10 23:48:39'),(70,'TRX6932FED76FD8C',3,1228.00000000,41.00000000,'Welcome bonus credited','bonus','2025-10-19 20:48:39','2025-10-19 20:48:39'),(71,'TRX6932FED771266',1,736.00000000,44.00000000,'Withdrawal request','withdraw','2025-11-06 19:48:39','2025-11-06 19:48:39'),(72,'TRX6932FED772B91',2,732.00000000,49.00000000,'Referral commission earned','referral_commission','2025-12-01 20:48:39','2025-12-01 20:48:39'),(73,'TRX6932FED773CD6',2,1237.00000000,1.00000000,'Withdrawal request','withdraw','2025-11-27 09:48:39','2025-11-27 09:48:39'),(74,'TRX6932FED77661B',2,177.00000000,36.00000000,'Welcome bonus credited','bonus','2025-09-23 04:48:39','2025-09-23 04:48:39'),(75,'TRX6932FED7784AA',4,728.00000000,44.00000000,'Payment refund','refund','2025-09-15 04:48:39','2025-09-15 04:48:39'),(76,'TRX6932FED77A08E',5,304.00000000,49.00000000,'Withdrawal request','withdraw','2025-10-30 15:48:39','2025-10-30 15:48:39'),(77,'TRX6932FED77BD1E',4,539.00000000,37.00000000,'Plan subscription payment','subscription','2025-10-11 20:48:39','2025-10-11 20:48:39'),(78,'TRX6932FED77D220',2,1911.00000000,49.00000000,'Referral commission earned','referral_commission','2025-11-16 17:48:39','2025-11-16 17:48:39'),(79,'TRX6932FED77FC12',1,827.00000000,14.00000000,'Referral commission earned','referral_commission','2025-09-10 15:48:39','2025-09-10 15:48:39'),(80,'TRX6932FED7813E4',1,1870.00000000,46.00000000,'Referral commission earned','referral_commission','2025-12-05 03:48:39','2025-12-05 03:48:39'),(81,'TRX6933008F3DF0F',5,1394.00000000,23.00000000,'Withdrawal request','withdraw','2025-11-11 09:55:59','2025-11-11 09:55:59'),(82,'TRX6933008F3F2A4',4,1052.00000000,29.00000000,'Payment refund','refund','2025-09-22 18:55:59','2025-09-22 18:55:59'),(83,'TRX6933008F4244D',4,1371.00000000,9.00000000,'Welcome bonus credited','bonus','2025-10-10 03:55:59','2025-10-10 03:55:59'),(84,'TRX6933008F4363F',4,1120.00000000,20.00000000,'Wallet deposit transaction','deposit','2025-11-17 04:55:59','2025-11-17 04:55:59'),(85,'TRX6933008F44999',1,299.00000000,5.00000000,'Welcome bonus credited','bonus','2025-09-16 06:55:59','2025-09-16 06:55:59'),(86,'TRX6933008F45E3C',4,692.00000000,36.00000000,'Payment refund','refund','2025-10-02 20:55:59','2025-10-02 20:55:59'),(87,'TRX6933008F47428',2,1042.00000000,36.00000000,'Plan subscription payment','subscription','2025-09-17 23:55:59','2025-09-17 23:55:59'),(88,'TRX6933008F487AB',1,10.00000000,14.00000000,'Wallet deposit transaction','deposit','2025-10-02 03:55:59','2025-10-02 03:55:59'),(89,'TRX6933008F4B341',2,703.00000000,27.00000000,'Referral commission earned','referral_commission','2025-11-04 04:55:59','2025-11-04 04:55:59'),(90,'TRX6933008F4DB53',4,1540.00000000,3.00000000,'Welcome bonus credited','bonus','2025-09-26 06:55:59','2025-09-26 06:55:59'),(91,'TRX6933008F4F08F',4,17.00000000,7.00000000,'Payment refund','refund','2025-11-30 23:55:59','2025-11-30 23:55:59'),(92,'TRX6933008F503C0',5,1017.00000000,9.00000000,'Wallet deposit transaction','deposit','2025-10-26 08:55:59','2025-10-26 08:55:59'),(93,'TRX6933008F5214C',3,1263.00000000,20.00000000,'Payment refund','refund','2025-09-09 16:55:59','2025-09-09 16:55:59'),(94,'TRX6933008F53901',4,132.00000000,6.00000000,'Plan subscription payment','subscription','2025-11-18 22:55:59','2025-11-18 22:55:59'),(95,'TRX6933008F5535D',2,1229.00000000,5.00000000,'Withdrawal request','withdraw','2025-10-01 15:55:59','2025-10-01 15:55:59'),(96,'TRX6933008F56E9D',2,1753.00000000,38.00000000,'Withdrawal request','withdraw','2025-09-07 05:55:59','2025-09-07 05:55:59'),(97,'TRX6933008F582A1',2,1852.00000000,40.00000000,'Welcome bonus credited','bonus','2025-10-10 20:55:59','2025-10-10 20:55:59'),(98,'TRX6933008F599D9',4,163.00000000,28.00000000,'Plan subscription payment','subscription','2025-09-29 18:55:59','2025-09-29 18:55:59'),(99,'TRX6933008F5A6E5',3,1789.00000000,44.00000000,'Welcome bonus credited','bonus','2025-11-01 00:55:59','2025-11-01 00:55:59'),(100,'TRX6933008F5BBED',2,1990.00000000,19.00000000,'Payment refund','refund','2025-09-13 11:55:59','2025-09-13 11:55:59'),(101,'TRX6933008F5D2C4',3,1534.00000000,24.00000000,'Wallet deposit transaction','deposit','2025-11-14 19:55:59','2025-11-14 19:55:59'),(102,'TRX6933008F5F03D',5,1034.00000000,50.00000000,'Plan subscription payment','subscription','2025-09-30 01:55:59','2025-09-30 01:55:59'),(103,'TRX6933008F606F1',2,686.00000000,18.00000000,'Payment refund','refund','2025-09-20 10:55:59','2025-09-20 10:55:59'),(104,'TRX6933008F61A0D',2,835.00000000,31.00000000,'Payment refund','refund','2025-11-30 14:55:59','2025-11-30 14:55:59'),(105,'TRX6933008F62F66',2,1089.00000000,11.00000000,'Referral commission earned','referral_commission','2025-10-23 00:55:59','2025-10-23 00:55:59'),(106,'TRX6933008F646CE',3,929.00000000,36.00000000,'Plan subscription payment','subscription','2025-10-21 12:55:59','2025-10-21 12:55:59'),(107,'TRX6933008F65929',2,1829.00000000,38.00000000,'Withdrawal request','withdraw','2025-09-16 20:55:59','2025-09-16 20:55:59'),(108,'TRX6933008F66E87',4,105.00000000,7.00000000,'Wallet deposit transaction','deposit','2025-10-07 08:55:59','2025-10-07 08:55:59'),(109,'TRX6933008F68527',2,1583.00000000,14.00000000,'Withdrawal request','withdraw','2025-10-05 10:55:59','2025-10-05 10:55:59'),(110,'TRX6933008F69F95',1,475.00000000,44.00000000,'Welcome bonus credited','bonus','2025-09-22 11:55:59','2025-09-22 11:55:59'),(111,'TRX6933008F6B546',3,1610.00000000,27.00000000,'Plan subscription payment','subscription','2025-11-21 02:55:59','2025-11-21 02:55:59'),(112,'TRX6933008F6CAB1',4,1552.00000000,18.00000000,'Wallet deposit transaction','deposit','2025-12-02 17:55:59','2025-12-02 17:55:59'),(113,'TRX6933008F6F3A5',5,264.00000000,43.00000000,'Payment refund','refund','2025-10-27 15:55:59','2025-10-27 15:55:59'),(114,'TRX6933008F704A5',2,641.00000000,38.00000000,'Plan subscription payment','subscription','2025-09-19 20:55:59','2025-09-19 20:55:59'),(115,'TRX6933008F71A2F',5,878.00000000,35.00000000,'Withdrawal request','withdraw','2025-09-19 03:55:59','2025-09-19 03:55:59'),(116,'TRX6933008F72C70',1,1122.00000000,5.00000000,'Payment refund','refund','2025-10-15 22:55:59','2025-10-15 22:55:59'),(117,'TRX6933008F7407B',2,863.00000000,44.00000000,'Payment refund','refund','2025-10-19 12:55:59','2025-10-19 12:55:59'),(118,'TRX6933008F75607',3,69.00000000,50.00000000,'Payment refund','refund','2025-11-11 21:55:59','2025-11-11 21:55:59'),(119,'TRX6933008F76B83',4,638.00000000,40.00000000,'Withdrawal request','withdraw','2025-10-23 20:55:59','2025-10-23 20:55:59'),(120,'TRX6933008F77F34',5,1085.00000000,4.00000000,'Withdrawal request','withdraw','2025-10-22 12:55:59','2025-10-22 12:55:59'),(121,'TRX6933010D3F497',5,1704.00000000,36.00000000,'Wallet deposit transaction','deposit','2025-10-17 04:58:05','2025-10-17 04:58:05'),(122,'TRX6933010D524C8',1,1522.00000000,23.00000000,'Payment refund','refund','2025-12-01 03:58:05','2025-12-01 03:58:05'),(123,'TRX6933010D5A476',1,1026.00000000,48.00000000,'Withdrawal request','withdraw','2025-10-14 12:58:05','2025-10-14 12:58:05'),(124,'TRX6933010D69EC5',1,115.00000000,31.00000000,'Wallet deposit transaction','deposit','2025-10-11 16:58:05','2025-10-11 16:58:05'),(125,'TRX6933010D73C6D',2,657.00000000,16.00000000,'Welcome bonus credited','bonus','2025-09-27 09:58:05','2025-09-27 09:58:05'),(126,'TRX6933010D835C9',1,701.00000000,18.00000000,'Payment refund','refund','2025-11-20 05:58:05','2025-11-20 05:58:05'),(127,'TRX6933010D99C42',1,367.00000000,25.00000000,'Payment refund','refund','2025-12-01 13:58:05','2025-12-01 13:58:05'),(128,'TRX6933010DA8E2F',1,465.00000000,33.00000000,'Withdrawal request','withdraw','2025-10-19 00:58:05','2025-10-19 00:58:05'),(129,'TRX6933010DADB13',3,1784.00000000,12.00000000,'Plan subscription payment','subscription','2025-11-21 05:58:05','2025-11-21 05:58:05'),(130,'TRX6933010DB41A1',2,493.00000000,20.00000000,'Payment refund','refund','2025-11-11 14:58:05','2025-11-11 14:58:05'),(131,'TRX6933010DD19CA',5,1417.00000000,28.00000000,'Withdrawal request','withdraw','2025-10-12 03:58:05','2025-10-12 03:58:05'),(132,'TRX6933010DE8BFB',1,1216.00000000,22.00000000,'Withdrawal request','withdraw','2025-09-22 07:58:05','2025-09-22 07:58:05'),(133,'TRX6933010E060E6',2,680.00000000,49.00000000,'Welcome bonus credited','bonus','2025-09-13 05:58:06','2025-09-13 05:58:06'),(134,'TRX6933010E1A692',3,446.00000000,0.00000000,'Welcome bonus credited','bonus','2025-10-05 22:58:06','2025-10-05 22:58:06'),(135,'TRX6933010E2C6AF',1,762.00000000,6.00000000,'Withdrawal request','withdraw','2025-11-28 04:58:06','2025-11-28 04:58:06'),(136,'TRX6933010E3DD08',5,1890.00000000,39.00000000,'Payment refund','refund','2025-09-21 21:58:06','2025-09-21 21:58:06'),(137,'TRX6933010E46C57',5,669.00000000,23.00000000,'Referral commission earned','referral_commission','2025-11-12 07:58:06','2025-11-12 07:58:06'),(138,'TRX6933010E5585B',1,1356.00000000,18.00000000,'Wallet deposit transaction','deposit','2025-11-16 15:58:06','2025-11-16 15:58:06'),(139,'TRX6933010E61BB0',1,1517.00000000,31.00000000,'Wallet deposit transaction','deposit','2025-09-15 23:58:06','2025-09-15 23:58:06'),(140,'TRX6933010E6596F',2,1103.00000000,44.00000000,'Payment refund','refund','2025-10-29 01:58:06','2025-10-29 01:58:06'),(141,'TRX6933010E6C3C9',2,965.00000000,33.00000000,'Payment refund','refund','2025-11-28 05:58:06','2025-11-28 05:58:06'),(142,'TRX6933010E72AA4',4,303.00000000,7.00000000,'Plan subscription payment','subscription','2025-10-16 16:58:06','2025-10-16 16:58:06'),(143,'TRX6933010E777BA',2,866.00000000,42.00000000,'Welcome bonus credited','bonus','2025-11-16 20:58:06','2025-11-16 20:58:06'),(144,'TRX6933010E7D4CE',4,690.00000000,15.00000000,'Wallet deposit transaction','deposit','2025-10-04 03:58:06','2025-10-04 03:58:06'),(145,'TRX6933010E82449',4,251.00000000,1.00000000,'Withdrawal request','withdraw','2025-09-05 14:58:06','2025-09-05 14:58:06'),(146,'TRX6933010E8E024',5,718.00000000,30.00000000,'Wallet deposit transaction','deposit','2025-12-04 12:58:06','2025-12-04 12:58:06'),(147,'TRX6933010E988C2',3,1495.00000000,11.00000000,'Referral commission earned','referral_commission','2025-11-20 02:58:06','2025-11-20 02:58:06'),(148,'TRX6933010E9C941',3,301.00000000,35.00000000,'Referral commission earned','referral_commission','2025-10-31 12:58:06','2025-10-31 12:58:06'),(149,'TRX6933010EA1712',2,797.00000000,13.00000000,'Plan subscription payment','subscription','2025-12-02 04:58:06','2025-12-02 04:58:06'),(150,'TRX6933010EB17A3',4,1812.00000000,13.00000000,'Welcome bonus credited','bonus','2025-10-27 23:58:06','2025-10-27 23:58:06'),(151,'TRX6933010EC4C3D',5,31.00000000,3.00000000,'Welcome bonus credited','bonus','2025-09-28 11:58:06','2025-09-28 11:58:06'),(152,'TRX6933010ED0052',1,760.00000000,1.00000000,'Welcome bonus credited','bonus','2025-09-18 20:58:06','2025-09-18 20:58:06'),(153,'TRX6933010EE340D',5,570.00000000,33.00000000,'Payment refund','refund','2025-11-19 05:58:06','2025-11-19 05:58:06'),(154,'TRX6933010EF03A7',4,1566.00000000,6.00000000,'Referral commission earned','referral_commission','2025-10-08 16:58:06','2025-10-08 16:58:06'),(155,'TRX6933010F0FBAE',3,1362.00000000,22.00000000,'Payment refund','refund','2025-09-28 09:58:07','2025-09-28 09:58:07'),(156,'TRX6933010F1D8E1',3,1164.00000000,31.00000000,'Referral commission earned','referral_commission','2025-11-27 16:58:07','2025-11-27 16:58:07'),(157,'TRX6933010F2B9DF',5,25.00000000,10.00000000,'Wallet deposit transaction','deposit','2025-10-03 12:58:07','2025-10-03 12:58:07'),(158,'TRX6933010F4AFF3',1,1693.00000000,14.00000000,'Referral commission earned','referral_commission','2025-10-13 15:58:07','2025-10-13 15:58:07'),(159,'TRX6933010F585F0',2,733.00000000,4.00000000,'Withdrawal request','withdraw','2025-09-07 02:58:07','2025-09-07 02:58:07'),(160,'TRX6933010F6982E',5,314.00000000,20.00000000,'Withdrawal request','withdraw','2025-11-17 13:58:07','2025-11-17 13:58:07'),(161,'TRX693301131E9DE',2,1886.00000000,3.00000000,'Plan subscription payment','subscription','2025-11-07 12:58:11','2025-11-07 12:58:11'),(162,'TRX6933011324691',2,116.00000000,3.00000000,'Withdrawal request','withdraw','2025-10-12 00:58:11','2025-10-12 00:58:11'),(163,'TRX69330113330A8',3,618.00000000,47.00000000,'Plan subscription payment','subscription','2025-09-13 05:58:11','2025-09-13 05:58:11'),(164,'TRX693301133F010',3,1706.00000000,33.00000000,'Wallet deposit transaction','deposit','2025-09-21 02:58:11','2025-09-21 02:58:11'),(165,'TRX69330113451E5',4,1526.00000000,12.00000000,'Referral commission earned','referral_commission','2025-09-13 08:58:11','2025-09-13 08:58:11'),(166,'TRX6933011353BF6',1,904.00000000,4.00000000,'Referral commission earned','referral_commission','2025-10-31 21:58:11','2025-10-31 21:58:11'),(167,'TRX693301136A963',5,655.00000000,18.00000000,'Withdrawal request','withdraw','2025-09-18 18:58:11','2025-09-18 18:58:11'),(168,'TRX693301136F6D5',3,664.00000000,0.00000000,'Payment refund','refund','2025-10-11 22:58:11','2025-10-11 22:58:11'),(169,'TRX693301137D4DB',5,960.00000000,8.00000000,'Plan subscription payment','subscription','2025-10-12 17:58:11','2025-10-12 17:58:11'),(170,'TRX6933011387066',4,969.00000000,41.00000000,'Payment refund','refund','2025-11-21 18:58:11','2025-11-21 18:58:11'),(171,'TRX6933011394AE3',2,827.00000000,15.00000000,'Referral commission earned','referral_commission','2025-11-06 12:58:11','2025-11-06 12:58:11'),(172,'TRX6933011398B47',3,1688.00000000,29.00000000,'Welcome bonus credited','bonus','2025-11-22 09:58:11','2025-11-22 09:58:11'),(173,'TRX693301139C09E',5,421.00000000,35.00000000,'Welcome bonus credited','bonus','2025-10-15 19:58:11','2025-10-15 19:58:11'),(174,'TRX693301139EF61',3,862.00000000,33.00000000,'Referral commission earned','referral_commission','2025-10-06 21:58:11','2025-10-06 21:58:11'),(175,'TRX69330113A2ED0',2,1207.00000000,36.00000000,'Wallet deposit transaction','deposit','2025-09-06 14:58:11','2025-09-06 14:58:11'),(176,'TRX69330113AB9B3',5,1333.00000000,47.00000000,'Plan subscription payment','subscription','2025-09-27 07:58:11','2025-09-27 07:58:11'),(177,'TRX69330113B903B',5,175.00000000,28.00000000,'Referral commission earned','referral_commission','2025-10-27 00:58:11','2025-10-27 00:58:11'),(178,'TRX69330113BEF44',4,1853.00000000,41.00000000,'Withdrawal request','withdraw','2025-11-18 10:58:11','2025-11-18 10:58:11'),(179,'TRX69330113C7D2B',2,947.00000000,2.00000000,'Payment refund','refund','2025-11-02 04:58:11','2025-11-02 04:58:11'),(180,'TRX69330113D03F3',1,178.00000000,27.00000000,'Wallet deposit transaction','deposit','2025-12-01 05:58:11','2025-12-01 05:58:11'),(181,'TRX69330113DB217',2,892.00000000,40.00000000,'Wallet deposit transaction','deposit','2025-10-15 11:58:11','2025-10-15 11:58:11'),(182,'TRX69330113E047B',5,557.00000000,46.00000000,'Payment refund','refund','2025-09-21 21:58:11','2025-09-21 21:58:11'),(183,'TRX69330113E9C5B',3,1030.00000000,44.00000000,'Referral commission earned','referral_commission','2025-09-20 17:58:11','2025-09-20 17:58:11'),(184,'TRX69330113F1CAE',1,1052.00000000,44.00000000,'Withdrawal request','withdraw','2025-10-22 14:58:11','2025-10-22 14:58:11'),(185,'TRX693301140534E',4,1045.00000000,9.00000000,'Welcome bonus credited','bonus','2025-09-18 15:58:12','2025-09-18 15:58:12'),(186,'TRX693301140FBD5',2,1863.00000000,1.00000000,'Wallet deposit transaction','deposit','2025-10-28 07:58:12','2025-10-28 07:58:12'),(187,'TRX693301141EBC8',5,1325.00000000,5.00000000,'Referral commission earned','referral_commission','2025-11-08 09:58:12','2025-11-08 09:58:12'),(188,'TRX6933011430979',5,1163.00000000,23.00000000,'Referral commission earned','referral_commission','2025-09-28 01:58:12','2025-09-28 01:58:12'),(189,'TRX693301143ECC0',4,1812.00000000,5.00000000,'Payment refund','refund','2025-11-10 05:58:12','2025-11-10 05:58:12'),(190,'TRX693301144769C',2,547.00000000,43.00000000,'Payment refund','refund','2025-10-20 02:58:12','2025-10-20 02:58:12'),(191,'TRX693301144C493',2,491.00000000,4.00000000,'Referral commission earned','referral_commission','2025-12-01 14:58:12','2025-12-01 14:58:12'),(192,'TRX69330114538EA',5,1366.00000000,50.00000000,'Wallet deposit transaction','deposit','2025-10-03 14:58:12','2025-10-03 14:58:12'),(193,'TRX6933011458203',1,576.00000000,12.00000000,'Wallet deposit transaction','deposit','2025-09-25 17:58:12','2025-09-25 17:58:12'),(194,'TRX693301145D1AB',2,721.00000000,9.00000000,'Payment refund','refund','2025-10-29 16:58:12','2025-10-29 16:58:12'),(195,'TRX69330114642BB',3,662.00000000,42.00000000,'Referral commission earned','referral_commission','2025-11-08 00:58:12','2025-11-08 00:58:12'),(196,'TRX693301146A0F7',4,794.00000000,13.00000000,'Welcome bonus credited','bonus','2025-11-17 14:58:12','2025-11-17 14:58:12'),(197,'TRX6933011473904',3,1439.00000000,41.00000000,'Withdrawal request','withdraw','2025-11-15 15:58:12','2025-11-15 15:58:12'),(198,'TRX6933011485928',3,223.00000000,31.00000000,'Referral commission earned','referral_commission','2025-10-12 07:58:12','2025-10-12 07:58:12'),(199,'TRX693301148F891',2,1991.00000000,35.00000000,'Withdrawal request','withdraw','2025-10-05 23:58:12','2025-10-05 23:58:12'),(200,'TRX6933011493C86',5,1712.00000000,39.00000000,'Payment refund','refund','2025-09-12 20:58:12','2025-09-12 20:58:12');
/*!40000 ALTER TABLE `sp_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_translation_settings`
--

DROP TABLE IF EXISTS `sp_translation_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_user_signals` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `signal_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sp_user_signals_user_id_signal_id_unique` (`user_id`,`signal_id`),
  UNIQUE KEY `user_signals_user_id_signal_id_unique` (`user_id`,`signal_id`)
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
/*!50503 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_users`
--

LOCK TABLES `sp_users` WRITE;
/*!40000 ALTER TABLE `sp_users` DISABLE KEYS */;
INSERT INTO `sp_users` VALUES (1,1,0,'investor_demo','investor@demo.com','+1234567890',NULL,'$2y$10$1Fsq7Rbo9VVnbiMwmLbtfe6S3W2YtjnU2uWXvuuZj6cxwJZliYFK6',10000.00000000,NULL,1,1,1,NULL,NULL,'0000-00-00 00:00:00',NULL,NULL,NULL,1,NULL,'2025-12-05 08:41:40','2025-12-05 08:58:06',NULL,NULL),(2,1,0,'trader_pro','trader@demo.com','+1234567891',NULL,'$2y$10$wHHrFyzmMqx0v8EHSYSW/.9fZYjgIAFzbIyOParbyDjW9UfEVvBVm',5000.00000000,NULL,1,1,1,NULL,NULL,'0000-00-00 00:00:00',NULL,NULL,NULL,1,NULL,'2025-12-05 08:41:40','2025-12-05 08:58:06',NULL,NULL),(3,1,0,'premium_user','premium@demo.com','+1234567892',NULL,'$2y$10$J38ytZDjhK5ryl1JbykRvu//sDuE51yJHDHjEwStQCgw2JREvldYW',2500.00000000,NULL,1,0,1,NULL,NULL,'0000-00-00 00:00:00',NULL,NULL,NULL,1,NULL,'2025-12-05 08:41:40','2025-12-05 08:58:06',NULL,NULL),(4,1,0,'basic_user','basic@demo.com','+1234567893',NULL,'$2y$10$QdssWe7zmHOxpnGR6qFOzuCkp/mwS7RhDVY3AFIOgqz3V4i2476dW',1000.00000000,NULL,1,0,0,NULL,NULL,'0000-00-00 00:00:00',NULL,NULL,NULL,1,NULL,'2025-12-05 08:41:40','2025-12-05 08:58:06',NULL,NULL),(5,1,0,'new_user','new@demo.com','+1234567894',NULL,'$2y$10$vF/TFPtq.5JFRKlM30nTaOB63P3flYhnBh5ZUNsgAoSLm/NDlkwsy',500.00000000,NULL,0,0,0,NULL,NULL,'0000-00-00 00:00:00',NULL,NULL,NULL,1,NULL,'2025-12-05 08:41:41','2025-12-05 08:58:06',NULL,NULL),(6,NULL,0,'user1','user1@user.com','812161652',NULL,'$2y$10$nNrkAw3/YOpbu034Zidusub7BMqAbgTXAaFzEgJW5LNeGgNBGG4cC',0.00000000,NULL,0,0,0,NULL,NULL,'0000-00-00 00:00:00',NULL,NULL,NULL,1,NULL,'2025-12-05 21:20:35','2025-12-05 21:20:35',NULL,NULL);
/*!40000 ALTER TABLE `sp_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_withdraw_gateways`
--

DROP TABLE IF EXISTS `sp_withdraw_gateways`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=121 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_withdraws`
--

LOCK TABLES `sp_withdraws` WRITE;
/*!40000 ALTER TABLE `sp_withdraws` DISABLE KEYS */;
INSERT INTO `sp_withdraws` VALUES (1,5,1,'WD6932FE2963315',505.00000000,1.00000000,504.00000000,NULL,'Demo rejection reason',2,'2025-11-20 14:45:45','2025-11-20 14:45:45'),(2,4,1,'WD6932FE2981D00',855.00000000,1.00000000,854.00000000,NULL,NULL,0,'2025-11-20 11:45:45','2025-11-20 11:45:45'),(3,2,1,'WD6932FE29A1255',149.00000000,1.00000000,148.00000000,NULL,NULL,0,'2025-11-19 00:45:45','2025-11-19 00:45:45'),(4,1,1,'WD6932FE29BEA51',77.00000000,1.00000000,76.00000000,NULL,'Demo rejection reason',2,'2025-11-30 17:45:45','2025-11-30 17:45:45'),(5,2,1,'WD6932FE29DB536',919.00000000,1.00000000,918.00000000,'\"{\\\"note\\\":\\\"Demo withdrawal processed successfully\\\"}\"',NULL,1,'2025-11-11 14:45:45','2025-11-11 14:45:45'),(6,5,1,'WD6932FE2A1C723',310.00000000,1.00000000,309.00000000,NULL,NULL,0,'2025-11-11 01:45:46','2025-11-11 01:45:46'),(7,5,1,'WD6932FE2A39DB2',186.00000000,1.00000000,185.00000000,NULL,NULL,0,'2025-12-02 02:45:46','2025-12-02 02:45:46'),(8,2,1,'WD6932FE2A58FF2',497.00000000,1.00000000,496.00000000,'\"{\\\"note\\\":\\\"Demo withdrawal processed successfully\\\"}\"',NULL,1,'2025-10-23 00:45:46','2025-10-23 00:45:46'),(9,3,1,'WD6932FE2A71B2C',875.00000000,1.00000000,874.00000000,NULL,NULL,0,'2025-11-19 19:45:46','2025-11-19 19:45:46'),(10,5,1,'WD6932FE2A8C891',683.00000000,1.00000000,682.00000000,NULL,'Demo rejection reason',2,'2025-11-02 12:45:46','2025-11-02 12:45:46'),(11,3,1,'WD6932FE2AA5824',77.00000000,1.00000000,76.00000000,NULL,'Demo rejection reason',2,'2025-11-28 19:45:46','2025-11-28 19:45:46'),(12,3,1,'WD6932FE2AC068A',251.00000000,1.00000000,250.00000000,NULL,'Demo rejection reason',2,'2025-11-16 01:45:46','2025-11-16 01:45:46'),(13,1,1,'WD6932FE2AEFA99',124.00000000,1.00000000,123.00000000,'\"{\\\"note\\\":\\\"Demo withdrawal processed successfully\\\"}\"',NULL,1,'2025-11-24 13:45:46','2025-11-24 13:45:46'),(14,5,1,'WD6932FE2B0E322',662.00000000,1.00000000,661.00000000,NULL,NULL,0,'2025-10-23 17:45:47','2025-10-23 17:45:47'),(15,2,1,'WD6932FE2B27188',658.00000000,1.00000000,657.00000000,NULL,'Demo rejection reason',2,'2025-11-14 17:45:47','2025-11-14 17:45:47'),(16,1,1,'WD6932FE2B403F1',745.00000000,1.00000000,744.00000000,'\"{\\\"note\\\":\\\"Demo withdrawal processed successfully\\\"}\"',NULL,1,'2025-12-04 08:45:47','2025-12-04 08:45:47'),(17,3,1,'WD6932FE2B84E6A',880.00000000,1.00000000,879.00000000,NULL,'Demo rejection reason',2,'2025-10-27 22:45:47','2025-10-27 22:45:47'),(18,4,1,'WD6932FE2BEB3FD',246.00000000,1.00000000,245.00000000,NULL,'Demo rejection reason',2,'2025-11-23 17:45:47','2025-11-23 17:45:47'),(19,2,1,'WD6932FE2C1EEC2',184.00000000,1.00000000,183.00000000,NULL,'Demo rejection reason',2,'2025-10-29 00:45:48','2025-10-29 00:45:48'),(20,2,1,'WD6932FE2C3B07A',65.00000000,1.00000000,64.00000000,'\"{\\\"note\\\":\\\"Demo withdrawal processed successfully\\\"}\"',NULL,1,'2025-12-01 13:45:48','2025-12-01 13:45:48'),(21,4,1,'WD6932FE827AD7B',453.00000000,1.00000000,452.00000000,NULL,'Demo rejection reason',2,'2025-11-29 20:47:14','2025-11-29 20:47:14'),(22,4,1,'WD6932FE827C724',271.00000000,1.00000000,270.00000000,NULL,'Demo rejection reason',2,'2025-11-27 18:47:14','2025-11-27 18:47:14'),(23,5,1,'WD6932FE827D73A',521.00000000,1.00000000,520.00000000,'\"{\\\"note\\\":\\\"Demo withdrawal processed successfully\\\"}\"',NULL,1,'2025-11-24 14:47:14','2025-11-24 14:47:14'),(24,4,1,'WD6932FE827EAA6',44.00000000,1.00000000,43.00000000,NULL,NULL,0,'2025-11-29 02:47:14','2025-11-29 02:47:14'),(25,4,1,'WD6932FE827FA73',870.00000000,1.00000000,869.00000000,NULL,'Demo rejection reason',2,'2025-11-04 01:47:14','2025-11-04 01:47:14'),(26,4,1,'WD6932FE8281C55',946.00000000,1.00000000,945.00000000,NULL,'Demo rejection reason',2,'2025-11-26 01:47:14','2025-11-26 01:47:14'),(27,4,1,'WD6932FE8282D28',456.00000000,1.00000000,455.00000000,NULL,NULL,0,'2025-11-01 16:47:14','2025-11-01 16:47:14'),(28,2,1,'WD6932FE8283D44',941.00000000,1.00000000,940.00000000,'\"{\\\"note\\\":\\\"Demo withdrawal processed successfully\\\"}\"',NULL,1,'2025-10-22 13:47:14','2025-10-22 13:47:14'),(29,1,1,'WD6932FE82852B4',476.00000000,1.00000000,475.00000000,'\"{\\\"note\\\":\\\"Demo withdrawal processed successfully\\\"}\"',NULL,1,'2025-11-01 21:47:14','2025-11-01 21:47:14'),(30,1,1,'WD6932FE828651F',237.00000000,1.00000000,236.00000000,NULL,NULL,0,'2025-11-09 16:47:14','2025-11-09 16:47:14'),(31,5,1,'WD6932FE828773B',662.00000000,1.00000000,661.00000000,NULL,NULL,0,'2025-12-02 00:47:14','2025-12-02 00:47:14'),(32,2,1,'WD6932FE8288B9B',768.00000000,1.00000000,767.00000000,NULL,'Demo rejection reason',2,'2025-11-04 01:47:14','2025-11-04 01:47:14'),(33,2,1,'WD6932FE8289E41',303.00000000,1.00000000,302.00000000,NULL,'Demo rejection reason',2,'2025-11-10 04:47:14','2025-11-10 04:47:14'),(34,1,1,'WD6932FE828B114',135.00000000,1.00000000,134.00000000,NULL,NULL,0,'2025-10-26 08:47:14','2025-10-26 08:47:14'),(35,3,1,'WD6932FE828C10C',321.00000000,1.00000000,320.00000000,NULL,NULL,0,'2025-12-02 23:47:14','2025-12-02 23:47:14'),(36,2,1,'WD6932FE828D1C7',881.00000000,1.00000000,880.00000000,NULL,'Demo rejection reason',2,'2025-11-15 21:47:14','2025-11-15 21:47:14'),(37,4,1,'WD6932FE828E616',414.00000000,1.00000000,413.00000000,NULL,'Demo rejection reason',2,'2025-11-14 06:47:14','2025-11-14 06:47:14'),(38,1,1,'WD6932FE828F8DE',313.00000000,1.00000000,312.00000000,NULL,'Demo rejection reason',2,'2025-12-01 06:47:14','2025-12-01 06:47:14'),(39,2,1,'WD6932FE82909D3',123.00000000,1.00000000,122.00000000,'\"{\\\"note\\\":\\\"Demo withdrawal processed successfully\\\"}\"',NULL,1,'2025-11-26 13:47:14','2025-11-26 13:47:14'),(40,2,1,'WD6932FE8291C34',288.00000000,1.00000000,287.00000000,NULL,NULL,0,'2025-10-30 14:47:14','2025-10-30 14:47:14'),(41,3,1,'WD6932FED72307D',450.00000000,1.00000000,449.00000000,'\"{\\\"note\\\":\\\"Demo withdrawal processed successfully\\\"}\"',NULL,1,'2025-11-26 07:48:39','2025-11-26 07:48:39'),(42,1,1,'WD6932FED724DD8',31.00000000,1.00000000,30.00000000,'\"{\\\"note\\\":\\\"Demo withdrawal processed successfully\\\"}\"',NULL,1,'2025-11-16 19:48:39','2025-11-16 19:48:39'),(43,2,1,'WD6932FED7264C1',110.00000000,1.00000000,109.00000000,'\"{\\\"note\\\":\\\"Demo withdrawal processed successfully\\\"}\"',NULL,1,'2025-11-11 22:48:39','2025-11-11 22:48:39'),(44,5,1,'WD6932FED727E47',986.00000000,1.00000000,985.00000000,'\"{\\\"note\\\":\\\"Demo withdrawal processed successfully\\\"}\"',NULL,1,'2025-11-13 20:48:39','2025-11-13 20:48:39'),(45,5,1,'WD6932FED729269',627.00000000,1.00000000,626.00000000,NULL,NULL,0,'2025-11-04 14:48:39','2025-11-04 14:48:39'),(46,3,1,'WD6932FED72A4B5',826.00000000,1.00000000,825.00000000,NULL,'Demo rejection reason',2,'2025-11-21 21:48:39','2025-11-21 21:48:39'),(47,1,1,'WD6932FED72BCC5',577.00000000,1.00000000,576.00000000,NULL,NULL,0,'2025-11-11 19:48:39','2025-11-11 19:48:39'),(48,1,1,'WD6932FED72DA4F',556.00000000,1.00000000,555.00000000,'\"{\\\"note\\\":\\\"Demo withdrawal processed successfully\\\"}\"',NULL,1,'2025-11-03 08:48:39','2025-11-03 08:48:39'),(49,1,1,'WD6932FED72F137',612.00000000,1.00000000,611.00000000,NULL,NULL,0,'2025-11-06 05:48:39','2025-11-06 05:48:39'),(50,2,1,'WD6932FED731062',796.00000000,1.00000000,795.00000000,'\"{\\\"note\\\":\\\"Demo withdrawal processed successfully\\\"}\"',NULL,1,'2025-12-04 06:48:39','2025-12-04 06:48:39'),(51,2,1,'WD6932FED732897',887.00000000,1.00000000,886.00000000,NULL,NULL,0,'2025-11-13 14:48:39','2025-11-13 14:48:39'),(52,2,1,'WD6932FED7343F1',817.00000000,1.00000000,816.00000000,'\"{\\\"note\\\":\\\"Demo withdrawal processed successfully\\\"}\"',NULL,1,'2025-11-05 20:48:39','2025-11-05 20:48:39'),(53,2,1,'WD6932FED7361C2',178.00000000,1.00000000,177.00000000,NULL,NULL,0,'2025-11-09 22:48:39','2025-11-09 22:48:39'),(54,2,1,'WD6932FED738A89',378.00000000,1.00000000,377.00000000,'\"{\\\"note\\\":\\\"Demo withdrawal processed successfully\\\"}\"',NULL,1,'2025-12-03 21:48:39','2025-12-03 21:48:39'),(55,1,1,'WD6932FED73B459',96.00000000,1.00000000,95.00000000,NULL,NULL,0,'2025-11-08 05:48:39','2025-11-08 05:48:39'),(56,5,1,'WD6932FED73D5AD',657.00000000,1.00000000,656.00000000,'\"{\\\"note\\\":\\\"Demo withdrawal processed successfully\\\"}\"',NULL,1,'2025-12-03 23:48:39','2025-12-03 23:48:39'),(57,1,1,'WD6932FED73E7E4',357.00000000,1.00000000,356.00000000,NULL,'Demo rejection reason',2,'2025-10-28 14:48:39','2025-10-28 14:48:39'),(58,1,1,'WD6932FED73FB7B',160.00000000,1.00000000,159.00000000,NULL,'Demo rejection reason',2,'2025-10-27 01:48:39','2025-10-27 01:48:39'),(59,3,1,'WD6932FED740F3C',740.00000000,1.00000000,739.00000000,NULL,'Demo rejection reason',2,'2025-11-06 02:48:39','2025-11-06 02:48:39'),(60,5,1,'WD6932FED742447',169.00000000,1.00000000,168.00000000,NULL,'Demo rejection reason',2,'2025-10-22 14:48:39','2025-10-22 14:48:39'),(61,3,1,'WD6933008F19A26',211.00000000,1.00000000,210.00000000,NULL,'Demo rejection reason',2,'2025-11-29 05:55:59','2025-11-29 05:55:59'),(62,4,1,'WD6933008F1B991',595.00000000,1.00000000,594.00000000,NULL,'Demo rejection reason',2,'2025-10-22 21:55:59','2025-10-22 21:55:59'),(63,2,1,'WD6933008F1C851',336.00000000,1.00000000,335.00000000,NULL,NULL,0,'2025-10-26 07:55:59','2025-10-26 07:55:59'),(64,2,1,'WD6933008F1F33A',427.00000000,1.00000000,426.00000000,NULL,NULL,0,'2025-11-03 13:55:59','2025-11-03 13:55:59'),(65,3,1,'WD6933008F2033A',713.00000000,1.00000000,712.00000000,NULL,NULL,0,'2025-12-05 02:55:59','2025-12-05 02:55:59'),(66,5,1,'WD6933008F226BC',760.00000000,1.00000000,759.00000000,'\"{\\\"note\\\":\\\"Demo withdrawal processed successfully\\\"}\"',NULL,1,'2025-11-11 13:55:59','2025-11-11 13:55:59'),(67,3,1,'WD6933008F24E4F',832.00000000,1.00000000,831.00000000,NULL,NULL,0,'2025-11-01 19:55:59','2025-11-01 19:55:59'),(68,2,1,'WD6933008F27190',935.00000000,1.00000000,934.00000000,NULL,NULL,0,'2025-10-31 15:55:59','2025-10-31 15:55:59'),(69,1,1,'WD6933008F28E67',429.00000000,1.00000000,428.00000000,'\"{\\\"note\\\":\\\"Demo withdrawal processed successfully\\\"}\"',NULL,1,'2025-11-13 05:55:59','2025-11-13 05:55:59'),(70,1,1,'WD6933008F2B213',800.00000000,1.00000000,799.00000000,NULL,'Demo rejection reason',2,'2025-11-27 11:55:59','2025-11-27 11:55:59'),(71,3,1,'WD6933008F2CC97',841.00000000,1.00000000,840.00000000,'\"{\\\"note\\\":\\\"Demo withdrawal processed successfully\\\"}\"',NULL,1,'2025-11-18 09:55:59','2025-11-18 09:55:59'),(72,5,1,'WD6933008F2F196',655.00000000,1.00000000,654.00000000,'\"{\\\"note\\\":\\\"Demo withdrawal processed successfully\\\"}\"',NULL,1,'2025-11-30 06:55:59','2025-11-30 06:55:59'),(73,3,1,'WD6933008F31D80',256.00000000,1.00000000,255.00000000,NULL,NULL,0,'2025-11-05 14:55:59','2025-11-05 14:55:59'),(74,2,1,'WD6933008F338CF',935.00000000,1.00000000,934.00000000,'\"{\\\"note\\\":\\\"Demo withdrawal processed successfully\\\"}\"',NULL,1,'2025-10-27 17:55:59','2025-10-27 17:55:59'),(75,5,1,'WD6933008F34E4F',200.00000000,1.00000000,199.00000000,NULL,NULL,0,'2025-11-18 22:55:59','2025-11-18 22:55:59'),(76,1,1,'WD6933008F361A0',885.00000000,1.00000000,884.00000000,NULL,'Demo rejection reason',2,'2025-11-11 21:55:59','2025-11-11 21:55:59'),(77,3,1,'WD6933008F373A7',350.00000000,1.00000000,349.00000000,'\"{\\\"note\\\":\\\"Demo withdrawal processed successfully\\\"}\"',NULL,1,'2025-11-15 04:55:59','2025-11-15 04:55:59'),(78,1,1,'WD6933008F389E6',402.00000000,1.00000000,401.00000000,NULL,NULL,0,'2025-11-13 08:55:59','2025-11-13 08:55:59'),(79,4,1,'WD6933008F3A283',555.00000000,1.00000000,554.00000000,NULL,NULL,0,'2025-11-24 00:55:59','2025-11-24 00:55:59'),(80,2,1,'WD6933008F3B9E7',537.00000000,1.00000000,536.00000000,NULL,NULL,0,'2025-11-01 00:55:59','2025-11-01 00:55:59'),(81,4,1,'WD6933010C2CD47',277.00000000,1.00000000,276.00000000,NULL,NULL,0,'2025-11-16 18:58:04','2025-11-16 18:58:04'),(82,5,1,'WD6933010C3719A',386.00000000,1.00000000,385.00000000,NULL,'Demo rejection reason',2,'2025-10-26 08:58:04','2025-10-26 08:58:04'),(83,5,1,'WD6933010C4E763',111.00000000,1.00000000,110.00000000,NULL,NULL,0,'2025-10-20 22:58:04','2025-10-20 22:58:04'),(84,3,1,'WD6933010C5C8DA',390.00000000,1.00000000,389.00000000,'\"{\\\"note\\\":\\\"Demo withdrawal processed successfully\\\"}\"',NULL,1,'2025-11-02 04:58:04','2025-11-02 04:58:04'),(85,4,1,'WD6933010C5FF09',291.00000000,1.00000000,290.00000000,NULL,'Demo rejection reason',2,'2025-11-08 01:58:04','2025-11-08 01:58:04'),(86,3,1,'WD6933010C69CFE',299.00000000,1.00000000,298.00000000,NULL,'Demo rejection reason',2,'2025-10-30 08:58:04','2025-10-30 08:58:04'),(87,2,1,'WD6933010C6F72B',332.00000000,1.00000000,331.00000000,NULL,NULL,0,'2025-11-15 23:58:04','2025-11-15 23:58:04'),(88,2,1,'WD6933010C77548',687.00000000,1.00000000,686.00000000,'\"{\\\"note\\\":\\\"Demo withdrawal processed successfully\\\"}\"',NULL,1,'2025-11-14 17:58:04','2025-11-14 17:58:04'),(89,1,1,'WD6933010C89376',475.00000000,1.00000000,474.00000000,'\"{\\\"note\\\":\\\"Demo withdrawal processed successfully\\\"}\"',NULL,1,'2025-10-20 10:58:04','2025-10-20 10:58:04'),(90,2,1,'WD6933010CA1E07',609.00000000,1.00000000,608.00000000,NULL,'Demo rejection reason',2,'2025-11-06 02:58:04','2025-11-06 02:58:04'),(91,3,1,'WD6933010CA7929',666.00000000,1.00000000,665.00000000,'\"{\\\"note\\\":\\\"Demo withdrawal processed successfully\\\"}\"',NULL,1,'2025-10-23 02:58:04','2025-10-23 02:58:04'),(92,2,1,'WD6933010CB1224',272.00000000,1.00000000,271.00000000,NULL,'Demo rejection reason',2,'2025-11-08 16:58:04','2025-11-08 16:58:04'),(93,2,1,'WD6933010CBACEA',988.00000000,1.00000000,987.00000000,NULL,NULL,0,'2025-11-04 17:58:04','2025-11-04 17:58:04'),(94,4,1,'WD6933010CC2814',262.00000000,1.00000000,261.00000000,'\"{\\\"note\\\":\\\"Demo withdrawal processed successfully\\\"}\"',NULL,1,'2025-11-30 07:58:04','2025-11-30 07:58:04'),(95,5,1,'WD6933010CD18C3',204.00000000,1.00000000,203.00000000,NULL,NULL,0,'2025-11-02 16:58:04','2025-11-02 16:58:04'),(96,5,1,'WD6933010CE588F',186.00000000,1.00000000,185.00000000,NULL,NULL,0,'2025-11-14 05:58:04','2025-11-14 05:58:04'),(97,4,1,'WD6933010D02670',969.00000000,1.00000000,968.00000000,NULL,NULL,0,'2025-11-10 00:58:05','2025-11-10 00:58:05'),(98,4,1,'WD6933010D1805A',190.00000000,1.00000000,189.00000000,NULL,NULL,0,'2025-10-28 07:58:05','2025-10-28 07:58:05'),(99,1,1,'WD6933010D26BAF',458.00000000,1.00000000,457.00000000,NULL,'Demo rejection reason',2,'2025-11-30 15:58:05','2025-11-30 15:58:05'),(100,1,1,'WD6933010D309C3',90.00000000,1.00000000,89.00000000,NULL,NULL,0,'2025-11-04 10:58:05','2025-11-04 10:58:05'),(101,3,1,'WD69330111E5294',789.00000000,1.00000000,788.00000000,'\"{\\\"note\\\":\\\"Demo withdrawal processed successfully\\\"}\"',NULL,1,'2025-11-27 18:58:09','2025-11-27 18:58:09'),(102,1,1,'WD69330111F1C04',193.00000000,1.00000000,192.00000000,NULL,NULL,0,'2025-10-23 11:58:09','2025-10-23 11:58:09'),(103,2,1,'WD6933011207CA6',315.00000000,1.00000000,314.00000000,NULL,'Demo rejection reason',2,'2025-11-28 18:58:10','2025-11-28 18:58:10'),(104,1,1,'WD6933011214C82',84.00000000,1.00000000,83.00000000,'\"{\\\"note\\\":\\\"Demo withdrawal processed successfully\\\"}\"',NULL,1,'2025-10-27 05:58:10','2025-10-27 05:58:10'),(105,5,1,'WD6933011226F34',846.00000000,1.00000000,845.00000000,'\"{\\\"note\\\":\\\"Demo withdrawal processed successfully\\\"}\"',NULL,1,'2025-11-06 04:58:10','2025-11-06 04:58:10'),(106,1,1,'WD6933011242712',176.00000000,1.00000000,175.00000000,NULL,NULL,0,'2025-11-12 04:58:10','2025-11-12 04:58:10'),(107,3,1,'WD693301126BAF3',898.00000000,1.00000000,897.00000000,NULL,NULL,0,'2025-10-31 19:58:10','2025-10-31 19:58:10'),(108,4,1,'WD693301127614B',95.00000000,1.00000000,94.00000000,NULL,'Demo rejection reason',2,'2025-10-21 00:58:10','2025-10-21 00:58:10'),(109,2,1,'WD693301127CC28',350.00000000,1.00000000,349.00000000,NULL,NULL,0,'2025-11-29 20:58:10','2025-11-29 20:58:10'),(110,4,1,'WD6933011280FFB',576.00000000,1.00000000,575.00000000,NULL,NULL,0,'2025-11-22 06:58:10','2025-11-22 06:58:10'),(111,5,1,'WD69330112870F1',215.00000000,1.00000000,214.00000000,'\"{\\\"note\\\":\\\"Demo withdrawal processed successfully\\\"}\"',NULL,1,'2025-12-04 08:58:10','2025-12-04 08:58:10'),(112,2,1,'WD6933011293C0B',989.00000000,1.00000000,988.00000000,NULL,NULL,0,'2025-10-28 04:58:10','2025-10-28 04:58:10'),(113,3,1,'WD69330112A16E9',77.00000000,1.00000000,76.00000000,NULL,NULL,0,'2025-11-04 19:58:10','2025-11-04 19:58:10'),(114,4,1,'WD69330112A837B',469.00000000,1.00000000,468.00000000,'\"{\\\"note\\\":\\\"Demo withdrawal processed successfully\\\"}\"',NULL,1,'2025-11-22 15:58:10','2025-11-22 15:58:10'),(115,2,1,'WD69330112BCF33',22.00000000,1.00000000,21.00000000,NULL,NULL,0,'2025-10-22 16:58:10','2025-10-22 16:58:10'),(116,2,1,'WD69330112E362E',860.00000000,1.00000000,859.00000000,NULL,'Demo rejection reason',2,'2025-10-24 06:58:10','2025-10-24 06:58:10'),(117,5,1,'WD69330112EA0CA',592.00000000,1.00000000,591.00000000,NULL,NULL,0,'2025-10-28 13:58:10','2025-10-28 13:58:10'),(118,2,1,'WD69330112F2B8E',626.00000000,1.00000000,625.00000000,NULL,NULL,0,'2025-12-01 17:58:10','2025-12-01 17:58:10'),(119,4,1,'WD693301130583F',753.00000000,1.00000000,752.00000000,'\"{\\\"note\\\":\\\"Demo withdrawal processed successfully\\\"}\"',NULL,1,'2025-10-25 08:58:11','2025-10-25 08:58:11'),(120,1,1,'WD6933011313E84',556.00000000,1.00000000,555.00000000,NULL,NULL,0,'2025-11-05 18:58:11','2025-11-05 18:58:11');
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

-- Dump completed on 2025-12-06 16:11:49

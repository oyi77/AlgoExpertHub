-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Waktu pembuatan: 04 Des 2025 pada 01.44
-- Versi server: 10.6.23-MariaDB-cll-lve
-- Versi PHP: 8.4.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `algotrad_signals`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_admins`
--

CREATE TABLE `sp_admins` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `sp_admins`
--

INSERT INTO `sp_admins` (`id`, `username`, `email`, `image`, `type`, `password`, `status`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@admin.com', NULL, 'super', '$2y$10$hENhHJyP4Z8DXWTHUWMbHO6dT30jIWxdtWcXzS6Bh9YNMWAXOwZyG', 1, 'axlK1hjPsEYGtx7OmnrJWWBly5xDQWlQJG3dlhXvdUmq0sSvXq6e6ynR1ziP', NULL, NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_admin_password_resets`
--

CREATE TABLE `sp_admin_password_resets` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `status` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_ai_configurations`
--

CREATE TABLE `sp_ai_configurations` (
  `id` bigint(20) UNSIGNED NOT NULL,
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
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `sp_ai_configurations`
--

INSERT INTO `sp_ai_configurations` (`id`, `provider`, `name`, `api_key`, `api_url`, `model`, `settings`, `enabled`, `migrated`, `priority`, `timeout`, `temperature`, `max_tokens`, `created_at`, `updated_at`) VALUES
(1, 'gemini', 'Gemini', 'eyJpdiI6Ii82YzM3czQybldCb3g1V28wRW1IVVE9PSIsInZhbHVlIjoiSC9zWW1YcEhOMWFNK1BqTDFGOFNhakl3YUp2Wi9GSFlvN09CNnhRcHdOcTBkUERheGxiQStEWDZRYS9yQjVYRyIsIm1hYyI6IjE5YzIxMGNmNGUwMmQxZTg5YTc3MmJkZjM5MjA2ZjQ2OTZmYjNkM2YwNjU1MzBmYWI3YzRiNzliZjc0MjE5ZDkiLCJ0YWciOiIifQ==', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent', 'gemini-2.5-flash', NULL, 1, 1, 50, 30, 0.30, 500, '2025-11-17 10:38:44', '2025-11-18 08:49:13');

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_ai_connections`
--

CREATE TABLE `sp_ai_connections` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `provider_id` bigint(20) UNSIGNED NOT NULL,
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
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_ai_connection_usage`
--

CREATE TABLE `sp_ai_connection_usage` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `connection_id` bigint(20) UNSIGNED NOT NULL,
  `feature` varchar(255) NOT NULL,
  `tokens_used` int(11) NOT NULL DEFAULT 0,
  `cost` decimal(10,6) NOT NULL DEFAULT 0.000000,
  `success` tinyint(1) NOT NULL DEFAULT 1,
  `response_time_ms` int(11) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_ai_model_profiles`
--

CREATE TABLE `sp_ai_model_profiles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_by_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `visibility` enum('PRIVATE','PUBLIC_MARKETPLACE') NOT NULL DEFAULT 'PRIVATE',
  `clonable` tinyint(1) NOT NULL DEFAULT 1,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `ai_connection_id` bigint(20) UNSIGNED DEFAULT NULL,
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
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_ai_parsing_profiles`
--

CREATE TABLE `sp_ai_parsing_profiles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `channel_source_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ai_connection_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `parsing_prompt` text DEFAULT NULL,
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`settings`)),
  `priority` int(11) NOT NULL DEFAULT 50,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_ai_providers`
--

CREATE TABLE `sp_ai_providers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `default_connection_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `sp_ai_providers`
--

INSERT INTO `sp_ai_providers` (`id`, `name`, `slug`, `status`, `default_connection_id`, `created_at`, `updated_at`) VALUES
(1, 'OpenAI', 'openai', 'active', NULL, '2025-12-03 01:02:29', '2025-12-03 01:02:29'),
(2, 'Google Gemini', 'gemini', 'active', NULL, '2025-12-03 01:02:29', '2025-12-03 01:02:29'),
(3, 'OpenRouter', 'openrouter', 'active', NULL, '2025-12-03 01:02:29', '2025-12-03 01:02:29');

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_channel_messages`
--

CREATE TABLE `sp_channel_messages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `channel_source_id` bigint(20) UNSIGNED NOT NULL,
  `raw_message` text NOT NULL,
  `message_hash` varchar(64) NOT NULL COMMENT 'SHA256 hash for duplicate detection',
  `parsed_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Parsed signal data' CHECK (json_valid(`parsed_data`)),
  `signal_id` bigint(20) UNSIGNED DEFAULT NULL,
  `status` enum('pending','processed','failed','duplicate','manual_review') NOT NULL DEFAULT 'pending',
  `confidence_score` int(10) UNSIGNED DEFAULT NULL COMMENT '0-100 parsing confidence',
  `error_message` text DEFAULT NULL,
  `processing_attempts` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_channel_sources`
--

CREATE TABLE `sp_channel_sources` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `is_admin_owned` tinyint(1) NOT NULL DEFAULT 0,
  `scope` enum('user','plan','global') DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `type` enum('telegram','telegram_mtproto','api','web_scrape','rss') NOT NULL DEFAULT 'telegram',
  `config` text DEFAULT NULL COMMENT 'Encrypted credentials, URLs, selectors, etc.',
  `status` enum('active','paused','error','pending') NOT NULL DEFAULT 'active',
  `last_processed_at` timestamp NULL DEFAULT NULL,
  `error_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `last_error` text DEFAULT NULL,
  `auto_publish_confidence_threshold` int(10) UNSIGNED NOT NULL DEFAULT 90 COMMENT '0-100, signals with confidence >= this are auto-published',
  `parser_preference` enum('auto','pattern','ai') NOT NULL DEFAULT 'auto' COMMENT 'auto = try pattern first, then AI; pattern = only pattern templates; ai = only AI parsing',
  `default_plan_id` bigint(20) UNSIGNED DEFAULT NULL,
  `default_market_id` bigint(20) UNSIGNED DEFAULT NULL,
  `default_timeframe_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `sp_channel_sources`
--

INSERT INTO `sp_channel_sources` (`id`, `user_id`, `is_admin_owned`, `scope`, `name`, `type`, `config`, `status`, `last_processed_at`, `error_count`, `last_error`, `auto_publish_confidence_threshold`, `parser_preference`, `default_plan_id`, `default_market_id`, `default_timeframe_id`, `created_at`, `updated_at`) VALUES
(16, NULL, 1, NULL, 'Tele CoderGaboets', 'telegram_mtproto', 'eyJpdiI6Ilg3dVpDWjVrUEEvdlh4QkhKYW5Cc0E9PSIsInZhbHVlIjoiL0Fhek1HeU5mL2wwYkM4RTRoc245VURXUEpZNWZQUTVXRHB0aDBTb2hscjZXR1NmSTJQQ3pQeUh6bENWTnVNRytWM0wwVzFaQk80Yjh5NWxibDF1WW1NWjFlKy9JUXVWRmhQYkUxbEwrK0tvbi95ODNabStpODc2cmc5Z0Y2dHk5L3dVOVYvb0VjcittYjFSYkFDazN0VnJGRkhvU3U4VUJVYUhIWkpnU2kyM0FINmpSSCtEcE1mM0Q4eUplTlFVVENCSUh0bU5uTnROWU9zL1FJaVlFV1JPdmg2akFLeUxUQWJ0WEMxd0dFbUJyUjhiM2ZyWFlmVC90Vnh3aXdPdHRtM0tnR1JUWnViSldSOU96UEhDS0wxcmhWc0Z6S2k4T3ZnVkU5MWJ0b0U9IiwibWFjIjoiYzg5NDlmNzA3M2VjZDczZmUzZDliNGEzZWI3NDU2ZTZiZDVhZWU3NzA0NWMxNjhmZGFjMDZlNzJjYjMzOTBkMCIsInRhZyI6IiJ9', 'active', NULL, 3, 'Failed to get dialogs: Undefined array key 0', 90, 'auto', NULL, NULL, NULL, '2025-11-14 22:51:12', '2025-11-16 09:48:17'),
(17, NULL, 1, NULL, 'Tele Veris (5 channels)', 'telegram_mtproto', 'eyJpdiI6IldTOGE0aWVlSVllUXowQmhQMXN1RXc9PSIsInZhbHVlIjoiem5iM3FxallBNzU5dUxyZG5Db3NxVUFjQTBWamYycGJDTWdPNnBBTlRIMnhRNXh1emlHdmU3NldsaGpEY055dGZSR0xBbGlvUENJN1JEUDROek80NERZNitkSDNwNk9UOFIrejRKT2tJUkhIUUdYandDT0I5UTZaUC9EZzMwZGpTcm4rVUJUZjJKZjFQVXRnV0RhQ0hlSFd0djI0WWxFeklSdzVQemxRa2ZRYklXOGFLd21KbnNrM3ZBZEljTXJMSHc0MUQ5aWpNbCs2NE94bHI4Szc4U2xnZjE1Q3k3a1ZyQ0pLQTZvbk4yWHF2ZTFIWUlZSXBrZkEyZUdGZkNxRnZzQktsZW8vd0huVXZSM2lPT0tDVjJ3RkNVUC9iTlJBbzlkUEY3aXUxVXkwVTFqRDFnVnQ4WFJCaXk5NXI1V2E5VGxXYU12RUxFYlpmTlV0eVhGTzJoVEcvOVpMUUcwSTFBOS85RHdWd0FWbER1Vkp4NGsrK0JlQjQwbnRiREY1VFk0d21MZXh3SEF6V1haZnBFSVlEdEg3Q0lmMDF0UUtnbytKYTNyY1EwbFppT1NDQ3FNZDNrdUs1MnloTjRMWTRINTVwK05PaGt6Vnp1SlphT2JXU2xrVWRHbTdkQVJUMHNibGJMVDJxY09KZlRhaHpiVUI2a1FGblJiWVRhczNtaVBQRDYzTHZoVWdKYTlGMWxCbEpZMHcrTXY4YjMxN2poR3k2NTJZYmlhc0JEYkg3cmFjMGxHcDl3MDcraHRXcXRrdVdwSWlDTGgwWmVGSE1tSTNBY2JhUjRyTHY4UEw5TjBLckZIdFRSWE5hSFBPRi9TWWZmSkY2cC9NZnRHREN0emdGcmFYSXZBeWxvNnNIQXF1bnlxenZTNVBFRVl2MnBCbHdsellVblhSU2tlN0VOUlNzYWJFOUtqamViZmsvYUxQUDJzc2NTankrMXhBN0tuRkQ4cndobXFyRG8vQ0R6R0ViUmRTcDNBS1hjTlhWaGhybTd0RUw4QTJYbUJNekZFN1FZRkhqOVN1azZXK3p6bkJta2RocUF1SlB5bXNXa1k1R1k4YUVJdTBuQkRabm0wT0ErbE5La2hpdTZ3WjFqQlM3Um9sbDR6eFhDNnVqUDR3R3ZlbjVsUmtoMEpjTkxSeGhqTW54anFRMDd2TWpscERpcmFaLzVxSURJM2s5QkJuWlkvSDAwaXdRekk5Wkc0UHd3VE94Tk9XNEcxUGNxeUFXL2RxaktHelNEVk13aEpiZVFlZitSdjdUakNMMlNiVUIrV1F4NjROM0d3d0VqNmhUbk9RUEJYOG9JUFVHa2xnTlA5dEQwQT0iLCJtYWMiOiIzYWU5ZmUzNTBhYzA4NDY1OWIyZTRmNDhjMDI2NjYzYzlkYWU2NWQ3ZmUyM2NlNzc0NzUyOGEwOWViODc1YWU1IiwidGFnIjoiIn0=', 'active', NULL, 1, 'Password authentication failed: I\'m not waiting for the password! Please call the phoneLogin and the completePhoneLogin methods first!', 90, 'auto', NULL, NULL, NULL, '2025-11-16 09:53:12', '2025-11-17 10:36:52'),
(18, NULL, 1, 'global', 'Tele Babi (4 channels)', 'telegram_mtproto', 'eyJpdiI6InBsSXlmVEZsMmNMM1ZSWWlNV3lYYXc9PSIsInZhbHVlIjoiWHhCUlZ4a0tzN2tVbkVNWUdGSFFHaWFZYjVUejlxb0drVkhLOCt6MHc5TitjaXNCaUFZVE4yVnVpbVhFK2gwMGVSSUMzSE5BVzQrUXRidmQvNU11b3d6bTRaQWNlVjkrOWNLMmJidkU0NGQzVW1mdm5YZURJUkhMSGhFRnBDeGJ3NUV5enF5Tm4zVDdCMHhLbWVIWTdNVU1lR0hGRlNNZVVON0FEQldGRFYyWGt1aUUxSFFkZDZ5R29KWmIwZmRBeUpXVmYvaDdqYmZmVVB4Q3MzZ1c3VU5vaXVyZkVWS24wV0cwbU5ETTdUckpNcWRldDFkZDEyaWZORFBaVGlvQW9kYTlRWjBGUDFKT3E4c0dGRUg5MVdUS0JxakRhNitJd1hUYmZvREZ6NEREakdvRUVoekRRVzJ4UEl0V0g2eU9zSTN1ODVJOWtiSVE2L2tSZ3diRndBZnpmSmRIc2x2Sll5L0tEaTNVNVNCcnc2VW1GSFdER0ZCOEJ6ZExLbFJaOXZZa3FiU0wvdnZBM1djUlYyZWxOTllIZUFxZEwyVExUa3ZmS1d6eEpnZE1ITVk3VUhmSGFDVjBSd2x4YWFEeGhXUWRWTUliR0hJbUxseTdjUzdSdnorbWh6Rm03bk9mWXFlNk1VditQL01CTEdOS1ZsTm94RW02VjNOak9zcVFJdjZ5K29OQWlNaktoUDRBQ2l6UDZDVjRKejJpVmZoMkx5UXFLVUo1dTU3YVhlcVhyV2ZQdnBudENBMWpaNWRwRVlMWm44Z3UxWHFueVdRSjVpTlBTYWpXeUhVeG5BRjZVa1M5a1NNajY4NHhCR0lra2l1UlZ3c1YxYjhMMSt3RkVNYUs5cU9CMmxPTkhQaXozbDVRMC9RUnpZbTYvK2oydUh4dGZYWGIwSjRDSS9INlR2Ny9ZWnV3NE55M3g2L3RtV1FVdTEyNDYvelE4bVdldDF2WWpMY2thWkUycG82YTdHdjFNSnVmcU1lbUVpZzFncWFWcjNVZ2NxZVM1ZFBMU1B6aEtQYzJ6TEo5cmh5dXFkZlllUGo2blZ4aUtyZzN5NXdLclJVRm1PU0VodWpPYndNZ2ZIOE5JM1VyaFFsbm50WVE4aVpybmlnS2NXamszRlYrc2c9PSIsIm1hYyI6IjM0NzNlYjcyYjYwZWMyNzMxNDIyMWVlZGQ4NWE3YThlYzUwYjYxMjc3NzVhYTA1MzRlMjg2NTBkY2ZhNWQ2OWIiLCJ0YWciOiIifQ==', 'active', NULL, 0, NULL, 90, 'auto', NULL, NULL, NULL, '2025-12-03 08:24:18', '2025-12-03 08:29:09');

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_channel_source_plans`
--

CREATE TABLE `sp_channel_source_plans` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `channel_source_id` bigint(20) UNSIGNED NOT NULL,
  `plan_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_channel_source_users`
--

CREATE TABLE `sp_channel_source_users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `channel_source_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_configurations`
--

CREATE TABLE `sp_configurations` (
  `id` bigint(20) UNSIGNED NOT NULL,
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
  `color` text DEFAULT NULL,
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
  `email_method` varchar(255) DEFAULT NULL,
  `email_sent_from` varchar(255) DEFAULT NULL,
  `email_config` text DEFAULT NULL,
  `decimal_precision` int(11) NOT NULL DEFAULT 2,
  `bot_url` varchar(255) DEFAULT NULL,
  `telegram_token` varchar(255) DEFAULT NULL,
  `allow_telegram` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `trade_charge` decimal(28,8) DEFAULT NULL,
  `min_trade_balance` decimal(28,8) DEFAULT NULL,
  `trade_limit` int(11) DEFAULT NULL,
  `crypto_api` varchar(255) DEFAULT NULL,
  `dark_logo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `sp_configurations`
--

INSERT INTO `sp_configurations` (`id`, `appname`, `theme`, `backend_theme`, `currency`, `pagination`, `number_format`, `alert`, `logo`, `favicon`, `reg_enabled`, `fonts`, `is_email_verification_on`, `is_sms_verification_on`, `preloader_image`, `preloader_status`, `analytics_status`, `analytics_key`, `allow_modal`, `button_text`, `cookie_text`, `allow_recaptcha`, `recaptcha_key`, `recaptcha_secret`, `tdio_allow`, `tdio_url`, `seo_tags`, `seo_description`, `color`, `signup_bonus`, `withdraw_limit`, `kyc`, `is_allow_kyc`, `transfer_limit`, `transfer_charge`, `transfer_type`, `transfer_min_amount`, `transfer_max_amount`, `allow_facebook`, `allow_google`, `cron`, `copyright`, `email_method`, `email_sent_from`, `email_config`, `decimal_precision`, `bot_url`, `telegram_token`, `allow_telegram`, `created_at`, `updated_at`, `trade_charge`, `min_trade_balance`, `trade_limit`, `crypto_api`, `dark_logo`) VALUES
(1, 'AlgoExpertHub', 'default', 'default', 'USD', 10, 2, 'izi', 'logo.jpg', 'icon.jpg', 1, '{\"heading_font_url\":\"https:\\/\\/fonts.googleapis.com\\/css2?family=Montserrat:wght@400;500;600;700&display=swap\",\"heading_font_family\":\"\'Montserrat\', sans-serif\",\"paragraph_font_url\":\"https:\\/\\/fonts.googleapis.com\\/css2?family=Poppins:wght@400;500&display=swap\",\"paragraph_font_family\":\"\'Poppins\',\'sans-serif\'\"}', 1, 0, NULL, 1, 1, 'hjkhjk', 1, 'Accept', 'By clicking “accept all cookies”, you agree stack exchange can store cookies on your device and disclose information in accordance with our cookie policy.', 0, '6LfnhS8eAAAAAAg3LgUY0ZBU0cxvyO6EkF8ylgFL', '6LfnhS8eAAAAADPPV4Z4nmii8B4-8rZW2o67O9pf', 1, 'dsadasdasdasdasdasd', '[\"ZXZX\",\"ZXZXZX\"]', 'AlgoExperthub merges AI analysis, proven strategies, and VIP signals into one auto-trading platform. Automate your trades across all markets, or become a Master Trader and earn from copiers. Start with a free trial. Trade up to $2.5M of our capital.', '', 500.00000000, 200, '[{\"field_label\":\"teset (required)\",\"field_name\":\"test\",\"type\":\"text\",\"validation\":\"required\"},{\"field_label\":\"asdasda\",\"field_name\":\"asdasd\",\"type\":\"text\",\"validation\":\"required\"}]', 0, 2, 2.00000000, 'percent', 2.00000000, 200.00000000, 0, 0, NULL, 'Copyright 2024 AlgoExpertHub. All Rights Reserved.', 'smtp', 'career@springsoftit.com', '{\"MAIL_DRIVER\":\"smtp\",\"MAIL_HOST\":\"sandbox.smtp.mailtrap.io\",\"MAIL_PORT\":\"587\",\"MAIL_USERNAME\":\"36c8fd83610f4d\",\"MAIL_PASSWORD\":\"fce37e1b98d6e9\",\"MAIL_ENCRYPTION\":\"tls\",\"MAIL_FROM_ADDRESS\":\"career@springsoftit.com\"}', 2, '@jairalok_bot', '6143471397:AAF2noabLskTTTdrdN8vAote5JNa_UPdxP8', 1, '2023-02-27 01:04:07', '2025-12-03 09:59:49', 2.00000000, 5.00000000, 2, '374b1887c32d907cb87236c99e13b05669f065525d447dbadad10b63a403a64a', 'dark_logo.jpg');

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_contents`
--

CREATE TABLE `sp_contents` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `type` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `theme` varchar(255) NOT NULL DEFAULT 'default',
  `language_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `sp_contents`
--

INSERT INTO `sp_contents` (`id`, `type`, `name`, `content`, `theme`, `language_id`, `parent_id`, `created_at`, `updated_at`) VALUES
(149, 'non_iteratable', 'banner', '{\"title\":\"Automate, Copy, or Lead \\u2013 Your Trading\",\"color_text_for_title\":\"Ecosystem Awaits\",\"button_text\":\"Get Started\",\"button_text_link\":\"login\",\"repeater\":[{\"repeater\":\"Let our AI engine forecast and execute across all markets\"},{\"repeater\":\"Make smarter decisions and build a profile others pay to copy\"},{\"repeater\":\"Discover autotrading that you control, plus insights from VIP traders\"},{\"repeater\":\"Unlock and trade up to $2.5m of our capital - keep 70% of any profits\"},{\"repeater\":\"Discover seamless autotrading tailored to your risk, on any market\"},{\"repeater\":\"Transition from a user to a funded strategist others replicate\"}],\"image_one\":\"645b70f58c43e1683714293.png\",\"image_two\":\"645b70f5a94b21683714293.png\",\"image_three\":null}', 'default', 0, NULL, '2023-05-10 04:18:34', '2025-12-03 09:31:07'),
(150, 'non_iteratable', 'about', '{\"title\":\"Unlock Your Edge in Algorithmic Trading\",\"color_text_for_title\":\"AlgoExperthub\",\"button_text\":\"Launch Your Edge\",\"button_link\":\"\\/join-now\",\"repeater\":[{\"repeater\":\"Your Trading Signals Are Already Obsolete. Evolve Your Edge.\"},{\"repeater\":\"From Manual Trading to Automated Strategy Architect.\"},{\"repeater\":\"Gain VIP Community Insights, Then Become the Source.\"},{\"repeater\":\"Trade with Conviction, Backed by AI & Institutional Tools.\"},{\"repeater\":\"Let the Platform Execute. You Focus on Strategy & Growth.\"},{\"repeater\":\"Get Funded, Get Copied, Build Your Trading Legacy.\"}],\"description\":\"AlgoExperthub is your all-in-one platform to automate, analyze, and amplify your trading. Leverage AI, institutional strategies, and a mastermind community. Don\'t just trade\\u2014build a scalable edge and monetize your track record.\",\"image_one\":\"693067e1750611764780001.png\",\"image_two\":\"645b90830273d1683722371.png\"}', 'default', 0, NULL, '2023-05-10 04:18:44', '2025-12-03 09:40:02'),
(151, 'non_iteratable', 'benefits', '{\"section_header\":\"Summary of Benefits\",\"title\":\"Everything You Need to Fast Track Your Trading\",\"color_text_for_title\":\"Track Your Trading\",\"image_one\":\"645b7110c9a051683714320.png\"}', 'default', 0, NULL, '2023-05-10 04:18:52', '2023-05-10 05:54:52'),
(154, 'non_iteratable', 'contact', '{\"section_header\":\"Contact\",\"title\":\"We\'d Love to Hear From You\",\"color_text_for_title\":\"Hear From You\",\"email\":\"support@company.com\",\"phone\":\"01857319149\",\"address\":\"Visit our office HQ 10\\/3A Zamzam Tower, Alwal Street Newyork\",\"form_header\":\"Love to hear from you, Get in touch\",\"color_text_for_form_header\":\"Get in touch\"}', 'default', 0, NULL, '2023-05-10 04:19:36', '2023-05-10 06:12:45'),
(155, 'non_iteratable', 'footer', '{\"footer_short_details\":\"Lorem ipsum dolor sit amet consectetur, adipisicing elit. Fugiat delectus maxime nisi explicabo doloribus minima similique, quia hic accusantium laudantium odit voluptatibus molestiae enim aut repellat.\",\"image_one\":\"6930638c9d0171764778892.jpg\"}', 'default', 0, NULL, '2023-05-10 04:19:45', '2025-12-03 09:21:32'),
(156, 'non_iteratable', 'how_works', '{\"section_header\":\"How it Works\",\"title\":\"Started Trading With Algoexperthub\",\"color_text_for_title\":\"With Algoexperthub\",\"image_one\":null}', 'default', 0, NULL, '2023-05-10 04:19:52', '2025-12-03 09:41:26'),
(159, 'non_iteratable', 'plans', '{\"section_header\":\"Packages\",\"title\":\"Our Best Packages\",\"color_text_for_title\":\"Packages\",\"image_one\":\"645b6feade0401683714026.png\"}', 'default', 0, NULL, '2023-05-10 04:20:27', '2023-05-10 06:26:38'),
(160, 'non_iteratable', 'referral', '{\"section_header\":\"Referral\",\"title\":\"Our Forex Trading Referral\",\"color_text_for_title\":\"Trading Referral\"}', 'default', 0, NULL, '2023-05-10 04:20:33', '2023-05-11 00:34:01'),
(161, 'non_iteratable', 'team', '{\"section_header\":\"Our Team\",\"title\":\"Our Forex Trading Specialist\",\"color_text_for_title\":\"Forex Trading\",\"image_one\":null}', 'default', 0, NULL, '2023-05-10 04:20:41', '2023-05-10 06:23:21'),
(163, 'non_iteratable', 'testimonial', '{\"section_header\":\"Testimonials\",\"title\":\"What Our Customer Says\",\"color_text_for_title\":\"Our Customer\",\"image_one\":null}', 'default', 0, NULL, '2023-05-10 04:21:34', '2023-05-10 06:20:31'),
(165, 'non_iteratable', 'why_choose_us', '{\"section_header\":\"Choose Us\",\"title\":\"Why Choose AlgoExperthub\",\"color_text_for_title\":\"AlgoExperthub\",\"image_one\":null}', 'default', 0, NULL, '2023-05-10 04:21:55', '2025-12-03 09:48:06'),
(167, 'non_iteratable', 'blog', '{\"section_header\":\"Blog Post\",\"title\":\"Our Latest News\",\"color_text_for_title\":\"News\",\"image_one\":null}', 'default', 0, NULL, '2023-05-10 04:22:18', '2023-05-10 06:35:26'),
(169, 'iteratable', 'socials', '{\"icon\":\"fas fa-headset\",\"link\":\"https:\\/\\/www.youtube.com\\/embed\\/YF26L8cFnLw\"}', 'default', 0, NULL, '2023-05-10 04:22:42', '2023-05-10 04:22:42'),
(170, 'iteratable', 'links', '{\"page_title\":\"sdfsdf\",\"description\":\"<p>asdasdasd<\\/p>\"}', 'default', 0, NULL, '2023-05-10 04:22:54', '2023-05-10 04:22:54'),
(171, 'non_iteratable', 'auth', '{\"title\":\"asdasdasd\",\"image_one\":\"645b7088d8dee1683714184.png\"}', 'default', 0, NULL, '2023-05-10 04:23:04', '2023-05-10 04:23:04'),
(172, 'iteratable', 'benefits', '{\"title\":\"20+ Proven Trading Strategies\",\"icon\":\"fab fa-searchengin\",\"description\":\"Go beyond basic indicators. Access a curated library of institutional-grade strategies (trend-following, mean-reversion, scalping) ready to deploy or customize.\",\"image_one\":\"645b866696d2b1683719782.png\"}', 'default', 0, NULL, '2023-05-10 05:56:22', '2025-12-03 09:49:55'),
(173, 'iteratable', 'benefits', '{\"title\":\"VIP Insights & Direct Support\",\"icon\":\"far fa-user\",\"description\":\"Get exclusive market analysis from our pros and real-time signals via VIP Telegram groups. Plus, dedicated admin support to guide your setup.\",\"image_one\":\"645b868dc03051683719821.png\"}', 'default', 0, NULL, '2023-05-10 05:57:01', '2025-12-03 09:50:41'),
(174, 'iteratable', 'benefits', '{\"title\":\"AI-Powered Market Forecasting\",\"icon\":\"far fa-thumbs-up\",\"description\":\"Let our advanced AI analyze sentiment, patterns, and correlations across markets to identify high-probability opportunities you might miss.\",\"image_one\":\"645b86bf9c2b01683719871.png\"}', 'default', 0, NULL, '2023-05-10 05:57:51', '2025-12-03 09:51:01'),
(175, 'iteratable', 'benefits', '{\"title\":\"Seamless Autotrading Execution\",\"icon\":\"far fa-chart-bar\",\"description\":\"Set your rules once. Our system executes trades 24\\/5 across Forex, Crypto, and Indices with precision speed, eliminating emotional decisions.\",\"image_one\":\"645b86d565db91683719893.png\"}', 'default', 0, NULL, '2023-05-10 05:58:13', '2025-12-03 09:51:20'),
(176, 'iteratable', 'benefits', '{\"title\":\"Multi-Channel Alert System\",\"icon\":\"far fa-envelope\",\"description\":\"Receive critical trade signals and platform updates directly via Telegram. Never miss a key market movement.\",\"image_one\":\"645b86fc69aea1683719932.png\"}', 'default', 0, NULL, '2023-05-10 05:58:52', '2025-12-03 09:51:55'),
(177, 'iteratable', 'benefits', '{\"title\":\"Join a growing community\",\"icon\":\"fas fa-users\",\"description\":\"Architecto doloremque neque asperiores laboriosam voluptatum doloribus aperiam.\",\"image_one\":\"645b87238dc001683719971.png\"}', 'default', 0, NULL, '2023-05-10 05:59:31', '2023-05-11 00:48:05'),
(178, 'iteratable', 'brand', '{\"image_one\":\"645b87a25db3c1683720098.png\"}', 'default', 0, NULL, '2023-05-10 06:01:38', '2023-05-10 06:01:38'),
(179, 'iteratable', 'brand', '{\"image_one\":\"645b87aa1f34b1683720106.png\"}', 'default', 0, NULL, '2023-05-10 06:01:46', '2023-05-10 06:01:46'),
(180, 'iteratable', 'brand', '{\"image_one\":\"645b87afc24751683720111.png\"}', 'default', 0, NULL, '2023-05-10 06:01:51', '2023-05-10 06:01:51'),
(181, 'iteratable', 'brand', '{\"image_one\":\"645b87b70b7111683720119.png\"}', 'default', 0, NULL, '2023-05-10 06:01:59', '2023-05-10 06:01:59'),
(182, 'iteratable', 'brand', '{\"image_one\":\"645b87bd8756f1683720125.png\"}', 'default', 0, NULL, '2023-05-10 06:02:05', '2023-05-10 06:02:05'),
(183, 'iteratable', 'brand', '{\"image_one\":\"645b87c38b29f1683720131.png\"}', 'default', 0, NULL, '2023-05-10 06:02:11', '2023-05-10 06:02:11'),
(184, 'iteratable', 'brand', '{\"image_one\":\"645b87ca64dce1683720138.png\"}', 'default', 0, NULL, '2023-05-10 06:02:18', '2023-05-10 06:02:18'),
(185, 'iteratable', 'brand', '{\"image_one\":\"645b87d2854eb1683720146.png\"}', 'default', 0, NULL, '2023-05-10 06:02:26', '2023-05-10 06:02:26'),
(186, 'iteratable', 'brand', '{\"image_one\":\"645b87d7c6e581683720151.png\"}', 'default', 0, NULL, '2023-05-10 06:02:31', '2023-05-10 06:02:31'),
(187, 'iteratable', 'socials', '{\"icon\":\"fab fa-facebook-f\",\"link\":\"https:\\/\\/www.facebook.com\"}', 'default', 0, NULL, '2023-05-10 06:06:05', '2023-05-10 06:06:05'),
(188, 'iteratable', 'socials', '{\"icon\":\"fab fa-twitter\",\"link\":\"https:\\/\\/twitter.com\\/\"}', 'default', 0, NULL, '2023-05-10 06:08:36', '2023-05-10 06:08:36'),
(189, 'iteratable', 'socials', '{\"icon\":\"fab fa-linkedin-in\",\"link\":\"https:\\/\\/linkedin.com\\/\"}', 'default', 0, NULL, '2023-05-10 06:08:51', '2023-05-10 06:08:51'),
(190, 'iteratable', 'socials', '{\"icon\":\"fab fa-telegram-plane\",\"link\":\"https:\\/\\/telegram.org\\/\"}', 'default', 0, NULL, '2023-05-10 06:09:03', '2023-05-10 06:09:03'),
(191, 'iteratable', 'socials', '{\"icon\":\"fab fa-instagram\",\"link\":\"https:\\/\\/instagram.com\\/\"}', 'default', 0, NULL, '2023-05-10 06:09:14', '2023-05-10 06:09:14'),
(192, 'iteratable', 'how_works', '{\"title\":\"Create Account\",\"description\":\"Open a Free AlgoExperthub Account\\r\\nDescription: Simple registration process. No credit card required for trial.\"}', 'default', 0, NULL, '2023-05-10 06:15:36', '2025-12-03 09:46:07'),
(193, 'iteratable', 'how_works', '{\"title\":\"Free Trial\",\"description\":\"Explore with 3-Day Full Access\\r\\nDescription: Test the platform with default strategy and get personalized guidance from our admin team.\"}', 'default', 0, NULL, '2023-05-10 06:15:50', '2025-12-03 09:46:24'),
(194, 'iteratable', 'how_works', '{\"title\":\"Select Package\",\"description\":\"Choose Your Subscription Plan\\r\\nDescription: Flexible monthly or yearly plans designed for traders of all levels.\"}', 'default', 0, NULL, '2023-05-10 06:16:02', '2025-12-03 09:46:40'),
(195, 'iteratable', 'why_choose_us', '{\"title\":\"Meta Trader\",\"icon\":\"far fa-user-circle\",\"description\":\"Lorem ipsum dolor sit amet, consectetur adipisicing elit. Minus adipisci eos, molestias, itaque aspernatur quis saepe recusandae fugit.\",\"image_one\":\"645b8b6befee71683721067.png\"}', 'default', 0, NULL, '2023-05-10 06:17:48', '2023-05-11 01:44:20'),
(196, 'iteratable', 'why_choose_us', '{\"title\":\"Competetive Pricing\",\"icon\":\"far fa-chart-bar\",\"description\":\"Lorem ipsum dolor sit amet, consectetur adipisicing elit. Minus adipisci eos, molestias, itaque aspernatur quis saepe recusandae fugit.\",\"image_one\":\"645b8b88d22fe1683721096.png\"}', 'default', 0, NULL, '2023-05-10 06:18:16', '2023-05-11 01:44:56'),
(197, 'iteratable', 'why_choose_us', '{\"title\":\"Active Trader\",\"icon\":\"fas fa-bolt\",\"description\":\"Lorem ipsum dolor sit amet, consectetur adipisicing elit. Minus adipisci eos, molestias, itaque aspernatur quis saepe recusandae fugit.\",\"image_one\":\"645b8bab65d931683721131.png\"}', 'default', 0, NULL, '2023-05-10 06:18:51', '2023-05-11 01:45:32'),
(198, 'iteratable', 'testimonial', '{\"client_name\":\"Fluria Jafe\",\"designation\":\"Investor\",\"description\":\"Lorem ipsum dolor sit amet consectetur adipisicing elit. Dolorem dolores eaque dolorum quisquam consectetur est cumque ad tenetur sit? Obcaecati laboriosam illo ipsa culpa quaerat maiores! Nobis, inventore, cumque eos laudantium ducimus blanditiis magni reprehenderit ipsum iusto ab asperiores! Aut nisi qui dignissimos non ipsa accusantium ut, assumenda asperiores voluptate.\",\"image_one\":\"645b8c539eeda1683721299.jpg\"}', 'default', 0, NULL, '2023-05-10 06:21:39', '2023-05-10 06:21:39'),
(199, 'iteratable', 'testimonial', '{\"client_name\":\"Justin & UK\",\"designation\":\"Volunteer\",\"description\":\"Lorem ipsum dolor sit amet consectetur adipisicing elit. Dolorem dolores eaque dolorum quisquam consectetur est cumque ad tenetur sit? Obcaecati laboriosam illo ipsa culpa quaerat maiores! Nobis, inventore, cumque eos laudantium ducimus blanditiis magni reprehenderit ipsum iusto ab asperiores! Aut nisi qui dignissimos non ipsa accusantium ut, assumenda asperiores voluptate.\",\"image_one\":\"645b8c65acae01683721317.jpg\"}', 'default', 0, NULL, '2023-05-10 06:21:57', '2023-05-10 06:21:57'),
(200, 'iteratable', 'testimonial', '{\"client_name\":\"Juan P\\u00e9rez\",\"designation\":\"Senior Copy Tader\",\"description\":\"Lorem ipsum dolor sit amet consectetur adipisicing elit. Dolorem dolores eaque dolorum quisquam consectetur est cumque ad tenetur sit? Obcaecati laboriosam illo ipsa culpa quaerat maiores! Nobis, inventore, cumque eos laudantium ducimus blanditiis magni reprehenderit ipsum iusto ab asperiores! Aut nisi qui dignissimos non ipsa accusantium ut, assumenda asperiores voluptate.\",\"image_one\":\"645b8c761679c1683721334.jpg\"}', 'default', 0, NULL, '2023-05-10 06:22:14', '2023-05-10 06:22:14'),
(201, 'iteratable', 'testimonial', '{\"client_name\":\"Jhon Mekila Mrsd\",\"designation\":\"Senior Copy Tader\",\"description\":\"Lorem ipsum dolor sit amet consectetur adipisicing elit. Dolorem dolores eaque dolorum quisquam consectetur est cumque ad tenetur sit? Obcaecati laboriosam illo ipsa culpa quaerat maiores! Nobis, inventore, cumque eos laudantium ducimus blanditiis magni reprehenderit ipsum iusto ab asperiores! Aut nisi qui dignissimos non ipsa accusantium ut, assumenda asperiores voluptate.\",\"image_one\":\"645b8c8674c361683721350.jpg\"}', 'default', 0, NULL, '2023-05-10 06:22:30', '2023-05-10 06:22:30'),
(202, 'iteratable', 'team', '{\"member_name\":\"Metfo Janil\",\"designation\":\"Investor\",\"facebook_url\":\"https:\\/\\/www.facebook.com\\/\",\"twitter_url\":\"https:\\/\\/www.twitter .com\\/\",\"linkedin_url\":\"https:\\/\\/www.linkedin .com\\/\",\"instagram_url\":\"https:\\/\\/www.instagram .com\\/\",\"image_one\":\"645b8ccee07491683721422.jpg\"}', 'default', 0, NULL, '2023-05-10 06:23:42', '2023-05-10 06:23:42'),
(203, 'iteratable', 'team', '{\"member_name\":\"Kmor Kotv\",\"designation\":\"Investor\",\"facebook_url\":\"https:\\/\\/www.facebook.com\\/\",\"twitter_url\":\"https:\\/\\/www.twitter .com\\/\",\"linkedin_url\":\"https:\\/\\/www.linkedin .com\\/\",\"instagram_url\":\"https:\\/\\/www.instagram .com\\/\",\"image_one\":\"645b8ce29bbbc1683721442.jpg\"}', 'default', 0, NULL, '2023-05-10 06:24:02', '2023-05-10 06:24:02'),
(204, 'iteratable', 'team', '{\"member_name\":\"Dimuni Sakmil\",\"designation\":\"Senior Copy Tader\",\"facebook_url\":\"https:\\/\\/www.facebook.com\\/\",\"twitter_url\":\"https:\\/\\/www.twitter .com\\/\",\"linkedin_url\":\"https:\\/\\/www.linkedin .com\\/\",\"instagram_url\":\"https:\\/\\/www.instagram .com\\/\",\"image_one\":\"645b8cf7980561683721463.jpg\"}', 'default', 0, NULL, '2023-05-10 06:24:23', '2023-05-10 06:24:23'),
(205, 'iteratable', 'team', '{\"member_name\":\"MG Morgan\",\"designation\":\"Volunteer\",\"facebook_url\":\"https:\\/\\/www.facebook.com\\/\",\"twitter_url\":\"https:\\/\\/www.twitter .com\\/\",\"linkedin_url\":\"https:\\/\\/www.linkedin .com\\/\",\"instagram_url\":\"https:\\/\\/www.instagram .com\\/\",\"image_one\":\"645b8d091d9051683721481.jpg\"}', 'default', 0, NULL, '2023-05-10 06:24:41', '2023-05-10 06:24:41'),
(206, 'iteratable', 'team', '{\"member_name\":\"MK Jon\",\"designation\":\"Investor\",\"facebook_url\":\"https:\\/\\/www.facebook.com\\/\",\"twitter_url\":\"https:\\/\\/www.twitter .com\\/\",\"linkedin_url\":\"https:\\/\\/www.linkedin .com\\/\",\"instagram_url\":\"https:\\/\\/www.instagram .com\\/\",\"image_one\":\"645b8d234d9d01683721507.jpg\"}', 'default', 0, NULL, '2023-05-10 06:25:07', '2023-05-10 06:25:07'),
(207, 'iteratable', 'team', '{\"member_name\":\"Metfo Janil\",\"designation\":\"Senior Web Developer\",\"facebook_url\":\"https:\\/\\/www.facebook.com\\/\",\"twitter_url\":\"https:\\/\\/www.twitter .com\\/\",\"linkedin_url\":\"https:\\/\\/www.linkedin .com\\/\",\"instagram_url\":\"https:\\/\\/www.instagram .com\\/\",\"image_one\":\"645b8d3e101cb1683721534.jpg\"}', 'default', 0, NULL, '2023-05-10 06:25:34', '2023-05-10 06:25:34'),
(208, 'iteratable', 'overview', '{\"title\":\"Total Signal\",\"icon\":\"fas fa-satellite-dish\",\"counter\":\"25M +\"}', 'default', 0, NULL, '2023-05-10 06:32:47', '2023-05-11 01:42:39'),
(209, 'iteratable', 'overview', '{\"title\":\"Total Users\",\"icon\":\"fas fa-user-check\",\"counter\":\"235M+\"}', 'default', 0, NULL, '2023-05-10 06:33:12', '2023-05-10 06:33:12'),
(210, 'iteratable', 'overview', '{\"title\":\"Success Rate\",\"icon\":\"fas fa-chart-line\",\"counter\":\"4560M+\"}', 'default', 0, NULL, '2023-05-10 06:33:41', '2023-05-10 06:33:41'),
(211, 'iteratable', 'overview', '{\"title\":\"Total Award\",\"icon\":\"fas fa-award\",\"counter\":\"856000M+\"}', 'default', 0, NULL, '2023-05-10 06:34:16', '2023-05-10 06:34:16'),
(212, 'iteratable', 'blog', '{\"blog_title\":\"Temporibus, dignissimos aperiam accusamus dolore\",\"short_description\":\"Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nunc diam sapien, bibendum eu suscipit ut, lacinia in turpis. Donec facilisis ipsum nec eros cursus commodo sit amet at eros. Aenean semper massa maximus dui cursus, vitae aliquam mauris gravida. Nam at sagittis metus, in cursus augue. Pellentesque mollis ullamcorper urna et rutrum. Vestibulum convallis eros posuere dictum interdum. Fusce convallis ante in dolor facilisis, vitae commodo justo pharetra.\",\"description\":\"<p style=\\\"margin-right: 0px; margin-bottom: 15px; margin-left: 0px; text-align: justify; color: rgb(0, 0, 0); font-family: &quot;Open Sans&quot;, Arial, sans-serif;\\\"><br><\\/p><p style=\\\"margin-right: 0px; margin-bottom: 15px; margin-left: 0px; text-align: justify; color: rgb(0, 0, 0); font-family: &quot;Open Sans&quot;, Arial, sans-serif;\\\">Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Nullam aliquam lorem non risus accumsan vulputate. Praesent eget mi id arcu faucibus bibendum id ut odio. Curabitur viverra justo id iaculis facilisis. Curabitur sed interdum nibh. Phasellus maximus purus sed consequat varius. Mauris fermentum scelerisque magna, eu euismod enim. Curabitur sagittis sollicitudin odio, vitae cursus metus ultricies sed. Curabitur egestas sodales&nbsp;<\\/p><p style=\\\"margin-right: 0px; margin-bottom: 15px; margin-left: 0px; text-align: justify; color: rgb(0, 0, 0); font-family: &quot;Open Sans&quot;, Arial, sans-serif;\\\">Pellentesque tempor massa dolor, ac auctor quam lobortis accumsan. Suspendisse eu condimentum libero. Mauris et finibus orci. In hac habitasse platea dictumst. Donec in ultrices metus. Praesent ultrices volutpat magna at sagittis. Mauris egestas erat tortor, in gravida felis aliquet eget. Aliquam dictum, eros vitae lobortis dapibus, odio lacus tempus lectus, ut fermentum risus mauris a ex. Suspendisse ac quam dictum justo porttitor varius. Vestibulum ac nibh ante. Donec auctor pulvinar erat, sit amet ullamcorper enim facilisis vitae. Nunc vulputate sed lacus sit amet accumsan. Nunc ut maximus orci, et dapibus quam.<\\/p><p style=\\\"margin-right: 0px; margin-bottom: 15px; margin-left: 0px; text-align: justify; color: rgb(0, 0, 0); font-family: &quot;Open Sans&quot;, Arial, sans-serif;\\\">Suspendisse bibendum leo nibh. Sed eu dui massa. Cras egestas eget augue vitae auctor. Vivamus ullamcorper ullamcorper leo, tincidunt eleifend lectus fringilla sit amet. Aliquam non lectus luctus, ultrices sapien a, eleifend sem. Ut vitae vestibulum tellus, ac sollicitudin dolor. Morbi pellentesque quis orci cursus convallis. Aliquam lobortis eu lorem nec tincidunt. Proin nec pharetra odio. Phasellus sit amet nunc viverra, suscipit tellus in, consectetur enim. Fusce sodales imperdiet cursus. Ut ullamcorper nibh vitae justo rutrum convallis. Fusce scelerisque mi nisl, a aliquet dolor auctor sollicitudin.<\\/p>\",\"image_one\":\"645b8fe7c84ff1683722215.jpg\"}', 'default', 0, NULL, '2023-05-10 06:36:55', '2023-05-10 06:36:55'),
(213, 'iteratable', 'blog', '{\"blog_title\":\"Consectetur ea quod et possimus quae dolore iste\",\"short_description\":\"Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nunc diam sapien, bibendum eu suscipit ut, lacinia in turpis. Donec facilisis ipsum nec eros cursus commodo sit amet at eros. Aenean semper massa maximus dui cursus, vitae aliquam mauris gravida. Nam at sagittis metus, in cursus augue. Pellentesque mollis ullamcorper urna et rutrum. Vestibulum convallis eros posuere dictum interdum. Fusce convallis ante in dolor facilisis, vitae commodo justo pharetra.\\r\\n\\r\\nPellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Nullam aliquam lorem non risus accumsan vulputate. Praesent eget mi id arcu faucibus bibendum id ut odio. Curabitur viverra justo\",\"description\":\"<p style=\\\"margin-right: 0px; margin-bottom: 15px; margin-left: 0px; text-align: justify; color: rgb(0, 0, 0); font-family: &quot;Open Sans&quot;, Arial, sans-serif;\\\"><span style=\\\"font-size: 0.875rem;\\\">id iaculis facilisis. Curabitur sed interdum nibh. Phasellus maximus purus sed consequat varius. Mauris fermentum scelerisque magna, eu euismod enim. Curabitur sagittis sollicitudin odio, vitae cursus metus ultricies sed. Curabitur egestas sodales malesuada. Ut venenatis magna at massa fermentum lobortis. Suspendisse sit amet mauris non metus semper volutpat sit amet bibendum tellus. Suspendisse id eleifend magna. Integer sodales diam et ex molestie semper. Suspendisse lacinia nibh eu posuere vestibulum. Praesent ac turpis nisl. Duis maximus ipsum luctus mauris blandit tempor.<\\/span><br><\\/p><p style=\\\"margin-right: 0px; margin-bottom: 15px; margin-left: 0px; text-align: justify; color: rgb(0, 0, 0); font-family: &quot;Open Sans&quot;, Arial, sans-serif;\\\">Phasellus ex velit, elementum porta massa in, laoreet ullamcorper ligula. Fusce mi lacus, gravida gravida massa quis, consequat molestie lorem. Nullam tempor, mi non pharetra rhoncus, neque lacus auctor leo, at hendrerit augue massa vitae sem. Nunc sit amet venenatis erat. Sed sapien massa, hendrerit eu bibendum in, rutrum accumsan lectus. Ut viverra dui dictum ornare venenatis. Pellentesque auctor ornare placerat. Praesent cursus odio odio, id euismod leo ornare eget. Proin a massa lectus.<\\/p><p style=\\\"margin-right: 0px; margin-bottom: 15px; margin-left: 0px; text-align: justify; color: rgb(0, 0, 0); font-family: &quot;Open Sans&quot;, Arial, sans-serif;\\\">Pellentesque tempor massa dolor, ac auctor quam lobortis accumsan. Suspendisse eu condimentum libero. Mauris et finibus orci. In hac habitasse platea dictumst. Donec in ultrices metus. Praesent ultrices volutpat magna at sagittis. Mauris egestas erat tortor, in gravida felis aliquet eget. Aliquam dictum, eros vitae lobortis dapibus, odio lacus tempus lectus, ut fermentum risus mauris a ex. Suspendisse ac quam dictum justo porttitor varius. Vestibulum ac nibh ante. Donec auctor pulvinar erat, sit amet ullamcorper enim facilisis vitae. Nunc vulputate sed lacus sit amet accumsan. Nunc ut maximus orci, et dapibus quam.<\\/p><p style=\\\"margin-right: 0px; margin-bottom: 15px; margin-left: 0px; text-align: justify; color: rgb(0, 0, 0); font-family: &quot;Open Sans&quot;, Arial, sans-serif;\\\">Suspendisse bibendum leo nibh. Sed eu dui massa. Cras egestas eget augue vitae auctor. Vivamus ullamcorper ullamcorper leo, tincidunt eleifend lectus fringilla sit amet. Aliquam non lectus luctus, ultrices sapien a, eleifend sem. Ut vitae vestibulum tellus, ac sollicitudin dolor. Morbi pellentesque quis orci cursus convallis. Aliquam lobortis eu lorem nec tincidunt. Proin nec pharetra odio. Phasellus sit amet nunc viverra, suscipit tellus in, consectetur enim. Fusce sodales imperdiet cursus. Ut ullamcorper nibh vitae justo rutrum convallis. Fusce scelerisque mi nisl, a aliquet dolor auctor sollicitudin.<\\/p>\",\"image_one\":\"645b900cb1a481683722252.jpg\"}', 'default', 0, NULL, '2023-05-10 06:37:32', '2023-05-10 06:37:32'),
(214, 'iteratable', 'blog', '{\"blog_title\":\"Recusandae modi dolores fugit suscipit officiis earum\",\"short_description\":\"Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nunc diam sapien, bibendum eu suscipit ut, lacinia in turpis. Donec facilisis ipsum nec eros cursus commodo sit amet at eros. Aenean semper massa maximus dui cursus, vitae aliquam mauris gravida. Nam at sagittis metus, in cursus augue. Pellentesque mollis ullamcorper urna et rutrum. Vestibulum convallis eros posuere dictum interdum. Fusce convallis ante in dolor facilisis, vitae commodo justo pharetra.\",\"description\":\"<p style=\\\"margin-right: 0px; margin-bottom: 15px; margin-left: 0px; text-align: justify; color: rgb(0, 0, 0); font-family: &quot;Open Sans&quot;, Arial, sans-serif;\\\">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nunc diam sapien, bibendum eu suscipit ut, lacinia in turpis. Donec facilisis ipsum nec eros cursus commodo sit amet at eros. Aenean semper massa maximus dui cursus, vitae aliquam mauris gravida. Nam at sagittis metus, in cursus augue. Pellentesque mollis ullamcorper urna et rutrum. Vestibulum convallis eros posuere dictum interdum. Fusce convallis ante in dolor facilisis, vitae commodo justo pharetra.<\\/p><p style=\\\"margin-right: 0px; margin-bottom: 15px; margin-left: 0px; text-align: justify; color: rgb(0, 0, 0); font-family: &quot;Open Sans&quot;, Arial, sans-serif;\\\">Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Nullam aliquam lorem non risus accumsan vulputate. Praesent eget mi id arcu faucibus bibendum id ut odio. Curabitur viverra justo id iaculis facilisis. Curabitur sed interdum nibh. Phasellus maximus purus sed consequat varius. Mauris fermentum scelerisque magna, eu euismod enim. Curabitur sagittis sollicitudin odio, vitae cursus metus ultricies sed. Curabitur egestas sodales malesuada. Ut venenatis magna at massa fermentum lobortis. Suspendisse sit amet mauris non metus semper volutpat sit amet bibendum tellus. Suspendisse id eleifend magna. Integer sodales diam et ex molestie semper. Suspendisse lacinia nibh eu posuere vestibulum. Praesent ac turpis nisl. Duis maximus ipsum luctus mauris blandit tempor.<\\/p><p style=\\\"margin-right: 0px; margin-bottom: 15px; margin-left: 0px; text-align: justify; color: rgb(0, 0, 0); font-family: &quot;Open Sans&quot;, Arial, sans-serif;\\\">Phasellus ex velit, elementum porta massa in, laoreet ullamcorper ligula. Fusce mi lacus, gravida gravida massa quis, consequat molestie lorem. Nullam tempor, mi non pharetra rhoncus, neque lacus auctor leo, at hendrerit augue massa vitae sem. Nunc sit amet venenatis erat. Sed sapien massa, hendrerit eu bibendum in, rutrum accumsan lectus. Ut viverra dui dictum ornare venenatis. Pellentesque auctor ornare placerat. Praesent cursus odio odio, id euismod leo ornare eget. Proin a massa lectus.<\\/p><p style=\\\"margin-right: 0px; margin-bottom: 15px; margin-left: 0px; text-align: justify; color: rgb(0, 0, 0); font-family: &quot;Open Sans&quot;, Arial, sans-serif;\\\">Pellentesque tempor massa dolor, ac auctor quam lobortis accumsan. Suspendisse eu condimentum libero. Mauris et finibus orci. In hac habitasse platea dictumst. Donec in ultrices metus. Praesent ultrices volutpat magna at sagittis. Mauris egestas erat tortor, in gravida felis aliquet eget. Aliquam dictum, eros vitae lobortis dapibus, odio lacus tempus lectus, ut fermentum risus mauris a ex. Suspendisse ac quam dictum justo porttitor varius. Vestibulum ac nibh ante. Donec auctor pulvinar erat, sit amet ullamcorper enim facilisis vitae. Nunc vulputate sed lacus sit amet accumsan. Nunc ut maximus orci, et dapibus quam.<\\/p><p style=\\\"margin-right: 0px; margin-bottom: 15px; margin-left: 0px; text-align: justify; color: rgb(0, 0, 0); font-family: &quot;Open Sans&quot;, Arial, sans-serif;\\\">Suspendisse bibendum leo nibh. Sed eu dui massa. Cras egestas eget augue vitae auctor. Vivamus ullamcorper ullamcorper leo, tincidunt eleifend lectus fringilla sit amet. Aliquam non lectus luctus, ultrices sapien a, eleifend sem. Ut vitae vestibulum tellus, ac sollicitudin dolor. Morbi pellentesque quis orci cursus convallis. Aliquam lobortis eu lorem nec tincidunt. Proin nec pharetra odio. Phasellus sit amet nunc viverra, suscipit tellus in, consectetur enim. Fusce sodales imperdiet cursus. Ut ullamcorper nibh vitae justo rutrum convallis. Fusce scelerisque mi nisl, a aliquet dolor auctor sollicitudin.<\\/p>\",\"image_one\":\"645b902b7f9fd1683722283.jpg\"}', 'default', 0, NULL, '2023-05-10 06:38:03', '2023-05-10 06:38:03'),
(215, 'non_iteratable', 'trade', '{\"section_header\":\"Live Trading\",\"title\":\"Join the Algoexperthub community\",\"color_text_for_title\":\"Algoexperthub community\",\"button_text\":\"Start Trading\",\"button_link\":\"login\",\"image_one\":null}', 'default', 0, NULL, '2023-06-20 04:53:49', '2025-12-03 09:41:13'),
(216, 'non_iteratable', 'banner', '{\"title\":\"Discover The World of Forex Signal\",\"color_text_for_title\":\"Forex Signal\",\"button_text\":\"Get Started\",\"button_text_link\":\"#\",\"button_two_text\":\"Register Now\",\"button_two_text_link\":\"register\",\"description\":\"Lorem ipsum dolor sit amet consectetur adipisicing elit. Alias sit eius quia facere numquam est harum cupiditate, repellat odit repudiandae voluptatem qui in sequi dicta magni quisquam neque fugit suscipit.\",\"image_three\":\"64abdca21d89a1688984738.png\",\"image_one\":\"64abd46b2a05a1688982635.png\",\"image_two\":\"64abafeb996ca1688973291.jpg\"}', 'light', 0, NULL, '2023-07-09 01:06:26', '2023-07-10 04:25:38'),
(217, 'non_iteratable', 'why_choose_us', '{\"section_header\":\"Choose us\",\"title\":\"Why Choose TradeMax\",\"color_text_for_title\":\"TradeMax\"}', 'light', 0, NULL, '2023-07-09 01:07:03', '2023-07-09 01:07:03'),
(218, 'non_iteratable', 'footer', '{\"footer_short_details\":\"Lorem ipsum dolor sit amet consectetur, adipisicing elit. fugiat delectus maxime nisi explicabo doloribus minima similique, quia hic accusantium laudantium odit voluptatibus molestiae enim aut repellat.\",\"image_one\":\"64afc3ee3cd381689240558.png\"}', 'light', 0, NULL, '2023-07-09 23:44:16', '2023-07-13 03:29:18'),
(219, 'non_iteratable', 'about', '{\"title\":\"Join the trademax community\",\"color_text_for_title\":\"trademax community\",\"button_text\":\"Learn More\",\"button_link\":\"about\",\"repeater\":[{\"repeater\":\"Learn how to read and forecast the markets\"},{\"repeater\":\"Learn how to read and forecast the markets\"},{\"repeater\":\"Learn how to read and forecast the markets\"},{\"repeater\":\"Learn how to read and forecast the markets\"}],\"description\":\"Lorem ipsum dolor sit amet consectetur adipisicing elit. tempora perferendis molestias nesciunt. accusamus excepturi sint dicta velit nulla quod, natus dolorum inventore alias voluptates voluptatem, iste nemo consequuntur esse.\",\"image_one\":\"64abed38b05fa1688988984.png\",\"image_two\":\"64abe35a53fa01688986458.png\"}', 'light', 0, NULL, '2023-07-10 04:54:18', '2023-07-10 05:36:24'),
(220, 'non_iteratable', 'how_works', '{\"section_header\":\"How it works\",\"title\":\"Started Trading With TradeMax\",\"color_text_for_title\":\"With TradeMax\",\"image_two\":\"64acfb1d11e101689058077.jpg\",\"image_one\":null}', 'light', 0, NULL, '2023-07-10 05:41:19', '2023-07-11 00:47:57'),
(228, 'iteratable', 'how_works', '{\"title\":\"Open an account\",\"description\":\"Lorem ipsum dolor sit amet consectetur adipisicing elit. repudiandae laboriosam adipisci neque cumque, corrupti.\",\"image_one\":\"64acf4f6e2fa71689056502.png\"}', 'light', 0, NULL, '2023-07-11 00:21:42', '2023-07-11 00:21:42'),
(229, 'iteratable', 'how_works', '{\"title\":\"Deposit amount\",\"description\":\"Lorem ipsum dolor sit amet consectetur adipisicing elit. repudiandae laboriosam adipisci neque cumque, corrupti.\",\"image_one\":\"64acf503a4e111689056515.png\"}', 'light', 0, NULL, '2023-07-11 00:21:55', '2023-07-11 00:21:55'),
(230, 'iteratable', 'how_works', '{\"title\":\"Start trading\",\"description\":\"Lorem ipsum dolor sit amet consectetur adipisicing elit. repudiandae laboriosam adipisci neque cumque, corrupti.\",\"image_one\":\"64acf50d79cb11689056525.png\"}', 'light', 0, NULL, '2023-07-11 00:22:05', '2023-07-11 00:22:05'),
(231, 'non_iteratable', 'plans', '{\"section_header\":\"Our best packages\",\"title\":\"Our Best Packages\",\"color_text_for_title\":\"Packages\",\"image_one\":\"64acfd4e457f21689058638.jpg\"}', 'light', 0, NULL, '2023-07-11 00:57:18', '2023-07-11 00:57:18'),
(232, 'non_iteratable', 'trade', '{\"section_header\":\"Live trading\",\"title\":\"Join the trademax community\",\"color_text_for_title\":\"trademax community\",\"button_text\":\"Start Trading\",\"button_link\":\"#\",\"image_one\":\"64ad23f80a7da1689068536.png\"}', 'light', 0, NULL, '2023-07-11 02:56:13', '2023-07-11 03:42:16'),
(233, 'iteratable', 'why_choose_us', '{\"title\":\"Meta trader\",\"description\":\"Lorem ipsum dolor sit amet, consectetur adipisicing elit. minus adipisci eos, molestias, itaque aspernatur quis saepe recusandae fugit.\",\"image_one\":\"64ae64c1a52761689150657.png\"}', 'light', 0, NULL, '2023-07-12 01:17:45', '2023-07-12 02:30:57'),
(234, 'iteratable', 'why_choose_us', '{\"title\":\"Competetive pricing\",\"description\":\"Lorem ipsum dolor sit amet, consectetur adipisicing elit. minus adipisci eos, molestias, itaque aspernatur quis saepe recusandae fugit.\",\"image_one\":\"64ae64c8b4e9c1689150664.png\"}', 'light', 0, NULL, '2023-07-12 01:18:53', '2023-07-12 02:31:04'),
(235, 'iteratable', 'why_choose_us', '{\"title\":\"Active trader\",\"description\":\"Lorem ipsum dolor sit amet, consectetur adipisicing elit. minus adipisci eos, molestias, itaque aspernatur quis saepe recusandae fugit.\",\"image_one\":\"64ae64cfdfb8f1689150671.png\"}', 'light', 0, NULL, '2023-07-12 01:19:10', '2023-07-12 02:31:11'),
(236, 'iteratable', 'why_choose_us', '{\"title\":\"Meta trader\",\"description\":\"Lorem ipsum dolor sit amet, consectetur adipisicing elit. minus adipisci eos, molestias, itaque aspernatur quis saepe recusandae fugit.\",\"image_one\":\"64ae64d7006ff1689150679.png\"}', 'light', 0, NULL, '2023-07-12 02:23:43', '2023-07-12 02:31:19'),
(237, 'iteratable', 'why_choose_us', '{\"title\":\"Competetive pricing\",\"description\":\"Lorem ipsum dolor sit amet, consectetur adipisicing elit. minus adipisci eos, molestias, itaque aspernatur quis saepe recusandae fugit.\",\"image_one\":\"64ae64dd785041689150685.png\"}', 'light', 0, NULL, '2023-07-12 02:24:02', '2023-07-12 02:31:25'),
(238, 'iteratable', 'why_choose_us', '{\"title\":\"Active trader\",\"description\":\"Lorem ipsum dolor sit amet, consectetur adipisicing elit. minus adipisci eos, molestias, itaque aspernatur quis saepe recusandae fugit.\",\"image_one\":\"64ae64e37ab7f1689150691.png\"}', 'light', 0, NULL, '2023-07-12 02:24:21', '2023-07-12 02:31:31'),
(239, 'non_iteratable', 'overview', '{\"title\":\"Join our Trading platform today.\",\"color_text_for_title\":\"platform today.\",\"description\":\"Deleniti amet dolorem rerum magni porro ab assumenda eos, officiis accusamus cumque eaque placeat debitis, quia, consequatur voluptate facere iste alias. Magnam temporibus, veniam perspiciatis suscipit eaque odit facere fuga hic tempore quidem nam optio sunt quia enim dolor, itaque quam dicta. A, quam. Odio, incidunt illo sunt quia enim dolor\",\"image_one\":\"64ae74ff11fa31689154815.png\"}', 'light', 0, NULL, '2023-07-12 02:42:48', '2023-07-12 03:40:15'),
(240, 'iteratable', 'overview', '{\"title\":\"Total signal\",\"counter\":\"25 M\"}', 'light', 0, NULL, '2023-07-12 02:50:22', '2023-07-12 02:50:22'),
(241, 'iteratable', 'overview', '{\"title\":\"Total users\",\"counter\":\"235 M\"}', 'light', 0, NULL, '2023-07-12 02:50:48', '2023-07-12 02:50:48'),
(242, 'iteratable', 'overview', '{\"title\":\"Success rate\",\"counter\":\"45 M\"}', 'light', 0, NULL, '2023-07-12 02:51:18', '2023-07-12 03:02:35'),
(243, 'iteratable', 'overview', '{\"title\":\"Total award\",\"counter\":\"12\"}', 'light', 0, NULL, '2023-07-12 02:51:31', '2023-07-12 02:51:31'),
(244, 'non_iteratable', 'benefits', '{\"section_header\":\"Summary of benefits\",\"title\":\"Everything you need to fast track your trading\",\"color_text_for_title\":\"track your trading\",\"image_one\":\"64ae7fdde00711689157597.png\"}', 'light', 0, NULL, '2023-07-12 04:26:38', '2023-07-12 04:26:38'),
(245, 'non_iteratable', 'blog', '{\"section_header\":\"Blog post\",\"title\":\"Our Latest News\",\"color_text_for_title\":\"News\"}', 'light', 0, NULL, '2023-07-12 04:32:55', '2023-07-12 04:32:55'),
(246, 'non_iteratable', 'referral', '{\"title\":\"Our Forex Trading Referral\",\"color_text_for_title\":\"Trading Referral\",\"description\":\"Lorem ipsum dolor sit amet consectetur adipisicing elit. error iste quae soluta adipisci eum. consequuntur tenetur doloremque commodi unde. praesentium fugiat molestias ipsum distinctio at reprehenderit porro.\",\"image_one\":\"64af8ef86fb1a1689227000.png\"}', 'light', 0, NULL, '2023-07-12 04:33:21', '2023-07-12 23:48:43'),
(247, 'non_iteratable', 'team', '{\"section_header\":\"Our team\",\"title\":\"Our Forex Trading Specialist\",\"color_text_for_title\":\"Forex Trading\"}', 'light', 0, NULL, '2023-07-12 04:33:48', '2023-07-12 04:33:48'),
(248, 'non_iteratable', 'testimonial', '{\"section_header\":\"Testimonials\",\"title\":\"What Our Customer Says\",\"color_text_for_title\":\"Our Customer\",\"image_two\":\"64af9af18c6391689230065.jpg\",\"image_one\":null}', 'light', 0, NULL, '2023-07-12 04:34:21', '2023-07-13 00:34:25'),
(249, 'iteratable', 'benefits', '{\"title\":\"Top technical analysis\",\"description\":\"Architecto doloremque neque asperiores laboriosam voluptatum doloribus aperiam.\",\"image_one\":\"64ae84308548c1689158704.png\"}', 'light', 0, NULL, '2023-07-12 04:45:04', '2023-07-12 04:45:04'),
(250, 'iteratable', 'benefits', '{\"title\":\"High performance\",\"description\":\"Architecto doloremque neque asperiores laboriosam voluptatum doloribus aperiam.\",\"image_one\":\"64ae8440c18d81689158720.png\"}', 'light', 0, NULL, '2023-07-12 04:45:20', '2023-07-12 04:45:20'),
(251, 'iteratable', 'benefits', '{\"title\":\"Full expert support\",\"description\":\"Architecto doloremque neque asperiores laboriosam voluptatum doloribus aperiam.\",\"image_one\":\"64ae84503450c1689158736.png\"}', 'light', 0, NULL, '2023-07-12 04:45:36', '2023-07-12 04:45:36'),
(252, 'iteratable', 'benefits', '{\"title\":\"Direct email and sms* signals\",\"description\":\"Architecto doloremque neque asperiores laboriosam voluptatum doloribus aperiam.\",\"image_one\":\"64ae845c76f401689158748.png\"}', 'light', 0, NULL, '2023-07-12 04:45:48', '2023-07-12 04:45:48'),
(253, 'iteratable', 'benefits', '{\"title\":\"Highly recommended\",\"description\":\"Architecto doloremque neque asperiores laboriosam voluptatum doloribus aperiam.\",\"image_one\":\"64ae8468df0051689158760.png\"}', 'light', 0, NULL, '2023-07-12 04:46:00', '2023-07-12 04:46:00'),
(254, 'iteratable', 'benefits', '{\"title\":\"Join a growing community\",\"description\":\"Architecto doloremque neque asperiores laboriosam voluptatum doloribus aperiam.\",\"image_one\":\"64ae8476570781689158774.png\"}', 'light', 0, NULL, '2023-07-12 04:46:14', '2023-07-12 04:46:14'),
(255, 'iteratable', 'blog', '{\"blog_title\":\"Temporibus, dignissimos aperiam accusamus dolore\",\"short_description\":\"Lorem ipsum dolor sit amet consectetur adipisicing elit. Ab sit, animi praesentium esse exercitationem cumque minus quaerat omnis accusamus soluta nam in dolores hic quod laboriosam nostrum rerum fugit porro repellat eius vitae quae at iure. Qui at corrupti expedita obcaecati tempore optio repellat, eligendi, fugit nulla eum beatae velit temporibus, aut asperiores veniam deleniti molestiae excepturi debitis. Rem, corporis laboriosam ad omnis eveniet esse numquam atque, tenetur aliquam laudantium nemo. Explicabo minus eligendi, vero distinctio id ipsam perferendis cupiditate sapiente enim perspiciatis vitae, nam nesciunt labore officia facilis aliquam fuga pariatur reprehenderit earum totam laboriosam. Officia molestias commodi quibusdam.\",\"description\":\"<div style=\\\"font-family: Consolas, &quot;Courier New&quot;, monospace; line-height: 19px; white-space: pre;\\\"><font color=\\\"#636363\\\">Lorem ipsum dolor sit amet consectetur adipisicing elit. Ab sit, animi praesentium esse exercitationem cumque minus quaerat omnis accusamus soluta nam in dolores hic quod laboriosam nostrum rerum fugit porro repellat eius vitae quae at iure. Qui at corrupti expedita obcaecati tempore optio repellat, eligendi, fugit nulla eum beatae velit temporibus, aut asperiores veniam deleniti molestiae excepturi debitis. Rem, corporis laboriosam ad omnis eveniet esse numquam atque, tenetur aliquam laudantium nemo. Explicabo minus eligendi, vero distinctio id ipsam perferendis cupiditate sapiente enim perspiciatis vitae, nam nesciunt labore officia facilis aliquam fuga pariatur reprehenderit earum totam laboriosam. Officia molestias commodi quibusdam.<\\/font><\\/div>\",\"image_one\":\"64ae8ae0746d41689160416.jpg\"}', 'light', 0, NULL, '2023-07-12 05:13:36', '2023-07-12 05:13:36'),
(256, 'iteratable', 'blog', '{\"blog_title\":\"Consectetur ea quod et possimus quae dolore iste\",\"short_description\":\"Lorem ipsum dolor sit amet consectetur adipisicing elit. Ab sit, animi praesentium esse exercitationem cumque minus quaerat omnis accusamus soluta nam in dolores hic quod laboriosam nostrum rerum fugit porro repellat eius vitae quae at iure. Qui at corrupti expedita obcaecati tempore optio repellat, eligendi, fugit nulla eum beatae velit temporibus, aut asperiores veniam deleniti molestiae excepturi debitis. Rem, corporis laboriosam ad omnis eveniet esse numquam atque, tenetur aliquam laudantium nemo. Explicabo minus eligendi, vero distinctio id ipsam perferendis cupiditate sapiente enim perspiciatis vitae, nam nesciunt labore officia facilis aliquam fuga pariatur reprehenderit earum totam laboriosam. Officia molestias commodi quibusdam.\",\"description\":\"<div style=\\\"font-family: Consolas, &quot;Courier New&quot;, monospace; line-height: 19px; white-space: pre;\\\"><font color=\\\"#636363\\\">Lorem ipsum dolor sit amet consectetur adipisicing elit. Ab sit, animi praesentium esse exercitationem cumque minus quaerat omnis accusamus soluta nam in dolores hic quod laboriosam nostrum rerum fugit porro repellat eius vitae quae at iure. Qui at corrupti expedita obcaecati tempore optio repellat, eligendi, fugit nulla eum beatae velit temporibus, aut asperiores veniam deleniti molestiae excepturi debitis. Rem, corporis laboriosam ad omnis eveniet esse numquam atque, tenetur aliquam laudantium nemo. Explicabo minus eligendi, vero distinctio id ipsam perferendis cupiditate sapiente enim perspiciatis vitae, nam nesciunt labore officia facilis aliquam fuga pariatur reprehenderit earum totam laboriosam. Officia molestias commodi quibusdam.<\\/font><\\/div>\",\"image_one\":\"64ae8b0a98bda1689160458.jpg\"}', 'light', 0, NULL, '2023-07-12 05:14:18', '2023-07-12 05:14:18'),
(257, 'iteratable', 'blog', '{\"blog_title\":\"Recusandae modi dolores fugit suscipit officiis earum\",\"short_description\":\"Lorem ipsum dolor sit amet consectetur adipisicing elit. Ab sit, animi praesentium esse exercitationem cumque minus quaerat omnis accusamus soluta nam in dolores hic quod laboriosam nostrum rerum fugit porro repellat eius vitae quae at iure. Qui at corrupti expedita obcaecati tempore optio repellat, eligendi, fugit nulla eum beatae velit temporibus, aut asperiores veniam deleniti molestiae excepturi debitis. Rem, corporis laboriosam ad omnis eveniet esse numquam atque, tenetur aliquam laudantium nemo. Explicabo minus eligendi, vero distinctio id ipsam perferendis cupiditate sapiente enim perspiciatis vitae, nam nesciunt labore officia facilis aliquam fuga pariatur reprehenderit earum totam laboriosam. Officia molestias commodi quibusdam.\",\"description\":\"<div style=\\\"font-family: Consolas, &quot;Courier New&quot;, monospace; line-height: 19px; white-space: pre;\\\"><font color=\\\"#636363\\\">Lorem ipsum dolor sit amet consectetur adipisicing elit. Ab sit, animi praesentium esse exercitationem cumque minus quaerat omnis accusamus soluta nam in dolores hic quod laboriosam nostrum rerum fugit porro repellat eius vitae quae at iure. Qui at corrupti expedita obcaecati tempore optio repellat, eligendi, fugit nulla eum beatae velit temporibus, aut asperiores veniam deleniti molestiae excepturi debitis. Rem, corporis laboriosam ad omnis eveniet esse numquam atque, tenetur aliquam laudantium nemo. Explicabo minus eligendi, vero distinctio id ipsam perferendis cupiditate sapiente enim perspiciatis vitae, nam nesciunt labore officia facilis aliquam fuga pariatur reprehenderit earum totam laboriosam. Officia molestias commodi quibusdam.<\\/font><\\/div>\",\"image_one\":\"64ae8b1e9a7d91689160478.jpg\"}', 'light', 0, NULL, '2023-07-12 05:14:38', '2023-07-12 05:14:38'),
(258, 'iteratable', 'team', '{\"member_name\":\"John Doe\",\"designation\":\"CEO, Toto Company\",\"facebook_url\":\"#\",\"twitter_url\":\"#\",\"linkedin_url\":\"#\",\"instagram_url\":\"#\",\"image_one\":\"64ae932422e791689162532.jpg\"}', 'light', 0, NULL, '2023-07-12 05:48:52', '2023-07-12 05:48:52'),
(259, 'iteratable', 'team', '{\"member_name\":\"Edward Milar\",\"designation\":\"CEO, Toto Company\",\"facebook_url\":\"#\",\"twitter_url\":\"#\",\"linkedin_url\":\"#\",\"instagram_url\":\"#\",\"image_one\":\"64ae933f01f661689162559.jpg\"}', 'light', 0, NULL, '2023-07-12 05:49:19', '2023-07-12 05:49:19'),
(260, 'iteratable', 'team', '{\"member_name\":\"Tarbo John\",\"designation\":\"CEO, Toto Company\",\"facebook_url\":\"#\",\"twitter_url\":\"#\",\"linkedin_url\":\"#\",\"instagram_url\":\"#\",\"image_one\":\"64ae9353762f91689162579.jpg\"}', 'light', 0, NULL, '2023-07-12 05:49:39', '2023-07-12 05:49:39'),
(261, 'iteratable', 'team', '{\"member_name\":\"Urabana Edawad\",\"designation\":\"CEO, Toto Company\",\"facebook_url\":\"#\",\"twitter_url\":\"#\",\"linkedin_url\":\"#\",\"instagram_url\":\"#\",\"image_one\":\"64ae936eaf5611689162606.jpg\"}', 'light', 0, NULL, '2023-07-12 05:50:06', '2023-07-12 05:50:06'),
(262, 'iteratable', 'team', '{\"member_name\":\"Teawk Jana\",\"designation\":\"CEO, Toto Company\",\"facebook_url\":\"#\",\"twitter_url\":\"#\",\"linkedin_url\":\"#\",\"instagram_url\":\"#\",\"image_one\":\"64ae938193ed51689162625.jpg\"}', 'light', 0, NULL, '2023-07-12 05:50:25', '2023-07-12 05:50:25'),
(263, 'iteratable', 'testimonial', '{\"client_name\":\"John Doe\",\"designation\":\"Toto Company\",\"description\":\"Lorem ipsum dolor sit amet, consectetur adipisicing elit. Aperiam, cum! Sit ad officiis eius et inventore optio eligendi. Totam suscipit in obcaecati repellat quisquam deleniti facilis excepturi ad minima consectetur consectetur adipisicing.\",\"image_one\":\"64afa18cb77c11689231756.jpg\"}', 'light', 0, NULL, '2023-07-13 00:37:31', '2023-07-13 01:02:36'),
(264, 'iteratable', 'testimonial', '{\"client_name\":\"Pera Mal\",\"designation\":\"Toto Company\",\"description\":\"Lorem ipsum dolor sit amet, consectetur adipisicing elit. Aperiam, cum! Sit ad officiis eius et inventore optio eligendi. Totam suscipit in obcaecati repellat quisquam deleniti facilis excepturi ad minima consectetur consectetur adipisicing.\",\"image_one\":\"64afa1a088bae1689231776.jpg\"}', 'light', 0, NULL, '2023-07-13 00:38:04', '2023-07-13 01:02:56'),
(265, 'iteratable', 'testimonial', '{\"client_name\":\"Tara Kal\",\"designation\":\"Toto Company\",\"description\":\"Lorem ipsum dolor sit amet, consectetur adipisicing elit. Aperiam, cum! Sit ad officiis eius et inventore optio eligendi. Totam suscipit in obcaecati repellat quisquam deleniti facilis excepturi ad minima consectetur consectetur adipisicing.\",\"image_one\":\"64afa240924b01689231936.jpg\"}', 'light', 0, NULL, '2023-07-13 00:38:22', '2023-07-13 01:05:36'),
(266, 'iteratable', 'testimonial', '{\"client_name\":\"Ualal Jal\",\"designation\":\"Toto Company\",\"description\":\"Lorem ipsum dolor sit amet, consectetur adipisicing elit. Aperiam, cum! Sit ad officiis eius et inventore optio eligendi. Totam suscipit in obcaecati repellat quisquam deleniti facilis excepturi ad minima consectetur consectetur adipisicing.\",\"image_one\":\"64afa247d6e351689231943.jpg\"}', 'light', 0, NULL, '2023-07-13 00:38:41', '2023-07-13 01:05:43'),
(267, 'iteratable', 'testimonial', '{\"client_name\":\"Yala Mal\",\"designation\":\"Toto Company\",\"description\":\"Lorem ipsum dolor sit amet, consectetur adipisicing elit. Aperiam, cum! Sit ad officiis eius et inventore optio eligendi. Totam suscipit in obcaecati repellat quisquam deleniti facilis excepturi ad minima consectetur consectetur adipisicing.\",\"image_one\":\"64afa24eb51111689231950.jpg\"}', 'light', 0, NULL, '2023-07-13 00:38:57', '2023-07-13 01:05:50');
INSERT INTO `sp_contents` (`id`, `type`, `name`, `content`, `theme`, `language_id`, `parent_id`, `created_at`, `updated_at`) VALUES
(268, 'iteratable', 'testimonial', '{\"client_name\":\"Pawal Hala\",\"designation\":\"Toto Company\",\"description\":\"Lorem ipsum dolor sit amet, consectetur adipisicing elit. Aperiam, cum! Sit ad officiis eius et inventore optio eligendi. Totam suscipit in obcaecati repellat quisquam deleniti facilis excepturi ad minima consectetur consectetur adipisicing.\",\"image_one\":\"64afa255340ee1689231957.jpg\"}', 'light', 0, NULL, '2023-07-13 00:39:19', '2023-07-13 01:05:57'),
(269, 'iteratable', 'brand', '{\"image_one\":\"64afbd8e8a0ae1689238926.png\"}', 'light', 0, NULL, '2023-07-13 03:02:06', '2023-07-13 03:02:06'),
(270, 'iteratable', 'brand', '{\"image_one\":\"64afbd95334cc1689238933.png\"}', 'light', 0, NULL, '2023-07-13 03:02:13', '2023-07-13 03:02:13'),
(271, 'iteratable', 'brand', '{\"image_one\":\"64afbd9dd16901689238941.png\"}', 'light', 0, NULL, '2023-07-13 03:02:21', '2023-07-13 03:02:21'),
(272, 'iteratable', 'brand', '{\"image_one\":\"64afbda3c9a7c1689238947.png\"}', 'light', 0, NULL, '2023-07-13 03:02:27', '2023-07-13 03:02:27'),
(273, 'iteratable', 'brand', '{\"image_one\":\"64afbda8b7a6f1689238952.png\"}', 'light', 0, NULL, '2023-07-13 03:02:32', '2023-07-13 03:02:32'),
(274, 'iteratable', 'brand', '{\"image_one\":\"64afbdae2e3a11689238958.png\"}', 'light', 0, NULL, '2023-07-13 03:02:38', '2023-07-13 03:02:38'),
(275, 'iteratable', 'socials', '{\"icon\":\"fab fa-facebook-f\",\"link\":\"#\"}', 'light', 0, NULL, '2023-07-13 03:51:33', '2023-07-13 03:51:33'),
(276, 'iteratable', 'socials', '{\"icon\":\"fab fa-twitter\",\"link\":\"#\"}', 'light', 0, NULL, '2023-07-13 03:51:43', '2023-07-13 03:51:43'),
(277, 'iteratable', 'socials', '{\"icon\":\"fab fa-linkedin-in\",\"link\":\"#\"}', 'light', 0, NULL, '2023-07-13 03:51:56', '2023-07-13 03:51:56'),
(278, 'iteratable', 'socials', '{\"icon\":\"fab fa-pinterest-p\",\"link\":\"#\"}', 'light', 0, NULL, '2023-07-13 03:52:09', '2023-07-13 03:52:09'),
(279, 'non_iteratable', 'contact', '{\"section_header\":\"Contact\",\"title\":\"We\'d Love to Hear From You\",\"color_text_for_title\":\"Hear From You\",\"email\":\"support@company.com\",\"phone\":\"+01857319149\",\"address\":\"HQ 10\\/3A Zamzam Tower, Alwal Street Newyork\",\"form_header\":\"Love to hear from you, Get in touch\",\"color_text_for_form_header\":\"Get in touch\"}', 'light', 0, NULL, '2023-07-13 05:16:58', '2023-07-13 05:16:58'),
(280, 'non_iteratable', 'auth', '{\"title\":\"Title Here\",\"image_one\":\"64afde43ea2dc1689247299.png\"}', 'light', 0, NULL, '2023-07-13 05:21:40', '2023-07-13 05:21:40'),
(299, 'non_iteratable', 'banner', '{\"title\":\"Trade Smarter, Not Harder. Your Forex Trading Destination\",\"color_text_for_title\":\"Trading Destination\",\"button_text\":\"Get Started\",\"button_text_link\":\"#0\",\"button_two_text\":\"Register Now\",\"button_two_text_link\":\"#0\",\"description\":\"Lorem ipsum dolor sit amet consectetur adipisicing elit. alias sit eius quia facere numquam est harum cupiditate, repellat odit repudiandae voluptatem qui in sequi dicta magni quisquam neque fugit suscipit.\",\"image_one\":\"65004336ec0641694516022.png\",\"image_two\":null}', 'blue', 0, NULL, '2023-09-12 03:54:31', '2023-09-14 00:16:32'),
(300, 'non_iteratable', 'about', '{\"title\":\"Join the trademax community\",\"color_text_for_title\":\"trademax community\",\"button_text\":\"Learn More\",\"button_link\":\"#0\",\"repeater\":[{\"repeater\":\"Learn how to read and forecast the markets\"},{\"repeater\":\"Learn how to read and forecast the markets\"},{\"repeater\":\"Learn how to read and forecast the markets\"},{\"repeater\":\"Learn how to read and forecast the markets\"}],\"description\":\"Lorem ipsum dolor sit amet consectetur adipisicing elit. tempora perferendis molestias nesciunt. accusamus excepturi sint dicta velit nulla quod, natus dolorum inventore alias voluptates voluptatem, iste nemo consequuntur esse.\",\"image_one\":\"65003a3c3dfc51694513724.jpg\",\"image_two\":\"65003a3c5e4e11694513724.png\"}', 'blue', 0, NULL, '2023-09-12 04:15:24', '2023-09-12 04:15:24'),
(301, 'non_iteratable', 'footer', '{\"footer_short_details\":\"Lorem ipsum dolor sit amet consectetur, adipisicing elit. fugiat delectus maxime nisi explicabo doloribus minima similique, quia hic accusantium laudantium odit voluptatibus molestiae enim aut repellat.\",\"image_one\":\"6502efb2257cd1694691250.png\"}', 'blue', 0, NULL, '2023-09-12 04:15:55', '2023-09-14 05:34:10'),
(302, 'non_iteratable', 'auth', '{\"title\":\"Login\",\"image_one\":\"650048a5329ff1694517413.png\"}', 'blue', 0, NULL, '2023-09-12 05:16:53', '2023-09-12 05:17:05'),
(303, 'non_iteratable', 'overview', '{\"title\":\"Place your trades on best conditions\",\"color_text_for_title\":\"best conditions\",\"image_one\":\"6502ab1cbc48b1694673692.png\"}', 'blue', 0, NULL, '2023-09-14 00:41:33', '2023-09-14 00:41:33'),
(304, 'iteratable', 'overview', '{\"title\":\"Minimum investment amount\",\"counter\":\"$5\"}', 'blue', 0, NULL, '2023-09-14 00:41:50', '2023-09-14 00:41:50'),
(305, 'iteratable', 'overview', '{\"title\":\"Minimum trade amount\",\"counter\":\"10\"}', 'blue', 0, NULL, '2023-09-14 00:42:02', '2023-09-14 02:49:09'),
(306, 'iteratable', 'overview', '{\"title\":\"Virtual money on your Demo account\",\"counter\":\"10K\"}', 'blue', 0, NULL, '2023-09-14 00:42:11', '2023-09-14 02:48:33'),
(307, 'iteratable', 'overview', '{\"title\":\"Payment methods\",\"counter\":\"50+\"}', 'blue', 0, NULL, '2023-09-14 00:42:25', '2023-09-14 00:42:25'),
(308, 'iteratable', 'overview', '{\"title\":\"No commission on deposit and withdrawal\",\"counter\":\"$10\"}', 'blue', 0, NULL, '2023-09-14 00:42:44', '2023-09-14 00:42:44'),
(309, 'iteratable', 'overview', '{\"title\":\"Assets for trading\",\"counter\":\"100+\"}', 'blue', 0, NULL, '2023-09-14 00:42:55', '2023-09-14 00:42:55'),
(310, 'non_iteratable', 'why_choose_us', '{\"title\":\"Why Choose TradeMax\",\"color_text_for_title\":\"TradeMax\"}', 'blue', 0, NULL, '2023-09-14 02:56:52', '2023-09-14 02:56:52'),
(311, 'iteratable', 'why_choose_us', '{\"title\":\"Meta trader\",\"description\":\"Lorem ipsum dolor sit amet, consectetur adipisicing elit. minus adipisci eos, molestias, itaque aspernatur quis saepe recusandae fugit.\",\"image_one\":\"6502cbaf12e901694682031.png\"}', 'blue', 0, NULL, '2023-09-14 03:00:31', '2023-09-14 03:00:31'),
(312, 'iteratable', 'why_choose_us', '{\"title\":\"Competetive pricing\",\"description\":\"Lorem ipsum dolor sit amet, consectetur adipisicing elit. minus adipisci eos, molestias, itaque aspernatur quis saepe recusandae fugit.\",\"image_one\":\"6502cbba62ebf1694682042.png\"}', 'blue', 0, NULL, '2023-09-14 03:00:42', '2023-09-14 03:00:42'),
(313, 'iteratable', 'why_choose_us', '{\"title\":\"Active trader\",\"description\":\"Lorem ipsum dolor sit amet, consectetur adipisicing elit. minus adipisci eos, molestias, itaque aspernatur quis saepe recusandae fugit.\",\"image_one\":\"6502cbc45d8b61694682052.png\"}', 'blue', 0, NULL, '2023-09-14 03:00:52', '2023-09-14 03:00:52'),
(314, 'iteratable', 'why_choose_us', '{\"title\":\"Meta trader\",\"description\":\"Lorem ipsum dolor sit amet, consectetur adipisicing elit. minus adipisci eos, molestias, itaque aspernatur quis saepe recusandae fugit.\",\"image_one\":\"6502cbce2e4c71694682062.png\"}', 'blue', 0, NULL, '2023-09-14 03:01:02', '2023-09-14 03:01:02'),
(315, 'iteratable', 'why_choose_us', '{\"title\":\"Competetive pricing\",\"description\":\"Lorem ipsum dolor sit amet, consectetur adipisicing elit. minus adipisci eos, molestias, itaque aspernatur quis saepe recusandae fugit.\",\"image_one\":\"6502cbfb088021694682107.png\"}', 'blue', 0, NULL, '2023-09-14 03:01:47', '2023-09-14 03:01:47'),
(316, 'iteratable', 'why_choose_us', '{\"title\":\"Active trader\",\"description\":\"Lorem ipsum dolor sit amet, consectetur adipisicing elit. minus adipisci eos, molestias, itaque aspernatur quis saepe recusandae fugit.\",\"image_one\":\"6502cc045c6bb1694682116.png\"}', 'blue', 0, NULL, '2023-09-14 03:01:56', '2023-09-14 03:01:56'),
(317, 'iteratable', 'why_choose_us', '{\"title\":\"Meta trader\",\"description\":\"Lorem ipsum dolor sit amet, consectetur adipisicing elit. minus adipisci eos, molestias, itaque aspernatur quis saepe recusandae fugit.\",\"image_one\":\"6502ccfbd12871694682363.png\"}', 'blue', 0, NULL, '2023-09-14 03:06:03', '2023-09-14 03:06:03'),
(318, 'iteratable', 'why_choose_us', '{\"title\":\"Competetive pricing\",\"description\":\"Lorem ipsum dolor sit amet, consectetur adipisicing elit. minus adipisci eos, molestias, itaque aspernatur quis saepe recusandae fugit.\",\"image_one\":\"6502cd0614a361694682374.png\"}', 'blue', 0, NULL, '2023-09-14 03:06:14', '2023-09-14 03:06:14'),
(319, 'non_iteratable', 'plans', '{\"title\":\"Our Best Packages\",\"color_text_for_title\":\"Packages\",\"image_one\":\"6502ce2a348531694682666.png\"}', 'blue', 0, NULL, '2023-09-14 03:11:06', '2023-09-14 03:11:06'),
(320, 'non_iteratable', 'how_works', '{\"title\":\"Started Trading With TradeMax\",\"color_text_for_title\":\"With TradeMax\"}', 'blue', 0, NULL, '2023-09-14 03:26:08', '2023-09-14 03:26:08'),
(321, 'iteratable', 'how_works', '{\"title\":\"Open an account\",\"description\":\"Lorem ipsum dolor sit amet consectetur adipisicing elit. repudiandae laboriosam.\"}', 'blue', 0, NULL, '2023-09-14 03:28:32', '2023-09-16 04:08:06'),
(322, 'iteratable', 'how_works', '{\"title\":\"Deposit amount\",\"description\":\"Lorem ipsum dolor sit amet consectetur adipisicing elit. repudiandae laboriosam.\"}', 'blue', 0, NULL, '2023-09-14 03:28:43', '2023-09-16 04:08:11'),
(323, 'iteratable', 'how_works', '{\"title\":\"Start trading\",\"description\":\"Lorem ipsum dolor sit amet consectetur adipisicing elit. repudiandae laboriosam.\"}', 'blue', 0, NULL, '2023-09-14 03:28:54', '2023-09-16 04:08:16'),
(324, 'non_iteratable', 'benefits', '{\"title\":\"Everything you need to fast track your trading\",\"color_text_for_title\":\"track your trading\",\"image_one\":\"6502d461e5f871694684257.png\"}', 'blue', 0, NULL, '2023-09-14 03:31:10', '2023-09-14 03:37:38'),
(325, 'iteratable', 'benefits', '{\"title\":\"Top technical analysis\",\"description\":\"Architecto doloremque neque asperiores laboriosam voluptatum doloribus aperiam.\",\"image_one\":\"6502d319bc16e1694683929.png\"}', 'blue', 0, NULL, '2023-09-14 03:32:09', '2023-09-14 03:32:09'),
(326, 'iteratable', 'benefits', '{\"title\":\"Direct email and sms* signals\",\"description\":\"Architecto doloremque neque asperiores laboriosam voluptatum doloribus aperiam.\",\"image_one\":\"6502d324d6af11694683940.png\"}', 'blue', 0, NULL, '2023-09-14 03:32:20', '2023-09-14 03:32:20'),
(327, 'iteratable', 'benefits', '{\"title\":\"High performance\",\"description\":\"Architecto doloremque neque asperiores laboriosam voluptatum doloribus aperiam.\",\"image_one\":\"6502d32f7eed81694683951.png\"}', 'blue', 0, NULL, '2023-09-14 03:32:31', '2023-09-14 03:32:31'),
(328, 'iteratable', 'benefits', '{\"title\":\"Highly recommended\",\"description\":\"Architecto doloremque neque asperiores laboriosam voluptatum doloribus aperiam.\",\"image_one\":\"6502d33a564131694683962.png\"}', 'blue', 0, NULL, '2023-09-14 03:32:42', '2023-09-14 03:32:42'),
(329, 'iteratable', 'benefits', '{\"title\":\"Full expert support\",\"description\":\"Architecto doloremque neque asperiores laboriosam voluptatum doloribus aperiam.\",\"image_one\":\"6502d34455f221694683972.png\"}', 'blue', 0, NULL, '2023-09-14 03:32:52', '2023-09-14 03:32:52'),
(330, 'iteratable', 'benefits', '{\"title\":\"Join a growing community\",\"description\":\"Architecto doloremque neque asperiores laboriosam voluptatum doloribus aperiam.\",\"image_one\":\"6502d34e3f8321694683982.png\"}', 'blue', 0, NULL, '2023-09-14 03:33:02', '2023-09-14 03:33:02'),
(331, 'non_iteratable', 'trade', '{\"title\":\"Join the trademax community\",\"color_text_for_title\":\"trademax community\",\"button_text\":\"Start Trading\",\"button_link\":\"register\",\"image_one\":\"6502dd486a7c81694686536.png\"}', 'blue', 0, NULL, '2023-09-14 04:13:47', '2023-09-14 04:15:36'),
(332, 'non_iteratable', 'referral', '{\"title\":\"Our Forex Trading Referral\",\"color_text_for_title\":\"Trading Referral\",\"description\":\"Lorem ipsum dolor sit amet consectetur adipisicing elit. error iste quae soluta adipisci eum. consequuntur tenetur doloremque commodi unde. praesentium fugiat molestias ipsum distinctio at reprehenderit porro.\",\"image_one\":\"6502e201223fb1694687745.png\"}', 'blue', 0, NULL, '2023-09-14 04:35:45', '2023-09-14 04:35:45'),
(333, 'non_iteratable', 'team', '{\"title\":\"Our Forex Trading Specialist\",\"color_text_for_title\":\"Trading Specialist\"}', 'blue', 0, NULL, '2023-09-14 04:37:35', '2023-09-14 04:37:35'),
(334, 'iteratable', 'team', '{\"member_name\":\"John Doe\",\"designation\":\"Ceo, Toto company\",\"facebook_url\":\"#\",\"twitter_url\":\"#\",\"linkedin_url\":\"#\",\"instagram_url\":\"#\",\"image_one\":\"6506a41f7bf0b1694934047.jpg\"}', 'blue', 0, NULL, '2023-09-14 04:39:20', '2023-09-17 01:00:47'),
(335, 'iteratable', 'team', '{\"member_name\":\"Edwen Yeark\",\"designation\":\"Ceo, Toto company\",\"facebook_url\":\"#\",\"twitter_url\":\"#\",\"linkedin_url\":\"#\",\"instagram_url\":\"#\",\"image_one\":\"6506a4273b5121694934055.jpg\"}', 'blue', 0, NULL, '2023-09-14 04:39:43', '2023-09-17 01:00:55'),
(336, 'iteratable', 'team', '{\"member_name\":\"Tara Lava\",\"designation\":\"Ceo, Toto company\",\"facebook_url\":\"#\",\"twitter_url\":\"#\",\"linkedin_url\":\"#\",\"instagram_url\":\"#\",\"image_one\":\"6506a42e77c511694934062.jpg\"}', 'blue', 0, NULL, '2023-09-14 04:40:04', '2023-09-17 01:01:02'),
(337, 'iteratable', 'team', '{\"member_name\":\"Gayan Tara\",\"designation\":\"Ceo, Toto company\",\"facebook_url\":\"#\",\"twitter_url\":\"#\",\"linkedin_url\":\"#\",\"instagram_url\":\"#\",\"image_one\":\"6506a436d7d141694934070.jpg\"}', 'blue', 0, NULL, '2023-09-14 04:40:40', '2023-09-17 01:01:10'),
(338, 'iteratable', 'team', '{\"member_name\":\"Habba Jama\",\"designation\":\"Ceo, Toto company\",\"facebook_url\":\"#\",\"twitter_url\":\"#\",\"linkedin_url\":\"#\",\"instagram_url\":\"#\",\"image_one\":\"6506a43cf32401694934076.jpg\"}', 'blue', 0, NULL, '2023-09-14 04:40:56', '2023-09-17 01:01:17'),
(339, 'non_iteratable', 'testimonial', '{\"title\":\"What Our Customer Says\",\"color_text_for_title\":\"Customer Says\",\"image_two\":\"6502e4130d7cd1694688275.png\"}', 'blue', 0, NULL, '2023-09-14 04:44:35', '2023-09-14 04:44:35'),
(340, 'iteratable', 'testimonial', '{\"client_name\":\"John Doe\",\"designation\":\"Toto Company\",\"description\":\"Lorem ipsum dolor sit amet, consectetur adipisicing elit. aperiam, cum! sit ad officiis eius et inventore optio eligendi. totam suscipit in obcaecati repellat quisquam deleniti facilis excepturi ad minima consectetur consectetur adipisicing.\",\"image_one\":\"6502e44b400c31694688331.jpg\"}', 'blue', 0, NULL, '2023-09-14 04:45:31', '2023-09-14 04:45:31'),
(341, 'iteratable', 'testimonial', '{\"client_name\":\"Templa Jakka\",\"designation\":\"Toto Company\",\"description\":\"Lorem ipsum dolor sit amet, consectetur adipisicing elit. aperiam, cum! sit ad officiis eius et inventore optio eligendi. totam suscipit in obcaecati repellat quisquam deleniti facilis excepturi ad minima consectetur consectetur adipisicing.\",\"image_one\":\"6502e4700b3b81694688368.jpg\"}', 'blue', 0, NULL, '2023-09-14 04:46:08', '2023-09-14 04:46:08'),
(342, 'iteratable', 'testimonial', '{\"client_name\":\"Embar Tata\",\"designation\":\"Toto Company\",\"description\":\"Lorem ipsum dolor sit amet, consectetur adipisicing elit. aperiam, cum! sit ad officiis eius et inventore optio eligendi. totam suscipit in obcaecati repellat quisquam deleniti facilis excepturi ad minima consectetur consectetur adipisicing.\",\"image_one\":\"6502e480416b11694688384.jpg\"}', 'blue', 0, NULL, '2023-09-14 04:46:24', '2023-09-14 04:46:24'),
(343, 'iteratable', 'testimonial', '{\"client_name\":\"Ubaya Rembar\",\"designation\":\"Toto Company\",\"description\":\"Lorem ipsum dolor sit amet, consectetur adipisicing elit. aperiam, cum! sit ad officiis eius et inventore optio eligendi. totam suscipit in obcaecati repellat quisquam deleniti facilis excepturi ad minima consectetur consectetur adipisicing.\",\"image_one\":\"6502e49af03fc1694688410.jpg\"}', 'blue', 0, NULL, '2023-09-14 04:46:51', '2023-09-14 04:46:51'),
(344, 'iteratable', 'testimonial', '{\"client_name\":\"Warama Kala\",\"designation\":\"Toto Company\",\"description\":\"Lorem ipsum dolor sit amet, consectetur adipisicing elit. aperiam, cum! sit ad officiis eius et inventore optio eligendi. totam suscipit in obcaecati repellat quisquam deleniti facilis excepturi ad minima consectetur consectetur adipisicing.\",\"image_one\":\"6502e4b419f5d1694688436.jpg\"}', 'blue', 0, NULL, '2023-09-14 04:47:16', '2023-09-14 04:47:16'),
(345, 'non_iteratable', 'blog', '{\"title\":\"Our Latest News\",\"color_text_for_title\":\"News\"}', 'blue', 0, NULL, '2023-09-14 05:17:50', '2023-09-14 05:17:50'),
(346, 'iteratable', 'blog', '{\"blog_title\":\"Temporibus, dignissimos aperiam accusamus dolore\",\"short_description\":\"Lorem ipsum dolor sit amet consectetur adipisicing elit. Harum atque alias voluptatibus, vero eaque ab saepe excepturi, rem, debitis culpa fugiat necessitatibus. Blanditiis, cum non quaerat aperiam delectus, voluptate asperiores nostrum esse soluta maiores architecto tempora nesciunt provident eveniet hic quam neque veritatis. Atque fugiat magnam, eveniet quidem nam dolor voluptate molestiae laudantium corporis quod, at voluptatibus asperiores architecto libero necessitatibus labore ex sit eaque alias consectetur a, nobis quo ipsum amet! Veniam officiis, qui repellendus sunt ullam quidem debitis vero beatae eveniet officia ipsa molestiae nemo illo eius iusto dolorum consequatur a itaque? Aliquid eveniet facere facilis est ipsam.\",\"description\":\"<div style=\\\"color: rgb(255, 255, 255); background-color: rgb(18, 32, 51); font-family: Consolas, &quot;Courier New&quot;, monospace; line-height: 19px; white-space: pre;\\\">Lorem ipsum dolor sit amet consectetur adipisicing elit. Harum atque alias voluptatibus, vero eaque ab saepe excepturi, rem, debitis culpa fugiat necessitatibus. Blanditiis, cum non quaerat aperiam delectus, voluptate asperiores nostrum esse soluta maiores architecto tempora nesciunt provident eveniet hic quam neque veritatis. Atque fugiat magnam, eveniet quidem nam dolor voluptate molestiae laudantium corporis quod, at voluptatibus asperiores architecto libero necessitatibus labore ex sit eaque alias consectetur a, nobis quo ipsum amet! Veniam officiis, qui repellendus sunt ullam quidem debitis vero beatae eveniet officia ipsa molestiae nemo illo eius iusto dolorum consequatur a itaque? Aliquid eveniet facere facilis est ipsam.<\\/div>\",\"image_one\":\"6502ec2c473a51694690348.jpg\"}', 'blue', 0, NULL, '2023-09-14 05:19:08', '2023-09-14 05:19:08'),
(347, 'iteratable', 'blog', '{\"blog_title\":\"Temporibus, dignissimos aperiam accusamus dolore\",\"short_description\":\"Lorem ipsum dolor sit amet consectetur adipisicing elit. Harum atque alias voluptatibus, vero eaque ab saepe excepturi, rem, debitis culpa fugiat necessitatibus. Blanditiis, cum non quaerat aperiam delectus, voluptate asperiores nostrum esse soluta maiores architecto tempora nesciunt provident eveniet hic quam neque veritatis. Atque fugiat magnam, eveniet quidem nam dolor voluptate molestiae laudantium corporis quod, at voluptatibus asperiores architecto libero necessitatibus labore ex sit eaque alias consectetur a, nobis quo ipsum amet! Veniam officiis, qui repellendus sunt ullam quidem debitis vero beatae eveniet officia ipsa molestiae nemo illo eius iusto dolorum consequatur a itaque? Aliquid eveniet facere facilis est ipsam.\",\"description\":\"<div style=\\\"color: rgb(255, 255, 255); background-color: rgb(18, 32, 51); font-family: Consolas, &quot;Courier New&quot;, monospace; line-height: 19px; white-space: pre;\\\">Lorem ipsum dolor sit amet consectetur adipisicing elit. Harum atque alias voluptatibus, vero eaque ab saepe excepturi, rem, debitis culpa fugiat necessitatibus. Blanditiis, cum non quaerat aperiam delectus, voluptate asperiores nostrum esse soluta maiores architecto tempora nesciunt provident eveniet hic quam neque veritatis. Atque fugiat magnam, eveniet quidem nam dolor voluptate molestiae laudantium corporis quod, at voluptatibus asperiores architecto libero necessitatibus labore ex sit eaque alias consectetur a, nobis quo ipsum amet! Veniam officiis, qui repellendus sunt ullam quidem debitis vero beatae eveniet officia ipsa molestiae nemo illo eius iusto dolorum consequatur a itaque? Aliquid eveniet facere facilis est ipsam.<\\/div>\",\"image_one\":\"6502ec471e76a1694690375.jpg\"}', 'blue', 0, NULL, '2023-09-14 05:19:35', '2023-09-14 05:19:35'),
(348, 'iteratable', 'blog', '{\"blog_title\":\"Temporibus, dignissimos aperiam accusamus dolore\",\"short_description\":\"Lorem ipsum dolor sit amet consectetur adipisicing elit. Harum atque alias voluptatibus, vero eaque ab saepe excepturi, rem, debitis culpa fugiat necessitatibus. Blanditiis, cum non quaerat aperiam delectus, voluptate asperiores nostrum esse soluta maiores architecto tempora nesciunt provident eveniet hic quam neque veritatis. Atque fugiat magnam, eveniet quidem nam dolor voluptate molestiae laudantium corporis quod, at voluptatibus asperiores architecto libero necessitatibus labore ex sit eaque alias consectetur a, nobis quo ipsum amet! Veniam officiis, qui repellendus sunt ullam quidem debitis vero beatae eveniet officia ipsa molestiae nemo illo eius iusto dolorum consequatur a itaque? Aliquid eveniet facere facilis est ipsam.\",\"description\":\"<p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Harum atque alias voluptatibus, vero eaque ab saepe excepturi, rem, debitis culpa fugiat necessitatibus. Blanditiis, cum non quaerat aperiam delectus, voluptate asperiores nostrum esse soluta maiores architecto tempora nesciunt provident eveniet hic quam neque veritatis. Atque fugiat magnam, eveniet quidem nam dolor voluptate molestiae laudantium corporis quod, at voluptatibus asperiores architecto libero necessitatibus labore ex sit eaque alias consectetur a, nobis quo ipsum amet! Veniam officiis, qui repellendus sunt ullam quidem debitis vero beatae eveniet officia ipsa molestiae nemo illo eius iusto dolorum consequatur a itaque? Aliquid eveniet facere facilis est ipsam.<br><\\/p>\",\"image_one\":\"6502ec5381d511694690387.jpg\"}', 'blue', 0, NULL, '2023-09-14 05:19:47', '2023-09-14 05:19:47'),
(349, 'iteratable', 'brand', '{\"image_one\":\"6502edf078a2e1694690800.png\"}', 'blue', 0, NULL, '2023-09-14 05:26:40', '2023-09-14 05:26:40'),
(350, 'iteratable', 'brand', '{\"image_one\":\"6502edf8a7e551694690808.png\"}', 'blue', 0, NULL, '2023-09-14 05:26:48', '2023-09-14 05:26:48'),
(351, 'iteratable', 'brand', '{\"image_one\":\"6502edff8b9a71694690815.png\"}', 'blue', 0, NULL, '2023-09-14 05:26:55', '2023-09-14 05:26:55'),
(352, 'iteratable', 'brand', '{\"image_one\":\"6502ee05124621694690821.png\"}', 'blue', 0, NULL, '2023-09-14 05:27:01', '2023-09-14 05:27:01'),
(353, 'iteratable', 'brand', '{\"image_one\":\"6502ee09998b51694690825.png\"}', 'blue', 0, NULL, '2023-09-14 05:27:05', '2023-09-14 05:27:05'),
(354, 'iteratable', 'brand', '{\"image_one\":\"6502ee1b119a01694690843.png\"}', 'blue', 0, NULL, '2023-09-14 05:27:23', '2023-09-14 05:27:23'),
(355, 'iteratable', 'brand', '{\"image_one\":\"6502ee20623581694690848.png\"}', 'blue', 0, NULL, '2023-09-14 05:27:28', '2023-09-14 05:27:28'),
(356, 'iteratable', 'brand', '{\"image_one\":\"6502ee2566c1f1694690853.png\"}', 'blue', 0, NULL, '2023-09-14 05:27:33', '2023-09-14 05:27:33'),
(357, 'iteratable', 'brand', '{\"image_one\":\"6502ee2ac5e3d1694690858.png\"}', 'blue', 0, NULL, '2023-09-14 05:27:38', '2023-09-14 05:27:38'),
(358, 'iteratable', 'socials', '{\"icon\":\"fab fa-facebook-f\",\"link\":\"#\"}', 'blue', 0, NULL, '2023-09-14 05:35:08', '2023-09-14 05:35:08'),
(359, 'iteratable', 'socials', '{\"icon\":\"fab fa-twitter\",\"link\":\"#\"}', 'blue', 0, NULL, '2023-09-14 05:35:29', '2023-09-14 05:35:29'),
(360, 'iteratable', 'socials', '{\"icon\":\"fab fa-linkedin-in\",\"link\":\"#\"}', 'blue', 0, NULL, '2023-09-14 05:38:04', '2023-09-14 05:38:04'),
(361, 'iteratable', 'socials', '{\"icon\":\"fab fa-instagram\",\"link\":\"#\"}', 'blue', 0, NULL, '2023-09-14 05:38:15', '2023-09-14 05:38:15'),
(362, 'iteratable', 'footer', '{\"title\":\"MARGIN TRADING DISCLAIMER\",\"description\":\"Trading foreign exchange and CFD\'s on margin carries a high level of risk, and may not be suitable for all investors. The high degree of leverage can work against you as well as for you. Before deciding to invest in foreign exchange you should carefully consider your investment objectives, level of experience, and risk appetite. The possibility exists that you could sustain a loss of some or all of your initial investment and therefore you should not invest money that you cannot afford to lose. You should be aware of all the risks associated with foreign exchange and CFD\'s trading, and seek advice from an independent financial advisor if you have any doubts.\"}', 'blue', 0, NULL, '2023-09-14 06:39:57', '2023-09-14 06:39:57'),
(363, 'iteratable', 'footer', '{\"title\":\"SITE DISCLAIMER\",\"description\":\"Trading foreign exchange and CFD\'s on margin carries a high level of risk, and may not be suitable for all investors. The high degree of leverage can work against you as well as for you. Before deciding to invest in foreign exchange you should carefully consider your investment objectives, level of experience, and risk appetite. The possibility exists that you could sustain a loss of some or all of your initial investment and therefore you should not invest money that you cannot afford to lose. You should be aware of all the risks associated with foreign exchange and CFD\'s trading, and seek advice from an independent financial advisor if you have any doubts.\"}', 'blue', 0, NULL, '2023-09-14 06:40:04', '2023-09-14 06:40:04'),
(364, 'iteratable', 'footer', '{\"title\":\"SOFTWARE DISCLAIMER\",\"description\":\"Trading foreign exchange and CFD\'s on margin carries a high level of risk, and may not be suitable for all investors. The high degree of leverage can work against you as well as for you. Before deciding to invest in foreign exchange you should carefully consider your investment objectives, level of experience, and risk appetite. The possibility exists that you could sustain a loss of some or all of your initial investment and therefore you should not invest money that you cannot afford to lose. You should be aware of all the risks associated with foreign exchange and CFD\'s trading, and seek advice from an independent financial advisor if you have any doubts.\"}', 'blue', 0, NULL, '2023-09-14 06:40:12', '2023-09-14 06:40:12'),
(365, 'iteratable', 'footer', '{\"title\":\"TRADEMARKS DISCLAIMER\",\"description\":\"Trading foreign exchange and CFD\'s on margin carries a high level of risk, and may not be suitable for all investors. The high degree of leverage can work against you as well as for you. Before deciding to invest in foreign exchange you should carefully consider your investment objectives, level of experience, and risk appetite. The possibility exists that you could sustain a loss of some or all of your initial investment and therefore you should not invest money that you cannot afford to lose. You should be aware of all the risks associated with foreign exchange and CFD\'s trading, and seek advice from an independent financial advisor if you have any doubts.\"}', 'blue', 0, NULL, '2023-09-14 06:40:20', '2023-09-14 06:40:20'),
(366, 'non_iteratable', 'contact', '{\"section_header\":\"Contact\",\"title\":\"We\'d Love to Hear From You\",\"color_text_for_title\":\"Hear From You\",\"email\":\"support@company.com\",\"phone\":\"+880 692487515\",\"address\":\"HQ 10\\/3A Zamzam Tower, Alwal Street Newyork\",\"form_header\":\"Love to hear from you, Get in touch\",\"color_text_for_form_header\":\"Get in touch\"}', 'blue', 0, NULL, '2023-09-16 04:42:25', '2023-09-16 04:42:25'),
(367, 'iteratable', 'how_works', '{\"title\":\"Pick Strategy\",\"description\":\"Browse & Select Winning Algorithms\\r\\nDescription: From AI forecasts to technical indicators, choose the strategy that fits your style.\"}', 'default', 0, NULL, '2025-12-03 09:46:58', '2025-12-03 09:46:58'),
(368, 'iteratable', 'how_works', '{\"title\":\"Connect Securely\",\"description\":\"Link Your Trading Account\\r\\nDescription: One-click secure connection to your broker for seamless autotrading.\"}', 'default', 0, NULL, '2025-12-03 09:47:17', '2025-12-03 09:47:17'),
(369, 'iteratable', 'how_works', '{\"title\":\"Launch & Profit\",\"description\":\"Start Autotrading and Withdraw Earnings\\r\\nDescription: Activate and let the algo work. Withdraw your share of profits easily.\"}', 'default', 0, NULL, '2025-12-03 09:47:33', '2025-12-03 09:47:33');

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_copy_trading_executions`
--

CREATE TABLE `sp_copy_trading_executions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `trader_position_id` bigint(20) UNSIGNED NOT NULL COMMENT 'Original ExecutionPosition from trader',
  `trader_id` bigint(20) UNSIGNED NOT NULL,
  `follower_id` bigint(20) UNSIGNED NOT NULL,
  `subscription_id` bigint(20) UNSIGNED NOT NULL,
  `follower_position_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'Created ExecutionPosition for follower',
  `follower_connection_id` bigint(20) UNSIGNED NOT NULL,
  `copied_at` timestamp NULL DEFAULT NULL,
  `status` enum('pending','executed','failed','closed') NOT NULL DEFAULT 'pending',
  `error_message` text DEFAULT NULL,
  `risk_multiplier_used` decimal(10,4) DEFAULT NULL,
  `original_quantity` decimal(20,8) NOT NULL,
  `copied_quantity` decimal(20,8) NOT NULL,
  `calculation_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Details about how quantity was calculated' CHECK (json_valid(`calculation_details`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_copy_trading_settings`
--

CREATE TABLE `sp_copy_trading_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `admin_id` bigint(20) UNSIGNED DEFAULT NULL,
  `is_admin_owned` tinyint(1) NOT NULL DEFAULT 0,
  `is_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `min_followers_balance` decimal(20,8) DEFAULT NULL COMMENT 'Minimum balance required to follow',
  `max_copiers` int(11) DEFAULT NULL COMMENT 'Max number of followers allowed',
  `risk_multiplier_default` decimal(10,4) NOT NULL DEFAULT 1.0000 COMMENT 'Default risk multiplier for followers',
  `allow_manual_trades` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Whether to copy manual trades',
  `allow_auto_trades` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Whether to copy signal-based trades',
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Additional settings' CHECK (json_valid(`settings`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `sp_copy_trading_settings`
--

INSERT INTO `sp_copy_trading_settings` (`id`, `user_id`, `admin_id`, `is_admin_owned`, `is_enabled`, `min_followers_balance`, `max_copiers`, `risk_multiplier_default`, `allow_manual_trades`, `allow_auto_trades`, `settings`, `created_at`, `updated_at`) VALUES
(1, NULL, 1, 1, 0, NULL, NULL, 1.0000, 1, 1, NULL, '2025-12-01 07:21:21', '2025-12-01 07:21:21'),
(2, 2, NULL, 0, 0, NULL, NULL, 1.0000, 1, 1, NULL, '2025-12-01 09:43:09', '2025-12-01 09:43:09');

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_copy_trading_subscriptions`
--

CREATE TABLE `sp_copy_trading_subscriptions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `trader_id` bigint(20) UNSIGNED NOT NULL COMMENT 'User who is being copied',
  `follower_id` bigint(20) UNSIGNED NOT NULL COMMENT 'User who is copying',
  `copy_mode` enum('easy','advanced') NOT NULL DEFAULT 'easy' COMMENT 'Copy trading mode',
  `risk_multiplier` decimal(10,4) NOT NULL DEFAULT 1.0000 COMMENT 'Position size multiplier for easy mode (0.1 to 10.0)',
  `max_position_size` decimal(20,8) DEFAULT NULL COMMENT 'Max USD per copied trade',
  `connection_id` bigint(20) UNSIGNED NOT NULL COMMENT 'Which ExecutionConnection to use for copying',
  `copy_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Advanced mode settings: method, percentage, fixed_quantity, min_quantity, max_quantity' CHECK (json_valid(`copy_settings`)),
  `preset_id` bigint(20) UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `subscribed_at` timestamp NULL DEFAULT NULL,
  `unsubscribed_at` timestamp NULL DEFAULT NULL,
  `stats` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Copied trades count, total PnL, etc.' CHECK (json_valid(`stats`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_currency_pairs`
--

CREATE TABLE `sp_currency_pairs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `status` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_dashboard_signals`
--

CREATE TABLE `sp_dashboard_signals` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `plan_id` bigint(20) UNSIGNED NOT NULL,
  `signal_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_deposits`
--

CREATE TABLE `sp_deposits` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `gateway_id` bigint(20) UNSIGNED NOT NULL,
  `trx` varchar(255) NOT NULL,
  `amount` decimal(28,8) NOT NULL DEFAULT 0.00000000,
  `rate` decimal(28,8) NOT NULL DEFAULT 0.00000000,
  `charge` decimal(28,8) NOT NULL DEFAULT 0.00000000,
  `total` decimal(28,8) NOT NULL DEFAULT 0.00000000,
  `status` int(11) NOT NULL COMMENT '1 => approved,2 => pending,3 => rejected',
  `type` int(11) NOT NULL DEFAULT 1 COMMENT '0=>manual , 1 => autometic',
  `payment_proof` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_execution_analytics`
--

CREATE TABLE `sp_execution_analytics` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `connection_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'For user analytics',
  `admin_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'For admin analytics',
  `date` date NOT NULL,
  `total_trades` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `winning_trades` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `losing_trades` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `total_pnl` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `win_rate` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Percentage',
  `profit_factor` decimal(10,4) NOT NULL DEFAULT 0.0000,
  `max_drawdown` decimal(10,4) NOT NULL DEFAULT 0.0000 COMMENT 'Percentage',
  `balance` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `equity` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `additional_metrics` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Sharpe ratio, expectancy, etc.' CHECK (json_valid(`additional_metrics`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_execution_connections`
--

CREATE TABLE `sp_execution_connections` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'For user-owned connections',
  `admin_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'For admin-owned connections',
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
  `preset_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_execution_logs`
--

CREATE TABLE `sp_execution_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `signal_id` bigint(20) UNSIGNED NOT NULL,
  `connection_id` bigint(20) UNSIGNED NOT NULL,
  `execution_type` enum('market','limit') NOT NULL DEFAULT 'market',
  `order_id` varchar(255) DEFAULT NULL COMMENT 'Exchange/broker order ID',
  `symbol` varchar(255) NOT NULL,
  `direction` enum('buy','sell') NOT NULL,
  `quantity` decimal(20,8) NOT NULL,
  `entry_price` decimal(20,8) DEFAULT NULL,
  `slippage` decimal(8,4) DEFAULT NULL COMMENT 'Actual slippage in pips',
  `latency_ms` int(10) UNSIGNED DEFAULT NULL COMMENT 'Time from signal received to execution',
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
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_execution_notifications`
--

CREATE TABLE `sp_execution_notifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'For user notifications',
  `admin_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'For admin notifications',
  `connection_id` bigint(20) UNSIGNED NOT NULL,
  `signal_id` bigint(20) UNSIGNED DEFAULT NULL,
  `position_id` bigint(20) UNSIGNED DEFAULT NULL,
  `type` enum('execution','open','close','error','sl_hit','tp_hit','liquidation') NOT NULL DEFAULT 'execution',
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Additional data' CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_execution_positions`
--

CREATE TABLE `sp_execution_positions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `signal_id` bigint(20) UNSIGNED NOT NULL,
  `connection_id` bigint(20) UNSIGNED NOT NULL,
  `execution_log_id` bigint(20) UNSIGNED NOT NULL,
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
  `tp3_closed_qty` decimal(20,8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_filter_strategies`
--

CREATE TABLE `sp_filter_strategies` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_by_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `visibility` enum('PRIVATE','PUBLIC_MARKETPLACE') NOT NULL DEFAULT 'PRIVATE',
  `clonable` tinyint(1) NOT NULL DEFAULT 1,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`config`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_frontend_components`
--

CREATE TABLE `sp_frontend_components` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `builder` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_frontend_media`
--

CREATE TABLE `sp_frontend_media` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `content_id` bigint(20) UNSIGNED NOT NULL,
  `media` text DEFAULT NULL,
  `section_name` varchar(255) DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_gateways`
--

CREATE TABLE `sp_gateways` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `parameter` text DEFAULT NULL,
  `type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '0=manual, 1=autometic',
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `rate` decimal(28,8) NOT NULL DEFAULT 1.00000000,
  `charge` decimal(28,8) NOT NULL DEFAULT 0.00000000,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `sp_gateways`
--

INSERT INTO `sp_gateways` (`id`, `name`, `image`, `parameter`, `type`, `status`, `rate`, `charge`, `created_at`, `updated_at`) VALUES
(1, 'stripe', '64b2444f3a2f41689404495.jpg', '{\"stripe_client_id\":\"pk_test_51JPpg8Ep0youpBChKWG5eyrUnj7weSPl3FlIaU8unUrqOfoA0aAFGJq6biVmcZBjKdD7Jf7HXmH6DKaxjtJsWn9200QGc9BTns\",\"stripe_client_secret\":\"sk_test_51JPpg8Ep0youpBChPXaj1T1fXk5zhCTg8A8hCCF5sfrFm7C0n7pIYfGoMptc1xqoFb5Mnro56LB3jn21JsTvnGtP00ZTxKIaJ8\",\"gateway_currency\":\"USD\"}', 1, 1, 1.00000000, 0.00000000, NULL, '2023-07-15 01:01:35'),
(2, 'paypal', '64b2446fb28f11689404527.jpg', '{\"gateway_currency\":\"USD\",\"mode\":\"sandbox\",\"paypal_client_id\":\"AQtCVGlS22wqYBGWPHW1a6aAVuUcFwSOWzUGoRvsbth2vUNNxrekowLwrYRwIYLMAetedRPu3hKMO57C\",\"paypal_client_secret\":\"EMksMmpKq5xfnJP3So7fVTyjghVV4mtUa70qsXbNAiw3nBF3ir6ENXZasxT-3cPDZ8ZXJX0DaggQFptv\"}', 1, 1, 1.00000000, 0.00000000, NULL, '2023-07-15 01:02:07'),
(3, 'vougepay', '64b24481157231689404545.jpg', '{\"gateway_currency\":\"NGN\",\"vouguepay_merchant_id\":\"sandbox_760e43f202878f651659820234cad9\"}', 1, 1, 1.00000000, 0.00000000, NULL, '2023-07-15 01:02:25'),
(4, 'razorpay', '64b24493c01af1689404563.jpg', '{\"gateway_currency\":\"INR\",\"razor_key\":\"rzp_test_r8XIwoQUldfBxE\",\"razor_secret\":\"G21wL8EwAeO2RIEg33qC1WjM\"}', 1, 1, 70.00000000, 0.00000000, NULL, '2023-07-15 01:02:43'),
(5, 'coinpayments', '64b244a2ee0441689404578.jpg', '{\"public_key\":\"38c42afde7a4259c137e59f355e49347418c191acbc8fd7d28bf2cf6ba6fc8ef\",\"private_key\":\"2f01fbce867E045eF996f7edde430cDb36D5c9D8bC7B8A6B952f69E9209a95eb\",\"merchant_id\":\"f734643e069b93f729f13159274dcc4c\",\"gateway_currency\":\"USD\"}', 1, 1, 1.00000000, 0.00000000, NULL, '2023-07-15 01:02:58'),
(6, 'mollie', '64b244b286d5e1689404594.jpg', '{\"mollie_key\":\"test_kABABRpec7dDq2hurGdUEGR6hvsghJ\",\"gateway_currency\":\"USD\"}', 1, 1, 1.00000000, 0.00000000, NULL, '2023-07-15 01:03:14'),
(7, 'nowpayments', '64b244c130d961689404609.jpg', '{\"nowpay_key\":\"GWNX9GQ-3MG4ZB3-Q4QCSD1-WMHR73F\",\"gateway_currency\":\"USD\"}', 1, 1, 1.00000000, 0.00000000, NULL, '2023-07-15 01:03:29'),
(8, 'flutterwave', '64b244cd7a61b1689404621.jpg', '{\"public_key\":\"FLWPUBK_TEST-SANDBOXDEMOKEY-X\",\"reference_key\":\"titanic-48981487343MDI0NzMx\",\"gateway_currency\":\"USD\"}', 1, 1, 1.00000000, 0.00000000, NULL, '2023-07-15 01:03:41'),
(9, 'paystack', '64b244d7a87151689404631.jpg', '{\"paystack_key\":\"pk_test_267cf8526cf89ade67da431da3b2b59b04e9c4e0\",\"gateway_currency\":\"ZAR\"}', 1, 1, 1.00000000, 0.00000000, NULL, '2023-07-15 01:03:51'),
(10, 'paghiper', '64b244e3683081689404643.jpg', '{\"paghiper_key\":\"apk_46328544-sawGwZEtyqZMGMpdKtqmmaIJzjLfZKMR\",\"token\":\"8G5O29JZNSDG851P6NTFVK3C7HS2T981PEQRNARB24RB\",\"gateway_currency\":\"BRL\"}', 1, 1, 1.00000000, 0.00000000, NULL, '2023-07-15 01:04:03'),
(11, 'gourl_BTC', '64b244f847b451689404664.jpg', '{\"gateway_currency\":\"BTC\",\"public_key\":\"25654AAo79c3Bitcoin77BTCPUBqwIefT1j9fqqMwUtMI0huVL\",\"private_key\":\"25654AAo79c3Bitcoin77BTCPRV0JG7w3jg0Tc5Pfi34U8o5JE\"}', 1, 1, 1.00000000, 0.00000000, NULL, '2023-07-15 01:04:24'),
(12, 'perfectmoney', '64b24538918c51689404728.jpg', '{\"accountid\":\"asdasd\",\"passphrase\":\"asdasd\",\"gateway_currency\":\"USD\"}', 1, 1, 1.00000000, 0.00000000, NULL, '2023-07-15 01:05:28'),
(13, 'mercadopago', '64b2454b45ded1689404747.jpg', '{\"access_token\":\"TEST-705032440135962-041006-ad2e021853f22338fe1a4db9f64d1491-421886156\",\"public_key\":\"TEST-fa4d869f-468f-4dfd-2620-8b520f888a32\",\"gateway_currency\":\"USD\"}', 1, 1, 1.00000000, 0.00000000, NULL, '2023-07-15 01:05:47'),
(14, 'paytm', '64b2455700d171689404759.jpg', '{\"gateway_currency\":\"INR\",\"mode\":\"0\",\"merchant_id\":\"DIY12386817555501617\",\"merchant_key\":\"bKMfNxPPf_QdZppa\",\"merchant_website\":\"asasdas\",\"merchant_channel\":\"web\",\"merchant_industry\":\"asdasdasd\"}', 1, 1, 1.00000000, 0.00000000, NULL, '2023-07-15 01:05:59'),
(15, 'city-bank', '64b2459c4a6381689404828.jpg', '{\"name\":\"City Bank\",\"account_number\":\"sdasd\",\"routing_number\":\"sdfsdf\",\"branch_name\":\"sdfsdf\",\"gateway_currency\":\"USD\",\"gateway_type\":\"bank\",\"qr_code\":\"\",\"payment_instruction\":\"<p>test<\\/p>\\r\\n\\r\\n<p>asdasdasd<\\/p>\",\"user_proof_param\":[{\"field_name\":\"asdasd\",\"type\":\"file\",\"validation\":\"required\"}]}', 0, 0, 5.00000000, 5.00000000, '2023-03-08 23:38:57', '2025-11-17 23:51:47'),
(16, 'bitcoin', '6425684e97dc31680173134.png', '{\"name\":\"bitcoin\",\"account_number\":null,\"routing_number\":null,\"branch_name\":null,\"gateway_currency\":\"usd\",\"gateway_type\":\"crypto\",\"qr_code\":\"6425684e9e6071680173134.png\",\"payment_instruction\":\"<p>bitcoin<\\/p>\",\"user_proof_param\":[{\"field_name\":\"trx\",\"type\":\"text\",\"validation\":\"required\"},{\"field_name\":\"image\",\"type\":\"file\",\"validation\":\"required\"},{\"field_name\":\"te\",\"type\":\"file\",\"validation\":\"nullable\"}]}', 0, 0, 1.00000000, 10.00000000, '2023-03-30 04:45:34', '2025-11-17 23:51:46');

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_jobs`
--

CREATE TABLE `sp_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_languages`
--

CREATE TABLE `sp_languages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `status` int(11) NOT NULL COMMENT '0=>default,1=>changeable',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `sp_languages`
--

INSERT INTO `sp_languages` (`id`, `name`, `code`, `status`, `created_at`, `updated_at`) VALUES
(1, 'English', 'en', 0, '2023-02-28 01:27:04', '2023-02-28 01:27:04'),
(5, 'Spanish', 'sp', 1, '2023-03-21 02:54:41', '2023-03-21 02:54:41'),
(7, 'Indonesia', 'id', 1, '2025-12-02 19:14:12', '2025-12-02 19:14:12');

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_login_securities`
--

CREATE TABLE `sp_login_securities` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `google2fa_enable` tinyint(1) NOT NULL,
  `google2fa_secret` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_markets`
--

CREATE TABLE `sp_markets` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `status` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_message_parsing_patterns`
--

CREATE TABLE `sp_message_parsing_patterns` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `channel_source_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'NULL for global patterns',
  `user_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'NULL for admin-created global patterns',
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `pattern_type` enum('regex','template','ai_fallback') NOT NULL DEFAULT 'regex',
  `pattern_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Pattern definitions, field mappings, regex rules' CHECK (json_valid(`pattern_config`)),
  `priority` int(11) NOT NULL DEFAULT 0 COMMENT 'Higher priority tried first',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `success_count` int(11) NOT NULL DEFAULT 0 COMMENT 'Number of successful parses',
  `failure_count` int(11) NOT NULL DEFAULT 0 COMMENT 'Number of failed parses',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `sp_message_parsing_patterns`
--

INSERT INTO `sp_message_parsing_patterns` (`id`, `channel_source_id`, `user_id`, `name`, `description`, `pattern_type`, `pattern_config`, `priority`, `is_active`, `success_count`, `failure_count`, `created_at`, `updated_at`) VALUES
(2, NULL, NULL, 'Forex Auto Format', 'Format: buy USA100 q=0.01 tt=0.46% td=0.46%', 'regex', '{\"required_fields\":[\"currency_pair\",\"direction\"],\"patterns\":{\"direction\":[\"\\/(?:^|\\\\s)(buy|sell)(?:\\\\s|$)\\/i\"],\"symbol\":[\"\\/(?:^|\\\\s)([A-Z0-9]{2,10})(?:\\\\s|q=)\\/i\",\"\\/(?:buy|sell)\\\\s+([A-Z0-9]{2,10})(?:\\\\s|q=)\\/i\"],\"currency_pair\":[\"\\/(?:buy|sell)\\\\s+([A-Z0-9]{2,10})(?:\\\\s|q=)\\/i\"],\"tp\":[\"\\/tt\\\\s*=\\\\s*([\\\\d.]+)\\\\s*%\\/i\",\"\\/tp\\\\s*=\\\\s*([\\\\d.]+)\\\\s*%\\/i\"],\"sl\":[\"\\/td\\\\s*=\\\\s*([\\\\d.]+)\\\\s*%\\/i\",\"\\/sl\\\\s*=\\\\s*([\\\\d.]+)\\\\s*%\\/i\"]},\"confidence_weights\":{\"currency_pair\":20,\"symbol\":20,\"direction\":20,\"tp\":20,\"sl\":20}}', 80, 1, 0, 0, '2025-12-01 06:59:35', '2025-12-01 06:59:35'),
(3, NULL, NULL, 'Gold Multi-TP Format', 'Format: Gold SELL Limit, TP1/TP2/TP3/TP MAX, entry range', 'regex', '{\"required_fields\":[\"currency_pair\",\"direction\"],\"patterns\":{\"direction\":[\"\\/(?:^|\\\\s)(Gold|XAU|GOLD)\\\\s+(BUY|SELL|LONG|SHORT)\\/i\",\"\\/(BUY|SELL|LONG|SHORT)\\\\s+(?:Limit|Market)\\/i\"],\"symbol\":[\"\\/(Gold|XAU|GOLD)\\\\s+(?:BUY|SELL|LONG|SHORT)\\/i\",\"\\/(?:^|\\\\s)(Gold|XAU|GOLD)(?:\\\\s|$)\\/i\"],\"currency_pair\":[\"\\/(Gold|XAU|GOLD)\\\\s+(?:BUY|SELL|LONG|SHORT)\\/i\",\"\\/(?:^|\\\\s)(Gold|XAU|GOLD)(?:\\\\s|$)\\/i\"],\"open_price\":[\"\\/(?:^|\\\\n)\\\\s*(\\\\d{3,5}\\\\.?\\\\d*)\\\\s*-\\\\s*(\\\\d{3,5}\\\\.?\\\\d*)\\\\s*(?:\\\\n|$)\\/\",\"\\/(?:^|\\\\n)\\\\s*(\\\\d{3,5}\\\\.?\\\\d*)\\\\s*(?:\\\\n|$)\\/\"],\"tp\":[\"\\/TP\\\\s*MAX\\\\s*[:\\\\s]*([\\\\d.]+)\\/i\",\"\\/TP\\\\s*(\\\\d+)\\\\s*[:\\\\s]*([\\\\d.]+)\\/i\",\"\\/TP\\\\s*[:\\\\s]*([\\\\d.]+)\\/i\"],\"sl\":[\"\\/STOP\\\\s*LOSS\\\\s*[:\\\\s]*([\\\\d.]+)\\/i\",\"\\/SL\\\\s*[:\\\\s]*([\\\\d.]+)\\/i\"]},\"confidence_weights\":{\"currency_pair\":20,\"symbol\":20,\"direction\":20,\"open_price\":20,\"tp\":15,\"sl\":15}}', 85, 1, 0, 0, '2025-12-01 06:59:40', '2025-12-01 06:59:40'),
(4, NULL, NULL, 'Line-Based Template', 'Each field on separate line', 'template', '{\"required_fields\":[\"currency_pair\",\"direction\"],\"line_mappings\":[{\"field\":\"currency_pair\",\"pattern\":\"\\/([A-Z]{2,10}\\\\\\/[A-Z]{2,10})\\/\",\"match_index\":1},{\"field\":\"direction\",\"pattern\":\"\\/(BUY|SELL)\\/i\",\"match_index\":1},{\"field\":\"open_price\",\"pattern\":\"\\/([\\\\d,]+\\\\.?\\\\d*)\\/\",\"match_index\":1},{\"field\":\"sl\",\"pattern\":\"\\/([\\\\d,]+\\\\.?\\\\d*)\\/\",\"match_index\":1},{\"field\":\"tp\",\"pattern\":\"\\/([\\\\d,]+\\\\.?\\\\d*)\\/\",\"match_index\":1}]}', 40, 1, 0, 0, '2025-12-01 06:59:43', '2025-12-01 06:59:43'),
(5, NULL, NULL, 'Standard Signal Format', 'Common format: PAIR DIRECTION ENTRY SL TP', 'regex', '{\"required_fields\":[\"currency_pair\",\"direction\",\"open_price\"],\"patterns\":{\"currency_pair\":[\"\\/([A-Z]{2,10}\\\\\\/[A-Z]{2,10})\\/\",\"\\/([A-Z]{2,10}-[A-Z]{2,10})\\/\"],\"direction\":[\"\\/(BUY|SELL)\\/i\",\"\\/(LONG|SHORT)\\/i\"],\"open_price\":[\"\\/ENTRY[:\\\\s]*([\\\\d,]+\\\\.?\\\\d*)\\/i\",\"\\/PRICE[:\\\\s]*([\\\\d,]+\\\\.?\\\\d*)\\/i\"],\"sl\":[\"\\/SL[:\\\\s]*([\\\\d,]+\\\\.?\\\\d*)\\/i\",\"\\/STOP[:\\\\s]*LOSS[:\\\\s]*([\\\\d,]+\\\\.?\\\\d*)\\/i\"],\"tp\":[\"\\/TP[:\\\\s]*([\\\\d,]+\\\\.?\\\\d*)\\/i\",\"\\/TAKE[:\\\\s]*PROFIT[:\\\\s]*([\\\\d,]+\\\\.?\\\\d*)\\/i\"]},\"confidence_weights\":{\"currency_pair\":15,\"direction\":15,\"open_price\":20,\"sl\":15,\"tp\":15}}', 50, 1, 0, 0, '2025-12-01 06:59:46', '2025-12-01 06:59:46');

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_migrations`
--

CREATE TABLE `sp_migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `sp_migrations`
--

INSERT INTO `sp_migrations` (`id`, `migration`, `batch`) VALUES
(1, '2019_12_14_000001_create_personal_access_tokens_table', 1),
(2, '2023_02_22_104311_create_admins_table', 1),
(3, '2023_02_22_111101_create_configurations_table', 1),
(4, '2023_02_22_121218_create_gateways_table', 1),
(5, '2023_02_25_120246_create_users_table', 1),
(6, '2023_02_26_063704_create_admin_password_resets_table', 1),
(7, '2023_02_26_081605_create_deposits_table', 1),
(8, '2023_02_26_082931_create_withdraw_gateways_table', 1),
(9, '2023_02_26_084519_create_withdraws_table', 1),
(10, '2023_02_26_085002_create_tickets_table', 1),
(11, '2023_02_26_085317_create_ticket_replies_table', 1),
(12, '2023_02_26_085758_create_payments_table', 1),
(13, '2023_02_26_090322_create_user_logs_table', 1),
(14, '2023_02_26_091028_create_languages_table', 1),
(16, '2023_02_26_094347_create_permission_tables', 1),
(17, '2023_02_26_105957_create_pages_table', 1),
(18, '2023_02_26_110308_create_page_sections_table', 1),
(19, '2023_02_28_064341_create_contents_table', 2),
(20, '2023_02_28_104449_create_frontend_components_table', 3),
(21, '2023_03_07_113921_create_referrals_table', 4),
(22, '2023_03_11_064120_create_subscribers_table', 5),
(24, '2023_03_11_101143_create_templates_table', 6),
(25, '2021_08_15_113006_create_crypto_payments_table', 7),
(26, '2023_03_16_054806_create_plan_subscriptions_table', 7),
(27, '2023_03_16_055015_create_login_securities_table', 8),
(28, '2023_03_16_055208_create_transactions_table', 9),
(29, '2023_03_16_055624_create_plans_table', 10),
(30, '2023_03_16_072610_create_markets_table', 11),
(31, '2023_03_16_080329_create_currency_pairs_table', 12),
(32, '2023_03_16_080524_create_time_frames_table', 13),
(33, '2023_03_16_080747_create_signals_table', 14),
(34, '2023_03_16_081326_create_plan_signals_table', 15),
(35, '2023_03_18_052943_create_dashboard_signals_table', 16),
(36, '2023_03_18_053717_create_user_signals_table', 17),
(37, '2023_03_20_091115_create_money_transfers_table', 18),
(38, '2023_03_20_095030_create_referral_commissions_table', 19),
(39, '2023_03_22_060754_create_jobs_table', 20),
(40, '2023_04_02_045912_create_frontend_media_table', 21),
(41, '2025_01_27_100000_create_channel_sources_table', 22),
(42, '2025_01_27_100001_create_channel_messages_table', 22),
(43, '2025_01_27_100002_add_channel_source_fields_to_signals_table', 22),
(44, '2025_11_11_100003_create_message_parsing_patterns_table', 23),
(45, '2025_11_11_100004_create_signal_analytics_table', 23),
(46, '2025_11_11_100000_extend_channel_sources_for_admin_ownership', 24),
(47, '2025_11_13_160910_make_user_id_nullable_in_channel_sources_table', 25),
(49, '2025_11_13_161451_change_config_column_to_text_in_channel_sources_table', 26),
(50, '2025_11_11_100001_create_channel_source_users_table', 27),
(51, '2025_11_11_100002_create_channel_source_plans_table', 27),
(52, '2025_01_28_100000_create_ai_configurations_table', 28),
(53, '2025_01_19_100000_add_parser_preference_to_channel_sources', 29),
(54, '2025_01_29_100000_create_execution_connections_table', 30),
(55, '2025_01_29_100001_create_execution_logs_table', 30),
(56, '2025_01_29_100002_create_execution_positions_table', 30),
(57, '2025_01_29_100003_create_execution_analytics_table', 30),
(58, '2025_01_29_100004_create_execution_notifications_table', 30),
(59, '2025_01_30_100000_create_copy_trading_settings_table', 31),
(60, '2025_01_30_100001_create_copy_trading_subscriptions_table', 31),
(61, '2025_01_30_100002_create_copy_trading_executions_table', 31),
(62, '2025_01_30_100003_add_admin_support_to_copy_trading_settings', 32),
(63, '2023_02_26_092247_create_notifications_table', 33),
(64, '2025_11_07_000000_add_manage_addon_permission', 34),
(65, '2025_01_29_100000_create_trading_presets_table', 35),
(66, '2025_01_29_100001_add_preset_id_to_execution_connections', 35),
(67, '2025_01_29_100002_add_preset_id_to_copy_trading_subscriptions', 35),
(68, '2025_01_29_100003_add_default_preset_id_to_users', 35),
(69, '2025_01_29_100004_add_multi_tp_to_execution_positions', 35),
(70, '2025_01_29_100005_add_structure_sl_to_signals', 35),
(71, '2025_01_29_100006_add_preset_id_to_trading_bots', 35),
(72, '2025_12_02_000001_create_openrouter_configurations_table', 36),
(73, '2025_12_02_000002_create_openrouter_models_table', 36),
(74, '2025_12_02_105002_create_filter_strategies_table', 37),
(75, '2025_12_02_111940_create_ai_model_profiles_table', 38),
(76, '2025_12_02_105100_add_filter_strategy_to_trading_presets', 39),
(77, '2025_12_02_111949_add_ai_fields_to_trading_presets', 40),
(78, '2025_12_03_020013_add_backend_theme_to_configurations_table', 41),
(79, '2025_12_03_100000_create_ai_providers_table', 42),
(80, '2025_12_03_100001_create_ai_connections_table', 42),
(81, '2025_12_03_100002_create_ai_connection_usage_table', 42),
(82, '2025_12_03_100003_add_default_connection_foreign_key', 42),
(83, '2025_12_03_120000_create_ai_parsing_profiles_table', 43),
(84, '2025_12_03_120001_migrate_ai_configurations_to_connections', 43),
(85, '2025_12_03_130000_create_translation_settings_table', 43),
(86, '2025_12_03_140000_refactor_ai_model_profiles_to_use_connections', 43),
(87, '2025_12_03_150000_refactor_openrouter_to_use_ai_connections', 43),
(88, '2025_12_02_120000_create_srm_signal_provider_metrics_table', 44),
(89, '2025_12_02_120001_create_srm_predictions_table', 44),
(90, '2025_12_02_120002_create_srm_model_versions_table', 44),
(91, '2025_12_02_120003_create_srm_ab_tests_table', 44),
(92, '2025_12_02_120004_create_srm_ab_test_assignments_table', 44),
(93, '2025_12_02_120005_add_srm_fields_to_execution_logs_table', 45),
(94, '2025_12_02_120006_add_srm_fields_to_execution_positions_table', 45);

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_model_has_permissions`
--

CREATE TABLE `sp_model_has_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_model_has_roles`
--

CREATE TABLE `sp_model_has_roles` (
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `sp_model_has_roles`
--

INSERT INTO `sp_model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES
(1, 'App\\Models\\Admin', 1),
(2, 'App\\Models\\Admin', 2),
(2, 'App\\Models\\Admin', 3);

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_money_transfers`
--

CREATE TABLE `sp_money_transfers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `sender_id` bigint(20) UNSIGNED NOT NULL,
  `receiver_id` bigint(20) UNSIGNED NOT NULL,
  `trx` varchar(255) NOT NULL,
  `details` varchar(255) NOT NULL,
  `amount` decimal(28,8) NOT NULL,
  `charge` decimal(28,8) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_notifications`
--

CREATE TABLE `sp_notifications` (
  `id` char(36) NOT NULL,
  `type` varchar(255) NOT NULL,
  `notifiable_type` varchar(255) NOT NULL,
  `notifiable_id` varchar(255) NOT NULL,
  `data` text NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `sp_notifications`
--

INSERT INTO `sp_notifications` (`id`, `type`, `notifiable_type`, `notifiable_id`, `data`, `read_at`, `created_at`, `updated_at`) VALUES
('4e4f5986-4b7c-4b83-9a23-338c2dc17fd8', 'App\\Notifications\\DepositNotification', 'App\\Models\\Admin', '1', '{\"user_id\":1,\"message\":\"dddfff has made a deposit amount of : 5,000.00 USD\",\"url\":\"http:\\/\\/localhost\\/codecanyon\\/signal\\/signal_v5\\/Scripts\\/admin\\/deposit\\/online\"}', '2025-11-16 10:22:38', '2023-09-17 05:17:58', '2025-11-16 10:22:38'),
('5e492950-1cfc-4f03-98b5-02a3ee2bbc43', 'App\\Notifications\\DepositNotification', 'App\\Models\\Admin', '1', '{\"user_id\":1,\"message\":\"dddfff has made a deposit amount of : 1,500.00 USD\",\"url\":\"http:\\/\\/localhost\\/codecanyon\\/signal\\/signal_v5\\/Scripts\\/admin\\/deposit\\/online\"}', '2025-11-16 10:22:44', '2023-09-17 05:14:55', '2025-11-16 10:22:44'),
('ae4d120a-5651-44e5-9fe6-2948f505375a', 'App\\Notifications\\TicketNotification', 'App\\Models\\Admin', '1', '{\"ticket_id\":1,\"message\":\"dddfff has opened a ticket\",\"url\":\"http:\\/\\/localhost\\/codecanyon\\/signal\\/signal_v5\\/Scripts\\/admin\\/ticket\\/filter\\/pending\"}', '2025-11-16 10:22:45', '2023-09-17 05:07:18', '2025-11-16 10:22:45'),
('b992017a-0f62-4500-be57-d502ff99a9a4', 'App\\Notifications\\PlanSubscriptionNotification', 'App\\Models\\Admin', '1', '{\"user_id\":1,\"message\":\"dddfff has taken a subscription plan name : Pro max\",\"url\":\"\"}', '2025-11-16 10:22:36', '2023-09-17 05:28:41', '2025-11-16 10:22:36'),
('e64b7d17-1ec0-42a3-a3d7-694c8818f2f0', 'App\\Notifications\\WithdrawNotification', 'App\\Models\\Admin', '1', '{\"user_id\":1,\"message\":\"dddfff has made a Withdraw amount of : 2000\",\"url\":\"http:\\/\\/localhost\\/codecanyon\\/signal\\/signal_v5\\/Scripts\\/admin\\/withdraw\\/pending\"}', '2025-11-16 10:22:37', '2023-09-17 05:18:35', '2025-11-16 10:22:37'),
('f3589eec-8542-49aa-b3d2-61416b3b244e', 'App\\Notifications\\WithdrawNotification', 'App\\Models\\Admin', '1', '{\"user_id\":1,\"message\":\"dddfff has made a Withdraw amount of : 1500\",\"url\":\"http:\\/\\/localhost\\/codecanyon\\/signal\\/signal_v5\\/Scripts\\/admin\\/withdraw\\/pending\"}', '2025-11-16 10:22:42', '2023-09-17 05:15:36', '2025-11-16 10:22:42');

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_openrouter_configurations`
--

CREATE TABLE `sp_openrouter_configurations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `api_key` text NOT NULL,
  `model_id` varchar(255) NOT NULL,
  `site_url` varchar(255) DEFAULT NULL,
  `site_name` varchar(255) DEFAULT NULL,
  `temperature` decimal(3,2) NOT NULL DEFAULT 0.30,
  `max_tokens` int(11) NOT NULL DEFAULT 500,
  `timeout` int(11) NOT NULL DEFAULT 30,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `ai_connection_id` bigint(20) UNSIGNED DEFAULT NULL,
  `priority` int(11) NOT NULL DEFAULT 50,
  `use_for_parsing` tinyint(1) NOT NULL DEFAULT 0,
  `use_for_analysis` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_openrouter_models`
--

CREATE TABLE `sp_openrouter_models` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `model_id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `provider` varchar(255) NOT NULL,
  `context_length` int(11) DEFAULT NULL,
  `pricing` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`pricing`)),
  `modalities` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`modalities`)),
  `is_available` tinyint(1) NOT NULL DEFAULT 1,
  `last_synced_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_pages`
--

CREATE TABLE `sp_pages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `order` int(11) NOT NULL,
  `is_dropdown` tinyint(1) NOT NULL,
  `seo_keywords` text DEFAULT NULL,
  `seo_description` text DEFAULT NULL,
  `status` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `sp_pages`
--

INSERT INTO `sp_pages` (`id`, `name`, `slug`, `order`, `is_dropdown`, `seo_keywords`, `seo_description`, `status`, `created_at`, `updated_at`) VALUES
(3, 'Home', 'home', 1, 0, '[\"signal\",\"btc\",\"froex\",\"Binance\",\"Crypto\",\"Buy\",\"Sell\"]', 'Simple stock & cryptocurrency price forecasting console application, using PHP Machine', 1, '2023-03-07 05:36:18', '2023-04-04 05:20:40'),
(4, 'About', 'about', 2, 0, '[\"abouts\"]', 'abouts', 1, '2023-03-20 05:13:22', '2025-12-02 09:23:41'),
(5, 'Contact', 'contact', 4, 0, '[\"test\"]', 'adasdasdasdasd', 1, '2023-03-20 05:19:31', '2023-03-30 01:42:18'),
(6, 'Packages', 'packages', 3, 0, '[\"Abouts\"]', 'Abouts Us', 1, '2023-03-29 01:13:52', '2023-03-30 01:45:52'),
(8, 'Blog', 'blog', 10, 1, '[\"10\",\"blog\"]', 'blog', 1, '2023-03-30 00:34:33', '2023-03-30 01:39:42');

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_page_sections`
--

CREATE TABLE `sp_page_sections` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `page_id` bigint(20) UNSIGNED NOT NULL,
  `sections` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `sp_page_sections`
--

INSERT INTO `sp_page_sections` (`id`, `page_id`, `sections`, `created_at`, `updated_at`) VALUES
(76, 8, '\"blog\"', '2023-03-30 01:39:42', '2023-03-30 01:39:42'),
(77, 5, '\"contact\"', '2023-03-30 01:42:18', '2023-03-30 01:42:18'),
(79, 6, '\"plans\"', '2023-03-30 01:46:12', '2023-03-30 01:46:12'),
(233, 3, '\"overview\"', '2023-09-14 04:49:38', '2023-09-14 04:49:38'),
(234, 3, '\"why_choose_us\"', '2023-09-14 04:49:38', '2023-09-14 04:49:38'),
(235, 3, '\"plans\"', '2023-09-14 04:49:38', '2023-09-14 04:49:38'),
(236, 3, '\"how_works\"', '2023-09-14 04:49:38', '2023-09-14 04:49:38'),
(237, 3, '\"benefits\"', '2023-09-14 04:49:38', '2023-09-14 04:49:38'),
(238, 3, '\"trade\"', '2023-09-14 04:49:38', '2023-09-14 04:49:38'),
(239, 3, '\"about\"', '2023-09-14 04:49:38', '2023-09-14 04:49:38'),
(240, 3, '\"referral\"', '2023-09-14 04:49:38', '2023-09-14 04:49:38'),
(241, 3, '\"team\"', '2023-09-14 04:49:38', '2023-09-14 04:49:38'),
(242, 3, '\"testimonial\"', '2023-09-14 04:49:38', '2023-09-14 04:49:38'),
(243, 3, '\"blog\"', '2023-09-14 04:49:38', '2023-09-14 04:49:38'),
(244, 4, '\"about\"', '2025-12-02 09:23:41', '2025-12-02 09:23:41'),
(245, 4, '\"overview\"', '2025-12-02 09:23:41', '2025-12-02 09:23:41'),
(246, 4, '\"how_works\"', '2025-12-02 09:23:41', '2025-12-02 09:23:41'),
(247, 4, '\"team\"', '2025-12-02 09:23:41', '2025-12-02 09:23:41');

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_payments`
--

CREATE TABLE `sp_payments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `plan_id` int(11) NOT NULL,
  `gateway_id` bigint(20) UNSIGNED NOT NULL,
  `trx` varchar(255) NOT NULL,
  `amount` decimal(28,8) NOT NULL,
  `rate` decimal(28,8) NOT NULL,
  `charge` decimal(28,8) NOT NULL,
  `total` decimal(28,8) NOT NULL,
  `status` int(11) NOT NULL COMMENT '1=>approved, 2=>pending, 3=>rejected',
  `type` int(11) DEFAULT NULL,
  `payment_proof` text DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `plan_expired_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_permissions`
--

CREATE TABLE `sp_permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `sp_permissions`
--

INSERT INTO `sp_permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'manage-admin', 'admin', NULL, NULL),
(2, 'manage-role', 'admin', NULL, NULL),
(3, 'manage-referral', 'admin', NULL, NULL),
(4, 'signal', 'admin', NULL, NULL),
(5, 'manage-plan', 'admin', NULL, NULL),
(6, 'manage-user', 'admin', NULL, NULL),
(7, 'manage-ticket', 'admin', NULL, NULL),
(8, 'manage-gateway', 'admin', NULL, NULL),
(9, 'payments', 'admin', NULL, NULL),
(10, 'manage-withdraw', 'admin', NULL, NULL),
(11, 'manage-deposit', 'admin', NULL, NULL),
(12, 'manage-theme', 'admin', NULL, NULL),
(13, 'manage-email', 'admin', NULL, NULL),
(14, 'manage-setting', 'admin', NULL, NULL),
(15, 'manage-language', 'admin', NULL, NULL),
(16, 'manage-logs', 'admin', NULL, NULL),
(17, 'manage-frontend', 'admin', NULL, NULL),
(18, 'manage-subscriber', 'admin', NULL, NULL),
(19, 'manage-report', 'admin', NULL, NULL),
(20, 'manage-addon', 'admin', '2025-12-01 12:48:04', '2025-12-01 12:48:04');

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_personal_access_tokens`
--

CREATE TABLE `sp_personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_plans`
--

CREATE TABLE `sp_plans` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `price` decimal(28,8) DEFAULT 0.00000000,
  `duration` int(11) DEFAULT 0,
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
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `sp_plans`
--

INSERT INTO `sp_plans` (`id`, `name`, `slug`, `price`, `duration`, `plan_type`, `price_type`, `feature`, `whatsapp`, `telegram`, `email`, `sms`, `dashboard`, `status`, `created_at`, `updated_at`) VALUES
(1, 'test', 'test', NULL, NULL, 'unlimited', 'free', '[\"asas\",\"asasas\"]', 0, 0, 1, 0, 0, 1, '2023-10-02 03:02:53', '2023-10-02 03:02:53'),
(2, 'Platinum', 'platinum', 500.00000000, 100, 'limited', 'paid', '[\"binance support\",\"premium support\",\"Forex\",\"crypto\"]', 0, 0, 1, 1, 1, 1, '2023-10-02 04:59:47', '2023-10-02 04:59:47'),
(3, 'Ultra super', 'ultra-super', 800.00000000, NULL, 'unlimited', 'paid', '[\"binance support\",\"premium\",\"crypto\",\"forex\"]', 0, 1, 1, 0, 1, 1, '2023-10-02 05:00:24', '2023-10-02 05:00:24');

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_plan_signals`
--

CREATE TABLE `sp_plan_signals` (
  `plan_id` bigint(20) UNSIGNED NOT NULL,
  `signal_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_plan_subscriptions`
--

CREATE TABLE `sp_plan_subscriptions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `plan_id` bigint(20) UNSIGNED NOT NULL,
  `is_current` tinyint(1) NOT NULL,
  `plan_expired_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_referrals`
--

CREATE TABLE `sp_referrals` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `type` varchar(255) NOT NULL,
  `level` text NOT NULL,
  `commission` text NOT NULL,
  `status` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `sp_referrals`
--

INSERT INTO `sp_referrals` (`id`, `type`, `level`, `commission`, `status`, `created_at`, `updated_at`) VALUES
(1, 'invest', '[\"level 1\",\"level 2\",\"level 3\",\"level 4\"]', '[\"5\",\"10\",\"15\",\"20\"]', 1, '2023-03-30 00:12:45', '2023-04-01 02:34:21'),
(2, 'interest', '[\"level 1\",\"level 2\",\"level 3\",\"level 4\"]', '[\"5\",\"10\",\"15\",\"20\"]', 1, '2023-03-30 00:18:29', '2023-04-01 02:44:55');

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_referral_commissions`
--

CREATE TABLE `sp_referral_commissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `commission_from` bigint(20) UNSIGNED NOT NULL,
  `commission_to` bigint(20) UNSIGNED NOT NULL,
  `amount` decimal(28,8) NOT NULL,
  `purpouse` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_roles`
--

CREATE TABLE `sp_roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `sp_roles`
--

INSERT INTO `sp_roles` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'Admin', 'admin', '2023-02-27 01:04:07', '2023-02-27 01:04:07'),
(2, 'broker', 'admin', '2023-03-11 00:58:52', '2023-03-11 00:58:52');

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_role_has_permissions`
--

CREATE TABLE `sp_role_has_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `sp_role_has_permissions`
--

INSERT INTO `sp_role_has_permissions` (`permission_id`, `role_id`) VALUES
(1, 1),
(2, 1),
(3, 1),
(4, 1),
(5, 1),
(6, 1),
(7, 1),
(8, 1),
(9, 1),
(10, 1),
(10, 2),
(11, 1),
(11, 2),
(12, 1),
(12, 2),
(13, 1),
(14, 1),
(15, 1),
(16, 1),
(17, 1),
(18, 1),
(19, 1),
(19, 2),
(20, 1);

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_signals`
--

CREATE TABLE `sp_signals` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `channel_source_id` bigint(20) UNSIGNED DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `time_frame_id` bigint(20) UNSIGNED NOT NULL,
  `currency_pair_id` bigint(20) UNSIGNED NOT NULL,
  `market_id` bigint(20) UNSIGNED NOT NULL,
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
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_signal_analytics`
--

CREATE TABLE `sp_signal_analytics` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `signal_id` bigint(20) UNSIGNED NOT NULL,
  `channel_source_id` bigint(20) UNSIGNED DEFAULT NULL,
  `plan_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'User yang menerima signal',
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
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_srm_ab_tests`
--

CREATE TABLE `sp_srm_ab_tests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL COMMENT 'Test name',
  `description` text DEFAULT NULL COMMENT 'Test description',
  `status` enum('draft','running','paused','completed','cancelled') NOT NULL DEFAULT 'draft' COMMENT 'Test status',
  `pilot_group_percentage` decimal(5,2) NOT NULL DEFAULT 10.00 COMMENT 'Percentage of users in pilot group',
  `test_duration_days` int(10) UNSIGNED NOT NULL DEFAULT 14 COMMENT 'Test duration in days',
  `control_logic` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Control group SRM logic (current production)' CHECK (json_valid(`control_logic`)),
  `pilot_logic` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Pilot group SRM logic (new logic to test)' CHECK (json_valid(`pilot_logic`)),
  `start_date` date DEFAULT NULL COMMENT 'Test start date',
  `end_date` date DEFAULT NULL COMMENT 'Test end date',
  `pilot_group_size` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Pilot group size',
  `control_group_size` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Control group size',
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
  `created_by_admin_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'Admin who created the test',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_srm_ab_test_assignments`
--

CREATE TABLE `sp_srm_ab_test_assignments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `ab_test_id` bigint(20) UNSIGNED NOT NULL COMMENT 'FK to srm_ab_tests',
  `user_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'FK to users',
  `connection_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'FK to execution_connections',
  `group_type` enum('pilot','control') NOT NULL COMMENT 'Group assignment',
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'When user was assigned'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_srm_model_versions`
--

CREATE TABLE `sp_srm_model_versions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `model_type` enum('slippage_prediction','performance_score','risk_optimization') NOT NULL COMMENT 'Type of ML model',
  `version` varchar(50) NOT NULL COMMENT 'Model version identifier',
  `status` enum('training','active','deprecated','testing') NOT NULL DEFAULT 'training' COMMENT 'Model status',
  `parameters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Model hyperparameters, weights, etc.' CHECK (json_valid(`parameters`)),
  `training_data_count` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Number of training samples',
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
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_srm_predictions`
--

CREATE TABLE `sp_srm_predictions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `execution_log_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'FK to execution_logs',
  `signal_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'FK to signals',
  `connection_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'FK to execution_connections',
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
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_srm_signal_provider_metrics`
--

CREATE TABLE `sp_srm_signal_provider_metrics` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `signal_provider_id` varchar(255) NOT NULL COMMENT 'channel_source_id or user_id',
  `signal_provider_type` enum('channel_source','user') NOT NULL COMMENT 'Type of signal provider',
  `period_start` date NOT NULL COMMENT 'Period start date',
  `period_end` date NOT NULL COMMENT 'Period end date',
  `period_type` enum('daily','weekly','monthly') NOT NULL DEFAULT 'daily' COMMENT 'Period type',
  `total_signals` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Total signals in period',
  `winning_signals` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Winning signals count',
  `losing_signals` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Losing signals count',
  `win_rate` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Win rate percentage',
  `avg_slippage` decimal(8,4) NOT NULL DEFAULT 0.0000 COMMENT 'Average slippage in pips',
  `max_slippage` decimal(8,4) NOT NULL DEFAULT 0.0000 COMMENT 'Maximum slippage in pips',
  `avg_latency_ms` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Average latency in milliseconds',
  `max_drawdown` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Maximum drawdown percentage',
  `reward_to_risk_ratio` decimal(8,4) NOT NULL DEFAULT 0.0000 COMMENT 'Reward to risk ratio',
  `sl_compliance_rate` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT 'SL compliance rate percentage',
  `performance_score` decimal(5,2) NOT NULL DEFAULT 50.00 COMMENT 'Performance score 0-100',
  `performance_score_previous` decimal(5,2) NOT NULL DEFAULT 50.00 COMMENT 'Previous performance score',
  `score_trend` enum('up','down','stable') NOT NULL DEFAULT 'stable' COMMENT 'Score trend',
  `calculated_at` timestamp NULL DEFAULT NULL COMMENT 'When metrics were calculated',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_subscribers`
--

CREATE TABLE `sp_subscribers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_templates`
--

CREATE TABLE `sp_templates` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `template` longtext DEFAULT NULL,
  `status` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `sp_templates`
--

INSERT INTO `sp_templates` (`id`, `name`, `subject`, `template`, `status`, `created_at`, `updated_at`) VALUES
(1, 'password_reset', 'verification', '<p>Hi %username%</p>\r\n\r\n<p>Your Verification Code is %code%</p>\r\n\r\n<p>Regard,</p>\r\n\r\n<p>%app_name%</p>', 1, NULL, '2023-03-21 04:38:36'),
(2, 'payment_successfull', 'Email For Payment Successfull', '<p>Hi %username%</p>\r\n\r\n<p><br></p>\r\n\r\n<p>Your plan name : %plan%</p>\r\n\r\n<p><span style=\"font-size:0.875rem;\">Your transaction id : </span>%trx%</p>\r\n\r\n<p>Amount: %amount%</p>\r\n\r\n<p>Currency: %currency%</p>\r\n\r\n<p><br></p>\r\n\r\n<p>Regards,</p>\r\n\r\n<p>%app_name%</p>', 1, NULL, '2023-04-01 01:13:24'),
(3, 'payment_received', 'Your Payment Has Been Received', '<p>Hi %username%</p><p><br></p><p><br></p><p>Regards</p><p>%app_name%</p>', 1, NULL, '2023-03-21 05:42:10'),
(4, 'verify_email', 'Verify Email', '<p>Hi %username%</p><p><br></p><p>Your Verification Code is %code%</p><p><br></p><p><br></p><p>Regrads,</p><p>%app_name%</p>', 1, NULL, '2023-03-30 05:01:13'),
(5, 'payment_confirmed', 'Payment Confirmation', '<p>Hi %username%</p><p>%trx%</p><p>%amount%</p><p><br></p><p>%charge%</p><p>%plan%</p><p>%currency%</p><p><br></p><p><br></p><p><br></p><p>Regards,</p><p>%app_name%</p>', 1, NULL, '2023-03-30 05:03:18'),
(6, 'payment_rejected', 'Payment Reject', '<p>Hi %username%</p><p>Transaction id: %trx%</p><p><span style=\"font-size: 0.875rem;\">amount:&nbsp;</span>%amount%</p><p>Charge: %charge%</p><p>Plan: %plan%</p><p>Currency: %currency%</p><p>Regards,</p><p>%app_name%</p>', 1, NULL, '2023-04-01 01:16:51'),
(7, 'withdraw_accepted', 'Wtihdraw Accepted', '<p>Hi %username%</p><p><br></p><p>Your payment amount: %amount%</p><p><span style=\"font-size: 0.875rem;\">Your method:&nbsp;</span><span style=\"font-size: 0.875rem;\">&nbsp;&nbsp;</span>%method%</p><p>currency : %currency%</p><p><br></p><p>Regards</p><p>%app_name%</p>', 1, NULL, '2023-04-01 01:15:21'),
(8, 'withdraw_rejected', 'Withdraw Rejected', '<p>HI %username%</p><p><br></p><p>Reason: %reason%</p><p>Method: %method%</p><p>Amount: %amount%</p><p>currecny: %currency%</p><p><br></p><p><br></p><p>Regards</p><p>%app_name%</p>', 1, NULL, '2023-04-01 01:35:22'),
(9, 'refer_commission', 'Referral Commission', '<p>Hi %username%</p><p><br></p><p>Refer user: %refer_user%</p><p>Amount: %amount%</p><p>Currency : %currency%</p><p><br></p><p>Regards,</p><p>%app_name%</p>', 1, NULL, '2023-04-01 01:17:34'),
(12, 'send_money', 'Send Money', '<p>Hi %username%</p><p><br></p><p>Receiver: %receiver%</p><p>Amount: %amount%</p><p>Transaction id : %trx%</p><p><br></p><p>Regards,</p><p>%app_name%</p>', 1, NULL, '2023-04-01 03:58:28'),
(13, 'receive_money', 'Money Received', '<p>Hi %username%</p><p><br></p><p>%sender%</p><p>%amount%</p><p>%trx%</p><p><br></p><p>Regards,</p><p>%app_name%</p>', 1, NULL, '2023-04-01 00:11:18'),
(14, 'plan_subscription', 'Plan Subscription', '<p>Hi %username%</p><p><br></p><p>%plan%</p><p>%amount%</p><p>%trx%</p><p><br></p><p>Regards,</p><p>%app_name%</p>', 1, NULL, '2023-04-04 03:20:09'),
(15, 'Signal', 'Signal Arrives', '<p>Hi %username%</p><p>%title%</p><p>%market%</p><p>%pair%-%<span style=\"font-size:0.875rem;\">frame%</span></p><p>Opening Poin : %open%</p><p>Stop Loss : %sl%</p><p>Take Profit : %tp%</p><p>Order Direction : %direction%</p>\r\n\r\n<p>%description%</p>\r\n\r\n<p>Regards,</p><p>%app_name%</p>', 1, NULL, '2023-04-04 03:43:25');

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_tickets`
--

CREATE TABLE `sp_tickets` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `reply_id` bigint(20) UNSIGNED NOT NULL,
  `support_id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `status` int(11) NOT NULL COMMENT '1=closed, 2=> pending, 3=> answered',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_ticket_replies`
--

CREATE TABLE `sp_ticket_replies` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `ticket_id` bigint(20) UNSIGNED NOT NULL,
  `admin_id` bigint(20) UNSIGNED NOT NULL,
  `message` text DEFAULT NULL,
  `file` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_time_frames`
--

CREATE TABLE `sp_time_frames` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `status` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_trades`
--

CREATE TABLE `sp_trades` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `ref` varchar(255) NOT NULL,
  `trade_type` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `currency` varchar(255) NOT NULL,
  `current_price` decimal(28,8) NOT NULL,
  `duration` int(11) NOT NULL,
  `trade_stop_at` datetime NOT NULL,
  `trade_opens_at` datetime NOT NULL,
  `profit_type` varchar(255) DEFAULT NULL,
  `profit_amount` decimal(8,2) NOT NULL DEFAULT 0.00,
  `loss_amount` decimal(8,2) NOT NULL DEFAULT 0.00,
  `charge` decimal(28,8) NOT NULL DEFAULT 0.00000000,
  `status` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_trading_presets`
--

CREATE TABLE `sp_trading_presets` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `symbol` varchar(50) DEFAULT NULL COMMENT 'Logical symbol (e.g., XAUUSD)',
  `timeframe` varchar(10) DEFAULT NULL COMMENT 'M1, M5, M15, H1, etc.',
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Array of tags: ["scalping", "xau", "layering"]' CHECK (json_valid(`tags`)),
  `position_size_mode` enum('FIXED','RISK_PERCENT') NOT NULL DEFAULT 'RISK_PERCENT',
  `fixed_lot` decimal(10,2) DEFAULT NULL,
  `risk_per_trade_pct` decimal(5,2) DEFAULT NULL COMMENT 'Percentage of equity',
  `max_positions` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `max_positions_per_symbol` int(10) UNSIGNED NOT NULL DEFAULT 1,
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
  `max_layers_per_symbol` int(10) UNSIGNED NOT NULL DEFAULT 3,
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
  `trading_days_mask` int(10) UNSIGNED NOT NULL DEFAULT 127 COMMENT 'Bitmask: 1=Mon, 2=Tue, 4=Wed, 8=Thu, 16=Fri, 32=Sat, 64=Sun',
  `session_profile` enum('ASIA','LONDON','NY','CUSTOM') NOT NULL DEFAULT 'CUSTOM',
  `only_trade_in_session` tinyint(1) NOT NULL DEFAULT 0,
  `weekly_target_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `weekly_target_profit_pct` decimal(5,2) DEFAULT NULL,
  `weekly_reset_day` tinyint(3) UNSIGNED DEFAULT NULL COMMENT '1=Monday, 7=Sunday',
  `auto_stop_on_weekly_target` tinyint(1) NOT NULL DEFAULT 0,
  `created_by_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `filter_strategy_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ai_model_profile_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ai_confirmation_mode` enum('NONE','REQUIRED','ADVISORY') NOT NULL DEFAULT 'NONE',
  `ai_min_safety_score` decimal(5,2) DEFAULT NULL,
  `ai_position_mgmt_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `is_default_template` tinyint(1) NOT NULL DEFAULT 0,
  `clonable` tinyint(1) NOT NULL DEFAULT 1,
  `visibility` enum('PRIVATE','PUBLIC_MARKETPLACE') NOT NULL DEFAULT 'PRIVATE',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `sp_trading_presets`
--

INSERT INTO `sp_trading_presets` (`id`, `name`, `description`, `symbol`, `timeframe`, `enabled`, `tags`, `position_size_mode`, `fixed_lot`, `risk_per_trade_pct`, `max_positions`, `max_positions_per_symbol`, `equity_dynamic_mode`, `equity_base`, `equity_step_factor`, `risk_min_pct`, `risk_max_pct`, `sl_mode`, `sl_pips`, `sl_r_multiple`, `tp_mode`, `tp1_enabled`, `tp1_rr`, `tp1_close_pct`, `tp2_enabled`, `tp2_rr`, `tp2_close_pct`, `tp3_enabled`, `tp3_rr`, `tp3_close_pct`, `close_remaining_at_tp3`, `be_enabled`, `be_trigger_rr`, `be_offset_pips`, `ts_enabled`, `ts_mode`, `ts_trigger_rr`, `ts_step_pips`, `ts_atr_period`, `ts_atr_multiplier`, `ts_update_interval_sec`, `layering_enabled`, `max_layers_per_symbol`, `layer_distance_pips`, `layer_martingale_mode`, `layer_martingale_factor`, `layer_max_total_risk_pct`, `hedging_enabled`, `hedge_trigger_drawdown_pct`, `hedge_distance_pips`, `hedge_lot_factor`, `auto_close_on_candle_close`, `auto_close_timeframe`, `hold_max_candles`, `trading_hours_start`, `trading_hours_end`, `trading_timezone`, `trading_days_mask`, `session_profile`, `only_trade_in_session`, `weekly_target_enabled`, `weekly_target_profit_pct`, `weekly_reset_day`, `auto_stop_on_weekly_target`, `created_by_user_id`, `filter_strategy_id`, `ai_model_profile_id`, `ai_confirmation_mode`, `ai_min_safety_score`, `ai_position_mgmt_enabled`, `is_default_template`, `clonable`, `visibility`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Conservative Scalper', 'Low risk, quick profits. Perfect for beginners who want to start trading safely with minimal risk per trade.', NULL, NULL, 1, '[\"scalping\",\"conservative\",\"beginner\",\"low-risk\"]', 'RISK_PERCENT', NULL, 0.50, 1, 1, 'NONE', NULL, NULL, NULL, NULL, 'PIPS', 20, NULL, 'SINGLE', 1, 1.50, 100.00, 0, NULL, NULL, 0, NULL, NULL, 0, 0, NULL, NULL, 0, 'STEP_PIPS', NULL, NULL, NULL, NULL, NULL, 0, 1, NULL, 'NONE', NULL, NULL, 0, NULL, NULL, NULL, 0, NULL, NULL, '08:00:00', '18:00:00', 'SERVER', 31, 'CUSTOM', 1, 1, 2.00, 1, 1, NULL, NULL, NULL, 'NONE', NULL, 0, 1, 1, 'PUBLIC_MARKETPLACE', '2025-12-01 12:49:38', '2025-12-01 12:49:38', NULL),
(2, 'Swing Trader', 'Medium-term trading strategy with multiple take profit levels. Uses break-even and trailing stop for better risk management.', NULL, NULL, 1, '[\"swing\",\"medium-term\",\"multi-tp\",\"break-even\"]', 'RISK_PERCENT', NULL, 1.00, 3, 1, 'NONE', NULL, NULL, NULL, NULL, 'PIPS', 50, NULL, 'MULTI', 1, 2.00, 30.00, 1, 3.00, 40.00, 1, 5.00, 30.00, 0, 1, 1.50, 0, 1, 'STEP_PIPS', 1.50, 20, NULL, NULL, 60, 0, 1, NULL, 'NONE', NULL, NULL, 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 'SERVER', 31, 'CUSTOM', 0, 0, NULL, NULL, 0, NULL, NULL, NULL, 'NONE', NULL, 0, 1, 1, 'PUBLIC_MARKETPLACE', '2025-12-01 12:50:16', '2025-12-01 12:50:16', NULL),
(3, 'Aggressive Day Trader', 'High risk, high reward strategy for experienced traders. Features layering and advanced trailing stop.', NULL, NULL, 1, '[\"aggressive\",\"day-trading\",\"layering\",\"high-risk\"]', 'RISK_PERCENT', NULL, 2.00, 5, 2, 'NONE', NULL, NULL, NULL, NULL, 'PIPS', 30, NULL, 'MULTI', 1, 1.50, 50.00, 1, 2.50, 30.00, 1, 4.00, 20.00, 0, 0, NULL, NULL, 1, 'STEP_ATR', 1.00, NULL, 14, 1.50, 30, 1, 3, 20, 'MULTIPLY', 1.50, 10.00, 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 'SERVER', 31, 'CUSTOM', 0, 1, 5.00, 1, 0, NULL, NULL, NULL, 'NONE', NULL, 0, 1, 1, 'PUBLIC_MARKETPLACE', '2025-12-01 12:50:16', '2025-12-01 12:50:16', NULL),
(4, 'Safe Long-Term', 'Very conservative strategy for long-term traders. Minimal risk with high reward potential.', NULL, NULL, 1, '[\"conservative\",\"long-term\",\"safe\",\"low-risk\"]', 'RISK_PERCENT', NULL, 0.25, 1, 1, 'NONE', NULL, NULL, NULL, NULL, 'PIPS', 100, NULL, 'SINGLE', 1, 3.00, 100.00, 0, NULL, NULL, 0, NULL, NULL, 0, 1, 2.00, 0, 0, 'STEP_PIPS', NULL, NULL, NULL, NULL, NULL, 0, 1, NULL, 'NONE', NULL, NULL, 0, NULL, NULL, NULL, 0, NULL, NULL, '08:00:00', '22:00:00', 'SERVER', 31, 'CUSTOM', 1, 1, 1.00, 1, 1, NULL, NULL, NULL, 'NONE', NULL, 0, 1, 1, 'PUBLIC_MARKETPLACE', '2025-12-01 12:50:16', '2025-12-01 12:50:16', NULL),
(5, 'Grid Trading', 'Grid/martingale strategy with layering and hedging. Suitable for range-bound markets.', NULL, NULL, 1, '[\"grid\",\"martingale\",\"layering\",\"hedging\"]', 'FIXED', 0.01, NULL, 5, 5, 'NONE', NULL, NULL, NULL, NULL, 'PIPS', 50, NULL, 'SINGLE', 1, 1.00, 100.00, 0, NULL, NULL, 0, NULL, NULL, 0, 0, NULL, NULL, 0, 'STEP_PIPS', NULL, NULL, NULL, NULL, NULL, 1, 5, 15, 'MULTIPLY', 2.00, 5.00, 1, 2.00, 20, 1.00, 0, NULL, NULL, NULL, NULL, 'SERVER', 31, 'CUSTOM', 0, 1, 3.00, 1, 0, NULL, NULL, NULL, 'NONE', NULL, 0, 1, 1, 'PUBLIC_MARKETPLACE', '2025-12-01 12:50:16', '2025-12-01 12:50:16', NULL),
(6, 'Breakout Trader', 'Breakout/volatility strategy with structure-based SL and Chandelier trailing stop.', NULL, NULL, 1, '[\"breakout\",\"volatility\",\"structure\",\"chandelier\"]', 'RISK_PERCENT', NULL, 1.50, 2, 1, 'NONE', NULL, NULL, NULL, NULL, 'STRUCTURE', NULL, NULL, 'MULTI', 1, 2.00, 40.00, 1, 4.00, 40.00, 1, 6.00, 20.00, 0, 1, 1.00, 0, 1, 'CHANDELIER', 1.00, NULL, 22, 3.00, 60, 0, 1, NULL, 'NONE', NULL, NULL, 0, NULL, NULL, NULL, 0, NULL, NULL, '08:00:00', '17:00:00', 'SERVER', 31, 'CUSTOM', 1, 0, NULL, NULL, 0, NULL, NULL, NULL, 'NONE', NULL, 0, 1, 1, 'PUBLIC_MARKETPLACE', '2025-12-01 12:50:16', '2025-12-01 12:50:16', NULL),
(7, 'Breakout Trader (Copy)', 'Breakout/volatility strategy with structure-based SL and Chandelier trailing stop.', NULL, NULL, 1, '\"[\\\"breakout\\\",\\\"volatility\\\",\\\"structure\\\",\\\"chandelier\\\"]\"', 'RISK_PERCENT', NULL, 1.50, 2, 1, 'NONE', NULL, NULL, NULL, NULL, 'STRUCTURE', NULL, NULL, 'MULTI', 1, 2.00, 40.00, 1, 4.00, 40.00, 1, 6.00, 20.00, 0, 1, 1.00, 0, 1, 'CHANDELIER', 1.00, NULL, 22, 3.00, 60, 0, 1, NULL, 'NONE', NULL, NULL, 0, NULL, NULL, NULL, 0, NULL, NULL, '08:00:00', '17:00:00', 'SERVER', 31, 'CUSTOM', 1, 0, NULL, NULL, 0, 3, NULL, NULL, 'NONE', NULL, 0, 0, 1, 'PRIVATE', '2025-12-03 08:20:14', '2025-12-03 08:20:14', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_transactions`
--

CREATE TABLE `sp_transactions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `trx` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `amount` decimal(28,8) NOT NULL,
  `charge` decimal(28,8) NOT NULL,
  `details` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_translation_settings`
--

CREATE TABLE `sp_translation_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `ai_connection_id` bigint(20) UNSIGNED NOT NULL,
  `fallback_connection_id` bigint(20) UNSIGNED DEFAULT NULL,
  `batch_size` int(11) NOT NULL DEFAULT 10,
  `delay_between_requests_ms` int(11) NOT NULL DEFAULT 100,
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`settings`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_users`
--

CREATE TABLE `sp_users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `default_preset_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ref_id` bigint(20) UNSIGNED NOT NULL,
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
  `telegram_id` varchar(255) DEFAULT NULL,
  `status` tinyint(1) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `sp_users`
--

INSERT INTO `sp_users` (`id`, `default_preset_id`, `ref_id`, `username`, `email`, `phone`, `address`, `password`, `balance`, `image`, `is_email_verified`, `is_sms_verified`, `is_kyc_verified`, `email_verification_code`, `sms_verification_code`, `login_at`, `kyc_information`, `facebook_id`, `google_id`, `telegram_id`, `status`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, NULL, 0, 'user', 'grahainsanmandiri@gmail.com', '081347241993', NULL, '$2y$10$CM9bQM2d496v5jI0QwERfezhZexeEqkxb96NgGNxMLnGrIEjqnTuu', 500.00000000, NULL, 0, 0, 0, NULL, NULL, '0000-00-00 00:00:00', NULL, NULL, NULL, NULL, 1, NULL, '2025-12-01 06:36:30', '2025-12-01 06:36:30'),
(2, NULL, 0, 'user1', 'user1@user.com', '898798', NULL, '$2y$10$1vC6rjaJlpPLgTmmCpdUsOjTtvYxnfIPfgy.Tkd6DOJqYG.9TR1Gi', 500.00000000, NULL, 0, 0, 0, NULL, NULL, '0000-00-00 00:00:00', NULL, NULL, NULL, NULL, 1, NULL, '2025-12-01 09:38:44', '2025-12-01 09:38:44'),
(3, 1, 0, 'admin', 'ketananna@yahoo.com', '082247006969', NULL, '$2y$10$2UcZdUUMugOldu9SKwQgGeO7sJ2PtPWoiPK.uJ4DQy5trIXhqgWTW', 500.00000000, NULL, 0, 0, 0, NULL, NULL, '0000-00-00 00:00:00', NULL, NULL, NULL, NULL, 1, NULL, '2025-12-03 08:17:26', '2025-12-03 08:17:26');

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_user_logs`
--

CREATE TABLE `sp_user_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `browser` varchar(255) NOT NULL,
  `system` varchar(255) NOT NULL,
  `country` varchar(255) NOT NULL,
  `ip` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `sp_user_logs`
--

INSERT INTO `sp_user_logs` (`id`, `user_id`, `browser`, `system`, `country`, `ip`, `created_at`, `updated_at`) VALUES
(1, 2, 'Chrome', 'Windows', 'Indonesia', '182.8.97.25', '2025-12-01 09:53:42', '2025-12-01 09:53:42'),
(2, 2, 'Chrome', 'Windows', 'Indonesia', '182.8.97.25', '2025-12-01 12:41:33', '2025-12-01 12:41:33'),
(3, 2, 'Chrome', 'Windows', 'Indonesia', '182.8.97.25', '2025-12-01 15:02:18', '2025-12-01 15:02:18'),
(4, 2, 'Chrome', 'Windows', 'Indonesia', '182.8.97.25', '2025-12-01 23:37:45', '2025-12-01 23:37:45'),
(5, 2, 'Chrome', 'Windows', 'Indonesia', '182.8.97.25', '2025-12-02 01:20:15', '2025-12-02 01:20:15'),
(6, 2, 'Chrome', 'Windows', 'Indonesia', '182.8.97.25', '2025-12-02 08:43:23', '2025-12-02 08:43:23'),
(7, 2, 'Edge', 'Windows', 'Indonesia', '182.8.97.25', '2025-12-02 08:44:01', '2025-12-02 08:44:01'),
(8, 2, 'Chrome', 'Windows', 'Indonesia', '182.8.97.25', '2025-12-02 09:26:16', '2025-12-02 09:26:16'),
(9, 2, 'Chrome', 'Windows', 'Indonesia', '182.8.97.25', '2025-12-02 09:27:23', '2025-12-02 09:27:23'),
(10, 2, 'Chrome', 'Windows', 'Indonesia', '182.8.97.25', '2025-12-02 10:16:29', '2025-12-02 10:16:29'),
(11, 2, 'Chrome', 'Windows', 'Indonesia', '182.8.97.25', '2025-12-02 10:16:58', '2025-12-02 10:16:58'),
(12, 2, 'Chrome', 'Windows', 'Indonesia', '182.8.97.25', '2025-12-03 01:52:52', '2025-12-03 01:52:52'),
(13, 2, 'Chrome', 'Windows', 'Indonesia', '182.8.97.25', '2025-12-03 08:17:06', '2025-12-03 08:17:06');

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_user_signals`
--

CREATE TABLE `sp_user_signals` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `signal_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_withdraws`
--

CREATE TABLE `sp_withdraws` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `withdraw_method_id` bigint(20) UNSIGNED NOT NULL,
  `trx` varchar(255) NOT NULL,
  `withdraw_amount` decimal(28,8) NOT NULL,
  `withdraw_charge` decimal(28,8) NOT NULL,
  `total` decimal(28,8) NOT NULL,
  `proof` text DEFAULT NULL,
  `reject_reason` text DEFAULT NULL,
  `status` tinyint(1) NOT NULL COMMENT '0=>pending, 1=>approved, 2 => reject',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `sp_withdraw_gateways`
--

CREATE TABLE `sp_withdraw_gateways` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `min_withdraw_amount` decimal(28,8) NOT NULL,
  `max_withdraw_amount` decimal(28,8) NOT NULL,
  `charge` decimal(28,8) NOT NULL,
  `type` varchar(255) NOT NULL,
  `instruction` text DEFAULT NULL,
  `status` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `sp_withdraw_gateways`
--

INSERT INTO `sp_withdraw_gateways` (`id`, `name`, `min_withdraw_amount`, `max_withdraw_amount`, `charge`, `type`, `instruction`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Bank', 50.00000000, 5000.00000000, 10.00000000, 'fixed', '<p>ddweh wedihwedijwe dih whd wkdjh wedh iweh diw</p><p>&nbsp;wudjhwe dihw iudh iwud iwh duiwh duy wid we</p><p>&nbsp;wi hdijwhid hwid iwedwe</p>', 1, '2023-07-15 00:22:05', '2023-07-15 00:22:05');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `sp_admins`
--
ALTER TABLE `sp_admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sp_admins_username_unique` (`username`),
  ADD UNIQUE KEY `sp_admins_email_unique` (`email`);

--
-- Indeks untuk tabel `sp_admin_password_resets`
--
ALTER TABLE `sp_admin_password_resets`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `sp_ai_configurations`
--
ALTER TABLE `sp_ai_configurations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sp_ai_configurations_provider_unique` (`provider`);

--
-- Indeks untuk tabel `sp_ai_connections`
--
ALTER TABLE `sp_ai_connections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sp_ai_connections_provider_id_status_priority_index` (`provider_id`,`status`,`priority`),
  ADD KEY `sp_ai_connections_status_index` (`status`),
  ADD KEY `sp_ai_connections_priority_index` (`priority`),
  ADD KEY `sp_ai_connections_last_used_at_index` (`last_used_at`);

--
-- Indeks untuk tabel `sp_ai_connection_usage`
--
ALTER TABLE `sp_ai_connection_usage`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sp_ai_connection_usage_connection_id_created_at_index` (`connection_id`,`created_at`),
  ADD KEY `sp_ai_connection_usage_feature_created_at_index` (`feature`,`created_at`),
  ADD KEY `sp_ai_connection_usage_success_created_at_index` (`success`,`created_at`),
  ADD KEY `sp_ai_connection_usage_created_at_index` (`created_at`);

--
-- Indeks untuk tabel `sp_ai_model_profiles`
--
ALTER TABLE `sp_ai_model_profiles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sp_ai_model_profiles_created_by_user_id_index` (`created_by_user_id`),
  ADD KEY `sp_ai_model_profiles_visibility_index` (`visibility`),
  ADD KEY `sp_ai_model_profiles_enabled_index` (`enabled`),
  ADD KEY `sp_ai_model_profiles_provider_index` (`provider`),
  ADD KEY `sp_ai_model_profiles_mode_index` (`mode`),
  ADD KEY `sp_ai_model_profiles_ai_connection_id_index` (`ai_connection_id`);

--
-- Indeks untuk tabel `sp_ai_parsing_profiles`
--
ALTER TABLE `sp_ai_parsing_profiles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sp_ai_parsing_profiles_channel_source_id_index` (`channel_source_id`),
  ADD KEY `sp_ai_parsing_profiles_ai_connection_id_index` (`ai_connection_id`),
  ADD KEY `sp_ai_parsing_profiles_enabled_priority_index` (`enabled`,`priority`);

--
-- Indeks untuk tabel `sp_ai_providers`
--
ALTER TABLE `sp_ai_providers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sp_ai_providers_slug_unique` (`slug`),
  ADD KEY `sp_ai_providers_status_index` (`status`),
  ADD KEY `sp_ai_providers_slug_index` (`slug`),
  ADD KEY `sp_ai_providers_default_connection_id_foreign` (`default_connection_id`);

--
-- Indeks untuk tabel `sp_channel_messages`
--
ALTER TABLE `sp_channel_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sp_channel_messages_channel_source_id_index` (`channel_source_id`),
  ADD KEY `sp_channel_messages_message_hash_index` (`message_hash`),
  ADD KEY `sp_channel_messages_status_index` (`status`),
  ADD KEY `sp_channel_messages_signal_id_index` (`signal_id`),
  ADD KEY `sp_channel_messages_created_at_index` (`created_at`);

--
-- Indeks untuk tabel `sp_channel_sources`
--
ALTER TABLE `sp_channel_sources`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sp_channel_sources_default_plan_id_foreign` (`default_plan_id`),
  ADD KEY `sp_channel_sources_default_market_id_foreign` (`default_market_id`),
  ADD KEY `sp_channel_sources_default_timeframe_id_foreign` (`default_timeframe_id`),
  ADD KEY `sp_channel_sources_user_id_index` (`user_id`),
  ADD KEY `sp_channel_sources_status_index` (`status`),
  ADD KEY `sp_channel_sources_type_index` (`type`),
  ADD KEY `sp_channel_sources_is_admin_owned_index` (`is_admin_owned`);

--
-- Indeks untuk tabel `sp_channel_source_plans`
--
ALTER TABLE `sp_channel_source_plans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sp_channel_source_plans_channel_source_id_plan_id_unique` (`channel_source_id`,`plan_id`),
  ADD KEY `sp_channel_source_plans_channel_source_id_index` (`channel_source_id`),
  ADD KEY `sp_channel_source_plans_plan_id_index` (`plan_id`);

--
-- Indeks untuk tabel `sp_channel_source_users`
--
ALTER TABLE `sp_channel_source_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sp_channel_source_users_channel_source_id_user_id_unique` (`channel_source_id`,`user_id`),
  ADD KEY `sp_channel_source_users_channel_source_id_index` (`channel_source_id`),
  ADD KEY `sp_channel_source_users_user_id_index` (`user_id`);

--
-- Indeks untuk tabel `sp_configurations`
--
ALTER TABLE `sp_configurations`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `sp_contents`
--
ALTER TABLE `sp_contents`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `sp_copy_trading_executions`
--
ALTER TABLE `sp_copy_trading_executions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sp_copy_trading_executions_follower_position_id_foreign` (`follower_position_id`),
  ADD KEY `sp_copy_trading_executions_follower_connection_id_foreign` (`follower_connection_id`),
  ADD KEY `sp_copy_trading_executions_trader_position_id_index` (`trader_position_id`),
  ADD KEY `sp_copy_trading_executions_trader_id_index` (`trader_id`),
  ADD KEY `sp_copy_trading_executions_follower_id_index` (`follower_id`),
  ADD KEY `sp_copy_trading_executions_subscription_id_index` (`subscription_id`),
  ADD KEY `sp_copy_trading_executions_status_index` (`status`);

--
-- Indeks untuk tabel `sp_copy_trading_settings`
--
ALTER TABLE `sp_copy_trading_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `copy_trading_settings_user_id_unique` (`user_id`),
  ADD UNIQUE KEY `copy_trading_settings_admin_id_unique` (`admin_id`),
  ADD KEY `sp_copy_trading_settings_is_enabled_index` (`is_enabled`),
  ADD KEY `sp_copy_trading_settings_admin_id_index` (`admin_id`),
  ADD KEY `sp_copy_trading_settings_is_admin_owned_index` (`is_admin_owned`);

--
-- Indeks untuk tabel `sp_copy_trading_subscriptions`
--
ALTER TABLE `sp_copy_trading_subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sp_copy_trading_subscriptions_trader_id_follower_id_unique` (`trader_id`,`follower_id`),
  ADD KEY `sp_copy_trading_subscriptions_connection_id_foreign` (`connection_id`),
  ADD KEY `sp_copy_trading_subscriptions_trader_id_index` (`trader_id`),
  ADD KEY `sp_copy_trading_subscriptions_follower_id_index` (`follower_id`),
  ADD KEY `sp_copy_trading_subscriptions_is_active_index` (`is_active`),
  ADD KEY `sp_copy_trading_subscriptions_copy_mode_index` (`copy_mode`),
  ADD KEY `sp_copy_trading_subscriptions_preset_id_index` (`preset_id`);

--
-- Indeks untuk tabel `sp_currency_pairs`
--
ALTER TABLE `sp_currency_pairs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sp_currency_pairs_name_unique` (`name`);

--
-- Indeks untuk tabel `sp_dashboard_signals`
--
ALTER TABLE `sp_dashboard_signals`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `sp_deposits`
--
ALTER TABLE `sp_deposits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sp_deposits_trx_unique` (`trx`);

--
-- Indeks untuk tabel `sp_execution_analytics`
--
ALTER TABLE `sp_execution_analytics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sp_execution_analytics_connection_id_date_unique` (`connection_id`,`date`),
  ADD KEY `sp_execution_analytics_user_id_index` (`user_id`),
  ADD KEY `sp_execution_analytics_admin_id_index` (`admin_id`),
  ADD KEY `sp_execution_analytics_date_index` (`date`);

--
-- Indeks untuk tabel `sp_execution_connections`
--
ALTER TABLE `sp_execution_connections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sp_execution_connections_user_id_index` (`user_id`),
  ADD KEY `sp_execution_connections_admin_id_index` (`admin_id`),
  ADD KEY `sp_execution_connections_type_index` (`type`),
  ADD KEY `sp_execution_connections_status_index` (`status`),
  ADD KEY `sp_execution_connections_is_active_index` (`is_active`),
  ADD KEY `sp_execution_connections_preset_id_index` (`preset_id`);

--
-- Indeks untuk tabel `sp_execution_logs`
--
ALTER TABLE `sp_execution_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sp_execution_logs_signal_id_index` (`signal_id`),
  ADD KEY `sp_execution_logs_connection_id_index` (`connection_id`),
  ADD KEY `sp_execution_logs_status_index` (`status`),
  ADD KEY `sp_execution_logs_order_id_index` (`order_id`),
  ADD KEY `sp_execution_logs_executed_at_index` (`executed_at`);

--
-- Indeks untuk tabel `sp_execution_notifications`
--
ALTER TABLE `sp_execution_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sp_execution_notifications_signal_id_foreign` (`signal_id`),
  ADD KEY `sp_execution_notifications_position_id_foreign` (`position_id`),
  ADD KEY `sp_execution_notifications_user_id_index` (`user_id`),
  ADD KEY `sp_execution_notifications_admin_id_index` (`admin_id`),
  ADD KEY `sp_execution_notifications_connection_id_index` (`connection_id`),
  ADD KEY `sp_execution_notifications_is_read_index` (`is_read`),
  ADD KEY `sp_execution_notifications_type_index` (`type`),
  ADD KEY `sp_execution_notifications_created_at_index` (`created_at`);

--
-- Indeks untuk tabel `sp_execution_positions`
--
ALTER TABLE `sp_execution_positions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sp_execution_positions_signal_id_index` (`signal_id`),
  ADD KEY `sp_execution_positions_connection_id_index` (`connection_id`),
  ADD KEY `sp_execution_positions_execution_log_id_index` (`execution_log_id`),
  ADD KEY `sp_execution_positions_status_index` (`status`),
  ADD KEY `sp_execution_positions_order_id_index` (`order_id`),
  ADD KEY `sp_execution_positions_closed_at_index` (`closed_at`);

--
-- Indeks untuk tabel `sp_filter_strategies`
--
ALTER TABLE `sp_filter_strategies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sp_filter_strategies_created_by_user_id_index` (`created_by_user_id`),
  ADD KEY `sp_filter_strategies_visibility_index` (`visibility`),
  ADD KEY `sp_filter_strategies_enabled_index` (`enabled`),
  ADD KEY `sp_filter_strategies_clonable_index` (`clonable`);

--
-- Indeks untuk tabel `sp_frontend_components`
--
ALTER TABLE `sp_frontend_components`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `sp_frontend_media`
--
ALTER TABLE `sp_frontend_media`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `sp_gateways`
--
ALTER TABLE `sp_gateways`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sp_gateways_name_unique` (`name`);

--
-- Indeks untuk tabel `sp_jobs`
--
ALTER TABLE `sp_jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sp_jobs_queue_index` (`queue`);

--
-- Indeks untuk tabel `sp_languages`
--
ALTER TABLE `sp_languages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sp_languages_name_unique` (`name`),
  ADD UNIQUE KEY `sp_languages_code_unique` (`code`);

--
-- Indeks untuk tabel `sp_login_securities`
--
ALTER TABLE `sp_login_securities`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `sp_markets`
--
ALTER TABLE `sp_markets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sp_markets_name_unique` (`name`);

--
-- Indeks untuk tabel `sp_message_parsing_patterns`
--
ALTER TABLE `sp_message_parsing_patterns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sp_message_parsing_patterns_channel_source_id_index` (`channel_source_id`),
  ADD KEY `sp_message_parsing_patterns_user_id_index` (`user_id`),
  ADD KEY `sp_message_parsing_patterns_priority_index` (`priority`),
  ADD KEY `sp_message_parsing_patterns_is_active_index` (`is_active`);

--
-- Indeks untuk tabel `sp_migrations`
--
ALTER TABLE `sp_migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `sp_model_has_permissions`
--
ALTER TABLE `sp_model_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  ADD KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indeks untuk tabel `sp_model_has_roles`
--
ALTER TABLE `sp_model_has_roles`
  ADD PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  ADD KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indeks untuk tabel `sp_money_transfers`
--
ALTER TABLE `sp_money_transfers`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `sp_notifications`
--
ALTER TABLE `sp_notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `sp_openrouter_configurations`
--
ALTER TABLE `sp_openrouter_configurations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sp_openrouter_configurations_enabled_use_for_parsing_index` (`enabled`,`use_for_parsing`),
  ADD KEY `sp_openrouter_configurations_enabled_use_for_analysis_index` (`enabled`,`use_for_analysis`),
  ADD KEY `sp_openrouter_configurations_priority_index` (`priority`),
  ADD KEY `sp_openrouter_configurations_ai_connection_id_index` (`ai_connection_id`);

--
-- Indeks untuk tabel `sp_openrouter_models`
--
ALTER TABLE `sp_openrouter_models`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sp_openrouter_models_model_id_unique` (`model_id`),
  ADD KEY `sp_openrouter_models_provider_index` (`provider`),
  ADD KEY `sp_openrouter_models_is_available_index` (`is_available`);

--
-- Indeks untuk tabel `sp_pages`
--
ALTER TABLE `sp_pages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sp_pages_name_unique` (`name`);

--
-- Indeks untuk tabel `sp_page_sections`
--
ALTER TABLE `sp_page_sections`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `sp_payments`
--
ALTER TABLE `sp_payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sp_payments_trx_unique` (`trx`);

--
-- Indeks untuk tabel `sp_permissions`
--
ALTER TABLE `sp_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sp_permissions_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indeks untuk tabel `sp_personal_access_tokens`
--
ALTER TABLE `sp_personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sp_personal_access_tokens_token_unique` (`token`),
  ADD KEY `sp_personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- Indeks untuk tabel `sp_plans`
--
ALTER TABLE `sp_plans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sp_plans_name_unique` (`name`),
  ADD UNIQUE KEY `sp_plans_slug_unique` (`slug`);

--
-- Indeks untuk tabel `sp_plan_subscriptions`
--
ALTER TABLE `sp_plan_subscriptions`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `sp_referrals`
--
ALTER TABLE `sp_referrals`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `sp_referral_commissions`
--
ALTER TABLE `sp_referral_commissions`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `sp_roles`
--
ALTER TABLE `sp_roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sp_roles_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indeks untuk tabel `sp_role_has_permissions`
--
ALTER TABLE `sp_role_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`role_id`),
  ADD KEY `sp_role_has_permissions_role_id_foreign` (`role_id`);

--
-- Indeks untuk tabel `sp_signals`
--
ALTER TABLE `sp_signals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sp_signals_channel_source_id_index` (`channel_source_id`),
  ADD KEY `sp_signals_auto_created_index` (`auto_created`),
  ADD KEY `sp_signals_message_hash_index` (`message_hash`),
  ADD KEY `sp_signals_structure_sl_price_index` (`structure_sl_price`);

--
-- Indeks untuk tabel `sp_signal_analytics`
--
ALTER TABLE `sp_signal_analytics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sp_signal_analytics_signal_id_index` (`signal_id`),
  ADD KEY `sp_signal_analytics_channel_source_id_index` (`channel_source_id`),
  ADD KEY `sp_signal_analytics_plan_id_index` (`plan_id`),
  ADD KEY `sp_signal_analytics_user_id_index` (`user_id`),
  ADD KEY `sp_signal_analytics_trade_status_index` (`trade_status`),
  ADD KEY `sp_signal_analytics_signal_received_at_index` (`signal_received_at`),
  ADD KEY `sp_signal_analytics_currency_pair_index` (`currency_pair`);

--
-- Indeks untuk tabel `sp_srm_ab_tests`
--
ALTER TABLE `sp_srm_ab_tests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_dates` (`start_date`,`end_date`);

--
-- Indeks untuk tabel `sp_srm_ab_test_assignments`
--
ALTER TABLE `sp_srm_ab_test_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ab_test` (`ab_test_id`,`group_type`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_connection` (`connection_id`);

--
-- Indeks untuk tabel `sp_srm_model_versions`
--
ALTER TABLE `sp_srm_model_versions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_model_version` (`model_type`,`version`),
  ADD KEY `idx_model_type` (`model_type`,`status`),
  ADD KEY `idx_version` (`version`);

--
-- Indeks untuk tabel `sp_srm_predictions`
--
ALTER TABLE `sp_srm_predictions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_execution_log` (`execution_log_id`),
  ADD KEY `idx_signal` (`signal_id`),
  ADD KEY `idx_connection` (`connection_id`),
  ADD KEY `idx_prediction_type` (`prediction_type`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indeks untuk tabel `sp_srm_signal_provider_metrics`
--
ALTER TABLE `sp_srm_signal_provider_metrics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_provider_period` (`signal_provider_id`,`signal_provider_type`,`period_start`,`period_end`,`period_type`),
  ADD KEY `idx_signal_provider` (`signal_provider_id`,`signal_provider_type`),
  ADD KEY `idx_period` (`period_start`,`period_end`,`period_type`),
  ADD KEY `idx_performance_score` (`performance_score`);

--
-- Indeks untuk tabel `sp_subscribers`
--
ALTER TABLE `sp_subscribers`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `sp_templates`
--
ALTER TABLE `sp_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sp_templates_name_unique` (`name`);

--
-- Indeks untuk tabel `sp_tickets`
--
ALTER TABLE `sp_tickets`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `sp_ticket_replies`
--
ALTER TABLE `sp_ticket_replies`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `sp_time_frames`
--
ALTER TABLE `sp_time_frames`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sp_time_frames_name_unique` (`name`);

--
-- Indeks untuk tabel `sp_trades`
--
ALTER TABLE `sp_trades`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sp_trades_ref_unique` (`ref`);

--
-- Indeks untuk tabel `sp_trading_presets`
--
ALTER TABLE `sp_trading_presets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sp_trading_presets_created_by_user_id_index` (`created_by_user_id`),
  ADD KEY `sp_trading_presets_visibility_index` (`visibility`),
  ADD KEY `sp_trading_presets_enabled_index` (`enabled`),
  ADD KEY `sp_trading_presets_is_default_template_index` (`is_default_template`),
  ADD KEY `sp_trading_presets_symbol_index` (`symbol`),
  ADD KEY `sp_trading_presets_timeframe_index` (`timeframe`),
  ADD KEY `sp_trading_presets_filter_strategy_id_index` (`filter_strategy_id`),
  ADD KEY `sp_trading_presets_ai_model_profile_id_index` (`ai_model_profile_id`),
  ADD KEY `sp_trading_presets_ai_confirmation_mode_index` (`ai_confirmation_mode`);

--
-- Indeks untuk tabel `sp_transactions`
--
ALTER TABLE `sp_transactions`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `sp_translation_settings`
--
ALTER TABLE `sp_translation_settings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sp_translation_settings_fallback_connection_id_foreign` (`fallback_connection_id`),
  ADD KEY `sp_translation_settings_ai_connection_id_index` (`ai_connection_id`);

--
-- Indeks untuk tabel `sp_users`
--
ALTER TABLE `sp_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sp_users_username_unique` (`username`),
  ADD UNIQUE KEY `sp_users_email_unique` (`email`),
  ADD UNIQUE KEY `sp_users_phone_unique` (`phone`),
  ADD KEY `sp_users_default_preset_id_index` (`default_preset_id`);

--
-- Indeks untuk tabel `sp_user_logs`
--
ALTER TABLE `sp_user_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `sp_user_signals`
--
ALTER TABLE `sp_user_signals`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `sp_withdraws`
--
ALTER TABLE `sp_withdraws`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sp_withdraws_trx_unique` (`trx`);

--
-- Indeks untuk tabel `sp_withdraw_gateways`
--
ALTER TABLE `sp_withdraw_gateways`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sp_withdraw_gateways_name_unique` (`name`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `sp_admins`
--
ALTER TABLE `sp_admins`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `sp_admin_password_resets`
--
ALTER TABLE `sp_admin_password_resets`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `sp_ai_configurations`
--
ALTER TABLE `sp_ai_configurations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `sp_ai_connections`
--
ALTER TABLE `sp_ai_connections`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `sp_ai_connection_usage`
--
ALTER TABLE `sp_ai_connection_usage`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `sp_ai_model_profiles`
--
ALTER TABLE `sp_ai_model_profiles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `sp_ai_parsing_profiles`
--
ALTER TABLE `sp_ai_parsing_profiles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `sp_ai_providers`
--
ALTER TABLE `sp_ai_providers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `sp_channel_messages`
--
ALTER TABLE `sp_channel_messages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `sp_channel_sources`
--
ALTER TABLE `sp_channel_sources`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT untuk tabel `sp_channel_source_plans`
--
ALTER TABLE `sp_channel_source_plans`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `sp_channel_source_users`
--
ALTER TABLE `sp_channel_source_users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `sp_configurations`
--
ALTER TABLE `sp_configurations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `sp_contents`
--
ALTER TABLE `sp_contents`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=370;

--
-- AUTO_INCREMENT untuk tabel `sp_copy_trading_executions`
--
ALTER TABLE `sp_copy_trading_executions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `sp_copy_trading_settings`
--
ALTER TABLE `sp_copy_trading_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `sp_copy_trading_subscriptions`
--
ALTER TABLE `sp_copy_trading_subscriptions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `sp_currency_pairs`
--
ALTER TABLE `sp_currency_pairs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `sp_dashboard_signals`
--
ALTER TABLE `sp_dashboard_signals`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `sp_deposits`
--
ALTER TABLE `sp_deposits`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `sp_execution_analytics`
--
ALTER TABLE `sp_execution_analytics`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `sp_execution_connections`
--
ALTER TABLE `sp_execution_connections`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `sp_execution_logs`
--
ALTER TABLE `sp_execution_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `sp_execution_notifications`
--
ALTER TABLE `sp_execution_notifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `sp_execution_positions`
--
ALTER TABLE `sp_execution_positions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `sp_filter_strategies`
--
ALTER TABLE `sp_filter_strategies`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `sp_frontend_components`
--
ALTER TABLE `sp_frontend_components`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `sp_frontend_media`
--
ALTER TABLE `sp_frontend_media`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `sp_gateways`
--
ALTER TABLE `sp_gateways`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT untuk tabel `sp_jobs`
--
ALTER TABLE `sp_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `sp_languages`
--
ALTER TABLE `sp_languages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `sp_login_securities`
--
ALTER TABLE `sp_login_securities`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `sp_markets`
--
ALTER TABLE `sp_markets`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `sp_message_parsing_patterns`
--
ALTER TABLE `sp_message_parsing_patterns`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `sp_migrations`
--
ALTER TABLE `sp_migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=95;

--
-- AUTO_INCREMENT untuk tabel `sp_money_transfers`
--
ALTER TABLE `sp_money_transfers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `sp_openrouter_configurations`
--
ALTER TABLE `sp_openrouter_configurations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `sp_openrouter_models`
--
ALTER TABLE `sp_openrouter_models`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `sp_pages`
--
ALTER TABLE `sp_pages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT untuk tabel `sp_page_sections`
--
ALTER TABLE `sp_page_sections`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=248;

--
-- AUTO_INCREMENT untuk tabel `sp_payments`
--
ALTER TABLE `sp_payments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `sp_permissions`
--
ALTER TABLE `sp_permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT untuk tabel `sp_personal_access_tokens`
--
ALTER TABLE `sp_personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `sp_plans`
--
ALTER TABLE `sp_plans`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `sp_plan_subscriptions`
--
ALTER TABLE `sp_plan_subscriptions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `sp_referrals`
--
ALTER TABLE `sp_referrals`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `sp_referral_commissions`
--
ALTER TABLE `sp_referral_commissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `sp_roles`
--
ALTER TABLE `sp_roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `sp_signals`
--
ALTER TABLE `sp_signals`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `sp_signal_analytics`
--
ALTER TABLE `sp_signal_analytics`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `sp_srm_ab_tests`
--
ALTER TABLE `sp_srm_ab_tests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `sp_srm_ab_test_assignments`
--
ALTER TABLE `sp_srm_ab_test_assignments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `sp_srm_model_versions`
--
ALTER TABLE `sp_srm_model_versions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `sp_srm_predictions`
--
ALTER TABLE `sp_srm_predictions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `sp_srm_signal_provider_metrics`
--
ALTER TABLE `sp_srm_signal_provider_metrics`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `sp_subscribers`
--
ALTER TABLE `sp_subscribers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `sp_templates`
--
ALTER TABLE `sp_templates`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT untuk tabel `sp_tickets`
--
ALTER TABLE `sp_tickets`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `sp_ticket_replies`
--
ALTER TABLE `sp_ticket_replies`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `sp_time_frames`
--
ALTER TABLE `sp_time_frames`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `sp_trades`
--
ALTER TABLE `sp_trades`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `sp_trading_presets`
--
ALTER TABLE `sp_trading_presets`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `sp_transactions`
--
ALTER TABLE `sp_transactions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `sp_translation_settings`
--
ALTER TABLE `sp_translation_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `sp_users`
--
ALTER TABLE `sp_users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `sp_user_logs`
--
ALTER TABLE `sp_user_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT untuk tabel `sp_user_signals`
--
ALTER TABLE `sp_user_signals`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `sp_withdraws`
--
ALTER TABLE `sp_withdraws`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `sp_withdraw_gateways`
--
ALTER TABLE `sp_withdraw_gateways`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `sp_ai_connections`
--
ALTER TABLE `sp_ai_connections`
  ADD CONSTRAINT `sp_ai_connections_provider_id_foreign` FOREIGN KEY (`provider_id`) REFERENCES `sp_ai_providers` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `sp_ai_connection_usage`
--
ALTER TABLE `sp_ai_connection_usage`
  ADD CONSTRAINT `sp_ai_connection_usage_connection_id_foreign` FOREIGN KEY (`connection_id`) REFERENCES `sp_ai_connections` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `sp_ai_model_profiles`
--
ALTER TABLE `sp_ai_model_profiles`
  ADD CONSTRAINT `sp_ai_model_profiles_ai_connection_id_foreign` FOREIGN KEY (`ai_connection_id`) REFERENCES `sp_ai_connections` (`id`),
  ADD CONSTRAINT `sp_ai_model_profiles_created_by_user_id_foreign` FOREIGN KEY (`created_by_user_id`) REFERENCES `sp_users` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `sp_ai_parsing_profiles`
--
ALTER TABLE `sp_ai_parsing_profiles`
  ADD CONSTRAINT `sp_ai_parsing_profiles_ai_connection_id_foreign` FOREIGN KEY (`ai_connection_id`) REFERENCES `sp_ai_connections` (`id`),
  ADD CONSTRAINT `sp_ai_parsing_profiles_channel_source_id_foreign` FOREIGN KEY (`channel_source_id`) REFERENCES `sp_channel_sources` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `sp_ai_providers`
--
ALTER TABLE `sp_ai_providers`
  ADD CONSTRAINT `sp_ai_providers_default_connection_id_foreign` FOREIGN KEY (`default_connection_id`) REFERENCES `sp_ai_connections` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `sp_channel_messages`
--
ALTER TABLE `sp_channel_messages`
  ADD CONSTRAINT `sp_channel_messages_channel_source_id_foreign` FOREIGN KEY (`channel_source_id`) REFERENCES `sp_channel_sources` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sp_channel_messages_signal_id_foreign` FOREIGN KEY (`signal_id`) REFERENCES `sp_signals` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `sp_channel_sources`
--
ALTER TABLE `sp_channel_sources`
  ADD CONSTRAINT `sp_channel_sources_default_market_id_foreign` FOREIGN KEY (`default_market_id`) REFERENCES `sp_markets` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sp_channel_sources_default_plan_id_foreign` FOREIGN KEY (`default_plan_id`) REFERENCES `sp_plans` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sp_channel_sources_default_timeframe_id_foreign` FOREIGN KEY (`default_timeframe_id`) REFERENCES `sp_time_frames` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sp_channel_sources_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `sp_users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `sp_channel_source_plans`
--
ALTER TABLE `sp_channel_source_plans`
  ADD CONSTRAINT `sp_channel_source_plans_channel_source_id_foreign` FOREIGN KEY (`channel_source_id`) REFERENCES `sp_channel_sources` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sp_channel_source_plans_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `sp_plans` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `sp_channel_source_users`
--
ALTER TABLE `sp_channel_source_users`
  ADD CONSTRAINT `sp_channel_source_users_channel_source_id_foreign` FOREIGN KEY (`channel_source_id`) REFERENCES `sp_channel_sources` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sp_channel_source_users_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `sp_users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `sp_copy_trading_executions`
--
ALTER TABLE `sp_copy_trading_executions`
  ADD CONSTRAINT `sp_copy_trading_executions_follower_connection_id_foreign` FOREIGN KEY (`follower_connection_id`) REFERENCES `sp_execution_connections` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sp_copy_trading_executions_follower_id_foreign` FOREIGN KEY (`follower_id`) REFERENCES `sp_users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sp_copy_trading_executions_follower_position_id_foreign` FOREIGN KEY (`follower_position_id`) REFERENCES `sp_execution_positions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sp_copy_trading_executions_subscription_id_foreign` FOREIGN KEY (`subscription_id`) REFERENCES `sp_copy_trading_subscriptions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sp_copy_trading_executions_trader_id_foreign` FOREIGN KEY (`trader_id`) REFERENCES `sp_users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sp_copy_trading_executions_trader_position_id_foreign` FOREIGN KEY (`trader_position_id`) REFERENCES `sp_execution_positions` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `sp_copy_trading_settings`
--
ALTER TABLE `sp_copy_trading_settings`
  ADD CONSTRAINT `sp_copy_trading_settings_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `sp_admins` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sp_copy_trading_settings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `sp_users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `sp_copy_trading_subscriptions`
--
ALTER TABLE `sp_copy_trading_subscriptions`
  ADD CONSTRAINT `sp_copy_trading_subscriptions_connection_id_foreign` FOREIGN KEY (`connection_id`) REFERENCES `sp_execution_connections` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sp_copy_trading_subscriptions_follower_id_foreign` FOREIGN KEY (`follower_id`) REFERENCES `sp_users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sp_copy_trading_subscriptions_preset_id_foreign` FOREIGN KEY (`preset_id`) REFERENCES `sp_trading_presets` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sp_copy_trading_subscriptions_trader_id_foreign` FOREIGN KEY (`trader_id`) REFERENCES `sp_users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `sp_execution_analytics`
--
ALTER TABLE `sp_execution_analytics`
  ADD CONSTRAINT `sp_execution_analytics_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `sp_admins` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sp_execution_analytics_connection_id_foreign` FOREIGN KEY (`connection_id`) REFERENCES `sp_execution_connections` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sp_execution_analytics_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `sp_users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `sp_execution_connections`
--
ALTER TABLE `sp_execution_connections`
  ADD CONSTRAINT `sp_execution_connections_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `sp_admins` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sp_execution_connections_preset_id_foreign` FOREIGN KEY (`preset_id`) REFERENCES `sp_trading_presets` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sp_execution_connections_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `sp_users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `sp_execution_logs`
--
ALTER TABLE `sp_execution_logs`
  ADD CONSTRAINT `sp_execution_logs_connection_id_foreign` FOREIGN KEY (`connection_id`) REFERENCES `sp_execution_connections` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sp_execution_logs_signal_id_foreign` FOREIGN KEY (`signal_id`) REFERENCES `sp_signals` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `sp_execution_notifications`
--
ALTER TABLE `sp_execution_notifications`
  ADD CONSTRAINT `sp_execution_notifications_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `sp_admins` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sp_execution_notifications_connection_id_foreign` FOREIGN KEY (`connection_id`) REFERENCES `sp_execution_connections` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sp_execution_notifications_position_id_foreign` FOREIGN KEY (`position_id`) REFERENCES `sp_execution_positions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sp_execution_notifications_signal_id_foreign` FOREIGN KEY (`signal_id`) REFERENCES `sp_signals` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sp_execution_notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `sp_users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `sp_execution_positions`
--
ALTER TABLE `sp_execution_positions`
  ADD CONSTRAINT `sp_execution_positions_connection_id_foreign` FOREIGN KEY (`connection_id`) REFERENCES `sp_execution_connections` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sp_execution_positions_execution_log_id_foreign` FOREIGN KEY (`execution_log_id`) REFERENCES `sp_execution_logs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sp_execution_positions_signal_id_foreign` FOREIGN KEY (`signal_id`) REFERENCES `sp_signals` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `sp_filter_strategies`
--
ALTER TABLE `sp_filter_strategies`
  ADD CONSTRAINT `sp_filter_strategies_created_by_user_id_foreign` FOREIGN KEY (`created_by_user_id`) REFERENCES `sp_users` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `sp_message_parsing_patterns`
--
ALTER TABLE `sp_message_parsing_patterns`
  ADD CONSTRAINT `sp_message_parsing_patterns_channel_source_id_foreign` FOREIGN KEY (`channel_source_id`) REFERENCES `sp_channel_sources` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sp_message_parsing_patterns_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `sp_users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `sp_model_has_permissions`
--
ALTER TABLE `sp_model_has_permissions`
  ADD CONSTRAINT `sp_model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `sp_permissions` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `sp_model_has_roles`
--
ALTER TABLE `sp_model_has_roles`
  ADD CONSTRAINT `sp_model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `sp_roles` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `sp_openrouter_configurations`
--
ALTER TABLE `sp_openrouter_configurations`
  ADD CONSTRAINT `sp_openrouter_configurations_ai_connection_id_foreign` FOREIGN KEY (`ai_connection_id`) REFERENCES `sp_ai_connections` (`id`);

--
-- Ketidakleluasaan untuk tabel `sp_role_has_permissions`
--
ALTER TABLE `sp_role_has_permissions`
  ADD CONSTRAINT `sp_role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `sp_permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sp_role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `sp_roles` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `sp_signals`
--
ALTER TABLE `sp_signals`
  ADD CONSTRAINT `sp_signals_channel_source_id_foreign` FOREIGN KEY (`channel_source_id`) REFERENCES `sp_channel_sources` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `sp_signal_analytics`
--
ALTER TABLE `sp_signal_analytics`
  ADD CONSTRAINT `sp_signal_analytics_channel_source_id_foreign` FOREIGN KEY (`channel_source_id`) REFERENCES `sp_channel_sources` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sp_signal_analytics_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `sp_plans` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sp_signal_analytics_signal_id_foreign` FOREIGN KEY (`signal_id`) REFERENCES `sp_signals` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sp_signal_analytics_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `sp_users` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `sp_srm_ab_test_assignments`
--
ALTER TABLE `sp_srm_ab_test_assignments`
  ADD CONSTRAINT `sp_srm_ab_test_assignments_ab_test_id_foreign` FOREIGN KEY (`ab_test_id`) REFERENCES `sp_srm_ab_tests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sp_srm_ab_test_assignments_connection_id_foreign` FOREIGN KEY (`connection_id`) REFERENCES `sp_execution_connections` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sp_srm_ab_test_assignments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `sp_users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `sp_srm_predictions`
--
ALTER TABLE `sp_srm_predictions`
  ADD CONSTRAINT `sp_srm_predictions_execution_log_id_foreign` FOREIGN KEY (`execution_log_id`) REFERENCES `sp_execution_logs` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sp_srm_predictions_signal_id_foreign` FOREIGN KEY (`signal_id`) REFERENCES `sp_signals` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `sp_trading_presets`
--
ALTER TABLE `sp_trading_presets`
  ADD CONSTRAINT `sp_trading_presets_ai_model_profile_id_foreign` FOREIGN KEY (`ai_model_profile_id`) REFERENCES `sp_ai_model_profiles` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sp_trading_presets_created_by_user_id_foreign` FOREIGN KEY (`created_by_user_id`) REFERENCES `sp_users` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `sp_translation_settings`
--
ALTER TABLE `sp_translation_settings`
  ADD CONSTRAINT `sp_translation_settings_ai_connection_id_foreign` FOREIGN KEY (`ai_connection_id`) REFERENCES `sp_ai_connections` (`id`),
  ADD CONSTRAINT `sp_translation_settings_fallback_connection_id_foreign` FOREIGN KEY (`fallback_connection_id`) REFERENCES `sp_ai_connections` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `sp_users`
--
ALTER TABLE `sp_users`
  ADD CONSTRAINT `sp_users_default_preset_id_foreign` FOREIGN KEY (`default_preset_id`) REFERENCES `sp_trading_presets` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

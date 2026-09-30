-- ============================================================================
-- MyEbookBazaar v1.0.0 — fresh-install database schema (MySQL 8, utf8mb4)
-- ============================================================================
-- HOW TO USE:
--   1. Create an empty database with utf8mb4 charset:
--        CREATE DATABASE myebookbazaar CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
--   2. Import this file:
--        mysql -u <user> -p myebookbazaar < myebookbazaar-schema.sql
--   3. Copy .env.example to .env, set DB_* and ADMIN_EMAIL / ADMIN_PASSWORD,
--      then run:  php artisan app:create-admin
--
-- CONTENTS:
--   - Full table structure (all tables, indexes, foreign keys)
--   - Seed data ONLY: countries, default settings, the "Fit at Home: Start
--     (Edition 1)" book (no PDF/cover file), its category, and policy pages
--   - The `migrations` table is pre-filled so `php artisan migrate` later
--     finds nothing pending.
--   - NO users, admins, orders, payments, downloads, sessions, cache rows,
--     audit logs, or secrets of any kind.
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;


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
DROP TABLE IF EXISTS `admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admins` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'staff',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `two_factor_secret` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `two_factor_enabled_at` timestamp NULL DEFAULT NULL,
  `two_factor_recovery_codes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admins_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=272 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` bigint unsigned DEFAULT NULL,
  `action` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `target_id` bigint unsigned DEFAULT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `audit_logs_action_created_at_index` (`action`,`created_at`),
  KEY `audit_logs_admin_id_created_at_index` (`admin_id`,`created_at`),
  CONSTRAINT `audit_logs_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=223 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `backups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `backups` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual',
  `size_bytes` bigint unsigned NOT NULL DEFAULT '0',
  `version` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `commit_sha` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `banners`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `banners` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `link_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `banners_is_active_index` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `books`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `books` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `category_id` bigint unsigned DEFAULT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `subtitle` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `short_description` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `author` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `edition` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pages` int unsigned DEFAULT NULL,
  `language` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'English',
  `cover_image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price_inr` decimal(10,2) NOT NULL,
  `price_usd` decimal(10,2) NOT NULL,
  `sale_price_inr` decimal(10,2) DEFAULT NULL,
  `sale_price_usd` decimal(10,2) DEFAULT NULL,
  `sale_starts_at` datetime DEFAULT NULL,
  `sale_ends_at` datetime DEFAULT NULL,
  `file_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_size` bigint unsigned DEFAULT NULL,
  `original_filename` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `isbn` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `published_at` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_featured` tinyint(1) NOT NULL DEFAULT '0',
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `books_slug_unique` (`slug`),
  KEY `books_is_active_is_featured_index` (`is_active`,`is_featured`),
  KEY `books_category_id_index` (`category_id`),
  CONSTRAINT `books_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=248 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `carts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `carts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `session_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `book_id` bigint unsigned NOT NULL,
  `quantity` int unsigned NOT NULL DEFAULT '1',
  `added_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `carts_book_id_foreign` (`book_id`),
  KEY `carts_user_id_book_id_index` (`user_id`,`book_id`),
  KEY `carts_session_id_book_id_index` (`session_id`,`book_id`),
  CONSTRAINT `carts_book_id_foreign` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
  CONSTRAINT `carts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=118 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_slug_unique` (`slug`),
  KEY `categories_is_active_index` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `combos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `combos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `book_ids` json NOT NULL,
  `combo_price_inr` decimal(10,2) NOT NULL,
  `combo_price_usd` decimal(10,2) NOT NULL,
  `sale_price_inr` decimal(10,2) DEFAULT NULL,
  `sale_price_usd` decimal(10,2) DEFAULT NULL,
  `sale_starts_at` timestamp NULL DEFAULT NULL,
  `sale_ends_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `combos_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `countries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `countries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `iso_code` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone_code` varchar(12) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `countries_iso_code_unique` (`iso_code`)
) ENGINE=InnoDB AUTO_INCREMENT=243 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `coupon_usages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `coupon_usages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `coupon_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `order_id` bigint unsigned NOT NULL,
  `used_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `coupon_usages_user_id_foreign` (`user_id`),
  KEY `coupon_usages_order_id_foreign` (`order_id`),
  KEY `coupon_usages_coupon_id_user_id_index` (`coupon_id`,`user_id`),
  CONSTRAINT `coupon_usages_coupon_id_foreign` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `coupon_usages_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `coupon_usages_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `coupons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `coupons` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` decimal(10,2) NOT NULL,
  `currency` varchar(4) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `min_order_amount` decimal(10,2) DEFAULT NULL,
  `max_uses` int unsigned DEFAULT NULL,
  `uses_count` int unsigned NOT NULL DEFAULT '0',
  `max_uses_per_user` int unsigned NOT NULL DEFAULT '1',
  `starts_at` timestamp NULL DEFAULT NULL,
  `ends_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `applies_to` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'all',
  `applies_book_ids` json DEFAULT NULL,
  `applies_category_ids` json DEFAULT NULL,
  `first_order_only` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `coupons_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=91 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `download_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `download_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `book_id` bigint unsigned NOT NULL,
  `order_id` bigint unsigned NOT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(1024) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `downloaded_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `download_logs_user_id_foreign` (`user_id`),
  KEY `download_logs_book_id_foreign` (`book_id`),
  KEY `download_logs_order_id_book_id_index` (`order_id`,`book_id`),
  CONSTRAINT `download_logs_book_id_foreign` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
  CONSTRAINT `download_logs_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `download_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned NOT NULL,
  `book_id` bigint unsigned NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `quantity` int unsigned NOT NULL DEFAULT '1',
  `line_total` decimal(10,2) NOT NULL,
  `currency` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_items_order_id_foreign` (`order_id`),
  KEY `order_items_book_id_foreign` (`book_id`),
  CONSTRAINT `order_items_book_id_foreign` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `order_items_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=103 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `orders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `order_number` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `currency` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `discount_total` decimal(10,2) NOT NULL DEFAULT '0.00',
  `tax_total` decimal(10,2) NOT NULL DEFAULT '0.00',
  `grand_total` decimal(10,2) NOT NULL,
  `status` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `payment_method` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `coupon_code` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `no_refund_accepted` tinyint(1) NOT NULL DEFAULT '0',
  `no_refund_accepted_at` timestamp NULL DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `orders_order_number_unique` (`order_number`),
  KEY `orders_user_id_status_index` (`user_id`,`status`),
  KEY `orders_status_index` (`status`),
  CONSTRAINT `orders_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=130 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pages_slug_unique` (`slug`),
  KEY `pages_is_active_index` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned NOT NULL,
  `razorpay_order_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `razorpay_payment_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'created',
  `raw_webhook` json DEFAULT NULL,
  `failure_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payments_razorpay_order_id_unique` (`razorpay_order_id`),
  UNIQUE KEY `payments_razorpay_payment_id_unique` (`razorpay_payment_id`),
  KEY `payments_order_id_foreign` (`order_id`),
  CONSTRAINT `payments_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `security_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `security_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `event` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `meta` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `security_logs_admin_id_foreign` (`admin_id`),
  KEY `security_logs_user_id_foreign` (`user_id`),
  KEY `security_logs_created_at_index` (`created_at`),
  KEY `security_logs_event_index` (`event`),
  CONSTRAINT `security_logs_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  CONSTRAINT `security_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=97 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `group` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'text',
  `is_secret` tinyint(1) NOT NULL DEFAULT '0',
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_key_unique` (`key`),
  KEY `settings_updated_by_foreign` (`updated_by`),
  KEY `settings_group_index` (`group`),
  CONSTRAINT `settings_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=92 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `country_code` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country_name` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pricing_region` enum('IN','INTL') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'INTL',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `is_blocked` tinyint(1) NOT NULL DEFAULT '0',
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `google_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_google_id_unique` (`google_id`),
  KEY `users_country_code_index` (`country_code`),
  KEY `users_pricing_region_index` (`pricing_region`)
) ENGINE=InnoDB AUTO_INCREMENT=343 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;


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

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000001_create_cache_table',1),(2,'0001_01_01_000002_create_jobs_table',1),(3,'2026_09_29_000010_create_countries_table',1),(4,'2026_09_29_000011_create_admins_table',1),(5,'2026_09_29_000012_create_users_table',1),(6,'2026_09_29_000020_add_role_to_admins_table',1),(7,'2026_09_29_000030_create_settings_table',1),(8,'2026_09_29_000031_create_audit_logs_table',1),(9,'2026_09_29_000040_create_categories_table',1),(10,'2026_09_29_000041_create_books_table',1),(11,'2026_09_29_000042_create_banners_table',1),(12,'2026_09_29_000043_create_carts_table',1),(13,'2026_09_29_000044_create_pages_table',1),(14,'2026_09_29_000050_create_orders_table',1),(15,'2026_09_29_000051_create_order_items_table',1),(16,'2026_09_29_000052_create_payments_table',1),(17,'2026_09_29_000053_create_download_logs_table',1),(18,'2026_09_29_000060_add_is_active_to_admins_table',1),(19,'2026_09_29_000060_create_coupons_table',1),(20,'2026_09_29_000061_create_coupon_usages_table',1),(21,'2026_09_29_000062_create_combos_table',1),(22,'2026_09_29_000070_add_two_factor_to_admins_table',1),(23,'2026_09_29_000071_create_security_logs_table',1),(24,'2026_09_29_000100_add_category_targeting_to_coupons_table',1),(25,'2026_09_29_120000_create_backups_table',1),(26,'2026_09_29_130000_add_google_id_to_users_table',1),(27,'2026_09_29_130001_make_user_country_nullable',1),(28,'2026_09_29_130002_add_tracking_to_download_logs_table',1),(29,'2026_09_29_130003_add_original_filename_to_books_table',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;


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

LOCK TABLES `countries` WRITE;
/*!40000 ALTER TABLE `countries` DISABLE KEYS */;
INSERT INTO `countries` VALUES (1,'AF','Afghanistan','+93'),(2,'AL','Albania','+355'),(3,'DZ','Algeria','+213'),(4,'AS','American Samoa','+1684'),(5,'AD','Andorra','+376'),(6,'AO','Angola','+244'),(7,'AI','Anguilla','+1264'),(8,'AQ','Antarctica','+672'),(9,'AG','Antigua and Barbuda','+1268'),(10,'AR','Argentina','+54'),(11,'AM','Armenia','+374'),(12,'AW','Aruba','+297'),(13,'AU','Australia','+61'),(14,'AT','Austria','+43'),(15,'AZ','Azerbaijan','+994'),(16,'BS','Bahamas','+1242'),(17,'BH','Bahrain','+973'),(18,'BD','Bangladesh','+880'),(19,'BB','Barbados','+1246'),(20,'BY','Belarus','+375'),(21,'BE','Belgium','+32'),(22,'BZ','Belize','+501'),(23,'BJ','Benin','+229'),(24,'BM','Bermuda','+1441'),(25,'BT','Bhutan','+975'),(26,'BO','Bolivia','+591'),(27,'BA','Bosnia and Herzegovina','+387'),(28,'BW','Botswana','+267'),(29,'BR','Brazil','+55'),(30,'IO','British Indian Ocean Territory','+246'),(31,'BN','Brunei','+673'),(32,'BG','Bulgaria','+359'),(33,'BF','Burkina Faso','+226'),(34,'BI','Burundi','+257'),(35,'KH','Cambodia','+855'),(36,'CM','Cameroon','+237'),(37,'CA','Canada','+1'),(38,'CV','Cape Verde','+238'),(39,'KY','Cayman Islands','+1345'),(40,'CF','Central African Republic','+236'),(41,'TD','Chad','+235'),(42,'CL','Chile','+56'),(43,'CN','China','+86'),(44,'CX','Christmas Island','+61'),(45,'CC','Cocos Islands','+61'),(46,'CO','Colombia','+57'),(47,'KM','Comoros','+269'),(48,'CG','Congo','+242'),(49,'CD','Congo, Democratic Republic of the','+243'),(50,'CK','Cook Islands','+682'),(51,'CR','Costa Rica','+506'),(52,'CI','Côte d\'Ivoire','+225'),(53,'HR','Croatia','+385'),(54,'CU','Cuba','+53'),(55,'CY','Cyprus','+357'),(56,'CZ','Czech Republic','+420'),(57,'DK','Denmark','+45'),(58,'DJ','Djibouti','+253'),(59,'DM','Dominica','+1767'),(60,'DO','Dominican Republic','+1809'),(61,'EC','Ecuador','+593'),(62,'EG','Egypt','+20'),(63,'SV','El Salvador','+503'),(64,'GQ','Equatorial Guinea','+240'),(65,'ER','Eritrea','+291'),(66,'EE','Estonia','+372'),(67,'ET','Ethiopia','+251'),(68,'FK','Falkland Islands','+500'),(69,'FO','Faroe Islands','+298'),(70,'FJ','Fiji','+679'),(71,'FI','Finland','+358'),(72,'FR','France','+33'),(73,'GF','French Guiana','+594'),(74,'PF','French Polynesia','+689'),(75,'GA','Gabon','+241'),(76,'GM','Gambia','+220'),(77,'GE','Georgia','+995'),(78,'DE','Germany','+49'),(79,'GH','Ghana','+233'),(80,'GI','Gibraltar','+350'),(81,'GR','Greece','+30'),(82,'GL','Greenland','+299'),(83,'GD','Grenada','+1473'),(84,'GP','Guadeloupe','+590'),(85,'GU','Guam','+1671'),(86,'GT','Guatemala','+502'),(87,'GG','Guernsey','+441481'),(88,'GN','Guinea','+224'),(89,'GW','Guinea-Bissau','+245'),(90,'GY','Guyana','+592'),(91,'HT','Haiti','+509'),(92,'HN','Honduras','+504'),(93,'HK','Hong Kong','+852'),(94,'HU','Hungary','+36'),(95,'IS','Iceland','+354'),(96,'IN','India','+91'),(97,'ID','Indonesia','+62'),(98,'IR','Iran','+98'),(99,'IQ','Iraq','+964'),(100,'IE','Ireland','+353'),(101,'IM','Isle of Man','+441624'),(102,'IL','Israel','+972'),(103,'IT','Italy','+39'),(104,'JM','Jamaica','+1876'),(105,'JP','Japan','+81'),(106,'JE','Jersey','+441534'),(107,'JO','Jordan','+962'),(108,'KZ','Kazakhstan','+7'),(109,'KE','Kenya','+254'),(110,'KI','Kiribati','+686'),(111,'KP','North Korea','+850'),(112,'KR','South Korea','+82'),(113,'KW','Kuwait','+965'),(114,'KG','Kyrgyzstan','+996'),(115,'LA','Laos','+856'),(116,'LV','Latvia','+371'),(117,'LB','Lebanon','+961'),(118,'LS','Lesotho','+266'),(119,'LR','Liberia','+231'),(120,'LY','Libya','+218'),(121,'LI','Liechtenstein','+423'),(122,'LT','Lithuania','+370'),(123,'LU','Luxembourg','+352'),(124,'MO','Macao','+853'),(125,'MK','North Macedonia','+389'),(126,'MG','Madagascar','+261'),(127,'MW','Malawi','+265'),(128,'MY','Malaysia','+60'),(129,'MV','Maldives','+960'),(130,'ML','Mali','+223'),(131,'MT','Malta','+356'),(132,'MH','Marshall Islands','+692'),(133,'MQ','Martinique','+596'),(134,'MR','Mauritania','+222'),(135,'MU','Mauritius','+230'),(136,'YT','Mayotte','+262'),(137,'MX','Mexico','+52'),(138,'FM','Micronesia','+691'),(139,'MD','Moldova','+373'),(140,'MC','Monaco','+377'),(141,'MN','Mongolia','+976'),(142,'ME','Montenegro','+382'),(143,'MS','Montserrat','+1664'),(144,'MA','Morocco','+212'),(145,'MZ','Mozambique','+258'),(146,'MM','Myanmar','+95'),(147,'NA','Namibia','+264'),(148,'NR','Nauru','+674'),(149,'NP','Nepal','+977'),(150,'NL','Netherlands','+31'),(151,'NC','New Caledonia','+687'),(152,'NZ','New Zealand','+64'),(153,'NI','Nicaragua','+505'),(154,'NE','Niger','+227'),(155,'NG','Nigeria','+234'),(156,'NU','Niue','+683'),(157,'NF','Norfolk Island','+672'),(158,'MP','Northern Mariana Islands','+1670'),(159,'NO','Norway','+47'),(160,'OM','Oman','+968'),(161,'PK','Pakistan','+92'),(162,'PW','Palau','+680'),(163,'PS','Palestine','+970'),(164,'PA','Panama','+507'),(165,'PG','Papua New Guinea','+675'),(166,'PY','Paraguay','+595'),(167,'PE','Peru','+51'),(168,'PH','Philippines','+63'),(169,'PN','Pitcairn Islands','+64'),(170,'PL','Poland','+48'),(171,'PT','Portugal','+351'),(172,'PR','Puerto Rico','+1787'),(173,'QA','Qatar','+974'),(174,'RE','Réunion','+262'),(175,'RO','Romania','+40'),(176,'RU','Russia','+7'),(177,'RW','Rwanda','+250'),(178,'BL','Saint Barthélemy','+590'),(179,'SH','Saint Helena','+290'),(180,'KN','Saint Kitts and Nevis','+1869'),(181,'LC','Saint Lucia','+1758'),(182,'MF','Saint Martin','+590'),(183,'PM','Saint Pierre and Miquelon','+508'),(184,'VC','Saint Vincent and the Grenadines','+1784'),(185,'WS','Samoa','+685'),(186,'SM','San Marino','+378'),(187,'ST','São Tomé and Príncipe','+239'),(188,'SA','Saudi Arabia','+966'),(189,'SN','Senegal','+221'),(190,'RS','Serbia','+381'),(191,'SC','Seychelles','+248'),(192,'SL','Sierra Leone','+232'),(193,'SG','Singapore','+65'),(194,'SX','Sint Maarten','+1721'),(195,'SK','Slovakia','+421'),(196,'SI','Slovenia','+386'),(197,'SB','Solomon Islands','+677'),(198,'SO','Somalia','+252'),(199,'ZA','South Africa','+27'),(200,'GS','South Georgia','+500'),(201,'SS','South Sudan','+211'),(202,'ES','Spain','+34'),(203,'LK','Sri Lanka','+94'),(204,'SD','Sudan','+249'),(205,'SR','Suriname','+597'),(206,'SJ','Svalbard and Jan Mayen','+47'),(207,'SZ','Eswatini','+268'),(208,'SE','Sweden','+46'),(209,'CH','Switzerland','+41'),(210,'SY','Syria','+963'),(211,'TW','Taiwan','+886'),(212,'TJ','Tajikistan','+992'),(213,'TZ','Tanzania','+255'),(214,'TH','Thailand','+66'),(215,'TL','Timor-Leste','+670'),(216,'TG','Togo','+228'),(217,'TK','Tokelau','+690'),(218,'TO','Tonga','+676'),(219,'TT','Trinidad and Tobago','+1868'),(220,'TN','Tunisia','+216'),(221,'TR','Turkey','+90'),(222,'TM','Turkmenistan','+993'),(223,'TC','Turks and Caicos Islands','+1649'),(224,'TV','Tuvalu','+688'),(225,'UG','Uganda','+256'),(226,'UA','Ukraine','+380'),(227,'AE','United Arab Emirates','+971'),(228,'GB','United Kingdom','+44'),(229,'US','United States','+1'),(230,'UY','Uruguay','+598'),(231,'UZ','Uzbekistan','+998'),(232,'VU','Vanuatu','+678'),(233,'VA','Vatican City','+379'),(234,'VE','Venezuela','+58'),(235,'VN','Vietnam','+84'),(236,'VG','British Virgin Islands','+1284'),(237,'VI','U.S. Virgin Islands','+1340'),(238,'WF','Wallis and Futuna','+681'),(239,'EH','Western Sahara','+212'),(240,'YE','Yemen','+967'),(241,'ZM','Zambia','+260'),(242,'ZW','Zimbabwe','+263');
/*!40000 ALTER TABLE `countries` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;


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

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES (1,'site.name','MyEbookBazaar','general','text',0,NULL,'2026-09-29 17:26:48','2026-09-29 17:26:48'),(2,'site.tagline','Simple ebooks for fitness, skills and money.','general','text',0,NULL,'2026-09-29 17:26:48','2026-09-29 17:26:48'),(3,'site.logo',NULL,'general','image',0,NULL,'2026-09-29 17:26:48','2026-09-29 17:26:48'),(4,'site.favicon',NULL,'general','image',0,NULL,'2026-09-29 17:26:48','2026-09-29 17:26:48'),(5,'site.support_email','support@myebookbazaar.com','general','text',0,NULL,'2026-09-29 17:26:48','2026-09-29 17:26:48'),(6,'site.phone',NULL,'general','text',0,NULL,'2026-09-29 17:26:48','2026-09-29 17:26:48'),(7,'site.address',NULL,'general','textarea',0,NULL,'2026-09-29 17:26:48','2026-09-29 17:26:48'),(8,'site.instagram','https://www.instagram.com/myebookbazaar','general','text',0,NULL,'2026-09-29 17:26:48','2026-09-29 17:26:48'),(9,'site.youtube',NULL,'general','text',0,NULL,'2026-09-29 17:26:48','2026-09-29 17:26:48'),(10,'site.x',NULL,'general','text',0,NULL,'2026-09-29 17:26:48','2026-09-29 17:26:48'),(11,'site.facebook',NULL,'general','text',0,NULL,'2026-09-29 17:26:48','2026-09-29 17:26:48'),(12,'site.telegram',NULL,'general','text',0,NULL,'2026-09-29 17:26:48','2026-09-29 17:26:48'),(13,'site.footer_text',NULL,'general','textarea',0,NULL,'2026-09-29 17:26:48','2026-09-29 17:26:48'),(14,'site.announcement_text','Our first ebook is on the way. Create a free account to get launch updates.','general','textarea',0,NULL,'2026-09-29 17:26:48','2026-09-29 17:26:48'),(15,'features.registration','1','features','boolean',0,NULL,'2026-09-29 17:26:48','2026-09-29 17:26:48'),(16,'features.login','1','features','boolean',0,NULL,'2026-09-29 17:26:48','2026-09-29 17:26:48'),(17,'features.email_verification','1','features','boolean',0,NULL,'2026-09-29 17:26:48','2026-09-29 17:26:48'),(18,'features.guest_browsing','1','features','boolean',0,NULL,'2026-09-29 17:26:48','2026-09-29 17:26:48'),(19,'features.guest_cart','1','features','boolean',0,NULL,'2026-09-29 17:26:48','2026-09-29 17:26:48'),(20,'features.buy_now','1','features','boolean',0,NULL,'2026-09-29 17:26:48','2026-09-29 17:26:48'),(21,'features.checkout','1','features','boolean',0,NULL,'2026-09-29 17:26:48','2026-09-29 17:26:48'),(22,'features.coupons','1','features','boolean',0,NULL,'2026-09-29 17:26:49','2026-09-29 17:26:49'),(23,'features.combos','1','features','boolean',0,NULL,'2026-09-29 17:26:49','2026-09-29 17:26:49'),(24,'features.sales_offers','1','features','boolean',0,NULL,'2026-09-29 17:26:49','2026-09-29 17:26:49'),(25,'features.announcement_bar','1','features','boolean',0,NULL,'2026-09-29 17:26:49','2026-09-29 17:26:49'),(26,'features.cookie_banner','1','features','boolean',0,NULL,'2026-09-29 17:26:49','2026-09-29 17:26:49'),(27,'features.contact_form','1','features','boolean',0,NULL,'2026-09-29 17:26:49','2026-09-29 17:26:49'),(28,'features.downloads','1','features','boolean',0,NULL,'2026-09-29 17:26:49','2026-09-29 17:26:49'),(29,'downloads.limit','5','features','number',0,NULL,'2026-09-29 17:26:49','2026-09-29 17:26:49'),(30,'pricing.inr_enabled','1','pricing','boolean',0,NULL,'2026-09-29 17:26:49','2026-09-29 17:26:49'),(31,'pricing.usd_enabled','1','pricing','boolean',0,NULL,'2026-09-29 17:26:49','2026-09-29 17:26:49'),(32,'pricing.default_region','IN','pricing','select',0,NULL,'2026-09-29 17:26:49','2026-09-29 17:26:49'),(33,'pricing.country_regions',NULL,'pricing','textarea',0,NULL,'2026-09-29 17:26:49','2026-09-29 17:26:49'),(34,'payments.razorpay_enabled','0','payments','boolean',0,NULL,'2026-09-29 17:26:49','2026-09-29 17:26:49'),(35,'payments.razorpay_mode','test','payments','select',0,NULL,'2026-09-29 17:26:49','2026-09-29 17:26:49'),(36,'payments.razorpay_key_id',NULL,'payments','text',0,NULL,'2026-09-29 17:26:49','2026-09-29 17:26:49'),(37,'payments.razorpay_key_secret',NULL,'payments','secret',1,NULL,'2026-09-29 17:26:49','2026-09-29 17:26:49'),(38,'payments.razorpay_webhook_secret',NULL,'payments','secret',1,NULL,'2026-09-29 17:26:49','2026-09-29 17:26:49'),(39,'payments.inr_enabled','1','payments','boolean',0,NULL,'2026-09-29 17:26:49','2026-09-29 17:26:49'),(40,'payments.usd_enabled','1','payments','boolean',0,NULL,'2026-09-29 17:26:49','2026-09-29 17:26:49'),(41,'mail.host',NULL,'mail','text',0,NULL,'2026-09-29 17:26:49','2026-09-29 17:26:49'),(42,'mail.port','587','mail','number',0,NULL,'2026-09-29 17:26:49','2026-09-29 17:26:49'),(43,'mail.username',NULL,'mail','text',0,NULL,'2026-09-29 17:26:49','2026-09-29 17:26:49'),(44,'mail.password',NULL,'mail','secret',1,NULL,'2026-09-29 17:26:49','2026-09-29 17:26:49'),(45,'mail.encryption','tls','mail','select',0,NULL,'2026-09-29 17:26:49','2026-09-29 17:26:49'),(46,'mail.from_name','MyEbookBazaar','mail','text',0,NULL,'2026-09-29 17:26:49','2026-09-29 17:26:49'),(47,'mail.from_address','hello@myebookbazaar.com','mail','text',0,NULL,'2026-09-29 17:26:49','2026-09-29 17:26:49'),(48,'security.turnstile_enabled','0','security','boolean',0,NULL,'2026-09-29 17:26:49','2026-09-29 17:26:49'),(49,'security.turnstile_site_key',NULL,'security','text',0,NULL,'2026-09-29 17:26:49','2026-09-29 17:26:49'),(50,'security.turnstile_secret_key',NULL,'security','secret',1,NULL,'2026-09-29 17:26:49','2026-09-29 17:26:49'),(51,'security.turnstile_register','0','security','boolean',0,NULL,'2026-09-29 17:26:49','2026-09-29 17:26:49'),(52,'security.turnstile_login','0','security','boolean',0,NULL,'2026-09-29 17:26:49','2026-09-29 17:26:49'),(53,'security.turnstile_forgot','0','security','boolean',0,NULL,'2026-09-29 17:26:49','2026-09-29 17:26:49'),(54,'security.turnstile_contact','0','security','boolean',0,NULL,'2026-09-29 17:26:49','2026-09-29 17:26:49'),(55,'security.turnstile_checkout','0','security','boolean',0,NULL,'2026-09-29 17:26:49','2026-09-29 17:26:49'),(56,'security.turnstile_admin_login','0','security','boolean',0,NULL,'2026-09-29 17:26:49','2026-09-29 17:26:49'),(57,'security.rate_limit_login','5','security','number',0,NULL,'2026-09-29 17:26:49','2026-09-29 17:26:49'),(58,'security.rate_limit_register','5','security','number',0,NULL,'2026-09-29 17:26:49','2026-09-29 17:26:49'),(59,'security.rate_limit_forgot','3','security','number',0,NULL,'2026-09-29 17:26:49','2026-09-29 17:26:49'),(60,'security.lockout_minutes','15','security','number',0,NULL,'2026-09-29 17:26:49','2026-09-29 17:26:49'),(61,'security.require_2fa_admin','0','security','boolean',0,NULL,'2026-09-29 17:26:49','2026-09-29 17:26:49'),(62,'security.honeypot_enabled','1','security','boolean',0,NULL,'2026-09-29 17:26:49','2026-09-29 17:26:49'),(63,'security.maintenance_enabled','','security','boolean',0,NULL,'2026-09-29 17:26:49','2026-09-29 17:37:43'),(64,'security.maintenance_message',NULL,'security','textarea',0,NULL,'2026-09-29 17:26:49','2026-09-29 17:26:49'),(65,'security.maintenance_bypass_ips',NULL,'security','textarea',0,NULL,'2026-09-29 17:26:49','2026-09-29 17:26:49'),(66,'security.cookie_consent_enabled','0','security','boolean',0,NULL,'2026-09-29 17:26:49','2026-09-29 17:26:49'),(67,'legal.privacy_policy',NULL,'legal','textarea',0,NULL,'2026-09-29 17:26:49','2026-09-29 17:26:49'),(68,'legal.no_refund_policy','All sales are final. Because our products are digital ebooks delivered instantly, we do not offer refunds.','legal','textarea',0,NULL,'2026-09-29 17:26:49','2026-09-29 17:26:49'),(69,'legal.terms',NULL,'legal','textarea',0,NULL,'2026-09-29 17:26:49','2026-09-29 17:26:49'),(70,'legal.cookie_notice',NULL,'legal','textarea',0,NULL,'2026-09-29 17:26:49','2026-09-29 17:26:49'),(71,'updates.enabled','','updates','boolean',0,NULL,'2026-09-29 17:26:49','2026-09-29 17:37:43'),(72,'updates.repo_owner',NULL,'updates','text',0,NULL,'2026-09-29 17:26:49','2026-09-29 17:37:43'),(73,'updates.repo_name',NULL,'updates','text',0,NULL,'2026-09-29 17:26:49','2026-09-29 17:37:43'),(74,'updates.branch','main','updates','text',0,NULL,'2026-09-29 17:26:49','2026-09-29 17:26:49'),(75,'updates.channel','stable','updates','select',0,NULL,'2026-09-29 17:26:49','2026-09-29 17:26:49'),(76,'updates.github_token',NULL,'updates','secret',1,NULL,'2026-09-29 17:26:49','2026-09-29 17:37:43'),(77,'updates.auto_check','1','updates','boolean',0,NULL,'2026-09-29 17:26:49','2026-09-29 17:26:49'),(78,'updates.backup_retention','7','updates','number',0,NULL,'2026-09-29 17:26:49','2026-09-29 17:26:49'),(79,'auth.google.enabled','0','updates','boolean',0,NULL,'2026-09-29 17:26:49','2026-09-29 17:26:49'),(80,'auth.google.client_id',NULL,'updates','text',0,NULL,'2026-09-29 17:26:49','2026-09-29 17:26:49'),(81,'auth.google.client_secret',NULL,'updates','secret',1,NULL,'2026-09-29 17:26:49','2026-09-29 17:26:49'),(82,'updates.installed_version',NULL,'updates','text',0,NULL,'2026-09-29 17:26:49','2026-09-29 17:37:43'),(83,'updates.installed_sha',NULL,'updates','text',0,NULL,'2026-09-29 17:26:49','2026-09-29 17:37:43'),(84,'updates.last_checked_at',NULL,'updates','text',0,NULL,'2026-09-29 17:26:49','2026-09-29 17:26:49'),(85,'updates.latest_sha',NULL,'updates','text',0,NULL,'2026-09-29 17:26:49','2026-09-29 17:26:49'),(86,'updates.release_tag',NULL,'updates','text',0,NULL,'2026-09-29 17:26:49','2026-09-29 17:26:49'),(87,'updates.release_name',NULL,'updates','text',0,NULL,'2026-09-29 17:26:49','2026-09-29 17:26:49'),(88,'updates.release_notes',NULL,'updates','textarea',0,NULL,'2026-09-29 17:26:49','2026-09-29 17:26:49');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;


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

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'Fitness & Diet','fitness-diet','Home workouts, simple nutrition and healthy-habit guides built for real Indian kitchens.',NULL,0,1,'2026-09-29 17:26:49','2026-09-29 17:26:49');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;


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

LOCK TABLES `books` WRITE;
/*!40000 ALTER TABLE `books` DISABLE KEYS */;
INSERT INTO `books` VALUES (1,1,'Fit at Home: Start (Edition 1)','fit-at-home-start-edition-1','The Simple Fitness & Diet Guide for Total Wellness','Fit at Home: Start is the no-gym, no-starvation way to begin your fitness journey.\n\nInside you get 10 chapters across 54 pages: simple home workouts that need no equipment, a full 30-day starter plan, and meal guidance built around everyday Indian food — dal, roti, rice, eggs and vegetables.\n\nEverything is written in plain, simple language. No complicated science, no expensive supplements, no gym membership needed. Just 20 minutes a day at home.\n\nWhat you get:\n- 10 easy-to-follow chapters (54 pages)\n- A complete 30-day starter workout plan\n- Indian-kitchen meal guidance with real food examples\n- Simple habit tips that fit a busy routine\n\nThis is Edition 1 of the Fit at Home series — the perfect starting point before Level Up and the Complete Edition.','The simple Indian-kitchen fitness and diet guide: home workouts and a 30-day starter plan.','MyEbookBazaar','1',54,'English',NULL,149.00,4.99,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,1,0,'2026-09-29 17:26:49','2026-09-29 17:26:49');
/*!40000 ALTER TABLE `books` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;


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

LOCK TABLES `pages` WRITE;
/*!40000 ALTER TABLE `pages` DISABLE KEYS */;
INSERT INTO `pages` VALUES (1,'about','About MyEbookBazaar','MyEbookBazaar is an online bookstore selling short, practical ebooks on fitness, skills and money — written in simple language and priced honestly.\n\nWhat we believe:\n\nGood guidance should be short enough to finish and clear enough to act on. Advice should fit real life — Indian kitchens, home workouts, small budgets. Prices should be honest: rupees for India, dollars for the rest of the world.\n\nWhat you get:\n\nEvery ebook is a PDF that works on any phone, tablet or computer. You pay once through secure Razorpay checkout and download instantly — no subscription, no waiting.',1,'2026-09-29 17:26:49','2026-09-29 17:26:49'),(2,'faq','Frequently asked questions','How do I pay?\nSecurely through Razorpay — UPI, credit and debit cards, and netbanking. Prices show in rupees for India and in dollars for other countries.\n\nHow do I get my ebook?\nRight after payment you get a download link in your account library. The file is a standard PDF that works on any phone, tablet or computer.\n\nCan I get a refund?\nAll sales are final because ebooks are digital products. Please read the book description carefully before you pay.\n\nDo I need an account to browse?\nNo. You can browse every book without an account. You only sign in when you are ready to buy.\n\nHow many times can I download my ebook?\nEvery purchase includes up to 5 downloads, so you can keep a copy on your phone and your computer.',1,'2026-09-29 17:26:49','2026-09-29 17:26:49');
/*!40000 ALTER TABLE `pages` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

SET FOREIGN_KEY_CHECKS = 1;

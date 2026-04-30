-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: share_hope
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

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
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `action_type` enum('create','update','delete','approve','deny','suspend','export','login','other') DEFAULT 'other',
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `entity_name` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `admin_id` (`admin_id`,`created_at`),
  KEY `action_type` (`action_type`,`created_at`),
  CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
INSERT INTO `activity_logs` VALUES (1,1,'export_stats','export','platform_stats',NULL,'Platform Stats','Exported platform statistics',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-07 22:06:28'),(2,1,'export_donations','export','donations',NULL,'Donations','Exported 6 donation records',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-07 22:06:35'),(3,1,'export_donations','export','donations',NULL,'Donations','Exported 6 donation records',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-08 14:05:43'),(4,1,'export_donations','export','donations',NULL,'Donations','Exported 6 donation records',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-08 14:10:21'),(5,1,'export_donations','export','donations',NULL,'Donations','Exported 6 donation records',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-08 14:10:23'),(6,1,'export_donations','export','donations',NULL,'Donations','Exported 6 donation records',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-08 14:22:52'),(7,1,'export_donations','export','donations',NULL,'Donations','Exported 6 donation records',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-08 14:23:00'),(8,1,'export_donations','export','donations',NULL,'Donations','Exported 6 donation records',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-08 14:27:40'),(9,1,'export_ngos','export','ngos',NULL,'NGOs','Exported 3 NGO records',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-08 14:28:11'),(10,1,'export_stats','export','platform_stats',NULL,'Platform Stats','Exported platform statistics',NULL,NULL,'192.168.100.9','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36','2026-03-08 18:03:47');
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin_permissions`
--

DROP TABLE IF EXISTS `admin_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL,
  `permission` varchar(100) NOT NULL,
  `granted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_permission` (`admin_id`,`permission`),
  CONSTRAINT `admin_permissions_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_permissions`
--

LOCK TABLES `admin_permissions` WRITE;
/*!40000 ALTER TABLE `admin_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `admin_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `announcements`
--

DROP TABLE IF EXISTS `announcements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `announcements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `target_audience` varchar(50) DEFAULT 'all',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_public` tinyint(1) DEFAULT 0,
  `action_link` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `announcements`
--

LOCK TABLES `announcements` WRITE;
/*!40000 ALTER TABLE `announcements` DISABLE KEYS */;
INSERT INTO `announcements` VALUES (1,NULL,'Public Emergency','need for an urgent contibution for a sudden pandemic','all','2026-03-05 21:21:51',1,''),(2,NULL,'jhhjokl;','hfcghjk;l','all','2026-03-07 13:35:33',1,'https:/cfyguhijokpl'),(3,1,'New In-Kind Donation Feature Available','We are excited to announce that NGOs can now accept in-kind donations such as food, clothing, medical supplies, and more. Visit the donation page to learn how to set up your in-kind campaigns!','all','2026-03-07 21:17:08',1,'/share_hope/campaigns.php');
/*!40000 ALTER TABLE `announcements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `campaign_updates`
--

DROP TABLE IF EXISTS `campaign_updates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `campaign_updates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `campaign_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `campaign_id` (`campaign_id`),
  CONSTRAINT `campaign_updates_ibfk_1` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `campaign_updates`
--

LOCK TABLES `campaign_updates` WRITE;
/*!40000 ALTER TABLE `campaign_updates` DISABLE KEYS */;
/*!40000 ALTER TABLE `campaign_updates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `campaigns`
--

DROP TABLE IF EXISTS `campaigns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `campaigns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ngo_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `goal_amount` decimal(15,2) NOT NULL,
  `current_amount` decimal(15,2) DEFAULT 0.00,
  `deadline` date DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `status` enum('active','completed','cancelled') DEFAULT 'active',
  `image_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ngo_id` (`ngo_id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `campaigns_ibfk_1` FOREIGN KEY (`ngo_id`) REFERENCES `ngos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `campaigns_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `campaigns`
--

LOCK TABLES `campaigns` WRITE;
/*!40000 ALTER TABLE `campaigns` DISABLE KEYS */;
INSERT INTO `campaigns` VALUES (1,1,'Drought Relief for Turkana Families','Providing emergency food and clean water access to pastoralist families affected by the severe drought in Turkana County.',500000.00,250175.00,NULL,NULL,'active','/share_hope/assets/uploads/images/a-robot-holding-a-small-bag-with-tools-attached-_000000.jpg','2026-03-03 00:30:56'),(2,1,'Flooding Emergency in Tana River','Supplying medical aid, blankets, and temporary shelter tents for households displaced by the River Tana bursting its banks.',800000.00,150000.00,NULL,NULL,'active','/share_hope/assets/uploads/images/Donation_concept__the_volunteer_giving_a_donate_box_to_the_recipient._standing_against_the_walll___Premium_Photo.jpg','2026-03-03 00:30:56'),(3,1,'Education Supplies for Kibera Schools','Equipping primary schools in Kibera with textbooks, desks, and solar lamps to ensure students can study safely at night.',300000.00,280000.00,NULL,NULL,'active','/share_hope/assets/uploads/images/Food_at_Godwin_Charity_Foundation.jpg','2026-03-03 00:30:56');
/*!40000 ALTER TABLE `campaigns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'Health','Medical and health related causes'),(2,'Education','Scholastic and educational funding'),(3,'Disaster Relief','Emergency responses and natural disasters'),(4,'Poverty Alleviation','Helping impoverished communities');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `data_backups`
--

DROP TABLE IF EXISTS `data_backups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `data_backups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entity_type` varchar(50) NOT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `payload` longtext NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `data_backups`
--

LOCK TABLES `data_backups` WRITE;
/*!40000 ALTER TABLE `data_backups` DISABLE KEYS */;
/*!40000 ALTER TABLE `data_backups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `donation_receipts`
--

DROP TABLE IF EXISTS `donation_receipts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `donation_receipts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `donation_id` int(11) NOT NULL,
  `receipt_number` varchar(50) NOT NULL,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `emailed_at` timestamp NULL DEFAULT NULL,
  `emailed_to` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `donation_id` (`donation_id`),
  UNIQUE KEY `receipt_number` (`receipt_number`),
  KEY `idx_receipt_number` (`receipt_number`),
  KEY `idx_emailed_at` (`emailed_at`),
  CONSTRAINT `donation_receipts_ibfk_1` FOREIGN KEY (`donation_id`) REFERENCES `donations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `donation_receipts`
--

LOCK TABLES `donation_receipts` WRITE;
/*!40000 ALTER TABLE `donation_receipts` DISABLE KEYS */;
/*!40000 ALTER TABLE `donation_receipts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `donations`
--

DROP TABLE IF EXISTS `donations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `donations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `campaign_id` int(11) NOT NULL,
  `donor_id` int(11) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `payment_method` enum('mpesa','card','bank','inkind','pledged') NOT NULL,
  `status` enum('pending','completed','failed') DEFAULT 'pending',
  `is_anonymous` tinyint(1) DEFAULT 0,
  `message` text DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `campaign_id` (`campaign_id`),
  KEY `donor_id` (`donor_id`),
  CONSTRAINT `donations_ibfk_1` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE,
  CONSTRAINT `donations_ibfk_2` FOREIGN KEY (`donor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `donations`
--

LOCK TABLES `donations` WRITE;
/*!40000 ALTER TABLE `donations` DISABLE KEYS */;
INSERT INTO `donations` VALUES (2,1,1,50.00,'mpesa','completed',0,'','MPESA_69A6795498FDD','2026-03-03 06:01:56'),(3,1,1,25.00,'mpesa','completed',0,'','MPESA_69A6C53F5170B','2026-03-03 11:25:51'),(4,1,1,25.00,'mpesa','completed',0,'','MPESA_69A6E1238BB0D','2026-03-03 13:24:51'),(5,1,4,25.00,'mpesa','completed',1,'','MPESA_69A6EB400AE74','2026-03-03 14:08:00'),(6,1,5,25.00,'mpesa','completed',0,'','MPESA_69A73D7DC7255','2026-03-03 19:58:53'),(7,1,6,25.00,'mpesa','completed',0,'','MPESA_69A740BF89DF8','2026-03-03 20:12:47');
/*!40000 ALTER TABLE `donations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `email_verification`
--

DROP TABLE IF EXISTS `email_verification`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `email_verification` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(100) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  UNIQUE KEY `token` (`token`),
  CONSTRAINT `email_verification_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_verification`
--

LOCK TABLES `email_verification` WRITE;
/*!40000 ALTER TABLE `email_verification` DISABLE KEYS */;
/*!40000 ALTER TABLE `email_verification` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inkind_donations`
--

DROP TABLE IF EXISTS `inkind_donations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inkind_donations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `campaign_id` int(11) NOT NULL,
  `donor_id` int(11) DEFAULT NULL,
  `donor_name` varchar(100) DEFAULT NULL,
  `donor_email` varchar(100) DEFAULT NULL,
  `donor_phone` varchar(20) DEFAULT NULL,
  `item_category` enum('Food','Clothing','Medical Supplies','Books/Education','Other') NOT NULL,
  `item_description` text NOT NULL,
  `quantity` varchar(100) NOT NULL,
  `status` enum('pledged','received','distributed') DEFAULT 'pledged',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `campaign_id` (`campaign_id`),
  KEY `donor_id` (`donor_id`),
  CONSTRAINT `inkind_donations_ibfk_1` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inkind_donations_ibfk_2` FOREIGN KEY (`donor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inkind_donations`
--

LOCK TABLES `inkind_donations` WRITE;
/*!40000 ALTER TABLE `inkind_donations` DISABLE KEYS */;
INSERT INTO `inkind_donations` VALUES (1,2,NULL,'hjkl','xvvkk@gfldmg.com','433232323','Clothing','bhjhkj','6','pledged','2026-03-03 14:57:26'),(2,3,5,NULL,NULL,NULL,'Clothing','Shirts and trousers','2','pledged','2026-03-03 20:53:38');
/*!40000 ALTER TABLE `inkind_donations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `login_attempts`
--

DROP TABLE IF EXISTS `login_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `success` tinyint(1) DEFAULT 0,
  `attempted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_email_ip` (`email`,`ip_address`,`attempted_at`),
  KEY `idx_user_id` (`user_id`,`attempted_at`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_attempts`
--

LOCK TABLES `login_attempts` WRITE;
/*!40000 ALTER TABLE `login_attempts` DISABLE KEYS */;
INSERT INTO `login_attempts` VALUES (1,5,'samuelmemia360@gmail.com','::1',0,'2026-03-07 21:38:02'),(2,1,'admin@sharehope.org','::1',1,'2026-03-07 21:45:52'),(3,5,'samuelmemia360@gmail.com','::1',0,'2026-03-07 21:49:57'),(4,1,'admin@sharehope.org','::1',1,'2026-03-07 21:50:26'),(5,1,'admin@sharehope.org','::1',1,'2026-03-08 14:03:29'),(6,1,'admin@sharehope.org','192.168.100.9',1,'2026-03-08 16:29:07'),(7,1,'admin@sharehope.org','192.168.100.9',1,'2026-03-08 16:36:33'),(8,1,'admin@sharehope.org','192.168.100.9',1,'2026-03-08 16:52:07'),(9,1,'admin@sharehope.org','192.168.100.9',1,'2026-03-08 17:00:20');
/*!40000 ALTER TABLE `login_attempts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ngos`
--

DROP TABLE IF EXISTS `ngos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ngos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `mission` text DEFAULT NULL,
  `contact_details` text DEFAULT NULL,
  `verification_doc` varchar(255) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `ngos_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ngos`
--

LOCK TABLES `ngos` WRITE;
/*!40000 ALTER TABLE `ngos` DISABLE KEYS */;
INSERT INTO `ngos` VALUES (1,2,NULL,'Local NGO',NULL,NULL,1,'2026-03-03 00:30:08',0.51330000,35.29080000),(2,3,NULL,'Bringing hope to those who need it most.',NULL,NULL,1,'2026-03-03 13:56:03',NULL,NULL),(3,8,NULL,'Trusted organisations',NULL,'/assets/uploads/docs/doc_69a74b9cd6bc1.jpg',1,'2026-03-03 20:59:08',NULL,NULL);
/*!40000 ALTER TABLE `ngos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (2,1,'Your M-Pesa donation of KSh 50.00 to Drought Relief for Turkana Families was successful. Thank you!',1,'2026-03-03 06:01:56'),(3,2,'You received a new M-Pesa donation of KSh 50.00 for your campaign: Drought Relief for Turkana Families (Phone: 0704165274)',0,'2026-03-03 06:01:56'),(4,1,'Your M-Pesa donation of KSh 25.00 to Drought Relief for Turkana Families was successful. Thank you!',1,'2026-03-03 11:25:51'),(5,2,'You received a new M-Pesa donation of KSh 25.00 for your campaign: Drought Relief for Turkana Families (Phone: 0704165274)',0,'2026-03-03 11:25:51'),(6,1,'Your M-Pesa donation of KSh 25.00 to Drought Relief for Turkana Families was successful. Thank you!',1,'2026-03-03 13:24:51'),(7,2,'You received a new M-Pesa donation of KSh 25.00 for your campaign: Drought Relief for Turkana Families (Phone: 0704165274)',0,'2026-03-03 13:24:51'),(8,4,'Your M-Pesa donation of KSh 25.00 to Drought Relief for Turkana Families was successful. Thank you!',0,'2026-03-03 14:08:00'),(9,2,'You received a new M-Pesa donation of KSh 25.00 for your campaign: Drought Relief for Turkana Families (Phone: 0704165274)',0,'2026-03-03 14:08:00'),(10,2,'New In-Kind Pledge Received: A donor pledged 6 of Clothing for your campaign \'Flooding Emergency in Tana River\'. Check your dashboard to contact them.',0,'2026-03-03 14:57:26'),(11,5,'Your M-Pesa donation of KSh 25.00 to Drought Relief for Turkana Families was successful. Thank you!',0,'2026-03-03 19:58:53'),(12,2,'You received a new M-Pesa donation of KSh 25.00 for your campaign: Drought Relief for Turkana Families (Phone: 0704165274)',0,'2026-03-03 19:58:53'),(13,6,'Your M-Pesa donation of KSh 25.00 to Drought Relief for Turkana Families was successful. Thank you!',0,'2026-03-03 20:12:47'),(14,2,'You received a new M-Pesa donation of KSh 25.00 for your campaign: Drought Relief for Turkana Families (Phone: 0755654437)',0,'2026-03-03 20:12:47'),(15,2,'New In-Kind Pledge Received: A donor pledged 2 of Clothing for your campaign \'Education Supplies for Kibera Schools\'. Check your dashboard to contact them.',0,'2026-03-03 20:53:38'),(16,8,'Congratulations! Your NGO application has been approved. You can now create campaigns.',0,'2026-03-03 21:00:20');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `donation_id` int(11) NOT NULL,
  `payment_gateway` varchar(50) DEFAULT NULL,
  `payment_status` varchar(50) DEFAULT NULL,
  `gateway_response` text DEFAULT NULL,
  `processed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `donation_id` (`donation_id`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`donation_id`) REFERENCES `donations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` VALUES (2,2,'mpesa','success','{\"mock_response\":\"M-Pesa Sandbox Verification Success\", \"phone\":\"0704165274\"}','2026-03-03 06:01:56'),(3,3,'mpesa','success','{\"mock_response\":\"M-Pesa Sandbox Verification Success\", \"phone\":\"0704165274\"}','2026-03-03 11:25:51'),(4,4,'mpesa','success','{\"mock_response\":\"M-Pesa Sandbox Verification Success\", \"phone\":\"0704165274\"}','2026-03-03 13:24:51'),(5,5,'mpesa','success','{\"mock_response\":\"M-Pesa Sandbox Verification Success\", \"phone\":\"0704165274\"}','2026-03-03 14:08:00'),(6,6,'mpesa','success','{\"mock_response\":\"M-Pesa Sandbox Verification Success\", \"phone\":\"0704165274\"}','2026-03-03 19:58:53'),(7,7,'mpesa','success','{\"mock_response\":\"M-Pesa Sandbox Verification Success\", \"phone\":\"0755654437\"}','2026-03-03 20:12:47');
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `protected_users`
--

DROP TABLE IF EXISTS `protected_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `protected_users` (
  `user_id` int(11) NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`user_id`),
  CONSTRAINT `protected_users_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `protected_users`
--

LOCK TABLES `protected_users` WRITE;
/*!40000 ALTER TABLE `protected_users` DISABLE KEYS */;
INSERT INTO `protected_users` VALUES (1,'Primary protected admin account',1,'2026-03-08 16:00:13');
/*!40000 ALTER TABLE `protected_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `schema_migrations`
--

DROP TABLE IF EXISTS `schema_migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `schema_migrations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `migration_name` varchar(255) NOT NULL,
  `checksum` char(64) NOT NULL,
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `migration_name` (`migration_name`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `schema_migrations`
--

LOCK TABLES `schema_migrations` WRITE;
/*!40000 ALTER TABLE `schema_migrations` DISABLE KEYS */;
INSERT INTO `schema_migrations` VALUES (1,'20260308_000_add_users_last_login.sql','839700646bdc5a187bda39c307ce7c164ad2e2658285355ed5a4d7db4378d2d2','2026-03-08 18:39:22'),(2,'20260308_001_add_admin_tables.sql','51ed433d3ad49b91f684ece520406b7d2def8d5c06945c0d5853d60a88c32319','2026-03-08 18:40:09'),(3,'20260308_002_add_admin_roles.sql','340f1c76a1354bae74d8a11b87ef4a082574ead8e19db2adab8bac4ae8ea2a6e','2026-03-08 18:40:09'),(4,'20260308_003_add_receipts.sql','fe722c6162ea6ebdacf0d3197a0e015556a2d97930a4fb7fafd9627ba0e2917a','2026-03-08 18:40:09'),(5,'20260308_004_add_social_media.sql','18567d3ae1e6ca82eac1352df478a4f10256a2ce77c74b3530fde68859917335','2026-03-08 18:40:09'),(6,'20260308_005_data_protection.sql','e4e28e089004cd46a119fd25e060b838872ab3d519db68dea0b9c80d2fd9ad95','2026-03-08 18:40:09'),(7,'20260308_006_security_features.sql','0e7cc120be0d30d3df9bb99bc50fb0eebb2932f4410ba00975ab4c5cd84a3436','2026-03-08 18:40:09');
/*!40000 ALTER TABLE `schema_migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `social_media_links`
--

DROP TABLE IF EXISTS `social_media_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `social_media_links` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `platform` varchar(50) NOT NULL,
  `platform_name` varchar(100) NOT NULL,
  `icon_class` varchar(100) NOT NULL,
  `url` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `platform` (`platform`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `social_media_links`
--

LOCK TABLES `social_media_links` WRITE;
/*!40000 ALTER TABLE `social_media_links` DISABLE KEYS */;
INSERT INTO `social_media_links` VALUES (1,'twitter','Twitter','fa-brands fa-twitter','#',0,1,'2026-03-07 21:25:03','2026-03-07 21:25:03'),(2,'facebook','Facebook','fa-brands fa-facebook-f','https://www.facebook.com/sharehope',1,2,'2026-03-07 21:25:03','2026-03-07 21:26:22'),(3,'instagram','Instagram','fa-brands fa-instagram','https://www.instagram.com/sharehope',1,3,'2026-03-07 21:25:03','2026-03-07 21:26:22'),(4,'whatsapp','WhatsApp','fa-brands fa-whatsapp','https://chat.whatsapp.com/C5KQvR5JXzXZYqX',1,1,'2026-03-07 21:25:03','2026-03-07 21:26:22'),(5,'linkedin','LinkedIn','fa-brands fa-linkedin-in','#',0,5,'2026-03-07 21:25:03','2026-03-07 21:25:03'),(6,'telegram','Telegram','fa-brands fa-telegram','https://t.me/sharehe',1,4,'2026-03-07 21:25:03','2026-03-07 21:26:22'),(7,'youtube','YouTube','fa-brands fa-youtube','#',0,7,'2026-03-07 21:25:03','2026-03-07 21:25:03'),(8,'tiktok','TikTok','fa-brands fa-tiktok','#',0,8,'2026-03-07 21:25:03','2026-03-07 21:25:03');
/*!40000 ALTER TABLE `social_media_links` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role` enum('admin','ngo','donor') NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `status` enum('active','suspended') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expires_at` datetime DEFAULT NULL,
  `email_verified` tinyint(1) DEFAULT 0,
  `account_locked_until` timestamp NULL DEFAULT NULL,
  `failed_login_attempts` int(11) DEFAULT 0,
  `role_level` int(11) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','Super Admin','admin@sharehope.org','1234567890','$2y$10$tmjwgEUZfcNavhvI7ejCVOVG6XepHLwlEHxIDLM5/BXsXgUS2boCO','active','2026-03-02 22:12:57',NULL,NULL,NULL,0,NULL,0,1),(2,'ngo','Hope Foundation Kenya','ngo@sharehope.org',NULL,'testhash','active','2026-03-03 00:30:08',NULL,NULL,NULL,0,NULL,0,1),(3,'ngo','Hope Africa Foundation','hope@africa.org',NULL,'$2y$10$azUyLGZU/qDZlGZc8d2.vuf6mGiP0ensLbjjKFj6APayXCRTHFvgC','active','2026-03-03 13:56:03',NULL,NULL,NULL,0,NULL,0,1),(4,'donor','John Doe','john@example.com',NULL,'$2y$10$azUyLGZU/qDZlGZc8d2.vuf6mGiP0ensLbjjKFj6APayXCRTHFvgC','active','2026-03-03 13:56:03',NULL,NULL,NULL,0,NULL,0,1),(5,'donor','Samuel mwangi','samuelmemia360@gmail.com','0704165274','$2y$10$JGlr09HjQtC32L22/9I9A.02uaYNaN1Ej5a1hnuk4J8EoehJFbtJS','active','2026-03-03 19:56:10',NULL,'4bfc49f8e178de951d3763e0d379aa9961c0f7dbb674fa44af86ca84c804e51b','2026-03-04 11:22:31',0,NULL,2,1),(6,'donor','Joseph','josephmutembeimakau312@gmail.com','0723002157','$2y$10$2RfMyE5TOAYkOJBV6ffoVOKDMWFME2kiSjWKX0KGNkkBXJpsSbZi2','active','2026-03-03 20:07:50',NULL,NULL,NULL,0,NULL,0,1),(7,'donor','Jackline Wanjiru','jacklynewanjiru8@gmail.com','0708322433','$2y$10$xPiRNuJdjIoivm5bYSJlCeWwEqkWB.X1wiABgNczQ2HakhF.z9bvS','active','2026-03-03 20:22:51',NULL,NULL,NULL,0,NULL,0,1),(8,'ngo','Joyhope foundation','joyhopefoundatiin@gmail.com','','$2y$10$Rsooh6UmiqmHUWqYnE93lOtix1NVnqctgq7bsjgw9RXeOgMHaezIq','active','2026-03-03 20:59:08',NULL,NULL,NULL,0,NULL,0,1);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'share_hope'
--

--
-- Dumping routines for database 'share_hope'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-03-08 21:41:12

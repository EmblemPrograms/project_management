-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: May 20, 2026 at 08:17 AM
-- Server version: 8.0.31
-- PHP Version: 8.0.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `project_repo`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
CREATE TABLE IF NOT EXISTS `admins` (
  `id` int NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('grand_admin','department_admin') NOT NULL,
  `department_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `department_id` (`department_id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `full_name`, `email`, `password_hash`, `role`, `department_id`, `created_at`) VALUES
(1, 'Grand Admin', 'grandadmin@nacosfpe.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'grand_admin', NULL, '2026-04-15 23:45:31'),
(2, 'Mr Moses', 'aduragbemi784@gmail.com', '$2y$10$lh/4pAy/ZjKascd8DQ0EBuV9HmmgE0OB7PAOMZLxI0ewM8W1PmSQO', 'department_admin', 3, '2026-04-16 00:56:41'),
(3, 'MRr Rahmon', 'engrpossiblemajor25@gmail.com', '$2y$10$R7KRwSxnbyu3/isW3q.gS.cAunWbhBmgyg3fjhGoJm3u0CeezWdPe', 'department_admin', 1, '2026-04-16 03:24:45'),
(4, 'Akinwale Adedigba', 'akinwale@gmail.com', '$2y$10$ULSzd/lkq8M7bz3IGGI3yOtGRXoQ//MftMNqYXObDYQe1kQlj2Lii', 'department_admin', 2, '2026-04-16 10:43:45');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
CREATE TABLE IF NOT EXISTS `departments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`) VALUES
(1, 'Computer Science'),
(2, 'Software and Web Development'),
(3, 'Cloud Computing and Networking');

-- --------------------------------------------------------

--
-- Table structure for table `email_verification`
--

DROP TABLE IF EXISTS `email_verification`;
CREATE TABLE IF NOT EXISTS `email_verification` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `otp` varchar(6) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `email_verification`
--

INSERT INTO `email_verification` (`id`, `user_id`, `otp`, `expires_at`, `used`, `created_at`) VALUES
(1, 11, '307532', '2026-04-11 17:50:18', 0, '2026-04-16 03:00:58'),
(2, 12, '581815', '2026-04-11 17:56:18', 0, '2026-04-16 03:00:58'),
(3, 13, '095807', '2026-04-11 18:13:00', 0, '2026-04-16 03:00:58'),
(4, 14, '256025', '2026-04-12 06:10:18', 0, '2026-04-16 03:00:58'),
(5, 15, '876401', '2026-04-12 08:49:42', 1, '2026-04-16 03:00:58'),
(6, 15, '974868', '2026-04-12 17:09:08', 0, '2026-04-16 03:00:58'),
(7, 15, '145044', '2026-04-12 17:09:56', 1, '2026-04-16 03:00:58'),
(8, 15, '896069', '2026-04-12 17:12:17', 1, '2026-04-16 03:00:58'),
(9, 16, '248706', '2026-04-13 13:30:48', 1, '2026-04-16 03:00:58'),
(10, 16, '779759', '2026-04-13 12:03:20', 1, '2026-04-16 03:00:58'),
(11, 22, '543511', '2026-04-14 18:39:53', 0, '2026-04-16 03:00:58'),
(12, 23, '044476', '2026-04-14 19:03:13', 0, '2026-04-16 03:00:58'),
(13, 24, '445427', '2026-04-14 19:05:30', 0, '2026-04-16 03:00:58'),
(14, 1, '007324', '2026-04-14 19:07:22', 0, '2026-04-16 03:00:58'),
(15, 1, '333145', '2026-04-14 19:09:06', 0, '2026-04-16 03:00:58'),
(16, 1, '222691', '2026-04-14 19:12:49', 0, '2026-04-16 03:00:58'),
(17, 1, '812483', '2026-04-14 17:56:02', 1, '2026-04-16 03:00:58'),
(18, 2, '336637', '2026-04-14 18:03:19', 1, '2026-04-16 03:00:58'),
(19, 1, '905882', '2026-04-15 17:42:04', 0, '2026-04-16 03:00:58'),
(20, 2, '034125', '2026-04-15 17:45:14', 0, '2026-04-16 03:00:58'),
(21, 3, '035590', '2026-04-15 17:46:57', 0, '2026-04-16 03:00:58'),
(22, 4, '215915', '2026-04-15 17:47:53', 0, '2026-04-16 03:00:58'),
(23, 5, '522428', '2026-04-15 17:51:27', 0, '2026-04-16 03:00:58'),
(24, 6, '659280', '2026-04-15 17:55:55', 0, '2026-04-16 03:00:58'),
(25, 1, '666675', '2026-04-16 03:30:17', 0, '2026-04-16 03:00:58'),
(26, 1, '992710', '2026-04-16 03:31:05', 0, '2026-04-16 03:01:05'),
(27, 1, '639082', '2026-04-16 03:31:14', 0, '2026-04-16 03:01:14'),
(28, 1, '519702', '2026-04-16 03:32:45', 0, '2026-04-16 03:02:45'),
(29, 5, '484133', '2026-04-16 10:43:27', 1, '2026-04-16 10:13:27'),
(30, 4, '321118', '2026-04-16 12:10:57', 1, '2026-04-16 11:40:57'),
(31, 7, '000991', '2026-04-16 12:26:37', 0, '2026-04-16 11:56:37'),
(32, 6, '471037', '2026-04-16 12:26:52', 0, '2026-04-16 11:56:52'),
(33, 7, '832744', '2026-04-16 12:29:14', 0, '2026-04-16 11:59:14'),
(34, 6, '140464', '2026-04-16 12:31:00', 1, '2026-04-16 12:01:00'),
(35, 7, '099536', '2026-04-16 12:31:19', 0, '2026-04-16 12:01:19'),
(36, 1, '379894', '2026-04-17 16:49:36', 0, '2026-04-17 16:19:36'),
(37, 1, '918133', '2026-04-17 16:49:40', 1, '2026-04-17 16:19:40');

-- --------------------------------------------------------

--
-- Table structure for table `nd_pairs`
--

DROP TABLE IF EXISTS `nd_pairs`;
CREATE TABLE IF NOT EXISTS `nd_pairs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student1_id` int NOT NULL,
  `student2_id` int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `student1_id` (`student1_id`),
  KEY `student2_id` (`student2_id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `nd_pairs`
--

INSERT INTO `nd_pairs` (`id`, `student1_id`, `student2_id`, `created_at`) VALUES
(1, 7, 8, '2026-04-15 18:44:34'),
(2, 9, 10, '2026-04-15 18:53:52'),
(3, 1, 2, '2026-04-15 20:33:44'),
(4, 2, 3, '2026-04-16 04:28:47'),
(5, 4, 5, '2026-04-16 09:48:25'),
(6, 6, 7, '2026-04-16 11:49:22');

-- --------------------------------------------------------

--
-- Table structure for table `pending_registrations`
--

DROP TABLE IF EXISTS `pending_registrations`;
CREATE TABLE IF NOT EXISTS `pending_registrations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `temp_id` varchar(50) NOT NULL,
  `level` enum('ND','HND') NOT NULL,
  `nd_type` varchar(10) DEFAULT NULL,
  `department_id` int NOT NULL,
  `session` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `matric_no` varchar(50) NOT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(100) NOT NULL,
  `contact` varchar(20) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `passport` varchar(255) NOT NULL,
  `pair_data` json DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT '2000.00',
  `status` enum('pending_payment','paid','failed') DEFAULT 'pending_payment',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `temp_id` (`temp_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `pending_registrations`
--

INSERT INTO `pending_registrations` (`id`, `temp_id`, `level`, `nd_type`, `department_id`, `session`, `address`, `matric_no`, `name`, `email`, `contact`, `password_hash`, `passport`, `pair_data`, `amount`, `status`, `created_at`) VALUES
(1, 'HND_17764390651180', 'HND', NULL, 2, '2024/2025', 'PLOT 13 BLOCK C IBUKUN OLU LAYOUT ANIFALAJE AKOBO, IBADAN, ., Nigeria', 'SW20240113314', 'Fawwas Ayomide Olajide', 'emblemprogram08@yahoo.com', '07089410451', '$2y$10$aiKOOeYIS7GABLdLYt4uZO9LFU9Hv63qexqE6fvINhEti678Dpnzq', 'uploads/passports/pass_1776439065_20511.jpg', NULL, '2000.00', 'paid', '2026-04-17 16:17:45');

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

DROP TABLE IF EXISTS `projects`;
CREATE TABLE IF NOT EXISTS `projects` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `upload_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('pending','approved','rejected') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT 'pending',
  `remark` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `source_code_path` varchar(255) DEFAULT NULL,
  `abstract` text,
  `supervisor` varchar(255) DEFAULT NULL,
  `uploaded_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `student_id`, `title`, `file_path`, `upload_date`, `status`, `remark`, `approved_at`, `source_code_path`, `abstract`, `supervisor`, `uploaded_at`) VALUES
(1, 6, 'design', 'uploads/projects/proj_69d9a2451e34f.docx', '2026-04-11 01:22:13', 'approved', '', NULL, NULL, NULL, NULL, '2026-04-16 04:18:11'),
(2, 15, 'Academy Research Based on Hardware Component Installation Related Issues', 'uploads/projects/proj_69dbbd9a25903.pdf', '2026-04-12 15:43:22', 'pending', '', NULL, NULL, NULL, NULL, '2026-04-16 04:18:11'),
(3, 16, 'Academy Research Based on Hardware Component Installation Related Issues', 'uploads/projects/proj_69dcc6afae698.pdf', '2026-04-13 10:34:23', 'pending', '', NULL, NULL, NULL, NULL, '2026-04-16 04:18:11'),
(4, 1, 'Design and implementation of job portal', 'uploads/projects/proj_69e04bbc1ee0f.pdf', '2026-04-16 02:41:14', 'approved', '', '2026-04-27 05:15:16', NULL, 'i love project', 'Mr Moses', '2026-04-16 04:18:11'),
(5, 5, 'DEVELOPMENT OF A SECURE FILE ENCRYPTION AND DECRYPTION SYSTEM USING BLOCKCHAIN', 'uploads/projects/proj_69e0a83753d3e.pdf', '2026-04-16 09:14:25', 'approved', '', NULL, NULL, 'In the era of rapid digital transformation driven by advancements in computer networking and information technology, the secure storage, transmission, and management of sensitive files have become critical challenges across various sectors. Traditional centralized file management systems suffer from significant limitations, including vulnerability to hacking, unauthorized tampering, single points of failure, and privacy breaches. These systems often rely on conventional security measures that struggle with scalability, reliability, user trust, and efficient data access, especially when handling large volumes of confidential documents such as personal records, intellectual property, financial data, or academic credentials.\r\nBlockchain technology has emerged as a transformative solution for building decentralized, immutable, and transparent systems for data handling. By recording transactions in a distributed ledger that is cryptographically secured and verified across multiple nodes, blockchain eliminates reliance on any single authority, making it extremely difficult for attackers to alter data without consensus from the majority of the network. When integrated with robust encryption techniques, blockchain enables secure file encryption and decryption while ensuring data confidentiality, integrity, and authenticity. This combination allows files to be stored in linked blocks, protected by cryptographic hashes, and shared in a tamper-proof manner without compromising privacy.', 'Mr Adegoke Moses', '2026-04-16 10:14:25'),
(6, 4, 'TTRTRTRTADD', 'uploads/projects/proj_69e0bcb990433.pdf', '2026-04-16 10:41:29', 'pending', '', NULL, NULL, 'SFFXVXV', 'Mr Adegoke Moses', '2026-04-16 11:41:29'),
(7, 6, 'design and implementation of software', 'uploads/projects/proj_69e0c16c2ec4a.pdf', '2026-04-16 11:02:56', 'pending', '', NULL, NULL, 'In the era of rapid digital transformation driven by advancements in computer networking and information technology, the secure storage, transmission, and management of sensitive files have become critical challenges across various sectors. Traditional centralized file management systems suffer from significant limitations, including vulnerability to hacking, unauthorized tampering, single points of failure, and privacy breaches. These systems often rely on conventional security measures that struggle with scalability, reliability, user trust, and efficient data access, especially when handling large volumes of confidential documents such as personal records, intellectual property, financial data, or academic credentials.\r\nBlockchain technology has emerged as a transformative solution for building decentralized, immutable, and transparent systems for data handling. By recording transactions in a distributed ledger that is cryptographically secured and verified across multiple nodes, blockchain eliminates reliance on any single authority, making it extremely difficult for attackers to alter data without consensus from the majority of the network. When integrated with robust encryption techniques, blockchain enables secure file encryption and decryption while ensuring data confidentiality, integrity, and authenticity. This combination allows files to be stored in linked blocks, protected by cryptographic hashes, and shared in a tamper-proof manner without compromising privacy.', 'Mr Adegoke Moses', '2026-04-16 12:02:56'),
(8, 1, 'Academy Research Based on Hardware Component Installation Related Issues', 'uploads/projects/proj_69e24f8cca6b9.pdf', '2026-04-17 15:20:19', 'rejected', 'The Project is empty', '2026-05-20 07:58:07', NULL, 'Academy Research Based on Hardware Component Installation Related Issues', 'Mr Adegoke Moses', '2026-04-17 16:20:19');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

DROP TABLE IF EXISTS `students`;
CREATE TABLE IF NOT EXISTS `students` (
  `id` int NOT NULL AUTO_INCREMENT,
  `matric_no` varchar(20) DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `contact` varchar(50) DEFAULT NULL,
  `session` varchar(20) NOT NULL,
  `address` text,
  `department_id` int NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('student') DEFAULT 'student',
  `passport` varchar(255) DEFAULT NULL,
  `level` varchar(10) DEFAULT NULL,
  `nd_type` varchar(10) DEFAULT NULL,
  `pair_id` int DEFAULT NULL,
  `supervisor` varchar(100) DEFAULT NULL,
  `project_title` varchar(255) DEFAULT NULL,
  `approved` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `matric_no` (`matric_no`),
  KEY `idx_matric_no` (`matric_no`),
  KEY `idx_email` (`email`),
  KEY `idx_pair` (`pair_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `matric_no`, `name`, `email`, `contact`, `session`, `address`, `department_id`, `password_hash`, `role`, `passport`, `level`, `nd_type`, `pair_id`, `supervisor`, `project_title`, `approved`, `created_at`) VALUES
(1, 'SW20240117501', 'Alabi Toheeb', 'alabi2916@gmail.com', '07056085255', '2024/2025', 'isale eko', 3, '$2y$10$vu7iYa6xBu3NAZT6z8MfqOs8In1nrdr/xy5zWaWGd3.OayZNB20Ru', 'student', 'pass_6a0d6df564657.jpg', 'HND', NULL, NULL, NULL, NULL, 1, '2026-04-15 22:41:55'),
(2, 'CS20240118501', 'owolabi blessing', 'felicitynatalie3@gmail.com', '08164673875', '2024/2025', 'agbale', 1, '$2y$10$EMFrtzqNoBJqtTElH1IKte0FT7AsQA6AuGVUcDDwfTrabmUTCqgJq', 'student', 'uploads/passports/pass_1776310113_56954.jpg', 'ND', 'FT', 4, NULL, NULL, 1, '2026-04-16 03:28:47'),
(3, 'CS20240118502', 'Olajide Fawwas', 'jiybi84@gmail.com', '08080472644', '2024/2025', 'agbale', 1, '$2y$10$EMFrtzqNoBJqtTElH1IKte0FT7AsQA6AuGVUcDDwfTrabmUTCqgJq', 'student', 'uploads/passports/pass_1776310113_39226.jpg', 'ND', 'FT', 4, NULL, NULL, 1, '2026-04-16 03:28:47'),
(4, 'NT20240117502', 'Akintunde Blessing', 'boluwatifhe698@gmail.com', '07080472644', '2024/2025', 'COUNTRY HOME', 2, '$2y$10$OtH9LvnrmLg3IKSx19meteX0ms8OovVWxpwr1Zbjnbkv/IJ4AexP2', 'student', 'uploads/passports/pass_1776347899_64392.jpg', 'HND', NULL, NULL, NULL, NULL, 1, '2026-04-16 13:58:36');

-- --------------------------------------------------------

--
-- Table structure for table `submission_settings`
--

DROP TABLE IF EXISTS `submission_settings`;
CREATE TABLE IF NOT EXISTS `submission_settings` (
  `id` int NOT NULL DEFAULT '1',
  `submission_start` date NOT NULL,
  `submission_end` date NOT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `submission_settings`
--

INSERT INTO `submission_settings` (`id`, `submission_start`, `submission_end`, `updated_at`) VALUES
(1, '2026-05-01', '2026-06-30', '2026-04-19 12:27:52');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `matric_no` varchar(20) DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `contact` varchar(50) DEFAULT NULL,
  `session` varchar(20) NOT NULL,
  `address` text,
  `department_id` int NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('student','dept_admin','grand_admin') DEFAULT 'student',
  `unique_code` varchar(50) DEFAULT NULL,
  `approved` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `matric_no` (`matric_no`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_matric_no` (`matric_no`),
  KEY `idx_email` (`email`),
  KEY `idx_department` (`department_id`),
  KEY `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

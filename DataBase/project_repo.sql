-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Apr 16, 2026 at 05:05 AM
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
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `full_name`, `email`, `password_hash`, `role`, `department_id`, `created_at`) VALUES
(1, 'Grand Admin', 'grandadmin@nacosfpe.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'grand_admin', NULL, '2026-04-15 23:45:31'),
(2, 'Mr Moses', 'aduragbemi784@gmail.com', '$2y$10$lh/4pAy/ZjKascd8DQ0EBuV9HmmgE0OB7PAOMZLxI0ewM8W1PmSQO', 'department_admin', 3, '2026-04-16 00:56:41'),
(3, 'MRr Rahmon', 'engrpossiblemajor25@gmail.com', '$2y$10$R7KRwSxnbyu3/isW3q.gS.cAunWbhBmgyg3fjhGoJm3u0CeezWdPe', 'department_admin', 1, '2026-04-16 03:24:45');

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
) ENGINE=MyISAM AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
(28, 1, '519702', '2026-04-16 03:32:45', 0, '2026-04-16 03:02:45');

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
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `nd_pairs`
--

INSERT INTO `nd_pairs` (`id`, `student1_id`, `student2_id`, `created_at`) VALUES
(1, 7, 8, '2026-04-15 18:44:34'),
(2, 9, 10, '2026-04-15 18:53:52'),
(3, 1, 2, '2026-04-15 20:33:44'),
(4, 2, 3, '2026-04-16 04:28:47');

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
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `pending_registrations`
--

INSERT INTO `pending_registrations` (`id`, `temp_id`, `level`, `nd_type`, `department_id`, `session`, `address`, `matric_no`, `name`, `email`, `contact`, `password_hash`, `passport`, `pair_data`, `amount`, `status`, `created_at`) VALUES
(1, 'HND_17762915362424', 'HND', NULL, 3, '2024/2025', 'isale eko', 'SW20240117501', 'Alabi Toheeb', 'admin2916@gmail.com', '07056085255', '$2y$10$a8..03tPdpOjh7QArzXd..tZvH/ZxS.9MnrhcWi9BNTglhK3DPJk6', 'uploads/passports/pass_1776291536_94100.jpg', NULL, '2000.00', 'paid', '2026-04-15 23:18:56'),
(2, 'HND_17762916261508', 'HND', NULL, 3, '2024/2025', 'isale eko', 'SW20240117501', 'Alabi Toheeb', 'alabi2916@gmail.com', '07056085255', '$2y$10$vu7iYa6xBu3NAZT6z8MfqOs8In1nrdr/xy5zWaWGd3.OayZNB20Ru', 'uploads/passports/pass_1776291626_74496.jpg', NULL, '2000.00', 'paid', '2026-04-15 23:20:26'),
(3, 'ND_17763101132030', 'ND', 'FT', 1, '2024/2025', 'agbale', 'CS20240118501', 'owolabi blessing', 'felicitynatalie3@gmail.com', '08164673875', '$2y$10$EMFrtzqNoBJqtTElH1IKte0FT7AsQA6AuGVUcDDwfTrabmUTCqgJq', 'uploads/passports/pass_1776310113_56954.jpg', '{\"name2\": \"Olajide Fawwas\", \"email2\": \"jiybi84@gmail.com\", \"contact2\": \"08080472644\", \"passport2\": \"uploads/passports/pass_1776310113_39226.jpg\", \"matric_no2\": \"CS20240118502\", \"password_hash2\": \"$2y$10$gHUfhqTHg5ZDxkyKIGwBZOrD6dbdp2zoNjnnFgPZUR4MEs2Rq6zTu\"}', '4000.00', 'paid', '2026-04-16 04:28:33');

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
  `status` enum('pending','approved') DEFAULT 'pending',
  `abstract` text,
  `supervisor` varchar(255) DEFAULT NULL,
  `uploaded_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `student_id`, `title`, `file_path`, `upload_date`, `status`, `abstract`, `supervisor`, `uploaded_at`) VALUES
(1, 6, 'design', 'uploads/projects/proj_69d9a2451e34f.docx', '2026-04-11 01:22:13', 'approved', NULL, NULL, '2026-04-16 04:18:11'),
(2, 15, 'Academy Research Based on Hardware Component Installation Related Issues', 'uploads/projects/proj_69dbbd9a25903.pdf', '2026-04-12 15:43:22', 'pending', NULL, NULL, '2026-04-16 04:18:11'),
(3, 16, 'Academy Research Based on Hardware Component Installation Related Issues', 'uploads/projects/proj_69dcc6afae698.pdf', '2026-04-13 10:34:23', 'pending', NULL, NULL, '2026-04-16 04:18:11'),
(4, 1, 'Design and implementation of job portal', 'uploads/projects/proj_69e04bbc1ee0f.pdf', '2026-04-16 02:41:14', 'approved', 'i love project', 'Mr Moses', '2026-04-16 04:18:11');

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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `matric_no`, `name`, `email`, `contact`, `session`, `address`, `department_id`, `password_hash`, `role`, `passport`, `level`, `nd_type`, `pair_id`, `supervisor`, `project_title`, `approved`, `created_at`) VALUES
(1, 'SW20240117501', 'Alabi Toheeb', 'alabi2916@gmail.com', '07056085255', '2024/2025', 'isale eko', 3, '$2y$10$vu7iYa6xBu3NAZT6z8MfqOs8In1nrdr/xy5zWaWGd3.OayZNB20Ru', 'student', 'pass_69e055ba816bf.jpg', 'HND', NULL, NULL, NULL, NULL, 1, '2026-04-15 22:41:55'),
(2, 'CS20240118501', 'owolabi blessing', 'felicitynatalie3@gmail.com', '08164673875', '2024/2025', 'agbale', 1, '$2y$10$EMFrtzqNoBJqtTElH1IKte0FT7AsQA6AuGVUcDDwfTrabmUTCqgJq', 'student', 'uploads/passports/pass_1776310113_56954.jpg', 'ND', 'FT', 4, NULL, NULL, 1, '2026-04-16 03:28:47'),
(3, 'CS20240118502', 'Olajide Fawwas', 'jiybi84@gmail.com', '08080472644', '2024/2025', 'agbale', 1, '$2y$10$EMFrtzqNoBJqtTElH1IKte0FT7AsQA6AuGVUcDDwfTrabmUTCqgJq', 'student', 'uploads/passports/pass_1776310113_39226.jpg', 'ND', 'FT', 4, NULL, NULL, 1, '2026-04-16 03:28:47');

-- --------------------------------------------------------

--
-- Table structure for table `students1`
--

DROP TABLE IF EXISTS `students1`;
CREATE TABLE IF NOT EXISTS `students1` (
  `id` int NOT NULL AUTO_INCREMENT,
  `matric_no` varchar(20) DEFAULT NULL,
  `matric_no1` varchar(150) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `name1` varchar(150) DEFAULT NULL,
  `passport` varchar(255) DEFAULT NULL,
  `supervisor` varchar(100) DEFAULT NULL,
  `contact` varchar(100) DEFAULT NULL,
  `session` varchar(20) NOT NULL,
  `address` text,
  `project_title` varchar(255) DEFAULT NULL,
  `department_id` int NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('student','dept_admin','grand_admin') DEFAULT 'student',
  `unique_code` varchar(50) DEFAULT NULL,
  `approved` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `level` enum('ND','HND') NOT NULL DEFAULT 'HND',
  `nd_type` enum('FT','DPT') DEFAULT NULL,
  `pair_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `matric_no` (`matric_no`),
  UNIQUE KEY `email` (`email`),
  KEY `department_id` (`department_id`),
  KEY `idx_matric` (`id`),
  KEY `idx_matric_no` (`matric_no`),
  KEY `idx_email` (`email`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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

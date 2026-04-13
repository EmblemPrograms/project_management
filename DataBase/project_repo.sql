-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Apr 13, 2026 at 05:59 AM
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
(3, 'Cloud Computing and Networking'),
(4, 'Mass Communication'),
(5, 'Science Labouratory and Technology');

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
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `email_verification`
--

INSERT INTO `email_verification` (`id`, `user_id`, `otp`, `expires_at`, `used`) VALUES
(1, 11, '307532', '2026-04-11 17:50:18', 0),
(2, 12, '581815', '2026-04-11 17:56:18', 0),
(3, 13, '095807', '2026-04-11 18:13:00', 0),
(4, 14, '256025', '2026-04-12 06:10:18', 0),
(5, 15, '876401', '2026-04-12 08:49:42', 1),
(6, 15, '974868', '2026-04-12 17:09:08', 0),
(7, 15, '145044', '2026-04-12 17:09:56', 1),
(8, 15, '896069', '2026-04-12 17:12:17', 1);

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
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `student_id`, `title`, `file_path`, `upload_date`, `status`) VALUES
(1, 6, 'design', 'uploads/projects/proj_69d9a2451e34f.docx', '2026-04-11 01:22:13', 'approved'),
(2, 15, 'Academy Research Based on Hardware Component Installation Related Issues', 'uploads/projects/proj_69dbbd9a25903.pdf', '2026-04-12 15:43:22', 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `matric_no` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `name` varchar(150) NOT NULL,
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
  PRIMARY KEY (`id`),
  UNIQUE KEY `matric_no` (`matric_no`),
  UNIQUE KEY `email` (`email`),
  KEY `department_id` (`department_id`),
  KEY `idx_matric` (`id`),
  KEY `idx_matric_no` (`matric_no`),
  KEY `idx_email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `matric_no`, `email`, `name`, `passport`, `supervisor`, `contact`, `session`, `address`, `project_title`, `department_id`, `password_hash`, `role`, `unique_code`, `approved`, `created_at`) VALUES
(5, NULL, NULL, 'Grand Admin', NULL, NULL, NULL, '2024/2025', NULL, NULL, 1, '$2y$10$hISu4lQ02Yv.M99pFm7LE.Ti76flKpWSj5dVYslTnsLfSGQMiMms6', 'grand_admin', 'GRAND1234', 1, '2026-04-11 00:30:25'),
(7, NULL, NULL, 'MR MOSES', NULL, NULL, '2024/2025', '2024/2025', NULL, NULL, 1, '$2y$10$hISu4lQ02Yv.M99pFm7LE.Ti76flKpWSj5dVYslTnsLfSGQMiMms6', 'dept_admin', 'MNTJ2DXX', 1, '2026-04-11 00:57:43'),
(15, 'SW20240113314', 'lekancent@gmail.com', 'Fawwas Ayomide Olajide', 'uploads/passports/pass_69dbbd5991087.jpg', 'Mr Adegoke Moses', '07089410451', '2024/2025', 'PLOT 13 BLOCK C IBUKUN OLU LAYOUT ANIFALAJE AKOBO, IBADAN, ., Nigeria', 'Academy Research Based on Hardware Component Installation Related Issues', 2, '$2y$10$OzQIqdGVmLco.3B.8iX0F.lbARp10MKN0tVVVqMmmGDcn5MPIlzxe', 'student', NULL, 1, '2026-04-12 05:49:42');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: May 23, 2025 at 09:04 PM
-- Server version: 9.1.0
-- PHP Version: 8.1.31

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `project`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
CREATE TABLE IF NOT EXISTS `admins` (
  `admin_id` int NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`admin_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `name`) VALUES
(1, 'Ahmed');

-- --------------------------------------------------------

--
-- Table structure for table `courseenrollments`
--

DROP TABLE IF EXISTS `courseenrollments`;
CREATE TABLE IF NOT EXISTS `courseenrollments` (
  `enrollment_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `course_id` int NOT NULL,
  `enrollment_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`enrollment_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `courseenrollments`
--

INSERT INTO `courseenrollments` (`enrollment_id`, `user_id`, `course_id`, `enrollment_date`) VALUES
(1, 1, 2, '2025-04-29 02:27:21'),
(2, 2, 2, '2025-04-29 02:27:21'),
(3, 3, 3, '2025-04-29 02:27:21'),
(4, 12, 6, '2025-04-29 21:18:54'),
(5, 5, 6, '2025-04-29 21:18:54'),
(6, 7, 6, '2025-04-29 21:18:54'),
(7, 12, 1, '2025-04-29 21:19:24'),
(8, 5, 1, '2025-04-29 21:19:24'),
(9, 7, 1, '2025-04-29 21:19:24');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

DROP TABLE IF EXISTS `courses`;
CREATE TABLE IF NOT EXISTS `courses` (
  `course_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `department_id` int DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`course_id`),
  KEY `department_id` (`department_id`)
) ENGINE=MyISAM AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`course_id`, `name`, `department_id`, `description`) VALUES
(1, 'Advanced Programming', 1, NULL),
(2, 'Database Systems', 1, NULL),
(3, 'Information Systems Management', 1, NULL),
(4, 'Network Security', 2, NULL),
(5, 'Cloud Computing', 2, NULL),
(6, 'System Analysis and Design', 2, NULL),
(13, 'database 1', 2, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `csrf_tokens`
--

DROP TABLE IF EXISTS `csrf_tokens`;
CREATE TABLE IF NOT EXISTS `csrf_tokens` (
  `id` int NOT NULL AUTO_INCREMENT,
  `token` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `user_id` (`user_id`),
  KEY `expires_at` (`expires_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
CREATE TABLE IF NOT EXISTS `departments` (
  `department_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`department_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`department_id`, `name`) VALUES
(1, 'BIS'),
(2, 'TIS');

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

DROP TABLE IF EXISTS `enrollments`;
CREATE TABLE IF NOT EXISTS `enrollments` (
  `enrollment_id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `course_id` int NOT NULL,
  `semester` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `year` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`enrollment_id`),
  KEY `student_id` (`student_id`),
  KEY `course_id` (`course_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `library_books`
--

DROP TABLE IF EXISTS `library_books`;
CREATE TABLE IF NOT EXISTS `library_books` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `course_id` int NOT NULL,
  `uploaded_by` int NOT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `upload_date` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `course_id` (`course_id`),
  KEY `uploaded_by` (`uploaded_by`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `library_books`
--

INSERT INTO `library_books` (`id`, `title`, `course_id`, `uploaded_by`, `file_path`, `description`, `upload_date`) VALUES
(1, 'database 1', 13, 2, 'uploads/books/681aa2c706729.pdf', 'data', '2025-05-07 03:01:11'),
(2, 'test', 6, 2, 'uploads/books/681c86ef1452a.pdf', 'test 064', '2025-05-08 13:26:55');

-- --------------------------------------------------------

--
-- Table structure for table `library_exams`
--

DROP TABLE IF EXISTS `library_exams`;
CREATE TABLE IF NOT EXISTS `library_exams` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `course_id` int NOT NULL,
  `exam_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `exam_year` int NOT NULL,
  `uploaded_by` int NOT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `upload_date` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `course_id` (`course_id`),
  KEY `uploaded_by` (`uploaded_by`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `library_exams`
--

INSERT INTO `library_exams` (`id`, `title`, `course_id`, `exam_type`, `exam_year`, `uploaded_by`, `file_path`, `upload_date`) VALUES
(1, 'test ll', 13, 'Midterm', 2025, 2, 'uploads/exams/681ca05d6b1af.pdf', '2025-05-08 15:15:25');

-- --------------------------------------------------------

--
-- Table structure for table `meetings`
--

DROP TABLE IF EXISTS `meetings`;
CREATE TABLE IF NOT EXISTS `meetings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `meeting_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `course_id` int NOT NULL,
  `professor_id` int NOT NULL,
  `meeting_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  `last_updated` datetime NOT NULL,
  `recording_url` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `welcome_message` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `meeting_id` (`meeting_id`),
  KEY `meeting_id_2` (`meeting_id`),
  KEY `course_id` (`course_id`),
  KEY `professor_id` (`professor_id`)
) ENGINE=MyISAM AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `meetings`
--

INSERT INTO `meetings` (`id`, `meeting_id`, `course_id`, `professor_id`, `meeting_name`, `created_at`, `last_updated`, `recording_url`, `welcome_message`) VALUES
(1, 'course-13-2-1746248071', 13, 2, 'database 1 Meeting', '2025-05-03 07:54:32', '2025-05-05 16:02:06', NULL, NULL),
(5, 'course-6-2-1746321917', 6, 2, 'System Analysis and Design Meeting', '2025-05-04 04:25:18', '2025-05-04 04:25:18', NULL, NULL),
(43, 'course-13-2-1746523387', 13, 2, 'meet1', '2025-05-06 12:23:08', '2025-05-06 13:21:32', NULL, NULL),
(44, 'course-13-2-1746527523', 13, 2, 'test', '2025-05-06 13:32:05', '2025-05-06 13:33:09', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `meeting_analytics`
--

DROP TABLE IF EXISTS `meeting_analytics`;
CREATE TABLE IF NOT EXISTS `meeting_analytics` (
  `id` int NOT NULL AUTO_INCREMENT,
  `meeting_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `course_id` int NOT NULL,
  `professor_id` int NOT NULL,
  `total_attendees` int NOT NULL DEFAULT '0',
  `avg_duration` int DEFAULT NULL,
  `peak_attendance` int DEFAULT NULL,
  `processed_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `meeting_id` (`meeting_id`),
  KEY `course_id` (`course_id`),
  KEY `professor_id` (`professor_id`),
  KEY `processed_at` (`processed_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `meeting_attendees`
--

DROP TABLE IF EXISTS `meeting_attendees`;
CREATE TABLE IF NOT EXISTS `meeting_attendees` (
  `id` int NOT NULL AUTO_INCREMENT,
  `meeting_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int NOT NULL,
  `join_time` datetime NOT NULL,
  `leave_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `meeting_id` (`meeting_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=73 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `meeting_attendees`
--

INSERT INTO `meeting_attendees` (`id`, `meeting_id`, `user_id`, `join_time`, `leave_time`) VALUES
(1, 'course-13-2-1746248071', 2, '2025-05-03 07:56:04', NULL),
(2, 'course-13-2-1746248071', 2, '2025-05-03 07:58:02', NULL),
(3, 'course-13-2-1746248071', 2, '2025-05-03 08:08:32', NULL),
(4, 'course-13-2-1746248071', 12, '2025-05-03 08:09:00', NULL),
(5, 'course-13-2-1746253204', 2, '2025-05-03 09:20:56', NULL),
(6, 'course-13-2-1746253204', 12, '2025-05-03 09:21:27', NULL),
(7, 'course-13-2-1746253204', 2, '2025-05-03 09:22:14', NULL),
(8, 'course-13-2-1746248071', 2, '2025-05-03 09:25:05', NULL),
(9, 'course-13-2-1746248071', 2, '2025-05-03 16:40:39', NULL),
(10, 'course-13-2-1746248071', 2, '2025-05-03 16:50:53', NULL),
(11, 'course-13-2-1746248071', 2, '2025-05-03 16:55:21', NULL),
(12, 'course-13-2-1746248071', 12, '2025-05-03 16:55:42', NULL),
(13, 'course-13-2-1746248071', 2, '2025-05-03 17:30:12', NULL),
(14, 'course-13-2-1746248071', 2, '2025-05-03 17:36:16', NULL),
(15, 'course-13-2-1746248071', 12, '2025-05-03 17:37:03', NULL),
(16, 'course-13-2-1746248071', 12, '2025-05-03 17:52:25', NULL),
(17, 'course-13-2-1746248071', 12, '2025-05-03 17:52:37', NULL),
(18, 'course-13-2-1746248071', 12, '2025-05-03 17:54:25', NULL),
(19, 'course-13-2-1746248071', 12, '2025-05-03 18:00:29', NULL),
(20, 'course-13-2-1746248071', 12, '2025-05-03 18:29:36', NULL),
(21, 'course-13-2-1746248071', 12, '2025-05-03 18:29:50', NULL),
(22, 'course-13-2-1746248071', 2, '2025-05-03 18:45:25', NULL),
(23, 'course-13-2-1746248071', 2, '2025-05-04 02:38:50', NULL),
(24, 'course-13-2-1746248071', 2, '2025-05-04 02:38:59', NULL),
(25, 'course-13-2-1746248071', 2, '2025-05-04 02:39:09', NULL),
(26, 'course-13-2-1746248071', 12, '2025-05-04 02:58:46', NULL),
(27, 'course-13-2-1746248071', 12, '2025-05-04 02:58:53', NULL),
(28, 'course-13-2-1746248071', 12, '2025-05-04 02:59:05', NULL),
(29, 'course-13-2-1746248071', 12, '2025-05-04 02:59:19', NULL),
(30, 'course-13-2-1746248071', 12, '2025-05-04 02:59:24', NULL),
(31, 'course-13-2-1746248071', 2, '2025-05-04 03:07:39', NULL),
(32, 'course-13-2-1746248071', 2, '2025-05-04 03:09:44', NULL),
(33, 'course-13-2-1746248071', 2, '2025-05-04 03:11:05', NULL),
(34, 'course-13-2-1746248071', 2, '2025-05-04 03:15:06', NULL),
(35, 'course-13-2-1746248071', 2, '2025-05-04 03:15:13', NULL),
(36, 'course-13-2-1746248071', 2, '2025-05-04 03:40:58', NULL),
(37, 'course-13-2-1746248071', 2, '2025-05-04 03:43:59', NULL),
(38, 'course-13-2-1746248071', 2, '2025-05-04 03:46:52', NULL),
(39, 'course-13-2-1746248071', 2, '2025-05-04 03:47:15', NULL),
(40, 'course-13-2-1746248071', 2, '2025-05-04 04:17:18', NULL),
(41, 'course-13-2-1746248071', 2, '2025-05-04 04:17:33', NULL),
(42, 'course-13-2-1746253461', 2, '2025-05-04 04:19:36', NULL),
(43, 'course-13-2-1746253461', 2, '2025-05-04 04:19:54', NULL),
(44, 'course-13-2-1746253461', 2, '2025-05-04 04:20:14', NULL),
(45, 'course-13-2-1746253461', 2, '2025-05-04 04:20:20', NULL),
(46, 'course-13-2-1746253461', 2, '2025-05-04 04:20:26', NULL),
(47, 'course-13-2-1746253461', 2, '2025-05-04 04:20:33', NULL),
(48, 'course-13-2-1746253461', 2, '2025-05-04 04:21:32', NULL),
(49, 'course-13-2-1746253461', 2, '2025-05-04 04:22:38', NULL),
(50, 'course-13-2-1746253461', 2, '2025-05-04 04:23:33', NULL),
(51, 'course-13-2-1746253461', 2, '2025-05-04 04:24:05', NULL),
(52, 'course-13-2-1746253461', 2, '2025-05-04 04:24:21', NULL),
(53, 'course-13-2-1746323686', 2, '2025-05-04 04:54:58', NULL),
(54, 'course-13-2-1746329880', 2, '2025-05-04 06:39:48', NULL),
(55, 'course-13-2-1746329880', 12, '2025-05-04 06:40:28', NULL),
(56, 'course-13-2-1746369542', 2, '2025-05-04 17:39:55', NULL),
(57, 'course-13-2-1746369542', 2, '2025-05-04 17:40:06', NULL),
(58, 'course-13-2-1746370851', 12, '2025-05-04 18:01:03', NULL),
(59, 'course-13-2-1746378140', 12, '2025-05-04 20:02:28', NULL),
(60, 'course-13-2-1746248071', 2, '2025-05-04 23:52:59', NULL),
(61, 'course-13-2-1746248071', 12, '2025-05-04 23:54:10', NULL),
(62, 'course-13-2-1746397560', 12, '2025-05-05 01:26:06', NULL),
(63, 'course-13-2-1746248071', 2, '2025-05-05 16:02:06', NULL),
(64, 'course-13-2-1746248071', 12, '2025-05-05 16:03:09', NULL),
(65, 'course-13-2-1746450408', 12, '2025-05-05 16:07:20', NULL),
(66, 'course-13-2-1746450408', 2, '2025-05-05 16:08:00', NULL),
(67, 'course-13-2-1746523387', 2, '2025-05-06 12:23:16', NULL),
(68, 'course-13-2-1746523387', 12, '2025-05-06 12:23:50', NULL),
(69, 'course-13-2-1746523387', 2, '2025-05-06 13:21:32', NULL),
(70, 'course-13-2-1746523387', 12, '2025-05-06 13:24:25', NULL),
(71, 'course-13-2-1746527523', 12, '2025-05-06 13:33:09', NULL),
(72, 'course-13-2-1746527523', 2, '2025-05-06 13:33:50', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `meeting_notifications`
--

DROP TABLE IF EXISTS `meeting_notifications`;
CREATE TABLE IF NOT EXISTS `meeting_notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `meeting_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `scheduled_meeting_id` int DEFAULT NULL,
  `user_id` int NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `meeting_id` (`meeting_id`),
  KEY `scheduled_meeting_id` (`scheduled_meeting_id`),
  KEY `user_id` (`user_id`),
  KEY `is_read` (`is_read`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `meeting_recordings`
--

DROP TABLE IF EXISTS `meeting_recordings`;
CREATE TABLE IF NOT EXISTS `meeting_recordings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `meeting_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `record_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `recording_url` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  `duration` int NOT NULL,
  `file_size` bigint NOT NULL,
  PRIMARY KEY (`id`),
  KEY `meeting_id` (`meeting_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notificationrecipients`
--

DROP TABLE IF EXISTS `notificationrecipients`;
CREATE TABLE IF NOT EXISTS `notificationrecipients` (
  `id` int NOT NULL AUTO_INCREMENT,
  `notification_id` int NOT NULL,
  `user_id` int NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `read_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notification_id` (`notification_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=88 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notificationrecipients`
--

INSERT INTO `notificationrecipients` (`id`, `notification_id`, `user_id`, `is_read`, `read_at`) VALUES
(1, 2, 12, 1, '2025-04-29 02:28:43'),
(2, 2, 5, 0, NULL),
(3, 2, 7, 1, '2025-04-29 04:02:04'),
(4, 3, 12, 1, '2025-04-29 02:29:56'),
(5, 3, 5, 0, NULL),
(6, 3, 7, 1, '2025-04-29 04:02:04'),
(7, 4, 12, 1, '2025-04-29 03:05:17'),
(8, 4, 5, 0, NULL),
(9, 4, 7, 1, '2025-04-29 04:02:04'),
(10, 5, 12, 1, '2025-04-29 08:04:06'),
(11, 5, 5, 0, NULL),
(12, 5, 7, 1, '2025-04-29 04:02:04'),
(13, 6, 12, 1, '2025-04-29 08:04:06'),
(14, 6, 5, 0, NULL),
(15, 6, 7, 1, '2025-04-29 04:54:20'),
(16, 7, 12, 1, '2025-04-29 08:04:06'),
(17, 7, 5, 0, NULL),
(18, 7, 7, 1, '2025-04-29 04:54:20'),
(19, 8, 12, 1, '2025-04-29 08:04:06'),
(20, 8, 5, 0, NULL),
(21, 8, 7, 1, '2025-04-29 04:54:20'),
(22, 9, 12, 1, '2025-04-29 08:04:06'),
(23, 9, 5, 0, NULL),
(24, 9, 7, 1, '2025-04-29 05:02:00'),
(25, 10, 12, 1, '2025-04-29 08:04:06'),
(26, 10, 5, 0, NULL),
(27, 10, 7, 1, '2025-04-29 05:02:00'),
(28, 11, 12, 1, '2025-04-29 08:04:06'),
(29, 11, 5, 0, NULL),
(30, 11, 7, 1, '2025-04-29 05:02:00'),
(31, 12, 12, 1, '2025-04-29 08:04:06'),
(32, 12, 5, 0, NULL),
(33, 12, 7, 1, '2025-04-29 05:02:00'),
(34, 13, 12, 1, '2025-04-29 08:04:06'),
(35, 13, 5, 0, NULL),
(36, 13, 7, 1, '2025-04-29 05:06:24'),
(37, 14, 12, 1, '2025-04-29 08:04:06'),
(38, 14, 5, 0, NULL),
(39, 14, 7, 1, '2025-04-29 05:06:24'),
(40, 15, 12, 1, '2025-04-29 22:05:08'),
(41, 15, 5, 0, NULL),
(42, 15, 7, 1, '2025-04-29 05:08:36'),
(43, 16, 12, 1, '2025-04-29 08:04:06'),
(44, 16, 5, 0, NULL),
(45, 16, 7, 1, '2025-04-29 05:17:07'),
(46, 17, 12, 1, '2025-04-29 08:04:06'),
(47, 17, 5, 0, NULL),
(48, 17, 7, 1, '2025-04-29 05:17:07'),
(49, 18, 12, 1, '2025-04-29 08:04:06'),
(50, 18, 5, 0, NULL),
(51, 18, 7, 1, '2025-04-29 05:17:07'),
(52, 19, 12, 1, '2025-04-29 21:15:28'),
(53, 19, 5, 0, NULL),
(54, 19, 7, 0, NULL),
(55, 26, 12, 1, '2025-05-05 13:23:51'),
(56, 26, 5, 0, NULL),
(57, 26, 7, 0, NULL),
(58, 27, 12, 1, '2025-05-05 13:23:51'),
(59, 27, 5, 0, NULL),
(60, 27, 7, 0, NULL),
(61, 28, 12, 1, '2025-05-05 13:23:51'),
(62, 28, 5, 0, NULL),
(63, 28, 7, 0, NULL),
(64, 29, 12, 1, '2025-05-05 13:23:51'),
(65, 29, 5, 0, NULL),
(66, 29, 7, 0, NULL),
(67, 30, 12, 1, '2025-05-05 13:23:51'),
(68, 30, 5, 0, NULL),
(69, 30, 7, 0, NULL),
(70, 31, 12, 1, '2025-05-05 13:23:51'),
(71, 31, 5, 0, NULL),
(72, 31, 7, 0, NULL),
(73, 32, 12, 1, '2025-05-05 13:23:51'),
(74, 32, 5, 0, NULL),
(75, 32, 7, 0, NULL),
(76, 33, 12, 1, '2025-05-05 13:23:51'),
(77, 33, 5, 0, NULL),
(78, 33, 7, 0, NULL),
(79, 34, 12, 1, '2025-05-05 13:23:51'),
(80, 34, 5, 0, NULL),
(81, 34, 7, 0, NULL),
(82, 35, 12, 1, '2025-05-05 13:23:51'),
(83, 35, 5, 0, NULL),
(84, 35, 7, 0, NULL),
(85, 36, 12, 1, '2025-05-06 09:11:13'),
(86, 36, 5, 0, NULL),
(87, 36, 7, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `notification_id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `sender_id` int DEFAULT NULL,
  `target_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'all, course, specific',
  `target_id` int DEFAULT NULL COMMENT 'course_id or NULL if for all',
  PRIMARY KEY (`notification_id`),
  KEY `sender_id` (`sender_id`)
) ENGINE=MyISAM AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `title`, `message`, `created_at`, `sender_id`, `target_type`, `target_id`) VALUES
(13, 'ءء', '# Profile Page Enhancement Plan\r\n\r\nI\'ll enhance the visual appearance of the profile page while maintaining its overall structure. Here\'s how I\'ll improve it:\r\n\r\n1. Add a more professional card design for the profile\r\n2. Improve spacing and visual hierarchy \r\n3. Enhance profile image presentation\r\n4. Add subtle animations and hover effects\r\n5. Improve responsive design\r\n\r\n## style.css\r\n\r\nI\'ll update the CSS to create a more professional and polished appearance.\r\n\r\n````css\r\n* {\r\n  padding: 0;\r\n  margin: 0;\r\n  box-sizing: border-box;\r\n}\r\n:root {\r\n  --text-color: #fff;\r\n  --primary-color: #103054;\r\n  --secondary-color: #f5c254;\r\n  --background-lightgray: #f4f4f4;\r\n  --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);\r\n  --card-border-radius: 15px;\r\n  --transition-speed: 0.3s;\r\n}\r\n\r\nbody {\r\n  font-family: Roboto Condensed, sans-serif;\r\n  background-color: #f9f9f9;\r\n  line-height: 1.6;\r\n}\r\n\r\n// ...existing code...\r\n\r\nh1 {\r\n  position: relative;\r\n  z-index: 1;\r\n  color: var(--text-color);\r\n  font-size: 10vw;\r\n  text-align: center;\r\n  top: 90px;\r\n  text-transform: capitalize;\r\n  letter-spacing: -4px;\r\n  text-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);\r\n}\r\n\r\n// ...existing code...\r\n\r\n.navbar {\r\n  background-color: var(--primary-color);\r\n  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);\r\n  position: relative;\r\n  z-index: 1000;\r\n}\r\n\r\n.navbar_container,\r\n.profile_container,\r\n.profile_settings_container,\r\n.footer_container {\r\n  width: 90%;\r\n  max-width: 1300px;\r\n  margin: auto;\r\n}\r\n\r\nnav {\r\n  display: flex;\r\n  justify-content: space-around;\r\n  padding: 15px 10px;\r\n  color: var(--text-color);\r\n}\r\n\r\n// ...existing code...\r\n\r\nheader {\r\n  background-color: var(--primary-color);\r\n  position: relative;\r\n  box-shadow: 0 4px 20px rgba(7, 22, 38, 0.2);\r\n  height: calc(70vh + (160 * ((100vw - 768px) / 1024)));\r\n  overflow: hidden;\r\n}\r\n\r\nheader::after {\r\n  content: \"\";\r\n  height: inherit;\r\n  width: 100%;\r\n  background: linear-gradient(to bottom, rgba(0, 0, 0, 0.8), rgba(0, 0, 0, 0));\r\n  top: 0;\r\n  left: 0;\r\n  position: absolute;\r\n}\r\n\r\n// ...existing code...\r\n\r\n.profile_user_image {\r\n  border-radius: 50%;\r\n  padding: 5px;\r\n  width: 25%;\r\n  background-color: #fff;\r\n  position: absolute;\r\n  z-index: 5;\r\n  bottom: 0;\r\n  left: 50%;\r\n  transform: translate(-50%, 42%);\r\n  box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);\r\n  border: 4px solid var(--secondary-color);\r\n  transition: transform var(--transition-speed);\r\n}\r\n\r\n.profile_user_image:hover {\r\n  transform: translate(-50%, 42%) scale(1.05);\r\n}\r\n\r\n.profile_title {\r\n  background-color: #fff;\r\n  box-shadow: var(--box-shadow);\r\n  margin-top: 40px;\r\n  border-radius: var(--card-border-radius);\r\n  display: flex;\r\n  flex-direction: column;\r\n  justify-content: flex-end;\r\n  align-items: center;\r\n  padding: 65px 25px 35px;\r\n  font-weight: 300;\r\n  text-transform: capitalize;\r\n  height: calc(50vh + (140 * ((100vw - 992px) / 1024)));\r\n  transition: transform var(--transition-speed);\r\n}\r\n\r\n.profile_title:hover {\r\n  transform: translateY(-5px);\r\n}\r\n\r\n.profile_title p {\r\n  color: #595959;\r\n  margin: 5px 0;\r\n}\r\n\r\n.profile_title p:first-of-type {\r\n  font-size: 28px;\r\n  font-weight: 500;\r\n}\r\n\r\n.profile_title p:last-of-type {\r\n  font-size: 36px;\r\n  color: var(--secondary-color);\r\n  font-weight: 400;\r\n}\r\n\r\n// ...existing code...\r\n\r\n/* User profile information styling */\r\n.user-info-card {\r\n  background-color: #fff;\r\n  border-radius: var(--card-border-radius);\r\n  box-shadow: var(--box-shadow);\r\n  padding: 25px;\r\n  margin-bottom: 25px;\r\n  transition: transform var(--transition-speed);\r\n}\r\n\r\n.user-info-card:hover {\r\n  transform: translateY(-5px);\r\n}\r\n\r\n.user-info-header {\r\n  display: flex;\r\n  align-items: center;\r\n  margin-bottom: 15px;\r\n  border-bottom: 1px solid #eee;\r\n  padding-bottom: 15px;\r\n}\r\n\r\n.user-info-header i {\r\n  font-size: 28px;\r\n  color: var(--secondary-color);\r\n  margin-right: 15px;\r\n}\r\n\r\n.user-info-header h3 {\r\n  font-size: 22px;\r\n  margin: 0;\r\n  font-weight: 600;\r\n  color: var(--primary-color);\r\n}\r\n\r\n// ...existing code...\r\n\r\n/* Courses section styling */\r\n.courses-section {\r\n  background-color: #fff;\r\n  border-radius: var(--card-border-radius);\r\n  box-shadow: var(--box-shadow);\r\n  padding: 25px;\r\n  margin-top: 25px;\r\n  transition: all var(--transition-speed);\r\n  width: 90%;\r\n  max-width: 600px;\r\n}\r\n\r\n.courses-section:hover {\r\n  transform: translateY(-5px);\r\n  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);\r\n}\r\n\r\n.courses-header {\r\n  display: flex;\r\n  align-items: center;\r\n  margin-bottom: 20px;\r\n  border-bottom: 1px solid #eee;\r\n  padding-bottom: 15px;\r\n}\r\n\r\n.courses-header i {\r\n  font-size: 24px;\r\n  color: var(--secondary-color);\r\n  margin-right: 15px;\r\n}\r\n\r\n.courses-header h4 {\r\n  font-size: 20px;\r\n  margin: 0;\r\n  font-weight: 600;\r\n  color: var(--primary-color);\r\n}\r\n\r\n.courses-list {\r\n  list-style-type: none;\r\n  padding: 0;\r\n  margin: 0;\r\n}\r\n\r\n.courses-list li {\r\n  display: flex;\r\n  align-items: center;\r\n  padding: 12px 15px;\r\n  border-bottom: 1px solid #f5f5f5;\r\n  transition: background-color var(--transition-speed);\r\n}\r\n\r\n.courses-list li:hover {\r\n  background-color: #f9f9f9;\r\n  padding-left: 20px;\r\n}\r\n\r\n.courses-list li:last-child {\r\n  border-bottom: none;\r\n}\r\n\r\n.courses-list li i {\r\n  color: var(--secondary-color);\r\n  margin-right: 15px;\r\n  font-size: 16px;\r\n}\r\n\r\n.courses-list li:before {\r\n  content: \"•\";\r\n  color: var(--secondary-color);\r\n  font-weight: bold;\r\n  display: inline-block;\r\n  width: 1em;\r\n  margin-right: 10px;\r\n}\r\n\r\n/* Alert styling */\r\n.alert {\r\n  padding: 15px 20px;\r\n  border-radius: 8px;\r\n  margin: 20px 0;\r\n  display: flex;\r\n  align-items: center;\r\n  width: 90%;\r\n  max-width: 600px;\r\n  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);\r\n}\r\n\r\n.alert i {\r\n  font-size: 20px;\r\n  margin-right: 15px;\r\n}\r\n\r\n.alert-success {\r\n  background-color: #e7f7ed;\r\n  color: #1d8a43;\r\n  border-left: 4px solid #1d8a43;\r\n}\r\n\r\n.alert-danger {\r\n  background-color: #fce8e8;\r\n  color: #d83030;\r\n  border-left: 4px solid #d83030;\r\n}\r\n\r\n// ...existing code...\r\n\r\n.profile_user {\r\n  position: relative;\r\n  display: inline-block;\r\n}\r\n\r\n.edit-profile-icon {\r\n  position: absolute;\r\n  bottom: -10px;\r\n  right: 0;\r\n  left: 0;\r\n  margin: auto;\r\n  width: 36px;\r\n  height: 36px;\r\n  background-color: var(--secondary-color);\r\n  border-radius: 50%;\r\n  display: flex;\r\n  justify-content: center;\r\n  align-items: center;\r\n  cursor: pointer;\r\n  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);\r\n  transition: all 0.3s ease;\r\n  z-index: 10;\r\n}\r\n\r\n.edit-profile-icon i {\r\n  color: white;\r\n  font-size: 18px;\r\n}\r\n\r\n.edit-profile-icon:hover {\r\n  background-color: #e8b543;\r\n  transform: scale(1.15) rotate(5deg);\r\n  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);\r\n}\r\n\r\n.profile_settings {\r\n  margin: 4% 0;\r\n}\r\n\r\n.profile_settings_edit,\r\n.profile_settings_logout,\r\n.profile_settings_edit_image {\r\n  display: flex;\r\n  align-items: center;\r\n  transition: all 0.5s ease;\r\n  width: fit-content;\r\n  margin-bottom: 15px;\r\n  padding: 10px 15px;\r\n  border-radius: 8px;\r\n}\r\n\r\n.profile_settings_logout:hover, \r\n.profile_settings_edit:hover,\r\n.profile_settings_edit_image:hover {\r\n  cursor: pointer;\r\n  letter-spacing: 1px;\r\n  background-color: rgba(245, 194, 84, 0.1);\r\n  transform: translateX(5px);\r\n}\r\n\r\n.profile_settings_edit_image {\r\n  margin-top: 15px;\r\n  padding-top: 15px;\r\n  border-top: 1px solid rgba(156, 156, 156, 0.2);\r\n}\r\n\r\n.profile_settings_edit_image a {\r\n  display: flex;\r\n  align-items: center;\r\n  text-decoration: none;\r\n  color: #9C9C9C;\r\n  transition: color 0.3s ease;\r\n}\r\n\r\n.profile_settings_edit_image a:hover {\r\n  color: var(--secondary-color);\r\n}\r\n\r\n.profile_settings_edit_image i {\r\n  font-size: 22px;\r\n  margin-right: 12px;\r\n  color: var(--secondary-color);\r\n}\r\n\r\n.profile_settings_edit_image p {\r\n  margin: 0;\r\n  font-size: 15px;\r\n}\r\n\r\n.profile_settings p {\r\n  margin-left: 10px;\r\n  text-transform: capitalize;\r\n  color: #949494;\r\n}\r\n\r\n.footer {\r\n  background-color: var(--primary-color);\r\n  padding: 30px 0;\r\n  margin-top: 40px;\r\n}\r\n\r\n.website {\r\n  text-decoration: line-through;\r\n  text-transform: uppercase;\r\n  font-size: 20px;\r\n  font-weight: 900;\r\n  color: #7B90A7;\r\n  transition: color var(--transition-speed);\r\n}\r\n\r\n.website:hover {\r\n  color: var(--secondary-color);\r\n}\r\n````\r\n\r\n## profile.php\r\n\r\nNow I\'ll update the HTML structure in the PHP file to improve the organization and add some icons for better visual appearance.\r\n\r\n````php\r\n\r\n\r\nMade changes.\r\n\r\n<?php\r\n// Include authentication and profile functions\r\nrequire_once \'includes/auth.php\';\r\nrequire_once \'includes/profile_functions.php\';\r\n\r\n// ...existing code...\r\n?>\r\n<!DOCTYPE html>\r\n<html lang=\"en\">\r\n  <head>\r\n    <meta charset=\"UTF-8\" />\r\n    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\" />\r\n    <link\r\n      href=\"https://fonts.googleapis.com/css2?family=Roboto+Condensed:wght@100..700&display=swap\"\r\n      rel=\"stylesheet\"\r\n    />\r\n    <link href=\'https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css\' rel=\'stylesheet\'>\r\n    <link rel=\"stylesheet\" href=\"style.css\" />\r\n    <title>Profile - UniHive</title>\r\n    <link rel=\"icon\" type=\"image/png\" href=\"images/logo_blue.png\">\r\n  </head>\r\n  <body>\r\n    <section class=\"navbar\">\r\n      <div class=\"navbar_container\">\r\n        <nav>\r\n          <div class=\"logo\">\r\n            <div class=\"logo_image\">\r\n              <img src=\"images/logo.png\" alt=\"UniHive Logo\" />\r\n            </div>\r\n          <div class=\"div\">\r\n            <button id=\"menu-toggle\">\r\n              <i class=\'bx bx-menu\'></i>\r\n          </button>\r\n          <div id=\"mean\" class=\"mean\" >\r\n              <ul>\r\n              <li><a href=\"#\" class=\"active\"> <i class=\'bx bxs-dashboard\'></i> <p>Dashboard</p> </a></li>\r\n              <li><a href=\"#\"> <i class=\'bx bx-library\'></i> <p>Library</p> </a></li>   \r\n              <li><a href=\"#\"> <i class=\'bx bx-question-mark\'></i> <p>Quizzes</p> </a></li>   \r\n              <li><a href=\"#\"> <i class=\'bx bxs-videos\' ></i> <p>Courses</p> </a></li>   \r\n              <li><a href=\"#\"> <i class=\'bx bxs-file-blank\'></i> <p>Sheets</p> </a></li>   \r\n              <li><a href=\"#\"> <i class=\'bx bxs-video\'></i> <p>Meeting</p> </a></li>   \r\n              <li><a href=\"#\"> <i class=\'bx bxs-message-rounded-dots\' ></i> <p>Chat</p> </a></li>   \r\n              <li><a href=\"#\"> <i class=\'bx bxs-calendar\'></i> <p>Calendar</p> </a></li>   \r\n              </ul>\r\n             </div> \r\n          </div>\r\n          </div>\r\n          <div class=\"user\">\r\n            <div class=\"user_notifications icon\">\r\n              <svg\r\n                width=\"27\"\r\n                height=\"35\"\r\n                viewBox=\"0 0 40 40\"\r\n                fill=\"none\"\r\n                xmlns=\"http://www.w3.org/2000/svg\"\r\n              >\r\n                <path\r\n                  fill-rule=\"evenodd\"\r\n                  clip-rule=\"evenodd\"\r\n                  d=\"M5.10142 13.8514C5.10142 10.1777 6.67109 6.6546 9.46513 4.05697C12.2592 1.45934 16.0487 0 20.0001 0C23.9515 0 27.741 1.45934 30.535 4.05697C33.3291 6.6546 34.8987 10.1777 34.8987 13.8514V15.3904C34.8987 19.7469 36.6645 23.7156 39.5736 26.7177C39.7548 26.9044 39.8841 27.1296 39.95 27.3735C40.016 27.6174 40.0167 27.8726 39.952 28.1168C39.8873 28.361 39.7592 28.5867 39.579 28.7742C39.3987 28.9618 39.1718 29.1054 38.9181 29.1925C35.5101 30.3622 31.9433 31.224 28.255 31.7432C28.3381 32.7982 28.1863 33.8579 27.809 34.8559C27.4318 35.854 26.8373 36.769 26.0626 37.5438C25.288 38.3186 24.3498 38.9367 23.3067 39.3593C22.2636 39.7819 21.1379 40 20.0001 40C18.8622 40 17.7366 39.7819 16.6935 39.3593C15.6504 38.9367 14.7122 38.3186 13.9375 37.5438C13.1629 36.769 12.5683 35.854 12.1911 34.8559C11.8139 33.8579 11.6621 32.7982 11.7451 31.7432C8.10687 31.2306 4.53414 30.3753 1.08209 29.1904C0.828555 29.1034 0.601793 28.96 0.421607 28.7728C0.241422 28.5855 0.113293 28.36 0.0484117 28.1161C-0.0164697 27.8722 -0.0161307 27.6172 0.0493995 27.3735C0.11493 27.1297 0.243658 26.9046 0.424341 26.7177C3.44117 23.6123 5.10821 19.5749 5.10142 15.3904V13.8514ZM15.0383 32.1146C15.01 32.7368 15.1175 33.3578 15.3541 33.9404C15.5907 34.5229 15.9517 35.055 16.4152 35.5045C16.8787 35.954 17.4353 36.3117 18.0513 36.5561C18.6674 36.8004 19.3303 36.9264 20.0001 36.9264C20.6699 36.9264 21.3328 36.8004 21.9488 36.5561C22.5649 36.3117 23.1215 35.954 23.585 35.5045C24.0485 35.055 24.4094 34.5229 24.6461 33.9404C24.8827 33.3578 24.9901 32.7368 24.9619 32.1146C21.6607 32.3911 18.3395 32.3911 15.0383 32.1146Z\"\r\n                  fill=\"#FFFBFB\"\r\n                />\r\n              </svg>\r\n            </div>\r\n            <div class=\"user_profile icon\">\r\n              <svg\r\n                id=\"userIcon\"\r\n                width=\"30\"\r\n                viewBox=\"0 0 87 90\"\r\n                fill=\"none\"\r\n                stroke=\"currentcolor\"\r\n                stroke-width=\"5\"\r\n                xmlns=\"http://www.w3.org/2000/svg\"\r\n              >\r\n                <path\r\n                  fill-rule=\"evenodd\"\r\n                  clip-rule=\"evenodd\"\r\n                  d=\"M73.3888 77.3527C77.6683 73.1998 81.0775 68.1845 83.4058 62.6169C85.734 57.0493 86.9318 51.0476 86.925 44.983C86.925 20.4218 67.6475 0.512939 43.8653 0.512939C20.0831 0.512939 0.805571 20.4218 0.805571 44.983C0.798776 51.0476 1.99652 57.0493 4.32476 62.6169C6.65301 68.1845 10.0623 73.1998 14.3418 77.3527C22.3219 85.1378 32.8877 89.4683 43.8653 89.4531C54.8429 89.4683 65.4086 85.1378 73.3888 77.3527ZM18.0074 71.4917C21.108 67.4855 25.0427 64.2521 29.5193 62.0317C33.9958 59.8114 38.8992 58.661 43.8653 58.6661C48.8313 58.661 53.7347 59.8114 58.2113 62.0317C62.6878 64.2521 66.6225 67.4855 69.7232 71.4917C66.3403 75.0206 62.3127 77.8207 57.8739 79.7295C53.4351 81.6383 48.6735 82.618 43.8653 82.6115C39.057 82.618 34.2954 81.6383 29.8566 79.7295C25.4178 77.8207 21.3902 75.0206 18.0074 71.4917ZM60.4267 31.2999C60.4267 35.8361 58.6818 40.1866 55.576 43.3942C52.4701 46.6018 48.2576 48.4038 43.8653 48.4038C39.4729 48.4038 35.2604 46.6018 32.1546 43.3942C29.0487 40.1866 27.3038 35.8361 27.3038 31.2999C27.3038 26.7637 29.0487 22.4132 32.1546 19.2056C35.2604 15.998 39.4729 14.196 43.8653 14.196C48.2576 14.196 52.4701 15.998 55.576 19.2056C58.6818 22.4132 60.4267 26.7637 60.4267 31.2999Z\"\r\n                  fill=\"white\"\r\n                />\r\n              </svg>\r\n            </div>\r\n          </div>\r\n        </nav>\r\n      </div>\r\n    </section>\r\n    <section>\r\n      <header>\r\n        <h1>\r\n          <span>w</span>\r\n          <span>e</span>\r\n          <span>l</span>\r\n          <span>c</span>\r\n          <span>o</span>\r\n          <span>m</span>\r\n          <span>e</span>\r\n      </h1>\r\n        <div class=\"profile\">\r\n          <div class=\"profile_user\">\r\n            <img\r\n              class=\"profile_user_image\"\r\n              src=\"<?php echo !empty($profile_image) ? $profile_image : \'images/user-removebg-preview (1).png\'; ?>\"\r\n              alt=\"<?php echo htmlspecialchars($display_name); ?>\"\r\n            />\r\n            <div class=\"edit-profile-icon\" id=\"edit-profile-image-btn\">\r\n              <i class=\'bx bxs-camera\'></i>\r\n            </div>\r\n          </div>\r\n        </div>\r\n      </header>\r\n    </section>\r\n    <section>\r\n      <main>\r\n        <div class=\"profile_container\">\r\n          <div class=\"profile_title\">\r\n            <h3><?php echo htmlspecialchars($display_name); ?></h3>\r\n            <p><i class=\'bx bxs-school\'></i> <?php echo htmlspecialchars($department); ?></p>\r\n            <p class=\"fourth\"><i class=\'bx bxs-graduation\'></i> <?php echo htmlspecialchars($year_or_role); ?></p>\r\n            \r\n            <?php if($update_success): ?>\r\n            <div class=\"alert alert-success\">\r\n              <i class=\'bx bxs-check-circle\'></i> Profile updated successfully!\r\n            </div>\r\n            <?php endif; ?>\r\n            \r\n            <?php if(!empty($update_error)): ?>\r\n            <div class=\"alert alert-danger\">\r\n              <i class=\'bx bxs-error\'></i> <?php echo $update_error; ?>\r\n            </div>\r\n            <?php endif; ?>\r\n            \r\n            <?php if(!empty($courses)): ?>\r\n            <div class=\"courses-section\">\r\n              <div class=\"courses-header\">\r\n                <i class=\'bx bxs-book-open\'></i>\r\n                <h4>My Courses</h4>\r\n              </div>\r\n              <ul class=\"courses-list\">\r\n                <?php foreach($courses as $course): ?>\r\n                <li><?php echo htmlspecialchars($course[\'name\']); ?></li>\r\n                <?php endforeach; ?>\r\n              </ul>\r\n            </div>\r\n            <?php endif; ?>\r\n          </div>\r\n        </div>\r\n      </main>\r\n    </section>\r\n    <div class=\"chat_icon\">\r\n      <svg\r\n        height=\"90\"\r\n        viewBox=\"0 0 150 150\"\r\n        fill=\"none\"\r\n        xmlns=\"http://www.w3.org/2000/svg\"\r\n      >\r\n        <circle cx=\"75\" cy=\"75\" r=\"75\" fill=\"#F5C254\" />\r\n        <path\r\n          fill-rule=\"evenodd\"\r\n          clip-rule=\"evenodd\"\r\n          d=\"M34.8059 134.383C37.0106 134.838 39.2464 135.065 41.4864 135.062C48.5661 135.073 55.5042 132.788 61.5 128.47C65.8233 129.636 70.3477 130.257 75.0006 130.257C104.728 130.257 129.461 104.823 129.461 72.5975C129.461 40.3721 104.728 14.9377 75.0006 14.9377C45.2735 14.9377 20.54 40.3721 20.54 72.5975C20.54 88.0311 26.2654 101.985 35.4762 112.267C36.7721 113.715 37.0234 115.009 36.895 115.746C36.2047 119.732 34.6398 123.449 32.3482 126.548C31.8725 127.192 31.5531 127.969 31.4223 128.799C31.2914 129.63 31.3537 130.486 31.6029 131.28C31.8521 132.074 32.2793 132.779 32.8416 133.324C33.4039 133.869 34.0814 134.234 34.8059 134.383ZM54.0542 65.39C52.3876 65.39 50.7893 66.1494 49.6108 67.501C48.4324 68.8527 47.7703 70.6859 47.7703 72.5975C47.7703 74.509 48.4324 76.3423 49.6108 77.6939C50.7893 79.0456 52.3876 79.8049 54.0542 79.8049C55.7208 79.8049 57.3192 79.0456 58.4976 77.6939C59.6761 76.3423 60.3382 74.509 60.3382 72.5975C60.3382 70.6859 59.6761 68.8527 58.4976 67.501C57.3192 66.1494 55.7208 65.39 54.0542 65.39ZM68.7167 72.5975C68.7167 70.6859 69.3788 68.8527 70.5572 67.501C71.7357 66.1494 73.334 65.39 75.0006 65.39C76.6672 65.39 78.2656 66.1494 79.444 67.501C80.6225 68.8527 81.2845 70.6859 81.2845 72.5975C81.2845 74.509 80.6225 76.3423 79.444 77.6939C78.2656 79.0456 76.6672 79.8049 75.0006 79.8049C73.334 79.8049 71.7357 79.0456 70.5572 77.6939C69.3788 76.3423 68.7167 74.509 68.7167 72.5975ZM95.947 65.39C94.2804 65.39 92.6821 66.1494 91.5036 67.501C90.3251 68.8527 89.6631 70.6859 89.6631 72.5975C89.6631 74.509 90.3251 76.3423 91.5036 77.6939C92.6821 79.0456 94.2804 79.8049 95.947 79.8049C97.6136 79.8049 99.2119 79.0456 100.39 77.6939C101.569 76.3423 102.231 74.509 102.231 72.5975C102.231 70.6859 101.569 68.8527 100.39 67.501C99.2119 66.1494 97.6136 65.39 95.947 65.39Z\"\r\n          fill=\"white\"\r\n        />\r\n      </svg>\r\n    </div>\r\n    <div class=\"profile_settings\">\r\n      <div class=\"profile_settings_container\">\r\n        <div class=\"profile_settings_logout\">\r\n          <a href=\"profile.php?logout=1\">\r\n            <svg\r\n              width=\"28\"\r\n              height=\"28\"\r\n              viewBox=\"0 0 28 28\"\r\n              fill=\"none\"\r\n              xmlns=\"http://www.w3.org/2000/svg\"\r\n            >\r\n              <path\r\n                fill-rule=\"evenodd\"\r\n                clip-rule=\"evenodd\"\r\n                d=\"M4.66667 2.15385C4.04783 2.15385 3.45434 2.38077 3.01675 2.78469C2.57917 3.18862 2.33333 3.73646 2.33333 4.30769V23.6923C2.33333 24.2635 2.57917 24.8114 3.01675 25.2153C3.45434 25.6192 4.04783 25.8462 4.66667 25.8462H14C14.6188 25.8462 15.2123 25.6192 15.6499 25.2153C16.0875 24.8114 16.3333 24.2635 16.3333 23.6923V18.3077C16.3333 18.0221 16.4563 17.7482 16.675 17.5462C16.8938 17.3442 17.1906 17.2308 17.5 17.2308C17.8094 17.2308 18.1062 17.3442 18.325 17.5462C18.5438 17.7482 18.6667 18.0221 18.6667 18.3077V23.6923C18.6667 24.8348 18.175 25.9305 17.2998 26.7383C16.4247 27.5462 15.2377 28 14 28H4.66667C3.42899 28 2.242 27.5462 1.36684 26.7383C0.491665 25.9305 0 24.8348 0 23.6923V4.30769C0 3.16522 0.491665 2.06954 1.36684 1.26169C2.242 0.453845 3.42899 0 4.66667 0H14C15.2377 0 16.4247 0.453845 17.2998 1.26169C18.175 2.06954 18.6667 3.16522 18.6667 4.30769V9.69231C18.6667 9.97793 18.5438 10.2518 18.325 10.4538C18.1062 10.6558 17.8094 10.7692 17.5 10.7692C17.1906 10.7692 16.8938 10.6558 16.675 10.4538C16.4563 10.2518 16.3333 9.97793 16.3333 9.69231V4.30769C16.3333 3.73646 16.0875 3.18862 15.6499 2.78469C15.2123 2.38077 14.6188 2.15385 14 2.15385H4.66667ZM12.4911 8.93128C12.7096 9.13321 12.8323 9.40692 12.8323 9.69231C12.8323 9.97769 12.7096 10.2514 12.4911 10.4533L9.81556 12.9231H26.8333C27.1428 12.9231 27.4395 13.0365 27.6583 13.2385C27.8771 13.4405 28 13.7144 28 14C28 14.2856 27.8771 14.5595 27.6583 14.7615C27.4395 14.9635 27.1428 15.0769 26.8333 15.0769H9.81556L12.4911 17.5467C12.6057 17.6453 12.6977 17.7642 12.7614 17.8963C12.8252 18.0284 12.8595 18.171 12.8623 18.3156C12.865 18.4602 12.8362 18.6038 12.7775 18.7379C12.7188 18.872 12.6315 18.9938 12.5207 19.0961C12.4099 19.1983 12.278 19.2789 12.1327 19.3331C11.9874 19.3873 11.8318 19.4139 11.6752 19.4113C11.5185 19.4088 11.3641 19.3771 11.2209 19.3183C11.0778 19.2594 10.949 19.1745 10.8422 19.0687L6.17556 14.761C5.95708 14.5591 5.83436 14.2854 5.83436 14C5.83436 13.7146 5.95708 13.4409 6.17556 13.239L10.8422 8.93128C11.061 8.72961 11.3575 8.61633 11.6667 8.61633C11.9758 8.61633 12.2724 8.72961 12.4911 8.93128Z\"\r\n                fill=\"#9C9C9C\"\r\n              />\r\n            </svg>\r\n            <p>log out</p>\r\n          </a>\r\n        </div>\r\n        \r\n        <!-- Profile image edit form -->\r\n        <form id=\"profileImageForm\" action=\"profile.php\" method=\"post\" enctype=\"multipart/form-data\">\r\n          <input type=\"hidden\" name=\"update_profile\" value=\"1\">\r\n          <?php if($role == \'student\'): ?>\r\n          <input type=\"hidden\" name=\"name\" value=\"<?php echo htmlspecialchars($user[\'name\']); ?>\">\r\n          <input type=\"hidden\" name=\"level\" value=\"<?php echo htmlspecialchars($user[\'level\']); ?>\">\r\n          <input type=\"hidden\" name=\"department_id\" value=\"<?php echo htmlspecialchars($user[\'department_id\']); ?>\">\r\n          <?php elseif($role == \'professor\'): ?>\r\n          <input type=\"hidden\" name=\"name\" value=\"<?php echo htmlspecialchars($user[\'name\']); ?>\">\r\n          <input type=\"hidden\" name=\"department_id\" value=\"<?php echo htmlspecialchars($user[\'department_id\']); ?>\">\r\n          <?php elseif($role == \'admin\'): ?>\r\n          <input type=\"hidden\" name=\"name\" value=\"<?php echo htmlspecialchars($user[\'name\']); ?>\">\r\n          <?php endif; ?>\r\n          <input type=\"file\" name=\"profile_image\" id=\"profileImageInput\" style=\"display: none;\">\r\n        </form>\r\n      </div>\r\n    </div>\r\n    \r\n    <div class=\"footer\">\r\n      <div class=\"footer_container\">\r\n        <div class=\"footer_content\">\r\n          <a href=\"#\" class=\"website\">www.unihive.com</a>\r\n          <div class=\"footer_links\">\r\n            <!-- ...existing code... -->\r\n          </div>\r\n        </div>\r\n      </div>\r\n    </div>\r\n    \r\n    <script src=\"./script.js\"></script>\r\n    <script>\r\n      document.addEventListener(\'DOMContentLoaded\', function() {\r\n        // Animation for welcome text\r\n        const letters = document.querySelectorAll(\'h1 span\');\r\n        letters.forEach(span => {\r\n          span.addEventListener(\'mouseenter\', function() {\r\n            this.style.transform = \'translateY(-20px)\';\r\n            this.style.color = \'#F5C254\';\r\n            this.style.textShadow = \'0 5px 10px rgba(0, 0, 0, 0.3)\';\r\n          });\r\n          \r\n          span.addEventListener(\'mouseleave\', function() {\r\n            this.style.transform = \'translateY(0)\';\r\n            this.style.color = \'\';\r\n            this.style.textShadow = \'\';\r\n          });\r\n        });\r\n        \r\n        // Profile image upload\r\n        const editImageBtn = document.getElementById(\'edit-profile-image-btn\');\r\n        const fileInput = document.getElementById(\'profileImageInput\');\r\n        \r\n        editImageBtn.addEventListener(\'click\', function(e) {\r\n          e.preventDefault();\r\n          fileInput.click();\r\n        });\r\n        \r\n        fileInput.addEventListener(\'change\', function() {\r\n          if (this.files && this.files[0]) {\r\n            document.getElementById(\'profileImageForm\').submit();\r\n          }\r\n        });\r\n        \r\n        // Mobile menu toggle\r\n        const menuToggle = document.getElementById(\'menu-toggle\');\r\n        const menu = document.getElementById(\'mean\');\r\n        \r\n        menuToggle.addEventListener(\'click\', function() {\r\n          menu.classList.toggle(\'active\');\r\n        });\r\n      });\r\n    </script>\r\n  </body>\r\n</html>\r\n````\r\n\r\nThese changes enhance the profile page with:\r\n\r\n1. Better card styling with shadows and rounded corners\r\n2. Improved spacing and visual hierarchy\r\n3. Added icons for departments, roles, and courses\r\n4. Enhanced profile image presentation with a proper camera icon for editing\r\n5. Hover effects for interactive elements\r\n6. Added animations for a more engaging user experience\r\n7. More consistent styling throughout the page\r\n\r\nThe changes maintain the original functionality while giving the profile a more professional appearance.\r\n\r\nMade changes.\r\n\r\nSimilar code found with 3 license types', '2025-04-29 05:05:32', 1, 'all', NULL),
(14, 'سس', 'سس', '2025-04-29 05:05:40', 1, 'all', NULL),
(15, 'hi', 'hello', '2025-04-29 05:08:10', 1, 'all', NULL),
(16, 'kk', 'lo', '2025-04-29 05:08:53', 1, 'course', 10),
(17, 'سسس', 'سسس', '2025-04-29 05:15:51', 1, 'course', 0),
(18, 'ء', 'ءءء', '2025-04-29 05:16:15', 1, 'course', 10),
(19, 'test', 'test', '2025-04-29 21:07:46', 1, 'all', NULL),
(20, 'ssss', 'sss', '2025-04-29 21:08:27', 1, 'course', 8),
(21, 'jjj', 'hhh', '2025-04-29 21:08:42', 1, 'course', 9),
(22, 'jjj', 'jjj', '2025-04-29 21:09:21', 1, 'course', 1),
(23, 'k', 'k', '2025-04-29 21:10:26', 1, 'course', 6),
(24, 'g', 'g', '2025-04-29 21:14:38', 1, 'course', 1),
(25, 'jjjjj', 'hhhh', '2025-04-29 21:16:21', 1, 'course', 6),
(26, 'sss', 'sss', '2025-04-29 21:18:54', 1, 'course', 6),
(27, 'sssssssss', 'ssss', '2025-04-29 21:19:24', 1, 'course', 1),
(28, 'بببببببببببب', 'بببببببببب', '2025-04-29 21:29:32', 1, 'course', 1),
(29, 'صصص', 'صصص', '2025-04-29 21:39:36', 1, 'course', 1),
(30, 'اهعاها', 'نتاهعاه', '2025-04-29 21:48:55', 1, 'all', NULL),
(31, 'ة', 'ى', '2025-04-29 21:52:16', 1, 'course', 1),
(32, 'بب', 'بب', '2025-04-30 00:00:14', 1, 'course', 1),
(33, 'ل', 'ل', '2025-04-30 01:52:10', 1, 'all', NULL),
(34, 'ش', 'ش', '2025-05-01 22:12:12', 1, 'all', NULL),
(35, 'skfudyfius', 'siflydfiosyod', '2025-05-05 13:23:36', 1, 'all', NULL),
(36, 'hi', 'hello', '2025-05-06 09:11:02', 1, 'all', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `professors`
--

DROP TABLE IF EXISTS `professors`;
CREATE TABLE IF NOT EXISTS `professors` (
  `professor_id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `department_id` int DEFAULT NULL,
  PRIMARY KEY (`professor_id`),
  KEY `department_id` (`department_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `professors`
--

INSERT INTO `professors` (`professor_id`, `name`, `department_id`) VALUES
(2, 'John Smith', 2),
(10, 'ali alo', 2),
(14, 'ramez ali', 1);

-- --------------------------------------------------------

--
-- Table structure for table `professor_courses`
--

DROP TABLE IF EXISTS `professor_courses`;
CREATE TABLE IF NOT EXISTS `professor_courses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `professor_id` int DEFAULT NULL,
  `course_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `professor_id` (`professor_id`),
  KEY `course_id` (`course_id`)
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `professor_courses`
--

INSERT INTO `professor_courses` (`id`, `professor_id`, `course_id`) VALUES
(9, 2, 13),
(8, 10, 9),
(3, 4, 3),
(4, 3, 4),
(5, 3, 5),
(6, 2, 6);

-- --------------------------------------------------------

--
-- Table structure for table `question_options`
--

DROP TABLE IF EXISTS `question_options`;
CREATE TABLE IF NOT EXISTS `question_options` (
  `option_id` int NOT NULL AUTO_INCREMENT,
  `question_id` int NOT NULL,
  `option_text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_correct` tinyint(1) NOT NULL DEFAULT '0',
  `option_order` int DEFAULT NULL,
  PRIMARY KEY (`option_id`),
  KEY `question_id` (`question_id`)
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `question_options`
--

INSERT INTO `question_options` (`option_id`, `question_id`, `option_text`, `is_correct`, `option_order`) VALUES
(59, 19, 'retyre', 1, 1),
(60, 19, 'erterer', 0, 2),
(61, 19, 'ert', 0, 3),
(62, 19, 'e4rtertert', 0, 4);

-- --------------------------------------------------------

--
-- Table structure for table `quizzes`
--

DROP TABLE IF EXISTS `quizzes`;
CREATE TABLE IF NOT EXISTS `quizzes` (
  `quiz_id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `course_id` int NOT NULL,
  `professor_id` int NOT NULL,
  `time_limit` int NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`quiz_id`),
  KEY `course_id` (`course_id`),
  KEY `professor_id` (`professor_id`)
) ENGINE=MyISAM AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `quizzes`
--

INSERT INTO `quizzes` (`quiz_id`, `title`, `description`, `course_id`, `professor_id`, `time_limit`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES
(25, 'test', NULL, 13, 2, 30, '2025-05-23 18:51:29', '2025-05-23 22:51:00', '2025-05-23 15:51:29', '2025-05-23 15:51:29');

-- --------------------------------------------------------

--
-- Table structure for table `quiz_answers`
--

DROP TABLE IF EXISTS `quiz_answers`;
CREATE TABLE IF NOT EXISTS `quiz_answers` (
  `answer_id` int NOT NULL AUTO_INCREMENT,
  `question_id` int NOT NULL,
  `answer_text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_correct` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`answer_id`),
  KEY `question_id` (`question_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quiz_attempts`
--

DROP TABLE IF EXISTS `quiz_attempts`;
CREATE TABLE IF NOT EXISTS `quiz_attempts` (
  `attempt_id` int NOT NULL AUTO_INCREMENT,
  `quiz_id` int NOT NULL,
  `student_id` int NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime DEFAULT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `status` enum('in_progress','completed','timed_out') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'in_progress',
  PRIMARY KEY (`attempt_id`),
  KEY `quiz_id` (`quiz_id`),
  KEY `student_id` (`student_id`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `quiz_attempts`
--

INSERT INTO `quiz_attempts` (`attempt_id`, `quiz_id`, `student_id`, `start_time`, `end_time`, `score`, `status`) VALUES
(8, 25, 12, '2025-05-23 18:53:11', '2025-05-23 18:53:22', 0.00, 'completed');

-- --------------------------------------------------------

--
-- Table structure for table `quiz_questions`
--

DROP TABLE IF EXISTS `quiz_questions`;
CREATE TABLE IF NOT EXISTS `quiz_questions` (
  `question_id` int NOT NULL AUTO_INCREMENT,
  `quiz_id` int NOT NULL,
  `question_text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `question_type` enum('multiple_choice','true_false','short_answer') COLLATE utf8mb4_unicode_ci NOT NULL,
  `points` int NOT NULL DEFAULT '1',
  `question_order` int DEFAULT '0',
  PRIMARY KEY (`question_id`),
  KEY `quiz_id` (`quiz_id`)
) ENGINE=MyISAM AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `quiz_questions`
--

INSERT INTO `quiz_questions` (`question_id`, `quiz_id`, `question_text`, `question_type`, `points`, `question_order`) VALUES
(19, 25, 'rtertewt', 'multiple_choice', 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `recording_notes`
--

DROP TABLE IF EXISTS `recording_notes`;
CREATE TABLE IF NOT EXISTS `recording_notes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `recording_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `professor_id` int NOT NULL,
  `note_content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `timestamp` float DEFAULT '0',
  `is_public` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `note_category` enum('important','explanation','question','reminder','general') COLLATE utf8mb4_unicode_ci DEFAULT 'general',
  `note_color` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#f8f9fa',
  PRIMARY KEY (`id`),
  KEY `recording_id` (`recording_id`),
  KEY `professor_id` (`professor_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `recording_notes`
--

INSERT INTO `recording_notes` (`id`, `recording_id`, `professor_id`, `note_content`, `timestamp`, `is_public`, `created_at`, `updated_at`, `note_category`, `note_color`) VALUES
(2, '724a458c5f8dfaebdaaba7ea98a5947cc40f514e-1746450410545', 2, 'hello', 9, 1, '2025-05-05 22:14:48', '2025-05-05 22:14:48', 'important', '#f8f9fa'),
(3, '724a458c5f8dfaebdaaba7ea98a5947cc40f514e-1746450410545', 2, 'hi', 6, 1, '2025-05-05 22:17:51', '2025-05-05 22:17:51', 'general', '#f8f9fa'),
(4, '724a458c5f8dfaebdaaba7ea98a5947cc40f514e-1746450410545', 2, 'all', 0, 1, '2025-05-06 00:13:51', '2025-05-06 00:13:51', 'question', '#f8f9fa'),
(5, '724a458c5f8dfaebdaaba7ea98a5947cc40f514e-1746450410545', 2, 'نن', 0, 1, '2025-05-06 00:22:23', '2025-05-06 00:22:23', 'general', '#f8f9fa'),
(10, '6c84e297b9aed7a03f135a81f5dca70188a4773f-1746248912473', 2, 'اي الاخبار', 0, 1, '2025-05-06 07:40:48', '2025-05-06 07:40:48', 'general', '#f8f9fa'),
(11, '338937de85469758533c249481f0e2a707027ad5-1746526891423', 2, 'database', 0, 1, '2025-05-06 10:36:34', '2025-05-06 10:36:34', 'question', '#f8f9fa');

-- --------------------------------------------------------

--
-- Table structure for table `recording_notes_old`
--

DROP TABLE IF EXISTS `recording_notes_old`;
CREATE TABLE IF NOT EXISTS `recording_notes_old` (
  `id` int NOT NULL AUTO_INCREMENT,
  `recording_id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `professor_id` int NOT NULL,
  `title` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `note_content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_public` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `recording_notes_old`
--

INSERT INTO `recording_notes_old` (`id`, `recording_id`, `professor_id`, `title`, `note_content`, `created_at`, `updated_at`, `is_public`) VALUES
(1, '6c84e297b9aed7a03f135a81f5dca70188a4773f-1746248912473', 2, 'fgdfg', '', '2025-05-05 20:40:01', '2025-05-05 20:40:01', 1),
(2, '6c84e297b9aed7a03f135a81f5dca70188a4773f-1746248912473', 2, 'gerge', '', '2025-05-05 20:40:17', '2025-05-05 20:40:17', 1),
(3, '6c84e297b9aed7a03f135a81f5dca70188a4773f-1746248912473', 2, 'hello', '', '2025-05-05 20:43:04', '2025-05-05 20:43:28', 1),
(4, '6c84e297b9aed7a03f135a81f5dca70188a4773f-1746248912473', 2, 'sdsff', 'sdffd', '2025-05-05 20:53:26', '2025-05-05 20:53:26', 1);

-- --------------------------------------------------------

--
-- Table structure for table `scheduled_meetings`
--

DROP TABLE IF EXISTS `scheduled_meetings`;
CREATE TABLE IF NOT EXISTS `scheduled_meetings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `meeting_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `course_id` int NOT NULL,
  `professor_id` int NOT NULL,
  `meeting_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `scheduled_time` datetime NOT NULL,
  `duration` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '60',
  `welcome_message` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL,
  `is_started` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `meeting_id` (`meeting_id`),
  KEY `meeting_id_2` (`meeting_id`),
  KEY `course_id` (`course_id`),
  KEY `professor_id` (`professor_id`),
  KEY `scheduled_time` (`scheduled_time`)
) ENGINE=MyISAM AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `scheduled_meetings`
--

INSERT INTO `scheduled_meetings` (`id`, `meeting_id`, `course_id`, `professor_id`, `meeting_name`, `scheduled_time`, `duration`, `welcome_message`, `created_at`, `is_started`) VALUES
(1, 'scheduled-13-2-1746323199', 13, 2, ' database 1 Meeting ', '2025-05-04 04:47:00', '60', 'اي الاخبار ', '2025-05-04 04:46:39', 1),
(2, 'scheduled-13-2-1746323369', 13, 2, 'data', '2025-05-04 04:50:00', '60', 'هلا والله', '2025-05-04 04:49:29', 1),
(3, 'scheduled-13-2-1746324787', 13, 2, 'tetst', '2025-05-04 05:14:00', '60', 'jjjjjjjjjj', '2025-05-04 05:13:07', 1),
(4, 'scheduled-13-2-1746325499', 13, 2, 'zero', '2025-05-04 05:26:00', '60', 'hhhhhhhh', '2025-05-04 05:24:59', 1),
(5, 'scheduled-13-2-1746326619', 13, 2, 'eee', '2025-05-04 05:44:00', '60', 'ddddddd', '2025-05-04 05:43:39', 1),
(6, 'scheduled-13-2-1746327163', 13, 2, 'sss', '2025-05-04 05:54:00', '60', 'helllo', '2025-05-04 05:52:43', 1),
(7, 'scheduled-13-2-1746327977', 13, 2, 'dddd', '2025-05-04 06:07:00', '60', 'ssssssssss', '2025-05-04 06:06:17', 1),
(8, 'scheduled-13-2-1746328294', 13, 2, 'ali', '2025-05-04 06:12:00', '60', 'ali and nada', '2025-05-04 06:11:34', 1),
(9, 'scheduled-13-2-1746329149', 13, 2, 'aaaa', '2025-05-04 06:26:00', '60', 'njjj', '2025-05-04 06:25:49', 1),
(10, 'scheduled-13-2-1746329262', 13, 2, 'aaaa', '2025-05-04 06:29:00', '60', 'hello', '2025-05-04 06:27:42', 1),
(11, 'scheduled-13-2-1746329654', 13, 2, 'aa', '2025-05-04 06:35:00', '60', 'kjhkjhlk', '2025-05-04 06:34:14', 1),
(12, 'scheduled-13-2-1746329820', 13, 2, 'hello', '2025-05-04 06:38:00', '60', 'hello all be', '2025-05-04 06:37:00', 1),
(13, 'scheduled-13-2-1746330016', 13, 2, 'no', '2025-05-04 06:41:00', '60', 'ggg', '2025-05-04 06:40:16', 1),
(14, 'scheduled-13-2-1746330274', 13, 2, 'alkdf', '2025-05-04 06:45:00', '60', 'j', '2025-05-04 06:44:34', 1),
(15, 'scheduled-13-2-1746368394', 13, 2, 'tero', '2025-05-04 17:21:00', '60', '44444', '2025-05-04 17:19:54', 1),
(16, 'scheduled-13-2-1746368622', 13, 2, 'test99', '2025-05-04 17:24:00', '60', 'ggg', '2025-05-04 17:23:42', 1),
(17, 'scheduled-13-2-1746368781', 13, 2, 'dddsd', '2025-05-04 17:27:00', '60', 'aaaaaaaaaaa', '2025-05-04 17:26:21', 1),
(18, 'scheduled-13-2-1746369162', 13, 2, 'khjfkdf', '2025-05-04 17:33:00', '60', '333', '2025-05-04 17:32:42', 1),
(19, 'scheduled-13-2-1746369507', 13, 2, 'dfsdfsdf', '2025-05-04 17:39:00', '60', 'ffffff', '2025-05-04 17:38:27', 1),
(20, 'scheduled-13-2-1746369856', 13, 2, 'liufgi', '2025-05-04 17:45:00', '60', 'jhkk;jk', '2025-05-04 17:44:16', 1),
(21, 'scheduled-13-2-1746370046', 13, 2, 'zeko', '2025-05-04 17:48:00', '60', 'vbnvbn', '2025-05-04 17:47:26', 1),
(22, 'scheduled-13-2-1746370789', 13, 2, 'dgdfg', '2025-05-04 18:00:00', '60', 'xcvxc', '2025-05-04 17:59:49', 1),
(23, 'scheduled-13-2-1746378058', 13, 2, 'dsfsdf', '2025-05-04 20:02:00', '60', 'sddd', '2025-05-04 20:00:58', 1),
(24, 'scheduled-13-2-1746378175', 13, 2, 'asd', '2025-05-04 20:07:00', '60', '7777', '2025-05-04 20:02:55', 1),
(25, 'scheduled-13-2-1746392109', 13, 2, 'www', '2025-05-05 00:00:00', '60', 'wwwwwww', '2025-05-04 23:55:09', 1),
(26, 'scheduled-13-2-1746397519', 13, 2, 'ytyt', '2025-05-05 01:26:00', '60', 'tyt', '2025-05-05 01:25:19', 1),
(27, 'scheduled-13-2-1746450326', 13, 2, 'ahmed', '2025-05-05 16:06:00', '59', 'hellllllo', '2025-05-05 16:05:26', 1),
(28, 'scheduled-13-2-1746527475', 13, 2, 'test', '2025-05-06 13:32:00', '60', 'hello', '2025-05-06 13:31:15', 1);

-- --------------------------------------------------------

--
-- Table structure for table `shared_recordings`
--

DROP TABLE IF EXISTS `shared_recordings`;
CREATE TABLE IF NOT EXISTS `shared_recordings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `meeting_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `course_id` int NOT NULL,
  `shared_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_meeting_course` (`meeting_id`,`course_id`),
  KEY `meeting_id` (`meeting_id`),
  KEY `course_id` (`course_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `shared_recordings`
--

INSERT INTO `shared_recordings` (`id`, `meeting_id`, `course_id`, `shared_at`) VALUES
(1, 'course-13-2-1746248071', 13, '2025-05-04 05:38:40');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

DROP TABLE IF EXISTS `students`;
CREATE TABLE IF NOT EXISTS `students` (
  `student_id` int NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `level` int NOT NULL,
  `department_id` int DEFAULT NULL,
  PRIMARY KEY (`student_id`),
  KEY `department_id` (`department_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `name`, `level`, `department_id`) VALUES
(5, 'Alice Williams', 1, 1),
(12, 'Ahmed Mohsen', 4, 2),
(7, 'ali ahmed', 3, 1);

-- --------------------------------------------------------

--
-- Table structure for table `student_answers`
--

DROP TABLE IF EXISTS `student_answers`;
CREATE TABLE IF NOT EXISTS `student_answers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `attempt_id` int NOT NULL,
  `question_id` int NOT NULL,
  `selected_option_id` int DEFAULT NULL,
  `answer_text` text COLLATE utf8mb4_unicode_ci,
  `selected_answer_id` int DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT NULL,
  `points_earned` decimal(5,2) DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `attempt_id` (`attempt_id`),
  KEY `question_id` (`question_id`),
  KEY `selected_answer_id` (`selected_answer_id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_answers`
--

INSERT INTO `student_answers` (`id`, `attempt_id`, `question_id`, `selected_option_id`, `answer_text`, `selected_answer_id`, `is_correct`, `points_earned`) VALUES
(1, 2, 13, 37, NULL, NULL, 0, 0.00),
(2, 3, 13, 35, NULL, NULL, 1, 0.00),
(3, 4, 14, 39, NULL, NULL, 0, 0.00),
(4, 5, 15, 41, NULL, NULL, 1, 0.00),
(5, 6, 16, 46, NULL, NULL, 0, 0.00),
(6, 8, 19, 60, NULL, NULL, 0, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `student_courses`
--

DROP TABLE IF EXISTS `student_courses`;
CREATE TABLE IF NOT EXISTS `student_courses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int DEFAULT NULL,
  `course_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `course_id` (`course_id`)
) ENGINE=MyISAM AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_courses`
--

INSERT INTO `student_courses` (`id`, `student_id`, `course_id`) VALUES
(20, 5, 7),
(25, 7, 2),
(3, 6, 4),
(4, 6, 5),
(27, 12, 4),
(7, 8, 5),
(8, 8, 6),
(23, 7, 4),
(24, 12, 13),
(11, 6, 4),
(12, 6, 5),
(26, 7, 5),
(15, 8, 5),
(28, 7, 13),
(17, 5, 4);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('student','professor','admin') COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `email`, `password`, `role`) VALUES
(1, 'admin@university.com', '54321', 'admin'),
(2, 'john.smith@university.com', '12345', 'professor'),
(12, 'ahmed@unihive.systems', '12345', 'student'),
(14, 'ramez@unihive.systems', '$2y$10$aPkyuBLx4qMYkTZoAp6TAeoV7st5DcVJpV56CIx7NVAWdmTzwbpl.', 'professor'),
(5, 'alice.williams@student.com', 'hashed_password', 'student'),
(10, 'mndo', '12345', 'professor'),
(7, 'ali@googo.com', '12345', 'student');

-- --------------------------------------------------------

--
-- Table structure for table `user_profiles`
--

DROP TABLE IF EXISTS `user_profiles`;
CREATE TABLE IF NOT EXISTS `user_profiles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `profile_image` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_profiles`
--

INSERT INTO `user_profiles` (`id`, `user_id`, `profile_image`, `created_at`, `updated_at`) VALUES
(1, 0, '7', '2025-04-28 01:59:46', '2025-04-28 01:59:46'),
(2, 7, 'uploads/profile_images/1745902130_photo_2023-09-01_12-49-26.jpg', '2025-04-28 02:30:58', '2025-04-29 04:48:50'),
(3, 12, 'uploads/profile_images/1745899266_photo_2025-04-28_05-44-34.jpg', '2025-04-28 02:44:56', '2025-04-29 04:01:06'),
(4, 2, 'uploads/profile_images/1746449951_photo_2025-04-28_05-44-34.jpg', '2025-05-05 12:59:11', '2025-05-05 12:59:11');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

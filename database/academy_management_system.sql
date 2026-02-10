-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 10, 2026 at 12:02 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `academy_management_system`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `check_announcement_expiration` ()   BEGIN
    -- Mark announcements as expired if end_date has passed
    UPDATE announcements 
    SET is_expired = 1, 
        status = 'inactive'
    WHERE end_date IS NOT NULL 
        AND end_date < NOW() 
        AND is_expired = 0;
    
    -- Mark announcements as expired if they're older than max duration
    UPDATE announcements 
    SET is_expired = 1, 
        status = 'inactive'
    WHERE end_date IS NULL 
        AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY) 
        AND is_expired = 0;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `target_role` varchar(50) DEFAULT NULL,
  `session_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `is_expired` tinyint(1) DEFAULT 0,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `created_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `message`, `target_role`, `session_id`, `created_at`, `status`, `start_date`, `end_date`, `expires_at`, `is_expired`, `priority`, `created_by`, `updated_at`) VALUES
(1, 'System Maintenance This Weekend', 'Our system will undergo scheduled maintenance on Saturday, Dec 15th from 10:00 PM to 2:00 AM. The portal will be temporarily unavailable during this time. Please complete any urgent tasks before the maintenance window.', 'all', 1, '2026-01-21 22:11:50', 'inactive', '2026-01-22 01:20:34', '2026-02-01 01:20:44', '0000-00-00 00:00:00', 0, 'high', 1, '2026-01-22 09:58:06');

-- --------------------------------------------------------

--
-- Table structure for table `batches`
--

CREATE TABLE `batches` (
  `id` int(11) NOT NULL,
  `skill_id` int(11) DEFAULT NULL,
  `session_id` int(11) DEFAULT NULL,
  `batch_name` varchar(255) DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `max_students` int(11) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `batches`
--

INSERT INTO `batches` (`id`, `skill_id`, `session_id`, `batch_name`, `start_time`, `end_time`, `max_students`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Batch A', '10:00:00', '12:00:00', 25, 'active', '2026-01-22 06:28:57', '2026-01-22 06:28:57'),
(2, 1, 1, 'Batch B', '14:00:00', '16:00:00', 25, 'active', '2026-01-22 06:28:57', '2026-01-22 06:28:57'),
(3, 2, 1, 'Batch A', '10:00:00', '12:00:00', 25, 'active', '2026-01-22 06:28:57', '2026-01-22 06:28:57'),
(4, 2, 1, 'Batch B', '14:00:00', '16:00:00', 25, 'active', '2026-01-22 06:28:57', '2026-01-22 06:28:57'),
(5, 3, 1, 'Batch A', '10:00:00', '12:00:00', 25, 'active', '2026-01-22 06:28:57', '2026-01-22 06:28:57'),
(6, 3, 1, 'Batch B', '14:00:00', '16:00:00', 25, 'active', '2026-01-22 06:28:57', '2026-01-22 06:28:57'),
(7, 4, 1, 'Batch A', '10:00:00', '12:00:00', 25, 'active', '2026-01-22 06:28:57', '2026-01-22 06:28:57'),
(8, 4, 1, 'Batch B', '14:00:00', '16:00:00', 25, 'active', '2026-01-22 06:28:57', '2026-01-22 06:28:57'),
(9, 5, 1, 'Batch A', '10:00:00', '12:00:00', 25, 'active', '2026-01-22 06:28:57', '2026-01-22 06:28:57'),
(10, 5, 1, 'Batch B', '14:00:00', '16:00:00', 25, 'active', '2026-01-22 06:28:57', '2026-01-22 06:28:57'),
(11, 6, 1, 'Batch A', '10:00:00', '12:00:00', 25, 'active', '2026-01-22 06:28:57', '2026-01-22 06:28:57'),
(12, 6, 1, 'Batch B', '14:00:00', '16:00:00', 25, 'active', '2026-01-22 06:28:57', '2026-01-22 06:28:57'),
(13, 7, 1, 'Batch A', '10:00:00', '12:00:00', 25, 'active', '2026-01-22 06:28:57', '2026-01-22 06:28:57'),
(14, 7, 1, 'Batch B', '14:00:00', '16:00:00', 25, 'active', '2026-01-22 06:28:57', '2026-01-22 06:28:57'),
(15, 8, 1, 'Batch A', '10:00:00', '12:00:00', 25, 'active', '2026-01-22 06:28:57', '2026-01-22 06:28:57'),
(16, 8, 1, 'Batch B', '14:00:00', '16:00:00', 25, 'active', '2026-01-22 06:28:57', '2026-01-22 06:28:57'),
(17, 9, 1, 'Batch A', '10:00:00', '12:00:00', 25, 'active', '2026-01-22 06:28:57', '2026-01-22 06:28:57'),
(18, 9, 1, 'Batch B', '14:00:00', '16:00:00', 25, 'active', '2026-01-22 06:28:57', '2026-01-22 06:28:57'),
(19, 10, 1, 'Batch A', '10:00:00', '12:00:00', 25, 'active', '2026-01-22 06:28:57', '2026-01-22 06:28:57'),
(20, 10, 1, 'Batch B', '14:00:00', '16:00:00', 25, 'active', '2026-01-22 06:28:57', '2026-01-22 06:28:57'),
(21, 11, 1, 'Batch A - Skill 11', '16:00:00', '17:01:00', 30, 'active', '2026-02-09 10:02:02', '2026-02-09 10:02:02');

-- --------------------------------------------------------

--
-- Table structure for table `batch_performance`
--

CREATE TABLE `batch_performance` (
  `id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `report_period` varchar(20) DEFAULT NULL,
  `total_students` int(11) DEFAULT 0,
  `avg_attendance` decimal(5,2) DEFAULT 0.00,
  `avg_quiz_score` decimal(5,2) DEFAULT 0.00,
  `top_performer` int(11) DEFAULT NULL,
  `weak_performer` int(11) DEFAULT NULL,
  `pass_percentage` decimal(5,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `batch_teachers`
--

CREATE TABLE `batch_teachers` (
  `id` int(11) NOT NULL,
  `batch_id` int(11) DEFAULT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `assigned_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `batch_teachers`
--

INSERT INTO `batch_teachers` (`id`, `batch_id`, `teacher_id`, `status`, `assigned_at`) VALUES
(1, 1, 3, NULL, NULL),
(2, 1, 3, NULL, NULL),
(3, 1, 3, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `certificates`
--

CREATE TABLE `certificates` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `skill_id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `certificate_number` varchar(50) NOT NULL,
  `verification_code` varchar(50) NOT NULL,
  `issued_date` datetime NOT NULL,
  `expiry_date` datetime DEFAULT NULL,
  `avg_score` decimal(5,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `certificates`
--

INSERT INTO `certificates` (`id`, `student_id`, `skill_id`, `batch_id`, `certificate_number`, `verification_code`, `issued_date`, `expiry_date`, `avg_score`, `created_at`) VALUES
(1, 3, 9, 17, 'CERT-0E3675AC-20260210', '06DCCBF83B17A63A', '2026-02-10 07:12:57', '2027-02-10 07:12:57', 100.00, '2026-02-10 06:12:57');

-- --------------------------------------------------------

--
-- Table structure for table `certificate_audit_log`
--

CREATE TABLE `certificate_audit_log` (
  `id` int(11) NOT NULL,
  `certificate_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `action` varchar(50) DEFAULT NULL,
  `action_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `donations`
--

CREATE TABLE `donations` (
  `id` int(11) NOT NULL,
  `donor_name` varchar(255) DEFAULT NULL,
  `donor_type` varchar(50) DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `donation_date` date DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expense_categories`
--

CREATE TABLE `expense_categories` (
  `id` int(11) NOT NULL,
  `category_name` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fee_collections`
--

CREATE TABLE `fee_collections` (
  `id` int(11) NOT NULL,
  `enrollment_id` int(11) DEFAULT NULL,
  `student_id` int(11) DEFAULT NULL,
  `skill_id` int(11) DEFAULT NULL,
  `session_id` int(11) DEFAULT NULL,
  `batch_id` int(11) DEFAULT NULL,
  `amount_paid` decimal(10,2) DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fee_collections`
--

INSERT INTO `fee_collections` (`id`, `enrollment_id`, `student_id`, `skill_id`, `session_id`, `batch_id`, `amount_paid`, `payment_date`, `payment_method`, `reference_no`, `remarks`, `status`, `created_at`, `updated_at`) VALUES
(1, NULL, 3, 9, 1, 17, 1000.00, '2026-02-06', 'cash', NULL, '', 'active', '2026-02-06 03:04:42', NULL),
(2, NULL, 3, 9, 1, 17, 500.00, '2026-02-06', 'card', NULL, '', 'active', '2026-02-06 03:04:59', NULL),
(3, NULL, 3, 9, 1, 17, 500.00, '2026-02-06', 'card', NULL, '', 'active', '2026-02-06 03:09:16', NULL),
(4, NULL, 3, 9, 1, 17, 500.00, '2026-02-08', 'online', NULL, '', 'active', '2026-02-08 21:13:04', NULL),
(5, NULL, 3, 9, 1, 17, 500.00, '2026-02-08', 'card', NULL, '', 'active', '2026-02-08 21:13:22', NULL),
(6, NULL, 3, 9, 1, 17, 500.00, '2026-02-08', 'cash', NULL, '', 'active', '2026-02-08 21:13:44', NULL),
(7, NULL, 3, 9, 1, 17, 500.00, '2026-02-08', 'card', NULL, '', 'active', '2026-02-08 21:14:29', NULL),
(8, NULL, 3, 9, 1, 17, 500.00, '2026-02-08', 'online', NULL, '', 'active', '2026-02-08 21:14:56', NULL),
(9, NULL, 3, 9, 1, 17, 500.00, '2026-02-08', 'cash', NULL, '', 'active', '2026-02-08 21:17:59', NULL),
(10, NULL, 3, 1, 1, 2, 5000.00, '2026-02-09', 'cheque', NULL, '', 'active', '2026-02-09 02:40:58', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `fee_structures`
--

CREATE TABLE `fee_structures` (
  `id` int(11) NOT NULL,
  `skill_id` int(11) DEFAULT NULL,
  `session_id` int(11) DEFAULT NULL,
  `total_fee` decimal(10,2) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fee_structures`
--

INSERT INTO `fee_structures` (`id`, `skill_id`, `session_id`, `total_fee`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 5000.00, 'active', '2026-01-21 02:20:31', '2026-01-21 02:20:31'),
(2, 2, 1, 5000.00, 'active', '2026-01-21 02:20:31', '2026-01-21 02:20:31'),
(3, 3, 1, 4000.00, 'active', '2026-01-21 02:20:31', '2026-01-21 02:20:31'),
(4, 4, 1, 4000.00, 'active', '2026-01-21 02:20:31', '2026-01-21 02:20:31'),
(5, 5, 1, 6000.00, 'active', '2026-01-21 02:20:31', '2026-01-21 02:20:31'),
(6, 6, 1, 6000.00, 'active', '2026-01-21 02:20:31', '2026-01-21 02:20:31'),
(7, 7, 1, 4500.00, 'active', '2026-01-21 02:20:31', '2026-01-21 02:20:31'),
(8, 8, 1, 4500.00, 'active', '2026-01-21 02:20:31', '2026-01-21 02:20:31'),
(9, 9, 1, 5500.00, 'active', '2026-01-21 02:20:31', '2026-01-21 02:20:31'),
(10, 10, 1, 5000.00, 'active', '2026-01-21 02:20:31', '2026-01-21 02:20:31'),
(11, 11, 1, 24999.98, 'active', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `institute_info`
--

CREATE TABLE `institute_info` (
  `id` int(11) NOT NULL,
  `institute_name` varchar(255) NOT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `institute_info`
--

INSERT INTO `institute_info` (`id`, `institute_name`, `logo_path`, `address`, `website`) VALUES
(1, 'My Academy', 'https://upload.wikimedia.org/wikipedia/commons/7/73/Example_logo.png', '123 Main Street, City', 'https://myacademy.com');

-- --------------------------------------------------------

--
-- Table structure for table `material_categories`
--

CREATE TABLE `material_categories` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `name` varchar(50) NOT NULL,
  `color` varchar(7) DEFAULT '#3b82f6',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `material_downloads`
--

CREATE TABLE `material_downloads` (
  `id` int(11) NOT NULL,
  `material_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `downloaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `monthly_profit`
--

CREATE TABLE `monthly_profit` (
  `id` int(11) NOT NULL,
  `month_year` varchar(50) DEFAULT NULL,
  `profit_month` decimal(10,2) DEFAULT NULL,
  `profit_year` decimal(10,2) DEFAULT NULL,
  `total_fees` decimal(10,2) DEFAULT NULL,
  `total_donations` decimal(10,2) DEFAULT NULL,
  `total_expenses` decimal(10,2) DEFAULT NULL,
  `net_profit` decimal(10,2) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `monthly_profit`
--

INSERT INTO `monthly_profit` (`id`, `month_year`, `profit_month`, `profit_year`, `total_fees`, `total_donations`, `total_expenses`, `net_profit`, `remarks`, `status`, `created_at`, `updated_at`) VALUES
(1, NULL, 2.00, 2026.00, 5000.00, 50000.00, 20000.00, 35000.00, '', 'active', '2026-02-08 23:06:13', '2026-02-08 23:06:13');

-- --------------------------------------------------------

--
-- Table structure for table `progress_history`
--

CREATE TABLE `progress_history` (
  `id` int(11) NOT NULL,
  `progress_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `skill_id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `old_progress_percent` decimal(5,2) DEFAULT NULL,
  `new_progress_percent` decimal(5,2) DEFAULT NULL,
  `old_quiz_score` decimal(5,2) DEFAULT NULL,
  `new_quiz_score` decimal(5,2) DEFAULT NULL,
  `old_assignment_score` decimal(5,2) DEFAULT NULL,
  `new_assignment_score` decimal(5,2) DEFAULT NULL,
  `old_project_score` decimal(5,2) DEFAULT NULL,
  `new_project_score` decimal(5,2) DEFAULT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `change_type` enum('Progress Update','Score Update','Remarks Update','Status Change') NOT NULL,
  `change_description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `progress_history`
--

INSERT INTO `progress_history` (`id`, `progress_id`, `student_id`, `skill_id`, `batch_id`, `session_id`, `old_progress_percent`, `new_progress_percent`, `old_quiz_score`, `new_quiz_score`, `old_assignment_score`, `new_assignment_score`, `old_project_score`, `new_project_score`, `changed_by`, `change_type`, `change_description`, `created_at`) VALUES
(1, 1, 1, 1, 1, 0, 0.00, 55.56, NULL, NULL, NULL, NULL, NULL, NULL, 3, 'Progress Update', 'Initial progress created. Topics: 5/9', '2026-01-29 10:23:49'),
(2, 2, 3, 9, 17, 0, 0.00, 50.00, NULL, NULL, NULL, NULL, NULL, NULL, 3, 'Progress Update', 'Initial progress created. Topics: 5/10', '2026-02-09 06:27:52'),
(3, 2, 3, 9, 17, 0, 0.00, 100.00, NULL, NULL, NULL, NULL, NULL, NULL, 4, 'Progress Update', 'Initial progress created. Topics: 10/10', '2026-02-09 11:53:19');

-- --------------------------------------------------------

--
-- Table structure for table `quizzes`
--

CREATE TABLE `quizzes` (
  `id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `total_questions` int(11) DEFAULT NULL,
  `total_marks` decimal(5,2) DEFAULT NULL,
  `time_limit` int(11) DEFAULT NULL,
  `status` enum('draft','published','completed') DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `quizzes`
--

INSERT INTO `quizzes` (`id`, `batch_id`, `teacher_id`, `title`, `description`, `total_questions`, `total_marks`, `time_limit`, `status`, `created_at`, `updated_at`, `start_date`, `end_date`) VALUES
(2, 1, 3, 'HTML TAGS', 'this is the html tags', 10, 1.00, 60, 'draft', '2026-01-30 06:52:15', '2026-01-30 06:53:59', NULL, NULL),
(3, 1, 3, 'css basic', 'in this student will do quiz about css', 5, 4.00, 60, 'draft', '2026-01-30 11:45:19', '2026-01-30 11:49:00', NULL, NULL),
(4, 18, 3, 'Cyber security basic', '', 10, 10.00, 60, 'draft', '2026-02-09 06:00:15', '2026-02-09 06:00:15', NULL, NULL),
(5, 18, 4, 'Cyber secruity quiz 1', 'solve this quiz to show your progress in cyber secuirty', 5, 4.00, 30, 'published', '2026-02-09 10:46:37', '2026-02-09 10:47:52', NULL, NULL),
(6, 17, 4, 'what is cyber secuirty', 'solve it', 1, 1.00, 10, 'published', '2026-02-09 10:49:12', '2026-02-09 10:49:40', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `quiz_questions`
--

CREATE TABLE `quiz_questions` (
  `id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `question` text NOT NULL,
  `question_type` enum('multiple_choice','true_false','short_answer') DEFAULT 'multiple_choice',
  `marks` int(11) DEFAULT 1,
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`options`)),
  `correct_answer` varchar(500) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `quiz_questions`
--

INSERT INTO `quiz_questions` (`id`, `quiz_id`, `question`, `question_type`, `marks`, `options`, `correct_answer`, `created_at`) VALUES
(1, 2, 'what is html stands for', 'multiple_choice', 1, '{\"A\":\"hyper text markup languag\",\"B\":\"web\",\"C\":\"python\",\"D\":\"c++\"}', '', '2026-01-30 06:53:04'),
(3, 3, 'gfdsfdsds', 'true_false', 1, NULL, '', '2026-01-30 11:47:20'),
(4, 3, 'dddSDx', 'true_false', 1, NULL, '', '2026-01-30 11:48:03'),
(5, 3, 'dasdasd', 'true_false', 1, NULL, '', '2026-01-30 11:48:28'),
(6, 3, 'sadsadfsaf', 'true_false', 1, NULL, '', '2026-01-30 11:49:00'),
(7, 5, 'what is cyber security', 'multiple_choice', 2, '{\"A\":\"hyper text markup languag\",\"B\":\"web\",\"C\":\"python\",\"D\":\"Secuirty in web\"}', '', '2026-02-09 10:47:15'),
(8, 5, 'what is active attack', 'multiple_choice', 2, '{\"A\":\"hyper text markup languag\",\"B\":\"cacsdin style sheet\",\"C\":\"python\",\"D\":\"Attack driectly on server\"}', '', '2026-02-09 10:47:52'),
(9, 6, 'what is cybser secuirty?', 'short_answer', 1, NULL, 'cyber securty is the filed of cs', '2026-02-09 10:49:40');

-- --------------------------------------------------------

--
-- Table structure for table `quiz_question_marks`
--

CREATE TABLE `quiz_question_marks` (
  `id` int(11) NOT NULL,
  `result_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `marks_awarded` decimal(5,2) NOT NULL DEFAULT 0.00,
  `feedback` text DEFAULT NULL,
  `graded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `graded_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quiz_results`
--

CREATE TABLE `quiz_results` (
  `id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `total_questions` int(11) DEFAULT NULL,
  `correct_answers` int(11) DEFAULT NULL,
  `answers` text DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `quiz_results`
--

INSERT INTO `quiz_results` (`id`, `quiz_id`, `student_id`, `batch_id`, `score`, `total_questions`, `correct_answers`, `answers`, `submitted_at`) VALUES
(1, 6, 3, 17, 0.00, 1, 0, '{\"9\":\"cyber scecuirty is feilds of cs\"}', '2026-02-09 10:52:26');

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` int(11) NOT NULL,
  `session_name` varchar(255) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `session_name`, `status`, `created_at`, `updated_at`) VALUES
(1, '2026–2027', 'active', '2026-01-21 02:23:31', '2026-01-21 02:23:31'),
(2, '2027–2028', 'active', '2026-01-21 02:23:31', '2026-01-21 02:23:31'),
(3, '2028–2029', 'active', '2026-01-21 02:23:31', '2026-01-21 02:23:31'),
(4, '2029–2030', 'active', '2026-01-21 02:23:31', '2026-01-21 02:23:31'),
(5, '2030–2031', 'active', '2026-01-21 02:23:31', '2026-01-21 02:23:31'),
(6, 'Session 2032', 'active', NULL, NULL),
(7, 'Session 2032', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `skills`
--

CREATE TABLE `skills` (
  `id` int(11) NOT NULL,
  `skill_name` varchar(255) DEFAULT NULL,
  `duration_months` int(11) DEFAULT NULL,
  `level` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `has_syllabus` tinyint(1) DEFAULT NULL,
  `has_practice` tinyint(1) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `skills`
--

INSERT INTO `skills` (`id`, `skill_name`, `duration_months`, `level`, `description`, `has_syllabus`, `has_practice`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Web Development', 3, 'Beginner', 'Learn HTML, CSS, JavaScript, and basic web projects', 1, 1, 'active', '2026-01-21 02:19:36', '2026-01-21 02:19:36'),
(2, 'Python Programming', 3, 'Beginner', 'Learn Python basics and scripting', 1, 1, 'active', '2026-01-21 02:19:36', '2026-01-21 02:19:36'),
(3, 'Graphic Design', 2, 'Beginner', 'Learn Photoshop, Illustrator, and design principles', 1, 1, 'active', '2026-01-21 02:19:36', '2026-01-21 02:19:36'),
(4, 'Digital Marketing', 2, 'Beginner', 'Learn SEO, social media marketing, and ads', 1, 1, 'active', '2026-01-21 02:19:36', '2026-01-21 02:19:36'),
(5, 'Data Science', 4, 'Intermediate', 'Learn data analysis, statistics, and Python for data', 1, 1, 'active', '2026-01-21 02:19:36', '2026-01-21 02:19:36'),
(6, 'Machine Learning', 4, 'Intermediate', 'Learn ML algorithms and Python implementation', 1, 1, 'active', '2026-01-21 02:19:36', '2026-01-21 02:19:36'),
(7, 'Gen AI', 2, 'Beginner', 'Learn AI tools and applications', 1, 1, 'active', '2026-01-21 02:19:36', '2026-01-21 02:19:36'),
(8, 'UI/UX Design', 2, 'Beginner', 'Learn design for websites and apps', 1, 1, 'active', '2026-01-21 02:19:36', '2026-01-21 02:19:36'),
(9, 'Cyber Security', 3, 'Intermediate', 'Learn ethical hacking and security fundamentals', 1, 1, 'active', '2026-01-21 02:19:36', '2026-01-21 02:19:36'),
(10, 'Mobile App Development', 3, 'Beginner', 'Learn to create apps for Android and iOS', 1, 1, 'active', '2026-01-21 02:19:36', '2026-01-21 02:19:36'),
(11, 'Banking Application', 6, 'Advanced', 'use for banking', 0, 0, 'active', '2026-02-09 01:59:44', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `skill_progress`
--

CREATE TABLE `skill_progress` (
  `id` int(11) NOT NULL,
  `enrollment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `skill_id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `topics_completed` int(11) DEFAULT 0,
  `total_topics` int(11) DEFAULT 0,
  `progress_percent` decimal(5,2) DEFAULT 0.00,
  `quiz_score` decimal(5,2) DEFAULT 0.00,
  `assignment_score` decimal(5,2) DEFAULT 0.00,
  `project_score` decimal(5,2) DEFAULT 0.00,
  `overall_performance` decimal(5,2) DEFAULT 0.00,
  `performance_level` enum('Beginner','Intermediate','Advanced','Excellent') DEFAULT 'Beginner',
  `remarks` text DEFAULT NULL,
  `status` enum('Active','Completed','Needs Attention') DEFAULT 'Active',
  `updated_by` int(11) DEFAULT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `skill_progress`
--

INSERT INTO `skill_progress` (`id`, `enrollment_id`, `student_id`, `skill_id`, `batch_id`, `session_id`, `topics_completed`, `total_topics`, `progress_percent`, `quiz_score`, `assignment_score`, `project_score`, `overall_performance`, `performance_level`, `remarks`, `status`, `updated_by`, `last_updated`, `created_at`) VALUES
(1, 1, 1, 1, 1, 1, 5, 9, 55.56, 20.00, 50.00, 70.00, 44.00, 'Beginner', '', 'Active', 3, '2026-01-29 10:23:49', '2026-01-29 10:23:49'),
(2, 3, 3, 9, 17, 1, 10, 10, 100.00, 100.00, 100.00, 100.00, 100.00, 'Excellent', '', 'Active', 4, '2026-02-09 11:53:19', '2026-02-09 06:27:52');

-- --------------------------------------------------------

--
-- Table structure for table `skill_syllabus`
--

CREATE TABLE `skill_syllabus` (
  `id` int(11) NOT NULL,
  `skill_id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `topic_title` varchar(255) NOT NULL,
  `topic_description` text DEFAULT NULL,
  `topic_order` int(11) DEFAULT 0,
  `duration_hours` int(11) DEFAULT 0,
  `learning_outcomes` text DEFAULT NULL,
  `prerequisites` text DEFAULT NULL,
  `resource_type` enum('PDF','DOC','PPT','Video','Link','Text') DEFAULT 'PDF',
  `file_path` varchar(500) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_size` varchar(20) DEFAULT NULL,
  `external_link` varchar(500) DEFAULT NULL,
  `content_text` text DEFAULT NULL,
  `status` enum('Active','Draft','Archived') DEFAULT 'Active',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `skill_syllabus`
--

INSERT INTO `skill_syllabus` (`id`, `skill_id`, `batch_id`, `topic_title`, `topic_description`, `topic_order`, `duration_hours`, `learning_outcomes`, `prerequisites`, `resource_type`, `file_path`, `file_name`, `file_size`, `external_link`, `content_text`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'HTML TAGS', 'IN this we will study about all the important tags usein html like p , h , a etc', 1, 30, 'After learning it student will we able to use or strat web development', 'no', 'PDF', '../uploads/syllabus/2026/01/697b3d0e512c9_job-application-form.pdf', '697b3d0e512c9_job-application-form.pdf', '310.47 KB', NULL, NULL, 'Active', 3, '2026-01-29 10:57:18', '2026-01-29 11:03:52'),
(4, 9, 18, 'cyber secrity basic', 'in this we will comes to know what is cyber secuirty', 1, 30, 'student will comes to you what is the cyber security', 'no', 'PDF', '../uploads/syllabus/2026/02/698983caa5d12_Return_Details_RTN-4_2026-01-07.pdf', '698983caa5d12_Return_Details_RTN-4_2026-01-07.pdf', '105.79 KB', NULL, NULL, 'Active', 3, '2026-02-09 06:50:50', '2026-02-09 06:50:50'),
(5, 9, 18, 'cyber secrity intermidate', 'student will knows about the cyber secuirty and infromation teachonlogy baoic', 2, 30, 'what is the information technology', 'cyber securtiy basic', 'PDF', '../uploads/syllabus/2026/02/6989844525f3a_job-application-form.pdf', '6989844525f3a_job-application-form.pdf', '310.47 KB', NULL, NULL, 'Active', 3, '2026-02-09 06:52:53', '2026-02-09 06:52:53'),
(6, 9, 17, 'cyber secrity basic', 'what is cyber secuirty', 1, 30, 'student will know about cyber secuirty basic', 'nothing', 'PDF', '../uploads/syllabus/2026/02/69898f2785a88_Return_Details_RTN-4_2026-01-07.pdf', '69898f2785a88_Return_Details_RTN-4_2026-01-07.pdf', '105.79 KB', NULL, NULL, 'Active', 3, '2026-02-09 07:39:19', '2026-02-09 07:39:19');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `student_code` varchar(50) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `father_name` varchar(255) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `user_id`, `student_code`, `name`, `father_name`, `gender`, `dob`, `phone`, `address`, `status`, `created_at`, `updated_at`) VALUES
(3, 6, 'STD-202601220006', 'ibad khan', 'Ahmed Khan', 'male', '2002-02-05', '0321452165', 'KOHAT', 'active', '2026-01-21 21:33:41', NULL),
(9, 48, 'STD-202602090048', 'hamad khan', 'saad khan', 'male', '2002-02-02', '03125469752', 'Billi tang', 'active', '2026-02-09 02:04:13', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `student_attendance`
--

CREATE TABLE `student_attendance` (
  `id` int(11) NOT NULL,
  `enrollment_id` int(11) DEFAULT NULL,
  `student_id` int(11) DEFAULT NULL,
  `skill_id` int(11) DEFAULT NULL,
  `session_id` int(11) DEFAULT NULL,
  `batch_id` int(11) DEFAULT NULL,
  `attendance_date` date DEFAULT NULL,
  `attendance_status` varchar(20) NOT NULL DEFAULT 'present',
  `attendance_percentage` decimal(5,2) DEFAULT NULL,
  `marked_by` int(11) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_attendance`
--

INSERT INTO `student_attendance` (`id`, `enrollment_id`, `student_id`, `skill_id`, `session_id`, `batch_id`, `attendance_date`, `attendance_status`, `attendance_percentage`, `marked_by`, `remarks`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 1, 1, '2026-01-21', 'present', 100.00, 1, '', 'active', '2026-01-21 03:28:50', '2026-01-21 03:36:22'),
(2, 3, 3, 9, 1, 17, '2026-01-22', 'present', 100.00, 1, '', 'active', '2026-01-21 21:34:35', NULL),
(6, 1, 1, 1, 1, 1, '2026-01-29', '0', 100.00, 3, '', 'active', '2026-01-29 01:44:28', NULL),
(7, 1, 1, 1, 1, 1, '2026-01-30', '0', 100.00, 3, '', 'active', '2026-01-30 03:37:51', NULL),
(8, 3, 3, 9, 1, 17, '2026-02-09', '0', 100.00, 4, '', 'active', '2026-02-09 02:34:48', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `student_enrollments`
--

CREATE TABLE `student_enrollments` (
  `id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `skill_id` int(11) DEFAULT NULL,
  `session_id` int(11) DEFAULT NULL,
  `batch_id` int(11) DEFAULT NULL,
  `admission_date` date DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_enrollments`
--

INSERT INTO `student_enrollments` (`id`, `student_id`, `skill_id`, `session_id`, `batch_id`, `admission_date`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 1, '2026-01-21', 'active', '2026-01-21 03:09:19', NULL),
(2, 2, 9, 1, 18, '2026-01-21', 'active', '2026-01-21 03:10:36', NULL),
(3, 3, 9, 1, 17, '2026-01-22', 'active', '2026-01-21 21:33:41', NULL),
(4, 3, 1, 1, 2, '2026-01-22', 'active', '2026-01-21 21:33:41', NULL),
(5, 9, 1, 1, 1, '2026-02-09', 'active', '2026-02-09 02:04:13', NULL),
(6, 9, 8, 1, 16, '2026-02-09', 'active', '2026-02-09 02:04:13', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `student_progress`
--

CREATE TABLE `student_progress` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `subject` varchar(100) DEFAULT NULL,
  `progress_score` decimal(5,2) DEFAULT 0.00,
  `attendance_percentage` decimal(5,2) DEFAULT 0.00,
  `quiz_score` decimal(5,2) DEFAULT 0.00,
  `assignment_score` decimal(5,2) DEFAULT 0.00,
  `overall_grade` char(1) DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `report_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_progress`
--

INSERT INTO `student_progress` (`id`, `student_id`, `batch_id`, `teacher_id`, `subject`, `progress_score`, `attendance_percentage`, `quiz_score`, `assignment_score`, `overall_grade`, `comments`, `report_date`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 3, NULL, 0.00, 0.00, 0.00, 0.00, 'D', '', '2026-01-30', '2026-01-30 10:11:05', '2026-01-30 10:20:35'),
(2, 3, 17, 3, NULL, 0.00, 0.00, 0.00, 0.00, 'A', 'good performance', '2026-02-08', '2026-02-09 06:27:32', '2026-02-09 06:27:32');

-- --------------------------------------------------------

--
-- Stand-in structure for view `student_syllabus_view`
-- (See below for the actual view)
--
CREATE TABLE `student_syllabus_view` (
`id` int(11)
,`skill_id` int(11)
,`batch_id` int(11)
,`topic_title` varchar(255)
,`topic_description` text
,`topic_order` int(11)
,`duration_hours` int(11)
,`learning_outcomes` text
,`prerequisites` text
,`resource_type` enum('PDF','DOC','PPT','Video','Link','Text')
,`file_path` varchar(500)
,`file_name` varchar(255)
,`file_size` varchar(20)
,`external_link` varchar(500)
,`content_text` text
,`status` enum('Active','Draft','Archived')
,`created_by` int(11)
,`created_at` timestamp
,`updated_at` timestamp
,`skill_name` varchar(255)
,`batch_name` varchar(255)
,`session_name` varchar(255)
,`teacher_name` varchar(255)
);

-- --------------------------------------------------------

--
-- Table structure for table `syllabus_history`
--

CREATE TABLE `syllabus_history` (
  `id` int(11) NOT NULL,
  `syllabus_id` int(11) NOT NULL,
  `skill_id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `topic_title` varchar(255) DEFAULT NULL,
  `changed_by` int(11) NOT NULL,
  `change_type` enum('Created','Updated','Archived','Restored') DEFAULT 'Updated',
  `change_description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `syllabus_history`
--

INSERT INTO `syllabus_history` (`id`, `syllabus_id`, `skill_id`, `batch_id`, `topic_title`, `changed_by`, `change_type`, `change_description`, `created_at`) VALUES
(1, 1, 1, 1, '0', 3, 'Created', 'New topic created: HTML TAGS', '2026-01-29 10:57:18'),
(2, 1, 1, 1, '0', 3, 'Archived', 'Topic archived: HTML TAGS', '2026-01-29 11:02:25'),
(3, 1, 1, 1, '0', 3, 'Restored', 'Topic restored: HTML TAGS', '2026-01-29 11:03:52'),
(4, 4, 9, 18, '0', 3, 'Created', 'New topic created: cyber secrity basic', '2026-02-09 06:50:50'),
(5, 5, 9, 18, '0', 3, 'Created', 'New topic created: cyber secrity intermidate', '2026-02-09 06:52:53'),
(6, 6, 9, 17, '0', 3, 'Created', 'New topic created: cyber secrity basic', '2026-02-09 07:39:19');

-- --------------------------------------------------------

--
-- Table structure for table `system_activities`
--

CREATE TABLE `system_activities` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `activity_type` varchar(50) NOT NULL COMMENT 'teacher_added, batch_created, student_enrolled, etc',
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `icon_color` varchar(20) DEFAULT '#3b82f6',
  `related_id` int(11) DEFAULT NULL,
  `related_type` varchar(50) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system_activities`
--

INSERT INTO `system_activities` (`id`, `user_id`, `activity_type`, `title`, `description`, `icon`, `icon_color`, `related_id`, `related_type`, `metadata`, `created_at`) VALUES
(1, 1, 'teacher_added', 'New Teacher Added', 'Teacher: sajid28', 'user-plus', '#10b981', NULL, NULL, NULL, '2026-01-21 22:18:54'),
(2, 1, 'batch_created', 'New Batch Created', 'Batch: Batch B for Web Development', 'calendar-plus', '#3b82f6', NULL, NULL, NULL, '2026-01-21 22:18:54'),
(3, 1, 'batch_created', 'New Batch Created', 'Batch: Batch A for Web Development', 'calendar-plus', '#3b82f6', NULL, NULL, NULL, '2026-01-21 22:18:54'),
(4, 1, 'student_enrolled', 'New Student Enrollment', 'ibad khan enrolled in Cyber Security', 'user-check', '#8b5cf6', NULL, NULL, NULL, '2026-01-21 21:18:54'),
(5, 1, 'login', 'Admin Logged In', 'System administrator logged in', 'log-in', '#059669', NULL, NULL, NULL, '2026-01-22 05:18:54'),
(6, 1, 'payment_received', 'Payment Received', 'Payment of ₹5,000 received from John Doe for Web Development course', 'credit-card', '#16a34a', NULL, NULL, NULL, '2026-01-22 03:18:54'),
(7, 1, 'assignment_submitted', 'Assignment Submitted', 'Sarah submitted \"Web Development Project 1\"', 'file-text', '#9333ea', NULL, NULL, NULL, '2026-01-22 02:18:54'),
(8, 1, 'course_created', 'New Course Created', 'Course: Data Science Fundamentals with Python', 'book-open', '#ea580c', NULL, NULL, NULL, '2026-01-22 01:18:54'),
(9, 1, 'exam_scheduled', 'Exam Scheduled', 'Mid-term exam scheduled for Batch A (Web Development)', 'calendar', '#dc2626', NULL, NULL, NULL, '2026-01-22 00:18:54'),
(10, 1, 'student_promoted', 'Student Promoted', 'Alex Johnson promoted to Advanced Web Development', 'trending-up', '#7c3aed', NULL, NULL, NULL, '2026-01-21 23:18:54');

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `teacher_code` varchar(50) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `qualification` varchar(255) DEFAULT NULL,
  `experience_years` int(11) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`id`, `user_id`, `teacher_code`, `name`, `gender`, `qualification`, `experience_years`, `phone`, `status`, `created_at`, `updated_at`) VALUES
(1, 4, 'TCH-202601220004', 'test teacher 1', NULL, 'MSc computer', 5, '000000-00000', 'inactive', '2026-01-21 21:19:54', '2026-02-08 22:15:02'),
(2, 5, 'TCH-202601220005', 'test teacher 2', NULL, 'Bs computer science', 3, '00000-00000', 'inactive', '2026-01-21 21:21:05', '2026-02-08 22:14:59'),
(3, 7, 'TCH-202601220007', 'Sajid Mehmood', NULL, 'Bs computer science', 5, '03177990549', 'active', '2026-01-21 22:52:24', NULL),
(4, 41, NULL, 'Izhan Sajid', NULL, 'M.Sc cyber secuirty', 5, '03214785693', 'active', NULL, NULL),
(5, 43, NULL, 'shayan shah', NULL, 'M.Sc computer science', 8, '0221369945', 'active', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `teacher_assignments`
--

CREATE TABLE `teacher_assignments` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `batch_id` int(11) DEFAULT NULL,
  `session_id` int(11) DEFAULT NULL,
  `assigned_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher_assignments`
--

INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `batch_id`, `session_id`, `assigned_date`) VALUES
(1, 3, 1, 1, '2026-01-28 22:02:08'),
(2, 3, 17, 1, '2026-02-08 21:41:19'),
(3, 3, 18, 1, '2026-02-08 21:42:55'),
(4, 4, 18, 1, '2026-02-09 02:05:45'),
(5, 4, 17, 1, '2026-02-09 02:05:54'),
(6, 5, 2, 1, '2026-02-09 02:13:50');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_attendance`
--

CREATE TABLE `teacher_attendance` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `attendance_date` date DEFAULT NULL,
  `attendance_status` varchar(50) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `marked_by` int(11) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher_attendance`
--

INSERT INTO `teacher_attendance` (`id`, `teacher_id`, `attendance_date`, `attendance_status`, `remarks`, `marked_by`, `status`, `created_at`, `updated_at`) VALUES
(1, 3, '2026-02-09', 'present', '', 1, 'active', '2026-02-08 23:04:28', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `teacher_performance`
--

CREATE TABLE `teacher_performance` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `month_year` varchar(7) DEFAULT NULL,
  `total_batches` int(11) DEFAULT 0,
  `total_students` int(11) DEFAULT 0,
  `avg_attendance` decimal(5,2) DEFAULT 0.00,
  `avg_quiz_score` decimal(5,2) DEFAULT 0.00,
  `materials_uploaded` int(11) DEFAULT 0,
  `quizzes_created` int(11) DEFAULT 0,
  `performance_score` decimal(5,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teacher_preferences`
--

CREATE TABLE `teacher_preferences` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `email_notifications` tinyint(1) DEFAULT 1,
  `progress_alerts` tinyint(1) DEFAULT 1,
  `assignment_alerts` tinyint(1) DEFAULT 1,
  `weekly_reports` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `teacher_preferences`
--

INSERT INTO `teacher_preferences` (`id`, `teacher_id`, `email_notifications`, `progress_alerts`, `assignment_alerts`, `weekly_reports`, `created_at`, `updated_at`) VALUES
(1, 3, 1, 1, 1, 1, '2026-01-30 10:49:30', '2026-01-30 10:49:30');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_privacy`
--

CREATE TABLE `teacher_privacy` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `profile_visibility` enum('public','students_only','private') DEFAULT 'public',
  `contact_visibility` enum('students_only','private') DEFAULT 'students_only',
  `show_online_status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teacher_system_preferences`
--

CREATE TABLE `teacher_system_preferences` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `theme` enum('light','dark','auto') DEFAULT 'light',
  `language` varchar(10) DEFAULT 'en',
  `timezone` varchar(50) DEFAULT 'UTC',
  `date_format` varchar(20) DEFAULT 'Y-m-d',
  `items_per_page` int(11) DEFAULT 10,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teaching_materials`
--

CREATE TABLE `teaching_materials` (
  `id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(50) DEFAULT 'General',
  `file_path` varchar(500) DEFAULT NULL,
  `file_type` enum('pdf','docx','ppt','video','image','zip','other') DEFAULT 'other',
  `file_size` varchar(20) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('active','inactive','archived') DEFAULT 'active',
  `download_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `teaching_materials`
--

INSERT INTO `teaching_materials` (`id`, `batch_id`, `teacher_id`, `title`, `description`, `category`, `file_path`, `file_type`, `file_size`, `uploaded_at`, `updated_at`, `status`, `download_count`) VALUES
(1, 1, 3, 'chapter 1', 'in this you will learn html', 'Lecture 1', '../uploads/materials/697c9ce069b6a_ChatGPTImageJan28202603_49_52AM.png', 'image', '2.19 MB', '2026-01-30 07:51:56', '2026-01-30 11:58:24', 'active', 0),
(2, 18, 3, 'Chapter 1', 'this is the basic of cyber security', 'Lecture 1', '../uploads/materials/698981f6a9757_Medicine_Allergex_2025-12-31.pdf', 'pdf', '4.89 KB', '2026-02-09 06:43:02', '2026-02-09 06:43:02', 'active', 0);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `user_type_id` int(11) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `user_type_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@academy.com', 'admin123', 1, 'active', '2026-01-21 02:24:59', '2026-01-21 02:24:59'),
(2, 'test1', 'test@gmail.com', '123', 3, 'active', '2026-01-21 03:09:19', '2026-01-21 03:10:56'),
(3, 'test2', 'test2@gmail.com', '123', 3, 'active', '2026-01-21 03:10:36', NULL),
(4, 'testteacher1', 'testteacher.1@eduskillpro.com', '123', 2, 'inactive', '2026-01-21 21:19:54', '2026-02-08 22:15:02'),
(5, 'testteacher2', 'testteacher2.@eduskillpro.com', '123', 2, 'inactive', '2026-01-21 21:21:05', '2026-02-08 22:14:59'),
(6, 'ibad', 'ibad@gmail.com', '123', 3, 'active', '2026-01-21 21:33:41', NULL),
(7, 'sajid28', 'sajid.mehmood28@eduskillpro.com', '123', 2, 'active', '2026-01-21 22:52:24', NULL),
(29, 'Aslam', 'aslam@gmail.com', '123', 3, 'active', '2026-02-09 01:34:05', NULL),
(41, 'izhan', 'izhan@gmail.com', '123', 2, 'active', NULL, NULL),
(43, 'shani123', 'sshah326@academy.edu', '$2y$10$cMlRi4iH3NWGN5SpjYOoSepnQUXKsmCzGQVstfsG28uFJ4JDzvEri', 2, 'active', NULL, NULL),
(48, 'Hammad', 'hak123@gmail.com', '123', 3, 'active', '2026-02-09 02:04:13', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_types`
--

CREATE TABLE `user_types` (
  `id` int(11) NOT NULL,
  `type_name` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_types`
--

INSERT INTO `user_types` (`id`, `type_name`) VALUES
(1, 'Admin'),
(2, 'Teacher'),
(3, 'Student');

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_recent_activities`
-- (See below for the actual view)
--
CREATE TABLE `view_recent_activities` (
);

-- --------------------------------------------------------

--
-- Structure for view `student_syllabus_view`
--
DROP TABLE IF EXISTS `student_syllabus_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `student_syllabus_view`  AS SELECT `ss`.`id` AS `id`, `ss`.`skill_id` AS `skill_id`, `ss`.`batch_id` AS `batch_id`, `ss`.`topic_title` AS `topic_title`, `ss`.`topic_description` AS `topic_description`, `ss`.`topic_order` AS `topic_order`, `ss`.`duration_hours` AS `duration_hours`, `ss`.`learning_outcomes` AS `learning_outcomes`, `ss`.`prerequisites` AS `prerequisites`, `ss`.`resource_type` AS `resource_type`, `ss`.`file_path` AS `file_path`, `ss`.`file_name` AS `file_name`, `ss`.`file_size` AS `file_size`, `ss`.`external_link` AS `external_link`, `ss`.`content_text` AS `content_text`, `ss`.`status` AS `status`, `ss`.`created_by` AS `created_by`, `ss`.`created_at` AS `created_at`, `ss`.`updated_at` AS `updated_at`, `s`.`skill_name` AS `skill_name`, `b`.`batch_name` AS `batch_name`, `se`.`session_name` AS `session_name`, `t`.`name` AS `teacher_name` FROM ((((`skill_syllabus` `ss` join `skills` `s` on(`ss`.`skill_id` = `s`.`id`)) join `batches` `b` on(`ss`.`batch_id` = `b`.`id`)) join `sessions` `se` on(`b`.`session_id` = `se`.`id`)) left join `teachers` `t` on(`ss`.`created_by` = `t`.`id`)) WHERE `ss`.`status` = 'Active' ;

-- --------------------------------------------------------

--
-- Structure for view `view_recent_activities`
--
DROP TABLE IF EXISTS `view_recent_activities`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_recent_activities`  AS SELECT `sa`.`id` AS `id`, `sa`.`activity_type` AS `activity_type`, `sa`.`title` AS `title`, `sa`.`description` AS `description`, `sa`.`user_id` AS `user_id`, `sa`.`related_id` AS `related_id`, `sa`.`related_table` AS `related_table`, `sa`.`is_read` AS `is_read`, `sa`.`created_at` AS `created_at`, `u`.`username` AS `username`, `u`.`username` AS `user_name`, `ut`.`type_name` AS `user_type` FROM ((`system_activities` `sa` left join `users` `u` on(`sa`.`user_id` = `u`.`id`)) left join `user_types` `ut` on(`u`.`user_type_id` = `ut`.`id`)) ORDER BY `sa`.`created_at` DESC LIMIT 0, 100 ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_expires_at` (`expires_at`),
  ADD KEY `idx_is_expired` (`is_expired`),
  ADD KEY `idx_status_expired` (`status`,`is_expired`);

--
-- Indexes for table `batches`
--
ALTER TABLE `batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `batch_performance`
--
ALTER TABLE `batch_performance`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `batch_teachers`
--
ALTER TABLE `batch_teachers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `certificates`
--
ALTER TABLE `certificates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `certificate_number` (`certificate_number`),
  ADD UNIQUE KEY `verification_code` (`verification_code`),
  ADD KEY `idx_verification` (`verification_code`),
  ADD KEY `idx_student` (`student_id`);

--
-- Indexes for table `certificate_audit_log`
--
ALTER TABLE `certificate_audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_certificate` (`certificate_id`),
  ADD KEY `idx_student` (`student_id`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `donations`
--
ALTER TABLE `donations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `expense_categories`
--
ALTER TABLE `expense_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `fee_collections`
--
ALTER TABLE `fee_collections`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `fee_structures`
--
ALTER TABLE `fee_structures`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `institute_info`
--
ALTER TABLE `institute_info`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `material_categories`
--
ALTER TABLE `material_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `material_downloads`
--
ALTER TABLE `material_downloads`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_download` (`material_id`,`student_id`);

--
-- Indexes for table `monthly_profit`
--
ALTER TABLE `monthly_profit`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `progress_history`
--
ALTER TABLE `progress_history`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `quizzes`
--
ALTER TABLE `quizzes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `batch_id` (`batch_id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `quiz_question_marks`
--
ALTER TABLE `quiz_question_marks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_result_question` (`result_id`,`question_id`);

--
-- Indexes for table `quiz_results`
--
ALTER TABLE `quiz_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quiz_id` (`quiz_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `batch_id` (`batch_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `skills`
--
ALTER TABLE `skills`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `skill_progress`
--
ALTER TABLE `skill_progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_progress` (`enrollment_id`,`skill_id`);

--
-- Indexes for table `skill_syllabus`
--
ALTER TABLE `skill_syllabus`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student_attendance`
--
ALTER TABLE `student_attendance`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student_enrollments`
--
ALTER TABLE `student_enrollments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student_progress`
--
ALTER TABLE `student_progress`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `syllabus_history`
--
ALTER TABLE `syllabus_history`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `system_activities`
--
ALTER TABLE `system_activities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_activity_type` (`activity_type`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `teacher_assignments`
--
ALTER TABLE `teacher_assignments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `teacher_attendance`
--
ALTER TABLE `teacher_attendance`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `teacher_performance`
--
ALTER TABLE `teacher_performance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_teacher_month` (`teacher_id`,`month_year`);

--
-- Indexes for table `teacher_preferences`
--
ALTER TABLE `teacher_preferences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_teacher` (`teacher_id`);

--
-- Indexes for table `teacher_privacy`
--
ALTER TABLE `teacher_privacy`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_teacher` (`teacher_id`);

--
-- Indexes for table `teacher_system_preferences`
--
ALTER TABLE `teacher_system_preferences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_teacher` (`teacher_id`);

--
-- Indexes for table `teaching_materials`
--
ALTER TABLE `teaching_materials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `batch_id` (`batch_id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_types`
--
ALTER TABLE `user_types`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `batches`
--
ALTER TABLE `batches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `batch_performance`
--
ALTER TABLE `batch_performance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `batch_teachers`
--
ALTER TABLE `batch_teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `certificates`
--
ALTER TABLE `certificates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `certificate_audit_log`
--
ALTER TABLE `certificate_audit_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `donations`
--
ALTER TABLE `donations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `expense_categories`
--
ALTER TABLE `expense_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fee_collections`
--
ALTER TABLE `fee_collections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `fee_structures`
--
ALTER TABLE `fee_structures`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `institute_info`
--
ALTER TABLE `institute_info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `material_categories`
--
ALTER TABLE `material_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `material_downloads`
--
ALTER TABLE `material_downloads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `monthly_profit`
--
ALTER TABLE `monthly_profit`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `progress_history`
--
ALTER TABLE `progress_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `quizzes`
--
ALTER TABLE `quizzes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `quiz_question_marks`
--
ALTER TABLE `quiz_question_marks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quiz_results`
--
ALTER TABLE `quiz_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sessions`
--
ALTER TABLE `sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `skills`
--
ALTER TABLE `skills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `skill_progress`
--
ALTER TABLE `skill_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `skill_syllabus`
--
ALTER TABLE `skill_syllabus`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `student_attendance`
--
ALTER TABLE `student_attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `student_enrollments`
--
ALTER TABLE `student_enrollments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `student_progress`
--
ALTER TABLE `student_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `syllabus_history`
--
ALTER TABLE `syllabus_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `system_activities`
--
ALTER TABLE `system_activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `teacher_assignments`
--
ALTER TABLE `teacher_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `teacher_attendance`
--
ALTER TABLE `teacher_attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `teacher_performance`
--
ALTER TABLE `teacher_performance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `teacher_preferences`
--
ALTER TABLE `teacher_preferences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `teacher_privacy`
--
ALTER TABLE `teacher_privacy`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `teacher_system_preferences`
--
ALTER TABLE `teacher_system_preferences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `teaching_materials`
--
ALTER TABLE `teaching_materials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `user_types`
--
ALTER TABLE `user_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `quizzes`
--
ALTER TABLE `quizzes`
  ADD CONSTRAINT `quizzes_ibfk_1` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`id`),
  ADD CONSTRAINT `quizzes_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`);

--
-- Constraints for table `quiz_results`
--
ALTER TABLE `quiz_results`
  ADD CONSTRAINT `quiz_results_ibfk_1` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`),
  ADD CONSTRAINT `quiz_results_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
  ADD CONSTRAINT `quiz_results_ibfk_3` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`id`);

--
-- Constraints for table `teaching_materials`
--
ALTER TABLE `teaching_materials`
  ADD CONSTRAINT `teaching_materials_ibfk_1` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`id`),
  ADD CONSTRAINT `teaching_materials_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`);

DELIMITER $$
--
-- Events
--
CREATE DEFINER=`root`@`localhost` EVENT `daily_announcement_expiry` ON SCHEDULE EVERY 1 DAY STARTS '2026-01-22 01:20:20' ON COMPLETION NOT PRESERVE ENABLE DO CALL check_announcement_expiration()$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

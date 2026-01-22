-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 22, 2026 at 10:29 AM
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
-- Table structure for table `activities`
--

CREATE TABLE `activities` (
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
-- Dumping data for table `activities`
--

INSERT INTO `activities` (`id`, `user_id`, `activity_type`, `title`, `description`, `icon`, `icon_color`, `related_id`, `related_type`, `metadata`, `created_at`) VALUES
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
(1, 'System Maintenance This Weekend', 'Our system will undergo scheduled maintenance on Saturday, Dec 15th from 10:00 PM to 2:00 AM. The portal will be temporarily unavailable during this time. Please complete any urgent tasks before the maintenance window.', 'all', 1, '2026-01-21 22:11:50', 'active', '2026-01-22 01:20:34', '2026-02-01 01:20:44', '0000-00-00 00:00:00', 0, 'high', 1, '2026-01-22 09:29:43');

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
(20, 10, 1, 'Batch B', '14:00:00', '16:00:00', 25, 'active', '2026-01-22 06:28:57', '2026-01-22 06:28:57');

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
  `remarks` text DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(10, 10, 1, 5000.00, 'active', '2026-01-21 02:20:31', '2026-01-21 02:20:31');

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
(5, '2030–2031', 'active', '2026-01-21 02:23:31', '2026-01-21 02:23:31');

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
(10, 'Mobile App Development', 3, 'Beginner', 'Learn to create apps for Android and iOS', 1, 1, 'active', '2026-01-21 02:19:36', '2026-01-21 02:19:36');

-- --------------------------------------------------------

--
-- Table structure for table `skill_progress`
--

CREATE TABLE `skill_progress` (
  `id` int(11) NOT NULL,
  `enrollment_id` int(11) DEFAULT NULL,
  `session_id` int(11) DEFAULT NULL,
  `topics_completed` int(11) DEFAULT NULL,
  `total_topics` int(11) DEFAULT NULL,
  `completion_percent` decimal(5,2) DEFAULT NULL,
  `last_updated` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `skill_syllabus`
--

CREATE TABLE `skill_syllabus` (
  `id` int(11) NOT NULL,
  `skill_id` int(11) DEFAULT NULL,
  `topic_title` varchar(255) DEFAULT NULL,
  `topic_order` int(11) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(1, 2, 'STD-202601210002', 'test test', 'test father', 'male', '2002-02-06', '0000000000', 'Billi tang', 'active', '2026-01-21 03:09:19', '2026-01-21 03:10:56'),
(2, 3, 'STD-202601210003', 'Test 2', 'test2 father', 'male', '2002-07-24', '0000-0000-0000', 'karachi', 'active', '2026-01-21 03:10:36', NULL),
(3, 6, 'STD-202601220006', 'ibad khan', 'Ahmed Khan', 'male', '2002-02-05', '0321452165', 'KOHAT', 'active', '2026-01-21 21:33:41', NULL);

--
-- Triggers `students`
--
DELIMITER $$
CREATE TRIGGER `after_student_insert` AFTER INSERT ON `students` FOR EACH ROW BEGIN
    -- Insert into activity_logs
    INSERT INTO activity_logs (user_id, action, module, table_name, record_id, details, operation, new_data)
    VALUES (NEW.user_id, 'create', 'student', 'students', NEW.id, 
            CONCAT('Student created: ', NEW.name, ' (', NEW.student_code, ')'), 
            'INSERT',
            JSON_OBJECT('name', NEW.name, 'student_code', NEW.student_code, 'phone', NEW.phone));
    
    -- Insert into system_activities
    INSERT INTO system_activities (activity_type, title, description, user_id, related_id, related_table)
    VALUES ('enrollment', 'New Student Enrollment', 
            CONCAT(NEW.name, ' enrolled'), 
            NEW.user_id, NEW.id, 'students');
END
$$
DELIMITER ;

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
(2, 3, 3, 9, 1, 17, '2026-01-22', 'present', 100.00, 1, '', 'active', '2026-01-21 21:34:35', NULL);

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
(4, 3, 1, 1, 2, '2026-01-22', 'active', '2026-01-21 21:33:41', NULL);

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
(1, 4, 'TCH-202601220004', 'test teacher 1', NULL, 'MSc computer', 5, '000000-00000', 'active', '2026-01-21 21:19:54', NULL),
(2, 5, 'TCH-202601220005', 'test teacher 2', NULL, 'Bs computer science', 3, '00000-00000', 'active', '2026-01-21 21:21:05', NULL),
(3, 7, 'TCH-202601220007', 'Sajid Mehmood', NULL, 'Bs computer science', 5, '03177990549', 'active', '2026-01-21 22:52:24', NULL);

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
(4, 'testteacher1', 'testteacher.1@eduskillpro.com', '123', 2, 'active', '2026-01-21 21:19:54', NULL),
(5, 'testteacher2', 'testteacher2.@eduskillpro.com', '123', 2, 'active', '2026-01-21 21:21:05', NULL),
(6, 'ibad', 'ibad@gmail.com', '123', 3, 'active', '2026-01-21 21:33:41', NULL),
(7, 'sajid28', 'sajid.mehmood28@eduskillpro.com', '123', 2, 'active', '2026-01-21 22:52:24', NULL);

--
-- Triggers `users`
--
DELIMITER $$
CREATE TRIGGER `after_teacher_insert` AFTER INSERT ON `users` FOR EACH ROW BEGIN
    IF NEW.user_type_id = 2 THEN
        INSERT INTO system_activities (activity_type, title, description, user_id, related_id, related_table)
        VALUES ('teacher_added', 'New Teacher Added', 
                CONCAT('Teacher: ', NEW.username),  -- Using username instead of name
                NEW.id, NEW.id, 'users');
    END IF;
END
$$
DELIMITER ;

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
-- Structure for view `view_recent_activities`
--
DROP TABLE IF EXISTS `view_recent_activities`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_recent_activities`  AS SELECT `sa`.`id` AS `id`, `sa`.`activity_type` AS `activity_type`, `sa`.`title` AS `title`, `sa`.`description` AS `description`, `sa`.`user_id` AS `user_id`, `sa`.`related_id` AS `related_id`, `sa`.`related_table` AS `related_table`, `sa`.`is_read` AS `is_read`, `sa`.`created_at` AS `created_at`, `u`.`username` AS `username`, `u`.`username` AS `user_name`, `ut`.`type_name` AS `user_type` FROM ((`system_activities` `sa` left join `users` `u` on(`sa`.`user_id` = `u`.`id`)) left join `user_types` `ut` on(`u`.`user_type_id` = `ut`.`id`)) ORDER BY `sa`.`created_at` DESC LIMIT 0, 100 ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activities`
--
ALTER TABLE `activities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_activity_type` (`activity_type`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_user_id` (`user_id`);

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
-- Indexes for table `batch_teachers`
--
ALTER TABLE `batch_teachers`
  ADD PRIMARY KEY (`id`);

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
-- Indexes for table `monthly_profit`
--
ALTER TABLE `monthly_profit`
  ADD PRIMARY KEY (`id`);

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
  ADD PRIMARY KEY (`id`);

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
-- AUTO_INCREMENT for table `activities`
--
ALTER TABLE `activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `batches`
--
ALTER TABLE `batches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `batch_teachers`
--
ALTER TABLE `batch_teachers`
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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fee_structures`
--
ALTER TABLE `fee_structures`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `monthly_profit`
--
ALTER TABLE `monthly_profit`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sessions`
--
ALTER TABLE `sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `skills`
--
ALTER TABLE `skills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `skill_progress`
--
ALTER TABLE `skill_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `skill_syllabus`
--
ALTER TABLE `skill_syllabus`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `student_attendance`
--
ALTER TABLE `student_attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `student_enrollments`
--
ALTER TABLE `student_enrollments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `teacher_assignments`
--
ALTER TABLE `teacher_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `teacher_attendance`
--
ALTER TABLE `teacher_attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `user_types`
--
ALTER TABLE `user_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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

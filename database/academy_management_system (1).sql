-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 20, 2026 at 12:46 PM
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

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` text DEFAULT NULL,
  `session_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(200) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `target_role` enum('admin','teacher','student','all') DEFAULT NULL,
  `session_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `batches`
--

CREATE TABLE `batches` (
  `id` int(11) NOT NULL,
  `skill_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `batch_name` varchar(100) NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `max_students` int(11) DEFAULT NULL,
  `status` enum('active','completed') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `batches`
--

INSERT INTO `batches` (`id`, `skill_id`, `session_id`, `batch_name`, `start_time`, `end_time`, `max_students`, `status`) VALUES
(1, 1, 1, 'Batch A - Web Development', '10:00:00', '12:00:00', 20, 'active'),
(2, 1, 1, 'Batch B - Web Development', '14:00:00', '16:00:00', 20, 'active'),
(3, 2, 1, 'Batch A - Python Programming', '12:00:00', '14:00:00', 25, 'active'),
(4, 2, 2, 'Batch B - Python Programming', '15:00:00', '17:00:00', 25, 'active'),
(5, 3, 1, 'Batch A - Graphic Design', '10:00:00', '12:00:00', 30, 'active'),
(6, 3, 2, 'Batch B - Graphic Design', '13:00:00', '15:00:00', 30, 'active'),
(7, 4, 1, 'Batch A - Digital Marketing', '11:00:00', '13:00:00', 25, 'active'),
(8, 5, 2, 'Batch A - Data Science', '10:00:00', '12:00:00', 20, 'active'),
(9, 5, 2, 'Batch B - Data Science', '13:00:00', '15:00:00', 20, 'active'),
(10, 6, 2, 'Batch A - Machine Learning', '14:00:00', '16:00:00', 20, 'active'),
(11, 7, 2, 'Batch A - Gen AI', '10:00:00', '12:00:00', 20, 'active'),
(12, 8, 1, 'Batch A - UI/UX Design', '12:00:00', '14:00:00', 20, 'active'),
(13, 9, 2, 'Batch A - Cyber Security', '10:00:00', '12:00:00', 20, 'active'),
(14, 10, 2, 'Batch A - Blockchain Basics', '13:00:00', '15:00:00', 20, 'active');

-- --------------------------------------------------------

--
-- Table structure for table `batch_teachers`
--

CREATE TABLE `batch_teachers` (
  `id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `assigned_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `status` enum('new','read','replied') DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `donations`
--

CREATE TABLE `donations` (
  `id` int(11) NOT NULL,
  `donor_name` varchar(255) NOT NULL,
  `donor_type` enum('organization','individual') DEFAULT 'organization',
  `contact_person` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `donation_date` date NOT NULL,
  `payment_method` enum('cash','bank','online','cheque') DEFAULT 'cash',
  `reference_no` varchar(100) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `donations`
--

INSERT INTO `donations` (`id`, `donor_name`, `donor_type`, `contact_person`, `phone`, `email`, `amount`, `donation_date`, `payment_method`, `reference_no`, `remarks`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Tech Foundation Pakistan', 'organization', 'Ali Raza', '0301-1234567', 'contact@techfoundation.pk', 150000.00, '2026-01-05', 'bank', 'TFP-2026-001', 'Donation for lab equipment and computers', 'active', '2026-01-20 10:47:43', '2026-01-20 10:47:43'),
(2, 'Education Support Trust', 'organization', 'Sara Khan', '0322-9876543', 'info@edusupport.org', 100000.00, '2026-01-12', 'cheque', 'EST-CHQ-109', 'Support for underprivileged students', 'active', '2026-01-20 10:47:43', '2026-01-20 10:47:43'),
(3, 'Ahmed Hassan', 'individual', 'Ahmed Hassan', '0333-4567890', 'ahmed.hassan@gmail.com', 25000.00, '2026-01-15', 'cash', 'CASH-001', 'Personal donation for academy growth', 'active', '2026-01-20 10:47:43', '2026-01-20 10:47:43'),
(4, 'Fatima Noor', 'individual', 'Fatima Noor', '0345-1122334', 'fatima.noor@gmail.com', 40000.00, '2026-01-18', 'online', 'ONL-FTM-778', 'Donation for student scholarships', 'active', '2026-01-20 10:47:43', '2026-01-20 10:47:43'),
(5, 'Global IT Solutions', 'organization', 'izhan', '', '', 200000.00, '2026-01-20', 'cash', '', 'CSR donation for skill development programs', 'active', '2026-01-20 10:47:43', '2026-01-20 11:00:23'),
(8, 'Zahid', 'individual', 'zahid', '03214785693', 'pandazahid334@gmail.com', 100000.00, '2026-01-20', 'cash', '123522456', 'student  study trip', 'active', '2026-01-20 11:04:47', '2026-01-20 11:05:11');

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`id`, `category_id`, `description`, `amount`, `status`, `created_at`, `updated_at`) VALUES
(1, 6, 'Fuel for academy vehicles', 2000.00, 'active', '2026-01-05 10:00:00', '2026-01-05 10:00:00'),
(2, 6, 'Vehicle maintenance', 1500.00, 'active', '2026-01-15 10:00:00', '2026-01-15 10:00:00'),
(3, 7, 'Stationery and printer paper', 800.00, 'active', '2026-01-08 09:30:00', '2026-01-08 09:30:00'),
(4, 7, 'Cleaning supplies', 500.00, 'active', '2026-01-12 11:00:00', '2026-01-12 11:00:00'),
(5, 8, 'Monthly rent for academy', 10000.00, 'active', '2026-01-01 08:00:00', '2026-01-01 08:00:00'),
(6, 9, 'January salary - Teachers', 50000.00, 'active', '2026-01-20 12:00:00', '2026-01-20 12:00:00'),
(7, 9, 'January salary - Staff', 14000.00, 'active', '2026-01-20 12:30:00', '2026-01-20 02:00:04'),
(8, 10, 'Electricity bill', 4000.00, 'active', '2026-01-10 09:00:00', '2026-01-10 09:00:00'),
(9, 10, 'Internet bill', 2000.00, 'active', '2026-01-05 09:00:00', '2026-01-05 09:00:00'),
(10, 11, 'Birthday celebration snacks', 1000.00, 'active', '2026-01-18 15:00:00', '2026-01-18 15:00:00'),
(11, 11, 'Emergency repair', 2500.00, 'active', '2026-01-16 14:00:00', '2026-01-16 14:00:00'),
(12, 7, 'use for the taking tools', 1000.00, 'active', '2026-01-20 01:59:26', '2026-01-20 01:59:26');

-- --------------------------------------------------------

--
-- Table structure for table `expense_categories`
--

CREATE TABLE `expense_categories` (
  `id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `expense_categories`
--

INSERT INTO `expense_categories` (`id`, `category_name`, `description`, `status`, `created_at`, `updated_at`) VALUES
(6, 'Transport', 'Transport costs for staff and operations', 'active', '2026-01-20 01:37:23', '2026-01-20 01:37:23'),
(7, 'Daily Uses', 'Office supplies and daily use items', 'active', '2026-01-20 01:37:23', '2026-01-20 02:29:44'),
(8, 'Rent', 'Academy building rent', 'active', '2026-01-20 01:37:23', '2026-01-20 01:37:23'),
(9, 'Salaries', 'Salaries for teachers and staff', 'active', '2026-01-20 01:37:23', '2026-01-20 01:37:23'),
(10, 'Utilities', 'Electricity, water, internet bills', 'active', '2026-01-20 01:37:23', '2026-01-20 01:37:23'),
(11, 'Miscellaneous', 'Other expenses not categorized above', 'active', '2026-01-20 01:37:23', '2026-01-20 01:37:23');

-- --------------------------------------------------------

--
-- Table structure for table `fee_collections`
--

CREATE TABLE `fee_collections` (
  `id` int(11) NOT NULL,
  `enrollment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `skill_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL,
  `payment_date` date DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fee_collections`
--

INSERT INTO `fee_collections` (`id`, `enrollment_id`, `student_id`, `skill_id`, `session_id`, `batch_id`, `amount_paid`, `payment_date`, `payment_method`, `remarks`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 1, 1, 5000.00, '2026-01-20', 'Cash', 'Full payment', 'active', '2026-01-20 01:25:17', '2026-01-20 01:25:17'),
(2, 2, 2, 2, 1, 2, 5000.00, '2026-01-20', 'Bank Transfer', 'Full payment', 'active', '2026-01-20 01:25:17', '2026-01-20 01:25:17'),
(3, 3, 3, 3, 1, 3, 5000.00, '2026-01-20', 'Cash', 'Full payment', 'active', '2026-01-20 01:25:17', '2026-01-20 01:25:17');

-- --------------------------------------------------------

--
-- Table structure for table `fee_structures`
--

CREATE TABLE `fee_structures` (
  `id` int(11) NOT NULL,
  `skill_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `total_fee` decimal(10,2) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fee_structures`
--

INSERT INTO `fee_structures` (`id`, `skill_id`, `session_id`, `total_fee`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 5000.00, 'active', '2026-01-18 22:51:41', '2026-01-18 22:51:41'),
(2, 2, 1, 4000.00, 'active', '2026-01-18 22:51:41', '2026-01-18 22:51:41'),
(3, 3, 1, 3000.00, 'active', '2026-01-18 22:51:41', '2026-01-18 22:51:41'),
(4, 4, 1, 3500.00, 'active', '2026-01-18 22:51:41', '2026-01-18 22:51:41'),
(5, 5, 1, 6000.00, 'active', '2026-01-18 22:51:41', '2026-01-18 22:51:41'),
(6, 6, 2, 6500.00, 'active', '2026-01-18 22:51:41', '2026-01-18 23:40:54'),
(7, 7, 2, 6000.00, 'active', '2026-01-18 22:51:41', '2026-01-18 22:52:01'),
(8, 8, 2, 7000.00, 'inactive', '2026-01-18 22:52:56', '2026-01-18 22:53:04'),
(9, 8, 2, 5000.00, 'active', '2026-01-18 23:41:12', '2026-01-18 23:41:12');

-- --------------------------------------------------------

--
-- Table structure for table `monthly_profit`
--

CREATE TABLE `monthly_profit` (
  `id` int(11) NOT NULL,
  `month_year` date NOT NULL,
  `profit_month` tinyint(4) NOT NULL,
  `profit_year` year(4) NOT NULL,
  `total_fees` decimal(10,2) DEFAULT 0.00,
  `total_donations` decimal(10,2) DEFAULT 0.00,
  `total_expenses` decimal(10,2) DEFAULT 0.00,
  `net_profit` decimal(10,2) NOT NULL,
  `remarks` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `monthly_profit`
--

INSERT INTO `monthly_profit` (`id`, `month_year`, `profit_month`, `profit_year`, `total_fees`, `total_donations`, `total_expenses`, `net_profit`, `remarks`, `status`, `created_at`, `updated_at`) VALUES
(1, '2026-01-01', 1, '2026', 1180000.00, 23140000.00, 1657800.00, 22662200.00, 'Auto-calculated for current month |  |  |  |  |', 'active', '2026-01-20 03:39:35', '2026-01-20 03:42:26');

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` int(11) NOT NULL,
  `session_name` varchar(50) NOT NULL,
  `status` enum('active','completed') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `session_name`, `status`) VALUES
(1, 'Session 2025', 'completed'),
(2, 'Session 2026', 'active'),
(3, 'Session 2027', 'active'),
(4, 'Session 2028', 'active'),
(5, 'Session 2029', 'active'),
(6, 'Session 2030', 'active'),
(7, 'Session 2031', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `skills`
--

CREATE TABLE `skills` (
  `id` int(11) NOT NULL,
  `skill_name` varchar(150) NOT NULL,
  `duration_months` int(11) NOT NULL,
  `level` enum('basic','intermediate','advanced') DEFAULT 'basic',
  `description` text DEFAULT NULL,
  `has_syllabus` tinyint(1) DEFAULT 1,
  `has_practice` tinyint(1) DEFAULT 1,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `skills`
--

INSERT INTO `skills` (`id`, `skill_name`, `duration_months`, `level`, `description`, `has_syllabus`, `has_practice`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Web Development', 6, 'basic', 'Learn HTML, CSS, JS and build websites', 1, 1, 'active', '2026-01-16 03:07:17', '2026-01-16 03:07:17'),
(2, 'Python Programming', 4, 'intermediate', 'Learn Python fundamentals and basic projects', 1, 1, 'active', '2026-01-16 02:59:30', '2026-01-16 03:06:06'),
(3, 'Graphic Design', 3, 'intermediate', 'Learn Photoshop, Illustrator and design concepts', 1, 1, 'active', '2026-01-16 02:59:30', '2026-01-16 03:06:06'),
(4, 'Digital Marketing', 5, 'intermediate', 'Learn SEO, Ads, Social Media marketing', 1, 0, 'active', '2026-01-16 02:59:30', '2026-01-16 03:06:06'),
(5, 'Data Science', 6, 'intermediate', 'Learn Python, Statistics, ML, Data Analysis', 1, 1, 'active', '2026-01-16 02:59:30', '2026-01-16 03:06:06'),
(6, 'Machine learning', 6, 'intermediate', 'leran the basic concept of machince learning', 1, 0, 'active', '2026-01-16 02:59:30', '2026-01-16 03:06:06'),
(7, 'Gen Ai', 6, 'intermediate', 'learn the basic of gen Ai', 0, 0, 'active', '2026-01-16 02:59:30', '2026-01-16 03:06:06'),
(8, 'UI/UX Design', 4, 'advanced', 'Learn user interface and user experience design principles', 1, 1, 'active', '2026-01-19 23:49:35', '2026-01-19 23:49:35'),
(9, 'Cyber Security', 5, 'intermediate', 'Learn basic cybersecurity, ethical hacking, and protection methods', 1, 0, 'active', '2026-01-18 22:52:43', '2026-01-18 22:52:43'),
(10, 'Blockchain Basics', 6, 'intermediate', 'Introduction to blockchain technology, crypto, and smart contracts', 1, 0, 'active', '2026-01-18 22:52:43', '2026-01-18 22:52:43');

-- --------------------------------------------------------

--
-- Table structure for table `skill_progress`
--

CREATE TABLE `skill_progress` (
  `id` int(11) NOT NULL,
  `enrollment_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `topics_completed` int(11) DEFAULT 0,
  `total_topics` int(11) DEFAULT 0,
  `completion_percent` decimal(5,2) DEFAULT 0.00,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `skill_syllabus`
--

CREATE TABLE `skill_syllabus` (
  `id` int(11) NOT NULL,
  `skill_id` int(11) NOT NULL,
  `topic_title` varchar(200) NOT NULL,
  `topic_order` int(11) NOT NULL,
  `file_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `skill_syllabus`
--

INSERT INTO `skill_syllabus` (`id`, `skill_id`, `topic_title`, `topic_order`, `file_path`) VALUES
(4, 2, 'Python Syntax & Variables', 1, 'uploads/syllabus/python_basics.pdf'),
(5, 2, 'Control Flow & Loops', 2, 'uploads/syllabus/python_control.pdf'),
(6, 2, 'Python Projects', 3, 'uploads/syllabus/python_projects.pdf'),
(7, 3, 'Photoshop Basics', 1, 'uploads/syllabus/photoshop_basics.pdf'),
(8, 3, 'Illustrator Tools', 2, 'uploads/syllabus/illustrator_tools.pdf'),
(9, 3, 'Design Projects', 3, 'uploads/syllabus/design_projects.pdf'),
(10, 4, 'SEO Basics', 1, 'uploads/syllabus/seo_basics.pdf'),
(11, 4, 'Social Media Marketing', 2, 'uploads/syllabus/social_media.pdf'),
(12, 4, 'Paid Ads', 3, 'uploads/syllabus/paid_ads.pdf'),
(13, 5, 'Python for Data Science', 1, 'uploads/syllabus/ds_python.pdf'),
(14, 5, 'Statistics & Probability', 2, 'uploads/syllabus/ds_stats.pdf'),
(15, 5, 'Machine Learning Basics', 3, 'uploads/syllabus/ds_ml.pdf'),
(17, 5, 'Machine learning advanced', 4, 'uploads/skills/1768546433_Applications of IC&T.pdf'),
(18, 6, 'Machine learning advanced', 1, 'admin/skills/uploads/1768547264_Applications of IC&T.pdf'),
(20, 1, 'Machine learning advan dsfasdced', 1, '/uploads/1768563761_CamScanner 02-10-2025 12.55.pdf'),
(22, 1, 'Machine learning advan dsfasdced dsfsaf', 3, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `student_code` varchar(50) DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `father_name` varchar(150) DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `user_id`, `student_code`, `name`, `father_name`, `gender`, `dob`, `phone`, `address`, `status`, `created_at`, `updated_at`) VALUES
(1, 4, 'STD-001', 'Ali Khan', 'Ahmed Khan', 'male', '2002-05-12', '030-112-3457', 'Kohat', 'active', '2026-01-20 07:17:32', '2026-01-19 23:42:11'),
(2, 5, 'STD-002', 'Ayesha Malik', 'Imran Malik', 'female', '2003-08-21', '03022334455', 'Karachi', 'active', '2026-01-20 07:17:32', '2026-01-19 23:17:32'),
(3, 6, 'STD-003', 'Usman Raza', 'Raza Hussain', 'male', '2001-11-02', '03123456789', 'Islamabad', 'active', '2026-01-20 07:17:32', '2026-01-19 23:17:32');

-- --------------------------------------------------------

--
-- Table structure for table `student_attendance`
--

CREATE TABLE `student_attendance` (
  `id` int(11) NOT NULL,
  `enrollment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `skill_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `attendance_date` date NOT NULL,
  `attendance_status` enum('present','absent','leave') NOT NULL,
  `marked_by` int(11) NOT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_attendance`
--

INSERT INTO `student_attendance` (`id`, `enrollment_id`, `student_id`, `skill_id`, `session_id`, `batch_id`, `attendance_date`, `attendance_status`, `marked_by`, `remarks`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 1, 1, '2026-01-20', 'present', 1, 'On time', 'active', '2026-01-20 09:25:17', '2026-01-20 09:25:17'),
(2, 2, 2, 2, 1, 2, '2026-01-20', 'absent', 1, 'Sick', 'active', '2026-01-20 09:25:17', '2026-01-20 09:25:17'),
(3, 3, 3, 3, 1, 3, '2026-01-20', 'present', 1, '', 'active', '2026-01-20 09:25:17', '2026-01-20 09:25:17');

-- --------------------------------------------------------

--
-- Table structure for table `student_enrollments`
--

CREATE TABLE `student_enrollments` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `skill_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `admission_date` date DEFAULT NULL,
  `status` enum('active','completed','dropped') DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_enrollments`
--

INSERT INTO `student_enrollments` (`id`, `student_id`, `skill_id`, `session_id`, `batch_id`, `admission_date`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 1, '2026-01-20', 'active', '2026-01-20 01:25:17', '2026-01-20 01:25:17'),
(2, 2, 2, 1, 2, '2026-01-20', 'active', '2026-01-20 01:25:17', '2026-01-20 01:25:17'),
(3, 3, 3, 1, 3, '2026-01-20', 'active', '2026-01-20 01:25:17', '2026-01-20 01:25:17');

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `teacher_code` varchar(50) DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `qualification` varchar(150) DEFAULT NULL,
  `experience_years` int(11) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`id`, `user_id`, `teacher_code`, `name`, `qualification`, `experience_years`, `phone`, `status`, `created_at`, `updated_at`) VALUES
(1, 2, 'TCH-001', 'Ahmed Khan', 'M.Sc Physics', 5, '03011234567', 'active', '2026-01-20 07:17:32', '2026-01-19 23:17:32'),
(2, 3, 'TCH-002', 'Sana Malik', 'B.Ed', 3, '03022334455', 'active', '2026-01-20 07:17:32', '2026-01-19 23:17:32'),
(3, 8, 'TCH-202601200008', 'Abdullah', 'M.Sc computer science', 3, '03214785693', 'active', '2026-01-20 07:35:00', '2026-01-19 23:49:03');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_assignments`
--

CREATE TABLE `teacher_assignments` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `assigned_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teacher_attendance`
--

CREATE TABLE `teacher_attendance` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `attendance_date` date NOT NULL,
  `attendance_status` enum('present','absent','leave') NOT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `marked_by` int(11) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `teacher_attendance`
--

INSERT INTO `teacher_attendance` (`id`, `teacher_id`, `attendance_date`, `attendance_status`, `remarks`, `marked_by`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, '2026-01-18', 'present', 'On time', 1, 'active', '2026-01-20 07:19:18', '2026-01-20 07:19:18'),
(2, 2, '2026-01-18', 'absent', 'Sick', 1, 'active', '2026-01-20 07:19:18', '2026-01-20 07:19:18');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `user_type_id` int(11) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `user_type_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@academy.com', 'admin123', 1, 'active', '2026-01-20 07:17:32', '2026-01-19 23:17:32'),
(2, 'teacher01', 'teacher01@academy.com', 'teacher123', 2, 'active', '2026-01-20 07:17:32', '2026-01-19 23:17:32'),
(3, 'teacher02', 'teacher02@academy.com', 'teacher123', 2, 'active', '2026-01-20 07:17:32', '2026-01-19 23:17:32'),
(4, 'student01', 'student01@student.com', 'student123', 3, 'active', '2026-01-20 07:17:32', '2026-01-19 23:42:11'),
(5, 'student02', 'student02@student.com', 'student123', 3, 'active', '2026-01-20 07:17:32', '2026-01-19 23:17:32'),
(6, 'student03', 'student03@student.com', 'student123', 3, 'active', '2026-01-20 07:17:32', '2026-01-19 23:17:32'),
(7, 'teacher03', 'izhan@gmail.com', '1203', 2, 'active', '2026-01-20 07:33:27', '2026-01-19 23:33:27'),
(8, 'teacher04', 'abdullah@gmail.com', '1203', 2, 'active', '2026-01-20 07:35:00', '2026-01-19 23:49:03');

-- --------------------------------------------------------

--
-- Table structure for table `user_types`
--

CREATE TABLE `user_types` (
  `id` int(11) NOT NULL,
  `type_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_types`
--

INSERT INTO `user_types` (`id`, `type_name`) VALUES
(1, 'admin'),
(3, 'student'),
(2, 'teacher');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `session_id` (`session_id`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `session_id` (`session_id`);

--
-- Indexes for table `batches`
--
ALTER TABLE `batches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `skill_id` (`skill_id`),
  ADD KEY `session_id` (`session_id`);

--
-- Indexes for table `batch_teachers`
--
ALTER TABLE `batch_teachers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `batch_id` (`batch_id`),
  ADD KEY `teacher_id` (`teacher_id`);

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
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_expense_category` (`category_id`);

--
-- Indexes for table `expense_categories`
--
ALTER TABLE `expense_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `fee_collections`
--
ALTER TABLE `fee_collections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `enrollment_id` (`enrollment_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `skill_id` (`skill_id`),
  ADD KEY `session_id` (`session_id`),
  ADD KEY `batch_id` (`batch_id`);

--
-- Indexes for table `fee_structures`
--
ALTER TABLE `fee_structures`
  ADD PRIMARY KEY (`id`),
  ADD KEY `skill_id` (`skill_id`),
  ADD KEY `session_id` (`session_id`);

--
-- Indexes for table `monthly_profit`
--
ALTER TABLE `monthly_profit`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_month_year` (`profit_month`,`profit_year`),
  ADD UNIQUE KEY `unique_month` (`month_year`);

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
  ADD KEY `enrollment_id` (`enrollment_id`),
  ADD KEY `session_id` (`session_id`);

--
-- Indexes for table `skill_syllabus`
--
ALTER TABLE `skill_syllabus`
  ADD PRIMARY KEY (`id`),
  ADD KEY `skill_id` (`skill_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_code` (`student_code`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `student_attendance`
--
ALTER TABLE `student_attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `enrollment_id` (`enrollment_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `skill_id` (`skill_id`),
  ADD KEY `session_id` (`session_id`),
  ADD KEY `batch_id` (`batch_id`);

--
-- Indexes for table `student_enrollments`
--
ALTER TABLE `student_enrollments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `skill_id` (`skill_id`),
  ADD KEY `session_id` (`session_id`),
  ADD KEY `batch_id` (`batch_id`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `teacher_code` (`teacher_code`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `teacher_assignments`
--
ALTER TABLE `teacher_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `batch_id` (`batch_id`),
  ADD KEY `session_id` (`session_id`);

--
-- Indexes for table `teacher_attendance`
--
ALTER TABLE `teacher_attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_teacher_date` (`teacher_id`,`attendance_date`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `user_type_id` (`user_type_id`);

--
-- Indexes for table `user_types`
--
ALTER TABLE `user_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `type_name` (`type_name`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `batches`
--
ALTER TABLE `batches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `batch_teachers`
--
ALTER TABLE `batch_teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `donations`
--
ALTER TABLE `donations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `expense_categories`
--
ALTER TABLE `expense_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `fee_collections`
--
ALTER TABLE `fee_collections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `fee_structures`
--
ALTER TABLE `fee_structures`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `monthly_profit`
--
ALTER TABLE `monthly_profit`
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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `skill_progress`
--
ALTER TABLE `skill_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `skill_syllabus`
--
ALTER TABLE `skill_syllabus`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `student_attendance`
--
ALTER TABLE `student_attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `student_enrollments`
--
ALTER TABLE `student_enrollments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `user_types`
--
ALTER TABLE `user_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `activity_logs_ibfk_2` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`);

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`);

--
-- Constraints for table `batches`
--
ALTER TABLE `batches`
  ADD CONSTRAINT `batches_ibfk_1` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`),
  ADD CONSTRAINT `batches_ibfk_2` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`);

--
-- Constraints for table `batch_teachers`
--
ALTER TABLE `batch_teachers`
  ADD CONSTRAINT `batch_teachers_ibfk_1` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`id`),
  ADD CONSTRAINT `batch_teachers_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`);

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `fk_expense_category` FOREIGN KEY (`category_id`) REFERENCES `expense_categories` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `fee_collections`
--
ALTER TABLE `fee_collections`
  ADD CONSTRAINT `fee_collections_ibfk_1` FOREIGN KEY (`enrollment_id`) REFERENCES `student_enrollments` (`id`),
  ADD CONSTRAINT `fee_collections_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
  ADD CONSTRAINT `fee_collections_ibfk_3` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`),
  ADD CONSTRAINT `fee_collections_ibfk_4` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`),
  ADD CONSTRAINT `fee_collections_ibfk_5` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`id`);

--
-- Constraints for table `fee_structures`
--
ALTER TABLE `fee_structures`
  ADD CONSTRAINT `fee_structures_ibfk_1` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`),
  ADD CONSTRAINT `fee_structures_ibfk_2` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`);

--
-- Constraints for table `skill_progress`
--
ALTER TABLE `skill_progress`
  ADD CONSTRAINT `skill_progress_ibfk_1` FOREIGN KEY (`enrollment_id`) REFERENCES `student_enrollments` (`id`),
  ADD CONSTRAINT `skill_progress_ibfk_2` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`);

--
-- Constraints for table `skill_syllabus`
--
ALTER TABLE `skill_syllabus`
  ADD CONSTRAINT `skill_syllabus_ibfk_1` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`);

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `student_attendance`
--
ALTER TABLE `student_attendance`
  ADD CONSTRAINT `student_attendance_ibfk_1` FOREIGN KEY (`enrollment_id`) REFERENCES `student_enrollments` (`id`),
  ADD CONSTRAINT `student_attendance_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
  ADD CONSTRAINT `student_attendance_ibfk_3` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`),
  ADD CONSTRAINT `student_attendance_ibfk_4` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`),
  ADD CONSTRAINT `student_attendance_ibfk_5` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`id`);

--
-- Constraints for table `student_enrollments`
--
ALTER TABLE `student_enrollments`
  ADD CONSTRAINT `student_enrollments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
  ADD CONSTRAINT `student_enrollments_ibfk_2` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`),
  ADD CONSTRAINT `student_enrollments_ibfk_3` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`),
  ADD CONSTRAINT `student_enrollments_ibfk_4` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`id`);

--
-- Constraints for table `teachers`
--
ALTER TABLE `teachers`
  ADD CONSTRAINT `teachers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `teacher_assignments`
--
ALTER TABLE `teacher_assignments`
  ADD CONSTRAINT `teacher_assignments_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`),
  ADD CONSTRAINT `teacher_assignments_ibfk_2` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`id`),
  ADD CONSTRAINT `teacher_assignments_ibfk_3` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`user_type_id`) REFERENCES `user_types` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

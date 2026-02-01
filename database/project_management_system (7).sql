-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 01, 2026 at 03:14 PM
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
-- Database: `project_management_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `client_name` varchar(255) NOT NULL,
  `client_phone` varchar(20) NOT NULL,
  `client_email` varchar(255) DEFAULT NULL,
  `category_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `task_id` int(11) DEFAULT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `notes` text DEFAULT NULL,
  `document_path` varchar(255) DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `status` enum('pending','confirmed','cancelled','completed') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `transfer_status` enum('none','pending','accepted','rejected') NOT NULL DEFAULT 'none',
  `transferred_to_user_id` int(11) DEFAULT NULL,
  `transfer_comments` text DEFAULT NULL,
  `transfer_requested_at` datetime DEFAULT NULL,
  `transfer_rejection_reason` text DEFAULT NULL,
  `transfer_from_user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `check_in` datetime DEFAULT NULL,
  `check_out` datetime DEFAULT NULL,
  `work_duration_hours` float NOT NULL DEFAULT 0,
  `work_duration` float DEFAULT 0,
  `status` enum('present','half_day','absent') NOT NULL,
  `entry_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `user_id`, `check_in`, `check_out`, `work_duration_hours`, `work_duration`, `status`, `entry_date`) VALUES
(1, 16, NULL, NULL, 0, 0, 'half_day', '2025-09-04'),
(2, 14, NULL, NULL, 0, 0, 'half_day', '2025-09-05'),
(3, 16, NULL, NULL, 0, 0, 'half_day', '2025-09-03'),
(4, 14, NULL, NULL, 0, 0, 'present', '2025-09-04'),
(5, 16, NULL, NULL, 0, 0, 'half_day', '2025-09-05'),
(6, 15, NULL, NULL, 0, 0, 'present', '2025-09-02'),
(7, 14, NULL, NULL, 0, 0, 'half_day', '2025-09-01'),
(8, 15, NULL, NULL, 0, 0, 'half_day', '2025-09-06'),
(9, 14, NULL, NULL, 0, 0, 'half_day', '2025-09-09'),
(10, 23, NULL, NULL, 0, 0, 'present', '2025-09-06');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `required_documents` text DEFAULT NULL,
  `is_live` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `required_documents`, `is_live`, `created_at`, `updated_at`) VALUES
(1, 'Web Development', 'Services related to website creation and maintenance.', NULL, 0, '2025-06-25 10:47:39', '2025-06-25 10:47:39'),
(2, 'Graphic Design', 'Visual content creation services.', NULL, 0, '2025-06-25 10:47:39', '2025-06-25 10:47:39'),
(3, 'Digital Marketing', 'Online marketing strategies and execution.', NULL, 0, '2025-06-25 10:47:39', '2025-06-25 10:47:39'),
(5, 'EPFO', 'Employees\' Provident Fund Organisation', 'આધારકાર્ડ \r\nપાનકાર્ડ', 1, '2025-06-30 18:57:49', '2025-09-12 23:29:05'),
(7, 'PAN CARD', 'INCOME TAX', NULL, 0, '2025-08-24 01:58:49', '2025-08-24 01:58:49'),
(8, 'Aadhaar', 'UIDAI', '', 1, '2025-09-06 15:35:15', '2025-09-12 23:29:11');

-- --------------------------------------------------------

--
-- Table structure for table `chat_connections`
--

CREATE TABLE `chat_connections` (
  `id` int(11) NOT NULL,
  `user_one_id` int(11) NOT NULL,
  `user_two_id` int(11) NOT NULL,
  `status` enum('pending','accepted','rejected','blocked') NOT NULL DEFAULT 'pending',
  `action_user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chat_connections`
--

INSERT INTO `chat_connections` (`id`, `user_one_id`, `user_two_id`, `status`, `action_user_id`, `created_at`, `updated_at`) VALUES
(1, 1, 13, 'accepted', 13, '2025-09-05 12:06:08', '2025-09-05 12:11:23'),
(2, 1, 14, 'pending', 14, '2025-09-07 10:23:42', '2025-09-07 10:23:42'),
(3, 1, 15, 'pending', 15, '2025-09-07 10:54:58', '2025-09-07 10:54:58'),
(4, 15, 23, 'pending', 15, '2025-09-07 10:55:00', '2025-09-07 10:55:00'),
(5, 14, 15, 'accepted', 14, '2025-09-07 10:55:01', '2025-09-07 11:23:26'),
(6, 14, 23, 'pending', 14, '2025-09-07 13:11:02', '2025-09-07 13:11:02'),
(7, 15, 24, 'pending', 15, '2025-09-08 12:32:12', '2025-09-08 12:32:12'),
(8, 1, 24, 'pending', 24, '2026-02-01 14:12:04', '2026-02-01 14:12:04');

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `id` int(11) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `document_path` varchar(255) DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `client_name` varchar(255) NOT NULL DEFAULT '',
  `company_name` varchar(255) DEFAULT NULL,
  `company` varchar(255) NOT NULL DEFAULT '',
  `status` varchar(20) NOT NULL DEFAULT 'Active',
  `submitted_by_user_id` int(11) DEFAULT NULL,
  `approval_status` enum('pending','approved','rejected') DEFAULT 'pending',
  `approved_by_user_id` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clients`
--

INSERT INTO `clients` (`id`, `email`, `phone`, `address`, `document_path`, `contact_person`, `created_at`, `updated_at`, `client_name`, `company_name`, `company`, `status`, `submitted_by_user_id`, `approval_status`, `approved_by_user_id`, `approved_at`) VALUES
(5, 'bronline234@gmail.com', '9870087387', 'Naroda', NULL, '7777975967', '2025-06-29 17:30:25', '2025-09-10 19:53:45', 'Demo Client', 'Honest', 'none', 'Active', NULL, 'pending', NULL, NULL),
(9, 'admin@yourcompany.com', '7777975967', 'Ahmedabad', NULL, '7777975967', '2025-06-30 18:35:05', '2025-06-30 18:35:05', 'Kajal', NULL, 'None', 'Active', NULL, 'pending', NULL, NULL),
(17, 'bhavesh@bronline.net', '9870087387', '', NULL, NULL, '2025-09-07 16:21:02', '2025-09-10 19:54:07', 'Demo', 'Arvind', '', 'Active', NULL, 'pending', NULL, NULL),
(18, 'admin@bronline.net', '9870087387', '', NULL, NULL, '2025-09-08 01:17:21', '2025-09-10 19:54:22', 'Demo Person3', 'AGEW', '', 'Active', NULL, 'pending', NULL, NULL),
(21, 'alertbronline@gmail.com', '9870087387', '', NULL, NULL, '2025-09-09 17:42:01', '2025-09-09 17:42:01', 'Pranil', NULL, '', 'Active', NULL, 'pending', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `connections`
--

CREATE TABLE `connections` (
  `id` int(11) NOT NULL,
  `user1_id` int(11) NOT NULL,
  `user2_id` int(11) NOT NULL,
  `status` enum('pending','accepted') NOT NULL DEFAULT 'pending',
  `action_user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `customer_address` text DEFAULT NULL,
  `customer_email` varchar(255) DEFAULT NULL,
  `client_id` int(11) DEFAULT NULL,
  `source` enum('form','appointment') NOT NULL DEFAULT 'form',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `customer_name`, `customer_phone`, `customer_address`, `customer_email`, `client_id`, `source`, `created_at`, `updated_at`) VALUES
(1, 'Pranil', '9870087387', 'Ahmedabad', 'pranil@bronline.net', 17, 'form', '2025-09-10 20:24:03', '2025-09-10 20:24:03'),
(5, 'Shilpa', '7861958916', '', '', NULL, 'form', '2025-09-10 20:47:42', '2025-09-10 20:47:42'),
(7, 'Preeti', '1235689745', 'Ahmedabad', 'preeti@gmail.com', NULL, 'form', '2025-09-11 12:21:28', '2025-09-11 12:21:28'),
(8, 'Appotment', '9426457456', NULL, NULL, NULL, 'appointment', '2025-09-11 12:22:15', '2025-09-11 12:22:15'),
(18, 'Demo Person', '9870087387', NULL, 'bronline234@gmail.com', 5, 'appointment', '2025-09-15 00:41:55', '2025-09-15 00:41:55');

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL,
  `expense_type` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `expense_date` date NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`id`, `expense_type`, `amount`, `description`, `expense_date`, `created_at`, `updated_at`) VALUES
(11, 'Electricity', 5000.00, '', '2025-09-10', '2025-09-10 13:52:46', '2025-09-10 13:52:46');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message_text` text NOT NULL,
  `read_at` datetime DEFAULT NULL,
  `sent_at` datetime NOT NULL DEFAULT current_timestamp(),
  `read_status` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_by_sender` tinyint(1) DEFAULT 0,
  `deleted_by_receiver` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `message`, `link`, `is_read`, `created_at`) VALUES
(6, 1, 'New withdrawal request of 200 from Preeti Freelancer.', '?page=manage_withdrawals', 1, '2025-09-05 00:14:25'),
(8, 1, 'Freelancer Preeti Freelancer returned task #25.', '?page=edit_task&id=25', 1, '2025-09-05 00:16:40'),
(9, 1, 'New withdrawal request from Preeti Freelancer', '?page=manage_withdrawals', 1, '2025-09-05 14:32:06'),
(18, 24, 'You have been assigned a new task #39.', '?page=update_task&id=39', 0, '2025-09-14 22:06:52'),
(19, 1, 'Task #39 has been submitted for verification.', '?page=edit_task&id=39', 0, '2025-09-14 23:21:26'),
(20, 24, 'Your task #39 has been verified and marked as completed!', '?page=update_task&id=39', 0, '2025-09-14 23:21:56'),
(21, 1, 'Task #39 has been submitted for verification.', '?page=edit_task&id=39', 0, '2025-09-14 23:37:54'),
(22, 24, 'Your task #39 has been verified and marked as completed!', '?page=update_task&id=39', 0, '2025-09-14 23:38:26'),
(23, 24, 'Your task #39 has been verified and marked as completed!', '?page=update_task&id=39', 0, '2025-09-14 23:43:03'),
(24, 24, 'Your task #39 has been verified and marked as completed!', '?page=update_task&id=39', 0, '2025-09-14 23:53:04'),
(25, 24, 'Your task #39 has been verified and marked as completed!', '?page=update_task&id=39', 0, '2025-09-14 23:58:51'),
(26, 24, 'You have been assigned a new task #40.', '?page=update_task&id=40', 0, '2025-09-15 00:24:04'),
(27, 1, 'Task #40 has been submitted for verification.', '?page=edit_task&id=40', 0, '2025-09-15 00:33:51'),
(28, 24, 'Your task #40 has been verified and marked as completed!', '?page=update_task&id=40', 0, '2025-09-15 00:34:25'),
(30, 1, 'Task #41 has been submitted for verification.', '?page=edit_task&id=41', 0, '2025-09-15 00:42:25'),
(31, 24, 'Your task #40 has been verified and marked as completed!', '?page=update_task&id=40', 0, '2025-09-15 00:43:21');

-- --------------------------------------------------------

--
-- Table structure for table `recruitment_posts`
--

CREATE TABLE `recruitment_posts` (
  `id` int(11) NOT NULL,
  `job_title` varchar(255) NOT NULL,
  `total_vacancies` int(11) NOT NULL,
  `image_banner_url` varchar(255) DEFAULT NULL,
  `eligibility_criteria` text DEFAULT NULL,
  `selection_process` text DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `last_date` date DEFAULT NULL,
  `exam_date` date DEFAULT NULL,
  `fee_payment_last_date` date DEFAULT NULL,
  `application_fees` text DEFAULT NULL,
  `category_wise_vacancies` text DEFAULT NULL,
  `notification_url` varchar(255) DEFAULT NULL,
  `apply_url` varchar(255) DEFAULT NULL,
  `admit_card_url` varchar(255) DEFAULT NULL,
  `official_website_url` varchar(255) DEFAULT NULL,
  `exam_prediction` text DEFAULT NULL,
  `custom_fields_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`custom_fields_json`)),
  `submitted_by_user_id` int(11) NOT NULL,
  `approval_status` enum('pending','approved','rejected','returned_for_edit') DEFAULT 'pending',
  `approved_by_user_id` int(11) DEFAULT NULL,
  `admin_comments` text DEFAULT NULL,
  `is_new_for_admin` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `approved_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `recruitment_posts`
--

INSERT INTO `recruitment_posts` (`id`, `job_title`, `total_vacancies`, `image_banner_url`, `eligibility_criteria`, `selection_process`, `start_date`, `last_date`, `exam_date`, `fee_payment_last_date`, `application_fees`, `category_wise_vacancies`, `notification_url`, `apply_url`, `admit_card_url`, `official_website_url`, `exam_prediction`, `custom_fields_json`, `submitted_by_user_id`, `approval_status`, `approved_by_user_id`, `admin_comments`, `is_new_for_admin`, `created_at`, `updated_at`, `approved_at`) VALUES
(13, 'SSC CGL Recruitment 2025 - Apply Online for 14,582 Vacancies', 500, 'http://localhost/project_management_system/post/generated_posters/poster_68c6f4d82cfef.jpg', '', '', '2025-09-17', '2025-09-19', '0000-00-00', '0000-00-00', '', '', 'https://www.bronline.net/', 'https://www.bronline.net/', '', 'https://www.bronline.net/', '', '[]', 24, 'approved', 1, NULL, 0, '2025-09-14 22:32:08', '2025-09-14 23:03:31', '2025-09-14 23:03:31');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `role_name` varchar(100) NOT NULL,
  `permissions` text DEFAULT NULL COMMENT 'Stores JSON array of allowed page keys',
  `dashboard_permissions` longtext DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `role_name`, `permissions`, `dashboard_permissions`, `created_at`) VALUES
(1, 'Admin', '*', NULL, '2025-08-23 14:33:22'),
(2, 'Coordinator', '[\"clients\",\"user_dashboard\",\"my_tasks\",\"update_task\",\"submit_work\",\"my_appointments\",\"customers\",\"messages\",\"user_settings\"]', '[]', '2025-08-23 14:33:22'),
(3, 'DEO', '[\"my_tasks\",\"worker_dashboard\",\"add_recruitment_post\",\"my_recruitment_posts\",\"view_recruitment_post\",\"generate_poster\",\"my_freelancer_tasks\",\"update_freelancer_task\",\"my_withdrawals\",\"bank_details\",\"messages\",\"user_settings\"]', NULL, '2025-08-23 22:14:52'),
(4, 'Manager', '[\"clients\",\"appointments\",\"categories\",\"assign_task\",\"all_tasks\",\"edit_task\",\"expenses\",\"reports\",\"user_dashboard\",\"my_tasks\",\"update_task\",\"submit_work\",\"my_appointments\",\"customers\",\"add_recruitment_post\",\"my_recruitment_posts\",\"generate_poster\",\"my_withdrawals\",\"bank_details\",\"messages\",\"user_settings\",\"master_dashboard\"]', '[\"show_financial_summary\",\"show_task_summary\",\"show_user_client_summary\",\"show_appointment_summary\",\"show_pending_actions\",\"show_recent_activity\",\"show_notifications\"]', '2025-08-24 05:47:52'),
(5, 'HR', '[\"hr_dashboard\",\"messages\",\"hr_management\",\"manage_attendance\",\"manage_salaries\",\"hr_settings\"]', NULL, '2025-09-03 14:29:06'),
(6, 'Freelancer', '[\"submit_work\",\"worker_dashboard\",\"add_recruitment_post\",\"my_recruitment_posts\",\"view_recruitment_post\",\"generate_poster\",\"my_freelancer_tasks\",\"update_freelancer_task\",\"my_withdrawals\",\"bank_details\",\"messages\",\"user_settings\"]', '[]', '2025-09-04 12:53:42'),
(7, 'Accountant', '[\"clients\",\"appointments\",\"manage_withdrawals\",\"manage_salaries\",\"accountant_dashboard\",\"user_dashboard\",\"my_tasks\",\"update_task\",\"submit_work\",\"my_appointments\",\"user_settings\",\"master_dashboard\"]', '[\"show_financial_summary\",\"show_task_summary\",\"show_user_client_summary\",\"show_appointment_summary\",\"show_pending_actions\",\"show_recent_activity\",\"show_notifications\"]', '2025-09-07 17:10:11');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `app_name` varchar(255) NOT NULL DEFAULT 'Project Management System',
  `app_logo_url` varchar(255) DEFAULT NULL,
  `currency_symbol` varchar(10) NOT NULL DEFAULT '₹',
  `required_daily_hours` float NOT NULL DEFAULT 8,
  `earning_per_approved_post` decimal(10,2) NOT NULL DEFAULT 10.00,
  `minimum_withdrawal_amount` decimal(10,2) NOT NULL DEFAULT 500.00,
  `whatsapp_business_number` varchar(255) DEFAULT NULL COMMENT 'Stores Phone Number ID for Meta',
  `whatsapp_api_key` text DEFAULT NULL COMMENT 'Stores Access Token for Meta',
  `smtp_host` varchar(255) DEFAULT NULL,
  `smtp_port` int(5) DEFAULT 587,
  `smtp_encryption` varchar(10) NOT NULL DEFAULT 'tls',
  `smtp_username` varchar(255) DEFAULT NULL,
  `smtp_password` varchar(255) DEFAULT NULL,
  `smtp_from_email` varchar(255) DEFAULT NULL,
  `smtp_from_name` varchar(255) DEFAULT NULL,
  `office_address` text DEFAULT NULL,
  `helpline_number` varchar(255) DEFAULT NULL,
  `office_start_time` time NOT NULL DEFAULT '10:00:00',
  `office_end_time` time NOT NULL DEFAULT '18:00:00',
  `appointment_slot_duration` int(11) NOT NULL DEFAULT 30 COMMENT 'Duration in minutes',
  `office_working_days` varchar(255) NOT NULL DEFAULT '1,2,3,4,5,6' COMMENT 'Comma-separated day numbers (1=Mon, 7=Sun)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `app_name`, `app_logo_url`, `currency_symbol`, `required_daily_hours`, `earning_per_approved_post`, `minimum_withdrawal_amount`, `whatsapp_business_number`, `whatsapp_api_key`, `smtp_host`, `smtp_port`, `smtp_encryption`, `smtp_username`, `smtp_password`, `smtp_from_email`, `smtp_from_name`, `office_address`, `helpline_number`, `office_start_time`, `office_end_time`, `appointment_slot_duration`, `office_working_days`) VALUES
(1, 'B R Online Services', 'http://localhost/project_management_system/uploads/logo/logo_1756908164.png', '₹', 8, 10.00, 10.00, '', '', 'smtp.gmail.com', 587, 'tls', 'bronline234@gmail.com', 'pqqv ywob ixde ckbd', 'info@bronline.net', 'B R Online Services', 'D1 GF Arvind Mega Trade, Opp Arvind Avishkar, Naroda Road, Ahmedabad - 382345', '+91 9870087387', '10:30:00', '18:00:00', 30, '1,2,3,4,5,6');

-- --------------------------------------------------------

--
-- Table structure for table `subcategories`
--

CREATE TABLE `subcategories` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `fare` decimal(10,2) NOT NULL DEFAULT 0.00,
  `maintenance_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `maintenance_fee_required` tinyint(1) NOT NULL DEFAULT 0,
  `description` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subcategories`
--

INSERT INTO `subcategories` (`id`, `category_id`, `name`, `fare`, `maintenance_fee`, `maintenance_fee_required`, `description`, `created_at`, `updated_at`) VALUES
(1, 1, 'E-commerce Website', 1500.00, 0.00, 0, 'Full E-commerce solution with payment gateway.', '2025-06-25 10:47:39', '2025-06-25 10:47:39'),
(3, 2, 'Logo Design', 250.00, 0.00, 0, 'Custom logo creation.', '2025-06-25 10:47:39', '2025-06-25 10:47:39'),
(4, 2, 'Brochure Design', 150.00, 0.00, 0, 'Tri-fold brochure design.', '2025-06-25 10:47:39', '2025-06-25 10:47:39'),
(5, 3, 'SEO Optimization', 300.00, 0.00, 0, 'Search Engine Optimization package.', '2025-06-25 10:47:39', '2025-06-25 10:47:39'),
(6, 3, 'Social Media Campaign', 400.00, 0.00, 0, 'Managed social media advertising.', '2025-06-25 10:47:39', '2025-06-25 10:47:39'),
(7, 5, 'FORM 19', 500.00, 0.00, 0, 'Form 19 (also known as PF Form 19) is the official claim form for final settlement of your Employee Provident Fund (EPF) — used to withdraw your full PF balance (your contribution + employer\'s share + interest) when you:', '2025-06-30 18:58:41', '2025-06-30 18:58:41'),
(8, 8, 'Appointment', 150.00, 0.00, 0, '', '2025-09-06 15:35:30', '2025-09-06 15:35:54');

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `task_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `task_type` enum('general','blog_post_entry','recruitment_data_entry') NOT NULL DEFAULT 'general',
  `assigned_to_user_id` int(11) NOT NULL,
  `assigned_by_user_id` int(11) DEFAULT NULL,
  `status` enum('pending','in_progress','completed','on_hold','cancelled') NOT NULL DEFAULT 'pending',
  `due_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','manager','coordinator','sales','assistant','accountant','data_entry_operator') NOT NULL DEFAULT 'assistant',
  `role_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `last_activity_at` datetime DEFAULT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `account_holder_name` varchar(255) DEFAULT NULL,
  `account_number` varchar(255) DEFAULT NULL,
  `ifsc_code` varchar(255) DEFAULT NULL,
  `upi_id` varchar(255) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `salary` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `role_id`, `created_at`, `updated_at`, `status`, `last_activity_at`, `bank_name`, `account_holder_name`, `account_number`, `ifsc_code`, `upi_id`, `profile_picture`, `salary`) VALUES
(1, 'Admin Bhavesh', 'admin@bronline.net', '$2y$10$nZFKzBSEVH71DKja9.8p/OdICAT17T8CEsbM/BziuHp46jirxAHo6', 'admin', 1, '2025-06-25 10:47:39', '2026-02-01 19:43:53', 'active', '2026-02-01 19:43:53', NULL, NULL, NULL, NULL, NULL, 'uploads/profile_pictures/user_1_1756911138.png', 0.00),
(24, 'Freelancer User', 'freelancer@bronline.net', '$2y$10$ufmxTpB0EhLwwudT2IDmQ.kjrKcj.I7Dgz8ItwXa3FJ4D4nbeDLzG', 'assistant', 6, '2025-09-07 22:39:41', '2026-02-01 19:42:45', 'active', '2026-02-01 19:42:45', 'Canarabank', 'Bhavesh', '123456', 'CNRB000175', '', 'uploads/profile_pictures/user_24_1757867936.jpg', 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `withdrawals`
--

CREATE TABLE `withdrawals` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `bank_details_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`bank_details_json`)),
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `requested_at` datetime NOT NULL DEFAULT current_timestamp(),
  `processed_by_user_id` int(11) DEFAULT NULL,
  `processed_at` datetime DEFAULT NULL,
  `admin_comments` text DEFAULT NULL,
  `transaction_id` varchar(255) DEFAULT NULL COMMENT 'Transaction ID from payment gateway'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `withdrawal_requests`
--

CREATE TABLE `withdrawal_requests` (
  `id` int(11) NOT NULL,
  `deo_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `request_date` datetime DEFAULT current_timestamp(),
  `status` enum('pending','processing','details_requested','paid','rejected') DEFAULT 'pending',
  `transaction_number` varchar(255) DEFAULT NULL,
  `admin_comments` text DEFAULT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `account_holder_name` varchar(255) DEFAULT NULL,
  `account_number` varchar(255) DEFAULT NULL,
  `ifsc_code` varchar(255) DEFAULT NULL,
  `upi_id` varchar(255) DEFAULT NULL,
  `processed_by_admin_id` int(11) DEFAULT NULL,
  `processed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `work_assignments`
--

CREATE TABLE `work_assignments` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `assigned_to_user_id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `assigned_by_user_id` int(11) DEFAULT NULL,
  `category_id` int(11) NOT NULL,
  `subcategory_id` int(11) NOT NULL,
  `work_description` text NOT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `deadline` date NOT NULL,
  `completed_at` datetime DEFAULT NULL,
  `fee` decimal(10,2) NOT NULL,
  `fee_mode` enum('online','cash','credit_card','pending') NOT NULL DEFAULT 'pending',
  `maintenance_fee` decimal(10,2) DEFAULT 0.00,
  `maintenance_fee_mode` enum('online','cash','credit_card','pending') NOT NULL DEFAULT 'pending',
  `discount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `task_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('pending','in_process','completed','cancelled','pending_verification','verified_completed','returned') NOT NULL DEFAULT 'pending',
  `payment_status` enum('pending','paid_full','paid_partial','refunded') NOT NULL DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `user_notes` text DEFAULT NULL,
  `task_attachment_path` varchar(255) DEFAULT NULL,
  `completion_receipt_path` varchar(255) DEFAULT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `transfer_status` enum('none','pending','accepted','rejected') NOT NULL DEFAULT 'none',
  `transferred_to_user_id` int(11) DEFAULT NULL,
  `transfer_requested_at` datetime DEFAULT NULL,
  `transfer_comments` text DEFAULT NULL,
  `transfer_rejection_reason` text DEFAULT NULL,
  `transfer_from_user_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `completion_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_date_unique` (`user_id`,`entry_date`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `chat_connections`
--
ALTER TABLE `chat_connections`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_connection` (`user_one_id`,`user_two_id`);

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `connections`
--
ALTER TABLE `connections`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_connection` (`user1_id`,`user2_id`),
  ADD KEY `user2_id` (`user2_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `recruitment_posts`
--
ALTER TABLE `recruitment_posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `submitted_by_user_id` (`submitted_by_user_id`),
  ADD KEY `approved_by_user_id` (`approved_by_user_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `subcategories`
--
ALTER TABLE `subcategories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `category_name_unique` (`category_id`,`name`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assigned_to_user_id` (`assigned_to_user_id`),
  ADD KEY `assigned_by_user_id` (`assigned_by_user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `withdrawals`
--
ALTER TABLE `withdrawals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `processed_by_user_id` (`processed_by_user_id`);

--
-- Indexes for table `withdrawal_requests`
--
ALTER TABLE `withdrawal_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `deo_id` (`deo_id`),
  ADD KEY `processed_by_admin_id` (`processed_by_admin_id`);

--
-- Indexes for table `work_assignments`
--
ALTER TABLE `work_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `assigned_to_user_id` (`assigned_to_user_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `subcategory_id` (`subcategory_id`),
  ADD KEY `work_assignments_ibfk_5` (`customer_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `chat_connections`
--
ALTER TABLE `chat_connections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `connections`
--
ALTER TABLE `connections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=141;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `recruitment_posts`
--
ALTER TABLE `recruitment_posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `subcategories`
--
ALTER TABLE `subcategories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `withdrawals`
--
ALTER TABLE `withdrawals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `withdrawal_requests`
--
ALTER TABLE `withdrawal_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `work_assignments`
--
ALTER TABLE `work_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `connections`
--
ALTER TABLE `connections`
  ADD CONSTRAINT `connections_ibfk_1` FOREIGN KEY (`user1_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `connections_ibfk_2` FOREIGN KEY (`user2_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `customers_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `recruitment_posts`
--
ALTER TABLE `recruitment_posts`
  ADD CONSTRAINT `recruitment_posts_ibfk_1` FOREIGN KEY (`submitted_by_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `recruitment_posts_ibfk_2` FOREIGN KEY (`approved_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `subcategories`
--
ALTER TABLE `subcategories`
  ADD CONSTRAINT `subcategories_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`assigned_to_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tasks_ibfk_2` FOREIGN KEY (`assigned_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `withdrawals`
--
ALTER TABLE `withdrawals`
  ADD CONSTRAINT `withdrawals_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `withdrawals_ibfk_2` FOREIGN KEY (`processed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `withdrawal_requests`
--
ALTER TABLE `withdrawal_requests`
  ADD CONSTRAINT `withdrawal_requests_ibfk_1` FOREIGN KEY (`deo_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `withdrawal_requests_ibfk_2` FOREIGN KEY (`processed_by_admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `work_assignments`
--
ALTER TABLE `work_assignments`
  ADD CONSTRAINT `work_assignments_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`),
  ADD CONSTRAINT `work_assignments_ibfk_2` FOREIGN KEY (`assigned_to_user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `work_assignments_ibfk_3` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  ADD CONSTRAINT `work_assignments_ibfk_4` FOREIGN KEY (`subcategory_id`) REFERENCES `subcategories` (`id`),
  ADD CONSTRAINT `work_assignments_ibfk_5` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

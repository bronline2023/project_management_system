-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 16, 2025 at 01:52 PM
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
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Web Development', 'Services related to website creation and maintenance.', '2025-06-25 10:47:39', '2025-06-25 10:47:39'),
(2, 'Graphic Design', 'Visual content creation services.', '2025-06-25 10:47:39', '2025-06-25 10:47:39'),
(3, 'Digital Marketing', 'Online marketing strategies and execution.', '2025-06-25 10:47:39', '2025-06-25 10:47:39'),
(5, 'EPFO', 'Employees\' Provident Fund Organisation', '2025-06-30 18:57:49', '2025-06-30 18:57:49');

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `client_name` varchar(255) NOT NULL DEFAULT '',
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

INSERT INTO `clients` (`id`, `name`, `email`, `phone`, `address`, `contact_person`, `created_at`, `updated_at`, `client_name`, `company`, `status`, `submitted_by_user_id`, `approval_status`, `approved_by_user_id`, `approved_at`) VALUES
(5, 'Bhavesh', 'info@jyotitravel.in', '9870087387', 'Ahmedabad', '7777975967', '2025-06-29 17:30:25', '2025-06-30 13:26:51', 'Demo Client', 'none', 'Active', NULL, 'pending', NULL, NULL),
(9, '', 'admin@yourcompany.com', '7777975967', 'Ahmedabad', '7777975967', '2025-06-30 18:35:05', '2025-06-30 18:35:05', 'Kajal', 'None', 'Active', NULL, 'pending', NULL, NULL),
(10, '', 'preeti@bronline.net', '8238111888', 'Ahmedabad', '7777975967', '2025-06-30 18:37:23', '2025-06-30 18:37:23', 'Demo Client 2', 'None', 'Active', NULL, 'pending', NULL, NULL);

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
(3, 'Stationery', 45.50, 'Purchase of office supplies.', '2025-06-15', '2025-06-25 10:47:39', '2025-06-25 10:47:39'),
(4, 'Electricity', 4190.00, 'CC ICICI', '2025-07-01', '2025-06-30 16:15:45', '2025-06-30 16:15:45');

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

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `sender_id`, `receiver_id`, `message_text`, `read_at`, `sent_at`, `read_status`, `created_at`, `is_read`, `deleted_by_sender`, `deleted_by_receiver`) VALUES
(54, 12, 1, 'yes recived', '2025-06-29 14:33:30', '2025-06-29 14:33:26', 1, '2025-06-29 14:33:26', 0, 0, 1),
(55, 1, 12, 'hello', '2025-06-29 14:37:38', '2025-06-29 14:37:15', 1, '2025-06-29 14:37:15', 0, 1, 0),
(56, 1, 12, 'hi', '2025-06-30 12:50:48', '2025-06-29 20:02:59', 1, '2025-06-29 20:02:59', 0, 0, 0),
(57, 12, 1, 'hello', '2025-06-30 18:36:40', '2025-06-30 18:36:26', 1, '2025-06-30 18:36:26', 0, 0, 0),
(58, 12, 1, 'Recived', '2025-06-30 18:54:10', '2025-06-30 18:53:19', 1, '2025-06-30 18:53:19', 0, 0, 0),
(59, 13, 1, 'hi', '2025-07-24 13:51:51', '2025-07-24 13:51:27', 1, '2025-07-24 13:51:27', 0, 0, 0),
(60, 13, 1, 'hello', '2025-07-24 13:56:04', '2025-07-24 13:55:51', 1, '2025-07-24 13:55:51', 0, 0, 0),
(61, 13, 1, 'hello', '2025-07-24 15:26:15', '2025-07-24 15:26:05', 1, '2025-07-24 15:26:05', 0, 0, 0),
(62, 1, 13, 'hello', '2025-07-24 20:17:54', '2025-07-24 20:17:44', 1, '2025-07-24 20:17:44', 0, 0, 0),
(63, 13, 1, 'yes recived', '2025-07-24 20:18:27', '2025-07-24 20:18:00', 1, '2025-07-24 20:18:00', 0, 0, 0),
(64, 13, 1, 'hfhfh', '2025-08-03 13:55:27', '2025-08-02 13:28:34', 1, '2025-08-02 13:28:34', 0, 0, 0),
(65, 1, 12, 'hi', NULL, '2025-08-16 16:51:53', 0, '2025-08-16 16:51:53', 0, 0, 0),
(66, 1, 12, 'hello', NULL, '2025-08-16 16:53:21', 0, '2025-08-16 16:53:21', 0, 0, 0),
(67, 13, 1, 'hi', '2025-08-16 16:55:06', '2025-08-16 16:54:49', 1, '2025-08-16 16:54:49', 0, 0, 0),
(68, 1, 13, 'yes', NULL, '2025-08-16 16:55:12', 0, '2025-08-16 16:55:12', 0, 0, 0),
(69, 1, 13, 'hello', NULL, '2025-08-16 16:56:38', 0, '2025-08-16 16:56:38', 0, 0, 0),
(70, 1, 13, 'hi', NULL, '2025-08-16 16:58:06', 0, '2025-08-16 16:58:06', 0, 0, 0),
(71, 1, 13, 'hello', NULL, '2025-08-16 16:58:27', 0, '2025-08-16 16:58:27', 0, 0, 0),
(72, 1, 13, 'hello', NULL, '2025-08-16 16:58:47', 0, '2025-08-16 16:58:47', 0, 0, 0),
(73, 1, 13, 'updated cod', NULL, '2025-08-16 17:10:00', 0, '2025-08-16 17:10:00', 0, 0, 0),
(74, 1, 13, 'updated new', NULL, '2025-08-16 17:11:20', 0, '2025-08-16 17:11:20', 0, 0, 0),
(75, 1, 13, 'neww', NULL, '2025-08-16 17:12:49', 0, '2025-08-16 17:12:49', 0, 0, 0),
(76, 1, 13, 'અગેન', NULL, '2025-08-16 17:14:52', 0, '2025-08-16 17:14:52', 0, 0, 0),
(77, 1, 13, 'અગેન', NULL, '2025-08-16 17:16:19', 0, '2025-08-16 17:16:19', 0, 0, 0),
(78, 1, 13, 'love', NULL, '2025-08-16 17:21:21', 0, '2025-08-16 17:21:21', 0, 0, 0);

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
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `approved_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `recruitment_posts`
--

INSERT INTO `recruitment_posts` (`id`, `job_title`, `total_vacancies`, `image_banner_url`, `eligibility_criteria`, `selection_process`, `start_date`, `last_date`, `exam_date`, `fee_payment_last_date`, `application_fees`, `category_wise_vacancies`, `notification_url`, `apply_url`, `admit_card_url`, `official_website_url`, `exam_prediction`, `custom_fields_json`, `submitted_by_user_id`, `approval_status`, `approved_by_user_id`, `admin_comments`, `created_at`, `updated_at`, `approved_at`) VALUES
(1, 'SSC CGL Recruitment 2025 - Apply Online for 14,582 Vacancies', 500, 'https://i.ibb.co/k2SsGZF0/recruitment-poster-13.png', 'Age - 18 - 30', 'Written Exam', '2025-07-01', '2025-07-22', '2025-07-22', '2025-07-30', 'General = 500\r\nSC = 200', 'UR = 1000\r\nSC - 50', 'https://www.bronline.net/2025/06/ssc-cgl-recruitment-2025-apply-online_13.html', 'https://www.bronline.net/2025/06/ssc-cgl-recruitment-2025-apply-online_13.html', 'https://www.bronline.net/2025/06/ssc-cgl-recruitment-2025-apply-online_13.html', 'https://www.bronline.net/2025/06/ssc-cgl-recruitment-2025-apply-online_13.html', 'Exepted', '[{\"heading\":\"Importatnt Not\",\"content\":\"250\"},{\"heading\":\"Imporatant not 2\",\"content\":\"25154\"}]', 13, 'approved', 1, NULL, '2025-07-28 15:43:51', '2025-07-28 16:20:29', '2025-07-28 16:20:29'),
(2, 'ntelligence Bureau IB Security Assistant/ Executive Recruitment 2025', 3500, 'https://www.incometax.gov.in/iec/foportal/sites/default/files/styles/home_slider/public/2025-01/Bank%20validation_Banner_Jan25.png?itok=N6Sfsr2U', 'Age = 18 - 27', 'Written Exam 1 \r\nTiar - 2\r\nInterview', '2025-08-01', '2025-08-30', '2025-08-15', '2025-08-16', 'General  = 500\r\nSC = 250', 'UR = 1500\r\nSC = 250', 'https://img2.freejobalert.com/news/2025/07/7896321-68805c049724c27506450.pdf', 'https://cdn.digialm.com/EForms/configuredHtml/1258/94478/Index.html', 'https://cdn.digialm.com/EForms/configuredHtml/1258/94478/Index.html', 'https://mha.gov.in/', '', '[{\"heading\":\"Extra Information\",\"content\":\"Contents\"}]', 13, 'rejected', 1, NULL, '2025-08-02 13:25:05', '2025-08-05 20:22:22', '2025-08-05 20:22:22'),
(3, 'Intelligence Bureau IB Security Assistant/ Executive Recruitment 2025', 5000, 'https://www.incometax.gov.in/iec/foportal/sites/default/files/styles/home_slider/public/2025-01/Bank%20validation_Banner_Jan25.png?itok=N6Sfsr2U', '', '', '2025-08-21', '2025-08-22', '2025-08-29', '0000-00-00', '', '', '', '', '', '', '', '[]', 13, 'approved', 1, NULL, '2025-08-05 20:20:48', '2025-08-05 20:22:46', '2025-08-05 20:22:46');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `app_name` varchar(255) NOT NULL DEFAULT 'Project Management System',
  `app_logo_url` varchar(255) DEFAULT NULL,
  `currency_symbol` varchar(10) NOT NULL DEFAULT '$',
  `timezone` varchar(255) NOT NULL DEFAULT 'Asia/Kolkata',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `earning_per_approved_post` decimal(10,2) DEFAULT 10.00,
  `minimum_withdrawal_amount` decimal(10,2) DEFAULT 500.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `app_name`, `app_logo_url`, `currency_symbol`, `timezone`, `created_at`, `updated_at`, `earning_per_approved_post`, `minimum_withdrawal_amount`) VALUES
(1, 'B R ONLINE SERVICES', 'https://lh3.googleusercontent.com/mUTqJIWA7ToKmDofA79wHdodwDrcFdR9KtYdJs72VqYgQ-W5pYOFaC9_mgEitxsyWab-Ilxv_TOWA9n2dnqH-IZcDSEv1MQMrdL8f80EhGjOj8UUM4-Scg=h60', '₹', 'Asia/Kolkata', '2025-06-25 10:47:39', '2025-07-29 18:25:19', 8.00, 8.00);

-- --------------------------------------------------------

--
-- Table structure for table `subcategories`
--

CREATE TABLE `subcategories` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `fare` decimal(10,2) NOT NULL DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subcategories`
--

INSERT INTO `subcategories` (`id`, `category_id`, `name`, `fare`, `description`, `created_at`, `updated_at`) VALUES
(1, 1, 'E-commerce Website', 1500.00, 'Full E-commerce solution with payment gateway.', '2025-06-25 10:47:39', '2025-06-25 10:47:39'),
(2, 1, 'Portfolio Website', 500.00, 'Basic portfolio site for individuals.', '2025-06-25 10:47:39', '2025-06-25 10:47:39'),
(3, 2, 'Logo Design', 250.00, 'Custom logo creation.', '2025-06-25 10:47:39', '2025-06-25 10:47:39'),
(4, 2, 'Brochure Design', 150.00, 'Tri-fold brochure design.', '2025-06-25 10:47:39', '2025-06-25 10:47:39'),
(5, 3, 'SEO Optimization', 300.00, 'Search Engine Optimization package.', '2025-06-25 10:47:39', '2025-06-25 10:47:39'),
(6, 3, 'Social Media Campaign', 400.00, 'Managed social media advertising.', '2025-06-25 10:47:39', '2025-06-25 10:47:39'),
(7, 5, 'FORM 19', 500.00, 'Form 19 (also known as PF Form 19) is the official claim form for final settlement of your Employee Provident Fund (EPF) — used to withdraw your full PF balance (your contribution + employer\'s share + interest) when you:', '2025-06-30 18:58:41', '2025-06-30 18:58:41');

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
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `last_activity_at` datetime DEFAULT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `account_holder_name` varchar(255) DEFAULT NULL,
  `account_number` varchar(255) DEFAULT NULL,
  `ifsc_code` varchar(255) DEFAULT NULL,
  `upi_id` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`, `updated_at`, `status`, `last_activity_at`, `bank_name`, `account_holder_name`, `account_number`, `ifsc_code`, `upi_id`) VALUES
(1, 'Admin Bhavesh', 'admin@bronline.net', '$2y$10$PJK9AaSMat4Js2in655ZEOLHbxB/WMUfSFg.VpzpSBZuNGuyKPc8q', 'admin', '2025-06-25 10:47:39', '2025-06-30 15:07:26', 'active', '2025-06-30 15:07:26', NULL, NULL, NULL, NULL, NULL),
(12, 'Dipika', 'dipika@bronline.net', '$2y$10$ZG/bNi94CEEqQBmStWUar.sNtqe9Xamv74g5ijbLrbSeUmUdMH/nK', 'manager', '2025-06-28 18:19:10', '2025-06-30 20:09:48', 'active', '2025-06-30 14:10:03', NULL, NULL, NULL, NULL, NULL),
(13, 'DEO User', 'deo@bronline.net', '$2y$10$qZ9gdT1crZ7PP/eAWDjM1.cClqxLh7bQ1rccWHbx9bXz9aMQZrPMy', 'data_entry_operator', '2025-07-17 11:54:28', '2025-07-29 19:12:25', 'active', NULL, 'Canara', '123456789', '123456789', 'CNRB000007', '9067090369@ybl');

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

--
-- Dumping data for table `withdrawal_requests`
--

INSERT INTO `withdrawal_requests` (`id`, `deo_id`, `amount`, `request_date`, `status`, `transaction_number`, `admin_comments`, `bank_name`, `account_holder_name`, `account_number`, `ifsc_code`, `upi_id`, `processed_by_admin_id`, `processed_at`) VALUES
(1, 13, 8.00, '2025-07-29 18:31:43', 'paid', '1234', 'paida', NULL, NULL, NULL, NULL, NULL, 1, '2025-07-29 18:36:20');

-- --------------------------------------------------------

--
-- Table structure for table `work_assignments`
--

CREATE TABLE `work_assignments` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `assigned_to_user_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `subcategory_id` int(11) NOT NULL,
  `work_description` text NOT NULL,
  `deadline` date NOT NULL,
  `completed_at` datetime DEFAULT NULL,
  `fee` decimal(10,2) NOT NULL,
  `fee_mode` enum('online','cash','credit_card','pending') NOT NULL DEFAULT 'pending',
  `maintenance_fee` decimal(10,2) DEFAULT 0.00,
  `maintenance_fee_mode` enum('online','cash','credit_card','pending') NOT NULL DEFAULT 'pending',
  `status` enum('pending','in_process','completed','cancelled') NOT NULL DEFAULT 'pending',
  `payment_status` enum('pending','paid_full','paid_partial','refunded') NOT NULL DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `user_notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `completion_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `work_assignments`
--

INSERT INTO `work_assignments` (`id`, `client_id`, `assigned_to_user_id`, `category_id`, `subcategory_id`, `work_description`, `deadline`, `completed_at`, `fee`, `fee_mode`, `maintenance_fee`, `maintenance_fee_mode`, `status`, `payment_status`, `admin_notes`, `user_notes`, `created_at`, `updated_at`, `completion_date`) VALUES
(12, 5, 12, 3, 5, 'Complite', '2025-06-30', NULL, 300.00, 'online', 0.00, 'pending', 'pending', 'pending', '', NULL, '2025-06-30 15:12:23', '2025-06-30 15:12:23', NULL),
(14, 10, 12, 5, 7, 'Form 19', '2025-06-30', '2025-06-30 20:04:45', 500.00, 'online', 0.00, 'pending', 'completed', 'pending', '', '<br />\r\n<b>Deprecated</b>:  htmlspecialchars(): Passing null to parameter #1 ($string) of type string is deprecated in <b>C:\\xampp\\htdocs\\project_management_system\\user\\update_task.php</b> on line <b>311</b><br />', '2025-06-30 18:59:10', '2025-06-30 20:04:45', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

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
-- Indexes for table `recruitment_posts`
--
ALTER TABLE `recruitment_posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `submitted_by_user_id` (`submitted_by_user_id`),
  ADD KEY `approved_by_user_id` (`approved_by_user_id`);

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
  ADD KEY `subcategory_id` (`subcategory_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- AUTO_INCREMENT for table `recruitment_posts`
--
ALTER TABLE `recruitment_posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `subcategories`
--
ALTER TABLE `subcategories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `withdrawal_requests`
--
ALTER TABLE `withdrawal_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `work_assignments`
--
ALTER TABLE `work_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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
  ADD CONSTRAINT `work_assignments_ibfk_4` FOREIGN KEY (`subcategory_id`) REFERENCES `subcategories` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

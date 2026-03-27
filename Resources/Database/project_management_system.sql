-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Mar 18, 2026 at 03:26 PM
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

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `client_name`, `client_phone`, `client_email`, `category_id`, `user_id`, `task_id`, `appointment_date`, `appointment_time`, `notes`, `document_path`, `photo_path`, `status`, `created_at`, `transfer_status`, `transferred_to_user_id`, `transfer_comments`, `transfer_requested_at`, `transfer_rejection_reason`, `transfer_from_user_id`) VALUES
(23, 'Marie Monsoor', '628434735', 'marie.monsoor@gmail.com', 8, 27, NULL, '0000-00-00', '00:00:00', 'Stop losing money on visitors who leave websites and start automatically redirecting that \"wasted\" traffic toward your own offers for as little as five cents per person. Exit Traffic Network provides a completely hands-free, set-it-and-forget-it system that captures real desktop traffic from established platforms while you sleep\r\nhttps://www.youtube.com/watch?v=3013L2Yxg1k', NULL, NULL, 'cancelled', '2026-02-17 01:34:47', 'none', NULL, NULL, NULL, NULL, NULL),
(24, 'Jodie Schrantz', '362661707', 'schrantz.jodie@gmail.com', 5, 30, NULL, '0000-00-00', '00:00:00', 'Scale your authority and warm up high-value leads on LinkedIn by generating tone-perfect, professional comments in just five seconds,. Stay consistently visible to your target audience and grow your business safely without bots for only $5 a month,.\r\n\r\nhttps://www.youtube.com/watch?v=fnBU0pu0F_s\r\n', NULL, NULL, 'pending', '2026-02-22 14:13:38', 'none', NULL, NULL, NULL, NULL, NULL);

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
(3, 'Digital Marketing', 'Online marketing strategies and execution.', '', 1, '2025-06-25 10:47:39', '2026-02-04 06:04:06'),
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
(21, 'alertbronline@gmail.com', '9870087387', '', NULL, NULL, '2025-09-09 17:42:01', '2025-09-09 17:42:01', 'Pranil', NULL, '', 'Active', NULL, 'pending', NULL, NULL),
(29, '', '0000000000', '', NULL, NULL, '2026-02-02 08:06:49', '2026-02-02 08:06:49', 'OTHER', '', '', 'Active', NULL, 'pending', NULL, NULL);

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
(18, 'Demo Person', '9870087387', NULL, 'bronline234@gmail.com', 5, 'appointment', '2025-09-15 00:41:55', '2025-09-15 00:41:55'),
(19, 'Krishna Deval', '9825722014', '', '', NULL, 'form', '2026-02-02 08:23:01', '2026-02-02 08:23:01'),
(20, 'Krunal Solanki', '9638244211', '', '', NULL, 'form', '2026-02-02 08:24:00', '2026-02-02 08:24:00'),
(21, 'Priyal Solanki', '9638244211', '', '', NULL, 'form', '2026-02-02 08:24:14', '2026-02-02 08:24:14'),
(22, 'Od Umesh', '7600354202', '', '', NULL, 'form', '2026-02-02 12:25:26', '2026-02-02 12:25:26'),
(23, 'Shyam Tiwari', '9978800310', '', '', 29, 'form', '2026-02-04 06:05:22', '2026-02-04 06:05:22'),
(24, 'Chauhan Puranmal', '9879609112', '', '', 29, 'form', '2026-02-04 06:16:53', '2026-02-04 06:16:53'),
(25, 'Prince Gohil', '8866349187', '', '', 29, 'form', '2026-02-04 06:59:57', '2026-02-04 06:59:57'),
(26, 'Vataliya Maheshkumar', '8320612067', '', '', 29, 'form', '2026-02-04 07:42:56', '2026-02-04 07:42:56'),
(27, 'Chuahan Hiteshkumar', '9054800665', '', '', 29, 'form', '2026-02-06 06:40:46', '2026-02-06 06:40:46');

-- --------------------------------------------------------

--
-- Table structure for table `digital_studio_logs`
--

CREATE TABLE `digital_studio_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `service_type` varchar(100) NOT NULL COMMENT 'Poster, Resume, Smart Card etc.',
  `cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `digital_studio_logs`
--

INSERT INTO `digital_studio_logs` (`id`, `user_id`, `service_type`, `cost`, `created_at`) VALUES
(1, 28, 'Poster Design', 20.00, '2026-03-16 13:44:12'),
(2, 28, 'Smart Card (PVC)', 20.00, '2026-03-16 14:05:48'),
(3, 28, 'Smart Card PVC (Auto-Detect)', 20.00, '2026-03-16 14:11:06'),
(4, 28, 'Smart Card (Pro Edited)', 20.00, '2026-03-16 14:17:42'),
(5, 28, 'Smart Card PVC (Pro Edit)', 20.00, '2026-03-16 14:52:33'),
(6, 28, 'Smart Card PVC', 20.00, '2026-03-16 15:00:04'),
(7, 28, 'Smart Card PVC', 20.00, '2026-03-16 15:01:39'),
(8, 28, 'Smart Card PVC', 20.00, '2026-03-17 05:47:10'),
(9, 28, 'Smart Card PVC', 20.00, '2026-03-17 05:49:59'),
(10, 28, 'Passport Photo Maker', 20.00, '2026-03-17 07:17:16'),
(11, 28, 'Poster Design', 20.00, '2026-03-17 07:21:30'),
(12, 28, 'Smart Card PVC', 20.00, '2026-03-17 07:33:22'),
(13, 28, 'Smart Card PVC', 20.00, '2026-03-17 07:42:07'),
(14, 28, 'Smart Card PVC', 20.00, '2026-03-17 09:53:25'),
(15, 28, 'Smart Card PVC', 20.00, '2026-03-17 10:15:21'),
(16, 28, 'Smart Card PVC', 20.00, '2026-03-17 10:16:35'),
(17, 28, 'Passport Photo Maker (AI Pro)', 20.00, '2026-03-17 11:32:31'),
(18, 28, 'Smart Card PVC', 20.00, '2026-03-17 11:51:18'),
(19, 28, 'Document Convert (compress_image)', 20.00, '2026-03-17 12:31:02'),
(20, 28, 'Document Convert (compress_image)', 20.00, '2026-03-17 12:37:37'),
(21, 28, 'Document Convert (pdf_to_word)', 20.00, '2026-03-17 12:38:05'),
(22, 28, 'Document Convert (pdf_to_word)', 20.00, '2026-03-17 12:44:55'),
(23, 28, 'Document Convert (pdf_to_excel)', 20.00, '2026-03-17 12:45:58'),
(24, 28, 'Document Convert (pdf_to_word)', 20.00, '2026-03-17 12:53:25'),
(25, 28, 'Document Tool (edit_pdf)', 20.00, '2026-03-17 13:38:50'),
(26, 28, 'Smart Card PVC', 20.00, '2026-03-17 14:45:53'),
(27, 28, 'Smart Card PVC', 20.00, '2026-03-18 06:12:50');

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
(12, 'D Mart', 6924.00, 'Home Grocery', '2026-02-07', '2026-02-07 09:37:03', '2026-02-07 09:37:03'),
(13, 'Aartiben', 500.00, 'January 2026', '2026-02-10', '2026-02-10 06:36:29', '2026-02-10 06:36:29'),
(14, 'Sonara', 5000.00, '2100 Kapaya', '2026-02-10', '2026-02-10 11:16:18', '2026-02-10 11:16:18'),
(15, 'C203', 9800.00, 'Maintenance Baki Che Haji ', '2026-02-10', '2026-02-10 11:55:35', '2026-02-10 11:55:35'),
(16, 'D1 ', 9800.00, 'Maintenance Baki Che ', '2026-02-10', '2026-02-10 11:55:56', '2026-02-10 11:55:56');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `deleted_by_sender` tinyint(1) DEFAULT 0,
  `deleted_by_receiver` tinyint(1) DEFAULT 0,
  `reply_to_id` int(11) DEFAULT NULL,
  `related_task_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `sender_id`, `receiver_id`, `message`, `attachment_path`, `is_read`, `created_at`, `deleted_by_sender`, `deleted_by_receiver`, `reply_to_id`, `related_task_id`) VALUES
(211, 1, 27, 'Mesej kem delete karo cho work na pan', NULL, 1, '2026-02-05 14:51:41', 0, 1, NULL, NULL),
(212, 1, 27, 'Hu pan bhuli jaav chu ke last tame su work nu status hatu', NULL, 1, '2026-02-05 14:52:00', 0, 1, NULL, NULL),
(213, 1, 27, 'Hello', NULL, 1, '2026-02-05 18:42:15', 0, 1, NULL, NULL),
(214, 1, 27, 'Hi', NULL, 1, '2026-02-05 19:05:03', 0, 1, NULL, NULL),
(215, 1, 27, 'Good morning', NULL, 1, '2026-02-06 04:03:10', 0, 1, NULL, NULL),
(216, 27, 1, 'Good morning', NULL, 1, '2026-02-06 04:05:56', 1, 0, NULL, NULL),
(217, 27, 1, 'Ok work to online work ni vat karjo ok', NULL, 1, '2026-02-06 04:06:23', 1, 0, NULL, NULL),
(218, 27, 1, 'Have nai karu delete', NULL, 1, '2026-02-06 04:06:30', 1, 0, NULL, NULL),
(219, 27, 1, 'Tame aa seen kari ne delete kari dejo', NULL, 1, '2026-02-06 04:06:47', 1, 0, NULL, NULL),
(220, 27, 1, 'Na mansi na paisa nai aayaw', NULL, 1, '2026-02-06 04:06:55', 1, 0, NULL, NULL),
(221, 1, 27, 'ha ok', NULL, 1, '2026-02-06 05:26:37', 0, 1, NULL, NULL),
(222, 1, 27, 'ha kari didha', NULL, 1, '2026-02-06 05:27:21', 0, 1, 219, NULL),
(223, 1, 27, 'saru hamna work nu kahu tamne', NULL, 1, '2026-02-06 05:27:55', 0, 1, NULL, NULL),
(224, 1, 27, 'ane ha happy marriage anniversary 👏', NULL, 1, '2026-02-06 05:28:40', 0, 1, NULL, NULL),
(225, 27, 1, 'Thanks', NULL, 1, '2026-02-06 05:31:01', 1, 0, NULL, NULL),
(226, 27, 1, 'Thankyou', NULL, 1, '2026-02-06 05:31:01', 1, 0, NULL, NULL),
(227, 27, 1, 'Ok', NULL, 1, '2026-02-06 05:31:08', 1, 0, NULL, NULL),
(228, 1, 27, 'welcome', NULL, 1, '2026-02-06 05:32:00', 0, 1, NULL, NULL),
(229, 1, 27, 'number apo mansi no mane', NULL, 1, '2026-02-06 05:32:09', 0, 1, NULL, NULL),
(230, 1, 27, 'have pehla badha jode paisaj lai levana', NULL, 1, '2026-02-06 05:33:56', 0, 1, NULL, NULL),
(231, 1, 27, 'portal ma mansi ni vigat nathi', NULL, 1, '2026-02-06 05:33:57', 0, 1, NULL, NULL),
(232, 1, 27, 'hello', NULL, 1, '2026-02-06 06:04:11', 0, 1, NULL, NULL),
(233, 1, 27, 'ek vaar tame vaat kari jovo nai to mane number apo', NULL, 1, '2026-02-06 06:22:47', 0, 1, NULL, NULL),
(234, 27, 1, 'Saru', NULL, 1, '2026-02-06 07:00:32', 1, 0, NULL, NULL),
(235, 27, 1, 'Ok', NULL, 1, '2026-02-06 07:43:24', 1, 0, NULL, NULL),
(236, 1, 27, 'thayi vaat mansi jode', NULL, 1, '2026-02-06 08:24:54', 0, 1, NULL, NULL),
(237, 27, 1, 'Na', NULL, 1, '2026-02-06 10:39:37', 1, 0, NULL, NULL),
(238, 27, 1, 'Ok', NULL, 1, '2026-02-06 12:35:55', 1, 0, NULL, NULL),
(239, 27, 1, 'Mansi na Paisa tamne mokla che evu Kidhu', NULL, 1, '2026-02-07 05:11:23', 1, 0, NULL, NULL),
(240, 27, 1, 'Ne sorry mare bija kam nai Thai', NULL, 1, '2026-02-07 05:11:27', 1, 0, NULL, NULL),
(241, 27, 1, 'Thankyou', NULL, 1, '2026-02-07 05:12:31', 1, 0, NULL, NULL),
(242, 27, 1, 'Hitesh nu payment tame lai lejo claim thai gayo che', NULL, 1, '2026-02-07 05:13:32', 1, 0, NULL, NULL),
(243, 1, 27, 'Ha mansi nu payment avi gayu che', NULL, 1, '2026-02-07 05:21:50', 0, 1, NULL, NULL),
(244, 1, 27, 'Kem su thayu achanak saru evu to kaam karta hata tame', NULL, 1, '2026-02-07 05:22:17', 0, 1, NULL, NULL),
(245, 27, 1, 'Bolo', NULL, 1, '2026-02-07 05:33:23', 1, 0, NULL, NULL),
(246, 27, 1, 'Pan have nai thay', NULL, 1, '2026-02-07 05:33:55', 1, 0, NULL, NULL),
(247, 1, 27, 'na baka yrr work to karo tamne time male eh hisab thi karo', NULL, 1, '2026-02-07 05:34:54', 0, 1, NULL, NULL),
(248, 1, 27, 'karan to kaho su thayu paisa no issue hoi to kai do ocha pade che?', NULL, 1, '2026-02-07 05:35:33', 0, 1, 246, NULL),
(249, 1, 27, 'tamne koi na pade che ? ke tamari office ma problem thai che ? tame mane kale call kari ne kahyu hatu te login chalu rahi gayu tu mins ke kai issue che?? atlist mane sachu javab to apo hu kai koi force nai karto ke tamej karo', NULL, 1, '2026-02-07 05:38:18', 0, 1, NULL, NULL),
(250, 27, 1, 'Kai nai', NULL, 1, '2026-02-07 05:39:26', 1, 0, NULL, NULL),
(251, 1, 27, 'evu na hoi tamne achanak su thai jai che kai khabar nai padti. koi tamne mara vise kai kahe che ke su?', NULL, 1, '2026-02-07 05:40:48', 0, 1, NULL, NULL),
(252, 27, 1, 'Na', NULL, 1, '2026-02-07 05:41:01', 1, 0, NULL, NULL),
(253, 1, 27, 'to sachu bolo ne su thayu che', NULL, 1, '2026-02-07 05:41:21', 0, 1, NULL, NULL),
(254, 27, 1, 'Kai nathi thayu', NULL, 1, '2026-02-07 05:41:59', 1, 0, NULL, NULL),
(255, 27, 1, 'Thank you', NULL, 1, '2026-02-07 05:42:00', 1, 0, NULL, NULL),
(256, 1, 27, 'to kem work nathi karvu mane tamara jevu koi visvas valu atyr sudhi nai malyu', NULL, 1, '2026-02-07 05:42:38', 0, 1, NULL, NULL),
(257, 27, 1, 'Have nai thay atle', NULL, 1, '2026-02-07 05:44:14', 1, 0, NULL, NULL),
(258, 1, 27, 'jovo hu tamne force nai karto .. tamaru ID password chalu rehshe tamare bhale kaam na karvu hoi .. jiyre pan ichha thai to kehjo hu tamne work apis. badho support pan apis.  have tamare je iccha hoi eeh karjo', NULL, 1, '2026-02-07 05:44:18', 0, 1, NULL, NULL),
(259, 27, 1, 'Mari koi ichha nathi have', NULL, 1, '2026-02-07 05:45:44', 1, 0, NULL, NULL),
(260, 1, 27, 'salary par work karvu che to bolo 25K monthly apu bus sari rete kaam karso to hu tamne badhu sikhvadi dayis pachi as you wish hu 9 month sudhij chu pachi aborad settled thav chu .. as yous wish have tamaru nasib', NULL, 1, '2026-02-07 05:46:04', 0, 1, NULL, NULL),
(261, 1, 27, 'ok abhar apno', NULL, 1, '2026-02-07 05:46:38', 0, 1, 259, NULL),
(262, 27, 1, 'Thank you', NULL, 1, '2026-02-07 05:53:21', 1, 0, NULL, NULL),
(263, 27, 1, 'Bolo', NULL, 1, '2026-02-07 05:54:56', 1, 0, NULL, NULL),
(264, 1, 27, 'su bolu kaam sivai koi vaat na thai mare', NULL, 1, '2026-02-07 05:56:53', 0, 1, NULL, NULL),
(265, 27, 1, 'Kem', NULL, 1, '2026-02-07 05:57:18', 1, 0, NULL, NULL),
(266, 1, 27, 'tame j kahyu che', NULL, 1, '2026-02-07 05:57:29', 0, 1, NULL, NULL),
(267, 27, 1, 'Su', NULL, 1, '2026-02-07 05:57:38', 1, 0, NULL, NULL),
(268, 1, 27, 'kaam sivai koi vaat na thai', NULL, 1, '2026-02-07 05:57:49', 0, 1, NULL, NULL),
(269, 27, 1, 'Ok', NULL, 1, '2026-02-07 05:58:03', 1, 0, NULL, NULL),
(270, 1, 27, 'ane ha tame jatej vakil kari dejo pachi khotu preeti ben mane kahe ane hu gothvu', NULL, 1, '2026-02-07 05:58:14', 0, 1, NULL, NULL),
(271, 27, 1, 'Saru', NULL, 1, '2026-02-07 05:58:31', 1, 0, NULL, NULL),
(272, 1, 27, 'tamare je issue hoi tame jatej have soul kari dejo kem ke mane tamaru nature nai saru lagtu have', NULL, 1, '2026-02-07 05:58:41', 0, 1, NULL, NULL),
(273, 27, 1, 'Su thayu', NULL, 1, '2026-02-07 05:58:59', 1, 0, NULL, NULL),
(274, 1, 27, 'tamare husband na ghare kiyre javanu che ?', NULL, 1, '2026-02-07 05:59:14', 0, 1, NULL, NULL),
(275, 27, 1, 'Kem', NULL, 1, '2026-02-07 05:59:38', 1, 0, NULL, NULL),
(276, 1, 27, 'tamare javu che evu sabhlyu main tamara mummy ne modhe thi', NULL, 1, '2026-02-07 05:59:57', 0, 1, NULL, NULL),
(277, 27, 1, 'Kam se kai', NULL, 1, '2026-02-07 05:59:58', 1, 0, NULL, NULL),
(278, 27, 1, 'Bolo', NULL, 1, '2026-02-07 06:00:38', 1, 0, NULL, NULL),
(279, 1, 27, 'are ben mare koi kaam nai pan tamari je icha hoi family ne kai ne kari do to eeh bichara tention mukt rahe', NULL, 1, '2026-02-07 06:00:41', 0, 1, 277, NULL),
(280, 27, 1, 'Bolo', NULL, 1, '2026-02-07 06:01:16', 1, 0, NULL, NULL),
(281, 1, 27, 'tamej cho ke biju koi mesej kare che mane??', NULL, 1, '2026-02-07 06:01:32', 0, 1, NULL, NULL),
(282, 27, 1, 'Hu j su', NULL, 1, '2026-02-07 06:01:46', 1, 0, NULL, NULL),
(283, 1, 27, 'language differ che tamari', NULL, 1, '2026-02-07 06:02:02', 0, 1, NULL, NULL),
(284, 1, 27, 'tamara office ma kai chale che ke su tame badhu koi sathe share karo cho??', NULL, 1, '2026-02-07 06:02:36', 0, 1, NULL, NULL),
(285, 27, 1, 'Na have', NULL, 1, '2026-02-07 06:03:16', 1, 0, NULL, NULL),
(286, 1, 27, 'mane kai to problem lage che tame real nathi biju koi vaat kari rahyu che', NULL, 1, '2026-02-07 06:03:22', 0, 1, NULL, NULL),
(287, 1, 27, 'jovo aah maru softwer ek dam secure hoi che main pote banayu che ghana paisa kharch thaya che ahma mare ane main special kaam mate banayu che ane ahh technical job mate hu UK ma select pan thai gayo chu bus mara wife ane pranil na visa mate rokayo chu hu baki hu kiyrno nikli gayo hoi', NULL, 1, '2026-02-07 06:05:32', 0, 1, NULL, NULL),
(288, 27, 1, 'Huj chu', NULL, 1, '2026-02-07 06:05:42', 1, 0, NULL, NULL),
(289, 27, 1, 'Mare koi problem nai', NULL, 1, '2026-02-07 06:05:52', 1, 0, NULL, NULL),
(290, 1, 27, 'mare koi issue nai pan tame tamari life jatej mota issue create karo cho ane tamara mummy pan strict che je tamne tamaru dharyu nai karva de', NULL, 1, '2026-02-07 06:06:16', 0, 1, NULL, NULL),
(291, 1, 27, 'tamara mota sister ane nana potani marji nu kari sake che ne ehm tame pote aah na kari shake life ma potanu ek mahtva ane decision power hovi joye', NULL, 1, '2026-02-07 06:07:22', 0, 1, NULL, NULL),
(292, 1, 27, 'ha to tamare su karvu che eh kai do chalo', NULL, 1, '2026-02-07 06:08:14', 0, 1, NULL, NULL),
(293, 27, 1, 'Matlb', NULL, 1, '2026-02-07 06:08:32', 1, 0, NULL, NULL),
(294, 1, 27, 'hu preetiben ne vaat kari dayis ehto tamara matter ni', NULL, 1, '2026-02-07 06:08:40', 0, 1, NULL, NULL),
(295, 1, 27, 'tamara mummy ne pan samjavis', NULL, 1, '2026-02-07 06:08:58', 0, 1, NULL, NULL),
(296, 27, 1, 'Shu maru matter', NULL, 1, '2026-02-07 06:09:02', 1, 0, NULL, NULL),
(297, 1, 27, 'husband na ghare javu che ke divorce leva che', NULL, 1, '2026-02-07 06:09:35', 0, 1, NULL, NULL),
(298, 27, 1, 'E badhu to eloko j naki kare che ne', NULL, 1, '2026-02-07 06:09:55', 1, 0, NULL, NULL),
(299, 27, 1, 'Mare kai nai kevu ema', NULL, 1, '2026-02-07 06:10:07', 1, 0, NULL, NULL),
(300, 1, 27, 'ha to tamaro su decision che', NULL, 1, '2026-02-07 06:10:34', 0, 1, NULL, NULL),
(301, 27, 1, 'Sorry mare kam nai thai bus ej keva nu hatu mare', NULL, 1, '2026-02-07 06:10:47', 1, 0, NULL, NULL),
(302, 1, 27, 'tamari life che ne tamare kai nai kevu', NULL, 1, '2026-02-07 06:10:59', 0, 1, NULL, NULL),
(303, 1, 27, 'ha ehto khabar che ke tamne koi mara vise kaan fusani kare che kato kai chali rahyu che tamari jode ena karane aah karo cho', NULL, 1, '2026-02-07 06:11:39', 0, 1, 301, NULL),
(304, 27, 1, 'Na evu kai nai', NULL, 1, '2026-02-07 06:12:13', 1, 0, NULL, NULL),
(305, 1, 27, 'aje sanje avanu che vakil ne malva ke nai?', NULL, 1, '2026-02-07 06:12:53', 0, 1, NULL, NULL),
(306, 27, 1, 'Matlb', NULL, 1, '2026-02-07 06:13:06', 1, 0, NULL, NULL),
(307, 1, 27, 'tamne badhi khabar che have', NULL, 1, '2026-02-07 06:13:21', 0, 1, NULL, NULL),
(308, 27, 1, 'Mare koi vat nai thai evi koi vakil ni', NULL, 1, '2026-02-07 06:13:28', 1, 0, NULL, NULL),
(309, 1, 27, 'tamne ghare vaat na thyi hoi evu banej nai', NULL, 1, '2026-02-07 06:13:42', 0, 1, NULL, NULL),
(310, 27, 1, 'Mare koi vat nai Thai', NULL, 1, '2026-02-07 06:14:00', 1, 0, NULL, NULL),
(311, 1, 27, 'To su job par cho tame', NULL, 1, '2026-02-07 06:14:11', 0, 1, NULL, NULL),
(312, 1, 27, 'Su karvanu che ehj khabar nai tamne to', NULL, 1, '2026-02-07 06:14:41', 0, 1, NULL, NULL),
(313, 27, 1, 'Na nai khabar', NULL, 1, '2026-02-07 06:15:07', 1, 0, NULL, NULL),
(314, 1, 27, 'Tamne pressor ape che tamaro husband ..tamne leva to avana hata ..ane sachu bolo tame kale maliya ne tamara husband ne anniversary ma', NULL, 1, '2026-02-07 06:15:48', 0, 1, NULL, NULL),
(315, 27, 1, 'E badhani tamre lod leva ni jarurt nai', NULL, 1, '2026-02-07 06:17:30', 1, 0, NULL, NULL),
(316, 1, 27, 'hu kai load nai leto ok', NULL, 1, '2026-02-07 06:17:49', 0, 1, NULL, NULL),
(317, 27, 1, 'Hu mari life ma shu Karu chu Shu Nai e', NULL, 1, '2026-02-07 06:17:53', 1, 0, NULL, NULL),
(318, 1, 27, 'tamara mummy mane badhi vaat kari hu bus ehmni help karva mangu chu tamari nai', NULL, 1, '2026-02-07 06:18:09', 0, 1, NULL, NULL),
(319, 1, 27, 'Ha to karo hu koi interface nathi karto just help karva magto pan ehh pan have hu nai kahu', NULL, 1, '2026-02-07 06:19:09', 0, 1, NULL, NULL),
(320, 1, 27, 'Get well soon', NULL, 1, '2026-02-07 06:19:31', 0, 1, NULL, NULL),
(321, 1, 27, 'Tame saav bagdi gaya cho pehla jene olkhto ehh bilkul nathi tame', NULL, 1, '2026-02-07 06:20:13', 0, 1, NULL, NULL),
(322, 27, 1, 'Ok thankyou', NULL, 1, '2026-02-07 06:21:39', 1, 0, NULL, NULL),
(323, 1, 27, 'Work kari sakso ke nai last vaar puchu chu ..mane biji koi vaat ma intrrst nai', NULL, 1, '2026-02-07 06:22:12', 0, 1, NULL, NULL),
(324, 1, 27, 'Maan na mago mari jode ..swechik sari life banava mago cho to work maru apel tamari life banavse', NULL, 1, '2026-02-07 06:22:58', 0, 1, NULL, NULL),
(325, 1, 27, 'Hello tamara portal ma task che te jayaben kari ne ehma transfer nu opsan avse ehma transfer kari do ne badha', NULL, 1, '2026-02-07 06:40:07', 0, 1, NULL, NULL),
(326, 27, 1, 'Ok', NULL, 1, '2026-02-07 07:10:14', 1, 0, NULL, NULL),
(327, 1, 27, 'Thankx', NULL, 1, '2026-02-07 07:21:41', 0, 1, NULL, NULL),
(328, 27, 1, 'Transfer done', NULL, 1, '2026-02-07 07:28:09', 1, 0, NULL, NULL),
(329, 1, 27, 'ok hu puchi lav kem ke tamaro login hu use nai kari saku', NULL, 1, '2026-02-07 07:33:42', 0, 1, NULL, NULL),
(330, 1, 27, 'hu bandh pan na kari saku kem ke main user delete nu koi opsanj nai apyu te bov secure hoi che.. tame login use karo ke na karo chalse ahma koi tamaru naam nathi ke tamaru ghanay', NULL, 1, '2026-02-07 07:36:27', 0, 1, NULL, NULL),
(331, 27, 1, 'Ok', NULL, 1, '2026-02-07 07:46:11', 1, 0, NULL, NULL),
(332, 1, 27, 'thankx', NULL, 1, '2026-02-07 07:47:22', 0, 1, NULL, NULL),
(333, 27, 1, 'Ok thankyou', NULL, 1, '2026-02-07 07:48:18', 1, 0, NULL, NULL),
(334, 1, 27, 'welcome kai biju kai help joye to kehjo joke tame mota manas cho amari help na lo', NULL, 1, '2026-02-07 07:53:28', 0, 1, NULL, NULL),
(335, 1, 28, 'hello', NULL, 1, '2026-02-09 13:57:24', 1, 0, NULL, NULL),
(336, 1, 27, 'hi', NULL, 1, '2026-02-19 11:46:54', 0, 0, NULL, NULL),
(337, 1, 27, 'hello', NULL, 0, '2026-02-21 06:24:29', 0, 0, NULL, NULL);

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
(19, 1, 'Task #39 has been submitted for verification.', '?page=edit_task&id=39', 0, '2025-09-14 23:21:26'),
(21, 1, 'Task #39 has been submitted for verification.', '?page=edit_task&id=39', 0, '2025-09-14 23:37:54'),
(27, 1, 'Task #40 has been submitted for verification.', '?page=edit_task&id=40', 0, '2025-09-15 00:33:51'),
(30, 1, 'Task #41 has been submitted for verification.', '?page=edit_task&id=41', 0, '2025-09-15 00:42:25'),
(38, 1, 'Task #43 has been submitted for verification.', '?page=edit_task&id=43', 0, '2026-02-01 22:20:43'),
(41, 1, 'Task #44 submitted by freelancer.', '?page=edit_task&id=44', 0, '2026-02-01 23:25:21'),
(44, 1, 'Task #45 submitted by freelancer.', '?page=edit_task&id=45', 0, '2026-02-01 23:31:57'),
(47, 1, 'Task #46 submitted by freelancer.', '?page=edit_task&id=46', 0, '2026-02-02 01:04:20'),
(49, 1, 'Task #47 submitted by freelancer.', '?page=edit_task&id=47', 0, '2026-02-02 01:10:54'),
(50, 27, 'New task assigned.', '?page=my_freelancer_tasks', 0, '2026-02-02 08:27:04'),
(51, 27, 'New task assigned.', '?page=my_freelancer_tasks', 0, '2026-02-02 08:31:14'),
(52, 27, 'New task assigned.', '?page=my_freelancer_tasks', 0, '2026-02-02 08:38:26'),
(53, 27, 'New task assigned.', '?page=my_freelancer_tasks', 0, '2026-02-02 12:27:34'),
(54, 1, 'Task #49 submitted by freelancer.', '?page=edit_task&id=49', 0, '2026-02-03 07:38:41'),
(55, 1, 'Task #49 submitted by freelancer.', '?page=edit_task&id=49', 0, '2026-02-03 10:27:32'),
(56, 27, 'New task assigned.', '?page=my_freelancer_tasks', 0, '2026-02-04 06:11:01'),
(57, 27, 'New task assigned.', '?page=my_freelancer_tasks', 0, '2026-02-04 06:18:00'),
(58, 27, 'New task assigned.', '?page=my_freelancer_tasks', 0, '2026-02-04 07:01:55'),
(59, 27, 'New task assigned.', '?page=my_freelancer_tasks', 0, '2026-02-04 07:45:08'),
(60, 27, 'New task assigned.', '?page=my_freelancer_tasks', 0, '2026-02-06 06:42:04'),
(61, 28, 'Your recruitment post #14 has been approved! ₹25 added to wallet.', '?page=my_recruitment_posts', 0, '2026-02-14 09:42:52'),
(62, 28, 'Your recruitment post #16 has been approved! ₹25 added to wallet.', '?page=my_recruitment_posts', 0, '2026-02-23 18:17:57');

-- --------------------------------------------------------

--
-- Table structure for table `portals`
--

CREATE TABLE `portals` (
  `id` int(11) NOT NULL,
  `domain_name` varchar(255) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `status` enum('active','suspended','expired') DEFAULT 'active',
  `expiry_date` date NOT NULL,
  `folder_path` varchar(255) NOT NULL,
  `theme_color` varchar(20) DEFAULT '#1e293b',
  `logo_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `age_limit` text DEFAULT NULL,
  `other_details_json` text DEFAULT NULL,
  `other_details_title` varchar(255) DEFAULT NULL,
  `other_details` text DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `last_date` date DEFAULT NULL,
  `exam_date` date DEFAULT NULL,
  `fee_payment_last_date` date DEFAULT NULL,
  `custom_dates_json` text DEFAULT NULL,
  `custom_date_title` varchar(255) DEFAULT NULL,
  `custom_date_value` date DEFAULT NULL,
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
  `approved_at` datetime DEFAULT NULL,
  `custom_links_json` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `recruitment_posts`
--

INSERT INTO `recruitment_posts` (`id`, `job_title`, `total_vacancies`, `image_banner_url`, `eligibility_criteria`, `selection_process`, `age_limit`, `other_details_json`, `other_details_title`, `other_details`, `start_date`, `last_date`, `exam_date`, `fee_payment_last_date`, `custom_dates_json`, `custom_date_title`, `custom_date_value`, `application_fees`, `category_wise_vacancies`, `notification_url`, `apply_url`, `admit_card_url`, `official_website_url`, `exam_prediction`, `custom_fields_json`, `submitted_by_user_id`, `approval_status`, `approved_by_user_id`, `admin_comments`, `is_new_for_admin`, `created_at`, `updated_at`, `approved_at`, `custom_links_json`) VALUES
(14, 'Indian Army Agniveer Recruitment 2026', 25000, 'https://bronline.online/uploads/generated_posters/poster_699040ccdbbcb_1771061452.jpg', '12TH, 10TH, 8TH', '', NULL, NULL, NULL, NULL, '2026-02-13', '2026-04-01', '0000-00-00', '0000-00-00', NULL, NULL, NULL, 'Rs 250/- per application for all categories.', '', 'https://164.100.158.23/latest-rally-agniveer-or.htm', 'http://www.joinindianarmy.nic.in/', '#', 'http://www.joinindianarmy.nic.in/', '', '[]', 28, 'approved', 1, NULL, 0, '2026-02-14 09:34:48', '2026-02-14 09:42:52', '2026-02-14 09:42:52', '[]'),
(15, 'Reserve Bank of India RBI Assistant Recruitment 2026 ', 650, 'https://bronline.online/uploads/generated_posters/poster_6999bc32a5069_1771682866.jpg', 'Bachelor’s Degree with minimum 50% marks (pass class for SC/ST/PwBD)', '', '', '[]', NULL, NULL, '2026-02-16', '2026-03-08', '2026-04-11', '0000-00-00', '[]', NULL, NULL, 'General/OBC/EWS	₹450/- plus 18% GST\r\nSC/ST/PwBD/EXS	₹50/- intimation charges plus 18% GST\r\nRBI Staff	: Nil', '', 'https://rbidocs.rbi.org.in/rdocs/Content/PDFs/DAASSISTANT2025CCB4E54CEB5542D9987FE6D1EA51D143.PDF', 'https://ibpsreg.ibps.in/rbiafeb26/', '#', 'https://www.rbi.org.in/', '', '[]', 28, 'pending', NULL, NULL, 0, '2026-02-21 14:11:54', '2026-02-25 13:36:31', NULL, '[]'),
(16, 'Reserve Bank of India RBI Assistant Recruitment 2026 ', 5000, 'https://bronline.online/uploads/generated_posters/poster_6999bc32a5069_1771682866.jpg', 'hfdhgf', 'fhfhfh', 'fhfhf', '[{\"title\":\"fhfh\",\"content\":\"fhfh\"},{\"title\":\"etetet\",\"content\":\"dgdg\"}]', NULL, NULL, '2026-02-01', '2026-02-23', '0000-00-00', '0000-00-00', '[]', NULL, NULL, 'dgdgdg', 'dgdgdg', '#', 'https://ibpsreg.ibps.in/rbiafeb26/', '#', '', 'dgdgdgdg', '[{\"heading\":\"dgdg\",\"content\":\"dgdg\"}]', 28, 'approved', 1, NULL, 0, '2026-02-23 17:24:43', '2026-02-23 18:17:57', '2026-02-23 18:17:57', '[]'),
(17, 'Reserve Bank of India RBI Assistant Recruitment 2026 ', 5000, 'https://bronline.online/uploads/generated_posters/poster_6999bc32a5069_1771682866.jpg', '', '', '', '[]', NULL, NULL, '2026-02-01', '2026-02-27', '0000-00-00', '0000-00-00', '[{\"title\":\"Aaa\",\"date\":\"2026-02-27\"}]', NULL, NULL, '', '', 'http://localhost/project_management_system/uploads/recruitment_docs/1772006767_notif_Ration.pdf', 'https://ibpsreg.ibps.in/rbiafeb26/', '#', 'https://www.rbi.org.in/', '', '[]', 28, 'pending', NULL, NULL, 0, '2026-02-25 13:36:07', '2026-02-25 13:36:31', NULL, '[]');

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
(6, 'Freelancer', '[\"reports\",\"submit_work\",\"worker_dashboard\",\"messages\",\"user_settings\"]', '[]', '2025-09-04 12:53:42'),
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
  `office_working_days` varchar(255) NOT NULL DEFAULT '1,2,3,4,5,6' COMMENT 'Comma-separated day numbers (1=Mon, 7=Sun)',
  `poster_generation_cost` decimal(10,2) NOT NULL DEFAULT 10.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `app_name`, `app_logo_url`, `currency_symbol`, `required_daily_hours`, `earning_per_approved_post`, `minimum_withdrawal_amount`, `whatsapp_business_number`, `whatsapp_api_key`, `smtp_host`, `smtp_port`, `smtp_encryption`, `smtp_username`, `smtp_password`, `smtp_from_email`, `smtp_from_name`, `office_address`, `helpline_number`, `office_start_time`, `office_end_time`, `appointment_slot_duration`, `office_working_days`, `poster_generation_cost`) VALUES
(1, 'B R Online Services', 'https://bronline.online/uploads/logo/logo_1756908164.png', '₹', 8, 25.00, 500.00, '', '', 'smtp.gmail.com', 587, 'tls', 'bronline234@gmail.com', 'pqqv ywob ixde ckbd', 'info@bronline.net', 'B R Online Services', 'D1 GF Arvind Mega Trade, Opp Arvind Avishkar, Naroda Road, Ahmedabad - 382345', '+91 9870087387', '10:30:00', '18:00:00', 30, '1,2,3,4,5,6', 10.00);

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
(6, 3, 'Social Media Campaign', 400.00, 0.00, 0, 'Managed social media advertising.', '2025-06-25 10:47:39', '2025-06-25 10:47:39'),
(7, 5, 'FORM 19', 500.00, 0.00, 0, 'Form 19 (also known as PF Form 19) is the official claim form for final settlement of your Employee Provident Fund (EPF) — used to withdraw your full PF balance (your contribution + employer\'s share + interest) when you:', '2025-06-30 18:58:41', '2025-06-30 18:58:41'),
(8, 8, 'Appointment', 150.00, 0.00, 0, '', '2025-09-06 15:35:30', '2025-09-06 15:35:54'),
(11, 5, 'FORM 31', 300.00, 0.00, 0, 'Advance Claim ', '2026-02-02 08:29:41', '2026-02-02 08:29:41'),
(12, 5, 'FORM 10D', 1000.00, 0.00, 0, 'Monthely Penstion Form EPFO', '2026-02-04 06:03:55', '2026-02-04 06:03:55'),
(13, 5, 'FORM 13', 150.00, 0.00, 0, 'Transfer One PF Company to Current Company ', '2026-02-04 07:43:38', '2026-02-04 07:43:38');

-- --------------------------------------------------------

--
-- Table structure for table `subscription_plans`
--

CREATE TABLE `subscription_plans` (
  `id` int(11) NOT NULL,
  `plan_name` varchar(100) NOT NULL,
  `monthly_price` decimal(10,2) NOT NULL,
  `yearly_price` decimal(10,2) NOT NULL,
  `features` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `portal_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `txn_type` enum('recharge','subscription_fee','service_deduction') NOT NULL,
  `payment_gateway_id` varchar(100) DEFAULT NULL,
  `status` enum('success','pending','failed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
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
  `bank_name` varchar(255) DEFAULT NULL,
  `account_holder_name` varchar(255) DEFAULT NULL,
  `account_number` varchar(255) DEFAULT NULL,
  `ifsc_code` varchar(255) DEFAULT NULL,
  `upi_id` varchar(255) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `salary` decimal(10,2) NOT NULL DEFAULT 0.00,
  `balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `last_activity` datetime DEFAULT NULL,
  `last_activity_at` datetime DEFAULT NULL,
  `poster_points` int(11) DEFAULT 0,
  `custom_poster_rate` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `role_id`, `created_at`, `updated_at`, `status`, `bank_name`, `account_holder_name`, `account_number`, `ifsc_code`, `upi_id`, `profile_picture`, `salary`, `balance`, `last_activity`, `last_activity_at`, `poster_points`, `custom_poster_rate`) VALUES
(1, 'Admin BR', 'admin@bronline.net', '$2y$10$GfqKb1il1.W0ZvmV4l0yPuuDIBhyL.zu4DGSorhpvFLO6n.nChzQO', 'admin', 1, '2025-06-25 10:47:39', '2026-03-18 19:46:48', 'active', NULL, NULL, NULL, NULL, NULL, 'uploads/profile_pictures/user_1_1756911138.png', 0.00, 0.00, '2026-03-18 19:46:48', '2026-02-02 07:06:11', 0, NULL),
(27, 'DP Freelancer', 'dp@bronline.net', '$2y$10$ysAKPFMaCzjfGo6BIgT1ueDXJKMxyE75M0U3i6bUs9iSH7EAiSiYG', 'assistant', 6, '2026-02-02 08:04:28', '2026-02-21 05:56:11', 'active', NULL, NULL, NULL, NULL, NULL, NULL, 0.00, -50.00, '2026-02-21 05:56:11', NULL, 0, NULL),
(28, 'Jaya Freelancer', 'jaya@bronline.net', '$2y$12$zXzhFtaNbmE/2i5JXgPgvOEnKMPqsLxkbH.PMM6IKKN6OhQitWDr2', 'assistant', 6, '2026-02-07 06:35:58', '2026-03-18 11:42:50', 'active', NULL, NULL, NULL, NULL, NULL, 'uploads/profile_pictures/user_28_1770529245.jpeg', 0.00, 990.00, '2026-03-18 11:41:37', NULL, 0, 20.00),
(30, 'Mujib', 'Mujib@bronline.net', '$2y$12$5E5WcT/vPCK1cR1bdmN6.OYtdQj9rW/qqMcTyNXtAs54f/5TLa91q', 'assistant', 6, '2026-02-21 09:57:44', '2026-02-21 09:57:44', 'active', NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, NULL, NULL, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `wallet_recharge_requests`
--

CREATE TABLE `wallet_recharge_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `screenshot_path` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wallet_recharge_requests`
--

INSERT INTO `wallet_recharge_requests` (`id`, `user_id`, `amount`, `screenshot_path`, `status`, `created_at`) VALUES
(1, 28, 500.00, 'proof_28_1773667446.webp', 'approved', '2026-03-16 13:24:06'),
(2, 28, 20.00, 'proof_28_1773669276.webp', 'approved', '2026-03-16 13:54:36'),
(3, 28, 1000.00, 'proof_28_1773813934.png', 'approved', '2026-03-18 06:05:34');

-- --------------------------------------------------------

--
-- Table structure for table `wallet_transactions`
--

CREATE TABLE `wallet_transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('credit','debit') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wallet_transactions`
--

INSERT INTO `wallet_transactions` (`id`, `user_id`, `type`, `amount`, `description`, `created_at`) VALUES
(1, 28, 'credit', 500.00, 'Online Wallet Recharge Approved', '2026-03-16 13:43:25'),
(2, 28, 'credit', 20.00, 'Online Wallet Recharge Approved', '2026-03-16 13:54:52'),
(3, 28, 'credit', 1000.00, 'Online Wallet Recharge Approved', '2026-03-18 06:05:53');

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
  `payment_collected_by` enum('none','company','self') DEFAULT 'none',
  `admin_notes` text DEFAULT NULL,
  `user_notes` text DEFAULT NULL,
  `task_attachment_path` varchar(255) DEFAULT NULL,
  `completion_receipt_path` varchar(255) DEFAULT NULL,
  `work_file` varchar(255) DEFAULT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `transfer_status` enum('none','pending','accepted','rejected') NOT NULL DEFAULT 'none',
  `transferred_to_user_id` int(11) DEFAULT NULL,
  `transfer_requested_at` datetime DEFAULT NULL,
  `transfer_comments` text DEFAULT NULL,
  `transfer_rejection_reason` text DEFAULT NULL,
  `transfer_from_user_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `completion_date` date DEFAULT NULL,
  `payment_status` varchar(50) DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `work_assignments`
--

INSERT INTO `work_assignments` (`id`, `client_id`, `assigned_to_user_id`, `customer_id`, `assigned_by_user_id`, `category_id`, `subcategory_id`, `work_description`, `attachment_path`, `deadline`, `completed_at`, `fee`, `fee_mode`, `maintenance_fee`, `maintenance_fee_mode`, `discount`, `task_price`, `status`, `payment_collected_by`, `admin_notes`, `user_notes`, `task_attachment_path`, `completion_receipt_path`, `work_file`, `is_verified`, `transfer_status`, `transferred_to_user_id`, `transfer_requested_at`, `transfer_comments`, `transfer_rejection_reason`, `transfer_from_user_id`, `created_at`, `updated_at`, `completion_date`, `payment_status`) VALUES
(49, 29, 27, 19, 1, 5, 11, '101208109423 \r\nPass : Abcd@2024 / Mehta@2025', NULL, '2026-02-28', NULL, 300.00, 'pending', 0.00, '', 0.00, 150.00, 'verified_completed', 'self', '', '', NULL, 'uploads/task_receipts/receipt_49_1770114452.png', NULL, 1, 'none', NULL, NULL, NULL, NULL, NULL, '2026-02-02 08:31:14', '2026-02-03 10:36:47', '2026-02-03', 'pending'),
(50, 29, 28, 20, 1, 5, 7, 'UAN : 101210375813\r\nPass : Viraansh@123', NULL, '2026-02-28', NULL, 500.00, 'pending', 0.00, '', 100.00, 200.00, 'pending', 'none', NULL, '', NULL, NULL, NULL, 0, 'none', NULL, NULL, NULL, NULL, NULL, '2026-02-02 08:38:26', '2026-02-07 07:25:54', NULL, 'pending'),
(51, 29, 27, 22, 1, 5, 11, 'UAN : 101488581662\r\nPass : Abcd@2026 / Abcd@2025\r\nAdvance Claim If Bank KYC will Done ', NULL, '2026-02-28', NULL, 200.00, 'online', 0.00, '', 0.00, 100.00, 'verified_completed', 'none', 'Customer paid only 200 for this task ', '', NULL, NULL, NULL, 1, 'none', NULL, NULL, NULL, NULL, NULL, '2026-02-02 12:27:34', '2026-02-05 06:23:57', '2026-02-05', 'paid'),
(52, 29, 28, 23, 1, 5, 12, 'UAN : 100564519595 \r\nPass : Abcd@2025 / Abcd@2026\r\n\r\nMonthely Pention Form 10D', NULL, '2026-03-31', NULL, 2000.00, 'pending', 0.00, '', 0.00, 1000.00, 'pending', 'none', NULL, '', NULL, NULL, NULL, 0, 'none', NULL, NULL, NULL, NULL, NULL, '2026-02-04 06:11:01', '2026-02-07 07:26:28', NULL, 'pending'),
(53, 29, 28, 24, 1, 5, 12, 'UAN : 100632470238\r\nPass : Abcd@2025 / Abcd@2026\r\n\r\nMonthely Pention Form 10D ', NULL, '2026-03-31', NULL, 2000.00, 'pending', 0.00, '', 0.00, 1000.00, 'pending', 'none', NULL, '', NULL, NULL, NULL, 0, 'none', NULL, NULL, NULL, NULL, NULL, '2026-02-04 06:18:00', '2026-02-07 07:26:14', NULL, 'pending'),
(54, 29, 28, 25, 1, 5, 7, 'UAN : 100905556040\r\nPass : Abcd@2025 / Abcd@2026\r\n\r\nPF and Penstion All Settlement Form 19 and 10C', NULL, '2026-03-31', NULL, 500.00, 'pending', 0.00, '', 0.00, 250.00, 'pending', 'none', NULL, NULL, NULL, NULL, NULL, 0, 'none', NULL, NULL, NULL, NULL, NULL, '2026-02-04 07:01:55', '2026-02-07 07:26:05', NULL, 'pending'),
(55, 29, 28, 26, 1, 5, 13, 'UAN : 100661335535\r\nPass : Bansu@2007\r\n\r\nTransfer Form 13', NULL, '2026-03-29', NULL, 150.00, 'online', 0.00, '', 0.00, 75.00, 'pending', 'none', NULL, '', NULL, NULL, NULL, 0, 'none', NULL, NULL, NULL, NULL, NULL, '2026-02-04 07:45:08', '2026-02-07 07:05:24', NULL, 'paid'),
(56, 29, 28, 27, 1, 5, 11, 'UAN : 101413986147\r\nPass : Abcd@2025\r\nAdvance nu Claim Karvanu che ', NULL, '2026-02-28', NULL, 300.00, 'pending', 0.00, '', 0.00, 150.00, 'pending', 'none', NULL, '', NULL, NULL, NULL, 0, 'none', NULL, NULL, NULL, NULL, NULL, '2026-02-06 06:42:04', '2026-02-07 07:05:12', NULL, 'pending');

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
-- Indexes for table `digital_studio_logs`
--
ALTER TABLE `digital_studio_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

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
-- Indexes for table `portals`
--
ALTER TABLE `portals`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `domain_name` (`domain_name`);

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
-- Indexes for table `subscription_plans`
--
ALTER TABLE `subscription_plans`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assigned_to_user_id` (`assigned_to_user_id`),
  ADD KEY `assigned_by_user_id` (`assigned_by_user_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `wallet_recharge_requests`
--
ALTER TABLE `wallet_recharge_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  ADD PRIMARY KEY (`id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `connections`
--
ALTER TABLE `connections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `digital_studio_logs`
--
ALTER TABLE `digital_studio_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=338;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT for table `portals`
--
ALTER TABLE `portals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `recruitment_posts`
--
ALTER TABLE `recruitment_posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `subscription_plans`
--
ALTER TABLE `subscription_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `wallet_recharge_requests`
--
ALTER TABLE `wallet_recharge_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `withdrawals`
--
ALTER TABLE `withdrawals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `withdrawal_requests`
--
ALTER TABLE `withdrawal_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `work_assignments`
--
ALTER TABLE `work_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

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

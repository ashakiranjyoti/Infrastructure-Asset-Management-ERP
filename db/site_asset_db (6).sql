-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 29, 2025 at 02:00 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `site_asset_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `items_master`
--

CREATE TABLE `items_master` (
  `id` int(11) NOT NULL,
  `item_name` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `items_master`
--

INSERT INTO `items_master` (`id`, `item_name`, `description`, `is_active`) VALUES
(1, 'Kitkat Pump (RYB)', 'Kitkat Pump (RYB)', 1),
(2, 'Actuator bypass', 'Actuator bypass', 1),
(3, 'RTU Control Panel', 'RTU Control Panel', 1),
(4, 'Kitkat Switch Board', 'Kitkat Switch Board', 1),
(6, 'RTU Panel', 'RTU Panel', 1);

-- --------------------------------------------------------

--
-- Table structure for table `lcs`
--

CREATE TABLE `lcs` (
  `id` int(11) NOT NULL,
  `site_id` int(11) DEFAULT NULL,
  `lcs_name` varchar(255) DEFAULT NULL,
  `tw_address` text DEFAULT NULL,
  `incharge_name` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `installation_date` date DEFAULT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lcs`
--

INSERT INTO `lcs` (`id`, `site_id`, `lcs_name`, `tw_address`, `incharge_name`, `latitude`, `longitude`, `installation_date`, `created_by`, `created_at`) VALUES
(1, 24, 'LCS NAM', 'hjkh', 'LCS INC', 0.00000000, 999.99999999, '2025-10-01', 'Ashakiran Jyoti', '2025-10-31 10:04:25'),
(2, 26, 'LCS NAME', 'jjj', 'LCS INC', 23.45545000, 89.24248000, '2025-11-01', 'Ashakiran Jyoti', '2025-11-03 07:14:22'),
(3, 25, 'pachperwa lcs', 'pachperwa address', 'LCS INC', 23.45545000, 89.24248000, '2025-11-01', 'Ashakiran Jyoti', '2025-11-03 07:34:44'),
(4, 28, 'MCOM LCS', 'DEF', 'M LCS INC', 12.31230000, 90.32440000, '2025-11-01', 'Ashakiran Jyoti', '2025-11-05 10:00:01'),
(5, 27, 'RUDAULI LCS', 'RUDAULI LCS ADDRESS', 'LCS', 12.31230000, 89.24248000, '2025-11-01', 'USER 2', '2025-11-06 12:30:25'),
(6, 29, 'katra lcs', 'kanpur lcs', 'lcs nm', 12.31230000, 89.24248000, '2025-11-01', 'Ashakiran Jyoti', '2025-11-07 06:17:13'),
(7, 31, 'Bidhuna lcs', 'BIDHUNA UP', 'lcs name', 12.31230000, 90.32440000, '2025-11-15', 'Ashakiran Jyoti', '2025-11-15 11:31:08');

-- --------------------------------------------------------

--
-- Table structure for table `lcs_item`
--

CREATE TABLE `lcs_item` (
  `id` int(11) NOT NULL,
  `item_name` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(4) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lcs_item`
--

INSERT INTO `lcs_item` (`id`, `item_name`, `description`, `is_active`) VALUES
(1, 'table 1', 'table 1', 1),
(2, 'lcs item 1', 'lcs item 1', 1),
(3, 'lcs item 2', 'lcs item 2', 1),
(4, 'lcs item 11', 'lcs item 11', 1),
(5, 'table 1', 'table 1', 1);

-- --------------------------------------------------------

--
-- Table structure for table `lcs_master_media`
--

CREATE TABLE `lcs_master_media` (
  `id` int(11) NOT NULL,
  `lcs_id` int(11) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_type` enum('image','video') NOT NULL,
  `uploaded_by` varchar(100) DEFAULT NULL,
  `uploaded_at` datetime NOT NULL,
  `status_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lcs_master_media`
--

INSERT INTO `lcs_master_media` (`id`, `lcs_id`, `file_path`, `file_type`, `uploaded_by`, `uploaded_at`, `status_date`) VALUES
(1, 1, 'uploads/lcs_master_note/69182a7627e42-cc.jpeg', 'image', 'Ashakiran Jyoti', '2025-11-15 12:53:34', '2025-11-15'),
(3, 1, 'uploads/lcs_master_note/69182aa39856f-focus.jpg', 'image', 'Ashakiran Jyoti', '2025-11-15 12:54:19', '2025-11-15'),
(5, 1, 'uploads/lcs_master_note/69182b42760e2-WhatsApp Video 2025-11-12 at 1.57.18 PM.mp4', 'video', 'Ashakiran Jyoti', '2025-11-15 12:56:58', '2025-11-15'),
(6, 1, 'uploads/lcs_master_note/69182b4280170-WhatsApp Video 2025-11-12 at 1.56.20 PM.mp4', 'video', 'Ashakiran Jyoti', '2025-11-15 12:56:58', '2025-11-15'),
(8, 7, 'uploads/lcs_master_note/691864f616b6c-bb-Picsart-AiImageEnhancer.jfif', 'image', 'Ashakiran Jyoti', '2025-11-15 17:03:10', '2025-11-15'),
(9, 2, 'uploads/lcs_master_note/691878abbe403-WhatsApp Image 2025-11-14 at 1.19.52 PM.jpeg', 'image', 'Ravikumar Shukla', '2025-11-15 18:27:15', '2025-11-15'),
(10, 7, 'uploads/lcs_master_note/69187b1ddd210-45348565_fgjr7.jpg', 'image', 'Ravikumar Shukla', '2025-11-15 18:37:41', '2025-11-15'),
(11, 7, 'uploads/lcs_master_note/691aa1767dd96-download (3).png', 'image', 'Ashakiran Jyoti', '2025-11-17 09:45:50', '2025-11-17'),
(12, 7, 'uploads/lcs_master_note/691aa1768949f-103.96.43.158_job-route-system_index.php.png', 'image', 'Ashakiran Jyoti', '2025-11-17 09:45:50', '2025-11-17'),
(13, 7, 'uploads/lcs_master_note/691ab26e4bbef-WhatsApp Video 2025-11-12 at 1.56.20 PM.mp4', 'video', 'Ashakiran Jyoti', '2025-11-17 10:58:14', '2025-11-17'),
(14, 1, 'uploads/lcs_master_note/691ab5d14ccd6-45348565_fgjr7.jpg', 'image', 'Ashakiran Jyoti', '2025-11-17 11:12:41', '2025-11-17'),
(15, 2, 'uploads/lcs_master_note/691ac24d6cb02-WhatsApp Image 2025-11-14 at 1.19.52 PM.jpeg', 'image', 'Ashakiran Jyoti', '2025-11-17 12:05:57', '2025-11-17'),
(16, 1, 'uploads/lcs_master_note/images/691f0770918a5-Screenshot_20-11-2025_173758_103.97.105.200.jpeg', 'image', 'Ashakiran Jyoti', '2025-11-20 17:50:00', '2025-11-20'),
(17, 1, 'uploads/lcs_master_note/images/691ff0de99a04-Screenshot_20-11-2025_17417_103.97.105.200.jpeg', 'image', 'Ashakiran Jyoti', '2025-11-21 10:25:58', '2025-11-21'),
(18, 2, 'uploads/lcs_master_note/images/69202d3fb64a4-WhatsApp_Image_2025-11-14_at_12.31.18_PM.jpeg', 'image', 'Ashakiran Jyoti', '2025-11-21 14:43:35', '2025-11-21'),
(19, 2, 'uploads/lcs_master_note/images/69202d3fbb23a-WhatsApp_Image_2025-11-14_at_1.19.52_PM.jpeg', 'image', 'Ashakiran Jyoti', '2025-11-21 14:43:35', '2025-11-21');

-- --------------------------------------------------------

--
-- Table structure for table `lcs_master_media_change_log`
--

CREATE TABLE `lcs_master_media_change_log` (
  `id` int(11) NOT NULL,
  `lcs_id` int(11) NOT NULL,
  `media_id` int(11) DEFAULT NULL,
  `action` enum('uploaded','deleted') NOT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `file_type` enum('image','video') DEFAULT NULL,
  `status_date` date DEFAULT NULL,
  `actor` varchar(100) DEFAULT NULL,
  `action_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lcs_master_media_change_log`
--

INSERT INTO `lcs_master_media_change_log` (`id`, `lcs_id`, `media_id`, `action`, `file_path`, `file_type`, `status_date`, `actor`, `action_at`) VALUES
(1, 1, 1, 'uploaded', 'uploads/lcs_master_note/69182a7627e42-cc.jpeg', 'image', '2025-11-15', 'Ashakiran Jyoti', '2025-11-15 12:53:34'),
(2, 1, 2, 'uploaded', 'uploads/lcs_master_note/69182aa38a7f6-wakanda.jpg', 'image', '2025-11-15', 'Ashakiran Jyoti', '2025-11-15 12:54:19'),
(3, 1, 3, 'uploaded', 'uploads/lcs_master_note/69182aa39856f-focus.jpg', 'image', '2025-11-15', 'Ashakiran Jyoti', '2025-11-15 12:54:19'),
(4, 1, 4, 'uploaded', 'uploads/lcs_master_note/69182aa39a780-jal_nigam_logo.png', 'image', '2025-11-15', 'Ashakiran Jyoti', '2025-11-15 12:54:19'),
(5, 1, 4, 'deleted', 'uploads/lcs_master_note/69182aa39a780-jal_nigam_logo.png', 'image', '2025-11-15', 'Ashakiran Jyoti', '2025-11-15 12:56:02'),
(6, 1, 5, 'uploaded', 'uploads/lcs_master_note/69182b42760e2-WhatsApp Video 2025-11-12 at 1.57.18 PM.mp4', 'video', '2025-11-15', 'Ashakiran Jyoti', '2025-11-15 12:56:58'),
(7, 1, 6, 'uploaded', 'uploads/lcs_master_note/69182b4280170-WhatsApp Video 2025-11-12 at 1.56.20 PM.mp4', 'video', '2025-11-15', 'Ashakiran Jyoti', '2025-11-15 12:56:58'),
(8, 1, 2, 'deleted', 'uploads/lcs_master_note/69182aa38a7f6-wakanda.jpg', 'image', '2025-11-15', 'Ashakiran Jyoti', '2025-11-15 14:16:31'),
(9, 7, 7, 'uploaded', 'uploads/lcs_master_note/691864f611538-Add a heading.png', 'image', '2025-11-15', 'Ashakiran Jyoti', '2025-11-15 17:03:10'),
(10, 7, 8, 'uploaded', 'uploads/lcs_master_note/691864f616b6c-bb-Picsart-AiImageEnhancer.jfif', 'image', '2025-11-15', 'Ashakiran Jyoti', '2025-11-15 17:03:10'),
(11, 7, 7, 'deleted', 'uploads/lcs_master_note/691864f611538-Add a heading.png', 'image', '2025-11-15', 'Ravikumar Shukla', '2025-11-15 17:05:10'),
(12, 2, 9, 'uploaded', 'uploads/lcs_master_note/691878abbe403-WhatsApp Image 2025-11-14 at 1.19.52 PM.jpeg', 'image', '2025-11-15', 'Ravikumar Shukla', '2025-11-15 18:27:15'),
(13, 7, 10, 'uploaded', 'uploads/lcs_master_note/69187b1ddd210-45348565_fgjr7.jpg', 'image', '2025-11-15', 'Ravikumar Shukla', '2025-11-15 18:37:41'),
(14, 7, 11, 'uploaded', 'uploads/lcs_master_note/691aa1767dd96-download (3).png', 'image', '2025-11-17', 'Ashakiran Jyoti', '2025-11-17 09:45:50'),
(15, 7, 12, 'uploaded', 'uploads/lcs_master_note/691aa1768949f-103.96.43.158_job-route-system_index.php.png', 'image', '2025-11-17', 'Ashakiran Jyoti', '2025-11-17 09:45:50'),
(16, 7, 13, 'uploaded', 'uploads/lcs_master_note/691ab26e4bbef-WhatsApp Video 2025-11-12 at 1.56.20 PM.mp4', 'video', '2025-11-17', 'Ashakiran Jyoti', '2025-11-17 10:58:14'),
(17, 1, 14, 'uploaded', 'uploads/lcs_master_note/691ab5d14ccd6-45348565_fgjr7.jpg', 'image', '2025-11-17', 'Ashakiran Jyoti', '2025-11-17 11:12:41'),
(18, 2, 15, 'uploaded', 'uploads/lcs_master_note/691ac24d6cb02-WhatsApp Image 2025-11-14 at 1.19.52 PM.jpeg', 'image', '2025-11-17', 'Ashakiran Jyoti', '2025-11-17 12:05:57'),
(19, 1, 16, 'uploaded', 'uploads/lcs_master_note/images/691f0770918a5-Screenshot_20-11-2025_173758_103.97.105.200.jpeg', 'image', '2025-11-20', 'Ashakiran Jyoti', '2025-11-20 17:50:00'),
(20, 1, 17, 'uploaded', 'uploads/lcs_master_note/images/691ff0de99a04-Screenshot_20-11-2025_17417_103.97.105.200.jpeg', 'image', '2025-11-21', 'Ashakiran Jyoti', '2025-11-21 10:25:58'),
(21, 2, 18, 'uploaded', 'uploads/lcs_master_note/images/69202d3fb64a4-WhatsApp_Image_2025-11-14_at_12.31.18_PM.jpeg', 'image', '2025-11-21', 'Ashakiran Jyoti', '2025-11-21 14:43:35'),
(22, 2, 19, 'uploaded', 'uploads/lcs_master_note/images/69202d3fbb23a-WhatsApp_Image_2025-11-14_at_1.19.52_PM.jpeg', 'image', '2025-11-21', 'Ashakiran Jyoti', '2025-11-21 14:43:35');

-- --------------------------------------------------------

--
-- Table structure for table `lcs_master_notes`
--

CREATE TABLE `lcs_master_notes` (
  `id` int(11) NOT NULL,
  `lcs_id` int(11) NOT NULL,
  `status_date` date NOT NULL,
  `note` text DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lcs_master_notes`
--

INSERT INTO `lcs_master_notes` (`id`, `lcs_id`, `status_date`, `note`, `updated_by`, `updated_at`) VALUES
(1, 3, '2025-11-11', 'please its not working checking now its working fine', 'Ashakiran Jyoti', '2025-11-11 17:33:17'),
(3, 2, '2025-11-10', 'jk hjkasd jkakjd kjadhi kjahai aKSJasoi kalksoas kadjiowd kasjdjad ksajdai aOIUIWQ oiuwiueiw lasoiws kasdjiwd akjdiw jaosdjiowd kkkkadjied jadiiwi knjako kandiw kkdjadi hfiibs dwwowns bnnajjso nmaoodh sqwocnw qwjsdfiu llljshdue nasbhc jsadhu sdjf askdji jjj jskdhiwed kadkowi ksaodkjio akdjowi ksd kadjiow kasbfu jdisahduwe asjdjwiqd ajsdnuiw', 'Ashakiran Jyoti', '2025-11-10 18:08:12'),
(5, 4, '2025-11-05', 'MASTER NOTE OF LCS DATE WISE', 'Ashakiran Jyoti', '2025-11-05 15:34:15'),
(6, 5, '2025-11-06', 'RUDAULI LCS MASTER NOTE', 'USER 2', '2025-11-06 18:04:32'),
(7, 6, '2025-11-07', 'master note of kanpur site lcs', 'USER 1', '2025-11-07 12:18:47'),
(14, 5, '2025-11-11', '11-11-25 RUDAULI LCS MASTER NOTE', 'Ashakiran Jyoti', '2025-11-11 18:12:13'),
(16, 1, '2025-11-12', 'lcs master note 12-11-2025 9:53', 'Ashakiran Jyoti', '2025-11-12 09:53:34'),
(17, 3, '2025-11-13', '13-11-25 14:15  please its not working checking now its working fine', 'Ashakiran Jyoti', '2025-11-13 14:15:55'),
(20, 5, '2025-11-14', '14-11-25 RUDAULI LCS MASTER NOTE', 'Ashakiran Jyoti', '2025-11-14 09:58:13'),
(21, 1, '2025-11-15', 'lcs master note 15-11-2025 12:30', 'Ashakiran Jyoti', '2025-11-15 14:16:31'),
(31, 7, '2025-11-15', 'BIDHUNA LCS MASTER NOTE 15-11-25 17:01', 'Ravikumar Shukla', '2025-11-15 18:37:46'),
(33, 2, '2025-11-15', 'jk hjkasd jkakjd kjadhi kjahai aKSJasoi kalksoas kadjiowd kasjdjad ksajdai aOIUIWQ oiuwiueiw lasoiws kasdjiwd akjdiw jaosdjiowd kkkkadjied jadiiwi knjako kandiw kkdjadi hfiibs dwwowns bnnajjso nmaoodh sqwocnw qwjsdfiu llljshdue nasbhc jsadhu sdjf askdji jjj jskdhiwed kadkowi ksaodkjio akdjowi ksd kadjiow kasbfu jdisahduwe asjdjwiqd ajsdnuiw', 'Ravikumar Shukla', '2025-11-15 18:27:18'),
(35, 7, '2025-11-17', 'BIDHUNA LCS MASTER NOTE 17-11-25 09:45', 'Ashakiran Jyoti', '2025-11-17 09:45:18'),
(36, 1, '2025-11-17', 'lcs master note 17-11-2025 11:12', 'Ashakiran Jyoti', '2025-11-17 11:12:27'),
(37, 2, '2025-11-17', 'aman engg 17-11-2025 12:05', 'Ashakiran Jyoti', '2025-11-17 12:05:47'),
(38, 2, '2025-11-19', 'aman engg 17-11-2025 12:05', 'Ravikumar Shukla', '2025-11-19 12:48:14'),
(39, 5, '2025-11-19', '19-11-25 16:53 RUDAULI LCS MASTER NOTEjj', 'USER 2', '2025-11-19 17:55:33'),
(41, 1, '2025-11-20', 'lcs master note 20-11-2025 17:49', 'Ashakiran Jyoti', '2025-11-20 17:50:00'),
(42, 1, '2025-11-21', 'lcs master note 21-11-2025 10:25', 'Ashakiran Jyoti', '2025-11-21 10:25:58'),
(43, 2, '2025-11-21', 'aman engg 21-11-2025 14:43', 'Ashakiran Jyoti', '2025-11-21 14:43:35');

-- --------------------------------------------------------

--
-- Table structure for table `lcs_master_note_contributors`
--

CREATE TABLE `lcs_master_note_contributors` (
  `id` int(11) NOT NULL,
  `lcs_id` int(11) NOT NULL,
  `status_date` date NOT NULL,
  `contributor_name` varchar(100) NOT NULL,
  `added_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lcs_master_note_contributors`
--

INSERT INTO `lcs_master_note_contributors` (`id`, `lcs_id`, `status_date`, `contributor_name`, `added_at`) VALUES
(1, 1, '2025-11-20', 'operator 1', '2025-11-20 17:50:00'),
(2, 1, '2025-11-20', 'operator 3', '2025-11-20 17:50:00'),
(3, 1, '2025-11-21', 'operator 3', '2025-11-21 10:25:58'),
(4, 1, '2025-11-21', 'operator 4', '2025-11-21 10:25:58'),
(5, 2, '2025-11-21', 'USER 1', '2025-11-21 14:43:35'),
(6, 2, '2025-11-21', 'USER 2', '2025-11-21 14:43:35');

-- --------------------------------------------------------

--
-- Table structure for table `lcs_media`
--

CREATE TABLE `lcs_media` (
  `id` int(11) NOT NULL,
  `lcs_id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_type` enum('image','video') DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `uploaded_by` varchar(100) DEFAULT NULL,
  `uploaded_at` datetime DEFAULT current_timestamp(),
  `status_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lcs_media`
--

INSERT INTO `lcs_media` (`id`, `lcs_id`, `item_name`, `file_name`, `file_type`, `file_path`, `uploaded_by`, `uploaded_at`, `status_date`) VALUES
(1, 1, 'table', '69142f3967927-docs.google.com_spreadsheets_d_1VTWyNGPSyjpdClYdxUoez4IhvlJeid6t37ed5Mr3gU4_edit_gid=0.png', 'image', 'uploads/lcs/images/69142f3967927-docs.google.com_spreadsheets_d_1VTWyNGPSyjpdClYdxUoez4IhvlJeid6t37ed5Mr3gU4_edit_gid=0.png', 'web', '2025-11-12 12:24:49', '2025-11-12'),
(3, 1, 'spare 1', '691433e8504e0-focus.jpg', 'image', 'uploads/lcs/images/691433e8504e0-focus.jpg', 'web', '2025-11-12 12:44:48', '2025-11-12'),
(4, 1, 'spare 1', '691433f9d55ca-KATRA.png', 'image', 'uploads/lcs/images/691433f9d55ca-KATRA.png', 'web', '2025-11-12 12:45:05', '2025-11-12'),
(5, 1, 'spare 1', '69143412ddccb-RUDAULII.png', 'image', 'uploads/lcs/images/69143412ddccb-RUDAULII.png', 'web', '2025-11-12 12:45:30', '2025-11-12'),
(6, 1, 'lcs spare item 1', '69144cba082a7-23916547_6877208.jpg', 'image', 'uploads/lcs/images/69144cba082a7-23916547_6877208.jpg', 'web', '2025-11-12 14:30:42', '2025-11-12'),
(7, 1, 'spare item 2', '69144d2adb03e-quickdash.jpg', 'image', 'uploads/lcs/images/69144d2adb03e-quickdash.jpg', 'web', '2025-11-12 14:32:34', '2025-11-12'),
(8, 1, 'spare item 2', '69144d2adb62b-docs.google.com_spreadsheets_d_1VTWyNGPSyjpdClYdxUoez4IhvlJeid6t37ed5Mr3gU4_edit_gid=0.png', 'image', 'uploads/lcs/images/69144d2adb62b-docs.google.com_spreadsheets_d_1VTWyNGPSyjpdClYdxUoez4IhvlJeid6t37ed5Mr3gU4_edit_gid=0.png', 'web', '2025-11-12 14:32:34', '2025-11-12'),
(9, 1, 'spare item 2', '69144d2ae2ef0-WhatsApp Image 2025-11-10 at 2.11.03 PM.jpeg', 'image', 'uploads/lcs/images/69144d2ae2ef0-WhatsApp Image 2025-11-10 at 2.11.03 PM.jpeg', 'web', '2025-11-12 14:32:34', '2025-11-12'),
(10, 3, 'table 1', '6915835b46375-WhatsApp Video 2025-11-12 at 1.57.18 PM.mp4', 'video', 'uploads/lcs/videos/6915835b46375-WhatsApp Video 2025-11-12 at 1.57.18 PM.mp4', 'web', '2025-11-13 12:36:03', '2025-11-13'),
(11, 3, 'spare item', '6915d7c14fe5e-WhatsApp Video 2025-11-12 at 1.57.18 PM.mp4', 'video', 'uploads/lcs/videos/6915d7c14fe5e-WhatsApp Video 2025-11-12 at 1.57.18 PM.mp4', 'web', '2025-11-13 18:36:09', '2025-11-13'),
(12, 3, 'spare item', '6915d7c154234-WhatsApp Video 2025-11-12 at 1.51.39 PM.mp4', 'video', 'uploads/lcs/videos/6915d7c154234-WhatsApp Video 2025-11-12 at 1.51.39 PM.mp4', 'web', '2025-11-13 18:36:09', '2025-11-13'),
(13, 3, 'spare item', '6915d7c15ca93-WhatsApp Video 2025-11-12 at 1.56.20 PM.mp4', 'video', 'uploads/lcs/videos/6915d7c15ca93-WhatsApp Video 2025-11-12 at 1.56.20 PM.mp4', 'web', '2025-11-13 18:36:09', '2025-11-13'),
(14, 5, 'table 1', '6916aff9c3dbf-WhatsApp Video 2025-11-12 at 2.11.10 PM.mp4', 'video', 'uploads/lcs/videos/6916aff9c3dbf-WhatsApp Video 2025-11-12 at 2.11.10 PM.mp4', 'web', '2025-11-14 09:58:41', '2025-11-14'),
(15, 2, 'lcs item 1', '6916d718808d6-WhatsApp Image 2025-11-13 at 10.26.48 AM.jpeg', 'image', 'uploads/lcs/images/6916d718808d6-WhatsApp Image 2025-11-13 at 10.26.48 AM.jpeg', 'web', '2025-11-14 12:45:36', '0000-00-00'),
(16, 2, 'lcs item 1', '6916d71881298-WhatsApp Image 2025-11-13 at 10.52.31 AM.jpeg', 'image', 'uploads/lcs/images/6916d71881298-WhatsApp Image 2025-11-13 at 10.52.31 AM.jpeg', 'web', '2025-11-14 12:45:36', '0000-00-00'),
(17, 2, 'table 1', '6916ec07015e7-WhatsApp Image 2025-11-14 at 10.34.03 AM.jpeg', 'image', 'uploads/lcs/images/6916ec07015e7-WhatsApp Image 2025-11-14 at 10.34.03 AM.jpeg', 'web', '2025-11-14 14:14:55', '0000-00-00'),
(18, 2, 'lcs item 11', NULL, 'image', 'uploads/lcs/images/6916edc1c832f-WhatsApp Image 2025-11-14 at 9.03.48 AM.jpeg', 'Ashakiran Jyoti', '2025-11-14 14:22:17', '2025-11-14'),
(19, 2, 'lcs item 1', NULL, 'image', 'uploads/lcs/images/6916ee052222c-WhatsApp Image 2025-11-14 at 12.31.18 PM.jpeg', 'Ashakiran Jyoti', '2025-11-14 14:23:25', '2025-11-14'),
(21, 2, 'lcs item 1', NULL, 'image', 'uploads/lcs/images/6916ee23ef11b-WhatsApp Image 2025-11-14 at 1.19.52 PM.jpeg', 'Ashakiran Jyoti', '2025-11-14 14:23:56', '2025-11-14'),
(24, 2, 'lcs item 2', NULL, 'image', 'uploads/lcs/images/6916ee6b20e25-45348565_fgjr7.jpg', 'Ashakiran Jyoti', '2025-11-14 14:25:07', '2025-11-14'),
(25, 1, '__MASTER_NOTE__', NULL, 'image', 'uploads/lcs/images/691824da64742-hh.jpg', 'Ashakiran Jyoti', '2025-11-15 12:29:38', '2025-11-15'),
(26, 1, '__MASTER_NOTE__', NULL, 'image', 'uploads/lcs/images/691824f8ba695-WhatsApp Image 2025-11-14 at 2.12.07 PM.jpeg', 'Ashakiran Jyoti', '2025-11-15 12:30:08', '2025-11-15'),
(27, 1, '__MASTER_NOTE__', NULL, 'image', 'uploads/lcs/images/691824f8ce5a4-WhatsApp Image 2025-11-14 at 1.25.45 PM.jpeg', 'Ashakiran Jyoti', '2025-11-15 12:30:08', '2025-11-15'),
(28, 1, '__MASTER_NOTE__', NULL, 'image', 'uploads/lcs/images/6918285396357-WhatsApp Image 2025-11-10 at 2.11.03 PM.jpeg', 'Ashakiran Jyoti', '2025-11-15 12:44:27', '2025-11-15'),
(29, 1, '__MASTER_NOTE__', NULL, 'image', 'uploads/lcs/images/6918289398a5a-wtr_2.png', 'Ashakiran Jyoti', '2025-11-15 12:45:31', '2025-11-15'),
(30, 1, 'lcs item 1', NULL, 'image', 'uploads/lcs/images/69182ae18863d-wmremove-transformed (2)-Picsart-AiImageEnhancer-Picsart-AiImageEnhancer.jpeg', 'Ashakiran Jyoti', '2025-11-15 12:55:21', '2025-11-15'),
(31, 1, 'lcs item 1', NULL, 'image', 'uploads/lcs/images/69182ae1972ab-bb.jfif', 'Ashakiran Jyoti', '2025-11-15 12:55:21', '2025-11-15'),
(32, 7, 'table 1', NULL, 'image', 'uploads/lcs/images/691864d79552e-jal_urban.png', 'Ashakiran Jyoti', '2025-11-15 17:02:39', '2025-11-15'),
(33, 7, 'table 1', NULL, 'image', 'uploads/lcs/images/691864d7a999d-WhatsApp Image 2025-10-30 at 11.06.48 AM.jpeg', 'Ashakiran Jyoti', '2025-11-15 17:02:39', '2025-11-15'),
(34, 7, 'lcs item 1', NULL, 'image', 'uploads/lcs/images/691865541460f-IOT_1.JPEG', 'Ashakiran Jyoti', '2025-11-15 17:04:44', '2025-11-15'),
(36, 7, 'lcs item 1', NULL, 'image', 'uploads/lcs/images/691865543057b-Screenshot 2025-09-24 104302.jpg', 'Ashakiran Jyoti', '2025-11-15 17:04:44', '2025-11-15'),
(37, 7, 'spare item of bin', NULL, 'image', 'uploads/lcs/images/691865d7a3f1a-AdobeStock_1476384304_Preview.jpeg', 'Ravikumar Shukla', '2025-11-15 17:06:55', '2025-11-15'),
(38, 7, 'table 1', NULL, 'image', 'uploads/lcs/images/691aa19a5cce7-money-heist.jpg', 'Ashakiran Jyoti', '2025-11-17 09:46:26', '2025-11-17'),
(39, 7, 'table 1', NULL, 'image', 'uploads/lcs/images/691aa19a6a8f6-WhatsApp Image 2025-07-28 at 12.43.21 PM.jpeg', 'Ashakiran Jyoti', '2025-11-17 09:46:26', '2025-11-17'),
(40, 7, 'spare', NULL, 'image', 'uploads/lcs/images/691aa492b040b-wtr.jpg', 'Ashakiran Jyoti', '2025-11-17 09:59:06', '2025-11-17'),
(41, 3, 'table 1', NULL, 'image', 'uploads/lcs/images/691c61157b9a9-WhatsApp Image 2025-11-13 at 10.52.31 AM.jpeg', 'Ravikumar Shukla', '2025-11-18 17:35:41', '2025-11-18'),
(42, 3, 'lcs item 1', NULL, 'image', 'uploads/lcs/images/691c61464ad22-WhatsApp Image 2025-11-14 at 9.03.48 AM.jpeg', 'Ravikumar Shukla', '2025-11-18 17:36:30', '2025-11-18'),
(43, 5, 'lcs item 2', NULL, 'image', 'uploads/lcs/images/691da22dd4140-WhatsApp Image 2025-11-14 at 2.12.07 PM.jpeg', 'USER 2', '2025-11-19 16:25:41', '2025-11-19'),
(44, 1, 'lcs item 2', NULL, 'image', 'uploads/lcs/images/691f0f403e84f-ChatGPT Image Nov 17, 2025, 06_40_58 PM.png', 'Ashakiran Jyoti', '2025-11-20 18:23:20', '2025-11-20'),
(45, 1, 'lcs item 1', NULL, 'image', 'uploads/lcs/images/691ff1193b568-quickdash.jpg', 'Ashakiran Jyoti', '2025-11-21 10:26:57', '2025-11-21'),
(46, 2, 'lcs item 2', NULL, 'image', 'uploads/lcs/images/69202d5f88c06-WhatsApp Image 2025-11-14 at 10.34.03 AM.jpeg', 'Ashakiran Jyoti', '2025-11-21 14:44:07', '2025-11-21'),
(47, 2, 'lcs item 2', NULL, 'image', 'uploads/lcs/images/69202d5f99f96-WhatsApp Image 2025-11-14 at 11.02.14 AM.jpeg', 'Ashakiran Jyoti', '2025-11-21 14:44:07', '2025-11-21'),
(48, 1, 'lcs item 2', NULL, 'image', 'uploads/lcs/images/6923e6ed8402a-WhatsApp Image 2025-11-14 at 1.19.52 PM.jpeg', 'Ashakiran Jyoti', '2025-11-24 10:32:37', '2025-11-24');

-- --------------------------------------------------------

--
-- Table structure for table `lcs_media_change_log`
--

CREATE TABLE `lcs_media_change_log` (
  `id` int(11) NOT NULL,
  `lcs_id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `media_id` int(11) DEFAULT NULL,
  `action` enum('uploaded','deleted') NOT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `file_type` enum('image','video') DEFAULT NULL,
  `status_date` date DEFAULT NULL,
  `actor` varchar(100) DEFAULT NULL,
  `action_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lcs_media_change_log`
--

INSERT INTO `lcs_media_change_log` (`id`, `lcs_id`, `item_name`, `media_id`, `action`, `file_path`, `file_type`, `status_date`, `actor`, `action_at`) VALUES
(1, 2, 'lcs item 11', 18, 'uploaded', 'uploads/lcs/images/6916edc1c832f-WhatsApp Image 2025-11-14 at 9.03.48 AM.jpeg', 'image', '2025-11-14', 'Ashakiran Jyoti', '2025-11-14 14:22:17'),
(2, 2, 'lcs item 1', 19, 'uploaded', 'uploads/lcs/images/6916ee052222c-WhatsApp Image 2025-11-14 at 12.31.18 PM.jpeg', 'image', '2025-11-14', 'Ashakiran Jyoti', '2025-11-14 14:23:25'),
(3, 2, 'lcs item 1', 20, 'uploaded', 'uploads/lcs/images/6916ee05374c3-WhatsApp Image 2025-11-14 at 1.19.52 PM.jpeg', 'image', '2025-11-14', 'Ashakiran Jyoti', '2025-11-14 14:23:25'),
(4, 2, 'lcs item 1', 20, 'deleted', 'uploads/lcs/images/6916ee05374c3-WhatsApp Image 2025-11-14 at 1.19.52 PM.jpeg', 'image', '2025-11-14', 'Ashakiran Jyoti', '2025-11-14 14:23:40'),
(5, 2, 'lcs item 1', 21, 'uploaded', 'uploads/lcs/images/6916ee23ef11b-WhatsApp Image 2025-11-14 at 1.19.52 PM.jpeg', 'image', '2025-11-14', 'Ashakiran Jyoti', '2025-11-14 14:23:56'),
(6, 2, 'lcs item 2', 22, 'uploaded', 'uploads/lcs/images/6916ee6b006cf-jal-nigam-urban.jpg', 'image', '2025-11-14', 'Ashakiran Jyoti', '2025-11-14 14:25:07'),
(7, 2, 'lcs item 2', 23, 'uploaded', 'uploads/lcs/images/6916ee6b19b0f-WhatsApp Image 2025-10-30 at 11.06.48 AM.jpeg', 'image', '2025-11-14', 'Ashakiran Jyoti', '2025-11-14 14:25:07'),
(8, 2, 'lcs item 2', 24, 'uploaded', 'uploads/lcs/images/6916ee6b20e25-45348565_fgjr7.jpg', 'image', '2025-11-14', 'Ashakiran Jyoti', '2025-11-14 14:25:07'),
(9, 2, 'lcs item 2', 22, 'deleted', 'uploads/lcs/images/6916ee6b006cf-jal-nigam-urban.jpg', 'image', '2025-11-14', 'Ashakiran Jyoti', '2025-11-14 14:26:27'),
(10, 2, 'lcs item 2', 23, 'deleted', 'uploads/lcs/images/6916ee6b19b0f-WhatsApp Image 2025-10-30 at 11.06.48 AM.jpeg', 'image', '2025-11-14', 'Ashakiran Jyoti', '2025-11-14 14:26:27'),
(11, 1, '__MASTER_NOTE__', 25, 'uploaded', 'uploads/lcs/images/691824da64742-hh.jpg', 'image', '2025-11-15', 'Ashakiran Jyoti', '2025-11-15 12:29:38'),
(12, 1, '__MASTER_NOTE__', 26, 'uploaded', 'uploads/lcs/images/691824f8ba695-WhatsApp Image 2025-11-14 at 2.12.07 PM.jpeg', 'image', '2025-11-15', 'Ashakiran Jyoti', '2025-11-15 12:30:08'),
(13, 1, '__MASTER_NOTE__', 27, 'uploaded', 'uploads/lcs/images/691824f8ce5a4-WhatsApp Image 2025-11-14 at 1.25.45 PM.jpeg', 'image', '2025-11-15', 'Ashakiran Jyoti', '2025-11-15 12:30:08'),
(14, 1, '__MASTER_NOTE__', 28, 'uploaded', 'uploads/lcs/images/6918285396357-WhatsApp Image 2025-11-10 at 2.11.03 PM.jpeg', 'image', '2025-11-15', 'Ashakiran Jyoti', '2025-11-15 12:44:27'),
(15, 1, '__MASTER_NOTE__', 29, 'uploaded', 'uploads/lcs/images/6918289398a5a-wtr_2.png', 'image', '2025-11-15', 'Ashakiran Jyoti', '2025-11-15 12:45:31'),
(16, 1, 'lcs item 1', 30, 'uploaded', 'uploads/lcs/images/69182ae18863d-wmremove-transformed (2)-Picsart-AiImageEnhancer-Picsart-AiImageEnhancer.jpeg', 'image', '2025-11-15', 'Ashakiran Jyoti', '2025-11-15 12:55:21'),
(17, 1, 'lcs item 1', 31, 'uploaded', 'uploads/lcs/images/69182ae1972ab-bb.jfif', 'image', '2025-11-15', 'Ashakiran Jyoti', '2025-11-15 12:55:21'),
(18, 7, 'table 1', 32, 'uploaded', 'uploads/lcs/images/691864d79552e-jal_urban.png', 'image', '2025-11-15', 'Ashakiran Jyoti', '2025-11-15 17:02:39'),
(19, 7, 'table 1', 33, 'uploaded', 'uploads/lcs/images/691864d7a999d-WhatsApp Image 2025-10-30 at 11.06.48 AM.jpeg', 'image', '2025-11-15', 'Ashakiran Jyoti', '2025-11-15 17:02:39'),
(20, 7, 'lcs item 1', 34, 'uploaded', 'uploads/lcs/images/691865541460f-IOT_1.JPEG', 'image', '2025-11-15', 'Ashakiran Jyoti', '2025-11-15 17:04:44'),
(21, 7, 'lcs item 1', 35, 'uploaded', 'uploads/lcs/images/6918655426954-IOT_2.JPEG', 'image', '2025-11-15', 'Ashakiran Jyoti', '2025-11-15 17:04:44'),
(22, 7, 'lcs item 1', 36, 'uploaded', 'uploads/lcs/images/691865543057b-Screenshot 2025-09-24 104302.jpg', 'image', '2025-11-15', 'Ashakiran Jyoti', '2025-11-15 17:04:44'),
(23, 7, 'lcs item 1', 35, 'deleted', 'uploads/lcs/images/6918655426954-IOT_2.JPEG', 'image', '2025-11-15', 'Ravikumar Shukla', '2025-11-15 17:05:50'),
(24, 7, 'spare item of bin', 37, 'uploaded', 'uploads/lcs/images/691865d7a3f1a-AdobeStock_1476384304_Preview.jpeg', 'image', '2025-11-15', 'Ravikumar Shukla', '2025-11-15 17:06:55'),
(25, 7, 'table 1', 38, 'uploaded', 'uploads/lcs/images/691aa19a5cce7-money-heist.jpg', 'image', '2025-11-17', 'Ashakiran Jyoti', '2025-11-17 09:46:26'),
(26, 7, 'table 1', 39, 'uploaded', 'uploads/lcs/images/691aa19a6a8f6-WhatsApp Image 2025-07-28 at 12.43.21 PM.jpeg', 'image', '2025-11-17', 'Ashakiran Jyoti', '2025-11-17 09:46:26'),
(27, 7, 'spare', 40, 'uploaded', 'uploads/lcs/images/691aa492b040b-wtr.jpg', 'image', '2025-11-17', 'Ashakiran Jyoti', '2025-11-17 09:59:06'),
(28, 3, 'table 1', 41, 'uploaded', 'uploads/lcs/images/691c61157b9a9-WhatsApp Image 2025-11-13 at 10.52.31 AM.jpeg', 'image', '2025-11-18', 'Ravikumar Shukla', '2025-11-18 17:35:41'),
(29, 3, 'lcs item 1', 42, 'uploaded', 'uploads/lcs/images/691c61464ad22-WhatsApp Image 2025-11-14 at 9.03.48 AM.jpeg', 'image', '2025-11-18', 'Ravikumar Shukla', '2025-11-18 17:36:30'),
(30, 5, 'lcs item 2', 43, 'uploaded', 'uploads/lcs/images/691da22dd4140-WhatsApp Image 2025-11-14 at 2.12.07 PM.jpeg', 'image', '2025-11-19', 'USER 2', '2025-11-19 16:25:41'),
(31, 1, 'lcs item 2', 44, 'uploaded', 'uploads/lcs/images/691f0f403e84f-ChatGPT Image Nov 17, 2025, 06_40_58 PM.png', 'image', '2025-11-20', 'Ashakiran Jyoti', '2025-11-20 18:23:20'),
(32, 1, 'lcs item 1', 45, 'uploaded', 'uploads/lcs/images/691ff1193b568-quickdash.jpg', 'image', '2025-11-21', 'Ashakiran Jyoti', '2025-11-21 10:26:57'),
(33, 2, 'lcs item 2', 46, 'uploaded', 'uploads/lcs/images/69202d5f88c06-WhatsApp Image 2025-11-14 at 10.34.03 AM.jpeg', 'image', '2025-11-21', 'Ashakiran Jyoti', '2025-11-21 14:44:07'),
(34, 2, 'lcs item 2', 47, 'uploaded', 'uploads/lcs/images/69202d5f99f96-WhatsApp Image 2025-11-14 at 11.02.14 AM.jpeg', 'image', '2025-11-21', 'Ashakiran Jyoti', '2025-11-21 14:44:07'),
(35, 1, 'lcs item 2', 48, 'uploaded', 'uploads/lcs/images/6923e6ed8402a-WhatsApp Image 2025-11-14 at 1.19.52 PM.jpeg', 'image', '2025-11-24', 'Ashakiran Jyoti', '2025-11-24 10:32:37');

-- --------------------------------------------------------

--
-- Table structure for table `lcs_status_history`
--

CREATE TABLE `lcs_status_history` (
  `id` int(11) NOT NULL,
  `site_id` int(11) DEFAULT NULL,
  `lcs_id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `make_model` varchar(255) DEFAULT NULL,
  `size_capacity` varchar(100) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `check_hmi_local` tinyint(1) DEFAULT 0,
  `check_web` tinyint(1) DEFAULT 0,
  `remark` text DEFAULT NULL,
  `status_date` date NOT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lcs_status_history`
--

INSERT INTO `lcs_status_history` (`id`, `site_id`, `lcs_id`, `item_name`, `make_model`, `size_capacity`, `status`, `check_hmi_local`, `check_web`, `remark`, `status_date`, `created_by`, `recorded_at`, `updated_at`) VALUES
(1, 24, 1, 'table', '', '', 'Not Required', 0, 0, '', '2025-10-31', 'Ashakiran Jyoti', '2025-10-31 10:05:49', '2025-10-31 10:05:49'),
(2, 26, 2, 'lcs item 2', 'KITKAT', '26HP', 'Not Required', 1, 1, 'REMARK1', '2025-11-03', 'Ashakiran Jyoti', '2025-11-03 07:26:52', '2025-11-03 07:26:52'),
(3, 26, 2, 'lcs spare item', 'spare make', 'spare cap', 'Not Working', 1, 1, 'spare remark', '2025-11-03', 'Ashakiran Jyoti', '2025-11-03 07:27:36', '2025-11-03 07:27:36'),
(4, 25, 3, 'table', 'MODEL MCOM 1', '12HP', 'Not Required', 1, 1, 'REMARK1', '2025-11-03', 'Ashakiran Jyoti', '2025-11-03 07:35:03', '2025-11-03 07:35:03'),
(5, 25, 3, 'lcs item 1', 'MODEL MCOM 2', '25HP', 'Not Supply', 1, 1, 'REMARK2', '2025-11-03', 'Ashakiran Jyoti', '2025-11-03 07:35:19', '2025-11-03 07:35:19'),
(6, 25, 3, 'lcs item 2', 'KITKAT', '26HP', 'In - installation', 1, 0, 'REMARK3', '2025-11-03', 'Ashakiran Jyoti', '2025-11-03 07:35:37', '2025-11-03 07:35:37'),
(7, 25, 3, 'pach spare item', 'pw spare make', 'pw spare cap', 'Not Working', 1, 0, 'remark for testing', '2025-11-03', 'Ashakiran Jyoti', '2025-11-03 07:36:21', '2025-11-03 07:36:21'),
(8, 28, 4, 'table', 'MODEL MCOM 1', '12HP', 'Not Supply', 1, 1, 'REMARK1', '2025-11-05', 'Ashakiran Jyoti', '2025-11-05 10:00:24', '2025-11-05 10:00:24'),
(9, 28, 4, 'lcs item 1', 'jskdasdjaidhaidhda', '25HP', 'In - installation', 0, 1, 'REMARK TO CHECK LCS VIEW IS WORKING OR NOT', '2025-11-05', 'Ashakiran Jyoti', '2025-11-05 10:03:53', '2025-11-05 10:03:53'),
(10, 28, 4, 'lcs item 3', 'TO CHECK', '24HP', 'Supplied', 1, 1, 'LCS ITEM 3 I SHOWING IN VIEW OR NOT', '2025-11-05', 'Ashakiran Jyoti', '2025-11-05 10:05:33', '2025-11-05 10:05:33'),
(11, 26, 2, 'lcs item 3', 'MODEM 232', '24HP', 'Not Required', 0, 1, 'aman engg to check last update date', '2025-11-05', 'Ashakiran Jyoti', '2025-11-05 10:40:55', '2025-11-05 10:40:55'),
(12, 28, 4, 'lcs item 3', 'TO CHECK', '24HP', 'Supplied', 1, 0, 'LCS ITEM 3 I SHOWING IN VIEW OR NOT', '2025-11-05', 'Ashakiran Jyoti', '2025-11-05 11:13:01', '2025-11-05 11:13:01'),
(13, 27, 5, 'table', 'MAKE 1', '12HP', 'Not Required', 1, 1, 'remark 1 of USER 2', '2025-11-06', 'USER 2', '2025-11-06 12:35:06', '2025-11-06 12:35:06'),
(14, 29, 6, 'table', 'MAKE 1', '12HP', 'Not Supply', 0, 0, 'remark 1 of USER 1', '2025-11-07', 'USER 1', '2025-11-07 06:49:09', '2025-11-07 06:49:09'),
(15, 29, 6, 'lcs item 3', 'make 4', '12HP', 'Working', 0, 0, 'REMARK CHANGING FOR TESTING', '2025-11-07', 'USER 1', '2025-11-07 06:51:08', '2025-11-07 06:51:08'),
(16, 29, 6, 'lcs item 2', '', '', 'Not Required', 0, 0, '', '2025-11-07', 'USER 1', '2025-11-07 06:51:14', '2025-11-07 06:51:14'),
(17, 29, 6, 'lcs item 2', 'KITKAT', '26HP', 'Not Required', 0, 0, 'kjasjdhajsd ahsdiashdiuasd ahdahdiuad iahdiahdiausd aihdiaushdiashd ashdiahdiausd aihdisahdh', '2025-11-07', 'USER 1', '2025-11-07 06:51:29', '2025-11-07 06:51:29'),
(18, 29, 6, 'lcs item 2', 'MCOM2', '26HP', 'Not Required', 0, 0, 'kjasjdhajsd ahsdiashdiuasd ahdahdiuad iahdiahdiausd aihdiaushdiashd ashdiahdiausd aihdisahdh', '2025-11-07', 'USER 1', '2025-11-07 06:51:42', '2025-11-07 06:51:42'),
(19, 29, 6, 'lcs item 1', 'ACTUATOR BYPASS', '24HP', 'Supplied', 0, 0, 'kjasjdhajsd ahsdiashdiuasd ahdahdiuad iahdiahdiausd aihdiaushdiashd ashdiahdiausd aihdisahdh', '2025-11-07', 'USER 1', '2025-11-07 06:52:04', '2025-11-07 06:52:04'),
(20, 29, 6, 'lcs item 2', 'MCOM2', '26HP', 'Not Required', 0, 0, 'kjasjdhajsd ahsdiashdiuasd ahdahdiuad iahdiahdiausd aihdiaushdiashd ashdiahdiausd aihdisahdh', '2025-11-07', 'USER 1', '2025-11-07 06:52:14', '2025-11-07 06:52:14'),
(21, 29, 6, 'lcs item 2', '', '', 'Not Required', 0, 0, 'REMARK CHANGING FOR TESTING', '2025-11-07', 'USER 1', '2025-11-07 06:52:20', '2025-11-07 06:52:20'),
(22, 29, 6, 'lcs item 2', 'KITKAT', '32hp', 'Not Required', 0, 0, '>REMARK CHANGING FOR TESTING', '2025-11-07', 'USER 2', '2025-11-07 06:53:16', '2025-11-07 06:53:16'),
(23, 29, 6, 'item 4', 'make 4', '12hp', 'Not Working', 0, 0, 'KJSAHDAS JASDAD MANDKA NJASDKAD AJDKHAJD JASDASHD JASDADHAI HAHDUD JADAJHD HJSDHASIUD KJASDIA', '2025-11-07', 'USER 2', '2025-11-07 06:53:52', '2025-11-07 06:53:52'),
(24, 29, 6, 'item 4', 'make 4', '12hp', 'Not Working', 0, 0, 'KJSAHDAS JASDAD MANDKA NJASDKAD AJDKHAJD JASDASHD JASDADHAI HAHDUD JADAJHD HJSDHASIUD KJASDIA', '2025-11-07', 'USER 2', '2025-11-07 06:53:57', '2025-11-07 06:53:57'),
(25, 29, 6, 'item 4', 'make 88', '12hp', 'Not Working', 0, 0, 'KJSAHDAS JASDAD MDKA NJASDKAD AJDKHAJD JASDASHD JASDADHAI HAHDUD JADAJHD HJSDHASIUD KJASDIA', '2025-11-07', 'USER 2', '2025-11-07 06:54:17', '2025-11-07 06:54:17'),
(27, 29, 6, 'item 5', 'make 11', '11hp', 'Not Required', 0, 0, 'remark to check spare item is working well or not', '2025-11-07', 'USER 2', '2025-11-07 07:40:05', '2025-11-07 07:40:05'),
(28, 27, 5, 'table', 'MAKE 1', '12HP', 'In - installation', 0, 0, 'remark 1 of USER 2', '2025-11-11', 'Ashakiran Jyoti', '2025-11-11 12:42:21', '2025-11-11 12:42:21'),
(29, 27, 5, 'lcs item 1', 'lcs make 1', '11hp', 'Not Supply', 0, 0, 'REMARK', '2025-11-11', 'Ashakiran Jyoti', '2025-11-11 12:42:39', '2025-11-11 12:42:39'),
(30, 27, 5, 'spare item of rudauli', 'spare make', '22hp', 'Working', 0, 0, 'spare remark', '2025-11-11', 'Ashakiran Jyoti', '2025-11-11 12:43:12', '2025-11-11 12:43:12'),
(31, 24, 1, 'lcs item 1', 'make 12', '12 hp', 'Not Supply', 0, 0, 'REMARK CHANGING FOR TESTING', '2025-11-12', 'Ashakiran Jyoti', '2025-11-12 04:24:03', '2025-11-12 04:24:03'),
(32, 24, 1, 'lcs item 2', 'make 12', '23hp', 'Working', 0, 0, 'REMARK', '2025-11-12', 'Ashakiran Jyoti', '2025-11-12 04:24:26', '2025-11-12 04:24:26'),
(35, 24, 1, 'table', 'table make', '11hp', 'In - installation', 0, 0, 'REMARK1', '2025-11-12', 'Ashakiran Jyoti', '2025-11-12 07:01:10', '2025-11-12 07:01:10'),
(39, 24, 1, 'spare 1', 'asm', 'kad', 'Supplied', 0, 0, 'dasd', '2025-11-12', 'Ashakiran Jyoti', '2025-11-12 07:15:30', '2025-11-12 07:15:30'),
(40, 24, 1, 'lcs spare item 1', '12 make', '11 hp', 'Not Supply', 0, 0, 'remark spare', '2025-11-12', 'Ashakiran Jyoti', '2025-11-12 09:00:41', '2025-11-12 09:00:41'),
(42, 24, 1, 'spare item 2', 'spare make 2', '2hp', 'Installed', 0, 0, 'spare item 2 remark', '2025-11-12', 'Ashakiran Jyoti', '2025-11-12 09:03:05', '2025-11-12 09:03:05'),
(43, 25, 3, 'table 1', 'MODEL MCOM 1', '12HP', 'Installed', 0, 0, 'REMARK1', '2025-11-13', 'Ashakiran Jyoti', '2025-11-13 07:06:03', '2025-11-13 07:06:03'),
(44, 25, 3, 'spare item', 'spare make', '11', 'In - installation', 0, 0, 'spare remark', '2025-11-13', 'Ashakiran Jyoti', '2025-11-13 13:06:09', '2025-11-13 13:06:09'),
(45, 27, 5, 'table 1', 'MODEL MCOM 1', '12HP', 'In - installation', 0, 0, 'REMARK1', '2025-11-14', 'Ashakiran Jyoti', '2025-11-14 04:28:41', '2025-11-14 04:28:41'),
(47, 26, 2, 'table 1', '', '', 'Supplied', 0, 0, '', '2025-11-14', 'Ashakiran Jyoti', '2025-11-14 08:44:54', '2025-11-14 08:44:54'),
(48, 26, 2, 'lcs item 11', 'mmm', '66', 'Supplied', 0, 0, 'ttt', '2025-11-14', 'Ashakiran Jyoti', '2025-11-14 08:52:17', '2025-11-14 08:52:17'),
(51, 26, 2, 'lcs item 1', 'MODEL MCOM 1', '12HP', 'Supplied', 0, 0, 'REMARK1', '2025-11-14', 'Ashakiran Jyoti', '2025-11-14 08:53:55', '2025-11-14 08:53:55'),
(53, 26, 2, 'lcs item 2', 'KITKAT', '26HP', 'Not Required', 0, 0, 'REMARK1', '2025-11-14', 'Ashakiran Jyoti', '2025-11-14 08:56:27', '2025-11-14 08:56:27'),
(54, 24, 1, 'lcs item 1', 'make 12', '12 hp', 'Not Required', 0, 0, 'media test', '2025-11-15', 'Ashakiran Jyoti', '2025-11-15 07:25:21', '2025-11-15 07:25:21'),
(55, 31, 7, 'table 1', 'MODEL LCS', '26HP', 'Supplied', 0, 0, 'REMARK1', '2025-11-15', 'Ashakiran Jyoti', '2025-11-15 11:32:39', '2025-11-15 11:32:39'),
(57, 31, 7, 'lcs item 1', 'MCOM2', '32hp', 'In - installation', 0, 0, 'REMARK CHANGING FOR TESTING', '2025-11-15', 'Ravikumar Shukla', '2025-11-15 11:35:50', '2025-11-15 11:35:50'),
(58, 31, 7, 'spare item of bin', 'mamam', 'kasd', 'Not Required', 0, 0, 'ksdnk', '2025-11-15', 'Ravikumar Shukla', '2025-11-15 11:36:55', '2025-11-15 11:36:55'),
(59, 26, 2, 'table 1', '', '', 'Supplied', 0, 0, '>REMARK CHANGING FOR TESTING', '2025-11-15', 'Ravikumar Shukla', '2025-11-15 12:57:30', '2025-11-15 12:57:30'),
(60, 31, 7, 'table 1', 'MODEL LCS', '26HP', 'Supplied', 0, 0, 'REMARK1', '2025-11-17', 'Ashakiran Jyoti', '2025-11-17 04:16:26', '2025-11-17 04:16:26'),
(61, 31, 7, 'spare', 'mmm', 'llllll', 'Installed', 0, 0, 'rrrrrrrrrrr', '2025-11-17', 'Ashakiran Jyoti', '2025-11-17 04:29:06', '2025-11-17 04:29:06'),
(62, 25, 3, 'table 1', 'MODEL MCOM 1', '12HP', 'Installed', 0, 0, 'REMARK', '2025-11-18', 'Ravikumar Shukla', '2025-11-18 12:05:41', '2025-11-18 12:05:41'),
(64, 25, 3, 'lcs item 2', 'KITKAT', '26HP', 'Installed', 0, 0, 'yyyyyyyy', '2025-11-18', 'Ravikumar Shukla', '2025-11-18 12:07:55', '2025-11-18 12:07:55'),
(66, 25, 3, 'lcs item 1', 'MODEL MCOM 2', '25HP', 'Not Supply', 0, 0, 'REMARK2', '2025-11-18', 'Ravikumar Shukla', '2025-11-18 12:19:37', '2025-11-18 12:19:37'),
(67, 25, 3, 'pach spare item', 'pw spare make', 'pw spare cap', 'Supplied', 0, 0, 'remark for testing', '2025-11-18', 'Ravikumar Shukla', '2025-11-18 12:46:37', '2025-11-18 12:46:37'),
(69, 26, 2, 'lcs item 1', 'MODEL MCOM 1', '12HP', 'Supplied', 0, 0, 'testing operator 2', '2025-11-19', 'Ravikumar Shukla', '2025-11-19 07:18:01', '2025-11-19 07:18:01'),
(74, 26, 2, 'table 1', '', 'testing op 4', 'Supplied', 0, 0, '>REMARK CHANGING FOR TESTING', '2025-11-19', 'USER 2', '2025-11-19 09:29:35', '2025-11-19 09:29:35'),
(83, 26, 2, 'lcs item 3', '999', 'kk', 'Not Supply', 0, 0, 'aa', '2025-11-19', 'USER 2', '2025-11-19 10:19:54', '2025-11-19 10:19:54'),
(85, 26, 2, 'lcs item 11', 'mmm', '66', 'Supplied', 0, 0, 'ttt', '2025-11-19', 'USER 2', '2025-11-19 10:51:12', '2025-11-19 10:51:12'),
(86, 26, 2, 'lcs item 2', 'KITKAT', '26HP', 'Not Supply', 0, 0, 'REMARK1', '2025-11-19', 'USER 2', '2025-11-19 10:51:29', '2025-11-19 10:51:29'),
(87, 26, 2, 'lcs spare item', 'spare make', 'spare cap', 'In - installation', 0, 0, 'spare remark ttttt', '2025-11-19', 'USER 2', '2025-11-19 10:51:46', '2025-11-19 10:51:46'),
(88, 27, 5, 'table 1', 'MODEL MCOM 1', '12HP', 'Supplied', 0, 0, 'REMARK1', '2025-11-19', 'USER 2', '2025-11-19 10:52:27', '2025-11-19 10:52:27'),
(89, 27, 5, 'spare item of rudauli', 'spare make', '22hp', 'Working', 0, 0, 'spare remark', '2025-11-19', 'USER 2', '2025-11-19 10:52:46', '2025-11-19 10:52:46'),
(90, 27, 5, 'lcs item 2', '', '', 'Not Required', 0, 0, 'TESTING PURPOSE', '2025-11-19', 'USER 2', '2025-11-19 10:55:41', '2025-11-19 10:55:41'),
(91, 24, 1, 'lcs item 2', 'make 12', '23hp', 'Working', 0, 0, 'REMARK', '2025-11-20', 'Ashakiran Jyoti', '2025-11-20 12:53:20', '2025-11-20 12:53:20'),
(92, 24, 1, 'lcs item 1', 'make 12', '12 hp', 'Not Required', 0, 0, 'media test', '2025-11-21', 'Ashakiran Jyoti', '2025-11-21 04:56:57', '2025-11-21 04:56:57'),
(93, 26, 2, 'lcs item 2', 'KITKAT', '26HP', 'Not Supply', 0, 0, 'REMARK1', '2025-11-21', 'Ashakiran Jyoti', '2025-11-21 09:14:07', '2025-11-21 09:14:07'),
(94, 26, 2, 'lcs item 11', 'mmm', '66', 'Supplied', 0, 0, 'adding remark to check its working or not', '2025-11-21', 'Ashakiran Jyoti', '2025-11-21 09:14:37', '2025-11-21 09:14:37'),
(95, 26, 2, 'table 1', '', 'testing op 4', 'Supplied', 0, 0, 'CHANGING FOR TESTING', '2025-11-21', 'Ashakiran Jyoti', '2025-11-21 09:22:31', '2025-11-21 09:22:31'),
(96, 24, 1, 'lcs item 2', 'make 12', '23hp', 'Not Required', 0, 0, 'REMARK', '2025-11-24', 'Ashakiran Jyoti', '2025-11-24 05:02:37', '2025-11-24 05:02:37'),
(97, 25, 3, 'table 1', 'MODEL MCOM 1', '12HP', 'Installed', 0, 0, 'REMARK', '2025-11-26', 'Ashakiran Jyoti', '2025-11-26 11:25:11', '2025-11-26 11:25:11');

-- --------------------------------------------------------

--
-- Table structure for table `lcs_status_locks`
--

CREATE TABLE `lcs_status_locks` (
  `id` int(11) NOT NULL,
  `lcs_id` int(11) NOT NULL,
  `status_date` date NOT NULL,
  `locked_by` varchar(100) DEFAULT NULL,
  `locked_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `media_change_log`
--

CREATE TABLE `media_change_log` (
  `id` int(11) NOT NULL,
  `tubewell_id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `media_id` int(11) DEFAULT NULL,
  `action` enum('uploaded','deleted') NOT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `file_type` enum('image','video') DEFAULT NULL,
  `status_date` date DEFAULT NULL,
  `actor` varchar(100) DEFAULT NULL,
  `action_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `media_change_log`
--

INSERT INTO `media_change_log` (`id`, `tubewell_id`, `item_name`, `media_id`, `action`, `file_path`, `file_type`, `status_date`, `actor`, `action_at`) VALUES
(1, 54, 'RTU Control Panel', 41, 'deleted', 'uploads/images/6916caa0e5556-WhatsApp Image 2025-11-13 at 10.52.31 AM.jpeg', 'image', '2025-11-14', 'USER 2', '2025-11-14 12:00:23'),
(2, 54, 'RTU Control Panel', 42, 'uploaded', 'uploads/images/6916cf993b6fb-WhatsApp Image 2025-11-14 at 9.03.48 AM.jpeg', 'image', '2025-11-14', 'USER 2', '2025-11-14 12:13:37'),
(3, 54, 'RTU Control Panel', 43, 'uploaded', 'uploads/images/6916cf993d8ea-WhatsApp Image 2025-11-14 at 10.34.03 AM.jpeg', 'image', '2025-11-14', 'USER 2', '2025-11-14 12:13:37'),
(4, 54, 'RTU Control Panel', 43, 'deleted', 'uploads/images/6916cf993d8ea-WhatsApp Image 2025-11-14 at 10.34.03 AM.jpeg', 'image', '2025-11-14', 'Ashakiran Jyoti', '2025-11-14 12:14:09'),
(5, 53, 'sprae item', 44, 'uploaded', 'uploads/images/6916fa4117d67-WhatsApp Image 2025-11-14 at 12.31.18 PM.jpeg', 'image', '2025-11-14', 'Ashakiran Jyoti', '2025-11-14 15:15:37'),
(6, 63, 'Kitkat Pump (RYB)', 45, 'uploaded', 'uploads/images/69170d31a1fb3-WhatsApp Image 2025-11-14 at 12.31.18 PM.jpeg', 'image', '2025-11-14', 'Ashakiran Jyoti', '2025-11-14 16:36:25'),
(7, 63, 'Kitkat Pump (RYB)', 46, 'uploaded', 'uploads/images/69170d31a6484-WhatsApp Image 2025-11-14 at 1.19.52 PM.jpeg', 'image', '2025-11-14', 'Ashakiran Jyoti', '2025-11-14 16:36:25'),
(8, 63, 'tw spare item', 47, 'uploaded', 'uploads/images/69170d7046e7d-WhatsApp Image 2025-11-14 at 2.12.07 PM.jpeg', 'image', '2025-11-14', 'Ashakiran Jyoti', '2025-11-14 16:37:28'),
(9, 63, 'tw spare item', 48, 'uploaded', 'uploads/images/69170d7049219-WhatsApp Image 2025-11-13 at 10.26.48 AM.jpeg', 'image', '2025-11-14', 'Ashakiran Jyoti', '2025-11-14 16:37:28'),
(10, 63, 'tw spare item', 48, 'deleted', 'uploads/images/69170d7049219-WhatsApp Image 2025-11-13 at 10.26.48 AM.jpeg', 'image', '2025-11-14', 'USER 2', '2025-11-14 16:39:26'),
(11, 55, 'Kitkat Switch Board', 35, 'deleted', 'uploads/videos/6916c5998e861-WhatsApp Video 2025-11-12 at 1.57.18 PM.mp4', 'video', '2025-11-14', 'Ashakiran Jyoti', '2025-11-14 17:08:22'),
(12, 53, '__MASTER_NOTE__', 5, 'uploaded', 'uploads/master_note/images/69172088b26f4-WhatsApp_Image_2025-11-14_at_1.19.52_PM.jpeg', 'image', '2025-11-14', 'Ashakiran Jyoti', '2025-11-14 17:58:56'),
(13, 53, '__MASTER_NOTE__', 6, 'uploaded', 'uploads/master_note/images/6917209da12c6-WhatsApp_Image_2025-11-14_at_1.19.52_PM.jpeg', 'image', '2025-11-14', 'Ashakiran Jyoti', '2025-11-14 17:59:17'),
(14, 53, '__MASTER_NOTE__', 7, 'uploaded', 'uploads/master_note/videos/691722431ab86-WhatsApp_Video_2025-11-12_at_1.56.20_PM.mp4', 'video', '2025-11-14', 'Ashakiran Jyoti', '2025-11-14 18:06:19'),
(15, 55, '__MASTER_NOTE__', 8, 'uploaded', 'uploads/master_note/images/691725aa33c42-WhatsApp_Image_2025-11-10_at_2.11.03_PM.jpeg', 'image', '2025-11-14', 'Ashakiran Jyoti', '2025-11-14 18:20:50'),
(16, 55, '__MASTER_NOTE__', 9, 'uploaded', 'uploads/master_note/images/6917260ec622b-WhatsApp_Image_2025-11-14_at_10.34.03_AM.jpeg', 'image', '2025-11-14', 'Ashakiran Jyoti', '2025-11-14 18:22:30'),
(17, 55, '__MASTER_NOTE__', 10, 'uploaded', 'uploads/master_note/images/69180057e7c89-hh.jpg', 'image', '2025-11-15', 'Ashakiran Jyoti', '2025-11-15 09:53:51'),
(18, 53, 'Actuator bypass', 49, 'uploaded', 'uploads/images/6918042a544f0-hh.jpg', 'image', '2025-11-15', 'Ashakiran Jyoti', '2025-11-15 10:10:10'),
(19, 53, 'Kitkat Pump (RYB)', 50, 'uploaded', 'uploads/images/69180457957c4-WhatsApp Image 2025-11-14 at 1.25.45 PM.jpeg', 'image', '2025-11-15', 'Ashakiran Jyoti', '2025-11-15 10:10:55'),
(20, 54, 'RTU Control Panel', 51, 'uploaded', 'uploads/images/691804db0aea2-IOT_1.JPEG', 'image', '2025-11-15', 'Ashakiran Jyoti', '2025-11-15 10:13:07'),
(21, 53, '__MASTER_NOTE__', 11, 'uploaded', 'uploads/master_note/images/6918180236a0a-wmremove-transformed.jpeg', 'image', '2025-11-15', 'Ashakiran Jyoti', '2025-11-15 11:34:50'),
(22, 53, '__MASTER_NOTE__', 11, 'deleted', 'uploads/master_note/images/6918180236a0a-wmremove-transformed.jpeg', 'image', '2025-11-15', 'Ashakiran Jyoti', '2025-11-15 11:35:05'),
(23, 53, '__MASTER_NOTE__', 12, 'uploaded', 'uploads/master_note/images/691818286e9c8-IOT_1.JPEG', 'image', '2025-11-15', 'Ashakiran Jyoti', '2025-11-15 11:35:28'),
(24, 53, '__MASTER_NOTE__', 13, 'uploaded', 'uploads/master_note/videos/691818714d04a-WhatsApp_Video_2025-11-12_at_1.51.39_PM.mp4', 'video', '2025-11-15', 'Ashakiran Jyoti', '2025-11-15 11:36:41'),
(25, 53, '__MASTER_NOTE__', 14, 'uploaded', 'uploads/master_note/videos/69181871525f6-WhatsApp_Video_2025-11-12_at_1.56.20_PM.mp4', 'video', '2025-11-15', 'Ashakiran Jyoti', '2025-11-15 11:36:41'),
(26, 64, 'Kitkat Pump (RYB)', 52, 'uploaded', 'uploads/images/69186161c811b-WhatsApp Image 2025-11-14 at 1.19.52 PM.jpeg', 'image', '2025-11-15', 'Ashakiran Jyoti', '2025-11-15 16:47:53'),
(27, 64, 'Kitkat Pump (RYB)', 53, 'uploaded', 'uploads/images/69186161d0270-WhatsApp Image 2025-11-14 at 12.31.18 PM.jpeg', 'image', '2025-11-15', 'Ashakiran Jyoti', '2025-11-15 16:47:53'),
(28, 64, '__MASTER_NOTE__', 15, 'uploaded', 'uploads/master_note/images/691861a48ffe2-WhatsApp_Image_2025-11-14_at_11.02.14_AM.jpeg', 'image', '2025-11-15', 'Ashakiran Jyoti', '2025-11-15 16:49:00'),
(29, 64, '__MASTER_NOTE__', 16, 'uploaded', 'uploads/master_note/images/691861a493c20-WhatsApp_Image_2025-11-14_at_11.32.55_AM.jpeg', 'image', '2025-11-15', 'Ashakiran Jyoti', '2025-11-15 16:49:00'),
(30, 64, 'SPARE ITEM 1', 54, 'uploaded', 'uploads/videos/6918624a52df1-WhatsApp Video 2025-11-12 at 1.51.39 PM.mp4', 'video', '2025-11-15', 'Ashakiran Jyoti', '2025-11-15 16:51:46'),
(31, 64, 'SPARE ITEM 1', 55, 'uploaded', 'uploads/videos/6918624a5d1c2-WhatsApp Video 2025-11-12 at 1.56.20 PM.mp4', 'video', '2025-11-15', 'Ashakiran Jyoti', '2025-11-15 16:51:46'),
(32, 64, 'RTU Control Panel', 56, 'uploaded', 'uploads/images/69186437e2a1b-docs.google.com_spreadsheets_d_1VTWyNGPSyjpdClYdxUoez4IhvlJeid6t37ed5Mr3gU4_edit_gid=0.png', 'image', '2025-11-15', 'Ashakiran Jyoti', '2025-11-15 16:59:59'),
(33, 64, 'RTU Control Panel', 57, 'uploaded', 'uploads/images/69186437e6b13-localhost_soft-jjm-new_pg-new-dash.php (3).png', 'image', '2025-11-15', 'Ashakiran Jyoti', '2025-11-15 16:59:59'),
(34, 64, 'spare item 2', 58, 'uploaded', 'uploads/images/6918723f69578-wmremove-transformed (1).jpeg', 'image', '2025-11-15', 'Ravikumar Shukla', '2025-11-15 17:59:51'),
(35, 64, 'spare item 2', 59, 'uploaded', 'uploads/images/6918723f70061-wmremove-transformed.jpeg', 'image', '2025-11-15', 'Ravikumar Shukla', '2025-11-15 17:59:51'),
(36, 64, '__MASTER_NOTE__', 17, 'uploaded', 'uploads/master_note/images/691aa0f5d28a0-am.jpeg', 'image', '2025-11-17', 'Ashakiran Jyoti', '2025-11-17 09:43:41'),
(37, 64, '__MASTER_NOTE__', 18, 'uploaded', 'uploads/master_note/images/691aa0f5d9eec-WhatsApp_Image_2025-08-05_at_10.55.43_AM__3_.jpeg', 'image', '2025-11-17', 'Ashakiran Jyoti', '2025-11-17 09:43:41'),
(38, 64, 'Kitkat Pump (RYB)', 60, 'uploaded', 'uploads/images/691aa119c3fdf-WhatsApp Image 2025-07-10 at 9.51.46 AM.jpeg', 'image', '2025-11-17', 'Ashakiran Jyoti', '2025-11-17 09:44:17'),
(39, 64, 'Kitkat Pump (RYB)', 61, 'uploaded', 'uploads/images/691aa119c63b1-colorpicker2000.png', 'image', '2025-11-17', 'Ashakiran Jyoti', '2025-11-17 09:44:17'),
(40, 64, 'spareeee', 62, 'uploaded', 'uploads/images/691aa4ff1cc61-pt.png', 'image', '2025-11-17', 'Ashakiran Jyoti', '2025-11-17 10:00:55'),
(41, 55, '__MASTER_NOTE__', 19, 'uploaded', 'uploads/master_note/images/691ab58fe5120-Screenshot_19-9-2025_144145_-removebg-preview.png', 'image', '2025-11-17', 'Ashakiran Jyoti', '2025-11-17 11:11:35'),
(42, 54, '__MASTER_NOTE__', 20, 'uploaded', 'uploads/master_note/images/691abef701958-jal_urban.png', 'image', '2025-11-17', 'Ashakiran Jyoti', '2025-11-17 11:51:43'),
(43, 61, '__MASTER_NOTE__', 21, 'uploaded', 'uploads/master_note/videos/691ac28c35144-WhatsApp_Video_2025-11-12_at_1.51.39_PM.mp4', 'video', '2025-11-17', 'Ashakiran Jyoti', '2025-11-17 12:07:00'),
(44, 53, '__MASTER_NOTE__', 22, 'uploaded', 'uploads/master_note/images/691af1e86c5f9-WhatsApp_Image_2025-11-14_at_1.25.45_PM.jpeg', 'image', '2025-11-17', 'Ashakiran Jyoti', '2025-11-17 15:29:04'),
(45, 53, '__MASTER_NOTE__', 23, 'uploaded', 'uploads/master_note/images/691af1f45ae0b-WhatsApp_Image_2025-11-14_at_11.02.14_AM.jpeg', 'image', '2025-11-17', 'Ashakiran Jyoti', '2025-11-17 15:29:16'),
(46, 53, '__MASTER_NOTE__', 24, 'uploaded', 'uploads/master_note/images/691af1f45e5f4-WhatsApp_Image_2025-11-14_at_11.32.55_AM.jpeg', 'image', '2025-11-17', 'Ashakiran Jyoti', '2025-11-17 15:29:16'),
(47, 53, 'Kitkat Pump (RYB)', 63, 'uploaded', 'uploads/images/691b0a4d21d84-WhatsApp Image 2025-11-14 at 9.03.48 AM.jpeg', 'image', '2025-11-17', 'Ashakiran Jyoti', '2025-11-17 17:13:09'),
(48, 53, 'Kitkat Pump (RYB)', 64, 'uploaded', 'uploads/images/691b0a4d25339-WhatsApp Image 2025-11-14 at 10.34.03 AM.jpeg', 'image', '2025-11-17', 'Ashakiran Jyoti', '2025-11-17 17:13:09'),
(49, 53, 'Kitkat Pump (RYB)', 65, 'uploaded', 'uploads/images/691b0a4d2754c-WhatsApp Image 2025-11-14 at 11.02.14 AM.jpeg', 'image', '2025-11-17', 'Ashakiran Jyoti', '2025-11-17 17:13:09'),
(50, 53, 'Kitkat Pump (RYB)', 66, 'uploaded', 'uploads/images/691c1d1e2d165-WhatsApp Image 2025-11-14 at 12.31.18 PM.jpeg', 'image', '2025-11-18', 'Ashakiran Jyoti', '2025-11-18 12:45:42'),
(51, 53, 'item 1', 67, 'uploaded', 'uploads/images/691c34d72efcf-WhatsApp Image 2025-11-14 at 9.03.48 AM.jpeg', 'image', '2025-11-18', 'Ravikumar Shukla', '2025-11-18 14:26:55'),
(52, 53, '__MASTER_NOTE__', 25, 'uploaded', 'uploads/master_note/images/691c354c2b2ef-WhatsApp_Image_2025-11-14_at_10.34.03_AM.jpeg', 'image', '2025-11-18', 'Ravikumar Shukla', '2025-11-18 14:28:52'),
(53, 53, 'RTU Panel', 68, 'uploaded', 'uploads/images/691c61d69b248-ChatGPT Image Nov 17, 2025, 06_40_58 PM.png', 'image', '2025-11-18', 'Ravikumar Shukla', '2025-11-18 17:38:54'),
(54, 55, 'Kitkat Pump (RYB)', 69, 'uploaded', 'uploads/images/691d57430d12f-WhatsApp Image 2025-11-13 at 10.52.31 AM.jpeg', 'image', '2025-11-19', 'Ravikumar Shukla', '2025-11-19 11:06:03'),
(55, 55, 'Kitkat Pump (RYB)', 70, 'uploaded', 'uploads/images/691d57431563a-WhatsApp Image 2025-11-14 at 9.03.48 AM.jpeg', 'image', '2025-11-19', 'Ravikumar Shukla', '2025-11-19 11:06:03'),
(56, 54, 'Kitkat Pump (RYB)', 71, 'uploaded', 'uploads/images/691d69bac46ca-WhatsApp Image 2025-11-14 at 1.25.45 PM.jpeg', 'image', '2025-11-19', 'Ravikumar Shukla', '2025-11-19 12:24:50'),
(57, 54, 'hhhh', 72, 'uploaded', 'uploads/images/691d771b4412f-WhatsApp Image 2025-11-14 at 1.25.45 PM.jpeg', 'image', '2025-11-19', 'Ravikumar Shukla', '2025-11-19 13:21:55'),
(58, 54, 'RTU Control Panel', 73, 'uploaded', 'uploads/images/691d8362706b6-jj.jpg', 'image', '2025-11-19', 'USER 2', '2025-11-19 14:14:18'),
(59, 54, 'Kitkat Switch Board', 74, 'uploaded', 'uploads/images/691d87bba78da-ChatGPT Image Nov 17, 2025, 06_40_58 PM.png', 'image', '2025-11-19', 'USER 2', '2025-11-19 14:32:51'),
(60, 54, '__MASTER_NOTE__', 26, 'uploaded', 'uploads/master_note/images/691db71460440-WhatsApp_Image_2025-11-14_at_11.32.55_AM.jpeg', 'image', '2025-11-19', 'USER 2', '2025-11-19 17:54:52'),
(61, 53, '__MASTER_NOTE__', 27, 'uploaded', 'uploads/master_note/images/691efe23443df-WhatsApp_Image_2025-11-14_at_1.25.45_PM.jpeg', 'image', '2025-11-20', 'Ashakiran Jyoti', '2025-11-20 17:10:19'),
(62, 53, '__MASTER_NOTE__', 28, 'uploaded', 'uploads/master_note/images/691f011a2d698-localhost_soft-jjm-new_pg-new-dash.php__5_.png', 'image', '2025-11-20', 'Ashakiran Jyoti', '2025-11-20 17:22:58'),
(63, 53, '__MASTER_NOTE__', 28, 'deleted', 'uploads/master_note/images/691f011a2d698-localhost_soft-jjm-new_pg-new-dash.php__5_.png', 'image', '2025-11-20', 'Ashakiran Jyoti', '2025-11-20 17:23:13'),
(64, 53, '__MASTER_NOTE__', 29, 'uploaded', 'uploads/master_note/images/691fef40ed94b-Screenshot_20-11-2025_174720_103.97.105.200.jpeg', 'image', '2025-11-21', 'Ashakiran Jyoti', '2025-11-21 10:19:05'),
(65, 53, 'Kitkat Pump (RYB)', 75, 'uploaded', 'uploads/images/691fef8506b3d-WhatsApp Image 2025-11-14 at 2.12.07 PM.jpeg', 'image', '2025-11-21', 'Ashakiran Jyoti', '2025-11-21 10:20:13'),
(66, 67, 'Kitkat Pump (RYB)', 76, 'uploaded', 'uploads/images/69205e694bef9-Screenshot 2025-11-20 173728.jpg', 'image', '2025-11-21', 'Ashakiran Jyoti', '2025-11-21 18:13:21'),
(67, 53, 'Kitkat Pump (RYB)', 77, 'uploaded', 'uploads/images/6923df4a19737-Screenshot_20-11-2025_173758_103.97.105.200.jpeg', 'image', '2025-11-24', 'Ashakiran Jyoti', '2025-11-24 10:00:02');

-- --------------------------------------------------------

--
-- Table structure for table `media_uploads`
--

CREATE TABLE `media_uploads` (
  `id` int(11) NOT NULL,
  `tubewell_id` int(11) NOT NULL,
  `item_name` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` enum('image','video') NOT NULL,
  `uploaded_by` varchar(100) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `media_uploads`
--

INSERT INTO `media_uploads` (`id`, `tubewell_id`, `item_name`, `file_path`, `file_type`, `uploaded_by`, `uploaded_at`, `status_date`) VALUES
(1, 53, 'Kitkat Pump (RYB)', 'uploads/images/69141a807d638-WhatsApp Image 2025-11-10 at 2.11.03 PM.jpeg', 'image', 'web', '2025-11-12 05:26:24', '2025-11-12'),
(2, 53, 'Kitkat Pump (RYB)', 'uploads/images/69141ac12b3a7-pg-pc-dash.jpg', 'image', 'web', '2025-11-12 05:27:29', '2025-11-12'),
(4, 53, 'item 1 12', 'uploads/images/69141c6f47cc0-docs.google.com_spreadsheets_d_1VTWyNGPSyjpdClYdxUoez4IhvlJeid6t37ed5Mr3gU4_edit_gid=0.png', 'image', 'web', '2025-11-12 05:34:39', '2025-11-12'),
(6, 53, 'spare iii', 'uploads/images/69142123b6ab4-asset_manage_103.jpeg', 'image', 'web', '2025-11-12 05:54:43', '2025-11-12'),
(7, 53, 'iiii', 'uploads/images/69142150bd6a6-quickdash.jpg', 'image', 'web', '2025-11-12 05:55:28', '2025-11-12'),
(8, 53, 'sprae item', 'uploads/images/691426a26d252-pg-pc-dash.jpg', 'image', 'web', '2025-11-12 06:18:10', '2025-11-12'),
(9, 53, 'sprae item', 'uploads/images/691426b4dcb76-NEW-DASH-DESIGN.jpg', 'image', 'web', '2025-11-12 06:18:28', '2025-11-12'),
(10, 53, 'item 1 12', 'uploads/images/6914492fabe64-docs.google.com_spreadsheets_d_1VTWyNGPSyjpdClYdxUoez4IhvlJeid6t37ed5Mr3gU4_edit_gid=0.png', 'image', 'web', '2025-11-12 08:45:35', '2025-11-12'),
(11, 53, 'item 1 12', 'uploads/images/6914493ac321c-WhatsApp Image 2025-11-10 at 2.11.03 PM.jpeg', 'image', 'web', '2025-11-12 08:45:46', '2025-11-12'),
(12, 53, 'item 1 12', 'uploads/images/6914494700f34-localhost_soft-jjm-new_pg-new-dash.php (3).png', 'image', 'web', '2025-11-12 08:45:59', '2025-11-12'),
(14, 55, 'RTU Control Panel', 'uploads/videos/69157d2845e02-WhatsApp Video 2025-11-12 at 2.11.10 PM.mp4', 'video', 'web', '2025-11-13 06:39:36', '2025-11-13'),
(16, 54, 'RTU Panel', 'uploads/videos/6915c626b53c9-WhatsApp Video 2025-11-12 at 2.11.10 PM.mp4', 'video', 'web', '2025-11-13 11:51:02', '2025-11-13'),
(17, 54, 'RTU Panel', 'uploads/images/6915c626bdcd4-WhatsApp Image 2025-11-10 at 2.11.03 PM.jpeg', 'image', 'web', '2025-11-13 11:51:02', '2025-11-13'),
(18, 54, 'RTU Panel', 'uploads/images/6915c626be241-docs.google.com_spreadsheets_d_1VTWyNGPSyjpdClYdxUoez4IhvlJeid6t37ed5Mr3gU4_edit_gid=0.png', 'image', 'web', '2025-11-13 11:51:02', '2025-11-13'),
(19, 54, 'RTU Panel', 'uploads/images/6915c626c078c-quickdash.jpg', 'image', 'web', '2025-11-13 11:51:02', '2025-11-13'),
(20, 54, 'RTU Panel', 'uploads/images/6915c626c5c03-localhost_soft-jjm-new_pg-new-dash.php (5).png', 'image', 'web', '2025-11-13 11:51:02', '2025-11-13'),
(21, 54, 'RTU Panel', 'uploads/videos/6915c626c6f2d-WhatsApp Video 2025-11-12 at 1.57.18 PM.mp4', 'video', 'web', '2025-11-13 11:51:02', '2025-11-13'),
(23, 54, 'RTU Panel', 'uploads/images/6915c626d7335-localhost_soft-jjm-new_pg-new-dash.php (4).png', 'image', 'web', '2025-11-13 11:51:02', '2025-11-13'),
(24, 54, 'RTU Panel', 'uploads/images/6915c626d8cf8-localhost_soft-jjm-new_pg-new-dash.php (2).png', 'image', 'web', '2025-11-13 11:51:02', '2025-11-13'),
(25, 54, 'RTU Panel', 'uploads/images/6915c626da1d1-localhost_soft-jjm-new_pg-new-dash.php (3).png', 'image', 'web', '2025-11-13 11:51:02', '2025-11-13'),
(26, 54, 'RTU Panel', 'uploads/images/6915c626d8d7c-localhost_soft-jjm-new_pg-new-dash.php (1).png', 'image', 'web', '2025-11-13 11:51:02', '2025-11-13'),
(27, 54, 'RTU Panel', 'uploads/images/6915c626dd053-localhost_soft-jjm-new_pg-new-dash.php.png', 'image', 'web', '2025-11-13 11:51:02', '2025-11-13'),
(28, 54, 'RTU Panel', 'uploads/videos/6915c626e9ec6-WhatsApp Video 2025-11-12 at 1.56.20 PM.mp4', 'video', 'web', '2025-11-13 11:51:02', '2025-11-13'),
(29, 54, 'RTU Panel', 'uploads/images/6915c68d6421b-WhatsApp Image 2025-10-30 at 11.06.48 AM.jpeg', 'image', 'web', '2025-11-13 11:52:45', '2025-11-13'),
(30, 55, 'spare item 1', 'uploads/videos/6915d28a7d4c5-WhatsApp Video 2025-11-12 at 2.11.10 PM.mp4', 'video', 'web', '2025-11-13 12:43:54', '2025-11-13'),
(31, 58, 'RTU Panel', 'uploads/videos/6916b07f68eeb-WhatsApp Video 2025-11-12 at 1.56.20 PM.mp4', 'video', 'web', '2025-11-14 04:30:55', '2025-11-14'),
(32, 53, 'Actuator bypass', 'uploads/images/6916bdc8f0672-ravikiran_leet_50Days.png', 'image', 'web', '2025-11-14 05:27:36', '2025-11-14'),
(33, 55, 'Kitkat Pump (RYB)', 'uploads/videos/6916be5744e11-WhatsApp Video 2025-11-12 at 2.11.10 PM.mp4', 'video', 'web', '2025-11-14 05:29:59', '2025-11-14'),
(34, 55, 'Kitkat Switch Board', 'uploads/images/6916c59986c72-6915c626bdcd4-WhatsApp Image 2025-11-10 at 2.11.03 PM.jpeg', 'image', 'web', '2025-11-14 06:00:57', '2025-11-14'),
(36, 55, 'Kitkat Switch Board', 'uploads/images/6916c59990b26-docs.google.com_spreadsheets_d_1VTWyNGPSyjpdClYdxUoez4IhvlJeid6t37ed5Mr3gU4_edit_gid=0.png', 'image', 'web', '2025-11-14 06:00:57', '2025-11-14'),
(37, 55, 'Kitkat Switch Board', 'uploads/images/6916c5bc16935-unique-dashboard-DESKTOP-8B05FK3.png', 'image', 'web', '2025-11-14 06:01:32', '2025-11-14'),
(38, 55, 'Kitkat Switch Board', 'uploads/images/6916c5de673a8-STARTBUTTON-DESKTOP-8B05FK3.PNG', 'image', 'web', '2025-11-14 06:02:06', '2025-11-14'),
(39, 54, 'Kitkat Switch Board', 'uploads/images/6916c6937f132-EQUA.png', 'image', 'web', '2025-11-14 06:05:07', '2025-11-14'),
(42, 54, 'RTU Control Panel', 'uploads/images/6916cf993b6fb-WhatsApp Image 2025-11-14 at 9.03.48 AM.jpeg', 'image', 'USER 2', '2025-11-14 06:43:37', '2025-11-14'),
(44, 53, 'sprae item', 'uploads/images/6916fa4117d67-WhatsApp Image 2025-11-14 at 12.31.18 PM.jpeg', 'image', 'Ashakiran Jyoti', '2025-11-14 09:45:37', '2025-11-14'),
(45, 63, 'Kitkat Pump (RYB)', 'uploads/images/69170d31a1fb3-WhatsApp Image 2025-11-14 at 12.31.18 PM.jpeg', 'image', 'Ashakiran Jyoti', '2025-11-14 11:06:25', '2025-11-14'),
(46, 63, 'Kitkat Pump (RYB)', 'uploads/images/69170d31a6484-WhatsApp Image 2025-11-14 at 1.19.52 PM.jpeg', 'image', 'Ashakiran Jyoti', '2025-11-14 11:06:25', '2025-11-14'),
(47, 63, 'tw spare item', 'uploads/images/69170d7046e7d-WhatsApp Image 2025-11-14 at 2.12.07 PM.jpeg', 'image', 'Ashakiran Jyoti', '2025-11-14 11:07:28', '2025-11-14'),
(49, 53, 'Actuator bypass', 'uploads/images/6918042a544f0-hh.jpg', 'image', 'Ashakiran Jyoti', '2025-11-15 04:40:10', '2025-11-15'),
(50, 53, 'Kitkat Pump (RYB)', 'uploads/images/69180457957c4-WhatsApp Image 2025-11-14 at 1.25.45 PM.jpeg', 'image', 'Ashakiran Jyoti', '2025-11-15 04:40:55', '2025-11-15'),
(51, 54, 'RTU Control Panel', 'uploads/images/691804db0aea2-IOT_1.JPEG', 'image', 'Ashakiran Jyoti', '2025-11-15 04:43:07', '2025-11-15'),
(52, 64, 'Kitkat Pump (RYB)', 'uploads/images/69186161c811b-WhatsApp Image 2025-11-14 at 1.19.52 PM.jpeg', 'image', 'Ashakiran Jyoti', '2025-11-15 11:17:53', '2025-11-15'),
(53, 64, 'Kitkat Pump (RYB)', 'uploads/images/69186161d0270-WhatsApp Image 2025-11-14 at 12.31.18 PM.jpeg', 'image', 'Ashakiran Jyoti', '2025-11-15 11:17:53', '2025-11-15'),
(54, 64, 'SPARE ITEM 1', 'uploads/videos/6918624a52df1-WhatsApp Video 2025-11-12 at 1.51.39 PM.mp4', 'video', 'Ashakiran Jyoti', '2025-11-15 11:21:46', '2025-11-15'),
(55, 64, 'SPARE ITEM 1', 'uploads/videos/6918624a5d1c2-WhatsApp Video 2025-11-12 at 1.56.20 PM.mp4', 'video', 'Ashakiran Jyoti', '2025-11-15 11:21:46', '2025-11-15'),
(56, 64, 'RTU Control Panel', 'uploads/images/69186437e2a1b-docs.google.com_spreadsheets_d_1VTWyNGPSyjpdClYdxUoez4IhvlJeid6t37ed5Mr3gU4_edit_gid=0.png', 'image', 'Ashakiran Jyoti', '2025-11-15 11:29:59', '2025-11-15'),
(57, 64, 'RTU Control Panel', 'uploads/images/69186437e6b13-localhost_soft-jjm-new_pg-new-dash.php (3).png', 'image', 'Ashakiran Jyoti', '2025-11-15 11:29:59', '2025-11-15'),
(58, 64, 'spare item 2', 'uploads/images/6918723f69578-wmremove-transformed (1).jpeg', 'image', 'Ravikumar Shukla', '2025-11-15 12:29:51', '2025-11-15'),
(59, 64, 'spare item 2', 'uploads/images/6918723f70061-wmremove-transformed.jpeg', 'image', 'Ravikumar Shukla', '2025-11-15 12:29:51', '2025-11-15'),
(60, 64, 'Kitkat Pump (RYB)', 'uploads/images/691aa119c3fdf-WhatsApp Image 2025-07-10 at 9.51.46 AM.jpeg', 'image', 'Ashakiran Jyoti', '2025-11-17 04:14:17', '2025-11-17'),
(61, 64, 'Kitkat Pump (RYB)', 'uploads/images/691aa119c63b1-colorpicker2000.png', 'image', 'Ashakiran Jyoti', '2025-11-17 04:14:17', '2025-11-17'),
(62, 64, 'spareeee', 'uploads/images/691aa4ff1cc61-pt.png', 'image', 'Ashakiran Jyoti', '2025-11-17 04:30:55', '2025-11-17'),
(63, 53, 'Kitkat Pump (RYB)', 'uploads/images/691b0a4d21d84-WhatsApp Image 2025-11-14 at 9.03.48 AM.jpeg', 'image', 'Ashakiran Jyoti', '2025-11-17 11:43:09', '2025-11-17'),
(64, 53, 'Kitkat Pump (RYB)', 'uploads/images/691b0a4d25339-WhatsApp Image 2025-11-14 at 10.34.03 AM.jpeg', 'image', 'Ashakiran Jyoti', '2025-11-17 11:43:09', '2025-11-17'),
(65, 53, 'Kitkat Pump (RYB)', 'uploads/images/691b0a4d2754c-WhatsApp Image 2025-11-14 at 11.02.14 AM.jpeg', 'image', 'Ashakiran Jyoti', '2025-11-17 11:43:09', '2025-11-17'),
(66, 53, 'Kitkat Pump (RYB)', 'uploads/images/691c1d1e2d165-WhatsApp Image 2025-11-14 at 12.31.18 PM.jpeg', 'image', 'Ashakiran Jyoti', '2025-11-18 07:15:42', '2025-11-18'),
(67, 53, 'item 1', 'uploads/images/691c34d72efcf-WhatsApp Image 2025-11-14 at 9.03.48 AM.jpeg', 'image', 'Ravikumar Shukla', '2025-11-18 08:56:55', '2025-11-18'),
(68, 53, 'RTU Panel', 'uploads/images/691c61d69b248-ChatGPT Image Nov 17, 2025, 06_40_58 PM.png', 'image', 'Ravikumar Shukla', '2025-11-18 12:08:54', '2025-11-18'),
(69, 55, 'Kitkat Pump (RYB)', 'uploads/images/691d57430d12f-WhatsApp Image 2025-11-13 at 10.52.31 AM.jpeg', 'image', 'Ravikumar Shukla', '2025-11-19 05:36:03', '2025-11-19'),
(70, 55, 'Kitkat Pump (RYB)', 'uploads/images/691d57431563a-WhatsApp Image 2025-11-14 at 9.03.48 AM.jpeg', 'image', 'Ravikumar Shukla', '2025-11-19 05:36:03', '2025-11-19'),
(71, 54, 'Kitkat Pump (RYB)', 'uploads/images/691d69bac46ca-WhatsApp Image 2025-11-14 at 1.25.45 PM.jpeg', 'image', 'Ravikumar Shukla', '2025-11-19 06:54:50', '2025-11-19'),
(72, 54, 'hhhh', 'uploads/images/691d771b4412f-WhatsApp Image 2025-11-14 at 1.25.45 PM.jpeg', 'image', 'Ravikumar Shukla', '2025-11-19 07:51:55', '2025-11-19'),
(73, 54, 'RTU Control Panel', 'uploads/images/691d8362706b6-jj.jpg', 'image', 'USER 2', '2025-11-19 08:44:18', '2025-11-19'),
(74, 54, 'Kitkat Switch Board', 'uploads/images/691d87bba78da-ChatGPT Image Nov 17, 2025, 06_40_58 PM.png', 'image', 'USER 2', '2025-11-19 09:02:51', '2025-11-19'),
(75, 53, 'Kitkat Pump (RYB)', 'uploads/images/691fef8506b3d-WhatsApp Image 2025-11-14 at 2.12.07 PM.jpeg', 'image', 'Ashakiran Jyoti', '2025-11-21 04:50:13', '2025-11-21'),
(76, 67, 'Kitkat Pump (RYB)', 'uploads/images/69205e694bef9-Screenshot 2025-11-20 173728.jpg', 'image', 'Ashakiran Jyoti', '2025-11-21 12:43:21', '2025-11-21'),
(77, 53, 'Kitkat Pump (RYB)', 'uploads/images/6923df4a19737-Screenshot_20-11-2025_173758_103.97.105.200.jpeg', 'image', 'Ashakiran Jyoti', '2025-11-24 04:30:02', '2025-11-24');

-- --------------------------------------------------------

--
-- Table structure for table `parameters`
--

CREATE TABLE `parameters` (
  `id` int(11) NOT NULL,
  `tubewell_id` int(11) DEFAULT NULL,
  `item_name` varchar(255) NOT NULL,
  `make_model` varchar(255) DEFAULT NULL,
  `size_capacity` varchar(100) DEFAULT NULL,
  `status` enum('Active','Inactive','Maintenance') DEFAULT NULL,
  `check_hmi_local` tinyint(1) DEFAULT 0,
  `check_web` tinyint(1) DEFAULT 0,
  `remark` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_updated` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sites`
--

CREATE TABLE `sites` (
  `id` int(11) NOT NULL,
  `site_name` varchar(255) NOT NULL,
  `address` text DEFAULT NULL,
  `division_name` varchar(255) DEFAULT NULL,
  `contractor_name` varchar(255) DEFAULT NULL,
  `site_incharge` varchar(255) DEFAULT NULL,
  `contact` varchar(20) DEFAULT NULL,
  `number_of_tubewell` int(11) DEFAULT NULL,
  `lcs_available` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sites`
--

INSERT INTO `sites` (`id`, `site_name`, `address`, `division_name`, `contractor_name`, `site_incharge`, `contact`, `number_of_tubewell`, `lcs_available`, `created_by`, `created_at`) VALUES
(24, 'PIHANI', 'hjkh', 'GHI', 'CON NM', 'JKL', 'MNO', 8, 1, 'Ashakiran Jyoti', '2025-10-31 10:03:52'),
(25, 'PACHPERWA', 'kjkh', 'GHI', 'CON NM', 'JKL', 'MNO', 4, 1, 'Ashakiran Jyoti', '2025-11-03 04:43:31'),
(26, 'Aman Engineering', 'jjj', 'GHI', 'ABC', 'JKL', 'MNO', 4, 1, 'Ashakiran Jyoti', '2025-11-03 04:43:47'),
(27, 'Rudauli', 'jimii', 'GHI', 'Haidargadh Con Nm', 'JKL', 'MNO', 4, 1, 'Ashakiran Jyoti', '2025-11-03 04:44:11'),
(28, 'MCOM', 'DEF', 'GHI', 'ABC', 'KLM', 'NOP', 5, 1, 'rrr', '2025-11-04 07:21:07'),
(29, 'Kanpur Site', 'kanpur', 'div 1', 'Kanpur Con Nm', 'site inc', '09138912389', 4, 1, 'Ashakiran Jyoti', '2025-11-07 06:11:20'),
(30, 'ravi', 'pune', 'div 1', 'name', 'inc', '8237492837', 5, 1, 'USER 2', '2025-11-07 09:43:11'),
(31, 'BIDHUNA', 'BIDHUNA UP', 'DIV 1', 'BIDHUNA CON', 'SITE INC', '9839113326', 8, 1, 'Ashakiran Jyoti', '2025-11-15 10:35:50'),
(32, 'MCOM TECH', 'hhhhhhhhhhhhhhhhhh', 'DIV 1', 'CON NM', 'JKL', '9839113326', 6, 1, 'Ashakiran Jyoti', '2025-11-20 05:55:19');

-- --------------------------------------------------------

--
-- Table structure for table `status_change_log`
--

CREATE TABLE `status_change_log` (
  `id` int(11) NOT NULL,
  `tubewell_id` int(11) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `changed_by` varchar(100) NOT NULL,
  `change_type` enum('Added','Updated') NOT NULL,
  `old_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`old_value`)),
  `new_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`new_value`)),
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `status_change_log`
--

INSERT INTO `status_change_log` (`id`, `tubewell_id`, `item_name`, `changed_by`, `change_type`, `old_value`, `new_value`, `changed_at`) VALUES
(1, 54, 'Kitkat Pump (RYB)', 'Ashakiran Jyoti', 'Added', '{}', '{\"item_name\":\"Kitkat Pump (RYB)\",\"make_model\":\"MODEL MCOM 1\",\"size_capacity\":\"12HP\",\"status\":\"Supplied\",\"check_hmi_local\":1,\"check_web\":1,\"remark\":\"REMARK CHANGING FOR TESTING\"}', '2025-11-03 05:30:53'),
(2, 54, 'Testing Item', 'Ashakiran Jyoti', 'Added', '{}', '{\"item_name\":\"Testing Item\",\"make_model\":\"Testing Make\",\"size_capacity\":\"14hp\",\"status\":\"In - installation\",\"check_hmi_local\":1,\"check_web\":1,\"remark\":\"Remark\"}', '2025-11-03 05:51:54'),
(3, 54, 'Actuator bypass', 'Ashakiran Jyoti', 'Added', '{}', '{\"item_name\":\"Actuator bypass\",\"make_model\":\"ACTUATOR BYPASS\",\"size_capacity\":\"25HP\",\"status\":\"Not Supply\",\"check_hmi_local\":1,\"check_web\":1,\"remark\":\"REMARK1\"}', '2025-11-03 05:52:50'),
(4, 54, 'Test 2', 'Ashakiran Jyoti', 'Added', '{}', '{\"item_name\":\"Test 2\",\"make_model\":\"mcom232\",\"size_capacity\":\"10hp\",\"status\":\"Working\",\"check_hmi_local\":1,\"check_web\":1,\"remark\":\"Remark Test\"}', '2025-11-03 06:13:03'),
(5, 54, 'Testing Item', 'Ashakiran Jyoti', 'Updated', '{\"item_name\":\"Testing Item\",\"make_model\":\"Testing Make\",\"size_capacity\":\"14hp\",\"status\":\"In - installation\",\"check_hmi_local\":1,\"check_web\":1,\"remark\":\"Remark\"}', '{\"item_name\":\"Testing Item\",\"make_model\":\"Testing mcom232\",\"size_capacity\":\"14hp\",\"status\":\"Not Required\",\"check_hmi_local\":1,\"check_web\":1,\"remark\":\"Remark changed make\"}', '2025-11-03 06:16:57'),
(6, 55, 'Kitkat Pump (RYB)', 'Ashakiran Jyoti', 'Added', '{}', '{\"item_name\":\"Kitkat Pump (RYB)\",\"make_model\":\"MODEL MCOM 1\",\"size_capacity\":\"25HP\",\"status\":\"Supplied\",\"check_hmi_local\":1,\"check_web\":0,\"remark\":\"REMARK CHANGING FOR TESTING\"}', '2025-11-03 06:18:04'),
(7, 55, 'Item 1`', 'Ashakiran Jyoti', 'Added', '{}', '{\"item_name\":\"Item 1`\",\"make_model\":\"make 1\",\"size_capacity\":\"cap 1\",\"status\":\"Not Required\",\"check_hmi_local\":1,\"check_web\":1,\"remark\":\"Remark 1\"}', '2025-11-03 06:36:11'),
(8, 55, 'Item 1`', 'Ashakiran Jyoti', 'Updated', '{\"item_name\":\"Item 1`\",\"make_model\":\"make 1\",\"size_capacity\":\"cap 1\",\"status\":\"Not Required\",\"check_hmi_local\":1,\"check_web\":1,\"remark\":\"Remark 1\"}', '{\"item_name\":\"Item 1`\",\"make_model\":\"make 2\",\"size_capacity\":\"cap 2\",\"status\":\"Not Supply\",\"check_hmi_local\":1,\"check_web\":1,\"remark\":\"Remark 1\"}', '2025-11-03 06:36:39'),
(9, 55, 'Actuator bypass', 'Ashakiran Jyoti', 'Added', '{}', '{\"item_name\":\"Actuator bypass\",\"make_model\":\"MCOM2\",\"size_capacity\":\"12HP\",\"status\":\"Not Supply\",\"check_hmi_local\":1,\"check_web\":1,\"remark\":\"testing\"}', '2025-11-04 12:41:17'),
(10, 54, 'Kitkat Switch Board', 'Ashakiran Jyoti', 'Added', '{}', '{\"item_name\":\"Kitkat Switch Board\",\"make_model\":\"check\",\"size_capacity\":\"1hp\",\"status\":\"In - installation\",\"check_hmi_local\":1,\"check_web\":1,\"remark\":\"to check view parameters is working or not\"}', '2025-11-04 12:52:55'),
(11, 54, 'new item', 'Ashakiran Jyoti', 'Added', '{}', '{\"item_name\":\"new item\",\"make_model\":\"SS\",\"size_capacity\":\"DD\",\"status\":\"Not Working\",\"check_hmi_local\":1,\"check_web\":1,\"remark\":\"TO CHECK SPARE ROW\"}', '2025-11-04 12:53:25'),
(12, 54, 'Actuator bypass', 'Ashakiran Jyoti', 'Added', '{}', '{\"item_name\":\"Actuator bypass\",\"make_model\":\"ACTUATOR BYPASS\",\"size_capacity\":\"25HP\",\"status\":\"Not Supply\",\"check_hmi_local\":1,\"check_web\":1,\"remark\":\"to check master note is working or not\"}', '2025-11-05 05:10:52'),
(13, 54, 'new item', 'Ashakiran Jyoti', 'Added', '{}', '{\"item_name\":\"new item\",\"make_model\":\"new make\",\"size_capacity\":\"1hp\",\"status\":\"Installed\",\"check_hmi_local\":1,\"check_web\":1,\"remark\":\"to check spare is working or not properly\"}', '2025-11-05 05:12:25'),
(14, 54, 'new item', 'Ashakiran Jyoti', 'Updated', '{\"item_name\":\"new item\",\"make_model\":\"new make\",\"size_capacity\":\"1hp\",\"status\":\"Installed\",\"check_hmi_local\":1,\"check_web\":1,\"remark\":\"to check spare is working or not properly\"}', '{\"item_name\":\"new item\",\"make_model\":\"new make\",\"size_capacity\":\"1hp\",\"status\":\"Installed\",\"check_hmi_local\":0,\"check_web\":1,\"remark\":\"to check spare is working or not properly\"}', '2025-11-05 10:45:02'),
(15, 54, 'Actuator bypass', 'Ashakiran Jyoti', 'Updated', '{\"item_name\":\"Actuator bypass\",\"make_model\":\"ACTUATOR BYPASS\",\"size_capacity\":\"25HP\",\"status\":\"Not Supply\",\"check_hmi_local\":1,\"check_web\":1,\"remark\":\"to check master note is working or not\"}', '{\"item_name\":\"Actuator bypass\",\"make_model\":\"ACTUATOR BYPASS\",\"size_capacity\":\"25HP\",\"status\":\"Not Supply\",\"check_hmi_local\":1,\"check_web\":0,\"remark\":\"to check master note is working or not\"}', '2025-11-05 10:51:33'),
(16, 54, 'RTU Control Panel', 'Ravikumar Shukla', 'Added', '{}', '{\"item_name\":\"RTU Control Panel\",\"make_model\":\"MCOM2\",\"size_capacity\":\"26HP\",\"status\":\"In - installation\",\"check_hmi_local\":1,\"check_web\":1,\"remark\":\"REMARK1\"}', '2025-11-06 07:59:48'),
(17, 56, 'Kitkat Pump (RYB)', 'operator 4', 'Added', '{}', '{\"item_name\":\"Kitkat Pump (RYB)\",\"make_model\":\"make 1\",\"size_capacity\":\"12hp\",\"status\":\"Not Supply\",\"check_hmi_local\":1,\"check_web\":1,\"remark\":\"remark 1 of operator 4\"}', '2025-11-06 09:21:58'),
(18, 56, 'RTU Control Panel', 'operator 3', 'Added', '{}', '{\"item_name\":\"RTU Control Panel\",\"make_model\":\"make 2\",\"size_capacity\":\"12hp\",\"status\":\"In - installation\",\"check_hmi_local\":1,\"check_web\":1,\"remark\":\"remark 3 of operator 3\"}', '2025-11-06 09:22:50'),
(19, 56, 'Actuator bypass', 'operator 1', 'Added', '{}', '{\"item_name\":\"Actuator bypass\",\"make_model\":\"make 3\",\"size_capacity\":\"12hp\",\"status\":\"Installed\",\"check_hmi_local\":1,\"check_web\":1,\"remark\":\"remark 2 of operator 1\"}', '2025-11-06 09:23:45'),
(20, 56, 'Kitkat Switch Board', 'operator 2', 'Added', '{}', '{\"item_name\":\"Kitkat Switch Board\",\"make_model\":\"make 4\",\"size_capacity\":\"24hp\",\"status\":\"Installed\",\"check_hmi_local\":1,\"check_web\":1,\"remark\":\"remark 4 of operator 2\"}', '2025-11-06 09:24:35'),
(21, 56, 'item 1', 'Ashakiran Jyoti', 'Added', '{}', '{\"item_name\":\"item 1\",\"make_model\":\"make 5\",\"size_capacity\":\"25hp\",\"status\":\"Not Working\",\"check_hmi_local\":1,\"check_web\":1,\"remark\":\"remark 5 of Ashakiran\"}', '2025-11-06 09:25:38'),
(22, 56, 'item 2', 'Ravikumar Shukla', 'Added', '{}', '{\"item_name\":\"item 2\",\"make_model\":\"make 6\",\"size_capacity\":\"26hp\",\"status\":\"Not Working\",\"check_hmi_local\":1,\"check_web\":1,\"remark\":\"remark 6 of ravikiran\"}', '2025-11-06 09:27:07'),
(23, 58, 'Kitkat Pump (RYB)', 'USER 1', 'Added', '{}', '{\"item_name\":\"Kitkat Pump (RYB)\",\"make_model\":\"make 1\",\"size_capacity\":\"12hp\",\"status\":\"Not Required\",\"check_hmi_local\":1,\"check_web\":1,\"remark\":\"remark 1 of USER 1\"}', '2025-11-06 11:36:41'),
(24, 58, 'Actuator bypass', 'USER 1', 'Added', '{}', '{\"item_name\":\"Actuator bypass\",\"make_model\":\"make 3\",\"size_capacity\":\"12hp\",\"status\":\"Not Required\",\"check_hmi_local\":1,\"check_web\":1,\"remark\":\"remark 2 of USER 1\"}', '2025-11-06 11:36:55'),
(25, 58, 'RTU Control Panel', 'USER 2', 'Added', '{}', '{\"item_name\":\"RTU Control Panel\",\"make_model\":\"make 2\",\"size_capacity\":\"12hp\",\"status\":\"Not Required\",\"check_hmi_local\":1,\"check_web\":1,\"remark\":\"remark 3 of USER 2\"}', '2025-11-06 11:37:30'),
(26, 58, 'Kitkat Switch Board', 'USER 2', 'Added', '{}', '{\"item_name\":\"Kitkat Switch Board\",\"make_model\":\"make 4\",\"size_capacity\":\"24hp\",\"status\":\"Not Required\",\"check_hmi_local\":1,\"check_web\":1,\"remark\":\"remark 4 of USER 2\"}', '2025-11-06 11:37:48'),
(27, 58, 'ITEM 1', 'Ravikumar Shukla', 'Added', '{}', '{\"item_name\":\"ITEM 1\",\"make_model\":\"Make 5\",\"size_capacity\":\"12hp\",\"status\":\"Not Working\",\"check_hmi_local\":1,\"check_web\":1,\"remark\":\"remark 5 of ravikumar\"}', '2025-11-06 11:38:54'),
(28, 53, 'Kitkat Pump (RYB)', 'USER 1', 'Added', '{}', '{\"item_name\":\"Kitkat Pump (RYB)\",\"make_model\":\"make 1\",\"size_capacity\":\"12hp\",\"status\":\"In - installation\",\"check_hmi_local\":1,\"check_web\":1,\"remark\":\"remark 1 of USER 1\"}', '2025-11-06 11:58:27'),
(29, 53, 'RTU Control Panel', 'USER 2', 'Added', '{}', '{\"item_name\":\"RTU Control Panel\",\"make_model\":\"RTU CONTROL PANEL\",\"size_capacity\":\"15HP\",\"status\":\"Supplied\",\"check_hmi_local\":0,\"check_web\":0,\"remark\":\"REMARK CHANGING FOR TESTING  USER 2\"}', '2025-11-06 11:59:21'),
(30, 59, 'Kitkat Pump (RYB)', 'USER 2', 'Added', '{}', '{\"item_name\":\"Kitkat Pump (RYB)\",\"make_model\":\"MODEL 1\",\"size_capacity\":\"23 HP\",\"status\":\"Not Supply\",\"check_hmi_local\":1,\"check_web\":1,\"remark\":\"kjasjdhajsd ahsdiashdiuasd ahdahdiuad iahdiahdiausd aihdiaushdiashd ashdiahdiausd aihdisahdh\"}', '2025-11-07 06:55:17'),
(31, 59, 'Actuator bypass', 'USER 2', 'Added', '{}', '{\"item_name\":\"Actuator bypass\",\"make_model\":\"MAKE 1\",\"size_capacity\":\"21HP\",\"status\":\"In - installation\",\"check_hmi_local\":1,\"check_web\":0,\"remark\":\"REMARK CHANGING FOR TESTING\"}', '2025-11-07 06:55:39'),
(32, 59, 'RTU Control Panel', 'USER 2', 'Added', '{}', '{\"item_name\":\"RTU Control Panel\",\"make_model\":\"CONTROL\",\"size_capacity\":\"11HP\",\"status\":\"Installed\",\"check_hmi_local\":1,\"check_web\":1,\"remark\":\">REMARK CHANGING FOR TESTING\"}', '2025-11-07 06:56:05'),
(33, 59, 'Kitkat Switch Board', 'USER 2', 'Added', '{}', '{\"item_name\":\"Kitkat Switch Board\",\"make_model\":\"MODEM 232\",\"size_capacity\":\"10HP\",\"status\":\"Installed\",\"check_hmi_local\":1,\"check_web\":1,\"remark\":\">REMARK CHANGING FOR TESTING\"}', '2025-11-07 06:56:23'),
(34, 59, 'SPARE ITEM 1', 'USER 2', 'Added', '{}', '{\"item_name\":\"SPARE ITEM 1\",\"make_model\":\"MAKE 1\",\"size_capacity\":\"11HP\",\"status\":\"Supplied\",\"check_hmi_local\":0,\"check_web\":1,\"remark\":\"REMARK OF KANPUR SITE TW 1 FOR TESTING\"}', '2025-11-07 06:57:10'),
(35, 59, 'SPARE ITEM 1', 'USER 2', 'Updated', '{\"item_name\":\"SPARE ITEM 1\",\"make_model\":\"MAKE 1\",\"size_capacity\":\"11HP\",\"status\":\"Supplied\",\"check_hmi_local\":0,\"check_web\":1,\"remark\":\"REMARK OF KANPUR SITE TW 1 FOR TESTING\"}', '{\"item_name\":\"SPARE ITEM 1\",\"make_model\":\"MAKE 1\",\"size_capacity\":\"11HP\",\"status\":\"Supplied\",\"check_hmi_local\":1,\"check_web\":1,\"remark\":\"REMARK OF KANPUR SITE TW 1 FOR TESTING\"}', '2025-11-07 06:57:17'),
(36, 59, 'SPARE ITEM 1', 'USER 2', 'Updated', '{\"item_name\":\"SPARE ITEM 1\",\"make_model\":\"MAKE 1\",\"size_capacity\":\"11HP\",\"status\":\"Supplied\",\"check_hmi_local\":1,\"check_web\":1,\"remark\":\"REMARK OF KANPUR SITE TW 1 FOR TESTING\"}', '{\"item_name\":\"SPARE ITEM 1\",\"make_model\":\"MAKE 1\",\"size_capacity\":\"11HP\",\"status\":\"Supplied\",\"check_hmi_local\":1,\"check_web\":1,\"remark\":\"REMARK OF KAPUR SITE TW 1 FOR TESTING\"}', '2025-11-07 06:57:24'),
(37, 59, 'Kitkat Switch Board', 'USER 2', 'Updated', '{\"item_name\":\"Kitkat Switch Board\",\"make_model\":\"MODEM 232\",\"size_capacity\":\"10HP\",\"status\":\"Installed\",\"check_hmi_local\":1,\"check_web\":1,\"remark\":\">REMARK CHANGING FOR TESTING\"}', '{\"item_name\":\"Kitkat Switch Board\",\"make_model\":\"MODEM 232\",\"size_capacity\":\"100HP\",\"status\":\"Installed\",\"check_hmi_local\":1,\"check_web\":1,\"remark\":\">REMARK CHANGING FOR TESTING\"}', '2025-11-07 07:26:54'),
(38, 61, 'Kitkat Pump (RYB)', 'USER 2', 'Added', '{}', '{\"item_name\":\"Kitkat Pump (RYB)\",\"make_model\":\"MAKE 1\",\"size_capacity\":\"12hp\",\"status\":\"Supplied\",\"check_hmi_local\":1,\"check_web\":0,\"remark\":\"REMARK1\"}', '2025-11-07 09:45:54'),
(39, 53, 'Actuator bypass', 'Ashakiran Jyoti', 'Added', '{}', '{\"item_name\":\"Actuator bypass\",\"make_model\":\"MODEL MCOM 1\",\"size_capacity\":\"12HP\",\"status\":\"Not Supply\",\"check_hmi_local\":1,\"check_web\":1,\"remark\":\"REMARK CHANGING FOR TESTING\"}', '2025-11-10 12:34:08'),
(40, 53, 'Kitkat Pump (RYB)', 'Ashakiran Jyoti', 'Added', '{}', '{\"item_name\":\"Kitkat Pump (RYB)\",\"make_model\":\"make 1\",\"size_capacity\":\"12hp\",\"status\":\"In - installation\",\"check_hmi_local\":2,\"check_web\":2,\"remark\":\"remark 1 of USER 1\"}', '2025-11-11 05:14:51'),
(41, 53, 'Actuator bypass', 'Ashakiran Jyoti', 'Added', '{}', '{\"item_name\":\"Actuator bypass\",\"make_model\":\"MODEL MCOM 1\",\"size_capacity\":\"12HP\",\"status\":\"Not Supply\",\"check_hmi_local\":2,\"check_web\":1,\"remark\":\"REMARK CHANGING FOR TESTING\"}', '2025-11-11 05:15:02'),
(42, 53, 'Actuator bypass', 'Ashakiran Jyoti', 'Updated', '{\"item_name\":\"Actuator bypass\",\"make_model\":\"MODEL MCOM 1\",\"size_capacity\":\"12HP\",\"status\":\"Not Supply\",\"check_hmi_local\":2,\"check_web\":1,\"remark\":\"REMARK CHANGING FOR TESTING\"}', '{\"item_name\":\"Actuator bypass\",\"make_model\":\"MODEL MCOM 1\",\"size_capacity\":\"12HP\",\"status\":\"Not Supply\",\"check_hmi_local\":0,\"check_web\":1,\"remark\":\"REMARK CHANGING FOR TESTING\"}', '2025-11-11 05:15:08'),
(43, 53, 'Kitkat Switch Board', 'Ashakiran Jyoti', 'Added', '{}', '{\"item_name\":\"Kitkat Switch Board\",\"make_model\":\"\",\"size_capacity\":\"\",\"status\":\"Not Required\",\"check_hmi_local\":1,\"check_web\":0,\"remark\":\"\"}', '2025-11-11 05:15:50'),
(44, 53, 'Kitkat Switch Board', 'Ashakiran Jyoti', 'Updated', '{\"item_name\":\"Kitkat Switch Board\",\"make_model\":\"\",\"size_capacity\":\"\",\"status\":\"Not Required\",\"check_hmi_local\":1,\"check_web\":0,\"remark\":\"\"}', '{\"item_name\":\"Kitkat Switch Board\",\"make_model\":\"\",\"size_capacity\":\"\",\"status\":\"Not Required\",\"check_hmi_local\":2,\"check_web\":0,\"remark\":\"\"}', '2025-11-11 05:15:56'),
(45, 53, 'item 1', 'Ashakiran Jyoti', 'Added', '{}', '{\"item_name\":\"item 1\",\"make_model\":\"make 1\",\"size_capacity\":\"11hp\",\"status\":\"In - installation\",\"check_hmi_local\":2,\"check_web\":1,\"remark\":\"to check hmi and web logic working or not\"}', '2025-11-11 05:19:30'),
(46, 53, 'item 1', 'Ashakiran Jyoti', 'Updated', '{\"item_name\":\"item 1\",\"make_model\":\"make 1\",\"size_capacity\":\"11hp\",\"status\":\"In - installation\",\"check_hmi_local\":2,\"check_web\":1,\"remark\":\"to check hmi and web logic working or not\"}', '{\"item_name\":\"item 1\",\"make_model\":\"make 1\",\"size_capacity\":\"11hp\",\"status\":\"In - installation\",\"check_hmi_local\":2,\"check_web\":0,\"remark\":\"to check hmi and web logic working or not\"}', '2025-11-11 05:20:01'),
(47, 58, 'Kitkat Pump (RYB)', 'Ashakiran Jyoti', 'Added', '{}', '{\"item_name\":\"Kitkat Pump (RYB)\",\"make_model\":\"make 1\",\"size_capacity\":\"12hp\",\"status\":\"Installed\",\"check_hmi_local\":2,\"check_web\":1,\"remark\":\"remark 1 of USER 1\"}', '2025-11-11 10:16:05'),
(48, 53, 'Actuator bypass', 'Ashakiran Jyoti', 'Added', '{}', '{\"item_name\":\"Actuator bypass\",\"make_model\":\"12 make\",\"size_capacity\":\"12HP\",\"status\":\"In - installation\",\"check_hmi_local\":2,\"check_web\":1,\"remark\":\"REMARK CHANGING FOR TESTING\"}', '2025-11-12 04:15:44'),
(49, 53, 'item 1 12', 'Ashakiran Jyoti', 'Added', '{}', '{\"item_name\":\"item 1 12\",\"make_model\":\"12 make\",\"size_capacity\":\"12 hp\",\"status\":\"Installed\",\"check_hmi_local\":1,\"check_web\":1,\"remark\":\"remark\"}', '2025-11-12 04:16:17'),
(50, 53, 'sprae item', 'Ashakiran Jyoti', 'Added', '{}', '{\"item_name\":\"sprae item\",\"make_model\":\"make spare\",\"size_capacity\":\"12hp\",\"status\":\"Installed\",\"check_hmi_local\":2,\"check_web\":1,\"remark\":\"spare remark\"}', '2025-11-12 06:18:10'),
(51, 53, 'Actuator bypass', 'Ashakiran Jyoti', 'Updated', '{\"item_name\":\"Actuator bypass\",\"make_model\":\"12 make\",\"size_capacity\":\"12HP\",\"status\":\"In - installation\",\"check_hmi_local\":2,\"check_web\":1,\"remark\":\"REMARK CHANGING FOR TESTING\"}', '{\"item_name\":\"Actuator bypass\",\"make_model\":\"12 make\",\"size_capacity\":\"12HP\",\"status\":\"Not Working\",\"check_hmi_local\":2,\"check_web\":1,\"remark\":\"REMARK CHANGING FOR TESTING\"}', '2025-11-12 09:22:45'),
(52, 53, 'Actuator bypass', 'Ashakiran Jyoti', 'Updated', '{\"item_name\":\"Actuator bypass\",\"make_model\":\"12 make\",\"size_capacity\":\"12HP\",\"status\":\"Not Working\",\"check_hmi_local\":2,\"check_web\":1,\"remark\":\"REMARK CHANGING FOR TESTING\"}', '{\"item_name\":\"Actuator bypass\",\"make_model\":\"12 make\",\"size_capacity\":\"12HP\",\"status\":\"Not Working\",\"check_hmi_local\":0,\"check_web\":1,\"remark\":\"REMARK CHANGING FOR TESTING\"}', '2025-11-12 09:22:55'),
(53, 53, 'Actuator bypass', 'Ashakiran Jyoti', 'Updated', '{\"item_name\":\"Actuator bypass\",\"make_model\":\"12 make\",\"size_capacity\":\"12HP\",\"status\":\"Not Working\",\"check_hmi_local\":0,\"check_web\":1,\"remark\":\"REMARK CHANGING FOR TESTING\"}', '{\"item_name\":\"Actuator bypass\",\"make_model\":\"12 make\",\"size_capacity\":\"12HP\",\"status\":\"Not Working\",\"check_hmi_local\":0,\"check_web\":0,\"remark\":\"REMARK CHANGING FOR TESTING\"}', '2025-11-12 09:23:03'),
(54, 53, 'RTU Control Panel', 'Ashakiran Jyoti', 'Added', '{}', '{\"item_name\":\"RTU Control Panel\",\"make_model\":\"RTU CONTROL PANEL\",\"size_capacity\":\"15HP\",\"status\":\"Supplied\",\"check_hmi_local\":0,\"check_web\":0,\"remark\":\"REMARK CHANGING FOR TESTING  USER 2\"}', '2025-11-12 10:05:03'),
(55, 53, 'Kitkat Pump (RYB)', 'Ashakiran Jyoti', 'Added', '{}', '{\"item_name\":\"Kitkat Pump (RYB)\",\"make_model\":\"make 1\",\"size_capacity\":\"12hp\",\"status\":\"In - installation\",\"check_hmi_local\":0,\"check_web\":2,\"remark\":\"remark 1 of USER 1\"}', '2025-11-12 12:32:34'),
(56, 53, 'Actuator bypass', 'Ashakiran Jyoti', 'Updated', '{\"item_name\":\"Actuator bypass\",\"make_model\":\"12 make\",\"size_capacity\":\"12HP\",\"status\":\"Not Working\",\"check_hmi_local\":0,\"check_web\":0,\"remark\":\"REMARK CHANGING FOR TESTING\"}', '{\"item_name\":\"Actuator bypass\",\"make_model\":\"12 make\",\"size_capacity\":\"12HP\",\"status\":\"Not Working\",\"check_hmi_local\":1,\"check_web\":2,\"remark\":\"REMARK CHANGING FOR TESTING\"}', '2025-11-12 12:33:07'),
(57, 53, 'RTU Control Panel', 'Ashakiran Jyoti', 'Added', '{}', '{\"item_name\":\"RTU Control Panel\",\"make_model\":\"RTU CONTROL PANEL\",\"size_capacity\":\"15HP\",\"status\":\"Installed\",\"check_hmi_local\":1,\"check_web\":0,\"remark\":\"REMARK CHANGING FOR TESTING  USER 2\"}', '2025-11-13 04:23:10'),
(58, 55, 'RTU Control Panel', 'Ashakiran Jyoti', 'Added', '{}', '{\"item_name\":\"RTU Control Panel\",\"make_model\":\"KITKAT\",\"size_capacity\":\"32hp\",\"status\":\"Supplied\",\"check_hmi_local\":1,\"check_web\":2,\"remark\":\"kjasjdhajsd ahsdiashdiuasd ahdahdiuad iahdiahdiausd aihdiaushdiashd ashdiahdiausd aihdisahdh\"}', '2025-11-13 06:39:36'),
(59, 54, 'RTU Panel', 'Ashakiran Jyoti', 'Added', '{}', '{\"item_name\":\"RTU Panel\",\"make_model\":\"12 make\",\"size_capacity\":\"12hp\",\"status\":\"Not Supply\",\"check_hmi_local\":2,\"check_web\":1,\"remark\":\"REMARK\"}', '2025-11-13 11:51:02'),
(60, 54, 'RTU Panel', 'Ashakiran Jyoti', 'Updated', '{\"item_name\":\"RTU Panel\",\"make_model\":\"12 make\",\"size_capacity\":\"12hp\",\"status\":\"Not Supply\",\"check_hmi_local\":2,\"check_web\":1,\"remark\":\"REMARK\"}', '{\"item_name\":\"RTU Panel\",\"make_model\":\"12 make\",\"size_capacity\":\"12hp\",\"status\":\"Not Supply\",\"check_hmi_local\":0,\"check_web\":1,\"remark\":\"REMARK\"}', '2025-11-13 11:52:25'),
(61, 54, 'RTU Panel', 'Ashakiran Jyoti', 'Updated', '{\"item_name\":\"RTU Panel\",\"make_model\":\"12 make\",\"size_capacity\":\"12hp\",\"status\":\"Not Supply\",\"check_hmi_local\":0,\"check_web\":1,\"remark\":\"REMARK\"}', '{\"item_name\":\"RTU Panel\",\"make_model\":\"12 make\",\"size_capacity\":\"12hp\",\"status\":\"Not Supply\",\"check_hmi_local\":0,\"check_web\":1,\"remark\":\"REMARK\"}', '2025-11-13 11:52:45'),
(62, 55, 'spare item 1', 'Ashakiran Jyoti', 'Added', '{}', '{\"item_name\":\"spare item 1\",\"make_model\":\"spare make\",\"size_capacity\":\"11\",\"status\":\"Working\",\"check_hmi_local\":1,\"check_web\":0,\"remark\":\"spare item\"}', '2025-11-13 12:43:54'),
(63, 58, 'RTU Panel', 'Ashakiran Jyoti', 'Added', '{}', '{\"item_name\":\"RTU Panel\",\"make_model\":\"MODEL MCOM 1\",\"size_capacity\":\"12HP\",\"status\":\"Not Working\",\"check_hmi_local\":1,\"check_web\":2,\"remark\":\"kjasjdhajsd ahsdiashdiuasd ahdahdiuad iahdiahdiausd aihdiaushdiashd ashdiahdiausd aihdisahdh\"}', '2025-11-14 04:30:55'),
(64, 53, 'Kitkat Switch Board', 'Ashakiran Jyoti', 'Added', '{}', '{\"item_name\":\"Kitkat Switch Board\",\"make_model\":\"\",\"size_capacity\":\"\",\"status\":\"Not Required\",\"check_hmi_local\":1,\"check_web\":0,\"remark\":\"\"}', '2025-11-14 05:09:35'),
(65, 53, 'Kitkat Switch Board', 'Ashakiran Jyoti', 'Updated', '{\"item_name\":\"Kitkat Switch Board\",\"make_model\":\"\",\"size_capacity\":\"\",\"status\":\"Not Required\",\"check_hmi_local\":1,\"check_web\":0,\"remark\":\"\"}', '{\"item_name\":\"Kitkat Switch Board\",\"make_model\":\"\",\"size_capacity\":\"\",\"status\":\"Not Required\",\"check_hmi_local\":1,\"check_web\":2,\"remark\":\"\"}', '2025-11-14 05:09:49'),
(66, 53, 'Kitkat Switch Board', 'Ashakiran Jyoti', 'Updated', '{\"item_name\":\"Kitkat Switch Board\",\"make_model\":\"\",\"size_capacity\":\"\",\"status\":\"Not Required\",\"check_hmi_local\":1,\"check_web\":2,\"remark\":\"\"}', '{\"item_name\":\"Kitkat Switch Board\",\"make_model\":\"\",\"size_capacity\":\"\",\"status\":\"Not Required\",\"check_hmi_local\":1,\"check_web\":0,\"remark\":\"\"}', '2025-11-14 05:10:21'),
(67, 53, 'Kitkat Switch Board', 'Ashakiran Jyoti', 'Updated', '{\"item_name\":\"Kitkat Switch Board\",\"make_model\":\"\",\"size_capacity\":\"\",\"status\":\"Not Required\",\"check_hmi_local\":1,\"check_web\":0,\"remark\":\"\"}', '{\"item_name\":\"Kitkat Switch Board\",\"make_model\":\"\",\"size_capacity\":\"\",\"status\":\"Not Required\",\"check_hmi_local\":2,\"check_web\":0,\"remark\":\"\"}', '2025-11-14 05:10:33'),
(68, 53, 'Kitkat Switch Board', 'Ashakiran Jyoti', 'Updated', '{\"item_name\":\"Kitkat Switch Board\",\"make_model\":\"\",\"size_capacity\":\"\",\"status\":\"Not Required\",\"check_hmi_local\":2,\"check_web\":0,\"remark\":\"\"}', '{\"item_name\":\"Kitkat Switch Board\",\"make_model\":\"\",\"size_capacity\":\"\",\"status\":\"Not Required\",\"check_hmi_local\":0,\"check_web\":0,\"remark\":\"\"}', '2025-11-14 05:10:57'),
(69, 53, 'RTU Control Panel', 'Ashakiran Jyoti', 'Added', '{}', '{\"item_name\":\"RTU Control Panel\",\"make_model\":\"RTU CONTROL PANEL\",\"size_capacity\":\"15HP\",\"status\":\"Installed\",\"check_hmi_local\":1,\"check_web\":2,\"remark\":\"REMARK CHANGING FOR TESTING  USER 2\"}', '2025-11-14 05:13:33'),
(70, 53, 'RTU Control Panel', 'Ashakiran Jyoti', 'Updated', '{\"item_name\":\"RTU Control Panel\",\"make_model\":\"RTU CONTROL PANEL\",\"size_capacity\":\"15HP\",\"status\":\"Installed\",\"check_hmi_local\":1,\"check_web\":2,\"remark\":\"REMARK CHANGING FOR TESTING  USER 2\"}', '{\"item_name\":\"RTU Control Panel\",\"make_model\":\"RTU CONTROL PANEL\",\"size_capacity\":\"15HP\",\"status\":\"Installed\",\"check_hmi_local\":1,\"check_web\":0,\"remark\":\"REMARK CHANGING FOR TESTING  USER 2\"}', '2025-11-14 05:13:41'),
(71, 53, 'RTU Control Panel', 'Ashakiran Jyoti', 'Updated', '{\"item_name\":\"RTU Control Panel\",\"make_model\":\"RTU CONTROL PANEL\",\"size_capacity\":\"15HP\",\"status\":\"Installed\",\"check_hmi_local\":1,\"check_web\":0,\"remark\":\"REMARK CHANGING FOR TESTING  USER 2\"}', '{\"item_name\":\"RTU Control Panel\",\"make_model\":\"RTU CONTROL PANEL\",\"size_capacity\":\"15HP\",\"status\":\"Installed\",\"check_hmi_local\":1,\"check_web\":0,\"remark\":\"REMARK CHANGING FOR TESTING  USER 2\"}', '2025-11-14 05:15:58'),
(72, 53, 'Actuator bypass', 'Ashakiran Jyoti', 'Added', '{}', '{\"item_name\":\"Actuator bypass\",\"make_model\":\"12 make\",\"size_capacity\":\"12HP\",\"status\":\"Not Working\",\"check_hmi_local\":1,\"check_web\":2,\"remark\":\"REMARK CHANGING FOR TESTING\"}', '2025-11-14 05:27:36'),
(73, 53, 'Actuator bypass', 'Ashakiran Jyoti', 'Updated', '{\"item_name\":\"Actuator bypass\",\"make_model\":\"12 make\",\"size_capacity\":\"12HP\",\"status\":\"Not Working\",\"check_hmi_local\":1,\"check_web\":2,\"remark\":\"REMARK CHANGING FOR TESTING\"}', '{\"item_name\":\"Actuator bypass\",\"make_model\":\"12 make\",\"size_capacity\":\"12HP\",\"status\":\"Not Working\",\"check_hmi_local\":1,\"check_web\":2,\"remark\":\"test\"}', '2025-11-14 05:28:33'),
(74, 55, 'Kitkat Pump (RYB)', 'Ashakiran Jyoti', 'Added', '{}', '{\"item_name\":\"Kitkat Pump (RYB)\",\"make_model\":\"MODEL MCOM 1\",\"size_capacity\":\"25HP\",\"status\":\"Supplied\",\"check_hmi_local\":2,\"check_web\":2,\"remark\":\"test\"}', '2025-11-14 05:29:59'),
(75, 55, 'Kitkat Switch Board', 'Ashakiran Jyoti', 'Added', '{}', '{\"item_name\":\"Kitkat Switch Board\",\"make_model\":\"KITKAT\",\"size_capacity\":\"32hp\",\"status\":\"Not Supply\",\"check_hmi_local\":1,\"check_web\":2,\"remark\":\"to test\"}', '2025-11-14 06:00:57'),
(76, 55, 'Kitkat Switch Board', 'Ashakiran Jyoti', 'Updated', '{\"item_name\":\"Kitkat Switch Board\",\"make_model\":\"KITKAT\",\"size_capacity\":\"32hp\",\"status\":\"Not Supply\",\"check_hmi_local\":1,\"check_web\":2,\"remark\":\"to test\"}', '{\"item_name\":\"Kitkat Switch Board\",\"make_model\":\"KITKAT\",\"size_capacity\":\"32hp\",\"status\":\"Not Supply\",\"check_hmi_local\":1,\"check_web\":2,\"remark\":\"to test\"}', '2025-11-14 06:01:32'),
(77, 55, 'Kitkat Switch Board', 'Ashakiran Jyoti', 'Updated', '{\"item_name\":\"Kitkat Switch Board\",\"make_model\":\"KITKAT\",\"size_capacity\":\"32hp\",\"status\":\"Not Supply\",\"check_hmi_local\":1,\"check_web\":2,\"remark\":\"to test\"}', '{\"item_name\":\"Kitkat Switch Board\",\"make_model\":\"KITKAT\",\"size_capacity\":\"32hp\",\"status\":\"Not Supply\",\"check_hmi_local\":1,\"check_web\":2,\"remark\":\"to test\"}', '2025-11-14 06:02:06'),
(78, 54, 'Kitkat Switch Board', 'Ashakiran Jyoti', 'Added', '{}', '{\"item_name\":\"Kitkat Switch Board\",\"make_model\":\"check\",\"size_capacity\":\"1hp\",\"status\":\"In - installation\",\"check_hmi_local\":0,\"check_web\":0,\"remark\":\"media check\"}', '2025-11-14 06:05:07'),
(79, 54, 'RTU Control Panel', 'Ashakiran Jyoti', 'Added', '{}', '{\"item_name\":\"RTU Control Panel\",\"make_model\":\"MCOM2\",\"size_capacity\":\"26HP\",\"status\":\"In - installation\",\"check_hmi_local\":1,\"check_web\":1,\"remark\":\"REMARK1\"}', '2025-11-14 06:22:24'),
(80, 54, 'RTU Control Panel', 'USER 2', 'Updated', '{\"item_name\":\"RTU Control Panel\",\"make_model\":\"MCOM2\",\"size_capacity\":\"26HP\",\"status\":\"In - installation\",\"check_hmi_local\":1,\"check_web\":1,\"remark\":\"REMARK1\"}', '{\"item_name\":\"RTU Control Panel\",\"make_model\":\"MCOM2\",\"size_capacity\":\"26HP\",\"status\":\"In - installation\",\"check_hmi_local\":1,\"check_web\":1,\"remark\":\"REMARK1\"}', '2025-11-14 06:43:37'),
(81, 54, 'RTU Control Panel', 'Ashakiran Jyoti', 'Updated', '{\"item_name\":\"RTU Control Panel\",\"make_model\":\"MCOM2\",\"size_capacity\":\"26HP\",\"status\":\"In - installation\",\"check_hmi_local\":1,\"check_web\":1,\"remark\":\"REMARK1\"}', '{\"item_name\":\"RTU Control Panel\",\"make_model\":\"MCOM2\",\"size_capacity\":\"26HP\",\"status\":\"In - installation\",\"check_hmi_local\":1,\"check_web\":1,\"remark\":\"REMARK1\"}', '2025-11-14 06:44:09'),
(82, 54, 'Kitkat Switch Board', 'Ashakiran Jyoti', 'Updated', '{\"item_name\":\"Kitkat Switch Board\",\"make_model\":\"check\",\"size_capacity\":\"1hp\",\"status\":\"In - installation\",\"check_hmi_local\":0,\"check_web\":0,\"remark\":\"media check\"}', '{\"item_name\":\"Kitkat Switch Board\",\"make_model\":\"check\",\"size_capacity\":\"1hp\",\"status\":\"In - installation\",\"check_hmi_local\":0,\"check_web\":1,\"remark\":\"media check\"}', '2025-11-14 08:59:14'),
(83, 54, 'RTU Control Panel', 'Ashakiran Jyoti', 'Updated', '{\"item_name\":\"RTU Control Panel\",\"make_model\":\"MCOM2\",\"size_capacity\":\"26HP\",\"status\":\"In - installation\",\"check_hmi_local\":1,\"check_web\":1,\"remark\":\"REMARK1\"}', '{\"item_name\":\"RTU Control Panel\",\"make_model\":\"MCOM2\",\"size_capacity\":\"26HP\",\"status\":\"In - installation\",\"check_hmi_local\":1,\"check_web\":2,\"remark\":\"REMARK1\"}', '2025-11-14 08:59:23'),
(84, 54, 'RTU Control Panel', 'Ashakiran Jyoti', 'Updated', '{\"item_name\":\"RTU Control Panel\",\"make_model\":\"MCOM2\",\"size_capacity\":\"26HP\",\"status\":\"In - installation\",\"check_hmi_local\":1,\"check_web\":2,\"remark\":\"REMARK1\"}', '{\"item_name\":\"RTU Control Panel\",\"make_model\":\"MCOM2\",\"size_capacity\":\"26HP\",\"status\":\"In - installation\",\"check_hmi_local\":1,\"check_web\":0,\"remark\":\"REMARK1\"}', '2025-11-14 08:59:29'),
(85, 53, 'sprae item', 'Ashakiran Jyoti', 'Added', '{}', '{\"item_name\":\"sprae item\",\"make_model\":\"make spare\",\"size_capacity\":\"12hp\",\"status\":\"Installed\",\"check_hmi_local\":2,\"check_web\":1,\"remark\":\"spare remark\"}', '2025-11-14 09:45:36'),
(86, 63, 'Kitkat Pump (RYB)', 'Ashakiran Jyoti', 'Added', '{}', '{\"item_name\":\"Kitkat Pump (RYB)\",\"make_model\":\"MODEL MCOM 1\",\"size_capacity\":\"12HP\",\"status\":\"Not Supply\",\"check_hmi_local\":1,\"check_web\":1,\"remark\":\"REMARK1\"}', '2025-11-14 11:06:25'),
(87, 63, 'tw spare item', 'Ashakiran Jyoti', 'Added', '{}', '{\"item_name\":\"tw spare item\",\"make_model\":\"make\",\"size_capacity\":\"12 hh\",\"status\":\"Not Working\",\"check_hmi_local\":1,\"check_web\":2,\"remark\":\"rrrrr\"}', '2025-11-14 11:07:28'),
(88, 63, 'tw spare item', 'Ashakiran Jyoti', 'Updated', '{\"item_name\":\"tw spare item\",\"make_model\":\"make\",\"size_capacity\":\"12 hh\",\"status\":\"Not Working\",\"check_hmi_local\":1,\"check_web\":2,\"remark\":\"rrrrr\"}', '{\"item_name\":\"tw spare item\",\"make_model\":\"make\",\"size_capacity\":\"12 hh\",\"status\":\"Not Working\",\"check_hmi_local\":1,\"check_web\":0,\"remark\":\"rrrrr\"}', '2025-11-14 11:07:39'),
(89, 63, 'tw spare item', 'USER 2', 'Updated', '{\"item_name\":\"tw spare item\",\"make_model\":\"make\",\"size_capacity\":\"12 hh\",\"status\":\"Not Working\",\"check_hmi_local\":1,\"check_web\":0,\"remark\":\"rrrrr\"}', '{\"item_name\":\"tw spare item\",\"make_model\":\"make\",\"size_capacity\":\"12 hh\",\"status\":\"Not Working\",\"check_hmi_local\":1,\"check_web\":0,\"remark\":\"rrrrr\"}', '2025-11-14 11:09:26'),
(90, 55, 'Kitkat Switch Board', 'Ashakiran Jyoti', 'Updated', '{\"item_name\":\"Kitkat Switch Board\",\"make_model\":\"KITKAT\",\"size_capacity\":\"32hp\",\"status\":\"Not Supply\",\"check_hmi_local\":1,\"check_web\":2,\"remark\":\"to test\"}', '{\"item_name\":\"Kitkat Switch Board\",\"make_model\":\"KITKAT\",\"size_capacity\":\"32hp\",\"status\":\"Not Supply\",\"check_hmi_local\":1,\"check_web\":2,\"remark\":\"to test\"}', '2025-11-14 11:38:22'),
(91, 53, 'Actuator bypass', 'Ashakiran Jyoti', 'Updated', '{\"item_name\":\"Actuator bypass\",\"make_model\":\"12 make\",\"size_capacity\":\"12HP\",\"status\":\"Not Working\",\"check_hmi_local\":1,\"check_web\":2,\"remark\":\"test\"}', '{\"item_name\":\"Actuator bypass\",\"make_model\":\"12 make\",\"size_capacity\":\"12HP\",\"status\":\"Not Working\",\"check_hmi_local\":1,\"check_web\":0,\"remark\":\"test\"}', '2025-11-14 12:20:32'),
(92, 53, 'Actuator bypass', 'Ashakiran Jyoti', 'Added', '{}', '{\"item_name\":\"Actuator bypass\",\"make_model\":\"12 make\",\"size_capacity\":\"12HP\",\"status\":\"Not Working\",\"check_hmi_local\":1,\"check_web\":1,\"remark\":\"media test\"}', '2025-11-15 04:40:10'),
(93, 53, 'Kitkat Pump (RYB)', 'Ashakiran Jyoti', 'Added', '{}', '{\"item_name\":\"Kitkat Pump (RYB)\",\"make_model\":\"make 1\",\"size_capacity\":\"12hp\",\"status\":\"In - installation\",\"check_hmi_local\":0,\"check_web\":0,\"remark\":\"remark 1 of USER 1\"}', '2025-11-15 04:40:55'),
(94, 54, 'RTU Control Panel', 'Ashakiran Jyoti', 'Added', '{}', '{\"item_name\":\"RTU Control Panel\",\"make_model\":\"MCOM2\",\"size_capacity\":\"26HP\",\"status\":\"In - installation\",\"check_hmi_local\":1,\"check_web\":0,\"remark\":\"media test\"}', '2025-11-15 04:43:07'),
(95, 64, 'Kitkat Pump (RYB)', 'Ashakiran Jyoti', 'Added', '{}', '{\"item_name\":\"Kitkat Pump (RYB)\",\"make_model\":\"MODEL MCOM 1\",\"size_capacity\":\"12HP\",\"status\":\"Not Required\",\"check_hmi_local\":2,\"check_web\":0,\"remark\":\"REMARK1\"}', '2025-11-15 11:17:53'),
(96, 64, 'SPARE ITEM 1', 'Ashakiran Jyoti', 'Added', '{}', '{\"item_name\":\"SPARE ITEM 1\",\"make_model\":\"SPARE ITEM\",\"size_capacity\":\"SPARE SIZE\",\"status\":\"Not Working\",\"check_hmi_local\":0,\"check_web\":0,\"remark\":\"SPARE REMARK\"}', '2025-11-15 11:21:46'),
(97, 64, 'RTU Control Panel', 'Ashakiran Jyoti', 'Added', '{}', '{\"item_name\":\"RTU Control Panel\",\"make_model\":\"MODEL MCOM 1\",\"size_capacity\":\"12HP\",\"status\":\"Not Supply\",\"check_hmi_local\":1,\"check_web\":1,\"remark\":\"REMARK1\"}', '2025-11-15 11:29:59'),
(98, 64, 'spare item 2', 'Ravikumar Shukla', 'Added', '{}', '{\"item_name\":\"spare item 2\",\"make_model\":\"mmm\",\"size_capacity\":\"sss\",\"status\":\"Installed\",\"check_hmi_local\":1,\"check_web\":2,\"remark\":\"spare item 2 remark\"}', '2025-11-15 12:29:51'),
(99, 64, 'Kitkat Pump (RYB)', 'Ashakiran Jyoti', 'Added', '{}', '{\"item_name\":\"Kitkat Pump (RYB)\",\"make_model\":\"MODEL MCOM 1\",\"size_capacity\":\"12HP\",\"status\":\"Not Required\",\"check_hmi_local\":2,\"check_web\":0,\"remark\":\"REMARK1\"}', '2025-11-17 04:14:17'),
(100, 64, 'spareeee', 'Ashakiran Jyoti', 'Added', '{}', '{\"item_name\":\"spareeee\",\"make_model\":\"jkjkjkjkj\",\"size_capacity\":\"jmjmjmjm\",\"status\":\"In - installation\",\"check_hmi_local\":2,\"check_web\":1,\"remark\":\"klklklklklklklk\"}', '2025-11-17 04:30:55'),
(101, 53, 'Kitkat Pump (RYB)', 'Ashakiran Jyoti', 'Added', '{}', '{\"item_name\":\"Kitkat Pump (RYB)\",\"make_model\":\"make 1\",\"size_capacity\":\"12hp\",\"status\":\"In - installation\",\"check_hmi_local\":0,\"check_web\":0,\"remark\":\"remark 1 of USER 1\"}', '2025-11-17 11:43:09'),
(102, 53, 'Kitkat Pump (RYB)', 'Ashakiran Jyoti', 'Added', '{}', '{\"item_name\":\"Kitkat Pump (RYB)\",\"make_model\":\"make 1\",\"size_capacity\":\"12hp\",\"status\":\"In - installation\",\"check_hmi_local\":0,\"check_web\":0,\"remark\":\"remark 1 of USER 1\"}', '2025-11-18 07:15:41'),
(103, 53, 'item 1', 'Ravikumar Shukla', 'Added', '{}', '{\"item_name\":\"item 1\",\"make_model\":\"make 1\",\"size_capacity\":\"11hp\",\"status\":\"In - installation\",\"check_hmi_local\":1,\"check_web\":1,\"remark\":\"to check hmi and web logic working or not\"}', '2025-11-18 08:56:55'),
(104, 53, 'RTU Panel', 'Ravikumar Shukla', 'Added', '{}', '{\"item_name\":\"RTU Panel\",\"make_model\":\"\",\"size_capacity\":\"\",\"status\":\"Not Required\",\"check_hmi_local\":2,\"check_web\":2,\"remark\":\"\"}', '2025-11-18 12:08:54'),
(105, 55, 'Kitkat Pump (RYB)', 'Ravikumar Shukla', 'Added', '{}', '{\"item_name\":\"Kitkat Pump (RYB)\",\"make_model\":\"MODEL MCOM 1\",\"size_capacity\":\"25HP\",\"status\":\"Supplied\",\"check_hmi_local\":2,\"check_web\":2,\"remark\":\"test\"}', '2025-11-19 05:36:02'),
(106, 54, 'Kitkat Pump (RYB)', 'Ravikumar Shukla', 'Added', '{}', '{\"item_name\":\"Kitkat Pump (RYB)\",\"make_model\":\"MODEL MCOM 1\",\"size_capacity\":\"12HP\",\"status\":\"Not Required\",\"check_hmi_local\":1,\"check_web\":2,\"remark\":\"REMARK CHANGING FOR TESTING\"}', '2025-11-19 06:54:50'),
(107, 54, 'hhhh', 'Ravikumar Shukla', 'Added', '{}', '{\"item_name\":\"hhhh\",\"make_model\":\"jjjjj\",\"size_capacity\":\"dgfdgd\",\"status\":\"Not Required\",\"check_hmi_local\":1,\"check_web\":2,\"remark\":\"jhjgdfateyiujvcfs\"}', '2025-11-19 07:51:55'),
(108, 54, 'hhhh', 'Ravikumar Shukla', 'Updated', '{\"item_name\":\"hhhh\",\"make_model\":\"jjjjj\",\"size_capacity\":\"dgfdgd\",\"status\":\"Not Required\",\"check_hmi_local\":1,\"check_web\":2,\"remark\":\"jhjgdfateyiujvcfs\"}', '{\"item_name\":\"hhhh\",\"make_model\":\"jjjjj\",\"size_capacity\":\"dgfdgd\",\"status\":\"Not Required\",\"check_hmi_local\":1,\"check_web\":2,\"remark\":\"jhjgdfateyiujvcfs\"}', '2025-11-19 07:52:08'),
(109, 54, 'RTU Control Panel', 'USER 2', 'Added', '{}', '{\"item_name\":\"RTU Control Panel\",\"make_model\":\"MCOM2\",\"size_capacity\":\"26HP\",\"status\":\"In - installation\",\"check_hmi_local\":1,\"check_web\":0,\"remark\":\"media test\"}', '2025-11-19 08:44:18'),
(110, 54, 'hhhh', 'USER 2', 'Updated', '{\"item_name\":\"hhhh\",\"make_model\":\"jjjjj\",\"size_capacity\":\"dgfdgd\",\"status\":\"Not Required\",\"check_hmi_local\":1,\"check_web\":2,\"remark\":\"jhjgdfateyiujvcfs\"}', '{\"item_name\":\"hhhh\",\"make_model\":\"jjjjj\",\"size_capacity\":\"dgfdgd\",\"status\":\"Not Required\",\"check_hmi_local\":1,\"check_web\":2,\"remark\":\"jhjgdfateyiujvcfs\"}', '2025-11-19 08:48:24'),
(111, 54, 'Kitkat Switch Board', 'USER 2', 'Added', '{}', '{\"item_name\":\"Kitkat Switch Board\",\"make_model\":\"check\",\"size_capacity\":\"1hp\",\"status\":\"In - installation\",\"check_hmi_local\":2,\"check_web\":2,\"remark\":\"media check\"}', '2025-11-19 09:02:51'),
(112, 54, 'Kitkat Switch Board', 'USER 2', 'Updated', '{\"item_name\":\"Kitkat Switch Board\",\"make_model\":\"check\",\"size_capacity\":\"1hp\",\"status\":\"In - installation\",\"check_hmi_local\":2,\"check_web\":2,\"remark\":\"media check\"}', '{\"item_name\":\"Kitkat Switch Board\",\"make_model\":\"check\",\"size_capacity\":\"1hp\",\"status\":\"In - installation\",\"check_hmi_local\":2,\"check_web\":2,\"remark\":\"media check\"}', '2025-11-19 09:27:32'),
(113, 54, 'Kitkat Switch Board', 'USER 2', 'Updated', '{\"item_name\":\"Kitkat Switch Board\",\"make_model\":\"check\",\"size_capacity\":\"1hp\",\"status\":\"In - installation\",\"check_hmi_local\":2,\"check_web\":2,\"remark\":\"media check\"}', '{\"item_name\":\"Kitkat Switch Board\",\"make_model\":\"check\",\"size_capacity\":\"1hp\",\"status\":\"In - installation\",\"check_hmi_local\":2,\"check_web\":2,\"remark\":\"media check\"}', '2025-11-19 09:28:04'),
(114, 54, 'RTU Control Panel', 'USER 2', 'Updated', '{\"item_name\":\"RTU Control Panel\",\"make_model\":\"MCOM2\",\"size_capacity\":\"26HP\",\"status\":\"In - installation\",\"check_hmi_local\":1,\"check_web\":0,\"remark\":\"media test\"}', '{\"item_name\":\"RTU Control Panel\",\"make_model\":\"MCOM2\",\"size_capacity\":\"26HP\",\"status\":\"In - installation\",\"check_hmi_local\":1,\"check_web\":0,\"remark\":\"media test\"}', '2025-11-19 10:04:32'),
(115, 54, 'Kitkat Switch Board', 'USER 2', 'Updated', '{\"item_name\":\"Kitkat Switch Board\",\"make_model\":\"check\",\"size_capacity\":\"1hp\",\"status\":\"In - installation\",\"check_hmi_local\":2,\"check_web\":2,\"remark\":\"media check\"}', '{\"item_name\":\"Kitkat Switch Board\",\"make_model\":\"check\",\"size_capacity\":\"1hp\",\"status\":\"In - installation\",\"check_hmi_local\":2,\"check_web\":2,\"remark\":\"media check\"}', '2025-11-19 10:04:49'),
(116, 54, 'Kitkat Switch Board', 'USER 2', 'Updated', '{\"item_name\":\"Kitkat Switch Board\",\"make_model\":\"check\",\"size_capacity\":\"1hp\",\"status\":\"In - installation\",\"check_hmi_local\":2,\"check_web\":2,\"remark\":\"media check\"}', '{\"item_name\":\"Kitkat Switch Board\",\"make_model\":\"check\",\"size_capacity\":\"1hp\",\"status\":\"In - installation\",\"check_hmi_local\":2,\"check_web\":2,\"remark\":\"media check\"}', '2025-11-19 10:05:34'),
(117, 54, 'Testing Item', 'USER 2', 'Added', '{}', '{\"item_name\":\"Testing Item\",\"make_model\":\"Testing mcom232\",\"size_capacity\":\"14hp\",\"status\":\"Not Required\",\"check_hmi_local\":1,\"check_web\":2,\"remark\":\"Remark changed make\"}', '2025-11-19 10:56:30'),
(118, 54, 'RTU Control Panel', 'USER 2', 'Updated', '{\"item_name\":\"RTU Control Panel\",\"make_model\":\"MCOM2\",\"size_capacity\":\"26HP\",\"status\":\"In - installation\",\"check_hmi_local\":1,\"check_web\":0,\"remark\":\"media test\"}', '{\"item_name\":\"RTU Control Panel\",\"make_model\":\"MCOM2\",\"size_capacity\":\"26HP\",\"status\":\"In - installation\",\"check_hmi_local\":1,\"check_web\":0,\"remark\":\"media test\"}', '2025-11-19 12:32:56'),
(119, 54, 'RTU Control Panel', 'USER 2', 'Updated', '{\"item_name\":\"RTU Control Panel\",\"make_model\":\"MCOM2\",\"size_capacity\":\"26HP\",\"status\":\"In - installation\",\"check_hmi_local\":1,\"check_web\":0,\"remark\":\"media test\"}', '{\"item_name\":\"RTU Control Panel\",\"make_model\":\"MCOM2\",\"size_capacity\":\"26HP\",\"status\":\"In - installation\",\"check_hmi_local\":1,\"check_web\":0,\"remark\":\"media test\"}', '2025-11-19 12:33:06'),
(120, 54, 'Kitkat Pump (RYB)', 'Ashakiran Jyoti', 'Added', '{}', '{\"item_name\":\"Kitkat Pump (RYB)\",\"make_model\":\"MODEL MCOM 1\",\"size_capacity\":\"12HP\",\"status\":\"Not Required\",\"check_hmi_local\":0,\"check_web\":0,\"remark\":\"REMARK CHANGING FOR TESTING\"}', '2025-11-20 04:49:28'),
(121, 53, 'Kitkat Switch Board', 'Ashakiran Jyoti', 'Added', '{}', '{\"item_name\":\"Kitkat Switch Board\",\"make_model\":\"\",\"size_capacity\":\"\",\"status\":\"Not Required\",\"check_hmi_local\":0,\"check_web\":0,\"remark\":\"ccccc\"}', '2025-11-20 11:29:23'),
(122, 53, 'Kitkat Pump (RYB)', 'Ashakiran Jyoti', 'Added', '{}', '{\"item_name\":\"Kitkat Pump (RYB)\",\"make_model\":\"make 1\",\"size_capacity\":\"12hp\",\"status\":\"In - installation\",\"check_hmi_local\":0,\"check_web\":2,\"remark\":\"remark 1 of USER 1\"}', '2025-11-21 04:50:12'),
(123, 67, 'Kitkat Pump (RYB)', 'Ashakiran Jyoti', 'Added', '{}', '{\"item_name\":\"Kitkat Pump (RYB)\",\"make_model\":\"\",\"size_capacity\":\"\",\"status\":\"Not Required\",\"check_hmi_local\":2,\"check_web\":2,\"remark\":\"REMARK CHANGING FOR TESTING\"}', '2025-11-21 12:43:21'),
(124, 53, 'Kitkat Pump (RYB)', 'Ashakiran Jyoti', 'Added', '{}', '{\"item_name\":\"Kitkat Pump (RYB)\",\"make_model\":\"make 1\",\"size_capacity\":\"12hp\",\"status\":\"In - installation\",\"check_hmi_local\":0,\"check_web\":0,\"remark\":\"remark 1 of USER 1\"}', '2025-11-24 04:30:02'),
(125, 53, 'Actuator bypass', 'Ashakiran Jyoti', 'Added', '{}', '{\"item_name\":\"Actuator bypass\",\"make_model\":\"12 make\",\"size_capacity\":\"12HP\",\"status\":\"Not Working\",\"check_hmi_local\":1,\"check_web\":1,\"remark\":\"media test\"}', '2025-11-24 07:11:22'),
(126, 55, 'Kitkat Pump (RYB)', 'Ashakiran Jyoti', 'Added', '{}', '{\"item_name\":\"Kitkat Pump (RYB)\",\"make_model\":\"MODEL MCOM 1\",\"size_capacity\":\"25HP\",\"status\":\"Supplied\",\"check_hmi_local\":2,\"check_web\":2,\"remark\":\"test\"}', '2025-11-25 04:39:54'),
(127, 55, 'Kitkat Pump (RYB)', 'Ashakiran Jyoti', 'Updated', '{\"item_name\":\"Kitkat Pump (RYB)\",\"make_model\":\"MODEL MCOM 1\",\"size_capacity\":\"25HP\",\"status\":\"Supplied\",\"check_hmi_local\":2,\"check_web\":2,\"remark\":\"test\"}', '{\"item_name\":\"Kitkat Pump (RYB)\",\"make_model\":\"MODEL MCOM 1\",\"size_capacity\":\"25HP\",\"status\":\"Supplied\",\"check_hmi_local\":2,\"check_web\":2,\"remark\":\"test\"}', '2025-11-25 04:40:18'),
(128, 55, 'Kitkat Pump (RYB)', 'Ravikumar Shukla', 'Updated', '{\"item_name\":\"Kitkat Pump (RYB)\",\"make_model\":\"MODEL MCOM 1\",\"size_capacity\":\"25HP\",\"status\":\"Supplied\",\"check_hmi_local\":2,\"check_web\":2,\"remark\":\"test\"}', '{\"item_name\":\"Kitkat Pump (RYB)\",\"make_model\":\"MODEL MCOM 1\",\"size_capacity\":\"25HP\",\"status\":\"Supplied\",\"check_hmi_local\":2,\"check_web\":2,\"remark\":\"test\"}', '2025-11-25 05:55:26'),
(129, 53, 'Kitkat Pump (RYB)', 'Ravikumar Shukla', 'Added', '{}', '{\"item_name\":\"Kitkat Pump (RYB)\",\"make_model\":\"make 1\",\"size_capacity\":\"12hp\",\"status\":\"In - installation\",\"check_hmi_local\":0,\"check_web\":0,\"remark\":\"remark 1 of USER 1\"}', '2025-11-25 06:31:33'),
(130, 53, 'Kitkat Pump (RYB)', 'Ravikumar Shukla', 'Updated', '{\"item_name\":\"Kitkat Pump (RYB)\",\"make_model\":\"make 1\",\"size_capacity\":\"12hp\",\"status\":\"In - installation\",\"check_hmi_local\":0,\"check_web\":0,\"remark\":\"remark 1 of USER 1\"}', '{\"item_name\":\"Kitkat Pump (RYB)\",\"make_model\":\"make 1\",\"size_capacity\":\"12hp\",\"status\":\"In - installation\",\"check_hmi_local\":0,\"check_web\":0,\"remark\":\"remark 1 of USER 1\"}', '2025-11-25 06:31:41'),
(131, 53, 'Kitkat Pump (RYB)', 'Ravikumar Shukla', 'Updated', '{\"item_name\":\"Kitkat Pump (RYB)\",\"make_model\":\"make 1\",\"size_capacity\":\"12hp\",\"status\":\"In - installation\",\"check_hmi_local\":0,\"check_web\":0,\"remark\":\"remark 1 of USER 1\"}', '{\"item_name\":\"Kitkat Pump (RYB)\",\"make_model\":\"make 1\",\"size_capacity\":\"12hp\",\"status\":\"In - installation\",\"check_hmi_local\":0,\"check_web\":0,\"remark\":\"remark 1 of USER 1\"}', '2025-11-25 06:51:06'),
(132, 53, 'Kitkat Pump (RYB)', 'Ashakiran Jyoti', 'Updated', '{\"item_name\":\"Kitkat Pump (RYB)\",\"make_model\":\"make 1\",\"size_capacity\":\"12hp\",\"status\":\"In - installation\",\"check_hmi_local\":0,\"check_web\":0,\"remark\":\"remark 1 of USER 1\"}', '{\"item_name\":\"Kitkat Pump (RYB)\",\"make_model\":\"make 1\",\"size_capacity\":\"12hp\",\"status\":\"In - installation\",\"check_hmi_local\":0,\"check_web\":0,\"remark\":\"remark 1 of USER 1\"}', '2025-11-25 07:46:40'),
(133, 53, 'Kitkat Pump (RYB)', 'Ashakiran Jyoti', 'Updated', '{\"item_name\":\"Kitkat Pump (RYB)\",\"make_model\":\"make 1\",\"size_capacity\":\"12hp\",\"status\":\"In - installation\",\"check_hmi_local\":0,\"check_web\":0,\"remark\":\"remark 1 of USER 1\"}', '{\"item_name\":\"Kitkat Pump (RYB)\",\"make_model\":\"make 1\",\"size_capacity\":\"12hp\",\"status\":\"In - installation\",\"check_hmi_local\":0,\"check_web\":0,\"remark\":\"remark 1 of USER 1\"}', '2025-11-25 07:47:13'),
(134, 55, 'Kitkat Pump (RYB)', 'Ashakiran Jyoti', 'Updated', '{\"item_name\":\"Kitkat Pump (RYB)\",\"make_model\":\"MODEL MCOM 1\",\"size_capacity\":\"25HP\",\"status\":\"Supplied\",\"check_hmi_local\":2,\"check_web\":2,\"remark\":\"test\"}', '{\"item_name\":\"Kitkat Pump (RYB)\",\"make_model\":\"MODEL MCOM 1\",\"size_capacity\":\"25HP\",\"status\":\"Supplied\",\"check_hmi_local\":2,\"check_web\":2,\"remark\":\"test\"}', '2025-11-25 09:05:51'),
(135, 53, 'Actuator bypass', 'Ashakiran Jyoti', 'Added', '{}', '{\"item_name\":\"Actuator bypass\",\"make_model\":\"12 make\",\"size_capacity\":\"12HP\",\"status\":\"Not Working\",\"check_hmi_local\":1,\"check_web\":1,\"remark\":\"media test\"}', '2025-11-25 09:15:31'),
(136, 53, 'Actuator bypass', 'Ashakiran Jyoti', 'Updated', '{\"item_name\":\"Actuator bypass\",\"make_model\":\"12 make\",\"size_capacity\":\"12HP\",\"status\":\"Not Working\",\"check_hmi_local\":1,\"check_web\":1,\"remark\":\"media test\"}', '{\"item_name\":\"Actuator bypass\",\"make_model\":\"12 make\",\"size_capacity\":\"12HP\",\"status\":\"Not Working\",\"check_hmi_local\":1,\"check_web\":1,\"remark\":\"media test\"}', '2025-11-25 09:16:12'),
(137, 53, 'Actuator bypass', 'Ashakiran Jyoti', 'Updated', '{\"item_name\":\"Actuator bypass\",\"make_model\":\"12 make\",\"size_capacity\":\"12HP\",\"status\":\"Not Working\",\"check_hmi_local\":1,\"check_web\":1,\"remark\":\"media test\"}', '{\"item_name\":\"Actuator bypass\",\"make_model\":\"12 make\",\"size_capacity\":\"12HP\",\"status\":\"Not Working\",\"check_hmi_local\":1,\"check_web\":1,\"remark\":\"media test\"}', '2025-11-25 09:52:14'),
(138, 53, 'Actuator bypass', 'rr', 'Updated', '{\"item_name\":\"Actuator bypass\",\"make_model\":\"12 make\",\"size_capacity\":\"12HP\",\"status\":\"Not Working\",\"check_hmi_local\":1,\"check_web\":1,\"remark\":\"media test\"}', '{\"item_name\":\"Actuator bypass\",\"make_model\":\"12 make\",\"size_capacity\":\"12HP\",\"status\":\"Not Working\",\"check_hmi_local\":1,\"check_web\":1,\"remark\":\"media test\"}', '2025-11-25 09:53:05');

-- --------------------------------------------------------

--
-- Table structure for table `status_history`
--

CREATE TABLE `status_history` (
  `id` int(11) NOT NULL,
  `site_id` int(11) DEFAULT NULL,
  `tubewell_id` int(11) DEFAULT NULL,
  `item_name` varchar(255) NOT NULL,
  `make_model` varchar(255) DEFAULT NULL,
  `size_capacity` varchar(100) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `check_hmi_local` tinyint(1) DEFAULT 0,
  `check_web` tinyint(1) DEFAULT 0,
  `remark` text DEFAULT NULL,
  `status_date` date NOT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `status_history`
--

INSERT INTO `status_history` (`id`, `site_id`, `tubewell_id`, `item_name`, `make_model`, `size_capacity`, `status`, `check_hmi_local`, `check_web`, `remark`, `status_date`, `created_by`, `recorded_at`, `updated_at`) VALUES
(1, 26, 54, 'Kitkat Pump (RYB)', 'MODEL MCOM 1', '12HP', 'Supplied', 1, 1, 'REMARK CHANGING FOR TESTING', '2025-11-03', 'Ashakiran Jyoti', '2025-11-03 05:30:53', '2025-11-03 05:30:53'),
(3, 26, 54, 'Actuator bypass', 'ACTUATOR BYPASS', '25HP', 'Not Supply', 1, 1, 'REMARK1', '2025-11-03', 'Ashakiran Jyoti', '2025-11-03 05:52:50', '2025-11-03 05:52:50'),
(4, 26, 54, 'Test 2', 'mcom232', '10hp', 'Working', 1, 1, 'Remark Test', '2025-11-03', 'Ashakiran Jyoti', '2025-11-03 06:13:03', '2025-11-03 06:13:03'),
(5, 26, 54, 'Testing Item', 'Testing mcom232', '14hp', 'Not Required', 1, 1, 'Remark changed make', '2025-11-03', 'Ashakiran Jyoti', '2025-11-03 06:16:57', '2025-11-03 06:16:57'),
(6, 25, 55, 'Kitkat Pump (RYB)', 'MODEL MCOM 1', '25HP', 'Supplied', 1, 0, 'REMARK CHANGING FOR TESTING', '2025-11-03', 'Ashakiran Jyoti', '2025-11-03 06:18:04', '2025-11-03 06:18:04'),
(8, 25, 55, 'Item 1`', 'make 2', 'cap 2', 'Not Supply', 1, 1, 'Remark 1', '2025-11-03', 'Ashakiran Jyoti', '2025-11-03 06:36:39', '2025-11-03 06:36:39'),
(9, 25, 55, 'Actuator bypass', 'MCOM2', '12HP', 'Not Supply', 1, 1, 'testing', '2025-11-04', 'Ashakiran Jyoti', '2025-11-04 12:41:17', '2025-11-04 12:41:17'),
(10, 26, 54, 'Kitkat Switch Board', 'check', '1hp', 'In - installation', 1, 1, 'to check view parameters is working or not', '2025-11-04', 'Ashakiran Jyoti', '2025-11-04 12:52:55', '2025-11-04 12:52:55'),
(11, 26, 54, 'new item', 'SS', 'DD', 'Not Working', 1, 1, 'TO CHECK SPARE ROW', '2025-11-04', 'Ashakiran Jyoti', '2025-11-04 12:53:25', '2025-11-04 12:53:25'),
(14, 26, 54, 'new item', 'new make', '1hp', 'Installed', 0, 1, 'to check spare is working or not properly', '2025-11-05', 'Ashakiran Jyoti', '2025-11-05 10:45:02', '2025-11-05 10:45:02'),
(15, 26, 54, 'Actuator bypass', 'ACTUATOR BYPASS', '25HP', 'Not Supply', 1, 0, 'to check master note is working or not', '2025-11-05', 'Ashakiran Jyoti', '2025-11-05 10:51:33', '2025-11-05 10:51:33'),
(16, 26, 54, 'RTU Control Panel', 'MCOM2', '26HP', 'In - installation', 1, 1, 'REMARK1', '2025-11-06', 'Ravikumar Shukla', '2025-11-06 07:59:48', '2025-11-06 07:59:48'),
(17, 28, 56, 'Kitkat Pump (RYB)', 'make 1', '12hp', 'Not Supply', 1, 1, 'remark 1 of operator 4', '2025-11-06', 'operator 4', '2025-11-06 09:21:58', '2025-11-06 09:21:58'),
(18, 28, 56, 'RTU Control Panel', 'make 2', '12hp', 'In - installation', 1, 1, 'remark 3 of operator 3', '2025-11-06', 'operator 3', '2025-11-06 09:22:50', '2025-11-06 09:22:50'),
(19, 28, 56, 'Actuator bypass', 'make 3', '12hp', 'Installed', 1, 1, 'remark 2 of operator 1', '2025-11-06', 'operator 1', '2025-11-06 09:23:45', '2025-11-06 09:23:45'),
(20, 28, 56, 'Kitkat Switch Board', 'make 4', '24hp', 'Installed', 1, 1, 'remark 4 of operator 2', '2025-11-06', 'operator 2', '2025-11-06 09:24:35', '2025-11-06 09:24:35'),
(21, 28, 56, 'item 1', 'make 5', '25hp', 'Not Working', 1, 1, 'remark 5 of Ashakiran', '2025-11-06', 'Ashakiran Jyoti', '2025-11-06 09:25:38', '2025-11-06 09:25:38'),
(22, 28, 56, 'item 2', 'make 6', '26hp', 'Not Working', 1, 1, 'remark 6 of ravikiran', '2025-11-06', 'Ravikumar Shukla', '2025-11-06 09:27:07', '2025-11-06 09:27:07'),
(23, 27, 58, 'Kitkat Pump (RYB)', 'make 1', '12hp', 'Not Required', 1, 1, 'remark 1 of USER 1', '2025-11-06', 'USER 1', '2025-11-06 11:36:41', '2025-11-06 11:36:41'),
(24, 27, 58, 'Actuator bypass', 'make 3', '12hp', 'Not Required', 1, 1, 'remark 2 of USER 1', '2025-11-06', 'USER 1', '2025-11-06 11:36:55', '2025-11-06 11:36:55'),
(25, 27, 58, 'RTU Control Panel', 'make 2', '12hp', 'Not Required', 1, 1, 'remark 3 of USER 2', '2025-11-06', 'USER 2', '2025-11-06 11:37:30', '2025-11-06 11:37:30'),
(26, 27, 58, 'Kitkat Switch Board', 'make 4', '24hp', 'Not Required', 1, 1, 'remark 4 of USER 2', '2025-11-06', 'USER 2', '2025-11-06 11:37:48', '2025-11-06 11:37:48'),
(27, 27, 58, 'ITEM 1', 'Make 5', '12hp', 'Not Working', 1, 1, 'remark 5 of ravikumar', '2025-11-06', 'Ravikumar Shukla', '2025-11-06 11:38:54', '2025-11-06 11:38:54'),
(28, 24, 53, 'Kitkat Pump (RYB)', 'make 1', '12hp', 'In - installation', 1, 1, 'remark 1 of USER 1', '2025-11-06', 'USER 1', '2025-11-06 11:58:27', '2025-11-06 11:58:27'),
(29, 24, 53, 'RTU Control Panel', 'RTU CONTROL PANEL', '15HP', 'Supplied', 0, 0, 'REMARK CHANGING FOR TESTING  USER 2', '2025-11-06', 'USER 2', '2025-11-06 11:59:21', '2025-11-06 11:59:21'),
(30, 29, 59, 'Kitkat Pump (RYB)', 'MODEL 1', '23 HP', 'Not Supply', 1, 1, 'kjasjdhajsd ahsdiashdiuasd ahdahdiuad iahdiahdiausd aihdiaushdiashd ashdiahdiausd aihdisahdh', '2025-11-07', 'USER 2', '2025-11-07 06:55:17', '2025-11-07 06:55:17'),
(31, 29, 59, 'Actuator bypass', 'MAKE 1', '21HP', 'In - installation', 1, 0, 'REMARK CHANGING FOR TESTING', '2025-11-07', 'USER 2', '2025-11-07 06:55:39', '2025-11-07 06:55:39'),
(32, 29, 59, 'RTU Control Panel', 'CONTROL', '11HP', 'Installed', 1, 1, '>REMARK CHANGING FOR TESTING', '2025-11-07', 'USER 2', '2025-11-07 06:56:05', '2025-11-07 06:56:05'),
(36, 29, 59, 'SPARE ITEM 1', 'MAKE 1', '11HP', 'Supplied', 1, 1, 'REMARK OF KAPUR SITE TW 1 FOR TESTING', '2025-11-07', 'USER 2', '2025-11-07 06:57:24', '2025-11-07 06:57:24'),
(37, 29, 59, 'Kitkat Switch Board', 'MODEM 232', '100HP', 'Installed', 1, 1, '>REMARK CHANGING FOR TESTING', '2025-11-07', 'USER 2', '2025-11-07 07:26:54', '2025-11-07 07:26:54'),
(38, 30, 61, 'Kitkat Pump (RYB)', 'MAKE 1', '12hp', 'Supplied', 1, 0, 'REMARK1', '2025-11-07', 'USER 2', '2025-11-07 09:45:54', '2025-11-07 09:45:54'),
(39, 24, 53, 'Actuator bypass', 'MODEL MCOM 1', '12HP', 'Not Supply', 1, 1, 'REMARK CHANGING FOR TESTING', '2025-11-10', 'Ashakiran Jyoti', '2025-11-10 12:34:08', '2025-11-10 12:34:08'),
(40, 24, 53, 'Kitkat Pump (RYB)', 'make 1', '12hp', 'In - installation', 2, 2, 'remark 1 of USER 1', '2025-11-11', 'Ashakiran Jyoti', '2025-11-11 05:14:51', '2025-11-11 05:14:51'),
(42, 24, 53, 'Actuator bypass', 'MODEL MCOM 1', '12HP', 'Not Supply', 0, 1, 'REMARK CHANGING FOR TESTING', '2025-11-11', 'Ashakiran Jyoti', '2025-11-11 05:15:08', '2025-11-11 05:15:08'),
(44, 24, 53, 'Kitkat Switch Board', '', '', 'Not Required', 2, 0, '', '2025-11-11', 'Ashakiran Jyoti', '2025-11-11 05:15:56', '2025-11-11 05:15:56'),
(46, 24, 53, 'item 1', 'make 1', '11hp', 'In - installation', 2, 0, 'to check hmi and web logic working or not', '2025-11-11', 'Ashakiran Jyoti', '2025-11-11 05:20:01', '2025-11-11 05:20:01'),
(47, 27, 58, 'Kitkat Pump (RYB)', 'make 1', '12hp', 'Installed', 2, 1, 'remark 1 of USER 1', '2025-11-11', 'Ashakiran Jyoti', '2025-11-11 10:16:05', '2025-11-11 10:16:05'),
(49, 24, 53, 'item 1 12', '12 make', '12 hp', 'Installed', 1, 1, 'remark', '2025-11-12', 'Ashakiran Jyoti', '2025-11-12 04:16:17', '2025-11-12 04:16:17'),
(50, 24, 53, 'sprae item', 'make spare', '12hp', 'Installed', 2, 1, 'spare remark', '2025-11-12', 'Ashakiran Jyoti', '2025-11-12 06:18:10', '2025-11-12 06:18:10'),
(54, 24, 53, 'RTU Control Panel', 'RTU CONTROL PANEL', '15HP', 'Supplied', 0, 0, 'REMARK CHANGING FOR TESTING  USER 2', '2025-11-12', 'Ashakiran Jyoti', '2025-11-12 10:05:03', '2025-11-12 10:05:03'),
(55, 24, 53, 'Kitkat Pump (RYB)', 'make 1', '12hp', 'In - installation', 0, 2, 'remark 1 of USER 1', '2025-11-12', 'Ashakiran Jyoti', '2025-11-12 12:32:34', '2025-11-12 12:32:34'),
(56, 24, 53, 'Actuator bypass', '12 make', '12HP', 'Not Working', 1, 2, 'REMARK CHANGING FOR TESTING', '2025-11-12', 'Ashakiran Jyoti', '2025-11-12 12:33:07', '2025-11-12 12:33:07'),
(57, 24, 53, 'RTU Control Panel', 'RTU CONTROL PANEL', '15HP', 'Installed', 1, 0, 'REMARK CHANGING FOR TESTING  USER 2', '2025-11-13', 'Ashakiran Jyoti', '2025-11-13 04:23:10', '2025-11-13 04:23:10'),
(58, 25, 55, 'RTU Control Panel', 'KITKAT', '32hp', 'Supplied', 1, 2, 'kjasjdhajsd ahsdiashdiuasd ahdahdiuad iahdiahdiausd aihdiaushdiashd ashdiahdiausd aihdisahdh', '2025-11-13', 'Ashakiran Jyoti', '2025-11-13 06:39:36', '2025-11-13 06:39:36'),
(61, 26, 54, 'RTU Panel', '12 make', '12hp', 'Not Supply', 0, 1, 'REMARK', '2025-11-13', 'Ashakiran Jyoti', '2025-11-13 11:52:45', '2025-11-13 11:52:45'),
(62, 25, 55, 'spare item 1', 'spare make', '11', 'Working', 1, 0, 'spare item', '2025-11-13', 'Ashakiran Jyoti', '2025-11-13 12:43:54', '2025-11-13 12:43:54'),
(63, 27, 58, 'RTU Panel', 'MODEL MCOM 1', '12HP', 'Not Working', 1, 2, 'kjasjdhajsd ahsdiashdiuasd ahdahdiuad iahdiahdiausd aihdiaushdiashd ashdiahdiausd aihdisahdh', '2025-11-14', 'Ashakiran Jyoti', '2025-11-14 04:30:55', '2025-11-14 04:30:55'),
(68, 24, 53, 'Kitkat Switch Board', '', '', 'Not Required', 0, 0, '', '2025-11-14', 'Ashakiran Jyoti', '2025-11-14 05:10:57', '2025-11-14 05:10:57'),
(71, 24, 53, 'RTU Control Panel', 'RTU CONTROL PANEL', '15HP', 'Installed', 1, 0, 'REMARK CHANGING FOR TESTING  USER 2', '2025-11-14', 'Ashakiran Jyoti', '2025-11-14 05:15:58', '2025-11-14 05:15:58'),
(74, 25, 55, 'Kitkat Pump (RYB)', 'MODEL MCOM 1', '25HP', 'Supplied', 2, 2, 'test', '2025-11-14', 'Ashakiran Jyoti', '2025-11-14 05:29:59', '2025-11-14 05:29:59'),
(82, 26, 54, 'Kitkat Switch Board', 'check', '1hp', 'In - installation', 0, 1, 'media check', '2025-11-14', 'Ashakiran Jyoti', '2025-11-14 08:59:14', '2025-11-14 08:59:14'),
(84, 26, 54, 'RTU Control Panel', 'MCOM2', '26HP', 'In - installation', 1, 0, 'REMARK1', '2025-11-14', 'Ashakiran Jyoti', '2025-11-14 08:59:29', '2025-11-14 08:59:29'),
(85, 24, 53, 'sprae item', 'make spare', '12hp', 'Installed', 2, 1, 'spare remark', '2025-11-14', 'Ashakiran Jyoti', '2025-11-14 09:45:36', '2025-11-14 09:45:36'),
(86, 30, 63, 'Kitkat Pump (RYB)', 'MODEL MCOM 1', '12HP', 'Not Supply', 1, 1, 'REMARK1', '2025-11-14', 'Ashakiran Jyoti', '2025-11-14 11:06:25', '2025-11-14 11:06:25'),
(89, 30, 63, 'tw spare item', 'make', '12 hh', 'Not Working', 1, 0, 'rrrrr', '2025-11-14', 'USER 2', '2025-11-14 11:09:26', '2025-11-14 11:09:26'),
(90, 25, 55, 'Kitkat Switch Board', 'KITKAT', '32hp', 'Not Supply', 1, 2, 'to test', '2025-11-14', 'Ashakiran Jyoti', '2025-11-14 11:38:22', '2025-11-14 11:38:22'),
(91, 24, 53, 'Actuator bypass', '12 make', '12HP', 'Not Working', 1, 0, 'test', '2025-11-14', 'Ashakiran Jyoti', '2025-11-14 12:20:32', '2025-11-14 12:20:32'),
(92, 24, 53, 'Actuator bypass', '12 make', '12HP', 'Not Working', 1, 1, 'media test', '2025-11-15', 'Ashakiran Jyoti', '2025-11-15 04:40:10', '2025-11-15 04:40:10'),
(93, 24, 53, 'Kitkat Pump (RYB)', 'make 1', '12hp', 'In - installation', 0, 0, 'remark 1 of USER 1', '2025-11-15', 'Ashakiran Jyoti', '2025-11-15 04:40:55', '2025-11-15 04:40:55'),
(94, 26, 54, 'RTU Control Panel', 'MCOM2', '26HP', 'In - installation', 1, 0, 'media test', '2025-11-15', 'Ashakiran Jyoti', '2025-11-15 04:43:07', '2025-11-15 04:43:07'),
(95, 31, 64, 'Kitkat Pump (RYB)', 'MODEL MCOM 1', '12HP', 'Not Required', 2, 0, 'REMARK1', '2025-11-15', 'Ashakiran Jyoti', '2025-11-15 11:17:53', '2025-11-15 11:17:53'),
(96, 31, 64, 'SPARE ITEM 1', 'SPARE ITEM', 'SPARE SIZE', 'Not Working', 0, 0, 'SPARE REMARK', '2025-11-15', 'Ashakiran Jyoti', '2025-11-15 11:21:46', '2025-11-15 11:21:46'),
(97, 31, 64, 'RTU Control Panel', 'MODEL MCOM 1', '12HP', 'Not Supply', 1, 1, 'REMARK1', '2025-11-15', 'Ashakiran Jyoti', '2025-11-15 11:29:59', '2025-11-15 11:29:59'),
(98, 31, 64, 'spare item 2', 'mmm', 'sss', 'Installed', 1, 2, 'spare item 2 remark', '2025-11-15', 'Ravikumar Shukla', '2025-11-15 12:29:51', '2025-11-15 12:29:51'),
(99, 31, 64, 'Kitkat Pump (RYB)', 'MODEL MCOM 1', '12HP', 'Not Required', 2, 0, 'REMARK1', '2025-11-17', 'Ashakiran Jyoti', '2025-11-17 04:14:17', '2025-11-17 04:14:17'),
(100, 31, 64, 'spareeee', 'jkjkjkjkj', 'jmjmjmjm', 'In - installation', 2, 1, 'klklklklklklklk', '2025-11-17', 'Ashakiran Jyoti', '2025-11-17 04:30:55', '2025-11-17 04:30:55'),
(101, 24, 53, 'Kitkat Pump (RYB)', 'make 1', '12hp', 'In - installation', 0, 0, 'remark 1 of USER 1', '2025-11-17', 'Ashakiran Jyoti', '2025-11-17 11:43:09', '2025-11-17 11:43:09'),
(102, 24, 53, 'Kitkat Pump (RYB)', 'make 1', '12hp', 'In - installation', 0, 0, 'remark 1 of USER 1', '2025-11-18', 'Ashakiran Jyoti', '2025-11-18 07:15:41', '2025-11-18 07:15:41'),
(103, 24, 53, 'item 1', 'make 1', '11hp', 'In - installation', 1, 1, 'to check hmi and web logic working or not', '2025-11-18', 'Ravikumar Shukla', '2025-11-18 08:56:55', '2025-11-18 08:56:55'),
(104, 24, 53, 'RTU Panel', '', '', 'Not Required', 2, 2, '', '2025-11-18', 'Ravikumar Shukla', '2025-11-18 12:08:54', '2025-11-18 12:08:54'),
(105, 25, 55, 'Kitkat Pump (RYB)', 'MODEL MCOM 1', '25HP', 'Supplied', 2, 2, 'test', '2025-11-19', 'Ravikumar Shukla', '2025-11-19 05:36:02', '2025-11-19 05:36:02'),
(106, 26, 54, 'Kitkat Pump (RYB)', 'MODEL MCOM 1', '12HP', 'Not Required', 1, 2, 'REMARK CHANGING FOR TESTING', '2025-11-19', 'Ravikumar Shukla', '2025-11-19 06:54:50', '2025-11-19 06:54:50'),
(110, 26, 54, 'hhhh', 'jjjjj', 'dgfdgd', 'Not Required', 1, 2, 'jhjgdfateyiujvcfs', '2025-11-19', 'USER 2', '2025-11-19 08:48:24', '2025-11-19 08:48:24'),
(116, 26, 54, 'Kitkat Switch Board', 'check', '1hp', 'In - installation', 2, 2, 'media check', '2025-11-19', 'USER 2', '2025-11-19 10:05:34', '2025-11-19 10:05:34'),
(117, 26, 54, 'Testing Item', 'Testing mcom232', '14hp', 'Not Required', 1, 2, 'Remark changed make', '2025-11-19', 'USER 2', '2025-11-19 10:56:30', '2025-11-19 10:56:30'),
(119, 26, 54, 'RTU Control Panel', 'MCOM2', '26HP', 'In - installation', 1, 0, 'media test', '2025-11-19', 'USER 2', '2025-11-19 12:33:06', '2025-11-19 12:33:06'),
(120, 26, 54, 'Kitkat Pump (RYB)', 'MODEL MCOM 1', '12HP', 'Not Required', 0, 0, 'REMARK CHANGING FOR TESTING', '2025-11-20', 'Ashakiran Jyoti', '2025-11-20 04:49:28', '2025-11-20 04:49:28'),
(121, 24, 53, 'Kitkat Switch Board', '', '', 'Not Required', 0, 0, 'ccccc', '2025-11-20', 'Ashakiran Jyoti', '2025-11-20 11:29:23', '2025-11-20 11:29:23'),
(122, 24, 53, 'Kitkat Pump (RYB)', 'make 1', '12hp', 'In - installation', 0, 2, 'remark 1 of USER 1', '2025-11-21', 'Ashakiran Jyoti', '2025-11-21 04:50:12', '2025-11-21 04:50:12'),
(123, 32, 67, 'Kitkat Pump (RYB)', '', '', 'Not Required', 2, 2, 'REMARK CHANGING FOR TESTING', '2025-11-21', 'Ashakiran Jyoti', '2025-11-21 12:43:21', '2025-11-21 12:43:21'),
(124, 24, 53, 'Kitkat Pump (RYB)', 'make 1', '12hp', 'In - installation', 0, 0, 'remark 1 of USER 1', '2025-11-24', 'Ashakiran Jyoti', '2025-11-24 04:30:01', '2025-11-24 04:30:01'),
(125, 24, 53, 'Actuator bypass', '12 make', '12HP', 'Not Working', 1, 1, 'media test', '2025-11-24', 'Ashakiran Jyoti', '2025-11-24 07:11:22', '2025-11-24 07:11:22'),
(133, 24, 53, 'Kitkat Pump (RYB)', 'make 1', '12hp', 'In - installation', 0, 0, 'remark 1 of USER 1', '2025-11-25', 'Ashakiran Jyoti', '2025-11-25 07:47:13', '2025-11-25 07:47:13'),
(134, 25, 55, 'Kitkat Pump (RYB)', 'MODEL MCOM 1', '25HP', 'Supplied', 2, 2, 'test', '2025-11-25', 'Ashakiran Jyoti', '2025-11-25 09:05:51', '2025-11-25 09:05:51'),
(138, 24, 53, 'Actuator bypass', '12 make', '12HP', 'Not Working', 1, 1, 'media test', '2025-11-25', 'rr', '2025-11-25 09:53:05', '2025-11-25 09:53:05');

-- --------------------------------------------------------

--
-- Table structure for table `status_locks`
--

CREATE TABLE `status_locks` (
  `id` int(11) NOT NULL,
  `tubewell_id` int(11) NOT NULL,
  `status_date` date NOT NULL,
  `locked_by` varchar(100) DEFAULT NULL,
  `locked_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tubewells`
--

CREATE TABLE `tubewells` (
  `id` int(11) NOT NULL,
  `site_id` int(11) DEFAULT NULL,
  `zone_name` varchar(100) NOT NULL,
  `tubewell_name` varchar(255) NOT NULL,
  `tw_address` text DEFAULT NULL,
  `incharge_name` varchar(255) DEFAULT NULL,
  `incharge_contact` varchar(20) DEFAULT NULL,
  `sim_no` varchar(20) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `installation_date` date DEFAULT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tubewells`
--

INSERT INTO `tubewells` (`id`, `site_id`, `zone_name`, `tubewell_name`, `tw_address`, `incharge_name`, `incharge_contact`, `sim_no`, `latitude`, `longitude`, `installation_date`, `created_by`, `created_at`) VALUES
(53, 24, 'ZONE1', 'TW1 TEST', 'jkh', 'VBN', '9898989898', '98098090', 76.00000000, 23.00000000, '2025-10-01', 'rrr', '2025-11-01 12:49:17'),
(54, 26, 'ZONE1', 'TW1 TEST', 'hjhj', 'VBN', '9898989898', '98098090', 76.00000000, 23.00000000, '2025-10-01', 'Ashakiran Jyoti', '2025-11-03 04:47:15'),
(55, 25, 'ZONE1', 'Tubewell 1', 'add', 'VVV', '9898989898', '98098090', 76.00000000, 23.00000000, '2025-10-01', 'Ashakiran Jyoti', '2025-11-03 05:07:02'),
(56, 28, 'zone 1', 'mcom 1', 'tubewell address', 'inc ', 'inc contact', '', 12.31230000, 79.23420000, '2025-11-01', 'operator 4', '2025-11-06 09:17:26'),
(57, 28, 'zone 1', 'mcom 2', 'tubewell address', 'inc ', 'inc contact', '', 12.31230000, 79.23420000, '2025-11-01', 'operator 4', '2025-11-06 09:17:42'),
(58, 27, 'RUDAULI ZONE 1', 'RUDAULI TW 1', 'RUDAULI ADDRESS', 'INC', '092131920381', '', 0.00000000, 0.00000000, '0000-00-00', 'Ravikumar Shukla', '2025-11-06 11:28:51'),
(59, 29, 'zone 1', 'katra tw 1', 'katra tw 1', 'katra tw 1 inc nm', '983242990', '983293189', 12.31230000, 89.24248000, '2025-11-01', 'Ashakiran Jyoti', '2025-11-07 06:12:13'),
(60, 29, 'zone 2', 'katra tw 2', 'katra tw 1 address', 'katra tw 2 inc nm', '9898989898', '983293189', 12.31230000, 89.24248000, '2025-11-01', 'Ashakiran Jyoti', '2025-11-07 06:12:45'),
(61, 30, 'ZONE 1', 'TW 1', 'PUNE', 'INC', '8327482394', '983298913', 23.45545000, 89.24248000, '2025-11-01', 'USER 2', '2025-11-07 09:43:46'),
(62, 30, 'ZONE 1', 'TW 2', 'PUNE', 'INC', '8327482394', '983298913', 23.45545000, 89.24248000, '2025-11-01', 'USER 2', '2025-11-07 09:44:05'),
(63, 30, 'zone 1', 'TW 1', 'kjaskdjkas', 'INC', '98876543212', '9189983173', 23.34234000, 79.23420000, '2025-11-13', 'Ashakiran Jyoti', '2025-11-13 05:12:42'),
(64, 31, 'zone 2', 'Chandarpur OHT Pump No.1', 'bidhuna up', 'inc', '9898989898', '923482034', 23.45545000, 90.32440000, '2025-11-01', 'Ashakiran Jyoti', '2025-11-15 11:16:35'),
(65, 31, 'zone 2', 'Chandarpur Pump No.2', 'Bidhuna up', 'inc', '9898989898', '923482034', 23.45545000, 90.32440000, '2025-11-01', 'Ashakiran Jyoti', '2025-11-15 11:16:59'),
(66, 31, 'zone 2', 'Chandarpur Pump No.3', 'bidhuna up', 'inc', '9898989898', '923482034', 23.45545000, 90.32440000, '2025-11-01', 'Ashakiran Jyoti', '2025-11-15 11:17:14'),
(67, 32, 'ZONE1', 'TW 1', 'pune up', 'RAHMANIA INC', '9898989898', '28379287247', 23.45545000, 89.24248000, '2025-11-19', 'Ashakiran Jyoti', '2025-11-20 05:57:20'),
(68, 32, 'ZONE1', 'TW 2', 'tw address', 'RAHMANIA INC', '9898989898', '28379287247', 23.45545000, 89.24248000, '2025-11-20', 'Ashakiran Jyoti', '2025-11-20 07:05:26');

-- --------------------------------------------------------

--
-- Table structure for table `tubewell_master_media`
--

CREATE TABLE `tubewell_master_media` (
  `id` int(11) NOT NULL,
  `tubewell_id` int(11) NOT NULL,
  `status_date` date NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_type` enum('image','video') NOT NULL,
  `uploaded_by` varchar(100) DEFAULT NULL,
  `uploaded_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tubewell_master_media`
--

INSERT INTO `tubewell_master_media` (`id`, `tubewell_id`, `status_date`, `file_path`, `file_type`, `uploaded_by`, `uploaded_at`) VALUES
(1, 53, '2025-11-14', 'uploads/master_note/images/69171e82b0c5c-WhatsApp_Image_2025-11-14_at_1.19.52_PM.jpeg', 'image', 'Ashakiran Jyoti', '2025-11-14 17:50:18'),
(2, 53, '2025-11-14', 'uploads/master_note/images/69171e82bc4c4-WhatsApp_Image_2025-11-14_at_12.31.18_PM.jpeg', 'image', 'Ashakiran Jyoti', '2025-11-14 17:50:18'),
(3, 53, '2025-11-14', 'uploads/master_note/images/69171e9c84513-WhatsApp_Image_2025-11-14_at_12.31.18_PM.jpeg', 'image', 'Ashakiran Jyoti', '2025-11-14 17:50:44'),
(4, 53, '2025-11-14', 'uploads/master_note/images/69171eb6e5764-WhatsApp_Image_2025-11-14_at_1.25.45_PM.jpeg', 'image', 'Ashakiran Jyoti', '2025-11-14 17:51:10'),
(5, 53, '2025-11-14', 'uploads/master_note/images/69172088b26f4-WhatsApp_Image_2025-11-14_at_1.19.52_PM.jpeg', 'image', 'Ashakiran Jyoti', '2025-11-14 17:58:56'),
(6, 53, '2025-11-14', 'uploads/master_note/images/6917209da12c6-WhatsApp_Image_2025-11-14_at_1.19.52_PM.jpeg', 'image', 'Ashakiran Jyoti', '2025-11-14 17:59:17'),
(7, 53, '2025-11-14', 'uploads/master_note/videos/691722431ab86-WhatsApp_Video_2025-11-12_at_1.56.20_PM.mp4', 'video', 'Ashakiran Jyoti', '2025-11-14 18:06:19'),
(8, 55, '2025-11-14', 'uploads/master_note/images/691725aa33c42-WhatsApp_Image_2025-11-10_at_2.11.03_PM.jpeg', 'image', 'Ashakiran Jyoti', '2025-11-14 18:20:50'),
(9, 55, '2025-11-14', 'uploads/master_note/images/6917260ec622b-WhatsApp_Image_2025-11-14_at_10.34.03_AM.jpeg', 'image', 'Ashakiran Jyoti', '2025-11-14 18:22:30'),
(10, 55, '2025-11-15', 'uploads/master_note/images/69180057e7c89-hh.jpg', 'image', 'Ashakiran Jyoti', '2025-11-15 09:53:51'),
(12, 53, '2025-11-15', 'uploads/master_note/images/691818286e9c8-IOT_1.JPEG', 'image', 'Ashakiran Jyoti', '2025-11-15 11:35:28'),
(13, 53, '2025-11-15', 'uploads/master_note/videos/691818714d04a-WhatsApp_Video_2025-11-12_at_1.51.39_PM.mp4', 'video', 'Ashakiran Jyoti', '2025-11-15 11:36:41'),
(14, 53, '2025-11-15', 'uploads/master_note/videos/69181871525f6-WhatsApp_Video_2025-11-12_at_1.56.20_PM.mp4', 'video', 'Ashakiran Jyoti', '2025-11-15 11:36:41'),
(15, 64, '2025-11-15', 'uploads/master_note/images/691861a48ffe2-WhatsApp_Image_2025-11-14_at_11.02.14_AM.jpeg', 'image', 'Ashakiran Jyoti', '2025-11-15 16:49:00'),
(16, 64, '2025-11-15', 'uploads/master_note/images/691861a493c20-WhatsApp_Image_2025-11-14_at_11.32.55_AM.jpeg', 'image', 'Ashakiran Jyoti', '2025-11-15 16:49:00'),
(17, 64, '2025-11-17', 'uploads/master_note/images/691aa0f5d28a0-am.jpeg', 'image', 'Ashakiran Jyoti', '2025-11-17 09:43:41'),
(18, 64, '2025-11-17', 'uploads/master_note/images/691aa0f5d9eec-WhatsApp_Image_2025-08-05_at_10.55.43_AM__3_.jpeg', 'image', 'Ashakiran Jyoti', '2025-11-17 09:43:41'),
(19, 55, '2025-11-17', 'uploads/master_note/images/691ab58fe5120-Screenshot_19-9-2025_144145_-removebg-preview.png', 'image', 'Ashakiran Jyoti', '2025-11-17 11:11:35'),
(20, 54, '2025-11-17', 'uploads/master_note/images/691abef701958-jal_urban.png', 'image', 'Ashakiran Jyoti', '2025-11-17 11:51:43'),
(21, 61, '2025-11-17', 'uploads/master_note/videos/691ac28c35144-WhatsApp_Video_2025-11-12_at_1.51.39_PM.mp4', 'video', 'Ashakiran Jyoti', '2025-11-17 12:07:00'),
(22, 53, '2025-11-17', 'uploads/master_note/images/691af1e86c5f9-WhatsApp_Image_2025-11-14_at_1.25.45_PM.jpeg', 'image', 'Ashakiran Jyoti', '2025-11-17 15:29:04'),
(23, 53, '2025-11-17', 'uploads/master_note/images/691af1f45ae0b-WhatsApp_Image_2025-11-14_at_11.02.14_AM.jpeg', 'image', 'Ashakiran Jyoti', '2025-11-17 15:29:16'),
(24, 53, '2025-11-17', 'uploads/master_note/images/691af1f45e5f4-WhatsApp_Image_2025-11-14_at_11.32.55_AM.jpeg', 'image', 'Ashakiran Jyoti', '2025-11-17 15:29:16'),
(25, 53, '2025-11-18', 'uploads/master_note/images/691c354c2b2ef-WhatsApp_Image_2025-11-14_at_10.34.03_AM.jpeg', 'image', 'Ravikumar Shukla', '2025-11-18 14:28:52'),
(26, 54, '2025-11-19', 'uploads/master_note/images/691db71460440-WhatsApp_Image_2025-11-14_at_11.32.55_AM.jpeg', 'image', 'USER 2', '2025-11-19 17:54:52'),
(27, 53, '2025-11-20', 'uploads/master_note/images/691efe23443df-WhatsApp_Image_2025-11-14_at_1.25.45_PM.jpeg', 'image', 'Ashakiran Jyoti', '2025-11-20 17:10:19'),
(29, 53, '2025-11-21', 'uploads/master_note/images/691fef40ed94b-Screenshot_20-11-2025_174720_103.97.105.200.jpeg', 'image', 'Ashakiran Jyoti', '2025-11-21 10:19:04');

-- --------------------------------------------------------

--
-- Table structure for table `tubewell_master_notes`
--

CREATE TABLE `tubewell_master_notes` (
  `id` int(11) NOT NULL,
  `tubewell_id` int(11) NOT NULL,
  `note` text DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT NULL,
  `updated_at` datetime NOT NULL,
  `status_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tubewell_master_notes`
--

INSERT INTO `tubewell_master_notes` (`id`, `tubewell_id`, `note`, `updated_by`, `updated_at`, `status_date`) VALUES
(1, 55, 'i am changing to ckeck Yahaan Pe Pump Problem thi abhi o solve ho gayi hai', 'Ashakiran Jyoti', '2025-11-11 15:15:51', '2025-11-11'),
(3, 54, 'master note of 10-11-2025 jhuy jiyu kuiy jyesru yy8bretred kjs ksaid kaS KAsjwiq kasdjo kalowi kalsdjoiw kadasd ndjvsdp ksjiw jsij kadhi jhuy jiyu kuiy jyesru yy8bretred kjs ksaid kaS KAsjwiq kasdjo kalowi kalsdjoiw kadasd ndjvsdp ksjiw jsij kadhi jhuy jiyu kuiy jyesru yy8bretred kjs ksaid kaS KAsjwiq kasdjo kalowi kalsdjoiw kadasd ndjvsdp ksjiw jsij kadhi', 'Ashakiran Jyoti', '2025-11-10 18:10:42', '2025-11-05'),
(8, 56, 'master note site - mcom zone 1 tw- mcom 1', 'operator 4', '2025-11-06 14:51:15', '2025-11-06'),
(9, 58, 'MASTER NOTE OF RUDAULI', 'Ravikumar Shukla', '2025-11-06 17:06:00', '2025-11-06'),
(10, 53, 'master note of 11-11-2025 14:58', 'Ashakiran Jyoti', '2025-11-11 14:58:31', '2025-11-11'),
(12, 59, 'MASTER NOTE OF KANPUR SITE TW 1', 'USER 2', '2025-11-07 12:24:51', '2025-11-07'),
(13, 61, 'MASTER NOTE', 'USER 2', '2025-11-07 15:15:01', '2025-11-07'),
(26, 59, 'CHANGIN TO CHECK 7 NOV NEW 11 NOV MASTER NOTE OF KANPUR SITE TW 1', 'Ashakiran Jyoti', '2025-11-11 15:24:54', '2025-11-11'),
(27, 58, 'MASTER NOTE OF RUDAULI 11-11-2025 15:45', 'Ashakiran Jyoti', '2025-11-11 15:45:28', '2025-11-11'),
(28, 53, 'master note of 12-11-2025 09:45', 'Ashakiran Jyoti', '2025-11-12 09:45:10', '2025-11-12'),
(29, 53, 'master note of 13-11-2025 09:52', 'Ashakiran Jyoti', '2025-11-13 09:52:39', '2025-11-13'),
(30, 55, '13-11-2025 12:08 Yahaan Pe Pump Problem thi abhi o solve ho gayi hai', 'Ashakiran Jyoti', '2025-11-13 12:09:02', '2025-11-13'),
(31, 54, 'jk', 'Ashakiran Jyoti', '2025-11-13 17:20:07', '2025-11-13'),
(32, 58, 'MASTER NOTE OF RUDAULI 14-11-2025 10:00', 'Ashakiran Jyoti', '2025-11-14 10:00:13', '2025-11-14'),
(33, 55, '14-11-2025 10:59 master note of pachperwa tubewell 1', 'Ashakiran Jyoti', '2025-11-14 18:22:12', '2025-11-14'),
(34, 63, 'master note of ravi tw 1 14-11-25 16:35', 'Ashakiran Jyoti', '2025-11-14 16:35:49', '2025-11-14'),
(36, 53, 'master note of 13-11-2025 09:52', 'Ashakiran Jyoti', '2025-11-14 18:12:26', '2025-11-14'),
(39, 53, 'master note of 14-11-2025 11:34', 'Ashakiran Jyoti', '2025-11-15 11:35:05', '2025-11-15'),
(41, 64, 'Bidhuna site - Chandarpur OHT Pump No.1 tubewell 15-11-25 16:48', 'Ashakiran Jyoti', '2025-11-15 16:48:47', '2025-11-15'),
(42, 64, 'Bidhuna site - Chandarpur OHT Pump No.1 tubewell 17-11-25 09:43', 'Ashakiran Jyoti', '2025-11-17 09:43:09', '2025-11-17'),
(43, 55, '17-11-2025 11:11 master note of pachperwa tubewell 1', 'Ashakiran Jyoti', '2025-11-17 11:11:10', '2025-11-17'),
(44, 54, 'master note of 17-11-2025 11:51 Aman Engineering', 'Ashakiran Jyoti', '2025-11-17 11:51:31', '2025-11-17'),
(45, 61, 'MASTER NOTE of tw1 17-11-2025 12:06', 'Ashakiran Jyoti', '2025-11-17 12:06:49', '2025-11-17'),
(46, 53, 'master note of 18-11-2025 14:28', 'Ravikumar Shukla', '2025-11-18 14:28:42', '2025-11-18'),
(47, 55, '17-11-2025 11:11 master note of pachperwa tubewell 1', 'Ravikumar Shukla', '2025-11-19 11:04:55', '2025-11-19'),
(48, 54, 'yyymaster note of 19-11-2025 16:54 Aman Engg', 'USER 2', '2025-11-19 18:14:53', '2025-11-19'),
(56, 54, 'mmm note 20-11-25 10:53', 'Ashakiran Jyoti', '2025-11-20 10:53:17', '2025-11-20'),
(57, 53, 'master note of 20-11-2025 17:22', 'Ashakiran Jyoti', '2025-11-20 17:23:13', '2025-11-20'),
(60, 53, 'master note of 21-11-2025 10:18', 'Ashakiran Jyoti', '2025-11-21 10:22:10', '2025-11-21'),
(62, 53, 'master note of 24-11-2025 10:02', 'Ashakiran Jyoti', '2025-11-24 12:42:26', '2025-11-24'),
(64, 53, 'master note of 25-11-2025 10:02', 'Ashakiran Jyoti', '2025-11-25 13:15:56', '2025-11-25');

-- --------------------------------------------------------

--
-- Table structure for table `tubewell_master_note_contributors`
--

CREATE TABLE `tubewell_master_note_contributors` (
  `id` int(11) NOT NULL,
  `tubewell_id` int(11) NOT NULL,
  `status_date` date NOT NULL,
  `contributor_name` varchar(100) NOT NULL,
  `added_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tubewell_master_note_contributors`
--

INSERT INTO `tubewell_master_note_contributors` (`id`, `tubewell_id`, `status_date`, `contributor_name`, `added_at`) VALUES
(1, 55, '2025-11-19', 'Mohan', '2025-11-19 11:04:55'),
(13, 54, '2025-11-19', 'operator 4', '2025-11-19 18:02:33'),
(15, 54, '2025-11-19', 'Ravikumar Shukla', '2025-11-19 18:14:13'),
(16, 54, '2025-11-19', 'Ashakiran Jyoti', '2025-11-19 18:14:53'),
(17, 54, '2025-11-19', 'operator 1', '2025-11-19 18:14:53'),
(18, 54, '2025-11-19', 'operator 2', '2025-11-19 18:14:53'),
(19, 54, '2025-11-19', 'operator 3', '2025-11-19 18:14:53'),
(22, 54, '2025-11-19', 'rr', '2025-11-19 18:14:53'),
(23, 54, '2025-11-19', 'USER 1', '2025-11-19 18:14:53'),
(24, 54, '2025-11-20', 'operator 3', '2025-11-20 10:53:17'),
(25, 54, '2025-11-20', 'operator 4', '2025-11-20 10:53:17'),
(26, 53, '2025-11-20', 'operator 1', '2025-11-20 17:09:48'),
(27, 53, '2025-11-20', 'operator 2', '2025-11-20 17:09:48'),
(32, 53, '2025-11-21', 'operator 1', '2025-11-21 10:19:04'),
(34, 53, '2025-11-21', 'Ravikumar Shukla', '2025-11-21 10:22:10'),
(35, 53, '2025-11-21', 'rr', '2025-11-21 10:22:10'),
(36, 53, '2025-11-24', 'USER 1', '2025-11-24 10:02:28'),
(38, 53, '2025-11-24', 'USER 2', '2025-11-24 12:42:26'),
(44, 53, '2025-11-25', 'operator 3', '2025-11-25 12:50:22'),
(47, 53, '2025-11-25', 'operator 4', '2025-11-25 13:15:56');

-- --------------------------------------------------------

--
-- Table structure for table `tubewell_media`
--

CREATE TABLE `tubewell_media` (
  `id` int(11) NOT NULL,
  `tubewell_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_type` enum('image','video','document') NOT NULL,
  `uploaded_by` varchar(100) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tubewell_media`
--

INSERT INTO `tubewell_media` (`id`, `tubewell_id`, `file_name`, `file_path`, `file_type`, `uploaded_by`, `uploaded_at`) VALUES
(1, 67, 'WhatsApp Image 2025-11-14 at 9.03.48 AM.jpeg', 'uploads/tubewells/691eadc058816_TW_1.jpeg', 'image', 'Ashakiran Jyoti', '2025-11-20 05:57:20'),
(2, 67, 'WhatsApp Image 2025-11-14 at 10.34.03 AM.jpeg', 'uploads/tubewells/691eadc058da4_TW_1.jpeg', 'image', 'Ashakiran Jyoti', '2025-11-20 05:57:20'),
(3, 67, 'WhatsApp Image 2025-11-14 at 11.02.14 AM.jpeg', 'uploads/tubewells/691eadc058f96_TW_1.jpeg', 'image', 'Ashakiran Jyoti', '2025-11-20 05:57:20'),
(5, 67, 'jj.jpg', 'uploads/tubewells/691ebaae067af_TW_1.jpg', 'image', 'Ashakiran Jyoti', '2025-11-20 06:52:30'),
(6, 68, 'ChatGPT Image Nov 17, 2025, 06_40_58 PM.png', 'uploads/tubewells/691ebdb66a35f_TW_2.png', 'image', 'Ashakiran Jyoti', '2025-11-20 07:05:26'),
(7, 68, 'WhatsApp Image 2025-10-30 at 11.06.48 AM.jpeg', 'uploads/tubewells/691ebdcf77446_TW_2.jpeg', 'image', 'Ashakiran Jyoti', '2025-11-20 07:05:51'),
(8, 66, 'WhatsApp Video 2025-11-12 at 1.56.20 PM.mp4', 'uploads/tubewells/691ec5e8b15db_Chandarpur_Pump_No_3.mp4', 'video', 'Ashakiran Jyoti', '2025-11-20 07:40:24'),
(9, 66, 'WhatsApp Image 2025-11-13 at 10.52.31 AM.jpeg', 'uploads/tubewells/691ec5fe0a5d8_Chandarpur_Pump_No_3.jpeg', 'image', 'Ashakiran Jyoti', '2025-11-20 07:40:46'),
(11, 55, 'WhatsApp Video 2025-11-24 at 12.29.11 PM.mp4', 'uploads/tubewells/6926a0562a7a9_Tubewell_1.mp4', 'video', 'Ashakiran Jyoti', '2025-11-26 06:38:14');

-- --------------------------------------------------------

--
-- Table structure for table `updates`
--

CREATE TABLE `updates` (
  `id` int(11) NOT NULL,
  `entity_type` varchar(50) NOT NULL,
  `entity_id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `status_date` date NOT NULL,
  `updated_by` varchar(100) NOT NULL,
  `updated_at` datetime NOT NULL,
  `change_summary` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `updates`
--

INSERT INTO `updates` (`id`, `entity_type`, `entity_id`, `item_name`, `status_date`, `updated_by`, `updated_at`, `change_summary`) VALUES
(1, 'tubewell', 53, 'Kitkat Pump (RYB)', '2025-11-18', 'Ashakiran Jyoti', '2025-11-18 12:45:42', NULL),
(2, 'tubewell', 53, 'item 1', '2025-11-18', 'Ravikumar Shukla', '2025-11-18 14:26:55', NULL),
(3, 'lcs', 3, 'table 1', '2025-11-18', 'Ravikumar Shukla', '2025-11-18 17:35:41', NULL),
(4, 'lcs', 3, 'lcs item 1', '2025-11-18', 'Ravikumar Shukla', '2025-11-18 17:36:30', NULL),
(5, 'lcs', 3, 'lcs item 2', '2025-11-18', 'Ravikumar Shukla', '2025-11-18 17:37:55', NULL),
(6, 'tubewell', 53, 'RTU Panel', '2025-11-18', 'Ravikumar Shukla', '2025-11-18 17:38:54', NULL),
(7, 'lcs', 3, 'lcs item 1', '2025-11-18', 'Ravikumar Shukla', '2025-11-18 17:48:48', NULL),
(8, 'lcs', 3, 'lcs item 1', '2025-11-18', 'Ravikumar Shukla', '2025-11-18 17:49:37', NULL),
(9, 'lcs', 3, 'pach spare item', '2025-11-18', 'Ravikumar Shukla', '2025-11-18 18:16:37', NULL),
(10, 'tubewell', 55, 'Kitkat Pump (RYB)', '2025-11-19', 'Ravikumar Shukla', '2025-11-19 11:06:02', NULL),
(11, 'tubewell', 54, 'Kitkat Pump (RYB)', '2025-11-19', 'Ravikumar Shukla', '2025-11-19 12:24:50', NULL),
(12, 'lcs', 2, 'lcs item 1', '2025-11-19', 'Ravikumar Shukla', '2025-11-19 12:47:53', NULL),
(13, 'lcs', 2, 'lcs item 1', '2025-11-19', 'Ravikumar Shukla', '2025-11-19 12:48:01', NULL),
(14, 'tubewell', 54, 'hhhh', '2025-11-19', 'Ravikumar Shukla', '2025-11-19 13:21:55', NULL),
(15, 'tubewell', 54, 'hhhh', '2025-11-19', 'Ravikumar Shukla', '2025-11-19 13:22:08', NULL),
(16, 'tubewell', 54, 'RTU Control Panel', '2025-11-19', 'USER 2', '2025-11-19 14:14:18', NULL),
(17, 'tubewell', 54, 'hhhh', '2025-11-19', 'USER 2', '2025-11-19 14:18:24', NULL),
(18, 'tubewell', 54, 'Kitkat Switch Board', '2025-11-19', 'USER 2', '2025-11-19 14:32:51', NULL),
(19, 'lcs', 2, 'lcs item 2', '2025-11-19', 'USER 2', '2025-11-19 14:34:06', NULL),
(20, 'lcs', 2, 'lcs item 2', '2025-11-19', 'USER 2', '2025-11-19 14:34:12', NULL),
(21, 'tubewell', 54, 'Kitkat Switch Board', '2025-11-19', 'USER 2', '2025-11-19 14:57:32', NULL),
(22, 'tubewell', 54, 'Kitkat Switch Board', '2025-11-19', 'USER 2', '2025-11-19 14:58:04', NULL),
(23, 'lcs', 2, 'lcs item 2', '2025-11-19', 'USER 2', '2025-11-19 14:58:52', NULL),
(24, 'lcs', 2, 'lcs item 2', '2025-11-19', 'USER 2', '2025-11-19 14:58:57', NULL),
(25, 'lcs', 2, 'table 1', '2025-11-19', 'USER 2', '2025-11-19 14:59:35', NULL),
(26, 'lcs', 2, 'lcs item 3', '2025-11-19', 'USER 2', '2025-11-19 14:59:57', NULL),
(27, 'tubewell', 54, 'RTU Control Panel', '2025-11-19', 'USER 2', '2025-11-19 15:34:32', NULL),
(28, 'tubewell', 54, 'Kitkat Switch Board', '2025-11-19', 'USER 2', '2025-11-19 15:34:49', NULL),
(29, 'tubewell', 54, 'Kitkat Switch Board', '2025-11-19', 'USER 2', '2025-11-19 15:35:34', NULL),
(30, 'lcs', 2, 'lcs item 11', '2025-11-19', 'USER 2', '2025-11-19 15:40:47', NULL),
(31, 'lcs', 2, 'lcs spare item', '2025-11-19', 'USER 2', '2025-11-19 15:44:04', NULL),
(32, 'lcs', 2, 'lcs spare item', '2025-11-19', 'USER 2', '2025-11-19 15:44:25', NULL),
(33, 'lcs', 2, 'lcs spare item', '2025-11-19', 'USER 2', '2025-11-19 15:44:29', NULL),
(34, 'lcs', 2, 'lcs spare item', '2025-11-19', 'USER 2', '2025-11-19 15:44:33', NULL),
(35, 'lcs', 2, 'lcs spare item', '2025-11-19', 'USER 2', '2025-11-19 15:46:15', NULL),
(36, 'lcs', 2, 'lcs spare item', '2025-11-19', 'USER 2', '2025-11-19 15:46:24', NULL),
(37, 'lcs', 2, 'lcs item 3', '2025-11-19', 'USER 2', '2025-11-19 15:49:54', NULL),
(38, 'lcs', 2, 'lcs spare item', '2025-11-19', 'USER 2', '2025-11-19 16:02:56', NULL),
(39, 'lcs', 2, 'lcs item 11', '2025-11-19', 'USER 2', '2025-11-19 16:21:12', NULL),
(40, 'lcs', 2, 'lcs item 2', '2025-11-19', 'USER 2', '2025-11-19 16:21:29', NULL),
(41, 'lcs', 2, 'lcs spare item', '2025-11-19', 'USER 2', '2025-11-19 16:21:46', NULL),
(42, 'lcs', 5, 'table 1', '2025-11-19', 'USER 2', '2025-11-19 16:22:27', NULL),
(43, 'lcs', 5, 'spare item of rudauli', '2025-11-19', 'USER 2', '2025-11-19 16:22:46', NULL),
(44, 'lcs', 5, 'lcs item 2', '2025-11-19', 'USER 2', '2025-11-19 16:25:41', NULL),
(45, 'tubewell', 54, 'Testing Item', '2025-11-19', 'USER 2', '2025-11-19 16:26:30', NULL),
(46, 'tubewell', 54, 'RTU Control Panel', '2025-11-19', 'USER 2', '2025-11-19 18:02:56', NULL),
(47, 'tubewell', 54, 'RTU Control Panel', '2025-11-19', 'USER 2', '2025-11-19 18:03:06', NULL),
(48, 'tubewell', 54, 'Kitkat Pump (RYB)', '2025-11-20', 'Ashakiran Jyoti', '2025-11-20 10:19:28', NULL),
(49, 'tubewell', 53, 'Kitkat Switch Board', '2025-11-20', 'Ashakiran Jyoti', '2025-11-20 16:59:23', NULL),
(50, 'lcs', 1, 'lcs item 2', '2025-11-20', 'Ashakiran Jyoti', '2025-11-20 18:23:20', NULL),
(51, 'tubewell', 53, 'Kitkat Pump (RYB)', '2025-11-21', 'Ashakiran Jyoti', '2025-11-21 10:20:12', NULL),
(52, 'lcs', 1, 'lcs item 1', '2025-11-21', 'Ashakiran Jyoti', '2025-11-21 10:26:57', NULL),
(53, 'lcs', 2, 'lcs item 2', '2025-11-21', 'Ashakiran Jyoti', '2025-11-21 14:44:07', NULL),
(54, 'lcs', 2, 'lcs item 11', '2025-11-21', 'Ashakiran Jyoti', '2025-11-21 14:44:37', NULL),
(55, 'lcs', 2, 'table 1', '2025-11-21', 'Ashakiran Jyoti', '2025-11-21 14:52:31', NULL),
(56, 'tubewell', 67, 'Kitkat Pump (RYB)', '2025-11-21', 'Ashakiran Jyoti', '2025-11-21 18:13:21', NULL),
(57, 'tubewell', 53, 'Kitkat Pump (RYB)', '2025-11-24', 'Ashakiran Jyoti', '2025-11-24 10:00:02', NULL),
(58, 'lcs', 1, 'lcs item 2', '2025-11-24', 'Ashakiran Jyoti', '2025-11-24 10:32:37', NULL),
(59, 'tubewell', 53, 'Actuator bypass', '2025-11-24', 'Ashakiran Jyoti', '2025-11-24 12:41:22', NULL),
(60, 'tubewell', 55, 'Kitkat Pump (RYB)', '2025-11-25', 'Ashakiran Jyoti', '2025-11-25 10:09:54', NULL),
(61, 'tubewell', 55, 'Kitkat Pump (RYB)', '2025-11-25', 'Ashakiran Jyoti', '2025-11-25 10:10:18', NULL),
(62, 'tubewell', 55, 'Kitkat Pump (RYB)', '2025-11-25', 'Ravikumar Shukla', '2025-11-25 11:25:26', NULL),
(63, 'tubewell', 53, 'Kitkat Pump (RYB)', '2025-11-25', 'Ravikumar Shukla', '2025-11-25 12:01:33', NULL),
(64, 'tubewell', 53, 'Kitkat Pump (RYB)', '2025-11-25', 'Ravikumar Shukla', '2025-11-25 12:01:41', NULL),
(65, 'tubewell', 53, 'Kitkat Pump (RYB)', '2025-11-25', 'Ravikumar Shukla', '2025-11-25 12:21:06', NULL),
(66, 'tubewell', 53, 'Kitkat Pump (RYB)', '2025-11-25', 'Ashakiran Jyoti', '2025-11-25 13:16:40', NULL),
(67, 'tubewell', 53, 'Kitkat Pump (RYB)', '2025-11-25', 'Ashakiran Jyoti', '2025-11-25 13:17:13', NULL),
(68, 'tubewell', 55, 'Kitkat Pump (RYB)', '2025-11-25', 'Ashakiran Jyoti', '2025-11-25 14:35:51', NULL),
(69, 'tubewell', 53, 'Actuator bypass', '2025-11-25', 'Ashakiran Jyoti', '2025-11-25 14:45:31', NULL),
(70, 'tubewell', 53, 'Actuator bypass', '2025-11-25', 'Ashakiran Jyoti', '2025-11-25 14:46:12', NULL),
(71, 'tubewell', 53, 'Actuator bypass', '2025-11-25', 'Ashakiran Jyoti', '2025-11-25 15:22:14', NULL),
(72, 'tubewell', 53, 'Actuator bypass', '2025-11-25', 'rr', '2025-11-25 15:23:05', NULL),
(73, 'lcs', 3, 'table 1', '2025-11-26', 'Ashakiran Jyoti', '2025-11-26 16:55:11', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `update_contributors`
--

CREATE TABLE `update_contributors` (
  `id` int(11) NOT NULL,
  `update_id` int(11) NOT NULL,
  `contributor_name` varchar(100) NOT NULL,
  `role` varchar(50) DEFAULT NULL,
  `added_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `update_contributors`
--

INSERT INTO `update_contributors` (`id`, `update_id`, `contributor_name`, `role`, `added_at`) VALUES
(1, 1, 'John', NULL, '2025-11-18 12:45:42'),
(2, 1, 'Raj', NULL, '2025-11-18 12:45:42'),
(3, 2, 'smith', NULL, '2025-11-18 14:26:55'),
(4, 2, 'Aman', NULL, '2025-11-18 14:26:55'),
(5, 4, 'Kumar', NULL, '2025-11-18 17:36:30'),
(6, 4, 'John', NULL, '2025-11-18 17:36:30'),
(7, 5, 'Rahul', NULL, '2025-11-18 17:37:55'),
(8, 5, 'Sagar', NULL, '2025-11-18 17:37:55'),
(9, 6, 'Pruthvi', NULL, '2025-11-18 17:38:54'),
(10, 7, 'Prathmesh', NULL, '2025-11-18 17:48:48'),
(11, 9, 'Riya', NULL, '2025-11-18 18:16:37'),
(12, 9, 'karuna', NULL, '2025-11-18 18:16:37'),
(13, 10, 'Raja', NULL, '2025-11-19 11:06:03'),
(14, 11, 'Ashakiran Jyoti', NULL, '2025-11-19 12:24:50'),
(15, 11, 'operator 1', NULL, '2025-11-19 12:24:50'),
(16, 11, 'operator 2', NULL, '2025-11-19 12:24:50'),
(17, 12, 'operator 2', NULL, '2025-11-19 12:47:53'),
(18, 13, 'operator 2', NULL, '2025-11-19 12:48:01'),
(19, 15, 'Ashakiran Jyoti', NULL, '2025-11-19 13:22:08'),
(20, 16, 'operator 1', NULL, '2025-11-19 14:14:18'),
(21, 17, 'operator 4', NULL, '2025-11-19 14:18:24'),
(22, 18, 'Ashakiran Jyoti', NULL, '2025-11-19 14:32:51'),
(23, 18, 'operator 1', NULL, '2025-11-19 14:32:51'),
(24, 19, 'operator 1', NULL, '2025-11-19 14:34:06'),
(25, 19, 'operator 2', NULL, '2025-11-19 14:34:06'),
(26, 20, 'operator 1', NULL, '2025-11-19 14:34:12'),
(27, 20, 'operator 2', NULL, '2025-11-19 14:34:12'),
(28, 21, 'Ashakiran Jyoti', NULL, '2025-11-19 14:57:32'),
(29, 23, 'operator 1', NULL, '2025-11-19 14:58:52'),
(30, 24, 'operator 1', NULL, '2025-11-19 14:58:57'),
(31, 25, 'operator 4', NULL, '2025-11-19 14:59:35'),
(32, 26, 'Ashakiran Jyoti', NULL, '2025-11-19 14:59:57'),
(33, 28, 'Ashakiran Jyoti', NULL, '2025-11-19 15:34:49'),
(34, 28, 'operator 1', NULL, '2025-11-19 15:34:49'),
(35, 28, 'operator 2', NULL, '2025-11-19 15:34:49'),
(36, 28, 'operator 3', NULL, '2025-11-19 15:34:49'),
(37, 29, 'Ashakiran Jyoti', NULL, '2025-11-19 15:35:34'),
(38, 29, 'operator 1', NULL, '2025-11-19 15:35:34'),
(39, 30, 'Ashakiran Jyoti', NULL, '2025-11-19 15:40:47'),
(40, 31, 'operator 1', NULL, '2025-11-19 15:44:04'),
(41, 32, 'operator 1', NULL, '2025-11-19 15:44:25'),
(42, 33, 'operator 1', NULL, '2025-11-19 15:44:29'),
(43, 34, 'operator 1', NULL, '2025-11-19 15:44:33'),
(44, 35, 'operator 1', NULL, '2025-11-19 15:46:15'),
(45, 36, 'operator 1', NULL, '2025-11-19 15:46:24'),
(46, 37, 'operator 1', NULL, '2025-11-19 15:49:54'),
(47, 37, 'operator 2', NULL, '2025-11-19 15:49:54'),
(48, 37, 'operator 3', NULL, '2025-11-19 15:49:54'),
(49, 38, 'Ashakiran Jyoti', NULL, '2025-11-19 16:02:56'),
(50, 38, 'operator 1', NULL, '2025-11-19 16:02:56'),
(51, 38, 'operator 2', NULL, '2025-11-19 16:02:56'),
(52, 39, 'Ashakiran Jyoti', NULL, '2025-11-19 16:21:12'),
(53, 39, 'operator 1', NULL, '2025-11-19 16:21:12'),
(54, 40, 'Ashakiran Jyoti', NULL, '2025-11-19 16:21:29'),
(55, 40, 'operator 1', NULL, '2025-11-19 16:21:29'),
(56, 40, 'operator 2', NULL, '2025-11-19 16:21:29'),
(57, 40, 'operator 3', NULL, '2025-11-19 16:21:29'),
(58, 41, 'Ashakiran Jyoti', NULL, '2025-11-19 16:21:46'),
(59, 41, 'operator 1', NULL, '2025-11-19 16:21:46'),
(60, 41, 'operator 2', NULL, '2025-11-19 16:21:46'),
(61, 41, 'operator 3', NULL, '2025-11-19 16:21:46'),
(62, 42, 'Ashakiran Jyoti', NULL, '2025-11-19 16:22:27'),
(63, 42, 'operator 1', NULL, '2025-11-19 16:22:27'),
(64, 42, 'operator 2', NULL, '2025-11-19 16:22:27'),
(65, 42, 'operator 3', NULL, '2025-11-19 16:22:27'),
(66, 42, 'operator 4', NULL, '2025-11-19 16:22:27'),
(67, 43, 'operator 1', NULL, '2025-11-19 16:22:46'),
(68, 43, 'operator 2', NULL, '2025-11-19 16:22:46'),
(69, 43, 'operator 3', NULL, '2025-11-19 16:22:46'),
(70, 44, 'operator 1', NULL, '2025-11-19 16:25:41'),
(71, 44, 'operator 2', NULL, '2025-11-19 16:25:41'),
(72, 45, 'operator 1', NULL, '2025-11-19 16:26:30'),
(73, 45, 'operator 2', NULL, '2025-11-19 16:26:30'),
(74, 46, 'operator 2', NULL, '2025-11-19 18:02:56'),
(75, 46, 'operator 3', NULL, '2025-11-19 18:02:56'),
(76, 48, 'rr', NULL, '2025-11-20 10:19:28'),
(77, 48, 'USER 1', NULL, '2025-11-20 10:19:28'),
(78, 48, 'USER 2', NULL, '2025-11-20 10:19:28'),
(79, 49, 'operator 1', NULL, '2025-11-20 16:59:23'),
(80, 49, 'operator 2', NULL, '2025-11-20 16:59:23'),
(81, 50, 'operator 4', NULL, '2025-11-20 18:23:20'),
(82, 50, 'Ravikumar Shukla', NULL, '2025-11-20 18:23:20'),
(83, 51, 'operator 2', NULL, '2025-11-21 10:20:12'),
(84, 52, 'Ravikumar Shukla', NULL, '2025-11-21 10:26:57'),
(85, 53, 'operator 3', NULL, '2025-11-21 14:44:07'),
(86, 53, 'operator 4', NULL, '2025-11-21 14:44:07'),
(87, 54, 'Ravikumar Shukla', NULL, '2025-11-21 14:44:37'),
(88, 55, 'rr', NULL, '2025-11-21 14:52:31'),
(89, 56, 'kkkkk', NULL, '2025-11-21 18:13:21'),
(90, 57, 'operator 1', NULL, '2025-11-24 10:00:02'),
(91, 57, 'operator 2', NULL, '2025-11-24 10:00:02'),
(92, 58, 'kkkkk', NULL, '2025-11-24 10:32:37'),
(93, 59, 'operator 1', NULL, '2025-11-24 12:41:22'),
(94, 59, 'operator 2', NULL, '2025-11-24 12:41:22'),
(95, 60, 'operator 1', NULL, '2025-11-25 10:09:54'),
(96, 60, 'operator 2', NULL, '2025-11-25 10:09:54'),
(97, 61, 'operator 1', NULL, '2025-11-25 10:10:18'),
(98, 62, 'operator 1', NULL, '2025-11-25 11:25:26'),
(99, 62, 'rr', NULL, '2025-11-25 11:25:26'),
(100, 63, 'kkkkk', NULL, '2025-11-25 12:01:33'),
(101, 63, 'operator 4', NULL, '2025-11-25 12:01:33'),
(102, 64, 'kkkkk', NULL, '2025-11-25 12:01:41'),
(103, 64, 'operator 4', NULL, '2025-11-25 12:01:41'),
(104, 65, 'operator 4', NULL, '2025-11-25 12:21:06'),
(105, 66, 'operator 4', NULL, '2025-11-25 13:16:40'),
(106, 66, 'Ravikumar Shukla', NULL, '2025-11-25 13:16:40'),
(107, 67, 'operator 4', NULL, '2025-11-25 13:17:13'),
(108, 68, 'rr', NULL, '2025-11-25 14:35:51'),
(109, 69, 'operator 2', NULL, '2025-11-25 14:45:31'),
(110, 69, 'operator 3', NULL, '2025-11-25 14:45:31'),
(111, 69, 'operator 4', NULL, '2025-11-25 14:45:31'),
(112, 70, 'operator 3', NULL, '2025-11-25 14:46:12'),
(113, 70, 'operator 4', NULL, '2025-11-25 14:46:12'),
(114, 71, 'operator 3', NULL, '2025-11-25 15:22:14'),
(115, 72, 'operator 3', NULL, '2025-11-25 15:23:05'),
(116, 72, 'operator 4', NULL, '2025-11-25 15:23:05');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `role` varchar(200) DEFAULT NULL,
  `access_type` enum('full','view') NOT NULL DEFAULT 'full'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `role`, `access_type`) VALUES
(1, 'Ravi', '123', 'Ravikumar Shukla', 'admin', 'full'),
(2, 'Ashakiran', '123', 'Ashakiran Jyoti', 'admin', 'view'),
(3, 'operator1', '123', 'operator 1', 'admin', 'full'),
(4, 'operator2', '123', 'operator 2', 'admin', 'full'),
(5, 'op1', '123', 'operator 3', 'admin', 'full'),
(6, 'op2', '123', 'operator 4', 'admin', 'full'),
(7, 'USER_1', '123', 'USER 1', 'admin', 'full'),
(8, 'USER_2', '123', 'USER 2', 'user', 'view'),
(9, 'Kavita', '123', 'rr', 'user', 'full'),
(10, 'Jaya', '123', 'kkkkk', 'admin', 'full');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `items_master`
--
ALTER TABLE `items_master`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lcs`
--
ALTER TABLE `lcs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_lcs_per_site` (`site_id`);

--
-- Indexes for table `lcs_item`
--
ALTER TABLE `lcs_item`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lcs_master_media`
--
ALTER TABLE `lcs_master_media`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_lcs_master_date` (`lcs_id`,`status_date`,`uploaded_at`);

--
-- Indexes for table `lcs_master_media_change_log`
--
ALTER TABLE `lcs_master_media_change_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_lcs_master_media_change` (`lcs_id`,`status_date`,`action_at`);

--
-- Indexes for table `lcs_master_notes`
--
ALTER TABLE `lcs_master_notes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_lcs_date` (`lcs_id`,`status_date`),
  ADD KEY `idx_lmn_lcs_id` (`lcs_id`);

--
-- Indexes for table `lcs_master_note_contributors`
--
ALTER TABLE `lcs_master_note_contributors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_lcs_mn` (`lcs_id`,`status_date`,`contributor_name`),
  ADD KEY `idx_lcs_mn_contrib` (`contributor_name`);

--
-- Indexes for table `lcs_media`
--
ALTER TABLE `lcs_media`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_lcs_item_date` (`lcs_id`,`item_name`,`status_date`,`uploaded_at`);

--
-- Indexes for table `lcs_media_change_log`
--
ALTER TABLE `lcs_media_change_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_lcs_media_change` (`lcs_id`,`item_name`,`status_date`,`action_at`);

--
-- Indexes for table `lcs_status_history`
--
ALTER TABLE `lcs_status_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `site_id` (`site_id`),
  ADD KEY `tubewell_id` (`lcs_id`);

--
-- Indexes for table `lcs_status_locks`
--
ALTER TABLE `lcs_status_locks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_lock` (`lcs_id`,`status_date`);

--
-- Indexes for table `media_change_log`
--
ALTER TABLE `media_change_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_media_change` (`tubewell_id`,`item_name`,`status_date`,`action_at`);

--
-- Indexes for table `media_uploads`
--
ALTER TABLE `media_uploads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tubewell_item_date` (`tubewell_id`,`item_name`,`status_date`,`uploaded_at`);

--
-- Indexes for table `parameters`
--
ALTER TABLE `parameters`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tubewell_id` (`tubewell_id`);

--
-- Indexes for table `sites`
--
ALTER TABLE `sites`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `status_change_log`
--
ALTER TABLE `status_change_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `status_history`
--
ALTER TABLE `status_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `site_id` (`site_id`),
  ADD KEY `tubewell_id` (`tubewell_id`);

--
-- Indexes for table `status_locks`
--
ALTER TABLE `status_locks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_lock` (`tubewell_id`,`status_date`);

--
-- Indexes for table `tubewells`
--
ALTER TABLE `tubewells`
  ADD PRIMARY KEY (`id`),
  ADD KEY `site_id` (`site_id`);

--
-- Indexes for table `tubewell_master_media`
--
ALTER TABLE `tubewell_master_media`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tmm` (`tubewell_id`,`status_date`,`uploaded_at`);

--
-- Indexes for table `tubewell_master_notes`
--
ALTER TABLE `tubewell_master_notes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_tubewell_date` (`tubewell_id`,`status_date`);

--
-- Indexes for table `tubewell_master_note_contributors`
--
ALTER TABLE `tubewell_master_note_contributors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_tw_mn` (`tubewell_id`,`status_date`,`contributor_name`),
  ADD KEY `idx_tw_mn_contrib` (`contributor_name`);

--
-- Indexes for table `tubewell_media`
--
ALTER TABLE `tubewell_media`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tubewell_id` (`tubewell_id`);

--
-- Indexes for table `updates`
--
ALTER TABLE `updates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_updates_entity` (`entity_type`,`entity_id`,`status_date`,`updated_at`),
  ADD KEY `idx_updates_by` (`updated_by`,`status_date`);

--
-- Indexes for table `update_contributors`
--
ALTER TABLE `update_contributors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_update_contributor` (`update_id`,`contributor_name`),
  ADD KEY `idx_contributor_name` (`contributor_name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `items_master`
--
ALTER TABLE `items_master`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `lcs`
--
ALTER TABLE `lcs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `lcs_item`
--
ALTER TABLE `lcs_item`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `lcs_master_media`
--
ALTER TABLE `lcs_master_media`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `lcs_master_media_change_log`
--
ALTER TABLE `lcs_master_media_change_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `lcs_master_notes`
--
ALTER TABLE `lcs_master_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `lcs_master_note_contributors`
--
ALTER TABLE `lcs_master_note_contributors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `lcs_media`
--
ALTER TABLE `lcs_media`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `lcs_media_change_log`
--
ALTER TABLE `lcs_media_change_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `lcs_status_history`
--
ALTER TABLE `lcs_status_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=98;

--
-- AUTO_INCREMENT for table `lcs_status_locks`
--
ALTER TABLE `lcs_status_locks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `media_change_log`
--
ALTER TABLE `media_change_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- AUTO_INCREMENT for table `media_uploads`
--
ALTER TABLE `media_uploads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT for table `parameters`
--
ALTER TABLE `parameters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sites`
--
ALTER TABLE `sites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `status_change_log`
--
ALTER TABLE `status_change_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=139;

--
-- AUTO_INCREMENT for table `status_history`
--
ALTER TABLE `status_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=139;

--
-- AUTO_INCREMENT for table `status_locks`
--
ALTER TABLE `status_locks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tubewells`
--
ALTER TABLE `tubewells`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

--
-- AUTO_INCREMENT for table `tubewell_master_media`
--
ALTER TABLE `tubewell_master_media`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `tubewell_master_notes`
--
ALTER TABLE `tubewell_master_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

--
-- AUTO_INCREMENT for table `tubewell_master_note_contributors`
--
ALTER TABLE `tubewell_master_note_contributors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `tubewell_media`
--
ALTER TABLE `tubewell_media`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `updates`
--
ALTER TABLE `updates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT for table `update_contributors`
--
ALTER TABLE `update_contributors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=117;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `lcs_master_notes`
--
ALTER TABLE `lcs_master_notes`
  ADD CONSTRAINT `fk_lmn_lcs` FOREIGN KEY (`lcs_id`) REFERENCES `lcs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `parameters`
--
ALTER TABLE `parameters`
  ADD CONSTRAINT `parameters_ibfk_1` FOREIGN KEY (`tubewell_id`) REFERENCES `tubewells` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `status_history`
--
ALTER TABLE `status_history`
  ADD CONSTRAINT `status_history_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `sites` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `status_history_ibfk_2` FOREIGN KEY (`tubewell_id`) REFERENCES `tubewells` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tubewells`
--
ALTER TABLE `tubewells`
  ADD CONSTRAINT `tubewells_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `sites` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tubewell_master_notes`
--
ALTER TABLE `tubewell_master_notes`
  ADD CONSTRAINT `fk_tmn_tw` FOREIGN KEY (`tubewell_id`) REFERENCES `tubewells` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tubewell_media`
--
ALTER TABLE `tubewell_media`
  ADD CONSTRAINT `tubewell_media_ibfk_1` FOREIGN KEY (`tubewell_id`) REFERENCES `tubewells` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `update_contributors`
--
ALTER TABLE `update_contributors`
  ADD CONSTRAINT `fk_uc_update` FOREIGN KEY (`update_id`) REFERENCES `updates` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

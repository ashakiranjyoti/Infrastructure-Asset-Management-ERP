-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 29, 2025 at 12:27 PM
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
  `tubewell_name` varchar(255) NOT NULL,
  `tw_address` text DEFAULT NULL,
  `incharge_name` varchar(255) DEFAULT NULL,
  `sim_no` varchar(20) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `installation_date` date DEFAULT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `role` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `role`) VALUES
(1, 'Ravi', 'ravi@28121989', 'Ravi Shukla', 'admin'),
(2, 'Vijay', 'vijay@28121989', 'Vijay Maurya', 'admin'),
(3, 'Sagar', 'sagar@28121989', 'Sagar Jadhav', 'admin'),
(4, 'Kavita', 'kavita@123', 'Kavita', 'user'),
(5, 'Jaya', 'jaya@123', 'Jaya', 'user'),
(6, 'Vaishnavi', 'vaishnavi@123', 'Vaishnavi Bandgar', 'user'),
(7, 'Manoj', 'manoj@123', 'Manoj Yadhav', 'user'),
(9, 'Yogesh', 'yogesh@123', 'Yogesh Jadhav', 'user'),
(10, 'Rohan', 'rohan@123', 'Rohan Jadhav', 'user'),
(11, 'Rohit', 'rohit@123', 'Rohit Khude', 'user'),
(12, 'Anand', 'anand@123', 'Anand Kushwaha', 'Admin'),
(13, 'Chetan', 'chetan@123', 'Chetan Chimman', 'user'),
(14, 'Sumit', 'sumit@123', 'Sumit Chawan', 'user'),
(15, 'Ashakiran', '123', 'Ashakiran Jyoti', 'admin'),
(16, 'operator1', '$2y$10$lbBTIhdifAEdFMCkk2WJpOUUh.4ChtQyBEnfUE1gesrEqWFx.qce6', 'HOPE Engineering', 'User');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lcs`
--
ALTER TABLE `lcs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lcs_item`
--
ALTER TABLE `lcs_item`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lcs_status_history`
--
ALTER TABLE `lcs_status_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lcs_status_locks`
--
ALTER TABLE `lcs_status_locks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `parameters`
--
ALTER TABLE `parameters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sites`
--
ALTER TABLE `sites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `status_change_log`
--
ALTER TABLE `status_change_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `status_history`
--
ALTER TABLE `status_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `status_locks`
--
ALTER TABLE `status_locks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tubewells`
--
ALTER TABLE `tubewells`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Constraints for dumped tables
--

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

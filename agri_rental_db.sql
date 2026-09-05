-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 05, 2026 at 01:45 PM
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
-- Database: `agri_rental_db`
--
CREATE DATABASE IF NOT EXISTS `agri_rental_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `agri_rental_db`;

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `booking_id` int(11) NOT NULL,
  `request_code` varchar(20) NOT NULL,
  `renter_id` int(11) NOT NULL,
  `phone_number` varchar(15) DEFAULT NULL,
  `equipment_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `total_days` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `total_amount` decimal(10,2) NOT NULL,
  `advance_amount` decimal(10,2) NOT NULL,
  `remaining_cod` decimal(10,2) NOT NULL,
  `id_proof_doc` varchar(255) NOT NULL,
  `id_number` varchar(50) DEFAULT NULL,
  `delivery_address` text NOT NULL,
  `status` enum('Pending','Accepted','Rejected','Delivered','Returned','Overdue') DEFAULT 'Pending',
  `late_charge` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`booking_id`, `request_code`, `renter_id`, `phone_number`, `equipment_id`, `start_date`, `end_date`, `total_days`, `quantity`, `total_amount`, `advance_amount`, `remaining_cod`, `id_proof_doc`, `id_number`, `delivery_address`, `status`, `late_charge`, `created_at`) VALUES
(1, 'REQ-5B1E2E', 5, '9845442268', 1, '2026-08-31', '2026-09-01', 2, 1, 886.00, 44.00, 842.00, 'ID_5_1788010293_bd7d4257.png', '124546556778', 'hassan karnataka', 'Pending', 0.00, '2026-08-29 13:31:33'),
(2, 'REQ-11B333', 5, '9845442268', 1, '2026-08-29', '2026-08-29', 1, 1, 443.00, 44.00, 399.00, 'ID_5_1788016865_509ef17b.png', 'er3ry65u6u7i78', 'hassan karnataka', 'Pending', 0.00, '2026-08-29 15:21:05'),
(3, 'REQ-3CC9E6', 5, '9987653421', 1, '2026-08-30', '2026-08-30', 1, 1, 443.00, 44.00, 399.00, 'ID_5_1788098739_92b27e1f.png', '868545', 'hassan karnataka', 'Pending', 0.00, '2026-08-30 14:05:39');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `icon_class` varchar(50) DEFAULT 'fa-tractor'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `category_name`, `description`, `image`, `icon_class`) VALUES
(1, 'Tractor', 'Heavy-duty tractors and utility vehicles for all farming operations.', NULL, 'fa-solid fa-tractor'),
(2, 'Harvesting', 'Combines, reapers, and crop gathering machinery.', NULL, 'fa-solid fa-wheat-awn'),
(3, 'Irrigation', 'Water pumps, sprinklers, and drip irrigation systems.', NULL, 'fa-solid fa-water'),
(4, 'Tillage', 'Plows, cultivators, and soil preparation equipment.', NULL, 'fa-solid fa-seedling'),
(5, 'Seeding', 'Seed drills, planters, and broadcasting machinery.', NULL, 'fa-solid fa-hands-holding-child'),
(6, 'Spraying', 'Pesticide sprayers, mist blowers, and boom sprayers.', NULL, 'fa-solid fa-spray-can-sparkles');

-- --------------------------------------------------------

--
-- Table structure for table `equipment`
--

CREATE TABLE `equipment` (
  `equipment_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `lender_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `category` varchar(50) NOT NULL,
  `brand_model` varchar(100) NOT NULL,
  `power_hp` int(11) DEFAULT NULL,
  `drive_type` varchar(20) DEFAULT NULL,
  `model_year` int(11) DEFAULT NULL,
  `fuel_type` varchar(30) DEFAULT NULL,
  `working_width` varchar(50) DEFAULT NULL,
  `equipment_condition` enum('New','Good','Used') DEFAULT 'Good',
  `price_per_day` decimal(10,2) NOT NULL,
  `min_booking_days` int(11) DEFAULT 1,
  `service_location` varchar(100) NOT NULL,
  `distance_km` decimal(5,1) DEFAULT 5.0,
  `description` text DEFAULT NULL,
  `image` varchar(255) NOT NULL,
  `badge` enum('RECOMMENDED','POPULAR','TOP RATED','BEST PRICE','NONE') DEFAULT 'NONE',
  `status` enum('Available','Rented Out','Under Maintenance','Inactive') DEFAULT 'Available',
  `rating` decimal(2,1) DEFAULT 4.5,
  `rating_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_featured` tinyint(1) DEFAULT 0,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `equipment`
--

INSERT INTO `equipment` (`equipment_id`, `category_id`, `lender_id`, `title`, `category`, `brand_model`, `power_hp`, `drive_type`, `model_year`, `fuel_type`, `working_width`, `equipment_condition`, `price_per_day`, `min_booking_days`, `service_location`, `distance_km`, `description`, `image`, `badge`, `status`, `rating`, `rating_count`, `created_at`, `is_featured`, `latitude`, `longitude`) VALUES
(1, 1, 7, 'tractor', 'Tractor', 'vg gr', 23, '4WD', 2026, 'Diesel', '', '', 443.00, 1, 'Bengaluru Rural, Tumakuru', 25.0, 'gbb', '1787390934_5282.png', '', 'Available', 0.0, 0, '2026-08-22 09:28:54', 1, NULL, NULL),
(3, 2, 7, 'trt', 'Harvesting', 'ttr yt', 45, '4WD', 2026, 'Diesel', '', 'Good', 44.00, 1, 'Bengaluru Rural', 25.0, 'gtrt', 'default.png', 'NONE', 'Available', 0.0, 0, '2026-09-03 14:17:28', 1, NULL, NULL),
(4, 2, 7, 'John Deere Combine Harvester', 'Harvesting', 'john Deere 9500 john Deere 9500', 34, '4WD', 2022, 'Diesel', '', 'Good', 8000.00, 1, 'Bengaluru Rural, Hassan Karnataka', 25.0, 'John Deere Combine Harvester is a powerful and efficient harvesting machine designed for quickly harvesting crops such as wheat, rice, and other grains. It combines cutting, threshing, separating, and collecting operations in a single machine. It is suitable for large agricultural fields and helps reduce harvesting time and manual labor.', '1787821036_5572.webp', '', 'Available', 0.0, 0, '2026-08-27 08:57:16', 1, NULL, NULL),
(5, 1, 7, 'Tractor', 'Tractor', 'Mahindra OJA 2121  4', 21, '4WD', 2026, 'Diesel', '', 'New', 2000.00, 1, 'Hassan', 0.0, 'Mahindra OJA 2121 4WD Steering type is power steering. \r\nIt has 12 forward + 12 reverse gears.\r\nIt has a  strong loading capacity of 950 kg. \r\nIt has a  unique, stylish modern body design and comes with LED headlamps. \r\nIt has a large fuel tank capacity, which allows farmers to work long hours on farms.', '1788524959_Mahindra--OJA-21211735718187_elQi6fl2s9.webp', 'RECOMMENDED', 'Available', 0.0, 0, '2026-09-04 12:25:35', 1, 13.22720000, 77.57400000);

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

CREATE TABLE `items` (
  `item_id` int(11) NOT NULL,
  `lender_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `year_of_purchase` int(11) DEFAULT NULL,
  `item_condition` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `price_per_day` decimal(10,2) NOT NULL,
  `security_deposit` decimal(10,2) DEFAULT 0.00,
  `min_rental_days` int(11) DEFAULT 1,
  `max_rental_days` int(11) DEFAULT NULL,
  `status` enum('Available','Not Available') DEFAULT 'Available',
  `service_areas` text DEFAULT NULL,
  `fuel_type` varchar(50) DEFAULT NULL,
  `power_hp` int(11) DEFAULT NULL,
  `working_hours` int(11) DEFAULT NULL,
  `is_recommended` tinyint(1) DEFAULT 0,
  `image` varchar(255) DEFAULT 'default.png',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `items`
--

INSERT INTO `items` (`item_id`, `lender_id`, `title`, `category`, `brand`, `model`, `year_of_purchase`, `item_condition`, `description`, `price_per_day`, `security_deposit`, `min_rental_days`, `max_rental_days`, `status`, `service_areas`, `fuel_type`, `power_hp`, `working_hours`, `is_recommended`, `image`, `created_at`, `latitude`, `longitude`) VALUES
(1, 7, 'tractor', 'Tractor', 'vg', 'gr', 2026, 'Excellent', 'gbb', 443.00, 44.00, 1, 8, 'Available', 'Bengaluru Rural, Tumakuru', 'Diesel', 23, 3, 1, '1787390934_5282.png', '2026-08-22 09:28:54', NULL, NULL),
(3, 7, 'trt', 'Harvesting', 'ttr', 'yt', 2026, 'Excellent', 'gtrt', 44.00, 42.00, 1, 5, 'Available', 'Bengaluru Rural', 'Diesel', 45, 2, 0, '', '2026-08-22 14:31:34', NULL, NULL),
(4, 7, 'John Deere Combine Harvester', 'Harvesting', 'john Deere 9500', 'john Deere 9500', 2022, 'Good', '\r\nJohn Deere Combine Harvester is a powerful and efficient harvesting machine designed for quickly harvesting crops such as wheat, rice, and other grains. It combines cutting, threshing, separating, and collecting operations in a single machine. It is suitable for large agricultural fields and helps reduce harvesting time and manual labor.\r\n', 8000.00, 2000.00, 1, 4, 'Available', 'Bengaluru Rural, Hassan Karnataka', 'Diesel', 34, 5, 1, '1787821036_5572.webp', '2026-08-27 08:57:16', NULL, NULL),
(5, 7, 'Tractor', 'Tractor', 'Mahindra OJA 2121 ', '4', 2026, 'Excellent', 'Mahindra OJA 2121 4WD Steering type is power steering. \r\nIt has 12 forward + 12 reverse gears.\r\nIt has a  strong loading capacity of 950 kg. \r\nIt has a  unique, stylish modern body design and comes with LED headlamps. \r\nIt has a large fuel tank capacity, which allows farmers to work long hours on farms.', 2000.00, 1000.00, 1, 4, 'Available', 'Bengaluru Rural, Hassan, Mysuru, Mandya', 'Diesel', 21, 3, 1, '1788524735_7090.webp', '2026-09-04 12:25:35', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `saved_items`
--

CREATE TABLE `saved_items` (
  `saved_id` int(11) NOT NULL,
  `renter_id` int(11) NOT NULL,
  `equipment_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('renter','lender') NOT NULL,
  `security_question` varchar(255) DEFAULT NULL,
  `security_answer` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT 'default_avatar.png',
  `reset_otp` varchar(6) DEFAULT NULL,
  `otp_expires_at` datetime DEFAULT NULL,
  `otp_code` varchar(6) DEFAULT NULL,
  `otp_expiry` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email`, `phone`, `password`, `role`, `security_question`, `security_answer`, `address`, `profile_pic`, `reset_otp`, `otp_expires_at`, `otp_code`, `otp_expiry`) VALUES
(3, 'sowmya', 'sowmya2006@gmail.com', '1234567890', '$2y$10$PGavgC5cXvwP6pDrbRW.U.hxBiNmhHdfjjbfRv.z16L4Hs69WmneO', 'renter', 'first_pet', 'pinky', 'banglore', 'default_avatar.png', NULL, NULL, NULL, NULL),
(4, 'sowmya r', 'sowmyar2006@gmail.com', '1234567891', '$2y$10$jTM5b6Ag4R./IxHAMOnarOfVYc4w7BEvTlMu1Y91lEXRgWNX3nL9K', 'renter', 'first_pet', 'pinkyz', 'mysuru', 'default_avatar.png', NULL, NULL, NULL, NULL),
(5, 'pragathi', 'pragathikt29@gmail.com', '9845442268', '$2y$10$zibi4oNs8WechG/OzlMmu.DZzv88c4yMmw3DwoLoo8YdK8fVEDrHa', 'renter', 'birth_city', 'arsikere', 'Hassan Karnataka', 'default_avatar.png', NULL, NULL, NULL, NULL),
(6, 'Tejomurthy', 'pragathikt2@gmail.com', '123456789', '$2y$10$KnT2yCXV.txkoTz.wQpyVeOqVj4PxHK4gRgfGwjYjM.bC3evG9zOy', 'lender', 'first_school', 'kodhialli school', 'mysuru', 'default_avatar.png', NULL, NULL, NULL, NULL),
(7, 'Tejomurthy', 'pragathikt4@gmail.com', '7975865577', '$2y$10$atpaFVFLYvVlHz.Vuv4MY.XxRqJntVb6T/ZWlI7qmMzYQMnwP4kz.', 'lender', 'first_school', 'government school', 'mysuru', 'default_avatar.png', NULL, NULL, NULL, NULL),
(8, 'Radha', 'rhn39@gmail.com', '2708200627', '$2y$10$rL9fd7lOcSMBCnVIiCFOUubMj2xmQykBL1RlNm6hNNuIlpj2E5JBS', 'renter', 'first_pet', 'ruby', 'Hassan Karnataka', 'default_avatar.png', NULL, NULL, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD UNIQUE KEY `request_code` (`request_code`),
  ADD KEY `renter_id` (`renter_id`),
  ADD KEY `equipment_id` (`equipment_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `equipment`
--
ALTER TABLE `equipment`
  ADD PRIMARY KEY (`equipment_id`),
  ADD KEY `lender_id` (`lender_id`);

--
-- Indexes for table `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `lender_id` (`lender_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `saved_items`
--
ALTER TABLE `saved_items`
  ADD PRIMARY KEY (`saved_id`),
  ADD KEY `renter_id` (`renter_id`),
  ADD KEY `equipment_id` (`equipment_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `equipment`
--
ALTER TABLE `equipment`
  MODIFY `equipment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `items`
--
ALTER TABLE `items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `saved_items`
--
ALTER TABLE `saved_items`
  MODIFY `saved_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`renter_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`equipment_id`) ON DELETE CASCADE;

--
-- Constraints for table `equipment`
--
ALTER TABLE `equipment`
  ADD CONSTRAINT `equipment_ibfk_1` FOREIGN KEY (`lender_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `items`
--
ALTER TABLE `items`
  ADD CONSTRAINT `items_ibfk_1` FOREIGN KEY (`lender_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `saved_items`
--
ALTER TABLE `saved_items`
  ADD CONSTRAINT `saved_items_ibfk_1` FOREIGN KEY (`renter_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `saved_items_ibfk_2` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`equipment_id`) ON DELETE CASCADE;
--
-- Database: `phpmyadmin`
--
CREATE DATABASE IF NOT EXISTS `phpmyadmin` DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;
USE `phpmyadmin`;

-- --------------------------------------------------------

--
-- Table structure for table `pma__bookmark`
--

CREATE TABLE `pma__bookmark` (
  `id` int(10) UNSIGNED NOT NULL,
  `dbase` varchar(255) NOT NULL DEFAULT '',
  `user` varchar(255) NOT NULL DEFAULT '',
  `label` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `query` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Bookmarks';

-- --------------------------------------------------------

--
-- Table structure for table `pma__central_columns`
--

CREATE TABLE `pma__central_columns` (
  `db_name` varchar(64) NOT NULL,
  `col_name` varchar(64) NOT NULL,
  `col_type` varchar(64) NOT NULL,
  `col_length` text DEFAULT NULL,
  `col_collation` varchar(64) NOT NULL,
  `col_isNull` tinyint(1) NOT NULL,
  `col_extra` varchar(255) DEFAULT '',
  `col_default` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Central list of columns';

-- --------------------------------------------------------

--
-- Table structure for table `pma__column_info`
--

CREATE TABLE `pma__column_info` (
  `id` int(5) UNSIGNED NOT NULL,
  `db_name` varchar(64) NOT NULL DEFAULT '',
  `table_name` varchar(64) NOT NULL DEFAULT '',
  `column_name` varchar(64) NOT NULL DEFAULT '',
  `comment` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `mimetype` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `transformation` varchar(255) NOT NULL DEFAULT '',
  `transformation_options` varchar(255) NOT NULL DEFAULT '',
  `input_transformation` varchar(255) NOT NULL DEFAULT '',
  `input_transformation_options` varchar(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Column information for phpMyAdmin';

-- --------------------------------------------------------

--
-- Table structure for table `pma__designer_settings`
--

CREATE TABLE `pma__designer_settings` (
  `username` varchar(64) NOT NULL,
  `settings_data` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Settings related to Designer';

-- --------------------------------------------------------

--
-- Table structure for table `pma__export_templates`
--

CREATE TABLE `pma__export_templates` (
  `id` int(5) UNSIGNED NOT NULL,
  `username` varchar(64) NOT NULL,
  `export_type` varchar(10) NOT NULL,
  `template_name` varchar(64) NOT NULL,
  `template_data` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Saved export templates';

-- --------------------------------------------------------

--
-- Table structure for table `pma__favorite`
--

CREATE TABLE `pma__favorite` (
  `username` varchar(64) NOT NULL,
  `tables` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Favorite tables';

-- --------------------------------------------------------

--
-- Table structure for table `pma__history`
--

CREATE TABLE `pma__history` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `username` varchar(64) NOT NULL DEFAULT '',
  `db` varchar(64) NOT NULL DEFAULT '',
  `table` varchar(64) NOT NULL DEFAULT '',
  `timevalue` timestamp NOT NULL DEFAULT current_timestamp(),
  `sqlquery` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='SQL history for phpMyAdmin';

-- --------------------------------------------------------

--
-- Table structure for table `pma__navigationhiding`
--

CREATE TABLE `pma__navigationhiding` (
  `username` varchar(64) NOT NULL,
  `item_name` varchar(64) NOT NULL,
  `item_type` varchar(64) NOT NULL,
  `db_name` varchar(64) NOT NULL,
  `table_name` varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Hidden items of navigation tree';

-- --------------------------------------------------------

--
-- Table structure for table `pma__pdf_pages`
--

CREATE TABLE `pma__pdf_pages` (
  `db_name` varchar(64) NOT NULL DEFAULT '',
  `page_nr` int(10) UNSIGNED NOT NULL,
  `page_descr` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='PDF relation pages for phpMyAdmin';

-- --------------------------------------------------------

--
-- Table structure for table `pma__recent`
--

CREATE TABLE `pma__recent` (
  `username` varchar(64) NOT NULL,
  `tables` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Recently accessed tables';

--
-- Dumping data for table `pma__recent`
--

INSERT INTO `pma__recent` (`username`, `tables`) VALUES
('root', '[{\"db\":\"agri_rental_db\",\"table\":\"items\"},{\"db\":\"agri_rental_db\",\"table\":\"equipment\"},{\"db\":\"agri_rental_db\",\"table\":\"categories\"},{\"db\":\"agri_rental_db\",\"table\":\"users\"},{\"db\":\"agri_rental_db\",\"table\":\"bookings\"},{\"db\":\"agri_rental_db\",\"table\":\"notifications\"},{\"db\":\"agri_rental_db\",\"table\":\"saved_items\"}]');

-- --------------------------------------------------------

--
-- Table structure for table `pma__relation`
--

CREATE TABLE `pma__relation` (
  `master_db` varchar(64) NOT NULL DEFAULT '',
  `master_table` varchar(64) NOT NULL DEFAULT '',
  `master_field` varchar(64) NOT NULL DEFAULT '',
  `foreign_db` varchar(64) NOT NULL DEFAULT '',
  `foreign_table` varchar(64) NOT NULL DEFAULT '',
  `foreign_field` varchar(64) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Relation table';

-- --------------------------------------------------------

--
-- Table structure for table `pma__savedsearches`
--

CREATE TABLE `pma__savedsearches` (
  `id` int(5) UNSIGNED NOT NULL,
  `username` varchar(64) NOT NULL DEFAULT '',
  `db_name` varchar(64) NOT NULL DEFAULT '',
  `search_name` varchar(64) NOT NULL DEFAULT '',
  `search_data` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Saved searches';

-- --------------------------------------------------------

--
-- Table structure for table `pma__table_coords`
--

CREATE TABLE `pma__table_coords` (
  `db_name` varchar(64) NOT NULL DEFAULT '',
  `table_name` varchar(64) NOT NULL DEFAULT '',
  `pdf_page_number` int(11) NOT NULL DEFAULT 0,
  `x` float UNSIGNED NOT NULL DEFAULT 0,
  `y` float UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Table coordinates for phpMyAdmin PDF output';

-- --------------------------------------------------------

--
-- Table structure for table `pma__table_info`
--

CREATE TABLE `pma__table_info` (
  `db_name` varchar(64) NOT NULL DEFAULT '',
  `table_name` varchar(64) NOT NULL DEFAULT '',
  `display_field` varchar(64) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Table information for phpMyAdmin';

-- --------------------------------------------------------

--
-- Table structure for table `pma__table_uiprefs`
--

CREATE TABLE `pma__table_uiprefs` (
  `username` varchar(64) NOT NULL,
  `db_name` varchar(64) NOT NULL,
  `table_name` varchar(64) NOT NULL,
  `prefs` text NOT NULL,
  `last_update` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Tables'' UI preferences';

--
-- Dumping data for table `pma__table_uiprefs`
--

INSERT INTO `pma__table_uiprefs` (`username`, `db_name`, `table_name`, `prefs`, `last_update`) VALUES
('root', 'agri_rental_db', 'equipment', '{\"sorted_col\":\"`equipment`.`equipment_id` ASC\"}', '2026-08-25 11:21:41');

-- --------------------------------------------------------

--
-- Table structure for table `pma__tracking`
--

CREATE TABLE `pma__tracking` (
  `db_name` varchar(64) NOT NULL,
  `table_name` varchar(64) NOT NULL,
  `version` int(10) UNSIGNED NOT NULL,
  `date_created` datetime NOT NULL,
  `date_updated` datetime NOT NULL,
  `schema_snapshot` text NOT NULL,
  `schema_sql` text DEFAULT NULL,
  `data_sql` longtext DEFAULT NULL,
  `tracking` set('UPDATE','REPLACE','INSERT','DELETE','TRUNCATE','CREATE DATABASE','ALTER DATABASE','DROP DATABASE','CREATE TABLE','ALTER TABLE','RENAME TABLE','DROP TABLE','CREATE INDEX','DROP INDEX','CREATE VIEW','ALTER VIEW','DROP VIEW') DEFAULT NULL,
  `tracking_active` int(1) UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Database changes tracking for phpMyAdmin';

-- --------------------------------------------------------

--
-- Table structure for table `pma__userconfig`
--

CREATE TABLE `pma__userconfig` (
  `username` varchar(64) NOT NULL,
  `timevalue` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `config_data` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='User preferences storage for phpMyAdmin';

--
-- Dumping data for table `pma__userconfig`
--

INSERT INTO `pma__userconfig` (`username`, `timevalue`, `config_data`) VALUES
('root', '2026-08-25 15:11:39', '{\"Console\\/Mode\":\"collapse\",\"NavigationWidth\":0}');

-- --------------------------------------------------------

--
-- Table structure for table `pma__usergroups`
--

CREATE TABLE `pma__usergroups` (
  `usergroup` varchar(64) NOT NULL,
  `tab` varchar(64) NOT NULL,
  `allowed` enum('Y','N') NOT NULL DEFAULT 'N'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='User groups with configured menu items';

-- --------------------------------------------------------

--
-- Table structure for table `pma__users`
--

CREATE TABLE `pma__users` (
  `username` varchar(64) NOT NULL,
  `usergroup` varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Users and their assignments to user groups';

--
-- Indexes for dumped tables
--

--
-- Indexes for table `pma__bookmark`
--
ALTER TABLE `pma__bookmark`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pma__central_columns`
--
ALTER TABLE `pma__central_columns`
  ADD PRIMARY KEY (`db_name`,`col_name`);

--
-- Indexes for table `pma__column_info`
--
ALTER TABLE `pma__column_info`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `db_name` (`db_name`,`table_name`,`column_name`);

--
-- Indexes for table `pma__designer_settings`
--
ALTER TABLE `pma__designer_settings`
  ADD PRIMARY KEY (`username`);

--
-- Indexes for table `pma__export_templates`
--
ALTER TABLE `pma__export_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `u_user_type_template` (`username`,`export_type`,`template_name`);

--
-- Indexes for table `pma__favorite`
--
ALTER TABLE `pma__favorite`
  ADD PRIMARY KEY (`username`);

--
-- Indexes for table `pma__history`
--
ALTER TABLE `pma__history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `username` (`username`,`db`,`table`,`timevalue`);

--
-- Indexes for table `pma__navigationhiding`
--
ALTER TABLE `pma__navigationhiding`
  ADD PRIMARY KEY (`username`,`item_name`,`item_type`,`db_name`,`table_name`);

--
-- Indexes for table `pma__pdf_pages`
--
ALTER TABLE `pma__pdf_pages`
  ADD PRIMARY KEY (`page_nr`),
  ADD KEY `db_name` (`db_name`);

--
-- Indexes for table `pma__recent`
--
ALTER TABLE `pma__recent`
  ADD PRIMARY KEY (`username`);

--
-- Indexes for table `pma__relation`
--
ALTER TABLE `pma__relation`
  ADD PRIMARY KEY (`master_db`,`master_table`,`master_field`),
  ADD KEY `foreign_field` (`foreign_db`,`foreign_table`);

--
-- Indexes for table `pma__savedsearches`
--
ALTER TABLE `pma__savedsearches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `u_savedsearches_username_dbname` (`username`,`db_name`,`search_name`);

--
-- Indexes for table `pma__table_coords`
--
ALTER TABLE `pma__table_coords`
  ADD PRIMARY KEY (`db_name`,`table_name`,`pdf_page_number`);

--
-- Indexes for table `pma__table_info`
--
ALTER TABLE `pma__table_info`
  ADD PRIMARY KEY (`db_name`,`table_name`);

--
-- Indexes for table `pma__table_uiprefs`
--
ALTER TABLE `pma__table_uiprefs`
  ADD PRIMARY KEY (`username`,`db_name`,`table_name`);

--
-- Indexes for table `pma__tracking`
--
ALTER TABLE `pma__tracking`
  ADD PRIMARY KEY (`db_name`,`table_name`,`version`);

--
-- Indexes for table `pma__userconfig`
--
ALTER TABLE `pma__userconfig`
  ADD PRIMARY KEY (`username`);

--
-- Indexes for table `pma__usergroups`
--
ALTER TABLE `pma__usergroups`
  ADD PRIMARY KEY (`usergroup`,`tab`,`allowed`);

--
-- Indexes for table `pma__users`
--
ALTER TABLE `pma__users`
  ADD PRIMARY KEY (`username`,`usergroup`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `pma__bookmark`
--
ALTER TABLE `pma__bookmark`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pma__column_info`
--
ALTER TABLE `pma__column_info`
  MODIFY `id` int(5) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pma__export_templates`
--
ALTER TABLE `pma__export_templates`
  MODIFY `id` int(5) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pma__history`
--
ALTER TABLE `pma__history`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pma__pdf_pages`
--
ALTER TABLE `pma__pdf_pages`
  MODIFY `page_nr` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pma__savedsearches`
--
ALTER TABLE `pma__savedsearches`
  MODIFY `id` int(5) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- Database: `test`
--
CREATE DATABASE IF NOT EXISTS `test` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `test`;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

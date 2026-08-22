-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 22, 2026 at 03:52 PM
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

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `booking_id` int(11) NOT NULL,
  `request_code` varchar(20) NOT NULL,
  `renter_id` int(11) NOT NULL,
  `equipment_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `total_days` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `advance_amount` decimal(10,2) NOT NULL,
  `remaining_cod` decimal(10,2) NOT NULL,
  `id_proof_doc` varchar(255) NOT NULL,
  `status` enum('Pending','Accepted','Rejected','Delivered','Returned','Overdue') DEFAULT 'Pending',
  `late_charge` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `is_featured` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `equipment`
--

INSERT INTO `equipment` (`equipment_id`, `category_id`, `lender_id`, `title`, `category`, `brand_model`, `power_hp`, `drive_type`, `model_year`, `fuel_type`, `working_width`, `equipment_condition`, `price_per_day`, `min_booking_days`, `service_location`, `distance_km`, `description`, `image`, `badge`, `status`, `rating`, `rating_count`, `created_at`, `is_featured`) VALUES
(1, 1, 7, 'tractor', 'Tractor', 'vg gr', 23, '4WD', 2026, 'Diesel', '', '', 443.00, 1, 'Bengaluru Rural, Tumakuru', 25.0, 'gbb', '1787390934_5282.png', '', 'Available', 0.0, 0, '2026-08-22 09:28:54', 1),
(2, 1, 7, 'rere', 'Tractor', 're fr', 6, '4WD', 2026, 'Diesel', '', 'Good', 3.00, 144, 'Bengaluru Rural', 25.0, 'sdf', '', '', 'Available', 0.0, 0, '2026-08-22 09:53:34', 1);

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `items`
--

INSERT INTO `items` (`item_id`, `lender_id`, `title`, `category`, `brand`, `model`, `year_of_purchase`, `item_condition`, `description`, `price_per_day`, `security_deposit`, `min_rental_days`, `max_rental_days`, `status`, `service_areas`, `fuel_type`, `power_hp`, `working_hours`, `is_recommended`, `image`, `created_at`) VALUES
(1, 7, 'tractor', 'Tractor', 'vg', 'gr', 2026, 'Excellent', 'gbb', 443.00, 44.00, 1, 8, 'Available', 'Bengaluru Rural, Tumakuru', 'Diesel', 23, 3, 1, '1787390934_5282.png', '2026-08-22 09:28:54'),
(2, 7, 'rere', 'Tractor', 're', 'fr', 2026, 'Good', 'sdf', 3.00, 45.00, 144, 51, 'Available', 'Bengaluru Rural', 'Diesel', 6, 6, 1, '', '2026-08-22 09:53:34');

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
(5, 'pragathi', 'pragathikt29@gmail.com', '9845442268', '$2y$10$zibi4oNs8WechG/OzlMmu.DZzv88c4yMmw3DwoLoo8YdK8fVEDrHa', 'renter', 'birth_city', 'arsikere', 'hassan karnataka', 'default_avatar.png', NULL, NULL, NULL, NULL),
(6, 'Tejomurthy', 'pragathikt2@gmail.com', '123456789', '$2y$10$KnT2yCXV.txkoTz.wQpyVeOqVj4PxHK4gRgfGwjYjM.bC3evG9zOy', 'lender', 'first_school', 'kodhialli school', 'mysuru', 'default_avatar.png', NULL, NULL, NULL, NULL),
(7, 'Tejomurthy', 'pragathikt4@gmail.com', '7975865577', '$2y$10$atpaFVFLYvVlHz.Vuv4MY.XxRqJntVb6T/ZWlI7qmMzYQMnwP4kz.', 'lender', 'first_school', 'government school', 'mysuru', 'default_avatar.png', NULL, NULL, NULL, NULL);

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
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `equipment`
--
ALTER TABLE `equipment`
  MODIFY `equipment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `items`
--
ALTER TABLE `items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

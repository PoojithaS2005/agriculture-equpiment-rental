-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 04, 2026 at 04:29 PM
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
  `phone_number` varchar(15) DEFAULT NULL,
  `equipment_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `delivery_date` date DEFAULT NULL,
  `total_days` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `total_amount` decimal(10,2) NOT NULL,
  `advance_amount` decimal(10,2) NOT NULL,
  `remaining_cod` decimal(10,2) NOT NULL,
  `id_proof_doc` varchar(255) NOT NULL,
  `id_number` varchar(50) DEFAULT NULL,
  `delivery_address` text NOT NULL,
  `status` enum('Pending','Accepted','Rejected','Delivered','Returned','Completed','Overdue','Cancelled') NOT NULL DEFAULT 'Pending',
  `late_charge` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `delivery_confirmed` tinyint(1) NOT NULL DEFAULT 0,
  `delivery_confirmed_at` datetime DEFAULT NULL,
  `return_confirmed` tinyint(1) NOT NULL DEFAULT 0,
  `return_confirmed_at` datetime DEFAULT NULL,
  `reminder_notification_sent_at` datetime DEFAULT NULL,
  `overdue_notification_sent_at` datetime DEFAULT NULL,
  `late_charge_collected` tinyint(1) NOT NULL DEFAULT 0,
  `late_charge_collected_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `late_charge_collected_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`booking_id`, `request_code`, `renter_id`, `phone_number`, `equipment_id`, `start_date`, `end_date`, `delivery_date`, `total_days`, `quantity`, `total_amount`, `advance_amount`, `remaining_cod`, `id_proof_doc`, `id_number`, `delivery_address`, `status`, `late_charge`, `created_at`, `delivery_confirmed`, `delivery_confirmed_at`, `return_confirmed`, `return_confirmed_at`, `reminder_notification_sent_at`, `overdue_notification_sent_at`, `late_charge_collected`, `late_charge_collected_amount`, `late_charge_collected_at`) VALUES
(1, 'REQ-5B1E2E', 5, '9845442268', 1, '2026-08-31', '2026-09-01', NULL, 2, 1, 886.00, 44.00, 842.00, 'ID_5_1788010293_bd7d4257.png', '124546556778', 'hassan karnataka', 'Completed', 0.00, '2026-08-29 13:31:33', 1, '2026-09-18 21:33:25', 1, '2026-09-18 21:34:15', NULL, NULL, 0, 0.00, NULL),
(2, 'REQ-11B333', 5, '9845442268', 1, '2026-08-29', '2026-08-29', NULL, 1, 1, 443.00, 44.00, 399.00, 'ID_5_1788016865_509ef17b.png', 'er3ry65u6u7i78', 'hassan karnataka', 'Rejected', 0.00, '2026-08-29 15:21:05', 0, NULL, 0, NULL, NULL, NULL, 0, 0.00, NULL),
(3, 'REQ-3CC9E6', 5, '9987653421', 1, '2026-08-30', '2026-08-30', NULL, 1, 1, 443.00, 44.00, 399.00, 'ID_5_1788098739_92b27e1f.png', '868545', 'hassan karnataka', 'Completed', 0.00, '2026-08-30 14:05:39', 0, NULL, 1, '2026-09-11 20:28:33', NULL, NULL, 0, 0.00, NULL),
(7, 'REQ-25DD12', 14, '09845442268', 20, '2026-09-26', '2026-09-30', NULL, 5, 1, 6500.00, 0.00, 6500.00, 'ID_14_1790399314_0cf9e3b1.png', '0987654321', 'Mysuru,Musuru,Karnataka', 'Completed', 1000.00, '2026-09-26 05:08:34', 0, NULL, 1, '2026-10-12 19:51:15', NULL, '2026-10-10 16:38:13', 0, 0.00, NULL),
(8, 'REQ-903D82', 27, '9000000005', 29, '2026-10-31', '2026-11-01', '2026-10-30', 2, 1, 3000.00, 2000.00, 1000.00, 'ID_27_1793372025_076791c3.png', '1234567890', 'Devanahalli, Bengaluru Rural', 'Completed', 0.00, '2026-10-30 14:53:45', 1, '2026-10-30 20:30:41', 1, '2026-10-30 20:31:13', NULL, NULL, 0, 0.00, NULL),
(9, 'REQ-B3221F', 5, '9000000006', 10, '2026-11-01', '2026-11-03', NULL, 3, 1, 1500.00, 0.00, 1500.00, 'ID_5_1793372779_c5e7df8f.png', '1234567890', 'Hassan Karnataka', 'Cancelled', 0.00, '2026-10-30 15:06:19', 0, NULL, 0, NULL, NULL, NULL, 0, 0.00, NULL),
(10, 'REQ-4C4989', 5, '9000000006', 38, '2026-11-02', '2026-11-03', NULL, 2, 1, 700.00, 150.00, 550.00, 'ID_5_1793372932_08d7c73b.png', '1234567890', 'Hassan Karnataka', 'Rejected', 0.00, '2026-10-30 15:08:52', 0, NULL, 0, NULL, NULL, NULL, 0, 0.00, NULL),
(11, 'REQ-346F61', 27, '9000000006', 33, '2026-11-04', '2026-11-05', NULL, 2, 1, 6000.00, 1500.00, 4500.00, 'ID_27_1793373075_eff10fa5.png', '1234567890', 'Devanahalli, Bengaluru Rural', 'Cancelled', 0.00, '2026-10-30 15:11:15', 0, NULL, 0, NULL, NULL, NULL, 0, 0.00, NULL),
(12, 'REQ-3A2341', 27, '9000000006', 35, '2026-10-30', '2026-10-31', NULL, 2, 1, 2000.00, 400.00, 1600.00, 'ID_27_1793373187_ae98d7cd.png', '1234567890', 'Devanahalli, Bengaluru Rural', 'Rejected', 0.00, '2026-10-30 15:13:07', 0, NULL, 0, NULL, NULL, NULL, 0, 0.00, NULL),
(13, 'REQ-A5EB50', 27, '9000000006', 47, '2026-10-31', '2026-11-02', '2026-10-31', 3, 1, 3000.00, 400.00, 2600.00, 'ID_27_1793373482_eeb7b63a.png', '1234567890', 'Devanahalli, Bengaluru Rural', 'Completed', 0.00, '2026-10-30 15:18:02', 1, '2026-10-30 20:49:01', 1, '2026-10-30 20:49:34', NULL, NULL, 0, 0.00, NULL),
(14, 'REQ-09B031', 27, '9000000006', 34, '2026-11-03', '2026-11-05', '2026-11-02', 3, 1, 7500.00, 1200.00, 6300.00, 'ID_27_1793373616_b39c6448.png', '1234567890', 'Devanahalli, Bengaluru Rural', 'Returned', 0.00, '2026-10-30 15:20:16', 1, '2026-10-30 21:05:16', 0, NULL, NULL, NULL, 0, 0.00, NULL),
(15, 'REQ-31FA5D', 27, '9000000006', 32, '2026-10-30', '2026-10-31', '2026-11-02', 2, 1, 4400.00, 1500.00, 2900.00, 'ID_27_1793373683_c861fe91.png', '1234567890', 'Devanahalli, Bengaluru Rural', 'Completed', 0.00, '2026-10-30 15:21:23', 1, '2026-11-02 20:33:59', 1, '2026-11-02 20:34:34', NULL, NULL, 0, 0.00, NULL),
(16, 'REQ-CC417E', 5, '9000000006', 31, '2026-10-30', '2026-11-04', NULL, 6, 1, 10800.00, 1400.00, 9400.00, 'ID_5_1793373836_7bf2218c.png', '1234567890', 'Hassan Karnataka', 'Pending', 0.00, '2026-10-30 15:23:56', 0, NULL, 0, NULL, NULL, NULL, 0, 0.00, NULL),
(17, 'REQ-D3F59B', 27, '9000000006', 46, '2026-10-30', '2026-11-04', '2026-10-31', 6, 1, 4800.00, 300.00, 4500.00, 'ID_27_1793373885_36e7234c.png', '1234567890', 'Devanahalli, Bengaluru Rural', 'Delivered', 0.00, '2026-10-30 15:24:45', 1, '2026-11-02 19:46:17', 0, NULL, '2026-11-03 12:03:52', NULL, 0, 0.00, NULL),
(18, 'REQ-C3F816', 5, '9000000006', 49, '2026-10-30', '2026-10-31', NULL, 2, 1, 600.00, 100.00, 500.00, 'ID_5_1793374396_65bb0d0c.png', '1234567890', 'Hassan Karnataka', 'Pending', 0.00, '2026-10-30 15:33:16', 0, NULL, 0, NULL, NULL, NULL, 0, 0.00, NULL),
(19, 'REQ-E47C06', 27, '6767987806', 39, '2026-11-02', '2026-11-02', '2026-11-02', 1, 1, 1200.00, 500.00, 700.00, 'ID_27_1793634942_68c7e725.png', '123456789', 'Devanahalli, Bengaluru Rural', 'Overdue', 100.00, '2026-11-02 15:55:42', 1, '2026-11-02 21:26:33', 0, NULL, NULL, '2026-11-03 12:03:52', 0, 0.00, NULL);

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
  `security_deposit` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_quantity` int(11) NOT NULL DEFAULT 1,
  `min_booking_days` int(11) DEFAULT 1,
  `max_booking_days` int(11) DEFAULT 30,
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

INSERT INTO `equipment` (`equipment_id`, `category_id`, `lender_id`, `title`, `category`, `brand_model`, `power_hp`, `drive_type`, `model_year`, `fuel_type`, `working_width`, `equipment_condition`, `price_per_day`, `security_deposit`, `total_quantity`, `min_booking_days`, `max_booking_days`, `service_location`, `distance_km`, `description`, `image`, `badge`, `status`, `rating`, `rating_count`, `created_at`, `is_featured`, `latitude`, `longitude`) VALUES
(1, 1, 7, 'tractor', 'Tractor', 'vg gr', 23, '4WD', 2026, 'Diesel', '', '', 443.00, 44.00, 1, 1, 8, 'Bengaluru Rural, Tumakuru', 25.0, 'gbb', '1787390934_5282.png', '', 'Available', 5.0, 1, '2026-08-22 09:28:54', 1, NULL, NULL),
(4, 2, 7, 'John Deere Combine Harvester', 'Harvesting', 'john Deere 9500 john Deere 9500', 34, '4WD', 2022, 'Diesel', '', 'Good', 8000.00, 2000.00, 1, 1, 4, 'Bengaluru Rural, Hassan Karnataka', 25.0, 'John Deere Combine Harvester is a powerful and efficient harvesting machine designed for quickly harvesting crops such as wheat, rice, and other grains. It combines cutting, threshing, separating, and collecting operations in a single machine. It is suitable for large agricultural fields and helps reduce harvesting time and manual labor.', '1787821036_5572.webp', '', 'Rented Out', 0.0, 0, '2026-08-27 08:57:16', 1, NULL, NULL),
(5, 1, 7, 'Tractor', 'Tractor', 'Mahindra OJA 2121  4', 21, '4WD', 2026, 'Diesel', '', 'New', 2000.00, 0.00, 1, 1, 30, 'Hassan', 0.0, 'Mahindra OJA 2121 4WD Steering type is power steering. \r\nIt has 12 forward + 12 reverse gears.\r\nIt has a  strong loading capacity of 950 kg. \r\nIt has a  unique, stylish modern body design and comes with LED headlamps. \r\nIt has a large fuel tank capacity, which allows farmers to work long hours on farms.', '1788524959_Mahindra--OJA-21211735718187_elQi6fl2s9.webp', 'RECOMMENDED', 'Available', 0.0, 0, '2026-09-04 12:25:35', 1, 13.22720000, 77.57400000),
(7, 2, 9, 'Mahindra Combine Harvester', 'Harvesting', 'Mahindra 6050', 60, '4WD', 2024, 'Diesel', '5.5 m', 'Good', 5000.00, 0.00, 2, 1, 30, 'Bengaluru, Anekal, Mysuru', 25.0, 'Combine harvester suitable for agricultural harvesting work.', 'default.png', 'NONE', 'Available', 0.0, 0, '2026-09-23 14:58:50', 0, NULL, NULL),
(8, 4, 9, 'Mahindra Rotavator', 'Tillage', 'Mahindra 7 Feet', 50, '4WD', 2024, 'Diesel', '7 Feet', 'Good', 1800.00, 0.00, 3, 1, 30, 'Bengaluru, Mysuru', 25.0, 'Rotavator suitable for soil preparation and tillage operations.', 'default.png', 'NONE', 'Available', 0.0, 0, '2026-09-23 14:58:50', 0, NULL, NULL),
(9, 5, 9, 'Mahindra Seed Drill', 'Seeding', 'Mahindra 9 Tine', 40, '4WD', 2024, 'Diesel', '9 Tine', 'Good', 1200.00, 0.00, 2, 1, 30, 'Bengaluru', 25.0, 'Seed drill suitable for agricultural seeding work.', 'default.png', 'NONE', 'Available', 0.0, 0, '2026-09-23 14:58:50', 0, NULL, NULL),
(10, 6, 9, 'Battery Knapsack Sprayer', 'Spraying', 'Kisan Kraft KK-BK-20', NULL, 'Manual', 2024, 'Battery', '20 L', 'Good', 500.00, 0.00, 5, 1, 30, 'Bengaluru, Bengaluru Urban', 25.0, 'Battery powered knapsack sprayer for agricultural spraying.', 'default.png', 'NONE', 'Available', 0.0, 0, '2026-09-23 14:58:50', 0, NULL, NULL),
(11, 3, 9, 'Portable Water Pump', 'Irrigation', 'Kirloskar 5HP', 5, 'Portable', 2024, 'Diesel', '', 'Good', 1000.00, 0.00, 3, 1, 30, 'Bengaluru', 25.0, 'Portable water pump suitable for agricultural irrigation.', 'default.png', 'NONE', 'Available', 0.0, 0, '2026-09-23 14:58:50', 0, NULL, NULL),
(12, 1, 9, 'Mahindra Tractor', 'Tractor', 'Mahindra 575 DI', 47, '4WD', 2024, 'Diesel', '', 'Good', 2500.00, 0.00, 2, 1, 30, 'Bengaluru, Bengaluru Urban', 25.0, 'Mahindra tractor suitable for general agricultural operations.', 'default.png', 'NONE', 'Available', 0.0, 0, '2026-09-23 14:58:50', 0, NULL, NULL),
(13, 2, 10, 'John Deere Harvester', 'Harvesting', 'John Deere W70', 70, '4WD', 2024, 'Diesel', '5 m', 'Good', 4500.00, 0.00, 2, 1, 30, 'Bengaluru, Anekal, Bengaluru Urban', 25.0, 'John Deere harvester suitable for agricultural harvesting.', 'default.png', 'NONE', 'Available', 0.0, 0, '2026-09-23 14:58:50', 0, NULL, NULL),
(14, 4, 10, 'Field King Disc Harrow', 'Tillage', 'Field King 8 Feet', 45, '4WD', 2024, 'Diesel', '8 Feet', 'Good', 1500.00, 0.00, 3, 1, 30, 'Bengaluru Urban, Anekal', 25.0, 'Disc harrow suitable for soil preparation.', 'default.png', 'NONE', 'Available', 0.0, 0, '2026-09-23 14:58:50', 0, NULL, NULL),
(15, 5, 10, 'John Deere Seed Planter', 'Seeding', 'John Deere 1590', 50, '4WD', 2024, 'Diesel', '9 Feet', 'Good', 1400.00, 0.00, 2, 1, 30, 'Bengaluru Urban, Bengaluru, Anekal', 25.0, 'Seed planter designed for accurate agricultural seeding.', 'default.png', 'NONE', 'Available', 0.0, 0, '2026-09-23 14:58:50', 0, NULL, NULL),
(16, 6, 10, 'Tractor Mounted Sprayer', 'Spraying', 'Neptune 600L', 50, '4WD', 2024, 'Diesel', '600 L', 'Good', 2200.00, 0.00, 2, 1, 30, 'Bengaluru, Bengaluru Urban, Anekal', 25.0, 'Tractor mounted sprayer for agricultural crop protection.', 'default.png', 'NONE', 'Available', 0.0, 0, '2026-09-23 14:58:50', 0, NULL, NULL),
(17, 3, 10, 'Solar Irrigation Pump', 'Irrigation', 'Shakti 3HP', 3, 'Portable', 2024, 'Solar', '', 'Good', 1800.00, 0.00, 3, 1, 30, 'Bengaluru Urban, Anekal', 25.0, 'Solar powered irrigation pump for agricultural use.', 'default.png', 'NONE', 'Available', 0.0, 0, '2026-09-23 14:58:50', 0, NULL, NULL),
(18, 1, 10, 'Swaraj Tractor', 'Tractor', 'Swaraj 744 FE', 48, '4WD', 2024, 'Diesel', '', 'Good', 2300.00, 0.00, 2, 1, 30, 'Bengaluru, Anekal, Bengaluru Urban', 25.0, 'Swaraj tractor suitable for agricultural operations.', 'default.png', 'NONE', 'Available', 0.0, 0, '2026-09-23 14:58:50', 0, NULL, NULL),
(19, 2, 11, 'Swaraj Harvester', 'Harvesting', 'Swaraj 8200', 80, '4WD', 2024, 'Diesel', '', 'Good', 4000.00, 0.00, 2, 1, 30, 'Bengaluru, Mysuru, Karnataka', 25.0, 'Swaraj harvester suitable for crop harvesting.', '1793203627_swaraj harvestor.webp', 'NONE', 'Available', 0.0, 0, '2026-09-23 14:58:50', 0, NULL, NULL),
(20, 5, 11, 'Shaktiman Seed Drill', 'Seeding', 'Shaktiman SRT', 45, '4WD', 2024, 'Diesel', '', 'Good', 1300.00, 0.00, 2, 1, 30, 'Karnataka', 25.0, 'Seed drill for efficient crop seeding.', '1793203527_shaktiman seed drill.jpg', 'NONE', 'Available', 0.0, 0, '2026-09-23 14:58:50', 0, NULL, NULL),
(21, 3, 11, 'Diesel Water Pump', 'Irrigation', 'Kirloskar 7.5HP', 7, 'Portable', 2024, 'Diesel', '3', 'Good', 1500.00, 600.00, 3, 1, 10, 'Karnataka', 25.0, 'Diesel water pump suitable for agricultural irrigation.', '1793203434_diesal water pump.avif', 'NONE', 'Available', 0.0, 0, '2026-09-23 14:58:50', 0, NULL, NULL),
(22, 1, 11, 'John Deere Tractor', 'Tractor', 'John Deere 5310', 55, '4WD', 2024, 'Diesel', '', 'Good', 2800.00, 1400.00, 2, 3, 30, 'Karnataka,Hassan,Mysuru', 0.0, 'John Deere tractor suitable for agricultural operations.', '1793203315_john derre tractor.webp', 'NONE', 'Available', 0.0, 0, '2026-09-23 14:58:50', 1, NULL, NULL),
(23, 4, 12, 'John Deere Cultivator', 'Tillage', 'John Deere 6205', 50, '4WD', 2024, 'Diesel', '6 Feet', 'Good', 1600.00, 0.00, 3, 1, 30, 'Bengaluru, Hassan, Karnataka', 25.0, 'Cultivator suitable for soil preparation and tillage.', 'default.png', 'NONE', 'Available', 0.0, 0, '2026-09-23 14:58:50', 0, NULL, NULL),
(24, 6, 12, 'Power Sprayer', 'Spraying', 'Kisan Kraft KK-PS50', 5, 'Portable', 2024, 'Petrol', '', 'Good', 900.00, 0.00, 4, 1, 30, 'Bengaluru, Hassan', 25.0, 'Power sprayer suitable for agricultural spraying operations.', 'default.png', 'NONE', 'Available', 0.0, 0, '2026-09-23 14:58:50', 0, NULL, NULL),
(25, 1, 12, 'New Holland Tractor', 'Tractor', 'New Holland 3630', 55, '4WD', 2024, 'Diesel', '', 'Good', 2600.00, 0.00, 2, 1, 30, 'Bengaluru, Hassan', 25.0, 'New Holland tractor suitable for general agricultural operations.', 'default.png', 'NONE', 'Available', 0.0, 0, '2026-09-23 14:58:50', 0, NULL, NULL),
(27, 6, 11, 'Agricultural Power Sprayer', 'Spraying', 'Honda GX35 Power Sprayer', 7, '4WD', 2026, 'Diesel', '', 'Good', 350.00, 150.00, 1, 1, 30, 'Bengaluru Rural, mysuru, bengaluru', 25.0, '	Agricultural power sprayer suitable for spraying pesticides, insecticides, and other crop treatments. Ideal for farms, gardens, and agricultural fields. Easy to operate and suitable for routine crop maintenance.', '1793203885_9961.jpg', 'RECOMMENDED', 'Available', 0.0, 0, '2026-10-28 16:11:25', 1, NULL, NULL),
(28, 4, 11, 'Rotavator', 'Tillage', 'Mahindra Rotary Tiller', 7, '4WD', 2026, 'Diesel', '', 'New', 850.00, 400.00, 3, 1, 9, 'Bengaluru Rural, Tumakuru, hassan', 25.0, 'Agricultural rotavator used for tilling soil, breaking soil clumps, and preparing fields for sowing. Suitable for routine farm soil preparation.', '1793204077_3713.jpg', 'NONE', 'Available', 0.0, 0, '2026-10-28 16:14:37', 0, NULL, NULL),
(29, 1, 26, 'Mahindra 575 DI Tractor	', 'Tractor', 'Mahindra 575 DI', 45, '4WD', 2023, 'Diesel', '', 'Good', 1500.00, 2000.00, 1, 1, 7, 'Bengaluru Rural, Kolar, Tumakuru, Mysuru, Hassan', 25.0, 'Mahindra 575 DI tractor suitable for ploughing, cultivation, soil preparation, and general agricultural activities. Well-maintained and available for daily rental.', '1793368847_7603.webp', 'NONE', 'Available', 4.0, 1, '2026-10-30 14:00:47', 0, NULL, NULL),
(30, 1, 26, 'John Deere 5050D Tractor', 'Tractor', 'John Deere 5050D', 45, '4WD', 2022, 'Diesel', '', 'Good', 1800.00, 1000.00, 3, 1, 7, 'Bengaluru Rural, Bengaluru Urban, Mysuru', 25.0, 'John Deere 5050D tractor suitable for ploughing, cultivation, transportation, and general agricultural operations.', '1793369120_6560.jpg', 'RECOMMENDED', 'Available', 0.0, 0, '2026-10-30 14:05:20', 1, NULL, NULL),
(31, 1, 26, 'Massey Ferguson 241 DI Tractor', 'Tractor', 'Massey Ferguson 241 DI', 50, '4WD', 2022, 'Diesel', '', 'Good', 1800.00, 1400.00, 1, 1, 7, 'Bengaluru Urban, Kolar, Tumakuru, Hassan', 25.0, 'Massey Ferguson 241 DI tractor for cultivation, soil preparation, and general farm operations.', '1793369374_4928.avif', 'NONE', 'Available', 0.0, 0, '2026-10-30 14:09:34', 0, NULL, NULL),
(32, 1, 26, 'New Holland 3630 Tractor', 'Tractor', 'New Holland 3630', 45, '4WD', 2026, 'Diesel', '', 'Good', 2200.00, 1500.00, 1, 1, 2, 'Bengaluru Urban, Kolar, Tumakuru, mysuru, Hassan', 25.0, 'New Holland 3630 tractor designed for field preparation, cultivation, and heavy agricultural activities.', '1793369544_9993.webp', 'NONE', 'Available', 0.0, 0, '2026-10-30 14:12:24', 0, NULL, NULL),
(33, 2, 26, 'Paddy Harvester', 'Harvesting', 'Kubota Paddy Harvester', 35, '4WD', 2023, 'Diesel', '', 'Good', 3000.00, 1500.00, 1, 1, 7, 'Bengaluru Rural, Hassan', 25.0, 'Paddy harvesting machine for cutting and collecting rice crops efficiently.', '1793371829_harvestor.jpg', 'NONE', 'Rented Out', 0.0, 0, '2026-10-30 14:26:07', 1, NULL, NULL),
(34, 2, 26, 'Mini Combine Harvester', 'Harvesting', 'Mahindra Mini Combine', 30, '4WD', 2024, 'Diesel', '1.2 m', 'Good', 2500.00, 1200.00, 1, 1, 7, 'Mysuru, Hassan', 25.0, 'Compact combine harvester suitable for harvesting crops on small and medium-sized farms.', 'default.png', 'NONE', 'Available', 0.0, 0, '2026-10-30 14:26:07', 0, NULL, NULL),
(35, 2, 26, 'Power Reaper', 'Harvesting', 'Kisan Kraft Power Reaper', 7, 'Portable', 2023, 'Petrol', '1.0 m', 'Used', 1000.00, 400.00, 1, 1, 7, 'Tumakuru, Mysuru', 25.0, 'Power-operated reaper for cutting standing crops during the harvesting season.', 'default.png', 'NONE', 'Available', 0.0, 0, '2026-10-30 14:26:07', 0, NULL, NULL),
(36, 6, 26, 'Battery Sprayer', 'Spraying', 'Kisan Kraft Battery Sprayer', 0, 'Portable', 2024, 'Battery', '', 'Good', 250.00, 100.00, 1, 1, 7, 'Bengaluru Rural, Tumakuru', 25.0, 'Rechargeable battery sprayer for applying suitable agricultural crop treatments.', '1793371773_kisan-bettery-sprayer-1000x1000.webp', 'NONE', 'Available', 0.0, 0, '2026-10-30 14:26:07', 0, NULL, NULL),
(37, 6, 26, 'Knapsack Sprayer', 'Spraying', 'Manual Knapsack Sprayer', 0, 'Manual', 2023, 'Manual', '', 'Good', 150.00, 50.00, 1, 1, 7, 'Hassan, Mysuru', 25.0, 'Hand-operated backpack sprayer for applying agricultural liquids to crops.', '1793371727_Agricultural-Manual-and-Electric-Knapsack-Sprayer.avif', 'NONE', 'Available', 0.0, 0, '2026-10-30 14:26:07', 0, NULL, NULL),
(38, 6, 26, 'Mist Blower Sprayer', 'Spraying', 'Kisan Kraft Mist Blower', 3, 'Portable', 2024, 'Petrol', '', 'Good', 350.00, 150.00, 1, 1, 7, 'Tumakuru, Hassan', 25.0, 'Portable mist blower for crop treatment in suitable agricultural fields.', '1793371672_Sprayer.jpg', 'NONE', 'Available', 0.0, 0, '2026-10-30 14:26:07', 1, NULL, NULL),
(39, 3, 26, 'Diesel Water Pump', 'Irrigation', 'Kirloskar Diesel Pump', 7, 'Portable', 2023, 'Diesel', '', 'Good', 1200.00, 500.00, 1, 1, 7, 'Bengaluru Rural, Mysuru', 25.0, 'Diesel-powered water pump for transferring water to agricultural fields.', '1793371600_maxresdefault.jpg', 'NONE', 'Available', 0.0, 0, '2026-10-30 14:26:07', 1, NULL, NULL),
(40, 3, 26, 'Portable Water Pump', 'Irrigation', 'Kirloskar Portable Pump', 5, 'Portable', 2024, 'Diesel', '', 'Good', 800.00, 300.00, 1, 1, 7, 'Hassan, Tumakuru', 25.0, 'Portable irrigation pump suitable for moving water from a water source to farm fields.', '1793371512_kirloskar-portable-fire-pump-1000x1000.webp', 'NONE', 'Available', 0.0, 0, '2026-10-30 14:26:07', 0, NULL, NULL),
(41, 3, 26, 'Solar Irrigation Pump', 'Irrigation', 'Shakti Solar Pump', 3, 'Portable', 2024, 'Solar', '', 'Good', 1500.00, 500.00, 1, 1, 7, 'Mysuru, Hassan', 25.0, 'Solar-powered irrigation pump designed to support agricultural water supply.', '1793371451_10-hp-shakti-solar-pumping-system-250x250.webp', 'NONE', 'Available', 0.0, 0, '2026-10-30 14:26:07', 0, NULL, NULL),
(42, 3, 26, 'Sprinkler Irrigation Set', 'Irrigation', 'Agricultural Sprinkler Set', 0, 'Portable', 2024, 'Manual', '', 'Good', 500.00, 200.00, 1, 1, 7, 'Tumakuru, Mysuru', 25.0, 'Portable sprinkler equipment for distributing water across suitable agricultural plots.', '1793371403_sprinker.jpg', 'NONE', 'Available', 0.0, 0, '2026-10-30 14:26:07', 0, NULL, NULL),
(43, 4, 26, 'Disc Plough', 'Tillage', 'Field King Disc Plough', 45, '3 Point Linkage', 2023, 'Diesel', '', 'Good', 900.00, 400.00, 1, 1, 7, 'Bengaluru Rural, Hassan', 25.0, 'Disc plough for breaking and turning soil before planting crops.', '1793371334_disc plough.webp', 'NONE', 'Available', 0.0, 0, '2026-10-30 14:26:07', 0, NULL, NULL),
(44, 4, 26, 'Cultivator', 'Tillage', 'Mahindra Agricultural Cultivator', 45, '3 Point Linkage', 2024, 'Diesel', '', 'Good', 700.00, 300.00, 1, 1, 7, 'Mysuru, Tumakuru', 25.0, 'Cultivator for loosening soil, preparing seedbeds, and controlling weeds.', '1793371262_mahindra-heavy-duty-cultivator-1000x1000.webp', 'NONE', 'Available', 0.0, 0, '2026-10-30 14:26:07', 1, NULL, NULL),
(45, 4, 26, 'Disc Harrow', 'Tillage', 'Field King Disc Harrow', 40, '3 Point Linkage', 2023, 'Diesel', '', 'Used', 650.00, 250.00, 1, 1, 7, 'Hassan, Mysuru', 0.0, 'Disc harrow for breaking soil clods and preparing fields for cultivation.', '1793371209_tillaged.webp', 'NONE', 'Available', 0.0, 0, '2026-10-30 14:26:07', 1, NULL, NULL),
(46, 5, 26, 'Seed Drill Machine', 'Seeding', 'Mahindra Seed Drill', 40, '3 Point Linkage', 2024, 'Diesel', '', 'Good', 800.00, 300.00, 1, 1, 7, 'Bengaluru Rural, Tumakuru', 25.0, 'Seed drill for placing seeds in rows at suitable depths and spacing.', '1793371099_Project-Report-For-Seed-drill-Machine-r8emi4jlj58wfx4s759lsqcvtaa83j1zfl39d737q0.jpeg', 'NONE', 'Available', 0.0, 0, '2026-10-30 14:26:07', 0, NULL, NULL),
(47, 5, 26, 'Multi Crop Planter', 'Seeding', 'Agricultural Multi Crop Planter', 40, '3 Point Linkage', 2023, 'Diesel', '', 'Good', 1000.00, 400.00, 1, 1, 7, 'Hassan, Mysuru', 25.0, 'Multi-crop planter for sowing suitable crop seeds with consistent spacing.', '1793371026_dsr-multi-crop-planter-1000x1000.webp', 'NONE', 'Available', 0.0, 0, '2026-10-30 14:26:07', 0, NULL, NULL),
(48, 5, 26, 'Maize Seed Planter', 'Seeding', 'Agricultural Maize Planter', 45, '3 Point Linkage', 2024, 'Diesel', '', 'Good', 900.00, 350.00, 1, 1, 7, 'Tumakuru, Hassan', 25.0, 'Seed planter designed to place maize seeds in rows at suitable intervals.', '1793370939_Maize seed drill.jpg', 'NONE', 'Available', 0.0, 0, '2026-10-30 14:26:07', 0, NULL, NULL),
(49, 5, 26, 'Manual Seed Drill', 'Seeding', 'Manual Agricultural Seed Drill', 45, 'Manual', 2023, 'Manual', NULL, 'Good', 499.00, 100.00, 1, 1, 7, 'Mysuru, Tumakuru', 25.0, 'Manually operated seed drill for sowing seeds in small agricultural plots.', '1793370875_animal-automatic-seed-drill-machine-1000x1000.jpg', 'NONE', 'Available', 0.0, 0, '2026-10-30 14:26:07', 0, NULL, NULL);

--
-- Triggers `equipment`
--
DELIMITER $$
CREATE TRIGGER `equipment_price_history_after_update` AFTER UPDATE ON `equipment` FOR EACH ROW INSERT INTO `equipment_price_history`
  (`equipment_id`, `lender_id`, `old_price`, `new_price`, `change_type`, `changed_at`)
SELECT NEW.`equipment_id`, NEW.`lender_id`, OLD.`price_per_day`, NEW.`price_per_day`, 'Price Change', CURRENT_TIMESTAMP
WHERE NOT (OLD.`price_per_day` <=> NEW.`price_per_day`)
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `equipment_price_history`
--

CREATE TABLE `equipment_price_history` (
  `history_id` int(11) NOT NULL,
  `equipment_id` int(11) NOT NULL,
  `lender_id` int(11) NOT NULL,
  `old_price` decimal(10,2) DEFAULT NULL,
  `new_price` decimal(10,2) NOT NULL,
  `change_type` enum('Initial','Price Change') NOT NULL DEFAULT 'Price Change',
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `equipment_price_history`
--

INSERT INTO `equipment_price_history` (`history_id`, `equipment_id`, `lender_id`, `old_price`, `new_price`, `change_type`, `changed_at`) VALUES
(1, 1, 7, NULL, 443.00, 'Initial', '2026-08-22 09:28:54'),
(2, 3, 7, NULL, 44.00, 'Initial', '2026-09-03 14:17:28'),
(3, 4, 7, NULL, 8000.00, 'Initial', '2026-08-27 08:57:16'),
(4, 5, 7, NULL, 2000.00, 'Initial', '2026-09-04 12:25:35'),
(5, 6, 7, NULL, 700.00, 'Initial', '2026-09-22 15:31:07'),
(6, 7, 9, NULL, 5000.00, 'Initial', '2026-09-23 14:58:50'),
(7, 8, 9, NULL, 1800.00, 'Initial', '2026-09-23 14:58:50'),
(8, 9, 9, NULL, 1200.00, 'Initial', '2026-09-23 14:58:50'),
(9, 10, 9, NULL, 500.00, 'Initial', '2026-09-23 14:58:50'),
(10, 11, 9, NULL, 1000.00, 'Initial', '2026-09-23 14:58:50'),
(11, 12, 9, NULL, 2500.00, 'Initial', '2026-09-23 14:58:50'),
(12, 13, 10, NULL, 4500.00, 'Initial', '2026-09-23 14:58:50'),
(13, 14, 10, NULL, 1500.00, 'Initial', '2026-09-23 14:58:50'),
(14, 15, 10, NULL, 1400.00, 'Initial', '2026-09-23 14:58:50'),
(15, 16, 10, NULL, 2200.00, 'Initial', '2026-09-23 14:58:50'),
(16, 17, 10, NULL, 1800.00, 'Initial', '2026-09-23 14:58:50'),
(17, 18, 10, NULL, 2300.00, 'Initial', '2026-09-23 14:58:50'),
(18, 19, 11, NULL, 4000.00, 'Initial', '2026-09-23 14:58:50'),
(19, 20, 11, NULL, 1300.00, 'Initial', '2026-09-23 14:58:50'),
(20, 21, 11, NULL, 1500.00, 'Initial', '2026-09-23 14:58:50'),
(21, 22, 11, NULL, 2800.00, 'Initial', '2026-09-23 14:58:50'),
(22, 23, 12, NULL, 1600.00, 'Initial', '2026-09-23 14:58:50'),
(23, 24, 12, NULL, 900.00, 'Initial', '2026-09-23 14:58:50'),
(24, 25, 12, NULL, 2600.00, 'Initial', '2026-09-23 14:58:50'),
(25, 26, 16, NULL, 700.00, 'Initial', '2026-09-23 15:37:47'),
(26, 28, 11, -0.06, 850.00, 'Price Change', '2026-10-28 16:15:22'),
(27, 27, 11, NULL, 350.00, 'Initial', '2026-11-04 05:04:45'),
(28, 29, 26, NULL, 1500.00, 'Initial', '2026-11-04 05:04:45'),
(29, 30, 26, NULL, 1800.00, 'Initial', '2026-11-04 05:04:45'),
(30, 31, 26, NULL, 1800.00, 'Initial', '2026-11-04 05:04:45'),
(31, 32, 26, NULL, 2200.00, 'Initial', '2026-11-04 05:04:45'),
(32, 33, 26, NULL, 3000.00, 'Initial', '2026-11-04 05:04:45'),
(33, 34, 26, NULL, 2500.00, 'Initial', '2026-11-04 05:04:45'),
(34, 35, 26, NULL, 1000.00, 'Initial', '2026-11-04 05:04:45'),
(35, 36, 26, NULL, 250.00, 'Initial', '2026-11-04 05:04:45'),
(36, 37, 26, NULL, 150.00, 'Initial', '2026-11-04 05:04:45'),
(37, 38, 26, NULL, 350.00, 'Initial', '2026-11-04 05:04:45'),
(38, 39, 26, NULL, 1200.00, 'Initial', '2026-11-04 05:04:45'),
(39, 40, 26, NULL, 800.00, 'Initial', '2026-11-04 05:04:45'),
(40, 41, 26, NULL, 1500.00, 'Initial', '2026-11-04 05:04:45'),
(41, 42, 26, NULL, 500.00, 'Initial', '2026-11-04 05:04:45'),
(42, 43, 26, NULL, 900.00, 'Initial', '2026-11-04 05:04:45'),
(43, 44, 26, NULL, 700.00, 'Initial', '2026-11-04 05:04:45'),
(44, 45, 26, NULL, 650.00, 'Initial', '2026-11-04 05:04:45'),
(45, 46, 26, NULL, 800.00, 'Initial', '2026-11-04 05:04:45'),
(46, 47, 26, NULL, 1000.00, 'Initial', '2026-11-04 05:04:45'),
(47, 48, 26, NULL, 900.00, 'Initial', '2026-11-04 05:04:45'),
(48, 49, 26, NULL, 300.00, 'Initial', '2026-11-04 05:04:45'),
(58, 49, 26, 300.00, 499.00, 'Price Change', '2026-11-04 05:08:10');

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
(5, 7, 'Tractor', 'Tractor', 'Mahindra OJA 2121 ', '4', 2026, 'Excellent', 'Mahindra OJA 2121 4WD Steering type is power steering. \r\nIt has 12 forward + 12 reverse gears.\r\nIt has a  strong loading capacity of 950 kg. \r\nIt has a  unique, stylish modern body design and comes with LED headlamps. \r\nIt has a large fuel tank capacity, which allows farmers to work long hours on farms.', 2000.00, 1000.00, 1, 4, 'Available', 'Bengaluru Rural, Hassan, Mysuru, Mandya', 'Diesel', 21, 3, 1, '1788524735_7090.webp', '2026-09-04 12:25:35', NULL, NULL),
(6, 7, 'harvestor', 'Harvesting', 'rtt', '6', 2026, 'Good', 'rtyjj', 700.00, 350.00, 1, 4, 'Available', 'Bengaluru Rural, Kolar', 'Diesel', 45, 5, 1, '', '2026-09-22 15:31:07', NULL, NULL);

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

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `title`, `message`, `is_read`, `created_at`) VALUES
(1, 7, 'New Customer Review', 'A renter has submitted a new 5-star review for your equipment \"tractor\".', 1, '2026-09-18 15:45:51'),
(2, 5, 'Equipment Delivered', 'The lender has marked your equipment as delivered for booking REQ-5B1E2E. Please open your booking details and confirm delivery.', 1, '2026-09-18 16:02:55'),
(3, 5, 'Equipment Returned', 'The lender has marked your equipment as returned for booking REQ-5B1E2E. Please open your booking details and confirm the return.', 1, '2026-09-18 16:03:55'),
(8, 11, 'New Rental Request', ' Test Renter Mysuru has sent a rental request for Shaktiman Seed Drill. Request Code: REQ-25DD12.', 1, '2026-09-26 05:08:34'),
(9, 14, 'Equipment Delivered', 'The lender has marked your equipment as delivered for booking REQ-25DD12. Please open your booking details and confirm delivery.', 0, '2026-09-26 05:13:08'),
(10, 14, 'Rental Period Expired', 'Your rental period for Shaktiman Seed Drill has ended. Please return the equipment and contact your lender at 9000000003. Current overdue charge: ₹1000 for 10 day(s).', 0, '2026-10-10 11:08:13'),
(11, 11, 'Equipment Return Overdue', 'The rental period for Shaktiman Seed Drill has ended, but the equipment is still marked as unreturned. Please contact the renter at 9000000012. Current overdue charge: ₹1000.', 1, '2026-10-10 11:08:13'),
(12, 14, 'Equipment Returned', 'The lender has marked your equipment as returned for booking REQ-25DD12. Please open your booking details and confirm the return.', 1, '2026-10-12 14:19:32'),
(13, 26, 'New Rental Request', 'Manjunath has sent a rental request for Mahindra 575 DI Tractor	. Request Code: REQ-903D82.', 0, '2026-10-30 14:53:45'),
(14, 27, 'Equipment Delivered', 'The lender has marked your equipment as delivered for booking REQ-903D82. Please open your booking details and confirm delivery.', 1, '2026-10-30 15:00:14'),
(15, 27, 'Equipment Returned', 'The lender has marked your equipment as returned for booking REQ-903D82. Please open your booking details and confirm the return.', 1, '2026-10-30 15:00:58'),
(16, 9, 'New Rental Request', 'pragathi has sent a rental request for Battery Knapsack Sprayer. Request Code: REQ-B3221F.', 0, '2026-10-30 15:06:19'),
(17, 9, 'Booking Cancelled by Renter', 'The renter has cancelled the booking for Battery Knapsack Sprayer.', 0, '2026-10-30 15:08:07'),
(18, 26, 'New Rental Request', 'pragathi has sent a rental request for Mist Blower Sprayer. Request Code: REQ-4C4989.', 0, '2026-10-30 15:08:52'),
(19, 26, 'New Rental Request', 'Manjunath has sent a rental request for Paddy Harvester. Request Code: REQ-346F61.', 0, '2026-10-30 15:11:15'),
(20, 26, 'Booking Cancelled by Renter', 'The renter has cancelled the booking for Paddy Harvester.', 0, '2026-10-30 15:11:53'),
(21, 26, 'New Rental Request', 'Manjunath has sent a rental request for Power Reaper. Request Code: REQ-3A2341.', 0, '2026-10-30 15:13:07'),
(22, 26, 'New Rental Request', 'Manjunath has sent a rental request for Multi Crop Planter. Request Code: REQ-A5EB50.', 0, '2026-10-30 15:18:02'),
(23, 27, 'Equipment Delivered', 'The lender has marked your equipment as delivered for booking REQ-A5EB50. Please open your booking details and confirm delivery.', 1, '2026-10-30 15:18:49'),
(24, 27, 'Equipment Returned', 'The lender has marked your equipment as returned for booking REQ-A5EB50. Please open your booking details and confirm the return.', 1, '2026-10-30 15:19:16'),
(25, 26, 'New Rental Request', 'Manjunath has sent a rental request for Mini Combine Harvester. Request Code: REQ-09B031.', 0, '2026-10-30 15:20:16'),
(26, 26, 'New Rental Request', 'Manjunath has sent a rental request for New Holland 3630 Tractor. Request Code: REQ-31FA5D.', 0, '2026-10-30 15:21:23'),
(27, 26, 'New Rental Request', 'pragathi has sent a rental request for Massey Ferguson 241 DI Tractor. Request Code: REQ-CC417E.', 0, '2026-10-30 15:23:56'),
(28, 26, 'New Rental Request', 'Manjunath has sent a rental request for Seed Drill Machine. Request Code: REQ-D3F59B.', 0, '2026-10-30 15:24:45'),
(29, 27, 'Equipment Delivered', 'The lender has marked your equipment as delivered for booking REQ-09B031. Please open your booking details and confirm delivery.', 1, '2026-10-30 15:26:04'),
(30, 26, 'New Customer Review', 'A renter has submitted a new 4-star review for your equipment \"Mahindra 575 DI Tractor	\".', 0, '2026-10-30 15:29:10'),
(31, 26, 'New Rental Request', 'pragathi has sent a rental request for Manual Seed Drill. Request Code: REQ-C3F816.', 0, '2026-10-30 15:33:16'),
(32, 27, 'Equipment Returned', 'The lender has marked your equipment as returned for booking REQ-09B031. Please open your booking details and confirm the return.', 0, '2026-10-30 15:35:45'),
(33, 27, 'Equipment Delivered', 'The lender has marked your equipment as delivered for booking REQ-D3F59B. Please open your booking details and confirm delivery.', 1, '2026-10-30 15:37:48'),
(34, 27, 'Equipment Delivered', 'The lender has marked your equipment as delivered for booking REQ-31FA5D. Please open your booking details and confirm delivery.', 1, '2026-11-02 15:03:42'),
(35, 27, 'Equipment Returned', 'The lender has marked your equipment as returned for booking REQ-31FA5D. Please open your booking details and confirm the return.', 1, '2026-11-02 15:04:14'),
(36, 26, 'New Rental Request', 'Manjunath has sent a rental request for Diesel Water Pump. Request Code: REQ-E47C06.', 0, '2026-11-02 15:55:42'),
(37, 27, 'Equipment Delivered', 'The lender has marked your equipment as delivered for booking REQ-E47C06. Please open your booking details and confirm delivery.', 1, '2026-11-02 15:56:22'),
(38, 27, 'Rental Ends Tomorrow', 'Your rental for Seed Drill Machine ends tomorrow. Please arrange to return the equipment and contact Ramesh Gowda at 9000000005.', 0, '2026-11-03 06:33:52'),
(39, 26, 'Rental Ends Tomorrow', 'The rental for Seed Drill Machine ends tomorrow. Please contact renter Manjunath at 9000000006 to arrange the return.', 0, '2026-11-03 06:33:52'),
(40, 27, 'Rental Period Expired', 'Your rental period for Diesel Water Pump has ended. Please return the equipment and contact your lender at 9000000005. Current overdue charge: ₹100 for 1 day(s).', 0, '2026-11-03 06:33:52'),
(41, 26, 'Equipment Return Overdue', 'The rental period for Diesel Water Pump has ended, but the equipment is still marked as unreturned. Please contact the renter at 9000000006. Current overdue charge: ₹100.', 0, '2026-11-03 06:33:52');

-- --------------------------------------------------------

--
-- Table structure for table `recommended_equipment`
--

CREATE TABLE `recommended_equipment` (
  `recommendation_id` int(11) NOT NULL,
  `equipment_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `price_per_day` decimal(10,2) DEFAULT 0.00,
  `location` varchar(255) DEFAULT NULL,
  `rating` decimal(3,1) DEFAULT 0.0,
  `review_count` int(11) DEFAULT 0,
  `lender_id` int(11) DEFAULT NULL,
  `lender_name` varchar(255) DEFAULT NULL,
  `lender_profile` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `recommended_equipment`
--

INSERT INTO `recommended_equipment` (`recommendation_id`, `equipment_id`, `title`, `category`, `image`, `price_per_day`, `location`, `rating`, `review_count`, `lender_id`, `lender_name`, `lender_profile`, `created_at`) VALUES
(1, 5, 'Tractors', 'Tractor', 'C:\\xampp\\htdocs\\argriculutre-eqiupiment-rental\\images\\Tractor.png', 1200.00, 'Hassan, Karnataka', 4.5, 12, 7, 'Lender', NULL, '2026-09-06 10:39:52'),
(2, 4, ' Harvesting', 'Harvesting', 'C:\\xampp\\htdocs\\argriculutre-eqiupiment-rental\\images\\harvesting.png', 2500.00, 'kolar Karnataka', 4.6, 8, 7, 'Lender', NULL, '2026-09-06 10:39:52'),
(3, 0, 'Irrigation ', 'Irrigation', 'C:\\xampp\\htdocs\\argriculutre-eqiupiment-rental\\images\\Irrigation.png', 800.00, 'mysuru, Karnataka', 4.4, 6, 7, 'Lender', NULL, '2026-09-06 10:39:52'),
(4, 0, 'Tillage', 'Tillage', 'C:\\xampp\\htdocs\\argriculutre-eqiupiment-rental\\images\\Tillage.png', 1000.00, ' Karnatakatumukru', 4.5, 10, 7, 'Lender', NULL, '2026-09-06 10:39:52'),
(5, 0, 'Seed Drill', 'Seeding', 'C:\\xampp\\htdocs\\argriculutre-eqiupiment-rental\\images\\Seeding.png', 900.00, 'Hassan, Karnataka', 4.3, 5, 7, 'Lender', NULL, '2026-09-06 10:39:52'),
(6, 0, 'Sprayer', 'Spraying', 'C:\\xampp\\htdocs\\argriculutre-eqiupiment-rental\\images\\Spraying.png', 700.00, 'mandya, Karnataka', 4.4, 7, 7, 'Lender', NULL, '2026-09-06 10:39:52');

-- --------------------------------------------------------

--
-- Table structure for table `rental_history`
--

CREATE TABLE `rental_history` (
  `rental_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `equipment_id` int(11) NOT NULL,
  `equipment_name` varchar(150) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `rental_start` date NOT NULL,
  `rental_end` date NOT NULL,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('Pending','Confirmed','Ongoing','Completed') DEFAULT 'Pending',
  `payment_status` enum('Pending','Paid','Partial','Refunded') DEFAULT 'Pending',
  `rented_on` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rental_history`
--

INSERT INTO `rental_history` (`rental_id`, `user_id`, `equipment_id`, `equipment_name`, `category`, `rental_start`, `rental_end`, `total_amount`, `status`, `payment_status`, `rented_on`) VALUES
(1, 1, 1, 'John Deere 5050D Tractor', 'Tractors', '2026-08-01', '2026-08-03', 6000.00, 'Completed', 'Paid', '2026-09-11 13:30:48'),
(2, 1, 2, 'Mahindra Combine Harvester', 'Harvesting', '2026-08-10', '2026-08-11', 6000.00, 'Completed', 'Paid', '2026-09-11 13:30:48'),
(3, 1, 4, 'Rotavator', 'Tillage', '2026-08-20', '2026-08-21', 1000.00, 'Ongoing', 'Partial', '2026-09-11 13:30:48'),
(4, 2, 5, 'Seed Drill', 'Seeding', '2026-08-25', '2026-08-26', 900.00, 'Confirmed', 'Pending', '2026-09-11 13:30:48'),
(5, 2, 6, 'Sprayer', 'Spraying', '2026-09-01', '2026-09-02', 700.00, 'Pending', 'Pending', '2026-09-11 13:30:48');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `equipment_id` int(11) NOT NULL,
  `renter_id` int(11) NOT NULL,
  `lender_id` int(11) NOT NULL,
  `rating` tinyint(1) NOT NULL CHECK (`rating` between 1 and 5),
  `review_text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`review_id`, `booking_id`, `equipment_id`, `renter_id`, `lender_id`, `rating`, `review_text`, `created_at`, `updated_at`) VALUES
(1, 3, 1, 5, 7, 5, 'good nice equipment', '2026-09-18 15:45:51', '2026-09-18 15:45:51'),
(2, 8, 29, 27, 26, 4, 'very nice equipment and the delivery experience is nice', '2026-10-30 15:29:10', '2026-10-30 15:29:10');

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
  `city` varchar(100) DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT 'default_avatar.png',
  `reset_otp` varchar(6) DEFAULT NULL,
  `otp_expires_at` datetime DEFAULT NULL,
  `otp_code` varchar(6) DEFAULT NULL,
  `otp_expiry` datetime DEFAULT NULL,
  `remember_token` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email`, `phone`, `password`, `role`, `security_question`, `security_answer`, `address`, `city`, `district`, `state`, `profile_pic`, `reset_otp`, `otp_expires_at`, `otp_code`, `otp_expiry`, `remember_token`) VALUES
(5, 'pragathi', 'pragathikt29@gmail.com', '9845442268', '$2y$10$W6FZNvu1hU4ZXSNpII2gLuhCAUDr6Yx23iAsEnPSPRauEwYBAEp76', 'renter', 'birth_city', 'arsikere', 'Hassan Karnataka', 'Hassan', 'Hassan', 'Karnataka', 'default_avatar.png', '722432', '2026-10-28 16:53:19', NULL, NULL, NULL),
(7, 'Tejomurthy', 'pragathikt4@gmail.com', '7975865577', '$2y$10$W763Ij/HvNecUqKCPkLxE.qmGc8DnrgbriMhbD9tm1XBuAscm4eY2', 'lender', 'first_school', 'government school', 'mysuru', 'Mysuru', 'Mysuru', 'Karnataka', 'default_avatar.png', NULL, NULL, NULL, NULL, NULL),
(9, 'Test Lender Bengaluru', 'lender.bengaluru@test.com', '9000000001', '$2y$10$6oQ/fJasHHsWblBze.OevuzGvMzNYraTOWyN0qORBbZetZLUkeSxW', 'lender', 'first_pet', 'ruby', 'Bengaluru,Bengaluru Urban,Karnataka', 'Bengaluru', 'Bengaluru Urban', 'Karnataka', 'default_avatar.png', NULL, NULL, NULL, NULL, NULL),
(10, 'Test Lender Anekal', 'lender.anekal@test.com', '9000000002', '$2y$10$.DLa.p7Lznqa.ab/sAXQ0e4F3wxg9winPSFr5yQUfXJtC0omhXtEi', 'lender', 'first_pet', 'snoofy', 'Anekal,Bengaluru Urban,Karnataka', 'Bengaluru', 'Bengaluru Urban', 'Karnataka', 'default_avatar.png', NULL, NULL, NULL, NULL, NULL),
(11, 'Test Lender Mysuru', 'lender.mysuru@test.com', '9000000003', '$2y$10$qTkT5XOxHmDHHq09dHmEcuOqqXdQ3WJyAIW3IZzCH6BWskoK7nvwq', 'lender', 'birth_city', 'Arsikere', 'Mysuru,Mysuru,Karnataka', 'Mysuru', 'Mysuru', 'Karnataka', 'default_avatar.png', NULL, NULL, NULL, NULL, NULL),
(12, 'Test Lender Hassan', 'lender.hassan@test.com', '9000000004', '$2y$10$UXdWbtKx6CCNJPI4GVhR2.2qvYUNFC8gl2p6zT1nkCeRkIVNbkYFy', 'lender', 'first_pet', 'Ruby', 'Hassan,Hassan,Karnataka', 'Hassan', 'Hassan', 'Karnataka', 'default_avatar.png', NULL, NULL, NULL, NULL, NULL),
(13, 'Test Renter Bengaluru', 'renter.bengaluru@test.com', '9000000011', '$2y$10$bSp9HkypgW7.0.yEltpqZeuGm8Sv6fnyyLHiDaFGRlKRvuUpJZE2C', 'renter', 'first_pet', 'Ruby', 'Bengaluru,Bengaluru urban,Karnataka', 'Bengaluru', 'Bengaluru', 'Karnataka', 'default_avatar.png', NULL, NULL, NULL, NULL, NULL),
(14, ' Test Renter Mysuru', 'renter.mysuru@test.com', '9000000012', '$2y$10$DMitevEZ1C5IhF1lZ6WAoeSxfjoCUqh6lmVjQpJvmAq2h1fldzIna', 'renter', 'first_pet', 'Ruby', 'Mysuru,Musuru,Karnataka', 'Mysuru', 'Mysuru', 'Karnataka', 'default_avatar.png', NULL, NULL, NULL, NULL, NULL),
(15, 'Test Renter Hassan', 'renter.hassan@test.com', ' 9000000013', '$2y$10$R2kI.rcemst.e6rbacsP3u7BrVwFi9h.98qUDIgnDPhWZM2h/A5oS', 'renter', 'first_pet', 'ruby', 'Hassan,Hassan,Karnataka', 'Hassan', 'Hassan', 'Karnataka', 'default_avatar.png', NULL, NULL, NULL, NULL, NULL),
(26, 'Ramesh Gowda', 'agrirental78@gmail.com', '9000000005', '$2y$10$5.7oOtjmIYhGsMMgobnOk.T.jf2DU3NdjRv22pGT6VX3p3MNzGZaK', 'lender', 'first_pet', 'Ruby', 'Devanahalli, Bengaluru Rural', 'Devanahalli', 'Bengaluru Rural', 'lender.mysuru@test.com', 'default_avatar.png', NULL, NULL, NULL, NULL, NULL),
(27, 'Manjunath', 'rhn3986@gmail.com', '9000000006', '$2y$10$TnF3dZw5dxCbbzk6wLepmekgd9hsh0DrY0xUwtOTmSDupGOYqUDLe', 'renter', 'first_pet', 'Ruby', 'Devanahalli, Bengaluru Rural', 'Devanahalli', 'Bengaluru Rural', 'agrirental78@gmail.com', 'default_avatar.png', NULL, NULL, NULL, NULL, NULL);

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
-- Indexes for table `equipment_price_history`
--
ALTER TABLE `equipment_price_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `idx_price_history_equipment_date` (`equipment_id`,`changed_at`),
  ADD KEY `idx_price_history_lender_date` (`lender_id`,`changed_at`);

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
-- Indexes for table `rental_history`
--
ALTER TABLE `rental_history`
  ADD PRIMARY KEY (`rental_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD UNIQUE KEY `booking_id` (`booking_id`),
  ADD UNIQUE KEY `unique_review_booking` (`booking_id`),
  ADD KEY `equipment_id` (`equipment_id`),
  ADD KEY `renter_id` (`renter_id`),
  ADD KEY `lender_id` (`lender_id`),
  ADD KEY `idx_reviews_equipment` (`equipment_id`),
  ADD KEY `idx_reviews_renter` (`renter_id`),
  ADD KEY `idx_reviews_lender` (`lender_id`);

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
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `equipment`
--
ALTER TABLE `equipment`
  MODIFY `equipment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `equipment_price_history`
--
ALTER TABLE `equipment_price_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `items`
--
ALTER TABLE `items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `rental_history`
--
ALTER TABLE `rental_history`
  MODIFY `rental_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

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
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `fk_reviews_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_reviews_equipment` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`equipment_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_reviews_lender` FOREIGN KEY (`lender_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_reviews_renter` FOREIGN KEY (`renter_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 10.123.0.222:3307
-- Generation Time: Feb 28, 2025 at 11:12 PM
-- Server version: 8.0.22
-- PHP Version: 8.2.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `salvageyard_ai`
--

-- --------------------------------------------------------

--
-- Table structure for table `breaks`
--

CREATE TABLE `breaks` (
  `id` int NOT NULL,
  `time_log_id` int DEFAULT NULL,
  `start_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `duration` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci TABLESPACE `salvageyard_ai`;

--
-- Dumping data for table `breaks`
--

INSERT INTO `breaks` (`id`, `time_log_id`, `start_time`, `duration`) VALUES
(1, 6, '2025-02-28 12:58:34', 286),
(2, 37, '2025-02-28 15:37:00', 15),
(3, 38, '2025-02-28 16:30:00', 30),
(4, 41, '2025-02-28 19:27:39', 58);

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int NOT NULL,
  `name` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `contact_info` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci TABLESPACE `salvageyard_ai`;

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int NOT NULL,
  `username` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `full_name` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `street_address` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `city` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `state` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `zip` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `emergency_contact` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `emergency_contact_phone` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  `drivers_license_picture` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `suspended` tinyint(1) DEFAULT '0',
  `can_process_vehicles` tinyint(1) DEFAULT '0',
  `email` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci TABLESPACE `salvageyard_ai`;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `username`, `password`, `full_name`, `street_address`, `city`, `state`, `zip`, `emergency_contact`, `emergency_contact_phone`, `birthday`, `hire_date`, `drivers_license_picture`, `suspended`, `can_process_vehicles`, `email`) VALUES
(1, 'admin', '', 'Admin User', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL),
(6, 'johndoe', 'password123', 'John Doe', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 'johndoe@example.com'),
(7, 'janedriver', 'password123', 'Jane Smith', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 'jane.smith@example.com'),
(8, 'bobyard', 'password123', 'Bob Johnson', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 'bob.johnson@example.com'),
(9, 'aliceoffice', 'password123', 'Alice Brown', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 'alice.brown@example.com');

-- --------------------------------------------------------

--
-- Table structure for table `employee_roles`
--

CREATE TABLE `employee_roles` (
  `employee_id` int NOT NULL,
  `role_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci TABLESPACE `salvageyard_ai`;

--
-- Dumping data for table `employee_roles`
--

INSERT INTO `employee_roles` (`employee_id`, `role_id`) VALUES
(1, 1),
(9, 3),
(8, 4),
(7, 5),
(6, 12);

-- --------------------------------------------------------

--
-- Table structure for table `fleet_maintenance`
--

CREATE TABLE `fleet_maintenance` (
  `id` int NOT NULL,
  `truck_id` int DEFAULT NULL,
  `maintenance_date` date DEFAULT NULL,
  `maintenance_type` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8_unicode_ci,
  `mileage_interval` int DEFAULT NULL,
  `scheduled_by` int DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci TABLESPACE `salvageyard_ai`;

-- --------------------------------------------------------

--
-- Table structure for table `fleet_maintenance_receipts`
--

CREATE TABLE `fleet_maintenance_receipts` (
  `id` int NOT NULL,
  `truck_id` int DEFAULT NULL,
  `receipt_path` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `service_type` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `service_date` date DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `needs_reimbursement` tinyint(1) DEFAULT '0',
  `notes` text COLLATE utf8_unicode_ci,
  `uploaded_by` int DEFAULT NULL,
  `uploaded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('pending','approved','rejected') COLLATE utf8_unicode_ci DEFAULT 'pending',
  `approved_by` int DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci TABLESPACE `salvageyard_ai`;

-- --------------------------------------------------------

--
-- Table structure for table `fleet_vehicles`
--

CREATE TABLE `fleet_vehicles` (
  `id` int NOT NULL,
  `make` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `model` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `year` int DEFAULT NULL,
  `license_plate` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `vin` varchar(17) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci TABLESPACE `salvageyard_ai`;

-- --------------------------------------------------------

--
-- Table structure for table `fleet_vehicle_assignments`
--

CREATE TABLE `fleet_vehicle_assignments` (
  `id` int NOT NULL,
  `vehicle_id` int DEFAULT NULL,
  `driver_id` int DEFAULT NULL,
  `assigned_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `unassigned_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci TABLESPACE `salvageyard_ai`;

-- --------------------------------------------------------

--
-- Table structure for table `fleet_vehicle_documents`
--

CREATE TABLE `fleet_vehicle_documents` (
  `id` int NOT NULL,
  `truck_id` int DEFAULT NULL,
  `document_path` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `document_type` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `expiration_date` date NOT NULL,
  `notes` text COLLATE utf8_unicode_ci,
  `uploaded_by` int DEFAULT NULL,
  `uploaded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('pending','approved','rejected') COLLATE utf8_unicode_ci DEFAULT 'pending',
  `approved_by` int DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `expiration_notified` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci TABLESPACE `salvageyard_ai`;

-- --------------------------------------------------------

--
-- Table structure for table `parts`
--

CREATE TABLE `parts` (
  `id` int NOT NULL,
  `vehicle_id` int NOT NULL,
  `name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `category` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `description` text COLLATE utf8_unicode_ci,
  `interchange_info` text COLLATE utf8_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci TABLESPACE `salvageyard_ai`;

--
-- Dumping data for table `parts`
--

INSERT INTO `parts` (`id`, `vehicle_id`, `name`, `category`, `price`, `description`, `interchange_info`) VALUES
(1, 1, 'Radiator', 'Cooling', 50.00, 'Used radiator in good condition', '12345'),
(2, 2, 'Alternator', 'Electrical', 75.00, 'Functional alternator, slightly worn', '67890'),
(3, 1, 'Radiator', 'Cooling', 50.00, 'Used radiator in good condition', '12345'),
(4, 2, 'Alternator', 'Electrical', 75.00, 'Functional alternator, slightly worn', '67890'),
(5, 1, 'Radiator', 'Cooling', 50.00, 'Used radiator in good condition', '12345'),
(6, 2, 'Alternator', 'Electrical', 75.00, 'Functional alternator, slightly worn', '67890');

-- --------------------------------------------------------

--
-- Table structure for table `pending_vehicles`
--

CREATE TABLE `pending_vehicles` (
  `id` int NOT NULL,
  `vin` varchar(17) COLLATE utf8_unicode_ci NOT NULL,
  `make` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `model` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `year` int DEFAULT NULL,
  `condition` text COLLATE utf8_unicode_ci,
  `weight` decimal(10,2) DEFAULT NULL,
  `nmvtis_status` varchar(50) COLLATE utf8_unicode_ci DEFAULT 'Pending',
  `submitted_by` int DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `pickup_truck_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci TABLESPACE `salvageyard_ai`;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int NOT NULL,
  `role_name` varchar(50) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci TABLESPACE `salvageyard_ai`;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `role_name`) VALUES
(1, 'admin'),
(2, 'baby admin'),
(5, 'driver'),
(12, 'employee'),
(11, 'fleet'),
(3, 'office'),
(4, 'yardman');

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int NOT NULL,
  `date` date DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `customer_id` int DEFAULT NULL,
  `employee_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci TABLESPACE `salvageyard_ai`;

-- --------------------------------------------------------

--
-- Table structure for table `sale_items`
--

CREATE TABLE `sale_items` (
  `id` int NOT NULL,
  `sale_id` int DEFAULT NULL,
  `part_id` int DEFAULT NULL,
  `quantity` int DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci TABLESPACE `salvageyard_ai`;

-- --------------------------------------------------------

--
-- Table structure for table `scrap_customers`
--

CREATE TABLE `scrap_customers` (
  `id` int NOT NULL,
  `seller_name` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `drivers_license_number` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `drivers_license_picture` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci TABLESPACE `salvageyard_ai`;

-- --------------------------------------------------------

--
-- Table structure for table `scrap_metal_types`
--

CREATE TABLE `scrap_metal_types` (
  `id` int NOT NULL,
  `metal_type` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci TABLESPACE `salvageyard_ai`;

--
-- Dumping data for table `scrap_metal_types`
--

INSERT INTO `scrap_metal_types` (`id`, `metal_type`, `price`, `updated_at`) VALUES
(1, 'Copper #1 Bare Bright', 3.50, '2025-02-28 02:47:20'),
(2, 'Copper #1', 3.20, '2025-02-28 02:47:20'),
(3, 'Copper #2', 2.90, '2025-02-28 02:47:20'),
(4, 'Copper Burnt Wire', 2.50, '2025-02-28 02:47:20'),
(5, 'Brass Yellow', 1.80, '2025-02-28 02:47:20'),
(6, 'Brass Red', 2.00, '2025-02-28 02:47:20'),
(7, 'Brass Dirty', 1.50, '2025-02-28 02:47:20'),
(8, 'Aluminum Clean Cast', 0.60, '2025-02-28 02:47:20'),
(9, 'Aluminum Extruded', 0.70, '2025-02-28 02:47:20'),
(10, 'Aluminum Breakage', 0.40, '2025-02-28 02:47:20'),
(11, 'Steel #1 Prepared', 0.10, '2025-02-28 02:47:20'),
(12, 'Steel #2 Prepared', 0.08, '2025-02-28 02:47:20'),
(13, 'Steel Unprepared', 0.06, '2025-02-28 02:47:20');

-- --------------------------------------------------------

--
-- Table structure for table `scrap_purchases`
--

CREATE TABLE `scrap_purchases` (
  `id` int NOT NULL,
  `employee_id` int NOT NULL,
  `material` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `weight` decimal(10,2) NOT NULL,
  `price_per_lb` decimal(10,2) NOT NULL,
  `purchase_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci TABLESPACE `salvageyard_ai`;

--
-- Dumping data for table `scrap_purchases`
--

INSERT INTO `scrap_purchases` (`id`, `employee_id`, `material`, `weight`, `price_per_lb`, `purchase_date`) VALUES
(1, 1, 'copper #1', 5.00, 0.25, '2025-02-28 14:35:48'),
(2, 9, 'copper #1', 4.00, 0.75, '2025-02-28 14:36:30'),
(3, 1, 'copper', 5.00, 1.65, '2025-02-28 16:38:07'),
(4, 9, 'copper #1', 8.00, 42.77, '2025-02-28 16:38:42');

-- --------------------------------------------------------

--
-- Table structure for table `scrap_transactions`
--

CREATE TABLE `scrap_transactions` (
  `id` int NOT NULL,
  `customer_id` int DEFAULT NULL,
  `truck_id` int DEFAULT NULL,
  `metal_type` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `weight` decimal(10,2) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `transaction_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `processed_by` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci TABLESPACE `salvageyard_ai`;

-- --------------------------------------------------------

--
-- Table structure for table `time_logs`
--

CREATE TABLE `time_logs` (
  `id` int NOT NULL,
  `employee_id` int DEFAULT NULL,
  `clock_in` timestamp NOT NULL,
  `clock_out` timestamp NULL DEFAULT NULL,
  `total_hours` decimal(5,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci TABLESPACE `salvageyard_ai`;

--
-- Dumping data for table `time_logs`
--

INSERT INTO `time_logs` (`id`, `employee_id`, `clock_in`, `clock_out`, `total_hours`) VALUES
(1, 1, '2025-02-28 03:21:17', '2025-02-28 03:27:33', NULL),
(2, 1, '2025-02-28 03:27:43', '2025-02-28 03:33:30', NULL),
(3, 1, '2025-02-28 03:33:40', '2025-02-28 12:51:22', NULL),
(4, 1, '2025-02-28 12:43:59', '2025-02-28 12:51:22', NULL),
(5, 1, '2025-02-28 12:51:29', '2025-02-28 12:57:35', NULL),
(6, 1, '2025-02-28 12:57:43', '2025-02-28 13:19:27', NULL),
(7, 1, '2025-02-28 13:30:12', '2025-02-28 13:30:15', NULL),
(8, 6, '2025-02-28 13:30:45', '2025-02-28 13:31:10', NULL),
(9, 9, '2025-02-28 13:31:33', '2025-02-28 13:31:51', NULL),
(10, 9, '2025-02-28 13:32:16', '2025-02-28 13:32:29', NULL),
(11, 1, '2025-02-28 13:32:36', '2025-02-28 13:38:02', NULL),
(12, 1, '2025-02-28 13:38:09', '2025-02-28 13:42:40', NULL),
(13, 1, '2025-02-28 13:42:49', '2025-02-28 13:47:12', NULL),
(14, 1, '2025-02-28 13:47:19', '2025-02-28 13:49:23', NULL),
(15, 1, '2025-02-28 13:49:31', '2025-02-28 13:51:52', NULL),
(16, 1, '2025-02-28 13:51:59', '2025-02-28 13:52:17', NULL),
(17, 9, '2025-02-28 13:52:33', '2025-02-28 13:56:26', NULL),
(18, 1, '2025-02-28 13:56:33', '2025-02-28 13:57:37', NULL),
(19, 9, '2025-02-28 13:57:49', '2025-02-28 13:59:45', NULL),
(20, 9, '2025-02-28 13:59:59', '2025-02-28 14:00:16', NULL),
(21, 1, '2025-02-28 14:00:22', '2025-02-28 14:00:29', NULL),
(22, 9, '2025-02-28 14:03:22', '2025-02-28 14:07:27', NULL),
(23, 9, '2025-02-28 14:07:43', '2025-02-28 14:13:44', NULL),
(24, 1, '2025-02-28 14:13:53', '2025-02-28 14:18:40', NULL),
(25, 9, '2025-02-28 14:18:54', '2025-02-28 14:30:04', NULL),
(26, 1, '2025-02-28 14:30:09', '2025-02-28 14:32:57', NULL),
(27, 9, '2025-02-28 14:33:14', '2025-02-28 14:35:29', NULL),
(28, 1, '2025-02-28 14:35:35', '2025-02-28 14:36:05', NULL),
(29, 9, '2025-02-28 14:36:17', '2025-02-28 14:41:13', NULL),
(30, 1, '2025-02-28 14:41:19', '2025-02-28 14:47:08', NULL),
(31, 1, '2025-02-28 14:47:15', '2025-02-28 14:51:34', NULL),
(32, 1, '2025-02-28 14:51:47', '2025-02-28 15:01:05', NULL),
(33, 1, '2025-02-28 15:01:15', '2025-02-28 15:28:21', NULL),
(34, 1, '2025-02-28 15:11:45', '2025-02-28 15:28:21', NULL),
(35, 1, '2025-02-28 15:28:29', '2025-02-28 15:31:16', NULL),
(36, 1, '2025-02-28 15:31:22', '2025-02-28 15:33:01', NULL),
(37, 9, '2025-02-28 15:33:11', '2025-02-28 16:24:52', NULL),
(38, 9, '2025-02-28 16:25:04', '2025-02-28 16:34:44', NULL),
(39, 1, '2025-02-28 16:34:50', '2025-02-28 16:38:12', NULL),
(40, 9, '2025-02-28 16:38:27', '2025-02-28 17:17:08', NULL),
(41, 1, '2025-02-28 17:17:15', '2025-02-28 21:11:26', NULL),
(42, 1, '2025-02-28 21:11:34', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `vehicles`
--

CREATE TABLE `vehicles` (
  `id` int NOT NULL,
  `vin` varchar(17) COLLATE utf8_unicode_ci NOT NULL,
  `make` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `model` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `trim` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `location` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `year` int DEFAULT NULL,
  `condition` text COLLATE utf8_unicode_ci,
  `weight` decimal(10,2) DEFAULT NULL,
  `date_acquired` date DEFAULT NULL,
  `status_id` int DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci TABLESPACE `salvageyard_ai`;

--
-- Dumping data for table `vehicles`
--

INSERT INTO `vehicles` (`id`, `vin`, `make`, `model`, `trim`, `location`, `year`, `condition`, `weight`, `date_acquired`, `status_id`) VALUES
(1, '1HGCM82633A004352', 'Honda', 'Accord', 'EX', 'Row 1, Spot 3', 2003, NULL, NULL, '2025-02-26', 0),
(2, '3FA6P0H73HR123456', 'Ford', 'Fusion', 'SE', 'Row 2, Spot 5', 2017, NULL, NULL, '2025-02-26', 0);

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_parts`
--

CREATE TABLE `vehicle_parts` (
  `id` int NOT NULL,
  `vehicle_id` int DEFAULT NULL,
  `part_id` int DEFAULT NULL,
  `quantity` int DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci TABLESPACE `salvageyard_ai`;

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_stripping`
--

CREATE TABLE `vehicle_stripping` (
  `id` int NOT NULL,
  `stock_number` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `vin` varchar(17) COLLATE utf8_unicode_ci NOT NULL,
  `first_scan_at` timestamp NULL DEFAULT NULL,
  `strip_confirmed_at` timestamp NULL DEFAULT NULL,
  `cat_present` tinyint(1) DEFAULT '0',
  `yardman_id` int DEFAULT NULL,
  `vehicle_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci TABLESPACE `salvageyard_ai`;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `breaks`
--
ALTER TABLE `breaks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `time_log_id` (`time_log_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `employee_roles`
--
ALTER TABLE `employee_roles`
  ADD PRIMARY KEY (`employee_id`,`role_id`),
  ADD KEY `role_id` (`role_id`);

--
-- Indexes for table `fleet_maintenance`
--
ALTER TABLE `fleet_maintenance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `truck_id` (`truck_id`),
  ADD KEY `scheduled_by` (`scheduled_by`),
  ADD KEY `maintenance_date` (`maintenance_date`);

--
-- Indexes for table `fleet_maintenance_receipts`
--
ALTER TABLE `fleet_maintenance_receipts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `truck_id` (`truck_id`),
  ADD KEY `uploaded_by` (`uploaded_by`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `uploaded_at` (`uploaded_at`);

--
-- Indexes for table `fleet_vehicles`
--
ALTER TABLE `fleet_vehicles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `vin` (`vin`);

--
-- Indexes for table `fleet_vehicle_assignments`
--
ALTER TABLE `fleet_vehicle_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vehicle_id` (`vehicle_id`),
  ADD KEY `driver_id` (`driver_id`);

--
-- Indexes for table `fleet_vehicle_documents`
--
ALTER TABLE `fleet_vehicle_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `truck_id` (`truck_id`),
  ADD KEY `uploaded_by` (`uploaded_by`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `uploaded_at` (`uploaded_at`,`status`);

--
-- Indexes for table `parts`
--
ALTER TABLE `parts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_vehicle` (`vehicle_id`);

--
-- Indexes for table `pending_vehicles`
--
ALTER TABLE `pending_vehicles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `submitted_by` (`submitted_by`),
  ADD KEY `pickup_truck_id` (`pickup_truck_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_id` (`sale_id`),
  ADD KEY `part_id` (`part_id`);

--
-- Indexes for table `scrap_customers`
--
ALTER TABLE `scrap_customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `drivers_license_number` (`drivers_license_number`);

--
-- Indexes for table `scrap_metal_types`
--
ALTER TABLE `scrap_metal_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `metal_type` (`metal_type`);

--
-- Indexes for table `scrap_purchases`
--
ALTER TABLE `scrap_purchases`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `scrap_transactions`
--
ALTER TABLE `scrap_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `truck_id` (`truck_id`),
  ADD KEY `processed_by` (`processed_by`),
  ADD KEY `transaction_date` (`transaction_date`);

--
-- Indexes for table `time_logs`
--
ALTER TABLE `time_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `vin` (`vin`);

--
-- Indexes for table `vehicle_parts`
--
ALTER TABLE `vehicle_parts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vehicle_id` (`vehicle_id`),
  ADD KEY `part_id` (`part_id`);

--
-- Indexes for table `vehicle_stripping`
--
ALTER TABLE `vehicle_stripping`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vehicle_id` (`vehicle_id`),
  ADD KEY `yardman_id` (`yardman_id`,`first_scan_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `breaks`
--
ALTER TABLE `breaks`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `fleet_maintenance`
--
ALTER TABLE `fleet_maintenance`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fleet_maintenance_receipts`
--
ALTER TABLE `fleet_maintenance_receipts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fleet_vehicles`
--
ALTER TABLE `fleet_vehicles`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fleet_vehicle_assignments`
--
ALTER TABLE `fleet_vehicle_assignments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fleet_vehicle_documents`
--
ALTER TABLE `fleet_vehicle_documents`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `parts`
--
ALTER TABLE `parts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `pending_vehicles`
--
ALTER TABLE `pending_vehicles`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sale_items`
--
ALTER TABLE `sale_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `scrap_customers`
--
ALTER TABLE `scrap_customers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `scrap_metal_types`
--
ALTER TABLE `scrap_metal_types`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `scrap_purchases`
--
ALTER TABLE `scrap_purchases`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `scrap_transactions`
--
ALTER TABLE `scrap_transactions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `time_logs`
--
ALTER TABLE `time_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `vehicle_parts`
--
ALTER TABLE `vehicle_parts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vehicle_stripping`
--
ALTER TABLE `vehicle_stripping`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `breaks`
--
ALTER TABLE `breaks`
  ADD CONSTRAINT `breaks_ibfk_1` FOREIGN KEY (`time_log_id`) REFERENCES `time_logs` (`id`);

--
-- Constraints for table `employee_roles`
--
ALTER TABLE `employee_roles`
  ADD CONSTRAINT `employee_roles_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employee_roles_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `fleet_maintenance`
--
ALTER TABLE `fleet_maintenance`
  ADD CONSTRAINT `fleet_maintenance_ibfk_1` FOREIGN KEY (`truck_id`) REFERENCES `fleet_vehicles` (`id`),
  ADD CONSTRAINT `fleet_maintenance_ibfk_2` FOREIGN KEY (`scheduled_by`) REFERENCES `employees` (`id`);

--
-- Constraints for table `fleet_maintenance_receipts`
--
ALTER TABLE `fleet_maintenance_receipts`
  ADD CONSTRAINT `fleet_maintenance_receipts_ibfk_1` FOREIGN KEY (`truck_id`) REFERENCES `fleet_vehicles` (`id`),
  ADD CONSTRAINT `fleet_maintenance_receipts_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `employees` (`id`),
  ADD CONSTRAINT `fleet_maintenance_receipts_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `employees` (`id`);

--
-- Constraints for table `fleet_vehicle_assignments`
--
ALTER TABLE `fleet_vehicle_assignments`
  ADD CONSTRAINT `fleet_vehicle_assignments_ibfk_1` FOREIGN KEY (`vehicle_id`) REFERENCES `fleet_vehicles` (`id`),
  ADD CONSTRAINT `fleet_vehicle_assignments_ibfk_2` FOREIGN KEY (`driver_id`) REFERENCES `employees` (`id`);

--
-- Constraints for table `fleet_vehicle_documents`
--
ALTER TABLE `fleet_vehicle_documents`
  ADD CONSTRAINT `fleet_vehicle_documents_ibfk_1` FOREIGN KEY (`truck_id`) REFERENCES `fleet_vehicles` (`id`),
  ADD CONSTRAINT `fleet_vehicle_documents_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `employees` (`id`),
  ADD CONSTRAINT `fleet_vehicle_documents_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `employees` (`id`);

--
-- Constraints for table `parts`
--
ALTER TABLE `parts`
  ADD CONSTRAINT `fk_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pending_vehicles`
--
ALTER TABLE `pending_vehicles`
  ADD CONSTRAINT `pending_vehicles_ibfk_1` FOREIGN KEY (`submitted_by`) REFERENCES `employees` (`id`),
  ADD CONSTRAINT `pending_vehicles_ibfk_2` FOREIGN KEY (`pickup_truck_id`) REFERENCES `fleet_vehicles` (`id`);

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `sales_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`);

--
-- Constraints for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD CONSTRAINT `sale_items_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`),
  ADD CONSTRAINT `sale_items_ibfk_2` FOREIGN KEY (`part_id`) REFERENCES `parts` (`id`);

--
-- Constraints for table `scrap_purchases`
--
ALTER TABLE `scrap_purchases`
  ADD CONSTRAINT `scrap_purchases_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`);

--
-- Constraints for table `scrap_transactions`
--
ALTER TABLE `scrap_transactions`
  ADD CONSTRAINT `scrap_transactions_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `scrap_customers` (`id`),
  ADD CONSTRAINT `scrap_transactions_ibfk_2` FOREIGN KEY (`truck_id`) REFERENCES `fleet_vehicles` (`id`),
  ADD CONSTRAINT `scrap_transactions_ibfk_3` FOREIGN KEY (`processed_by`) REFERENCES `employees` (`id`);

--
-- Constraints for table `time_logs`
--
ALTER TABLE `time_logs`
  ADD CONSTRAINT `time_logs_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`);

--
-- Constraints for table `vehicle_parts`
--
ALTER TABLE `vehicle_parts`
  ADD CONSTRAINT `vehicle_parts_ibfk_1` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`),
  ADD CONSTRAINT `vehicle_parts_ibfk_2` FOREIGN KEY (`part_id`) REFERENCES `parts` (`id`);

--
-- Constraints for table `vehicle_stripping`
--
ALTER TABLE `vehicle_stripping`
  ADD CONSTRAINT `vehicle_stripping_ibfk_1` FOREIGN KEY (`yardman_id`) REFERENCES `employees` (`id`),
  ADD CONSTRAINT `vehicle_stripping_ibfk_2` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

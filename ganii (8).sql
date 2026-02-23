-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 08, 2026 at 11:21 AM
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
-- Database: `ganii`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `datetime` datetime NOT NULL DEFAULT current_timestamp(),
  `user` varchar(191) NOT NULL,
  `action` varchar(191) NOT NULL,
  `module` varchar(191) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `datetime`, `user`, `action`, `module`, `description`) VALUES
(53, '2025-12-04 12:45:07', 'isagani@gmail.com', 'Login', 'Authentication', 'User logged in successfully as Admin'),
(54, '2025-12-04 12:47:22', 'isagani@gmail.com', 'Update Product', 'Inventory', 'Product updated: \'lpg-000 (5kg)\' (ID: 135, Stock: 50)'),
(55, '2025-12-04 12:48:22', 'isagani@gmail.com', 'Update Product', 'Inventory', 'Product updated: \'lpg-000 (5kg)\' (ID: 135, Stock: 50)'),
(56, '2025-12-04 22:21:34', 'isagani@gmail.com', 'Login', 'Authentication', 'User logged in successfully as Admin'),
(58, '2025-12-04 22:36:39', 'isagani@gmail.com', 'Login', 'Authentication', 'User logged in successfully as Admin'),
(76, '2025-12-06 12:52:26', 'isagani@gmail.com', 'Logout', 'Authentication', 'User logged out'),
(77, '2025-12-06 12:52:35', 'admin@gmail.com', 'Login', 'Authentication', 'User logged in successfully as Admin'),
(78, '2025-12-06 12:53:08', 'admin@gmail.com', 'Change Password', 'User', 'Password updated successfully'),
(79, '2025-12-06 12:53:41', 'admin@gmail.com', 'Logout', 'Authentication', 'User logged out'),
(80, '2025-12-06 14:58:00', 'isagani@gmail.com', 'Login', 'Authentication', 'User logged in successfully as Admin'),
(81, '2025-12-06 15:39:47', 'isagani@gmail.com', 'Logout', 'Authentication', 'User logged out'),
(82, '2025-12-06 15:46:57', 'isagani@gmail.com', 'Login', 'Authentication', 'User logged in successfully as Admin'),
(83, '2025-12-14 22:24:05', 'isagani@gmail.com', 'Login', 'Authentication', 'User logged in successfully as Admin'),
(84, '2025-12-15 13:53:36', 'isagani@gmail.com', 'Login', 'Authentication', 'User logged in successfully as Admin'),
(85, '2025-12-15 14:25:11', 'isagani@gmail.com', 'Logout', 'Authentication', 'User logged out'),
(86, '2025-12-15 14:25:20', 'user@gmail.com', 'Login', 'Authentication', 'User logged in successfully as User'),
(87, '2025-12-15 14:43:27', 'user@gmail.com', 'Logout', 'Authentication', 'User logged out'),
(88, '2025-12-15 14:43:36', 'isagani@gmail.com', 'Login', 'Authentication', 'User logged in successfully as Admin'),
(89, '2025-12-15 14:57:31', 'isagani@gmail.com', 'Logout', 'Authentication', 'User logged out'),
(90, '2025-12-15 14:57:39', 'user@gmail.com', 'Login', 'Authentication', 'User logged in successfully as User'),
(91, '2025-12-15 14:59:34', 'user@gmail.com', 'Logout', 'Authentication', 'User logged out'),
(92, '2025-12-15 14:59:47', 'isagani@gmail.com', 'Login', 'Authentication', 'User logged in successfully as Admin'),
(93, '2025-12-15 15:00:15', 'isagani@gmail.com', 'Logout', 'Authentication', 'User logged out'),
(94, '2025-12-15 15:00:22', 'don@gmail.com', 'Login', 'Authentication', 'User logged in successfully as User'),
(95, '2025-12-15 15:00:50', 'don@gmail.com', 'Logout', 'Authentication', 'User logged out'),
(96, '2025-12-15 15:00:57', 'isagani@gmail.com', 'Login', 'Authentication', 'User logged in successfully as Admin'),
(97, '2026-01-03 19:09:12', 'isagani@gmail.com', 'Login', 'Authentication', 'User logged in successfully as Admin'),
(98, '2026-01-19 19:34:08', 'isagani@gmail.com', 'Login', 'Authentication', 'User logged in successfully as Admin'),
(99, '2026-01-19 19:36:37', 'isagani@gmail.com', 'Logout', 'Authentication', 'User logged out'),
(100, '2026-01-19 19:36:49', 'user@gmail.com', 'Login', 'Authentication', 'User logged in successfully as User'),
(101, '2026-01-19 19:46:55', 'user@gmail.com', 'Logout', 'Authentication', 'User logged out'),
(102, '2026-01-19 19:47:51', 'isagani@gmail.com', 'Login', 'Authentication', 'User logged in successfully as Admin'),
(103, '2026-01-20 13:15:04', 'isagani@gmail.com', 'Login', 'Authentication', 'User logged in successfully as Admin'),
(104, '2026-01-20 14:15:15', 'isagani@gmail.com', 'Logout', 'Authentication', 'User logged out'),
(105, '2026-01-20 14:15:27', 'isagani@gmail.com', 'Login Failed', 'Authentication', 'Failed login attempt with invalid credentials'),
(106, '2026-01-20 14:15:35', 'isagani@gmail.com', 'Login', 'Authentication', 'User logged in successfully as Admin'),
(107, '2026-01-20 14:15:47', 'isagani@gmail.com', 'Logout', 'Authentication', 'User logged out'),
(108, '2026-01-20 14:15:55', 'user@gmail.com', 'Login', 'Authentication', 'User logged in successfully as User'),
(109, '2026-01-20 14:21:11', 'user@gmail.com', 'Logout', 'Authentication', 'User logged out'),
(110, '2026-01-20 14:21:20', 'isagani@gmail.com', 'Login', 'Authentication', 'User logged in successfully as Admin'),
(111, '2026-01-20 14:22:14', 'isagani@gmail.com', 'Logout', 'Authentication', 'User logged out'),
(112, '2026-01-20 14:22:26', 'user@gmail.com', 'Login', 'Authentication', 'User logged in successfully as User'),
(113, '2026-01-20 14:30:50', 'user@gmail.com', 'Logout', 'Authentication', 'User logged out'),
(114, '2026-01-20 14:30:59', 'user@gmail.com', 'Login', 'Authentication', 'User logged in successfully as User'),
(115, '2026-01-20 14:36:53', 'user@gmail.com', 'Logout', 'Authentication', 'User logged out'),
(116, '2026-01-20 14:37:04', 'isagani@gmail.com', 'Login', 'Authentication', 'User logged in successfully as Admin'),
(117, '2026-01-20 14:40:28', 'isagani@gmail.com', 'Logout', 'Authentication', 'User logged out'),
(118, '2026-01-20 14:40:37', 'user@gmail.com', 'Login', 'Authentication', 'User logged in successfully as User'),
(119, '2026-01-20 14:45:40', 'isagani@gmail.com', 'Login', 'Authentication', 'User logged in successfully as Admin'),
(120, '2026-01-30 19:46:01', 'isagani@gmail.com', 'Login', 'Authentication', 'User logged in successfully as Admin'),
(121, '2026-01-30 19:47:27', 'isagani@gmail.com', 'Logout', 'Authentication', 'User logged out'),
(122, '2026-01-30 20:12:28', 'isagani@gmail.com', 'Login', 'Authentication', 'User logged in successfully as Admin'),
(123, '2026-01-30 21:14:40', 'isagani@gmail.com', 'Logout', 'Authentication', 'User logged out'),
(124, '2026-01-31 21:11:53', 'isagani@gmail.com', 'Login', 'Authentication', 'User logged in successfully as Admin'),
(125, '2026-02-01 17:46:24', 'isagani@gmail.com', 'Login', 'Authentication', 'User logged in successfully as Admin'),
(126, '2026-02-08 15:53:16', 'isagani@gmail.com', 'Update Product', 'Inventory', 'Product updated: \'lpg-000 (5kg)\' (ID: 141, Added: 20, New Total: 120)'),
(127, '2026-02-08 15:53:43', 'isagani@gmail.com', 'Update Product', 'Inventory', 'Product updated: \'lpg-000 (5kg)\' (ID: 141, Added: 30, New Total: 150)'),
(128, '2026-02-08 16:15:32', 'isagani@gmail.com', 'Update Product', 'Inventory', 'Product updated: \'lpg-000 (5kg)\' (ID: 141, Added: 0, New Total: 147)'),
(129, '2026-02-08 16:17:21', 'isagani@gmail.com', 'Update Product', 'Inventory', 'Product updated: \'lpg-000 (5kg)\' (ID: 141, Added: 0, New Total: 147)'),
(130, '2026-02-08 16:22:58', 'isagani@gmail.com', 'Update Product', 'Inventory', 'Product updated: \'lpg-000 (5kg)\' (ID: 141, Added: 3, New Total: 150)'),
(131, '2026-02-08 16:25:59', 'isagani@gmail.com', 'Update Product', 'Inventory', 'Product updated: \'lpg-000 (5kg)\' (ID: 141, Added: 0, New Total: 150)'),
(132, '2026-02-08 16:26:24', 'isagani@gmail.com', 'Update Product', 'Inventory', 'Product updated: \'lpg-000 (5kg)\' (ID: 141, Added: 10, New Total: 160)'),
(133, '2026-02-08 16:33:07', 'isagani@gmail.com', 'Update Product', 'Inventory', 'Product updated: \'lpg-000 (5kg)\' (ID: 141, Added: 2, New Total: 160)'),
(134, '2026-02-08 16:40:52', 'isagani@gmail.com', 'Logout', 'Authentication', 'User logged out'),
(135, '2026-02-08 16:41:57', 'isagani@gmail.com', 'Logout', 'Authentication', 'User logged out'),
(136, '2026-02-08 16:42:15', 'user@gmail.com', 'Logout', 'Authentication', 'User logged out'),
(137, '2026-02-08 17:14:32', 'isagani@gmail.com', 'Update Product', 'Inventory', 'Product updated: \'lpg-000 (5kg)\' (ID: 141, Added: 0, New Total: 150)'),
(138, '2026-02-08 17:17:21', 'isagani@gmail.com', 'Logout', 'Authentication', 'User logged out');

-- --------------------------------------------------------

--
-- Table structure for table `stock_history`
--

CREATE TABLE `stock_history` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `type` enum('add','deduct') NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `previous_stock` decimal(10,2) NOT NULL,
  `new_stock` decimal(10,2) NOT NULL,
  `remarks` text DEFAULT NULL,
  `user` varchar(191) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_archive_activity_log`
--

CREATE TABLE `tbl_archive_activity_log` (
  `log_id` int(11) NOT NULL,
  `invoice_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `user_email` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_category`
--

CREATE TABLE `tbl_category` (
  `catid` int(11) NOT NULL,
  `category` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_invoice`
--

CREATE TABLE `tbl_invoice` (
  `invoice_id` int(11) NOT NULL,
  `order_date` date NOT NULL,
  `subtotal` double NOT NULL,
  `discount` double NOT NULL,
  `sgst` float NOT NULL,
  `cgst` float NOT NULL,
  `total` double NOT NULL,
  `payment_type` tinytext NOT NULL,
  `due` double NOT NULL,
  `paid` double NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_invoice`
--

INSERT INTO `tbl_invoice` (`invoice_id`, `order_date`, `subtotal`, `discount`, `sgst`, `cgst`, `total`, `payment_type`, `due`, `paid`) VALUES
(145, '2025-12-06', 1350, 20, 0, 0, 1080, 'cash', -20, 1100),
(146, '2025-12-06', 900, 0, 0, 0, 900, 'cash', 0, 900),
(147, '2025-12-06', 40500, 10, 0, 0, 36450, 'cash', -50, 36500),
(150, '2026-01-03', 2250, 20, 0, 0, 1800, 'cash', 0, 1800),
(151, '2026-01-20', 5000, 20, 0, 0, 4000, 'cash', 0, 4000),
(152, '2026-01-31', 450, 0, 0, 0, 450, 'cash', 0, 450),
(157, '2026-02-08', 900, 0, 0, 0, 1000, 'cash', 1000, 1000),
(158, '2026-02-08', 3600, 0, 0, 0, 3600, 'cash', 3600, 4000),
(159, '2026-02-08', 4050, 0, 0, 0, 4050, 'cash', 4050, 4100),
(160, '2026-02-08', 450, 0, 0, 0, 500, 'cash', 0, 0),
(161, '2026-02-08', 2250, 0, 0, 0, 2500, 'cash', 2500, 2500);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_invoice_archive`
--

CREATE TABLE `tbl_invoice_archive` (
  `archive_id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_type` varchar(50) DEFAULT NULL,
  `order_date` date NOT NULL,
  `subtotal` decimal(10,2) DEFAULT NULL,
  `discount` decimal(10,2) DEFAULT NULL,
  `tax` decimal(10,2) DEFAULT NULL,
  `archived_by` int(11) NOT NULL,
  `archived_at` datetime NOT NULL DEFAULT current_timestamp(),
  `status` enum('archived','restored','permanently_deleted') DEFAULT 'archived'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_invoice_archive`
--

INSERT INTO `tbl_invoice_archive` (`archive_id`, `invoice_id`, `customer_name`, `total_amount`, `payment_type`, `order_date`, `subtotal`, `discount`, `tax`, `archived_by`, `archived_at`, `status`) VALUES
(1, 160, NULL, 500.00, 'cash', '2026-02-08', 450.00, 0.00, NULL, 13, '2026-02-08 18:10:04', 'restored');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_invoice_details`
--

CREATE TABLE `tbl_invoice_details` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `barcode` varchar(200) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(200) NOT NULL,
  `qty` int(11) NOT NULL,
  `rate` double NOT NULL,
  `saleprice` double NOT NULL,
  `order_date` date NOT NULL,
  `servicetype` varchar(50) DEFAULT NULL,
  `addfee` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_invoice_details`
--

INSERT INTO `tbl_invoice_details` (`id`, `invoice_id`, `barcode`, `product_id`, `product_name`, `qty`, `rate`, `saleprice`, `order_date`, `servicetype`, `addfee`) VALUES
(54, 60, '', 94, 'lpg-000', 3, 450, 1350, '2025-11-04', 'Pick up', 0.00),
(55, 61, '', 94, 'lpg-000', 2, 450, 900, '2025-11-04', 'Pick up', 0.00),
(56, 62, '', 98, 'gani', 5, 450, 2500, '2025-11-05', 'Delivery', 250.00),
(57, 63, '', 99, 'test', 3, 450, 1500, '2025-11-05', 'Delivery', 150.00),
(58, 64, '', 99, 'test', 3, 450, 1500, '2025-11-05', 'Delivery', 150.00),
(59, 65, '', 98, 'gani', 2, 450, 1000, '2025-11-05', 'Delivery', 100.00),
(60, 66, '', 99, 'test', 6, 450, 3000, '2025-11-05', 'Delivery', 300.00),
(79, 85, '', 109, 'sample', 2, 1000, 2100, '2025-11-05', 'Delivery', 100.00),
(80, 86, '', 90, 'lpg-001', 2, 1000, 2100, '2025-11-05', 'Delivery', 100.00),
(81, 87, '', 94, 'lpg-000', 1, 450, 450, '2025-11-05', 'Pick up', 0.00),
(82, 88, '', 89, 'lpg-002', 1, 450, 500, '2025-11-05', 'Delivery', 50.00),
(83, 89, '', 94, 'lpg-000', 1, 450, 500, '2025-11-05', 'Delivery', 50.00),
(84, 90, '', 94, 'lpg-000', 1, 450, 500, '2025-11-05', 'Delivery', 50.00),
(85, 91, '', 94, 'lpg-000', 1, 450, 500, '2025-11-05', 'Delivery', 50.00),
(86, 92, '', 94, 'lpg-000', 1, 450, 500, '2025-11-05', 'Delivery', 50.00),
(87, 93, '', 94, 'lpg-000', 1, 450, 450, '2025-11-05', 'Pick up', 0.00),
(88, 94, '', 94, 'lpg-000', 1, 450, 500, '2025-11-05', 'Delivery', 50.00),
(89, 95, '', 110, 'isagani', 1, 450, 450, '2025-11-05', 'Pick up', 0.00),
(90, 96, '', 111, 'testing', 4, 450, 1800, '2025-11-06', 'Pick up', 0.00),
(91, 97, '', 112, 'lpg-000 5(kg)', 1, 450, 450, '2025-11-06', 'Pick up', 0.00),
(92, 98, '', 112, 'lpg-000 5(kg)', 4, 450, 1800, '2025-11-06', 'Pick up', 0.00),
(93, 99, '', 111, 'lpg-000', 10, 450, 4500, '2025-11-06', 'Pick up', 0.00),
(94, 100, '', 111, 'lpg-000', 10, 450, 4500, '2025-11-06', 'Pick up', 0.00),
(95, 101, '', 113, 'lpg-000 5(kg)', 1, 1000, 1050, '2025-11-14', 'Delivery', 50.00),
(96, 102, '', 115, 'test', 1, 1000, 1000, '2025-11-14', 'Pick up', 0.00),
(97, 103, '', 116, 'testing', 1, 450, 450, '2025-11-14', 'Pick up', 0.00),
(98, 104, '', 118, 'testing', 1, 450, 450, '2025-11-14', 'Pick up', 0.00),
(99, 105, '', 111, 'lpg-000', 4, 450, 2000, '2025-11-14', 'Delivery', 200.00),
(100, 106, '', 111, 'lpg-000', 4, 450, 2000, '2025-11-14', 'Delivery', 200.00),
(101, 107, '', 111, 'lpg-000', 1, 450, 500, '2025-11-14', 'Delivery', 50.00),
(102, 108, '', 111, 'lpg-000', 1, 450, 500, '2025-11-14', 'Delivery', 50.00),
(103, 109, '', 111, 'lpg-000', 1, 450, 500, '2025-11-14', 'Delivery', 50.00),
(104, 110, '', 111, 'lpg-000', 1, 450, 500, '2025-11-14', 'Delivery', 50.00),
(105, 111, '', 114, 'lpg-000 5(kg)', 1, 1000, 1050, '2025-11-14', 'Delivery', 50.00),
(106, 112, '', 111, 'lpg-000', 1, 450, 450, '2025-11-14', 'Pick-up', 0.00),
(107, 112, '', 113, 'lpg-000 5(kg)', 5, 1000, 5000, '2025-11-14', 'Delivery', 0.00),
(108, 113, '', 111, 'lpg-000', 5, 450, 2500, '2025-11-14', 'Delivery', 250.00),
(109, 114, '', 124, 'testingg', 2, 4200, 8500, '2025-11-14', 'Delivery', 100.00),
(110, 115, '', 125, '22', 10, 1850, 18500, '2025-11-14', 'Pick up', 0.00),
(111, 116, '', 126, 'test', 10, 450, 5000, '2025-11-27', 'Delivery', 500.00),
(112, 117, '', 127, 'testing', 30, 450, 15000, '2025-11-27', 'Delivery', 1500.00),
(113, 118, '', 127, 'testing', 5, 450, 2500, '2025-11-27', 'Delivery', 250.00),
(114, 119, '', 128, 'pick up', 5, 450, 2250, '2025-11-27', 'Pick up', 0.00),
(115, 120, '', 129, 'test', 10, 450, 4500, '2025-11-27', 'Pick up', 0.00),
(116, 121, '', 131, 'testing', 5, 450, 2500, '2025-11-30', 'Delivery', 250.00),
(117, 122, '', 132, 'lpg tank (001)', 5, 450, 2500, '2025-11-30', 'Delivery', 250.00),
(118, 123, '', 133, 'lpg tank (002)', 5, 450, 2500, '2025-11-30', 'Delivery', 250.00),
(119, 124, '', 134, 'lpg tank (003)', 1, 1000, 1050, '2025-11-30', 'Delivery', 50.00),
(120, 125, '', 135, 'lpg-000', 5, 450, 2250, '2025-11-30', 'Pick-up', 0.00),
(121, 126, '', 135, 'lpg-000', 1, 450, 450, '2025-11-30', 'Pick-up', 0.00),
(122, 127, '', 90, 'test 11 kg', 10, 1000, 10500, '2025-11-30', 'Delivery', 500.00),
(123, 128, '', 135, 'lpg-000', 10, 450, 4500, '2025-11-30', 'Pick-up', 0.00),
(124, 129, '', 137, 'lpg-000 t', 5, 450, 2500, '2025-11-30', 'Delivery', 250.00),
(125, 130, '', 89, 'lpg-002', 1, 450, 500, '2025-11-30', 'Delivery', 50.00),
(126, 131, '', 90, 'testttt', 1, 1000, 1050, '2025-11-30', 'Delivery', 50.00),
(127, 132, '', 89, 'lpg-002', 1, 450, 500, '2025-11-30', 'Delivery', 50.00),
(128, 133, '', 90, 'lpg-001', 9, 1000, 9450, '2025-11-30', 'Delivery', 450.00),
(129, 134, '', 89, 'lpg-002', 8, 450, 3600, '2025-11-30', 'Pick-up', 0.00),
(130, 135, '', 90, 'lpg-001', 10, 1000, 10500, '2025-11-30', 'Delivery', 500.00),
(131, 136, '', 90, 'lpg-001', 10, 1000, 10000, '2025-11-30', 'Pick-up', 0.00),
(132, 137, '', 135, 'lpg-000', 10, 450, 4500, '2025-11-30', 'Pick-up', 0.00),
(133, 138, '', 138, 'lpg-000', 10, 450, 5000, '2025-11-30', 'Delivery', 500.00),
(134, 139, '', 135, 'lpg-000', 10, 450, 5000, '2025-11-30', 'Delivery', 500.00),
(135, 140, '', 135, 'lpg-000 (5kg)', 2, 450, 1000, '2025-12-04', 'Delivery', 100.00),
(136, 141, '', 90, 'lpg-001 (11kg)', 1, 1000, 1000, '2025-12-04', 'Pick-up', 0.00),
(137, 142, '', 139, 'lpg-000 (5kg)', 5, 1000, 5000, '2025-12-04', 'Pick-up', 0.00),
(138, 143, '', 140, 'lpg-000 (5kg)', 1, 1000, 1000, '2025-12-06', 'Pick-up', 0.00),
(139, 144, '', 140, 'lpg-000 (5kg)', 2, 1000, 2100, '2025-12-06', 'Delivery', 100.00),
(140, 145, '', 141, 'lpg-000 (5kg)', 3, 450, 1350, '2025-12-06', 'Pick-up', 0.00),
(141, 146, '', 141, 'lpg-000 (5kg)', 2, 450, 900, '2025-12-06', 'Pick-up', 0.00),
(142, 147, '', 141, 'lpg-000 (5kg)', 90, 450, 40500, '2025-12-06', 'Pick-up', 0.00),
(143, 150, '', 141, 'lpg-000 (5kg)', 5, 450, 2250, '2026-01-03', 'Pick-up', 0.00),
(144, 151, '', 90, 'lpg-001 (11kg)', 5, 1000, 5000, '2026-01-20', 'Pick-up', 0.00),
(145, 152, '', 141, 'lpg-000 (5kg)', 1, 450, 450, '2026-01-31', 'Pick-up', 0.00),
(146, 153, '', 141, 'lpg-000 (5kg)', 1, 450, 500, '2026-02-08', 'Delivery', 50.00),
(147, 154, '', 141, 'lpg-000 (5kg)', 1, 450, 500, '2026-02-08', 'Delivery', 50.00),
(148, 155, '', 141, 'lpg-000 (5kg)', 1, 450, 500, '2026-02-08', 'Delivery', 50.00),
(149, 156, '', 141, 'lpg-000 (5kg)', 2, 450, 1000, '2026-02-08', 'Delivery', 100.00),
(150, 157, '', 141, 'lpg-000 (5kg)', 2, 450, 1000, '2026-02-08', 'Delivery', 100.00),
(151, 158, '', 141, 'lpg-000 (5kg)', 8, 450, 3600, '2026-02-08', 'Pick up', 0.00),
(152, 159, '', 141, 'lpg-000 (5kg)', 9, 450, 4050, '2026-02-08', 'Pick up', 0.00),
(153, 160, '', 141, 'lpg-000 (5kg)', 1, 450, 500, '2026-02-08', NULL, 0.00),
(154, 161, '', 141, 'lpg-000 (5kg)', 5, 450, 2500, '2026-02-08', 'Delivery', 250.00);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_invoice_details_archive`
--

CREATE TABLE `tbl_invoice_details_archive` (
  `archive_detail_id` int(11) NOT NULL,
  `detail_id` int(11) DEFAULT NULL,
  `invoice_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `qty` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `service_type` varchar(100) DEFAULT NULL,
  `additional_fee` decimal(10,2) DEFAULT NULL,
  `archived_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_invoice_details_archive`
--

INSERT INTO `tbl_invoice_details_archive` (`archive_detail_id`, `detail_id`, `invoice_id`, `product_id`, `product_name`, `qty`, `price`, `total_price`, `service_type`, `additional_fee`, `archived_at`) VALUES
(1, 153, 160, 141, 'lpg-000 (5kg)', 1, 450.00, 500.00, 'Delivery', 50.00, '2026-02-08 18:10:04');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_product`
--

CREATE TABLE `tbl_product` (
  `pid` int(11) NOT NULL,
  `barcode` int(11) NOT NULL,
  `product` varchar(200) NOT NULL,
  `category` varchar(200) NOT NULL,
  `description` varchar(200) NOT NULL,
  `servicetype` varchar(100) DEFAULT NULL,
  `additionalfee` decimal(10,2) DEFAULT NULL,
  `stock` int(11) NOT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `expirydate` date DEFAULT NULL,
  `purchaseprice` float NOT NULL,
  `saleprice` float NOT NULL,
  `image` varchar(200) NOT NULL,
  `valvetype` varchar(100) NOT NULL DEFAULT 'Roskas (threaded)',
  `addedstock` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_product`
--

INSERT INTO `tbl_product` (`pid`, `barcode`, `product`, `category`, `description`, `servicetype`, `additionalfee`, `stock`, `brand`, `expirydate`, `purchaseprice`, `saleprice`, `image`, `valvetype`, `addedstock`) VALUES
(87, 0, 'lpg-003 (50kg)', '50 kg (Extra Large)', 'household', 'Pick-up', 0.00, 100, 'Pryce Gas', '2026-01-02', 3800, 4200, '6906eab59ad61.jpg', 'Roskas (threaded)', 0),
(89, 0, 'lpg-002 (22kg)', '22 Kg (Large)', 'houseold', 'Pick-up', 0.00, 100, 'Pryce Gas', '2026-11-02', 1700, 1850, '692c0a0649e53.jpg', 'Roskas (threaded)', 0),
(90, 0, 'lpg-001 (11kg)', '11 Kg (Standard)', 'houseold', 'Pick-up', 0.00, 95, 'Pryce Gas', '2026-11-02', 900, 1000, '692c0b73a0eff.jpg', 'Roskas (threaded)', 0),
(141, 0, 'lpg-000 (5kg)', '5 kg (Medium)', 'houseold', 'Pick-up', 0.00, 135, 'Pryce Gas', '2026-11-04', 400, 450, '692c0b73a0eff.jpg', 'Roskas (threaded)', 2);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_taxdis`
--

CREATE TABLE `tbl_taxdis` (
  `taxdis_id` int(11) NOT NULL,
  `sgst` float NOT NULL,
  `cgst` float NOT NULL,
  `discount` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_taxdis`
--

INSERT INTO `tbl_taxdis` (`taxdis_id`, `sgst`, `cgst`, `discount`) VALUES
(1, 2.3, 2.5, 20),
(2, 50, 55, 50),
(3, 2, 3, 50);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_user`
--

CREATE TABLE `tbl_user` (
  `userid` int(11) NOT NULL,
  `username` varchar(200) NOT NULL,
  `useremail` varchar(200) NOT NULL,
  `userpassword` varchar(200) NOT NULL,
  `role` varchar(50) NOT NULL,
  `userage` int(200) NOT NULL,
  `useraddress` varchar(200) NOT NULL,
  `usercontact` int(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_user`
--

INSERT INTO `tbl_user` (`userid`, `username`, `useremail`, `userpassword`, `role`, `userage`, `useraddress`, `usercontact`) VALUES
(13, 'SPM LPG TRADING', 'isagani@gmail.com', 'isagani', 'Admin', 20, 'subic', 2147483647),
(16, 'admin 2', 'admin@gmail.com', 'admin', 'Admin', 20, 'subic', 98765432),
(17, 'isagani', 'user@gmail.com', '00000', 'User', 21, 'san isidro', 1254566),
(18, 'don', 'don@gmail.com', '12345', 'User', 23, 'cawag', 1234567),
(19, 'judy', 'judy@gmail.com', '2424', 'User', 23, 'matain', 4561239);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `stock_history`
--
ALTER TABLE `stock_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `tbl_archive_activity_log`
--
ALTER TABLE `tbl_archive_activity_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_invoice_id` (`invoice_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `tbl_category`
--
ALTER TABLE `tbl_category`
  ADD PRIMARY KEY (`catid`);

--
-- Indexes for table `tbl_invoice`
--
ALTER TABLE `tbl_invoice`
  ADD PRIMARY KEY (`invoice_id`);

--
-- Indexes for table `tbl_invoice_archive`
--
ALTER TABLE `tbl_invoice_archive`
  ADD PRIMARY KEY (`archive_id`),
  ADD KEY `idx_invoice_id` (`invoice_id`),
  ADD KEY `idx_order_date` (`order_date`),
  ADD KEY `idx_archived_at` (`archived_at`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_archived_by` (`archived_by`);

--
-- Indexes for table `tbl_invoice_details`
--
ALTER TABLE `tbl_invoice_details`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_invoice_details_archive`
--
ALTER TABLE `tbl_invoice_details_archive`
  ADD PRIMARY KEY (`archive_detail_id`),
  ADD KEY `idx_invoice_id` (`invoice_id`),
  ADD KEY `idx_product_id` (`product_id`),
  ADD KEY `idx_archived_at` (`archived_at`);

--
-- Indexes for table `tbl_product`
--
ALTER TABLE `tbl_product`
  ADD PRIMARY KEY (`pid`);

--
-- Indexes for table `tbl_taxdis`
--
ALTER TABLE `tbl_taxdis`
  ADD PRIMARY KEY (`taxdis_id`);

--
-- Indexes for table `tbl_user`
--
ALTER TABLE `tbl_user`
  ADD PRIMARY KEY (`userid`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=139;

--
-- AUTO_INCREMENT for table `stock_history`
--
ALTER TABLE `stock_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_archive_activity_log`
--
ALTER TABLE `tbl_archive_activity_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_category`
--
ALTER TABLE `tbl_category`
  MODIFY `catid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `tbl_invoice`
--
ALTER TABLE `tbl_invoice`
  MODIFY `invoice_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=162;

--
-- AUTO_INCREMENT for table `tbl_invoice_archive`
--
ALTER TABLE `tbl_invoice_archive`
  MODIFY `archive_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tbl_invoice_details`
--
ALTER TABLE `tbl_invoice_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=155;

--
-- AUTO_INCREMENT for table `tbl_invoice_details_archive`
--
ALTER TABLE `tbl_invoice_details_archive`
  MODIFY `archive_detail_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tbl_product`
--
ALTER TABLE `tbl_product`
  MODIFY `pid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=142;

--
-- AUTO_INCREMENT for table `tbl_taxdis`
--
ALTER TABLE `tbl_taxdis`
  MODIFY `taxdis_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tbl_user`
--
ALTER TABLE `tbl_user`
  MODIFY `userid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

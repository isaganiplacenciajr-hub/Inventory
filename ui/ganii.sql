-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 04, 2025 at 01:26 PM
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
-- Database: `ganii`
--

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
(33, '2025-10-31', 2300, 20, 2.3, 2.5, 1950.4, 'cash', 0.4, 1950),
(34, '2025-10-31', 2250, 20, 2.3, 2.5, 1908, 'cash', -92, 2000),
(35, '2025-10-31', 2250, 20, 2.3, 2.5, 1908, 'cash', -92, 2000),
(36, '2025-10-31', 5000, 20, 2.3, 2.5, 4240, 'cash', -260, 4500),
(37, '2025-10-31', 450, 0, 0, 0, 450, 'cash', -50, 500),
(38, '2025-10-31', 2250, 20, 0, 0, 1800, 'cash', 1800, 360),
(39, '2025-10-31', 5000, 20, 0, 0, 4000, 'cash', 4000, 4000),
(40, '2025-10-31', 2300, 0, 0, 0, 2300, 'cash', 0, 2300),
(41, '2025-11-02', 450, 20, 0, 0, 360, 'cash', -40, 400),
(42, '2025-11-02', 2250, 0, 0, 0, 2250, 'cash', -50, 2300),
(43, '2025-11-02', 2250, 0, 0, 0, 2250, 'cash', -50, 2300),
(44, '2025-11-02', 950, 0, 0, 0, 950, 'cash', 0, 950),
(45, '2025-11-02', 450, 20, 0, 0, 360, 'cash', -40, 400),
(46, '2025-11-02', 450, 20, 0, 0, 360, 'cash', -40, 400),
(47, '2025-11-02', 1850, 0, 0, 0, 1850, 'cash', -50, 1900),
(48, '2025-11-02', 900, 0, 0, 0, 900, 'cash', 0, 900),
(49, '2025-11-02', 2250, 0, 0, 0, 2250, 'cash', -50, 2300),
(50, '2025-11-03', 3500, 20, 0, 0, 2800, 'cash', 0, 2800),
(51, '2025-11-03', 450, 0, 0, 0, 450, 'cash', 0, 450),
(52, '2025-11-03', 500, 20, 0, 0, 400, 'cash', 400, 0),
(53, '2025-11-03', 5000, 0, 0, 0, 5000, 'cash', 0, 5000);

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
(1, 1, '141432', 3, 'nike t shirt', 1, 45, 55, '2025-09-26', NULL, 0.00),
(2, 3, '141432', 3, 'nike t shirt', 1, 44, 55, '2025-09-26', NULL, 0.00),
(3, 5, '141432', 3, 'nike t shirt', 10, 43, 550, '2025-09-26', NULL, 0.00),
(4, 6, '', 5, 'Petron', 5, 10, 5000, '2025-09-26', NULL, 0.00),
(5, 7, '', 2, 'lpg tank', 1, 10, 550, '2025-09-26', NULL, 0.00),
(6, 8, '11 Kg (Standard)', 21, 'STANDARD', 1, 12, 500, '2025-09-26', NULL, 0.00),
(7, 9, '2.7 kg (Small)', 26, 'SMALL', 1, 10, 500, '2025-09-26', NULL, 0.00),
(8, 10, '', 26, 'SMALL', 1, 9, 500, '2025-09-26', NULL, 0.00),
(9, 12, '2.7 kg (Small)', 33, 'SMALL', 1, 10, 550, '2025-09-26', NULL, 0.00),
(10, 13, '0', 31, 'Standard ', 10, 13, 7000, '2025-09-27', NULL, 0.00),
(11, 14, '11', 46, 'Lpg-002', 10, 45, 10000, '2025-10-01', NULL, 0.00),
(12, 15, '5', 44, 'Lpg-001', 5, 50, 5000, '2025-10-01', NULL, 0.00),
(13, 17, 'undefined', 44, 'Lpg-001', 1, 31, 1000, '2025-10-26', NULL, 0.00),
(14, 20, '', 44, 'Lpg-001', 1, 1000, 1000, '2025-10-30', 'Pick up', 0.00),
(15, 21, '', 61, 'testtt', 1, 1000, 1000, '2025-10-30', 'Pick up', 0.00),
(16, 22, '', 44, 'Lpg-001', 1, 1000, 1050, '2025-10-30', 'Delivery', 50.00),
(17, 23, '', 62, 'spm 50', 3, 450, 1350, '2025-10-30', 'Pick up', 0.00),
(18, 24, '', 63, 'tank', 4, 700, 2800, '2025-10-30', 'Pick up', 0.00),
(19, 25, '', 64, 'tank 2', 6, 700, 4200, '2025-10-30', 'Pick up', 0.00),
(20, 26, '', 65, 'lpgg', 5, 450, 2300, '2025-10-30', 'Delivery', 50.00),
(21, 27, '', 70, 'carlo', 1, 450, 450, '2025-10-30', 'Pick up', 0.00),
(22, 28, '', 71, '001', 1, 450, 450, '2025-10-31', 'Pick up', 0.00),
(23, 29, '', 75, 'testing', 5, 450, 2300, '2025-10-31', 'Delivery', 50.00),
(24, 30, '', 63, 'tank', 5, 700, 3550, '2025-10-31', 'Delivery', 50.00),
(25, 31, '', 76, '002', 5, 450, 2300, '2025-10-31', 'Delivery', 50.00),
(26, 32, '', 77, '004', 5, 450, 2300, '2025-10-31', 'Delivery', 50.00),
(27, 33, '', 71, '001', 5, 450, 2300, '2025-10-31', 'Delivery', 50.00),
(28, 34, '', 71, '001', 5, 450, 2250, '2025-10-31', 'Pick up', 0.00),
(29, 35, '', 71, '001', 5, 450, 2250, '2025-10-31', 'Pick up', 0.00),
(30, 36, '', 79, '020', 5, 1000, 5000, '2025-10-31', 'Pick up', 0.00),
(31, 37, '', 71, '001', 1, 450, 450, '2025-10-31', 'Pick up', 0.00),
(32, 38, '', 80, '030', 5, 450, 2250, '2025-10-31', 'Pick up', 0.00),
(33, 39, '', 84, 'o14', 5, 1000, 5000, '2025-10-31', 'Pick up', 0.00),
(34, 40, '', 85, '15', 5, 450, 2300, '2025-10-31', 'Delivery', 50.00),
(35, 41, '', 85, '15', 1, 450, 450, '2025-11-02', 'Pick up', 0.00),
(36, 42, '', 86, 'lpg-000', 5, 450, 2250, '2025-11-02', 'Pick up', 0.00),
(37, 43, '', 86, 'lpg-000', 5, 450, 2250, '2025-11-02', 'Pick up', 0.00),
(38, 44, '', 85, '15', 2, 450, 950, '2025-11-02', 'Delivery', 50.00),
(39, 45, '', 85, '15', 1, 450, 450, '2025-11-02', 'Pick up', 0.00),
(40, 46, '', 87, 'lpg', 1, 450, 450, '2025-11-02', 'Pick up', 0.00),
(41, 47, '', 86, 'lpg-000', 4, 450, 1850, '2025-11-02', 'Delivery', 50.00),
(42, 48, '', 87, 'lpg', 2, 450, 900, '2025-11-02', 'Pick up', 0.00),
(43, 49, '', 85, '15', 5, 450, 2250, '2025-11-02', 'Pick up', 0.00),
(44, 50, '', 85, '15', 7, 450, 3500, '2025-11-03', 'Delivery', 350.00),
(45, 51, '', 85, '15', 1, 450, 450, '2025-11-03', 'Pick up', 0.00),
(46, 52, '', 85, '15', 1, 450, 500, '2025-11-03', 'Delivery', 50.00),
(47, 53, '', 87, 'lpg', 10, 450, 5000, '2025-11-03', 'Delivery', 500.00);

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
  `image` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_product`
--

INSERT INTO `tbl_product` (`pid`, `barcode`, `product`, `category`, `description`, `servicetype`, `additionalfee`, `stock`, `brand`, `expirydate`, `purchaseprice`, `saleprice`, `image`) VALUES
(85, 0, '15', '5 kg (Medium)', 'houseold', 'Delivery', 50.00, 22, 'regasco', '2025-10-30', 400, 450, '69041ca8cc4bb.jpg'),
(86, 0, 'lpg-000', '5 kg (Medium)', 'household', 'Delivery', 50.00, 36, 'Pryce gas', '2026-01-02', 400, 450, '6906d0f17dc6d.jpg'),
(87, 0, 'lpg', '5 kg (Medium)', 'household', 'Pick-up', 0.00, 37, 'Pryce gas', '2026-01-02', 400, 450, '6906eab59ad61.jpg'),
(88, 0, 'lpg-001', '11 Kg (Standard)', 'houseold', 'Delivery', 50.00, 30, 'regasco', '2026-11-02', 900, 1000, '690722ceb7c7f.jpg'),
(89, 0, 'lpg-001', '5 kg (Medium)', 'houseold', 'Delivery', 50.00, 30, 'regasco', '2026-11-02', 400, 450, '69072d611cc9b.jpg'),
(90, 0, 'lpg-001', '11 Kg (Standard)', 'houseold', 'Pick-up', 0.00, 30, 'regasco', '2026-11-02', 900, 1000, '6908375bced17.jpg'),
(91, 0, 'eqwewqewq', '11 Kg (Standard)', 'gas', 'Pick-up', 0.00, 6, 'bear brand', NULL, 900, 1000, '6909ef352004f.jpg');

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
(14, 'staff', 'staff@gmail.com', '12345', 'User', 20, 'subic', 156595);

--
-- Indexes for dumped tables
--

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
-- Indexes for table `tbl_invoice_details`
--
ALTER TABLE `tbl_invoice_details`
  ADD PRIMARY KEY (`id`);

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
-- AUTO_INCREMENT for table `tbl_category`
--
ALTER TABLE `tbl_category`
  MODIFY `catid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `tbl_invoice`
--
ALTER TABLE `tbl_invoice`
  MODIFY `invoice_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `tbl_invoice_details`
--
ALTER TABLE `tbl_invoice_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `tbl_product`
--
ALTER TABLE `tbl_product`
  MODIFY `pid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=92;

--
-- AUTO_INCREMENT for table `tbl_taxdis`
--
ALTER TABLE `tbl_taxdis`
  MODIFY `taxdis_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tbl_user`
--
ALTER TABLE `tbl_user`
  MODIFY `userid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

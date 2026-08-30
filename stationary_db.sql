-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 30, 2026 at 06:24 PM
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
-- Database: `stationary_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `cancel_request`
--

CREATE TABLE `cancel_request` (
  `request_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `reason` text NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  `admin_note` text DEFAULT NULL,
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cancel_request`
--

INSERT INTO `cancel_request` (`request_id`, `order_id`, `member_id`, `reason`, `photo`, `status`, `admin_note`, `requested_at`, `reviewed_at`, `reviewed_by`) VALUES
(6, 20, 8, 'choose wrong item', NULL, 'Approved', NULL, '2026-08-28 06:32:56', '2026-08-28 06:33:17', 1);

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`id`, `member_id`, `created_at`, `updated_at`) VALUES
(1, 8, '2026-08-26 20:04:47', '2026-08-28 15:20:01'),
(2, 2, '2026-08-28 17:51:33', '2026-08-30 23:14:29');

-- --------------------------------------------------------

--
-- Table structure for table `cart_item`
--

CREATE TABLE `cart_item` (
  `id` int(11) NOT NULL,
  `cart_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart_item`
--

INSERT INTO `cart_item` (`id`, `cart_id`, `product_id`, `quantity`) VALUES
(1, 0, 26, 1);

-- --------------------------------------------------------

--
-- Table structure for table `category`
--

CREATE TABLE `category` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `category`
--

INSERT INTO `category` (`id`, `name`) VALUES
(1, 'Art'),
(2, 'File & Folder'),
(5, 'Notebooks'),
(4, 'Paper'),
(3, 'Pen & Pencil');

-- --------------------------------------------------------

--
-- Table structure for table `login_log`
--

CREATE TABLE `login_log` (
  `log_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `login_time` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_log`
--

INSERT INTO `login_log` (`log_id`, `member_id`, `login_time`) VALUES
(1, 8, '2026-08-28 14:56:09'),
(2, 3, '2026-08-28 15:15:01'),
(3, 8, '2026-08-28 15:15:20'),
(4, 2, '2026-08-28 17:51:09'),
(5, 3, '2026-08-28 17:54:29'),
(6, 3, '2026-08-29 16:45:52'),
(7, 2, '2026-08-29 16:58:19'),
(8, 3, '2026-08-29 19:12:26'),
(9, 2, '2026-08-29 19:30:43'),
(10, 2, '2026-08-29 20:02:07'),
(11, 1, '2026-08-29 20:03:06'),
(12, 3, '2026-08-29 20:04:20'),
(13, 2, '2026-08-29 21:22:56'),
(14, 2, '2026-08-30 01:24:15'),
(15, 1, '2026-08-30 19:55:25'),
(16, 2, '2026-08-30 23:12:50'),
(17, 1, '2026-08-30 23:15:53'),
(18, 2, '2026-08-30 23:23:39');

-- --------------------------------------------------------

--
-- Table structure for table `member`
--

CREATE TABLE `member` (
  `member_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `role` enum('Member','Admin','Super Admin') NOT NULL DEFAULT 'Member',
  `status` enum('Active','Blocked') NOT NULL DEFAULT 'Active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `last_active` datetime DEFAULT NULL,
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `email_otp` char(6) DEFAULT NULL,
  `email_otp_expires` datetime DEFAULT NULL,
  `failed_attempts` int(11) NOT NULL DEFAULT 0,
  `locked_until` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `member`
--

INSERT INTO `member` (`member_id`, `username`, `email`, `phone`, `password`, `photo`, `role`, `status`, `created_at`, `updated_at`, `last_login`, `last_active`, `reset_token`, `reset_expires`, `address`, `email_verified`, `email_otp`, `email_otp_expires`, `failed_attempts`, `locked_until`) VALUES
(1, 'admin', 'admin@example.com', '0123456789', '$2b$10$qCOf3B0hjvPn.h2c3nuvceF57ANmQwe4FFKfYbCp8Frs//S8rpJ7K', '6a941a4be63bc.jpg', 'Admin', 'Active', '2026-08-15 14:24:52', '2026-08-30 19:55:56', NULL, '2026-08-30 23:23:22', NULL, NULL, NULL, 1, NULL, NULL, 0, NULL),
(2, 'Chan', 'chanjw-wm24@student.tarc.edu.my', '011-1141-9185', '$2y$10$09.stYRWravLKfmp./FzmeAtdxBQkKaY8JjlhYG9qtlBr5RM5DSBW', '6a944e883a1f5.jpg', 'Member', 'Active', '2026-08-15 14:28:30', '2026-08-30 23:38:48', NULL, '2026-08-31 00:22:33', '338f173a89d602ef445e6f75d2127f57cf6349ea31bef1e046c39a2a71cb23a8', '2026-08-28 18:50:33', '1111', 1, NULL, NULL, 0, NULL),
(3, 'superadmin', 'superadmin@example.com', '0123456789', '$2b$10$OGfnO20QDyOC1n/UTzhAUe5Y.G1da.zcTTDPPFRu9vdgF/eg.HUXe', NULL, 'Super Admin', 'Active', '2026-08-16 23:17:36', NULL, NULL, '2026-08-29 20:04:43', NULL, NULL, NULL, 1, NULL, NULL, 0, NULL),
(4, 'Ken', 'hehe@gmail.com', '0123456789', '$2y$10$aBQA1qQOGgMk/idBTbZ7leOgkaoEthzv.XZozorUA2DO5KwDJaiuG', '6a839c36f1079.jpg', 'Member', 'Active', '2026-08-18 07:38:49', '2026-08-18 07:41:43', NULL, NULL, '5e87153ed843fbac62fcb724ae57cfd403719b1041cb8487ba80d26b877e3616', '2026-08-18 08:39:43', NULL, 1, NULL, NULL, 2, NULL),
(5, 'admin2', '0602@gmail.com', '0123456789', '$2y$10$FL.bGhGM2jJvWf9jtG0zpeJN8hnFqHSjLzTj6AvRBabXrUSBJqiiG', NULL, 'Admin', 'Active', '2026-08-18 18:06:03', '2026-08-29 20:04:31', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, NULL),
(6, 'Emily', '123@gmail.com', '0123456789', '$2y$10$2NHac2hnrgImXiCkOVCDKuHNt3ZDHVYpyBdYJYsUUkRU29gY423Py', NULL, 'Member', 'Active', '2026-08-18 18:32:35', NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, NULL),
(7, 'yuan', 'yongqiyuan06@gmail.com', '0123456789', '$2y$10$VU0r4UXjgzLKtBmil43dl.nOZsWBe4CnSCBP7i9KXZR21fGCxUp6m', '6a865b0b44495.jpg', 'Member', 'Active', '2026-08-20 09:40:27', NULL, NULL, NULL, NULL, NULL, '-', 1, '422994', '2026-08-20 09:55:27', 0, NULL),
(8, 'yqy', 'yongq-wm24@student.tarc.edu.my', '0123456789', '$2y$10$07iKyQhwCkhwDG7QLg334eDN.ZcvWmJWegrd5qMXJSY7hs3vwTCXy', '6a86bf4d7d1b0.jpg', 'Member', 'Active', '2026-08-20 16:48:13', NULL, NULL, '2026-08-28 15:27:43', '639a48a6688313e52cd6a8a86e469d5e29fb05f8b2330397d0dd50a508bee2a6', '2026-08-21 16:43:14', '-', 1, NULL, NULL, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `member_address`
--

CREATE TABLE `member_address` (
  `address_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `label` varchar(50) DEFAULT NULL,
  `address` varchar(255) NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `member_address`
--

INSERT INTO `member_address` (`address_id`, `member_id`, `label`, `address`, `is_default`, `created_at`, `updated_at`) VALUES
(1, 2, NULL, 'aaabbbccc', 0, '2026-08-30 22:59:52', NULL),
(2, 7, NULL, '-', 1, '2026-08-30 22:59:52', NULL),
(3, 8, NULL, '-', 1, '2026-08-30 22:59:52', NULL),
(4, 2, 'Home', '123', 1, '2026-08-30 23:16:21', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `order_date` datetime NOT NULL DEFAULT current_timestamp(),
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `order_status` varchar(20) NOT NULL DEFAULT 'Pending',
  `shipping_address` varchar(255) DEFAULT NULL,
  `address_id` int(11) DEFAULT NULL,
  `tracking_number` varchar(20) DEFAULT NULL,
  `payment_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `member_id`, `order_date`, `total_amount`, `order_status`, `shipping_address`, `address_id`, `tracking_number`, `payment_id`) VALUES
(18, 8, '2026-08-28 09:53:09', 215.00, 'Shipped', '-', NULL, 'JT930959211237', 1),
(19, 8, '2026-08-28 14:31:43', 31.50, 'Processing', '222333', NULL, NULL, 1),
(20, 8, '2026-08-28 14:32:15', 49.50, 'Cancelled', '222333', NULL, NULL, 1),
(21, 8, '2026-08-28 15:25:16', 20.00, 'Completed', '222333', NULL, 'JT876217190742', 1),
(22, 2, '2026-08-28 17:51:42', 70.00, 'Processing', '123 hehehehe', NULL, NULL, 3),
(23, 2, '2026-08-28 17:52:58', 420.00, 'Processing', '123 hehehehe', NULL, NULL, 3),
(24, 2, '2026-08-29 19:31:03', 2.50, 'Processing', '321', NULL, NULL, 2),
(25, 2, '2026-08-29 19:36:21', 55.00, 'Processing', '2222', NULL, NULL, 1),
(26, 2, '2026-08-31 00:22:17', 90.00, 'Processing', 'aaabbbccc', 1, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `order_item`
--

CREATE TABLE `order_item` (
  `order_item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_item`
--

INSERT INTO `order_item` (`order_item_id`, `order_id`, `product_id`, `quantity`, `unit_price`) VALUES
(25, 18, 19, 3, 35.00),
(26, 18, 21, 2, 55.00),
(27, 19, 19, 1, 35.00),
(28, 20, 21, 1, 55.00),
(29, 21, 37, 2, 15.00),
(30, 22, 19, 2, 35.00),
(31, 23, 22, 28, 15.00),
(32, 24, 20, 1, 2.50),
(33, 25, 21, 1, 55.00),
(34, 26, 3, 1, 90.00);

-- --------------------------------------------------------

--
-- Table structure for table `order_status_log`
--

CREATE TABLE `order_status_log` (
  `log_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `status` varchar(20) NOT NULL,
  `changed_at` datetime NOT NULL DEFAULT current_timestamp(),
  `note` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_status_log`
--

INSERT INTO `order_status_log` (`log_id`, `order_id`, `status`, `changed_at`, `note`) VALUES
(47, 18, 'Processing', '2026-08-28 09:53:11', NULL),
(48, 18, 'Shipped', '2026-08-28 14:29:51', NULL),
(49, 19, 'Processing', '2026-08-28 14:31:45', NULL),
(50, 20, 'Cancelled', '2026-08-28 14:33:17', 'Approved cancellation request — choose wrong item'),
(51, 21, 'Processing', '2026-08-28 15:25:20', NULL),
(52, 21, 'Shipped', '2026-08-28 15:26:46', NULL),
(53, 21, 'Completed', '2026-08-28 15:27:13', NULL),
(54, 22, 'Processing', '2026-08-28 17:51:45', NULL),
(55, 23, 'Processing', '2026-08-28 17:53:00', NULL),
(56, 24, 'Processing', '2026-08-29 19:31:05', NULL),
(57, 25, 'Processing', '2026-08-29 19:36:22', NULL),
(58, 26, 'Processing', '2026-08-31 00:22:19', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `pay_id` int(11) NOT NULL,
  `pay_name` varchar(25) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment`
--

INSERT INTO `payment` (`pay_id`, `pay_name`) VALUES
(1, 'Touch n Go'),
(2, 'Debit/Credit Card'),
(3, 'Online Banking');

-- --------------------------------------------------------

--
-- Table structure for table `product`
--

CREATE TABLE `product` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `category_id` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `cost_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `stock_qty` int(11) NOT NULL DEFAULT 0,
  `description` text DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `video_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product`
--

INSERT INTO `product` (`id`, `name`, `category_id`, `price`, `cost_price`, `stock_qty`, `description`, `photo`, `video_url`) VALUES
(3, 'Color Paper', 4, 90.00, 0.00, 99, '', 'color-paper-a887fd.jpg', NULL),
(19, 'Art Highlighter Pen', 1, 35.00, 18.00, 72, '', 'art-highlighter-pen-a10df1.jpg', NULL),
(20, 'Color Files', 2, 2.50, 0.00, 17, '', 'color-files-7af429.jpg', NULL),
(21, 'Blue Notebook', 5, 55.00, 12.00, 5, '', 'blue-notebook-4abc79.jpg', NULL),
(22, 'Ballpen', 3, 15.00, 4.13, 0, 'testing 123', 'ballpen-352f91.jpg', 'https://youtu.be/GpM2CLnVOA4?si=wClet1C7XndoVQIR'),
(23, 'Spiral Notebook', 5, 28.00, 0.00, 59, '', 'spiral-notebook-85e6e8.jpg', NULL),
(24, 'Flip Files', 2, 3.50, 0.00, 17, '', 'flip-files-7878a6.jpg', NULL),
(25, 'Double A A4 Paper', 4, 16.00, 0.00, 180, '', 'double-a-a4-paper-dbc4ca.jpg', NULL),
(26, 'Metallic Marker Pen', 1, 30.00, 0.00, 98, '', 'metallic-marker-pen-e08dd2.jpg', NULL),
(27, 'Gel pen', 3, 14.00, 0.00, 45, '', 'gel-pen-ab23cf.jpg', NULL),
(28, 'Roller Ball Pens', 3, 25.50, 0.00, 66, '', 'roller-ball-pens-26b649.jpg', NULL),
(29, 'IK Yellow A4 Papers', 4, 12.70, 0.00, 11, '', 'ik-yellow-a4-papers-2e075b.jpg', NULL),
(36, 'Bullet Notebooks', 5, 24.85, 5.00, 18, '-', 'bullet-notebooks-26c4cb.jpg', NULL),
(37, 'aaaaa', 3, 15.00, 10.00, 8, 'dwwde\r\nwd\r\nde\r\ndw\r\ne', 'aaaaa-637787.jpg', 'https://youtu.be/GpM2CLnVOA4?si=wClet1C7XndoVQIR');

-- --------------------------------------------------------

--
-- Table structure for table `product_cost_history`
--

CREATE TABLE `product_cost_history` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `cost_price` decimal(10,2) NOT NULL,
  `effective_from` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_cost_history`
--

INSERT INTO `product_cost_history` (`id`, `product_id`, `cost_price`, `effective_from`, `created_at`) VALUES
(3, 22, 3.00, '2026-08-25', '2026-08-25 03:03:02'),
(4, 22, 4.13, '2026-08-25', '2026-08-25 03:04:01'),
(5, 21, 12.00, '2026-08-25', '2026-08-25 03:04:25'),
(6, 19, 15.00, '2026-03-01', '2026-08-25 03:07:07'),
(7, 19, 18.00, '2026-07-01', '2026-08-25 03:07:07'),
(8, 36, 5.00, '2026-08-28', '2026-08-28 02:30:58'),
(9, 37, 10.00, '2026-08-28', '2026-08-28 07:19:02');

-- --------------------------------------------------------

--
-- Table structure for table `product_photo`
--

CREATE TABLE `product_photo` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `photo` varchar(255) NOT NULL,
  `sort_order` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_photo`
--

INSERT INTO `product_photo` (`id`, `product_id`, `photo`, `sort_order`) VALUES
(12, 22, 'ballpen-gallery-8b2e29.jpg', 0),
(13, 22, 'ballpen-gallery-8c9edd.jpg', 1),
(14, 22, 'ballpen-gallery-8e903c.jpg', 2),
(15, 22, 'ballpen-gallery-90f746.jpg', 3),
(16, 22, 'ballpen-gallery-9238b5.jpg', 4),
(17, 22, 'ballpen-gallery-940f71.jpg', 5),
(18, 22, 'ballpen-gallery-956a6e.jpg', 6),
(19, 22, 'ballpen-gallery-974cf3.jpg', 7),
(20, 22, 'ballpen-gallery-9885c4.jpg', 8),
(21, 22, 'ballpen-gallery-99bacf.jpg', 9),
(22, 22, 'ballpen-gallery-9b895a.jpg', 10);

-- --------------------------------------------------------

--
-- Table structure for table `voucher`
--

CREATE TABLE `voucher` (
  `voucher_id` int(11) NOT NULL,
  `code` varchar(30) NOT NULL,
  `discount_type` enum('Fixed','Percentage') NOT NULL,
  `discount_value` decimal(10,2) NOT NULL,
  `max_discount` decimal(10,2) DEFAULT NULL,
  `min_spend` decimal(10,2) NOT NULL DEFAULT 0.00,
  `max_uses` int(11) DEFAULT NULL,
  `used_count` int(11) NOT NULL DEFAULT 0,
  `one_per_member` tinyint(1) NOT NULL DEFAULT 0,
  `valid_from` date NOT NULL,
  `valid_until` date NOT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `voucher`
--

INSERT INTO `voucher` (`voucher_id`, `code`, `discount_type`, `discount_value`, `max_discount`, `min_spend`, `max_uses`, `used_count`, `one_per_member`, `valid_from`, `valid_until`, `status`, `created_at`) VALUES
(6, 'WELCOME10', 'Percentage', 10.00, NULL, 0.00, NULL, 1, 1, '2026-08-21', '2026-09-25', 'Active', '2026-08-28 06:28:27'),
(7, 'HELLO10', 'Percentage', 10.00, NULL, 0.00, 30, 0, 0, '2026-08-14', '2026-10-02', 'Active', '2026-08-28 06:29:04'),
(8, 'TEST123', 'Fixed', 10.00, NULL, 20.00, 2, 1, 1, '2026-08-28', '2026-08-29', 'Active', '2026-08-28 07:23:46');

-- --------------------------------------------------------

--
-- Table structure for table `voucher_usage`
--

CREATE TABLE `voucher_usage` (
  `usage_id` int(11) NOT NULL,
  `voucher_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `used_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `voucher_usage`
--

INSERT INTO `voucher_usage` (`usage_id`, `voucher_id`, `member_id`, `order_id`, `used_at`) VALUES
(1, 6, 8, 19, '2026-08-28 06:31:43'),
(3, 8, 8, 21, '2026-08-28 07:25:16');

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `wishlist_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wishlist`
--

INSERT INTO `wishlist` (`wishlist_id`, `member_id`, `product_id`, `created_at`) VALUES
(1, 7, 22, '2026-08-28 01:18:45');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cancel_request`
--
ALTER TABLE `cancel_request`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `member_id` (`member_id`),
  ADD KEY `reviewed_by` (`reviewed_by`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `member_id` (`member_id`);

--
-- Indexes for table `cart_item`
--
ALTER TABLE `cart_item`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cart_id` (`cart_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `login_log`
--
ALTER TABLE `login_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `member_id` (`member_id`);

--
-- Indexes for table `member`
--
ALTER TABLE `member`
  ADD PRIMARY KEY (`member_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `member_address`
--
ALTER TABLE `member_address`
  ADD PRIMARY KEY (`address_id`),
  ADD KEY `idx_member_id` (`member_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `member_id` (`member_id`),
  ADD KEY `orders_payment_fk` (`payment_id`),
  ADD KEY `fk_orders_address` (`address_id`);

--
-- Indexes for table `order_item`
--
ALTER TABLE `order_item`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `order_status_log`
--
ALTER TABLE `order_status_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`pay_id`);

--
-- Indexes for table `product`
--
ALTER TABLE `product`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `product_cost_history`
--
ALTER TABLE `product_cost_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `product_photo`
--
ALTER TABLE `product_photo`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `voucher`
--
ALTER TABLE `voucher`
  ADD PRIMARY KEY (`voucher_id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `voucher_usage`
--
ALTER TABLE `voucher_usage`
  ADD PRIMARY KEY (`usage_id`),
  ADD KEY `voucher_id` (`voucher_id`),
  ADD KEY `member_id` (`member_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`wishlist_id`),
  ADD UNIQUE KEY `unique_wishlist_item` (`member_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cancel_request`
--
ALTER TABLE `cancel_request`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `cart_item`
--
ALTER TABLE `cart_item`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `category`
--
ALTER TABLE `category`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `login_log`
--
ALTER TABLE `login_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `member`
--
ALTER TABLE `member`
  MODIFY `member_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `member_address`
--
ALTER TABLE `member_address`
  MODIFY `address_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `order_item`
--
ALTER TABLE `order_item`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `order_status_log`
--
ALTER TABLE `order_status_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `product`
--
ALTER TABLE `product`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `product_cost_history`
--
ALTER TABLE `product_cost_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `product_photo`
--
ALTER TABLE `product_photo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `voucher`
--
ALTER TABLE `voucher`
  MODIFY `voucher_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `voucher_usage`
--
ALTER TABLE `voucher_usage`
  MODIFY `usage_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `wishlist_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cancel_request`
--
ALTER TABLE `cancel_request`
  ADD CONSTRAINT `cancel_request_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`),
  ADD CONSTRAINT `cancel_request_ibfk_2` FOREIGN KEY (`member_id`) REFERENCES `member` (`member_id`),
  ADD CONSTRAINT `cancel_request_ibfk_3` FOREIGN KEY (`reviewed_by`) REFERENCES `member` (`member_id`);

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `member` (`member_id`);

--
-- Constraints for table `login_log`
--
ALTER TABLE `login_log`
  ADD CONSTRAINT `login_log_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `member` (`member_id`);

--
-- Constraints for table `member_address`
--
ALTER TABLE `member_address`
  ADD CONSTRAINT `fk_member_address_member` FOREIGN KEY (`member_id`) REFERENCES `member` (`member_id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_address` FOREIGN KEY (`address_id`) REFERENCES `member_address` (`address_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

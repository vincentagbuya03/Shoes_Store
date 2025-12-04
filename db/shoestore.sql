-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Dec 01, 2025 at 02:21 AM
-- Server version: 8.4.3
-- PHP Version: 8.3.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `shoestore`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `admin_id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(120) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`admin_id`, `name`, `email`, `password`, `created_at`) VALUES
(1, 'Vincent Agbuya', 'vincentagbuya3@gmail.com', '$2y$10$g35fTCZzZT4YEWuO8hNCteI8iLt1jZxWGUhOB9Fn/zSLk5Jp17MHO', '2025-11-24 10:29:46');

-- --------------------------------------------------------

--
-- Table structure for table `brand`
--

CREATE TABLE `brand` (
  `brand_id` int NOT NULL,
  `brand_name` varchar(150) NOT NULL,
  `brand_logo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `brand`
--

INSERT INTO `brand` (`brand_id`, `brand_name`, `brand_logo`, `created_at`) VALUES
(1, 'Nike', 'upload\\brand-picture\\nike.png', '2025-11-14 08:56:01'),
(2, 'Adidas', 'upload\\brand-picture\\adidas.png', '2025-11-14 11:43:43'),
(3, 'World Balance', 'upload\\brand-picture\\worldbalance.png', '2025-11-14 12:23:11'),
(4, 'New Balance', 'upload\\brand-picture\\newbalance.png', '2025-11-14 12:25:22'),
(5, 'Puma', 'upload\\brand-picture\\puma.png', '2025-11-14 12:26:53');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `cart_id` int NOT NULL,
  `customer_id` int NOT NULL,
  `variant_id` int NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `added_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`cart_id`, `customer_id`, `variant_id`, `quantity`, `added_at`, `updated_at`) VALUES
(18, 2, 10, 1, '2025-11-29 15:19:51', '2025-11-29 15:19:51');

-- --------------------------------------------------------

--
-- Table structure for table `color`
--

CREATE TABLE `color` (
  `color_id` int NOT NULL,
  `color_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `color`
--

INSERT INTO `color` (`color_id`, `color_name`) VALUES
(2, 'Black'),
(4, 'Blue'),
(5, 'Green'),
(1, 'Orange'),
(3, 'Red'),
(6, 'White');

-- --------------------------------------------------------

--
-- Table structure for table `customer`
--

CREATE TABLE `customer` (
  `customer_id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(120) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `customer`
--

INSERT INTO `customer` (`customer_id`, `name`, `email`, `phone`, `address`, `password`, `created_at`) VALUES
(1, 'Glend Allyzza Loresco', 'glend143@gmail.com', '09122354762', '13 Barangay St. BRGY. Sitio, Di Mahanap City, Biringan', '$2y$10$g35fTCZzZT4YEWuO8hNCteI8iLt1jZxWGUhOB9Fn/zSLk5Jp17MHO', '2025-11-14 15:12:45'),
(2, 'Nick Vincent Delos Santos Agbuya', '23sc4114_ms@psu.edu.ph', '09122354762', '13 Balon Kalds Turac San Carlos City Pangasinan', '$2y$10$g35fTCZzZT4YEWuO8hNCteI8iLt1jZxWGUhOB9Fn/zSLk5Jp17MHO', '2025-11-14 15:28:42');

-- --------------------------------------------------------

--
-- Table structure for table `hero_carousel`
--

CREATE TABLE `hero_carousel` (
  `carousel_id` int NOT NULL,
  `image_url` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `sort_order` int DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `hero_carousel`
--

INSERT INTO `hero_carousel` (`carousel_id`, `image_url`, `title`, `description`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'upload\\carousel-picture\\photo_2025-11-14_22-43-16.jpg', 'Slide 1', NULL, 1, 1, '2025-11-14 13:20:47', '2025-11-14 14:43:42'),
(2, 'upload\\carousel-picture\\photo_2025-11-14_21-26-15.jpg', 'Slide 2', NULL, 2, 1, '2025-11-14 13:20:47', '2025-11-14 13:31:22'),
(3, 'upload\\carousel-picture\\photo_2025-11-14_22-02-22.jpg', 'Slide 3', NULL, 3, 1, '2025-11-14 13:20:47', '2025-11-14 14:12:51'),
(4, 'upload\\carousel-picture\\photo_2025-11-16_21-32-12.jpg', 'Slide 4', NULL, 4, 1, '2025-11-14 13:20:47', '2025-11-16 13:47:43'),
(5, 'upload/carousel/hero-5.jpg', 'Slide 5', NULL, 5, 1, '2025-11-14 13:20:47', '2025-11-14 13:20:47');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int NOT NULL,
  `user_type` enum('customer','rider','admin') NOT NULL,
  `user_id` int NOT NULL,
  `type` varchar(80) NOT NULL,
  `payload` json DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int NOT NULL,
  `customer_id` int NOT NULL,
  `rider_id` int DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `payment_method` enum('COD','Card','Gcash') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT 'COD',
  `status` enum('pending','confirmed','delivering','completed','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT 'pending',
  `delivery_proof` varchar(255) DEFAULT NULL,
  `order_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `delivery_lat` decimal(10,7) DEFAULT NULL,
  `delivery_lng` decimal(10,7) DEFAULT NULL,
  `payment_meta` text,
  `payment_proof` varchar(255) DEFAULT NULL,
  `delivery_address` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `customer_id`, `rider_id`, `total_amount`, `payment_method`, `status`, `delivery_proof`, `order_date`, `delivery_lat`, `delivery_lng`, `payment_meta`, `payment_proof`, `delivery_address`) VALUES
(4, 2, NULL, 7200.00, 'Gcash', 'pending', NULL, '2025-11-27 14:47:47', NULL, NULL, '{\"payment\":\"gcash\",\"gcash_number\":\"639122354762\",\"gcash_ref\":\"639122354762\",\"bank_name\":\"\",\"bank_account_name\":\"\",\"bank_account_number\":\"\"}', 'upload/payment-proof/proof_4_1764254867.png', '13 Turac San Carlos City Pangasinan'),
(5, 2, 2, 7200.00, 'Gcash', 'completed', 'upload\\delivery-proof\\proof_5_1764424292_9ec8486a.png', '2025-11-27 14:48:23', 15.9050560, 120.3676940, '{\"payment\":\"gcash\",\"gcash_number\":\"639122354762\",\"gcash_ref\":\"\",\"bank_name\":\"\",\"bank_account_name\":\"\",\"bank_account_number\":\"\"}', 'upload/payment-proof/proof_5_1764254903.png', '13 Turac San Carlos City Pangasinan'),
(6, 2, 2, 7200.00, 'Gcash', 'completed', 'upload/delivery-proof/proof_6_1764428046_b2a1ad80.png', '2025-11-29 14:04:36', NULL, NULL, '{\"payment\":\"gcash\",\"gcash_number\":\"639122354762\",\"gcash_ref\":\"639122354762\",\"bank_name\":\"\",\"bank_account_name\":\"\",\"bank_account_number\":\"\"}', 'upload/payment-proof/proof_4_1764254867.png', '13 Turac San Carlos City Pangasinan'),
(7, 2, NULL, 1000.00, 'Gcash', 'pending', NULL, '2025-11-29 14:13:18', NULL, NULL, '{\"payment\":\"gcash\",\"gcash_number\":\"639122354762\",\"gcash_ref\":\"639122354762\",\"bank_name\":\"\",\"bank_account_name\":\"\",\"bank_account_number\":\"\"}', 'upload/payment-proof/proof_7_1764425598.png', '13 Turac San Carlos City Pangasinan'),
(8, 2, 2, 6800.00, 'Gcash', 'delivering', NULL, '2025-11-29 15:19:24', 15.9050170, 120.3677730, '{\"payment\":\"gcash\",\"gcash_number\":\"639122354762\",\"gcash_ref\":\"639122354762\",\"bank_name\":\"\",\"bank_account_name\":\"\",\"bank_account_number\":\"\"}', 'upload/payment-proof/proof_8_1764429564.png', '13 Turac San Carlos City Pangasinan');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int NOT NULL,
  `order_id` int NOT NULL,
  `variant_id` int NOT NULL,
  `product_id` int DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `quantity` int DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `variant_id`, `product_id`, `price`, `quantity`) VALUES
(2, 4, 8, 7, 7200.00, 1),
(3, 5, 8, 7, 7200.00, 1),
(4, 6, 8, 7, 7200.00, 1),
(5, 7, 1, 1, 1000.00, 1),
(6, 8, 10, 8, 6800.00, 1);

-- --------------------------------------------------------

--
-- Table structure for table `product`
--

CREATE TABLE `product` (
  `product_id` int NOT NULL,
  `brand_id` int NOT NULL,
  `name` varchar(150) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `product_badge` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `product`
--

INSERT INTO `product` (`product_id`, `brand_id`, `name`, `category`, `product_badge`, `created_at`) VALUES
(1, 1, 'NIKE P600', 'Male', 'Hot', '2025-11-14 08:57:04'),
(2, 1, 'NIKE DUNK LOW RETRO', 'Male', '10%', '2025-11-14 08:59:23'),
(3, 1, 'NIKE ZOOM VOMERO 5', 'Male', '10%', '2025-11-14 09:15:59'),
(4, 1, 'NIKE CORTEZ LEATHER', 'Male', '20%', '2025-11-14 12:38:17'),
(5, 1, 'NIKE SB ALLEYOOP', 'Unisex', 'Hot', '2025-11-14 12:55:34'),
(6, 5, 'SPEEDCAT OG SNEAKERS', 'UNISEX', 'Hot', '2025-11-19 13:14:29'),
(7, 5, 'PALERMO VINTAGE SNEAKERS ', 'UNISEX', 'Hot', '2025-11-19 13:29:31'),
(8, 2, 'SAMBA OG SHOE', 'UNISEX', 'Hot', '2025-11-19 14:02:14');

-- --------------------------------------------------------

--
-- Table structure for table `product_color_image`
--

CREATE TABLE `product_color_image` (
  `color_image_id` int NOT NULL,
  `product_id` int NOT NULL,
  `color_id` int NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `sort_order` tinyint DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `product_color_image`
--

INSERT INTO `product_color_image` (`color_image_id`, `product_id`, `color_id`, `image_url`, `sort_order`) VALUES
(1, 2, 1, 'upload\\product-image\\photo_2025-11-08_22-55-33.jpg', 1),
(2, 2, 1, 'upload\\product-image\\product1-1.jpg', 2),
(3, 2, 1, 'upload\\product-image\\product1-2.jpg', 3),
(4, 2, 1, 'upload\\product-image\\product1-3.jpg', 4),
(5, 2, 1, 'upload\\product-image\\product1-4.jpg', 5),
(6, 2, 2, 'upload\\product-image\\photo_2025-11-19_12-56-09.jpg', 1),
(7, 2, 2, 'upload\\product-image\\photo_2025-11-19_12-56-09 (2).jpg', 2),
(8, 2, 2, 'upload\\product-image\\photo_2025-11-19_12-56-09 (3).jpg', 3),
(9, 2, 2, 'upload\\product-image\\photo_2025-11-19_12-56-09 (4).jpg', 4),
(10, 2, 2, 'upload\\product-image\\photo_2025-11-19_12-57-19.jpg', 5),
(11, 6, 3, 'upload\\product-image\\photo_2025-11-19_21-29-51.jpg', 1),
(12, 7, 4, 'upload\\product-image\\photo_2025-11-19_21-04-49 (2).jpg', 1),
(13, 7, 4, 'upload\\product-image\\photo_2025-11-19_21-04-49.jpg', 2),
(14, 7, 4, 'upload\\product-image\\photo_2025-11-19_21-04-49 (5).jpg', 3),
(15, 6, 4, 'upload\\product-image\\photo_2025-11-19_21-04-49 (4).jpg', 4),
(17, 6, 4, 'upload\\product-image\\photo_2025-11-19_21-04-49 (5).jpg', 5),
(18, 6, 3, 'upload\\product-image\\photo_2025-11-19_21-29-51 (2).jpg', 2),
(19, 6, 3, 'upload\\product-image\\photo_2025-11-19_21-29-51 (3).jpg', 3),
(20, 6, 2, 'upload\\product-image\\photo_2025-11-19_20-57-22 (3).jpg', 1),
(21, 6, 2, 'upload\\product-image\\photo_2025-11-19_20-57-22 (4).jpg', 2),
(22, 6, 2, 'upload\\product-image\\photo_2025-11-19_20-57-22 (5).jpg', 3),
(23, 6, 2, 'upload\\product-image\\photo_2025-11-19_20-57-22 (6).jpg', 4),
(24, 6, 2, 'upload\\product-image\\photo_2025-11-19_20-57-22 (7).jpg', 5),
(25, 6, 3, 'upload\\product-image\\photo_2025-11-19_21-29-51 (4).jpg', 4),
(26, 6, 3, 'upload\\product-image\\photo_2025-11-19_21-29-51 (5).jpg', 5),
(27, 7, 4, 'upload\\product-image\\photo_2025-11-19_21-04-49 (4).jpg', 4),
(28, 7, 4, 'upload\\product-image\\photo_2025-11-19_21-04-49 (3).jpg', 4),
(29, 8, 5, 'upload\\product-image\\photo_2025-11-19_21-38-11.jpg', 1),
(30, 8, 6, 'upload\\product-image\\photo_2025-11-19_21-38-12.jpg', 1),
(31, 8, 5, 'upload\\product-image\\photo_2025-11-19_21-38-11 (2).jpg', 2),
(32, 8, 5, 'upload\\product-image\\photo_2025-11-19_21-38-11 (3).jpg', 3),
(33, 8, 5, 'upload\\product-image\\photo_2025-11-19_21-38-11 (4).jpg', 4),
(34, 8, 5, 'upload\\product-image\\photo_2025-11-19_21-38-11 (5).jpg', 5),
(35, 8, 6, 'upload\\product-image\\photo_2025-11-19_21-38-11 (6).jpg', 2),
(36, 8, 6, 'upload\\product-image\\photo_2025-11-19_21-38-11 (7).jpg', 3),
(37, 8, 6, 'upload\\product-image\\photo_2025-11-19_21-38-12 (2).jpg', 4),
(38, 8, 6, 'upload\\product-image\\photo_2025-11-19_21-38-12 (3).jpg', 5);

-- --------------------------------------------------------

--
-- Table structure for table `product_variant`
--

CREATE TABLE `product_variant` (
  `variant_id` int NOT NULL,
  `product_id` int NOT NULL,
  `color_id` int NOT NULL,
  `size_id` int NOT NULL,
  `stock` int NOT NULL DEFAULT '0',
  `price` decimal(10,2) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `product_variant`
--

INSERT INTO `product_variant` (`variant_id`, `product_id`, `color_id`, `size_id`, `stock`, `price`, `image_url`) VALUES
(1, 1, 1, 1, 10, 1000.00, NULL),
(2, 4, 1, 1, 10, 600.00, NULL),
(3, 4, 2, 1, 13, 699.00, NULL),
(4, 2, 1, 1, 1, 600.00, NULL),
(5, 2, 2, 1, 10, 6020.00, NULL),
(6, 2, 2, 2, 1, 600.00, NULL),
(7, 6, 3, 4, 2, 599.00, NULL),
(8, 7, 4, 3, 10, 7200.00, NULL),
(9, 6, 2, 1, 2, 7200.00, NULL),
(10, 8, 5, 4, 2, 6800.00, ''),
(11, 8, 6, 5, 1, 6800.00, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `rider`
--

CREATE TABLE `rider` (
  `rider_id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(120) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('available','busy','inactive') DEFAULT 'available',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `current_lat` decimal(11,8) DEFAULT NULL,
  `current_lng` decimal(11,8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `rider`
--

INSERT INTO `rider` (`rider_id`, `name`, `email`, `phone`, `password`, `status`, `created_at`, `current_lat`, `current_lng`) VALUES
(1, 'Rider In Tandem', 'rider@gmail.com', '09122354762', '$2y$10$g35fTCZzZT4YEWuO8hNCteI8iLt1jZxWGUhOB9Fn/zSLk5Jp17MHO', 'inactive', '2025-11-25 13:55:40', NULL, NULL),
(2, 'Vincent Agbuya', 'vince@gmail.com', '09122354762', '$2y$10$g35fTCZzZT4YEWuO8hNCteI8iLt1jZxWGUhOB9Fn/zSLk5Jp17MHO', 'busy', '2025-11-25 16:28:34', 15.90481200, 120.36786650);

-- --------------------------------------------------------

--
-- Stand-in structure for view `rider_avg_rating`
-- (See below for the actual view)
--
CREATE TABLE `rider_avg_rating` (
`avg_rating` decimal(7,4)
,`rating_count` bigint
,`rider_id` int
);

-- --------------------------------------------------------

--
-- Table structure for table `rider_location_history`
--

CREATE TABLE `rider_location_history` (
  `id` int NOT NULL,
  `rider_id` int NOT NULL,
  `latitude` decimal(10,7) NOT NULL,
  `longitude` decimal(10,7) NOT NULL,
  `heading` decimal(6,2) DEFAULT NULL,
  `accuracy` int DEFAULT NULL,
  `recorded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `rider_location_history`
--




-- --------------------------------------------------------

--
-- Table structure for table `rider_rating`
--

CREATE TABLE `rider_rating` (
  `rating_id` int NOT NULL,
  `rider_id` int NOT NULL,
  `customer_id` int DEFAULT NULL,
  `order_id` int DEFAULT NULL,
  `rating` tinyint NOT NULL,
  `review` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ;

--
-- Dumping data for table `rider_rating`
--

INSERT INTO `rider_rating` (`rating_id`, `rider_id`, `customer_id`, `order_id`, `rating`, `review`, `created_at`) VALUES
(1, 1, 1, 101, 5, 'Fast and courteous delivery.', '2025-11-25 17:24:52'),
(2, 1, 2, 102, 4, 'Delivered on time.', '2025-11-25 17:24:52'),
(3, 2, 3, 103, 3, 'Package arrived slightly late.', '2025-11-25 17:24:52'),
(4, 1, NULL, 104, 5, 'Excellent service (guest checkout)', '2025-11-25 17:24:52');

-- --------------------------------------------------------

--
-- Table structure for table `size`
--

CREATE TABLE `size` (
  `size_id` int NOT NULL,
  `size_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `size`
--

INSERT INTO `size` (`size_id`, `size_name`) VALUES
(3, '38'),
(4, '39'),
(5, '40'),
(2, '43'),
(1, '44');

-- --------------------------------------------------------

--
-- Table structure for table `store`
--

CREATE TABLE `store` (
  `store_id` int NOT NULL,
  `store_name` varchar(50) DEFAULT NULL,
  `store_logo` varchar(100) DEFAULT NULL,
  `gcash_no` text,
  `start_lat` decimal(10,7) DEFAULT NULL,
  `start_lng` decimal(10,7) DEFAULT NULL,
  `gcash_qr` varchar(512) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `store`
--

INSERT INTO `store` (`store_id`, `store_name`, `store_logo`, `gcash_no`, `start_lat`, `start_lng`, `gcash_qr`) VALUES
(1, 'ShoeTakels', 'upload\\logo\\logo.png', '', 15.9334040, 120.3472140, 'admin\\upload\\picture\\569910871_1187957236598844_2847302257809057682_n.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `store_bank_account`
--

CREATE TABLE `store_bank_account` (
  `account_id` int NOT NULL,
  `store_id` int NOT NULL,
  `bank_name` varchar(150) NOT NULL,
  `account_name` varchar(150) DEFAULT NULL,
  `account_number` varchar(100) NOT NULL,
  `branch` varchar(150) DEFAULT NULL,
  `bank_code` varchar(50) DEFAULT NULL,
  `account_type` varchar(50) DEFAULT NULL,
  `is_primary` tinyint(1) DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `store_bank_account`
--

INSERT INTO `store_bank_account` (`account_id`, `store_id`, `bank_name`, `account_name`, `account_number`, `branch`, `bank_code`, `account_type`, `is_primary`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'BDO', 'ShoeTakels Inc.', '1234567890', 'Makati Branch', 'BDO001', 'checking', 1, 1, '2025-11-27 12:38:39', '2025-11-27 12:38:39'),
(2, 1, 'BPI', 'ShoeTakels Inc.', '0987654321', 'Ortigas Branch', 'BPI002', 'savings', 0, 1, '2025-11-27 12:38:39', '2025-11-27 12:38:39');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `brand`
--
ALTER TABLE `brand`
  ADD PRIMARY KEY (`brand_id`),
  ADD UNIQUE KEY `brand_name` (`brand_name`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`cart_id`),
  ADD UNIQUE KEY `unique_user_variant` (`customer_id`,`variant_id`),
  ADD KEY `idx_customer_id` (`customer_id`),
  ADD KEY `idx_variant_id` (`variant_id`);

--
-- Indexes for table `color`
--
ALTER TABLE `color`
  ADD PRIMARY KEY (`color_id`),
  ADD UNIQUE KEY `color_name` (`color_name`);

--
-- Indexes for table `customer`
--
ALTER TABLE `customer`
  ADD PRIMARY KEY (`customer_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `hero_carousel`
--
ALTER TABLE `hero_carousel`
  ADD PRIMARY KEY (`carousel_id`),
  ADD KEY `idx_sort` (`sort_order`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `rider_id` (`rider_id`),
  ADD KEY `idx_delivery_coords` (`delivery_lat`,`delivery_lng`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `product`
--
ALTER TABLE `product`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `brand_id` (`brand_id`);

--
-- Indexes for table `product_color_image`
--
ALTER TABLE `product_color_image`
  ADD PRIMARY KEY (`color_image_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `color_id` (`color_id`);

--
-- Indexes for table `product_variant`
--
ALTER TABLE `product_variant`
  ADD PRIMARY KEY (`variant_id`),
  ADD UNIQUE KEY `unique_variant` (`product_id`,`color_id`,`size_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `color_id` (`color_id`),
  ADD KEY `size_id` (`size_id`);

--
-- Indexes for table `rider`
--
ALTER TABLE `rider`
  ADD PRIMARY KEY (`rider_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `rider_location_history`
--
ALTER TABLE `rider_location_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `rider_id` (`rider_id`,`recorded_at`);

--
-- Indexes for table `rider_rating`
--
ALTER TABLE `rider_rating`
  ADD PRIMARY KEY (`rating_id`),
  ADD KEY `idx_rider_id` (`rider_id`),
  ADD KEY `idx_customer_id` (`customer_id`),
  ADD KEY `idx_order_id` (`order_id`);

--
-- Indexes for table `size`
--
ALTER TABLE `size`
  ADD PRIMARY KEY (`size_id`),
  ADD UNIQUE KEY `size_name` (`size_name`);

--
-- Indexes for table `store`
--
ALTER TABLE `store`
  ADD PRIMARY KEY (`store_id`),
  ADD KEY `idx_start_coords` (`start_lat`,`start_lng`);

--
-- Indexes for table `store_bank_account`
--
ALTER TABLE `store_bank_account`
  ADD PRIMARY KEY (`account_id`),
  ADD KEY `idx_store_id` (`store_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `admin_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `brand`
--
ALTER TABLE `brand`
  MODIFY `brand_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `cart_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `color`
--
ALTER TABLE `color`
  MODIFY `color_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `customer`
--
ALTER TABLE `customer`
  MODIFY `customer_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `hero_carousel`
--
ALTER TABLE `hero_carousel`
  MODIFY `carousel_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `product`
--
ALTER TABLE `product`
  MODIFY `product_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `product_color_image`
--
ALTER TABLE `product_color_image`
  MODIFY `color_image_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `product_variant`
--
ALTER TABLE `product_variant`
  MODIFY `variant_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `rider`
--
ALTER TABLE `rider`
  MODIFY `rider_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `rider_location_history`
--
ALTER TABLE `rider_location_history`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=844;

--
-- AUTO_INCREMENT for table `rider_rating`
--
ALTER TABLE `rider_rating`
  MODIFY `rating_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `size`
--
ALTER TABLE `size`
  MODIFY `size_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `store`
--
ALTER TABLE `store`
  MODIFY `store_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `store_bank_account`
--
ALTER TABLE `store_bank_account`
  MODIFY `account_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

-- --------------------------------------------------------

--
-- Structure for view `rider_avg_rating`
--
DROP TABLE IF EXISTS `rider_avg_rating`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `rider_avg_rating`  AS SELECT `rider_rating`.`rider_id` AS `rider_id`, avg(`rider_rating`.`rating`) AS `avg_rating`, count(0) AS `rating_count` FROM `rider_rating` GROUP BY `rider_rating`.`rider_id` ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_customer_fk` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_variant_fk` FOREIGN KEY (`variant_id`) REFERENCES `product_variant` (`variant_id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`rider_id`) REFERENCES `rider` (`rider_id`) ON DELETE SET NULL;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE;

--
-- Constraints for table `product`
--
ALTER TABLE `product`
  ADD CONSTRAINT `product_ibfk_1` FOREIGN KEY (`brand_id`) REFERENCES `brand` (`brand_id`) ON DELETE CASCADE;

--
-- Constraints for table `product_color_image`
--
ALTER TABLE `product_color_image`
  ADD CONSTRAINT `product_color_image_color_fk` FOREIGN KEY (`color_id`) REFERENCES `color` (`color_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_color_image_product_fk` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `product_variant`
--
ALTER TABLE `product_variant`
  ADD CONSTRAINT `variant_color_fk` FOREIGN KEY (`color_id`) REFERENCES `color` (`color_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `variant_product_fk` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `variant_size_fk` FOREIGN KEY (`size_id`) REFERENCES `size` (`size_id`) ON DELETE CASCADE;

--
-- Constraints for table `rider_location_history`
--
ALTER TABLE `rider_location_history`
  ADD CONSTRAINT `rider_location_history_ibfk_1` FOREIGN KEY (`rider_id`) REFERENCES `rider` (`rider_id`) ON DELETE CASCADE;

--
-- Constraints for table `store_bank_account`
--
ALTER TABLE `store_bank_account`
  ADD CONSTRAINT `fk_store_bank_store` FOREIGN KEY (`store_id`) REFERENCES `store` (`store_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

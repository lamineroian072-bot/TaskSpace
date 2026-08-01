-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 01, 2026 at 03:18 PM
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
-- Database: `app_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `iot_logs`
--

CREATE TABLE `iot_logs` (
  `id` int(11) NOT NULL,
  `device_id` varchar(50) NOT NULL,
  `temperature` decimal(5,2) NOT NULL,
  `humidity` decimal(5,2) NOT NULL,
  `status` enum('OPTIMAL','WARNING','CRITICAL') DEFAULT 'OPTIMAL',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `iot_logs`
--

INSERT INTO `iot_logs` (`id`, `device_id`, `temperature`, `humidity`, `status`, `created_at`) VALUES
(1, 'SENSOR-01', 24.50, 55.00, 'OPTIMAL', '2026-08-01 12:04:16'),
(2, 'SENSOR-02', 32.10, 78.40, 'WARNING', '2026-08-01 12:04:16'),
(3, 'SENSOR-03', 41.80, 88.20, 'CRITICAL', '2026-08-01 12:04:16'),
(4, 'SENSOR-01', 25.00, 54.20, 'CRITICAL', '2026-08-01 12:04:16');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('Pending','Completed','Cancelled') DEFAULT 'Pending',
  `order_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `customer_name`, `customer_phone`, `total_amount`, `status`, `order_date`) VALUES
(1, 'Juan Dela Cruz', '09171234567', 32990.00, 'Completed', '2026-08-01 12:58:18'),
(2, 'Maria Santos', '09189876543', 59990.00, 'Cancelled', '2026-08-01 12:58:18');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(1, 1, 3, 1, 32990.00),
(2, 2, 6, 1, 59990.00);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `model` varchar(50) NOT NULL,
  `condition_type` enum('Brand New','Secondhand') DEFAULT 'Brand New',
  `storage` varchar(20) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 1,
  `status` enum('In Stock','Low Stock','Out of Stock') DEFAULT 'In Stock',
  `image_url` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `model`, `condition_type`, `storage`, `price`, `stock`, `status`, `image_url`, `created_at`) VALUES
(1, 'iPhone 11', 'Secondhand', '64GB', 14990.00, 5, 'In Stock', 'https://images.unsplash.com/photo-1574944985070-8f3ebc6b79d2?w=500&auto=format&fit=crop', '2026-08-01 12:58:18'),
(2, 'iPhone 12', 'Secondhand', '128GB', 21990.00, 3, 'In Stock', 'https://images.unsplash.com/photo-1605236453806-6ff36851218e?w=500&auto=format&fit=crop', '2026-08-01 12:58:18'),
(3, 'iPhone 13', 'Brand New', '128GB', 32990.00, 10, 'In Stock', 'https://images.unsplash.com/photo-1632661674596-df8be070a5c5?w=500&auto=format&fit=crop', '2026-08-01 12:58:18'),
(4, 'iPhone 14', 'Secondhand', '256GB', 34990.00, 2, 'Low Stock', 'https://images.unsplash.com/photo-1663499482523-1c0c1bae4ce1?w=500&auto=format&fit=crop', '2026-08-01 12:58:18'),
(5, 'iPhone 15', 'Brand New', '128GB', 44990.00, 8, 'In Stock', 'https://images.unsplash.com/photo-1695048133142-1a20484d2569?w=500&auto=format&fit=crop', '2026-08-01 12:58:18'),
(6, 'iPhone 15 Pro', 'Brand New', '256GB', 59990.00, 4, 'In Stock', 'https://images.unsplash.com/photo-1695048065056-111079f53531?w=500&auto=format&fit=crop', '2026-08-01 12:58:18');

-- --------------------------------------------------------

--
-- Table structure for table `records`
--

CREATE TABLE `records` (
  `id` int(11) NOT NULL,
  `record_code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `role` varchar(100) NOT NULL,
  `image` varchar(255) DEFAULT 'default.png',
  `status` enum('Active','Inactive','Pending') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `records`
--

INSERT INTO `records` (`id`, `record_code`, `name`, `role`, `image`, `status`, `created_at`, `updated_at`) VALUES
(1, 'REC-101', 'Elena Rostova', 'Data Engineer', 'default.png', 'Active', '2026-07-21 13:14:02', '2026-07-21 13:14:02'),
(2, 'REC-102', 'Marcus Vance', 'UI/UX Designer', 'default.png', 'Pending', '2026-07-21 13:14:02', '2026-07-21 13:14:02'),
(3, 'REC-103', 'Sophia Chen', 'Product Analyst', 'default.png', 'Active', '2026-07-21 13:14:02', '2026-07-21 13:14:02');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `iot_logs`
--
ALTER TABLE `iot_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_device` (`device_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `records`
--
ALTER TABLE `records`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `record_code` (`record_code`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `iot_logs`
--
ALTER TABLE `iot_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `records`
--
ALTER TABLE `records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

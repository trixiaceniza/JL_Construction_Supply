-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 28, 2026 at 06:17 AM
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
-- Database: `jlcs`
--

-- --------------------------------------------------------

--
-- Table structure for table `inventory_history`
--

CREATE TABLE `inventory_history` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `movement_type` enum('stock_in','sale','adjustment') NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL,
  `before_quantity` int(10) UNSIGNED NOT NULL,
  `after_quantity` int(10) UNSIGNED NOT NULL,
  `note` varchar(255) NOT NULL DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inventory_history`
--

INSERT INTO `inventory_history` (`id`, `product_id`, `user_id`, `movement_type`, `quantity`, `before_quantity`, `after_quantity`, `note`, `created_at`) VALUES
(1, 1, 3, 'sale', 3, 100, 97, 'Sale JL-20261021-172055-0319', '2026-10-21 15:20:55'),
(2, 3, 3, 'sale', 1, 100, 99, 'Sale JL-20261023-174939-8AA0', '2026-10-23 15:49:39');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(180) NOT NULL,
  `category` varchar(120) NOT NULL DEFAULT '',
  `unit` varchar(20) NOT NULL DEFAULT 'pcs',
  `pieces_per_box` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `cost_per_unit` decimal(12,2) DEFAULT NULL,
  `stock_quantity` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `low_stock_threshold` int(10) UNSIGNED NOT NULL DEFAULT 5,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `category`, `unit`, `pieces_per_box`, `price`, `cost_per_unit`, `stock_quantity`, `low_stock_threshold`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Cement', 'basta', 'pcs', 1, 100.00, NULL, 97, 5, 0, '2026-10-21 15:19:35', '2026-10-23 16:14:17'),
(2, 'Common Nail 2 inches', 'Building Materials', 'kg', 1, 80.00, NULL, 100, 5, 1, '2026-10-23 15:46:44', '2026-10-23 15:46:44'),
(3, 'Common Nail 3 inches', 'Building Materials', 'kg', 1, 80.00, NULL, 99, 5, 1, '2026-10-23 15:48:19', '2026-10-23 15:49:39'),
(4, 'Plywood 1/4 inch', 'Building Materials', 'pcs', 1, 100.00, NULL, 50, 5, 1, '2026-10-23 15:48:57', '2026-10-23 15:48:57');

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `transaction_no` varchar(40) NOT NULL,
  `staff_id` int(10) UNSIGNED NOT NULL,
  `total_amount` decimal(12,2) NOT NULL,
  `status` enum('completed','voided') NOT NULL DEFAULT 'completed',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `transaction_no`, `staff_id`, `total_amount`, `status`, `created_at`) VALUES
(1, 'JL-20261021-172055-0319', 3, 300.00, 'completed', '2026-10-21 15:20:55'),
(2, 'JL-20261023-174939-8AA0', 3, 80.00, 'completed', '2026-10-23 15:49:39');

-- --------------------------------------------------------

--
-- Table structure for table `sale_items`
--

CREATE TABLE `sale_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `sale_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `product_name` varchar(180) NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL,
  `unit_price` decimal(12,2) NOT NULL,
  `unit_cost` decimal(12,2) DEFAULT NULL,
  `line_total` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sale_items`
--

INSERT INTO `sale_items` (`id`, `sale_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `unit_cost`, `line_total`) VALUES
(1, 1, 1, 'Cement', 3, 100.00, NULL, 300.00),
(2, 2, 3, 'Common Nail 3 inches', 1, 80.00, NULL, 80.00);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('owner','staff') NOT NULL DEFAULT 'staff',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `password_hash`, `role`, `is_active`, `created_at`, `updated_at`) VALUES
(2, 'Trixia Ceniza', 'trixiaceniza32@gmail.com', '$2y$10$/lE9xH2sceQxnUOQg8bo0.NbLY7SdNqkj5fskiryqCBlUsMSP4Ygm', 'owner', 1, '2026-10-21 15:18:25', '2026-10-21 15:18:25'),
(3, 'Juan Caminsi', 'juan@gmail.com', '$2y$10$1jK50xkxfSkad6Iq5KJemezmwGldUbWgzD3mNAEPQqc.bpdZjCh0m', 'staff', 1, '2026-10-21 15:20:26', '2026-10-21 15:20:26');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `inventory_history`
--
ALTER TABLE `inventory_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_history_product` (`product_id`),
  ADD KEY `fk_history_user` (`user_id`),
  ADD KEY `idx_history_created` (`created_at`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_products_active_name` (`is_active`,`name`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transaction_no` (`transaction_no`),
  ADD KEY `idx_sales_created_status` (`created_at`,`status`),
  ADD KEY `idx_sales_staff_created` (`staff_id`,`created_at`);

--
-- Indexes for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_sale_items_sale` (`sale_id`),
  ADD KEY `idx_sale_items_product` (`product_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `inventory_history`
--
ALTER TABLE `inventory_history`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `sale_items`
--
ALTER TABLE `sale_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `inventory_history`
--
ALTER TABLE `inventory_history`
  ADD CONSTRAINT `fk_history_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `fk_history_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `fk_sales_staff` FOREIGN KEY (`staff_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD CONSTRAINT `fk_sale_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `fk_sale_items_sale` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

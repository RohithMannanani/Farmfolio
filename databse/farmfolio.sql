-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 19, 2025 at 06:36 PM
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
-- Database: `farmfolio`
--

-- --------------------------------------------------------

--
-- Table structure for table `tbl_cart`
--

CREATE TABLE `tbl_cart` (
  `cart_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `user_id` int(11) DEFAULT NULL,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_category`
--

CREATE TABLE `tbl_category` (
  `category_id` int(11) NOT NULL,
  `category` varchar(255) NOT NULL,
  `sub` varchar(100) NOT NULL,
  `status` enum('0','1') NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_category`
--

INSERT INTO `tbl_category` (`category_id`, `category`, `sub`, `status`) VALUES
(8, 'chicken farm', 'eggs', '1'),
(9, 'chicken farm', 'chicken', '1'),
(10, 'fruits', 'rambutan', '1'),
(11, 'fruits', 'mango', '1'),
(12, 'fruits', 'orange', '1'),
(13, 'vegitables', 'tomatos', '1'),
(14, 'vegitables', 'leafy', '1'),
(15, 'vegitables', 'non leafy', '1'),
(16, 'cow farm', 'milk', '1'),
(17, 'cow farm', 'curd', '1'),
(18, 'cow farm', 'ghee', '1'),
(19, 'Chicken farm', 'Chick ', '0'),
(20, 'Chicken farm', 'Chick ', '1');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_events`
--

CREATE TABLE `tbl_events` (
  `event_id` int(11) NOT NULL,
  `farm_id` int(11) NOT NULL,
  `event_name` varchar(255) NOT NULL,
  `event_date` date NOT NULL,
  `event_description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('0','1') NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_events`
--

INSERT INTO `tbl_events` (`event_id`, `farm_id`, `event_name`, `event_date`, `event_description`, `created_at`, `updated_at`, `status`) VALUES
(3, 8, 'Chicken breeding Class', '2025-05-14', 'In this event the participants get an idea about how to grow a hen and how that  process works', '2025-03-12 03:44:15', '2025-03-17 00:44:45', '1');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_farms`
--

CREATE TABLE `tbl_farms` (
  `farm_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `farm_name` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','active','rejected') NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_farms`
--

INSERT INTO `tbl_farms` (`farm_id`, `user_id`, `farm_name`, `location`, `description`, `created_at`, `status`) VALUES
(8, 48, 'AB\'s Farm', 'Mundakayam', 'Fresh products', '2025-03-11 04:09:13', 'active'),
(9, 49, 'Tom\'s Farm', 'Kanjirappally', 'Tom\'s Farm is a fruit farm . We deliver good quality fruits to our customers . Our pricing is very low and affordable to everyone . You can also come to our farm and know about farming.  ', '2025-03-18 00:00:39', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_farm_image`
--

CREATE TABLE `tbl_farm_image` (
  `image_id` int(11) NOT NULL,
  `farm_id` int(11) DEFAULT NULL,
  `path` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_farm_image`
--

INSERT INTO `tbl_farm_image` (`image_id`, `farm_id`, `path`) VALUES
(17, 8, 'uploads/farm_images/1742225233_g5.jpg'),
(18, 9, 'uploads/farm_images/1742256398_HOME.png');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_favorites`
--

CREATE TABLE `tbl_favorites` (
  `favorite_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `farm_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_favorites`
--

INSERT INTO `tbl_favorites` (`favorite_id`, `user_id`, `farm_id`, `created_at`) VALUES
(48, 36, 8, '2025-03-17 16:23:31');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_fc`
--

CREATE TABLE `tbl_fc` (
  `id` int(11) NOT NULL,
  `farm_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_fc`
--

INSERT INTO `tbl_fc` (`id`, `farm_id`, `category_id`) VALUES
(6, 8, 9),
(7, 8, 8),
(8, 9, 10),
(9, 9, 11),
(10, 9, 12),
(12, 8, 20);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_login`
--

CREATE TABLE `tbl_login` (
  `login_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `type` int(11) NOT NULL,
  `login_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `userid` int(11) NOT NULL,
  `username` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_login`
--

INSERT INTO `tbl_login` (`login_id`, `email`, `password`, `type`, `login_time`, `userid`, `username`) VALUES
(24, 'farmfoliomini@gmail.com', 'Admin@2004', 4, '2025-02-16 17:26:50', 33, 'admin'),
(26, 'rohithreghu482@gmail.com', 'Abin@2004\r\n', 0, '2025-02-16 17:53:29', 36, 'Abin Sebastian'),
(27, 'rohithreghu842@gmail.com', 'Hari@2004', 2, '2025-02-16 17:55:11', 37, 'Hari Govind'),
(36, 'amalbabu2027@mca.ajce.in', 'Amal@2004', 1, '2025-03-11 04:08:17', 48, 'Amal Babu'),
(37, 'rnairrohith17@gmail.com', 'Rohith@2004', 1, '2025-03-17 23:57:10', 49, 'Tom Shibu'),
(38, 'dennisjacob2027@mca.ajce.in', 'Rohith@2004', 2, '2025-03-18 10:54:54', 50, 'Dennis Jacob');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_notifications`
--

CREATE TABLE `tbl_notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_orders`
--

CREATE TABLE `tbl_orders` (
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `order_status` enum('pending','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `order_date` datetime NOT NULL,
  `delivery_address` text DEFAULT NULL,
  `phone_number` varchar(15) DEFAULT NULL,
  `payment_method` varchar(10) NOT NULL DEFAULT 'cod',
  `payment_status` enum('pending','paid','failed') NOT NULL DEFAULT 'pending',
  `delivery_boy_id` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_orders`
--

INSERT INTO `tbl_orders` (`order_id`, `user_id`, `total_amount`, `order_status`, `order_date`, `delivery_address`, `phone_number`, `payment_method`, `payment_status`, `delivery_boy_id`, `updated_at`) VALUES
(12, 36, 165.00, 'delivered', '2025-03-19 22:46:13', 'Puthupuredathu(H)\r\nKavumbhagom(PO)\r\nCheruvally\r\n686519', '8590177602', 'cod', 'paid', 37, '2025-03-19 17:16:35'),
(13, 36, 30.00, 'shipped', '2025-03-19 22:47:32', 'Puthupuredathu(H)\r\nKavumbhagom(PO)\r\nCheruvally\r\n686519', '8590177602', 'cod', 'pending', 37, '2025-03-19 17:18:09');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_order_items`
--

CREATE TABLE `tbl_order_items` (
  `item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_order_items`
--

INSERT INTO `tbl_order_items` (`item_id`, `order_id`, `product_id`, `quantity`, `price`, `subtotal`) VALUES
(16, 12, 22, 3, 55.00, 165.00),
(17, 13, 20, 5, 6.00, 30.00);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_participants`
--

CREATE TABLE `tbl_participants` (
  `participant_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `registration_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('Pending','Confirmed','Cancelled') DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_participants`
--

INSERT INTO `tbl_participants` (`participant_id`, `event_id`, `user_id`, `registration_date`, `status`) VALUES
(5, 3, 36, '2025-03-19 16:55:30', 'Pending');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_products`
--

CREATE TABLE `tbl_products` (
  `product_id` int(11) NOT NULL,
  `farm_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `category_id` int(11) NOT NULL,
  `unit` enum('kg','g','l','ml') NOT NULL,
  `status` enum('0','1') NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_products`
--

INSERT INTO `tbl_products` (`product_id`, `farm_id`, `product_name`, `price`, `stock`, `description`, `created_at`, `category_id`, `unit`, `status`) VALUES
(20, 8, 'Eggs', 6.00, 95, 'fresh eggs ', '2025-03-17 15:26:44', 8, 'ml', '0'),
(21, 9, 'Rambutan', 45.00, 97, 'Fresh rambutan from our plantation', '2025-03-18 00:09:58', 10, 'kg', '0'),
(22, 9, 'Mango', 55.00, 94, 'fresh mango..', '2025-03-18 00:12:30', 11, 'kg', '0');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_reviews`
--

CREATE TABLE `tbl_reviews` (
  `review_id` int(11) NOT NULL,
  `farm_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `rating` int(11) NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_reviews`
--

INSERT INTO `tbl_reviews` (`review_id`, `farm_id`, `user_id`, `rating`, `comment`, `created_at`) VALUES
(1, 8, 36, 5, 'a good farm and good service ', '2025-03-19 16:55:06'),
(2, 8, 48, 1, '', '2025-03-17 10:17:02');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_signup`
--

CREATE TABLE `tbl_signup` (
  `userid` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `mobile` varchar(15) NOT NULL,
  `email` varchar(100) NOT NULL,
  `house` varchar(255) NOT NULL,
  `state` varchar(100) NOT NULL,
  `district` varchar(100) NOT NULL,
  `pin` char(6) NOT NULL,
  `password` varchar(255) NOT NULL,
  `signup_time` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_signup`
--

INSERT INTO `tbl_signup` (`userid`, `username`, `mobile`, `email`, `house`, `state`, `district`, `pin`, `password`, `signup_time`) VALUES
(33, 'admin', '7561827502', 'farmfoliomin@gmail.com', '............................', 'Kerala', 'Kottayam', '686519', 'Admin@2004', '2025-02-16 22:54:12'),
(36, 'Abin Sebastian', '9961115723', 'rohithreghu482@gmail.com', 'valliyil', 'Kerala', 'Kottayam', '686519', 'Abin@2004', '2025-02-16 23:23:29'),
(37, 'Hari Govind', '9746794654', 'rohithreghu842@gmail.com', 'hhhhhh', 'Kerala', 'Kottayam', '686519', 'Hari@2004', '2025-02-16 23:25:11'),
(48, 'Amal Babu', '6574893214', 'amalbabu2027@mca.ajce.in', 'pathiplackal', 'Kerala', 'Kottayam', '686582', 'Amal@2004', '2025-03-11 09:38:17'),
(49, 'Tom Shibu', '9875412630', 'rnairrohith17@gmail.com', 'Mmmmm', 'Kerala', 'Kottayam', '686518', 'Rohith@2004', '2025-03-18 05:27:10'),
(50, 'Dennis Jacob', '9747012188', 'dennisjacob2027@mca.ajce.in', 'Punnathanathu', 'Kerala', 'Idukki', '685609', 'Rohith@2004', '2025-03-18 16:24:54');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tbl_cart`
--
ALTER TABLE `tbl_cart`
  ADD PRIMARY KEY (`cart_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `tbl_category`
--
ALTER TABLE `tbl_category`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `tbl_events`
--
ALTER TABLE `tbl_events`
  ADD PRIMARY KEY (`event_id`),
  ADD KEY `farm_id` (`farm_id`);

--
-- Indexes for table `tbl_farms`
--
ALTER TABLE `tbl_farms`
  ADD PRIMARY KEY (`farm_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `tbl_farm_image`
--
ALTER TABLE `tbl_farm_image`
  ADD PRIMARY KEY (`image_id`),
  ADD KEY `farm_id` (`farm_id`);

--
-- Indexes for table `tbl_favorites`
--
ALTER TABLE `tbl_favorites`
  ADD PRIMARY KEY (`favorite_id`),
  ADD UNIQUE KEY `unique_favorite` (`user_id`,`farm_id`),
  ADD KEY `farm_id` (`farm_id`);

--
-- Indexes for table `tbl_fc`
--
ALTER TABLE `tbl_fc`
  ADD PRIMARY KEY (`id`),
  ADD KEY `farm_id` (`farm_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `tbl_login`
--
ALTER TABLE `tbl_login`
  ADD PRIMARY KEY (`login_id`),
  ADD KEY `userid` (`userid`);

--
-- Indexes for table `tbl_notifications`
--
ALTER TABLE `tbl_notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `tbl_orders`
--
ALTER TABLE `tbl_orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `delivery_boy_id` (`delivery_boy_id`);

--
-- Indexes for table `tbl_order_items`
--
ALTER TABLE `tbl_order_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `tbl_participants`
--
ALTER TABLE `tbl_participants`
  ADD PRIMARY KEY (`participant_id`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `tbl_products`
--
ALTER TABLE `tbl_products`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `farm_id` (`farm_id`),
  ADD KEY `fk_category` (`category_id`);

--
-- Indexes for table `tbl_reviews`
--
ALTER TABLE `tbl_reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `farm_id` (`farm_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `tbl_signup`
--
ALTER TABLE `tbl_signup`
  ADD PRIMARY KEY (`userid`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `mobile` (`mobile`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tbl_cart`
--
ALTER TABLE `tbl_cart`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=155;

--
-- AUTO_INCREMENT for table `tbl_category`
--
ALTER TABLE `tbl_category`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `tbl_events`
--
ALTER TABLE `tbl_events`
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tbl_farms`
--
ALTER TABLE `tbl_farms`
  MODIFY `farm_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `tbl_farm_image`
--
ALTER TABLE `tbl_farm_image`
  MODIFY `image_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `tbl_favorites`
--
ALTER TABLE `tbl_favorites`
  MODIFY `favorite_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `tbl_fc`
--
ALTER TABLE `tbl_fc`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `tbl_login`
--
ALTER TABLE `tbl_login`
  MODIFY `login_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `tbl_notifications`
--
ALTER TABLE `tbl_notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_orders`
--
ALTER TABLE `tbl_orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `tbl_order_items`
--
ALTER TABLE `tbl_order_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `tbl_participants`
--
ALTER TABLE `tbl_participants`
  MODIFY `participant_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tbl_products`
--
ALTER TABLE `tbl_products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `tbl_reviews`
--
ALTER TABLE `tbl_reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tbl_signup`
--
ALTER TABLE `tbl_signup`
  MODIFY `userid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tbl_cart`
--
ALTER TABLE `tbl_cart`
  ADD CONSTRAINT `tbl_cart_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `tbl_products` (`product_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tbl_cart_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `tbl_signup` (`userid`);

--
-- Constraints for table `tbl_events`
--
ALTER TABLE `tbl_events`
  ADD CONSTRAINT `tbl_events_ibfk_1` FOREIGN KEY (`farm_id`) REFERENCES `tbl_farms` (`farm_id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_farms`
--
ALTER TABLE `tbl_farms`
  ADD CONSTRAINT `tbl_farms_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `tbl_signup` (`userid`);

--
-- Constraints for table `tbl_farm_image`
--
ALTER TABLE `tbl_farm_image`
  ADD CONSTRAINT `tbl_farm_image_ibfk_1` FOREIGN KEY (`farm_id`) REFERENCES `tbl_farms` (`farm_id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_favorites`
--
ALTER TABLE `tbl_favorites`
  ADD CONSTRAINT `tbl_favorites_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `tbl_signup` (`userid`),
  ADD CONSTRAINT `tbl_favorites_ibfk_2` FOREIGN KEY (`farm_id`) REFERENCES `tbl_farms` (`farm_id`);

--
-- Constraints for table `tbl_fc`
--
ALTER TABLE `tbl_fc`
  ADD CONSTRAINT `tbl_fc_ibfk_1` FOREIGN KEY (`farm_id`) REFERENCES `tbl_farms` (`farm_id`),
  ADD CONSTRAINT `tbl_fc_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `tbl_category` (`category_id`);

--
-- Constraints for table `tbl_login`
--
ALTER TABLE `tbl_login`
  ADD CONSTRAINT `tbl_login_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `tbl_signup` (`userid`);

--
-- Constraints for table `tbl_notifications`
--
ALTER TABLE `tbl_notifications`
  ADD CONSTRAINT `tbl_notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `tbl_signup` (`userid`);

--
-- Constraints for table `tbl_orders`
--
ALTER TABLE `tbl_orders`
  ADD CONSTRAINT `tbl_orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `tbl_login` (`userid`),
  ADD CONSTRAINT `tbl_orders_ibfk_2` FOREIGN KEY (`delivery_boy_id`) REFERENCES `tbl_signup` (`userid`);

--
-- Constraints for table `tbl_order_items`
--
ALTER TABLE `tbl_order_items`
  ADD CONSTRAINT `tbl_order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `tbl_orders` (`order_id`),
  ADD CONSTRAINT `tbl_order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `tbl_products` (`product_id`);

--
-- Constraints for table `tbl_participants`
--
ALTER TABLE `tbl_participants`
  ADD CONSTRAINT `tbl_participants_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `tbl_events` (`event_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tbl_participants_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `tbl_login` (`userid`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_products`
--
ALTER TABLE `tbl_products`
  ADD CONSTRAINT `fk_category` FOREIGN KEY (`category_id`) REFERENCES `tbl_category` (`category_id`),
  ADD CONSTRAINT `tbl_products_ibfk_1` FOREIGN KEY (`farm_id`) REFERENCES `tbl_farms` (`farm_id`);

--
-- Constraints for table `tbl_reviews`
--
ALTER TABLE `tbl_reviews`
  ADD CONSTRAINT `tbl_reviews_ibfk_1` FOREIGN KEY (`farm_id`) REFERENCES `tbl_farms` (`farm_id`),
  ADD CONSTRAINT `tbl_reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `tbl_signup` (`userid`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 12, 2025 at 07:23 PM
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
(1, 'geo@mca.in', 'Geo@2004', 0, '2025-01-21 15:02:00', 1, 'geo'),
(2, 'admin2004@gmail.com', 'Admin@2004', 4, '2025-01-21 15:07:04', 2, 'admin'),
(3, 'melbin@123gmail.com', 'Balan@2004', 0, '2025-01-21 15:13:35', 3, 'balan'),
(4, 'rajesh@mca.in', 'Rajesh@2004', 0, '2025-01-21 15:19:21', 5, 'Rajesh_2004'),
(5, 'deric@mca.in', 'Deric@2004', 0, '2025-01-21 15:29:15', 6, 'Deric_2004'),
(6, 'amal@mca.in', 'Amal@2004', 0, '2025-01-23 08:57:16', 10, 'amal'),
(7, 'abhijith@mca.in', 'Abhijith@2004', 0, '2025-01-27 16:38:45', 12, 'Abhijith'),
(9, 'alen@mca.in', 'Alen@2004', 0, '2025-01-29 08:04:41', 15, 'alen kuriakose'),
(10, 'rohith@mca.in', '$2y$10$AQd.PcZjGdt2JnZx62qAcevrnmNM94WH3qOUNiRONu0rrDVOh3zKC', 0, '2025-02-04 19:13:16', 16, 'amal r'),
(11, 'rose@mca.in', 'Rose@2004', 2, '2025-02-04 19:32:53', 17, 'rose mary'),
(22, 'rohithrnair2027@mca.ajce.in', 'Rajesh@2004', 0, '2025-02-11 07:33:21', 29, 'Rohith'),
(23, 'rnairrohith17@gmail.com', 'Rohith@2004', 0, '2025-02-12 15:09:23', 30, 'rinikuri');

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
(1, ' geo', ' 1234567890', 'geo@mca.in', 'Moozhayil', 'kerala', ' kottayam', '686519', ' Geo@2004', '2025-01-21 20:11:12'),
(2, 'admin', '7561827503', 'admin2004@gmail.com', '...............', 'Kerala', 'Kottayam', '686519', 'Admin@2004', '2025-01-21 20:34:41'),
(3, ' balan', ' 9876543210', 'melbin@123gmail.com', 'puthupuredathu', 'kerahjla', ' kottayam', '686519', ' Balan@2004', '2025-01-21 20:43:05'),
(5, ' Rajesh_2004', ' 2468101214', 'rajesh@mca.in', 'Moozhayil', 'kerala', ' kottayam', '686519', ' Rajesh@2004', '2025-01-21 20:48:59'),
(6, ' Deric_2004', ' 7510839543', 'deric@mca.in', 'Moozhayil', 'kerala', ' kottayam', '686519', ' Deric@2004', '2025-01-21 20:58:53'),
(7, ' deric', ' 4582769178', 'deric@ajce.in', 'puthu', 'kerala', ' kottayam', '686519', ' Deric@2005', '2025-01-21 21:08:49'),
(8, ' hari', ' 9746794654', 'hari@mca.in', 'kannuvettikkal', 'kerala', ' kottayam', '686519', ' Hari@2004', '2025-01-22 13:44:58'),
(10, ' amal', ' 9745252847', 'amal@mca.in', 'hjghj', 'edjwygyjgdyj', ' wjkjfhkjwhf', '656519', ' Amal@2004', '2025-01-23 14:26:38'),
(12, 'Abhijith', '6572821476', 'abhijith@mca.in', 'palackal', 'Kerala', 'Kottayam', '686519', 'Abhijith@2004', '2025-01-27 22:01:48'),
(13, 'adityan', '8579641302', 'adityan@mca.in', 'valliplackel', 'Kerala', 'Kottayam', '686519', '$2y$10$5RDuduIWYjq2AJUV0n823.oruHOc7JnLdI5RkitrB94O/Me1BDJeK', '2025-01-27 22:27:06'),
(15, 'alen kuriakose', '9495850142', 'alen@mca.in', 'puthanpurackal', 'Kerala', 'Kottayam', '686519', 'Alen@2004', '2025-01-29 13:34:24'),
(16, 'amal r', '6547893210', 'rohith@mca.in', 'puthu', 'Kerala', 'Kottayam', '686519', '$2y$10$AQd.PcZjGdt2JnZx62qAcevrnmNM94WH3qOUNiRONu0rrDVOh3zKC', '2025-02-05 00:43:16'),
(17, 'rose mary', '7548612390', 'rose@mca.in', 'kalapurakkal', 'Kerala', 'Kottayam', '686519', 'Rose@2004', '2025-02-05 01:02:53'),
(29, 'Rohith', '9748563214', 'rohithrnair2027@mca.ajce.in', 'puthupuredathu', 'Kerala', 'Kottayam', '686519', 'Rohith@2004', '2025-02-11 13:03:21'),
(30, 'rinikuri', '6214785932', 'rnairrohith17@gmail.com', 'puthupuredathu', 'Kerala', 'Kottayam', '686519', 'Rohith@2004', '2025-02-12 20:39:23');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tbl_login`
--
ALTER TABLE `tbl_login`
  ADD PRIMARY KEY (`login_id`),
  ADD KEY `userid` (`userid`);

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
-- AUTO_INCREMENT for table `tbl_login`
--
ALTER TABLE `tbl_login`
  MODIFY `login_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `tbl_signup`
--
ALTER TABLE `tbl_signup`
  MODIFY `userid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tbl_login`
--
ALTER TABLE `tbl_login`
  ADD CONSTRAINT `tbl_login_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `tbl_signup` (`userid`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

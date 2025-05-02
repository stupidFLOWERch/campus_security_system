-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 02, 2025 at 10:47 PM
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
-- Database: `campus_security_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `detections`
--

CREATE TABLE `detections` (
  `id` int(11) NOT NULL,
  `car_id` int(11) NOT NULL,
  `frame_nmr` int(11) NOT NULL,
  `license_number` varchar(20) NOT NULL,
  `license_number_score` float NOT NULL,
  `license_plate_crop` blob NOT NULL,
  `license_plate_crop_thresh` blob NOT NULL,
  `detection_time` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `security_personnel`
--

CREATE TABLE `security_personnel` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `role` enum('ADMIN','SECURITY','STAFF') NOT NULL DEFAULT 'SECURITY',
  `last_accessed_time` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `security_personnel`
--

INSERT INTO `security_personnel` (`id`, `username`, `password`, `name`, `role`, `last_accessed_time`) VALUES
(1, 'abc', '123', 'abc', 'ADMIN', '2025-05-02 20:44:35');

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_registered_details`
--

CREATE TABLE `vehicle_registered_details` (
  `id` int(11) NOT NULL,
  `license_plate` varchar(20) NOT NULL,
  `owner_name` varchar(100) NOT NULL,
  `student_id` varchar(20) NOT NULL,
  `car_brand_model` varchar(100) NOT NULL,
  `car_colour` varchar(50) NOT NULL,
  `permit_type` varchar(50) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `owner_email` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vehicle_registered_details`
--

INSERT INTO `vehicle_registered_details` (`id`, `license_plate`, `owner_name`, `student_id`, `car_brand_model`, `car_colour`, `permit_type`, `phone_number`, `owner_email`) VALUES
(1, 'WYE1083', 'WONG YE', '20401083', 'PROTON SAGA', 'BROWN', 'Blue Permit', '0123456788', 'wye@gmail.com'),
(2, 'BKV1867', 'BOON KV', '20673345', 'MYVI', 'GREY', 'White Permit', '0123456789', 'bkv@gmail.com'),
(3, 'WA3931Q', 'WONG AQ', '20203405', 'NISSAN', 'GREY', 'TEMPORARY PARKING PASS', '0132223422', 'waq@gmail.com'),
(4, 'DEV6686', 'DAVID', '20203948', 'HONDA', 'GREY', 'Temporary Parking Pass', '0132546789', 'davide@gmail.com');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `detections`
--
ALTER TABLE `detections`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `security_personnel`
--
ALTER TABLE `security_personnel`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `vehicle_registered_details`
--
ALTER TABLE `vehicle_registered_details`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `license_plate` (`license_plate`),
  ADD UNIQUE KEY `student_id` (`student_id`),
  ADD UNIQUE KEY `phone_number` (`phone_number`),
  ADD UNIQUE KEY `owner_email` (`owner_email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `detections`
--
ALTER TABLE `detections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `security_personnel`
--
ALTER TABLE `security_personnel`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `vehicle_registered_details`
--
ALTER TABLE `vehicle_registered_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

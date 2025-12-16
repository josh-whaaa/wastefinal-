-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 11, 2025 at 06:08 AM
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
-- Database: `cemo_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_table`
--

CREATE TABLE `admin_table` (
  `admin_id` int(11) NOT NULL,
  `user_role` varchar(225) NOT NULL,
  `first_name` varchar(225) NOT NULL,
  `last_name` varchar(225) NOT NULL,
  `email` varchar(225) NOT NULL,
  `gender` varchar(225) NOT NULL,
  `address` varchar(225) NOT NULL,
  `birth_date` date NOT NULL,
  `contact` int(225) NOT NULL,
  `reset_token` varchar(225) NOT NULL,
  `password` varchar(225) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_table`
--

INSERT INTO `admin_table` (`admin_id`, `user_role`, `first_name`, `last_name`, `email`, `gender`, `address`, `birth_date`, `contact`, `reset_token`, `password`) VALUES
(1, 'admin', 'Louie Jake', 'Narvaez', 'jack@gmail.com', 'Male', 'Pacol, Bago City', '2003-09-27', 912312312, '608c9ade98a44ca4876d1d90eeda119a7199fd7d0a617310e51a4bdeb9739985', '$2y$10$UxT23X10kG2kxkr7B36/4uQ.9A6qN94pb22At2uWDJ0/Cze0aD62a'),
(2, '', 'admin', 'admin', 'admin@gmail.com', 'male', 'admin', '2000-02-04', 912312312, 'bb4bcc8a078a13d4c951356dfd6209ce83e7b2ade1fc7e75acea14db6ec3ec3a', '$2y$10$TsOVCJ6KWhOQs8446NaqYO8jZuMkVx3bzs/wPnFJaqBTUsafJv6vu'),
(3, '', 'Jack', 'Narvaez', 'jake@gmail.com', '', '', '0000-00-00', 0, '', '$2y$10$k.xX7KAgvsx5HQdGZIESAuxE1hjlWSqrc8soIGlLwigqzDDqs2WnO');

-- --------------------------------------------------------

--
-- Table structure for table `barangays_table`
--

CREATE TABLE `barangays_table` (
  `brgy_id` int(24) NOT NULL,
  `barangay` varchar(225) NOT NULL,
  `latitude` double NOT NULL,
  `longitude` double NOT NULL,
  `city` varchar(225) NOT NULL,
  `facebook_link` varchar(225) NOT NULL,
  `link_text` varchar(225) NOT NULL,
  `schedule_id` int(225) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `barangays_table`
--

INSERT INTO `barangays_table` (`brgy_id`, `barangay`, `latitude`, `longitude`, `city`, `facebook_link`, `link_text`, `schedule_id`) VALUES
(1, 'Abuanan', 10.525313, 122.992415, 'Bago City', 'https://www.google.com/', 'test', 0),
(2, 'Alianza', 10.47393, 122.92993, 'Bago City', '', '', 0),
(3, 'Atipuluan', 10.51083, 122.95626, 'Bago City', '', '', 0),
(4, 'Bacong-Montilla', 10.51895, 123.03452, 'Bago City', '', '', 0),
(5, 'Bagroy', 10.47718, 122.87212, 'Bago City', '', '', 0),
(6, 'Balingasag', 10.53161, 122.84595, 'Bago City', '', '', 0),
(7, 'Binubuhan', 10.45755, 123.00718, 'Bago City', '', '', 0),
(8, 'Busay', 10.53718, 122.88822, 'Bago City', '', '', 0),
(9, 'Calumangan', 10.56009, 122.8768, 'Bago City', '', '', 0),
(10, 'Caridad', 10.48198, 122.90567, 'Bago City', '', '', 0),
(11, 'Don Jorge L. Araneta', 10.47642, 122.94615, 'Bago City', '', '', 0),
(12, 'Dulao', 10.54916, 122.95165, 'Bago City', '', '', 0),
(13, 'Ilijan', 10.453, 123.05486, 'Bago City', '', '', 0),
(14, 'Lag-Asan', 10.53006, 122.838575, 'Bago City', '', '', 0),
(15, 'Ma-ao', 10.49019, 122.99165, 'Bago City', '', '', 0),
(16, 'Mailum', 10.46211, 123.0492, 'Bago City', '', '', 0),
(17, 'Malingin', 10.49395, 122.91783, 'Bago City', 'https://www.facebook.com/BrgyMalingin', 'Brgy Malingin Official', 0),
(18, 'Napoles', 10.51267, 122.89781, 'Bago City', '', '', 0),
(19, 'Pacol', 10.49507, 122.86697, 'Bago City', 'https://www.facebook.com/SKPacol', 'SK Brgy. Pacol', 0),
(20, 'Poblacion', 10.54115, 122.83539, 'Bago City', '', '', 0),
(21, 'Sagasa', 10.46983, 122.89283, 'Bago City', '', '', 0),
(22, 'Tabunan', 10.57625, 122.93727, 'Bago City', '', '', 0),
(23, 'Taloc', 10.5873, 122.90942, 'Bago City', '', '', 0),
(24, 'Sampinit', 10.54426, 122.85341, 'Bago City', '', '', 0);

-- --------------------------------------------------------

--
-- Table structure for table `client_table`
--

CREATE TABLE `client_table` (
  `client_id` int(225) NOT NULL,
  `first_name` varchar(225) NOT NULL,
  `last_name` varchar(225) NOT NULL,
  `contact` int(11) NOT NULL,
  `email` varchar(225) NOT NULL,
  `barangay` varchar(225) NOT NULL,
  `password` varchar(225) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `client_table`
--

INSERT INTO `client_table` (`client_id`, `first_name`, `last_name`, `contact`, `email`, `barangay`, `password`) VALUES
(1, 'Jason', 'Test', 0, 'jason@gmail.com', 'Busay', '$2y$10$7xVCoZKLJ/etEPlMMAALYurCMbI5SFHAqx.NNdOW8JShDB68hOjiG'),
(2, 'Joshua', 'Elgario', 0, 'joshua@gmail.com', 'Alianza', '$2y$10$9ytvFSOUOOLQRrrKBcXs3Occ3FWW.0XBy9mxYhHh8jfYakLeldLfS');

-- --------------------------------------------------------

--
-- Table structure for table `driver_table`
--

CREATE TABLE `driver_table` (
  `driver_id` int(6) NOT NULL,
  `first_name` varchar(225) NOT NULL,
  `last_name` varchar(225) NOT NULL,
  `address` varchar(225) NOT NULL,
  `contact` int(11) NOT NULL,
  `age` int(225) NOT NULL,
  `gender` varchar(225) NOT NULL,
  `password` varchar(225) NOT NULL,
  `location_id` int(225) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `driver_table`
--

INSERT INTO `driver_table` (`driver_id`, `first_name`, `last_name`, `address`, `contact`, `age`, `gender`, `password`, `location_id`) VALUES
(1, 'Angel', 'Adlaon', 'Brgy Malingin', 932132112, 35, 'Male', 'Jack123', 1);

-- --------------------------------------------------------

--
-- Table structure for table `dumpsite_table`
--

CREATE TABLE `dumpsite_table` (
  `dump_id` int(11) NOT NULL,
  `dumpsite_capacity` varchar(225) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gps_location`
--

CREATE TABLE `gps_location` (
  `location_id` int(11) NOT NULL,
  `driver_id` int(11) NOT NULL,
  `latitude` double NOT NULL,
  `longitude` double NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_table`
--

CREATE TABLE `maintenance_table` (
  `maintenance_id` int(11) NOT NULL,
  `date_time` datetime NOT NULL,
  `waste_service_id` int(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `maintenance_table`
--

INSERT INTO `maintenance_table` (`maintenance_id`, `date_time`, `waste_service_id`) VALUES
(1, '0000-00-00 00:00:00', 1),
(2, '0000-00-00 00:00:00', 2);

-- --------------------------------------------------------

--
-- Table structure for table `route_table`
--

CREATE TABLE `route_table` (
  `route_id` int(225) NOT NULL,
  `brgy_id` int(225) NOT NULL,
  `waste_service_id` int(225) NOT NULL,
  `driver_id` int(11) NOT NULL,
  `start_point` varchar(225) NOT NULL,
  `end_point` varchar(225) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `route_table`
--

INSERT INTO `route_table` (`route_id`, `brgy_id`, `waste_service_id`, `driver_id`, `start_point`, `end_point`) VALUES
(1, 14, 1, 1, 'Bago City Hall', 'Pacol');

-- --------------------------------------------------------

--
-- Table structure for table `schedule_table`
--

CREATE TABLE `schedule_table` (
  `schedule_id` int(11) NOT NULL,
  `day` varchar(225) NOT NULL,
  `time` time NOT NULL,
  `status` varchar(225) NOT NULL,
  `waste_service_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schedule_table`
--

INSERT INTO `schedule_table` (`schedule_id`, `day`, `time`, `status`, `waste_service_id`) VALUES
(1, 'Monday', '08:00:00', 'Assigned', 1),
(2, 'Tuesday ', '00:00:00', 'Vacant', 1),
(3, 'Wednesday', '00:00:00', 'Pending', 1),
(4, 'Thursday', '00:00:00', 'Waste Collected	', 1),
(5, 'Friday', '00:00:00', 'Waste Collected	', 1),
(6, 'Saturday', '00:00:00', 'Pending', 1),
(7, 'Sunday', '00:00:00', 'Vacant', 1);

-- --------------------------------------------------------

--
-- Table structure for table `sensor`
--

CREATE TABLE `sensor` (
  `sensor_id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL,
  `timestamp` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `service_assignment_table`
--

CREATE TABLE `service_assignment_table` (
  `service_ass_id` int(11) NOT NULL,
  `waste_service_id` int(11) NOT NULL,
  `vehicle_type` varchar(225) NOT NULL,
  `schedule_id` int(225) NOT NULL,
  `brgy_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_assignment_table`
--

INSERT INTO `service_assignment_table` (`service_ass_id`, `waste_service_id`, `vehicle_type`, `schedule_id`, `brgy_id`) VALUES
(1, 1, 'Dump Truck', 1, 12);

-- --------------------------------------------------------

--
-- Table structure for table `tracking_table`
--

CREATE TABLE `tracking_table` (
  `tracking_id` int(225) NOT NULL,
  `route_id` int(225) NOT NULL,
  `status` varchar(225) NOT NULL,
  `date_time` datetime NOT NULL,
  `location_id` int(225) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `waste_collection_table`
--

CREATE TABLE `waste_collection_table` (
  `waste_collection_id` int(11) NOT NULL,
  `waste_service_id` int(11) NOT NULL,
  `status` varchar(225) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `waste_management`
--

CREATE TABLE `waste_management` (
  `waste_management_id` int(11) NOT NULL,
  `waste_collection_id` int(11) NOT NULL,
  `dump_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `waste_service_table`
--

CREATE TABLE `waste_service_table` (
  `waste_service_id` int(6) NOT NULL,
  `vehicle_name` varchar(225) NOT NULL,
  `driver_id` int(11) NOT NULL,
  `vehicle_capacity` varchar(225) NOT NULL,
  `schedule_id` int(225) NOT NULL,
  `maintenance_id` int(225) NOT NULL,
  `route_id` int(11) NOT NULL,
  `plate_no` int(225) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `waste_service_table`
--

INSERT INTO `waste_service_table` (`waste_service_id`, `vehicle_name`, `driver_id`, `vehicle_capacity`, `schedule_id`, `maintenance_id`, `route_id`, `plate_no`) VALUES
(1, 'Vehicle 1', 1, '3 - 5 tons', 1, 2, 1, 3210);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_table`
--
ALTER TABLE `admin_table`
  ADD PRIMARY KEY (`admin_id`);

--
-- Indexes for table `barangays_table`
--
ALTER TABLE `barangays_table`
  ADD PRIMARY KEY (`brgy_id`);

--
-- Indexes for table `client_table`
--
ALTER TABLE `client_table`
  ADD PRIMARY KEY (`client_id`);

--
-- Indexes for table `driver_table`
--
ALTER TABLE `driver_table`
  ADD PRIMARY KEY (`driver_id`);

--
-- Indexes for table `dumpsite_table`
--
ALTER TABLE `dumpsite_table`
  ADD PRIMARY KEY (`dump_id`);

--
-- Indexes for table `gps_location`
--
ALTER TABLE `gps_location`
  ADD PRIMARY KEY (`location_id`);

--
-- Indexes for table `maintenance_table`
--
ALTER TABLE `maintenance_table`
  ADD PRIMARY KEY (`maintenance_id`);

--
-- Indexes for table `route_table`
--
ALTER TABLE `route_table`
  ADD PRIMARY KEY (`route_id`);

--
-- Indexes for table `schedule_table`
--
ALTER TABLE `schedule_table`
  ADD PRIMARY KEY (`schedule_id`);

--
-- Indexes for table `sensor`
--
ALTER TABLE `sensor`
  ADD PRIMARY KEY (`sensor_id`);

--
-- Indexes for table `service_assignment_table`
--
ALTER TABLE `service_assignment_table`
  ADD PRIMARY KEY (`service_ass_id`);

--
-- Indexes for table `tracking_table`
--
ALTER TABLE `tracking_table`
  ADD PRIMARY KEY (`tracking_id`);

--
-- Indexes for table `waste_collection_table`
--
ALTER TABLE `waste_collection_table`
  ADD PRIMARY KEY (`waste_collection_id`);

--
-- Indexes for table `waste_management`
--
ALTER TABLE `waste_management`
  ADD PRIMARY KEY (`waste_management_id`);

--
-- Indexes for table `waste_service_table`
--
ALTER TABLE `waste_service_table`
  ADD PRIMARY KEY (`waste_service_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_table`
--
ALTER TABLE `admin_table`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `barangays_table`
--
ALTER TABLE `barangays_table`
  MODIFY `brgy_id` int(24) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `client_table`
--
ALTER TABLE `client_table`
  MODIFY `client_id` int(225) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `driver_table`
--
ALTER TABLE `driver_table`
  MODIFY `driver_id` int(6) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `dumpsite_table`
--
ALTER TABLE `dumpsite_table`
  MODIFY `dump_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gps_location`
--
ALTER TABLE `gps_location`
  MODIFY `location_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `maintenance_table`
--
ALTER TABLE `maintenance_table`
  MODIFY `maintenance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `route_table`
--
ALTER TABLE `route_table`
  MODIFY `route_id` int(225) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `schedule_table`
--
ALTER TABLE `schedule_table`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `sensor`
--
ALTER TABLE `sensor`
  MODIFY `sensor_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `service_assignment_table`
--
ALTER TABLE `service_assignment_table`
  MODIFY `service_ass_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tracking_table`
--
ALTER TABLE `tracking_table`
  MODIFY `tracking_id` int(225) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `waste_collection_table`
--
ALTER TABLE `waste_collection_table`
  MODIFY `waste_collection_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `waste_management`
--
ALTER TABLE `waste_management`
  MODIFY `waste_management_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `waste_service_table`
--
ALTER TABLE `waste_service_table`
  MODIFY `waste_service_id` int(6) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

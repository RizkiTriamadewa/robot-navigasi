-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 31, 2026 at 02:28 AM
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
-- Database: `robot_dashboard`
--

-- --------------------------------------------------------

--
-- Table structure for table `daily_logs`
--

CREATE TABLE `daily_logs` (
  `id` int(11) NOT NULL,
  `log_date` date DEFAULT NULL,
  `distance_m` float DEFAULT 0,
  `water_used_ml` float DEFAULT 0,
  `battery_percent` float DEFAULT 100,
  `path_data` longtext DEFAULT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `spray_data` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `daily_logs`
--

INSERT INTO `daily_logs` (`id`, `log_date`, `distance_m`, `water_used_ml`, `battery_percent`, `path_data`, `last_updated`, `spray_data`) VALUES
(1, '2026-03-09', 37, 100, 92.6, '[{\"x\":400,\"y\":200},{\"x\":390,\"y\":200},{\"x\":380,\"y\":200},{\"x\":370,\"y\":200},{\"x\":360,\"y\":200},{\"x\":350,\"y\":200},{\"x\":340,\"y\":200},{\"x\":330,\"y\":200},{\"x\":320,\"y\":200},{\"x\":320,\"y\":190},{\"x\":320,\"y\":180},{\"x\":320,\"y\":170},{\"x\":320,\"y\":160},{\"x\":320,\"y\":150},{\"x\":320,\"y\":140},{\"x\":330,\"y\":140},{\"x\":340,\"y\":140},{\"x\":350,\"y\":140},{\"x\":360,\"y\":140},{\"x\":370,\"y\":140},{\"x\":380,\"y\":140},{\"x\":390,\"y\":140},{\"x\":390,\"y\":130},{\"x\":390,\"y\":120},{\"x\":390,\"y\":110},{\"x\":390,\"y\":100},{\"x\":400,\"y\":100},{\"x\":410,\"y\":100},{\"x\":420,\"y\":100},{\"x\":430,\"y\":100},{\"x\":440,\"y\":100},{\"x\":450,\"y\":100},{\"x\":460,\"y\":100},{\"x\":470,\"y\":100},{\"x\":480,\"y\":100},{\"x\":480,\"y\":110},{\"x\":480,\"y\":120},{\"x\":480,\"y\":130},{\"x\":480,\"y\":140},{\"x\":480,\"y\":150},{\"x\":480,\"y\":160},{\"x\":480,\"y\":170},{\"x\":480,\"y\":180},{\"x\":480,\"y\":190},{\"x\":480,\"y\":200},{\"x\":470,\"y\":200},{\"x\":460,\"y\":200},{\"x\":450,\"y\":200},{\"x\":440,\"y\":200},{\"x\":430,\"y\":200},{\"x\":420,\"y\":200},{\"x\":410,\"y\":200},{\"x\":400,\"y\":200},{\"x\":400,\"y\":210},{\"x\":400,\"y\":220},{\"x\":400,\"y\":230},{\"x\":400,\"y\":240},{\"x\":400,\"y\":250},{\"x\":400,\"y\":260},{\"x\":410,\"y\":260},{\"x\":420,\"y\":260},{\"x\":430,\"y\":260},{\"x\":440,\"y\":260},{\"x\":450,\"y\":260},{\"x\":460,\"y\":260},{\"x\":470,\"y\":260},{\"x\":470,\"y\":250},{\"x\":470,\"y\":240},{\"x\":470,\"y\":230},{\"x\":480,\"y\":230},{\"x\":490,\"y\":230},{\"x\":500,\"y\":230},{\"x\":500,\"y\":220},{\"x\":500,\"y\":210},{\"x\":500,\"y\":200}]', '2026-03-09 12:11:22', NULL),
(2, '2026-03-10', 16.5, 200, 96.7, '[{\"x\":400,\"y\":200},{\"x\":390,\"y\":200},{\"x\":380,\"y\":200},{\"x\":370,\"y\":200},{\"x\":360,\"y\":200},{\"x\":350,\"y\":200},{\"x\":340,\"y\":200},{\"x\":330,\"y\":200},{\"x\":320,\"y\":200},{\"x\":310,\"y\":200},{\"x\":300,\"y\":200},{\"x\":290,\"y\":200},{\"x\":280,\"y\":200},{\"x\":270,\"y\":200},{\"x\":260,\"y\":200},{\"x\":260,\"y\":190},{\"x\":260,\"y\":180},{\"x\":260,\"y\":170},{\"x\":260,\"y\":160},{\"x\":260,\"y\":150},{\"x\":260,\"y\":140},{\"x\":260,\"y\":130},{\"x\":260,\"y\":120},{\"x\":260,\"y\":110},{\"x\":270,\"y\":110},{\"x\":280,\"y\":110},{\"x\":290,\"y\":110},{\"x\":300,\"y\":110},{\"x\":310,\"y\":110},{\"x\":320,\"y\":110},{\"x\":330,\"y\":110},{\"x\":340,\"y\":110},{\"x\":350,\"y\":110},{\"x\":360,\"y\":110}]', '2026-03-10 12:10:53', '[{\"x\":350,\"y\":200},{\"x\":260,\"y\":160},{\"x\":310,\"y\":110},{\"x\":310,\"y\":110}]');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `daily_logs`
--
ALTER TABLE `daily_logs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `log_date` (`log_date`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `daily_logs`
--
ALTER TABLE `daily_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

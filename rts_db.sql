-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 26, 2026 at 09:30 AM
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
-- Database: `rts_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ticket_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `ticket_id`, `message`, `is_read`, `created_at`) VALUES
(1, 5, 1, 'Your request for \'Transcript of Records (TOR)\' has been submitted successfully. Ticket: TKT-20260226-80DFD', 0, '2026-02-26 08:23:36'),
(2, 5, 1, 'Your ticket TKT-20260226-80DFD status has been updated to: Ready for Pickup.', 0, '2026-02-26 08:24:54');

-- --------------------------------------------------------

--
-- Table structure for table `tickets`
--

CREATE TABLE `tickets` (
  `id` int(11) NOT NULL,
  `ticket_number` varchar(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `request_type` varchar(100) NOT NULL,
  `purpose` text DEFAULT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `school_year` varchar(20) DEFAULT NULL,
  `copies` int(11) DEFAULT 1,
  `notes` text DEFAULT NULL,
  `status` enum('pending','in_progress','ready','completed','rejected') DEFAULT 'pending',
  `admin_remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tickets`
--

INSERT INTO `tickets` (`id`, `ticket_number`, `user_id`, `request_type`, `purpose`, `semester`, `school_year`, `copies`, `notes`, `status`, `admin_remarks`, `created_at`, `updated_at`) VALUES
(1, 'TKT-20260226-80DFD', 5, 'tor', 'board exam', '1st Semester', '2022-2025', 2, '', 'ready', '', '2026-02-26 08:23:36', '2026-02-26 08:24:54');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `student_id` varchar(20) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `course` varchar(100) DEFAULT NULL,
  `year_level` varchar(20) DEFAULT NULL,
  `role` enum('student','admin') DEFAULT 'student',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `student_id`, `first_name`, `last_name`, `email`, `password`, `course`, `year_level`, `role`, `created_at`) VALUES
(1, 'ADMIN001', 'System', 'Administrator', 'admin@rts.edu', '$2y$10$FUk06RmgETzEkTu9wwQuoem92s3ghUgQX5At7wkNHflPiakagcFwi', NULL, NULL, 'admin', '2026-02-26 08:18:05'),
(5, 'SCC-22-00015058', 'angel', 'sabellano', 'angelsabellanolibay22@gmail.com', '$2y$10$g/z05eCB42KncAeyj.0suOYTCKu0RY2WhFxiIhW.4sxSVjdyQyqbe', 'BS Information Technology', '2nd Year', 'student', '2026-02-26 08:19:28'),
(59, 'SCC-22-00015057', 'kc', 'balse', 'kc@gmail.com', '$2y$10$bNQOsalmzhMhjcCu823gIegLO4/bq2U54xq9PDvRQrobEBmFX/Jg2', 'BS Education', '3rd Year', 'student', '2026-02-26 08:27:14');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ticket_number` (`ticket_number`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tickets`
--
ALTER TABLE `tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `tickets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

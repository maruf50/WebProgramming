-- phpMyAdmin SQL Dump
-- UniRoom Database for XAMPP
-- Ready to import into phpMyAdmin
--
-- Host: 127.0.0.1
-- Generation Time: Jan 25, 2026
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
-- Create and use database
--
DROP DATABASE IF EXISTS `uniroom_db`;
CREATE DATABASE `uniroom_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `uniroom_db`;

-- --------------------------------------------------------

--
-- Table structure for table `blocked_rooms`
--

CREATE TABLE `blocked_rooms` (
  `id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `blocked_date` date NOT NULL,
  `reason` text DEFAULT NULL,
  `blocked_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `blocked_rooms`
--

INSERT INTO `blocked_rooms` (`id`, `room_id`, `blocked_date`, `reason`, `blocked_by`, `created_at`) VALUES
(1, 7, '2026-01-30', 'Under maintenance', 2, '2026-01-23 06:10:21');

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `slot_id` int(11) NOT NULL,
  `booking_date` date NOT NULL,
  `purpose` text DEFAULT NULL,
  `status` enum('pending','approved','rejected','cancelled') DEFAULT 'pending',
  `priority` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `room_id`, `slot_id`, `booking_date`, `purpose`, `status`, `priority`, `created_at`) VALUES
(1, 1, 6, 2, '2026-01-26', 'Study group meeting', 'approved', 1, '2026-01-23 05:51:24'),
(2, 1, 2, 3, '2026-01-27', 'Project discussion', 'pending', 1, '2026-01-23 06:15:34'),
(3, 3, 3, 1, '2026-01-26', 'Extra class for students', 'approved', 3, '2026-01-23 06:55:20'),
(4, 4, 5, 4, '2026-01-28', 'Club meeting', 'pending', 2, '2026-01-23 06:53:59');

-- --------------------------------------------------------

--
-- Table structure for table `campus_events`
--

CREATE TABLE `campus_events` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `start_at` datetime NOT NULL,
  `end_at` datetime NOT NULL,
  `location` varchar(200) DEFAULT NULL,
  `room_id` int(11) DEFAULT NULL,
  `audience` enum('all','students','faculty','club') DEFAULT 'all',
  `is_published` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `campus_events`
--

INSERT INTO `campus_events` (`id`, `title`, `description`, `start_at`, `end_at`, `location`, `room_id`, `audience`, `is_published`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'AI Workshop', 'Hands-on intro session', '2026-02-01 10:00:00', '2026-02-01 12:00:00', 'Room 402', NULL, 'students', 1, NULL, '2026-01-23 11:23:14', '2026-01-23 11:23:14'),
(2, 'Debate Finals', 'Inter-college finals', '2026-02-05 14:00:00', '2026-02-05 17:00:00', 'Main Hall', NULL, 'all', 1, NULL, '2026-01-23 11:23:14', '2026-01-23 11:23:14'),
(3, 'Coding Combat', 'Inter universitry coding competition', '2026-02-01 10:00:00', '2026-02-01 12:00:00', 'Room 403', NULL, 'students', 1, NULL, '2026-01-23 11:44:19', '2026-01-23 12:39:07');

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `room_number` varchar(20) NOT NULL,
  `room_name` varchar(100) DEFAULT NULL,
  `capacity` int(11) NOT NULL,
  `building` varchar(50) DEFAULT NULL,
  `floor` varchar(20) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `room_number`, `room_name`, `capacity`, `building`, `floor`, `description`, `is_available`, `created_at`) VALUES
(1, '101', 'Room 101', 30, 'Main Building', '1', 'General purpose room', 1, '2026-01-22 14:32:03'),
(2, '102', 'Room 102', 25, 'Main Building', '1', 'Study room', 1, '2026-01-22 14:32:03'),
(3, '103', 'Room 103', 40, 'Main Building', '1', 'Lecture room', 1, '2026-01-22 14:32:03'),
(4, '104', 'Room 104', 35, 'Main Building', '1', 'Seminar room', 1, '2026-01-22 14:32:03'),
(5, '105', 'Room 105', 20, 'Main Building', '1', 'Small group room', 1, '2026-01-22 14:32:03'),
(6, '201', 'Room 201', 50, 'Main Building', '2', 'Large classroom', 1, '2026-01-22 14:32:03'),
(7, '202', 'Room 202', 30, 'Main Building', '2', 'Computer lab', 1, '2026-01-22 14:32:03'),
(8, '203', 'Room 203', 25, 'Main Building', '2', 'Quiet study', 1, '2026-01-22 14:32:03'),
(9, '204', 'Room 204', 35, 'Main Building', '2', 'Meeting room', 1, '2026-01-22 14:32:03'),
(10, '1001', 'Room 1001', 25, '0', '10', NULL, 1, '2026-01-23 06:34:04');

-- --------------------------------------------------------

--
-- Table structure for table `room_reports`
--

CREATE TABLE `room_reports` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `severity` enum('low','medium','high') DEFAULT 'low',
  `status` enum('open','in_progress','resolved','closed') DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `room_reports`
--

INSERT INTO `room_reports` (`id`, `user_id`, `room_id`, `title`, `description`, `severity`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 6, 'Broken AC', 'The air conditioner is not working properly', 'medium', 'open', '2026-01-23 06:18:38', '2026-01-23 06:18:38'),
(2, 1, 8, 'Projector Issue', 'The projector screen has a tear', 'low', 'open', '2026-01-23 09:33:54', '2026-01-23 09:33:54');

-- --------------------------------------------------------

--
-- Table structure for table `time_slots`
--

CREATE TABLE `time_slots` (
  `id` int(11) NOT NULL,
  `slot_name` varchar(50) DEFAULT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `time_slots`
--

INSERT INTO `time_slots` (`id`, `slot_name`, `start_time`, `end_time`) VALUES
(1, 'Morning Session 1', '09:30:00', '11:00:00'),
(2, 'Morning Session 2', '11:00:00', '12:30:00'),
(3, 'Afternoon Session 1', '13:00:00', '14:30:00'),
(4, 'Afternoon Session 2', '14:30:00', '16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('student','faculty','admin','club') DEFAULT 'student',
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `student_id` varchar(50) DEFAULT NULL,
  `club_name` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `first_name`, `last_name`, `student_id`, `club_name`, `is_active`, `created_at`, `last_login`) VALUES
(1, 'student1', 'student@example.com', 'password123', 'student', 'John', 'Doe', 'STU001', NULL, 1, '2026-01-22 14:35:15', NULL),
(2, 'admin', 'admin@example.com', 'admin123', 'admin', 'Admin', 'User', NULL, NULL, 1, '2026-01-23 05:58:10', NULL),
(3, 'faculty1', 'faculty@example.com', 'faculty123', 'faculty', 'Jane', 'Smith', NULL, NULL, 1, '2026-01-23 06:45:45', NULL),
(4, 'club1', 'club@example.com', 'club123', 'club', 'Club', 'Leader', NULL, 'Tech Club', 1, '2026-01-23 06:52:22', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `blocked_rooms`
--
ALTER TABLE `blocked_rooms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `room_id` (`room_id`),
  ADD KEY `blocked_by` (`blocked_by`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `room_id` (`room_id`),
  ADD KEY `slot_id` (`slot_id`),
  ADD KEY `idx_booking_date` (`booking_date`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `campus_events`
--
ALTER TABLE `campus_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `room_id` (`room_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_events_start` (`start_at`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `room_number` (`room_number`);

--
-- Indexes for table `room_reports`
--
ALTER TABLE `room_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_reports_status` (`status`),
  ADD KEY `idx_reports_room` (`room_id`);

--
-- Indexes for table `time_slots`
--
ALTER TABLE `time_slots`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_slot` (`start_time`,`end_time`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `blocked_rooms`
--
ALTER TABLE `blocked_rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `campus_events`
--
ALTER TABLE `campus_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `room_reports`
--
ALTER TABLE `room_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `time_slots`
--
ALTER TABLE `time_slots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `blocked_rooms`
--
ALTER TABLE `blocked_rooms`
  ADD CONSTRAINT `blocked_rooms_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`),
  ADD CONSTRAINT `blocked_rooms_ibfk_2` FOREIGN KEY (`blocked_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`),
  ADD CONSTRAINT `bookings_ibfk_3` FOREIGN KEY (`slot_id`) REFERENCES `time_slots` (`id`);

--
-- Constraints for table `campus_events`
--
ALTER TABLE `campus_events`
  ADD CONSTRAINT `campus_events_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`),
  ADD CONSTRAINT `campus_events_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `room_reports`
--
ALTER TABLE `room_reports`
  ADD CONSTRAINT `room_reports_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `room_reports_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

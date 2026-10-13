-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 24, 2026 at 04:12 AM
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
-- Database: `cookie`
--

-- --------------------------------------------------------

--
-- Table structure for table `booking`
--

CREATE TABLE `booking` (
  `booking_id` int(20) NOT NULL,
  `user_id` varchar(20) NOT NULL,
  `facility_id` varchar(30) NOT NULL,
  `date` date NOT NULL,
  `time` time NOT NULL,
  `purpose` varchar(255) DEFAULT NULL,
  `emergency_phone` varchar(30) DEFAULT NULL,
  `remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `booking`
--

INSERT INTO `booking` (`booking_id`, `user_id`, `facility_id`, `date`, `time`, `purpose`, `emergency_phone`, `remarks`) VALUES
(16, '12', '3', '2026-05-25', '08:30:00', 'German Class', '012-678 4521', ''),
(17, '13', '4', '2026-05-27', '14:00:00', 'Class Session', '014-882 6190', ''),
(18, '14', '6', '2026-05-26', '08:30:00', 'Class Session', '013-772 4608', ''),
(19, '15', '9', '2026-05-31', '10:00:00', 'Class Session', '017-664 2198', '');

-- --------------------------------------------------------

--
-- Table structure for table `facilities`
--

CREATE TABLE `facilities` (
  `facility_id` int(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `location` varchar(100) NOT NULL,
  `category` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `peripherals` text DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `person_in_charge` varchar(100) DEFAULT NULL,
  `pic_phone` varchar(30) DEFAULT NULL,
  `pic_details` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `facilities`
--

INSERT INTO `facilities` (`facility_id`, `name`, `location`, `category`, `description`, `peripherals`, `image_url`, `person_in_charge`, `pic_phone`, `pic_details`) VALUES
(1, 'KT5-10-002', 'Kompleks Teknology 5', 'Mini Cinema', 'A mini cinema room designed for multimedia presentations, screenings, seminars, and immersive learning sessions.', 'Large projection screen, cinema projector, surround sound speakers, fixed cinema seating, HDMI connection, lecturer table, air conditioner, blackout lighting.', 'KT/Mini_Cinema.jpg', 'Mohd Farid bin Ismail', '011-2345 9087', 'Multimedia and AV support officer for Kompleks Teknology 5. Contact for projector setup, audio system support, seating access, and emergency room assistance.'),
(2, 'KT1-03-012', 'Kompleks Teknology 1', 'Classroom', 'A standard classroom suitable for lectures, tutorials, briefings, and small group learning sessions.', 'Projector, whiteboard, lecturer table, student desks and chairs, air conditioner, Wi-Fi access point, power sockets.', 'KT/mechanical_classroom.jpg', 'Nur Aisyah binti Salleh', '013-445 7821', 'Academic facilities coordinator for Kompleks Teknology 1. Available during office hours from 8:00 AM to 5:00 PM for classroom access and basic equipment support.'),
(3, 'KT1-01-002', 'Kompleks Teknology 1', 'Lecture Hall', 'A lecture hall suitable for seminars, academic briefings, presentations, and large group teaching sessions.', 'Projector, PA sound system, whiteboard, lecturer computer, HDMI connection, air conditioner, Wi-Fi access point, student desks and chairs.', 'KT/lecture_hall.jpg', 'Ahmad Firdaus bin Rahman', '012-678 4521', 'Facility coordinator for Kompleks Teknology 1. Available during office hours from 8:00 AM to 5:00 PM for booking support and technical assistance.'),
(4, 'KT5-08-001', 'Kompleks Teknology 5', 'Cybersecurity Lab', 'A cybersecurity training room suitable for cyber defense simulations, threat monitoring exercises, and digital forensics practice.', 'Desktop workstations, dual monitors, instructor console, threat monitoring display wall, network simulation tools, Wi-Fi access point, projector, whiteboard.', 'KT/cyber_battlefield.jpg', 'Siti Hajar binti Roslan', '014-882 6190', 'Cybersecurity lab coordinator for Kompleks Teknology 5. Contact for lab access, network simulation setup, and technical support during cyber training sessions.'),
(5, 'KT5-09-001', 'Kompleks Teknology 5', 'Electrical Lab', 'An electrical engineering lab for automation, control system practice, circuit testing, and hands-on technical training.', 'Automation training rig, electrical control panels, multimeters, power supply units, wiring trainer, PLC module, safety equipment, lecturer workstation.', 'KT/electrical_engineering_lab.jpg', 'Rizal bin Mahmud', '012-904 3376', 'Electrical lab officer for Kompleks Teknology 5. Contact for equipment preparation, safety briefing, and emergency support during practical sessions.'),
(6, 'KT5-11-001', 'Kompleks Teknology 5', 'Software Testing Lab', 'A software testing lab for IoT application testing, quality assurance practice, debugging sessions, and software validation activities.', 'Desktop workstations, software testing tools, IoT test devices, dual monitors, lecturer workstation, projector, whiteboard, Wi-Fi access point, power sockets.', 'KT/software_testing_lab.jpg', 'Farah Nabila binti Omar', '013-772 4608', 'Software testing lab coordinator for Kompleks Teknology 5. Contact for lab access, testing device preparation, software setup, and technical support during practical sessions.'),
(7, 'KT5-12-001', 'Kompleks Teknology 5', 'Network Lab', 'A networking lab for routing, switching, server setup, network troubleshooting, and infrastructure practice.', 'Desktop workstations, network racks, switches, routers, patch panels, LAN cables, server cabinet, projector, whiteboard, Wi-Fi access point.', 'KT/network_lab.jpg', 'Muhammad Danish bin Zulkifli', '019-556 2840', 'Network lab coordinator for Kompleks Teknology 5. Contact for rack access, equipment preparation, cabling support, and network troubleshooting assistance.'),
(8, 'KT1-03-013', 'Kompleks Teknology 1', 'Classroom', 'A standard classroom suitable for lectures, tutorials, group discussions, and academic briefing sessions.', 'Projector, whiteboard, notice board, lecturer table, student desks and chairs, air conditioner, Wi-Fi access point, power sockets.', 'KT/classroom.jpg', 'Nurul Izzati binti Kamal', '012-771 3482', 'Classroom coordinator for Kompleks Teknology 1. Contact for room access, basic classroom setup, and teaching equipment support.'),
(9, 'KT1-04-001', 'Kompleks Teknology 1', 'Classroom', 'A computer classroom suitable for software lessons, practical tutorials, assessments, and guided digital learning activities.', 'Desktop computers, projector, instructor computer, whiteboard, TV display, speakers, air conditioner, Wi-Fi access point, power sockets.', 'KT/classroom2.jpg', 'Hakim bin Razak', '017-664 2198', 'Computer classroom coordinator for Kompleks Teknology 1. Contact for PC preparation, classroom access, and basic technical support.'),
(10, 'KT1-04-002', 'Kompleks Teknology 1', 'Classroom', 'A multimedia classroom suitable for communication classes, language learning, presentations, and small practical sessions.', 'Desktop computers, headsets, projector, whiteboard, lecturer workstation, speakers, air conditioner, Wi-Fi access point, student chairs.', 'KT/classroom3.jpg', 'Aina Farhana binti Zainal', '018-334 7025', 'Multimedia classroom coordinator for Kompleks Teknology 1. Contact for headset setup, presentation equipment, classroom access, and technical assistance.');

-- --------------------------------------------------------

--
-- Table structure for table `facility_categories`
--

CREATE TABLE `facility_categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `facility_categories`
--

INSERT INTO `facility_categories` (`category_id`, `category_name`) VALUES
(5, 'Auditorium'),
(1, 'Classroom'),
(2, 'Computer Lab'),
(5492, 'Cybersecurity Lab'),
(5493, 'Electrical Lab'),
(37, 'Halls'),
(36, 'Lab'),
(7, 'Laboratory'),
(3, 'Lecture Hall'),
(4, 'Meeting Room'),
(2503, 'Mini Cinema'),
(6728, 'Network Lab'),
(6601, 'Software Testing Lab'),
(6, 'Workshop');

-- --------------------------------------------------------

--
-- Table structure for table `facility_descriptions`
--

CREATE TABLE `facility_descriptions` (
  `description_id` int(11) NOT NULL,
  `facility_id` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `unique_details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `facility_descriptions`
--

INSERT INTO `facility_descriptions` (`description_id`, `facility_id`, `description`, `unique_details`, `created_at`, `updated_at`) VALUES
(1, 1, 'A mini cinema room designed for multimedia presentations, screenings, seminars, and immersive learning sessions.', NULL, '2026-05-24 01:41:24', '2026-05-24 01:41:24'),
(2, 2, 'A standard classroom suitable for lectures, tutorials, briefings, and small group learning sessions.', NULL, '2026-05-24 01:41:24', '2026-05-24 01:41:24'),
(3, 3, 'A lecture hall suitable for seminars, academic briefings, presentations, and large group teaching sessions.', NULL, '2026-05-24 01:41:24', '2026-05-24 01:41:24'),
(4, 4, 'A cybersecurity training room suitable for cyber defense simulations, threat monitoring exercises, and digital forensics practice.', NULL, '2026-05-24 01:41:24', '2026-05-24 01:41:24'),
(5, 5, 'An electrical engineering lab for automation, control system practice, circuit testing, and hands-on technical training.', NULL, '2026-05-24 01:41:24', '2026-05-24 01:41:24'),
(6, 6, 'A software testing lab for IoT application testing, quality assurance practice, debugging sessions, and software validation activities.', NULL, '2026-05-24 01:41:24', '2026-05-24 01:41:24'),
(7, 7, 'A networking lab for routing, switching, server setup, network troubleshooting, and infrastructure practice.', NULL, '2026-05-24 01:41:24', '2026-05-24 01:41:24'),
(8, 8, 'A standard classroom suitable for lectures, tutorials, group discussions, and academic briefing sessions.', NULL, '2026-05-24 01:41:24', '2026-05-24 01:41:24'),
(9, 9, 'A computer classroom suitable for software lessons, practical tutorials, assessments, and guided digital learning activities.', NULL, '2026-05-24 01:41:24', '2026-05-24 01:41:24'),
(10, 10, 'A multimedia classroom suitable for communication classes, language learning, presentations, and small practical sessions.', NULL, '2026-05-24 01:41:24', '2026-05-24 01:41:24');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`setting_key`, `setting_value`) VALUES
('gmi_logo_url', 'KT/gmi_logo.png');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(100) NOT NULL,
  `matric_id` varchar(100) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` varchar(100) NOT NULL,
  `phone_number` varchar(30) NOT NULL,
  `address` varchar(300) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `matric_id`, `full_name`, `password`, `email`, `role`, `phone_number`, `address`) VALUES
(11, 'admin1', 'Hairi Hakimi', '$2y$10$pb3DohJNGLT03VQ3hVhjUeNkJV3vSF.mbBN3f9OV.UFWR.a0LfXBy', 'hairihakimi006@gmail.com', 'admin', '0109807164', '24, Jalan 4/9Y, Seksyen 4 Tambahan, 43650, Bandar Baru Bangi, Selangor'),
(12, 'SWE24070880', 'Hairi Hakimi', '$2y$10$ur9oqJkoFbo9K6IXgU9S8Oh9tgtEhfETBun9cYX4YDMLM5uKerXjW', 'hairihakimi.hasmadi@student.gmi.edu.my', 'student', '0109807164', '24, Jalan 4/9Y, Seksyen 4 Tambahan, 43650, Bandar Baru Bangi, Selangor'),
(13, 'SWE24071345', 'Haziq Safwan', '$2y$10$fW.vcFn0HLu6PImF6KSl1.4A/htII08gvne9P/5d/aN5fpeKZBvxq', 'haziqsafwan.shahril@student.gmi.edu.my', 'student', '011-5823 7406', 'No. 18, Jalan Melati 4, Taman Melati, 53100 Kuala Lumpur'),
(14, 'SWE24072918', 'Adam Fahmi', '$2y$10$crA2gJjH/NJ6EXUb3S5LpOid5p.NgMb.uDSrRifMljW1rl5kQ4016', 'adamfahmi.shari@student.gmi.edu.my', 'student', '013-904 2671', '22, Jalan Anggerik 3, Seksyen 7, 40000 Shah Alam, Selangor'),
(15, 'SWE24074602', 'Tuan Mohamed Syafiq', '$2y$10$QlXUVXjjH3t4vrkaTiOIDuVSv5JVKE7N6h591MkekdCa.72fuNucm', 'tuanmohamedsyafiq.tuansalman@student.gmi.edu.my', 'student', '017-638 5194', 'Lot 45, Kampung Baru, 20000 Kuala Terengganu, Terengganu');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `booking`
--
ALTER TABLE `booking`
  ADD PRIMARY KEY (`booking_id`);

--
-- Indexes for table `facilities`
--
ALTER TABLE `facilities`
  ADD PRIMARY KEY (`facility_id`);

--
-- Indexes for table `facility_categories`
--
ALTER TABLE `facility_categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- Indexes for table `facility_descriptions`
--
ALTER TABLE `facility_descriptions`
  ADD PRIMARY KEY (`description_id`),
  ADD UNIQUE KEY `facility_id` (`facility_id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `booking`
--
ALTER TABLE `booking`
  MODIFY `booking_id` int(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `facilities`
--
ALTER TABLE `facilities`
  MODIFY `facility_id` int(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `facility_categories`
--
ALTER TABLE `facility_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8619;

--
-- AUTO_INCREMENT for table `facility_descriptions`
--
ALTER TABLE `facility_descriptions`
  MODIFY `description_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=158;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

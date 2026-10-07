-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 07, 2026 at 05:13 PM
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
-- Database: `capstone1v1`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `firstName` varchar(100) NOT NULL,
  `lastName` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `contact` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role_as` varchar(50) NOT NULL DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `banned` tinyint(1) DEFAULT 0,
  `google_uid` varchar(100) DEFAULT NULL,
  `picture` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `firstName`, `lastName`, `email`, `contact`, `password`, `role_as`, `created_at`, `updated_at`, `banned`, `google_uid`, `picture`) VALUES
(1, 'Admin', 'Kirisaki', 'princepls17@gmail.com', '09708000529', '$2y$10$dQ.kfHX2P.QEJ/g/nafwUeoX4/9Qeym4B1EHRuLVOZg1vlq1UqGre', 'admin', '2026-03-19 12:55:11', '2026-10-03 09:40:45', 0, NULL, NULL),
(2, 'vonn', 'villaroman', 'vonnvillaroman5@gmail.com', '09708000529', '$2y$10$x4.PZPd99msXoQ9VX86Sfu7SWwhsGhNsifL/Q8ZV04piOCzu4VYSu', 'admin', '2026-07-27 15:57:01', NULL, 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `adminlogs`
--

CREATE TABLE `adminlogs` (
  `id` int(11) NOT NULL,
  `adminId` int(11) NOT NULL,
  `login_time` datetime DEFAULT NULL,
  `logout_time` datetime DEFAULT NULL,
  `status` enum('online','offline') DEFAULT 'offline'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `adminlogs`
--

INSERT INTO `adminlogs` (`id`, `adminId`, `login_time`, `logout_time`, `status`) VALUES
(76, 1, '2026-06-05 18:57:25', '2026-06-06 00:57:37', 'offline'),
(77, 1, '2026-06-05 18:58:34', '2026-06-06 00:58:59', 'offline'),
(78, 1, '2026-06-05 19:03:13', '2026-06-06 01:04:11', 'offline'),
(79, 1, '2026-06-05 19:04:32', '2026-06-06 01:25:30', 'offline'),
(80, 1, '2026-06-05 19:25:55', '2026-06-06 01:26:28', 'offline'),
(81, 1, '2026-06-05 19:27:18', '2026-06-06 01:27:37', 'offline'),
(82, 1, '2026-06-05 19:27:27', '2026-06-06 01:27:33', 'offline'),
(83, 1, '2026-06-05 19:27:46', '2026-06-06 01:28:41', 'offline'),
(84, 1, '2026-06-05 19:27:56', '2026-06-06 01:28:30', 'offline'),
(85, 1, '2026-06-05 19:29:37', NULL, 'online'),
(86, 1, '2026-06-06 03:31:51', '2026-06-06 09:34:31', 'offline'),
(87, 1, '2026-06-06 03:35:38', '2026-06-06 03:55:14', 'offline'),
(88, 1, '2026-06-15 02:37:02', NULL, 'online'),
(89, 1, '2026-06-15 05:19:32', '2026-06-15 05:58:27', 'offline'),
(90, 1, '2026-06-15 05:58:34', NULL, 'online'),
(91, 1, '2026-06-16 03:05:49', NULL, 'online'),
(92, 1, '2026-06-16 06:37:09', NULL, 'online'),
(93, 1, '2026-06-16 11:10:58', NULL, 'online'),
(94, 1, '2026-06-17 07:14:07', NULL, 'online'),
(95, 1, '2026-06-17 15:46:34', '2026-06-17 16:01:35', 'offline'),
(96, 1, '2026-06-17 16:15:24', NULL, 'online'),
(97, 1, '2026-06-18 10:36:58', NULL, 'online'),
(98, 1, '2026-06-18 14:58:11', NULL, 'online'),
(99, 1, '2026-06-19 06:00:37', NULL, 'online'),
(100, 1, '2026-06-19 10:11:41', '2026-06-19 17:09:05', 'offline'),
(101, 1, '2026-06-19 11:09:27', '2026-06-19 17:09:30', 'offline'),
(102, 1, '2026-06-19 11:10:59', NULL, 'online'),
(103, 1, '2026-06-20 03:45:28', NULL, 'online'),
(104, 1, '2026-06-22 13:47:11', NULL, 'online'),
(105, 1, '2026-06-24 02:52:22', NULL, 'online'),
(106, 1, '2026-06-24 07:16:05', '2026-06-24 07:40:06', 'offline'),
(107, 1, '2026-06-24 07:40:20', '2026-06-24 08:19:51', 'offline'),
(108, 1, '2026-06-24 08:19:58', NULL, 'online'),
(109, 1, '2026-06-25 02:35:05', NULL, 'online'),
(110, 1, '2026-06-25 14:06:40', '2026-06-25 15:09:15', 'offline'),
(111, 1, '2026-06-25 15:09:21', NULL, 'online'),
(112, 1, '2026-06-26 03:26:01', '2026-06-26 04:03:31', 'offline'),
(113, 1, '2026-06-26 04:04:39', '2026-06-26 04:32:28', 'offline'),
(114, 1, '2026-06-26 04:33:30', NULL, 'online'),
(115, 1, '2026-06-26 07:53:56', NULL, 'online'),
(116, 1, '2026-06-26 11:55:54', '2026-06-26 17:57:39', 'offline'),
(117, 1, '2026-06-26 11:57:53', NULL, 'online'),
(118, 1, '2026-06-27 08:20:26', NULL, 'online'),
(119, 1, '2026-06-29 02:07:50', NULL, 'online'),
(120, 1, '2026-06-29 13:39:08', NULL, 'online'),
(121, 1, '2026-06-30 13:02:36', NULL, 'online'),
(122, 1, '2026-07-01 05:08:54', '2026-07-01 05:34:59', 'offline'),
(123, 1, '2026-07-01 05:35:05', NULL, 'online'),
(124, 1, '2026-07-05 15:18:17', NULL, 'online'),
(125, 1, '2026-07-06 15:07:32', '2026-07-06 15:40:02', 'offline'),
(126, 1, '2026-07-08 05:26:47', '2026-07-08 06:59:55', 'offline'),
(127, 1, '2026-07-08 07:00:04', NULL, 'online'),
(128, 1, '2026-07-08 12:34:26', '2026-07-08 13:24:53', 'offline'),
(129, 1, '2026-07-08 13:24:59', '2026-07-08 13:58:17', 'offline'),
(130, 1, '2026-07-08 13:58:21', '2026-07-08 15:04:21', 'offline'),
(131, 1, '2026-07-08 15:04:26', NULL, 'online'),
(132, 1, '2026-07-09 13:46:31', '2026-07-09 19:47:09', 'offline'),
(133, 1, '2026-07-11 05:45:41', NULL, 'online'),
(134, 1, '2026-07-12 15:10:41', NULL, 'online'),
(135, 1, '2026-07-14 02:59:47', NULL, 'online'),
(136, 1, '2026-07-15 04:27:28', '2026-07-15 04:42:29', 'offline'),
(137, 1, '2026-07-15 04:53:51', NULL, 'online'),
(138, 1, '2026-07-21 03:30:51', '2026-07-21 03:54:11', 'offline'),
(139, 1, '2026-07-22 02:14:56', '2026-07-22 03:10:29', 'offline'),
(140, 1, '2026-07-22 03:10:35', '2026-07-22 05:20:30', 'offline'),
(141, 1, '2026-07-22 05:23:11', '2026-07-22 06:42:21', 'offline'),
(142, 1, '2026-07-22 06:43:05', NULL, 'online'),
(143, 1, '2026-07-22 07:58:33', '2026-07-22 08:38:21', 'offline'),
(144, 1, '2026-07-22 08:38:29', NULL, 'online'),
(145, 1, '2026-07-27 04:35:51', '2026-07-27 05:01:42', 'offline'),
(146, 1, '2026-07-27 05:01:48', NULL, 'online'),
(147, 1, '2026-07-27 16:11:56', '2026-07-27 16:48:35', 'offline'),
(148, 1, '2026-07-27 16:48:41', '2026-07-27 17:23:49', 'offline'),
(149, 1, '2026-07-27 17:23:55', '2026-07-27 17:39:12', 'offline'),
(150, 1, '2026-07-27 17:55:44', '2026-07-28 00:00:21', 'offline'),
(151, 2, '2026-07-27 18:00:31', '2026-07-28 00:01:09', 'offline'),
(152, 1, '2026-07-27 18:01:39', NULL, 'online'),
(153, 2, '2026-07-28 04:02:21', '2026-07-28 10:04:35', 'offline'),
(154, 2, '2026-07-28 04:04:43', '2026-07-28 04:20:27', 'offline'),
(155, 1, '2026-07-28 04:20:33', '2026-07-28 10:20:47', 'offline'),
(156, 2, '2026-07-28 04:20:55', '2026-07-28 05:25:56', 'offline'),
(157, 1, '2026-07-28 05:27:26', '2026-07-28 05:51:58', 'offline'),
(158, 1, '2026-07-28 05:52:03', '2026-07-28 06:28:29', 'offline'),
(159, 1, '2026-07-28 06:28:36', '2026-07-28 06:58:36', 'offline'),
(160, 1, '2026-07-31 06:38:13', '2026-07-31 12:42:07', 'offline'),
(161, 2, '2026-07-31 06:42:20', '2026-07-31 07:02:15', 'offline'),
(162, 1, '2026-07-31 07:25:03', NULL, 'online'),
(163, 1, '2026-08-03 00:43:29', '2026-08-03 02:16:26', 'offline'),
(164, 1, '2026-08-03 02:16:37', NULL, 'online'),
(165, 1, '2026-08-04 11:25:37', '2026-08-04 17:25:41', 'offline'),
(166, 1, '2026-08-04 11:26:25', '2026-08-04 12:22:54', 'offline'),
(167, 1, '2026-08-04 12:23:01', NULL, 'online'),
(168, 1, '2026-08-11 23:55:18', '2026-08-12 01:35:45', 'offline'),
(169, 1, '2026-08-12 01:35:50', '2026-08-12 02:03:09', 'offline'),
(170, 1, '2026-08-12 02:03:15', NULL, 'online'),
(171, 1, '2026-08-12 15:36:53', '2026-08-12 16:50:27', 'offline'),
(172, 1, '2026-08-12 16:56:35', NULL, 'online'),
(173, 1, '2026-08-13 07:42:00', '2026-08-13 08:12:00', 'offline'),
(174, 1, '2026-08-13 08:37:06', '2026-08-13 08:52:07', 'offline'),
(175, 1, '2026-08-13 16:31:29', '2026-08-13 22:31:38', 'offline'),
(176, 1, '2026-08-13 16:35:15', NULL, 'online'),
(177, 1, '2026-08-13 19:15:56', NULL, 'online'),
(178, 1, '2026-08-14 12:10:14', NULL, 'online'),
(179, 1, '2026-08-14 14:31:11', '2026-08-14 15:21:20', 'offline'),
(180, 1, '2026-08-15 05:35:12', '2026-08-15 05:50:31', 'offline'),
(181, 1, '2026-08-15 06:06:19', '2026-08-15 09:12:08', 'offline'),
(182, 1, '2026-08-15 09:13:08', '2026-08-15 10:37:14', 'offline'),
(183, 1, '2026-08-15 10:37:21', NULL, 'online'),
(184, 1, '2026-08-16 02:59:48', '2026-08-16 03:47:24', 'offline'),
(185, 1, '2026-08-16 03:47:29', NULL, 'online'),
(186, 1, '2026-08-16 10:18:24', '2026-08-16 15:45:45', 'offline'),
(187, 1, '2026-08-16 15:45:52', '2026-08-16 21:58:40', 'offline'),
(188, 1, '2026-08-16 15:58:51', '2026-08-16 16:14:05', 'offline'),
(189, 1, '2026-08-16 16:20:19', NULL, 'online'),
(190, 1, '2026-08-17 03:00:00', '2026-08-17 03:15:22', 'offline'),
(191, 1, '2026-08-19 11:18:07', '2026-08-19 11:36:39', 'offline'),
(192, 1, '2026-08-19 11:38:26', NULL, 'online'),
(193, 1, '2026-08-19 18:00:28', '2026-08-20 00:32:38', 'offline'),
(194, 1, '2026-08-19 18:32:43', '2026-08-20 00:32:54', 'offline'),
(195, 1, '2026-08-19 18:32:59', NULL, 'online'),
(196, 1, '2026-08-19 18:33:24', NULL, 'online'),
(197, 1, '2026-08-20 02:29:20', NULL, 'online'),
(198, 1, '2026-08-22 17:41:10', NULL, 'online'),
(199, 1, '2026-08-26 08:26:39', '2026-08-26 14:46:17', 'offline'),
(200, 1, '2026-08-26 08:46:26', '2026-08-26 14:46:28', 'offline'),
(201, 1, '2026-08-26 08:46:33', '2026-08-26 14:47:58', 'offline'),
(202, 1, '2026-08-26 08:48:04', '2026-08-26 09:11:17', 'offline'),
(203, 1, '2026-08-26 09:11:23', NULL, 'online'),
(204, 1, '2026-08-27 09:31:54', NULL, 'online'),
(205, 1, '2026-08-27 13:31:13', '2026-08-27 14:32:49', 'offline'),
(206, 1, '2026-08-27 14:32:57', NULL, 'online'),
(207, 1, '2026-08-28 10:20:37', NULL, 'online'),
(208, 1, '2026-08-28 15:47:39', '2026-08-28 16:28:53', 'offline'),
(209, 1, '2026-08-28 16:28:59', '2026-08-28 17:10:36', 'offline'),
(210, 1, '2026-08-28 17:10:41', '2026-08-28 18:05:23', 'offline'),
(211, 1, '2026-08-28 18:05:28', '2026-08-28 18:30:50', 'offline'),
(212, 1, '2026-08-28 18:36:10', '2026-08-28 18:51:21', 'offline'),
(213, 1, '2026-08-28 18:53:16', NULL, 'online'),
(214, 1, '2026-08-29 09:22:15', '2026-08-29 10:08:34', 'offline'),
(215, 1, '2026-08-29 10:44:12', NULL, 'online'),
(216, 1, '2026-08-29 13:04:38', '2026-08-29 13:36:32', 'offline'),
(217, 1, '2026-08-29 13:36:38', '2026-08-29 14:13:24', 'offline'),
(218, 1, '2026-08-29 14:13:50', NULL, 'online'),
(220, 1, '2026-08-29 16:14:09', '2026-08-29 16:32:28', 'offline'),
(221, 1, '2026-08-29 16:35:40', '2026-08-29 17:00:48', 'offline'),
(222, 1, '2026-08-29 17:00:53', NULL, 'online'),
(223, 1, '2026-08-30 04:27:47', NULL, 'online'),
(224, 1, '2026-08-31 17:47:40', '2026-08-31 18:03:38', 'offline'),
(225, 1, '2026-09-02 05:07:54', NULL, 'online'),
(226, 1, '2026-09-03 07:47:34', '2026-09-03 08:40:53', 'offline'),
(227, 1, '2026-09-03 08:41:52', NULL, 'online'),
(228, 1, '2026-09-03 14:08:26', '2026-09-03 15:20:32', 'offline'),
(229, 1, '2026-09-03 15:42:13', '2026-09-03 16:01:09', 'offline'),
(230, 1, '2026-09-03 16:01:56', NULL, 'online'),
(231, 1, '2026-09-04 04:40:16', '2026-09-04 06:13:24', 'offline'),
(232, 1, '2026-09-04 06:13:46', '2026-09-04 07:25:00', 'offline'),
(233, 1, '2026-09-04 07:25:31', '2026-09-04 09:01:33', 'offline'),
(234, 1, '2026-09-04 09:01:39', '2026-09-04 10:39:31', 'offline'),
(235, 1, '2026-09-04 10:39:43', '2026-09-04 11:51:13', 'offline'),
(236, 1, '2026-09-04 11:51:20', NULL, 'online'),
(237, 1, '2026-09-04 16:43:55', NULL, 'online'),
(238, 1, '2026-09-05 04:58:50', '2026-09-05 05:26:41', 'offline'),
(239, 1, '2026-09-05 05:26:56', '2026-09-05 05:53:57', 'offline'),
(240, 1, '2026-09-05 05:55:45', NULL, 'online'),
(241, 1, '2026-09-05 13:50:17', '2026-09-05 16:11:14', 'offline'),
(242, 1, '2026-09-05 16:19:29', '2026-09-05 22:31:09', 'offline'),
(243, 1, '2026-09-05 16:31:40', '2026-09-05 17:02:47', 'offline'),
(244, 1, '2026-09-06 04:16:19', NULL, 'online'),
(245, 1, '2026-09-06 06:21:47', '2026-09-06 06:55:10', 'offline'),
(246, 1, '2026-09-06 12:08:10', '2026-09-06 13:11:56', 'offline'),
(247, 1, '2026-09-06 13:21:33', NULL, 'online'),
(248, 1, '2026-09-06 14:53:34', NULL, 'online'),
(249, 1, '2026-09-06 18:06:12', '2026-09-06 19:03:23', 'offline'),
(250, 1, '2026-09-06 19:03:29', NULL, 'online'),
(251, 1, '2026-09-07 05:50:45', '2026-09-07 07:23:13', 'offline'),
(252, 1, '2026-09-07 07:53:16', '2026-09-07 08:37:04', 'offline'),
(253, 1, '2026-09-07 08:37:09', NULL, 'online'),
(254, 1, '2026-09-07 12:45:57', '2026-09-07 13:05:41', 'offline'),
(255, 1, '2026-09-07 13:05:46', '2026-09-07 13:23:28', 'offline'),
(256, 1, '2026-09-07 13:23:35', NULL, 'online'),
(257, 1, '2026-09-07 15:47:07', '2026-09-07 16:18:02', 'offline'),
(258, 1, '2026-09-07 17:19:29', '2026-09-07 17:48:55', 'offline'),
(259, 1, '2026-09-07 17:49:01', '2026-09-08 01:31:33', 'offline'),
(260, 1, '2026-09-08 04:57:32', '2026-09-08 05:47:59', 'offline'),
(261, 1, '2026-09-08 05:48:09', NULL, 'online'),
(262, 1, '2026-09-08 13:57:57', '2026-09-08 14:38:01', 'offline'),
(263, 1, '2026-09-08 14:41:29', '2026-09-08 15:54:09', 'offline'),
(264, 1, '2026-09-08 16:38:49', '2026-09-08 19:24:43', 'offline'),
(265, 1, '2026-09-09 00:39:27', NULL, 'online'),
(266, 1, '2026-09-09 01:09:16', '2026-09-09 09:37:10', 'offline'),
(267, 1, '2026-09-09 03:37:15', NULL, 'online'),
(268, 1, '2026-09-09 03:57:10', '2026-09-09 10:15:30', 'offline'),
(269, 1, '2026-09-09 04:15:35', NULL, 'online'),
(270, 1, '2026-09-09 11:51:00', '2026-09-09 13:25:34', 'offline'),
(271, 1, '2026-09-09 13:27:09', '2026-09-09 15:09:57', 'offline'),
(272, 1, '2026-09-09 15:16:35', '2026-09-09 15:59:58', 'offline'),
(273, 1, '2026-09-09 16:00:04', NULL, 'online'),
(274, 1, '2026-09-10 04:56:06', '2026-09-10 11:31:43', 'offline'),
(275, 1, '2026-09-10 05:31:54', '2026-09-10 05:56:44', 'offline'),
(276, 1, '2026-09-10 05:56:50', '2026-09-10 06:47:14', 'offline'),
(277, 1, '2026-09-10 06:47:19', '2026-09-10 07:57:19', 'offline'),
(278, 1, '2026-09-10 07:57:24', NULL, 'online'),
(279, 1, '2026-09-10 12:56:52', '2026-09-10 13:17:36', 'offline'),
(280, 1, '2026-09-10 13:17:42', '2026-09-10 14:50:01', 'offline'),
(281, 1, '2026-09-10 14:50:37', '2026-09-10 16:00:22', 'offline'),
(282, 1, '2026-09-10 16:04:01', NULL, 'online'),
(283, 1, '2026-09-11 03:27:01', '2026-09-11 09:46:48', 'offline'),
(284, 1, '2026-09-11 09:47:07', NULL, 'online'),
(285, 1, '2026-09-11 13:15:54', '2026-09-11 13:45:14', 'offline'),
(286, 1, '2026-09-11 13:48:42', '2026-09-11 14:29:24', 'offline'),
(287, 1, '2026-09-11 14:29:39', '2026-09-11 15:08:43', 'offline'),
(288, 1, '2026-09-11 15:08:49', '2026-09-11 17:19:33', 'offline'),
(289, 1, '2026-09-11 17:52:05', NULL, 'online'),
(290, 1, '2026-09-12 05:00:32', '2026-09-12 05:55:09', 'offline'),
(291, 1, '2026-09-12 05:56:44', '2026-09-12 07:18:50', 'offline'),
(292, 1, '2026-09-12 07:19:26', '2026-09-12 10:43:58', 'offline'),
(293, 1, '2026-09-12 10:58:17', '2026-09-12 13:17:42', 'offline'),
(294, 1, '2026-09-12 13:19:10', '2026-09-12 14:46:11', 'offline'),
(295, 1, '2026-09-12 14:46:17', '2026-09-12 15:24:08', 'offline'),
(296, 1, '2026-09-12 15:24:14', NULL, 'online'),
(297, 1, '2026-09-12 17:13:22', NULL, 'online'),
(298, 1, '2026-09-13 03:25:05', '2026-09-13 04:24:11', 'offline'),
(299, 1, '2026-09-13 04:30:30', '2026-09-13 05:06:42', 'offline'),
(300, 1, '2026-09-13 05:12:37', '2026-09-13 08:12:16', 'offline'),
(301, 1, '2026-09-13 08:46:02', '2026-09-13 15:05:17', 'offline'),
(302, 1, '2026-09-13 09:07:19', '2026-09-13 15:07:21', 'offline'),
(303, 1, '2026-09-13 09:07:33', '2026-09-13 15:07:35', 'offline'),
(304, 1, '2026-09-13 09:29:31', '2026-09-13 15:29:49', 'offline'),
(305, 1, '2026-09-13 09:29:54', '2026-09-13 09:45:26', 'offline'),
(306, 1, '2026-09-13 10:20:32', '2026-09-13 16:22:24', 'offline'),
(307, 1, '2026-09-13 10:32:13', '2026-09-13 16:32:16', 'offline'),
(308, 1, '2026-09-13 13:04:39', NULL, 'online'),
(309, 1, '2026-09-13 16:21:04', '2026-09-13 22:40:57', 'offline'),
(310, 1, '2026-09-14 02:30:46', NULL, 'online'),
(311, 1, '2026-09-14 06:31:45', '2026-09-14 12:31:47', 'offline'),
(312, 1, '2026-09-14 10:12:24', '2026-09-14 16:44:22', 'offline'),
(313, 1, '2026-09-14 10:44:27', '2026-09-14 12:56:01', 'offline'),
(314, 1, '2026-09-14 13:05:29', '2026-09-14 13:36:57', 'offline'),
(315, 1, '2026-09-14 13:52:08', '2026-09-14 14:22:46', 'offline'),
(316, 1, '2026-09-14 14:29:11', '2026-09-14 15:23:09', 'offline'),
(317, 1, '2026-09-14 15:23:24', NULL, 'online'),
(318, 1, '2026-09-15 03:09:19', '2026-09-15 05:05:53', 'offline'),
(319, 1, '2026-09-15 05:06:15', NULL, 'online'),
(320, 1, '2026-09-15 09:40:06', '2026-09-15 15:46:54', 'offline'),
(321, 1, '2026-09-15 09:54:32', '2026-09-15 15:54:59', 'offline'),
(322, 1, '2026-09-15 10:01:53', '2026-09-15 16:25:01', 'offline'),
(323, 1, '2026-09-15 10:33:54', NULL, 'online'),
(324, 1, '2026-09-15 13:51:23', '2026-09-15 14:15:14', 'offline'),
(325, 1, '2026-09-15 14:31:14', '2026-09-15 14:54:47', 'offline'),
(326, 1, '2026-09-15 15:01:58', '2026-09-15 15:32:41', 'offline'),
(327, 1, '2026-09-15 15:59:46', '2026-09-15 16:44:39', 'offline'),
(328, 1, '2026-09-15 16:49:42', '2026-09-15 19:23:28', 'offline'),
(329, 1, '2026-09-15 19:23:59', NULL, 'online'),
(330, 1, '2026-09-16 03:23:06', '2026-09-16 09:23:41', 'offline'),
(331, 1, '2026-09-16 03:23:47', '2026-09-16 04:01:13', 'offline'),
(332, 1, '2026-09-16 04:10:03', NULL, 'online'),
(333, 1, '2026-09-16 06:29:22', NULL, 'online'),
(334, 1, '2026-09-16 07:06:40', '2026-09-16 07:37:21', 'offline'),
(335, 1, '2026-09-16 07:39:27', '2026-09-16 08:02:28', 'offline'),
(336, 1, '2026-09-16 08:02:37', NULL, 'online'),
(337, 1, '2026-09-16 14:46:30', '2026-09-16 16:38:16', 'offline'),
(338, 1, '2026-09-16 16:39:08', '2026-09-16 17:03:40', 'offline'),
(339, 1, '2026-09-16 17:03:46', '2026-09-16 23:20:23', 'offline'),
(340, 1, '2026-09-16 23:26:45', NULL, 'online'),
(341, 1, '2026-09-17 01:05:20', NULL, 'online'),
(342, 1, '2026-09-17 12:12:27', '2026-09-17 12:34:41', 'offline'),
(343, 1, '2026-09-17 12:34:59', '2026-09-17 14:08:29', 'offline'),
(344, 1, '2026-09-17 14:09:17', '2026-09-17 14:32:05', 'offline'),
(345, 1, '2026-09-17 14:47:02', '2026-09-17 15:02:13', 'offline'),
(346, 1, '2026-09-17 15:06:57', '2026-09-17 15:23:02', 'offline'),
(347, 1, '2026-09-17 15:23:07', NULL, 'online'),
(348, 1, '2026-09-17 17:53:22', NULL, 'online'),
(349, 1, '2026-09-17 22:28:37', '2026-09-18 00:03:54', 'offline'),
(350, 1, '2026-09-18 00:17:00', '2026-09-18 01:57:05', 'offline'),
(351, 1, '2026-09-18 01:58:17', '2026-09-18 02:13:53', 'offline'),
(352, 1, '2026-09-18 20:30:01', '2026-09-18 20:59:08', 'offline'),
(353, 1, '2026-09-19 12:27:23', '2026-09-19 13:16:07', 'offline'),
(354, 1, '2026-09-19 13:16:14', '2026-09-19 13:31:18', 'offline'),
(355, 1, '2026-09-19 13:37:20', '2026-09-19 14:49:00', 'offline'),
(356, 1, '2026-09-19 14:49:47', NULL, 'online'),
(357, 1, '2026-09-19 15:50:35', '2026-09-19 16:38:06', 'offline'),
(358, 1, '2026-09-19 16:38:13', '2026-09-19 16:38:40', 'offline'),
(359, 1, '2026-09-19 16:38:56', '2026-09-19 17:10:19', 'offline'),
(360, 1, '2026-09-19 17:12:09', '2026-09-19 17:25:49', 'offline'),
(361, 1, '2026-09-19 17:26:23', NULL, 'online'),
(362, 1, '2026-09-19 21:07:23', '2026-09-19 21:29:02', 'offline'),
(363, 1, '2026-09-19 21:32:30', '2026-09-19 23:00:38', 'offline'),
(364, 1, '2026-09-19 23:08:23', '2026-09-19 23:42:54', 'offline'),
(365, 1, '2026-09-19 23:49:27', '2026-09-20 00:23:54', 'offline'),
(366, 1, '2026-09-20 00:38:39', NULL, 'online'),
(367, 1, '2026-09-20 20:24:22', '2026-09-20 20:36:58', 'offline'),
(368, 1, '2026-09-20 20:59:58', '2026-09-20 21:22:23', 'offline'),
(369, 1, '2026-09-20 21:23:02', '2026-09-20 21:43:38', 'offline'),
(370, 1, '2026-09-20 22:02:53', NULL, 'online'),
(371, 1, '2026-09-21 14:22:13', NULL, 'online'),
(372, 1, '2026-09-21 18:00:43', '2026-09-21 18:00:46', 'offline'),
(373, 1, '2026-09-21 18:14:17', '2026-09-21 18:54:38', 'offline'),
(374, 1, '2026-09-21 19:05:27', '2026-09-21 19:34:22', 'offline'),
(375, 1, '2026-09-21 19:35:04', '2026-09-21 20:02:03', 'offline'),
(376, 1, '2026-09-21 20:39:43', '2026-09-21 21:11:48', 'offline'),
(377, 1, '2026-09-21 21:56:43', '2026-09-21 22:31:57', 'offline'),
(378, 1, '2026-09-21 23:30:37', '2026-09-21 23:45:58', 'offline'),
(379, 1, '2026-09-22 00:24:14', '2026-09-22 00:49:24', 'offline'),
(380, 1, '2026-09-22 13:19:05', '2026-09-22 13:47:05', 'offline'),
(381, 1, '2026-09-22 13:47:17', '2026-09-22 14:04:23', 'offline'),
(382, 1, '2026-09-22 14:05:53', '2026-09-22 14:19:04', 'offline'),
(383, 1, '2026-09-22 14:19:10', NULL, 'online'),
(384, 1, '2026-09-22 17:06:06', '2026-09-22 17:35:23', 'offline'),
(385, 1, '2026-09-22 17:35:34', '2026-09-22 19:11:49', 'offline'),
(386, 1, '2026-09-22 19:12:12', '2026-09-22 19:58:09', 'offline'),
(387, 1, '2026-09-22 21:18:16', '2026-09-22 21:52:19', 'offline'),
(388, 1, '2026-09-22 21:52:25', '2026-09-22 22:08:22', 'offline'),
(389, 1, '2026-09-22 22:19:52', '2026-09-22 22:37:34', 'offline'),
(390, 1, '2026-09-22 22:38:45', '2026-09-22 22:53:53', 'offline'),
(391, 1, '2026-09-23 09:26:28', '2026-09-23 09:47:24', 'offline'),
(392, 1, '2026-09-23 09:47:43', '2026-09-23 10:16:02', 'offline'),
(393, 1, '2026-09-23 10:23:24', '2026-09-23 10:39:00', 'offline'),
(394, 1, '2026-09-23 10:55:31', '2026-09-23 11:10:39', 'offline'),
(395, 1, '2026-09-23 11:30:38', '2026-09-23 11:46:03', 'offline'),
(396, 1, '2026-09-23 11:58:07', '2026-09-23 12:26:01', 'offline'),
(397, 1, '2026-09-24 09:41:06', '2026-09-24 10:23:56', 'offline'),
(398, 1, '2026-09-24 10:25:03', NULL, 'online'),
(399, 1, '2026-09-24 12:36:03', NULL, 'online'),
(400, 1, '2026-09-24 15:42:43', NULL, 'online'),
(401, 1, '2026-09-25 10:03:02', NULL, 'online'),
(402, 1, '2026-09-25 18:11:00', '2026-09-25 18:48:49', 'offline'),
(403, 1, '2026-09-25 18:57:15', NULL, 'online'),
(404, 1, '2026-09-25 21:48:33', '2026-09-25 22:26:49', 'offline'),
(405, 1, '2026-09-25 22:26:55', '2026-09-25 22:45:58', 'offline'),
(406, 1, '2026-09-25 22:46:04', NULL, 'online'),
(407, 1, '2026-09-26 09:18:17', '2026-09-26 11:08:29', 'offline'),
(408, 1, '2026-09-26 11:14:52', '2026-09-26 14:04:00', 'offline'),
(409, 1, '2026-09-26 14:04:53', '2026-09-26 14:26:55', 'offline'),
(410, 1, '2026-09-26 15:38:10', '2026-09-26 16:28:12', 'offline'),
(411, 1, '2026-09-26 16:28:20', '2026-09-26 19:54:16', 'offline'),
(412, 1, '2026-09-26 21:05:25', '2026-09-26 22:08:54', 'offline'),
(413, 1, '2026-09-26 22:13:23', '2026-09-26 22:28:29', 'offline'),
(414, 1, '2026-09-26 22:32:11', NULL, 'online'),
(415, 1, '2026-09-27 15:20:29', '2026-09-27 15:52:43', 'offline'),
(416, 1, '2026-09-27 15:54:04', NULL, 'online'),
(417, 1, '2026-09-27 19:43:11', '2026-09-27 20:40:47', 'offline'),
(418, 1, '2026-09-27 20:40:53', '2026-09-27 21:31:17', 'offline'),
(419, 1, '2026-09-27 21:31:25', '2026-09-27 22:55:51', 'offline'),
(420, 1, '2026-09-27 23:10:26', '2026-09-27 23:25:27', 'offline'),
(421, 1, '2026-09-28 10:34:56', '2026-09-28 11:10:49', 'offline'),
(422, 1, '2026-09-28 11:11:24', NULL, 'online'),
(423, 1, '2026-09-28 12:27:23', '2026-09-28 13:30:17', 'offline'),
(424, 1, '2026-09-28 13:53:49', NULL, 'online'),
(425, 1, '2026-09-28 16:10:08', NULL, 'online'),
(426, 1, '2026-09-28 20:32:05', NULL, 'online'),
(427, 1, '2026-09-29 09:08:14', '2026-09-29 09:45:33', 'offline'),
(428, 1, '2026-09-29 09:50:32', NULL, 'online'),
(429, 1, '2026-09-30 09:54:11', NULL, 'online'),
(430, 1, '2026-09-30 21:12:10', '2026-09-30 21:16:30', 'offline'),
(431, 1, '2026-09-30 23:33:44', '2026-09-30 23:39:02', 'offline'),
(432, 1, '2026-10-01 00:15:54', '2026-10-01 00:16:00', 'offline'),
(434, 1, '2026-10-01 11:57:05', '2026-10-01 12:15:43', 'offline'),
(435, 1, '2026-10-01 20:44:33', '2026-10-01 20:51:45', 'offline'),
(436, 1, '2026-10-01 21:25:53', '2026-10-01 21:56:31', 'offline'),
(437, 1, '2026-10-02 13:09:52', '2026-10-02 13:16:47', 'offline'),
(438, 1, '2026-10-02 13:30:44', '2026-10-02 13:58:29', 'offline'),
(439, 1, '2026-10-02 14:00:20', '2026-10-02 15:17:06', 'offline'),
(440, 1, '2026-10-02 15:36:32', '2026-10-02 15:55:33', 'offline'),
(441, 1, '2026-10-02 20:11:03', '2026-10-02 22:11:29', 'offline'),
(443, 1, '2026-10-03 11:29:16', '2026-10-03 11:59:28', 'offline'),
(444, 1, '2026-10-03 17:38:57', '2026-10-03 17:40:19', 'offline'),
(445, 1, '2026-10-03 17:40:32', '2026-10-03 17:43:08', 'offline'),
(446, 1, '2026-10-03 17:43:34', '2026-10-03 18:06:06', 'offline'),
(447, 1, '2026-10-04 11:31:21', '2026-10-04 12:46:51', 'offline'),
(448, 1, '2026-10-04 12:48:45', '2026-10-04 13:37:04', 'offline'),
(449, 1, '2026-10-04 13:51:11', '2026-10-04 17:18:13', 'offline'),
(450, 1, '2026-10-04 17:18:22', NULL, 'online'),
(451, 1, '2026-10-04 23:15:36', NULL, 'online'),
(452, 1, '2026-10-05 09:55:08', NULL, 'online'),
(453, 1, '2026-10-05 17:18:05', '2026-10-05 18:12:43', 'offline'),
(454, 1, '2026-10-05 21:42:23', NULL, 'online'),
(455, 1, '2026-10-06 11:10:54', '2026-10-06 11:20:52', 'offline'),
(456, 1, '2026-10-06 11:27:41', '2026-10-06 12:28:57', 'offline'),
(457, 1, '2026-10-06 13:10:43', '2026-10-06 13:30:47', 'offline'),
(458, 1, '2026-10-06 13:45:59', NULL, 'online'),
(459, 1, '2026-10-06 16:09:53', '2026-10-06 16:42:48', 'offline'),
(460, 1, '2026-10-06 17:00:23', '2026-10-06 17:23:20', 'offline'),
(461, 1, '2026-10-06 19:40:09', '2026-10-06 20:31:23', 'offline'),
(462, 1, '2026-10-06 21:03:09', '2026-10-06 21:29:34', 'offline'),
(463, 1, '2026-10-07 10:18:20', '2026-10-07 11:33:25', 'offline'),
(464, 1, '2026-10-07 12:01:48', '2026-10-07 12:39:58', 'offline'),
(465, 1, '2026-10-07 21:16:58', NULL, 'online');

-- --------------------------------------------------------

--
-- Table structure for table `lost_found`
--

CREATE TABLE `lost_found` (
  `id` int(11) NOT NULL,
  `category` enum('Cash','Gadget','Document','Other') NOT NULL,
  `status` enum('Lost','Found') NOT NULL,
  `reported_location` varchar(255) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `image` varchar(255) DEFAULT 'default.jpg',
  `reporter_role` enum('Student','Faculty','Visitor / Guest','Anonymous') DEFAULT NULL,
  `reporter_name` varchar(150) DEFAULT NULL,
  `reporter_id` varchar(50) DEFAULT NULL,
  `reporter_year` varchar(50) DEFAULT NULL,
  `reporter_department` enum('BSA','CRIM','EDUC','HMTM','IT','RAD TECH / MED TECH') DEFAULT NULL,
  `cash_amount` decimal(10,2) DEFAULT NULL,
  `gadget_type` enum('Cell Phone','Laptop','Tablet','Smart watches','Audio Gadgets') DEFAULT NULL,
  `gadget_brand` varchar(100) DEFAULT NULL,
  `gadget_color` varchar(100) DEFAULT NULL,
  `gadget_description` text DEFAULT NULL,
  `document_type` varchar(100) DEFAULT NULL,
  `document_name` varchar(100) DEFAULT NULL,
  `other_title` varchar(150) DEFAULT NULL,
  `other_description` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `edited_by` int(11) DEFAULT NULL,
  `edited_at` datetime DEFAULT NULL,
  `released_by` int(11) DEFAULT NULL,
  `resolved_by` int(11) DEFAULT NULL,
  `is_claimed` tinyint(1) DEFAULT 0,
  `claimed_by` varchar(255) DEFAULT NULL,
  `claimed_id` varchar(255) DEFAULT NULL,
  `claimed_email` varchar(255) DEFAULT NULL,
  `claimed_contact` varchar(255) DEFAULT NULL,
  `claimed_department` enum('BSA','CRIM','EDUC','HMTM','IT','RAD TECH / MED TECH','FACULTY','VISITOR / GUEST') DEFAULT NULL,
  `claimed_address` varchar(255) DEFAULT NULL,
  `claimed_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_resolved` tinyint(1) DEFAULT 0,
  `resolved_at` datetime DEFAULT NULL,
  `renewed_at` datetime DEFAULT NULL,
  `renewed_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lost_found`
--

INSERT INTO `lost_found` (`id`, `category`, `status`, `reported_location`, `email`, `image`, `reporter_role`, `reporter_name`, `reporter_id`, `reporter_year`, `reporter_department`, `cash_amount`, `gadget_type`, `gadget_brand`, `gadget_color`, `gadget_description`, `document_type`, `document_name`, `other_title`, `other_description`, `created_by`, `edited_by`, `edited_at`, `released_by`, `resolved_by`, `is_claimed`, `claimed_by`, `claimed_id`, `claimed_email`, `claimed_contact`, `claimed_department`, `claimed_address`, `claimed_date`, `created_at`, `is_resolved`, `resolved_at`, `renewed_at`, `renewed_by`) VALUES
(415, 'Cash', 'Found', 'Parking Area', '', 'default1.png', 'Student', 'Fyang Smith', 'ijoii', '1st Year', 'BSA', 30.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 1, NULL, 1, 'prince leomar', '01-1516-01018', 'princepls17@gmail.com', '09708000529', 'IT', 'Brgy. Sumaacab este', '2026-10-05 21:48:08', '2026-10-04 15:16:11', 0, NULL, NULL, NULL),
(418, 'Cash', 'Found', 'Computer Laboratory', '', 'default1.png', 'Student', 'Fyang Smith', 'ijoii', '1st Year', 'IT', 30.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-10-05 14:53:39', 0, NULL, NULL, NULL),
(419, 'Other', 'Found', 'Computer Laboratory', '', 'default1.png', 'Student', 'Fyang Smith', 'ijoii', '1st Year', 'IT', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'umbrella', 'malunnng', 1, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-10-05 14:55:33', 0, NULL, NULL, NULL),
(420, 'Document', 'Found', 'Computer Laboratory', '', 'default1.png', 'Visitor / Guest', 'Fyang Smith', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ID', 'Prince leomar C. Sobrepena', NULL, NULL, 1, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-10-05 14:59:00', 0, NULL, NULL, NULL),
(421, 'Cash', 'Lost', 'room 303', 'princepls17@gmail.com', 'default1.png', 'Student', 'Fyang Smith', 'ijoii', '1st Year', 'BSA', 30.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-10-06 05:12:44', 0, NULL, NULL, NULL),
(422, 'Cash', 'Found', 'Registrar\'s Office', '', 'default1.png', 'Student', 'goku', 'bruhh', '1st Year', 'IT', 8080.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, '2026-10-07 10:57:02', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-10-06 05:15:36', 0, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `lost_found_deletions`
--

CREATE TABLE `lost_found_deletions` (
  `id` int(11) NOT NULL,
  `lost_found_id` int(11) NOT NULL,
  `category` enum('Cash','Gadget','Document','Other') NOT NULL,
  `status` enum('Lost','Found') NOT NULL,
  `was_resolved` tinyint(1) NOT NULL DEFAULT 0,
  `was_claimed` tinyint(1) NOT NULL DEFAULT 0,
  `was_disposed` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_by` int(11) NOT NULL,
  `deleted_role` enum('admin','staff') NOT NULL,
  `deleted_at` datetime DEFAULT current_timestamp(),
  `image` varchar(255) DEFAULT NULL,
  `reported_location` varchar(255) DEFAULT NULL,
  `reporter_role` enum('Student','Faculty','Visitor / Guest','Anonymous') DEFAULT NULL,
  `reporter_name` varchar(150) DEFAULT NULL,
  `reporter_id` varchar(50) DEFAULT NULL,
  `reporter_year` varchar(50) DEFAULT NULL,
  `reporter_department` enum('BSA','CRIM','EDUC','HMTM','IT','RAD TECH / MED TECH') DEFAULT NULL,
  `cash_amount` decimal(10,2) DEFAULT NULL,
  `gadget_type` enum('Cell Phone','Laptop','Tablet','Smart watches','Audio Gadgets') DEFAULT NULL,
  `gadget_brand` varchar(100) DEFAULT NULL,
  `gadget_color` varchar(100) DEFAULT NULL,
  `gadget_description` text DEFAULT NULL,
  `document_type` varchar(100) DEFAULT NULL,
  `document_name` varchar(100) DEFAULT NULL,
  `other_title` varchar(150) DEFAULT NULL,
  `other_description` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lost_found_edits`
--

CREATE TABLE `lost_found_edits` (
  `id` int(11) NOT NULL,
  `lost_found_id` int(11) NOT NULL,
  `old_data` longtext NOT NULL,
  `new_data` longtext NOT NULL,
  `edited_by` int(11) NOT NULL,
  `edited_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lost_found_edits`
--

INSERT INTO `lost_found_edits` (`id`, `lost_found_id`, `old_data`, `new_data`, `edited_by`, `edited_at`) VALUES
(1, 422, '{\"status\": \"Found\", \"email\": \"\", \"image\": \"default1.png\", \"reporter_role\": \"Student\", \"reporter_name\": \"Fyang Smith\", \"reporter_id\": \"ijoii\", \"reporter_year\": \"1st Year\", \"reporter_department\": \"IT\", \"category\": \"Cash\", \"reported_location\": \"Registrar\'s Office\", \"cash_amount\": \"9.00\", \"gadget_type\": \"\", \"gadget_brand\": \"\", \"gadget_color\": \"\", \"gadget_description\": \"\", \"document_type\": \"\", \"document_name\": \"\", \"other_title\": \"\", \"other_description\": \"\"}', '{\"status\": \"Found\", \"email\": \"\", \"image\": \"default1.png\", \"reporter_role\": \"Student\", \"reporter_name\": \"Fyang Smith\", \"reporter_id\": \"ijoii\", \"reporter_year\": \"1st Year\", \"reporter_department\": \"IT\", \"category\": \"Cash\", \"reported_location\": \"Registrar\'s Office\", \"cash_amount\": \"999.00\", \"gadget_type\": \"\", \"gadget_brand\": \"\", \"gadget_color\": \"\", \"gadget_description\": \"\", \"document_type\": \"\", \"document_name\": \"\", \"other_title\": \"\", \"other_description\": \"\"}', 1, '2026-10-07 10:48:58'),
(2, 422, '{\"status\": \"Found\", \"email\": \"\", \"image\": \"default1.png\", \"reporter_role\": \"Student\", \"reporter_name\": \"Fyang Smith\", \"reporter_id\": \"ijoii\", \"reporter_year\": \"1st Year\", \"reporter_department\": \"IT\", \"category\": \"Cash\", \"reported_location\": \"Registrar\'s Office\", \"cash_amount\": \"999.00\", \"gadget_type\": \"\", \"gadget_brand\": \"\", \"gadget_color\": \"\", \"gadget_description\": \"\", \"document_type\": \"\", \"document_name\": \"\", \"other_title\": \"\", \"other_description\": \"\"}', '{\"status\": \"Found\", \"email\": \"\", \"image\": \"default1.png\", \"reporter_role\": \"Student\", \"reporter_name\": \"Fyang Smith\", \"reporter_id\": \"ijoii\", \"reporter_year\": \"1st Year\", \"reporter_department\": \"IT\", \"category\": \"Cash\", \"reported_location\": \"Registrar\'s Office\", \"cash_amount\": \"9090.00\", \"gadget_type\": \"\", \"gadget_brand\": \"\", \"gadget_color\": \"\", \"gadget_description\": \"\", \"document_type\": \"\", \"document_name\": \"\", \"other_title\": \"\", \"other_description\": \"\"}', 1, '2026-10-07 10:51:23'),
(3, 422, '{\"status\": \"Found\", \"email\": \"\", \"image\": \"default1.png\", \"reporter_role\": \"Student\", \"reporter_name\": \"Fyang Smith\", \"reporter_id\": \"ijoii\", \"reporter_year\": \"1st Year\", \"reporter_department\": \"IT\", \"category\": \"Cash\", \"reported_location\": \"Registrar\'s Office\", \"cash_amount\": \"9090.00\", \"gadget_type\": \"\", \"gadget_brand\": \"\", \"gadget_color\": \"\", \"gadget_description\": \"\", \"document_type\": \"\", \"document_name\": \"\", \"other_title\": \"\", \"other_description\": \"\"}', '{\"status\": \"Found\", \"email\": \"\", \"image\": \"default1.png\", \"reporter_role\": \"Student\", \"reporter_name\": \"Batman\", \"reporter_id\": \"ijoii\", \"reporter_year\": \"1st Year\", \"reporter_department\": \"IT\", \"category\": \"Cash\", \"reported_location\": \"Registrar\'s Office\", \"cash_amount\": \"9090.00\", \"gadget_type\": \"\", \"gadget_brand\": \"\", \"gadget_color\": \"\", \"gadget_description\": \"\", \"document_type\": \"\", \"document_name\": \"\", \"other_title\": \"\", \"other_description\": \"\"}', 1, '2026-10-07 10:53:49'),
(4, 422, '{\"status\": \"Found\", \"email\": \"\", \"image\": \"default1.png\", \"reporter_role\": \"Student\", \"reporter_name\": \"Batman\", \"reporter_id\": \"ijoii\", \"reporter_year\": \"1st Year\", \"reporter_department\": \"IT\", \"category\": \"Cash\", \"reported_location\": \"Registrar\'s Office\", \"cash_amount\": \"9090.00\", \"gadget_type\": \"\", \"gadget_brand\": \"\", \"gadget_color\": \"\", \"gadget_description\": \"\", \"document_type\": \"\", \"document_name\": \"\", \"other_title\": \"\", \"other_description\": \"\"}', '{\"status\": \"Found\", \"email\": \"\", \"image\": \"default1.png\", \"reporter_role\": \"Student\", \"reporter_name\": \"goku\", \"reporter_id\": \"bruhh\", \"reporter_year\": \"1st Year\", \"reporter_department\": \"IT\", \"category\": \"Cash\", \"reported_location\": \"Registrar\'s Office\", \"cash_amount\": \"8080.00\", \"gadget_type\": \"\", \"gadget_brand\": \"\", \"gadget_color\": \"\", \"gadget_description\": \"\", \"document_type\": \"\", \"document_name\": \"\", \"other_title\": \"\", \"other_description\": \"\"}', 1, '2026-10-07 10:57:02');

-- --------------------------------------------------------

--
-- Table structure for table `lost_found_matches`
--

CREATE TABLE `lost_found_matches` (
  `id` int(11) NOT NULL,
  `lost_id` int(11) NOT NULL,
  `found_id` int(11) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `is_resolved` tinyint(1) DEFAULT 0,
  `resolved_at` datetime DEFAULT NULL,
  `matched_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lost_found_matches`
--

INSERT INTO `lost_found_matches` (`id`, `lost_id`, `found_id`, `status`, `is_resolved`, `resolved_at`, `matched_at`) VALUES
(55, 410, 409, 'pending', 0, NULL, '2026-10-04 07:00:51'),
(56, 411, 412, 'pending', 0, NULL, '2026-10-04 07:56:58'),
(57, 413, 412, 'pending', 0, NULL, '2026-10-04 09:29:27'),
(60, 421, 418, 'pending', 0, NULL, '2026-10-06 05:12:44');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `id` int(11) NOT NULL,
  `firstName` varchar(100) NOT NULL,
  `lastName` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `contact` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `role_as` varchar(50) NOT NULL DEFAULT 'staff',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `banned` tinyint(1) DEFAULT 0,
  `google_uid` varchar(100) DEFAULT NULL,
  `picture` varchar(255) DEFAULT NULL,
  `mustChangePassword` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`id`, `firstName`, `lastName`, `email`, `contact`, `password`, `image`, `role_as`, `created_at`, `updated_at`, `banned`, `google_uid`, `picture`, `mustChangePassword`) VALUES
(14, 'King', 'Sieshiro', 'princepls17@gmail.com', '09234234233', '$2y$10$Af8suQt05Km56zr5YtjSlemXuBSTGw.mhP1JvaR6EDgwNM88l7BJC', '1789445287_0d6df65850d13ac1bca34bb7849b9e4a.jpg', 'staff', '2026-09-15 04:08:07', '2026-09-26 03:33:21', 1, NULL, NULL, 0),
(16, 'Nagi', 'boss kirisaki', 'rinadawson56@gmail.com', '09708000529', '$2y$10$m2eYMjZP2v/1M.sI3shGxeqKpSOczcXp3ArsJ8AtBbyEjCzesOF1q', 'default.jpg', 'staff', '2026-09-25 10:31:38', '2026-10-03 08:59:09', 0, NULL, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `stafflogs`
--

CREATE TABLE `stafflogs` (
  `id` int(11) NOT NULL,
  `staffId` int(11) NOT NULL,
  `login_time` datetime DEFAULT NULL,
  `logout_time` datetime DEFAULT NULL,
  `status` enum('online','offline') DEFAULT 'offline'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stafflogs`
--

INSERT INTO `stafflogs` (`id`, `staffId`, `login_time`, `logout_time`, `status`) VALUES
(55, 16, '2026-09-25 19:01:00', '2026-09-25 19:01:16', 'offline'),
(56, 16, '2026-09-25 19:33:43', '2026-09-25 19:33:58', 'offline'),
(57, 16, '2026-10-03 16:59:09', '2026-10-03 16:59:16', 'offline'),
(58, 16, '2026-10-03 17:00:21', '2026-10-03 17:00:35', 'offline');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `adminlogs`
--
ALTER TABLE `adminlogs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `adminId` (`adminId`);

--
-- Indexes for table `lost_found`
--
ALTER TABLE `lost_found`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lost_found_deletions`
--
ALTER TABLE `lost_found_deletions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lost_found_edits`
--
ALTER TABLE `lost_found_edits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_lost_found_id` (`lost_found_id`);

--
-- Indexes for table `lost_found_matches`
--
ALTER TABLE `lost_found_matches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `stafflogs`
--
ALTER TABLE `stafflogs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `staffId` (`staffId`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `adminlogs`
--
ALTER TABLE `adminlogs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=466;

--
-- AUTO_INCREMENT for table `lost_found`
--
ALTER TABLE `lost_found`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=423;

--
-- AUTO_INCREMENT for table `lost_found_deletions`
--
ALTER TABLE `lost_found_deletions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lost_found_edits`
--
ALTER TABLE `lost_found_edits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `lost_found_matches`
--
ALTER TABLE `lost_found_matches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `stafflogs`
--
ALTER TABLE `stafflogs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `adminlogs`
--
ALTER TABLE `adminlogs`
  ADD CONSTRAINT `adminlogs_ibfk_1` FOREIGN KEY (`adminId`) REFERENCES `admin` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stafflogs`
--
ALTER TABLE `stafflogs`
  ADD CONSTRAINT `stafflogs_ibfk_1` FOREIGN KEY (`staffId`) REFERENCES `staff` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

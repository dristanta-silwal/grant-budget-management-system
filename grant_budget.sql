-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 16, 2024 at 12:33 AM
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
-- Database: `grant_budget`
--

-- --------------------------------------------------------

--
-- Table structure for table `budget_categories`
--

CREATE TABLE `budget_categories` (
  `id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `max_amount` decimal(10,2) DEFAULT NULL,
  `restrictions` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `budget_categories`
--

INSERT INTO `budget_categories` (`id`, `category_name`, `max_amount`, `restrictions`) VALUES
(1, 'Personnel Compensation', 30.00, 'Restrictions for Personnel Compensation'),
(2, 'Other Personnel', 20.00, 'Restrictions for Other Personnel '),
(4, 'Equipment', 10.00, 'Equipment in this field should be >$5000'),
(5, 'Travel', 10.00, 'Restrictions for travel'),
(6, 'Other Direct Costs', 10.00, 'Restrictions for Other Direct Costs'),
(7, 'Subawards', 10.00, 'Restrictions for Subawards'),
(8, 'Total Direct Costs', 10.00, 'Restrictions for Total Direct Costs');

-- --------------------------------------------------------

--
-- Table structure for table `budget_items`
--

CREATE TABLE `budget_items` (
  `id` int(11) NOT NULL,
  `grant_id` int(11) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `year_1` decimal(12,2) DEFAULT 0.00,
  `year_2` decimal(12,2) DEFAULT 0.00,
  `year_3` decimal(12,2) DEFAULT 0.00,
  `year_4` decimal(12,2) DEFAULT 0.00,
  `year_5` decimal(12,2) DEFAULT 0.00,
  `year_6` decimal(12,2) DEFAULT 0.00,
  `hourly_rate` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `budget_items`
--

INSERT INTO `budget_items` (`id`, `grant_id`, `category_id`, `description`, `amount`, `year_1`, `year_2`, `year_3`, `year_4`, `year_5`, `year_6`, `hourly_rate`) VALUES
(15, 4, 1, 'PI', 140.00, 60.00, 0.00, 30.00, 60.00, NULL, NULL, NULL),
(16, 4, 1, 'Co-PI', 95.00, 30.00, 40.00, 20.00, 5.00, NULL, NULL, NULL),
(17, 4, 1, 'Co-PI', 100.00, 60.00, 20.00, 0.00, 20.00, NULL, NULL, NULL),
(19, 4, 2, 'UI professional staff & Post Docs', 450.00, 100.00, 80.00, 120.00, 150.00, NULL, NULL, NULL),
(20, 4, 2, 'GRAs/UGrads', 580.00, 150.00, 120.00, 130.00, 180.00, NULL, NULL, NULL),
(21, 4, 2, 'Temp Help', 240.00, 70.00, 50.00, 90.00, 30.00, NULL, NULL, NULL),
(22, 4, 5, 'Domestic', 19000.00, 5000.00, 6000.00, 3000.00, 5000.00, NULL, NULL, NULL),
(23, 4, 5, 'International', 210.00, 60.00, 60.00, 40.00, 50.00, NULL, NULL, NULL),
(24, 4, 4, 'Large Servers', 27000.00, 6000.00, 8000.00, 7000.00, 6000.00, NULL, NULL, NULL),
(25, 4, 4, 'Equipment s for data room setup', 33000.00, 9000.00, 10000.00, 6000.00, 8000.00, NULL, NULL, NULL),
(26, 4, 6, 'Materialts & Supplies', 5800.00, 500.00, 2000.00, 1500.00, 1800.00, NULL, NULL, NULL),
(27, 4, 6, '<$5K small equipment', 5700.00, 3000.00, 1500.00, 200.00, 1000.00, NULL, NULL, NULL),
(28, 4, 6, 'Computer Services', 1450.00, 300.00, 50.00, 800.00, 300.00, NULL, NULL, NULL),
(29, 4, 6, 'Software', 1900.00, 300.00, 400.00, 300.00, 900.00, NULL, NULL, NULL),
(30, 4, 6, 'Conference Registration', 3900.00, 1000.00, 1500.00, 900.00, 500.00, NULL, NULL, NULL),
(31, 4, 6, 'Other', 2100.00, 300.00, 500.00, 1000.00, 300.00, NULL, NULL, NULL),
(32, 4, 8, 'Back Out GRA T&F', 12000.00, 3000.00, 5000.00, 1000.00, 3000.00, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `fringe_rates`
--

CREATE TABLE `fringe_rates` (
  `id` int(11) NOT NULL,
  `role` varchar(50) NOT NULL,
  `year` int(11) NOT NULL CHECK (`year` between 1 and 6),
  `fringe_rate` decimal(5,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fringe_rates`
--

INSERT INTO `fringe_rates` (`id`, `role`, `year`, `fringe_rate`) VALUES
(1, 'Faculty', 1, 30.00),
(2, 'Faculty', 2, 30.50),
(3, 'Faculty', 3, 31.00),
(4, 'Faculty', 4, 31.50),
(5, 'Faculty', 5, 32.00),
(7, 'UI professional staff & Post Docs', 1, 32.00),
(8, 'UI professional staff & Post Docs', 2, 32.50),
(9, 'UI professional staff & Post Docs', 3, 33.00),
(10, 'UI professional staff & Post Docs', 4, 33.50),
(11, 'UI professional staff & Post Docs', 5, 34.00),
(13, 'GRAs/UGrads', 1, 20.00),
(14, 'GRAs/UGrads', 2, 20.50),
(15, 'GRAs/UGrads', 3, 21.00),
(16, 'GRAs/UGrads', 4, 21.50),
(17, 'GRAs/UGrads', 5, 22.00),
(19, 'Temp Help', 1, 10.00),
(20, 'Temp Help', 2, 10.50),
(21, 'Temp Help', 3, 11.00),
(22, 'Temp Help', 4, 11.50),
(23, 'Temp Help', 5, 12.00);

-- --------------------------------------------------------

--
-- Table structure for table `grants`
--

CREATE TABLE `grants` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(100) NOT NULL,
  `agency` varchar(100) NOT NULL,
  `duration_in_years` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `grants`
--

INSERT INTO `grants` (`id`, `user_id`, `title`, `agency`, `duration_in_years`, `total_amount`, `start_date`, `end_date`) VALUES
(4, 1, 'Machine Learning Project', 'NSF', 4, 50000.00, '2024-11-10', '2028-11-08'),
(11, 1, 'Data Science Project', 'NIH', 3, 50000.00, '2024-11-15', '0000-00-00'),
(12, 2, 'Test Project', 'NSF', 5, 500000.00, '2024-11-15', '0000-00-00');

-- --------------------------------------------------------

--
-- Table structure for table `grant_users`
--

CREATE TABLE `grant_users` (
  `id` int(11) NOT NULL,
  `grant_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` enum('creator','PI','CO-PI','viewer') NOT NULL DEFAULT 'viewer',
  `added_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','accepted','rejected') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `grant_users`
--

INSERT INTO `grant_users` (`id`, `grant_id`, `user_id`, `role`, `added_at`, `status`) VALUES
(8, 4, 1, 'PI', '2024-11-10 07:19:25', 'accepted'),
(9, 4, 2, 'CO-PI', '2024-11-10 07:19:25', 'accepted'),
(10, 4, 3, 'CO-PI', '2024-11-10 07:19:25', 'accepted'),
(28, 4, 144, 'CO-PI', '2024-11-10 07:19:25', 'accepted'),
(29, 4, 4, 'CO-PI', '2024-11-10 07:19:25', 'accepted'),
(37, 11, 1, 'PI', '2024-11-14 21:28:46', 'accepted'),
(38, 11, 2, 'CO-PI', '2024-11-14 21:28:46', 'accepted'),
(39, 11, 16, 'CO-PI', '2024-11-14 21:28:46', 'pending'),
(40, 11, 163, 'PI', '2024-11-14 21:28:46', 'pending'),
(41, 11, 159, 'viewer', '2024-11-14 21:28:46', 'accepted'),
(42, 11, 3, 'CO-PI', '2024-11-14 21:28:46', 'pending'),
(43, 11, 175, 'PI', '2024-11-14 21:28:46', 'pending'),
(44, 12, 2, 'PI', '2024-11-14 21:47:05', 'accepted');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read` tinyint(1) DEFAULT 0,
  `grant_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `message`, `created_at`, `read`, `grant_id`) VALUES
(15, 2, 'You have been invited to join the grant: dsaf as a PI.', '2024-11-12 21:30:08', 1, 9),
(16, 16, 'You have been invited to join the grant: sdf as a CO-PI.', '2024-11-14 00:42:29', 1, 10),
(17, 18, 'You have been invited to join the grant: sdf as a PI.', '2024-11-14 00:42:29', 0, 10),
(18, 1, 'You have been invited to join the grant: sdf as a PI.', '2024-11-14 00:42:29', 1, 10),
(19, 163, 'You have been invited to join the grant: sdf as a CO-PI.', '2024-11-14 00:42:29', 0, 10),
(20, 2, 'You have been invited to join the grant: Data Scienc Project as a PI.', '2024-11-14 21:28:46', 1, 11),
(21, 16, 'You have been invited to join the grant: Data Scienc Project as a CO-PI.', '2024-11-14 21:28:46', 0, 11),
(22, 163, 'You have been invited to join the grant: Data Scienc Project as a PI.', '2024-11-14 21:28:46', 0, 11),
(23, 3, 'You have been invited to join the grant: Data Scienc Project as a CO-PI.', '2024-11-14 21:28:46', 0, 11),
(24, 175, 'You have been invited to join the grant: Data Scienc Project as a PI.', '2024-11-14 21:28:46', 0, 11);

-- --------------------------------------------------------

--
-- Table structure for table `salaries`
--

CREATE TABLE `salaries` (
  `id` int(11) NOT NULL,
  `role` varchar(50) NOT NULL,
  `year` int(11) NOT NULL CHECK (`year` between 1 and 6),
  `hourly_rate` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `salaries`
--

INSERT INTO `salaries` (`id`, `role`, `year`, `hourly_rate`) VALUES
(29, 'PI', 1, 32.00),
(30, 'PI', 2, 52.00),
(31, 'PI', 3, 54.00),
(32, 'PI', 4, 56.00),
(33, 'PI', 5, 58.00),
(35, 'Co-PI', 1, 36.00),
(36, 'Co-PI', 2, 45.00),
(37, 'Co-PI', 3, 47.00),
(38, 'Co-PI', 4, 48.00),
(39, 'Co-PI', 5, 50.00),
(41, 'UI professional staff & Post Docs', 1, 40.00),
(42, 'UI professional staff & Post Docs', 2, 42.00),
(43, 'UI professional staff & Post Docs', 3, 44.00),
(44, 'UI professional staff & Post Docs', 4, 46.00),
(45, 'UI professional staff & Post Docs', 5, 48.00),
(47, 'GRAs/UGrads', 1, 25.00),
(48, 'GRAs/UGrads', 2, 26.00),
(49, 'GRAs/UGrads', 3, 27.00),
(50, 'GRAs/UGrads', 4, 28.00),
(51, 'GRAs/UGrads', 5, 29.00),
(53, 'Temp Help', 1, 15.00),
(54, 'Temp Help', 2, 15.50),
(55, 'Temp Help', 3, 16.00),
(56, 'Temp Help', 4, 16.50),
(57, 'Temp Help', 5, 17.00),
(59, 'Faculty', 1, 40.00),
(60, 'Faculty', 2, 42.00),
(61, 'Faculty', 3, 44.00),
(62, 'Faculty', 4, 45.00),
(63, 'Faculty', 5, 46.00);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(255) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `organization` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `username`, `password`, `last_name`, `email`, `organization`) VALUES
(1, 'Dristanta', 'Dristanta', '$2y$10$ZqH5VZYHXtJtWv5RnQhEne6.Sx79TRpMZPeGo9zxgePz2Hejs9sU2', 'Silwal', 'dristantasilwal003@gmail.com', 'Org1'),
(2, 'Saran', 'Saran', '$2y$10$cjsPjhjPqaGB/WCzkBVVLeykKXO3MjgP5GshulD/rAKqdNfE6sAdK', 'Raes', 'saran.dae@bsu.edu', 'Boise State University'),
(3, 'Marco', 'Marco', '$2y$10$80YkPEz33yr57D6iWI3J3OPrqyXfcHjWAd5afwxUGEk9WHtVrSqvy', 'Francs', 'francomarco@isu.edu', 'Idaho State University'),
(4, 'Bigyan', 'Bigyan', '$2y$10$mvQ8bAHTnV0gpt93jIPoS.ABCrbRyR.vj.EFNHP1pNCBXmZFXrGgK', 'Karki', 'Laudubigyan@gmail.com', 'Idaho State University'),
(10, 'John', 'John', '$2y$10$dRtnVo7l.DozPPO04MqM3O5H6dQ/bgCKTVxV0CLTIPDgdZ8famwh6', 'Joe', 'jdoe@example.com', 'Boise State University'),
(11, 'Jane', 'Jane', '$2y$10$P5qUrhg/tFqoQj3F9nFql.sBqpKKntEzpcREOVBgI.JOdVeNUCC7K', 'Smith', 'jsmith@raver.com', 'Idaho State University'),
(12, 'Alice', 'Alice', '$2y$10$B5Ovaydagn3seBO.ja5mRefGvQYwp/3E7zMKatq03gp3BWAqv1dte', 'Creed', 'acreed@mail.isu.edu', 'Idaho State University'),
(13, 'Kapil', 'Kapil', '$2y$10$h2XG7ejHzrqOJz80Vl/EpexeRpeMB.RiHuREdcvCCkO4F9ZJ6Do5e', 'Poudel', 'kapilpoudel@uidaho.edu', 'University of Idaho'),
(14, 'Emma', 'Emma', '$2y$10$ZnnZozK5BLtH1tGOuXJT.uILt4H2QbHbvtNcqOKAGjQ.3ezy8WTFq', 'Taylor', 'tayloremma@vandals.uidaho.edu', 'University of Idaho'),
(15, 'David', 'David', '$2y$10$zQFyw.fbsf1eyU7f0ootFuAcEADNeNwz2fWXFDtyxmU1YgKva7R1C', 'Beckham', 'bechamd@isu.edu', 'Idaho State University'),
(16, 'Sarad', 'Sarad', '$2y$10$rODagdgpzjf3DAbmdPHWROAwP/cyoSUkgxgFKRitp59HzXF/pZ39i', 'Vanco', 'sarad@test.com', 'University of Idaho'),
(18, 'Saraswoti', 'Saraswoti', '$2y$10$bfCZ/tZNT/XeSWwS2QWXyunaMPsXjTtHGsizvSO/fFZZ3o6rGBGsq', 'Vanco', 'saraswoti@test.com', 'Boise State University'),
(32, 'Emma', 'Emmam', '$2y$10$PV3fMkHLKD9kfhOMr5yBfF7MNBPjX2K5wOVkQJrJpJ...', 'Mitchell', 'emmam@vandals.uidaho.edu', 'University of Idaho'),
(33, 'Jack', 'Jacky', '$2y$10$0PYROnFBKbFhLGhTFQx5KhLfMZ0RpTq7OPRTGLnIuU...', 'Davis', 'jackd@bsu.edu', 'Boise State University'),
(34, 'Olivia', 'Olivia', '$2y$10$9sFpRfL9Gn4J7fO4dKRMlJzRmMf2yBZL5PHG5tYfY4...', 'Lopez', 'olivial@isu.edu', 'Idaho State University'),
(140, 'Lucas', 'Lucas', '$2y$10$AaBQqJWgP4cBv9DSx1vphO9gK2HTBO8CZBsDf3KMOKR...', 'Morris', 'lucas88@isu.edu', 'Idaho State University'),
(141, 'Sophia', 'Sophia', '$2y$10$Afdf3AKjC0rHqPdoQi0vxiN8ZD7lgh6OHYrZTzF1FKh...', 'Thompson', 'sophiat@vandals.uidaho.edu', 'University of Idaho'),
(142, 'Ethan', 'Ethan', '$2y$10$ZtW5fJ/4zRJxoLRzkPnOmeRuY0S7MzOWmbxzTSJnLq...', 'Baker', 'ethanb@bsu.edu', 'Boise State University'),
(143, 'Mia', 'Mia', '$2y$10$BmRTv/4s9sdfZhGt5B7DlOPpTe72LZKmYOKjUjkGLd...', 'Clark', 'miac@isu.edu', 'Idaho State University'),
(144, 'Oliver', 'Oliver', '$2y$10$CJR1JL5KkGeOM/RTnpfyBe7nZB4yVK5hOtuH3BdKQI...', 'King', 'oliverk@vandals.uidaho.edu', 'University of Idaho'),
(145, 'Amelia', 'Amelia', '$2y$10$NDQ9h8Lnt4vXKQ5W5lvc9DYeU2qUG6wQTyF3NpZR8q...', 'Robinson', 'ameliar@bsu.edu', 'Boise State University'),
(146, 'Noah', 'Noah', '$2y$10$X8fE4Bs3ZqVzglFP1hX6OqeF7OVldVZ5q4eBlFqF6V...', 'Perry', 'noahp@isu.edu', 'Idaho State University'),
(147, 'Ava', 'Ava', '$2y$10$uNzRLZnS4Lrjgv7gWzVZ/O7O7BLF5xT1KHOt2JP1Kk...', 'Bennett', 'avab@vandals.uidaho.edu', 'University of Idaho'),
(148, 'William', 'William', '$2y$10$fhz9xT2wUuMOPVzGO6xeEpeF7eBCjwX2YxsLG7BDcP...', 'Harris', 'williamh@bsu.edu', 'Boise State University'),
(149, 'Isabella', 'Isabella', '$2y$10$9YO0TiQQLjkFbB8hKLY/0OprQ7/xeq6M5vBQ5GLlL...', 'Kennedy', 'isabellak@isu.edu', 'Idaho State University'),
(150, 'James', 'James', '$2y$10$hGJZ9LX2ZrffjGBF4TpOJExfpeK4GZBZ0wUVRQdfS2...', 'Lee', 'jamesl@vandals.uidaho.edu', 'University of Idaho'),
(151, 'Charlotte', 'Charlotte', '$2y$10$5HKv9zxIRZBFf5ZGHGHjU9nLd6F4jLK7YNbHF5FG5J...', 'Johnson', 'charlottej@bsu.edu', 'Boise State University'),
(152, 'Benjamin', 'Benjamin', '$2y$10$QFJkH9HYcBlf5dEF2o9hBOMmOQ1qYdQKjRVzqNRE6R...', 'Miller', 'benjaminm@isu.edu', 'Idaho State University'),
(153, 'Jack', 'Jack', '$2y$10$0PYROnFBKbFhLGhTFQx5KhLfMZ0RpTq7OPRTGLnIuU...', 'Davis', 'jacwkd@bsu.edu', 'Boise State University'),
(154, 'Liam', 'Liam', '$2y$10$7sPfrGj8Nl0GnQp/z7P8AeFZsYZkj/hJjfbT3O4pQ.a...', 'White', 'liamw@bsu.edu', 'Boise State University'),
(155, 'Harper', 'Harper', '$2y$10$ZP5Y8SkLPgD8XsRf3Tt6WLe8zxZn1K4TfF5dXsGZq9...', 'Reed', 'harperr@isu.edu', 'Idaho State University'),
(156, 'Alexander', 'Alexander', '$2y$10$7dkskf3O4Kd8uDh/sPfXSlvYknFz8x5JjfhLdB5O9N...', 'Green', 'alexgreen@vandals.uidaho.edu', 'University of Idaho'),
(157, 'Ella', 'Ella', '$2y$10$Tz7V3O9Khk3JY6dUOnSlP8QHk2Zv0x3R1Y9Jq8U5Ps...', 'Young', 'ellay@bsu.edu', 'Boise State University'),
(158, 'Henry', 'Henry', '$2y$10$fV9RJSkPL8d5VsLf5QZ3QLe7vXhJ0xKhR5S4gU5Ps9...', 'Scott', 'henrys@isu.edu', 'Idaho State University'),
(159, 'Avery', 'Avery', '$2y$10$9sdTJKy3Pl8VpGhQ4Rk5OLe8vYr7P5Kh3T4gL9Fs9Q...', 'Morgan', 'averym@vandals.uidaho.edu', 'University of Idaho'),
(160, 'Samuel', 'Samuel', '$2y$10$LsQ0V7eNd6V4Ph5Q6FJ3QLe7vTqN0x7Xj8fS9YjUs9...', 'Hughes', 'samh@bsu.edu', 'Boise State University'),
(161, 'Aria', 'Aria', '$2y$10$KsQ7V5gPd7Vs7Lf6FJ9KZe2Yt8P5QJ4Fs7GJ3xHs9Z...', 'Gray', 'ariag@isu.edu', 'Idaho State University'),
(162, 'Sebastian', 'Sebastian', '$2y$10$JsV0T3hNd6T5Gh9WJk8RZe8qYr9P5Kh5J5dL9F8Zp1...', 'Hall', 'sebastianh@vandals.uidaho.edu', 'University of Idaho'),
(163, 'Grace', 'Grace', '$2y$10$Tf0G5gPpL3V5Sf9R3T8OZe8qVn2P0x1Fg7H8xGJf9U...', 'Allen', 'gracea@bsu.edu', 'Boise State University'),
(164, 'Daniel', 'Daniel', '$2y$10$9dvT3G8Np7F5Jh5Y3J9DZe7VqF6O2x6Jh7L5dSf9Gj...', 'Parker', 'danielp@isu.edu', 'Idaho State University'),
(165, 'Chloe', 'Chloe', '$2y$10$Af4P5V7Np6R5Th9LJ8QZe2YqGn8S5x2Jf7GJ9Ff4Q7...', 'Brooks', 'chloeb@vandals.uidaho.edu', 'University of Idaho'),
(166, 'Matthew', 'Matthew', '$2y$10$7cP9R2bKl4F9Th0Y3FZ3QLe9VqM5N0x3Lk8S9T6Gj3...', 'Ward', 'mattheww@bsu.edu', 'Boise State University'),
(167, 'Zoey', 'Zoey', '$2y$10$9vT3G6hKh5V4Sh5LJ3FZ3QLe4vZ8S9H8dGJ3Jk4G7A...', 'Cox', 'zoeyc@isu.edu', 'Idaho State University'),
(168, 'Elijah', 'Elijah', '$2y$10$Ff8V5K9Pd6V3Sh3Y2FZ3RLe8qT7P9H7Kf8S8dSf9Lf...', 'Torres', 'elijaht@vandals.uidaho.edu', 'University of Idaho'),
(169, 'Lily', 'Lily', '$2y$10$8kP7J5QpL2F5Th9R3J9KZe8qVr2S0y3Jf9G8Yf4Q9T...', 'Peterson', 'lilyp@bsu.edu', 'Boise State University'),
(170, 'Jackson', 'Jackson', '$2y$10$9fT3G8JkL5V4Ph9J2QZ4QLe9vY9N5h7Jf8K9Gf5L7M...', 'Ramirez', 'jacksonr@isu.edu', 'Idaho State University'),
(171, 'Scarlett', 'Scarlett', '$2y$10$8vT6J9GqL4V3Rh9Y2FZ4QRe8qM8P5y5Jf7G5dLf4H7...', 'Carter', 'scarlettc@vandals.uidaho.edu', 'University of Idaho'),
(172, 'Julian', 'Julian', '$2y$10$Lf0T3H8Jl6V5Rh3LJ9RZe8yVqP2O2h7Jh7G4dYf3J3...', 'Mitchell', 'julianm@bsu.edu', 'Boise State University'),
(173, 'Hannah', 'Hannah', '$2y$10$7sT3F9GkN2V5Jh0L3J8FZe7qM6N9H9Kj9S6Yf2J9F7...', 'Perez', 'hannahp@isu.edu', 'Idaho State University'),
(174, 'Brian', 'Brian', '$2y$10$JevP2uBpPB9ompNMJy5WuOUNPrhbJBX8s155nlxcuBAneDf/d/LXG', 'Lenab', 'lenabbrian@uidaho.edu', 'University of Idaho'),
(175, 'Sikha', 'Sikha', '$2y$10$d7VvYy/xadB0x00W/GhdHOqGkYcCanqaOpEAzRlTBWEdz84q5mtRu', 'Chaudhary', 'sikhachaudhary@gmail.com', 'University of Idaho'),
(176, 'sadf', 'sadf', '$2y$10$sMDkd95DPkKVU9i2s./86e15slh5gxcD6jBx/HobbYgR6FM1zo4fy', 'asdf', 'sadf@as.com', 'Idaho State University');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `budget_categories`
--
ALTER TABLE `budget_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `budget_items`
--
ALTER TABLE `budget_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `grant_id` (`grant_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `fringe_rates`
--
ALTER TABLE `fringe_rates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `grants`
--
ALTER TABLE `grants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `grant_users`
--
ALTER TABLE `grant_users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `grant_id` (`grant_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `salaries`
--
ALTER TABLE `salaries`
  ADD PRIMARY KEY (`id`);

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
-- AUTO_INCREMENT for table `budget_categories`
--
ALTER TABLE `budget_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `budget_items`
--
ALTER TABLE `budget_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `fringe_rates`
--
ALTER TABLE `fringe_rates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `grants`
--
ALTER TABLE `grants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `grant_users`
--
ALTER TABLE `grant_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `salaries`
--
ALTER TABLE `salaries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=177;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `budget_items`
--
ALTER TABLE `budget_items`
  ADD CONSTRAINT `budget_items_ibfk_1` FOREIGN KEY (`grant_id`) REFERENCES `grants` (`id`),
  ADD CONSTRAINT `budget_items_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `budget_categories` (`id`);

--
-- Constraints for table `grants`
--
ALTER TABLE `grants`
  ADD CONSTRAINT `grants_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `grants_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `grant_users`
--
ALTER TABLE `grant_users`
  ADD CONSTRAINT `grant_users_ibfk_1` FOREIGN KEY (`grant_id`) REFERENCES `grants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `grant_users_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

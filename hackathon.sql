-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 04, 2026 at 08:45 PM
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
-- Database: `hackathon`
--

-- --------------------------------------------------------

--
-- Table structure for table `conversations`
--

CREATE TABLE `conversations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT 'Untitled Conversation',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `conversations`
--

INSERT INTO `conversations` (`id`, `user_id`, `title`, `created_at`) VALUES
(16, 3, 'Untitled Conversation', '2026-03-03 20:55:20'),
(17, 3, 'Untitled Conversation', '2026-03-04 14:27:05'),
(18, 3, 'Untitled Conversation', '2026-03-04 15:08:40');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `conversation_id` int(11) NOT NULL,
  `role` enum('user','assistant') NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `conversation_id`, `role`, `content`, `created_at`) VALUES
(43, 16, 'user', 'what is photosynthesis', '2026-03-03 20:55:20'),
(44, 16, 'assistant', 'Photosynthesis is a process used by plants, algae, and some bacteria to convert light energy from the sun into chemical energy stored in glucose. This process primarily occurs in the chloroplasts of plant cells, where chlorophyll captures sunlight. The overall chemical equation for photosynthesis is:\n\n\\[ 6CO_2 + 6H_2O + light energy \\rightarrow C_6H_{12}O_6 + 6O_2 \\]\n\nThis means that carbon dioxide and water, in the presence of light energy, are converted into glucose and oxygen. Oxygen is released as a byproduct.', '2026-03-03 20:55:22'),
(45, 17, 'user', 'what is photosynthesis', '2026-03-04 14:27:05'),
(46, 17, 'assistant', 'Photosynthesis is the process by which plants, algae, and some bacteria convert light energy, usually from the sun, into chemical energy stored in glucose. This process occurs primarily in the chloroplasts of plant cells, where chlorophyll captures sunlight. The overall equation for photosynthesis is:\n\n\\[ 6CO_2 + 6H_2O + light energy \\rightarrow C_6H_{12}O_6 + 6O_2 \\]\n\nThis means that carbon dioxide and water, in the presence of light energy, are converted into glucose and oxygen. The oxygen is released into the atmosphere as a byproduct.', '2026-03-04 14:27:07'),
(47, 18, 'user', 'What is photosynthesis', '2026-03-04 15:08:40'),
(48, 18, 'assistant', 'Photosynthesis is a process used by plants, algae, and some bacteria to convert light energy, usually from the sun, into chemical energy stored in glucose. This process primarily occurs in the chloroplasts of plant cells, where chlorophyll, a green pigment, captures light energy. The overall chemical equation for photosynthesis can be summarized as:\n\n\\[ 6CO_2 + 6H_2O + light energy \\rightarrow C_6H_{12}O_6 + 6O_2 \\]\n\nThis means that carbon dioxide and water, in the presence of light energy, are converted into glucose and oxygen. Oxygen is released as a byproduct.', '2026-03-04 15:08:42');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `huggingface_token` varchar(500) DEFAULT NULL,
  `huggingface_model` varchar(255) DEFAULT 'Qwen/Qwen2.5-7B-Instruct'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `created_at`, `huggingface_token`, `huggingface_model`) VALUES
(3, 'newbie', 'new@gmail.com', '$2y$10$ugT.GOlEVg326VIhiIFGu.dLHs7JDBM0MwoR6esku3eoIZs78wgEG', '2026-03-03 20:41:15', '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', 'Qwen/Qwen2.5-7B-Instruct');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `conversations`
--
ALTER TABLE `conversations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `conversation_id` (`conversation_id`);

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
-- AUTO_INCREMENT for table `conversations`
--
ALTER TABLE `conversations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `conversations`
--
ALTER TABLE `conversations`
  ADD CONSTRAINT `conversations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

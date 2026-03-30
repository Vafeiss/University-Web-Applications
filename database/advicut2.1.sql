-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 29, 2026 at 05:17 PM
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
-- Database: `advicut`
--

-- --------------------------------------------------------

--
-- Table structure for table `conversations`
--

CREATE TABLE `conversations` (
  `Conversation_ID` int(11) NOT NULL,
  `Student_ID` int(11) NOT NULL,
  `Advisor_ID` int(11) NOT NULL,
  `Created_At` datetime DEFAULT current_timestamp(),
  `Updated_At` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `degree`
--

CREATE TABLE `degree` (
  `DegreeID` int(11) NOT NULL,
  `DepartmentID` int(11) NOT NULL,
  `DegreeName` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `degree`
--

INSERT INTO `degree` (`DegreeID`, `DepartmentID`, `DegreeName`) VALUES
(1, 1, 'Computer Engineer & Informatics');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `DepartmentID` int(11) NOT NULL,
  `DepartmentName` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`DepartmentID`, `DepartmentName`) VALUES
(1, 'HMMHY');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `Message_ID` int(11) NOT NULL,
  `Conversation_ID` int(11) NOT NULL,
  `Sender_ID` int(11) NOT NULL,
  `Message_Text` text NOT NULL,
  `Sent_At` datetime DEFAULT current_timestamp(),
  `Is_Read` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `promotion_log`
--

CREATE TABLE `promotion_log` (
  `id` int(11) NOT NULL,
  `promotion_year` int(11) NOT NULL,
  `executed_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `promotion_log`
--

INSERT INTO `promotion_log` (`id`, `promotion_year`, `executed_at`) VALUES
(1, 2026, '2026-03-26 20:48:38');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `User_ID` int(11) NOT NULL,
  `year` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`User_ID`, `year`) VALUES
(42, 4),
(55, 5),
(67, 6),
(69, 2);

-- --------------------------------------------------------

--
-- Table structure for table `student_advisors`
--

CREATE TABLE `student_advisors` (
  `Student_ID` int(11) NOT NULL,
  `Advisor_ID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_advisors`
--

INSERT INTO `student_advisors` (`Student_ID`, `Advisor_ID`) VALUES
(12234, 2000),
(24503, 30080),
(22222, 44556),
(40546, 44556);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `User_ID` int(11) NOT NULL,
  `External_ID` int(11) DEFAULT NULL,
  `Uni_Email` varchar(150) NOT NULL,
  `Password` varchar(200) DEFAULT NULL,
  `Role` enum('Student','Advisor','Admin','SuperUser') NOT NULL,
  `First_name` varchar(50) NOT NULL,
  `Last_Name` varchar(50) NOT NULL,
  `Phone` varchar(20) DEFAULT NULL,
  `Department_ID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`User_ID`, `External_ID`, `Uni_Email`, `Password`, `Role`, `First_name`, `Last_Name`, `Phone`, `Department_ID`) VALUES
(1, 1, 'admin@cut.ac.cy', '$2y$10$46bh2IXiYsStGwDSNr5zoernaZ7.ZYjHJqJeMtF4SXPlHRCvgzKoe', 'Admin', 'admin', 'admin', '', NULL),
(3, 30080, 'test@cut.ac.cy', '$2y$10$xGMjYptph0okQ8Xcsk1RjOX0Uvjkaz5Xj9yvAiN/cnovEI7mDm7Qi', 'Advisor', 'test', 'test2', '97854623', 1),
(42, 24503, 'b@edu.cut.ac.cy', '$2y$10$DKWfoubG8oTfoKRlVhM9jev3nOUx7SGtYNJjAyUNaEmLadaOKOhwK', 'Student', 'andreas', 'Test2', NULL, 1),
(43, 44556, 'test2@gmail.com', '$2y$10$46bh2IXiYsStGwDSNr5zoernaZ7.ZYjHJqJeMtF4SXPlHRCvgzKoe', 'Advisor', 'test2', 'test2', '96751099', 1),
(44, 23232, 'test3@gmail.com', '$2y$10$683alx37Nbd4Vho1Bi64IOZwiz6Iwmrj/N78ck5rfGJM1j0drBGAu', 'Advisor', 'test3ad', 'test3ad', '99786720', 1),
(55, 22222, 'a.kyriakou@edu.cut.ac.cy', '$2y$10$GnssS31HIi.YXoRLgCsqf.xV/f4EeC2KDL9SaZYj3BU42DHZW80Mi', 'Student', 'andreas', 'Kyriakoy', NULL, 1),
(60, 2000, 'test4@cut.ac.cy', '$2y$10$OEn5rHQme53GuQa.Pnwi6u47whJ4ub/r0Eeo1d1X0yHOKE9csZEDC', 'Advisor', 'test4', 'test4ad', '99875049', 1),
(67, 12234, 'student@edu.cut.ac.cy', '$2y$10$urXHjKD6/7k6wYCfE2RL2.jWizrfrw9T7X2S.HlimGf5P9X1SA30S', 'Student', 'Student', 'Student3', NULL, 1),
(69, 40546, 'pl@edu.cut.ac.cy', '$2y$10$FgDGU2MuS.XOqfeff3St.emL7VD0mFDsC8PdHp/y7Lh1X.LrOqA2.', 'Student', 'Polis', 'Polikarpou', NULL, 1),
(73, 2, 'Superuser@cut.ac.cy', '$2y$10$TqvTUH.RQUMUP9LvprY7d.ZsXPPzi3aNjpCom56poVXBJrWiFKtPK', 'SuperUser', 'SuperUser', 'SuperUser', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `conversations`
--
ALTER TABLE `conversations`
  ADD PRIMARY KEY (`Conversation_ID`),
  ADD KEY `Student_ID` (`Student_ID`),
  ADD KEY `Advisor_ID` (`Advisor_ID`);

--
-- Indexes for table `degree`
--
ALTER TABLE `degree`
  ADD PRIMARY KEY (`DegreeID`),
  ADD KEY `department_delete` (`DepartmentID`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`DepartmentID`),
  ADD UNIQUE KEY `DepartmentName` (`DepartmentName`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`Message_ID`),
  ADD KEY `Conversation_ID` (`Conversation_ID`),
  ADD KEY `Sender_ID` (`Sender_ID`);

--
-- Indexes for table `promotion_log`
--
ALTER TABLE `promotion_log`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `promotion_year` (`promotion_year`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`User_ID`);

--
-- Indexes for table `student_advisors`
--
ALTER TABLE `student_advisors`
  ADD PRIMARY KEY (`Student_ID`),
  ADD KEY `Advisor_ID` (`Advisor_ID`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`User_ID`),
  ADD UNIQUE KEY `Uni_Email` (`Uni_Email`),
  ADD UNIQUE KEY `External_ID` (`External_ID`),
  ADD KEY `Department_ID` (`Department_ID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `conversations`
--
ALTER TABLE `conversations`
  MODIFY `Conversation_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `DepartmentID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `Message_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `promotion_log`
--
ALTER TABLE `promotion_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `User_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `conversations`
--
ALTER TABLE `conversations`
  ADD CONSTRAINT `conversations_ibfk_1` FOREIGN KEY (`Student_ID`) REFERENCES `users` (`User_ID`),
  ADD CONSTRAINT `conversations_ibfk_2` FOREIGN KEY (`Advisor_ID`) REFERENCES `users` (`User_ID`);

--
-- Constraints for table `degree`
--
ALTER TABLE `degree`
  ADD CONSTRAINT `department_delete` FOREIGN KEY (`DepartmentID`) REFERENCES `departments` (`DepartmentID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`Conversation_ID`) REFERENCES `conversations` (`Conversation_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`Sender_ID`) REFERENCES `users` (`User_ID`);

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`User_ID`) REFERENCES `users` (`User_ID`) ON DELETE CASCADE;

--
-- Constraints for table `student_advisors`
--
ALTER TABLE `student_advisors`
  ADD CONSTRAINT `student_advisors_ibfk_1` FOREIGN KEY (`Advisor_ID`) REFERENCES `users` (`External_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_advisors_ibfk_2` FOREIGN KEY (`Student_ID`) REFERENCES `users` (`External_ID`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`Department_ID`) REFERENCES `degree` (`DegreeID`) ON DELETE NO ACTION ON UPDATE NO ACTION;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

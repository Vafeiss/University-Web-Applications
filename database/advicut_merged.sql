-- phpMyAdmin SQL Dump
-- Merged schema for AdviCut
-- Base: advicutv2.0.sql
-- Added: appointment tables from appointments export

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Database: `advicut`

-- --------------------------------------------------------
-- Table structure for table `appointment_history`
-- --------------------------------------------------------

CREATE TABLE `appointment_history` (
  `History_ID` int(11) NOT NULL,
  `Request_ID` int(11) DEFAULT NULL,
  `Appointment_ID` int(11) DEFAULT NULL,
  `Student_ID` int(11) NOT NULL,
  `Advisor_ID` int(11) NOT NULL,
  `Action_Type` enum('Requested','Approved','Declined','Cancelled','Completed') NOT NULL,
  `Action_Reason` text DEFAULT NULL,
  `Action_By` int(11) NOT NULL,
  `Created_At` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `appointments`
-- --------------------------------------------------------

CREATE TABLE `appointments` (
  `Appointment_ID` int(11) NOT NULL,
  `Request_ID` int(11) NOT NULL,
  `Student_ID` int(11) NOT NULL,
  `Advisor_ID` int(11) NOT NULL,
  `OfficeHour_ID` int(11) NOT NULL,
  `Appointment_Date` date NOT NULL,
  `Start_Time` time NOT NULL,
  `End_Time` time NOT NULL,
  `Status` enum('Scheduled','Completed','Cancelled') NOT NULL DEFAULT 'Scheduled',
  `Created_At` timestamp NOT NULL DEFAULT current_timestamp(),
  `Updated_At` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `appointment_requests`
-- --------------------------------------------------------

CREATE TABLE `appointment_requests` (
  `Request_ID` int(11) NOT NULL,
  `Student_ID` int(11) NOT NULL,
  `Advisor_ID` int(11) NOT NULL,
  `OfficeHour_ID` int(11) NOT NULL,
  `Appointment_Date` date NOT NULL,
  `Student_Reason` text NOT NULL,
  `Advisor_Reason` text DEFAULT NULL,
  `Status` enum('Pending','Approved','Declined','Cancelled') NOT NULL DEFAULT 'Pending',
  `Created_At` timestamp NOT NULL DEFAULT current_timestamp(),
  `Updated_At` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `communication_history`
-- --------------------------------------------------------

CREATE TABLE `communication_history` (
  `Comm_ID` int(11) NOT NULL,
  `Student_ID` int(11) NOT NULL,
  `Advisor_ID` int(11) NOT NULL,
  `Message_Content` text NOT NULL,
  `TimeStamp` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `degree`
-- --------------------------------------------------------

CREATE TABLE `degree` (
  `DegreeID` int(11) NOT NULL,
  `DepartmentID` int(11) NOT NULL,
  `DegreeName` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `degree` (`DegreeID`, `DepartmentID`, `DegreeName`) VALUES
(1, 1, 'Computer Engineer & Informatics'),
(2, 1, 'Electrical Engineer');

-- --------------------------------------------------------
-- Table structure for table `departments`
-- --------------------------------------------------------

CREATE TABLE `departments` (
  `DepartmentID` int(11) NOT NULL,
  `DepartmentName` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `departments` (`DepartmentID`, `DepartmentName`) VALUES
(1, 'HMMHY');

-- --------------------------------------------------------
-- Table structure for table `office_hours`
-- --------------------------------------------------------

CREATE TABLE `office_hours` (
  `OfficeHour_ID` int(11) NOT NULL,
  `Advisor_ID` int(11) NOT NULL,
  `Day_of_Week` enum('Monday','Tuesday','Wednesday','Thursday','Friday') NOT NULL,
  `Start_Time` time NOT NULL,
  `End_Time` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `students`
-- --------------------------------------------------------

CREATE TABLE `students` (
  `User_ID` int(11) NOT NULL,
  `year` int(11) DEFAULT NULL,
  `last_promoted_year` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `students` (`User_ID`, `year`, `last_promoted_year`) VALUES
(42, 3, NULL),
(51, 1, NULL),
(55, 4, NULL),
(59, 2, NULL),
(67, 5, NULL);

-- --------------------------------------------------------
-- Table structure for table `student_advisors`
-- --------------------------------------------------------

CREATE TABLE `student_advisors` (
  `Student_ID` int(11) NOT NULL,
  `Advisor_ID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `student_advisors` (`Student_ID`, `Advisor_ID`) VALUES
(24503, 30080),
(89760, 30080),
(22222, 44556),
(30405, 44556);

-- --------------------------------------------------------
-- Table structure for table `system_settings`
-- --------------------------------------------------------

CREATE TABLE `system_settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `system_settings` (`setting_key`, `setting_value`) VALUES
('academic_year', '2025');

-- --------------------------------------------------------
-- Table structure for table `users`
-- --------------------------------------------------------

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

INSERT INTO `users` (`User_ID`, `External_ID`, `Uni_Email`, `Password`, `Role`, `First_name`, `Last_Name`, `Phone`, `Department_ID`) VALUES
(1, 1, 'admin@cut.ac.cy', '$2y$10$46bh2IXiYsStGwDSNr5zoernaZ7.ZYjHJqJeMtF4SXPlHRCvgzKoe', 'Admin', 'admin', 'admin', '', NULL),
(3, 30080, 'test@cut.ac.cy', '$2y$10$xGMjYptph0okQ8Xcsk1RjOX0Uvjkaz5Xj9yvAiN/cnovEI7mDm7Qi', 'Advisor', 'test', 'test2', '97854623', 1),
(27, NULL, 'superuser@cut.ac.cy', '$2y$10$qV7TKVylCHp94RZ2JZp18Ow2OVVa8/QAPTv3jz3augluaWpchdfmy', 'SuperUser', 'SuperUser', 'SuperUser', NULL, NULL),
(42, 24503, 'b@edu.cut.ac.cy', '$2y$10$DKWfoubG8oTfoKRlVhM9jev3nOUx7SGtYNJjAyUNaEmLadaOKOhwK', 'Student', 'andreas', 'Test2', NULL, 1),
(43, 44556, 'test2@gmail.com', '$2y$10$uIRDeO6ANa9lQYBxh4qMpeeEeR6Ddan/hA.j5eWIQKOlEoNiMSaQK', 'Advisor', 'test2', 'test2', '96751099', 1),
(44, 23232, 'test3@gmail.com', '$2y$10$683alx37Nbd4Vho1Bi64IOZwiz6Iwmrj/N78ck5rfGJM1j0drBGAu', 'Advisor', 'test3ad', 'test3ad', '99786720', 1),
(51, 30405, 'a.ab@edu.cut.ac.cy', '$2y$10$DAh.I5YVoDMqe0R1LhuVDu1DtQddSO5dijh.T5O9Tyx1Zr4X.eRNe', 'Student', 'student', 'student1', NULL, 2),
(55, 22222, 'a.kyriakou@edu.cut.ac.cy', '$2y$10$GnssS31HIi.YXoRLgCsqf.xV/f4EeC2KDL9SaZYj3BU42DHZW80Mi', 'Student', 'andreas', 'Kyriakoy', NULL, 1),
(59, 89760, 'pn.panas@edu.cut.ac.cy', '$2y$10$IibB9D4ealImGp.nkAmHFuJ4d1hcawH4mMqSbHw.QUOoSwcs0G7ca', 'Student', 'student2', 'student2', NULL, 2),
(60, 2000, 'test4@cut.ac.cy', '$2y$10$OEn5rHQme53GuQa.Pnwi6u47whJ4ub/r0Eeo1d1X0yHOKE9csZEDC', 'Advisor', 'test4', 'test4ad', '99875049', 1),
(67, 12234, 'student@edu.cut.ac.cy', '$2y$10$urXHjKD6/7k6wYCfE2RL2.jWizrfrw9T7X2S.HlimGf5P9X1SA30S', 'Student', 'student3', 'student3', NULL, 1);

-- --------------------------------------------------------
-- Indexes for table `appointment_history`
-- --------------------------------------------------------

ALTER TABLE `appointment_history`
  ADD PRIMARY KEY (`History_ID`),
  ADD KEY `fk_history_request` (`Request_ID`),
  ADD KEY `fk_history_appointment` (`Appointment_ID`),
  ADD KEY `fk_history_student` (`Student_ID`),
  ADD KEY `fk_history_advisor` (`Advisor_ID`),
  ADD KEY `fk_history_action_by` (`Action_By`);

-- --------------------------------------------------------
-- Indexes for table `appointments`
-- --------------------------------------------------------

ALTER TABLE `appointments`
  ADD PRIMARY KEY (`Appointment_ID`),
  ADD KEY `fk_appointments_request` (`Request_ID`),
  ADD KEY `fk_appointments_student` (`Student_ID`),
  ADD KEY `fk_appointments_advisor` (`Advisor_ID`),
  ADD KEY `fk_appointments_officehour` (`OfficeHour_ID`);

-- --------------------------------------------------------
-- Indexes for table `appointment_requests`
-- --------------------------------------------------------

ALTER TABLE `appointment_requests`
  ADD PRIMARY KEY (`Request_ID`),
  ADD KEY `fk_appointment_requests_student` (`Student_ID`),
  ADD KEY `fk_appointment_requests_advisor` (`Advisor_ID`),
  ADD KEY `fk_appointment_requests_officehour` (`OfficeHour_ID`);

-- --------------------------------------------------------
-- Indexes for table `communication_history`
-- --------------------------------------------------------

ALTER TABLE `communication_history`
  ADD PRIMARY KEY (`Comm_ID`),
  ADD KEY `Student_ID` (`Student_ID`),
  ADD KEY `Advisor_ID` (`Advisor_ID`);

-- --------------------------------------------------------
-- Indexes for table `degree`
-- --------------------------------------------------------

ALTER TABLE `degree`
  ADD PRIMARY KEY (`DegreeID`),
  ADD KEY `department_delete` (`DepartmentID`);

-- --------------------------------------------------------
-- Indexes for table `departments`
-- --------------------------------------------------------

ALTER TABLE `departments`
  ADD PRIMARY KEY (`DepartmentID`),
  ADD UNIQUE KEY `DepartmentName` (`DepartmentName`);

-- --------------------------------------------------------
-- Indexes for table `office_hours`
-- --------------------------------------------------------

ALTER TABLE `office_hours`
  ADD PRIMARY KEY (`OfficeHour_ID`),
  ADD KEY `fk_officehours_advisor` (`Advisor_ID`);

-- --------------------------------------------------------
-- Indexes for table `students`
-- --------------------------------------------------------

ALTER TABLE `students`
  ADD PRIMARY KEY (`User_ID`);

-- --------------------------------------------------------
-- Indexes for table `student_advisors`
-- --------------------------------------------------------

ALTER TABLE `student_advisors`
  ADD PRIMARY KEY (`Student_ID`),
  ADD KEY `Advisor_ID` (`Advisor_ID`);

-- --------------------------------------------------------
-- Indexes for table `system_settings`
-- --------------------------------------------------------

ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`setting_key`);

-- --------------------------------------------------------
-- Indexes for table `users`
-- --------------------------------------------------------

ALTER TABLE `users`
  ADD PRIMARY KEY (`User_ID`),
  ADD UNIQUE KEY `Uni_Email` (`Uni_Email`),
  ADD UNIQUE KEY `External_ID` (`External_ID`),
  ADD KEY `Department_ID` (`Department_ID`);

-- --------------------------------------------------------
-- AUTO_INCREMENT for table `appointment_history`
-- --------------------------------------------------------

ALTER TABLE `appointment_history`
  MODIFY `History_ID` int(11) NOT NULL AUTO_INCREMENT;

-- --------------------------------------------------------
-- AUTO_INCREMENT for table `appointments`
-- --------------------------------------------------------

ALTER TABLE `appointments`
  MODIFY `Appointment_ID` int(11) NOT NULL AUTO_INCREMENT;

-- --------------------------------------------------------
-- AUTO_INCREMENT for table `appointment_requests`
-- --------------------------------------------------------

ALTER TABLE `appointment_requests`
  MODIFY `Request_ID` int(11) NOT NULL AUTO_INCREMENT;

-- --------------------------------------------------------
-- AUTO_INCREMENT for table `communication_history`
-- --------------------------------------------------------

ALTER TABLE `communication_history`
  MODIFY `Comm_ID` int(11) NOT NULL AUTO_INCREMENT;

-- --------------------------------------------------------
-- AUTO_INCREMENT for table `departments`
-- --------------------------------------------------------

ALTER TABLE `departments`
  MODIFY `DepartmentID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

-- --------------------------------------------------------
-- AUTO_INCREMENT for table `office_hours`
-- --------------------------------------------------------

ALTER TABLE `office_hours`
  MODIFY `OfficeHour_ID` int(11) NOT NULL AUTO_INCREMENT;

-- --------------------------------------------------------
-- AUTO_INCREMENT for table `users`
-- --------------------------------------------------------

ALTER TABLE `users`
  MODIFY `User_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

-- --------------------------------------------------------
-- Constraints for table `appointment_history`
-- --------------------------------------------------------

ALTER TABLE `appointment_history`
  ADD CONSTRAINT `fk_history_action_by` FOREIGN KEY (`Action_By`) REFERENCES `users` (`User_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_history_advisor` FOREIGN KEY (`Advisor_ID`) REFERENCES `users` (`User_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_history_appointment` FOREIGN KEY (`Appointment_ID`) REFERENCES `appointments` (`Appointment_ID`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_history_request` FOREIGN KEY (`Request_ID`) REFERENCES `appointment_requests` (`Request_ID`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_history_student` FOREIGN KEY (`Student_ID`) REFERENCES `users` (`User_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

-- --------------------------------------------------------
-- Constraints for table `appointments`
-- --------------------------------------------------------

ALTER TABLE `appointments`
  ADD CONSTRAINT `fk_appointments_advisor` FOREIGN KEY (`Advisor_ID`) REFERENCES `users` (`User_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_appointments_officehour` FOREIGN KEY (`OfficeHour_ID`) REFERENCES `office_hours` (`OfficeHour_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_appointments_request` FOREIGN KEY (`Request_ID`) REFERENCES `appointment_requests` (`Request_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_appointments_student` FOREIGN KEY (`Student_ID`) REFERENCES `users` (`User_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

-- --------------------------------------------------------
-- Constraints for table `appointment_requests`
-- --------------------------------------------------------

ALTER TABLE `appointment_requests`
  ADD CONSTRAINT `fk_appointment_requests_advisor` FOREIGN KEY (`Advisor_ID`) REFERENCES `users` (`User_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_appointment_requests_officehour` FOREIGN KEY (`OfficeHour_ID`) REFERENCES `office_hours` (`OfficeHour_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_appointment_requests_student` FOREIGN KEY (`Student_ID`) REFERENCES `users` (`User_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

-- --------------------------------------------------------
-- Constraints for table `communication_history`
-- --------------------------------------------------------

ALTER TABLE `communication_history`
  ADD CONSTRAINT `communication_history_ibfk_1` FOREIGN KEY (`Advisor_ID`) REFERENCES `users` (`User_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `communication_history_ibfk_2` FOREIGN KEY (`Student_ID`) REFERENCES `users` (`User_ID`) ON DELETE CASCADE;

-- --------------------------------------------------------
-- Constraints for table `degree`
-- --------------------------------------------------------

ALTER TABLE `degree`
  ADD CONSTRAINT `department_delete` FOREIGN KEY (`DepartmentID`) REFERENCES `departments` (`DepartmentID`) ON DELETE CASCADE ON UPDATE CASCADE;

-- --------------------------------------------------------
-- Constraints for table `office_hours`
-- --------------------------------------------------------

ALTER TABLE `office_hours`
  ADD CONSTRAINT `fk_officehours_advisor` FOREIGN KEY (`Advisor_ID`) REFERENCES `users` (`User_ID`) ON DELETE CASCADE;

-- --------------------------------------------------------
-- Constraints for table `students`
-- --------------------------------------------------------

ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`User_ID`) REFERENCES `users` (`User_ID`) ON DELETE CASCADE;

-- --------------------------------------------------------
-- Constraints for table `student_advisors`
-- --------------------------------------------------------

ALTER TABLE `student_advisors`
  ADD CONSTRAINT `student_advisors_ibfk_1` FOREIGN KEY (`Advisor_ID`) REFERENCES `users` (`External_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_advisors_ibfk_2` FOREIGN KEY (`Student_ID`) REFERENCES `users` (`External_ID`) ON DELETE CASCADE;

-- --------------------------------------------------------
-- Constraints for table `users`
-- --------------------------------------------------------

ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`Department_ID`) REFERENCES `degree` (`DegreeID`);

COMMIT;
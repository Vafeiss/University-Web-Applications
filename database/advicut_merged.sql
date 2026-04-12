SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE TABLE `departments` (
  `DepartmentID` int(11) NOT NULL AUTO_INCREMENT,
  `DepartmentName` varchar(100) NOT NULL,
  PRIMARY KEY (`DepartmentID`),
  UNIQUE KEY `DepartmentName` (`DepartmentName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AUTO_INCREMENT=7;

CREATE TABLE `degree` (
  `DegreeID` int(11) NOT NULL,
  `DepartmentID` int(11) NOT NULL,
  `DegreeName` varchar(200) NOT NULL,
  PRIMARY KEY (`DegreeID`),
  KEY `department_delete` (`DepartmentID`),
  CONSTRAINT `department_delete` FOREIGN KEY (`DepartmentID`) REFERENCES `departments` (`DepartmentID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `users` (
  `User_ID` int(11) NOT NULL AUTO_INCREMENT,
  `External_ID` int(11) DEFAULT NULL,
  `Uni_Email` varchar(150) NOT NULL,
  `Password` varchar(200) DEFAULT NULL,
  `Role` enum('Student','Advisor','Admin','SuperUser') NOT NULL,
  `First_name` varchar(50) NOT NULL,
  `Last_Name` varchar(50) NOT NULL,
  `Phone` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`User_ID`),
  UNIQUE KEY `Uni_Email` (`Uni_Email`),
  UNIQUE KEY `External_ID` (`External_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AUTO_INCREMENT=83;

CREATE TABLE `advisordepartment` (
  `User_ID` int(11) NOT NULL,
  `DepartmentID` int(11) NOT NULL,
  PRIMARY KEY (`User_ID`),
  KEY `DepartmentID` (`DepartmentID`),
  CONSTRAINT `advisordepartment_ibfk_1` FOREIGN KEY (`User_ID`) REFERENCES `users` (`User_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `advisordepartment_ibfk_2` FOREIGN KEY (`DepartmentID`) REFERENCES `departments` (`DepartmentID`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `students` (
  `User_ID` int(11) NOT NULL,
  `year` int(11) DEFAULT NULL,
  PRIMARY KEY (`User_ID`),
  CONSTRAINT `students_ibfk_1` FOREIGN KEY (`User_ID`) REFERENCES `users` (`User_ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `studentdegree` (
  `User_ID` int(11) NOT NULL,
  `DegreeID` int(11) NOT NULL,
  PRIMARY KEY (`User_ID`),
  KEY `DegreeID` (`DegreeID`),
  CONSTRAINT `studentdegree_ibfk_1` FOREIGN KEY (`User_ID`) REFERENCES `users` (`User_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `studentdegree_ibfk_2` FOREIGN KEY (`DegreeID`) REFERENCES `degree` (`DegreeID`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `student_advisors` (
  `Student_ID` int(11) NOT NULL,
  `Advisor_ID` int(11) NOT NULL,
  PRIMARY KEY (`Student_ID`),
  KEY `Advisor_ID` (`Advisor_ID`),
  CONSTRAINT `student_advisors_ibfk_1` FOREIGN KEY (`Advisor_ID`) REFERENCES `users` (`External_ID`) ON DELETE CASCADE,
  CONSTRAINT `student_advisors_ibfk_2` FOREIGN KEY (`Student_ID`) REFERENCES `users` (`External_ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `conversations` (
  `Conversation_ID` int(11) NOT NULL AUTO_INCREMENT,
  `Student_ID` int(11) NOT NULL,
  `Advisor_ID` int(11) NOT NULL,
  `Created_At` datetime DEFAULT current_timestamp(),
  `Updated_At` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`Conversation_ID`),
  KEY `Student_ID` (`Student_ID`),
  KEY `Advisor_ID` (`Advisor_ID`),
  CONSTRAINT `conversations_ibfk_1` FOREIGN KEY (`Student_ID`) REFERENCES `users` (`User_ID`),
  CONSTRAINT `conversations_ibfk_2` FOREIGN KEY (`Advisor_ID`) REFERENCES `users` (`User_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AUTO_INCREMENT=3;

CREATE TABLE `messages` (
  `Message_ID` int(11) NOT NULL AUTO_INCREMENT,
  `Conversation_ID` int(11) NOT NULL,
  `Sender_ID` int(11) NOT NULL,
  `Message_Text` text NOT NULL,
  `Sent_At` datetime DEFAULT current_timestamp(),
  `Is_Read` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`Message_ID`),
  KEY `Conversation_ID` (`Conversation_ID`),
  KEY `Sender_ID` (`Sender_ID`),
  CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`Conversation_ID`) REFERENCES `conversations` (`Conversation_ID`) ON DELETE CASCADE,
  CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`Sender_ID`) REFERENCES `users` (`User_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AUTO_INCREMENT=2;

CREATE TABLE `office_hours` (
  `OfficeHour_ID` int(11) NOT NULL AUTO_INCREMENT,
  `Advisor_ID` int(11) NOT NULL,
  `Day_of_Week` enum('Monday','Tuesday','Wednesday','Thursday','Friday') NOT NULL,
  `Start_Time` time NOT NULL,
  `End_Time` time NOT NULL,
  PRIMARY KEY (`OfficeHour_ID`),
  KEY `fk_officehours_advisor` (`Advisor_ID`),
  CONSTRAINT `fk_officehours_advisor` FOREIGN KEY (`Advisor_ID`) REFERENCES `users` (`User_ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AUTO_INCREMENT=18;

CREATE TABLE `appointment_requests` (
  `Request_ID` int(11) NOT NULL AUTO_INCREMENT,
  `Student_ID` int(11) NOT NULL,
  `Advisor_ID` int(11) NOT NULL,
  `OfficeHour_ID` int(11) NOT NULL,
  `Appointment_Date` date NOT NULL,
  `Student_Reason` text NOT NULL,
  `Advisor_Reason` text DEFAULT NULL,
  `Status` enum('Pending','Approved','Declined','Cancelled') NOT NULL DEFAULT 'Pending',
  `Created_At` timestamp NOT NULL DEFAULT current_timestamp(),
  `Updated_At` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`Request_ID`),
  KEY `fk_appointment_requests_student` (`Student_ID`),
  KEY `fk_appointment_requests_advisor` (`Advisor_ID`),
  KEY `fk_appointment_requests_officehour` (`OfficeHour_ID`),
  CONSTRAINT `fk_appointment_requests_advisor` FOREIGN KEY (`Advisor_ID`) REFERENCES `users` (`User_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_appointment_requests_officehour` FOREIGN KEY (`OfficeHour_ID`) REFERENCES `office_hours` (`OfficeHour_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_appointment_requests_student` FOREIGN KEY (`Student_ID`) REFERENCES `users` (`User_ID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AUTO_INCREMENT=7;

CREATE TABLE `appointments` (
  `Appointment_ID` int(11) NOT NULL AUTO_INCREMENT,
  `Request_ID` int(11) NOT NULL,
  `Student_ID` int(11) NOT NULL,
  `Advisor_ID` int(11) NOT NULL,
  `OfficeHour_ID` int(11) NOT NULL,
  `Appointment_Date` date NOT NULL,
  `Start_Time` time NOT NULL,
  `End_Time` time NOT NULL,
  `Status` enum('Scheduled','Completed','Cancelled') NOT NULL DEFAULT 'Scheduled',
  `Created_At` timestamp NOT NULL DEFAULT current_timestamp(),
  `Updated_At` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`Appointment_ID`),
  KEY `fk_appointments_request` (`Request_ID`),
  KEY `fk_appointments_student` (`Student_ID`),
  KEY `fk_appointments_advisor` (`Advisor_ID`),
  KEY `fk_appointments_officehour` (`OfficeHour_ID`),
  CONSTRAINT `fk_appointments_advisor` FOREIGN KEY (`Advisor_ID`) REFERENCES `users` (`User_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_appointments_officehour` FOREIGN KEY (`OfficeHour_ID`) REFERENCES `office_hours` (`OfficeHour_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_appointments_request` FOREIGN KEY (`Request_ID`) REFERENCES `appointment_requests` (`Request_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_appointments_student` FOREIGN KEY (`Student_ID`) REFERENCES `users` (`User_ID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AUTO_INCREMENT=2;

CREATE TABLE `appointment_history` (
  `History_ID` int(11) NOT NULL AUTO_INCREMENT,
  `Request_ID` int(11) DEFAULT NULL,
  `Appointment_ID` int(11) DEFAULT NULL,
  `Student_ID` int(11) NOT NULL,
  `Advisor_ID` int(11) NOT NULL,
  `Action_Type` enum('Requested','Approved','Declined','Cancelled','Completed') NOT NULL,
  `Action_Reason` text DEFAULT NULL,
  `Action_By` int(11) NOT NULL,
  `Created_At` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`History_ID`),
  KEY `fk_history_request` (`Request_ID`),
  KEY `fk_history_appointment` (`Appointment_ID`),
  KEY `fk_history_student` (`Student_ID`),
  KEY `fk_history_advisor` (`Advisor_ID`),
  KEY `fk_history_action_by` (`Action_By`),
  CONSTRAINT `fk_history_action_by` FOREIGN KEY (`Action_By`) REFERENCES `users` (`User_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_history_advisor` FOREIGN KEY (`Advisor_ID`) REFERENCES `users` (`User_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_history_appointment` FOREIGN KEY (`Appointment_ID`) REFERENCES `appointments` (`Appointment_ID`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_history_request` FOREIGN KEY (`Request_ID`) REFERENCES `appointment_requests` (`Request_ID`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_history_student` FOREIGN KEY (`Student_ID`) REFERENCES `users` (`User_ID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AUTO_INCREMENT=2;

CREATE TABLE `communication_history` (
  `Comm_ID` int(11) NOT NULL AUTO_INCREMENT,
  `Student_ID` int(11) NOT NULL,
  `Advisor_ID` int(11) NOT NULL,
  `Message_Content` text NOT NULL,
  `TimeStamp` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`Comm_ID`),
  KEY `fk_comm_student` (`Student_ID`),
  KEY `fk_comm_advisor` (`Advisor_ID`),
  CONSTRAINT `fk_comm_advisor` FOREIGN KEY (`Advisor_ID`) REFERENCES `users` (`User_ID`) ON DELETE CASCADE,
  CONSTRAINT `fk_comm_student` FOREIGN KEY (`Student_ID`) REFERENCES `users` (`User_ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AUTO_INCREMENT=8;

CREATE TABLE `promotion_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `promotion_year` int(11) NOT NULL,
  `executed_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `promotion_year` (`promotion_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AUTO_INCREMENT=2;

INSERT INTO `departments` (`DepartmentID`, `DepartmentName`) VALUES
(1, 'HMMHY'),
(6, 'Marketing');

INSERT INTO `degree` (`DegreeID`, `DepartmentID`, `DegreeName`) VALUES
(0, 6, 'Marketing'),
(1, 1, 'Computer Engineer & Informatics');

INSERT INTO `users` (`User_ID`, `External_ID`, `Uni_Email`, `Password`, `Role`, `First_name`, `Last_Name`, `Phone`) VALUES
(1, 1, 'admin@cut.ac.cy', '$2y$10$46bh2IXiYsStGwDSNr5zoernaZ7.ZYjHJqJeMtF4SXPlHRCvgzKoe', 'Admin', 'admin', 'admin', ''),
(3, 30080, 'test@cut.ac.cy', '$2a$12$KJG/4kJesdxexklmNSNvoexGO6iKvVIIIMYzyieNTv5H34CIKohIu', 'Advisor', 'test', 'test2', '97854623'),
(42, 24503, 'b@edu.cut.ac.cy', '$2y$10$DKWfoubG8oTfoKRlVhM9jev3nOUx7SGtYNJjAyUNaEmLadaOKOhwK', 'Student', 'andreas', 'Test2', NULL),
(43, 44556, 'test2@gmail.com', '$2y$10$46bh2IXiYsStGwDSNr5zoernaZ7.ZYjHJqJeMtF4SXPlHRCvgzKoe', 'Advisor', 'test2', 'test2', '96751099'),
(44, 23232, 'test3@gmail.com', '$2y$10$683alx37Nbd4Vho1Bi64IOZwiz6Iwmrj/N78ck5rfGJM1j0drBGAu', 'Advisor', 'test3ad', 'test3ad', '99786720'),
(55, 22222, 'a.kyriakou@edu.cut.ac.cy', '$2y$10$GnssS31HIi.YXoRLgCsqf.xV/f4EeC2KDL9SaZYj3BU42DHZW80Mi', 'Student', 'andreas', 'Kyriakoy', NULL),
(60, 2000, 'test4@cut.ac.cy', '$2y$10$OEn5rHQme53GuQa.Pnwi6u47whJ4ub/r0Eeo1d1X0yHOKE9csZEDC', 'Advisor', 'test4', 'test4k', '99875049'),
(67, 12234, 'student@edu.cut.ac.cy', '$2a$12$KJG/4kJesdxexklmNSNvoexGO6iKvVIIIMYzyieNTv5H34CIKohIu', 'Student', 'Student', 'Student3', NULL),
(69, 40546, 'pl@edu.cut.ac.cy', '$2y$10$FgDGU2MuS.XOqfeff3St.emL7VD0mFDsC8PdHp/y7Lh1X.LrOqA2.', 'Student', 'Polis', 'Polikarpou', NULL),
(73, 2, 'Superuser@cut.ac.cy', '$2y$10$46bh2IXiYsStGwDSNr5zoernaZ7.ZYjHJqJeMtF4SXPlHRCvgzKoe', 'SuperUser', 'SuperUser', 'SuperUser', NULL),
(74, 24305, 'test3@edu.cut.ac.cy', '$2y$10$OFEHcfpXdrreJXrUvq1hoekNIADpunbtgFTNUmVmLzNt08o1PlyIC', 'Student', 'Test3', 'Test3', NULL),
(75, 23609, 'test4@edu.cut.ac.cy', '$2y$10$kSA2gqmpRbWx68gLemvw8.cmW3VcAth6tgiD1J2eo6j8ZH7Etgud2', 'Student', 'Test4', 'Test4', NULL),
(76, 25678, 'test5@edu.cut.ac.cy', '$2y$10$MIdYKtwkgyjR88UpGtMzguukJcNlb5QFZAu41pO4CPOrHVNy6G.o6', 'Student', 'Test5', 'Test5', NULL),
(77, 27654, 'test6@edu.cut.ac.cy', '$2y$10$iy3Xnf4WGSIKT7bYb5bYOOaIZJdWsRqyidg6uSMeKyUNEDoyUdGo2', 'Student', 'Test6', 'Test6', NULL),
(78, 28765, 'test7@edu.cut.ac.cy', '$2y$10$aAuTzgt8txX.lDUytUQfZemOZqFCDQETTyKqVzR.YZ0BZg1MAJ9BK', 'Student', 'Test7', 'Test7', NULL),
(79, 23435, 'test8@edu.cut.ac.cy', '$2y$10$KIFQ06EDh0aYAnmKm7jdqOSu4Ty2SWib0BxKXQhRkHGauZReB3Cie', 'Student', 'Test8', 'Test8', NULL),
(80, 30965, 'ale@cut.ac.cy', '$2y$10$n/7uDLITBO/WlvwYAVh.DOcuaF6LAxHSnNwpAGAoNPp6VXBE.4D1W', 'Advisor', 'Ale', 'Alks', '34565478'),
(81, 63468, 'test9@edu.cut.ac.cy', '$2y$10$mf1OJ2BQDdvO/bPPJ1PEjOmDGkOuee3L0CoVnAkAVmxoUekkM1iBG', 'Student', 'Par', 'Vaf', NULL),
(82, 34567, 'paras@edu.cut.ac.cy', '$2y$10$Li/eJziLEaFA/6pyUiKA4OP9nXzW0z/YBmFR14UeB.2oBWh6Jf.lO', 'Student', 'Paraskevas', 'Vafeiadis', NULL);

INSERT INTO `advisordepartment` (`User_ID`, `DepartmentID`) VALUES
(3, 1),
(43, 1),
(44, 1),
(60, 1),
(80, 6);

INSERT INTO `students` (`User_ID`, `year`) VALUES
(42, 4),
(55, 5),
(67, 6),
(69, 2),
(74, 3),
(75, 2),
(76, 1),
(77, 4),
(78, 5),
(79, 3),
(81, 2),
(82, 3);

INSERT INTO `studentdegree` (`User_ID`, `DegreeID`) VALUES
(42, 1),
(55, 1),
(67, 1),
(69, 1),
(74, 1),
(75, 1),
(76, 1),
(77, 1),
(78, 1),
(79, 1),
(81, 1),
(82, 1);

INSERT INTO `student_advisors` (`Student_ID`, `Advisor_ID`) VALUES
(12234, 2000),
(23435, 2000),
(24305, 23232),
(24503, 30080),
(25678, 30080),
(28765, 30965),
(22222, 44556),
(23609, 44556),
(34567, 44556),
(40546, 44556);

INSERT INTO `conversations` (`Conversation_ID`, `Student_ID`, `Advisor_ID`, `Created_At`, `Updated_At`) VALUES
(1, 42, 3, '2026-04-09 20:28:11', '2026-04-09 20:28:11'),
(2, 76, 3, '2026-04-09 20:28:11', '2026-04-09 20:28:11');

INSERT INTO `messages` (`Message_ID`, `Conversation_ID`, `Sender_ID`, `Message_Text`, `Sent_At`, `Is_Read`) VALUES
(1, 1, 3, 'hello', '2026-04-10 00:03:42', 0);

INSERT INTO `password_resets` (`id`, `email`, `token`, `expires_at`, `used`, `created_at`) VALUES
(4, 'vafeiadisparaskevas@gmail.com', '9eb966a71c351595b9b5d55b486c54c8ce18bf9f48f68f695ba221f30575d510', '2026-04-06 17:52:36', 1, '2026-04-06 13:52:36'),
(5, 'admin@cut.ac.cy', '492219b93e9a7ee03395c4ca63aabf2aff729b2221e9494518958db615b9c2b0', '2026-04-08 18:28:47', 0, '2026-04-08 14:28:47'),
(7, 'pt.vafeiadis@edu.cut.ac.cy', '7fe151a657303c06497d6cf9506909a3b6d9809d3537a12d8ae48a5a4d599ec4', '2026-04-09 15:06:30', 1, '2026-04-09 11:06:30');

INSERT INTO `promotion_log` (`id`, `promotion_year`, `executed_at`) VALUES
(1, 2026, '2026-03-26 20:48:38');

COMMIT;

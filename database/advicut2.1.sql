SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `departments` (
  `DepartmentID` int(11) NOT NULL AUTO_INCREMENT,
  `DepartmentName` varchar(100) NOT NULL,
  PRIMARY KEY (`DepartmentID`),
  UNIQUE KEY `DepartmentName` (`DepartmentName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AUTO_INCREMENT=6;

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
  `Department_ID` int(11) DEFAULT NULL,
  PRIMARY KEY (`User_ID`),
  UNIQUE KEY `Uni_Email` (`Uni_Email`),
  UNIQUE KEY `External_ID` (`External_ID`),
  KEY `Department_ID` (`Department_ID`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`Department_ID`) REFERENCES `degree` (`DegreeID`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AUTO_INCREMENT=74;

CREATE TABLE `students` (
  `User_ID` int(11) NOT NULL,
  `year` int(11) DEFAULT NULL,
  PRIMARY KEY (`User_ID`),
  CONSTRAINT `students_ibfk_1` FOREIGN KEY (`User_ID`) REFERENCES `users` (`User_ID`) ON DELETE CASCADE
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `promotion_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `promotion_year` int(11) NOT NULL,
  `executed_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `promotion_year` (`promotion_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AUTO_INCREMENT=2;

INSERT INTO `departments` (`DepartmentID`, `DepartmentName`) VALUES
(1, 'HMMHY');

INSERT INTO `degree` (`DegreeID`, `DepartmentID`, `DegreeName`) VALUES
(1, 1, 'Computer Engineer & Informatics');

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

INSERT INTO `students` (`User_ID`, `year`) VALUES
(42, 4),
(55, 5),
(67, 6),
(69, 2);

INSERT INTO `student_advisors` (`Student_ID`, `Advisor_ID`) VALUES
(12234, 2000),
(24503, 30080),
(22222, 44556),
(40546, 44556);

INSERT INTO `promotion_log` (`id`, `promotion_year`, `executed_at`) VALUES
(1, 2026, '2026-03-26 20:48:38');

COMMIT;
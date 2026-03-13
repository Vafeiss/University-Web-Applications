SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE TABLE `appointment_history` (
  `Appointment_ID` int(11) NOT NULL,
  `Student_ID` int(11) NOT NULL,
  `Advisor_ID` int(11) NOT NULL,
  `Reason` text NOT NULL,
  `Appointment_Date` datetime NOT NULL,
  `Status` tinyint(1) NOT NULL,
  `Attendance` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `communication_history` (
  `Comm_ID` int(11) NOT NULL,
  `Student_ID` int(11) NOT NULL,
  `Advisor_ID` int(11) NOT NULL,
  `Message_Content` text NOT NULL,
  `TimeStamp` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `degree` (
  `DegreeID` int(11) NOT NULL,
  `Department_Name` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `degree` (`DegreeID`, `Department_Name`) VALUES
(0, 'None'),
(1, 'HMMHY');

CREATE TABLE `student_advisors` (
  `Student_ID` int(11) NOT NULL,
  `Advisor_ID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `student_advisors` (`Student_ID`, `Advisor_ID`) VALUES
(27407, 30080);

CREATE TABLE `users` (
  `User_ID` int(11) NOT NULL,
  `External_ID` int(11) DEFAULT NULL,
  `Uni_Email` varchar(150) NOT NULL,
  `Password` varchar(200) DEFAULT NULL,
  `Role` enum('Student','Advisor','Admin','SuperUser') NOT NULL,
  `First_name` varchar(50) NOT NULL,
  `Last_Name` varchar(50) NOT NULL,
  `Phone` varchar(20) DEFAULT NULL,
  `Department_ID` int(11) DEFAULT NULL,
  `Year` enum('First','Second','Third','Fourth','Fifth') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `users` (`User_ID`, `External_ID`, `Uni_Email`, `Password`, `Role`, `First_name`, `Last_Name`, `Phone`, `Department_ID`, `Year`) VALUES
(1, 1, 'admin@cut.ac.cy', '$2y$10$46bh2IXiYsStGwDSNr5zoernaZ7.ZYjHJqJeMtF4SXPlHRCvgzKoe', 'Admin', 'admin', 'admin', '', 0, ''),
(3, 30080, 'test@cut.ac.cy', '$2y$10$xGMjYptph0okQ8Xcsk1RjOX0Uvjkaz5Xj9yvAiN/cnovEI7mDm7Qi', 'Advisor', 'test', 'test2', '97854623', 1, NULL),
(12, 27407, 'pt.vafeiadis@edu.cut.ac.cy', '$2y$10$uzvkyxLDiO2A4OAcCxvslemzveVcg.tsw3VxKAaUfcRrhw/dfyxSO', 'Student', 'paraskevas', 'vafeiadis', NULL, 1, 'Third'),
(27, NULL, 'superuser@cut.ac.cy', '$2y$10$qV7TKVylCHp94RZ2JZp18Ow2OVVa8/QAPTv3jz3augluaWpchdfmy', 'SuperUser', 'SuperUser', 'SuperUser', NULL, 0, NULL),
(42, 24503, 'b@edu.cut.ac.cy', '$2y$10$DKWfoubG8oTfoKRlVhM9jev3nOUx7SGtYNJjAyUNaEmLadaOKOhwK', 'Student', 'b', 'Test2', NULL, 1, 'Third');

ALTER TABLE `appointment_history`
  ADD PRIMARY KEY (`Appointment_ID`),
  ADD KEY `fk_app_student` (`Student_ID`),
  ADD KEY `fk_app_advisor` (`Advisor_ID`);

ALTER TABLE `communication_history`
  ADD PRIMARY KEY (`Comm_ID`),
  ADD KEY `fk_comm_student` (`Student_ID`),
  ADD KEY `fk_comm_advisor` (`Advisor_ID`);

ALTER TABLE `degree`
  ADD PRIMARY KEY (`DegreeID`);

ALTER TABLE `student_advisors`
  ADD PRIMARY KEY (`Student_ID`),
  ADD KEY `fk_advisor` (`Advisor_ID`);

ALTER TABLE `users`
  ADD PRIMARY KEY (`User_ID`),
  ADD UNIQUE KEY `uniq_email` (`Uni_Email`),
  ADD UNIQUE KEY `uniq_external_id` (`External_ID`),
  ADD KEY `degreeFK` (`Department_ID`);

ALTER TABLE `appointment_history`
  MODIFY `Appointment_ID` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `communication_history`
  MODIFY `Comm_ID` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `users`
  MODIFY `User_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

ALTER TABLE `appointment_history`
  ADD CONSTRAINT `fk_app_advisor`
  FOREIGN KEY (`Advisor_ID`) REFERENCES `users` (`User_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_app_student`
  FOREIGN KEY (`Student_ID`) REFERENCES `users` (`User_ID`) ON DELETE CASCADE;

ALTER TABLE `communication_history`
  ADD CONSTRAINT `fk_comm_advisor`
  FOREIGN KEY (`Advisor_ID`) REFERENCES `users` (`User_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_comm_student`
  FOREIGN KEY (`Student_ID`) REFERENCES `users` (`User_ID`) ON DELETE CASCADE;

ALTER TABLE `student_advisors`
  ADD CONSTRAINT `fk_advisor`
  FOREIGN KEY (`Advisor_ID`) REFERENCES `users` (`External_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_student`
  FOREIGN KEY (`Student_ID`) REFERENCES `users` (`External_ID`) ON DELETE CASCADE;

ALTER TABLE `users`
  ADD CONSTRAINT `degreeFK`
  FOREIGN KEY (`Department_ID`) REFERENCES `degree` (`DegreeID`)
  ON DELETE NO ACTION ON UPDATE NO ACTION;

COMMIT;
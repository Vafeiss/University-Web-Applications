-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Εξυπηρετητής: 127.0.0.1
-- Χρόνος δημιουργίας: 05 Απρ 2026 στις 19:57:22
-- Έκδοση διακομιστή: 10.4.32-MariaDB
-- Έκδοση PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

--
-- 19-Apr-2026 v2.2
-- Added schema support for advisor additional one-off appointment slots
-- Panteleimoni Alexandrou
--


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Βάση δεδομένων: `advicut`
--

-- --------------------------------------------------------

--
-- Δομή πίνακα για τον πίνακα `appointments`
--

CREATE TABLE `appointments` (
  `Appointment_ID` int(11) NOT NULL,
  `Request_ID` int(11) NOT NULL,
  `Student_ID` int(11) NOT NULL,
  `Advisor_ID` int(11) NOT NULL,
  `OfficeHour_ID` int(11) DEFAULT NULL,
  `AdditionalSlot_ID` int(11) DEFAULT NULL,
  `Appointment_Date` date NOT NULL,
  `Start_Time` time NOT NULL,
  `End_Time` time NOT NULL,
  `Status` enum('Scheduled','Completed','Cancelled') NOT NULL DEFAULT 'Scheduled',
  `Created_At` timestamp NOT NULL DEFAULT current_timestamp(),
  `Updated_At` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Άδειασμα δεδομένων του πίνακα `appointments`
--

INSERT INTO `appointments` (`Appointment_ID`, `Request_ID`, `Student_ID`, `Advisor_ID`, `OfficeHour_ID`, `Appointment_Date`, `Start_Time`, `End_Time`, `Status`, `Created_At`, `Updated_At`) VALUES
(1, 6, 1, 2, 16, '2026-03-30', '12:38:00', '13:38:00', 'Scheduled', '2026-03-30 17:02:08', '2026-03-30 17:02:08');

-- --------------------------------------------------------

--
-- Δομή πίνακα για τον πίνακα `appointment_history`
--

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

--
-- Άδειασμα δεδομένων του πίνακα `appointment_history`
--

INSERT INTO `appointment_history` (`History_ID`, `Request_ID`, `Appointment_ID`, `Student_ID`, `Advisor_ID`, `Action_Type`, `Action_Reason`, `Action_By`, `Created_At`) VALUES
(1, 6, 1, 1, 2, 'Approved', NULL, 2, '2026-03-30 17:02:08');

-- --------------------------------------------------------

--
-- Δομή πίνακα για τον πίνακα `appointment_requests`
--

CREATE TABLE `appointment_requests` (
  `Request_ID` int(11) NOT NULL,
  `Student_ID` int(11) NOT NULL,
  `Advisor_ID` int(11) NOT NULL,
  `OfficeHour_ID` int(11) DEFAULT NULL,
  `AdditionalSlot_ID` int(11) DEFAULT NULL,
  `Appointment_Date` date NOT NULL,
  `Student_Reason` text NOT NULL,
  `Advisor_Reason` text DEFAULT NULL,
  `Status` enum('Pending','Approved','Declined','Cancelled') NOT NULL DEFAULT 'Pending',
  `Created_At` timestamp NOT NULL DEFAULT current_timestamp(),
  `Updated_At` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Άδειασμα δεδομένων του πίνακα `appointment_requests`
--

INSERT INTO `appointment_requests` (`Request_ID`, `Student_ID`, `Advisor_ID`, `OfficeHour_ID`, `Appointment_Date`, `Student_Reason`, `Advisor_Reason`, `Status`, `Created_At`, `Updated_At`) VALUES
(3, 1, 2, 12, '2026-03-26', 'οην§εγοε§ξγοςα', 'ξβξΩΒΞΩΝΞΣ', 'Declined', '2026-03-26 11:19:02', '2026-03-26 11:19:10'),
(4, 1, 2, 12, '2026-04-02', 'νψνςκνψ', 'δεν μπορω τελικα', 'Declined', '2026-03-27 20:04:47', '2026-03-27 20:54:25'),
(5, 1, 2, 12, '2026-04-02', 'ισδωνσεδ', 'ιοζηγ¦\'[ΠΑΕ§ΗΟΣΘ09ΤΠΡΕ', 'Declined', '2026-03-30 08:37:37', '2026-03-30 08:37:45'),
(6, 1, 2, 16, '2026-03-30', 'bjb', NULL, 'Approved', '2026-03-30 17:01:54', '2026-03-30 17:02:08');

-- --------------------------------------------------------

--
-- Δομή πίνακα για τον πίνακα `communication_history`
--

CREATE TABLE `communication_history` (
  `Comm_ID` int(11) NOT NULL,
  `Student_ID` int(11) NOT NULL,
  `Advisor_ID` int(11) NOT NULL,
  `Message_Content` text NOT NULL,
  `TimeStamp` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Δομή πίνακα για τον πίνακα `office_hours`
--

CREATE TABLE `office_hours` (
  `OfficeHour_ID` int(11) NOT NULL,
  `Advisor_ID` int(11) NOT NULL,
  `Day_of_Week` enum('Monday','Tuesday','Wednesday','Thursday','Friday') NOT NULL,
  `Start_Time` time NOT NULL,
  `End_Time` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Άδειασμα δεδομένων του πίνακα `office_hours`
--

INSERT INTO `office_hours` (`OfficeHour_ID`, `Advisor_ID`, `Day_of_Week`, `Start_Time`, `End_Time`) VALUES
(1, 1, 'Monday', '10:00:00', '10:30:00'),
(12, 2, 'Thursday', '09:10:00', '10:10:00'),
(13, 2, 'Thursday', '15:51:00', '16:51:00'),
(14, 2, 'Friday', '07:06:00', '08:06:00'),
(15, 2, 'Tuesday', '02:07:00', '03:07:00'),
(16, 2, 'Monday', '12:38:00', '13:38:00');

-- --------------------------------------------------------

--
-- Δομή πίνακα για τον πίνακα `student_advisors`
--

--
-- Î”Î¿Î¼Î® Ï€Î¯Î½Î±ÎºÎ± Î³Î¹Î± Ï„Î¿Î½ Ï€Î¯Î½Î±ÎºÎ± `advisor_additional_slots`
--

CREATE TABLE `advisor_additional_slots` (
  `AdditionalSlot_ID` int(11) NOT NULL,
  `Advisor_ID` int(11) NOT NULL,
  `Slot_Date` date NOT NULL,
  `Start_Time` time NOT NULL,
  `End_Time` time NOT NULL,
  `Is_Active` tinyint(1) NOT NULL DEFAULT 1,
  `Created_At` timestamp NOT NULL DEFAULT current_timestamp(),
  `Updated_At` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Î”Î¿Î¼Î® Ï€Î¯Î½Î±ÎºÎ± Î³Î¹Î± Ï„Î¿Î½ Ï€Î¯Î½Î±ÎºÎ± `student_advisors`
--

CREATE TABLE `student_advisors` (
  `Student_ID` int(11) NOT NULL,
  `Advisor_ID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Άδειασμα δεδομένων του πίνακα `student_advisors`
--

INSERT INTO `student_advisors` (`Student_ID`, `Advisor_ID`) VALUES
(1, 2),
(4, 2),
(27407, 30080);

-- --------------------------------------------------------

--
-- Δομή πίνακα για τον πίνακα `users`
--

CREATE TABLE `users` (
  `User_ID` int(11) NOT NULL,
  `External_ID` int(11) NOT NULL,
  `Uni_Email` varchar(150) NOT NULL,
  `Password` varchar(200) DEFAULT NULL,
  `Role` enum('Student','Advisor','Admin','SuperUser') NOT NULL,
  `First_name` varchar(50) NOT NULL,
  `Last_Name` varchar(50) NOT NULL,
  `Phone` varchar(20) DEFAULT NULL,
  `Department_Name` varchar(100) DEFAULT NULL,
  `Year` enum('First','Second','Third','Fourth','Fifth') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Άδειασμα δεδομένων του πίνακα `users`
--

INSERT INTO `users` (`User_ID`, `External_ID`, `Uni_Email`, `Password`, `Role`, `First_name`, `Last_Name`, `Phone`, `Department_Name`, `Year`) VALUES
(1, 1, 'admin@cut.ac.cy', '$2y$10$46bh2IXiYsStGwDSNr5zoernaZ7.ZYjHJqJeMtF4SXPlHRCvgzKoe', 'Admin', 'admin', 'admin', '', '', ''),
(2, 30080, 'advisor@cut.ac.cy', '1234', 'Advisor', 'Test', 'Advisor', NULL, NULL, NULL),
(4, 27407, 'student@cut.ac.cy', '1234', 'Student', '', '', NULL, NULL, NULL);

--
-- Ευρετήρια για άχρηστους πίνακες
--

--
-- Ευρετήρια για πίνακα `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`Appointment_ID`),
  ADD KEY `fk_appointments_request` (`Request_ID`),
  ADD KEY `fk_appointments_student` (`Student_ID`),
  ADD KEY `fk_appointments_advisor` (`Advisor_ID`),
  ADD KEY `fk_appointments_officehour` (`OfficeHour_ID`),
  ADD KEY `fk_appointments_additional_slot` (`AdditionalSlot_ID`);

--
-- Ευρετήρια για πίνακα `appointment_history`
--
ALTER TABLE `appointment_history`
  ADD PRIMARY KEY (`History_ID`),
  ADD KEY `fk_history_request` (`Request_ID`),
  ADD KEY `fk_history_appointment` (`Appointment_ID`),
  ADD KEY `fk_history_student` (`Student_ID`),
  ADD KEY `fk_history_advisor` (`Advisor_ID`),
  ADD KEY `fk_history_action_by` (`Action_By`);

--
-- Ευρετήρια για πίνακα `appointment_requests`
--
ALTER TABLE `appointment_requests`
  ADD PRIMARY KEY (`Request_ID`),
  ADD KEY `fk_appointment_requests_student` (`Student_ID`),
  ADD KEY `fk_appointment_requests_advisor` (`Advisor_ID`),
  ADD KEY `fk_appointment_requests_officehour` (`OfficeHour_ID`),
  ADD KEY `fk_appointment_requests_additional_slot` (`AdditionalSlot_ID`);

--
-- Î•Ï…ÏÎµÏ„Î®ÏÎ¹Î± Î³Î¹Î± Ï€Î¯Î½Î±ÎºÎ± `advisor_additional_slots`
--
ALTER TABLE `advisor_additional_slots`
  ADD PRIMARY KEY (`AdditionalSlot_ID`),
  ADD KEY `fk_additional_slots_advisor` (`Advisor_ID`);

--
-- Ευρετήρια για πίνακα `communication_history`
--
ALTER TABLE `communication_history`
  ADD PRIMARY KEY (`Comm_ID`),
  ADD KEY `fk_comm_student` (`Student_ID`),
  ADD KEY `fk_comm_advisor` (`Advisor_ID`);

--
-- Ευρετήρια για πίνακα `office_hours`
--
ALTER TABLE `office_hours`
  ADD PRIMARY KEY (`OfficeHour_ID`),
  ADD KEY `fk_officehours_advisor` (`Advisor_ID`);

--
-- Ευρετήρια για πίνακα `student_advisors`
--
ALTER TABLE `student_advisors`
  ADD PRIMARY KEY (`Student_ID`);

--
-- Ευρετήρια για πίνακα `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`User_ID`),
  ADD UNIQUE KEY `uniq_email` (`Uni_Email`),
  ADD UNIQUE KEY `uniq_external_id` (`External_ID`);

--
-- AUTO_INCREMENT για άχρηστους πίνακες
--

--
-- AUTO_INCREMENT για πίνακα `appointments`
--
ALTER TABLE `appointments`
  MODIFY `Appointment_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT για πίνακα `appointment_history`
--
ALTER TABLE `appointment_history`
  MODIFY `History_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT για πίνακα `appointment_requests`
--
ALTER TABLE `appointment_requests`
  MODIFY `Request_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT για πίνακα `communication_history`
--
ALTER TABLE `communication_history`
  MODIFY `Comm_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT για πίνακα `office_hours`
--
ALTER TABLE `office_hours`
  MODIFY `OfficeHour_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT για πίνακα `users`
--
ALTER TABLE `users`
  MODIFY `User_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Περιορισμοί για άχρηστους πίνακες
--

--
-- Περιορισμοί για πίνακα `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `fk_appointments_advisor` FOREIGN KEY (`Advisor_ID`) REFERENCES `users` (`User_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_appointments_officehour` FOREIGN KEY (`OfficeHour_ID`) REFERENCES `office_hours` (`OfficeHour_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_appointments_request` FOREIGN KEY (`Request_ID`) REFERENCES `appointment_requests` (`Request_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_appointments_student` FOREIGN KEY (`Student_ID`) REFERENCES `users` (`User_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Περιορισμοί για πίνακα `appointment_history`
--
ALTER TABLE `appointment_history`
  ADD CONSTRAINT `fk_history_action_by` FOREIGN KEY (`Action_By`) REFERENCES `users` (`User_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_history_advisor` FOREIGN KEY (`Advisor_ID`) REFERENCES `users` (`User_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_history_appointment` FOREIGN KEY (`Appointment_ID`) REFERENCES `appointments` (`Appointment_ID`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_history_request` FOREIGN KEY (`Request_ID`) REFERENCES `appointment_requests` (`Request_ID`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_history_student` FOREIGN KEY (`Student_ID`) REFERENCES `users` (`User_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Περιορισμοί για πίνακα `appointment_requests`
--
ALTER TABLE `appointment_requests`
  ADD CONSTRAINT `fk_appointment_requests_advisor` FOREIGN KEY (`Advisor_ID`) REFERENCES `users` (`User_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_appointment_requests_additional_slot` FOREIGN KEY (`AdditionalSlot_ID`) REFERENCES `advisor_additional_slots` (`AdditionalSlot_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_appointment_requests_officehour` FOREIGN KEY (`OfficeHour_ID`) REFERENCES `office_hours` (`OfficeHour_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_appointment_requests_student` FOREIGN KEY (`Student_ID`) REFERENCES `users` (`User_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Περιορισμοί για πίνακα `communication_history`
--
ALTER TABLE `communication_history`
  ADD CONSTRAINT `fk_comm_advisor` FOREIGN KEY (`Advisor_ID`) REFERENCES `users` (`User_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_comm_student` FOREIGN KEY (`Student_ID`) REFERENCES `users` (`User_ID`) ON DELETE CASCADE;

--
-- Περιορισμοί για πίνακα `office_hours`
--
ALTER TABLE `office_hours`
  ADD CONSTRAINT `fk_officehours_advisor` FOREIGN KEY (`Advisor_ID`) REFERENCES `users` (`User_ID`) ON DELETE CASCADE;

--
-- Î ÎµÏÎ¹Î¿ÏÎ¹ÏƒÎ¼Î¿Î¯ Î³Î¹Î± Ï€Î¯Î½Î±ÎºÎ± `advisor_additional_slots`
--
ALTER TABLE `advisor_additional_slots`
  ADD CONSTRAINT `fk_additional_slots_advisor` FOREIGN KEY (`Advisor_ID`) REFERENCES `users` (`User_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Î ÎµÏÎ¹Î¿ÏÎ¹ÏƒÎ¼Î¿Î¯ Î³Î¹Î± Ï€Î¯Î½Î±ÎºÎ± `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `fk_appointments_additional_slot` FOREIGN KEY (`AdditionalSlot_ID`) REFERENCES `advisor_additional_slots` (`AdditionalSlot_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- AUTO_INCREMENT Î³Î¹Î± Ï€Î¯Î½Î±ÎºÎ± `advisor_additional_slots`
--
ALTER TABLE `advisor_additional_slots`
  MODIFY `AdditionalSlot_ID` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

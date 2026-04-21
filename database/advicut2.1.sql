CREATE DATABASE IF NOT EXISTS advicut
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

USE advicut;

SET time_zone = '+00:00';

CREATE TABLE `departments` (
  `DepartmentID` int(11) NOT NULL AUTO_INCREMENT,
  `DepartmentName` varchar(100) NOT NULL,
  PRIMARY KEY (`DepartmentID`),
  UNIQUE KEY `uk_departments_name` (`DepartmentName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `degree` (
  `DegreeID` int(11) NOT NULL AUTO_INCREMENT,
  `DepartmentID` int(11) NOT NULL,
  `DegreeName` varchar(200) NOT NULL,
  PRIMARY KEY (`DegreeID`),
  KEY `idx_degree_departmentid` (`DepartmentID`),
  CONSTRAINT `fk_degree_department`
    FOREIGN KEY (`DepartmentID`)
    REFERENCES `departments` (`DepartmentID`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
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
  UNIQUE KEY `uk_users_email` (`Uni_Email`),
  UNIQUE KEY `uk_users_external_id` (`External_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `advisordepartment` (
  `User_ID` int(11) NOT NULL,
  `DepartmentID` int(11) NOT NULL,
  PRIMARY KEY (`User_ID`),
  KEY `idx_advisordepartment_departmentid` (`DepartmentID`),
  CONSTRAINT `fk_advisordepartment_user`
    FOREIGN KEY (`User_ID`)
    REFERENCES `users` (`User_ID`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_advisordepartment_department`
    FOREIGN KEY (`DepartmentID`)
    REFERENCES `departments` (`DepartmentID`)
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `students` (
  `User_ID` int(11) NOT NULL,
  `year` int(11) DEFAULT NULL,
  PRIMARY KEY (`User_ID`),
  CONSTRAINT `fk_students_user`
    FOREIGN KEY (`User_ID`)
    REFERENCES `users` (`User_ID`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `studentdegree` (
  `User_ID` int(11) NOT NULL,
  `DegreeID` int(11) NOT NULL,
  PRIMARY KEY (`User_ID`),
  KEY `idx_studentdegree_degreeid` (`DegreeID`),
  CONSTRAINT `fk_studentdegree_user`
    FOREIGN KEY (`User_ID`)
    REFERENCES `users` (`User_ID`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_studentdegree_degree`
    FOREIGN KEY (`DegreeID`)
    REFERENCES `degree` (`DegreeID`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `student_advisors` (
  `Student_ID` int(11) NOT NULL,
  `Advisor_ID` int(11) NOT NULL,
  PRIMARY KEY (`Student_ID`),
  KEY `idx_student_advisors_advisor_id` (`Advisor_ID`),
  CONSTRAINT `fk_student_advisors_student`
    FOREIGN KEY (`Student_ID`)
    REFERENCES `users` (`External_ID`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_student_advisors_advisor`
    FOREIGN KEY (`Advisor_ID`)
    REFERENCES `users` (`External_ID`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `conversations` (
  `Conversation_ID` int(11) NOT NULL AUTO_INCREMENT,
  `Student_ID` int(11) NOT NULL,
  `Advisor_ID` int(11) NOT NULL,
  `Created_At` datetime DEFAULT current_timestamp(),
  `Updated_At` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`Conversation_ID`),
  KEY `idx_conversations_student_id` (`Student_ID`),
  KEY `idx_conversations_advisor_id` (`Advisor_ID`),
  CONSTRAINT `fk_conversations_student`
    FOREIGN KEY (`Student_ID`)
    REFERENCES `users` (`User_ID`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_conversations_advisor`
    FOREIGN KEY (`Advisor_ID`)
    REFERENCES `users` (`User_ID`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `messages` (
  `Message_ID` int(11) NOT NULL AUTO_INCREMENT,
  `Conversation_ID` int(11) NOT NULL,
  `Sender_ID` int(11) NOT NULL,
  `Message_Text` text NOT NULL,
  `Sent_At` datetime DEFAULT current_timestamp(),
  `Is_Read` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`Message_ID`),
  KEY `idx_messages_conversation_id` (`Conversation_ID`),
  KEY `idx_messages_sender_id` (`Sender_ID`),
  CONSTRAINT `fk_messages_conversation`
    FOREIGN KEY (`Conversation_ID`)
    REFERENCES `conversations` (`Conversation_ID`)
    ON DELETE CASCADE,
  CONSTRAINT `fk_messages_sender`
    FOREIGN KEY (`Sender_ID`)
    REFERENCES `users` (`User_ID`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `office_hours` (
  `OfficeHour_ID` int(11) NOT NULL AUTO_INCREMENT,
  `Advisor_ID` int(11) NOT NULL,
  `Day_of_Week` enum('Monday','Tuesday','Wednesday','Thursday','Friday') NOT NULL,
  `Start_Time` time NOT NULL,
  `End_Time` time NOT NULL,
  PRIMARY KEY (`OfficeHour_ID`),
  KEY `idx_office_hours_advisor_id` (`Advisor_ID`),
  CONSTRAINT `fk_office_hours_advisor`
    FOREIGN KEY (`Advisor_ID`)
    REFERENCES `users` (`User_ID`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `advisor_additional_slots` (
  `AdditionalSlot_ID` int(11) NOT NULL AUTO_INCREMENT,
  `Advisor_ID` int(11) NOT NULL,
  `Slot_Date` date NOT NULL,
  `Start_Time` time NOT NULL,
  `End_Time` time NOT NULL,
  `Is_Active` tinyint(1) NOT NULL DEFAULT 1,
  `Created_At` timestamp NOT NULL DEFAULT current_timestamp(),
  `Updated_At` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`AdditionalSlot_ID`),
  KEY `idx_additional_slots_advisor_id` (`Advisor_ID`),
  CONSTRAINT `fk_additional_slots_advisor`
    FOREIGN KEY (`Advisor_ID`)
    REFERENCES `users` (`User_ID`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `appointment_requests` (
  `Request_ID` int(11) NOT NULL AUTO_INCREMENT,
  `Student_ID` int(11) NOT NULL,
  `Advisor_ID` int(11) NOT NULL,
  `OfficeHour_ID` int(11) DEFAULT NULL,
  `AdditionalSlot_ID` int(11) DEFAULT NULL,
  `Appointment_Date` date NOT NULL,
  `Student_Reason` text NOT NULL,
  `Advisor_Reason` text DEFAULT NULL,
  `Status` enum('Pending','Approved','Declined','Cancelled') NOT NULL DEFAULT 'Pending',
  `Created_At` timestamp NOT NULL DEFAULT current_timestamp(),
  `Updated_At` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`Request_ID`),
  KEY `idx_appointment_requests_student_id` (`Student_ID`),
  KEY `idx_appointment_requests_advisor_id` (`Advisor_ID`),
  KEY `idx_appointment_requests_officehour_id` (`OfficeHour_ID`),
  KEY `idx_appointment_requests_additional_slot_id` (`AdditionalSlot_ID`),
  CONSTRAINT `fk_appointment_requests_student`
    FOREIGN KEY (`Student_ID`)
    REFERENCES `users` (`User_ID`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_appointment_requests_advisor`
    FOREIGN KEY (`Advisor_ID`)
    REFERENCES `users` (`User_ID`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_appointment_requests_officehour`
    FOREIGN KEY (`OfficeHour_ID`)
    REFERENCES `office_hours` (`OfficeHour_ID`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_appointment_requests_additional_slot`
    FOREIGN KEY (`AdditionalSlot_ID`)
    REFERENCES `advisor_additional_slots` (`AdditionalSlot_ID`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `appointments` (
  `Appointment_ID` int(11) NOT NULL AUTO_INCREMENT,
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
  `Updated_At` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`Appointment_ID`),
  KEY `idx_appointments_request_id` (`Request_ID`),
  KEY `idx_appointments_student_id` (`Student_ID`),
  KEY `idx_appointments_advisor_id` (`Advisor_ID`),
  KEY `idx_appointments_officehour_id` (`OfficeHour_ID`),
  KEY `idx_appointments_additional_slot_id` (`AdditionalSlot_ID`),
  CONSTRAINT `fk_appointments_request`
    FOREIGN KEY (`Request_ID`)
    REFERENCES `appointment_requests` (`Request_ID`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_appointments_student`
    FOREIGN KEY (`Student_ID`)
    REFERENCES `users` (`User_ID`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_appointments_advisor`
    FOREIGN KEY (`Advisor_ID`)
    REFERENCES `users` (`User_ID`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_appointments_officehour`
    FOREIGN KEY (`OfficeHour_ID`)
    REFERENCES `office_hours` (`OfficeHour_ID`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_appointments_additional_slot`
    FOREIGN KEY (`AdditionalSlot_ID`)
    REFERENCES `advisor_additional_slots` (`AdditionalSlot_ID`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  KEY `idx_appointment_history_request_id` (`Request_ID`),
  KEY `idx_appointment_history_appointment_id` (`Appointment_ID`),
  KEY `idx_appointment_history_student_id` (`Student_ID`),
  KEY `idx_appointment_history_advisor_id` (`Advisor_ID`),
  KEY `idx_appointment_history_action_by` (`Action_By`),
  CONSTRAINT `fk_appointment_history_request`
    FOREIGN KEY (`Request_ID`)
    REFERENCES `appointment_requests` (`Request_ID`)
    ON DELETE SET NULL
    ON UPDATE CASCADE,
  CONSTRAINT `fk_appointment_history_appointment`
    FOREIGN KEY (`Appointment_ID`)
    REFERENCES `appointments` (`Appointment_ID`)
    ON DELETE SET NULL
    ON UPDATE CASCADE,
  CONSTRAINT `fk_appointment_history_student`
    FOREIGN KEY (`Student_ID`)
    REFERENCES `users` (`User_ID`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_appointment_history_advisor`
    FOREIGN KEY (`Advisor_ID`)
    REFERENCES `users` (`User_ID`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_appointment_history_action_by`
    FOREIGN KEY (`Action_By`)
    REFERENCES `users` (`User_ID`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `communication_history` (
  `Comm_ID` int(11) NOT NULL AUTO_INCREMENT,
  `Student_ID` int(11) NOT NULL,
  `Advisor_ID` int(11) NOT NULL,
  `Message_Content` text NOT NULL,
  `TimeStamp` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`Comm_ID`),
  KEY `idx_communication_history_student_id` (`Student_ID`),
  KEY `idx_communication_history_advisor_id` (`Advisor_ID`),
  CONSTRAINT `fk_communication_history_student`
    FOREIGN KEY (`Student_ID`)
    REFERENCES `users` (`User_ID`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_communication_history_advisor`
    FOREIGN KEY (`Advisor_ID`)
    REFERENCES `users` (`User_ID`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `promotion_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `promotion_year` int(11) NOT NULL,
  `executed_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_promotion_log_year` (`promotion_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `notifications` (
`Notification_ID` int(11) NOT NULL AUTO_INCREMENT,
`Recipient_ID` int(11) NOT NULL,
`Sender_ID` int(11) DEFAULT NULL,
`Type` enum(
'appointment_requested',
'appointment_approved',
'appointment_declined',
'appointment_cancelled',
'appointment_completed',
'office_hours_changed',
'new_message'
) NOT NULL,
`Title` varchar(255) NOT NULL,
`Notification_Message` text NOT NULL,
`Related_Request_ID` int(11) DEFAULT NULL,
`Related_Appointment_ID` int(11) DEFAULT NULL,
`Related_Conversation_ID` int(11) DEFAULT NULL,
`Is_Read` tinyint(1) NOT NULL DEFAULT 0,
`Created_At` timestamp NOT NULL DEFAULT current_timestamp(),
`Read_At` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`Notification_ID`),
KEY `idx_notifications_recipient` (`Recipient_ID`),
KEY `idx_notifications_sender` (`Sender_ID`),
KEY `idx_notifications_request` (`Related_Request_ID`),
KEY `idx_notifications_appointment` (`Related_Appointment_ID`),
KEY `idx_notifications_conversation` (`Related_Conversation_ID`),
KEY `idx_notifications_recipient_read` (`Recipient_ID`, `Is_Read`, `Created_At`),
CONSTRAINT `fk_notifications_recipient`
FOREIGN KEY (`Recipient_ID`) REFERENCES `users` (`User_ID`)
ON DELETE CASCADE ON UPDATE CASCADE,
CONSTRAINT `fk_notifications_sender`
FOREIGN KEY (`Sender_ID`) REFERENCES `users` (`User_ID`)
ON DELETE SET NULL ON UPDATE CASCADE,
CONSTRAINT `fk_notifications_request`
FOREIGN KEY (`Related_Request_ID`) REFERENCES `appointment_requests` (`Request_ID`)
ON DELETE SET NULL ON UPDATE CASCADE,
CONSTRAINT `fk_notifications_appointment`
FOREIGN KEY (`Related_Appointment_ID`) REFERENCES `appointments` (`Appointment_ID`)
ON DELETE SET NULL ON UPDATE CASCADE,
CONSTRAINT `fk_notifications_conversation`
FOREIGN KEY (`Related_Conversation_ID`) REFERENCES `conversations` (`Conversation_ID`)
ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
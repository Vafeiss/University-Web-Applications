--
-- 19-Apr-2026 v1.0
-- Added schema support for advisor additional one-off appointment slots
-- Panteleimoni Alexandrou
--

START TRANSACTION;

CREATE TABLE IF NOT EXISTS `advisor_additional_slots` (
  `AdditionalSlot_ID` int(11) NOT NULL AUTO_INCREMENT,
  `Advisor_ID` int(11) NOT NULL,
  `Slot_Date` date NOT NULL,
  `Start_Time` time NOT NULL,
  `End_Time` time NOT NULL,
  `Is_Active` tinyint(1) NOT NULL DEFAULT 1,
  `Created_At` timestamp NOT NULL DEFAULT current_timestamp(),
  `Updated_At` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`AdditionalSlot_ID`),
  KEY `fk_additional_slots_advisor` (`Advisor_ID`),
  CONSTRAINT `fk_additional_slots_advisor`
    FOREIGN KEY (`Advisor_ID`) REFERENCES `users` (`User_ID`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `appointment_requests`
  MODIFY COLUMN `OfficeHour_ID` int(11) NULL,
  ADD COLUMN `AdditionalSlot_ID` int(11) NULL AFTER `OfficeHour_ID`,
  ADD KEY `fk_appointment_requests_additional_slot` (`AdditionalSlot_ID`),
  ADD CONSTRAINT `fk_appointment_requests_additional_slot`
    FOREIGN KEY (`AdditionalSlot_ID`) REFERENCES `advisor_additional_slots` (`AdditionalSlot_ID`)
    ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `appointments`
  MODIFY COLUMN `OfficeHour_ID` int(11) NULL,
  ADD COLUMN `AdditionalSlot_ID` int(11) NULL AFTER `OfficeHour_ID`,
  ADD KEY `fk_appointments_additional_slot` (`AdditionalSlot_ID`),
  ADD CONSTRAINT `fk_appointments_additional_slot`
    FOREIGN KEY (`AdditionalSlot_ID`) REFERENCES `advisor_additional_slots` (`AdditionalSlot_ID`)
    ON DELETE CASCADE ON UPDATE CASCADE;

COMMIT;

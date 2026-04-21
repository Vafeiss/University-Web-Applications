

INSERT INTO `departments` (`DepartmentID`, `DepartmentName`) VALUES
(1, 'HMMHY'),
(6, 'Marketing');

INSERT INTO `degree` (`DegreeID`, `DepartmentID`, `DegreeName`) VALUES
(1, 1, 'Computer Engineer & Informatics'),
(2, 6, 'Marketing');

INSERT INTO `users` (`User_ID`, `External_ID`, `Uni_Email`, `Password`, `Role`, `First_name`, `Last_Name`, `Phone`) VALUES
(1, 1, 'admin1@cut.ac.cy', '$2y$10$46bh2IXiYsStGwDSNr5zoernaZ7.ZYjHJqJeMtF4SXPlHRCvgzKoe', 'Admin', 'Admin1', 'Admin1', ''),
(3, 30080, 'advisor1@cut.ac.cy', '$2a$12$KJG/4kJesdxexklmNSNvoexGO6iKvVIIIMYzyieNTv5H34CIKohIu', 'Advisor', 'Advisor1', 'Advisor1', '97854623'),
(42, 24503, 'student1@edu.cut.ac.cy', '$2y$10$DKWfoubG8oTfoKRlVhM9jev3nOUx7SGtYNJjAyUNaEmLadaOKOhwK', 'Student', 'Student1', 'Student1', NULL),
(43, 44556, 'advisor2@cut.ac.cy', '$2y$10$46bh2IXiYsStGwDSNr5zoernaZ7.ZYjHJqJeMtF4SXPlHRCvgzKoe', 'Advisor', 'Advisor2', 'Advisor2', '96751099'),
(44, 23232, 'advisor3@cut.ac.cy', '$2y$10$683alx37Nbd4Vho1Bi64IOZwiz6Iwmrj/N78ck5rfGJM1j0drBGAu', 'Advisor', 'Advisor3', 'Advisor3', '99786720'),
(55, 22222, 'student2@edu.cut.ac.cy', '$2y$10$GnssS31HIi.YXoRLgCsqf.xV/f4EeC2KDL9SaZYj3BU42DHZW80Mi', 'Student', 'Student2', 'Student2', NULL),
(60, 2000, 'advisor4@cut.ac.cy', '$2y$10$OEn5rHQme53GuQa.Pnwi6u47whJ4ub/r0Eeo1d1X0yHOKE9csZEDC', 'Advisor', 'Advisor4', 'Advisor4', '99875049'),
(67, 12234, 'student3@edu.cut.ac.cy', '$2a$12$KJG/4kJesdxexklmNSNvoexGO6iKvVIIIMYzyieNTv5H34CIKohIu', 'Student', 'Student3', 'Student3', NULL),
(69, 40546, 'student4@edu.cut.ac.cy', '$2y$10$FgDGU2MuS.XOqfeff3St.emL7VD0mFDsC8PdHp/y7Lh1X.LrOqA2.', 'Student', 'Student4', 'Student4', NULL),
(73, 2, 'superuser1@cut.ac.cy', '$2y$10$46bh2IXiYsStGwDSNr5zoernaZ7.ZYjHJqJeMtF4SXPlHRCvgzKoe', 'SuperUser', 'SuperUser1', 'SuperUser1', NULL),
(74, 24305, 'student5@edu.cut.ac.cy', '$2y$10$OFEHcfpXdrreJXrUvq1hoekNIADpunbtgFTNUmVmLzNt08o1PlyIC', 'Student', 'Student5', 'Student5', NULL),
(75, 23609, 'student6@edu.cut.ac.cy', '$2y$10$kSA2gqmpRbWx68gLemvw8.cmW3VcAth6tgiD1J2eo6j8ZH7Etgud2', 'Student', 'Student6', 'Student6', NULL),
(76, 25678, 'student7@edu.cut.ac.cy', '$2y$10$MIdYKtwkgyjR88UpGtMzguukJcNlb5QFZAu41pO4CPOrHVNy6G.o6', 'Student', 'Student7', 'Student7', NULL),
(77, 27654, 'student8@edu.cut.ac.cy', '$2y$10$iy3Xnf4WGSIKT7bYb5bYOOaIZJdWsRqyidg6uSMeKyUNEDoyUdGo2', 'Student', 'Student8', 'Student8', NULL),
(78, 28765, 'student9@edu.cut.ac.cy', '$2y$10$aAuTzgt8txX.lDUytUQfZemOZqFCDQETTyKqVzR.YZ0BZg1MAJ9BK', 'Student', 'Student9', 'Student9', NULL),
(79, 23435, 'student10@edu.cut.ac.cy', '$2y$10$KIFQ06EDh0aYAnmKm7jdqOSu4Ty2SWib0BxKXQhRkHGauZReB3Cie', 'Student', 'Student10', 'Student10', NULL),
(80, 30965, 'advisor5@cut.ac.cy', '$2y$10$n/7uDLITBO/WlvwYAVh.DOcuaF6LAxHSnNwpAGAoNPp6VXBE.4D1W', 'Advisor', 'Advisor5', 'Advisor5', '34565478'),
(81, 63468, 'student11@edu.cut.ac.cy', '$2y$10$mf1OJ2BQDdvO/bPPJ1PEjOmDGkOuee3L0CoVnAkAVmxoUekkM1iBG', 'Student', 'Student11', 'Student11', NULL),
(82, 34567, 'student12@edu.cut.ac.cy', '$2y$10$Li/eJziLEaFA/6pyUiKA4OP9nXzW0z/YBmFR14UeB.2oBWh6Jf.lO', 'Student', 'Student12', 'Student12', NULL);

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
(22222, 44556),
(23435, 2000),
(23609, 44556),
(24305, 23232),
(24503, 30080),
(25678, 30080),
(27654, 30080),
(28765, 30965),
(34567, 44556),
(40546, 44556),
(63468, 30965);

INSERT INTO `conversations` (`Conversation_ID`, `Student_ID`, `Advisor_ID`, `Created_At`, `Updated_At`) VALUES
(1, 42, 3, '2026-04-09 20:28:11', '2026-04-09 20:35:00'),
(2, 76, 3, '2026-04-10 08:30:00', '2026-04-10 10:15:00'),
(3, 55, 43, '2026-04-11 11:00:00', '2026-04-11 11:25:00');

INSERT INTO `messages` (`Message_ID`, `Conversation_ID`, `Sender_ID`, `Message_Text`, `Sent_At`, `Is_Read`) VALUES
(1, 1, 42, 'Hello Advisor1, I would like to arrange a meeting.', '2026-04-09 20:30:00', 1),
(2, 1, 3, 'Sure, please send your preferred date.', '2026-04-09 20:35:00', 1),
(3, 2, 76, 'Hello Advisor1, I submitted a request for an additional slot.', '2026-04-10 08:35:00', 1),
(4, 2, 3, 'Approved. See you on the scheduled slot.', '2026-04-10 10:15:00', 0),
(5, 3, 55, 'Hello Advisor2, I need guidance about my module selection.', '2026-04-11 11:02:00', 1),
(6, 3, 43, 'Please book a meeting during my Tuesday office hours.', '2026-04-11 11:25:00', 0);

INSERT INTO `office_hours` (`OfficeHour_ID`, `Advisor_ID`, `Day_of_Week`, `Start_Time`, `End_Time`) VALUES
(1, 3, 'Monday', '10:00:00', '12:00:00'),
(2, 3, 'Wednesday', '14:00:00', '16:00:00'),
(3, 43, 'Tuesday', '09:00:00', '11:00:00'),
(4, 43, 'Thursday', '13:00:00', '15:00:00'),
(5, 44, 'Monday', '11:00:00', '13:00:00'),
(6, 60, 'Wednesday', '09:00:00', '11:00:00'),
(7, 80, 'Friday', '10:00:00', '12:00:00');

INSERT INTO `advisor_additional_slots`
(`AdditionalSlot_ID`, `Advisor_ID`, `Slot_Date`, `Start_Time`, `End_Time`, `Is_Active`, `Created_At`, `Updated_At`) VALUES
(1, 3, '2026-04-22', '10:00:00', '10:30:00', 1, '2026-04-10 08:00:00', '2026-04-10 08:00:00'),
(2, 44, '2026-04-23', '13:30:00', '14:00:00', 1, '2026-04-11 09:00:00', '2026-04-11 09:00:00'),
(3, 80, '2026-04-24', '09:00:00', '09:30:00', 1, '2026-04-12 10:00:00', '2026-04-12 10:00:00');

INSERT INTO `appointment_requests`
(`Request_ID`, `Student_ID`, `Advisor_ID`, `OfficeHour_ID`, `AdditionalSlot_ID`, `Appointment_Date`, `Student_Reason`, `Advisor_Reason`, `Status`, `Created_At`, `Updated_At`) VALUES
(1, 42, 3, 1, NULL, '2026-04-21', 'Student1 would like to discuss academic progress.', NULL, 'Pending', '2026-04-10 09:00:00', '2026-04-10 09:00:00'),
(2, 76, 3, NULL, 1, '2026-04-22', 'Student7 requested an additional slot for urgent guidance.', 'Approved for the additional slot.', 'Approved', '2026-04-10 09:30:00', '2026-04-10 10:00:00'),
(3, 55, 43, 3, NULL, '2026-04-22', 'Student2 would like advice on module selection.', 'Please use the regular office hours next week.', 'Declined', '2026-04-11 11:30:00', '2026-04-11 12:00:00'),
(4, 74, 44, NULL, 2, '2026-04-23', 'Student5 requested a one-off appointment.', 'Approved for the requested slot.', 'Approved', '2026-04-12 08:45:00', '2026-04-12 09:10:00'),
(5, 79, 60, 6, NULL, '2026-04-23', 'Student10 requested to discuss internship options.', 'Cancelled by the student.', 'Cancelled', '2026-04-12 10:00:00', '2026-04-12 10:30:00'),
(6, 67, 80, NULL, 3, '2026-04-24', 'Student3 requested advice about degree planning.', 'Approved for the marketing advisor slot.', 'Approved', '2026-04-13 14:00:00', '2026-04-13 14:20:00');

INSERT INTO `appointments`
(`Appointment_ID`, `Request_ID`, `Student_ID`, `Advisor_ID`, `OfficeHour_ID`, `AdditionalSlot_ID`, `Appointment_Date`, `Start_Time`, `End_Time`, `Status`, `Created_At`, `Updated_At`) VALUES
(1, 2, 76, 3, NULL, 1, '2026-04-22', '10:00:00', '10:30:00', 'Scheduled', '2026-04-10 10:05:00', '2026-04-10 10:05:00'),
(2, 4, 74, 44, NULL, 2, '2026-04-23', '13:30:00', '14:00:00', 'Completed', '2026-04-12 09:15:00', '2026-04-23 14:05:00'),
(3, 6, 67, 80, NULL, 3, '2026-04-24', '09:00:00', '09:30:00', 'Scheduled', '2026-04-13 14:25:00', '2026-04-13 14:25:00');

INSERT INTO `appointment_history`
(`History_ID`, `Request_ID`, `Appointment_ID`, `Student_ID`, `Advisor_ID`, `Action_Type`, `Action_Reason`, `Action_By`, `Created_At`) VALUES
(1, 1, NULL, 42, 3, 'Requested', 'Initial request submitted by Student1.', 42, '2026-04-10 09:00:00'),
(2, 2, NULL, 76, 3, 'Requested', 'Initial request submitted by Student7.', 76, '2026-04-10 09:30:00'),
(3, 2, NULL, 76, 3, 'Approved', 'Advisor1 approved the additional slot request.', 3, '2026-04-10 10:00:00'),
(4, 3, NULL, 55, 43, 'Requested', 'Initial request submitted by Student2.', 55, '2026-04-11 11:30:00'),
(5, 3, NULL, 55, 43, 'Declined', 'Advisor2 declined and asked for a later booking.', 43, '2026-04-11 12:00:00'),
(6, 4, NULL, 74, 44, 'Requested', 'Initial request submitted by Student5.', 74, '2026-04-12 08:45:00'),
(7, 4, NULL, 74, 44, 'Approved', 'Advisor3 approved the one-off slot.', 44, '2026-04-12 09:10:00'),
(8, 4, 2, 74, 44, 'Completed', 'Appointment completed successfully.', 44, '2026-04-23 14:05:00'),
(9, 5, NULL, 79, 60, 'Requested', 'Initial request submitted by Student10.', 79, '2026-04-12 10:00:00'),
(10, 5, NULL, 79, 60, 'Cancelled', 'Student10 cancelled the request.', 79, '2026-04-12 10:30:00'),
(11, 6, NULL, 67, 80, 'Requested', 'Initial request submitted by Student3.', 67, '2026-04-13 14:00:00'),
(12, 6, NULL, 67, 80, 'Approved', 'Advisor5 approved the request.', 80, '2026-04-13 14:20:00');

INSERT INTO `communication_history`
(`Comm_ID`, `Student_ID`, `Advisor_ID`, `Message_Content`, `TimeStamp`) VALUES
(1, 42, 3, 'Student1 contacted Advisor1 about arranging a meeting.', '2026-04-09 20:30:00'),
(2, 76, 3, 'Advisor1 confirmed the additional slot request.', '2026-04-10 10:15:00'),
(3, 55, 43, 'Student2 asked Advisor2 about module selection.', '2026-04-11 11:25:00');

INSERT INTO `password_resets` (`id`, `email`, `token`, `expires_at`, `used`, `created_at`) VALUES
(4, 'student12@edu.cut.ac.cy', '9eb966a71c351595b9b5d55b486c54c8ce18bf9f48f68f695ba221f30575d510', '2026-04-06 17:52:36', 1, '2026-04-06 13:52:36'),
(5, 'admin1@cut.ac.cy', '492219b93e9a7ee03395c4ca63aabf2aff729b2221e9494518958db615b9c2b0', '2026-04-08 18:28:47', 0, '2026-04-08 14:28:47'),
(7, 'student1@edu.cut.ac.cy', '7fe151a657303c06497d6cf9506909a3b6d9809d3537a12d8ae48a5a4d599ec4', '2026-04-09 15:06:30', 1, '2026-04-09 11:06:30');

INSERT INTO `promotion_log` (`id`, `promotion_year`, `executed_at`) VALUES
(1, 2026, '2026-03-26 20:48:38');

-- Optional: sync next AUTO_INCREMENT values after explicit inserts
ALTER TABLE `departments` AUTO_INCREMENT = 7;
ALTER TABLE `degree` AUTO_INCREMENT = 3;
ALTER TABLE `users` AUTO_INCREMENT = 83;
ALTER TABLE `conversations` AUTO_INCREMENT = 4;
ALTER TABLE `messages` AUTO_INCREMENT = 7;
ALTER TABLE `office_hours` AUTO_INCREMENT = 8;
ALTER TABLE `advisor_additional_slots` AUTO_INCREMENT = 4;
ALTER TABLE `appointment_requests` AUTO_INCREMENT = 7;
ALTER TABLE `appointments` AUTO_INCREMENT = 4;
ALTER TABLE `appointment_history` AUTO_INCREMENT = 13;
ALTER TABLE `communication_history` AUTO_INCREMENT = 4;
ALTER TABLE `password_resets` AUTO_INCREMENT = 8;
ALTER TABLE `promotion_log` AUTO_INCREMENT = 2;
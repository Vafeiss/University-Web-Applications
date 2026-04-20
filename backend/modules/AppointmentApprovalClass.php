<?php
declare(strict_types=1);
/*
20-Apr-2026 v1.1
Added support for approving additional appointment slots alongside recurring office hours
Panteleimoni Alexandrou
*/

require_once __DIR__ . '/databaseconnect.php';

class AppointmentApproval
{
    private PDO $conn;

    public function __construct()
    {
        $this->conn = ConnectToDatabase();
    }

    public function getPendingAppointmentsForAdvisor(int $advisorId): array
    {
                try {
                        $sql = "SELECT ar.*, u.First_name, u.Last_Name, oh.Start_Time, oh.End_Time
                                        FROM appointment_requests ar
                                        LEFT JOIN users u ON ar.Student_ID = u.User_ID
                                        LEFT JOIN office_hours oh ON ar.OfficeHour_ID = oh.OfficeHour_ID
                                        WHERE ar.Advisor_ID = ?
                                            AND ar.Status = 'Pending'
                                        ORDER BY ar.Appointment_Date ASC";

                        $stmt = $this->conn->prepare($sql);
                        $stmt->execute([$advisorId]);

                        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        return is_array($result) ? $result : [];
                } catch (Throwable $e) {
                        error_log('AppointmentApproval::getPendingAppointmentsForAdvisor error: ' . $e->getMessage());
                        return [];
                }
    }

    public function approveAppointment(int $requestId, int $advisorId): bool
    {
        try {
            $requestStmt = $this->conn->prepare(
                "SELECT Request_ID, Student_ID, Advisor_ID, OfficeHour_ID, AdditionalSlot_ID, Appointment_Date
                 FROM appointment_requests
                 WHERE Request_ID = ?
                   AND Advisor_ID = ?
                   AND Status = 'Pending'
                 LIMIT 1"
            );
            $requestStmt->execute([$requestId, $advisorId]);
            $request = $requestStmt->fetch(PDO::FETCH_ASSOC);

            if ($request === false) {
                return false;
            }

            $officeHourId = isset($request['OfficeHour_ID']) ? (int)$request['OfficeHour_ID'] : 0;
            $additionalSlotId = isset($request['AdditionalSlot_ID']) ? (int)$request['AdditionalSlot_ID'] : 0;
            $appointmentDate = (string)$request['Appointment_Date'];

            if ($officeHourId > 0) {
                $slotStmt = $this->conn->prepare(
                    "SELECT Start_Time, End_Time
                     FROM office_hours
                     WHERE OfficeHour_ID = ?
                       AND Advisor_ID = ?
                     LIMIT 1"
                );
                $slotStmt->execute([$officeHourId, $advisorId]);
                $slot = $slotStmt->fetch(PDO::FETCH_ASSOC);
            } elseif ($additionalSlotId > 0) {
                $slotStmt = $this->conn->prepare(
                    "SELECT Slot_Date, Start_Time, End_Time, Advisor_ID, Is_Active
                     FROM advisor_additional_slots
                     WHERE AdditionalSlot_ID = ?
                       AND Advisor_ID = ?
                     LIMIT 1"
                );
                $slotStmt->execute([$additionalSlotId, $advisorId]);
                $slot = $slotStmt->fetch(PDO::FETCH_ASSOC);

                if ($slot === false) {
                    return false;
                }

                if ((int)($slot['Advisor_ID'] ?? 0) !== $advisorId || (int)($slot['Is_Active'] ?? 0) !== 1) {
                    return false;
                }

                if ((string)($slot['Slot_Date'] ?? '') !== $appointmentDate) {
                    return false;
                }

                $today = date('Y-m-d');
                if ($appointmentDate < $today) {
                    return false;
                }

                if ($appointmentDate === $today && (string)($slot['End_Time'] ?? '') <= date('H:i:s')) {
                    return false;
                }

                $conflictStmt = $this->conn->prepare(
                    "SELECT Appointment_ID
                     FROM appointments
                     WHERE Advisor_ID = ?
                       AND Appointment_Date = ?
                       AND Status IN ('Scheduled', 'Approved')
                       AND ((Start_Time < ? AND End_Time > ?))
                     LIMIT 1"
                );
                $conflictStmt->execute([
                    $advisorId,
                    $appointmentDate,
                    (string)$slot['End_Time'],
                    (string)$slot['Start_Time'],
                ]);

                if ($conflictStmt->fetch(PDO::FETCH_ASSOC)) {
                    return false;
                }
            } else {
                return false;
            }

            if ($slot === false) {
                return false;
            }

            $this->conn->beginTransaction();

            $updateStmt = $this->conn->prepare(
                "UPDATE appointment_requests
                 SET Status = 'Approved',
                     Updated_At = CURRENT_TIMESTAMP
                 WHERE Request_ID = ?
                   AND Advisor_ID = ?
                   AND Status = 'Pending'"
            );
            $updateStmt->execute([$requestId, $advisorId]);

            if ($updateStmt->rowCount() <= 0) {
                $this->conn->rollBack();
                return false;
            }

            $insertStmt = $this->conn->prepare(
                "INSERT INTO appointments
                 (Request_ID, Student_ID, Advisor_ID, OfficeHour_ID, AdditionalSlot_ID, Appointment_Date, Start_Time, End_Time, Status)
                 VALUES
                 (?, ?, ?, ?, ?, ?, ?, ?, 'Scheduled')"
            );
            $insertStmt->execute([
                $requestId,
                (int)$request['Student_ID'],
                (int)$request['Advisor_ID'],
                $officeHourId > 0 ? $officeHourId : null,
                $additionalSlotId > 0 ? $additionalSlotId : null,
                $appointmentDate,
                (string)$slot['Start_Time'],
                (string)$slot['End_Time'],
            ]);

            $appointmentId = (int)$this->conn->lastInsertId();

            $historyStmt = $this->conn->prepare(
                "INSERT INTO appointment_history
                 (Request_ID, Appointment_ID, Student_ID, Advisor_ID, Action_Type, Action_Reason, Action_By)
                 VALUES
                 (?, ?, ?, ?, 'Approved', NULL, ?)"
            );
            $historyStmt->execute([
                $requestId,
                $appointmentId,
                (int)$request['Student_ID'],
                (int)$request['Advisor_ID'],
                $advisorId,
            ]);

            $this->conn->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            error_log('AppointmentApproval::approveAppointment error: ' . $e->getMessage());
            return false;
        }
    }

    public function declineAppointment(int $requestId, int $advisorId, string $reason): bool
    {
        $reason = trim($reason);

        if ($reason === '') {
            return false;
        }

        try {
            $requestStmt = $this->conn->prepare(
                "SELECT Student_ID, Advisor_ID
                 FROM appointment_requests
                 WHERE Request_ID = ?
                   AND Advisor_ID = ?
                   AND Status = 'Pending'
                 LIMIT 1"
            );
            $requestStmt->execute([$requestId, $advisorId]);
            $request = $requestStmt->fetch(PDO::FETCH_ASSOC);

            if ($request === false) {
                return false;
            }

            $this->conn->beginTransaction();

            $stmt = $this->conn->prepare(
                "UPDATE appointment_requests
                 SET Status = 'Declined',
                     Advisor_Reason = ?,
                     Updated_At = CURRENT_TIMESTAMP
                 WHERE Request_ID = ?
                   AND Advisor_ID = ?
                   AND Status = 'Pending'"
            );
            $stmt->execute([$reason, $requestId, $advisorId]);

            if ($stmt->rowCount() <= 0) {
                $this->conn->rollBack();
                return false;
            }

            $historyStmt = $this->conn->prepare(
                "INSERT INTO appointment_history
                 (Request_ID, Appointment_ID, Student_ID, Advisor_ID, Action_Type, Action_Reason, Action_By)
                 VALUES
                 (?, NULL, ?, ?, 'Declined', ?, ?)"
            );
            $historyStmt->execute([
                $requestId,
                (int)$request['Student_ID'],
                (int)$request['Advisor_ID'],
                $reason,
                $advisorId,
            ]);

            $this->conn->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            error_log('AppointmentApproval::declineAppointment error: ' . $e->getMessage());
            return false;
        }
    }

    public function markAttendance(int $appointmentId, int $advisorId, int $attendance): bool
    {
        $newStatus = match ($attendance) {
            1 => 'Completed',
            2 => 'Cancelled',
            default => ''
        };

        if ($newStatus === '') {
            return false;
        }

        try {
            $sql = "UPDATE appointments
                    SET Status = ?
                    WHERE Appointment_ID = ?
                      AND Advisor_ID = ?
                      AND Status = 'Scheduled'";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$newStatus, $appointmentId, $advisorId]);

            return $stmt->rowCount() > 0;
        } catch (Throwable $e) {
            error_log('AppointmentApproval::markAttendance error: ' . $e->getMessage());
            return false;
        }
    }
}
?>

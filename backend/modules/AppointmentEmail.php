<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/PHPMailer/Exception.php';
require_once __DIR__ . '/../config/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../config/PHPMailer/SMTP.php';
require_once __DIR__ . '/databaseconnect.php';

class AppointmentEmail
{
    private PDO $conn;
    private string $smtpUser;
    private string $smtpPass;
    private string $baseUrl;

    public function __construct(?PDO $conn = null)
    {
        $this->conn = $conn ?? ConnectToDatabase();
        $this->smtpUser = (string)(getenv('APP_EMAIL_SMTP_USER') ?: getenv('PASSWORD_RESET_SMTP_USER') ?: '');
        $this->smtpPass = (string)(getenv('APP_EMAIL_SMTP_PASS') ?: getenv('PASSWORD_RESET_SMTP_PASS') ?: '');

        $configuredBaseUrl = (string)(getenv('APP_BASE_URL') ?: '');
        if ($configuredBaseUrl !== '') {
            $this->baseUrl = rtrim($configuredBaseUrl, '/');
        } else {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = (string)($_SERVER['HTTP_HOST'] ?? 'localhost');
            $this->baseUrl = $scheme . '://' . $host . rtrim(BASE_URL, '/');
        }
    }

    public function sendAdvisorRequestEmail(int $requestId): bool
    {
        $details = $this->getRequestDetails($requestId);
        if ($details === null) {
            return false;
        }

        $advisorEmail = trim((string)($details['Advisor_Email'] ?? ''));
        if ($advisorEmail === '' || !filter_var($advisorEmail, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $advisorName = trim((string)($details['Advisor_Name'] ?? ''));
        $studentName = trim((string)($details['Student_Name'] ?? ''));
        $requestType = trim((string)($details['Request_Type'] ?? 'Appointment Request'));
        $appointmentDate = trim((string)($details['Appointment_Date'] ?? ''));
        $startTime = trim((string)($details['Start_Time'] ?? ''));
        $endTime = trim((string)($details['End_Time'] ?? ''));
        $studentReason = trim((string)($details['Student_Reason'] ?? ''));

        $subject = 'New Appointment Request';
        $body = '<p>Hello' . ($advisorName !== '' ? ' ' . htmlspecialchars($advisorName) : '') . ',</p>'
            . '<p>You have received a new appointment request from ' . htmlspecialchars($studentName !== '' ? $studentName : 'a student') . '.</p>'
            . '<ul>'
            . '<li><strong>Request type:</strong> ' . htmlspecialchars($requestType) . '</li>'
            . ($appointmentDate !== '' ? '<li><strong>Date:</strong> ' . htmlspecialchars($appointmentDate) . '</li>' : '')
            . ($startTime !== '' && $endTime !== '' ? '<li><strong>Time:</strong> ' . htmlspecialchars($startTime . ' - ' . $endTime) . '</li>' : '')
            . ($studentReason !== '' ? '<li><strong>Student reason:</strong> ' . htmlspecialchars($studentReason) . '</li>' : '')
            . '</ul>'
            . '<p>Please review the request in your dashboard.</p>'
            . $this->footerHtml();

        $altBody = 'You have received a new appointment request from ' . ($studentName !== '' ? $studentName : 'a student') . '. Request type: ' . $requestType . '.'
            . ($appointmentDate !== '' ? ' Date: ' . $appointmentDate . '.' : '')
            . ($startTime !== '' && $endTime !== '' ? ' Time: ' . $startTime . ' - ' . $endTime . '.' : '')
            . ($studentReason !== '' ? ' Student reason: ' . $studentReason . '.' : '')
            . ' Please review the request in your dashboard. ' . $this->footerText();

        return $this->sendMail($advisorEmail, $advisorName, $subject, $body, $altBody);
    }

    public function sendStudentDecisionEmail(int $requestId, string $decision): bool
    {
        $details = $this->getRequestDetails($requestId);
        if ($details === null) {
            return false;
        }

        $studentEmail = trim((string)($details['Student_Email'] ?? ''));
        if ($studentEmail === '' || !filter_var($studentEmail, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $studentName = trim((string)($details['Student_Name'] ?? ''));
        $advisorName = trim((string)($details['Advisor_Name'] ?? ''));
        $requestType = trim((string)($details['Request_Type'] ?? 'Appointment Request'));
        $appointmentDate = trim((string)($details['Appointment_Date'] ?? ''));
        $startTime = trim((string)($details['Start_Time'] ?? ''));
        $endTime = trim((string)($details['End_Time'] ?? ''));
        $decisionLabel = ucfirst(strtolower(trim($decision)));
        if ($decisionLabel === '') {
            $decisionLabel = 'Updated';
        }

        $statusText = strtolower($decisionLabel);
        $subject = 'Appointment Request ' . $decisionLabel;
        $body = '<p>Hello' . ($studentName !== '' ? ' ' . htmlspecialchars($studentName) : '') . ',</p>'
            . '<p>Your appointment request has been ' . htmlspecialchars($statusText) . ' by your advisor' . ($advisorName !== '' ? ' (' . htmlspecialchars($advisorName) . ')' : '') . '.</p>'
            . '<ul>'
            . '<li><strong>Status:</strong> ' . htmlspecialchars($decisionLabel) . '</li>'
            . '<li><strong>Request type:</strong> ' . htmlspecialchars($requestType) . '</li>'
            . ($appointmentDate !== '' ? '<li><strong>Date:</strong> ' . htmlspecialchars($appointmentDate) . '</li>' : '')
            . ($startTime !== '' && $endTime !== '' ? '<li><strong>Time:</strong> ' . htmlspecialchars($startTime . ' - ' . $endTime) . '</li>' : '')
            . '</ul>'
            . $this->footerHtml();

        $altBody = 'Your appointment request has been ' . $statusText . ' by your advisor' . ($advisorName !== '' ? ' (' . $advisorName . ')' : '') . '. '
            . 'Status: ' . $decisionLabel . '. Request type: ' . $requestType . '.'
            . ($appointmentDate !== '' ? ' Date: ' . $appointmentDate . '.' : '')
            . ($startTime !== '' && $endTime !== '' ? ' Time: ' . $startTime . ' - ' . $endTime . '.' : '')
            . ' ' . $this->footerText();

        return $this->sendMail($studentEmail, $studentName, $subject, $body, $altBody);
    }

    private function getRequestDetails(int $requestId): ?array
    {
        try {
            $sql = "SELECT
                        ar.Request_ID,
                        ar.Student_Reason,
                        ar.Request_Type,
                        COALESCE(ap.Appointment_Date, ar.Appointment_Date) AS Appointment_Date,
                        COALESCE(ap.Start_Time, oh.Start_Time, aas.Start_Time) AS Start_Time,
                        COALESCE(ap.End_Time, oh.End_Time, aas.End_Time) AS End_Time,
                        CONCAT_WS(' ', s.First_name, s.Last_Name) AS Student_Name,
                        s.Uni_Email AS Student_Email,
                        CONCAT_WS(' ', a.First_name, a.Last_Name) AS Advisor_Name,
                        a.Uni_Email AS Advisor_Email
                    FROM appointment_requests ar
                    INNER JOIN users s ON s.User_ID = ar.Student_ID
                    INNER JOIN users a ON a.User_ID = ar.Advisor_ID
                    LEFT JOIN office_hours oh ON oh.OfficeHour_ID = ar.OfficeHour_ID
                    LEFT JOIN advisor_additional_slots aas ON aas.AdditionalSlot_ID = ar.AdditionalSlot_ID
                    LEFT JOIN appointments ap ON ap.Request_ID = ar.Request_ID
                    WHERE ar.Request_ID = ?
                    LIMIT 1";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$requestId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row === false) {
                return null;
            }

            return $row;
        } catch (Throwable $e) {
            error_log('AppointmentEmail::getRequestDetails error: ' . $e->getMessage());
            return null;
        }
    }

    private function sendMail(string $toEmail, string $toName, string $subject, string $body, string $altBody): bool
    {
        if ($this->smtpUser === '' || $this->smtpPass === '') {
            error_log('Appointment email configuration missing: set APP_EMAIL_SMTP_USER and APP_EMAIL_SMTP_PASS, or reuse the password reset SMTP credentials.');
            return false;
        }

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtpUser;
            $mail->Password = $this->smtpPass;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->CharSet = 'UTF-8';
            $mail->setFrom($this->smtpUser, 'AdviCut System');
            $mail->addAddress($toEmail, $toName);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = $altBody;
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log('Appointment email failed: ' . $mail->ErrorInfo);
            return false;
        }
    }

    private function footerHtml(): string
    {
        $websiteUrl = htmlspecialchars($this->baseUrl . '/frontend/index.php');
        return '<p>For more information visit the website: <a href="' . $websiteUrl . '">' . $websiteUrl . '</a></p>'
            . '<br><small>AdviCut System - Do not reply to this email.</small>';
    }

    private function footerText(): string
    {
        return 'For more information visit the website: ' . $this->baseUrl . '/frontend/index.php. AdviCut System - Do not reply to this email.';
    }
}

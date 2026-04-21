<?php
/*
   NAME: Advisor Appointment Dashboard
   Description: This page displays the advisor dashboard for managing appointment requests, office hours, appointments, history, assigned students, and communications
   Panteleimoni Alexandrou
   13-Apr-2026 v2.4
   Inputs: Section parameter from URL and database records for office hours, requests, appointments, history, calendar events, and assigned students
   Outputs: Advisor dashboard interface with real database data
   Error Messages: If database fetch fails, an error message is displayed inside the relevant section, while action feedback is displayed using NotificationsClass
   Files in use: AdvisorAppointmentDashboard.php, AdvisorOfficeHours.php, AppointmentControllerAction.php, databaseconnect.php, AdvisorClass.php, UsersClass.php, NotificationsClass.php

   13-Apr-2026 v2.5
   Fixed decline modal placement, restored dashboard JavaScript flow, added decline reason modal, and switched request actions to dispatcher route
   Panteleimoni Alexandrou

   19-Apr-2026 v2.6
   Added dashboard redirect target for advisor request approve and decline actions so notifications return to the requests section after processing
   Panteleimoni Alexandrou

   19-Apr-2026 v2.7
   Added database notifications display panel for appointment-related user notifications
   Panteleimoni Alexandrou

   19-Apr-2026 v2.8
   Moved database notifications into top-navbar bell dropdown and added notification item redirects
   Panteleimoni Alexandrou

   20-Apr-2026 v2.9
   Fixed advisor approve and decline form validation flow by restoring CSRF token submission through dispatcher
   Panteleimoni Alexandrou

   19-Apr-2026 v2.9
   Added mark-as-read functionality for notifications on click
   Panteleimoni Alexandrou

   19-Apr-2026 v3.0
   Added small EN/EL language toggle for appointment dashboard interface text
   Panteleimoni Alexandrou

   19-Apr-2026 v3.1
   Expanded EN/EL translation coverage for full appointment dashboard interface text
   Panteleimoni Alexandrou

   19-Apr-2026 v3.2
   Completed remaining EN/EL translation coverage for communications, modals, counters and confirmation texts
   Panteleimoni Alexandrou

   19-Apr-2026 v3.3
   Added FullCalendar localization (EN/EL) based on dashboard session language
   Panteleimoni Alexandrou

   20-Apr-2026 v3.4
   Fixed logout form CSRF submission so logout redirects correctly without dispatcher validation errors
   Panteleimoni Alexandrou

   20-Apr-2026 v3.5
   Added advisor-side additional slot creation modal and EN/EL labels for one-off slot creation
   Panteleimoni Alexandrou

   20-Apr-2026 v3.6
   Added advisor dashboard display for additional one-off appointment slots
   Panteleimoni Alexandrou
*/

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_GET['set_lang']) && in_array((string)$_GET['set_lang'], ['en', 'el'], true)) {
    $_SESSION['appointment_dashboard_lang'] = (string)$_GET['set_lang'];
    $redirectParams = $_GET;
    unset($redirectParams['set_lang']);
    $redirectUrl = basename((string)($_SERVER['PHP_SELF'] ?? 'AdvisorAppointmentDashboard.php'));
    if ($redirectParams !== []) {
        $redirectUrl .= '?' . http_build_query($redirectParams);
    }
    header('Location: ' . $redirectUrl);
    exit();
}

require_once __DIR__ . '/../backend/modules/databaseconnect.php';
require_once __DIR__ . '/../backend/modules/AdvisorClass.php';
require_once __DIR__ . '/../backend/modules/UsersClass.php';
require_once __DIR__ . '/../backend/modules/NotificationsClass.php';
require_once __DIR__ . '/../backend/modules/Csrf.php';

$user = new Users();
$user->Check_Session('Advisor');

$pdo = ConnectToDatabase();
$csrfToken = Csrf::ensureToken();

if (isset($_SESSION['UserID']) && is_numeric($_SESSION['UserID'])) {
    $advisorId = (int) $_SESSION['UserID'];
}

if (!isset($advisorId)) {
    $advisorId = 0;
}

if ($advisorId > 0 && isset($_GET['notification_id']) && is_numeric($_GET['notification_id'])) {
    try {
        $markNotificationSql = "UPDATE notifications
                                SET Is_Read = 1
                                WHERE Notification_ID = :notification_id
                                  AND Recipient_ID = :recipient_id";

        $markNotificationStmt = $pdo->prepare($markNotificationSql);
        $markNotificationStmt->execute([
            'notification_id' => (int)$_GET['notification_id'],
            'recipient_id' => $advisorId
        ]);
    } catch (Throwable $e) {
        error_log('AdvisorAppointmentDashboard mark notification read error: ' . $e->getMessage());
    }
}

$lang = isset($_SESSION['appointment_dashboard_lang']) && in_array($_SESSION['appointment_dashboard_lang'], ['en', 'el'], true)
    ? (string)$_SESSION['appointment_dashboard_lang']
    : 'en';

$translations = [
    'en' => [
        'page_title' => 'Advisor Appointment Dashboard',
        'welcome' => 'Welcome to AdviCut, %s! 👋',
        'notifications' => 'Notifications',
        'notifications_subtitle' => 'Appointment-related updates',
        'no_notifications' => 'No notifications yet.',
        'unread' => 'Unread',
        'manual' => 'Manual',
        'change_password' => 'Change Password',
        'logout' => 'Logout',
        'manual_title' => 'Advisor Dashboard Manual',
        'manual_item_1' => 'Review appointment requests in Requests.',
        'manual_item_2' => 'Manage your weekly slots in Office Hours.',
        'manual_item_3' => 'Check appointment details in Appointments and History.',
        'manual_item_4' => 'Use Communications to message and see assigned students.',
        'close' => 'Close',
        'tab_calendar' => 'Calendar',
        'tab_requests' => 'Requests',
        'tab_officehours' => 'Office Hours',
        'tab_appointments' => 'Appointments',
        'tab_history' => 'History',
        'tab_mystudents' => 'My Students',
        'tab_communications' => 'Communications',
        'appointment_requests' => 'Appointment Requests',
        'appointment_requests_subtitle' => 'Review pending student appointment requests'
    ],
    'el' => [
        'page_title' => 'Πίνακας Ραντεβού Συμβούλου',
        'welcome' => 'Καλώς ήρθες στο AdviCut, %s! 👋',
        'notifications' => 'Ειδοποιήσεις',
        'notifications_subtitle' => 'Ενημερώσεις σχετικές με ραντεβού',
        'no_notifications' => 'Δεν υπάρχουν ειδοποιήσεις ακόμη.',
        'unread' => 'Μη αναγνωσμένο',
        'manual' => 'Οδηγός',
        'change_password' => 'Αλλαγή Κωδικού',
        'logout' => 'Αποσύνδεση',
        'manual_title' => 'Οδηγός Πίνακα Συμβούλου',
        'manual_item_1' => 'Ελέγξτε τα αιτήματα ραντεβού στην ενότητα Αιτήματα.',
        'manual_item_2' => 'Διαχειριστείτε τις εβδομαδιαίες ώρες γραφείου σας.',
        'manual_item_3' => 'Δείτε λεπτομέρειες ραντεβού στις ενότητες Ραντεβού και Ιστορικό.',
        'manual_item_4' => 'Χρησιμοποιήστε τις Επικοινωνίες για μηνύματα και προβολή φοιτητών.',
        'close' => 'Κλείσιμο',
        'tab_calendar' => 'Ημερολόγιο',
        'tab_requests' => 'Αιτήματα',
        'tab_officehours' => 'Ώρες Γραφείου',
        'tab_appointments' => 'Ραντεβού',
        'tab_history' => 'Ιστορικό',
        'tab_mystudents' => 'Οι Φοιτητές Μου',
        'tab_communications' => 'Επικοινωνίες',
        'appointment_requests' => 'Αιτήματα Ραντεβού',
        'appointment_requests_subtitle' => 'Ελέγξτε εκκρεμή αιτήματα ραντεβού φοιτητών'
    ]
];

$translations['en'] = array_merge($translations['en'], [
    'welcome' => 'Welcome to AdviCut, %s! 👋',
    'search_requests' => 'Search requests...',
    'student_id' => 'Student ID',
    'date' => 'Date',
    'student_reason' => 'Student Reason',
    'status' => 'Status',
    'advisor_reason' => 'Advisor Reason',
    'actions' => 'Actions',
    'no_pending_requests_found' => 'No pending requests found',
    'pending' => 'Pending',
    'approve' => 'Approve',
    'decline' => 'Decline',
    'office_hours_title' => 'Office Hours',
    'office_hours_subtitle' => 'Manage your fixed weekly appointment hours',
    'add_slot' => 'Add Slot',
    'add_additional_slot' => 'Add Additional Slot',
    'day' => 'Day',
    'start_time' => 'Start Time',
    'end_time' => 'End Time',
    'action' => 'Action',
    'no_office_hours_loaded' => 'No office hours loaded yet',
    'active' => 'Active',
    'approved_appointments_title' => 'Approved Appointments',
    'approved_appointments_subtitle' => 'View approved and scheduled appointments',
    'appointment_id' => 'Appointment ID',
    'attendance' => 'Attendance',
    'mark_attendance' => 'Mark Attendance',
    'attended' => 'Attended',
    'no_show' => 'No Show',
    'mark_attendance_confirm' => 'Confirm student attendance for this appointment?',
    'attendance_available_on_day' => 'Available on appointment day',
    'no_appointments_found' => 'No appointments found',
    'scheduled' => 'Scheduled',
    'completed' => 'Completed',
    'cancelled' => 'Cancelled',
    'history_title' => 'Appointment History',
    'history_subtitle' => 'View all previous appointment actions',
    'request_id' => 'Request ID',
    'no_history_found' => 'No history found',
    'approved' => 'Approved',
    'declined' => 'Declined',
    'my_students_title' => 'My Students',
    'my_students_subtitle' => 'View students currently assigned to you',
    'search_students' => 'Search students...',
    'filter_by_year' => 'Filter by year',
    'all_years' => 'All years',
    'apply_filters' => 'Apply filters',
    'reset' => 'Reset',
    'name' => 'Name',
    'last_name' => 'Last Name',
    'year' => 'Year',
    'no_assigned_students_found' => 'No assigned students found',
    'year_label' => 'Year %s',
    'communications_title' => 'Communications',
    'communications_subtitle' => 'Review and reply to student messages',
    'assigned_students' => 'Assigned Students',
    'select_student' => 'Select a student',
    'choose_student_messages' => 'Choose a student from the left to load messages.',
    'select_student_conversation' => 'Select a student to view the conversation.',
    'reply_message' => 'Reply message',
    'choose_student_first' => 'Choose a student first...',
    'send_reply' => 'Send Reply',
    'calendar_title' => 'Appointment Calendar',
    'calendar_subtitle' => 'See student appointments and request statuses by date',
    'add_office_hour_slot' => 'Add Office Hour Slot',
    'add_additional_slot_title' => 'Add Additional Slot',
    'additional_slot_date' => 'Date',
    'day_of_week' => 'Day of Week',
    'select_day' => 'Select day...',
    'monday' => 'Monday',
    'tuesday' => 'Tuesday',
    'wednesday' => 'Wednesday',
    'thursday' => 'Thursday',
    'friday' => 'Friday',
    'cancel' => 'Cancel',
    'save_additional_slot' => 'Save Additional Slot',
    'appointment_details' => 'Appointment Details',
    'student' => 'Student',
    'time' => 'Time',
    'view_details' => 'View details',
    'view_reason' => 'View Reason',
    'advisor_note' => 'Advisor Note',
    'decline_request_title' => 'Decline Appointment Request',
    'reason_for_decline' => 'Reason for Decline',
    'decline_request' => 'Decline Request',
    'notifications_aria' => 'Notifications',
    'could_not_load_notifications' => 'Could not load notifications.',
    'words_max_compact' => '200 words max',
    'words_suffix' => 'words',
    'type_message_here' => 'Type your message here...',
    'loading_messages' => 'Loading messages...',
    'no_messages_yet_reply' => 'No messages yet. Send the first reply.',
    'failed_to_load_messages' => 'Failed to load messages. Please try again.',
    'sending' => 'Sending...',
    'failed_to_send_message' => 'Failed to send message.',
    'network_error_sending' => 'Network error while sending message.',
    'you' => 'You',
    'delete_office_hour_confirm' => 'Delete this office hour slot?',
    'delete_additional_slot_confirm' => 'Delete this additional slot?',
    'decline_reason_placeholder' => 'Write the reason for declining this request...',
    'ok' => 'OK'
]);

$translations['el'] = array_merge($translations['el'], [
    'welcome' => 'Καλώς ήρθες στο AdviCut, %s! 👋',
    'search_requests' => 'Αναζήτηση αιτημάτων...',
    'student_id' => 'Κωδικός Φοιτητή',
    'date' => 'Ημερομηνία',
    'student_reason' => 'Λόγος Φοιτητή',
    'status' => 'Κατάσταση',
    'advisor_reason' => 'Λόγος Συμβούλου',
    'actions' => 'Ενέργειες',
    'no_pending_requests_found' => 'Δεν βρέθηκαν εκκρεμή αιτήματα',
    'pending' => 'Εκκρεμεί',
    'approve' => 'Έγκριση',
    'decline' => 'Απόρριψη',
    'office_hours_title' => 'Ώρες Γραφείου',
    'office_hours_subtitle' => 'Διαχειριστείτε τις σταθερές εβδομαδιαίες ώρες ραντεβού σας',
    'add_slot' => 'Προσθήκη Θέσης',
    'day' => 'Ημέρα',
    'start_time' => 'Ώρα Έναρξης',
    'end_time' => 'Ώρα Λήξης',
    'action' => 'Ενέργεια',
    'no_office_hours_loaded' => 'Δεν έχουν φορτωθεί ώρες γραφείου ακόμη',
    'active' => 'Ενεργό',
    'approved_appointments_title' => 'Εγκεκριμένα Ραντεβού',
    'approved_appointments_subtitle' => 'Δείτε εγκεκριμένα και προγραμματισμένα ραντεβού',
    'appointment_id' => 'Κωδικός Ραντεβού',
    'attendance' => 'Παρουσία',
    'mark_attendance' => 'Καταχώρηση Παρουσίας',
    'attended' => 'Παρευρέθηκε',
    'no_show' => 'Απουσία',
    'mark_attendance_confirm' => 'Επιβεβαίωση παρουσίας φοιτητή για αυτό το ραντεβού;',
    'attendance_available_on_day' => 'Διαθέσιμο την ημέρα του ραντεβού',
    'no_appointments_found' => 'Δεν βρέθηκαν ραντεβού',
    'scheduled' => 'Προγραμματισμένο',
    'completed' => 'Ολοκληρώθηκε',
    'cancelled' => 'Ακυρώθηκε',
    'history_title' => 'Ιστορικό Ραντεβού',
    'history_subtitle' => 'Δείτε όλες τις προηγούμενες ενέργειες ραντεβού',
    'request_id' => 'Κωδικός Αιτήματος',
    'no_history_found' => 'Δεν βρέθηκε ιστορικό',
    'approved' => 'Εγκρίθηκε',
    'declined' => 'Απορρίφθηκε',
    'my_students_title' => 'Οι Φοιτητές Μου',
    'my_students_subtitle' => 'Δείτε τους φοιτητές που είναι αυτή τη στιγμή ανατεθειμένοι σε εσάς',
    'search_students' => 'Αναζήτηση φοιτητών...',
    'filter_by_year' => 'Φίλτρο ανά έτος',
    'all_years' => 'Όλα τα έτη',
    'apply_filters' => 'Εφαρμογή φίλτρων',
    'reset' => 'Επαναφορά',
    'name' => 'Όνομα',
    'last_name' => 'Επώνυμο',
    'year' => 'Έτος',
    'no_assigned_students_found' => 'Δεν βρέθηκαν ανατεθειμένοι φοιτητές',
    'year_label' => 'Έτος %s',
    'communications_title' => 'Επικοινωνίες',
    'communications_subtitle' => 'Ελέγξτε και απαντήστε σε μηνύματα φοιτητών',
    'assigned_students' => 'Ανατεθειμένοι Φοιτητές',
    'select_student' => 'Επιλέξτε έναν φοιτητή',
    'choose_student_messages' => 'Επιλέξτε έναν φοιτητή από τα αριστερά για φόρτωση μηνυμάτων.',
    'select_student_conversation' => 'Επιλέξτε έναν φοιτητή για να δείτε τη συνομιλία.',
    'reply_message' => 'Απάντηση μηνύματος',
    'choose_student_first' => 'Επιλέξτε πρώτα έναν φοιτητή...',
    'send_reply' => 'Αποστολή Απάντησης',
    'calendar_title' => 'Ημερολόγιο Ραντεβού',
    'calendar_subtitle' => 'Δείτε ραντεβού φοιτητών και καταστάσεις αιτημάτων ανά ημερομηνία',
    'add_office_hour_slot' => 'Προσθήκη Ώρας Γραφείου',
    'day_of_week' => 'Ημέρα Εβδομάδας',
    'select_day' => 'Επιλέξτε ημέρα...',
    'monday' => 'Δευτέρα',
    'tuesday' => 'Τρίτη',
    'wednesday' => 'Τετάρτη',
    'thursday' => 'Πέμπτη',
    'friday' => 'Παρασκευή',
    'cancel' => 'Ακύρωση',
    'appointment_details' => 'Λεπτομέρειες Ραντεβού',
    'student' => 'Φοιτητής',
    'time' => 'Ώρα',
    'view_details' => 'Προβολή λεπτομερειών',
    'view_reason' => 'Προβολή Λόγου',
    'advisor_note' => 'Σημείωση Συμβούλου',
    'decline_request_title' => 'Απόρριψη Αιτήματος Ραντεβού',
    'reason_for_decline' => 'Λόγος Απόρριψης',
    'decline_request' => 'Απόρριψη Αιτήματος'
]);

$translations['en'] = array_merge($translations['en'], [
    'notifications_aria' => 'Notifications',
    'could_not_load_notifications' => 'Could not load notifications.',
    'could_not_load_office_hours' => 'Could not load office hours.',
    'could_not_load_requests' => 'Could not load appointment requests.',
    'could_not_load_appointments' => 'Could not load appointments.',
    'could_not_load_history' => 'Could not load appointment history.',
    'could_not_load_communications' => 'Could not load assigned students for communications.',
    'could_not_load_calendar' => 'Could not load calendar events.',
    'words_max_compact' => '200 words max',
    'words_suffix' => 'words',
    'type_message_here' => 'Type your message here...',
    'loading_messages' => 'Loading messages...',
    'no_messages_yet_reply' => 'No messages yet. Send the first reply.',
    'failed_to_load_messages' => 'Failed to load messages. Please try again.',
    'you' => 'You',
    'delete_office_hour_confirm' => 'Delete this office hour slot?',
    'decline_reason_placeholder' => 'Write the reason for declining this request...',
    'confirm_action' => 'Confirm Action',
    'confirm_continue' => 'Are you sure you want to continue?',
    'confirm' => 'Confirm',
    'ok' => 'OK'
]);

$translations['el'] = array_merge($translations['el'], [
    'notifications_aria' => 'Ειδοποιήσεις',
    'could_not_load_notifications' => 'Δεν ήταν δυνατή η φόρτωση ειδοποιήσεων.',
    'could_not_load_office_hours' => 'Δεν ήταν δυνατή η φόρτωση ωρών γραφείου.',
    'could_not_load_requests' => 'Δεν ήταν δυνατή η φόρτωση αιτημάτων ραντεβού.',
    'could_not_load_appointments' => 'Δεν ήταν δυνατή η φόρτωση ραντεβού.',
    'could_not_load_history' => 'Δεν ήταν δυνατή η φόρτωση ιστορικού ραντεβού.',
    'could_not_load_communications' => 'Δεν ήταν δυνατή η φόρτωση ανατεθειμένων φοιτητών για επικοινωνίες.',
    'could_not_load_calendar' => 'Δεν ήταν δυνατή η φόρτωση γεγονότων ημερολογίου.',
    'words_max_compact' => 'μέχρι 200 λέξεις',
    'words_suffix' => 'λέξεις',
    'type_message_here' => 'Πληκτρολογήστε το μήνυμά σας εδώ...',
    'loading_messages' => 'Φόρτωση μηνυμάτων...',
    'no_messages_yet_reply' => 'Δεν υπάρχουν μηνύματα ακόμη. Στείλτε την πρώτη απάντηση.',
    'failed_to_load_messages' => 'Η φόρτωση μηνυμάτων απέτυχε. Παρακαλώ δοκιμάστε ξανά.',
    'sending' => 'Αποστολή...',
    'failed_to_send_message' => 'Η αποστολή μηνύματος απέτυχε.',
    'network_error_sending' => 'Σφάλμα δικτύου κατά την αποστολή μηνύματος.',
    'you' => 'Εσείς',
    'delete_office_hour_confirm' => 'Να διαγραφεί αυτή η ώρα γραφείου;',
    'delete_additional_slot_confirm' => 'Να διαγραφεί αυτό το επιπλέον slot;',
    'decline_reason_placeholder' => 'Γράψτε τον λόγο απόρριψης αυτού του αιτήματος...',
    'confirm_action' => 'Επιβεβαίωση Ενέργειας',
    'confirm_continue' => 'Είστε σίγουροι ότι θέλετε να συνεχίσετε;',
    'confirm' => 'Επιβεβαίωση',
    'ok' => 'OK'
]);

$translations['en'] = array_merge($translations['en'], [
    'add_additional_slot' => 'Add Additional Slot',
    'add_additional_slot_title' => 'Add Additional Slot',
    'additional_slot_date' => 'Date',
    'save_additional_slot' => 'Save Additional Slot'
]);

$translations['el'] = array_merge($translations['el'], [
    'add_additional_slot' => 'Προσθήκη Επιπλέον Slot',
    'add_additional_slot_title' => 'Προσθήκη Επιπλέον Slot',
    'additional_slot_date' => 'Ημερομηνία',
    'save_additional_slot' => 'Αποθήκευση Επιπλέον Slot'
]);

$translations['en'] = array_merge($translations['en'], [
    'could_not_load_additional_slots' => 'Could not load additional slots.',
    'additional_slots_title' => 'Additional Slots',
    'additional_slots_subtitle' => 'View your one-off custom appointment availability',
    'type' => 'Type',
    'additional' => 'Additional',
    'no_additional_slots_found' => 'No additional slots found.'
]);

$translations['el'] = array_merge($translations['el'], [
    'could_not_load_additional_slots' => 'Δεν ήταν δυνατή η φόρτωση επιπλέον slots.',
    'additional_slots_title' => 'Επιπλέον Slots',
    'additional_slots_subtitle' => 'Δείτε τη μη επαναλαμβανόμενη διαθεσιμότητα ραντεβού σας',
    'type' => 'Τύπος',
    'additional' => 'Επιπλέον',
    'no_additional_slots_found' => 'Δεν βρέθηκαν επιπλέον slots.'
]);

$t = static function (string $key) use ($translations, $lang): string {
    return $translations[$lang][$key] ?? $translations['en'][$key] ?? $key;
};

$buildCurrentUrl = static function (array $overrides = [], array $remove = []): string {
    $params = $_GET;
    foreach ($remove as $param) {
        unset($params[$param]);
    }
    foreach ($overrides as $key => $value) {
        if ($value === null) {
            unset($params[$key]);
        } else {
            $params[$key] = $value;
        }
    }

    $path = basename((string)($_SERVER['PHP_SELF'] ?? 'AdvisorAppointmentDashboard.php'));
    return $path . ($params !== [] ? '?' . http_build_query($params) : '');
};

$toggleLang = $lang === 'en' ? 'el' : 'en';
$toggleUrl = $buildCurrentUrl(['set_lang' => $toggleLang], ['notification_id']);
$notificationReturnUrl = $buildCurrentUrl([], ['notification_id']);
$langButtonLabel = $lang === 'en' ? 'EN / EL' : 'EL / EN';

$advisorName = 'Advisor';
if ($advisorId > 0) {
    try {
        $advisorNameStmt = $pdo->prepare('SELECT First_name FROM users WHERE User_ID = :advisor_id AND Role = "Advisor" LIMIT 1');
        $advisorNameStmt->execute(['advisor_id' => $advisorId]);
        $advisorFirstName = trim((string)($advisorNameStmt->fetchColumn() ?: ''));
        if ($advisorFirstName !== '') {
            $advisorName = $advisorFirstName;
        }
    } catch (Throwable $e) {
        if (isset($_SESSION['First_name']) && trim((string)$_SESSION['First_name']) !== '') {
            $advisorName = (string) $_SESSION['First_name'];
        } elseif (isset($_SESSION['email']) && trim((string)$_SESSION['email']) !== '') {
            $advisorName = (string) $_SESSION['email'];
        }
    }
} elseif (isset($_SESSION['First_name']) && trim((string)$_SESSION['First_name']) !== '') {
    $advisorName = (string) $_SESSION['First_name'];
} elseif (isset($_SESSION['email']) && trim((string)$_SESSION['email']) !== '') {
    $advisorName = (string) $_SESSION['email'];
}

$activeSection = isset($_GET['section']) ? (string) $_GET['section'] : 'calendar';

$officeHours = [];
$officeHoursError = '';

$additionalSlots = [];
$additionalSlotsError = '';

$requests = [];
$requestsError = '';

$appointments = [];
$appointmentsError = '';

$historyRows = [];
$historyError = '';

$advisorCalendarEvents = [];
$advisorCalendarError = '';

$assignedStudents = [];
$communicationsError = '';
$selectedStudentsYear = trim((string)($_GET['student_year'] ?? ''));

$advisorNotifications = [];
$advisorNotificationsError = '';
$advisorUnreadNotifications = 0;

if (!isset($pdo) || !($pdo instanceof PDO)) {
    die('Database connection is not available.');
}

// Initialize advisor data access layer
$advisorModule = new AdvisorClass();

try {
    $officeHours = $advisorModule->getOfficeHours($advisorId);
} catch (Throwable $e) {
    $officeHoursError = $t('could_not_load_office_hours');
}

try {
    $additionalSlots = $advisorModule->getAdditionalSlots($advisorId);
} catch (Throwable $e) {
    $additionalSlotsError = $t('could_not_load_additional_slots');
}

try {
    $requests = $advisorModule->getPendingRequests($advisorId);
} catch (Throwable $e) {
    $requestsError = $t('could_not_load_requests');
}

try {
    $appointments = $advisorModule->getScheduledAppointmentsWithPendingAttendance($advisorId);
} catch (Throwable $e) {
    $appointmentsError = $t('could_not_load_appointments');
}

try {
    $historyRows = $advisorModule->getAppointmentHistory($advisorId);
} catch (Throwable $e) {
    $historyError = $t('could_not_load_history');
}

try {
    $assignedStudents = $advisorModule->getAssignedStudents($advisorId);

    if ($selectedStudentsYear !== '') {
        $assignedStudents = array_values(array_filter($assignedStudents, static function (array $student) use ($selectedStudentsYear): bool {
            return (string)($student['StuYear'] ?? '') === $selectedStudentsYear;
        }));
    }
} catch (Throwable $e) {
    $communicationsError = $t('could_not_load_communications');
}

try {
    $advisorNotifications = Notifications::getAdvisorNotifications($advisorId);

    foreach ($advisorNotifications as &$notification) {
        if ((int)($notification['Is_Read'] ?? 0) === 0) {
            $advisorUnreadNotifications++;
        }
    }
    unset($notification);
} catch (Throwable $e) {
    $advisorNotificationsError = $t('could_not_load_notifications');
}

try {
    $calendarRows = $advisorModule->getCalendarEvents($advisorId);

    foreach ($calendarRows as $row) {
        $status = (string)($row['Status'] ?? 'Pending');
        $studentFullName = trim(
            (string)($row['Student_First_Name'] ?? '') . ' ' .
            (string)($row['Student_Last_Name'] ?? '')
        );

        $title = $studentFullName !== '' ? $studentFullName : 'Appointment';

        $eventColor = '#6c757d';
        if ($status === 'Pending') $eventColor = '#f0ad4e';
        if ($status === 'Approved') $eventColor = '#198754';
        if ($status === 'Declined') $eventColor = '#dc3545';
        if ($status === 'Cancelled') $eventColor = '#212529';

        $advisorCalendarEvents[] = [
            'id' => (int)($row['Request_ID'] ?? 0),
            'title' => $title . ' (' . $status . ')',
            'start' => (string)($row['Appointment_Date'] ?? ''),
            'backgroundColor' => $eventColor,
            'borderColor' => $eventColor,
            'extendedProps' => [
                'student' => $studentFullName,
                'date' => (string)($row['Appointment_Date'] ?? ''),
                'time' => (string)($row['Start_Time'] ?? '') . ' - ' . (string)($row['End_Time'] ?? ''),
                'student_reason' => (string)($row['Student_Reason'] ?? ''),
                'advisor_reason' => (string)($row['Advisor_Reason'] ?? ''),
                'status' => $status
            ]
        ];
    }
} catch (Throwable $e) {
    $advisorCalendarError = $t('could_not_load_calendar');
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($t('page_title')) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/advisor_appointment_dashboard.css">
</head>
<body>

<?php Notifications::createNotification(); ?>

<header class="top-navbar">
    <img src="../documents/tepaklogo.png" alt="Logo" class="logo">

    <div class="navbar-center">
        <span class="welcome-text"><?= htmlspecialchars(sprintf($t('welcome'), $advisorName)) ?></span>
    </div>

    <div class="d-flex align-items-center gap-3">
        <div class="dropdown">
            <button class="btn position-relative p-0 border-0 bg-transparent" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="<?= htmlspecialchars($t('notifications_aria')) ?>">
                <i class="bi bi-bell fs-5 text-dark"></i>
                <?php if ($advisorUnreadNotifications > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        <?= $advisorUnreadNotifications > 99 ? '99+' : $advisorUnreadNotifications ?>
                    </span>
                <?php endif; ?>
            </button>
            <div class="dropdown-menu dropdown-menu-end p-0 shadow border-0" style="width: 360px; max-width: calc(100vw - 32px);">
                <div class="px-3 py-2 border-bottom">
                    <div class="fw-semibold"><?= htmlspecialchars($t('notifications')) ?></div>
                    <div class="text-muted" style="font-size:.8rem;"><?= htmlspecialchars($t('notifications_subtitle')) ?></div>
                </div>

                <?php if ($advisorNotificationsError !== ''): ?>
                    <div class="px-3 py-3 text-danger small"><?= htmlspecialchars($advisorNotificationsError) ?></div>
                <?php elseif (count($advisorNotifications) === 0): ?>
                    <div class="px-3 py-3 text-muted small"><?= htmlspecialchars($t('no_notifications')) ?></div>
                <?php else: ?>
                    <div style="max-height: 360px; overflow-y: auto;">
                        <?php foreach ($advisorNotifications as $notification): ?>
                            <?php $isUnread = (int)($notification['Is_Read'] ?? 0) === 0; ?>
                            <?php
                            $redirectUrl = (string)($notification['Redirect_URL'] ?? 'AdvisorAppointmentDashboard.php?section=calendar');
                            $redirectSeparator = str_contains($redirectUrl, '?') ? '&' : '?';
                            $notificationUrl = $redirectUrl . $redirectSeparator . 'notification_id=' . (int)($notification['Notification_ID'] ?? 0);
                            ?>
                            <div class="dropdown-item px-3 py-3 border-bottom text-wrap <?= $isUnread ? 'fw-semibold bg-light' : '' ?>">
                                <div class="d-flex align-items-start justify-content-between gap-2">
                                    <a href="<?= htmlspecialchars($notificationUrl) ?>" class="text-decoration-none text-reset flex-grow-1">
                                        <div class="d-flex align-items-start justify-content-between gap-2">
                                            <span><?= htmlspecialchars((string)($notification['Title'] ?? 'Notification')) ?></span>
                                            <?php if ($isUnread): ?>
                                                <span class="badge bg-primary"><?= htmlspecialchars($t('unread')) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-muted mt-1" style="font-size:.9rem; white-space: normal;">
                                            <?= htmlspecialchars((string)($notification['Message'] ?? '')) ?>
                                        </div>
                                        <div class="text-muted mt-2" style="font-size:.78rem;">
                                            <?= htmlspecialchars((string)($notification['Created_At'] ?? '')) ?>
                                        </div>
                                    </a>

                                    <form action="../backend/modules/dispatcher.php" method="POST" class="ms-2">
                                        <input type="hidden" name="action" value="/notification/delete">
                                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="notification_id" value="<?= (int)($notification['Notification_ID'] ?? 0) ?>">
                                        <input type="hidden" name="redirect_to" value="<?= htmlspecialchars($notificationReturnUrl, ENT_QUOTES, 'UTF-8') ?>">
                                        <button type="submit"
                                                class="btn btn-sm btn-outline-danger"
                                                title="Delete notification"
                                                aria-label="Delete notification">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <a href="<?= htmlspecialchars($toggleUrl) ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-2 py-1">
            <i class="bi bi-globe2 me-1"></i><?= htmlspecialchars($langButtonLabel) ?>
        </a>

        <div class="dropdown">
            <button class="btn btn-outline-secondary rounded-circle d-inline-flex align-items-center justify-content-center p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="<?= htmlspecialchars($t('manual')) ?> menu" style="width: 38px; height: 38px;">
                <i class="bi bi-list fs-4"></i>
            </button>
            <div class="dropdown-menu dropdown-menu-end p-2" style="min-width: 220px;">
                <button class="dropdown-item" type="button" data-bs-toggle="modal" data-bs-target="#manualInstructionsModal">
                    <i class="bi bi-journal-text me-2"></i><?= htmlspecialchars($t('manual')) ?>
                </button>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="changepassword.php">
                    <i class="bi bi-shield-lock me-2"></i><?= htmlspecialchars($t('change_password')) ?>
                </a>
                <div class="dropdown-divider"></div>
                <form action="../backend/modules/dispatcher.php" method="POST" class="mb-0">
                    <input type="hidden" name="action" value="/logout">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit" class="dropdown-item text-danger">
                        <i class="bi bi-box-arrow-right me-2"></i><?= htmlspecialchars($t('logout')) ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>

<div class="modal fade" id="manualInstructionsModal" tabindex="-1" aria-labelledby="manualInstructionsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-semibold" id="manualInstructionsModalLabel">
                    <i class="bi bi-info-circle me-2 text-primary"></i><?= htmlspecialchars($t('manual_title')) ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= htmlspecialchars($t('close')) ?>"></button>
            </div>
            <div class="modal-body pt-2">
                <ol class="mb-0 ps-3">
                    <li><?= htmlspecialchars($t('manual_item_1')) ?></li>
                    <li><?= htmlspecialchars($t('manual_item_2')) ?></li>
                    <li><?= htmlspecialchars($t('manual_item_3')) ?></li>
                    <li><?= htmlspecialchars($t('manual_item_4')) ?></li>
                </ol>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal"><?= htmlspecialchars($t('close')) ?></button>
            </div>
        </div>
    </div>
</div>

<div class="tab-bar">
    <button type="button" class="tab-btn <?= $activeSection === 'calendar' ? 'active' : '' ?>" data-section="calendar">
        <i class="bi bi-calendar3"></i> <?= htmlspecialchars($t('tab_calendar')) ?>
    </button>

    <button type="button" class="tab-btn <?= $activeSection === 'requests' ? 'active' : '' ?>" data-section="requests">
        <i class="bi bi-envelope-paper"></i> <?= htmlspecialchars($t('tab_requests')) ?>
    </button>

    <button type="button" class="tab-btn <?= $activeSection === 'officehours' ? 'active' : '' ?>" data-section="officehours">
        <i class="bi bi-clock"></i> <?= htmlspecialchars($t('tab_officehours')) ?>
    </button>

    <button type="button" class="tab-btn <?= $activeSection === 'appointments' ? 'active' : '' ?>" data-section="appointments">
        <i class="bi bi-calendar-check"></i> <?= htmlspecialchars($t('tab_appointments')) ?>
    </button>

    <button type="button" class="tab-btn <?= $activeSection === 'history' ? 'active' : '' ?>" data-section="history">
        <i class="bi bi-clock-history"></i> <?= htmlspecialchars($t('tab_history')) ?>
    </button>

    <button type="button" class="tab-btn <?= $activeSection === 'mystudents' ? 'active' : '' ?>" data-section="mystudents">
        <i class="bi bi-people"></i> <?= htmlspecialchars($t('tab_mystudents')) ?>
    </button>

    <button type="button" class="tab-btn <?= $activeSection === 'communications' ? 'active' : '' ?>" data-section="communications">
        <i class="bi bi-chat-dots"></i> <?= htmlspecialchars($t('tab_communications')) ?>
    </button>
</div>

<main class="container-fluid py-4 px-4" style="max-width: 1100px;">
    <div class="section-panel <?= $activeSection === 'requests' ? 'active' : '' ?>" id="section-requests">
        <div class="section-card">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h5 class="mb-0 fw-semibold"><?= htmlspecialchars($t('appointment_requests')) ?></h5>
                    <p class="text-muted mb-0" style="font-size:.85rem;"><?= htmlspecialchars($t('appointment_requests_subtitle')) ?></p>
                </div>
            </div>

            <?php if ($requestsError !== ''): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($requestsError) ?>
                </div>
            <?php endif; ?>

            <input class="form-control mb-3" id="requestSearch" placeholder="<?= htmlspecialchars($t('search_requests')) ?>">

            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th><?= htmlspecialchars($t('student_id')) ?></th>
                            <th><?= htmlspecialchars($t('date')) ?></th>
                            <th><?= htmlspecialchars($t('student_reason')) ?></th>
                            <th><?= htmlspecialchars($t('status')) ?></th>
                            <th><?= htmlspecialchars($t('advisor_reason')) ?></th>
                            <th style="width:220px;"><?= htmlspecialchars($t('actions')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($requests) === 0): ?>
                            <tr class="request-row">
                                <td colspan="6" class="text-center text-muted"><?= htmlspecialchars($t('no_pending_requests_found')) ?></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($requests as $request): ?>
                                <?php
                                    $requestStudentReason = trim((string)($request['Student_Reason'] ?? ''));
                                    $requestAdvisorReason = trim((string)($request['Advisor_Reason'] ?? ''));
                                ?>
                                <tr class="request-row">
                                    <td><?= htmlspecialchars((string)($request['Student_External_ID'] ?? '-')) ?></td>
                                    <td><?= htmlspecialchars((string)$request['Appointment_Date']) ?></td>
                                    <td>
                                        <?php if ($requestStudentReason !== ''): ?>
                                            <button type="button"
                                                    class="btn btn-outline-primary btn-sm advisor-reason-btn"
                                                    data-reason-title="<?= htmlspecialchars($t('student_reason')) ?>"
                                                    data-reason-content="<?= htmlspecialchars($requestStudentReason) ?>">
                                                <?= htmlspecialchars($t('view_reason')) ?>
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($t('pending')) ?></span></td>
                                    <td>
                                        <?php if ($requestAdvisorReason !== ''): ?>
                                            <button type="button"
                                                    class="btn btn-outline-primary btn-sm advisor-reason-btn"
                                                    data-reason-title="<?= htmlspecialchars($t('advisor_reason')) ?>"
                                                    data-reason-content="<?= htmlspecialchars($requestAdvisorReason) ?>">
                                                <?= htmlspecialchars($t('view_reason')) ?>
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <form action="../backend/modules/dispatcher.php" method="POST" class="mb-0">
                                                <input type="hidden" name="action" value="/appointment/action">
                                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="appointment_action" value="approve">
                                                <input type="hidden" name="request_id" value="<?= (int)$request['Request_ID'] ?>">
                                                <input type="hidden" name="redirect_target" value="advisor_dashboard_requests">
                                                <button type="submit" class="btn btn-success btn-sm">
                                                    <?= htmlspecialchars($t('approve')) ?>
                                                </button>
                                            </form>

                                            <button type="button"
                                                    class="btn btn-danger btn-sm open-decline-modal-btn"
                                                    data-request-id="<?= (int)$request['Request_ID'] ?>"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#declineRequestModal">
                                                <?= htmlspecialchars($t('decline')) ?>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    <div class="section-panel <?= $activeSection === 'officehours' ? 'active' : '' ?>" id="section-officehours">
        <div class="section-card">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h5 class="mb-0 fw-semibold"><?= htmlspecialchars($t('office_hours_title')) ?></h5>
                    <p class="text-muted mb-0" style="font-size:.85rem;"><?= htmlspecialchars($t('office_hours_subtitle')) ?></p>
                </div>

                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addOfficeHourModal">
                        <i class="bi bi-plus-circle me-1"></i> <?= htmlspecialchars($t('add_slot')) ?>
                    </button>
                </div>
            </div>

            <?php if ($officeHoursError !== ''): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($officeHoursError) ?>
                </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th><?= htmlspecialchars($t('day')) ?></th>
                            <th><?= htmlspecialchars($t('start_time')) ?></th>
                            <th><?= htmlspecialchars($t('end_time')) ?></th>
                            <th><?= htmlspecialchars($t('status')) ?></th>
                            <th style="width:120px;"><?= htmlspecialchars($t('action')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($officeHours) === 0): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted"><?= htmlspecialchars($t('no_office_hours_loaded')) ?></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($officeHours as $slot): ?>
                                <tr>
                                    <td><?= htmlspecialchars((string)$slot['Day_of_Week']) ?></td>
                                    <td><?= htmlspecialchars(substr((string)$slot['Start_Time'], 0, 5)) ?></td>
                                    <td><?= htmlspecialchars(substr((string)$slot['End_Time'], 0, 5)) ?></td>
                                    <td><span class="badge bg-success"><?= htmlspecialchars($t('active')) ?></span></td>
                                    <td>
                                        <a href="../backend/controllers/AdvisorOfficeHours.php?delete=<?= (int)$slot['OfficeHour_ID'] ?>"
                                            class="btn btn-outline-danger btn-sm"
                                            onclick="event.preventDefault(); customConfirm('<?= htmlspecialchars($t('delete_office_hour_confirm')) ?>', function(ok) { if (ok) window.location.href = this.href; }.bind(this));">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="section-card mt-4">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h5 class="mb-0 fw-semibold"><?= htmlspecialchars($t('additional_slots_title')) ?></h5>
                    <p class="text-muted mb-0" style="font-size:.85rem;"><?= htmlspecialchars($t('additional_slots_subtitle')) ?></p>
                </div>

                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addAdditionalSlotModal">
                        <i class="bi bi-calendar-plus me-1"></i> <?= htmlspecialchars($t('add_additional_slot')) ?>
                    </button>
                </div>
            </div>

            <?php if ($additionalSlotsError !== ''): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($additionalSlotsError) ?>
                </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th><?= htmlspecialchars($t('date')) ?></th>
                            <th><?= htmlspecialchars($t('start_time')) ?></th>
                            <th><?= htmlspecialchars($t('end_time')) ?></th>
                            <th><?= htmlspecialchars($t('type')) ?></th>
                            <th><?= htmlspecialchars($t('status')) ?></th>
                            <th style="width:120px;"><?= htmlspecialchars($t('action')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($additionalSlots) === 0): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted"><?= htmlspecialchars($t('no_additional_slots_found')) ?></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($additionalSlots as $additionalSlot): ?>
                                <tr>
                                    <td><?= htmlspecialchars((string)$additionalSlot['Slot_Date']) ?></td>
                                    <td><?= htmlspecialchars(substr((string)$additionalSlot['Start_Time'], 0, 5)) ?></td>
                                    <td><?= htmlspecialchars(substr((string)$additionalSlot['End_Time'], 0, 5)) ?></td>
                                    <td><span class="badge bg-info text-dark"><?= htmlspecialchars($t('additional')) ?></span></td>
                                    <td><span class="badge bg-success"><?= htmlspecialchars($t('active')) ?></span></td>
                                    <td>
                                        <a href="../backend/controllers/AdvisorOfficeHours.php?delete_additional=<?= (int)$additionalSlot['AdditionalSlot_ID'] ?>"
                                           class="btn btn-outline-danger btn-sm"
                                           onclick="event.preventDefault(); customConfirm('<?= htmlspecialchars($t('delete_additional_slot_confirm')) ?>', function(ok) { if (ok) window.location.href = this.href; }.bind(this));">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="section-panel <?= $activeSection === 'appointments' ? 'active' : '' ?>" id="section-appointments">
        <div class="section-card">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h5 class="mb-0 fw-semibold"><?= htmlspecialchars($t('approved_appointments_title')) ?></h5>
                    <p class="text-muted mb-0" style="font-size:.85rem;"><?= htmlspecialchars($t('approved_appointments_subtitle')) ?></p>
                </div>
            </div>

            <?php if ($appointmentsError !== ''): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($appointmentsError) ?>
                </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th><?= htmlspecialchars($t('appointment_id')) ?></th>
                            <th><?= htmlspecialchars($t('student_id')) ?></th>
                            <th><?= htmlspecialchars($t('date')) ?></th>
                            <th><?= htmlspecialchars($t('start_time')) ?></th>
                            <th><?= htmlspecialchars($t('end_time')) ?></th>
                            <th><?= htmlspecialchars($t('status')) ?></th>
                            <th><?= htmlspecialchars($t('attendance')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($appointments) === 0): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted"><?= htmlspecialchars($t('no_appointments_found')) ?></td>
                            </tr>
                        <?php else: ?>
                            <?php $todayDate = date('Y-m-d'); ?>
                            <?php foreach ($appointments as $appointment): ?>
                                <?php $canMarkAttendance = ((string)$appointment['Appointment_Date'] === $todayDate); ?>
                                <tr>
                                    <td><?= htmlspecialchars((string)$appointment['Appointment_ID']) ?></td>
                                    <td><?= htmlspecialchars((string)($appointment['Student_External_ID'] ?? $appointment['Student_ID'])) ?></td>
                                    <td><?= htmlspecialchars((string)$appointment['Appointment_Date']) ?></td>
                                    <td><?= htmlspecialchars(substr((string)$appointment['Start_Time'], 0, 5)) ?></td>
                                    <td><?= htmlspecialchars(substr((string)$appointment['End_Time'], 0, 5)) ?></td>
                                    <td>
                                        <?php if ($appointment['Status'] === 'Scheduled'): ?>
                                            <span class="badge bg-primary"><?= htmlspecialchars($t('scheduled')) ?></span>
                                        <?php elseif ($appointment['Status'] === 'Completed'): ?>
                                            <span class="badge bg-success"><?= htmlspecialchars($t('completed')) ?></span>
                                        <?php elseif ($appointment['Status'] === 'Cancelled'): ?>
                                            <span class="badge bg-danger"><?= htmlspecialchars($t('cancelled')) ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-dark"><?= htmlspecialchars((string)$appointment['Status']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($canMarkAttendance): ?>
                                            <form action="../backend/modules/dispatcher.php"
                                                  method="POST"
                                                  class="d-flex align-items-center gap-2"
                                                  onsubmit="event.preventDefault(); customConfirm('<?= htmlspecialchars($t('mark_attendance_confirm')) ?>', function(ok) { if (ok) this.submit(); }.bind(this));">
                                                <input type="hidden" name="action" value="/appointment/action">
                                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="appointment_action" value="mark_attendance">
                                                <input type="hidden" name="appointment_id" value="<?= (int)$appointment['Appointment_ID'] ?>">
                                                <input type="hidden" name="redirect_target" value="advisor_dashboard_appointments">
                                                <select name="student_attendance" class="form-select form-select-sm" required>
                                                    <option value="Attended"><?= htmlspecialchars($t('attended')) ?></option>
                                                    <option value="No Show"><?= htmlspecialchars($t('no_show')) ?></option>
                                                </select>
                                                <button type="submit" class="btn btn-primary btn-sm text-nowrap">
                                                    <?= htmlspecialchars($t('mark_attendance')) ?>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted small"><?= htmlspecialchars($t('attendance_available_on_day')) ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="section-panel <?= $activeSection === 'history' ? 'active' : '' ?>" id="section-history">
        <div class="section-card">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h5 class="mb-0 fw-semibold"><?= htmlspecialchars($t('history_title')) ?></h5>
                    <p class="text-muted mb-0" style="font-size:.85rem;"><?= htmlspecialchars($t('history_subtitle')) ?></p>
                </div>
            </div>

            <?php if ($historyError !== ''): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($historyError) ?>
                </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th><?= htmlspecialchars($t('request_id')) ?></th>
                            <th><?= htmlspecialchars($t('student_id')) ?></th>
                            <th><?= htmlspecialchars($t('status')) ?></th>
                            <th><?= htmlspecialchars($t('attendance')) ?></th>
                            <th><?= htmlspecialchars($t('student_reason')) ?></th>
                            <th><?= htmlspecialchars($t('advisor_reason')) ?></th>
                            <th><?= htmlspecialchars($t('date')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($historyRows) === 0): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted"><?= htmlspecialchars($t('no_history_found')) ?></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($historyRows as $history): ?>
                                <?php $historyStudentReason = trim((string)($history['Student_Reason'] ?? '')); ?>
                                <?php $historyAdvisorReason = trim((string)($history['Advisor_Reason'] ?? '')); ?>
                                <?php $historyAttendance = trim((string)($history['Student_Attendance'] ?? '')); ?>
                                <tr>
                                    <td><?= htmlspecialchars((string)$history['Request_ID']) ?></td>
                                    <td><?= htmlspecialchars((string)$history['Student_External_ID']) ?></td>
                                    <td>
                                        <?php if ($history['Status'] === 'Approved'): ?>
                                            <span class="badge bg-success"><?= htmlspecialchars($t('approved')) ?></span>
                                        <?php elseif ($history['Status'] === 'Declined'): ?>
                                            <span class="badge bg-danger"><?= htmlspecialchars($t('declined')) ?></span>
                                        <?php elseif ($history['Status'] === 'Cancelled'): ?>
                                            <span class="badge bg-dark"><?= htmlspecialchars($t('cancelled')) ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-primary"><?= htmlspecialchars((string)$history['Status']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($historyAttendance === 'Attended'): ?>
                                            <span class="badge bg-success"><?= htmlspecialchars($t('attended')) ?></span>
                                        <?php elseif ($historyAttendance === 'No Show'): ?>
                                            <span class="badge bg-danger"><?= htmlspecialchars($t('no_show')) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($historyStudentReason !== ''): ?>
                                            <button type="button"
                                                    class="btn btn-outline-primary btn-sm advisor-reason-btn"
                                                    data-reason-title="<?= htmlspecialchars($t('student_reason')) ?>"
                                                    data-reason-content="<?= htmlspecialchars($historyStudentReason) ?>">
                                                <?= htmlspecialchars($t('view_reason')) ?>
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($historyAdvisorReason !== ''): ?>
                                            <button type="button"
                                                    class="btn btn-outline-primary btn-sm advisor-reason-btn"
                                                    data-reason-title="<?= htmlspecialchars($t('advisor_reason')) ?>"
                                                    data-reason-content="<?= htmlspecialchars($historyAdvisorReason) ?>">
                                                <?= htmlspecialchars($t('view_reason')) ?>
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                   <td><?= htmlspecialchars((string)($history['Appointment_Date'] ?? $history['Created_At'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="section-panel <?= $activeSection === 'mystudents' ? 'active' : '' ?>" id="section-mystudents">
        <div class="section-card">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h5 class="mb-0 fw-semibold"><?= htmlspecialchars($t('my_students_title')) ?></h5>
                    <p class="text-muted mb-0" style="font-size:.85rem;"><?= htmlspecialchars($t('my_students_subtitle')) ?></p>
                </div>
            </div>

            <?php if ($communicationsError !== ''): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($communicationsError) ?>
                </div>
            <?php endif; ?>

            <form method="GET" class="mb-3">
                <input type="hidden" name="section" value="mystudents">

                <div class="d-flex flex-wrap gap-2 mb-3">
                    <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#studentYearFilterWrap" aria-expanded="false" aria-controls="studentYearFilterWrap">
                        <i class="bi bi-calendar3 me-1"></i> <?= htmlspecialchars($t('filter_by_year')) ?>
                    </button>
                    <button class="btn btn-primary btn-sm" type="submit">
                        <i class="bi bi-funnel-fill me-1"></i> <?= htmlspecialchars($t('apply_filters')) ?>
                    </button>
                    <a href="AdvisorAppointmentDashboard.php?section=mystudents" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> <?= htmlspecialchars($t('reset')) ?>
                    </a>
                </div>

                <div class="row g-2 align-items-end mb-3">
                    <div class="col-sm-4 col-md-3 collapse <?= $selectedStudentsYear !== '' ? 'show' : '' ?>" id="studentYearFilterWrap">
                        <label for="studentYearFilter" class="form-label mb-1"><?= htmlspecialchars($t('filter_by_year')) ?></label>
                        <select class="form-select" id="studentYearFilter" name="student_year">
                            <option value="" <?= $selectedStudentsYear === '' ? 'selected' : '' ?>><?= htmlspecialchars($t('all_years')) ?></option>
                            <?php for ($yearValue = 1; $yearValue <= 6; $yearValue++): ?>
                                <option value="<?= $yearValue ?>" <?= $selectedStudentsYear === (string)$yearValue ? 'selected' : '' ?>>
                                    <?= htmlspecialchars(sprintf($t('year_label'), (string)$yearValue)) ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
            </form>

            <input class="form-control mb-3" id="studentSearch" placeholder="<?= htmlspecialchars($t('search_students')) ?>">

            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th><?= htmlspecialchars($t('student_id')) ?></th>
                            <th><?= htmlspecialchars($t('name')) ?></th>
                            <th><?= htmlspecialchars($t('last_name')) ?></th>
                            <th><?= htmlspecialchars($t('year')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($assignedStudents) === 0): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted"><?= htmlspecialchars($t('no_assigned_students_found')) ?></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($assignedStudents as $student): ?>
                                <tr class="student-row" data-year="<?= htmlspecialchars((string)($student['StuYear'] ?? '')) ?>">
                                    <td><?= htmlspecialchars((string)($student['StuExternal_ID'] ?? '-')) ?></td>
                                    <td><?= htmlspecialchars((string)($student['First_name'] ?? '-')) ?></td>
                                    <td><?= htmlspecialchars((string)($student['Last_Name'] ?? '-')) ?></td>
                                    <td><?= htmlspecialchars(sprintf($t('year_label'), (string)($student['StuYear'] ?? '-'))) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="section-panel <?= $activeSection === 'communications' ? 'active' : '' ?>" id="section-communications">
        <div class="section-card">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h5 class="mb-0 fw-semibold"><?= htmlspecialchars($t('communications_title')) ?></h5>
                    <p class="text-muted mb-0" style="font-size:.85rem;"><?= htmlspecialchars($t('communications_subtitle')) ?></p>
                </div>
            </div>

            <?php if ($communicationsError !== ''): ?>
                <div class="alert alert-danger mb-3">
                    <?= htmlspecialchars($communicationsError) ?>
                </div>
            <?php endif; ?>

            <div class="comm-layout">
                <aside class="comm-sidebar">
                    <div class="comm-sidebar-header">
                        <h6><?= htmlspecialchars($t('assigned_students')) ?></h6>
                    </div>

                    <div class="comm-student-list" id="commStudentList">
                        <?php if (count($assignedStudents) === 0): ?>
                            <div class="comm-placeholder">
                                <i class="bi bi-people"></i>
                                <p><?= htmlspecialchars($t('no_assigned_students_found')) ?></p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($assignedStudents as $student):
                                $studentUserId = (int)($student['User_ID'] ?? 0);
                                $studentName = trim((string)($student['First_name'] ?? '') . ' ' . (string)($student['Last_Name'] ?? ''));
                                $studentExternalId = (string)($student['StuExternal_ID'] ?? '');
                                $unreadCount = (int)($student['unread_count'] ?? 0);
                            ?>
                                <div class="comm-student-item"
                                     data-student-id="<?= $studentUserId ?>"
                                     data-student-name="<?= htmlspecialchars($studentName !== '' ? $studentName : $t('student')) ?>"
                                     data-student-ext-id="<?= htmlspecialchars($studentExternalId) ?>">
                                    <div>
                                        <div class="comm-stu-name"><?= htmlspecialchars($studentName !== '' ? $studentName : $t('student')) ?></div>
                                        <div class="comm-stu-id">ID: <?= htmlspecialchars($studentExternalId !== '' ? $studentExternalId : '-') ?></div>
                                    </div>
                                    <?php if ($unreadCount > 0): ?>
                                        <span class="comm-unread"><?= $unreadCount ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </aside>

                <section class="comm-pane">
                    <div class="comm-pane-header">
                        <h6 id="commPaneStudentName"><?= htmlspecialchars($t('select_student')) ?></h6>
                        <small id="commPaneStudentMeta"><?= htmlspecialchars($t('choose_student_messages')) ?></small>
                    </div>

                    <div class="comm-messages" id="commMessages">
                        <div class="comm-placeholder">
                            <i class="bi bi-chat-dots"></i>
                            <p><?= htmlspecialchars($t('select_student_conversation')) ?></p>
                        </div>
                    </div>

                    <div class="comm-compose">
                        <label for="commTextarea"><?= htmlspecialchars($t('reply_message')) ?> <span class="text-muted">(<?= htmlspecialchars($t('words_max_compact')) ?>)</span></label>
                        <textarea id="commTextarea"
                                  placeholder="<?= htmlspecialchars($t('choose_student_first')) ?>"
                                  maxlength="2000"
                                  disabled
                                  oninput="commWordCount(this)"></textarea>
                        <div class="comm-compose-footer">
                            <span class="comm-word-count" id="commWordCount">0 / 200 <?= htmlspecialchars($t('words_suffix')) ?></span>
                            <button type="button" class="btn-send" id="commSendBtn" onclick="commSend()" disabled>
                                <i class="bi bi-send-fill"></i> <?= htmlspecialchars($t('send_reply')) ?>
                            </button>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>

    <div class="section-panel <?= $activeSection === 'calendar' ? 'active' : '' ?>" id="section-calendar">
        <div class="section-card">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h5 class="mb-0 fw-semibold"><?= htmlspecialchars($t('calendar_title')) ?></h5>
                    <p class="text-muted mb-0" style="font-size:.85rem;"><?= htmlspecialchars($t('calendar_subtitle')) ?></p>
                </div>
            </div>

            <?php if ($advisorCalendarError !== ''): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($advisorCalendarError) ?>
                </div>
            <?php endif; ?>

            <div id="advisorCalendar"></div>
        </div>
    </div>

</main>

<?php require_once __DIR__ . '/footer/dashboard_footer.php'; ?>

<div class="modal fade" id="addOfficeHourModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-semibold"><?= htmlspecialchars($t('add_office_hour_slot')) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form action="../backend/controllers/AdvisorOfficeHours.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label"><?= htmlspecialchars($t('day_of_week')) ?> <span class="text-danger">*</span></label>
                            <select name="day_of_week" class="form-select" required>
                                <option value="" disabled selected><?= htmlspecialchars($t('select_day')) ?></option>
                                <option value="Monday"><?= htmlspecialchars($t('monday')) ?></option>
                                <option value="Tuesday"><?= htmlspecialchars($t('tuesday')) ?></option>
                                <option value="Wednesday"><?= htmlspecialchars($t('wednesday')) ?></option>
                                <option value="Thursday"><?= htmlspecialchars($t('thursday')) ?></option>
                                <option value="Friday"><?= htmlspecialchars($t('friday')) ?></option>
                            </select>
                        </div>

                        <div class="col-6">
                            <label class="form-label"><?= htmlspecialchars($t('start_time')) ?> <span class="text-danger">*</span></label>
                            <input type="time" name="start_time" class="form-control" required>
                        </div>

                        <div class="col-6">
                            <label class="form-label"><?= htmlspecialchars($t('end_time')) ?> <span class="text-danger">*</span></label>
                            <input type="time" name="end_time" class="form-control" required>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal"><?= htmlspecialchars($t('cancel')) ?></button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i> <?= htmlspecialchars($t('add_slot')) ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="addAdditionalSlotModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-semibold"><?= htmlspecialchars($t('add_additional_slot_title')) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form action="../backend/controllers/AdvisorOfficeHours.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_additional">

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label"><?= htmlspecialchars($t('additional_slot_date')) ?> <span class="text-danger">*</span></label>
                            <input type="date" name="slot_date" class="form-control" required>
                        </div>

                        <div class="col-6">
                            <label class="form-label"><?= htmlspecialchars($t('start_time')) ?> <span class="text-danger">*</span></label>
                            <input type="time" name="start_time" class="form-control" required>
                        </div>

                        <div class="col-6">
                            <label class="form-label"><?= htmlspecialchars($t('end_time')) ?> <span class="text-danger">*</span></label>
                            <input type="time" name="end_time" class="form-control" required>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal"><?= htmlspecialchars($t('cancel')) ?></button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-calendar-plus me-1"></i> <?= htmlspecialchars($t('save_additional_slot')) ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="advisorCalendarModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content rounded-4">
            <div class="modal-header">
                <h5 class="modal-title"><?= htmlspecialchars($t('appointment_details')) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= htmlspecialchars($t('close')) ?>"></button>
            </div>
            <div class="modal-body">
                <p><strong><?= htmlspecialchars($t('student')) ?>:</strong> <span id="advisorCalendarModalStudent"></span></p>
                <p><strong><?= htmlspecialchars($t('date')) ?>:</strong> <span id="advisorCalendarModalDate"></span></p>
                <p><strong><?= htmlspecialchars($t('time')) ?>:</strong> <span id="advisorCalendarModalTime"></span></p>
                <p><strong><?= htmlspecialchars($t('status')) ?>:</strong> <span id="advisorCalendarModalStatus"></span></p>

                <div class="calendar-reason-group">
                    <div class="d-flex align-items-center justify-content-between gap-3">
                        <strong><?= htmlspecialchars($t('student_reason')) ?>:</strong>
                        <button type="button"
                                class="btn btn-outline-primary btn-sm calendar-reason-btn"
                                id="advisorCalendarModalStudentReasonBtn"
                                data-bs-toggle="collapse"
                                data-bs-target="#advisorCalendarModalStudentReasonWrap"
                                aria-expanded="false"
                                aria-controls="advisorCalendarModalStudentReasonWrap">
                            <?= htmlspecialchars($t('view_reason')) ?>
                        </button>
                    </div>
                    <div class="collapse mt-2" id="advisorCalendarModalStudentReasonWrap">
                        <div class="calendar-reason-box" id="advisorCalendarModalStudentReason"></div>
                    </div>
                </div>

                <div class="calendar-reason-group mt-3">
                    <div class="d-flex align-items-center justify-content-between gap-3">
                        <strong><?= htmlspecialchars($t('advisor_note')) ?>:</strong>
                        <button type="button"
                                class="btn btn-outline-primary btn-sm calendar-reason-btn"
                                id="advisorCalendarModalAdvisorReasonBtn"
                                data-bs-toggle="collapse"
                                data-bs-target="#advisorCalendarModalAdvisorReasonWrap"
                                aria-expanded="false"
                                aria-controls="advisorCalendarModalAdvisorReasonWrap">
                            <?= htmlspecialchars($t('view_reason')) ?>
                        </button>
                    </div>
                    <div class="collapse mt-2" id="advisorCalendarModalAdvisorReasonWrap">
                        <div class="calendar-reason-box" id="advisorCalendarModalAdvisorReason"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="declineRequestModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-semibold"><?= htmlspecialchars($t('decline_request_title')) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form action="../backend/modules/dispatcher.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="/appointment/action">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="appointment_action" value="decline">
                    <input type="hidden" name="request_id" id="declineRequestId" value="">
                    <input type="hidden" name="redirect_target" value="advisor_dashboard_requests">

                    <div class="mb-3">
                        <label class="form-label"><?= htmlspecialchars($t('reason_for_decline')) ?> <span class="text-danger">*</span></label>
                        <textarea name="decline_reason"
                                  id="declineReasonTextarea"
                                  class="form-control"
                                  rows="4"
                                  placeholder="<?= htmlspecialchars($t('decline_reason_placeholder')) ?>"
                                  required></textarea>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal"><?= htmlspecialchars($t('cancel')) ?></button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-x-circle me-1"></i> <?= htmlspecialchars($t('decline_request')) ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="advisorReasonModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4">
            <div class="modal-header">
                <h5 class="modal-title" id="advisorReasonModalTitle"><?= htmlspecialchars($t('reason')) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= htmlspecialchars($t('close')) ?>"></button>
            </div>
            <div class="modal-body">
                <div class="calendar-reason-box" id="advisorReasonModalText"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="advisorConfirmModal" tabindex="-1" aria-labelledby="advisorConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-semibold" id="advisorConfirmModalLabel"><?= htmlspecialchars($t('confirm_action')) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= htmlspecialchars($t('close')) ?>"></button>
            </div>
            <div class="modal-body pt-2">
                <p class="mb-0" id="advisorConfirmMessage"><?= htmlspecialchars($t('confirm_continue')) ?></p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal"><?= htmlspecialchars($t('cancel')) ?></button>
                <button type="button" class="btn btn-danger" id="advisorConfirmButton"><?= htmlspecialchars($t('confirm')) ?></button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/locales-all.global.min.js"></script>

<script>
const COMM_MAX_WORDS = 200;
const currentLang = <?= json_encode($lang === 'el' ? 'el' : 'en') ?>;
let commActiveStudentId = 0;
let commLoadedForStudent = 0;
let advisorCalendarLoaded = false;
let advisorCalendarInstance = null;
let advisorReasonModal = null;
let advisorConfirmModalInstance = null;
let pendingAdvisorConfirmCallback = null;

const advisorCalendarEvents = <?= json_encode($advisorCalendarEvents, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

function setCalendarReason(buttonId, wrapId, contentId, value) {
    const button = document.getElementById(buttonId);
    const wrap = document.getElementById(wrapId);
    const content = document.getElementById(contentId);

    if (!button || !wrap || !content) return;

    const text = String(value ?? '').trim();
    const hasValue = text !== '' && text !== '-';

    content.textContent = hasValue ? text : '';
    button.style.display = hasValue ? 'inline-flex' : 'none';

    if (!hasValue) {
        const collapse = bootstrap.Collapse.getOrCreateInstance(wrap, { toggle: false });
        collapse.hide();
    }
}

function resetCalendarReasonState(wrapId) {
    const wrap = document.getElementById(wrapId);
    if (!wrap) return;

    const collapse = bootstrap.Collapse.getOrCreateInstance(wrap, { toggle: false });
    collapse.hide();
}

function openAdvisorReasonModal(titleText, reasonText) {
    const titleEl = document.getElementById('advisorReasonModalTitle');
    const textEl = document.getElementById('advisorReasonModalText');

    if (!titleEl || !textEl || !advisorReasonModal) return;

    titleEl.textContent = String(titleText ?? '').trim() || <?= json_encode($t('reason')) ?>;
    textEl.textContent = String(reasonText ?? '').trim() || '-';
    advisorReasonModal.show();
}

function renderAdvisorCalendar() {
    if (advisorCalendarLoaded) return;

    const calendarEl = document.getElementById('advisorCalendar');
    const modalEl = document.getElementById('advisorCalendarModal');
    if (!calendarEl || !modalEl) return;

    const detailsModal = new bootstrap.Modal(modalEl);

    advisorCalendarInstance = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: currentLang,
        buttonText: {
            today: currentLang === 'el' ? 'Σήμερα' : 'today'
        },
        height: 'auto',
        events: advisorCalendarEvents,
        eventClick: function (info) {
            const props = info.event.extendedProps || {};
            document.getElementById('advisorCalendarModalStudent').textContent = props.student || '-';
            document.getElementById('advisorCalendarModalDate').textContent = props.date || '-';
            document.getElementById('advisorCalendarModalTime').textContent = props.time || '-';
            document.getElementById('advisorCalendarModalStatus').textContent = props.status || '-';
            setCalendarReason('advisorCalendarModalStudentReasonBtn', 'advisorCalendarModalStudentReasonWrap', 'advisorCalendarModalStudentReason', props.student_reason);
            setCalendarReason('advisorCalendarModalAdvisorReasonBtn', 'advisorCalendarModalAdvisorReasonWrap', 'advisorCalendarModalAdvisorReason', props.advisor_reason);
            resetCalendarReasonState('advisorCalendarModalStudentReasonWrap');
            resetCalendarReasonState('advisorCalendarModalAdvisorReasonWrap');
            detailsModal.show();
        }
    });

    advisorCalendarInstance.render();
    advisorCalendarLoaded = true;
}

function commEsc(str) {
    return String(str ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function commSetActiveStudent(item) {
    document.querySelectorAll('.comm-student-item').forEach(function (el) {
        el.classList.remove('active');
    });
    item.classList.add('active');

    const name = item.getAttribute('data-student-name') || 'Student';
    const extId = item.getAttribute('data-student-ext-id') || '-';
    document.getElementById('commPaneStudentName').textContent = name;
    document.getElementById('commPaneStudentMeta').textContent = 'Student ID: ' + extId;

    const textarea = document.getElementById('commTextarea');
    textarea.disabled = false;
    textarea.placeholder = <?= json_encode($t('type_message_here')) ?>;
}

function commLoadThread(studentId) {
    const box = document.getElementById('commMessages');
    if (!box || studentId <= 0) return;

    box.innerHTML = '<div class="comm-loading"><?= htmlspecialchars($t('loading_messages')) ?></div>';

    const fd = new FormData();
    fd.append('action', '/message/thread');
    fd.append('student_id', String(studentId));
    fd.append('_csrf', <?= json_encode($csrfToken) ?>);

    fetch('../backend/modules/dispatcher.php', { method: 'POST', body: fd })
        .then(function (r) { return r.json(); })
        .then(function (payload) {
            const messages = Array.isArray(payload)
                ? payload
                : (payload && Array.isArray(payload.data) ? payload.data : []);

            if (!Array.isArray(messages) || messages.length === 0) {
                box.innerHTML = '<div class="comm-placeholder"><i class="bi bi-chat"></i><p><?= htmlspecialchars($t('no_messages_yet_reply')) ?></p></div>';
            } else {
                box.innerHTML = messages.map(function (m) {
                    const side = m.sender === 'advisor' ? 'from-advisor' : 'from-student';
                    const senderLabel = m.sender === 'advisor' ? <?= json_encode($t('you')) ?> : (m.sender_name || <?= json_encode($t('student')) ?>);
                    const time = m.sent_at ? new Date(m.sent_at).toLocaleString() : '';
                    return [
                        '<div class="msg-bubble-wrap ' + side + '">',
                        '<div class="msg-meta">',
                        '<span class="msg-sender">' + commEsc(senderLabel) + '</span>',
                        '<span>' + commEsc(time) + '</span>',
                        '</div>',
                        '<div class="msg-bubble">' + commEsc(m.body) + '</div>',
                        '</div>'
                    ].join('');
                }).join('');
            }

            box.scrollTop = box.scrollHeight;

            const readFd = new FormData();
            readFd.append('action', '/message/read');
            readFd.append('student_id', String(studentId));
            readFd.append('_csrf', <?= json_encode($csrfToken) ?>);
            fetch('../backend/modules/dispatcher.php', { method: 'POST', body: readFd }).catch(function () {});

            const activeItem = document.querySelector('.comm-student-item.active .comm-unread');
            if (activeItem) {
                activeItem.remove();
            }
        })
        .catch(function () {
            box.innerHTML = '<div class="comm-placeholder" style="color:#ef4444"><i class="bi bi-exclamation-circle"></i><p><?= htmlspecialchars($t('failed_to_load_messages')) ?></p></div>';
        });
}

function commWordCount(textarea) {
    const words = textarea.value.trim() === '' ? 0 : textarea.value.trim().split(/\s+/).length;
    const counter = document.getElementById('commWordCount');
    const sendBtn = document.getElementById('commSendBtn');

    counter.textContent = words + ' / ' + COMM_MAX_WORDS + ' ' + <?= json_encode($t('words_suffix')) ?>;
    counter.classList.toggle('over', words > COMM_MAX_WORDS);
    sendBtn.disabled = (commActiveStudentId <= 0 || words === 0 || words > COMM_MAX_WORDS);
}

function commSend() {
    const textarea = document.getElementById('commTextarea');
    const sendBtn = document.getElementById('commSendBtn');
    const messageBody = textarea.value.trim();

    if (commActiveStudentId <= 0 || messageBody === '') return;

    sendBtn.disabled = true;
    sendBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> <?= htmlspecialchars($t('sending')) ?>';

    const fd = new FormData();
    fd.append('action', '/message/send');
    fd.append('student_id', String(commActiveStudentId));
    fd.append('message_body', messageBody);
    fd.append('_csrf', <?= json_encode($csrfToken) ?>);

    fetch('../backend/modules/dispatcher.php', { method: 'POST', body: fd })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data && data.success) {
                textarea.value = '';
                commWordCount(textarea);
                commLoadThread(commActiveStudentId);
            } else {
                showPageMessage((data && data.message) ? data.message : <?= json_encode($t('failed_to_send_message')) ?>, 'danger');
            }
        })
        .catch(function () {
            showPageMessage(<?= json_encode($t('network_error_sending')) ?>, 'danger');
        })
        .finally(function () {
            sendBtn.innerHTML = '<i class="bi bi-send-fill"></i> <?= htmlspecialchars($t('send_reply')) ?>';
            commWordCount(textarea);
        });
}
function showPageMessage(message, type = 'success') {
  const existing = document.getElementById('pageMessageToast');
  if (existing) {
    existing.remove();
  }

  const box = document.createElement('div');
  box.id = 'pageMessageToast';
  box.className = 'alert alert-' + type + ' position-fixed top-0 end-0 m-3 shadow';
  box.style.zIndex = '9999';
  box.style.minWidth = '260px';
  box.textContent = message;

  document.body.appendChild(box);

  setTimeout(function () {
    box.remove();
  }, 3000);
}

function openAdvisorConfirmModal(message, options = {}) {
    const modalElement = document.getElementById('advisorConfirmModal');
    const messageElement = document.getElementById('advisorConfirmMessage');
    const confirmButton = document.getElementById('advisorConfirmButton');

    if (!modalElement || !messageElement || !confirmButton) {
        return;
    }

    if (!advisorConfirmModalInstance) {
        advisorConfirmModalInstance = new bootstrap.Modal(modalElement);
    }

    messageElement.textContent = String(message ?? '').trim() || <?= json_encode($t('confirm_continue')) ?>;
    confirmButton.textContent = options.confirmLabel || <?= json_encode($t('confirm')) ?>;

    pendingAdvisorConfirmCallback = typeof options.onConfirm === 'function' ? options.onConfirm : null;

    confirmButton.onclick = function () {
        if (advisorConfirmModalInstance) {
            advisorConfirmModalInstance.hide();
        }

        const callback = pendingAdvisorConfirmCallback;
        pendingAdvisorConfirmCallback = null;

        if (callback) {
            callback(true);
        }
    };

    modalElement.addEventListener('hidden.bs.modal', function clearAdvisorConfirmState() {
        pendingAdvisorConfirmCallback = null;
        modalElement.removeEventListener('hidden.bs.modal', clearAdvisorConfirmState);
    });

    advisorConfirmModalInstance.show();
}

function customConfirm(message, callback) {
    openAdvisorConfirmModal(message, { onConfirm: callback });
}

document.addEventListener("DOMContentLoaded", function () {
    const advisorReasonModalEl = document.getElementById('advisorReasonModal');
    if (advisorReasonModalEl) {
        advisorReasonModal = new bootstrap.Modal(advisorReasonModalEl);
    }

    document.querySelectorAll('.advisor-reason-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            openAdvisorReasonModal(
                btn.getAttribute('data-reason-title'),
                btn.getAttribute('data-reason-content')
            );
        });
    });

    const params = new URLSearchParams(window.location.search);
    const section = params.get("section");

    if (section) {
        const btn = document.querySelector('.tab-btn[data-section="' + section + '"]');
        const panel = document.getElementById('section-' + section);

        if (btn && panel) {
            document.querySelectorAll('.tab-btn').forEach(function (b) {
                b.classList.remove('active');
            });

            document.querySelectorAll('.section-panel').forEach(function (p) {
                p.classList.remove('active');
            });

            btn.classList.add('active');
            panel.classList.add('active');
        }
    }

    document.querySelectorAll('.tab-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const sectionName = btn.getAttribute('data-section');

            document.querySelectorAll('.tab-btn').forEach(function (b) {
                b.classList.remove('active');
            });

            document.querySelectorAll('.section-panel').forEach(function (p) {
                p.classList.remove('active');
            });

            btn.classList.add('active');

            const targetPanel = document.getElementById('section-' + sectionName);
            if (targetPanel) {
                targetPanel.classList.add('active');
            }

            const url = new URL(window.location);
            url.searchParams.set('section', sectionName);
            window.history.replaceState({}, '', url);

            if (sectionName === 'communications') {
                const firstStudent = document.querySelector('.comm-student-item');
                if (firstStudent && commLoadedForStudent === 0) {
                    firstStudent.click();
                }
            }

            if (sectionName === 'calendar') {
                renderAdvisorCalendar();
            }
        });
    });

    const requestSearch = document.getElementById('requestSearch');
    if (requestSearch) {
        requestSearch.addEventListener('input', function () {
            const q = this.value.toLowerCase();
            document.querySelectorAll('.request-row').forEach(function (row) {
                row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        });
    }

    const studentSearch = document.getElementById('studentSearch');
    if (studentSearch) {
        studentSearch.addEventListener('input', function () {
            const q = this.value.toLowerCase();
            document.querySelectorAll('.student-row').forEach(function (row) {
                row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        });
    }

    document.querySelectorAll('.comm-student-item').forEach(function (item) {
        item.addEventListener('click', function () {
            const studentId = parseInt(item.getAttribute('data-student-id') || '0', 10);
            if (!studentId) return;

            commActiveStudentId = studentId;
            commLoadedForStudent = studentId;

            commSetActiveStudent(item);
            commLoadThread(studentId);
            commWordCount(document.getElementById('commTextarea'));
        });
    });

    document.querySelectorAll('.open-decline-modal-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const requestId = this.getAttribute('data-request-id');
            const input = document.getElementById('declineRequestId');
            if (input) {
                input.value = requestId;
            }
        });
    });

    if (document.getElementById('section-communications')?.classList.contains('active')) {
        const firstStudent = document.querySelector('.comm-student-item');
        if (firstStudent) {
            firstStudent.click();
        }
    }

    if (document.getElementById('section-calendar')?.classList.contains('active')) {
        renderAdvisorCalendar();
    }
});
</script>

</body>
</html>

<?php
/*
   NAME: Student Appointment Dashboard
   Description: This page displays the student dashboard for booking appointments, viewing pending requests, approved appointments and appointment history
   Panteleimoni Alexandrou
   30-Mar-2026 v1.8
   Inputs: Section parameter from URL, session flash messages and database records for available slots, requests, appointments and history
   Outputs: Student dashboard interface with real database data
   Error Messages: If database fetch fails, an error message is displayed inside the relevant section
  Files in use: StudentAppointmentDashboard.php, StudentBookAppointment.php, databaseconnect.php

   30-Mar-2026 v1.8
   Added booking submit integration and appointments fallback logic
   Panteleimoni Alexandrou

   19-Apr-2026 v1.9
   Added database notifications display panel for appointment-related user notifications
   Panteleimoni Alexandrou

   19-Apr-2026 v2.0
   Moved database notifications into top-navbar bell dropdown and added notification item redirects
   Panteleimoni Alexandrou

   19-Apr-2026 v2.1
   Added mark-as-read functionality for notifications on click
   Panteleimoni Alexandrou

   19-Apr-2026 v2.2
   Added small EN/EL language toggle for appointment dashboard interface text
   Panteleimoni Alexandrou

   19-Apr-2026 v2.3
   Expanded EN/EL translation coverage for full appointment dashboard interface text
   Panteleimoni Alexandrou

   19-Apr-2026 v2.4
   Completed remaining EN/EL translation coverage for communications, tables, counters and notification labels
   Panteleimoni Alexandrou

   19-Apr-2026 v2.5
   Added FullCalendar localization (EN/EL) based on dashboard session language
   Panteleimoni Alexandrou

   20-Apr-2026 v2.6
   Fixed logout form CSRF submission so logout redirects correctly without dispatcher validation errors
   Panteleimoni Alexandrou

   20-Apr-2026 v2.7
   Changed student availability rendering to unified concrete recurring/additional slot list
   Panteleimoni Alexandrou

   20-Apr-2026 v2.8
   Changed student booking flow to submit concrete recurring/additional slot selections without free date input
   Panteleimoni Alexandrou

   20-Apr-2026 v2.9
   Removed obsolete New Request button from student booking section after concrete slot booking flow update
   Panteleimoni Alexandrou

   20-Apr-2026 v3.0
   Updated student booking flow so recurring slots keep advisor-defined times and only require date selection, while additional slots remain fully fixed
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
  $redirectUrl = basename((string)($_SERVER['PHP_SELF'] ?? 'StudentAppointmentDashboard.php'));
  if ($redirectParams !== []) {
    $redirectUrl .= '?' . http_build_query($redirectParams);
  }
  header('Location: ' . $redirectUrl);
  exit();
}

require_once __DIR__ . '/init.php';
require_once __DIR__ . '/../backend/modules/UsersClass.php';
require_once __DIR__ . '/../backend/modules/StudentClass.php';
require_once __DIR__ . '/../backend/modules/NotificationsClass.php';
require_once __DIR__ . '/../backend/modules/Csrf.php';

$user = new Users();
$user->Check_Session('Student');

require_once __DIR__ . '/../backend/modules/databaseconnect.php';

$pdo = ConnectToDatabase();
$csrfToken = Csrf::ensureToken();

/*
login/session of student user
*/
$studentId = isset($_SESSION['UserID']) && is_numeric($_SESSION['UserID'])
  ? (int)$_SESSION['UserID']
  : 0;

if ($studentId > 0 && isset($_GET['notification_id']) && is_numeric($_GET['notification_id'])) {
  try {
    $markNotificationSql = "UPDATE notifications
                            SET Is_Read = 1
                            WHERE Notification_ID = :notification_id
                              AND Recipient_ID = :recipient_id";

    $markNotificationStmt = $pdo->prepare($markNotificationSql);
    $markNotificationStmt->execute([
      'notification_id' => (int)$_GET['notification_id'],
      'recipient_id' => $studentId
    ]);
  } catch (Throwable $e) {
    error_log('StudentAppointmentDashboard mark notification read error: ' . $e->getMessage());
  }
}

$lang = isset($_SESSION['appointment_dashboard_lang']) && in_array($_SESSION['appointment_dashboard_lang'], ['en', 'el'], true)
  ? (string)$_SESSION['appointment_dashboard_lang']
  : 'en';

$translations = [
  'en' => [
    'page_title' => 'Student Appointment Portal',
    'welcome' => 'Welcome to AdviCut, %s! 👋',
    'notifications' => 'Notifications',
    'notifications_subtitle' => 'Appointment-related updates',
    'no_notifications' => 'No notifications yet.',
    'unread' => 'Unread',
    'manual' => 'Manual',
    'change_password' => 'Change Password',
    'logout' => 'Logout',
    'manual_title' => 'Student Dashboard Manual',
    'manual_item_1' => 'Use Book Appointment to schedule meetings with your advisor based on the available slots.',
    'manual_item_2' => 'Use My Requests to view the status of your appointment requests.',
    'manual_item_3' => 'Use My Appointments to see upcoming confirmed/denied appointments.',
    'manual_item_4' => 'Use Appointment History to review past appointments and notes.',
    'manual_item_5' => 'Use Communications to message your assigned advisor.',
    'manual_item_6' => 'Use Change Password to update your login password.',
    'manual_item_7' => 'If no advisor is assigned yet, contact the administration office.',
    'close' => 'Close',
    'tab_calendar' => 'Calendar',
    'tab_book' => 'Book Appointment',
    'tab_requests' => 'My Requests',
    'tab_appointments' => 'My Appointments',
    'tab_history' => 'History',
    'tab_communications' => 'Communications',
    'book_title' => 'Book Appointment',
    'book_subtitle' => 'Select an available advisor slot and request a meeting',
    'new_request' => 'New Request',
        'requests_title' => 'My Requests'
  ],
  'el' => [
    'page_title' => 'Πύλη Ραντεβού Φοιτητή',
    'welcome' => 'Καλώς ήρθες στο AdviCut, %s! 👋',
    'notifications' => 'Ειδοποιήσεις',
    'notifications_subtitle' => 'Ενημερώσεις σχετικές με ραντεβού',
    'no_notifications' => 'Δεν υπάρχουν ειδοποιήσεις ακόμη.',
    'unread' => 'Μη αναγνωσμένο',
    'manual' => 'Οδηγός',
    'change_password' => 'Αλλαγή Κωδικού',
    'logout' => 'Αποσύνδεση',
    'manual_title' => 'Οδηγός Πίνακα Φοιτητή',
    'manual_item_1' => 'Χρησιμοποιήστε την Κράτηση Ραντεβού για να προγραμματίσετε συνάντηση με τον σύμβουλό σας.',
    'manual_item_2' => 'Χρησιμοποιήστε τα Αιτήματά Μου για να δείτε την κατάσταση των αιτημάτων σας.',
    'manual_item_3' => 'Χρησιμοποιήστε τα Ραντεβού Μου για να δείτε επερχόμενα επιβεβαιωμένα ή απορριφθέντα ραντεβού.',
    'manual_item_4' => 'Χρησιμοποιήστε το Ιστορικό Ραντεβού για να δείτε παλαιότερα ραντεβού και σημειώσεις.',
    'manual_item_5' => 'Χρησιμοποιήστε τις Επικοινωνίες για να στείλετε μήνυμα στον σύμβουλό σας.',
    'manual_item_6' => 'Χρησιμοποιήστε την Αλλαγή Κωδικού για να ενημερώσετε τον κωδικό σας.',
    'manual_item_7' => 'Αν δεν έχει οριστεί σύμβουλος, επικοινωνήστε με τη διοίκηση.',
    'close' => 'Κλείσιμο',
    'tab_calendar' => 'Ημερολόγιο',
    'tab_book' => 'Κράτηση Ραντεβού',
    'tab_requests' => 'Τα Αιτήματά Μου',
    'tab_appointments' => 'Τα Ραντεβού Μου',
    'tab_history' => 'Ιστορικό',
    'tab_communications' => 'Επικοινωνίες',
    'book_title' => 'Κράτηση Ραντεβού',
    'book_subtitle' => 'Επιλέξτε διαθέσιμη ώρα συμβούλου και ζητήστε συνάντηση',
    'new_request' => 'Νέο Αίτημα',
    'requests_title' => 'Τα Αιτήματά Μου'
  ]
];

$translations['en'] = array_merge($translations['en'], [
  'welcome' => 'Welcome to AdviCut, %s! 👋',
  'requests_subtitle' => 'View all your pending appointment requests',
  'search_requests' => 'Search requests...',
  'advisor' => 'Advisor',
  'day' => 'Day',
  'start_time' => 'Start Time',
  'end_time' => 'End Time',
  'status' => 'Status',
  'action' => 'Action',
  'assigned_advisor' => 'Assigned Advisor',
  'request_status' => 'Request Status',
  'track_pending_requests' => 'Track pending and approved requests',
  'no_office_hours_loaded' => 'No office hour slots loaded yet',
  'available' => 'Available',
  'book' => 'Book',
  'date' => 'Date',
  'reason' => 'Reason',
  'decline_reason' => 'Decline Reason',
  'no_pending_requests_loaded' => 'No pending requests loaded yet',
  'pending' => 'Pending',
  'my_appointments_title' => 'My Appointments',
  'my_appointments_subtitle' => 'View all approved appointments with your advisor',
  'no_approved_appointments' => 'No approved appointments loaded yet',
  'scheduled' => 'Scheduled',
  'completed' => 'Completed',
  'cancelled' => 'Cancelled',
  'approved' => 'Approved',
  'history_title' => 'Appointment History',
  'history_subtitle' => 'View previous appointment actions and decisions',
  'details' => 'Details',
  'no_history_loaded' => 'No history loaded yet',
  'declined' => 'Declined',
  'view_details' => 'View details',
  'calendar_title' => 'Appointment Calendar',
  'calendar_subtitle' => 'Track all your requests and appointment decisions by date',
  'communications_title' => 'Communications',
  'communications_subtitle' => 'Send and receive messages from your academic advisor.',
  'no_advisor_assigned' => "You don't have an advisor assigned yet.<br>Please contact the administration.",
  'loading_messages' => 'Loading messages...',
  'send_message_to' => 'Send a message to %s',
  'words_max' => '(200 words max)',
  'message_placeholder' => 'Type your question or message here...',
  'send_message' => 'Send Message',
  'book_modal_title' => 'Book Appointment',
  'available_slot' => 'Available Slot',
  'select_slot' => 'Select a slot...',
  'appointment_date' => 'Appointment Date',
  'reason_for_appointment' => 'Reason for Appointment',
  'request_reason_placeholder' => 'Write the reason for your appointment request...',
  'cancel' => 'Cancel',
  'send_request' => 'Send Request',
  'appointment_details' => 'Appointment Details',
  'time' => 'Time',
  'your_reason' => 'Your Reason',
  'view_reason' => 'View Reason',
  'advisor_reason' => 'Advisor Reason',
  'notifications_aria' => 'Notifications',
  'could_not_load_notifications' => 'Could not load notifications.',
  'words_suffix' => 'words',
  'no_messages_yet' => 'No messages yet. Send your first message to your advisor!',
  'failed_to_load_messages' => 'Failed to load messages. Please refresh the page.',
  'sending' => 'Sending...',
  'failed_to_send_message' => 'Failed to send message. Please try again.',
  'network_error_sending' => 'Network error. Please try again.',
  'you' => 'You'
]);

$translations['el'] = array_merge($translations['el'], [
  'welcome' => 'Καλώς ήρθες στο AdviCut, %s! 👋',
  'requests_subtitle' => 'Δείτε όλα τα εκκρεμή αιτήματα ραντεβού σας',
  'search_requests' => 'Αναζήτηση αιτημάτων...',
  'advisor' => 'Σύμβουλος',
  'day' => 'Ημέρα',
  'start_time' => 'Ώρα Έναρξης',
  'end_time' => 'Ώρα Λήξης',
  'status' => 'Κατάσταση',
  'action' => 'Ενέργεια',
  'assigned_advisor' => 'Ανατεθειμένος Σύμβουλος',
  'request_status' => 'Κατάσταση Αιτημάτων',
  'track_pending_requests' => 'Παρακολουθήστε εκκρεμή και εγκεκριμένα αιτήματα',
  'no_office_hours_loaded' => 'Δεν έχουν φορτωθεί διαθέσιμες ώρες γραφείου ακόμη',
  'available' => 'Διαθέσιμο',
  'book' => 'Κράτηση',
  'date' => 'Ημερομηνία',
  'reason' => 'Λόγος',
  'decline_reason' => 'Λόγος Απόρριψης',
  'no_pending_requests_loaded' => 'Δεν έχουν φορτωθεί εκκρεμή αιτήματα ακόμη',
  'pending' => 'Εκκρεμεί',
  'my_appointments_title' => 'Τα Ραντεβού Μου',
  'my_appointments_subtitle' => 'Δείτε όλα τα εγκεκριμένα ραντεβού με τον σύμβουλό σας',
  'no_approved_appointments' => 'Δεν έχουν φορτωθεί εγκεκριμένα ραντεβού ακόμη',
  'scheduled' => 'Προγραμματισμένο',
  'completed' => 'Ολοκληρώθηκε',
  'cancelled' => 'Ακυρώθηκε',
  'approved' => 'Εγκρίθηκε',
  'history_title' => 'Ιστορικό Ραντεβού',
  'history_subtitle' => 'Δείτε προηγούμενες ενέργειες και αποφάσεις ραντεβού',
  'details' => 'Λεπτομέρειες',
  'no_history_loaded' => 'Δεν έχει φορτωθεί ιστορικό ακόμη',
  'declined' => 'Απορρίφθηκε',
  'view_details' => 'Προβολή λεπτομερειών',
  'calendar_title' => 'Ημερολόγιο Ραντεβού',
  'calendar_subtitle' => 'Παρακολουθήστε όλα τα αιτήματα και τις αποφάσεις ραντεβού ανά ημερομηνία',
  'communications_title' => 'Επικοινωνίες',
  'communications_subtitle' => 'Στείλτε και λάβετε μηνύματα από τον ακαδημαϊκό σας σύμβουλο.',
  'no_advisor_assigned' => 'Δεν σας έχει ανατεθεί ακόμη σύμβουλος.<br>Παρακαλώ επικοινωνήστε με τη διοίκηση.',
  'loading_messages' => 'Φόρτωση μηνυμάτων...',
  'send_message_to' => 'Στείλτε μήνυμα προς %s',
  'words_max' => '(μέχρι 200 λέξεις)',
  'message_placeholder' => 'Πληκτρολογήστε την ερώτηση ή το μήνυμά σας εδώ...',
  'send_message' => 'Αποστολή Μηνύματος',
  'book_modal_title' => 'Κράτηση Ραντεβού',
  'available_slot' => 'Διαθέσιμη Ώρα',
  'select_slot' => 'Επιλέξτε ώρα...',
  'appointment_date' => 'Ημερομηνία Ραντεβού',
  'reason_for_appointment' => 'Λόγος Ραντεβού',
  'request_reason_placeholder' => 'Γράψτε τον λόγο για το αίτημα ραντεβού σας...',
  'cancel' => 'Ακύρωση',
  'send_request' => 'Αποστολή Αιτήματος',
  'appointment_details' => 'Λεπτομέρειες Ραντεβού',
  'time' => 'Ώρα',
  'your_reason' => 'Ο Δικός Σας Λόγος',
  'view_reason' => 'Προβολή Λόγου',
  'advisor_reason' => 'Λόγος Συμβούλου'
]);

$translations['en'] = array_merge($translations['en'], [
  'notifications_aria' => 'Notifications',
  'could_not_load_notifications' => 'Could not load notifications.',
  'could_not_load_available_slots' => 'Could not load available office hour slots.',
  'could_not_load_requests' => 'Could not load appointment requests.',
  'could_not_load_appointments' => 'Could not load student appointments.',
  'could_not_load_history' => 'Could not load appointment history.',
  'could_not_load_calendar' => 'Could not load calendar events.',
  'words_suffix' => 'words',
  'no_messages_yet' => 'No messages yet. Send your first message to your advisor!',
  'failed_to_load_messages' => 'Failed to load messages. Please refresh the page.',
  'you' => 'You'
]);

$translations['el'] = array_merge($translations['el'], [
  'notifications_aria' => 'Ειδοποιήσεις',
  'could_not_load_notifications' => 'Δεν ήταν δυνατή η φόρτωση ειδοποιήσεων.',
  'could_not_load_available_slots' => 'Δεν ήταν δυνατή η φόρτωση διαθέσιμων ωρών γραφείου.',
  'could_not_load_requests' => 'Δεν ήταν δυνατή η φόρτωση αιτημάτων ραντεβού.',
  'could_not_load_appointments' => 'Δεν ήταν δυνατή η φόρτωση ραντεβού φοιτητή.',
  'could_not_load_history' => 'Δεν ήταν δυνατή η φόρτωση ιστορικού ραντεβού.',
  'could_not_load_calendar' => 'Δεν ήταν δυνατή η φόρτωση γεγονότων ημερολογίου.',
  'words_suffix' => 'λέξεις',
  'no_messages_yet' => 'Δεν υπάρχουν μηνύματα ακόμη. Στείλτε το πρώτο σας μήνυμα στον σύμβουλό σας!',
  'failed_to_load_messages' => 'Η φόρτωση μηνυμάτων απέτυχε. Παρακαλώ ανανεώστε τη σελίδα.',
  'sending' => 'Αποστολή...',
  'failed_to_send_message' => 'Η αποστολή μηνύματος απέτυχε. Παρακαλώ δοκιμάστε ξανά.',
  'network_error_sending' => 'Σφάλμα δικτύου. Παρακαλώ δοκιμάστε ξανά.',
  'you' => 'Εσείς'
]);

$translations['en'] = array_merge($translations['en'], [
  'book_subtitle' => 'Choose from your advisor\'s upcoming concrete availability slots',
  'type' => 'Type',
  'recurring' => 'Recurring',
  'additional' => 'Additional',
  'no_available_slots_loaded' => 'No available slots loaded yet',
  'additional_booking_next_phase' => 'Additional slot booking will be enabled in the next phase.'
]);

$translations['el'] = array_merge($translations['el'], [
  'book_subtitle' => 'Επιλέξτε από τα επερχόμενα συγκεκριμένα διαθέσιμα slots του συμβούλου σας',
  'type' => 'Τύπος',
  'recurring' => 'Επαναλαμβανόμενο',
  'additional' => 'Επιπλέον',
  'no_available_slots_loaded' => 'Δεν έχουν φορτωθεί διαθέσιμα slots ακόμη',
  'additional_booking_next_phase' => 'Η κράτηση επιπλέον slot θα ενεργοποιηθεί στο επόμενο phase.'
]);

$translations['en'] = array_merge($translations['en'], [
  'selected_slot' => 'Selected Slot',
  'selected_time' => 'Selected Time',
  'selected_type' => 'Type',
  'no_slot_selected' => 'No slot selected yet.'
]);

$translations['el'] = array_merge($translations['el'], [
  'selected_slot' => 'Επιλεγμένο Slot',
  'selected_time' => 'Επιλεγμένη Ώρα',
  'selected_type' => 'Τύπος',
  'no_slot_selected' => 'Δεν έχει επιλεγεί slot ακόμη.'
]);

$translations['en'] = array_merge($translations['en'], [
  'book_subtitle' => 'Choose from your advisor\'s recurring weekly hours or fixed additional slots',
  'recurring_slots' => 'Recurring Slots',
  'additional_slots' => 'Additional Slots',
  'selected_day' => 'Selected Day',
  'selected_date' => 'Selected Date',
  'choose_date' => 'Choose Date',
  'no_recurring_slots_loaded' => 'No recurring slots loaded yet',
  'no_additional_slots_loaded' => 'No additional slots found'
]);

$translations['el'] = array_merge($translations['el'], [
  'book_subtitle' => 'Επιλέξτε από τις επαναλαμβανόμενες ώρες ή τα σταθερά επιπλέον slots του συμβούλου σας',
  'recurring_slots' => 'Επαναλαμβανόμενα Slots',
  'additional_slots' => 'Επιπλέον Slots',
  'selected_day' => 'Επιλεγμένη Ημέρα',
  'selected_date' => 'Επιλεγμένη Ημερομηνία',
  'choose_date' => 'Επιλογή Ημερομηνίας',
  'no_recurring_slots_loaded' => 'Δεν έχουν φορτωθεί επαναλαμβανόμενα slots ακόμη',
  'no_additional_slots_loaded' => 'Δεν βρέθηκαν επιπλέον slots'
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

  $path = basename((string)($_SERVER['PHP_SELF'] ?? 'StudentAppointmentDashboard.php'));
  return $path . ($params !== [] ? '?' . http_build_query($params) : '');
};

$toggleLang = $lang === 'en' ? 'el' : 'en';
$toggleUrl = $buildCurrentUrl(['set_lang' => $toggleLang], ['notification_id']);
$notificationReturnUrl = $buildCurrentUrl([], ['notification_id']);
$langButtonLabel = $lang === 'en' ? 'EN / EL' : 'EL / EN';
  

$currentStudent = [];
$myAdvisor = null;
$studentExternalId = 0;
$studentName = 'Student';

if ($studentId > 0) {
  try {
    $studentModule = new StudentClass();
    $currentStudent = $studentModule->getStudentInfo($studentId);
    $myAdvisor = $studentModule->getStudentAdvisor($studentId);
    $studentExternalId = isset($currentStudent['Student_ID']) && is_numeric($currentStudent['Student_ID'])
      ? (int)$currentStudent['Student_ID']
      : 0;
  } catch (Throwable $e) {
    error_log('Error loading student dashboard info: ' . $e->getMessage());
    $currentStudent = [];
    $myAdvisor = null;
    $studentExternalId = 0;
  }
}

$studentFirstName = trim((string)($currentStudent['First_name'] ?? ''));
if ($studentFirstName !== '') {
  $studentName = $studentFirstName;
} elseif (!empty($_SESSION['First_name']) && is_string($_SESSION['First_name'])) {
  $studentName = trim((string)$_SESSION['First_name']);
}

// Get active section from URL
$activeSection = isset($_GET['section']) ? $_GET['section'] : 'calendar';

// Student advisor mapping
$advisorId = null;
$advisorName = 'Assigned Advisor';

// Available office hours for booking
$recurringSlots = [];
$additionalSlots = [];
$availableSlotsError = '';

// Student pending requests
$studentRequests = [];
$studentRequestsError = '';

// Student appointments
$studentAppointments = [];
$studentAppointmentsError = '';

// Student history
$studentHistory = [];
$studentHistoryError = '';

// Student calendar events
$studentCalendarEvents = [];
$studentCalendarError = '';

$studentNotifications = [];
$studentNotificationsError = '';
$studentUnreadNotifications = 0;

/*
------------------------------------------------------------
FETCH STUDENT ADVISOR
------------------------------------------------------------
*/
try {
  if (is_array($myAdvisor) && isset($myAdvisor['User_ID'])) {
    $advisorId = (int)$myAdvisor['User_ID'];
    $advisorName = trim((string)($myAdvisor['First_name'] ?? '') . ' ' . (string)($myAdvisor['Last_Name'] ?? ''));
    if ($advisorName === '') {
      $advisorName = 'Assigned Advisor';
    }
  }
} catch (Throwable $e) {
  $advisorId = null;
}

/*
------------------------------------------------------------
FETCH AVAILABLE OFFICE HOUR SLOTS
------------------------------------------------------------
*/
$studentModule = new StudentClass();

if ($advisorId !== null) {
  try {
    $todayDate = date('Y-m-d');
    $currentTime = date('H:i:s');

    $blockedAdditionalSlots = $studentModule->getBlockedAdditionalSlots();

    $recurringRows = $studentModule->getAvailableRecurringSlots($advisorId);

    foreach ($recurringRows as $row) {
      $recurringSlots[] = [
        'slot_source' => 'recurring',
        'slot_id' => (int)$row['OfficeHour_ID'],
        'day_of_week' => (string)$row['Day_of_Week'],
        'start_time' => (string)$row['Start_Time'],
        'end_time' => (string)$row['End_Time'],
        'type_label' => $t('recurring')
      ];
    }

    $additionalRows = $studentModule->getAvailableAdditionalSlots($advisorId);

    foreach ($additionalRows as $row) {
      $slotDate = (string)$row['Slot_Date'];
      $endTime = (string)$row['End_Time'];
      $slotId = (int)$row['AdditionalSlot_ID'];

      if ($slotDate === $todayDate && $endTime <= $currentTime) {
        continue;
      }

      if (isset($blockedAdditionalSlots[$slotId])) {
        continue;
      }

      $additionalSlots[] = [
        'slot_source' => 'additional',
        'slot_id' => $slotId,
        'slot_date' => $slotDate,
        'start_time' => (string)$row['Start_Time'],
        'end_time' => (string)$row['End_Time'],
        'type_label' => $t('additional')
      ];
    }

    usort($recurringSlots, static function (array $a, array $b): int {
      $timeCompare = strcmp((string)$a['start_time'], (string)$b['start_time']);
      if ($timeCompare !== 0) {
        return $timeCompare;
      }

      return strcmp((string)$a['day_of_week'], (string)$b['day_of_week']);
    });

    usort($additionalSlots, static function (array $a, array $b): int {
      $dateCompare = strcmp((string)$a['slot_date'], (string)$b['slot_date']);
      if ($dateCompare !== 0) {
        return $dateCompare;
      }

      return strcmp((string)$a['start_time'], (string)$b['start_time']);
    });
  } catch (Throwable $e) {
    $recurringSlots = [];
    $additionalSlots = [];
    $availableSlotsError = $t('could_not_load_available_slots');
  }
} else {
    $availableSlotsError = 'No advisor is assigned to this student.';
}

$studentMessageUserId = (int)($currentStudent['User_ID'] ?? $studentId);
$communicationAdvisorLabel = !empty($myAdvisor)
  ? trim((string)($myAdvisor['First_name'] ?? '') . ' ' . (string)($myAdvisor['Last_Name'] ?? ''))
  : $advisorName;

/*
------------------------------------------------------------
FETCH ONLY PENDING STUDENT REQUESTS
------------------------------------------------------------
*/
try {
    $studentRequests = $studentModule->getPendingRequests($studentId);
} catch (Throwable $e) {
    $studentRequestsError = $t('could_not_load_requests');
}

/*
------------------------------------------------------------
FETCH STUDENT APPOINTMENTS
First try appointments table.
If no rows are found, fallback to approved requests.
------------------------------------------------------------
*/
try {
  $studentAppointments = $studentModule->getApprovedAppointments($studentId);
} catch (Throwable $e) {
    $studentAppointmentsError = $t('could_not_load_appointments');
}

/*
------------------------------------------------------------
FETCH STUDENT HISTORY
Use non-pending request records as history for now
------------------------------------------------------------
*/
try {
    $studentHistory = $studentModule->getAppointmentHistory($studentId);
} catch (Throwable $e) {
    $studentHistoryError = $t('could_not_load_history');
}

/*
------------------------------------------------------------
FETCH STUDENT CALENDAR EVENTS
------------------------------------------------------------
*/
try {
  $calendarRows = $studentModule->getCalendarEvents($studentId);

  foreach ($calendarRows as $row) {
    $status = (string)($row['Status'] ?? 'Pending');
    $advisorFullName = trim(
      (string)($row['Advisor_First_Name'] ?? '') . ' ' .
      (string)($row['Advisor_Last_Name'] ?? '')
    );

    $title = $advisorFullName !== '' ? $advisorFullName : 'Appointment';

    $eventColor = '#6c757d';
    if ($status === 'Pending') $eventColor = '#f0ad4e';
    if ($status === 'Approved') $eventColor = '#198754';
    if ($status === 'Declined') $eventColor = '#dc3545';
    if ($status === 'Cancelled') $eventColor = '#212529';

    $studentCalendarEvents[] = [
      'id' => (int)($row['Request_ID'] ?? 0),
      'title' => $title . ' (' . $status . ')',
      'start' => (string)($row['Appointment_Date'] ?? ''),
      'backgroundColor' => $eventColor,
      'borderColor' => $eventColor,
      'extendedProps' => [
        'advisor' => $advisorFullName,
        'date' => (string)($row['Appointment_Date'] ?? ''),
        'time' => (string)($row['Start_Time'] ?? '') . ' - ' . (string)($row['End_Time'] ?? ''),
        'student_reason' => (string)($row['Student_Reason'] ?? ''),
        'advisor_reason' => (string)($row['Advisor_Reason'] ?? ''),
        'status' => $status
      ]
    ];
  }
} catch (Throwable $e) {
  $studentCalendarError = $t('could_not_load_calendar');
}

try {
  $studentNotifications = Notifications::getStudentNotifications($studentId);

  foreach ($studentNotifications as &$notification) {
    if ((int)($notification['Is_Read'] ?? 0) === 0) {
      $studentUnreadNotifications++;
    }
  }
  unset($notification);
} catch (Throwable $e) {
  $studentNotificationsError = $t('could_not_load_notifications');
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($t('page_title')) ?></title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
  <link rel="stylesheet" href="css/student_appointment_dashboard.css">

</head>
<body>
<?php Notifications::createNotification(); 
?>

<header class="top-navbar">
  <img src="../documents/tepaklogo.png" alt="Logo" class="logo">

  <div class="navbar-center">
    <span class="welcome-text"><?= htmlspecialchars(sprintf($t('welcome'), $studentName)) ?></span>
  </div>

  <div class="d-flex align-items-center gap-3">
    <div class="dropdown">
      <button class="btn position-relative p-0 border-0 bg-transparent" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="<?= htmlspecialchars($t('notifications_aria')) ?>">
        <i class="bi bi-bell fs-5 text-dark"></i>
        <?php if ($studentUnreadNotifications > 0): ?>
          <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
            <?= $studentUnreadNotifications > 99 ? '99+' : $studentUnreadNotifications ?>
          </span>
        <?php endif; ?>
      </button>
      <div class="dropdown-menu dropdown-menu-end p-0 shadow border-0" style="width: 360px; max-width: calc(100vw - 32px);">
        <div class="px-3 py-2 border-bottom">
          <div class="fw-semibold"><?= htmlspecialchars($t('notifications')) ?></div>
          <div class="text-muted" style="font-size:.8rem;"><?= htmlspecialchars($t('notifications_subtitle')) ?></div>
        </div>

        <?php if ($studentNotificationsError !== ''): ?>
          <div class="px-3 py-3 text-danger small"><?= htmlspecialchars($studentNotificationsError) ?></div>
        <?php elseif (count($studentNotifications) === 0): ?>
          <div class="px-3 py-3 text-muted small"><?= htmlspecialchars($t('no_notifications')) ?></div>
        <?php else: ?>
          <div style="max-height: 360px; overflow-y: auto;">
            <?php foreach ($studentNotifications as $notification): ?>
              <?php $isUnread = (int)($notification['Is_Read'] ?? 0) === 0; ?>
              <?php
              $redirectUrl = (string)($notification['Redirect_URL'] ?? 'StudentAppointmentDashboard.php?section=calendar');
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
          <li><?= htmlspecialchars($t('manual_item_5')) ?></li>
          <li><?= htmlspecialchars($t('manual_item_6')) ?></li>
          <li><?= htmlspecialchars($t('manual_item_7')) ?></li>
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

  <button type="button" class="tab-btn <?= $activeSection === 'book' ? 'active' : '' ?>" data-section="book">
    <i class="bi bi-calendar-plus"></i> <?= htmlspecialchars($t('tab_book')) ?>
  </button>

  <button type="button" class="tab-btn <?= $activeSection === 'requests' ? 'active' : '' ?>" data-section="requests">
    <i class="bi bi-hourglass-split"></i> <?= htmlspecialchars($t('tab_requests')) ?>
  </button>

  <button type="button" class="tab-btn <?= $activeSection === 'appointments' ? 'active' : '' ?>" data-section="appointments">
    <i class="bi bi-calendar-check"></i> <?= htmlspecialchars($t('tab_appointments')) ?>
  </button>

  <button type="button" class="tab-btn <?= $activeSection === 'history' ? 'active' : '' ?>" data-section="history">
    <i class="bi bi-clock-history"></i> <?= htmlspecialchars($t('tab_history')) ?>
  </button>

  <button type="button" class="tab-btn <?= $activeSection === 'communications' ? 'active' : '' ?>" data-section="communications">
    <i class="bi bi-chat-dots"></i> <?= htmlspecialchars($t('tab_communications')) ?>
  </button>
</div>

<main class="container-fluid py-4 px-4" style="max-width: 1100px;">
  <!-- Book Appointment tab -->
  <div class="section-panel <?= $activeSection === 'book' ? 'active' : '' ?>" id="section-book">
    <div class="section-card">

      <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
          <h5 class="mb-0 fw-semibold"><?= htmlspecialchars($t('book_title')) ?></h5>
          <p class="text-muted mb-0" style="font-size:.85rem;"><?= htmlspecialchars($t('book_subtitle')) ?></p>
        </div>
      </div>

<div class="row g-3 mb-4">

  <div class="col-12 col-md-6">
    <button type="button"
            class="info-box d-flex align-items-center gap-3 w-100 border-0 text-start"
            onclick="document.querySelector('.tab-btn[data-section=\'communications\']').click();">
      <div class="info-icon">
        <i class="bi bi-person-badge"></i>
      </div>
      <div>
        <div class="fw-semibold"><?= htmlspecialchars($t('assigned_advisor')) ?></div>
        <div class="text-muted small"><?= htmlspecialchars($advisorName) ?></div>
      </div>
    </button>
  </div>

  <div class="col-12 col-md-6">
    <button type="button"
            class="info-box d-flex align-items-center gap-3 w-100 border-0 text-start"
            onclick="document.querySelector('.tab-btn[data-section=\'requests\']').click();">
      <div class="info-icon">
        <i class="bi bi-send-check"></i>
      </div>
      <div>
        <div class="fw-semibold"><?= htmlspecialchars($t('request_status')) ?></div>
        <div class="text-muted small"><?= htmlspecialchars($t('track_pending_requests')) ?></div>
      </div>
    </button>
  </div>

</div>

      <?php if ($availableSlotsError !== ''): ?>
        <div class="alert alert-danger">
          <?= htmlspecialchars($availableSlotsError) ?>
        </div>
      <?php endif; ?>

      <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
          <h6 class="fw-semibold mb-3"><?= htmlspecialchars($t('recurring_slots')) ?></h6>
          <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th><?= htmlspecialchars($t('day')) ?></th>
                  <th><?= htmlspecialchars($t('start_time')) ?></th>
                  <th><?= htmlspecialchars($t('end_time')) ?></th>
                  <th><?= htmlspecialchars($t('type')) ?></th>
                  <th style="width:140px;"><?= htmlspecialchars($t('action')) ?></th>
                </tr>
              </thead>
              <tbody>
                <?php if (count($recurringSlots) === 0): ?>
                  <tr class="book-row">
                    <td colspan="5" class="text-center text-muted"><?= htmlspecialchars($t('no_recurring_slots_loaded')) ?></td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($recurringSlots as $slot): ?>
                    <tr class="book-row">
                      <td><?= htmlspecialchars((string)$slot['day_of_week']) ?></td>
                      <td><?= htmlspecialchars(substr((string)$slot['start_time'], 0, 5)) ?></td>
                      <td><?= htmlspecialchars(substr((string)$slot['end_time'], 0, 5)) ?></td>
                      <td><span class="badge bg-secondary"><?= htmlspecialchars($t('recurring')) ?></span></td>
                      <td>
                        <button type="button"
                                class="btn btn-primary btn-sm open-book-modal-btn"
                                data-slot-source="recurring"
                                data-slot-id="<?= (int)$slot['slot_id'] ?>"
                                data-slot-day="<?= htmlspecialchars((string)$slot['day_of_week']) ?>"
                                data-slot-start-time="<?= htmlspecialchars(substr((string)$slot['start_time'], 0, 5)) ?>"
                                data-slot-end-time="<?= htmlspecialchars(substr((string)$slot['end_time'], 0, 5)) ?>"
                                data-slot-type="<?= htmlspecialchars((string)$slot['type_label']) ?>"
                                data-bs-toggle="modal"
                                data-bs-target="#bookAppointmentModal">
                          <?= htmlspecialchars($t('book')) ?>
                        </button>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="card border-0 shadow-sm">
        <div class="card-body">
          <h6 class="fw-semibold mb-3"><?= htmlspecialchars($t('additional_slots')) ?></h6>
          <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th><?= htmlspecialchars($t('date')) ?></th>
                  <th><?= htmlspecialchars($t('start_time')) ?></th>
                  <th><?= htmlspecialchars($t('end_time')) ?></th>
                  <th><?= htmlspecialchars($t('type')) ?></th>
                  <th style="width:140px;"><?= htmlspecialchars($t('action')) ?></th>
                </tr>
              </thead>
              <tbody>
                <?php if (count($additionalSlots) === 0): ?>
                  <tr class="book-row">
                    <td colspan="5" class="text-center text-muted"><?= htmlspecialchars($t('no_additional_slots_loaded')) ?></td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($additionalSlots as $slot): ?>
                    <tr class="book-row">
                      <td><?= htmlspecialchars((string)$slot['slot_date']) ?></td>
                      <td><?= htmlspecialchars(substr((string)$slot['start_time'], 0, 5)) ?></td>
                      <td><?= htmlspecialchars(substr((string)$slot['end_time'], 0, 5)) ?></td>
                      <td><span class="badge bg-info text-dark"><?= htmlspecialchars($t('additional')) ?></span></td>
                      <td>
                        <button type="button"
                                class="btn btn-primary btn-sm open-book-modal-btn"
                                data-slot-source="additional"
                                data-slot-id="<?= (int)$slot['slot_id'] ?>"
                                data-slot-date="<?= htmlspecialchars((string)$slot['slot_date']) ?>"
                                data-slot-start-time="<?= htmlspecialchars(substr((string)$slot['start_time'], 0, 5)) ?>"
                                data-slot-end-time="<?= htmlspecialchars(substr((string)$slot['end_time'], 0, 5)) ?>"
                                data-slot-type="<?= htmlspecialchars((string)$slot['type_label']) ?>"
                                data-bs-toggle="modal"
                                data-bs-target="#bookAppointmentModal">
                          <?= htmlspecialchars($t('book')) ?>
                        </button>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>
  </div>

  <!-- My Requests tab -->
  <div class="section-panel <?= $activeSection === 'requests' ? 'active' : '' ?>" id="section-requests">
    <div class="section-card">

      <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
          <h5 class="mb-0 fw-semibold"><?= htmlspecialchars($t('requests_title')) ?></h5>
          <p class="text-muted mb-0" style="font-size:.85rem;"><?= htmlspecialchars($t('requests_subtitle')) ?></p>
        </div>
      </div>

      <?php if ($studentRequestsError !== ''): ?>
        <div class="alert alert-danger">
          <?= htmlspecialchars($studentRequestsError) ?>
        </div>
      <?php endif; ?>

      <input class="form-control mb-3" id="studentRequestSearch" placeholder="<?= htmlspecialchars($t('search_requests')) ?>">

      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th><?= htmlspecialchars($t('advisor')) ?></th>
              <th><?= htmlspecialchars($t('date')) ?></th>
              <th><?= htmlspecialchars($t('reason')) ?></th>
              <th><?= htmlspecialchars($t('status')) ?></th>
              <th><?= htmlspecialchars($t('decline_reason')) ?></th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($studentRequests) === 0): ?>
              <tr class="student-request-row">
                <td colspan="5" class="text-center text-muted"><?= htmlspecialchars($t('no_pending_requests_loaded')) ?></td>
              </tr>
            <?php else: ?>
              <?php foreach ($studentRequests as $request): ?>
                <?php
                  $requestAdvisorName = trim(
                    (string)($request['Advisor_First_Name'] ?? '') . ' ' .
                    (string)($request['Advisor_Last_Name'] ?? '')
                  );
                  $studentReason = trim((string)($request['Student_Reason'] ?? ''));
                  $declineReason = trim((string)($request['Advisor_Reason'] ?? ''));
                ?>
                <tr class="student-request-row">
                  <td><?= htmlspecialchars($requestAdvisorName !== '' ? $requestAdvisorName : $t('advisor')) ?></td>
                  <td><?= htmlspecialchars((string)$request['Appointment_Date']) ?></td>
                  <td>
                    <?php if ($studentReason !== ''): ?>
                            <button type="button"
                              class="btn btn-outline-primary btn-sm calendar-reason-btn request-reason-btn"
                              data-reason-title="<?= htmlspecialchars($t('reason')) ?>"
                              data-reason-content="<?= htmlspecialchars($studentReason) ?>">
                        <?= htmlspecialchars($t('view_details')) ?>
                      </button>
                    <?php else: ?>
                      <span class="text-muted">-</span>
                    <?php endif; ?>
                  </td>
                  <td><span class="badge bg-secondary"><?= htmlspecialchars($t('pending')) ?></span></td>
                  <td>
                    <?php if ($declineReason !== ''): ?>
                            <button type="button"
                              class="btn btn-outline-primary btn-sm calendar-reason-btn request-reason-btn"
                              data-reason-title="<?= htmlspecialchars($t('decline_reason')) ?>"
                              data-reason-content="<?= htmlspecialchars($declineReason) ?>">
                        <?= htmlspecialchars($t('view_details')) ?>
                      </button>
                    <?php else: ?>
                      <span class="text-muted">-</span>
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

  <!-- My Appointments tab -->
  <div class="section-panel <?= $activeSection === 'appointments' ? 'active' : '' ?>" id="section-appointments">
    <div class="section-card">

      <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
          <h5 class="mb-0 fw-semibold"><?= htmlspecialchars($t('my_appointments_title')) ?></h5>
          <p class="text-muted mb-0" style="font-size:.85rem;"><?= htmlspecialchars($t('my_appointments_subtitle')) ?></p>
        </div>
      </div>

      <?php if ($studentAppointmentsError !== ''): ?>
        <div class="alert alert-danger">
          <?= htmlspecialchars($studentAppointmentsError) ?>
        </div>
      <?php endif; ?>

      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th><?= htmlspecialchars($t('advisor')) ?></th>
              <th><?= htmlspecialchars($t('date')) ?></th>
              <th><?= htmlspecialchars($t('start_time')) ?></th>
              <th><?= htmlspecialchars($t('end_time')) ?></th>
              <th><?= htmlspecialchars($t('status')) ?></th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($studentAppointments) === 0): ?>
              <tr>
                <td colspan="5" class="text-center text-muted"><?= htmlspecialchars($t('no_approved_appointments')) ?></td>
              </tr>
            <?php else: ?>
              <?php foreach ($studentAppointments as $appointment): ?>
                <tr>
                  <td><?= htmlspecialchars(trim((string)($appointment['Advisor_Last_Name'] ?? '')) !== '' ? (string)$appointment['Advisor_Last_Name'] : $t('advisor')) ?></td>
                  <td><?= htmlspecialchars((string)$appointment['Appointment_Date']) ?></td>
                  <td><?= htmlspecialchars($appointment['Start_Time'] ? substr((string)$appointment['Start_Time'], 0, 5) : '-') ?></td>
                  <td><?= htmlspecialchars($appointment['End_Time'] ? substr((string)$appointment['End_Time'], 0, 5) : '-') ?></td>
                  <td>
                    <?php if (strtolower(trim((string)$appointment['Status'])) === 'scheduled'): ?>
                      <span class="badge bg-primary"><?= htmlspecialchars($t('scheduled')) ?></span>
                    <?php elseif (strtolower(trim((string)$appointment['Status'])) === 'completed'): ?>
                      <span class="badge bg-success"><?= htmlspecialchars($t('completed')) ?></span>
                    <?php elseif (strtolower(trim((string)$appointment['Status'])) === 'cancelled'): ?>
                      <span class="badge bg-danger"><?= htmlspecialchars($t('cancelled')) ?></span>
                    <?php elseif (strtolower(trim((string)$appointment['Status'])) === 'approved'): ?>
                      <span class="badge bg-success"><?= htmlspecialchars($t('approved')) ?></span>
                    <?php else: ?>
                      <span class="badge bg-dark"><?= htmlspecialchars((string)$appointment['Status']) ?></span>
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

  <!-- History tab -->
  <div class="section-panel <?= $activeSection === 'history' ? 'active' : '' ?>" id="section-history">
    <div class="section-card">

      <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
          <h5 class="mb-0 fw-semibold"><?= htmlspecialchars($t('history_title')) ?></h5>
          <p class="text-muted mb-0" style="font-size:.85rem;"><?= htmlspecialchars($t('history_subtitle')) ?></p>
        </div>
      </div>

      <?php if ($studentHistoryError !== ''): ?>
        <div class="alert alert-danger">
          <?= htmlspecialchars($studentHistoryError) ?>
        </div>
      <?php endif; ?>

      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th><?= htmlspecialchars($t('advisor')) ?></th>
              <th><?= htmlspecialchars($t('status')) ?></th>
              <th>Attendance</th>
              <th><?= htmlspecialchars($t('date')) ?></th>
              <th><?= htmlspecialchars($t('details')) ?></th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($studentHistory) === 0): ?>
              <tr>
                <td colspan="5" class="text-center text-muted"><?= htmlspecialchars($t('no_history_loaded')) ?></td>
              </tr>
            <?php else: ?>
              <?php foreach ($studentHistory as $history): ?>
                <?php
                  $historyReason = trim((string)($history['Advisor_Reason'] ?? $history['Student_Reason'] ?? ''));
                  $historyAdvisorLastName = trim((string)($history['Advisor_Last_Name'] ?? ''));
                ?>
                <tr>
                  <td><?= htmlspecialchars($historyAdvisorLastName !== '' ? $historyAdvisorLastName : $t('advisor')) ?></td>
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
                  <td><?= htmlspecialchars(trim((string)($history['Student_Attendance'] ?? '')) !== '' ? (string)$history['Student_Attendance'] : 'Pending') ?></td>
                  <td><?= htmlspecialchars((string)$history['Appointment_Date']) ?></td>
                  <td>
                    <?php if ($historyReason !== ''): ?>
                      <button type="button"
                              class="btn btn-outline-primary btn-sm calendar-reason-btn history-details-btn"
                              data-history-reason="<?= htmlspecialchars($historyReason) ?>">
                        <?= htmlspecialchars($t('view_details')) ?>
                      </button>
                    <?php else: ?>
                      <span class="text-muted">-</span>
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

  <!-- Calendar tab -->
  <div class="section-panel <?= $activeSection === 'calendar' ? 'active' : '' ?>" id="section-calendar">
    <div class="section-card">

      <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
          <h5 class="mb-0 fw-semibold"><?= htmlspecialchars($t('calendar_title')) ?></h5>
          <p class="text-muted mb-0" style="font-size:.85rem;"><?= htmlspecialchars($t('calendar_subtitle')) ?></p>
        </div>
      </div>

      <?php if ($studentCalendarError !== ''): ?>
        <div class="alert alert-danger">
          <?= htmlspecialchars($studentCalendarError) ?>
        </div>
      <?php endif; ?>

      <div id="studentCalendar"></div>
    </div>
  </div>

  <!-- Communications tab -->
  <div class="section-panel <?= $activeSection === 'communications' ? 'active' : '' ?>" id="section-communications">
    <div class="section-card">

      <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
          <h5 class="mb-0 fw-semibold"><?= htmlspecialchars($t('communications_title')) ?></h5>
          <p class="text-muted mb-0" style="font-size:.85rem;"><?= htmlspecialchars($t('communications_subtitle')) ?></p>
        </div>
      </div>

      <div class="comm-layout">

        <?php if ($advisorId === null): ?>
          <div class="comm-placeholder">
            <i class="bi bi-person-x"></i>
            <p><?= $t('no_advisor_assigned') ?></p>
          </div>
        <?php else: ?>

          <div class="comm-messages" id="commMessages">
            <div class="comm-loading"><?= htmlspecialchars($t('loading_messages')) ?></div>
          </div>

          <div class="comm-compose">
            <label for="commTextarea"><?= htmlspecialchars(sprintf($t('send_message_to'), $communicationAdvisorLabel !== '' ? $communicationAdvisorLabel : $t('advisor'))) ?> <span class="text-muted"><?= htmlspecialchars($t('words_max')) ?></span></label>
            <textarea id="commTextarea"
                      placeholder="<?= htmlspecialchars($t('message_placeholder')) ?>"
                      maxlength="2000"
                      oninput="commWordCount(this)"></textarea>
            <div class="comm-compose-footer">
              <span class="comm-word-count" id="commWordCount">0 / 200 <?= htmlspecialchars($t('words_suffix')) ?></span>
              <button type="button" class="btn-send" id="commSendBtn" onclick="commSend()" disabled>
                <i class="bi bi-send-fill"></i> <?= htmlspecialchars($t('send_message')) ?>
              </button>
            </div>
          </div>

          <script>window.commStudentId = <?= json_encode($studentMessageUserId) ?>;</script>
        <?php endif; ?>

      </div>

    </div>
  </div>

</main>

<?php require_once __DIR__ . '/footer/dashboard_footer.php'; ?>

<!-- Book Appointment modal -->
<div class="modal fade" id="bookAppointmentModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-semibold"><?= htmlspecialchars($t('book_modal_title')) ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <form action="../backend/controllers/StudentBookAppointment.php" method="POST">
        <div class="modal-body">
          <input type="hidden" name="student_id" value="<?= (int)$studentId ?>">
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">
          <input type="hidden" name="slot_source" id="bookSlotSource" value="">
          <input type="hidden" name="slot_id" id="bookSlotId" value="">
          <input type="hidden" name="slot_date" id="bookSlotDate" value="">
          <input type="hidden" name="slot_day" id="bookSlotDay" value="">

          <div class="row g-3">

            <div class="col-12">
              <label class="form-label"><?= htmlspecialchars($t('advisor')) ?> <span class="text-danger">*</span></label>
              <input type="text" class="form-control" value="<?= htmlspecialchars($advisorName) ?>" readonly>
            </div>

            <div class="col-12">
              <label class="form-label"><?= htmlspecialchars($t('selected_slot')) ?> <span class="text-danger">*</span></label>
              <div class="form-control bg-light" id="bookSelectedSlotSummary" style="min-height:44px;"><?= htmlspecialchars($t('no_slot_selected')) ?></div>
            </div>

            <div class="col-6" id="bookSelectedDayWrap">
              <label class="form-label"><?= htmlspecialchars($t('selected_day')) ?> <span class="text-danger">*</span></label>
              <input type="text" id="bookSelectedSlotDayDisplay" class="form-control" readonly>
            </div>

            <div class="col-6">
              <label class="form-label"><?= htmlspecialchars($t('selected_time')) ?> <span class="text-danger">*</span></label>
              <input type="text" id="bookSelectedSlotTimeDisplay" class="form-control" readonly>
            </div>

            <div class="col-12" id="bookSelectedDateWrap">
              <label class="form-label"><?= htmlspecialchars($t('selected_date')) ?> <span class="text-danger">*</span></label>
              <input type="text" id="bookSelectedSlotDateDisplay" class="form-control" readonly>
            </div>

            <div class="col-12 d-none" id="bookRecurringDateWrap">
              <label class="form-label"><?= htmlspecialchars($t('choose_date')) ?> <span class="text-danger">*</span></label>
              <input type="date" name="appointment_date" id="bookRecurringDateInput" class="form-control">
            </div>

            <div class="col-12">
              <label class="form-label"><?= htmlspecialchars($t('selected_type')) ?> <span class="text-danger">*</span></label>
              <input type="text" id="bookSelectedSlotTypeDisplay" class="form-control" readonly>
            </div>

            <div class="col-12">
              <label class="form-label"><?= htmlspecialchars($t('reason_for_appointment')) ?> <span class="text-danger">*</span></label>
              <textarea name="reason" class="form-control" rows="4" placeholder="<?= htmlspecialchars($t('request_reason_placeholder')) ?>" required></textarea>
            </div>

          </div>
        </div>

        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal"><?= htmlspecialchars($t('cancel')) ?></button>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-send me-1"></i> <?= htmlspecialchars($t('send_request')) ?>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Student Calendar Details Modal -->
<div class="modal fade" id="studentCalendarModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content rounded-4">
      <div class="modal-header">
        <h5 class="modal-title"><?= htmlspecialchars($t('appointment_details')) ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= htmlspecialchars($t('close')) ?>"></button>
      </div>
      <div class="modal-body">
        <p><strong><?= htmlspecialchars($t('advisor')) ?>:</strong> <span id="calendarModalAdvisor"></span></p>
        <p><strong><?= htmlspecialchars($t('date')) ?>:</strong> <span id="calendarModalDate"></span></p>
        <p><strong><?= htmlspecialchars($t('time')) ?>:</strong> <span id="calendarModalTime"></span></p>
        <p><strong><?= htmlspecialchars($t('status')) ?>:</strong> <span id="calendarModalStatus"></span></p>

        <div class="calendar-reason-group">
          <div class="d-flex align-items-center justify-content-between gap-3">
            <strong><?= htmlspecialchars($t('your_reason')) ?>:</strong>
            <button type="button"
                    class="btn btn-outline-primary btn-sm calendar-reason-btn"
                    id="calendarModalStudentReasonBtn"
                    data-bs-toggle="collapse"
                    data-bs-target="#calendarModalStudentReasonWrap"
                    aria-expanded="false"
                    aria-controls="calendarModalStudentReasonWrap">
              <?= htmlspecialchars($t('view_reason')) ?>
            </button>
          </div>
          <div class="collapse mt-2" id="calendarModalStudentReasonWrap">
            <div class="calendar-reason-box" id="calendarModalStudentReason"></div>
          </div>
        </div>

        <div class="calendar-reason-group mt-3">
          <div class="d-flex align-items-center justify-content-between gap-3">
            <strong><?= htmlspecialchars($t('advisor_reason')) ?>:</strong>
            <button type="button"
                    class="btn btn-outline-primary btn-sm calendar-reason-btn"
                    id="calendarModalAdvisorReasonBtn"
                    data-bs-toggle="collapse"
                    data-bs-target="#calendarModalAdvisorReasonWrap"
                    aria-expanded="false"
                    aria-controls="calendarModalAdvisorReasonWrap">
              <?= htmlspecialchars($t('view_reason')) ?>
            </button>
          </div>
          <div class="collapse mt-2" id="calendarModalAdvisorReasonWrap">
            <div class="calendar-reason-box" id="calendarModalAdvisorReason"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- History Details Modal -->
<div class="modal fade" id="historyDetailsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content rounded-4">
      <div class="modal-header">
        <h5 class="modal-title"><?= htmlspecialchars($t('appointment_details')) ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= htmlspecialchars($t('close')) ?>"></button>
      </div>
      <div class="modal-body">
        <div class="calendar-reason-box" id="historyDetailsText"></div>
      </div>
    </div>
  </div>
</div>

<!-- Request Reason Modal -->
<div class="modal fade" id="requestReasonModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content rounded-4">
      <div class="modal-header">
        <h5 class="modal-title" id="requestReasonModalTitle"><?= htmlspecialchars($t('reason')) ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= htmlspecialchars($t('close')) ?>"></button>
      </div>
      <div class="modal-body">
        <div class="calendar-reason-box" id="requestReasonModalText"></div>
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
let commLoaded = false;
let studentCalendarLoaded = false;
let studentCalendarInstance = null;
let historyDetailsModal = null;
let requestReasonModal = null;

function openHistoryDetailsModal(reasonText) {
  const content = document.getElementById('historyDetailsText');
  if (!content || !historyDetailsModal) return;

  const cleanReason = String(reasonText ?? '').trim();
  content.textContent = cleanReason !== '' ? cleanReason : '-';
  historyDetailsModal.show();
}

function openRequestReasonModal(titleText, reasonText) {
  const titleEl = document.getElementById('requestReasonModalTitle');
  const textEl = document.getElementById('requestReasonModalText');

  if (!titleEl || !textEl || !requestReasonModal) return;

  titleEl.textContent = String(titleText ?? '').trim() || <?= json_encode($t('reason')) ?>;
  textEl.textContent = String(reasonText ?? '').trim() || '-';
  requestReasonModal.show();
}

const studentCalendarEvents = <?= json_encode($studentCalendarEvents, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

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

function resetCalendarReasonState(wrapId) {
  const wrap = document.getElementById(wrapId);
  if (!wrap) return;

  const collapse = bootstrap.Collapse.getOrCreateInstance(wrap, { toggle: false });
  collapse.hide();
}

function renderStudentCalendar() {
  if (studentCalendarLoaded) return;

  const calendarEl = document.getElementById('studentCalendar');
  const modalEl = document.getElementById('studentCalendarModal');
  if (!calendarEl || !modalEl) return;

  const detailsModal = new bootstrap.Modal(modalEl);

  studentCalendarInstance = new FullCalendar.Calendar(calendarEl, {
    initialView: 'dayGridMonth',
    locale: currentLang,
    buttonText: {
      today: currentLang === 'el' ? 'Σήμερα' : 'today'
    },
    height: 'auto',
    events: studentCalendarEvents,
    eventClick: function (info) {
      const props = info.event.extendedProps || {};
      document.getElementById('calendarModalAdvisor').textContent = props.advisor || '-';
      document.getElementById('calendarModalDate').textContent = props.date || '-';
      document.getElementById('calendarModalTime').textContent = props.time || '-';
      document.getElementById('calendarModalStatus').textContent = props.status || '-';
      setCalendarReason('calendarModalStudentReasonBtn', 'calendarModalStudentReasonWrap', 'calendarModalStudentReason', props.student_reason);
      setCalendarReason('calendarModalAdvisorReasonBtn', 'calendarModalAdvisorReasonWrap', 'calendarModalAdvisorReason', props.advisor_reason);
      resetCalendarReasonState('calendarModalStudentReasonWrap');
      resetCalendarReasonState('calendarModalAdvisorReasonWrap');
      detailsModal.show();
    }
  });

  studentCalendarInstance.render();
  studentCalendarLoaded = true;
}

function commLoad() {
  if (!window.commStudentId) return;
  commLoaded = true;
  commFetchThread();
}

function commFetchThread() {
  const box = document.getElementById('commMessages');
  if (!box) return;

  box.innerHTML = '<div class="comm-loading"><?= htmlspecialchars($t('loading_messages')) ?></div>';

  const fd = new FormData();
  fd.append('action', '/student/message/thread');
  fd.append('student_id', window.commStudentId);
  fd.append('_csrf', <?= json_encode($csrfToken) ?>);

  fetch('../backend/modules/dispatcher.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(payload => {
      const messages = Array.isArray(payload)
        ? payload
        : (payload && Array.isArray(payload.data) ? payload.data : []);

      if (!messages.length) {
        box.innerHTML = [
          '<div class="comm-placeholder">',
          '<i class="bi bi-chat"></i>',
          '<p><?= htmlspecialchars($t('no_messages_yet')) ?></p>',
          '</div>'
        ].join('');
        return;
      }

      box.innerHTML = messages.map(m => commBubble(m)).join('');
      box.scrollTop = box.scrollHeight;

      const markReadFd = new FormData();
      markReadFd.append('action', '/student/message/read');
      markReadFd.append('student_id', window.commStudentId);
      markReadFd.append('_csrf', <?= json_encode($csrfToken) ?>);
      fetch('../backend/modules/dispatcher.php', { method: 'POST', body: markReadFd }).catch(() => {});
    })
    .catch(() => {
      box.innerHTML = [
        '<div class="comm-placeholder" style="color:#ef4444">',
        '<i class="bi bi-exclamation-circle"></i>',
        '<p><?= htmlspecialchars($t('failed_to_load_messages')) ?></p>',
        '</div>'
      ].join('');
    });
}

function commBubble(m) {
  const isStudent = m.sender === 'student';
  const side = isStudent ? 'from-student' : 'from-advisor';
  const senderLabel = isStudent ? <?= json_encode($t('you')) ?> : (m.sender_name || <?= json_encode($t('advisor')) ?>);
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
}

function commWordCount(textarea) {
  const words = textarea.value.trim() === '' ? 0 : textarea.value.trim().split(/\s+/).length;
  const el = document.getElementById('commWordCount');
  const btn = document.getElementById('commSendBtn');
  if (!el || !btn) return;

  el.textContent = words + ' / ' + COMM_MAX_WORDS + ' ' + <?= json_encode($t('words_suffix')) ?>;
  el.classList.toggle('over', words > COMM_MAX_WORDS);
  btn.disabled = (words === 0 || words > COMM_MAX_WORDS);
}

function commSend() {
  const textarea = document.getElementById('commTextarea');
  const btn = document.getElementById('commSendBtn');
  if (!textarea || !btn) return;

  const body = textarea.value.trim();
  if (!body || !window.commStudentId) return;

  btn.disabled = true;
  btn.innerHTML = '<i class="bi bi-hourglass-split"></i> <?= htmlspecialchars($t('sending')) ?>';

  const fd = new FormData();
  fd.append('action', '/student/message/send');
  fd.append('student_id', window.commStudentId);
  fd.append('message_body', body);
  fd.append('_csrf', <?= json_encode($csrfToken) ?>);

  fetch('../backend/modules/dispatcher.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        textarea.value = '';
        commWordCount(textarea);
        commFetchThread();
      } else {
        showPageMessage(data.message || <?= json_encode($t('failed_to_send_message')) ?>, 'danger');
        btn.disabled = false;
      }
    })
    .catch(() => {
      showPageMessage(<?= json_encode($t('network_error_sending')) ?>, 'danger');
      btn.disabled = false;
    })
    .finally(() => {
      btn.innerHTML = '<i class="bi bi-send-fill"></i> Send Message';
    });
}

function commEsc(str) {
  return String(str ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

document.addEventListener("DOMContentLoaded", function () {
  const historyModalEl = document.getElementById('historyDetailsModal');
  if (historyModalEl) {
    historyDetailsModal = new bootstrap.Modal(historyModalEl);
  }

  const requestReasonModalEl = document.getElementById('requestReasonModal');
  if (requestReasonModalEl) {
    requestReasonModal = new bootstrap.Modal(requestReasonModalEl);
  }

  document.querySelectorAll('.history-details-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      openHistoryDetailsModal(btn.getAttribute('data-history-reason'));
    });
  });

  document.querySelectorAll('.request-reason-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      openRequestReasonModal(
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

      if (sectionName === 'communications' && !commLoaded) {
        commLoad();
      }

      if (sectionName === 'calendar') {
        renderStudentCalendar();
      }
    });
  });

  if (document.getElementById('section-communications')?.classList.contains('active')) {
    commLoad();
  }

  if (document.getElementById('section-calendar')?.classList.contains('active')) {
    renderStudentCalendar();
  }

  const studentRequestSearch = document.getElementById('studentRequestSearch');
  if (studentRequestSearch) {
    studentRequestSearch.addEventListener('input', function () {
      const q = this.value.toLowerCase();
      document.querySelectorAll('.student-request-row').forEach(function (row) {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
      });
    });
  }

  document.querySelectorAll('.open-book-modal-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const slotId = btn.getAttribute('data-slot-id');
      const slotSource = btn.getAttribute('data-slot-source') || '';
      const slotDate = btn.getAttribute('data-slot-date');
      const slotDay = btn.getAttribute('data-slot-day') || '';
      const slotStartTime = btn.getAttribute('data-slot-start-time') || '';
      const slotEndTime = btn.getAttribute('data-slot-end-time') || '';
      const slotType = btn.getAttribute('data-slot-type') || '';
      const slotIdInput = document.getElementById('bookSlotId');
      const slotSourceInput = document.getElementById('bookSlotSource');
      const slotDateInput = document.getElementById('bookSlotDate');
      const slotDayInput = document.getElementById('bookSlotDay');
      const slotSummary = document.getElementById('bookSelectedSlotSummary');
      const selectedDayWrap = document.getElementById('bookSelectedDayWrap');
      const slotDayDisplay = document.getElementById('bookSelectedSlotDayDisplay');
      const slotDateDisplay = document.getElementById('bookSelectedSlotDateDisplay');
      const slotTimeDisplay = document.getElementById('bookSelectedSlotTimeDisplay');
      const slotTypeDisplay = document.getElementById('bookSelectedSlotTypeDisplay');
      const recurringDateWrap = document.getElementById('bookRecurringDateWrap');
      const recurringDateInput = document.getElementById('bookRecurringDateInput');
      const selectedDateWrap = document.getElementById('bookSelectedDateWrap');

      if (slotIdInput && slotId) {
        slotIdInput.value = slotId;
      }

      if (slotSourceInput) {
        slotSourceInput.value = slotSource;
      }

      if (slotDateInput) {
        slotDateInput.value = slotSource === 'additional' ? (slotDate || '') : '';
      }

      if (slotDayInput) {
        slotDayInput.value = slotDay;
      }

      if (slotSummary) {
        slotSummary.textContent = [slotDay || slotDate || '', slotStartTime && slotEndTime ? (slotStartTime + ' - ' + slotEndTime) : '', slotType || '']
          .filter(Boolean)
          .join(' | ');
      }

      if (slotDayDisplay) {
        slotDayDisplay.value = slotDay || '';
      }

      if (slotDateDisplay) {
        slotDateDisplay.value = slotSource === 'additional' ? (slotDate || '') : '';
      }

      if (slotTimeDisplay) {
        slotTimeDisplay.value = slotStartTime && slotEndTime ? (slotStartTime + ' - ' + slotEndTime) : '';
      }

      if (slotTypeDisplay) {
        slotTypeDisplay.value = slotType || '';
      }

      if (recurringDateWrap && recurringDateInput) {
        if (slotSource === 'recurring') {
          recurringDateWrap.classList.remove('d-none');
          recurringDateInput.required = true;
          recurringDateInput.value = '';
          recurringDateInput.min = new Date().toISOString().split('T')[0];
        } else {
          recurringDateWrap.classList.add('d-none');
          recurringDateInput.required = false;
          recurringDateInput.value = '';
        }
      }

      if (selectedDateWrap) {
        if (slotSource === 'additional') {
          selectedDateWrap.classList.remove('d-none');
        } else {
          selectedDateWrap.classList.add('d-none');
        }
      }

      if (selectedDayWrap) {
        if (slotSource === 'recurring') {
          selectedDayWrap.classList.remove('d-none');
        } else {
          selectedDayWrap.classList.add('d-none');
        }
      }
    });
  });
});
</script>

</body>
</html>

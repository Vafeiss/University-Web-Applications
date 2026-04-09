<?php
/* 
NAME: Student Available Slots Page
Description: Displays available advisor office hours to the student and allows slot selection for booking.
Author: Panteleimoni Alexandrou
Date: 20/03/2026 v1.5
Inputs: None
Outputs: HTML page showing available slots + Select button
Error Messages: Shows database/query error if something fails
Files in use: backend/modules/databaseconnect.php, users table, office_hours table, appointment_history table, student_advisors table, Bootstrap CSS from the web
*/

declare(strict_types=1);

require_once __DIR__ . '/../modules/databaseconnect.php';

$pdo = ConnectToDatabase();

$errorMessage = "";
$slots = [];
$advisorName = "";

/*
TEMP: hardcoded student for testing
Student User_ID = 4
Later this must come from session/login
*/
$studentUserId = 4;
$advisorUserId = 0;

/*
------------------------------------------------------------
FIND ASSIGNED ADVISOR
------------------------------------------------------------
*/
try {
    $sql = "SELECT 
                advisor.User_ID AS Advisor_User_ID,
                advisor.First_name,
                advisor.Last_Name
            FROM users student
            INNER JOIN student_advisors sa 
                ON sa.Student_ID = student.External_ID
            INNER JOIN users advisor
                ON advisor.External_ID = sa.Advisor_ID
            WHERE student.User_ID = :student_user_id
              AND student.Role = 'Student'
              AND advisor.Role = 'Advisor'
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'student_user_id' => $studentUserId
    ]);

    $advisor = $stmt->fetch();

    if ($advisor) {
        $advisorUserId = (int)$advisor['Advisor_User_ID'];
        $advisorName = trim($advisor['First_name'] . ' ' . $advisor['Last_Name']);
    } else {
        $errorMessage = "No assigned advisor was found for this student.";
    }

} catch (Throwable $e) {
    $errorMessage = $e->getMessage();
}

/*
------------------------------------------------------------
FETCH AVAILABLE SLOTS
Hide slots that already have Pending (0) or Approved (1) appointments
------------------------------------------------------------
*/
if ($errorMessage === "") {
    try {
        $sql = "SELECT oh.OfficeHour_ID, oh.Day_of_Week, oh.Start_Time, oh.End_Time
                FROM office_hours oh
                WHERE oh.Advisor_ID = :advisor_id
                  AND oh.OfficeHour_ID NOT IN (
                      SELECT ah.OfficeHour_ID
                      FROM appointment_history ah
                      WHERE ah.OfficeHour_ID IS NOT NULL
                        AND ah.Status IN (0, 1)
                  )
                ORDER BY 
                    FIELD(oh.Day_of_Week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'),
                    oh.Start_Time ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'advisor_id' => $advisorUserId
        ]);

        $slots = $stmt->fetchAll();

    } catch (Throwable $e) {
        $errorMessage = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>AdviCut - Available Slots</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-10">
                <div class="card shadow p-4 rounded-4">
                    
                    <h3 class="text-center mb-3">Available Advisor Slots</h3>

                    <div class="mb-4">
                        <h5 class="mb-0"><?= htmlspecialchars($advisorName) ?></h5>
                    </div>

                    <?php if ($errorMessage !== ""): ?>
                        <div class="alert alert-danger text-center">
                            Error: <?= htmlspecialchars($errorMessage) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($errorMessage === "" && count($slots) === 0): ?>
                        <div class="alert alert-secondary text-center">
                            No available slots found for your advisor.
                        </div>
                    <?php endif; ?>

                    <?php if (count($slots) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle text-center">
                                <thead class="table-primary">
                                    <tr>
                                        <th>Slot ID</th>
                                        <th>Day</th>
                                        <th>Start</th>
                                        <th>End</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($slots as $s): ?>
                                        <tr>
                                            <td><?= htmlspecialchars((string)$s['OfficeHour_ID']) ?></td>
                                            <td><?= htmlspecialchars((string)$s['Day_of_Week']) ?></td>
                                            <td><?= htmlspecialchars((string)$s['Start_Time']) ?></td>
                                            <td><?= htmlspecialchars((string)$s['End_Time']) ?></td>
                                            <td>
                                                <form action="StudentBookAppointment.php" method="POST" class="m-0">
                                                    <input type="hidden" name="slot_id" value="<?= (int)$s['OfficeHour_ID'] ?>">
                                                    <button type="submit" class="btn btn-primary btn-sm">
                                                        Select
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <div class="mt-3 text-center">
                        <a href="../../frontend/index.php" class="btn btn-primary">Back</a>
                    </div>

                </div>
            </div>
        </div>
    </div>
</body>
</html>
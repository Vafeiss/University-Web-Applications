<?php
declare(strict_types=1);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>AdviCut - Appointment Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-10">
            <div class="card shadow p-4 rounded-4">
                <h2 class="text-center mb-4">Appointment Dashboard</h2>

                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="card h-100 shadow-sm">
                            <div class="card-body text-center">
                                <h4 class="mb-3">Advisor Functions</h4>
                                <div class="d-grid gap-2">
                                    <a href="../backend/controllers/AdvisorOfficeHours.php" class="btn btn-primary">Manage Office Hours</a>
                                    <a href="../backend/controllers/AdvisorAppointmentRequests.php" class="btn btn-primary">View Appointment Requests</a>
                                    <a href="../backend/controllers/AdvisorAttendance.php" class="btn btn-primary">Mark Attendance</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card h-100 shadow-sm">
                            <div class="card-body text-center">
                                <h4 class="mb-3">Student Functions</h4>
                                <div class="d-grid gap-2">
                                    <a href="../backend/controllers/StudentAvailableSlots.php" class="btn btn-success">View Available Slots</a>
                                    <a href="../backend/controllers/StudentAppointmentHistory.php" class="btn btn-success">View Appointment History</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 text-center">
                    <a href="index.php" class="btn btn-secondary">Back to Home</a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
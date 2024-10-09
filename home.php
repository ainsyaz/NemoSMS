<?php
include 'index.php';
include 'db.php'; // Include your database connection

checkLogin();

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

$logo_sql = "SELECT logo_path FROM settings WHERE id = 1";
$result = $conn->query($logo_sql);
$row = $result->fetch_assoc();
$logo_path = $row['logo_path'] ?? 'images/logo.png'; // Use a default logo if not set

// In your header.php or home.php (where you want to show the notification)
// SQL query to count unread notifications
$notification_sql = "SELECT COUNT(*) AS unread FROM notifications WHERE user_id = ? AND is_read = 0";
$stmt = $conn->prepare($notification_sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($unread_count);
$stmt->fetch();
$stmt->close();

// Display notification if there are unread messages
if ($unread_count > 0) {
    echo "<div class='notification'>You have $unread_count new announcement(s). <a href='news.php'>View</a></div>";
}


// Fetch user details from the database
$sql = "SELECT name, ic_number, phone_number, email, date_birth, address, picture FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<p>User not found.</p>";
    exit();
}

$user = $result->fetch_assoc();
$stmt->close();

$profile_picture = !empty($user['picture']) ? htmlspecialchars($user['picture']) : 'default.png';

// Handle Punch-In
$punch_in_disabled = false;
$punch_out_disabled = false;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['punch_in'])) {
    // Check if the user has already punched in today
    $sql = "SELECT id FROM user_activity WHERE user_id = ? AND punch_in_time IS NOT NULL AND punch_out_time IS NULL";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Redirect if already punched in
        header("Location: home.php");
        exit();
    }

    // Insert punch-in time into the database
    $sql = "INSERT INTO user_activity (user_id, punch_in_time) VALUES (?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        header("Location: home.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

// Handle Punch-Out
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['punch_out'])) {
    // Check if the user has punched in but not out
    $sql = "SELECT id FROM user_activity WHERE user_id = ? AND punch_in_time IS NOT NULL AND punch_out_time IS NULL";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        // Redirect if not punched in
        header("Location: home.php");
        exit();
    }

    // Update punch-out time in the database
    $sql = "UPDATE user_activity SET punch_out_time = NOW() WHERE user_id = ? AND punch_in_time IS NOT NULL AND punch_out_time IS NULL";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        header("Location: home.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

// Check Punch-In Status for Button State
$sql = "SELECT id FROM user_activity WHERE user_id = ? AND punch_in_time IS NOT NULL AND punch_out_time IS NULL";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $punch_in_disabled = true;
} else {
    $punch_out_disabled = true;
}

$stmt->close();

// Fetch User's Leave Balance and Applications
$sql = "SELECT leave_balance FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($leave_balance);
$stmt->fetch();
$stmt->close();

// Handle Leave Application
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['apply_leave'])) {
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';

    // Check if application is at least 3 days in advance
    $today = new DateTime();
    $start = new DateTime($start_date);
    $diff = $today->diff($start)->days;

    if ($diff < 3 && empty($reason)) {
        echo "<script>alert('For leave application less than 3 days in advance, a reason is mandatory.'); window.location.href = 'home.php';</script>";
        exit();
    }

    // Calculate the leave duration
    $leave_days = (new DateTime($end_date))->diff(new DateTime($start_date))->days + 1;

    // Handle file upload
    $file_path = '';
    if (isset($_FILES['leave_file']) && $_FILES['leave_file']['error'] == 0) {
        $file_name = basename($_FILES['leave_file']['name']);
        $target_dir = "leave/";
        $target_file = $target_dir . time() . "_" . $file_name;

        if (move_uploaded_file($_FILES['leave_file']['tmp_name'], $target_file)) {
            $file_path = $target_file;
        } else {
            echo "<script>alert('Error uploading file.'); window.location.href = 'home.php';</script>";
            exit();
        }
    }

    // Insert leave application into the database
    $sql = "INSERT INTO leave_applications (user_id, start_date, end_date, reason, file_path) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issss", $user_id, $start_date, $end_date, $reason, $file_path);

    if ($stmt->execute()) {
        // Deduct leave days from user balance
        $sql = "UPDATE users SET leave_balance = leave_balance - ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $leave_days, $user_id);
        $stmt->execute();

        echo "<script>alert('Leave application submitted successfully.'); window.location.href = 'home.php';</script>";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}


// Handle Leave Approval/Rejection by Admin
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['manage_leave'])) {
    $leave_id = $_POST['leave_id'];
    $action = $_POST['action'];

    // Get leave days to restore if rejected
    if ($action == 'rejected') {
        $sql = "SELECT start_date, end_date FROM leave_applications WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $leave_id);
        $stmt->execute();
        $stmt->bind_result($start_date, $end_date);
        $stmt->fetch();
        $stmt->close();

        $leave_days = (new DateTime($end_date))->diff(new DateTime($start_date))->days + 1;

        // Restore leave days to user balance
        $sql = "UPDATE users SET leave_balance = leave_balance + ? WHERE id = (SELECT user_id FROM leave_applications WHERE id = ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $leave_days, $leave_id);
        $stmt->execute();
    }

    // Update leave application status
    $sql = "UPDATE leave_applications SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $action, $leave_id);

    if ($stmt->execute()) {
        echo "<script>alert('Leave application $action successfully.'); window.location.href = 'home.php';</script>";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

// Fetch Leave Applications
$leave_applications = [];
if ($user_role == 'admin' || $user_role == 'super') {
    $sql = "SELECT la.id, u.name, u.ic_number, la.start_date, la.end_date, la.reason, la.status, la.user_id, u.role
            FROM leave_applications la
            JOIN users u ON la.user_id = u.id
            WHERE la.status = 'pending'"; // Added condition for pending status

    if ($user_role == 'admin') {
        // Admins can see staff applications except their own
        $sql .= " AND u.role = 'staff' AND la.user_id <> ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
    } elseif ($user_role == 'super') {
        // Super Admins can see all admin applications
        $sql .= " AND u.role = 'admin'";
        $stmt = $conn->prepare($sql);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $leave_applications[] = $row;
    }
    $stmt->close();
}

// Fetch User PunchIn/PunchOut Records (Admin Only)
$punch_records = [];
if ($user_role == 'admin' || $user_role == 'super') {
    $today = new DateTime();

    // Calculate the start of the current week (Sunday)
    $start_of_week = (clone $today)->modify('last Sunday');
    if ($today->format('w') == 0) {
        $start_of_week = $today;
    }

    // Calculate the end of the week (Thursday)
    $end_of_week = (clone $start_of_week)->modify('+4 days'); // Thursday

    $sql = "SELECT u.id AS user_id, u.name, l.punch_in_time, l.punch_out_time 
        FROM user_activity l 
        JOIN users u ON l.user_id = u.id 
        WHERE l.punch_in_time BETWEEN ? AND ? 
        ORDER BY l.punch_in_time DESC";

    $stmt = $conn->prepare($sql);

    // Correctly pass variables to bind_param
    $start_of_week_str = $start_of_week->format('Y-m-d 00:00:00');
    $end_of_week_str = $end_of_week->format('Y-m-d 23:59:59');
    $stmt->bind_param("ss", $start_of_week_str, $end_of_week_str);

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $punch_records[] = $row;
        }
        $stmt->close();
    } else {
        echo "Error executing query: " . $stmt->error;
    }
}

// Organize data by user and date
$organized_punch_records = [];
foreach ($punch_records as $log) {
    $user_id = $log['user_id'];
    $name = $log['name'];
    $punch_in_time = new DateTime($log['punch_in_time']);
    $punch_out_time = $log['punch_out_time'] ? new DateTime($log['punch_out_time']) : null;
    $date_key = $punch_in_time->format('Y-m-d');

    if (!isset($organized_punch_records[$user_id])) {
        $organized_punch_records[$user_id] = [
            'name' => $name,
            'dates' => []
        ];
    }

    if (!isset($organized_punch_records[$user_id]['dates'][$date_key])) {
        $organized_punch_records[$user_id]['dates'][$date_key] = [];
    }

    $organized_punch_records[$user_id]['dates'][$date_key][] = [
        'punch_in_time' => $punch_in_time,
        'punch_out_time' => $punch_out_time
    ];
}

// Fetch Leave Records
$leave_records = [];

// Check if the user is an admin, super admin, or staff
if ($user_role == 'admin' || $user_role == 'super') {
    // Base query
    $sql = "SELECT u.id AS user_id, u.name, u.role, la.start_date, la.end_date, la.reason, la.status 
            FROM leave_applications la 
            JOIN users u ON la.user_id = u.id";

    // Add condition based on user role
    if ($user_role == 'admin') {
        $sql .= " WHERE u.role IN ('staff','admin')";
    } elseif ($user_role == 'super') {
        $sql .= " WHERE u.role IN ('staff', 'admin')";
    }

    // Prepare and execute the statement
    if ($stmt = $conn->prepare($sql)) {
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $leave_records[] = $row;
        }
        $stmt->close();
    } else {
        die("Query preparation failed: " . $conn->error);
    }
} elseif ($user_role == 'staff') {
    // Staff users can only see their own records
    $sql = "SELECT u.id AS user_id, u.name, u.role, la.start_date, la.end_date, la.reason, la.status 
            FROM leave_applications la 
            JOIN users u ON la.user_id = u.id 
            WHERE la.user_id = ?";

    // Prepare and execute the statement
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $user_id); // Bind the user_id for staff
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $leave_records[] = $row;
        }
        $stmt->close();
    } else {
        die("Query preparation failed: " . $conn->error);
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>

<head>
    <title>VSG</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css?family=Montserrat" rel="stylesheet">

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var punchInButton = document.getElementById('punch-in-button');
            var punchOutButton = document.getElementById('punch-out-button');
            var currentTime = new Date();
            var currentHour = currentTime.getHours();

            console.log("Current hour:", currentHour);

            // Check if buttons should be enabled or disabled
            var punchInDisabled = <?php echo json_encode($punch_in_disabled); ?>;
            var punchOutDisabled = <?php echo json_encode($punch_out_disabled); ?>;

            console.log("Punch In Disabled:", punchInDisabled);
            console.log("Punch Out Disabled:", punchOutDisabled);

            // Manage punch-in button state
            if (punchInDisabled) {
                punchInButton.disabled = true;
                punchInButton.classList.add('disabled');
                punchInButton.classList.remove('red'); // Remove red if disabled
            } else {
                punchInButton.disabled = false;
                punchInButton.classList.remove('disabled');
                if (currentHour >= 9) {
                    punchInButton.classList.add('red');
                } else {
                    punchInButton.classList.remove('red');
                }
            }

            // Manage punch-out button state
            if (punchOutDisabled) {
                punchOutButton.disabled = true;
                punchOutButton.classList.add('disabled');
                punchOutButton.classList.remove('red'); // Remove red if disabled
            } else {
                punchOutButton.disabled = false;
                punchOutButton.classList.remove('disabled');
                if (currentHour < 17) {
                    punchOutButton.classList.add('red');
                } else {
                    punchOutButton.classList.remove('red');
                }
            }

            console.log(punchInButton.classList);
            console.log(punchOutButton.classList);
        });
    </script>
</head>

<body>
    <?php include 'header.php'; ?>
    <div class="content">
        <div class="details-container">
            <div class="profile-picture-container">
                <img src="<?php echo 'photos/' . $profile_picture; ?>" alt="Profile Picture" class="profile-picture">
            </div>
            <div class="user-details">
                <h2><?php echo htmlspecialchars($user['name']); ?></h2>
                <p>Email: <?php echo htmlspecialchars($user['email']); ?></p>
                <p>IC Number: <?php echo htmlspecialchars($user['ic_number']); ?></p>
                <p>Phone Number: <?php echo htmlspecialchars($user['phone_number']); ?></p>
                <p>Date Of Birth: <?php echo htmlspecialchars($user['date_birth']); ?></p>
                <p>Address: <?php echo htmlspecialchars($user['address']); ?></p>
            </div>
            <?php if ($user_role != 'super'): ?>
                <div class="punch-button-container">
                    <!-- Punch In/Out Forms -->
                    <form class="punchin-form" method="post" action="home.php">
                        <input type="hidden" name="punch_in">
                        <button type="submit" id="punch-in-button" class="punch-button">Punch In</button>
                    </form>

                    <form class="punchout-form" method="post" action="home.php">
                        <input type="hidden" name="punch_out">
                        <button type="submit" id="punch-out-button" class="punch-button">Punch Out</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
        <?php if ($user_role != 'super'): ?>
            <div class="leave-container">
                <h1>Leave Matters</h1>
                <h2>Your leave balance: <?php echo htmlspecialchars($leave_balance); ?> days</h2>
                <h2>Request Leave</h2>
                <form method="post" action="home.php" enctype="multipart/form-data">
                    <input type="hidden" name="apply_leave">
                    <label>Start Date: </label><input type="date" name="start_date" id="start_date" required><br>
                    <label>End Date: </label><input type="date" name="end_date" id="end_date" required><br>
                    <label>Reason: </label><textarea name="reason" id="reason" rows="5" cols="30" placeholder="Enter reason for leave..."></textarea><br>
                    <label>Upload Supporting Document (Optional): </label><input type="file" name="leave_file" id="leave_file" accept="image/*,.pdf"><br>
                    <div class="button-container">
                        <input type="submit" value="Submit Leave Request">
                    </div>
                </form>
            </div>

        <?php endif; ?>
        <div class="leave-records">
            <?php if ($user_role == 'admin' || $user_role == 'super'): ?>
                <h2>Leave Applications</h2>
                <?php
                $count = 1;
                if (count($leave_applications) > 0): ?>
                    <table border="1">
                        <tr>
                            <th></th>
                            <th>Name</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                        <?php foreach ($leave_applications as $application): ?>
                            <tr>
                                <td><?php echo $count++; ?></td>
                                <td><?php echo htmlspecialchars($application['name']); ?></td>
                                <td><?php echo htmlspecialchars($application['start_date']); ?></td>
                                <td><?php echo htmlspecialchars($application['end_date']); ?></td>
                                <td><?php echo htmlspecialchars($application['reason']); ?></td>
                                <td><?php echo htmlspecialchars($application['status']); ?></td>
                                <?php if ($application['status'] == 'pending'): ?>
                                    <td>
                                        <div class="action-buttons">
                                            <form method="post" action="home.php" style="display:inline;">
                                                <input type="hidden" name="leave_id" value="<?php echo $application['id']; ?>">
                                                <input type="hidden" name="manage_leave">
                                                <input type="submit" name="action" value="approved">
                                            </form>
                                            <form method="post" action="home.php" style="display:inline;">
                                                <input type="hidden" name="leave_id" value="<?php echo $application['id']; ?>">
                                                <input type="hidden" name="manage_leave">
                                                <input type="submit" name="action" value="rejected">
                                            </form>
                                        </div>
                                    </td>
                                <?php endif; ?>
                                <?php if ($application['status'] == 'rejected' || $application['status'] == 'approved'): ?>
                                    <td></td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php else: ?>
                    <p>No leave applications found.</p>
                <?php endif; ?>
            <?php endif; ?>
            <?php if ($user_role == 'super'): ?>
                <h2>User Punch Ins and Punch Outs (Sunday to Thursday)</h2>
                <?php if (count($organized_punch_records) > 0): ?>
                    <table border="1">
                        <tr>
                            <th></th>
                            <th>Name</th>
                            <?php
                            // Generate table headers for each day from Sunday to Thursday
                            for ($i = 0; $i < 5; $i++) {
                                $day = (clone $start_of_week)->modify("+$i days");
                                echo "<th>" . $day->format('D') . "<br>" . $day->format('Y-m-d') . "</th>";
                            }
                            ?>
                        </tr>
                        <?php
                        $count = 1;
                        foreach ($organized_punch_records as $user_id => $log_data): ?>
                            <tr>
                                <td><?php echo $count++; ?></td>
                                <td><?php echo htmlspecialchars($log_data['name']); ?></td>
                                <?php
                                for ($i = 0; $i < 5; $i++) {
                                    $day = (clone $start_of_week)->modify("+$i days")->format('Y-m-d');
                                    echo "<td>";
                                    if (isset($log_data['dates'][$day])) {
                                        foreach ($log_data['dates'][$day] as $log) {
                                            $punch_in_time = $log['punch_in_time']->format('H:i');
                                            $punch_out_time = $log['punch_out_time'] ? $log['punch_out_time']->format('H:i') : 'No logout';

                                            $punch_in_style = ($log['punch_in_time']->format('H:i') >= '09:00') ? 'highlight-red' : '';
                                            $punch_out_style = ($log['punch_out_time'] && $log['punch_out_time']->format('H:i') <= '17:00') ? 'highlight-red' : '';

                                            echo "<div class='$punch_in_style'>Punch In: $punch_in_time</div>";
                                            echo "<div class='$punch_out_style'>Punch Out: $punch_out_time</div>";
                                        }
                                    } else {
                                        echo "No record";
                                    }
                                    echo "</td>";
                                }
                                ?>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php else: ?>
                    <p>No punch ins or punch outs found for the specified period.</p>
                <?php endif; ?>
            <?php endif; ?>
            <h2>Leave Records</h2>
            <?php
            $count = 1;
            if (count($leave_records) > 0): ?>
                <table border="1">
                    <tr>
                        <th></th>
                        <th>Name</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Reason</th>
                        <th>Status</th>
                    </tr>
                    <?php foreach ($leave_records as $record): ?>
                        <tr>
                            <td><?php echo $count++; ?></td>
                            <td><?php echo htmlspecialchars($record['name']); ?></td>
                            <td><?php echo htmlspecialchars($record['start_date']); ?></td>
                            <td><?php echo htmlspecialchars($record['end_date']); ?></td>
                            <td><?php echo htmlspecialchars($record['reason']); ?></td>
                            <td><?php echo htmlspecialchars($record['status']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php else: ?>
                <p>No leave records found.</p>
            <?php endif; ?>
        </div>
    </div>
    <?php include 'footer.php'; ?>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
</body>

</html>
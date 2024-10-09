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

// Handle password change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];

    // Verify current password
    if ($stmt = $conn->prepare("SELECT password FROM users WHERE id = ?")) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_data = $result->fetch_assoc();
        $stmt->close();

        // Check if the current password is correct
        if (password_verify($current_password, $user_data['password'])) {
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            if ($stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?")) {
                $stmt->bind_param("si", $hashed_password, $user_id);
                if ($stmt->execute()) {
                    echo "<script>alert('Password updated successfully.'); window.location.href = 'profile.php';</script>";
                } else {
                    echo "<script>alert('Error updating password: " . $conn->error . "');</script>";
                }
                $stmt->close();
            }
        } else {
            echo "<script>alert('Current password is incorrect.');</script>";
        }
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Bank Details</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="css/styles.css">
</head>

<body>
    <?php include 'header.php'; ?>
    <div class="content">
        <div class="change-password-container">
            <h1>Change Password</h1>
            <form method="post" action="change_password.php">
                <div class="form-group">
                    <label for="current_password">Current Password:</label>
                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                </div>
                <div class="form-group">
                    <label for="new_password">New Password:</label>
                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                </div>
                <div class="button-container">
                    <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                </div>
            </form>
        </div>
    </div>
    <?php include 'footer.php'; ?>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
</body>

</html>
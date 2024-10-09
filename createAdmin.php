<?php
include 'index.php';
include 'db.php'; // Include your database connection

checkLogin();

$logo_sql = "SELECT logo_path FROM settings WHERE id = 1";
$result = $conn->query($logo_sql);
$row = $result->fetch_assoc();
$logo_path = $row['logo_path'] ?? 'images/logo.png'; // Use a default logo if not set

$user_role = $_SESSION['role'];
// Retrieve staff users with their profile pictures, excluding archived users
$staff_users = [];
$sql = "SELECT id, name, email, phone_number, picture FROM users WHERE role = 'staff' AND archived = 0";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $staff_users[] = $row;
}

// Handle form submission to update role to admin
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_role'])) {
    $selected_users = $_POST['users'] ?? [];
    if (!empty($selected_users)) {
        $user_ids = implode(',', array_map('intval', $selected_users));
        $update_sql = "UPDATE users SET role = 'admin' WHERE id IN ($user_ids) AND archived = 0";
        if ($conn->query($update_sql) === TRUE) {
            echo "<script>alert('Selected users have been promoted to admin.'); window.location.href = 'createAdmin.php';</script>";
        } else {
            echo "<script>alert('Error updating users: " . $conn->error . "');</script>";
        }
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Header</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css?family=Montserrat" rel="stylesheet">
</head>

<body>
<?php include 'header.php'; ?>
    <div class="content">
        <div class="promote-staff-container">
            <h1>Promote Staff to Admin</h1>
            <form method="post" action="createAdmin.php">
                <div class="user-list">
                    <?php if (!empty($staff_users)): ?>
                        <?php foreach ($staff_users as $user): ?>
                            <div class="user-item">
                                <input type="checkbox" name="users[]" value="<?php echo htmlspecialchars($user['id']); ?>">
                                <img src="<?php echo 'photos/' . htmlspecialchars($user['picture']); ?>" alt="Profile Picture">
                                <div class="user-info">
                                    <h3><?php echo htmlspecialchars($user['name']); ?></h3>
                                    <p>Email: <?php echo htmlspecialchars($user['email']); ?></p>
                                    <p>Phone Number: <?php echo htmlspecialchars($user['phone_number']); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No staff users available to promote.</p>
                    <?php endif; ?>
                </div>
                <div class="button-container">
                    <input type="submit" name="update_role" value="Promote to Admin">
                </div>
            </form>
        </div>
    </div>
    <?php include 'footer.php'; ?>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
</body>

</html>
<?php
include 'index.php';
include 'db.php'; // Include your database connection

checkLogin();

$user_role = $_SESSION['role'];

$logo_sql = "SELECT logo_path FROM settings WHERE id = 1";
$result = $conn->query($logo_sql);
$row = $result->fetch_assoc();
$logo_path = $row['logo_path'] ?? 'images/logo.png'; // Use a default logo if not set

// Ensure only super admins and admins can access this page
if ($user_role !== 'admin' && $user_role !== 'super') {
    echo "Access denied.";
    exit();
}

// Handle archiving/unarchiving
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['archive'])) {
        $user_id = $_POST['user_id'];
        $archive_status = $_POST['archive'] == 'archive' ? 1 : 0;

        $sql = "UPDATE users SET archived = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $archive_status, $user_id);

        if ($stmt->execute()) {
            echo "<script>alert('User status updated successfully.'); window.location.href = 'archive.php';</script>";
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    } elseif (isset($_POST['delete'])) {
        $user_id = $_POST['user_id'];

        // Delete the user
        $sql = "DELETE FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);

        if ($stmt->execute()) {
            echo "<script>alert('User deleted successfully.'); window.location.href = 'archive.php';</script>";
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Fetch staff users
$staff_users = [];
$sql = "SELECT id, name, archived FROM users WHERE role = 'staff'";

$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $staff_users[] = $row;
}
$stmt->close();

$conn->close();
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
        <div class="archive-staff">
            <h1>Archive/Unarchive or Delete Staff</h1>
            <table class="table table-bordered staff-table">
                <tr>
                    <th>Name</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
                <?php foreach ($staff_users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                        <td><?php echo $user['archived'] ? 'Archived' : 'Active'; ?></td>
                        <td>
                            <div class="action-buttons">
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <input type="hidden" name="archive" value="<?php echo $user['archived'] ? 'unarchive' : 'archive'; ?>">
                                    <button type="submit" class="btn btn-<?php echo $user['archived'] ? 'success' : 'warning'; ?>">
                                        <?php echo $user['archived'] ? 'Unarchive' : 'Archive'; ?>
                                    </button>
                                </form>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" name="delete" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this user?');">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
    <?php include 'footer.php'; ?>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
</body>

</html>
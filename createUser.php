<?php
include 'index.php';
include 'db.php'; // Include your database connection

// Fetch user ID from session or other authentication method
checkLogin();

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Initialize message variable
$messages = [];

// Handle form submission if it is a POST request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check if required fields are present
    if (isset($_POST['ic_number']) && !empty($_POST['ic_number']) && isset($_POST['role']) && !empty($_POST['role']) && isset($_POST['date_joined']) && !empty($_POST['date_joined'])) {
        $ic_number = trim($_POST['ic_number']);
        $role = trim($_POST['role']);
        $date_joined = trim($_POST['date_joined']);

        // Validate role
        if (!in_array($role, ['staff', 'admin'])) {
            $messages[] = 'Invalid role selected.';
        } else {
            // Default password
            $password = 'vsglabs';

            // Hash the password (use bcrypt hashing for security)
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            // Get current date for date_created
            $date_created = date('Y-m-d');

            // Insert new user into the database
            $insert_sql = "INSERT INTO users (ic_number, password, role, date_joined, date_created) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param('sssss', $ic_number, $hashed_password, $role, $date_joined, $date_created);

            if ($stmt->execute()) {
                $messages[] = 'User created successfully with default password.';
            } else {
                $messages[] = 'Error creating user: ' . $stmt->error;
            }
            $stmt->close();
        }
    } else {
        $messages[] = 'IC number, role, and date joined are required.';
    }
}

// Prepare message string
$message_str = implode('<br>', $messages);
?>

<!DOCTYPE html>
<html>

<head>
    <title>Create New Staff</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css?family=Montserrat" rel="stylesheet">
    <style>
        .form-control-custom {
            width: 100%;
            height: auto;
            margin-bottom: 15px;
        }

        .form-label {
            margin-bottom: 5px;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <?php include 'header.php'; ?>
    <div class="content">
        <div class="create-staff-container">
            <h1>Create New Staff</h1>
            <form method="post">
                <div class="form-group">
                    <label for="ic_number" class="form-label">IC Number:</label>
                    <input type="text" name="ic_number" id="ic_number" class="form-control form-control-custom" required>
                </div>
                <div class="form-group">
                    <label for="date_joined" class="form-label">Date Joined:</label>
                    <input type="date" name="date_joined" id="date_joined" class="form-control form-control-custom" required>
                </div>
                <div class="form-group">
                    <label for="role" class="form-label">Role:</label>
                    <select name="role" id="role" class="form-control form-control-custom" required>
                        <option value="">Select Role</option>
                        <option value="staff">Staff</option>
                        <?php if ($user_role == 'super'): ?>
                            <option value="admin">Admin</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="button-container">
                    <button type="submit" class="btn btn-primary">Create User</button>
                </div>
            </form>
            <?php if (!empty($messages)): ?>
                <script>
                    alert("<?php echo addslashes($message_str); ?>");
                </script>
            <?php endif; ?>
        </div>
    </div>
    <?php include 'footer.php'; ?>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
</body>

</html>
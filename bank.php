<?php
include 'index.php';
include 'db.php'; // Include your database connection

// Fetch user ID from session or other authentication method
checkLogin();

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

$logo_sql = "SELECT logo_path FROM settings WHERE id = 1";
$result = $conn->query($logo_sql);
$row = $result->fetch_assoc();
$logo_path = $row['logo_path'] ?? 'images/logo.png'; // Use a default logo if not set

// Handle form submission to insert or update bank details
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $bank_name = $_POST['bank_name'] ?? '';
    $account_number = $_POST['account_number'] ?? '';

    // Handle file upload
    $statement_file = $_FILES['statement_file'] ?? null;
    $statement_file_path = '';

    if ($statement_file && $statement_file['error'] == 0) {
        // Validate file type
        $allowed_types = ['application/pdf'];
        if (in_array($statement_file['type'], $allowed_types)) {
            $upload_dir = 'statements/';
            $file_name = basename($statement_file['name']);
            $target_file = $upload_dir . $file_name;

            // Move uploaded file to the target directory
            if (move_uploaded_file($statement_file['tmp_name'], $target_file)) {
                $statement_file_path = $target_file;
            } else {
                echo "<script>alert('Error uploading the bank statement file.');</script>";
            }
        } else {
            echo "<script>alert('Only PDF files are allowed.');</script>";
        }
    }

    if ($user_id) {
        // Check if bank details already exist for this user
        $check_sql = "SELECT id FROM bank_details WHERE user_id = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Update existing record
            $update_sql = "UPDATE bank_details SET bank_name = ?, account_number = ?, statement_file = ? WHERE user_id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param('sssi', $bank_name, $account_number, $statement_file_path, $user_id);
            if ($stmt->execute()) {
                echo "<script>alert('Bank details updated successfully.');</script>";
            } else {
                echo "<script>alert('Error updating bank details: " . $conn->error . "');</script>";
            }
        } else {
            // Insert new record
            $insert_sql = "INSERT INTO bank_details (user_id, bank_name, account_number, statement_file) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param('isss', $user_id, $bank_name, $account_number, $statement_file_path);
            if ($stmt->execute()) {
                echo "<script>alert('Bank details added successfully.');</script>";
            } else {
                echo "<script>alert('Error adding bank details: " . $conn->error . "');</script>";
            }
        }
        $stmt->close();
    }
}

// Fetch existing bank details for the user
$bank_details = [];
if ($user_id) {
    $fetch_sql = "SELECT bank_name, account_number, statement_file FROM bank_details WHERE user_id = ?";
    $stmt = $conn->prepare($fetch_sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $bank_details = $result->fetch_assoc();
    }
    $stmt->close();
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
        <div class="bank-details-container">
            <h1>Bank Details</h1>
            <form method="post" action="bank.php" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="bank_name">Bank Name:</label>
                    <input type="text" class="form-control" id="bank_name" name="bank_name" value="<?php echo htmlspecialchars($bank_details['bank_name'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="account_number">Account Number:</label>
                    <input type="text" class="form-control" id="account_number" name="account_number" value="<?php echo htmlspecialchars($bank_details['account_number'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="statement_file">Bank Statement (PDF):</label>
                    <input type="file" class="form-control" id="statement_file" name="statement_file" accept=".pdf">
                </div>
                <div class="button-container">
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
    <?php include 'footer.php'; ?>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
</body>

</html>
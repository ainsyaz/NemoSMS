<?php
include 'index.php';
include 'db.php'; // Include your database connection

// Fetch user ID from session or other authentication method
checkLogin();

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Initialize message variable
$messages = [];

// Define function to insert or update settings
function saveCompanySettings($conn, $company_name, $address, $registration_number, $logo_path)
{
    // Check if the settings already exist
    $check_sql = "SELECT id FROM settings WHERE id = 1";
    $result = $conn->query($check_sql);

    if ($result->num_rows > 0) {
        // Record exists, perform an update
        $update_sql = "UPDATE settings 
                       SET company_name = ?, address = ?, registration_number = ?, logo_path = ? 
                       WHERE id = 1";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param('ssss', $company_name, $address, $registration_number, $logo_path);
        if ($stmt->execute()) {
            return 'Settings updated successfully.';
        } else {
            return 'Error updating settings: ' . $stmt->error;
        }
        $stmt->close();
    } else {
        // Record does not exist, perform an insert
        $insert_sql = "INSERT INTO settings (id, company_name, address, registration_number, logo_path) 
                       VALUES (1, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param('ssss', $company_name, $address, $registration_number, $logo_path);
        if ($stmt->execute()) {
            return 'Settings inserted successfully.';
        } else {
            return 'Error inserting settings: ' . $stmt->error;
        }
        $stmt->close();
    }
}

// Fetch current settings from database
$settings_sql = "SELECT logo_path, company_name, address, registration_number FROM settings WHERE id = 1";
$result = $conn->query($settings_sql);
$row = $result->fetch_assoc();
$logo_path = $row['logo_path'] ?? 'images/logo.png'; // Use default logo if not set
$company_name = $row['company_name'] ?? ''; // Use default if company name is not set
$address = $row['address'] ?? ''; // Use default if address is not set
$registration_number = $row['registration_number'] ?? ''; // Use default if registration number is not set

if ($user_role == 'super') {
    // Handle form submission if it is a POST request
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Update company name if provided
        $new_company_name = !empty($_POST['name']) ? trim($_POST['name']) : $company_name;
        $new_address = !empty($_POST['address']) ? trim($_POST['address']) : $address;
        $new_registration_number = !empty($_POST['registration_number']) ? trim($_POST['registration_number']) : $registration_number;

        // Handle file upload if logo is provided
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK && $_FILES['logo']['size'] > 0) {
            $target_dir = "images/"; // Ensure this directory exists and is writable
            $target_file = $target_dir . basename($_FILES["logo"]["name"]);
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

            // Check if the file is an actual image
            $check = getimagesize($_FILES["logo"]["tmp_name"]);
            if ($check !== false) {
                // Check file size (e.g., 500KB max)
                if ($_FILES["logo"]["size"] <= 500000) {
                    // Allow certain file formats (e.g., jpg, png)
                    if (in_array($imageFileType, ["jpg", "png", "jpeg", "gif"])) {
                        // Upload the file
                        if (move_uploaded_file($_FILES["logo"]["tmp_name"], $target_file)) {
                            // Update the logo path
                            $logo_path = $target_file; // Relative path to the uploaded file
                        } else {
                            $messages[] = 'Error uploading the file. Please check directory permissions.';
                        }
                    } else {
                        $messages[] = 'Only JPG, JPEG, PNG & GIF files are allowed.';
                    }
                } else {
                    $messages[] = 'Sorry, your file is too large.';
                }
            } else {
                $messages[] = 'File is not an image.';
            }
        }

        // Save settings (insert or update)
        $message = saveCompanySettings($conn, $new_company_name, $new_address, $new_registration_number, $logo_path);
        $messages[] = $message;

        if (strpos($message, 'successfully') !== false) {
            echo "<script>
                alert('Operation completed successfully.');
                window.location.href = 'manage_company.php?status=success';
            </script>";
            exit;
        }
        
    }
}

// Prepare message string
$message_str = implode('<br>', $messages);
?>
<!DOCTYPE html>
<html>

<head>
    <title>Manage Company Information</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="css/styles.css">
</head>

<body>
    <?php include 'header.php'; ?>
    <div class="content">
        <div class="manage-company">
            <h1>Manage Company Information</h1>
            <form method="post" enctype="multipart/form-data">
                <label for="name">Update Company Name:</label>
                <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($company_name); ?>">
                <label for="address">Update Address:</label>
                <input type="text" name="address" id="address" value="<?php echo htmlspecialchars($address); ?>">
                <label for="registration_number">Update Registration Number:</label>
                <input type="text" name="registration_number" id="registration_number" value="<?php echo htmlspecialchars($registration_number); ?>">
                <label for="logo">Upload New Logo:</label>
                <input type="file" name="logo" id="logo" accept=".jpg,.jpeg,.png,.gif">
                <div class="button-container">
                    <button type="submit" class="btn btn-primary">Update Information</button>
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
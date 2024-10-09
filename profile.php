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

$user = [];
if ($stmt = $conn->prepare("SELECT id, name, email, phone_number, picture, date_birth, address FROM users WHERE id = ?")) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone_number = $_POST['phone_number'];
    $date_birth = $_POST['date_birth'];
    $address = $_POST['address'];

    // Handle picture upload and compression
    $picture = $user['picture']; // Keep current picture if not updated
    if (isset($_FILES['picture']) && $_FILES['picture']['error'] == UPLOAD_ERR_OK) {
        $picture_name = basename($_FILES['picture']['name']);
        $picture_tmp_path = $_FILES['picture']['tmp_name'];
        $extension = strtolower(pathinfo($picture_name, PATHINFO_EXTENSION));

        // Generate a new standard name for the image
        $new_picture_name = "user_" . $user_id . "_" . time() . "." . $extension;
        $new_picture_path = 'photos/' . $new_picture_name;

        // Compress and move the uploaded image based on its type (jpeg or png)
        if ($extension == 'jpeg' || $extension == 'jpg') {
            $image = imagecreatefromjpeg($picture_tmp_path);
            // Compress to 75% quality
            if (imagejpeg($image, $new_picture_path, 75)) {
                $picture = $new_picture_name; // Update picture name
            } else {
                echo "<script>alert('Error uploading and compressing JPEG picture.');</script>";
            }
            imagedestroy($image); // Free up memory
        } elseif ($extension == 'png') {
            $image = imagecreatefrompng($picture_tmp_path);
            // Compress PNG by reducing quality, setting compression level (0 - no compression, 9 - max compression)
            if (imagepng($image, $new_picture_path, 6)) {
                $picture = $new_picture_name; // Update picture name
            } else {
                echo "<script>alert('Error uploading and compressing PNG picture.');</script>";
            }
            imagedestroy($image); // Free up memory
        } else {
            echo "<script>alert('Unsupported image format. Please upload JPG or PNG.');</script>";
        }
    }

    // Update user details in the database
    if ($stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone_number = ?, date_birth = ?, address = ?, picture = ? WHERE id = ?")) {
        $stmt->bind_param("ssssssi", $name, $email, $phone_number, $date_birth, $address, $picture, $user_id);
        if ($stmt->execute()) {
            echo "<script>alert('Profile updated successfully.'); window.location.href = 'profile.php';</script>";
        } else {
            echo "<script>alert('Error updating profile: " . $conn->error . "');</script>";
        }
        $stmt->close();
    }
}

?>

<!DOCTYPE html>
<html>

<head>
    <title>Profile</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="css/styles.css">
</head>

<body>
    <?php include 'header.php'; ?>
    <div class="content">
        <div class="update-profile-container">
            <h1>Profile</h1>

            <!-- Update Profile Form -->
            <form method="post" action="profile.php" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name">Name:</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="phone_number">Phone Number:</label>
                    <input type="text" class="form-control" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($user['phone_number']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="phone_number">Date Of Birth:</label>
                    <input type="date" class="form-control" id="date_birth" name="date_birth" value="<?php echo htmlspecialchars($user['date_birth']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="address">Address:</label>
                    <input type="text" class="form-control" id="address" name="address" value="<?php echo htmlspecialchars($user['address']); ?>">
                </div>
                <div class="form-group">
                    <label for="picture">Profile Picture:</label>
                    <input type="file" class="form-control" id="picture" name="picture">
                    <?php if ($user['picture']): ?>
                        <img src="<?php echo 'photos/' . htmlspecialchars($user['picture']); ?>" alt="Profile Picture" style="width: 150px; height: auto; margin-top: 10px;">
                    <?php endif; ?>
                </div>
                <div class="button-container">
                    <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                </div>
            </form>

            <hr>
            <a href="change_password.php" class="btn btn-secondary">Change Password</a>
        </div>
    </div>
    <?php include 'footer.php'; ?>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
</body>

</html>
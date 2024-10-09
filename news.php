<?php
include 'index.php';
include 'db.php';
checkLogin();

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

$logo_sql = "SELECT logo_path FROM settings WHERE id = 1";
$result = $conn->query($logo_sql);
$row = $result->fetch_assoc();
$logo_path = $row['logo_path'] ?? 'images/logo.png'; // Use a default logo if not set

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];

    // File upload handling
    $target_dir = "news/";
    $file_name = basename($_FILES["file"]["name"]);
    $target_file = $target_dir . $file_name;
    $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Check if file is a valid image or PDF
    $allowed_types = array('jpg', 'jpeg', 'png', 'gif', 'pdf');
    if (in_array($file_type, $allowed_types)) {
        if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
            // Insert news data into the database
            $sql = "INSERT INTO news (title, description, file_path) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('sss', $title, $description, $target_file);

            if ($stmt->execute()) {
                // Get the ID of the newly inserted news item
                $news_id = $stmt->insert_id;

                // Notify only admins and staff about the new announcement
                $users_sql = "SELECT id FROM users WHERE role IN ('admin', 'staff')";
                $result = $conn->query($users_sql);

                while ($row = $result->fetch_assoc()) {
                    $user_id = $row['id'];
                    $insert_notification_sql = "INSERT INTO notifications (user_id, news_id, is_read) VALUES (?, ?, 0)";
                    $notify_stmt = $conn->prepare($insert_notification_sql);
                    $notify_stmt->bind_param('ii', $user_id, $news_id);
                    $notify_stmt->execute();
                }

                echo "<script>alert('News and notifications added successfully.');</script>";
            } else {
                echo "<script>alert('Error: " . mysqli_error($conn) . "');</script>";
            }
            $stmt->close();
        } else {
            echo "<script>alert('Sorry, there was an error uploading your file.');</script>";
        }
    } else {
        echo "<script>alert('Sorry, only JPG, JPEG, PNG, GIF, and PDF files are allowed.');</script>";
    }
}

// Fetch and display news
$sql = "SELECT * FROM news ORDER BY created_at DESC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>News and Announcements</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/styles.css">
</head>

<body>
    <?php include 'header.php'; ?>

    <div class="content">
        <div class="news-container">
            <?php if ($user_role != 'staff'): ?>
                <h1>Add News or Announcement</h1>
                <form action="news.php" method="post" enctype="multipart/form-data">
                    <label for="title">Title:</label>
                    <input type="text" id="title" name="title" required><br><br>

                    <label for="description">Description:</label>
                    <textarea id="description" name="description" rows="4" required></textarea><br><br>

                    <label for="file">Upload Poster (Image/PDF):</label>
                    <input type="file" id="file" name="file" accept="image/*,application/pdf" required><br><br>

                    <input type="submit" value="Add News">
                </form>
            <?php endif; ?>

            <h1>News and Announcements</h1>
            <?php
            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $news_id = $row['id'];
                    echo "<div class='news-item'>";
                    echo "<h3>Title: " . $row['title'] . "</h3>";
                    echo "<p>Description: " . $row['description'] . "</p>";

                    $file_path = $row['file_path'];
                    $file_type = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));

                    if (in_array($file_type, ['jpg', 'jpeg', 'png', 'gif'])) {
                        echo "<img src='" . $file_path . "' alt='News Poster' style='max-width: 100%; height: auto;'>";
                    } elseif ($file_type == 'pdf') {
                        echo "<a href='" . $file_path . "' target='_blank'>View PDF Poster</a>";
                    }

                    echo "</div><hr>";

                    // Mark the notification as read only for the current user
                    if ($user_role != 'super') {
                        $update_sql = "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND news_id = ?";
                        $update_stmt = $conn->prepare($update_sql);
                        $update_stmt->bind_param('ii', $user_id, $news_id);
                        $update_stmt->execute();
                        $update_stmt->close();
                    }
                }
            } else {
                echo "<p>No news or announcements available.</p>";
            }

            // Close the database connection
            mysqli_close($conn);
            ?>
        </div>
    </div>
    <?php include 'footer.php'; ?>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
</body>

</html>
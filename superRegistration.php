<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    include 'db.php'; // Ensure this file includes necessary database connection setup

    $ic_number = $_POST['ic_number'];
    $password = $_POST['password'];
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $date_created = date('Y-m-d'); // Get today's date

    $sql = "INSERT INTO users (ic_number, password, role, date_created) VALUES (?, ?, 'super', ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $ic_number, $hashed_password, $date_created);

    if ($stmt->execute()) {
        echo "<script>alert('Super admin registered successfully!'); window.location.href = 'login.php';</script>";
    } else {
        echo "<script>alert('Error registering super admin: " . $stmt->error . "'); window.location.href = 'superRegistration.php';</script>";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Super Admin Registration</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="css/styles.css">
</head>

<body>
    <div class="header">
        <nav class="navbar navbar-default">
            <div class="container">
                <a class="navbar-brand" href="home.php"></a>
            </div>
        </nav>
    </div>
    <div class="content">
        <div class="super-admin-registration">
            <h1>Super Admin Registration</h1>
            <form method="post" action="superRegistration.php">
                <div class="form-group">
                    <label for="ic_number">IC Number:</label>
                    <input type="text" class="form-control" id="ic_number" name="ic_number" required>
                </div>
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="button-container">
                    <button type="submit" class="btn btn-primary">Register</button>
                </div>
            </form>
        </div>
    </div>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
</body>

</html>
<?php
include 'db.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ic_number = $_POST['ic_number'];
    $password = $_POST['password'];

    $sql = "SELECT id, password, role FROM users WHERE ic_number = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $ic_number);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($id, $hashed_password, $role);

    if ($stmt->num_rows > 0) {
        $stmt->fetch();
        if (password_verify($password, $hashed_password)) {
            $_SESSION['user_id'] = $id;
            $_SESSION['role'] = $role;

            header("Location: home.php");
        } else {
            echo "<script>alert('Wrong username or password.'); window.location.href = 'login.php';</script>";
        }
    } else {
        echo "<script>alert('Wrong username or password.'); window.location.href = 'login.php';</script>";
    }

    $stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html>

<head>
    <title>Login Page</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="css/styles.css">
</head>

<body>
    <div class="content-login">
        <div class="login-container">
            <h1>Sign In</h1>
            <form method="post" action="login.php">
                <label>Ic Number: </label><input type="text" name="ic_number" required><br>
                <label>Password: </label><input type="password" name="password" required><br>
                <div class="button-container">
                    <input type="submit" value="Login">
                </div>
            </form>
        </div>
    </div>
    <?php include 'footer.php'; ?>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
</body>

</html>
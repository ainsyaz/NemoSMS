<?php
include 'index.php';
include 'db.php';

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    session_destroy();
    header("Location: login.php");
} else {
    echo "You are not logged in.";
}
$conn->close();
?>

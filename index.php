<?php
session_start();

function checkLogin() {
    // Set timeout duration (in seconds)
    $timeout_duration = 3600; // 1 hour

    // Check if the user is logged in
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }

    // Check last activity time
    if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
        // Session has expired
        session_unset();     // Unset session variables
        session_destroy();   // Destroy the session
        header("Location: login.php");
        exit();
    }

    // Update last activity time
    $_SESSION['LAST_ACTIVITY'] = time();
}

// Call the function to check login status
checkLogin();

// Rest of your index.php code goes here
?>

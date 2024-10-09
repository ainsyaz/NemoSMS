<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $database = $_POST['database'];

    // Default values
    $servername = "localhost";
    $username = "root";
    $password = "";

    // Create connection
    $conn = new mysqli($servername, $username, $password);

    // Check connection
    if ($conn->connect_error) {
        echo "<script>alert('Connection failed: " . $conn->connect_error . "'); window.location.href = 'install.php';</script>";
    } else {
        // Create database
        $sql = "CREATE DATABASE IF NOT EXISTS `$database`";
        if ($conn->query($sql) === TRUE) {
            // Select the database
            $conn->select_db($database);

            // SQL to create tables
            $table_sql = "
                -- Table structure for table `bank_details`
                CREATE TABLE IF NOT EXISTS `bank_details` (
                `id` int(11) NOT NULL,
                `user_id` int(11) NOT NULL,
                `bank_name` varchar(255) NOT NULL,
                `account_number` varchar(255) NOT NULL,
                `statement_file` varchar(255) DEFAULT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

                -- Table structure for table `leave_applications`
                CREATE TABLE IF NOT EXISTS `leave_applications` (
                `id` int(11) NOT NULL,
                `user_id` int(11) NOT NULL,
                `start_date` date NOT NULL,
                `end_date` date NOT NULL,
                `reason` text DEFAULT NULL,
                `status` enum('pending','approved','rejected') DEFAULT 'pending',
                `file_path` varchar(255) DEFAULT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

                -- Table structure for table `news`
                CREATE TABLE IF NOT EXISTS `news` (
                `id` int(11) NOT NULL,
                `title` varchar(255) NOT NULL,
                `description` text NOT NULL,
                `file_path` varchar(255) DEFAULT NULL,
                `created_at` timestamp NOT NULL DEFAULT current_timestamp()
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

                -- Table structure for table `notifications`
                CREATE TABLE IF NOT EXISTS `notifications` (
                `id` int(11) NOT NULL,
                `user_id` int(11) DEFAULT NULL,
                `news_id` int(11) DEFAULT NULL,
                `is_read` tinyint(1) DEFAULT 0,
                `created_at` timestamp NOT NULL DEFAULT current_timestamp()
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

                -- Table structure for table `settings`
                CREATE TABLE IF NOT EXISTS `settings` (
                `id` int(11) NOT NULL,
                `logo_path` varchar(255) NOT NULL,
                `company_name` varchar(255) NOT NULL,
                `address` varchar(255) NOT NULL,
                `registration_number` varchar(255) NOT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

                -- Table structure for table `users`
                CREATE TABLE IF NOT EXISTS `users` (
                `id` int(11) NOT NULL,
                `ic_number` varchar(20) NOT NULL,
                `phone_number` varchar(11) NOT NULL,
                `email` varchar(30) NOT NULL,
                `name` varchar(30) NOT NULL,
                `password` varchar(255) NOT NULL,
                `role` varchar(5) NOT NULL,
                `leave_balance` int(11) DEFAULT 20,
                `date_birth` date NOT NULL,
                `address` varchar(50) NOT NULL,
                `date_joined` date DEFAULT NULL,
                `date_created` date DEFAULT NULL,
                `picture` varchar(50) NOT NULL,
                `archived` tinyint(1) DEFAULT 0
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

                -- Table structure for table `user_activity`
                CREATE TABLE IF NOT EXISTS `user_activity` (
                `id` int(11) NOT NULL,
                `user_id` int(11) NOT NULL,
                `punch_in_time` timestamp NULL DEFAULT current_timestamp(),
                `punch_out_time` datetime DEFAULT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

                -- Indexes and constraints
                ALTER TABLE `bank_details`
                ADD PRIMARY KEY (`id`),
                ADD KEY `user_id` (`user_id`);

                ALTER TABLE `leave_applications`
                ADD PRIMARY KEY (`id`),
                ADD KEY `user_id` (`user_id`);

                ALTER TABLE `news`
                ADD PRIMARY KEY (`id`);

                ALTER TABLE `notifications`
                ADD PRIMARY KEY (`id`);

                ALTER TABLE `settings`
                ADD PRIMARY KEY (`id`);

                ALTER TABLE `users`
                ADD PRIMARY KEY (`id`),
                ADD UNIQUE KEY `ic_number` (`ic_number`);

                ALTER TABLE `user_activity`
                ADD PRIMARY KEY (`id`),
                ADD KEY `fk_user` (`user_id`);

                ALTER TABLE `bank_details`
                MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

                ALTER TABLE `leave_applications`
                MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

                ALTER TABLE `news`
                MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

                ALTER TABLE `notifications`
                MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

                ALTER TABLE `settings`
                MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

                ALTER TABLE `users`
                MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

                ALTER TABLE `user_activity`
                MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

                -- Constraints
                ALTER TABLE `bank_details`
                ADD CONSTRAINT `bank_details_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

                ALTER TABLE `leave_applications`
                ADD CONSTRAINT `leave_applications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

                ALTER TABLE `user_activity`
                ADD CONSTRAINT `fk_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
            ";

            // Execute the multi_query and check for errors
            if ($conn->multi_query($table_sql)) {
                do {
                    if ($result = $conn->store_result()) {
                        $result->free();
                    }
                } while ($conn->more_results() && $conn->next_result());

                if ($conn->errno) {
                    echo "<script>alert('Error creating tables: " . $conn->error . "'); window.location.href = 'install.php';</script>";
                } else {
                    // Save database configuration to config.php
                    $config_content = "<?php\n";
                    $config_content .= "\$servername = '$servername';\n";
                    $config_content .= "\$username = '$username';\n";
                    $config_content .= "\$password = '$password';\n";
                    $config_content .= "\$dbname = '$database';\n";
                    $config_content .= "?>";

                    file_put_contents('config.php', $config_content);

                    echo "<script>alert('Database and tables created successfully. Redirecting to super admin registration.'); window.location.href = 'superRegistration.php';</script>";
                }
            } else {
                echo "<script>alert('Error creating tables: " . $conn->error . "'); window.location.href = 'install.php';</script>";
            }
        } else {
            echo "<script>alert('Error creating database: " . $conn->error . "'); window.location.href = 'install.php';</script>";
        }

        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Install</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
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
    <div class="content-install">
        <div class="db-container">
            <h1>Installation</h1>
            <form method="post" action="install.php">
                <label for="database">Database Name:</label>
                <input type="text" name="database" id="database" required><br><br>
                <div class="button-container">
                    <input type="submit" value="Install">
                </div>
            </form>
        </div>
    </div>
    <script src="http://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
    <script src="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
</body>

</html>
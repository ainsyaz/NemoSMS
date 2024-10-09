<?php
include 'db.php'; // Include your database connection

// Fetch company name from settings
$company_sql = "SELECT company_name FROM settings WHERE id = 1";
$result = $conn->query($company_sql);
$row = $result->fetch_assoc();
$company_name = $row['company_name'] ?? 'Your Company Name'; // Use default if not set

// Fetch company address and registration number if stored in settings
$address_sql = "SELECT address, registration_number FROM settings WHERE id = 1";
$result = $conn->query($address_sql);
$row = $result->fetch_assoc();
$address = $row['address'] ?? 'Your Company Address, City, State, ZIP Code'; // Default address
$registration_number = $row['registration_number'] ?? '123456789'; // Default registration number
?>
<div class="footer">
    <div class="company-info">
        <p>Company Address: <?php echo htmlspecialchars($address); ?></p>
        <p>Company Registration Number: <?php echo htmlspecialchars($registration_number); ?></p>
    </div>
    <div class="copyright">
        <p>Copyright &copy; <?php echo date("Y"); ?> <?php echo htmlspecialchars($company_name); ?>. All rights reserved.</p>
    </div>
</div>
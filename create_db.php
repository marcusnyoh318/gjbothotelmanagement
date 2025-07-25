<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'hotel_db';

// Connect to MySQL server (no DB yet)
$conn = new mysqli($host, $user, $pass);
if ($conn->connect_error) {
    die("<h2 style='color:red;'>Connection failed: " . htmlspecialchars($conn->connect_error) . "</h2>");
}

// Create database if not exists
if (!$conn->query("CREATE DATABASE IF NOT EXISTS $db")) {
    die("<h2 style='color:red;'>Database creation failed: " . htmlspecialchars($conn->error) . "</h2>");
}

// Select the database
if (!$conn->select_db($db)) {
    die("<h2 style='color:red;'>Database selection failed: " . htmlspecialchars($conn->error) . "</h2>");
}

// Create bookings table if not exists
$tableSql = "CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    guest_name VARCHAR(255) NOT NULL,
    room_type VARCHAR(100) NOT NULL,
    check_in DATE NOT NULL,
    check_out DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if (!$conn->query($tableSql)) {
    die("<h2 style='color:red;'>Table creation failed: " . htmlspecialchars($conn->error) . "</h2>");
}

echo "<h2 style='color:green;'>Database and table created successfully!</h2>";
echo "<a href='index.html'>Go to Booking Form</a> | <a href='admin.php'>Go to Admin Panel</a>";

$conn->close();
?>

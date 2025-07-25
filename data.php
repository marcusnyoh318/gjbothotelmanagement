<?php
session_start();

$host = 'localhost';
$db = 'hotel_db';
$user = 'root';
$pass = '';

// Connect directly to the database
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
  echo "<h2 style='color:red;'>Connection failed: " . htmlspecialchars($conn->connect_error) . "</h2>";
  exit;
}

// Check if bookings table exists, create if it doesn't
$table_check = $conn->query("SHOW TABLES LIKE 'bookings'");
if ($table_check->num_rows == 0) {
    $create_table_sql = "CREATE TABLE bookings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        guest_name VARCHAR(255) NOT NULL,
        room_type VARCHAR(100) NOT NULL,
        check_in DATE NOT NULL,
        check_out DATE NOT NULL,
        total_amount DECIMAL(10,2) DEFAULT 0,
        payment_status VARCHAR(50) DEFAULT 'pending',
        transaction_id VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if (!$conn->query($create_table_sql)) {
        echo "<h2 style='color:red;'>Error creating bookings table: " . htmlspecialchars($conn->error) . "</h2>";
        exit;
    }
} else {
    // Check if payment columns exist, add if they don't
    $columns_to_add = [
        'total_amount' => "ALTER TABLE bookings ADD COLUMN total_amount DECIMAL(10,2) DEFAULT 0",
        'payment_status' => "ALTER TABLE bookings ADD COLUMN payment_status VARCHAR(50) DEFAULT 'pending'",
        'transaction_id' => "ALTER TABLE bookings ADD COLUMN transaction_id VARCHAR(100)",
        'created_at' => "ALTER TABLE bookings ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP"
    ];
    
    foreach ($columns_to_add as $column => $sql) {
        $column_check = $conn->query("SHOW COLUMNS FROM bookings LIKE '$column'");
        if ($column_check->num_rows == 0) {
            $conn->query($sql);
        }
    }
}

// Validate and sanitize input
$name = isset($_POST['guestName']) ? trim($_POST['guestName']) : '';
$room = isset($_POST['roomType']) ? trim($_POST['roomType']) : '';
$checkIn = isset($_POST['checkIn']) ? $_POST['checkIn'] : '';
$checkOut = isset($_POST['checkOut']) ? $_POST['checkOut'] : '';

function showError($msg) {
  echo "<!DOCTYPE html>
<html lang='en'>
<head>
  <meta charset='UTF-8'>
  <title>Booking Error</title>
  <link rel='stylesheet' href='style.css'>
  <style>
    body { background: #181c24; color: #f4f4f4; }
    .container { background: #232a36; color: #f4f4f4; }
    .error { color: #ff6b6b; background: #2d1b1b; padding: 20px; border-radius: 8px; margin: 20px 0; }
    .back-link { color: #007bff; text-decoration: none; padding: 10px 20px; background: #1e3a5f; border-radius: 4px; display: inline-block; margin: 10px 5px; }
    .back-link:hover { background: #007bff; color: #fff; }
  </style>
</head>
<body>
  <div class='container' style='max-width:500px;margin-top:40px;'>
    <div class='error'>
      <h2>Booking Error</h2>
      <p>$msg</p>
    </div>
    <div style='text-align:center;'>
      <a href='index.html' class='back-link'>Back to Booking Form</a>
      <a href='user_bookings.php' class='back-link'>View My Bookings</a>
    </div>
  </div>
</body>
</html>";
  exit;
}

// Validation
if (!$name || !$room || !$checkIn || !$checkOut) {
  showError("All fields are required. Please fill in all the information.");
}

if (!preg_match('/^[a-zA-Z\s\.\-\']+$/', $name)) {
  showError("Guest name should only contain letters, spaces, periods, hyphens, and apostrophes.");
}

if (strlen($name) < 2 || strlen($name) > 100) {
  showError("Guest name must be between 2 and 100 characters.");
}

// Validate dates
$checkInDate = DateTime::createFromFormat('Y-m-d', $checkIn);
$checkOutDate = DateTime::createFromFormat('Y-m-d', $checkOut);
$today = new DateTime();
$today->setTime(0, 0, 0); // Set to beginning of day for comparison

if (!$checkInDate || !$checkOutDate) {
  showError("Invalid date format. Please select valid dates.");
}

if ($checkInDate < $today) {
  showError("Check-in date cannot be in the past.");
}

if ($checkOutDate <= $checkInDate) {
  showError("Check-out date must be after check-in date.");
}

// Validate room type
$valid_rooms = ['Single', 'Double', 'Suite'];
if (!in_array($room, $valid_rooms)) {
  showError("Invalid room type selected.");
}

// Store booking data in session and redirect to payment
$_SESSION['pending_booking'] = [
    'guest_name' => $name,
    'room_type' => $room,
    'check_in' => $checkIn,
    'check_out' => $checkOut
];

// Redirect to payment page
header('Location: payment.php');
exit;

$conn->close();
?>
<?php
session_start();

// Get booking ID from URL parameter
$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($booking_id <= 0) {
    header('Location: index.html');
    exit;
}

$host = 'localhost';
$db = 'hotel_db';
$user = 'root';
$pass = '';

// Connect to database
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("<h2 style='color:red;'>Connection failed: " . htmlspecialchars($conn->connect_error) . "</h2>");
}

// Fetch booking details
$stmt = $conn->prepare("SELECT * FROM bookings WHERE id = ?");
$stmt->bind_param("i", $booking_id);
$booking = null;

if ($stmt->execute()) {
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $booking = $result->fetch_assoc();
    }
}

if (!$booking) {
    echo "<h2 style='color:red;'>Booking not found.</h2>";
    echo "<a href='index.html'>Back to Home</a>";
    exit;
}

// Calculate details
$check_in = new DateTime($booking['check_in']);
$check_out = new DateTime($booking['check_out']);
$nights = $check_in->diff($check_out)->days;

// Room prices
$room_prices = [
    'Single' => 100,
    'Double' => 150,
    'Suite' => 250
];
$room_price = $room_prices[$booking['room_type']] ?? 100;
$subtotal = $room_price * $nights;
$tax = $subtotal * 0.10;
$total = isset($booking['total_amount']) ? floatval($booking['total_amount']) : ($subtotal + $tax);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Receipt - Booking #<?= $booking_id ?></title>
  <link rel="stylesheet" href="style.css">
  <style>
    body {
      background: #fff !important;
      color: #333;
      font-family: 'Courier New', monospace;
    }
    .container {
      background: #fff !important;
      color: #333;
      max-width: 600px;
      border: 2px solid #333;
      margin: 20px auto;
      padding: 30px;
    }
    .receipt-header {
      text-align: center;
      border-bottom: 2px solid #333;
      padding-bottom: 20px;
      margin-bottom: 20px;
    }
    .hotel-name {
      font-size: 2rem;
      font-weight: bold;
      margin-bottom: 5px;
    }
    .receipt-title {
      font-size: 1.2rem;
      margin-bottom: 10px;
    }
    .receipt-info {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
      margin: 20px 0;
    }
    .info-section h4 {
      border-bottom: 1px solid #333;
      margin-bottom: 10px;
      padding-bottom: 5px;
    }
    .info-row {
      display: flex;
      justify-content: space-between;
      margin: 8px 0;
    }
    .charges-section {
      border-top: 1px solid #333;
      border-bottom: 2px solid #333;
      padding: 15px 0;
      margin: 20px 0;
    }
    .total-row {
      font-weight: bold;
      font-size: 1.1em;
      border-top: 1px solid #333;
      padding-top: 10px;
      margin-top: 10px;
    }
    .payment-info {
      background: #f8f8f8;
      padding: 15px;
      border: 1px solid #ddd;
      margin: 20px 0;
    }
    .footer-info {
      text-align: center;
      font-size: 0.9em;
      color: #666;
      margin-top: 30px;
      border-top: 1px solid #333;
      padding-top: 15px;
    }
    .print-btn {
      background: #007bff;
      color: #fff;
      padding: 10px 20px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 16px;
      margin: 10px 5px;
    }
    .print-btn:hover {
      background: #0056b3;
    }
    @media print {
      .no-print {
        display: none !important;
      }
      .container {
        border: none;
        margin: 0;
        max-width: 100%;
      }
    }
    @media (max-width: 600px) {
      .receipt-info {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="receipt-header">
      <div class="hotel-name">🏨 HOTEL EASE</div>
      <div>23, KAMPUNG BAHARU, 11400 AYER ITAM, PENANG</div>
      <div>Phone: +(60)12-345 6789 | Email: info@hotelease.com</div>
      <div class="receipt-title">BOOKING RECEIPT</div>
    </div>
    
    <div class="receipt-info">
      <div class="info-section">
        <h4>BOOKING DETAILS</h4>
        <div class="info-row">
          <span>Booking ID:</span>
          <span>#<?= htmlspecialchars($booking['id']) ?></span>
        </div>
        <div class="info-row">
          <span>Guest Name:</span>
          <span><?= htmlspecialchars($booking['guest_name']) ?></span>
        </div>
        <div class="info-row">
          <span>Room Type:</span>
          <span><?= htmlspecialchars($booking['room_type']) ?></span>
        </div>
        <div class="info-row">
          <span>Check-In:</span>
          <span><?= date('M d, Y', strtotime($booking['check_in'])) ?></span>
        </div>
        <div class="info-row">
          <span>Check-Out:</span>
          <span><?= date('M d, Y', strtotime($booking['check_out'])) ?></span>
        </div>
        <div class="info-row">
          <span>Nights:</span>
          <span><?= $nights ?></span>
        </div>
      </div>
      
      <div class="info-section">
        <h4>BOOKING INFO</h4>
        <div class="info-row">
          <span>Booking Date:</span>
          <span><?= date('M d, Y g:i A', strtotime($booking['created_at'])) ?></span>
        </div>
        <div class="info-row">
          <span>Payment Status:</span>
          <span style="color: <?= $booking['payment_status'] == 'paid' ? 'green' : 'red' ?>">
            <?= strtoupper($booking['payment_status'] ?? 'PENDING') ?>
          </span>
        </div>
        <?php if (isset($booking['transaction_id']) && $booking['transaction_id']): ?>
        <div class="info-row">
          <span>Transaction ID:</span>
          <span><?= htmlspecialchars($booking['transaction_id']) ?></span>
        </div>
        <?php endif; ?>
        <div class="info-row">
          <span>Receipt Date:</span>
          <span><?= date('M d, Y g:i A') ?></span>
        </div>
      </div>
    </div>
    
    <div class="charges-section">
      <h4>CHARGES</h4>
      <div class="info-row">
        <span><?= htmlspecialchars($booking['room_type']) ?> Room (<?= $nights ?> nights @ RM<?= number_format($room_price, 2) ?>)</span>
        <span>RM<?= number_format($subtotal, 2) ?></span>
      </div>
      <div class="info-row">
        <span>Tax (10%)</span>
        <span>RM<?= number_format($tax, 2) ?></span>
      </div>
      <div class="info-row total-row">
        <span>TOTAL AMOUNT</span>
        <span>RM<?= number_format($total, 2) ?></span>
      </div>
    </div>
    
    <?php if ($booking['payment_status'] == 'paid'): ?>
    <div class="payment-info">
      <h4 style="margin-top: 0;">PAYMENT CONFIRMED</h4>
      <p>✓ Payment has been successfully processed and confirmed.</p>
      <p>Your reservation is guaranteed and confirmed.</p>
    </div>
    <?php else: ?>
    <div class="payment-info" style="background: #fff3cd; border-color: #ffeaa7;">
      <h4 style="margin-top: 0;">PAYMENT PENDING</h4>
      <p>⚠️ Payment is still pending for this booking.</p>
      <p>Please complete payment to confirm your reservation.</p>
    </div>
    <?php endif; ?>
    
    <div class="footer-info">
      <p><strong>HOTEL POLICIES</strong></p>
      <p>Check-in: 3:00 PM | Check-out: 12:00 AM</p>
      <p>Cancellation: 24 hours before check-in</p>
      <p>For inquiries, contact us at (555) 123-4567</p>
      <p style="margin-top: 15px;">Thank you for choosing Hotel Ease!</p>
    </div>
    
    <div class="no-print" style="text-align: center; margin-top: 30px;">
      <button onclick="window.print()" class="print-btn">🖨️ Print Receipt</button>
      <button onclick="window.history.back()" class="print-btn" style="background: #6c757d;">← Go Back</button>
      <a href="user_bookings.php" class="print-btn" style="background: #28a745; text-decoration: none;">View All Bookings</a>
    </div>
  </div>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
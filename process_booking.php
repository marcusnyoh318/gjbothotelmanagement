<?php
session_start();

// Check if we have both booking and payment data
if (!isset($_SESSION['pending_booking']) || !isset($_SESSION['payment_data'])) {
    header('Location: index.html');
    exit;
}

$booking_data = $_SESSION['pending_booking'];
$payment_data = $_SESSION['payment_data'];

$host = 'localhost';
$db = 'hotel_db';
$user = 'root';
$pass = '';

// Connect directly to the database
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("<h2 style='color:red;'>Connection failed: " . htmlspecialchars($conn->connect_error) . "</h2>");
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
        die("<h2 style='color:red;'>Error creating bookings table: " . htmlspecialchars($conn->error) . "</h2>");
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

// Prepare booking insertion
$stmt = $conn->prepare("INSERT INTO bookings (guest_name, room_type, check_in, check_out, total_amount, payment_status, transaction_id) VALUES (?, ?, ?, ?, ?, ?, ?)");

if (!$stmt) {
    die("<h2 style='color:red;'>Error preparing statement: " . htmlspecialchars($conn->error) . "</h2>");
}

// Bind parameters
$payment_status = 'paid';
$stmt->bind_param("ssssdss", 
    $booking_data['guest_name'],
    $booking_data['room_type'],
    $booking_data['check_in'],
    $booking_data['check_out'],
    $payment_data['amount'],
    $payment_status,
    $payment_data['transaction_id']
);

// Execute the statement
if ($stmt->execute()) {
    $booking_id = $conn->insert_id;
    
    // Clear session data
    unset($_SESSION['pending_booking']);
    unset($_SESSION['payment_data']);
    
    // Show success page
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8">
      <title>Booking Confirmed - Hotel Ease</title>
      <link rel="stylesheet" href="style.css">
      <style>
        body {
          background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
          min-height: 100vh;
          margin: 0;
          padding: 20px;
        }
        
        .success-container {
          max-width: 800px;
          margin: 0 auto;
          background: rgba(255, 255, 255, 0.95);
          backdrop-filter: blur(10px);
          border-radius: 20px;
          padding: 2rem;
          box-shadow: 0 20px 40px rgba(0,0,0,0.1);
          color: #2c3e50;
        }
        
        .success-header {
          text-align: center;
          margin-bottom: 3rem;
          padding-bottom: 2rem;
          border-bottom: 2px solid #e1e8ed;
        }
        
        .success-icon {
          width: 100px;
          height: 100px;
          background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 3rem;
          color: white;
          margin: 0 auto 1.5rem auto;
          box-shadow: 0 10px 30px rgba(34, 197, 94, 0.3);
          animation: successPulse 2s ease-in-out infinite;
        }
        
        @keyframes successPulse {
          0%, 100% { transform: scale(1); }
          50% { transform: scale(1.05); }
        }
        
        .success-title {
          font-size: 2.5rem;
          font-weight: 700;
          color: #2c3e50;
          margin-bottom: 0.5rem;
        }
        
        .success-subtitle {
          color: #64748b;
          font-size: 1.1rem;
          line-height: 1.6;
        }
        
        .booking-summary {
          background: linear-gradient(135deg, #f8fafc 0%, #e1e8ed 100%);
          padding: 2rem;
          border-radius: 16px;
          margin: 2rem 0;
          border-left: 4px solid #22c55e;
          box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .summary-header {
          display: flex;
          align-items: center;
          gap: 1rem;
          margin-bottom: 1.5rem;
        }
        
        .summary-icon {
          width: 50px;
          height: 50px;
          background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 1.5rem;
          color: white;
        }
        
        .summary-title {
          color: #2c3e50;
          font-size: 1.5rem;
          font-weight: 600;
          margin: 0;
        }
        
        .booking-details {
          display: grid;
          grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
          gap: 1.5rem;
        }
        
        .detail-card {
          background: white;
          padding: 1.5rem;
          border-radius: 12px;
          border: 1px solid #e1e8ed;
          text-align: center;
          transition: transform 0.3s ease;
        }
        
        .detail-card:hover {
          transform: translateY(-2px);
          box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .detail-icon {
          font-size: 2rem;
          margin-bottom: 0.5rem;
          display: block;
        }
        
        .detail-label {
          font-size: 0.875rem;
          color: #64748b;
          font-weight: 600;
          text-transform: uppercase;
          letter-spacing: 0.5px;
          margin-bottom: 0.5rem;
        }
        
        .detail-value {
          font-size: 1.1rem;
          color: #2c3e50;
          font-weight: 700;
        }
        
        .detail-card.highlight {
          background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
          border-color: #22c55e;
        }
        
        .detail-card.highlight .detail-value {
          color: #166534;
        }
        
        .action-section {
          background: rgba(102, 126, 234, 0.1);
          border-radius: 16px;
          padding: 2rem;
          text-align: center;
          margin: 2rem 0;
        }
        
        .action-title {
          color: #2c3e50;
          font-size: 1.3rem;
          font-weight: 600;
          margin-bottom: 1.5rem;
        }
        
        .action-buttons {
          display: flex;
          gap: 1rem;
          justify-content: center;
          flex-wrap: wrap;
        }
        
        .action-btn {
          background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
          color: white;
          padding: 14px 28px;
          border: none;
          border-radius: 12px;
          font-size: 1rem;
          font-weight: 600;
          text-decoration: none;
          transition: all 0.3s ease;
          display: inline-block;
          box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
          min-width: 160px;
          text-align: center;
        }
        
        .action-btn:hover {
          transform: translateY(-2px);
          box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
          text-decoration: none;
          color: white;
        }
        
        .action-btn.primary {
          background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
          box-shadow: 0 4px 15px rgba(34, 197, 94, 0.3);
        }
        
        .action-btn.primary:hover {
          box-shadow: 0 8px 25px rgba(34, 197, 94, 0.4);
        }
        
        .action-btn.secondary {
          background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
          box-shadow: 0 4px 15px rgba(6, 182, 212, 0.3);
        }
        
        .action-btn.secondary:hover {
          box-shadow: 0 8px 25px rgba(6, 182, 212, 0.4);
        }
        
        .important-info {
          background: linear-gradient(135deg, #fef3c7 0%, #fed7aa 100%);
          border-radius: 16px;
          padding: 2rem;
          margin: 2rem 0;
          border-left: 4px solid #f59e0b;
        }
        
        .info-header {
          display: flex;
          align-items: center;
          gap: 1rem;
          margin-bottom: 1rem;
        }
        
        .info-icon {
          width: 40px;
          height: 40px;
          background: #f59e0b;
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 1.2rem;
          color: white;
        }
        
        .info-title {
          color: #92400e;
          font-size: 1.2rem;
          font-weight: 600;
          margin: 0;
        }
        
        .info-list {
          list-style: none;
          padding: 0;
          margin: 0;
        }
        
        .info-list li {
          color: #92400e;
          margin-bottom: 0.75rem;
          padding-left: 1.5rem;
          position: relative;
          line-height: 1.5;
        }
        
        .info-list li:before {
          content: "💡";
          position: absolute;
          left: 0;
        }
        
        .booking-id-highlight {
          background: rgba(102, 126, 234, 0.1);
          padding: 0.5rem 1rem;
          border-radius: 8px;
          display: inline-block;
          font-family: monospace;
          font-weight: 700;
          color: #667eea;
          margin: 0 0.25rem;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
          .success-container {
            padding: 1rem;
            margin: 10px;
          }
          
          .success-title {
            font-size: 2rem;
          }
          
          .booking-details {
            grid-template-columns: 1fr;
          }
          
          .action-buttons {
            flex-direction: column;
            align-items: center;
          }
          
          .action-btn {
            width: 100%;
            max-width: 280px;
          }
          
          .success-icon {
            width: 80px;
            height: 80px;
            font-size: 2.5rem;
          }
        }
      </style>
    </head>
    <body>
      <div class="success-container">
        <div class="success-header">
          <div class="success-icon">✅</div>
          <h1 class="success-title">Booking Confirmed!</h1>
          <p class="success-subtitle">
            Thank you for choosing Hotel Ease! Your reservation has been successfully confirmed 
            and payment has been processed. We look forward to welcoming you.
          </p>
        </div>
        
        <div class="booking-summary">
          <div class="summary-header">
            <div class="summary-icon">📋</div>
            <h3 class="summary-title">Booking Summary</h3>
          </div>
          
          <div class="booking-details">
            <div class="detail-card highlight">
              <span class="detail-icon">🆔</span>
              <div class="detail-label">Booking ID</div>
              <div class="detail-value">#<?= htmlspecialchars($booking_id) ?></div>
            </div>
            
            <div class="detail-card">
              <span class="detail-icon">👤</span>
              <div class="detail-label">Guest Name</div>
              <div class="detail-value"><?= htmlspecialchars($booking_data['guest_name']) ?></div>
            </div>
            
            <div class="detail-card">
              <span class="detail-icon">🏨</span>
              <div class="detail-label">Room Type</div>
              <div class="detail-value"><?= htmlspecialchars($booking_data['room_type']) ?></div>
            </div>
            
            <div class="detail-card">
              <span class="detail-icon">📅</span>
              <div class="detail-label">Check-in Date</div>
              <div class="detail-value"><?= date('M d, Y', strtotime($booking_data['check_in'])) ?></div>
            </div>
            
            <div class="detail-card">
              <span class="detail-icon">📅</span>
              <div class="detail-label">Check-out Date</div>
              <div class="detail-value"><?= date('M d, Y', strtotime($booking_data['check_out'])) ?></div>
            </div>
            
            <div class="detail-card">
              <span class="detail-icon">🔒</span>
              <div class="detail-label">Transaction ID</div>
              <div class="detail-value"><?= htmlspecialchars($payment_data['transaction_id']) ?></div>
            </div>
            
            <div class="detail-card highlight">
              <span class="detail-icon">💰</span>
              <div class="detail-label">Total Paid</div>
              <div class="detail-value">RM<?= number_format($payment_data['amount'], 2) ?></div>
            </div>
            
            <div class="detail-card">
              <span class="detail-icon">✅</span>
              <div class="detail-label">Payment Status</div>
              <div class="detail-value">Confirmed</div>
            </div>
          </div>
        </div>
        
        <div class="action-section">
          <h3 class="action-title">What would you like to do next?</h3>
          <div class="action-buttons">
            <a href="receipt.php?id=<?= $booking_id ?>" class="action-btn primary">
              📄 View Receipt
            </a>
            <a href="user_bookings.php" class="action-btn secondary">
              📋 My Bookings
            </a>
            <a href="index.html" class="action-btn">
              🏨 Book Another Room
            </a>
          </div>
        </div>
        
        <div class="important-info">
          <div class="info-header">
            <div class="info-icon">📌</div>
            <h4 class="info-title">Important Information</h4>
          </div>
          <ul class="info-list">
            <li>
              Please save your booking confirmation number: 
              <span class="booking-id-highlight">#<?= htmlspecialchars($booking_id) ?></span>
            </li>
            <li>Check-in time: 3:00 PM | Check-out time: 11:00 AM</li>
            <li>Please bring a valid ID and this confirmation for check-in</li>
            <li>For any changes or cancellations, contact us at +(60) 12-345 6789</li>
            <li>Free WiFi and complimentary breakfast included with your stay</li>
          </ul>
        </div>
      </div>
      
      <script>
        // Add some interactive elements
        document.addEventListener('DOMContentLoaded', function() {
          // Animate detail cards
          const detailCards = document.querySelectorAll('.detail-card');
          detailCards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
              card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
              card.style.opacity = '1';
              card.style.transform = 'translateY(0)';
            }, index * 100);
          });
          
          // Add click to copy booking ID
          const bookingIds = document.querySelectorAll('.booking-id-highlight');
          bookingIds.forEach(id => {
            id.style.cursor = 'pointer';
            id.title = 'Click to copy booking ID';
            
            id.addEventListener('click', function() {
              const bookingId = this.textContent.trim();
              navigator.clipboard.writeText(bookingId).then(() => {
                // Show temporary feedback
                const originalText = this.textContent;
                this.textContent = '✓ Copied!';
                this.style.background = 'rgba(34, 197, 94, 0.2)';
                this.style.color = '#166534';
                
                setTimeout(() => {
                  this.textContent = originalText;
                  this.style.background = '';
                  this.style.color = '';
                }, 2000);
              });
            });
          });
        });
      </script>
    </body>
    </html>
    <?php
} else {
    // Error occurred
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8">
      <title>Booking Error - Hotel Ease</title>
      <link rel="stylesheet" href="style.css">
      <style>
        body {
          background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
          min-height: 100vh;
          margin: 0;
          padding: 20px;
        }
        
        .error-container {
          max-width: 600px;
          margin: 0 auto;
          background: rgba(255, 255, 255, 0.95);
          backdrop-filter: blur(10px);
          border-radius: 20px;
          padding: 2rem;
          box-shadow: 0 20px 40px rgba(0,0,0,0.1);
          color: #2c3e50;
          text-align: center;
        }
        
        .error-icon {
          width: 100px;
          height: 100px;
          background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 3rem;
          color: white;
          margin: 0 auto 1.5rem auto;
          box-shadow: 0 10px 30px rgba(239, 68, 68, 0.3);
        }
        
        .error-title {
          font-size: 2.5rem;
          font-weight: 700;
          color: #dc2626;
          margin-bottom: 1rem;
        }
        
        .error-message {
          background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
          color: #991b1b;
          padding: 1.5rem;
          border-radius: 12px;
          margin: 2rem 0;
          border-left: 4px solid #ef4444;
          text-align: left;
        }
        
        .error-details {
          font-weight: 600;
          margin-bottom: 0.5rem;
        }
        
        .action-buttons {
          display: flex;
          gap: 1rem;
          justify-content: center;
          margin-top: 2rem;
          flex-wrap: wrap;
        }
        
        .action-btn {
          background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
          color: white;
          padding: 14px 28px;
          border: none;
          border-radius: 12px;
          font-size: 1rem;
          font-weight: 600;
          text-decoration: none;
          transition: all 0.3s ease;
          display: inline-block;
          box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
          min-width: 160px;
          text-align: center;
        }
        
        .action-btn:hover {
          transform: translateY(-2px);
          box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
          text-decoration: none;
          color: white;
        }
        
        .action-btn.retry {
          background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
          box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
        }
        
        .action-btn.retry:hover {
          box-shadow: 0 8px 25px rgba(245, 158, 11, 0.4);
        }
        
        @media (max-width: 768px) {
          .error-container {
            padding: 1rem;
            margin: 10px;
          }
          
          .action-buttons {
            flex-direction: column;
            align-items: center;
          }
          
          .action-btn {
            width: 100%;
            max-width: 280px;
          }
        }
      </style>
    </head>
    <body>
      <div class="error-container">
        <div class="error-icon">❌</div>
        <h1 class="error-title">Booking Error</h1>
        
        <div class="error-message">
          <div class="error-details">Something went wrong while processing your booking:</div>
          <p><?= htmlspecialchars($stmt->error) ?></p>
          <p><strong>Don't worry!</strong> Your payment was not processed and no charges were made to your account.</p>
        </div>
        
        <div class="action-buttons">
          <a href="payment.php" class="action-btn retry">🔄 Try Payment Again</a>
          <a href="index.html" class="action-btn">🏨 Back to Booking Form</a>
        </div>
      </div>
    </body>
    </html>
    <?php
}

$stmt->close();
$conn->close();
?>
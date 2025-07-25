<?php
session_start();

// Check if we have pending booking data
if (!isset($_SESSION['pending_booking'])) {
    header('Location: index.html');
    exit;
}

$booking_data = $_SESSION['pending_booking'];

// Calculate pricing and total
$room_rates = [
    'Single' => 100,
    'Double' => 150,
    'Suite' => 250
];

$room_type = $booking_data['room_type'];
$check_in = new DateTime($booking_data['check_in']);
$check_out = new DateTime($booking_data['check_out']);
$nights = $check_in->diff($check_out)->days;
$subtotal = $room_rates[$room_type] * $nights;
$tax = $subtotal * 0.10; // 10% tax
$total = $subtotal + $tax;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate payment data
    $card_number = preg_replace('/\s+/', '', $_POST['card_number']);
    $expiry_date = $_POST['expiry_date'];
    $cvv = $_POST['cvv'];
    $card_name = strtoupper(trim($_POST['card_name']));
    
    // Basic validation
    $errors = [];
    
    if (strlen($card_number) < 16 || !preg_match('/^\d+$/', $card_number)) {
        $errors[] = "Invalid card number";
    }
    
    if (!preg_match('/^\d{2}\/\d{2}$/', $expiry_date)) {
        $errors[] = "Invalid expiry date format";
    }
    
    if (strlen($cvv) < 3 || !preg_match('/^\d+$/', $cvv)) {
        $errors[] = "Invalid CVV";
    }
    
    if (empty($card_name) || strlen($card_name) < 2) {
        $errors[] = "Invalid cardholder name";
    }
    
    if (empty($errors)) {
        // Generate transaction ID
        $transaction_id = 'TXN' . date('YmdHis') . rand(1000, 9999);
        
        // Store payment data in session
        $_SESSION['payment_data'] = [
            'amount' => $total,
            'transaction_id' => $transaction_id,
            'card_last_four' => substr($card_number, -4),
            'payment_date' => date('Y-m-d H:i:s')
        ];
        
        // Redirect to booking processing
        header('Location: process_booking.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Payment - Hotel Ease</title>
  <link rel="stylesheet" href="style.css">
  <style>
    body {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      margin: 0;
      padding: 20px;
    }
    
    .payment-container {
      max-width: 1200px;
      margin: 0 auto;
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      border-radius: 20px;
      padding: 2rem;
      box-shadow: 0 20px 40px rgba(0,0,0,0.1);
      color: #2c3e50;
    }
    
    .payment-header {
      text-align: center;
      margin-bottom: 3rem;
      padding-bottom: 2rem;
      border-bottom: 2px solid #e1e8ed;
    }
    
    .payment-title {
      font-size: 2.5rem;
      font-weight: 700;
      color: #2c3e50;
      margin-bottom: 0.5rem;
    }
    
    .payment-subtitle {
      color: #64748b;
      font-size: 1.1rem;
    }
    
    .payment-content {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 3rem;
      margin-top: 2rem;
    }
    
    .booking-summary {
      background: linear-gradient(135deg, #f8fafc 0%, #e1e8ed 100%);
      padding: 2rem;
      border-radius: 16px;
      border-left: 4px solid #667eea;
      box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }
    
    .summary-header {
      display: flex;
      align-items: center;
      gap: 1rem;
      margin-bottom: 2rem;
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
    
    .summary-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin: 1rem 0;
      padding: 0.75rem 0;
      border-bottom: 1px solid #e1e8ed;
    }
    
    .summary-row:last-child {
      border-bottom: none;
      font-weight: 700;
      font-size: 1.2rem;
      color: #667eea;
      border-top: 2px solid #667eea;
      padding-top: 1rem;
      margin-top: 1.5rem;
      background: rgba(102, 126, 234, 0.1);
      padding: 1rem;
      border-radius: 8px;
    }
    
    .guest-info {
      background: rgba(102, 126, 234, 0.1);
      padding: 1rem;
      border-radius: 8px;
      margin: 1rem 0;
    }
    
    .guest-info h4 {
      margin: 0 0 0.5rem 0;
      color: #667eea;
      font-size: 1rem;
    }
    
    .payment-form {
      background: white;
      padding: 2rem;
      border-radius: 16px;
      box-shadow: 0 5px 20px rgba(0,0,0,0.1);
      border: 1px solid #e1e8ed;
    }
    
    .form-header {
      display: flex;
      align-items: center;
      gap: 1rem;
      margin-bottom: 2rem;
    }
    
    .form-icon {
      width: 50px;
      height: 50px;
      background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
      color: white;
    }
    
    .form-title {
      color: #2c3e50;
      font-size: 1.5rem;
      font-weight: 600;
      margin: 0;
    }
    
    .security-badges {
      display: flex;
      justify-content: center;
      gap: 1rem;
      margin-bottom: 2rem;
      flex-wrap: wrap;
    }
    
    .security-badge {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      background: rgba(34, 197, 94, 0.1);
      color: #166534;
      padding: 0.5rem 1rem;
      border-radius: 20px;
      font-size: 0.875rem;
      font-weight: 600;
      border: 1px solid rgba(34, 197, 94, 0.2);
    }
    
    .form-group {
      margin-bottom: 1.5rem;
    }
    
    .form-group label {
      display: block;
      color: #2c3e50;
      margin-bottom: 0.5rem;
      font-weight: 600;
      font-size: 0.95rem;
    }
    
    .form-group input {
      width: 100%;
      padding: 14px 16px;
      border: 2px solid #e1e8ed;
      border-radius: 12px;
      font-size: 16px;
      background: #f8fafc;
      transition: all 0.3s ease;
      font-family: inherit;
      box-sizing: border-box;
    }
    
    .form-group input:focus {
      border-color: #667eea;
      background: #fff;
      outline: none;
      box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
      transform: translateY(-1px);
    }
    
    .form-row {
      display: grid;
      grid-template-columns: 2fr 1fr;
      gap: 1rem;
    }
    
    .submit-btn {
      background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
      color: white;
      padding: 16px 32px;
      font-size: 1.1rem;
      font-weight: 700;
      border: none;
      border-radius: 12px;
      cursor: pointer;
      width: 100%;
      margin-top: 1rem;
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(34, 197, 94, 0.3);
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    .submit-btn:hover {
      background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(34, 197, 94, 0.4);
    }
    
    .submit-btn:disabled {
      background: #9ca3af;
      cursor: not-allowed;
      transform: none;
      box-shadow: none;
    }
    
    .error-message {
      background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
      color: #991b1b;
      padding: 1rem 1.5rem;
      border-radius: 12px;
      margin: 1rem 0;
      border-left: 4px solid #ef4444;
      font-weight: 500;
    }
    
    .back-link {
      text-align: center;
      margin-top: 2rem;
      padding-top: 2rem;
      border-top: 1px solid #e1e8ed;
    }
    
    .back-link a {
      color: #667eea;
      text-decoration: none;
      padding: 12px 24px;
      border: 2px solid #667eea;
      border-radius: 12px;
      font-weight: 600;
      transition: all 0.3s ease;
      display: inline-block;
    }
    
    .back-link a:hover {
      background: #667eea;
      color: white;
      transform: translateY(-2px);
      text-decoration: none;
    }
    
    @media (max-width: 768px) {
      .payment-container {
        padding: 1rem;
        margin: 10px;
      }
      
      .payment-content {
        grid-template-columns: 1fr;
        gap: 2rem;
      }
      
      .form-row {
        grid-template-columns: 1fr;
      }
      
      .payment-title {
        font-size: 2rem;
      }
    }
  </style>
</head>
<body>
  <div class="payment-container">
    <div class="payment-header">
      <h1 class="payment-title">💳 Secure Payment</h1>
      <p class="payment-subtitle">Complete your booking with our secure payment system</p>
    </div>
    
    <?php if (!empty($errors)): ?>
      <div class="error-message">
        <strong>⚠️ Please correct the following errors:</strong>
        <ul style="margin: 0.5rem 0 0 1.5rem;">
          <?php foreach ($errors as $error): ?>
            <li><?= htmlspecialchars($error) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>
    
    <div class="payment-content">
      <!-- Booking Summary -->
      <div class="booking-summary">
        <div class="summary-header">
          <div class="summary-icon">📋</div>
          <h3 class="summary-title">Booking Summary</h3>
        </div>
        
        <div class="guest-info">
          <h4>Guest Information</h4>
          <p><strong>Name:</strong> <?= htmlspecialchars($booking_data['guest_name']) ?></p>
        </div>
        
        <div class="summary-row">
          <span>📍 Hotel:</span>
          <span>Hotel Ease Premium</span>
        </div>
        <div class="summary-row">
          <span>🏨 Room Type:</span>
          <span><?= htmlspecialchars($booking_data['room_type']) ?></span>
        </div>
        <div class="summary-row">
          <span>📅 Check-in:</span>
          <span><?= date('M d, Y', strtotime($booking_data['check_in'])) ?></span>
        </div>
        <div class="summary-row">
          <span>📅 Check-out:</span>
          <span><?= date('M d, Y', strtotime($booking_data['check_out'])) ?></span>
        </div>
        <div class="summary-row">
          <span>🌙 Number of Nights:</span>
          <span><?= $nights ?></span>
        </div>
        <div class="summary-row">
          <span>💰 Room Rate (per night):</span>
          <span>RM<?= number_format($room_rates[$room_type], 2) ?></span>
        </div>
        <div class="summary-row">
          <span>📊 Subtotal:</span>
          <span>RM<?= number_format($subtotal, 2) ?></span>
        </div>
        <div class="summary-row">
          <span>🏛️ Tax (10%):</span>
          <span>RM<?= number_format($tax, 2) ?></span>
        </div>
        <div class="summary-row">
          <span>💳 Total Amount:</span>
          <span>RM<?= number_format($total, 2) ?></span>
        </div>
      </div>
      
      <!-- Payment Form -->
      <div class="payment-form">
        <div class="form-header">
          <div class="form-icon">💳</div>
          <h3 class="form-title">Payment Details</h3>
        </div>
        
        <div class="security-badges">
          <div class="security-badge">
            <span>🔒</span>
            <span>SSL Encrypted</span>
          </div>
          <div class="security-badge">
            <span>✅</span>
            <span>Secure Payment</span>
          </div>
          <div class="security-badge">
            <span>🛡️</span>
            <span>PCI Compliant</span>
          </div>
        </div>
        
        <form id="paymentForm" method="POST">
          <div class="form-group">
            <label for="card_number">Card Number</label>
            <input type="text" 
                   id="card_number" 
                   name="card_number" 
                   placeholder="1234 5678 9012 3456"
                   maxlength="19"
                   oninput="formatCardNumber(this)"
                   required>
          </div>
          
          <div class="form-row">
            <div class="form-group">
              <label for="expiry_date">Expiry Date</label>
              <input type="text" 
                     id="expiry_date" 
                     name="expiry_date" 
                     placeholder="MM/YY"
                     maxlength="5"
                     oninput="formatExpiry(this)"
                     required>
            </div>
            <div class="form-group">
              <label for="cvv">CVV</label>
              <input type="text" 
                     id="cvv" 
                     name="cvv" 
                     placeholder="123"
                     maxlength="4"
                     oninput="formatCVV(this)"
                     required>
            </div>
          </div>
          
          <div class="form-group">
            <label for="card_name">Name on Card</label>
            <input type="text" 
                   id="card_name" 
                   name="card_name" 
                   placeholder="John Doe"
                   style="text-transform: uppercase;"
                   required>
          </div>
          
          <button type="submit" id="submitBtn" class="submit-btn">
            🔒 Pay RM<?= number_format($total, 2) ?> Securely
          </button>
        </form>
      </div>
    </div>
    
    <div class="back-link">
      <a href="index.html">← Back to Booking Form</a>
    </div>
  </div>
  
  <script>
    function formatCardNumber(input) {
      let value = input.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
      let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
      if (formattedValue.length > 19) formattedValue = formattedValue.substring(0, 19);
      input.value = formattedValue;
      
      if (value.length >= 16) {
        input.style.borderColor = '#22c55e';
      } else {
        input.style.borderColor = '#e1e8ed';
      }
    }
    
    function formatExpiry(input) {
      let value = input.value.replace(/\D/g, '');
      if (value.length >= 2) {
        value = value.substring(0, 2) + '/' + value.substring(2, 4);
      }
      input.value = value;
      
      if (value.length === 5) {
        input.style.borderColor = '#22c55e';
      } else {
        input.style.borderColor = '#e1e8ed';
      }
    }
    
    function formatCVV(input) {
      input.value = input.value.replace(/[^0-9]/g, '').substring(0, 4);
      
      if (input.value.length >= 3) {
        input.style.borderColor = '#22c55e';
      } else {
        input.style.borderColor = '#e1e8ed';
      }
    }
    
    document.getElementById('paymentForm').addEventListener('submit', function(e) {
      const submitBtn = document.getElementById('submitBtn');
      submitBtn.disabled = true;
      submitBtn.innerHTML = '🔄 Processing Payment...';
      submitBtn.style.background = '#9ca3af';
    });
  </script>
</body>
</html>
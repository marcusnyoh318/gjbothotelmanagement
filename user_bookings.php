<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: user_login.php?message=Please log in to view your bookings');
    exit;
}

$username = $_SESSION['username'] ?? 'Guest';

// Database connection
$host = 'localhost';
$db = 'hotel_db';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("<h2 style='color:red;'>Connection failed: " . htmlspecialchars($conn->connect_error) . "</h2>");
}

// Fetch user's bookings from database
$bookings_query = "SELECT * FROM bookings WHERE guest_name = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($bookings_query);
$stmt->bind_param("s", $username);
$stmt->execute();
$bookings_result = $stmt->get_result();
$bookings = [];
if ($bookings_result) {
    while ($row = $bookings_result->fetch_assoc()) {
        $bookings[] = $row;
    }
}

// Calculate statistics
$total_bookings = count($bookings);
$upcoming_bookings = 0;
$ongoing_bookings = 0;
$past_bookings = 0;
$today = date('Y-m-d');

foreach ($bookings as $booking) {
    $check_in = $booking['check_in'];
    $check_out = $booking['check_out'];
    
    if ($check_in > $today) {
        $upcoming_bookings++;
    } elseif ($check_in <= $today && $check_out > $today) {
        $ongoing_bookings++;
    } else {
        $past_bookings++;
    }
}

$conn->close();

function getBookingStatus($check_in, $check_out) {
    $today = date('Y-m-d');
    if ($check_in > $today) {
        return 'upcoming';
    } elseif ($check_in <= $today && $check_out > $today) {
        return 'ongoing';
    } else {
        return 'past';
    }
}

function getRoomIcon($room_type) {
    switch($room_type) {
        case 'Single': return '🛏️';
        case 'Double': return '🏠';
        case 'Suite': return '👑';
        default: return '🏨';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Bookings - Hotel Ease</title>
  <link rel="stylesheet" href="style.css">
  <style>
    body {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      margin: 0;
      padding: 20px;
    }
    
    .user-container {
      max-width: 1200px;
      margin: 0 auto;
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      border-radius: 20px;
      padding: 2rem;
      box-shadow: 0 20px 40px rgba(0,0,0,0.1);
      position: relative;
    }
    
    .user-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 2rem;
      padding-bottom: 1rem;
      border-bottom: 2px solid #e1e8ed;
    }
    
    .user-profile {
      display: flex;
      align-items: center;
      gap: 1rem;
    }
    
    .profile-avatar {
      width: 60px;
      height: 60px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2rem;
      color: white;
    }
    
    .profile-info h1 {
      margin: 0;
      color: #2c3e50;
      font-size: 2rem;
      font-weight: 700;
    }
    
    .profile-subtitle {
      color: #64748b;
      margin: 0;
      font-size: 1rem;
    }
    
    .logout-btn {
      background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
      color: white;
      padding: 12px 24px;
      border: none;
      border-radius: 12px;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
    }
    
    .logout-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(239, 68, 68, 0.4);
      text-decoration: none;
      color: white;
    }
    
    /* Welcome Card */
    .welcome-card {
      background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
      border-radius: 16px;
      padding: 2rem;
      margin-bottom: 2rem;
      text-align: center;
      border: 1px solid rgba(102, 126, 234, 0.2);
    }
    
    .welcome-card h2 {
      color: #2c3e50;
      margin-bottom: 0.5rem;
      font-size: 1.5rem;
    }
    
    .welcome-message {
      color: #64748b;
      font-size: 1.1rem;
      line-height: 1.6;
    }
    
    /* Enhanced Statistics */
    .stats-section {
      margin-bottom: 3rem;
    }
    
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1.5rem;
    }
    
    .stat-card-user {
      background: linear-gradient(135deg, #f8fafc 0%, #e1e8ed 100%);
      border-radius: 16px;
      padding: 1.5rem;
      text-align: center;
      box-shadow: 0 5px 20px rgba(0,0,0,0.1);
      border: 1px solid #e1e8ed;
      transition: transform 0.3s ease;
      position: relative;
      overflow: hidden;
    }
    
    .stat-card-user:hover {
      transform: translateY(-5px);
    }
    
    .stat-card-user::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
    }
    
    .stat-card-user.total::before {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    
    .stat-card-user.upcoming::before {
      background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    }
    
    .stat-card-user.ongoing::before {
      background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    }
    
    .stat-card-user.past::before {
      background: linear-gradient(135deg, #64748b 0%, #475569 100%);
    }
    
    .stat-icon-user {
      font-size: 2.5rem;
      margin-bottom: 1rem;
      display: block;
    }
    
    .stat-number-user {
      font-size: 2.5rem;
      font-weight: 700;
      margin-bottom: 0.5rem;
      display: block;
    }
    
    .stat-card-user.total .stat-number-user {
      color: #667eea;
    }
    
    .stat-card-user.upcoming .stat-number-user {
      color: #22c55e;
    }
    
    .stat-card-user.ongoing .stat-number-user {
      color: #f59e0b;
    }
    
    .stat-card-user.past .stat-number-user {
      color: #64748b;
    }
    
    .stat-label-user {
      color: #64748b;
      font-size: 1rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    /* Bookings Section */
    .bookings-section {
      background: white;
      border-radius: 16px;
      padding: 2rem;
      box-shadow: 0 5px 20px rgba(0,0,0,0.1);
      margin-bottom: 2rem;
    }
    
    .section-title {
      display: flex;
      align-items: center;
      gap: 1rem;
      margin-bottom: 1.5rem;
      color: #2c3e50;
      font-size: 1.5rem;
      font-weight: 600;
    }
    
    .section-icon {
      font-size: 1.8rem;
    }
    
    /* Booking Cards */
    .bookings-grid {
      display: grid;
      gap: 1.5rem;
    }
    
    .booking-card-item {
      background: #f8fafc;
      border-radius: 12px;
      padding: 1.5rem;
      border-left: 4px solid #667eea;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }
    
    .booking-card-item:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }
    
    .booking-card-item.upcoming {
      border-left-color: #22c55e;
      background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
    }
    
    .booking-card-item.ongoing {
      border-left-color: #f59e0b;
      background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
    }
    
    .booking-card-item.past {
      border-left-color: #64748b;
      background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    }
    
    .booking-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1rem;
    }
    
    .booking-id {
      font-size: 1.2rem;
      font-weight: 700;
      color: #2c3e50;
    }
    
    .booking-status {
      padding: 0.25rem 0.75rem;
      border-radius: 20px;
      font-size: 0.875rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    .status-upcoming {
      background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
      color: #166534;
    }
    
    .status-ongoing {
      background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
      color: #92400e;
    }
    
    .status-past {
      background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
      color: #475569;
    }
    
    .booking-details {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1rem;
    }
    
    .detail-item {
      display: flex;
      flex-direction: column;
    }
    
    .detail-label {
      font-size: 0.875rem;
      color: #64748b;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 0.25rem;
    }
    
    .detail-value {
      font-size: 1rem;
      color: #2c3e50;
      font-weight: 600;
    }
    
    .room-type-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.5rem 1rem;
      background: rgba(102, 126, 234, 0.1);
      border-radius: 20px;
      color: #667eea;
      font-weight: 600;
      width: fit-content;
    }
    
    /* Empty State */
    .empty-state {
      text-align: center;
      padding: 4rem 2rem;
      color: #64748b;
    }
    
    .empty-state-icon {
      font-size: 5rem;
      margin-bottom: 1.5rem;
      opacity: 0.5;
    }
    
    .empty-state h3 {
      color: #2c3e50;
      margin-bottom: 1rem;
      font-size: 1.5rem;
    }
    
    .empty-state p {
      font-size: 1.1rem;
      line-height: 1.6;
      margin-bottom: 2rem;
    }
    
    .cta-btn {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 14px 28px;
      border: none;
      border-radius: 12px;
      font-size: 1.1rem;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.3s ease;
      display: inline-block;
      box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    }
    
    .cta-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
      text-decoration: none;
      color: white;
    }
    
    /* Action Links */
    .action-links {
      background: rgba(102, 126, 234, 0.1);
      border-radius: 16px;
      padding: 1.5rem;
      text-align: center;
      margin-top: 2rem;
    }
    
    .action-links h3 {
      color: #2c3e50;
      margin-bottom: 1rem;
    }
    
    .action-buttons {
      display: flex;
      gap: 1rem;
      justify-content: center;
      flex-wrap: wrap;
    }
    
    .action-link {
      background: rgba(255, 255, 255, 0.8);
      backdrop-filter: blur(10px);
      color: #2c3e50;
      border: 2px solid rgba(102, 126, 234, 0.2);
      padding: 12px 24px;
      border-radius: 12px;
      text-decoration: none;
      font-weight: 600;
      transition: all 0.3s ease;
    }
    
    .action-link:hover {
      background: rgba(102, 126, 234, 0.1);
      border-color: #667eea;
      transform: translateY(-2px);
      text-decoration: none;
      color: #2c3e50;
    }
    
    /* Responsive Design */
    @media (max-width: 768px) {
      .user-container {
        padding: 1rem;
        margin: 10px;
      }
      
      .user-header {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
      }
      
      .profile-info h1 {
        font-size: 1.5rem;
      }
      
      .stats-grid {
        grid-template-columns: repeat(2, 1fr);
      }
      
      .booking-header {
        flex-direction: column;
        gap: 0.5rem;
        align-items: flex-start;
      }
      
      .booking-details {
        grid-template-columns: 1fr;
      }
      
      .action-buttons {
        flex-direction: column;
        align-items: center;
      }
      
      .action-link {
        width: 100%;
        max-width: 280px;
        text-align: center;
      }
    }
    
    @media (max-width: 480px) {
      .stats-grid {
        grid-template-columns: 1fr;
      }
      
      .welcome-card {
        padding: 1.5rem;
      }
      
      .bookings-section {
        padding: 1.5rem;
      }
    }
  </style>
</head>
<body>
  <div class="user-container">
    <!-- User Header -->
    <div class="user-header">
      <div class="user-profile">
        <div class="profile-avatar">👤</div>
        <div class="profile-info">
          <h1>Welcome, <?= htmlspecialchars($username) ?></h1>
          <p class="profile-subtitle">Manage your hotel bookings</p>
        </div>
      </div>
      <a href="user_logout.php" class="logout-btn">
        🚪 Logout
      </a>
    </div>
    
    <!-- Welcome Card -->
    <div class="welcome-card">
      <h2>Your Booking Dashboard</h2>
      <p class="welcome-message">
        Keep track of all your hotel reservations in one place. View upcoming stays, 
        check booking details, and manage your travel plans effortlessly.
      </p>
    </div>
    
    <!-- Statistics Section -->
    <div class="stats-section">
      <div class="stats-grid">
        <div class="stat-card-user total">
          <span class="stat-icon-user">📊</span>
          <span class="stat-number-user"><?= $total_bookings ?></span>
          <span class="stat-label-user">Total Bookings</span>
        </div>
        <div class="stat-card-user upcoming">
          <span class="stat-icon-user">📅</span>
          <span class="stat-number-user"><?= $upcoming_bookings ?></span>
          <span class="stat-label-user">Upcoming</span>
        </div>
        <div class="stat-card-user ongoing">
          <span class="stat-icon-user">🏨</span>
          <span class="stat-number-user"><?= $ongoing_bookings ?></span>
          <span class="stat-label-user">Current Stay</span>
        </div>
        <div class="stat-card-user past">
          <span class="stat-icon-user">✅</span>
          <span class="stat-number-user"><?= $past_bookings ?></span>
          <span class="stat-label-user">Completed</span>
        </div>
      </div>
    </div>
    
    <!-- Bookings Section -->
    <div class="bookings-section">
      <h2 class="section-title">
        <span class="section-icon">📋</span>
        Your Bookings
      </h2>
      
      <?php if (empty($bookings)): ?>
        <!-- Empty State -->
        <div class="empty-state">
          <div class="empty-state-icon">📝</div>
          <h3>No Bookings Yet</h3>
          <p>
            You haven't made any hotel reservations yet. 
            Start planning your perfect getaway today!
          </p>
          <a href="index.html" class="cta-btn">🏨 Book Your First Room</a>
        </div>
      <?php else: ?>
        <div class="bookings-grid">
          <?php foreach ($bookings as $booking): ?>
            <?php $status = getBookingStatus($booking['check_in'], $booking['check_out']); ?>
            <div class="booking-card-item <?= $status ?>">
              <div class="booking-header">
                <div class="booking-id">#<?= htmlspecialchars($booking['id']) ?></div>
                <div class="booking-status status-<?= $status ?>">
                  <?php
                  switch($status) {
                    case 'upcoming': echo '📅 Upcoming'; break;
                    case 'ongoing': echo '🏨 Current Stay'; break;
                    case 'past': echo '✅ Completed'; break;
                  }
                  ?>
                </div>
              </div>
              <div class="booking-details">
                <div class="detail-item">
                  <span class="detail-label">Room Type</span>
                  <div class="room-type-badge">
                    <?= getRoomIcon($booking['room_type']) ?> <?= htmlspecialchars($booking['room_type']) ?>
                  </div>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Check-in</span>
                  <span class="detail-value"><?= date('M d, Y', strtotime($booking['check_in'])) ?></span>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Check-out</span>
                  <span class="detail-value"><?= date('M d, Y', strtotime($booking['check_out'])) ?></span>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Total Amount</span>
                  <span class="detail-value">RM<?= number_format($booking['total_amount'], 2) ?></span>
                </div>
                <?php if (isset($booking['transaction_id'])): ?>
                <div class="detail-item">
                  <span class="detail-label">Transaction ID</span>
                  <span class="detail-value" style="font-family: monospace; font-size: 0.9rem;"><?= htmlspecialchars($booking['transaction_id']) ?></span>
                </div>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
    
    <!-- Action Links -->
    <div class="action-links">
      <h3>Quick Actions</h3>
      <div class="action-buttons">
        <a href="index.html" class="action-link">🏨 New Booking</a>
        <a href="user_login.php" class="action-link">🔄 Switch Account</a>
        <?php if (!empty($bookings)): ?>
        <a href="receipt.php?id=<?= $bookings[0]['id'] ?>" class="action-link">📄 View Receipt</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
  
  <script>
    // Add some interactivity
    document.addEventListener('DOMContentLoaded', function() {
      // Add fade-in animation to booking cards
      const bookingCards = document.querySelectorAll('.booking-card-item');
      bookingCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
          card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
          card.style.opacity = '1';
          card.style.transform = 'translateY(0)';
        }, index * 150);
      });
      
      // Add click to copy booking ID
      document.querySelectorAll('.booking-id').forEach(id => {
        id.style.cursor = 'pointer';
        id.title = 'Click to copy booking ID';
        
        id.addEventListener('click', function() {
          const bookingId = this.textContent;
          navigator.clipboard.writeText(bookingId).then(() => {
            // Show temporary feedback
            const originalText = this.textContent;
            this.textContent = '✓ Copied!';
            this.style.color = '#22c55e';
            
            setTimeout(() => {
              this.textContent = originalText;
              this.style.color = '';
            }, 1500);
          });
        });
      });
    });
  </script>
</body>
</html>
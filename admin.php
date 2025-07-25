<?php
session_start();

// Database connection
$host = 'localhost';
$db = 'hotel_db';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("<h2 style='color:red;'>Connection failed: " . htmlspecialchars($conn->connect_error) . "</h2>");
}

// Fetch all bookings from database
$bookings_query = "SELECT * FROM bookings ORDER BY created_at DESC";
$bookings_result = $conn->query($bookings_query);
$bookings = [];
if ($bookings_result) {
    while ($row = $bookings_result->fetch_assoc()) {
        $bookings[] = $row;
    }
}

// Calculate booking statistics by date for calendar
$booking_stats = [];
$total_bookings = 0;
$today_bookings = 0;
$today_revenue = 0;
$today = date('Y-m-d');

$room_rates = ['Single' => 100, 'Double' => 150, 'Suite' => 250];

foreach ($bookings as $booking) {
    $check_in = $booking['check_in'];
    $check_out = $booking['check_out'];
    $room_type = $booking['room_type'];
    
    // Count bookings for today
    if ($check_in <= $today && $check_out > $today) {
        $today_bookings++;
        $today_revenue += isset($booking['total_amount']) ? $booking['total_amount'] : 0;
    }
    
    $total_bookings++;
    
    // Create date range for calendar display
    $current_date = new DateTime($check_in);
    $end_date = new DateTime($check_out);
    
    while ($current_date < $end_date) {
        $date_str = $current_date->format('Y-m-d');
        
        if (!isset($booking_stats[$date_str])) {
            $booking_stats[$date_str] = ['single' => 0, 'double' => 0, 'suite' => 0];
        }
        
        $room_type_key = strtolower($room_type);
        if (isset($booking_stats[$date_str][$room_type_key])) {
            $booking_stats[$date_str][$room_type_key]++;
        }
        
        $current_date->add(new DateInterval('P1D'));
    }
}

// Calculate occupancy rate
$total_rooms = 30; // 10 of each type
$occupied_today = 0;
if (isset($booking_stats[$today])) {
    $occupied_today = $booking_stats[$today]['single'] + $booking_stats[$today]['double'] + $booking_stats[$today]['suite'];
}
$occupancy_rate = $total_rooms > 0 ? round(($occupied_today / $total_rooms) * 100) : 0;

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Panel - Hotel Ease</title>
  <link rel="stylesheet" href="style.css">
  <style>
    body {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      margin: 0;
      padding: 20px;
    }
    
    .admin-container {
      max-width: 1600px;
      margin: 0 auto;
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      border-radius: 20px;
      padding: 2rem;
      box-shadow: 0 20px 40px rgba(0,0,0,0.1);
      position: relative;
    }
    
    .admin-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 2rem;
      padding-bottom: 1rem;
      border-bottom: 2px solid #e1e8ed;
    }
    
    .admin-title {
      display: flex;
      align-items: center;
      gap: 1rem;
    }
    
    .admin-title h1 {
      margin: 0;
      color: #2c3e50;
      font-size: 2.5rem;
      font-weight: 700;
    }
    
    .admin-icon {
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
    
    /* Tab Navigation */
    .tab-navigation {
      display: flex;
      gap: 1rem;
      margin-bottom: 2rem;
      border-bottom: 2px solid #e1e8ed;
      padding-bottom: 1rem;
    }
    
    .tab-btn {
      background: rgba(102, 126, 234, 0.1);
      color: #667eea;
      border: 2px solid rgba(102, 126, 234, 0.2);
      padding: 12px 24px;
      border-radius: 12px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    
    .tab-btn:hover, .tab-btn.active {
      background: #667eea;
      color: white;
      border-color: #667eea;
      transform: translateY(-2px);
      text-decoration: none;
    }
    
    .tab-content {
      display: none;
    }
    
    .tab-content.active {
      display: block;
    }
    
    /* Statistics Cards */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1.5rem;
      margin-bottom: 2rem;
    }
    
    .stat-card {
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
    
    .stat-card:hover {
      transform: translateY(-5px);
    }
    
    .stat-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    
    .stat-icon {
      font-size: 2.5rem;
      margin-bottom: 1rem;
      display: block;
    }
    
    .stat-number {
      font-size: 2.5rem;
      font-weight: 700;
      color: #667eea;
      margin-bottom: 0.5rem;
      display: block;
    }
    
    .stat-label {
      color: #64748b;
      font-size: 1rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    /* Calendar Styles */
    .calendar-section {
      background: white;
      border-radius: 16px;
      padding: 2rem;
      box-shadow: 0 5px 20px rgba(0,0,0,0.1);
      margin-bottom: 2rem;
    }
    
    .calendar-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 2rem;
      flex-wrap: wrap;
      gap: 1rem;
    }
    
    .calendar-title {
      display: flex;
      align-items: center;
      gap: 1rem;
      color: #2c3e50;
      font-size: 1.5rem;
      font-weight: 600;
    }
    
    .calendar-controls {
      display: flex;
      align-items: center;
      gap: 1rem;
    }
    
    .month-nav {
      display: flex;
      align-items: center;
      gap: 1rem;
    }
    
    .nav-btn {
      background: #667eea;
      color: white;
      border: none;
      width: 40px;
      height: 40px;
      border-radius: 50%;
      cursor: pointer;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.2rem;
    }
    
    .nav-btn:hover {
      background: #5a67d8;
      transform: scale(1.1);
    }
    
    .current-month {
      font-size: 1.2rem;
      font-weight: 600;
      color: #2c3e50;
      min-width: 150px;
      text-align: center;
    }
    
    .room-filter {
      display: flex;
      gap: 0.5rem;
      flex-wrap: wrap;
    }
    
    .filter-btn {
      background: rgba(102, 126, 234, 0.1);
      color: #667eea;
      border: 2px solid rgba(102, 126, 234, 0.2);
      padding: 8px 16px;
      border-radius: 20px;
      font-size: 0.875rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
    }
    
    .filter-btn:hover, .filter-btn.active {
      background: #667eea;
      color: white;
      border-color: #667eea;
    }
    
    /* Calendar Grid */
    .calendar-grid {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      gap: 1px;
      background: #e1e8ed;
      border-radius: 12px;
      overflow: hidden;
    }
    
    .calendar-day-header {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 1rem;
      text-align: center;
      font-weight: 600;
      font-size: 0.875rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    .calendar-day {
      background: white;
      min-height: 120px;
      padding: 0.5rem;
      position: relative;
      cursor: pointer;
      transition: background-color 0.2s ease;
    }
    
    .calendar-day:hover {
      background: #f8fafc;
    }
    
    .calendar-day.other-month {
      background: #f1f5f9;
      color: #94a3b8;
    }
    
    .calendar-day.today {
      background: rgba(102, 126, 234, 0.1);
      border: 2px solid #667eea;
    }
    
    .day-number {
      font-weight: 600;
      color: #2c3e50;
      margin-bottom: 0.5rem;
    }
    
    .room-availability {
      display: flex;
      flex-direction: column;
      gap: 0.25rem;
    }
    
    .room-type-indicator {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0.25rem 0.5rem;
      border-radius: 4px;
      font-size: 0.75rem;
      font-weight: 600;
    }
    
    .room-type-indicator.single {
      background: rgba(59, 130, 246, 0.1);
      color: #1d4ed8;
    }
    
    .room-type-indicator.double {
      background: rgba(34, 197, 94, 0.1);
      color: #166534;
    }
    
    .room-type-indicator.suite {
      background: rgba(168, 85, 247, 0.1);
      color: #7c3aed;
    }
    
    .availability-count {
      font-weight: 700;
      font-size: 0.8rem;
    }
    
    .room-type-indicator.full {
      background: rgba(239, 68, 68, 0.1);
      color: #dc2626;
    }
    
    /* Booking Table */
    .bookings-table {
      background: white;
      border-radius: 16px;
      padding: 2rem;
      box-shadow: 0 5px 20px rgba(0,0,0,0.1);
      margin-bottom: 2rem;
    }
    
    .enhanced-table {
      width: 100%;
      border-collapse: collapse;
      background: white;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    
    .enhanced-table thead {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    
    .enhanced-table th {
      padding: 1rem;
      text-align: left;
      color: white;
      font-weight: 600;
      text-transform: uppercase;
      font-size: 0.875rem;
      letter-spacing: 0.5px;
    }
    
    .enhanced-table td {
      padding: 1rem;
      border-bottom: 1px solid #e1e8ed;
      color: #2c3e50;
      font-size: 0.95rem;
    }
    
    .enhanced-table tbody tr:hover {
      background-color: #f8fafc;
    }
    
    .enhanced-table tbody tr:last-child td {
      border-bottom: none;
    }
    
    /* Action Buttons */
    .action-btn {
      background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
      color: white;
      border: none;
      padding: 8px 16px;
      border-radius: 8px;
      font-size: 0.875rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 2px 8px rgba(239, 68, 68, 0.2);
    }
    
    .action-btn:hover {
      transform: translateY(-1px);
      box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
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
    
    /* Responsive Design */
    @media (max-width: 768px) {
      .admin-container {
        padding: 1rem;
        margin: 10px;
      }
      
      .admin-header {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
      }
      
      .calendar-header {
        flex-direction: column;
        align-items: stretch;
      }
      
      .calendar-controls {
        justify-content: center;
        flex-wrap: wrap;
      }
      
      .calendar-grid {
        font-size: 0.875rem;
      }
      
      .calendar-day {
        min-height: 80px;
        padding: 0.25rem;
      }
      
      .room-type-indicator {
        font-size: 0.6rem;
        padding: 0.125rem 0.25rem;
      }
      
      .tab-navigation {
        flex-wrap: wrap;
        justify-content: center;
      }
    }
  </style>
</head>
<body>
  <div class="admin-container">
    <!-- Admin Header -->
    <div class="admin-header">
      <div class="admin-title">
        <div class="admin-icon">👨‍💼</div>
        <h1>Admin Dashboard</h1>
      </div>
      <a href="admin_logout.php" class="logout-btn">
        🚪 Logout
      </a>
    </div>
    
    <!-- Tab Navigation -->
    <div class="tab-navigation">
      <button class="tab-btn active" onclick="showTab('calendar')">
        📅 Room Calendar
      </button>
      <button class="tab-btn" onclick="showTab('bookings')">
        📋 Bookings
      </button>
      <button class="tab-btn" onclick="showTab('statistics')">
        📊 Statistics
      </button>
    </div>
    
    <!-- Calendar Tab -->
    <div id="calendar" class="tab-content active">
      <!-- Statistics Overview -->
      <div class="stats-grid">
        <div class="stat-card">
          <span class="stat-icon">🏨</span>
          <span class="stat-number" id="totalRooms"><?= $total_rooms ?></span>
          <span class="stat-label">Total Rooms</span>
        </div>
        <div class="stat-card">
          <span class="stat-icon">📅</span>
          <span class="stat-number" id="todayBookings"><?= $today_bookings ?></span>
          <span class="stat-label">Today's Bookings</span>
        </div>
        <div class="stat-card">
          <span class="stat-icon">💰</span>
          <span class="stat-number" id="todayRevenue">RM<?= number_format($today_revenue, 0) ?></span>
          <span class="stat-label">Today's Revenue</span>
        </div>
        <div class="stat-card">
          <span class="stat-icon">📈</span>
          <span class="stat-number" id="occupancyRate"><?= $occupancy_rate ?>%</span>
          <span class="stat-label">Occupancy Rate</span>
        </div>
      </div>
      
      <!-- Room Availability Calendar -->
      <div class="calendar-section">
        <div class="calendar-header">
          <div class="calendar-title">
            <span>📅</span>
            <span>Room Availability Calendar</span>
          </div>
          <div class="calendar-controls">
            <div class="month-nav">
              <button class="nav-btn" onclick="changeMonth(-1)">‹</button>
              <div class="current-month" id="currentMonth"><?= date('F Y') ?></div>
              <button class="nav-btn" onclick="changeMonth(1)">›</button>
            </div>
            <div class="room-filter">
              <button class="filter-btn active" onclick="filterRooms('all')">All Rooms</button>
              <button class="filter-btn" onclick="filterRooms('single')">Single</button>
              <button class="filter-btn" onclick="filterRooms('double')">Double</button>
              <button class="filter-btn" onclick="filterRooms('suite')">Suite</button>
            </div>
          </div>
        </div>
        
        <div class="calendar-grid" id="calendarGrid">
          <!-- Calendar will be generated by JavaScript -->
        </div>
      </div>
    </div>
    
    <!-- Bookings Tab -->
    <div id="bookings" class="tab-content">
      <div class="bookings-table">
        <h2 style="color: #2c3e50; margin-bottom: 1.5rem;">
          <span style="margin-right: 0.5rem;">📋</span>
          All Bookings (<?= count($bookings) ?>)
        </h2>
        
        <table class="enhanced-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Guest Name</th>
              <th>Room Type</th>
              <th>Check-In</th>
              <th>Check-Out</th>
              <th>Amount</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($bookings)): ?>
              <tr>
                <td colspan="8" style="text-align: center; padding: 2rem; color: #64748b;">
                  No bookings found. Bookings will appear here once customers make reservations.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($bookings as $booking): ?>
                <tr>
                  <td>#<?= htmlspecialchars($booking['id']) ?></td>
                  <td><?= htmlspecialchars($booking['guest_name']) ?></td>
                  <td><?= htmlspecialchars($booking['room_type']) ?></td>
                  <td><?= date('M d, Y', strtotime($booking['check_in'])) ?></td>
                  <td><?= date('M d, Y', strtotime($booking['check_out'])) ?></td>
                  <td>RM<?= number_format(isset($booking['total_amount']) ? $booking['total_amount'] : 0, 2) ?></td>
                  <td>
                    <span style="background: <?= ($booking['payment_status'] ?? 'pending') == 'paid' ? 'rgba(34, 197, 94, 0.1)' : 'rgba(245, 158, 11, 0.1)' ?>; 
                                 color: <?= ($booking['payment_status'] ?? 'pending') == 'paid' ? '#166534' : '#92400e' ?>; 
                                 padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.875rem; font-weight: 600;">
      <?= ($booking['payment_status'] ?? 'pending') == 'paid' ? '✓ Confirmed' : '⏳ Pending' ?>
    </span>
                  </td>
                  <td>
                    <button class="action-btn" onclick="deleteBooking(<?= $booking['id'] ?>)">
                      🗑️ Delete
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    
    <!-- Statistics Tab -->
    <div id="statistics" class="tab-content">
      <div style="text-align: center; padding: 4rem; color: #64748b;">
        <div style="font-size: 4rem; margin-bottom: 1rem;">📊</div>
        <h3 style="color: #2c3e50; margin-bottom: 1rem;">Detailed Statistics</h3>
        <p>Advanced analytics and reporting features coming soon!</p>
        <div style="margin-top: 2rem; text-align: left; max-width: 600px; margin-left: auto; margin-right: auto;">
          <h4 style="color: #2c3e50;">Current Overview:</h4>
          <ul style="color: #64748b; line-height: 1.8;">
            <li>Total Bookings: <?= $total_bookings ?></li>
            <li>Today's Revenue: RM<?= number_format($today_revenue, 2) ?></li>
            <li>Current Occupancy: <?= $occupancy_rate ?>%</li>
            <li>Active Bookings: <?= $today_bookings ?></li>
          </ul>
        </div>
      </div>
    </div>
  </div>
  
  <script>
    // Pass PHP data to JavaScript
    const bookingData = <?= json_encode($booking_stats) ?>;
    const roomCapacity = {
      single: 10,
      double: 10,
      suite: 10
    };
    
    let currentDate = new Date();
    let currentFilter = 'all';
    
    // Initialize calendar
    document.addEventListener('DOMContentLoaded', function() {
      generateCalendar();
    });
    
    function showTab(tabName) {
      // Hide all tab contents
      const tabContents = document.querySelectorAll('.tab-content');
      tabContents.forEach(tab => {
        tab.classList.remove('active');
      });
      
      // Remove active class from all tab buttons
      const tabBtns = document.querySelectorAll('.tab-btn');
      tabBtns.forEach(btn => {
        btn.classList.remove('active');
      });
      
      // Show selected tab
      document.getElementById(tabName).classList.add('active');
      event.target.classList.add('active');
    }
    
    function generateCalendar() {
      const calendar = document.getElementById('calendarGrid');
      const monthElement = document.getElementById('currentMonth');
      
      // Clear previous calendar
      calendar.innerHTML = '';
      
      // Update month display
      const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                         'July', 'August', 'September', 'October', 'November', 'December'];
      monthElement.textContent = `${monthNames[currentDate.getMonth()]} ${currentDate.getFullYear()}`;
      
      // Add day headers
      const dayHeaders = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
      dayHeaders.forEach(day => {
        const dayHeader = document.createElement('div');
        dayHeader.className = 'calendar-day-header';
        dayHeader.textContent = day;
        calendar.appendChild(dayHeader);
      });
      
      // Get first day of month and number of days
      const firstDay = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
      const lastDay = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0);
      const startDate = new Date(firstDay);
      startDate.setDate(startDate.getDate() - firstDay.getDay());
      
      // Generate calendar days
      for (let i = 0; i < 42; i++) {
        const dayDate = new Date(startDate);
        dayDate.setDate(startDate.getDate() + i);
        
        const dayElement = document.createElement('div');
        dayElement.className = 'calendar-day';
        
        if (dayDate.getMonth() !== currentDate.getMonth()) {
          dayElement.classList.add('other-month');
        }
        
        if (isToday(dayDate)) {
          dayElement.classList.add('today');
        }
        
        const dateString = formatDateString(dayDate);
        const bookings = bookingData[dateString] || { single: 0, double: 0, suite: 0 };
        
        dayElement.innerHTML = `
          <div class="day-number">${dayDate.getDate()}</div>
          <div class="room-availability">
            ${generateRoomIndicators(bookings)}
          </div>
        `;
        
        calendar.appendChild(dayElement);
      }
    }
    
    function generateRoomIndicators(bookings) {
      let indicators = '';
      
      if (currentFilter === 'all' || currentFilter === 'single') {
        const booked = bookings.single || 0;
        const available = roomCapacity.single - booked;
        const className = available === 0 ? 'full' : 'single';
        indicators += `
          <div class="room-type-indicator ${className}">
            <span>S</span>
            <span class="availability-count">${available}/10</span>
          </div>
        `;
      }
      
      if (currentFilter === 'all' || currentFilter === 'double') {
        const booked = bookings.double || 0;
        const available = roomCapacity.double - booked;
        const className = available === 0 ? 'full' : 'double';
        indicators += `
          <div class="room-type-indicator ${className}">
            <span>D</span>
            <span class="availability-count">${available}/10</span>
          </div>
        `;
      }
      
      if (currentFilter === 'all' || currentFilter === 'suite') {
        const booked = bookings.suite || 0;
        const available = roomCapacity.suite - booked;
        const className = available === 0 ? 'full' : 'suite';
        indicators += `
          <div class="room-type-indicator ${className}">
            <span>Su</span>
            <span class="availability-count">${available}/10</span>
          </div>
        `;
      }
      
      return indicators;
    }
    
    function changeMonth(direction) {
      currentDate.setMonth(currentDate.getMonth() + direction);
      generateCalendar();
    }
    
    function filterRooms(type) {
      currentFilter = type;
      
      // Update filter buttons
      const filterBtns = document.querySelectorAll('.filter-btn');
      filterBtns.forEach(btn => btn.classList.remove('active'));
      event.target.classList.add('active');
      
      generateCalendar();
    }
    
    function formatDateString(date) {
      return date.getFullYear() + '-' + 
             String(date.getMonth() + 1).padStart(2, '0') + '-' + 
             String(date.getDate()).padStart(2, '0');
    }
    
    function isToday(date) {
      const today = new Date();
      return date.toDateString() === today.toDateString();
    }
    
    function deleteBooking(id) {
      if (confirm(`Are you sure you want to delete booking #${id}?`)) {
        // Create a form to submit the deletion request
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'delete_booking.php';
        
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'booking_id';
        input.value = id;
        
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
      }
    }
  </script>
</body>
</html>
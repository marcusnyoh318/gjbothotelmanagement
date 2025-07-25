<?php
session_start();

$login_error = '';
$info_message = '';

// Check for logout message
if (isset($_GET['logged_out']) && $_GET['logged_out'] == '1') {
    $info_message = 'You have been logged out successfully.';
}

// Check for access message
if (isset($_GET['message'])) {
    $info_message = htmlspecialchars($_GET['message']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $login_error = 'Please enter both username and password.';
    } else {
        $usersFile = __DIR__ . '/users.txt';
        $found = false;
        
        // Check registered users from file
        if (file_exists($usersFile)) {
            $users = file($usersFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($users as $userLine) {
                $parts = explode(':', $userLine, 2);
                if (count($parts) === 2) {
                    list($existingUser, $hashed) = $parts;
                    if (trim($existingUser) === $username && password_verify($password, trim($hashed))) {
                        $found = true;
                        break;
                    }
                }
            }
        }
        
        // Fallback to demo user (for testing)
        if (!$found && $username === 'user' && $password === 'user123') {
            $found = true;
        }
        
        if ($found) {
            // Set session variables
            $_SESSION['user_logged_in'] = true;
            $_SESSION['username'] = $username;
            
            // Redirect to the page they were trying to access, or home
            $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'user_bookings.php';
            header('Location: ' . $redirect);
            exit;
        } else {
            $login_error = 'Invalid username or password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>User Login - Hotel Management</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .login-card {
      background: #fff;
      border-radius: 14px;
      box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
      padding: 32px 28px 24px 28px;
      max-width: 400px;
      width: 100%;
      margin: 60px auto 0 auto;
      display: flex;
      flex-direction: column;
      align-items: center;
    }
    .login-card h2 {
      margin-top: 0;
      margin-bottom: 18px;
      color: #007bff;
      font-size: 1.5rem;
      letter-spacing: 0.5px;
      text-align: center;
      width: 100%;
    }
    .login-card .error {
      color: #d32f2f;
      text-align: center;
      margin-bottom: 10px;
      width: 100%;
      background: #ffebee;
      padding: 8px;
      border-radius: 6px;
      border: 1px solid #ffcdd2;
    }
    .login-card .info {
      color: #1976d2;
      text-align: center;
      margin-bottom: 10px;
      width: 100%;
      background: #e3f2fd;
      padding: 8px;
      border-radius: 6px;
      border: 1px solid #bbdefb;
    }
    .login-card form {
      width: 100%;
      display: flex;
      flex-direction: column;
      align-items: stretch;
    }
    .login-card label {
      margin-top: 10px;
      color: #444;
      text-align: left;
    }
    .login-card button {
      margin-top: 16px;
    }
    .login-card .back-link {
      text-align: center;
      margin-top: 18px;
      width: 100%;
    }
    .login-card .back-link a {
      margin: 0 px;
    }
    .demo-credentials {
      background: #fff3e0;
      border: 1px solid #ffcc02;
      border-radius: 6px;
      padding: 12px;
      margin: 15px 0;
      text-align: center;
      font-size: 14px;
    }
    .demo-credentials strong {
      color: #f57c00;
    }
    @media (max-width: 500px) {
      .login-card {
        padding: 16px 6px 14px 6px;
      }
    }
  </style>
</head>
<body>
  <div class="container" style="background:none;box-shadow:none;border:none;align-items:center;">
    <div class="login-card">
      <h2>User Login</h2>
      <?php if ($login_error): ?>
        <div class="error"><?= htmlspecialchars($login_error) ?></div>
      <?php endif; ?>
      <?php if ($info_message): ?>
        <div class="info"><?= htmlspecialchars($info_message) ?></div>
      <?php endif; ?>
      

      <form method="POST" autocomplete="off">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required autofocus>
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
        <button type="submit">Login</button>
      </form>
      <div class="back-link">
        <a href="user_register.php">Create Account</a> |
        <a href="index.html">Back to Home</a> |
        <a href="admin_login.php">Admin Login</a>
      </div>
    </div>
  </div>
</body>
</html>
<?php
session_start();
$login_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    // Simple hardcoded credentials
    if ($username === 'admin' && $password === 'admin123') {
        $_SESSION['admin_logged_in'] = true;
        header('Location: admin.php');
        exit;
    } else {
        $login_error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Login</title>
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
      <h2>Admin Login</h2>
      <?php if ($login_error): ?>
        <div class="error"><?= htmlspecialchars($login_error) ?></div>
      <?php endif; ?>
      <form method="POST" autocomplete="off">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required autofocus>
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
        <button type="submit">Login</button>
      </form>
      <div class="back-link">
        <a href="index.html">Back to Booking Page</a>
      </div>
    </div>
  </div>
</body>
</html>

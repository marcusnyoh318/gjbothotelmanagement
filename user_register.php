<?php
session_start();
$registration_error = '';
$registration_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($username) || empty($password) || empty($confirm_password)) {
        $registration_error = 'All fields are required.';
    } elseif (strlen($username) < 3) {
        $registration_error = 'Username must be at least 3 characters long.';
    } elseif (strlen($password) < 6) {
        $registration_error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm_password) {
        $registration_error = 'Passwords do not match.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $registration_error = 'Username can only contain letters, numbers, and underscores.';
    } else {
        $usersFile = __DIR__ . '/users.txt';
        $userExists = false;
        
        // Check if user already exists
        if (file_exists($usersFile)) {
            $users = file($usersFile, FILE_IGNORE_NEW_LINES);
            foreach ($users as $userLine) {
                list($existingUser) = explode(':', $userLine, 2);
                if (strtolower($existingUser) === strtolower($username)) {
                    $userExists = true;
                    break;
                }
            }
        }
        
        if ($userExists) {
            $registration_error = 'Username already exists. Please choose a different username.';
        } else {
            // Hash the password and save user
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $userEntry = $username . ':' . $hashedPassword . "\n";
            
            if (file_put_contents($usersFile, $userEntry, FILE_APPEND | LOCK_EX) !== false) {
                $registration_success = 'Account created successfully! You can now log in.';
                // Clear form data
                $username = '';
                $password = '';
                $confirm_password = '';
            } else {
                $registration_error = 'Error creating account. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Create Account</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .register-card {
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
    .register-card h2 {
      margin-top: 0;
      margin-bottom: 18px;
      color: #007bff;
      font-size: 1.5rem;
      letter-spacing: 0.5px;
      text-align: center;
      width: 100%;
    }
    .register-card .error {
      color: #d32f2f;
      text-align: center;
      margin-bottom: 10px;
      width: 100%;
      padding: 8px;
      background: #ffebee;
      border-radius: 6px;
      border: 1px solid #ffcdd2;
    }
    .register-card .success {
      color: #2e7d32;
      text-align: center;
      margin-bottom: 10px;
      width: 100%;
      padding: 8px;
      background: #e8f5e8;
      border-radius: 6px;
      border: 1px solid #c8e6c9;
    }
    .register-card form {
      width: 100%;
      display: flex;
      flex-direction: column;
      align-items: stretch;
    }
    .register-card label {
      margin-top: 10px;
      color: #444;
      text-align: left;
      font-size: 14px;
    }
    .register-card input {
      margin-bottom: 15px;
    }
    .register-card button {
      margin-top: 16px;
    }
    .register-card .back-link {
      text-align: center;
      margin-top: 18px;
      width: 100%;
    }
    .register-card .back-link a {
      margin: 0 8px;
    }
    .password-requirements {
      font-size: 12px;
      color: #666;
      margin-top: -15px;
      margin-bottom: 15px;
      line-height: 1.4;
    }
    @media (max-width: 500px) {
      .register-card {
        padding: 16px 6px 14px 6px;
      }
    }
  </style>
  <script>
    // Real-time password validation
    function validatePassword() {
      const password = document.getElementById('password').value;
      const confirmPassword = document.getElementById('confirm_password').value;
      const submitBtn = document.getElementById('submitBtn');
      
      let isValid = true;
      
      if (password.length < 6) {
        isValid = false;
      }
      
      if (password !== confirmPassword && confirmPassword !== '') {
        isValid = false;
      }
      
      submitBtn.disabled = !isValid;
      submitBtn.style.opacity = isValid ? '1' : '0.6';
    }
    
    function checkUsername() {
      const username = document.getElementById('username').value;
      const submitBtn = document.getElementById('submitBtn');
      
      if (username.length < 3 || !/^[a-zA-Z0-9_]+$/.test(username)) {
        submitBtn.disabled = true;
        submitBtn.style.opacity = '0.6';
      } else {
        validatePassword();
      }
    }
  </script>
</head>
<body>
  <div class="container" style="background:none;box-shadow:none;border:none;align-items:center;">
    <div class="register-card">
      <h2>Create Account</h2>
      <?php if ($registration_error): ?>
        <div class="error"><?= htmlspecialchars($registration_error) ?></div>
      <?php endif; ?>
      <?php if ($registration_success): ?>
        <div class="success"><?= htmlspecialchars($registration_success) ?></div>
      <?php endif; ?>
      <form method="POST" autocomplete="off">
        <label for="username">Username:</label>
        <input type="text" 
               id="username" 
               name="username" 
               value="<?= htmlspecialchars($username ?? '') ?>"
               required 
               autofocus 
               minlength="3"
               pattern="[a-zA-Z0-9_]+"
               title="Username can only contain letters, numbers, and underscores"
               onkeyup="checkUsername()">
        <div class="password-requirements">
          Username must be at least 3 characters long and contain only letters, numbers, and underscores.
        </div>
        
        <label for="password">Password:</label>
        <input type="password" 
               id="password" 
               name="password" 
               required 
               minlength="6"
               onkeyup="validatePassword()">
        <div class="password-requirements">
          Password must be at least 6 characters long.
        </div>
        
        <label for="confirm_password">Confirm Password:</label>
        <input type="password" 
               id="confirm_password" 
               name="confirm_password" 
               required 
               minlength="6"
               onkeyup="validatePassword()">
        
        <button type="submit" id="submitBtn">Create Account</button>
      </form>
      <div class="back-link">
        <a href="user_login.php">Already have an account? Login</a><br>
        <a href="index.html">Back to Booking Page</a>
      </div>
    </div>
  </div>
</body>
</html>
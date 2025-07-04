<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        echo "<p class='message error'>Please fill in both fields.</p>";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            echo "<p class='message error'>Username already taken. Please choose another.</p>";
        } else {
            $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $stmt->bind_param("ss", $username, $hashed_password);

            if ($stmt->execute()) {
                echo "<p class='message success'>✅ Registration successful. <a href='login.php'>Login</a></p>";
            } else {
                echo "<p class='message error'>Something went wrong. Try again.</p>";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register</title>
  <style>
    body {
      background: #f4f6f8;
      font-family: "Segoe UI", Tahoma, sans-serif;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }

    .register-box {
      background: #fff;
      padding: 40px;
      border-radius: 12px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.06);
      width: 100%;
      max-width: 400px;
    }

    h2 {
      text-align: center;
      margin-bottom: 24px;
      color: #333;
    }

    form input[type="text"],
    form input[type="password"] {
      width: 100%;
      padding: 14px;
      margin: 10px 0;
      border: 1px solid #ccc;
      border-radius: 10px;
      font-size: 16px;
    }

    form button {
      width: 100%;
      padding: 14px;
      border: none;
      background-color: #4f46e5;
      color: #fff;
      font-weight: bold;
      font-size: 16px;
      border-radius: 10px;
      cursor: pointer;
      transition: background 0.3s;
    }

    form button:hover {
      background-color: #4338ca;
    }

    .message {
      text-align: center;
      margin: 10px 0 20px;
      padding: 10px;
      border-radius: 8px;
    }

    .message.success {
      background-color: #dcfce7;
      color: #166534;
    }

    .message.error {
      background-color: #fee2e2;
      color: #991b1b;
    }
  </style>
</head>
<body>
  <div class="register-box">
    <h2>Create Account</h2>
    <form method="POST">
      <input type="text" name="username" placeholder="Enter Username" required>
      <input type="password" name="password" placeholder="Enter Password" required>
      <button type="submit">Register</button>
    </form>
  </div>
</body>
</html>

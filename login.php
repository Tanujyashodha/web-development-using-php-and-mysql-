<?php
session_start();
include "db.php";

if (isset($_POST['submit'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username = '$username'";
    $result = mysqli_query($conn, $sql);

    if (!$result) {
        echo "<div class='error'>Error!: {$conn->error}</div>";
    } else {
        if ($result->num_rows > 0) {
            $row = mysqli_fetch_assoc($result);

            // ✅ You should use password_verify() if passwords are hashed
            // if (password_verify($password, $row['password'])) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['user_name'] = $row['username'];
                echo "<div class='success'>Logged in successfully! <a href='dashboard.php'>Go to Dashboard</a></div>";
            // } else {
            //     echo "<div class='error'>Incorrect password.</div>";
            // }
        } else {
            echo "<div class='error'>User not found.</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body {
      font-family: "Segoe UI", Tahoma, sans-serif;
      background-color: #f9fafb;
      display: flex;
      align-items: center;
      justify-content: center;
      height: 100vh;
      margin: 0;
    }

    .login-box {
      background-color: #ffffff;
      padding: 40px 30px;
      border-radius: 12px;
      box-shadow: 0 6px 16px rgba(0, 0, 0, 0.1);
      width: 100%;
      max-width: 400px;
    }

    .login-box h2 {
      text-align: center;
      margin-bottom: 24px;
      color: #4f46e5;
    }

    label {
      display: block;
      margin-bottom: 8px;
      color: #374151;
      font-weight: 600;
    }

    input[type="text"],
    input[type="password"] {
      width: 100%;
      padding: 12px;
      margin-bottom: 20px;
      border: 1px solid #d1d5db;
      border-radius: 8px;
      font-size: 16px;
      background: #fff;
    }

    input[type="submit"] {
      width: 100%;
      padding: 12px;
      background-color: #4f46e5;
      border: none;
      border-radius: 8px;
      color: white;
      font-weight: bold;
      font-size: 16px;
      cursor: pointer;
      transition: background-color 0.2s;
    }

    input[type="submit"]:hover {
      background-color: #4338ca;
    }

    .error {
      background: #fee2e2;
      color: #b91c1c;
      padding: 12px;
      margin-bottom: 20px;
      border-radius: 8px;
    }

    .success {
      background: #d1fae5;
      color: #065f46;
      padding: 12px;
      margin-bottom: 20px;
      border-radius: 8px;
    }

    a {
      color: #4f46e5;
      text-decoration: underline;
    }

    a:hover {
      color: #3730a3;
    }
  </style>
</head>
<body>

<div class="login-box">
  <h2>Login</h2>
  <form action="login.php" method="POST">
    <label for="username">Username</label>
    <input type="text" name="username" id="username" required>

    <label for="password">Password</label>
    <input type="password" name="password" id="password" required>

    <input type="submit" name="submit" value="Login">
  </form>
</div>

</body>
</html>

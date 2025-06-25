<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Check for empty input
    if ($username === '' || $password === '') {
        echo "Please fill in both fields.";
    } else {
        // Hash password securely
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Check if username already exists
        $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            echo "Username already taken. Please choose another.";
        } else {
            // Insert new user
            $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $stmt->bind_param("ss", $username, $hashed_password);

            if ($stmt->execute()) {
                echo "✅ Registration successful. <a href='login.php'>Login</a>";
            } else {
                echo "Something went wrong. Try again.";
            }
        }
    }
}
?>

<!-- 🧾 HTML Form -->
<form method="POST">
  <input type="text" name="username" placeholder="Enter Username" required><br>
  <input type="password" name="password" placeholder="Enter Password" required><br>
  <button type="submit">Register</button>
</form>

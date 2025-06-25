<?php
session_start();
include "db.php";

if (isset($_POST['submit'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username = '$username'";
    $result = mysqli_query($conn, $sql);

    if (!$result) {
        echo "Error!: {$conn->error}";
    } else {
        if ($result->num_rows > 0) {
            $row = mysqli_fetch_assoc($result);

            // ✅ Password check recommended if using password_hash()
            // if (password_verify($password, $row['password'])) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['user_name'] = $row['username'];
                echo "<br> Logged in successfully! <br> <a href='dashboard.php'>Dashboard</a>";
            // } else {
            //     echo "Incorrect password.";
            // }
        } else {
            echo "User not found.";
        }
    }
}
?>
<form action="login.php" method="POST">
  Username: <input type="text" name="username" required><br>
  Password: <input type="password" name="password" required><br>
  <input type="submit" name="submit" value="Login">
</form>
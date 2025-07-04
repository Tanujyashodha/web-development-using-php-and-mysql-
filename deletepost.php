<?php
session_start();
include 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$post_id = intval($_GET['id'] ?? 0);
$user_id = $_SESSION['user_id'];

// First, fetch the post to confirm it exists and belongs to the user
$stmt = $conn->prepare("SELECT title FROM posts WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $post_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<h3 style='color:red;text-align:center;'>❌ Post not found or not authorized.</h3>";
    exit;
}

$post = $result->fetch_assoc();

// If form submitted, proceed with deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $delStmt = $conn->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?");
    $delStmt->bind_param("ii", $post_id, $user_id);
    if ($delStmt->execute()) {
        header("Location: dashboard.php?deleted=1");
        exit;
    } else {
        echo "<h3 style='color:red;'>❌ Failed to delete post.</h3>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Delete Post</title>
  <style>
    body {
      font-family: "Segoe UI", Tahoma, sans-serif;
      background-color: #f9fafb;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
    }
    .confirm-box {
      background-color: white;
      padding: 30px 40px;
      border-radius: 12px;
      box-shadow: 0 6px 20px rgba(0,0,0,0.1);
      text-align: center;
      max-width: 400px;
      width: 100%;
    }
    h2 {
      color: #111827;
      margin-bottom: 15px;
    }
    p {
      margin-bottom: 25px;
      color: #4b5563;
    }
    .btn {
      padding: 10px 16px;
      border: none;
      border-radius: 8px;
      font-size: 15px;
      cursor: pointer;
      margin: 0 8px;
    }
    .btn-danger {
      background-color: #dc2626;
      color: white;
    }
    .btn-danger:hover {
      background-color: #b91c1c;
    }
    .btn-secondary {
      background-color: #9ca3af;
      color: white;
    }
    .btn-secondary:hover {
      background-color: #6b7280;
    }
  </style>
</head>
<body>

<div class="confirm-box">
  <h2>Confirm Deletion</h2>
  <p>Are you sure you want to delete the post: <strong><?= htmlspecialchars($post['title']) ?></strong>?</p>
  <form method="POST">
    <button type="submit" class="btn btn-danger">Yes, Delete</button>
    <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
  </form>
</div>

</body>
</html>
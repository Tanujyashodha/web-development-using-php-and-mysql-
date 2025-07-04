<?php
session_start();
include 'db.php';

// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$post_id = intval($_GET['id'] ?? 0);

// Get post
$stmt = $conn->prepare("SELECT title, content FROM posts WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $post_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<h3 style='color:red;text-align:center;'>❌ Post not found or unauthorized.</h3>";
    exit;
}

$post = $result->fetch_assoc();
$message = '';

// Update on POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);

    if ($title === '' || $content === '') {
        $message = "❗ Please fill in all fields.";
    } else {
        $update = $conn->prepare("UPDATE posts SET title = ?, content = ? WHERE id = ? AND user_id = ?");
        $update->bind_param("ssii", $title, $content, $post_id, $user_id);

        if ($update->execute()) {
            header("Location: dashboard.php?updated=1");
            exit;
        } else {
            $message = "❌ Failed to update post.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Post</title>
  <style>
    body {
      font-family: "Segoe UI", Tahoma, sans-serif;
      background-color: #f3f4f6;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
    }
    .form-container {
      width: 500px;
      background: white;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 6px 20px rgba(0,0,0,0.1);
    }
    h2 {
      margin-bottom: 20px;
      text-align: center;
      color: #1f2937;
    }
    input[type="text"], textarea {
      width: 100%;
      padding: 10px 12px;
      margin-bottom: 15px;
      border: 1px solid #ccc;
      border-radius: 8px;
      font-size: 14px;
    }
    button {
      width: 100%;
      padding: 10px;
      background-color: #4f46e5;
      border: none;
      color: white;
      font-size: 16px;
      border-radius: 8px;
      cursor: pointer;
    }
    button:hover {
      background-color: #4338ca;
    }
    .message {
      margin-top: 10px;
      color: #dc2626;
      text-align: center;
    }
  </style>
</head>
<body>

<div class="form-container">
  <h2>Edit Post</h2>
  <form method="POST">
    <input type="text" name="title" value="<?= htmlspecialchars($post['title']) ?>" required>
    <textarea name="content" rows="6" required><?= htmlspecialchars($post['content']) ?></textarea>
    <button type="submit">Update Post</button>
  </form>
  <?php if ($message): ?>
    <div class="message"><?= $message ?></div>
  <?php endif; ?>
</div>

</body>
</html>
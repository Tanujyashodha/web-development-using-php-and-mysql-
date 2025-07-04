<?php
session_start();
include 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');

    if ($title === '' || $content === '') {
        $message = "❗ Title and Content cannot be empty.";
    } else {
        $user_id = $_SESSION['user_id'];

        $stmt = $conn->prepare("INSERT INTO posts (user_id, title, content) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $title, $content);

        if ($stmt->execute()) {
            $message = "✅ Post added successfully!";
        } else {
            $message = "❌ Error: " . $stmt->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Create Post</title>
  <style>
    :root {
      --primary: #4f46e5;
      --primary-dark: #4338ca;
      --bg: #f9fafb;
      --text: #333;
      --card-bg: #ffffff;
      --border: #e5e7eb;
      --shadow: rgba(0, 0, 0, 0.08);
    }

    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      font-family: "Segoe UI", Tahoma, sans-serif;
      background-color: var(--bg);
      color: var(--text);
      display: flex;
      justify-content: center;
      align-items: flex-start;
      min-height: 100vh;
      padding: 40px 16px;
    }

    .container {
      max-width: 700px;
      width: 100%;
      background: var(--card-bg);
      border: 1px solid var(--border);
      padding: 35px 40px;
      border-radius: 12px;
      box-shadow: 0 8px 24px var(--shadow);
    }

    h2 {
      margin-bottom: 24px;
      text-align: center;
      font-size: 26px;
    }

    .form-group {
      margin-bottom: 20px;
    }

    label {
      display: block;
      margin-bottom: 6px;
      font-weight: 600;
    }

    input[type="text"],
    textarea {
      width: 100%;
      padding: 12px 14px;
      border: 1px solid var(--border);
      border-radius: 8px;
      font-size: 15px;
      background-color: #f9f9f9;
    }

    textarea {
      resize: vertical;
      min-height: 150px;
    }

    .btn {
      padding: 12px 20px;
      border: none;
      border-radius: 8px;
      font-size: 15px;
      cursor: pointer;
      transition: background-color 0.2s ease;
      text-decoration: none;
      display: inline-block;
      margin-right: 10px;
    }

    .btn-primary {
      background-color: var(--primary);
      color: white;
    }

    .btn-primary:hover {
      background-color: var(--primary-dark);
    }

    .btn-secondary {
      background-color: #9ca3af;
      color: white;
    }

    .btn-secondary:hover {
      background-color: #6b7280;
    }

    .message {
      margin-bottom: 20px;
      padding: 10px 15px;
      border-radius: 6px;
      font-size: 14px;
    }

    .message.success {
      background-color: #ecfdf5;
      color: #065f46;
      border: 1px solid #10b981;
    }

    .message.error {
      background-color: #fef2f2;
      color: #991b1b;
      border: 1px solid #f87171;
    }
  </style>
</head>
<body>

<div class="container">
  <h2>Create a New Blog Post</h2>

  <?php if ($message): ?>
    <div class="message <?= str_starts_with($message, '✅') ? 'success' : 'error' ?>">
      <?= $message ?>
    </div>
  <?php endif; ?>

  <form method="POST">
    <div class="form-group">
      <label for="title">Post Title</label>
      <input type="text" name="title" id="title" required>
    </div>

    <div class="form-group">
      <label for="content">Content</label>
      <textarea name="content" id="content" required></textarea>
    </div>

    <button type="submit" class="btn btn-primary">Publish</button>
    <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
  </form>
</div>

</body>
</html>
<?php
include 'db.php'; // include your database connection

// Fetch posts
$sql = "SELECT * FROM posts ORDER BY created_at DESC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>All Blog Posts</title>
  <style>
    body {
      font-family: "Segoe UI", Tahoma, sans-serif;
      background-color: #f4f6f8;
      margin: 0;
      padding: 20px;
    }
    .container {
      max-width: 800px;
      margin: auto;
    }
    .post {
      background-color: white;
      padding: 20px;
      margin-bottom: 20px;
      border-radius: 10px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.06);
    }
    .post h2 {
      margin-top: 0;
      font-size: 24px;
      color: #111827;
    }
    .post p {
      color: #374151;
      line-height: 1.6;
    }
    .date {
      color: #6b7280;
      font-size: 13px;
      margin-bottom: 10px;
    }
    h1 {
      text-align: center;
      margin-bottom: 30px;
      color: #1f2937;
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>📝 All Blog Posts</h1>

    <?php if (mysqli_num_rows($result) > 0): ?>
      <?php while ($row = mysqli_fetch_assoc($result)): ?>
        <div class="post">
          <h2><?= htmlspecialchars($row['title']) ?></h2>
          <div class="date">Posted on <?= date("F j, Y, g:i a", strtotime($row['created_at'])) ?></div>
          <p><?= nl2br(htmlspecialchars($row['content'])) ?></p>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <p>No posts found.</p>
    <?php endif; ?>
  </div>
</body>
</html>
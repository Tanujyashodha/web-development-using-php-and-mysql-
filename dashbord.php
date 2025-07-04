<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

$search = trim($_GET['search'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 5;
$offset = ($page - 1) * $limit;

$search_safe = mysqli_real_escape_string($conn, $search);
$search_condition = $search ? "AND (title LIKE '%$search_safe%' OR content LIKE '%$search_safe%')" : "";

$count_sql = "SELECT COUNT(*) as total FROM posts WHERE user_id = $user_id $search_condition";
$total_result = mysqli_query($conn, $count_sql);
$total_posts = mysqli_fetch_assoc($total_result)['total'];
$total_pages = ceil($total_posts / $limit);

$sql = "SELECT * FROM posts WHERE user_id = $user_id $search_condition ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(to right, #f0f4f8, #d9e4f5);
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .dashboard-container {
      max-width: 960px;
      margin: 60px auto;
      background: #ffffff;
      padding: 50px;
      border-radius: 18px;
      box-shadow: 0 12px 30px rgba(0, 0, 0, 0.08);
    }

    .dashboard-header {
      text-align: center;
      margin-bottom: 40px;
    }

    .dashboard-header h2 {
      font-weight: bold;
      color: #2c3e50;
    }

    .search-bar {
      display: flex;
      gap: 10px;
      margin-bottom: 30px;
    }

    .search-bar input {
      flex: 1;
      border-radius: 8px;
      border: 1px solid #ced4da;
      padding: 12px;
      font-size: 15px;
    }

    .search-bar button {
      border-radius: 8px;
      padding: 12px 20px;
      background: #0077b6;
      border: none;
      color: white;
      font-weight: 600;
    }

    .search-bar button:hover {
      background: #023e8a;
    }

    .post {
      background-color: #f8f9fa;
      padding: 24px;
      border-left: 5px solid #0077b6;
      border-radius: 12px;
      margin-bottom: 24px;
      transition: box-shadow 0.3s ease;
    }

    .post:hover {
      box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
    }

    .post h5 {
      color: #1d3557;
      font-size: 20px;
      font-weight: 700;
    }

    .post p {
      font-size: 15px;
      color: #495057;
      margin-top: 10px;
    }

    .post small {
      color: #6c757d;
      display: block;
      margin-top: 8px;
    }

    .pagination .page-link {
      border-radius: 8px;
      margin: 0 5px;
      color: #0077b6;
      font-weight: 500;
    }

    .pagination .page-item.active .page-link {
      background-color: #0077b6;
      color: white;
      border: none;
    }

    .action-buttons {
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
      justify-content: center;
      margin-top: 30px;
    }

    .action-buttons .btn {
      border-radius: 10px;
      padding: 10px 18px;
      font-weight: 600;
    }

    .logout-btn {
      display: block;
      margin: 40px auto 0;
      font-weight: 600;
      border-radius: 10px;
      padding: 12px 24px;
    }
  </style>
</head>
<body>
  <div class="dashboard-container">
    <div class="dashboard-header">
      <h2>Welcome, <?= htmlspecialchars($user_name) ?> 👋</h2>
    </div>

    <!-- Search Form -->
    <form method="GET" class="search-bar">
      <input type="text" name="search" placeholder="Search your posts..." value="<?= htmlspecialchars($search) ?>">
      <button type="submit">Search</button>
    </form>

    <!-- Posts -->
    <?php if ($result && mysqli_num_rows($result) > 0): ?>
      <?php while ($row = mysqli_fetch_assoc($result)): ?>
        <div class="post">
          <h5><?= htmlspecialchars($row['title']) ?></h5>
          <p><?= nl2br(htmlspecialchars($row['content'])) ?></p>
          <small>Posted on <?= date('F j, Y', strtotime($row['created_at'])) ?></small>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <p>No posts found.</p>
    <?php endif; ?>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
      <nav class="mt-4">
        <ul class="pagination justify-content-center">
          <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
              <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
            </li>
          <?php endfor; ?>
        </ul>
      </nav>
    <?php endif; ?>

    <!-- Action Buttons -->
    <div class="action-buttons">
      <a href="insertpost.php" class="btn btn-success">Insert Post</a>
      <a href="displaypost.php" class="btn btn-info text-white">Display Posts</a>
      <a href="updatepost.php" class="btn btn-warning text-dark">Update Post</a>
      <a href="deletepost.php" class="btn btn-danger">Delete Post</a>
    </div>

    <a href="logout.php" class="btn btn-danger logout-btn">Logout</a>
  </div>
</body>
</html>

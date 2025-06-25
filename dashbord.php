<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$search = trim($_GET['search'] ?? '');
$limit = 5;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Escape search input
$search_safe = mysqli_real_escape_string($conn, $search);
$search_condition = $search ? " AND (title LIKE '%$search_safe%' OR content LIKE '%$search_safe%')" : "";

// Total posts for pagination
$count_sql = "SELECT COUNT(*) as total FROM posts WHERE id = $user_id $search_condition";
$total_result = mysqli_query($conn, $count_sql);
$total_posts = mysqli_fetch_assoc($total_result)['total'];
$total_pages = ceil($total_posts / $limit);

// Fetch paginated posts
$sql = "SELECT * FROM posts WHERE id = $user_id $search_condition ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .container { max-width: 800px; margin-top: 40px; }
    .post { background: #fff; padding: 20px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 0 8px rgba(0,0,0,0.1); }
    .pagination a, .pagination strong { margin: 0 5px; text-decoration: none; }
  </style>
</head>
<body>
<div class="container">
  <h2 class="mb-4">Welcome, <?= htmlspecialchars($_SESSION['user_name']) ?>!</h2>
  <form class="d-flex mb-4" method="GET" action="dashboard.php">
    <input class="form-control me-2" type="search" name="search" placeholder="Search posts" value="<?= htmlspecialchars($search) ?>">
    <button class="btn btn-primary" type="submit">Search</button>
  </form>

  <?php while ($row = mysqli_fetch_assoc($result)): ?>
    <div class="post">
      <h4><?= htmlspecialchars($row['title']) ?></h4>
      <p><?= nl2br(htmlspecialchars($row['content'])) ?></p>
      <small class="text-muted">Posted on <?= $row['created_at'] ?></small>
    </div>
  <?php endwhile; ?>

  <nav class="pagination">
    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
      <?= $i == $page ? "<strong>$i</strong>" : "<a href='?page=$i&search=" . urlencode($search) . "'>$i</a>" ?>
    <?php endfor; ?>
  </nav>

  <a href="logout.php" class="btn btn-danger mt-3">Logout</a>
</div>
</body>
</html>
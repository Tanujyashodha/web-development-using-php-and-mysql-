<?php
/************************************************************
 *  index.php – Blog home with search + pagination
 ************************************************************/
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require 'db.php';                 // ① DB connection ($conn)

/* ---------- CONFIG ---------- */
$limit = 5;                       // posts per page

/* ---------- INPUT ---------- */
$search = trim($_GET['search'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

/* ---------- HELPER: bind by ref ---------- */
function bindParams(mysqli_stmt $stmt, string $types, array $params): void {
    /* mysqli requires params passed by reference */
    $refs = [];
    foreach ($params as $k => $v) $refs[$k] = &$params[$k];
    array_unshift($refs, $types);
    call_user_func_array([$stmt, 'bind_param'], $refs);
}

/* ---------- BUILD SEARCH CLAUSE ---------- */
$searchClause = '';
$params       = [];
$types        = '';

if ($search !== '') {
    $searchClause = 'WHERE title LIKE ? OR content LIKE ?';
    $like = "%{$search}%";
    $params = [$like, $like];
    $types  = 'ss';
}

/* ---------- COUNT POSTS ---------- */
$count_sql = "SELECT COUNT(*) AS total FROM posts $searchClause";
if (!$count_stmt = $conn->prepare($count_sql)) die("Count prep error: {$conn->error}");

if ($search !== '') bindParams($count_stmt, $types, $params);
$count_stmt->execute();
$total_posts = $count_stmt->get_result()->fetch_assoc()['total'] ?? 0;
$total_pages = max(1, (int)ceil($total_posts / $limit));

/* ---------- GET PAGINATED POSTS ---------- */
$list_sql = "
  SELECT id, title, content, created_at
  FROM posts
  $searchClause
  ORDER BY created_at DESC
  LIMIT ? OFFSET ?
";
$list_stmt = $conn->prepare($list_sql) or die("List prep error: {$conn->error}");

/* add limit & offset params */
$params_list = $params;
$types_list  = $types . 'ii';
$params_list[] = $limit;
$params_list[] = $offset;

bindParams($list_stmt, $types_list, $params_list);
$list_stmt->execute();
$result = $list_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Blog</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
/* ========== GLOBAL ========== */
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:"Segoe UI",Tahoma,sans-serif;background:#f4f6f8;color:#111827}
a{text-decoration:none}
header{background:#4f46e5;color:#fff;text-align:center;padding:24px 12px}
header h1{font-size:28px;margin-bottom:6px}
.nav-links a{color:#fff;margin:0 10px;font-weight:600}
.container{max-width:800px;margin:32px auto;padding:0 20px}
/* ========== SEARCH ========== */
.search-form{display:flex;gap:10px;margin-bottom:32px}
.search-form input{flex:1;padding:12px;border:1px solid #ccc;border-radius:8px}
.search-form button{padding:12px 20px;background:#4f46e5;border:none;border-radius:8px;color:#fff;font-weight:700;cursor:pointer}
.search-form button:hover{background:#4338ca}
/* ========== POST CARD ========== */
.post{background:#fff;padding:24px;border-radius:12px;margin-bottom:24px;box-shadow:0 4px 12px rgba(0,0,0,.05);transition:transform .15s}
.post:hover{transform:translateY(-3px)}
.post h2{margin-bottom:6px;font-size:22px;color:#1f2937}
.post small{color:#6b7280;display:block;margin-bottom:10px}
.post p{line-height:1.6;color:#374151}
.read-more{display:inline-block;margin-top:10px;color:#4f46e5;font-weight:600}
/* ========== PAGINATION ========== */
.pagination{text-align:center;margin-top:32px}
.pagination a,.pagination span{display:inline-block;margin:0 4px;padding:8px 12px;border-radius:6px;background:#e5e7eb;color:#1f2937;font-weight:500}
.pagination a:hover{background:#d1d5db}
.pagination .current{background:#4f46e5;color:#fff}
/* ========== FOOTER ========== */
footer{text-align:center;padding:32px 0;color:#9ca3af;font-size:14px}
/* ========== MOBILE ========== */
@media(max-width:600px){
  .search-form{flex-direction:column}
  .search-form button{width:100%}
}
</style>
</head>
<body>

<header>
  <h1>📰 My Blog</h1>
  <div class="nav-links">
    <?php if (isset($_SESSION['user_id'])): ?>
      <a href="dashboard.php">Dashboard</a>
      <a href="logout.php">Logout</a>
    <?php else: ?>
      <a href="login.php">Sign In</a>
    <?php endif; ?>
  </div>
</header>

<main class="container">

  <!-- Search Box -->
  <form class="search-form" method="GET">
    <input type="text" name="search" placeholder="Search posts..." value="<?= htmlspecialchars($search) ?>">
    <button type="submit">Search</button>
  </form>

  <!-- Posts -->
  <?php if ($result->num_rows): ?>
    <?php while ($row = $result->fetch_assoc()): ?>
      <article class="post">
        <h2><?= htmlspecialchars($row['title']) ?></h2>
        <small><?= date("F j, Y", strtotime($row['created_at'])) ?></small>
        <p><?= htmlspecialchars(substr($row['content'], 0, 150)) ?>...</p>
        <a class="read-more" href="view_post.php?id=<?= $row['id'] ?>">Read More →</a>
      </article>
    <?php endwhile; ?>
  <?php else: ?>
    <p>No posts found.</p>
  <?php endif; ?>

  <!-- Pagination -->
  <?php if ($total_pages > 1): ?>
    <nav class="pagination">
      <?php for ($i=1;$i<=$total_pages;$i++): ?>
        <?php if ($i === $page): ?>
          <span class="current"><?= $i ?></span>
        <?php else: ?>
          <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
        <?php endif; ?>
      <?php endfor; ?>
    </nav>
  <?php endif; ?>

</main>

<footer>&copy; <?= date('Y') ?> My Blog. All rights reserved.</footer>
</body>
</html>
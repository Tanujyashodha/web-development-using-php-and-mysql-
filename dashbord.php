
<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = intval($_SESSION['user_id']);
$user_name = $_SESSION['user_name'];

$search = trim($_GET['search'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 5;
$offset = ($page - 1) * $limit;

$search_condition = '';
$params = [$user_id];
$types = 'i';

if ($search) {
    $search_condition = "AND (title LIKE ? OR content LIKE ?)";
    $search_param = '%' . $search . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ss';
}

$count_sql = "SELECT COUNT(*) as total FROM posts WHERE user_id = ? $search_condition";
$count_stmt = mysqli_prepare($conn, $count_sql);
mysqli_stmt_bind_param($count_stmt, $types, ...$params);
mysqli_stmt_execute($count_stmt);
$total_result = mysqli_stmt_get_result($count_stmt);
$total_posts = mysqli_fetch_assoc($total_result)['total'];
$total_pages = ceil($total_posts / $limit);

$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$sql = "SELECT id, title, content, created_at FROM posts WHERE user_id = ? $search_condition ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= htmlspecialchars($user_name) ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-bg: #0a0a0a;
            --secondary-bg: #1a1a1a;
            --accent-bg: #2a2a2a;
            --hover-bg: #3a3a3a;
            --primary-text: #ffffff;
            --secondary-text: #b0b0b0;
            --accent-text: #888888;
            --highlight: #00d4ff;
            --highlight-hover: #00b8e6;
            --gradient-1: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-2: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --gradient-3: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            --border-radius: 16px;
            --border-radius-lg: 24px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--primary-bg);
            color: var(--primary-text);
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* Animated background */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(600px circle at 20% 30%, rgba(102, 126, 234, 0.1) 0%, transparent 50%),
                radial-gradient(800px circle at 80% 70%, rgba(118, 75, 162, 0.1) 0%, transparent 50%),
                radial-gradient(600px circle at 40% 80%, rgba(75, 172, 254, 0.1) 0%, transparent 50%);
            z-index: -1;
            animation: bgMove 20s ease-in-out infinite;
        }

        @keyframes bgMove {
            0%, 100% { transform: translateX(0) translateY(0); }
            50% { transform: translateX(10px) translateY(-10px); }
        }

        /* Header */
        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: rgba(26, 26, 26, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            z-index: 1000;
            padding: 1rem 0;
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .logo-icon {
            width: 40px;
            height: 40px;
            background: var(--gradient-1);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: white;
        }

        .logo-text {
            font-size: 1.5rem;
            font-weight: 700;
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .action-btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: var(--transition);
            font-size: 0.9rem;
        }

        .btn-primary {
            background: var(--gradient-1);
            color: white;
        }

        .btn-secondary {
            background: var(--secondary-bg);
            color: var(--primary-text);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .btn-danger {
            background: var(--gradient-2);
            color: white;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        /* Main Content */
        .main-content {
            margin-top: 100px;
            padding: 2rem;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }

        .welcome-section {
            text-align: center;
            margin-bottom: 3rem;
        }

        .welcome-title {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 1rem;
            background: var(--gradient-3);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stats-container {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--secondary-bg);
            padding: 1.5rem 2rem;
            border-radius: var(--border-radius);
            border: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            min-width: 120px;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--highlight);
        }

        .stat-label {
            color: var(--secondary-text);
            font-size: 0.9rem;
        }

        /* Search Section */
        .search-section {
            margin-bottom: 3rem;
        }

        .search-container {
            position: relative;
            max-width: 600px;
            margin: 0 auto;
        }

        .search-input {
            width: 100%;
            padding: 1rem 1.5rem 1rem 3rem;
            background: var(--secondary-bg);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: var(--border-radius-lg);
            color: var(--primary-text);
            font-size: 1rem;
            transition: var(--transition);
        }

        .search-input:focus {
            outline: none;
            border-color: var(--highlight);
            box-shadow: 0 0 0 4px rgba(0, 212, 255, 0.1);
        }

        .search-input::placeholder {
            color: var(--accent-text);
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--accent-text);
            font-size: 1.1rem;
        }

        .search-btn {
            position: absolute;
            right: 0.5rem;
            top: 50%;
            transform: translateY(-50%);
            padding: 0.5rem 1rem;
            background: var(--highlight);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: var(--transition);
        }

        .search-btn:hover {
            background: var(--highlight-hover);
        }

        /* Posts Grid */
        .posts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .post-card {
            background: var(--secondary-bg);
            border-radius: var(--border-radius-lg);
            border: 1px solid rgba(255, 255, 255, 0.1);
            overflow: hidden;
            transition: var(--transition);
            cursor: pointer;
            position: relative;
        }

        .post-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow);
            border-color: var(--highlight);
        }

        .post-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-1);
        }

        .post-header {
            padding: 1.5rem 1.5rem 1rem;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .post-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--primary-text);
            margin-bottom: 0.5rem;
            line-height: 1.4;
        }

        .post-menu {
            background: none;
            border: none;
            color: var(--secondary-text);
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 8px;
            transition: var(--transition);
            position: relative;
        }

        .post-menu:hover {
            background: var(--hover-bg);
            color: var(--primary-text);
        }

        .post-content {
            padding: 0 1.5rem;
            color: var(--secondary-text);
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        .post-footer {
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.02);
        }

        .post-date {
            color: var(--accent-text);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .post-actions {
            display: flex;
            gap: 0.5rem;
        }

        .post-action {
            padding: 0.5rem;
            background: none;
            border: none;
            color: var(--secondary-text);
            cursor: pointer;
            border-radius: 8px;
            transition: var(--transition);
        }

        .post-action:hover {
            background: var(--hover-bg);
            color: var(--primary-text);
        }

        .post-action.delete:hover {
            background: rgba(245, 87, 108, 0.1);
            color: #f5576c;
        }

        /* Dropdown */
        .dropdown {
            position: relative;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            background: var(--accent-bg);
            min-width: 140px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            border: 1px solid rgba(255, 255, 255, 0.1);
            z-index: 100;
            overflow: hidden;
        }

        .dropdown-content a {
            display: block;
            padding: 0.75rem 1rem;
            color: var(--primary-text);
            text-decoration: none;
            transition: var(--transition);
        }

        .dropdown-content a:hover {
            background: var(--hover-bg);
        }

        .dropdown:hover .dropdown-content {
            display: block;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 3rem;
        }

        .page-btn {
            padding: 0.75rem 1rem;
            background: var(--secondary-bg);
            color: var(--primary-text);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: var(--border-radius);
            text-decoration: none;
            transition: var(--transition);
            min-width: 44px;
            text-align: center;
        }

        .page-btn:hover,
        .page-btn.active {
            background: var(--highlight);
            color: white;
            border-color: var(--highlight);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--secondary-text);
        }

        .empty-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: var(--accent-text);
        }

        .empty-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--primary-text);
        }

        .empty-description {
            margin-bottom: 2rem;
        }

        .empty-action {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem 2rem;
            background: var(--gradient-1);
            color: white;
            text-decoration: none;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }

        .empty-action:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(4px);
            z-index: 2000;
            animation: fadeIn 0.3s ease-out;
        }

        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: var(--secondary-bg);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: var(--border-radius-lg);
            max-width: 700px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            animation: slideIn 0.3s ease-out;
        }

        .modal-header {
            padding: 2rem 2rem 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary-text);
            margin-bottom: 0.5rem;
        }

        .modal-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: none;
            border: none;
            color: var(--secondary-text);
            cursor: pointer;
            font-size: 1.5rem;
            padding: 0.5rem;
            border-radius: 8px;
            transition: var(--transition);
        }

        .modal-close:hover {
            background: var(--hover-bg);
            color: var(--primary-text);
        }

        .modal-body {
            padding: 2rem;
        }

        .modal-content-text {
            color: var(--secondary-text);
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        .modal-meta {
            color: var(--accent-text);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from { transform: translate(-50%, -60%); opacity: 0; }
            to { transform: translate(-50%, -50%); opacity: 1; }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .header-content {
                padding: 0 1rem;
            }
            
            .main-content {
                padding: 1rem;
            }
            
            .welcome-title {
                font-size: 2rem;
            }
            
            .stats-container {
                flex-direction: column;
                align-items: center;
            }
            
            .posts-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .header-actions {
                flex-wrap: wrap;
                gap: 0.5rem;
            }
            
            .action-btn {
                padding: 0.5rem 1rem;
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <div class="logo-icon">
                    <i class="fas fa-feather-alt"></i>
                </div>
                <div class="logo-text">BlogSpace</div>
            </div>
            
            <div class="header-actions">
                <a href="insertpost.php" class="action-btn btn-primary">
                    <i class="fas fa-plus"></i>
                    New Post
                </a>
                <a href="displaypost.php" class="action-btn btn-secondary">
                    <i class="fas fa-th-large"></i>
                    View All
                </a>
                <form method="POST" action="logout.php" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <button type="submit" class="action-btn btn-danger" onclick="return confirm('Are you sure you want to logout?')">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </header>

    <main class="main-content">
        <section class="welcome-section">
            <h1 class="welcome-title">Welcome back, <?= htmlspecialchars($user_name) ?>!</h1>
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-number"><?= $total_posts ?></div>
                    <div class="stat-label">Total Posts</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $total_pages ?></div>
                    <div class="stat-label">Pages</div>
                </div>
            </div>
        </section>

        <section class="search-section">
            <form method="GET" class="search-container">
                <i class="fas fa-search search-icon"></i>
                <input type="text" name="search" class="search-input" placeholder="Search your posts..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="search-btn">
                    <i class="fas fa-arrow-right"></i>
                </button>
            </form>
        </section>

        <?php if ($result && mysqli_num_rows($result) > 0): ?>
            <section class="posts-grid">
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <article class="post-card" onclick="openPost(<?= $row['id'] ?>, '<?= htmlspecialchars($row['title'], ENT_QUOTES) ?>', '<?= htmlspecialchars($row['content'], ENT_QUOTES) ?>', '<?= $row['created_at'] ?>')">
                        <div class="post-header">
                            <h3 class="post-title"><?= htmlspecialchars($row['title']) ?></h3>
                            <div class="dropdown">
                                <button class="post-menu" onclick="event.stopPropagation()">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <div class="dropdown-content">
                                    <a href="updatepost.php?id=<?= $row['id'] ?>">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="deletepost.php?id=<?= $row['id'] ?>" onclick="return confirm('Are you sure you want to delete this post?')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="post-content">
                            <?= nl2br(htmlspecialchars(substr($row['content'], 0, 150))) ?><?= strlen($row['content']) > 150 ? '...' : '' ?>
                        </div>
                        <div class="post-footer">
                            <div class="post-date">
                                <i class="fas fa-calendar-alt"></i>
                                <?= date('M j, Y', strtotime($row['created_at'])) ?>
                            </div>
                            <div class="post-actions">
                                <button class="post-action" onclick="event.stopPropagation(); window.location.href='updatepost.php?id=<?= $row['id'] ?>'">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="post-action delete" onclick="event.stopPropagation(); if(confirm('Delete this post?')) window.location.href='deletepost.php?id=<?= $row['id'] ?>'">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </article>
                <?php endwhile; ?>
            </section>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <h3 class="empty-title">No posts found</h3>
                <p class="empty-description">
                    <?= $search ? "No posts match your search. Try different keywords or " : "You haven't created any posts yet. " ?>
                </p>
                <a href="insertpost.php" class="empty-action">
                    <i class="fas fa-plus"></i>
                    Create Your First Post
                </a>
            </div>
        <?php endif; ?>

        <?php if ($total_pages > 1): ?>
            <nav class="pagination">
                <?php if ($page > 1): ?>
                    <a class="page-btn" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <a class="page-btn <?= $i === $page ? 'active' : '' ?>" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a class="page-btn" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    </main>

    <div id="postModal" class="modal" onclick="if(event.target===this) closePost()">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="postTitle"></h2>
                <button class="modal-close" onclick="closePost()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="modal-content-text" id="postContent"></div>
                <div class="modal-meta" id="postMeta">
                    <i class="fas fa-clock"></i>
                    <span id="postDate"></span>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openPost(id, title, content, date) {
            document.getElementById('postTitle').textContent = title;
            document.getElementById('postContent').innerHTML = content.replace(/\n/g, '<br>');
            document.getElementById('postDate').textContent = new Date(date).toLocaleDateString('en-US', {
                year: 'numeric', 
                month: 'long', 
                day: 'numeric', 
                hour: 'numeric', 
                minute: '2-digit', 
                hour12: true
            });
            document.getElementById('postModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
        
        function closePost() {
            document.getElementById('postModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        
        // Enhanced keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closePost();
            }
        });

        // Smooth scroll for pagination
        document.querySelectorAll('.page-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                window.location.href = this.href;
                setTimeout(() => {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }, 100);
            });
        });

        // Auto-focus search when pressing '/'
        document.addEventListener('keydown', function(e) {
            if (e.key === '/' && e.target.tagName !== 'INPUT' && e.target.tagName !== 'TEXTAREA') {
                e.preventDefault();
                document.querySelector('.search-input').focus();
            }
        });
    </script>
</body>
</html>

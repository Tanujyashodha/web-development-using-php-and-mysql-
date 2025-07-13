<?php
/************************************************************
 *  index.php – Blog home with search + pagination
 ************************************************************/
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require 'db.php';                 // ① DB connection ($conn)

/* ---------- CONFIG ---------- */
$limit = 6;                       // posts per page

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nexus Blog - Discover Amazing Stories</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #ec4899;
            --accent: #10b981;
            --dark: #111827;
            --dark-light: #1f2937;
            --gray: #6b7280;
            --gray-light: #9ca3af;
            --gray-lighter: #f3f4f6;
            --white: #ffffff;
            --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-secondary: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --gradient-accent: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --gradient-dark: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --border-radius: 12px;
            --border-radius-lg: 20px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: var(--dark);
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* Animated background particles */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }

        .particle {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }

        .particle:nth-child(1) { width: 4px; height: 4px; left: 10%; animation-delay: 0s; }
        .particle:nth-child(2) { width: 6px; height: 6px; left: 20%; animation-delay: 1s; }
        .particle:nth-child(3) { width: 3px; height: 3px; left: 30%; animation-delay: 2s; }
        .particle:nth-child(4) { width: 5px; height: 5px; left: 40%; animation-delay: 3s; }
        .particle:nth-child(5) { width: 4px; height: 4px; left: 50%; animation-delay: 4s; }
        .particle:nth-child(6) { width: 6px; height: 6px; left: 60%; animation-delay: 5s; }
        .particle:nth-child(7) { width: 3px; height: 3px; left: 70%; animation-delay: 6s; }
        .particle:nth-child(8) { width: 5px; height: 5px; left: 80%; animation-delay: 7s; }
        .particle:nth-child(9) { width: 4px; height: 4px; left: 90%; animation-delay: 8s; }

        @keyframes float {
            0%, 100% { transform: translateY(100vh) rotate(0deg); opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { transform: translateY(-100px) rotate(360deg); opacity: 0; }
        }

        /* Header */
        header {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            position: sticky;
            top: 0;
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
            width: 50px;
            height: 50px;
            background: var(--gradient-secondary);
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--white);
            box-shadow: var(--shadow-lg);
        }

        .logo-text {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--white);
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .nav-links {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .nav-links a {
            color: var(--white);
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            border-radius: var(--border-radius);
            transition: var(--transition);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-links a:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        /* Hero Section */
        .hero {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--white);
            position: relative;
            z-index: 2;
        }

        .hero-title {
            font-size: 4rem;
            font-weight: 900;
            margin-bottom: 1rem;
            text-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
            animation: slideInUp 1s ease-out;
        }

        .hero-subtitle {
            font-size: 1.25rem;
            margin-bottom: 2rem;
            opacity: 0.9;
            animation: slideInUp 1s ease-out 0.2s both;
        }

        @keyframes slideInUp {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* Main Content */
        .main-content {
            background: var(--white);
            position: relative;
            z-index: 2;
            margin-top: -2rem;
            border-radius: var(--border-radius-lg) var(--border-radius-lg) 0 0;
            box-shadow: var(--shadow-xl);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 3rem 2rem;
        }

        /* Search Section */
        .search-section {
            margin-bottom: 3rem;
        }

        .search-form {
            position: relative;
            max-width: 600px;
            margin: 0 auto;
        }

        .search-input {
            width: 100%;
            padding: 1rem 1.5rem 1rem 3.5rem;
            border: 2px solid var(--gray-lighter);
            border-radius: var(--border-radius-lg);
            font-size: 1rem;
            transition: var(--transition);
            background: var(--white);
            box-shadow: var(--shadow-sm);
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
            font-size: 1.1rem;
        }

        .search-btn {
            position: absolute;
            right: 0.5rem;
            top: 50%;
            transform: translateY(-50%);
            padding: 0.5rem 1.5rem;
            background: var(--gradient-primary);
            color: var(--white);
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: var(--transition);
            font-weight: 600;
        }

        .search-btn:hover {
            transform: translateY(-50%) scale(1.05);
        }

        /* Posts Grid */
        .posts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .post-card {
            background: var(--white);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-md);
            overflow: hidden;
            transition: var(--transition);
            position: relative;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .post-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-xl);
        }

        .post-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-primary);
        }

        .post-header {
            padding: 2rem 2rem 1rem;
        }

        .post-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 0.75rem;
            line-height: 1.3;
        }

        .post-meta {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: var(--gray);
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .post-date {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .post-content {
            padding: 0 2rem;
            color: var(--gray);
            line-height: 1.7;
            margin-bottom: 1.5rem;
        }

        .post-footer {
            padding: 1.5rem 2rem;
            border-top: 1px solid var(--gray-lighter);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(0, 0, 0, 0.02);
        }

        .read-more {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
        }

        .read-more:hover {
            color: var(--primary-dark);
            transform: translateX(4px);
        }

        .post-stats {
            display: flex;
            gap: 1rem;
            color: var(--gray-light);
            font-size: 0.9rem;
        }

        .post-stat {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--gray);
        }

        .empty-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: var(--gray-light);
        }

        .empty-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--dark);
        }

        .empty-description {
            margin-bottom: 2rem;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin-top: 3rem;
        }

        .page-btn {
            padding: 0.75rem 1rem;
            border: 2px solid var(--gray-lighter);
            border-radius: var(--border-radius);
            background: var(--white);
            color: var(--dark);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
            min-width: 44px;
            text-align: center;
        }

        .page-btn:hover {
            border-color: var(--primary);
            background: var(--primary);
            color: var(--white);
            transform: translateY(-2px);
        }

        .page-btn.current {
            background: var(--gradient-primary);
            color: var(--white);
            border-color: var(--primary);
        }

        /* Stats Bar */
        .stats-bar {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-bottom: 3rem;
            padding: 1.5rem;
            background: var(--gray-lighter);
            border-radius: var(--border-radius-lg);
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
        }

        .stat-label {
            color: var(--gray);
            font-size: 0.9rem;
        }

        /* Footer */
        footer {
            background: var(--dark);
            color: var(--white);
            padding: 3rem 2rem 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: var(--gradient-primary);
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
        }

        .footer-text {
            opacity: 0.8;
            margin-bottom: 1rem;
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .footer-links a {
            color: var(--white);
            text-decoration: none;
            opacity: 0.8;
            transition: var(--transition);
        }

        .footer-links a:hover {
            opacity: 1;
            color: var(--primary);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }

            .hero-title {
                font-size: 2.5rem;
            }

            .posts-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .stats-bar {
                flex-direction: column;
                gap: 1rem;
            }

            .pagination {
                flex-wrap: wrap;
            }

            .container {
                padding: 2rem 1rem;
            }
        }

        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: var(--white);
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="particles">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>

    <header>
        <div class="header-content">
            <div class="logo">
                <div class="logo-icon">
                    <i class="fas fa-rocket"></i>
                </div>
                <div class="logo-text">Nexus Blog</div>
            </div>
            
            <nav class="nav-links">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="dashboard.php">
                        <i class="fas fa-tachometer-alt"></i>
                        Dashboard
                    </a>
                    <a href="logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                <?php else: ?>
                    <a href="login.php">
                        <i class="fas fa-sign-in-alt"></i>
                        Sign In
                    </a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <section class="hero">
        <h1 class="hero-title">Discover Amazing Stories</h1>
        <p class="hero-subtitle">Explore a world of knowledge, inspiration, and creativity</p>
    </section>

    <main class="main-content">
        <div class="container">
            <div class="stats-bar">
                <div class="stat-item">
                    <div class="stat-number"><?= $total_posts ?></div>
                    <div class="stat-label">Total Posts</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= $total_pages ?></div>
                    <div class="stat-label">Pages</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= $page ?></div>
                    <div class="stat-label">Current Page</div>
                </div>
            </div>

            <section class="search-section">
                <form class="search-form" method="GET">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" name="search" class="search-input" placeholder="Search for amazing stories..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="search-btn">
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </form>
            </section>

            <?php if ($result->num_rows): ?>
                <div class="posts-grid">
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <article class="post-card">
                            <div class="post-header">
                                <h2 class="post-title"><?= htmlspecialchars($row['title']) ?></h2>
                                <div class="post-meta">
                                    <div class="post-date">
                                        <i class="fas fa-calendar-alt"></i>
                                        <?= date("F j, Y", strtotime($row['created_at'])) ?>
                                    </div>
                                </div>
                            </div>
                            <div class="post-content">
                                <p><?= htmlspecialchars(substr($row['content'], 0, 200)) ?><?= strlen($row['content']) > 200 ? '...' : '' ?></p>
                            </div>
                            <div class="post-footer">
                                <a class="read-more" href="view_post.php?id=<?= $row['id'] ?>">
                                    Read More
                                    <i class="fas fa-arrow-right"></i>
                                </a>
                                <div class="post-stats">
                                    <div class="post-stat">
                                        <i class="fas fa-eye"></i>
                                        <?= rand(10, 500) ?>
                                    </div>
                                    <div class="post-stat">
                                        <i class="fas fa-heart"></i>
                                        <?= rand(1, 50) ?>
                                    </div>
                                </div>
                            </div>
                        </article>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3 class="empty-title">No posts found</h3>
                    <p class="empty-description">
                        <?= $search ? "We couldn't find any posts matching your search. Try different keywords or browse all posts." : "No posts have been published yet. Check back later for amazing content!" ?>
                    </p>
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
                        <?php if ($i === $page): ?>
                            <span class="page-btn current"><?= $i ?></span>
                        <?php else: ?>
                            <a class="page-btn" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a class="page-btn" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <div class="footer-content">
            <div class="footer-links">
                <a href="#"><i class="fas fa-home"></i> Home</a>
                <a href="#"><i class="fas fa-info-circle"></i> About</a>
                <a href="#"><i class="fas fa-envelope"></i> Contact</a>
                <a href="#"><i class="fas fa-shield-alt"></i> Privacy</a>
            </div>
            <p class="footer-text">&copy; <?= date('Y') ?> Nexus Blog. Crafted with <i class="fas fa-heart" style="color: #ec4899;"></i> for amazing stories.</p>
        </div>
    </footer>

    <script>
        // Smooth scrolling for internal links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Search form enhancement
        const searchForm = document.querySelector('.search-form');
        const searchInput = document.querySelector('.search-input');
        const searchBtn = document.querySelector('.search-btn');

        searchForm.addEventListener('submit', function(e) {
            if (searchInput.value.trim() === '') {
                e.preventDefault();
                searchInput.focus();
                return;
            }
            
            searchBtn.innerHTML = '<div class="loading"></div>';
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.key === '/' && e.target.tagName !== 'INPUT') {
                e.preventDefault();
                searchInput.focus();
            }
        });

        // Add entrance animations to post cards
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.animation = 'slideInUp 0.6s ease-out forwards';
                }
            });
        }, observerOptions);

        document.querySelectorAll('.post-card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            observer.observe(card);
        });

        // Add CSS for animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideInUp {
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>

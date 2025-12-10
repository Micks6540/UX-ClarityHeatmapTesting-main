<?php
session_start();

// Include database or content loader
include __DIR__ . '/../includes/db.php'; // $data comes from db.php

// Redirect if not logged in
if (!isset($_SESSION['user'])) {
    header("Location: /api/login.php"); 
    exit;
}

// Cart count for header badge
$cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;

// Load books data
$books = $data['books'] ?? [];

// Split into popular and references
$popular = array_slice($books, 0, 8);
$refs    = array_slice($books, 8, 8);

// Load genres
$genres = $data['genres'] ?? ['Fiction','English','Biology','Encyclopedia','Math & Statistics','Biography','Chemistry','History','Law & Politics'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Librow - Homepage</title>
<link rel="stylesheet" href="/style.css">
<style>
.book-item { cursor: pointer; transition: transform 0.2s; position: relative; }
.book-item:hover { transform: translateY(-5px); }
.book-link { text-decoration: none; color: inherit; display: block; }
</style>
</head>
<body>

<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<header class="header">
    <div class="header-left">
        <div class="logo-container">
            <img src="/img/logo.png" alt="Lib'Row" class="logo" onerror="this.style.display='none'">
            <h1 class="site-title">Lib'Row</h1>
        </div>
    </div>
    <div class="header-right">
        <div class="header-icons">
            <span class="icon" title="Notifications">🔔<span class="badge notification-badge">2</span></span>
            <span class="icon" title="Cart" onclick="window.location.href='/api/cart.php'">🛒<span class="badge cart-badge"><?php echo $cart_count; ?></span></span>
        </div>
        <div class="menu-icon" onclick="toggleSidebar()" title="Menu"><div class="menu-icon-inner">☰</div></div>
    </div>
</header>

<main style="padding-bottom:60px;">
    <!-- SEARCH -->
    <div class="search-box">
        <form method="GET" action="/api/index.php">
            <input type="search" name="q" placeholder="Search" value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
        </form>
    </div>

    <!-- FILTER BUTTONS -->
    <div class="filter-row">
        <button class="filter-btn">Sort by ▼</button>
        <button class="filter-btn">Books</button>
        <button class="filter-btn">Tools & Equipment</button>
        <button class="filter-btn">Medicine</button>
    </div>

    <!-- POPULAR BOOKS -->
    <h3 class="section-title">Popular books</h3>
    <div class="horizontal-scroll">
        <?php
        if (empty($popular)) {
            $popular = [
                ['book_id'=>1,'title'=>'The Hobbit','author'=>'J.R.R. Tolkien','cover'=>'/img/hobbit.jpg'],
                ['book_id'=>2,'title'=>'Catching Fire','author'=>'Suzanne Collins','cover'=>'/img/catchingfire.jpg'],
            ];
        }
        foreach ($popular as $b):
            $cover = $b['cover'] ?? '/img/placeholder.png';
            $book_id = $b['book_id'] ?? 0;
        ?>
            <div class="book-item" onclick="window.location.href='/api/book_details.php?id=<?php echo $book_id; ?>'">
                <img src="<?php echo htmlspecialchars($cover); ?>" alt="<?php echo htmlspecialchars($b['title']); ?>" onerror="this.onerror=null;this.src='/img/placeholder.png';">
                <p title="<?php echo htmlspecialchars($b['title']); ?>"><?php echo htmlspecialchars($b['title']); ?></p>
                <small style="display:block;color:#666;"><?php echo htmlspecialchars($b['author'] ?? ''); ?></small>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- POPULAR GENRES -->
    <h3 class="section-title">Popular genre</h3>
    <div class="genre-grid">
        <?php foreach ($genres as $g): ?>
            <span><?php echo htmlspecialchars($g); ?></span>
        <?php endforeach; ?>
    </div>

    <!-- BOOKS & REFERENCES -->
    <h3 class="section-title">Books & References</h3>
    <div class="horizontal-scroll">
        <?php
        if (empty($refs)) {
            $refs = [
                ['book_id'=>5,'title'=>'Problem Solving in GCSE Mathematics','author'=>'Daniel','cover'=>'/img/gcse.jpg'],
                ['book_id'=>6,'title'=>'Beat Teacher Burnout','author'=>'Grace Stevens','cover'=>'/img/burnout.jpg'],
            ];
        }
        foreach ($refs as $r):
            $cover = $r['cover'] ?? '/img/placeholder.png';
            $book_id = $r['book_id'] ?? 0;
        ?>
            <div class="book-item" onclick="window.location.href='/api/book_details.php?id=<?php echo $book_id; ?>'">
                <img src="<?php echo htmlspecialchars($cover); ?>" alt="<?php echo htmlspecialchars($r['title']); ?>" onerror="this.onerror=null;this.src='/img/placeholder.png';">
                <p><?php echo htmlspecialchars(mb_strimwidth($r['title'],0,24,'...')); ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</main>

<script src="/script.js"></script>
</body>
</html>

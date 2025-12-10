<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$book_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($book_id == 0) {
    header("Location: index.php");
    exit;
}

// Fetch book details
$sql = "SELECT * FROM books WHERE book_id = $book_id";
$result = mysqli_query($conn, $sql);

if (!$result || mysqli_num_rows($result) == 0) {
    header("Location: index.php");
    exit;
}

$book = mysqli_fetch_assoc($result);

// Handle add to cart
if (isset($_POST['add_to_cart'])) {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = array();
    }
    
    if (!in_array($book_id, $_SESSION['cart'])) {
        $_SESSION['cart'][] = $book_id;
        $cart_message = "Added to cart!";
    } else {
        $cart_message = "Already in cart.";
    }
}

// Get cart count for badge
$cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($book['title']); ?> - Lib'Row</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }
        
        body {
            background: #f5f5f5;
        }
        
        /* Header */
        .top-header {
            background: #214d25;
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .back-btn {
            color: white;
            text-decoration: none;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        /* Cart Icon Styles */
        .cart-icon-container {
            position: relative;
            cursor: pointer;
            margin-right: 50px;
        }
        
        .cart-icon {
            font-size: 30px;
            padding: 8px;
            border-radius: 50%;
            transition: background-color 0.2s;
            
        }
        
        .cart-icon:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .cart-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #ff4757;
            color: white;
            font-size: 15px;
            font-weight: 600;
            border-radius: 50%;
            width: 23px;
            height: 23px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #214d25;
        }
        
        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .time {
            font-size: 14px;
            opacity: 0.9;
        }
        
        /* Main Container */
        .book-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .book-content {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 40px;
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        @media (max-width: 768px) {
            .book-content {
                grid-template-columns: 1fr;
            }
        }
        
        /* Book Cover */
        .book-cover {
            text-align: center;
        }
        
        .book-image {
            width: 250px;
            height: 350px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            font-size: 24px;
            margin-bottom: 20px;
        }
        
        .availability-badge {
            display: inline-block;
            padding: 8px 15px;
            background: #4CAF50;
            color: white;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
            margin-top: 10px;
        }
        
        .availability-badge.unavailable {
            background: #f44336;
        }
        
        /* Book Details */
        .book-details {
            padding: 10px 0;
        }
        
        .book-title {
            font-size: 36px;
            color: #214d25;
            margin-bottom: 10px;
            line-height: 1.2;
        }
        
        .book-author {
            font-size: 20px;
            color: #666;
            margin-bottom: 25px;
            font-weight: 500;
        }
        
        .section-title {
            color: #214d25;
            font-size: 18px;
            margin: 25px 0 8px 0;
            font-weight: 600;
        }
        
        .detail-row {
            display: flex;
            margin-bottom: 12px;
        }
        
        .detail-label {
            width: 150px;
            font-weight: 600;
            color: #555;
        }
        
        .detail-value {
            color: #333;
            flex: 1;
        }
        
        .description {
            line-height: 1.6;
            color: #444;
            margin-top: 5px;
        }
        
        /* Action Buttons */
        .action-buttons {
            margin-top: 30px;
            display: flex;
            gap: 15px;
        }
        
        .btn {
            padding: 14px 30px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
        }
        
        .btn-borrow {
            background: #214d25;
            color: white;
            flex: 1;
        }
        
        .btn-borrow:hover {
            background: #2e7d32;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(33, 77, 37, 0.2);
        }
        
        .btn-cart {
            background: #4ecdc4;
            color: white;
            flex: 1;
        }
        
        .btn-cart:hover {
            background: #26a69a;
            transform: translateY(-2px);
        }
        
        .btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none !important;
        }
        
        /* Messages */
        .message {
            padding: 12px 20px;
            border-radius: 6px;
            margin: 15px 0;
            font-weight: 500;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .message.info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        /* Related Books */
        .related-books {
            margin-top: 50px;
        }
        
        .related-title {
            color: #214d25;
            font-size: 24px;
            margin-bottom: 20px;
        }
        
        .books-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 20px;
        }
        
        .related-book {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.2s;
            text-decoration: none;
            color: inherit;
        }
        
        .related-book:hover {
            transform: translateY(-5px);
        }
        
        .related-book img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        
        .related-book-info {
            padding: 12px;
        }
        
        .related-book-title {
            font-weight: 600;
            margin-bottom: 5px;
            font-size: 14px;
        }
        
        .related-book-author {
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <!-- Top Header -->
    <div class="top-header">
        <a href="index.php" class="back-btn">← Lib'Row</a>
        
        <div class="header-right">
            <div class="cart-icon-container" onclick="window.location.href='cart.php'">
                <span class="cart-icon">🛒</span>
                <?php if ($cart_count > 0): ?>
                    <span class="cart-badge"><?php echo $cart_count; ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="book-container">
        <?php if (isset($success_message)): ?>
            <div class="message success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="message error"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($cart_message)): ?>
            <div class="message info"><?php echo $cart_message; ?></div>
        <?php endif; ?>
        
        <div class="book-content">
            <!-- Left: Book Cover -->
            <div class="book-cover">
                <div class="book-image">
                    <?php
                    $imagePath = "img/books/{$book['book_id']}.jpg";
                    if (file_exists($imagePath)) {
                        echo '<img src="' . htmlspecialchars($imagePath) . '" alt="' . htmlspecialchars($book['title']) . '" style="width:100%;height:100%;object-fit:cover;">';
                    } else {
                        // Display title initials
                        $words = explode(' ', $book['title']);
                        $initials = '';
                        foreach ($words as $word) {
                            if (strlen($word) > 0) {
                                $initials .= strtoupper($word[0]);
                                if (strlen($initials) >= 3) break;
                            }
                        }
                        echo $initials ?: '📚';
                    }
                    ?>
                </div>
                
                <div class="availability-badge <?php echo ($book['available'] > 0) ? '' : 'unavailable'; ?>">
                    <?php echo ($book['available'] > 0) ? 'Available (' . $book['available'] . ' copies)' : 'Out of Stock'; ?>
                </div>
            </div>
            
            <!-- Right: Book Details -->
            <div class="book-details">
                <h1 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h1>
                <div class="book-author"><?php echo htmlspecialchars($book['author']); ?></div>
                
                <div class="section-title">Section name:</div>
                <div class="description"><?php echo htmlspecialchars($book['category'] ?? 'General'); ?></div>
                
                <div class="section-title">Book number:</div>
                <div class="description"><?php echo htmlspecialchars($book['book_id']); ?></div>
                
                <div class="section-title">Language:</div>
                <div class="description"><?php echo htmlspecialchars($book['language'] ?? 'English'); ?></div>
                
                <div class="section-title">Access:</div>
                <div class="description">
                    <?php 
                    if ($book['available'] > 3) {
                        echo 'Available immediately';
                    } elseif ($book['available'] > 0) {
                        echo 'Available 3-4 weeks';
                    } else {
                        echo 'Currently unavailable';
                    }
                    ?>
                </div>
                
                <?php if (!empty($book['description'])): ?>
                    <div class="section-title">Description:</div>
                    <div class="description"><?php echo nl2br(htmlspecialchars($book['description'])); ?></div>
                <?php endif; ?>
                
                <?php if (!empty($book['publisher'])): ?>
                    <div class="section-title">Publisher:</div>
                    <div class="description"><?php echo htmlspecialchars($book['publisher']); ?></div>
                <?php endif; ?>
                
                <?php if (!empty($book['publication_year'])): ?>
                    <div class="section-title">Published:</div>
                    <div class="description"><?php echo htmlspecialchars($book['publication_year']); ?></div>
                <?php endif; ?>
                
                <!-- Action Buttons -->
                <div class="action-buttons">
                    <a href="borrow_confirmation.php?book_id=<?php echo $book_id; ?>" 
                       class="btn btn-borrow" 
                       <?php echo ($book['available'] == 0) ? 'style="pointer-events: none; opacity: 0.6;"' : ''; ?>>
                        BORROW NOW
                    </a>
                    
                    <form method="POST" style="flex:1;">
                        <button type="submit" name="add_to_cart" class="btn btn-cart">
                            ADD TO CART
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Related Books Section -->
        <div class="related-books">
            <h2 class="related-title">Related Books</h2>
            <div class="books-grid">
                <?php
                // Fetch related books by category
                $category = $book['category'] ?? '';
                if ($category) {
                    $related_sql = "SELECT book_id, title, author FROM books 
                                   WHERE category = '$category' AND book_id != $book_id 
                                   ORDER BY RAND() LIMIT 4";
                    $related_result = mysqli_query($conn, $related_sql);
                    
                    if ($related_result && mysqli_num_rows($related_result) > 0) {
                        while ($related = mysqli_fetch_assoc($related_result)) {
                            echo '<a href="book_details.php?id=' . $related['book_id'] . '" class="related-book">';
                            $relImagePath = "img/books/{$related['book_id']}.jpg";
                            if (file_exists($relImagePath)) {
                                echo '<img src="' . htmlspecialchars($relImagePath) . '" alt="' . htmlspecialchars($related['title']) . '">';
                            } else {
                                echo '<div style="width:100%;height:200px;background:#f0f0f0;display:flex;align-items:center;justify-content:center;">📚</div>';
                            }
                            echo '<div class="related-book-info">';
                            echo '<div class="related-book-title">' . htmlspecialchars(mb_strimwidth($related['title'], 0, 30, '...')) . '</div>';
                            echo '<div class="related-book-author">' . htmlspecialchars(mb_strimwidth($related['author'], 0, 25, '...')) . '</div>';
                            echo '</div></a>';
                        }
                    }
                }
                
                // If no related books, show some popular ones
                if (empty($category) || !$related_result || mysqli_num_rows($related_result) == 0) {
                    $popular_sql = "SELECT book_id, title, author FROM books 
                                   WHERE book_id != $book_id 
                                   ORDER BY RAND() LIMIT 4";
                    $popular_result = mysqli_query($conn, $popular_sql);
                    
                    if ($popular_result && mysqli_num_rows($popular_result) > 0) {
                        while ($popular = mysqli_fetch_assoc($popular_result)) {
                            echo '<a href="book_details.php?id=' . $popular['book_id'] . '" class="related-book">';
                            $popImagePath = "img/books/{$popular['book_id']}.jpg";
                            if (file_exists($popImagePath)) {
                                echo '<img src="' . htmlspecialchars($popImagePath) . '" alt="' . htmlspecialchars($popular['title']) . '">';
                            } else {
                                echo '<div style="width:100%;height:200px;background:#f0f0f0;display:flex;align-items:center;justify-content:center;">📚</div>';
                            }
                            echo '<div class="related-book-info">';
                            echo '<div class="related-book-title">' . htmlspecialchars(mb_strimwidth($popular['title'], 0, 30, '...')) . '</div>';
                            echo '<div class="related-book-author">' . htmlspecialchars(mb_strimwidth($popular['author'], 0, 25, '...')) . '</div>';
                            echo '</div></a>';
                        }
                    }
                }
                ?>
            </div>
        </div>
    </div>
    
    <script>
        // Update time every minute
        function updateTime() {
            const now = new Date();
            const timeString = now.getHours().toString().padStart(2, '0') + ':' + 
                              now.getMinutes().toString().padStart(2, '0');
            // Add this if you want to show time
            // document.querySelector('.time').textContent = timeString;
        }
        
        // Initial time update
        updateTime();
        // Update every minute
        setInterval(updateTime, 60000);
        
        // Auto-hide messages after 5 seconds
        setTimeout(function() {
            const messages = document.querySelectorAll('.message');
            messages.forEach(msg => {
                msg.style.opacity = '0';
                msg.style.transition = 'opacity 0.5s';
                setTimeout(() => msg.style.display = 'none', 500);
            });
        }, 5000);
        
        // Update cart badge when adding to cart
        document.addEventListener('DOMContentLoaded', function() {
            const addToCartBtn = document.querySelector('.btn-cart');
            if (addToCartBtn) {
                addToCartBtn.addEventListener('click', function() {
                    // Update cart count immediately (client-side update)
                    setTimeout(() => {
                        location.reload(); // Reload to update cart badge
                    }, 1000);
                });
            }
        });
    </script>
</body>
</html>
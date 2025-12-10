<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// Get cart items from session
$cart_items = isset($_SESSION['cart']) ? $_SESSION['cart'] : array();

// Fetch book details for cart items
$cart_books = array();
$total_items = 0;

if (!empty($cart_items)) {
    $placeholders = implode(',', array_fill(0, count($cart_items), '?'));
    $sql = "SELECT book_id, title, author, available, quantity FROM books WHERE book_id IN ($placeholders)";
    $stmt = $conn->prepare($sql);
    
    // Bind parameters
    $types = str_repeat('i', count($cart_items));
    $stmt->bind_param($types, ...$cart_items);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $cart_books[] = $row;
            $total_items++;
        }
    }
}

// Handle remove from cart
if (isset($_GET['remove']) && is_numeric($_GET['remove'])) {
    $book_id = intval($_GET['remove']);
    if (($key = array_search($book_id, $cart_items)) !== false) {
        unset($cart_items[$key]);
        $_SESSION['cart'] = array_values($cart_items); // Reindex array
    }
    header("Location: cart.php");
    exit;
}

// Handle borrow action - redirect to borrow confirmation
if (isset($_POST['borrow_selected']) && isset($_POST['selected_books'])) {
    $selected_books = $_POST['selected_books'];
    
    // Store selected books in session for borrow_confirmation.php
    $_SESSION['selected_books_for_borrow'] = $selected_books;
    
    // Redirect to borrow confirmation page with first book ID
    if (!empty($selected_books)) {
        $first_book_id = intval($selected_books[0]);
        header("Location: borrow_confirmation.php?book_id=" . $first_book_id . "&multiple=true");
        exit;
    }
}

// Handle select all
if (isset($_POST['select_all'])) {
    $all_book_ids = array_column($cart_books, 'book_id');
    $_SESSION['selected_books'] = $all_book_ids;
}

// Get cart count for badge
$cart_count = count($cart_items);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart - Lib'Row</title>
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
        
        .time {
            font-size: 14px;
            opacity: 0.9;
        }
        
        /* Search */
        .search-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
        }
        
        .search-box {
            width: 100%;
            padding: 12px 20px;
            border: 1px solid #ddd;
            border-radius: 25px;
            font-size: 16px;
            background: white;
        }
        
        /* Main Container */
        .cart-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
        }
        
        .cart-header {
            margin-bottom: 20px;
        }
        
        .cart-title {
            color: #214d25;
            font-size: 28px;
            margin-bottom: 5px;
        }
        
        .cart-subtitle {
            color: #666;
            font-size: 16px;
        }
        
        /* Cart Items */
        .cart-items {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .cart-item {
            display: flex;
            align-items: center;
            padding: 20px 0;
            border-bottom: 1px solid #eee;
        }
        
        .cart-item:last-child {
            border-bottom: none;
        }
        
        .item-checkbox {
            margin-right: 15px;
        }
        
        .item-image {
            width: 80px;
            height: 100px;
            object-fit: cover;
            border-radius: 6px;
            margin-right: 20px;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            font-size: 14px;
        }
        
        .item-details {
            flex: 1;
        }
        
        .item-title {
            font-size: 18px;
            color: #214d25;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .item-author {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .item-availability {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .available {
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        .unavailable {
            background: #ffebee;
            color: #c62828;
        }
        
        .item-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .action-btn {
            background: none;
            border: none;
            color: #214d25;
            cursor: pointer;
            font-size: 14px;
            text-align: left;
            padding: 5px 0;
        }
        
        .action-btn:hover {
            text-decoration: underline;
        }
        
        .remove-btn {
            color: #ff4757;
        }
        
        /* Cart Actions */
        .cart-actions {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-top: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .select-all {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .select-all label {
            color: #333;
            font-weight: 600;
        }
        
        .borrow-btn {
            background: #214d25;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .borrow-btn:hover {
            background: #2e7d32;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(33, 77, 37, 0.2);
        }
        
        .borrow-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }
        
        /* Empty Cart */
        .empty-cart {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .empty-icon {
            font-size: 60px;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        .empty-title {
            color: #666;
            font-size: 20px;
            margin-bottom: 10px;
        }
        
        .empty-text {
            color: #999;
            margin-bottom: 20px;
        }
        
        .browse-btn {
            background: #214d25;
            color: white;
            text-decoration: none;
            padding: 10px 25px;
            border-radius: 6px;
            display: inline-block;
            font-weight: 600;
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
        
        /* Unavailable Items */
        .unavailable-section {
            margin-top: 30px;
        }
        
        .section-title {
            color: #214d25;
            font-size: 20px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
        }
        
        @media (max-width: 768px) {
            .cart-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .item-image {
                width: 100%;
                height: 200px;
            }
            
            .cart-actions {
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
            }
            
            .borrow-btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Top Header -->
    <div class="top-header">
        <a href="index.php" class="back-btn">← Lib'Row</a>
        <div class="time"><?php echo date('H:i'); ?></div>
    </div>

    <!-- Search -->
    <div class="search-container">
        <input type="text" class="search-box" placeholder="Search">
    </div>

    <!-- Main Content -->
    <div class="cart-container">
        <?php if (isset($_GET['success'])): ?>
            <div class="message success"><?php echo htmlspecialchars($_GET['success']); ?></div>
        <?php endif; ?>
        
        <div class="cart-header">
            <h1 class="cart-title">Your Cart</h1>
            <p class="cart-subtitle">(<?php echo $total_items; ?> item<?php echo $total_items != 1 ? 's' : ''; ?>)</p>
        </div>
        
        <?php if (empty($cart_books)): ?>
            <!-- Empty Cart -->
            <div class="empty-cart">
                <div class="empty-icon">🛒</div>
                <h2 class="empty-title">Your cart is empty</h2>
                <p class="empty-text">Add some books to get started!</p>
                <a href="index.php" class="browse-btn">Browse Books</a>
            </div>
        <?php else: ?>
            <!-- Cart Items -->
            <div class="cart-items">
                <form id="cartForm" method="POST" action="cart.php">
                    <?php 
                    $available_books = array();
                    $unavailable_books = array();
                    
                    foreach ($cart_books as $book) {
                        if ($book['available'] > 0) {
                            $available_books[] = $book;
                        } else {
                            $unavailable_books[] = $book;
                        }
                    }
                    ?>
                    
                    <!-- Available Items -->
                    <?php if (!empty($available_books)): ?>
                        <?php foreach($available_books as $book): ?>
                            <div class="cart-item">
                                <div class="item-checkbox">
                                    <input type="checkbox" name="selected_books[]" value="<?php echo $book['book_id']; ?>" 
                                           class="book-checkbox" id="book_<?php echo $book['book_id']; ?>">
                                </div>
                                
                                <div class="item-image">
                                    <?php
                                    $imagePath = "img/books/{$book['book_id']}.jpg";
                                    if (file_exists($imagePath)) {
                                        echo '<img src="' . htmlspecialchars($imagePath) . '" alt="' . htmlspecialchars($book['title']) . '" style="width:100%;height:100%;object-fit:cover;">';
                                    } else {
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
                                
                                <div class="item-details">
                                    <h3 class="item-title"><?php echo htmlspecialchars($book['title']); ?></h3>
                                    <p class="item-author"><?php echo htmlspecialchars($book['author']); ?></p>
                                    <span class="item-availability available">
                                        Available (<?php echo $book['available']; ?> copies)
                                    </span>
                                </div>
                                
                                <div class="item-actions">
                                    <a href="book_details.php?id=<?php echo $book['book_id']; ?>" class="action-btn">View Details</a>
                                    <a href="cart.php?remove=<?php echo $book['book_id']; ?>" class="action-btn remove-btn">Remove</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <!-- Unavailable Items -->
                    <?php if (!empty($unavailable_books)): ?>
                        <div class="unavailable-section">
                            <h3 class="section-title">Unavailable item<?php echo count($unavailable_books) != 1 ? 's' : ''; ?> (<?php echo count($unavailable_books); ?>)</h3>
                            
                            <?php foreach($unavailable_books as $book): ?>
                                <div class="cart-item">
                                    <div class="item-checkbox">
                                        <input type="checkbox" disabled>
                                    </div>
                                    
                                    <div class="item-image">
                                        <?php
                                        $imagePath = "img/books/{$book['book_id']}.jpg";
                                        if (file_exists($imagePath)) {
                                            echo '<img src="' . htmlspecialchars($imagePath) . '" alt="' . htmlspecialchars($book['title']) . '" style="width:100%;height:100%;object-fit:cover;">';
                                        } else {
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
                                    
                                    <div class="item-details">
                                        <h3 class="item-title"><?php echo htmlspecialchars($book['title']); ?></h3>
                                        <p class="item-author"><?php echo htmlspecialchars($book['author']); ?></p>
                                        <span class="item-availability unavailable">
                                            Currently Unavailable
                                        </span>
                                    </div>
                                    
                                    <div class="item-actions">
                                        <a href="book_details.php?id=<?php echo $book['book_id']; ?>" class="action-btn">View Details</a>
                                        <a href="cart.php?remove=<?php echo $book['book_id']; ?>" class="action-btn remove-btn">Remove</a>
                                        <button type="button" class="action-btn">Find Similar ></button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
            
            <!-- Cart Actions -->
            <div class="cart-actions">
                <div class="select-all">
                    <input type="checkbox" id="selectAllCheckbox">
                    <label for="selectAllCheckbox">Select All</label>
                </div>
                
                <button type="submit" form="cartForm" name="borrow_selected" class="borrow-btn" id="borrowBtn" disabled>
                    BORROW (<span id="selectedCount">0</span>)
                </button>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Update time
        function updateTime() {
            const now = new Date();
            const timeString = now.getHours().toString().padStart(2, '0') + ':' + 
                              now.getMinutes().toString().padStart(2, '0');
            document.querySelector('.time').textContent = timeString;
        }
        
        updateTime();
        setInterval(updateTime, 60000);
        
        // Handle select all checkbox
        document.addEventListener('DOMContentLoaded', function() {
            const selectAllCheckbox = document.getElementById('selectAllCheckbox');
            const bookCheckboxes = document.querySelectorAll('.book-checkbox');
            const borrowBtn = document.getElementById('borrowBtn');
            const selectedCount = document.getElementById('selectedCount');
            
            // Function to update selected count
            function updateSelectedCount() {
                const checkedBoxes = document.querySelectorAll('.book-checkbox:checked');
                const count = checkedBoxes.length;
                selectedCount.textContent = count;
                
                // Enable/disable borrow button
                borrowBtn.disabled = count === 0;
                borrowBtn.textContent = count > 0 ? `BORROW (${count})` : 'BORROW (0)';
                
                // Update select all checkbox state
                if (bookCheckboxes.length > 0) {
                    const allChecked = Array.from(bookCheckboxes).every(cb => cb.checked);
                    const someChecked = Array.from(bookCheckboxes).some(cb => cb.checked);
                    
                    selectAllCheckbox.checked = allChecked;
                    selectAllCheckbox.indeterminate = someChecked && !allChecked;
                }
            }
            
            // Select all functionality
            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', function() {
                    bookCheckboxes.forEach(cb => {
                        if (!cb.disabled) {
                            cb.checked = this.checked;
                        }
                    });
                    updateSelectedCount();
                });
            }
            
            // Individual checkbox changes
            bookCheckboxes.forEach(cb => {
                cb.addEventListener('change', updateSelectedCount);
            });
            
            // Initial count update
            updateSelectedCount();
            
            // Auto-hide success message
            setTimeout(function() {
                const messages = document.querySelectorAll('.message');
                messages.forEach(msg => {
                    msg.style.opacity = '0';
                    msg.style.transition = 'opacity 0.5s';
                    setTimeout(() => msg.style.display = 'none', 500);
                });
            }, 5000);
            
            // Search functionality
            const searchBox = document.querySelector('.search-box');
            if (searchBox) {
                searchBox.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    const cartItems = document.querySelectorAll('.cart-item');
                    
                    cartItems.forEach(item => {
                        const title = item.querySelector('.item-title').textContent.toLowerCase();
                        const author = item.querySelector('.item-author').textContent.toLowerCase();
                        
                        if (title.includes(searchTerm) || author.includes(searchTerm)) {
                            item.style.display = 'flex';
                        } else {
                            item.style.display = 'none';
                        }
                    });
                });
            }
            
            // Confirm before borrowing
            const cartForm = document.getElementById('cartForm');
            if (cartForm) {
                cartForm.addEventListener('submit', function(e) {
                    const checkedBoxes = document.querySelectorAll('.book-checkbox:checked');
                    if (checkedBoxes.length === 0) {
                        e.preventDefault();
                        alert('Please select at least one book to borrow.');
                        return false;
                    }
                    
                    const availableChecked = Array.from(checkedBoxes).every(cb => {
                        const cartItem = cb.closest('.cart-item');
                        return !cartItem.querySelector('.unavailable');
                    });
                    
                    if (!availableChecked) {
                        e.preventDefault();
                        alert('Some selected books are unavailable. Please uncheck unavailable items.');
                        return false;
                    }
                    
                    return confirm(`Are you sure you want to borrow ${checkedBoxes.length} book(s)?\n\nYou will be redirected to the borrow confirmation page.`);
                });
            }
        });
    </script>
</body>
</html>
<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// Get cart count for badge
$cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;

// Pagination variables
$limit = 10; // Records per page
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// Get total records for pagination
$total_sql = "SELECT COUNT(*) as total FROM borrow";
$total_result = $conn->query($total_sql);
$total_row = $total_result->fetch_assoc();
$total_records = $total_row['total'];
$total_pages = ceil($total_records / $limit);

// Fetch all borrow records
$sql = "SELECT b.*, bk.title, bk.author, bk.category,
               u.username, u.id_number
        FROM borrow b
        JOIN books bk ON b.book_id = bk.book_id
        JOIN users u ON b.user_id = u.id
        ORDER BY b.date_borrowed DESC
        LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();

$result = $stmt->get_result();
$borrows = [];
while ($row = $result->fetch_assoc()) {
    $borrows[] = $row;
}

// Handle return book
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['return_book'])) {
    $borrow_id = intval($_POST['borrow_id']);
    
    // Update borrow record as returned
    $update_sql = "UPDATE borrow SET status = 'returned', date_returned = CURDATE() WHERE borrow_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("i", $borrow_id);
    
    if ($update_stmt->execute()) {
        // Make the book available again
        $book_sql = "UPDATE books b 
                    JOIN borrow br ON b.book_id = br.book_id
                    SET b.available = b.available + 1 
                    WHERE br.borrow_id = ?";
        $book_stmt = $conn->prepare($book_sql);
        $book_stmt->bind_param("i", $borrow_id);
        $book_stmt->execute();
        
        // Redirect to prevent form resubmission
        header("Location: manage_borrows.php?page=" . $page . "&returned=true");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Librow - Manage Borrows</title>
<link rel="stylesheet" href="style.css">
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Inter', Arial, sans-serif;
    }
    
    body {
        background: #f4f6f6;
        color: #1a1a1a;
    }
    
    main {
        padding: 20px;
        max-width: 1200px;
        margin: 0 auto;
    }
    
    /* Page Header */
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        padding-bottom: 15px;
        border-bottom: 2px solid #eaeaea;
    }
    
    .page-header h1 {
        font-size: 28px;
        color: #214d25;
        font-weight: 600;
    }
    
    .time-display {
        background-color: #e8f5e9;
        color: #214d25;
        padding: 10px 20px;
        border-radius: 25px;
        font-weight: 500;
        font-size: 16px;
    }
    
    /* Summary Stats */
    .stats-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-card {
        background-color: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
        text-align: center;
    }
    
    .stat-icon {
        font-size: 30px;
        margin-bottom: 10px;
    }
    
    .stat-count {
        font-size: 28px;
        font-weight: 600;
        color: #214d25;
        margin-bottom: 5px;
    }
    
    .stat-label {
        color: #666;
        font-size: 14px;
    }
    
    /* Borrow Cards */
    .borrow-cards {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 25px;
        margin-bottom: 40px;
    }
    
    .borrow-card {
        background-color: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        transition: transform 0.3s, box-shadow 0.3s;
    }
    
    .borrow-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }
    
    .book-header {
        display: flex;
        padding: 20px;
        background-color: #f8f9fa;
        border-bottom: 1px solid #eaeaea;
    }
    
    .book-cover {
        width: 80px;
        height: 100px;
        background-color: #e0e0e0;
        border-radius: 6px;
        overflow: hidden;
        margin-right: 20px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #214d25;
        color: white;
    }
    
    .book-cover i {
        font-size: 36px;
    }
    
    .book-info {
        flex: 1;
    }
    
    .book-title {
        font-size: 18px;
        font-weight: 600;
        color: #214d25;
        margin-bottom: 5px;
    }
    
    .book-author {
        color: #666;
        font-size: 14px;
        margin-bottom: 10px;
    }
    
    .borrow-details {
        padding: 20px;
    }
    
    .detail-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 12px;
        padding-bottom: 12px;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .detail-label {
        color: #666;
        font-size: 14px;
    }
    
    .detail-value {
        font-weight: 600;
        color: #333;
    }
    
    .user-info {
        background-color: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        margin-top: 15px;
    }
    
    .user-info .detail-label {
        font-size: 13px;
    }
    
    /* Action Buttons */
    .action-buttons {
        display: flex;
        gap: 10px;
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid #eaeaea;
    }
    
    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        flex: 1;
        text-align: center;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        font-family: 'Inter', Arial, sans-serif;
    }
    
    .btn-return {
        background-color: #e8f5e9;
        color: #2e7d32;
    }
    
    .btn-return:hover {
        background-color: #c8e6c9;
    }
    
    .btn-view {
        background-color: #e3f2fd;
        color: #1565c0;
    }
    
    .btn-view:hover {
        background-color: #bbdefb;
    }
    
    /* Status Badges */
    .status-badge {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .status-borrowed {
        background-color: #e3f2fd;
        color: #1565c0;
    }
    
    .status-returned {
        background-color: #e8f5e9;
        color: #2e7d32;
    }
    
    .status-overdue {
        background-color: #ffebee;
        color: #c62828;
    }
    
    /* Pagination */
    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 10px;
        margin-top: 40px;
    }
    
    .pagination a, .pagination span {
        padding: 8px 16px;
        border-radius: 6px;
        text-decoration: none;
        color: #333;
        font-weight: 500;
        transition: all 0.3s;
        font-family: 'Inter', Arial, sans-serif;
    }
    
    .pagination a:hover {
        background-color: #e3f2fd;
        color: #1a73e8;
    }
    
    .pagination .current {
        background-color: #214d25;
        color: white;
    }
    
    .pagination .disabled {
        color: #999;
        cursor: not-allowed;
    }
    
    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        background-color: white;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    }
    
    .empty-icon {
        font-size: 60px;
        color: #bdbdbd;
        margin-bottom: 20px;
    }
    
    .empty-state h3 {
        color: #666;
        margin-bottom: 10px;
    }
    
    .empty-state p {
        color: #888;
        max-width: 400px;
        margin: 0 auto;
    }
    
    /* Success Message */
    .success-message {
        background-color: #d4edda;
        color: #155724;
        padding: 12px;
        border-radius: 6px;
        margin-bottom: 20px;
        border-left: 4px solid #28a745;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .borrow-cards {
            grid-template-columns: 1fr;
        }
        
        .stats-container {
            grid-template-columns: 1fr;
        }
        
        .page-header {
            flex-direction: column;
            gap: 15px;
            text-align: center;
        }
        
        .action-buttons {
            flex-direction: column;
        }
    }
</style>
</head>
<body>

<?php include "sidebar.php"; ?>

<!-- HEADER -->
<header class="header">
    <div class="header-left">
        <div class="logo-container">
            <img src="img/logo.png" alt="Lib'Row" class="logo" onerror="this.style.display='none'">
            <h1 class="site-title">Lib'Row</h1>
        </div>
    </div>

    <div class="header-right">
        <div class="header-icons">
            <span class="icon" title="Notifications">
                🔔
                <span class="badge notification-badge">2</span>
            </span>
            <span class="icon" title="Cart" onclick="window.location.href='cart.php'">
                🛒
                <span class="badge cart-badge"><?php echo $cart_count; ?></span>
            </span>
        </div>

        <div class="menu-icon" onclick="toggleSidebar()" title="Menu">
            <div class="menu-icon-inner">☰</div>
        </div>
    </div>
</header>

<!-- MAIN CONTENT -->
<main style="padding-bottom:60px;">

    <!-- Page Header -->
    <div class="page-header">
        <h1>Manage Borrows</h1>
    </div>
    
    <?php if (isset($_GET['returned']) && $_GET['returned'] == 'true'): ?>
    <div class="success-message">
        <i class="fas fa-check-circle"></i> Book returned successfully!
    </div>
    <?php endif; ?>
    
    <!-- Summary Stats -->
    <div class="stats-container">
        <div class="stat-card">
            <div class="stat-icon" style="color: #214d25;">
                <i class="fas fa-book"></i>
            </div>
            <div class="stat-count"><?php echo $total_records; ?></div>
            <div class="stat-label">Total Borrows</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="color: #2e7d32;">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-count">
                <?php 
                $active_sql = "SELECT COUNT(*) as active FROM borrow WHERE status = 'borrowed' OR status = 'accepted'";
                $active_result = $conn->query($active_sql);
                $active_row = $active_result->fetch_assoc();
                echo $active_row['active'];
                ?>
            </div>
            <div class="stat-label">Active Borrows</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="color: #c62828;">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-count">
                <?php 
                $overdue_sql = "SELECT COUNT(*) as overdue FROM borrow WHERE date_due < CURDATE() AND (status = 'borrowed' OR status = 'accepted')";
                $overdue_result = $conn->query($overdue_sql);
                $overdue_row = $overdue_result->fetch_assoc();
                echo $overdue_row['overdue'];
                ?>
            </div>
            <div class="stat-label">Overdue Books</div>
        </div>
    </div>
    
    <!-- Borrow Cards -->
    <?php if (!empty($borrows)): ?>
        <div class="borrow-cards">
            <?php foreach ($borrows as $borrow): 
                // Determine status class
                $status_class = 'status-borrowed';
                if ($borrow['status'] == 'returned') {
                    $status_class = 'status-returned';
                } elseif (strtotime($borrow['date_due']) < time() && ($borrow['status'] == 'borrowed' || $borrow['status'] == 'accepted')) {
                    $status_class = 'status-overdue';
                }
            ?>
            <div class="borrow-card">
                <div class="book-header">
                    <div class="book-cover">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="book-info">
                        <div class="book-title"><?php echo htmlspecialchars($borrow['title']); ?></div>
                        <div class="book-author"><?php echo htmlspecialchars($borrow['author'] ?? 'Unknown Author'); ?></div>
                        <span class="status-badge <?php echo $status_class; ?>">
                            <?php 
                            if ($status_class == 'status-overdue') {
                                echo 'Overdue';
                            } else {
                                echo ucfirst($borrow['status']);
                            }
                            ?>
                        </span>
                    </div>
                </div>
                
                <div class="borrow-details">
                    <div class="detail-row">
                        <span class="detail-label">Borrow Date:</span>
                        <span class="detail-value"><?php echo date('m/d/Y', strtotime($borrow['date_borrowed'])); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Return Date:</span>
                        <span class="detail-value"><?php echo date('m/d/Y', strtotime($borrow['date_due'])); ?></span>
                    </div>
                    
                    <?php if ($borrow['date_returned']): ?>
                    <div class="detail-row">
                        <span class="detail-label">Returned On:</span>
                        <span class="detail-value"><?php echo date('m/d/Y', strtotime($borrow['date_returned'])); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="user-info">
                        <div class="detail-row">
                            <span class="detail-label">Borrowed By:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($borrow['username']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">ID Number:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($borrow['id_number'] ?? 'N/A'); ?></span>
                        </div>
                    </div>
                    
                    <?php if ($borrow['status'] == 'borrowed' || $borrow['status'] == 'accepted'): ?>
                    <div class="action-buttons">
                        <button class="btn btn-view" onclick="location.href='book_details.php?id=<?php echo $borrow['book_id']; ?>'">
                            <i class="fas fa-eye"></i> View Book
                        </button>
                        <form method="POST" style="flex: 1;" onsubmit="return confirm('Mark this book as returned?')">
                            <input type="hidden" name="borrow_id" value="<?php echo $borrow['borrow_id']; ?>">
                            <button type="submit" name="return_book" class="btn btn-return">
                                <i class="fas fa-check-circle"></i> Mark as Returned
                            </button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">
                <i class="fas fa-book-open"></i>
            </div>
            <h3>No borrow records found</h3>
            <p>There are no borrowed books at the moment.</p>
        </div>
    <?php endif; ?>
    
    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page - 1; ?>">
                <i class="fas fa-chevron-left"></i> Previous
            </a>
        <?php else: ?>
            <span class="disabled"><i class="fas fa-chevron-left"></i> Previous</span>
        <?php endif; ?>
        
        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
            <?php if ($i == $page): ?>
                <span class="current"><?php echo $i; ?></span>
            <?php else: ?>
                <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
            <?php endif; ?>
        <?php endfor; ?>
        
        <?php if ($page < $total_pages): ?>
            <a href="?page=<?php echo $page + 1; ?>">
                Next <i class="fas fa-chevron-right"></i>
            </a>
        <?php else: ?>
            <span class="disabled">Next <i class="fas fa-chevron-right"></i></span>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</main>

<script>
    // Set current time and update every minute
    function updateTime() {
        const now = new Date();
        const timeString = now.getHours().toString().padStart(2, '0') + ':' + 
                          now.getMinutes().toString().padStart(2, '0');
        document.querySelector('.time-display').innerHTML = 
            `<i class="far fa-clock"></i> ${timeString}`;
    }
    
    // Update time every minute
    setInterval(updateTime, 60000);
    
    // Initialize time
    updateTime();
    
    // Auto-hide success message after 5 seconds
    const successMessage = document.querySelector('.success-message');
    if (successMessage) {
        setTimeout(() => {
            successMessage.style.display = 'none';
        }, 5000);
    }
    
    // Toggle sidebar function (from your existing script.js)
    function toggleSidebar() {
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.querySelector('.sidebar-overlay');
        
        if (sidebar && overlay) {
            sidebar.classList.toggle('open');
            overlay.style.display = sidebar.classList.contains('open') ? 'block' : 'none';
        }
    }
</script>
</body>
</html>
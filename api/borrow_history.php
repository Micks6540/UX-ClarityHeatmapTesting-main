<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// Get cart count for badge
$cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;

// Fetch borrow records from database
$borrow_records = [];
$user_id = $_SESSION['user']['id'] ?? $_SESSION['user']['user_id'] ?? 0; // Try both possible user ID fields

// Try to get username for better debugging
$username = $_SESSION['user']['username'] ?? 'Unknown';

// First, let's debug the session data
error_log("User ID from session: " . $user_id);
error_log("Username from session: " . $username);

// Fix 1: Query the correct table name - it's 'borrow' not 'borrow_records'
// Fix 2: Join with books table to get book titles
$query = "SELECT b.*, bk.title as book_title, bk.author 
          FROM borrow b 
          JOIN books bk ON b.book_id = bk.book_id 
          WHERE b.user_id = ? 
          ORDER BY b.date_borrowed DESC 
          LIMIT 20";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Map the database fields to expected array format
        $borrow_records[] = array(
            "borrow_id" => $row['borrow_id'],
            "book_title" => $row['book_title'] . ($row['author'] ? " by " . $row['author'] : ""),
            "date_borrowed" => $row['date_borrowed'],
            "date_due" => $row['date_due'],
            "date_returned" => $row['date_returned'] ?? null,
            "status" => $row['status']
        );
    }
    
    error_log("Found " . count($borrow_records) . " borrow records for user ID: " . $user_id);
} else {
    // For debugging, let's see what's in the database
    $debug_query = "SELECT COUNT(*) as total FROM borrow WHERE user_id = ?";
    $debug_stmt = $conn->prepare($debug_query);
    $debug_stmt->bind_param("i", $user_id);
    $debug_stmt->execute();
    $debug_result = $debug_stmt->get_result();
    $debug_row = $debug_result->fetch_assoc();
    
    error_log("Debug: Total borrow records for user ID " . $user_id . ": " . ($debug_row['total'] ?? 0));
    
    // Show what's actually in the borrow table for this user
    $show_query = "SELECT b.*, bk.title FROM borrow b LEFT JOIN books bk ON b.book_id = bk.book_id WHERE b.user_id = ?";
    $show_stmt = $conn->prepare($show_query);
    $show_stmt->bind_param("i", $user_id);
    $show_stmt->execute();
    $show_result = $show_stmt->get_result();
    
    if ($show_result && mysqli_num_rows($show_result) > 0) {
        while ($debug_row = mysqli_fetch_assoc($show_result)) {
            error_log("Found in DB: borrow_id=" . $debug_row['borrow_id'] . 
                     ", book_id=" . $debug_row['book_id'] . 
                     ", title=" . ($debug_row['title'] ?? 'N/A') . 
                     ", status=" . $debug_row['status']);
            
            // Use actual data from database
            $borrow_records[] = array(
                "borrow_id" => $debug_row['borrow_id'],
                "book_title" => $debug_row['title'] ?? "Book ID: " . $debug_row['book_id'],
                "date_borrowed" => $debug_row['date_borrowed'],
                "date_due" => $debug_row['date_due'],
                "date_returned" => $debug_row['date_returned'] ?? null,
                "status" => $debug_row['status']
            );
        }
    } else {
        error_log("No borrow records found in database for user ID: " . $user_id);
        
        // Fallback to sample data if no records found
        $borrow_records = array(
            array("borrow_id" => 11, "book_title" => "Brave New World", "date_borrowed" => "2025-12-05", "date_due" => "2025-12-19", "status" => "borrowed"),
            array("borrow_id" => 10, "book_title" => "Pride and Prejudice", "date_borrowed" => "2025-12-05", "date_due" => "2025-12-12", "status" => "returned"),
            array("borrow_id" => 9, "book_title" => "The Great Gatsby", "date_borrowed" => "2025-12-05", "date_due" => "2025-12-12", "status" => "borrowed")
        );
    }
}

// Counters for summary
$total_borrowed = 0;
$total_returned = 0;
foreach ($borrow_records as $record) {
    if ($record["status"] == "borrowed" || $record["status"] == "accepted") {
        $total_borrowed++;
    } else if ($record["status"] == "returned") {
        $total_returned++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrow History - Lib'Row</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Additional styles for borrow history page */
        .page-container {
            padding: 20px 18px 60px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .page-title {
            color: #214d25;
            font-size: 28px;
            margin: 20px 0 10px;
            font-weight: 700;
        }
        
        .page-subtitle {
            color: #6a6d6d;
            font-size: 16px;
            margin-bottom: 24px;
        }
        
        .debug-info {
            background: #fff3cd;
            border: 1px solid #ffecb5;
            color: #856404;
            padding: 10px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .borrow-summary {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }
        
        .summary-card {
            background: white;
            border-radius: 12px;
            padding: 18px 20px;
            flex: 1;
            min-width: 180px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid #eaeaea;
        }
        
        .summary-card h3 {
            color: #214d25;
            font-size: 15px;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .summary-value {
            font-size: 32px;
            font-weight: 700;
            color: #214d25;
            line-height: 1;
        }
        
        .records-container {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            margin-bottom: 30px;
            border: 1px solid #eaeaea;
        }
        
        .section-header {
            background: #f8f9fa;
            padding: 16px 20px;
            border-bottom: 1px solid #eaeaea;
        }
        
        .section-header h2 {
            color: #214d25;
            font-size: 20px;
            font-weight: 700;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background-color: #f8f9fa;
            padding: 16px 12px;
            text-align: left;
            font-weight: 600;
            color: #2c3e50;
            border-bottom: 2px solid #eaeaea;
            font-size: 14px;
        }
        
        td {
            padding: 16px 12px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }
        
        tr:hover {
            background-color: #f9f9f9;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            display: inline-block;
            min-width: 90px;
            text-align: center;
        }
        
        .status-borrowed {
            background-color: #ffeaa7;
            color: #d35400;
        }
        
        .status-accepted {
            background-color: #a8e6cf;
            color: #27ae60;
        }
        
        .status-returned {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .date-cell {
            font-family: monospace;
            font-size: 0.95rem;
        }
        
        .book-title {
            font-weight: 600;
            color: #1a1a1a;
        }
        
        .no-records {
            text-align: center;
            padding: 40px 20px;
            color: #6a6d6d;
            font-size: 16px;
        }
        
        .actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 20px;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 6px;
            border: none;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background-color: #214d25;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #1a3e1e;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(33, 77, 37, 0.3);
        }
        
        .btn-secondary {
            background-color: #f1f2f6;
            color: #2c3e50;
            border: 1px solid #ddd;
        }
        
        .btn-secondary:hover {
            background-color: #e4e5e9;
        }
        
        .current-date {
            font-size: 14px;
            color: #6a6d6d;
            margin-bottom: 15px;
            display: inline-block;
            background: #f8f9fa;
            padding: 6px 12px;
            border-radius: 6px;
        }
        
        @media (max-width: 768px) {
            .borrow-summary {
                flex-direction: column;
            }
            
            .summary-card {
                min-width: 100%;
            }
            
            .actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
            
            table {
                display: block;
                overflow-x: auto;
            }
            
            .page-title {
                font-size: 24px;
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
<div class="page-container">
    
    <h1 class="page-title">Borrow History</h1>
    <p class="page-subtitle">Review your current and past borrowed books</p>
    
    <!-- Debug information (remove in production) -->
    <?php if (isset($_GET['debug'])): ?>
    <div class="debug-info">
        <strong>Debug Info:</strong><br>
        User ID: <?php echo $user_id; ?><br>
        Username: <?php echo htmlspecialchars($username); ?><br>
        Total Records Found: <?php echo count($borrow_records); ?>
    </div>
    <?php endif; ?>
    
    <!-- Summary Cards -->
    <div class="borrow-summary">
        <div class="summary-card">
            <h3>Total Records</h3>
            <div class="summary-value"><?php echo count($borrow_records); ?></div>
        </div>
        <div class="summary-card">
            <h3>Currently Borrowed</h3>
            <div class="summary-value"><?php echo $total_borrowed; ?></div>
        </div>
        <div class="summary-card">
            <h3>Returned</h3>
            <div class="summary-value"><?php echo $total_returned; ?></div>
        </div>
    </div>
    
    <!-- Records Table -->
    <div class="records-container">
        <div class="section-header">
            <h2>Recent Borrow Records</h2>
        </div>
        
        <?php if (count($borrow_records) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Borrow ID</th>
                    <th>Book Title</th>
                    <th>Date Borrowed</th>
                    <th>Date Due</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($borrow_records as $record) {
                    // Determine status class
                    $status_class = "status-" . $record["status"];
                    
                    echo "<tr>";
                    echo "<td>" . $record["borrow_id"] . "</td>";
                    echo "<td class='book-title'>" . htmlspecialchars($record["book_title"]) . "</td>";
                    echo "<td class='date-cell'>" . $record["date_borrowed"] . "</td>";
                    echo "<td class='date-cell'>" . $record["date_due"] . "</td>";
                    echo "<td><span class='status-badge " . $status_class . "'>" . $record["status"] . "</span></td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="no-records">
            <p>No borrow records found. Start borrowing books from the library!</p>
            <p style="margin-top: 10px; font-size: 14px;">
                <a href="index.php" style="color: #214d25; text-decoration: underline;">Browse books</a>
            </p>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Action Buttons -->
    <div class="actions">
        <button class="btn btn-secondary" onclick="window.location.href='index.php'">Back to Home</button>
    </div>
</div>

<script src="script.js"></script>
<script>
    // Add row selection functionality
    document.addEventListener('DOMContentLoaded', function() {
        const tableRows = document.querySelectorAll('table tbody tr');
        tableRows.forEach(row => {
            row.addEventListener('click', function() {
                // Toggle selection class
                this.classList.toggle('selected');
                
                // Remove selection from other rows
                tableRows.forEach(otherRow => {
                    if (otherRow !== this) {
                        otherRow.classList.remove('selected');
                    }
                });
            });
        });
        
        // Style for selected row
        const style = document.createElement('style');
        style.textContent = `
            tr.selected {
                background-color: #e8f4fd !important;
                border-left: 4px solid #3498db;
            }
        `;
        document.head.appendChild(style);
    });
</script>
</body>
</html>
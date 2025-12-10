<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// Get book ID from URL or POST
$book_id = isset($_GET['book_id']) ? intval($_GET['book_id']) : 
           (isset($_POST['book_id']) ? intval($_POST['book_id']) : 1);

// Check if borrowing multiple books from cart
$is_multiple = isset($_GET['multiple']) || isset($_POST['multiple_books']);
$selected_books = isset($_SESSION['selected_books_for_borrow']) ? 
                  $_SESSION['selected_books_for_borrow'] : [];

// Get book details
$book_title = 'Book Title';
$book_number = '';
$section_name = 'General';
$language = 'English';
$availability = 'Available';

$sql = "SELECT * FROM books WHERE book_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $book_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $row = $result->fetch_assoc()) {
    $book_title = $row['title'];
    $book_number = $row['book_id'];
    $section_name = $row['category'] ?? 'General';
    $language = $row['language'] ?? 'English';
    $availability = $row['available'] > 0 ? 'Available' : 'Not Available';
}

// Handle form submission
$confirmed = false;
$borrower_name = '';
$pickup_date = '';
$duration = '';
$return_date = '';
$purpose = '';
$record_ids = [];
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['confirm'])) {
        // Validate and sanitize input
        $borrower_name = trim($_POST['borrower_name']);
        $pickup_date = $_POST['pickup_date'];
        $duration = $_POST['duration'];
        $purpose = trim($_POST['purpose']);
        $user_id = $_SESSION['user']['id'];
        
        // Calculate return date based on duration
       if ($pickup_date && $duration) {
    $pickup_datetime = DateTime::createFromFormat('Y-m-d', $pickup_date);
    if ($pickup_datetime) {
        if ($duration == '1_week') {
            $weeks = 1;
        } elseif ($duration == '2_weeks') {
            $weeks = 2;
        } elseif ($duration == '3_weeks') {
            $weeks = 3;
        } else { // '4_weeks'
            $weeks = 4;
        }
        $pickup_datetime->modify("+$weeks weeks");
        $return_date = $pickup_datetime->format('Y-m-d');
    }
                    }
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            $books_to_borrow = $is_multiple ? $selected_books : [$book_id];
            
            foreach ($books_to_borrow as $current_book_id) {
                $current_book_id = intval($current_book_id);
                
                // Check availability
                $check_sql = "SELECT available FROM books WHERE book_id = ?";
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->bind_param("i", $current_book_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result && $book_data = $check_result->fetch_assoc()) {
                    if ($book_data['available'] > 0) {
                        // Insert borrow record
                        $borrow_sql = "INSERT INTO borrow (book_id, user_id, date_borrowed, date_due, status) 
                                      VALUES (?, ?, ?, ?, 'borrowed')";
                        $borrow_stmt = $conn->prepare($borrow_sql);
                        $borrow_stmt->bind_param("iiss", $current_book_id, $user_id, $pickup_date, $return_date);
                        $borrow_stmt->execute();
                        
                        $record_ids[] = $conn->insert_id;
                        
                        // Update availability
                        $update_sql = "UPDATE books SET available = available - 1 WHERE book_id = ?";
                        $update_stmt = $conn->prepare($update_sql);
                        $update_stmt->bind_param("i", $current_book_id);
                        $update_stmt->execute();
                        
                        // Remove from cart if exists
                        if (isset($_SESSION['cart'])) {
                            $key = array_search($current_book_id, $_SESSION['cart']);
                            if ($key !== false) {
                                unset($_SESSION['cart'][$key]);
                            }
                        }
                    } else {
                        throw new Exception("Book ID $current_book_id is no longer available.");
                    }
                }
            }
            
            // Commit transaction
            $conn->commit();
            $confirmed = true;
            
            // Clear selected books from session
            unset($_SESSION['selected_books_for_borrow']);
            
        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            $error_message = "Error: " . $e->getMessage();
        }
    }
}

// Get borrower name from session
$borrower_name = isset($_SESSION['user']['name']) ? $_SESSION['user']['name'] : 
                 (isset($_SESSION['user']['username']) ? $_SESSION['user']['username'] : '');

// Get recent borrows for this user
$user_id = $_SESSION['user']['id'];
$borrow_history = [];
$history_sql = "SELECT b.borrow_id, b.book_id, b.date_borrowed, b.date_due, b.status, 
                       bk.title AS book_title
                FROM borrow b
                LEFT JOIN books bk ON b.book_id = bk.book_id
                WHERE b.user_id = ?
                ORDER BY b.date_borrowed DESC 
                LIMIT 5";
$history_stmt = $conn->prepare($history_sql);
$history_stmt->bind_param("i", $user_id);
$history_stmt->execute();
$history_result = $history_stmt->get_result();

if ($history_result) {
    while ($row = $history_result->fetch_assoc()) {
        $borrow_history[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrow Confirmation System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
            padding: 20px;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .header {
            background-color: #1a73e8;
            color: white;
            padding: 25px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 24px;
            font-weight: 600;
        }
        
        .time-display {
            background-color: rgba(255, 255, 255, 0.2);
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 500;
        }
        
        .book-info {
            background-color: #f8f9fa;
            padding: 25px 30px;
            border-bottom: 1px solid #eaeaea;
        }
        
        .book-title {
            font-size: 22px;
            font-weight: 600;
            color: #1a237e;
            margin-bottom: 10px;
        }
        
        .book-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .detail-item {
            background-color: white;
            padding: 12px 15px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }
        
        .detail-label {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .detail-value {
            font-weight: 600;
            color: #333;
        }
        
        .availability {
            color: #2e7d32;
            font-weight: 600;
        }
        
        .unavailable {
            color: #c62828;
            font-weight: 600;
        }
        
        .form-section {
            padding: 30px;
        }
        
        .section-title {
            font-size: 20px;
            color: #1a237e;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eaeaea;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-label {
            display: block;
            font-weight: 600;
            margin-bottom: 10px;
            color: #444;
        }
        
        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 14px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-input:focus, .form-select:focus, .form-textarea:focus {
            border-color: #1a73e8;
            outline: none;
        }
        
        .form-textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        .duration-options {
            display: flex;
            gap: 15px;
        }
        
        .duration-option {
            flex: 1;
            text-align: center;
        }
        
        .duration-btn {
            width: 100%;
            padding: 15px;
            background-color: #f1f3f4;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .duration-btn:hover {
            background-color: #e8f0fe;
        }
        
        .duration-btn.selected {
            background-color: #1a73e8;
            color: white;
            border-color: #1a73e8;
        }
        
        .reminder {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 0 8px 8px 0;
        }
        
        .reminder h4 {
            color: #856404;
            margin-bottom: 8px;
        }
        
        .terms-container {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 25px 0;
        }
        
        .terms-checkbox {
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        
        .terms-checkbox input {
            margin-top: 5px;
        }
        
        .button-group {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            margin-top: 30px;
        }
        
        .btn {
            padding: 15px 30px;
            font-size: 16px;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            flex: 1;
            text-align: center;
        }
        
        .btn-confirm {
            background-color: #1a73e8;
            color: white;
        }
        
        .btn-confirm:hover {
            background-color: #0d62d9;
        }
        
        .btn-return {
            background-color: #f1f3f4;
            color: #333;
        }
        
        .btn-return:hover {
            background-color: #e0e2e4;
        }
        
        .confirmation-message {
            text-align: center;
            padding: 40px 20px;
        }
        
        .confirmation-icon {
            font-size: 70px;
            color: #2e7d32;
            margin-bottom: 20px;
        }
        
        .confirmation-title {
            font-size: 28px;
            color: #1a237e;
            margin-bottom: 15px;
        }
        
        .confirmation-text {
            font-size: 18px;
            color: #555;
            margin-bottom: 30px;
        }
        
        .borrower-details {
            background-color: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            max-width: 600px;
            margin: 30px auto;
            text-align: left;
        }
        
        .borrower-details h3 {
            margin-bottom: 20px;
            color: #1a237e;
        }
        
        .detail-row {
            display: flex;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eaeaea;
        }
        
        .detail-label {
            width: 150px;
            font-weight: 600;
            color: #555;
        }
        
        .detail-value {
            flex: 1;
            color: #333;
        }
        
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }
        
        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            flex: 1;
        }
        
        .step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 20px;
            right: -50%;
            width: 100%;
            height: 3px;
            background-color: #ddd;
            z-index: 1;
        }
        
        .step.active:not(:last-child)::after {
            background-color: #1a73e8;
        }
        
        .step-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #ddd;
            color: #666;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 18px;
            z-index: 2;
            margin-bottom: 10px;
        }
        
        .step.active .step-circle {
            background-color: #1a73e8;
            color: white;
        }
        
        .step.completed .step-circle {
            background-color: #2e7d32;
            color: white;
        }
        
        .step-text {
            font-size: 14px;
            color: #666;
            font-weight: 500;
        }
        
        .step.active .step-text {
            color: #1a73e8;
            font-weight: 600;
        }
        
        /* Recent borrows section */
        .recent-borrows {
            margin-top: 40px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 10px;
        }
        
        .recent-borrows h3 {
            color: #1a237e;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .borrow-table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .borrow-table th {
            background-color: #1a73e8;
            color: white;
            padding: 12px 15px;
            text-align: left;
        }
        
        .borrow-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eaeaea;
        }
        
        .borrow-table tr:hover {
            background-color: #f5f5f5;
        }
        
        .status-borrowed {
            background-color: #e8f5e8;
            color: #2e7d32;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-returned {
            background-color: #e3f2fd;
            color: #1565c0;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .multiple-books-notice {
            background-color: #e3f2fd;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 4px solid #2196f3;
        }
        
        @media (max-width: 768px) {
            .container {
                border-radius: 0;
            }
            
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .book-details {
                grid-template-columns: 1fr;
            }
            
            .duration-options {
                flex-direction: column;
            }
            
            .button-group {
                flex-direction: column;
            }
            
            .step:not(:last-child)::after {
                display: none;
            }
            
            .borrow-table {
                font-size: 14px;
            }
            
            .borrow-table th, .borrow-table td {
                padding: 8px 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header with time -->
        <div class="header">
            <h1>Borrow Confirmation</h1>
        </div>
        
        <!-- Book information -->
        <div class="book-info">
            <div class="book-title"><?php echo htmlspecialchars($book_title); ?></div>
            <?php if ($is_multiple && count($selected_books) > 1): ?>
                <div class="multiple-books-notice">
                    <strong><i class="fas fa-books"></i> Borrowing <?php echo count($selected_books); ?> books from your cart</strong>
                </div>
            <?php endif; ?>
            <div class="book-details">
                <div class="detail-item">
                    <div class="detail-label">Section name</div>
                    <div class="detail-value"><?php echo htmlspecialchars($section_name); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Book number</div>
                    <div class="detail-value"><?php echo htmlspecialchars($book_number); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Language</div>
                    <div class="detail-value"><?php echo htmlspecialchars($language); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Access</div>
                    <div class="detail-value <?php echo $availability == 'Available' ? 'availability' : 'unavailable'; ?>">
                        <?php echo htmlspecialchars($availability); ?>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if (!$confirmed): ?>
        <!-- Step indicator -->
        <div class="step-indicator">
            <div class="step active">
                <div class="step-circle">1</div>
                <div class="step-text">Borrower Details</div>
            </div>
            <div class="step">
                <div class="step-circle">2</div>
                <div class="step-text">Confirmation</div>
            </div>
            <div class="step">
                <div class="step-circle">3</div>
                <div class="step-text">Request Confirmed</div>
            </div>
        </div>


        
        <!-- Borrow form -->
        <div class="form-section">
            <form method="POST" action="">
                <?php if ($is_multiple): ?>
                    <input type="hidden" name="multiple_books" value="true">
                <?php endif; ?>
                <input type="hidden" name="book_id" value="<?php echo $book_id; ?>">
                
                <h2 class="section-title">Borrower Information</h2>
                
                <div class="form-group">
                    <label for="borrower_name" class="form-label">Name of borrower</label>
                    <input type="text" id="borrower_name" name="borrower_name" class="form-input" 
                           placeholder="Enter your full name" value="<?php echo htmlspecialchars($borrower_name); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="pickup_date" class="form-label">Pick-up date</label>
                    <input type="date" id="pickup_date" name="pickup_date" class="form-input" required>
                    <div class="reminder">
                        <h4><i class="fas fa-exclamation-circle"></i> Reminder</h4>
                        <p>Please select a pick-up date on a regular school day.</p>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Duration</label>
                    <div class="duration-options">
                        <div class="duration-option">
                            <button type="button" class="duration-btn" id="1week" onclick="selectDuration('1_week')">1 Week</button>
                            <input type="radio" name="duration" value="1_week" id="duration_1week" style="display:none;" required>
                        </div>
                        <div class="duration-option">
                            <button type="button" class="duration-btn" id="2weeks" onclick="selectDuration('2_weeks')">2 Weeks</button>
                            <input type="radio" name="duration" value="2_weeks" id="duration_2weeks" style="display:none;" required>
                        </div>
                        <div class="duration-option">
                            <button type="button" class="duration-btn" id="3weeks" onclick="selectDuration('3_weeks')">3 Weeks</button>
                            <input type="radio" name="duration" value="3_weeks" id="duration_3weeks" style="display:none;" required>
                        </div>
                        <div class="duration-option">
                            <button type="button" class="duration-btn" id="4weeks" onclick="selectDuration('4_weeks')">4 Weeks</button>
                            <input type="radio" name="duration" value="4_weeks" id="duration_4weeks" style="display:none;" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="purpose" class="form-label">Purpose</label>
                    <textarea id="purpose" name="purpose" class="form-textarea" placeholder="Please explain why you need to borrow this book" required>I am borrowing this book to strengthen my understanding of problem-solving techniques in preparation for my upcoming GCSE mathematics exams. I aim to improve my ability to tackle complex word problems and apply mathematical concepts more confidently.</textarea>
                </div>
                
                <div class="terms-container">
                    <div class="terms-checkbox">
                        <input type="checkbox" id="terms" name="terms" required>
                        <label for="terms">I agree to the terms & conditions. I understand that late returns will incur fees, and damaged or lost items may result in repair or replacement charges.</label>
                    </div>
                </div>
                
                <div class="button-group">
                    <button type="button" class="btn btn-return" onclick="window.location.href='index.php'">
                        <i class="fas fa-home"></i> Return to Home
                    </button>
                    <button type="submit" name="confirm" class="btn btn-confirm">
                        <i class="fas fa-check-circle"></i> Confirm Borrow Request
                    </button>
                </div>
            </form>
        </div>
        

        
        <?php else: ?>
        <!-- Confirmation message -->
        <div class="step-indicator">
            <div class="step completed">
                <div class="step-circle"><i class="fas fa-check"></i></div>
                <div class="step-text">Borrower Details</div>
            </div>
            <div class="step completed">
                <div class="step-circle"><i class="fas fa-check"></i></div>
                <div class="step-text">Confirmation</div>
            </div>
            <div class="step active">
                <div class="step-circle">3</div>
                <div class="step-text">Request Confirmed</div>
            </div>
        </div>
        
        <div class="confirmation-message">
            <div class="confirmation-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h2 class="confirmation-title">Borrow Request Confirmed</h2>
            <p class="confirmation-text">Please allow at least 24 hours for your reservation to be validated.</p>
            
            <div class="borrower-details">
                <h3>Borrow Request Summary</h3>
                <div class="detail-row">
                    <div class="detail-label">Book Title:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($book_title); ?></div>
                </div>
                <?php if ($is_multiple && count($selected_books) > 1): ?>
                <div class="detail-row">
                    <div class="detail-label">Total Books:</div>
                    <div class="detail-value"><?php echo count($selected_books); ?> books</div>
                </div>
                <?php endif; ?>
                <div class="detail-row">
                    <div class="detail-label">Borrower Name:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($borrower_name); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Pick-up Date:</div>
                    <div class="detail-value"><?php echo date('m/d/Y', strtotime($pickup_date)); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Duration:</div>
                    <div class="detail-value"><?php 
        if ($duration == '1_week') {
            echo '1 Week';
        } elseif ($duration == '2_weeks') {
            echo '2 Weeks';
        } elseif ($duration == '3_weeks') {
            echo '3 Weeks';
        } elseif ($duration == '4_weeks') {
            echo '4 Weeks';
        }
        ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Return Date:</div>
                    <div class="detail-value"><?php echo date('m/d/Y', strtotime($return_date)); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Purpose:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($purpose); ?></div>
                </div>
                <?php if (!empty($record_ids)): ?>
                <div class="detail-row">
                    <div class="detail-label">Reference ID<?php echo count($record_ids) > 1 ? 's' : ''; ?>:</div>
                    <div class="detail-value" style="color: #1a73e8; font-weight: 700;">
                        <?php 
                        if (count($record_ids) === 1) {
                            echo '#' . $record_ids[0];
                        } else {
                            echo '#' . implode(', #', $record_ids);
                        }
                        ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="reminder" style="max-width: 600px; margin: 0 auto 30px;">
                <h4><i class="fas fa-exclamation-triangle"></i> Important Reminder</h4>
                <p>Late returns will incur fees, and damaged or lost items may result in repair or replacement charges.</p>
            </div>
            
            <div class="button-group" style="max-width: 300px;">
                <button class="btn btn-return" onclick="window.location.href='index.php'">
                    <i class="fas fa-home"></i> Return to Home
                </button>
                <?php if ($is_multiple): ?>
                <button class="btn btn-return" onclick="window.location.href='cart.php'">
                    <i class="fas fa-shopping-cart"></i> Back to Cart
                </button>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // Set default pickup date to tomorrow
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        const formattedDate = tomorrow.toISOString().split('T')[0];
        document.getElementById('pickup_date').value = formattedDate;
        
        // Set default duration to 2 weeks
        selectDuration('2_weeks');
        
        function selectDuration(duration) {
            // Update button styles
            document.getElementById('1week').classList.remove('selected');
            document.getElementById('2weeks').classList.remove('selected');
            document.getElementById('3weeks').classList.remove('selected');
            document.getElementById('4weeks').classList.remove('selected');
            
            if (duration === '1_week') {
                document.getElementById('1week').classList.add('selected');
                document.getElementById('duration_1week').checked = true;
            } else if (duration === '2_weeks') {
                document.getElementById('2weeks').classList.add('selected');
                document.getElementById('duration_2weeks').checked = true;
            } else if (duration === '3_weeks') {
                document.getElementById('3weeks').classList.add('selected');
                document.getElementById('duration_3weeks').checked = true;
            } else {
                document.getElementById('4weeks').classList.add('selected');
                document.getElementById('duration_4weeks').checked = true;
            }
            
            // Update return date preview if pickup date is selected
            updateReturnDatePreview();
        }
                
        // Update return date preview when pickup date changes
        document.getElementById('pickup_date').addEventListener('change', updateReturnDatePreview);
        
function updateReturnDatePreview() {
    const pickupDate = document.getElementById('pickup_date').value;
    const durationSelected = document.querySelector('input[name="duration"]:checked');
    
    if (pickupDate && durationSelected) {
        let weeks;
        if (durationSelected.value === '1_week') {
            weeks = 1;
        } else if (durationSelected.value === '2_weeks') {
            weeks = 2;
        } else if (durationSelected.value === '3_weeks') {
            weeks = 3;
        } else { // '4_weeks'
            weeks = 4;
        }
        
        const returnDate = new Date(pickupDate);
        returnDate.setDate(returnDate.getDate() + (weeks * 7));
        
        // Format date as MM/DD/YYYY
        const formattedReturnDate = (returnDate.getMonth() + 1).toString().padStart(2, '0') + '/' + 
                                   returnDate.getDate().toString().padStart(2, '0') + '/' + 
                                   returnDate.getFullYear();
        
        // Show preview
        const previewElement = document.getElementById('return-date-preview');
        if (!previewElement) {
            const previewDiv = document.createElement('div');
            previewDiv.id = 'return-date-preview';
            previewDiv.className = 'reminder';
            previewDiv.innerHTML = `<h4><i class="far fa-calendar-alt"></i> Return Date Preview</h4>
                                   <p>If borrowed for ${weeks} week${weeks > 1 ? 's' : ''}, the return date will be: <strong>${formattedReturnDate}</strong></p>`;
            
            // Insert after duration options
            const durationGroup = document.querySelector('.duration-options').parentElement;
            durationGroup.appendChild(previewDiv);
        } else {
            previewElement.innerHTML = `<h4><i class="far fa-calendar-alt"></i> Return Date Preview</h4>
                                      <p>If borrowed for ${weeks} week${weeks > 1 ? 's' : ''}, the return date will be: <strong>${formattedReturnDate}</strong></p>`;
        }
    }
}
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(event) {
            const termsChecked = document.getElementById('terms').checked;
            if (!termsChecked) {
                event.preventDefault();
                alert('You must agree to the terms & conditions to proceed.');
                return false;
            }
            
            // Check if duration is selected
            const durationSelected = document.querySelector('input[name="duration"]:checked');
            if (!durationSelected) {
                event.preventDefault();
                alert('Please select a borrowing duration.');
                return false;
            }
            
            // Check if pickup date is in the future
            const pickupDate = new Date(document.getElementById('pickup_date').value);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            if (pickupDate < today) {
                event.preventDefault();
                alert('Pick-up date cannot be in the past. Please select a future date.');
                return false;
            }
            
            return true;
        });
    </script>
</body>
</html>
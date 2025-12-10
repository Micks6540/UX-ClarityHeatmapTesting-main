<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// Connect to database
include 'includes/db.php';

// Get user ID from session
$user_id = $_SESSION['user']['id'];

// Fetch user data from database
$sql = "SELECT username, id_number FROM users WHERE id = '$user_id'";
$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    $user = mysqli_fetch_assoc($result);
    $username = htmlspecialchars($user['username']);
    $id_number = htmlspecialchars($user['id_number']);
    
    // Get initials for profile picture
    $initials = '';
    $name_parts = explode(' ', $username);
    if (count($name_parts) >= 2) {
        $initials = strtoupper($name_parts[0][0] . $name_parts[count($name_parts)-1][0]);
    } else {
        $initials = strtoupper(substr($username, 0, 2));
    }
} else {
    // Fallback if user not found
    $username = "Unknown User";
    $id_number = "N/A";
    $initials = "??";
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Management</title>
    <style>
        body {
            margin: 0;
            padding: 20px;
            font-family: Arial, sans-serif;
            background: #f5f5f5;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .header {
            background: #214d25;
            color: white;
            padding: 20px 30px;
        }
        
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        
        .time {
            margin-top: 5px;
            font-size: 14px;
            opacity: 0.8;
        }
        
        .content {
            padding: 30px;
        }
        
        .profile-section {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .profile-pic {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: #e0e0e0;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: #666;
            background-color: #214d25;
            color: white;
            font-weight: bold;
        }
        
        .name {
            font-size: 20px;
            font-weight: bold;
            margin: 10px 0 5px;
            color: #214d25;
        }
        
        .id-number {
            font-size: 16px;
            color: #666;
            margin-bottom: 20px;
        }
        
        .edit-btn {
            background: #214d25;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .edit-btn:hover {
            background: #2e7d32;
        }
        
        .back-link {
            margin-top: 70px;
            text-align: center;
        }
        
        .back-link a {
            color: #214d25;
            text-decoration: none;
            font-weight: bold;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>Account Management</h1>
        </div>
        
        <!-- Content -->
        <div class="content">
            <!-- Profile Section -->
            <div class="profile-section">
                <div class="profile-pic">
                    <?php echo $initials; ?>
                </div>
                <div class="name"><?php echo $username; ?></div>
                <div class="id-number"><?php echo $id_number; ?></div>
                <button class="edit-btn">Edit profile</button>
            </div>
            
            <div class="back-link">
                <a href="index.php">← Back to Home</a>
            </div>
        </div>
    </div>
</body>
</html>
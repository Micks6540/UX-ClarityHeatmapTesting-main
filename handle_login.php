<?php
session_start();
include "includes/db.php";

$id_number = trim($_POST['id_number']);
$password  = $_POST['password'];

$sql = "SELECT id, username, id_number, password 
        FROM users 
        WHERE id_number = '$id_number'
        LIMIT 1";
        
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 1) {
    $user = mysqli_fetch_assoc($result);

    if (password_verify($password, $user['password'])) {

        // Store ONLY SAFE DATA in session
        $_SESSION['user'] = [
            "id"        => $user['id'],
            "username"  => $user['username'],
            "id_number" => $user['id_number']
        ];

        header("Location: index.php");
        exit;
    }
}

// If login fails:
header("Location: login.php?error=1");
exit;
?>

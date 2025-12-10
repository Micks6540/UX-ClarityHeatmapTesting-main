<?php
include "includes/db.php";

// Get form data
$username = $_POST['username'];
$id_number = $_POST['id_number'];
$password = $_POST['password'];
$confirm = $_POST['confirm_password'];

// Validate
if ($password !== $confirm) {
    die("Passwords do not match. <a href='login.php'>Try again</a>");
}

// Hash password
$hashed = password_hash($password, PASSWORD_DEFAULT);

// Handle ID picture upload
$upload_dir = "uploads/";
if (!is_dir($upload_dir)) { mkdir($upload_dir); }

$file_name = time() . "_" . basename($_FILES["id_picture"]["name"]);
$target_file = $upload_dir . $file_name;

move_uploaded_file($_FILES["id_picture"]["tmp_name"], $target_file);

// Insert into DB
$sql = "INSERT INTO users (username, id_number, password, id_picture)
        VALUES ('$username', '$id_number', '$hashed', '$file_name')";

if (mysqli_query($conn, $sql)) {
    header("Location: login.php?success=1");
    exit;
} else {
    echo "Error: " . mysqli_error($conn);
}

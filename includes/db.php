<?php
// Read credentials from Vercel environment
$host = getenv('DB_HOST');
$port = getenv('DB_PORT') ?: 3306;
$db   = getenv('DB_NAME');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');

// Connect to Aiven MySQL
$conn = mysqli_connect($host, $user, $pass, $db, $port);

// Check connection
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Optionally, you can preload books data into $data array
$data = [];
$books = [];
$res = mysqli_query($conn, "SELECT * FROM books ORDER BY book_id DESC");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $books[] = $row;
    }
}
$data['books'] = $books;

// Example genres
$data['genres'] = ['Fiction','English','Biology','Encyclopedia','Math & Statistics','Biography','Chemistry','History','Law & Politics'];
?>

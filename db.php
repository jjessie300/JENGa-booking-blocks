<?php
// Nikola
// credentials
$host     = "localhost";
$db       = "comp-307-db";
$user     = "cs307-user";
$password = "naQx0rMGgelmqwC5MGnr";

$conn = new mysqli($host, $user, $password, $db);

if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

$conn->set_charset('utf8mb4');

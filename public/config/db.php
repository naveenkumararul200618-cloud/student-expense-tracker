<?php

$host = "db01.dbhost.dev";
$user = "user_4559ayc5h";
$password = "p4559ayc5h";
$database = "db_4559ayc5h";
$port = "5051";

$conn = new mysqli($host, $user, $password, $database,$port);

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

?>
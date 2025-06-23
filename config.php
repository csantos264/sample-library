<?php

$host = "localhost";
$user = "root";
$password = "";
$database = "library_db";

$conn = new mysqli ($host, $user, $password, $database, 3306);

if ($conn->connect_error){
    die("Connection Failed: ". $conn->connect_error);
}
?>
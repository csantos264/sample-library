<?php

$host = "localhost";
$user = "root";
$password = "";
$database = "library_db";

$conn = new mysqli ($host, $user, $password, $database);

if ($conn->connect_error){
    die("Connection Failed: ". $conn->connect_error);
}
?>
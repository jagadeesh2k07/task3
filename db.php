<?php
$host     = "sql313.infinityfree.com";
$user     = "if0_42297065";
$password = "YOUR_ACTUAL_PASSWORD";  // click the eye icon to see it
$database = "if0_42297065_jagadeesh_task3";

$conn = mysqli_connect($host, $user, $password, $database);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
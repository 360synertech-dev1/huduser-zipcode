<?php

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'mls-test';

$mysqli = new mysqli($host, $username, $password, $database);

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

?>

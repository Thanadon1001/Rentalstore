<?php
$dsn = "pgsql:host=caboose.proxy.rlwy.net;port=52806;dbname=postgres";
$username = "postgres";
$password = "ikqjokIoIpnvGEzjITisIjFvFbVZkagO";

try {
    $conn = new PDO($dsn, $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
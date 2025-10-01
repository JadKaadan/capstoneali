<?php
// db.php - Imad Market Connection File

$servername = "localhost";
$username   = "u339875507_pharmacyallcap";
$password   = "-!68JjI09iJ";
$dbname     = "u339875507_pharmacyallcap";

try {
    $con = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>

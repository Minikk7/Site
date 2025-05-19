<?php
// Tietokantayhteys
$host = "localhost";
$dbname = "tietokonekauppa";
$username = "root"; // Default username for most local setups
$password = ""; // Default password is often empty for local development

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    // Asetetaan PDO error mode poikkeuksille
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Tietokantavirhe: " . $e->getMessage());
}
?>
<?php
$host = '158.69.48.109';        // ou IP de ton serveur MySQL
$dbname = 'dbdarquest2';       // nom de ta BD
$user = 'equipe2';             // adapte
$pass = '72ae8d4w';                 // adapte

$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die('Erreur connexion BD : ' . $e->getMessage());
}

?>
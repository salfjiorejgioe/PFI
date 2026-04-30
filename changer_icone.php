<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user']['idJoueur'])) {
    header("Location: login.php");
    exit;
}

$idJoueur = (int)$_SESSION['user']['idJoueur'];
$icone = $_POST['icone'] ?? 'icone1.png';

$iconesPermises = [
    'icone1.png',
    'icone2.png',
    'icone3.png',
    'icone4.png',
    'icone5.png',
    'icone6.png'
];

if (!in_array($icone, $iconesPermises)) {
    $icone = 'icone1.png';
}

$sql = "UPDATE Joueurs SET iconeProfil = ? WHERE idJoueur = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$icone, $idJoueur]);

$_SESSION['user']['iconeProfil'] = $icone;

header("Location: " . $_SERVER['HTTP_REFERER']);
exit;
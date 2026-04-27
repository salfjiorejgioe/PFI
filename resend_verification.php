<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mail_helper.php';

if (!isset($_SESSION['verification_email'])) {
    header('Location: signup.php');
    exit;
}

$email = $_SESSION['verification_email'];
$code = (string) random_int(100000, 999999);

$update = $pdo->prepare("
    UPDATE Joueurs
    SET emailCode = :emailCode
    WHERE courriel = :courriel
      AND emailVerifie = 0
");
$update->execute([
    ':emailCode' => $code,
    ':courriel' => $email
]);

$erreurMail = null;
if (envoyerCodeVerification($email, $code, $erreurMail)) {
    $_SESSION['auth_message'] = "Un nouveau code a été envoyé.";
} else {
    $_SESSION['auth_message'] = "Erreur d'envoi : " . $erreurMail;
}

header('Location: verify_notice.php');
exit;
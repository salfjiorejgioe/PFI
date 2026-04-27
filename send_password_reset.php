<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth_helper.php';
require_once __DIR__ . '/mail_helper.php';

exigerConnexion();

$idJoueur = (int) $_SESSION['user']['idJoueur'];

$stmt = $pdo->prepare("
    SELECT idJoueur, alias, courriel
    FROM Joueurs
    WHERE idJoueur = :idJoueur
    LIMIT 1
");
$stmt->execute([':idJoueur' => $idJoueur]);
$joueur = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$joueur || empty($joueur['courriel'])) {
    $_SESSION['profile_message'] = "Aucun courriel associé à ce compte.";
    header('Location: profile.php');
    exit;
}

$token = bin2hex(random_bytes(32));
$tokenHash = hash('sha256', $token);
$expire = date('Y-m-d H:i:s', time() + 3600);

$update = $pdo->prepare("
    UPDATE Joueurs
    SET resetToken = :resetToken,
        resetExpire = :resetExpire
    WHERE idJoueur = :idJoueur
");
$update->execute([
    ':resetToken' => $tokenHash,
    ':resetExpire' => $expire,
    ':idJoueur' => $joueur['idJoueur']
]);

$lien = 'http://158.69.48.57/~darquest2/Pfi/reset_password.php?token=' . urlencode($token);

$erreurMail = null;
if (envoyerLienReset($joueur['courriel'], $joueur['alias'], $lien, $erreurMail)) {
    $_SESSION['profile_message'] = "Un lien de changement de mot de passe a été envoyé à votre courriel.";
} else {
    $_SESSION['profile_message'] = "Erreur d'envoi du courriel : " . $erreurMail;
}

header('Location: profil.php');
exit;
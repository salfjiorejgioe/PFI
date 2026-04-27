<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';

function configurerMailer(): PHPMailer
{
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'zekonknigth@gmail.com';
    $mail->Password = 'sbyuhjluoomluilt';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    $mail->CharSet = 'UTF-8';
    $mail->setFrom('zekonknigth@gmail.com', 'Diddyquest');
    return $mail;
}

function envoyerCodeVerification($destinataire, $code, &$erreur = null)
{
    try {
        $mail = configurerMailer();
        $mail->addAddress($destinataire);
        $mail->isHTML(true);
        $mail->Subject = 'Code de vérification Darquest';
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; color: #111;'>
                <h2>Bienvenue sur Darquest</h2>
                <p>Merci pour votre inscription.</p>
                <p>Votre code de vérification est :</p>
                <p style='font-size: 28px; font-weight: bold; letter-spacing: 3px;'>
                    {$code}
                </p>
                <p>Entrez ce code sur la page de vérification pour activer votre compte.</p>
            </div>
        ";
        $mail->AltBody = "Votre code de vérification Darquest est : {$code}";
        $mail->send();
        return true;
    } catch (Exception $e) {
        $erreur = $mail->ErrorInfo ?? $e->getMessage();
        return false;
    }
}

function envoyerLienReset($destinataire, $alias, $lien, &$erreur = null)
{
    try {
        $mail = configurerMailer();
        $mail->addAddress($destinataire);
        $mail->isHTML(true);
        $mail->Subject = 'Changer votre mot de passe Darquest';
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; color: #111;'>
                <h2>Changement du mot de passe</h2>
                <p>Bonjour " . htmlspecialchars($alias) . ",</p>
                <p>Vous avez demandé un changement de mot de passe.</p>
                <p><a href='{$lien}'>Cliquez ici pour choisir un nouveau mot de passe</a></p>
                <p>Ce lien expire dans 1 heure et ne fonctionne qu'une seule fois.</p>
            </div>
        ";
        $mail->AltBody = "Changer votre mot de passe : {$lien}";
        $mail->send();
        return true;
    } catch (Exception $e) {
        $erreur = $mail->ErrorInfo ?? $e->getMessage();
        return false;
    }
}
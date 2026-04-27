<?php
function estConnecte()
{
    return isset($_SESSION['user']) && !empty($_SESSION['user']['idJoueur']);
}

function estVerifie()
{
    return estConnecte()
        && isset($_SESSION['user']['emailVerifie'])
        && (int) $_SESSION['user']['emailVerifie'] === 1;
}

function exigerConnexion()
{
    if (!estConnecte()) {
        $_SESSION['auth_message'] = "Veuillez vous connecter.";
        header('Location: login.php');
        exit;
    }
}

function exigerCompteVerifie()
{
    if (!estConnecte()) {
        $_SESSION['auth_message'] = "Veuillez vous connecter.";
        header('Location: login.php');
        exit;
    }

    if (!estVerifie()) {
        $_SESSION['auth_message'] = "Veuillez vérifier votre courriel pour utiliser cette fonctionnalité.";
        header('Location: verify_notice.php');
        exit;
    }
}
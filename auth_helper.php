<?php
require_once __DIR__ . '/session_config.php';

function estConnecte()
{
    return isset($_SESSION['user'])
        && isset($_SESSION['user']['idJoueur'])
        && (int) $_SESSION['user']['idJoueur'] > 0;
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
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? 'index.php';

        header('Location: login.php');
        exit;
    }
}

function exigerCompteVerifie()
{
    if (!estConnecte()) {
        $_SESSION['auth_message'] = "Veuillez vous connecter.";
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? 'index.php';

        header('Location: login.php');
        exit;
    }

    if (!estVerifie()) {
        $_SESSION['auth_message'] = "Veuillez vérifier votre courriel pour utiliser cette fonctionnalité.";
        header('Location: verify_notice.php');
        exit;
    }
}
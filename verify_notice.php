<?php
session_start();
$message = $_SESSION['auth_message'] ?? "Veuillez vérifier votre courriel pour continuer.";
unset($_SESSION['auth_message']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Vérification requise</title>
    <link rel="stylesheet" href="public/css/style.css">
</head>
<body>
<main class="auth-container signup-page">
    <h1>Vérification requise</h1>
    <div class="error">
        <p><?php echo htmlspecialchars($message); ?></p>
    </div>
    <p><a href="verify.php">Entrer le code</a></p>
    <p><a href="resend_verification.php">Renvoyer le code</a></p>
</main>
</body>
</html>
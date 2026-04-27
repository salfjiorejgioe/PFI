<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['verification_email'])) {
    header('Location: signup.php');
    exit;
}

$email = $_SESSION['verification_email'];
$alias = $_SESSION['verification_alias'] ?? '';
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code'] ?? '');

    if ($code === '') {
        $errors[] = "Le code est obligatoire.";
    } else {
        $stmt = $pdo->prepare("
            SELECT idJoueur
            FROM Joueurs
            WHERE courriel = :courriel
              AND emailCode = :code
              AND emailVerifie = 0
            LIMIT 1
        ");
        $stmt->execute([
            ':courriel' => $email,
            ':code' => $code
        ]);

        $joueur = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($joueur) {
            $update = $pdo->prepare("
                UPDATE Joueurs
                SET emailVerifie = 1,
                    emailCode = NULL
                WHERE idJoueur = :idJoueur
            ");
            $update->execute([
                ':idJoueur' => $joueur['idJoueur']
            ]);

            unset($_SESSION['verification_email'], $_SESSION['verification_alias']);
            $success = "Courriel vérifié avec succès. Vous pouvez maintenant vous connecter.";
        } else {
            $errors[] = "Code invalide.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Vérification du courriel</title>
    <link rel="stylesheet" href="public/css/style.css">
</head>
<body>
<main class="auth-container signup-page">
    <h1>Vérification du courriel</h1>

    <?php if ($errors): ?>
        <div class="error">
            <?php foreach ($errors as $e): ?>
                <p><?php echo htmlspecialchars($e); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="success">
            <p><?php echo htmlspecialchars($success); ?></p>
            <p><a href="login.php">Aller à la connexion</a></p>
        </div>
    <?php else: ?>
        <p>Un code a été envoyé à : <strong><?php echo htmlspecialchars($email); ?></strong></p>
        <?php if ($alias !== ''): ?>
            <p>Compte : <strong><?php echo htmlspecialchars($alias); ?></strong></p>
        <?php endif; ?>

        <form method="post" class="auth-form">
            <label for="code">Code de vérification</label>
            <input type="text" name="code" id="code" maxlength="6" required>
            <button type="submit">Vérifier</button>
        </form>

        <p><a href="resend_verification.php">Renvoyer le code</a></p>
    <?php endif; ?>
</main>
</body>
</html>
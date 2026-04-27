<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/db.php';

function h($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

$token = $_GET['token'] ?? $_POST['token'] ?? '';
$errors = [];
$success = '';

if ($token === '') {
    $errors[] = "Lien invalide : token manquant.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token !== '') {
    $password = trim($_POST['password'] ?? '');
    $confirm = trim($_POST['confirm_password'] ?? '');

    if ($password === '' || $confirm === '') {
        $errors[] = "Tous les champs sont obligatoires.";
    } elseif ($password !== $confirm) {
        $errors[] = "Les mots de passe ne correspondent pas.";
    } elseif (strlen($password) < 8) {
        $errors[] = "Le mot de passe doit contenir au moins 8 caractères.";
    } else {
        $tokenHash = hash('sha256', $token);

        try {
            $stmt = $pdo->prepare("
                SELECT idJoueur
                FROM Joueurs
                WHERE resetToken = :resetToken
                  AND resetExpire IS NOT NULL
                  AND resetExpire >= NOW()
                LIMIT 1
            ");
            $stmt->execute([':resetToken' => $tokenHash]);
            $joueur = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$joueur) {
                $errors[] = "Lien invalide ou expiré.";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);

                $update = $pdo->prepare("
                    UPDATE Joueurs
                    SET motDePasse = :motDePasse,
                        resetToken = NULL,
                        resetExpire = NULL
                    WHERE idJoueur = :idJoueur
                ");
                $update->execute([
                    ':motDePasse' => $hash,
                    ':idJoueur' => $joueur['idJoueur']
                ]);

                $success = "Votre mot de passe a été changé avec succès.";
            }
        } catch (PDOException $e) {
            $errors[] = "Erreur SQL : " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Changer le mot de passe</title>
    <link rel="stylesheet" href="public/css/style.css">
</head>
<body>
<main class="auth-container signup-page">
    <h1>Changer le mot de passe</h1>

    <?php if ($errors): ?>
        <div class="error">
            <?php foreach ($errors as $e): ?>
                <p><?php echo h($e); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="success">
            <p><?php echo h($success); ?></p>
            <p><a href="login.php">Retour à la connexion</a></p>
        </div>
    <?php elseif ($token !== ''): ?>
        <form method="post" class="auth-form">
            <input type="hidden" name="token" value="<?php echo h($token); ?>">

            <label for="password">Nouveau mot de passe</label>
            <input type="password" name="password" id="password" required>

            <label for="confirm_password">Confirmer le mot de passe</label>
            <input type="password" name="confirm_password" id="confirm_password" required>

            <button type="submit">Enregistrer</button>
        </form>
    <?php endif; ?>
</main>
</body>
</html>
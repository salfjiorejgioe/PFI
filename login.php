<?php
session_start();
require_once 'db.php';

$errors = [];

$auth_message = $_SESSION['auth_message'] ?? '';
unset($_SESSION['auth_message']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $alias    = trim($_POST['alias'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($alias === '' || $password === '') {
        $errors[] = "Alias et mot de passe sont obligatoires.";
    } else {

        $stmt = $pdo->prepare("
            SELECT
                idJoueur,
                alias,
                motDePasse,
                gold,
                argent,
                bronze,
                estMage,
                estAdmin,
                pointsVie,
                courriel,
                emailVerifie
            FROM Joueurs
            WHERE alias = :alias
            LIMIT 1
        ");

        $stmt->execute([':alias' => $alias]);
        $joueur = $stmt->fetch(PDO::FETCH_ASSOC);

        // Vérification du mot de passe
        if (!$joueur || !password_verify($password, $joueur['motDePasse'])) {
            $errors[] = "Alias ou mot de passe invalide.";
        } elseif ((int)$joueur['emailVerifie'] !== 1) {
            // Compte non vérifié → on redirige vers la page de vérification
            $_SESSION['verification_email'] = $joueur['courriel'];
            $_SESSION['verification_alias'] = $joueur['alias'];
            $_SESSION['auth_message'] = "Veuillez vérifier votre courriel avant de vous connecter.";
            header('Location: verify_notice.php');
            exit;
        } else {
            // Connexion OK et email vérifié → on enregistre tout dans la session
            $_SESSION['user'] = [
                'idJoueur'  => (int)$joueur['idJoueur'],
                'alias'     => $joueur['alias'],

                'or'        => (int)$joueur['gold'],
                'argent'    => (int)$joueur['argent'],
                'bronze'    => (int)$joueur['bronze'],

                'estMage'   => (int)$joueur['estMage'],
                'estAdmin'  => (int)$joueur['estAdmin'],
                'pointsVie' => (int)$joueur['pointsVie'],

                'courriel'      => $joueur['courriel'],
                'emailVerifie'  => (int)$joueur['emailVerifie'],
            ];

            if (!empty($_SESSION['redirect_after_login'])) {
                $dest = $_SESSION['redirect_after_login'];
                unset($_SESSION['redirect_after_login']);
                header("Location: " . $dest);
            } else {
                header('Location: index.php');
            }
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion - Darquest</title>
    <link rel="stylesheet" href="public/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>
<header>
    <h1>Connexion</h1>
</header>

<main class="darquest-login auth-container">
    <?php if ($auth_message): ?>
        <div class="error">
            <p><?php echo htmlspecialchars($auth_message); ?></p>
        </div>
    <?php endif; ?>

    <?php if ($errors): ?>
        <div class="error">
            <?php foreach ($errors as $e): ?>
                <p><?php echo htmlspecialchars($e); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form action="login.php" method="post" class="auth-form">
        <label for="alias">Alias (username)</label>
        <input type="text" name="alias" id="alias" required>

        <label for="password">Mot de passe</label>
        <input type="password" name="password" id="password" required>

        <button type="submit">Se connecter</button>
    </form>

    <p>Pas de compte ? <a href="signup.php">Créer un compte</a></p>

    <form action="index.php" method="get" style="margin-top: 1rem;">
        <button type="submit">Retour à la boutique</button>
    </form>
</main>
</body>
</html>

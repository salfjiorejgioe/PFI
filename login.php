<?php
session_start();
require_once 'db.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $alias    = trim($_POST['alias'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($alias === '' || $password === '') {
        $errors[] = "Alias et mot de passe sont obligatoires.";
    } else {

        $stmt = $pdo->prepare("
            SELECT idJoueur,
                   alias,
                   motDePasse,
                   gold,
                   argent,
                   bronze,
                   estMage,
                   estAdmin,
                   pointsVie
            FROM Joueurs
            WHERE alias = :alias
            LIMIT 1
        ");

        $stmt->execute([':alias' => $alias]);
        $joueur = $stmt->fetch(PDO::FETCH_ASSOC);

        // Vérification du mot de passe avec password_verify
        if (!$joueur || !password_verify($password, $joueur['motDePasse'])) {
            $errors[] = "Alias ou mot de passe invalide.";
        } else {
            // On normalise tout dans $_SESSION['user']
            $_SESSION['user'] = [
                'idJoueur' => (int)$joueur['idJoueur'],
                'alias'    => $joueur['alias'],

                'or'      => (int)$joueur['gold'],
                'argent'  => (int)$joueur['argent'],
                'bronze'  => (int)$joueur['bronze'],

                'estMage'  => (int)$joueur['estMage'],
                'estAdmin' => (int)$joueur['estAdmin'],
                'pointsVie' => (int)$joueur['pointsVie'] ?? 0,
            ];

            header('Location: index.php');
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
    <?php if ($errors): ?>
        <div class="error">
            <?php foreach ($errors as $e) echo "<p>".htmlspecialchars($e)."</p>"; ?>
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
</main>
</body>
</html>

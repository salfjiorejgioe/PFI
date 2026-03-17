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
                   estMage
            FROM Joueurs
            WHERE alias = :alias
            LIMIT 1
        ");
        $stmt->execute([':alias' => $alias]);
        $joueur = $stmt->fetch();

        if (!$joueur || !password_verify($password, $joueur['motDePasse'])) {
            $errors[] = "Alias ou mot de passe invalide.";
        } else {
            $_SESSION['joueur_id']       = $joueur['idJoueur'];
            $_SESSION['joueur_alias']    = $joueur['alias'];
            $_SESSION['joueur_or']       = (int)$joueur['gold'];
            $_SESSION['joueur_argent']   = (int)$joueur['argent'];
            $_SESSION['joueur_bronze']   = (int)$joueur['bronze'];
            $_SESSION['joueur_est_mage'] = (int)$joueur['estMage'] === 1;

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
</head>
<body>
<header>
    <h1>Connexion</h1>
</header>

<main class="auth-container">
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

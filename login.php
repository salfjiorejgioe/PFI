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

        //  j'ai changé les noms de colonnes pour avoir les memes noms que dans la BD
        $stmt = $pdo->prepare("
            SELECT idJoueur,
                   alias,
                   motDePasse,
                   gold,
                   argent,
                   bronze,
                   estMage,
                   estAdmin
            FROM Joueurs
            WHERE alias = :alias
            LIMIT 1
        ");

        $stmt->execute([':alias' => $alias]);
        $joueur = $stmt->fetch();

        // j'ai changé mpasee à motDePasse et j'ai gardé le password_verify pour comparer le mot de passe entré avec le hash stocké en BD
        if (!$joueur || !password_verify($password, $joueur['motDePasse'])) {
            $errors[] = "Alias ou mot de passe invalide.";
        } else {
            $_SESSION['joueur_id']       = $joueur['idJoueur'];
            $_SESSION['joueur_alias']    = $joueur['alias'];

            // j'ai changé Montant en or à gold argent et bronze comme dans la bd
            $_SESSION['joueur_or']       = (int)$joueur['gold'];
            $_SESSION['joueur_argent']   = (int)$joueur['argent'];
            $_SESSION['joueur_bronze']   = (int)$joueur['bronze'];

            // j'ai  changé nom session pour correspondre à index.php
            // j'en avais besoin pour le log in dans la page index.php pour afficher le nom du joueur et aussi pour vérifier si le joueur est mage ou pas pour afficher les items de magie
            $_SESSION['joueur_estMage']  = (int)$joueur['estMage'];
            $_SESSION['joueur_estAdmin'] = (int)$joueur['estAdmin'];

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
    <!-- Import Google Fonts for Roboto -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>
<header>
    <h1>Connexion</h1>
</header>

<!-- Scoped wrapper: CSS ONLY applies within this main -->
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

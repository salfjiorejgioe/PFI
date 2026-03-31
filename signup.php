<?php
session_start();
require_once 'db.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $alias = trim($_POST['alias'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if ($alias === '' || $password === '' || $nom === '') {
        $errors[] = "Alias, mot de passe et nom sont obligatoires.";
    }

    if (empty($errors)) {
        // Vérifier unicité alias
        $stmt = $pdo->prepare("SELECT idJoueur FROM Joueurs WHERE alias = :alias");
        $stmt->execute([':alias' => $alias]);

        //Verifier unicite courriel
        $stmtCourriel = $pdo->prepare("SELECT idJoueur FROM Joueurs WHERE courriel = :courriel");
        $stmtCourriel->execute([':courriel' => $email]);
        if ($stmt->fetch()) {
            $errors[] = "Cet alias est déjà utilisé.";
        }
        if (!empty($email)) {
            if ($stmtCourriel->fetch()) {
                $errors[] = "Ce courriel est déjà utilisé.";
            }
        } 
        if (empty($errors)){
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $insert = $pdo->prepare("
                INSERT INTO Joueurs (
                    alias,
                    nom,
                    prenom,
                    gold,
                    argent,
                    bronze,
                    estMage,
                    motDePasse,
                    courriel,
                    estAdmin,
                    pointsVie
                ) VALUES (
                    :alias,
                    :nom,
                    :prenom,
                    1000,      -- gold initial
                    1000,     -- argent initial
                    1000,     -- bronze initial
                    0,      -- estMage
                    :motDePasse,
                    :courriel,
                    0,      -- estAdmin
                    50      -- pointsVie
                )
            ");

            $insert->execute([
                ':alias' => $alias,
                ':nom' => $nom,
                ':prenom' => $prenom,
                ':motDePasse' => $hash,
                ':courriel' => $email
            ]);

            $success = "Compte créé. Vous pouvez maintenant vous connecter.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Inscription - Darquest</title>
    <link rel="stylesheet" href="public/css/style.css">
</head>

<body>
    <header>
        <h1>Création de compte</h1>
    </header>

    <main class="auth-container signup-page">
        <?php if ($errors): ?>
            <div class="error">
                <?php foreach ($errors as $e)
                    echo "<p>" . htmlspecialchars($e) . "</p>"; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success">
                <p><?php echo htmlspecialchars($success); ?></p>
                <p><a href="login.php">Aller à la connexion</a></p>
            </div>
        <?php endif; ?>

        <form action="signup.php" method="post" class="auth-form">
            <label for="alias">Alias (username)</label>
            <input type="text" name="alias" id="alias" required>

            <label for="nom">Nom</label>
            <input type="text" name="nom" id="nom" required>

            <label for="prenom">Prénom</label>
            <input type="text" name="prenom" id="prenom">

            <label for="email">Email (optionnel)</label>
            <input type="email" name="email" id="email">

            <label for="password">Mot de passe</label>
            <input type="password" name="password" id="password" required>

            <button type="submit">Créer le compte</button>
        </form>

        <p>Déjà un compte ? <a href="login.php">Connexion</a></p>
    </main>
</body>

</html>
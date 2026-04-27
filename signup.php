<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mail_helper.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $alias = trim($_POST['alias'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if ($alias === '' || $password === '' || $nom === '' || $email === '') {
        $errors[] = "Alias, mot de passe, nom et courriel sont obligatoires.";
    }

    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Le courriel n'est pas valide.";
    }

    if (strlen($password) < 8) {
        $errors[] = "Le mot de passe doit contenir au moins 8 caractères.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT idJoueur FROM Joueurs WHERE alias = :alias");
        $stmt->execute([':alias' => $alias]);

        $stmtCourriel = $pdo->prepare("SELECT idJoueur FROM Joueurs WHERE courriel = :courriel");
        $stmtCourriel->execute([':courriel' => $email]);

        if ($stmt->fetch()) {
            $errors[] = "Cet alias est déjà utilisé.";
        }

        if ($stmtCourriel->fetch()) {
            $errors[] = "Ce courriel est déjà utilisé.";
        }

        if (empty($errors)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $code = (string) random_int(100000, 999999);

            try {
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
                        pointsVie,
                        emailCode,
                        emailVerifie
                    ) VALUES (
                        :alias,
                        :nom,
                        :prenom,
                        1000,
                        1000,
                        1000,
                        0,
                        :motDePasse,
                        :courriel,
                        0,
                        50,
                        :emailCode,
                        0
                    )
                ");

                $insert->execute([
                    ':alias' => $alias,
                    ':nom' => $nom,
                    ':prenom' => $prenom,
                    ':motDePasse' => $hash,
                    ':courriel' => $email,
                    ':emailCode' => $code
                ]);

                $erreurMail = null;
                if (envoyerCodeVerification($email, $code, $erreurMail)) {
                    $_SESSION['verification_email'] = $email;
                    $_SESSION['verification_alias'] = $alias;
                    header('Location: verify.php');
                    exit;
                } else {
                    $errors[] = "Compte créé, mais le courriel n'a pas pu être envoyé : " . $erreurMail;
                }
            } catch (PDOException $e) {
                $errors[] = "Erreur lors de la création du compte.";
            }
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
            <?php foreach ($errors as $e): ?>
                <p><?php echo htmlspecialchars($e); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form action="signup.php" method="post" class="auth-form">
        <label for="alias">Alias</label>
        <input type="text" name="alias" id="alias" required value="<?php echo htmlspecialchars($_POST['alias'] ?? ''); ?>">

        <label for="nom">Nom</label>
        <input type="text" name="nom" id="nom" required value="<?php echo htmlspecialchars($_POST['nom'] ?? ''); ?>">

        <label for="prenom">Prénom</label>
        <input type="text" name="prenom" id="prenom" value="<?php echo htmlspecialchars($_POST['prenom'] ?? ''); ?>">

        <label for="email">Courriel</label>
        <input type="email" name="email" id="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">

        <label for="password">Mot de passe</label>
        <input type="password" name="password" id="password" required>

        <button type="submit">Créer le compte</button>
    </form>

    <p>Déjà un compte ? <a href="login.php">Connexion</a></p>
</main>
</body>
</html>

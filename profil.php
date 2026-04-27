<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth_helper.php';
require_once __DIR__ . '/mail_helper.php';

exigerConnexion();

if (!isset($_SESSION['user']) || !isset($_SESSION['user']['idJoueur'])) {
    header('Location: login.php');
    exit;
}

function h($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

$idJoueur = (int) $_SESSION['user']['idJoueur'];
$successMessage = '';
$errorMessage = '';

if (!empty($_SESSION['profile_message'])) {
    $successMessage = $_SESSION['profile_message'];
    unset($_SESSION['profile_message']);
}

$stmt = $pdo->prepare("
    SELECT idJoueur, alias, prenom, nom, courriel, emailVerifie
    FROM Joueurs
    WHERE idJoueur = ?
");
$stmt->execute([$idJoueur]);
$joueur = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$joueur) {
    die("Joueur introuvable.");
}

$alias = $joueur['alias'];
$prenom = $joueur['prenom'];
$nom = $joueur['nom'];
$courriel = $joueur['courriel'];
$emailVerifie = (int) $joueur['emailVerifie'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $alias = trim($_POST['alias'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $courriel = trim($_POST['courriel'] ?? '');

    if ($alias === '' || $prenom === '' || $nom === '' || $courriel === '') {
        $errorMessage = "Tous les champs obligatoires doivent être remplis.";
    } elseif (!filter_var($courriel, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = "Le courriel n'est pas valide.";
    } else {
        try {
            $stmtAlias = $pdo->prepare("
                SELECT idJoueur
                FROM Joueurs
                WHERE alias = ?
                  AND idJoueur != ?
                LIMIT 1
            ");
            $stmtAlias->execute([$alias, $idJoueur]);

            if ($stmtAlias->fetch()) {
                $errorMessage = "Cet alias est déjà utilisé.";
            } else {
                $stmtCourriel = $pdo->prepare("
                    SELECT idJoueur
                    FROM Joueurs
                    WHERE courriel = ?
                      AND idJoueur != ?
                    LIMIT 1
                ");
                $stmtCourriel->execute([$courriel, $idJoueur]);

                if ($stmtCourriel->fetch()) {
                    $errorMessage = "Ce courriel est déjà utilisé.";
                } else {
                    $ancienCourriel = $joueur['courriel'];
                    $courrielModifie = ($courriel !== $ancienCourriel);

                    if ($courrielModifie) {
                        $code = (string) random_int(100000, 999999);

                        $update = $pdo->prepare("
                            UPDATE Joueurs
                            SET alias = ?, prenom = ?, nom = ?, courriel = ?, emailVerifie = 0, emailCode = ?
                            WHERE idJoueur = ?
                        ");
                        $update->execute([$alias, $prenom, $nom, $courriel, $code, $idJoueur]);

                        $erreurMail = null;
                        if (envoyerCodeVerification($courriel, $code, $erreurMail)) {
                            $_SESSION['verification_email'] = $courriel;
                            $_SESSION['verification_alias'] = $alias;
                            $_SESSION['auth_message'] = "Ton courriel a été modifié. Vérifie-le pour continuer à utiliser toutes les fonctionnalités.";

                            $_SESSION['user']['alias'] = $alias;
                            $_SESSION['user']['prenom'] = $prenom;
                            $_SESSION['user']['nom'] = $nom;
                            $_SESSION['user']['courriel'] = $courriel;
                            $_SESSION['user']['emailVerifie'] = 0;

                            header('Location: verify_notice.php');
                            exit;
                        } else {
                            $errorMessage = "Profil mis à jour, mais le courriel de vérification n'a pas pu être envoyé : " . $erreurMail;
                        }
                    } else {
                        $update = $pdo->prepare("
                            UPDATE Joueurs
                            SET alias = ?, prenom = ?, nom = ?, courriel = ?
                            WHERE idJoueur = ?
                        ");
                        $update->execute([$alias, $prenom, $nom, $courriel, $idJoueur]);

                        $_SESSION['user']['alias'] = $alias;
                        $_SESSION['user']['prenom'] = $prenom;
                        $_SESSION['user']['nom'] = $nom;
                        $_SESSION['user']['courriel'] = $courriel;

                        $successMessage = "Profil mis à jour avec succès.";
                    }

                    $stmt = $pdo->prepare("
                        SELECT idJoueur, alias, prenom, nom, courriel, emailVerifie
                        FROM Joueurs
                        WHERE idJoueur = ?
                    ");
                    $stmt->execute([$idJoueur]);
                    $joueur = $stmt->fetch(PDO::FETCH_ASSOC);

                    $alias = $joueur['alias'];
                    $prenom = $joueur['prenom'];
                    $nom = $joueur['nom'];
                    $courriel = $joueur['courriel'];
                    $emailVerifie = (int) $joueur['emailVerifie'];
                }
            }
        } catch (PDOException $e) {
            $errorMessage = "Une erreur est survenue pendant la mise à jour du profil.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon profil</title>
    <link rel="stylesheet" href="public/css/style.css">
    <style>
        main.profil-page {
            width: min(760px, 92%);
            margin: 30px auto 60px;
        }

        .profil-card {
            background: rgba(10, 10, 10, 0.55);
            border: 1px solid rgba(255, 255, 255, 0.14);
            border-radius: 20px;
            padding: 24px;
            backdrop-filter: blur(10px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.25);
        }

        .profil-card h1 {
            margin: 0 0 10px 0;
            text-align: left;
            font-size: 2rem;
        }

        .profil-card p {
            margin: 0 0 20px 0;
            color: rgba(255,255,255,0.82);
        }

        .profil-message {
            padding: 12px 14px;
            border-radius: 12px;
            font-weight: 700;
            margin-bottom: 16px;
        }

        .profil-message.success {
            background: rgba(60, 160, 90, 0.20);
            border: 1px solid rgba(90, 200, 120, 0.35);
            color: #d8ffe0;
        }

        .profil-message.error {
            background: rgba(180, 50, 50, 0.20);
            border: 1px solid rgba(255, 90, 90, 0.35);
            color: #ffd8d8;
        }

        .profil-status {
            display: inline-block;
            margin-bottom: 18px;
            padding: 10px 14px;
            border-radius: 999px;
            font-weight: 700;
            font-size: 0.95rem;
        }

        .profil-status.verifie {
            background: rgba(60, 160, 90, 0.20);
            border: 1px solid rgba(90, 200, 120, 0.35);
            color: #d8ffe0;
        }

        .profil-status.non-verifie {
            background: rgba(180, 50, 50, 0.20);
            border: 1px solid rgba(255, 90, 90, 0.35);
            color: #ffd8d8;
        }

        .profil-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .profil-field {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .profil-field.full {
            grid-column: 1 / -1;
        }

        .profil-field label {
            font-weight: 700;
            color: #fff;
        }

        .profil-field input {
            width: 100%;
            padding: 12px 14px;
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,0.18);
            background: rgba(0,0,0,0.35);
            color: white;
            outline: none;
        }

        .profil-field input::placeholder {
            color: rgba(255,255,255,0.6);
        }

        .profil-field input:focus {
            border-color: #f6d26a;
            box-shadow: 0 0 0 3px rgba(246, 210, 106, 0.15);
        }

        .profil-help {
            font-size: 0.9rem;
            color: rgba(255,255,255,0.68);
        }

        .profil-actions {
            grid-column: 1 / -1;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 8px;
            flex-wrap: wrap;
        }

        .profil-btn {
            border: none;
            border-radius: 12px;
            padding: 12px 18px;
            font-size: 1rem;
            font-weight: 800;
            cursor: pointer;
            transition: transform 0.15s ease, filter 0.15s ease;
        }

        .profil-btn:hover {
            filter: brightness(1.03);
            transform: translateY(-1px);
        }

        .profil-btn.primary {
            color: #2a1b00;
            background: linear-gradient(135deg, #d4af37, #f6d365);
            box-shadow: 0 8px 20px rgba(212, 175, 55, 0.25);
        }

        .profil-btn.secondary {
            color: white;
            background: linear-gradient(135deg, #2f6fed, #5c8dff);
            box-shadow: 0 8px 20px rgba(47, 111, 237, 0.25);
        }

        .profil-inline-form {
            margin-top: 18px;
        }

        .profil-inline-form button {
            width: 100%;
        }

        @media (max-width: 640px) {
            .profil-card {
                padding: 18px;
            }

            .profil-card h1 {
                font-size: 1.6rem;
            }

            .profil-form {
                grid-template-columns: 1fr;
            }

            .profil-actions {
                justify-content: stretch;
            }

            .profil-btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php include_once 'template/header.php'; ?>

    <main class="profil-page">
        <div class="profil-card">
            <h1>Mon profil</h1>
            <p>Modifie tes informations personnelles ici.</p>

            <?php if ($emailVerifie === 1): ?>
                <div class="profil-status verifie">Courriel vérifié</div>
            <?php else: ?>
                <div class="profil-status non-verifie">Courriel non vérifié</div>
            <?php endif; ?>

            <?php if ($successMessage !== ''): ?>
                <div class="profil-message success"><?php echo h($successMessage); ?></div>
            <?php endif; ?>

            <?php if ($errorMessage !== ''): ?>
                <div class="profil-message error"><?php echo h($errorMessage); ?></div>
            <?php endif; ?>

            <form method="post" class="profil-form">
                <div class="profil-field">
                    <label for="alias">Alias</label>
                    <input
                        type="text"
                        id="alias"
                        name="alias"
                        value="<?php echo h($alias); ?>"
                        required
                    >
                </div>

                <div class="profil-field">
                    <label for="courriel">Courriel</label>
                    <input
                        type="email"
                        id="courriel"
                        name="courriel"
                        value="<?php echo h($courriel); ?>"
                        required
                    >
                </div>

                <div class="profil-field">
                    <label for="prenom">Prénom</label>
                    <input
                        type="text"
                        id="prenom"
                        name="prenom"
                        value="<?php echo h($prenom); ?>"
                        required
                    >
                </div>

                <div class="profil-field">
                    <label for="nom">Nom</label>
                    <input
                        type="text"
                        id="nom"
                        name="nom"
                        value="<?php echo h($nom); ?>"
                        required
                    >
                </div>

                <div class="profil-field full">
                    <div class="profil-help">
                        Pour changer ton mot de passe, utilise le bouton ci-dessous. Un lien sécurisé sera envoyé à ton courriel.
                    </div>
                </div>

                <div class="profil-actions">
                    <button type="submit" class="profil-btn primary">Enregistrer les modifications</button>
                </div>
            </form>

            <form action="send_password_reset.php" method="post" class="profil-inline-form">
                <button type="submit" class="profil-btn secondary">Changer le mot de passe</button>
            </form>

            <?php if ($emailVerifie !== 1): ?>
                <form action="resend_verification.php" method="get" class="profil-inline-form">
                    <button type="submit" class="profil-btn secondary">Renvoyer le code de vérification</button>
                </form>
            <?php endif; ?>
        </div>
    </main>

    <?php include_once 'template/footer.php'; ?>
</body>
</html>

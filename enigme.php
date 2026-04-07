<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user']) || !isset($_SESSION['user']['idJoueur'])) {
    header('Location: login.php');
    exit;
}

function h($texte)
{
    return htmlspecialchars((string) $texte, ENT_QUOTES, 'UTF-8');
}

$idJoueur = (int) $_SESSION['user']['idJoueur'];

/* =========================
   RÉPONDRE À UNE ÉNIGME
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'answer_enigme') {
    $idEnigme = (int) $_POST['idEnigme'];
    $idReponse = (int) $_POST['idReponse'];

    try {
        // Vérifier si le joueur a déjà répondu
        $stmt = $pdo->prepare("
            SELECT estReussie
            FROM Statistiques
            WHERE idJoueur = ? AND idQuestion = ?
        ");
        $stmt->execute([$idJoueur, $idEnigme]);
        $stat = $stmt->fetch();

        if ($stat) {
            $_SESSION['enigme_message'] = "Tu as déjà répondu à cette quête.";
            $_SESSION['enigme_message_type'] = "error";
            header("Location: enigme.php");
            exit;
        }

        // Aller chercher la réponse choisie + les infos de l'énigme
        $stmt = $pdo->prepare("
            SELECT 
                r.idReponse,
                r.estBonneReponse,
                r.reponse,
                e.idEnigme,
                e.idCategorie,
                e.Recompense,
                e.Punition,
                e.enonce
            FROM Reponses r
            INNER JOIN Enigmes e ON e.idEnigme = r.idEnigme
            WHERE r.idReponse = ? AND e.idEnigme = ?
        ");
        $stmt->execute([$idReponse, $idEnigme]);
        $choix = $stmt->fetch();

        if (!$choix) {
            $_SESSION['enigme_message'] = "Réponse invalide.";
            $_SESSION['enigme_message_type'] = "error";
            header("Location: enigme.php");
            exit;
        }

        $bonne = (int) $choix['estBonneReponse'] === 1;
        $recompense = (int) $choix['Recompense'];
        $punition = (int) $choix['Punition'];

        $pdo->beginTransaction();

        if ($bonne) {
            // Donner la récompense en or
            $stmt = $pdo->prepare("
                UPDATE Joueurs
                SET gold = gold + ?
                WHERE idJoueur = ?
            ");
            $stmt->execute([$recompense, $idJoueur]);

            // Sauvegarder la réussite
            $stmt = $pdo->prepare("
                INSERT INTO Statistiques (idJoueur, idQuestion, estReussie)
                VALUES (?, ?, 1)
            ");
            $stmt->execute([$idJoueur, $idEnigme]);

            // Vérifier combien d'énigmes magiques réussies
            $stmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM Statistiques s
                INNER JOIN Enigmes e ON e.idEnigme = s.idQuestion
                WHERE s.idJoueur = ?
                AND s.estReussie = 1
                AND e.idCategorie = 'M'
            ");
            $stmt->execute([$idJoueur]);
            $nbReussitesMagiques = (int) $stmt->fetchColumn();

            // Vérifier si le joueur est déjà mage
            $stmt = $pdo->prepare("
                SELECT estMage
                FROM Joueurs
                WHERE idJoueur = ?
            ");
            $stmt->execute([$idJoueur]);
            $estMage = (int) $stmt->fetchColumn();

            $_SESSION['enigme_message'] = "Bonne réponse ! Tu gagnes {$recompense} or.";

            // Devenir mage après 3 réussites magiques
            if ($nbReussitesMagiques >= 3 && $estMage === 0) {
                $stmt = $pdo->prepare("
                    UPDATE Joueurs
                    SET estMage = 1
                    WHERE idJoueur = ?
                ");
                $stmt->execute([$idJoueur]);

                $_SESSION['user']['estMage'] = 1;
                $_SESSION['enigme_message'] .= " 🧙 Tu es maintenant devenu Mage !";
            }

            // Mettre à jour la session si elle contient l'or
            if (isset($_SESSION['user']['or'])) {
                $_SESSION['user']['or'] += $recompense;
            }

            $_SESSION['enigme_message_type'] = "success";

        } else {
            // Infliger la punition sur les points de vie
            $stmt = $pdo->prepare("
                UPDATE Joueurs
                SET pointsVie = GREATEST(pointsVie - ?, 0)
                WHERE idJoueur = ?
            ");
            $stmt->execute([$punition, $idJoueur]);

            // Sauvegarder l'échec
            $stmt = $pdo->prepare("
                INSERT INTO Statistiques (idJoueur, idQuestion, estReussie)
                VALUES (?, ?, 0)
            ");
            $stmt->execute([$idJoueur, $idEnigme]);

            // Mettre à jour la session si elle contient les PV
            if (isset($_SESSION['user']['pointsVie'])) {
                $_SESSION['user']['pointsVie'] = max(0, (int) $_SESSION['user']['pointsVie'] - $punition);
            }

            $_SESSION['enigme_message'] = "Mauvaise réponse... Tu perds {$punition} point(s) de vie.";
            $_SESSION['enigme_message_type'] = "error";
        }

        $pdo->commit();

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $_SESSION['enigme_message'] = "Erreur : " . $e->getMessage();
        $_SESSION['enigme_message_type'] = "error";
    }

    header("Location: enigme.php");
    exit;
}

/* =========================
   INFORMATIONS DU JOUEUR
========================= */
$stmt = $pdo->prepare("
    SELECT gold, pointsVie, alias
    FROM Joueurs
    WHERE idJoueur = ?
");
$stmt->execute([$idJoueur]);
$joueur = $stmt->fetch();

$gold = $joueur ? (int) $joueur['gold'] : 0;
$pointsVie = $joueur ? (int) $joueur['pointsVie'] : 0;
$alias = $joueur ? $joueur['alias'] : 'Joueur';

/* =========================
   QUÊTES DISPONIBLES
========================= */
$stmt = $pdo->prepare("
    SELECT 
        e.idEnigme,
        e.enonce,
        e.difficulte,
        e.Recompense,
        e.Punition,
        c.nomCategorie
    FROM Enigmes e
    INNER JOIN Categories c ON c.idCategorie = e.idCategorie
    LEFT JOIN Statistiques s
        ON s.idQuestion = e.idEnigme
       AND s.idJoueur = ?
    WHERE s.idQuestion IS NULL
    ORDER BY e.idEnigme DESC
");
$stmt->execute([$idJoueur]);
$enigmesDisponibles = $stmt->fetchAll();

/* =========================
   OUVERTURE DU POPUP
========================= */
$enigmeOuverte = null;
$reponsesEnigmeOuverte = [];

if (isset($_GET['open'])) {
    $idEnigmeOuverte = (int) $_GET['open'];

    $stmt = $pdo->prepare("
        SELECT 
            e.idEnigme,
            e.enonce,
            e.difficulte,
            e.Recompense,
            e.Punition,
            c.nomCategorie
        FROM Enigmes e
        INNER JOIN Categories c ON c.idCategorie = e.idCategorie
        LEFT JOIN Statistiques s
            ON s.idQuestion = e.idEnigme
           AND s.idJoueur = ?
        WHERE e.idEnigme = ?
          AND s.idQuestion IS NULL
    ");
    $stmt->execute([$idJoueur, $idEnigmeOuverte]);
    $enigmeOuverte = $stmt->fetch();

    if ($enigmeOuverte) {
        $stmt = $pdo->prepare("
            SELECT idReponse, reponse
            FROM Reponses
            WHERE idEnigme = ?
            ORDER BY idReponse
        ");
        $stmt->execute([$idEnigmeOuverte]);
        $reponsesEnigmeOuverte = $stmt->fetchAll();
    }
}

$message = $_SESSION['enigme_message'] ?? "";
$messageType = $_SESSION['enigme_message_type'] ?? "";
unset($_SESSION['enigme_message'], $_SESSION['enigme_message_type']);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quêtes / Énigmes</title>
    <link rel="stylesheet" href="public/css/style.css">
    <style>
        main {
            width: min(1200px, 92%);
            margin: 30px auto 60px auto;
        }

        .top-box,
        .enigme-card,
        .popup-content {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 18px;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.28);
        }

        .top-box {
            padding: 24px;
            margin-bottom: 24px;
        }

        .top-box h1 {
            margin-top: 0;
            color: #f4d27a;
        }

        .joueur-stats {
            display: flex;
            gap: 18px;
            flex-wrap: wrap;
            margin-top: 14px;
        }

        .stat-pill {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.10);
            border-radius: 999px;
            padding: 10px 14px;
            font-weight: bold;
        }

        .mage-badge {
            display: inline-block;
            padding: 10px 14px;
            border-radius: 999px;
            font-weight: bold;
            background: linear-gradient(135deg, #7b2ff7, #f107a3);
            color: white;
            box-shadow: 0 0 12px rgba(123, 47, 247, 0.35);
        }

        .message {
            padding: 12px 14px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-weight: bold;
        }

        .message.success {
            background: rgba(40, 160, 80, 0.20);
            color: #d9ffe2;
            border: 1px solid rgba(70, 220, 120, 0.30);
        }

        .message.error {
            background: rgba(180, 50, 50, 0.20);
            color: #ffdada;
            border: 1px solid rgba(255, 80, 80, 0.30);
        }

        .enigmes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 22px;
        }

        .enigme-card {
            padding: 20px;
        }

        .enigme-card h2 {
            margin-top: 0;
            color: #f4d27a;
            font-size: 1.3rem;
        }

        .enigme-meta {
            margin: 12px 0;
            display: grid;
            gap: 8px;
            color: #e9e9e9;
        }

        .enigme-preview {
            margin: 14px 0;
            color: #ddd;
            line-height: 1.5;
        }

        .btn-row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 16px;
        }

        .btn {
            border: none;
            border-radius: 12px;
            padding: 10px 14px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .btn-accept {
            background: linear-gradient(135deg, #d4af37, #f6d365);
            color: #2d2100;
        }

        .vide {
            padding: 24px;
            text-align: center;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.05);
        }

        .popup-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.72);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 999;
            padding: 20px;
        }

        .popup-content {
            width: min(760px, 100%);
            padding: 24px;
            position: relative;
        }

        .popup-content h2 {
            margin-top: 0;
            color: #f4d27a;
        }

        .popup-close {
            position: absolute;
            top: 14px;
            right: 14px;
            background: transparent;
            border: none;
            color: white;
            font-size: 1.4rem;
            cursor: pointer;
        }

        .popup-enonce {
            background: rgba(255, 255, 255, 0.06);
            border-radius: 14px;
            padding: 16px;
            margin: 16px 0 20px 0;
            line-height: 1.6;
        }

        .reponses-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .reponse-btn {
            width: 100%;
            text-align: left;
            padding: 18px;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.14);
            background: rgba(255, 255, 255, 0.07);
            color: white;
            font-size: 1rem;
            cursor: pointer;
            transition: transform 0.15s ease, background 0.15s ease;
        }

        .reponse-btn:hover {
            transform: translateY(-2px);
            background: rgba(255, 255, 255, 0.12);
        }

        @media (max-width: 700px) {
            .reponses-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <?php include_once 'template/header.php'; ?>

    <main>
        <div class="top-box">
            <h1>Tableau des quêtes</h1>
            <p>Clique sur une quête pour la tenter. Une bonne réponse donne de l’or. Une mauvaise réponse enlève des
                points de vie.</p>

            <div class="joueur-stats">
                <div class="stat-pill">Joueur : <?= h($alias) ?></div>
                <div class="stat-pill">Or : <?= h($gold) ?></div>
                <div class="stat-pill">Points de vie : <?= h($pointsVie) ?></div>

                <?php if (!empty($_SESSION['user']['estMage']) && (int) $_SESSION['user']['estMage'] === 1): ?>
                    <div class="mage-badge">🧙 Mage</div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($message !== ""): ?>
            <div class="message <?= $messageType === "success" ? "success" : "error" ?>">
                <?= h($message) ?>
            </div>
        <?php endif; ?>

        <?php if (empty($enigmesDisponibles)): ?>
            <div class="vide">
                Aucune quête disponible pour le moment.
            </div>
        <?php else: ?>
            <div class="enigmes-grid">
                <?php foreach ($enigmesDisponibles as $enigme): ?>
                    <div class="enigme-card">
                        <h2><?= h($enigme['nomCategorie']) ?></h2>

                        <div class="enigme-meta">
                            <div><strong>Difficulté :</strong> <?= h($enigme['difficulte']) ?></div>
                            <div><strong>Récompense :</strong> <?= h($enigme['Recompense']) ?> or</div>
                            <div><strong>Punition :</strong> <?= h($enigme['Punition']) ?> dégât(s)</div>
                        </div>

                        <div class="enigme-preview">
                            <?= h(mb_strimwidth($enigme['enonce'], 0, 120, '...')) ?>
                        </div>

                        <div class="btn-row">
                            <a class="btn btn-accept" href="enigme.php?open=<?= (int) $enigme['idEnigme'] ?>">Tenter la
                                quête</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <?php if ($enigmeOuverte): ?>
        <div class="popup-overlay">
            <div class="popup-content">
                <button class="popup-close" onclick="window.location.href='enigme.php'">✕</button>

                <h2><?= h($enigmeOuverte['nomCategorie']) ?></h2>

                <div class="enigme-meta">
                    <div><strong>Difficulté :</strong> <?= h($enigmeOuverte['difficulte']) ?></div>
                    <div><strong>Récompense :</strong> <?= h($enigmeOuverte['Recompense']) ?> or</div>
                    <div><strong>Punition :</strong> <?= h($enigmeOuverte['Punition']) ?> dégât(s)</div>
                </div>

                <div class="popup-enonce">
                    <?= nl2br(h($enigmeOuverte['enonce'])) ?>
                </div>

                <div class="reponses-grid">
                    <?php foreach ($reponsesEnigmeOuverte as $reponse): ?>
                        <form method="post">
                            <input type="hidden" name="action" value="answer_enigme">
                            <input type="hidden" name="idEnigme" value="<?= (int) $enigmeOuverte['idEnigme'] ?>">
                            <input type="hidden" name="idReponse" value="<?= (int) $reponse['idReponse'] ?>">

                            <button type="submit" class="reponse-btn">
                                <?= h($reponse['reponse']) ?>
                            </button>
                        </form>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

</body>

</html>
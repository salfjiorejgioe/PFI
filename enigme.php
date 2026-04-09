<?php
session_start();
require_once 'db.php';
require_once 'helpers.php';

if (!isset($_SESSION['user']) || !isset($_SESSION['user']['idJoueur'])) {
    header('Location: login.php');
    exit;
}

$idJoueur = (int) $_SESSION['user']['idJoueur'];

$enigmeOuverte = null;
$reponsesEnigmeOuverte = [];


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'answer_enigme') {
    $idEnigme = (int) $_POST['idEnigme'];
    $idReponse = (int) $_POST['idReponse'];

    try {
        $stmt = $pdo->prepare("
            SELECT idQuestion
            FROM Statistiques
            WHERE idJoueur = ? AND idQuestion = ?
        ");
        $stmt->execute([$idJoueur, $idEnigme]);

        if ($stmt->fetch()) {
            $_SESSION['enigme_message'] = "Tu as déjà répondu à cette quête.";
            $_SESSION['enigme_message_type'] = "error";
            header("Location: enigme.php");
            exit;
        }

        $stmt = $pdo->prepare("
            SELECT 
                r.estBonneReponse,
                e.idEnigme,
                e.idCategorie,
                e.difficulte,
                e.Recompense,
                e.Punition
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
        $idCategorie = $choix['idCategorie'];
        $difficulte = $choix['difficulte'];

        $pdo->beginTransaction();

        if ($bonne) {

            $stmt = $pdo->prepare("
                UPDATE Joueurs
                SET gold = gold + ?
                WHERE idJoueur = ?
            ");
            $stmt->execute([$recompense, $idJoueur]);

            $stmt = $pdo->prepare("
                INSERT INTO Statistiques (idJoueur, idQuestion, estReussie)
                VALUES (?, ?, 1)
            ");
            $stmt->execute([$idJoueur, $idEnigme]);

            $message = "Bonne réponse ! Tu gagnes {$recompense} or.";
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

            $stmt = $pdo->prepare("
                SELECT estMage
                FROM Joueurs
                WHERE idJoueur = ?
            ");
            $stmt->execute([$idJoueur]);
            $estMage = (int) $stmt->fetchColumn();

            if ($nbReussitesMagiques >= 3 && $estMage === 0) {
                $stmt = $pdo->prepare("
                    UPDATE Joueurs
                    SET estMage = 1
                    WHERE idJoueur = ?
                ");
                $stmt->execute([$idJoueur]);

                $_SESSION['user']['estMage'] = 1;
                $message .= " 🧙 Tu es maintenant devenu Mage !";
            }

            $stmt = $pdo->prepare("
                SELECT e.difficulte
                FROM Statistiques s
                INNER JOIN Enigmes e ON e.idEnigme = s.idQuestion
                WHERE s.idJoueur = ?
                AND s.estReussie = 1
                ORDER BY s.idQuestion DESC
                LIMIT 3
            ");
            $stmt->execute([$idJoueur]);
            $troisDernieres = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (count($troisDernieres) === 3 &&
                $troisDernieres[0] === 'D' &&
                $troisDernieres[1] === 'D' &&
                $troisDernieres[2] === 'D') {

                $stmt = $pdo->prepare("
                    UPDATE Joueurs
                    SET gold = gold + 100
                    WHERE idJoueur = ?
                ");
                $stmt->execute([$idJoueur]);

                $message .= " Bonus ! 3 quêtes difficiles réussies : +100 or.";
                $recompense += 100;
            }

            // Mettre à jour session
            if (isset($_SESSION['user']['or'])) {
                $_SESSION['user']['or'] += $recompense;
            }

            $_SESSION['enigme_message'] = $message;
            $_SESSION['enigme_message_type'] = "success";

        } else {
            // Enlever points de vie
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
   INFOS JOUEUR
========================= */
$stmt = $pdo->prepare("
    SELECT gold, pointsVie, alias, estMage
    FROM Joueurs
    WHERE idJoueur = ?
");
$stmt->execute([$idJoueur]);
$joueur = $stmt->fetch();

$gold = $joueur ? (int) $joueur['gold'] : 0;
$pointsVie = $joueur ? (int) $joueur['pointsVie'] : 0;
$alias = $joueur ? $joueur['alias'] : 'Joueur';
$estMage = $joueur ? (int) $joueur['estMage'] : 0;

if (isset($_GET['piger'])) {
    $typePige = $_GET['piger'];
    
$sql = "
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
    WHERE (s.idQuestion IS NULL OR s.estReussie = 0)
";

    if ($typePige === 'F') {
        $sql .= " AND e.difficulte = 'F' ";
    } elseif ($typePige === 'M') {
        $sql .= " AND e.difficulte = 'M' ";
    } elseif ($typePige === 'D') {
        $sql .= " AND e.difficulte = 'D' ";
    }

    $sql .= " ORDER BY RAND() LIMIT 1 ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idJoueur]);
    $enigmeOuverte = $stmt->fetch();

    if ($enigmeOuverte) {
        $stmt = $pdo->prepare("
            SELECT idReponse, reponse
            FROM Reponses
            WHERE idEnigme = ?
            ORDER BY idReponse
        ");
        $stmt->execute([$enigmeOuverte['idEnigme']]);
        $reponsesEnigmeOuverte = $stmt->fetchAll();
    } else {
        $_SESSION['enigme_message'] = "Aucune quête disponible pour ce choix.";
        $_SESSION['enigme_message_type'] = "error";
        header("Location: enigme.php");
        exit;
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
            width: min(1100px, 92%);
            margin: 30px auto 60px auto;
            text-align: center;
        }

        .top-box,
        .popup-content {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 18px;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.28);
        }

        .top-box {
            padding: 30px;
            margin-bottom: 24px;
        }

        .top-box h1 {
            margin-top: 0;
            color: #f4d27a;
        }

        .joueur-stats {
            display: flex;
            justify-content: center;
            gap: 18px;
            flex-wrap: wrap;
            margin-top: 18px;
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

        .buttons-box {
            display: flex;
            justify-content: center;
            gap: 14px;
            flex-wrap: wrap;
            margin-top: 24px;
        }

        .btn-piger {
            display: inline-block;
            padding: 16px 24px;
            border-radius: 16px;
            text-decoration: none;
            font-weight: bold;
            font-size: 1rem;
            background: linear-gradient(135deg, #d4af37, #f6d365);
            color: #2d2100;
            box-shadow: 0 8px 24px rgba(212, 175, 55, 0.35);
        }

        .btn-piger:hover {
            transform: translateY(-2px);
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
            text-align: left;
        }

        .popup-content h2 {
            margin-top: 0;
            color: #f4d27a;
            text-align: center;
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

        .enigme-meta {
            margin: 14px 0;
            display: grid;
            gap: 8px;
            color: #e9e9e9;
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
        <h1>Quêtes</h1>
        <p>Choisis le type de quête que tu veux piger.</p>

        <div class="joueur-stats">
            <div class="stat-pill">Joueur : <?= h($alias) ?></div>
            <div class="stat-pill">Or : <?= h($gold) ?></div>
            <div class="stat-pill">Points de vie : <?= h($pointsVie) ?></div>

            <?php if ($estMage === 1): ?>
                <div class="mage-badge">🧙 Mage</div>
            <?php endif; ?>
        </div>

        <div class="buttons-box">
            <a class="btn-piger" href="enigme.php?piger=alea">Piger une quête aléatoire</a>
            <a class="btn-piger" href="enigme.php?piger=F">Piger une quête facile</a>
            <a class="btn-piger" href="enigme.php?piger=M">Piger une quête moyenne</a>
            <a class="btn-piger" href="enigme.php?piger=D">Piger une quête difficile</a>
        </div>
    </div>

    <?php if ($message !== ""): ?>
        <div class="message <?= $messageType === "success" ? "success" : "error" ?>">
            <?= h($message) ?>
        </div>
    <?php endif; ?>
</main>

<?php if ($enigmeOuverte): ?>
    <div class="popup-overlay">
        <div class="popup-content">
            <button class="popup-close" onclick="window.location.href='enigme.php'">✕</button>

            <h2><?= h($enigmeOuverte['nomCategorie']) ?></h2>

            <div class="enigme-meta">
                <div><strong>Catégorie :</strong> <?= h($enigmeOuverte['nomCategorie']) ?></div>
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
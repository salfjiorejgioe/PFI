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
            SELECT estReussie
            FROM Statistiques
            WHERE idJoueur = ? AND idQuestion = ?
            LIMIT 1
        ");
        $stmt->execute([$idJoueur, $idEnigme]);
        $ancienneTentative = $stmt->fetch();

        if ($ancienneTentative && (int) $ancienneTentative['estReussie'] === 1) {
            $_SESSION['enigme_message'] = "Tu as déjà réussi cette quête.";
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
        $difficulte = $choix['difficulte'];

        $pdo->beginTransaction();

        if ($bonne) {
            $colonneRecompense = 'gold';
            $nomMonnaie = 'or';

            if ($difficulte === 'F') {
                $colonneRecompense = 'bronze';
                $nomMonnaie = 'bronze';
            } elseif ($difficulte === 'M') {
                $colonneRecompense = 'argent';
                $nomMonnaie = 'argent';
            } elseif ($difficulte === 'D') {
                $colonneRecompense = 'gold';
                $nomMonnaie = 'or';
            }

            $stmt = $pdo->prepare("
                UPDATE Joueurs
                SET {$colonneRecompense} = {$colonneRecompense} + ?
                WHERE idJoueur = ?
            ");
            $stmt->execute([$recompense, $idJoueur]);

            $stmt = $pdo->prepare("
                INSERT INTO Statistiques (idJoueur, idQuestion, estReussie)
                VALUES (?, ?, 1)
                ON DUPLICATE KEY UPDATE estReussie = 1
            ");
            $stmt->execute([$idJoueur, $idEnigme]);

            $message = "Bonne réponse ! Tu gagnes {$recompense} {$nomMonnaie}.";

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
            $estMageActuel = (int) $stmt->fetchColumn();

            if ($nbReussitesMagiques >= 3 && $estMageActuel === 0) {
                $stmt = $pdo->prepare("
                    UPDATE Joueurs
                    SET estMage = 1
                    WHERE idJoueur = ?
                ");
                $stmt->execute([$idJoueur]);

                $_SESSION['user']['estMage'] = 1;
                $message .= " 🧙 Tu es maintenant devenu Mage !";
            }

            if (!isset($_SESSION['combo_difficiles'])) {
                $_SESSION['combo_difficiles'] = 0;
            }

            if ($difficulte === 'D') {
                $_SESSION['combo_difficiles']++;

                if ($_SESSION['combo_difficiles'] === 3) {
                    $stmt = $pdo->prepare("
                        UPDATE Joueurs
                        SET gold = gold + 100
                        WHERE idJoueur = ?
                    ");
                    $stmt->execute([$idJoueur]);

                    $message .= " Bonus ! 3 quêtes difficiles réussies de suite : +100 or.";

                    if (isset($_SESSION['user']['or'])) {
                        $_SESSION['user']['or'] += 100;
                    }

                    $_SESSION['combo_difficiles'] = 0;
                }
            } else {
                $_SESSION['combo_difficiles'] = 0;
            }

            if ($difficulte === 'F' && isset($_SESSION['user']['bronze'])) {
                $_SESSION['user']['bronze'] += $recompense;
            } elseif ($difficulte === 'M' && isset($_SESSION['user']['argent'])) {
                $_SESSION['user']['argent'] += $recompense;
            } elseif ($difficulte === 'D' && isset($_SESSION['user']['or'])) {
                $_SESSION['user']['or'] += $recompense;
            }

            $_SESSION['enigme_message'] = $message;
            $_SESSION['enigme_message_type'] = "success";
        } else {
            $stmt = $pdo->prepare("
                UPDATE Joueurs
                SET pointsVie = GREATEST(pointsVie - ?, 0)
                WHERE idJoueur = ?
            ");
            $stmt->execute([$punition, $idJoueur]);

            $stmt = $pdo->prepare("
                INSERT INTO Statistiques (idJoueur, idQuestion, estReussie)
                VALUES (?, ?, 0)
                ON DUPLICATE KEY UPDATE estReussie = 0
            ");
            $stmt->execute([$idJoueur, $idEnigme]);

            if (isset($_SESSION['user']['pointsVie'])) {
                $_SESSION['user']['pointsVie'] = max(0, (int) $_SESSION['user']['pointsVie'] - $punition);
            }

            $_SESSION['combo_difficiles'] = 0;
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
    SELECT gold, argent, bronze, pointsVie, alias, estMage
    FROM Joueurs
    WHERE idJoueur = ?
");
$stmt->execute([$idJoueur]);
$joueur = $stmt->fetch();

$gold = $joueur ? (int) $joueur['gold'] : 0;
$argent = $joueur ? (int) $joueur['argent'] : 0;
$bronze = $joueur ? (int) $joueur['bronze'] : 0;
$pointsVie = $joueur ? (int) $joueur['pointsVie'] : 0;
$alias = $joueur ? $joueur['alias'] : 'Joueur';
$estMage = $joueur ? (int) $joueur['estMage'] : 0;
$voirStats = isset($_GET['voir']) && $_GET['voir'] === 'stats';
$statsJoueur = null;

if ($voirStats) {
    $stmt = $pdo->prepare("
        SELECT
            j.alias,
            j.estMage,

            SUM(CASE WHEN e.difficulte = 'F' AND s.estReussie = 1 THEN 1 ELSE 0 END) AS faciles_reussies,
            SUM(CASE WHEN e.difficulte = 'F' THEN 1 ELSE 0 END) AS faciles_faites,

            SUM(CASE WHEN e.difficulte = 'M' AND s.estReussie = 1 THEN 1 ELSE 0 END) AS moyennes_reussies,
            SUM(CASE WHEN e.difficulte = 'M' THEN 1 ELSE 0 END) AS moyennes_faites,

            SUM(CASE WHEN e.difficulte = 'D' AND s.estReussie = 1 THEN 1 ELSE 0 END) AS difficiles_reussies,
            SUM(CASE WHEN e.difficulte = 'D' THEN 1 ELSE 0 END) AS difficiles_faites,

            SUM(CASE WHEN s.estReussie = 1 THEN 1 ELSE 0 END) AS total_reussies,
            COUNT(s.idQuestion) AS total_faites
        FROM Joueurs j
        LEFT JOIN Statistiques s ON s.idJoueur = j.idJoueur
        LEFT JOIN Enigmes e ON e.idEnigme = s.idQuestion
        WHERE j.idJoueur = ?
        GROUP BY j.idJoueur, j.alias, j.estMage
    ");
    $stmt->execute([$idJoueur]);
    $statsJoueur = $stmt->fetch();

    if ($statsJoueur) {
        $totalFaites = (int) $statsJoueur['total_faites'];
        $totalReussies = (int) $statsJoueur['total_reussies'];
        $statsJoueur['winrate'] = $totalFaites > 0
            ? round(($totalReussies / $totalFaites) * 100, 1)
            : 0;
    }
}

/* =========================
   PIGER UNE QUÊTE
========================= */
if (isset($_GET['piger'])) {
    $typePige = $_GET['piger'];

$minPV = 3;
if ($typePige === 'F') {
    $minPV = 3;
} elseif ($typePige === 'M') {
    $minPV = 6;
} elseif ($typePige === 'D') {
    $minPV = 10;
} elseif ($typePige === 'alea') {
    $minPV = 3;
} elseif ($typePige === 'magique') {
    $minPV = 3;
}

    if ($pointsVie < $minPV) {
        $_SESSION['enigme_message'] = "Pas assez de points de vie pour ce type de quête.";
        $_SESSION['enigme_message_type'] = "error";
        header("Location: enigme.php");
        exit;
    }

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
        WHERE e.Punition <= ?
          AND NOT EXISTS (
              SELECT 1
              FROM Statistiques s
              WHERE s.idJoueur = ?
                AND s.idQuestion = e.idEnigme
                AND s.estReussie = 1
          )
    ";

if ($typePige === 'F') {
    $sql .= " AND e.difficulte = 'F' ";
} elseif ($typePige === 'M') {
    $sql .= " AND e.difficulte = 'M' ";
} elseif ($typePige === 'D') {
    $sql .= " AND e.difficulte = 'D' ";
} elseif ($typePige === 'magique') {
    $sql .= " AND e.idCategorie = 'M' ";
}

    $sql .= " ORDER BY RAND() LIMIT 1 ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$pointsVie, $idJoueur]);
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
        $_SESSION['enigme_message'] = "Aucune quête disponible pour ce choix avec tes points de vie actuels.";
        $_SESSION['enigme_message_type'] = "error";
        header("Location: enigme.php");
        exit;
    }
}

$message = $_SESSION['enigme_message'] ?? "";
$messageType = $_SESSION['enigme_message_type'] ?? "";
unset($_SESSION['enigme_message'], $_SESSION['enigme_message_type']);

$pvPercent = max(0, min(100, $pointsVie));
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enigma</title>
    <link rel="stylesheet" href="public/css/enigma.css">

</head>

<body>
 <?php include_once 'template/header-enigma.php'; ?>

    <main>
       
        <div class="hero-top">
            <div class="mage-title">✨ ENIGMA ✨</div>

            <div class="pv-row" style="position: relative">
                <label for="healthbar" style="position: relative; right: 31px; top: 50px;">
                    <?php
                    $hp = (int) ($user['pointsVie']);
                    if ($hp > 50)
                        echo '<img src="image-site/1Pixel_heart_overflow.png" alt="confident" style="height: 70px;">';
                    elseif ($hp >= 35 && $hp <= 50)
                        echo '<img src="image-site/2Pixel_heart.png" alt="omagah" style="height: 70px;">';
                    elseif ($hp > 15 && $hp < 35)
                        echo '<img src="image-site/3Pixel_heart_mid.png" alt="hmmm" style="height: 70px;">';
                    elseif ($hp <= 15)
                        echo '<img src="image-site/4Pixel_heart_damaged.png" alt="o nooooo" style="height: 70px;">';
                    ?>
                </label>
                <progress id="healthbar" style="height: 40px;
              border-radius: 20px;" class="
              <?php
              $hp = (int) ($user['pointsVie']);
              if ($hp <= 15)
                  echo 'low_hp';
              if ($hp > 15 && $hp < 35)
                  echo 'mid_hp';
              elseif ($hp >= 35 && $hp <= 50)
                  echo 'high_hp';
              elseif ($hp > 50)
                  echo 'overflow_hp';
              ?>
              " value="<?php echo (int) ($user['pointsVie']); ?>" max="50"></progress>
                <label for="healthbar"
                    style="font-weight: bold; position: absolute; bottom: -9px; right: 20px; text-shadow: 0px 0px 5px black;"><?php echo (int) ($user['pointsVie']); ?>
                    / 50 PV</label>
            </div>

            <div class="currencies">
                <div class="currency or">🪙 <?= h($gold) ?></div>
                <div class="currency argent">🪙 <?= h($argent) ?></div>
                <div class="currency bronze">🪙 <?= h($bronze) ?></div>
            </div>

            <h1 class="big-title">Piger une quête</h1>
            <p class="subtitle">
                <?= $estMage === 1 ? 'Vous êtes mage.' : 'Vous n’êtes pas encore mage.' ?>
            </p>
        </div>

        <div class="quest-grid">
            <a class="quest-card facile" href="enigme.php?piger=F">
                <span class="quest-icon">✨</span>
                <div class="quest-title">Quête Facile</div>
                <div class="quest-subtitle">Pour les aventuriers prudents</div>
            </a>

            <a class="quest-card moyenne" href="enigme.php?piger=M">
                <span class="quest-icon">🔥</span>
                <div class="quest-title">Quête Moyenne</div>
                <div class="quest-subtitle">Un défi équilibré</div>
            </a>

            <a class="quest-card difficile" href="enigme.php?piger=D">
                <span class="quest-icon">💀</span>
                <div class="quest-title">Quête Difficile</div>
                <div class="quest-subtitle">Seulement pour les courageux</div>
            </a>

            <a class="quest-card aleatoire" href="enigme.php?piger=alea">
                <span class="quest-icon">🎲</span>
                <div class="quest-title">Quête Aléatoire</div>
                <div class="quest-subtitle">Laissez le destin décider</div>
            </a>
            <a class="quest-card magique" href="enigme.php?piger=magique">
                <span class="quest-icon">🔮</span>
                <div class="quest-title">Quête Magique</div>
                <div class="quest-subtitle">Pour devenir mage</div>
            </a>

           <a class="quest-card statistiques" href="enigme.php?voir=stats">
    <span class="quest-icon">
        <img src="image-site/statistique.png" class="icon-stats" alt="Statistiques">
    </span>
    <div class="quest-title">Statistiques</div>
    <div class="quest-subtitle">Voir votre progression</div>
</a>
        </div>
    </main>

    <?php if ($message !== ""): ?>
        <div class="popup-overlay">
            <div class="popup-content message-popup <?= $messageType === 'success' ? 'success' : 'error' ?>">
                <button class="popup-close" onclick="window.location.href='enigme.php'">✕</button>

                <div class="message-icon">
                    <?= $messageType === 'success' ? '✓' : '✕' ?>
                </div>

                <div class="message-title">
                    <?= $messageType === 'success' ? 'Résultat' : 'Attention' ?>
                </div>

                <div class="message-text">
                    <?= h($message) ?>
                </div>

                <button class="close-message-btn" onclick="window.location.href='enigme.php'">
                    Fermer
                </button>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($enigmeOuverte): ?>
        <div class="popup-overlay">
            <div class="popup-content">
                <button class="popup-close" onclick="window.location.href='enigme.php'">✕</button>

                <h2><?= h($enigmeOuverte['nomCategorie']) ?></h2>

                <div class="enigme-meta">
                    <div><strong>Catégorie :</strong> <?= h($enigmeOuverte['nomCategorie']) ?></div>
                    <div><strong>Difficulté :</strong> <?= h($enigmeOuverte['difficulte']) ?></div>
                    <div>
                        <strong>Récompense :</strong>
                        <?= h($enigmeOuverte['Recompense']) ?>
                        <?php
                        if ($enigmeOuverte['difficulte'] === 'F') {
                            echo ' bronze';
                        } elseif ($enigmeOuverte['difficulte'] === 'M') {
                            echo ' argent';
                        } else {
                            echo ' or';
                        }
                        ?>
                    </div>
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
    <?php if ($voirStats && $statsJoueur): ?>
    <div class="popup-overlay">
        <div class="popup-content message-popup">
            <button class="popup-close" onclick="window.location.href='enigme.php'">✕</button>

            <div class="message-icon"> <img src="image-site/statistique.png" class="icon-stats" alt="Statistiques"></div>
            <div class="message-title">Statistiques</div>

            <div class="message-text" style="text-align:left;">
                <p><strong>Joueur :</strong> <?= h($statsJoueur['alias']) ?></p>
                <p><strong>Statut :</strong> <?= (int)$statsJoueur['estMage'] === 1 ? 'Mage' : 'Pas mage' ?></p>
                <p><strong>Quêtes réussies / faites :</strong> <?= (int)$statsJoueur['total_reussies'] ?> / <?= (int)$statsJoueur['total_faites'] ?></p>
                <p><strong>Winrate :</strong> <?= h($statsJoueur['winrate']) ?>%</p>

                <hr style="border-color: rgba(255,255,255,0.12); margin: 16px 0;">

                <p><strong>Quêtes faciles réussies / pigées :</strong> <?= (int)$statsJoueur['faciles_reussies'] ?> / <?= (int)$statsJoueur['faciles_faites'] ?></p>
                <p><strong>Quêtes moyennes réussies / pigées :</strong> <?= (int)$statsJoueur['moyennes_reussies'] ?> / <?= (int)$statsJoueur['moyennes_faites'] ?></p>
                <p><strong>Quêtes difficiles réussies / pigées :</strong> <?= (int)$statsJoueur['difficiles_reussies'] ?> / <?= (int)$statsJoueur['difficiles_faites'] ?></p>
            </div>

            <button class="close-message-btn" onclick="window.location.href='enigme.php'">
                Fermer
            </button>
        </div>
    </div>
<?php endif; ?>

</body>

</html>
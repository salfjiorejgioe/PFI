<?php
require_once 'session_config.php';
require_once 'db.php';
require_once 'helpers.php';

/* =========================
   SÉCURITÉ CONNEXION
========================= */
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['idJoueur'])) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? 'enigme.php';
    header('Location: login.php');
    exit;
}

$idJoueur = (int) $_SESSION['user']['idJoueur'];
$comboDifficiles = (int) ($_SESSION['combo_difficiles'] ?? 0);

$message = $_SESSION['enigme_message'] ?? "";
$messageType = $_SESSION['enigme_message_type'] ?? "";

unset($_SESSION['enigme_message'], $_SESSION['enigme_message_type']);

/*
    Important:
    On libère la session après avoir lu les infos nécessaires.
    Ça évite que la page bloque si MySQL est lent.
*/
session_write_close();

$enigmeOuverte = null;
$reponsesEnigmeOuverte = [];
$statsCategories = [];
$statsJoueur = null;
$voirStats = isset($_GET['voir']) && $_GET['voir'] === 'stats';

/* =========================
   OUTILS SESSION / REDIRECT
========================= */
function redirectEnigme(string $message, string $type = 'error', array $sessionData = []): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $_SESSION['enigme_message'] = $message;
    $_SESSION['enigme_message_type'] = $type;

    foreach ($sessionData as $key => $value) {
        $_SESSION[$key] = $value;
    }

    session_write_close();

    header('Location: enigme.php');
    exit;
}

/* =========================
   RÉPONDRE À UNE ÉNIGME
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'answer_enigme') {
    $idEnigme = (int) ($_POST['idEnigme'] ?? 0);
    $idReponse = (int) ($_POST['idReponse'] ?? 0);

    if ($idEnigme <= 0 || $idReponse <= 0) {
        redirectEnigme("Réponse invalide.", "error");
    }

    try {
        $stmt = $pdo->prepare("
            SELECT estReussie
            FROM Statistiques
            WHERE idJoueur = ? AND idQuestion = ?
            LIMIT 1
        ");
        $stmt->execute([$idJoueur, $idEnigme]);
        $ancienneTentative = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($ancienneTentative && (int) $ancienneTentative['estReussie'] === 1) {
            redirectEnigme("Tu as déjà réussi cette quête.", "error");
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
            LIMIT 1
        ");
        $stmt->execute([$idReponse, $idEnigme]);
        $choix = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$choix) {
            redirectEnigme("Réponse invalide.", "error");
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
            }

            $stmt = $pdo->prepare("
                UPDATE Joueurs
                SET $colonneRecompense = $colonneRecompense + ?
                WHERE idJoueur = ?
            ");
            $stmt->execute([$recompense, $idJoueur]);

            $stmt = $pdo->prepare("
                INSERT INTO Statistiques (idJoueur, idQuestion, estReussie)
                VALUES (?, ?, 1)
                ON DUPLICATE KEY UPDATE estReussie = 1
            ");
            $stmt->execute([$idJoueur, $idEnigme]);

            $messageFinal = "Bonne réponse ! Tu gagnes {$recompense} {$nomMonnaie}.";

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
                LIMIT 1
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

                $messageFinal .= " 🧙 Tu es maintenant devenu Mage !";
            }

            if ($difficulte === 'D') {
                $comboDifficiles++;

                if ($comboDifficiles >= 3) {
                    $stmt = $pdo->prepare("
                        UPDATE Joueurs
                        SET gold = gold + 100
                        WHERE idJoueur = ?
                    ");
                    $stmt->execute([$idJoueur]);

                    $messageFinal .= " Bonus ! 3 quêtes difficiles réussies de suite : +100 or.";
                    $comboDifficiles = 0;
                }
            } else {
                $comboDifficiles = 0;
            }

            $pdo->commit();

            redirectEnigme($messageFinal, "success", [
                'combo_difficiles' => $comboDifficiles
            ]);
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

            $pdo->commit();

            redirectEnigme("Mauvaise réponse... Tu perds {$punition} point(s) de vie.", "error", [
                'combo_difficiles' => 0
            ]);
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        redirectEnigme("Erreur serveur. Réessaie dans quelques secondes.", "error");
    }
}

/* =========================
   INFOS JOUEUR
========================= */
try {
    $stmt = $pdo->prepare("
        SELECT gold, argent, bronze, pointsVie, alias, estMage
        FROM Joueurs
        WHERE idJoueur = ?
        LIMIT 1
    ");
    $stmt->execute([$idJoueur]);
    $joueur = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$joueur) {
        redirectEnigme("Joueur introuvable. Reconnecte-toi.", "error");
    }
} catch (PDOException $e) {
    redirectEnigme("Erreur serveur lors du chargement du joueur.", "error");
}

$gold = (int) $joueur['gold'];
$argent = (int) $joueur['argent'];
$bronze = (int) $joueur['bronze'];
$pointsVie = (int) $joueur['pointsVie'];
$alias = $joueur['alias'] ?? 'Joueur';
$estMage = (int) $joueur['estMage'];

/* =========================
   STATISTIQUES
========================= */
if ($voirStats) {
    try {
        $stmt = $pdo->prepare("
            SELECT
                j.alias,
                j.estMage,

                COALESCE(SUM(CASE WHEN e.difficulte = 'F' AND s.estReussie = 1 THEN 1 ELSE 0 END), 0) AS faciles_reussies,
                COALESCE(SUM(CASE WHEN e.difficulte = 'F' THEN 1 ELSE 0 END), 0) AS faciles_faites,

                COALESCE(SUM(CASE WHEN e.difficulte = 'M' AND s.estReussie = 1 THEN 1 ELSE 0 END), 0) AS moyennes_reussies,
                COALESCE(SUM(CASE WHEN e.difficulte = 'M' THEN 1 ELSE 0 END), 0) AS moyennes_faites,

                COALESCE(SUM(CASE WHEN e.difficulte = 'D' AND s.estReussie = 1 THEN 1 ELSE 0 END), 0) AS difficiles_reussies,
                COALESCE(SUM(CASE WHEN e.difficulte = 'D' THEN 1 ELSE 0 END), 0) AS difficiles_faites,

                COALESCE(SUM(CASE WHEN s.estReussie = 1 THEN 1 ELSE 0 END), 0) AS total_reussies,
                COALESCE(SUM(CASE WHEN s.estReussie = 0 THEN 1 ELSE 0 END), 0) AS total_echecs,
                COUNT(s.idQuestion) AS total_faites,

                COALESCE(SUM(CASE WHEN s.estReussie = 0 THEN e.Punition ELSE 0 END), 0) AS degats_totaux_subis,
                COALESCE(AVG(CASE WHEN s.estReussie = 0 THEN e.Punition END), 0) AS moyenne_punition_echec,

                COALESCE(SUM(CASE WHEN s.estReussie = 1 THEN e.Recompense ELSE 0 END), 0) AS total_recompenses_gagnees
            FROM Joueurs j
            LEFT JOIN Statistiques s ON s.idJoueur = j.idJoueur
            LEFT JOIN Enigmes e ON e.idEnigme = s.idQuestion
            WHERE j.idJoueur = ?
            GROUP BY j.idJoueur, j.alias, j.estMage
        ");
        $stmt->execute([$idJoueur]);
        $statsJoueur = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$statsJoueur) {
            $statsJoueur = [
                'alias' => $alias,
                'estMage' => $estMage,
                'faciles_reussies' => 0,
                'faciles_faites' => 0,
                'moyennes_reussies' => 0,
                'moyennes_faites' => 0,
                'difficiles_reussies' => 0,
                'difficiles_faites' => 0,
                'total_reussies' => 0,
                'total_echecs' => 0,
                'total_faites' => 0,
                'degats_totaux_subis' => 0,
                'moyenne_punition_echec' => 0,
                'total_recompenses_gagnees' => 0
            ];
        }

        foreach ($statsJoueur as $key => $value) {
            if ($value === null) {
                $statsJoueur[$key] = 0;
            }
        }

        $statsJoueur['faciles_reussies'] = (int) $statsJoueur['faciles_reussies'];
        $statsJoueur['faciles_faites'] = (int) $statsJoueur['faciles_faites'];
        $statsJoueur['moyennes_reussies'] = (int) $statsJoueur['moyennes_reussies'];
        $statsJoueur['moyennes_faites'] = (int) $statsJoueur['moyennes_faites'];
        $statsJoueur['difficiles_reussies'] = (int) $statsJoueur['difficiles_reussies'];
        $statsJoueur['difficiles_faites'] = (int) $statsJoueur['difficiles_faites'];
        $statsJoueur['total_reussies'] = (int) $statsJoueur['total_reussies'];
        $statsJoueur['total_echecs'] = (int) $statsJoueur['total_echecs'];
        $statsJoueur['total_faites'] = (int) $statsJoueur['total_faites'];
        $statsJoueur['degats_totaux_subis'] = (int) $statsJoueur['degats_totaux_subis'];
        $statsJoueur['moyenne_punition_echec'] = round((float) $statsJoueur['moyenne_punition_echec'], 1);
        $statsJoueur['total_recompenses_gagnees'] = (int) $statsJoueur['total_recompenses_gagnees'];

        $totalFaites = $statsJoueur['total_faites'];
        $totalReussies = $statsJoueur['total_reussies'];

        $statsJoueur['winrate'] = $totalFaites > 0
            ? round(($totalReussies / $totalFaites) * 100, 1)
            : 0;

        $statsJoueur['hard_share'] = $totalFaites > 0
            ? round(($statsJoueur['difficiles_faites'] / $totalFaites) * 100, 1)
            : 0;

        $stmt = $pdo->prepare("
            SELECT 
                c.idCategorie,
                c.nomCategorie,
                COALESCE(SUM(CASE WHEN s.estReussie = 1 THEN 1 ELSE 0 END), 0) AS reussies,
                COALESCE(SUM(CASE WHEN s.estReussie = 0 THEN 1 ELSE 0 END), 0) AS echecs,
                COUNT(s.idQuestion) AS total
            FROM Categories c
            LEFT JOIN Enigmes e ON e.idCategorie = c.idCategorie
            LEFT JOIN Statistiques s 
                ON s.idQuestion = e.idEnigme
               AND s.idJoueur = ?
            GROUP BY c.idCategorie, c.nomCategorie
            ORDER BY c.nomCategorie
        ");
        $stmt->execute([$idJoueur]);
        $statsCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $bestCategory = null;
        $worstCategory = null;
        $bestRate = -1;
        $worstRate = 101;
        $nbCategoriesRepondues = 0;
        $nbCategoriesParfaites = 0;

        foreach ($statsCategories as &$cat) {
            $cat['reussies'] = (int) $cat['reussies'];
            $cat['echecs'] = (int) $cat['echecs'];
            $cat['total'] = (int) $cat['total'];
            $cat['rate'] = $cat['total'] > 0 ? round(($cat['reussies'] / $cat['total']) * 100, 1) : 0;

            if ($cat['total'] > 0) {
                $nbCategoriesRepondues++;

                if ($cat['reussies'] === $cat['total']) {
                    $nbCategoriesParfaites++;
                }

                if ($cat['rate'] > $bestRate) {
                    $bestRate = $cat['rate'];
                    $bestCategory = $cat;
                }

                if ($cat['rate'] < $worstRate) {
                    $worstRate = $cat['rate'];
                    $worstCategory = $cat;
                }
            }
        }
        unset($cat);

        $difficultes = [
            'Facile' => [
                'faites' => $statsJoueur['faciles_faites'],
                'reussies' => $statsJoueur['faciles_reussies']
            ],
            'Moyenne' => [
                'faites' => $statsJoueur['moyennes_faites'],
                'reussies' => $statsJoueur['moyennes_reussies']
            ],
            'Difficile' => [
                'faites' => $statsJoueur['difficiles_faites'],
                'reussies' => $statsJoueur['difficiles_reussies']
            ]
        ];

        $favoriteDifficulty = 'Aucune';
        $bestDifficulty = 'Aucune';
        $maxPlayed = -1;
        $maxRate = -1;

        foreach ($difficultes as $nom => $diff) {
            $faites = (int) $diff['faites'];
            $reussies = (int) $diff['reussies'];
            $rate = $faites > 0 ? round(($reussies / $faites) * 100, 1) : 0;

            if ($faites > $maxPlayed) {
                $maxPlayed = $faites;
                $favoriteDifficulty = $nom;
            }

            if ($faites > 0 && $rate > $maxRate) {
                $maxRate = $rate;
                $bestDifficulty = $nom;
            }
        }

        $stmt = $pdo->prepare("
            SELECT estReussie
            FROM Statistiques
            WHERE idJoueur = ?
            ORDER BY idQuestion ASC
        ");
        $stmt->execute([$idJoueur]);
        $historique = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

        $currentWinStreak = 0;
        $currentLoseStreak = 0;
        $bestWinStreak = 0;
        $worstLoseStreak = 0;
        $tempWin = 0;
        $tempLose = 0;

        foreach ($historique as $resultat) {
            $resultat = (int) $resultat;

            if ($resultat === 1) {
                $tempWin++;
                $tempLose = 0;
                $bestWinStreak = max($bestWinStreak, $tempWin);
            } else {
                $tempLose++;
                $tempWin = 0;
                $worstLoseStreak = max($worstLoseStreak, $tempLose);
            }
        }

        for ($i = count($historique) - 1; $i >= 0; $i--) {
            if ((int) $historique[$i] === 1) {
                if ($currentLoseStreak > 0) {
                    break;
                }
                $currentWinStreak++;
            } else {
                if ($currentWinStreak > 0) {
                    break;
                }
                $currentLoseStreak++;
            }
        }

        $statsJoueur['nb_categories_repondues'] = $nbCategoriesRepondues;
        $statsJoueur['nb_categories_parfaites'] = $nbCategoriesParfaites;

        $statsJoueur['best_category_name'] = $bestCategory['nomCategorie'] ?? 'Aucune';
        $statsJoueur['best_category_rate'] = $bestCategory['rate'] ?? 0;

        $statsJoueur['worst_category_name'] = $worstCategory['nomCategorie'] ?? 'Aucune';
        $statsJoueur['worst_category_rate'] = $worstCategory['rate'] ?? 0;

        $statsJoueur['favorite_difficulty'] = $favoriteDifficulty;
        $statsJoueur['best_difficulty'] = $bestDifficulty;

        $statsJoueur['current_win_streak'] = $currentWinStreak;
        $statsJoueur['current_lose_streak'] = $currentLoseStreak;
        $statsJoueur['best_win_streak'] = $bestWinStreak;
        $statsJoueur['worst_lose_streak'] = $worstLoseStreak;

    } catch (PDOException $e) {
        redirectEnigme("Erreur serveur lors du chargement des statistiques.", "error");
    }
}

/* =========================
   PIGER UNE QUÊTE OPTIMISÉE
========================= */
if (isset($_GET['piger'])) {
    $typePige = $_GET['piger'];

    $typesValides = ['F', 'M', 'D', 'alea', 'magique'];

    if (!in_array($typePige, $typesValides, true)) {
        redirectEnigme("Type de quête invalide.", "error");
    }

    $minPV = 3;

    if ($typePige === 'M') {
        $minPV = 6;
    } elseif ($typePige === 'D') {
        $minPV = 10;
    }

    if ($pointsVie < $minPV) {
        redirectEnigme("Pas assez de points de vie pour ce type de quête.", "error");
    }

    try {
        $where = "
            e.Punition <= ?
            AND NOT EXISTS (
                SELECT 1
                FROM Statistiques s
                WHERE s.idJoueur = ?
                  AND s.idQuestion = e.idEnigme
                  AND s.estReussie = 1
            )
        ";

        $params = [$pointsVie, $idJoueur];

        if ($typePige === 'F') {
            $where .= " AND e.difficulte = 'F' ";
        } elseif ($typePige === 'M') {
            $where .= " AND e.difficulte = 'M' ";
        } elseif ($typePige === 'D') {
            $where .= " AND e.difficulte = 'D' ";
        } elseif ($typePige === 'magique') {
            $where .= " AND e.idCategorie = 'M' ";
        }

        $countSql = "
            SELECT COUNT(*)
            FROM Enigmes e
            INNER JOIN Categories c ON c.idCategorie = e.idCategorie
            WHERE $where
        ";

        $stmt = $pdo->prepare($countSql);
        $stmt->execute($params);
        $totalPossibles = (int) $stmt->fetchColumn();

        if ($totalPossibles <= 0) {
            redirectEnigme("Aucune quête disponible pour ce choix avec tes points de vie actuels.", "error");
        }

        $offset = random_int(0, $totalPossibles - 1);

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
            WHERE $where
            LIMIT 1 OFFSET $offset
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $enigmeOuverte = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$enigmeOuverte) {
            redirectEnigme("Aucune quête disponible pour ce choix avec tes points de vie actuels.", "error");
        }

        $stmt = $pdo->prepare("
            SELECT idReponse, reponse
            FROM Reponses
            WHERE idEnigme = ?
            ORDER BY idReponse
        ");
        $stmt->execute([(int) $enigmeOuverte['idEnigme']]);
        $reponsesEnigmeOuverte = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        redirectEnigme("Erreur serveur lors de la pige de quête.", "error");
    }
}

$pvPercent = max(0, min(100, $pointsVie));
$hp = $pointsVie;
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enigma</title>
    <link rel="stylesheet" href="public/css/enigma.css">
    <link href="https://fonts.googleapis.com/css2?family=VT323&display=swap" rel="stylesheet">
</head>

<body>
    <main>

        <div class="hero-top">
            <div class="mage-title">✨ ENIGMA ✨</div>

            <div class="pv-row" style="position: relative">
                <label for="healthbar" style="position: relative; right: 30px; top: 50px;">
                    <?php
                    if ($hp > 50) {
                        echo '<img src="image-site/1Pixel_heart_overflow.png" alt="confident" style="height: 70px;">';
                    } elseif ($hp >= 35 && $hp <= 50) {
                        echo '<img src="image-site/2Pixel_heart.png" alt="omagah" style="height: 70px;">';
                    } elseif ($hp > 15 && $hp < 35) {
                        echo '<img src="image-site/3Pixel_heart_mid.png" alt="hmmm" style="height: 70px;">';
                    } else {
                        echo '<img src="image-site/4Pixel_heart_damaged.png" alt="o nooooo" style="height: 70px;">';
                    }
                    ?>
                </label>

                <progress id="healthbar" style="height: 40px; border-radius: 20px;" class="<?php
                    if ($hp <= 15) {
                        echo 'low_hp';
                    } elseif ($hp > 15 && $hp < 35) {
                        echo 'mid_hp';
                    } elseif ($hp >= 35 && $hp <= 50) {
                        echo 'high_hp';
                    } elseif ($hp > 50) {
                        echo 'overflow_hp';
                    }
                ?>" value="<?= (int) $hp ?>" max="50"></progress>

                <label for="healthbar"
                    style="font-weight: bold; position: absolute; bottom: -9px; right: 20px; text-shadow: 0px 0px 5px black;">
                    <?= (int) $hp ?> / 50 PV
                </label>
            </div>

            <div class="currencies">
                <div class="currency or"><img class="coin-icon" src="image-site/gold_coin.png" alt=""> <?= h($gold) ?></div>
                <div class="currency argent"><img class="coin-icon" src="image-site/argent_coin.png" alt=""> <?= h($argent) ?></div>
                <div class="currency bronze"><img class="coin-icon" src="image-site/bronze_coin.png" alt=""> <?= h($bronze) ?></div>
            </div>

            <h1 class="big-title">Piger une quête</h1>
            <p class="subtitle">
                <?= $estMage === 1 ? 'Vous êtes mage.' : 'Vous n’êtes pas encore mage.' ?>
            </p>
        </div>

        <div class="quest-grid">
            <a class="quest-card facile" href="enigme.php?piger=F">
                <span class="quest-icon"><img class="quest-icon-img" src="image-site/base_skull.png" alt=""></span>
                <div class="quest-title">Quête Facile</div>
                <div class="quest-subtitle">Pour les aventuriers prudents</div>
            </a>

            <a class="quest-card moyenne" href="enigme.php?piger=M">
                <span class="quest-icon"><img class="quest-icon-img" src="image-site/blue_skull.png" alt=""></span>
                <div class="quest-title">Quête Moyenne</div>
                <div class="quest-subtitle">Pour les aventuriers curieux</div>
            </a>

            <a class="quest-card difficile" href="enigme.php?piger=D">
                <span class="quest-icon"><img class="quest-icon-img" src="image-site/purple_skull.png" alt=""></span>
                <div class="quest-title">Quête Difficile</div>
                <div class="quest-subtitle">Seulement pour les courageux</div>
            </a>

            <a class="quest-card aleatoire" href="enigme.php?piger=alea">
                <span class="quest-icon">
                    <img class="quest-icon-img" src="image-site/pink_random.png" alt="">
                </span>
                <div class="quest-title">Quête Aléatoire</div>
                <div class="quest-subtitle">Laissez le destin décider</div>
            </a>

            <a class="quest-card magique" href="enigme.php?piger=magique">
                <span class="quest-icon"><img class="quest-icon-img" src="image-site/scroll.png" alt=""></span>
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

    <?php include_once 'template/header-enigma.php'; ?>

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
        <?php
        $facileRate = (int) $statsJoueur['faciles_faites'] > 0
            ? round(((int) $statsJoueur['faciles_reussies'] / (int) $statsJoueur['faciles_faites']) * 100, 1)
            : 0;

        $moyenneRate = (int) $statsJoueur['moyennes_faites'] > 0
            ? round(((int) $statsJoueur['moyennes_reussies'] / (int) $statsJoueur['moyennes_faites']) * 100, 1)
            : 0;

        $difficileRate = (int) $statsJoueur['difficiles_faites'] > 0
            ? round(((int) $statsJoueur['difficiles_reussies'] / (int) $statsJoueur['difficiles_faites']) * 100, 1)
            : 0;
        ?>

        <div class="popup-overlay">
            <div class="popup-content stats-popup">
                <button class="popup-close" onclick="window.location.href='enigme.php'">✕</button>

                <div class="stats-header">
                    <div class="stats-avatar">📜</div>
                    <div>
                        <div class="stats-title-main">Tableau du héros</div>
                        <div class="stats-subtitle-main">
                            <?= h($statsJoueur['alias']) ?> •
                            <?= (int) $statsJoueur['estMage'] === 1 ? 'Mage' : 'Aventurier' ?>
                        </div>
                    </div>
                </div>

                <div class="stats-grid-cards">
                    <div class="stat-card epic">
                        <div class="stat-label">Winrate</div>
                        <div class="stat-value"><?= h($statsJoueur['winrate']) ?>%</div>
                        <div class="stat-note"><?= (int) $statsJoueur['total_reussies'] ?> réussies /
                            <?= (int) $statsJoueur['total_faites'] ?> jouées</div>
                    </div>

                    <div class="stat-card success">
                        <div class="stat-label">Victoires</div>
                        <div class="stat-value"><?= (int) $statsJoueur['total_reussies'] ?></div>
                        <div class="stat-note">Quêtes réussies</div>
                    </div>

                    <div class="stat-card danger">
                        <div class="stat-label">Échecs</div>
                        <div class="stat-value"><?= (int) $statsJoueur['total_echecs'] ?></div>
                        <div class="stat-note">Quêtes ratées</div>
                    </div>

                    <div class="stat-card goldy">
                        <div class="stat-label">Best streak</div>
                        <div class="stat-value"><?= (int) $statsJoueur['best_win_streak'] ?></div>
                        <div class="stat-note">Série max</div>
                    </div>
                </div>

                <div class="stats-section">
                    <div class="stats-section-title">⚔️ Progression par difficulté</div>

                    <div class="difficulty-box">
                        <div class="difficulty-row">
                            <span>Facile</span>
                            <span><?= (int) $statsJoueur['faciles_reussies'] ?> /
                                <?= (int) $statsJoueur['faciles_faites'] ?> • <?= h($facileRate) ?>%</span>
                        </div>
                        <div class="progress-shell facile">
                            <div class="progress-fill" style="width: <?= h($facileRate) ?>%;"></div>
                        </div>
                    </div>

                    <div class="difficulty-box">
                        <div class="difficulty-row">
                            <span>Moyenne</span>
                            <span><?= (int) $statsJoueur['moyennes_reussies'] ?> /
                                <?= (int) $statsJoueur['moyennes_faites'] ?> • <?= h($moyenneRate) ?>%</span>
                        </div>
                        <div class="progress-shell moyenne">
                            <div class="progress-fill" style="width: <?= h($moyenneRate) ?>%;"></div>
                        </div>
                    </div>

                    <div class="difficulty-box">
                        <div class="difficulty-row">
                            <span>Difficile</span>
                            <span><?= (int) $statsJoueur['difficiles_reussies'] ?> /
                                <?= (int) $statsJoueur['difficiles_faites'] ?> • <?= h($difficileRate) ?>%</span>
                        </div>
                        <div class="progress-shell difficile">
                            <div class="progress-fill" style="width: <?= h($difficileRate) ?>%;"></div>
                        </div>
                    </div>
                </div>

                <div class="stats-grid-cards secondary">
                    <div class="stat-card">
                        <div class="stat-label">Meilleure catégorie</div>
                        <div class="stat-value small"><?= h($statsJoueur['best_category_name']) ?></div>
                        <div class="stat-note"><?= h($statsJoueur['best_category_rate']) ?>%</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-label">Pire catégorie</div>
                        <div class="stat-value small"><?= h($statsJoueur['worst_category_name']) ?></div>
                        <div class="stat-note"><?= h($statsJoueur['worst_category_rate']) ?>%</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-label">Difficulté favorite</div>
                        <div class="stat-value small"><?= h($statsJoueur['favorite_difficulty']) ?></div>
                        <div class="stat-note">La plus jouée</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-label">Meilleure difficulté</div>
                        <div class="stat-value small"><?= h($statsJoueur['best_difficulty']) ?></div>
                        <div class="stat-note">Meilleur taux</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-label">Catégories jouées</div>
                        <div class="stat-value"><?= (int) $statsJoueur['nb_categories_repondues'] ?></div>
                        <div class="stat-note">Explorées</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-label">Catégories parfaites</div>
                        <div class="stat-value"><?= (int) $statsJoueur['nb_categories_parfaites'] ?></div>
                        <div class="stat-note">100% de réussite</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-label">Dégâts subis</div>
                        <div class="stat-value"><?= (int) $statsJoueur['degats_totaux_subis'] ?></div>
                        <div class="stat-note">PV perdus</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-label">Punition moyenne</div>
                        <div class="stat-value"><?= h($statsJoueur['moyenne_punition_echec']) ?></div>
                        <div class="stat-note">Par échec</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-label">Récompenses gagnées</div>
                        <div class="stat-value"><?= (int) $statsJoueur['total_recompenses_gagnees'] ?></div>
                        <div class="stat-note">Total obtenu</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-label">Part du difficile</div>
                        <div class="stat-value"><?= h($statsJoueur['hard_share']) ?>%</div>
                        <div class="stat-note">Du total joué</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-label">Streak actuelle</div>
                        <div class="stat-value"><?= (int) $statsJoueur['current_win_streak'] ?></div>
                        <div class="stat-note">Victoires de suite</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-label">Défaites actuelles</div>
                        <div class="stat-value"><?= (int) $statsJoueur['current_lose_streak'] ?></div>
                        <div class="stat-note">Défaites de suite</div>
                    </div>
                </div>

                <div class="stats-section">
                    <div class="stats-section-title">🧩 Détail par catégorie</div>

                    <div class="stats-table-wrap">
                        <table class="stats-table">
                            <thead>
                                <tr>
                                    <th>Catégorie</th>
                                    <th>Réussies</th>
                                    <th>Échecs</th>
                                    <th>Total</th>
                                    <th>Taux</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($statsCategories as $cat): ?>
                                    <tr>
                                        <td><?= h($cat['nomCategorie']) ?></td>
                                        <td><?= (int) $cat['reussies'] ?></td>
                                        <td><?= (int) $cat['echecs'] ?></td>
                                        <td><?= (int) $cat['total'] ?></td>
                                        <td><?= h($cat['rate']) ?>%</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <button class="close-message-btn" onclick="window.location.href='enigme.php'">
                    Fermer
                </button>
            </div>
        </div>
    <?php endif; ?>

    <script>
        document.querySelectorAll("a.quest-card").forEach(card => {
            card.addEventListener("click", () => {
                document.querySelectorAll("a.quest-card").forEach(c => {
                    c.style.pointerEvents = "none";
                    c.style.opacity = "0.6";
                });
            });
        });

        document.querySelectorAll("form").forEach(form => {
            form.addEventListener("submit", () => {
                form.querySelectorAll("button").forEach(btn => {
                    btn.disabled = true;
                    btn.innerText = "Chargement...";
                });
            });
        });
    </script>
</body>

</html>
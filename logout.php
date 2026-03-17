<?php
session_start();
session_unset();
session_destroy();
header('Location: index.php');

// Note: vider panier du joueur quand déconnexion.

exit;

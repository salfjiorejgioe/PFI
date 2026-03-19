<?php
if (!function_exists('h')) {
    function h($texte) {
        return htmlspecialchars($texte, ENT_QUOTES, 'UTF-8');
    }
}

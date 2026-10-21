<?php
// ============================================================
//  Modèle à recopier en config.local.php SUR LE SERVEUR.
//  config.local.php est exclu du dépôt (.gitignore) : il survit aux
//  déploiements et n'expose jamais d'identifiant dans git.
// ============================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'u000000000_sublimes');
define('DB_USER', 'u000000000_sublimes');
define('DB_PASS', 'votre_mot_de_passe');

// URL publique (sans slash final), utilisée pour le SEO.
define('SITE_URL', 'https://gaecdupointsublime.com');

<?php
// Gabarit commun de l'espace d'administration.
require_once __DIR__ . '/../includes/bootstrap.php';

function admin_entete(string $titre, string $actif = ''): void {
    $menu = ['index' => 'Tableau de bord', 'glaces' => 'Glaces', 'confitures' => 'Confitures', 'buches' => 'Bûches de Noël', 'tarifs' => 'Grille tarifaire', 'lieux' => 'Où nous retrouver', 'partenaires' => 'Partenaires', 'messages' => 'Messages'];
    $f = flash();
    ?><!DOCTYPE html>
<html lang="fr"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= h($titre) ?> — Administration Les Sublimes</title>
<link rel="icon" href="../assets/img/logo.png">
<link rel="stylesheet" href="../assets/css/admin.css?v=<?= h(filemtime(__DIR__ . '/../assets/css/admin.css')) ?>">
</head><body>
<?php if ($actif !== 'login'): ?>
<header class="adm-bar">
    <a class="adm-brand" href="index.php"><img src="../assets/img/logo.png" alt="" width="36" height="36"> Les Sublimes · Admin</a>
    <nav>
        <?php foreach ($menu as $k => $l): ?><a href="<?= $k ?>.php" class="<?= $k === $actif ? 'actif' : '' ?>"><?= h($l) ?><?php if ($k === 'messages' && ($nb = messages_non_lus())): ?> <span class="adm-badge"><?= $nb ?></span><?php endif; ?></a><?php endforeach; ?>
        <a href="../" target="_blank" rel="noopener">Voir le site ↗</a>
        <a href="logout.php">Déconnexion</a>
    </nav>
</header>
<?php endif; ?>
<main class="adm-main">
<h1><?= h($titre) ?></h1>
<?php if ($f): ?><div class="adm-flash adm-flash--<?= h($f[1]) ?>"><?= h($f[0]) ?></div><?php endif; ?>
<?php }

function admin_pied(): void { ?>
</main>
<script src="../assets/js/admin.js" defer></script>
</body></html>
<?php }

/** Redirige vers la page admin courante (motif POST/Redirect/GET). */
function rediriger(string $page): void { header('Location: ' . $page); exit; }

/** Vérifie que la base est utilisable ; sinon explique quoi faire. */
function base_exigee(): PDO {
    $pdo = db();
    if (!$pdo) {
        admin_entete('Base de données non configurée', 'login');
        echo '<p>Copiez <code>config.example.php</code> en <code>config.local.php</code> sur le serveur et renseignez les identifiants MySQL, puis importez <code>sql/schema.sql</code>.</p>';
        admin_pied(); exit;
    }
    return $pdo;
}

/** Nombre de messages non lus (0 si la table n'existe pas encore). */
function messages_non_lus(): int {
    try { return (int)db()->query('SELECT COUNT(*) FROM messages WHERE lu = 0')->fetchColumn(); } catch (Throwable $e) { return 0; }
}

<?php
// En-tête commun du site public. Variables attendues : $pageTitle, $pageDescription, $currentPage.
require_once __DIR__ . '/includes/bootstrap.php';
$R = reglages();
$noel = ($R['noel_actif'] ?? '0') === '1';
$metaTitle = $pageTitle ?? 'Les Sublimes — Glaces au lait de chèvre | GAEC du Point Sublime';
$metaDescription = $pageDescription ?? 'Glaces artisanales au lait de nos chèvres, à la Ferme du Tensonnieu, aux portes des Gorges du Tarn. Marchés d\'Aveyron et de Lozère.';
$path = ($currentPage ?? 'home') === 'home' ? '/' : '/' . basename($_SERVER['SCRIPT_NAME']);
$canonical = SITE_URL . $path;
$ogImage = SITE_URL . '/assets/img/hero-poster.jpg';
?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($metaTitle) ?></title>
    <meta name="description" content="<?= h($metaDescription) ?>">
    <link rel="canonical" href="<?= h($canonical) ?>">
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= h($metaTitle) ?>">
    <meta property="og:description" content="<?= h($metaDescription) ?>">
    <meta property="og:url" content="<?= h($canonical) ?>">
    <meta property="og:image" content="<?= h($ogImage) ?>">
    <meta name="theme-color" content="#4f6340">
    <link rel="icon" type="image/png" href="assets/img/logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700&family=Playfair+Display:ital,wght@0,600;0,700;1,500&display=swap">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= h(filemtime(__DIR__ . '/assets/css/style.css')) ?>">
    <script type="application/ld+json"><?= json_encode([
        '@context' => 'https://schema.org', '@type' => 'LocalBusiness',
        'name' => 'GAEC du Point Sublime — Les Sublimes', 'url' => SITE_URL,
        'description' => 'Glaces artisanales, confitures de lait et bûches glacées au lait de chèvre, Ferme du Tensonnieu (Gorges du Tarn).',
        'image' => $ogImage, 'address' => $R['adresse'] ?? '',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
</head>
<body class="<?= $noel ? 'noel' : '' ?> page-<?= h($currentPage ?? 'home') ?>">
<a class="skip-link" href="#contenu">Aller au contenu</a>

<header class="site-header" id="site-header">
    <a class="brand" href="./" aria-label="Les Sublimes — accueil">
        <img src="assets/img/logo.png" alt="Logo Les Sublimes, glace au lait de nos chèvres" width="56" height="56">
        <span class="brand-text">Les Sublimes</span>
    </a>
    <button class="nav-toggle" aria-label="Menu" aria-expanded="false" aria-controls="nav-principal"><span></span><span></span><span></span></button>
    <nav id="nav-principal" class="nav-principal" aria-label="Navigation principale">
        <a href="./#glaces">Nos glaces</a>
        <a href="./#confitures">Confitures de lait</a>
        <?php if ($noel): ?><a href="./#buches" class="nav-noel">🎄 Bûches de Noël</a><?php endif; ?>
        <a href="./#pros">Professionnels</a>
        <a href="./#ferme">Notre histoire</a>
        <a href="./#retrouver">Nous retrouver</a>
        <a href="./#contact" class="nav-contact">Contact</a>
    </nav>
</header>

<main id="contenu">

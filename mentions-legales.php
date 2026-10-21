<?php
$pageTitle = 'Mentions légales — Les Sublimes';
$pageDescription = 'Mentions légales du site Les Sublimes, glaces au lait de chèvre du GAEC du Point Sublime.';
$currentPage = 'legal';
include 'header.php';
?>
<section class="page-section container texte-legal">
    <h1>Mentions légales</h1>
    <h2>Éditeur du site</h2>
    <p>GAEC du Point Sublime — Ferme du Tensonnieu, aux portes des Gorges du Tarn (Aveyron / Lozère).<?php if (!empty($R['email'])): ?><br>Courriel : <?= h($R['email']) ?><?php endif; ?><?php if (!empty($R['telephone'])): ?><br>Téléphone : <?= h($R['telephone']) ?><?php endif; ?></p>
    <p><em>Les informations légales complètes (SIRET, directeur de publication) sont à compléter par l'exploitation.</em></p>
    <h2>Données personnelles</h2>
    <p>Ce site ne dépose aucun traceur publicitaire et ne collecte aucune donnée personnelle auprès des visiteurs. Un cookie technique de session n'est utilisé que dans l'espace d'administration.</p>
    <h2>Conception</h2>
    <p>Site réalisé par Colibri et Compagnie.</p>
</section>
<?php include 'footer.php'; ?>

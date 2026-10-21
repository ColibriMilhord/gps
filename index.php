<?php
$pageTitle = 'Les Sublimes — Glaces au lait de chèvre | Ferme du Tensonnieu, Gorges du Tarn';
$pageDescription = 'Glaces artisanales au lait de nos chèvres alpines et anglo-nubiennes, Ferme du Tensonnieu aux portes des Gorges du Tarn. Marchés d\'Aveyron et de Lozère.';
$currentPage = 'home';
include 'header.php';
require_once __DIR__ . '/includes/cartes.php';

$mode   = $R['prix_mode'] ?? 'individuel';
$glaces = produits_visibles('glaces');
$buches = $noel ? produits_visibles('buches') : [];
$tarifs = $mode === 'grille' ? grille_tarifs() : [];
?>

<section class="hero" id="accueil">
    <video autoplay muted loop playsinline preload="metadata" poster="assets/img/hero-poster.jpg" aria-hidden="true">
        <source src="assets/video/troupeau.mp4" type="video/mp4">
    </video>
    <div class="hero-voile"></div>
    <div class="hero-contenu">
        <img class="hero-logo" src="assets/img/logo.png" alt="Les Sublimes — glace au lait de nos chèvres, la fraîcheur des Causses" width="220" height="220">
        <h1>L'authenticité de notre ferme,<br><em>la douceur de nos glaces.</em></h1>
        <p class="hero-sous">Glaces artisanales au lait de nos chèvres · Gorges du Tarn</p>
        <div class="hero-actions">
            <a class="btn" href="#glaces">Découvrir nos parfums</a>
            <?php if ($noel && $buches): ?><a class="btn btn-ghost" href="#buches">🎄 Bûches de Noël</a><?php endif; ?>
        </div>
    </div>
    <a class="scroll-indicator" href="#glaces" aria-label="Faire défiler"><span></span></a>
</section>

<section class="page-section" id="glaces">
    <div class="container">
        <header class="section-titre reveal">
            <p class="surtitre">Nos glaces</p>
            <h2>Les parfums des Sublimes</h2>
            <p>Onctueuses, naturelles, faites avec le lait de nos biquettes et un stabilisant biologique à base de farine de lin et de fibres végétales.</p>
        </header>
        <?php if ($glaces): ?>
            <div class="grille-cartes">
                <?php foreach ($glaces as $g) echo carte_produit($g, 'glaces', $mode); ?>
            </div>
        <?php else: ?>
            <p class="vide">Les parfums arrivent bientôt. Revenez nous voir !</p>
        <?php endif; ?>

        <?php if ($mode === 'grille' && $tarifs): ?>
            <div class="grille-tarifs reveal">
                <h3>Nos tarifs</h3>
                <ul>
                    <?php foreach ($tarifs as $t): ?>
                        <li><span><?= h($t['libelle']) ?></span><strong><?= h(prix_fr($t['prix'])) ?></strong></li>
                    <?php endforeach; ?>
                </ul>
                <p class="note">Tous les parfums sont au même prix.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php if ($noel): ?>
<section class="page-section section-noel" id="buches">
    <div class="container">
        <header class="section-titre reveal">
            <p class="surtitre">Fêtes de fin d'année</p>
            <h2>Nos bûches de Noël sur commande</h2>
            <p><?= h($R['noel_message'] ?? '') ?></p>
        </header>
        <?php if ($buches): ?>
            <div class="grille-cartes">
                <?php foreach ($buches as $b) echo carte_produit($b, 'buches', $mode); ?>
            </div>
        <?php else: ?>
            <p class="vide">Nos bûches arrivent bientôt.</p>
        <?php endif; ?>
        <p class="commande-noel reveal">
            Pour commander :
            <?php if (!empty($R['telephone'])): ?><a href="tel:<?= h(preg_replace('/[^0-9+]/', '', $R['telephone'])) ?>"><?= h($R['telephone']) ?></a><?php endif; ?>
            <?php if (!empty($R['telephone']) && !empty($R['email'])): ?> ou <?php endif; ?>
            <?php if (!empty($R['email'])): ?><a href="mailto:<?= h($R['email']) ?>"><?= h($R['email']) ?></a><?php endif; ?>
            <?php if (empty($R['telephone']) && empty($R['email'])): ?>retrouvez-nous sur les marchés ou à la ferme.<?php endif; ?>
        </p>
    </div>
</section>
<?php endif; ?>

<section class="page-section section-ferme" id="ferme">
    <div class="ferme-parallax" data-parallax style="background-image:url('assets/img/noisette-profil.jpg')" role="img" aria-label="Noisette, chèvre alpine brune de profil, cornes dressées"></div>
    <div class="container ferme-texte">
        <header class="section-titre reveal">
            <p class="surtitre">Notre ferme</p>
            <h2>Nous, Noisette, nos biquettes, nos glaces</h2>
        </header>
        <div class="histoire reveal">
            <p>Bienvenue à la <strong>Ferme du Tensonnieu</strong>, un havre de paix niché aux portes des Gorges du Tarn, où nous vous invitons à découvrir l'histoire de Noisette et de nos glaces artisanales.</p>
            <p>L'aventure a débuté avec <strong>Noisette</strong>, une chevrette espiègle arrivée un matin lumineux dans un seau, annonçant le commencement d'un projet innovant : la production de glaces au lait de chèvre. Nos biquettes alpines et anglo-nubiennes, élevées avec soin, fournissent un lait riche qui est la base de nos créations glacées, alliant onctuosité et naturalité.</p>
            <p>Chez nous, la gourmandise rime avec authenticité : une alternative savoureuse aux glaces conventionnelles, grâce à notre savoir-faire traditionnel et notre engagement pour la qualité.</p>
            <p>Le respect de l'environnement et de votre bien-être guide chacun de nos gestes. C'est pourquoi nous utilisons un <strong>stabilisant biologique</strong> à base de farine de lin et de fibres végétales, et nos chèvres se délectent d'une <strong>alimentation biologique</strong> <small>(en cours de certification bio)</small>.</p>
        </div>

        <div class="galerie reveal" aria-label="Nos chèvres anglo-nubiennes">
            <figure><img src="assets/img/chevrette-1.jpg" alt="Chevrette anglo-nubienne tachetée, oreilles tombantes" loading="lazy"></figure>
            <figure><img src="assets/img/chevrette-3.jpg" alt="Chevrette anglo-nubienne à l'ombre du frêne" loading="lazy"></figure>
            <figure><img src="assets/img/chevrette-2.jpg" alt="Anglo-nubienne curieuse face à l'objectif, le troupeau derrière" loading="lazy"></figure>
        </div>
    </div>
</section>

<section class="page-section section-contact" id="contact">
    <div class="container contact-grille reveal">
        <div>
            <p class="surtitre">Nous trouver</p>
            <h2>À la ferme et sur les marchés</h2>
            <p>Nous vous convions à la Ferme du Tensonnieu pour une escapade gourmande : dégustez nos glaces exclusives et profitez d'un interlude de détente dans un cadre authentique.</p>
            <p>Noisette, Delphine, Christian et toute la famille caprine vous attendent pour partager un moment de convivialité et de plaisir gustatif.</p>
        </div>
        <ul class="infos">
            <li><span aria-hidden="true">📍</span> <?= h($R['adresse'] ?? '') ?></li>
            <li><span aria-hidden="true">🛒</span> <?= h($R['marches'] ?? '') ?></li>
            <?php if (!empty($R['telephone'])): ?><li><span aria-hidden="true">📞</span> <a href="tel:<?= h(preg_replace('/[^0-9+]/', '', $R['telephone'])) ?>"><?= h($R['telephone']) ?></a></li><?php endif; ?>
            <?php if (!empty($R['email'])): ?><li><span aria-hidden="true">✉️</span> <a href="mailto:<?= h($R['email']) ?>"><?= h($R['email']) ?></a></li><?php endif; ?>
        </ul>
    </div>
</section>

<dialog id="dialogue-logo" class="dialogue-logo">
    <form method="dialog"><button class="fermer" aria-label="Fermer">×</button></form>
    <img src="" alt="">
    <p></p>
</dialog>

<?php include 'footer.php'; ?>

<?php
$pageTitle = 'Les Sublimes — Glaces au lait de chèvre | Ferme du Tensonnieu, Gorges du Tarn';
$pageDescription = 'Glaces artisanales au lait de nos chèvres alpines et anglo-nubiennes, Ferme du Tensonnieu aux portes des Gorges du Tarn. Marchés d\'Aveyron et de Lozère.';
$currentPage = 'home';
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/contact.php';
require_once __DIR__ . '/includes/cartes.php';

$noel = (reglages()['noel_actif'] ?? '0') === '1';
$resContact = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['contact'])) {
    $resContact = contact_traiter($noel);   // redirige si tout est bon
} elseif (isset($_GET['profil']) && isset(profils_contact($noel)[$_GET['profil']])) {
    $resContact = ['valeurs' => ['profil' => $_GET['profil']]];   // lien « Devenir revendeur »
}
include 'header.php';
$lieux = lieux_a_venir();
$partenaires = partenaires_visibles();
$prochain = $lieux[0] ?? null;

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
    <button type="button" class="video-pause" aria-pressed="false" aria-label="Mettre la vidéo en pause">⏸</button>
    <a class="scroll-indicator" href="#glaces" aria-label="Aller aux glaces"><span></span></a>
</section>

<?php if ($prochain): ?>
<a class="bandeau-rdv" href="#retrouver">
    <span class="bandeau-pastille">Prochain rendez-vous</span>
    <strong><?= h($prochain['titre']) ?><?= $prochain['ville'] ? ' · ' . h($prochain['ville']) : '' ?></strong>
    <span><?= h(lieu_quand($prochain)) ?><?= $prochain['horaires'] ? ' · ' . h($prochain['horaires']) : '' ?></span>
    <span class="bandeau-lien">Voir toutes les dates →</span>
</a>
<?php endif; ?>

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
    <svg class="vague" viewBox="0 0 1200 60" preserveAspectRatio="none" aria-hidden="true" focusable="false"><path d="M0 0H1200V30C1050 62 900 62 750 36C600 10 450 10 300 36C150 62 50 56 0 38Z"/></svg>
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

<section class="page-section section-histoire" id="ferme" aria-labelledby="titre-histoire">
    <svg class="vague" viewBox="0 0 1200 60" preserveAspectRatio="none" aria-hidden="true" focusable="false"><path d="M0 0H1200V30C1050 62 900 62 750 36C600 10 450 10 300 36C150 62 50 56 0 38Z"/></svg>
    <div class="container">
        <header class="section-titre reveal">
            <p class="surtitre">Notre histoire</p>
            <h2 id="titre-histoire">Nous, Noisette, nos biquettes, nos glaces</h2>
        </header>

        <blockquote class="citation reveal">
            Noisette est arrivée un matin lumineux, <em>dans un seau.</em>
            <footer>Tout a commencé là, à la Ferme du Tensonnieu.</footer>
        </blockquote>

        <div class="histoire-grille">
            <figure class="portrait reveal" data-portrait>
                <div class="portrait-cadre">
                    <img src="assets/img/noisette-profil.jpg" alt="Noisette, chèvre alpine brune de profil, cornes dressées, collier jaune" width="900" height="1200" loading="lazy">
                </div>
                <figcaption><span>Noisette</span> notre doyenne espiègle</figcaption>
            </figure>

            <ol class="chapitres" data-chapitres>
                <li class="chevre-marcheuse" aria-hidden="true"><span>🐐</span></li>
                <li class="chapitre chapitre--1 reveal">
                    <span class="chapitre-badge" aria-hidden="true"><span class="chapitre-icone">🏞️</span><b>01</b></span>
                    <div class="chapitre-corps"><h3>Un havre aux portes des Gorges du Tarn</h3><p>Bienvenue à la <strong>Ferme du Tensonnieu</strong>, un havre de paix où nous vous invitons à découvrir l'histoire de Noisette et de nos glaces artisanales.</p></div>
                </li>
                <li class="chapitre chapitre--2 reveal">
                    <span class="chapitre-badge" aria-hidden="true"><span class="chapitre-icone">🪣</span><b>02</b></span>
                    <div class="chapitre-corps"><h3>Une chevrette, un seau, une idée folle</h3><p>Noisette, chevrette espiègle, a annoncé le commencement d'un projet innovant : <strong>des glaces au lait de chèvre</strong>. Nos biquettes alpines et anglo-nubiennes, élevées avec soin, fournissent un lait riche, base de créations alliant onctuosité et naturalité.</p></div>
                </li>
                <li class="chapitre chapitre--3 reveal">
                    <span class="chapitre-badge" aria-hidden="true"><span class="chapitre-icone">🍦</span><b>03</b></span>
                    <div class="chapitre-corps"><h3>La gourmandise rime avec authenticité</h3><p>Une alternative savoureuse aux glaces conventionnelles, grâce à notre savoir-faire traditionnel et notre engagement pour la qualité.</p></div>
                </li>
                <li class="chapitre chapitre--4 reveal">
                    <span class="chapitre-badge" aria-hidden="true"><span class="chapitre-icone">🌿</span><b>04</b></span>
                    <div class="chapitre-corps"><h3>Le respect du vivant, à chaque geste</h3><p>Un <strong>stabilisant biologique</strong> à base de farine de lin et de fibres végétales, et des chèvres qui se délectent d'une <strong>alimentation biologique</strong> pour un lait et une crème supérieurs.</p></div>
                </li>
            </ol>
        </div>

        <ul class="pastilles reveal" aria-label="Nos engagements">
            <li><span aria-hidden="true">🐐</span> Chèvres alpines &amp; anglo-nubiennes</li>
            <li><span aria-hidden="true">🌾</span> Stabilisant bio : lin &amp; fibres végétales</li>
            <li><span aria-hidden="true">🌿</span> Alimentation bio <small>(en cours de certification)</small></li>
            <li><span aria-hidden="true">🍦</span> Fabriquées à la ferme</li>
        </ul>

        <div class="polaroids reveal" aria-label="Nos chèvres anglo-nubiennes">
            <figure><img src="assets/img/chevrette-1.jpg" alt="Chevrette anglo-nubienne tachetée, oreilles tombantes" loading="lazy"><figcaption>Ça gambade !</figcaption></figure>
            <figure><img src="assets/img/chevrette-3.jpg" alt="Chevrette anglo-nubienne à l'ombre du frêne" loading="lazy"><figcaption>À l'ombre du frêne</figcaption></figure>
            <figure><img src="assets/img/chevrette-2.jpg" alt="Anglo-nubienne curieuse face à l'objectif, le troupeau derrière" loading="lazy"><figcaption>Curieuse, comme Noisette</figcaption></figure>
        </div>

        <p class="invitation reveal">Noisette, Delphine, Christian et toute la famille caprine vous attendent pour partager un moment de convivialité et de plaisir gustatif.</p>
    </div>
</section>

<section class="page-section section-retrouver" id="retrouver" aria-labelledby="titre-retrouver">
    <svg class="vague" viewBox="0 0 1200 60" preserveAspectRatio="none" aria-hidden="true" focusable="false"><path d="M0 0H1200V30C1050 62 900 62 750 36C600 10 450 10 300 36C150 62 50 56 0 38Z"/></svg>
    <div class="container">
        <header class="section-titre reveal">
            <p class="surtitre">Où nous retrouver</p>
            <h2 id="titre-retrouver">À la ferme, sur les marchés, chez nos partenaires</h2>
            <p>Dégustez nos glaces exclusives et profitez d'un interlude de détente dans un cadre authentique.</p>
        </header>

        <div class="retrouver-grille">
            <?php $lieuxFerme = array_filter($lieux, fn($l) => $l['type'] === 'ferme'); $agenda = array_filter($lieux, fn($l) => $l['type'] !== 'ferme'); ?>
            <article class="bloc-ferme reveal">
                <h3>🏡 À la ferme</h3>
                <p class="adresse"><?= h($R['adresse'] ?? '') ?></p>
                <?php foreach ($lieuxFerme as $l): ?>
                    <p class="ferme-ligne"><strong><?= h(lieu_quand($l) ?: $l['titre']) ?></strong><?= $l['horaires'] ? ' · ' . h($l['horaires']) : '' ?>
                    <?php if ($l['note']): ?><br><small><?= nl2br(h($l['note'])) ?></small><?php endif; ?></p>
                <?php endforeach; ?>
                <p><a class="btn btn-clair" href="?profil=visite#contact">Organiser une visite</a></p>
            </article>

            <article class="bloc-agenda reveal">
                <h3>🛒 Marchés &amp; événements</h3>
                <?php if ($agenda): ?>
                <ul class="agenda">
                    <?php foreach ($agenda as $l): $carte = lien_carte($l); ?>
                    <li>
                        <span class="agenda-date <?= $l['date_debut'] ? '' : 'agenda-date--rec' ?>">
                            <?php if ($l['date_debut']): $t = strtotime($l['date_debut']); ?>
                                <b><?= (int)date('j', $t) ?></b><i><?= h(mb_substr(['', 'janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'][(int)date('n', $t)], 0, 5)) ?></i>
                            <?php else: ?><b aria-hidden="true">↻</b><i>Chaque</i><?php endif; ?>
                        </span>
                        <span class="agenda-texte">
                            <strong><?= h($l['titre']) ?></strong>
                            <span><?= h(lieu_quand($l)) ?><?= $l['horaires'] ? ' · ' . h($l['horaires']) : '' ?></span>
                            <span><?= h(trim(($l['adresse'] ?? '') . ' ' . ($l['ville'] ?? ''))) ?></span>
                            <?php if ($l['note']): ?><small><?= h($l['note']) ?></small><?php endif; ?>
                        </span>
                        <?php if ($carte): ?><a class="agenda-carte" href="<?= h($carte) ?>" target="_blank" rel="noopener" aria-label="Voir <?= h($l['titre']) ?> sur la carte (nouvel onglet)">📍 Plan</a><?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php else: ?>
                <p class="vide">Nos prochains marchés seront annoncés ici très bientôt. <br><?= h($R['marches'] ?? '') ?>.</p>
                <?php endif; ?>
            </article>
        </div>

        <div class="partenaires reveal">
            <h3>🍨 Nos glaces chez nos partenaires</h3>
            <?php if ($partenaires): ?>
            <ul class="partenaires-grille">
                <?php foreach ($partenaires as $p): ?>
                <li class="partenaire">
                    <span class="puce"><?= h(CATEGORIES_PARTENAIRE[$p['categorie']] ?? 'Partenaire') ?></span>
                    <strong><?= h($p['nom']) ?></strong>
                    <?php if ($p['ville'] || $p['adresse']): ?><span><?= h(trim(($p['adresse'] ?? '') . ' ' . ($p['ville'] ?? ''))) ?></span><?php endif; ?>
                    <?php if ($p['telephone']): ?><a href="tel:<?= h(preg_replace('/[^0-9+]/', '', $p['telephone'])) ?>"><?= h($p['telephone']) ?></a><?php endif; ?>
                    <?php if ($p['site_web']): ?><a href="<?= h($p['site_web']) ?>" target="_blank" rel="noopener">Site web<span class="sr-only"> de <?= h($p['nom']) ?> (nouvel onglet)</span></a><?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
            <p class="devenir">Boutique, restaurant, épicerie : <a href="?profil=partenaire#contact">vous aussi, proposez nos glaces</a>.</p>
        </div>
    </div>
</section>

<section class="page-section section-contact" id="contact" aria-labelledby="titre-contact">
    <svg class="vague" viewBox="0 0 1200 60" preserveAspectRatio="none" aria-hidden="true" focusable="false"><path d="M0 0H1200V30C1050 62 900 62 750 36C600 10 450 10 300 36C150 62 50 56 0 38Z"/></svg>
    <div class="container contact-grille">
        <div class="contact-intro reveal">
            <p class="surtitre">Contact</p>
            <h2 id="titre-contact">Une question ? Une envie ? Parlons-en !</h2>
            <p class="accroche"><strong>Vous êtes une boutique, un restaurant</strong> et nos produits vous intéressent ? <strong>Contactez-nous directement :</strong> nous vous répondons en personne.</p>
            <ul class="infos">
                <li><span aria-hidden="true">📍</span> <?= h($R['adresse'] ?? '') ?></li>
                <li><span aria-hidden="true">🛒</span> <?= h($R['marches'] ?? '') ?></li>
                <?php if (!empty($R['telephone'])): ?><li><span aria-hidden="true">📞</span> <a href="tel:<?= h(preg_replace('/[^0-9+]/', '', $R['telephone'])) ?>"><?= h($R['telephone']) ?></a></li><?php endif; ?>
                <?php if (!empty($R['email'])): ?><li><span aria-hidden="true">✉️</span> <a href="mailto:<?= h($R['email']) ?>"><?= h($R['email']) ?></a></li><?php endif; ?>
            </ul>
        </div>
        <div class="contact-carte reveal">
            <?php if (isset($_GET['envoye'])): ?>
                <div class="alerte alerte--ok" role="status" tabindex="-1" id="alerte-contact">Merci ! Votre message nous est bien parvenu, nous revenons vers vous très vite.</div>
            <?php else:
                contact_afficher($noel, $buches ?: ($noel ? produits_visibles('buches') : []), $resContact);
            endif; ?>
        </div>
    </div>
</section>

<dialog id="dialogue-logo" class="dialogue-logo">
    <form method="dialog"><button class="fermer" aria-label="Fermer">×</button></form>
    <img src="" alt="">
    <p></p>
</dialog>

<?php include 'footer.php'; ?>

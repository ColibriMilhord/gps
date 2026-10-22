<?php
// Rendu des cartes produit (glaces et bûches). $mode = individuel | grille | aucun
require_once __DIR__ . '/images.php';

/** '#rrggbb' → '221,111,31' pour les rgba() du CSS. */
function hex_rgb(string $hex): string {
    $h = ltrim($hex, '#');
    return hexdec(substr($h, 0, 2)) . ',' . hexdec(substr($h, 2, 2)) . ',' . hexdec(substr($h, 4, 2));
}

/** $accentFixe : teinte imposée (confitures, sans logo à analyser). $confiture : variante « pot de confiture » de la carte. */
function carte_produit(array $p, string $dossierPhoto, string $mode, bool $buche = false, ?string $accentFixe = null, bool $confiture = false): string {
    $photo = upload_url($p['image'] ?? null, $dossierPhoto);
    $logo  = upload_url($p['logo'] ?? null, 'logos');

    // Une « photo » carrée (étiquette envoyée dans le champ photo) est présentée comme un logo, entière, sans rognage
    $rPhoto = $photo ? image_ratio($p['image'], $dossierPhoto) : null;
    $photoEstLogo = $photo && $rPhoto !== null && abs($rPhoto - 1) < 0.25;
    $visuelLogo = !$photo || $photoEstLogo;
    $visuel = $photo ?: $logo;
    $badge = $photo && !$photoEstLogo && $logo;

    $accent = $buche ? '#a3262f' : ($accentFixe ?: couleur_accent($p['logo'] ?? null));
    $rgb = hex_rgb($accent);
    ob_start(); ?>
    <article class="carte <?= $buche ? 'carte--buche' : ($confiture ? 'carte--confiture' : 'carte--glace') ?> reveal" style="--accent:<?= h($accent) ?>;--accent-rgb:<?= h($rgb) ?>">
        <div class="carte-media <?= $visuelLogo ? 'carte-media--logo' : 'carte-media--photo' ?>">
            <?php if ($visuel): ?>
                <img src="<?= h($visuel) ?>" alt="<?= h($p['nom']) ?> — Les Sublimes" loading="lazy" decoding="async">
            <?php elseif ($confiture): ?>
                <span class="carte-pot" aria-hidden="true">🍯</span>
            <?php else: ?>
                <img src="assets/img/logo.png" alt="" loading="lazy" class="placeholder">
            <?php endif; ?>
            <?php if ($buche): ?><span class="carte-ruban">Sur commande</span><?php endif; ?>
            <?php if ($badge): ?>
                <a class="carte-logo" href="<?= h($logo) ?>" target="_blank" rel="noopener" data-logo="<?= h($logo) ?>" data-titre="<?= h($p['nom']) ?>" aria-label="Voir le logo de <?= h($p['nom']) ?>">
                    <img src="<?= h($logo) ?>" alt="" loading="lazy">
                </a>
            <?php endif; ?>
        </div>
        <div class="carte-corps">
            <h3><?= h($p['nom']) ?></h3>
            <?php if (!empty($p['parfums'])): ?><p class="carte-parfums"><?= h($p['parfums']) ?></p><?php endif; ?>
            <?php if (!empty($p['description'])): ?><p class="carte-desc"><?= nl2br(h($p['description'])) ?></p><?php endif; ?>
            <?php if (!empty($p['parts'])): ?><p class="carte-parts">🍰 <?= h($p['parts']) ?></p><?php endif; ?>
            <?php if (!empty($p['ingredients'])): ?>
                <details class="carte-ingredients">
                    <summary>Ingrédients</summary>
                    <p><?= h(mb_strtoupper(mb_substr($p['ingredients'], 0, 1)) . mb_substr($p['ingredients'], 1)) ?>.</p>
                    <?php if (!empty($p['poids'])): ?><p class="carte-poids">Poids net : <?= h($p['poids']) ?></p><?php endif; ?>
                </details>
            <?php endif; ?>
            <div class="carte-pied">
                <?php if ($mode === 'individuel' && $p['prix'] !== null && $p['prix'] !== ''): ?>
                    <span class="carte-prix"><?= h(prix_fr($p['prix'])) ?></span>
                <?php endif; ?>
                <?php if ($buche): ?>
                    <a class="carte-cta" href="?profil=buche&amp;produit=<?= h(rawurlencode($p['nom'])) ?>#contact">Commander <span aria-hidden="true">→</span><span class="sr-only"> la bûche <?= h($p['nom']) ?></span></a>
                <?php elseif ($confiture): ?>
                    <a class="carte-cta" href="?profil=confiture&amp;produit=<?= h(rawurlencode($p['nom'])) ?>#contact">En commander <span aria-hidden="true">→</span><span class="sr-only"> : <?= h($p['nom']) ?></span></a>
                <?php else: ?>
                    <a class="carte-cta" href="#retrouver">Où la goûter ? <span aria-hidden="true">→</span><span class="sr-only"> glace <?= h($p['nom']) ?></span></a>
                <?php endif; ?>
            </div>
        </div>
    </article>
    <?php return ob_get_clean();
}

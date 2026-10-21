<?php
// Rendu des cartes produit (glaces et bûches). $mode = individuel | grille | aucun
function carte_produit(array $p, string $dossierPhoto, string $mode): string {
    $photo = upload_url($p['image'] ?? null, $dossierPhoto);
    $logo  = upload_url($p['logo'] ?? null, 'logos');
    $visuel = $photo ?: $logo;
    ob_start(); ?>
    <article class="carte reveal">
        <div class="carte-media <?= $photo ? '' : 'carte-media--logo' ?>">
            <?php if ($visuel): ?>
                <img src="<?= h($visuel) ?>" alt="<?= h($p['nom']) ?> — Les Sublimes" loading="lazy">
            <?php else: ?>
                <img src="assets/img/logo.png" alt="" loading="lazy" class="placeholder">
            <?php endif; ?>
            <?php if ($photo && $logo): ?>
                <button type="button" class="carte-logo" data-logo="<?= h($logo) ?>" data-titre="<?= h($p['nom']) ?>" aria-label="Voir le logo de <?= h($p['nom']) ?>">
                    <img src="<?= h($logo) ?>" alt="" loading="lazy">
                </button>
            <?php endif; ?>
        </div>
        <div class="carte-corps">
            <h3><?= h($p['nom']) ?></h3>
            <?php if (!empty($p['parfums'])): ?><p class="carte-parfums"><?= h($p['parfums']) ?></p><?php endif; ?>
            <?php if (!empty($p['description'])): ?><p><?= nl2br(h($p['description'])) ?></p><?php endif; ?>
            <?php if (!empty($p['parts'])): ?><p class="carte-parts"><?= h($p['parts']) ?></p><?php endif; ?>
            <?php if ($mode === 'individuel' && $p['prix'] !== null && $p['prix'] !== ''): ?>
                <p class="carte-prix"><?= h(prix_fr($p['prix'])) ?></p>
            <?php endif; ?>
        </div>
    </article>
    <?php return ob_get_clean();
}

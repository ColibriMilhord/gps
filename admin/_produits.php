<?php
// CRUD générique pour les parfums de glaces et les bûches de Noël.
require_once __DIR__ . '/_layout.php';

/**
 * @param string $table   'glaces' ou 'buches'
 * @param string $dossier dossier des photos dans uploads/
 */
function page_produits(string $table, string $titre, string $singulier, string $dossier, bool $estBuche): void {
    admin_exiger();
    $pdo = base_exigee();
    $page = $table . '.php';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_verifier();
        $action = $_POST['action'] ?? '';
        $id = (int)($_POST['id'] ?? 0);
        try {
            if ($action === 'supprimer' && $id) {
                $st = $pdo->prepare("SELECT image, logo FROM $table WHERE id = ?"); $st->execute([$id]);
                if ($l = $st->fetch()) {
                    $pdo->prepare("DELETE FROM $table WHERE id = ?")->execute([$id]);
                    image_supprimer($l['image'], $dossier);
                    logo_supprimer_si_orphelin($pdo, $l['logo']);
                }
                flash("$singulier supprimé(e).");
            } elseif ($action === 'visibilite' && $id) {
                $pdo->prepare("UPDATE $table SET visible = 1 - visible WHERE id = ?")->execute([$id]);
                flash('Visibilité modifiée.');
            } elseif ($action === 'enregistrer') {
                $nom = trim((string)($_POST['nom'] ?? ''));
                if ($nom === '') throw new RuntimeException('Le nom est obligatoire.');
                $prix = trim(str_replace(',', '.', (string)($_POST['prix'] ?? '')));
                if ($prix !== '' && (!is_numeric($prix) || $prix < 0 || $prix > 9999)) throw new RuntimeException('Prix invalide.');
                $champs = [
                    'nom' => mb_substr($nom, 0, 120),
                    'description' => mb_substr(trim((string)($_POST['description'] ?? '')), 0, 2000),
                    'prix' => $prix === '' ? null : round((float)$prix, 2),
                    'visible' => !empty($_POST['visible']) ? 1 : 0,
                    'ordre' => (int)($_POST['ordre'] ?? 0),
                ];
                if (!$estBuche) {
                    $champs['ingredients'] = mb_substr(trim((string)($_POST['ingredients'] ?? '')), 0, 2000) ?: null;
                    $champs['poids'] = mb_substr(trim((string)($_POST['poids'] ?? '')), 0, 60) ?: null;
                }
                if ($estBuche) {
                    $champs['parfums'] = mb_substr(trim((string)($_POST['parfums'] ?? '')), 0, 255);
                    $champs['parts'] = mb_substr(trim((string)($_POST['parts'] ?? '')), 0, 60);
                }
                $ancien = ['image' => null, 'logo' => null];
                if ($id) {
                    $st = $pdo->prepare("SELECT image, logo FROM $table WHERE id = ?"); $st->execute([$id]);
                    $ancien = $st->fetch() ?: throw new RuntimeException('Élément introuvable.');
                }
                // Photo et logo propre au produit : remplacement ou suppression
                foreach (['image' => $dossier, 'logo' => 'logos'] as $champ => $dos) {
                    $nouveau = image_enregistrer($champ, $dos);
                    if ($nouveau) { $champs[$champ] = $nouveau; }
                    elseif (!empty($_POST['suppr_' . $champ])) { $champs[$champ] = null; }
                }
                if ($id) {
                    $set = implode(', ', array_map(fn($c) => "$c = ?", array_keys($champs)));
                    $pdo->prepare("UPDATE $table SET $set WHERE id = ?")->execute([...array_values($champs), $id]);
                    if (array_key_exists('image', $champs) && $champs['image'] !== $ancien['image']) image_supprimer($ancien['image'], $dossier);
                    if (array_key_exists('logo', $champs) && $champs['logo'] !== $ancien['logo']) logo_supprimer_si_orphelin($pdo, $ancien['logo']);
                } else {
                    $cols = implode(', ', array_keys($champs));
                    $marq = implode(', ', array_fill(0, count($champs), '?'));
                    $pdo->prepare("INSERT INTO $table ($cols) VALUES ($marq)")->execute(array_values($champs));
                }
                flash("$singulier enregistré(e).");
            }
        } catch (RuntimeException $e) { flash($e->getMessage(), 'err'); }
        catch (Throwable $e) { error_log($e->getMessage()); flash('Erreur interne, rien n\'a été enregistré.', 'err'); }
        rediriger($page);
    }

    $edit = null;
    if (isset($_GET['edit'])) {
        $st = $pdo->prepare("SELECT * FROM $table WHERE id = ?"); $st->execute([(int)$_GET['edit']]);
        $edit = $st->fetch() ?: null;
    }
    $lignes = $pdo->query("SELECT * FROM $table ORDER BY ordre, id")->fetchAll();
    $v = fn($c, $def = '') => h($edit[$c] ?? $def);

    admin_entete($titre, $table);
    ?>
    <form method="post" enctype="multipart/form-data" class="adm-carte adm-form" id="formulaire">
        <?= csrf_champ() ?>
        <input type="hidden" name="action" value="enregistrer">
        <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
        <h2><?= $edit ? 'Modifier : ' . h($edit['nom']) : 'Ajouter ' . h($singulier) ?></h2>
        <div class="adm-grille2">
            <label>Nom * <input name="nom" required maxlength="120" value="<?= $v('nom') ?>"></label>
            <label>Prix (€) <input name="prix" inputmode="decimal" value="<?= $v('prix') ?>" placeholder="ex. 3,50"></label>
        </div>
        <?php if ($estBuche): ?>
        <div class="adm-grille2">
            <label>Parfums de la bûche <input name="parfums" maxlength="255" value="<?= $v('parfums') ?>" placeholder="ex. Miel · Fruits rouges"></label>
            <label>Nombre de parts <input name="parts" maxlength="60" value="<?= $v('parts') ?>" placeholder="ex. 6 parts"></label>
        </div>
        <?php endif; ?>
        <?php if (!$estBuche): ?>
        <div class="adm-grille2">
            <label class="adm-large">Ingrédients <textarea name="ingredients" rows="3" placeholder="ex. pulpe de fraises, lait entier bio de nos chèvres, sucre…"><?= $v('ingredients') ?></textarea>
                <small class="adm-aide">Affichés sur la carte, dans « Ingrédients » (sans le point final).</small></label>
            <label>Poids net <input name="poids" maxlength="60" value="<?= $v('poids') ?>" placeholder="ex. 100 g / 140 ml"></label>
        </div>
        <?php endif; ?>
        <label>Description <textarea name="description" rows="3"><?= $v('description') ?></textarea></label>
        <div class="adm-grille2">
            <div class="adm-fichier">
                <label>Photo <input type="file" name="image" accept="image/jpeg,image/png,image/webp"></label>
                <?php if (!empty($edit['image'])): ?>
                    <img src="../<?= h(upload_url($edit['image'], $dossier)) ?>" alt="" class="adm-vignette">
                    <label class="adm-case"><input type="checkbox" name="suppr_image" value="1"> Supprimer la photo</label>
                <?php endif; ?>
            </div>
            <div class="adm-fichier">
                <label>Logo / étiquette de ce produit <input type="file" name="logo" accept="image/jpeg,image/png,image/webp"></label>
                <?php if (!empty($edit['logo'])): ?>
                    <img src="../<?= h(upload_url($edit['logo'], 'logos')) ?>" alt="" class="adm-vignette">
                    <label class="adm-case"><input type="checkbox" name="suppr_logo" value="1"> Supprimer le logo</label>
                <?php endif; ?>
            </div>
        </div>
        <div class="adm-grille2">
            <label>Ordre d'affichage <input type="number" name="ordre" value="<?= $v('ordre', count($lignes) + 1) ?>"></label>
            <label class="adm-case adm-case--bas"><input type="checkbox" name="visible" value="1" <?= (!$edit || $edit['visible']) ? 'checked' : '' ?>> Visible sur le site</label>
        </div>
        <p class="adm-aide">Envoyez l'image telle quelle (JPEG, PNG ou WebP, 25 Mo maximum) : le site la recadre, la redimensionne et l'allège tout seul. Une photo est mise au format paysage ; un logo ou une étiquette est entièrement conservé, marges blanches retirées. Sans photo, le logo sert de visuel.</p>
        <button class="adm-btn"><?= $edit ? 'Mettre à jour' : 'Ajouter' ?></button>
        <?php if ($edit): ?><a class="adm-btn adm-btn--clair" href="<?= h($page) ?>">Annuler</a><?php endif; ?>
    </form>

    <div class="adm-carte adm-table-wrap">
        <table class="adm-table">
            <thead><tr><th>Visuel</th><th>Nom</th><th>Prix</th><th>Ordre</th><th>Statut</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($lignes as $l): $vis = upload_url($l['image'], $dossier) ?: upload_url($l['logo'], 'logos'); ?>
                <tr class="<?= $l['visible'] ? '' : 'masque' ?>">
                    <td><?php if ($vis): ?><img src="../<?= h($vis) ?>" alt="" class="adm-vignette adm-vignette--p"><?php endif; ?></td>
                    <td><strong><?= h($l['nom']) ?></strong></td>
                    <td><?= $l['prix'] !== null ? h(prix_fr($l['prix'])) : '—' ?></td>
                    <td><?= (int)$l['ordre'] ?></td>
                    <td><?= $l['visible'] ? 'Visible' : 'Masqué' ?></td>
                    <td class="adm-actions">
                        <a class="adm-btn adm-btn--p" href="<?= h($page) ?>?edit=<?= (int)$l['id'] ?>#formulaire">Modifier</a>
                        <form method="post"><?= csrf_champ() ?><input type="hidden" name="action" value="visibilite"><input type="hidden" name="id" value="<?= (int)$l['id'] ?>"><button class="adm-btn adm-btn--p adm-btn--clair"><?= $l['visible'] ? 'Masquer' : 'Afficher' ?></button></form>
                        <form method="post" data-confirmer="Supprimer « <?= h($l['nom']) ?> » définitivement ?"><?= csrf_champ() ?><input type="hidden" name="action" value="supprimer"><input type="hidden" name="id" value="<?= (int)$l['id'] ?>"><button class="adm-btn adm-btn--p adm-btn--danger">Supprimer</button></form>
                    </td>
                </tr>
            <?php endforeach; if (!$lignes): ?><tr><td colspan="6">Aucun élément pour l'instant.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
    admin_pied();
}

/** Supprime un logo du disque seulement si plus aucune glace/bûche ne l'utilise. */
function logo_supprimer_si_orphelin(PDO $pdo, ?string $logo): void {
    if (!$logo) return;
    foreach (['glaces', 'buches'] as $t) {
        $st = $pdo->prepare("SELECT COUNT(*) FROM $t WHERE logo = ?"); $st->execute([$logo]);
        if ((int)$st->fetchColumn() > 0) return;
    }
    image_supprimer($logo, 'logos');
}

<?php
// Grille tarifaire générale (utilisée quand « Grille tarifaire » est choisie dans le tableau de bord).
require_once __DIR__ . '/_layout.php';
admin_exiger();
$pdo = base_exigee();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verifier();
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    try {
        if ($action === 'supprimer' && $id) {
            $pdo->prepare('DELETE FROM tarifs WHERE id = ?')->execute([$id]);
            flash('Ligne supprimée.');
        } elseif ($action === 'enregistrer') {
            $lib = trim((string)($_POST['libelle'] ?? ''));
            $prix = str_replace(',', '.', trim((string)($_POST['prix'] ?? '')));
            if ($lib === '' || !is_numeric($prix) || $prix < 0 || $prix > 9999) throw new RuntimeException('Libellé et prix valides requis.');
            $cible = in_array($_POST['cible'] ?? '', ['particulier', 'pro'], true) ? $_POST['cible'] : 'particulier';
            $groupe = in_array($_POST['groupe'] ?? '', ['glaces', 'confitures'], true) ? $_POST['groupe'] : 'glaces';
            $d = [mb_substr($lib, 0, 120), round((float)$prix, 2), (int)($_POST['ordre'] ?? 0), $cible, $groupe];
            if ($id) $pdo->prepare('UPDATE tarifs SET libelle = ?, prix = ?, ordre = ?, cible = ?, groupe = ? WHERE id = ?')->execute([...$d, $id]);
            else $pdo->prepare('INSERT INTO tarifs (libelle, prix, ordre, cible, groupe) VALUES (?, ?, ?, ?, ?)')->execute($d);
            flash('Tarif enregistré.');
        }
    } catch (RuntimeException $e) { flash($e->getMessage(), 'err'); }
    catch (Throwable $e) { error_log($e->getMessage()); flash('Erreur interne.', 'err'); }
    rediriger('tarifs.php');
}

$edit = null;
if (isset($_GET['edit'])) { $st = $pdo->prepare('SELECT * FROM tarifs WHERE id = ?'); $st->execute([(int)$_GET['edit']]); $edit = $st->fetch() ?: null; }
$lignes = $pdo->query('SELECT * FROM tarifs ORDER BY ordre, id')->fetchAll();
$mode = reglages()['prix_mode'];
admin_entete('Grille tarifaire', 'tarifs');
?>
<?php if ($mode === 'aucun'): ?><div class="adm-flash adm-flash--info">La grille n'est pas affichée sur le site : le mode d'affichage actuel est « aucun prix ». <a href="index.php">Changer</a></div><?php endif; ?>
<p class="adm-aide">Les tarifs « Particuliers » s'affichent sous les glaces et les confitures ; les tarifs « Professionnels » (prix TTC) dans la section « Boutiques, restaurants, épiceries ».</p>
<form method="post" class="adm-carte adm-form">
    <?= csrf_champ() ?><input type="hidden" name="action" value="enregistrer"><input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
    <h2><?= $edit ? 'Modifier la ligne' : 'Ajouter une ligne' ?></h2>
    <div class="adm-grille2">
        <label>Pour qui ? <select name="cible"><?php foreach (['particulier' => 'Particuliers', 'pro' => 'Professionnels (TTC)'] as $k => $l): ?><option value="<?= $k ?>" <?= ($edit['cible'] ?? 'particulier') === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></label>
        <label>Produit <select name="groupe"><?php foreach (['glaces' => 'Glaces', 'confitures' => 'Confitures de lait'] as $k => $l): ?><option value="<?= $k ?>" <?= ($edit['groupe'] ?? 'glaces') === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></label>
    </div>
    <div class="adm-grille3">
        <label>Libellé * <input name="libelle" required maxlength="120" value="<?= h($edit['libelle'] ?? '') ?>" placeholder="ex. Pot 500 ml"></label>
        <label>Prix (€) * <input name="prix" required inputmode="decimal" value="<?= h($edit['prix'] ?? '') ?>"></label>
        <label>Ordre <input type="number" name="ordre" value="<?= h($edit['ordre'] ?? count($lignes) + 1) ?>"></label>
    </div>
    <button class="adm-btn"><?= $edit ? 'Mettre à jour' : 'Ajouter' ?></button>
    <?php if ($edit): ?><a class="adm-btn adm-btn--clair" href="tarifs.php">Annuler</a><?php endif; ?>
</form>
<div class="adm-carte adm-table-wrap"><table class="adm-table">
    <thead><tr><th>Libellé</th><th>Produit</th><th>Pour</th><th>Prix</th><th>Ordre</th><th>Actions</th></tr></thead><tbody>
    <?php foreach ($lignes as $l): ?>
        <tr><td><?= h($l['libelle']) ?></td><td><?= $l['groupe'] === 'confitures' ? 'Confitures' : 'Glaces' ?></td><td><?= $l['cible'] === 'pro' ? 'Pros (TTC)' : 'Particuliers' ?></td><td><?= h(prix_fr($l['prix'])) ?></td><td><?= (int)$l['ordre'] ?></td>
        <td class="adm-actions"><a class="adm-btn adm-btn--p" href="tarifs.php?edit=<?= (int)$l['id'] ?>">Modifier</a>
        <form method="post" data-confirmer="Supprimer cette ligne ?"><?= csrf_champ() ?><input type="hidden" name="action" value="supprimer"><input type="hidden" name="id" value="<?= (int)$l['id'] ?>"><button class="adm-btn adm-btn--p adm-btn--danger">Supprimer</button></form></td></tr>
    <?php endforeach; if (!$lignes): ?><tr><td colspan="6">Aucune ligne.</td></tr><?php endif; ?>
</tbody></table></div>
<?php admin_pied();

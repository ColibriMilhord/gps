<?php
// CRUD générique piloté par une configuration (rendez-vous, partenaires…).
require_once __DIR__ . '/_layout.php';

/**
 * $cfg = [
 *   'table', 'page', 'titre', 'singulier', 'aide' (facultatif), 'tri' (SQL ORDER BY),
 *   'champs'   => [[nom, libellé, type(text|textarea|date|select|url|number), options]],  // options : ['requis'=>1,'choix'=>[..],'max'=>n,'aide'=>'..','large'=>1]
 *   'colonnes' => [[libellé, fn(array $ligne): string]],
 * ]
 */
function page_crud(array $cfg): void {
    admin_exiger();
    $pdo = base_exigee();
    $t = $cfg['table']; $page = $cfg['page'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_verifier();
        $id = (int)($_POST['id'] ?? 0);
        try {
            $action = $_POST['action'] ?? '';
            if ($action === 'supprimer' && $id) {
                $pdo->prepare("DELETE FROM $t WHERE id = ?")->execute([$id]); flash('Supprimé.');
            } elseif ($action === 'visibilite' && $id) {
                $pdo->prepare("UPDATE $t SET visible = 1 - visible WHERE id = ?")->execute([$id]); flash('Visibilité modifiée.');
            } elseif ($action === 'enregistrer') {
                $d = [];
                foreach ($cfg['champs'] as [$nom, $lib, $type, $o]) {
                    $o = $o ?? [];
                    $val = trim((string)($_POST[$nom] ?? ''));
                    if (!empty($o['requis']) && $val === '') throw new RuntimeException("« $lib » est obligatoire.");
                    if ($val === '') { $d[$nom] = $type === 'number' ? 0 : null; continue; }
                    switch ($type) {
                        case 'date':
                            $dt = DateTime::createFromFormat('Y-m-d', $val);
                            if (!$dt || $dt->format('Y-m-d') !== $val) throw new RuntimeException("« $lib » : date invalide.");
                            break;
                        case 'select':
                            if (!isset($o['choix'][$val])) throw new RuntimeException("« $lib » : choix invalide.");
                            break;
                        case 'url':
                            if (!preg_match('#^https?://#i', $val) || !filter_var($val, FILTER_VALIDATE_URL)) throw new RuntimeException("« $lib » : adresse web invalide (https://…).");
                            break;
                        case 'number': $val = (int)$val; break;
                    }
                    $d[$nom] = is_string($val) ? mb_substr($val, 0, $o['max'] ?? ($type === 'textarea' ? 2000 : 255)) : $val;
                }
                if (isset($d['date_debut'], $d['date_fin']) && $d['date_fin'] < $d['date_debut']) throw new RuntimeException('La date de fin précède la date de début.');
                $d['visible'] = !empty($_POST['visible']) ? 1 : 0;
                if ($id) {
                    $set = implode(', ', array_map(fn($c) => "$c = ?", array_keys($d)));
                    $pdo->prepare("UPDATE $t SET $set WHERE id = ?")->execute([...array_values($d), $id]);
                } else {
                    $pdo->prepare("INSERT INTO $t (" . implode(', ', array_keys($d)) . ') VALUES (' . implode(', ', array_fill(0, count($d), '?')) . ')')->execute(array_values($d));
                }
                flash('Enregistré.');
            }
        } catch (RuntimeException $e) { flash($e->getMessage(), 'err'); }
        catch (Throwable $e) { error_log($e->getMessage()); flash('Erreur interne, rien n\'a été enregistré.', 'err'); }
        rediriger($page);
    }

    $edit = null;
    if (isset($_GET['edit'])) { $st = $pdo->prepare("SELECT * FROM $t WHERE id = ?"); $st->execute([(int)$_GET['edit']]); $edit = $st->fetch() ?: null; }
    $lignes = $pdo->query("SELECT * FROM $t ORDER BY " . $cfg['tri'])->fetchAll();

    admin_entete($cfg['titre'], $cfg['actif']);
    if (!empty($cfg['aide'])) echo '<div class="adm-flash adm-flash--info">' . $cfg['aide'] . '</div>';
    ?>
    <form method="post" class="adm-carte adm-form" id="formulaire">
        <?= csrf_champ() ?><input type="hidden" name="action" value="enregistrer"><input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
        <h2><?= $edit ? 'Modifier' : 'Ajouter ' . h($cfg['singulier']) ?></h2>
        <div class="adm-grille2">
        <?php foreach ($cfg['champs'] as [$nom, $lib, $type, $o]): $o = $o ?? []; $val = $edit[$nom] ?? ($o['defaut'] ?? ''); $req = !empty($o['requis']); ?>
            <label class="<?= !empty($o['large']) ? 'adm-large' : '' ?>"><?= h($lib) ?><?= $req ? ' *' : '' ?>
            <?php if ($type === 'textarea'): ?><textarea name="<?= $nom ?>" rows="3"><?= h($val) ?></textarea>
            <?php elseif ($type === 'select'): ?><select name="<?= $nom ?>"><?php foreach ($o['choix'] as $k => $l): ?><option value="<?= h($k) ?>" <?= (string)$val === (string)$k ? 'selected' : '' ?>><?= h($l) ?></option><?php endforeach; ?></select>
            <?php else: ?><input name="<?= $nom ?>" type="<?= in_array($type, ['date', 'number'], true) ? $type : ($type === 'url' ? 'url' : 'text') ?>" value="<?= h($val) ?>" <?= $req ? 'required' : '' ?> <?= !empty($o['placeholder']) ? 'placeholder="' . h($o['placeholder']) . '"' : '' ?>><?php endif; ?>
            <?php if (!empty($o['aide'])): ?><small class="adm-aide"><?= h($o['aide']) ?></small><?php endif; ?></label>
        <?php endforeach; ?>
        </div>
        <label class="adm-case"><input type="checkbox" name="visible" value="1" <?= (!$edit || $edit['visible']) ? 'checked' : '' ?>> Visible sur le site</label>
        <button class="adm-btn"><?= $edit ? 'Mettre à jour' : 'Ajouter' ?></button>
        <?php if ($edit): ?><a class="adm-btn adm-btn--clair" href="<?= h($page) ?>">Annuler</a><?php endif; ?>
    </form>

    <div class="adm-carte adm-table-wrap"><table class="adm-table">
        <thead><tr><?php foreach ($cfg['colonnes'] as [$lib]): ?><th><?= h($lib) ?></th><?php endforeach; ?><th>Statut</th><th>Actions</th></tr></thead><tbody>
        <?php foreach ($lignes as $l): ?>
            <tr class="<?= $l['visible'] ? '' : 'masque' ?>">
                <?php foreach ($cfg['colonnes'] as [, $fn]): ?><td><?= h($fn($l)) ?></td><?php endforeach; ?>
                <td><?= $l['visible'] ? 'Visible' : 'Masqué' ?></td>
                <td class="adm-actions">
                    <a class="adm-btn adm-btn--p" href="<?= h($page) ?>?edit=<?= (int)$l['id'] ?>#formulaire">Modifier</a>
                    <form method="post"><?= csrf_champ() ?><input type="hidden" name="action" value="visibilite"><input type="hidden" name="id" value="<?= (int)$l['id'] ?>"><button class="adm-btn adm-btn--p adm-btn--clair"><?= $l['visible'] ? 'Masquer' : 'Afficher' ?></button></form>
                    <form method="post" data-confirmer="Supprimer définitivement cet élément ?"><?= csrf_champ() ?><input type="hidden" name="action" value="supprimer"><input type="hidden" name="id" value="<?= (int)$l['id'] ?>"><button class="adm-btn adm-btn--p adm-btn--danger">Supprimer</button></form>
                </td>
            </tr>
        <?php endforeach; if (!$lignes): ?><tr><td colspan="<?= count($cfg['colonnes']) + 2 ?>">Aucun élément pour l'instant.</td></tr><?php endif; ?>
        </tbody></table></div>
    <?php
    admin_pied();
}

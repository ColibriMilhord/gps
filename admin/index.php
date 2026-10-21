<?php
// Tableau de bord : module Noël, affichage des prix, coordonnées.
require_once __DIR__ . '/_layout.php';
admin_exiger();
$pdo = base_exigee();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verifier();
    try {
        reglage_enregistrer('noel_actif', !empty($_POST['noel_actif']) ? '1' : '0');
        $mode = $_POST['prix_mode'] ?? 'individuel';
        reglage_enregistrer('prix_mode', in_array($mode, ['individuel', 'grille', 'aucun'], true) ? $mode : 'individuel');
        foreach (['noel_message', 'telephone', 'email', 'adresse', 'marches'] as $c) {
            reglage_enregistrer($c, mb_substr(trim((string)($_POST[$c] ?? '')), 0, 500));
        }
        flash('Paramètres enregistrés.');
    } catch (Throwable $e) { error_log($e->getMessage()); flash("Enregistrement impossible.", 'err'); }
    rediriger('index.php');
}

$R = reglages();
$n = fn($t) => (int)$pdo->query("SELECT COUNT(*) FROM $t")->fetchColumn();
admin_entete('Tableau de bord', 'index');
?>
<div class="adm-stats">
    <a class="adm-carte" href="glaces.php"><strong><?= $n('glaces') ?></strong> parfum(s) de glace</a>
    <a class="adm-carte" href="buches.php"><strong><?= $n('buches') ?></strong> bûche(s)</a>
    <a class="adm-carte" href="tarifs.php"><strong><?= $n('tarifs') ?></strong> ligne(s) de tarif</a>
    <a class="adm-carte" href="lieux.php"><strong><?= $n('lieux') ?></strong> rendez-vous</a>
    <a class="adm-carte" href="partenaires.php"><strong><?= $n('partenaires') ?></strong> partenaire(s)</a>
    <a class="adm-carte" href="messages.php"><strong><?= messages_non_lus() ?></strong> message(s) non lu(s)</a>
</div>

<form method="post" class="adm-carte adm-form">
    <?= csrf_champ() ?>
    <h2>🎄 Période de Noël</h2>
    <label class="adm-interrupteur">
        <input type="checkbox" name="noel_actif" value="1" <?= $R['noel_actif'] === '1' ? 'checked' : '' ?>>
        <span class="piste"></span>
        <span>Activer le mode Noël (flocons, couleurs de fête et section « Nos bûches de Noël sur commande » sur l'accueil)</span>
    </label>
    <label>Message affiché au-dessus des bûches
        <textarea name="noel_message" rows="2"><?= h($R['noel_message']) ?></textarea></label>

    <h2>💶 Affichage des prix</h2>
    <fieldset>
        <label class="adm-radio"><input type="radio" name="prix_mode" value="individuel" <?= $R['prix_mode'] === 'individuel' ? 'checked' : '' ?>> Prix affiché sur chaque glace / bûche</label>
        <label class="adm-radio"><input type="radio" name="prix_mode" value="grille" <?= $R['prix_mode'] === 'grille' ? 'checked' : '' ?>> Grille tarifaire générale (<a href="tarifs.php">à gérer ici</a>)</label>
        <label class="adm-radio"><input type="radio" name="prix_mode" value="aucun" <?= $R['prix_mode'] === 'aucun' ? 'checked' : '' ?>> Ne pas afficher de prix</label>
    </fieldset>

    <h2>📍 Coordonnées</h2>
    <div class="adm-grille2">
        <label>Téléphone <input name="telephone" value="<?= h($R['telephone']) ?>"></label>
        <label>Courriel (reçoit aussi les messages du formulaire de contact) <input type="email" name="email" value="<?= h($R['email']) ?>"></label>
        <label>Adresse <input name="adresse" value="<?= h($R['adresse']) ?>"></label>
        <label>Marchés <input name="marches" value="<?= h($R['marches']) ?>"></label>
    </div>
    <button class="adm-btn">Enregistrer</button>
</form>
<?php admin_pied();

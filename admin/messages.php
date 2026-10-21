<?php
// Messages reçus par le formulaire de contact.
require_once __DIR__ . '/_layout.php';
admin_exiger();
$pdo = base_exigee();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verifier();
    $id = (int)($_POST['id'] ?? 0);
    if (($_POST['action'] ?? '') === 'supprimer') { $pdo->prepare('DELETE FROM messages WHERE id = ?')->execute([$id]); flash('Message supprimé.'); }
    elseif (($_POST['action'] ?? '') === 'lu') { $pdo->prepare('UPDATE messages SET lu = 1 - lu WHERE id = ?')->execute([$id]); }
    rediriger('messages.php');
}
$profils = profils_contact(true);
$lignes = $pdo->query('SELECT * FROM messages ORDER BY cree_le DESC, id DESC LIMIT 200')->fetchAll();
admin_entete('Messages reçus', 'messages');
if (!$lignes) echo '<p class="adm-carte">Aucun message pour l\'instant.</p>';
foreach ($lignes as $m): ?>
    <article class="adm-carte adm-message <?= $m['lu'] ? '' : 'adm-message--nouveau' ?>">
        <header>
            <strong><?= h($m['nom']) ?></strong> <?= $m['etablissement'] ? '(' . h($m['etablissement']) . ')' : '' ?>
            <span class="adm-puce"><?= h($profils[$m['profil']] ?? $m['profil']) ?></span>
            <time><?= h(date('d/m/Y H:i', strtotime($m['cree_le']))) ?></time>
        </header>
        <p><a href="mailto:<?= h($m['email']) ?>"><?= h($m['email']) ?></a><?= $m['telephone'] ? ' · <a href="tel:' . h(preg_replace('/[^0-9+]/', '', $m['telephone'])) . '">' . h($m['telephone']) . '</a>' : '' ?></p>
        <?php if ($m['produit'] || $m['date_souhaitee']): ?><p class="adm-aide"><?= $m['produit'] ? 'Demande : ' . h($m['produit']) . ' · ' : '' ?><?= $m['date_souhaitee'] ? 'Date souhaitée : ' . h($m['date_souhaitee']) : '' ?></p><?php endif; ?>
        <p><?= nl2br(h($m['message'])) ?></p>
        <div class="adm-actions">
            <form method="post"><?= csrf_champ() ?><input type="hidden" name="action" value="lu"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>"><button class="adm-btn adm-btn--p adm-btn--clair"><?= $m['lu'] ? 'Marquer non lu' : 'Marquer comme lu' ?></button></form>
            <form method="post" data-confirmer="Supprimer ce message ?"><?= csrf_champ() ?><input type="hidden" name="action" value="supprimer"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>"><button class="adm-btn adm-btn--p adm-btn--danger">Supprimer</button></form>
        </div>
    </article>
<?php endforeach; admin_pied();

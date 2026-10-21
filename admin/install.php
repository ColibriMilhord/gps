<?php
// Première installation : importe le schéma si besoin et crée le premier compte.
// Cette page se verrouille toute seule dès qu'un compte existe.
require_once __DIR__ . '/_layout.php';
session_demarrer();
$pdo = base_exigee();

$tablesOk = true;
try { $nb = (int)$pdo->query('SELECT COUNT(*) FROM admin')->fetchColumn(); } catch (Throwable $e) { $tablesOk = false; $nb = 0; }
if ($nb > 0) { http_response_code(403); exit('Installation déjà effectuée. Supprimez ce fichier du serveur.'); }

$erreur = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verifier();
    $id = trim($_POST['identifiant'] ?? '');
    $mdp = (string)($_POST['mot_de_passe'] ?? '');
    if (!preg_match('/^[A-Za-z0-9._-]{3,60}$/', $id)) $erreur = "Identifiant : 3 à 60 caractères (lettres, chiffres, . _ -).";
    elseif (strlen($mdp) < 10) $erreur = 'Mot de passe : 10 caractères minimum.';
    elseif ($mdp !== (string)($_POST['mot_de_passe2'] ?? '')) $erreur = 'Les deux mots de passe ne correspondent pas.';
    else {
        try {
            if (!$tablesOk) {
                $sql = file_get_contents(__DIR__ . '/../sql/schema.sql');
                $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
                $pdo->exec($sql);
            }
            $pdo->prepare('INSERT INTO admin (identifiant, mot_de_passe) VALUES (?, ?)')
                ->execute([$id, password_hash($mdp, PASSWORD_DEFAULT)]);
            flash('Compte créé. Connectez-vous. Pensez à supprimer admin/install.php du serveur.');
            rediriger('login.php');
        } catch (Throwable $e) { error_log($e->getMessage()); $erreur = "Échec de l'installation (voir les journaux du serveur)."; }
    }
}
admin_entete('Installation', 'login');
?>
<form method="post" class="adm-carte adm-login">
    <p><?= $tablesOk ? 'Créez le compte administrateur.' : 'Les tables seront créées, puis le compte administrateur.' ?></p>
    <?php if ($erreur): ?><div class="adm-flash adm-flash--err"><?= h($erreur) ?></div><?php endif; ?>
    <?= csrf_champ() ?>
    <label>Identifiant <input name="identifiant" required autocomplete="username"></label>
    <label>Mot de passe (10 caractères min.) <input type="password" name="mot_de_passe" required autocomplete="new-password"></label>
    <label>Confirmation <input type="password" name="mot_de_passe2" required autocomplete="new-password"></label>
    <button class="adm-btn">Créer le compte</button>
</form>
<?php admin_pied();

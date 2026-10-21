<?php
require_once __DIR__ . '/_layout.php';
session_demarrer();
$pdo = base_exigee();

// Aucun compte : première installation
try { $nb = (int)$pdo->query('SELECT COUNT(*) FROM admin')->fetchColumn(); }
catch (Throwable $e) { rediriger('install.php'); }
if ($nb === 0) rediriger('install.php');

$erreur = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verifier();
    $ip = substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45);
    // Limitation : 5 échecs en 15 minutes par adresse IP
    $seuil = date('Y-m-d H:i:s', time() - 900);
    $st = $pdo->prepare('SELECT COUNT(*) FROM tentatives_connexion WHERE ip = ? AND date_essai > ?');
    $st->execute([$ip, $seuil]);
    if ((int)$st->fetchColumn() >= 5) {
        $erreur = 'Trop de tentatives. Réessayez dans quelques minutes.';
    } else {
        $st = $pdo->prepare('SELECT * FROM admin WHERE identifiant = ?');
        $st->execute([trim($_POST['identifiant'] ?? '')]);
        $u = $st->fetch();
        if ($u && password_verify((string)($_POST['mot_de_passe'] ?? ''), $u['mot_de_passe'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int)$u['id'];
            $pdo->prepare('DELETE FROM tentatives_connexion WHERE ip = ?')->execute([$ip]);
            $pdo->prepare('UPDATE admin SET derniere_connexion = ? WHERE id = ?')->execute([date('Y-m-d H:i:s'), $u['id']]);
            rediriger('index.php');
        }
        $pdo->prepare('INSERT INTO tentatives_connexion (ip, date_essai) VALUES (?, ?)')->execute([$ip, date('Y-m-d H:i:s')]);
        $erreur = 'Identifiant ou mot de passe incorrect.';
    }
}
if (admin_connecte()) rediriger('index.php');
admin_entete('Connexion', 'login');
?>
<form method="post" class="adm-carte adm-login">
    <img src="../assets/img/logo.png" alt="Les Sublimes" width="110" height="110">
    <?php if ($erreur): ?><div class="adm-flash adm-flash--err"><?= h($erreur) ?></div><?php endif; ?>
    <?= csrf_champ() ?>
    <label>Identifiant <input name="identifiant" autocomplete="username" required autofocus></label>
    <label>Mot de passe <input type="password" name="mot_de_passe" autocomplete="current-password" required></label>
    <button class="adm-btn">Se connecter</button>
</form>
<?php admin_pied();

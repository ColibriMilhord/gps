<?php
// ============================================================
//  Socle commun (site public + administration) :
//  connexion base de données, session, CSRF, helpers, envoi d'images.
// ============================================================

if (!defined('BOOTSTRAP_OK')) {
    define('BOOTSTRAP_OK', true);
    define('RACINE', dirname(__DIR__));

    if (is_file(RACINE . '/config.local.php')) {
        require_once RACINE . '/config.local.php';
    }
    if (!defined('SITE_URL')) define('SITE_URL', 'https://gaecdupointsublime.com');
}

/** Connexion PDO unique (null si la base n'est pas configurée / joignable). */
function db(): ?PDO {
    static $pdo = false;
    if ($pdo !== false) return $pdo;
    try {
        if (defined('DB_DSN')) { // surcharge (tests locaux)
            $pdo = new PDO(DB_DSN, null, null);
        } elseif (defined('DB_NAME')) {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER, DB_PASS
            );
        } else {
            return $pdo = null;
        }
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    } catch (Throwable $e) {
        error_log('DB: ' . $e->getMessage());
        $pdo = null;
    }
    return $pdo;
}

/** Échappement HTML. */
function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

/** Prix formaté « 3,50 € ». */
function prix_fr($p): string { return number_format((float)$p, 2, ',', ' ') . ' €'; }

/** Tous les paramètres du site, avec valeurs par défaut si la base est vide. */
function reglages(): array {
    static $cache = null;
    if ($cache !== null) return $cache;
    $cache = [
        'noel_actif' => '0', 'noel_message' => '', 'prix_mode' => 'individuel',
        'telephone' => '', 'email' => '', 'adresse' => 'Ferme du Tensonnieu, aux portes des Gorges du Tarn',
        'marches' => "Marchés d'Aveyron et de Lozère",
        'telephone_2' => '', 'glaces_bientot' => '', 'noel_infos' => '',
    ];
    if ($pdo = db()) {
        try {
            foreach ($pdo->query('SELECT cle, valeur FROM settings_site') as $l) $cache[$l['cle']] = (string)$l['valeur'];
        } catch (Throwable $e) { error_log('settings: ' . $e->getMessage()); }
    }
    return $cache;
}

function reglage_enregistrer(string $cle, string $valeur): void {
    $pdo = db();
    $st = $pdo->prepare('UPDATE settings_site SET valeur = ? WHERE cle = ?');
    $st->execute([$valeur, $cle]);
    if ($st->rowCount() === 0) {
        $existe = $pdo->prepare('SELECT COUNT(*) FROM settings_site WHERE cle = ?');
        $existe->execute([$cle]);
        if (!(int)$existe->fetchColumn()) {
            $pdo->prepare('INSERT INTO settings_site (cle, valeur) VALUES (?, ?)')->execute([$cle, $valeur]);
        }
    }
}

/** URL d'un fichier envoyé (dossier uploads/<type>/). */
function upload_url(?string $fichier, string $type): ?string {
    if (!$fichier) return null;
    return 'uploads/' . $type . '/' . rawurlencode($fichier);
}

// ------------------------------------------------------------
//  Données publiques
// ------------------------------------------------------------
function produits_visibles(string $table): array {
    if (!in_array($table, ['glaces', 'buches', 'confitures'], true) || !($pdo = db())) return [];
    try {
        return $pdo->query("SELECT * FROM $table WHERE visible = 1 ORDER BY ordre, id")->fetchAll();
    } catch (Throwable $e) { error_log($e->getMessage()); return []; }
}

function grille_tarifs(): array {
    if (!($pdo = db())) return [];
    try { return $pdo->query('SELECT * FROM tarifs ORDER BY ordre, id')->fetchAll(); }
    catch (Throwable $e) { error_log($e->getMessage()); return []; }
}

// ------------------------------------------------------------
//  Session, CSRF, authentification (administration)
// ------------------------------------------------------------
function session_demarrer(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    session_name('sublimes_admin');
    session_set_cookie_params([
        'lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']),
    ]);
    session_start();
}

function csrf_token(): string {
    session_demarrer();
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_champ(): string { return '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">'; }
function csrf_verifier(): void {
    session_demarrer();
    $ok = isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)$_POST['csrf']);
    if (!$ok) { http_response_code(403); exit('Session expirée ou requête invalide. Rechargez la page.'); }
}

function admin_connecte(): bool { session_demarrer(); return !empty($_SESSION['admin_id']); }
function admin_exiger(): void {
    if (!admin_connecte()) { header('Location: login.php'); exit; }
    migrer_base();
    header('X-Robots-Tag: noindex, nofollow');
}

/** Message « flash » affiché à la page suivante. */
function flash(?string $msg = null, string $type = 'ok'): ?array {
    session_demarrer();
    if ($msg !== null) { $_SESSION['flash'] = [$msg, $type]; return null; }
    $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f;
}

// ------------------------------------------------------------
//  Envoi d'images (photo ou logo) — validation stricte
// ------------------------------------------------------------
/**
 * Enregistre l'image du champ $champ dans uploads/$dossier/, redimensionnée et recadrée automatiquement
 * (voir includes/images.php). Retourne le nom de fichier, null si aucun fichier envoyé, ou lève une exception.
 */
function image_enregistrer(string $champ, string $dossier): ?string {
    if (empty($_FILES[$champ]) || $_FILES[$champ]['error'] === UPLOAD_ERR_NO_FILE) return null;
    $f = $_FILES[$champ];
    if ($f['error'] === UPLOAD_ERR_INI_SIZE || $f['error'] === UPLOAD_ERR_FORM_SIZE) throw new RuntimeException('Image trop lourde pour le serveur : réduisez-la ou envoyez une autre version.');
    if ($f['error'] !== UPLOAD_ERR_OK) throw new RuntimeException("Échec de l'envoi de l'image (code {$f['error']}).");
    if ($f['size'] > 25 * 1024 * 1024) throw new RuntimeException('Image trop lourde (25 Mo maximum).');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true) || !@getimagesize($f['tmp_name'])) {
        throw new RuntimeException('Format non accepté : JPEG, PNG ou WebP uniquement.');
    }
    require_once __DIR__ . '/images.php';
    $dir = RACINE . '/uploads/' . $dossier;
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) throw new RuntimeException('Dossier uploads inaccessible.');
    $nom = bin2hex(random_bytes(8)) . '.jpg';
    image_traiter($f['tmp_name'], "$dir/$nom", $dossier === 'logos' ? 'logo' : 'photo');
    return $nom;
}

function image_supprimer(?string $fichier, string $dossier): void {
    if (!$fichier || $fichier !== basename($fichier)) return;
    $p = RACINE . '/uploads/' . $dossier . '/' . $fichier;
    if (is_file($p)) @unlink($p);
}

// ------------------------------------------------------------
//  v1.1.0 — rendez-vous (lieux), partenaires, migration
// ------------------------------------------------------------
const TYPES_LIEU = ['ferme' => 'À la ferme', 'marche' => 'Marché', 'evenement' => 'Événement'];
const CATEGORIES_PARTENAIRE = ['boutique' => 'Boutique', 'restaurant' => 'Restaurant', 'epicerie' => 'Épicerie', 'autre' => 'Autre'];

/** « sam. 25 octobre 2026 » (sans dépendre de l'extension intl). */
function date_fr(?string $ymd, bool $annee = true): string {
    if (!$ymd || !($t = strtotime($ymd))) return '';
    $j = ['dim.', 'lun.', 'mar.', 'mer.', 'jeu.', 'ven.', 'sam.'];
    $m = ['', 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
    return $j[(int)date('w', $t)] . ' ' . (int)date('j', $t) . ' ' . $m[(int)date('n', $t)] . ($annee ? ' ' . date('Y', $t) : '');
}

/** Phrase de date d'un rendez-vous : période, date unique ou récurrence. */
function lieu_quand(array $l): string {
    $d = $l['date_debut'] ?? null; $f = $l['date_fin'] ?? null;
    if ($d && $f && $f !== $d) return 'Du ' . date_fr($d, false) . ' au ' . date_fr($f);
    if ($d) return date_fr($d);
    return (string)($l['recurrence'] ?? '');
}

/** Rendez-vous visibles et non terminés : datés d'abord (chronologique), puis permanents. */
function lieux_a_venir(): array {
    if (!($pdo = db())) return [];
    try {
        $st = $pdo->prepare('SELECT * FROM lieux WHERE visible = 1
            AND ((date_fin IS NOT NULL AND date_fin >= :j1) OR (date_fin IS NULL AND (date_debut IS NULL OR date_debut >= :j2)))
            ORDER BY (date_debut IS NULL), date_debut, ordre, id');
        $st->execute([':j1' => date('Y-m-d'), ':j2' => date('Y-m-d')]);
        return $st->fetchAll();
    } catch (Throwable $e) { error_log('lieux: ' . $e->getMessage()); return []; }
}

function partenaires_visibles(): array {
    if (!($pdo = db())) return [];
    try { return $pdo->query('SELECT * FROM partenaires WHERE visible = 1 ORDER BY ordre, nom')->fetchAll(); }
    catch (Throwable $e) { error_log('partenaires: ' . $e->getMessage()); return []; }
}

/** Lien carte (recherche) pour une adresse. */
function lien_carte(array $l): ?string {
    $q = trim(($l['adresse'] ?? '') . ' ' . ($l['ville'] ?? ''));
    return $q === '' ? null : 'https://www.openstreetmap.org/search?query=' . rawurlencode($q);
}

/**
 * Met la base au niveau du code (idempotent, appelé par l'administration et au premier affichage public) :
 *  - 1.1.0 : tables lieux / partenaires / messages ;
 *  - 1.2.0 : colonnes ingrédients + poids sur les glaces, catalogue initial des parfums, coordonnées de la ferme.
 *  - 1.4.0 : confitures de lait, tarifs particuliers / professionnels, bûches (voir migrer_v14).
 * Le catalogue n'est injecté qu'une fois (drapeau catalogue_v12) : un parfum supprimé ensuite ne revient pas.
 */
function migrer_base(): void {
    $pdo = db(); if (!$pdo) return;
    try {
        try { $pdo->query('SELECT 1 FROM messages LIMIT 1'); }
        catch (Throwable $e) {
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
            $pdo->exec((string)file_get_contents(RACINE . '/sql/migration-1.1.0.sql'));
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        }
        try { $pdo->query('SELECT ingredients, poids FROM glaces LIMIT 1'); }
        catch (Throwable $e) {
            $pdo->exec('ALTER TABLE glaces ADD COLUMN ingredients TEXT NULL');
            $pdo->exec('ALTER TABLE glaces ADD COLUMN poids VARCHAR(60) NULL');
        }
        migrer_logos_v13($pdo);
        $f = $pdo->prepare("SELECT valeur FROM settings_site WHERE cle = 'catalogue_v12'"); $f->execute();
        if ($f->fetchColumn() === '1') { migrer_v14_securisee($pdo); return; }

        $catalogue = require RACINE . '/includes/catalogue-initial.php';
        $cherche = $pdo->prepare('SELECT id, ingredients, logo FROM glaces WHERE nom LIKE ? ORDER BY id LIMIT 1');
        foreach ($catalogue as $g) {
            $cherche->execute([$g['recherche'] ?? $g['nom']]);
            if ($ex = $cherche->fetch()) {   // parfum déjà saisi : on complète sans écraser
                $pdo->prepare('UPDATE glaces SET ingredients = COALESCE(ingredients, ?), poids = COALESCE(poids, ?), logo = COALESCE(logo, ?) WHERE id = ?')
                    ->execute([$g['ingredients'], POIDS_POT, $g['logo'], $ex['id']]);
            } else {
                $pdo->prepare('INSERT INTO glaces (nom, ingredients, poids, logo, ordre, visible) VALUES (?, ?, ?, ?, ?, 1)')
                    ->execute([$g['nom'], $g['ingredients'], POIDS_POT, $g['logo'], $g['ordre']]);
            }
        }
        // Coordonnées de la ferme (étiquettes) : seulement si les champs sont encore vides
        foreach (['telephone' => '06 51 10 67 33', 'email' => 'contact@gaecdupointsublime.com'] as $c => $v) {
            $pdo->prepare("UPDATE settings_site SET valeur = ? WHERE cle = ? AND (valeur IS NULL OR valeur = '')")->execute([$v, $c]);
        }
        $pdo->prepare("UPDATE settings_site SET valeur = ? WHERE cle = 'adresse' AND valeur LIKE 'Ferme du Tensonnieu, aux portes%'")
            ->execute(['Ferme du Tensonnieu — 12 rue Jules Ricard, 48500 Massegros Causses Gorges']);
        reglage_enregistrer('catalogue_v12', '1');
        migrer_v14_securisee($pdo);   // après le catalogue : sur une installation neuve, les parfums existent alors
    } catch (Throwable $e) { error_log('migration: ' . $e->getMessage()); }
}

/** Une panne de la migration 1.4.0 ne doit pas empêcher le reste : on journalise et le prochain affichage réessaie. */
function migrer_v14_securisee(PDO $pdo): void {
    try { migrer_v14($pdo); } catch (Throwable $e) { error_log('migration 1.4.0: ' . $e->getMessage()); }
}

/**
 * 1.3.0 : logos de Vanille, Chocolat, Yaourt et Pomme. Ne touche que les parfums sans logo ; si une étiquette
 * avait déjà été envoyée comme « photo », elle est reprise comme logo (puis la copie en doublon est retirée).
 */
function migrer_logos_v13(PDO $pdo): void {
    $f = $pdo->prepare("SELECT valeur FROM settings_site WHERE cle = 'logos_v13'"); $f->execute();
    if ($f->fetchColumn() === '1') return;
    foreach (['Vanille' => 'logo-vanille.jpg', 'Chocolat' => 'logo-chocolat.jpg', 'Yaourt' => 'logo-yaourt.jpg', 'Pomme' => 'logo-pomme.jpg'] as $nom => $logo) {
        $pdo->prepare("UPDATE glaces SET logo = ? WHERE nom LIKE ? AND (logo IS NULL OR logo = '')")->execute([$logo, $nom . '%']);
    }
    reglage_enregistrer('logos_v13', '1');
}

/**
 * 1.4.0 : données des flyers (includes/catalogue-v14.php). Crée la table confitures et les colonnes cible/groupe des tarifs,
 * puis ajoute ce qui manque sans jamais écraser une saisie existante. Rejouable : le drapeau catalogue_v14 n'est posé qu'à la fin.
 */
function migrer_v14(PDO $pdo): void {
    $f = $pdo->prepare("SELECT valeur FROM settings_site WHERE cle = 'catalogue_v14'"); $f->execute();
    if ($f->fetchColumn() === '1') return;

    try { $pdo->query('SELECT cible, groupe FROM tarifs LIMIT 1'); }
    catch (Throwable $e) {
        $pdo->exec("ALTER TABLE tarifs ADD COLUMN cible VARCHAR(12) NOT NULL DEFAULT 'particulier'");
        $pdo->exec("ALTER TABLE tarifs ADD COLUMN groupe VARCHAR(12) NOT NULL DEFAULT 'glaces'");
    }
    $pdo->exec("CREATE TABLE IF NOT EXISTS confitures (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(120) NOT NULL, description TEXT NULL, ingredients TEXT NULL, poids VARCHAR(60) NULL,
        image VARCHAR(255) NULL, logo VARCHAR(255) NULL, prix DECIMAL(6,2) NULL,
        visible TINYINT(1) NOT NULL DEFAULT 1, ordre INT NOT NULL DEFAULT 0,
        cree_le TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $c = require RACINE . '/includes/catalogue-v14.php';
    $pdo->beginTransaction();
    try {
        foreach ($c['tarifs_exemple'] as [$lib, $prix]) {
            $pdo->prepare('DELETE FROM tarifs WHERE libelle = ? AND prix = ?')->execute([$lib, $prix]);
        }
        $existe = $pdo->prepare('SELECT COUNT(*) FROM tarifs WHERE cible = ? AND groupe = ? AND libelle = ?');
        foreach ($c['tarifs'] as $t) {
            $existe->execute([$t['public'], $t['groupe'], $t['libelle']]);
            if (!(int)$existe->fetchColumn()) {
                $pdo->prepare('INSERT INTO tarifs (libelle, prix, ordre, cible, groupe) VALUES (?, ?, ?, ?, ?)')
                    ->execute([$t['libelle'], $t['prix'], $t['ordre'], $t['public'], $t['groupe']]);
            }
        }
        $pdo->prepare('UPDATE glaces SET prix = ? WHERE prix IS NULL')->execute([$c['prix_pot_glace']]);

        $cherche = $pdo->prepare('SELECT COUNT(*) FROM buches WHERE nom LIKE ?');
        foreach ($c['buches'] as $b) {
            $cherche->execute([$b['nom']]);
            if (!(int)$cherche->fetchColumn()) {
                $pdo->prepare('INSERT INTO buches (nom, description, parfums, parts, ordre, visible) VALUES (?, ?, ?, ?, ?, 1)')
                    ->execute([$b['nom'], $b['description'], $b['parfums'], $b['parts'], $b['ordre']]);
            }
        }
        $cherche = $pdo->prepare('SELECT COUNT(*) FROM confitures WHERE nom LIKE ?');
        foreach ($c['confitures'] as $k) {
            $cherche->execute([$k['nom']]);
            if (!(int)$cherche->fetchColumn()) {
                $pdo->prepare('INSERT INTO confitures (nom, description, prix, ordre, visible) VALUES (?, ?, ?, ?, 1)')
                    ->execute([$k['nom'], $c['confiture_description'], $c['prix_confiture'], $k['ordre']]);
            }
        }
        $lit = $pdo->prepare('SELECT valeur FROM settings_site WHERE cle = ?');
        foreach ($c['reglages'] as $cle => $valeur) {
            $lit->execute([$cle]);
            $actuel = $lit->fetchColumn();
            if ($actuel === false || $actuel === null || $actuel === '') reglage_enregistrer($cle, $valeur);
        }
        reglage_enregistrer('catalogue_v14', '1');
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

/** Au premier affichage public après une mise à jour, applique la migration (une requête légère ensuite). */
function migrer_si_besoin(): void {
    static $fait = false; if ($fait) return; $fait = true;
    if (!($pdo = db())) return;
    try {
        $st = $pdo->prepare("SELECT valeur FROM settings_site WHERE cle = 'catalogue_v12'"); $st->execute();
        $v13 = $pdo->prepare("SELECT valeur FROM settings_site WHERE cle = 'logos_v13'"); $v13->execute();
        $v14 = $pdo->prepare("SELECT valeur FROM settings_site WHERE cle = 'catalogue_v14'"); $v14->execute();
        if ($st->fetchColumn() === '1' && $v13->fetchColumn() === '1' && $v14->fetchColumn() === '1') return;
    } catch (Throwable $e) { /* table absente : sera créée par l'installation */ return; }
    migrer_base();
}

/** Profils du formulaire de contact. 'noel' = proposé seulement si le module Noël est actif. */
function profils_contact(bool $noel): array {
    $p = [
        'restaurant' => 'Je suis restaurateur·rice',
        'boutique'   => 'Je tiens une boutique ou une épicerie',
        'partenaire' => 'Je veux travailler avec vous',
        'visite'     => 'Je suis particulier et je veux visiter la ferme',
        'glace'      => 'Je souhaite commander des glaces',
        'confiture'  => 'Je souhaite commander des confitures de lait',
    ];
    if ($noel) $p['buche'] = 'Je souhaite commander une bûche de Noël';
    $p['autre'] = 'Autre demande';
    return $p;
}

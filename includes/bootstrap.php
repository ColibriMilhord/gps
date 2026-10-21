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
    if (!in_array($table, ['glaces', 'buches'], true) || !($pdo = db())) return [];
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
 * Enregistre l'image du champ $champ dans uploads/$dossier/.
 * Retourne le nom de fichier, null si aucun fichier envoyé, ou lève une exception.
 */
function image_enregistrer(string $champ, string $dossier): ?string {
    if (empty($_FILES[$champ]) || $_FILES[$champ]['error'] === UPLOAD_ERR_NO_FILE) return null;
    $f = $_FILES[$champ];
    if ($f['error'] !== UPLOAD_ERR_OK) throw new RuntimeException("Échec de l'envoi de l'image (code {$f['error']}).");
    if ($f['size'] > 8 * 1024 * 1024) throw new RuntimeException('Image trop lourde (8 Mo maximum).');
    $types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    if (!isset($types[$mime]) || !@getimagesize($f['tmp_name'])) {
        throw new RuntimeException('Format non accepté : JPEG, PNG ou WebP uniquement.');
    }
    $dir = RACINE . '/uploads/' . $dossier;
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) throw new RuntimeException('Dossier uploads inaccessible.');
    $nom = bin2hex(random_bytes(8)) . '.' . $types[$mime];
    if (!move_uploaded_file($f['tmp_name'], "$dir/$nom")) throw new RuntimeException("Impossible d'enregistrer l'image.");
    return $nom;
}

function image_supprimer(?string $fichier, string $dossier): void {
    if (!$fichier || $fichier !== basename($fichier)) return;
    $p = RACINE . '/uploads/' . $dossier . '/' . $fichier;
    if (is_file($p)) @unlink($p);
}

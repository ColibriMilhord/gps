<?php
// ============================================================
//  Traitement automatique des images envoyées dans l'administration.
//  L'artisan envoie ce qu'il a (photo de téléphone, étiquette exportée, PNG transparent…) :
//   - photo   → recadrée au format 4:3 (1200 × 900), orientation EXIF corrigée ;
//   - logo / étiquette ronde → marges blanches retirées, centrée sur un carré blanc (900 × 900) ;
//   - une « photo » qui est en réalité une étiquette (bords blancs) est traitée comme un logo.
//  Tout est recompressé (JPEG optimisé) : fichiers légers, affichage rapide, taille homogène.
// ============================================================

const IMG_SORTIE_PHOTO = [1200, 900];
const IMG_SORTIE_LOGO = 900;

/** Charge une image GD depuis un fichier (JPEG/PNG/WebP), orientation EXIF appliquée. */
function image_charger(string $chemin) {
    @ini_set('memory_limit', '384M');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($chemin);
    $im = match ($mime) {
        'image/jpeg' => @imagecreatefromjpeg($chemin),
        'image/png'  => @imagecreatefrompng($chemin),
        'image/webp' => @imagecreatefromwebp($chemin),
        default      => false,
    };
    if (!$im) throw new RuntimeException("Image illisible ou trop grande pour être traitée.");
    if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
        $exif = @exif_read_data($chemin);
        $rot = [3 => 180, 6 => -90, 8 => 90][$exif['Orientation'] ?? 1] ?? 0;
        if ($rot) { $r = imagerotate($im, $rot, 0); if ($r) $im = $r; }
    }
    return $im;
}

/** Pixel « vide » : blanc ou transparent. */
function image_pixel_vide(int $rgba): bool {
    $a = ($rgba >> 24) & 0x7F;
    if ($a > 90) return true;
    return (($rgba >> 16) & 255) > 238 && (($rgba >> 8) & 255) > 238 && ($rgba & 255) > 238;
}

/** Rectangle englobant le contenu (hors marges blanches/transparentes) : [x, y, w, h] en pixels de l'original. */
function image_boite_contenu($im): array {
    $w = imagesx($im); $h = imagesy($im);
    $s = max(1, (int)ceil(max($w, $h) / 300));
    $minx = $w; $miny = $h; $maxx = 0; $maxy = 0;
    // On ignore un liseré de 1,5 % sur le pourtour : les captures d'écran et exports ont souvent un trait de cadre gris
    $mx = (int)ceil($w * 0.015); $my = (int)ceil($h * 0.015);
    for ($y = $my; $y < $h - $my; $y += $s) for ($x = $mx; $x < $w - $mx; $x += $s) {
        if (!image_pixel_vide(imagecolorat($im, $x, $y))) {
            $minx = min($minx, $x); $maxx = max($maxx, $x); $miny = min($miny, $y); $maxy = max($maxy, $y);
        }
    }
    if ($maxx <= $minx || $maxy <= $miny) return [0, 0, $w, $h];
    return [$minx, $miny, min($w - $minx, $maxx - $minx + $s), min($h - $miny, $maxy - $miny + $s)];
}

/** Vrai si l'image ressemble à une étiquette/un logo : le pourtour est presque entièrement blanc ou transparent. */
function image_ressemble_logo($im): bool {
    $w = imagesx($im); $h = imagesy($im);
    $n = 0; $vides = 0; $pas = max(1, (int)floor(min($w, $h) / 60));
    for ($i = 0; $i < $w; $i += $pas) foreach ([0, $h - 1] as $y) { $n++; $vides += image_pixel_vide(imagecolorat($im, $i, $y)); }
    for ($j = 0; $j < $h; $j += $pas) foreach ([0, $w - 1] as $x) { $n++; $vides += image_pixel_vide(imagecolorat($im, $x, $j)); }
    return $n > 0 && $vides / $n > 0.9;
}

/** Fond blanc + copie redimensionnée. */
function image_toile(int $lw, int $lh) {
    $t = imagecreatetruecolor($lw, $lh);
    imagefill($t, 0, 0, imagecolorallocate($t, 255, 255, 255));
    imagealphablending($t, true);
    return $t;
}

/** Logo : marges retirées, centré sur un carré blanc. */
function image_vers_logo($im) {
    [$x, $y, $w, $h] = image_boite_contenu($im);
    $cote = (int)round(max($w, $h) * 1.06);              // 3 % de respiration autour
    $sortie = min(IMG_SORTIE_LOGO, $cote);
    $t = image_toile($sortie, $sortie);
    $k = $sortie / $cote;
    $dw = (int)round($w * $k); $dh = (int)round($h * $k);
    imagecopyresampled($t, $im, (int)(($sortie - $dw) / 2), (int)(($sortie - $dh) / 2), $x, $y, $dw, $dh, $w, $h);
    return $t;
}

/** Photo : recadrage centré (un peu au-dessus du milieu, là où sont souvent les sujets) au format 4:3. */
function image_vers_photo($im) {
    [$lw, $lh] = IMG_SORTIE_PHOTO;
    $w = imagesx($im); $h = imagesy($im);
    $cibleRatio = $lw / $lh;
    if ($w / $h > $cibleRatio) { $cw = (int)round($h * $cibleRatio); $ch = $h; $cx = (int)(($w - $cw) / 2); $cy = 0; }
    else { $cw = $w; $ch = (int)round($w / $cibleRatio); $cx = 0; $cy = (int)(($h - $ch) * 0.4); }
    $sw = min($lw, $cw); $sh = (int)round($sw / $cibleRatio);
    $t = image_toile($sw, $sh);
    imagecopyresampled($t, $im, 0, 0, $cx, $cy, $sw, $sh, $cw, $ch);
    return $t;
}

/**
 * Traite un fichier envoyé et l'écrit dans $destination (JPEG).
 * $type : 'photo' ou 'logo'. Retourne 'photo' ou 'logo' selon ce qui a été réellement produit.
 */
function image_traiter(string $source, string $destination, string $type): string {
    $im = image_charger($source);
    $logo = $type === 'logo' || image_ressemble_logo($im);
    $sortie = $logo ? image_vers_logo($im) : image_vers_photo($im);
    imageinterlace($sortie, true);
    if (!imagejpeg($sortie, $destination, 88)) throw new RuntimeException("Impossible d'enregistrer l'image.");
    return $logo ? 'logo' : 'photo';
}

/** Ratio largeur/hauteur d'un fichier envoyé (null si introuvable). */
function image_ratio(?string $fichier, string $dossier): ?float {
    if (!$fichier || $fichier !== basename($fichier)) return null;
    $i = @getimagesize(RACINE . '/uploads/' . $dossier . '/' . $fichier);
    return $i && $i[1] ? $i[0] / $i[1] : null;
}

/**
 * Couleur d'accentuation d'une étiquette (liseré rouge des fraises, jaune des mirabelles…) :
 * teinte dominante saturée, hors verts/bleus du paysage. Mémorisée dans data/couleurs.json.
 */
function couleur_accent(?string $fichier): string {
    $defaut = '#8da07a';
    if (!$fichier || $fichier !== basename($fichier)) return $defaut;
    $chemin = RACINE . '/uploads/logos/' . $fichier;
    if (!is_file($chemin)) return $defaut;
    $cache = RACINE . '/data/couleurs.json';
    $cle = $fichier . ':' . filemtime($chemin);
    $memo = is_file($cache) ? (json_decode((string)file_get_contents($cache), true) ?: []) : [];
    if (isset($memo[$cle])) return $memo[$cle];
    try {
        $im = image_charger($chemin);
        $p = imagescale($im, 80, 80);
        $casiers = [];
        for ($y = 0; $y < 80; $y++) for ($x = 0; $x < 80; $x++) {
            $c = imagecolorat($p, $x, $y);
            [$r, $g, $b] = [($c >> 16) & 255, ($c >> 8) & 255, $c & 255];
            $mx = max($r, $g, $b); $mn = min($r, $g, $b);
            if ($mx < 60 || $mx - $mn < 55) continue;                       // noir, blanc, gris, beige
            $s = ($mx - $mn) / $mx;
            if ($s < 0.45) continue;
            $d = $mx - $mn;
            $t = $mx === $r ? fmod(($g - $b) / $d, 6) : ($mx === $g ? ($b - $r) / $d + 2 : ($r - $g) / $d + 4);
            $teinte = (int)round($t * 60); if ($teinte < 0) $teinte += 360;
            if ($teinte > 70 && $teinte < 260) continue;                    // verts / bleus du décor
            $k = intdiv($teinte, 20);
            $casiers[$k] = ($casiers[$k] ?? ['n' => 0, 'r' => 0, 'g' => 0, 'b' => 0]);
            $casiers[$k]['n']++; $casiers[$k]['r'] += $r; $casiers[$k]['g'] += $g; $casiers[$k]['b'] += $b;
        }
        $couleur = $defaut;
        if ($casiers) {
            uasort($casiers, fn($a, $b) => $b['n'] <=> $a['n']);
            $c = reset($casiers);
            if ($c['n'] > 25) $couleur = sprintf('#%02x%02x%02x', intdiv($c['r'], $c['n']), intdiv($c['g'], $c['n']), intdiv($c['b'], $c['n']));
        }
    } catch (Throwable $e) { $couleur = $defaut; }
    $memo[$cle] = $couleur;
    @file_put_contents($cache, json_encode($memo), LOCK_EX);
    return $couleur;
}

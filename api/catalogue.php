<?php
// API JSON publique et en lecture seule : le catalogue visible du site.
// Utilisée par le JavaScript (rafraîchissement) et utile pour de futurs usages (appli, marchés).
require_once __DIR__ . '/../includes/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=60');

$R = reglages();
$mode = $R['prix_mode'] ?? 'individuel';
$noel = ($R['noel_actif'] ?? '0') === '1';

$mapper = function (array $l, string $dossier) use ($mode) {
    return [
        'id' => (int)$l['id'], 'nom' => $l['nom'], 'description' => $l['description'],
        'parfums' => $l['parfums'] ?? null, 'ingredients' => $l['ingredients'] ?? null, 'poids' => $l['poids'] ?? null, 'parts' => $l['parts'] ?? null,
        'image' => upload_url($l['image'], $dossier), 'logo' => upload_url($l['logo'], 'logos'),
        'prix' => ($mode === 'individuel' && $l['prix'] !== null) ? (float)$l['prix'] : null,
    ];
};

echo json_encode([
    'noel' => $noel, 'noel_message' => $noel ? $R['noel_message'] : null, 'prix_mode' => $mode,
    'glaces' => array_map(fn($l) => $mapper($l, 'glaces'), produits_visibles('glaces')),
    'buches' => $noel ? array_map(fn($l) => $mapper($l, 'buches'), produits_visibles('buches')) : [],
    'tarifs' => $mode === 'grille' ? array_map(fn($t) => ['libelle' => $t['libelle'], 'prix' => (float)$t['prix']], grille_tarifs()) : [],
], JSON_UNESCAPED_UNICODE);

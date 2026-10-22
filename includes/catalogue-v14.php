<?php
// Données issues des flyers du GAEC (v1.4.0) : tarifs particuliers et professionnels, bûches de Noël, confitures de lait.
// Injectées une seule fois en base par migrer_v14() ; ensuite tout se modifie dans l'administration.
// Rien n'est inventé : une information absente des flyers (prix de « La Pomme de Pain », ingrédients des confitures) reste vide.
return [
    // Grille tarifaire. public : particulier | pro (prix pros TTC) ; groupe : glaces | confitures
    'tarifs' => [
        ['public' => 'particulier', 'groupe' => 'glaces',     'libelle' => 'Pot 100 g (140 ml)',          'prix' => 3.00,  'ordre' => 1],
        ['public' => 'particulier', 'groupe' => 'glaces',     'libelle' => 'Pot 600 g (780 ml)',          'prix' => 9.50,  'ordre' => 2],
        ['public' => 'particulier', 'groupe' => 'glaces',     'libelle' => 'Pot 1 600 g (2,5 L)',         'prix' => 25.00, 'ordre' => 3],
        ['public' => 'particulier', 'groupe' => 'confitures', 'libelle' => 'Pot de confiture de lait',    'prix' => 5.00,  'ordre' => 4],
        ['public' => 'pro',         'groupe' => 'glaces',     'libelle' => 'Pot 100 g / 140 ml (2 boules)', 'prix' => 2.30,  'ordre' => 5],
        ['public' => 'pro',         'groupe' => 'glaces',     'libelle' => 'Pot 600 g / 780 ml',          'prix' => 8.00,  'ordre' => 6],
        ['public' => 'pro',         'groupe' => 'glaces',     'libelle' => 'Bac 1 600 g / 2,5 L (pour bouler)', 'prix' => 22.00, 'ordre' => 7],
        ['public' => 'pro',         'groupe' => 'confitures', 'libelle' => 'Pot de confiture de lait',    'prix' => 4.00,  'ordre' => 8],
    ],
    // Ancien tarif d'exemple posé par schema.sql : retiré s'il n'a pas été modifié
    'tarifs_exemple' => [['Pot 100 ml', 3.50], ['Pot 500 ml', 9.00], ['Pot 1 L', 16.00]],
    'prix_pot_glace' => 3.00,

    'buches' => [
        ['nom' => 'La Fruitée', 'ordre' => 1,
         'parfums' => 'Vanille · Crème de châtaigne · Mirabelle',
         'description' => 'Crème glacée vanille, insert crème de châtaigne, glace mirabelle, biscuit joconde, décor marron glacé.',
         'parts' => '6 pers. : 18 € · 10 pers. : 28 €'],
        ['nom' => 'La Tout Choco', 'ordre' => 2,
         'parfums' => 'Chocolat · Cerise',
         'description' => 'Glace chocolat, insert glace cerise et coulis cerise, brownies, décor meringue et cannelé, cerise confite.',
         'parts' => '6 pers. : 18 € · 10 pers. : 28 €'],
        ['nom' => 'La Pomme de Pain', 'ordre' => 3,
         'parfums' => 'Pomme · Pain d\'épices · Orange',
         'description' => 'Glace pomme, insert glace pain d\'épices et écorces d\'orange, biscuit moelleux spéculoos, décor orangettes et pain d\'épices, pomme et clémentine.',
         'parts' => '6 et 10 personnes — tarif à confirmer par téléphone'],
    ],

    // accent : teinte de la carte (pas de photo/logo pour l'instant)
    'confitures' => [
        ['nom' => 'Confiture de lait nature',   'ordre' => 1, 'accent' => '#d9a441'],
        ['nom' => 'Confiture de lait vanille',  'ordre' => 2, 'accent' => '#e3b94d'],
        ['nom' => 'Confiture de lait noisette', 'ordre' => 3, 'accent' => '#a8743c'],
        ['nom' => 'Confiture de lait chocolat', 'ordre' => 4, 'accent' => '#6b3f2a'],
    ],
    'confiture_description' => 'Confiture de lait de nos chèvres, préparée à la ferme.',
    'prix_confiture' => 5.00,

    'reglages' => [
        'telephone_2'    => '06 33 69 62 68',
        'glaces_bientot' => 'reine des prés, citron',
        'noel_infos'     => 'Réservations par téléphone. Retrait à la boutique de la ferme tous les jours (merci d\'appeler avant), ou livraison la semaine avant Noël si possible.',
    ],
];

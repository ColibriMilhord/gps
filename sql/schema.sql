-- ============================================================
--  GAEC du Point Sublime — « Les Sublimes » (glaces au lait de chèvre)
--  Schéma MySQL / MariaDB.  À importer une seule fois (phpMyAdmin > Importer).
--  Tables : admin, glaces, buches, tarifs, settings_site
-- ============================================================

SET NAMES utf8mb4;

-- Comptes de l'espace d'administration (mots de passe hachés avec password_hash)
CREATE TABLE IF NOT EXISTS admin (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    identifiant   VARCHAR(60)  NOT NULL UNIQUE,
    mot_de_passe  VARCHAR(255) NOT NULL,
    cree_le       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    derniere_connexion DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tentatives de connexion échouées (limitation anti force brute)
CREATE TABLE IF NOT EXISTS tentatives_connexion (
    id        INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    ip        VARCHAR(45) NOT NULL,
    date_essai TIMESTAMP  NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_ip_date (ip, date_essai)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Parfums de glaces.
--   image : photo du parfum (carte de la vitrine)
--   logo  : logo/étiquette propre à ce parfum (ex. « Crème glacée Miel »)
CREATE TABLE IF NOT EXISTS glaces (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nom         VARCHAR(120) NOT NULL,
    description TEXT NULL,
    image       VARCHAR(255) NULL,
    logo        VARCHAR(255) NULL,
    ingredients TEXT NULL,
    poids       VARCHAR(60) NULL,
    prix        DECIMAL(6,2) NULL,
    visible     TINYINT(1) NOT NULL DEFAULT 1,
    ordre       INT NOT NULL DEFAULT 0,
    cree_le     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bûches de Noël sur commande (même logique que les glaces)
CREATE TABLE IF NOT EXISTS buches (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nom         VARCHAR(120) NOT NULL,
    description TEXT NULL,
    parfums     VARCHAR(255) NULL,
    parts       VARCHAR(60)  NULL,
    image       VARCHAR(255) NULL,
    logo        VARCHAR(255) NULL,
    prix        DECIMAL(6,2) NULL,
    visible     TINYINT(1) NOT NULL DEFAULT 1,
    ordre       INT NOT NULL DEFAULT 0,
    cree_le     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Grille tarifaire générale (ex. « Pot 100 ml — 3,50 € »)
CREATE TABLE IF NOT EXISTS tarifs (
    id      INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    libelle VARCHAR(120) NOT NULL,
    prix    DECIMAL(6,2) NOT NULL,
    ordre   INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Paramètres du site (clé / valeur)
CREATE TABLE IF NOT EXISTS settings_site (
    cle    VARCHAR(60) NOT NULL PRIMARY KEY,
    valeur TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO settings_site (cle, valeur) VALUES
    ('noel_actif',    '0'),
    ('noel_message',  'Commandez vos bûches glacées au lait de chèvre pour vos repas de fêtes.'),
    ('prix_mode',     'individuel'),   -- individuel | grille | aucun
    ('telephone',     ''),
    ('email',         ''),
    ('adresse',       'Ferme du Tensonnieu, aux portes des Gorges du Tarn'),
    ('marches',       'Marchés d''Aveyron et de Lozère');

-- Les parfums (ingrédients, logos) sont injectés automatiquement au premier affichage : voir includes/catalogue-initial.php
INSERT INTO tarifs (libelle, prix, ordre) VALUES
    ('Pot 100 ml', 3.50, 1), ('Pot 500 ml', 9.00, 2), ('Pot 1 L', 16.00, 3);

-- ------------------------------------------------------------
--  v1.1.0 — « Où nous retrouver », revendeurs, formulaire de contact
-- ------------------------------------------------------------

-- Rendez-vous : à la ferme, marchés, événements.
--   Dates vides = rendez-vous permanent (ex. « Tous les samedis matin »).
CREATE TABLE IF NOT EXISTS lieux (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    type       VARCHAR(20)  NOT NULL DEFAULT 'marche',   -- ferme | marche | evenement
    titre      VARCHAR(140) NOT NULL,
    ville      VARCHAR(100) NULL,
    adresse    VARCHAR(255) NULL,
    date_debut DATE NULL,
    date_fin   DATE NULL,
    recurrence VARCHAR(140) NULL,
    horaires   VARCHAR(140) NULL,
    note       TEXT NULL,
    visible    TINYINT(1) NOT NULL DEFAULT 1,
    ordre      INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Partenaires qui vendent nos glaces (boutiques, restaurants, épiceries…)
CREATE TABLE IF NOT EXISTS partenaires (
    id        INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nom       VARCHAR(140) NOT NULL,
    categorie VARCHAR(30)  NOT NULL DEFAULT 'boutique',  -- boutique | restaurant | epicerie | autre
    ville     VARCHAR(100) NULL,
    adresse   VARCHAR(255) NULL,
    telephone VARCHAR(40)  NULL,
    site_web  VARCHAR(255) NULL,
    visible   TINYINT(1) NOT NULL DEFAULT 1,
    ordre     INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Messages reçus par le formulaire de contact
CREATE TABLE IF NOT EXISTS messages (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    profil        VARCHAR(30)  NOT NULL,
    nom           VARCHAR(120) NOT NULL,
    email         VARCHAR(190) NOT NULL,
    telephone     VARCHAR(40)  NULL,
    etablissement VARCHAR(140) NULL,
    produit       VARCHAR(190) NULL,
    date_souhaitee VARCHAR(60) NULL,
    message       TEXT NOT NULL,
    ip            VARCHAR(45)  NULL,
    lu            TINYINT(1) NOT NULL DEFAULT 0,
    cree_le       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_ip_date (ip, cree_le)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

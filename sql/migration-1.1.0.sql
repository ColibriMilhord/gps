SET NAMES utf8mb4;

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

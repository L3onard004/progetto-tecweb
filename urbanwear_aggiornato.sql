-- =====================================================
-- DATABASE UrbanWear (schema + dati demo + immagini)
-- Aggiornato: target separati in bambino e bambina
-- =====================================================

CREATE DATABASE IF NOT EXISTS urbanwear
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE urbanwear;

-- =====================================================
-- DROP (per re-import pulito)
-- =====================================================
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS immagini_prodotti;
DROP TABLE IF EXISTS ordine_prodotti;
DROP TABLE IF EXISTS ordini;
DROP TABLE IF EXISTS carrello_prodotti;
DROP TABLE IF EXISTS carrelli;
DROP TABLE IF EXISTS prodotti;
DROP TABLE IF EXISTS categorie;
DROP TABLE IF EXISTS utenti;
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- TABELLA UTENTI
-- =====================================================
CREATE TABLE utenti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL,
    cognome VARCHAR(50) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    ruolo ENUM('admin', 'user') NOT NULL DEFAULT 'user'
) ENGINE=InnoDB;

-- =====================================================
-- TABELLA CATEGORIE
-- =====================================================
CREATE TABLE categorie (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- =====================================================
-- TABELLA PRODOTTI
-- =====================================================
CREATE TABLE prodotti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descrizione TEXT NOT NULL,
    materiali TEXT NOT NULL DEFAULT '',
    guida_taglie TEXT NOT NULL DEFAULT '',
    cura TEXT NOT NULL DEFAULT '',
    prezzo DECIMAL(8,2) NOT NULL,
    id_categoria INT NOT NULL,
    target ENUM('uomo', 'donna', 'bambino', 'bambina') NOT NULL,
    attivo TINYINT(1) NOT NULL DEFAULT 1,

    FOREIGN KEY (id_categoria) REFERENCES categorie(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- TABELLA IMMAGINI PRODOTTI (4 foto + alt)
-- =====================================================
CREATE TABLE immagini_prodotti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_prodotto INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    alt VARCHAR(255) NOT NULL,
    ordine TINYINT UNSIGNED NOT NULL DEFAULT 1,
    is_principale TINYINT(1) NOT NULL DEFAULT 0,

    UNIQUE KEY uq_img_ordine (id_prodotto, ordine),
    KEY idx_img_prodotto (id_prodotto),

    FOREIGN KEY (id_prodotto) REFERENCES prodotti(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- TABELLA CARRELLI
-- =====================================================
CREATE TABLE carrelli (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_utente INT NOT NULL,

    FOREIGN KEY (id_utente) REFERENCES utenti(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- RELAZIONE CARRELLO - PRODOTTI
-- =====================================================
CREATE TABLE carrello_prodotti (
    id_carrello INT NOT NULL,
    id_prodotto INT NOT NULL,
    quantita INT NOT NULL DEFAULT 1,

    PRIMARY KEY (id_carrello, id_prodotto),

    FOREIGN KEY (id_carrello) REFERENCES carrelli(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    FOREIGN KEY (id_prodotto) REFERENCES prodotti(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- TABELLA ORDINI
-- =====================================================
CREATE TABLE ordini (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_utente INT NOT NULL,
    data DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    note TEXT,

    FOREIGN KEY (id_utente) REFERENCES utenti(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- RELAZIONE ORDINI - PRODOTTI
-- =====================================================
CREATE TABLE ordine_prodotti (
    id_ordine INT NOT NULL,
    id_prodotto INT NOT NULL,
    quantita INT NOT NULL,
    prezzo_unitario DECIMAL(8,2) NOT NULL,

    PRIMARY KEY (id_ordine, id_prodotto),

    FOREIGN KEY (id_ordine) REFERENCES ordini(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    FOREIGN KEY (id_prodotto) REFERENCES prodotti(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- DATI DEMO: utenti
-- =====================================================
INSERT INTO utenti (nome, cognome, username, password, email, ruolo) VALUES
    ('Amministratore',    'Urbanwear', 'admin', '$2y$10$dmSosKUFONoDCNYlSy2E2e.9qQ9sqhUm82uGqKDCX0jlIJTo.6hV2', 'admin@urbanwear.it',    'admin'),
    ('Utente',   'Urbanwear',      'user',  '$2y$10$tLWfKCj5wdoNoejDyy6uAecvo8JSu.Km/BQeY8lwR2kZnPYNA.7.2', 'user@urbanwear.it',     'user');

-- =====================================================
-- DATI DEMO: categorie + prodotti + immagini
-- =====================================================
INSERT INTO categorie (id, nome) VALUES
    (1, 'Accessori'),
    (2, 'Pantaloni'),
    (3, 'Felpe'),
    (4, 'Magliette'),
    (5, 'Giacche'),
    (6, 'Scarpe'),
    (7, 'Gonne'),
    (8, 'Maglioni');

INSERT INTO prodotti (id, nome, descrizione, materiali, guida_taglie, cura, prezzo, id_categoria, target, attivo) VALUES
    (1,  'Berretto Invernale', 'Berretto caldo e morbido per bambino, ideale per le giornate fredde e per uno stile urbano confortevole.',
         '100% acrilico morbido\nFodera interna in pile',
         'S: circonferenza testa 52–54 cm\nM: circonferenza testa 54–57 cm\nL: circonferenza testa 57–60 cm',
         'Lavaggio a mano a 30°C\nNon centrifugare\nAsciugare in piano',
         14.90, 1, 'bambino', 1),

    (2,  'Felpa Essenziale', 'Felpa da donna con uno stile essenziale, moderno e versatile, adatta per essere indossata tutti i giorni.',
         '80% cotone\n20% poliestere riciclato',
         'S: busto 82–86 cm\nM: busto 86–90 cm\nL: busto 90–94 cm',
         'Lavaggio in lavatrice a 30°C\nNon utilizzare candeggina\nStirare a temperatura media',
         34.90, 3, 'donna', 1),

    (3,  'Maglietta Semplice', 'Maglietta da donna leggera e traspirante, perfetta come base per uno stile informale in ogni stagione.',
         '100% cotone bio certificato GOTS',
         'S: busto 82–86 cm\nM: busto 86–90 cm\nL: busto 90–94 cm',
         'Lavaggio a 40°C\nNon utilizzare candeggina\nStirare a temperatura media',
         19.90, 4, 'donna', 1),

    (4,  'Giacca Leggera', 'Giacca da uomo pratica e leggera, adatta alle mezze stagioni e a uno stile urbano pulito.',
         'Esterno: 100% poliestere ripstop\nFodera: 100% nylon',
         'S: busto 92–96 cm\nM: busto 96–100 cm\nL: busto 100–104 cm',
         'Lavaggio a 30°C\nNon centrifugare\nNon utilizzare candeggina',
         59.90, 5, 'uomo', 1),

    (5,  'Scarpe', 'Scarpe da uomo dallo stile urbano, ideali per tutti i giorni.',
         'Tomaia: pelle pieno fiore\nFodera: pelle naturale\nSuola: gomma',
         '40: lunghezza piede 25,5 cm\n41: lunghezza piede 26 cm\n42: lunghezza piede 26,5 cm\n43: lunghezza piede 27 cm',
         'Pulire con panno asciutto\nCondizionare con crema per pelle\nConservare con forma interna',
         79.90, 6, 'uomo', 1),

    (6,  'Maglietta Urbana', 'Maglietta per bambino fresca e comoda, ideale per l''uso quotidiano e per il tempo libero.',
         '100% cotone jersey',
         'S: busto 64–68 cm\nM: busto 68–72 cm\nL: busto 72–76 cm',
         'Lavaggio a 40°C\nNon utilizzare candeggina\nStirare a bassa temperatura',
         18.90, 4, 'bambino', 1),

    (7,  'Pantalone', 'Pantalone per bambino dalla vestibilità comoda, adatto a scuola, gioco e attività di tutti i giorni.',
         '98% cotone\n2% elastan',
         'S: vita 50–54 cm\nM: vita 54–58 cm\nL: vita 58–62 cm',
         'Lavaggio a 30°C\nNon utilizzare candeggina\nStirare a temperatura media',
         26.90, 2, 'bambino', 1),

    (8,  'Gonna', 'Gonna per bambina dallo stile semplice e versatile, adatta a un abbigliamento ricercato ma pratico.',
         '100% cotone leggero',
         'S: vita 46–50 cm\nM: vita 50–54 cm\nL: vita 54–58 cm',
         'Lavaggio a 30°C\nNon utilizzare candeggina\nStirare a bassa temperatura',
         22.90, 7, 'bambina', 1),

    (9,  'Maglietta con Stampe', 'Maglietta per bambina morbida e leggera, perfetta per uno stile fresco e quotidiano.',
         '100% cotone jersey morbido',
         'S: busto 60–64 cm\nM: busto 64–68 cm\nL: busto 68–72 cm',
         'Lavaggio a 40°C\nNon utilizzare candeggina\nStirare a bassa temperatura',
         17.90, 4, 'bambina', 1),

    (10, 'Pantaloni', 'Pantaloni da donna dal taglio contemporaneo, pensati per uno stile urbano comodo e ricercato.',
         '97% cotone\n3% elastan',
         'S: vita 62–66 cm\nM: vita 66–70 cm\nL: vita 70–74 cm',
         'Lavaggio a 30°C\nNon utilizzare candeggina\nStirare a temperatura media',
         39.90, 2, 'donna', 1),

    (11, 'Maglione', 'Maglione da uomo caldo, semplice e versatile, perfetto per i mesi più freddi ed è ideale da indossare tutti i giorni.',
         '80% lana merino\n20% nylon',
         'S: busto 92–96 cm\nM: busto 96–100 cm\nL: busto 100–104 cm',
         'Lavaggio a mano a 30°C\nAsciugare in piano\nNon utilizzare candeggina',
         44.90, 8, 'uomo', 1),

    (12, 'Sandali', 'Sandali per bambina dallo stile urbano con logo, comodi e resistenti per il tempo libero e le giornate all''aria aperta.',
         'Tomaia: pelle sintetica\nFodera: materiale traspirante\nSuola: gomma antiscivolo',
         '28: lunghezza piede 17,5 cm\n29: lunghezza piede 18 cm\n30: lunghezza piede 18,5 cm\n31: lunghezza piede 19 cm\n32: lunghezza piede 19,5 cm',
         'Pulire con panno umido\nNon immergere in acqua\nAsciugare all''aria',
         29.90, 6, 'bambina', 1);

INSERT INTO immagini_prodotti (id_prodotto, file_path, alt, ordine, is_principale) VALUES
    -- 1) Berretto Invernale (bambino)
    (1,  'immagini/bambino/berretto invernale/berretto.webp', 'Berretto invernale per bambino, foto prodotto su sfondo neutro', 1, 1),
    (1,  'immagini/bambino/berretto invernale/fronte.webp',   'Berretto invernale per bambino, vista frontale indossato', 2, 0),
    (1,  'immagini/bambino/berretto invernale/gioco.webp',    'Berretto invernale per bambino, indossato durante un momento di gioco', 3, 0),
    (1,  'immagini/bambino/berretto invernale/retro.webp',    'Berretto invernale per bambino, vista posteriore indossato', 4, 0),

    -- 2) Felpa Essenziale (donna)
    (2,  'immagini/donna/felpa minimal/felpa.webp',   'Felpa essenziale da donna, foto prodotto su sfondo neutro', 1, 1),
    (2,  'immagini/donna/felpa minimal/fronte.webp',  'Felpa essenziale da donna, vista frontale indossata', 2, 0),
    (2,  'immagini/donna/felpa minimal/muretto.webp', 'Felpa essenziale da donna, indossata in ambiente urbano vicino a un muretto', 3, 0),
    (2,  'immagini/donna/felpa minimal/spalle.webp',  'Felpa essenziale da donna, vista posteriore indossata', 4, 0),

    -- 3) Maglietta Semplice (donna)
    (3,  'immagini/donna/maglietta basic/maglietta.webp', 'Maglietta semplice da donna, foto prodotto su sfondo neutro', 1, 1),
    (3,  'immagini/donna/maglietta basic/fronte.webp',    'Maglietta semplice da donna, vista frontale indossata', 2, 0),
    (3,  'immagini/donna/maglietta basic/scalinata.webp', 'Maglietta semplice da donna, indossata su una scalinata in ambiente urbano', 3, 0),
    (3,  'immagini/donna/maglietta basic/spalle.webp',    'Maglietta semplice da donna, vista posteriore indossata', 4, 0),

    -- 4) Giacca Leggera (uomo)
    (4,  'immagini/uomo/Giacca leggera/giacca.webp',  'Giacca leggera da uomo, foto prodotto su sfondo neutro', 1, 1),
    (4,  'immagini/uomo/Giacca leggera/fronte.webp',  'Giacca leggera da uomo, vista frontale indossata', 2, 0),
    (4,  'immagini/uomo/Giacca leggera/seduto.webp',  'Giacca leggera da uomo, indossata mentre si è seduti in ambiente urbano', 3, 0),
    (4,  'immagini/uomo/Giacca leggera/spalle.webp',  'Giacca leggera da uomo, vista posteriore indossata', 4, 0),

    -- 5) Scarpe (uomo)
    (5,  'immagini/uomo/scarpe/scarpe.webp',     'Scarpe da uomo, foto prodotto su sfondo neutro', 1, 1),
    (5,  'immagini/uomo/scarpe/fronte.webp',     'Scarpe da uomo, vista frontale indossate', 2, 0),
    (5,  'immagini/uomo/scarpe/ristorante.webp', 'Scarpe da uomo, indossate in contesto serale al ristorante', 3, 0),
    (5,  'immagini/uomo/scarpe/spalle.webp',     'Scarpe da uomo, vista posteriore indossate', 4, 0),

    -- 6) Maglietta Urbana (bambino)
    (6,  'immagini/bambino/T-shirt/tshirt.webp', 'Maglietta urbana per bambino, foto prodotto su sfondo neutro', 1, 1),
    (6,  'immagini/bambino/T-shirt/fronte.webp', 'Maglietta urbana per bambino, vista frontale indossata', 2, 0),
    (6,  'immagini/bambino/T-shirt/gioco.webp',  'Maglietta urbana per bambino, indossata durante un momento di gioco', 3, 0),
    (6,  'immagini/bambino/T-shirt/spalle.webp', 'Maglietta urbana per bambino, vista posteriore indossata', 4, 0),

    -- 7) Pantalone (bambino)
    (7,  'immagini/bambino/pantalone/pantalone.webp', 'Pantalone per bambino, foto prodotto su sfondo neutro', 1, 1),
    (7,  'immagini/bambino/pantalone/fronte.webp',    'Pantalone per bambino, vista frontale indossato', 2, 0),
    (7,  'immagini/bambino/pantalone/gioco.webp',     'Pantalone per bambino, indossato durante un momento di gioco', 3, 0),
    (7,  'immagini/bambino/pantalone/retro.webp',     'Pantalone per bambino, vista posteriore indossato', 4, 0),

    -- 8) Gonna (bambina)
    (8,  'immagini/bambina/gonna/gonna.webp',  'Gonna per bambina, foto prodotto su sfondo neutro', 1, 1),
    (8,  'immagini/bambina/gonna/fronte.webp', 'Gonna per bambina, vista frontale indossata', 2, 0),
    (8,  'immagini/bambina/gonna/gioco.webp',  'Gonna per bambina, indossata durante un momento di gioco', 3, 0),
    (8,  'immagini/bambina/gonna/retro.webp',  'Gonna per bambina, vista posteriore indossata', 4, 0),

    -- 9) Maglietta (bambina)
    (9,  'immagini/bambina/maglietta/maglietta.webp', 'Maglietta per bambina, foto prodotto su sfondo neutro', 1, 1),
    (9,  'immagini/bambina/maglietta/fronte.webp',    'Maglietta per bambina, vista frontale indossata', 2, 0),
    (9,  'immagini/bambina/maglietta/gioco.webp',     'Maglietta per bambina, indossata durante un momento di gioco', 3, 0),
    (9,  'immagini/bambina/maglietta/retro.webp',     'Maglietta per bambina, vista posteriore indossata', 4, 0),

    -- 10) Pantaloni (donna)
    (10, 'immagini/donna/pantaloni/pantaloni.webp', 'Pantaloni da donna, foto prodotto su sfondo neutro', 1, 1),
    (10, 'immagini/donna/pantaloni/fronte.webp',    'Pantaloni da donna, vista frontale indossati', 2, 0),
    (10, 'immagini/donna/pantaloni/writing.webp',   'Pantaloni da donna, indossati in ambiente urbano su scalinata', 3, 0),
    (10, 'immagini/donna/pantaloni/spalle.webp',    'Pantaloni da donna, vista posteriore indossati', 4, 0),

    -- 11) Maglione (uomo)
    (11, 'immagini/uomo/maglione/maglione.webp', 'Maglione da uomo, foto prodotto su sfondo neutro', 1, 1),
    (11, 'immagini/uomo/maglione/fronte.webp',   'Maglione da uomo, vista frontale indossato', 2, 0),
    (11, 'immagini/uomo/maglione/skate.webp',    'Maglione da uomo, indossato in ambiente urbano su skateboard', 3, 0),
    (11, 'immagini/uomo/maglione/spalle.webp',   'Maglione da uomo, vista posteriore indossato', 4, 0),

    -- 12) Sandali (bambina)
    (12, 'immagini/bambina/sandali/sandali.webp', 'Sandali per bambina, vista dall''alto su sfondo neutro', 1, 1),
    (12, 'immagini/bambina/sandali/fronte.webp',  'Sandali per bambina, indossati vista frontale', 2, 0),
    (12, 'immagini/bambina/sandali/gioco.webp',   'Sandali per bambina, indossati durante un momento di gioco', 3, 0),
    (12, 'immagini/bambina/sandali/retro.webp',   'Sandali per bambina, indossati vista posteriore', 4, 0);

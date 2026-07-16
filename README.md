# UrbanWear — Negozio di abbigliamento online

Progetto per il corso di **Tecnologie Web** — Università degli Studi di Padova, A.A. 2025/2026.

UrbanWear è un negozio di abbigliamento online che permette agli utenti di sfogliare un catalogo di prodotti, gestire il carrello e completare acquisti. Un pannello di amministrazione consente di gestire il catalogo prodotti.

## Tecnologie utilizzate

- **HTML5** — markup semantico e accessibile
- **CSS3** — layout responsive con Flexbox e CSS Grid
- **JavaScript** — progressive enhancement (funziona anche senza JS)
- **PHP** — backend con pattern a template
- **MySQL / MariaDB** — database relazionale

## Requisiti

- [XAMPP](https://www.apachefriends.org/) (o equivalente con Apache + PHP + MySQL)
- PHP 8.0+
- Browser moderno

## Installazione in locale

1. Clona o scarica il repository nella cartella `htdocs` di XAMPP:
   ```
   C:\xampp\htdocs\ProgettoTechWeb\
   ```

2. Importa il database in phpMyAdmin:
   - Apri `http://localhost/phpmyadmin`
   - Crea un nuovo database oppure importa direttamente il file
   - Importa `urbanwear_aggiornato.sql`

3. Verifica la configurazione in `config.php`:
   ```php
   $host = 'localhost';
   $db   = 'urbanwear';
   $user = 'root';
   $pass = '';
   ```

4. Avvia Apache e MySQL da XAMPP e apri:
   ```
   http://localhost/ProgettoTechWeb/
   ```

## Credenziali di accesso

| Ruolo         | Username | Password |
|---------------|----------|----------|
| Amministratore | `admin`  | `admin`  |
| Utente demo   | `user`   | `user`   |

## Struttura del progetto

```
ProgettoTechWeb/
├── index.html               # Home
├── prodotti.php             # Catalogo prodotti
├── prodotto.php             # Dettaglio prodotto
├── carrello.php             # Gestione carrello
├── checkout.php             # Checkout
├── conferma_ordine.php      # Conferma ordine
├── ordini.php               # Storico ordini
├── login.php                # Login / area personale
├── registrazione.html/.php  # Registrazione
├── modifica_password.php    # Modifica password
├── logout.php               # Logout
├── gestione_prodotti.php    # Pannello amministratore
├── dati_carrello.php        # API JSON carrello
├── config.php               # Configurazione DB
├── chi_siamo.html           # Chi siamo
├── privacy.html             # Privacy Policy
├── termini.html             # Termini di servizio
├── reso.html                # Politica di reso
├── 403.php / 404.php / 500.php
├── Template/                # Template HTML per le pagine dinamiche
├── Style/                   # Fogli di stile
│   ├── style.css            # Stile principale (desktop)
│   ├── mobile.css           # Responsive
│   └── stampa.css           # Stampa
├── Script/                  # File JavaScript
├── immagini/                # Immagini del sito e dei prodotti
└── urbanwear_aggiornato.sql # Schema e dati del database
```

## Funzionalità principali

- Catalogo filtrabile per target (Uomo, Donna, Bambino, Bambina) con ricerca per parola chiave
- Pagina prodotto con galleria fotografica e accordion informativo
- Carrello persistente per utenti registrati
- Checkout e storico ordini
- Pannello admin per gestione completa del catalogo
- Accessibilità: skip link, ARIA, navigazione da tastiera, compatibile con screen reader
- Supporto stampa con layout dedicato

## Accessibilità e validazione

Il sito è stato validato con W3C Validator e TotalValidator. È compatibile con NVDA (Windows) e VoiceOver (macOS/iOS).

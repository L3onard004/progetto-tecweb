<?php
declare(strict_types=1);

session_start();
require_once 'config.php'; 

// Funzione di supporto
function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// CONFIGURAZIONE LOGIN
$MAX_ATTEMPTS = 5;
$COOLDOWN_SEC = 15;

$_SESSION['login_fail_count'] ??= 0;
$_SESSION['login_cooldown_until'] ??= 0;

// Esecuzione login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $now = time();
    if ($now < (int)$_SESSION['login_cooldown_until']) {
        $_SESSION['login_error'] = 'Troppi tentativi. Riprova tra qualche secondo.';
        $_SESSION['last_login_username'] = trim($_POST['username'] ?? '');
        header('Location: login.php');
        exit;
    }

    $username = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $_SESSION['login_error'] = 'Inserisci username e password.';
        $_SESSION['last_login_username'] = $username;
        header('Location: login.php');
        exit;
    }

    $stmt = $conn->prepare(
        "SELECT id, username, password, nome, ruolo
         FROM utenti
         WHERE username = ?"
    );

    if (!$stmt) {
        $_SESSION['login_error'] = 'Errore interno. Riprova.';
        $_SESSION['last_login_username'] = $username;
        header('Location: login.php');
        exit;
    }

    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $utente = $result->fetch_assoc();

        if (password_verify($password, (string)$utente['password'])) {

            session_regenerate_id(true);

            $_SESSION['user_id']  = (int)$utente['id'];
            $_SESSION['username'] = (string)$utente['username'];
            $_SESSION['nome']     = (string)$utente['nome'];
            $_SESSION['ruolo']    = (string)($utente['ruolo'] ?? 'user');

            $_SESSION['login_fail_count'] = 0;
            $_SESSION['login_cooldown_until'] = 0;
            unset($_SESSION['login_error'], $_SESSION['last_login_username']);

            // Recupero o creazione del carrello
            if (empty($_SESSION['id_carrello'])) {

                $stmt = $conn->prepare("
                    SELECT id
                    FROM carrelli
                    WHERE id_utente = ?
                    LIMIT 1
                ");
                $stmt->bind_param("i", $_SESSION['user_id']);
                $stmt->execute();
                $res = $stmt->get_result();
                $stmt->close();

                if ($row = $res->fetch_assoc()) {
                    $_SESSION['id_carrello'] = (int)$row['id'];
                } else {
                    $stmt = $conn->prepare("
                        INSERT INTO carrelli (id_utente)
                        VALUES (?)
                    ");
                    $stmt->bind_param("i", $_SESSION['user_id']);
                    $stmt->execute();
                    $_SESSION['id_carrello'] = $stmt->insert_id;
                    $stmt->close();
                }
            }

            // Nel DB: carrello utente non loggato + carrello utente loggato  
            if (!empty($_SESSION['guest_cart']) && is_array($_SESSION['guest_cart'])) {

                foreach ($_SESSION['guest_cart'] as $idProdotto => $qty) {
                    $idProdotto = (int)$idProdotto;
                    $qty = max(1, (int)$qty);

                    $stmt = $conn->prepare("
                        INSERT INTO carrello_prodotti (id_carrello, id_prodotto, quantita)
                        VALUES (?, ?, ?)
                        ON DUPLICATE KEY UPDATE quantita = quantita + ?
                    ");
                    $stmt->bind_param(
                        "iiii",
                        $_SESSION['id_carrello'],
                        $idProdotto,
                        $qty,
                        $qty
                    );
                    $stmt->execute();
                    $stmt->close();
                }

                unset($_SESSION['guest_cart']);
            }

            // Redirect dopo il login
            if (!empty($_SESSION['redirect_after_login']) && is_string($_SESSION['redirect_after_login'])) {
                $dest = $_SESSION['redirect_after_login'];
                unset($_SESSION['redirect_after_login']);

                $parsed = parse_url($dest);
                if ($dest !== '' && str_starts_with($dest, '/') && empty($parsed['host'])) {
                    header('Location: ' . $dest);
                    exit;
                }
            }

            header('Location: index.html');
            exit;
        }
    }

    // Login fallito
    $_SESSION['login_fail_count']++;
    if ($_SESSION['login_fail_count'] >= $MAX_ATTEMPTS) {
        $_SESSION['login_cooldown_until'] = time() + $COOLDOWN_SEC;
    }

    $_SESSION['login_error'] = 'Credenziali non valide.';
    $_SESSION['last_login_username'] = $username;
    header('Location: login.php');
    exit;
}

// CARICAMENTO TEMPLETE
$template = file_get_contents('Template/login.html');
if ($template === false) {
    http_response_code(500);
    header('Location: 500.php');
    exit;
}

$isLogged = isset($_SESSION['user_id']);
$displayName = (string)($_SESSION['nome'] ?? 'Utente');

$err = (string)($_SESSION['login_error'] ?? '');
$hasErr = ($err !== '');

$repl = [
    '[LOGIN_USERNAME]' => e((string)($_SESSION['last_login_username'] ?? '')),
    '[LOGIN_ERR]'      => e($err),
    '[SKIP_TARGET]'    => $isLogged ? '#benvenuto' : '#login-form',
    '[DISPLAY_NAME]'   => $isLogged ? e($displayName) : '',
];

unset($_SESSION['login_error'], $_SESSION['last_login_username']);

$template = strtr($template, $repl);

if ($isLogged) {
    $template = str_replace('id="login-form"', 'id="login-form" hidden', $template);
}
if (!$hasErr) {
    $template = str_replace('id="login-errors"', 'id="login-errors" hidden', $template);
}
if ($hasErr) {
    $template = str_replace('id="username"', 'id="username" aria-describedby="login-errors"', $template);
    $template = str_replace('id="password"', 'id="password" aria-describedby="login-errors"', $template);
}
if (!$isLogged) {
    $template = str_replace('id="benvenuto"', 'id="benvenuto" hidden', $template);
}
$isAdmin = $isLogged && isset($_SESSION['ruolo']) && $_SESSION['ruolo'] === 'admin';
if (!$isAdmin) {
    $template = str_replace('id="admin-nav-link"', 'id="admin-nav-link" hidden', $template);
}
if (!$isLogged) {
    $template = str_replace('aria-labelledby="titolo-supporto"', 'aria-labelledby="titolo-supporto" hidden', $template);
}

echo $template;

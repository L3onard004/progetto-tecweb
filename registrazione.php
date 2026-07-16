<?php
declare(strict_types=1);

session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome     = trim($_POST['nome'] ?? '');
    $cognome  = trim($_POST['cognome'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    // Validazione server-side
    if ($nome === '' || $cognome === '' || $username === '' || $email === '' || $password === '') {
        header('Location: registrazione.html?errore=campi_vuoti');
        exit;
    }

    if (mb_strlen($nome, 'UTF-8') < 2) {
        header('Location: registrazione.html?errore=nome_corto');
        exit;
    }

    if (mb_strlen($cognome, 'UTF-8') < 2) {
        header('Location: registrazione.html?errore=cognome_corto');
        exit;
    }

    if (!preg_match('/^[a-zA-Z0-9._-]{3,20}$/', $username)) {
        header('Location: registrazione.html?errore=username_invalido');
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header('Location: registrazione.html?errore=email_invalida');
        exit;
    }

    if (mb_strlen($password, 'UTF-8') < 8) {
        header('Location: registrazione.html?errore=password_corta');
        exit;
    }

    $stmtChk = $conn->prepare("SELECT id FROM utenti WHERE username = ? LIMIT 1");
    $stmtChk->bind_param('s', $username);
    $stmtChk->execute();
    $stmtChk->store_result();
    if ($stmtChk->num_rows > 0) {
        $stmtChk->close();
        header('Location: registrazione.html?errore=username_duplicato');
        exit;
    }
    $stmtChk->close();

    $stmtChk = $conn->prepare("SELECT id FROM utenti WHERE email = ? LIMIT 1");
    $stmtChk->bind_param('s', $email);
    $stmtChk->execute();
    $stmtChk->store_result();
    if ($stmtChk->num_rows > 0) {
        $stmtChk->close();
        header('Location: registrazione.html?errore=email_duplicata');
        exit;
    }
    $stmtChk->close();

    // Hash password
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare(
        "INSERT INTO utenti (nome, cognome, username, email, password)
         VALUES (?, ?, ?, ?, ?)"
    );

    if (!$stmt) {
        http_response_code(500);
        exit('Errore interno.');
    }

    $stmt->bind_param('sssss', $nome, $cognome, $username, $email, $passwordHash);

    try {
        $stmt->execute();

        $userId = $stmt->insert_id;

        $_SESSION['user_id']  = $userId;
        $_SESSION['username'] = $username;
        $_SESSION['nome']     = $nome;
        $_SESSION['ruolo']    = 'user';

        header('Location: login.php');
        exit;

    } catch (Throwable $e) {
        if ($e instanceof mysqli_sql_exception && (int)$e->getCode() === 1062) {
            header('Location: registrazione.html?errore=duplicato');
        } else {
            header('Location: registrazione.html?errore=generico');
        }
        exit;
    }
}

header('Location: registrazione.html');
exit;

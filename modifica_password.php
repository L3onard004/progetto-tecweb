<?php
declare(strict_types=1);
session_start();
require_once 'config.php';

if (empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = (int) $_SESSION['user_id'];
$errors = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldPassword     = $_POST['old_password'] ?? '';
    $newPassword     = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($oldPassword === '' || $newPassword === '' || $confirmPassword === '') {
        $errors = 'Compila tutti i campi.';
    } elseif (mb_strlen($newPassword, 'UTF-8') < 8) {
        $errors = 'La nuova password deve contenere almeno 8 caratteri.';
    } elseif ($newPassword !== $confirmPassword) {
        $errors = 'Le nuove password non coincidono.';
    } else {
        $stmt = $conn->prepare("SELECT password FROM utenti WHERE id = ?");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->bind_result($hashedPassword);
        $stmt->fetch();
        $stmt->close();

        if (!password_verify($oldPassword, $hashedPassword)) {
            $errors = 'La vecchia password non è corretta.';
        } else {
            $newHashed = password_hash($newPassword, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("UPDATE utenti SET password = ? WHERE id = ?");
            $stmt->bind_param('si', $newHashed, $userId);
            if ($stmt->execute()) {
                $stmt->close();
                header('Location: login.php?message=password_changed');
                exit;
            } else {
                $errors = 'Errore durante l\'aggiornamento della password. Riprova.';
            }
        }
    }
}

// CARICAMENTO TEMPLATE
$template = file_get_contents('Template/modifica_password.html');
if ($template === false) {
    http_response_code(500);
    header('Location: 500.php');
    exit;
}
$template = str_replace('[MODPW_ERR]', htmlspecialchars($errors, ENT_QUOTES, 'UTF-8'), $template);
if (empty($errors)) {
    $template = str_replace('id="modpw-errors"', 'id="modpw-errors" hidden', $template);
}
echo $template;

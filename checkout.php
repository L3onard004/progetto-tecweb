<?php
session_start();
require_once 'config.php';

// CONTROLLO LOGIN
if (empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$nome = (string)($_SESSION['nome'] ?? 'cliente');
$idUtente = (int)$_SESSION['user_id'];
$idOrdine = isset($_SESSION['id_ordine']) ? (int)$_SESSION['id_ordine'] : 0;

$numeroOrdine = 'Ordine non disponibile';
$dataOrdine = date('d/m/Y');
$totaleOrdine = '0,00 €';

$messaggioCheckout = '';
if (!empty($_SESSION['warning'])) {
    $messaggioCheckout = '<p class="checkout-notice" role="status">' .
        htmlspecialchars((string)$_SESSION['warning'], ENT_QUOTES, 'UTF-8') .
        '</p>';
    unset($_SESSION['warning']);
}

// LETTURA ORDINE
if ($idOrdine > 0) {
    $sql = "
        SELECT 
            o.id,
            o.data,
            COALESCE(SUM(op.quantita * op.prezzo_unitario), 0) AS totale
        FROM ordini o
        LEFT JOIN ordine_prodotti op ON op.id_ordine = o.id
        WHERE o.id = ? AND o.id_utente = ?
        GROUP BY o.id, o.data
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ii", $idOrdine, $idUtente);
    }
} else {
    $sql = "
        SELECT
            o.id,
            o.data,
            COALESCE(SUM(op.quantita * op.prezzo_unitario), 0) AS totale
        FROM ordini o
        LEFT JOIN ordine_prodotti op ON op.id_ordine = o.id
        WHERE o.id_utente = ?
        GROUP BY o.id, o.data
        ORDER BY o.data DESC, o.id DESC
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $idUtente);
    }
}

if (isset($stmt) && $stmt) {
    $stmt->execute();
    $result = $stmt->get_result();

    if ($ordine = $result->fetch_assoc()) {
        $numeroOrdine = 'UW-' . str_pad((string)$ordine['id'], 6, '0', STR_PAD_LEFT);
        $dataOrdine = date('d/m/Y H:i', strtotime((string)$ordine['data']));
        $totaleOrdine = number_format((float)$ordine['totale'], 2, ',', '.') . ' €';
    }

    $stmt->close();
}

// controlli output 
$nome = htmlspecialchars($nome, ENT_QUOTES, 'UTF-8');
$numeroOrdine = htmlspecialchars($numeroOrdine, ENT_QUOTES, 'UTF-8');
$dataOrdine = htmlspecialchars($dataOrdine, ENT_QUOTES, 'UTF-8');
$totaleOrdine = htmlspecialchars($totaleOrdine, ENT_QUOTES, 'UTF-8');

// CARICAMENTO TEMPLATE
$template = file_get_contents('Template/checkout.html');
if ($template === false) {
    http_response_code(500);
    header('Location: 500.php');
    exit;
}

$template = str_replace(
    ['{{NOME_UTENTE}}', '{{NUMERO_ORDINE}}', '{{DATA_ORDINE}}', '{{TOTALE_ORDINE}}', '{{MESSAGGIO_CHECKOUT}}'],
    [$nome, $numeroOrdine, $dataOrdine, $totaleOrdine, $messaggioCheckout],
    $template
);

echo $template;

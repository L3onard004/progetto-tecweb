<?php
session_start();
require_once 'config.php';

// CONTROLLO LOGIN
if (empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$idUtente = (int) $_SESSION['user_id'];

// CONTROLLO CARRELLO
if (empty($_SESSION['id_carrello'])) {
    header("Location: carrello.php");
    exit;
}

$idCarrello = (int) $_SESSION['id_carrello'];

// LETTURRA CARRELLO DAL DB
$sql = "
    SELECT 
        cp.id_prodotto,
        cp.quantita,
        p.prezzo,
        p.attivo
    FROM carrello_prodotti cp
    LEFT JOIN prodotti p ON p.id = cp.id_prodotto
    WHERE cp.id_carrello = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $idCarrello);
$stmt->execute();
$result = $stmt->get_result();

$prodottiAcquistabili = [];
$prodottiScartati = 0;

while ($row = $result->fetch_assoc()) {
    $isAttivo = isset($row['attivo']) && (int)$row['attivo'] === 1;
    $hasPrezzo = isset($row['prezzo']);

    if ($isAttivo && $hasPrezzo) {
        $prodottiAcquistabili[] = [
            'id_prodotto' => (int)$row['id_prodotto'],
            'quantita' => (int)$row['quantita'],
            'prezzo' => (float)$row['prezzo']
        ];
    } else {
        $prodottiScartati++;
    }
}

$stmt->close();

if (count($prodottiAcquistabili) === 0) {
    header("Location: carrello.php?errore=prodotti_non_disponibili");
    exit;
}

$conn->begin_transaction();

try {
    // Creazione dell'ordine
    $sqlOrdine = "INSERT INTO ordini (id_utente) VALUES (?)";
    $stmtOrdine = $conn->prepare($sqlOrdine);
    $stmtOrdine->bind_param("i", $idUtente);
    $stmtOrdine->execute();

    $idOrdine = (int)$conn->insert_id;
    $_SESSION['id_ordine'] = $idOrdine;

    $sqlProdotto = "
        INSERT INTO ordine_prodotti
        (id_ordine, id_prodotto, quantita, prezzo_unitario)
        VALUES (?, ?, ?, ?)
    ";
    $stmtProdotto = $conn->prepare($sqlProdotto);

    foreach ($prodottiAcquistabili as $prodotto) {
        $idProdotto = $prodotto['id_prodotto'];
        $quantita = $prodotto['quantita'];
        $prezzo = $prodotto['prezzo'];

        $stmtProdotto->bind_param(
            "iiid",
            $idOrdine,
            $idProdotto,
            $quantita,
            $prezzo
        );
        $stmtProdotto->execute();
    }

    $sqlSvuota = "DELETE FROM carrello_prodotti WHERE id_carrello = ?";
    $stmtSvuota = $conn->prepare($sqlSvuota);
    $stmtSvuota->bind_param("i", $idCarrello);
    $stmtSvuota->execute();
    $conn->commit();

    if ($prodottiScartati > 0) {
        $_SESSION['warning'] = "Alcuni prodotti non erano più disponibili e non sono stati inclusi nell'ordine.";
    } else {
        unset($_SESSION['warning']);
    }

    header("Location: checkout.php");
    exit;

} catch (Throwable $e) {
    $conn->rollback();
    http_response_code(500);
    header('Location: 500.php');
    exit;
}

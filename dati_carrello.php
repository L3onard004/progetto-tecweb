<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

// funzione di supporto
function encode_asset_path(string $path): string {
    $parts = explode('/', str_replace('\\', '/', $path));
    $parts = array_map('rawurlencode', $parts);
    return implode('/', $parts);
}

if (empty($_SESSION['user_id'])) {
    echo json_encode(['items' => [], 'total' => 0, 'count' => 0, 'logged_in' => false]);
    exit;
}

if (empty($_SESSION['id_carrello'])) {
    $stmt = $conn->prepare("SELECT id FROM carrelli WHERE id_utente = ? LIMIT 1");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $res = $stmt->get_result();
    $stmt->close();
    if ($row = $res->fetch_assoc()) {
        $_SESSION['id_carrello'] = (int) $row['id'];
    }
}

if (empty($_SESSION['id_carrello'])) {
    echo json_encode(['items' => [], 'total' => 0, 'count' => 0, 'logged_in' => true]);
    exit;
}

$idCarrello = (int) $_SESSION['id_carrello'];

$stmt = $conn->prepare("
    SELECT
        p.id, p.nome, p.prezzo, cp.quantita,
        ip.file_path AS img_path,
        ip.alt AS img_alt
    FROM carrello_prodotti cp
    JOIN prodotti p ON p.id = cp.id_prodotto
    LEFT JOIN immagini_prodotti ip
        ON ip.id_prodotto = p.id AND ip.is_principale = 1
    WHERE cp.id_carrello = ? AND p.attivo = 1
");
$stmt->bind_param("i", $idCarrello);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

$items = [];
$total = 0;
$count = 0;

while ($p = $result->fetch_assoc()) {
    $imgSrc = '';
    $imgAlt = '';
    if (!empty($p['img_path'])) {
        $imgSrc = encode_asset_path((string)$p['img_path']);
        $imgAlt = !empty($p['img_alt'])
            ? (string)$p['img_alt']
            : 'Immagine del prodotto ' . (string)$p['nome'];
    }

    $subtotale = (float)$p['prezzo'] * (int)$p['quantita'];
    $total += $subtotale;
    $count += (int)$p['quantita'];

    $items[] = [
        'id'        => (int)$p['id'],
        'nome'      => (string)$p['nome'],
        'prezzo'    => (float)$p['prezzo'],
        'quantita'  => (int)$p['quantita'],
        'subtotale' => $subtotale,
        'img_src'   => $imgSrc,
        'img_alt'   => $imgAlt,
    ];
}

echo json_encode([
    'items'     => $items,
    'total'     => $total,
    'count'     => $count,
    'logged_in' => true,
]);

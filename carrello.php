<?php
session_start();
require_once 'config.php';

function encode_asset_path(string $path): string {
    $parts = explode('/', str_replace('\\', '/', $path));
    $parts = array_map('rawurlencode', $parts);
    return implode('/', $parts);
}

/* CONTROLLO LOGIN */
if (empty($_SESSION['user_id'])) {
    $idProdotto = (int) ($_POST['id_prodotto'] ?? 0);
    $qty = max(1, (int) ($_POST['qty'] ?? 1));
    $returnTo = (string)($_POST['return_to'] ?? '');

    if ($idProdotto > 0) {
        $_SESSION['guest_cart'][$idProdotto] =
            ($_SESSION['guest_cart'][$idProdotto] ?? 0) + $qty;
    }

    // dopo il login torna alla pagina prodotto
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    if ($returnTo !== '') {
        $sep = (strpos($returnTo, '?') !== false) ? '&' : '?';
        $_SESSION['redirect_after_login'] = $base . '/' . ltrim($returnTo, '/') . $sep . 'added=1';
    } else {
        $_SESSION['redirect_after_login'] = $base . '/prodotto.php?id=' . $idProdotto . '&added=1';
    }
    header("Location: login.php");   
    exit;
}

/* CREAZIONE E RECUPERO CARRELLO */
if (empty($_SESSION['id_carrello'])) {

    // recupero carrello esistente dell'utente
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
        $_SESSION['id_carrello'] = (int) $row['id'];
    } else {
        // creazione nuovo carrello
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

$idCarrello = (int) $_SESSION['id_carrello'];



/* GESTIONE POST */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $azione = $_POST['azione'] ?? '';

    if ($azione === 'aggiungi') {
        $idProdotto = (int) ($_POST['id_prodotto'] ?? 0);
        $qty = max(1, (int) ($_POST['qty'] ?? 1));
        $returnTo = (string)($_POST['return_to'] ?? '');

        $stmtCheck = $conn->prepare("SELECT attivo FROM prodotti WHERE id = ?");
        $stmtCheck->bind_param("i", $idProdotto);
        $stmtCheck->execute();
        $prod = $stmtCheck->get_result()->fetch_assoc();

        if (!$prod || (int)$prod['attivo'] !== 1) {
            header("Location: prodotti.php?errore=prodotto_non_disponibile");
            exit;
        }

        $stmt = $conn->prepare("
            INSERT INTO carrello_prodotti (id_carrello, id_prodotto, quantita)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE quantita = quantita + ?
        ");
        $stmt->bind_param("iiii", $idCarrello, $idProdotto, $qty, $qty);
        $stmt->execute();

        if ($returnTo !== '') {
            $sep = (strpos($returnTo, '?') !== false) ? '&' : '?';
            header('Location: ' . $returnTo . $sep . 'added=1');
            exit;
        }
        
        header('Location: carrello.php');
        exit;
    }

    if ($azione === 'aggiorna') {
        $idProdotto = (int) ($_POST['id_prodotto'] ?? 0);
        $qtyAttuale = (int) ($_POST['quantita'] ?? 1);
        $delta = (int) ($_POST['delta'] ?? 0);
        $qty = $qtyAttuale + $delta;

        if ($qty < 1) {
            $stmt = $conn->prepare("DELETE FROM carrello_prodotti WHERE id_carrello = ? AND id_prodotto = ?");
            $stmt->bind_param("ii", $idCarrello, $idProdotto);
        } else {
            $stmt = $conn->prepare("
                UPDATE carrello_prodotti
                SET quantita = ?
                WHERE id_carrello = ? AND id_prodotto = ?
            ");
            $stmt->bind_param("iii", $qty, $idCarrello, $idProdotto);
        }
        $stmt->execute();
        header("Location: carrello.php");
        exit;
    }

    if ($azione === 'rimuovi') {
        $idProdotto = (int) ($_POST['id_prodotto'] ?? 0);
        if ($idProdotto > 0) {
            $stmt = $conn->prepare("DELETE FROM carrello_prodotti WHERE id_carrello = ? AND id_prodotto = ?");
            $stmt->bind_param("ii", $idCarrello, $idProdotto);
            $stmt->execute();
        }
        header("Location: carrello.php");
        exit;
    }
}

/* LETTURA CARRELLO */
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

$totale = 0;
$prodottiHtml = '';

if ($result->num_rows === 0) {
    $prodottiHtml = '<p id="carrello-vuoto">Il tuo carrello è vuoto oppure contiene solo prodotti non più disponibili</p>
    <a href="prodotti.php" class="btn-primary">Vai ai prodotti</a>';
} else {
    while ($p = $result->fetch_assoc()) {
        $subtotale = $p['prezzo'] * $p['quantita'];
        $totale += $subtotale;

        $imgTag = '';
        if (!empty($p['img_path'])) {
            $src = encode_asset_path((string)$p['img_path']);
            $alt = !empty($p['img_alt'])
                ? htmlspecialchars((string)$p['img_alt'], ENT_QUOTES, 'UTF-8')
                : 'Immagine del prodotto ' . htmlspecialchars((string)$p['nome'], ENT_QUOTES, 'UTF-8');
            $imgTag = '<img src="'.$src.'" alt="'.$alt.'" loading="lazy">';
        }

        $prodottiHtml .= '
        <article class="cart-item">
            <div class="cart-item-media">
                '.$imgTag.'
            </div>
            <div class="cart-item-info">
                <h3 class="cart-item-title">'.htmlspecialchars($p['nome'], ENT_QUOTES, 'UTF-8').'</h3>
                <p class="cart-item-price">€'.number_format($p['prezzo'], 2, ',', '.').'</p>

                <form method="post" class="cart-qty-form">
                    <input type="hidden" name="azione" value="aggiorna">
                    <input type="hidden" name="id_prodotto" value="'.$p['id'].'">
                    <input type="hidden" name="quantita" value="'.$p['quantita'].'">
                    <button type="submit" name="delta" value="-1" aria-label="Diminuisci quantità" class="btn-mini" >−</button>
                    <span class="qty-display">'.$p['quantita'].'</span>
                    <button type="submit" name="delta" value="1" aria-label="Aumenta quantità" class="btn-mini" >+</button>
                </form>

                <p class="cart-item-total">Totale: €'.number_format($subtotale, 2, ',', '.').'</p>

                <form method="post">
                    <input type="hidden" name="azione" value="rimuovi">
                    <input type="hidden" name="id_prodotto" value="'.$p['id'].'">
                    <button type="submit" class="rimuovi-button">Rimuovi</button>
                </form>
            </div>
        </article>';
    }
}
/* CARICAMENTO TEMPLATE */
$template = file_get_contents('Template/carrello.html');
if ($template === false) {
    http_response_code(500);
    header('Location: 500.php');
    exit;
}
$template = str_replace('{{CART_ITEMS}}', $prodottiHtml, $template);
$template = str_replace('{{TOTAL}}', number_format($totale, 2, ',', '.'), $template);

echo $template;
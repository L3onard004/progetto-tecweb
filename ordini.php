<?php
session_start();
require_once 'config.php';

if (empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$sqlOrdini = "
    SELECT id, data
    FROM ordini
    WHERE id_utente = ?
    ORDER BY data DESC
";

$stmtOrdini = $conn->prepare($sqlOrdini);
$stmtOrdini->bind_param('i', $_SESSION['user_id']);
$stmtOrdini->execute();
$ordini = $stmtOrdini->get_result();
$contenutoOrdini = '';

if ($ordini->num_rows === 0) {

    $contenutoOrdini = '
        <p>Non hai ancora effettuato nessun ordine.</p>
        <a href="prodotti.php" class="btn">Vai ai prodotti</a>
    ';

} else {

    while ($ordine = $ordini->fetch_assoc()) {

        // Recupero dei prodotti
        $sqlProdotti = "
            SELECT p.nome, op.quantita, op.prezzo_unitario
            FROM ordine_prodotti op
            JOIN prodotti p ON p.id = op.id_prodotto
            WHERE op.id_ordine = ?
        ";

        $stmtProdotti = $conn->prepare($sqlProdotti);
        $stmtProdotti->bind_param('i', $ordine['id']);
        $stmtProdotti->execute();
        $prodotti = $stmtProdotti->get_result();

        $totale = 0;
        $righeProdotti = '';

        while ($p = $prodotti->fetch_assoc()) {
            $subtotale = $p['quantita'] * $p['prezzo_unitario'];
            $totale += $subtotale;

            $righeProdotti .= '
                <li class="ordine-prodotto">
                    <span class="prodotto-nome">'.htmlspecialchars($p['nome'], ENT_QUOTES, 'UTF-8').'</span>
                    <span class="prodotto-quantita">× '.(int)$p['quantita'].'</span>
                    <span class="prodotto-prezzo">
                        €'.number_format($subtotale, 2, ',', '.').'
                    </span>
                </li>
            ';
        }

        $contenutoOrdini .= '
        <section class="ordine">
            <div>
                <h3>
                    Ordine '.(int)$ordine['id'].' -
                    '.date('d/m/Y', strtotime($ordine['data'])).'
                </h3>
            </div>

            <ul class="ordine-prodotti">
                '.$righeProdotti.'
            </ul>

            <p class="ordine-totale">
                Totale ordine:
                <strong>€'.number_format($totale, 2, ',', '.').'</strong>
            </p>
        </section>
        ';
    }
}

// CARICAMENTO TEMPLATE
$template = file_get_contents('Template/ordini.html');
if ($template === false) {
    http_response_code(500);
    header('Location: 500.php');
    exit;
}

$template = str_replace('{{CONTENUTO_ORDINI}}', $contenutoOrdini, $template);

echo $template;

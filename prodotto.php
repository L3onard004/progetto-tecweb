<?php
require_once 'config.php';

function encode_asset_path(string $path): string {
    $parts = explode('/', str_replace('\\', '/', $path));
    $parts = array_map('rawurlencode', $parts);
    return implode('/', $parts);
}

function lines_to_list(string $text): string {
    $lines = array_filter(array_map('trim', explode("\n", str_replace("\r", '', $text))));
    if (empty($lines)) return '';
    $items = array_map(fn($l) => '<li>' . htmlspecialchars($l, ENT_QUOTES, 'UTF-8') . '</li>', array_values($lines));
    return '<ul>' . implode('', $items) . '</ul>';
}

function target_label(string $target): string {
    return match ($target) {
        'donna'    => 'Donna',
        'uomo'     => 'Uomo',
        'bambino'  => 'Bambino',
        'bambina'  => 'Bambina',
        default    => ucfirst($target),
    };
}

if (empty($_GET['id']) || !ctype_digit($_GET['id'])) {
    http_response_code(404);
    header('Location: 404.php');
    exit;
}

$id = (int) $_GET['id'];
$q  = trim($_GET['q'] ?? '');

// URL di ritorno dopo aver premuto Aggiungi al carrello
$returnTo = 'prodotto.php?id=' . $id;
if ($q !== '') {
    $returnTo .= '&q=' . urlencode($q);
}
if (!empty($_GET['target'])) {
    $returnTo .= '&target=' . urlencode((string)$_GET['target']);
}

$sql = "
    SELECT p.*, c.nome AS categoria
    FROM prodotti p
    JOIN categorie c ON c.id = p.id_categoria
    WHERE p.id = ? AND p.attivo = 1
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();

$result   = $stmt->get_result();
$prodotto = $result->fetch_assoc();

if (!$prodotto) {
    http_response_code(404);
    header('Location: 404.php');
    exit;
}

$imgs = [];
$stmtImg = $conn->prepare("
    SELECT file_path, alt, ordine, is_principale
    FROM immagini_prodotti
    WHERE id_prodotto = ?
    ORDER BY ordine ASC
");
$stmtImg->bind_param('i', $id);
$stmtImg->execute();
$resImg = $stmtImg->get_result();
while ($row = $resImg->fetch_assoc()) {
    $imgs[] = $row;
}
$stmtImg->close();

$galleriaHtml = '';
if (!empty($imgs)) {

    $main = $imgs[0];

    // normalizzazione path immagini
    $mainPath = (string)$main['file_path'];
    if ($mainPath !== '' && strpos($mainPath, 'immagini/') !== 0) {
        $mainPath = 'immagini/' . ltrim($mainPath, '/');
    }

    $mainSrc = encode_asset_path($mainPath);
    $mainAlt = htmlspecialchars((string)$main['alt'], ENT_QUOTES, 'UTF-8');

    $thumbsHtml = '';
    foreach ($imgs as $idx => $im) {
        if ($idx === 0) continue;

        $tPath = (string)$im['file_path'];
        if ($tPath !== '' && strpos($tPath, 'immagini/') !== 0) {
            $tPath = 'immagini/' . ltrim($tPath, '/');
        }

        $tSrc = encode_asset_path($tPath);
        $tAlt = htmlspecialchars((string)$im['alt'], ENT_QUOTES, 'UTF-8');
        $btnLabel = $tAlt !== '' ? $tAlt : 'Immagine aggiuntiva ' . $idx;

        $thumbsHtml .= '
            <button type="button" class="product-thumb" aria-label="' . $btnLabel . '">
                <img class="thumb-img" src="'.$tSrc.'" alt="" aria-hidden="true" loading="lazy">
            </button>
        ';
    }

    $galleriaHtml = '
        <div class="product-gallery">
            <div class="product-main-media">
                <img id="mainProductImage" src="'.$mainSrc.'" alt="'.$mainAlt.'" loading="eager">
            </div>

            <div class="product-thumbs" aria-label="Altre immagini del prodotto" role="group">
                '.$thumbsHtml.'
            </div>
        </div>
    ';
}

// PLACEHOLDER
$placeholders = [
    '{{TITOLO_PAGINA}}'    => htmlspecialchars($prodotto['nome'], ENT_QUOTES, 'UTF-8') . ' | UrbanWear',
    '{{META_DESCRIPTION}}' => htmlspecialchars($prodotto['descrizione'], ENT_QUOTES, 'UTF-8'),
    '{{NOME}}'             => htmlspecialchars($prodotto['nome'], ENT_QUOTES, 'UTF-8'),
    '{{DESCRIZIONE}}'      => htmlspecialchars($prodotto['descrizione'], ENT_QUOTES, 'UTF-8'),
    '{{PREZZO}}'           => number_format($prodotto['prezzo'], 2, ',', '.'),
    '{{ID_PRODOTTO}}'      => (int)$prodotto['id'],
    '{{TARGET}}'           => htmlspecialchars(target_label((string)$prodotto['target']), ENT_QUOTES, 'UTF-8'),
    '{{CATEGORIA}}'        => htmlspecialchars((string)$prodotto['categoria'], ENT_QUOTES, 'UTF-8'),
    '{{MATERIALI}}'        => lines_to_list((string)$prodotto['materiali']),
    '{{GUIDA_TAGLIE}}'     => lines_to_list((string)$prodotto['guida_taglie']),
    '{{CURA}}'             => lines_to_list((string)$prodotto['cura']),
    '{{BREADCRUMB}}'       => '<span aria-current="page">'
        . htmlspecialchars($prodotto['nome'], ENT_QUOTES, 'UTF-8')
        . '</span>',
    '{{FLASH_MESSAGE}}'    => (!empty($_GET['added']) && $_GET['added'] === '1')
        ? '<div id="cart-toast" class="msg-success cart-toast" role="status" aria-live="polite">Prodotto aggiunto al carrello</div>'
        : '',

    '{{RETURN_TO}}'        => $returnTo
    ,
    '{{GALLERIA}}'         => $galleriaHtml
];


if ($q !== '') {
    $placeholders['{{LINK_RITORNO}}'] =
        '<p class="auth-alt"><a href="prodotti.php?q='.urlencode($q).'">
        Torna ai risultati della ricerca</a></p>';
} elseif (!empty($_GET['target'])) {
    $placeholders['{{LINK_RITORNO}}'] =
        '<p class="auth-alt"><a href="prodotti.php?target='.urlencode($_GET['target']).'">
        Torna ai prodotti '.htmlspecialchars(target_label((string)$_GET['target']), ENT_QUOTES, 'UTF-8').'</a></p>';
} else {
    $placeholders['{{LINK_RITORNO}}'] =
        '<p class="auth-alt"><a href="prodotti.php">
        Torna al catalogo completo</a></p>';
}

// CARICAMENTO TEMPLATE
$template = file_get_contents('Template/prodotto.html');
if ($template === false) {
    http_response_code(500);
    header('Location: 500.php');
    exit;
}
$template = str_replace(array_keys($placeholders), array_values($placeholders), $template);

echo $template;

$stmt->close();

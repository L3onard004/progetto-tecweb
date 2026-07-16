<?php
require_once 'config.php';

// Funzioni di supporto
function encode_asset_path(string $path): string {
    $parts = explode('/', str_replace('\\', '/', $path));
    $parts = array_map('rawurlencode', $parts);
    return implode('/', $parts);
}

$where  = [];
$params = [];
$types  = "";

$where[] = "attivo = 1";

$target = trim((string)($_GET['target'] ?? ''));

function target_label(string $target): string {
    return match ($target) {
        'donna'    => 'Donna',
        'uomo'     => 'Uomo',
        'bambino'  => 'Bambino',
        'bambina'  => 'Bambina',
        default    => '',
    };
}

$labelTarget = target_label($target);

$breadcrumbProdotti = '<a href="prodotti.php">Prodotti</a>';
if ($labelTarget !== '') {
    $breadcrumbProdotti .= ' &raquo; <span aria-current="page">'.$labelTarget.'</span>';
} else {
    $breadcrumbProdotti = '<span aria-current="page">Prodotti</span>';
}

if ($target !== '') {
    $where[]  = "target = ?";
    $params[] = $target;
    $types   .= "s";
}

if (!empty($_GET['q'])) {
    $where[]  = "(nome LIKE ? OR descrizione LIKE ?)";
    $search   = "%" . $_GET['q'] . "%";
    $params[] = $search;
    $params[] = $search;
    $types   .= "ss";
}

$sql = "
    SELECT
        p.*,
        ip.file_path AS img_path,
        ip.alt AS img_alt
    FROM prodotti p
    LEFT JOIN immagini_prodotti ip
        ON ip.id_prodotto = p.id AND ip.is_principale = 1
";
if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result   = $stmt->get_result();
$prodotti = $result->fetch_all(MYSQLI_ASSOC);
$prodottiHtml = '';

if (empty($prodotti)) {
    $prodottiHtml = '<p>Nessun prodotto disponibile.</p>';
} else {
    $prodottiHtml = '<ul class="prodotti-grid">';
    foreach ($prodotti as $p) {
        $imgTag = '';
        if (!empty($p['img_path'])) {
            $src = encode_asset_path((string)$p['img_path']);
            $alt = !empty($p['img_alt'])
                ? htmlspecialchars((string)$p['img_alt'], ENT_QUOTES, 'UTF-8')
                : 'Immagine del prodotto ' . htmlspecialchars((string)$p['nome'], ENT_QUOTES, 'UTF-8');
            $imgTag = '<img src="' . $src . '" alt="' . $alt . '" loading="lazy">';
        }

        $link = 'prodotto.php?id=' . (int)$p['id'];
        $linkParams = [];
        if ($target !== '') {
            $linkParams['target'] = $target;
        }
        if (!empty($_GET['q'])) {
            $linkParams['q'] = (string)$_GET['q'];
        }
        if (!empty($linkParams)) {
            $link .= '&' . http_build_query($linkParams);
        }

        $prodottiHtml .= '
        <li class="'.htmlspecialchars($p['target'], ENT_QUOTES, 'UTF-8').'">
            <a href="'.$link.'" class="prodotti-link">
                <article class="prodotti">
                    '.$imgTag.'
                    <h3>'.htmlspecialchars($p['nome'], ENT_QUOTES, 'UTF-8').'</h3>
                    <p>'.htmlspecialchars($p['descrizione'], ENT_QUOTES, 'UTF-8').'</p>
                    <p><strong>Prezzo:</strong> €'.number_format($p['prezzo'], 2, ',', '.').'</p>
                    <span class="vedi-prodotto">Vedi prodotto</span>
                </article>
            </a>
        </li>';
    }
    $prodottiHtml .= '</ul>';
}


$erroreHtml = '';
if (($_GET['errore'] ?? '') === 'prodotto_non_disponibile') {
    $erroreHtml = '<div class="alert alert-warning">Il prodotto selezionato non è più disponibile.</div>';
}

$tornaCatalogoHtml = '';
if (!empty($_GET['target']) || !empty($_GET['q'])) {
    $tornaCatalogoHtml = '
    <div class="torna-catalogo">
        <a href="prodotti.php" class="btn-primary">Torna al catalogo completo</a>
    </div>';
}

// CARICAMENTO TEMPLATE
$template = file_get_contents('Template/prodotti.html');
if ($template === false) {
    http_response_code(500);
    header('Location: 500.php');
    exit;
}

$template = str_replace('{{PRODOTTI}}', $prodottiHtml, $template);
$template = str_replace('{{ERRORE}}', $erroreHtml, $template);
$template = str_replace('{{TORNA_CATALOGO}}', $tornaCatalogoHtml, $template);
$template = str_replace('{{BREADCRUMB_PRODOTTI}}', $breadcrumbProdotti, $template);


echo $template;

$stmt->close();

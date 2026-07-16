<?php

declare(strict_types=1);

session_start();
require_once 'config.php';

// Funzioni di supporto
function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// rimozione caratteri non ammessi
function safe_folder_name(string $name): string
{
    $name = trim($name);
    $name = preg_replace('/[<>:"\/*\\\\|\?\*\x00-\x1F]/u', '', $name) ?? '';
    $name = preg_replace('/\s+/u', ' ', $name) ?? '';
    $name = trim($name, " .\t\n\r\0\x0B");
    return $name;
}

function target_folder(string $target): string
{
    return $target;
}

function upload_error_message(int $err): string
{
    return match ($err) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File troppo grande.',
        UPLOAD_ERR_PARTIAL => 'Upload incompleto.',
        UPLOAD_ERR_NO_FILE => 'Nessun file caricato.',
        UPLOAD_ERR_NO_TMP_DIR => 'Cartella temporanea mancante.',
        UPLOAD_ERR_CANT_WRITE => 'Impossibile scrivere il file su disco.',
        UPLOAD_ERR_EXTENSION => 'Upload bloccato da estensione PHP.',
        default => 'Errore durante l’upload.',
    };
}

function allowed_image_ext_from_mime(string $mime): ?string
{
    return match ($mime) {
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        default => null,
    };
}


// Valida il caricamento di un'immagine
function validate_image_upload(string $field, int $maxBytes = 1_000_000): array
{
    if (empty($_FILES[$field]) || !is_array($_FILES[$field])) {
        throw new RuntimeException('File mancante: ' . $field);
    }

    $f = $_FILES[$field];
    $err = (int)($f['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($err !== UPLOAD_ERR_OK) {
        throw new RuntimeException(upload_error_message($err));
    }

    $tmp = (string)($f['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        throw new RuntimeException('Upload non valido.');
    }

    $size = (int)($f['size'] ?? 0);
    if ($size <= 0 || $size > $maxBytes) {
        throw new RuntimeException('Dimensione immagine non valida (max 5MB).');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($tmp) ?: '';
    $ext   = allowed_image_ext_from_mime($mime);
    if ($ext === null) {
        throw new RuntimeException('Formato immagine non supportato (solo JPG/PNG/WEBP).');
    }

    $orig = (string)($f['name'] ?? '');
    return [$tmp, $ext, $orig];
}

if (empty($_SESSION['user_id'])) {
    http_response_code(403);
    header('Location: 403.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];

$stmtRole = $conn->prepare("SELECT ruolo FROM utenti WHERE id = ? LIMIT 1");
if (!$stmtRole) {
    http_response_code(500);
    header('Location: 500.php');
    exit;
}
$stmtRole->bind_param("i", $userId);
$stmtRole->execute();
$resRole = $stmtRole->get_result();
$rowRole = $resRole ? $resRole->fetch_assoc() : null;
$stmtRole->close();

if (!$rowRole || ($rowRole['ruolo'] ?? '') !== 'admin') {
    http_response_code(403);
    header('Location: 403.php');
    exit;
}

// CARICAMENTO TEMPLATE
$template = file_get_contents('Template/gestione_prodotti.html');
if ($template === false) {
    http_response_code(500);
    header('Location: 500.php');
    exit;
}

$errAdd = $succAdd = '';
$errEdit = $succEdit = '';
$errStato = $succStato = '';
$errDel = $succDel = '';

// Azioni disponibili per l'amministratore
$azione = (string)($_POST['azione'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Aggiungi
    if ($azione === 'aggiungi') {
        $nome        = trim((string)($_POST['nome'] ?? ''));
        $descrizione = trim((string)($_POST['descrizione'] ?? ''));
        $materiali   = trim((string)($_POST['materiali'] ?? ''));
        $guidaTaglie = trim((string)($_POST['guida_taglie'] ?? ''));
        $cura        = trim((string)($_POST['cura'] ?? ''));
        $prezzoRaw   = trim((string)($_POST['prezzo'] ?? ''));
        $idCategoria = (int)($_POST['id_categoria'] ?? 0);
        $target      = trim((string)($_POST['target'] ?? ''));

        if ($nome === '' || $descrizione === '' || $materiali === '' || $guidaTaglie === '' || $cura === '' || $prezzoRaw === '' || $idCategoria <= 0 || !in_array($target, ['uomo', 'donna', 'bambino', 'bambina'], true)) {
            $errAdd = '<p>Compila correttamente tutti i campi obbligatori.</p>';
        } elseif (!preg_match('/^\d+(\.\d{1,2})?$/', $prezzoRaw) || (float)$prezzoRaw <= 0) {
            $errAdd = '<p>Inserisci un prezzo valido (maggiore di 0).</p>';
        } else {
            try {
                [$tmpNorm, $extNorm] = validate_image_upload('img_normale');
                [$tmpFronte, $extFronte] = validate_image_upload('img_fronte');
                [$tmpRetro, $extRetro] = validate_image_upload('img_retro');
                [$tmpExtra, $extExtra] = validate_image_upload('img_extra');
            } catch (RuntimeException $ex) {
                $errAdd = '<p>' . e($ex->getMessage()) . '</p>';
                $tmpNorm = $tmpFronte = $tmpRetro = $tmpExtra = '';
            }

            if ($errAdd === '') {
                $prezzo = (float)$prezzoRaw;
                $conn->begin_transaction();

                try {
                    $stmt = $conn->prepare(
                        "INSERT INTO prodotti (nome, descrizione, materiali, guida_taglie, cura, prezzo, id_categoria, target, attivo)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)"
                    );
                    if (!$stmt) {
                        throw new RuntimeException('Errore interno: impossibile preparare la query.');
                    }
                    $stmt->bind_param("sssssdis", $nome, $descrizione, $materiali, $guidaTaglie, $cura, $prezzo, $idCategoria, $target);
                    if (!$stmt->execute()) {
                        $stmt->close();
                        throw new RuntimeException('Non è stato possibile aggiungere il prodotto.');
                    }
                    $newId = (int)$stmt->insert_id;
                    $stmt->close();

                    $tFolder = target_folder($target);
                    $pFolder = safe_folder_name(mb_strtolower($nome, 'UTF-8'));
                    if ($pFolder === '') {
                        $pFolder = 'prodotto-' . $newId;
                    }

                    $absDir = 'immagini/' . $tFolder . '/' . $pFolder;
                    if (!is_dir($absDir)) {
                        if (!mkdir($absDir, 0775, true) && !is_dir($absDir)) {
                            throw new RuntimeException('Impossibile creare la cartella immagini.');
                        }
                    }

                    // salvataggio file con nomi standard
                    $saved = [];
                    $move = function (string $tmp, string $ext, string $base) use ($absDir, &$saved): string {
                        $fname = $base . '.' . $ext;
                        $dest = $absDir . '/' . $fname;
                        if (!move_uploaded_file($tmp, $dest)) {
                            throw new RuntimeException('Errore durante il salvataggio delle immagini.');
                        }
                        $saved[] = $dest;
                        return $fname;
                    };

                    $file1 = $move($tmpNorm, $extNorm, 'prodotto');
                    $file2 = $move($tmpFronte, $extFronte, 'fronte');
                    $file3 = $move($tmpRetro, $extRetro, 'retro');
                    $file4 = $move($tmpExtra, $extExtra, 'extra');
                    $relBase = 'immagini/' . $tFolder . '/' . $pFolder . '/';
                    $imgs = [
                        [1, 1, $relBase . $file1, $nome . ', foto prodotto su sfondo neutro'],
                        [2, 0, $relBase . $file2, $nome . ', vista frontale'],
                        [3, 0, $relBase . $file3, $nome . ', vista posteriore'],
                        [4, 0, $relBase . $file4, $nome . ', immagine extra'],
                    ];

                    $stmtImg = $conn->prepare(
                        "INSERT INTO immagini_prodotti (id_prodotto, file_path, alt, ordine, is_principale)
                         VALUES (?, ?, ?, ?, ?)"
                    );
                    if (!$stmtImg) {
                        throw new RuntimeException('Errore interno: impossibile preparare inserimento immagini.');
                    }
                    foreach ($imgs as [$ordine, $isPrincipale, $path, $alt]) {
                        $ordine = (int)$ordine;
                        $isPrincipale = (int)$isPrincipale;
                        $alt = (string)$alt;
                        $path = (string)$path;
                        $stmtImg->bind_param('issii', $newId, $path, $alt, $ordine, $isPrincipale);
                        if (!$stmtImg->execute()) {
                            $stmtImg->close();
                            throw new RuntimeException('Non è stato possibile salvare le immagini nel database.');
                        }
                    }
                    $stmtImg->close();

                    $conn->commit();
                    $succAdd = '<p>Prodotto aggiunto con successo (con immagini).</p>';
                } catch (Throwable $ex) {
                    $conn->rollback();

                    // pulizia file eventualmente salvati
                    if (!empty($saved)) {
                        foreach ($saved as $fp) {
                            if (is_string($fp) && $fp !== '' && is_file($fp)) {
                                @unlink($fp);
                            }
                        }
                    }

                    $errAdd = '<p>' . e($ex->getMessage()) . '</p>';
                }
            }
        }
    }

    //Modifica
    if ($azione === 'modifica') {
        $idProdotto    = (int)($_POST['id_prodotto'] ?? 0);
        $nomeMod       = trim((string)($_POST['nome_mod'] ?? ''));
        $descMod       = trim((string)($_POST['descrizione_mod'] ?? ''));
        $materialiMod  = trim((string)($_POST['materiali_mod'] ?? ''));
        $guidaMod      = trim((string)($_POST['guida_taglie_mod'] ?? ''));
        $curaMod       = trim((string)($_POST['cura_mod'] ?? ''));
        $prezzoMod     = trim((string)($_POST['prezzo_mod'] ?? ''));

        if ($idProdotto <= 0) {
            $errEdit = '<p>Seleziona un prodotto valido.</p>';
        } else {
            $set = [];
            $types = '';
            $vals = [];

            if ($nomeMod !== '') {
                $set[] = "nome = ?";
                $types .= "s";
                $vals[] = $nomeMod;
            }
            if ($descMod !== '') {
                $set[] = "descrizione = ?";
                $types .= "s";
                $vals[] = $descMod;
            }
            if ($materialiMod !== '') {
                $set[] = "materiali = ?";
                $types .= "s";
                $vals[] = $materialiMod;
            }
            if ($guidaMod !== '') {
                $set[] = "guida_taglie = ?";
                $types .= "s";
                $vals[] = $guidaMod;
            }
            if ($curaMod !== '') {
                $set[] = "cura = ?";
                $types .= "s";
                $vals[] = $curaMod;
            }
            if ($prezzoMod !== '') {
                if (!preg_match('/^\d+(\.\d{1,2})?$/', $prezzoMod) || (float)$prezzoMod <= 0) {
                    $errEdit = '<p>Prezzo non valido.</p>';
                } else {
                    $set[] = "prezzo = ?";
                    $types .= "d";
                    $vals[] = (float)$prezzoMod;
                }
            }

            if ($errEdit === '' && count($set) === 0) {
                $errEdit = '<p>Non hai inserito nessuna modifica.</p>';
            }

            if ($errEdit === '') {
                $sql = "UPDATE prodotti SET " . implode(", ", $set) . " WHERE id = ?";
                $stmt = $conn->prepare($sql);

                if (!$stmt) {
                    $errEdit = '<p>Errore interno: impossibile preparare la query.</p>';
                } else {
                    $types .= "i";
                    $vals[] = $idProdotto;

                    $stmt->bind_param($types, ...$vals);

                    if ($stmt->execute()) {
                        $succEdit = '<p>Modifiche salvate.</p>';
                    } else {
                        $errEdit = '<p>Non è stato possibile salvare le modifiche.</p>';
                    }
                    $stmt->close();
                }
            }
        }
    }

    // Stato
    if ($azione === 'stato') {
        $idProdotto = (int)($_POST['id_prodotto'] ?? 0);
        $attivoRaw  = (string)($_POST['attivo'] ?? '');

        if ($idProdotto <= 0) {
            $errStato = '<p>Seleziona un prodotto valido.</p>';
        } elseif ($attivoRaw !== '0' && $attivoRaw !== '1') {
            $errStato = '<p>Stato non valido.</p>';
        } else {
            $attivo = (int)$attivoRaw;
            $stmt = $conn->prepare("UPDATE prodotti SET attivo = ? WHERE id = ?");

            if (!$stmt) {
                $errStato = '<p>Errore interno: impossibile preparare la query.</p>';
            } else {
                $stmt->bind_param("ii", $attivo, $idProdotto);
                if ($stmt->execute()) {
                    $succStato = $attivo === 1 ? '<p>Prodotto attivato.</p>' : '<p>Prodotto disattivato.</p>';
                } else {
                    $errStato = '<p>Non è stato possibile aggiornare lo stato.</p>';
                }
                $stmt->close();
            }
        }
    }

    // Elimina
    if ($azione === 'elimina') {
        $idProdotto = (int)($_POST['id_prodotto'] ?? 0);

        if ($idProdotto <= 0) {
            $errDel = '<p>Seleziona un prodotto valido.</p>';
        } else {
            $stmt = $conn->prepare("DELETE FROM prodotti WHERE id = ?");
            if (!$stmt) {
                $errDel = '<p>Errore interno: impossibile preparare la query.</p>';
            } else {
                $stmt->bind_param("i", $idProdotto);

                try {
                    $stmt->execute();

                    if ($stmt->affected_rows > 0) {
                        $succDel = '<p>Prodotto eliminato definitivamente.</p>';
                    } else {
                        $errDel = '<p>Non è stato possibile eliminare il prodotto (forse non esiste più).</p>';
                    }
                } catch (mysqli_sql_exception $ex) {
                    // Controllo se il prodotto è presente in ordini
                    if ((int)$ex->getCode() === 1451) {
                        $errDel = '<p>Non posso eliminare questo prodotto perché è presente in uno o più ordini. Per mantenere lo storico, disattivalo (attivo = 0).</p>';
                    } else {
                        $errDel = '<p>Errore durante l’eliminazione del prodotto.</p>';
                    }
                }

                $stmt->close();
            }
        }
    }
}

//CARICAMENTO TEMPLATE
$categorieOptions = '';
$resCat = $conn->query("SELECT id, nome FROM categorie ORDER BY nome ASC");
if ($resCat) {
    while ($c = $resCat->fetch_assoc()) {
        $categorieOptions .= '<option value="' . (int)$c['id'] . '">' . e((string)$c['nome']) . '</option>';
    }
    $resCat->free();
}

$prodottiOptions = '';
$rows = '';

$sqlProd = "
    SELECT p.id, p.nome, p.prezzo, p.target, p.attivo, c.nome AS categoria
    FROM prodotti p
    JOIN categorie c ON c.id = p.id_categoria
    ORDER BY p.id ASC
";
$resProd = $conn->query($sqlProd);

if ($resProd && $resProd->num_rows > 0) {
    $rowNum = 0;
    while ($p = $resProd->fetch_assoc()) {
        $id = (int)$p['id'];
        $rowNum++;
        $nome = (string)$p['nome'];
        $stato = ((int)$p['attivo'] === 1) ? 'Attivo' : 'Disattivo';

        $rows .= '<tr>'
            .  '<th scope="row" data-label="N°">' . $rowNum . '</th>'
            .  '<td data-label="Nome">' . e($nome) . '</td>'
            .  '<td data-label="Categoria">' . e((string)$p['categoria']) . '</td>'
            .  '<td data-label="Target">' . e((string)$p['target']) . '</td>'
            .  '<td data-label="Prezzo">' . number_format((float)$p['prezzo'], 2, ',', '') . '€</td>'
            .  '<td data-label="Stato">' . $stato . '</td>'
            .  '</tr>';

        $prodottiOptions .= '<option value="' . $id . '">' . e($nome) . ' (ID ' . $id . ')</option>';
    }
    $resProd->free();
} else {
    $rows = '<tr><td colspan="6">Nessun prodotto presente.</td></tr>';
}

$prodottiTabella = '
<p id="catalogo-desc" class="visually-hidden">
  Tabella del catalogo prodotti UrbanWear. Colonne: Numero, Nome, Categoria, Target, Prezzo in euro, Stato.
</p>

<div class="table-wrap" role="region" aria-label="Catalogo prodotti" tabindex="0">
  <table class="admin-table" aria-describedby="catalogo-desc">
    <caption class="visually-hidden">Elenco prodotti UrbanWear</caption>

    <thead>
      <tr>
        <th scope="col"><abbr title="Numero">N°</abbr></th>
        <th scope="col">Nome</th>
        <th scope="col">Categoria</th>
        <th scope="col">Target</th>
        <th scope="col">Prezzo</th>
        <th scope="col">Stato</th>
      </tr>
    </thead>

    <tbody>
      ' . $rows . '
    </tbody>
  </table>
</div>';


// SOSTITUZIONE PLACEHOLDER
$template = str_replace('<template data-ph="CATEGORIE_OPTIONS"></template>', $categorieOptions, $template);
$template = str_replace('[PRODOTTI_TABELLA]', $prodottiTabella, $template);
$template = str_replace('<template data-ph="PRODOTTI_OPTIONS"></template>', $prodottiOptions, $template);

$template = str_replace('[ERR_ADD]', $errAdd, $template);
$template = str_replace('[SUCC_ADD]', $succAdd, $template);

$template = str_replace('[ERR_EDIT]', $errEdit, $template);
$template = str_replace('[SUCC_EDIT]', $succEdit, $template);

$template = str_replace('[ERR_STATO]', $errStato, $template);
$template = str_replace('[SUCC_STATO]', $succStato, $template);

$template = str_replace('[ERR_DEL]', $errDel, $template);
$template = str_replace('[SUCC_DEL]', $succDel, $template);

echo $template;

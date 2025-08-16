<?php
require_once 'auth.php';
require_once 'config.php';
require_once 'qr_helper.php';

// Requires admin authentication
requireAdmin();

$labels = [];
$error = '';
$success = '';

// Function to parse specific IDs (ranges and comma-separated)
function parseSpecificIds($input) {
    $ids = [];
    $input = trim($input);
    
    if (empty($input)) {
        return $ids;
    }
    
    // Split by comma first
    $parts = explode(',', $input);
    
    foreach ($parts as $part) {
        $part = trim($part);
        
        // Check if it's a range (contains dash)
        if (strpos($part, '-') !== false) {
            $range = explode('-', $part, 2);
            $start = (int)trim($range[0]);
            $end = (int)trim($range[1]);
            
            if ($start > 0 && $end > 0 && $start <= $end) {
                for ($i = $start; $i <= $end; $i++) {
                    $ids[] = $i;
                }
            }
        } else {
            // Single ID
            $id = (int)$part;
            if ($id > 0) {
                $ids[] = $id;
            }
        }
    }
    
    // Remove duplicates and sort
    $ids = array_unique($ids);
    sort($ids);
    
    return $ids;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $from_id = isset($_POST['from_id']) && $_POST['from_id'] !== '' ? (int)$_POST['from_id'] : null;
    $to_id = isset($_POST['to_id']) && $_POST['to_id'] !== '' ? (int)$_POST['to_id'] : null;
    $specific_ids_input = $_POST['specific_ids'] ?? '';
    $sort_by = $_POST['sort_by'] ?? 'created_at_desc';
    $preview_limit = isset($_POST['preview_limit']) && $_POST['preview_limit'] !== '' ? (int)$_POST['preview_limit'] : 24;
    $start_position = isset($_POST['start_position']) && $_POST['start_position'] !== '' ? (int)$_POST['start_position'] : 1;
    
    // Parse specific IDs if provided
    $specific_ids = parseSpecificIds($specific_ids_input);
    
    // Validate inputs
    if ($from_id === null && $to_id === null && empty($specific_ids)) {
        $error = 'Inserire almeno un valore tra "Da ID", "A ID" o "ID specifici"';
    } elseif ($start_position < 1 || $start_position > 24) {
        $error = 'La posizione di partenza deve essere tra 1 e 24';
    } else {
        try {
            $db = getDb();
            
            // Build the query
            $sql = "SELECT k.kit_id, t.name as team_name, n.name as nation_name, n.fifa_code, k.season, k.created_at, jt.name as jersey_type_name
                    FROM kits k 
                    JOIN teams t ON k.team_id = t.team_id 
                    JOIN nations n ON t.nation_id = n.nation_id 
                    LEFT JOIN jersey_types jt ON k.jersey_type_id = jt.jersey_type_id
                    WHERE 1=1";
            
            $params = [];
            
            // Priority: if specific IDs are provided, use them; otherwise use range
            if (!empty($specific_ids)) {
                $placeholders = [];
                foreach ($specific_ids as $index => $id) {
                    $placeholder = ":id_" . $index;
                    $placeholders[] = $placeholder;
                    $params[$placeholder] = $id;
                }
                $sql .= " AND k.kit_id IN (" . implode(',', $placeholders) . ")";
            } else {
                // Use range filters only if no specific IDs provided
                if ($from_id !== null) {
                    $sql .= " AND k.kit_id >= :from_id";
                    $params['from_id'] = $from_id;
                }
                
                if ($to_id !== null) {
                    $sql .= " AND k.kit_id <= :to_id";
                    $params['to_id'] = $to_id;
                }
            }
            
            // Add sorting
            switch ($sort_by) {
                case 'id_asc':
                    $sql .= " ORDER BY k.kit_id ASC";
                    break;
                case 'id_desc':
                    $sql .= " ORDER BY k.kit_id DESC";
                    break;
                case 'created_at_asc':
                    $sql .= " ORDER BY k.created_at ASC";
                    break;
                case 'created_at_desc':
                default:
                    $sql .= " ORDER BY k.created_at DESC";
                    break;
            }
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $all_labels = $stmt->fetchAll();
            
            if (empty($all_labels)) {
                if (!empty($specific_ids)) {
                    $error = 'Nessuna etichetta trovata per gli ID specificati';
                } else {
                    $error = 'Nessuna etichetta trovata per i criteri selezionati';
                }
            } else {
                // Limit for preview if specified
                if ($preview_limit > 0 && count($all_labels) > $preview_limit) {
                    $labels = array_slice($all_labels, 0, $preview_limit);
                    if (!empty($specific_ids)) {
                        $success = 'Anteprima di ' . count($labels) . ' etichette su ' . count($all_labels) . ' totali (ID specifici: ' . count($specific_ids) . ' richiesti)';
                    } else {
                        $success = 'Anteprima di ' . count($labels) . ' etichette su ' . count($all_labels) . ' totali';
                    }
                } else {
                    $labels = $all_labels;
                    if (!empty($specific_ids)) {
                        $success = 'Trovate ' . count($labels) . ' etichette per ' . count($specific_ids) . ' ID specifici';
                    } else {
                        $success = 'Trovate ' . count($labels) . ' etichette';
                    }
                }
            }
            
        } catch (PDOException $e) {
            $error = 'Errore nel database: ' . $e->getMessage();
        }
    }
}

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stampa etichette - KITSDB</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@400;700&family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <style>
        .filters-section {
            background: var(--surface);
            padding: 2rem;
            border-radius: 0.75rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }
        
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            color: var(--primary-text);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .form-group input,
        .form-group select {
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 0.375rem;
            background: var(--background);
            color: var(--primary-text);
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--highlight-yellow);
            box-shadow: 0 0 0 2px rgba(220, 247, 99, 0.2);
        }
        
        .buttons-group {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.375rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .btn-primary {
            background: var(--action-red);
            color: white;
        }
        
        .btn-primary:hover {
            background: #c13643;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: var(--highlight-yellow);
            color: var(--background);
        }
        
        .btn-secondary:hover {
            background: #c9e356;
            transform: translateY(-2px);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 0.375rem;
            margin-bottom: 1rem;
        }
        
        .alert-error {
            background: rgba(222, 60, 75, 0.1);
            border: 1px solid var(--action-red);
            color: var(--action-red);
        }
        
        .alert-success {
            background: rgba(220, 247, 99, 0.1);
            border: 1px solid var(--highlight-yellow);
            color: var(--highlight-yellow);
        }
        
        /* Print styles */
        @media print {
            body * {
                visibility: hidden;
            }
            
            .labels-container,
            .labels-container * {
                visibility: visible;
            }
            
            .labels-container {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                z-index: 9999;
            }
            
            .filters-section,
            .page-header,
            .alert,
            header,
            .admin-header,
            nav {
                display: none !important;
            }
            
            @page {
                size: A4 portrait;
                margin: 0 !important;
            }
            
            html, body {
                margin: 0 !important;
                padding: 0 !important;
                background: white !important;
            }
            
            /* Ensure container doesn't interfere */
            .container {
                all: unset !important;
                display: block !important;
            }
        }
        
        /* Labels layout */
        .labels-container {
            background: white;
            margin: 2rem 0;
        }
        
        .labels-page {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto 2rem;
            background: white;
            padding: 0;
            box-sizing: border-box;
            display: grid;
            grid-template-columns: repeat(3, 70mm);
            grid-template-rows: repeat(8, 36mm);
            gap: 0;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .label {
            width: 70mm;
            height: 36mm;
            border: 1px solid #ddd;
            display: flex;
            align-items: center;
            padding: 2mm;
            box-sizing: border-box;
            background: white;
            color: black;
        }
        
        .label-empty {
            border: 1px dashed #ccc;
            background: #f9f9f9;
        }
        
        .qr-code {
            width: 18mm;
            height: 18mm;
            margin-right: 3mm;
            flex-shrink: 0;
        }
        
        .label-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            font-family: Arial, sans-serif;
            line-height: 1.2;
            margin-right: 15mm; /* 15mm spazio bianco a destra */
        }
        
        .team-name {
            font-family: 'Barlow Condensed', Arial, sans-serif;
            font-weight: bold;
            font-size: 16px; /* Metà di 33px */
            margin-bottom: 5px; /* 4-6px di spazio per evitare taglio lettere */
            word-wrap: break-word;
            overflow: hidden;
            line-height: 0.9;
            white-space: nowrap;
            text-overflow: ellipsis;
        }
        
        .jersey-type {
            font-family: 'Montserrat', Arial, sans-serif;
            font-weight: bold;
            font-size: 9px;
            color: #666;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-bottom: 1mm;
        }
        
        .nation-season {
            font-family: 'Montserrat', Arial, sans-serif;
            font-weight: bold;
            font-size: 9px;
            color: #666;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-bottom: 1mm;
        }
        
        .kit-id {
            font-family: 'Montserrat', Arial, sans-serif;
            font-weight: bold;
            font-size: 8px;
            color: #333;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        @media print {
            .labels-container {
                margin: 0 !important;
                padding: 0 !important;
            }
            
            .labels-page {
                box-shadow: none;
                margin: 0 !important;
                padding: 0 !important;
                width: 210mm !important;
                height: 297mm !important;
                break-inside: avoid;
                page-break-after: always;
            }
            
            .labels-page:last-child {
                page-break-after: auto;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/admin_header.php'; ?>

    <div class="container">
        <div class="page-header">
            <h1>Stampa etichette</h1>
            <p>Genera e stampa etichette adesive 70×36 mm per kit da calcio. Le etichette sono organizzate in una griglia 3×8 su foglio A4 (24 etichette per pagina) con QR code a sinistra e informazioni del kit a destra.</p>
            <p><strong>Modalità di selezione:</strong> Utilizza intervalli (Da/A ID), ID specifici con virgole e trattini (es: 1,5,76,81-85), o una combinazione di entrambi.</p>
            <p><strong>Posizioni griglia:</strong> Le etichette sono numerate da 1 a 24 in una griglia 3×8. Posizione 1 = prima riga prima colonna, posizione 4 = seconda riga prima colonna, posizione 8 = terza riga seconda colonna.</p>
        </div>

        <!-- Filters Section -->
        <div class="filters-section">
            <h2>Filtri e opzioni</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="filters-grid">
                    <div class="form-group">
                        <label for="from_id">Da ID:</label>
                        <input type="number" id="from_id" name="from_id" min="1" 
                               value="<?php echo isset($_POST['from_id']) ? htmlspecialchars($_POST['from_id']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="to_id">A ID:</label>
                        <input type="number" id="to_id" name="to_id" min="1" 
                               value="<?php echo isset($_POST['to_id']) ? htmlspecialchars($_POST['to_id']) : ''; ?>">
                    </div>
                    
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label for="specific_ids">ID specifici (es: 1,5,76,81-85):</label>
                        <input type="text" id="specific_ids" name="specific_ids" 
                               placeholder="Esempio: 1,5,76,81-85"
                               value="<?php echo isset($_POST['specific_ids']) ? htmlspecialchars($_POST['specific_ids']) : ''; ?>">
                        <small style="color: var(--secondary-text); font-size: 0.85rem; margin-top: 0.25rem; display: block;">
                            Inserire ID singoli separati da virgole e/o intervalli con trattino. Se specificato, ignora "Da ID" e "A ID".
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label for="sort_by">Ordinamento:</label>
                        <select id="sort_by" name="sort_by">
                            <option value="id_asc" <?php echo (isset($_POST['sort_by']) && $_POST['sort_by'] === 'id_asc') ? 'selected' : ''; ?>>ID crescente</option>
                            <option value="id_desc" <?php echo (isset($_POST['sort_by']) && $_POST['sort_by'] === 'id_desc') ? 'selected' : ''; ?>>ID decrescente</option>
                            <option value="created_at_asc" <?php echo (isset($_POST['sort_by']) && $_POST['sort_by'] === 'created_at_asc') ? 'selected' : ''; ?>>Data inserimento crescente</option>
                            <option value="created_at_desc" <?php echo (!isset($_POST['sort_by']) || $_POST['sort_by'] === 'created_at_desc') ? 'selected' : ''; ?>>Data inserimento decrescente</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="preview_limit">Anteprima quantità:</label>
                        <input type="number" id="preview_limit" name="preview_limit" min="1" max="1000" 
                               value="<?php echo isset($_POST['preview_limit']) ? htmlspecialchars($_POST['preview_limit']) : '24'; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="start_position">Stampa da posizione:</label>
                        <input type="number" id="start_position" name="start_position" min="1" max="24" 
                               value="<?php echo isset($_POST['start_position']) ? htmlspecialchars($_POST['start_position']) : '1'; ?>">
                        <small style="color: var(--secondary-text); font-size: 0.85rem; margin-top: 0.25rem; display: block;">
                            Posizione nella griglia 3×8 (1=prima etichetta in alto a sinistra, 8=terza fila seconda etichetta)
                        </small>
                    </div>
                </div>
                
                <div class="buttons-group">
                    <button type="submit" class="btn btn-primary">Genera anteprima</button>
                    <?php if (!empty($labels)): ?>
                        <button type="button" class="btn btn-secondary" onclick="window.print()">Stampa</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Labels Preview -->
        <?php if (!empty($labels)): ?>
            <div class="labels-container">
                <?php
                $labels_per_page = 24;
                $start_pos = isset($_POST['start_position']) ? (int)$_POST['start_position'] - 1 : 0; // Convert to 0-based index
                
                // Calculate total positions needed (start position + labels)
                $total_positions = $start_pos + count($labels);
                $total_pages = ceil($total_positions / $labels_per_page);
                
                $label_index = 0; // Index for actual labels array
                
                for ($page = 0; $page < $total_pages; $page++):
                ?>
                    <div class="labels-page">
                        <?php 
                        // Fill the page with labels (up to 24 positions)
                        for ($i = 0; $i < $labels_per_page; $i++):
                            $global_position = $page * $labels_per_page + $i;
                            
                            // Check if this position should have a label
                            if ($global_position >= $start_pos && $label_index < count($labels)):
                                $label = $labels[$label_index];
                                $label_index++;
                                $qr_image_url = generateKitQRCode($label['kit_id'], getBaseURL(), 80); // QR più piccolo
                                
                                // Tronca il nome della squadra se troppo lungo
                                $team_name = $label['team_name'];
                                if (strlen($team_name) > 25) { // Limite caratteri approssimativo
                                    $team_name = substr($team_name, 0, 24) . '.';
                                }
                                
                                // Format display: FIFA code and season
                                $fifa_season = '';
                                if (!empty($label['fifa_code'])) {
                                    $fifa_season .= '(' . $label['fifa_code'] . ')';
                                }
                                if (!empty($label['season'])) {
                                    if (!empty($fifa_season)) {
                                        $fifa_season .= ' | ' . $label['season'];
                                    } else {
                                        $fifa_season = $label['season'];
                                    }
                                }
                                if (empty($fifa_season)) {
                                    $fifa_season = 'N/D';
                                }
                        ?>
                            <div class="label">
                                <div class="qr-code">
                                    <img src="<?php echo htmlspecialchars($qr_image_url); ?>" alt="QR" style="width: 100%; height: 100%; object-fit: contain;">
                                </div>
                                <div class="label-content">
                                    <div class="team-name"><?php echo htmlspecialchars($team_name); ?></div>
                                    <div class="jersey-type"><?php echo htmlspecialchars($label['jersey_type_name'] ?? 'N/A'); ?></div>
                                    <div class="nation-season"><?php echo htmlspecialchars($fifa_season); ?></div>
                                    <div class="kit-id">ID: <?php echo htmlspecialchars($label['kit_id']); ?></div>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- Empty position (before start or after end) -->
                            <div class="label label-empty"></div>
                        <?php 
                            endif;
                        endfor; 
                        ?>
                    </div>
                <?php endfor; ?>
            </div>

        <?php endif; ?>
    </div>
</body>
</html>
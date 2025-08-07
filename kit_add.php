<?php
require_once 'auth.php';
require_once 'config.php';

requireAdmin();

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = getDb();
        
        // Validazione base
        $team_id = intval($_POST['team_id'] ?? 0);
        $season = trim($_POST['season'] ?? '');
        $number = intval($_POST['number'] ?? 0);
        $player_name = trim($_POST['player_name'] ?? '');
        $brand_id = intval($_POST['brand_id'] ?? 0) ?: null;
        $size_id = intval($_POST['size_id'] ?? 0) ?: null;
        $sleeves = $_POST['sleeves'] ?? 'Short';
        $condition_id = intval($_POST['condition_id'] ?? 0) ?: null;
        $jersey_type_id = intval($_POST['jersey_type_id'] ?? 0) ?: null;
        $category_id = intval($_POST['category_id'] ?? 0) ?: null;
        $color1_id = intval($_POST['color1_id'] ?? 0) ?: null;
        $color2_id = intval($_POST['color2_id'] ?? 0) ?: null;
        $color3_id = intval($_POST['color3_id'] ?? 0) ?: null;
        $notes = trim($_POST['notes'] ?? '');
        
        if ($team_id <= 0 || empty($season) || $number < 0) {
            throw new Exception('Campi obbligatori mancanti o non validi.');
        }
        
        // Inserimento kit
        $stmt = $db->prepare("
            INSERT INTO kits (team_id, season, number, player_name, brand_id, size_id, 
                            sleeves, condition_id, jersey_type_id, category_id, 
                            color1_id, color2_id, color3_id, notes, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $team_id, $season, $number, $player_name, $brand_id, $size_id,
            $sleeves, $condition_id, $jersey_type_id, $category_id,
            $color1_id, $color2_id, $color3_id, $notes
        ]);
        
        $kit_id = $db->lastInsertId();
        
        // Gestione upload foto
        if (!empty($_FILES['photos']['name'][0])) {
            $uploadDir = __DIR__ . '/upload_tmp/';
            
            for ($i = 0; $i < count($_FILES['photos']['name']); $i++) {
                if ($_FILES['photos']['error'][$i] === UPLOAD_ERR_OK) {
                    $tmpName = $_FILES['photos']['tmp_name'][$i];
                    $originalName = $_FILES['photos']['name'][$i];
                    $photoTitle = trim($_POST['photo_titles'][$i] ?? '');
                    $classificationId = intval($_POST['photo_classifications'][$i] ?? 0) ?: null;
                    
                    // Genera nome file sicuro
                    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
                    $safeFilename = 'kit_' . $kit_id . '_' . time() . '_' . $i . '.' . strtolower($extension);
                    
                    // Sposta in upload_tmp prima
                    $tempPath = $uploadDir . $safeFilename;
                    
                    if (move_uploaded_file($tmpName, $tempPath)) {
                        // Determina destinazione finale basata sul tipo
                        $photoType = $_POST['photo_types'][$i] ?? 'extra';
                        $finalDir = __DIR__ . '/uploads/' . $photoType . '/';
                        $finalPath = $finalDir . $safeFilename;
                        
                        // Crea directory se non esistente
                        if (!file_exists($finalDir)) {
                            mkdir($finalDir, 0755, true);
                        }
                        
                        // Sposta alla destinazione finale
                        if (rename($tempPath, $finalPath)) {
                            // Salva nel database
                            $photoStmt = $db->prepare("
                                INSERT INTO photos (kit_id, filename, title, classification_id, uploaded_at) 
                                VALUES (?, ?, ?, ?, NOW())
                            ");
                            $photoStmt->execute([$kit_id, $safeFilename, $photoTitle, $classificationId]);
                        }
                    }
                }
            }
        }
        
        $success = "Maglia aggiunta con successo! ID: $kit_id";
        
        // Reset form dopo successo
        if ($success) {
            header("Location: kits_list.php");
            exit();
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aggiungi Maglia - KITSDB</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--space-md);
            margin-bottom: var(--space-lg);
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        .inline-group {
            display: flex;
            gap: var(--space-sm);
        }
        
        .inline-group .form-group {
            flex: 1;
        }
        
        .inline-group .form-group.small {
            flex: 0 0 100px;
        }
        
        .photo-upload-section {
            background: var(--surface);
            padding: var(--space-lg);
            border-radius: 0.5rem;
            margin-top: var(--space-lg);
        }
        
        .uploaded-photos {
            display: flex;
            flex-wrap: wrap;
            gap: var(--space-md);
            margin-top: var(--space-md);
        }
        
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .inline-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <a href="dashboard.php" class="logo">KITSDB</a>
            <nav class="nav-menu">
                <a href="kits_list.php" class="nav-link">Lista Maglie</a>
                <a href="kit_add.php" class="nav-link" style="color: var(--highlight-yellow);">Aggiungi Maglia</a>
                <form method="POST" action="logout.php" style="display: inline;">
                    <button type="submit" class="logout-btn">Logout</button>
                </form>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <div class="container">
        <h1>Aggiungi Nuova Maglia</h1>
        
        <?php if ($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" id="kitForm">
            <div class="form-grid">
                <!-- Squadra con autocomplete -->
                <div class="form-group">
                    <label for="team_search">Squadra *</label>
                    <div class="autocomplete-container">
                        <input type="text" id="team_search" placeholder="Cerca squadra..." required>
                        <div class="autocomplete-suggestions" id="team_suggestions" style="display: none;"></div>
                    </div>
                    <input type="hidden" name="team_id" id="team_id" required>
                </div>

                <!-- Stagione -->
                <div class="form-group">
                    <label for="season">Stagione *</label>
                    <input type="text" id="season" name="season" placeholder="es. 2023-24" required>
                </div>

                <!-- Numero e Giocatore -->
                <div class="form-group">
                    <div class="inline-group">
                        <div class="form-group small">
                            <label for="number">Numero *</label>
                            <input type="number" id="number" name="number" min="0" max="99" required>
                        </div>
                        <div class="form-group">
                            <label for="player_name">Nome Giocatore</label>
                            <input type="text" id="player_name" name="player_name" placeholder="Nome del giocatore">
                        </div>
                    </div>
                </div>

                <!-- Maniche -->
                <div class="form-group">
                    <label>Maniche</label>
                    <div class="size-selector">
                        <button type="button" class="size-btn" data-value="Short">Corte</button>
                        <button type="button" class="size-btn" data-value="Long">Lunghe</button>
                    </div>
                    <input type="hidden" name="sleeves" id="sleeves" value="Short">
                </div>

                <!-- Brand -->
                <div class="form-group">
                    <label for="brand_id">Brand</label>
                    <select name="brand_id" id="brand_id">
                        <option value="">Seleziona brand...</option>
                    </select>
                </div>

                <!-- Categoria -->
                <div class="form-group">
                    <label for="category_id">Categoria</label>
                    <select name="category_id" id="category_id">
                        <option value="">Seleziona categoria...</option>
                    </select>
                </div>

                <!-- Tipo Maglia -->
                <div class="form-group">
                    <label for="jersey_type_id">Tipo Maglia</label>
                    <select name="jersey_type_id" id="jersey_type_id">
                        <option value="">Seleziona tipo...</option>
                    </select>
                </div>

                <!-- Condizione -->
                <div class="form-group">
                    <label for="condition_id">Condizione</label>
                    <select name="condition_id" id="condition_id">
                        <option value="">Seleziona condizione...</option>
                    </select>
                </div>

                <!-- Taglie -->
                <div class="form-group full-width">
                    <label>Taglia</label>
                    <div class="size-selector" id="size-selector">
                        <!-- Caricate via JS -->
                    </div>
                    <input type="hidden" name="size_id" id="size_id">
                </div>

                <!-- Colori -->
                <div class="form-group">
                    <label for="color1_id">Colore Primario</label>
                    <select name="color1_id" id="color1_id">
                        <option value="">Seleziona colore...</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="color2_id">Colore Secondario</label>
                    <select name="color2_id" id="color2_id">
                        <option value="">Seleziona colore...</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="color3_id">Colore Terziario</label>
                    <select name="color3_id" id="color3_id">
                        <option value="">Seleziona colore...</option>
                    </select>
                </div>

                <!-- Note -->
                <div class="form-group full-width">
                    <label for="notes">Note</label>
                    <textarea id="notes" name="notes" rows="3" placeholder="Note aggiuntive..."></textarea>
                </div>
            </div>

            <!-- Upload Foto -->
            <div class="photo-upload-section">
                <h3>Carica Foto</h3>
                <div class="upload-area" id="upload-area">
                    <div class="upload-icon">📷</div>
                    <div class="upload-text">
                        Trascina le foto qui o clicca per selezionare<br>
                        <small>Formati supportati: JPG, PNG, GIF (max 5MB)</small>
                    </div>
                    <input type="file" id="photo-input" name="photos[]" multiple accept="image/*" style="display: none;">
                </div>
                
                <div class="uploaded-photos" id="uploaded-photos">
                    <!-- File caricati mostrati qui -->
                </div>
            </div>

            <div style="text-align: center; margin-top: var(--space-lg);">
                <button type="submit" class="btn btn-primary" style="padding: 1rem 2rem;">Salva Maglia</button>
                <a href="kits_list.php" class="btn btn-secondary" style="padding: 1rem 2rem; margin-left: 1rem;">Annulla</a>
            </div>
        </form>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Carica dati lookup
        loadLookupData();
        setupAutocomplete();
        setupFileUpload();
        setupSizeSelectors();
    });

    function loadLookupData() {
        const lookupTypes = ['brands', 'categories', 'jersey_types', 'conditions', 'sizes', 'colors'];
        
        lookupTypes.forEach(type => {
            fetch(`api/lookup.php?type=${type}`)
                .then(response => response.json())
                .then(data => {
                    if (type === 'sizes') {
                        populateSizes(data);
                    } else if (type === 'conditions') {
                        populateConditions(data);
                    } else {
                        populateSelect(type, data);
                    }
                })
                .catch(console.error);
        });
    }

    function populateSelect(type, data) {
        const selectMap = {
            'brands': 'brand_id',
            'categories': 'category_id', 
            'jersey_types': 'jersey_type_id',
            'colors': 'color1_id,color2_id,color3_id'
        };
        
        const selectIds = selectMap[type].split(',');
        
        selectIds.forEach(selectId => {
            const select = document.getElementById(selectId);
            if (select && Array.isArray(data)) {
                data.forEach(item => {
                    const option = document.createElement('option');
                    option.value = item.id;
                    option.textContent = item.name;
                    select.appendChild(option);
                });
            }
        });
    }

    function populateSizes(data) {
        const container = document.getElementById('size-selector');
        container.innerHTML = '';
        
        data.forEach(size => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'size-btn';
            btn.textContent = size.name;
            btn.dataset.value = size.id;
            
            btn.addEventListener('click', function() {
                container.querySelectorAll('.size-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                document.getElementById('size_id').value = this.dataset.value;
            });
            
            container.appendChild(btn);
        });
    }

    function populateConditions(data) {
        const select = document.getElementById('condition_id');
        data.forEach(condition => {
            const option = document.createElement('option');
            option.value = condition.id;
            option.textContent = condition.name + ' (' + '⭐'.repeat(condition.stars) + ')';
            select.appendChild(option);
        });
    }

    function setupSizeSelectors() {
        // Maniche selector
        document.querySelectorAll('.size-selector .size-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const container = this.parentElement;
                container.querySelectorAll('.size-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                const hiddenInput = container.parentElement.querySelector('input[type="hidden"]');
                if (hiddenInput) {
                    hiddenInput.value = this.dataset.value;
                }
            });
        });
        
        // Set default per maniche
        document.querySelector('.size-btn[data-value="Short"]').classList.add('active');
    }

    function setupAutocomplete() {
        const teamInput = document.getElementById('team_search');
        const teamSuggestions = document.getElementById('team_suggestions');
        const teamIdInput = document.getElementById('team_id');
        
        let debounceTimer;
        
        teamInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            const query = this.value.trim();
            
            if (query.length < 2) {
                teamSuggestions.style.display = 'none';
                return;
            }
            
            debounceTimer = setTimeout(() => {
                fetch(`api/autocomplete.php?type=teams&q=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        teamSuggestions.innerHTML = '';
                        
                        if (Array.isArray(data) && data.length > 0) {
                            data.forEach(team => {
                                const item = document.createElement('div');
                                item.className = 'autocomplete-item';
                                item.innerHTML = `
                                    <strong>${team.name}</strong>
                                    ${team.nation ? `<br><small>${team.nation}</small>` : ''}
                                `;
                                
                                item.addEventListener('click', function() {
                                    teamInput.value = team.name;
                                    teamIdInput.value = team.id;
                                    teamSuggestions.style.display = 'none';
                                });
                                
                                teamSuggestions.appendChild(item);
                            });
                            teamSuggestions.style.display = 'block';
                        } else {
                            teamSuggestions.style.display = 'none';
                        }
                    })
                    .catch(console.error);
            }, 300);
        });
        
        // Nascondi suggerimenti quando si clicca fuori
        document.addEventListener('click', function(e) {
            if (!teamInput.contains(e.target) && !teamSuggestions.contains(e.target)) {
                teamSuggestions.style.display = 'none';
            }
        });
    }

    function setupFileUpload() {
        const uploadArea = document.getElementById('upload-area');
        const fileInput = document.getElementById('photo-input');
        const uploadedPhotos = document.getElementById('uploaded-photos');
        
        // Click to select
        uploadArea.addEventListener('click', () => fileInput.click());
        
        // Drag & drop
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            handleFiles(e.dataTransfer.files);
        });
        
        fileInput.addEventListener('change', (e) => {
            handleFiles(e.target.files);
        });
    }

    function handleFiles(files) {
        const uploadedPhotos = document.getElementById('uploaded-photos');
        
        Array.from(files).forEach((file, index) => {
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const fileItem = createFilePreview(file, e.target.result, index);
                    uploadedPhotos.appendChild(fileItem);
                };
                reader.readAsDataURL(file);
            }
        });
    }

    function createFilePreview(file, src, index) {
        const fileItem = document.createElement('div');
        fileItem.className = 'file-item';
        
        fileItem.innerHTML = `
            <img src="${src}" alt="${file.name}" class="file-thumbnail">
            <div class="file-info">
                <input type="text" 
                       name="photo_titles[]" 
                       placeholder="Nome foto..." 
                       class="file-name-input">
                <select name="photo_types[]" class="file-name-input">
                    <option value="front">Fronte</option>
                    <option value="back">Retro</option>
                    <option value="extra">Extra</option>
                </select>
                <select name="photo_classifications[]" class="file-name-input">
                    <option value="1">Match</option>
                    <option value="2">Web</option>
                    <option value="3">Store</option>
                    <option value="4">Altro</option>
                </select>
                <button type="button" onclick="this.parentElement.parentElement.remove()" 
                        style="background: var(--action-red); color: white; border: none; padding: 0.25rem 0.5rem; border-radius: 0.25rem; cursor: pointer;">
                    Rimuovi
                </button>
            </div>
        `;
        
        return fileItem;
    }
    </script>
</body>
</html>
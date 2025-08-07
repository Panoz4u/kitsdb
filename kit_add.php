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
        $number = !empty($_POST['number']) ? intval($_POST['number']) : null;
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
        
        if ($team_id <= 0) {
            throw new Exception('Team is required.');
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
                <!-- Team with autocomplete -->
                <div class="form-group">
                    <label for="team_search">Team *</label>
                    <div class="autocomplete-container">
                        <input type="text" id="team_search" placeholder="Search team..." required>
                        <div class="autocomplete-suggestions" id="team_suggestions" style="display: none;"></div>
                    </div>
                    <input type="hidden" name="team_id" id="team_id" required>
                </div>

                <!-- Season -->
                <div class="form-group">
                    <label for="season">Season</label>
                    <select name="season" id="season">
                        <option value="" style="opacity: 0.6;">Select season...</option>
                    </select>
                </div>

                <!-- Number and Player -->
                <div class="form-group">
                    <div class="inline-group">
                        <div class="form-group small">
                            <label for="number">Number</label>
                            <input type="number" id="number" name="number" min="0" max="99" placeholder="">
                        </div>
                        <div class="form-group">
                            <label for="player_name">Player Name</label>
                            <input type="text" id="player_name" name="player_name" placeholder="Player name">
                        </div>
                    </div>
                </div>

                <!-- Sleeves -->
                <div class="form-group">
                    <label>Sleeves</label>
                    <div class="size-selector">
                        <button type="button" class="size-btn" data-value="Short">Short</button>
                        <button type="button" class="size-btn" data-value="Long">Long</button>
                    </div>
                    <input type="hidden" name="sleeves" id="sleeves" value="Short">
                </div>

                <!-- Brand -->
                <div class="form-group">
                    <label for="brand_id">Brand</label>
                    <select name="brand_id" id="brand_id">
                        <option value="">Select brand...</option>
                    </select>
                </div>

                <!-- Category -->
                <div class="form-group">
                    <label for="category_id">Category</label>
                    <select name="category_id" id="category_id">
                        <option value="">Select category...</option>
                    </select>
                </div>

                <!-- Jersey Type -->
                <div class="form-group">
                    <label for="jersey_type_id">Jersey Type</label>
                    <select name="jersey_type_id" id="jersey_type_id">
                        <option value="">Select type...</option>
                    </select>
                </div>

                <!-- Condition -->
                <div class="form-group">
                    <label for="condition_id">Condition</label>
                    <select name="condition_id" id="condition_id">
                        <option value="">Select condition...</option>
                    </select>
                </div>

                <!-- Sizes -->
                <div class="form-group full-width">
                    <label>Size</label>
                    <div class="size-selector" id="size-selector">
                        <!-- Loaded via JS -->
                    </div>
                    <input type="hidden" name="size_id" id="size_id">
                </div>

                <!-- Colors -->
                <div class="form-group">
                    <label for="color1_id">Primary Color</label>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <select name="color1_id" id="color1_id" style="flex: 1;">
                            <option value="">Select color...</option>
                        </select>
                        <div class="color-swatch" id="color1_swatch" style="width: 30px; height: 30px; border: 2px solid var(--border-color); border-radius: 4px; background: #333;"></div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="color2_id">Secondary Color</label>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <select name="color2_id" id="color2_id" style="flex: 1;">
                            <option value="">Select color...</option>
                        </select>
                        <div class="color-swatch" id="color2_swatch" style="width: 30px; height: 30px; border: 2px solid var(--border-color); border-radius: 4px; background: #fff;"></div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="color3_id">Tertiary Color</label>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <select name="color3_id" id="color3_id" style="flex: 1;">
                            <option value="">Select color...</option>
                        </select>
                        <div class="color-swatch" id="color3_swatch" style="width: 30px; height: 30px; border: 2px solid var(--border-color); border-radius: 4px; background: transparent;"></div>
                    </div>
                </div>

                <!-- Notes -->
                <div class="form-group full-width">
                    <label for="notes">Notes</label>
                    <textarea id="notes" name="notes" rows="3" placeholder="Additional notes..."></textarea>
                </div>
            </div>

            <!-- Preview SVG Live -->
            <div class="svg-preview-container">
                <h3>Jersey Preview</h3>
                <div id="live-preview">
                    <svg width="200" height="200" viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <filter id="shadow" x="-20%" y="-20%" width="140%" height="140%">
                                <feDropShadow dx="4" dy="4" stdDeviation="6" flood-opacity="0.3"/>
                            </filter>
                        </defs>
                        
                        <!-- Jersey body (primary color - white area from new SVG) -->
                        <path id="jerseyBody" d="
                            M 145 372 
                            C 209 370 274 280 278 225 
                            C 320 170 408 120 512 120
                            C 616 120 704 170 746 225
                            C 750 280 815 370 879 372
                            C 944 414 990 462 990 514
                            L 990 736
                            C 990 808 934 864 862 864
                            L 162 864
                            C 90 864 34 808 34 736
                            L 34 514
                            C 34 462 80 414 145 372
                            Z
                            
                            M 178 564
                            C 178 564 134 588 118 596
                            C 106 602 98 616 98 632
                            L 98 728
                            C 98 770 132 804 174 804
                            L 306 804
                            L 306 580
                            C 306 556 326 536 350 536
                            L 674 536
                            C 698 536 718 556 718 580
                            L 718 804
                            L 850 804
                            C 892 804 926 770 926 728
                            L 926 632
                            C 926 616 918 602 906 596
                            C 890 588 846 564 846 564
                            C 782 596 678 620 512 620
                            C 346 620 242 596 178 564
                            Z
                        " fill="#ffffff" fill-rule="evenodd" filter="url(#shadow)"/>
                        
                        <!-- V-neck cutout (transparent) -->
                        <path d="
                            M 378 224
                            L 512 356
                            L 646 224
                            C 622 240 584 256 512 256
                            C 440 256 402 240 378 224
                            Z
                        " fill="none"/>
                        
                        <!-- Secondary color stripes/details -->
                        <rect x="340" y="400" width="344" height="200" id="numberArea" 
                              fill="#000000" rx="20" opacity="0.9"/>
                        
                        <!-- Player name -->
                        <text x="512" y="450" id="playerName" font-family="Arial, sans-serif" font-size="36" 
                              font-weight="bold" text-anchor="middle" fill="#ffffff">
                        </text>
                        
                        <!-- Number -->
                        <text x="512" y="530" id="shirtNumber" font-family="Arial, sans-serif" font-size="80" 
                              font-weight="bold" text-anchor="middle" fill="#ffffff">
                        </text>
                    </svg>
                </div>
            </div>

            <!-- Upload Photos -->
            <div class="photo-upload-section">
                <h3>Upload Photos</h3>
                <div class="upload-area" id="upload-area">
                    <div class="upload-icon">📷</div>
                    <div class="upload-text">
                        Drag photos here or click to select<br>
                        <small>Supported formats: JPG, PNG, GIF (max 5MB)</small>
                    </div>
                    <input type="file" id="photo-input" name="photos[]" multiple accept="image/*" style="display: none;">
                </div>
                
                <div class="uploaded-photos" id="uploaded-photos">
                    <!-- Uploaded files shown here -->
                </div>
            </div>

            <div style="text-align: center; margin-top: var(--space-lg);">
                <button type="submit" class="btn btn-primary" style="padding: 1rem 2rem;">Save Jersey</button>
                <a href="kits_list.php" class="btn btn-secondary" style="padding: 1rem 2rem; margin-left: 1rem;">Cancel</a>
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
        setupLivePreview();
    });

    function loadLookupData() {
        const lookupTypes = ['brands', 'categories', 'jersey_types', 'conditions', 'sizes', 'colors', 'seasons'];
        
        lookupTypes.forEach(type => {
            fetch(`api/lookup.php?type=${type}`)
                .then(response => response.json())
                .then(data => {
                    if (type === 'sizes') {
                        populateSizes(data);
                    } else if (type === 'conditions') {
                        populateConditions(data);
                    } else if (type === 'jersey_types') {
                        populateJerseyTypes(data);
                    } else if (type === 'colors') {
                        populateColors(data);
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
            'seasons': 'season'
        };
        
        const selectIds = selectMap[type].split(',');
        
        selectIds.forEach(selectId => {
            const select = document.getElementById(selectId);
            if (select && Array.isArray(data)) {
                data.forEach(item => {
                    const option = document.createElement('option');
                    option.value = type === 'seasons' ? item.name : item.id;
                    option.textContent = item.name;
                    select.appendChild(option);
                });
            }
        });
    }

    function populateJerseyTypes(data) {
        const select = document.getElementById('jersey_type_id');
        
        // Sort by jersey_type_id to ensure proper database order
        const sortedData = [...data].sort((a, b) => {
            return parseInt(a.id) - parseInt(b.id);
        });
        
        sortedData.forEach(item => {
            const option = document.createElement('option');
            option.value = item.id;
            option.textContent = item.name;
            select.appendChild(option);
        });
    }

    function getContrastColor(backgroundColor) {
        if (!backgroundColor) return '#000000';
        
        // Convert hex to RGB
        const hex = backgroundColor.replace('#', '');
        const r = parseInt(hex.substr(0, 2), 16);
        const g = parseInt(hex.substr(2, 2), 16);
        const b = parseInt(hex.substr(4, 2), 16);
        
        // Calculate luminance
        const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
        
        return luminance > 0.5 ? '#000000' : '#ffffff';
    }

    function populateColors(data) {
        const colorSelects = ['color1_id', 'color2_id', 'color3_id'];
        
        colorSelects.forEach(selectId => {
            const select = document.getElementById(selectId);
            
            data.forEach(color => {
                const option = document.createElement('option');
                option.value = color.id;
                option.textContent = `■ ${color.name}`;
                option.dataset.hex = color.hex;
                option.style.setProperty('--color-hex', color.hex);
                option.style.color = color.hex;
                select.appendChild(option);
            });
            
            // Add change listener to update color swatch
            select.addEventListener('change', function() {
                const swatchId = selectId.replace('_id', '_swatch');
                const swatch = document.getElementById(swatchId);
                const selectedOption = this.options[this.selectedIndex];
                
                if (selectedOption.dataset.hex) {
                    swatch.style.background = selectedOption.dataset.hex;
                } else {
                    swatch.style.background = 'transparent';
                }
            });
        });
    }

    function populateSizes(data) {
        const container = document.getElementById('size-selector');
        container.innerHTML = '';
        
        // Custom order with YTH before XS
        const customOrder = ['YTH', 'XS', 'S', 'M', 'L', 'XL', 'XXL'];
        const sortedData = data.sort((a, b) => {
            const aIndex = customOrder.indexOf(a.name);
            const bIndex = customOrder.indexOf(b.name);
            if (aIndex !== -1 && bIndex !== -1) return aIndex - bIndex;
            if (aIndex !== -1) return -1;
            if (bIndex !== -1) return 1;
            return a.name.localeCompare(b.name);
        });
        
        sortedData.forEach(size => {
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

    let selectedFiles = [];

    function handleFiles(files) {
        const uploadedPhotos = document.getElementById('uploaded-photos');
        const fileInput = document.getElementById('photo-input');
        
        // Add new files to our array
        Array.from(files).forEach((file) => {
            if (file.type.startsWith('image/')) {
                selectedFiles.push(file);
            }
        });
        
        // Update the file input with all selected files
        const dataTransfer = new DataTransfer();
        selectedFiles.forEach(file => {
            dataTransfer.items.add(file);
        });
        fileInput.files = dataTransfer.files;
        
        // Update preview
        updateFilePreview();
    }
    
    function updateFilePreview() {
        const uploadedPhotos = document.getElementById('uploaded-photos');
        uploadedPhotos.innerHTML = '';
        
        selectedFiles.forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const fileItem = createFilePreview(file, e.target.result, index);
                uploadedPhotos.appendChild(fileItem);
            };
            reader.readAsDataURL(file);
        });
    }

    function createFilePreview(file, src, index) {
        const fileItem = document.createElement('div');
        fileItem.className = 'file-item';
        fileItem.dataset.filename = file.name;
        fileItem.dataset.fileIndex = index;
        
        fileItem.innerHTML = `
            <div style="position: relative;">
                <img src="${src}" alt="${file.name}" class="file-thumbnail">
                <button type="button" onclick="removeFileItem(this)" 
                        style="position: absolute; top: 5px; right: 5px; background: var(--action-red); color: white; border: none; 
                               width: 24px; height: 24px; border-radius: 50%; cursor: pointer; font-size: 14px; font-weight: bold;
                               display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 4px rgba(0,0,0,0.3);">
                    ✕
                </button>
            </div>
            <div class="file-info">
                <input type="text" 
                       name="photo_titles[]" 
                       placeholder="Photo name..." 
                       class="file-name-input">
                <select name="photo_types[]" class="file-name-input">
                    <option value="front">Front</option>
                    <option value="back">Back</option>
                    <option value="extra">Extra</option>
                </select>
                <select name="photo_classifications[]" class="file-name-input">
                    <option value="1">Match</option>
                    <option value="2">Web</option>
                    <option value="3">Store</option>
                    <option value="4">Other</option>
                </select>
            </div>
        `;
        
        return fileItem;
    }

    function removeFileItem(button) {
        const fileItem = button.closest('.file-item');
        const fileIndex = parseInt(fileItem.dataset.fileIndex);
        
        // Remove the file from our array
        selectedFiles.splice(fileIndex, 1);
        
        // Update the file input
        const fileInput = document.getElementById('photo-input');
        const dataTransfer = new DataTransfer();
        selectedFiles.forEach(file => {
            dataTransfer.items.add(file);
        });
        fileInput.files = dataTransfer.files;
        
        // Update the preview
        updateFilePreview();
    }

    function setupLivePreview() {
        // Form elements that affect preview
        const numberInput = document.getElementById('number');
        const playerInput = document.getElementById('player_name');
        const color1Select = document.getElementById('color1_id');
        const color2Select = document.getElementById('color2_id');
        const color3Select = document.getElementById('color3_id');
        
        // Function to update preview
        function updatePreview() {
            const number = numberInput.value || '';
            let playerName = playerInput.value.toUpperCase();
            
            // Truncate player name if longer than 7 characters
            if (playerName.length > 7) {
                playerName = playerName.substring(0, 7) + '.';
            }
            
            // Update number
            document.getElementById('shirtNumber').textContent = number;
            
            // Update player name (above number)
            const playerNameElement = document.getElementById('playerName');
            playerNameElement.textContent = playerName;
            
            // Get selected colors using hex from database
            const color1 = getSelectedColorHex(color1Select) || '#333333'; // Primary
            const color2 = getSelectedColorHex(color2Select) || '#ffffff'; // Secondary
            const color3 = getSelectedColorHex(color3Select) || color2;    // Tertiary
            
            // Update jersey colors (primary for body - white area)
            document.getElementById('jerseyBody').setAttribute('fill', color1);
            
            // Update number area (secondary color)
            document.getElementById('numberArea').setAttribute('fill', color2);
            
            // Calculate text color for number and name
            const textColor = getContrastColor(color2);
            document.getElementById('shirtNumber').setAttribute('fill', textColor);
            document.getElementById('playerName').setAttribute('fill', textColor);
        }
        
        function getSelectedColorHex(selectElement) {
            if (!selectElement.value) return null;
            
            const selectedOption = selectElement.options[selectElement.selectedIndex];
            return selectedOption.dataset.hex || null;
        }
        
        
        // Event listeners
        numberInput.addEventListener('input', updatePreview);
        playerInput.addEventListener('input', updatePreview);
        color1Select.addEventListener('change', updatePreview);
        color2Select.addEventListener('change', updatePreview);
        color3Select.addEventListener('change', updatePreview);
        
        // Initial update
        updatePreview();
    }
    </script>
</body>
</html>
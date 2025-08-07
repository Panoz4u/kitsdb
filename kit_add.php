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

            <!-- Preview Jersey Live -->
            <div class="svg-preview-container">
                <h3>Jersey Preview</h3>
                <div id="live-preview" style="display: flex; justify-content: center;">
                    <div style="position: relative;">
                        <svg width="200" height="200" viewBox="0 0 4267 4267" xmlns="http://www.w3.org/2000/svg" style="max-width: 200px; max-height: 200px;">
                            <!-- Jersey borders/outline (secondary color - originally gray #4B5563) -->
                            <g transform="translate(0.000000,4267.000000) scale(0.100000,-0.100000)" id="jerseyBorder">
                                <path d="M14535 37249 c-2088 -1384 -4740 -2804 -7115 -3811 -307 -131 -744
                                -306 -1000 -403 -113 -42 -263 -105 -335 -140 -521 -254 -909 -693 -1148
                                -1300 -120 -304 -193 -615 -244 -1035 -17 -137 -18 -640 -21 -9455 -2 -6570 0
                                -9357 8 -9470 102 -1525 802 -2885 1961 -3811 683 -546 1495 -911 2379 -1070
                                166 -30 410 -60 595 -74 195 -14 23245 -14 23440 0 734 54 1388 230 2030 545
                                1251 615 2197 1705 2641 3043 141 424 233 899 264 1367 8 113 10 2900 8 9470
                                -3 8815 -4 9318 -21 9455 -51 420 -124 731 -244 1035 -239 607 -627 1046
                                -1148 1300 -71 35 -222 98 -335 140 -533 201 -1236 496 -1905 800 -2128 966
                                -4276 2145 -6158 3378 -98 65 -180 117 -182 117 -2 0 -111 -107 -242 -238
                                -965 -964 -1977 -1713 -3023 -2237 -1589 -795 -3180 -1034 -4770 -715 -1736
                                349 -3469 1359 -5063 2952 -131 131 -241 238 -245 237 -4 0 -61 -37 -127 -80z
                                m1770 -4104 c1137 -701 2438 -1043 4280 -1125 284 -13 1216 -13 1500 0 1574
                                70 2747 330 3747 830 260 130 456 243 738 425 98 62 102 64 69 29 -50 -54
                                -3532 -3649 -3688 -3808 l-126 -128 155 6 c3842 162 7613 978 10157 2200 l200
                                96 204 -111 c787 -428 1720 -927 2157 -1152 285 -147 368 -204 505 -347 246
                                -255 401 -590 452 -977 13 -101 15 -1104 15 -8521 0 -8966 2 -8501 -45 -8770
                                -228 -1315 -1273 -2357 -2585 -2581 -280 -47 -183 -46 -3195 -46 l-2840 0 -5
                                5610 c-5 5099 -7 5617 -22 5685 -64 298 -143 492 -290 710 -254 377 -630 647
                                -1061 761 -242 63 146 59 -5292 59 -5438 0 -5050 4 -5292 -59 -634 -168 -1151
                                -685 -1315 -1315 -57 -220 -52 275 -58 -5841 l-5 -5610 -2840 0 c-2298 0
                                -2863 3 -2960 13 -717 80 -1338 359 -1850 832 -503 464 -855 1109 -970 1777
                                -47 277 -45 -207 -45 8775 0 7417 2 8420 15 8521 51 387 206 722 452 977 137
                                143 220 200 505 347 437 225 1370 724 2157 1152 l204 111 200 -96 c2547 -1223
                                6298 -2035 10162 -2200 l150 -6 -126 128 c-154 158 -3638 3754 -3688 3808 -33
                                35 -29 33 69 -29 58 -38 150 -96 205 -130z" 
                                fill="#4B5563"/>
                            </g>
                            
                            <!-- Jersey inner area (primary color - originally transparent) -->
                            <g transform="translate(0.000000,4267.000000) scale(0.100000,-0.100000)" id="jerseyInner">
                                <path d="M16305 33145 c1137 -701 2438 -1043 4280 -1125 284 -13 1216 -13 1500 0 1574
                                70 2747 330 3747 830 260 130 456 243 738 425 98 62 102 64 69 29 -50 -54
                                -3532 -3649 -3688 -3808 l-126 -128 155 6 c3842 162 7613 978 10157 2200 l200
                                96 204 -111 c787 -428 1720 -927 2157 -1152 285 -147 368 -204 505 -347 246
                                -255 401 -590 452 -977 13 -101 15 -1104 15 -8521 0 -8966 2 -8501 -45 -8770
                                -228 -1315 -1273 -2357 -2585 -2581 -280 -47 -183 -46 -3195 -46 l-2840 0 -5
                                5610 c-5 5099 -7 5617 -22 5685 -64 298 -143 492 -290 710 -254 377 -630 647
                                -1061 761 -242 63 146 59 -5292 59 -5438 0 -5050 4 -5292 -59 -634 -168 -1151
                                -685 -1315 -1315 -57 -220 -52 275 -58 -5841 l-5 -5610 -2840 0 c-2298 0
                                -2863 3 -2960 13 -717 80 -1338 359 -1850 832 -503 464 -855 1109 -970 1777
                                -47 277 -45 -207 -45 8775 0 7417 2 8420 15 8521 51 387 206 722 452 977 137
                                143 220 200 505 347 437 225 1370 724 2157 1152 l204 111 200 -96 c2547 -1223
                                6298 -2035 10162 -2200 l150 -6 -126 128 c-154 158 -3638 3754 -3688 3808 -33
                                35 -29 33 69 -29 58 -38 150 -96 205 -130z" 
                                fill="#ffffff" fill-opacity="0.9"/>
                            </g>
                            
                            <!-- V-neck cutout (transparent) -->
                            <path d="M 2000 800 L 2133 1200 L 2267 800 C 2240 850 2180 900 2133 900 C 2086 900 2026 850 2000 800 Z" 
                                  fill="none" stroke="none"/>
                        </svg>
                        
                        <!-- Number area overlay (secondary color) -->
                        <div id="numberArea" style="
                            position: absolute;
                            width: 80px;
                            height: 50px;
                            background: #4B5563;
                            border-radius: 8px;
                            left: 60px;
                            top: 90px;
                            display: flex;
                            flex-direction: column;
                            justify-content: center;
                            align-items: center;
                            opacity: 0.9;
                        ">
                            <!-- Player name -->
                            <div id="playerName" style="
                                font-family: Arial, sans-serif;
                                font-size: 8px;
                                font-weight: bold;
                                color: #ffffff;
                                margin-bottom: 2px;
                            "></div>
                            
                            <!-- Number -->
                            <div id="shirtNumber" style="
                                font-family: Arial, sans-serif;
                                font-size: 20px;
                                font-weight: bold;
                                color: #ffffff;
                            "></div>
                        </div>
                    </div>
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
                option.textContent = color.name;
                option.dataset.hex = color.hex;
                option.style.backgroundColor = color.hex;
                option.style.color = getContrastColor(color.hex);
                option.style.fontWeight = 'bold';
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
            
            // Update number and player name
            document.getElementById('shirtNumber').textContent = number;
            document.getElementById('playerName').textContent = playerName;
            
            // Get selected colors using hex from database
            const color1 = getSelectedColorHex(color1Select) || '#ffffff'; // Primary (inner jersey)
            const color2 = getSelectedColorHex(color2Select) || '#4B5563'; // Secondary (borders)
            
            // Update jersey colors in SVG
            const jerseyInner = document.getElementById('jerseyInner');
            const jerseyBorder = document.getElementById('jerseyBorder');
            
            if (jerseyInner) {
                jerseyInner.querySelector('path').setAttribute('fill', color1);
            }
            if (jerseyBorder) {
                jerseyBorder.querySelector('path').setAttribute('fill', color2);
            }
            
            // Update number area
            document.getElementById('numberArea').style.background = color2;
            
            // Calculate text color for number and name
            const textColor = getContrastColor(color2);
            document.getElementById('shirtNumber').style.color = textColor;
            document.getElementById('playerName').style.color = textColor;
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
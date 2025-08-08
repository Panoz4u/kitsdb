<?php
require_once 'config.php';

$kit_id = intval($_GET['id'] ?? 0);

if ($kit_id <= 0) {
    header('Location: kits_browse.php');
    exit();
}

try {
    $db = getDb();
    
    // Load kit data with all related information
    $stmt = $db->prepare("
        SELECT 
            k.*,
            t.name as team_name, t.FMID,
            b.name as brand_name,
            c.name as category_name,
            jt.name as jersey_type_name,
            co.name as condition_name, co.stars as condition_stars,
            s.name as size_name,
            c1.name as color1_name, c1.hex as color1_hex,
            c2.name as color2_name, c2.hex as color2_hex,
            c3.name as color3_name, c3.hex as color3_hex
        FROM kits k
        LEFT JOIN teams t ON k.team_id = t.team_id
        LEFT JOIN brands b ON k.brand_id = b.brand_id
        LEFT JOIN categories c ON k.category_id = c.category_id
        LEFT JOIN jersey_types jt ON k.jersey_type_id = jt.jersey_type_id
        LEFT JOIN conditions co ON k.condition_id = co.condition_id
        LEFT JOIN sizes s ON k.size_id = s.size_id
        LEFT JOIN colors c1 ON k.color1_id = c1.color_id
        LEFT JOIN colors c2 ON k.color2_id = c2.color_id
        LEFT JOIN colors c3 ON k.color3_id = c3.color_id
        WHERE k.kit_id = ?
    ");
    $stmt->execute([$kit_id]);
    $kit = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$kit) {
        header('Location: kits_browse.php');
        exit();
    }
    
    // Load photos
    $photoStmt = $db->prepare("
        SELECT p.*, pc.name as classification_name 
        FROM photos p 
        LEFT JOIN photo_classifications pc ON p.classification_id = pc.classification_id 
        WHERE p.kit_id = ? 
        ORDER BY p.uploaded_at ASC
    ");
    $photoStmt->execute([$kit_id]);
    $photos = $photoStmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    header('Location: kits_browse.php');
    exit();
}

// Function to determine if text should be white or black based on background color
function getContrastColor($hexColor) {
    if (!$hexColor) return '#ffffff';
    
    // Remove # if present
    $hexColor = ltrim($hexColor, '#');
    
    // Convert hex to RGB
    $r = hexdec(substr($hexColor, 0, 2));
    $g = hexdec(substr($hexColor, 2, 2));
    $b = hexdec(substr($hexColor, 4, 2));
    
    // Calculate relative luminance
    $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
    
    // Return white text for dark backgrounds, black for light backgrounds
    return $luminance > 0.5 ? '#000000' : '#ffffff';
}

$nameTextColor = getContrastColor($kit['color1_hex']); // Name contrasts with primary color
$numberTextColor = getContrastColor($kit['color2_hex']); // Number contrasts with secondary color
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($kit['team_name']); ?> Jersey - KITSDB</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .back-icon {
            position: absolute;
            top: 1rem;
            left: 1rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.1);
            color: var(--primary-text);
            text-decoration: none;
            border-radius: 50%;
            border: 1px solid var(--border-color);
            transition: all 0.2s ease;
            font-size: 1.2rem;
            font-weight: bold;
            z-index: 10;
        }
        
        .back-icon:hover {
            background: var(--action-red);
            border-color: var(--action-red);
            color: white;
            transform: scale(1.1);
        }
        
        .browse-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: var(--space-lg);
            padding: 1rem 0;
        }
        
        .browse-title {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary-text);
            margin: 0;
        }
        
        .home-btn {
            background: var(--surface);
            color: var(--primary-text);
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            text-decoration: none;
            border: 1px solid var(--border-color);
            transition: all 0.2s ease;
            font-weight: 500;
        }
        
        .home-btn:hover {
            background: var(--action-red);
            border-color: var(--action-red);
        }
        
        .kit-header {
            background: linear-gradient(135deg, var(--surface) 0%, var(--background) 100%);
            border-radius: 1rem;
            padding: 2rem 1.5rem 1.5rem 1.5rem;
            margin-bottom: 2rem;
            border: 1px solid var(--border-color);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            position: relative;
        }
        
        .header-content {
            display: flex;
            align-items: center;
            gap: 2rem;
        }
        
        .team-logo-large {
            width: 80px;
            height: 80px;
            object-fit: contain;
            background: rgba(255,255,255,0.1);
            border-radius: 0.5rem;
            padding: 0.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .header-info {
            flex: 1;
        }
        
        .team-name {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-text);
            margin: 0 0 0.5rem 0;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .kit-subtitle {
            color: var(--highlight-yellow);
            font-size: 1.2rem;
            font-weight: 600;
            margin: 0;
        }
        
        .jersey-preview-large {
            width: 250px;
            height: auto;
            filter: drop-shadow(0 8px 16px rgba(0, 0, 0, 0.3));
        }
        
        .kit-details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.25rem;
            margin-bottom: 1.25rem;
        }
        
        .details-card {
            background: var(--surface);
            border-radius: 0.75rem;
            padding: 1.5rem;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
        }
        
        .card-title {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--highlight-yellow);
            margin: 0 0 1rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .detail-row.color-row {
            display: grid;
            grid-template-columns: 1fr auto auto;
            align-items: center;
            gap: 0.5rem;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: 500;
            color: var(--secondary-text);
        }
        
        .detail-value {
            color: var(--primary-text);
            font-weight: 600;
            text-align: right;
        }
        
        .detail-row .color-display {
            justify-self: flex-end;
        }
        
        .color-display {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
        }
        
        .color-swatch {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            border: 2px solid var(--border-color);
            display: inline-block;
        }
        
        .condition-display {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .condition-stars {
            color: var(--highlight-yellow);
            font-size: 1.1rem;
        }
        
        .notes-card {
            background: var(--surface);
            border-radius: 0.75rem;
            padding: 1.5rem;
            border: 1px solid var(--border-color);
            margin-bottom: 1.25rem;
            margin-top: 1.25rem;
        }
        
        .notes-text {
            color: var(--primary-text);
            line-height: 1.6;
            font-style: italic;
        }
        
        .photo-gallery {
            margin-bottom: var(--space-xl);
            margin-top: 1.25rem;
        }
        
        .gallery-title {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--primary-text);
            margin: 0 0 1rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .photo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .photo-item {
            background: var(--surface);
            border-radius: 0.75rem;
            overflow: hidden;
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
            cursor: pointer;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
        }
        
        .photo-item:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 32px rgba(222, 60, 75, 0.3);
            border-color: var(--action-red);
        }
        
        .photo-thumbnail {
            width: 100%;
            height: 200px;
            object-fit: contain;
            display: block;
            background: var(--background);
        }
        
        .photo-info {
            padding: 1rem;
        }
        
        .photo-title {
            font-weight: 600;
            color: var(--primary-text);
            margin: 0 0 0.5rem 0;
            font-size: 0.9rem;
        }
        
        .photo-meta {
            color: var(--secondary-text);
            font-size: 0.8rem;
            margin: 0 0 0.5rem 0;
        }
        
        .photo-filename {
            color: var(--secondary-text);
            font-size: 0.75rem;
            font-family: monospace;
            margin: 0;
            opacity: 0.8;
        }
        
        .no-photos {
            text-align: center;
            padding: 3rem 2rem;
            color: var(--secondary-text);
        }
        
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(10px);
        }
        
        .modal-content {
            position: relative;
            margin: 2% auto;
            max-width: 90%;
            max-height: 90%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-image {
            max-width: 100%;
            max-height: 100vh;
            object-fit: contain;
            border-radius: 0.5rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
        }
        
        .modal-close {
            position: absolute;
            top: 20px;
            right: 35px;
            color: #fff;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
            z-index: 1001;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0, 0, 0, 0.5);
            border-radius: 50%;
            transition: all 0.3s ease;
        }
        
        .modal-close:hover {
            background: var(--action-red);
            transform: scale(1.1);
        }
        
        .modal-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0, 0, 0, 0.5);
            color: white;
            border: none;
            font-size: 30px;
            padding: 20px;
            cursor: pointer;
            border-radius: 50%;
            transition: all 0.3s ease;
            z-index: 1001;
        }
        
        .modal-nav:hover {
            background: var(--action-red);
            transform: translateY(-50%) scale(1.1);
        }
        
        .modal-prev {
            left: 20px;
        }
        
        .modal-next {
            right: 20px;
        }
        
        .modal-caption {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 1rem 2rem;
            border-radius: 2rem;
            text-align: center;
            max-width: 80%;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: var(--space-lg);
        }
        
        .action-btn {
            padding: 0.75rem 2rem;
            border-radius: 0.375rem;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-secondary {
            background: var(--action-red);
            color: white;
            border: 1px solid var(--action-red);
        }
        
        .btn-secondary:hover {
            background: #c23842;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(222, 60, 75, 0.4);
        }
        
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }
            
            .team-name {
                font-size: 2rem;
            }
            
            .kit-details-grid {
                grid-template-columns: 1fr;
                gap: var(--space-lg);
            }
            
            .photo-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }
            
            .modal-nav {
                display: none;
            }
            
            .browse-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <!-- Main Content -->
    <div class="container">
        <!-- Browse Header -->
        <div class="browse-header">
            <h1 class="browse-title">Kit Details</h1>
            <a href="kits_browse.php" class="home-btn">← Back to Browse</a>
        </div>
        
        <!-- Kit Header -->
        <div class="kit-header">
            <div class="header-content">
                <a href="kits_browse.php" class="back-icon">
                    &lt;
                </a>
                <?php if ($kit['FMID']): ?>
                    <img src="logo/<?php echo $kit['FMID']; ?>.png" 
                         alt="<?php echo htmlspecialchars($kit['team_name']); ?>" 
                         class="team-logo-large"
                         onerror="this.style.display='none'">
                <?php endif; ?>
                
                <div class="header-info">
                    <h1 class="team-name"><?php echo htmlspecialchars($kit['team_name'] ?? 'Unknown Team'); ?></h1>
                    <p class="kit-subtitle">
                        <?php if ($kit['player_name']): ?>
                            <?php echo htmlspecialchars($kit['player_name']); ?> 
                            <?php if ($kit['number']): ?>#<?php echo $kit['number']; ?><?php endif; ?>
                        <?php elseif ($kit['number']): ?>
                            Jersey #<?php echo $kit['number']; ?>
                        <?php else: ?>
                            Jersey Kit
                        <?php endif; ?>
                        <?php if ($kit['season']): ?>
                            - <?php echo htmlspecialchars($kit['season']); ?>
                        <?php endif; ?>
                    </p>
                </div>
                
                <div>
                    <div style="position: relative;">
                        <svg width="250" height="250" viewBox="0 0 4267 4267" xmlns="http://www.w3.org/2000/svg" style="max-width: 250px; max-height: 250px; filter: drop-shadow(0 8px 16px rgba(0, 0, 0, 0.3));">
                            <!-- Jersey borders/outline (secondary color) -->
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
                                fill="<?php echo $kit['color2_hex'] ?: '#4B5563'; ?>"/>
                            </g>
                            
                            <!-- Jersey inner area (primary color) -->
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
                                fill="<?php echo $kit['color1_hex'] ?: '#ffffff'; ?>" fill-opacity="0.9"/>
                            </g>
                            
                            <!-- V-neck cutout (transparent) -->
                            <path d="M 2000 800 L 2133 1200 L 2267 800 C 2240 850 2180 900 2133 900 C 2086 900 2026 850 2000 800 Z" 
                                  fill="none" stroke="none"/>
                            
                            <!-- Player name text -->
                            <text x="2133" y="1900" text-anchor="middle" 
                                  font-family="Barlow Condensed, Arial, sans-serif" font-weight="bold" 
                                  font-size="600" fill="<?php echo $nameTextColor; ?>">
                                <?php echo strtoupper(htmlspecialchars($kit['player_name'] ?: '')); ?>
                            </text>
                            
                            <!-- Number text -->
                            <text x="2133" y="3100" text-anchor="middle" 
                                  font-family="Barlow Condensed, Arial, sans-serif" font-weight="bold" 
                                  font-size="1000" fill="<?php echo $numberTextColor; ?>">
                                <?php echo $kit['number'] ?: ''; ?>
                            </text>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kit Details -->
        <div class="kit-details-grid">
            <!-- Basic Information -->
            <div class="details-card">
                <h3 class="card-title">📋 Basic Information</h3>
                
                <?php if ($kit['season']): ?>
                <div class="detail-row">
                    <span class="detail-label">Season</span>
                    <span class="detail-value"><?php echo htmlspecialchars($kit['season']); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($kit['brand_name']): ?>
                <div class="detail-row">
                    <span class="detail-label">Brand</span>
                    <span class="detail-value"><?php echo htmlspecialchars($kit['brand_name']); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($kit['category_name']): ?>
                <div class="detail-row">
                    <span class="detail-label">Category</span>
                    <span class="detail-value"><?php echo htmlspecialchars($kit['category_name']); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($kit['jersey_type_name']): ?>
                <div class="detail-row">
                    <span class="detail-label">Type</span>
                    <span class="detail-value"><?php echo htmlspecialchars($kit['jersey_type_name']); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($kit['size_name']): ?>
                <div class="detail-row">
                    <span class="detail-label">Size</span>
                    <span class="detail-value"><?php echo htmlspecialchars($kit['size_name']); ?></span>
                </div>
                <?php endif; ?>
                
                <div class="detail-row">
                    <span class="detail-label">Sleeves</span>
                    <span class="detail-value"><?php echo htmlspecialchars($kit['sleeves'] ?? 'Short'); ?></span>
                </div>
            </div>

            <!-- Colors & Condition -->
            <div class="details-card">
                <h3 class="card-title">🎨 Colors & Condition</h3>
                
                <?php if ($kit['color1_name']): ?>
                <div class="detail-row color-row">
                    <span class="detail-label">Primary Color</span>
                    <span class="detail-value"><?php echo htmlspecialchars($kit['color1_name']); ?></span>
                    <span class="color-swatch" style="background-color: <?php echo $kit['color1_hex']; ?>"></span>
                </div>
                <?php endif; ?>
                
                <?php if ($kit['color2_name']): ?>
                <div class="detail-row color-row">
                    <span class="detail-label">Secondary Color</span>
                    <span class="detail-value"><?php echo htmlspecialchars($kit['color2_name']); ?></span>
                    <span class="color-swatch" style="background-color: <?php echo $kit['color2_hex']; ?>"></span>
                </div>
                <?php endif; ?>
                
                <?php if ($kit['color3_name']): ?>
                <div class="detail-row color-row">
                    <span class="detail-label">Tertiary Color</span>
                    <span class="detail-value"><?php echo htmlspecialchars($kit['color3_name']); ?></span>
                    <span class="color-swatch" style="background-color: <?php echo $kit['color3_hex']; ?>"></span>
                </div>
                <?php endif; ?>
                
                <?php if ($kit['condition_name']): ?>
                <div class="detail-row">
                    <span class="detail-label">Condition</span>
                    <div class="condition-display">
                        <span class="detail-value"><?php echo htmlspecialchars($kit['condition_name']); ?></span>
                        <span class="condition-stars">
                            <?php for ($i = 0; $i < $kit['condition_stars']; $i++): ?>⭐<?php endfor; ?>
                        </span>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="detail-row">
                    <span class="detail-label">Added</span>
                    <span class="detail-value"><?php echo date('d/m/Y', strtotime($kit['created_at'])); ?></span>
                </div>
            </div>
        </div>

        <!-- Notes -->
        <?php if ($kit['notes']): ?>
        <div class="notes-card">
            <h3 class="card-title">💬 Notes</h3>
            <p class="notes-text"><?php echo nl2br(htmlspecialchars($kit['notes'])); ?></p>
        </div>
        <?php endif; ?>

        <!-- Photo Gallery -->
        <div class="photo-gallery">
            <h2 class="gallery-title">📸 Photo Gallery (<?php echo count($photos); ?>)</h2>
            
            <?php if (!empty($photos)): ?>
            <div class="photo-grid">
                <?php foreach ($photos as $index => $photo): ?>
                    <?php
                    $photoPath = null;
                    $possiblePaths = [
                        'uploads/front/' . $photo['filename'],
                        'uploads/back/' . $photo['filename'], 
                        'uploads/extra/' . $photo['filename']
                    ];
                    foreach ($possiblePaths as $path) {
                        if (file_exists(__DIR__ . '/' . $path)) {
                            $photoPath = $path;
                            break;
                        }
                    }
                    ?>
                    
                    <?php if ($photoPath): ?>
                    <div class="photo-item" onclick="openModal(<?php echo $index; ?>)">
                        <img src="<?php echo $photoPath; ?>" 
                             alt="<?php echo htmlspecialchars($photo['title'] ?? 'Photo'); ?>" 
                             class="photo-thumbnail"
                             loading="lazy">
                        <div class="photo-info">
                            <h4 class="photo-title"><?php echo htmlspecialchars($photo['title'] ?: 'No title'); ?></h4>
                            <p class="photo-meta">
                                <?php echo htmlspecialchars($photo['classification_name'] ?? 'N/A'); ?>
                                • <?php echo date('d/m/Y', strtotime($photo['uploaded_at'])); ?>
                            </p>
                            <p class="photo-filename">
                                <?php echo htmlspecialchars($photo['filename']); ?>
                            </p>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="no-photos">
                <p>No photos available for this jersey.</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Action Button -->
        <div class="action-buttons">
            <a href="kits_browse.php" class="action-btn btn-secondary">
                📋 Back to Browse
            </a>
        </div>
    </div>

    <!-- Photo Modal -->
    <div id="photoModal" class="modal">
        <span class="modal-close" onclick="closeModal()">&times;</span>
        <div class="modal-content">
            <button class="modal-nav modal-prev" onclick="changePhoto(-1)">&#10094;</button>
            <img id="modalImage" src="" alt="" class="modal-image">
            <button class="modal-nav modal-next" onclick="changePhoto(1)">&#10095;</button>
        </div>
        <div class="modal-caption" id="modalCaption"></div>
    </div>

    <script>
    const photos = <?php echo json_encode(array_map(function($photo) {
        $photoPath = null;
        $possiblePaths = [
            'uploads/front/' . $photo['filename'],
            'uploads/back/' . $photo['filename'], 
            'uploads/extra/' . $photo['filename']
        ];
        foreach ($possiblePaths as $path) {
            if (file_exists(__DIR__ . '/' . $path)) {
                $photoPath = $path;
                break;
            }
        }
        return [
            'path' => $photoPath,
            'title' => $photo['title'] ?: $photo['filename'],
            'classification' => $photo['classification_name'] ?? 'N/A'
        ];
    }, $photos)); ?>;
    
    let currentPhotoIndex = 0;
    
    function openModal(index) {
        currentPhotoIndex = index;
        const modal = document.getElementById('photoModal');
        const modalImage = document.getElementById('modalImage');
        const modalCaption = document.getElementById('modalCaption');
        
        if (photos[index] && photos[index].path) {
            modalImage.src = photos[index].path;
            modalCaption.innerHTML = `
                <strong>${photos[index].title}</strong><br>
                <small>${photos[index].classification}</small>
            `;
            modal.style.display = 'block';
        }
    }
    
    function closeModal() {
        document.getElementById('photoModal').style.display = 'none';
    }
    
    function changePhoto(direction) {
        const validPhotos = photos.filter(photo => photo.path);
        if (validPhotos.length <= 1) return;
        
        currentPhotoIndex += direction;
        if (currentPhotoIndex >= validPhotos.length) {
            currentPhotoIndex = 0;
        } else if (currentPhotoIndex < 0) {
            currentPhotoIndex = validPhotos.length - 1;
        }
        
        // Find the actual index in the original photos array
        let actualIndex = 0;
        let validCount = 0;
        for (let i = 0; i < photos.length; i++) {
            if (photos[i].path) {
                if (validCount === currentPhotoIndex) {
                    actualIndex = i;
                    break;
                }
                validCount++;
            }
        }
        
        openModal(actualIndex);
    }
    
    // Close modal on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal();
        } else if (e.key === 'ArrowLeft') {
            changePhoto(-1);
        } else if (e.key === 'ArrowRight') {
            changePhoto(1);
        }
    });
    
    // Close modal when clicking outside image
    document.getElementById('photoModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });
    </script>
</body>
</html>
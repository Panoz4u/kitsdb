<?php
require_once 'auth.php';
require_once 'config.php';

requireAuth();

$kit_id = intval($_GET['id'] ?? 0);

if ($kit_id <= 0) {
    header('Location: kits_list.php');
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
        header('Location: kits_list.php');
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
    header('Location: kits_list.php');
    exit();
}

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($kit['team_name']); ?> Jersey - KITSDB</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .back-navigation {
            margin-bottom: var(--space-lg);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: var(--surface);
            color: var(--primary-text);
            text-decoration: none;
            border-radius: 0.375rem;
            border: 1px solid var(--border-color);
            transition: all 0.2s ease;
            font-size: 0.9rem;
        }
        
        .back-btn:hover {
            background: var(--action-red);
            border-color: var(--action-red);
            color: white;
        }
        
        .kit-header {
            background: linear-gradient(135deg, var(--surface) 0%, var(--background) 100%);
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: var(--space-xl);
            border: 1px solid var(--border-color);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
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
            width: 150px;
            height: auto;
            filter: drop-shadow(0 8px 16px rgba(0, 0, 0, 0.3));
        }
        
        .kit-details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--space-xl);
            margin-bottom: var(--space-xl);
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
        
        .color-display {
            display: flex;
            align-items: center;
            gap: 0.5rem;
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
            margin-bottom: var(--space-xl);
        }
        
        .notes-text {
            color: var(--primary-text);
            line-height: 1.6;
            font-style: italic;
        }
        
        .photo-gallery {
            margin-bottom: var(--space-xl);
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
            object-fit: cover;
            display: block;
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
        
        .btn-edit {
            background: var(--action-red);
            color: white;
            border: 1px solid var(--action-red);
        }
        
        .btn-edit:hover {
            background: #c23842;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(222, 60, 75, 0.4);
        }
        
        .btn-secondary {
            background: var(--surface);
            color: var(--primary-text);
            border: 1px solid var(--border-color);
        }
        
        .btn-secondary:hover {
            background: var(--background);
            transform: translateY(-2px);
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
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <a href="dashboard.php" class="logo">KITSDB</a>
            <nav class="nav-menu">
                <a href="kits_list.php" class="nav-link">Jersey List</a>
                <a href="kit_add.php" class="nav-link">Add Jersey</a>
                <form method="POST" action="logout.php" style="display: inline;">
                    <button type="submit" class="logout-btn">Logout</button>
                </form>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <div class="container">
        <!-- Back Navigation -->
        <div class="back-navigation">
            <a href="kits_list.php" class="back-btn">
                ← Back to List
            </a>
        </div>

        <!-- Kit Header -->
        <div class="kit-header">
            <div class="header-content">
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
                    <img src="preview/maglia.php?id=<?php echo $kit['kit_id']; ?>" 
                         alt="Jersey preview" 
                         class="jersey-preview-large">
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
                <div class="detail-row">
                    <span class="detail-label">Primary Color</span>
                    <div class="color-display">
                        <span class="color-swatch" style="background-color: <?php echo $kit['color1_hex']; ?>"></span>
                        <span class="detail-value"><?php echo htmlspecialchars($kit['color1_name']); ?></span>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($kit['color2_name']): ?>
                <div class="detail-row">
                    <span class="detail-label">Secondary Color</span>
                    <div class="color-display">
                        <span class="color-swatch" style="background-color: <?php echo $kit['color2_hex']; ?>"></span>
                        <span class="detail-value"><?php echo htmlspecialchars($kit['color2_name']); ?></span>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($kit['color3_name']): ?>
                <div class="detail-row">
                    <span class="detail-label">Tertiary Color</span>
                    <div class="color-display">
                        <span class="color-swatch" style="background-color: <?php echo $kit['color3_hex']; ?>"></span>
                        <span class="detail-value"><?php echo htmlspecialchars($kit['color3_name']); ?></span>
                    </div>
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
                            <h4 class="photo-title"><?php echo htmlspecialchars($photo['title'] ?: $photo['filename']); ?></h4>
                            <p class="photo-meta">
                                <?php echo htmlspecialchars($photo['classification_name'] ?? 'N/A'); ?>
                                • <?php echo date('d/m/Y', strtotime($photo['uploaded_at'])); ?>
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

        <!-- Action Buttons -->
        <div class="action-buttons">
            <a href="kit_edit.php?id=<?php echo $kit['kit_id']; ?>" class="action-btn btn-edit">
                ✏️ Edit Jersey
            </a>
            <a href="kits_list.php" class="action-btn btn-secondary">
                📋 Back to List
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
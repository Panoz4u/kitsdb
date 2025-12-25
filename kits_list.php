<?php
require_once 'auth.php';
require_once 'config.php';
require_once 'qr_helper.php';

requireAdmin();

// Contrast helper for text over colors
if (!function_exists('getContrastColor')) {
function getContrastColor($hexColor) {
    if (!$hexColor) return '#ffffff';
    $hexColor = ltrim($hexColor, '#');
    $r = hexdec(substr($hexColor, 0, 2));
    $g = hexdec(substr($hexColor, 2, 2));
    $b = hexdec(substr($hexColor, 4, 2));
    $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
    return $luminance > 0.5 ? '#000000' : '#ffffff';
}
}



// Handle filters and search
$search = $_GET['search'] ?? '';
$brand_filter = $_GET['brand'] ?? '';
$category_filter = $_GET['category'] ?? '';
$type_filter = $_GET['type'] ?? '';
$condition_filter = $_GET['condition'] ?? '';
$nation_filter = $_GET['nation'] ?? '';
$view_mode = $_GET['view'] ?? 'list'; // cards or list
$sort_by = $_GET['sort'] ?? 'created_at';
$sort_direction = $_GET['dir'] ?? 'desc';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = intval($_GET['per_page'] ?? 15);
if (!in_array($per_page, [15, 30, 50])) $per_page = 15;
$offset = ($page - 1) * $per_page;

try {
    $db = getDb();
    
    // Base query for kits
    $where_conditions = ['1=1'];
    $params = [];
    
    // Text search filter
    if (!empty($search)) {
        $where_conditions[] = "(t.name LIKE ? OR k.player_name LIKE ? OR k.season LIKE ? OR k.number LIKE ? OR k.kit_id LIKE ?)";
        $searchTerm = '%' . $search . '%';
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }
    
    // Dropdown filters
    if (!empty($brand_filter)) {
        $where_conditions[] = "k.brand_id = ?";
        $params[] = $brand_filter;
    }
    
    if (!empty($category_filter)) {
        $where_conditions[] = "k.category_id = ?";
        $params[] = $category_filter;
    }
    
    if (!empty($type_filter)) {
        $where_conditions[] = "k.jersey_type_id = ?";
        $params[] = $type_filter;
    }
    
    if (!empty($condition_filter)) {
        $where_conditions[] = "k.condition_id = ?";
        $params[] = $condition_filter;
    }
    
    if (!empty($nation_filter)) {
        $where_conditions[] = "t.nation_id = ?";
        $params[] = $nation_filter;
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    // Valid sort columns and their mappings
    $valid_sorts = [
        'team' => 't.name',
        'season' => 'k.season',
        'type' => 'jt.name',
        'category' => 'c.name',
        'brand' => 'b.name',
        'size' => 's.name', 
        'condition' => 'co.stars',
        'created_at' => 'k.created_at'
    ];
    
    $order_column = $valid_sorts[$sort_by] ?? 'k.created_at';
    $order_direction = ($sort_direction === 'asc') ? 'ASC' : 'DESC';
    
    // Query to count total
    $count_sql = "
        SELECT COUNT(*) 
        FROM kits k
        LEFT JOIN teams t ON k.team_id = t.team_id
        WHERE $where_clause
    ";
    $count_stmt = $db->prepare($count_sql);
    $count_stmt->execute($params);
    $total_kits = $count_stmt->fetchColumn();
    $total_pages = ceil($total_kits / $per_page);
    
    // Main query for data
    $sql = "
        SELECT 
            k.*,
            t.name as team_name,
            t.FMID,
            b.name as brand_name,
            c.name as category_name,
            jt.name as jersey_type_name,
            co.name as condition_name,
            co.stars as condition_stars,
            s.name as size_name,
            c1.name as color1_name,
            c1.hex as color1_hex,
            c2.name as color2_name,
            c2.hex as color2_hex,
            c3.name as color3_name,
            c3.hex as color3_hex,
            (SELECT COUNT(*) FROM photos p WHERE p.kit_id = k.kit_id) as photo_count
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
        WHERE $where_clause
        ORDER BY $order_column $order_direction
        LIMIT $per_page OFFSET $offset
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $kits = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $kits = [];
    $total_kits = 0;
    $total_pages = 0;
}

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jersey List - KITSDB</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        /* Top search and controls section */
        .search-controls {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: var(--space-lg);
            background: var(--surface);
            padding: 1rem;
            border-radius: 0.5rem;
            border: 1px solid var(--border-color);
        }
        
        .search-main {
            flex: 1;
        }
        
        .search-main input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: 0.375rem;
            background: var(--background);
            color: var(--primary-text);
            font-size: 1rem;
            height: 48px;
            box-sizing: border-box;
            margin-bottom: 5px;
        }
        
        .search-main input:focus {
            outline: none;
            border-color: var(--highlight-yellow);
            box-shadow: 0 0 0 2px rgba(220, 247, 99, 0.2);
        }
        
        .controls-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        /* Results header with view toggle */
        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--space-md);
        }
        
        .view-toggle {
            display: flex;
            background: var(--background);
            border-radius: 0.375rem;
            border: 1px solid var(--border-color);
            overflow: hidden;
        }
        
        .view-btn {
            padding: 0.5rem 1rem;
            background: transparent;
            border: none;
            color: var(--primary-text);
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .view-btn.active {
            background: var(--action-red);
            color: white;
        }
        
        .view-btn:hover {
            background: var(--action-red);
            color: white;
        }
        
        .filters-toggle {
            background: var(--action-red);
            color: white;
            border: none;
            padding: 0.75rem 1rem;
            border-radius: 0.375rem;
            cursor: pointer;
            transition: all 0.2s ease;
            height: 48px;
            box-sizing: border-box;
            display: flex;
            align-items: center;
            margin-top: -5px;
        }
        
        .filters-toggle:hover {
            background: #c23842;
        }
        
        /* Filters panel */
        .filters-panel {
            background: var(--surface);
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: var(--space-lg);
            display: none;
        }
        
        .filters-panel.active {
            display: block;
        }
        
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--primary-text);
            font-weight: 500;
        }
        
        .filter-group select {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid var(--border-color);
            border-radius: 0.375rem;
            background: var(--background);
            color: var(--primary-text);
        }
        
        .filters-actions {
            display: flex;
            justify-content: flex-end;
            gap: 0.5rem;
        }
        
        /* List view styles */
        .kit-list {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .kit-list-item {
            display: flex;
            align-items: center;
            padding: 0.5rem 1rem;
            background: var(--surface);
            border: 1px solid var(--border-color);
            border-radius: 0.375rem;
            transition: all 0.2s ease;
            min-height: 50px;
        }
        
        .kit-list-item:hover {
            border-color: var(--action-red);
            box-shadow: 0 2px 8px rgba(222, 60, 75, 0.2);
        }
        
        .list-preview {
            width: 40px;
            height: 40px;
            margin-right: 1rem;
            flex-shrink: 0;
        }
        
        .list-preview img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        .list-content {
            flex: 1;
            display: grid;
            grid-template-columns: 40px 40px 2fr 1fr 1fr 1fr 100px 150px auto;
            gap: 0.75rem;
            align-items: center;
            min-width: 0;
        }
        
        @media (max-width: 768px) {
            .list-content {
                grid-template-columns: 40px 2fr 1fr;
                gap: 0.5rem;
            }
            .list-content .hide-mobile {
                display: none;
            }
            .list-team {
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                max-width: 100%;
            }
            .kit-list-item {
                min-height: 50px;
                max-height: 50px;
                overflow: hidden;
            }
        }
        
        @media (min-width: 769px) and (max-width: 1024px) {
            .list-content {
                grid-template-columns: 40px 40px 2fr 1fr 1fr 100px auto;
                gap: 0.5rem;
            }
            .list-content .hide-tablet {
                display: none;
            }
        }
        
        .list-team {
            font-weight: 600;
            color: var(--primary-text);
        }
        
        @media (max-width: 768px) {
            .list-team {
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                max-width: 120px;
            }
        }
        
        .list-player {
            color: var(--secondary-text);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .list-detail {
            color: var(--secondary-text);
            font-size: 0.875rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .list-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .list-actions .action-btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }
        
        /* Existing pagination styles */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin-top: var(--space-lg);
        }
        
        .pagination a, .pagination span {
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        
        .pagination a {
            background: var(--surface);
            color: var(--primary-text);
            border: 1px solid var(--border-color);
        }
        
        .pagination a:hover {
            background: var(--action-red);
            border-color: var(--action-red);
        }
        
        .pagination .current {
            background: var(--action-red);
            color: var(--primary-text);
            font-weight: 600;
        }
        
        .results-info {
            color: var(--secondary-text);
        }
        
        .team-logo {
            width: 100px;
            height: 100px;
            object-fit: contain;
            border-radius: 0.25rem;
            padding: 4px;
        }
        
        .team-logo-list {
            width: 30px;
            height: 30px;
            object-fit: contain;
            border-radius: 0.25rem;
            background: rgba(255,255,255,0.1);
            padding: 2px;
            margin-right: 0.75rem;
        }
        
        .kit-preview {
            height: 200px;
            background: var(--background);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            flex-direction: row;
            gap: 1rem;
        }
        
        .svg-preview {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .kit-colors {
            display: flex;
            gap: 0.25rem;
            margin-top: 0.5rem;
        }
        
        .color-swatch {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 2px solid var(--border-color);
        }
        
        .condition-stars {
            color: var(--highlight-yellow);
            font-size: 0.9rem;
        }
        
        /* Hide card grid when in list view */
        body[data-view="list"] .kit-grid {
            display: none;
        }
        
        body[data-view="cards"] .kit-list {
            display: none;
        }
        
        /* Ensure card layout and button alignment */
        .kit-card {
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        .kit-info {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .kit-actions {
            margin-top: auto;
        }
        
        /* List view sortable header */
        .list-header {
            display: none;
            background: var(--surface);
            border: 1px solid var(--border-color);
            border-radius: 0.375rem;
            padding: 0.5rem 1rem;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--primary-text);
            min-height: 50px;
            box-sizing: border-box;
        }
        
        body[data-view="list"] .list-header {
            display: grid;
            grid-template-columns: 40px 40px 240px 116px 120px 118px 100px 156px auto;
            gap: 0.75rem;
            align-items: center;
            
        }
        
        /* Mobile responsive styles */
        @media (max-width: 768px) {
            body[data-view="list"] .list-header {
                grid-template-columns: 40px 2fr 1fr;
                gap: 0.5rem;
                
            }
            .list-header .hide-mobile {
                display: none;
            }
        }
        
        @media (min-width: 769px) and (max-width: 1024px) {
            body[data-view="list"] .list-header {
                grid-template-columns: 40px 40px 2fr 1fr 1fr 100px auto;
                gap: 0.5rem;
                
            }
            .list-header .hide-tablet {
                display: none;
            }
        }
        
        .sort-btn {
            background: none;
            border: none;
            color: var(--primary-text);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0;
            font-weight: 600;
            transition: color 0.2s ease;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            justify-content: flex-start;
        }
        
        @media (max-width: 768px) {
            .sort-btn {
                font-size: 0.875rem;
                justify-content: flex-start;
            }
        }
        
        .sort-btn:hover {
            color: var(--highlight-yellow);
        }
        
        .sort-indicator {
            font-size: 0.8rem;
            opacity: 0.7;
        }
    
        .split-header { display: grid; grid-template-columns: 1fr 1fr; gap: 0; background: transparent; }
        .split-left, .split-right { display: flex; align-items: center; justify-content: center; background: transparent; min-height: 140px; }
        .split-left img.team-logo { max-width: 100px; max-height: 100px; }
        
        /* QR Code styles for kit listing */
        .qr-mini {
            width: 24px;
            height: 24px;
            margin-left: 0.5rem;
            cursor: pointer;
            border-radius: 0.25rem;
            transition: all 0.2s ease;
        }
        
        .qr-mini:hover {
            transform: scale(1.1);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }
        
        .qr-tooltip {
            position: relative;
            display: inline-block;
        }
        
        .qr-tooltip::after {
            content: "Click for QR code";
            position: absolute;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%);
            background: var(--background);
            color: var(--primary-text);
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s ease;
            border: 1px solid var(--border-color);
            z-index: 1000;
        }
        
        .qr-tooltip:hover::after {
            opacity: 1;
        }
        
        .qr-modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(5px);
        }
        
        .qr-modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: var(--surface);
            padding: 2rem;
            border-radius: 1rem;
            border: 1px solid var(--border-color);
            text-align: center;
            max-width: 400px;
            width: 90%;
        }
        
        .qr-modal-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            color: var(--secondary-text);
            font-size: 1.5rem;
            cursor: pointer;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.2s ease;
        }
        
        .qr-modal-close:hover {
            background: var(--action-red);
            color: white;
        }
        
        .qr-modal-title {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary-text);
            margin: 0 0 1rem 0;
        }
        
        .qr-modal img {
            max-width: 250px;
            width: 100%;
            border-radius: 0.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            display: block;
            margin: 0 auto;
        }
        
        .qr-modal-info {
            margin-top: 1rem;
            color: var(--secondary-text);
            font-size: 0.875rem;
        }
        
        .qr-modal-download {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1rem;
            padding: 0.5rem 1rem;
            background: var(--action-red);
            color: white;
            text-decoration: none;
            border-radius: 0.375rem;
            transition: all 0.2s ease;
        }
        
        .qr-modal-download:hover {
            background: #c23842;
            transform: translateY(-2px);
        }
        </style>
        
</head>
<body data-view="<?php echo $view_mode; ?>">
    <?php include 'includes/admin_header.php'; ?>

    <!-- Main Content -->
    <div class="container">
        <h1>Jersey List</h1>
        
        <!-- Search and Filters Controls -->
        <div class="search-controls">
            <div class="search-main">
                <form method="GET" id="searchForm">
                    <input type="hidden" name="view" value="<?php echo htmlspecialchars($view_mode); ?>">
                    <input type="hidden" name="brand" value="<?php echo htmlspecialchars($brand_filter); ?>">
                    <input type="hidden" name="category" value="<?php echo htmlspecialchars($category_filter); ?>">
                    <input type="hidden" name="type" value="<?php echo htmlspecialchars($type_filter); ?>">
                    <input type="hidden" name="condition" value="<?php echo htmlspecialchars($condition_filter); ?>">
                    <input type="hidden" name="nation" value="<?php echo htmlspecialchars($nation_filter); ?>">
                    <input type="text" 
                           name="search" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Search team, player, season, ID...">
                </form>
            </div>
            
            <div class="controls-right">
                <button class="filters-toggle" onclick="toggleFilters()">
                    Filters
                    <?php 
                    $active_filters = 0;
                    if (!empty($brand_filter)) $active_filters++;
                    if (!empty($category_filter)) $active_filters++;
                    if (!empty($type_filter)) $active_filters++;
                    if (!empty($condition_filter)) $active_filters++;
                    if ($active_filters > 0): ?>
                        <span style="margin-left: 0.25rem;">(<?php echo $active_filters; ?>)</span>
                    <?php endif; ?>
                </button>
            </div>
        </div>
        
        <!-- Filters Panel -->
        <div class="filters-panel" id="filtersPanel">
            <form method="GET" id="filterForm">
                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                <input type="hidden" name="view" value="<?php echo htmlspecialchars($view_mode); ?>">
                <input type="hidden" name="nation" value="<?php echo htmlspecialchars($nation_filter); ?>">
                
                <div class="filters-grid">
                    <div class="filter-group">
                        <label>Brand</label>
                        <select name="brand" id="brandFilter">
                            <option value="">All brands</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Category</label>
                        <select name="category" id="categoryFilter">
                            <option value="">All categories</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Type</label>
                        <select name="type" id="typeFilter">
                            <option value="">All types</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Condition</label>
                        <select name="condition" id="conditionFilter">
                            <option value="">All conditions</option>
                        </select>
                    </div>
                </div>
                
                <div class="filters-actions">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <button type="button" class="btn btn-secondary" onclick="resetFilters()">Reset</button>
                </div>
            </form>
        </div>
        
        <!-- Results and View Toggle -->
        <div class="results-header">
            <div class="results-info">
                Found <?php echo number_format($total_kits); ?> jerseys
                <?php if ($total_pages > 1): ?>
                    - Page <?php echo $page; ?> of <?php echo $total_pages; ?>
                <?php endif; ?>
            </div>
            
            <div class="view-toggle">
                <a href="?<?php echo http_build_query(array_merge($_GET, ['view' => 'cards'])); ?>" 
                   class="view-btn <?php echo $view_mode === 'cards' ? 'active' : ''; ?>">
                    ⊞ Cards
                </a>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['view' => 'list'])); ?>" 
                   class="view-btn <?php echo $view_mode === 'list' ? 'active' : ''; ?>">
                    ☰ List
                </a>
            </div>
        </div>
        
        <!-- Kit Grid (Cards View) -->
        <div class="kit-grid">
            <?php foreach ($kits as $kit): ?>
                <div class="kit-card" onclick="window.location.href='kit_view.php?id=<?php echo $kit['kit_id']; ?>'" style="cursor: pointer;">
                    <div class="kit-preview">
    <div class="split-header">
      <div class="split-left">
        <?php if ($kit['FMID']): ?>
          <img src="logo/<?php echo $kit['FMID']; ?>.png" alt="<?php echo htmlspecialchars($kit['team_name']); ?>" class="team-logo" onerror="this.style.display='none'">
        <?php endif; ?>
      </div>
      <div class="split-right">
        <?php $nameTextColor = getContrastColor($kit['color1_hex'] ?? '#000000'); $numberTextColor = getContrastColor($kit['color2_hex'] ?? '#000000'); ?>
<div class="svg-preview">
                            <svg width="120" height="120" viewBox="0 0 4267 4267" xmlns="http://www.w3.org/2000/svg" style="max-width: 120px; max-height: 120px;">
                                <!-- Jersey borders/outline -->
                                <g transform="translate(0.000000,4267.000000) scale(0.100000,-0.100000)">
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
                                    fill="<?php echo $kit['color2_hex'] ?? '#4B5563'; ?>"/>
                                </g>
                                
                                <!-- Jersey inner area -->
                                <g transform="translate(0.000000,4267.000000) scale(0.100000,-0.100000)">
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
                                    fill="<?php echo $kit['color1_hex'] ?? '#ffffff'; ?>" fill-opacity="0.9"/>
                                </g>
                                
                                <?php if ($kit['player_name']): ?>
    <!-- Player name -->
    <text x="2133" y="1900" text-anchor="middle"
          font-family="Barlow Condensed, Arial, sans-serif" font-weight="bold"
          font-size="600" fill="<?php echo $nameTextColor; ?>">
        <?php echo strtoupper(htmlspecialchars($kit['player_name'])); ?>
    </text>
<?php endif; ?>
<?php if ($kit['number']): ?>
                                    <!-- Jersey number -->
                                    <text x="2133" y="3000" font-family="Barlow Condensed, sans-serif" font-size="900" font-weight="bold" 
                                          text-anchor="middle" fill="<?php echo $numberTextColor; ?>">
                                        <?php echo htmlspecialchars($kit['number']); ?>
                                    </text>
                                <?php endif; ?>
                            </svg>
                        
        </div>
      </div>
    </div>
                    </div>
                    
                    <div class="kit-info">
                        <div class="kit-team"><?php echo htmlspecialchars($kit['team_name'] ?? 'N/A'); ?></div>
                        
                        <div class="kit-details">
                            <?php if ($kit['player_name']): ?>
                                <div><strong><?php echo htmlspecialchars($kit['player_name']); ?></strong> #<?php echo $kit['number']; ?></div>
                            <?php else: ?>
                                <div>Number: <?php echo $kit['number']; ?></div>
                            <?php endif; ?>
                            
                            <div>Season: <?php echo htmlspecialchars($kit['season']); ?></div>
                            
                            <?php if ($kit['brand_name']): ?>
                                <div><?php echo htmlspecialchars($kit['brand_name']); ?></div>
                            <?php endif; ?>
                            
                            <?php if ($kit['size_name']): ?>
                                <div>Size: <?php echo htmlspecialchars($kit['size_name']); ?></div>
                            <?php endif; ?>
                            
                            <?php if ($kit['condition_name']): ?>
                                <div class="condition-stars">
                                    <?php echo htmlspecialchars($kit['condition_name']); ?>
                                    <?php for ($i = 0; $i < $kit['condition_stars']; $i++): ?>⭐<?php endfor; ?>
                                </div>
                            <?php endif; ?>
                            
                            <div><?php echo $kit['photo_count']; ?> photos</div>
                        </div>
                        
                        <!-- Colors -->
                        <?php if ($kit['color1_hex'] || $kit['color2_hex'] || $kit['color3_hex']): ?>
                            <div class="kit-colors">
                                <?php if ($kit['color1_hex']): ?>
                                    <div class="color-swatch" 
                                         style="background-color: <?php echo $kit['color1_hex']; ?>"
                                         title="<?php echo $kit['color1_name']; ?>"></div>
                                <?php endif; ?>
                                <?php if ($kit['color2_hex']): ?>
                                    <div class="color-swatch" 
                                         style="background-color: <?php echo $kit['color2_hex']; ?>"
                                         title="<?php echo $kit['color2_name']; ?>"></div>
                                <?php endif; ?>
                                <?php if ($kit['color3_hex']): ?>
                                    <div class="color-swatch" 
                                         style="background-color: <?php echo $kit['color3_hex']; ?>"
                                         title="<?php echo $kit['color3_name']; ?>"></div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="kit-actions">
                        <a href="kit_edit.php?id=<?php echo $kit['kit_id']; ?>" class="action-btn edit" onclick="event.stopPropagation();">Edit</a>
                        <a href="kit_delete.php?id=<?php echo $kit['kit_id']; ?>" 
                           class="action-btn delete"
                           onclick="event.stopPropagation(); return confirm('Are you sure you want to delete this jersey?');">Delete</a>
                        <span class="qr-tooltip">
                            <img src="<?php echo generateKitQRCode($kit['kit_id'], null, 100); ?>" 
                                 class="qr-mini" 
                                 onclick="event.stopPropagation(); openQRModal(<?php echo $kit['kit_id']; ?>, '<?php echo htmlspecialchars($kit['team_name']); ?>');"
                                 alt="QR Code">
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- List Header (List View Only) -->
        <div class="list-header">
            <div style="font-size: 0.875rem; text-align: left;">Kit</div>
            <div class="hide-mobile" style="font-size: 0.875rem; text-align: left;">Logo</div>
            <button class="sort-btn" onclick="toggleSort('team')" style="text-align: left; justify-content: flex-start;">
                Team <?php if ($sort_by === 'team'): ?><span class="sort-indicator"><?php echo $sort_direction === 'asc' ? '↑' : '↓'; ?></span><?php endif; ?>
            </button>
            <button class="sort-btn" onclick="toggleSort('season')" style="text-align: left; justify-content: flex-start;">
                Season <?php if ($sort_by === 'season'): ?><span class="sort-indicator"><?php echo $sort_direction === 'asc' ? '↑' : '↓'; ?></span><?php endif; ?>
            </button>
            <button class="sort-btn hide-mobile hide-tablet" onclick="toggleSort('type')" style="text-align: left; justify-content: flex-start;">
                Type <?php if ($sort_by === 'type'): ?><span class="sort-indicator"><?php echo $sort_direction === 'asc' ? '↑' : '↓'; ?></span><?php endif; ?>
            </button>
            <button class="sort-btn hide-mobile hide-tablet" onclick="toggleSort('category')" style="text-align: left; justify-content: flex-start;">
                Cat. <?php if ($sort_by === 'category'): ?><span class="sort-indicator"><?php echo $sort_direction === 'asc' ? '↑' : '↓'; ?></span><?php endif; ?>
            </button>
            <div class="hide-mobile" style="font-size: 0.875rem; text-align: left;">Colors</div>
            <div class="hide-mobile hide-tablet" style="font-size: 0.875rem; text-align: left;">Player</div>
            <div class="hide-mobile" style="font-size: 0.875rem; text-align: left;">Actions</div>
        </div>
        
        <!-- Kit List (List View) -->
        <div class="kit-list">
            <?php foreach ($kits as $kit): ?>
                <div class="kit-list-item" onclick="window.location.href='kit_view.php?id=<?php echo $kit['kit_id']; ?>'" style="cursor: pointer;">
                    
                    <div class="list-content">
                        <div class="list-preview">
                            <?php $nameTextColor = getContrastColor($kit['color1_hex'] ?? '#000000'); $numberTextColor = getContrastColor($kit['color2_hex'] ?? '#000000'); ?>
                            <svg width="40" height="40" viewBox="0 0 4267 4267" xmlns="http://www.w3.org/2000/svg">
                                <!-- Jersey borders/outline -->
                                <g transform="translate(0.000000,4267.000000) scale(0.100000,-0.100000)">
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
                                    349 -3469 1359 -5063 2952 -131 131 -241 238 -245 237 -4 0 -61 -37 -127 -80z" 
                                    fill="<?php echo $kit['color2_hex'] ?? '#4B5563'; ?>"/>
                                </g>
                                <!-- Jersey inner area -->
                                <g transform="translate(0.000000,4267.000000) scale(0.100000,-0.100000)">
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
                                    fill="<?php echo $kit['color1_hex'] ?? '#ffffff'; ?>" fill-opacity="0.9"/>
                                </g>
                                
                                <?php if ($kit['player_name']): ?>
                                    <!-- Player name -->
                                    <text x="2133" y="1900" text-anchor="middle"
                                          font-family="Barlow Condensed, Arial, sans-serif" font-weight="bold"
                                          font-size="600" fill="<?php echo $nameTextColor; ?>">
                                        <?php echo strtoupper(htmlspecialchars($kit['player_name'])); ?>
                                    </text>
                                <?php endif; ?>
                                <?php if ($kit['number']): ?>
                                    <!-- Jersey number -->
                                    <text x="2133" y="3000" font-family="Barlow Condensed, sans-serif" font-size="900" font-weight="bold" 
                                          text-anchor="middle" fill="<?php echo $numberTextColor; ?>">
                                        <?php echo htmlspecialchars($kit['number']); ?>
                                    </text>
                                <?php endif; ?>
                            </svg>
                        </div>
                        
                        <div class="hide-mobile">
                            <?php if ($kit['FMID']): ?>
                                <img src="logo/<?php echo $kit['FMID']; ?>.png" 
                                     alt="<?php echo htmlspecialchars($kit['team_name']); ?>" 
                                     class="team-logo-list"
                                     onerror="this.style.display='none'">
                            <?php endif; ?>
                        </div>
                        
                        <div class="list-team">
                            <?php echo htmlspecialchars($kit['team_name'] ?? 'N/A'); ?>
                        </div>
                        
                        <div class="list-detail" style="font-size: 0.875rem; text-align: left;">
                            <?php echo htmlspecialchars($kit['season']); ?>
                        </div>
                        
                        <div class="list-detail hide-mobile hide-tablet" style="font-size: 0.875rem; text-align: left;">
                            <?php echo htmlspecialchars($kit['jersey_type_name'] ?? 'N/A'); ?>
                        </div>
                        
                        <div class="list-detail hide-mobile hide-tablet" style="font-size: 0.875rem; text-align: left;">
                            <?php echo htmlspecialchars($kit['category_name'] ?? 'N/A'); ?>
                        </div>
                        
                        <div class="list-colors hide-mobile">
                            <?php if ($kit['color1_hex'] || $kit['color2_hex'] || $kit['color3_hex']): ?>
                                <div style="display: flex; gap: 0.25rem;">
                                    <?php if ($kit['color1_hex']): ?>
                                        <div class="color-swatch" 
                                             style="background-color: <?php echo $kit['color1_hex']; ?>; width: 16px; height: 16px;"
                                             title="<?php echo $kit['color1_name']; ?>"></div>
                                    <?php endif; ?>
                                    <?php if ($kit['color2_hex']): ?>
                                        <div class="color-swatch" 
                                             style="background-color: <?php echo $kit['color2_hex']; ?>; width: 16px; height: 16px;"
                                             title="<?php echo $kit['color2_name']; ?>"></div>
                                    <?php endif; ?>
                                    <?php if ($kit['color3_hex']): ?>
                                        <div class="color-swatch" 
                                             style="background-color: <?php echo $kit['color3_hex']; ?>; width: 16px; height: 16px;"
                                             title="<?php echo $kit['color3_name']; ?>"></div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="list-player hide-mobile hide-tablet">
                            <?php if ($kit['player_name'] || $kit['number']): ?>
                                <span style="color: var(--secondary-text); font-size: 0.875rem;">
                                    <?php if ($kit['player_name']): ?>
                                        <?php echo htmlspecialchars($kit['player_name']); ?>
                                        <?php if ($kit['number']): ?> #<?php echo $kit['number']; ?><?php endif; ?>
                                    <?php elseif ($kit['number']): ?>
                                        #<?php echo $kit['number']; ?>
                                    <?php endif; ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="list-actions hide-mobile">
                            <a href="kit_edit.php?id=<?php echo $kit['kit_id']; ?>" class="action-btn edit" onclick="event.stopPropagation();" title="Edit">✏️</a>
                            <a href="kit_delete.php?id=<?php echo $kit['kit_id']; ?>" 
                               class="action-btn delete"
                               onclick="event.stopPropagation(); return confirm('Are you sure you want to delete this jersey?');" title="Delete">🗑️</a>
                            <span class="qr-tooltip">
                                <img src="<?php echo generateKitQRCode($kit['kit_id'], null, 100); ?>" 
                                     class="qr-mini" 
                                     onclick="event.stopPropagation(); openQRModal(<?php echo $kit['kit_id']; ?>, '<?php echo htmlspecialchars($kit['team_name']); ?>');"
                                     alt="QR Code" title="QR Code">
                            </span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (empty($kits)): ?>
            <div class="card" style="text-align: center; padding: 3rem;">
                <h3>No jerseys found</h3>
                <p>Try changing the search filters or <a href="kit_add.php">add a new jersey</a>.</p>
            </div>
        <?php endif; ?>
        
        <!-- Pagination -->
        <div style="margin-top: var(--space-lg);">
            
            <?php if ($total_pages > 1): ?>
                <div class="pagination" style="justify-content: center;">
                    <?php if ($page > 1): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">‹ Previous</a>
                    <?php endif; ?>
                    
                    <?php
                    $start = max(1, $page - 2);
                    $end = min($total_pages, $page + 2);
                    
                    if ($start > 1): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>">1</a>
                        <?php if ($start > 2): ?><span>...</span><?php endif; ?>
                    <?php endif; ?>
                    
                    <?php for ($i = $start; $i <= $end; $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($end < $total_pages): ?>
                        <?php if ($end < $total_pages - 1): ?><span>...</span><?php endif; ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>"><?php echo $total_pages; ?></a>
                    <?php endif; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">Next ›</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <!-- Rows Per Page -->
            <div style="display: flex; justify-content: flex-end; align-items: center; gap: 0.5rem; margin-top: 1rem;">
                <span style="color: var(--primary-text); white-space: nowrap; font-size: 0.875rem; line-height: 1.5; margin: 0;">Rows per page:</span>
                <select id="perPageSelect" onchange="changePerPage(this.value)" style="
                    padding: 0.25rem 0.5rem;
                    border: 1px solid var(--border-color);
                    border-radius: 0.25rem;
                    background: var(--surface);
                    color: var(--primary-text);
                    font-size: 0.875rem;
                    width: auto;
                    min-width: 60px;
                    line-height: 1.5;
                    margin: 0;
                    ">
                    <option value="15" <?php echo $per_page == 15 ? 'selected' : ''; ?>>15</option>
                    <option value="30" <?php echo $per_page == 30 ? 'selected' : ''; ?>>30</option>
                    <option value="50" <?php echo $per_page == 50 ? 'selected' : ''; ?>>50</option>
                </select>
            </div>
        </div>
    </div>

    <!-- QR Code Modal -->
    <div id="qrModal" class="qr-modal">
        <div class="qr-modal-content">
            <span class="qr-modal-close" onclick="closeQRModal()">&times;</span>
            <h3 class="qr-modal-title" id="qrModalTitle">Kit QR Code</h3>
            <img id="qrModalImage" src="" alt="QR Code">
            <div class="qr-modal-info">
                <div>Scan to view kit details</div>
                <div id="qrModalUrl" style="font-size: 0.75rem; margin-top: 0.25rem; word-break: break-all;"></div>
            </div>
            <a id="qrModalDownload" href="" download="" class="qr-modal-download">
                📥 Download QR Code
            </a>
        </div>
    </div>

    <script>
    // Toggle filters panel
    function toggleFilters() {
        const panel = document.getElementById('filtersPanel');
        panel.classList.toggle('active');
    }
    
    // QR Modal functions
    function openQRModal(kitId, teamName) {
        const modal = document.getElementById('qrModal');
        const modalTitle = document.getElementById('qrModalTitle');
        const modalImage = document.getElementById('qrModalImage');
        const modalUrl = document.getElementById('qrModalUrl');
        const modalDownload = document.getElementById('qrModalDownload');
        
        // Auto-detect base URL
        const baseUrl = window.location.protocol + '//' + window.location.host;
        const kitUrl = baseUrl + '/kit_browse_view.php?id=' + kitId;
        const qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' + encodeURIComponent(kitUrl) + '&format=png';
        
        modalTitle.textContent = teamName + ' - QR Code';
        modalImage.src = qrUrl;
        modalUrl.textContent = kitUrl;
        modalDownload.href = qrUrl;
        modalDownload.download = 'kit_' + kitId + '_qr.png';
        
        modal.style.display = 'block';
    }
    
    function closeQRModal() {
        document.getElementById('qrModal').style.display = 'none';
    }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('qrModal');
        if (event.target == modal) {
            closeQRModal();
        }
    }
    
    // Close modal on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeQRModal();
        }
    });
    
    // Sort functionality
    function toggleSort(column) {
        const urlParams = new URLSearchParams(window.location.search);
        const currentSort = urlParams.get('sort');
        const currentDir = urlParams.get('dir') || 'desc';
        
        if (currentSort === column) {
            // Toggle direction
            urlParams.set('dir', currentDir === 'asc' ? 'desc' : 'asc');
        } else {
            // New sort column, start with ascending
            urlParams.set('sort', column);
            urlParams.set('dir', 'asc');
        }
        
        // Reset to page 1 when sorting
        urlParams.set('page', '1');
        
        window.location.search = urlParams.toString();
    }
    
    // Reset filters function
    function resetFilters() {
        document.getElementById('brandFilter').value = '';
        document.getElementById('categoryFilter').value = '';
        document.getElementById('typeFilter').value = '';
        document.getElementById('conditionFilter').value = '';
        // Keep the panel open after reset
    }
    
    // Change per page function
    function changePerPage(perPage) {
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set('per_page', perPage);
        urlParams.set('page', '1'); // Reset to first page
        window.location.search = urlParams.toString();
    }
    
    // Load filter options
    document.addEventListener('DOMContentLoaded', function() {
        const filterTypes = ['brands', 'categories', 'jersey_types', 'conditions'];
        const filterElements = {
            'brands': document.getElementById('brandFilter'),
            'categories': document.getElementById('categoryFilter'), 
            'jersey_types': document.getElementById('typeFilter'),
            'conditions': document.getElementById('conditionFilter')
        };
        
        // Load each filter type
        filterTypes.forEach(type => {
            fetch(`api/lookup.php?type=${type}`)
                .then(response => response.json())
                .then(data => {
                    const select = filterElements[type];
                    if (select && Array.isArray(data)) {
                        data.forEach(item => {
                            const option = document.createElement('option');
                            option.value = item.id;
                            option.textContent = item.name;
                            
                            // Keep current selection
                            const currentValue = new URLSearchParams(window.location.search).get(
                                select.name
                            );
                            if (currentValue == item.id) {
                                option.selected = true;
                            }
                            
                            select.appendChild(option);
                        });
                    }
                })
                .catch(console.error);
        });
        
        // Remove auto-submit from filter dropdowns - only submit when Apply Filters is clicked
        // No event listeners on filter selects anymore
        
        // Submit search on Enter
        document.querySelector('#searchForm input[name="search"]').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('searchForm').submit();
            }
        });
        
        // Auto-submit search after typing (with debounce)
        let searchTimeout;
        document.querySelector('#searchForm input[name="search"]').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                document.getElementById('searchForm').submit();
            }, 500);
        });
    });
    </script>
</body>
</html>
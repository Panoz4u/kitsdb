<?php
require_once 'auth.php';
require_once 'config.php';

requireAdmin();

// Handle filters and search
$search = $_GET['search'] ?? '';
$brand_filter = $_GET['brand'] ?? '';
$category_filter = $_GET['category'] ?? '';
$type_filter = $_GET['type'] ?? '';
$condition_filter = $_GET['condition'] ?? '';
$view_mode = $_GET['view'] ?? 'cards'; // cards or list
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 24;
$offset = ($page - 1) * $per_page;

try {
    $db = getDb();
    
    // Base query for kits
    $where_conditions = ['1=1'];
    $params = [];
    
    // Text search filter
    if (!empty($search)) {
        $where_conditions[] = "(t.name LIKE ? OR k.player_name LIKE ? OR k.season LIKE ? OR k.number LIKE ?)";
        $searchTerm = '%' . $search . '%';
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
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
    
    $where_clause = implode(' AND ', $where_conditions);
    
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
        ORDER BY k.created_at DESC
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
            padding: 0.75rem 1rem;
            background: var(--surface);
            border: 1px solid var(--border-color);
            border-radius: 0.375rem;
            transition: all 0.2s ease;
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
            grid-template-columns: 2fr 1fr 1fr 1fr 1fr 1fr auto;
            gap: 1rem;
            align-items: center;
            min-width: 0;
        }
        
        .list-team {
            font-weight: 600;
            color: var(--primary-text);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
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
            width: 30px;
            height: 30px;
            object-fit: contain;
            border-radius: 0.25rem;
            background: rgba(255,255,255,0.1);
            padding: 2px;
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
    </style>
</head>
<body data-view="<?php echo $view_mode; ?>">
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <a href="dashboard.php" class="logo">KITSDB</a>
            <nav class="nav-menu">
                <a href="kits_list.php" class="nav-link" style="color: var(--highlight-yellow);">Jersey List</a>
                <a href="kit_add.php" class="nav-link">Add Jersey</a>
                <form method="POST" action="logout.php" style="display: inline;">
                    <button type="submit" class="logout-btn">Logout</button>
                </form>
            </nav>
        </div>
    </header>

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
                    <input type="text" 
                           name="search" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Search team, player, season...">
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
                <div class="kit-card">
                    <div class="kit-preview">
                        <?php if ($kit['FMID']): ?>
                            <img src="logo/<?php echo $kit['FMID']; ?>.png" 
                                 alt="<?php echo htmlspecialchars($kit['team_name']); ?>" 
                                 class="team-logo"
                                 onerror="this.style.display='none'">
                        <?php endif; ?>
                        
                        <!-- Dynamic SVG preview -->
                        <div class="svg-preview">
                            <img src="preview/maglia.php?id=<?php echo $kit['kit_id']; ?>" 
                                 alt="Jersey preview" 
                                 style="max-width: 80px; height: auto;"
                                 loading="lazy">
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
                        <a href="kit_edit.php?id=<?php echo $kit['kit_id']; ?>" class="action-btn edit">Edit</a>
                        <a href="kit_delete.php?id=<?php echo $kit['kit_id']; ?>" 
                           class="action-btn delete"
                           onclick="return confirm('Are you sure you want to delete this jersey?')">Delete</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Kit List (List View) -->
        <div class="kit-list">
            <?php foreach ($kits as $kit): ?>
                <div class="kit-list-item">
                    <div class="list-preview">
                        <img src="preview/maglia.php?id=<?php echo $kit['kit_id']; ?>" 
                             alt="Jersey preview" 
                             loading="lazy">
                    </div>
                    
                    <div class="list-content">
                        <div class="list-team">
                            <?php echo htmlspecialchars($kit['team_name'] ?? 'N/A'); ?>
                        </div>
                        
                        <div class="list-player">
                            <?php if ($kit['player_name']): ?>
                                <?php echo htmlspecialchars($kit['player_name']); ?> #<?php echo $kit['number']; ?>
                            <?php else: ?>
                                #<?php echo $kit['number']; ?>
                            <?php endif; ?>
                        </div>
                        
                        <div class="list-detail">
                            <?php echo htmlspecialchars($kit['season']); ?>
                        </div>
                        
                        <div class="list-detail">
                            <?php echo htmlspecialchars($kit['brand_name'] ?? 'N/A'); ?>
                        </div>
                        
                        <div class="list-detail">
                            <?php echo htmlspecialchars($kit['size_name'] ?? 'N/A'); ?>
                        </div>
                        
                        <div class="list-detail">
                            <?php if ($kit['condition_name']): ?>
                                <?php echo htmlspecialchars($kit['condition_name']); ?>
                                <?php for ($i = 0; $i < $kit['condition_stars']; $i++): ?>⭐<?php endfor; ?>
                            <?php else: ?>
                                N/A
                            <?php endif; ?>
                        </div>
                        
                        <div class="list-actions">
                            <a href="kit_edit.php?id=<?php echo $kit['kit_id']; ?>" class="action-btn edit">Edit</a>
                            <a href="kit_delete.php?id=<?php echo $kit['kit_id']; ?>" 
                               class="action-btn delete"
                               onclick="return confirm('Are you sure you want to delete this jersey?')">Delete</a>
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
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
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
    </div>

    <script>
    // Toggle filters panel
    function toggleFilters() {
        const panel = document.getElementById('filtersPanel');
        panel.classList.toggle('active');
    }
    
    // Reset filters function
    function resetFilters() {
        document.getElementById('brandFilter').value = '';
        document.getElementById('categoryFilter').value = '';
        document.getElementById('typeFilter').value = '';
        document.getElementById('conditionFilter').value = '';
        // Keep the panel open after reset
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
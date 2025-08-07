<?php
require_once 'auth.php';
require_once 'config.php';

requireAdmin();

// Gestione filtri e ricerca
$search = $_GET['search'] ?? '';
$brand_filter = $_GET['brand'] ?? '';
$category_filter = $_GET['category'] ?? '';
$type_filter = $_GET['type'] ?? '';
$condition_filter = $_GET['condition'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 24;
$offset = ($page - 1) * $per_page;

try {
    $db = getDb();
    
    // Query base per i kits
    $where_conditions = ['1=1'];
    $params = [];
    
    // Filtro ricerca testuale
    if (!empty($search)) {
        $where_conditions[] = "(t.name LIKE ? OR k.player_name LIKE ? OR k.season LIKE ? OR k.number LIKE ?)";
        $searchTerm = '%' . $search . '%';
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }
    
    // Filtri dropdown
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
    
    // Query per contare il totale
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
    
    // Query principale per i dati
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
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista Maglie - KITSDB</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
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
            text-align: center;
            color: var(--secondary-text);
            margin-bottom: var(--space-md);
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
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <a href="dashboard.php" class="logo">KITSDB</a>
            <nav class="nav-menu">
                <a href="kits_list.php" class="nav-link" style="color: var(--highlight-yellow);">Lista Maglie</a>
                <a href="kit_add.php" class="nav-link">Aggiungi Maglia</a>
                <form method="POST" action="logout.php" style="display: inline;">
                    <button type="submit" class="logout-btn">Logout</button>
                </form>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <div class="container">
        <h1>Lista Maglie</h1>
        
        <!-- Filtri e Ricerca -->
        <form method="GET" class="search-filters" id="filterForm">
            <div class="filter-row">
                <div class="filter-group">
                    <label>Ricerca</label>
                    <input type="text" 
                           name="search" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Cerca squadra, giocatore, stagione..." 
                           class="search-input">
                </div>
                
                <div class="filter-group">
                    <label>Brand</label>
                    <select name="brand" id="brandFilter">
                        <option value="">Tutti i brand</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>Categoria</label>
                    <select name="category" id="categoryFilter">
                        <option value="">Tutte le categorie</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>Tipo</label>
                    <select name="type" id="typeFilter">
                        <option value="">Tutti i tipi</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>Condizione</label>
                    <select name="condition" id="conditionFilter">
                        <option value="">Tutte le condizioni</option>
                    </select>
                </div>
                
                <div class="filter-group" style="display: flex; gap: 0.5rem;">
                    <button type="submit" class="btn btn-primary">Filtra</button>
                    <a href="kits_list.php" class="btn btn-secondary">Reset</a>
                </div>
            </div>
        </form>
        
        <!-- Risultati -->
        <div class="results-info">
            Trovate <?php echo number_format($total_kits); ?> maglie
            <?php if ($total_pages > 1): ?>
                - Pagina <?php echo $page; ?> di <?php echo $total_pages; ?>
            <?php endif; ?>
        </div>
        
        <!-- Lista Kits -->
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
                        
                        <!-- Anteprima SVG dinamica -->
                        <div class="svg-preview">
                            <img src="preview/maglia.php?id=<?php echo $kit['kit_id']; ?>" 
                                 alt="Preview maglia" 
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
                                <div>Numero: <?php echo $kit['number']; ?></div>
                            <?php endif; ?>
                            
                            <div>Stagione: <?php echo htmlspecialchars($kit['season']); ?></div>
                            
                            <?php if ($kit['brand_name']): ?>
                                <div><?php echo htmlspecialchars($kit['brand_name']); ?></div>
                            <?php endif; ?>
                            
                            <?php if ($kit['size_name']): ?>
                                <div>Taglia: <?php echo htmlspecialchars($kit['size_name']); ?></div>
                            <?php endif; ?>
                            
                            <?php if ($kit['condition_name']): ?>
                                <div class="condition-stars">
                                    <?php echo htmlspecialchars($kit['condition_name']); ?>
                                    <?php for ($i = 0; $i < $kit['condition_stars']; $i++): ?>⭐<?php endfor; ?>
                                </div>
                            <?php endif; ?>
                            
                            <div><?php echo $kit['photo_count']; ?> foto</div>
                        </div>
                        
                        <!-- Colori -->
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
                        <a href="kit_edit.php?id=<?php echo $kit['kit_id']; ?>" class="action-btn edit">Modifica</a>
                        <a href="kit_delete.php?id=<?php echo $kit['kit_id']; ?>" 
                           class="action-btn delete"
                           onclick="return confirm('Sei sicuro di voler eliminare questa maglia?')">Elimina</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (empty($kits)): ?>
            <div class="card" style="text-align: center; padding: 3rem;">
                <h3>Nessuna maglia trovata</h3>
                <p>Prova a modificare i filtri di ricerca o <a href="kit_add.php">aggiungi una nuova maglia</a>.</p>
            </div>
        <?php endif; ?>
        
        <!-- Paginazione -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">‹ Precedente</a>
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
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">Successiva ›</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
    // Carica le opzioni dei filtri
    document.addEventListener('DOMContentLoaded', function() {
        const filterTypes = ['brands', 'categories', 'jersey_types', 'conditions'];
        const filterElements = {
            'brands': document.getElementById('brandFilter'),
            'categories': document.getElementById('categoryFilter'), 
            'jersey_types': document.getElementById('typeFilter'),
            'conditions': document.getElementById('conditionFilter')
        };
        
        // Carica ogni tipo di filtro
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
                            
                            // Mantieni selezione corrente
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
        
        // Auto-submit su cambio filtri
        document.querySelectorAll('select').forEach(select => {
            select.addEventListener('change', () => {
                document.getElementById('filterForm').submit();
            });
        });
        
        // Submit su Enter nella ricerca
        document.querySelector('input[name="search"]').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('filterForm').submit();
            }
        });
    });
    </script>
</body>
</html>
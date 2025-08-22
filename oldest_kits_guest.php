<?php
require_once 'config.php';

// Pagination settings (no auth required for guest view)
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 50;
$offset = ($page - 1) * $per_page;

try {
    $db = getDb();
    
    // Count total kits
    $count_sql = "
        SELECT COUNT(*) 
        FROM kits k
        LEFT JOIN teams t ON k.team_id = t.team_id
    ";
    $count_stmt = $db->prepare($count_sql);
    $count_stmt->execute();
    $total_kits = $count_stmt->fetchColumn();
    $total_pages = ceil($total_kits / $per_page);
    
    // Get oldest kits (ordered by season)
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
            s.name as size_name
        FROM kits k
        LEFT JOIN teams t ON k.team_id = t.team_id
        LEFT JOIN brands b ON k.brand_id = b.brand_id
        LEFT JOIN categories c ON k.category_id = c.category_id
        LEFT JOIN jersey_types jt ON k.jersey_type_id = jt.jersey_type_id
        LEFT JOIN conditions co ON k.condition_id = co.condition_id
        LEFT JOIN sizes s ON k.size_id = s.size_id
        ORDER BY k.season ASC, t.name ASC
        LIMIT $per_page OFFSET $offset
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $kits = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $kits = [];
    $total_kits = 0;
    $total_pages = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oldest Kits - KITSDB</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .leaderboard-container {
            margin-top: var(--space-lg);
        }
        
        .leaderboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--space-lg);
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1rem;
            background: var(--surface);
            color: var(--primary-text);
            text-decoration: none;
            border-radius: 0.375rem;
            border: 1px solid var(--border-color);
            transition: all 0.2s ease;
        }
        
        .back-btn:hover {
            background: var(--action-red);
            border-color: var(--action-red);
            color: white;
        }
        
        .kits-list {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .kit-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            background: var(--surface);
            border: 1px solid var(--border-color);
            border-radius: 0.375rem;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        
        .kit-item:hover {
            border-color: var(--action-red);
            box-shadow: 0 2px 8px rgba(222, 60, 75, 0.2);
        }
        
        .kit-rank {
            font-weight: 700;
            color: var(--highlight-yellow);
            min-width: 40px;
            text-align: center;
            font-size: 1.1rem;
        }
        
        .kit-preview {
            width: 50px;
            height: 50px;
            margin: 0 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255,255,255,0.1);
            border-radius: 0.25rem;
            font-size: 1.2rem;
            font-weight: bold;
            color: var(--primary-text);
        }
        
        .kit-info {
            flex: 1;
            min-width: 0;
        }
        
        .kit-team {
            font-weight: 600;
            color: var(--primary-text);
            font-size: 1.1rem;
            margin-bottom: 0.25rem;
        }
        
        .kit-details {
            color: var(--secondary-text);
            font-size: 0.875rem;
        }
        
        .kit-season {
            font-weight: 700;
            color: var(--highlight-yellow);
            font-size: 1.2rem;
            min-width: 80px;
            text-align: center;
        }
        
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
            margin-bottom: var(--space-md);
        }
        
        @media (max-width: 768px) {
            .kit-item {
                padding: 0.75rem;
            }
            
            .kit-preview {
                width: 40px;
                height: 40px;
                margin: 0 0.75rem;
                font-size: 1rem;
            }
            
            .kit-team {
                font-size: 1rem;
            }
            
            .kit-season {
                font-size: 1rem;
                min-width: 60px;
            }
            
            .kit-rank {
                min-width: 30px;
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/public_header.php'; ?>

    <div class="container">
        <div class="leaderboard-container">
            <div class="leaderboard-header">
                <h1>Oldest Kits</h1>
                <a href="dashboard_guest.php" class="back-btn">
                    ← Back to Dashboard
                </a>
            </div>
            
            <div class="results-info">
                Showing <?php echo number_format($total_kits); ?> kits (ordered by season, oldest first)
                <?php if ($total_pages > 1): ?>
                    - Page <?php echo $page; ?> of <?php echo $total_pages; ?>
                <?php endif; ?>
            </div>
            
            <div class="kits-list">
                <?php foreach ($kits as $index => $kit): ?>
                    <div class="kit-item" onclick="window.location.href='kits_browse.php?search=<?php echo urlencode($kit['team_name']); ?>'">
                        <div class="kit-rank"><?php echo ($offset + $index + 1); ?></div>
                        
                        <div class="kit-preview">
                            <?php if ($kit['FMID']): ?>
                                <img src="logo/<?php echo $kit['FMID']; ?>.png" 
                                     alt="<?php echo htmlspecialchars($kit['team_name']); ?>" 
                                     style="width: 100%; height: 100%; object-fit: contain;"
                                     onerror="this.style.display='none'">
                            <?php else: ?>
                                ⚽
                            <?php endif; ?>
                        </div>
                        
                        <div class="kit-info">
                            <div class="kit-team"><?php echo htmlspecialchars($kit['team_name'] ?? 'N/A'); ?></div>
                            <div class="kit-details">
                                <?php if ($kit['jersey_type_name']): ?>
                                    <?php echo htmlspecialchars($kit['jersey_type_name']); ?>
                                <?php endif; ?>
                                <?php if ($kit['category_name']): ?>
                                    • <?php echo htmlspecialchars($kit['category_name']); ?>
                                <?php endif; ?>
                                <?php if ($kit['player_name']): ?>
                                    • <?php echo htmlspecialchars($kit['player_name']); ?>
                                    <?php if ($kit['number']): ?>
                                        #<?php echo $kit['number']; ?>
                                    <?php endif; ?>
                                <?php elseif ($kit['number']): ?>
                                    • #<?php echo $kit['number']; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="kit-season"><?php echo htmlspecialchars($kit['season']); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if (empty($kits)): ?>
                <div class="card" style="text-align: center; padding: 3rem;">
                    <h3>No kits found</h3>
                    <p>No kits in the database.</p>
                </div>
            <?php endif; ?>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>">‹ Previous</a>
                    <?php endif; ?>
                    
                    <?php
                    $start = max(1, $page - 2);
                    $end = min($total_pages, $page + 2);
                    
                    if ($start > 1): ?>
                        <a href="?page=1">1</a>
                        <?php if ($start > 2): ?><span>...</span><?php endif; ?>
                    <?php endif; ?>
                    
                    <?php for ($i = $start; $i <= $end; $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($end < $total_pages): ?>
                        <?php if ($end < $total_pages - 1): ?><span>...</span><?php endif; ?>
                        <a href="?page=<?php echo $total_pages; ?>"><?php echo $total_pages; ?></a>
                    <?php endif; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>">Next ›</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
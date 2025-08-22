<?php
require_once 'config.php';

// Pagination settings (no auth required for guest view)
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 50;
$offset = ($page - 1) * $per_page;

try {
    $db = getDb();
    
    // Count total nations with kits
    $count_sql = "
        SELECT COUNT(DISTINCT n.nation_id) 
        FROM nations n 
        INNER JOIN teams t ON n.nation_id = t.nation_id
        INNER JOIN kits k ON t.team_id = k.team_id
    ";
    $count_stmt = $db->prepare($count_sql);
    $count_stmt->execute();
    $total_nations = $count_stmt->fetchColumn();
    $total_pages = ceil($total_nations / $per_page);
    
    // Get nations with kit counts and season ranges
    $sql = "
        SELECT 
            n.nation_id,
            n.name as nation_name,
            COUNT(k.kit_id) as kit_count,
            MIN(k.season) as oldest_season,
            MAX(k.season) as latest_season
        FROM nations n
        INNER JOIN teams t ON n.nation_id = t.nation_id
        INNER JOIN kits k ON t.team_id = k.team_id
        GROUP BY n.nation_id, n.name
        ORDER BY kit_count DESC
        LIMIT $per_page OFFSET $offset
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $nations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $nations = [];
    $total_nations = 0;
    $total_pages = 0;
}

// Define country flag emojis mapping
$flag_emojis = [
    'Italy' => '🇮🇹',
    'Spain' => '🇪🇸', 
    'France' => '🇫🇷',
    'Germany' => '🇩🇪',
    'England' => '🏴󠁧󠁢󠁥󠁮󠁧󠁿',
    'Brazil' => '🇧🇷',
    'Argentina' => '🇦🇷',
    'Netherlands' => '🇳🇱',
    'Portugal' => '🇵🇹',
    'Belgium' => '🇧🇪',
    'Croatia' => '🇭🇷',
    'Poland' => '🇵🇱',
    'Ukraine' => '🇺🇦',
    'Denmark' => '🇩🇰',
    'Sweden' => '🇸🇪',
    'Norway' => '🇳🇴',
    'Austria' => '🇦🇹',
    'Switzerland' => '🇨🇭',
    'Czech Republic' => '🇨🇿',
    'Slovakia' => '🇸🇰',
    'Hungary' => '🇭🇺',
    'Romania' => '🇷🇴',
    'Bulgaria' => '🇧🇬',
    'Serbia' => '🇷🇸',
    'Slovenia' => '🇸🇮',
    'Bosnia and Herzegovina' => '🇧🇦',
    'Montenegro' => '🇲🇪',
    'North Macedonia' => '🇲🇰',
    'Albania' => '🇦🇱',
    'Greece' => '🇬🇷',
    'Turkey' => '🇹🇷',
    'Russia' => '🇷🇺',
    'Scotland' => '🏴󠁧󠁢󠁳󠁣󠁴󠁿',
    'Wales' => '🏴󠁧󠁢󠁷󠁬󠁳󠁿',
    'Ireland' => '🇮🇪',
    'Northern Ireland' => '🇬🇧',
    'United States' => '🇺🇸',
    'Mexico' => '🇲🇽',
    'Canada' => '🇨🇦',
    'Japan' => '🇯🇵',
    'South Korea' => '🇰🇷',
    'Australia' => '🇦🇺',
    'New Zealand' => '🇳🇿',
    'China' => '🇨🇳',
    'India' => '🇮🇳',
    'Morocco' => '🇲🇦',
    'Egypt' => '🇪🇬',
    'Nigeria' => '🇳🇬',
    'South Africa' => '🇿🇦',
    'Ghana' => '🇬🇭',
    'Senegal' => '🇸🇳',
    'Tunisia' => '🇹🇳',
    'Algeria' => '🇩🇿',
    'Cameroon' => '🇨🇲',
    'Kenya' => '🇰🇪',
    'Chile' => '🇨🇱',
    'Colombia' => '🇨🇴',
    'Ecuador' => '🇪🇨',
    'Peru' => '🇵🇪',
    'Uruguay' => '🇺🇾',
    'Venezuela' => '🇻🇪',
    'Paraguay' => '🇵🇾',
    'Bolivia' => '🇧🇴'
];

function getNationFlag($nationName, $flagEmojis) {
    return $flagEmojis[$nationName] ?? strtoupper(substr($nationName, 0, 2));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nations Leaderboard - KITSDB</title>
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
        
        .nations-list {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .nation-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            background: var(--surface);
            border: 1px solid var(--border-color);
            border-radius: 0.375rem;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        
        .nation-item:hover {
            border-color: var(--action-red);
            box-shadow: 0 2px 8px rgba(222, 60, 75, 0.2);
        }
        
        .nation-rank {
            font-weight: 700;
            color: var(--highlight-yellow);
            min-width: 40px;
            text-align: center;
            font-size: 1.1rem;
        }
        
        .nation-flag {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.25rem;
            background: rgba(255,255,255,0.1);
            margin: 0 1rem;
            font-size: 1.5rem;
        }
        
        .nation-info {
            flex: 1;
            min-width: 0;
        }
        
        .nation-name {
            font-weight: 600;
            color: var(--primary-text);
            font-size: 1.1rem;
            margin-bottom: 0.25rem;
        }
        
        .nation-seasons {
            color: var(--secondary-text);
            font-size: 0.875rem;
        }
        
        .nation-count {
            font-weight: 700;
            color: var(--highlight-yellow);
            font-size: 1.5rem;
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
            .nation-item {
                padding: 0.75rem;
            }
            
            .nation-flag {
                width: 32px;
                height: 32px;
                margin: 0 0.75rem;
                font-size: 1.25rem;
            }
            
            .nation-name {
                font-size: 1rem;
            }
            
            .nation-count {
                font-size: 1.25rem;
                min-width: 60px;
            }
            
            .nation-rank {
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
                <h1>Nations Leaderboard</h1>
                <a href="dashboard_guest.php" class="back-btn">
                    ← Back to Dashboard
                </a>
            </div>
            
            <div class="results-info">
                Showing <?php echo number_format($total_nations); ?> nations with kits
                <?php if ($total_pages > 1): ?>
                    - Page <?php echo $page; ?> of <?php echo $total_pages; ?>
                <?php endif; ?>
            </div>
            
            <div class="nations-list">
                <?php foreach ($nations as $index => $nation): ?>
                    <div class="nation-item" onclick="window.location.href='kits_browse.php?nation=<?php echo $nation['nation_id']; ?>'">
                        <div class="nation-rank"><?php echo ($offset + $index + 1); ?></div>
                        
                        <div class="nation-flag">
                            <?php echo getNationFlag($nation['nation_name'], $flag_emojis); ?>
                        </div>
                        
                        <div class="nation-info">
                            <div class="nation-name"><?php echo htmlspecialchars($nation['nation_name']); ?></div>
                            <div class="nation-seasons">
                                <?php echo htmlspecialchars($nation['oldest_season']); ?> - <?php echo htmlspecialchars($nation['latest_season']); ?>
                            </div>
                        </div>
                        
                        <div class="nation-count"><?php echo $nation['kit_count']; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if (empty($nations)): ?>
                <div class="card" style="text-align: center; padding: 3rem;">
                    <h3>No nations found</h3>
                    <p>No nations with kits in the database.</p>
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
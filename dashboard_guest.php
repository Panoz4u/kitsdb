<?php
require_once 'config.php';

// Get database statistics and leaderboards (no auth required for guest view)
try {
    $db = getDb();
    
    // Count total kits
    $stmt = $db->query("SELECT COUNT(*) as total FROM kits");
    $kit_count = $stmt->fetch()['total'];
    
    // Count kits by category (issued vs worn)
    $stmt = $db->query("SELECT 
        SUM(CASE WHEN k.category_id = 2 THEN 1 ELSE 0 END) as issued_count,
        SUM(CASE WHEN k.category_id = 1 THEN 1 ELSE 0 END) as worn_count
        FROM kits k 
        WHERE k.category_id IS NOT NULL");
    $category_stats = $stmt->fetch();
    $issued_count = $category_stats['issued_count'] ?? 0;
    $worn_count = $category_stats['worn_count'] ?? 0;
    
    // Top 5 teams by kit count
    $stmt = $db->query("SELECT t.name, t.FMID, COUNT(*) as kit_count 
        FROM kits k 
        JOIN teams t ON k.team_id = t.team_id 
        GROUP BY t.team_id, t.name, t.FMID 
        ORDER BY kit_count DESC 
        LIMIT 5");
    $top_teams = $stmt->fetchAll();
    
    // Top 5 nations by kit count
    $stmt = $db->query("SELECT n.name, COUNT(*) as kit_count 
        FROM kits k 
        JOIN teams t ON k.team_id = t.team_id 
        JOIN nations n ON t.nation_id = n.nation_id 
        GROUP BY n.nation_id, n.name 
        ORDER BY kit_count DESC 
        LIMIT 5");
    $top_nations = $stmt->fetchAll();
    
    // Oldest kits (top 5)
    $stmt = $db->query("SELECT k.kit_id, t.name as team_name, t.FMID, k.season, k.player_name, k.number 
        FROM kits k 
        JOIN teams t ON k.team_id = t.team_id 
        ORDER BY k.season ASC 
        LIMIT 5");
    $oldest_kits = $stmt->fetchAll();
    
    // Top 5 brands by kit count
    $stmt = $db->query("SELECT b.name, COUNT(*) as kit_count 
        FROM kits k 
        JOIN brands b ON k.brand_id = b.brand_id 
        GROUP BY b.brand_id, b.name 
        ORDER BY kit_count DESC 
        LIMIT 5");
    $top_brands = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $kit_count = $issued_count = $worn_count = 0;
    $top_teams = $top_nations = $oldest_kits = $top_brands = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KITSDB - Football Kit Collection</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .leaderboard-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: var(--space-md);
            margin-bottom: var(--space-lg);
        }
        
        @media (max-width: 1200px) {
            .leaderboard-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .leaderboard-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .leaderboard-card {
            background: var(--surface);
            padding: 1rem;
            border-radius: 0.5rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            transition: all 0.2s ease;
            cursor: pointer;
            min-height: 220px;
            display: flex;
            flex-direction: column;
        }
        
        .leaderboard-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.4);
        }
        
        .leaderboard-header {
            margin-bottom: 0.75rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .leaderboard-title {
            font-family: var(--font-display);
            font-size: 1rem;
            font-weight: 600;
            color: var(--primary-text);
            margin: 0;
            text-align: center;
        }
        
        .view-more {
            display: block;
            color: var(--action-red);
            font-size: 0.75rem;
            text-decoration: none;
            font-weight: 500;
            text-align: center;
            margin-top: auto;
            padding-top: 0.75rem;
            border-top: 1px solid var(--border-color);
        }
        
        .view-more:hover {
            text-decoration: underline;
        }
        
        .leaderboard-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .leaderboard-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.375rem 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .leaderboard-item:last-child {
            border-bottom: none;
        }
        
        .leaderboard-item-left {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex: 1;
            min-width: 0;
        }
        
        .leaderboard-rank {
            font-weight: 700;
            color: var(--highlight-yellow);
            min-width: 16px;
            text-align: center;
            font-size: 0.875rem;
        }
        
        .leaderboard-logo {
            width: 20px;
            height: 20px;
            object-fit: contain;
            border-radius: 0.25rem;
            background: rgba(255,255,255,0.1);
            padding: 2px;
        }
        
        .leaderboard-name {
            color: var(--primary-text);
            font-weight: 500;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            flex: 1;
            min-width: 0;
            font-size: 0.875rem;
        }
        
        .leaderboard-count {
            font-weight: 700;
            color: var(--highlight-yellow);
            font-size: 0.875rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: var(--space-md);
            margin-bottom: var(--space-lg);
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .stat-card {
            background: var(--surface);
            padding: 1.5rem;
            border-radius: 0.5rem;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.3);
        }
        
        .stat-number {
            font-family: var(--font-display);
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--highlight-yellow);
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: var(--secondary-text);
            font-size: 1rem;
            font-weight: 500;
            margin-bottom: 0.75rem;
        }
        
        .stat-view-all {
            display: block;
            color: var(--action-red);
            font-size: 0.75rem;
            text-decoration: none;
            font-weight: 500;
            margin-top: 0.5rem;
        }
        
        .stat-view-all:hover {
            text-decoration: underline;
        }
        
        .welcome-section {
            text-align: center;
            margin-bottom: var(--space-lg);
            padding: 2rem;
            background: var(--surface);
            border-radius: 0.75rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }
        
        .welcome-title {
            font-family: var(--font-display);
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--highlight-yellow);
            margin-bottom: 1rem;
        }
        
        .welcome-subtitle {
            font-size: 1.2rem;
            color: var(--secondary-text);
            margin-bottom: 0;
        }
    </style>
</head>
<body>
    <?php include 'includes/public_header.php'; ?>

    <!-- Main Content -->
    <div class="container">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <h1 class="welcome-title">Welcome to KITSDB</h1>
            <p class="welcome-subtitle">Explore our collection of football jerseys from around the world</p>
        </div>
        
        <!-- Kit Type Statistics -->
        <h2 style="color: var(--highlight-yellow); margin: var(--space-lg) 0;">Kit Types</h2>
        <div class="stats-grid">
            <div class="stat-card" onclick="window.location.href='kits_browse.php'">
                <div class="stat-number"><?php echo number_format($kit_count); ?></div>
                <div class="stat-label">Total Kits</div>
                <a href="kits_browse.php" class="stat-view-all">View All →</a>
            </div>
            
            <div class="stat-card" onclick="window.location.href='kits_browse.php?category=2'">
                <div class="stat-number"><?php echo number_format($issued_count); ?></div>
                <div class="stat-label">Issued Kits</div>
                <a href="kits_browse.php?category=2" class="stat-view-all">View All →</a>
            </div>
            
            <div class="stat-card" onclick="window.location.href='kits_browse.php?category=1'">
                <div class="stat-number"><?php echo number_format($worn_count); ?></div>
                <div class="stat-label">Worn Kits</div>
                <a href="kits_browse.php?category=1" class="stat-view-all">View All →</a>
            </div>
        </div>
        
        <!-- Leaderboards -->
        <h2 style="color: var(--highlight-yellow); margin: var(--space-lg) 0;">Leaderboards</h2>
        <div class="leaderboard-grid">
            <!-- Top Teams -->
            <div class="leaderboard-card">
                <div class="leaderboard-header">
                    <h3 class="leaderboard-title">Top Teams</h3>
                </div>
                <ul class="leaderboard-list">
                    <?php foreach ($top_teams as $index => $team): ?>
                        <li class="leaderboard-item">
                            <div class="leaderboard-item-left">
                                <div class="leaderboard-rank"><?php echo $index + 1; ?></div>
                                <?php if ($team['FMID']): ?>
                                    <img src="logo/<?php echo $team['FMID']; ?>.png" 
                                         alt="<?php echo htmlspecialchars($team['name']); ?>" 
                                         class="leaderboard-logo"
                                         onerror="this.style.display='none'">
                                <?php endif; ?>
                                <div class="leaderboard-name"><?php echo htmlspecialchars($team['name']); ?></div>
                            </div>
                            <div class="leaderboard-count"><?php echo $team['kit_count']; ?></div>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <a href="teams_leaderboard_guest.php" class="view-more">View More →</a>
            </div>
            
            <!-- Top Nations -->
            <div class="leaderboard-card">
                <div class="leaderboard-header">
                    <h3 class="leaderboard-title">Top Nations</h3>
                </div>
                <ul class="leaderboard-list">
                    <?php foreach ($top_nations as $index => $nation): ?>
                        <li class="leaderboard-item">
                            <div class="leaderboard-item-left">
                                <div class="leaderboard-rank"><?php echo $index + 1; ?></div>
                                <div class="leaderboard-logo" style="background: var(--surface); display: flex; align-items: center; justify-content: center; color: var(--secondary-text); font-size: 0.5rem; font-weight: bold;">
                                    <?php echo strtoupper(substr($nation['name'], 0, 2)); ?>
                                </div>
                                <div class="leaderboard-name"><?php echo htmlspecialchars($nation['name']); ?></div>
                            </div>
                            <div class="leaderboard-count"><?php echo $nation['kit_count']; ?></div>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <a href="nations_leaderboard_guest.php" class="view-more">View More →</a>
            </div>
            
            <!-- Oldest Kits -->
            <div class="leaderboard-card">
                <div class="leaderboard-header">
                    <h3 class="leaderboard-title">Oldest Kits</h3>
                </div>
                <ul class="leaderboard-list">
                    <?php foreach ($oldest_kits as $index => $kit): ?>
                        <li class="leaderboard-item">
                            <div class="leaderboard-item-left">
                                <div class="leaderboard-rank"><?php echo $index + 1; ?></div>
                                <?php if ($kit['FMID']): ?>
                                    <img src="logo/<?php echo $kit['FMID']; ?>.png" 
                                         alt="<?php echo htmlspecialchars($kit['team_name']); ?>" 
                                         class="leaderboard-logo"
                                         onerror="this.style.display='none'">
                                <?php endif; ?>
                                <div class="leaderboard-name">
                                    <?php echo htmlspecialchars($kit['team_name']); ?>
                                    <?php if ($kit['player_name']): ?>
                                        <br><small style="color: var(--secondary-text); font-size: 0.75rem;"><?php echo htmlspecialchars($kit['player_name']); ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="leaderboard-count"><?php echo $kit['season']; ?></div>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <a href="oldest_kits_guest.php" class="view-more">View More →</a>
            </div>
            
            <!-- Top Brands -->
            <div class="leaderboard-card">
                <div class="leaderboard-header">
                    <h3 class="leaderboard-title">Top Brands</h3>
                </div>
                <ul class="leaderboard-list">
                    <?php foreach ($top_brands as $index => $brand): ?>
                        <li class="leaderboard-item">
                            <div class="leaderboard-item-left">
                                <div class="leaderboard-rank"><?php echo $index + 1; ?></div>
                                <div class="leaderboard-name"><?php echo htmlspecialchars($brand['name']); ?></div>
                            </div>
                            <div class="leaderboard-count"><?php echo $brand['kit_count']; ?></div>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <a href="brands_leaderboard_guest.php" class="view-more">View More →</a>
            </div>
        </div>
    </div>

</body>
</html>
<?php
require_once 'auth.php';
require_once 'config.php';

// Requires admin authentication
requireAdmin();

// Get database statistics
try {
    $db = getDb();
    
    // Count kits
    $stmt = $db->query("SELECT COUNT(*) as total FROM kits");
    $kit_count = $stmt->fetch()['total'];
    
    // Count photos
    $stmt = $db->query("SELECT COUNT(*) as total FROM photos");
    $photo_count = $stmt->fetch()['total'];
    
    // Count brands
    $stmt = $db->query("SELECT COUNT(*) as total FROM brands");
    $brand_count = $stmt->fetch()['total'];
    
    // Count teams
    $stmt = $db->query("SELECT COUNT(*) as total FROM teams");
    $team_count = $stmt->fetch()['total'];
    
} catch (PDOException $e) {
    $kit_count = $photo_count = $brand_count = $team_count = 0;
}

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - KITSDB</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: var(--space-lg);
            margin-bottom: var(--space-lg);
        }
        
        .stat-card {
            background: var(--surface);
            padding: 2rem;
            border-radius: 0.75rem;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            transition: all 0.2s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.4);
        }
        
        .stat-number {
            font-family: var(--font-display);
            font-size: 3rem;
            font-weight: 700;
            color: var(--highlight-yellow);
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: var(--secondary-text);
            font-size: 1.1rem;
            font-weight: 600;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--space-md);
        }
        
        .action-card {
            background: var(--surface);
            padding: 1.5rem;
            border-radius: 0.5rem;
            text-align: center;
            transition: all 0.2s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        
        .action-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.3);
        }
        
        .action-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        
        
    </style>
</head>
<body>
    <?php include 'includes/admin_header.php'; ?>

    <!-- Main Content -->
    <div class="container">
        <!-- Quick Actions -->
        <div class="quick-actions" style="margin-top: var(--space-lg);">
            <a href="kit_add.php" class="action-card">
                <div class="action-icon">➕</div>
                <h3>Add New Jersey</h3>
                <p>Add a new jersey to the collection</p>
            </a>
            
            <a href="kits_list.php" class="action-card">
                <div class="action-icon">📋</div>
                <h3>View List</h3>
                <p>Browse all jerseys with advanced filters</p>
            </a>
            
            <a href="nations.php" class="action-card">
                <div class="action-icon">🌍</div>
                <h3>Manage Nations</h3>
                <p>View and manage nations</p>
            </a>
        </div>

        <!-- Overview Section -->
        <h2 style="color: var(--highlight-yellow); margin: var(--space-lg) 0;">Overview</h2>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($kit_count); ?></div>
                <div class="stat-label">Total Jerseys</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($photo_count); ?></div>
                <div class="stat-label">Photos Uploaded</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($brand_count); ?></div>
                <div class="stat-label">Available Brands</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($team_count); ?></div>
                <div class="stat-label">Teams in Database</div>
            </div>
        </div>
    </div>

</body>
</html>
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
        
        .nav-user {
            color: var(--primary-text);
            font-weight: 600;
            margin-right: var(--space-sm);
        }
        
        .mobile-menu-btn {
            display: none;
            flex-direction: column;
            background: transparent;
            border: none;
            cursor: pointer;
            padding: 0.5rem;
        }
        
        .mobile-menu-btn span {
            width: 25px;
            height: 3px;
            background: var(--primary-text);
            margin: 2px 0;
            transition: 0.3s;
        }
        
        .mobile-menu {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            background: var(--surface);
            border-radius: 0.5rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            padding: 1rem;
            min-width: 200px;
            z-index: 1001;
        }
        
        .mobile-menu.active {
            display: block;
        }
        
        .mobile-nav-link {
            display: block;
            color: var(--primary-text);
            text-decoration: none;
            padding: 0.75rem 1rem;
            border-radius: 0.375rem;
            margin-bottom: 0.5rem;
            transition: background 0.2s ease;
        }
        
        .mobile-nav-link:hover {
            background: var(--action-red);
        }
        
        .mobile-logout-btn {
            width: 100%;
            background: var(--action-red);
            color: var(--primary-text);
            border: none;
            padding: 0.75rem;
            border-radius: 0.375rem;
            font-weight: 600;
            cursor: pointer;
        }
        
        @media (max-width: 768px) {
            .header-content {
                flex-direction: row !important;
                justify-content: space-between !important;
                align-items: center !important;
                gap: 0 !important;
                padding: 0 var(--space-sm) !important;
            }
            
            .nav-menu {
                display: none !important;
            }
            
            .mobile-menu-btn {
                display: flex !important;
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
                <span class="nav-user"><?php echo htmlspecialchars($user['username']); ?></span>
                <form method="POST" action="logout.php" style="display: inline;">
                    <button type="submit" class="logout-btn">Logout</button>
                </form>
            </nav>
            
            <!-- Mobile Menu Button -->
            <button class="mobile-menu-btn" onclick="toggleMobileMenu()">
                <span></span>
                <span></span>
                <span></span>
            </button>
            
            <!-- Mobile Menu -->
            <div class="mobile-menu" id="mobileMenu">
                <a href="kits_list.php" class="mobile-nav-link">Jersey List</a>
                <a href="kit_add.php" class="mobile-nav-link">Add Jersey</a>
                <form method="POST" action="logout.php">
                    <button type="submit" class="mobile-logout-btn">Logout</button>
                </form>
            </div>
        </div>
    </header>

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

    <script>
    function toggleMobileMenu() {
        const mobileMenu = document.getElementById('mobileMenu');
        mobileMenu.classList.toggle('active');
    }
    
    // Close menu when clicking outside
    document.addEventListener('click', function(event) {
        const mobileMenu = document.getElementById('mobileMenu');
        const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
        
        if (!mobileMenu.contains(event.target) && !mobileMenuBtn.contains(event.target)) {
            mobileMenu.classList.remove('active');
        }
    });
    </script>
</body>
</html>
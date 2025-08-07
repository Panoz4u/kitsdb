<?php
require_once 'auth.php';
require_once 'config.php';

// Richiede autenticazione admin
requireAdmin();

// Ottieni statistiche del database
try {
    $db = getDb();
    
    // Conta kits
    $stmt = $db->query("SELECT COUNT(*) as total FROM kits");
    $kit_count = $stmt->fetch()['total'];
    
    // Conta foto
    $stmt = $db->query("SELECT COUNT(*) as total FROM photos");
    $photo_count = $stmt->fetch()['total'];
    
    // Conta brands
    $stmt = $db->query("SELECT COUNT(*) as total FROM brands");
    $brand_count = $stmt->fetch()['total'];
    
    // Conta teams
    $stmt = $db->query("SELECT COUNT(*) as total FROM teams");
    $team_count = $stmt->fetch()['total'];
    
} catch (PDOException $e) {
    $kit_count = $photo_count = $brand_count = $team_count = 0;
}

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="it">
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
        
        .welcome-section {
            background: linear-gradient(135deg, var(--action-red), #c13349);
            color: var(--primary-text);
            padding: 2rem;
            border-radius: 0.75rem;
            margin-bottom: var(--space-lg);
            text-align: center;
        }
        
        .welcome-title {
            font-family: var(--font-display);
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            color: var(--primary-text);
        }
        
        .welcome-subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <a href="dashboard.php" class="logo">KITSDB</a>
            <nav class="nav-menu">
                <a href="kits_list.php" class="nav-link">Lista Maglie</a>
                <a href="kit_add.php" class="nav-link">Aggiungi Maglia</a>
                <form method="POST" action="logout.php" style="display: inline;">
                    <button type="submit" class="logout-btn">Logout</button>
                </form>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <div class="container">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <h1 class="welcome-title">Benvenuto, <?php echo htmlspecialchars($user['username']); ?>!</h1>
            <p class="welcome-subtitle">Gestisci la tua collezione di maglie da calcio</p>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($kit_count); ?></div>
                <div class="stat-label">Maglie Totali</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($photo_count); ?></div>
                <div class="stat-label">Foto Caricate</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($brand_count); ?></div>
                <div class="stat-label">Brand Disponibili</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($team_count); ?></div>
                <div class="stat-label">Squadre nel Database</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <h2>Azioni Rapide</h2>
        <div class="quick-actions">
            <a href="kit_add.php" class="action-card">
                <div class="action-icon">➕</div>
                <h3>Aggiungi Nuova Maglia</h3>
                <p>Inserisci una nuova maglia nella collezione</p>
            </a>
            
            <a href="kits_list.php" class="action-card">
                <div class="action-icon">📋</div>
                <h3>Visualizza Lista</h3>
                <p>Sfoglia tutte le maglie con filtri avanzati</p>
            </a>
            
            <a href="nations.php" class="action-card">
                <div class="action-icon">🌍</div>
                <h3>Gestisci Nazioni</h3>
                <p>Visualizza e gestisci le nazioni</p>
            </a>
        </div>
    </div>
</body>
</html>
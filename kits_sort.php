<?php
require_once 'auth.php';
require_once 'config.php';

requireAdmin();

try {
    $db = getDb();
    
    // Complex multi-level sorting query
    $sql = "
        SELECT 
            k.kit_id,
            k.season,
            k.player_name,
            k.number,
            t.name as team_name,
            n.name as nation_name,
            jt.name as jersey_type_name,
            b.name as brand_name,
            c.name as category_name,
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
        LEFT JOIN nations n ON t.nation_id = n.nation_id
        LEFT JOIN brands b ON k.brand_id = b.brand_id
        LEFT JOIN categories c ON k.category_id = c.category_id
        LEFT JOIN jersey_types jt ON k.jersey_type_id = jt.jersey_type_id
        LEFT JOIN conditions co ON k.condition_id = co.condition_id
        LEFT JOIN sizes s ON k.size_id = s.size_id
        LEFT JOIN colors c1 ON k.color1_id = c1.color_id
        LEFT JOIN colors c2 ON k.color2_id = c2.color_id
        LEFT JOIN colors c3 ON k.color3_id = c3.color_id
        ORDER BY 
            n.name ASC,
            t.name ASC,
            k.season ASC,
            CASE jt.name
                WHEN 'Home' THEN 1
                WHEN 'Away' THEN 2
                WHEN 'Third' THEN 3
                WHEN 'GK' THEN 5
                ELSE 4
            END ASC
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $kits = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $kits = [];
    $error_message = "Database error: " . $e->getMessage();
}

$user = getCurrentUser();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sorted Kit List - KITSDB</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .kit-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: var(--space-lg);
            background: var(--surface);
            border-radius: 0.5rem;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }

        .kit-table th,
        .kit-table td {
            padding: var(--space-sm);
            text-align: left;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .kit-table th {
            background: var(--action-red);
            color: var(--primary-text);
            font-weight: 600;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .kit-table tr:hover {
            background: rgba(255,255,255,0.05);
        }

        .kit-id {
            font-weight: bold;
            color: var(--highlight-yellow);
            font-size: 1.1em;
        }

        .color-swatch {
            display: inline-block;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 2px solid rgba(255,255,255,0.3);
            margin-right: 5px;
            vertical-align: middle;
        }

        .condition-stars {
            color: #FFD700;
        }

        .photo-count {
            background: var(--highlight-yellow);
            color: #000;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 0.8em;
            font-weight: bold;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--space-md);
        }

        .sort-info {
            color: var(--secondary-text);
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <?php include 'includes/admin_header.php'; ?>
    
    <main class="main-content">
        <div class="container">
            <div class="table-header">
                <h1>Sorted Kit List</h1>
                <div class="sort-info">
                    Sorted by: Nation → Team → Year → Type (Home, Away, Third, Other, GK)
                </div>
            </div>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($kits)): ?>
                <div class="table-responsive">
                    <table class="kit-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nation</th>
                                <th>Team</th>
                                <th>Season</th>
                                <th>Type</th>
                                <th>Player</th>
                                <th>Number</th>
                                <th>Brand</th>
                                <th>Category</th>
                                <th>Size</th>
                                <th>Condition</th>
                                <th>Colors</th>
                                <th>Photos</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($kits as $kit): ?>
                                <tr>
                                    <td class="kit-id" <?php if ($kit['kit_id'] >= 546): ?>style="color: var(--action-red);"<?php endif; ?>>#<?php echo $kit['kit_id']; ?></td>
                                    <td><?php echo htmlspecialchars($kit['nation_name'] ?: '-'); ?></td>
                                    <td><?php echo htmlspecialchars($kit['team_name'] ?: '-'); ?></td>
                                    <td><?php echo htmlspecialchars($kit['season'] ?: '-'); ?></td>
                                    <td><?php echo htmlspecialchars($kit['jersey_type_name'] ?: '-'); ?></td>
                                    <td><?php echo htmlspecialchars($kit['player_name'] ?: '-'); ?></td>
                                    <td><?php echo htmlspecialchars($kit['number'] ?: '-'); ?></td>
                                    <td><?php echo htmlspecialchars($kit['brand_name'] ?: '-'); ?></td>
                                    <td><?php echo htmlspecialchars($kit['category_name'] ?: '-'); ?></td>
                                    <td><?php echo htmlspecialchars($kit['size_name'] ?: '-'); ?></td>
                                    <td>
                                        <?php if ($kit['condition_stars']): ?>
                                            <span class="condition-stars">
                                                <?php echo str_repeat('★', $kit['condition_stars']); ?>
                                            </span>
                                        <?php else: ?>
                                            <?php echo htmlspecialchars($kit['condition_name'] ?: '-'); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($kit['color1_hex']): ?>
                                            <span class="color-swatch" style="background-color: <?php echo $kit['color1_hex']; ?>" title="<?php echo htmlspecialchars($kit['color1_name']); ?>"></span>
                                        <?php endif; ?>
                                        <?php if ($kit['color2_hex']): ?>
                                            <span class="color-swatch" style="background-color: <?php echo $kit['color2_hex']; ?>" title="<?php echo htmlspecialchars($kit['color2_name']); ?>"></span>
                                        <?php endif; ?>
                                        <?php if ($kit['color3_hex']): ?>
                                            <span class="color-swatch" style="background-color: <?php echo $kit['color3_hex']; ?>" title="<?php echo htmlspecialchars($kit['color3_name']); ?>"></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($kit['photo_count'] > 0): ?>
                                            <span class="photo-count"><?php echo $kit['photo_count']; ?></span>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="stats" style="margin-top: var(--space-lg); text-align: center; color: var(--secondary-text);">
                    Total Kits: <?php echo count($kits); ?>
                </div>
                
            <?php else: ?>
                <div class="empty-state">
                    <p>No kits found in the database.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
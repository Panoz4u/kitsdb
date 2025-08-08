<?php
require_once 'auth.php';
require_once 'config.php';

requireAdmin();

$error = '';
$success = '';
$kit_id = intval($_GET['id'] ?? 0);

if ($kit_id <= 0) {
    header('Location: kits_list.php');
    exit();
}

try {
    $db = getDb();
    
    // Carica dati maglia per conferma
    $stmt = $db->prepare("
        SELECT k.*, t.name as team_name 
        FROM kits k 
        LEFT JOIN teams t ON k.team_id = t.team_id 
        WHERE k.kit_id = ?
    ");
    $stmt->execute([$kit_id]);
    $kit = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$kit) {
        header('Location: kits_list.php');
        exit();
    }
    
    // Carica foto associate
    $photoStmt = $db->prepare("SELECT filename FROM photos WHERE kit_id = ?");
    $photoStmt->execute([$kit_id]);
    $photos = $photoStmt->fetchAll(PDO::FETCH_COLUMN);
    
} catch (PDOException $e) {
    $error = 'Errore nel caricamento dei dati: ' . $e->getMessage();
    $kit = null;
}

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete']) && $kit) {
    try {
        $db->beginTransaction();
        
        // Elimina le foto fisiche
        foreach ($photos as $filename) {
            $filePaths = [
                __DIR__ . '/uploads/front/' . $filename,
                __DIR__ . '/uploads/back/' . $filename,
                __DIR__ . '/uploads/extra/' . $filename
            ];
            
            foreach ($filePaths as $filePath) {
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
        }
        
        // Elimina le foto dal database
        $stmt = $db->prepare("DELETE FROM photos WHERE kit_id = ?");
        $stmt->execute([$kit_id]);
        
        // Elimina il kit
        $stmt = $db->prepare("DELETE FROM kits WHERE kit_id = ?");
        $stmt->execute([$kit_id]);
        
        $db->commit();
        
        // Redirect con messaggio di successo
        header('Location: kits_list.php?deleted=1');
        exit();
        
    } catch (Exception $e) {
        $db->rollBack();
        $error = 'Errore durante l\'eliminazione: ' . $e->getMessage();
    }
}

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elimina Maglia - KITSDB</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .delete-container {
            max-width: 600px;
            margin: 0 auto;
        }
        
        .kit-summary {
            background: var(--surface);
            border-radius: 0.75rem;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 2px solid var(--action-red);
        }
        
        .kit-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .kit-number {
            background: var(--action-red);
            color: var(--primary-text);
            font-size: 2rem;
            font-weight: bold;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .kit-info h2 {
            margin: 0;
            color: var(--highlight-yellow);
            font-family: var(--font-display);
        }
        
        .kit-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .detail-item {
            background: rgba(255,255,255,0.05);
            padding: 1rem;
            border-radius: 0.5rem;
        }
        
        .detail-label {
            font-weight: 600;
            color: var(--secondary-text);
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }
        
        .detail-value {
            color: var(--primary-text);
        }
        
        .warning-box {
            background: rgba(222, 60, 75, 0.1);
            border: 2px solid var(--action-red);
            border-radius: 0.75rem;
            padding: 2rem;
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .warning-icon {
            font-size: 3rem;
            color: var(--action-red);
            margin-bottom: 1rem;
        }
        
        .warning-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--action-red);
            margin-bottom: 1rem;
        }
        
        .warning-text {
            color: var(--secondary-text);
            line-height: 1.6;
        }
        
        .photo-count {
            background: var(--highlight-yellow);
            color: var(--background);
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-block;
            margin-top: 0.5rem;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }
        
        .btn-danger {
            background: var(--action-red);
            color: var(--primary-text);
            padding: 1rem 2rem;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 1.1rem;
        }
        
        .btn-danger:hover {
            background: #c13349;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(222, 60, 75, 0.4);
        }
        
        .btn-cancel {
            background: var(--surface);
            color: var(--primary-text);
            padding: 1rem 2rem;
            border: 2px solid var(--border-color);
            border-radius: 0.5rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
            font-size: 1.1rem;
            display: inline-block;
        }
        
        .btn-cancel:hover {
            border-color: var(--highlight-yellow);
            color: var(--highlight-yellow);
        }
    </style>
</head>
<body>
    <?php include 'includes/admin_header.php'; ?>

    <!-- Main Content -->
    <div class="container">
        <div class="delete-container">
            <h1>Elimina Maglia</h1>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($kit): ?>
                <!-- Kit Summary -->
                <div class="kit-summary">
                    <div class="kit-header">
                        <div class="kit-number"><?php echo $kit['number']; ?></div>
                        <div class="kit-info">
                            <h2><?php echo htmlspecialchars($kit['team_name'] ?? 'N/A'); ?></h2>
                            <?php if ($kit['player_name']): ?>
                                <div style="font-size: 1.2rem; color: var(--secondary-text);">
                                    <?php echo htmlspecialchars($kit['player_name']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="kit-details">
                        <div class="detail-item">
                            <div class="detail-label">Stagione</div>
                            <div class="detail-value"><?php echo htmlspecialchars($kit['season']); ?></div>
                        </div>
                        
                        <?php if ($kit['sleeves']): ?>
                        <div class="detail-item">
                            <div class="detail-label">Maniche</div>
                            <div class="detail-value"><?php echo $kit['sleeves'] === 'Short' ? 'Corte' : 'Lunghe'; ?></div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="detail-item">
                            <div class="detail-label">Creata il</div>
                            <div class="detail-value">
                                <?php echo date('d/m/Y H:i', strtotime($kit['created_at'])); ?>
                            </div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">Ultima modifica</div>
                            <div class="detail-value">
                                <?php echo date('d/m/Y H:i', strtotime($kit['updated_at'])); ?>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($photos)): ?>
                        <div class="photo-count">
                            📷 <?php echo count($photos); ?> foto associate
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($kit['notes']): ?>
                        <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border-color);">
                            <div class="detail-label">Note</div>
                            <div class="detail-value"><?php echo nl2br(htmlspecialchars($kit['notes'])); ?></div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Warning -->
                <div class="warning-box">
                    <div class="warning-icon">⚠️</div>
                    <div class="warning-title">Attenzione: Operazione Irreversibile</div>
                    <div class="warning-text">
                        Stai per eliminare definitivamente questa maglia dal database.<br>
                        <?php if (!empty($photos)): ?>
                            Verranno eliminate anche tutte le <strong><?php echo count($photos); ?> foto</strong> associate.<br>
                        <?php endif; ?>
                        <strong>Questa operazione non può essere annullata.</strong>
                    </div>
                </div>

                <!-- Action Buttons -->
                <form method="POST" onsubmit="return confirm('Sei assolutamente sicuro di voler eliminare questa maglia? Questa operazione è irreversibile.');">
                    <div class="action-buttons">
                        <a href="kits_list.php" class="btn-cancel">Annulla</a>
                        <button type="submit" name="confirm_delete" value="1" class="btn-danger">
                            🗑️ Elimina Definitivamente
                        </button>
                    </div>
                </form>

            <?php else: ?>
                <div class="card" style="text-align: center; padding: 3rem;">
                    <h3>Maglia non trovata</h3>
                    <p>La maglia richiesta non esiste o è stata già eliminata.</p>
                    <a href="kits_list.php" class="btn btn-primary">Torna alla Lista</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
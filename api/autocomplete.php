<?php
header('Content-Type: application/json');
require_once '../auth.php';
require_once '../config.php';

// Richiede autenticazione
requireAuth();

$type = $_GET['type'] ?? '';
$query = $_GET['q'] ?? '';
$response = [];

if (strlen($query) < 2) {
    echo json_encode([]);
    exit();
}

try {
    $db = getDb();
    $searchTerm = '%' . $query . '%';
    
    switch ($type) {
        case 'teams':
            $stmt = $db->prepare("
                SELECT t.team_id as id, t.name, t.FMID, n.name as nation
                FROM teams t 
                LEFT JOIN nations n ON t.nation_id = n.nation_id 
                WHERE t.name LIKE ? 
                ORDER BY t.name 
                LIMIT 10
            ");
            $stmt->execute([$searchTerm]);
            $response = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'players':
            $stmt = $db->prepare("
                SELECT DISTINCT player_name as name, COUNT(*) as kits_count
                FROM kits 
                WHERE player_name IS NOT NULL 
                AND player_name != '' 
                AND player_name LIKE ? 
                GROUP BY player_name 
                ORDER BY kits_count DESC, player_name
                LIMIT 10
            ");
            $stmt->execute([$searchTerm]);
            $response = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'seasons':
            $stmt = $db->prepare("
                SELECT DISTINCT season 
                FROM kits 
                WHERE season LIKE ? 
                ORDER BY season DESC
                LIMIT 10
            ");
            $stmt->execute([$searchTerm]);
            $seasons = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $response = array_map(function($season) {
                return ['name' => $season];
            }, $seasons);
            break;
            
        default:
            http_response_code(400);
            $response = ['error' => 'Invalid autocomplete type'];
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    $response = ['error' => 'Database error'];
}

echo json_encode($response);
?>
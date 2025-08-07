<?php
header('Content-Type: application/json');
require_once '../auth.php';
require_once '../config.php';

// Richiede autenticazione
requireAuth();

$type = $_GET['type'] ?? '';
$response = [];

try {
    $db = getDb();
    
    switch ($type) {
        case 'brands':
            $stmt = $db->query("SELECT brand_id as id, name FROM brands ORDER BY name");
            $response = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'categories':
            $stmt = $db->query("SELECT category_id as id, name FROM categories ORDER BY name");
            $response = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'jersey_types':
            $stmt = $db->query("SELECT jersey_type_id as id, name FROM jersey_types ORDER BY name");
            $response = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'conditions':
            $stmt = $db->query("SELECT condition_id as id, name, stars FROM conditions ORDER BY stars DESC");
            $response = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'sizes':
            $stmt = $db->query("SELECT size_id as id, name FROM sizes ORDER BY 
                CASE name 
                    WHEN 'XS' THEN 1 
                    WHEN 'S' THEN 2 
                    WHEN 'M' THEN 3 
                    WHEN 'L' THEN 4 
                    WHEN 'XL' THEN 5 
                    WHEN 'XXL' THEN 6 
                    ELSE 7 
                END");
            $response = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'colors':
            $stmt = $db->query("SELECT color_id as id, name, hex FROM colors ORDER BY name");
            $response = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'seasons':
            $stmt = $db->query("SELECT season_id as id, name FROM seasons ORDER BY name DESC");
            $response = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'nations':
            $stmt = $db->query("SELECT n.nation_id as id, n.name, c.name as continent 
                               FROM nations n 
                               LEFT JOIN continents c ON n.continent_id = c.continent_id 
                               ORDER BY n.name");
            $response = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'photo_classifications':
            $stmt = $db->query("SELECT classification_id as id, name FROM photo_classifications ORDER BY name");
            $response = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        default:
            http_response_code(400);
            $response = ['error' => 'Invalid lookup type'];
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    $response = ['error' => 'Database error'];
}

echo json_encode($response);
?>
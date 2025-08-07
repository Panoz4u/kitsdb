<?php
require_once '../auth.php';
require_once '../config.php';

requireAuth();

$kit_id = intval($_GET['id'] ?? 0);

if ($kit_id <= 0) {
    http_response_code(400);
    exit('Kit ID non valido');
}

try {
    $db = getDb();
    
    // Carica dati maglia con colori
    $stmt = $db->prepare("
        SELECT 
            k.*,
            c1.hex as color1_hex, c1.name as color1_name,
            c2.hex as color2_hex, c2.name as color2_name, 
            c3.hex as color3_hex, c3.name as color3_name
        FROM kits k
        LEFT JOIN colors c1 ON k.color1_id = c1.color_id
        LEFT JOIN colors c2 ON k.color2_id = c2.color_id
        LEFT JOIN colors c3 ON k.color3_id = c3.color_id
        WHERE k.kit_id = ?
    ");
    $stmt->execute([$kit_id]);
    $kit = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$kit) {
        http_response_code(404);
        exit('Maglia non trovata');
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    exit('Errore database');
}

// Calcola i colori
$primaryColor = $kit['color1_hex'] ?? '#333333';
$secondaryColor = $kit['color2_hex'] ?? '#ffffff';
$accentColor = $kit['color3_hex'] ?? $secondaryColor;

// Calcola il colore del numero (contrasto automatico)
function getTextColor($backgroundColor) {
    if (empty($backgroundColor) || $backgroundColor[0] !== '#') {
        return '#ffffff';
    }
    
    // Rimuovi #
    $hex = ltrim($backgroundColor, '#');
    
    // Converti in RGB
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    
    // Calcola luminanza
    $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
    
    return $luminance > 0.5 ? '#000000' : '#ffffff';
}

$numberColor = getTextColor($primaryColor);

// Headers per SVG
header('Content-Type: image/svg+xml');
header('Cache-Control: max-age=3600');

?>
<svg width="200" height="240" viewBox="0 0 200 240" xmlns="http://www.w3.org/2000/svg">
    <defs>
        <!-- Gradiente per effetto 3D -->
        <linearGradient id="shirtGradient" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" style="stop-color:<?php echo $primaryColor; ?>;stop-opacity:1" />
            <stop offset="100%" style="stop-color:<?php echo $primaryColor; ?>;stop-opacity:0.8" />
        </linearGradient>
        
        <!-- Ombra -->
        <filter id="shadow" x="-20%" y="-20%" width="140%" height="140%">
            <feDropShadow dx="2" dy="2" stdDeviation="3" flood-opacity="0.3"/>
        </filter>
        
        <!-- Pattern per colore secondario -->
        <?php if ($kit['color2_hex'] && $kit['color2_hex'] !== $primaryColor): ?>
        <pattern id="stripePattern" patternUnits="userSpaceOnUse" width="8" height="8">
            <rect width="4" height="8" fill="<?php echo $primaryColor; ?>"/>
            <rect x="4" width="4" height="8" fill="<?php echo $secondaryColor; ?>"/>
        </pattern>
        <?php endif; ?>
    </defs>
    
    <!-- Background circle -->
    <circle cx="100" cy="120" r="90" fill="rgba(0,0,0,0.1)" opacity="0.3"/>
    
    <!-- Corpo della maglia -->
    <path d="M 60 80 
             L 60 200 
             L 140 200 
             L 140 80 
             L 130 70
             L 120 60
             L 80 60
             L 70 70
             Z" 
          fill="url(#shirtGradient)" 
          stroke="rgba(0,0,0,0.2)" 
          stroke-width="1"
          filter="url(#shadow)"/>
    
    <!-- Maniche -->
    <ellipse cx="50" cy="85" rx="12" ry="25" fill="<?php echo $primaryColor; ?>" opacity="0.9"/>
    <ellipse cx="150" cy="85" rx="12" ry="25" fill="<?php echo $primaryColor; ?>" opacity="0.9"/>
    
    <!-- Colletto -->
    <path d="M 85 60 
             L 85 50 
             C 90 45, 110 45, 115 50
             L 115 60
             Z" 
          fill="<?php echo $accentColor ?? $secondaryColor; ?>" 
          stroke="rgba(0,0,0,0.1)" 
          stroke-width="1"/>
    
    <!-- Dettagli colore secondario -->
    <?php if ($kit['color2_hex'] && $kit['color2_hex'] !== $primaryColor): ?>
    <!-- Striscia laterale sinistra -->
    <rect x="65" y="80" width="3" height="120" fill="<?php echo $secondaryColor; ?>"/>
    <!-- Striscia laterale destra -->
    <rect x="132" y="80" width="3" height="120" fill="<?php echo $secondaryColor; ?>"/>
    <!-- Bordo maniche -->
    <ellipse cx="50" cy="110" rx="12" ry="3" fill="<?php echo $secondaryColor; ?>"/>
    <ellipse cx="150" cy="110" rx="12" ry="3" fill="<?php echo $secondaryColor; ?>"/>
    <?php endif; ?>
    
    <!-- Numero della maglia -->
    <?php if ($kit['number']): ?>
    <text x="100" y="140" 
          font-family="Arial, sans-serif" 
          font-size="36" 
          font-weight="bold" 
          text-anchor="middle" 
          fill="<?php echo $numberColor; ?>"
          stroke="rgba(0,0,0,0.3)" 
          stroke-width="0.5">
        <?php echo htmlspecialchars($kit['number']); ?>
    </text>
    <?php endif; ?>
    
    <!-- Nome giocatore (se presente e se c'è spazio) -->
    <?php if ($kit['player_name'] && strlen($kit['player_name']) <= 12): ?>
    <text x="100" y="170" 
          font-family="Arial, sans-serif" 
          font-size="10" 
          font-weight="bold" 
          text-anchor="middle" 
          fill="<?php echo $numberColor; ?>"
          opacity="0.8">
        <?php echo strtoupper(htmlspecialchars($kit['player_name'])); ?>
    </text>
    <?php endif; ?>
    
    <!-- Dettagli aggiuntivi per colore terziario -->
    <?php if ($kit['color3_hex'] && $kit['color3_hex'] !== $primaryColor && $kit['color3_hex'] !== $secondaryColor): ?>
    <!-- Piccoli dettagli decorativi -->
    <rect x="95" y="65" width="10" height="2" fill="<?php echo $accentColor; ?>"/>
    <rect x="85" y="195" width="30" height="2" fill="<?php echo $accentColor; ?>"/>
    <?php endif; ?>
    
    <!-- Effetto lucido -->
    <path d="M 70 70 
             L 80 60
             L 120 60
             L 130 70
             L 125 75
             L 115 65
             L 85 65
             L 75 75
             Z" 
          fill="rgba(255,255,255,0.2)" 
          opacity="0.6"/>
</svg>
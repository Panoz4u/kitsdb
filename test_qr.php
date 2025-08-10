<?php
require_once 'qr_helper.php';

echo '<h1>QR Code Test</h1>';
echo '<p>Testing QR code generation functionality</p>';

// Test kit ID 1
$test_kit_id = 1;

echo '<h2>Kit ID: ' . $test_kit_id . '</h2>';

// Generate kit URL
$kit_url = generateKitURL($test_kit_id);
echo '<p><strong>Kit URL:</strong> ' . htmlspecialchars($kit_url) . '</p>';

// Generate QR code URL
$qr_url = generateKitQRCode($test_kit_id);
echo '<p><strong>QR Code URL:</strong> ' . htmlspecialchars($qr_url) . '</p>';

// Display QR code
echo '<h3>QR Code Image:</h3>';
echo '<img src="' . htmlspecialchars($qr_url) . '" alt="QR Code" style="border: 1px solid #ccc; padding: 10px;">';

// Render full QR code component
echo '<h3>Full QR Code Component:</h3>';
echo '<div style="background: #f5f5f5; padding: 20px; border-radius: 8px;">';
echo renderKitQRCode($test_kit_id);
echo '</div>';

echo '<style>
body { 
    font-family: Arial, sans-serif; 
    margin: 40px; 
    background: #210B2C; 
    color: white; 
}
h1, h2, h3 { color: #DCF763; }
a { color: #DE3C4B; }
.qr-code-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
    background: #2A1A38;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #444;
}
.qr-code {
    border-radius: 0.5rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}
.qr-info small {
    color: #ccc;
    display: block;
    margin-bottom: 0.5rem;
}
.qr-download {
    color: #DE3C4B;
    text-decoration: none;
    padding: 8px 16px;
    border: 1px solid #DE3C4B;
    border-radius: 4px;
    transition: all 0.2s;
}
.qr-download:hover {
    background: #DE3C4B;
    color: white;
}
</style>';
?>
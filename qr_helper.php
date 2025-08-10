<?php
/**
 * QR Code Helper Functions
 * Simple QR code generation for kit URLs
 */

/**
 * Generate a QR code URL using an online service
 * @param string $data The data to encode in the QR code
 * @param int $size Size of the QR code (default 200x200)
 * @return string QR code image URL
 */
function generateQRCodeURL($data, $size = 200) {
    // Using qr-server.com which is a free, reliable QR code API
    $encodedData = urlencode($data);
    return "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data={$encodedData}&format=png";
}

/**
 * Generate the full kit detail URL
 * @param int $kit_id The kit ID
 * @param string $base_url The base URL of the application
 * @return string Full URL to the kit detail page
 */
function generateKitURL($kit_id, $base_url = 'https://kitsdb.panoz4u.com') {
    return $base_url . '/kit_browse_view.php?id=' . intval($kit_id);
}

/**
 * Generate QR code for a specific kit
 * @param int $kit_id The kit ID
 * @param string $base_url The base URL of the application
 * @param int $size Size of the QR code
 * @return string QR code image URL
 */
function generateKitQRCode($kit_id, $base_url = 'https://kitsdb.panoz4u.com', $size = 200) {
    $kit_url = generateKitURL($kit_id, $base_url);
    return generateQRCodeURL($kit_url, $size);
}

/**
 * Get the base URL for the application
 * @return string Base URL
 */
function getBaseURL() {
    // Check if we're running locally for testing
    if (isset($_SERVER['HTTP_HOST']) && (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false)) {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        return $protocol . '://' . $_SERVER['HTTP_HOST'];
    }
    // Production URL
    return 'https://kitsdb.panoz4u.com';
}

/**
 * Create a downloadable QR code HTML element
 * @param int $kit_id The kit ID
 * @param string $base_url The base URL (if null, auto-detect)
 * @param int $size Size of the QR code
 * @param string $css_class CSS class for styling
 * @return string HTML for QR code image with download link
 */
function renderKitQRCode($kit_id, $base_url = null, $size = 200, $css_class = 'qr-code') {
    if ($base_url === null) {
        $base_url = getBaseURL();
    }
    
    $kit_url = generateKitURL($kit_id, $base_url);
    $qr_url = generateKitQRCode($kit_id, $base_url, $size);
    
    return '
    <div class="' . htmlspecialchars($css_class) . '-container">
        <img src="' . htmlspecialchars($qr_url) . '" 
             alt="QR Code for Kit #' . intval($kit_id) . '" 
             class="' . htmlspecialchars($css_class) . '"
             title="Scan to view kit details: ' . htmlspecialchars($kit_url) . '">
        <div class="qr-info">
            <small>Scan to view kit details</small>
            <br>
            <a href="' . htmlspecialchars($qr_url) . '" download="kit_' . intval($kit_id) . '_qr.png" class="qr-download">
                📥 Download QR Code
            </a>
        </div>
    </div>';
}
?>
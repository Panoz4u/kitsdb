<?php
require_once 'auth.php';
requireAuth();

echo "<h2>Simple Upload Test</h2>";
echo "Current working directory: " . __DIR__ . "<br>";
echo "Upload directories:<br>";

$dirs = [
    __DIR__ . '/upload_tmp/',
    __DIR__ . '/uploads/front/',
    __DIR__ . '/uploads/back/',
    __DIR__ . '/uploads/extra/'
];

foreach ($dirs as $dir) {
    echo "- $dir: ";
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
        echo "CREATED ";
    }
    echo file_exists($dir) ? 'EXISTS ' : 'NOT FOUND ';
    echo is_writable($dir) ? 'WRITABLE' : 'NOT WRITABLE';
    echo "<br>";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['test_photo']['name'])) {
    echo "<h3>Processing Upload:</h3>";
    
    $file = $_FILES['test_photo'];
    echo "File name: " . $file['name'] . "<br>";
    echo "File size: " . $file['size'] . "<br>";
    echo "File error: " . $file['error'] . "<br>";
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/upload_tmp/';
        $filename = 'test_' . time() . '_' . basename($file['name']);
        $targetPath = $uploadDir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            echo "✅ SUCCESS: File uploaded to $targetPath<br>";
            
            // Test moving to final destination
            $finalPath = __DIR__ . '/uploads/extra/' . $filename;
            if (rename($targetPath, $finalPath)) {
                echo "✅ SUCCESS: File moved to final location: $finalPath<br>";
            } else {
                echo "❌ ERROR: Could not move to final location<br>";
            }
        } else {
            echo "❌ ERROR: move_uploaded_file failed<br>";
        }
    } else {
        echo "❌ ERROR: Upload error code " . $file['error'] . "<br>";
    }
}
?>

<form method="POST" enctype="multipart/form-data">
    <h3>Test Single File Upload</h3>
    <input type="file" name="test_photo" accept="image/*" required>
    <button type="submit">Upload Test File</button>
</form>
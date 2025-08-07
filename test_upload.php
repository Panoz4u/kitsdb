<?php
require_once 'auth.php';
requireAuth();

echo "<h2>Test Upload Debug</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h3>POST Data Received:</h3>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    echo "<h3>FILES Data Received:</h3>";
    echo "<pre>";
    print_r($_FILES);
    echo "</pre>";
    
    if (!empty($_FILES['photos']['name'][0])) {
        $uploadDir = __DIR__ . '/upload_tmp/';
        
        echo "<h3>Upload Directory: $uploadDir</h3>";
        echo "Directory exists: " . (file_exists($uploadDir) ? 'YES' : 'NO') . "<br>";
        echo "Directory writable: " . (is_writable($uploadDir) ? 'YES' : 'NO') . "<br>";
        
        for ($i = 0; $i < count($_FILES['photos']['name']); $i++) {
            echo "<h4>Processing file $i:</h4>";
            echo "Name: " . $_FILES['photos']['name'][$i] . "<br>";
            echo "Error: " . $_FILES['photos']['error'][$i] . "<br>";
            echo "Size: " . $_FILES['photos']['size'][$i] . "<br>";
            echo "Type: " . $_FILES['photos']['type'][$i] . "<br>";
            echo "Tmp name: " . $_FILES['photos']['tmp_name'][$i] . "<br>";
            
            if ($_FILES['photos']['error'][$i] === UPLOAD_ERR_OK) {
                $tmpName = $_FILES['photos']['tmp_name'][$i];
                $originalName = $_FILES['photos']['name'][$i];
                
                $extension = pathinfo($originalName, PATHINFO_EXTENSION);
                $safeFilename = 'test_' . time() . '_' . $i . '.' . strtolower($extension);
                
                $tempPath = $uploadDir . $safeFilename;
                
                if (move_uploaded_file($tmpName, $tempPath)) {
                    echo "✓ File uploaded successfully to: $tempPath<br>";
                } else {
                    echo "✗ Failed to move uploaded file<br>";
                }
            } else {
                echo "Upload error code: " . $_FILES['photos']['error'][$i] . "<br>";
            }
        }
    } else {
        echo "<h3>No files received in $_FILES['photos']</h3>";
    }
}
?>

<form method="POST" enctype="multipart/form-data">
    <h3>Test Upload Form</h3>
    <input type="file" name="photos[]" multiple accept="image/*" required>
    <button type="submit">Test Upload</button>
</form>
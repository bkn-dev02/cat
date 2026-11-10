<?php
// Debug upload configuration
session_start();

echo "<h1>Debug Upload Configuration</h1>";
echo "<pre>";

echo "=== PHP Configuration ===\n";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "post_max_size: " . ini_get('post_max_size') . "\n";
echo "max_execution_time: " . ini_get('max_execution_time') . "\n";
echo "max_input_time: " . ini_get('max_input_time') . "\n";
echo "memory_limit: " . ini_get('memory_limit') . "\n";
echo "max_file_uploads: " . ini_get('max_file_uploads') . "\n";

echo "\n=== Server Info ===\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "\n";

echo "\n=== GD Library (for image processing) ===\n";
if (extension_loaded('gd')) {
    echo "GD Library: INSTALLED ✓\n";
    $gdInfo = gd_info();
    echo "GD Version: " . $gdInfo['GD Version'] . "\n";
    echo "JPEG Support: " . ($gdInfo['JPEG Support'] ? 'YES ✓' : 'NO ✗') . "\n";
    echo "PNG Support: " . ($gdInfo['PNG Support'] ? 'YES ✓' : 'NO ✗') . "\n";
    echo "GIF Support: " . ($gdInfo['GIF Create Support'] ? 'YES ✓' : 'NO ✗') . "\n";
    echo "WebP Support: " . (isset($gdInfo['WebP Support']) && $gdInfo['WebP Support'] ? 'YES ✓' : 'NO ✗') . "\n";
} else {
    echo "GD Library: NOT INSTALLED ✗\n";
}

echo "\n=== Upload Directory ===\n";
$uploadDir = 'uploads/questions/';
echo "Directory: " . $uploadDir . "\n";
echo "Exists: " . (is_dir($uploadDir) ? 'YES ✓' : 'NO ✗') . "\n";
echo "Writable: " . (is_writable($uploadDir) ? 'YES ✓' : 'NO ✗') . "\n";
echo "Permissions: " . substr(sprintf('%o', fileperms($uploadDir)), -4) . "\n";

echo "\n=== Test Upload Form ===\n";
echo "</pre>";
?>

<form method="POST" enctype="multipart/form-data" style="margin: 20px; padding: 20px; border: 2px solid #ccc; border-radius: 10px; max-width: 500px;">
    <h3>Test Upload Gambar</h3>
    <input type="file" name="test_image" accept="image/*" required style="margin: 10px 0;">
    <br>
    <button type="submit" name="test_upload" style="padding: 10px 20px; background: #4CAF50; color: white; border: none; border-radius: 5px; cursor: pointer;">
        Upload Test
    </button>
</form>

<?php
if (isset($_POST['test_upload']) && isset($_FILES['test_image'])) {
    echo "<pre>";
    echo "\n=== Upload Test Result ===\n";
    
    $file = $_FILES['test_image'];
    echo "File Name: " . $file['name'] . "\n";
    echo "File Size: " . number_format($file['size'] / 1024, 2) . " KB\n";
    echo "File Type: " . $file['type'] . "\n";
    echo "Error Code: " . $file['error'] . "\n";
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        $destination = 'uploads/questions/test_' . time() . '_' . basename($file['name']);
        
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            echo "\n✓ SUCCESS! File uploaded to: " . $destination . "\n";
            echo "File size on disk: " . number_format(filesize($destination) / 1024, 2) . " KB\n";
            
            // Try to get image info
            $imageInfo = getimagesize($destination);
            if ($imageInfo) {
                echo "Image dimensions: " . $imageInfo[0] . " x " . $imageInfo[1] . " pixels\n";
                echo "Image type: " . $imageInfo['mime'] . "\n";
            }
            
            echo "\n<img src='" . $destination . "' style='max-width: 500px; border: 1px solid #ccc; margin-top: 10px;'>\n";
        } else {
            echo "\n✗ FAILED to move uploaded file!\n";
        }
    } else {
        echo "\n✗ Upload Error!\n";
        switch ($file['error']) {
            case UPLOAD_ERR_INI_SIZE:
                echo "Error: File exceeds upload_max_filesize\n";
                break;
            case UPLOAD_ERR_FORM_SIZE:
                echo "Error: File exceeds MAX_FILE_SIZE\n";
                break;
            case UPLOAD_ERR_PARTIAL:
                echo "Error: File was only partially uploaded\n";
                break;
            case UPLOAD_ERR_NO_FILE:
                echo "Error: No file was uploaded\n";
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                echo "Error: Missing temporary folder\n";
                break;
            case UPLOAD_ERR_CANT_WRITE:
                echo "Error: Failed to write file to disk\n";
                break;
            case UPLOAD_ERR_EXTENSION:
                echo "Error: A PHP extension stopped the file upload\n";
                break;
        }
    }
    
    echo "</pre>";
}
?>

<div style="margin: 20px; padding: 20px; background: #f0f0f0; border-radius: 10px; max-width: 500px;">
    <h3>Troubleshooting Tips:</h3>
    <ul>
        <li>Jika upload_max_filesize terlalu kecil, hubungi hosting provider</li>
        <li>Jika directory tidak writable, jalankan: <code>chmod 777 uploads/questions/</code></li>
        <li>Jika GD Library tidak terinstall, hubungi hosting provider</li>
        <li>Jika masih error, cek error log di cPanel atau hosting panel</li>
    </ul>
</div>

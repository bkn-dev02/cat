<?php
/**
 * EMERGENCY FIX - Simple Upload Without Compression
 * 
 * Ganti method uploadImage di QuestionController.php dengan ini:
 */
?>

private function uploadImage($file)
{
    // Simple validation
    $allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    
    if (!in_array($file['type'], $allowed)) {
        $_SESSION['error'] = 'Format gambar tidak didukung.';
        return null;
    }
    
    // Generate filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'question_' . time() . '_' . uniqid() . '.' . $extension;
    $destination = 'uploads/questions/' . $filename;
    
    // Simple move - NO COMPRESSION
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return $filename;
    }
    
    return null;
}

<?php
echo "\n\n=== LANGKAH PERBAIKAN ===\n\n";
echo "1. Buka file: app/Controllers/QuestionController.php\n";
echo "2. Cari method: private function uploadImage\n";
echo "3. Ganti dengan code di atas\n";
echo "4. Save dan test lagi\n\n";
echo "Ini akan menghilangkan semua kompresi dan hanya upload simple.\n";
echo "Gambar akan dikompresi di browser saja (client-side).\n";
?>

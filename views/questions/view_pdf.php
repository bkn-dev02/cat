<?php
// views/questions/view_pdf.php
require_once __DIR__ . '/../../config/init.php'; // Sesuaikan path ke config

// Cek autentikasi
if (!auth()) {
    http_response_code(403);
    exit('Akses ditolak');
}

// Ambil parameter file
$file = $_GET['file'] ?? '';

// Validasi: hanya boleh file PDF
if (empty($file) || pathinfo($file, PATHINFO_EXTENSION) !== 'pdf') {
    http_response_code(400);
    exit('File tidak valid');
}

// Bersihkan path dari karakter berbahaya
$file = basename($file);
$filePath = __DIR__ . '/../../public/uploads/pdfs/' . $file;

// Cek file ada
if (!file_exists($filePath)) {
    http_response_code(404);
    exit('File tidak ditemukan: ' . $filePath);
}

// Header untuk menampilkan PDF di browser (bukan download)
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . basename($filePath) . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: private, max-age=0, must-revalidate');

// Baca dan kirim file
readfile($filePath);
exit;
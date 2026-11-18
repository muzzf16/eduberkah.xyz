<?php
/**
 * Endpoint Unduh Berkas Produk Digital Aman - EduBerkah Digital
 * GET /api/download/:orderId
 * 
 * ATURAN KEAMANAN WAJIB:
 * Hanya pesanan dengan status 'SETTLEMENT' yang boleh mengunduh berkas.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// Ambil Order ID dari parameter GET
$orderId = trim($_GET['orderId'] ?? '');

// Jika kosong, coba ambil dari PATH_INFO
if (empty($orderId) && !empty($_SERVER['PATH_INFO'])) {
    $orderId = trim(basename($_SERVER['PATH_INFO']));
}

if (empty($orderId)) {
    http_response_code(400);
    echo "ID Pesanan wajib disertakan.";
    exit;
}

$order = db_get_order($orderId);

if (!$order) {
    http_response_code(404);
    echo "Pesanan tidak ditemukan. Pastikan URL unduhan Anda sesuai.";
    exit;
}

// ATURAN KEAMANAN: Hanya pesanan berstatus SETTLEMENT yang boleh mengunduh
if (($order['status'] ?? '') !== 'SETTLEMENT') {
    http_response_code(403);
    echo "Akses unduhan ditolak: Pembayaran belum terverifikasi lunas (Settlement). Status saat ini: " . htmlspecialchars($order['status'] ?? 'PENDING');
    exit;
}

global $PRODUCTS_CATALOG;
$productId = $order['product_id'] ?? '';
$product = null;

foreach ($PRODUCTS_CATALOG as $p) {
    if ($p['id'] === $productId) {
        $product = $p;
        break;
    }
}

if (!$product) {
    $product = $PRODUCTS_CATALOG['paket-administrasi-guru'];
}

$fileName = $product['filename'] ?? 'Paket_Administrasi_Guru_Lengkap.xlsx';
$filePath = rtrim(PRODUCTS_DIR, '/\\') . DIRECTORY_SEPARATOR . $fileName;

if (!file_exists($filePath)) {
    http_response_code(404);
    echo "Berkas produk sedang disiapkan oleh tim. Silakan hubungi admin WhatsApp kami.";
    exit;
}

// Bersihkan output buffer sebelum kirim binary file
if (ob_get_level()) {
    ob_end_clean();
}

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($fileName) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filePath));

readfile($filePath);
exit;

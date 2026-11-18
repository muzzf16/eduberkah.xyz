<?php
/**
 * Endpoint Pembuatan Snap Token Midtrans - EduBerkah Digital
 * POST /api/create-snap-token
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

// Hanya izinkan POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metode permintaan tidak diizinkan. Gunakan POST.']);
    exit;
}

// Baca raw JSON input
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true) ?: $_POST;

$nama = trim($data['nama'] ?? '');
$email = trim($data['email'] ?? '');
$productKey = trim($data['produk'] ?? 'paket-administrasi-guru');
$utmSource = trim($data['utm_source'] ?? 'direct');
$utmMedium = trim($data['utm_medium'] ?? '');
$utmCampaign = trim($data['utm_campaign'] ?? '');

// 1. Validasi Nama
if (empty($nama) || strlen($nama) < 2) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Mohon masukkan nama lengkap Anda minimal 2 karakter.']);
    exit;
}

// 2. Validasi Email
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Alamat email tidak valid. File unduhan akan dikirim ke email ini.']);
    exit;
}

// 3. Validasi Produk & KUNCI HARGA DI SERVER
global $PRODUCTS_CATALOG;
$selectedProduct = $PRODUCTS_CATALOG[$productKey] ?? $PRODUCTS_CATALOG['paket-administrasi-guru'];
$lockedPrice = (int) $selectedProduct['price'];

// 4. Buat Order ID Unik
$timestamp = (int) (microtime(true) * 1000);
$randomSuffix = strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
$orderId = "ADM-{$timestamp}-{$randomSuffix}";

// 5. Pastikan Server Key terisi — gagal jelas, jangan panggil Midtrans
// dengan key kosong (akan 401 dan membingungkan).
if (empty(MIDTRANS_SERVER_KEY)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Konfigurasi pembayaran belum lengkap di server. Silakan isi MIDTRANS_SERVER_KEY di file .env server.']);
    exit;
}

// 6. Payload Transaksi untuk Midtrans Snap API
$transactionPayload = [
    'transaction_details' => [
        'order_id' => $orderId,
        'gross_amount' => $lockedPrice
    ],
    'item_details' => [
        [
            'id' => $selectedProduct['id'],
            'price' => $lockedPrice,
            'quantity' => 1,
            'name' => substr($selectedProduct['name'], 0, 50)
        ]
    ],
    'customer_details' => [
        'first_name' => substr($nama, 0, 50),
        'email' => $email
    ],
    'custom_field1' => $utmSource,
    'custom_field2' => $utmMedium,
    'custom_field3' => $utmCampaign
];

// DEBUG SEMENTARA: Hapus baris ini setelah masalah selesai
error_log("[DEBUG] Server Key yang terbaca: " . substr(MIDTRANS_SERVER_KEY, 0, 15) . "...");
error_log("[DEBUG] Panjang Server Key: " . strlen(MIDTRANS_SERVER_KEY));

// 6. Panggil API Midtrans Snap
$authString = base64_encode(MIDTRANS_SERVER_KEY . ':');
$ch = curl_init(MIDTRANS_SNAP_URL);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Content-Type: application/json',
    'Authorization: Basic ' . $authString
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($transactionPayload));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);

$midtransResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    http_response_code(502);
    echo json_encode(['success' => false, 'message' => 'Gagal terhubung ke gateway pembayaran Midtrans: ' . $curlError]);
    exit;
}

$midtransData = json_decode($midtransResponse, true);

// DIAGNOSTIK: catat jawaban mentah Midtrans ke error_log (api/error_log)
// agar penyebab 500 terlihat jelas. Tidak mengubah perilaku ke pembeli.
if ($httpCode !== 201 || empty($midtransData['token'])) {
    error_log('[MIDTRANS] Snap gagal. HTTP ' . $httpCode . ' | Body: ' . substr((string) $midtransResponse, 0, 500));
}

if ($httpCode !== 201 && empty($midtransData['token'])) {
    http_response_code(500);
    $errorMsg = $midtransData['error_messages'][0] ?? $midtransData['status_message'] ?? 'Gagal membuat transaksi di Midtrans.';
    echo json_encode(['success' => false, 'message' => $errorMsg]);
    exit;
}

// 7. Simpan Order ke Database dengan status PENDING
// PENTING: simpan dulu dan pastikan berhasil SEBELUM mengembalikan token.
// Kalau order tidak tersimpan tapi token dikembalikan, pembeli bisa bayar
// tanpa order tercatat — webhook tidak akan menemukan ordernya dan
// download akan 404 ("Pesanan tidak ditemukan").
$orderRecord = [
    'order_id' => $orderId,
    'product_id' => $selectedProduct['id'],
    'product_name' => $selectedProduct['name'],
    'gross_amount' => $lockedPrice,
    'customer_name' => $nama,
    'customer_email' => $email,
    'status' => 'PENDING',
    'payment_type' => null,
    'utm_source' => $utmSource,
    'utm_medium' => $utmMedium,
    'utm_campaign' => $utmCampaign,
    'created_at' => date('c'),
    'updated_at' => date('c')
];

if (!db_save_order($orderRecord)) {
    error_log("[ORDER ERROR] Gagal menyimpan order {$orderId} ke database. Token Midtrans sudah dibuat tapi TIDAK dikembalikan ke pembeli.");
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan pesanan. Silakan coba lagi beberapa saat.']);
    exit;
}

// 8. Kirim kembali token ke Frontend
echo json_encode([
    'success' => true,
    'token' => $midtransData['token'],
    'redirect_url' => $midtransData['redirect_url'] ?? '',
    'order_id' => $orderId,
    'is_production' => MIDTRANS_IS_PRODUCTION,
    'client_key' => MIDTRANS_CLIENT_KEY
]);

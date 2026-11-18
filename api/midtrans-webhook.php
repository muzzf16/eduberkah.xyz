<?php
/**
 * Webhook Notification Handler Midtrans - EduBerkah Digital
 * POST /api/midtrans-webhook
 * 
 * ATURAN KEAMANAN WAJIB:
 * 1. Verifikasi Signature Key menggunakan SHA-512 (order_id + status_code + gross_amount + ServerKey).
 * 2. Hanya status 'settlement' / 'capture' yang valid menandakan pembayaran lunas.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

// Hanya izinkan POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['message' => 'Hanya menerima request POST']);
    exit;
}

$rawInput = file_get_contents('php://input');
$notification = json_decode($rawInput, true);

if (!$notification) {
    http_response_code(400);
    echo json_encode(['message' => 'Payload JSON webhook tidak valid']);
    exit;
}

$order_id = $notification['order_id'] ?? '';
$status_code = $notification['status_code'] ?? '';
$gross_amount = $notification['gross_amount'] ?? '';
$signature_key = $notification['signature_key'] ?? '';
$transaction_status = $notification['transaction_status'] ?? '';
$fraud_status = $notification['fraud_status'] ?? '';
$payment_type = $notification['payment_type'] ?? '';

if (empty($order_id) || empty($status_code) || empty($gross_amount) || empty($signature_key)) {
    http_response_code(400);
    echo json_encode(['message' => 'Payload webhook tidak lengkap.']);
    exit;
}

// 1. Verifikasi SHA-512 Signature
$rawSignature = $order_id . $status_code . $gross_amount . MIDTRANS_SERVER_KEY;
$calculatedSignature = hash('sha512', $rawSignature);

if (!hash_equals($calculatedSignature, $signature_key)) {
    error_log("[SECURITY ALERT] Signature webhook TIDAK VALID untuk order {$order_id}!");
    http_response_code(403);
    echo json_encode(['message' => 'Signature tidak cocok! Permintaan ditolak.']);
    exit;
}

// 2. Evaluasi status transaksi
$finalStatus = 'PENDING';

if ($transaction_status === 'capture') {
    if ($fraud_status === 'accept') {
        $finalStatus = 'SETTLEMENT';
    } elseif ($fraud_status === 'challenge') {
        $finalStatus = 'CHALLENGE';
    }
} elseif ($transaction_status === 'settlement') {
    $finalStatus = 'SETTLEMENT';
} elseif (in_array($transaction_status, ['cancel', 'deny', 'expire'])) {
    $finalStatus = 'CANCELLED';
} elseif ($transaction_status === 'pending') {
    $finalStatus = 'PENDING';
}

// 3. Update database pesanan
db_update_order($order_id, [
    'status' => $finalStatus,
    'payment_type' => $payment_type
]);

// 3b. Jaring pengaman: PATCH tidak membuat baris baru. Kalau order ini
// belum tercatat (misalnya penyimpanan saat checkout sempat gagal),
// buat dari data webhook supaya pembayaran lunas tidak hilang dan
// pembeli tetap bisa mengunduh.
$order = db_get_order($order_id);
if (!$order) {
    error_log("[WEBHOOK] Order {$order_id} belum tercatat, dibuat dari data webhook (status: {$finalStatus}).");
    db_save_order([
        'order_id' => $order_id,
        // String kosong (bukan null) supaya baris ini tetap bisa disimpan
        // meskipun kolom product_id / customer_email di tabel dibuat NOT NULL.
        'product_id' => '',
        'product_name' => '',
        'gross_amount' => $gross_amount,
        'customer_name' => '',
        'customer_email' => '',
        'status' => $finalStatus,
        'payment_type' => $payment_type,
        'created_at' => date('c'),
        'updated_at' => date('c')
    ]);
    $order = db_get_order($order_id);
}

// 4. Aksi saat LUNAS (Settlement)
if ($finalStatus === 'SETTLEMENT') {
    $downloadLink = rtrim(APP_URL, '/') . '/api/download/' . $order_id;
    error_log("========================================================");
    error_log("[PEMBAYARAN LUNAS / SETTLEMENT]");
    error_log("Order ID   : {$order_id}");
    error_log("Nama       : " . ($order['customer_name'] ?? 'Pembeli'));
    error_log("Email      : " . ($order['customer_email'] ?? 'Tidak diketahui'));
    error_log("Nominal    : Rp{$gross_amount}");
    error_log("Link Unduh : {$downloadLink}");
    error_log("Tindakan   : Kirim link download ke email pemesan.");
    error_log("========================================================");
}

// Selalu balas HTTP 200 ke Midtrans
http_response_code(200);
echo json_encode(['status' => 'OK', 'message' => 'Notifikasi berhasil diproses.']);

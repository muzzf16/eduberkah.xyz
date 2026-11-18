<?php
/**
 * Endpoint Permintaan Lead Magnet (Checklist 1 Semester Gratis) - EduBerkah Digital
 * POST /api/lead-magnet
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Hanya menerima request POST']);
    exit;
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true) ?: $_POST;

$nama = trim($data['nama'] ?? 'Bapak/Ibu Guru');
$email = trim($data['email'] ?? '');
$whatsapp = trim($data['whatsapp'] ?? '');

if (empty($email) && empty($whatsapp)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Mohon masukkan alamat email atau nomor WhatsApp Anda.']);
    exit;
}

$leadRecord = [
    'nama' => $nama,
    'email' => strtolower($email),
    'whatsapp' => $whatsapp,
    'created_at' => date('c')
];

db_save_lead($leadRecord);

echo json_encode([
    'success' => true,
    'message' => 'Terima kasih! File Checklist Kelengkapan Administrasi 1 Semester akan kami kirimkan dalam 1x24 jam.'
]);

<?php
/**
 * Health Check Endpoint - EduBerkah Digital
 * GET /health atau GET /api/health
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

$health = [
    'status' => 'OK',
    'timestamp' => date('c'),
    'server' => 'PHP ' . PHP_VERSION,
    'environment' => 'production'
];

if (is_supabase_ready()) {
    $res = supabase_api_call('eduberkah_orders?limit=1&select=order_id');
    if ($res['success']) {
        $health['database'] = 'supabase-ok';
    } else {
        $health['status'] = 'DEGRADED';
        $health['database'] = 'error';
        $health['db_error'] = $res['error'] ?? ('HTTP ' . $res['code']);
        http_response_code(503);
    }
} else {
    $health['database'] = 'local-file-mode';
}

echo json_encode($health, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

<?php
/**
 * Database Adapter (Dual-Mode: Supabase Cloud Database + File Lokal Fallback)
 * EduBerkah Digital
 */

require_once __DIR__ . '/config.php';

// Helper panggilan REST API Supabase
function supabase_api_call($path, $method = 'GET', $data = null, $extraHeaders = []) {
    $url = rtrim(SUPABASE_URL, '/') . '/rest/v1/' . ltrim($path, '/');
    $ch = curl_init($url);

    $headers = [
        'apikey: ' . SUPABASE_SERVICE_ROLE_KEY,
        'Authorization: Bearer ' . SUPABASE_SERVICE_ROLE_KEY,
        'Content-Type: application/json'
    ];
    foreach ($extraHeaders as $h) {
        $headers[] = $h;
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    } elseif ($method === 'PATCH') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        if ($data !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    } elseif ($method !== 'GET') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        error_log("[SUPABASE CURL ERROR]: $error");
        return ['success' => false, 'code' => 500, 'error' => $error];
    }

    $decoded = json_decode($response, true);
    return [
        'success' => ($httpCode >= 200 && $httpCode < 300),
        'code' => $httpCode,
        'data' => $decoded,
        'raw' => $response
    ];
}

// Cek apakah Supabase aktif terkonfigurasi
function is_supabase_ready() {
    return !empty(SUPABASE_URL) && !empty(SUPABASE_SERVICE_ROLE_KEY);
}

// Peringatan sekali per request bila jatuh ke mode file lokal: jangan sampai
// berjalan diam-diam tanpa Supabase di production. Pesanan tetap tersimpan
// (file di home dir, di luar web root), tapi tidak masuk Supabase dan
// /health akan melaporkan 'local-file-mode'.
function warn_file_fallback_once() {
    static $warned = false;
    if (!$warned) {
        $warned = true;
        error_log('[DATABASE] PERINGATAN: Supabase belum dikonfigurasi (SUPABASE_URL / SUPABASE_SERVICE_ROLE_KEY kosong), memakai file lokal. Isi kredensial Supabase di .env agar pesanan tersimpan di cloud.');
    }
}

// File lokal paths
define('LOCAL_ORDERS_FILE', __DIR__ . '/../../orders_db.json');
define('LOCAL_LEADS_FILE', __DIR__ . '/../../leads_db.json');

// Ambil data satu pesanan
function db_get_order($order_id) {
    if (is_supabase_ready()) {
        $res = supabase_api_call("eduberkah_orders?order_id=eq." . urlencode($order_id) . "&select=*");
        if ($res['success'] && !empty($res['data'])) {
            return $res['data'][0];
        }
        return null;
    }

    // Fallback file lokal
    warn_file_fallback_once();
    $file = file_exists(LOCAL_ORDERS_FILE) ? LOCAL_ORDERS_FILE : (__DIR__ . '/orders_db.json');
    if (file_exists($file)) {
        $content = json_decode(file_get_contents($file), true) ?: [];
        return $content[$order_id] ?? null;
    }
    return null;
}

// Simpan atau upsert pesanan
function db_save_order($order) {
    if (is_supabase_ready()) {
        $res = supabase_api_call(
            "eduberkah_orders", 
            "POST", 
            $order, 
            ['Prefer: return=representation,resolution=merge-duplicates']
        );
        return $res['success'];
    }

    // Fallback file lokal
    warn_file_fallback_once();
    $file = file_exists(LOCAL_ORDERS_FILE) ? LOCAL_ORDERS_FILE : (__DIR__ . '/orders_db.json');
    $content = file_exists($file) ? (json_decode(file_get_contents($file), true) ?: []) : [];
    $content[$order['order_id']] = $order;
    return (bool) file_put_contents($file, json_encode($content, JSON_PRETTY_PRINT));
}

// Update sebagian field pesanan
function db_update_order($order_id, $patch) {
    $patch['updated_at'] = date('c');

    if (is_supabase_ready()) {
        $res = supabase_api_call(
            "eduberkah_orders?order_id=eq." . urlencode($order_id), 
            "PATCH", 
            $patch, 
            ['Prefer: return=representation']
        );
        return $res['success'];
    }

    // Fallback file lokal
    $existing = db_get_order($order_id) ?: ['order_id' => $order_id];
    $merged = array_merge($existing, $patch);
    return db_save_order($merged);
}

// Simpan data lead magnet (checklist gratis)
function db_save_lead($lead) {
    if (is_supabase_ready()) {
        $res = supabase_api_call("eduberkah_leads", "POST", $lead);
        return $res['success'];
    }

    // Fallback file lokal
    warn_file_fallback_once();
    $file = file_exists(LOCAL_LEADS_FILE) ? LOCAL_LEADS_FILE : (__DIR__ . '/leads_db.json');
    $leads = file_exists($file) ? (json_decode(file_get_contents($file), true) ?: []) : [];
    $leads[] = $lead;
    return (bool) file_put_contents($file, json_encode($leads, JSON_PRETTY_PRINT));
}

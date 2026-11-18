<?php
/**
 * Konfigurasi Utama Backend PHP - EduBerkah Digital
 * Menyediakan konfigurasi Midtrans, Supabase, Identitas Usaha, dan Katalog 16 Produk.
 */

// Helper untuk membaca file .env sederhana (jika ada)
function load_env_file($path) {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (str_contains($line, '=')) {
            [$key, $val] = explode('=', $line, 2);
            $key = trim($key);
            $val = trim($val);
            // Hapus kutip jika ada
            $val = trim($val, "\"'");
            if (!getenv($key)) {
                putenv("$key=$val");
                $_ENV[$key] = $val;
            }
        }
    }
}

// Cek .env di folder api atau root
load_env_file(__DIR__ . '/.env');
load_env_file(__DIR__ . '/../.env');
load_env_file(__DIR__ . '/../../.env');

// ==============================================================================
// KUNCI DAN KREDENSIAL (Wajib diisi via .env — JANGAN PERNAH hardcode di file ini)
// ==============================================================================
// .env dibaca dari: api/.env, public_html/.env, atau folder di atas public_html
// (di luar web root — paling aman). File .env tidak boleh di-commit ke repo
// dan tidak boleh diunggah ke folder yang bisa diakses browser.
// PERINGATAN: Server Key production yang sebelumnya tertulis di file ini sudah
// terekspos (chat + ZIP di Drive). Putar key itu di dashboard Midtrans
// (hapus yang lama, buat baru), lalu isi yang baru lewat .env.
define('MIDTRANS_SERVER_KEY', getenv('MIDTRANS_SERVER_KEY') ?: '');
define('MIDTRANS_CLIENT_KEY', getenv('MIDTRANS_CLIENT_KEY') ?: '');

// Auto deteksi mode production: jika Server Key berawalan 'Mid-server-', otomatis mode Production
$isServerProd = str_starts_with(MIDTRANS_SERVER_KEY, 'Mid-server-');
define('MIDTRANS_IS_PRODUCTION', getenv('MIDTRANS_IS_PRODUCTION') === 'true' || $isServerProd);

define('MIDTRANS_SNAP_URL', MIDTRANS_IS_PRODUCTION 
    ? 'https://app.midtrans.com/snap/v1/transactions' 
    : 'https://app.sandbox.midtrans.com/snap/v1/transactions');

// Supabase Cloud Database (Project crevarea)
define('SUPABASE_URL', getenv('SUPABASE_URL') ?: 'https://abfhhosbsrmrwbkcteqb.supabase.co');
define('SUPABASE_SERVICE_ROLE_KEY', getenv('SUPABASE_SERVICE_ROLE_KEY') ?: '');

// Identitas Usaha & Kontak
define('BUSINESS_NAME', getenv('BUSINESS_NAME') ?: 'EduBerkah Digital');
define('WHATSAPP_NUMBER', getenv('WHATSAPP_NUMBER') ?: '6281234567890');
define('CONTACT_EMAIL', getenv('CONTACT_EMAIL') ?: 'bantuan@eduberkah.xyz');
define('APP_URL', getenv('APP_URL') ?: 'https://eduberkah.xyz');

// Folder penyimpanan file produk digital
define('PRODUCTS_DIR', realpath(__DIR__ . '/../products_storage') ?: realpath(__DIR__ . '/../../products_storage') ?: (__DIR__ . '/../products_storage'));

// ==============================================================================
// KATALOG 16 PRODUK DIGITAL RESMI (HARGA DIKUNCI DI SERVER)
// ==============================================================================
$PRODUCTS_CATALOG = [
    'paket-administrasi-guru' => [
        'slug' => 'paket-administrasi-guru',
        'id' => 'PAKET-ADM-GURU-01',
        'name' => 'Paket Administrasi Guru Lengkap (1 File Excel 9 Sheet)',
        'price' => 129000,
        'category' => 'Administrasi Utama',
        'format' => '1 File Excel (.xlsx) 9 Sheet • Tanpa Makro',
        'filename' => 'Paket_Administrasi_Guru_Lengkap.xlsx',
        'description' => 'Jurnal mengajar harian, presensi otomatis H/S/I/A, agenda kelas, program semester, rekap nilai ringkas, & cetak A4.',
        'featured' => true
    ],
    'buku-kas-wali-kelas' => [
        'slug' => 'buku-kas-wali-kelas',
        'id' => 'PAKET-KAS-TABUNGAN-02',
        'name' => 'Spreadsheet Kas & Tabungan Siswa Otomatis',
        'price' => 39000,
        'category' => 'Wali Kelas',
        'format' => '1 File Excel (.xlsx) 5 Sheet • Tanpa Makro',
        'filename' => 'Spreadsheet_Kas_dan_Tabungan_Siswa.xlsx',
        'description' => 'Pencatatan iuran kas kelas masuk/keluar, tabungan per anak, dan laporan bulanan A4 siap tanda tangan.',
        'featured' => true
    ],
    'generator-kisi-kisi' => [
        'slug' => 'generator-kisi-kisi',
        'id' => 'PAKET-KISI-KARTU-03',
        'name' => 'Generator Kisi-Kisi & Kartu Soal Otomatis',
        'price' => 49000,
        'category' => 'Asesmen & Ujian',
        'format' => '1 File Excel (.xlsx) 4 Sheet • Tanpa Makro',
        'filename' => 'Generator_Kisi_Kisi_dan_Kartu_Soal.xlsx',
        'description' => 'Input bank soal dan indikator sekali, kisi-kisi dan kartu soal ujian otomatis terbentuk siap cetak.',
        'featured' => true
    ],
    'logbook-konseling-siswa' => [
        'slug' => 'logbook-konseling-siswa',
        'id' => 'PAKET-LOGBOOK-BK-04',
        'name' => 'Logbook Catatan Pelanggaran & Konseling Siswa',
        'price' => 35000,
        'category' => 'Kesiswaan & BK',
        'format' => '1 File Excel (.xlsx) 4 Sheet • Tanpa Makro',
        'filename' => 'Logbook_Catatan_Kasus_dan_Konseling_Siswa.xlsx',
        'description' => 'Rekam kejadian pelanggaran tata tertib, akumulasi poin per siswa, dan portofolio konseling siap cetak.'
    ],
    'tracker-kinerja-asn' => [
        'slug' => 'tracker-kinerja-asn',
        'id' => 'PAKET-TRACKER-RHK-05',
        'name' => 'Tracker & Portofolio Bukti Dukung Kinerja Guru ASN',
        'price' => 49000,
        'category' => 'Kinerja ASN',
        'format' => '1 File Excel (.xlsx) 4 Sheet • Tanpa Makro',
        'filename' => 'Tracker_Bukti_Dukung_Kinerja_Guru_ASN.xlsx',
        'description' => 'Pantau kesiapan berkas upload RHK, sertifikat pelatihan mandiri (PMM), dan link penyimpanan Google Drive.'
    ],
    'analisis-butir-soal' => [
        'slug' => 'analisis-butir-soal',
        'id' => 'PAKET-ANALISIS-SOAL-06',
        'name' => 'Kalkulator & Analisis Butir Soal Ringkas',
        'price' => 59000,
        'category' => 'Evaluasi Guru',
        'format' => '1 File Excel (.xlsx) 4 Sheet • Tanpa Makro',
        'filename' => 'Kalkulator_dan_Analisis_Butir_Soal.xlsx',
        'description' => 'Perhitungan otomatis tingkat kesukaran soal, klasifikasi mudah/sedang/sukar, dan rekomendasi revisi.'
    ],
    'rekap-nilai-rapor-bayangan' => [
        'slug' => 'rekap-nilai-rapor-bayangan',
        'id' => 'PAKET-RAPOR-BAYANGAN-07',
        'name' => 'Rekap Nilai Formatif-Sumatif & Rapor Bayangan',
        'price' => 49000,
        'category' => 'Penilaian Siswa',
        'format' => '1 File Excel (.xlsx) 5 Sheet • Tanpa Makro',
        'filename' => 'Rekap_Nilai_Sumatif_dan_Rapor_Bayangan.xlsx',
        'description' => 'Pengolahan nilai tugas dan ulangan harian dengan slip cetak rapor sisipan/PTS 1 lembar per anak.'
    ],
    'buku-agenda-piket' => [
        'slug' => 'buku-agenda-piket',
        'id' => 'PAKET-AGENDA-PIKET-08',
        'name' => 'Buku Agenda & Jurnal Piket Harian Sekolah',
        'price' => 35000,
        'category' => 'Tugas Tambahan',
        'format' => '1 File Excel (.xlsx) 4 Sheet • Tanpa Makro',
        'filename' => 'Buku_Agenda_dan_Jurnal_Piket_Harian.xlsx',
        'description' => 'Catatan guru berhalangan, jadwal guru pengganti, rekap siswa terlambat, dan laporan mingguan A4.'
    ],
    'rubrik-asesmen-p5' => [
        'slug' => 'rubrik-asesmen-p5',
        'id' => 'PAKET-RUBRIK-P5-09',
        'name' => 'Matriks & Rubrik Asesmen Projek Profil P5',
        'price' => 59000,
        'category' => 'Kurikulum Merdeka',
        'format' => '1 File Excel (.xlsx) 4 Sheet • Tanpa Makro',
        'filename' => 'Matriks_dan_Rubrik_Asesmen_Projek_P5.xlsx',
        'description' => 'Matriks dimensi dan elemen P5 dengan centang ketercapaian BB/MB/BSH/SAB serta rekap rapor projek.'
    ],
    'tracker-kenaikan-pangkat' => [
        'slug' => 'tracker-kenaikan-pangkat',
        'id' => 'PAKET-KENAIKAN-PANGKAT-10',
        'name' => 'Tracker Dokumen Kenaikan Pangkat Mandiri ASN',
        'price' => 49000,
        'category' => 'Kepegawaian ASN',
        'format' => '1 File Excel (.xlsx) 4 Sheet • Tanpa Makro',
        'filename' => 'Tracker_Dokumen_Kenaikan_Pangkat_ASN.xlsx',
        'description' => 'Checklist kelengkapan dokumen fisik dan scan SK Pangkat, PAK, SKP, Serdik, dan estimasi syarat kenaikan.'
    ],
    'notulen-rapat-dewan-guru' => [
        'slug' => 'notulen-rapat-dewan-guru',
        'id' => 'PAKET-NOTULEN-RAPAT-11',
        'name' => 'Format Notulen & Agenda Rapat Dewan Guru',
        'price' => 29000,
        'category' => 'Administrasi Sekolah',
        'format' => 'Microsoft Word (.docx) • Siap Edit',
        'filename' => 'Format_Notulen_dan_Agenda_Rapat_Dewan_Guru.docx',
        'description' => 'Format standar notulensi rapat dewan guru, daftar hadir peserta, dan berita acara kesepakatan sekolah.'
    ],
    'laporan-kombel-guru' => [
        'slug' => 'laporan-kombel-guru',
        'id' => 'PAKET-LAPORAN-KOMBEL-12',
        'name' => 'Format Laporan Komunitas Belajar (Kombel) Guru',
        'price' => 39000,
        'category' => 'Pengembangan Guru',
        'format' => 'Microsoft Word (.docx) • Siap Edit',
        'filename' => 'Format_Laporan_Komunitas_Belajar_Kombel.docx',
        'description' => 'Sistematika laporan siklus inkuiri kombel sekolah: refleksi awal, perencanaan, implementasi, dan evaluasi.'
    ],
    'template-layout-lkpd' => [
        'slug' => 'template-layout-lkpd',
        'id' => 'PAKET-LAYOUT-LKPD-13',
        'name' => 'Paket Template Layout LKPD Siap Pakai (10 Desain)',
        'price' => 39000,
        'category' => 'Bahan Ajar',
        'format' => 'Microsoft Word (.docx) • Ramah Fotokopi',
        'filename' => 'Paket_Template_Layout_LKPD_10_Desain.docx',
        'description' => '10 desain tata letak lembar kerja siswa untuk eksperimen, diskusi, isian, dan pemecahan masalah.'
    ],
    'administrasi-ekskul' => [
        'slug' => 'administrasi-ekskul',
        'id' => 'PAKET-ADM-EKSKUL-14',
        'name' => 'Bundel Administrasi Ekstrakurikuler Sekolah',
        'price' => 49000,
        'category' => 'Pembina Ekskul',
        'format' => 'Microsoft Word (.docx) • Siap Edit',
        'filename' => 'Bundel_Administrasi_Ekstrakurikuler_Sekolah.docx',
        'description' => 'Proposal program kerja tahunan ekskul, rencana anggaran biaya (RAB), dan laporan pertanggungjawaban.'
    ],
    'super-bundle-wali-kelas' => [
        'slug' => 'super-bundle-wali-kelas',
        'id' => 'PAKET-BUNDLE-WALI-15',
        'name' => 'Super Bundle Administrasi Wali Kelas 1 Tahun Ajaran',
        'price' => 99000,
        'category' => 'Super Bundle Hemat',
        'format' => 'Bundel ZIP (Excel & Word Lengkap)',
        'filename' => 'Super_Bundle_Administrasi_Wali_Kelas.zip',
        'description' => 'Paket gabungan hemat: Spreadsheet Kas & Tabungan Siswa + Logbook Konseling Kasus + Format Notulensi Kelas.',
        'featured' => true
    ],
    'paket-desain-canva-guru' => [
        'slug' => 'paket-desain-canva-guru',
        'id' => 'PAKET-CANVA-GURU-16',
        'name' => 'Paket Desain Template Canva untuk Guru (Piagam, Slide, Poster)',
        'price' => 39000,
        'category' => 'Desain Canva',
        'format' => 'Link Canva Siap Edit + 3 PDF Siap Cetak',
        'filename' => 'Paket_Desain_Template_Canva_Guru.zip',
        'description' => 'Template Canva resmi: Piagam penghargaan siswa, slide media ajar interaktif 16:9, dan poster tata tertib kelas.',
        'featured' => true
    ]
];

// Jika diakses langsung oleh browser atau fetch (/api/config)
if (basename($_SERVER['SCRIPT_FILENAME']) === 'config.php' || (isset($_GET['action']) && $_GET['action'] === 'config')) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'businessName' => BUSINESS_NAME,
        'whatsappNumber' => WHATSAPP_NUMBER,
        'contactEmail' => CONTACT_EMAIL,
        'clientKey' => MIDTRANS_CLIENT_KEY,
        'isProduction' => MIDTRANS_IS_PRODUCTION,
        'products' => $PRODUCTS_CATALOG
    ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

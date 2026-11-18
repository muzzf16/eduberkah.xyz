/**
 * Konfigurasi Client-Side Landing Page
 * Nilai default dapat ditimpa secara otomatis oleh endpoint server /api/config.
 */

window.APP_CONFIG = {
  // Nama Usaha
  businessName: 'EduBerkah Digital',

  // Nomor WhatsApp untuk tombol "Tanya via WhatsApp" (Format internasional tanpa '+' atau tanda kurung, misal 6281234567890)
  whatsappNumber: '6281234567890',

  // Email Kontak Bantuan
  contactEmail: 'bantuan@eduberkah.xyz',

  // Client Key Midtrans Sandbox (Ganti dengan Client Key akun Midtrans Anda)
  // AMAN ditaruh di frontend, BERBEDA dengan Server Key yang wajib di server.
  midtransClientKey: 'SB-Mid-client-xxxxxxxxxxxxxxxxxxxxxxxx',

  // Status Environment Midtrans: false untuk Sandbox, true untuk Production
  isProduction: false,

  // Pesan default WhatsApp
  waDefaultMessage: 'Halo, saya mau tanya soal Paket Administrasi Guru.',

  // Katalog Produk Digital (16 Produk Siap Pakai)
  products: {
    'paket-administrasi-guru': {
      slug: 'paket-administrasi-guru',
      name: 'Paket Administrasi Guru Lengkap (1 File Excel 9 Sheet)',
      price: 129000,
      category: 'Administrasi Utama',
      format: '1 File Excel (.xlsx) 9 Sheet • Tanpa Makro'
    },
    'buku-kas-wali-kelas': {
      slug: 'buku-kas-wali-kelas',
      name: 'Spreadsheet Kas & Tabungan Siswa Otomatis',
      price: 39000,
      category: 'Wali Kelas',
      format: '1 File Excel (.xlsx) 5 Sheet • Tanpa Makro'
    },
    'generator-kisi-kisi': {
      slug: 'generator-kisi-kisi',
      name: 'Generator Kisi-Kisi & Kartu Soal Otomatis',
      price: 49000,
      category: 'Asesmen & Ujian',
      format: '1 File Excel (.xlsx) 4 Sheet • Tanpa Makro'
    },
    'logbook-konseling-siswa': {
      slug: 'logbook-konseling-siswa',
      name: 'Logbook Catatan Pelanggaran & Konseling Siswa',
      price: 35000,
      category: 'Kesiswaan & BK',
      format: '1 File Excel (.xlsx) 4 Sheet • Tanpa Makro'
    },
    'tracker-kinerja-asn': {
      slug: 'tracker-kinerja-asn',
      name: 'Tracker & Portofolio Bukti Dukung Kinerja Guru ASN',
      price: 49000,
      category: 'Kinerja ASN',
      format: '1 File Excel (.xlsx) 4 Sheet • Tanpa Makro'
    },
    'analisis-butir-soal': {
      slug: 'analisis-butir-soal',
      name: 'Kalkulator & Analisis Butir Soal Ringkas',
      price: 59000,
      category: 'Evaluasi Guru',
      format: '1 File Excel (.xlsx) 4 Sheet • Tanpa Makro'
    },
    'rekap-nilai-rapor-bayangan': {
      slug: 'rekap-nilai-rapor-bayangan',
      name: 'Rekap Nilai Formatif-Sumatif & Rapor Bayangan',
      price: 49000,
      category: 'Penilaian Siswa',
      format: '1 File Excel (.xlsx) 5 Sheet • Tanpa Makro'
    },
    'buku-agenda-piket': {
      slug: 'buku-agenda-piket',
      name: 'Buku Agenda & Jurnal Piket Harian Sekolah',
      price: 35000,
      category: 'Tugas Tambahan',
      format: '1 File Excel (.xlsx) 4 Sheet • Tanpa Makro'
    },
    'rubrik-asesmen-p5': {
      slug: 'rubrik-asesmen-p5',
      name: 'Matriks & Rubrik Asesmen Projek Profil P5',
      price: 59000,
      category: 'Kurikulum Merdeka',
      format: '1 File Excel (.xlsx) 4 Sheet • Tanpa Makro'
    },
    'tracker-kenaikan-pangkat': {
      slug: 'tracker-kenaikan-pangkat',
      name: 'Tracker Dokumen Kenaikan Pangkat Mandiri ASN',
      price: 49000,
      category: 'Kepegawaian ASN',
      format: '1 File Excel (.xlsx) 4 Sheet • Tanpa Makro'
    },
    'notulen-rapat-dewan-guru': {
      slug: 'notulen-rapat-dewan-guru',
      name: 'Format Notulen & Agenda Rapat Dewan Guru',
      price: 29000,
      category: 'Administrasi Sekolah',
      format: 'Microsoft Word (.docx) • Siap Edit'
    },
    'laporan-kombel-guru': {
      slug: 'laporan-kombel-guru',
      name: 'Format Laporan Komunitas Belajar (Kombel) Guru',
      price: 39000,
      category: 'Pengembangan Guru',
      format: 'Microsoft Word (.docx) • Siap Edit'
    },
    'template-layout-lkpd': {
      slug: 'template-layout-lkpd',
      name: 'Paket Template Layout LKPD Siap Pakai (10 Desain)',
      price: 39000,
      category: 'Bahan Ajar',
      format: 'Microsoft Word (.docx) • Ramah Fotokopi'
    },
    'administrasi-ekskul': {
      slug: 'administrasi-ekskul',
      name: 'Bundel Administrasi Ekstrakurikuler Sekolah',
      price: 49000,
      category: 'Pembina Ekskul',
      format: 'Microsoft Word (.docx) • Siap Edit'
    },
    'super-bundle-wali-kelas': {
      slug: 'super-bundle-wali-kelas',
      name: 'Super Bundle Administrasi Wali Kelas 1 Tahun Ajaran',
      price: 99000,
      category: 'Super Bundle Hemat',
      format: 'Bundel ZIP (Excel & Word Lengkap)'
    },
    'paket-desain-canva-guru': {
      slug: 'paket-desain-canva-guru',
      name: 'Paket Desain Template Canva untuk Guru (Piagam, Slide, Poster)',
      price: 39000,
      category: 'Desain Canva',
      format: 'Link Canva Siap Edit + 3 PDF Siap Cetak'
    }
  }
};

// Ambil konfigurasi dinamis dari server
window.APP_CONFIG_PROMISE = (async function initConfig() {
  try {
    const res = await fetch('/api/config');
    if (res.ok) {
      const serverConfig = await res.json();
      if (serverConfig.businessName) window.APP_CONFIG.businessName = serverConfig.businessName;
      if (serverConfig.whatsappNumber) window.APP_CONFIG.whatsappNumber = serverConfig.whatsappNumber;
      if (serverConfig.contactEmail) window.APP_CONFIG.contactEmail = serverConfig.contactEmail;
      if (serverConfig.products) window.APP_CONFIG.products = serverConfig.products;
      if (serverConfig.clientKey && !serverConfig.clientKey.includes('PLACEHOLDER')) {
        window.APP_CONFIG.midtransClientKey = serverConfig.clientKey;
      }

      // Deteksi otomatis mode Production:
      // 1. Dari flag server isProduction
      // 2. Atau jika Client Key diawali 'Mid-client-' (bukan 'SB-Mid-client-')
      const isClientKeyProd = serverConfig.clientKey && serverConfig.clientKey.startsWith('Mid-client-');
      window.APP_CONFIG.isProduction = !!(serverConfig.isProduction || isClientKeyProd);

      console.log(`[CONFIG] Mode Midtrans: ${window.APP_CONFIG.isProduction ? 'PRODUCTION' : 'SANDBOX'}`);

      // Update link WA dan teks kontak di DOM jika sudah siap
      if (typeof window.applyDynamicConfig === 'function') {
        window.applyDynamicConfig();
      }
    }
  } catch (e) {
    console.log('[CONFIG] Menggunakan konfigurasi statis lokal.');
  }
  return window.APP_CONFIG;
})();

/**
 * Logika Frontend Utama Landing Page Paket Administrasi Guru
 * - Menangani UTM Tracking (utm_source, utm_medium, utm_campaign)
 * - Menangani Modal Form Checkout (Validasi nama & email)
 * - Memanggil endpoint backend /api/create-snap-token
 * - Memanggil Midtrans Snap.js dan menangani 4 callback (Success, Pending, Error, Close)
 * - Menangani Sticky CTA saat scroll melewati hero
 * - Menangani Form Lead Magnet Gratis
 */

// 1. Parsing & Penyimpanan Parameter UTM
function getUTMParams() {
  const urlParams = new URLSearchParams(window.location.search);
  const utmSource = urlParams.get('utm_source') || 'direct';
  const utmMedium = urlParams.get('utm_medium') || '';
  const utmCampaign = urlParams.get('utm_campaign') || '';

  return {
    utm_source: utmSource,
    utm_medium: utmMedium,
    utm_campaign: utmCampaign
  };
}

const currentUTM = getUTMParams();

// 2. Fungsi Pembantu: Format Link WhatsApp Resmi
function getWhatsAppUrl(customText) {
  const cfg = window.APP_CONFIG || {};
  const phone = cfg.whatsappNumber || '6281234567890';
  const text = customText || cfg.waDefaultMessage || 'Halo, saya mau tanya soal Paket Administrasi Guru.';
  return `https://wa.me/${phone}?text=${encodeURIComponent(text)}`;
}

// 3. Terapkan Konfigurasi Dinamis ke Elemen DOM
window.applyDynamicConfig = function() {
  const cfg = window.APP_CONFIG || {};

  // Update Nama Usaha
  document.querySelectorAll('.js-business-name').forEach(el => {
    el.textContent = cfg.businessName;
  });

  // Update Kontak Email
  document.querySelectorAll('.js-contact-email').forEach(el => {
    el.textContent = cfg.contactEmail;
    if (el.tagName === 'A') el.href = `mailto:${cfg.contactEmail}`;
  });

  // Update Link WhatsApp
  document.querySelectorAll('.js-wa-link').forEach(el => {
    el.href = getWhatsAppUrl();
  });

  // Muat script Midtrans Snap.js sesuai mode (Sandbox / Production)
  loadMidtransSnapScript();
};

// 4. Inisialisasi Script Snap.js secara Dinamis & Sinkron
let currentSnapEnvironment = null; // 'sandbox' atau 'production'

function loadMidtransSnapScript(forceProduction) {
  return new Promise((resolve) => {
    const cfg = window.APP_CONFIG || {};

    // Deteksi mode: prioritaskan argumen forceProduction, lalu isProduction, lalu awalan client key
    const isProd = (typeof forceProduction === 'boolean')
      ? forceProduction
      : (cfg.isProduction || (cfg.midtransClientKey && cfg.midtransClientKey.startsWith('Mid-client-')));

    const targetEnv = isProd ? 'production' : 'sandbox';
    const snapUrl = isProd
      ? 'https://app.midtrans.com/snap/snap.js'
      : 'https://app.sandbox.midtrans.com/snap/snap.js';

    // Jika script dengan environment yang sama sudah aktif, tidak perlu reload
    if (currentSnapEnvironment === targetEnv && window.snap) {
      return resolve(window.snap);
    }

    console.log(`[MIDTRANS SNAP] Memuat Snap.js untuk mode: ${targetEnv.toUpperCase()} (${snapUrl})`);

    // Bersihkan script lama jika ada
    const existingScript = document.getElementById('midtrans-snap-script');
    if (existingScript) existingScript.remove();

    // Reset window.snap jika berganti environment
    if (window.snap && currentSnapEnvironment !== targetEnv) {
      try { window.snap = undefined; } catch (e) {}
    }

    const script = document.createElement('script');
    script.id = 'midtrans-snap-script';
    script.src = snapUrl;
    script.setAttribute('data-client-key', cfg.midtransClientKey || '');

    script.onload = () => {
      currentSnapEnvironment = targetEnv;
      console.log(`[MIDTRANS SNAP] Snap.js (${targetEnv}) berhasil dimuat.`);
      resolve(window.snap);
    };

    script.onerror = () => {
      console.warn(`[MIDTRANS SNAP] Gagal memuat script Snap.js (${targetEnv}).`);
      resolve(null);
    };

    document.head.appendChild(script);
  });
}

// 5. Sticky CTA Bar Observer
function setupStickyCTA() {
  const heroSection = document.getElementById('hero');
  const stickyBar = document.getElementById('sticky-cta');

  if (!heroSection || !stickyBar) return;

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach(entry => {
        // Tampilkan sticky bar jika hero sudah tidak terlihat (pengunjung scroll ke bawah)
        if (!entry.isIntersecting) {
          stickyBar.classList.add('visible');
        } else {
          stickyBar.classList.remove('visible');
        }
      });
    },
    { threshold: 0.1 }
  );

  observer.observe(heroSection);
}

// 6. Manajemen Modal Checkout
const modalBackdrop = document.getElementById('checkout-modal');
const checkoutFormContainer = document.getElementById('checkout-form-container');
const checkoutStatusContainer = document.getElementById('checkout-status-container');
const checkoutForm = document.getElementById('checkout-form');
const inputNama = document.getElementById('checkout-nama');
const inputEmail = document.getElementById('checkout-email');
const emailError = document.getElementById('email-error');
const namaError = document.getElementById('nama-error');
const btnSubmitCheckout = document.getElementById('btn-submit-checkout');
const modalSummaryName = document.getElementById('modal-summary-name');
const modalSummaryPrice = document.getElementById('modal-summary-price');
const modalSummaryDetails = document.getElementById('modal-summary-details');

let lastBuyerData = { nama: '', email: '', orderId: '' };
let currentSelectedProduct = {
  slug: 'paket-administrasi-guru',
  name: 'Paket Administrasi Guru Lengkap (1 File Excel 9 Sheet)',
  price: 129000,
  details: 'Termasuk 9 Sheet, Video Panduan, & Update Versi 1.1 Gratis'
};

function formatRupiah(number) {
  return 'Rp' + number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

function openCheckoutModal(productSlug) {
  if (!modalBackdrop) return;

  // Tentukan produk yang dipilih
  const cfg = window.APP_CONFIG || {};
  const products = cfg.products || {};
  const targetSlug = productSlug || 'paket-administrasi-guru';

  if (products[targetSlug]) {
    const p = products[targetSlug];
    currentSelectedProduct = {
      slug: targetSlug,
      name: p.name,
      price: p.price,
      details: p.format || 'Produk Digital Siap Pakai'
    };
  } else {
    currentSelectedProduct = {
      slug: 'paket-administrasi-guru',
      name: 'Paket Administrasi Guru Lengkap (1 File Excel 9 Sheet)',
      price: 129000,
      details: 'Termasuk 9 Sheet, Video Panduan, & Update Versi 1.1 Gratis'
    };
  }

  // Update ringkasan di modal
  if (modalSummaryName) modalSummaryName.textContent = currentSelectedProduct.name;
  if (modalSummaryPrice) modalSummaryPrice.textContent = formatRupiah(currentSelectedProduct.price);
  if (modalSummaryDetails) modalSummaryDetails.textContent = currentSelectedProduct.details;

  // Reset tampilan modal ke form
  checkoutFormContainer.style.display = 'block';
  checkoutStatusContainer.style.display = 'none';
  modalBackdrop.classList.add('active');
  document.body.style.overflow = 'hidden'; // Kunci scroll di belakang
  if (inputNama) inputNama.focus();
}

function closeCheckoutModal() {
  if (!modalBackdrop) return;
  modalBackdrop.classList.remove('active');
  document.body.style.overflow = '';
}

// Validasi Email Sederhana & Kuat
function isValidEmail(email) {
  const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return re.test(String(email).toLowerCase());
}

// Handler Submit Checkout
async function handleCheckoutSubmit(e) {
  e.preventDefault();

  const nama = inputNama.value.trim();
  const email = inputEmail.value.trim();
  let hasError = false;

  // Validasi Nama
  if (nama.length < 2) {
    namaError.classList.add('visible');
    hasError = true;
  } else {
    namaError.classList.remove('visible');
  }

  // Validasi Email
  if (!isValidEmail(email)) {
    emailError.classList.add('visible');
    hasError = true;
  } else {
    emailError.classList.remove('visible');
  }

  if (hasError) return;

  lastBuyerData.nama = nama;
  lastBuyerData.email = email;

  // Tampilkan state loading
  const originalBtnText = btnSubmitCheckout.innerHTML;
  btnSubmitCheckout.disabled = true;
  btnSubmitCheckout.innerHTML = '<span>Menghubungkan ke Midtrans...</span>';

  try {
    // Panggil Backend untuk mendapatkan Snap Token dengan produk terpilih
    const res = await fetch('/api/create-snap-token', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        nama,
        email,
        produk: currentSelectedProduct.slug,
        harga: currentSelectedProduct.price, // Divalidasi dan dikunci ulang di backend
        utm_source: currentUTM.utm_source,
        utm_medium: currentUTM.utm_medium,
        utm_campaign: currentUTM.utm_campaign
      })
    });

    const data = await res.json();

    if (!res.ok || !data.success) {
      throw new Error(data.message || 'Gagal membuat token pembayaran.');
    }

    lastBuyerData.orderId = data.order_id;

    // Pastikan environment Snap script di browser SAMA dengan environment token dari backend
    const isProdFromBackend = typeof data.is_production === 'boolean'
      ? data.is_production
      : (window.APP_CONFIG && window.APP_CONFIG.isProduction);

    const targetEnv = isProdFromBackend ? 'production' : 'sandbox';

    if (currentSnapEnvironment !== targetEnv || !window.snap || !window.snap.pay) {
      console.log(`[CHECKOUT] Sinkronisasi script Snap.js ke mode ${targetEnv}...`);
      await loadMidtransSnapScript(isProdFromBackend);
    }

    // Periksa apakah Snap.js sudah siap
    if (typeof window.snap === 'undefined' || !window.snap.pay) {
      // Jika snap.js diblokir browser atau gagal termuat, gunakan redirect_url resmi Midtrans
      if (data.redirect_url) {
        window.location.href = data.redirect_url;
        return;
      }
      throw new Error('Sistem pembayaran Midtrans Snap belum siap. Silakan refresh halaman.');
    }

    // Panggil Popup Midtrans Snap
    window.snap.pay(data.token, {
      // Callback 1: Sukses Pembayaran
      onSuccess: function(result) {
        console.log('[SNAP SUCCESS]', result);
        showPaymentStatus('success', {
          title: 'Pembayaran Diterima!',
          orderId: data.order_id,
          message: `Terima kasih Bapak/Ibu ${escapeHtml(nama)}. Pembayaran Anda untuk <strong>${escapeHtml(currentSelectedProduct.name)}</strong> telah kami terima. Link download file akan dikirimkan ke email <strong>${escapeHtml(email)}</strong> setelah status terverifikasi lunas (settlement).`
        });
      },

      // Callback 2: Menunggu Pembayaran (Pending - misal QRIS belum scan / VA belum transfer)
      onPending: function(result) {
        console.log('[SNAP PENDING]', result);
        showPaymentStatus('pending', {
          title: 'Menunggu Pembayaran',
          orderId: data.order_id,
          message: `Pesanan Anda untuk <strong>${escapeHtml(currentSelectedProduct.name)}</strong> berhasil dibuat! Silakan selesaikan pembayaran sesuai petunjuk metode yang dipilih (QRIS / Virtual Account). File akan dikirim ke email <strong>${escapeHtml(email)}</strong> setelah pembayaran lunas.`
        });
      },

      // Callback 3: Error / Gagal
      onError: function(result) {
        console.error('[SNAP ERROR]', result);
        showPaymentStatus('error', {
          title: 'Pembayaran Belum Berhasil',
          orderId: data.order_id,
          message: `Terjadi kendala atau transaksi dibatalkan. Saldo Anda belum terpotong. Silakan coba kembali atau gunakan metode pembayaran lainnya.`
        });
      },

      // Callback 4: Jendela Snap Ditutup Pengunjung
      onClose: function() {
        console.log('[SNAP CLOSED]');
        showPaymentStatus('pending', {
          title: 'Jendela Pembayaran Ditutup',
          orderId: data.order_id,
          message: `Anda menutup jendela pembayaran sebelum proses selesai. Ingin melanjutkan pembayaran untuk ${escapeHtml(currentSelectedProduct.name)} atau ada pertanyaan yang perlu kami bantu?`
        });
      }
    });

  } catch (err) {
    alert(err.message || 'Terjadi kesalahan. Silakan coba lagi.');
  } finally {
    btnSubmitCheckout.disabled = false;
    btnSubmitCheckout.innerHTML = originalBtnText;
  }
}

// 7. Render Status Transaksi di Modal
function showPaymentStatus(type, info) {
  checkoutFormContainer.style.display = 'none';
  checkoutStatusContainer.style.display = 'block';

  let iconHtml = '✓';
  let iconClass = 'success';
  if (type === 'pending') {
    iconHtml = '⏳';
    iconClass = 'pending';
  } else if (type === 'error') {
    iconHtml = '✕';
    iconClass = 'error';
  }

  checkoutStatusContainer.innerHTML = `
    <div class="status-card">
      <div class="status-icon ${iconClass}">${iconHtml}</div>
      <h3 class="status-title">${escapeHtml(info.title)}</h3>
      ${info.orderId ? `<div class="order-badge-id">Order ID: ${escapeHtml(info.orderId)}</div>` : ''}
      <p class="status-msg">${info.message}</p>
      
      <div style="display:flex; flex-direction:column; gap:10px; margin-top:20px;">
        ${type !== 'success' ? `
          <button type="button" class="btn btn-primary btn-block" onclick="retryCheckout()">
            Coba Bayar Lagi
          </button>
        ` : ''}
        <a href="${getWhatsAppUrl(`Halo, saya mau konfirmasi status pesanan ${info.orderId || ''}`)}" class="btn btn-wa btn-block" target="_blank" rel="noopener">
          Konfirmasi via WhatsApp
        </a>
        <button type="button" class="btn btn-secondary btn-block" onclick="closeCheckoutModal()">
          Tutup
        </button>
      </div>
    </div>
  `;
}

window.retryCheckout = function() {
  checkoutStatusContainer.style.display = 'none';
  checkoutFormContainer.style.display = 'block';
};

// 8. Lead Magnet Handler (Checklist Kelengkapan Administrasi 1 Halaman)
function setupLeadMagnet() {
  const form = document.getElementById('lead-magnet-form');
  const msgEl = document.getElementById('lead-magnet-msg');
  if (!form || !msgEl) return;

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const emailInput = document.getElementById('lead-email');
    const email = emailInput ? emailInput.value.trim() : '';

    if (!isValidEmail(email)) {
      msgEl.style.display = 'block';
      msgEl.style.color = '#dc2626';
      msgEl.textContent = 'Mohon masukkan alamat email yang valid.';
      return;
    }

    const btnSubmit = form.querySelector('button[type="submit"]');
    const origText = btnSubmit.textContent;
    btnSubmit.disabled = true;
    btnSubmit.textContent = 'Mengirim...';

    try {
      const res = await fetch('/api/lead-magnet', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, nama: 'Bapak/Ibu Guru' })
      });
      const data = await res.json();
      msgEl.style.display = 'block';
      msgEl.style.color = '#16a34a';
      msgEl.textContent = data.message || 'Terima kasih! Checklist 1 Halaman akan dikirim ke email Anda.';
      form.reset();
    } catch (err) {
      msgEl.style.display = 'block';
      msgEl.style.color = '#dc2626';
      msgEl.textContent = 'Terjadi gangguan jaringan. Silakan hubungi WhatsApp kami.';
    } finally {
      btnSubmit.disabled = false;
      btnSubmit.textContent = origText;
    }
  });
}

function escapeHtml(text) {
  if (!text) return '';
  return text
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

// Inisialisasi saat DOM siap
document.addEventListener('DOMContentLoaded', async () => {
  // Tunggu konfigurasi backend selesai dimuat agar tidak terjadi mismatch sandbox/production
  if (window.APP_CONFIG_PROMISE) {
    try {
      await window.APP_CONFIG_PROMISE;
    } catch (e) {}
  }

  window.applyDynamicConfig();
  await loadMidtransSnapScript();
  setupStickyCTA();
  setupLeadMagnet();

  // Pasang event listener untuk semua tombol "Beli Sekarang"
  document.querySelectorAll('.js-btn-buy').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      const productSlug = btn.getAttribute('data-product-slug') || 'paket-administrasi-guru';
      openCheckoutModal(productSlug);
    });
  });

  // Tombol tutup modal
  const btnCloseModal = document.getElementById('modal-close-btn');
  if (btnCloseModal) {
    btnCloseModal.addEventListener('click', closeCheckoutModal);
  }

  // Klik di luar dialog untuk menutup
  if (modalBackdrop) {
    modalBackdrop.addEventListener('click', (e) => {
      if (e.target === modalBackdrop) closeCheckoutModal();
    });
  }

  // Event submit checkout
  if (checkoutForm) {
    checkoutForm.addEventListener('submit', handleCheckoutSubmit);
  }
});

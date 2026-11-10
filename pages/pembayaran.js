// ==============================
// PRODUCT (loaded from DB)
// ==============================
let currentProduct = null;
let durasi = 3;

function formatRp(n) {
  return 'Rp ' + Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

async function loadProduct(id) {
  let p = null;
  try {
    const res = await fetch('../api/products.php?id=' + id);
    if (res.ok) p = await res.json();
  } catch (e) {}

  if (!p) {
    alert('Produk tidak ditemukan di database.');
    return;
  }

  currentProduct = p;

  const priceDay = Math.round(Number(p.price_per_day) || 0);
  const d = parseInt(new URLSearchParams(window.location.search).get('durasi'), 10);
  durasi = Math.min(30, Math.max(1, isNaN(d) ? 3 : d));

  const today = new Date();
  const startDate = new Date(today);
  startDate.setDate(startDate.getDate() + 1);
  const endDate = new Date(startDate);
  endDate.setDate(endDate.getDate() + durasi);

  const months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
  const monthsFull = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

  const fmtDate = (d) => d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
  const fmtDateFull = (d) => d.getDate() + ' ' + monthsFull[d.getMonth()] + ' ' + d.getFullYear();

  const asuransi = 50000;
  const ongkir = 35000;
  const layanan = 15000;
  const voucher = 20000;
  const kodeUnik = 143;
  const subtotal = priceDay * durasi;
  const total = subtotal + asuransi + ongkir + layanan - voucher + kodeUnik;

  const img = (p.images && p.images.length) ? p.images[0].image_url : '';
  const priceStr = formatRp(priceDay);
  const location = p.provider_location || p.location || 'Jakarta Selatan';

  if (img) {
    document.getElementById('bookingThumb').src = img;
    document.getElementById('bookingThumb').alt = p.name;
    document.getElementById('sumThumb').src = img;
    document.getElementById('sumThumb').alt = p.name;
  }

  document.getElementById('bookingName').textContent = p.name;
  document.getElementById('bookingPrice').textContent = priceStr + ' / hari';
  document.getElementById('bookingDuration').textContent = durasi + ' Hari';
  document.getElementById('bookingStart').textContent = fmtDateFull(startDate);
  document.getElementById('bookingEnd').textContent = fmtDateFull(endDate);
  document.getElementById('bookingLocation').textContent = location;

  document.getElementById('sumName').textContent = p.name;
  document.getElementById('sumPrice').textContent = priceStr + ' / hari';
  document.getElementById('sumDurasi').textContent = durasi + ' Hari';
  document.getElementById('sumStart').textContent = fmtDate(startDate);
  document.getElementById('sumEnd').textContent = fmtDate(endDate);
  document.getElementById('sumSubtotalLabel').textContent = 'Subtotal (' + durasi + ' x ' + priceStr + ')';
  document.getElementById('sumSubtotal').textContent = formatRp(subtotal);
  document.getElementById('sumAsuransi').textContent = formatRp(asuransi);
  document.getElementById('sumOngkir').textContent = formatRp(ongkir);
  document.getElementById('sumLayanan').textContent = formatRp(layanan);
  document.getElementById('sumVoucher').textContent = '-' + formatRp(voucher);
  document.getElementById('sumKodeUnik').textContent = formatRp(kodeUnik);
  document.getElementById('sumTotalDisplay').textContent = formatRp(total);
  document.getElementById('detailTotalDisplay').textContent = formatRp(total - kodeUnik);
  document.getElementById('detailKodeUnik').textContent = kodeUnik;
  document.getElementById('instructionAmount').textContent = formatRp(total);
}

const params = new URLSearchParams(window.location.search);
const productId = params.get('id') ? parseInt(params.get('id')) : 1;
loadProduct(productId);

// ==============================
// PAYMENT METHOD SELECTION
// ==============================
const methodCards = document.querySelectorAll('.method-card');
const paymentDetail = document.getElementById('paymentDetail');
const confirmBtn = document.getElementById('confirmBtn');

const uploadCard = document.getElementById('uploadCard');
const instructionsCard = document.getElementById('instructionsCard');

methodCards.forEach(card => {
  card.addEventListener('click', () => {
    methodCards.forEach(c => c.classList.remove('selected'));
    card.classList.add('selected');

    const method = card.dataset.method;

    paymentDetail.classList.toggle('active', method === 'bank_transfer');

    if (uploadCard) uploadCard.style.display = (method === 'bank_transfer') ? '' : 'none';
    if (instructionsCard) instructionsCard.style.display = (method === 'bank_transfer') ? '' : 'none';
    if (confirmBtn) confirmBtn.style.display = (method === 'bank_transfer') ? '' : 'none';
  });
});

// ==============================
// CONFIRM PAYMENT → CREATE BOOKING
// ==============================
confirmBtn.addEventListener('click', async () => {
  if (!currentProduct) {
    alert('Produk belum dimuat.');
    return;
  }

  confirmBtn.disabled = true;
  confirmBtn.classList.add('loading');

  const ymd = (d) => d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
  const today = new Date();
  const startDate = new Date(today);
  startDate.setDate(startDate.getDate() + 1);
  const endDate = new Date(startDate);
  endDate.setDate(endDate.getDate() + durasi);

  try {
    const res = await fetch('../api/bookings.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        items: [{ product_id: currentProduct.id, quantity: 1 }],
        start_date: ymd(startDate),
        end_date: ymd(endDate),
        notes: ''
      })
    });
    const data = await res.json();

    if (res.status === 401) {
      window.location.href = 'login.php';
      return;
    }
    if (!res.ok) throw new Error(data.error || 'Gagal membuat booking');

    window.location.href = 'booking.html?success=' + encodeURIComponent(data.booking_code);
  } catch (e) {
    alert(e.message);
    confirmBtn.disabled = false;
    confirmBtn.classList.remove('loading');
  }
});

// ==============================
// COPY ACCOUNT NUMBER
// ==============================
const copyBtn = document.getElementById('copyBtn');
if (copyBtn) {
  copyBtn.addEventListener('click', () => {
    const account = '1234567890123456';
    navigator.clipboard.writeText(account).then(() => {
      copyBtn.classList.add('copied');
      copyBtn.innerHTML = `
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><polyline points="20 6 9 17 4 12"/></svg>
        Tersalin
      `;
      setTimeout(() => {
        copyBtn.classList.remove('copied');
        copyBtn.innerHTML = `
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
          Salin
        `;
      }, 2500);
    });
  });
}

// ==============================
// FILE UPLOAD (DRAG & DROP + BROWSE)
// ==============================
const uploadZone = document.getElementById('uploadZone');
const fileInput = document.getElementById('fileInput');
const browseBtn = document.getElementById('browseBtn');
const uploadPreview = document.getElementById('uploadPreview');
const fileName = document.getElementById('fileName');
const fileSize = document.getElementById('fileSize');
const removeFile = document.getElementById('removeFile');

browseBtn.addEventListener('click', (e) => {
  e.stopPropagation();
  fileInput.click();
});

uploadZone.addEventListener('click', () => {
  if (!uploadZone.classList.contains('has-file')) {
    fileInput.click();
  }
});

uploadZone.addEventListener('dragover', (e) => {
  e.preventDefault();
  uploadZone.classList.add('dragover');
});

uploadZone.addEventListener('dragleave', () => {
  uploadZone.classList.remove('dragover');
});

uploadZone.addEventListener('drop', (e) => {
  e.preventDefault();
  uploadZone.classList.remove('dragover');
  const files = e.dataTransfer.files;
  if (files.length > 0) handleFile(files[0]);
});

fileInput.addEventListener('change', () => {
  if (fileInput.files.length > 0) handleFile(fileInput.files[0]);
});

function handleFile(file) {
  const validTypes = ['image/jpeg', 'image/png', 'application/pdf'];
  const maxSize = 10 * 1024 * 1024;

  if (!validTypes.includes(file.type)) {
    alert('Format file tidak didukung. Gunakan JPG, PNG, atau PDF.');
    return;
  }

  if (file.size > maxSize) {
    alert('Ukuran file maksimal 10 MB.');
    return;
  }

  fileName.textContent = file.name;
  fileSize.textContent = (file.size / (1024 * 1024)).toFixed(1) + ' MB';

  uploadZone.classList.add('has-file');
  uploadPreview.classList.add('active');
}

removeFile.addEventListener('click', () => {
  uploadZone.classList.remove('has-file');
  uploadPreview.classList.remove('active');
  fileInput.value = '';
});

// ==============================
// COUNTDOWN TIMER (23:41:05 starting)
// ==============================
const timerEl = document.getElementById('countdownTimer');
let totalSeconds = 23 * 3600 + 41 * 60 + 5;

function updateTimer() {
  const h = Math.floor(totalSeconds / 3600);
  const m = Math.floor((totalSeconds % 3600) / 60);
  const s = totalSeconds % 60;
  const pad = (n) => String(n).padStart(2, '0');
  timerEl.textContent = `${pad(h)}:${pad(m)}:${pad(s)}`;
  if (totalSeconds > 0) totalSeconds--;
}

updateTimer();
setInterval(updateTimer, 1000);

// ==============================
// THEME TOGGLE
// ==============================
const themeToggle = document.getElementById('themeToggle');
const themeLabel = document.getElementById('themeLabel');

function applyTheme(theme) {
  if (theme === 'light') {
    document.documentElement.setAttribute('data-theme', 'light');
    themeToggle.classList.add('off');
    themeLabel.textContent = 'Mode Terang';
  } else {
    document.documentElement.removeAttribute('data-theme');
    themeToggle.classList.remove('off');
    themeLabel.textContent = 'Mode Gelap';
  }
}

themeToggle.addEventListener('click', () => {
  const current = document.documentElement.getAttribute('data-theme');
  const next = current === 'light' ? 'dark' : 'light';
  localStorage.setItem('rv-theme', next);
  applyTheme(next);
});

const savedTheme = localStorage.getItem('rv-theme') || 'dark';
applyTheme(savedTheme);

// ==============================
// HAMBURGER MENU (mobile)
// ==============================
const hamburger = document.getElementById('hamburger');
const sidebar = document.querySelector('.sidebar');
const sidebarOverlay = document.getElementById('sidebarOverlay');

hamburger.addEventListener('click', () => {
  sidebar.classList.toggle('open');
  sidebarOverlay.classList.toggle('active');
});

sidebarOverlay.addEventListener('click', () => {
  sidebar.classList.remove('open');
  sidebarOverlay.classList.remove('active');
});

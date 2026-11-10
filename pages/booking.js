(function () {
  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  function formatRp(n) {
    return 'Rp ' + Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
  }

  function fmtDate(s) {
    if (!s) return '-';
    const d = new Date(s + 'T00:00:00');
    const months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    return d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
  }

  function isExpired(b) {
    if (b.status !== 'active' || !b.end_date) return false;
    const end = new Date(b.end_date + 'T00:00:00');
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    return end < today;
  }

  function statusBadge(b) {
    if (b.status === 'cancelled') return '<span class="bk-badge cancelled">Dibatalkan</span>';
    if (b.status === 'completed') return '<span class="bk-badge done">Sudah dikembalikan</span>';
    if (isExpired(b)) return '<span class="bk-badge expired">Waktu Booking telah habis</span>';
    if (b.status === 'active') return '<span class="bk-badge active">Berlangsung</span>';
    if (b.status === 'confirmed') return '<span class="bk-badge confirmed">Dikonfirmasi</span>';

    if (b.payment_status !== 'paid') return '<span class="bk-badge waiting">Menunggu Verifikasi</span>';
    return '<span class="bk-badge pending">Pending</span>';
  }

  async function load() {
    const listEl = document.getElementById('bookingList');
    const emptyEl = document.getElementById('emptyBooking');

    const code = new URLSearchParams(window.location.search).get('success');
    if (code) {
      const banner = document.getElementById('successBanner');
      const el = document.getElementById('successCode');
      if (banner) banner.style.display = 'flex';
      if (el) el.textContent = code;
      history.replaceState(null, '', 'booking.html');
    }

    try {
      const res = await fetch('/pkk/api/bookings.php');
      if (res.status === 401) {
        window.location.href = 'login.php?redirect=booking.html';
        return;
      }
      if (!res.ok) throw new Error('HTTP ' + res.status);
      const data = await res.json();
      const list = data.bookings || [];

      if (listEl) listEl.style.display = list.length ? 'grid' : 'none';
      if (emptyEl) emptyEl.style.display = list.length ? 'none' : 'flex';
      if (!list.length) return;

      // ambil detail tiap booking untuk ambil thumbnail + nama produk
      const items = await Promise.all(list.map(async (b) => {
        try {
          const r = await fetch('/pkk/api/bookings.php?id=' + b.id);
          if (!r.ok) return b;
          const full = await r.json();
          return full.items && full.items.length
            ? Object.assign({}, b, { items: full.items, first: full.items[0] })
            : b;
        } catch (e) {
          return b;
        }
      }));

      listEl.innerHTML = items.map(b => {
        const imgsrc = b.first && b.first.thumbnail
          ? b.first.thumbnail
          : 'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?w=200&q=80';
        const name = b.first ? b.first.product_name : 'Produk';
        const bookingItems = b.items || (b.first ? [b.first] : []);
        const rateButtons = b.status === 'completed' ? bookingItems.map(it => {
          if (it.review_id) {
            const stars = Math.max(0, Math.min(5, Number(it.my_rating) || 0));
            return `<span class="bk-rated">Sudah dinilai ${'★'.repeat(stars)}</span>`;
          }
          return `
              <button class="bk-rate" type="button"
                      data-booking="${b.id}"
                      data-product="${it.product_id}"
                      data-name="${esc(it.product_name || 'Produk')}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m12 2 3.1 6.3 7 1-5 4.9 1.2 6.9-6.3-3.3-6.3 3.3 1.2-6.9-5-4.9 7-1Z"/></svg>
                Beri Rating${bookingItems.length > 1 ? ' - ' + esc(it.product_name || '') : ''}
              </button>`;
        }).join('') : '';
        return `
          <div class="bk-card">
            <img src="${imgsrc}" alt="${esc(name)}">
            <div class="bk-info">
              <div class="bk-top">
                <h4>${esc(name)}</h4>
                ${statusBadge(b)}
              </div>
              <div class="bk-code">${esc(b.booking_code)}</div>
              <div class="bk-meta">
                <span>${fmtDate(b.start_date)} — ${fmtDate(b.end_date)}</span>
                <span>${b.total_days} hari</span>
              </div>
            </div>
            <div class="bk-price">${formatRp(b.total_price)}</div>
            ${rateButtons ? `<div class="bk-rate-list">${rateButtons}</div>` : ''}
          </div>
        `;
      }).join('');
    } catch (e) {
      if (emptyEl) {
        emptyEl.style.display = 'flex';
        const p = emptyEl.querySelector('p');
        if (p) p.textContent = 'Gagal memuat daftar booking. Coba muat ulang.';
      }
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    const listEl = document.getElementById('bookingList');
    if (listEl) {
      listEl.addEventListener('click', function (e) {
        const btn = e.target.closest('.bk-rate');
        if (!btn) return;
        openRatingModal(Number(btn.dataset.booking), Number(btn.dataset.product), btn.dataset.name);
      });
    }
    load();
  });
})();

function openRatingModal(bookingId, productId, productName) {
  const backdrop = document.getElementById('ratingModal');
  if (!backdrop) return;
  document.getElementById('ratingProductName').textContent = productName || 'Produk';
  document.getElementById('ratingModal').dataset.productId = productId;
  document.getElementById('ratingModal').dataset.bookingId = bookingId;
  const stars = backdrop.querySelectorAll('.rate-star');
  stars.forEach(s => s.classList.remove('on'));
  const submit = document.getElementById('rateSubmit');
  submit.disabled = true;
  document.getElementById('rateComment').value = '';
  document.getElementById('rateError').textContent = '';
  backdrop.classList.add('active');
}

function closeRatingModal() {
  const backdrop = document.getElementById('ratingModal');
  if (backdrop) backdrop.classList.remove('active');
}

function setupRating() {
  const backdrop = document.getElementById('ratingModal');
  if (!backdrop) return;

  backdrop.querySelectorAll('.rate-star').forEach(star => {
    star.addEventListener('click', () => {
      const val = Number(star.dataset.value);
      backdrop.querySelectorAll('.rate-star').forEach(s => {
        s.classList.toggle('on', Number(s.dataset.value) <= val);
      });
      document.getElementById('rateSubmit').disabled = false;
    });
  });

  backdrop.addEventListener('click', (e) => {
    if (e.target === backdrop) closeRatingModal();
  });

  document.getElementById('rateSubmit').addEventListener('click', async () => {
    const bookingId = Number(backdrop.dataset.bookingId);
    const productId = Number(backdrop.dataset.productId);
    const val = backdrop.querySelectorAll('.rate-star.on').length;
    const errEl = document.getElementById('rateError');
    if (!bookingId || !productId || val < 1) {
      errEl.textContent = 'Pilih rating 1-5 bintang dulu.';
      return;
    }
    const submit = document.getElementById('rateSubmit');
    submit.disabled = true;
    try {
      const res = await fetch('/pkk/api/reviews.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ booking_id: bookingId, product_id: productId, rating: val, comment: document.getElementById('rateComment').value })
      });
      const data = await res.json();
      if (res.status === 401) { window.location.href = 'login.php?redirect=booking.html'; return; }
      if (!res.ok) throw new Error(data.error || 'Gagal mengirim rating');
      closeRatingModal();
      document.getElementById('bookingList').innerHTML = '';
      load();
    } catch (e) {
      submit.disabled = false;
      errEl.textContent = e.message;
    }
  });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', setupRating);
} else {
  setupRating();
}
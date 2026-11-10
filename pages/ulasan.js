(function () {
  'use strict';

  const $ = (s) => document.querySelector(s);

  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  function fmtDate(s) {
    if (!s) return '-';
    var d = new Date(String(s).replace(' ', 'T'));
    if (isNaN(d.getTime())) return '-';
    var months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    return d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
  }

  async function loadMyReviews() {
    const listEl = $('#reviewList');
    const emptyEl = $('#reviewEmpty');
    if (!listEl) return;

    try {
      const res = await fetch('/pkk/api/reviews.php?mine=1');
      if (res.status === 401) {
        window.location.href = 'login.php?redirect=ulasan.html';
        return;
      }
      if (!res.ok) throw new Error('HTTP ' + res.status);
      const data = await res.json();
      const list = data.reviews || [];

      if (!list.length) {
        listEl.style.display = 'none';
        if (emptyEl) emptyEl.classList.remove('hidden');
        return;
      }

      if (emptyEl) emptyEl.classList.add('hidden');
      listEl.style.display = 'grid';
      listEl.innerHTML = list.map(function (r) {
        const stars = Math.max(0, Math.min(5, Number(r.rating) || 0));
        const product = r.product_slug
          ? '<a href="detail.html?id=' + Number(r.product_id) + '">' + esc(r.product_name || 'Produk') + '</a>'
          : esc(r.product_name || 'Produk');
        return '<div class="review-card">' +
          '<div class="review-card-head">' +
          '<div><div class="review-product">' + product + '</div>' +
          '<div class="review-meta">' + (r.booking_code ? esc(r.booking_code) + ' &middot; ' : '') + fmtDate(r.created_at) + '</div></div>' +
          '<div class="review-stars" title="' + stars + ' dari 5">' + '★'.repeat(stars) + '<span class="off">' + '☆'.repeat(5 - stars) + '</span></div>' +
          '</div>' +
          (r.comment ? '<p class="review-comment">' + r.comment + '</p>' : '') +
          '</div>';
      }).join('');
    } catch (e) {
      if (emptyEl) {
        emptyEl.classList.remove('hidden');
        const p = emptyEl.querySelector('p');
        if (p) p.textContent = 'Gagal memuat ulasan. Coba muat ulang.';
      }
    }
  }

  function renderWhyList() {
    var whys = [
      { title: 'Banyak Pilihan', desc: 'Ribuan peralatan fotografi dari berbagai kategori.', icon: '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>' },
      { title: 'Harga Terjangkau', desc: 'Sewa murah berkualitas, sesuai kebutuhanmu.', icon: '<path d="M12 2v20M2 12h20"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>' },
      { title: 'Aman & Terpercaya', desc: 'Proses aman dengan sistem pembayaran terjamin.', icon: '<path d="M12 2 4 5v6c0 5.2 3.4 9.7 8 11 4.6-1.3 8-5.8 8-11V5Z"/>' },
      { title: 'Dukungan 24/7', desc: 'Tim support siap membantu kapan saja.', icon: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/>' },
    ];
    $('#whyList').innerHTML = whys.map(function (w) {
      return '<div class="why-item">' +
        '<div class="why-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' + w.icon + '</svg></div>' +
        '<div><h4>' + w.title + '</h4><p>' + w.desc + '</p></div>' +
      '</div>';
    }).join('');
  }

  function applyTheme(theme) {
    if (theme === 'light') {
      document.documentElement.setAttribute('data-theme', 'light');
      $('#themeToggle').classList.add('off');
      $('#themeLabel').textContent = 'Mode Terang';
    } else {
      document.documentElement.removeAttribute('data-theme');
      $('#themeToggle').classList.remove('off');
      $('#themeLabel').textContent = 'Mode Gelap';
    }
  }

  function setupSidebar() {
    $('#hamburger').addEventListener('click', function () {
      $('.sidebar').classList.toggle('open');
      $('#sidebarOverlay').classList.toggle('active');
    });
    $('#sidebarOverlay').addEventListener('click', function () {
      $('.sidebar').classList.remove('open');
      $('#sidebarOverlay').classList.remove('active');
    });
  }

  function setupProfile() {
    var chip = $('#userChip');
    var drop = $('#profileDropdown');
    chip.addEventListener('click', function (e) {
      e.stopPropagation();
      chip.classList.toggle('open');
      drop.classList.toggle('active');
    });
    document.addEventListener('click', function (e) {
      if (!chip.contains(e.target) && !drop.contains(e.target)) {
        chip.classList.remove('open');
        drop.classList.remove('active');
      }
    });
  }

  function setupUserProfile() {
    var img = $('#userAvatar');
    img.style.display = 'none';
    var div = document.createElement('div');
    div.style.cssText = 'width:34px;height:34px;border-radius:50%;background:var(--accent-grad);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:800;color:#fff;flex-shrink:0;';
    div.textContent = 'FB';
    img.parentNode.insertBefore(div, img);
  }

  function init() {
    applyTheme(localStorage.getItem('rv-theme') || 'dark');
    renderWhyList();
    setupSidebar();
    setupProfile();
    setupUserProfile();
    loadMyReviews();

    $('#themeToggle').addEventListener('click', function () {
      var cur = document.documentElement.getAttribute('data-theme');
      var nxt = cur === 'light' ? 'dark' : 'light';
      localStorage.setItem('rv-theme', nxt);
      applyTheme(nxt);
    });
  }

  init();
})();
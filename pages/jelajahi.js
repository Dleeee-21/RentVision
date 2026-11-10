(function () {
  'use strict';


  let allProducts = [];
  let categories = [];
  let filtered = [];
  let wishlistIds = new Set();

  const state = {
    search: '',
    selectedCats: new Set(),
    selectedLocs: new Set(),
    priceMin: 0,
    priceMax: 0,
    availOnly: true,
    sort: 'popular',
    page: 1,
    perPage: 12,
  };

  const PLACEHOLDER = "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='600' height='400'><rect width='600' height='400' fill='%23161822'/><g fill='none' stroke='%232e3440' stroke-width='2'><rect x='190' y='150' width='220' height='130' rx='10'/><circle cx='300' cy='215' r='40'/><path d='M245 150l15-25h80l15 25'/></g></svg>";


  const $ = (s) => document.querySelector(s);
  const $$ = (s) => document.querySelectorAll(s);
  const prodGrid = $('#prodGrid');
  const emptyState = $('#emptyState');
  const pagination = $('#pagination');
  const productCount = $('#productCount');
  const gearSearchInput = $('#gearSearchInput');
  const categoryChips = $('#categoryChips');
  const locationChips = $('#locationChips');
  const availToggle = $('#availToggle');
  const priceMin = $('#priceMin');
  const priceMax = $('#priceMax');
  const sortSelect = $('#sortSelect');


  function fmtRp(n) {
    return 'Rp ' + Number(n).toLocaleString('id-ID');
  }

  function hl(text, q) {
    if (!q) return text;
    const s = q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    return text.replace(new RegExp('(' + s + ')', 'gi'), '<mark>$1</mark>');
  }

  /* ---- Fetch ---- */
  async function loadCategories() {
    try {
      const res = await fetch('/pkk/api/categories.php');
      const data = await res.json();
      categories = data.categories || [];
    } catch (e) {
      categories = [];
    }
  }

  async function loadProducts() {
    try {
      const res = await fetch('/pkk/api/products.php?per_page=100&available=1');
      const data = await res.json();
      allProducts = data.items || [];
    } catch (e) {
      allProducts = [];
    }
  }

  async function loadWishlistIds() {
    try {
      const res = await fetch('/pkk/api/wishlist.php');
      if (res.status === 401) return;
      const data = await res.json();
      (data.wishlists || []).forEach(function (w) {
        wishlistIds.add(Number(w.product_id));
      });
    } catch (e) {}
  }


  function getLocations() {
    var locSet = new Set();
    allProducts.forEach(function (p) {
      var loc = p.location || p.provider_location;
      if (loc) locSet.add(loc);
    });
    return Array.from(locSet).sort();
  }


  function applyFilters() {
    var q = state.search.toLowerCase();
    var list = allProducts.slice();


    if (q) {
      list = list.filter(function (p) {
        return p.name.toLowerCase().includes(q) ||
          (p.store_name || '').toLowerCase().includes(q) ||
          (p.category_name || '').toLowerCase().includes(q);
      });
    }


    if (state.selectedCats.size > 0) {
      list = list.filter(function (p) {
        return state.selectedCats.has(p.category_name);
      });
    }


    if (state.selectedLocs.size > 0) {
      list = list.filter(function (p) {
        var loc = p.location || p.provider_location || '';
        return state.selectedLocs.has(loc);
      });
    }


    if (state.availOnly) {
      list = list.filter(function (p) {
        return Number(p.is_available) === 1;
      });
    }


    if (state.priceMin > 0) {
      list = list.filter(function (p) {
        return Number(p.price_per_day) >= state.priceMin;
      });
    }
    if (state.priceMax > 0) {
      list = list.filter(function (p) {
        return Number(p.price_per_day) <= state.priceMax;
      });
    }


    switch (state.sort) {
      case 'price_asc':
        list.sort(function (a, b) { return Number(a.price_per_day) - Number(b.price_per_day); });
        break;
      case 'price_desc':
        list.sort(function (a, b) { return Number(b.price_per_day) - Number(a.price_per_day); });
        break;
      case 'rating':
        list.sort(function (a, b) { return Number(b.rating) - Number(a.rating); });
        break;
      case 'newest':
        list.sort(function (a, b) { return new Date(b.created_at) - new Date(a.created_at); });
        break;
      case 'popular':
      default:
        list.sort(function (a, b) { return Number(b.total_reviews) - Number(a.total_reviews); });
        break;
    }

    filtered = list;
    state.page = 1;
    renderProducts();
    renderPagination();
  }


  function renderCategoryChips() {
    var html = '<button class="filter-chip active" data-cat="">Semua</button>';
    categories.forEach(function (c) {
      html += '<button class="filter-chip" data-cat="' + c.name + '">' + c.name + ' (' + c.product_count + ')</button>';
    });
    categoryChips.innerHTML = html;

    categoryChips.querySelectorAll('.filter-chip').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var cat = this.dataset.cat;
        if (cat === '') {
          state.selectedCats.clear();
        } else {
          if (state.selectedCats.has(cat)) {
            state.selectedCats.delete(cat);
          } else {
            state.selectedCats.add(cat);
          }
        }
        updateChipActive(categoryChips, state.selectedCats);
        applyFilters();
      });
    });
  }


  function renderLocationChips() {
    var locs = getLocations();
    var html = '<button class="filter-chip active" data-loc="">Semua</button>';
    locs.forEach(function (loc) {
      html += '<button class="filter-chip" data-loc="' + loc + '">' + loc + '</button>';
    });
    locationChips.innerHTML = html;

    locationChips.querySelectorAll('.filter-chip').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var loc = this.dataset.loc;
        if (loc === '') {
          state.selectedLocs.clear();
        } else {
          if (state.selectedLocs.has(loc)) {
            state.selectedLocs.delete(loc);
          } else {
            state.selectedLocs.add(loc);
          }
        }
        updateChipActive(locationChips, state.selectedLocs);
        applyFilters();
      });
    });
  }

  function updateChipActive(container, selectedSet) {
    container.querySelectorAll('.filter-chip').forEach(function (btn) {
      var val = btn.dataset.cat !== undefined ? btn.dataset.cat : btn.dataset.loc;
      if (val === '') {
        btn.classList.toggle('active', selectedSet.size === 0);
      } else {
        btn.classList.toggle('active', selectedSet.has(val));
      }
    });
  }


  function renderProducts() {
    var total = filtered.length;
    productCount.textContent = total + ' Hasil';

    if (total === 0) {
      prodGrid.innerHTML = '';
      emptyState.classList.remove('hidden');
      return;
    }
    emptyState.classList.add('hidden');

    var start = (state.page - 1) * state.perPage;
    var pageItems = filtered.slice(start, start + state.perPage);

    prodGrid.innerHTML = pageItems.map(function (p) {
      var img = p.thumbnail || PLACEHOLDER;
      var loc = p.location || p.provider_location || '';
      var liked = wishlistIds.has(Number(p.id)) ? ' liked' : '';
      return '<div class="prod-card" data-id="' + p.id + '">' +
        '<div class="prod-media">' +
          '<img src="' + img + '" alt="' + p.name + '" loading="lazy">' +
          '<button class="fav-btn' + liked + '" data-id="' + p.id + '">' +
            '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-7-4.5-9.5-9A5.5 5.5 0 0 1 12 6a5.5 5.5 0 0 1 9.5 6c-2.5 4.5-9.5 9-9.5 9Z"/></svg>' +
          '</button>' +
        '</div>' +
        '<div class="prod-body">' +
          '<div class="prod-cat">' + (p.category_name || '') + '</div>' +
          (p.store_name ? '<div class="prod-store"><a href="store.php?slug=' + p.provider_slug + '" data-store>' + p.store_name + '</a></div>' : '') +
          '<div class="prod-name">' + p.name + '</div>' +
          '<div class="prod-price">' + fmtRp(p.price_per_day) + ' <span>/ hari</span></div>' +
          '<div class="prod-meta">' +
            '<div class="rating">&#9733; ' + (Number(p.rating) || 0) + ' <span>(' + (Number(p.total_reviews) || 0) + ')</span></div>' +
            '<div class="loc"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-7-6.5-7-11a7 7 0 0 1 14 0c0 4.5-7 11-7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg>' + loc + '</div>' +
          '</div>' +
        '</div>' +
      '</div>';
    }).join('');


    prodGrid.querySelectorAll('.prod-card').forEach(function (card) {
      card.addEventListener('click', function (e) {
        if (e.target.closest('.fav-btn')) return;
        if (e.target.closest('[data-store]')) return;
        location.href = 'detail.html?id=' + card.dataset.id;
      });
    });


    prodGrid.querySelectorAll('.fav-btn').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.stopPropagation();
        toggleWishlist(this);
      });
    });
  }


  async function toggleWishlist(btn) {
    var productId = Number(btn.dataset.id);
    var wasLiked = btn.classList.contains('liked');
    btn.classList.toggle('liked');
    try {
      var res = await fetch('/pkk/api/wishlist.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ product_id: productId })
      });
      if (res.status === 401) {
        window.location.href = 'login.php?redirect=' + encodeURIComponent('/pkk/pages/jelajahi.html');
        return;
      }
      if (!res.ok) { btn.classList.toggle('liked', wasLiked); return; }
      var data = await res.json();
      if (data.action === 'added') wishlistIds.add(productId);
      else wishlistIds.delete(productId);
    } catch (e) {
      btn.classList.toggle('liked', wasLiked);
    }
  }


  function renderPagination() {
    var totalPages = Math.max(1, Math.ceil(filtered.length / state.perPage));
    var cur = state.page;
    if (cur > totalPages) cur = totalPages;
    state.page = cur;

    var html = '';


    html += '<button class="page-btn' + (cur <= 1 ? ' disabled' : '') + '" data-page="' + (cur - 1) + '">&#8592;</button>';


    var pages = [];
    if (totalPages <= 7) {
      for (var i = 1; i <= totalPages; i++) pages.push(i);
    } else {
      pages.push(1);
      if (cur > 3) pages.push('...');
      for (var j = Math.max(2, cur - 1); j <= Math.min(totalPages - 1, cur + 1); j++) {
        pages.push(j);
      }
      if (cur < totalPages - 2) pages.push('...');
      pages.push(totalPages);
    }

    pages.forEach(function (p) {
      if (p === '...') {
        html += '<span class="page-dots">...</span>';
      } else {
        html += '<button class="page-btn' + (p === cur ? ' active' : '') + '" data-page="' + p + '">' + p + '</button>';
      }
    });


    html += '<button class="page-btn' + (cur >= totalPages ? ' disabled' : '') + '" data-page="' + (cur + 1) + '">&#8594;</button>';

    pagination.innerHTML = html;

    pagination.querySelectorAll('.page-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var pg = parseInt(this.dataset.page);
        if (isNaN(pg) || pg < 1 || pg > totalPages) return;
        state.page = pg;
        renderProducts();
        renderPagination();
        prodGrid.scrollIntoView({ behavior: 'smooth', block: 'start' });
      });
    });
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
    var sidebar = $('.sidebar');
    var overlay = $('#sidebarOverlay');
    $('#hamburger').addEventListener('click', function () {
      sidebar.classList.toggle('open');
      overlay.classList.toggle('active');
    });
    overlay.addEventListener('click', function () {
      sidebar.classList.remove('open');
      overlay.classList.remove('active');
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


  function setupEvents() {

    var debounceTimer;
    gearSearchInput.addEventListener('input', function () {
      var v = this.value;
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(function () {
        state.search = v;
        applyFilters();
      }, 250);
    });


    availToggle.addEventListener('change', function () {
      state.availOnly = this.checked;
      applyFilters();
    });


    var priceTimer;
    function onPriceChange() {
      clearTimeout(priceTimer);
      priceTimer = setTimeout(function () {
        state.priceMin = Number(priceMin.value) || 0;
        state.priceMax = Number(priceMax.value) || 0;
        applyFilters();
      }, 400);
    }
    priceMin.addEventListener('input', onPriceChange);
    priceMax.addEventListener('input', onPriceChange);


    sortSelect.addEventListener('change', function () {
      state.sort = this.value;
      applyFilters();
    });

    // theme
    $('#themeToggle').addEventListener('click', function () {
      var cur = document.documentElement.getAttribute('data-theme');
      var nxt = cur === 'light' ? 'dark' : 'light';
      localStorage.setItem('rv-theme', nxt);
      applyTheme(nxt);
    });


    async function loadUserProfile() {
      try {
        var res = await fetch('/pkk/api/auth/check.php');
        var data = await res.json();
        if (data.logged_in && data.user) {
          $('#profileName').textContent = data.user.name;
          $('#profileEmail').textContent = data.user.email;
          $('#userNameTop').textContent = data.user.name;
          $('#profileRole').textContent = data.user.role.charAt(0).toUpperCase() + data.user.role.slice(1);
          var initials = data.user.name.split(' ').map(function (w) { return w[0]; }).slice(0, 2).join('').toUpperCase();
          $$('.user-chip img, .profile-avatar').forEach(function (img) {
            img.style.display = 'none';
            var div = document.createElement('div');
            div.style.cssText = 'width:34px;height:34px;border-radius:50%;background:var(--accent-grad);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:800;color:#fff;flex-shrink:0;';
            div.textContent = initials;
            img.parentNode.insertBefore(div, img);
          });
        }
      } catch (e) {}
    }
    loadUserProfile();
  }


  async function init() {
    applyTheme(localStorage.getItem('rv-theme') || 'dark');
    setupSidebar();
    setupProfile();
    renderWhyList();

    await Promise.all([loadCategories(), loadProducts(), loadWishlistIds()]);

    renderCategoryChips();
    renderLocationChips();
    applyFilters();
    setupEvents();
  }

  init();
})();

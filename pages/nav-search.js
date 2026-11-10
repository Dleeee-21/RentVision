const PLACEHOLDER = "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='80' height='80'><rect width='80' height='80' fill='%23222533'/><g fill='none' stroke='%232e3440' stroke-width='2'><rect x='22' y='24' width='36' height='26' rx='3'/><circle cx='40' cy='37' r='8'/></g></svg>";

const BRAND_RULES = [
  { re: /^sony/i, b: 'Sony' },
  { re: /^canon/i, b: 'Canon' },
  { re: /^nikon/i, b: 'Nikon' },
  { re: /^sigma/i, b: 'Sigma' },
  { re: /^godox/i, b: 'Godox' },
  { re: /^aputure/i, b: 'Aputure' },
  { re: /^nanlite/i, b: 'Nanlite' },
  { re: /^manfrotto/i, b: 'Manfrotto' },
  { re: /^gitzo/i, b: 'Gitzo' },
  { re: /^dji/i, b: 'DJI' },
  { re: /^rode/i, b: 'Rode' },
  { re: /^zoom/i, b: 'Zoom' },
  { re: /^peak/i, b: 'Peak' },
  { re: /^sandisk/i, b: 'SanDisk' },
];

let navProducts = [];
let brands = [];

const navSearch = {
  input: document.getElementById('navSearchInput'),
  clear: document.getElementById('navSearchClear'),
  dropdown: document.getElementById('navSearchDropdown'),
  wrap: document.querySelector('.search-nav-wrap'),
};

let activeBrand = '';

function brandOf(name) {
  for (const r of BRAND_RULES) {
    if (r.re.test(name)) return r.b;
  }
  return 'Lainnya';
}

function fmtRp(n) {
  return 'Rp ' + Number(n).toLocaleString('id-ID') + '/hari';
}

async function loadNavProducts() {
  try {
    const res = await fetch('/pkk/api/products.php?per_page=100&available=1');
    const data = await res.json();
    navProducts = (data.items || []).map(p => ({
      id: Number(p.id),
      name: p.name,
      brand: brandOf(p.name),
      category: p.category_name || '',
      price: fmtRp(p.price_per_day),
      img: p.thumbnail || PLACEHOLDER,
    }));
    brands = [...new Set(navProducts.map(p => p.brand))].sort();
  } catch (e) {
    navProducts = [];
    brands = [];
  }
}

function hl(text, q) {
  if (!q) return text;
  const s = q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  return text.replace(new RegExp('(' + s + ')', 'gi'), '<mark>$1</mark>');
}

function renderNavDropdown(q) {
  const query = q.trim().toLowerCase();
  let filtered = navProducts;
  if (query) {
    filtered = navProducts.filter(p =>
      p.name.toLowerCase().includes(query) ||
      p.brand.toLowerCase().includes(query) ||
      p.category.toLowerCase().includes(query)
    );
  }
  if (activeBrand) {
    filtered = filtered.filter(p => p.brand === activeBrand);
  }

  if (query && filtered.length === 0) {
    navSearch.dropdown.innerHTML =
      '<div class="navd-empty">' +
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/><path d="M8 11h6"/></svg>' +
      '<p>Tidak ada hasil</p><span>Coba kata kunci lain</span></div>';
    navSearch.dropdown.classList.add('active');
    return;
  }

  const items = query || activeBrand ? filtered.slice(0, 8) : navProducts.slice(0, 5);
  let html = '';

  if (!query && !activeBrand) {
    html += '<div class="navd-section"><div class="navd-title">Brand Populer</div><div class="navd-brands">';
    brands.forEach(b => {
      html += '<button class="navd-brand-pill' + (activeBrand === b ? ' active' : '') + '" data-brand="' + b + '">' + b + '</button>';
    });
    html += '</div></div>';
  }

  html += '<div class="navd-section"><div class="navd-title">' + (query || activeBrand ? 'Hasil Pencarian' : 'Gear yang Disarankan') + '</div><div class="navd-results">';
  items.forEach(p => {
    html += '<button data-id="' + p.id + '" class="navd-item">';
    html += '<img src="' + p.img + '" alt="' + p.name + '" class="navd-thumb" loading="lazy">';
    html += '<div class="navd-info"><div class="navd-name">' + hl(p.name, query) + '</div><div class="navd-meta"><span class="navd-cat">' + p.category + '</span><span class="navd-brand">' + hl(p.brand, query) + '</span></div></div>';
    html += '<div class="navd-price">' + p.price + '</div>';
    html += '</button>';
  });
  html += '</div></div>';

  navSearch.dropdown.innerHTML = html;
  navSearch.dropdown.classList.add('active');

  navSearch.dropdown.querySelectorAll('.navd-brand-pill').forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.stopPropagation();
      activeBrand = this.classList.contains('active') ? '' : this.dataset.brand;
      renderNavDropdown(navSearch.input.value);
    });
  });

  navSearch.dropdown.querySelectorAll('.navd-item').forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.stopPropagation();
      navSearch.dropdown.classList.remove('active');
      const id = this.dataset.id;
      if (id) location.href = (location.pathname.includes('/pages/') ? '' : 'pages/') + 'detail.html?id=' + id;
    });
  });
}

navSearch.input.addEventListener('input', function() {
  const v = this.value;
  navSearch.clear.classList.toggle('hidden', v.length === 0);
  navSearch.clear.classList.toggle('flex', v.length > 0);
  activeBrand = '';
  renderNavDropdown(v);
});

navSearch.input.addEventListener('focus', function() {
  renderNavDropdown(this.value);
});

navSearch.clear.addEventListener('click', function(e) {
  e.stopPropagation();
  navSearch.input.value = '';
  navSearch.clear.classList.add('hidden');
  navSearch.clear.classList.remove('flex');
  navSearch.input.focus();
  activeBrand = '';
  navSearch.dropdown.classList.remove('active');
});

document.addEventListener('click', function(e) {
  if (navSearch.wrap && !navSearch.wrap.contains(e.target)) {
    navSearch.dropdown.classList.remove('active');
  }
});

navSearch.input.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    navSearch.dropdown.classList.remove('active');
    this.blur();
  }
});

loadNavProducts();

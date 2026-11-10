const categories = [
  { name: 'Kamera', icon: '<path d="M4 8h3l1.5-2h7L17 8h3a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9a1 1 0 0 1 1-1Z"/><circle cx="12" cy="13" r="3.5"/>' },
  { name: 'Lensa', icon: '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="4.5"/><circle cx="12" cy="12" r="1"/>' },
  { name: 'Lighting', icon: '<circle cx="12" cy="9" r="5.5"/><path d="M9 20h6M10 22h4M12 3V1"/>' },
  { name: 'Tripod', icon: '<path d="M12 2v9M12 11l-6 11M12 11l6 11M6 22h3M15 22h3"/>' },
  { name: 'Drone', icon: '<rect x="9" y="9" width="6" height="6" rx="1"/><path d="M9 9 3 5M15 9l6-4M9 15l-6 4M15 15l6 4"/><circle cx="3" cy="5" r="1.6"/><circle cx="21" cy="5" r="1.6"/><circle cx="3" cy="19" r="1.6"/><circle cx="21" cy="19" r="1.6"/>' },
  { name: 'Audio', icon: '<rect x="9" y="2" width="6" height="12" rx="3"/><path d="M5 11a7 7 0 0 0 14 0M12 18v4M9 22h6"/>' },
  { name: 'Aksesoris', icon: '<rect x="3" y="8" width="18" height="12" rx="2"/><path d="M8 8V6a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>' },
];


const wishlistIds = new Set();
let loggedIn = true;

function fmtRp(n) {
  return 'Rp ' + Number(n).toLocaleString('id-ID');
}

async function toggleWishlist(btn) {
  const productId = Number(btn.dataset.id);
  if (!loggedIn) {
    window.location.href = '/pkk/pages/login.php?redirect=' + encodeURIComponent('/pkk/');
    return;
  }
  const wasLiked = btn.classList.contains('liked');
  btn.classList.toggle('liked');
  try {
    const res = await fetch('/pkk/api/wishlist.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ product_id: productId })
    });
    if (res.status === 401) {
      window.location.href = '/pkk/pages/login.php?redirect=' + encodeURIComponent('/pkk/');
      return;
    }
    if (!res.ok) { btn.classList.toggle('liked', wasLiked); return; }
    const data = await res.json();
    if (data.action === 'added') wishlistIds.add(productId);
    else wishlistIds.delete(productId);
  } catch (e) {
    btn.classList.toggle('liked', wasLiked);
  }
}

async function loadProducts() {
  try {
    const res = await fetch('/pkk/api/products.php?per_page=12&available=1');
    if (!res.ok) return;
    const data = await res.json();
    const items = data.items || [];
    document.getElementById('prod-grid').innerHTML = items.map(p => `
<div class="prod-card" onclick="location.href='pages/detail.html?id=${p.id}'">
  <div class="prod-media">
    <img src="${p.thumbnail || ''}" alt="${p.name}" loading="lazy">
    <button class="fav-btn${wishlistIds.has(p.id) ? ' liked' : ''}" data-id="${p.id}" onclick="event.stopPropagation();toggleWishlist(this)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-7-4.5-9.5-9A5.5 5.5 0 0 1 12 6a5.5 5.5 0 0 1 9.5 6c-2.5 4.5-9.5 9-9.5 9Z"/></svg></button>
  </div>
  <div class="prod-body">
    <div class="prod-cat">${p.category_name}</div>
    <div class="prod-name">${p.name}</div>
    <div class="prod-price">${fmtRp(p.price_per_day)} <span>/ hari</span></div>
    <div class="prod-meta">
      <div class="rating">★ ${p.rating} <span>(${p.total_reviews})</span></div>
      <div class="loc"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-7-6.5-7-11a7 7 0 0 1 14 0c0 4.5-7 11-7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg>${p.location || p.provider_location}</div>
    </div>
  </div>
</div>
`).join('');
  } catch (e) {}
}

async function loadWishlistIds() {
  try {
    const res = await fetch('/pkk/api/wishlist.php');
    if (res.status === 401) { loggedIn = false; return; }
    const data = await res.json();
    (data.wishlists || []).forEach(w => wishlistIds.add(Number(w.product_id)));
  } catch (e) { loggedIn = false; }
}

function fmtDate(s) {
  if (!s) return '-';
  const d = new Date(s + 'T00:00:00');
  const months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
  return d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
}

async function loadActiveBookings() {
  const panel = document.getElementById('activeBookingPanel');
  if (!panel) return;
  try {
    const res = await fetch('/pkk/api/bookings.php?status=active');
    if (res.status === 401) { panel.style.display = 'none'; return; }
    if (!res.ok) { panel.style.display = 'none'; return; }
    const data = await res.json();
    let list = data.bookings || [];
    if (!list.length) { panel.style.display = 'none'; return; }

    const today = new Date(); today.setHours(0, 0, 0, 0);
    list = list.filter(b => {
      const end = new Date(b.end_date + 'T00:00:00');
      return end >= today;
    });
    if (!list.length) { panel.style.display = 'none'; return; }

    const items = await Promise.all(list.map(async (b) => {
      try {
        const r = await fetch('/pkk/api/bookings.php?id=' + b.id);
        if (!r.ok) return b;
        const full = await r.json();
        return full.items && full.items.length ? Object.assign({}, b, { first: full.items[0] }) : b;
      } catch (e) {
        return b;
      }
    }));

    document.getElementById('activeBookingList').innerHTML = items.map(b => {
      const name = b.first ? b.first.product_name : 'Produk';
      const imgsrc = b.first && b.first.thumbnail
        ? b.first.thumbnail
        : 'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?w=200&q=80';
      return `
        <div class="booking-card">
          <img src="${imgsrc}" alt="${name.replace(/"/g, '&quot;')}">
          <div class="booking-info">
            <div class="bk-code">${b.booking_code}</div>
            <h4>${name}</h4>
            <div class="date">${fmtDate(b.start_date)} — ${fmtDate(b.end_date)}</div>
            <span class="status-pill">Berlangsung</span>
          </div>
        </div>
      `;
    }).join('');
  } catch (e) {
    panel.style.display = 'none';
  }
}

(async function initHome() {
  await loadWishlistIds();
  await loadProducts();
  await loadActiveBookings();
})();


const whys = [
  { title: 'Banyak Pilihan', desc: 'Ribuan peralatan fotografi dari berbagai kategori.', icon: '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>' },
  { title: 'Harga Terjangkau', desc: 'Sewa murah berkualitas, sesuai kebutuhanmu.', icon: '<path d="M12 2v20M2 12h20"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>' },
  { title: 'Aman & Terpercaya', desc: 'Proses aman dengan sistem pembayaran terjamin.', icon: '<path d="M12 2 4 5v6c0 5.2 3.4 9.7 8 11 4.6-1.3 8-5.8 8-11V5Z"/>' },
  { title: 'Dukungan 24/7', desc: 'Tim support siap membantu kapan saja.', icon: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/>' },
];
document.getElementById('why-list').innerHTML = whys.map(w => `
<div class="why-item">
  <div class="why-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">${w.icon}</svg></div>
  <div><h4>${w.title}</h4><p>${w.desc}</p></div>
</div>
`).join('');
const hamburger = document.getElementById('hamburger');
const sidebar = document.querySelector('.sidebar');
const overlay = document.getElementById('sidebar-overlay');
hamburger.addEventListener('click', () => {
  sidebar.classList.toggle('open');
  overlay.classList.toggle('active');
});
overlay.addEventListener('click', () => {
  sidebar.classList.remove('open');
  overlay.classList.remove('active');
});

document.querySelectorAll('.bottom-nav-item').forEach(item => {
  item.addEventListener('click', (e) => {
    e.preventDefault();
    document.querySelectorAll('.bottom-nav-item').forEach(i => i.classList.remove('active'));
    item.classList.add('active');
  });
});

const userChip      = document.getElementById('userChip');
const profileDrop   = document.getElementById('profileDropdown');
const profileName   = document.getElementById('profileName');
const profileEmail  = document.getElementById('profileEmail');
const profileRole   = document.getElementById('profileRole');
const userNameTop   = document.getElementById('userNameTop');

function toggleProfileMenu() {
  userChip.classList.toggle('open');
  profileDrop.classList.toggle('active');
}

document.addEventListener('click', (e) => {
  if (!userChip.contains(e.target) && !profileDrop.contains(e.target)) {
    userChip.classList.remove('open');
    profileDrop.classList.remove('active');
  }
});

function insertAvatar(img, text) {
  if (img.previousElementSibling && img.previousElementSibling.classList.contains('guest-avatar')) return;
  img.style.display = 'none';
  const div = document.createElement('div');
  div.className = 'guest-avatar';
  div.style.cssText = 'width:34px;height:34px;border-radius:50%;background:var(--accent-grad);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:800;color:#fff;flex-shrink:0;';
  if (img.classList.contains('profile-avatar')) {
    div.style.width = '48px';
    div.style.height = '48px';
    div.style.fontSize = '17px';
  }
  div.textContent = text;
  img.parentNode.insertBefore(div, img);
}

function setLinkLabel(a, label) {
  let textNode = null;
  for (const node of a.childNodes) {
    if (node.nodeType === 3 && node.textContent.trim()) textNode = node;
  }
  if (textNode) textNode.textContent = ' ' + label;
  else a.appendChild(document.createTextNode(label));
}

function renderGuest() {
  userNameTop.textContent  = 'Guest';
  profileName.textContent  = 'Guest';
  profileEmail.textContent = 'Belum login';
  if (profileRole) profileRole.style.display = 'none';
  document.querySelectorAll('.user-chip img, .profile-avatar').forEach(img => insertAvatar(img, 'G'));
  const logout = document.getElementById('logoutBtn');
  if (logout) {
    logout.href = '/pkk/pages/login.php';
    setLinkLabel(logout, 'Masuk');
  }
}

async function loadUserProfile() {
  try {
    const res = await fetch('/pkk/api/auth/check.php');
    const data = await res.json();
    if (data.logged_in && data.user) {
      profileName.textContent  = data.user.name;
      profileEmail.textContent = data.user.email;
      userNameTop.textContent  = data.user.name;
      profileRole.textContent  = data.user.role.charAt(0).toUpperCase() + data.user.role.slice(1);

      const initials = data.user.name.split(' ').map(w => w[0]).slice(0,2).join('').toUpperCase();
      document.querySelectorAll('.user-chip img, .profile-avatar').forEach(img => insertAvatar(img, initials));
    } else {
      renderGuest();
    }
  } catch(e) {}
}
loadUserProfile();

const themeToggle = document.getElementById('themeToggle');
const themeLabel  = document.getElementById('themeLabel');

function applyTheme(theme) {
  if (theme === 'light') {
    document.documentElement.setAttribute('data-theme', 'light');
    themeToggle.classList.add('off');
    themeLabel.textContent = 'Mode Gelap';
  } else {
    document.documentElement.removeAttribute('data-theme');
    themeToggle.classList.remove('off');
    themeLabel.textContent = 'Mode Gelap';
  }
}

function toggleTheme() {
  const current = document.documentElement.getAttribute('data-theme');
  const next = current === 'light' ? 'dark' : 'light';
  localStorage.setItem('rv-theme', next);
  applyTheme(next);
}


const savedTheme = localStorage.getItem('rv-theme') || 'dark';
applyTheme(savedTheme);


function openHowItWorks() {
  document.getElementById('howItWorksModal').classList.add('active');
}
function closeHowItWorks() {
  document.getElementById('howItWorksModal').classList.remove('active');
}

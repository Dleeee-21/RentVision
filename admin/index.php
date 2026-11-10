<?php
session_start();
if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'Dev') {
    header('Location: /pkk/pages/login.php?redirect=/pkk/admin/');
    exit;
}
$adminName = $_SESSION['user_name'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel — RentVision</title>
    <link rel="icon" type="image/png" sizes="256x256" href="/pkk/rentvision-favicon.png">
    <link rel="stylesheet" href="/pkk/assets/auth.css">
    <style>
        .admin-content { display:flex; flex-direction:column; gap:24px; }
        .toolbar {
            display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;
        }
        .toolbar-left { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
        .toolbar h2 { font-size:18px; font-weight:800; }
        .search-input, .filter-select {
            padding:9px 12px; background:var(--bg-panel-2); border:1px solid var(--border);
            border-radius:var(--radius-sm); color:var(--text); font-size:13px; font-family:inherit;
        }
        .search-input:focus, .filter-select:focus { outline:none; border-color:var(--accent-1); }
        .btn {
            padding:9px 16px; border-radius:var(--radius-sm); font-size:13px; font-weight:700;
            border:none; cursor:pointer; font-family:inherit; transition:opacity .15s;
        }
        .btn:hover { opacity:.85; }
        .btn-primary { background:var(--accent-grad); color:#fff; }
        .btn-danger { background:rgba(255,92,92,.2); color:#ff5c5c; border:1px solid rgba(255,92,92,.3); }
        .btn-success { background:rgba(53,201,143,.2); color:#35c98f; border:1px solid rgba(53,201,143,.3); }
        .btn-sm { padding:6px 10px; font-size:11.5px; }
        .btn-ghost { background:transparent; color:var(--text-dim); border:1px solid var(--border); }

        /* Modal */
        .modal-overlay {
            display:none; position:fixed; inset:0; z-index:200;
            background:rgba(0,0,0,.6); backdrop-filter:blur(4px);
            align-items:center; justify-content:center;
        }
        .modal-overlay.active { display:flex; }
        .modal {
            background:var(--bg-panel); border:1px solid var(--border); border-radius:var(--radius-lg);
            padding:28px; width:90%; max-width:520px; max-height:85vh; overflow-y:auto;
        }
        .modal h3 { font-size:17px; font-weight:800; margin-bottom:18px; }
        .modal-actions { display:flex; gap:10px; justify-content:flex-end; margin-top:20px; }

        /* Toast */
        .toast {
            position:fixed; top:20px; right:20px; z-index:300;
            padding:12px 20px; border-radius:var(--radius-sm);
            font-size:13px; font-weight:600;
            transform:translateX(120%); transition:transform .3s ease;
        }
        .toast.show { transform:translateX(0); }
        .toast.success { background:#1a3d2a; border:1px solid #35c98f; color:#6ff0b8; }
        .toast.error { background:#3d1a1a; border:1px solid #ff5c5c; color:#ff8a8a; }

        /* Sidebar nav */
        .admin-nav a.active {
            background: linear-gradient(135deg, rgba(124,92,252,.18), rgba(91,140,255,.10));
            color: var(--text);
            box-shadow: inset 0 0 0 1px rgba(124,92,252,.35);
            font-weight:600;
        }
        .admin-nav a {
            display:flex; align-items:center; gap:10px; padding:10px 12px; border-radius:10px;
            font-size:13.5px; font-weight:500; color:var(--text-dim); cursor:pointer;
            transition: background .15s, color .15s;
        }
        .admin-nav a:hover { background:var(--bg-panel-2); color:var(--text); }
        .admin-nav a svg { width:18px; height:18px; flex-shrink:0; opacity:.8; }

        /* Table row hover */
        .dash-table tbody tr { cursor:default; }
        .dash-table tbody tr:hover td { background:rgba(124,92,252,.04); }
        .action-btns { display:flex; gap:6px; }

        /* Pagination */
        .pagination {
            display:flex; align-items:center; justify-content:center; gap:6px; margin-top:16px;
        }
        .pagination button {
            width:34px; height:34px; border-radius:8px; border:1px solid var(--border);
            background:var(--bg-panel-2); color:var(--text-dim); font-size:13px; font-weight:600;
            cursor:pointer; display:flex; align-items:center; justify-content:center;
        }
        .pagination button.active { background:var(--accent-grad); color:#fff; border-color:transparent; }
        .pagination button:hover:not(.active) { background:var(--bg-panel); color:var(--text); }

        @media (max-width:860px) {
            .dash-layout { grid-template-columns:1fr; }
            .dash-sidebar { display:none; }
        }
    </style>
</head>
<body>
    <div class="dash-layout">
        <aside class="dash-sidebar">
            <div class="auth-brand" style="margin-bottom:10px;">
                <div class="auth-brand-mark">
                    <img src="../Rentvision logo.png" alt="RentVision">
                </div>
                <div class="auth-brand-name">Rent<span>Vision</span></div>
            </div>

            <div style="padding:12px; background:var(--bg-panel-2); border-radius:var(--radius-sm); border:1px solid var(--border); margin-bottom:6px;">
                <div style="font-size:13px; font-weight:700;"><?= htmlspecialchars($adminName) ?></div>
                <span class="badge completed" style="font-size:10px; margin-top:4px;">Admin</span>
            </div>

            <nav class="admin-nav" style="display:flex; flex-direction:column; gap:2px;">
                <a class="active" data-page="dashboard" onclick="goTo('dashboard')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9.5 12 3l9 6.5V21a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1Z"/></svg>
                    Dashboard
                </a>
                <a data-page="products" onclick="goTo('products')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="13" r="4"/><path d="M4 8h3l1.5-2h7L17 8h3a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9a1 1 0 0 1 1-1Z"/></svg>
                    Produk
                </a>
                <a data-page="categories" onclick="goTo('categories')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
                    Kategori
                </a>
                <a data-page="bookings" onclick="goTo('bookings')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                    Booking
                </a>
                <a data-page="reviews" onclick="goTo('reviews')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m12 2 3.1 6.3 7 1-5 4.9 1.2 6.9-6.3-3.3-6.3 3.3 1.2-6.9-5-4.9 7-1Z"/></svg>
                    Ulasan
                </a>
                <a data-page="users" onclick="goTo('users')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M20 21a8 8 0 1 0-16 0"/></svg>
                    User
                </a>
                <a data-page="providers" onclick="goTo('providers')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M3 12h18"/></svg>
                    Provider
                </a>
            </nav>

            <div style="margin-top:auto; display:flex; flex-direction:column; gap:6px;">
                <a href="/pkk/" class="admin-nav" style="text-decoration:none;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M18 6 6 18M6 6l12 12"/></svg>
                    Kembali ke Beranda
                </a>
                <a href="/pkk/pages/logout.php" class="admin-nav" style="text-decoration:none; color:var(--error);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
                    Keluar
                </a>
            </div>
        </aside>

        <main class="dash-main">
            <div id="pageContent" class="admin-content">
                <p style="color:var(--text-dim);">Memuat...</p>
            </div>
        </main>
    </div>


    <div class="modal-overlay" id="modalOverlay" onclick="if(event.target===this)closeModal()">
        <div class="modal" id="modalBody"></div>
    </div>


    <div class="toast" id="toast"></div>

    <script>
    const API = '/pkk/api';
    let currentPage = 'dashboard';

    function rupiah(n) { return 'Rp ' + Number(n).toLocaleString('id-ID'); }
    function fmtDate(d) { return d ? new Date(d).toLocaleDateString('id-ID',{day:'numeric',month:'short',year:'numeric'}) : '—'; }
    function statusBadge(s) { return `<span class="badge ${s}">${s}</span>`; }

    async function api(path, opts = {}) {
        const res = await fetch(API + path, {
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            ...opts,
            body: opts.body ? JSON.stringify(opts.body) : undefined,
        });
        const data = await res.json();
        if (!res.ok) throw new Error(data.error || 'Request gagal');
        return data;
    }

    function toast(msg, type = 'success') {
        const el = document.getElementById('toast');
        el.textContent = msg;
        el.className = `toast ${type} show`;
        setTimeout(() => el.classList.remove('show'), 3000);
    }

    function openModal(html) {
        document.getElementById('modalBody').innerHTML = html;
        document.getElementById('modalOverlay').classList.add('active');
    }
    function closeModal() {
        document.getElementById('modalOverlay').classList.remove('active');
    }

    function goTo(page) {
        currentPage = page;
        document.querySelectorAll('.admin-nav a').forEach(a => {
            a.classList.toggle('active', a.dataset.page === page);
        });
        loadPage(page);
    }

    async function loadPage(page) {
        const el = document.getElementById('pageContent');
        el.innerHTML = '<p style="color:var(--text-dim);">Memuat...</p>';
        try {
            switch(page) {
                case 'dashboard': await loadDashboard(el); break;
                case 'products':  await loadProducts(el); break;
                case 'categories':await loadCategories(el); break;
                case 'bookings':  await loadBookings(el); break;
                case 'reviews':   await loadReviews(el); break;
                case 'users':     await loadUsers(el); break;
                case 'providers': await loadProviders(el); break;
            }
        } catch(e) {
            el.innerHTML = `<p style="color:var(--error);">Error: ${e.message}</p>`;
        }
    }


    async function loadDashboard(el) {
        const d = await api('/dashboard.php');
        el.innerHTML = `
            <div class="toolbar"><h2>Dashboard</h2></div>
            <div class="stats-grid">
                <div class="stat-card"><div class="stat-label">Produk</div><div class="stat-value accent">${d.stats.total_products}</div></div>
                <div class="stat-card"><div class="stat-label">Booking</div><div class="stat-value accent">${d.stats.total_bookings}</div></div>
                <div class="stat-card"><div class="stat-label">Aktif</div><div class="stat-value accent">${d.stats.active_bookings}</div></div>
                <div class="stat-card"><div class="stat-label">User</div><div class="stat-value accent">${d.stats.total_users}</div></div>
                <div class="stat-card"><div class="stat-label">Pendapatan</div><div class="stat-value accent">${rupiah(d.stats.total_revenue)}</div></div>
            </div>
            <div class="dash-section">
                <h2>Booking Terbaru</h2>
                <table class="dash-table"><thead><tr><th>Kode</th><th>User</th><th>Tanggal</th><th>Total</th><th>Status</th></tr></thead>
                <tbody>${d.recent_bookings.map(b => `<tr><td style="font-weight:700;font-size:12.5px;">${b.booking_code}</td><td>${b.user_name}</td><td style="font-size:12.5px;color:var(--text-dim);">${fmtDate(b.start_date)} — ${fmtDate(b.end_date)}</td><td style="font-weight:700;">${rupiah(b.total_price)}</td><td>${statusBadge(b.status)}</td></tr>`).join('') || '<tr><td colspan="5" style="text-align:center;color:var(--text-dimmer);padding:20px;">Kosong</td></tr>'}</tbody></table>
            </div>`;
    }


    let prodPage = 1, prodSearch = '';
    async function loadProducts(el, page = 1, search = '') {
        prodPage = page; prodSearch = search;
        const d = await api(`/products.php?page=${page}&per_page=10&available=&search=${encodeURIComponent(search)}`);
        el.innerHTML = `
            <div class="toolbar">
                <div class="toolbar-left">
                    <h2>Produk (${d.total})</h2>
                    <input class="search-input" placeholder="Cari produk..." value="${search}" onkeyup="if(event.key==='Enter')loadProducts(document.getElementById('pageContent'),1,this.value)">
                </div>
                <button class="btn btn-primary" onclick="showProductForm()">+ Tambah Produk</button>
            </div>
            <table class="dash-table"><thead><tr><th>Nama</th><th>Kategori</th><th>Harga/Hari</th><th>Stok</th><th>Rating</th><th>Aksi</th></tr></thead>
            <tbody>${d.items.map(p => `<tr>
                <td style="font-weight:700;">${p.name}<br><span style="font-size:11px;color:var(--text-dimmer);">${p.store_name}</span></td>
                <td>${p.category_name}</td>
                <td style="font-weight:700;">${rupiah(p.price_per_day)}</td>
                <td>${p.stock}</td>
                <td style="color:var(--star);font-weight:700;">★ ${p.rating}</td>
                <td class="action-btns">
                    <button class="btn btn-ghost btn-sm" onclick="showProductForm(${p.id})">Edit</button>
                    <button class="btn btn-danger btn-sm" onclick="deleteProduct(${p.id},'${p.name.replace(/'/g,"\\'")}')">Hapus</button>
                </td>
            </tr>`).join('') || '<tr><td colspan="6" style="text-align:center;color:var(--text-dimmer);padding:20px;">Tidak ada produk</td></tr>'}</tbody></table>
            <div class="pagination">${Array.from({length:d.total_pages},(_,i)=>i+1).map(p=>`<button class="${p===d.page?'active':''}" onclick="loadProducts(document.getElementById('pageContent'),${p},'${search.replace(/'/g,"\\'")}')">${p}</button>`).join('')}</div>`;
    }

    async function showProductForm(id = null) {
        const cats = await api('/categories.php');
        const catsList = cats.categories;
        let p = { name:'', category_id:'', price_per_day:'', deposit:'', stock:'1', condition:'baru', description:'', location:'', is_available:1 };
        if (id) {
            p = await api(`/products.php?id=${id}`);
        }
        const isEdit = !!id;
        openModal(`
            <h3>${isEdit ? 'Edit' : 'Tambah'} Produk</h3>
            <form onsubmit="return saveProduct(event,${id})">
                <div class="form-group"><label class="form-label">Nama</label><input class="form-input" name="name" value="${p.name}" required></div>
                <div class="form-group"><label class="form-label">Kategori</label><select class="form-input" name="category_id" required>${catsList.map(c=>`<option value="${c.id}" ${c.id==p.category_id?'selected':''}>${c.name}</option>`).join('')}</select></div>
                <div class="form-group"><label class="form-label">Harga/Hari (Rp)</label><input class="form-input" type="number" name="price_per_day" value="${p.price_per_day}" required></div>
                <div class="form-group"><label class="form-label">Deposit (Rp)</label><input class="form-input" type="number" name="deposit" value="${p.deposit||0}"></div>
                <div class="form-group"><label class="form-label">Stok</label><input class="form-input" type="number" name="stock" value="${p.stock}" min="0" required></div>
                <div class="form-group"><label class="form-label">Kondisi</label><select class="form-input" name="condition"><option value="baru" ${p.condition==='baru'?'selected':''}>Baru</option><option value="bekas" ${p.condition==='bekas'?'selected':''}>Bekas</option><option value="sebagus_baru" ${p.condition==='sebagus_baru'?'selected':''}>Seperti Baru</option></select></div>
                <div class="form-group"><label class="form-label">Lokasi</label><input class="form-input" name="location" value="${p.location||''}"></div>
                <div class="form-group"><label class="form-label">Deskripsi</label><textarea class="form-input" name="description" rows="3">${p.description||''}</textarea></div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-ghost" onclick="closeModal()">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>`);
    }

    async function saveProduct(e, id) {
        e.preventDefault();
        const f = e.target;
        const body = Object.fromEntries(new FormData(f));
        body.price_per_day = parseFloat(body.price_per_day);
        body.deposit = parseFloat(body.deposit || 0);
        body.stock = parseInt(body.stock);
        body.is_available = 1;
        try {
            if (id) {
                await api(`/products.php?id=${id}`, { method:'PUT', body });
                toast('Produk berhasil diupdate');
            } else {
                await api('/products.php', { method:'POST', body });
                toast('Produk berhasil ditambahkan');
            }
            closeModal();
            loadProducts(document.getElementById('pageContent'), prodPage, prodSearch);
        } catch(err) { toast(err.message, 'error'); }
    }

    async function deleteProduct(id, name) {
        if (!confirm(`Hapus produk "${name}"?`)) return;
        try {
            await api(`/products.php?id=${id}`, { method:'DELETE' });
            toast('Produk dihapus');
            loadProducts(document.getElementById('pageContent'), prodPage, prodSearch);
        } catch(err) { toast(err.message, 'error'); }
    }


    async function loadCategories(el) {
        const d = await api('/categories.php');
        el.innerHTML = `
            <div class="toolbar">
                <h2>Kategori (${d.categories.length})</h2>
                <button class="btn btn-primary" onclick="showCategoryForm()">+ Tambah Kategori</button>
            </div>
            <table class="dash-table"><thead><tr><th>Nama</th><th>Slug</th><th>Produk</th><th>Aksi</th></tr></thead>
            <tbody>${d.categories.map(c => `<tr>
                <td style="font-weight:700;">${c.name}</td>
                <td style="color:var(--text-dim);font-size:12.5px;">${c.slug}</td>
                <td>${c.product_count}</td>
                <td class="action-btns">
                    <button class="btn btn-ghost btn-sm" onclick="showCategoryForm(${c.id},'${c.name.replace(/'/g,"\\'")}')">Edit</button>
                    <button class="btn btn-danger btn-sm" onclick="deleteCategory(${c.id},'${c.name.replace(/'/g,"\\'")}')">Hapus</button>
                </td>
            </tr>`).join('')}</tbody></table>`;
    }

    function showCategoryForm(id = null, name = '') {
        openModal(`
            <h3>${id ? 'Edit' : 'Tambah'} Kategori</h3>
            <form onsubmit="return saveCategory(event,${id||'null'})">
                <div class="form-group"><label class="form-label">Nama Kategori</label><input class="form-input" name="name" value="${name}" required placeholder="contoh: Kamera"></div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-ghost" onclick="closeModal()">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>`);
    }

    async function saveCategory(e, id) {
        e.preventDefault();
        const name = e.target.name.value;
        try {
            if (id) {
                await api(`/categories.php?id=${id}`, { method:'PUT', body:{name} });
                toast('Kategori diupdate');
            } else {
                await api('/categories.php', { method:'POST', body:{name} });
                toast('Kategori ditambahkan');
            }
            closeModal();
            loadCategories(document.getElementById('pageContent'));
        } catch(err) { toast(err.message, 'error'); }
    }

    async function deleteCategory(id, name) {
        if (!confirm(`Hapus kategori "${name}"?`)) return;
        try {
            await api(`/categories.php?id=${id}`, { method:'DELETE' });
            toast('Kategori dihapus');
            loadCategories(document.getElementById('pageContent'));
        } catch(err) { toast(err.message, 'error'); }
    }


    async function loadBookings(el) {
        const d = await api('/dashboard.php');
        el.innerHTML = `
            <div class="toolbar"><h2>Semua Booking</h2></div>
            <table class="dash-table"><thead><tr><th>Kode</th><th>User</th><th>Tanggal</th><th>Total</th><th>Status</th><th>Bayar</th><th>Aksi</th></tr></thead>
            <tbody>${d.recent_bookings.map(b => `<tr>
                <td style="font-weight:700;font-size:12.5px;">${b.booking_code}</td>
                <td>${b.user_name}</td>
                <td style="font-size:12.5px;color:var(--text-dim);">${fmtDate(b.start_date)} — ${fmtDate(b.end_date)}</td>
                <td style="font-weight:700;">${rupiah(b.total_price)}</td>
                <td>${statusBadge(b.status)}</td>
                <td>${statusBadge(b.payment_status)}</td>
                <td class="action-btns">
                    <select class="filter-select" style="padding:4px 6px;font-size:11px;" onchange="updateBookingStatus(${b.id},this.value)">
                        <option value="">Ubah...</option>
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="active">Active</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                    <button class="btn btn-danger btn-sm" onclick="deleteBooking(${b.id},'${b.booking_code}')">Hapus</button>
                </td>
            </tr>`).join('') || '<tr><td colspan="7" style="text-align:center;color:var(--text-dimmer);padding:20px;">Kosong</td></tr>'}</tbody></table>`;
    }

    async function updateBookingStatus(id, status) {
        if (!status) return;
        try {
            await api(`/bookings.php?id=${id}`, { method:'PUT', body:{status} });
            toast('Status booking diupdate');
            loadBookings(document.getElementById('pageContent'));
        } catch(err) { toast(err.message, 'error'); }
    }

    async function deleteBooking(id, code) {
        if (!confirm(`Hapus booking "${code}"?`)) return;
        try {
            await api(`/bookings.php?id=${id}`, { method:'DELETE' });
            toast('Booking dihapus');
            loadBookings(document.getElementById('pageContent'));
        } catch(err) { toast(err.message, 'error'); }
    }


    async function loadReviews(el) {
        const d = await api('/admin-reviews.php');
        el.innerHTML = `
            <div class="toolbar"><h2>Semua Ulasan (${d.total})</h2></div>
            <table class="dash-table"><thead><tr><th>User</th><th>Produk</th><th>Rating</th><th>Komentar</th><th>Tanggal</th><th>Aksi</th></tr></thead>
            <tbody>${d.items.map(r => `<tr>
                <td style="font-weight:700;">${r.user_name}</td>
                <td>${r.product_name}</td>
                <td style="color:var(--star);font-weight:700;">★ ${r.rating}</td>
                <td style="max-width:300px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${r.comment||'—'}</td>
                <td style="font-size:12.5px;color:var(--text-dim);">${fmtDate(r.created_at)}</td>
                <td><button class="btn btn-danger btn-sm" onclick="deleteReview(${r.id})">Hapus</button></td>
            </tr>`).join('') || '<tr><td colspan="6" style="text-align:center;color:var(--text-dimmer);padding:20px;">Belum ada ulasan</td></tr>'}</tbody></table>`;
    }

    async function deleteReview(id) {
        if (!confirm('Hapus ulasan ini?')) return;
        try {
            await api(`/reviews.php?id=${id}`, { method:'DELETE' });
            toast('Ulasan dihapus');
            loadReviews(document.getElementById('pageContent'));
        } catch(err) { toast(err.message, 'error'); }
    }


    let userPage = 1;
    async function loadUsers(el, page = 1) {
        userPage = page;
        const d = await api(`/users.php?page=${page}`);
        el.innerHTML = `
            <div class="toolbar"><h2>User (${d.total})</h2></div>
            <table class="dash-table"><thead><tr><th>Nama</th><th>Email</th><th>Role</th><th>Status</th><th>Daftar</th><th>Aksi</th></tr></thead>
            <tbody>${d.items.map(u => `<tr>
                <td style="font-weight:700;">${u.name}</td>
                <td style="color:var(--text-dim);font-size:12.5px;">${u.email}</td>
                <td>${statusBadge(u.role)}</td>
                <td>${u.is_active ? '<span class="badge active">Aktif</span>' : '<span class="badge cancelled">Nonaktif</span>'}</td>
                <td style="font-size:12.5px;color:var(--text-dim);">${fmtDate(u.created_at)}</td>
                <td class="action-btns">
                    <button class="btn btn-ghost btn-sm" onclick="showUserForm(${u.id})">Edit</button>
                    <button class="btn btn-danger btn-sm" onclick="deleteUser(${u.id},'${u.name.replace(/'/g,"\\'")}')">Hapus</button>
                </td>
            </tr>`).join('')}</tbody></table>
            <div class="pagination">${Array.from({length:d.total_pages},(_,i)=>i+1).map(p=>`<button class="${p===d.page?'active':''}" onclick="loadUsers(document.getElementById('pageContent'),${p})">${p}</button>`).join('')}</div>`;
    }

    async function showUserForm(id) {
        const u = await api(`/users.php?id=${id}`);
        openModal(`
            <h3>Edit User</h3>
            <form onsubmit="return saveUser(event,${id})">
                <div class="form-group"><label class="form-label">Nama</label><input class="form-input" name="name" value="${u.name}" required></div>
                <div class="form-group"><label class="form-label">Email</label><input class="form-input" value="${u.email}" disabled></div>
                <div class="form-group"><label class="form-label">Phone</label><input class="form-input" name="phone" value="${u.phone||''}"></div>
                <div class="form-group"><label class="form-label">Role</label><select class="form-input" name="role"><option value="Customer" ${u.role==='Customer'?'selected':''}>Customer</option><option value="Vendor" ${u.role==='Vendor'?'selected':''}>Vendor</option><option value="Dev" ${u.role==='Dev'?'selected':''}>Dev</option></select></div>
                <div class="form-group"><label class="form-label">Status</label><select class="form-input" name="is_active"><option value="1" ${u.is_active?'selected':''}>Aktif</option><option value="0" ${!u.is_active?'selected':''}>Nonaktif</option></select></div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-ghost" onclick="closeModal()">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>`);
    }

    async function saveUser(e, id) {
        e.preventDefault();
        const body = Object.fromEntries(new FormData(e.target));
        body.is_active = parseInt(body.is_active);
        try {
            await api(`/users.php?id=${id}`, { method:'PUT', body });
            toast('User diupdate');
            closeModal();
            loadUsers(document.getElementById('pageContent'), userPage);
        } catch(err) { toast(err.message, 'error'); }
    }

    async function deleteUser(id, name) {
        if (!confirm(`Hapus user "${name}"?`)) return;
        try {
            await api(`/users.php?id=${id}`, { method:'DELETE' });
            toast('User dihapus');
            loadUsers(document.getElementById('pageContent'), userPage);
        } catch(err) { toast(err.message, 'error'); }
    }

    // ========== PROVIDERS ==========
    async function loadProviders(el) {
        const d = await api('/providers.php');
        el.innerHTML = `
            <div class="toolbar"><h2>Provider (${d.providers.length})</h2></div>
            <table class="dash-table"><thead><tr><th>Nama Toko</th><th>Lokasi</th><th>Rating</th><th>Transaksi</th><th>Verified</th><th>Aksi</th></tr></thead>
            <tbody>${d.providers.map(p => `<tr>
                <td style="font-weight:700;">${p.store_name}</td>
                <td>${p.location||'—'}</td>
                <td style="color:var(--star);font-weight:700;">★ ${p.rating}</td>
                <td>${p.total_transactions}+</td>
                <td>${p.is_verified ? '<span class="badge active">Ya</span>' : '<span class="badge cancelled">Tidak</span>'}</td>
                <td class="action-btns">
                    <button class="btn btn-ghost btn-sm" onclick="showProviderForm(${p.id})">Edit</button>
                    <button class="btn btn-danger btn-sm" onclick="deleteProvider(${p.id},'${p.store_name.replace(/'/g,"\\'")}')">Hapus</button>
                </td>
            </tr>`).join('')}</tbody></table>`;
    }

    async function showProviderForm(id) {
        const p = await api(`/providers.php?id=${id}`);
        openModal(`
            <h3>Edit Provider</h3>
            <form onsubmit="return saveProvider(event,${id})">
                <div class="form-group"><label class="form-label">Nama Toko</label><input class="form-input" name="store_name" value="${p.store_name}" required></div>
                <div class="form-group"><label class="form-label">Lokasi</label><input class="form-input" name="location" value="${p.location||''}"></div>
                <div class="form-group"><label class="form-label">Deskripsi</label><textarea class="form-input" name="description" rows="3">${p.description||''}</textarea></div>
                <div class="form-group"><label class="form-label">Verified</label><select class="form-input" name="is_verified"><option value="1" ${p.is_verified?'selected':''}>Ya</option><option value="0" ${!p.is_verified?'selected':''}>Tidak</option></select></div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-ghost" onclick="closeModal()">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>`);
    }

    async function saveProvider(e, id) {
        e.preventDefault();
        const body = Object.fromEntries(new FormData(e.target));
        body.is_verified = parseInt(body.is_verified);
        try {
            await api(`/providers.php?id=${id}`, { method:'PUT', body });
            toast('Provider diupdate');
            closeModal();
            loadProviders(document.getElementById('pageContent'));
        } catch(err) { toast(err.message, 'error'); }
    }

    async function deleteProvider(id, name) {
        if (!confirm(`Hapus provider "${name}"? Produk terkait juga akan dihapus.`)) return;
        try {
            await api(`/providers.php?id=${id}`, { method:'DELETE' });
            toast('Provider dihapus');
            loadProviders(document.getElementById('pageContent'));
        } catch(err) { toast(err.message, 'error'); }
    }

    loadPage('dashboard');
    </script>
</body>
</html>

<?php
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: /pkk/pages/login.php?redirect=/pkk/pages/vendor/index.php');
    exit;
}
$userName  = $_SESSION['user_name']  ?? 'User';
$userRole  = $_SESSION['user_role']  ?? 'Customer';
$userEmail = $_SESSION['user_email'] ?? '';
$active = 'dashboard';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Vendor — RentVision</title>
    <link rel="icon" type="image/png" sizes="256x256" href="/pkk/rentvision-favicon.png">
    <link rel="stylesheet" href="/pkk/assets/auth.css">
    <link rel="stylesheet" href="/pkk/assets/vendor.css">
</head>
<body>
<div class="vendor-layout">
    <aside class="vendor-sidebar">
        <div class="auth-brand" style="margin-bottom:6px;">
            <div class="auth-brand-mark"><img src="/pkk/Rentvision logo.png" alt="RentVision"></div>
            <div class="auth-brand-name">Rent<span>Vision</span></div>
        </div>

        <div style="padding:12px; background:var(--bg-panel-2, #1a1d29); border-radius:var(--radius-sm,12px); border:1px solid var(--border, #262a3a);">
            <div style="font-size:13px; font-weight:700;"><?= htmlspecialchars($userName) ?></div>
            <div style="font-size:11.5px; color:var(--text-dimmer, #6c7086);" id="sidebarStore">Memuat toko...</div>
            <div style="margin-top:6px;"><span class="badge confirmed" style="font-size:10px;"><?= htmlspecialchars($userRole) ?></span></div>
        </div>

        <nav class="vendor-nav">
            <a class="vn-link <?= $active==='dashboard'?'active':'' ?>" href="/pkk/pages/vendor/index.php">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg>
                Dashboard
            </a>
            <a class="vn-link <?= $active==='products'?'active':'' ?>" href="/pkk/pages/vendor/products.php">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 8l-9-5-9 5v8l9 5 9-5Z"/><path d="M3 8l9 5 9-5M12 13v8"/></svg>
                Produk
            </a>
            <a class="vn-link" id="storeLink" href="/pkk/pages/vendor/store.php">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l1.5-5h15L21 9M3 9a3 3 0 0 0 6 0 3 3 0 0 0 6 0 3 3 0 0 0 6 0M4 9v11h16V9"/></svg>
                Profil Toko
            </a>
            <div style="flex:1"></div>
            <a class="vn-link" href="/pkk/">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9.5 12 3l9 6.5V21a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1Z"/></svg>
                Beranda
            </a>
        </nav>
    </aside>

    <main class="vendor-main">
        <div class="vendor-head">
            <div>
                <h1>Dashboard Vendor</h1>
                <span class="sub" id="storeName">Selamat datang, <?= htmlspecialchars($userName) ?>!</span>
            </div>
            <a class="btn-primary" style="width:auto; padding:10px 16px; font-size:13px; text-decoration:none;" href="/pkk/pages/vendor/products.php">+ Tambah Produk</a>
        </div>

        <div class="alert" id="alert"></div>

        <div class="stats-grid" id="statsGrid">
            <div class="stat-card"><div class="stat-label">Produk Saya</div><div class="stat-value" id="stProducts">—</div></div>
            <div class="stat-card"><div class="stat-label">Total Booking</div><div class="stat-value accent" id="stBookings">—</div></div>
            <div class="stat-card"><div class="stat-label">Booking Aktif</div><div class="stat-value accent" id="stActive">—</div></div>
            <div class="stat-card"><div class="stat-label">Pendapatan</div><div class="stat-value accent" id="stRevenue">—</div></div>
        </div>

        <div class="dash-section">
            <h2>Booking Masuk</h2>
            <div style="overflow-x:auto;">
                <table class="dash-table">
                    <thead><tr><th>Kode</th><th>Customer</th><th>Produk</th><th>Tanggal</th><th>Subtotal (saya)</th><th>Status</th><th>Pembayaran</th><th>Aksi</th></tr></thead>
                    <tbody id="bookingTable"><tr><td colspan="8" style="text-align:center;color:var(--text-dimmer,#6c7086);padding:24px;">Memuat data...</td></tr></tbody>
                </table>
            </div>
        </div>

        <div class="dash-section">
            <h2>Stok Menipis</h2>
            <div style="overflow-x:auto;">
                <table class="dash-table">
                    <thead><tr><th>Produk</th><th>Stok</th></tr></thead>
                    <tbody id="lowStockTable"><tr><td colspan="2" style="text-align:center;color:var(--text-dimmer,#6c7086);padding:24px;">Memuat data...</td></tr></tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<script>
function rupiah(n){ return 'Rp ' + Number(n).toLocaleString('id-ID'); }
function fmtDate(d){ return new Date(d).toLocaleDateString('id-ID',{day:'numeric',month:'short',year:'numeric'}); }

function bookingStatus(b) {
    if (b.status === 'completed') return { cls: 'completed', label: 'Sudah dikembalikan' };
    if (b.status === 'active') {
        const end = new Date(b.end_date + 'T00:00:00');
        const today = new Date(); today.setHours(0,0,0,0);
        if (end < today) return { cls: 'expired', label: 'Waktu booking habis' };
    }
    return { cls: b.status, label: b.status };
}

async function load() {
    try {
        const res = await fetch('/pkk/api/vendor/dashboard.php');
        if (res.status === 403 || res.status === 401) {
            window.location.href = '/pkk/pages/login.php?redirect=/pkk/pages/vendor/index.php';
            return;
        }
        if (!res.ok) throw new Error(await res.text());
        const data = await res.json();

        document.getElementById('stProducts').textContent = data.stats.total_products;
        document.getElementById('stBookings').textContent = data.stats.total_bookings;
        document.getElementById('stActive').textContent = data.stats.active_bookings;
        document.getElementById('stRevenue').textContent = rupiah(data.stats.revenue);
        document.getElementById('storeName').textContent = 'Toko: ' + (data.provider.store_name || '-');
        document.getElementById('sidebarStore').textContent = data.provider.store_name || '-';
        document.getElementById('storeLink').href = '/pkk/pages/store.php?slug=' + (data.provider.slug || '');

        const bt = document.getElementById('bookingTable');
        if (!data.bookings.length) {
            bt.innerHTML = '<tr><td colspan="8" style="text-align:center;color:var(--text-dimmer,#6c7086);padding:24px;">Belum ada booking</td></tr>';
        } else {
            const canReturn = (b) => b.status !== 'completed' && b.status !== 'cancelled';
            bt.innerHTML = data.bookings.map(b => {
                const st = bookingStatus(b);
                return '<tr>' +
                '<td style="font-weight:700;font-size:12.5px;">'+b.booking_code+'</td>' +
                '<td>'+b.user_name+'</td>' +
                '<td style="font-size:12.5px;color:var(--text-dim,#a3a7bd);">'+(b.product_names || '-')+'</td>' +
                '<td style="font-size:12.5px;color:var(--text-dim,#a3a7bd);">'+fmtDate(b.start_date)+' — '+fmtDate(b.end_date)+'</td>' +
                '<td style="font-weight:700;">'+rupiah(b.vendor_subtotal)+'</td>' +
                '<td><span class="badge '+st.cls+'">'+st.label+'</span></td>' +
                '<td><span class="badge '+(b.payment_status==="paid"?"paid":"unpaid")+'">'+b.payment_status+'</span></td>' +
                '<td>'+(canReturn(b)
                    ? '<button class="btn-primary return-btn" style="padding:7px 12px;font-size:12px;border:none;border-radius:8px;cursor:pointer;" data-id="'+b.id+'">Konfirmasi Kembali</button>'
                    : '<span style="font-size:12px;color:var(--text-dimmer,#6c7086);">Selesai</span>')+'</td>' +
                '</tr>';
            }).join('');
        }

        const lt = document.getElementById('lowStockTable');
        if (!data.low_stock.length) {
            lt.innerHTML = '<tr><td colspan="2" style="text-align:center;color:var(--text-dimmer,#6c7086);padding:24px;">Stok aman</td></tr>';
        } else {
            lt.innerHTML = data.low_stock.map(p => '<tr><td style="font-weight:700;">'+p.name+'</td><td>'+p.stock+'</td></tr>').join('');
        }
    } catch (e) {
        const a = document.getElementById('alert');
        a.className = 'alert error show'; a.textContent = 'Gagal memuat data panel';
    }
}
load();

document.getElementById('bookingTable').addEventListener('click', async function (e) {
    const btn = e.target.closest('.return-btn');
    if (!btn) return;
    if (!confirm('Konfirmasi barang sudah dikembalikan oleh customer?')) return;
    btn.disabled = true;
    const a = document.getElementById('alert');
    try {
        const res = await fetch('/pkk/api/vendor/return.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ booking_id: btn.dataset.id })
        });
        const data = await res.json();
        if (!res.ok) throw new Error(data.error || 'Gagal memproses');
        a.className = 'alert success show'; a.textContent = data.message;
        load();
    } catch (err) {
        btn.disabled = false;
        a.className = 'alert error show'; a.textContent = err.message;
    }
});
</script>
</body>
</html>

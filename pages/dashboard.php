<?php
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: /pkk/pages/login.php?redirect=/pkk/pages/dashboard.php');
    exit;
}
$userName  = $_SESSION['user_name']  ?? 'User';
$userRole  = $_SESSION['user_role']  ?? 'Customer';
$userEmail = $_SESSION['user_email'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — RentVision</title>
    <link rel="icon" type="image/png" sizes="256x256" href="/pkk/rentvision-favicon.png">
    <link rel="stylesheet" href="/pkk/assets/auth.css">
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

            <div style="padding:12px; background:var(--bg-panel-2); border-radius:var(--radius-sm); border:1px solid var(--border);">
                <div style="font-size:13px; font-weight:700;"><?= htmlspecialchars($userName) ?></div>
                <div style="font-size:11.5px; color:var(--text-dimmer);"><?= htmlspecialchars($userEmail) ?></div>
                <div style="margin-top:6px;">
                    <span class="badge active" style="font-size:10px;"><?= ucfirst($userRole) ?></span>
                </div>
            </div>

            <nav style="display:flex; flex-direction:column; gap:2px;">
                <a href="#" class="nav-item active" style="display:flex; align-items:center; gap:10px; padding:10px 12px; border-radius:10px; font-size:13.5px; font-weight:600; color:var(--text); background:linear-gradient(135deg, rgba(124,92,252,.18), rgba(91,140,255,.10));">
                    Dashboard
                </a>
                <a href="/pkk/" class="nav-item" style="display:flex; align-items:center; gap:10px; padding:10px 12px; border-radius:10px; font-size:13.5px; font-weight:500; color:var(--text-dim);">
                    Beranda
                </a>
                <a href="/pkk/pages/logout.php" class="nav-item" style="display:flex; align-items:center; gap:10px; padding:10px 12px; border-radius:10px; font-size:13.5px; font-weight:500; color:var(--error);">
                    Keluar
                </a>
            </nav>
        </aside>

        <main class="dash-main">
            <div class="dash-header">
                <h1>Dashboard</h1>
                <span style="font-size:13px; color:var(--text-dim);">Selamat datang, <?= htmlspecialchars($userName) ?>!</span>
            </div>

            <!-- Stats -->
            <div class="stats-grid" id="statsGrid">
                <div class="stat-card">
                    <div class="stat-label">Total Produk</div>
                    <div class="stat-value accent" id="statProducts">—</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Booking</div>
                    <div class="stat-value accent" id="statBookings">—</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Booking Aktif</div>
                    <div class="stat-value accent" id="statActive">—</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total User</div>
                    <div class="stat-value accent" id="statUsers">—</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Pendapatan</div>
                    <div class="stat-value accent" id="statRevenue">—</div>
                </div>
            </div>


            <div class="dash-section">
                <h2>Booking Terbaru</h2>
                <div style="overflow-x:auto;">
                    <table class="dash-table">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>User</th>
                                <th>Tanggal</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Pembayaran</th>
                            </tr>
                        </thead>
                        <tbody id="bookingTable">
                            <tr><td colspan="6" style="text-align:center; color:var(--text-dimmer); padding:24px;">Memuat data...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>


            <div class="dash-section">
                <h2>User Terbaru</h2>
                <div style="overflow-x:auto;">
                    <table class="dash-table">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Terdaftar</th>
                            </tr>
                        </thead>
                        <tbody id="userTable">
                            <tr><td colspan="4" style="text-align:center; color:var(--text-dimmer); padding:24px;">Memuat data...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
    function formatRupiah(num) {
        return 'Rp ' + Number(num).toLocaleString('id-ID');
    }
    function formatDate(d) {
        return new Date(d).toLocaleDateString('id-ID', { day:'numeric', month:'short', year:'numeric' });
    }

    async function loadDashboard() {
        try {
            const res = await fetch('/pkk/api/dashboard.php');
            if (!res.ok) throw new Error('Unauthorized');
            const data = await res.json();


            document.getElementById('statProducts').textContent = data.stats.total_products;
            document.getElementById('statBookings').textContent = data.stats.total_bookings;
            document.getElementById('statActive').textContent = data.stats.active_bookings;
            document.getElementById('statUsers').textContent = data.stats.total_users;
            document.getElementById('statRevenue').textContent = formatRupiah(data.stats.total_revenue);


            const tbody = document.getElementById('bookingTable');
            if (data.recent_bookings.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; color:var(--text-dimmer); padding:24px;">Belum ada booking</td></tr>';
            } else { 
                tbody.innerHTML = data.recent_bookings.map(b => `
                    <tr>
                        <td style="font-weight:700; font-size:12.5px;">${b.booking_code}</td>
                        <td>${b.user_name}</td>
                        <td style="font-size:12.5px; color:var(--text-dim);">${formatDate(b.start_date)} — ${formatDate(b.end_date)}</td>
                        <td style="font-weight:700;">${formatRupiah(b.total_price)}</td>
                        <td><span class="badge ${b.status}">${b.status}</span></td>
                        <td><span class="badge ${b.payment_status}">${b.payment_status}</span></td>
                    </tr>
                    
                `).join('');
            }

            // Users
            const uBody = document.getElementById('userTable');
            if (data.recent_users.length === 0) {
                uBody.innerHTML = '<tr><td colspan="4" style="text-align:center; color:var(--text-dimmer); padding:24px;">Belum ada user</td></tr>';
            } else {
                uBody.innerHTML = data.recent_users.map(u => `
                    <tr>
                        <td style="font-weight:700;">${u.name}</td>
                        <td style="color:var(--text-dim);">${u.email}</td>
                        <td><span class="badge ${u.role === 'Dev' ? 'completed' : u.role === 'Vendor' ? 'confirmed' : 'active'}">${u.role}</span></td>
                        <td style="font-size:12.5px; color:var(--text-dim);">${formatDate(u.created_at)}</td>
                    </tr>
                `).join('');
            }
        } catch (err) {
            window.location.href = '/pkk/pages/login.php?redirect=/pkk/pages/dashboard.php';
        }
    }

    loadDashboard();
    </script>
</body>
</html>

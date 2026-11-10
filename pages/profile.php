<?php
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: /pkk/pages/login.php?redirect=/pkk/pages/profile.php');
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
    <title>Profil Saya — RentVision</title>
    <link rel="icon" type="image/png" sizes="256x256" href="/pkk/rentvision-favicon.png">
    <link rel="stylesheet" href="/pkk/assets/auth.css">
    <link rel="stylesheet" href="/pkk/pages/profil.css">
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
                    <span class="badge active" style="font-size:10px;"><?= htmlspecialchars($userRole) ?></span>
                </div>
            </div>

            <nav style="display:flex; flex-direction:column; gap:2px;">
                <a href="/pkk/" class="nav-item" style="display:flex; align-items:center; gap:10px; padding:10px 12px; border-radius:10px; font-size:13.5px; font-weight:500; color:var(--text-dim);">
                    Dashboard
                </a>
                <a href="/pkk/pages/jelajahi.html" class="nav-item" style="display:flex; align-items:center; gap:10px; padding:10px 12px; border-radius:10px; font-size:13.5px; font-weight:500; color:var(--text-dim);">
                    Jelajahi Gear
                </a>
                <a href="/pkk/pages/booking.html" class="nav-item" style="display:flex; align-items:center; gap:10px; padding:10px 12px; border-radius:10px; font-size:13.5px; font-weight:500; color:var(--text-dim);">
                    Booking Saya
                </a>
                <a href="/pkk/pages/wishlist.html" class="nav-item" style="display:flex; align-items:center; gap:10px; padding:10px 12px; border-radius:10px; font-size:13.5px; font-weight:500; color:var(--text-dim);">
                    Wishlist
                </a>
                <a href="/pkk/pages/ulasan.html" class="nav-item" style="display:flex; align-items:center; gap:10px; padding:10px 12px; border-radius:10px; font-size:13.5px; font-weight:500; color:var(--text-dim);">
                    Ulasan
                </a>
                <?php if ($userRole === 'Vendor'): ?>
                <a href="/pkk/pages/vendor/index.php" class="nav-item" style="display:flex; align-items:center; gap:10px; padding:10px 12px; border-radius:10px; font-size:13.5px; font-weight:500; color:var(--text-dim);">
                    Panel Vendor
                </a>
                <?php endif; ?>
                <a href="#" class="nav-item active" style="display:flex; align-items:center; gap:10px; padding:10px 12px; border-radius:10px; font-size:13.5px; font-weight:600; color:var(--text); background:linear-gradient(135deg, rgba(124,92,252,.18), rgba(91,140,255,.10));">
                    Profil
                </a>
                <a href="/pkk/pages/logout.php" class="nav-item" style="display:flex; align-items:center; gap:10px; padding:10px 12px; border-radius:10px; font-size:13.5px; font-weight:500; color:var(--error);">
                    Keluar
                </a>
            </nav>
        </aside>

        <main class="dash-main">
            <div class="profile-head">
                <h1>Profil Saya</h1>
                <a class="profile-back" href="/pkk/">&larr; Kembali ke beranda</a>
            </div>

            <div class="alert" id="alert" role="alert" tabindex="-1"></div>

            <!-- Info akun -->
            <div class="profile-info-card">
                <div class="profile-avatar-lg" id="avatarLg">...</div>
                <div class="profile-info-main">
                    <h3 class="profile-info-name" id="infoName">...</h3>
                    <p class="profile-info-email" id="infoEmail">...</p>
                    <div class="profile-info-meta">
                        <span><strong id="infoRole">-</strong></span>
                        <span>Bergabung: <strong id="infoSince">-</strong></span>
                        <span>No. HP: <strong id="infoPhone">-</strong></span>
                    </div>
                </div>
            </div>

            <!-- Edit profil -->
            <section class="profile-section">
                <h2>Edit Profil</h2>
                <p class="section-sub">Perbarui informasi dasar akun Anda. Email tidak dapat diubah.</p>
                <form id="profileForm" novalidate>
                    <div class="form-group">
                        <label class="form-label" for="name">Nama Lengkap</label>
                        <input class="form-input" type="text" id="name" required>
                        <p class="error-msg" id="nameErr"></p>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="phone">Nomor Telepon</label>
                        <input class="form-input" type="tel" id="phone" placeholder="08xxxxxxxxxx">
                        <p class="error-msg" id="phoneErr"></p>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="avatar">URL Foto Profil (opsional)</label>
                        <input class="form-input" type="url" id="avatar" placeholder="https://... (kosongkan untuk inisial)">
                        <p class="error-msg" id="avatarErr"></p>
                    </div>
                    <button type="submit" class="btn-primary" id="profileBtn" style="max-width:220px;">Simpan Perubahan</button>
                </form>
            </section>

            <!-- Ganti password -->
            <section class="profile-section">
                <h2>Ganti Password</h2>
                <p class="section-sub">Gunakan password baru minimal 6 karakter.</p>
                <form id="passwordForm" novalidate>
                    <div class="form-group">
                        <label class="form-label" for="current_password">Password Saat Ini</label>
                        <input class="form-input" type="password" id="current_password" required autocomplete="current-password">
                        <p class="error-msg" id="currentErr"></p>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="new_password">Password Baru</label>
                        <input class="form-input" type="password" id="new_password" required minlength="6" autocomplete="new-password">
                        <p class="error-msg" id="newErr"></p>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="new_password2">Konfirmasi Password Baru</label>
                        <input class="form-input" type="password" id="new_password2" required minlength="6" autocomplete="new-password">
                        <p class="error-msg" id="newErr2"></p>
                    </div>
                    <button type="submit" class="btn-primary" id="passwordBtn" style="max-width:220px;">Simpan Password</button>
                </form>
            </section>
        </main>
    </div>

    <script>
    const $ = (s) => document.querySelector(s);

    function initials(name) {
        return (name || '?').split(' ').map(w => w[0]).filter(Boolean).slice(0, 2).join('').toUpperCase();
    }

    const ROLE_BADGE = { 'Dev': 'completed', 'Vendor': 'confirmed', 'Customer': 'active' };

    function showAlert(msg, type) {
        const el = $('#alert');
        el.className = 'alert show ' + (type || '');
        el.textContent = msg;
        el.focus();
    }
    function clearAlert() { $('#alert').className = 'alert'; $('#alert').textContent = ''; }
    function clearFieldErrors() {
        document.querySelectorAll('.error-msg').forEach(e => e.classList.remove('show'));
        document.querySelectorAll('.form-input').forEach(i => i.classList.remove('error'));
    }
    function setFieldError(id, msg) {
        const field = document.getElementById(id);
        const err = document.getElementById(id + 'Err');
        field.classList.add('error');
        err.textContent = msg;
        err.classList.add('show');
    }

    function renderAvatar(avatar, name) {
        const box = $('#avatarLg');
        if (avatar) {
            box.innerHTML = '<img src="' + avatar + '" alt="Foto profil" style="width:100%;height:100%;object-fit:cover;border-radius:22px;">';
        } else {
            box.style.background = 'var(--accent-grad)';
            box.textContent = initials(name);
        }
    }

    async function loadProfile() {
        try {
            const res = await fetch('/pkk/api/auth/profile.php');
            if (!res.ok) throw new Error('Unauthorized');
            const d = await res.json();

            $('#name').value = d.name || '';
            $('#phone').value = d.phone || '';
            $('#avatar').value = d.avatar || '';

            $('#infoName').textContent = d.name;
            $('#infoEmail').textContent = d.email;
            $('#infoRole').textContent = d.role;
            $('#infoSince').textContent = d.created_at;
            $('#infoPhone').textContent = d.phone || '-';

            const badge = document.getElementById('infoRole');
            badge.className = 'badge ' + (ROLE_BADGE[d.role] || 'active');
            badge.textContent = d.role;

            renderAvatar(d.avatar, d.name);
        } catch (e) {
            window.location.href = '/pkk/pages/login.php?redirect=/pkk/pages/profile.php';
        }
    }

    async function submitProfile(e) {
        e.preventDefault();
        clearFieldErrors(); clearAlert();

        const name = $('#name').value.trim();
        if (!name) { setFieldError('name', 'Nama wajib diisi'); return; }
        const avatar = $('#avatar').value.trim();

        const btn = $('#profileBtn');
        btn.disabled = true; btn.textContent = 'Menyimpan...';
        try {
            const res = await fetch('/pkk/api/auth/profile.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name, phone: $('#phone').value.trim(), avatar })
            });
            const data = await res.json();
            if (!res.ok) { showAlert(data.error || 'Gagal menyimpan', 'error'); return; }
            showAlert(data.message, 'success');
            $('#infoName').textContent = name;
            renderAvatar(avatar, name);
            if (data.message) { /* nama session sudah di-sync oleh API */ }
        } catch (err) {
            showAlert('Terjadi kesalahan jaringan', 'error');
        } finally {
            btn.disabled = false; btn.textContent = 'Simpan Perubahan';
        }
    }

    async function submitPassword(e) {
        e.preventDefault();
        clearFieldErrors(); clearAlert();

        const cur = $('#current_password').value;
        const nw = $('#new_password').value;
        const nw2 = $('#new_password2').value;

        if (!cur) { setFieldError('current_password', 'Password saat ini wajib diisi'); return; }
        if (nw.length < 6) { setFieldError('new_password', 'Password baru minimal 6 karakter'); return; }
        if (nw !== nw2) { setFieldError('new_password2', 'Konfirmasi password tidak cocok'); return; }

        const btn = $('#passwordBtn');
        btn.disabled = true; btn.textContent = 'Menyimpan...';
        try {
            const res = await fetch('/pkk/api/auth/profile.php?op=password', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ current_password: cur, new_password: nw, new_password2: nw2 })
            });
            const data = await res.json();
            if (!res.ok) {
                if (data.error && data.error.includes('saat ini')) { setFieldError('current_password', data.error); }
                else { showAlert(data.error || 'Gagal mengganti password', 'error'); }
                return;
            }
            showAlert(data.message, 'success');
            $('#passwordForm').reset();
        } catch (err) {
            showAlert('Terjadi kesalahan jaringan', 'error');
        } finally {
            btn.disabled = false; btn.textContent = 'Simpan Password';
        }
    }

    $('#profileForm').addEventListener('submit', submitProfile);
    $('#passwordForm').addEventListener('submit', submitPassword);
    loadProfile();
    </script>
</body>
</html>

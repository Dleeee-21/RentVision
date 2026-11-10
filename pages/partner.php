<?php
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: /pkk/pages/login.php?redirect=/pkk/pages/partner.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Jadi Partner — RentVision</title>
    <link rel="icon" type="image/png" sizes="256x256" href="/pkk/rentvision-favicon.png">
    <link rel="stylesheet" href="/pkk/assets/auth.css">
</head>
<body>
    <div class="auth-wrap">
        <div class="auth-card">
            <div class="auth-brand">
                <div class="auth-brand-mark">
                    <img src="../Rentvision logo.png" alt="RentVision">
                </div>
                <div class="auth-brand-name">Rent<span>Vision</span></div>
            </div>

            <h1 class="auth-title">Daftar Jadi Partner</h1>
            <p class="auth-subtitle">Buka tokomu di RentVision dan dapatkan penghasilan tambahan</p>

            <div class="alert" id="alert" role="alert"></div>

            <form id="partnerForm" onsubmit="return handleSubmit(event)">
                <div class="form-group">
                    <label class="form-label" for="store_name">Nama Toko</label>
                    <input class="form-input" type="text" id="store_name" name="store_name" placeholder="Mis. KameraKita" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="location">Lokasi Toko</label>
                    <input class="form-input" type="text" id="location" name="location" placeholder="Kecamatan, Kota" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="description">Deskripsi Toko (opsional)</label>
                    <textarea class="form-input" id="description" name="description" rows="4" placeholder="Ceritakan singkat tentang tokomu..."></textarea>
                </div>
                <button type="submit" class="btn-primary" id="submitBtn">Daftar Sekarang</button>
            </form>

            <div class="auth-footer">
                Sudah jadi partner? <a href="/pkk/">Ke beranda</a>
            </div>
        </div>

        <div class="auth-footer" style="margin-top:18px;">
            <a href="/pkk/">&larr; Kembali ke beranda</a>
        </div>
    </div>

    <script>
    async function handleSubmit(e) {
        e.preventDefault();
        const btn = document.getElementById('submitBtn');
        const alert = document.getElementById('alert');
        btn.disabled = true;
        btn.textContent = 'Mendaftar...';
        alert.className = 'alert';

        try {
            const res = await fetch('/pkk/api/partners.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    store_name:  document.getElementById('store_name').value,
                    location:    document.getElementById('location').value,
                    description: document.getElementById('description').value
                })
            });
            const data = await res.json();

            if (res.status === 401) {
                alert.className = 'alert error show';
                alert.textContent = data.error || 'Silakan login terlebih dahulu';
                setTimeout(() => { window.location.href = '/pkk/pages/login.php?redirect=/pkk/pages/partner.php'; }, 900);
                btn.disabled = false;
                btn.textContent = 'Daftar Sekarang';
                return;
            }

            if (res.ok) {
                alert.className = 'alert success show';
                alert.textContent = data.message + '! Mengalihkan...';
                setTimeout(() => { window.location.href = '/pkk/pages/dashboard.php'; }, 900);
            } else {
                alert.className = 'alert error show';
                alert.textContent = data.error || 'Pendaftaran gagal';
                btn.disabled = false;
                btn.textContent = 'Daftar Sekarang';
            }
        } catch (err) {
            alert.className = 'alert error show';
            alert.textContent = 'Terjadi kesalahan jaringan';
            btn.disabled = false;
            btn.textContent = 'Daftar Sekarang';
        }
    }
    </script>
</body>
</html>

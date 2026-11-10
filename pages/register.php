<?php
session_start();
if (!empty($_SESSION['user_id'])) {
    header('Location: /pkk/');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar — RentVision</title>
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

            <h1 class="auth-title">Buat Akun Baru</h1>
            <p class="auth-subtitle">Daftar untuk mulai menyewa peralatan fotografi</p>

            <div class="alert" id="alert"></div>

            <form id="registerForm" onsubmit="return handleRegister(event)">
                <div class="form-group">
                    <label class="form-label" for="name">Nama Lengkap</label>
                    <input class="form-input" type="text" id="name" name="name" placeholder="Masukkan nama" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="email">Email</label>
                    <input class="form-input" type="email" id="email" name="email" placeholder="Masukkan email" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="phone">Nomor Telepon (opsional)</label>
                    <input class="form-input" type="tel" id="phone" name="phone" placeholder="08xxxxxxxxxx">
                </div>
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input class="form-input" type="password" id="password" name="password" placeholder="Minimal 6 karakter" required minlength="6">
                </div>
                <div class="form-group">
                    <label class="form-label" for="password2">Konfirmasi Password</label>
                    <input class="form-input" type="password" id="password2" name="password2" placeholder="Ulangi password" required minlength="6">
                </div>
                <button type="submit" class="btn-primary" id="submitBtn">Daftar</button>
            </form>

            <div class="auth-footer">
                Sudah punya akun? <a href="/pkk/pages/login.php">Masuk</a>
            </div>
        </div>

        <div class="auth-footer" style="margin-top:18px;">
            <a href="/pkk/">&larr; Kembali ke beranda</a>
        </div>
    </div>

    <script>
    async function handleRegister(e) {
        e.preventDefault();
        const btn = document.getElementById('submitBtn');
        const alert = document.getElementById('alert');
        btn.disabled = true;
        btn.textContent = 'Mendaftar...';
        alert.className = 'alert';

        const pass = document.getElementById('password').value;
        const pass2 = document.getElementById('password2').value;

        if (pass !== pass2) {
            alert.className = 'alert error show';
            alert.textContent = 'Konfirmasi password tidak cocok';
            btn.disabled = false;
            btn.textContent = 'Daftar';
            return false;
        }

        try {
            const res = await fetch('/pkk/api/auth/register.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    name:     document.getElementById('name').value,
                    email:    document.getElementById('email').value,
                    phone:    document.getElementById('phone').value,
                    password: pass
                })
            });
            const data = await res.json();

            if (res.ok) {
                alert.className = 'alert success show';
                alert.textContent = 'Registrasi berhasil! Mengalihkan...';
                setTimeout(() => { window.location.href = '/pkk/'; }, 800);
            } else {
                alert.className = 'alert error show';
                alert.textContent = data.error || 'Registrasi gagal';
                btn.disabled = false;
                btn.textContent = 'Daftar';
            }
        } catch (err) {
            alert.className = 'alert error show';
            alert.textContent = 'Terjadi kesalahan jaringan';
            btn.disabled = false;
            btn.textContent = 'Daftar';
        }
    }
    </script>
</body>
</html>

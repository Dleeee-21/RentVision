<?php
session_start();
if (!empty($_SESSION['user_id'])) {
    header('Location: /pkk/');
    exit;
}
$redirect = $_GET['redirect'] ?? '/pkk/';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — RentVision</title>
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

            <h1 class="auth-title">Selamat Datang Kembali</h1>
            <p class="auth-subtitle">Masuk ke akun Anda untuk melanjutkan</p>

            <div class="alert" id="alert"></div>

            <form id="loginForm" onsubmit="return handleLogin(event)">
                <div class="form-group">
                    <label class="form-label" for="email">Email</label>
                    <input class="form-input" type="email" id="email" name="email" placeholder="Masukkan email" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input class="form-input" type="password" id="password" name="password" placeholder="Masukkan password" required minlength="6">
                </div>
                <button type="submit" class="btn-primary" id="submitBtn">Masuk</button>
            </form>

            <div class="auth-footer">
                Belum punya akun? <a href="/pkk/pages/register.php">Daftar sekarang</a>
            </div>
        </div>

        <div class="auth-footer" style="margin-top:18px;">
            <a href="/pkk/">&larr; Kembali ke beranda</a>
        </div>
    </div>

    <script>
    async function handleLogin(e) {
        e.preventDefault();
        const btn = document.getElementById('submitBtn');
        const alert = document.getElementById('alert');
        btn.disabled = true;
        btn.textContent = 'Masuk...';
        alert.className = 'alert';

        try {
            const res = await fetch('/pkk/api/auth/login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    email: document.getElementById('email').value,
                    password: document.getElementById('password').value
                })
            });
            const data = await res.json();

            if (res.ok) {
                alert.className = 'alert success show';
                alert.textContent = 'Login berhasil! Mengalihkan...';
                const redirect = <?= json_encode($redirect) ?>;
                setTimeout(() => { window.location.href = redirect; }, 800);
            } else {
                alert.className = 'alert error show';
                alert.textContent = data.error || 'Login gagal';
                btn.disabled = false;
                btn.textContent = 'Masuk';
            }
        } catch (err) {
            alert.className = 'alert error show';
            alert.textContent = 'Terjadi kesalahan jaringan';
            btn.disabled = false;
            btn.textContent = 'Masuk';
        }
    }
    </script>
</body>
</html>

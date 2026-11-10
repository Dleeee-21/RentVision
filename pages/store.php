<?php
$param = isset($_GET['slug']) ? 'slug=' . urlencode($_GET['slug']) : 'id=' . (int)($_GET['id'] ?? 0);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toko — RentVision</title>
    <link rel="icon" type="image/png" sizes="256x256" href="/pkk/rentvision-favicon.png">
    <link rel="stylesheet" href="store.css?v=2">
</head>
<body>
    <div class="backbar">
        <a href="jelajahi.html">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
            Kembali ke Jelajahi
        </a>
    </div>

    <div id="store"></div>

    <div class="footer" id="footer">
        <span>&copy; 2026 RentVision. All rights reserved.</span>
        <a href="jelajahi.html">Jelajahi Gear</a>
    </div>

    <script>
        const loc = window.location;
        const api = '/pkk/api/providers.php?<?= $param ?>';

        function esc(s) {
            return String(s == null ? '' : s).replace(/[&<>"']/g, c =>
                ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        }

        function rupiah(n) { return 'Rp ' + Number(n).toLocaleString('id-ID'); }

        fetch(api).then(r => r.json()).then(p => {
            if (p.error) {
                document.getElementById('store').innerHTML = '<div class="empty">Toko tidak ditemukan</div>';
                return;
            }
            const avatar = p.avatar
                ? '<img class="hero-avatar" src="' + esc(p.avatar) + '" alt="">'
                : '<div class="hero-avatar">' + esc(p.store_name || 'T').charAt(0).toUpperCase() + '</div>';
            const verified = Number(p.is_verified)
                ? '<span class="verified-badge"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m4.5 12.1 4 4L19.5 7"/></svg>Terverifikasi</span>' : '';
            const products = (p.products || []).map(pr =>
                '<div class="prod-card" onclick="location.href=\'detail.html?id=' + pr.id + '\'">' +
                '<div class="prod-media">' +
                (pr.thumbnail ? '<img src="' + esc(pr.thumbnail) + '" loading="lazy" alt="">'
                    : '<div class="noimg">Tanpa foto</div>') +
                '<span class="prod-cat">' + esc(pr.category_name || 'Produk') + '</span>' +
                '</div>' +
                '<div class="prod-body">' +
                '<div class="prod-name">' + esc(pr.name) + '</div>' +
                '<div class="prod-price">' + rupiah(pr.price_per_day) + ' <span>/ hari</span></div>' +
                '<div class="prod-meta">' +
                '<span class="rating">&#9733; ' + Number(pr.rating || 0).toFixed(1) +
                ' <small>(' + (pr.total_reviews || 0) + ')</small></span>' +
                (pr.location ? '<span class="loc"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>' + esc(pr.location) + '</span>' : '') +
                '</div></div></div>'
            ).join('') || '<div class="empty">Belum ada produk</div>';

            const meta = '<div class="hero-meta">' +
                (p.location ? '<span class="m"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>' + esc(p.location) + '</span>' : '') +
                '<span class="m rating">&#9733; ' + Number(p.rating || 0).toFixed(1) + ' <small>(' + (p.total_transactions || 0) + ' transaksi)</small></span>' +
                '<span class="m"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="13" r="4"/><path d="M4 8h3l1.5-2h7L17 8h3a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9a1 1 0 0 1 1-1Z"/></svg>' + (p.products || []).length + ' produk</span>' +
                '</div>';

            document.getElementById('store').innerHTML =
                '<div class="cover"></div>' +
                '<div class="hero"><div class="hero-inner">' + avatar +
                '<div class="hero-main">' +
                '<h1 class="hero-title">' + esc(p.store_name) + ' ' + verified + '</h1>' + meta +
                (p.description ? '<p class="hero-desc">' + esc(p.description) + '</p>' : '') +
                '</div></div></div>' +
                '<div class="products">' +
                '<h2 class="products-head">Produk <span class="count-chip">' + (p.products || []).length + ' Items</span></h2>' +
                '<div class="prod-grid">' + products + '</div></div>';
        }).catch(() => {
            document.getElementById('store').innerHTML = '<div class="empty">Gagal memuat toko</div>';
        });
    </script>
</body>
</html>
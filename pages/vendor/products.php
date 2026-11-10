<?php
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: /pkk/pages/login.php?redirect=/pkk/pages/vendor/products.php');
    exit;
}
$userName  = $_SESSION['user_name']  ?? 'User';
$userRole  = $_SESSION['user_role']  ?? 'Customer';
$active = 'products';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produk Vendor — RentVision</title>
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
            <div style="font-size:11.5px; color:var(--text-dimmer, #6c7086);">Manajemen Produk</div>
            <div style="margin-top:6px;"><span class="badge confirmed" style="font-size:10px;"><?= htmlspecialchars($userRole) ?></span></div>
        </div>
        <nav class="vendor-nav">
            <a class="vn-link" href="/pkk/pages/vendor/index.php">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg>
                Dashboard
            </a>
            <a class="vn-link <?= $active==='products'?'active':'' ?>" href="/pkk/pages/vendor/products.php">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 8l-9-5-9 5v8l9 5 9-5Z"/><path d="M3 8l9 5 9-5M12 13v8"/></svg>
                Produk
            </a>
            <a class="vn-link" id="storeLink" href="/pkk/pages/store.php">
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
                <h1>Produk Saya</h1>
                <span class="sub">Kelola produk & foto tokomu</span>
            </div>
            <button class="btn-primary" style="width:auto; padding:10px 16px; font-size:13px; border:none; cursor:pointer;" onclick="openForm()">+ Tambah Produk</button>
        </div>

        <div class="alert" id="alert"></div>

        <div id="formPanel" style="display:none; background:var(--bg-panel, #171a24); border:1px solid var(--border, #262a3a); border-radius:14px; padding:24px; margin-bottom:28px;">
            <h2 id="formTitle" style="font-size:17px; margin:0 0 18px;">Tambah Produk</h2>
            <input type="hidden" id="fId" value="">
            <div class="form-group">
                <label class="form-label" for="fName">Nama Produk</label>
                <input class="form-input" type="text" id="fName" required>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label class="form-label" for="fCategory">Kategori</label>
                    <select class="form-input" id="fCategory" required></select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="fCondition">Kondisi</label>
                    <select class="form-input" id="fCondition">
                        <option value="baru">Baru</option>
                        <option value="bekas">Bekas</option>
                        <option value="sebagus_baru">Seperti Baru</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label" for="fDesc">Deskripsi</label>
                <textarea class="form-input" id="fDesc" rows="3"></textarea>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label class="form-label" for="fPriceDay">Harga / hari</label>
                    <input class="form-input" type="number" id="fPriceDay" min="0" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="fPriceWeek">Harga / minggu</label>
                    <input class="form-input" type="number" id="fPriceWeek" min="0">
                </div>
                <div class="form-group">
                    <label class="form-label" for="fStock">Stok</label>
                    <input class="form-input" type="number" id="fStock" min="0" value="1">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label" for="fLocation">Lokasi</label>
                <input class="form-input" type="text" id="fLocation" placeholder="Kecamatan, Kota">
            </div>
            <div class="form-group">
                <label class="form-label" for="fAvailable">
                    <input type="checkbox" id="fAvailable" style="margin-right:6px;"> Tersedia untuk disewa
                </label>
            </div>
            <div class="form-group">
                <label class="form-label">Foto Produk (unggah dari komputer)</label>
                <input class="form-input" type="file" id="fImages" accept="image/*" multiple style="padding:8px;">
                <div id="imgPreview" style="display:flex; flex-wrap:wrap; gap:10px; margin-top:12px;"></div>
            </div>
            <div style="display:flex; gap:10px; margin-top:18px;">
                <button class="btn-primary" style="width:auto; padding:10px 20px; border:none; cursor:pointer;" onclick="saveProduct()">Simpan</button>
                <button class="btn-primary" style="width:auto; padding:10px 20px; border:none; cursor:pointer; background:var(--bg-panel-2, #1a1d29); color:var(--text, #e9eaf2);" onclick="closeForm()">Batal</button>
            </div>
        </div>

        <div class="dash-section">
            <div style="overflow-x:auto;">
                <table class="dash-table">
                    <thead><tr><th>Foto</th><th>Nama</th><th>Kategori</th><th>Harga/hari</th><th>Stok</th><th>Status</th><th>Aksi</th></tr></thead>
                    <tbody id="productTable"><tr><td colspan="7" style="text-align:center;color:var(--text-dimmer,#6c7086);padding:24px;">Memuat data...</td></tr></tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<script>
var editingImages = [];

function rupiah(n){ return 'Rp ' + Number(n).toLocaleString('id-ID'); }

function loadCategories() {
    fetch('/pkk/api/categories.php').then(r=>r.json()).then(d=>{
        const sel = document.getElementById('fCategory');
        sel.innerHTML = d.categories.map(c=>'<option value="'+c.id+'">'+c.name+'</option>').join('');
    });
}

async function loadProducts() {
    try {
        const res = await fetch('/pkk/api/products.php?mine=1&per_page=100');
        if (res.status === 401 || res.status === 403) { window.location.href='/pkk/pages/login.php'; return; }
        const d = await res.json();
        const tb = document.getElementById('productTable');
        if (!d.items.length) {
            tb.innerHTML = '<tr><td colspan="7" style="text-align:center;color:var(--text-dimmer,#6c7086);padding:24px;">Belum ada produk. Klik "Tambah Produk".</td></tr>';
            return;
        }
        tb.innerHTML = d.items.map(p => {
            const img = p.thumbnail ? '<img src="'+p.thumbnail+'" style="width:44px;height:44px;object-fit:cover;border-radius:8px;">' : '<div style="width:44px;height:44px;border-radius:8px;background:var(--bg-panel-2,#1a1d29);"></div>';
            const st = p.is_available == 1 ? '<span class="badge active">Tersedia</span>' : '<span class="badge cancelled">Nonaktif</span>';
            return '<tr>' +
                '<td>'+img+'</td>' +
                '<td style="font-weight:700;">'+p.name+'</td>' +
                '<td style="font-size:12.5px;color:var(--text-dim,#a3a7bd);">'+p.category_name+'</td>' +
                '<td style="font-weight:700;">'+rupiah(p.price_per_day)+'</td>' +
                '<td>'+(Number(p.stock)<=2?'<span class="badge pending" style="background:#5c3d00;color:#ffb84d;">': '')+p.stock+(Number(p.stock)<=2?'</span>':'')+'</td>' +
                '<td>'+st+'</td>' +
                '<td><button class="btn" style="margin:2px;padding:5px 10px;font-size:12px;border-radius:8px;border:none;cursor:pointer;background:rgba(124,92,252,.15);color:var(--text);" onclick="editProduct('+p.id+')">Edit</button>' +
                '<button class="btn" style="margin:2px;padding:5px 10px;font-size:12px;border-radius:8px;border:none;cursor:pointer;background:rgba(255,92,108,.15);color:#ff5c6c;" onclick="delProduct('+p.id+',\''+p.name.replace(/'/g,"\\'")+'\')">Hapus</button></td>' +
                '</tr>';
        }).join('');
    } catch(e) { document.getElementById('productTable').innerHTML='<tr><td colspan="7" style="text-align:center;padding:24px;">Gagal memuat</td></tr>'; }
}

function openForm() {
    document.getElementById('formPanel').style.display='block';
    document.getElementById('formTitle').textContent='Tambah Produk';
    document.getElementById('fId').value='';
    ['fName','fDesc','fPriceDay','fPriceWeek','fStock','fLocation'].forEach(id=>{ if(id==='fStock'){document.getElementById(id).value=1;} else {document.getElementById(id).value='';} });
    document.getElementById('fAvailable').checked=true;
    document.getElementById('fCondition').value='baru';
    document.getElementById('imgPreview').innerHTML='';
    document.getElementById('fImages').value='';
    editingImages=[];
    document.getElementById('fImages').closest('.form-group').scrollIntoView({behavior:'smooth'});
    window.scrollTo({top:0,behavior:'smooth'});
}

function closeForm() { document.getElementById('formPanel').style.display='none'; }

async function editProduct(id) {
    const res = await fetch('/pkk/api/products.php?id='+id);
    const p = await res.json();
    openForm();
    document.getElementById('formTitle').textContent='Edit: '+p.name;
    document.getElementById('fId').value=p.id;
    document.getElementById('fName').value=p.name || '';
    document.getElementById('fDesc').value=p.description || '';
    document.getElementById('fPriceDay').value=p.price_per_day;
    document.getElementById('fPriceWeek').value=p.price_per_week || '';
    document.getElementById('fStock').value=p.stock;
    document.getElementById('fLocation').value=p.location || '';
    document.getElementById('fAvailable').checked = p.is_available==1;
    document.getElementById('fCondition').value=p.condition || 'baru';
    document.getElementById('fCategory').value=p.category_id;
    editingImages = (p.images||[]).slice();
    renderImages();
}

function renderImages() {
    const box = document.getElementById('imgPreview');
    box.innerHTML = editingImages.map(img =>
        '<div style="position:relative;">' +
        '<img src="'+img.image_url+'" style="width:72px;height:72px;object-fit:cover;border-radius:10px;border:'+(img.is_primary?'2px solid #7c5cfc':'1px solid var(--border)')+';">' +
        (img.is_primary?'<div style="position:absolute;top:-6px;left:-6px;font-size:9px;background:#7c5cfc;color:#fff;padding:2px 5px;border-radius:6px;">UTAMA</div>':'') +
        '<button onclick="delImage('+img.id+')" style="position:absolute;top:-6px;right:-6px;width:18px;height:18px;border-radius:50%;border:none;background:#ff5c6c;color:#fff;cursor:pointer;font-size:11px;line-height:1;">&times;</button>' +
        '</div>'
    ).join('');
}

async function delImage(id) {
    if (!confirm('Hapus gambar ini?')) return;
    const res = await fetch('/pkk/api/vendor/images.php?id='+id+'&X='+Date.now(), {method:'DELETE'});
    if (res.ok) { editingImages = editingImages.filter(i=>i.id!=id); renderImages(); }
}

async function saveProduct() {
    const id = document.getElementById('fId').value;
    const name = document.getElementById('fName').value.trim();
    const catId = document.getElementById('fCategory').value;
    const price = document.getElementById('fPriceDay').value;
    if (!name || !catId || !price) { alert('Nama, kategori, dan harga wajib diisi'); return; }

    const payload = {
        name: name,
        category_id: Number(catId),
        description: document.getElementById('fDesc').value,
        price_per_day: Number(price),
        price_per_week: document.getElementById('fPriceWeek').value ? Number(document.getElementById('fPriceWeek').value) : null,
        stock: Number(document.getElementById('fStock').value)||1,
        condition: document.getElementById('fCondition').value,
        location: document.getElementById('fLocation').value,
        is_available: document.getElementById('fAvailable').checked ? 1 : 0
    };

    let productId = id;
    if (!id) {
        const res = await fetch('/pkk/api/products.php', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)});
        if (!res.ok) { const e=await res.json(); alert(e.error||'Gagal simpan produk'); return; }
        const d = await res.json();
        productId = d.product_id;
    } else {
        const res = await fetch('/pkk/api/products.php?id='+id, {method:'PUT', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)});
        if (!res.ok) { const e=await res.json(); alert(e.error||'Gagal update produk'); return; }
    }

    // upload new files
    const files = window._newFiles || [];
    if (files.length) {
        for (const file of files) {
            const fd = new FormData();
            fd.append('file', file);
            fd.append('product_id', productId);
            await fetch('/pkk/api/vendor/images.php', {method:'POST', body: fd});
        }
        window._newFiles = [];
    }
    closeForm(); loadProducts();
    const a=document.getElementById('alert'); a.className='alert success show'; a.textContent='Produk tersimpan';
    setTimeout(()=>a.className='alert', 2500);
}

async function delProduct(id, name) {
    if (!confirm('Hapus produk "'+name+'"?')) return;
    const res = await fetch('/pkk/api/products.php?id='+id, {method:'DELETE'});
    if (res.ok) loadProducts();
    else { const e=await res.json(); alert(e.error||'Gagal hapus'); }
}

document.getElementById('fImages').addEventListener('change', function(){
    const box = document.getElementById('imgPreview');
    for (const f of this.files) {
        const r = new FileReader();
        r.onload = e => {
            const d = document.createElement('div');
            d.style.position='relative';
            d.innerHTML='<img src="'+e.target.result+'" style="width:72px;height:72px;object-fit:cover;border-radius:10px;"><div style="position:absolute;bottom:-6px;left:0;right:0;font-size:9px;background:rgba(0,0,0,.6);color:#fff;padding:2px;text-align:center;">BARU</div>';
            box.appendChild(d);
        };
        r.readAsDataURL(f);
    }
    window._newFiles = Array.from(this.files);
});
loadCategories();
loadProducts();
</script>
</body>
</html>

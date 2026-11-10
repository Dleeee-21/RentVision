<?php
session_start();
$isVendor = ($_SESSION['user_role'] ?? '') === 'Vendor';
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>RentVision — Sewa Peralatan Fotografi</title>
  <link rel="icon" type="image/png" sizes="256x256" href="/pkk/rentvision-favicon.png">
</head>
<link rel="stylesheet" href="index.css">


<body>
  <div class="app">


    <aside class="sidebar">
      <div class="brand">
        <div class="brand-mark">
          <img src="Rentvision logo.png" alt="RentVision" width="12px" height="12px">
        </div>
        <div class="brand-name">Rent<span>Vision</span></div>
      </div>

      <nav class="nav-group">
        <a class="nav-item active" href="#">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M3 9.5 12 3l9 6.5V21a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1Z" />
          </svg>
          Dashboard
        </a>
        <a class="nav-item" href="pages/jelajahi.html">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="13" r="4" />
            <path d="M4 8h3l1.5-2h7L17 8h3a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9a1 1 0 0 1 1-1Z" />
          </svg>
          Jelajahi Gear
        </a>
        <a class="nav-item" href="pages/booking.html">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="4" width="18" height="18" rx="2" />
            <path d="M16 2v4M8 2v4M3 10h18" />
          </svg>
          Booking Saya
        </a>
        <a class="nav-item" href="pages/wishlist.html">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 21s-7-4.5-9.5-9A5.5 5.5 0 0 1 12 6a5.5 5.5 0 0 1 9.5 6c-2.5 4.5-9.5 9-9.5 9Z" />
          </svg>
          Wishlist
        </a>
        <a class="nav-item" href="pages/ulasan.html">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="m12 2 3.1 6.3 7 1-5 4.9 1.2 6.9-6.3-3.3-6.3 3.3 1.2-6.9-5-4.9 7-1Z" />
          </svg>
          Ulasan
        </a>
        <?php if ($isVendor): ?>
        <a class="nav-item" href="pages/vendor/index.php">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M3 9l1.5-5h15L21 9M3 9a3 3 0 0 0 6 0 3 3 0 0 0 6 0 3 3 0 0 0 6 0M4 9v11h16V9" />
          </svg>
          Toko saya
        </a>
        <?php endif; ?>
      </nav>

      <div class="nav-divider"></div>

      <nav class="nav-group">
        <a class="nav-item" href="pages/partner.php">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="7" width="18" height="13" rx="2" />
            <path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M3 12h18" />
          </svg>
          Menjadi Partner
        </a>
        <a class="nav-item" href="#">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="3" />
            <path
              d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1-1.6 1.7 1.7 0 0 0-1.9.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.9 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.6-1 1.7 1.7 0 0 0-.3-1.9l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.9.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.9-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.9V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1Z" />
          </svg>
          Pengaturan
        </a>
      </nav>

      <div class="toggle-row">
        <div class="left">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z" />
          </svg>
          <span id="themeLabel">Mode Gelap</span>
        </div>
        <div class="switch" id="themeToggle" onclick="toggleTheme()"></div>
      </div>

      <div class="promo-card">
        <h4>Butuh Gear Profesional?</h4>
        <p>Sewa peralatan fotografi terbaik untuk hasil terbaik.</p>
        <a class="btn-solid" href="#">Jelajahi Sekarang</a>
      </div>

      <div class="footnote"> 2026 RentVision<br>All rights reserved.</div>
    </aside>

    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <main class="main">
      <header class="topbar">
        <button class="hamburger" id="hamburger" aria-label="Menu">
          <span></span><span></span><span></span>
        </button>
        <div class="search-nav-wrap">
          <div class="search-nav-box">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" style="color:#6c6c82;flex-shrink:0;">
              <circle cx="11" cy="11" r="7" />
              <path d="m21 21-4.3-4.3" />
            </svg>
            <input type="text" id="navSearchInput" placeholder="Cari kamera, lensa, brand..." autocomplete="off" />
            <button class="search-clear" id="navSearchClear">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
          </div>
          <div class="search-nav-dropdown" id="navSearchDropdown"></div>
        </div>
        <nav class="topnav">
          <a class="active" href="#">Beranda</a>
          <a href="pages/jelajahi.html">Jelajahi</a>
          <a href="#" onclick="openHowItWorks(); return false;">Cara Kerja</a>
          <a href="pages/partner.php">Menjadi Partner</a>
        </nav>
        <div class="top-actions">
          <div class="user-chip" id="userChip" onclick="toggleProfileMenu()">
            <img src="John Carlton.jpg" alt="Fadly Bisma" id="userAvatar">
            <span id="userNameTop">Fadly Bisma</span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="m6 9 6 6 6-6" />
            </svg>
          </div>
          <div class="profile-dropdown" id="profileDropdown">
            <div class="profile-header">
              <img src="C:\laragon\www\pkk\John Carlton.jpg" alt="Fadly Bisma" class="profile-avatar">
              <div class="profile-info">
                <div class="profile-name" id="profileName">Fadly Bisma</div>
                <div class="profile-email" id="profileEmail">fadly@rentvision.id</div>
                <span class="badge active" id="profileRole">Developer</span>
              </div>
            </div>
            <div class="profile-divider"></div>
            <a class="profile-menu-item" href="pages/profile.php">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M20 21a8 8 0 1 0-16 0"/></svg>
              Profil Saya
            </a>
            <div class="profile-divider"></div>
            <a class="profile-menu-item" href="/pkk/pages/logout.php" id="logoutBtn">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
              Keluar
            </a>
          </div>
        </div>
      </header>

      <div class="content">

        <section class="hero">
          <img src="https://images.unsplash.com/photo-1502920917128-1aa500764cbd?w=1400&q=80" alt="Kamera profesional">
          <div class="hero-inner">
            <span class="eyebrow">RENT. SHOOT. CREATE.</span>
            <h1>Sewa Peralatan Fotografi <span class="grad">Profesional dengan Mudah</span></h1>
            <p>RentVision adalah platform penyewaan kamera, lensa, dan peralatan fotografi terpercaya untuk setiap
              kebutuhanmu.</p>
            <div class="hero-tags">
              <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M12 2v20M2 12h20" />
                  <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
                </svg>Harga Terbaik</span>
              <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="m22 11-3-1-1-3-1 3-3 1 3 1 1 3 1-3Z" />
                  <circle cx="8" cy="14" r="6" />
                </svg>Peralatan Terawat</span>
              <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M12 2 4 5v6c0 5.2 3.4 9.7 8 11 4.6-1.3 8-5.8 8-11V5Z" />
                </svg>Aman &amp; Terpercaya</span>
            </div>
          </div>
        </section>

        <section>
          <div class="section-head">
          </div>
          <div class="cat-grid" id="cat-grid"></div>
        </section>

        <section>
          <div class="section-head">
            <h2>Rekomendasi Untukmu</h2>
            <a href="#">Lihat Semua</a>
          </div>
          <div class="prod-grid" id="prod-grid"></div>
        </section>

      </div>
    </main>


    <aside class="right-rail">
      <div class="panel" id="activeBookingPanel">
        <div class="panel-head">
          <h3>Booking Aktif</h3>
          <a href="pages/booking.html">Lihat Semua</a>
        </div>

        <div id="activeBookingList"></div>

        <button class="btn-full" onclick="location.href='pages/booking.html'">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="4" width="18" height="18" rx="2" />
            <path d="M16 2v4M8 2v4M3 10h18" />
          </svg>
          Lihat Detail Booking
        </button>
      </div>

      <div class="panel">
        <div class="panel-head">
          <h3>Kenapa Memilih RentVision?</h3>
        </div>
        <div class="why-list" id="why-list"></div>
      </div>

      <div class="panel partner-panel" onclick="location.href='pages/partner.php'">
        <h3>Jadi Partner RentVision</h3>
        <p>Dapatkan penghasilan tambahan dengan menyewakan peralatanmu.</p>
        <a href="pages/partner.php" class="btn-full">Daftar Sekarang</a>
        <img src="https://images.unsplash.com/photo-1606986628253-05620e9bb18e?w=300&q=80" alt="">
      </div>
    </aside>

  </div>

  <nav class="bottom-nav">
    <a class="bottom-nav-item active" href="#">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M3 9.5 12 3l9 6.5V21a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1Z" />
      </svg>
      <span>Home</span>
    </a>
    <a class="bottom-nav-item" href="pages/jelajahi.html">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="11" cy="11" r="7" />
        <path d="m21 21-4.3-4.3" />
      </svg>
      <span>Jelajahi</span>
    </a>
    <a class="bottom-nav-item" href="pages/booking.html">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <rect x="3" y="4" width="18" height="18" rx="2" />
        <path d="M16 2v4M8 2v4M3 10h18" />
      </svg>
      <span>Booking</span>
    </a>
    <a class="bottom-nav-item" href="pages/wishlist.html">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M12 21s-7-4.5-9.5-9A5.5 5.5 0 0 1 12 6a5.5 5.5 0 0 1 9.5 6c-2.5 4.5-9.5 9-9.5 9Z" />
      </svg>
      <span>Wishlist</span>
    </a>
    <a class="bottom-nav-item" href="#">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="8" r="4" />
        <path d="M20 21a8 8 0 1 0-16 0" />
      </svg>
      <span>Profil</span>
    </a>
  </nav>

  <script src="index.js?v=2"></script>

  <script src="pages/nav-search.js"></script>

  <div class="hiw-overlay" id="howItWorksModal" onclick="if(event.target===this)closeHowItWorks()">
    <div class="hiw-panel">
      <button class="hiw-close" onclick="closeHowItWorks()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
      <div class="hiw-head">
        <div class="hiw-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="9"/>
            <path d="M12 3v6M12 15v6M3 12h6M15 12h6"/>
          </svg>
        </div>
        <h2>Cara Kerja RentVision</h2>
        <p>Sewa peralatan fotografi dalam 4 langkah mudah</p>
      </div>

      <div class="hiw-steps">
        <div class="hiw-step">
          <div class="hiw-num">1</div>
          <div class="hiw-body">
            <h4>Pilih Alat yang Diinginkan</h4>
            <p>Jelajahi katalog kami dan temukan peralatan fotografi yang kamu butuhkan.</p>
          </div>
        </div>
        <div class="hiw-connector"></div>

        <div class="hiw-step">
          <div class="hiw-num">2</div>
          <div class="hiw-body">
            <h4>Klik Booking</h4>
            <p>Pilih tanggal sewa dan klik tombol booking untuk melakukan pemesanan.</p>
          </div>
        </div>
        <div class="hiw-connector"></div>

        <div class="hiw-step">
          <div class="hiw-num">3</div>
          <div class="hiw-body">
            <h4>Lakukan Pembayaran</h4>
            <p>Selesaikan pembayaran melalui metode yang tersedia untuk mengonfirmasi pesanan.</p>
          </div>
        </div>
        <div class="hiw-connector"></div>

        <div class="hiw-step">
          <div class="hiw-num done">
            <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
          </div>
          <div class="hiw-body">
            <h4>Alat Diantar ke Lokasi Anda</h4>
            <p>Peralatan akan dikirimkan langsung ke lokasi kamu. Siap digunakan!</p>
          </div>
        </div>
      </div>

      <button class="hiw-btn" onclick="closeHowItWorks()">Mengerti!</button>
    </div>
  </div>
</body>

</html>
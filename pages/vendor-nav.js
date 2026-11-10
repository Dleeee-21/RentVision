(function () {
  function insertGuestAvatar(img) {
    if (img.previousElementSibling && img.previousElementSibling.classList.contains('guest-avatar')) return;
    img.style.display = 'none';
    var div = document.createElement('div');
    div.className = 'guest-avatar';
    div.style.cssText = 'width:34px;height:34px;border-radius:50%;background:var(--accent-grad);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:800;color:#fff;flex-shrink:0;';
    if (img.classList.contains('profile-avatar')) {
      div.style.width = '48px';
      div.style.height = '48px';
      div.style.fontSize = '17px';
    }
    div.textContent = 'G';
    img.parentNode.insertBefore(div, img);
  }

  function setLinkLabel(a, label) {
    var textNode = null;
    for (var i = 0; i < a.childNodes.length; i++) {
      if (a.childNodes[i].nodeType === 3 && a.childNodes[i].textContent.trim()) textNode = a.childNodes[i];
    }
    if (textNode) textNode.textContent = ' ' + label;
    else a.appendChild(document.createTextNode(label));
  }

  function setGuestChip() {
    var chip = document.querySelector('.user-chip');
    if (!chip) return;

    var name = document.getElementById('userNameTop') || chip.querySelector('span');
    if (name) name.textContent = 'Guest';

    var profileName = document.getElementById('profileName');
    if (profileName) profileName.textContent = 'Guest';
    var profileEmail = document.getElementById('profileEmail');
    if (profileEmail) profileEmail.textContent = 'Belum login';
    var profileRole = document.getElementById('profileRole');
    if (profileRole) profileRole.style.display = 'none';

    Array.prototype.forEach.call(document.querySelectorAll('.user-chip img, .profile-avatar'), insertGuestAvatar);
    Array.prototype.forEach.call(document.querySelectorAll('a[href*="logout.php"]'), function (a) {
      a.href = 'login.php';
      setLinkLabel(a, 'Masuk');
    });
  }

  fetch('/pkk/api/auth/check.php')
    .then(function (r) { return r.json(); })
    .then(function (data) {
      if (!data.logged_in || !data.user) {
        setGuestChip();
        return;
      }
      if (data.user.role !== 'Vendor') return;

      var nav = document.querySelector('.sidebar .nav-group:first-of-type');
      if (!nav) return;

      var link = document.createElement('a');
      link.className = 'nav-item';
      link.href = 'vendor/index.php';
      link.innerHTML =
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
        '<path d="M3 9l1.5-5h15L21 9M3 9a3 3 0 0 0 6 0 3 3 0 0 0 6 0 3 3 0 0 0 6 0M4 9v11h16V9"/></svg>' +
        'Toko saya';

      var ulasan = Array.prototype.slice.call(nav.querySelectorAll('.nav-item')).find(function (a) {
        return a.textContent.trim() === 'Ulasan';
      });
      nav.insertBefore(link, ulasan ? ulasan.nextSibling : null);
    });
})();

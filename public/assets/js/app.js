/* Script global aplikasi */

function initApp() {
  var sidebarNav = document.querySelector('#sidebar nav');

  // ---- Ensure active menu item is visible on every page load ----
  if (sidebarNav) {
    var activeLink = sidebarNav.querySelector('a.bg-emerald-700.text-white, a.bg-emerald-600.text-white, a.bg-emerald-600');
    if (activeLink) {
      // Force scroll to active item (centered in viewport)
      activeLink.scrollIntoView({ behavior: 'auto', block: 'center' });
    }
  }

  // ---- Sidebar mobile ----
  var toggleBtn = document.getElementById('sidebarToggle');
  var sidebar = document.getElementById('sidebar');
  var overlay = document.getElementById('sidebarOverlay');
  if (toggleBtn && sidebar && overlay) {
    toggleBtn.addEventListener('click', function () {
      sidebar.classList.toggle('-translate-x-full');
      overlay.classList.toggle('hidden');
    });
    overlay.addEventListener('click', function () {
      sidebar.classList.add('-translate-x-full');
      overlay.classList.add('hidden');
    });
  }

  // ---- Real-time clock in navbar ----
  var timeEl = document.getElementById('clock-time');
  var dateEl = document.getElementById('clock-date');
  if (timeEl && dateEl) {
    var days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    var months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    function updateClock() {
      var now = new Date();
      var h = String(now.getHours()).padStart(2, '0');
      var m = String(now.getMinutes()).padStart(2, '0');
      var s = String(now.getSeconds()).padStart(2, '0');
      timeEl.textContent = h + ':' + m + ':' + s;
      dateEl.textContent = days[now.getDay()] + ', ' + now.getDate() + ' ' + months[now.getMonth()] + ' ' + now.getFullYear();
    }
    updateClock();
    setInterval(updateClock, 1000);
  }

  // ---- Dropdown menu user (logout) ----
  var userMenu = document.getElementById('userMenu');
  var userMenuDropdown = document.getElementById('userMenuDropdown');
  if (userMenu && userMenuDropdown) {
    var hideTimer = null;
    userMenu.addEventListener('mouseenter', function () {
      clearTimeout(hideTimer);
      userMenuDropdown.classList.remove('hidden');
    });
    userMenu.addEventListener('mouseleave', function () {
      hideTimer = setTimeout(function () {
        userMenuDropdown.classList.add('hidden');
      }, 200);
    });
    userMenuDropdown.addEventListener('mouseenter', function () {
      clearTimeout(hideTimer);
    });
    userMenuDropdown.addEventListener('mouseleave', function () {
      userMenuDropdown.classList.add('hidden');
    });
  }

  // ---- Konfirmasi logout ----
  document.querySelectorAll('#linkLogout, a[href$="/logout"]').forEach(function (link) {
    link.addEventListener('click', function (e) {
      e.preventDefault();
      Swal.fire({
        icon: 'question',
        title: 'Keluar?',
        text: 'Yakin ingin keluar dari aplikasi?',
        showCancelButton: true,
        confirmButtonText: 'Ya, Keluar',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#dc2626'
      }).then(function (r) {
        if (r.isConfirmed) {
          window.location.href = link.href;
        }
      });
    });
  });
}

// Initialize app when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initApp);
} else {
  initApp();
}

/* ================= Filter auto-submit ================= */
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('form.js-filter-form').forEach(function (form) {
    function doSubmit() {
      form.submit();
    }

    function readyToSubmit() {
      var d1 = form.querySelector('[name="dari"]');
      var d2 = form.querySelector('[name="sampai"]');
      // Jika form memakai pasangan tanggal, tunggu keduanya terisi
      if (d1 && d2 && d1.type === 'date' && d2.type === 'date') {
        return d1.value !== '' && d2.value !== '';
      }
      return true;
    }

    // Rentang (kas): pilih custom -> tampilkan tanggal; lain -> langsung submit
    var rng = form.querySelector('select[name="range"]');
    var rngDates = form.querySelectorAll('input[data-range-date]');
    if (rng && rngDates.length) {
      var toggleDates = function () {
        var show = rng.value === 'custom';
        rngDates.forEach(function (d) {
          d.closest('div').style.display = show ? '' : 'none';
        });
      };
      toggleDates();
      rng.addEventListener('change', function () {
        toggleDates();
        if (rng.value !== 'custom') form.submit();
      });
      rngDates.forEach(function (d) {
        d.addEventListener('change', function () {
          if (readyToSubmit()) form.submit();
        });
      });
    }

    // Select biasa & tanggal: submit saat berubah
    form.querySelectorAll('select, input[type="date"]').forEach(function (el) {
      if (el === rng || el.hasAttribute('data-range-date')) return;
      el.addEventListener('change', function () {
        if (readyToSubmit()) form.submit();
      });
    });

    // Kolom teks: debounce
    form.querySelectorAll('input[type="text"], input[type="search"]').forEach(function (el) {
      var timer;
      el.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(function () {
          if (readyToSubmit()) form.submit();
        }, 600);
      });
    });
  });
});

/* ================= SweetAlert2 - helper global ================= */
function showSuccess(message) {
  return Swal.fire({
    icon: 'success', title: 'Berhasil', text: message,
    timer: 2500, timerProgressBar: true, showConfirmButton: false,
    position: 'top-end', toast: true
  });
}
function showError(message) {
  return Swal.fire({ icon: 'error', title: 'Gagal', text: message, confirmButtonText: 'OK' });
}
function showWarning(message) {
  return Swal.fire({ icon: 'warning', title: 'Peringatan', text: message, confirmButtonText: 'OK' });
}
function showInfo(message) {
  return Swal.fire({ icon: 'info', title: 'Informasi', text: message, confirmButtonText: 'OK' });
}
function confirmAction(title, text, confirmText) {
  return Swal.fire({
    icon: 'warning',
    title: title,
    text: text,
    showCancelButton: true,
    confirmButtonText: confirmText || 'Ya',
    cancelButtonText: 'Batal',
    confirmButtonColor: '#059669',
    cancelButtonColor: '#94a3b8'
  });
}

/** Konfirmasi sebelum submit form (form terdekat dari tombol). */
function appConfirmSubmit(ev, message, title) {
  ev.preventDefault();
  var form = ev.currentTarget.closest('form');
  if (!form) return false;
  var swalMessage = message || 'Yakin melanjutkan tindakan ini?';
  confirmAction(title || 'Konfirmasi', swalMessage, 'Ya, Lanjutkan').then(function (r) {
    if (r.isConfirmed) {
      form.submit();
    }
  });
  return false;
}

/** Konfirmasi pembatalan transaksi dengan alasan wajib (SweetAlert2). */
function confirmCancelForm(ev, title) {
  ev.preventDefault();
  var form = ev.currentTarget.closest('form');
  if (!form) return false;
  Swal.fire({
    icon: 'warning',
    title: title || 'Batalkan transaksi?',
    text: 'Transaksi yang dibatalkan tetap tersimpan dalam riwayat. Efek kas/stok/piutang/hutang dibalik otomatis.',
    input: 'textarea',
    inputLabel: 'Alasan pembatalan (wajib)',
    inputPlaceholder: 'Tulis alasan...',
    inputValidator: function (v) {
      if (!v || !v.trim()) return 'Alasan wajib diisi!';
    },
    showCancelButton: true,
    confirmButtonText: 'Ya, Batalkan',
    cancelButtonText: 'Batal',
    confirmButtonColor: '#dc2626'
  }).then(function (r) {
    if (r.isConfirmed) {
      var inp = document.createElement('input');
      inp.type = 'hidden';
      inp.name = 'alasan';
      inp.value = r.value;
      form.appendChild(inp);
      form.submit();
    }
  });
  return false;
}

/** Format rupiah pada input (selektornya dipakai sebagai fallback manual). */
function formatInputRupiah(el) {
  if (!el) return;
  el.addEventListener('input', function () {
    var v = el.value.replace(/[^\d]/g, '');
    el.dataset.raw = v;
    if (v === '') { el.value = ''; return; }
    el.value = Number(v).toLocaleString('id-ID');
  });
}

function inputRupiahValue(el) {
  return parseInt(String(el.value).replace(/[^\d]/g, ''), 10) || 0;
}

/* ============ Digit grouping otomatis untuk semua input nominal ============ */
(function () {
  var MONEY_SELECTOR = [
    'input[name="nominal"]',
    'input[name="harga"]',
    'input[name="harga[]"]',
    'input[name="harga_beli"]',
    'input[name="harga_jual"]',
    'input[name="diskon"]',
    'input[name="diskon[]"]',
    'input[name="diskon_global"]',
    'input[name="saldo"]',
    'input[name="saldo_minimum_cash"]'
  ].join(', ');

  function moneyDigits(v) {
    return String(v).replace(/[^\d]/g, '');
  }

  function formatMoney(el) {
    var digits = moneyDigits(el.value);
    el.dataset.raw = digits;
    if (digits === '') { el.value = ''; return; }
    el.value = Number(digits).toLocaleString('id-ID');
  }

  document.addEventListener('input', function (e) {
    var t = e.target;
    if (t.matches && t.matches(MONEY_SELECTOR)) {
      formatMoney(t);
    }
  });

  // Sebelum submit, kembalikan nilai bersih (tanpa titik) supaya server menerima angka utuh.
  document.addEventListener('submit', function (e) {
    e.target.querySelectorAll(MONEY_SELECTOR).forEach(function (el) {
      if (el.dataset.raw !== undefined) {
        el.value = el.dataset.raw === '' ? '' : Number(el.dataset.raw).toFixed(0);
      }
    });
  }, true);

  // Input yang nilainya sudah terisi tetap diformat saat halaman siap.
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll(MONEY_SELECTOR).forEach(function (el) {
      if (el.value !== '' && /^\d+$/.test(el.value) && el.value !== '0') {
        formatMoney(el);
      }
    });
  });
})();
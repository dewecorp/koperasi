/* Script global aplikasi */

document.addEventListener('DOMContentLoaded', function () {
  // ---- Sidebar scroll position persistence ----
  var sidebar = document.getElementById('sidebar');
  if (sidebar) {
    // Restore scroll position
    var savedScroll = sessionStorage.getItem('sidebarScrollTop');
    if (savedScroll) {
      sidebar.scrollTop = parseInt(savedScroll, 10);
    }
    // Save scroll position before unload
    window.addEventListener('beforeunload', function () {
      sessionStorage.setItem('sidebarScrollTop', sidebar.scrollTop.toString());
    });
  }

  // ---- Sidebar mobile ----
  var overlay = document.getElementById('sidebarOverlay');
  var toggle = document.getElementById('sidebarToggle');
  if (toggle && sidebar) {
    toggle.addEventListener('click', function () {
      sidebar.classList.toggle('-translate-x-full');
      overlay.classList.toggle('hidden');
    });
  }
  if (overlay) {
    overlay.addEventListener('click', function () {
      sidebar.classList.add('-translate-x-full');
      overlay.classList.add('hidden');
    });
  }
});

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

/** Format rupiah pada input */
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
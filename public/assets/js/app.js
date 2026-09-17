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

  // ---- Update sistem ----
  var btnUpdate = document.getElementById('btnUpdateSistem');
  if (btnUpdate) {
    btnUpdate.addEventListener('click', function () {
      Swal.fire({
        icon: 'question',
        title: 'Update Sistem?',
        text: 'Aplikasi akan diperbarui ke versi terbaru. Proses berlangsung beberapa saat dan halaman akan tetap aman. Lanjutkan?',
        showCancelButton: true,
        confirmButtonText: 'Ya, Update',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#059669'
      }).then(function (r) {
        if (!r.isConfirmed) return;

        Swal.fire({
          title: 'Memperbarui...',
          text: 'Mohon tunggu, jangan tutup halaman.',
          allowOutsideClick: false,
          allowEscapeKey: false,
          showConfirmButton: false,
          didOpen: function () {
            Swal.showLoading();
            var form = new FormData();
            form.append('csrf_token', btnUpdate.dataset.csrf);
            fetch(btnUpdate.dataset.url, { method: 'POST', body: form })
              .then(function (resp) { return resp.json(); })
              .then(function (res) {
                var icon = res.ok ? 'success' : 'error';
                var title = res.ok ? 'Update Berhasil' : 'Update Gagal';
                var bg = res.ok ? '#059669' : '#dc2626';
                Swal.fire({
                  icon: icon,
                  title: title,
                  text: res.message,
                  background: bg,
                  color: '#ffffff',
                  iconColor: '#ffffff',
                  toast: true,
                  position: 'top-end',
                  timer: res.ok ? 3500 : 5000,
                  timerProgressBar: false,
                  showConfirmButton: false
                });
              })
              .catch(function () {
                Swal.fire({
                  icon: 'error', title: 'Update Gagal',
                  text: 'Terjadi kesalahan koneksi.',
                  background: '#dc2626', color: '#ffffff', iconColor: '#ffffff',
                  toast: true, position: 'top-end', timer: 5000, showConfirmButton: false
                });
              });
          }
        });
      });
    });
  }
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
    timer: 2500, showConfirmButton: false,
    toast: true, position: 'top-end'
  });
}
function showError(message) {
  return Swal.fire({
    icon: 'error', title: 'Gagal', text: message,
    background: '#dc2626', color: '#ffffff', iconColor: '#ffffff',
    toast: true, position: 'top-end', timer: 4500, showConfirmButton: false
  });
}
function showWarning(message) {
  return Swal.fire({
    icon: 'warning', title: 'Peringatan', text: message,
    background: '#d97706', color: '#ffffff', iconColor: '#ffffff',
    toast: true, position: 'top-end', timer: 4000, showConfirmButton: false
  });
}
function showInfo(message) {
  return Swal.fire({
    icon: 'info', title: 'Informasi', text: message,
    background: '#0284c7', color: '#ffffff', iconColor: '#ffffff',
    toast: true, position: 'top-end', timer: 4000, showConfirmButton: false
  });
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
  // Termasuk cart penjualan/pembelian (name="qty[]", "harga[]", "diskon[]")
  document.addEventListener('submit', function (e) {
    e.target.querySelectorAll(MONEY_SELECTOR).forEach(function (el) {
      if (el.dataset.raw !== undefined) {
        el.value = el.dataset.raw === '' ? '' : Number(el.dataset.raw).toFixed(0);
      } else {
        var d = String(el.value).replace(/[^\d]/g, '');
        if (el.value !== '' && d !== el.value) el.value = d;
      }
    });
    e.target.querySelectorAll('input[name="qty[]"], input[name="harga[]"], input[name="diskon[]"]').forEach(function (el) {
      el.value = String(el.value).replace(/[^\d]/g, '');
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

/* ============ Custom select rounded (agar dropdown ikut rounded seperti .input) ============ */
(function () {
  function closeAll() {
    document.querySelectorAll('.custom-select-list').forEach(function (l) { l.classList.add('hidden'); });
    document.querySelectorAll('.custom-select-button').forEach(function (b) { b.setAttribute('aria-expanded', 'false'); b.classList.remove('is-open'); });
  }

  function syncLabel(sel, label, list) {
    var opt = sel.options[sel.selectedIndex];
    label.textContent = opt ? opt.textContent.trim() : '';
    if (list) {
      list.querySelectorAll('.custom-select-option').forEach(function (o) {
        o.classList.toggle('is-active', o.dataset.value === sel.value);
      });
    }
  }

  function enhanceSelect(sel) {
    if (!sel || sel.dataset.csEnhanced === '1' || sel.multiple) return;
    if (sel.dataset.noCs === '1' || sel.hasAttribute('data-no-cs')) return;
    if (sel.closest('.custom-select-wrap')) return;
    if (sel.closest('.swal2-container') || sel.classList.contains('swal2-select')) return;
    if (sel.classList.contains('select2-hidden-accessible') || sel.dataset.select2Id || sel.classList.contains('select2-offscreen')) return;
    sel.dataset.csEnhanced = '1';

    var wrap = document.createElement('div');
    wrap.className = 'custom-select-wrap';
    var wClasses = (sel.className.match(/(?:^|\s)(w-\S+|sm:w-\S+|md:w-\S+|lg:w-\S+|xl:w-\S+|flex-\S+|flex|basis-\S+|min-w\S+|max-w\S+)/g) || []).join(' ');
    if (wClasses) wrap.className += ' ' + wClasses.trim();

    sel.parentNode.insertBefore(wrap, sel);
    wrap.appendChild(sel);

    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'custom-select-button';
    btn.setAttribute('aria-haspopup', 'listbox');
    btn.setAttribute('aria-expanded', 'false');
    btn.disabled = sel.disabled;
    if (sel.id) btn.id = sel.id + '-cs-btn';

    var label = document.createElement('span');
    label.style.cssText = 'flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;text-align:left;';
    btn.appendChild(label);

    var arrow = document.createElement('span');
    arrow.className = 'custom-select-arrow';
    arrow.setAttribute('aria-hidden', 'true');
    btn.appendChild(arrow);

    var list = document.createElement('div');
    list.className = 'custom-select-list hidden';
    list.setAttribute('role', 'listbox');

    Array.prototype.forEach.call(sel.options, function (opt) {
      var o = document.createElement('button');
      o.type = 'button';
      o.className = 'custom-select-option';
      o.setAttribute('role', 'option');
      o.dataset.value = opt.value;
      o.textContent = opt.textContent.trim();
      if (opt.disabled) o.disabled = true;
      if (opt.value === sel.value) o.classList.add('is-active');
      o.addEventListener('click', function () {
        if (o.disabled) return;
        sel.value = o.dataset.value;
        syncLabel(sel, label, list);
        closeAll();
        sel.dispatchEvent(new Event('change', { bubbles: true }));
        sel.dispatchEvent(new Event('input', { bubbles: true }));
      });
      list.appendChild(o);
    });

    syncLabel(sel, label, list);

    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      var isHidden = list.classList.contains('hidden');
      closeAll();
      if (isHidden) {
        list.classList.remove('hidden');
        btn.setAttribute('aria-expanded', 'true');
        btn.classList.add('is-open');

        // Deteksi posisi layar: jika ruang bawah sempit, buka ke atas (drop-up)
        var btnRect = btn.getBoundingClientRect();
        var spaceBelow = window.innerHeight - btnRect.bottom;
        var spaceAbove = btnRect.top;
        var listHeight = list.offsetHeight || 220;
        var openUp = spaceBelow < listHeight && spaceAbove > spaceBelow;
        list.classList.toggle('drop-up', openUp);

        // Auto-scroll ke opsi yang sedang aktif
        var activeOpt = list.querySelector('.custom-select-option.is-active');
        if (activeOpt) {
          activeOpt.scrollIntoView({ block: 'nearest' });
        }
      }
    });

    sel.addEventListener('change', function () { syncLabel(sel, label, list); });

    var obs = new MutationObserver(function () {
      btn.disabled = sel.disabled;
      syncLabel(sel, label, list);
    });
    obs.observe(sel, { attributes: true, attributeFilter: ['disabled'] });

    wrap.appendChild(btn);
    wrap.appendChild(list);
  }

  function enhanceAll(root) {
    (root || document).querySelectorAll('select').forEach(enhanceSelect);
  }

  document.addEventListener('DOMContentLoaded', function () {
    enhanceAll(document);
    document.addEventListener('click', closeAll);
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeAll();
    });
    var mo = new MutationObserver(function (muts) {
      muts.forEach(function (m) {
        m.addedNodes.forEach(function (n) {
          if (n.nodeType !== 1) return;
          if (n.closest && n.closest('.swal2-container')) return;
          if (n.classList && n.classList.contains('swal2-container')) return;
          if (n.classList && (n.classList.contains('select2-container') || n.classList.contains('select2'))) return;
          if (n.tagName === 'SELECT') enhanceSelect(n);
          else if (n.querySelectorAll) n.querySelectorAll('select').forEach(enhanceSelect);
        });
      });
    });
    mo.observe(document.body, { childList: true, subtree: true });
  });
})();
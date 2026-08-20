/* Grafik dashboard */
(function () {
  if (typeof Chart === 'undefined') return;

  Chart.defaults.font.family = "'Segoe UI', system-ui, sans-serif";
  Chart.defaults.color = '#475569';

  var dataBulanan = {
    labels: window.chartBulanLabels,
    datasets: [
      { label: 'Pemasukan', data: window.chartSeriMasuk, backgroundColor: '#0ea5e9', borderRadius: 4 },
      { label: 'Pengeluaran', data: window.chartSeriKeluar, backgroundColor: '#f43f5e', borderRadius: 4 },
    ]
  };
  new Chart(document.getElementById('chartBulanan'), {
    type: 'bar',
    data: dataBulanan,
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: { y: { beginAtZero: true } },
      plugins: { legend: { position: 'bottom' } }
    }
  });

  new Chart(document.getElementById('chartHarian'), {
    type: 'line',
    data: {
      labels: window.chartHariLabels,
      datasets: [{
        label: 'Penjualan',
        data: window.chartSeriJual,
        borderColor: '#059669',
        backgroundColor: 'rgba(5,150,105,.12)',
        fill: true,
        tension: 0.35
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: { y: { beginAtZero: true } },
      plugins: { legend: { display: false } }
    }
  });

  new Chart(document.getElementById('chartKategori'), {
    type: 'doughnut',
    data: {
      labels: window.chartKatLabels,
      datasets: [{
        data: window.chartKatNilai,
        backgroundColor: ['#059669', '#0ea5e9', '#f59e0b', '#8b5cf6', '#f43f5e', '#14b8a6']
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { position: 'right' } }
    }
  });
})();
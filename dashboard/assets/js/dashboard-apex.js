// Variable Global untuk Filter
let activeYear = null;

// Instance Chart (disimpan agar bisa di-update)
let chart1, chart2, chart3, chart4, chart5;

// Konfigurasi Warna Tema Riho
const colors = ["#7366ff", "#f73164", "#51bb25", "#f8d62b", "#a927f9"];

/**
 * Fungsi Utama: Fetch Data dari API
 */
async function fetchData(action) {
  let url = `api/data.php?action=${action}`;
  if (activeYear) {
    url += `&year=${activeYear}`;
  }
  const res = await fetch(url);
  return await res.json();
}

/**
 * 1. Render Chart BQ1: Tren Penjualan (Support Drill-down)
 */
async function renderBQ1() {
  const data = await fetchData("bq1");
  const labels = data.map((d) => d.Label);
  const seriesData = data.map((d) => parseFloat(d.Total));

  const options = {
    series: [{ name: "Total Sales", data: seriesData }],
    chart: {
      type: "bar",
      height: 350,
      events: {
        // EVENT KLIK UNTUK DRILL-DOWN
        dataPointSelection: function (event, chartContext, config) {
          // Cek jika kita sedang di view tahun (belum ada filter aktif)
          if (activeYear === null) {
            const selectedYear =
              config.w.config.xaxis.categories[config.dataPointIndex];
            applyFilter(selectedYear); // Trigger Drill-down
          }
        },
      },
    },
    colors: [colors[0]],
    plotOptions: { bar: { borderRadius: 4, columnWidth: "45%" } },
    xaxis: { categories: labels },
    title: {
      text: activeYear
        ? `Detail Penjualan Tahun ${activeYear}`
        : "Penjualan per Tahun (Klik bar untuk detail)",
      align: "left",
    },
  };

  if (chart1) {
    chart1.updateOptions(options);
  } else {
    chart1 = new ApexCharts(document.querySelector("#chart-bq1"), options);
    chart1.render();
  }
}

/**
 * 2. Render Chart BQ2: Distribusi Pembelian (Histogram Style)
 */
async function renderBQ2() {
  const data = await fetchData("bq2");
  const options = {
    series: [{ name: "Jumlah Customer", data: data.map((d) => d.Total) }],
    chart: { type: "bar", height: 350 },
    colors: [colors[3]],
    xaxis: {
      categories: data.map((d) => d.Label + "x Transaksi"),
      title: { text: "Frekuensi Beli" },
    },
    title: { text: "Seberapa sering customer belanja?", align: "left" },
  };

  if (chart2) chart2.updateOptions(options);
  else {
    chart2 = new ApexCharts(document.querySelector("#chart-bq2"), options);
    chart2.render();
  }
}

/**
 * 3. Render Chart BQ3: Urban vs Rural (Area Chart)
 */
async function renderBQ3() {
  const data = await fetchData("bq3");

  // Data processing: Pisahkan Urban dan Rural
  const months = [...new Set(data.map((item) => item.MonthName))];
  const urbanData = months.map((m) => {
    const found = data.find(
      (d) => d.MonthName === m && d.UrbanFlag === "Urban"
    );
    return found ? parseFloat(found.Total) : 0;
  });
  const ruralData = months.map((m) => {
    const found = data.find(
      (d) => d.MonthName === m && d.UrbanFlag === "Rural"
    );
    return found ? parseFloat(found.Total) : 0;
  });

  const options = {
    series: [
      { name: "Urban", data: urbanData },
      { name: "Rural", data: ruralData },
    ],
    chart: { type: "area", height: 350 },
    colors: [colors[2], colors[1]],
    dataLabels: { enabled: false },
    stroke: { curve: "smooth" },
    xaxis: { categories: months },
    title: { text: "Perbandingan Wilayah", align: "left" },
  };

  if (chart3) chart3.updateOptions(options);
  else {
    chart3 = new ApexCharts(document.querySelector("#chart-bq3"), options);
    chart3.render();
  }
}

/**
 * 4. Render Chart BQ4: Online vs Offline (Donut)
 */
async function renderBQ4() {
  const data = await fetchData("bq4");
  const options = {
    series: data.map((d) => parseFloat(d.Total)),
    labels: data.map((d) => d.Label),
    chart: { type: "donut", height: 350 },
    colors: [colors[0], colors[4]],
    legend: { position: "bottom" },
    title: { text: "Share Channel Penjualan", align: "left" },
  };

  if (chart4) chart4.updateOptions(options);
  else {
    chart4 = new ApexCharts(document.querySelector("#chart-bq4"), options);
    chart4.render();
  }
}

/**
 * 5. Render Chart BQ5: Top Salesperson (Bar Horizontal)
 */
async function renderBQ5() {
  const data = await fetchData("bq5");
  const options = {
    series: [
      { name: "Total Sales", data: data.map((d) => parseFloat(d.Total)) },
    ],
    chart: { type: "bar", height: 350 },
    plotOptions: { bar: { horizontal: true } },
    colors: [colors[4]],
    xaxis: { categories: data.map((d) => d.Label) },
    title: { text: "Top 5 Salesperson", align: "left" },
  };

  if (chart5) chart5.updateOptions(options);
  else {
    chart5 = new ApexCharts(document.querySelector("#chart-bq5"), options);
    chart5.render();
  }
}

/**
 * --- LOGIC FILTERING ---
 */

// Fungsi dipanggil saat grafik tahun diklik
function applyFilter(year) {
  activeYear = year;
  document.getElementById("filter-status").innerText = `Tahun ${year}`;
  document.getElementById("filter-status").classList.remove("text-danger");
  document.getElementById("filter-status").classList.add("text-success");

  // Refresh semua chart dengan filter baru
  refreshAllCharts();
}

// Fungsi Reset
function resetDashboard() {
  activeYear = null;
  document.getElementById("filter-status").innerText = "All Years";

  // Refresh semua chart kembali ke default
  refreshAllCharts();
}

// Helper untuk update semua
function refreshAllCharts() {
  renderBQ1();
  renderBQ2();
  renderBQ3();
  renderBQ4();
  renderBQ5();
}

// Inisialisasi awal saat halaman load
document.addEventListener("DOMContentLoaded", function () {
  refreshAllCharts();
});

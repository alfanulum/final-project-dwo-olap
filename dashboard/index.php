<?php
require_once 'config/database.php';

$db = new Database();
$conn = $db->getConnection();

/* =========================================================
   AJAX → DETAIL BULAN
   ========================================================= */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'monthly') {

  $year = $_GET['year'];
  $territory = $_GET['territory'];

  $sql = "
    SELECT
      dt.Month,
      dt.MonthName,
      SUM(fs.LineTotal) AS TotalSales
    FROM factsales fs
    JOIN dimtime dt ON fs.TimeKey = dt.TimeKey
    JOIN dimgeography dg ON fs.GeographyKey = dg.GeographyKey
    WHERE dt.Year = :year
      AND dg.TerritoryName = :territory
    GROUP BY dt.Month, dt.MonthName
    ORDER BY dt.Month ASC
  ";

  $stmt = $conn->prepare($sql);
  $stmt->execute([
    ':year' => $year,
    ':territory' => $territory
  ]);

  $labels = [];
  $data = [];

  foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $labels[] = $row['MonthName'];
    $data[] = round($row['TotalSales'], 2);
  }

  echo json_encode([
    'labels' => $labels,
    'data' => $data
  ]);
  exit;
}

/* =========================================================
   DATA TAHUNAN
   ========================================================= */
$sql = "
SELECT 
  dt.Year,
  dg.TerritoryName,
  SUM(fs.LineTotal) AS TotalSales
FROM factsales fs
JOIN dimtime dt ON fs.TimeKey = dt.TimeKey
JOIN dimgeography dg ON fs.GeographyKey = dg.GeographyKey
WHERE dt.Year >= (SELECT MAX(Year) - 4 FROM dimtime)
GROUP BY dt.Year, dg.TerritoryName
ORDER BY dt.Year ASC
";

$stmt = $conn->prepare($sql);
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================================================
   FORMAT DATA
   ========================================================= */
$years = [];
$tmp = [];

foreach ($data as $row) {
  $years[] = $row['Year'];
  $tmp[$row['TerritoryName']][$row['Year']] = $row['TotalSales'];
}

$years = array_values(array_unique($years));
sort($years);

$datasets = [];
foreach ($tmp as $territory => $values) {
  $points = [];
  foreach ($years as $y) {
    $points[] = round($values[$y] ?? 0, 2);
  }
  $datasets[] = [
    'label' => $territory,
    'data' => $points,
    'tension' => 0.4
  ];
}

/* =========================================================
   FREKUENSI PEMBELIAN PELANGGAN PER BULAN (12 BULAN)
   ========================================================= */
$sql = "
SELECT
  DATE_FORMAT(dt.FullDate, '%b %Y') AS MonthLabel,
  COUNT(DISTINCT fs.CustomerKey) AS TotalCustomers
FROM factsales fs
JOIN dimtime dt ON fs.TimeKey = dt.TimeKey
WHERE dt.FullDate >= DATE_SUB(
  (SELECT MAX(FullDate) FROM dimtime),
  INTERVAL 12 MONTH
)
GROUP BY YEAR(dt.FullDate), MONTH(dt.FullDate)
ORDER BY YEAR(dt.FullDate), MONTH(dt.FullDate)
";

$stmt = $conn->prepare($sql);
$stmt->execute();
$monthlyFreq = $stmt->fetchAll(PDO::FETCH_ASSOC);

$monthLabels = [];
$customerCounts = [];

foreach ($monthlyFreq as $row) {
  $monthLabels[] = $row['MonthLabel'];
  $customerCounts[] = (int)$row['TotalCustomers'];
}

/* =========================================================
   PERBANDINGAN PENJUALAN BULANAN BERDASARKAN GENDER
   (TAHUN TERAKHIR)
   ========================================================= */
$sql = "
SELECT
  dt.Month,
  dt.MonthName,
  dc.Gender,
  SUM(fs.LineTotal) AS TotalSales
FROM factsales fs
JOIN dimtime dt ON fs.TimeKey = dt.TimeKey
JOIN dimcustomer dc ON fs.CustomerKey = dc.CustomerKey
WHERE dt.Year = (SELECT MAX(Year) FROM dimtime)
GROUP BY dt.Month, dt.MonthName, dc.Gender
ORDER BY dt.Month ASC
";

$stmt = $conn->prepare($sql);
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================================================
   FORMAT DATA UNTUK CHART.JS
   ========================================================= */
$months = [];
$male = [];
$female = [];

foreach ($data as $row) {
  if (!in_array($row['MonthName'], $months)) {
    $months[] = $row['MonthName'];
  }

  if (strtolower($row['Gender']) === 'male') {
    $male[$row['MonthName']] = round($row['TotalSales'], 2);
  } elseif (strtolower($row['Gender']) === 'female') {
    $female[$row['MonthName']] = round($row['TotalSales'], 2);
  }
}

$maleData = [];
$femaleData = [];

foreach ($months as $m) {
  $maleData[] = $male[$m] ?? 0;
  $femaleData[] = $female[$m] ?? 0;
}
?>

<?php include 'layouts/head.php'; ?>

<div class="page-body-wrapper">
  <?php include 'layouts/sidebar.php'; ?>

  <div class="page-body">
    <div class="container-fluid">

      <!-- Modern Hero Section -->
      <div class="page-hero">
        <h3>Sales Overview</h3>
        <p>Comprehensive analytics and insights for your business performance</p>
      </div>

      <!-- Chart Section 1 -->
      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              <h4 id="chartTitle">Total Penjualan Tahunan</h4>
              <p class="text-muted mb-0" id="chartSubtitle">
                Berdasarkan wilayah dan produk di AdventureWorks selama 5 tahun terakhir
              </p>
            </div>
            <div class="card-body">
              <button id="btnBack" class="btn btn-sm btn-secondary mb-3 d-none">
                <i class="fas fa-arrow-left"></i> Kembali ke Tahunan
              </button>
              <canvas id="chartCanvas" height="100"></canvas>
            </div>
          </div>
        </div>
      </div>

      <!-- Chart Section 2 -->
      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              <h4>Distribusi Frekuensi Pembelian Pelanggan</h4>
              <p class="text-muted mb-0">
                Berdasarkan jumlah pelanggan yang melakukan pembelian selama 12 bulan terakhir
              </p>
            </div>
            <div class="card-body">
              <canvas id="frequencyChart" height="100"></canvas>
            </div>
          </div>
        </div>
      </div>
      <!-- Chart Section 3 -->
      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              <h4>Perbandingan Penjualan Bulanan</h4>
              <p class="text-muted mb-0">
                Berdasarkan Gender Customer (1 Tahun Terakhir)
              </p>
            </div>
            <div class="card-body">
              <canvas id="genderSalesChart" height="100"></canvas>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php include 'layouts/footer.php'; ?>
</div>

<?php include 'layouts/tail.php'; ?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
  let chart;
  let mode = 'yearly';

  const yearlyData = {
    labels: <?= json_encode($years); ?>,
    datasets: <?= json_encode($datasets); ?>
  };

  const ctx = document.getElementById('chartCanvas');

  const baseAnimation = {
    duration: 800,
    easing: 'easeOutQuart'
  };

  function renderYearly() {
    document.getElementById('chartTitle').innerText = 'Total Penjualan Tahunan';
    document.getElementById('chartSubtitle').innerText = 'Klik titik tahun untuk melihat detail bulanan';
    document.getElementById('btnBack').classList.add('d-none');

    if (chart) chart.destroy();

    chart = new Chart(ctx, {
      type: 'line',
      data: yearlyData,
      options: {
        animation: baseAnimation,
        responsive: true,
        maintainAspectRatio: true,
        animations: {
          y: {
            from: 0
          }
        },
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              padding: 15,
              usePointStyle: true
            }
          },
          tooltip: {
            callbacks: {
              label: ctx => 'Rp ' + ctx.raw.toLocaleString('id-ID')
            }
          }
        },
        scales: {
          y: {
            ticks: {
              callback: v => 'Rp ' + v.toLocaleString('id-ID')
            }
          }
        },
        onClick: (evt, elements) => {
          if (!elements.length) return;
          const el = elements[0];
          const year = yearlyData.labels[el.index];
          const territory = yearlyData.datasets[el.datasetIndex].label;
          loadMonthly(year, territory);
        }
      }
    });

    mode = 'yearly';
  }

  function loadMonthly(year, territory) {
    fetch(`?ajax=monthly&year=${year}&territory=${territory}`)
      .then(res => res.json())
      .then(res => {
        document.getElementById('chartTitle').innerText = 'Detail Penjualan Bulanan';
        document.getElementById('chartSubtitle').innerText = `${territory} - ${year}`;
        document.getElementById('btnBack').classList.remove('d-none');

        if (chart) chart.destroy();

        chart = new Chart(ctx, {
          type: 'bar',
          data: {
            labels: res.labels,
            datasets: [{
              label: 'Total Penjualan',
              data: res.data,
              backgroundColor: 'rgba(13, 148, 136, 0.8)',
              borderColor: 'rgba(13, 148, 136, 1)',
              borderWidth: 1
            }]
          },
          options: {
            animation: baseAnimation,
            responsive: true,
            maintainAspectRatio: true,
            animations: {
              y: {
                from: 0
              }
            },
            plugins: {
              legend: {
                display: false
              },
              tooltip: {
                callbacks: {
                  label: ctx => 'Rp ' + ctx.raw.toLocaleString('id-ID')
                }
              }
            },
            scales: {
              y: {
                ticks: {
                  callback: v => 'Rp ' + v.toLocaleString('id-ID')
                }
              }
            }
          }
        });

        mode = 'monthly';
      });
  }

  document.getElementById('btnBack').addEventListener('click', () => {
    renderYearly();
  });

  /* INIT */
  renderYearly();
</script>

<script>
  const freqCtx = document.getElementById('frequencyChart');

  new Chart(freqCtx, {
    type: 'line',
    data: {
      labels: <?= json_encode($monthLabels); ?>,
      datasets: [{
        label: 'Jumlah Pelanggan',
        data: <?= json_encode($customerCounts); ?>,
        tension: 0.4,
        fill: true,
        backgroundColor: 'rgba(13, 148, 136, 0.1)',
        borderColor: 'rgba(13, 148, 136, 1)',
        borderWidth: 2,
        pointBackgroundColor: 'rgba(13, 148, 136, 1)',
        pointBorderColor: '#fff',
        pointBorderWidth: 2,
        pointRadius: 4,
        pointHoverRadius: 6
      }]
    },
    options: {
      animation: {
        duration: 800,
        easing: 'easeOutQuart'
      },
      responsive: true,
      maintainAspectRatio: true,
      plugins: {
        legend: {
          position: 'bottom',
          labels: {
            padding: 15,
            usePointStyle: true
          }
        },
        tooltip: {
          callbacks: {
            label: ctx => ctx.raw.toLocaleString('id-ID') + ' pelanggan'
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            precision: 0
          },
          title: {
            display: true,
            text: 'Jumlah Pelanggan'
          }
        },
        x: {
          title: {
            display: true,
            text: 'Bulan'
          }
        }
      }
    }
  });
  // js chart 3 

  const genderCtx = document.getElementById('genderSalesChart');

  new Chart(genderCtx, {
    type: 'line',
    data: {
      labels: <?= json_encode($months); ?>,
      datasets: [{
          label: 'Male',
          data: <?= json_encode($maleData); ?>,
          tension: 0.4,
          borderWidth: 2,
          borderColor: 'rgba(59, 130, 246, 1)',
          backgroundColor: 'rgba(59, 130, 246, 0.15)',
          fill: true,
          pointRadius: 4,
          pointHoverRadius: 6
        },
        {
          label: 'Female',
          data: <?= json_encode($femaleData); ?>,
          tension: 0.4,
          borderWidth: 2,
          borderColor: 'rgba(236, 72, 153, 1)',
          backgroundColor: 'rgba(236, 72, 153, 0.15)',
          fill: true,
          pointRadius: 4,
          pointHoverRadius: 6
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      plugins: {
        legend: {
          position: 'bottom'
        },
        tooltip: {
          callbacks: {
            label: ctx =>
              ctx.dataset.label + ': Rp ' + ctx.raw.toLocaleString('id-ID')
          }
        }
      },
      scales: {
        y: {
          ticks: {
            callback: v => 'Rp ' + v.toLocaleString('id-ID')
          },
          title: {
            display: true,
            text: 'Total Penjualan'
          }
        },
        x: {
          title: {
            display: true,
            text: 'Bulan'
          }
        }
      }
    }
  });
</script>
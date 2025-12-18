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

/* =========================================================
   TAMBAHAN 1: OVERALL STATISTICS
   ========================================================= */
$sqlOverall = "
SELECT 
  SUM(fs.LineTotal) AS TotalSales,
  COUNT(DISTINCT fs.CustomerKey) AS TotalCustomers,
  COUNT(DISTINCT fs.ProductKey) AS TotalProducts,
  COUNT(DISTINCT dg.TerritoryName) AS TotalTerritories,
  AVG(fs.LineTotal) AS AvgTransactionValue,
  COUNT(*) AS TotalTransactions
FROM factsales fs
JOIN dimgeography dg ON fs.GeographyKey = dg.GeographyKey
";

$stmt = $conn->prepare($sqlOverall);
$stmt->execute();
$overallStats = $stmt->fetch(PDO::FETCH_ASSOC);

/* =========================================================
   TAMBAHAN 2: TOP 3 PRODUCTS THIS YEAR
   ========================================================= */
$sqlTopProducts = "
SELECT 
  dp.ProductName,
  SUM(fs.LineTotal) AS TotalSales,
  SUM(fs.OrderQty) AS TotalQty,
  COUNT(DISTINCT fs.CustomerKey) AS TotalCustomers
FROM factsales fs
JOIN dimproduct dp ON fs.ProductKey = dp.ProductKey
JOIN dimtime dt ON fs.TimeKey = dt.TimeKey
WHERE dt.Year = (SELECT MAX(Year) FROM dimtime)
GROUP BY dp.ProductName
ORDER BY TotalSales DESC
LIMIT 3
";

$stmt = $conn->prepare($sqlTopProducts);
$stmt->execute();
$topProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================================================
   TAMBAHAN 3: CUSTOMER SEGMENTATION
   ========================================================= */
$sqlSegments = "
SELECT 
  CASE 
    WHEN PurchaseCount >= 10 THEN 'High Frequency'
    WHEN PurchaseCount >= 5 THEN 'Medium Frequency'
    ELSE 'Low Frequency'
  END AS Segment,
  COUNT(*) AS CustomerCount,
  SUM(TotalSpent) AS SegmentSales,
  AVG(PurchaseCount) AS AvgTransactions,
  AVG(TotalSpent) AS AvgSpent
FROM (
  SELECT 
    CustomerKey,
    COUNT(*) AS PurchaseCount,
    SUM(LineTotal) AS TotalSpent
  FROM factsales
  GROUP BY CustomerKey
) AS customer_stats
GROUP BY 
  CASE 
    WHEN PurchaseCount >= 10 THEN 'High Frequency'
    WHEN PurchaseCount >= 5 THEN 'Medium Frequency'
    ELSE 'Low Frequency'
  END
ORDER BY SegmentSales DESC
";

$stmt = $conn->prepare($sqlSegments);
$stmt->execute();
$customerSegments = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================================================
   TAMBAHAN 4: MONTHLY GROWTH
   ========================================================= */
$sqlGrowth = "
SELECT 
  DATE_FORMAT(CurrentMonth.FullDate, '%b %Y') AS CurrentMonth,
  DATE_FORMAT(PrevMonth.FullDate, '%b %Y') AS PreviousMonth,
  CurrentMonth.TotalSales AS CurrentSales,
  PrevMonth.TotalSales AS PreviousSales,
  ROUND(((CurrentMonth.TotalSales - PrevMonth.TotalSales) / PrevMonth.TotalSales) * 100, 2) AS GrowthRate
FROM (
  SELECT 
    YEAR(dt.FullDate) AS Year,
    MONTH(dt.FullDate) AS Month,
    MAX(dt.FullDate) AS FullDate,
    SUM(fs.LineTotal) AS TotalSales
  FROM factsales fs
  JOIN dimtime dt ON fs.TimeKey = dt.TimeKey
  WHERE dt.FullDate >= DATE_SUB((SELECT MAX(FullDate) FROM dimtime), INTERVAL 13 MONTH)
  GROUP BY YEAR(dt.FullDate), MONTH(dt.FullDate)
) AS CurrentMonth
JOIN (
  SELECT 
    YEAR(dt.FullDate) AS Year,
    MONTH(dt.FullDate) AS Month,
    MAX(dt.FullDate) AS FullDate,
    SUM(fs.LineTotal) AS TotalSales
  FROM factsales fs
  JOIN dimtime dt ON fs.TimeKey = dt.TimeKey
  WHERE dt.FullDate >= DATE_SUB((SELECT MAX(FullDate) FROM dimtime), INTERVAL 14 MONTH)
  GROUP BY YEAR(dt.FullDate), MONTH(dt.FullDate)
) AS PrevMonth ON 
  (CurrentMonth.Year = PrevMonth.Year AND CurrentMonth.Month = PrevMonth.Month + 1)
  OR (CurrentMonth.Month = 1 AND CurrentMonth.Year = PrevMonth.Year + 1 AND PrevMonth.Month = 12)
ORDER BY CurrentMonth.Year, CurrentMonth.Month DESC
LIMIT 1
";

$stmt = $conn->prepare($sqlGrowth);
$stmt->execute();
$growthData = $stmt->fetch(PDO::FETCH_ASSOC);

/* =========================================================
   TAMBAHAN 5: BEST PERFORMING TERRITORY
   ========================================================= */
$sqlBestTerritory = "
SELECT 
  dg.TerritoryName,
  SUM(fs.LineTotal) AS TotalSales,
  COUNT(DISTINCT fs.CustomerKey) AS TotalCustomers,
  COUNT(DISTINCT fs.ProductKey) AS UniqueProducts,
  ROUND(SUM(fs.LineTotal) / COUNT(DISTINCT fs.CustomerKey), 2) AS AvgPerCustomer
FROM factsales fs
JOIN dimgeography dg ON fs.GeographyKey = dg.GeographyKey
GROUP BY dg.TerritoryName
ORDER BY TotalSales DESC
LIMIT 1
";

$stmt = $conn->prepare($sqlBestTerritory);
$stmt->execute();
$bestTerritory = $stmt->fetch(PDO::FETCH_ASSOC);

?>

<?php include 'layouts/head.php'; ?>

<div class="page-body-wrapper">
  <?php include 'layouts/sidebar.php'; ?>

  <div class="page-body">
    <div class="container-fluid">

      <!-- Modern Hero Section -->
      <div class="page-hero">
        <div class="hero-content">
          <h3>Home Dashboard</h3>
          <p>Ringkasan utama performa penjualan dan perilaku pelanggan</p>
        </div>
      </div>

      <!-- =========================================================
           TAMBAHAN: OVERALL STATISTICS CARDS
           ========================================================= -->
      <div class="row mb-4">
        <div class="col-xl-3 col-md-6 ">
          <div class="card stat-card gradient-card">
            <div class="card-body">
              <div class="card-icon">
                <i class="fas fa-chart-line"></i>
              </div>
              <div class="card-content">
                <h6 class="card-title">Total Penjualan</h6>
                <h2 class="card-value">Rp <?= number_format($overallStats['TotalSales'], 0, ',', '.') ?></h2>
                <?php if ($growthData): ?>
                  <div class="card-growth">
                    <span class="growth-badge <?= $growthData['GrowthRate'] >= 0 ? 'positive' : 'negative' ?>">
                      <i class="fas fa-arrow-<?= $growthData['GrowthRate'] >= 0 ? 'up' : 'down' ?>"></i>
                      <?= abs($growthData['GrowthRate']) ?>%
                    </span>
                    <small class="text-muted">vs bulan sebelumnya</small>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>

        <div class="col-xl-3 col-md-6">
          <div class="card stat-card gradient-card">
            <div class="card-body">
              <div class="card-icon">
                <i class="fas fa-users"></i>
              </div>
              <div class="card-content">
                <h6 class="card-title">Total Pelanggan</h6>
                <h2 class="card-value"><?= number_format($overallStats['TotalCustomers'], 0, ',', '.') ?></h2>
                <small class="text-muted">Pelanggan aktif</small>
              </div>
            </div>
          </div>
        </div>

        <div class="col-xl-3 col-md-6">
          <div class="card stat-card gradient-card">
            <div class="card-body">
              <div class="card-icon">
                <i class="fas fa-boxes"></i>
              </div>
              <div class="card-content">
                <h6 class="card-title">Total Produk</h6>
                <h2 class="card-value"><?= number_format($overallStats['TotalProducts'], 0, ',', '.') ?></h2>
                <small class="text-muted">SKU terjual</small>
              </div>
            </div>
          </div>
        </div>

        <div class="col-xl-3 col-md-6">
          <div class="card stat-card gradient-card">
            <div class="card-body">
              <div class="card-icon">
                <i class="fas fa-shopping-cart"></i>
              </div>
              <div class="card-content">
                <h6 class="card-title">Rata-rata Transaksi</h6>
                <h2 class="card-value">Rp <?= number_format($overallStats['AvgTransactionValue'], 0, ',', '.') ?></h2>
                <small class="text-muted">Per transaksi</small>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- =========================================================
           TAMBAHAN: TOP PRODUCTS & BEST TERRITORY
           ========================================================= -->
      <div class="row mb-4">
        <!-- TOP 3 PRODUCTS -->
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              <h4><i class="fas fa-trophy me-2"></i>Top 3 Produk Tahun Ini</h4>
              <p class="text-muted mb-0">Produk dengan penjualan tertinggi di tahun <?= max($years) ?></p>
            </div>
            <div class="card-body">
              <div class="row g-6">
                <?php foreach ($topProducts as $index => $product): ?>
                  <div class="col-md-4">
                    <div class="top-product-card">
                      <div class="product-rank">#<?= $index + 1 ?></div>
                      <h6 class="product-name"><?= $product['ProductName'] ?></h6>
                      <div class="product-stats">
                        <div class="stat-item">
                          <span class="stat-label">Total Penjualan</span>
                          <span class="stat-value text-primary">
                            Rp <?= number_format($product['TotalSales'], 0, ',', '.') ?>
                          </span>
                        </div>
                        <div class="row g-2 mt-2">
                          <div class="col-6">
                            <span class="stat-label">Qty Terjual</span>
                            <span class="stat-value"><?= number_format($product['TotalQty'], 0, ',', '.') ?></span>
                          </div>
                          <div class="col-6">
                            <span class="stat-label">Pelanggan</span>
                            <span class="stat-value"><?= number_format($product['TotalCustomers'], 0, ',', '.') ?></span>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>

        <!-- BEST PERFORMING TERRITORY -->
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              <h4><i class="fas fa-award me-2"></i>Wilayah Terbaik</h4>
              <p class="text-muted mb-0">Wilayah dengan performa penjualan terbaik</p>
            </div>
            <div class="card-body">
              <div class="best-territory-content">
                <div class="territory-icon">
                  <i class="fas fa-trophy"></i>
                </div>
                <h3 class="territory-name"><?= $bestTerritory['TerritoryName'] ?></h3>
                <div class="territory-sales">
                  <span class="badge sales-badge">
                    Rp <?= number_format($bestTerritory['TotalSales'], 0, ',', '.') ?>
                  </span>
                </div>
                <div class="territory-stats mt-4">
                  <div class="row g-12">
                    <div class="col-6">
                      <div class="stat-box">
                        <small class="stat-label">Pelanggan</small>
                        <div class="stat-value"><?= number_format($bestTerritory['TotalCustomers'], 0, ',', '.') ?></div>
                      </div>
                    </div>
                    <div class="col-6">
                      <div class="stat-box">
                        <small class="stat-label">Produk Unik</small>
                        <div class="stat-value"><?= number_format($bestTerritory['UniqueProducts'], 0, ',', '.') ?></div>
                      </div>
                    </div>
                  </div>
                  <div class="avg-per-customer mt-3">
                    <small class="text-muted">Rata-rata per pelanggan</small>
                    <div class="avg-value text-success">
                      Rp <?= number_format($bestTerritory['AvgPerCustomer'], 0, ',', '.') ?>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Chart Section 1 - TIDAK BERUBAH -->
      <div class="row mb-4">
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              <h4 id="chartTitle">Total Penjualan Tahunan</h4>
              <p class="text-muted mb-0" id="chartSubtitle">
                Ringkasan penjualan tahunan berdasarkan wilayah (5 tahun terakhir)
              </p>
            </div>
            <div class="card-body">
              <button id="btnBack" class="btn btn-sm btn-secondary mb-3 d-none">
                <i class="fas fa-arrow-left me-1"></i>Kembali ke Tahunan
              </button>
              <div class="chart-container">
                <canvas id="chartCanvas" height="100"></canvas>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- =========================================================
           TAMBAHAN: CUSTOMER SEGMENTATION
           ========================================================= -->
      <div class="row mb-4">
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              <h4><i class="fas fa-users me-2"></i>Segmentasi Pelanggan</h4>
              <p class="text-muted mb-0">Analisis pelanggan berdasarkan frekuensi pembelian</p>
            </div>
            <div class="card-body">
              <div class="row">
                <div class="col-lg-6">
                  <div class="chart-container">
                    <canvas id="segmentChart" height="250"></canvas>
                  </div>
                </div>
                <div class="col-lg-6">
                  <div class="segment-list">
                    <?php foreach ($customerSegments as $segment): ?>
                      <div class="segment-item">
                        <div class="segment-header">
                          <h6 class="segment-title"><?= $segment['Segment'] ?></h6>
                          <span class="badge customer-count">
                            <?= number_format($segment['CustomerCount'], 0, ',', '.') ?> pelanggan
                          </span>
                        </div>
                        <div class="segment-body">
                          <div class="row g-3 mb-3">
                            <div class="col-6">
                              <small class="text-muted">Total Penjualan</small>
                              <div class="text-success fw-bold segment-sales">
                                Rp <?= number_format($segment['SegmentSales'], 0, ',', '.') ?>
                              </div>
                            </div>
                            <div class="col-6">
                              <small class="text-muted">Rata-rata Transaksi</small>
                              <div class="fw-bold segment-transactions">
                                <?= number_format($segment['AvgTransactions'], 1) ?>x
                              </div>
                            </div>
                          </div>
                          <?php
                          $totalSegmentSales = array_sum(array_column($customerSegments, 'SegmentSales'));
                          $percentage = $totalSegmentSales > 0 ? ($segment['SegmentSales'] / $totalSegmentSales) * 100 : 0;
                          ?>
                          <div class="progress">
                            <div class="progress-bar segment-progress"
                              style="width: <?= $percentage ?>%;"
                              data-segment="<?= strtolower(explode(' ', $segment['Segment'])[0]) ?>">
                            </div>
                          </div>
                          <div class="segment-percentage mt-2">
                            <small class="text-muted">
                              <?= round($percentage, 1) ?>% dari total penjualan
                            </small>
                          </div>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                </div>
              </div>
              <div class="row mt-4">
                <div class="col-12">
                  <div class="insight-alert">
                    <div class="alert-icon">
                      <i class="fas fa-lightbulb"></i>
                    </div>
                    <div class="alert-content">
                      <h6 class="alert-heading">Business Insight:</h6>
                      <p class="mb-0">
                        Pelanggan <strong>High Frequency</strong> (10+ transaksi) hanya mewakili
                        <?=
                        array_sum(array_column($customerSegments, 'CustomerCount')) > 0 ?
                          round(($customerSegments[0]['CustomerCount'] / array_sum(array_column($customerSegments, 'CustomerCount'))) * 100, 1) : 0
                        ?>% dari total pelanggan, namun memberikan kontribusi
                        <?=
                        $totalSegmentSales > 0 ?
                          round(($customerSegments[0]['SegmentSales'] / $totalSegmentSales) * 100, 1) : 0
                        ?>% dari total penjualan.
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Chart Section 2 - TIDAK BERUBAH -->
      <div class="row mb-4">
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              <h4>Distribusi Frekuensi Pembelian Pelanggan</h4>
              <p class="text-muted mb-0">
                Berdasarkan jumlah pelanggan yang melakukan pembelian selama 12 bulan terakhir
              </p>
            </div>
            <div class="card-body">
              <div class="chart-container">
                <canvas id="frequencyChart" height="100"></canvas>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Chart Section 3 - TIDAK BERUBAH -->
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
              <div class="chart-container">
                <canvas id="genderSalesChart" height="100"></canvas>
              </div>
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

<!-- Script untuk grafik utama (TIDAK BERUBAH) -->
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

<!-- Script untuk grafik frekuensi (TIDAK BERUBAH) -->
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
</script>

<!-- Script untuk grafik gender (TIDAK BERUBAH) -->
<script>
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

<!-- Script TAMBAHAN untuk Customer Segmentation Chart -->
<script>
  const segmentData = {
    labels: <?= json_encode(array_column($customerSegments, 'Segment')); ?>,
    datasets: [{
      data: <?= json_encode(array_column($customerSegments, 'SegmentSales')); ?>,
      backgroundColor: [
        'rgba(16, 185, 129, 0.8)', // High Frequency - success
        'rgba(245, 158, 11, 0.8)', // Medium Frequency - warning
        'rgba(59, 130, 246, 0.8)' // Low Frequency - info
      ],
      borderColor: [
        'rgb(16, 185, 129)',
        'rgb(245, 158, 11)',
        'rgb(59, 130, 246)'
      ],
      borderWidth: 2
    }]
  };

  const segmentCtx = document.getElementById('segmentChart').getContext('2d');
  new Chart(segmentCtx, {
    type: 'doughnut',
    data: segmentData,
    options: {
      responsive: true,
      maintainAspectRatio: true,
      cutout: '65%',
      plugins: {
        legend: {
          position: 'right',
          labels: {
            padding: 20,
            usePointStyle: true,
            pointStyle: 'circle',
            font: {
              size: 11
            }
          }
        },
        tooltip: {
          callbacks: {
            label: function(context) {
              const label = context.label || '';
              const value = context.parsed;
              const total = context.dataset.data.reduce((a, b) => a + b, 0);
              const percentage = ((value / total) * 100).toFixed(1);
              return `${label}: Rp ${value.toLocaleString('id-ID')} (${percentage}%)`;
            }
          }
        }
      }
    }
  });
</script>
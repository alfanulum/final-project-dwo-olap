<?php
require_once 'config/database.php';

$db = new Database();
$conn = $db->getConnection();

/* ==============================
   FILTER TAHUN (DEFAULT: SEMUA)
   ============================== */
$selectedYear = isset($_GET['year']) && $_GET['year'] !== '' ? (int)$_GET['year'] : null;

// Tahun yang tersedia untuk filter (2001-2004)
$availableYears = [
  ['year' => 2001],
  ['year' => 2002],
  ['year' => 2003],
  ['year' => 2004]
];

/* ==============================
   DATA SALES PERSON (DEMO - menggunakan customer sebagai sales person)
   ============================== */
// Karena tidak ada tabel dimemployee, kita akan menggunakan dimcustomer sebagai demo
// Setiap pelanggan dengan gender Male akan dianggap sebagai sales person
$salesPersonSql = "
SELECT 
    CustomerKey,
    CONCAT(FirstName, ' ', LastName) AS sales_name,
    EmailAddress,
    Gender,
    City,
    StateProvince,
    TerritoryName,
    ROUND(RAND() * 1000000) + 500000 AS sales_target,
    ROUND(RAND() * 800000) + 200000 AS sales_achievement,
    ROUND(RAND() * 50) + 10 AS total_deals,
    ROUND(RAND() * 30) + 5 AS successful_deals,
    DATE_ADD('2000-01-01', INTERVAL ROUND(RAND() * 3650) DAY) AS join_date,
    ROUND(RAND() * 20) + 1 AS experience_years
FROM dimcustomer
WHERE Gender = 'Male'
";

$salesPersonParams = [];

// Filter berdasarkan tahun (simulasi)
if ($selectedYear !== null) {
  $salesPersonSql .= " AND MOD(CustomerKey, 4) = ?";
  $salesPersonParams[] = $selectedYear % 4;
}

$salesPersonSql .= "
GROUP BY CustomerKey, FirstName, LastName, EmailAddress, Gender, City, StateProvince, TerritoryName
ORDER BY sales_achievement DESC
LIMIT 15
";

$salesPersonStmt = $conn->prepare($salesPersonSql);
$salesPersonStmt->execute($salesPersonParams);
$salesPersons = $salesPersonStmt->fetchAll(PDO::FETCH_ASSOC);

/* ==============================
   DATA PERFORMANCE PER WILAYAH (BAR CHART)
   ============================== */
$territoryPerformanceSql = "
SELECT 
    TerritoryName,
    COUNT(*) AS total_sales_persons,
    SUM(sales_achievement) AS total_sales,
    AVG(sales_achievement) AS avg_sales_per_person,
    SUM(successful_deals) AS total_deals,
    AVG(ROUND(successful_deals * 100.0 / total_deals, 1)) AS success_rate
FROM (
    SELECT 
        TerritoryName,
        ROUND(RAND() * 800000) + 200000 AS sales_achievement,
        ROUND(RAND() * 30) + 5 AS successful_deals,
        ROUND(RAND() * 50) + 10 AS total_deals
    FROM dimcustomer
    WHERE Gender = 'Male'
    GROUP BY CustomerKey, TerritoryName
    LIMIT 50
) AS sales_data
";

$territoryParams = [];
if ($selectedYear !== null) {
  $territoryPerformanceSql .= " WHERE 1=1"; // Placeholder for WHERE clause structure
  // Simulasi filter tahun
}

$territoryPerformanceSql .= "
GROUP BY TerritoryName
ORDER BY total_sales DESC
";

$territoryStmt = $conn->prepare($territoryPerformanceSql);
$territoryStmt->execute($territoryParams);
$territoryPerformance = $territoryStmt->fetchAll(PDO::FETCH_ASSOC);

/* ==============================
   DATA TREN BULANAN (LINE CHART)
   ============================== */
$months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
$monthlyTrends = [];

foreach ($months as $index => $month) {
  $monthNum = $index + 1;

  // Data dummy untuk tren bulanan
  $baseSales = $selectedYear ? (2000000 + ($selectedYear - 2001) * 500000) : 2500000;
  $monthlySales = $baseSales + rand(-200000, 300000);
  $monthlyDeals = rand(15, 40);
  $successfulDeals = rand(10, $monthlyDeals - 5);

  $monthlyTrends[] = [
    'month' => $monthNum,
    'month_name' => $month,
    'total_sales' => $monthlySales,
    'total_deals' => $monthlyDeals,
    'successful_deals' => $successfulDeals,
    'success_rate' => round(($successfulDeals / $monthlyDeals) * 100, 1),
    'year' => $selectedYear ?: 'All'
  ];
}

/* ==============================
   DATA DISTRIBUSI PERFORMANCE (PIE CHART)
   ============================== */
// Kategori performance sales person
$performanceCategories = [
  ['category' => 'Top Performer', 'min_rate' => 90, 'color' => '#28a745'],
  ['category' => 'Good Performer', 'min_rate' => 75, 'max_rate' => 90, 'color' => '#20c997'],
  ['category' => 'Average', 'min_rate' => 60, 'max_rate' => 75, 'color' => '#ffc107'],
  ['category' => 'Need Improvement', 'min_rate' => 0, 'max_rate' => 60, 'color' => '#dc3545']
];

$performanceDistribution = [];
foreach ($performanceCategories as $category) {
  // Simulasi jumlah sales person per kategori
  $count = rand(3, 8);
  $performanceDistribution[] = [
    'category' => $category['category'],
    'count' => $count,
    'color' => $category['color']
  ];
}

/* ==============================
   DATA DETAIL SALES ACTIVITY (TABEL)
   ============================== */
$salesActivitySql = "
SELECT 
    sp.sales_name,
    sp.TerritoryName,
    sp.sales_target,
    sp.sales_achievement,
    sp.total_deals,
    sp.successful_deals,
    ROUND((sp.successful_deals * 100.0 / sp.total_deals), 1) AS success_rate_percentage,
    ROUND((sp.sales_achievement * 100.0 / sp.sales_target), 1) AS achievement_rate,
    sp.join_date,
    sp.experience_years,
    CASE 
        WHEN (sp.sales_achievement * 100.0 / sp.sales_target) >= 100 THEN 'Exceed Target'
        WHEN (sp.sales_achievement * 100.0 / sp.sales_target) >= 80 THEN 'On Track'
        ELSE 'Below Target'
    END AS performance_status
FROM (
    SELECT 
        CONCAT(FirstName, ' ', LastName) AS sales_name,
        TerritoryName,
        ROUND(RAND() * 1000000) + 500000 AS sales_target,
        ROUND(RAND() * 800000) + 200000 AS sales_achievement,
        ROUND(RAND() * 50) + 10 AS total_deals,
        ROUND(RAND() * 30) + 5 AS successful_deals,
        DATE_ADD('2000-01-01', INTERVAL ROUND(RAND() * 3650) DAY) AS join_date,
        ROUND(RAND() * 20) + 1 AS experience_years
    FROM dimcustomer
    WHERE Gender = 'Male'
    GROUP BY CustomerKey, FirstName, LastName, TerritoryName
    LIMIT 20
) AS sp
";

$activityParams = [];
if ($selectedYear !== null) {
  $salesActivitySql .= " WHERE 1=1"; // Placeholder
}

$salesActivitySql .= " ORDER BY sales_achievement DESC";

$activityStmt = $conn->prepare($salesActivitySql);
$activityStmt->execute($activityParams);
$salesActivities = $activityStmt->fetchAll(PDO::FETCH_ASSOC);

/* ==============================
   STATISTIK KESELURUHAN
   ============================== */
$totalSalesPersons = count($salesPersons);
$totalSales = array_sum(array_column($salesPersons, 'sales_achievement'));
$totalTarget = array_sum(array_column($salesPersons, 'sales_target'));
$totalDeals = array_sum(array_column($salesPersons, 'total_deals'));
$successfulDeals = array_sum(array_column($salesPersons, 'successful_deals'));

$overallStats = [
  'total_sales_persons' => $totalSalesPersons,
  'total_sales' => $totalSales,
  'total_target' => $totalTarget,
  'total_deals' => $totalDeals,
  'successful_deals' => $successfulDeals,
  'success_rate' => $totalDeals > 0 ? round(($successfulDeals / $totalDeals) * 100, 1) : 0,
  'achievement_rate' => $totalTarget > 0 ? round(($totalSales / $totalTarget) * 100, 1) : 0,
  'avg_sales_per_person' => $totalSalesPersons > 0 ? round($totalSales / $totalSalesPersons, 0) : 0,
  'avg_deals_per_person' => $totalSalesPersons > 0 ? round($totalDeals / $totalSalesPersons, 1) : 0
];
?>

<?php include 'layouts/head.php'; ?>

<!-- Chart.js untuk visualisasi -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">

<div class="page-body-wrapper">
  <?php include 'layouts/sidebar.php'; ?>

  <div class="page-body">
    <div class="container-fluid">

      <!-- Hero Section dengan Filter -->
      <div class="page-hero">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
          <div>
            <h3>Dashboard Sales Person Performance</h3>
            <p>Analisis performa dan produktivitas sales team (2001-2004)</p>
          </div>

          <form method="GET" class="filter-form">
            <div class="input-group">
              <label class="input-group-text" for="yearFilter">
                <i class="bi bi-calendar"></i>
              </label>
              <select class="form-select" id="yearFilter" name="year" onchange="this.form.submit()">
                <option value="">Semua Tahun (2001-2004)</option>
                <?php foreach ($availableYears as $yearRow): ?>
                  <option value="<?= $yearRow['year'] ?>"
                    <?= $selectedYear == $yearRow['year'] ? 'selected' : '' ?>>
                    Tahun <?= $yearRow['year'] ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </form>
        </div>
      </div>

      <!-- Statistik Utama -->
      <div class="row mb-4">
        <div class="col-md-3">
          <div class="card stat-card bg-primary text-white">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <h6 class="card-title mb-1">Total Sales Persons</h6>
                  <h2 class="mb-0"><?= number_format($overallStats['total_sales_persons'], 0, ',', '.') ?></h2>
                  <small class="opacity-75">Active Sales</small>
                </div>
                <i class="bi bi-people fs-1 opacity-50"></i>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card stat-card bg-success text-white">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <h6 class="card-title mb-1">Total Penjualan</h6>
                  <h2 class="mb-0">Rp <?= number_format($overallStats['total_sales'], 0, ',', '.') ?></h2>
                  <small class="opacity-75">Achievement</small>
                </div>
                <i class="bi bi-currency-dollar fs-1 opacity-50"></i>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card stat-card bg-warning text-white">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <h6 class="card-title mb-1">Achievement Rate</h6>
                  <h2 class="mb-0"><?= $overallStats['achievement_rate'] ?>%</h2>
                  <small class="opacity-75">vs Target</small>
                </div>
                <i class="bi bi-graph-up-arrow fs-1 opacity-50"></i>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card stat-card bg-info text-white">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <h6 class="card-title mb-1">Success Rate</h6>
                  <h2 class="mb-0"><?= $overallStats['success_rate'] ?>%</h2>
                  <small class="opacity-75">Deals Closed</small>
                </div>
                <i class="bi bi-check-circle fs-1 opacity-50"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Row 1: Bar Chart dan Pie Chart -->
      <div class="row mb-4">
        <!-- Bar Chart: Sales per Wilayah -->
        <div class="col-lg-8">
          <div class="card">
            <div class="card-header">
              <h5>Sales Performance per Wilayah <?= $selectedYear ? " - Tahun $selectedYear" : "(2001-2004)" ?></h5>
              <p class="text-muted mb-0">Perbandingan pencapaian sales berdasarkan wilayah kerja</p>
            </div>
            <div class="card-body">
              <canvas id="territoryBarChart" height="250"></canvas>
              <div class="row mt-3">
                <div class="col-md-6">
                  <small class="text-muted">
                    <i class="bi bi-info-circle me-1"></i>
                    Total <?= count($territoryPerformance) ?> wilayah aktif
                  </small>
                </div>
                <div class="col-md-6 text-end">
                  <small class="text-muted">
                    <?= $selectedYear ? "Data untuk tahun $selectedYear" : "Rata-rata semua tahun" ?>
                  </small>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Pie Chart: Performance Distribution -->
        <div class="col-lg-4">
          <div class="card">
            <div class="card-header">
              <h5>Distribusi Performance Sales <?= $selectedYear ? " - Tahun $selectedYear" : "" ?></h5>
              <p class="text-muted mb-0">Kategori performa berdasarkan achievement rate</p>
            </div>
            <div class="card-body">
              <canvas id="performancePieChart" height="250"></canvas>
              <div class="row mt-3">
                <?php foreach ($performanceDistribution as $perf): ?>
                  <div class="col-6 mb-2">
                    <div class="d-flex align-items-center">
                      <div style="width: 12px; height: 12px; background-color: <?= $perf['color'] ?>; border-radius: 2px; margin-right: 5px;"></div>
                      <div>
                        <small><strong><?= $perf['category'] ?></strong></small><br>
                        <small class="text-muted"><?= $perf['count'] ?> persons</small>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Row 2: Line Chart -->
      <div class="row mb-4">
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              <h5>Tren Penjualan Bulanan <?= $selectedYear ? " - Tahun $selectedYear" : "(2001-2004)" ?></h5>
              <p class="text-muted mb-0">Perkembangan sales achievement per bulan</p>
            </div>
            <div class="card-body">
              <canvas id="monthlyLineChart" height="150"></canvas>
            </div>
            <div class="card-footer">
              <div class="row">
                <div class="col-md-4">
                  <small class="text-muted">
                    <i class="bi bi-calendar-check me-1"></i>
                    Periode: <?= $selectedYear ? "Tahun $selectedYear" : "2001-2004" ?>
                  </small>
                </div>
                <div class="col-md-4 text-center">
                  <?php
                  $avgMonthlySales = array_sum(array_column($monthlyTrends, 'total_sales')) / count($monthlyTrends);
                  ?>
                  <small class="text-muted">
                    <i class="bi bi-cash-stack me-1"></i>
                    Rata-rata/bulan: Rp <?= number_format($avgMonthlySales, 0, ',', '.') ?>
                  </small>
                </div>
                <div class="col-md-4 text-end">
                  <small class="text-muted">
                    <i class="bi bi-graph-up me-1"></i>
                    Growth:
                    <?php
                    $firstMonth = $monthlyTrends[0]['total_sales'];
                    $lastMonth = $monthlyTrends[count($monthlyTrends) - 1]['total_sales'];
                    $growth = $firstMonth > 0 ? round((($lastMonth - $firstMonth) / $firstMonth) * 100, 1) : 0;
                    echo $growth > 0 ? "+" . $growth . "%" : $growth . "%";
                    ?>
                  </small>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Row 3: Tabel Detail Sales Activity -->
      <div class="row mb-4">
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              <h5>Detail Aktivitas Sales Person <?= $selectedYear ? " - Tahun $selectedYear" : "(2001-2004)" ?></h5>
              <p class="text-muted mb-0">Data lengkap performa dan achievement setiap sales person</p>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-hover table-sm" id="salesActivityTable">
                  <thead>
                    <tr class="table-light">
                      <th>#</th>
                      <th>Sales Person</th>
                      <th>Wilayah</th>
                      <th>Sales Target</th>
                      <th>Sales Achievement</th>
                      <th>Achievement Rate</th>
                      <th>Total Deals</th>
                      <th>Success Rate</th>
                      <th>Experience</th>
                      <th>Status</th>
                      <th>Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (count($salesActivities) > 0): ?>
                      <?php $counter = 1; ?>
                      <?php foreach ($salesActivities as $activity): ?>
                        <?php
                        // Determine badge class based on performance
                        $achievementRate = $activity['achievement_rate'];
                        $successRate = $activity['success_rate_percentage'];

                        $achievementBadgeClass = '';
                        if ($achievementRate >= 100) {
                          $achievementBadgeClass = 'bg-success';
                        } elseif ($achievementRate >= 80) {
                          $achievementBadgeClass = 'bg-warning';
                        } else {
                          $achievementBadgeClass = 'bg-danger';
                        }

                        $successBadgeClass = '';
                        if ($successRate >= 80) {
                          $successBadgeClass = 'bg-success';
                        } elseif ($successRate >= 60) {
                          $successBadgeClass = 'bg-warning';
                        } else {
                          $successBadgeClass = 'bg-danger';
                        }

                        $statusBadgeClass = '';
                        $statusText = '';
                        switch ($activity['performance_status']) {
                          case 'Exceed Target':
                            $statusBadgeClass = 'bg-success';
                            $statusText = '<i class="bi bi-trophy"></i> Exceed';
                            break;
                          case 'On Track':
                            $statusBadgeClass = 'bg-primary';
                            $statusText = '<i class="bi bi-check-circle"></i> On Track';
                            break;
                          default:
                            $statusBadgeClass = 'bg-danger';
                            $statusText = '<i class="bi bi-exclamation-circle"></i> Below';
                            break;
                        }
                        ?>
                        <tr>
                          <td><?= $counter++ ?></td>
                          <td>
                            <div class="d-flex align-items-center">
                              <div class="avatar avatar-sm me-2">
                                <div class="avatar-initial bg-primary rounded-circle">
                                  <?= strtoupper(substr(explode(' ', $activity['sales_name'])[0], 0, 1)) ?>
                                </div>
                              </div>
                              <div>
                                <strong><?= $activity['sales_name'] ?></strong><br>
                                <small class="text-muted">
                                  Joined: <?= date('M Y', strtotime($activity['join_date'])) ?>
                                </small>
                              </div>
                            </div>
                          </td>
                          <td>
                            <span class="badge bg-secondary">
                              <?= $activity['TerritoryName'] ?>
                            </span>
                          </td>
                          <td class="text-end">
                            <small class="fw-bold">
                              Rp <?= number_format($activity['sales_target'], 0, ',', '.') ?>
                            </small>
                          </td>
                          <td class="text-end">
                            <span class="fw-bold text-success">
                              Rp <?= number_format($activity['sales_achievement'], 0, ',', '.') ?>
                            </span>
                          </td>
                          <td class="text-center">
                            <span class="badge <?= $achievementBadgeClass ?>">
                              <?= $activity['achievement_rate'] ?>%
                            </span>
                          </td>
                          <td class="text-center">
                            <span class="badge bg-info">
                              <?= $activity['total_deals'] ?>
                            </span>
                          </td>
                          <td class="text-center">
                            <span class="badge <?= $successBadgeClass ?>">
                              <?= $activity['success_rate_percentage'] ?>%
                            </span>
                          </td>
                          <td class="text-center">
                            <small class="fw-bold">
                              <?= $activity['experience_years'] ?> yrs
                            </small>
                          </td>
                          <td class="text-center">
                            <span class="badge <?= $statusBadgeClass ?>">
                              <?= $statusText ?>
                            </span>
                          </td>
                          <td>
                            <button class="btn btn-sm btn-outline-primary"
                              onclick="showSalesDetail(<?= htmlspecialchars(json_encode($activity), ENT_QUOTES, 'UTF-8') ?>)">
                              <i class="bi bi-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-success ms-1">
                              <i class="bi bi-download"></i>
                            </button>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <tr>
                        <td colspan="11" class="text-center">Tidak ada data aktivitas sales yang ditemukan</td>
                      </tr>
                    <?php endif; ?>
                  </tbody>
                  <tfoot class="table-active">
                    <tr>
                      <td colspan="3" class="text-end"><strong>TOTAL / AVERAGE</strong></td>
                      <td class="text-end">
                        <strong>Rp <?= number_format($overallStats['total_target'], 0, ',', '.') ?></strong>
                      </td>
                      <td class="text-end">
                        <strong class="text-success">
                          Rp <?= number_format($overallStats['total_sales'], 0, ',', '.') ?>
                        </strong>
                      </td>
                      <td class="text-center">
                        <strong class="badge <?= $overallStats['achievement_rate'] >= 100 ? 'bg-success' : ($overallStats['achievement_rate'] >= 80 ? 'bg-warning' : 'bg-danger') ?>">
                          <?= $overallStats['achievement_rate'] ?>%
                        </strong>
                      </td>
                      <td class="text-center">
                        <strong><?= number_format($overallStats['total_deals'], 0, ',', '.') ?></strong>
                      </td>
                      <td class="text-center">
                        <strong class="badge <?= $overallStats['success_rate'] >= 80 ? 'bg-success' : ($overallStats['success_rate'] >= 60 ? 'bg-warning' : 'bg-danger') ?>">
                          <?= $overallStats['success_rate'] ?>%
                        </strong>
                      </td>
                      <td class="text-center">
                        <strong>
                          <?= count($salesActivities) > 0 ?
                            round(array_sum(array_column($salesActivities, 'experience_years')) / count($salesActivities), 1) : 0 ?> yrs
                        </strong>
                      </td>
                      <td class="text-center">
                        <strong>
                          <?=
                          $overallStats['achievement_rate'] >= 100 ? 'Exceed Target' : ($overallStats['achievement_rate'] >= 80 ? 'On Track' : 'Below Target')
                          ?>
                        </strong>
                      </td>
                      <td></td>
                    </tr>
                  </tfoot>
                </table>
              </div>

              <!-- Summary Statistics -->
              <div class="row mt-4">
                <div class="col-md-3">
                  <div class="card bg-light">
                    <div class="card-body text-center py-2">
                      <small class="text-muted">Rata-rata Sales/Person</small>
                      <h6 class="mb-0 text-success">
                        Rp <?= number_format($overallStats['avg_sales_per_person'], 0, ',', '.') ?>
                      </h6>
                    </div>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="card bg-light">
                    <div class="card-body text-center py-2">
                      <small class="text-muted">Rata-rata Deals/Person</small>
                      <h6 class="mb-0 text-primary">
                        <?= $overallStats['avg_deals_per_person'] ?>
                      </h6>
                    </div>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="card bg-light">
                    <div class="card-body text-center py-2">
                      <small class="text-muted">Top Sales Person</small>
                      <h6 class="mb-0">
                        <?= count($salesActivities) > 0 ?
                          explode(' ', $salesActivities[0]['sales_name'])[0] : '-' ?>
                      </h6>
                    </div>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="card bg-light">
                    <div class="card-body text-center py-2">
                      <small class="text-muted">Best Performing Region</small>
                      <h6 class="mb-0">
                        <?= count($territoryPerformance) > 0 ?
                          $territoryPerformance[0]['TerritoryName'] : '-' ?>
                      </h6>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Action Buttons -->
              <div class="row mt-4">
                <div class="col-12">
                  <div class="d-flex justify-content-between">
                    <div>
                      <button class="btn btn-outline-primary" onclick="exportTableToExcel('salesActivityTable', 'sales-data-<?= $selectedYear ?: 'all' ?>')">
                        <i class="bi bi-download me-1"></i> Export to Excel
                      </button>
                      <button class="btn btn-outline-success ms-2">
                        <i class="bi bi-printer me-1"></i> Print Report
                      </button>
                    </div>
                    <div>
                      <button class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i> Add New Sales
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Row 4: Top Performers -->
      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              <h5>Top 5 Sales Performers <?= $selectedYear ? " - Tahun $selectedYear" : "(2001-2004)" ?></h5>
              <p class="text-muted mb-0">Sales person dengan pencapaian terbaik</p>
            </div>
            <div class="card-body">
              <div class="row">
                <?php $topSales = array_slice($salesActivities, 0, 5); ?>
                <?php foreach ($topSales as $index => $top): ?>
                  <div class="col-md-4 mb-3">
                    <div class="card h-100 border-<?=
                                                  $top['performance_status'] == 'Exceed Target' ? 'success' : ($top['performance_status'] == 'On Track' ? 'primary' : 'warning')
                                                  ?>">
                      <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                          <div class="avatar avatar-lg me-3">
                            <div class="avatar-initial bg-<?=
                                                          $top['performance_status'] == 'Exceed Target' ? 'success' : ($top['performance_status'] == 'On Track' ? 'primary' : 'warning')
                                                          ?> rounded-circle display-6">
                              <?= strtoupper(substr(explode(' ', $top['sales_name'])[0], 0, 1)) ?>
                            </div>
                          </div>
                          <div>
                            <h6 class="mb-1"><?= $top['sales_name'] ?></h6>
                            <small class="text-muted"><?= $top['TerritoryName'] ?></small>
                          </div>
                          <div class="ms-auto">
                            <span class="badge bg-<?=
                                                  $top['performance_status'] == 'Exceed Target' ? 'success' : ($top['performance_status'] == 'On Track' ? 'primary' : 'warning')
                                                  ?>">
                              #<?= $index + 1 ?>
                            </span>
                          </div>
                        </div>
                        <div class="row text-center">
                          <div class="col-6">
                            <small class="text-muted">Sales Achievement</small>
                            <h5 class="text-success">Rp <?= number_format($top['sales_achievement'], 0, ',', '.') ?></h5>
                          </div>
                          <div class="col-6">
                            <small class="text-muted">Achievement Rate</small>
                            <h5 class="text-<?=
                                            $top['achievement_rate'] >= 100 ? 'success' : ($top['achievement_rate'] >= 80 ? 'primary' : 'warning')
                                            ?>">
                              <?= $top['achievement_rate'] ?>%
                            </h5>
                          </div>
                        </div>
                        <div class="progress mt-2" style="height: 8px;">
                          <div class="progress-bar bg-<?=
                                                      $top['achievement_rate'] >= 100 ? 'success' : ($top['achievement_rate'] >= 80 ? 'primary' : 'warning')
                                                      ?>" style="width: <?= min(100, $top['achievement_rate']) ?>%"></div>
                        </div>
                        <div class="d-flex justify-content-between mt-2">
                          <small>Target: Rp <?= number_format($top['sales_target'], 0, ',', '.') ?></small>
                          <small>Deals: <?= $top['total_deals'] ?></small>
                        </div>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
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

<script>
  // Data untuk charts
  const territoryData = <?= json_encode($territoryPerformance) ?>;
  const monthlyTrends = <?= json_encode($monthlyTrends) ?>;
  const performanceData = <?= json_encode($performanceDistribution) ?>;
  const selectedYear = <?= $selectedYear ? $selectedYear : 'null' ?>;
  const overallStats = <?= json_encode($overallStats) ?>;

  /* ==============================
     BAR CHART: Sales per Wilayah
     ============================== */
  if (territoryData.length > 0) {
    const ctx1 = document.getElementById('territoryBarChart').getContext('2d');
    const territoryBarChart = new Chart(ctx1, {
      type: 'bar',
      data: {
        labels: territoryData.map(t => t.TerritoryName),
        datasets: [{
          label: 'Total Sales (Rp)',
          data: territoryData.map(t => t.total_sales),
          backgroundColor: [
            'rgba(78, 115, 223, 0.8)',
            'rgba(40, 167, 69, 0.8)',
            'rgba(255, 193, 7, 0.8)',
            'rgba(220, 53, 69, 0.8)',
            'rgba(23, 162, 184, 0.8)',
            'rgba(108, 117, 125, 0.8)'
          ],
          borderColor: [
            'rgba(78, 115, 223, 1)',
            'rgba(40, 167, 69, 1)',
            'rgba(255, 193, 7, 1)',
            'rgba(220, 53, 69, 1)',
            'rgba(23, 162, 184, 1)',
            'rgba(108, 117, 125, 1)'
          ],
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            display: false
          },
          tooltip: {
            callbacks: {
              label: function(context) {
                const territory = territoryData[context.dataIndex];
                return [
                  `Wilayah: ${territory.TerritoryName}`,
                  `Total Sales: Rp ${territory.total_sales.toLocaleString('id-ID')}`,
                  `Avg/Sales: Rp ${Math.round(territory.avg_sales_per_person).toLocaleString('id-ID')}`,
                  `Sales Persons: ${territory.total_sales_persons}`,
                  `Success Rate: ${territory.success_rate}%`
                ];
              }
            }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback: function(value) {
                if (value >= 1000000) {
                  return 'Rp ' + (value / 1000000).toFixed(1) + 'M';
                } else if (value >= 1000) {
                  return 'Rp ' + (value / 1000).toFixed(0) + 'K';
                }
                return 'Rp ' + value;
              }
            }
          }
        }
      }
    });
  }

  /* ==============================
     PIE CHART: Performance Distribution
     ============================== */
  if (performanceData.length > 0) {
    const ctx2 = document.getElementById('performancePieChart').getContext('2d');
    const performancePieChart = new Chart(ctx2, {
      type: 'doughnut',
      data: {
        labels: performanceData.map(p => p.category),
        datasets: [{
          data: performanceData.map(p => p.count),
          backgroundColor: performanceData.map(p => p.color),
          borderColor: '#fff',
          borderWidth: 2
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            display: false
          },
          tooltip: {
            callbacks: {
              label: function(context) {
                const label = context.label;
                const value = context.raw;
                const total = performanceData.reduce((sum, p) => sum + p.count, 0);
                const percentage = Math.round((value / total) * 100);
                return `${label}: ${value} persons (${percentage}%)`;
              }
            }
          }
        },
        cutout: '70%'
      }
    });
  }

  /* ==============================
     LINE CHART: Tren Bulanan
     ============================== */
  if (monthlyTrends.length > 0) {
    const ctx3 = document.getElementById('monthlyLineChart').getContext('2d');
    const monthlyLineChart = new Chart(ctx3, {
      type: 'line',
      data: {
        labels: monthlyTrends.map(m => m.month_name),
        datasets: [{
          label: 'Total Sales (Rp)',
          data: monthlyTrends.map(m => m.total_sales),
          borderColor: 'rgb(78, 115, 223)',
          backgroundColor: 'rgba(78, 115, 223, 0.1)',
          tension: 0.4,
          fill: true,
          pointBackgroundColor: 'rgb(78, 115, 223)',
          pointBorderColor: '#fff',
          pointBorderWidth: 2,
          pointRadius: 5
        }, {
          label: 'Success Rate (%)',
          data: monthlyTrends.map(m => m.success_rate),
          borderColor: 'rgb(40, 167, 69)',
          backgroundColor: 'rgba(40, 167, 69, 0.1)',
          tension: 0.4,
          fill: false,
          yAxisID: 'y1',
          pointStyle: 'rect'
        }]
      },
      options: {
        responsive: true,
        interaction: {
          mode: 'index',
          intersect: false
        },
        plugins: {
          tooltip: {
            callbacks: {
              label: function(context) {
                let label = context.dataset.label || '';
                if (label) {
                  label += ': ';
                }
                if (context.dataset.label.includes('Sales')) {
                  label += 'Rp ' + context.raw.toLocaleString('id-ID');
                } else {
                  label += context.raw + '%';
                }
                return label;
              }
            }
          }
        },
        scales: {
          y: {
            type: 'linear',
            display: true,
            position: 'left',
            title: {
              display: true,
              text: 'Total Sales (Rp)'
            },
            ticks: {
              callback: function(value) {
                if (value >= 1000000) {
                  return 'Rp ' + (value / 1000000).toFixed(0) + 'M';
                }
                return 'Rp ' + value;
              }
            }
          },
          y1: {
            type: 'linear',
            display: true,
            position: 'right',
            title: {
              display: true,
              text: 'Success Rate (%)'
            },
            min: 0,
            max: 100,
            grid: {
              drawOnChartArea: false
            }
          }
        }
      }
    });
  }

  /* ==============================
     FUNGSI TAMBAHAN
     ============================== */
  function showSalesDetail(sales) {
    const modalHtml = `
            <div class="modal fade" id="salesDetailModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Sales Person Detail</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-4 text-center">
                                    <div class="avatar avatar-xxl mb-3">
                                        <div class="avatar-initial bg-primary rounded-circle display-4">
                                            ${sales.sales_name.charAt(0)}
                                        </div>
                                    </div>
                                    <h4>${sales.sales_name}</h4>
                                    <p class="text-muted">${sales.TerritoryName}</p>
                                    <div class="badge ${sales.performance_status == 'Exceed Target' ? 'bg-success' : 
                                                         sales.performance_status == 'On Track' ? 'bg-primary' : 'bg-danger'}">
                                        ${sales.performance_status}
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <div class="row">
                                        <div class="col-6 mb-3">
                                            <small class="text-muted">Sales Target</small>
                                            <h4 class="text-primary">Rp ${sales.sales_target.toLocaleString('id-ID')}</h4>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <small class="text-muted">Sales Achievement</small>
                                            <h4 class="text-success">Rp ${sales.sales_achievement.toLocaleString('id-ID')}</h4>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <small class="text-muted">Achievement Rate</small>
                                            <h4 class="${sales.achievement_rate >= 100 ? 'text-success' : 
                                                        sales.achievement_rate >= 80 ? 'text-primary' : 'text-danger'}">
                                                ${sales.achievement_rate}%
                                            </h4>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <small class="text-muted">Success Rate</small>
                                            <h4 class="${sales.success_rate_percentage >= 80 ? 'text-success' : 
                                                        sales.success_rate_percentage >= 60 ? 'text-warning' : 'text-danger'}">
                                                ${sales.success_rate_percentage}%
                                            </h4>
                                        </div>
                                    </div>
                                    
                                    <hr>
                                    
                                    <div class="row">
                                        <div class="col-6">
                                            <small class="text-muted">Total Deals</small>
                                            <h5>${sales.total_deals}</h5>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted">Successful Deals</small>
                                            <h5>${sales.successful_deals}</h5>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted">Experience</small>
                                            <h5>${sales.experience_years} years</h5>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted">Join Date</small>
                                            <h5>${new Date(sales.join_date).toLocaleDateString('id-ID')}</h5>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-primary">
                                <i class="bi bi-download"></i> Export Report
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

    document.body.insertAdjacentHTML('beforeend', modalHtml);

    const modal = new bootstrap.Modal(document.getElementById('salesDetailModal'));
    modal.show();

    document.getElementById('salesDetailModal').addEventListener('hidden.bs.modal', function() {
      this.remove();
    });
  }

  function exportTableToExcel(tableId, filename = 'sales-data') {
    const table = document.getElementById(tableId);
    const rows = table.querySelectorAll('tr');
    let csv = [];

    for (let i = 0; i < rows.length; i++) {
      const row = [],
        cols = rows[i].querySelectorAll('td, th');

      for (let j = 0; j < cols.length; j++) {
        let data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, '').replace(/(\s\s)/gm, ' ');
        data = data.replace(/"/g, '""');
        row.push('"' + data + '"');
      }

      csv.push(row.join(','));
    }

    const csvString = csv.join('\n');
    const blob = new Blob([csvString], {
      type: 'text/csv;charset=utf-8;'
    });
    const link = document.createElement('a');

    if (navigator.msSaveBlob) {
      navigator.msSaveBlob(blob, filename + '.csv');
    } else {
      link.href = URL.createObjectURL(blob);
      link.download = filename + '.csv';
      link.style.display = 'none';
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    }
  }
</script>

<style>
  .filter-form {
    min-width: 250px;
  }

  .stat-card {
    border: none;
    border-radius: 10px;
    transition: transform 0.3s;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  }

  .stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
  }

  .avatar {
    display: inline-flex;
    align-items: center;
    justify-content: center;
  }

  .avatar-initial {
    color: white;
    font-weight: bold;
  }

  .avatar-sm {
    width: 32px;
    height: 32px;
    font-size: 14px;
  }

  .avatar-lg {
    width: 60px;
    height: 60px;
    font-size: 24px;
  }

  .avatar-xxl {
    width: 100px;
    height: 100px;
    font-size: 40px;
  }

  .table th {
    background-color: #f8f9fa;
    font-weight: 600;
  }

  .progress {
    background-color: #e9ecef;
  }

  .badge {
    font-size: 0.85em;
    padding: 4px 8px;
  }

  .card {
    border: none;
    box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
    margin-bottom: 20px;
  }

  .card-header {
    background-color: #fff;
    border-bottom: 1px solid #e3e6f0;
    padding: 1rem 1.25rem;
  }

  .table-hover tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.05);
  }
</style>
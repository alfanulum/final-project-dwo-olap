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
   CUSTOMER GEOGRAFIS (COUNTRY)
   ============================== */
// Untuk simulasi, kita akan buat data berbeda per tahun
$sql = "
SELECT 
  dg.TerritoryName AS country,
  COUNT(DISTINCT fs.CustomerKey) AS total_customers,
  SUM(fs.LineTotal) AS total_sales
FROM factsales fs
JOIN dimgeography dg ON fs.GeographyKey = dg.GeographyKey
";

$params = [];

// Jika ada tahun yang dipilih, kita akan mengubah query untuk mensimulasikan data per tahun
// Karena tidak ada kolom tanggal, kita akan menggunakan CustomerKey untuk simulasi
if ($selectedYear !== null) {
    // Simulasi: kita akan filter berdasarkan modulo dari CustomerKey untuk mendapatkan distribusi berbeda per tahun
    // Ini hanya untuk demonstrasi - dalam real case, Anda harus menyesuaikan dengan logika bisnis
    $sql .= " WHERE MOD(fs.CustomerKey, 4) = ?";
    $params[] = $selectedYear % 4; // 0 untuk 2004, 1 untuk 2001, 2 untuk 2002, 3 untuk 2003
}

$sql .= "
GROUP BY dg.TerritoryName
ORDER BY total_customers DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ==============================
   DATA GENDER (TAMBAHAN)
   ============================== */
$genderSql = "
SELECT 
    dg.TerritoryName AS country,
    dc.Gender,
    COUNT(DISTINCT fs.CustomerKey) AS total_customers,
    SUM(fs.LineTotal) AS total_sales
FROM factsales fs
JOIN dimgeography dg ON fs.GeographyKey = dg.GeographyKey
JOIN dimcustomer dc ON fs.CustomerKey = dc.CustomerKey
";

$genderParams = [];
if ($selectedYear !== null) {
    $genderSql .= " WHERE MOD(fs.CustomerKey, 4) = ?";
    $genderParams[] = $selectedYear % 4;
}

$genderSql .= " GROUP BY dg.TerritoryName, dc.Gender ORDER BY country, total_customers DESC";

$genderStmt = $conn->prepare($genderSql);
$genderStmt->execute($genderParams);
$genderData = $genderStmt->fetchAll(PDO::FETCH_ASSOC);

// Format data gender per negara
$genderByCountry = [];
foreach ($genderData as $row) {
    if (!isset($genderByCountry[$row['country']])) {
        $genderByCountry[$row['country']] = [
            'country' => $row['country'],
            'Male' => 0,
            'Female' => 0,
            'total_customers' => 0,
            'total_sales' => 0
        ];
    }
    $genderByCountry[$row['country']][$row['Gender']] = $row['total_customers'];
    $genderByCountry[$row['country']]['total_customers'] += $row['total_customers'];
    $genderByCountry[$row['country']]['total_sales'] += $row['total_sales'];
}

/* Koordinat negara (hardcode – cukup untuk OLAP) */
$coords = [
    'United States' => [37.1, -95.7],
    'Australia' => [-25.3, 133.8],
    'Canada' => [56.1, -106.3],
    'Germany' => [51.1, 10.4],
    'France' => [46.2, 2.2],
    'United Kingdom' => [55.3, -3.4]
];

// Untuk mensimulasikan perbedaan data per tahun, kita akan adjust jumlah pelanggan
$data = [];
foreach ($rows as $r) {
    if (isset($coords[$r['country']])) {
        $baseCustomers = (int)$r['total_customers'];
        $baseSales = (float)$r['total_sales'];
        
        // Adjust berdasarkan tahun untuk simulasi pertumbuhan
        if ($selectedYear !== null) {
            // Contoh: pertumbuhan 20% per tahun dari baseline (2004)
            $yearMultiplier = [
                2001 => 0.7, // 70% dari baseline
                2002 => 0.8, // 80% dari baseline
                2003 => 0.9, // 90% dari baseline
                2004 => 1.0  // 100% baseline
            ];
            
            $multiplier = $yearMultiplier[$selectedYear] ?? 1.0;
            $adjustedCustomers = round($baseCustomers * $multiplier);
            $adjustedSales = $baseSales * $multiplier;
        } else {
            // Jika semua tahun, gunakan nilai total
            $adjustedCustomers = $baseCustomers;
            $adjustedSales = $baseSales;
        }
        
        $data[] = [
            'country' => $r['country'],
            'customers' => $adjustedCustomers,
            'sales' => $adjustedSales,
            'lat' => $coords[$r['country']][0],
            'lng' => $coords[$r['country']][1],
            'year' => $selectedYear
        ];
    }
}
?>

<?php include 'layouts/head.php'; ?>

<!-- Leaflet -->
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">

<div class="page-body-wrapper">
    <?php include 'layouts/sidebar.php'; ?>

    <div class="page-body">
        <div class="container-fluid">

            <!-- Hero Section dengan Filter -->
            <div class="page-hero">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div>
                        <h3>Customer Geografis</h3>
                        <p>Persebaran pelanggan dan kontribusi penjualan berdasarkan negara (2001-2004)</p>
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

            <!-- Map Card -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4>
                                Customer Geografis (Country Level) 
                                <?php if ($selectedYear): ?>
                                    <span class="badge bg-primary">Tahun <?= $selectedYear ?></span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">2001-2004 (Semua Tahun)</span>
                                <?php endif; ?>
                            </h4>
                            <p class="text-muted mb-0">
                                Visualisasi persebaran pelanggan menggunakan pendekatan OLAP
                                <?php if ($selectedYear): ?>
                                    <br><small>Menampilkan data untuk tahun <?= $selectedYear ?> (simulasi pertumbuhan)</small>
                                <?php else: ?>
                                    <br><small>Menampilkan data agregat semua tahun (2001-2004)</small>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="card-body">
                            <!-- Info Simulasi -->
                            <?php if ($selectedYear): ?>
                                <div class="alert alert-info mb-3">
                                    <i class="bi bi-info-circle me-2"></i>
                                    <strong>Catatan:</strong> Data ditampilkan berdasarkan simulasi pertumbuhan pelanggan per tahun. Baseline: Tahun 2004 = 100%, dengan penurunan 10% per tahun sebelumnya.
                                </div>
                            <?php endif; ?>
                            
                            <div id="map" style="height: 500px; border-radius: 8px;"></div>
                            
                            <!-- Ringkasan Statistik -->
                            <div class="row mt-4">
                                <div class="col-md-3">
                                    <div class="card stat-card">
                                        <div class="card-body">
                                            <h6 class="card-title">Total Negara</h6>
                                            <h3 class="text-primary"><?= count($data) ?></h3>
                                            <small class="text-muted">Negara dengan transaksi</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card stat-card">
                                        <div class="card-body">
                                            <h6 class="card-title">Total Pelanggan</h6>
                                            <h3 class="text-success">
                                                <?= array_sum(array_column($data, 'customers')) ?>
                                            </h3>
                                            <small class="text-muted">Pelanggan unik</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card stat-card">
                                        <div class="card-body">
                                            <h6 class="card-title">Total Penjualan</h6>
                                            <h3 class="text-warning">
                                                Rp <?= number_format(array_sum(array_column($data, 'sales')), 0, ',', '.') ?>
                                            </h3>
                                            <small class="text-muted">Nilai penjualan</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card stat-card">
                                        <div class="card-body">
                                            <h6 class="card-title">Rata-rata per Customer</h6>
                                            <h3 class="text-info">
                                                Rp <?= 
                                                    count($data) > 0 && array_sum(array_column($data, 'customers')) > 0 ?
                                                    number_format(array_sum(array_column($data, 'sales')) / array_sum(array_column($data, 'customers')), 0, ',', '.') :
                                                    '0'
                                                ?>
                                            </h3>
                                            <small class="text-muted">Value per pelanggan</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Tabel Data Customer per Negara -->
                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5>Detail Data per Negara <?= $selectedYear ? " - Tahun $selectedYear" : "(2001-2004)" ?></h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-hover">
                                                    <thead>
                                                        <tr>
                                                            <th>Negara</th>
                                                            <th>Jumlah Pelanggan</th>
                                                            <th>Total Penjualan</th>
                                                            <th>Rata-rata per Pelanggan</th>
                                                            <th>% dari Total</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php 
                                                        $totalCustomers = array_sum(array_column($data, 'customers'));
                                                        $totalSales = array_sum(array_column($data, 'sales'));
                                                        ?>
                                                        <?php if (count($data) > 0): ?>
                                                            <?php foreach ($data as $item): ?>
                                                                <tr>
                                                                    <td><strong><?= $item['country'] ?></strong></td>
                                                                    <td><?= number_format($item['customers'], 0, ',', '.') ?></td>
                                                                    <td>Rp <?= number_format($item['sales'], 0, ',', '.') ?></td>
                                                                    <td>Rp <?= $item['customers'] > 0 ? number_format($item['sales'] / $item['customers'], 0, ',', '.') : 0 ?></td>
                                                                    <td>
                                                                        <?php if ($totalCustomers > 0): ?>
                                                                            <?= number_format(($item['customers'] / $totalCustomers) * 100, 1) ?>%
                                                                        <?php else: ?>
                                                                            0%
                                                                        <?php endif; ?>
                                                                    </td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                            <!-- Total Row -->
                                                            <tr class="table-active">
                                                                <td><strong>TOTAL</strong></td>
                                                                <td><strong><?= number_format($totalCustomers, 0, ',', '.') ?></strong></td>
                                                                <td><strong>Rp <?= number_format($totalSales, 0, ',', '.') ?></strong></td>
                                                                <td><strong>Rp <?= $totalCustomers > 0 ? number_format($totalSales / $totalCustomers, 0, ',', '.') : 0 ?></strong></td>
                                                                <td><strong>100%</strong></td>
                                                            </tr>
                                                        <?php else: ?>
                                                            <tr>
                                                                <td colspan="5" class="text-center">Tidak ada data yang ditemukan</td>
                                                            </tr>
                                                        <?php endif; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Tabel Data Gender per Negara (TAMBAHAN) -->
                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5>Distribusi Gender per Negara <?= $selectedYear ? " - Tahun $selectedYear" : "(2001-2004)" ?></h5>
                                            <p class="text-muted mb-0">Analisis pelanggan berdasarkan jenis kelamin</p>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-hover">
                                                    <thead>
                                                        <tr>
                                                            <th>Negara</th>
                                                            <th>Total Pelanggan</th>
                                                            <th>Male</th>
                                                            <th>Female</th>
                                                            <th>% Male</th>
                                                            <th>% Female</th>
                                                            <th>Gender Ratio</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php 
                                                        $totalGenderCustomers = 0;
                                                        $totalMale = 0;
                                                        $totalFemale = 0;
                                                        
                                                        foreach ($genderByCountry as $countryData): 
                                                            $countryTotal = $countryData['total_customers'];
                                                            $maleCount = $countryData['Male'];
                                                            $femaleCount = $countryData['Female'];
                                                            
                                                            $totalGenderCustomers += $countryTotal;
                                                            $totalMale += $maleCount;
                                                            $totalFemale += $femaleCount;
                                                            
                                                            $malePercentage = $countryTotal > 0 ? round(($maleCount / $countryTotal) * 100, 1) : 0;
                                                            $femalePercentage = $countryTotal > 0 ? round(($femaleCount / $countryTotal) * 100, 1) : 0;
                                                            $genderRatio = $femaleCount > 0 ? round($maleCount / $femaleCount, 1) : 0;
                                                        ?>
                                                            <tr>
                                                                <td><strong><?= $countryData['country'] ?></strong></td>
                                                                <td><?= number_format($countryTotal, 0, ',', '.') ?></td>
                                                                <td>
                                                                    <span class="badge bg-primary"><?= number_format($maleCount, 0, ',', '.') ?></span>
                                                                    <small class="text-muted">(<?= $malePercentage ?>%)</small>
                                                                </td>
                                                                <td>
                                                                    <span class="badge bg-danger"><?= number_format($femaleCount, 0, ',', '.') ?></span>
                                                                    <small class="text-muted">(<?= $femalePercentage ?>%)</small>
                                                                </td>
                                                                <td>
                                                                    <div class="progress" style="height: 8px;">
                                                                        <div class="progress-bar bg-primary" style="width: <?= $malePercentage ?>%"></div>
                                                                    </div>
                                                                    <small><?= $malePercentage ?>%</small>
                                                                </td>
                                                                <td>
                                                                    <div class="progress" style="height: 8px;">
                                                                        <div class="progress-bar bg-danger" style="width: <?= $femalePercentage ?>%"></div>
                                                                    </div>
                                                                    <small><?= $femalePercentage ?>%</small>
                                                                </td>
                                                                <td>
                                                                    <?php if ($genderRatio > 0): ?>
                                                                        <span class="badge <?= $genderRatio > 1.2 ? 'bg-primary' : ($genderRatio < 0.8 ? 'bg-danger' : 'bg-warning') ?>">
                                                                            <?= $genderRatio ?>:1
                                                                        </span>
                                                                        <small class="text-muted">
                                                                            (M:F)
                                                                        </small>
                                                                    <?php else: ?>
                                                                        <span class="badge bg-secondary">N/A</span>
                                                                    <?php endif; ?>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                        
                                                        <!-- Total Row -->
                                                        <?php 
                                                        $totalMalePercentage = $totalGenderCustomers > 0 ? round(($totalMale / $totalGenderCustomers) * 100, 1) : 0;
                                                        $totalFemalePercentage = $totalGenderCustomers > 0 ? round(($totalFemale / $totalGenderCustomers) * 100, 1) : 0;
                                                        $totalGenderRatio = $totalFemale > 0 ? round($totalMale / $totalFemale, 1) : 0;
                                                        ?>
                                                        <tr class="table-active">
                                                            <td><strong>TOTAL</strong></td>
                                                            <td><strong><?= number_format($totalGenderCustomers, 0, ',', '.') ?></strong></td>
                                                            <td>
                                                                <strong>
                                                                    <span class="badge bg-primary"><?= number_format($totalMale, 0, ',', '.') ?></span>
                                                                    <small>(<?= $totalMalePercentage ?>%)</small>
                                                                </strong>
                                                            </td>
                                                            <td>
                                                                <strong>
                                                                    <span class="badge bg-danger"><?= number_format($totalFemale, 0, ',', '.') ?></span>
                                                                    <small>(<?= $totalFemalePercentage ?>%)</small>
                                                                </strong>
                                                            </td>
                                                            <td>
                                                                <div class="progress" style="height: 8px;">
                                                                    <div class="progress-bar bg-primary" style="width: <?= $totalMalePercentage ?>%"></div>
                                                                </div>
                                                                <strong><?= $totalMalePercentage ?>%</strong>
                                                            </td>
                                                            <td>
                                                                <div class="progress" style="height: 8px;">
                                                                    <div class="progress-bar bg-danger" style="width: <?= $totalFemalePercentage ?>%"></div>
                                                                </div>
                                                                <strong><?= $totalFemalePercentage ?>%</strong>
                                                            </td>
                                                            <td>
                                                                <strong>
                                                                    <span class="badge <?= $totalGenderRatio > 1.2 ? 'bg-primary' : ($totalGenderRatio < 0.8 ? 'bg-danger' : 'bg-warning') ?>">
                                                                        <?= $totalGenderRatio ?>:1
                                                                    </span>
                                                                    <small class="text-muted">(M:F)</small>
                                                                </strong>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                            
                                            <!-- Ringkasan Gender -->
                                            <div class="row mt-4">
                                                <div class="col-md-6">
                                                    <div class="card">
                                                        <div class="card-body text-center">
                                                            <h6>Distribusi Gender Global</h6>
                                                            <div class="d-flex justify-content-center align-items-center mt-3">
                                                                <div class="text-center mx-4">
                                                                    <div class="display-4 text-primary"><?= $totalMalePercentage ?>%</div>
                                                                    <small class="text-muted">Male</small>
                                                                </div>
                                                                <div class="text-center mx-4">
                                                                    <div class="display-4 text-danger"><?= $totalFemalePercentage ?>%</div>
                                                                    <small class="text-muted">Female</small>
                                                                </div>
                                                            </div>
                                                            <div class="progress mt-3" style="height: 20px;">
                                                                <div class="progress-bar bg-primary" style="width: <?= $totalMalePercentage ?>%">
                                                                    Male (<?= number_format($totalMale, 0, ',', '.') ?>)
                                                                </div>
                                                                <div class="progress-bar bg-danger" style="width: <?= $totalFemalePercentage ?>%">
                                                                    Female (<?= number_format($totalFemale, 0, ',', '.') ?>)
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="card">
                                                        <div class="card-body text-center">
                                                            <h6>Gender Ratio Analysis</h6>
                                                            <div class="mt-3">
                                                                <?php if ($totalGenderRatio > 1.2): ?>
                                                                    <div class="alert alert-primary">
                                                                        <i class="bi bi-gender-male fs-4"></i>
                                                                        <h5>Male Dominant</h5>
                                                                        <p class="mb-0">Lebih banyak pelanggan pria dibanding wanita</p>
                                                                    </div>
                                                                <?php elseif ($totalGenderRatio < 0.8): ?>
                                                                    <div class="alert alert-danger">
                                                                        <i class="bi bi-gender-female fs-4"></i>
                                                                        <h5>Female Dominant</h5>
                                                                        <p class="mb-0">Lebih banyak pelanggan wanita dibanding pria</p>
                                                                    </div>
                                                                <?php else: ?>
                                                                    <div class="alert alert-warning">
                                                                        <i class="bi bi-gender-ambiguous fs-4"></i>
                                                                        <h5>Balanced</h5>
                                                                        <p class="mb-0">Distribusi gender yang seimbang</p>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="mt-2">
                                                                <small class="text-muted">
                                                                    Ratio: <?= $totalGenderRatio ?>:1 (Male:Female)<br>
                                                                    Total Pelanggan: <?= number_format($totalGenderCustomers, 0, ',', '.') ?>
                                                                </small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
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

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<script>
    const geoData = <?= json_encode($data); ?>;
    const selectedYear = <?= $selectedYear ? $selectedYear : 'null' ?>;
    
    // Inisialisasi map
    const map = L.map('map').setView([20, 0], 2);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap'
    }).addTo(map);

    // Warna berbeda berdasarkan jumlah pelanggan
    function getColor(customerCount) {
        if (customerCount > 1000) return '#e31a1c';
        if (customerCount > 500) return '#fd8d3c';
        if (customerCount > 200) return '#fecc5c';
        if (customerCount > 100) return '#ffffb2';
        return '#f0f0f0';
    }

    // Fungsi untuk format angka
    function formatNumber(num) {
        return num.toLocaleString('id-ID');
    }

    // Fungsi untuk format mata uang
    function formatCurrency(num) {
        return 'Rp ' + num.toLocaleString('id-ID');
    }

    // Tambahkan marker untuk setiap negara
    geoData.forEach(d => {
        const color = getColor(d.customers);
        // Ukuran marker berdasarkan jumlah pelanggan
        const radius = Math.max(10, Math.sqrt(d.customers) * 1.5);
        
        // Buat custom icon dengan jumlah pelanggan
        const customerIcon = L.divIcon({
            html: `
                <div style="
                    background-color: ${color};
                    width: ${radius * 2}px;
                    height: ${radius * 2}px;
                    border-radius: 50%;
                    border: 2px solid #333;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: #333;
                    font-weight: bold;
                    font-size: ${Math.max(10, radius/2)}px;
                    box-shadow: 0 2px 5px rgba(0,0,0,0.3);
                ">
                    ${d.customers > 999 ? (d.customers/1000).toFixed(0) + 'k' : d.customers}
                </div>
            `,
            className: 'customer-marker',
            iconSize: [radius * 2, radius * 2],
            iconAnchor: [radius, radius]
        });
        
        // Buat marker dengan custom icon
        const marker = L.marker([d.lat, d.lng], { icon: customerIcon })
            .bindTooltip(`
                <div style="font-size:12px; min-width:200px">
                    <b>${d.country}</b><br>
                    <hr style="margin:3px 0">
                    👥 <b>${formatNumber(d.customers)}</b> pelanggan<br>
                    💰 <b>${formatCurrency(d.sales)}</b><br>
                    📅 <small>Tahun ${selectedYear || '2001-2004'}</small>
                </div>
            `, {
                permanent: false,
                direction: 'top',
                className: 'custom-tooltip'
            })
            .bindPopup(`
                <div style="font-size:13px; min-width:200px">
                    <h6 style="margin:0">${d.country}</h6>
                    <hr style="margin:5px 0">
                    <div class="row">
                        <div class="col-6">
                            <small>Pelanggan:</small><br>
                            <b style="font-size:16px">${formatNumber(d.customers)}</b>
                        </div>
                        <div class="col-6">
                            <small>Penjualan:</small><br>
                            <b style="font-size:16px">${formatCurrency(d.sales)}</b>
                        </div>
                    </div>
                    <hr style="margin:8px 0">
                    <div class="row">
                        <div class="col-12">
                            <small>Rata-rata per pelanggan:</small><br>
                            <b>${formatCurrency(d.customers > 0 ? d.sales / d.customers : 0)}</b>
                        </div>
                    </div>
                    <hr style="margin:8px 0">
                    <small><i>Tahun ${selectedYear || '2001-2004'}</i></small>
                </div>
            `)
            .addTo(map);
    });
    
    // Tambahkan legend
    const legend = L.control({ position: 'bottomright' });
    
    legend.onAdd = function (map) {
        const div = L.DomUtil.create('div', 'info legend');
        const grades = [0, 100, 200, 500, 1000];
        const labels = ['<strong>Jumlah Pelanggan</strong><br>'];
        
        for (let i = 0; i < grades.length; i++) {
            div.innerHTML +=
                '<div style="display:flex; align-items:center; margin-bottom:3px;">' +
                '<div style="background:' + getColor(grades[i] + 1) + '; width:15px; height:15px; border-radius:50%; margin-right:5px; border:1px solid #333;"></div>' +
                '<span>' + grades[i] + (grades[i + 1] ? '&ndash;' + grades[i + 1] : '+') + '</span>' +
                '</div>';
        }
        
        div.innerHTML += '<div style="margin-top:8px; font-size:11px; color:#666;">Ukuran lingkaran<br>sesuai jumlah pelanggan</div>';
        
        div.style.padding = '10px';
        div.style.background = 'white';
        div.style.borderRadius = '5px';
        div.style.boxShadow = '0 0 15px rgba(0,0,0,0.2)';
        div.style.fontSize = '12px';
        div.style.lineHeight = '1.4';
        
        return div;
    };
    
    legend.addTo(map);
    
    // Tambahkan control tahun jika ada filter tahun
    if (selectedYear) {
        const yearControl = L.control({ position: 'topleft' });
        
        yearControl.onAdd = function (map) {
            const div = L.DomUtil.create('div', 'year-control');
            div.innerHTML = `
                <div style="
                    background: #007bff;
                    color: white;
                    padding: 8px 12px;
                    border-radius: 4px;
                    font-weight: bold;
                    font-size: 14px;
                    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
                ">
                    <i class="bi bi-calendar" style="margin-right:5px"></i>
                    Tahun ${selectedYear}
                </div>
            `;
            return div;
        };
        
        yearControl.addTo(map);
    }
</script>

<style>
    .filter-form {
        min-width: 250px;
    }
    .stat-card {
        border: none;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        transition: transform 0.2s;
        border-radius: 8px;
    }
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    .info.legend {
        line-height: 18px;
        font-size: 12px;
    }
    .table th {
        background-color: #f8f9fa;
        font-weight: 600;
    }
    .table-active {
        background-color: rgba(0,123,255,0.1) !important;
    }
    .customer-marker {
        background: transparent !important;
        border: none !important;
    }
    .custom-tooltip {
        font-weight: normal !important;
    }
    .progress {
        background-color: #e9ecef;
    }
    .badge {
        font-size: 0.85em;
        padding: 4px 8px;
    }
</style>
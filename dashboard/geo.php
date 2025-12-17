<?php
require_once 'config/database.php';

$db = new Database();
$conn = $db->getConnection();

/* ==============================
   CUSTOMER GEOGRAFIS (COUNTRY)
   ============================== */
$sql = "
SELECT 
  dg.TerritoryName AS country,
  COUNT(DISTINCT fs.CustomerKey) AS total_customers,
  SUM(fs.LineTotal) AS total_sales
FROM factsales fs
JOIN dimgeography dg ON fs.GeographyKey = dg.GeographyKey
GROUP BY dg.TerritoryName
ORDER BY total_customers DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Koordinat negara (hardcode – cukup untuk OLAP) */
$coords = [
  'United States' => [37.1, -95.7],
  'Australia' => [-25.3, 133.8],
  'Canada' => [56.1, -106.3],
  'Germany' => [51.1, 10.4],
  'France' => [46.2, 2.2],
  'United Kingdom' => [55.3, -3.4]
];

$data = [];
foreach ($rows as $r) {
  if (isset($coords[$r['country']])) {
    $data[] = [
      'country' => $r['country'],
      'customers' => (int)$r['total_customers'],
      'sales' => (float)$r['total_sales'],
      'lat' => $coords[$r['country']][0],
      'lng' => $coords[$r['country']][1]
    ];
  }
}
?>

<?php include 'layouts/head.php'; ?>

<!-- Leaflet -->
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css">

<div class="page-body-wrapper">
  <?php include 'layouts/sidebar.php'; ?>

  <div class="page-body">
    <div class="container-fluid">

      <!-- Hero Section -->
      <div class="page-hero">
        <h3>Customer Geografis</h3>
        <p>Persebaran pelanggan dan kontribusi penjualan berdasarkan negara</p>
      </div>

      <!-- Map Card -->
      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              <h4>Customer Geografis (Country Level)</h4>
              <p class="text-muted mb-0">
                Visualisasi persebaran pelanggan menggunakan pendekatan OLAP
              </p>
            </div>
            <div class="card-body">
              <div id="map" style="height: 500px; border-radius: 8px;"></div>
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

  const map = L.map('map').setView([20, 0], 2);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap'
  }).addTo(map);

  geoData.forEach(d => {
    L.circleMarker([d.lat, d.lng], {opacity: 0})
    .bindTooltip(`
      <div style="font-size:12px">
        <b>${d.country}</b><br>
        👥 ${d.customers.toLocaleString('id-ID')} customers<br>
        💰 Rp ${d.sales.toLocaleString('id-ID')}
      </div>
    `, {
      permanent: true,
      direction: 'top',
      offset: [0, -5],
      opacity: 0.9
    })
    .addTo(map);
  });
</script>


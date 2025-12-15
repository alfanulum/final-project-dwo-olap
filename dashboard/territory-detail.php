<?php
include 'layouts/head.php';
include 'config/database.php';

$db = new Database();
$conn = $db->getConnection();

// Get parameters
$territory = $_GET['territory'] ?? '';
$year = $_GET['year'] ?? date('Y') - 1;

// Get territory detail data
$query = "
    SELECT 
        p.Category,
        p.Subcategory,
        p.ProductName,
        SUM(f.OrderQty) as TotalQty,
        SUM(f.LineTotal) as TotalSales,
        AVG(f.UnitPrice) as AvgPrice,
        COUNT(DISTINCT f.CustomerKey) as UniqueCustomers,
        COUNT(DISTINCT f.SalesKey) as TransactionCount
    FROM factsales f
    JOIN dimproduct p ON f.ProductKey = p.ProductKey
    JOIN dimgeography g ON f.GeographyKey = g.GeographyKey
    JOIN dimtime t ON f.TimeKey = t.TimeKey
    WHERE g.TerritoryName = :territory
    AND t.Year = :year
    GROUP BY p.Category, p.Subcategory, p.ProductName
    ORDER BY TotalSales DESC
";

$stmt = $conn->prepare($query);
$stmt->bindParam(':territory', $territory);
$stmt->bindParam(':year', $year);
$stmt->execute();
$territory_detail = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="page-body-wrapper">
  <?php include 'layouts/sidebar.php'; ?>

  <div class="page-body">
    <div class="container-fluid">
      <div class="page-title">
        <div class="row">
          <div class="col-6">
            <h4>Territory Detail: <?php echo htmlspecialchars($territory); ?> (<?php echo $year; ?>)</h4>
          </div>
          <div class="col-6">
            <ol class="breadcrumb">
              <li class="breadcrumb-item">
                <a href="index.php">
                  <svg class="stroke-icon">
                    <use href="assets/svg/icon-sprite.svg#stroke-home"></use>
                  </svg>
                </a>
              </li>
              <li class="breadcrumb-item">
                <a href="dashboard.php">Dashboard</a>
              </li>
              <li class="breadcrumb-item active">Territory Detail</li>
            </ol>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              <h5>Product Performance in <?php echo htmlspecialchars($territory); ?></h5>
              <button class="btn btn-primary" onclick="window.history.back()">
                Back to Dashboard
              </button>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-hover">
                  <thead>
                    <tr>
                      <th>Category</th>
                      <th>Subcategory</th>
                      <th>Product</th>
                      <th class="text-end">Quantity</th>
                      <th class="text-end">Total Sales</th>
                      <th class="text-end">Avg Price</th>
                      <th class="text-end">Customers</th>
                      <th class="text-end">Transactions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($territory_detail as $item): ?>
                      <tr>
                        <td><?php echo htmlspecialchars($item['Category']); ?></td>
                        <td><?php echo htmlspecialchars($item['Subcategory']); ?></td>
                        <td><?php echo htmlspecialchars($item['ProductName']); ?></td>
                        <td class="text-end"><?php echo number_format($item['TotalQty']); ?></td>
                        <td class="text-end">$<?php echo number_format($item['TotalSales'], 2); ?></td>
                        <td class="text-end">$<?php echo number_format($item['AvgPrice'], 2); ?></td>
                        <td class="text-end"><?php echo number_format($item['UniqueCustomers']); ?></td>
                        <td class="text-end"><?php echo number_format($item['TransactionCount']); ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
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
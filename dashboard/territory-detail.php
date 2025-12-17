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

      <!-- Modern Hero Section -->
      <div class="page-hero">
        <h3>Territory Detail: <?php echo htmlspecialchars($territory); ?></h3>
        <p>Detailed product performance analysis for year <?php echo $year; ?></p>
      </div>

      <!-- Territory Detail Card -->
      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <h4>Product Performance</h4>
                  <p class="text-muted mb-0">Breakdown by category, subcategory, and product</p>
                </div>
                <button class="btn btn-primary" onclick="window.history.back()">
                  <i class="fas fa-arrow-left"></i> Back
                </button>
              </div>
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
                    <?php if (count($territory_detail) > 0): ?>
                      <?php foreach ($territory_detail as $item): ?>
                        <tr>
                          <td><strong><?php echo htmlspecialchars($item['Category']); ?></strong></td>
                          <td><?php echo htmlspecialchars($item['Subcategory']); ?></td>
                          <td><?php echo htmlspecialchars($item['ProductName']); ?></td>
                          <td class="text-end"><?php echo number_format($item['TotalQty']); ?></td>
                          <td class="text-end">$<?php echo number_format($item['TotalSales'], 2); ?></td>
                          <td class="text-end">$<?php echo number_format($item['AvgPrice'], 2); ?></td>
                          <td class="text-end"><?php echo number_format($item['UniqueCustomers']); ?></td>
                          <td class="text-end"><?php echo number_format($item['TransactionCount']); ?></td>
                        </tr>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <tr>
                        <td colspan="8" class="text-center">
                          <div style="padding: 40px 0;">
                            <i class="fas fa-inbox" style="font-size: 48px; color: #ccc;"></i>
                            <p class="text-muted mt-3">No data available for this territory</p>
                          </div>
                        </td>
                      </tr>
                    <?php endif; ?>
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
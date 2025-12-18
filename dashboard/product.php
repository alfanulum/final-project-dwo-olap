<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

$db = new Database();
$conn = $db->getConnection();

/* ========== QUERY 1: TOP 10 PRODUK PALING LAKU ========== */
$sqlQty = "
SELECT 
  dp.ProductName,
  SUM(fs.OrderQty) AS TotalQty
FROM factsales fs
JOIN dimproduct dp ON fs.ProductKey = dp.ProductKey
GROUP BY dp.ProductName
ORDER BY TotalQty DESC
LIMIT 10
";
$stmt = $conn->prepare($sqlQty);
$stmt->execute();
$topQty = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ========== QUERY 2: TOP 5 REVENUE ========== */
$sqlRevenue = "
SELECT 
  dp.ProductName,
  SUM(fs.LineTotal) AS TotalRevenue
FROM factsales fs
JOIN dimproduct dp ON fs.ProductKey = dp.ProductKey
GROUP BY dp.ProductName
ORDER BY TotalRevenue DESC
LIMIT 5
";
$stmt = $conn->prepare($sqlRevenue);
$stmt->execute();
$topRevenue = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ========== QUERY 3: KATEGORI PRODUK ========== */
$sqlCategory = "
SELECT 
  dp.Category,
  SUM(fs.LineTotal) AS TotalSales
FROM factsales fs
JOIN dimproduct dp ON fs.ProductKey = dp.ProductKey
GROUP BY dp.Category
ORDER BY TotalSales DESC
";
$stmt = $conn->prepare($sqlCategory);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ========== FORMAT DATA ========== */
$qtyLabels = $qtyData = [];
foreach ($topQty as $r) {
  $qtyLabels[] = $r['ProductName'];
  $qtyData[] = (int)$r['TotalQty'];
}

$revLabels = $revData = [];
$totalRevenueAll = 0;
foreach ($topRevenue as $r) {
  $revLabels[] = $r['ProductName'];
  $revData[] = round($r['TotalRevenue'], 2);
  $totalRevenueAll += $r['TotalRevenue'];
}

$catLabels = $catData = [];
foreach ($categories as $r) {
  $catLabels[] = $r['Category'];
  $catData[] = round($r['TotalSales'], 2);
}
?>

<?php include 'layouts/head.php'; ?>

<div class="page-body-wrapper">
  <?php include 'layouts/sidebar.php'; ?>

  <div class="page-body">
    <div class="container-fluid">

      <!-- HERO SECTION -->
      <div class="page-hero">
        <h3>📊 Product Analysis</h3>
        <p>Analisis performa produk berdasarkan penjualan dan kontribusi revenue</p>
      </div>

      <!-- TOP PRODUK PALING LAKU -->
      <div class="row">
        <div class="col-12">
          <div class="card card-animated">
            <div class="card-header">
              <div class="d-flex align-items-center justify-content-between">
                <div>
                  <h4>🏆 Top 10 Produk Paling Laku</h4>
                  <p class="text-muted mb-0">Berdasarkan total unit terjual</p>
                </div>
                <span class="badge-primary">Top Sellers</span>
              </div>
            </div>
            <div class="card-body">
              <canvas id="qtyChart" height="80"></canvas>
            </div>
          </div>
        </div>
      </div>

      <!-- REVENUE & KATEGORI -->
      <div class="row mb-4">
        <div class="col-12 col-md-6">
          <div class="card card-animated h-100">
            <div class="card-header">
              <h4>💰 Kontribusi Revenue Produk</h4>
              <p class="text-muted mb-0">Top 5 produk dengan revenue tertinggi</p>
            </div>
            <div class="card-body d-flex flex-column justify-content-center">
              <div class="chart-wrapper">
                <canvas id="revenueChart"></canvas>
              </div>
            </div>
          </div>
        </div>

        <!-- KATEGORI PRODUK -->
        <div class="col-12 col-md-6">
          <div class="card card-animated h-100">
            <div class="card-header">
              <h4>📦 Penjualan per Kategori</h4>
              <p class="text-muted mb-0">Total revenue berdasarkan kategori</p>
            </div>
            <div class="card-body">
              <canvas id="categoryChart"></canvas>
            </div>
          </div>
        </div>
      </div>

      <!-- TABLE DETAIL -->
      <div class="row">
        <div class="col-12">
          <div class="card card-animated">
            <div class="card-header">
              <h4>📋 Detail Top Produk (Revenue)</h4>
            </div>
            <div class="card-body table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Produk</th>
                    <th>Total Revenue</th>
                    <th>Kontribusi</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($topRevenue as $idx => $r): ?>
                    <tr class="table-row-animated" style="animation-delay: <?= $idx * 0.1 ?>s">
                      <td><span class="rank-badge"><?= $idx + 1 ?></span></td>
                      <td><strong><?= htmlspecialchars($r['ProductName']); ?></strong></td>
                      <td><span class="revenue-text">Rp <?= number_format($r['TotalRevenue'], 0, ',', '.'); ?></span></td>
                      <td>
                        <div class="progress-wrapper">
                          <span class="progress-label"><?= round(($r['TotalRevenue'] / $totalRevenueAll) * 100, 1); ?>%</span>
                          <div class="progress-bar">
                            <div class="progress-fill" style="width: <?= round(($r['TotalRevenue'] / $totalRevenueAll) * 100, 1); ?>%"></div>
                          </div>
                        </div>
                      </td>
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

  <?php include 'layouts/footer.php'; ?>
</div>

<?php include 'layouts/tail.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
  // BAR - PRODUK PALING LAKU
  new Chart(document.getElementById('qtyChart'), {
    type: 'bar',
    data: {
      labels: <?= json_encode($qtyLabels); ?>,
      datasets: [{
        label: 'Unit Terjual',
        data: <?= json_encode($qtyData); ?>,
        backgroundColor: 'rgba(13, 148, 136, 0.8)',
        borderRadius: 8,
        borderSkipped: false
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      plugins: {
        legend: {
          display: false
        },
        tooltip: {
          backgroundColor: 'rgba(0, 0, 0, 0.8)',
          padding: 12,
          titleFont: {
            size: 14,
            weight: 'bold'
          },
          bodyFont: {
            size: 13
          },
          callbacks: {
            label: (ctx) => `Terjual: ${ctx.raw.toLocaleString('id-ID')} unit`
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          grid: {
            color: 'rgba(0, 0, 0, 0.05)'
          },
          ticks: {
            font: {
              size: 11
            }
          }
        },
        x: {
          grid: {
            display: false
          },
          ticks: {
            font: {
              size: 11
            },
            maxRotation: 45,
            minRotation: 45
          }
        }
      },
      animation: {
        duration: 1500,
        easing: 'easeOutQuart'
      }
    }
  });

  // DOUGHNUT - KONTRIBUSI REVENUE
  new Chart(document.getElementById('revenueChart'), {
    type: 'doughnut',
    data: {
      labels: <?= json_encode($revLabels); ?>,
      datasets: [{
        data: <?= json_encode($revData); ?>,
        backgroundColor: [
          '#0d9488',
          '#14b8a6',
          '#2dd4bf',
          '#5eead4',
          '#99f6e4'
        ],
        borderWidth: 3,
        borderColor: '#fff'
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      cutout: '70%',
      plugins: {
        legend: {
          position: 'bottom',
          labels: {
            padding: 15,
            font: {
              size: 12
            },
            usePointStyle: true,
            pointStyle: 'circle'
          }
        },
        tooltip: {
          backgroundColor: 'rgba(0, 0, 0, 0.8)',
          padding: 12,
          callbacks: {
            label: (ctx) => {
              const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
              const pct = ((ctx.raw / total) * 100).toFixed(1);
              return `${ctx.label}: Rp ${ctx.raw.toLocaleString('id-ID')} (${pct}%)`;
            }
          }
        }
      },
      animation: {
        animateRotate: true,
        animateScale: true,
        duration: 1500,
        easing: 'easeOutQuart'
      }
    }
  });

  // BAR HORIZONTAL - KATEGORI
  new Chart(document.getElementById('categoryChart'), {
    type: 'bar',
    data: {
      labels: <?= json_encode($catLabels); ?>,
      datasets: [{
        label: 'Total Penjualan',
        data: <?= json_encode($catData); ?>,
        backgroundColor: 'rgba(59, 130, 246, 0.8)',
        borderRadius: 8,
        borderSkipped: false
      }]
    },
    options: {
      indexAxis: 'y',
      responsive: true,
      maintainAspectRatio: true,
      plugins: {
        legend: {
          display: false
        },
        tooltip: {
          backgroundColor: 'rgba(0, 0, 0, 0.8)',
          padding: 12,
          callbacks: {
            label: (ctx) => `Revenue: Rp ${ctx.raw.toLocaleString('id-ID')}`
          }
        }
      },
      scales: {
        x: {
          grid: {
            color: 'rgba(0, 0, 0, 0.05)'
          },
          ticks: {
            font: {
              size: 11
            },
            callback: (v) => 'Rp ' + (v / 1000000).toFixed(0) + 'M'
          }
        },
        y: {
          grid: {
            display: false
          },
          ticks: {
            font: {
              size: 12
            }
          }
        }
      },
      animation: {
        duration: 1500,
        easing: 'easeOutQuart'
      }
    }
  });
</script>

<style>
  /* CARD ANIMATIONS */
  .card-animated {
    animation: slideUp 0.6s ease-out backwards;
  }

  .card-animated:nth-child(1) {
    animation-delay: 0.1s;
  }

  .card-animated:nth-child(2) {
    animation-delay: 0.2s;
  }

  .card-animated:nth-child(3) {
    animation-delay: 0.3s;
  }

  @keyframes slideUp {
    from {
      opacity: 0;
      transform: translateY(30px);
    }

    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  /* BADGE */
  .badge-primary {
    background: linear-gradient(135deg, #0d9488, #14b8a6);
    color: white;
    padding: 6px 16px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 0.5px;
  }

  /* TABLE ANIMATIONS */
  .table-row-animated {
    animation: fadeInRow 0.5s ease-out backwards;
  }

  @keyframes fadeInRow {
    from {
      opacity: 0;
      transform: translateX(-20px);
    }

    to {
      opacity: 1;
      transform: translateX(0);
    }
  }

  /* RANK BADGE */
  .rank-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    background: linear-gradient(135deg, #0d9488, #14b8a6);
    color: white;
    border-radius: 8px;
    font-weight: 700;
    font-size: 14px;
  }

  /* REVENUE TEXT */
  .revenue-text {
    color: #0d9488;
    font-weight: 600;
  }

  /* PROGRESS BAR */
  .progress-wrapper {
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .progress-label {
    min-width: 45px;
    font-weight: 600;
    color: #0d9488;
    font-size: 14px;
  }

  .progress-bar {
    flex: 1;
    height: 8px;
    background: #e5e7eb;
    border-radius: 10px;
    overflow: hidden;
  }

  .progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #0d9488, #14b8a6);
    border-radius: 10px;
    transition: width 1s ease-out;
    animation: progressAnimation 1.5s ease-out;
  }

  @keyframes progressAnimation {
    from {
      width: 0 !important;
    }
  }

  /* CHART WRAPPER */
  .chart-wrapper {
    position: relative;
    height: 300px;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  /* RESPONSIVE */
  @media (max-width: 768px) {
    .badge-primary {
      display: none;
    }

    .chart-wrapper {
      height: 250px;
    }

    .progress-wrapper {
      flex-direction: column;
      align-items: flex-start;
      gap: 6px;
    }

    .progress-bar {
      width: 100%;
    }
  }
</style>
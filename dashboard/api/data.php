<?php
// api/data.php
require_once "../config/database.php";
header('Content-Type: application/json');

$db = new Database();
$conn = $db->getConnection();

$filterYear = isset($_GET['year']) ? $_GET['year'] : null;
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Base Query Logic
$whereClause = " WHERE t.Year >= 2021 ";
$params = [];

if ($filterYear) {
  $whereClause .= " AND t.Year = :year ";
  $params[':year'] = $filterYear;
}

try {
  switch ($action) {
    case 'bq1': // Drill-down: Year -> Month
      if ($filterYear) {
        // View Bulanan (jika tahun dipilih)
        $sql = "SELECT t.MonthName as Label, SUM(f.LineTotal) as Total 
                        FROM factsales f
                        JOIN dimtime t ON f.TimeKey = t.TimeKey
                        $whereClause
                        GROUP BY t.Month, t.MonthName ORDER BY t.Month ASC";
      } else {
        // View Tahunan (Default)
        $sql = "SELECT t.Year as Label, SUM(f.LineTotal) as Total 
                        FROM factsales f
                        JOIN dimtime t ON f.TimeKey = t.TimeKey
                        $whereClause
                        GROUP BY t.Year ORDER BY t.Year ASC";
      }
      break;

    case 'bq2': // Frequency Distribution
      $sql = "SELECT PurchaseCount as Label, COUNT(CustomerKey) as Total 
                    FROM (
                        SELECT CustomerKey, COUNT(SalesKey) as PurchaseCount 
                        FROM factsales f 
                        JOIN dimtime t ON f.TimeKey = t.TimeKey
                        $whereClause
                        GROUP BY CustomerKey
                    ) as SubQuery
                    GROUP BY PurchaseCount ORDER BY PurchaseCount ASC LIMIT 10"; // Limit biar rapi
      break;

    case 'bq3': // Urban vs Rural
      $sql = "SELECT t.MonthName, g.UrbanFlag, SUM(f.LineTotal) as Total 
                    FROM factsales f
                    JOIN dimtime t ON f.TimeKey = t.TimeKey
                    JOIN dimgeography g ON f.GeographyKey = g.GeographyKey
                    $whereClause
                    GROUP BY t.Month, t.MonthName, g.UrbanFlag 
                    ORDER BY t.Month ASC";
      break;

    case 'bq4': // Online vs Offline
      $sql = "SELECT f.SalesChannel as Label, SUM(f.LineTotal) as Total 
                    FROM factsales f
                    JOIN dimtime t ON f.TimeKey = t.TimeKey
                    $whereClause
                    GROUP BY f.SalesChannel";
      break;

    case 'bq5': // Top Salesperson
      $sql = "SELECT CONCAT(sp.FirstName, ' ', sp.LastName) as Label, SUM(f.LineTotal) as Total 
                    FROM factsales f
                    JOIN dimsalesperson sp ON f.SalesPersonKey = sp.SalesPersonKey
                    JOIN dimtime t ON f.TimeKey = t.TimeKey
                    $whereClause
                    GROUP BY sp.SalesPersonKey
                    ORDER BY Total DESC LIMIT 5";
      break;

    default:
      $response = [];
      break;
  }

  if (isset($sql)) {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $response = $stmt->fetchAll(PDO::FETCH_ASSOC);
  }
} catch (Exception $e) {
  $response = ['error' => $e->getMessage()];
}

echo json_encode($response);

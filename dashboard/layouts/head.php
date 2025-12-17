<?php
require_once "config/database.php";

$db = new Database();
$conn = $db->getConnection();

// cek cookie
if (!isset($_COOKIE['login_token']) || !isset($_COOKIE['user_name'])) {
  header("Location: login.php");
  exit;
}

$username = $_COOKIE['user_name'];
$token    = $_COOKIE['login_token'];

// query pakai PDO
$stmt = $conn->prepare("
    SELECT UserKey, UserName
    FROM users
    WHERE UserName = :username
");
$stmt->bindParam(':username', $username);
$stmt->execute();

if ($stmt->rowCount() !== 1) {
  header("Location: login.php");
  exit;
}

$user = $stmt->fetch(PDO::FETCH_ASSOC);

$validToken = hash('sha256', $user['UserKey'] . $user['UserName']);

if ($token !== $validToken) {
  header("Location: login.php");
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Modern dashboard for AdventureWorks data warehouse">
  <meta name="keywords" content="dashboard, data warehouse, analytics, adventureworks">
  <meta name="author" content="AdventureWorks">
  <link rel="icon" href="./assets/images/favicon.png" type="image/x-icon">
  <link rel="shortcut icon" href="./assets/images/favicon.png" type="image/x-icon">
  <title>Dashboard - AdventureWorks</title>

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <!-- Custom CSS -->
  <style>
    /* Include the Modern Dashboard CSS here or link to external file */
    <?php include 'assets/css/style.css'; ?>
  </style>
</head>

<body>
  <!-- loader starts-->
  <div class="loader-wrapper">
    <div class="loader">
      <div class="loader4"></div>
    </div>
  </div>
  <!-- loader ends-->

  <!-- tap on top starts-->
  <div class="tap-top">
    <i class="fas fa-chevron-up"></i>
  </div>
  <!-- tap on tap ends-->

  <!-- page-wrapper Start-->
  <div class="page-wrapper compact-wrapper" id="pageWrapper">
    <!-- Page Header Start-->
    <div class="page-header">
      <div class="header-wrapper row m-0">
        <!-- Search -->
        <form class="form-inline search-full col" action="#" method="get">
          <div class="form-group w-100">
            <input class="form-control" type="text" placeholder="Search..." name="q">
          </div>
        </form>

        <!-- Welcome Section -->
        <div class="left-header col-xxl-5 col-xl-6 col-lg-5 col-md-4 col-sm-3 p-0">
          <div class="d-flex align-items-center gap-2">
            <h4 class="f-w-600">Welcome <?php echo htmlspecialchars($username); ?> 👋</h4>
          </div>
          <div class="welcome-content d-xl-block d-none">
            <span>Here's what's happening with your store today.</span>
          </div>
        </div>

        <!-- Right Navigation -->
        <div class="nav-right col-xxl-7 col-xl-6 col-md-7 col-8 pull-right right-header p-0 ms-auto">
          <a class="btn btn-outline-primary btn-sm" href="logout.php">
            <i class="fas fa-sign-out-alt"></i> Logout
          </a>
        </div>
      </div>
    </div>
    <!-- Page Header Ends-->
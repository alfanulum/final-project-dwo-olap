<?php
require_once "config/database.php";

$db = new Database();
$conn = $db->getConnection();

// Check cookie authentication
if (!isset($_COOKIE['login_token']) || !isset($_COOKIE['user_name'])) {
  header("Location: login.php");
  exit;
}

$username = $_COOKIE['user_name'];
$token = $_COOKIE['login_token'];

// Verify user with PDO
$stmt = $conn->prepare("SELECT UserKey, UserName FROM users WHERE UserName = :username");
$stmt->bindParam(':username', $username, PDO::PARAM_STR);
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
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Modern dashboard for AdventureWorks data warehouse">
  <meta name="keywords" content="dashboard, data warehouse, analytics, adventureworks">
  <meta name="author" content="AdventureWorks">

  <!-- Favicons -->
  <link rel="icon" href="./assets/images/favicon.png" type="image/x-icon">
  <link rel="shortcut icon" href="./assets/images/favicon.png" type="image/x-icon">

  <title>Dashboard - AdventureWorks</title>

  <!-- Google Fonts - Preload for better performance -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer">

  <!-- Custom CSS -->
  <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
  <!-- Loader -->
  <div class="loader-wrapper">
    <div class="loader4"></div>
  </div>

  <!-- Scroll to Top Button -->
  <div class="tap-top">
    <i class="fas fa-chevron-up"></i>
  </div>

  <!-- Page Wrapper -->
  <div class="page-wrapper">
    <!-- Page Header -->
    <div class="page-header">
      <div class="header-wrapper">
        <!-- Welcome Section -->
        <div class="left-header">
          <h4>Welcome <?php echo htmlspecialchars($username); ?> 👋</h4>
        </div>

        <!-- Right Navigation -->
        <div class="nav-right">
          <a class="btn-outline-primary" href="logout.php">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
          </a>
        </div>
      </div>
    </div>
    <!-- Page Header Ends -->
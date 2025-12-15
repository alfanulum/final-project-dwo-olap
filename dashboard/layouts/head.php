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
  <meta name="description"
    content="Riho admin is super flexible, powerful, clean &amp; modern responsive bootstrap 5 admin template with unlimited possibilities.">
  <meta name="keywords"
    content="admin template, Riho admin template, dashboard template, flat admin template, responsive admin template, web app">
  <meta name="author" content="pixelstrap">
  <link rel="icon" href="./assets/images/favicon.png" type="image/x-icon">
  <link rel="shortcut icon" href="./assets/images/favicon.png" type="image/x-icon">
  <title>Riho - Premium Admin Template</title>
  <!-- Google font-->
  <link rel="preconnect" href="https://fonts.googleapis.com/">
  <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin="">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@200;300;400;500;600;700;800&amp;display=swap"
    rel="stylesheet">
  <link rel="stylesheet" type="text/css" href="./assets/css/font-awesome.css">
  <!-- ico-font-->
  <link rel="stylesheet" type="text/css" href="./assets/css/vendors/icofont.css">
  <!-- Themify icon-->
  <link rel="stylesheet" type="text/css" href="./assets/css/vendors/themify.css">
  <!-- Flag icon-->
  <link rel="stylesheet" type="text/css" href="./assets/css/vendors/flag-icon.css">
  <!-- Feather icon-->
  <link rel="stylesheet" type="text/css" href="./assets/css/vendors/feather-icon.css">
  <!-- Plugins css start-->
  <link rel="stylesheet" type="text/css" href="./assets/css/vendors/slick.css">
  <link rel="stylesheet" type="text/css" href="./assets/css/vendors/slick-theme.css">
  <link rel="stylesheet" type="text/css" href="./assets/css/vendors/scrollbar.css">
  <link rel="stylesheet" type="text/css" href="./assets/css/vendors/animate.css">
  <link rel="stylesheet" type="text/css" href="./assets/css/vendors/echart.css">
  <link rel="stylesheet" type="text/css" href="./assets/css/vendors/date-picker.css">
  <!-- Plugins css Ends-->
  <!-- Bootstrap css-->
  <link rel="stylesheet" type="text/css" href="./assets/css/vendors/bootstrap.css">
  <!-- App css-->
  <link rel="stylesheet" type="text/css" href="./assets/css/style.css">
  <link id="color" rel="stylesheet" href="./assets/css/color-1.css" media="screen">
  <!-- Responsive css-->
  <link rel="stylesheet" type="text/css" href="./assets/css/responsive.css">
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
  <div class="tap-top"><i data-feather="chevrons-up"></i></div>
  <!-- tap on tap ends-->
  <!-- page-wrapper Start-->
  <div class="page-wrapper compact-wrapper" id="pageWrapper">
    <!-- Page Header Start-->
    <div class="page-header">
      <div class="header-wrapper row m-0">
        <form class="form-inline search-full col" action="#" method="get">
          <div class="form-group w-100">
            <div class="Typeahead Typeahead--twitterUsers">
              <div class="u-posRelative">
                <input class="demo-input Typeahead-input form-control-plaintext w-100" type="text"
                  placeholder="Search Riho .." name="q" title="" autofocus>
                <div class="spinner-border Typeahead-spinner" role="status"><span class="sr-only">Loading... </span>
                </div><i class="close-search" data-feather="x"></i>
              </div>
              <div class="Typeahead-menu"> </div>
            </div>
          </div>
        </form>
        <div class="header-logo-wrapper col-auto p-0">
          <div class="logo-wrapper">
            <a href="index-2.html">
              Dashboard
            </a>
          </div>
          <div class="toggle-sidebar"> <i class="status_toggle middle sidebar-toggle" data-feather="align-center"></i>
          </div>
        </div>
        <div class="left-header col-xxl-5 col-xl-6 col-lg-5 col-md-4 col-sm-3 p-0">
          <div> <a class="toggle-sidebar" href="#"> <i class="iconly-Category icli"> </i></a>
            <div class="d-flex align-items-center gap-2 ">
              <h4 class="f-w-600">Welcome Admin</h4><img class="mt-0" src="./assets/images/hand.gif" alt="hand-gif">
            </div>
          </div>
          <div class="welcome-content d-xl-block d-none"><span class="text-truncate col-12">Here’s what’s happening with
              your store today. </span></div>
        </div>
        <div class="nav-right col-xxl-7 col-xl-6 col-md-7 col-8 pull-right right-header p-0 ms-auto">
          <a class="btn btn-pill btn-outline-primary btn-sm" href="logout.php">Keluar</a>
        </div>
      </div>
    </div>
    <!-- Page Header Ends-->
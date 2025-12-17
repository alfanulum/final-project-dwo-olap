<?php
require_once "config/database.php";

$error = "";

// Buat koneksi PDO
$database = new Database();
$conn = $database->getConnection();

// Jika sudah login (cookie ada)
if (isset($_COOKIE['login_token'])) {
  header("Location: index.php");
  exit;
}

if (isset($_POST['login'])) {

  $username = $_POST['username'];
  $password = hash('sha256', $_POST['password']);

  $sql = "SELECT * FROM users 
          WHERE UserName = :username 
          AND UserPassword = :password";

  $stmt = $conn->prepare($sql);
  $stmt->bindParam(':username', $username);
  $stmt->bindParam(':password', $password);
  $stmt->execute();

  if ($stmt->rowCount() === 1) {
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // token sederhana (untuk tugas)
    $token = hash('sha256', $user['UserKey'] . $user['UserName']);

    // set cookie (1 hari)
    setcookie("login_token", $token, time() + 86400, "/", "", false, true);
    setcookie("user_name", $user['UserName'], time() + 86400, "/", "", false, true);

    header("Location: index.php");
    exit;
  } else {
    $error = "Username atau password salah!";
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Dashboard</title>

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <!-- Custom CSS -->
  <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
  <div class="login-card">
    <div class="login-main">
      <div style="text-align: center; margin-bottom: 32px;">
        <div class="login-brand">
          <i class="fas fa-chart-line"></i>
        </div>
        <h4>Welcome Back!</h4>
        <p>Sign in to access your dashboard</p>
      </div>

      <form method="POST">
        <?php if ($error): ?>
          <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?= $error ?>
          </div>
        <?php endif; ?>

        <div class="form-group">
          <label>Username</label>
          <input class="form-control"
            type="text"
            name="username"
            required
            placeholder="Enter your username"
            autofocus>
        </div>

        <div class="form-group">
          <label>Password</label>
          <input class="form-control"
            type="password"
            name="password"
            required
            placeholder="Enter your password">
        </div>

        <div class="form-group mb-0">
          <button class="btn btn-primary w-100"
            type="submit"
            name="login">
            <i class="fas fa-sign-in-alt"></i> Sign In
          </button>
        </div>
      </form>

      <div style="text-align: center; margin-top: 24px; padding-top: 24px; border-top: 1px solid #e5e7eb;">
        <p style="font-size: 13px; color: #6b7280; margin: 0;">
          © 2024 AdventureWorks Dashboard
        </p>
      </div>
    </div>
  </div>

  <script src="assets/js/modern-dashboard.js"></script>
</body>

</html>
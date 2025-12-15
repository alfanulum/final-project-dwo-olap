<?php
require_once "config/database.php";

$error = "";

// Jika sudah login (cookie ada)
if (isset($_COOKIE['login_token'])) {
  header("Location: index.php");
  exit;
}

if (isset($_POST['login'])) {

  $username = mysqli_real_escape_string($conn, $_POST['username']);
  $password = hash('sha256', $_POST['password']);

  $query = mysqli_query($conn, "
        SELECT * FROM users
        WHERE UserName='$username'
        AND UserPassword='$password'
    ");

  if (mysqli_num_rows($query) == 1) {
    $user = mysqli_fetch_assoc($query);

    // token sederhana (untuk tugas)
    $token = hash('sha256', $user['UserKey'] . $user['UserName']);

    // set cookie (1 hari)
    setcookie("login_token", $token, time() + (86400), "/", "", false, true);
    setcookie("user_name", $user['UserName'], time() + (86400), "/", "", false, true);

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
  <title>Login</title>

  <!-- (CSS ASLI TIDAK DIUBAH) -->
  <link rel="stylesheet" href="./assets/css/vendors/bootstrap.css">
  <link rel="stylesheet" href="./assets/css/style.css">
</head>

<body>
  <div class="container-fluid p-0">
    <div class="row m-0">
      <div class="col-12 p-0">
        <div class="login-card login-dark">
          <div>
            <div class="login-main">
              <form class="theme-form" method="POST">
                <h4>Sign in to account</h4>
                <p>Enter username & password</p>

                <?php if ($error): ?>
                  <div class="alert alert-danger">
                    <?= $error ?>
                  </div>
                <?php endif; ?>

                <div class="form-group">
                  <label class="col-form-label">Username</label>
                  <input class="form-control"
                    type="text"
                    name="username"
                    required
                    placeholder="admin">
                </div>

                <div class="form-group">
                  <label class="col-form-label">Password</label>
                  <div class="form-input position-relative">
                    <input class="form-control"
                      type="password"
                      name="password"
                      required
                      placeholder="********">
                  </div>
                </div>

                <div class="form-group mb-0 text-end mt-3">
                  <button class="btn btn-primary w-100"
                    type="submit"
                    name="login">
                    Sign in
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>

</html>
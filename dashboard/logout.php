<?php
setcookie("login_token", "", time() - 3600, "/");
setcookie("user_name", "", time() - 3600, "/");

header("Location: login.php");
exit;

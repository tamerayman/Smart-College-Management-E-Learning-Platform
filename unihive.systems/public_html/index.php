<?php
// صفحة تسجيل الدخول

require_once 'auth.php';

// إذا كان المستخدم مسجل دخوله بالفعل، قم بتوجيهه حسب نوع حسابه
if (isLoggedIn()) {
    redirectUserByRole($_SESSION["role"]);
}

// رسائل الخطأ
$error_message = "";
$error_class = "";
if (isset($_GET["error"])) {
    $error_class = "error-alert";
    switch ($_GET["error"]) {
        case "invalid":
            $error_message = "Invalid email or password";
            break;
        case "role":
            $error_message = "Unknown user role";
            break;
        case "inactive":
            $error_message = "Your account is inactive";
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Login</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="index.css">
</head>
<body>

<div class="container">
   <div class="form-box login">
    <?php if (!empty($error_message)): ?>
        <div class="<?php echo $error_class; ?>"><?php echo $error_message; ?></div>
    <?php endif; ?>
    <form action="login_process.php" method="post">
       <h1>login</h1>
       <div class="input-box">
         <input type="text" name="email" placeholder="email" required>
         <i class='bx bxs-user'></i>
       </div>
       <div class="input-box">
         <input type="password" name="password" placeholder="password" required>
         <i class='bx bxs-lock-alt'></i>
       </div>
       <button type="submit" class="btn">login</button>
    </form>
   </div>

   <div class="toggle-box">
       <div class="toggle-panel toggle-left">
           <img src="home/logo_white.png" alt="UniHive" style="width: 250px; margin-bottom: 20px;">
       </div>
   </div>
</div>
</body>
</html>